<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Competition;
use App\Models\GameMatch;
use App\Services\PoolStanding;
use Inertia\Inertia;
use Inertia\Response;

class CompetitionController extends Controller
{
    public function show(?string $slug = null): Response
    {
        $competition = $slug
            ? Competition::where('slug', $slug)->firstOrFail()
            : Competition::firstOrFail();

        $pools = $competition->pools()->with(['players.club'])->get();

        $poolPayload = $pools->map(function ($pool) {
            $matches = GameMatch::with(['playerA', 'playerB', 'table'])
                ->where('pool_id', $pool->id)
                ->where('phase', 'pool')
                ->orderBy('round_position')
                ->get();

            return [
                'id' => $pool->id,
                'name' => $pool->name,
                'players' => $pool->players,
                'standings' => PoolStanding::compute($pool)->map(fn ($r) => [
                    'player_id' => $r['player_id'],
                    'pool_slot' => $r['pool_slot'],
                    'name' => trim($r['player']->first_name . ' ' . $r['player']->last_name),
                    'v' => $r['v'],
                    'w' => $r['w'],
                    'l' => $r['l'],
                    'diff' => $r['diff'],
                    'warnings' => $r['warnings'],
                    'rank' => $r['rank'],
                ])->values(),
                'matches' => $matches,
            ];
        });

        $knockoutMatches = GameMatch::with(['playerA.club', 'playerB.club', 'table'])
            ->where('competition_id', $competition->id)
            ->where('phase', 'knockout')
            ->orderBy('round')
            ->orderBy('round_position')
            ->get()
            ->groupBy('round');

        $liveMatches = GameMatch::with(['playerA.club', 'playerB.club', 'table'])
            ->where('competition_id', $competition->id)
            ->where('status', 'live')
            ->get();

        return Inertia::render('Public/Competition', [
            'competition' => $competition,
            'pools' => $poolPayload,
            'knockoutMatches' => $knockoutMatches,
            'liveMatches' => $liveMatches,
        ]);
    }
}
