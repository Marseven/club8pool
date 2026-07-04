<?php

namespace Tests\Feature\Referee;

use App\Models\Competition;
use App\Models\GameMatch;
use App\Models\MatchEvent;
use App\Models\Player;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class RefereeExtensionTest extends TestCase
{
    use RefreshDatabase;

    private function makeReferee(): User
    {
        return User::factory()->create([
            'role'              => 'referee',
            'pin'               => Hash::make('1234'),
            'is_referee_active' => true,
        ]);
    }

    private function makeCompetition(int $extPerPlayer = 1): Competition
    {
        return Competition::create([
            'name'                             => 'Test Open',
            'slug'                             => 'test-open-' . Str::random(6),
            'discipline'                       => '8-ball',
            'format'                           => 'single_elim',
            'race_to'                          => 7,
            'status'                           => 'in_progress',
            'player_slots'                     => 16,
            'shot_clock'                       => 30,
            'shot_clock_enabled'               => true,
            'shot_clock_extensions_per_player' => $extPerPlayer,
        ]);
    }

    private function makeLiveMatch(Competition $comp, User $referee): GameMatch
    {
        $pa = Player::create(['first_name' => 'Alice', 'last_name' => 'A', 'email' => 'a' . Str::random(4) . '@test.com']);
        $pb = Player::create(['first_name' => 'Bob',   'last_name' => 'B', 'email' => 'b' . Str::random(4) . '@test.com']);

        return GameMatch::create([
            'competition_id' => $comp->id,
            'round'          => 'QF',
            'round_position' => 1,
            'phase'          => 'knockout',
            'status'         => 'live',
            'referee_id'     => $referee->id,
            'player_a_id'    => $pa->id,
            'player_b_id'    => $pb->id,
            'score_a'        => 0,
            'score_b'        => 0,
            'started_at'     => now()->subMinutes(5),
        ]);
    }

    // ─── Tests ──────────────────────────────────────────────────────────────

    public function test_referee_can_use_extension_for_player_a(): void
    {
        $comp    = $this->makeCompetition(1);
        $referee = $this->makeReferee();
        $match   = $this->makeLiveMatch($comp, $referee);

        $this->actingAs($referee)
             ->post("/arbitre/match/{$match->id}/extension", ['player' => 'A'])
             ->assertRedirect()
             ->assertSessionHas('success');

        $this->assertDatabaseHas('match_events', [
            'match_id'   => $match->id,
            'event_type' => 'shot_clock_extension',
            'player_id'  => $match->player_a_id,
        ]);
    }

    public function test_second_extension_for_same_player_is_rejected(): void
    {
        $comp    = $this->makeCompetition(1);
        $referee = $this->makeReferee();
        $match   = $this->makeLiveMatch($comp, $referee);

        // Première extension
        MatchEvent::create([
            'match_id'       => $match->id,
            'competition_id' => $match->competition_id,
            'event_type'     => 'shot_clock_extension',
            'player_id'      => $match->player_a_id,
            'recorded_by'    => $referee->id,
            'occurred_at'    => now(),
        ]);

        $this->actingAs($referee)
             ->post("/arbitre/match/{$match->id}/extension", ['player' => 'A'])
             ->assertRedirect()
             ->assertSessionHas('error');
    }

    public function test_extension_not_allowed_when_zero_configured(): void
    {
        $comp    = $this->makeCompetition(0);
        $referee = $this->makeReferee();
        $match   = $this->makeLiveMatch($comp, $referee);

        $this->actingAs($referee)
             ->post("/arbitre/match/{$match->id}/extension", ['player' => 'A'])
             ->assertRedirect()
             ->assertSessionHas('error');
    }

    public function test_extension_rejected_on_non_live_match(): void
    {
        $comp    = $this->makeCompetition(1);
        $referee = $this->makeReferee();
        $match   = $this->makeLiveMatch($comp, $referee);
        $match->update(['status' => 'scheduled']);

        $this->actingAs($referee)
             ->post("/arbitre/match/{$match->id}/extension", ['player' => 'A'])
             ->assertRedirect()
             ->assertSessionHas('error');
    }

    public function test_extension_independent_per_player(): void
    {
        $comp    = $this->makeCompetition(1);
        $referee = $this->makeReferee();
        $match   = $this->makeLiveMatch($comp, $referee);

        // Joueur A utilise son extension
        MatchEvent::create([
            'match_id'       => $match->id,
            'competition_id' => $match->competition_id,
            'event_type'     => 'shot_clock_extension',
            'player_id'      => $match->player_a_id,
            'recorded_by'    => $referee->id,
            'occurred_at'    => now(),
        ]);

        // Joueur B peut toujours utiliser la sienne
        $this->actingAs($referee)
             ->post("/arbitre/match/{$match->id}/extension", ['player' => 'B'])
             ->assertRedirect()
             ->assertSessionHas('success');
    }
}
