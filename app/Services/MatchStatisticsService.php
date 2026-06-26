<?php

namespace App\Services;

use App\Models\GameMatch;
use App\Models\MatchEvent;
use App\Models\PlayerCompetitionStatistic;
use Illuminate\Support\Facades\DB;

class MatchStatisticsService
{
    public static function recordEvent(
        GameMatch $match,
        string $eventType,
        ?int $playerId = null,
        ?int $frameNumber = null,
        array $metadata = []
    ): MatchEvent {
        return MatchEvent::create([
            'competition_id' => $match->competition_id,
            'match_id'       => $match->id,
            'frame_number'   => $frameNumber,
            'actor_id'       => auth()->id(),
            'player_id'      => $playerId,
            'event_type'     => $eventType,
            'metadata'       => !empty($metadata) ? $metadata : null,
            'occurred_at'    => now(),
        ]);
    }

    public static function aggregateForMatch(GameMatch $match): void
    {
        if (!$match->player_a_id || !$match->player_b_id) return;

        DB::transaction(function () use ($match) {
            $events = MatchEvent::where('match_id', $match->id)->get();

            foreach ([$match->player_a_id, $match->player_b_id] as $playerId) {
                $playerEvents = $events->where('player_id', $playerId);
                $won = $match->status === 'done' && (
                    ($playerId === $match->player_a_id && $match->score_a > $match->score_b) ||
                    ($playerId === $match->player_b_id && $match->score_b > $match->score_a)
                );

                $stat = PlayerCompetitionStatistic::firstOrCreate([
                    'competition_id' => $match->competition_id,
                    'player_id'      => $playerId,
                ]);

                $stat->increment('matches_played');
                if ($won) {
                    $stat->increment('matches_won');
                } else {
                    $stat->increment('matches_lost');
                }

                $framesWon  = $playerId === $match->player_a_id ? $match->score_a : $match->score_b;
                $framesLost = $playerId === $match->player_a_id ? $match->score_b : $match->score_a;

                $stat->increment('frames_won', $framesWon);
                $stat->increment('frames_lost', $framesLost);
                $stat->increment('break_and_runs', $playerEvents->where('event_type', 'break_and_run')->count());
                $stat->increment('safeties', $playerEvents->where('event_type', 'safety')->count());
                $stat->increment('fouls', $playerEvents->where('event_type', 'foul')->count());
                $stat->increment('misses', $playerEvents->where('event_type', 'miss')->count());
                $stat->increment('warnings', $playerEvents->where('event_type', 'warning')->count());
                $stat->increment('shot_clock_violations', $playerEvents->where('event_type', 'shot_clock_violation')->count());
                $stat->increment('breaks_taken', $playerEvents->where('event_type', 'break')->count());
                $stat->increment('break_wins', $playerEvents->where('event_type', 'break_win')->count());
            }
        });
    }

    public static function markStale(int $matchId): void
    {
        $match = GameMatch::find($matchId);
        if (!$match) return;

        PlayerCompetitionStatistic::where('competition_id', $match->competition_id)
            ->whereIn('player_id', array_filter([$match->player_a_id, $match->player_b_id]))
            ->update(['is_stale' => true]);
    }
}
