<?php

namespace Database\Seeders;

use App\Models\Club;
use App\Models\Competition;
use App\Models\GameMatch;
use App\Models\Player;
use App\Models\PoolTable;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Comptes
        User::create([
            'name' => 'Kévin Boussougou',
            'email' => 'admin@club8pool.ga',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'title' => 'Directeur · FGB',
        ]);

        $referee = User::create([
            'name' => 'Olivier Kombila',
            'email' => 'olivier@club8pool.ga',
            'fgb_card' => 'FGB-ARB-2026-0024',
            'pin' => Hash::make('12345'),
            'password' => Hash::make('password'),
            'role' => 'referee',
            'title' => 'Arbitre National',
        ]);

        User::create([
            'name' => 'Franck Ndong',
            'email' => 'franck@club8pool.ga',
            'fgb_card' => 'FGB-ARB-2026-0031',
            'pin' => Hash::make('12345'),
            'password' => Hash::make('password'),
            'role' => 'referee',
            'title' => 'Arbitre',
        ]);

        // Clubs
        $clubs = collect([
            ['name' => 'Le Cadre', 'city' => 'Libreville'],
            ['name' => 'Akanda Pool Club', 'city' => 'Akanda'],
            ['name' => 'Owendo Billard', 'city' => 'Owendo'],
            ['name' => 'Port-Gentil 8', 'city' => 'Port-Gentil'],
            ['name' => 'Franceville Cue', 'city' => 'Franceville'],
            ['name' => 'Lambaréné Club', 'city' => 'Lambaréné'],
        ])->mapWithKeys(function ($c) {
            $club = Club::create([
                'name' => $c['name'],
                'city' => $c['city'],
                'slug' => Str::slug($c['name']),
            ]);
            return [$c['name'] => $club];
        });

        // Joueurs (cf data.js du design)
        $players = [
            ['Junior', 'MBOUMBA', 'Le Cadre', 1842, 27, 6],
            ['Brice', 'ONDO', 'Akanda Pool Club', 1798, 24, 9],
            ['Yann', 'NZAMBA', 'Owendo Billard', 1755, 22, 11],
            ['Patrick', 'OBAME', 'Le Cadre', 1721, 21, 12],
            ['Steeve', 'MOUSSAVOU', 'Port-Gentil 8', 1698, 19, 13],
            ['Wilfried', 'MENGUE', 'Akanda Pool Club', 1672, 18, 14],
            ['Yves', 'BIYOGO', 'Franceville Cue', 1654, 17, 15],
            ['Hervé', 'NDJAVE', 'Le Cadre', 1631, 16, 16],
            ['Olivier', 'KOMBILA', 'Owendo Billard', 1610, 15, 17],
            ['Aurélien', 'BIVIGOU', 'Port-Gentil 8', 1588, 14, 18],
            ['Franck', 'NDONG', 'Akanda Pool Club', 1564, 13, 19],
            ['Régis', 'ANGUE', 'Le Cadre', 1541, 12, 20],
            ['Anicet', 'IBINGA', 'Lambaréné Club', 1520, 11, 21],
            ['Mike', 'OBIANG', 'Akanda Pool Club', 1498, 10, 22],
            ['Loïc', 'MAGANGA', 'Owendo Billard', 1476, 9, 23],
            ['Ulrich', 'MEZUI', 'Franceville Cue', 1452, 8, 24],
        ];

        $playerModels = collect();
        foreach ($players as $i => $p) {
            $playerModels->push(Player::create([
                'first_name' => $p[0],
                'last_name' => $p[1],
                'club_id' => $clubs[$p[2]]->id,
                'fgb_card' => sprintf('FGB-2026-%04d', $i + 100),
                'phone' => '+241 65 78 ' . str_pad((string) (1000 + $i * 7), 4, '0', STR_PAD_LEFT),
                'email' => strtolower($p[0]) . '.' . strtolower($p[1]) . '@fgb.ga',
                'birthdate' => sprintf('19%02d-%02d-%02d', 80 + ($i % 15), 1 + ($i % 12), 1 + ($i % 28)),
                'cue' => $i % 3 === 0 ? 'Predator 12.4mm' : ($i % 3 === 1 ? 'Mezz United Pro' : 'McDermott Lucky'),
                'address' => 'BP ' . (4000 + $i * 23) . ', Libreville',
                'rating' => $p[3],
                'wins' => $p[4],
                'losses' => $p[5],
            ]));
        }

        // Compétition
        $comp = Competition::create([
            'name' => 'Coupe du Gabon 8-Ball — Édition 04',
            'slug' => 'coupe-du-gabon-2026',
            'discipline' => '8-ball',
            'format' => 'single_elim',
            'player_slots' => 16,
            'race_to' => 7,
            'shot_clock' => 30,
            'alternate_break' => true,
            'push_out' => false,
            'frame_pause' => 60,
            'tiebreak_race' => 9,
            'venue' => 'Le Cadre, Libreville',
            'city' => 'Libreville',
            'entry_fee' => 25000,
            'deposit' => 10000,
            'prize_pool' => 1400000,
            'starts_on' => '2026-06-05',
            'ends_on' => '2026-06-07',
            'registration_closes_at' => '2026-06-04 18:00:00',
            'status' => 'in_progress',
        ]);

        // Tables
        $tables = [
            ['Table 1', 'Centre', 'live'],
            ['Table 2', 'Aile A', 'live'],
            ['Table 3', 'Aile B', 'idle'],
            ['Table 4', 'Démo', 'idle'],
            ['Table 5', 'Réservée', 'maint'],
        ];
        $tableModels = collect();
        foreach ($tables as $t) {
            $tableModels->push(PoolTable::create([
                'competition_id' => $comp->id,
                'name' => $t[0],
                'location' => $t[1],
                'status' => $t[2],
            ]));
        }

        // Inscriptions (16 seeds)
        foreach ($playerModels as $i => $p) {
            Registration::create([
                'competition_id' => $comp->id,
                'player_id' => $p->id,
                'seed' => $i + 1,
                'status' => 'paid',
                'registered_at' => now()->subDays(rand(5, 20)),
            ]);
        }

        // BRACKET — Round of 16 (terminé)
        $r16 = [
            [1, 16, 7, 3], [8, 9, 7, 5], [4, 13, 7, 4], [5, 12, 7, 6],
            [2, 15, 7, 2], [7, 10, 5, 7], [3, 14, 7, 1], [6, 11, 7, 4],
        ];
        foreach ($r16 as $idx => $m) {
            GameMatch::create([
                'competition_id' => $comp->id,
                'round' => 'R16',
                'round_position' => $idx,
                'player_a_id' => $playerModels[$m[0] - 1]->id,
                'player_b_id' => $playerModels[$m[1] - 1]->id,
                'score_a' => $m[2],
                'score_b' => $m[3],
                'pool_table_id' => $tableModels[$idx % 4]->id,
                'referee_id' => $referee->id,
                'status' => 'done',
                'scheduled_at' => '2026-06-06 14:00:00',
                'started_at' => '2026-06-06 14:00:00',
                'ended_at' => '2026-06-06 15:30:00',
                'duration_seconds' => 5400,
            ]);
        }

        // Quarts (en cours)
        GameMatch::create([
            'competition_id' => $comp->id, 'round' => 'QF', 'round_position' => 0,
            'player_a_id' => $playerModels[0]->id, 'player_b_id' => $playerModels[7]->id,
            'score_a' => 5, 'score_b' => 3,
            'pool_table_id' => $tableModels[0]->id, 'referee_id' => $referee->id,
            'status' => 'live', 'scheduled_at' => '2026-06-06 17:00:00',
            'started_at' => '2026-06-06 17:00:00',
        ]);
        GameMatch::create([
            'competition_id' => $comp->id, 'round' => 'QF', 'round_position' => 1,
            'player_a_id' => $playerModels[3]->id, 'player_b_id' => $playerModels[4]->id,
            'score_a' => 7, 'score_b' => 6,
            'pool_table_id' => $tableModels[1]->id, 'referee_id' => $referee->id,
            'status' => 'done', 'scheduled_at' => '2026-06-06 17:00:00',
            'started_at' => '2026-06-06 17:00:00', 'ended_at' => '2026-06-06 18:48:00',
            'duration_seconds' => 6480,
        ]);
        GameMatch::create([
            'competition_id' => $comp->id, 'round' => 'QF', 'round_position' => 2,
            'player_a_id' => $playerModels[1]->id, 'player_b_id' => $playerModels[9]->id,
            'score_a' => 4, 'score_b' => 2,
            'pool_table_id' => $tableModels[1]->id, 'referee_id' => $referee->id,
            'status' => 'live', 'scheduled_at' => '2026-06-06 17:00:00',
            'started_at' => '2026-06-06 17:00:00',
        ]);
        GameMatch::create([
            'competition_id' => $comp->id, 'round' => 'QF', 'round_position' => 3,
            'player_a_id' => $playerModels[2]->id, 'player_b_id' => $playerModels[5]->id,
            'score_a' => 0, 'score_b' => 0,
            'status' => 'scheduled', 'scheduled_at' => '2026-06-06 20:30:00',
        ]);

        // Demi-finales (placeholders)
        GameMatch::create([
            'competition_id' => $comp->id, 'round' => 'SF', 'round_position' => 0,
            'player_a_id' => null, 'player_b_id' => $playerModels[3]->id,
            'status' => 'pending', 'scheduled_at' => '2026-06-06 22:00:00',
        ]);
        GameMatch::create([
            'competition_id' => $comp->id, 'round' => 'SF', 'round_position' => 1,
            'player_a_id' => null, 'player_b_id' => null,
            'status' => 'pending', 'scheduled_at' => '2026-06-06 22:00:00',
        ]);

        // Finale (placeholder)
        GameMatch::create([
            'competition_id' => $comp->id, 'round' => 'F', 'round_position' => 0,
            'status' => 'pending', 'scheduled_at' => '2026-06-06 23:30:00',
        ]);
    }
}
