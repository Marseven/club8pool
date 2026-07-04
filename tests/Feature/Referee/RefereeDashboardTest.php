<?php

namespace Tests\Feature\Referee;

use App\Models\Competition;
use App\Models\GameMatch;
use App\Models\Player;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class RefereeDashboardTest extends TestCase
{
    use RefreshDatabase;

    // ─── Helpers ────────────────────────────────────────────────────────────

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
            'format'       => 'single_elim',
            'race_to'      => 7,
            'status'       => 'in_progress',
            'shot_clock'   => 30,
            'player_slots' => 16,
        ]);
    }

    private function makeMatch(Competition $comp, array $extra = []): GameMatch
    {
        return GameMatch::create(array_merge([
            'competition_id' => $comp->id,
            'round'          => 'QF',
            'round_position' => 1,
            'phase'          => 'knockout',
            'status'         => 'scheduled',
        ], $extra));
    }

    // ─── Tests ──────────────────────────────────────────────────────────────

    public function test_unauthenticated_redirected_from_queue(): void
    {
        $this->get('/arbitre')->assertRedirect();
    }

    public function test_referee_can_access_queue(): void
    {
        $referee = $this->makeReferee();

        $this->actingAs($referee)
             ->get('/arbitre')
             ->assertOk()
             ->assertInertia(fn ($p) => $p->component('Referee/Queue'));
    }

    public function test_queue_only_shows_non_done_matches_for_referee(): void
    {
        $comp     = $this->makeCompetition();
        $referee  = $this->makeReferee();

        $active   = $this->makeMatch($comp, ['referee_id' => $referee->id, 'status' => 'live']);
        $done     = $this->makeMatch($comp, ['referee_id' => $referee->id, 'status' => 'done']);
        $other    = $this->makeMatch($comp, ['referee_id' => $referee->id, 'status' => 'scheduled']);

        $this->actingAs($referee)
             ->get('/arbitre')
             ->assertInertia(fn ($p) => $p
                 ->has('matches', 2)
                 ->where('matches.0.id', $active->id)
             );
    }

    public function test_queue_live_matches_ordered_first(): void
    {
        $comp    = $this->makeCompetition();
        $referee = $this->makeReferee();

        $sched = $this->makeMatch($comp, ['referee_id' => $referee->id, 'status' => 'scheduled']);
        $live  = $this->makeMatch($comp, ['referee_id' => $referee->id, 'status' => 'live']);

        $this->actingAs($referee)
             ->get('/arbitre')
             ->assertInertia(fn ($p) => $p
                 ->where('matches.0.id', $live->id)
             );
    }

    public function test_queue_includes_available_unassigned_matches(): void
    {
        $comp    = $this->makeCompetition();
        $referee = $this->makeReferee();

        $available = $this->makeMatch($comp, ['status' => 'scheduled']);

        $this->actingAs($referee)
             ->get('/arbitre')
             ->assertInertia(fn ($p) => $p->has('available', 1));
    }

    public function test_queue_excludes_available_already_assigned_to_other(): void
    {
        $comp     = $this->makeCompetition();
        $referee  = $this->makeReferee();
        $other    = $this->makeReferee();

        $this->makeMatch($comp, ['referee_id' => $other->id, 'status' => 'scheduled']);

        $this->actingAs($referee)
             ->get('/arbitre')
             ->assertInertia(fn ($p) => $p->has('available', 0));
    }

    public function test_non_referee_redirected_from_queue(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
             ->get('/arbitre')
             ->assertRedirect();
    }
}
