<?php

namespace App\Services;

class PoolKnockoutMappingService
{
    const STRATEGY = 'pool_cross_ac_bd_eg_fh';

    // Fixed pool pairs (top half first, then bottom half)
    const POOL_PAIRS = [
        ['A', 'C'],  // positions 0-3  → top half
        ['B', 'D'],  // positions 4-7  → top half
        ['E', 'G'],  // positions 8-11 → bottom half
        ['F', 'H'],  // positions 12-15 → bottom half
    ];

    /**
     * Build 16 bracket pairs from 8 pools × 4 qualifiers each.
     *
     * $qualifiers format (from KnockoutGenerator::qualifiers()):
     *   ['A' => [['player_id' => X, 'rank' => 1, ...], ...], 'B' => [...], ...]
     *   Players are already ordered rank 1 → 4 (index 0 = rank 1).
     *
     * Returns 16 pairs: each pair is [$playerA, $playerB]
     * where each player has: player_id, rank, pool_name, source (e.g. 'A1')
     *
     * @throws \InvalidArgumentException if a pool is missing or has < 4 qualifiers
     */
    public function buildPairs(array $qualifiers): array
    {
        $this->validate($qualifiers);

        $pairs = [];

        foreach (self::POOL_PAIRS as [$poolX, $poolY]) {
            $X = $qualifiers[$poolX];
            $Y = $qualifiers[$poolY];

            for ($i = 0; $i < 4; $i++) {
                $a = array_merge($X[$i], [
                    'source' => $poolX . ($i + 1),          // A1, A2, A3, A4
                ]);
                $b = array_merge($Y[3 - $i], [
                    'source' => $poolY . (4 - $i),          // C4, C3, C2, C1
                ]);
                $pairs[] = [$a, $b];
            }
        }

        return $pairs;
    }

    /**
     * Returns only the source label mapping, useful for display:
     * ['A1 vs C4', 'A2 vs C3', ...]
     */
    public function getSourceLabelMap(): array
    {
        $labels = [];
        foreach (self::POOL_PAIRS as [$poolX, $poolY]) {
            for ($i = 0; $i < 4; $i++) {
                $labels[] = $poolX . ($i + 1) . ' vs ' . $poolY . (4 - $i);
            }
        }
        return $labels;
    }

    private function validate(array $qualifiers): void
    {
        foreach (self::POOL_PAIRS as [$poolX, $poolY]) {
            foreach ([$poolX, $poolY] as $pool) {
                if (!array_key_exists($pool, $qualifiers)) {
                    throw new \InvalidArgumentException(
                        "Pool {$pool} is missing from qualifiers. All 8 pools (A-H) are required."
                    );
                }
                if (count($qualifiers[$pool]) < 4) {
                    throw new \InvalidArgumentException(
                        "Pool {$pool} has fewer than 4 qualifiers (" . count($qualifiers[$pool]) . " found). " .
                        "All pools must have at least 4 qualifiers before generating the bracket."
                    );
                }
            }
        }
    }
}
