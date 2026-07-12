<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Competition;
use App\Models\GameMatch;
use App\Models\PlayerCompetitionStatistic;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Artisan;
use Inertia\Inertia;
use Inertia\Response;

class StatsController extends Controller
{
    public function showCurrent(): Response
    {
        $competition = Competition::current()
            ?? Competition::orderByDesc('starts_on')->firstOrFail();

        return $this->render($competition, 'stats');
    }

    public function show(Competition $competition): Response
    {
        return $this->render($competition, 'comps');
    }

    private function render(Competition $competition, string $active): Response
    {
        $statistics = PlayerCompetitionStatistic::with('player.club')
            ->where('competition_id', $competition->id)
            ->orderByDesc('frames_won')
            ->get();

        return Inertia::render('Admin/Stats', [
            'competition' => $competition,
            'statistics'  => $statistics,
            'overview'    => $this->buildOverview($competition, $statistics),
            'leaders'     => $this->buildLeaders($statistics),
            'active'      => $active,
        ]);
    }

    /**
     * Agrégats globaux de la compétition (synthèse en tête de page).
     */
    private function buildOverview(Competition $competition, $statistics): array
    {
        $matches = GameMatch::where('competition_id', $competition->id)->get();
        $done    = $matches->where('status', 'done');

        $totalFrames = $done->sum(fn ($m) => (int) $m->score_a + (int) $m->score_b);
        $durations   = $done->whereNotNull('duration_seconds')->pluck('duration_seconds');

        // Match le plus long (durée) et le plus serré / le plus large (écart de manches)
        $longest = $done->whereNotNull('duration_seconds')->sortByDesc('duration_seconds')->first();
        $widest  = $done->sortByDesc(fn ($m) => abs((int) $m->score_a - (int) $m->score_b))->first();

        $matchLabel = function (?GameMatch $m) {
            if (! $m) return null;
            $m->loadMissing(['playerA', 'playerB']);
            $a = $m->playerA?->first_name ?? '?';
            $b = $m->playerB?->first_name ?? '?';
            return [
                'players'  => $a . ' vs ' . $b,
                'score'    => (int) $m->score_a . '–' . (int) $m->score_b,
                'duration' => $m->duration_seconds ? round($m->duration_seconds / 60) . ' min' : null,
                'gap'      => abs((int) $m->score_a - (int) $m->score_b),
            ];
        };

        return [
            'players'       => (int) PlayerCompetitionStatistic::where('competition_id', $competition->id)->count(),
            'matches_total' => $matches->count(),
            'matches_done'  => $done->count(),
            'matches_live'  => $matches->where('status', 'live')->count(),
            'frames_total'  => $totalFrames,
            'frames_avg'    => $done->count() ? round($totalFrames / $done->count(), 1) : 0,
            'avg_duration'  => $durations->count() ? round($durations->avg() / 60) : null,
            'total_hours'   => $durations->sum() ? round($durations->sum() / 3600, 1) : null,
            'break_and_runs' => (int) $statistics->sum('break_and_runs'),
            'fouls'          => (int) $statistics->sum('fouls'),
            'longest_match'  => $matchLabel($longest),
            'widest_match'   => $matchLabel($widest),
        ];
    }

    /**
     * Petits classements (top 3) pour la synthèse.
     */
    private function buildLeaders($statistics): array
    {
        $top = fn (string $field) => $statistics
            ->sortByDesc($field)
            ->filter(fn ($s) => (int) $s->{$field} > 0)
            ->take(3)
            ->map(fn ($s) => [
                'name'  => $s->player ? trim($s->player->first_name . ' ' . $s->player->last_name) : '—',
                'club'  => $s->player?->club?->name,
                'value' => (int) $s->{$field},
            ])->values()->all();

        return [
            'frames_won'     => $top('frames_won'),
            'matches_won'    => $top('matches_won'),
            'break_and_runs' => $top('break_and_runs'),
        ];
    }

    public function recalculate(Competition $competition): RedirectResponse
    {
        Artisan::call('c8p:recalculate-statistics', ['competition' => $competition->id]);

        return redirect()->route('admin.competitions.stats', $competition)
            ->with('success', 'Statistiques recalculées.');
    }

    public function recalculateCurrent(): RedirectResponse
    {
        $competition = Competition::current()
            ?? Competition::orderByDesc('starts_on')->firstOrFail();

        Artisan::call('c8p:recalculate-statistics', ['competition' => $competition->id]);

        return redirect()->route('admin.stats.current')
            ->with('success', 'Statistiques recalculées.');
    }
}
