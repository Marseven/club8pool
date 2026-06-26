<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Competition;
use App\Models\GameMatch;
use App\Models\Player;
use App\Models\PoolTable;
use App\Models\Registration;
use App\Services\PoolStanding;
use Inertia\Inertia;
use Inertia\Response;

class LandingController extends Controller
{
    public function __invoke(): Response
    {
        $competition = Competition::current(['pools']);

        $liveMatches = GameMatch::with(['playerA', 'playerB', 'table'])
            ->where('competition_id', $competition?->id)
            ->where('status', 'live')
            ->get();

        $pools = $competition?->pools->map(fn ($pool) => [
            'id' => $pool->id,
            'name' => $pool->name,
            'leader' => PoolStanding::compute($pool)->first(),
        ])->map(fn ($p) => [
            ...$p,
            'leader' => $p['leader'] ? [
                'name' => trim($p['leader']['player']->first_name . ' ' . $p['leader']['player']->last_name),
                'v' => $p['leader']['v'],
                'diff' => $p['leader']['diff'],
            ] : null,
        ]) ?? collect();

        $today = now()->format('Y-m-d');
        $playDays = $competition?->settings['play_days'] ?? [];
        $restDays = $competition?->settings['rest_days'] ?? [];
        $schedule = [];
        foreach ($playDays as $day) {
            $dt = \Carbon\Carbon::parse($day);
            $schedule[] = [
                'time' => $dt->translatedFormat('D d/m'),
                'round' => 'Journée de compétition',
                'status' => $day < $today ? 'done' : ($day === $today ? 'live' : 'next'),
            ];
        }
        foreach ($restDays as $day) {
            $dt = \Carbon\Carbon::parse($day);
            $schedule[] = [
                'time' => $dt->translatedFormat('D d/m'),
                'round' => 'Journée de repos',
                'status' => 'rest',
            ];
        }
        usort($schedule, fn ($a, $b) => strcmp($a['time'], $b['time']));

        return Inertia::render('Public/Landing', [
            'competition' => $competition,
            'pools' => $pools,
            'liveMatches' => $liveMatches,
            'schedule' => $schedule,
            'stats' => [
                'players' => Registration::where('competition_id', $competition?->id)->count(),
                'pools' => $competition?->pools->count() ?? 0,
                'tables' => PoolTable::where('competition_id', $competition?->id)->count(),
                'matches' => GameMatch::where('competition_id', $competition?->id)->count(),
                'prize_pool' => $competition?->prize_pool ?? 0,
            ],
        ]);
    }
}
