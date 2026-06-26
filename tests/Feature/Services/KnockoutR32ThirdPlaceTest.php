<?php

namespace Tests\Feature\Services;

use App\Models\Competition;
use App\Models\GameMatch;
use App\Models\Player;
use App\Services\BracketProgression;
use App\Services\KnockoutGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Tests for R32 bracket support (32 qualifiers = 16 first-round pairs)
 * and the third-place match (3P / petite finale).
 */
class KnockoutR32ThirdPlaceTest extends TestCase
{
    use RefreshDatabase;

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function makeCompetition(array $overrides = []): Competition
    {
        return Competition::create(array_merge([
            'name'         => 'R32 Test Cup',
            'slug'         => 'r32-test-' . Str::random(6),
            'discipline'   => '8-ball',
            'format'       => 'pools',
            'structure'    => 'pools_knockout',
            'race_to'      => 7,
            'pool_race_to' => 4,
            'knockout_race_to' => 7,
            'status'       => 'in_progress',
            'shot_clock'   => 30,
            'player_slots' => 32,
        ], $overrides));
    }

    private function makePlayer(string $first): Player
    {
        return Player::create(['first_name' => $first, 'last_name' => 'Test']);
    }

    /** Build 16 pairs of players for a 32-player R32 bracket. */
    private function make16Pairs(Competition $comp): array
    {
        $pairs = [];
        for ($i = 0; $i < 16; $i++) {
            $a = $this->makePlayer('PA' . $i);
            $b = $this->makePlayer('PB' . $i);
            $pairs[] = [
                ['player_id' => $a->id],
                ['player_id' => $b->id],
            ];
        }
        return $pairs;
    }

    // ── 1. R32 bracket: correct rounds ───────────────────────────────────────

    public function test_r32_bracket_generates_correct_round_chain(): void
    {
        $comp  = $this->makeCompetition();
        $pairs = $this->make16Pairs($comp);

        (new KnockoutGenerator())->generate($comp, $pairs);

        // First round must be R32 with 16 matches
        $this->assertDatabaseCount('matches', 16 + 8 + 4 + 2 + 1); // R32 + R16 + QF + SF + F

        $this->assertEquals(16, GameMatch::where('competition_id', $comp->id)->where('round', 'R32')->count());
        $this->assertEquals(8,  GameMatch::where('competition_id', $comp->id)->where('round', 'R16')->count());
        $this->assertEquals(4,  GameMatch::where('competition_id', $comp->id)->where('round', 'QF')->count());
        $this->assertEquals(2,  GameMatch::where('competition_id', $comp->id)->where('round', 'SF')->count());
        $this->assertEquals(1,  GameMatch::where('competition_id', $comp->id)->where('round', 'F')->count());
    }

    public function test_r32_first_round_matches_have_players_assigned(): void
    {
        $comp  = $this->makeCompetition();
        $pairs = $this->make16Pairs($comp);

        (new KnockoutGenerator())->generate($comp, $pairs);

        $r32 = GameMatch::where('competition_id', $comp->id)->where('round', 'R32')->get();
        $this->assertCount(16, $r32);
        foreach ($r32 as $match) {
            $this->assertNotNull($match->player_a_id, 'R32 match must have player_a');
            $this->assertNotNull($match->player_b_id, 'R32 match must have player_b');
            $this->assertEquals('scheduled', $match->status);
        }
    }

    public function test_r32_subsequent_rounds_are_pending_placeholders(): void
    {
        $comp  = $this->makeCompetition();
        $pairs = $this->make16Pairs($comp);

        (new KnockoutGenerator())->generate($comp, $pairs);

        $later = GameMatch::where('competition_id', $comp->id)
            ->whereIn('round', ['R16', 'QF', 'SF', 'F'])
            ->get();

        foreach ($later as $match) {
            $this->assertEquals('pending', $match->status, "Round {$match->round} placeholder must be pending");
            $this->assertNull($match->player_a_id);
            $this->assertNull($match->player_b_id);
        }
    }

    // ── 2. Third-place match creation ─────────────────────────────────────────

    public function test_third_place_match_created_when_flag_enabled(): void
    {
        $comp = $this->makeCompetition([
            'settings' => ['has_third_place_match' => true],
        ]);
        $pairs = $this->make16Pairs($comp);

        (new KnockoutGenerator())->generate($comp, $pairs);

        $this->assertEquals(1, GameMatch::where('competition_id', $comp->id)->where('round', '3P')->count());

        $thirdPlace = GameMatch::where('competition_id', $comp->id)->where('round', '3P')->first();
        $this->assertEquals('pending', $thirdPlace->status);
        $this->assertEquals(0, $thirdPlace->round_position);
        $this->assertNull($thirdPlace->player_a_id);
        $this->assertNull($thirdPlace->player_b_id);
    }

