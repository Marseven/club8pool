<?php

namespace Tests\Feature\Referee;

use App\Models\Competition;
use App\Models\GameMatch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class RefereeMatchControlTest extends TestCase
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

    private function makeCompetition(array $extra = []): Competition
    {
        return Competition::create(array_merge([
            'name'         => 'Test Open',
            'slug'         => 'test-open-' . Str::random(6),
            'discipline'   => '8-ball',
            'format'       => 'single_elim',
            'race_to'      => 3,
            'pool_race_to' => 3,
            'status'       => 'in_progress',
            'player_slots' => 16,
        ], $extra));
    }

    private function makeLiveMatch(Competition $comp, User $referee, array $extra = []): GameMatch
    {
        return GameMatch::create(array_merge([
            'competition_id' => $comp->id,
            'round'          => 'QF',
            'round_position' => 1,
            'phase'          => 'knockout',
            'status'         => 'live',
            'referee_id'     => $referee->id,
            'score_a'        => 0,
            'score_b'        => 0,
            'started_at'     => now()->subMinutes(10),
        ], $extra));
    }

    // ─── Frame ──────────────────────────────────────────────────────────────

    public function test_referee_can_score_frame_for_a(): void
    {
        $comp    = $this->makeCompetition();
        $referee = $this->makeReferee();
        $match   = $this->makeLiveMatch($comp, $referee);

        $this->actingAs($referee)
             ->post("/arbitre/match/{$match->id}/frame", ['winner' => 'A'])
             ->assertRedirect();

        $this->assertDatabaseHas('matches', ['id' => $match->id, 'score_a' => 1]);
    }

    public function test_referee_can_score_frame_for_b(): void
    {
        $comp    = $this->makeCompetition();
        $referee = $this->makeReferee();
        $match   = $this->makeLiveMatch($comp, $referee);

        $this->actingAs($referee)
             ->post("/arbitre/match/{$match->id}/frame", ['winner' => 'B'])
             ->assertRedirect();

        $this->assertDatabaseHas('matches', ['id' => $match->id, 'score_b' => 1]);
    }

    public function test_cannot_score_frame_on_done_match(): void
    {
        $comp    = $this->makeCompetition();
        $referee = $this->makeReferee();
        $match   = $this->makeLiveMatch($comp, $referee, ['status' => 'done', 'score_a' => 3]);

        $this->actingAs($referee)
             ->post("/arbitre/match/{$match->id}/frame", ['winner' => 'A'])
             ->assertSessionHas('error');
    }

    // ─── Undo frame ─────────────────────────────────────────────────────────

    public function test_referee_can_undo_frame(): void
    {
        $comp    = $this->makeCompetition();
        $referee = $this->makeReferee();
        $match   = $this->makeLiveMatch($comp, $referee, ['score_a' => 2]);

        $this->actingAs($referee)
             ->post("/arbitre/match/{$match->id}/undo-frame", ['player' => 'A'])
             ->assertRedirect();

        $this->assertDatabaseHas('matches', ['id' => $match->id, 'score_a' => 1]);
    }

    public function test_undo_frame_at_zero_is_noop(): void
    {
        $comp    = $this->makeCompetition();
        $referee = $this->makeReferee();
        $match   = $this->makeLiveMatch($comp, $referee, ['score_a' => 0]);

        $this->actingAs($referee)
             ->post("/arbitre/match/{$match->id}/undo-frame", ['player' => 'A'])
             ->assertRedirect();

        $this->assertDatabaseHas('matches', ['id' => $match->id, 'score_a' => 0]);
    }

    // ─── Close ──────────────────────────────────────────────────────────────

    public function test_referee_can_close_match_when_race_reached(): void
    {
        $comp    = $this->makeCompetition(['race_to' => 3]);
        $referee = $this->makeReferee();
        $match   = $this->makeLiveMatch($comp, $referee, ['score_a' => 3, 'score_b' => 1]);

        $this->actingAs($referee)
             ->post("/arbitre/match/{$match->id}/clore")
             ->assertRedirect('/arbitre');

        $this->assertDatabaseHas('matches', ['id' => $match->id, 'status' => 'done']);
    }

    public function test_close_fails_when_race_not_reached(): void
    {
        $comp    = $this->makeCompetition(['race_to' => 3]);
        $referee = $this->makeReferee();
        $match   = $this->makeLiveMatch($comp, $referee, ['score_a' => 2, 'score_b' => 1]);

        $this->actingAs($referee)
             ->post("/arbitre/match/{$match->id}/clore")
             ->assertRedirect()
             ->assertSessionHas('error');

        $this->assertDatabaseHas('matches', ['id' => $match->id, 'status' => 'live']);
    }

    public function test_close_knockout_draw_is_rejected(): void
    {
        $comp    = $this->makeCompetition(['race_to' => 3]);
        $referee = $this->makeReferee();
        $match   = $this->makeLiveMatch($comp, $referee, ['score_a' => 3, 'score_b' => 3, 'phase' => 'knockout']);

        $this->actingAs($referee)
             ->post("/arbitre/match/{$match->id}/clore")
             ->assertRedirect()
             ->assertSessionHas('error');
    }

    public function test_close_already_done_returns_error(): void
    {
        $comp    = $this->makeCompetition();
        $referee = $this->makeReferee();
        $match   = $this->makeLiveMatch($comp, $referee, ['status' => 'done', 'score_a' => 3]);

        $this->actingAs($referee)
             ->post("/arbitre/match/{$match->id}/clore")
             ->assertSessionHas('error');
    }

    public function test_non_assigned_referee_cannot_close(): void
    {
        $comp    = $this->makeCompetition(['race_to' => 3]);
        $referee = $this->makeReferee();
        $other   = $this->makeReferee();
        $match   = $this->makeLiveMatch($comp, $other, ['score_a' => 3]);

        $this->actingAs($referee)
             ->post("/arbitre/match/{$match->id}/clore")
             ->assertForbidden();
    }
}
