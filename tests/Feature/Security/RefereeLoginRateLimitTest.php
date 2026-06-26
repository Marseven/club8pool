<?php

namespace Tests\Feature\Security;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class RefereeLoginRateLimitTest extends TestCase
{
    use RefreshDatabase;

    private function makeReferee(string $name = 'TestRef', string $pin = '1234'): User
    {
        return User::factory()->create([
            'name' => $name,
            'role' => 'referee',
            'pin'  => Hash::make($pin),
        ]);
    }

    // -------------------------------------------------------------------------
    // 1. Valid credentials
    // -------------------------------------------------------------------------

    public function test_referee_can_login_with_valid_credentials(): void
    {
        $referee = $this->makeReferee('Alice', '1234');

        $response = $this->postJson('/api/referee/login', [
            'name' => 'Alice',
            'pin'  => '1234',
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure(['token', 'user' => ['id', 'name']]);
    }

    // -------------------------------------------------------------------------
    // 2. Wrong PIN
    // -------------------------------------------------------------------------

    public function test_referee_login_fails_with_wrong_pin(): void
    {
        $this->makeReferee('Bob', '1234');

        $response = $this->postJson('/api/referee/login', [
            'name' => 'Bob',
            'pin'  => '9999',
        ]);

        $response->assertStatus(401);
    }

    // -------------------------------------------------------------------------
    // 3. Unknown name
    // -------------------------------------------------------------------------

    public function test_referee_login_fails_with_unknown_name(): void
    {
        $response = $this->postJson('/api/referee/login', [
            'name' => 'Nobody',
            'pin'  => '1234',
        ]);

        $response->assertStatus(401);
    }

    // -------------------------------------------------------------------------
    // 4. Rate limiting — 6th attempt returns 429
    //    The login route must be protected by throttle:5,1 (5 requests per
    //    minute per IP). This test INTENTIONALLY FAILS until the middleware
    //    is added to the /api/referee/login route in routes/api.php:
    //
    //      Route::post('/referee/login', [...])
    //          ->middleware('throttle:5,1');
    //
    //    Fix: add ->middleware('throttle:5,1') to the login route definition.
    // -------------------------------------------------------------------------

    public function test_referee_login_is_rate_limited(): void
    {
        // Clear any cached rate-limit counters for this IP so the test is
        // reproducible regardless of run order.
        RateLimiter::clear('api');

        $lastResponse = null;

        for ($i = 1; $i <= 6; $i++) {
            $lastResponse = $this->postJson('/api/referee/login', [
                'name' => 'RateTestUser',
                'pin'  => 'wrongpin',
            ]);
        }

        // The 6th request must be throttled.
        $lastResponse->assertStatus(429);
    }

    // -------------------------------------------------------------------------
    // 5. Admin cannot log in via the referee endpoint
    // -------------------------------------------------------------------------

    public function test_admin_user_cannot_login_as_referee(): void
    {
        User::factory()->create([
            'name' => 'AdminUser',
            'role' => 'admin',
            'pin'  => Hash::make('1234'),
        ]);

        $response = $this->postJson('/api/referee/login', [
            'name' => 'AdminUser',
            'pin'  => '1234',
        ]);

        $response->assertStatus(401);
    }

    // -------------------------------------------------------------------------
    // 6. Protected endpoints require a Sanctum token
    // -------------------------------------------------------------------------

    public function test_api_endpoints_require_sanctum_token(): void
    {
        $response = $this->getJson('/api/referee/me');

        $response->assertStatus(401);
    }

    // -------------------------------------------------------------------------
    // 7. Valid token grants access to protected endpoints
    // -------------------------------------------------------------------------

    public function test_api_endpoints_work_with_valid_token(): void
    {
        $referee = $this->makeReferee('Carol', '5678');
        $token   = $referee->createToken('test')->plainTextToken;

        $response = $this->withToken($token)->getJson('/api/referee/me');

        $response->assertStatus(200);
    }
}
