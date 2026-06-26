<?php

namespace Tests\Unit\Services;

use App\Models\Competition;
use App\Models\GameMatch;
use App\Models\Player;
use App\Models\PlayerRating;
use App\Models\RatingEvent;
use App\Services\PlayerRatingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PlayerRatingServiceTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function service(): PlayerRatingService
    {
        return new PlayerRatingService();
    }

    private function makePlayer(string $first, string $last = 'Tester'): Player
    {
        return Player::create(['first_name' => $first, 'last_name' => $last]);
    }

    private function makeCompetition(string $discipline = '8-ball'): Competition
    {
        return Competition::create([
            'name'         => 'Rating Test Cup',
            'slug'         => 'rating-test-' . Str::random(6),
            'discipline'   => $discipline,
            'format'       => 'single_elim',
            'structure'    => 'knockout',
            'race_to'      => 7,
            'status'       => 'in_progress',
            'shot_clock'   => 30,
            'player_slots' => 16,
        ]);
    }

    private function makeMatch(Competition $comp, Player $playerA, Player $playerB, array $overrides = []): GameMatch
    {
        return GameMatch::create(array_merge([
            'competition_id' => $comp->id,
            'round'          => 'QF',
            'round_position' => 0,
            'phase'          => 'knockout',
            'status'         => 'done',
            'player_a_id'    => $playerA->id,
            'player_b_id'    => $playerB->id,
            'score_a'        => 7,
            'score_b'        => 0,
        ], $overrides));
    }

    // -------------------------------------------------------------------------
    // 1. New player starts at 1500, provisional=true, games_played=0
    // -------------------------------------------------------------------------

    public function test_new_player_starts_at_1500(): void
    {
        $player = $this->makePlayer('New', 'Player');

        $rating = $this->service()->getOrCreateRating($player, '8-ball');

        $this->assertEquals(1500, $rating->rating);
        $this->assertTrue($rating->provisional);
        $this->assertEquals(0, $rating->games_played);
    }

    // -------------------------------------------------------------------------
    // 2. Winner's rating increases after a match
    // -------------------------------------------------------------------------

    public function test_winner_rating_increases(): void
    {
        $comp    = $this->makeCompetition();
        $playerA = $this->makePlayer('Winner');
        $playerB = $this->makePlayer('Loser');

        $match = $this->makeMatch($comp, $playerA, $playerB, [
            'score_a' => 7,
            'score_b' => 0,
        ]);

        $this->service()->applyMatchResult($match);

        $ratingA = PlayerRating::where('player_id', $playerA->id)->where('discipline', '8-ball')->first();
        $this->assertGreaterThan(1500, $ratingA->rating);
    }

    // -------------------------------------------------------------------------
    // 3. Loser's rating decreases after a match
    // -------------------------------------------------------------------------

    public function test_loser_rating_decreases(): void
    {
        $comp    = $this->makeCompetition();
        $playerA = $this->makePlayer('Victor');
        $playerB = $this->makePlayer('Vanquished');

        $match = $this->makeMatch($comp, $playerA, $playerB, [
            'score_a' => 7,
            'score_b' => 0,
        ]);

        $this->service()->applyMatchResult($match);

        $ratingB = PlayerRating::where('player_id', $playerB->id)->where('discipline', '8-ball')->first();
        $this->assertLessThan(1500, $ratingB->rating);
    }

    // -------------------------------------------------------------------------
    // 4. Beating a stronger opponent gives more points than beating an equal one
    // -------------------------------------------------------------------------

    public function test_beating_stronger_opponent_gives_more_points(): void
    {
        // playerA (1400) beats playerB (1600): upsets give more Elo
        $comp    = $this->makeCompetition();
        $playerA = $this->makePlayer('Underdog');
        $playerB = $this->makePlayer('Favourite');

        // Pre-seed ratings
        PlayerRating::create([
            'player_id'    => $playerA->id,
            'discipline'   => '8-ball',
            'rating'       => 1400,
            'games_played' => 0,
            'frames_won'   => 0,
            'frames_lost'  => 0,
            'robustness'   => 0,
            'provisional'  => true,
        ]);
        PlayerRating::create([
            'player_id'    => $playerB->id,
            'discipline'   => '8-ball',
            'rating'       => 1600,
            'games_played' => 0,
            'frames_won'   => 0,
            'frames_lost'  => 0,
            'robustness'   => 0,
            'provisional'  => true,
        ]);

        $match = $this->makeMatch($comp, $playerA, $playerB, [
            'score_a' => 7,
            'score_b' => 0,
        ]);

        $this->service()->applyMatchResult($match);

        $deltaA = PlayerRating::where('player_id', $playerA->id)
                               ->where('discipline', '8-ball')
                               ->value('rating') - 1400;

        // Beating a 200-point-higher opponent should yield > 20 points
        $this->assertGreaterThan(20, $deltaA);
    }

    // -------------------------------------------------------------------------
    // 5. Blowout win gives more points than a close win
    // -------------------------------------------------------------------------

    public function test_blowout_win_gives_more_points_than_close_win(): void
    {
        $comp = $this->makeCompetition();

        // --- scenario 1: blowout 7-0 ---
        $pA1 = $this->makePlayer('BlowA');
        $pB1 = $this->makePlayer('BlowB');

        $matchBlowout = $this->makeMatch($comp, $pA1, $pB1, [
            'score_a' => 7, 'score_b' => 0,
        ]);
        $this->service()->applyMatchResult($matchBlowout);
        $deltaBlowout = PlayerRating::where('player_id', $pA1->id)
                                    ->where('discipline', '8-ball')
                                    ->value('rating') - 1500;

        // --- scenario 2: close 7-6, different players, fresh DB state ---
        $pA2 = $this->makePlayer('CloseA');
        $pB2 = $this->makePlayer('CloseB');

        $matchClose = $this->makeMatch($comp, $pA2, $pB2, [
            'score_a' => 7, 'score_b' => 6,
            'round_position' => 1,
        ]);
        $this->service()->applyMatchResult($matchClose);
        $deltaClose = PlayerRating::where('player_id', $pA2->id)
                                   ->where('discipline', '8-ball')
                                   ->value('rating') - 1500;

        $this->assertGreaterThan($deltaClose, $deltaBlowout);
    }

    // -------------------------------------------------------------------------
    // 6. Calling applyMatchResult twice is idempotent (one rating_event per match)
    // -------------------------------------------------------------------------

    public function test_rating_not_applied_twice(): void
    {
        $comp    = $this->makeCompetition();
        $playerA = $this->makePlayer('Idempotent', 'A');
        $playerB = $this->makePlayer('Idempotent', 'B');

        $match = $this->makeMatch($comp, $playerA, $playerB);

        $this->service()->applyMatchResult($match);
        $this->service()->applyMatchResult($match); // second call — must be a no-op

        $count = RatingEvent::where('match_id', $match->id)->count();
        $this->assertEquals(1, $count);
    }

    // -------------------------------------------------------------------------
    // 7. Draw match does not crash and leaves both players near 0 delta
    // -------------------------------------------------------------------------

    public function test_draw_gives_half_point(): void
    {
        $comp    = $this->makeCompetition();
        $playerA = $this->makePlayer('DrawA');
        $playerB = $this->makePlayer('DrawB');

        $match = $this->makeMatch($comp, $playerA, $playerB, [
            'score_a' => 4,
            'score_b' => 4,
            'is_draw' => true,
        ]);

        // Must not throw
        $this->service()->applyMatchResult($match);

        // Both ratings should exist and be close to 1500 (equal players, draw)
        $ratingA = PlayerRating::where('player_id', $playerA->id)->where('discipline', '8-ball')->value('rating');
        $ratingB = PlayerRating::where('player_id', $playerB->id)->where('discipline', '8-ball')->value('rating');

        $this->assertNotNull($ratingA);
        $this->assertNotNull($ratingB);
        // With equal 1500 ratings the expected score is exactly 0.5 each,
        // so the net delta must be 0 (rounded).
        $this->assertEquals(1500, $ratingA);
        $this->assertEquals(1500, $ratingB);
    }

    // -------------------------------------------------------------------------
    // 8. Ratings are per discipline — 10-ball rating is independent of 8-ball
    // -------------------------------------------------------------------------

    public function test_ratings_are_per_discipline(): void
    {
        $player = $this->makePlayer('Multi', 'Disc');

        // Pre-set an 8-ball rating at 1600
        PlayerRating::create([
            'player_id'    => $player->id,
            'discipline'   => '8-ball',
            'rating'       => 1600,
            'games_played' => 5,
            'frames_won'   => 35,
            'frames_lost'  => 10,
            'robustness'   => 5,
            'provisional'  => true,
        ]);

        // Requesting a 10-ball rating must create a fresh 1500 entry
        $tenBallRating = $this->service()->getOrCreateRating($player, '10-ball');

        $this->assertEquals(1500, $tenBallRating->rating);
        $this->assertEquals(0, $tenBallRating->games_played);

        // 8-ball rating must be untouched
        $eightBallRating = PlayerRating::where('player_id', $player->id)
                                       ->where('discipline', '8-ball')
                                       ->value('rating');
        $this->assertEquals(1600, $eightBallRating);
    }
}
