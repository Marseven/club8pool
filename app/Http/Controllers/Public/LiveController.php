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
        $competition = Competition::firstOrFail();

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

        // Bracket phase finale — tous les matchs (pending inclus pour la structure)
        $koMatches = GameMatch::with(['playerA', 'playerB'])
            ->where('competition_id', $competition->id)
            ->where('phase', 'knockout')
            ->orderBy('round_position')
            ->get();

        $knockoutBracket = [];
        foreach (['R16', 'QF', 'SF', 'F'] as $r) {
            $rounds = $koMatches->where('round', $r)->values();
            if ($rounds->isNotEmpty()) {
                $knockoutBracket[$r] = $rounds->map(fn ($m) => [
                    'id'       => $m->id,
                    'status'   => $m->status,
                    'score_a'  => $m->score_a ?? 0,
                    'score_b'  => $m->score_b ?? 0,
                    'ended_at' => $m->ended_at?->toIso8601String(),
                    'player_a' => $m->playerA ? ['first_name' => $m->playerA->first_name, 'last_name' => $m->playerA->last_name] : null,
                    'player_b' => $m->playerB ? ['first_name' => $m->playerB->first_name, 'last_name' => $m->playerB->last_name] : null,
                ])->values();
            }
        }

        return Inertia::render('Public/Live', [
            'competition'     => $competition,
            'liveMatches'     => $liveMatches,
            'nextMatches'     => $nextMatches,
            'knockoutBracket' => $knockoutBracket,
        ]);
    }
}
