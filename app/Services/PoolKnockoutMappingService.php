<?php

namespace App\Services;

class PoolKnockoutMappingService
{
    // 8 pools × 4 qualifiers/pool → R32 (16 pairs): A/C, B/D, E/G, F/H
    const STRATEGY = 'pool_cross_ac_bd_eg_fh';

    // 8 pools × 2 qualifiers/pool → R16 (8 pairs): A/D, B/C, E/H, F/G  (legacy)
    const STRATEGY_2Q = 'pool_cross_ad_bc_eh_fg';

    // 8 pools × 2 qualifiers/pool → R16 (8 pairs): Summer Edition 2026 official bracket
    // Gauche: A/B + C/D  —  Droite: E/F + H/G
    //   pos0: A1vB2  pos1: B1vA2  → QF_0
    //   pos2: C1vD2  pos3: D1vC2  → QF_1
    //   pos4: E1vF2  pos5: F1vE2  → QF_2
    //   pos6: H1vG2  pos7: G1vH2  → QF_3
    const STRATEGY_SE2026 = 'pool_cross_ab_cd_ef_gh';

    private static array $CONFIGS = [
        'pool_cross_ac_bd_eg_fh' => [
            'pool_pairs' => [['A','C'], ['B','D'], ['E','G'], ['F','H']],
            'qualifiers'  => 4,
        ],
        'pool_cross_ad_bc_eh_fg' => [
            'pool_pairs' => [['A','D'], ['B','C'], ['E','H'], ['F','G']],
            'qualifiers'  => 2,
        ],
        // Summer Edition 2026 — mapping explicite (flat_pairs)
        // Format: [poolX, rankX (0-based), poolY, rankY (0-based)]
        // Bracket gauche : A/B + C/D — Bracket droite : E/F + H/G
        'pool_cross_ab_cd_ef_gh' => [
            'qualifiers' => 2,
            'flat_pairs' => [
                ['A', 0, 'B', 1],  // pos 0 : A1 vs B2  → QF 0
                ['B', 0, 'A', 1],  // pos 1 : B1 vs A2  → QF 0
                ['C', 0, 'D', 1],  // pos 2 : C1 vs D2  → QF 1
                ['D', 0, 'C', 1],  // pos 3 : D1 vs C2  → QF 1
                ['E', 0, 'F', 1],  // pos 4 : E1 vs F2  → QF 2
                ['F', 0, 'E', 1],  // pos 5 : F1 vs E2  → QF 2
                ['H', 0, 'G', 1],  // pos 6 : H1 vs G2  → QF 3
                ['G', 0, 'H', 1],  // pos 7 : G1 vs H2  → QF 3
            ],
        ],
    ];

    /**
     * Build bracket pairs from pool qualifiers.
     *
     * $qualifiers format (from KnockoutGenerator::qualifiers()):
     *   ['A' => [['player_id' => X, 'rank' => 1, ...], ...], ...]
     *   Players ordered rank 1 → N (index 0 = rank 1).
     *
     * @throws \InvalidArgumentException if a pool is missing or has < N qualifiers
     */
    public function buildPairs(array $qualifiers, string $strategy = self::STRATEGY): array
    {
        $config = self::$CONFIGS[$strategy]
            ?? throw new \InvalidArgumentException("Unknown knockout mapping strategy: '{$strategy}'.");

        if (isset($config['flat_pairs'])) {
            return $this->buildFlatPairs($qualifiers, $config);
        }

        $poolPairs = $config['pool_pairs'];
        $qCount    = $config['qualifiers'];

        $this->validatePoolPairs($qualifiers, $poolPairs, $qCount);

        $pairs = [];
        foreach ($poolPairs as [$poolX, $poolY]) {
            $X = $qualifiers[$poolX];
            $Y = $qualifiers[$poolY];
            for ($i = 0; $i < $qCount; $i++) {
                $pairs[] = [
                    array_merge($X[$i],              ['source' => $poolX . ($i + 1)]),
                    array_merge($Y[$qCount - 1 - $i], ['source' => $poolY . ($qCount - $i)]),
                ];
            }
        }

        return $pairs;
    }

    /**
     * Returns source label pairs for display, e.g. ['A1 vs B2', 'C2 vs D1', …]
     */
    public function getSourceLabelMap(string $strategy = self::STRATEGY): array
    {
        $config = self::$CONFIGS[$strategy]
            ?? throw new \InvalidArgumentException("Unknown knockout mapping strategy: '{$strategy}'.");

        if (isset($config['flat_pairs'])) {
            return array_map(
                fn ($p) => $p[0] . ($p[1] + 1) . ' vs ' . $p[2] . ($p[3] + 1),
                $config['flat_pairs']
            );
        }

        $qCount = $config['qualifiers'];
        $labels = [];
        foreach ($config['pool_pairs'] as [$poolX, $poolY]) {
            for ($i = 0; $i < $qCount; $i++) {
                $labels[] = $poolX . ($i + 1) . ' vs ' . $poolY . ($qCount - $i);
            }
        }

        return $labels;
    }

    private function buildFlatPairs(array $qualifiers, array $config): array
    {
        $this->validateFlatPairs($qualifiers, $config['flat_pairs']);

        $pairs = [];
        foreach ($config['flat_pairs'] as [$poolX, $rankX, $poolY, $rankY]) {
            $pairs[] = [
                array_merge($qualifiers[$poolX][$rankX], ['source' => $poolX . ($rankX + 1)]),
                array_merge($qualifiers[$poolY][$rankY], ['source' => $poolY . ($rankY + 1)]),
            ];
        }

        return $pairs;
    }

    private function validatePoolPairs(array $qualifiers, array $poolPairs, int $required): void
    {
        foreach ($poolPairs as [$poolX, $poolY]) {
            foreach ([$poolX, $poolY] as $pool) {
                if (!array_key_exists($pool, $qualifiers)) {
                    throw new \InvalidArgumentException(
                        "Pool {$pool} is missing from qualifiers. All 8 pools (A-H) are required."
                    );
                }
                if (count($qualifiers[$pool]) < $required) {
                    throw new \InvalidArgumentException(
                        "Pool {$pool} has fewer than {$required} qualifiers (" . count($qualifiers[$pool]) . " found)."
                    );
                }
            }
        }
    }

    private function validateFlatPairs(array $qualifiers, array $flatPairs): void
    {
        foreach ($flatPairs as [$poolX, $rankX, $poolY, $rankY]) {
            foreach ([[$poolX, $rankX], [$poolY, $rankY]] as [$pool, $rank]) {
                if (!array_key_exists($pool, $qualifiers)) {
                    throw new \InvalidArgumentException("Pool {$pool} is missing from qualifiers.");
                }
                if (count($qualifiers[$pool]) <= $rank) {
                    throw new \InvalidArgumentException(
                        "Pool {$pool} has fewer than " . ($rank + 1) . " qualifiers."
                    );
                }
            }
        }
    }
}
