<?php

namespace App\Services;

use App\Models\Competition;
use App\Models\GameMatch;
use App\Models\Player;
use App\Models\Registration;

class PlayerCompetitionJourneyService
{
    public function getJourney(Player $player, Competition $competition): array
    {
        $registration = Registration::where('competition_id', $competition->id)
            ->where('player_id', $player->id)
            ->first();

        if (!$registration) {
            return ['stage' => 'not_registered', 'registration' => null];
        }

        // Pool matches
        $poolMatches = GameMatch::with(['playerA', 'playerB', 'table', 'referee'])
            ->where('competition_id', $competition->id)
            ->where('phase', 'pool')
            ->where(fn($q) => $q->where('player_a_id', $player->id)->orWhere('player_b_id', $player->id))
            ->orderBy('scheduled_at')
            ->get();

        // KO matches
        $koMatches = GameMatch::with(['playerA', 'playerB', 'table', 'referee'])
            ->where('competition_id', $competition->id)
            ->where('phase', 'knockout')
            ->where(fn($q) => $q->where('player_a_id', $player->id)->orWhere('player_b_id', $player->id))
            ->orderByRaw("CASE round WHEN 'R32' THEN 1 WHEN 'R16' THEN 2 WHEN 'QF' THEN 3 WHEN 'SF' THEN 4 WHEN '3P' THEN 5 WHEN 'F' THEN 6 ELSE 7 END")
            ->get();

        // Pool standing
        $pool = $registration->pool;
        $poolRank = null;
        $poolRecord = ['w' => 0, 'l' => 0, 'd' => 0];
        if ($pool) {
            foreach ($poolMatches as $m) {
                if ($m->status !== 'done') continue;
                $isA = $m->player_a_id === $player->id;
                $myScore = $isA ? $m->score_a : $m->score_b;
                $opScore = $isA ? $m->score_b : $m->score_a;
                if ($m->is_draw) { $poolRecord['d']++; }
                elseif ($myScore > $opScore) { $poolRecord['w']++; }
                else { $poolRecord['l']++; }
            }
        }

        // Determine stage + last KO round reached
        $eliminated = false;
        $champion = false;
        $lastKoRound = null;
        $qualified = $registration->status === 'confirmed' && $pool && $koMatches->isNotEmpty();

        foreach ($koMatches as $m) {
            if ($m->status !== 'done') continue;
            $isA = $m->player_a_id === $player->id;
            $myScore = $isA ? $m->score_a : $m->score_b;
            $opScore = $isA ? $m->score_b : $m->score_a;
            $lastKoRound = $m->round;
            if ($myScore < $opScore && !$m->is_draw) {
                $eliminated = true;
            }
            if ($m->round === 'F' && $myScore > $opScore) {
                $champion = true;
            }
        }

        // Next match
        $nextMatch = GameMatch::with(['playerA', 'playerB', 'table'])
            ->where('competition_id', $competition->id)
            ->where(fn($q) => $q->where('player_a_id', $player->id)->orWhere('player_b_id', $player->id))
            ->whereIn('status', ['scheduled', 'pending'])
            ->orderBy('scheduled_at')
            ->first();

        // Determine stage label
        $stage = $this->resolveStage(
            $registration, $pool, $poolMatches, $koMatches,
            $eliminated, $champion, $lastKoRound
        );

        return [
            'registration' => [
                'id'     => $registration->id,
                'status' => $registration->status,
                'pool'   => $pool ? ['id' => $pool->id, 'name' => $pool->name] : null,
                'seed'   => $registration->seed,
            ],
            'stage'        => $stage,
            'pool_record'  => $poolRecord,
            'pool_rank'    => $poolRank,
            'pool_matches' => $poolMatches->map(fn($m) => $this->formatMatch($m, $player->id)),
            'ko_matches'   => $koMatches->map(fn($m) => $this->formatMatch($m, $player->id)),
            'next_match'   => $nextMatch ? $this->formatMatch($nextMatch, $player->id) : null,
            'eliminated'   => $eliminated,
            'qualified'    => $qualified && !$eliminated,
            'champion'     => $champion,
            'last_ko_round'=> $lastKoRound,
        ];
    }

    private function resolveStage(
        $registration, $pool, $poolMatches, $koMatches,
        bool $eliminated, bool $champion, ?string $lastKoRound
    ): string {
        if ($champion) return 'champion';
        if ($lastKoRound) {
            if ($eliminated) {
                return match ($lastKoRound) {
                    'F'  => 'runner_up',
                    '3P' => 'third_place',
                    'SF' => 'eliminated_sf',
                    'QF' => 'eliminated_qf',
                    'R16'=> 'eliminated_r16',
                    'R32'=> 'eliminated_r32',
                    default => 'eliminated',
                };
            }
            return match ($lastKoRound) {
                'F'  => 'finalist',
                '3P' => 'third_place_match',
                'SF' => 'knockout_sf',
                'QF' => 'knockout_qf',
                'R16'=> 'knockout_r16',
                'R32'=> 'knockout_r32',
                default => 'knockout',
            };
        }
        if ($koMatches->isNotEmpty()) return 'qualified_to_knockout';
        if ($eliminated) return 'eliminated_in_pool';
        if ($pool && $poolMatches->isNotEmpty()) return 'pool_stage';
        if ($pool) return 'pool_stage';
        if ($registration->status === 'pending') return 'registered';
        return 'waiting_pool_assignment';
    }

    private function formatMatch(GameMatch $match, int $playerId): array
    {
        $isA = $match->player_a_id === $playerId;
        $myScore = $isA ? $match->score_a : $match->score_b;
        $opScore = $isA ? $match->score_b : $match->score_a;
        $opponent = $isA ? $match->playerB : $match->playerA;
        $result = null;
        if ($match->status === 'done') {
            if ($match->is_draw) $result = 'draw';
            elseif ($myScore > $opScore) $result = 'win';
            else $result = 'loss';
        }
        return [
            'id'           => $match->id,
            'phase'        => $match->phase,
            'round'        => $match->round,
            'status'       => $match->status,
            'my_score'     => $myScore,
            'op_score'     => $opScore,
            'result'       => $result,
            'opponent'     => $opponent ? ['id' => $opponent->id, 'name' => $opponent->name] : null,
            'table'        => $match->table?->name,
            'scheduled_at' => $match->scheduled_at?->toIso8601String(),
            'started_at'   => $match->started_at?->toIso8601String(),
            'ended_at'     => $match->ended_at?->toIso8601String(),
        ];
    }
}
