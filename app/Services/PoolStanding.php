<?php

namespace App\Services;

use App\Models\GameMatch;
use App\Models\Pool;
use Illuminate\Support\Collection;

class PoolStanding
{
    /**
     * Compute V (matches won), W (frames won), L (frames lost),
     * Diff (W-L), Rang (rank) for each player in a pool.
     */
    public static function compute(Pool $pool): Collection
    {
        $registrations = $pool->registrations()->with('player')->get();

        $matches = GameMatch::where('pool_id', $pool->id)
            ->where('phase', 'pool')
            ->get();

        $rows = $registrations->mapWithKeys(function ($r) {
            return [$r->player_id => [
                'player_id' => $r->player_id,
                'pool_slot' => $r->pool_slot,
                'player' => $r->player,
                'v' => 0,
                'w' => 0,
                'l' => 0,
                'diff' => 0,
                'warnings' => 0,
                'rank' => null,
            ]];
        })->toArray();

        foreach ($matches as $m) {
            if ($m->status !== 'done') continue;
            $a = $m->player_a_id;
            $b = $m->player_b_id;
            if (! isset($rows[$a]) || ! isset($rows[$b])) continue;

            $rows[$a]['w'] += $m->score_a;
            $rows[$a]['l'] += $m->score_b;
            $rows[$b]['w'] += $m->score_b;
            $rows[$b]['l'] += $m->score_a;

            if ($m->warning_a) $rows[$a]['warnings']++;
            if ($m->warning_b) $rows[$b]['warnings']++;

            if (! $m->is_draw) {
                if ($m->score_a > $m->score_b) $rows[$a]['v']++;
                elseif ($m->score_b > $m->score_a) $rows[$b]['v']++;
            }
        }

        foreach ($rows as &$row) {
            $row['diff'] = $row['w'] - $row['l'];
        }
        unset($row);

        $sorted = collect($rows)->sort(function ($a, $b) {
            if ($a['v'] !== $b['v']) return $b['v'] <=> $a['v'];
            if ($a['diff'] !== $b['diff']) return $b['diff'] <=> $a['diff'];
            return $b['w'] <=> $a['w'];
        })->values();

        // Rang : V puis Diff. Ex-aequo seulement si V ET Diff sont identiques.
        $rank = 0;
        $prevKey = null;
        $sorted = $sorted->map(function ($r, $i) use (&$rank, &$prevKey) {
            $key = $r['v'] . '|' . $r['diff'];
            if ($key !== $prevKey) {
                $rank = $i + 1;
                $prevKey = $key;
            }
            $r['rank'] = $rank;
            return $r;
        });

        return $sorted;
    }

    /**
     * Top N qualifiers per pool, in seeded order for the knockout bracket.
     */
    public static function qualifiers(Pool $pool, int $perPool = 2): Collection
    {
        return self::compute($pool)->take($perPool);
    }
}
