<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Competition;
use App\Models\GameMatch;
use App\Models\PoolTable;
use App\Models\Registration;
use App\Services\PoolStanding;
use Carbon\Carbon;
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
            'id'     => $pool->id,
            'name'   => $pool->name,
            'leader' => PoolStanding::compute($pool)->first(),
        ])->map(fn ($p) => [
            ...$p,
            'leader' => $p['leader'] ? [
                'name' => trim($p['leader']['player']->first_name . ' ' . $p['leader']['player']->last_name),
                'v'    => $p['leader']['v'],
                'diff' => $p['leader']['diff'],
            ] : null,
        ]) ?? collect();

        // ── Schedule ────────────────────────────────────────────────────────────
        // Merge play + rest days keyed by ISO date string so ksort() orders them
        // correctly without relying on locale-translated weekday names.
        $today     = now()->toDateString();
        $playDays  = $competition?->settings['play_days'] ?? [];
        $restDays  = $competition?->settings['rest_days'] ?? [];

        $allDays = [];
        foreach ($playDays as $day) {
            $allDays[$day] = 'play';
        }
        foreach ($restDays as $day) {
            $allDays[$day] = 'rest';
        }
        ksort($allDays);

        $schedule = [];
        foreach ($allDays as $day => $type) {
            $dt = Carbon::parse($day);
            $schedule[] = [
                'time'  => $dt->translatedFormat('D d/m'),
                'round' => $type === 'play' ? 'Journée de compétition' : 'Journée de repos',
                'status' => $type === 'rest'
                    ? 'rest'
                    : ($day < $today ? 'done' : ($day === $today ? 'live' : 'next')),
            ];
        }

        // ── Countdown ───────────────────────────────────────────────────────────
        [$countdownTo, $countdownLabel] = $this->computeCountdown($competition, $playDays, $today);

        return Inertia::render('Public/Landing', [
            'competition'    => $competition,
            'pools'          => $pools,
            'liveMatches'    => $liveMatches,
            'schedule'       => $schedule,
            'countdownTo'    => $countdownTo,
            'countdownLabel' => $countdownLabel,
            'stats'          => [
                'players'    => Registration::where('competition_id', $competition?->id)->where('status', '!=', 'cancelled')->count(),
                'pools'      => $competition?->pools->count() ?? 0,
                'tables'     => PoolTable::where('competition_id', $competition?->id)->count(),
                'matches'    => GameMatch::where('competition_id', $competition?->id)->count(),
                'matches_done' => GameMatch::where('competition_id', $competition?->id)->where('status', 'done')->count(),
                'prize_pool' => $competition?->prize_pool ?? 0,
            ],
        ]);
    }

    /** Returns [isoDatetime|null, label|null] for the next significant event. */
    private function computeCountdown(?Competition $competition, array $playDays, string $today): array
    {
        if (! $competition) {
            return [null, null];
        }

        if (in_array($competition->status, ['draft', 'registration'])) {
            if ($competition->starts_on) {
                return [
                    $competition->starts_on->toIso8601String(),
                    'DÉBUT DU TOURNOI',
                ];
            }
            return [null, 'DATE À CONFIRMER'];
        }

        if ($competition->status === 'in_progress') {
            // Next play day from settings
            $next = collect($playDays)->filter(fn ($d) => $d >= $today)->sort()->first();
            if ($next) {
                return [
                    Carbon::parse($next)->startOfDay()->toIso8601String(),
                    'PROCHAINE JOURNÉE',
                ];
            }
            // Fall back to ends_on
            if ($competition->ends_on) {
                return [
                    $competition->ends_on->endOfDay()->toIso8601String(),
                    'FIN DU TOURNOI',
                ];
            }
        }

        return [null, null];
    }
}
