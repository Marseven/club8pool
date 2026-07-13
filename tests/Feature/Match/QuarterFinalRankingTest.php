<?php

namespace Tests\Feature\Match;

use App\Models\Competition;
use App\Models\GameMatch;
use App\Models\Player;
use App\Models\User;
use App\Services\QuarterFinalRankingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class QuarterFinalRankingTest extends TestCase
{
    use RefreshDatabase;

    private function makeAdmin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function makeCompetition(): Competition
    {
        static $n = 0;
        $n++;
        return Competition::create([
            'name'             => 'QF Cup ' . $n,
            'slug'             => 'qf-cup-' . $n . '-' . Str::random(4),
            'discipline'       => '8-ball',
            'format'           => 'pools',
            'structure'        => 'pools_knockout',
            'race_to'          => 5,
            'knockout_race_to' => 9,
            'status'           => 'in_progress',
            'player_slots'     => 8,
        ]);
    }

    /** Build a fully-played 8-player bracket, return [competition, playerIds]. */
    private function fullBracket(Competition $comp): array
    {
        $p = [];
        for ($i = 1; $i <= 8; $i++) {
            $p[$i] = Player::create(['first_name' => 'P' . $i, 'last_name' => ''])->id;
        }
        $mk = fn ($round, $pos, $a, $b, $sa, $sb) => GameMatch::create([
            'competition_id' => $comp->id, 'phase' => 'knockout', 'round' => $round,
            'round_position' => $pos, 'player_a_id' => $a, 'player_b_id' => $b,
            'score_a' => $sa, 'score_b' => $sb, 'status' => 'done',
        ]);
        $mk('QF', 0, $p[1], $p[8], 9, 2);
        $mk('QF', 1, $p[2], $p[7], 9, 7);
        $mk('QF', 2, $p[3], $p[6], 9, 4);
        $mk('QF', 3, $p[4], $p[5], 9, 8);
        $mk('SF', 0, $p[1], $p[4], 9, 5);
        $mk('SF', 1, $p[2], $p[3], 9, 6);
        $mk('3P', 0, $p[4], $p[3], 9, 7);
        $mk('F', 0, $p[1], $p[2], 11, 8);
        return [$comp, $p];
    }

    public function test_ranking_orders_all_eight_players(): void
    {
        [$comp, $p] = $this->fullBracket($this->makeCompetition());

        $result = app(QuarterFinalRankingService::class)->compute($comp);

        $this->assertTrue($result['has_qf']);
        $this->assertFalse($result['provisional']);
        $this->assertCount(8, $result['rows']);

        // Positions by player id
        $byRank = collect($result['rows'])->keyBy('rank');
        $this->assertSame($p[1], $byRank[1]['player_id']); // champion
        $this->assertSame($p[2], $byRank[2]['player_id']); // finalist
        $this->assertSame($p[4], $byRank[3]['player_id']); // 3P winner
        $this->assertSame($p[3], $byRank[4]['player_id']); // 3P loser

        // 5th-8th are the four QF losers, ordered by frame differential
        $bottom = [$byRank[5]['player_id'], $byRank[6]['player_id'], $byRank[7]['player_id'], $byRank[8]['player_id']];
        $this->assertSame([$p[5], $p[7], $p[6], $p[8]], $bottom);
    }

    public function test_incomplete_bracket_is_provisional(): void
    {
        $comp = $this->makeCompetition();
        $a = Player::create(['first_name' => 'A', 'last_name' => '']);
        $b = Player::create(['first_name' => 'B', 'last_name' => '']);
        // A single scheduled QF, nothing decided
        GameMatch::create([
            'competition_id' => $comp->id, 'phase' => 'knockout', 'round' => 'QF',
            'round_position' => 0, 'player_a_id' => $a->id, 'player_b_id' => $b->id,
            'status' => 'scheduled',
        ]);

        $result = app(QuarterFinalRankingService::class)->compute($comp);
        $this->assertTrue($result['has_qf']);
        $this->assertTrue($result['provisional']);
    }

    public function test_admin_can_export_qf_ranking_pdf(): void
    {
        [$comp] = $this->fullBracket($this->makeCompetition());

        $this->actingAs($this->makeAdmin())
            ->get("/admin/competitions/{$comp->id}/export/classement-qf")
            ->assertOk()
            ->assertSee('Classement des 8 quart-de-finalistes', false);
    }

    public function test_admin_can_export_scoped_pools_pdf(): void
    {
        $comp = $this->makeCompetition();

        $this->actingAs($this->makeAdmin())
            ->get("/admin/competitions/{$comp->id}/export/poules-pdf")
            ->assertOk();
    }

    public function test_knockout_page_lists_competitions_for_selector(): void
    {
        $comp = $this->makeCompetition();

        $this->actingAs($this->makeAdmin())
            ->get("/admin/competitions/{$comp->id}/phase-finale")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Knockout')
                ->has('competitions'));
    }
}
