<?php

/**
 * Demo Tournament Seeder — Club 8 Pool
 *
 * Provides a realistic in-progress competition for QA testing.
 * All data is entirely fictional (no real PII, no real persons).
 *
 * Run with:
 *   php artisan db:seed --class=DemoTournamentSeeder
 *
 * The seeder is idempotent: safe to run multiple times thanks to
 * firstOrCreate() on every record.
 *
 * ⚠  NOT wired into DatabaseSeeder — must be called explicitly.
 */

namespace Database\Seeders;

use App\Models\Competition;
use App\Models\GameMatch;
use App\Models\Player;
use App\Models\PlayerRating;
use App\Models\Pool;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoTournamentSeeder extends Seeder
{
    // -----------------------------------------------------------------
    // Fictional player data — no real PII
    // -----------------------------------------------------------------
    private array $playerData = [
        // [ first_name, last_name, fgb_card, rating ]
        ['Jean',  'KOUASSI', 'FGB-DEMO-001', 1700],
        ['Marc',  'DIALLO',  'FGB-DEMO-002', 1600],
        ['Ahmed', 'BARRY',   'FGB-DEMO-003', 1550],
        ['Pierre','ASSI',    'FGB-DEMO-004', 1480],
        ['Kwame', 'MENSAH',  'FGB-DEMO-005', 1430],
        ['Louis', 'TRAORE',  'FGB-DEMO-006', 1370],
        ['Denis', 'GBEKE',   'FGB-DEMO-007', 1300],
        ['Paul',  'YAPI',    'FGB-DEMO-008', 1200],
    ];

    public function run(): void
    {
        // ----------------------------------------------------------
        // 1. Competition
        // ----------------------------------------------------------
        /** @var Competition $competition */
        $competition = Competition::firstOrCreate(
            ['slug' => 'demo-cup-mrtech-2026'],
            [
                'name'                         => 'Demo Cup MRTECH 2026',
                'discipline'                   => '8-ball',
                'format'                       => 'pools',
                'structure'                    => 'pools_knockout',
                'status'                       => 'in_progress',
                'race_to'                      => 3,
                'pool_race_to'                 => 3,
                'knockout_race_to'             => 5,
                'player_slots'                 => 8,
                'pool_count'                   => 2,
                'pool_size'                    => 4,
                'qualifiers_per_pool'          => 2,
                'shot_clock_enabled'           => true,
                'shot_clock'                   => 30,
                'shot_clock_late_seconds'      => 15,
                'shot_clock_late_rule'         => 'hill',
                'shot_clock_extensions_per_player' => 1,
                'seed_strategy'                => 'rating',
                'seeded_players_count'         => 4,
                'draw_randomize_unseeded'      => true,
                'alternate_break'              => true,
                'allow_draw'                   => false,
                'enable_warnings'              => true,
                'push_out'                     => false,
                'push_out_enabled'             => false,
                'frame_pause'                  => 60,
                'tiebreak_race'                => 3,
                'tie_break_mode'               => 'race_to_one',
                'rack_mode'                    => 'template',
                'venue'                        => 'Salle MRTECH',
                'city'                         => 'Abidjan',
                'entry_fee'                    => 0,
                'deposit'                      => 0,
                'prize_pool'                   => 0,
                'starts_on'                    => now()->toDateString(),
                'ends_on'                      => now()->addDays(1)->toDateString(),
            ]
        );

        // ----------------------------------------------------------
        // 2. Players (8 fictional, no real PII)
        // ----------------------------------------------------------
        $players = [];
        foreach ($this->playerData as [$firstName, $lastName, $fgbCard, $rating]) {
            $player = Player::firstOrCreate(
                ['fgb_card' => $fgbCard],
                [
                    'first_name' => $firstName,
                    'last_name'  => $lastName,
                    'phone'      => '00000000',        // fictitious
                    'email'      => 'demo@example.com',// fictitious
                    'birthdate'  => null,
                    'address'    => null,
                    'cue'        => null,
                    'rating'     => $rating,
                    'wins'       => 0,
                    'losses'     => 0,
                ]
            );
            $players[] = $player;
        }

        // ----------------------------------------------------------
        // 3. Pools  (A = position 0, B = position 1)
        // ----------------------------------------------------------
        /** @var Pool $poolA */
        $poolA = Pool::firstOrCreate(
            ['competition_id' => $competition->id, 'name' => 'A'],
            ['position' => 0, 'size' => 4]
        );

        /** @var Pool $poolB */
        $poolB = Pool::firstOrCreate(
            ['competition_id' => $competition->id, 'name' => 'B'],
            ['position' => 1, 'size' => 4]
        );

        // ----------------------------------------------------------
        // 4. Registrations — Pool A: players[0–3], Pool B: players[4–7]
        //    Sorted by descending rating (seed_strategy = 'rating'):
        //      Pool A: Jean KOUASSI(1700), Ahmed BARRY(1550),
        //              Kwame MENSAH(1430), Denis GBEKE(1300)
        //      Pool B: Marc DIALLO(1600), Pierre ASSI(1480),
        //              Louis TRAORE(1370), Paul YAPI(1200)
        // ----------------------------------------------------------
        $poolAPlayers = [$players[0], $players[2], $players[4], $players[6]]; // 1700,1550,1430,1300
        $poolBPlayers = [$players[1], $players[3], $players[5], $players[7]]; // 1600,1480,1370,1200

        foreach ($poolAPlayers as $slot => $player) {
            Registration::firstOrCreate(
                ['competition_id' => $competition->id, 'player_id' => $player->id],
                [
                    'pool_id'       => $poolA->id,
                    'pool_slot'     => $slot + 1,
                    'seed'          => $slot + 1,
                    'seed_rating'   => $player->rating,
                    'status'        => 'confirmed',
                    'registered_at' => now()->subDays(3),
                ]
            );
        }

        foreach ($poolBPlayers as $slot => $player) {
            Registration::firstOrCreate(
                ['competition_id' => $competition->id, 'player_id' => $player->id],
                [
                    'pool_id'       => $poolB->id,
                    'pool_slot'     => $slot + 1,
                    'seed'          => $slot + 1,
                    'seed_rating'   => $player->rating,
                    'status'        => 'confirmed',
                    'registered_at' => now()->subDays(3),
                ]
            );
        }

        // ----------------------------------------------------------
        // 5 & 6. Pool matches — full round-robin (6 per pool = 12 total)
        //   Pool A combos: (0v1),(0v2),(0v3),(1v2),(1v3),(2v3)
        //   Pool B combos: (0v1),(0v2),(0v3),(1v2),(1v3),(2v3)
        //
        //   Pool A: 3 done, 3 scheduled (one of the done = live)
        //   Pool B: 2 done, 4 scheduled
        // ----------------------------------------------------------
        $this->createPoolMatches($competition, $poolA, $poolAPlayers);
        $this->createPoolMatches($competition, $poolB, $poolBPlayers);

        // ----------------------------------------------------------
        // 7. Referee users (idempotent)
        // ----------------------------------------------------------
        User::firstOrCreate(
            ['email' => 'ali.demo@club8pool.local'],
            [
                'name'     => 'Ali Demo',
                'password' => Hash::make('DemoPass2026!'),
                'fgb_card' => 'FGB-ARB-DEMO-01',
                'pin'      => Hash::make('1234'),
                'role'     => 'referee',
                'title'    => 'Arbitre Demo',
            ]
        );

        User::firstOrCreate(
            ['email' => 'sam.demo@club8pool.local'],
            [
                'name'     => 'Sam Demo',
                'password' => Hash::make('DemoPass2026!'),
                'fgb_card' => 'FGB-ARB-DEMO-02',
                'pin'      => Hash::make('5678'),
                'role'     => 'referee',
                'title'    => 'Arbitre Demo',
            ]
        );

        // Admin fallback (only created if no admin exists yet)
        if (! User::where('role', 'admin')->exists()) {
            User::firstOrCreate(
                ['email' => 'admin@club8pool.com'],
                [
                    'name'     => 'Admin Demo',
                    'password' => Hash::make('AdminDemo2026!'),
                    'role'     => 'admin',
                    'title'    => 'Administrateur Demo',
                ]
            );
        }

        // ----------------------------------------------------------
        // 8. PlayerRating records for all 8 players in '8-ball'
        // ----------------------------------------------------------
        foreach ($players as $player) {
            PlayerRating::firstOrCreate(
                ['player_id' => $player->id, 'discipline' => '8-ball'],
                [
                    'rating'       => $player->rating,
                    'games_played' => 0,
                    'frames_won'   => 0,
                    'frames_lost'  => 0,
                    'robustness'   => 0,
                    'provisional'  => true,
                    'last_match_at'=> null,
                ]
            );
        }

        $this->command->info('DemoTournamentSeeder completed successfully.');
        $this->command->info('Competition: "Demo Cup MRTECH 2026" (slug: demo-cup-mrtech-2026)');
        $this->command->info('8 players, 2 pools, 12 matches (1 live), 2 referee accounts created.');
    }

    // -----------------------------------------------------------------
    // Helper — create 6 round-robin matches for a 4-player pool
    // -----------------------------------------------------------------
    private function createPoolMatches(
        Competition $competition,
        Pool        $pool,
        array       $players   // 4 Player models
    ): void {
        // All C(4,2) = 6 pairs
        $pairs = [
            [0, 1], [0, 2], [0, 3],
            [1, 2], [1, 3], [2, 3],
        ];

        // Pool A: 3 done, 3 scheduled (match index 2 = live)
        // Pool B: 2 done, 4 scheduled
        $doneCount = ($pool->name === 'A') ? 3 : 2;
        $liveIndex = ($pool->name === 'A') ? 2 : null; // match index that becomes 'live'

        // Realistic completed scores for pool phase (race_to=3)
        $doneScores = [
            [3, 1],   // pair 0
            [3, 2],   // pair 1
            [2, 3],   // pair 2 (this one becomes 'live' for Pool A)
            [3, 0],   // pair 3
            [1, 3],   // pair 4
        ];

        foreach ($pairs as $pairIndex => [$ia, $ib]) {
            $playerA = $players[$ia];
            $playerB = $players[$ib];

            // Unique key: competition + pool + both players
            $existing = GameMatch::where('competition_id', $competition->id)
                ->where('pool_id', $pool->id)
                ->where('player_a_id', $playerA->id)
                ->where('player_b_id', $playerB->id)
                ->first();

            if ($existing) {
                continue; // idempotent — already seeded
            }

            $isDone      = $pairIndex < $doneCount;
            $isLive      = ($liveIndex !== null && $pairIndex === $liveIndex);
            $isScheduled = ! $isDone && ! $isLive;

            if ($isLive) {
                $status  = 'live';
                $scoreA  = 2;
                $scoreB  = 1;
                $startedAt = now()->subMinutes(15);
                $endedAt   = null;
            } elseif ($isDone) {
                $status  = 'done';
                $scores  = $doneScores[$pairIndex] ?? [3, 1];
                $scoreA  = $scores[0];
                $scoreB  = $scores[1];
                $startedAt = now()->subHours(rand(2, 5));
                $endedAt   = now()->subHours(rand(1, 2));
            } else {
                $status    = 'scheduled';
                $scoreA    = 0;
                $scoreB    = 0;
                $startedAt = null;
                $endedAt   = null;
            }

            GameMatch::create([
                'competition_id'  => $competition->id,
                'pool_id'         => $pool->id,
                'phase'           => 'pool',
                'round'           => 'R16',  // round field required (enum default)
                'round_position'  => $pairIndex,
                'player_a_id'     => $playerA->id,
                'player_b_id'     => $playerB->id,
                'score_a'         => $scoreA,
                'score_b'         => $scoreB,
                'status'          => $status,
                'scheduled_at'    => $isScheduled ? now()->addHours(rand(1, 4)) : null,
                'started_at'      => $startedAt,
                'ended_at'        => $endedAt,
                'warning_a'       => false,
                'warning_b'       => false,
                'is_draw'         => false,
            ]);
        }
    }
}
