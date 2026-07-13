<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateMatchRequest;
use App\Models\Competition;
use App\Models\GameMatch;
use App\Models\Pool;
use App\Models\PoolTable;
use App\Models\User;
use App\Services\PoolStanding;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PoolController extends Controller
{
    public function index(): \Illuminate\Http\RedirectResponse
    {
        $competition = Competition::current()
            ?? Competition::orderByDesc('starts_on')->firstOrFail();

        return redirect()->route('admin.competition.pools', $competition);
    }

    public function showCompetition(Competition $competition): Response
    {
        $competition->load('pools.players.club');

        $pools = $competition->pools->map(function ($pool) {
            $matches = GameMatch::with(['playerA', 'playerB', 'table', 'referee'])
                ->where('pool_id', $pool->id)
                ->where('phase', 'pool')
                ->orderBy('round_position')
                ->get();

            return [
                'id' => $pool->id,
                'name' => $pool->name,
                'size' => $pool->size,
                'players' => $pool->players->map(fn ($p) => [
                    'id' => $p->id,
                    'pool_slot' => $p->pivot->pool_slot,
                    'name' => trim($p->first_name . ' ' . $p->last_name),
                    'fgb_card' => $p->fgb_card,
                    'club' => $p->club?->name,
                ]),
                'standings' => PoolStanding::compute($pool)->map(fn ($r) => [
                    'player_id' => $r['player_id'],
                    'pool_slot' => $r['pool_slot'],
                    'name' => trim($r['player']->first_name . ' ' . $r['player']->last_name),
                    'v' => $r['v'],
                    'w' => $r['w'],
                    'l' => $r['l'],
                    'diff' => $r['diff'],
                    'warnings' => $r['warnings'],
                    'rank' => $r['rank'],
                ])->values(),
                'matches' => $matches,
                'progress' => $matches->count() > 0
                    ? round($matches->where('status', 'done')->count() / $matches->count() * 100)
                    : 0,
            ];
        });

        return Inertia::render('Admin/Pools/Index', [
            'competition' => $competition,
            'competitions' => Competition::whereIn('structure', ['pools_knockout', 'pools_only'])
                ->orderByDesc('starts_on')
                ->get(['id', 'name', 'status']),
            'pools' => $pools,
            'tables' => PoolTable::where('competition_id', $competition->id)
                ->orderBy('id')
                ->get(['id', 'name', 'location', 'status']),
            'referees' => User::where('role', 'referee')
                ->orderBy('name')
                ->get(['id', 'name', 'title']),
        ]);
    }

    public function updateMatch(UpdateMatchRequest $request, GameMatch $match): RedirectResponse
    {
        $data = $request->validated();

        $data['status'] = 'done';
        $data['ended_at'] = now();
        if ($match->started_at && ! $match->ended_at) {
            $data['duration_seconds'] = (int) $match->started_at->diffInSeconds(now());
        }

        $match->update($data);
        (new \App\Services\BracketProgression())->advanceWinner($match->fresh());

        return back()->with('success', 'Score enregistré.');
    }

    public function scoreFrame(Request $request, GameMatch $match): RedirectResponse
    {
        $data = $request->validate(['player' => ['required', 'in:A,B']]);

        if ($data['player'] === 'A') {
            $match->increment('score_a');
        } else {
            $match->increment('score_b');
        }

        if ($match->status !== 'live') {
            $match->update(['status' => 'live', 'started_at' => $match->started_at ?? now()]);
        }

        return back();
    }

    public function undoFrame(Request $request, GameMatch $match): RedirectResponse
    {
        $data = $request->validate(['player' => ['required', 'in:A,B']]);

        if ($data['player'] === 'A' && $match->score_a > 0) {
            $match->decrement('score_a');
        } elseif ($data['player'] === 'B' && $match->score_b > 0) {
            $match->decrement('score_b');
        }

        return back();
    }

    public function resetMatch(GameMatch $match): RedirectResponse
    {
        $match->update([
            'status'           => 'scheduled',
            'score_a'          => 0,
            'score_b'          => 0,
            'started_at'       => null,
            'ended_at'         => null,
            'duration_seconds' => null,
            'warning_a'        => false,
            'warning_b'        => false,
            'referee_note'     => null,
            'pool_table_id'    => null,
            'referee_id'       => null,
        ]);

        return back()->with('success', 'Match remis à zéro.');
    }

    public function startMatch(Request $request, GameMatch $match): RedirectResponse
    {
        if ($match->status === 'done') {
            return back()->with('error', 'Ce match est déjà terminé. Utilisez la correction de score pour modifier le résultat.');
        }

        $data = $request->validate([
            'pool_table_id' => ['nullable', 'exists:pool_tables,id'],
            'referee_id'    => ['nullable', 'exists:users,id'],
        ]);

        $tableId = $data['pool_table_id'] ?? null;

        if ($tableId) {
            PoolTable::where('id', $tableId)->update(['status' => 'live']);
        }

        $match->update([
            'pool_table_id' => $tableId,
            'referee_id'    => $data['referee_id'] ?? null,
            'status'        => 'live',
            'started_at'    => now(),
            'score_a'       => 0,
            'score_b'       => 0,
        ]);

        return back()->with('success', 'Match lancé.');
    }
}
