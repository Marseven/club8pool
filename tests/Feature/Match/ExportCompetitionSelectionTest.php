<?php

namespace Tests\Feature\Match;

use App\Models\Competition;
use App\Models\GameMatch;
use App\Models\Player;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ExportCompetitionSelectionTest extends TestCase
{
    use RefreshDatabase;

    private function makeAdmin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function makeCompetition(string $status = 'in_progress'): Competition
    {
        static $n = 0;
        $n++;
        return Competition::create([
            'name'         => 'Export Cup ' . $n,
            'slug'         => 'export-cup-' . $n . '-' . Str::random(4),
            'discipline'   => '8-ball',
            'format'       => 'pools',
            'structure'    => 'pools_knockout',
            'race_to'      => 5,
            'status'       => $status,
            'player_slots' => 8,
            'starts_on'    => now()->subDays($n)->toDateString(),
        ]);
    }

    private function doneMatchOn(Competition $comp, string $date): GameMatch
    {
        $a = Player::create(['first_name' => 'A' . Str::random(3), 'last_name' => '']);
        $b = Player::create(['first_name' => 'B' . Str::random(3), 'last_name' => '']);
        return GameMatch::create([
            'competition_id' => $comp->id, 'phase' => 'pool', 'round' => 'RR', 'round_position' => 0,
            'player_a_id' => $a->id, 'player_b_id' => $b->id, 'score_a' => 5, 'score_b' => 3,
            'status' => 'done', 'ended_at' => $date . ' 18:00:00',
        ]);
    }

    public function test_export_page_lists_all_competitions(): void
    {
        $this->makeCompetition();
        $this->makeCompetition('finished');

        $this->actingAs($this->makeAdmin())
            ->get('/admin/exports')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Exports')
                ->has('competitions', 2));
    }

    public function test_export_page_selects_competition_by_param(): void
    {
        $current  = $this->makeCompetition();
        $previous = $this->makeCompetition('finished');
        $this->doneMatchOn($previous, '2026-06-01');

        // Ask for the previous competition explicitly
        $this->actingAs($this->makeAdmin())
            ->get('/admin/exports?competition=' . $previous->id)
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('competition.id', $previous->id)
                ->has('days', 1)
                ->where('days.0.date', '2026-06-01'));
    }

    public function test_day_pdf_export_respects_selected_competition(): void
    {
        $current  = $this->makeCompetition();
        $previous = $this->makeCompetition('finished');
        $this->doneMatchOn($previous, '2026-06-01');

        $this->actingAs($this->makeAdmin())
            ->get('/admin/exports/pdf?date=2026-06-01&competition=' . $previous->id)
            ->assertOk()
            ->assertSee($previous->name, false);
    }
}
