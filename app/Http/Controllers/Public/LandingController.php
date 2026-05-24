<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Competition;
use App\Models\GameMatch;
use App\Models\Player;
use App\Models\PoolTable;
use Inertia\Inertia;
use Inertia\Response;

class LandingController extends Controller
{
    public function __invoke(): Response
    {
        $competition = Competition::first();

        $liveMatches = GameMatch::with(['playerA', 'playerB', 'table'])
            ->where('competition_id', $competition?->id)
            ->where('status', 'live')
            ->get();

        $schedule = [
            ['time' => '14:00', 'round' => 'Huitièmes', 'status' => 'done'],
            ['time' => '17:00', 'round' => 'Quarts', 'status' => 'live'],
            ['time' => '20:30', 'round' => 'Quarts (suite)', 'status' => 'next'],
            ['time' => '22:00', 'round' => 'Demi-finales', 'status' => 'next'],
            ['time' => '23:30', 'round' => 'Finale', 'status' => 'next'],
        ];

        return Inertia::render('Public/Landing', [
            'competition' => $competition,
            'liveMatches' => $liveMatches,
            'schedule' => $schedule,
            'stats' => [
                'players' => Player::count(),
                'clubs' => 4,
                'tables' => PoolTable::where('competition_id', $competition?->id)->count(),
                'matches' => GameMatch::where('competition_id', $competition?->id)->count(),
                'prize_pool' => $competition?->prize_pool ?? 0,
            ],
        ]);
    }
}
