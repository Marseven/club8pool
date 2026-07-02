<?php

namespace Tests\Feature\Competition;

use App\Models\Competition;
use App\Models\GameMatch;
use App\Models\Pool;
use Database\Seeders\SummerEditionDemoResultsSeeder;
use Database\Seeders\SummerEditionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SummerEditionDemoResultsTest extends TestCase
{
    use RefreshDatabase;

    private function seedBoth(): void
    {
        $this->artisan('db:seed', ['--class' => SummerEditionSeeder::class])->assertSuccessful();
        $this->artisan('db:seed', ['--class' => SummerEditionDemoResultsSeeder::class])->assertSuccessful();
    }

    public function test_pools_a_to_g_are_fully_done(): void
    {
        $this->seedBoth();

        $comp = Competition::where('slug', 'summer-edition')->first();

        foreach (['A', 'B', 'C', 'D', 'E', 'F', 'G'] as $poolName) {
            $pool = Pool::where('competition_id', $comp->id)->where('name', $poolName)->first();
            $this->assertNotNull($pool, "Pool {$poolName} should exist");

            $nonDone = GameMatch::where('pool_id', $pool->id)
                ->where('status', '!=', 'done')
                ->count();

            $this->assertSame(0, $nonDone, "Pool {$poolName} has unfinished matches");
        }
    }

    public function test_pool_h_matches_remain_scheduled(): void
    {
        $this->seedBoth();

        $comp  = Competition::where('slug', 'summer-edition')->first();
        $poolH = Pool::where('competition_id', $comp->id)->where('name', 'H')->first();

        $this->assertNotNull($poolH, 'Pool H should exist');

        $scheduledCount = GameMatch::where('pool_id', $poolH->id)
            ->where('status', 'scheduled')
            ->count();

        $this->assertGreaterThan(0, $scheduledCount, 'Pool H should still have scheduled matches');
    }

    public function test_scores_are_valid_for_race_to_4(): void
    {
        $this->seedBoth();

        $comp       = Competition::where('slug', 'summer-edition')->first();
        $doneMatches = GameMatch::where('competition_id', $comp->id)
            ->where('phase', 'pool')
            ->where('status', 'done')
            ->get();

        foreach ($doneMatches as $m) {
            $this->assertTrue(
                $m->score_a === 4 || $m->score_b === 4,
                "Match #{$m->id}: neither score is 4 ({$m->score_a}/{$m->score_b})"
            );
            $this->assertFalse(
                $m->score_a === 4 && $m->score_b === 4,
                "Match #{$m->id}: both scores are 4"
            );
        }
    }

    public function test_no_knockout_matches_created(): void
    {
        $this->seedBoth();

        $comp    = Competition::where('slug', 'summer-edition')->first();
        $koCount = GameMatch::where('competition_id', $comp->id)
            ->where('phase', 'knockout')
            ->count();

        $this->assertSame(0, $koCount, 'No KO matches should be created');
    }

    public function test_demo_results_seeder_is_idempotent(): void
    {
        $this->seedBoth();
        // Run the demo results seeder a second time
        $this->artisan('db:seed', ['--class' => SummerEditionDemoResultsSeeder::class])->assertSuccessful();

        $comp  = Competition::where('slug', 'summer-edition')->first();
        $poolH = Pool::where('competition_id', $comp->id)->where('name', 'H')->first();

        // Pool H still scheduled after second run
        $scheduledCount = GameMatch::where('pool_id', $poolH->id)
            ->where('status', 'scheduled')
            ->count();

        $this->assertGreaterThan(0, $scheduledCount, 'Pool H should still have scheduled matches after second run');
    }

    public function test_demo_seeder_aborts_gracefully_when_competition_missing(): void
    {
        // Do NOT run SummerEditionSeeder — competition does not exist
        $this->artisan('db:seed', ['--class' => SummerEditionDemoResultsSeeder::class])->assertSuccessful();

        // No matches should exist
        $this->assertSame(0, GameMatch::where('phase', 'pool')->count());
    }

    public function test_pools_a_to_g_have_correct_match_count(): void
    {
        $this->seedBoth();

        $comp = Competition::where('slug', 'summer-edition')->first();

        foreach (['A', 'B', 'C', 'D', 'E', 'F', 'G'] as $poolName) {
            $pool = Pool::where('competition_id', $comp->id)->where('name', $poolName)->first();
            // C(6,2) = 15 matches per pool
            $this->assertSame(
                15,
                GameMatch::where('pool_id', $pool->id)->where('phase', 'pool')->count(),
                "Pool {$poolName} should have exactly 15 matches"
            );
        }
    }

    public function test_done_matches_have_timestamps(): void
    {
        $this->seedBoth();

        $comp        = Competition::where('slug', 'summer-edition')->first();
        $doneMatches = GameMatch::where('competition_id', $comp->id)
            ->where('phase', 'pool')
            ->where('status', 'done')
            ->get();

        foreach ($doneMatches as $m) {
            $this->assertNotNull($m->started_at, "Match #{$m->id} should have started_at");
            $this->assertNotNull($m->ended_at,   "Match #{$m->id} should have ended_at");
        }
    }
}
