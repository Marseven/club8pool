<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Competition;
use App\Models\GameMatch;
use App\Services\PoolStanding;
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

        $pools = $competition->pools->map(fn ($pool) => [
            'id' => $pool->id,
            'name' => $pool->name,
            'standings' => PoolStanding::compute($pool)->map(fn ($r) => [
                'player_id' => $r['player_id'],
                'pool_slot' => $r['pool_slot'],
                'name' => trim($r['player']->first_name . ' ' . $r['player']->last_name),
                'v' => $r['v'],
                'w' => $r['w'],
                'l' => $r['l'],
                'diff' => $r['diff'],
                'rank' => $r['rank'],
            ])->values(),
        ]);

        return Inertia::render('Public/Live', [
            'competition' => $competition,
            'liveMatches' => $liveMatches,
            'nextMatches' => $nextMatches,
            'pools' => $pools,
        ]);
    }
}
