<?php
namespace Tests\Feature\Public;

use App\Models\Competition;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicCompetitionNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_competitions_index_returns_200_when_no_competitions(): void
    {
        $response = $this->get('/competitions');
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Public/Competitions'));
    }

    public function test_competitions_index_lists_all_competitions(): void
    {
        Competition::create([
            'name' => 'Test Cup A', 'slug' => 'test-cup-a', 'discipline' => '8-ball',
            'format' => 'pools', 'structure' => 'pools_knockout', 'status' => 'in_progress',
            'race_to' => 3,
        ]);
        Competition::create([
            'name' => 'Test Cup B', 'slug' => 'test-cup-b', 'discipline' => '8-ball',
            'format' => 'pools', 'structure' => 'pools_knockout', 'status' => 'finished',
            'race_to' => 3,
        ]);

        $response = $this->get('/competitions');
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) =>
            $page->component('Public/Competitions')
                 ->has('grouped')
                 ->where('total', 2)
        );
    }

    public function test_competition_show_by_slug(): void
    {
        Competition::create([
            'name' => 'Show Cup', 'slug' => 'show-cup', 'discipline' => '8-ball',
            'format' => 'pools', 'structure' => 'pools_knockout', 'status' => 'in_progress',
            'race_to' => 3,
        ]);

        $response = $this->get('/competitions/show-cup');
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Public/Competition'));
    }

    public function test_competition_show_returns_404_for_unknown_slug(): void
    {
        $response = $this->get('/competitions/unknown-slug-xyz');
        $response->assertStatus(404);
    }

    public function test_joueurs_page_returns_200_with_no_active_competition(): void
    {
        $response = $this->get('/joueurs');
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Public/Players'));
    }

    public function test_arbitre_login_redirects_to_login(): void
    {
        $response = $this->get('/arbitre/login');
        $response->assertRedirectContains('/login');
    }
}
