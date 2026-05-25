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
     *   A vs B  et  C vs D  (les poules adjacentes s'affrontent)
     *   Interleaved pour garantir QF cross-group (A/B winner vs C/D winner) :
     *
     *   pos 0 : A1 vs B4   pos 1 : C1 vs D4   → QF 0
     *   pos 2 : A2 vs B3   pos 3 : C2 vs D3   → QF 1
     *   pos 4 : A3 vs B2   pos 5 : C3 vs D2   → QF 2
     *   pos 6 : A4 vs B1   pos 7 : C4 vs D1   → QF 3
     *
     * Pour d'autres formats on tombe en repli sur un seeding 1-vs-N
     * en alternant les poules.
     */
    public function seedPairs(array $qualifiers): array
    {
        $poolKeys = array_keys($qualifiers);
        sort($poolKeys);
        $size = count($poolKeys) > 0 ? count($qualifiers[$poolKeys[0]]) : 0;

        if (count($poolKeys) === 4 && $size === 4) {
            $A = $qualifiers['A'] ?? [];
            $B = $qualifiers['B'] ?? [];
            $C = $qualifiers['C'] ?? [];
            $D = $qualifiers['D'] ?? [];
            return [
                [$A[0] ?? null, $B[3] ?? null],  // pos 0 : A1 vs B4
                [$C[0] ?? null, $D[3] ?? null],  // pos 1 : C1 vs D4  → QF 0
                [$A[1] ?? null, $B[2] ?? null],  // pos 2 : A2 vs B3
                [$C[1] ?? null, $D[2] ?? null],  // pos 3 : C2 vs D3  → QF 1
                [$A[2] ?? null, $B[1] ?? null],  // pos 4 : A3 vs B2
                [$C[2] ?? null, $D[1] ?? null],  // pos 5 : C3 vs D2  → QF 2
                [$A[3] ?? null, $B[0] ?? null],  // pos 6 : A4 vs B1
                [$C[3] ?? null, $D[0] ?? null],  // pos 7 : C4 vs D1  → QF 3
            ];
        }

        // Fallback générique : alterne poules, top vs bottom
        $flat = [];
        foreach ($poolKeys as $key) {
            foreach ($qualifiers[$key] as $q) $flat[] = $q;
        }
        $half = count($flat) / 2;
        $pairs = [];
        for ($i = 0; $i < $half; $i++) {
            $pairs[] = [$flat[$i] ?? null, $flat[count($flat) - 1 - $i] ?? null];
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
    }

    private function roundChain(int $pairs): array
    {
        return match (true) {
            $pairs >= 8 => ['R16', 'QF', 'SF', 'F'],
            $pairs === 4 => ['QF', 'SF', 'F'],
            $pairs === 2 => ['SF', 'F'],
            default => ['F'],
        };
    }
}
