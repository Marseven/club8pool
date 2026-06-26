<?php

namespace Tests\Feature;

use App\Models\Competition;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_application_returns_a_successful_response(): void
    {
        Competition::create([
            'name' => 'Test Competition',
            'slug' => 'test-comp',
            'discipline' => '8-ball',
            'format' => 'single_elim',
            'structure' => 'knockout',
            'status' => 'in_progress',
            'race_to' => 7,
            'shot_clock' => 30,
        ]);

        $response = $this->get('/');

        $response->assertStatus(200);
    }
}
