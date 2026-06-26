<?php

namespace Tests\Feature\Security;

use App\Models\Competition;
use App\Models\GameMatch;
use App\Models\Player;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class UploadValidationTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeAdmin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    /**
     * Create a competition with all non-nullable fields filled in.
     */
    private function makeCompetition(): Competition
    {
        return Competition::create([
            'name'        => 'Test Open',
            'slug'        => 'test-open-' . Str::random(6),
            'discipline'  => '8-ball',
            'format'      => 'single_elim',
            'structure'   => 'knockout',
            'race_to'     => 7,
            'status'      => 'draft',
            'shot_clock'  => 30,
            'player_slots' => 16,
        ]);
    }

    // -------------------------------------------------------------------------
    // 1. Valid PNG is accepted
    // -------------------------------------------------------------------------

    public function test_logo_upload_accepts_valid_png(): void
    {
        Storage::fake('public');

        $admin = $this->makeAdmin();
        $comp  = $this->makeCompetition();

        $file = UploadedFile::fake()->image('logo.png', 100, 100);

        $response = $this->actingAs($admin)
                         ->post("/admin/competitions/{$comp->id}/logo", [
                             'logo' => $file,
                         ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
    }

    // -------------------------------------------------------------------------
    // 2. Non-image file is rejected
    // -------------------------------------------------------------------------

    public function test_logo_upload_rejects_non_image(): void
    {
        Storage::fake('public');

        $admin = $this->makeAdmin();
        $comp  = $this->makeCompetition();

        $file = UploadedFile::fake()->create('malware.txt', 10, 'text/plain');

        $response = $this->actingAs($admin)
                         ->post("/admin/competitions/{$comp->id}/logo", [
                             'logo' => $file,
                         ]);

        // Laravel redirects back with errors for non-JSON requests on validation failure
        $response->assertSessionHasErrors(['logo']);
    }

    // -------------------------------------------------------------------------
    // 3. Oversized image is rejected (> 2 MB)
    // -------------------------------------------------------------------------

    public function test_logo_upload_rejects_oversized_file(): void
    {
        Storage::fake('public');

        $admin = $this->makeAdmin();
        $comp  = $this->makeCompetition();

        // 2049 KB exceeds the 2048 KB (2 MB) max
        $file = UploadedFile::fake()->image('big.png')->size(2049);

        $response = $this->actingAs($admin)
                         ->post("/admin/competitions/{$comp->id}/logo", [
                             'logo' => $file,
                         ]);

        $response->assertSessionHasErrors(['logo']);
    }

    // -------------------------------------------------------------------------
    // 4. Signature endpoint rejects oversized payload
    //    The sign endpoint currently validates signature_data as nullable|string
    //    with no max length. This test INTENTIONALLY FAILS until a max rule
    //    is added in RefereeApiController::sign():
    //
    //      'signature_data' => ['nullable', 'string', 'max:200000'],
    //
    //    Fix: add 'max:200000' to the signature_data validation rule.
    // -------------------------------------------------------------------------

    public function test_signature_rejects_oversized_payload(): void
    {
        $referee = User::factory()->create(['role' => 'referee']);
        $comp    = $this->makeCompetition();
        $player  = Player::create(['first_name' => 'Jean', 'last_name' => 'Dupont']);

        $match = GameMatch::create([
            'competition_id' => $comp->id,
            'round'          => 'QF',
            'round_position' => 0,
            'phase'          => 'knockout',
            'status'         => 'done',
            'player_a_id'    => $player->id,
        ]);

        $token = $referee->createToken('test')->plainTextToken;

        $response = $this->withToken($token)
                         ->postJson("/api/referee/matches/{$match->id}/sign", [
                             'player_id'      => $player->id,
                             'signature_data' => str_repeat('A', 200001),
                         ]);

        // The server must reject the payload with 422 (validation) or 413 (too large).
        $this->assertContains($response->status(), [413, 422]);
    }
}
