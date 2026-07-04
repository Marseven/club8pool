<?php

namespace App\Services;

class PoolKnockoutMappingService
{
    // 8 pools × 4 qualifiers/pool → R32 (16 pairs): A/C, B/D, E/G, F/H
    const STRATEGY = 'pool_cross_ac_bd_eg_fh';

    // 8 pools × 2 qualifiers/pool → R16 (8 pairs): A/D, B/C, E/H, F/G
    const STRATEGY_2Q = 'pool_cross_ad_bc_eh_fg';

    private static array $CONFIGS = [
        'pool_cross_ac_bd_eg_fh' => [
            'pool_pairs' => [['A','C'], ['B','D'], ['E','G'], ['F','H']],
            'qualifiers'  => 4,
        ],
        'pool_cross_ad_bc_eh_fg' => [
            'pool_pairs' => [['A','D'], ['B','C'], ['E','H'], ['F','G']],
            'qualifiers'  => 2,
        ],
    ];

    /**
     * Build bracket pairs from pool qualifiers.
     *
     * $qualifiers format (from KnockoutGenerator::qualifiers()):
     *   ['A' => [['player_id' => X, 'rank' => 1, ...], ...], ...]
     *   Players ordered rank 1 → N (index 0 = rank 1).
     *
     * Default strategy: pool_cross_ac_bd_eg_fh (4Q, R32).
     * Summer Edition 2026: pool_cross_ad_bc_eh_fg (2Q, R16).
     *
     * @throws \InvalidArgumentException if a pool is missing or has < N qualifiers
     */
    public function buildPairs(array $qualifiers, string $strategy = self::STRATEGY): array
    {
        $config = self::$CONFIGS[$strategy]
            ?? throw new \InvalidArgumentException("Unknown knockout mapping strategy: '{$strategy}'.");

        $poolPairs  = $config['pool_pairs'];
        $qCount     = $config['qualifiers'];

        $this->validate($qualifiers, $poolPairs, $qCount);

        $pairs = [];

        foreach ($poolPairs as [$poolX, $poolY]) {
            $X = $qualifiers[$poolX];
            $Y = $qualifiers[$poolY];

            for ($i = 0; $i < $qCount; $i++) {
                $a = array_merge($X[$i], [
                    'source' => $poolX . ($i + 1),               // A1, A2, …
                ]);
                $b = array_merge($Y[$qCount - 1 - $i], [
                    'source' => $poolY . ($qCount - $i),         // C4, C3 … or D2, D1
                ]);
                $pairs[] = [$a, $b];
            }
        }

        return $pairs;
    }

    /**
     * Returns source label pairs for display, e.g. ['A1 vs C4', 'A2 vs C3', …]
     */
    public function getSourceLabelMap(string $strategy = self::STRATEGY): array
    {
        $config = self::$CONFIGS[$strategy]
            ?? throw new \InvalidArgumentException("Unknown knockout mapping strategy: '{$strategy}'.");

        $qCount = $config['qualifiers'];
        $labels = [];

        foreach ($config['pool_pairs'] as [$poolX, $poolY]) {
            for ($i = 0; $i < $qCount; $i++) {
                $labels[] = $poolX . ($i + 1) . ' vs ' . $poolY . ($qCount - $i);
            }
        }

        return $labels;
    }

    private function validate(array $qualifiers, array $poolPairs, int $required): void
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
}
