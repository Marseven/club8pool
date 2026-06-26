<?php

namespace Tests\Feature\Security;

use App\Models\Competition;
use App\Models\GameMatch;
use App\Models\Player;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class RefereeEventAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeCompetition(array $overrides = []): Competition
    {
        return Competition::create(array_merge([
            'name'         => 'Event Auth Cup',
            'slug'         => 'event-auth-' . Str::random(6),
            'discipline'   => '8-ball',
            'format'       => 'single_elim',
            'structure'    => 'knockout',
            'race_to'      => 7,
            'status'       => 'in_progress',
            'shot_clock'   => 30,
            'player_slots' => 8,
        ], $overrides));
    }

    private function makeReferee(string $name = 'Ref'): User
    {
        return User::factory()->create([
            'name' => $name,
            'role' => 'referee',
            'pin'  => Hash::make('1234'),
        ]);
    }

    private function makeMatch(Competition $comp, array $overrides = []): GameMatch
    {
        $playerA = Player::create(['first_name' => 'AlphaA', 'last_name' => 'Test']);
        $playerB = Player::create(['first_name' => 'AlphaB', 'last_name' => 'Test']);

        return GameMatch::create(array_merge([
            'competition_id' => $comp->id,
            'round'          => 'QF',
            'round_position' => 0,
            'phase'          => 'knockout',
            'status'         => 'live',
            'player_a_id'    => $playerA->id,
            'player_b_id'    => $playerB->id,
            'score_a'        => 0,
            'score_b'        => 0,
        ], $overrides));
    }

    private function validEventPayload(): array
    {
        return [
            'event_type' => 'foul',
            'player'     => 'A',
        ];
    }

    // -------------------------------------------------------------------------
    // 1. Assigned referee can record an event
    // -------------------------------------------------------------------------

    public function test_assigned_referee_can_record_event(): void
    {
        $referee = $this->makeReferee('AssignedRef');
        $comp    = $this->makeCompetition();
        $match   = $this->makeMatch($comp, ['referee_id' => $referee->id]);

        $token = $referee->createToken('test')->plainTextToken;

        $response = $this->withToken($token)
                         ->postJson("/api/referee/matches/{$match->id}/events", $this->validEventPayload());

        $response->assertStatus(200);
    }

    // -------------------------------------------------------------------------
    // 2. Unassigned referee cannot record an event on a match already taken
    // -------------------------------------------------------------------------

    public function test_unassigned_referee_cannot_record_event_on_taken_match(): void
    {
        $referee1 = $this->makeReferee('Owner');
        $referee2 = $this->makeReferee('Intruder');
        $comp     = $this->makeCompetition();
        $match    = $this->makeMatch($comp, ['referee_id' => $referee1->id]);

        $token = $referee2->createToken('test')->plainTextToken;

        $response = $this->withToken($token)
                         ->postJson("/api/referee/matches/{$match->id}/events", $this->validEventPayload());

        $response->assertStatus(403);
    }

    // -------------------------------------------------------------------------
    // 3. Any referee can record an event on an unclaimed match
    // -------------------------------------------------------------------------

    public function test_any_referee_can_record_event_on_unclaimed_match(): void
    {
        $referee = $this->makeReferee('FreeRef');
        $comp    = $this->makeCompetition();
        $match   = $this->makeMatch($comp, ['referee_id' => null]);

        $token = $referee->createToken('test')->plainTextToken;

        $response = $this->withToken($token)
                         ->postJson("/api/referee/matches/{$match->id}/events", $this->validEventPayload());

        $response->assertStatus(200);
    }

    // -------------------------------------------------------------------------
    // 4. Invalid event_type is rejected with 422
    // -------------------------------------------------------------------------

    public function test_invalid_event_type_is_rejected(): void
    {
        $referee = $this->makeReferee('HackRef');
        $comp    = $this->makeCompetition();
        $match   = $this->makeMatch($comp, ['referee_id' => $referee->id]);

        $token = $referee->createToken('test')->plainTextToken;

        $response = $this->withToken($token)
                         ->postJson("/api/referee/matches/{$match->id}/events", [
                             'event_type' => 'hacked',
                             'player'     => 'A',
                         ]);

        $response->assertStatus(422);
    }

    // -------------------------------------------------------------------------
    // 5. player field must be in:A,B — sending 'C' returns 422
    // -------------------------------------------------------------------------

    public function test_event_player_must_be_in_match(): void
    {
        $referee = $this->makeReferee('PlayerRef');
        $comp    = $this->makeCompetition();
        $match   = $this->makeMatch($comp, ['referee_id' => $referee->id]);

        $token = $referee->createToken('test')->plainTextToken;

        $response = $this->withToken($token)
                         ->postJson("/api/referee/matches/{$match->id}/events", [
                             'event_type' => 'foul',
                             'player'     => 'C', // invalid — only A or B accepted
                         ]);

        $response->assertStatus(422);
    }
}
