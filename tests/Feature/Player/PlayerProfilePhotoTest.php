<?php

namespace Tests\Feature\Player;

use App\Models\Player;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PlayerProfilePhotoTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private static int $seq = 0;

    private function makePlayer(array $overrides = []): Player
    {
        self::$seq++;
        return Player::create(array_merge([
            'first_name'                => 'Photo',
            'last_name'                 => 'Joueur',
            'fgb_card'                  => 'FGB' . rand(1000, 9999),
            'rating'                    => 1200,
            'wins'                      => 0,
            'losses'                    => 0,
            'login_name'                => 'photoplayer' . self::$seq,
            'login_slug'                => 'photo-player-' . self::$seq,
            'password'                  => Hash::make('1234567'),
            'must_change_password'      => false,
            'is_player_account_enabled' => true,
        ], $overrides));
    }

    // -------------------------------------------------------------------------
    // 1. Player can upload a valid JPEG photo
    // -------------------------------------------------------------------------

    public function test_player_can_upload_valid_photo(): void
    {
        Storage::fake('public');

        $player = $this->makePlayer();
        $photo  = UploadedFile::fake()->image('test.jpg', 100, 100)->size(500);

        $response = $this->actingAs($player, 'player')
            ->post('/joueur/photo', [
                'photo' => $photo,
            ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $fresh = Player::find($player->id);
        $this->assertNotNull($fresh->profile_photo_path);
        Storage::disk('public')->assertExists($fresh->profile_photo_path);
    }

    // -------------------------------------------------------------------------
    // 2. Invalid MIME type is rejected (web route → redirect with session errors)
    // -------------------------------------------------------------------------

    public function test_invalid_mime_rejected(): void
    {
        Storage::fake('public');

        $player  = $this->makePlayer();
        $badFile = UploadedFile::fake()->create('document.txt', 100, 'text/plain');

        $response = $this->actingAs($player, 'player')
            ->post('/joueur/photo', [
                'photo' => $badFile,
            ]);

        // Web routes redirect back with session errors on validation failure
        $response->assertRedirect();
        $response->assertSessionHasErrors('photo');
    }

    // -------------------------------------------------------------------------
    // 3. File larger than 2MB is rejected (web route → redirect with session errors)
    // -------------------------------------------------------------------------

    public function test_file_too_large_rejected(): void
    {
        Storage::fake('public');

        $player    = $this->makePlayer();
        // 3 MB (3072 KB) exceeds the 2 MB limit
        $largeFile = UploadedFile::fake()->image('big.jpg', 2000, 2000)->size(3072);

        $response = $this->actingAs($player, 'player')
            ->post('/joueur/photo', [
                'photo' => $largeFile,
            ]);

        // Web routes redirect back with session errors on validation failure
        $response->assertRedirect();
        $response->assertSessionHasErrors('photo');
    }

    // -------------------------------------------------------------------------
    // 4. Unauthenticated upload redirects to login
    // -------------------------------------------------------------------------

    public function test_unauthenticated_cannot_upload_photo(): void
    {
        Storage::fake('public');

        $photo = UploadedFile::fake()->image('test.jpg', 100, 100)->size(500);

        $response = $this->post('/joueur/photo', [
            'photo' => $photo,
        ]);

        $response->assertRedirect(route('player.login'));
    }

    // -------------------------------------------------------------------------
    // 5. Old photo is deleted from storage when a new photo is uploaded
    // -------------------------------------------------------------------------

    public function test_old_photo_is_deleted_on_new_upload(): void
    {
        Storage::fake('public');

        // Create a fake existing photo in storage
        $oldPath = 'player-photos/old_photo.jpg';
        Storage::disk('public')->put($oldPath, 'fake image content');

        $player = $this->makePlayer(['profile_photo_path' => $oldPath]);

        Storage::disk('public')->assertExists($oldPath);

        $newPhoto = UploadedFile::fake()->image('new.jpg', 100, 100)->size(500);

        $this->actingAs($player, 'player')
            ->post('/joueur/photo', [
                'photo' => $newPhoto,
            ]);

        // Old file should have been deleted
        Storage::disk('public')->assertMissing($oldPath);
    }
}
