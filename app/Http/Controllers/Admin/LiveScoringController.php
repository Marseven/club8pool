<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Competition;
use App\Models\GameMatch;
use App\Models\PoolTable;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class LiveScoringController extends Controller
{
    private const ROUND_LABELS = [
        'R32' => '16es', 'R16' => '8es de finale', 'QF' => 'Quart de finale',
        'SF' => 'Demi-finale', '3P' => 'Match 3e place', 'F' => 'Finale',
    ];

    public function index(): Response
    {
        $competition = Competition::current()
            ?? Competition::orderByDesc('starts_on')->firstOrFail();

        $matches = GameMatch::with(['playerA.club', 'playerB.club', 'pool', 'table', 'referee'])
            ->where('competition_id', $competition->id)
            ->whereIn('status', ['live', 'scheduled'])
            ->get();

        $live = $matches->where('status', 'live')
            ->sortBy(fn ($m) => $m->started_at?->timestamp ?? PHP_INT_MAX)
            ->map(fn ($m) => $this->serialize($m, $competition))
            ->values()
            ->all();

        // Only scheduled matches with both players assigned can be started.
        $upcoming = $matches->where('status', 'scheduled')
            ->filter(fn ($m) => $m->player_a_id && $m->player_b_id)
            ->sortBy(fn ($m) => $m->scheduled_at?->timestamp ?? PHP_INT_MAX)
            ->map(fn ($m) => $this->serialize($m, $competition))
            ->values()
            ->all();

        return Inertia::render('Admin/LiveScoring', [
            'competition' => [
                'id'   => $competition->id,
                'name' => $competition->name,
            ],
            'liveMatches'     => $live,
            'upcomingMatches' => $upcoming,
            'tables'          => PoolTable::orderBy('id')->get(['id', 'name', 'status']),
            'referees'        => User::where('role', 'referee')->orderBy('name')->get(['id', 'name', 'title']),
        ]);
    }

    private function serialize(GameMatch $m, Competition $competition): array
    {
        $raceTo = $m->phase === 'knockout'
            ? $competition->raceForRound((string) $m->round)
            : $competition->raceForPhase('pool');

        $context = $m->phase === 'pool'
            ? ('Poule ' . ($m->pool?->name ?? '—'))
            : (self::ROUND_LABELS[$m->round] ?? (string) $m->round);

        return [
            'id'         => $m->id,
            'phase'      => $m->phase,
            'round'      => $m->round,
            'context'    => $context,
            'status'     => $m->status,
            'score_a'    => (int) ($m->score_a ?? 0),
            'score_b'    => (int) ($m->score_b ?? 0),
            'race_to'    => $raceTo,
            'started_at' => $m->started_at?->toIso8601String(),
            'table'      => $m->table ? ['id' => $m->table->id, 'name' => $m->table->name] : null,
            'referee'    => $m->referee ? ['id' => $m->referee->id, 'name' => $m->referee->name] : null,
            'player_a'   => $this->player($m->playerA),
            'player_b'   => $this->player($m->playerB),
        ];
    }

    private function player($p): ?array
    {
        if (! $p) return null;
        return [
            'id'         => $p->id,
            'first_name' => $p->first_name,
            'last_name'  => $p->last_name,
            'club'       => $p->club?->name,
        ];
    }
}