    public function test_third_place_match_not_created_by_default(): void
    {
        $comp  = $this->makeCompetition(); // no has_third_place_match in settings
        $pairs = $this->make16Pairs($comp);

        (new KnockoutGenerator())->generate($comp, $pairs);

        $this->assertEquals(0, GameMatch::where('competition_id', $comp->id)->where('round', '3P')->count());
    }

    // ── 3. SF loser routing to 3P ─────────────────────────────────────────────

    public function test_sf_loser_routes_to_third_place_match(): void
    {
        $comp    = $this->makeCompetition(['settings' => ['has_third_place_match' => true]]);
        $pairs   = $this->make16Pairs($comp);

        (new KnockoutGenerator())->generate($comp, $pairs);

        // Get two SF matches and simulate both being played
        $sf0 = GameMatch::where('competition_id', $comp->id)->where('round', 'SF')->where('round_position', 0)->first();
        $sf1 = GameMatch::where('competition_id', $comp->id)->where('round', 'SF')->where('round_position', 1)->first();

        $winnerA  = $this->makePlayer('SFWinnerA');
        $winnerB  = $this->makePlayer('SFWinnerB');
        $loserA   = $this->makePlayer('SFLoserA');
        $loserB   = $this->makePlayer('SFLoserB');

        $sf0->update([
            'player_a_id' => $winnerA->id,
            'player_b_id' => $loserA->id,
            'score_a'     => 9,
            'score_b'     => 5,
            'status'      => 'done',
        ]);

        $sf1->update([
            'player_a_id' => $loserB->id,
            'player_b_id' => $winnerB->id,
            'score_a'     => 3,
            'score_b'     => 9,
            'status'      => 'done',
        ]);

        $service = new BracketProgression();
        $service->advanceWinner($sf0->fresh());
        $service->advanceWinner($sf1->fresh());

        // Winners → Final
        $final = GameMatch::where('competition_id', $comp->id)->where('round', 'F')->first();
        $this->assertEquals($winnerA->id, $final->player_a_id, 'SF0 winner → F slot A');
        $this->assertEquals($winnerB->id, $final->player_b_id, 'SF1 winner → F slot B');
        $this->assertEquals('scheduled', $final->status);

        // Losers → 3P
        $thirdPlace = GameMatch::where('competition_id', $comp->id)->where('round', '3P')->first();
        $this->assertEquals($loserA->id, $thirdPlace->player_a_id, 'SF0 loser → 3P slot A');
        $this->assertEquals($loserB->id, $thirdPlace->player_b_id, 'SF1 loser → 3P slot B');
        $this->assertEquals('scheduled', $thirdPlace->status);
    }

    public function test_sf_advancement_still_works_when_no_third_place_match(): void
    {
        // Without a 3P match in the DB, advanceWinner must not throw.
        $comp  = $this->makeCompetition(); // no has_third_place_match
        $pairs = $this->make16Pairs($comp);

        (new KnockoutGenerator())->generate($comp, $pairs);

        $sf = GameMatch::where('competition_id', $comp->id)->where('round', 'SF')->where('round_position', 0)->first();
        $pA = $this->makePlayer('SFA');
        $pB = $this->makePlayer('SFB');

        $sf->update([
            'player_a_id' => $pA->id,
            'player_b_id' => $pB->id,
            'score_a'     => 9,
            'score_b'     => 4,
            'status'      => 'done',
        ]);

        // Must not throw
        (new BracketProgression())->advanceWinner($sf->fresh());

        // Winner reaches Final slot A
        $final = GameMatch::where('competition_id', $comp->id)->where('round', 'F')->first();
        $this->assertEquals($pA->id, $final->player_a_id);

        // No 3P match exists
        $this->assertEquals(0, GameMatch::where('competition_id', $comp->id)->where('round', '3P')->count());
    }

    // ── 4. Competition::raceForRound() ────────────────────────────────────────

    public function test_race_for_round_reads_per_round_config(): void
    {
        $comp = $this->makeCompetition([
            'knockout_race_to' => 7,
            'settings' => [
                'round_race_to' => [
                    'R32' => 7,
                    'R16' => 7,
                    'QF'  => 9,
                    'SF'  => 9,
                    '3P'  => 5,
                    'F'   => 11,
                ],
            ],
        ]);

        $this->assertEquals(7,  $comp->raceForRound('R32'));
        $this->assertEquals(7,  $comp->raceForRound('R16'));
        $this->assertEquals(9,  $comp->raceForRound('QF'));
        $this->assertEquals(9,  $comp->raceForRound('SF'));
        $this->assertEquals(5,  $comp->raceForRound('3P'));
        $this->assertEquals(11, $comp->raceForRound('F'));
    }

    public function test_race_for_round_falls_back_to_knockout_race_to(): void
    {
        $comp = $this->makeCompetition(['knockout_race_to' => 9]);
        // No round_race_to in settings
        $this->assertEquals(9, $comp->raceForRound('QF'));
        $this->assertEquals(9, $comp->raceForRound('F'));
    }
}
