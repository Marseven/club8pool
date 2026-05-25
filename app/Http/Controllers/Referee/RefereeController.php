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
        $userId = $request->user()->id;

        $matches = GameMatch::with(['playerA', 'playerB', 'table', 'competition', 'pool'])
            ->where('referee_id', $userId)
            ->orderBy('scheduled_at')
            ->get();

        // Pools already locked by another referee
        $blockedPools = GameMatch::whereNotNull('referee_id')
            ->where('referee_id', '!=', $userId)
            ->whereNotNull('pool_id')
            ->pluck('pool_id')
            ->unique();

        $available = GameMatch::with(['playerA', 'playerB', 'table', 'competition', 'pool'])
            ->whereIn('status', ['pending', 'scheduled'])
            ->whereNull('referee_id')
            ->when($blockedPools->isNotEmpty(), fn($q) => $q->whereNotIn('pool_id', $blockedPools))
            ->orderBy('scheduled_at')
            ->get();

        return Inertia::render('Referee/Queue', [
            'matches'   => $matches,
            'available' => $available,
        ]);
    }

    public function claim(Request $request, GameMatch $match): RedirectResponse
    {
        $userId = $request->user()->id;

        if ($match->referee_id === $userId) {
            return back();
        }
        if ($match->referee_id) {
            return back()->with('error', 'Ce match est déjà pris en charge par un autre arbitre.');
        }
        if ($match->pool_id) {
            $conflict = GameMatch::where('pool_id', $match->pool_id)
                ->whereNotNull('referee_id')
                ->where('referee_id', '!=', $userId)
                ->exists();
            if ($conflict) {
                return back()->with('error', 'Cette poule est déjà arbitrée par un autre arbitre.');
            }
        }
        $match->update(['referee_id' => $userId]);
        return back()->with('success', 'Match pris en charge.');
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
