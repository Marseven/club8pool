<?php

namespace Tests\Feature\Api;

use App\Models\Competition;
use App\Models\GameMatch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RefereeApiAuthTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeReferee(string $name = 'TestRef'): User
    {
        return User::factory()->create([
            'name' => $name,
            'role' => 'referee',
            'pin'  => Hash::make('1234'),
        ]);
    }

    private function makeCompetition(): Competition
    {
        return Competition::create([
            'name'         => 'Test Open',
            'slug'         => 'test-open-' . Str::random(6),
            'discipline'   => '8-ball',
            'format'       => 'single_elim',
            'structure'    => 'knockout',
            'race_to'      => 7,
            'status'       => 'draft',
            'shot_clock'   => 30,
            'player_slots' => 16,
        ]);
    }

    private function makeMatch(Competition $comp, array $overrides = []): GameMatch
    {
        return GameMatch::create(array_merge([
            'competition_id' => $comp->id,
            'round'          => 'QF',
            'round_position' => 0,
            'phase'          => 'knockout',
            'status'         => 'scheduled',
        ], $overrides));
    }

    // -------------------------------------------------------------------------
    // 1. Login returns token
    // -------------------------------------------------------------------------

    public function test_login_returns_token(): void
    {
        $referee = $this->makeReferee('Alice');

        $response = $this->postJson('/api/referee/login', [
            'name' => 'Alice',
            'pin'  => '1234',
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure(['token', 'user' => ['id']]);
    }

    // -------------------------------------------------------------------------
    // 2. Unauthenticated request to /queue returns 401
    // -------------------------------------------------------------------------

    public function test_unauthenticated_cannot_reach_queue(): void
    {
        $this->getJson('/api/referee/queue')
             ->assertStatus(401);
    }

    // -------------------------------------------------------------------------
    // 3. Authenticated referee can reach /queue
    // -------------------------------------------------------------------------

    public function test_authenticated_referee_can_reach_queue(): void
    {
        $referee = $this->makeReferee('Bob');

        Sanctum::actingAs($referee, ['*']);

        $this->getJson('/api/referee/queue')
             ->assertStatus(200);
    }

    // -------------------------------------------------------------------------
    // 4. Unauthenticated request to claim a match returns 401
    // -------------------------------------------------------------------------

    public function test_unauthenticated_cannot_claim_match(): void
    {
        $comp  = $this->makeCompetition();
        $match = $this->makeMatch($comp);

        $this->postJson("/api/referee/matches/{$match->id}/claim")
             ->assertStatus(401);
    }

    // -------------------------------------------------------------------------
    // 5. Referee can claim an available (unassigned) match
    // -------------------------------------------------------------------------

    public function test_referee_can_claim_available_match(): void
    {
        $referee = $this->makeReferee('Carol');
        $comp    = $this->makeCompetition();
        $match   = $this->makeMatch($comp, ['referee_id' => null]);

        $token = $referee->createToken('test')->plainTextToken;

        $response = $this->withToken($token)
                         ->postJson("/api/referee/matches/{$match->id}/claim");

        $response->assertStatus(200);
        $this->assertDatabaseHas('matches', [
            'id'          => $match->id,
            'referee_id'  => $referee->id,
        ]);
    }

    // -------------------------------------------------------------------------
    // 6. Referee B cannot claim a match already assigned to referee A
    // -------------------------------------------------------------------------

    public function test_referee_cannot_claim_already_taken_match(): void
    {
        $refereeA = $this->makeReferee('Dave');
        $refereeB = $this->makeReferee('Eve');
        $comp     = $this->makeCompetition();
        $match    = $this->makeMatch($comp, ['referee_id' => $refereeA->id]);

        $token = $refereeB->createToken('test')->plainTextToken;

        $this->withToken($token)
             ->postJson("/api/referee/matches/{$match->id}/claim")
             ->assertStatus(403);
    }

    // -------------------------------------------------------------------------
    // 7. Referee claiming their own match is idempotent (returns 200)
    // -------------------------------------------------------------------------

    public function test_referee_cannot_double_claim(): void
    {
        $referee = $this->makeReferee('Frank');
        $comp    = $this->makeCompetition();
        $match   = $this->makeMatch($comp, ['referee_id' => $referee->id]);

        $token = $referee->createToken('test')->plainTextToken;

        // First claim is idempotent (already owns the match).
        $this->withToken($token)
             ->postJson("/api/referee/matches/{$match->id}/claim")
             ->assertStatus(200);

        // Second call — still their own match, still 200.
        $this->withToken($token)
             ->postJson("/api/referee/matches/{$match->id}/claim")
             ->assertStatus(200);

        $this->assertDatabaseHas('matches', [
            'id'         => $match->id,
            'referee_id' => $referee->id,
        ]);
    }
}
