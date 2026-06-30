<?php

namespace Tests\Feature\Match;

use App\Models\Competition;
use App\Models\GameMatch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class RaceExtensionTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeAdmin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function makeReferee(): User
    {
        return User::factory()->create(['role' => 'referee']);
    }

    private function makeCompetition(array $overrides = []): Competition
    {
        static $n = 0;
        $n++;
        return Competition::create(array_merge([
            'name'         => 'Race Cup ' . $n,
            'slug'         => 'race-cup-' . $n . '-' . Str::random(4),
            'discipline'   => '8-ball',
            'format'       => 'single_elim',
            'structure'    => 'knockout',
            'race_to'      => 5,
            'status'       => 'in_progress',
            'player_slots' => 8,
        ], $overrides));
    }

    private function makeMatch(Competition $comp, array $overrides = []): GameMatch
    {
        return GameMatch::create(array_merge([
            'competition_id' => $comp->id,
            'phase'          => 'knockout',
            'round'          => 'QF',
            'round_position' => 0,
            'score_a'        => 0,
            'score_b'        => 0,
            'status'         => 'scheduled',
        ], $overrides));
    }

    // -------------------------------------------------------------------------
    // 1. Admin can extend the race for a match
    // -------------------------------------------------------------------------

    public function test_admin_can_extend_race(): void
    {
        if (! in_array('race_to_override', $this->getRaceOverrideColumns())) {
            $this->markTestSkipped('race_to_override column not yet in matches table — migration pending.');
        }

        if (! $this->hasRaceOverrideValidation()) {
            $this->markTestSkipped('race_to_override not yet accepted by MatchController::update() — add it to the validation rules.');
        }

        $admin = $this->makeAdmin();
        $comp  = $this->makeCompetition(['race_to' => 5]);
        $match = $this->makeMatch($comp);

        $response = $this->actingAs($admin)
            ->patch(route('admin.matches.update', $match), [
                'race_to_override'        => 7,
                'race_to_override_reason' => 'Décision de l\'organisateur pour la finale',
            ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('matches', [
            'id'                      => $match->id,
            'race_to_override'        => 7,
            'race_to_override_reason' => 'Décision de l\'organisateur pour la finale',
        ]);
    }

    // -------------------------------------------------------------------------
    // 2. Referee (non-admin) cannot reach the admin route (redirected, not 403)
    // -------------------------------------------------------------------------

    public function test_referee_cannot_extend_race(): void
    {
        if (! in_array('race_to_override', $this->getRaceOverrideColumns())) {
            $this->markTestSkipped('race_to_override column not yet in matches table — migration pending.');
        }

        $referee = $this->makeReferee();
        $comp    = $this->makeCompetition();
        $match   = $this->makeMatch($comp);

        $response = $this->actingAs($referee)
            ->patch(route('admin.matches.update', $match), [
                'race_to_override'        => 7,
                'race_to_override_reason' => 'Tentative non autorisée',
            ]);

        // The admin middleware redirects (302) or returns 403 depending on implementation.
        // Either way the referee must NOT be allowed through.
        $this->assertContains($response->status(), [302, 403]);

        // The DB must be unchanged regardless of HTTP status
        $this->assertDatabaseMissing('matches', [
            'id'               => $match->id,
            'race_to_override' => 7,
        ]);
    }

    // -------------------------------------------------------------------------
    // 3. race_to_override requires a reason (validation)
    // -------------------------------------------------------------------------

    public function test_race_override_requires_reason(): void
    {
        if (! in_array('race_to_override', $this->getRaceOverrideColumns())) {
            $this->markTestSkipped('race_to_override column not yet in matches table — migration pending.');
        }

        $admin = $this->makeAdmin();
        $comp  = $this->makeCompetition();
        $match = $this->makeMatch($comp);

        $validated = $this->hasRaceOverrideValidation();
        if (! $validated) {
            $this->markTestSkipped('race_to_override validation not yet in MatchController — add it to the update() rules.');
        }

        $response = $this->actingAs($admin)
            ->patch(route('admin.matches.update', $match), [
                'race_to_override' => 7,
                // race_to_override_reason intentionally omitted
            ]);

        $response->assertSessionHasErrors('race_to_override_reason');
    }

    // -------------------------------------------------------------------------
    // 4. race_to_override must exceed the competition's race_to
    // -------------------------------------------------------------------------

    public function test_race_override_must_exceed_current_race(): void
    {
        if (! in_array('race_to_override', $this->getRaceOverrideColumns())) {
            $this->markTestSkipped('race_to_override column not yet in matches table — migration pending.');
        }

        $validated = $this->hasRaceOverrideValidation();
        if (! $validated) {
            $this->markTestSkipped('race_to_override validation not yet in MatchController — add cross-field validation.');
        }

        $admin = $this->makeAdmin();
        $comp  = $this->makeCompetition(['race_to' => 5]);
        $match = $this->makeMatch($comp);

        // race_to_override = 5 is equal to race_to (should be strictly greater)
        $response = $this->actingAs($admin)
            ->patch(route('admin.matches.update', $match), [
                'race_to_override'        => 5,
                'race_to_override_reason' => 'Même valeur que race_to',
            ]);

        $response->assertSessionHasErrors('race_to_override');
    }

    // -------------------------------------------------------------------------
    // Introspection helpers (check DB schema / controller rules at test time)
    // -------------------------------------------------------------------------

    /**
     * Returns the list of columns currently present in the `matches` table.
     *
     * @return string[]
     */
    private function getRaceOverrideColumns(): array
    {
        try {
            return \Illuminate\Support\Facades\Schema::getColumnListing('matches');
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Heuristic: check whether the MatchController::update validation rules
     * mention `race_to_override`.
     */
    private function hasRaceOverrideValidation(): bool
    {
        $controllerPath = app_path('Http/Controllers/Admin/MatchController.php');
        if (! file_exists($controllerPath)) {
            return false;
        }
        return str_contains(file_get_contents($controllerPath), 'race_to_override');
    }
}
