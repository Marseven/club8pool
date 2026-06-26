<?php

namespace Tests\Feature\Services;

use App\Models\Competition;
use App\Models\GameMatch;
use App\Models\Player;
use App\Services\BracketProgression;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class BracketProgressionTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeCompetition(): Competition
    {
        return Competition::create([
            'name'         => 'Bracket Test Cup',
            'slug'         => 'bracket-test-' . Str::random(6),
            'discipline'   => '8-ball',
            'format'       => 'single_elim',
            'structure'    => 'knockout',
            'race_to'      => 7,
            'status'       => 'in_progress',
            'shot_clock'   => 30,
            'player_slots' => 16,
        ]);
    }

    private function makePlayer(string $first, string $last = 'Test'): Player
    {
        return Player::create(['first_name' => $first, 'last_name' => $last]);
    }

    private function makeKnockoutMatch(Competition $comp, array $overrides = []): GameMatch
    {
        return GameMatch::create(array_merge([
            'competition_id' => $comp->id,
            'phase'          => 'knockout',
            'round'          => 'R16',
            'round_position' => 0,
            'status'         => 'scheduled',
            'score_a'        => 0,
            'score_b'        => 0,
        ], $overrides));
    }

    private function service(): BracketProgression
    {
        return new BracketProgression();
    }

    // -------------------------------------------------------------------------
    // 1. Winner advances to the next-round match in the correct slot
    // -------------------------------------------------------------------------

    public function test_winner_advances_to_next_round(): void
    {
        $comp    = $this->makeCompetition();
        $playerA = $this->makePlayer('Jean', 'Dupont');
        $playerB = $this->makePlayer('Pierre', 'Martin');

        // R16 match at position 0 — winner goes to QF at position 0, slot A
        $r16 = $this->makeKnockoutMatch($comp, [
            'round'          => 'R16',
            'round_position' => 0,
            'status'         => 'done',
            'score_a'        => 7,
            'score_b'        => 3,
            'player_a_id'    => $playerA->id,
            'player_b_id'    => $playerB->id,
        ]);

        // Placeholder QF match
        $qf = $this->makeKnockoutMatch($comp, [
            'round'          => 'QF',
            'round_position' => 0,
            'status'         => 'pending',
        ]);

        $this->service()->advanceWinner($r16);

        $this->assertDatabaseHas('matches', [
            'id'           => $qf->id,
            'player_a_id'  => $playerA->id, // winner (position 0 → slot A)
        ]);
    }

    // -------------------------------------------------------------------------
    // 2. Loser does NOT advance — the other slot stays null
    // -------------------------------------------------------------------------

    public function test_loser_does_not_advance(): void
    {
        $comp    = $this->makeCompetition();
        $playerA = $this->makePlayer('Leon', 'Girard');
        $playerB = $this->makePlayer('Marc', 'Lebrun');

        $r16 = $this->makeKnockoutMatch($comp, [
            'round'          => 'R16',
            'round_position' => 0,
            'status'         => 'done',
            'score_a'        => 7,
            'score_b'        => 3,
            'player_a_id'    => $playerA->id,
            'player_b_id'    => $playerB->id,
        ]);

        $qf = $this->makeKnockoutMatch($comp, [
            'round'          => 'QF',
            'round_position' => 0,
            'status'         => 'pending',
        ]);

        $this->service()->advanceWinner($r16);

        $qf->refresh();
        // Slot B must remain empty — only the winner (playerA) was placed.
        $this->assertNull($qf->player_b_id);
    }

    // -------------------------------------------------------------------------
    // 3. Calling advanceWinner twice is idempotent
    // -------------------------------------------------------------------------

    public function test_progression_is_idempotent(): void
    {
        $comp    = $this->makeCompetition();
        $playerA = $this->makePlayer('Hugo', 'Noir');
        $playerB = $this->makePlayer('Luc', 'Blanc');

        $r16 = $this->makeKnockoutMatch($comp, [
            'round'          => 'R16',
            'round_position' => 0,
            'status'         => 'done',
            'score_a'        => 7,
            'score_b'        => 2,
            'player_a_id'    => $playerA->id,
            'player_b_id'    => $playerB->id,
        ]);

        $qf = $this->makeKnockoutMatch($comp, [
            'round'          => 'QF',
            'round_position' => 0,
            'status'         => 'pending',
        ]);

        $this->service()->advanceWinner($r16);
        // Second call — must not throw and must leave the value intact.
        $this->service()->advanceWinner($r16);

        $qf->refresh();
        $this->assertEquals($playerA->id, $qf->player_a_id);
    }

    // -------------------------------------------------------------------------
    // 4. A draw match does NOT advance anyone
    // -------------------------------------------------------------------------

    public function test_draw_does_not_advance(): void
    {
        $comp    = $this->makeCompetition();
        $playerA = $this->makePlayer('Ana', 'Costa');
        $playerB = $this->makePlayer('Bia', 'Silva');

        $r16 = $this->makeKnockoutMatch($comp, [
            'round'          => 'R16',
            'round_position' => 0,
            'status'         => 'done',
            'score_a'        => 4,
            'score_b'        => 4,
            'is_draw'        => true,
            'player_a_id'    => $playerA->id,
            'player_b_id'    => $playerB->id,
        ]);

        $qf = $this->makeKnockoutMatch($comp, [
            'round'          => 'QF',
            'round_position' => 0,
            'status'         => 'pending',
        ]);

        $this->service()->advanceWinner($r16);

        $qf->refresh();
        $this->assertNull($qf->player_a_id);
        $this->assertNull($qf->player_b_id);
    }

    // -------------------------------------------------------------------------
    // 5. Pool-phase match does NOT trigger advancement
    // -------------------------------------------------------------------------

    public function test_pool_match_does_not_advance(): void
    {
        $comp    = $this->makeCompetition();
        $playerA = $this->makePlayer('Tom', 'Ferreira');
        $playerB = $this->makePlayer('Zed', 'Alves');

        $poolMatch = GameMatch::create([
            'competition_id' => $comp->id,
            'phase'          => 'pool',       // <-- pool phase
            'round'          => 'R16',
            'round_position' => 0,
            'status'         => 'done',
            'score_a'        => 7,
            'score_b'        => 2,
            'player_a_id'    => $playerA->id,
            'player_b_id'    => $playerB->id,
        ]);

        $qf = $this->makeKnockoutMatch($comp, [
            'round'          => 'QF',
            'round_position' => 0,
            'status'         => 'pending',
        ]);

        $this->service()->advanceWinner($poolMatch);

        $qf->refresh();
        $this->assertNull($qf->player_a_id);
    }

    // -------------------------------------------------------------------------
    // 6. Final match does not crash — no next round exists
    // -------------------------------------------------------------------------

    public function test_final_match_does_not_advance(): void
    {
        $comp    = $this->makeCompetition();
        $playerA = $this->makePlayer('Max', 'Muster');
        $playerB = $this->makePlayer('Jan', 'Kowalski');

        $final = $this->makeKnockoutMatch($comp, [
            'round'          => 'F',
            'round_position' => 0,
            'status'         => 'done',
            'score_a'        => 7,
            'score_b'        => 5,
            'player_a_id'    => $playerA->id,
            'player_b_id'    => $playerB->id,
        ]);

        // No assertion needed beyond "it doesn't throw"
        $this->service()->advanceWinner($final);

        $this->assertTrue(true);
    }
}
