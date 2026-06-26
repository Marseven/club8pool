<?php

namespace App\Services;

use App\Models\Competition;
use App\Models\PlayerRating;
use App\Models\Registration;

class SeedingService
{
    /**
     * Order qualifiers according to the competition's seed_strategy.
     *
     * @param  Competition  $competition
     * @param  array  $qualifiers  Keyed by pool name; values are arrays of players
     *                             with at least ['player_id', 'player', ...].
     * @return array  Flat, ordered list of players ready for bracket seeding.
     */
    public function orderQualifiers(Competition $competition, array $qualifiers): array
    {
        $flat = $this->flatten($qualifiers);

        return match ($competition->seed_strategy ?? 'random') {
            'rating'  => $this->applyRatingStrategy($flat, $competition),
            'manual'  => $this->applyManualStrategy($flat, $competition),
            'hybrid'  => $this->applyHybridStrategy($flat, $competition),
            default   => $this->applyRandomStrategy($flat),
        };
    }

    // -------------------------------------------------------------------------
    // Strategies
    // -------------------------------------------------------------------------

    /**
     * random: shuffle all qualifiers.
     */
    private function applyRandomStrategy(array $flat): array
    {
        shuffle($flat);
        return $flat;
    }

    /**
     * rating: sort by Elo rating descending (player_ratings table, fallback to
     * players.rating), then apply classic 1-vs-last seeding.
     */
    private function applyRatingStrategy(array $flat, Competition $competition): array
    {
        $ratings = $this->fetchRatings(
            array_column($flat, 'player_id'),
            $competition->discipline ?? '8-ball'
        );

        usort($flat, function ($a, $b) use ($ratings) {
            $ra = $ratings[$a['player_id']] ?? ($a['player']->rating ?? 0);
            $rb = $ratings[$b['player_id']] ?? ($b['player']->rating ?? 0);
            return $rb <=> $ra; // descending
        });

        return $flat;
    }

    /**
     * manual: respect registrations.seed_rating to determine order.
     * Players without a seed_rating go to the end.
     */
    private function applyManualStrategy(array $flat, Competition $competition): array
    {
        $seedRatings = $this->fetchSeedRatings(
            array_column($flat, 'player_id'),
            $competition->id
        );

        usort($flat, function ($a, $b) use ($seedRatings) {
            $ra = $seedRatings[$a['player_id']] ?? PHP_INT_MAX;
            $rb = $seedRatings[$b['player_id']] ?? PHP_INT_MAX;
            return $ra <=> $rb; // ascending (lower seed_rating = better seed)
        });

        return $flat;
    }

    /**
     * hybrid: top N players seeded by rating; remaining positions optionally
     * randomized based on competition->draw_randomize_unseeded.
     */
    private function applyHybridStrategy(array $flat, Competition $competition): array
    {
        $n = (int) ($competition->seeded_players_count ?? 0);

        if ($n <= 0) {
            // No seeded players → equivalent to random
            return $this->applyRandomStrategy($flat);
        }

        $ratings = $this->fetchRatings(
            array_column($flat, 'player_id'),
            $competition->discipline ?? '8-ball'
        );

        // Sort all by rating descending to pick the top N
        $sorted = $flat;
        usort($sorted, function ($a, $b) use ($ratings) {
            $ra = $ratings[$a['player_id']] ?? ($a['player']->rating ?? 0);
            $rb = $ratings[$b['player_id']] ?? ($b['player']->rating ?? 0);
            return $rb <=> $ra;
        });

        $seeded   = array_slice($sorted, 0, $n);
        $unseeded = array_slice($sorted, $n);

        if ($competition->draw_randomize_unseeded ?? true) {
            shuffle($unseeded);
        }

        return array_merge($seeded, $unseeded);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Flatten a pool-keyed array into a single list.
     */
    private function flatten(array $qualifiers): array
    {
        $flat = [];
        foreach ($qualifiers as $players) {
            foreach ($players as $player) {
                $flat[] = $player;
            }
        }
        return $flat;
    }

    /**
     * Load player_ratings for given player IDs and discipline.
     * Returns [player_id => rating].
     */
    private function fetchRatings(array $playerIds, string $discipline): array
    {
        if (empty($playerIds)) {
            return [];
        }

        return PlayerRating::whereIn('player_id', $playerIds)
            ->where('discipline', $discipline)
            ->get(['player_id', 'rating'])
            ->pluck('rating', 'player_id')
            ->all();
    }

    /**
     * Load seed_rating from registrations for given player IDs and competition.
     * Returns [player_id => seed_rating].
     */
    private function fetchSeedRatings(array $playerIds, int $competitionId): array
    {
        if (empty($playerIds)) {
            return [];
        }

        return Registration::whereIn('player_id', $playerIds)
            ->where('competition_id', $competitionId)
            ->whereNotNull('seed_rating')
            ->get(['player_id', 'seed_rating'])
            ->pluck('seed_rating', 'player_id')
            ->all();
    }
}
