<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCompetitionRequest;
use App\Http\Requests\Admin\UpdateCompetitionRequest;
use App\Http\Requests\Admin\UploadCompetitionLogoRequest;
use App\Models\Competition;
use App\Models\Pool;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CompetitionController extends Controller
{
    public function index(): Response
    {
        $competitions = Competition::orderByDesc('starts_on')->withCount(['registrations', 'matches'])->get();

        return Inertia::render('Admin/Competitions/Index', [
            'competitions' => $competitions,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Competition::class);

        return Inertia::render('Admin/Competitions/Create');
    }

    public function store(StoreCompetitionRequest $request): RedirectResponse
    {
        $this->authorize('create', Competition::class);
        $data = $request->validated();
        $data['slug'] = Str::slug($data['name']) . '-' . now()->timestamp;
        $data['status'] = 'draft';
        $data['format'] = $this->mapFormat($data['structure']);

        $comp = Competition::create($data);

        // Auto-create empty pools when applicable.
        if (in_array($comp->structure, ['pools_knockout', 'pools_only'])) {
            $count = max(1, (int) $comp->pool_count);
            for ($i = 0; $i < $count; $i++) {
                Pool::create([
                    'competition_id' => $comp->id,
                    'name' => chr(ord('A') + $i),
                    'position' => $i,
                    'size' => $comp->pool_size,
                ]);
            }
        }

        AuditLogService::log('competition.created', $comp, [], ['name' => $comp->name, 'structure' => $comp->structure], $comp->id);

        return redirect()->route('admin.competitions.show', $comp);
    }

    public function show(Competition $competition): Response
    {
        return Inertia::render('Admin/Competitions/Show', [
            'competition' => $competition->load(['tables', 'pools', 'registrations.player.club']),
        ]);
    }

    public function edit(Competition $competition): Response
    {
        $this->authorize('update', $competition);

        return Inertia::render('Admin/Competitions/Edit', [
            'competition' => $competition,
        ]);
    }

    public function update(UpdateCompetitionRequest $request, Competition $competition): RedirectResponse
    {
        $this->authorize('update', $competition);
        $data = $request->validated();
        $data['format'] = $this->mapFormat($data['structure']);
        $competition->update($data);

        AuditLogService::log('competition.updated', $competition, [], ['name' => $competition->name], $competition->id);

        return redirect()->route('admin.competitions.show', $competition)->with('success', 'Compétition mise à jour.');
    }

    public function uploadLogo(UploadCompetitionLogoRequest $request, Competition $competition): RedirectResponse
    {
        $this->authorize('update', $competition);

        if ($competition->logo_path && Storage::disk('public')->exists($competition->logo_path)) {
            Storage::disk('public')->delete($competition->logo_path);
        }

        $path = $request->file('logo')->store('competitions/logos', 'public');
        $competition->update(['logo_path' => $path]);

        return back()->with('success', 'Logo mis à jour.');
    }

    public function removeLogo(Competition $competition): RedirectResponse
    {
        $this->authorize('update', $competition);

        if ($competition->logo_path && Storage::disk('public')->exists($competition->logo_path)) {
            Storage::disk('public')->delete($competition->logo_path);
        }
        $competition->update(['logo_path' => null]);

        return back()->with('success', 'Logo retiré.');
    }

    public function archive(Competition $competition): RedirectResponse
    {
        $this->authorize('update', $competition);
        $competition->update(['status' => 'finished']);

        return redirect()->route('admin.competitions.show', $competition)
            ->with('success', 'Compétition archivée.');
    }

    public function activate(Competition $competition, string $status): RedirectResponse
    {
        $this->authorize('update', $competition);
        abort_unless(in_array($status, ['draft', 'registration', 'in_progress']), 422);
        $competition->update(['status' => $status]);

        return redirect()->route('admin.competitions.show', $competition)
            ->with('success', 'Statut mis à jour.');
    }

    private function mapFormat(string $structure): string
    {
        return match ($structure) {
            'knockout' => 'single_elim',
            'pools_knockout', 'pools_only' => 'pools',
            'round_robin' => 'round_robin',
            default => 'single_elim',
        };
    }
}
