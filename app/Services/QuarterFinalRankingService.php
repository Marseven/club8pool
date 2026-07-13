<?php

namespace App\Services;

use App\Models\Competition;
use App\Models\GameMatch;
use App\Models\Player;

/**
 * Classement final des 8 quart-de-finalistes (1er → 8e).
 *
 * Méthode :
 *   1er  = vainqueur de la finale
 *   2e   = finaliste (perdant de la finale)
 *   3e   = vainqueur du match pour la 3e place (petite finale)
 *   4e   = perdant du match pour la 3e place
 *          (sans petite finale jouée : les 2 perdants de demi partagent 3e-4e)
 *   5e-8e = les 4 perdants de quart
 *
 * Départage à niveau égal : (manches gagnées − perdues) sur tout le tournoi,
 * puis manches gagnées, puis nom.
 */
class QuarterFinalRankingService
{
    /**
     * @return array{rows: array<int, array>, provisional: bool, has_qf: bool}
     */
    public function compute(Competition $competition): array
    {
        $ko = GameMatch::with(['playerA', 'playerB'])
            ->where('competition_id', $competition->id)
            ->where('phase', 'knockout')
            ->get();

        $qf = $ko->where('round', 'QF');
        if ($qf->isEmpty()) {
            return ['rows' => [], 'provisional' => false, 'has_qf' => false];
        }

        $qfPlayerIds = collect();
        foreach ($qf as $m) {
            if ($m->player_a_id) $qfPlayerIds->push($m->player_a_id);
            if ($m->player_b_id) $qfPlayerIds->push($m->player_b_id);
        }
        $qfPlayerIds = $qfPlayerIds->unique()->values();

        $tally = $this->frameTally($competition->id);

        $provisional = false;
        $tierOf = [];

        $final = $ko->firstWhere('round', 'F');
        $third = $ko->firstWhere('round', '3P');
        $sf    = $ko->where('round', 'SF');

        // 1er / 2e
        if ($final && $final->status === 'done' && $final->player_a_id && $final->player_b_id) {
            [$w, $l] = $this->winnerLoser($final);
            $tierOf[$w] = 1;
            $tierOf[$l] = 2;
        } else {
            $provisional = true;
        }

        // 3e / 4e
        if ($third && $third->status === 'done' && $third->player_a_id && $third->player_b_id) {
            [$w, $l] = $this->winnerLoser($third);
            $tierOf[$w] = 3;
            $tierOf[$l] = 4;
        } else {
            foreach ($sf as $m) {
                if ($m->status !== 'done' || ! $m->player_a_id || ! $m->player_b_id) { $provisional = true; continue; }
                [, $l] = $this->winnerLoser($m);
                if (! isset($tierOf[$l])) $tierOf[$l] = 3;
            }
        }

        // 5e-8e
        foreach ($qf as $m) {
            if ($m->status !== 'done' || ! $m->player_a_id || ! $m->player_b_id) { $provisional = true; continue; }
            [, $l] = $this->winnerLoser($m);
            if (! isset($tierOf[$l])) $tierOf[$l] = 5;
        }

        foreach ($qfPlayerIds as $pid) {
            if (! isset($tierOf[$pid])) { $tierOf[$pid] = 9; $provisional = true; }
        }

        $rows = $qfPlayerIds->map(function ($pid) use ($tierOf, $tally) {
            $t = $tally[$pid] ?? ['won' => 0, 'lost' => 0];
            return [
                'player_id' => $pid,
                'name'      => $this->playerName($pid),
                'tier'      => $tierOf[$pid],
                'in_play'   => $tierOf[$pid] === 9,
                'won'       => $t['won'],
                'lost'      => $t['lost'],
                'diff'      => $t['won'] - $t['lost'],
            ];
        })->sort(function ($a, $b) {
            return [$a['tier'], -$a['diff'], -$a['won'], $a['name']]
               <=> [$b['tier'], -$b['diff'], -$b['won'], $b['name']];
        })->values();

        $rows = $rows->map(fn ($r, $i) => ['rank' => $i + 1] + $r)->all();

        return ['rows' => $rows, 'provisional' => $provisional, 'has_qf' => true];
    }

    /** @return array{0:int,1:int} [winnerId, loserId] */
    private function winnerLoser(GameMatch $m): array
    {
        return $m->score_a > $m->score_b
            ? [$m->player_a_id, $m->player_b_id]
            : [$m->player_b_id, $m->player_a_id];
    }

    private function frameTally(int $competitionId): array
    {
        $tally = [];
        $matches = GameMatch::where('competition_id', $competitionId)
            ->where('status', 'done')
            ->get(['player_a_id', 'player_b_id', 'score_a', 'score_b']);

        foreach ($matches as $m) {
            if ($m->player_a_id) {
                $tally[$m->player_a_id]['won']  = ($tally[$m->player_a_id]['won']  ?? 0) + (int) $m->score_a;
                $tally[$m->player_a_id]['lost'] = ($tally[$m->player_a_id]['lost'] ?? 0) + (int) $m->score_b;
            }
            if ($m->player_b_id) {
                $tally[$m->player_b_id]['won']  = ($tally[$m->player_b_id]['won']  ?? 0) + (int) $m->score_b;
                $tally[$m->player_b_id]['lost'] = ($tally[$m->player_b_id]['lost'] ?? 0) + (int) $m->score_a;
            }
        }
        return $tally;
    }

    private function playerName(int $pid): string
    {
        static $cache = [];
        if (! isset($cache[$pid])) {
            $p = Player::find($pid);
            $cache[$pid] = $p ? trim($p->first_name . ' ' . $p->last_name) : "#{$pid}";
        }
        return $cache[$pid];
    }
}
