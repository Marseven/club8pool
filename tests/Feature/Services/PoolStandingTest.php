<?php

namespace Tests\Feature\Services;

use App\Models\Competition;
use App\Models\GameMatch;
use App\Models\Player;
use App\Models\Pool;
use App\Models\Registration;
use App\Services\PoolStanding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PoolStandingTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeCompetition(): Competition
    {
        return Competition::create([
            'name'         => 'Standing Test Cup',
            'slug'         => 'standing-test-' . Str::random(6),
            'discipline'   => '8-ball',
            'format'       => 'pools',
            'structure'    => 'pools_knockout',
            'race_to'      => 7,
            'status'       => 'in_progress',
            'shot_clock'   => 30,
            'player_slots' => 16,
        ]);
    }

    private function makePool(Competition $comp, string $name = 'A'): Pool
    {
        return Pool::create([
            'competition_id' => $comp->id,
            'name'           => $name,
            'position'       => 0,
            'size'           => 4,
        ]);
    }

    private function makePlayer(string $first, string $last = 'Player'): Player
    {
        return Player::create(['first_name' => $first, 'last_name' => $last]);
    }

    private function registerPlayer(Player $player, Competition $comp, Pool $pool, int $slot): Registration
    {
        return Registration::create([
            'competition_id' => $comp->id,
            'player_id'      => $player->id,
            'pool_id'        => $pool->id,
            'pool_slot'      => $slot,
            'status'         => 'confirmed',
        ]);
    }

    private function makePoolMatch(Competition $comp, Pool $pool, array $overrides = []): GameMatch
    {
        return GameMatch::create(array_merge([
            'competition_id' => $comp->id,
            'pool_id'        => $pool->id,
            'phase'          => 'pool',
            'round'          => 'R16',
            'round_position' => 0,
            'status'         => 'scheduled',
            'score_a'        => 0,
            'score_b'        => 0,
        ], $overrides));
    }

    // -------------------------------------------------------------------------
    // 1. No matches — all players start at zero
    // -------------------------------------------------------------------------

    public function test_standings_with_no_matches_shows_all_players_at_zero(): void
    {
        $comp = $this->makeCompetition();
        $pool = $this->makePool($comp);

        $p1 = $this->makePlayer('Alice');
        $p2 = $this->makePlayer('Bob');
        $p3 = $this->makePlayer('Carol');

        $this->registerPlayer($p1, $comp, $pool, 1);
        $this->registerPlayer($p2, $comp, $pool, 2);
        $this->registerPlayer($p3, $comp, $pool, 3);

        $standings = PoolStanding::compute($pool);

        $this->assertCount(3, $standings);

        foreach ($standings as $row) {
            $this->assertEquals(0, $row['v'], "Expected v=0 for {$row['player']->first_name}");
            $this->assertEquals(0, $row['w'], "Expected w=0 for {$row['player']->first_name}");
            $this->assertEquals(0, $row['l'], "Expected l=0 for {$row['player']->first_name}");
        }
    }

    // -------------------------------------------------------------------------
    // 2. Winner of a match gets v incremented by 1
    // -------------------------------------------------------------------------

    public function test_winner_gets_v_plus_one(): void
    {
        $comp = $this->makeCompetition();
        $pool = $this->makePool($comp);

        $playerA = $this->makePlayer('Dave');
        $playerB = $this->makePlayer('Eve');

        $this->registerPlayer($playerA, $comp, $pool, 1);
        $this->registerPlayer($playerB, $comp, $pool, 2);

        $this->makePoolMatch($comp, $pool, [
            'player_a_id' => $playerA->id,
            'player_b_id' => $playerB->id,
            'score_a'     => 7,
            'score_b'     => 3,
            'status'      => 'done',
        ]);

        $standings = PoolStanding::compute($pool);

        $rowA = $standings->firstWhere('player_id', $playerA->id);
        $rowB = $standings->firstWhere('player_id', $playerB->id);

        $this->assertEquals(1, $rowA['v']);
        $this->assertEquals(0, $rowB['v']);
    }

    // -------------------------------------------------------------------------
    // 3. Frames are counted correctly (w = frames won, l = frames lost)
    // -------------------------------------------------------------------------

    public function test_frames_are_counted_correctly(): void
    {
        $comp = $this->makeCompetition();
        $pool = $this->makePool($comp);

        $playerA = $this->makePlayer('Frank');
        $playerB = $this->makePlayer('Grace');

        $this->registerPlayer($playerA, $comp, $pool, 1);
        $this->registerPlayer($playerB, $comp, $pool, 2);

        $this->makePoolMatch($comp, $pool, [
            'player_a_id' => $playerA->id,
            'player_b_id' => $playerB->id,
            'score_a'     => 7,
            'score_b'     => 3,
            'status'      => 'done',
        ]);

        $standings = PoolStanding::compute($pool);

        $rowA = $standings->firstWhere('player_id', $playerA->id);
        $rowB = $standings->firstWhere('player_id', $playerB->id);

        $this->assertEquals(7, $rowA['w']);
        $this->assertEquals(3, $rowA['l']);
        $this->assertEquals(3, $rowB['w']);
        $this->assertEquals(7, $rowB['l']);
    }

    // -------------------------------------------------------------------------
    // 4. Standings sorted by victories first
    // -------------------------------------------------------------------------

    public function test_standings_sorted_by_victories_first(): void
    {
        $comp = $this->makeCompetition();
        $pool = $this->makePool($comp);

        $playerA = $this->makePlayer('Henri');  // 2 wins
        $playerB = $this->makePlayer('Irene');  // 1 win
        $playerC = $this->makePlayer('Jake');   // 0 wins

        $this->registerPlayer($playerA, $comp, $pool, 1);
        $this->registerPlayer($playerB, $comp, $pool, 2);
        $this->registerPlayer($playerC, $comp, $pool, 3);

        // A beats B
        $this->makePoolMatch($comp, $pool, [
            'player_a_id' => $playerA->id,
            'player_b_id' => $playerB->id,
            'score_a' => 7, 'score_b' => 2,
            'status'  => 'done',
            'round_position' => 0,
        ]);

        // A beats C
        $this->makePoolMatch($comp, $pool, [
            'player_a_id' => $playerA->id,
            'player_b_id' => $playerC->id,
            'score_a' => 7, 'score_b' => 1,
            'status'  => 'done',
            'round_position' => 1,
        ]);

        // B beats C
        $this->makePoolMatch($comp, $pool, [
            'player_a_id' => $playerB->id,
            'player_b_id' => $playerC->id,
            'score_a' => 7, 'score_b' => 4,
            'status'  => 'done',
            'round_position' => 2,
        ]);

        $standings = PoolStanding::compute($pool);

        $this->assertEquals($playerA->id, $standings[0]['player_id']); // 2V
        $this->assertEquals($playerB->id, $standings[1]['player_id']); // 1V
        $this->assertEquals($playerC->id, $standings[2]['player_id']); // 0V
    }

    // -------------------------------------------------------------------------
    // 5. Tiebreak by frame difference
    // -------------------------------------------------------------------------

    public function test_tiebreak_by_frame_difference(): void
    {
        $comp = $this->makeCompetition();
        $pool = $this->makePool($comp);

        // Use a third "cannon" player to give both A and B exactly 1 victory.
        $playerA = $this->makePlayer('Karl');  // wins vs C with big margin (diff +6)
        $playerB = $this->makePlayer('Lisa');  // wins vs C with small margin (diff +1)
        $playerC = $this->makePlayer('Max');   // loses both

        $this->registerPlayer($playerA, $comp, $pool, 1);
        $this->registerPlayer($playerB, $comp, $pool, 2);
        $this->registerPlayer($playerC, $comp, $pool, 3);

        // A vs C: A wins 7-1  → diff for A = +6
        $this->makePoolMatch($comp, $pool, [
            'player_a_id'    => $playerA->id,
            'player_b_id'    => $playerC->id,
            'score_a' => 7, 'score_b' => 1,
            'status'  => 'done',
            'round_position' => 0,
        ]);

        // B vs C: B wins 7-6  → diff for B = +1
        $this->makePoolMatch($comp, $pool, [
            'player_a_id'    => $playerB->id,
            'player_b_id'    => $playerC->id,
            'score_a' => 7, 'score_b' => 6,
            'status'  => 'done',
            'round_position' => 1,
        ]);

        // A vs B: draw-ish match or not played — give them a head-to-head that
        // is a draw so it falls through to diff as tiebreaker.
        // We skip this match so neither plays the other, keeping only diff as tie.

        $standings = PoolStanding::compute($pool);

        // Both A and B have 1V; A has higher diff (+6 vs +1) → A first.
        $top2 = $standings->take(2)->pluck('player_id')->values();
        $this->assertEquals($playerA->id, $top2[0]);
        $this->assertEquals($playerB->id, $top2[1]);
    }
}
