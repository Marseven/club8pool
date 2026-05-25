<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Competition;
use App\Services\PoolResultsImporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ImportController extends Controller
{
    public function show(Request $request): Response
    {
        $preview = $request->session()->get('import_preview');
        $competition = Competition::with('pools')->firstOrFail();

        return Inertia::render('Admin/Import', [
            'competition' => $competition,
            'preview' => $preview,
        ]);
    }

    public function preview(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:5120'],
        ]);

        $competition = Competition::with('pools.registrations.player')->firstOrFail();

        $path = $request->file('file')->store('imports');
        $fullPath = Storage::path($path);

        try {
            $importer = new PoolResultsImporter();
            $stats = $importer->parse($fullPath, $competition);
        } catch (\Throwable $e) {
            Storage::delete($path);
            return back()->with('error', 'Lecture du fichier impossible : ' . $e->getMessage());
        }

        // garde le résultat parsé en session pour le commit
        $request->session()->put('import_preview', [
            'file' => $path,
            'filename' => $request->file('file')->getClientOriginalName(),
            'stats' => $stats,
            'parsed_at' => now()->toIso8601String(),
        ]);

        return back();
    }

    public function commit(Request $request): RedirectResponse
    {
        $preview = $request->session()->pull('import_preview');
        if (! $preview) {
            return redirect()->route('admin.import.show')->with('error', 'Aucun aperçu en session — re-uploadez le fichier.');
        }

        $importer = new PoolResultsImporter();
        $result = $importer->apply($preview['stats']['matches']);

        // cleanup
        if (! empty($preview['file'])) {
            Storage::delete($preview['file']);
        }

        $msg = $result['applied'] . ' matchs importés.';
        if (! empty($result['errors'])) {
            $msg .= ' ' . count($result['errors']) . ' erreurs.';
        }

        return redirect()->route('admin.pools.index')->with('success', $msg);
    }

    public function cancel(Request $request): RedirectResponse
    {
        $preview = $request->session()->pull('import_preview');
        if ($preview && ! empty($preview['file'])) {
            Storage::delete($preview['file']);
        }
        return back()->with('success', 'Aperçu annulé.');
    }
}
