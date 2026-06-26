<?php

namespace Tests\Feature\Services;

use App\Models\Competition;
use App\Models\GameMatch;
use App\Models\Player;
use App\Models\PlayerRating;
use App\Models\Pool;
use App\Models\Registration;
use App\Models\User;
use App\Services\KnockoutGenerator;
use App\Services\SeedingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SeedingIntegrationTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeCompetition(array $overrides = []): Competition
    {
        return Competition::create(array_merge([
            'name'                => 'Seeding Integration Cup',
            'slug'                => 'seed-int-' . Str::random(6),
            'discipline'          => '8-ball',
            'format'              => 'pools',
            'structure'           => 'pools_knockout',
            'race_to'             => 7,
            'pool_race_to'        => 7,
            'knockout_race_to'    => 7,
            'status'              => 'in_progress',
            'shot_clock'          => 30,
            'player_slots'        => 8,
            'pool_count'          => 2,
            'pool_size'           => 4,
            'qualifiers_per_pool' => 2,
            'seed_strategy'       => 'random',
        ], $overrides));
    }

    private function makePlayer(string $first, int $rating = 1500): Player
    {
        return Player::create([
            'first_name' => $first,
            'last_name'  => 'Test',
            'rating'     => $rating,
        ]);
    }

    private function makePool(Competition $comp, string $name, int $position): Pool
    {
        return Pool::create([
            'competition_id' => $comp->id,
            'name'           => $name,
            'position'       => $position,
            'size'           => 4,
        ]);
    }

    private function registerAndRate(Competition $comp, Pool $pool, Player $player, int $slot, int $eloRating, int $poolSlot): void
    {
        Registration::create([
            'competition_id' => $comp->id,
            'pool_id'        => $pool->id,
            'pool_slot'      => $poolSlot,
            'player_id'      => $player->id,
            'status'         => 'confirmed',
        ]);

        PlayerRating::create([
            'player_id'  => $player->id,
            'discipline' => '8-ball',
            'rating'     => $eloRating,
        ]);
    }

    /** Build the pool-keyed qualifier entry array expected by SeedingService. */
    private function buildQualifierEntry(Player $player, Pool $pool, int $v = 2): array
    {
        return [
            'player_id' => $player->id,
            'player'    => $player,
            'pool_name' => $pool->name,
            'pool_slot' => 1,
            'name'      => trim($player->first_name . ' ' . $player->last_name),
            'v'         => $v,
            'w'         => $v * 3,
            'l'         => 0,
            'diff'      => $v * 3,
            'rank'      => 1,
        ];
    }

    // -------------------------------------------------------------------------
    // 1. rating strategy: first match has highest-rated player
    // -------------------------------------------------------------------------

    public function test_rating_strategy_affects_bracket_pair_order(): void
    {
        $comp  = $this->makeCompetition(['seed_strategy' => 'rating', 'discipline' => '8-ball']);
        $poolA = $this->makePool($comp, 'A', 0);
        $poolB = $this->makePool($comp, 'B', 1);

        // Pool A qualifiers: strong (2000) and weak (1000)
        $strong = $this->makePlayer('Strong', 2000);
        $weak   = $this->makePlayer('Weak',   1000);
        $this->registerAndRate($comp, $poolA, $strong, 0, 2000, 1);
        $this->registerAndRate($comp, $poolA, $weak,   1, 1000, 2);

        // Pool B qualifiers: mid-high (1800) and mid-low (1200)
        $midHigh = $this->makePlayer('MidHigh', 1800);
        $midLow  = $this->makePlayer('MidLow',  1200);
        $this->registerAndRate($comp, $poolB, $midHigh, 2, 1800, 1);
        $this->registerAndRate($comp, $poolB, $midLow,  3, 1200, 2);

        $qualifiers = [
            'A' => [$this->buildQualifierEntry($strong, $poolA), $this->buildQualifierEntry($weak, $poolA)],
            'B' => [$this->buildQualifierEntry($midHigh, $poolB), $this->buildQualifierEntry($midLow, $poolB)],
        ];

        $seeder     = new SeedingService();
        $orderedFlat = $seeder->orderQualifiers($comp, $qualifiers);

        // After rating strategy, the first position must be the highest-rated player
        $this->assertEquals(
            $strong->id,
            $orderedFlat[0]['player_id'],
            'Rating strategy must place the highest-rated player first (seed 1)'
        );

        // Generate bracket and verify seed 1 is in position 0 of the first match
        $generator = new KnockoutGenerator();
        $pairs = $generator->pairsFromFlat($orderedFlat);
        $generator->generate($comp, $pairs);

        // Query specifically for first-round matches that have players assigned
        // (placeholder final/SF rounds are pending without player_a_id).
        $firstMatch = GameMatch::where('competition_id', $comp->id)
            ->where('phase', 'knockout')
            ->whereNotNull('player_a_id')
            ->orderBy('round_position')
            ->first();

        $this->assertEquals(
            $strong->id,
            $firstMatch->player_a_id,
            'After KnockoutGenerator::generate(), the highest-rated player must appear in the first bracket slot'
        );
    }

    // -------------------------------------------------------------------------
    // 2. hybrid strategy: top N seeded players get positions 0 and 1
    // -------------------------------------------------------------------------

    public function test_hybrid_strategy_seeded_players_get_first_positions(): void
    {
        $comp  = $this->makeCompetition([
            'seed_strategy'           => 'hybrid',
            'discipline'              => '8-ball',
            'seeded_players_count'    => 2,
            'draw_randomize_unseeded' => false,
        ]);
        $poolA = $this->makePool($comp, 'A', 0);
        $poolB = $this->makePool($comp, 'B', 1);

        $top1   = $this->makePlayer('Seed1', 2000);
        $top2   = $this->makePlayer('Seed2', 1800);
        $unseed1 = $this->makePlayer('Un1',  1200);
        $unseed2 = $this->makePlayer('Un2',  1000);

        $this->registerAndRate($comp, $poolA, $top1,    0, 2000, 1);
        $this->registerAndRate($comp, $poolA, $unseed1, 1, 1200, 2);
        $this->registerAndRate($comp, $poolB, $top2,    2, 1800, 1);
        $this->registerAndRate($comp, $poolB, $unseed2, 3, 1000, 2);

        $qualifiers = [
            'A' => [$this->buildQualifierEntry($top1, $poolA), $this->buildQualifierEntry($unseed1, $poolA)],
            'B' => [$this->buildQualifierEntry($top2, $poolB), $this->buildQualifierEntry($unseed2, $poolB)],
        ];

        $seeder     = new SeedingService();
        $orderedFlat = $seeder->orderQualifiers($comp, $qualifiers);

        $this->assertCount(4, $orderedFlat);

        // The two seeded players must occupy positions 0 and 1
        $seededPositionIds = [$orderedFlat[0]['player_id'], $orderedFlat[1]['player_id']];

        $this->assertContains($top1->id, $seededPositionIds, 'top1 (highest rated) must be in position 0 or 1');
        $this->assertContains($top2->id, $seededPositionIds, 'top2 (second highest) must be in position 0 or 1');

        // Verify ordering: top1 first, top2 second
        $this->assertEquals($top1->id, $orderedFlat[0]['player_id'], 'top1 must be seed 1 (position 0)');
        $this->assertEquals($top2->id, $orderedFlat[1]['player_id'], 'top2 must be seed 2 (position 1)');
    }

    // -------------------------------------------------------------------------
    // 3. random strategy preserves all qualifiers (no loss, no duplicates)
    // -------------------------------------------------------------------------

    public function test_random_strategy_preserves_all_qualifiers(): void
    {
        $comp  = $this->makeCompetition(['seed_strategy' => 'random']);
        $poolA = $this->makePool($comp, 'A', 0);
        $poolB = $this->makePool($comp, 'B', 1);

        $players = [];
        for ($i = 1; $i <= 4; $i++) {
            $players[$i] = $this->makePlayer("P{$i}", 1500);
        }

        $this->registerAndRate($comp, $poolA, $players[1], 0, 1500, 1);
        $this->registerAndRate($comp, $poolA, $players[2], 1, 1500, 2);
        $this->registerAndRate($comp, $poolB, $players[3], 2, 1500, 1);
        $this->registerAndRate($comp, $poolB, $players[4], 3, 1500, 2);

        $qualifiers = [
            'A' => [
                $this->buildQualifierEntry($players[1], $poolA),
                $this->buildQualifierEntry($players[2], $poolA),
            ],
            'B' => [
                $this->buildQualifierEntry($players[3], $poolB),
                $this->buildQualifierEntry($players[4], $poolB),
            ],
        ];

        $seeder     = new SeedingService();
        $orderedFlat = $seeder->orderQualifiers($comp, $qualifiers);

        // All 4 qualifiers must still be present
        $this->assertCount(4, $orderedFlat, 'random strategy must preserve all 4 qualifiers');

        $inputIds  = array_map(fn ($p) => $p->id, $players);
        $resultIds = array_column($orderedFlat, 'player_id');

        sort($inputIds);
        sort($resultIds);

        $this->assertEquals($inputIds, $resultIds, 'random strategy must not lose or duplicate any qualifier');
    }

    // -------------------------------------------------------------------------
    // 4. KnockoutController::show() renders with pairs key in Inertia response
    // -------------------------------------------------------------------------

    public function test_seeding_service_is_called_in_knockout_controller(): void
    {
        // The KnockoutController::show() calls SeedingService internally.
        // We verify the Inertia page renders with a 'pairs' prop.
        $admin = User::factory()->create(['role' => 'admin']);

        // Competition must exist for Competition::with('pools')->firstOrFail()
        $comp = $this->makeCompetition();

        $response = $this->actingAs($admin)
                         ->get('/admin/phase-finale');

        $response->assertStatus(200);

        // Inertia returns component props as JSON in the page — 'pairs' must be present
        $content = $response->getContent();
        $this->assertStringContainsString('"pairs"', $content, 'KnockoutController::show() must pass pairs to the Inertia view');
    }
}
