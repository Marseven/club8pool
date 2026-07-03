<?php

namespace Tests\Feature\Competition;

use App\Models\Competition;
use App\Models\GameMatch;
use App\Models\Player;
use App\Services\KnockoutGenerator;
use App\Services\PoolKnockoutMappingService;
use Database\Seeders\SummerEditionDemoResultsSeeder;
use Database\Seeders\SummerEditionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KnockoutR32MappingTest extends TestCase
{
    use RefreshDatabase;

    // ─────────────────────────────────────────────────────────────────────────
    // Source label storage in KnockoutGenerator
    // ─────────────────────────────────────────────────────────────────────────

    public function test_generate_stores_player_a_source_and_player_b_source(): void
    {
        $comp = Competition::create([
            'name'   => 'Src Test',
            'slug'   => 'src-test',
            'status' => 'in_progress',
        ]);

        $players = collect(range(1, 4))->map(fn ($i) => Player::create([
            'first_name' => 'P',
            'last_name'  => (string) $i,
            'rating'     => 1000,
            'wins'       => 0,
            'losses'     => 0,
        ]));

        $pairs = [
            [
                ['player_id' => $players[0]->id, 'source' => 'A1'],
                ['player_id' => $players[1]->id, 'source' => 'C4'],
            ],
            [
                ['player_id' => $players[2]->id, 'source' => 'B1'],
                ['player_id' => $players[3]->id, 'source' => 'D4'],
            ],
        ];

        (new KnockoutGenerator())->generate($comp, $pairs);

        $matches = GameMatch::where('competition_id', $comp->id)
            ->where('phase', 'knockout')
            ->where('status', 'scheduled')
            ->orderBy('round_position')
            ->get();

        $this->assertSame('A1', $matches[0]->player_a_source);
        $this->assertSame('C4', $matches[0]->player_b_source);
        $this->assertSame('B1', $matches[1]->player_a_source);
        $this->assertSame('D4', $matches[1]->player_b_source);
    }

    public function test_generate_stores_null_source_when_not_provided(): void
    {
        $comp = Competition::create([
            'name'   => 'No Src Test',
            'slug'   => 'no-src-test',
            'status' => 'in_progress',
        ]);

        $players = collect(range(1, 2))->map(fn ($i) => Player::create([
            'first_name' => 'P',
            'last_name'  => (string) $i,
            'rating'     => 1000,
            'wins'       => 0,
            'losses'     => 0,
        ]));

        // Pairs without 'source' key
        $pairs = [
            [
                ['player_id' => $players[0]->id],
                ['player_id' => $players[1]->id],
            ],
        ];

        (new KnockoutGenerator())->generate($comp, $pairs);

        $match = GameMatch::where('competition_id', $comp->id)
            ->where('phase', 'knockout')
            ->where('round_position', 0)
            ->first();

        $this->assertNull($match->player_a_source);
        $this->assertNull($match->player_b_source);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // SummerEditionSeeder sets knockout_mapping_strategy
    // ─────────────────────────────────────────────────────────────────────────

    public function test_seeder_sets_knockout_mapping_strategy(): void
    {
        $this->artisan('db:seed', ['--class' => SummerEditionSeeder::class])->assertSuccessful();

        $comp = Competition::where('slug', 'summer-edition')->first();
        $this->assertNotNull($comp);
        $this->assertSame(PoolKnockoutMappingService::STRATEGY, $comp->knockout_mapping_strategy);
    }

    public function test_seeder_is_idempotent_and_keeps_knockout_strategy(): void
    {
        $this->artisan('db:seed', ['--class' => SummerEditionSeeder::class])->assertSuccessful();
        $this->artisan('db:seed', ['--class' => SummerEditionSeeder::class])->assertSuccessful();

        $comp = Competition::where('slug', 'summer-edition')->first();
        $this->assertSame(PoolKnockoutMappingService::STRATEGY, $comp->knockout_mapping_strategy);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Full integration: DemoResults + KO generation with correct positions
    // ─────────────────────────────────────────────────────────────────────────

    public function test_ko_generation_creates_16_r32_matches_with_correct_sources(): void
    {
        $this->artisan('db:seed', ['--class' => SummerEditionSeeder::class])->assertSuccessful();
        $this->artisan('db:seed', ['--class' => SummerEditionDemoResultsSeeder::class])->assertSuccessful();

        $comp = Competition::where('slug', 'summer-edition')->with('pools')->first();

        $generator = new KnockoutGenerator();
        $qualifiers = $generator->qualifiers($comp);

        $service = new PoolKnockoutMappingService();
        $pairs = $service->buildPairs($qualifiers);

        $generator->generate($comp, $pairs);

        $r32 = GameMatch::where('competition_id', $comp->id)
            ->where('phase', 'knockout')
            ->where('round', 'R32')
            ->orderBy('round_position')
            ->get();

        $this->assertCount(16, $r32);

        // Top half: A/C block (positions 0-3)
        $this->assertSame('A1', $r32[0]->player_a_source);
        $this->assertSame('C4', $r32[0]->player_b_source);
        $this->assertSame('A2', $r32[1]->player_a_source);
        $this->assertSame('C3', $r32[1]->player_b_source);
        $this->assertSame('A3', $r32[2]->player_a_source);
        $this->assertSame('C2', $r32[2]->player_b_source);
        $this->assertSame('A4', $r32[3]->player_a_source);
        $this->assertSame('C1', $r32[3]->player_b_source);

        // Top half: B/D block (positions 4-7)
        $this->assertSame('B1', $r32[4]->player_a_source);
        $this->assertSame('D4', $r32[4]->player_b_source);
        $this->assertSame('B4', $r32[7]->player_a_source);
        $this->assertSame('D1', $r32[7]->player_b_source);

        // Bottom half: E/G block (positions 8-11)
        $this->assertSame('E1', $r32[8]->player_a_source);
        $this->assertSame('G4', $r32[8]->player_b_source);
        $this->assertSame('E4', $r32[11]->player_a_source);
        $this->assertSame('G1', $r32[11]->player_b_source);

        // Bottom half: F/H block (positions 12-15)
        $this->assertSame('F1', $r32[12]->player_a_source);
        $this->assertSame('H4', $r32[12]->player_b_source);
        $this->assertSame('F4', $r32[15]->player_a_source);
        $this->assertSame('H1', $r32[15]->player_b_source);
    }

    public function test_ko_generation_creates_placeholder_rounds_after_r32(): void
    {
        $this->artisan('db:seed', ['--class' => SummerEditionSeeder::class])->assertSuccessful();
        $this->artisan('db:seed', ['--class' => SummerEditionDemoResultsSeeder::class])->assertSuccessful();

        $comp = Competition::where('slug', 'summer-edition')->with('pools')->first();

        $generator = new KnockoutGenerator();
        $pairs = (new PoolKnockoutMappingService())->buildPairs($generator->qualifiers($comp));
        $generator->generate($comp, $pairs);

        $rounds = GameMatch::where('competition_id', $comp->id)
            ->where('phase', 'knockout')
            ->pluck('round')
            ->unique()
            ->sort()
            ->values()
            ->toArray();

        // Should have R32, R16, QF, SF, F
        foreach (['R32', 'R16', 'QF', 'SF', 'F'] as $expected) {
            $this->assertContains($expected, $rounds);
        }

        // R32: 16, R16: 8, QF: 4, SF: 2, F: 1 = 31 total (+ optional 3P)
        $koTotal = GameMatch::where('competition_id', $comp->id)
            ->where('phase', 'knockout')
            ->whereIn('round', ['R32', 'R16', 'QF', 'SF', 'F'])
            ->count();
        $this->assertSame(31, $koTotal);
    }

    public function test_all_32_qualifiers_appear_exactly_once_in_r32(): void
    {
        $this->artisan('db:seed', ['--class' => SummerEditionSeeder::class])->assertSuccessful();
        $this->artisan('db:seed', ['--class' => SummerEditionDemoResultsSeeder::class])->assertSuccessful();

        $comp = Competition::where('slug', 'summer-edition')->with('pools')->first();

        $generator = new KnockoutGenerator();
        $pairs = (new PoolKnockoutMappingService())->buildPairs($generator->qualifiers($comp));
        $generator->generate($comp, $pairs);

        $r32 = GameMatch::where('competition_id', $comp->id)
            ->where('phase', 'knockout')
            ->where('round', 'R32')
            ->get();

        $playerIds = $r32->flatMap(fn ($m) => [$m->player_a_id, $m->player_b_id])
            ->filter()
            ->unique();

        $this->assertCount(32, $playerIds);
    }
}
