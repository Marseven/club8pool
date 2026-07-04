<?php

namespace Tests\Feature\Referee;

use App\Models\Competition;
use App\Models\GameMatch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class RefereeArchiveTest extends TestCase
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
            'format'       => 'single_elim',
            'race_to'      => 7,
            'status'       => 'in_progress',
            'player_slots' => 16,
        ]);
    }

    // ─── Tests ──────────────────────────────────────────────────────────────

    public function test_referee_can_access_archive(): void
    {
        $referee = $this->makeReferee();

        $this->actingAs($referee)
             ->get('/arbitre/archive')
             ->assertOk()
             ->assertInertia(fn ($p) => $p->component('Referee/Archive'));
    }

    public function test_archive_shows_only_done_matches_for_referee(): void
    {
        $comp    = $this->makeCompetition();
        $referee = $this->makeReferee();
        $other   = $this->makeReferee();

        $mine     = GameMatch::create(['competition_id' => $comp->id, 'round' => 'QF', 'round_position' => 1, 'phase' => 'knockout', 'referee_id' => $referee->id, 'status' => 'done', 'ended_at' => now()]);
        $live     = GameMatch::create(['competition_id' => $comp->id, 'round' => 'QF', 'round_position' => 2, 'phase' => 'knockout', 'referee_id' => $referee->id, 'status' => 'live']);
        $theirs   = GameMatch::create(['competition_id' => $comp->id, 'round' => 'QF', 'round_position' => 3, 'phase' => 'knockout', 'referee_id' => $other->id, 'status' => 'done', 'ended_at' => now()]);

        $this->actingAs($referee)
             ->get('/arbitre/archive')
             ->assertInertia(fn ($p) => $p->has('matches', 1)
                 ->where('matches.0.id', $mine->id));
    }

    public function test_archive_limited_to_200(): void
    {
        $comp    = $this->makeCompetition();
        $referee = $this->makeReferee();

        for ($i = 1; $i <= 10; $i++) {
            GameMatch::create(['competition_id' => $comp->id, 'round' => 'QF', 'round_position' => $i, 'phase' => 'knockout', 'referee_id' => $referee->id, 'status' => 'done', 'ended_at' => now()]);
        }

        $this->actingAs($referee)
             ->get('/arbitre/archive')
             ->assertInertia(fn ($p) => $p->has('matches', 10));
    }

    public function test_unauthenticated_redirected_from_archive(): void
    {
        $this->get('/arbitre/archive')->assertRedirect();
    }
}
