<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GameMatch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MatchController extends Controller
{
    public function update(Request $request, GameMatch $match): RedirectResponse
    {
        $data = $request->validate([
            'score_a' => ['nullable', 'integer', 'min:0'],
            'score_b' => ['nullable', 'integer', 'min:0'],
            'status' => ['nullable', 'in:scheduled,live,done,disputed,pending'],
            'pool_table_id' => ['nullable', 'integer'],
            'referee_id' => ['nullable', 'integer'],
        ]);

        if (($data['status'] ?? null) === 'live' && ! $match->started_at) {
            $data['started_at'] = now();
        }
        if (($data['status'] ?? null) === 'done' && ! $match->ended_at) {
            $data['ended_at'] = now();
            if ($match->started_at) {
                $data['duration_seconds'] = now()->diffInSeconds($match->started_at);
            }
        }

        $match->update($data);
        (new \App\Services\BracketProgression())->advanceWinner($match->fresh());

        return back()->with('success', 'Match mis à jour.');
    }
}
