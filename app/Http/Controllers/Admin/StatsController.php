<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Competition;
use App\Models\PlayerCompetitionStatistic;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Artisan;
use Inertia\Inertia;
use Inertia\Response;

class StatsController extends Controller
{
    public function show(Competition $competition): Response
    {
        $statistics = PlayerCompetitionStatistic::with('player.club')
            ->where('competition_id', $competition->id)
            ->orderByDesc('frames_won')
            ->get();

        return Inertia::render('Admin/Stats', [
            'competition' => $competition,
            'statistics'  => $statistics,
        ]);
    }

    public function recalculate(Competition $competition): RedirectResponse
    {
        Artisan::call('c8p:recalculate-statistics', ['competition' => $competition->id]);

        return redirect()->route('admin.competitions.stats', $competition)
            ->with('success', 'Statistiques recalculées.');
    }
}
