<?php

namespace Tests\Feature\Api;

use App\Models\Competition;
use App\Models\GameMatch;
use App\Models\Player;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MobileApiBackwardCompatibilityTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeCompetition(array $overrides = []): Competition
    {
        return Competition::create(array_merge([
            'name'                            => 'Mobile API Cup',
            'slug'                            => 'mobile-api-' . Str::random(6),
            'discipline'                      => '8-ball',
            'format'                          => 'single_elim',
            'structure'                       => 'knockout',
            'race_to'                         => 7,
            'status'                          => 'in_progress',
            'shot_clock'                      => 30,
            'shot_clock_enabled'              => true,
            'player_slots'                    => 8,
        ], $overrides));
    }

    private function makeReferee(string $name = 'Ref', string $pin = '1234'): User
    {
        return User::factory()->create([
            'name' => $name,
            'role' => 'referee',
            'pin'  => Hash::make($pin),
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
    // 1. Login with wrong PIN returns 401 with message key
    // -------------------------------------------------------------------------

    public function test_login_without_token_returns_401(): void
    {
        $this->makeReferee('Alice', '9999');

        $response = $this->postJson('/api/referee/login', [
            'name' => 'Alice',
            'pin'  => '0000', // wrong PIN
        ]);

        $response->assertStatus(401)
                 ->assertJsonStructure(['message']);
    }

    // -------------------------------------------------------------------------
    // 2. Unauthenticated GET /api/referee/me returns 401
    // -------------------------------------------------------------------------

    public function test_unauthenticated_api_returns_401(): void
    {
        $this->getJson('/api/referee/me')
             ->assertStatus(401);
    }

    // -------------------------------------------------------------------------
    // 3. Blocked referee (trying to claim a match already taken) returns 403
    // -------------------------------------------------------------------------

    public function test_unauthorized_action_returns_403(): void
    {
        $referee1 = $this->makeReferee('Ref1');
        $referee2 = $this->makeReferee('Ref2');
        $comp     = $this->makeCompetition();
        $match    = $this->makeMatch($comp, ['referee_id' => $referee1->id]);

        $token = $referee2->createToken('test')->plainTextToken;

        $this->withToken($token)
             ->postJson("/api/referee/matches/{$match->id}/claim")
             ->assertStatus(403);
    }

    // -------------------------------------------------------------------------
    // 4. Invalid event_type returns 422
    // -------------------------------------------------------------------------

    public function test_invalid_event_type_returns_422(): void
    {
        $referee = $this->makeReferee('EventRef');
        $comp    = $this->makeCompetition();
        $match   = $this->makeMatch($comp, ['referee_id' => $referee->id]);

        $token = $referee->createToken('test')->plainTextToken;

        $this->withToken($token)
             ->postJson("/api/referee/matches/{$match->id}/events", [
                 'event_type' => 'invalid_type',
             ])
             ->assertStatus(422);
    }

    // -------------------------------------------------------------------------
    // 5. Match show includes backward-compatible fields
    // -------------------------------------------------------------------------

    public function test_show_includes_backward_compatible_fields(): void
    {
        $referee = $this->makeReferee('ShowRef');
        $comp    = $this->makeCompetition();
        $playerA = Player::create(['first_name' => 'Ana', 'last_name' => 'A']);
        $playerB = Player::create(['first_name' => 'Ben', 'last_name' => 'B']);
        $match   = $this->makeMatch($comp, [
            'player_a_id' => $playerA->id,
            'player_b_id' => $playerB->id,
            'score_a'     => 2,
            'score_b'     => 3,
            'status'      => 'live',
        ]);

        $token = $referee->createToken('test')->plainTextToken;

        $response = $this->withToken($token)
                         ->getJson("/api/referee/matches/{$match->id}");

        $response->assertStatus(200)
                 ->assertJsonStructure(['id', 'status', 'score_a', 'score_b', 'player_a_id', 'player_b_id']);

        // Verify actual values
        $response->assertJsonFragment([
            'id'          => $match->id,
            'status'      => 'live',
            'score_a'     => 2,
            'score_b'     => 3,
            'player_a_id' => $playerA->id,
            'player_b_id' => $playerB->id,
        ]);
    }

    // -------------------------------------------------------------------------
    // 6. Match show includes new optional fields (v2 additions)
    // -------------------------------------------------------------------------

    public function test_show_includes_new_optional_fields(): void
    {
        $referee = $this->makeReferee('NewFieldRef');
        $comp    = $this->makeCompetition(['shot_clock_enabled' => true, 'shot_clock' => 30]);
        $match   = $this->makeMatch($comp);

        $token = $referee->createToken('test')->plainTextToken;

        $response = $this->withToken($token)
                         ->getJson("/api/referee/matches/{$match->id}");

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'shot_clock_config',
                     'rack_mode',
                     'player_rating_summary',
                     'allowed_events',
                 ]);

        // shot_clock_config must carry enabled flag
        $data = $response->json();
        $this->assertIsArray($data['shot_clock_config'], 'shot_clock_config must be an array');
        $this->assertArrayHasKey('enabled', $data['shot_clock_config']);
        $this->assertArrayHasKey('seconds', $data['shot_clock_config']);

        // allowed_events must be a non-empty list
        $this->assertIsArray($data['allowed_events']);
        $this->assertNotEmpty($data['allowed_events']);
    }

    // -------------------------------------------------------------------------
    // 7. Queue items include shot_clock_config
    // -------------------------------------------------------------------------

    public function test_queue_includes_shot_clock_config(): void
    {
        $referee = $this->makeReferee('QueueRef');
        $comp    = $this->makeCompetition(['shot_clock_enabled' => true, 'shot_clock' => 45]);
        $match   = $this->makeMatch($comp, ['referee_id' => $referee->id]);

        $token = $referee->createToken('test')->plainTextToken;

        $response = $this->withToken($token)
                         ->getJson('/api/referee/queue');

        $response->assertStatus(200);

        $items = $response->json();
        $this->assertIsArray($items);
        $this->assertNotEmpty($items, 'Queue must contain at least one item for the assigned match');

        // Every item in the queue must carry shot_clock_config
        foreach ($items as $item) {
            $this->assertArrayHasKey('shot_clock_config', $item, 'Each queue item must include shot_clock_config');
        }
    }
}
