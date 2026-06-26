<?php

namespace App\Services;

use App\Models\GameMatch;
use Illuminate\Support\Facades\DB;

class BracketProgression
{
    /**
     * Quand un match knockout est terminé, fait remonter le vainqueur
     * au tour suivant (round_position // 2, côté A si position paire, B si impaire).
     *
     * Pour les demi-finales (SF), route également le PERDANT vers le match de
     * petite finale (3P, position 0) si ce match existe dans la DB.
     *
     * @throws \LogicException si le match cible est déjà en cours ou terminé.
     */
    public function advanceWinner(GameMatch $match): void
    {
        if ($match->phase !== 'knockout') return;
        if ($match->status !== 'done') return;
        if ($match->is_draw) return;

        $winnerId = $match->score_a > $match->score_b
            ? $match->player_a_id
            : $match->player_b_id;

        if (! $winnerId) return;

        $nextRound    = $this->nextRound($match->round);
        $routeSfLoser = ($match->round === 'SF');

        // Nothing to do if there is no next round and no 3P loser to route.
        if (is_null($nextRound) && ! $routeSfLoser) return;

        $loserId      = $match->score_a > $match->score_b
            ? $match->player_b_id
            : $match->player_a_id;

        $nextPosition = intdiv($match->round_position, 2);
        $side         = $match->round_position % 2 === 0 ? 'player_a_id' : 'player_b_id';

        DB::transaction(function () use ($match, $winnerId, $loserId, $nextRound, $nextPosition, $side, $routeSfLoser) {
            // ── Advance winner to next round ────────────────────────────────
            if ($nextRound !== null) {
                $next = GameMatch::where('competition_id', $match->competition_id)
                    ->where('phase', 'knockout')
                    ->where('round', $nextRound)
                    ->where('round_position', $nextPosition)
                    ->lockForUpdate()
                    ->first();

                if ($next) {
                    if (in_array($next->status, ['live', 'done'], true)) {
                        throw new \LogicException(
                            "Cannot advance winner into match #{$next->id} ({$nextRound} pos {$nextPosition}): "
                            . "match is already in status '{$next->status}'."
                        );
                    }

                    if ($next->{$side} !== $winnerId) {
                        $newPlayerA = $side === 'player_a_id' ? $winnerId : $next->player_a_id;
                        $newPlayerB = $side === 'player_b_id' ? $winnerId : $next->player_b_id;
                        $next->update([
                            $side    => $winnerId,
                            'status' => ($newPlayerA && $newPlayerB) ? 'scheduled' : 'pending',
                        ]);
                    }
                }
            }

            // ── Route SF loser to 3P (petite finale) if it exists ──────────
            if ($routeSfLoser && $loserId) {
                $thirdSide = $match->round_position === 0 ? 'player_a_id' : 'player_b_id';

                $third = GameMatch::where('competition_id', $match->competition_id)
                    ->where('phase', 'knockout')
                    ->where('round', '3P')
                    ->where('round_position', 0)
                    ->lockForUpdate()
                    ->first();

                if ($third && ! in_array($third->status, ['live', 'done'], true)) {
                    if ($third->{$thirdSide} !== $loserId) {
                        $newA = $thirdSide === 'player_a_id' ? $loserId : $third->player_a_id;
                        $newB = $thirdSide === 'player_b_id' ? $loserId : $third->player_b_id;
                        $third->update([
                            $thirdSide => $loserId,
                            'status'   => ($newA && $newB) ? 'scheduled' : 'pending',
                        ]);
                    }
                }
            }
        });
    }

    private function nextRound(string $round): ?string
    {
        return match ($round) {
            'R32' => 'R16',
            'R16' => 'QF',
            'QF'  => 'SF',
            'SF'  => 'F',
            default => null, // F, 3P, GF, EXH — no winner advancement
        };
    }
}
