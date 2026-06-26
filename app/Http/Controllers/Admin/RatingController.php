<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlayerRating;
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
}
