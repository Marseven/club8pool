<?php

namespace Database\Seeders;

use App\Models\Club;
use App\Models\Competition;
use App\Models\Player;
use App\Models\Pool;
use App\Models\PoolTable;
use App\Models\Registration;
use App\Models\User;
use App\Services\RoundRobinGenerator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Comptes
        User::firstOrCreate(['email' => 'admin@club8pool.ga'], [
            'name'     => 'Admin',
            'password' => Hash::make('password'),
            'role'     => 'admin',
            'title'    => 'Directeur de compétition',
        ]);

        User::firstOrCreate(['email' => 'eric@club8pool.ga'], [
            'name'     => 'Eric',
            'fgb_card' => 'ICN-ARB-001',
            'pin'      => Hash::make('12345'),
            'password' => Hash::make('password'),
            'role'     => 'referee',
            'title'    => 'Arbitre',
        ]);

        User::firstOrCreate(['email' => 'tone@club8pool.ga'], [
            'name'     => 'T-One',
            'fgb_card' => 'ICN-ARB-002',
            'pin'      => Hash::make('12345'),
            'password' => Hash::make('password'),
            'role'     => 'referee',
            'title'    => 'Arbitre',
        ]);

        $club = Club::firstOrCreate(['slug' => 'icone-pool'], [
            'name' => 'Icone Pool',
            'city' => 'Libreville',
        ]);

        // Liste des joueurs par poule (cf. fichier Excel)
        $pools = [
            'A' => ['Youssef', 'Aziz', 'Mitch', 'Philippe', 'Zouzou', 'Attiss', 'Haidara'],
            'B' => ['MHD', 'Eric', 'Danny', 'Arnaud', 'Alec', 'Paolo', 'Wallas'],
            'C' => ['Maud', 'Virgile', 'Mohamed', 'Khaled', 'Anne', 'JD', 'Toto'],
            'D' => ['Amaury', 'Dimitri', 'Hakim', 'Hani', 'Zhao', 'Dr Joel', 'Ismael'],
        ];

        $competition = Competition::firstOrCreate(['slug' => 'icone-pool-championship-2026'], [
            'name' => 'Icone Pool Championship',
            'slug' => 'icone-pool-championship-2026',
            'discipline' => '8-ball',
            'format' => 'pools',
            'structure' => 'pools_knockout',
            'player_slots' => 28,
            'pool_count' => 4,
            'pool_size' => 7,
            'qualifiers_per_pool' => 4,
            'race_to' => 3,
            'pool_race_to' => 3,
            'knockout_race_to' => 7,
            'shot_clock' => 30,
            'alternate_break' => true,
            'allow_draw' => true,
            'enable_warnings' => true,
            'push_out' => false,
            'frame_pause' => 60,
            'tiebreak_race' => 5,
            'venue' => 'Salle Icone, Libreville',
            'city' => 'Libreville',
            'entry_fee' => 20000,
            'deposit' => 2000,
            'prize_pool' => 600000,
            'starts_on' => '2026-05-24',
            'ends_on' => '2026-05-27',
            'registration_closes_at' => '2026-05-23 18:00:00',
            'status' => 'in_progress',
            'settings' => [
                // Jours effectivement joués (24, 25, 27 mai — pause mardi 26)
                'play_days' => ['2026-05-24', '2026-05-25', '2026-05-27'],
                'rest_days' => ['2026-05-26'],
                // Horaires souples : pas de timing strict, on suit le rythme terrain
                'schedule_strict' => false,
            ],
        ]);

        // Tables
        $tableModels = collect();
        foreach ([
            ['Table 1', 'Salle principale', 'live'],
            ['Table 2', 'Salle principale', 'live'],
        ] as $t) {
            $tableModels->push(PoolTable::firstOrCreate(
                ['competition_id' => $competition->id, 'name' => $t[0]],
                ['location' => $t[1], 'status' => $t[2]]
            ));
        }

        // Création des joueurs et des poules + inscriptions
        $playersByLabel = [];
        $fgbIndex = 1;
        foreach ($pools as $poolName => $names) {
            $pool = Pool::firstOrCreate(
                ['competition_id' => $competition->id, 'name' => $poolName],
                ['position' => ord($poolName) - ord('A'), 'size' => count($names)]
            );

            foreach ($names as $slot => $name) {
                $parts = explode(' ', $name);
                $first = $parts[0];
                $last  = count($parts) > 1 ? implode(' ', array_slice($parts, 1)) : '';
                $fgb   = 'ICN-2026-' . sprintf('%03d', $fgbIndex++);

                $player = Player::firstOrCreate(
                    ['fgb_card' => $fgb],
                    [
                        'first_name' => $first,
                        'last_name'  => $last !== '' ? strtoupper($last) : '',
                        'club_id'    => $club->id,
                        'phone'      => null,
                        'email'      => null,
                        'birthdate'  => null,
                        'cue'        => null,
                        'address'    => null,
                        'rating'     => 1500,
                        'wins'       => 0,
                        'losses'     => 0,
                    ]
                );

                Registration::firstOrCreate(
                    ['competition_id' => $competition->id, 'player_id' => $player->id],
                    [
                        'pool_id'      => $pool->id,
                        'pool_slot'    => $slot + 1,
                        'seed'         => null,
                        'status'       => 'paid',
                        'registered_at'=> now()->subDays(rand(5, 20)),
                    ]
                );

                $playersByLabel[$poolName . ($slot + 1)] = $player;
                $playersByLabel[$name] = $player;
            }

            // Génère les matchs seulement si la poule n'en a pas encore
            if ($pool->matches()->count() === 0) {
                RoundRobinGenerator::generate($pool);
            }
        }

        // Aucun match joué pour l'instant : tous les 84 matchs de poule
        // restent en statut 'scheduled' (créés par RoundRobinGenerator
        // dans la boucle ci-dessus), sans horaire fixé, sans table
        // ni arbitre assigné. L'admin programmera ça en cours de
        // tournoi via /admin/poules ▶ Démarrer.
    }
}
