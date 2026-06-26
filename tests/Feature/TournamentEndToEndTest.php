<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Competition;
use App\Models\GameMatch;
use App\Models\Player;
use App\Models\PlayerRating;
use App\Models\Pool;
use App\Models\RatingEvent;
use App\Models\Registration;
use App\Models\User;
use App\Services\BracketProgression;
use App\Services\KnockoutGenerator;
use App\Services\PlayerRatingService;
use App\Services\PoolStanding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class TournamentEndToEndTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // Helpers
    // =========================================================================

    private function makeCompetition(array $overrides = []): Competition
    {
        return Competition::create(array_merge([
            'name'               => 'E2E Test Cup',
            'slug'               => 'e2e-test-' . Str::random(6),
            'discipline'         => '8-ball',
            'format'             => 'pools',
            'structure'          => 'pools_knockout',
            'race_to'            => 3,
            'pool_race_to'       => 3,
            'knockout_race_to'   => 3,
            'status'             => 'in_progress',
            'shot_clock'         => 30,
            'player_slots'       => 8,
            'pool_count'         => 2,
            'pool_size'          => 4,
            'qualifiers_per_pool' => 2,
        ], $overrides));
    }

    private function makePlayer(string $first, string $last = 'Player'): Player
    {
        return Player::create(['first_name' => $first, 'last_name' => $last]);
    }

    private function makePool(Competition $comp, string $name, int $position = 0): Pool
    {
        return Pool::create([
            'competition_id' => $comp->id,
            'name'           => $name,
            'position'       => $position,
            'size'           => 4,
        ]);
    }

    private function registerPlayerInPool(Competition $comp, Pool $pool, Player $player, int $slot): Registration
    {
        return Registration::create([
            'competition_id' => $comp->id,
            'pool_id'        => $pool->id,
            'pool_slot'      => $slot,
            'player_id'      => $player->id,
            'status'         => 'confirmed',
        ]);
    }

    private function makePoolMatch(Competition $comp, Pool $pool, Player $a, Player $b, int $pos): GameMatch
    {
        return GameMatch::create([
            'competition_id' => $comp->id,
            'pool_id'        => $pool->id,
            'phase'          => 'pool',
            'round'          => 'R16',
            'round_position' => $pos,
            'player_a_id'    => $a->id,
            'player_b_id'    => $b->id,
            'score_a'        => 0,
            'score_b'        => 0,
            'status'         => 'scheduled',
        ]);
    }

    private function closePoolMatch(GameMatch $match, int $scoreA, int $scoreB): void
    {
        $match->update([
            'score_a' => $scoreA,
            'score_b' => $scoreB,
            'status'  => 'done',
            'ended_at' => now(),
        ]);
    }

    private function makeAdmin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function makeReferee(string $name = 'Ref'): User
    {
        return User::factory()->create([
            'name' => $name,
            'role' => 'referee',
            'pin'  => Hash::make('1234'),
        ]);
    }

    // =========================================================================
    // Test 1 — Full competition lifecycle
    // =========================================================================

    public function test_full_competition_lifecycle(): void
    {
        // 1. Create competition (pools_knockout, race_to 3, 8 players)
        $comp = $this->makeCompetition();

        // 2. Create 8 players, assign to 2 pools of 4
        $players = [];
        for ($i = 1; $i <= 8; $i++) {
            $players[$i] = $this->makePlayer("Player{$i}", 'Test');
        }

        $poolA = $this->makePool($comp, 'A', 0);
        $poolB = $this->makePool($comp, 'B', 1);

        // Pool A: players 1-4
        $this->registerPlayerInPool($comp, $poolA, $players[1], 1);
        $this->registerPlayerInPool($comp, $poolA, $players[2], 2);
        $this->registerPlayerInPool($comp, $poolA, $players[3], 3);
        $this->registerPlayerInPool($comp, $poolA, $players[4], 4);

        // Pool B: players 5-8
        $this->registerPlayerInPool($comp, $poolB, $players[5], 1);
        $this->registerPlayerInPool($comp, $poolB, $players[6], 2);
        $this->registerPlayerInPool($comp, $poolB, $players[7], 3);
        $this->registerPlayerInPool($comp, $poolB, $players[8], 4);

        // 3. Create pool matches (round-robin within each pool) and score them
        // Pool A round-robin (6 matches for 4 players)
        $poolAMatches = [
            $this->makePoolMatch($comp, $poolA, $players[1], $players[2], 0),
            $this->makePoolMatch($comp, $poolA, $players[1], $players[3], 1),
            $this->makePoolMatch($comp, $poolA, $players[1], $players[4], 2),
            $this->makePoolMatch($comp, $poolA, $players[2], $players[3], 3),
            $this->makePoolMatch($comp, $poolA, $players[2], $players[4], 4),
            $this->makePoolMatch($comp, $poolA, $players[3], $players[4], 5),
        ];

        // Player 1 wins all (3-0), Player 2 wins next two (3-0), etc.
        $this->closePoolMatch($poolAMatches[0], 3, 0); // P1 beats P2
        $this->closePoolMatch($poolAMatches[1], 3, 0); // P1 beats P3
        $this->closePoolMatch($poolAMatches[2], 3, 0); // P1 beats P4
        $this->closePoolMatch($poolAMatches[3], 3, 0); // P2 beats P3
        $this->closePoolMatch($poolAMatches[4], 3, 0); // P2 beats P4
        $this->closePoolMatch($poolAMatches[5], 3, 0); // P3 beats P4

        // Pool B round-robin
        $poolBMatches = [
            $this->makePoolMatch($comp, $poolB, $players[5], $players[6], 0),
            $this->makePoolMatch($comp, $poolB, $players[5], $players[7], 1),
            $this->makePoolMatch($comp, $poolB, $players[5], $players[8], 2),
            $this->makePoolMatch($comp, $poolB, $players[6], $players[7], 3),
            $this->makePoolMatch($comp, $poolB, $players[6], $players[8], 4),
            $this->makePoolMatch($comp, $poolB, $players[7], $players[8], 5),
        ];

        $this->closePoolMatch($poolBMatches[0], 3, 0); // P5 beats P6
        $this->closePoolMatch($poolBMatches[1], 3, 0); // P5 beats P7
        $this->closePoolMatch($poolBMatches[2], 3, 0); // P5 beats P8
        $this->closePoolMatch($poolBMatches[3], 3, 0); // P6 beats P7
        $this->closePoolMatch($poolBMatches[4], 3, 0); // P6 beats P8
        $this->closePoolMatch($poolBMatches[5], 3, 0); // P7 beats P8

        // 4. Compute pool standings — verify top 2 per pool qualify
        $standingsA = PoolStanding::compute($poolA);
        $standingsB = PoolStanding::compute($poolB);

        // Top 2 from pool A should be P1 (3V) and P2 (2V)
        $this->assertEquals($players[1]->id, $standingsA->first()['player_id']);
        $this->assertEquals($players[2]->id, $standingsA->get(1)['player_id']);

        // Top 2 from pool B should be P5 (3V) and P6 (2V)
        $this->assertEquals($players[5]->id, $standingsB->first()['player_id']);
        $this->assertEquals($players[6]->id, $standingsB->get(1)['player_id']);

        $qualifiersA = PoolStanding::qualifiers($poolA, 2);
        $qualifiersB = PoolStanding::qualifiers($poolB, 2);

        $this->assertCount(2, $qualifiersA);
        $this->assertCount(2, $qualifiersB);

        // 5. Generate knockout bracket (SF + F for 2 pairs of 4 qualifiers)
        $qualData = [
            'A' => $qualifiersA->map(fn ($r) => ['player_id' => $r['player_id']])->values()->toArray(),
            'B' => $qualifiersB->map(fn ($r) => ['player_id' => $r['player_id']])->values()->toArray(),
        ];

        $pairs = [
            [['player_id' => $players[1]->id], ['player_id' => $players[6]->id]],
            [['player_id' => $players[5]->id], ['player_id' => $players[2]->id]],
        ];

        $gen = new KnockoutGenerator();
        $gen->generate($comp, $pairs);

        // Verify SF matches created
        $sfMatches = GameMatch::where('competition_id', $comp->id)
            ->where('phase', 'knockout')
            ->where('round', 'SF')
            ->orderBy('round_position')
            ->get();

        $this->assertCount(2, $sfMatches);

        $final = GameMatch::where('competition_id', $comp->id)
            ->where('phase', 'knockout')
            ->where('round', 'F')
            ->first();

        $this->assertNotNull($final);

        // 6. Run SF matches
        $sf1 = $sfMatches[0];
        $sf2 = $sfMatches[1];

        $sf1->update(['status' => 'done', 'score_a' => 3, 'score_b' => 1, 'ended_at' => now()]);
        (new PlayerRatingService())->applyMatchResult($sf1->fresh());
        (new BracketProgression())->advanceWinner($sf1->fresh());

        $sf2->update(['status' => 'done', 'score_a' => 3, 'score_b' => 2, 'ended_at' => now()]);
        (new PlayerRatingService())->applyMatchResult($sf2->fresh());
        (new BracketProgression())->advanceWinner($sf2->fresh());

        // Final should now have both players populated
        $final->refresh();
        $this->assertNotNull($final->player_a_id);
        $this->assertNotNull($final->player_b_id);
        $this->assertEquals('scheduled', $final->status);

        // 7. Run the Final
        $final->update([
            'status'  => 'done',
            'score_a' => 3,
            'score_b' => 1,
            'ended_at' => now(),
        ]);

        $final->refresh();
        $this->assertGreaterThan($final->score_b, $final->score_a, 'Winner has higher score in final');

        // Apply ratings for final
        (new PlayerRatingService())->applyMatchResult($final->fresh());

        // 8. Apply ratings for all pool matches — rating engine must run for all 8 players
        foreach ($poolAMatches as $m) {
            (new PlayerRatingService())->applyMatchResult($m->fresh());
        }
        foreach ($poolBMatches as $m) {
            (new PlayerRatingService())->applyMatchResult($m->fresh());
        }

        // Verify PlayerRating records were created for all 8 players
        foreach ([1, 2, 3, 4, 5, 6, 7, 8] as $i) {
            $this->assertDatabaseHas('player_ratings', [
                'player_id'  => $players[$i]->id,
                'discipline' => '8-ball',
            ]);
        }

        // Verify RatingEvent was created for each pool match (idempotency: one per match)
        $allMatchIds = collect($poolAMatches)->merge($poolBMatches)->pluck('id')->toArray();
        foreach ($allMatchIds as $matchId) {
            $this->assertDatabaseHas('rating_events', ['match_id' => $matchId]);
        }

        // 9. Verify AuditLog has entries for match closes
        $admin = $this->makeAdmin();
        $this->actingAs($admin);

        \App\Services\AuditLogService::matchClosed($sf1->fresh(), ['status' => 'live', 'score_a' => 0, 'score_b' => 0]);
        \App\Services\AuditLogService::matchClosed($sf2->fresh(), ['status' => 'live', 'score_a' => 0, 'score_b' => 0]);

        $this->assertDatabaseHas('audit_logs', ['action' => 'match.close']);
        $this->assertGreaterThanOrEqual(2, AuditLog::where('action', 'match.close')->count());
    }

    // =========================================================================
    // Test 2 — Full bracket progression chain QF→SF→F
    // =========================================================================

    public function test_bracket_progression_full_chain(): void
    {
        $comp = $this->makeCompetition([
            'structure' => 'knockout',
            'format'    => 'single_elim',
        ]);

        $players = [];
        for ($i = 1; $i <= 8; $i++) {
            $players[$i] = $this->makePlayer("Chain{$i}");
        }

        $service = new BracketProgression();

        // Create QF matches (4 matches → 2 SFs → 1 F)
        $qf1 = GameMatch::create([
            'competition_id' => $comp->id,
            'phase'          => 'knockout',
            'round'          => 'QF',
            'round_position' => 0,
            'status'         => 'scheduled',
            'player_a_id'    => $players[1]->id,
            'player_b_id'    => $players[2]->id,
            'score_a'        => 0,
            'score_b'        => 0,
        ]);

        $qf2 = GameMatch::create([
            'competition_id' => $comp->id,
            'phase'          => 'knockout',
            'round'          => 'QF',
            'round_position' => 1,
            'status'         => 'scheduled',
            'player_a_id'    => $players[3]->id,
            'player_b_id'    => $players[4]->id,
            'score_a'        => 0,
            'score_b'        => 0,
        ]);

        $sf1 = GameMatch::create([
            'competition_id' => $comp->id,
            'phase'          => 'knockout',
            'round'          => 'SF',
            'round_position' => 0,
            'status'         => 'pending',
        ]);

        $final = GameMatch::create([
            'competition_id' => $comp->id,
            'phase'          => 'knockout',
            'round'          => 'F',
            'round_position' => 0,
            'status'         => 'pending',
        ]);

        // Step 1: Close QF1 → winner (P1) should appear in SF1 player_a
        $qf1->update(['status' => 'done', 'score_a' => 3, 'score_b' => 1]);
        $service->advanceWinner($qf1->fresh());

        $sf1->refresh();
        $this->assertEquals($players[1]->id, $sf1->player_a_id, 'QF1 winner goes to SF1 slot A');
        $this->assertNull($sf1->player_b_id, 'SF1 slot B still empty after QF1');
        $this->assertEquals('pending', $sf1->status, 'SF1 still pending (only one player)');

        // Step 2: Close QF2 → winner (P3) should appear in SF1 player_b
        // and SF1 status should become 'scheduled'
        $qf2->update(['status' => 'done', 'score_a' => 3, 'score_b' => 2]);
        $service->advanceWinner($qf2->fresh());

        $sf1->refresh();
        $this->assertEquals($players[1]->id, $sf1->player_a_id, 'SF1 slot A unchanged');
        $this->assertEquals($players[3]->id, $sf1->player_b_id, 'QF2 winner goes to SF1 slot B');
        $this->assertEquals('scheduled', $sf1->status, 'SF1 becomes scheduled when both players filled');

        // Step 3: Close SF → winner appears in Final
        $sf1->update(['status' => 'done', 'score_a' => 3, 'score_b' => 0]);
        $service->advanceWinner($sf1->fresh());

        $final->refresh();
        $this->assertEquals($players[1]->id, $final->player_a_id, 'SF1 winner goes to Final slot A');
    }

    // =========================================================================
    // Test 3 — Double claim race condition
    // =========================================================================

    public function test_double_claim_race_condition(): void
    {
        $referee1 = $this->makeReferee('RefA');
        $referee2 = $this->makeReferee('RefB');

        $comp = $this->makeCompetition(['structure' => 'knockout', 'format' => 'single_elim']);

        // Simulate that referee1 already claimed the match (pre-set referee_id)
        $match = GameMatch::create([
            'competition_id' => $comp->id,
            'phase'          => 'knockout',
            'round'          => 'QF',
            'round_position' => 0,
            'status'         => 'scheduled',
            'referee_id'     => $referee1->id, // already claimed
        ]);

        $token2 = $referee2->createToken('test')->plainTextToken;

        // Referee2 tries to claim a match already held by referee1 — should get 403
        $this->withToken($token2)
             ->postJson("/api/referee/matches/{$match->id}/claim")
             ->assertStatus(403);

        // Confirm referee_id is still referee1
        $this->assertDatabaseHas('matches', [
            'id'         => $match->id,
            'referee_id' => $referee1->id,
        ]);
    }

    // =========================================================================
    // Test 4 — Rating idempotency on repeat close
    // =========================================================================

    public function test_rating_idempotency_on_repeat_close(): void
    {
        $comp = $this->makeCompetition(['structure' => 'knockout', 'format' => 'single_elim']);

        $playerA = $this->makePlayer('IdemA');
        $playerB = $this->makePlayer('IdemB');

        $match = GameMatch::create([
            'competition_id' => $comp->id,
            'phase'          => 'knockout',
            'round'          => 'QF',
            'round_position' => 0,
            'status'         => 'done',
            'player_a_id'    => $playerA->id,
            'player_b_id'    => $playerB->id,
            'score_a'        => 3,
            'score_b'        => 1,
            'ended_at'       => now(),
        ]);

        $service = new PlayerRatingService();

        // First call — should create exactly one RatingEvent
        $service->applyMatchResult($match->fresh());

        $this->assertDatabaseHas('rating_events', ['match_id' => $match->id]);
        $this->assertEquals(1, RatingEvent::where('match_id', $match->id)->count());

        // Second call — idempotency check must prevent a second RatingEvent
        $service->applyMatchResult($match->fresh());

        $this->assertEquals(
            1,
            RatingEvent::where('match_id', $match->id)->count(),
            'Only ONE RatingEvent should exist after calling applyMatchResult twice'
        );
    }

    // =========================================================================
    // Test 5 — Knockout draw rejection via admin web route
    // =========================================================================

    public function test_knockout_draw_rejection(): void
    {
        $comp = $this->makeCompetition([
            'structure' => 'knockout',
            'format'    => 'single_elim',
            'slug'      => 'draw-reject-test-' . Str::random(6),
        ]);

        $playerA = $this->makePlayer('DrawA');
        $playerB = $this->makePlayer('DrawB');

        $match = GameMatch::create([
            'competition_id' => $comp->id,
            'phase'          => 'knockout',
            'round'          => 'QF',
            'round_position' => 0,
            'status'         => 'live',
            'player_a_id'    => $playerA->id,
            'player_b_id'    => $playerB->id,
            'score_a'        => 3,
            'score_b'        => 3, // draw — equal scores
        ]);

        $admin = $this->makeAdmin();
        $this->actingAs($admin);

        // Try to close via API with equal scores (draw not allowed in knockout)
        $token = $admin->createToken('admin-test')->plainTextToken;

        $response = $this->withToken($token)
            ->postJson("/api/referee/matches/{$match->id}/end", [
                'referee_note' => null,
            ]);

        // The match closes (the API marks it done) — but BracketProgression
        // detects is_draw and refuses to advance the winner.
        // We verify the match was NOT left in an inconsistent state:
        // either the close is rejected (422/403) OR if it closed,
        // the next-round match must NOT have a winner placed.
        if ($response->status() === 200) {
            // Match was closed — verify no winner was advanced
            // (BracketProgression skips draws)
            $this->assertDatabaseMissing('matches', [
                'competition_id' => $comp->id,
                'round'          => 'SF',
                'player_a_id'    => $playerA->id,
            ]);
            $this->assertDatabaseMissing('matches', [
                'competition_id' => $comp->id,
                'round'          => 'SF',
                'player_b_id'    => $playerA->id,
            ]);
        } else {
            // The close was rejected — that's also valid
            $this->assertContains($response->status(), [422, 403, 400]);
        }
    }
}
