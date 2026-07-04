<?php

namespace Tests\Feature\Competition;

use App\Models\Competition;
use App\Models\GameMatch;
use App\Models\Player;
use App\Models\Pool;
use App\Models\Registration;
use Database\Seeders\SummerEditionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SummerEditionInitialStateTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_competition_with_correct_status(): void
    {
        $this->artisan('db:seed', ['--class' => SummerEditionSeeder::class])->assertSuccessful();

        $comp = Competition::where('slug', 'summer-edition')->first();
        $this->assertNotNull($comp);
        $this->assertSame('in_progress', $comp->status);
    }

    public function test_seeder_creates_eight_pools(): void
    {
        $this->artisan('db:seed', ['--class' => SummerEditionSeeder::class])->assertSuccessful();

        $comp = Competition::where('slug', 'summer-edition')->first();
        $this->assertSame(8, $comp->pools()->count());
    }

    public function test_seeder_creates_40_registrations(): void
    {
        $this->artisan('db:seed', ['--class' => SummerEditionSeeder::class])->assertSuccessful();

        $comp = Competition::where('slug', 'summer-edition')->first();
        $this->assertSame(40, $comp->registrations()->count());
    }

    public function test_seeder_generates_pool_matches(): void
    {
        $this->artisan('db:seed', ['--class' => SummerEditionSeeder::class])->assertSuccessful();

        $comp = Competition::where('slug', 'summer-edition')->first();
        // 8 pools × C(5,2) = 8 × 10 = 80 matches
        $poolMatches = GameMatch::where('competition_id', $comp->id)
            ->where('phase', 'pool')
            ->count();

        $this->assertSame(80, $poolMatches);
    }

    public function test_seeder_does_not_create_knockout_matches(): void
    {
        $this->artisan('db:seed', ['--class' => SummerEditionSeeder::class])->assertSuccessful();

        $comp = Competition::where('slug', 'summer-edition')->first();
        $koMatches = GameMatch::where('competition_id', $comp->id)
            ->where('phase', 'knockout')
            ->count();

        $this->assertSame(0, $koMatches);
    }

    public function test_seeder_is_idempotent(): void
    {
        $this->artisan('db:seed', ['--class' => SummerEditionSeeder::class])->assertSuccessful();
        $this->artisan('db:seed', ['--class' => SummerEditionSeeder::class])->assertSuccessful();

        $comp = Competition::where('slug', 'summer-edition')->first();

        $this->assertSame(1, Competition::where('slug', 'summer-edition')->count());
        $this->assertSame(8, $comp->pools()->count());
        $this->assertSame(40, $comp->registrations()->count());
        // Pool matches should NOT double (guard prevents regeneration)
        $this->assertSame(
            80,
            GameMatch::where('competition_id', $comp->id)->where('phase', 'pool')->count()
        );
    }

    public function test_players_have_login_accounts(): void
    {
        $this->artisan('db:seed', ['--class' => SummerEditionSeeder::class])->assertSuccessful();

        $comp       = Competition::where('slug', 'summer-edition')->first();
        $playerIds  = $comp->registrations()->pluck('player_id');
        $withAccounts = Player::whereIn('id', $playerIds)
            ->whereNotNull('password')
            ->count();

        $this->assertSame(40, $withAccounts);
    }

    public function test_each_pool_has_exactly_five_players(): void
    {
        $this->artisan('db:seed', ['--class' => SummerEditionSeeder::class])->assertSuccessful();

        $comp = Competition::where('slug', 'summer-edition')->first();

        foreach (['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'] as $poolName) {
            $pool = Pool::where('competition_id', $comp->id)->where('name', $poolName)->first();
            $this->assertNotNull($pool, "Pool {$poolName} not found");
            $this->assertSame(5, $pool->registrations()->count(), "Pool {$poolName} should have 5 registrations");
        }
    }

    public function test_all_registrations_are_confirmed(): void
    {
        $this->artisan('db:seed', ['--class' => SummerEditionSeeder::class])->assertSuccessful();

        $comp = Competition::where('slug', 'summer-edition')->first();
        $pendingCount = $comp->registrations()->where('status', '!=', 'confirmed')->count();

        $this->assertSame(0, $pendingCount, 'All registrations should be confirmed');
    }

    public function test_seeds_are_correctly_assigned(): void
    {
        $this->artisan('db:seed', ['--class' => SummerEditionSeeder::class])->assertSuccessful();

        $comp = Competition::where('slug', 'summer-edition')->first();

        // 8 seeded registrations (one per pool, slots 1)
        $seededCount = $comp->registrations()->whereNotNull('seed')->count();
        $this->assertSame(8, $seededCount);

        // Seeds numbered 1–8
        for ($s = 1; $s <= 8; $s++) {
            $this->assertSame(
                1,
                $comp->registrations()->where('seed', $s)->count(),
                "Seed #{$s} should appear exactly once"
            );
        }
    }

    public function test_pool_matches_are_all_scheduled(): void
    {
        $this->artisan('db:seed', ['--class' => SummerEditionSeeder::class])->assertSuccessful();

        $comp = Competition::where('slug', 'summer-edition')->first();
        $nonScheduled = GameMatch::where('competition_id', $comp->id)
            ->where('phase', 'pool')
            ->where('status', '!=', 'scheduled')
            ->count();

        $this->assertSame(0, $nonScheduled, 'All freshly generated pool matches should be scheduled');
    }
}
