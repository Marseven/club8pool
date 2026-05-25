<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Competition;
use App\Models\GameMatch;
use App\Services\KnockoutGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class KnockoutController extends Controller
{
    public function show(): Response
    {
        $competition = Competition::with('pools')->firstOrFail();
        $generator = new KnockoutGenerator();

        $qualifiers = $generator->qualifiers($competition);
        $ties = $generator->ties($qualifiers, $competition);
        $pairs = $generator->seedPairs($qualifiers);

        // Matchs déjà créés s'ils existent
        $existing = GameMatch::with(['playerA', 'playerB'])
            ->where('competition_id', $competition->id)
            ->where('phase', 'knockout')
            ->orderBy('round')
            ->orderBy('round_position')
            ->get()
            ->groupBy('round');

        $poolMatches = GameMatch::where('competition_id', $competition->id)
            ->where('phase', 'pool')
            ->get();
        $poolDone = $poolMatches->where('status', 'done')->count();
        $poolTotal = $poolMatches->count();

        return Inertia::render('Admin/Knockout', [
            'competition' => $competition,
            'qualifiers' => $qualifiers,
            'ties' => $ties,
            'pairs' => $pairs,
            'existing' => $existing,
            'progress' => [
                'pool_done' => $poolDone,
                'pool_total' => $poolTotal,
                'pool_ready' => $poolDone === $poolTotal && $poolTotal > 0,
            ],
        ]);
    }

    public function generate(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'pairs' => ['required', 'array', 'min:1'],
            'pairs.*' => ['array', 'size:2'],
            'pairs.*.0.player_id' => ['nullable', 'integer', 'exists:players,id'],
            'pairs.*.1.player_id' => ['nullable', 'integer', 'exists:players,id'],
        ]);

        $competition = Competition::firstOrFail();

        // Sanity : on attend autant de paires que (player_slots / 2) à la louche
        (new KnockoutGenerator())->generate($competition, $data['pairs']);

        return back()->with('success', count($data['pairs']) . ' matchs de phase finale créés.');
    }
}
