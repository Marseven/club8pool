<?php

namespace Tests\Feature\Player;

use App\Models\Player;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PlayerPasswordChangeTest extends TestCase
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
            'first_name'                => 'Pass',
            'last_name'                 => 'Joueur',
            'fgb_card'                  => 'FGB' . rand(1000, 9999),
            'rating'                    => 1200,
            'wins'                      => 0,
            'losses'                    => 0,
            'login_name'                => 'passplayer' . self::$seq,
            'login_slug'                => 'pass-player-' . self::$seq,
            'password'                  => Hash::make('oldpassword1'),
            'must_change_password'      => false,
            'is_player_account_enabled' => true,
        ], $overrides));
    }

    // -------------------------------------------------------------------------
    // 1. Player can successfully change their password
    // -------------------------------------------------------------------------

    public function test_player_can_change_password(): void
    {
        $player = $this->makePlayer();

        $response = $this->actingAs($player, 'player')
            ->post('/joueur/password/change', [
                'current_password'      => 'oldpassword1',
                'password'              => 'newpassword8',
                'password_confirmation' => 'newpassword8',
            ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $fresh = Player::find($player->id);
        $this->assertTrue(Hash::check('newpassword8', $fresh->password));
    }

    // -------------------------------------------------------------------------
    // 2. Wrong current password is rejected
    // -------------------------------------------------------------------------

    public function test_old_password_must_be_correct(): void
    {
        $player = $this->makePlayer();

        $response = $this->actingAs($player, 'player')
            ->post('/joueur/password/change', [
                'current_password'      => 'wrongcurrentpassword',
                'password'              => 'newpassword8',
                'password_confirmation' => 'newpassword8',
            ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors();

        // Password must NOT have changed
        $fresh = Player::find($player->id);
        $this->assertTrue(Hash::check('oldpassword1', $fresh->password));
    }

    // -------------------------------------------------------------------------
    // 3. Password confirmation mismatch triggers validation error
    // -------------------------------------------------------------------------

    public function test_new_password_must_be_confirmed(): void
    {
        $player = $this->makePlayer();

        $response = $this->actingAs($player, 'player')
            ->post('/joueur/password/change', [
                'current_password'      => 'oldpassword1',
                'password'              => 'newpassword8',
                'password_confirmation' => 'differentvalue',
            ]);

        $response->assertSessionHasErrors('password');
    }

    // -------------------------------------------------------------------------
    // 4. New password shorter than 8 characters triggers validation error
    // -------------------------------------------------------------------------

    public function test_new_password_must_be_at_least_8_chars(): void
    {
        $player = $this->makePlayer();

        $response = $this->actingAs($player, 'player')
            ->post('/joueur/password/change', [
                'current_password'      => 'oldpassword1',
                'password'              => 'short',
                'password_confirmation' => 'short',
            ]);

        $response->assertSessionHasErrors('password');
    }

    // -------------------------------------------------------------------------
    // 5. must_change_password flag is cleared after a successful change
    // -------------------------------------------------------------------------

    public function test_must_change_password_flag_cleared_after_change(): void
    {
        $player = $this->makePlayer(['must_change_password' => true]);

        $this->assertTrue($player->must_change_password);

        $this->actingAs($player, 'player')
            ->post('/joueur/password/change', [
                'current_password'      => 'oldpassword1',
                'password'              => 'newpassword8',
                'password_confirmation' => 'newpassword8',
            ]);

        $fresh = Player::find($player->id);
        $this->assertFalse((bool) $fresh->must_change_password);
    }
}
