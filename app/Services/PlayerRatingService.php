<?php

namespace App\Services;

use App\Models\GameMatch;
use App\Models\Player;
use App\Models\PlayerRating;
use App\Models\RatingEvent;
use Illuminate\Support\Facades\DB;

class PlayerRatingService
{
    public function getOrCreateRating(Player $player, string $discipline): PlayerRating
    {
        return PlayerRating::firstOrCreate(
            ['player_id' => $player->id, 'discipline' => $discipline],
            [
                'rating'       => 1500,
                'games_played' => 0,
                'frames_won'   => 0,
                'frames_lost'  => 0,
                'robustness'   => 0,
                'provisional'  => true,
            ]
        );
    }

    public function applyMatchResult(GameMatch $match): void
    {
        // Only for completed matches with two real players
        if (!$match->player_a_id || !$match->player_b_id) return;
        if ($match->status !== 'done') return;

        $match->loadMissing('competition');
        $discipline = $match->competition?->discipline ?? '8-ball';

        // Idempotency: one event per match
        if (RatingEvent::where('match_id', $match->id)->exists()) return;

        DB::transaction(function () use ($match, $discipline) {
            $playerA = Player::lockForUpdate()->find($match->player_a_id);
            $playerB = Player::lockForUpdate()->find($match->player_b_id);

            if (!$playerA || !$playerB) return;

            $ratingA = PlayerRating::lockForUpdate()->firstOrCreate(
                ['player_id' => $playerA->id, 'discipline' => $discipline],
                ['rating' => 1500, 'games_played' => 0, 'frames_won' => 0, 'frames_lost' => 0, 'robustness' => 0, 'provisional' => true]
            );
            $ratingB = PlayerRating::lockForUpdate()->firstOrCreate(
                ['player_id' => $playerB->id, 'discipline' => $discipline],
                ['rating' => 1500, 'games_played' => 0, 'frames_won' => 0, 'frames_lost' => 0, 'robustness' => 0, 'provisional' => true]
            );

            $ra = $ratingA->rating;
            $rb = $ratingB->rating;

            $expectedA = 1 / (1 + pow(10, ($rb - $ra) / 400));
            $expectedB = 1 - $expectedA;

            // Determine actual score
            $isDraw = $match->is_draw ?? false;
            if ($isDraw) {
                $actualA = 0.5;
                $actualB = 0.5;
            } elseif ($match->score_a > $match->score_b) {
                $actualA = 1.0;
                $actualB = 0.0;
            } else {
                $actualA = 0.0;
                $actualB = 1.0;
            }

            // Margin factor based on frame differential
            $totalFrames  = max($match->score_a + $match->score_b, 1);
            $marginRatio  = abs($match->score_a - $match->score_b) / $totalFrames;
            $marginFactor = min(1 + $marginRatio, 1.75);

            // K factor
            $kA = $this->kFactor($ratingA->games_played, $ratingA->provisional);
            $kB = $this->kFactor($ratingB->games_played, $ratingB->provisional);

            $deltaA = (int) round($kA * $marginFactor * ($actualA - $expectedA));
            $deltaB = (int) round($kB * $marginFactor * ($actualB - $expectedB));

            $newRatingA = max(100, $ra + $deltaA);
            $newRatingB = max(100, $rb + $deltaB);

            $ratingA->update([
                'rating'        => $newRatingA,
                'games_played'  => $ratingA->games_played + 1,
                'frames_won'    => $ratingA->frames_won + $match->score_a,
                'frames_lost'   => $ratingA->frames_lost + $match->score_b,
                'robustness'    => $ratingA->robustness + 1,
                'provisional'   => $ratingA->games_played + 1 < 20,
                'last_match_at' => now(),
            ]);

            $ratingB->update([
                'rating'        => $newRatingB,
                'games_played'  => $ratingB->games_played + 1,
                'frames_won'    => $ratingB->frames_won + $match->score_b,
                'frames_lost'   => $ratingB->frames_lost + $match->score_a,
                'robustness'    => $ratingB->robustness + 1,
                'provisional'   => $ratingB->games_played + 1 < 20,
                'last_match_at' => now(),
            ]);

            RatingEvent::create([
                'match_id'        => $match->id,
                'competition_id'  => $match->competition_id,
                'discipline'      => $discipline,
                'player_a_id'     => $match->player_a_id,
                'player_b_id'     => $match->player_b_id,
                'rating_a_before' => $ra,
                'rating_b_before' => $rb,
                'rating_a_after'  => $newRatingA,
                'rating_b_after'  => $newRatingB,
                'expected_a'      => round($expectedA, 4),
                'expected_b'      => round($expectedB, 4),
                'score_a'         => $match->score_a,
                'score_b'         => $match->score_b,
                'margin_factor'   => round($marginFactor, 3),
                'k_factor_a'      => $kA,
                'k_factor_b'      => $kB,
            ]);
        });
    }

    private function kFactor(int $gamesPlayed, bool $provisional): int
    {
        if ($provisional || $gamesPlayed < 20) return 40;
        if ($gamesPlayed < 100) return 24;
        return 16;
    }

    public function recalculateForCompetition(int $competitionId): int
    {
        // TODO: implement full recalculation — requires reversing all existing events and reapplying in match order
        $count   = 0;
        $matches = GameMatch::where('competition_id', $competitionId)
            ->where('status', 'done')
            ->whereNotNull('player_a_id')
            ->whereNotNull('player_b_id')
            ->orderBy('ended_at')
            ->get();

        foreach ($matches as $match) {
            // Delete existing event to allow re-rating
            RatingEvent::where('match_id', $match->id)->delete();
            $this->applyMatchResult($match->fresh());
            $count++;
        }

        return $count;
    }
}
