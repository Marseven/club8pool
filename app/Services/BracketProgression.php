<?php

namespace App\Services;

use App\Models\GameMatch;
use Illuminate\Support\Facades\DB;

class BracketProgression
{
    /**
     * Quand un match knockout est terminé, fait remonter le vainqueur
     * au tour suivant à la bonne position (round_position // 2,
     * côté A si position paire, côté B si impaire).
     *
     * Si le match n'est pas un knockout terminé, ne fait rien.
     *
     * @throws \LogicException si le match suivant est déjà en cours ou terminé.
     */
    public function advanceWinner(GameMatch $match): void
    {
        if ($match->phase !== 'knockout') return;
        if ($match->status !== 'done') return;
        if ($match->is_draw) return; // pas de gagnant en cas de nul

        $winnerId = $match->score_a > $match->score_b
            ? $match->player_a_id
            : $match->player_b_id;

        if (! $winnerId) return;

        $nextRound = $this->nextRound($match->round);
        if (! $nextRound) return; // c'était la finale

        $nextPosition = intdiv($match->round_position, 2);
        $side = $match->round_position % 2 === 0 ? 'player_a_id' : 'player_b_id';

        DB::transaction(function () use ($match, $winnerId, $nextRound, $nextPosition, $side) {
            $next = GameMatch::where('competition_id', $match->competition_id)
                ->where('phase', 'knockout')
                ->where('round', $nextRound)
                ->where('round_position', $nextPosition)
                ->lockForUpdate()
                ->first();

            if (! $next) return;

            // Guard: do not touch a match that is already live or done
            if (in_array($next->status, ['live', 'done'], true)) {
                throw new \LogicException(
                    "Cannot advance winner into match #{$next->id} ({$nextRound} pos {$nextPosition}): "
                    . "match is already in status '{$next->status}'."
                );
            }

            // Idempotency: winner is already in the correct slot — nothing to do
            if ($next->{$side} === $winnerId) {
                return;
            }

            $newPlayerA = $side === 'player_a_id' ? $winnerId : $next->player_a_id;
            $newPlayerB = $side === 'player_b_id' ? $winnerId : $next->player_b_id;

            $next->update([
                $side => $winnerId,
                'status' => ($newPlayerA && $newPlayerB) ? 'scheduled' : 'pending',
            ]);
        });
    }

    private function nextRound(string $round): ?string
    {
        return match ($round) {
            'R32' => 'R16',
            'R16' => 'QF',
            'QF'  => 'SF',
            'SF'  => 'F',
            default => null,
        };
    }
}
