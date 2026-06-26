<?php

namespace Tests\Feature\Services;

use App\Models\Competition;
use App\Models\Player;
use App\Models\PlayerRating;
use App\Models\Registration;
use App\Services\SeedingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SeedingServiceTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeCompetition(array $overrides = []): Competition
    {
        return Competition::create(array_merge([
            'name'         => 'Seeding Test Cup',
            'slug'         => 'seeding-test-' . Str::random(6),
            'discipline'   => '8-ball',
            'format'       => 'pools',
            'structure'    => 'pools_knockout',
            'race_to'      => 7,
            'status'       => 'in_progress',
            'shot_clock'   => 30,
            'player_slots' => 16,
            'seed_strategy' => 'random',
        ], $overrides));
    }

    private function makePlayer(string $first, string $last = 'Test', int $rating = 1500): Player
    {
        return Player::create([
            'first_name' => $first,
            'last_name'  => $last,
            'rating'     => $rating,
        ]);
    }

    private function makePlayerEntry(Player $player, Competition $competition, array $regOverrides = []): array
    {
        Registration::create(array_merge([
            'competition_id' => $competition->id,
            'player_id'      => $player->id,
            'status'         => 'confirmed',
        ], $regOverrides));

        return [
            'player_id'  => $player->id,
            'player'     => $player,
            'pool_name'  => 'A',
            'pool_slot'  => 1,
            'name'       => trim($player->first_name . ' ' . $player->last_name),
        ];
    }

    /** Build a pool-keyed qualifiers array from flat entries. */
    private function qualifiers(array ...$pools): array
    {
        $result = [];
        foreach ($pools as $idx => $entries) {
            $poolName = chr(ord('A') + $idx);
            foreach ($entries as &$entry) {
                $entry['pool_name'] = $poolName;
            }
            $result[$poolName] = $entries;
        }
        return $result;
    }

    private function service(): SeedingService
    {
        return new SeedingService();
    }

    // -------------------------------------------------------------------------
    // 1. Random strategy returns all qualifiers
    // -------------------------------------------------------------------------

    public function test_random_strategy_returns_all_qualifiers(): void
    {
        $comp = $this->makeCompetition(['seed_strategy' => 'random']);

        $players = [];
        for ($i = 1; $i <= 4; $i++) {
            $p = $this->makePlayer("Player{$i}");
            $players[] = $this->makePlayerEntry($p, $comp);
        }

        $qualifiers = $this->qualifiers($players);

        $result = $this->service()->orderQualifiers($comp, $qualifiers);

        $this->assertCount(4, $result);

        $inputIds = array_column($players, 'player_id');
        $resultIds = array_column($result, 'player_id');

        sort($inputIds);
        sort($resultIds);
        $this->assertEquals($inputIds, $resultIds, 'All players from input must appear in the result.');
    }

    // -------------------------------------------------------------------------
    // 2. Rating strategy sorts by rating descending
    // -------------------------------------------------------------------------

    public function test_rating_strategy_sorts_by_rating_descending(): void
    {
        $comp = $this->makeCompetition([
            'seed_strategy' => 'rating',
            'discipline'    => '8-ball',
        ]);

        $strong = $this->makePlayer('Strong', 'Player', 2000);
        $mid    = $this->makePlayer('Mid',    'Player', 1500);
        $weak   = $this->makePlayer('Weak',   'Player', 1000);

        // Create player_ratings entries so the service picks them up.
        PlayerRating::create([
            'player_id'   => $strong->id,
            'discipline'  => '8-ball',
            'rating'      => 2000,
        ]);
        PlayerRating::create([
            'player_id'   => $mid->id,
            'discipline'  => '8-ball',
            'rating'      => 1500,
        ]);
        PlayerRating::create([
            'player_id'   => $weak->id,
            'discipline'  => '8-ball',
            'rating'      => 1000,
        ]);

        $entries = [
            $this->makePlayerEntry($mid,    $comp),
            $this->makePlayerEntry($weak,   $comp),
            $this->makePlayerEntry($strong, $comp),
        ];

        $qualifiers = $this->qualifiers($entries);

        $result = $this->service()->orderQualifiers($comp, $qualifiers);

        $this->assertCount(3, $result);
        // Seed 1 (index 0) must be the strongest player.
        $this->assertEquals($strong->id, $result[0]['player_id'], 'Highest rated player should be first (seed 1).');
        $this->assertEquals($mid->id,    $result[1]['player_id']);
        $this->assertEquals($weak->id,   $result[2]['player_id'], 'Lowest rated player should be last.');
    }

    // -------------------------------------------------------------------------
    // 3. Manual strategy respects seed_rating
    // -------------------------------------------------------------------------

    public function test_manual_strategy_respects_seed_rating(): void
    {
        $comp = $this->makeCompetition(['seed_strategy' => 'manual']);

        $p1 = $this->makePlayer('First');  // seed_rating = 1
        $p2 = $this->makePlayer('Second'); // seed_rating = 2
        $p3 = $this->makePlayer('Third');  // seed_rating = 3

        // Create registrations with seed_rating values, inserted in reverse order.
        $entries = [
            $this->makePlayerEntry($p3, $comp, ['seed_rating' => 3]),
            $this->makePlayerEntry($p2, $comp, ['seed_rating' => 2]),
            $this->makePlayerEntry($p1, $comp, ['seed_rating' => 1]),
        ];

        $qualifiers = $this->qualifiers($entries);

        $result = $this->service()->orderQualifiers($comp, $qualifiers);

        $this->assertCount(3, $result);
        $this->assertEquals($p1->id, $result[0]['player_id'], 'Player with seed_rating=1 should be first.');
        $this->assertEquals($p2->id, $result[1]['player_id'], 'Player with seed_rating=2 should be second.');
        $this->assertEquals($p3->id, $result[2]['player_id'], 'Player with seed_rating=3 should be last.');
    }

    // -------------------------------------------------------------------------
    // 4. Hybrid strategy seeds top N by rating
    // -------------------------------------------------------------------------

    public function test_hybrid_strategy_seeds_top_n_by_rating(): void
    {
        $comp = $this->makeCompetition([
            'seed_strategy'          => 'hybrid',
            'discipline'             => '8-ball',
            'seeded_players_count'   => 2,
            'draw_randomize_unseeded' => false, // keep unseeded order stable
        ]);

        $top1   = $this->makePlayer('Top1',   'Player', 2000);
        $top2   = $this->makePlayer('Top2',   'Player', 1800);
        $unseed1 = $this->makePlayer('Un1',   'Player', 1200);
        $unseed2 = $this->makePlayer('Un2',   'Player', 1000);

        PlayerRating::create(['player_id' => $top1->id,   'discipline' => '8-ball', 'rating' => 2000]);
        PlayerRating::create(['player_id' => $top2->id,   'discipline' => '8-ball', 'rating' => 1800]);
        PlayerRating::create(['player_id' => $unseed1->id, 'discipline' => '8-ball', 'rating' => 1200]);
        PlayerRating::create(['player_id' => $unseed2->id, 'discipline' => '8-ball', 'rating' => 1000]);

        // Insert in a scrambled order to test that seeding sorts them correctly.
        $entries = [
            $this->makePlayerEntry($unseed2, $comp),
            $this->makePlayerEntry($top2,    $comp),
            $this->makePlayerEntry($unseed1, $comp),
            $this->makePlayerEntry($top1,    $comp),
        ];

        $qualifiers = $this->qualifiers($entries);

        $result = $this->service()->orderQualifiers($comp, $qualifiers);

        $this->assertCount(4, $result);

        // Top 2 seeded positions must be the two highest-rated players, in order.
        $this->assertEquals($top1->id,   $result[0]['player_id'], 'Seed 1 must be the highest-rated player.');
        $this->assertEquals($top2->id,   $result[1]['player_id'], 'Seed 2 must be the second-highest-rated player.');

        // The remaining two positions must contain both unseeded players.
        $unseededResultIds = [$result[2]['player_id'], $result[3]['player_id']];
        $this->assertContains($unseed1->id, $unseededResultIds);
        $this->assertContains($unseed2->id, $unseededResultIds);
    }

    // -------------------------------------------------------------------------
    // 5. Hybrid with zero seeded players is equivalent to random (all returned)
    // -------------------------------------------------------------------------

    public function test_hybrid_strategy_with_zero_seeded_players_is_equivalent_to_random(): void
    {
        $comp = $this->makeCompetition([
            'seed_strategy'        => 'hybrid',
            'seeded_players_count' => 0,
        ]);

        $players = [];
        for ($i = 1; $i <= 6; $i++) {
            $p = $this->makePlayer("P{$i}");
            $players[] = $this->makePlayerEntry($p, $comp);
        }

        $qualifiers = $this->qualifiers($players);

        $result = $this->service()->orderQualifiers($comp, $qualifiers);

        $this->assertCount(6, $result, 'All 6 players must be returned even when seeded_players_count=0.');

        $inputIds  = array_column($players, 'player_id');
        $resultIds = array_column($result,  'player_id');

        sort($inputIds);
        sort($resultIds);
        $this->assertEquals($inputIds, $resultIds, 'The result must contain exactly the same players as the input.');
    }
}
