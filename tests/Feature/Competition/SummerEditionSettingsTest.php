<?php

namespace Tests\Feature\Competition;

use App\Models\Competition;
use App\Models\GameMatch;
use App\Models\Player;
use App\Services\KnockoutGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Tests for Summer Edition settings: round_race_to, prize_breakdown,
 * schedule, payment info, and API exposure of race_to.
 */
class SummerEditionSettingsTest extends TestCase
{
    use RefreshDatabase;

    private function makeSummerComp(array $overrides = []): Competition
    {
        return Competition::create(array_merge([
            'name'             => 'Summer Edition Test',
            'slug'             => 'summer-edition-test-' . Str::random(4),
            'discipline'       => '8-ball',
            'format'           => 'pools',
            'structure'        => 'pools_knockout',
            'race_to'          => 7,
            'pool_race_to'     => 4,
            'knockout_race_to' => 7,
            'status'           => 'draft',
            'player_slots'     => 48,
        ], $overrides));
    }

    // ── 1. round_race_to ──────────────────────────────────────────────────────

    public function it_stores_round_race_to_settings(): void
    {
        $comp = $this->makeSummerComp([
            'settings' => [
                'round_race_to' => [
                    'R32' => 7, 'R16' => 7, 'QF' => 9, 'SF' => 9, '3P' => 5, 'F' => 11,
                ],
            ],
        ]);

        $this->assertEquals(['R32' => 7, 'R16' => 7, 'QF' => 9, 'SF' => 9, '3P' => 5, 'F' => 11],
            $comp->fresh()->settings['round_race_to']);
    }

    public function test_it_returns_round_specific_race_to(): void
    {
        $comp = $this->makeSummerComp([
            'knockout_race_to' => 7,
            'settings' => [
                'round_race_to' => [
                    'R32' => 7, 'R16' => 7, 'QF' => 9, 'SF' => 9, '3P' => 5, 'F' => 11,
                ],
            ],
        ]);

        $this->assertSame(7,  $comp->raceForRound('R32'));
        $this->assertSame(7,  $comp->raceForRound('R16'));
        $this->assertSame(9,  $comp->raceForRound('QF'));
        $this->assertSame(9,  $comp->raceForRound('SF'));
        $this->assertSame(5,  $comp->raceForRound('3P'));
        $this->assertSame(11, $comp->raceForRound('F'));
    }

    public function test_it_falls_back_to_knockout_race_to(): void
    {
        $comp = $this->makeSummerComp(['knockout_race_to' => 9]); // no round_race_to
        $this->assertSame(9, $comp->raceForRound('QF'));
        $this->assertSame(9, $comp->raceForRound('F'));
        $this->assertSame(9, $comp->raceForRound('3P'));
    }

    public function test_it_assigns_or_exposes_race_to_for_third_place_match(): void
    {
        $comp = $this->makeSummerComp([
            'knockout_race_to' => 7,
            'status'           => 'in_progress',
            'settings' => [
                'has_third_place_match' => true,
                'round_race_to' => ['R32' => 7, 'R16' => 7, 'QF' => 9, 'SF' => 9, '3P' => 5, 'F' => 11],
            ],
        ]);

        // Build a minimal R32 bracket
        $pairs = [];
        for ($i = 0; $i < 16; $i++) {
            $a = Player::create(['first_name' => "PA{$i}", 'last_name' => 'T']);
            $b = Player::create(['first_name' => "PB{$i}", 'last_name' => 'T']);
            $pairs[] = [['player_id' => $a->id], ['player_id' => $b->id]];
        }
        (new KnockoutGenerator())->generate($comp, $pairs);

        $thirdPlace = GameMatch::where('competition_id', $comp->id)->where('round', '3P')->first();
        $this->assertNotNull($thirdPlace, '3P match must exist');
        $this->assertSame(5, $comp->raceForRound('3P'));
    }

    // ── 2. prize_breakdown ───────────────────────────────────────────────────

