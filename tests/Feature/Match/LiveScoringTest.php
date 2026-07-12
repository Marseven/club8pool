<?php

namespace Tests\Feature\Match;

use App\Models\Competition;
use App\Models\GameMatch;
use App\Models\Player;
use App\Models\Pool;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class LiveScoringTest extends TestCase
{
    use RefreshDatabase;

    private function makeAdmin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function makeCompetition(array $overrides = []): Competition
    {
        static $n = 0;
        $n++;
        return Competition::create(array_merge([
            'name'             => 'Scoring Cup ' . $n,
            'slug'             => 'scoring-cup-' . $n . '-' . Str::random(4),
            'discipline'       => '8-ball',
            'format'           => 'pools',
            'structure'        => 'pools_knockout',
            'race_to'          => 5,
            'pool_race_to'     => 5,
            'knockout_race_to' => 9,
            'status'           => 'in_progress',
            'player_slots'     => 8,
        ], $overrides));
    }

    private function makePlayers(int $count = 2): array
    {
        $out = [];
        for ($i = 0; $i < $count; $i++) {
            $out[] = Player::create([
                'first_name' => 'P' . Str::random(4),
                'last_name'  => '',
            ]);
        }
        return $out;
    }

    // -------------------------------------------------------------------------
    // 1. Live scoring page loads and lists live + upcoming matches
    // -------------------------------------------------------------------------

    public function test_scoring_page_lists_live_and_upcoming(): void
    {
        $admin = $this->makeAdmin();
        $comp  = $this->makeCompetition();
        [$a, $b, $c, $d] = $this->makePlayers(4);

        $live = GameMatch::create([
            'competition_id' => $comp->id, 'phase' => 'knockout', 'round' => 'QF',
            'round_position' => 0, 'player_a_id' => $a->id, 'player_b_id' => $b->id,
            'score_a' => 3, 'score_b' => 1, 'status' => 'live', 'started_at' => now(),
        ]);
        $upcoming = GameMatch::create([
            'competition_id' => $comp->id, 'phase' => 'knockout', 'round' => 'QF',
            'round_position' => 1, 'player_a_id' => $c->id, 'player_b_id' => $d->id,
            'status' => 'scheduled',
        ]);

        $this->actingAs($admin)
            ->get('/admin/scoring')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/LiveScoring')
                ->has('liveMatches', 1)
                ->has('upcomingMatches', 1)
                ->where('liveMatches.0.id', $live->id)
                ->where('liveMatches.0.race_to', 9)
                ->where('upcomingMatches.0.id', $upcoming->id));
    }

    // -------------------------------------------------------------------------
    // 2. Closing a live pool match keeps the current score (the fixed bug)
    // -------------------------------------------------------------------------

    public function test_close_live_pool_match_saves_score(): void
    {
        $admin = $this->makeAdmin();
        $comp  = $this->makeCompetition();
        $pool  = Pool::create(['competition_id' => $comp->id, 'name' => 'A', 'position' => 0, 'size' => 4]);
        [$a, $b] = $this->makePlayers(2);

        $match = GameMatch::create([
            'competition_id' => $comp->id, 'pool_id' => $pool->id, 'phase' => 'pool',
            'round' => 'RR', 'round_position' => 0,
            'player_a_id' => $a->id, 'player_b_id' => $b->id,
            'score_a' => 5, 'score_b' => 3, 'status' => 'live', 'started_at' => now()->subMinutes(5),
        ]);

        // Close with the live score (what the fixed modal now pre-fills)
        $this->actingAs($admin)
            ->patch("/admin/poules/matchs/{$match->id}", ['score_a' => 5, 'score_b' => 3])
            ->assertRedirect();

        $match->refresh();
        $this->assertSame('done', $match->status);
        $this->assertSame(5, (int) $match->score_a);
        $this->assertSame(3, (int) $match->score_b);
    }

    // -------------------------------------------------------------------------
    // 3. Closing a live knockout match keeps the current score
    // -------------------------------------------------------------------------

    public function test_close_live_knockout_match_saves_score(): void
    {
        $admin = $this->makeAdmin();
        $comp  = $this->makeCompetition();
        [$a, $b] = $this->makePlayers(2);

        $match = GameMatch::create([
            'competition_id' => $comp->id, 'phase' => 'knockout', 'round' => 'SF',
            'round_position' => 0, 'player_a_id' => $a->id, 'player_b_id' => $b->id,
            'score_a' => 9, 'score_b' => 6, 'status' => 'live', 'started_at' => now()->subMinutes(5),
        ]);

        $this->actingAs($admin)
            ->post("/admin/phase-finale/matchs/{$match->id}/clore", ['score_a' => 9, 'score_b' => 6])
            ->assertRedirect();

        $match->refresh();
        $this->assertSame('done', $match->status);
        $this->assertSame(9, (int) $match->score_a);
        $this->assertSame(6, (int) $match->score_b);
    }

    // -------------------------------------------------------------------------
    // 4. Frame scoring increments the live score
    // -------------------------------------------------------------------------

    public function test_frame_increments_live_score(): void
    {
        $admin = $this->makeAdmin();
        $comp  = $this->makeCompetition();
        [$a, $b] = $this->makePlayers(2);

        $match = GameMatch::create([
            'competition_id' => $comp->id, 'phase' => 'knockout', 'round' => 'QF',
            'round_position' => 0, 'player_a_id' => $a->id, 'player_b_id' => $b->id,
            'score_a' => 2, 'score_b' => 1, 'status' => 'live', 'started_at' => now(),
        ]);

        $this->actingAs($admin)
            ->post("/admin/phase-finale/matchs/{$match->id}/frame", ['player' => 'A']);

        $this->assertSame(3, (int) $match->fresh()->score_a);
    }
}
