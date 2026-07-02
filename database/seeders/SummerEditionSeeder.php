<?php

/**
 * SummerEditionSeeder
 *
 * Initialise la compétition "Summer Edition" pour L'Icône, Libreville.
 * Mode : initial complet — 48 joueurs fictifs, 8 poules, matchs de poule générés.
 * Le seeder est IDEMPOTENT : peut être relancé sans créer de doublons.
 *
 * Ce seeder NE crée PAS :
 *   - De matchs de phase finale (bracket KO)
 *
 * Usage :
 *   php artisan db:seed --class=SummerEditionSeeder
 *
 * Source des données : database/seeders/data/summer_edition.php
 */

namespace Database\Seeders;

use App\Models\Competition;
use App\Models\GameMatch;
use App\Models\Player;
use App\Models\PlayerRating;
use App\Models\Pool;
use App\Models\PoolTable;
use App\Models\Registration;
use App\Services\RoundRobinGenerator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SummerEditionSeeder extends Seeder
{
    // ──────────────────────────────────────────────────────────────────────────
    // 48 joueurs fictifs, 6 par poule (A–H)
    // Structure : [pool, fgb_card, first_name, last_name, rating, is_seed]
    // ──────────────────────────────────────────────────────────────────────────
    private array $players = [
        // POULE A
        ['pool' => 'A', 'fgb' => 'SE-2026-A-01', 'first' => 'Amauris',    'last' => 'KOUA',        'rating' => 1750, 'seed' => 1],
        ['pool' => 'A', 'fgb' => 'SE-2026-A-02', 'first' => 'Franck',     'last' => 'MOUDOUMA',    'rating' => 1580, 'seed' => null],
        ['pool' => 'A', 'fgb' => 'SE-2026-A-03', 'first' => 'Joël',       'last' => 'NGUEMA',      'rating' => 1420, 'seed' => null],
        ['pool' => 'A', 'fgb' => 'SE-2026-A-04', 'first' => 'Patrick',    'last' => 'ONDO',        'rating' => 1380, 'seed' => null],
        ['pool' => 'A', 'fgb' => 'SE-2026-A-05', 'first' => 'Serge',      'last' => 'BIYOGO',      'rating' => 1290, 'seed' => null],
        ['pool' => 'A', 'fgb' => 'SE-2026-A-06', 'first' => 'Didier',     'last' => 'MEYE',        'rating' => 1150, 'seed' => null],
        // POULE B
        ['pool' => 'B', 'fgb' => 'SE-2026-B-01', 'first' => 'Paolo',      'last' => 'FERREIRA',    'rating' => 1730, 'seed' => 2],
        ['pool' => 'B', 'fgb' => 'SE-2026-B-02', 'first' => 'Christophe', 'last' => 'OSSENDE',     'rating' => 1560, 'seed' => null],
        ['pool' => 'B', 'fgb' => 'SE-2026-B-03', 'first' => 'Léon',       'last' => 'MINTSA',      'rating' => 1440, 'seed' => null],
        ['pool' => 'B', 'fgb' => 'SE-2026-B-04', 'first' => 'Armand',     'last' => 'NKOGHE',      'rating' => 1360, 'seed' => null],
        ['pool' => 'B', 'fgb' => 'SE-2026-B-05', 'first' => 'Bruno',      'last' => 'BEKALE',      'rating' => 1250, 'seed' => null],
        ['pool' => 'B', 'fgb' => 'SE-2026-B-06', 'first' => 'Yves',       'last' => 'MBOUMBA',     'rating' => 1120, 'seed' => null],
        // POULE C
        ['pool' => 'C', 'fgb' => 'SE-2026-C-01', 'first' => 'Zouzou',     'last' => 'NZIGOU',      'rating' => 1710, 'seed' => 3],
        ['pool' => 'C', 'fgb' => 'SE-2026-C-02', 'first' => 'Alain',      'last' => 'ESSONO',      'rating' => 1540, 'seed' => null],
        ['pool' => 'C', 'fgb' => 'SE-2026-C-03', 'first' => 'Roger',      'last' => 'NGUEMA',      'rating' => 1410, 'seed' => null],
        ['pool' => 'C', 'fgb' => 'SE-2026-C-04', 'first' => 'Henri',      'last' => 'MEZUI',       'rating' => 1350, 'seed' => null],
        ['pool' => 'C', 'fgb' => 'SE-2026-C-05', 'first' => 'Gaston',     'last' => 'NTOLO',       'rating' => 1230, 'seed' => null],
        ['pool' => 'C', 'fgb' => 'SE-2026-C-06', 'first' => 'Marcel',     'last' => 'OBAME',       'rating' => 1100, 'seed' => null],
        // POULE D
        ['pool' => 'D', 'fgb' => 'SE-2026-D-01', 'first' => 'Zack',       'last' => 'EKOUNGOU',    'rating' => 1690, 'seed' => 4],
        ['pool' => 'D', 'fgb' => 'SE-2026-D-02', 'first' => 'Thomas',     'last' => 'MINKO',       'rating' => 1520, 'seed' => null],
        ['pool' => 'D', 'fgb' => 'SE-2026-D-03', 'first' => 'André',      'last' => 'NTOUTOUME',   'rating' => 1395, 'seed' => null],
        ['pool' => 'D', 'fgb' => 'SE-2026-D-04', 'first' => 'Cédric',     'last' => 'ELLA',        'rating' => 1330, 'seed' => null],
        ['pool' => 'D', 'fgb' => 'SE-2026-D-05', 'first' => 'Romuald',    'last' => 'BIYOGHE',     'rating' => 1210, 'seed' => null],
        ['pool' => 'D', 'fgb' => 'SE-2026-D-06', 'first' => 'Samuel',     'last' => 'ONDO',        'rating' => 1080, 'seed' => null],
        // POULE E
        ['pool' => 'E', 'fgb' => 'SE-2026-E-01', 'first' => 'Youssef',    'last' => 'AZIZ',        'rating' => 1670, 'seed' => 5],
        ['pool' => 'E', 'fgb' => 'SE-2026-E-02', 'first' => 'Eric',       'last' => 'NGUEMA',      'rating' => 1500, 'seed' => null],
        ['pool' => 'E', 'fgb' => 'SE-2026-E-03', 'first' => 'Danny',      'last' => 'NKOGHE',      'rating' => 1375, 'seed' => null],
        ['pool' => 'E', 'fgb' => 'SE-2026-E-04', 'first' => 'Arnaud',     'last' => 'MINKO',       'rating' => 1310, 'seed' => null],
        ['pool' => 'E', 'fgb' => 'SE-2026-E-05', 'first' => 'Alec',       'last' => 'ONDO',        'rating' => 1190, 'seed' => null],
        ['pool' => 'E', 'fgb' => 'SE-2026-E-06', 'first' => 'Wallas',     'last' => 'OBAME',       'rating' => 1060, 'seed' => null],
        // POULE F
        ['pool' => 'F', 'fgb' => 'SE-2026-F-01', 'first' => 'Mohamed',    'last' => 'HASSAN',      'rating' => 1650, 'seed' => 6],
        ['pool' => 'F', 'fgb' => 'SE-2026-F-02', 'first' => 'Khaled',     'last' => 'IBRAHIM',     'rating' => 1480, 'seed' => null],
        ['pool' => 'F', 'fgb' => 'SE-2026-F-03', 'first' => 'Anne',       'last' => 'MVONDO',      'rating' => 1360, 'seed' => null],
        ['pool' => 'F', 'fgb' => 'SE-2026-F-04', 'first' => 'JD',         'last' => 'EKOUAGHE',    'rating' => 1290, 'seed' => null],
        ['pool' => 'F', 'fgb' => 'SE-2026-F-05', 'first' => 'Tino',       'last' => 'MOUNDOUNGA',  'rating' => 1170, 'seed' => null],
        ['pool' => 'F', 'fgb' => 'SE-2026-F-06', 'first' => 'Clovis',     'last' => 'MEZUI',       'rating' => 1040, 'seed' => null],
        // POULE G
        ['pool' => 'G', 'fgb' => 'SE-2026-G-01', 'first' => 'Bobby',      'last' => 'MAMFOUMBI',   'rating' => 1630, 'seed' => 7],
        ['pool' => 'G', 'fgb' => 'SE-2026-G-02', 'first' => 'Virgile',    'last' => 'OBAME',       'rating' => 1460, 'seed' => null],
        ['pool' => 'G', 'fgb' => 'SE-2026-G-03', 'first' => 'Hakim',      'last' => 'MOUELE',      'rating' => 1340, 'seed' => null],
        ['pool' => 'G', 'fgb' => 'SE-2026-G-04', 'first' => 'Hani',       'last' => 'ABOUSAID',    'rating' => 1270, 'seed' => null],
        ['pool' => 'G', 'fgb' => 'SE-2026-G-05', 'first' => 'Zhao',       'last' => 'LIN',         'rating' => 1150, 'seed' => null],
        ['pool' => 'G', 'fgb' => 'SE-2026-G-06', 'first' => 'Ismael',     'last' => 'BONGO',       'rating' => 1020, 'seed' => null],
        // POULE H
        ['pool' => 'H', 'fgb' => 'SE-2026-H-01', 'first' => 'Toto',       'last' => 'NKOGHE',      'rating' => 1610, 'seed' => 8],
        ['pool' => 'H', 'fgb' => 'SE-2026-H-02', 'first' => 'Amaury',     'last' => 'NGUEMA',      'rating' => 1440, 'seed' => null],
        ['pool' => 'H', 'fgb' => 'SE-2026-H-03', 'first' => 'Dimitri',    'last' => 'MINKO',       'rating' => 1320, 'seed' => null],
        ['pool' => 'H', 'fgb' => 'SE-2026-H-04', 'first' => 'Mitch',      'last' => 'ELLA',        'rating' => 1250, 'seed' => null],
        ['pool' => 'H', 'fgb' => 'SE-2026-H-05', 'first' => 'Philippe',   'last' => 'MVONDO',      'rating' => 1130, 'seed' => null],
        ['pool' => 'H', 'fgb' => 'SE-2026-H-06', 'first' => 'Zephirin',   'last' => 'BIYOGO',      'rating' => 1000, 'seed' => null],
    ];

    public function run(): void
    {
        $cfg = require __DIR__ . '/data/summer_edition.php';

        // ── 1. Competition ────────────────────────────────────────────────────
        $competition = Competition::firstOrCreate(
            ['slug' => $cfg['competition']['slug']],
            array_merge(
                $cfg['competition'],
                ['status' => 'in_progress'],   // override draft → in_progress
                $cfg['format'],
                [
                    'settings' => [
                        'round_race_to'         => $cfg['round_race_to'],
                        'has_third_place_match' => true,
                        'prize_breakdown'       => $cfg['prize_breakdown'],
                        'schedule'              => $cfg['schedule'],
                        'payment'               => $cfg['payment'],
                        'public_announcement'   => [
                            'missing_information' => $cfg['missing_information'],
                        ],
                    ],
                ]
            )
        );

        if ($competition->wasRecentlyCreated) {
            $this->command->info('Summer Edition: compétition créée (in_progress).');
        } else {
            // Force status to in_progress if still draft
            if ($competition->status === 'draft') {
                $competition->update(['status' => 'in_progress']);
                $this->command->info('Summer Edition: statut forcé à in_progress.');
            } else {
                $this->command->info('Summer Edition: compétition existante — pas de modification du statut.');
            }
        }

        // ── 2. Tables de jeu ─────────────────────────────────────────────────
        foreach ($cfg['tables'] as $tableData) {
            PoolTable::firstOrCreate(
                ['competition_id' => $competition->id, 'name' => $tableData['name']],
                ['location' => $tableData['location'], 'status' => 'idle']
            );
        }
        $this->command->info('Summer Edition: ' . count($cfg['tables']) . ' tables de jeu OK.');

        // ── 3. Poules A–H ─────────────────────────────────────────────────────
        $pools = [];
        foreach ($cfg['pools'] as $poolData) {
            $pools[$poolData['name']] = Pool::firstOrCreate(
                ['competition_id' => $competition->id, 'name' => $poolData['name']],
                ['position' => $poolData['position'], 'size' => $poolData['size']]
            );
        }
        $this->command->info('Summer Edition: ' . count($pools) . ' poules OK (A–H).');

        // ── 4. Joueurs & registrations ───────────────────────────────────────
        //
        // Calcul des login_slug : <prenom_minuscule>-se
        // Tous les prénoms du set sont distincts donc pas de collision.
        //
        $slugCounts = [];    // pour détecter d'éventuelles collisions
        $playerCount = 0;

        // Grouper par poule pour assigner les pool_slots
        $byPool = [];
        foreach ($this->players as $p) {
            $byPool[$p['pool']][] = $p;
        }

        foreach ($byPool as $poolName => $poolPlayers) {
            $pool = $pools[$poolName] ?? null;
            if (! $pool) {
                $this->command->warn("Poule {$poolName} introuvable — joueurs ignorés.");
                continue;
            }

            $slot = 1;
            foreach ($poolPlayers as $pd) {
                // Calcul du login_slug avec déduplication
                $baseSlug = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', iconv('UTF-8', 'ASCII//TRANSLIT', $pd['first']))) . '-se';
                if (isset($slugCounts[$baseSlug])) {
                    $slugCounts[$baseSlug]++;
                    $loginSlug = $baseSlug . '-' . strtolower($poolName);
                } else {
                    $slugCounts[$baseSlug] = 1;
                    $loginSlug = $baseSlug;
                }

                $loginName = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', iconv('UTF-8', 'ASCII//TRANSLIT', $pd['first'])));

                // Créer ou mettre à jour le joueur (clé : fgb_card)
                $player = Player::updateOrCreate(
                    ['fgb_card' => $pd['fgb']],
                    [
                        'first_name'               => $pd['first'],
                        'last_name'                => $pd['last'],
                        'rating'                   => $pd['rating'],
                        'wins'                     => 0,
                        'losses'                   => 0,
                        'phone'                    => '',
                        'email'                    => '',
                        'address'                  => '',
                        'login_name'               => $loginName,
                        'login_slug'               => $loginSlug,
                        'password'                 => Hash::make('1234567'),
                        'must_change_password'     => true,
                        'is_player_account_enabled' => true,
                    ]
                );

                // Registration (idempotent par competition_id + player_id)
                Registration::firstOrCreate(
                    ['competition_id' => $competition->id, 'player_id' => $player->id],
                    [
                        'pool_id'       => $pool->id,
                        'pool_slot'     => $slot,
                        'seed'          => $pd['seed'],
                        'seed_rating'   => $pd['seed'] ? $pd['rating'] : null,
                        'status'        => 'confirmed',
                        'registered_at' => now(),
                    ]
                );

                // PlayerRating 8-ball (idempotent)
                PlayerRating::firstOrCreate(
                    ['player_id' => $player->id, 'discipline' => '8-ball'],
                    [
                        'rating'       => $pd['rating'],
                        'games_played' => 0,
                        'frames_won'   => 0,
                        'frames_lost'  => 0,
                        'robustness'   => 0,
                        'provisional'  => true,
                        'last_match_at' => null,
                    ]
                );

                $slot++;
                $playerCount++;
            }

            $this->command->info("  Poule {$poolName} : {$slot}-1 joueurs enregistrés.");
        }

        $this->command->info("Summer Edition: {$playerCount} joueurs créés/mis à jour.");

        // ── 5. Matchs de poule ───────────────────────────────────────────────
        //
        // RoundRobinGenerator::generate() wipe et régénère — on le protège avec
        // un guard pour ne pas effacer des résultats existants.
        //
        $matchesGenerated = 0;
        foreach ($pools as $poolName => $pool) {
            if ($pool->matches()->count() === 0) {
                $pool->load('registrations');  // ensure fresh
                $count = RoundRobinGenerator::generate($pool);
                $matchesGenerated += $count;
                $this->command->info("  Poule {$poolName} : {$count} matchs générés.");
            } else {
                $this->command->info("  Poule {$poolName} : matchs déjà existants — ignorés.");
            }
        }

        $this->command->info("Summer Edition: {$matchesGenerated} matchs de poule générés.");

        // ── Résumé ────────────────────────────────────────────────────────────
        $this->command->newLine();
        $this->command->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->command->info('Summer Edition — Seeder terminé avec succès.');
        $this->command->line('  Competition ID : ' . $competition->id);
        $this->command->line('  Statut         : ' . $competition->fresh()->status);
        $this->command->line('  Joueurs        : ' . $playerCount . ' (48)');
        $this->command->line('  Poules         : ' . count($pools) . ' (A–H)');
        $this->command->line('  Matchs pool    : ' . GameMatch::where('competition_id', $competition->id)->where('phase', 'pool')->count());
        $this->command->line('  Matchs KO      : 0 (bracket non encore généré)');
        $this->command->line('  URL admin      : /admin/competitions/' . $competition->id);
        $this->command->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
    }
}
