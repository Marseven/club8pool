<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\PlayerRating;
use Inertia\Inertia;
use Inertia\Response;

class RankingController extends Controller
{
    public function index(): Response
    {
        $ratings = PlayerRating::with('player.club')
            ->orderBy('discipline')
            ->orderByDesc('rating')
            ->get();

        $byDiscipline = $ratings->groupBy('discipline')->map(function ($rows) {
            return $rows->values()->map(function ($r, $i) {
                $player = $r->player;
                return [
                    'rank'         => $i + 1,
                    'player_id'    => $player?->id,
                    'first_name'   => $player?->first_name,
                    'last_name'    => $player?->last_name,
                    'club'         => $player?->club?->name,
                    'nationality'  => $player?->nationality,
                    'rating'       => (int) $r->rating,
                    'games_played' => (int) $r->games_played,
                    'frames_won'   => (int) $r->frames_won,
                    'frames_lost'  => (int) $r->frames_lost,
                    'provisional'  => (bool) $r->provisional,
                    'last_match_at' => $r->last_match_at?->toDateString(),
                ];
            })->values();
        });

        $disciplines = $byDiscipline->keys()->sort()->values()->all();

        return Inertia::render('Public/Ranking', [
            'byDiscipline' => $byDiscipline,
            'disciplines'  => $disciplines,
        ]);
    }
}