    public function test_it_stores_prize_breakdown_without_amounts(): void
    {
        $breakdown = [
            '1st' => ['label' => 'Champion',        'amount' => null, 'currency' => 'XAF'],
            '2nd' => ['label' => 'Finaliste',       'amount' => null, 'currency' => 'XAF'],
            '3rd' => ['label' => 'Troisième place', 'amount' => null, 'currency' => 'XAF'],
            '4th' => ['label' => 'Quatrième place', 'amount' => null, 'currency' => 'XAF'],
        ];

        $comp = $this->makeSummerComp(['settings' => ['prize_breakdown' => $breakdown]]);

        $this->assertEquals($breakdown, $comp->fresh()->prizeBreakdown());
    }

    public function test_prize_breakdown_stores_known_amounts(): void
    {
        $breakdown = [
            '1st'     => ['label' => 'Champion',     'amount' => 500000,  'currency' => 'XAF'],
            '2nd'     => ['label' => 'Finaliste',    'amount' => 250000,  'currency' => 'XAF'],
            '3rd'     => ['label' => 'Troisième',    'amount' => 150000,  'currency' => 'XAF'],
            '4th'     => ['label' => 'Quatrième',    'amount' => 100000,  'currency' => 'XAF'],
            '5th-8th' => ['label' => '5e–8e place',  'amount_each' => 30000, 'players' => 4, 'currency' => 'XAF'],
        ];

        $comp = $this->makeSummerComp(['settings' => ['prize_breakdown' => $breakdown]]);
        $stored = $comp->fresh()->prizeBreakdown();

        $this->assertEquals(500000,  $stored['1st']['amount']);
        $this->assertEquals(250000,  $stored['2nd']['amount']);
        $this->assertEquals(30000,   $stored['5th-8th']['amount_each']);
        $this->assertNull($stored['1st']['amount'] - 500000 ?: null); // sanity
    }

    public function test_prize_breakdown_returns_empty_array_when_not_set(): void
    {
        $comp = $this->makeSummerComp();
        $this->assertSame([], $comp->prizeBreakdown());
    }

    // ── 3. schedule ──────────────────────────────────────────────────────────

    public function test_it_stores_schedule_with_nullable_dates(): void
    {
        $schedule = [
            'timezone'              => 'Africa/Libreville',
            'registration_deadline' => null,
            'days' => [
                [
                    'date'  => '2026-07-04',
                    'label' => 'Samedi 4 juillet 2026',
                    'items' => [
                        ['phase' => 'pools', 'label' => 'Poules A–C', 'pool_range' => ['A', 'C'], 'starts_at' => null, 'ends_at' => null],
                    ],
                ],
                [
                    'date'  => null,     // date non confirmée
                    'label' => 'Phase finale',
                    'items' => [
                        ['phase' => 'knockout', 'rounds' => ['QF', 'SF', '3P', 'F'], 'starts_at' => null, 'ends_at' => null],
                    ],
                ],
            ],
        ];

        $comp = $this->makeSummerComp(['settings' => ['schedule' => $schedule]]);
        $stored = $comp->fresh()->competitionSchedule();

        $this->assertEquals('Africa/Libreville', $stored['timezone']);
        $this->assertNull($stored['registration_deadline']);
        $this->assertCount(2, $stored['days']);
        $this->assertNull($stored['days'][1]['date']);
        $this->assertNull($stored['days'][0]['items'][0]['starts_at']);
    }

    public function test_schedule_preserves_timezone(): void
    {
        $comp = $this->makeSummerComp([
            'settings' => ['schedule' => ['timezone' => 'Africa/Libreville', 'days' => []]],
        ]);

        $this->assertEquals('Africa/Libreville', $comp->competitionSchedule()['timezone']);
    }

    public function test_schedule_returns_empty_array_when_not_set(): void
    {
        $comp = $this->makeSummerComp();
        $this->assertSame([], $comp->competitionSchedule());
    }

    // ── 4. payment ───────────────────────────────────────────────────────────

