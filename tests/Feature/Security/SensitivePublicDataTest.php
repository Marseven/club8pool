<?php

namespace Tests\Feature\Security;

use App\Models\Competition;
use App\Models\GameMatch;
use App\Models\Player;
use App\Models\Pool;
use App\Models\Registration;
use App\Models\Signature;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SensitivePublicDataTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeCompetition(array $overrides = []): Competition
    {
        return Competition::create(array_merge([
            'name'                => 'PII Test Open',
            'slug'                => 'pii-test-' . Str::random(6),
            'discipline'          => '8-ball',
            'format'              => 'pools',
            'structure'           => 'pools_knockout',
            'race_to'             => 7,
            'pool_race_to'        => 7,
            'knockout_race_to'    => 7,
            'status'              => 'in_progress',
            'shot_clock'          => 30,
            'player_slots'        => 8,
            'pool_count'          => 1,
            'pool_size'           => 4,
            'qualifiers_per_pool' => 2,
        ], $overrides));
    }

    private function makePlayerWithPii(string $first, string $last = 'Test'): Player
    {
        return Player::create([
            'first_name' => $first,
            'last_name'  => $last,
            'phone'      => '0600000001',
            'email'      => strtolower($first) . '.piitest@example.com',
            'address'    => '123 Rue Confidentielle, Paris',
            'birthdate'  => '1985-06-15',
        ]);
    }

    private function makeReferee(): User
    {
        return User::factory()->create(['role' => 'referee']);
    }

    /**
     * Recursively check that a nested array/value does NOT contain the given string as a key.
     */
    private function assertKeyAbsent(array $data, string $key, string $message = ''): void
    {
        array_walk_recursive($data, function ($value, $k) use ($key, $message) {
            $this->assertNotEquals($key, $k, $message ?: "Key \"{$key}\" must not appear in the Inertia page props");
        });
    }

    /**
     * Recursively check that a nested array/value does NOT contain the given string as a value.
     */
    private function assertValueAbsent(array $data, string $needle, string $message = ''): void
    {
        $json = json_encode($data);
        $this->assertStringNotContainsString($needle, $json, $message ?: "Value \"{$needle}\" must not appear in the Inertia page props");
    }

    // -------------------------------------------------------------------------
    // 1. GET /joueurs index does not expose PII in Inertia props
    // -------------------------------------------------------------------------

    public function test_public_player_index_does_not_expose_pii(): void
    {
        $comp   = $this->makeCompetition();
        $pool   = Pool::create([
            'competition_id' => $comp->id,
            'name'           => 'A',
            'position'       => 0,
            'size'           => 4,
        ]);
        $player = $this->makePlayerWithPii('Alice');

        Registration::create([
            'competition_id' => $comp->id,
            'pool_id'        => $pool->id,
            'pool_slot'      => 1,
            'player_id'      => $player->id,
            'status'         => 'confirmed',
        ]);

        $response = $this->get('/joueurs');
        $response->assertStatus(200);

        // Extract only the Inertia props (avoids false positives from HTML/JS/LD+JSON structure)
        $props = $response->inertiaProps();

        $propsJson = json_encode($props);

        // PII field names must not appear as JSON keys in the component props
        $this->assertStringNotContainsString('"phone"', $propsJson, 'phone field must be hidden from public players index props');
        $this->assertStringNotContainsString('"address"', $propsJson, 'address field must be hidden from public players index props');
        $this->assertStringNotContainsString('"birthdate"', $propsJson, 'birthdate field must be hidden from public players index props');

        // PII values must not leak under any key name
        $this->assertStringNotContainsString('0600000001', $propsJson, 'phone value must not appear in public players index props');
        $this->assertStringNotContainsString('Rue Confidentielle', $propsJson, 'address value must not appear in public players index props');
        $this->assertStringNotContainsString('1985-06-15', $propsJson, 'birthdate value must not appear in public players index props');

        // Email is trickier: the manual array builder in PlayerController does not include it.
        // We check the value is absent, not the key (since "email" may appear in competition model).
        $this->assertStringNotContainsString('piitest@example.com', $propsJson, 'email value must not appear in public players index props');
    }

    // -------------------------------------------------------------------------
    // 2. GET /joueurs/{player} show does not expose PII in Inertia props
    // -------------------------------------------------------------------------

    public function test_public_player_show_does_not_expose_pii(): void
    {
        $comp   = $this->makeCompetition();
        $pool   = Pool::create([
            'competition_id' => $comp->id,
            'name'           => 'A',
            'position'       => 0,
            'size'           => 4,
        ]);
        $player = $this->makePlayerWithPii('Bob');

        Registration::create([
            'competition_id' => $comp->id,
            'pool_id'        => $pool->id,
            'pool_slot'      => 1,
            'player_id'      => $player->id,
            'status'         => 'confirmed',
        ]);

        $response = $this->get("/joueurs/{$player->id}");
        $response->assertStatus(200);

        $props    = $response->inertiaProps();
        $propsJson = json_encode($props);

        // PlayerController::show() passes $player->load('club') — the Player model has
        // $hidden = ['phone', 'email', 'address', 'birthdate'], so these must not appear.
        $this->assertStringNotContainsString('"phone"', $propsJson, 'phone must be hidden in player show props');
        $this->assertStringNotContainsString('"address"', $propsJson, 'address must be hidden in player show props');
        $this->assertStringNotContainsString('"birthdate"', $propsJson, 'birthdate must be hidden in player show props');

        // Verify actual PII values are absent
        $this->assertStringNotContainsString('0600000001', $propsJson, 'phone value must not appear in player show props');
        $this->assertStringNotContainsString('Rue Confidentielle', $propsJson, 'address value must not appear in player show props');
        $this->assertStringNotContainsString('1985-06-15', $propsJson, 'birthdate value must not appear in player show props');
        $this->assertStringNotContainsString('piitest@example.com', $propsJson, 'email value must not appear in player show props');
    }

    // -------------------------------------------------------------------------
    // 3. GET /competitions/{slug} does not expose PII in Inertia props
    // -------------------------------------------------------------------------

    public function test_public_competition_show_does_not_expose_pii(): void
    {
        $comp   = $this->makeCompetition();
        $pool   = Pool::create([
            'competition_id' => $comp->id,
            'name'           => 'A',
            'position'       => 0,
            'size'           => 4,
        ]);
        $player = $this->makePlayerWithPii('Carol');

        Registration::create([
            'competition_id' => $comp->id,
            'pool_id'        => $pool->id,
            'pool_slot'      => 1,
            'player_id'      => $player->id,
            'status'         => 'confirmed',
        ]);

        $response = $this->get("/competitions/{$comp->slug}");
        $response->assertStatus(200);

        $props    = $response->inertiaProps();
        $propsJson = json_encode($props);

        // CompetitionController passes $pool->players (Eloquent collection)
        // The Player model $hidden must prevent PII exposure
        $this->assertStringNotContainsString('"phone"', $propsJson, 'phone must be hidden in competition show props');
        $this->assertStringNotContainsString('"address"', $propsJson, 'address must be hidden in competition show props');
        $this->assertStringNotContainsString('"birthdate"', $propsJson, 'birthdate must be hidden in competition show props');

        // Verify actual PII values are absent
        $this->assertStringNotContainsString('0600000001', $propsJson, 'phone value must not appear in competition show props');
        $this->assertStringNotContainsString('Rue Confidentielle', $propsJson, 'address value must not appear in competition show props');
        $this->assertStringNotContainsString('1985-06-15', $propsJson, 'birthdate value must not appear in competition show props');
        $this->assertStringNotContainsString('piitest@example.com', $propsJson, 'email value must not appear in competition show props');
    }

    // -------------------------------------------------------------------------
    // 4. GET /api/referee/matches/{id} must not expose raw signature_data
    //
    //    The API currently exposes signature_data in the signatures relation
    //    because Signature::$hidden does not include 'signature_data' and
    //    RefereeApiController::show() loads the full relation.
    //
    //    Fix options:
    //      a) Add 'signature_data' to Signature::$hidden
    //      b) Load signatures with ->select('id','match_id','player_id','signed_at')
    //
    //    This test will PASS once either fix is applied.
    //    Currently skipped to keep the suite green while the fix is tracked.
    //    Remove the markTestSkipped() call after applying the fix.
    // -------------------------------------------------------------------------

    public function test_api_show_does_not_expose_signature_data(): void
    {
        $referee = $this->makeReferee();
        $comp    = $this->makeCompetition();
        $player  = $this->makePlayerWithPii('Dave');

        $match = GameMatch::create([
            'competition_id' => $comp->id,
            'round'          => 'QF',
            'round_position' => 0,
            'phase'          => 'knockout',
            'status'         => 'done',
            'player_a_id'    => $player->id,
            'referee_id'     => $referee->id,
        ]);

        // Store a signature with raw binary data
        Signature::create([
            'match_id'       => $match->id,
            'player_id'      => $player->id,
            'signature_data' => 'data:image/png;base64,' . base64_encode('FAKE_SIG_BLOB_UNIQUE_99887766'),
            'signed_at'      => now(),
        ]);

        $token    = $referee->createToken('test')->plainTextToken;
        $response = $this->withToken($token)
                         ->getJson("/api/referee/matches/{$match->id}");

        $response->assertStatus(200);

        $content = $response->getContent();

        // The raw signature_data field must not be present in the API response.
        $this->assertStringNotContainsString(
            '"signature_data"',
            $content,
            'raw signature_data must not be exposed via the API match show endpoint — add signature_data to Signature::$hidden to fix'
        );
        $this->assertStringNotContainsString(
            'FAKE_SIG_BLOB_UNIQUE_99887766',
            $content,
            'signature binary content must not be exposed via the API match show endpoint'
        );
    }
}
