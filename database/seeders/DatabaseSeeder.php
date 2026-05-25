<?php

namespace Database\Seeders;

use App\Models\Club;
use App\Models\Competition;
use App\Models\GameMatch;
use App\Models\Player;
use App\Models\Pool;
use App\Models\PoolTable;
use App\Models\Registration;
use App\Models\User;
use App\Services\RoundRobinGenerator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Comptes
        User::create([
            'name' => 'Admin',
            'email' => 'admin@club8pool.ga',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'title' => 'Directeur de compétition',
        ]);

        $refOlivier = User::create([
            'name' => 'Olivier Kombila',
            'email' => 'olivier@club8pool.ga',
            'fgb_card' => 'FGB-ARB-2026-0024',
            'pin' => Hash::make('12345'),
            'password' => Hash::make('password'),
            'role' => 'referee',
            'title' => 'Arbitre principal',
        ]);

        $refFranck = User::create([
            'name' => 'Franck Ndong',
            'email' => 'franck@club8pool.ga',
            'fgb_card' => 'FGB-ARB-2026-0031',
            'pin' => Hash::make('12345'),
            'password' => Hash::make('password'),
            'role' => 'referee',
            'title' => 'Arbitre',
        ]);

        $club = Club::create([
            'name' => 'Icone Pool',
            'city' => 'Libreville',
            'slug' => 'icone-pool',
        ]);

        // Liste des joueurs par poule (cf. fichier Excel)
        $pools = [
            'A' => ['Youssef', 'Aziz', 'Mitch', 'Philippe', 'Zouzou', 'Attiss', 'Haidara'],
            'B' => ['MHD', 'Eric', 'Danny', 'Arnaud', 'Alec', 'Paolo', 'Wallas'],
            'C' => ['Maud', 'Virgile', 'Mohamed', 'Khaled', 'Anne', 'JD', 'Toto'],
            'D' => ['Amaury', 'Dimitri', 'Hakim', 'Hani', 'Zhao', 'Dr Joel', 'Ismael'],
        ];

        $competition = Competition::create([
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
            'entry_fee' => 10000,
            'deposit' => 5000,
            'prize_pool' => 600000,
            'starts_on' => '2026-06-05',
            'ends_on' => '2026-06-08',
            'registration_closes_at' => '2026-06-04 18:00:00',
            'status' => 'in_progress',
        ]);

        // Tables
        $tableModels = collect();
        foreach ([
            ['Table 1', 'Salle principale', 'live'],
            ['Table 2', 'Salle principale', 'live'],
        ] as $t) {
            $tableModels->push(PoolTable::create([
                'competition_id' => $competition->id,
                'name' => $t[0],
                'location' => $t[1],
                'status' => $t[2],
            ]));
        }

        // Création des joueurs et des poules + inscriptions
        $playersByLabel = [];
        foreach ($pools as $poolName => $names) {
            $pool = Pool::create([
                'competition_id' => $competition->id,
                'name' => $poolName,
                'position' => ord($poolName) - ord('A'),
                'size' => count($names),
            ]);

            foreach ($names as $slot => $name) {
                $parts = explode(' ', $name);
                $first = $parts[0];
                $last = count($parts) > 1 ? implode(' ', array_slice($parts, 1)) : '';

                $player = Player::create([
                    'first_name' => $first,
                    'last_name' => $last !== '' ? strtoupper($last) : '',
                    'club_id' => $club->id,
                    'fgb_card' => 'ICN-2026-' . sprintf('%03d', count($playersByLabel) + 1),
                    'phone' => null,
                    'email' => null,
                    'birthdate' => null,
                    'cue' => null,
                    'address' => null,
                    'rating' => 1500,
                    'wins' => 0,
                    'losses' => 0,
                ]);

                Registration::create([
                    'competition_id' => $competition->id,
                    'pool_id' => $pool->id,
                    'pool_slot' => $slot + 1,
                    'player_id' => $player->id,
                    'seed' => null,
                    'status' => 'paid',
                    'registered_at' => now()->subDays(rand(5, 20)),
                ]);

                $playersByLabel[$poolName . ($slot + 1)] = $player;
                $playersByLabel[$name] = $player;
            }

            RoundRobinGenerator::generate($pool);
        }

        // Application des scores connus (fichier Excel)
        $applyScore = function (string $key1, string $key2, ?int $sa, ?int $sb, $opts = []) use ($playersByLabel) {
            $a = $playersByLabel[$key1] ?? null;
            $b = $playersByLabel[$key2] ?? null;
            if (! $a || ! $b) return;

            $match = GameMatch::where('phase', 'pool')
                ->where(function ($q) use ($a, $b) {
                    $q->where(function ($q) use ($a, $b) {
                        $q->where('player_a_id', $a->id)->where('player_b_id', $b->id);
                    })->orWhere(function ($q) use ($a, $b) {
                        $q->where('player_a_id', $b->id)->where('player_b_id', $a->id);
                    });
                })->first();

            if (! $match) return;

            $swap = $match->player_a_id !== $a->id;
            $scoreA = $swap ? $sb : $sa;
            $scoreB = $swap ? $sa : $sb;

            $isDraw = $opts['draw'] ?? ($sa !== null && $sb !== null && $sa === $sb);
            $match->update([
                'score_a' => $scoreA ?? 0,
                'score_b' => $scoreB ?? 0,
                'status' => 'done',
                'is_draw' => $isDraw,
                'warning_a' => $swap ? ($opts['warning_b'] ?? false) : ($opts['warning_a'] ?? false),
                'warning_b' => $swap ? ($opts['warning_a'] ?? false) : ($opts['warning_b'] ?? false),
                'started_at' => now()->subHours(rand(3, 24)),
                'ended_at' => now()->subHours(rand(1, 2)),
                'duration_seconds' => rand(900, 1800),
            ]);
        };

        // POULE A — selon fichier Excel
        $applyScore('Youssef', 'Aziz', 3, 1);
        $applyScore('Youssef', 'Mitch', 1, 3);
        $applyScore('Youssef', 'Philippe', 3, 1);
        $applyScore('Youssef', 'Zouzou', 0, 3);
        $applyScore('Youssef', 'Attiss', 3, 0);
        $applyScore('Youssef', 'Haidara', 3, 0);
        $applyScore('Aziz', 'Mitch', 0, 3);
        $applyScore('Aziz', 'Philippe', 2, 3);
        $applyScore('Aziz', 'Zouzou', 0, 3);
        $applyScore('Aziz', 'Attiss', 3, 1);
        $applyScore('Mitch', 'Philippe', 2, 3);
        $applyScore('Mitch', 'Zouzou', 2, 3);
        $applyScore('Mitch', 'Attiss', 1, 1, ['draw' => true]);
        $applyScore('Mitch', 'Haidara', 3, 1);
        $applyScore('Philippe', 'Zouzou', 3, 1);
        $applyScore('Philippe', 'Haidara', 3, 0);
        $applyScore('Zouzou', 'Attiss', 3, 2);
        $applyScore('Zouzou', 'Haidara', 3, 0);
        $applyScore('Attiss', 'Haidara', 3, 1);

        // POULE B — seul match avec scores
        $applyScore('MHD', 'Arnaud', 3, 1);

        // POULE C — complète
        $applyScore('Maud', 'Virgile', 3, 1);
        $applyScore('Maud', 'Mohamed', 2, 3);
        $applyScore('Maud', 'Khaled', 3, 0);
        $applyScore('Maud', 'Anne', 0, 3);
        $applyScore('Maud', 'JD', 3, 1, ['warning_a' => true]);
        $applyScore('Maud', 'Toto', 3, 2);
        $applyScore('Virgile', 'Mohamed', 1, 3);
        $applyScore('Virgile', 'Khaled', 1, 3);
        $applyScore('Virgile', 'Anne', 0, 3);
        $applyScore('Virgile', 'JD', 0, 3);
        $applyScore('Virgile', 'Toto', 1, 3);
        $applyScore('Mohamed', 'Khaled', 2, 3);
        $applyScore('Mohamed', 'Anne', 3, 2);
        $applyScore('Mohamed', 'JD', 3, 0);
        $applyScore('Mohamed', 'Toto', 3, 1);
        $applyScore('Khaled', 'Anne', 3, 1);
        $applyScore('Khaled', 'JD', 2, 3);
        $applyScore('Khaled', 'Toto', 0, 3);
        $applyScore('Anne', 'JD', 3, 0, ['warning_a' => true]);
        $applyScore('Anne', 'Toto', 3, 2);
        $applyScore('JD', 'Toto', 1, 3);

        // POULE D — matchs partiels
        $applyScore('Amaury', 'Dimitri', 1, 3);
        $applyScore('Amaury', 'Dr Joel', 0, 3);
        $applyScore('Dimitri', 'Hani', 0, 3);

        // Quelques matchs live en cours (poule B)
        $liveMatch = GameMatch::where('phase', 'pool')
            ->where('pool_id', Pool::where('name', 'B')->first()?->id)
            ->where('player_a_id', $playersByLabel['Eric']->id)
            ->orWhere('player_b_id', $playersByLabel['Eric']->id)
            ->first();
        if ($liveMatch) {
            $liveMatch->update([
                'status' => 'live',
                'pool_table_id' => $tableModels[0]->id,
                'referee_id' => $refOlivier->id,
                'started_at' => now()->subMinutes(35),
                'score_a' => 2,
                'score_b' => 1,
            ]);
        }

        $liveMatch2 = GameMatch::where('phase', 'pool')
            ->where('pool_id', Pool::where('name', 'D')->first()?->id)
            ->where('status', 'scheduled')
            ->first();
        if ($liveMatch2) {
            $liveMatch2->update([
                'status' => 'live',
                'pool_table_id' => $tableModels[1]->id,
                'referee_id' => $refFranck->id,
                'started_at' => now()->subMinutes(12),
                'score_a' => 1,
                'score_b' => 0,
            ]);
        }
    }
}
