<?php

namespace App\Http\Controllers\Referee;

use App\Http\Controllers\Controller;
use App\Models\GameMatch;
use App\Models\Signature;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RefereeController extends Controller
{
    public function queue(Request $request): Response
    {
        $user = $request->user();

        $matches = GameMatch::with(['playerA', 'playerB', 'table', 'competition'])
            ->where('referee_id', $user->id)
            ->orderBy('scheduled_at')
            ->get();

        return Inertia::render('Referee/Queue', [
            'matches' => $matches,
        ]);
    }

    public function tables(): Response
    {
        $tables = \App\Models\PoolTable::with([
            'liveMatch.playerA', 'liveMatch.playerB', 'liveMatch.referee', 'liveMatch.pool',
            'competition',
        ])->orderBy('id')->get();

        return Inertia::render('Referee/Tables', [
            'tables' => $tables,
        ]);
    }

    public function preMatch(GameMatch $match): Response
    {
        return Inertia::render('Referee/PreMatch', [
            'match' => $match->load(['playerA.club', 'playerB.club', 'table', 'competition']),
        ]);
    }

    public function live(GameMatch $match): Response
    {
        return Inertia::render('Referee/Live', [
            'match' => $match->load(['playerA.club', 'playerB.club', 'table', 'competition']),
        ]);
    }

    public function endMatch(GameMatch $match): Response
    {
        return Inertia::render('Referee/EndMatch', [
            'match' => $match->load(['playerA.club', 'playerB.club', 'table', 'competition', 'signatures']),
        ]);
    }

    public function commitFrame(Request $request, GameMatch $match): RedirectResponse
    {
        $data = $request->validate([
            'winner' => ['required', 'in:A,B'],
        ]);

        if ($data['winner'] === 'A') {
            $match->increment('score_a');
        } else {
            $match->increment('score_b');
        }

        $match->update(['status' => 'live']);

        return back();
    }

    public function sign(Request $request, GameMatch $match): RedirectResponse
    {
        $data = $request->validate([
            'player_id' => ['required', 'exists:players,id'],
            'signature_data' => ['nullable', 'string'],
        ]);

        Signature::updateOrCreate(
            ['match_id' => $match->id, 'player_id' => $data['player_id']],
            ['signature_data' => $data['signature_data'] ?? '✓', 'signed_at' => now()]
        );

        return back()->with('success', 'Signature enregistrée.');
    }
}
