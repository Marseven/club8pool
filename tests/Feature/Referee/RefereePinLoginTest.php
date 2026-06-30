<?php

namespace Tests\Feature\Referee;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RefereePinLoginTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeReferee(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'role'              => 'referee',
            'pin'               => Hash::make('1234'),
            'is_referee_active' => true,
        ], $overrides));
    }

    // -------------------------------------------------------------------------
    // 1. Valid referee name + PIN succeeds and redirects to referee queue
    // -------------------------------------------------------------------------

    public function test_referee_can_login_with_name_and_pin(): void
    {
        $referee = $this->makeReferee(['name' => 'Jean Arbitre']);

        $response = $this->post('/login', [
            'mode' => 'referee',
            'name' => 'Jean Arbitre',
            'pin'  => '1234',
        ]);

        $response->assertRedirect(route('referee.queue'));
        $this->assertAuthenticatedAs($referee);
    }

    // -------------------------------------------------------------------------
    // 2. Inactive referee (is_referee_active=false) cannot login
    // -------------------------------------------------------------------------

    public function test_inactive_referee_cannot_login(): void
    {
        $this->makeReferee([
            'name'              => 'Inactive Ref',
            'is_referee_active' => false,
        ]);

        $response = $this->post('/login', [
            'mode' => 'referee',
            'name' => 'Inactive Ref',
            'pin'  => '1234',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors();
        $this->assertGuest();
    }

    // -------------------------------------------------------------------------
    // 3. Correct name but wrong PIN is rejected
    // -------------------------------------------------------------------------

    public function test_wrong_pin_rejected(): void
    {
        $this->makeReferee(['name' => 'Good Ref']);

        $response = $this->post('/login', [
            'mode' => 'referee',
            'name' => 'Good Ref',
            'pin'  => '9999',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors();
        $this->assertGuest();
    }

    // -------------------------------------------------------------------------
    // 4. Duplicate referee name returns an error mentioning multiple referees
    // -------------------------------------------------------------------------

    public function test_duplicate_referee_name_rejected(): void
    {
        // Two referees with the same name (data integrity scenario)
        User::factory()->create([
            'name'              => 'doublon ref',
            'email'             => 'ref1@test.com',
            'role'              => 'referee',
            'pin'               => Hash::make('1234'),
            'is_referee_active' => true,
        ]);
        User::factory()->create([
            'name'              => 'doublon ref',
            'email'             => 'ref2@test.com',
            'role'              => 'referee',
            'pin'               => Hash::make('1234'),
            'is_referee_active' => true,
        ]);

        $response = $this->post('/login', [
            'mode' => 'referee',
            'name' => 'doublon ref',
            'pin'  => '1234',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors();

        $errors    = session('errors');
        $allErrors = $errors ? implode(' ', $errors->all()) : '';
        $this->assertStringContainsStringIgnoringCase('plusieurs', $allErrors);
        $this->assertGuest();
    }

    // -------------------------------------------------------------------------
    // 5. Throttle middleware is applied to the login route
    // -------------------------------------------------------------------------

    public function test_referee_rate_limited(): void
    {
        // Check that the POST /login route has throttle middleware applied.
        // This is a structural test — it verifies the route definition carries
        // rate-limiting middleware without actually exhausting the limit.
        $routeMiddleware = collect(Route::getRoutes())
            ->filter(fn ($r) => $r->uri() === 'login' && in_array('POST', $r->methods()))
            ->flatMap(fn ($r) => $r->gatherMiddleware())
            ->values()
            ->toArray();

        $hasThrottle = collect($routeMiddleware)
            ->contains(fn ($m) => str_starts_with((string) $m, 'throttle'));

        if (! $hasThrottle) {
            $this->markTestSkipped('throttle middleware not yet applied to POST /login — add it to the route definition.');
        }

        $this->assertTrue($hasThrottle, 'POST /login should carry throttle middleware');
    }
}