    public function test_it_stores_payment_info_with_nullable_values(): void
    {
        $payment = [
            'registration_fee' => null,
            'currency'         => 'XAF',
            'methods' => [
                ['type' => 'mobile_money', 'provider' => null, 'phone' => '077 79 10 57', 'account_name' => 'Dimitri'],
            ],
            'contacts' => [
                ['role' => 'Organisation', 'name' => 'Dimitri', 'phone' => '077 79 10 57', 'email' => null],
            ],
        ];

        $comp = $this->makeSummerComp(['settings' => ['payment' => $payment]]);
        $stored = $comp->fresh()->paymentInfo();

        $this->assertNull($stored['registration_fee']);
        $this->assertEquals('XAF', $stored['currency']);
        $this->assertEquals('077 79 10 57', $stored['methods'][0]['phone']);
        $this->assertNull($stored['methods'][0]['provider']);
        $this->assertNull($stored['contacts'][0]['email']);
    }

    public function test_payment_info_returns_empty_array_when_not_set(): void
    {
        $comp = $this->makeSummerComp();
        $this->assertSame([], $comp->paymentInfo());
    }

    // ── 5. Seeder idempotency ─────────────────────────────────────────────────

    public function test_it_keeps_summer_edition_seeder_idempotent(): void
    {
        $this->artisan('db:seed', ['--class' => 'SummerEditionSeeder'])->assertSuccessful();
        $countAfterFirst = Competition::where('slug', 'summer-edition')->count();

        $this->artisan('db:seed', ['--class' => 'SummerEditionSeeder'])->assertSuccessful();
        $countAfterSecond = Competition::where('slug', 'summer-edition')->count();

        $this->assertEquals(1, $countAfterFirst);
        $this->assertEquals(1, $countAfterSecond, 'Re-running seeder must not create duplicate competition');

        // Pool count stays at 8
        $comp = Competition::where('slug', 'summer-edition')->first();
        $this->assertEquals(8, $comp->pools()->count());

        // Registration count stays at 40 (5 players × 8 pools)
        $this->assertEquals(40, $comp->registrations()->count());
    }

    // ── 6. API: race_to in referee show ──────────────────────────────────────

    public function test_referee_show_includes_race_to(): void
    {
        $comp = $this->makeSummerComp([
            'status'           => 'in_progress',
            'knockout_race_to' => 7,
            'settings' => [
                'round_race_to' => ['R32' => 7, 'R16' => 7, 'QF' => 9, 'SF' => 9, '3P' => 5, 'F' => 11],
            ],
        ]);

        $player = Player::create(['first_name' => 'A', 'last_name' => 'B']);
        $match  = GameMatch::create([
            'competition_id' => $comp->id,
            'phase'          => 'knockout',
            'round'          => 'QF',
            'round_position' => 0,
            'status'         => 'scheduled',
            'player_a_id'    => $player->id,
        ]);

        $referee = \App\Models\User::create([
            'name'     => 'Ref Test',
            'email'    => 'ref-se@test.com',
            'password' => bcrypt('secret'),
            'role'     => 'referee',
            'pin'      => bcrypt('1234'),
        ]);

        $token = $referee->createToken('test')->plainTextToken;

        $response = $this->withHeaders(['Authorization' => "Bearer {$token}"])
            ->getJson("/api/referee/matches/{$match->id}");

        $response->assertOk();
        $response->assertJsonPath('race_to', 9);
    }

    public function test_referee_queue_includes_race_to_per_match(): void
    {
        $comp = $this->makeSummerComp([
            'status'           => 'in_progress',
            'knockout_race_to' => 7,
            'settings' => [
                'round_race_to' => ['R32' => 7, 'R16' => 7, 'QF' => 9, 'SF' => 9, '3P' => 5, 'F' => 11],
            ],
        ]);

        $referee = \App\Models\User::create([
            'name'     => 'QueueRef',
            'email'    => 'queueref@test.com',
            'password' => bcrypt('secret'),
            'role'     => 'referee',
            'pin'      => bcrypt('1234'),
        ]);

        GameMatch::create([
            'competition_id' => $comp->id,
            'phase'          => 'knockout',
            'round'          => 'F',
            'round_position' => 0,
            'status'         => 'scheduled',
            'referee_id'     => $referee->id,
        ]);

        $token = $referee->createToken('test')->plainTextToken;

        $response = $this->withHeaders(['Authorization' => "Bearer {$token}"])
            ->getJson('/api/referee/queue');

        $response->assertOk();
        $data = $response->json();
        $this->assertArrayHasKey('race_to', $data[0]);
        $this->assertSame(11, $data[0]['race_to']);
    }
}
