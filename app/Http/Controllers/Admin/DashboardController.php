<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Competition;
use App\Models\GameMatch;
use App\Models\PoolTable;
use App\Models\Registration;
use App\Services\PoolStanding;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        $competition = Competition::with('pools')->firstOrFail();

        $tables = PoolTable::with(['liveMatch.playerA', 'liveMatch.playerB', 'liveMatch.referee'])
            ->where('competition_id', $competition->id)
            ->orderBy('id')
            ->get();

        $matchesTotal = GameMatch::where('competition_id', $competition->id)->count();
        $matchesDone = GameMatch::where('competition_id', $competition->id)->where('status', 'done')->count();
        $matchesLive = GameMatch::where('competition_id', $competition->id)->where('status', 'live')->count();

        $longestLive = GameMatch::with(['playerA', 'playerB', 'table'])
            ->where('competition_id', $competition->id)
            ->where('status', 'live')
            ->orderBy('started_at')
            ->first();

        $recentRegistrations = Registration::with(['player.club', 'pool'])
            ->where('competition_id', $competition->id)
            ->orderByDesc('registered_at')
            ->limit(5)
            ->get();

        $poolSummary = $competition->pools->map(function ($pool) {
            $standings = PoolStanding::compute($pool);
            $done = $pool->matches()->where('status', 'done')->count();
            $total = $pool->matches()->count();
            return [
                'id' => $pool->id,
                'name' => $pool->name,
                'leader' => $standings->first(),
                'progress' => $total > 0 ? round(($done / $total) * 100) : 0,
                'matches_done' => $done,
                'matches_total' => $total,
            ];
        });

        $schedule = [
            ['time' => '14:00', 'round' => 'Poule A', 'status' => 'done'],
            ['time' => '17:00', 'round' => 'Poule B & D', 'status' => 'live'],
            ['time' => '20:30', 'round' => 'Poule C (suite)', 'status' => 'next'],
            ['time' => '22:00', 'round' => 'Quarts (qualifiés)', 'status' => 'next'],
            ['time' => '23:30', 'round' => 'Demi & finale', 'status' => 'next'],
        ];

        return Inertia::render('Admin/Dashboard', [
            'competition' => $competition,
            'tables' => $tables,
            'pools' => $poolSummary,
            'kpis' => [
                'players' => Registration::where('competition_id', $competition->id)->count(),
                'slots' => $competition->player_slots,
                'matches_total' => $matchesTotal,
                'matches_done' => $matchesDone,
                'matches_live' => $matchesLive,
                'tables_active' => $tables->where('status', 'live')->count(),
                'tables_total' => $tables->count(),
                'longest_live' => $longestLive,
            ],
            'recentRegistrations' => $recentRegistrations,
            'schedule' => $schedule,
        ]);
    }
}
