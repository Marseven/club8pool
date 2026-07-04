<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Competition;
use App\Models\PlayerRating;
use App\Models\RatingEvent;
use App\Services\PlayerRatingService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class RatingController extends Controller
{
    public function index(): Response
    {
        $ratings = PlayerRating::with('player.club')
            ->orderBy('discipline')
            ->orderByDesc('rating')
            ->get();

        // Group by discipline and inject rank within each discipline
        $byDiscipline = $ratings->groupBy('discipline')->map(function ($rows) {
            return $rows->values()->map(function ($r, $i) {
                return array_merge($r->toArray(), ['rank' => $i + 1]);
            })->values();
        });

        $disciplines = $byDiscipline->keys()->sort()->values()->all();

        return Inertia::render('Admin/Rating', [
            'byDiscipline' => $byDiscipline,
            'disciplines'  => $disciplines,
        ]);
    }

    public function reset(): RedirectResponse
    {
        RatingEvent::truncate();
        PlayerRating::truncate();

        return back()->with('success', 'Classement ELO réinitialisé.');
    }

    public function recalculate(PlayerRatingService $service): RedirectResponse
    {
        RatingEvent::truncate();
        PlayerRating::truncate();

        $competitions = Competition::whereIn('status', ['in_progress', 'finished'])->get();
        $total = 0;

        foreach ($competitions as $comp) {
            $total += $service->recalculateForCompetition($comp->id);
        }

        return back()->with('success', "Recalcul terminé — {$total} matchs traités.");
    }
}
