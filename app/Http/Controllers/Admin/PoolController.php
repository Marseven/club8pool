<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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
    public function index(): Response
    {
        $competition = Competition::with('pools.players.club')->firstOrFail();

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
            'pools' => $pools,
            'tables' => PoolTable::where('competition_id', $competition->id)
                ->orderBy('id')
                ->get(['id', 'name', 'location', 'status']),
            'referees' => User::where('role', 'referee')
                ->orderBy('name')
                ->get(['id', 'name', 'title']),
        ]);
    }

    public function updateMatch(Request $request, GameMatch $match): RedirectResponse
    {
        $data = $request->validate([
            'score_a' => ['required', 'integer', 'min:0'],
            'score_b' => ['required', 'integer', 'min:0'],
            'is_draw' => ['boolean'],
            'warning_a' => ['boolean'],
            'warning_b' => ['boolean'],
            'note' => ['nullable', 'string'],
        ]);

        $data['status'] = 'done';
        $data['ended_at'] = now();

        $match->update($data);

        return back()->with('success', 'Score enregistré.');
    }

    public function startMatch(Request $request, GameMatch $match): RedirectResponse
    {
        $data = $request->validate([
            'pool_table_id' => ['required', 'exists:pool_tables,id'],
            'referee_id' => ['nullable', 'exists:users,id'],
        ]);

        // Libérer toute autre table actuellement live et l'arbitre s'il était assigné ailleurs
        GameMatch::where('competition_id', $match->competition_id)
            ->where('pool_table_id', $data['pool_table_id'])
            ->where('status', 'live')
            ->where('id', '!=', $match->id)
            ->update(['status' => 'scheduled']);

        $match->update([
            'pool_table_id' => $data['pool_table_id'],
            'referee_id' => $data['referee_id'] ?? null,
            'status' => 'live',
            'started_at' => now(),
            'score_a' => 0,
            'score_b' => 0,
        ]);

        PoolTable::where('id', $data['pool_table_id'])->update(['status' => 'live']);

        return back()->with('success', 'Match lancé.');
    }
}
