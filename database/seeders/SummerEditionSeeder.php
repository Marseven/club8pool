<?php

/**
 * SummerEditionSeeder
 *
 * Initialise la compétition "Summer Edition" pour L'Icône, Libreville.
 * Le seeder est IDEMPOTENT : il peut être relancé sans créer de doublons.
 *
 * Usage :
 *   php artisan db:seed --class=SummerEditionSeeder
 *
 * Ce seeder ne crée PAS :
 *   - Les 40 joueurs non-têtes de série (à importer via /admin une fois la liste connue)
 *   - Les matchs de poules (générés par le système lors du tirage)
 *   - Le bracket (généré par /admin/phase-finale une fois les poules terminées)
 *
 * Source des données : database/seeders/data/summer_edition.php
 */

namespace Database\Seeders;

use App\Models\Competition;
use App\Models\Player;
use App\Models\Pool;
use App\Models\PoolTable;
use App\Models\Registration;
use Illuminate\Database\Seeder;

class SummerEditionSeeder extends Seeder
{
    public function run(): void
    {
        $cfg = require __DIR__ . '/data/summer_edition.php';

        // ── 1. Competition ────────────────────────────────────────────────────
        $competition = Competition::firstOrCreate(
            ['slug' => $cfg['competition']['slug']],
            array_merge(
                $cfg['competition'],
                $cfg['format'],
                [
                    'settings' => [
                        'round_race_to'          => $cfg['round_race_to'],
                        'has_third_place_match'  => true,
                        'prize_breakdown'        => $cfg['prizes']['breakdown'],
                        'schedule'               => $cfg['schedule'],
                        'payment'                => $cfg['payment'],
                        'public_announcement'    => [
                            'missing_information' => $cfg['missing_information'],
                        ],
                    ],
                ]
            )
        );

        // If competition already exists, update the settings (idempotent upsert of config fields)
        if (! $competition->wasRecentlyCreated) {
            $competition->update(array_merge(
                $cfg['competition'],
                $cfg['format'],
                [
                    'settings' => array_merge($competition->settings ?? [], [
                        'round_race_to'         => $cfg['round_race_to'],
                        'has_third_place_match' => true,
                        'prize_breakdown'       => $cfg['prizes']['breakdown'],
                        'schedule'              => $cfg['schedule'],
                        'payment'               => $cfg['payment'],
                    ]),
                ]
            ));
            $this->command->info('Summer Edition: updated existing competition.');
        } else {
            $this->command->info('Summer Edition: created competition.');
        }

        // ── 2. Tables de jeu ─────────────────────────────────────────────────
        foreach ($cfg['tables'] as $tableData) {
            PoolTable::firstOrCreate(
                ['competition_id' => $competition->id, 'name' => $tableData['name']],
                ['location' => $tableData['location'], 'status' => 'idle']
            );
        }
        $this->command->info('Summer Edition: ' . count($cfg['tables']) . ' tables OK.');

        // ── 3. Poules ─────────────────────────────────────────────────────────
        $pools = [];
        foreach ($cfg['pools'] as $poolData) {
            $pools[$poolData['name']] = Pool::firstOrCreate(
                ['competition_id' => $competition->id, 'name' => $poolData['name']],
                ['position' => $poolData['position'], 'size' => $poolData['size']]
            );
        }
        $this->command->info('Summer Edition: ' . count($pools) . ' pools OK.');

        // ── 4. Têtes de série (8 joueurs) ────────────────────────────────────
        //
        // Seuls les display_names sont connus. Les noms de famille sont des
        // placeholders — à mettre à jour via l'interface admin avant le tirage.
        // La carte FGB est provisoire pour garantir l'unicité (firstOrCreate).
        //
        $seededCount = 0;
        foreach ($cfg['top_seeds'] as $seedData) {
            $fgbCard = 'SE-2026-SEED-' . str_pad($seedData['seed'], 2, '0', STR_PAD_LEFT);

            $player = Player::firstOrCreate(
                ['fgb_card' => $fgbCard],
                [
                    'first_name' => $seedData['display_name'],
                    'last_name'  => '',  // à renseigner avant le tirage
                    'phone'      => '',
                    'email'      => '',
                    'address'    => '',
                ]
            );

            $pool = $pools[$seedData['pool']] ?? null;
            if (! $pool) continue;

            Registration::firstOrCreate(
                ['competition_id' => $competition->id, 'player_id' => $player->id],
                [
                    'pool_id'       => $pool->id,
                    'pool_slot'     => 1,                    // tête de série = slot 1 dans sa poule
                    'seed'          => $seedData['seed'],
                    'seed_rating'   => $seedData['seed'],    // utilisé par SeedingService (manual)
                    'status'        => 'confirmed',
                    'registered_at' => now(),
                ]
            );

            $seededCount++;
        }
        $this->command->info("Summer Edition: {$seededCount} têtes de série inscrites (pool slot 1).");

        // ── 5. Placeholders pour les 40 joueurs restants ──────────────────────
        //
        // 8 pools × 5 joueurs non-têtes de série = 40 placeholders.
        // Ces entrées seront remplacées par les vrais joueurs via l'import admin.
        // Ils ne sont PAS confirmés (status = pending) pour distinguer des inscrits réels.
        //
        $poolNames = array_column($cfg['pools'], 'name');
        $placeholderNum = 1;

        foreach ($poolNames as $poolName) {
            $pool = $pools[$poolName] ?? null;
            if (! $pool) continue;

            for ($slot = 2; $slot <= 6; $slot++) {
                $fgbCard = 'SE-2026-PH-' . str_pad($placeholderNum, 2, '0', STR_PAD_LEFT);

                $player = Player::firstOrCreate(
                    ['fgb_card' => $fgbCard],
                    [
                        'first_name' => 'Joueur',
                        'last_name'  => (string) (8 + $placeholderNum),
                        'phone'      => '',
                        'email'      => '',
                        'address'    => '',
                    ]
                );

                Registration::firstOrCreate(
                    ['competition_id' => $competition->id, 'player_id' => $player->id],
                    [
                        'pool_id'     => $pool->id,
                        'pool_slot'   => $slot,
                        'seed'        => null,
                        'status'      => 'pending',   // placeholder — pas encore confirmé
                    ]
                );

                $placeholderNum++;
            }
        }
        $this->command->info("Summer Edition: {$placeholderNum} joueurs au total (8 seeds + 40 placeholders).");

        // ── Résumé ────────────────────────────────────────────────────────────
        $this->command->newLine();
        $this->command->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->command->info('Summer Edition — Seeder terminé avec succès.');
        $this->command->line('  Competition ID : ' . $competition->id);
        $this->command->line('  URL admin      : /admin/competitions/' . $competition->id);
        $this->command->newLine();
        $this->command->warn('INFORMATIONS MANQUANTES (à collecter avant le tirage) :');
        foreach ($cfg['missing_information'] as $item) {
            $this->command->line('  · ' . $item);
        }
        $this->command->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
    }
}
