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
            // Merge rows by first_name: same pseudo = same physical player across competitions
            $merged = [];
            foreach ($rows as $r) {
                $player = $r->player;
                $key = mb_strtolower(trim($player?->first_name ?? ''));
                if ($key === '') continue;

                if (!isset($merged[$key])) {
                    $merged[$key] = [
                        'first_name'    => $player->first_name,
                        'last_name'     => $player->last_name,
                        'club'          => $player->club?->name,
                        'nationality'   => $player->nationality,
                        'rating'        => (int) $r->rating,
                        'games_played'  => (int) $r->games_played,
                        'frames_won'    => (int) $r->frames_won,
                        'frames_lost'   => (int) $r->frames_lost,
                        'last_match_at' => $r->last_match_at?->toDateString(),
                    ];
                } else {
                    // Prefer higher ELO as the display rating
                    if ((int) $r->rating > $merged[$key]['rating']) {
                        $merged[$key]['rating'] = (int) $r->rating;
                    }
                    // Prefer a named club over null
                    if (!$merged[$key]['club'] && $player->club?->name) {
                        $merged[$key]['club'] = $player->club->name;
                    }
                    // Sum match/frame stats across competitions
                    $merged[$key]['games_played'] += (int) $r->games_played;
                    $merged[$key]['frames_won']   += (int) $r->frames_won;
                    $merged[$key]['frames_lost']  += (int) $r->frames_lost;
                    // Most recent match date
                    $date = $r->last_match_at?->toDateString();
                    if ($date && (!$merged[$key]['last_match_at'] || $date > $merged[$key]['last_match_at'])) {
                        $merged[$key]['last_match_at'] = $date;
                    }
                }
            }

            // Sort by rating descending
            usort($merged, fn ($a, $b) => $b['rating'] <=> $a['rating']);

            return collect($merged)->values()->map(fn ($r, $i) => [
                'rank'          => $i + 1,
                'first_name'    => $r['first_name'],
                'last_name'     => $r['last_name'],
                'club'          => $r['club'],
                'nationality'   => $r['nationality'],
                'rating'        => $r['rating'],
                'games_played'  => $r['games_played'],
                'frames_won'    => $r['frames_won'],
                'frames_lost'   => $r['frames_lost'],
                'last_match_at' => $r['last_match_at'],
            ])->values();
        });

        $disciplines = $byDiscipline->keys()->sort()->values()->all();

        return Inertia::render('Public/Ranking', [
            'byDiscipline' => $byDiscipline,
            'disciplines'  => $disciplines,
        ]);
    }
}
