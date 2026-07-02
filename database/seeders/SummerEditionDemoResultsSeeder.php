<?php

/**
 * SummerEditionDemoResultsSeeder
 *
 * Pré-remplit les résultats de poule pour les poules A–G de la Summer Edition.
 * La poule H est laissée en mode 'scheduled' pour permettre les tests terrain.
 *
 * Prérequis : SummerEditionSeeder doit avoir été exécuté avant ce seeder.
 *
 * Idempotent : ne modifie que les matchs encore en statut 'scheduled'.
 *
 * Usage :
 *   php artisan db:seed --class=SummerEditionDemoResultsSeeder
 */

namespace Database\Seeders;

use App\Models\Competition;
use App\Models\GameMatch;
use App\Models\Player;
use App\Models\Pool;
use Illuminate\Database\Seeder;

class SummerEditionDemoResultsSeeder extends Seeder
{
    public function run(): void
    {
        // ── 1. Trouver la compétition ─────────────────────────────────────────
        $competition = Competition::where('slug', 'summer-edition')->first();

        if (! $competition) {
            $this->command->error(
                'Compétition "summer-edition" introuvable. '
                . 'Exécutez d\'abord SummerEditionSeeder : '
                . 'php artisan db:seed --class=SummerEditionSeeder'
            );
            return;
        }

        $this->command->info('Summer Edition Demo Results: compétition trouvée (ID ' . $competition->id . ').');

        // ── 2. Pré-remplir les poules A–G ────────────────────────────────────
        foreach (['A', 'B', 'C', 'D', 'E', 'F', 'G'] as $poolName) {
            $pool = Pool::where('competition_id', $competition->id)
                ->where('name', $poolName)
                ->first();

            if (! $pool) {
                $this->command->warn("Poule {$poolName} introuvable — ignorée.");
                continue;
            }

            $matches = GameMatch::where('pool_id', $pool->id)
                ->where('phase', 'pool')
                ->get();

            $updated = 0;

            foreach ($matches as $match) {
                // Idempotent : on ne touche que les matchs encore programmés
                if ($match->status !== 'scheduled') {
                    continue;
                }

                $ratingA = Player::find($match->player_a_id)?->rating ?? 1000;
                $ratingB = Player::find($match->player_b_id)?->rating ?? 1000;
                $diff    = abs($ratingA - $ratingB);

                $winA = ($ratingA >= $ratingB);

                $loserScore = match (true) {
                    $diff > 200  => rand(0, 1),
                    $diff >= 100 => rand(1, 2),
                    default      => rand(2, 3),
                };

                $match->update([
                    'score_a'    => $winA ? 4 : $loserScore,
                    'score_b'    => $winA ? $loserScore : 4,
                    'status'     => 'done',
                    'started_at' => now()->subHours(rand(2, 6)),
                    'ended_at'   => now()->subHours(rand(0, 2)),
                ]);

                $updated++;
            }

            $this->command->info(
                "Poule {$poolName} : {$updated} résultat(s) prérempli(s) — total matchs : {$matches->count()}."
            );
        }

        // ── 3. Poule H — laissée en scheduled ────────────────────────────────
        $poolH = Pool::where('competition_id', $competition->id)->where('name', 'H')->first();

        if ($poolH) {
            $scheduledH = GameMatch::where('pool_id', $poolH->id)
                ->where('phase', 'pool')
                ->where('status', 'scheduled')
                ->count();

            $this->command->info(
                "Poule H : laissée en mode scheduled ({$scheduledH} match(s) testables terrain)."
            );
        } else {
            $this->command->warn('Poule H introuvable.');
        }

        // ── Résumé ────────────────────────────────────────────────────────────
        $doneCount = GameMatch::where('competition_id', $competition->id)
            ->where('phase', 'pool')
            ->where('status', 'done')
            ->count();

        $scheduledCount = GameMatch::where('competition_id', $competition->id)
            ->where('phase', 'pool')
            ->where('status', 'scheduled')
            ->count();

        $this->command->newLine();
        $this->command->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->command->info('Summer Edition Demo Results — Seeder terminé.');
        $this->command->line('  Matchs terminés   : ' . $doneCount . ' (poules A–G)');
        $this->command->line('  Matchs scheduled  : ' . $scheduledCount . ' (poule H)');
        $this->command->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
    }
}
