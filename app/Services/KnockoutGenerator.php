<?php

namespace App\Services;

use App\Models\Competition;
use App\Models\GameMatch;
use App\Models\Pool;
use Illuminate\Support\Collection;

class KnockoutGenerator
{
    /**
     * Pour chaque poule, retourne les top N qualifiés (ordonnés selon
     * PoolStanding : V puis Diff puis W). N = competition.qualifiers_per_pool.
     *
     * Retour : ['A' => [{ player_id, pool_slot, name, v, w, l, diff, rank }, ...], 'B' => [...], ...]
     */
    public function qualifiers(Competition $competition): array
    {
        $qualifiersPerPool = (int) ($competition->qualifiers_per_pool ?? 2);
        $byPool = [];

        foreach ($competition->pools as $pool) {
            $standings = PoolStanding::compute($pool);
            $top = $standings->take($qualifiersPerPool)->map(fn ($r) => [
                'player_id' => $r['player_id'],
                'pool_slot' => $r['pool_slot'],
                'pool_name' => $pool->name,
                'name' => trim($r['player']->first_name . ' ' . $r['player']->last_name),
                'v' => $r['v'],
                'w' => $r['w'],
                'l' => $r['l'],
                'diff' => $r['diff'],
                'rank' => $r['rank'],
            ])->values()->toArray();
            $byPool[$pool->name] = $top;
        }

        return $byPool;
    }

    /**
     * Détecte les ex-aequo dans chaque poule (rang identique sur le top N + 1).
     * Retourne un tableau de groupes [{ pool, rank, players: [...] }].
     */
    public function ties(array $qualifiers, Competition $competition): array
    {
        $ties = [];
        foreach ($competition->pools as $pool) {
            $all = PoolStanding::compute($pool);
            $perPool = (int) ($competition->qualifiers_per_pool ?? 2);
            // Group by rank within the cut zone or on the bubble (rank = cut + 1)
            $candidates = $all->take($perPool + 2);
            $groups = $candidates->groupBy('rank');
            foreach ($groups as $rank => $players) {
                if ($players->count() < 2) continue;
                // Ne flagger que les groupes qui chevauchent la ligne de cut
                $hasInside = $players->contains(fn ($p) => $p['rank'] <= $perPool);
                if (! $hasInside) continue;
                $ties[] = [
                    'pool' => $pool->name,
                    'rank' => (int) $rank,
                    'players' => $players->map(fn ($p) => [
                        'player_id' => $p['player_id'],
                        'pool_slot' => $p['pool_slot'],
                        'name' => trim($p['player']->first_name . ' ' . $p['player']->last_name),
                        'v' => $p['v'],
                        'w' => $p['w'],
                        'l' => $p['l'],
                        'diff' => $p['diff'],
                    ])->values()->toArray(),
                ];
            }
        }
        return $ties;
    }

    /**
     * Cross-poules pour 4 poules × 4 qualifiés = 16 → R16 :
     *   A vs C  et  B vs D  (les poules opposées s'affrontent)
     *   Interleaved pour garantir QF cross-group (A/C winner vs B/D winner) :
     *
     *   pos 0 : A1 vs C4   pos 1 : B1 vs D4   → QF 0
     *   pos 2 : A2 vs C3   pos 3 : B2 vs D3   → QF 1
     *   pos 4 : A3 vs C2   pos 5 : B3 vs D2   → QF 2
     *   pos 6 : A4 vs C1   pos 7 : B4 vs D1   → QF 3
     *
     * Pour d'autres formats on tombe en repli sur un seeding 1-vs-N
     * en alternant les poules.
     *
     * If $orderedFlat is provided (pre-ordered by SeedingService), the pool-based
     * logic is bypassed and standard 1-vs-N single-elimination pairing is used.
     */
    public function seedPairs(array $qualifiers, array $orderedFlat = []): array
    {
        // If a pre-ordered flat list is supplied, use standard bracket seeding.
        if (! empty($orderedFlat)) {
            return $this->pairsFromFlat($orderedFlat);
        }

        $poolKeys = array_keys($qualifiers);
        sort($poolKeys);
        $size = count($poolKeys) > 0 ? count($qualifiers[$poolKeys[0]]) : 0;

        if (count($poolKeys) === 4 && $size === 4) {
            $A = $qualifiers['A'] ?? [];
            $B = $qualifiers['B'] ?? [];
            $C = $qualifiers['C'] ?? [];
            $D = $qualifiers['D'] ?? [];
            return [
                [$A[0] ?? null, $C[3] ?? null],  // pos 0 : A1 vs C4
                [$B[0] ?? null, $D[3] ?? null],  // pos 1 : B1 vs D4  → QF 0
                [$A[1] ?? null, $C[2] ?? null],  // pos 2 : A2 vs C3
                [$B[1] ?? null, $D[2] ?? null],  // pos 3 : B2 vs D3  → QF 1
                [$A[2] ?? null, $C[1] ?? null],  // pos 4 : A3 vs C2
                [$B[2] ?? null, $D[1] ?? null],  // pos 5 : B3 vs D2  → QF 2
                [$A[3] ?? null, $C[0] ?? null],  // pos 6 : A4 vs C1
                [$B[3] ?? null, $D[0] ?? null],  // pos 7 : B4 vs D1  → QF 3
            ];
        }

        // Fallback générique : alterne poules, top vs bottom
        $flat = [];
        foreach ($poolKeys as $key) {
            foreach ($qualifiers[$key] as $q) $flat[] = $q;
        }
        return $this->pairsFromFlat($flat);
    }

