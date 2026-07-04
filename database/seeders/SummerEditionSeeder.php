<?php

/**
 * SummerEditionSeeder
 *
 * Initialise la compétition "Summer Edition" pour L'Icône, Libreville.
 * Mode : initial complet — 40 joueurs réels (5/poule), 8 poules, matchs de poule générés.
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
    // 40 joueurs officiels, 5 par poule (A–H)
    // Source : Calendrier officiel Summer Edition 2026 (PDF du 04/07/2026)
    // Structure : [pool, fgb_card, first_name, last_name, rating, is_seed]
    // Noms de famille non communiqués → chaîne vide en attente.
    // ──────────────────────────────────────────────────────────────────────────
    private array $players = [
        // POULE A — 04/07 14h
        ['pool' => 'A', 'fgb' => 'SE-2026-A-01', 'first' => 'Appolinaire', 'last' => '',     'rating' => 1580, 'seed' => 1],
        ['pool' => 'A', 'fgb' => 'SE-2026-A-02', 'first' => 'Kremlin',     'last' => '',     'rating' => 1350, 'seed' => null],
        ['pool' => 'A', 'fgb' => 'SE-2026-A-03', 'first' => 'Bobo',        'last' => '',     'rating' => 1250, 'seed' => null],
        ['pool' => 'A', 'fgb' => 'SE-2026-A-04', 'first' => 'Armelia',     'last' => '',     'rating' => 1150, 'seed' => null],
        ['pool' => 'A', 'fgb' => 'SE-2026-A-05', 'first' => 'Joel',        'last' => '',     'rating' => 1050, 'seed' => null],
        // POULE B — 05/07 14h
        ['pool' => 'B', 'fgb' => 'SE-2026-B-01', 'first' => 'Kass',        'last' => '',     'rating' => 1560, 'seed' => 2],
        ['pool' => 'B', 'fgb' => 'SE-2026-B-02', 'first' => 'JD',          'last' => '',     'rating' => 1380, 'seed' => null],
        ['pool' => 'B', 'fgb' => 'SE-2026-B-03', 'first' => 'Toto',        'last' => '',     'rating' => 1280, 'seed' => null],
        ['pool' => 'B', 'fgb' => 'SE-2026-B-04', 'first' => 'Yvan',        'last' => '',     'rating' => 1180, 'seed' => null],
        ['pool' => 'B', 'fgb' => 'SE-2026-B-05', 'first' => 'Sam',         'last' => '',     'rating' => 1080, 'seed' => null],
        // POULE C — 04/07 18h30
        ['pool' => 'C', 'fgb' => 'SE-2026-C-01', 'first' => 'Benson',      'last' => '',     'rating' => 1540, 'seed' => 3],
        ['pool' => 'C', 'fgb' => 'SE-2026-C-02', 'first' => 'Salif',       'last' => '',     'rating' => 1360, 'seed' => null],
        ['pool' => 'C', 'fgb' => 'SE-2026-C-03', 'first' => 'Serge',       'last' => '',     'rating' => 1260, 'seed' => null],
        ['pool' => 'C', 'fgb' => 'SE-2026-C-04', 'first' => 'Jess',        'last' => '',     'rating' => 1160, 'seed' => null],
        ['pool' => 'C', 'fgb' => 'SE-2026-C-05', 'first' => 'Glen',        'last' => '',     'rating' => 1060, 'seed' => null],
        // POULE D — 05/07 18h30
        ['pool' => 'D', 'fgb' => 'SE-2026-D-01', 'first' => 'Bobby',       'last' => '',     'rating' => 1520, 'seed' => 4],
        ['pool' => 'D', 'fgb' => 'SE-2026-D-02', 'first' => 'Hanni',       'last' => '',     'rating' => 1340, 'seed' => null],
        ['pool' => 'D', 'fgb' => 'SE-2026-D-03', 'first' => 'Jessy',       'last' => '',     'rating' => 1240, 'seed' => null],
        ['pool' => 'D', 'fgb' => 'SE-2026-D-04', 'first' => 'Wallas',      'last' => '',     'rating' => 1140, 'seed' => null],
        ['pool' => 'D', 'fgb' => 'SE-2026-D-05', 'first' => 'Zouzou',      'last' => '',     'rating' => 1040, 'seed' => null],
        // POULE E — 06/07 18h30
        ['pool' => 'E', 'fgb' => 'SE-2026-E-01', 'first' => 'Aziz',        'last' => '',     'rating' => 1500, 'seed' => 5],
        ['pool' => 'E', 'fgb' => 'SE-2026-E-02', 'first' => 'Mohamed',     'last' => '',     'rating' => 1350, 'seed' => null],
        ['pool' => 'E', 'fgb' => 'SE-2026-E-03', 'first' => 'Tarek',       'last' => '',     'rating' => 1250, 'seed' => null],
        ['pool' => 'E', 'fgb' => 'SE-2026-E-04', 'first' => 'Khaled',      'last' => '',     'rating' => 1150, 'seed' => null],
        ['pool' => 'E', 'fgb' => 'SE-2026-E-05', 'first' => 'Valery',      'last' => '',     'rating' => 1050, 'seed' => null],
        // POULE F — 07/07 18h30
        ['pool' => 'F', 'fgb' => 'SE-2026-F-01', 'first' => 'Dimitri',     'last' => '',     'rating' => 1480, 'seed' => 6],
        ['pool' => 'F', 'fgb' => 'SE-2026-F-02', 'first' => 'Mitch',       'last' => '',     'rating' => 1320, 'seed' => null],
        ['pool' => 'F', 'fgb' => 'SE-2026-F-03', 'first' => 'Alec',        'last' => '',     'rating' => 1220, 'seed' => null],
        ['pool' => 'F', 'fgb' => 'SE-2026-F-04', 'first' => 'Teuf',        'last' => '',     'rating' => 1120, 'seed' => null],
        ['pool' => 'F', 'fgb' => 'SE-2026-F-05', 'first' => 'TBD',         'last' => 'X2',   'rating' => 1000, 'seed' => null],
        // POULE G — 06/07 18h30
        ['pool' => 'G', 'fgb' => 'SE-2026-G-01', 'first' => 'Attiss',      'last' => '',     'rating' => 1460, 'seed' => 7],
        ['pool' => 'G', 'fgb' => 'SE-2026-G-02', 'first' => 'Amaury',      'last' => '',     'rating' => 1300, 'seed' => null],
        ['pool' => 'G', 'fgb' => 'SE-2026-G-03', 'first' => 'Haidara',     'last' => '',     'rating' => 1200, 'seed' => null],
        ['pool' => 'G', 'fgb' => 'SE-2026-G-04', 'first' => 'Calixte',     'last' => '',     'rating' => 1100, 'seed' => null],
        ['pool' => 'G', 'fgb' => 'SE-2026-G-05', 'first' => 'TBD',         'last' => 'X3',   'rating' => 1000, 'seed' => null],
        // POULE H — 07/07 18h30
        ['pool' => 'H', 'fgb' => 'SE-2026-H-01', 'first' => 'Paolo',       'last' => '',     'rating' => 1600, 'seed' => 8],
        ['pool' => 'H', 'fgb' => 'SE-2026-H-02', 'first' => 'Maud',        'last' => '',     'rating' => 1280, 'seed' => null],
        ['pool' => 'H', 'fgb' => 'SE-2026-H-03', 'first' => 'Anne',        'last' => '',     'rating' => 1180, 'seed' => null],
        ['pool' => 'H', 'fgb' => 'SE-2026-H-04', 'first' => 'Daniel',      'last' => '',     'rating' => 1080, 'seed' => null],
        ['pool' => 'H', 'fgb' => 'SE-2026-H-05', 'first' => 'TBD',         'last' => 'X4',   'rating' => 1000, 'seed' => null],
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

        // Always sync knockout_mapping_strategy with config (idempotent)
        if ($competition->knockout_mapping_strategy !== $cfg['competition']['knockout_mapping_strategy']) {
            $competition->update(['knockout_mapping_strategy' => $cfg['competition']['knockout_mapping_strategy']]);
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

            $this->command->info("  Poule {$poolName} : " . ($slot - 1) . " joueurs enregistrés.");
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
        $this->command->line('  Joueurs        : ' . $playerCount . ' (' . $competition->fresh()->registrations()->count() . ')');
        $this->command->line('  Poules         : ' . count($pools) . ' (A–H)');
        $this->command->line('  Matchs pool    : ' . GameMatch::where('competition_id', $competition->id)->where('phase', 'pool')->count());
        $this->command->line('  Matchs KO      : 0 (bracket non encore généré)');
        $this->command->line('  URL admin      : /admin/competitions/' . $competition->id);
        $this->command->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
    }
}
