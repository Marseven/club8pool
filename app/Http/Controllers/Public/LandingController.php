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
        $competition = Competition::with('pools')->first();

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

        $schedule = [
            ['time' => '14:00', 'round' => 'Poule A', 'status' => 'done'],
            ['time' => '17:00', 'round' => 'Poule B & D', 'status' => 'live'],
            ['time' => '20:30', 'round' => 'Poule C (suite)', 'status' => 'next'],
            ['time' => '22:00', 'round' => 'Quarts (qualifiés)', 'status' => 'next'],
            ['time' => '23:30', 'round' => 'Demi & finale', 'status' => 'next'],
        ];

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
