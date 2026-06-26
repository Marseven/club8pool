<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Competition;
use App\Models\GameMatch;
use App\Models\Player;
use App\Models\Registration;
use App\Services\PoolStanding;
use Inertia\Inertia;
use Inertia\Response;

class PlayerController extends Controller
{
    public function index(): Response
    {
        $competition = Competition::with('pools')->first();

        $registrations = Registration::with(['player.club', 'pool'])
            ->where('competition_id', $competition?->id)
            ->orderBy('pool_id')
            ->orderBy('pool_slot')
            ->get();

        $byPool = $competition?->pools->map(function ($pool) use ($competition) {
            $standings = PoolStanding::compute($pool)->keyBy('player_id');
            $regs = Registration::with('player.club')
                ->where('pool_id', $pool->id)
                ->orderBy('pool_slot')
                ->get();

            return [
                'id' => $pool->id,
                'name' => $pool->name,
                'players' => $regs->map(function ($r) use ($standings, $competition) {
                    $s = $standings->get($r->player_id);
                    return [
                        'id' => $r->player->id,
                        'name' => trim($r->player->first_name . ' ' . $r->player->last_name),
                        'first_name' => $r->player->first_name,
                        'last_name' => $r->player->last_name,
                        'club' => $r->player->club?->name,
                        'rating' => $r->player->rating,
                        'fgb_card' => $r->player->fgb_card,
                        'pool_slot' => $r->pool_slot,
                        'rank' => $s['rank'] ?? null,
                        'v' => $s['v'] ?? 0,
                        'w' => $s['w'] ?? 0,
                        'l' => $s['l'] ?? 0,
                        'diff' => $s['diff'] ?? 0,
                        'qualified' => $s && $s['rank'] <= ($competition?->qualifiers_per_pool ?? 2),
                    ];
                })->sortBy('rank')->values(),
            ];
        });

        // Joueurs sans poule (non assignés mais inscrits)
        $unassigned = Registration::with('player.club')
            ->where('competition_id', $competition?->id)
            ->whereNull('pool_id')
            ->get();

        return Inertia::render('Public/Players', [
            'competition' => $competition,
            'pools' => $byPool ?? collect(),
            'unassigned' => $unassigned->map(fn ($r) => [
                'id' => $r->player->id,
                'name' => trim($r->player->first_name . ' ' . $r->player->last_name),
                'club' => $r->player->club?->name,
                'rating' => $r->player->rating,
            ]),
            'totalPlayers' => $registrations->count(),
        ]);
    }

    public function show(Player $player): Response
    {
        $competition = Competition::first();

        $registration = Registration::with('pool')
            ->where('competition_id', $competition?->id)
            ->where('player_id', $player->id)
            ->first();

        $standing = null;
        $poolName = null;
        $qualified = null;
        $totalPlayersInPool = null;

        if ($registration?->pool) {
            $standings = PoolStanding::compute($registration->pool);
            $totalPlayersInPool = $standings->count();
            $row = $standings->firstWhere('player_id', $player->id);
            if ($row) {
                $standing = [
                    'rank' => $row['rank'],
                    'v' => $row['v'],
                    'w' => $row['w'],
                    'l' => $row['l'],
                    'diff' => $row['diff'],
                    'warnings' => $row['warnings'],
                ];
                $qualified = $row['rank'] <= ($competition?->qualifiers_per_pool ?? 2);
            }
            $poolName = $registration->pool->name;
        }

        // Parcours dans la compétition courante
        $journey = GameMatch::with(['playerA', 'playerB', 'table', 'pool'])
            ->where('competition_id', $competition?->id)
            ->where(function ($q) use ($player) {
                $q->where('player_a_id', $player->id)->orWhere('player_b_id', $player->id);
            })
            ->orderByRaw("CASE WHEN status = 'live' THEN 0 WHEN status = 'scheduled' THEN 1 WHEN status = 'done' THEN 2 ELSE 3 END")
            ->orderBy('round_position')
            ->get()
            ->map(function ($m) use ($player) {
                $isA = $m->player_a_id === $player->id;
                $opp = $isA ? $m->player_b : $m->player_a;
                $myScore = $isA ? $m->score_a : $m->score_b;
                $oppScore = $isA ? $m->score_b : $m->score_a;
                $win = $m->status === 'done' && ! $m->is_draw && $myScore > $oppScore;
                $loss = $m->status === 'done' && ! $m->is_draw && $myScore < $oppScore;
                return [
                    'id' => $m->id,
                    'phase' => $m->phase,
                    'pool_name' => $m->pool?->name,
                    'round' => $m->round,
                    'opponent' => $opp ? [
                        'id' => $opp->id,
                        'name' => trim($opp->first_name . ' ' . $opp->last_name),
                    ] : null,
                    'my_score' => $myScore,
                    'opp_score' => $oppScore,
                    'status' => $m->status,
                    'is_draw' => $m->is_draw,
                    'win' => $win,
                    'loss' => $loss,
                    'table' => $m->table?->name,
                    'scheduled_at' => $m->scheduled_at,
                ];
            });

        // Historique global (autres compétitions terminées)
        $history = GameMatch::with(['playerA', 'playerB', 'competition'])
            ->where('competition_id', '!=', $competition?->id)
            ->where(function ($q) use ($player) {
                $q->where('player_a_id', $player->id)->orWhere('player_b_id', $player->id);
            })
            ->where('status', 'done')
            ->orderByDesc('ended_at')
            ->limit(10)
            ->get();

        $ratings = \App\Models\PlayerRating::where('player_id', $player->id)
            ->orderByDesc('rating')
            ->get(['discipline', 'rating', 'games_played', 'provisional', 'last_match_at']);

        return Inertia::render('Public/Player', [
            'player' => $player->load('club'),
            'competition' => $competition,
            'registration' => $registration ? [
                'pool_name' => $poolName,
                'pool_slot' => $registration->pool_slot,
                'status' => $registration->status,
            ] : null,
            'standing' => $standing,
            'qualified' => $qualified,
            'totalPlayersInPool' => $totalPlayersInPool,
            'journey' => $journey,
            'history' => $history,
            'ratings' => $ratings->map(fn ($r) => [
                'discipline'    => $r->discipline,
                'rating'        => $r->rating,
                'games_played'  => $r->games_played,
                'provisional'   => $r->provisional,
                'last_match_at' => $r->last_match_at?->toIso8601String(),
            ]),
        ]);
    }
}
