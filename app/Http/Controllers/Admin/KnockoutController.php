<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Competition;
use App\Models\GameMatch;
use App\Models\PoolTable;
use App\Models\User;
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
            'qualifiers'  => $qualifiers,
            'ties'        => $ties,
            'pairs'       => $pairs,
            'existing'    => $existing,
            'tables'      => PoolTable::orderBy('id')->get(),
            'referees'    => User::where('role', 'referee')->orderBy('name')->get(['id', 'name', 'title']),
            'progress'    => [
                'pool_done'  => $poolDone,
                'pool_total' => $poolTotal,
                'pool_ready' => $poolDone === $poolTotal && $poolTotal > 0,
            ],
        ]);
    }

    public function startMatch(Request $request, GameMatch $match): RedirectResponse
    {
        $data = $request->validate([
            'pool_table_id' => ['required', 'exists:pool_tables,id'],
            'referee_id'    => ['nullable', 'exists:users,id'],
        ]);
        $match->update([
            'status'       => 'live',
            'pool_table_id' => $data['pool_table_id'],
            'referee_id'   => $data['referee_id'] ?? null,
            'started_at'   => now(),
        ]);
        return back()->with('success', 'Match lancé.');
    }

    public function scoreFrame(Request $request, GameMatch $match): RedirectResponse
    {
        $data = $request->validate(['player' => ['required', 'in:A,B']]);
        if ($data['player'] === 'A') $match->increment('score_a');
        else $match->increment('score_b');
        if ($match->status !== 'live') {
            $match->update(['status' => 'live', 'started_at' => $match->started_at ?? now()]);
        }
        return back();
    }

    public function undoFrame(Request $request, GameMatch $match): RedirectResponse
    {
        $data = $request->validate(['player' => ['required', 'in:A,B']]);
        if ($data['player'] === 'A' && $match->score_a > 0) $match->decrement('score_a');
        elseif ($data['player'] === 'B' && $match->score_b > 0) $match->decrement('score_b');
        return back();
    }

    public function closeMatch(Request $request, GameMatch $match): RedirectResponse
    {
        $data = $request->validate([
            'score_a' => ['required', 'integer', 'min:0'],
            'score_b' => ['required', 'integer', 'min:0'],
        ]);
        $data['status']   = 'done';
        $data['ended_at'] = now();
        if ($match->started_at && ! $match->ended_at) {
            $data['duration_seconds'] = (int) $match->started_at->diffInSeconds(now());
        }
        $match->update($data);
        (new \App\Services\BracketProgression())->advanceWinner($match->fresh());
        return back()->with('success', 'Match clôturé.');
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
