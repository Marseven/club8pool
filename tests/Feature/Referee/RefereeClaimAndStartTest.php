<?php

namespace Tests\Feature\Referee;

use App\Models\Competition;
use App\Models\GameMatch;
use App\Models\Pool;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class RefereeClaimAndStartTest extends TestCase
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

    private function makeCompetition(): Competition
    {
        return Competition::create([
            'name'         => 'Test Open',
            'slug'         => 'test-open-' . Str::random(6),
            'discipline'   => '8-ball',
            'format'       => 'pools',
            'race_to'      => 5,
            'status'       => 'in_progress',
            'player_slots' => 16,
        ]);
    }

    private function makeMatch(Competition $comp, array $extra = []): GameMatch
    {
        return GameMatch::create(array_merge([
            'competition_id' => $comp->id,
            'round'          => 'pool',
            'round_position' => 1,
            'phase'          => 'pool',
            'status'         => 'scheduled',
        ], $extra));
    }

    // ─── Claim ──────────────────────────────────────────────────────────────

    public function test_referee_can_claim_unassigned_match(): void
    {
        $comp    = $this->makeCompetition();
        $referee = $this->makeReferee();
        $match   = $this->makeMatch($comp);

        $this->actingAs($referee)
             ->post("/arbitre/match/{$match->id}/claim")
             ->assertRedirect();

        $this->assertDatabaseHas('matches', [
            'id'          => $match->id,
            'referee_id'  => $referee->id,
        ]);
    }

    public function test_claiming_already_taken_match_returns_error(): void
    {
        $comp    = $this->makeCompetition();
        $referee = $this->makeReferee();
        $other   = $this->makeReferee();
        $match   = $this->makeMatch($comp, ['referee_id' => $other->id]);

        $this->actingAs($referee)
             ->post("/arbitre/match/{$match->id}/claim")
             ->assertRedirect()
             ->assertSessionHas('error');
    }

    public function test_claiming_match_in_pool_taken_by_other_is_blocked(): void
    {
        $comp    = $this->makeCompetition();
        $referee = $this->makeReferee();
        $other   = $this->makeReferee();

        $pool = Pool::create(['competition_id' => $comp->id, 'name' => 'A']);

        $this->makeMatch($comp, ['pool_id' => $pool->id, 'referee_id' => $other->id]);
        $target = $this->makeMatch($comp, ['pool_id' => $pool->id]);

        $this->actingAs($referee)
             ->post("/arbitre/match/{$target->id}/claim")
             ->assertRedirect()
             ->assertSessionHas('error');
    }

    public function test_claiming_already_mine_is_idempotent(): void
    {
        $comp    = $this->makeCompetition();
        $referee = $this->makeReferee();
        $match   = $this->makeMatch($comp, ['referee_id' => $referee->id]);

        $this->actingAs($referee)
             ->post("/arbitre/match/{$match->id}/claim")
             ->assertRedirect();

        $this->assertDatabaseHas('matches', ['id' => $match->id, 'referee_id' => $referee->id]);
    }

    // ─── Start ──────────────────────────────────────────────────────────────

    public function test_referee_can_start_assigned_match(): void
    {
        $comp    = $this->makeCompetition();
        $referee = $this->makeReferee();
        $match   = $this->makeMatch($comp, ['referee_id' => $referee->id]);

        $this->actingAs($referee)
             ->post("/arbitre/match/{$match->id}/start")
             ->assertRedirect("/arbitre/match/{$match->id}/live");

        $this->assertDatabaseHas('matches', [
            'id'     => $match->id,
            'status' => 'live',
        ]);
    }

    public function test_starting_live_match_redirects_without_error(): void
    {
        $comp    = $this->makeCompetition();
        $referee = $this->makeReferee();
        $match   = $this->makeMatch($comp, ['referee_id' => $referee->id, 'status' => 'live']);

        $this->actingAs($referee)
             ->post("/arbitre/match/{$match->id}/start")
             ->assertRedirect("/arbitre/match/{$match->id}/live");
    }

    public function test_starting_done_match_returns_error(): void
    {
        $comp    = $this->makeCompetition();
        $referee = $this->makeReferee();
        $match   = $this->makeMatch($comp, ['referee_id' => $referee->id, 'status' => 'done']);

        $this->actingAs($referee)
             ->post("/arbitre/match/{$match->id}/start")
             ->assertRedirect()
             ->assertSessionHas('error');
    }

    public function test_non_assigned_referee_cannot_start_match(): void
    {
        $comp    = $this->makeCompetition();
        $referee = $this->makeReferee();
        $other   = $this->makeReferee();
        $match   = $this->makeMatch($comp, ['referee_id' => $other->id]);

        $this->actingAs($referee)
             ->post("/arbitre/match/{$match->id}/start")
             ->assertForbidden();
    }
}
