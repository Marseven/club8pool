<?php

namespace Tests\Feature\Security;

use App\Models\Competition;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeAdmin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function makeReferee(): User
    {
        return User::factory()->create(['role' => 'referee']);
    }

    /**
     * The admin dashboard calls Competition::firstOrFail(), so a competition
     * must exist in the DB for the route to return 200 rather than 404.
     */
    private function seedCompetition(): Competition
    {
        return Competition::create([
            'name'         => 'Admin Test Cup',
            'slug'         => 'admin-test-' . Str::random(6),
            'discipline'   => '8-ball',
            'format'       => 'single_elim',
            'structure'    => 'knockout',
            'race_to'      => 7,
            'status'       => 'in_progress',
            'shot_clock'   => 30,
            'player_slots' => 16,
        ]);
    }

    // -------------------------------------------------------------------------
    // 1. Admin can access admin dashboard
    //    The DashboardController calls Competition::firstOrFail(), so the test
    //    must seed one competition to avoid a model-not-found 404.
    // -------------------------------------------------------------------------

    public function test_admin_can_access_admin_dashboard(): void
    {
        $this->seedCompetition();
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
             ->get('/admin')
             ->assertStatus(200);
    }

    // -------------------------------------------------------------------------
    // 2. Guest is redirected to /login
    // -------------------------------------------------------------------------

    public function test_guest_cannot_access_admin_dashboard(): void
    {
        $this->get('/admin')
             ->assertRedirect('/login');
    }

    // -------------------------------------------------------------------------
    // 3. Referee is redirected away from admin dashboard
    //    EnsureAdmin redirects to /login for any non-admin user.
    // -------------------------------------------------------------------------

    public function test_referee_cannot_access_admin_dashboard(): void
    {
        $referee = $this->makeReferee();

        $this->actingAs($referee)
             ->get('/admin')
             ->assertRedirect('/login');
    }

    // -------------------------------------------------------------------------
    // 4. Admin can list competitions
    // -------------------------------------------------------------------------

    public function test_admin_can_access_competitions(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
             ->get('/admin/competitions')
             ->assertStatus(200);
    }

    // -------------------------------------------------------------------------
    // 5. Referee cannot access admin competitions list
    // -------------------------------------------------------------------------

    public function test_referee_cannot_access_competitions(): void
    {
        $referee = $this->makeReferee();

        $this->actingAs($referee)
             ->get('/admin/competitions')
             ->assertRedirect('/login');
    }

    // -------------------------------------------------------------------------
    // 6. Referee can access their own queue
    // -------------------------------------------------------------------------

    public function test_referee_can_access_referee_queue(): void
    {
        $referee = $this->makeReferee();

        $this->actingAs($referee)
             ->get('/arbitre')
             ->assertStatus(200);
    }

    // -------------------------------------------------------------------------
    // 7. Admin is redirected away from referee queue (EnsureReferee blocks them)
    // -------------------------------------------------------------------------

    public function test_admin_cannot_access_referee_queue(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
             ->get('/arbitre')
             ->assertRedirect('/login');
    }
}
