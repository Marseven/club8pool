<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Competition;
use App\Models\GameMatch;
use App\Models\Player;
use App\Models\PoolTable;
use App\Models\Registration;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        $competition = Competition::firstOrFail();

        $tables = PoolTable::with(['liveMatch.playerA', 'liveMatch.playerB', 'liveMatch.referee'])
            ->where('competition_id', $competition->id)
            ->orderBy('id')
            ->get();

        $matchesTotal = GameMatch::where('competition_id', $competition->id)->count();
        $matchesDone = GameMatch::where('competition_id', $competition->id)->where('status', 'done')->count();
        $matchesLive = GameMatch::where('competition_id', $competition->id)->where('status', 'live')->count();

        $longestLive = GameMatch::with(['playerA', 'playerB'])
            ->where('competition_id', $competition->id)
            ->where('status', 'live')
            ->orderBy('started_at')
            ->first();

        $recentRegistrations = Registration::with(['player.club'])
            ->where('competition_id', $competition->id)
            ->orderByDesc('registered_at')
            ->limit(5)
            ->get();

        $schedule = [
            ['time' => '14:00', 'round' => 'Huitièmes', 'status' => 'done'],
            ['time' => '17:00', 'round' => 'Quarts', 'status' => 'live'],
            ['time' => '20:30', 'round' => 'Quarts (suite)', 'status' => 'next'],
            ['time' => '22:00', 'round' => 'Demi-finales', 'status' => 'next'],
            ['time' => '23:30', 'round' => 'Finale', 'status' => 'next'],
        ];

        return Inertia::render('Admin/Dashboard', [
            'competition' => $competition,
            'tables' => $tables,
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
