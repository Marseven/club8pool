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

        // Programme par jour (horaires souples, pas de timing strict)
        $today = now()->format('Y-m-d');
        $playDays = $competition->settings['play_days'] ?? [];
        $restDays = $competition->settings['rest_days'] ?? [];
        $schedule = [];
        foreach ($playDays as $day) {
            $dt = \Carbon\Carbon::parse($day);
            $schedule[] = [
                'date' => $day,
                'label' => $dt->translatedFormat('D d/m'),
                'kind' => 'play',
                'status' => $day < $today ? 'done' : ($day === $today ? 'live' : 'next'),
            ];
        }
        foreach ($restDays as $day) {
            $dt = \Carbon\Carbon::parse($day);
            $schedule[] = [
                'date' => $day,
                'label' => $dt->translatedFormat('D d/m'),
                'kind' => 'rest',
                'status' => 'rest',
            ];
        }
        usort($schedule, fn ($a, $b) => strcmp($a['date'], $b['date']));

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
