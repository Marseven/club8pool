<?php

namespace App\Services;

use App\Models\GameMatch;
use App\Models\Pool;
use App\Models\Registration;

class RoundRobinGenerator
{
    /**
     * Generate all pairwise matches for a pool (round-robin).
     * Returns the number of matches created.
     */
    public static function generate(Pool $pool): int
    {
        $registrations = Registration::where('pool_id', $pool->id)
            ->orderBy('pool_slot')
            ->get();

        // wipe existing pool matches for idempotency
        GameMatch::where('pool_id', $pool->id)->delete();

        $count = 0;
        $rounds = 'POOL_' . $pool->name;

        for ($i = 0; $i < $registrations->count(); $i++) {
            for ($j = $i + 1; $j < $registrations->count(); $j++) {
                GameMatch::create([
                    'competition_id' => $pool->competition_id,
                    'pool_id' => $pool->id,
                    'phase' => 'pool',
                    'round' => 'R32', // placeholder, real round is identified by phase+pool
                    'round_position' => $count,
                    'player_a_id' => $registrations[$i]->player_id,
                    'player_b_id' => $registrations[$j]->player_id,
                    'score_a' => 0,
                    'score_b' => 0,
                    'status' => 'scheduled',
                ]);
                $count++;
            }
        }

        return $count;
    }
}
