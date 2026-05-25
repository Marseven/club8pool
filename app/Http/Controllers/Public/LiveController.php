<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Competition;
use App\Models\GameMatch;
use Inertia\Inertia;
use Inertia\Response;

class LiveController extends Controller
{
    public function __invoke(): Response
    {
        $competition = Competition::with('pools')->firstOrFail();

        $liveMatches = GameMatch::with(['playerA.club', 'playerB.club', 'table', 'referee'])
            ->where('competition_id', $competition->id)
            ->where('status', 'live')
            ->orderBy('pool_table_id')
            ->get();

        $nextMatches = GameMatch::with(['playerA', 'playerB', 'table'])
            ->where('competition_id', $competition->id)
            ->where('status', 'scheduled')
            ->whereNotNull('pool_table_id')
            ->orderBy('scheduled_at')
            ->limit(4)
            ->get();

        // Tous les matchs terminés, plus récents en premier
        $allDone = GameMatch::with(['playerA', 'playerB'])
            ->where('competition_id', $competition->id)
            ->where('status', 'done')
            ->whereNotNull('player_a_id')
            ->whereNotNull('player_b_id')
            ->orderByDesc('ended_at')
            ->get();

        $roundOrder = ['F' => 0, 'SF' => 1, 'QF' => 2, 'R16' => 3];

        $knockoutDone = $allDone
            ->where('phase', 'knockout')
            ->sortBy(fn ($m) => $roundOrder[$m->round] ?? 9)
            ->map(fn ($m) => [
                'id'       => $m->id,
                'round'    => $m->round,
                'score_a'  => $m->score_a,
                'score_b'  => $m->score_b,
                'ended_at' => $m->ended_at?->toIso8601String(),
                'player_a' => $m->playerA ? ['first_name' => $m->playerA->first_name, 'last_name' => $m->playerA->last_name] : null,
                'player_b' => $m->playerB ? ['first_name' => $m->playerB->first_name, 'last_name' => $m->playerB->last_name] : null,
            ])->values();

        $poolsDone = $competition->pools->map(fn ($pool) => [
            'id'      => $pool->id,
            'name'    => $pool->name,
            'matches' => $allDone->where('pool_id', $pool->id)->map(fn ($m) => [
                'id'       => $m->id,
                'score_a'  => $m->score_a,
                'score_b'  => $m->score_b,
                'ended_at' => $m->ended_at?->toIso8601String(),
                'player_a' => $m->playerA ? ['first_name' => $m->playerA->first_name, 'last_name' => $m->playerA->last_name] : null,
                'player_b' => $m->playerB ? ['first_name' => $m->playerB->first_name, 'last_name' => $m->playerB->last_name] : null,
            ])->values(),
        ]);

        return Inertia::render('Public/Live', [
            'competition'  => $competition,
            'liveMatches'  => $liveMatches,
            'nextMatches'  => $nextMatches,
            'knockoutDone' => $knockoutDone,
            'poolsDone'    => $poolsDone,
        ]);
    }
}
