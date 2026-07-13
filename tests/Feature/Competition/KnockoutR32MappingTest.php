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
                ['player_id' => $players[1]->id, 'source' => 'D2'],
            ],
            [
                ['player_id' => $players[2]->id, 'source' => 'A2'],
                ['player_id' => $players[3]->id, 'source' => 'D1'],
            ],
        ];

        (new KnockoutGenerator())->generate($comp, $pairs);

        $matches = GameMatch::where('competition_id', $comp->id)
            ->where('phase', 'knockout')
            ->where('status', 'scheduled')
            ->orderBy('round_position')
            ->get();

        $this->assertSame('A1', $matches[0]->player_a_source);
        $this->assertSame('D2', $matches[0]->player_b_source);
        $this->assertSame('A2', $matches[1]->player_a_source);
        $this->assertSame('D1', $matches[1]->player_b_source);
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

        $pairs = [[
            ['player_id' => $players[0]->id],
            ['player_id' => $players[1]->id],
        ]];

        (new KnockoutGenerator())->generate($comp, $pairs);

        $match = GameMatch::where('competition_id', $comp->id)
            ->where('phase', 'knockout')
            ->where('status', 'scheduled')
            ->first();

        $this->assertNull($match->player_a_source);
        $this->assertNull($match->player_b_source);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // SummerEditionSeeder sets knockout_mapping_strategy = STRATEGY_2Q
    // ─────────────────────────────────────────────────────────────────────────

    public function test_seeder_sets_knockout_mapping_strategy(): void
    {
        $this->artisan('db:seed', ['--class' => SummerEditionSeeder::class])->assertSuccessful();

        $comp = Competition::where('slug', 'summer-edition')->first();
        $this->assertNotNull($comp);
        $this->assertSame(PoolKnockoutMappingService::STRATEGY_SE2026, $comp->knockout_mapping_strategy);
    }

    public function test_seeder_is_idempotent_and_keeps_knockout_strategy(): void
    {
        $this->artisan('db:seed', ['--class' => SummerEditionSeeder::class])->assertSuccessful();
        $this->artisan('db:seed', ['--class' => SummerEditionSeeder::class])->assertSuccessful();

        $comp = Competition::where('slug', 'summer-edition')->first();
        $this->assertSame(PoolKnockoutMappingService::STRATEGY_SE2026, $comp->knockout_mapping_strategy);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Full integration: DemoResults + KO generation (2Q strategy → R16, 8 pairs)
    // ─────────────────────────────────────────────────────────────────────────

    public function test_ko_generation_creates_8_r16_matches_with_correct_sources(): void
    {
        $this->artisan('db:seed', ['--class' => SummerEditionSeeder::class])->assertSuccessful();
        $this->artisan('db:seed', ['--class' => SummerEditionDemoResultsSeeder::class])->assertSuccessful();

        $comp = Competition::where('slug', 'summer-edition')->with('pools')->first();

        $generator = new KnockoutGenerator();
        $qualifiers = $generator->qualifiers($comp);

        $pairs = (new PoolKnockoutMappingService())->buildPairs($qualifiers, PoolKnockoutMappingService::STRATEGY_2Q);

        $generator->generate($comp, $pairs);

        $r16 = GameMatch::where('competition_id', $comp->id)
            ->where('phase', 'knockout')
            ->where('round', 'R16')
            ->orderBy('round_position')
            ->get();

        $this->assertCount(8, $r16);

        // Top half: A/D block (positions 0-1)
        $this->assertSame('A1', $r16[0]->player_a_source);
        $this->assertSame('D2', $r16[0]->player_b_source);
        $this->assertSame('A2', $r16[1]->player_a_source);
        $this->assertSame('D1', $r16[1]->player_b_source);

        // Top half: B/C block (positions 2-3)
        $this->assertSame('B1', $r16[2]->player_a_source);
        $this->assertSame('C2', $r16[2]->player_b_source);
        $this->assertSame('B2', $r16[3]->player_a_source);
        $this->assertSame('C1', $r16[3]->player_b_source);

        // Bottom half: E/H block (positions 4-5)
        $this->assertSame('E1', $r16[4]->player_a_source);
        $this->assertSame('H2', $r16[4]->player_b_source);
        $this->assertSame('E2', $r16[5]->player_a_source);
        $this->assertSame('H1', $r16[5]->player_b_source);

        // Bottom half: F/G block (positions 6-7)
        $this->assertSame('F1', $r16[6]->player_a_source);
        $this->assertSame('G2', $r16[6]->player_b_source);
        $this->assertSame('F2', $r16[7]->player_a_source);
        $this->assertSame('G1', $r16[7]->player_b_source);
    }

    public function test_ko_generation_creates_placeholder_rounds_after_r16(): void
    {
        $this->artisan('db:seed', ['--class' => SummerEditionSeeder::class])->assertSuccessful();
        $this->artisan('db:seed', ['--class' => SummerEditionDemoResultsSeeder::class])->assertSuccessful();

        $comp = Competition::where('slug', 'summer-edition')->with('pools')->first();

        $generator = new KnockoutGenerator();
        $pairs = (new PoolKnockoutMappingService())->buildPairs(
            $generator->qualifiers($comp),
            PoolKnockoutMappingService::STRATEGY_2Q
        );
        $generator->generate($comp, $pairs);

        $rounds = GameMatch::where('competition_id', $comp->id)
            ->where('phase', 'knockout')
            ->pluck('round')
            ->unique()
            ->sort()
            ->values()
            ->toArray();

        // 8 pairs → R16 → QF → SF → F
        foreach (['R16', 'QF', 'SF', 'F'] as $expected) {
            $this->assertContains($expected, $rounds);
        }

        // R16: 8 + QF: 4 + SF: 2 + F: 1 = 15 total
        $koTotal = GameMatch::where('competition_id', $comp->id)
            ->where('phase', 'knockout')
            ->whereIn('round', ['R16', 'QF', 'SF', 'F'])
            ->count();
        $this->assertSame(15, $koTotal);
    }

    public function test_all_16_qualifiers_appear_exactly_once_in_r16(): void
    {
        $this->artisan('db:seed', ['--class' => SummerEditionSeeder::class])->assertSuccessful();
        $this->artisan('db:seed', ['--class' => SummerEditionDemoResultsSeeder::class])->assertSuccessful();

        $comp = Competition::where('slug', 'summer-edition')->with('pools')->first();

        $generator = new KnockoutGenerator();
        $pairs = (new PoolKnockoutMappingService())->buildPairs(
            $generator->qualifiers($comp),
            PoolKnockoutMappingService::STRATEGY_2Q
        );
        $generator->generate($comp, $pairs);

        $r16 = GameMatch::where('competition_id', $comp->id)
            ->where('phase', 'knockout')
            ->where('round', 'R16')
            ->get();

        $playerIds = $r16->flatMap(fn ($m) => [$m->player_a_id, $m->player_b_id])
            ->filter()
            ->unique();

        $this->assertCount(16, $playerIds);
    }
}
