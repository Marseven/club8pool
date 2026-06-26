<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Competition;
use App\Models\GameMatch;
use App\Models\PoolTable;
use Inertia\Inertia;
use Inertia\Response;

class TvController extends Controller
{
    public function show(?int $tableId = null): Response
    {
        $competition = Competition::current() ?? abort(404);

        $match = GameMatch::with(['playerA.club', 'playerB.club', 'table', 'referee'])
            ->where('competition_id', $competition->id);

        if ($tableId) {
            $match = $match->where('pool_table_id', $tableId);
        } else {
            $match = $match->where('status', 'live');
        }

        $match = $match->first() ?? GameMatch::with(['playerA.club', 'playerB.club', 'table'])
            ->where('competition_id', $competition->id)
            ->orderByDesc('id')
            ->first();

        $nextMatch = GameMatch::with(['playerA', 'playerB', 'table'])
            ->where('competition_id', $competition->id)
            ->where('status', 'scheduled')
            ->orderBy('scheduled_at')
            ->first();

        return Inertia::render('Public/Tv', [
            'competition' => $competition,
            'match' => $match,
            'nextMatch' => $nextMatch,
        ]);
    }
}