    /**
     * Standard single-elimination pairing from an ordered flat list:
     *   seed 1 vs seed N, seed 2 vs seed N-1, etc.
     */
    public function pairsFromFlat(array $flat): array
    {
        $total = count($flat);
        $half  = (int) ($total / 2);
        $pairs = [];
        for ($i = 0; $i < $half; $i++) {
            $pairs[] = [$flat[$i] ?? null, $flat[$total - 1 - $i] ?? null];
        }
        return $pairs;
    }

    /**
     * Persiste les matchs de phase finale dans la DB.
     * Supprime les anciens matchs knockout puis crée :
     *   - les paires R16 (avec joueurs)
     *   - les placeholders QF / SF / F (sans joueurs, status=pending)
     *
     * Adapte le nombre de tours au nombre de paires :
     *   8 paires → R16 → QF → SF → F
     *   4 paires → QF → SF → F
     *   2 paires → SF → F
     *   1 paire  → F
     */
    public function generate(Competition $competition, array $pairs): void
    {
        // wipe existing knockout matches
        GameMatch::where('competition_id', $competition->id)
            ->where('phase', 'knockout')
            ->delete();

        $rounds = $this->roundChain(count($pairs));

        // Determine before shifting whether the bracket includes a semi-final
        // (needed to decide whether to create a 3P placeholder).
        $hasThirdPlace = ($competition->settings['has_third_place_match'] ?? false)
            && in_array('SF', $rounds, true);

        $firstRound = array_shift($rounds);

        foreach ($pairs as $i => [$a, $b]) {
            GameMatch::create([
                'competition_id' => $competition->id,
                'phase' => 'knockout',
                'round' => $firstRound,
                'round_position' => $i,
                'player_a_id' => $a['player_id'] ?? null,
                'player_b_id' => $b['player_id'] ?? null,
                'score_a' => 0,
                'score_b' => 0,
                'status' => 'scheduled',
            ]);
        }

        // Placeholders pour les tours suivants
        $count = count($pairs);
        foreach ($rounds as $round) {
            $count = (int) ceil($count / 2);
            for ($i = 0; $i < $count; $i++) {
                GameMatch::create([
                    'competition_id' => $competition->id,
                    'phase' => 'knockout',
                    'round' => $round,
                    'round_position' => $i,
                    'status' => 'pending',
                ]);
            }
        }

        // Petite finale (3P) — created only when the competition explicitly enables it
        if ($hasThirdPlace) {
            GameMatch::create([
                'competition_id' => $competition->id,
                'phase'          => 'knockout',
                'round'          => '3P',
                'round_position' => 0,
                'status'         => 'pending',
            ]);
        }
    }

    private function roundChain(int $pairs): array
    {
        return match (true) {
            $pairs >= 16 => ['R32', 'R16', 'QF', 'SF', 'F'],
            $pairs >= 8  => ['R16', 'QF', 'SF', 'F'],
            $pairs >= 4  => ['QF', 'SF', 'F'],
            $pairs >= 2  => ['SF', 'F'],
            default      => ['F'],
        };
    }
}
