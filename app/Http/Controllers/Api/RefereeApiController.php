<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\EndMatchRequest;
use App\Http\Requests\Api\RefereeLoginRequest;
use App\Http\Requests\Api\StoreFrameRequest;
use App\Http\Requests\Api\StoreSignatureRequest;
use App\Models\GameMatch;
use App\Models\MatchIncident;
use App\Models\Signature;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\BracketProgression;
use App\Services\MatchStatisticsService;
use App\Services\PlayerRatingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class RefereeApiController extends Controller
{
    public function login(RefereeLoginRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = User::whereRaw('LOWER(name) = ?', [strtolower(trim($data['name']))])
            ->where('role', 'referee')
            ->first();

        if (! $user || ! $user->pin || ! Hash::check($data['pin'], $user->pin)) {
            return response()->json(['message' => 'Prénom ou PIN invalide.'], 401);
        }

        $token = $user->createToken('referee-mobile')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => [
                'id'    => $user->id,
                'name'  => $user->name,
                'title' => $user->title,
            ],
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json($request->user());
    }

    public function queue(Request $request): JsonResponse
    {
        $matches = GameMatch::with(['playerA.club', 'playerB.club', 'table', 'competition', 'pool'])
            ->where('referee_id', $request->user()->id)
            ->orderBy('scheduled_at')
            ->get();

        // Augment each match with shot_clock_config and rack_mode for the mobile queue screen
        $augmented = $matches->map(function (GameMatch $m) {
            $data = $m->toArray();
            $data['shot_clock_config'] = $m->competition ? [
                'enabled'               => $m->competition->shot_clock_enabled ?? false,
                'seconds'               => $m->competition->shot_clock ?? 30,
                'late_seconds'          => $m->competition->shot_clock_late_seconds ?? 15,
                'late_rule'             => $m->competition->shot_clock_late_rule ?? 'never',
                'extensions_per_player' => $m->competition->shot_clock_extensions_per_player ?? 1,
            ] : null;
            $data['rack_mode'] = $m->competition?->rack_mode ?? 'triangle';
            return $data;
        });

        return response()->json($augmented);
    }

    public function tables(): JsonResponse
    {
        $tables = \App\Models\PoolTable::with([
            'liveMatch.playerA', 'liveMatch.playerB', 'liveMatch.referee', 'liveMatch.pool',
        ])->orderBy('id')->get();

        return response()->json($tables);
    }

    public function show(GameMatch $match): JsonResponse
    {
        $match->load(['playerA.club', 'playerB.club', 'table', 'competition', 'signatures']);

        // New optional fields for mobile app v2 (backward-compatible — only adds, never removes)
        $extra = [
            'shot_clock_config' => $match->competition ? [
                'enabled'                   => $match->competition->shot_clock_enabled ?? false,
                'seconds'                   => $match->competition->shot_clock ?? 30,
                'late_seconds'              => $match->competition->shot_clock_late_seconds ?? 15,
                'late_rule'                 => $match->competition->shot_clock_late_rule ?? 'never',
                'extensions_per_player'     => $match->competition->shot_clock_extensions_per_player ?? 1,
            ] : null,
            'rack_mode'            => $match->competition?->rack_mode ?? 'triangle',
            'tie_break_mode'       => $match->competition?->tie_break_mode ?? 'none',
            'push_out_enabled'     => $match->competition?->push_out_enabled ?? false,
            'player_rating_summary' => [
                'a' => \App\Models\PlayerRating::where('player_id', $match->player_a_id)
                        ->where('discipline', $match->competition?->discipline ?? '8-ball')
                        ->select('rating', 'games_played', 'provisional')
                        ->first(),
                'b' => \App\Models\PlayerRating::where('player_id', $match->player_b_id)
                        ->where('discipline', $match->competition?->discipline ?? '8-ball')
                        ->select('rating', 'games_played', 'provisional')
                        ->first(),
            ],
            'allowed_events' => ['foul', 'safety', 'warning', 'miss', 'break_and_run', 'shot_clock_extension', 'shot_clock_violation', 're_rack', 'timeout', 'coaching_request', 'other'],
        ];

        return response()->json(array_merge($match->toArray(), $extra));
    }

    public function frame(StoreFrameRequest $request, GameMatch $match): JsonResponse
    {
        $data = $request->validated();

        $freshMatch = DB::transaction(function () use ($data, $match) {
            $m = GameMatch::lockForUpdate()->find($match->id);

            if ($data['winner'] === 'A') {
                $m->increment('score_a');
            } elseif ($data['winner'] === 'B') {
                $m->increment('score_b');
            } else {
                $m->increment('score_a');
                $m->increment('score_b');
            }

            $m->update([
                'status'    => 'live',
                'is_draw'   => $data['winner'] === 'draw' ? true : $m->is_draw,
                'warning_a' => $data['warning_a'] ?? $m->warning_a,
                'warning_b' => $data['warning_b'] ?? $m->warning_b,
            ]);

            return $m->fresh()->load(['playerA.club', 'playerB.club', 'table', 'pool', 'competition']);
        });

        return response()->json(['match' => $freshMatch]);
    }

    public function start(GameMatch $match): JsonResponse
    {
        $match->update(['status' => 'live', 'started_at' => now()]);

        return response()->json($match->fresh()->load(['playerA.club', 'playerB.club', 'table', 'pool', 'competition']));
    }

    public function end(EndMatchRequest $request, GameMatch $match): JsonResponse
    {
        $data = $request->validated();

        $before = ['status' => $match->status, 'score_a' => $match->score_a, 'score_b' => $match->score_b];

        $freshMatch = DB::transaction(function () use ($data, $match) {
            $m = GameMatch::lockForUpdate()->find($match->id);

            $m->update([
                'status'           => 'done',
                'ended_at'         => now(),
                'duration_seconds' => $m->started_at ? (int) $m->started_at->diffInSeconds(now()) : null,
                'referee_note'     => $data['referee_note'] ?? null,
            ]);

            $fresh = $m->fresh();

            (new PlayerRatingService())->applyMatchResult($fresh);
            MatchStatisticsService::aggregateForMatch($fresh);

            return $fresh;
        });

        // Advance bracket winner — non-fatal: a crash here must never 500 the endpoint
        try {
            (new BracketProgression())->advanceWinner($freshMatch->fresh());
        } catch (\Throwable $e) {
            Log::warning('BracketProgression failed on match #' . $match->id . ': ' . $e->getMessage());
        }

        AuditLogService::matchClosed($freshMatch->fresh(), $before, $request);

        return response()->json($freshMatch->fresh()->load(['playerA.club', 'playerB.club', 'table', 'pool', 'competition', 'signatures']));
    }

    public function sign(StoreSignatureRequest $request, GameMatch $match): JsonResponse
    {
        $data = $request->validated();

        $sig = Signature::updateOrCreate(
            ['match_id' => $match->id, 'player_id' => $data['player_id']],
            ['signature_data' => $data['signature_data'] ?? '✓', 'signed_at' => now()]
        );

        AuditLogService::signatureRecorded($sig, $match->id, $match->competition_id, $request);

        return response()->json($sig);
    }

    public function available(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        // Pool IDs already taken by a DIFFERENT referee
        $blockedPools = GameMatch::whereNotNull('referee_id')
            ->where('referee_id', '!=', $userId)
            ->whereNotNull('pool_id')
            ->pluck('pool_id')
            ->unique();

        $matches = GameMatch::with(['playerA.club', 'playerB.club', 'table', 'pool', 'competition'])
            ->whereIn('status', ['pending', 'scheduled'])
            ->whereNull('referee_id')
            ->when($blockedPools->isNotEmpty(), fn($q) => $q->whereNotIn('pool_id', $blockedPools))
            ->orderBy('scheduled_at')
            ->get();

        return response()->json($matches);
    }

    public function claim(Request $request, GameMatch $match): JsonResponse
    {
        $userId = $request->user()->id;

        $result = DB::transaction(function () use ($userId, $match) {
            $freshMatch = GameMatch::lockForUpdate()->find($match->id);

            if ($freshMatch->referee_id === $userId) {
                return $freshMatch->load(['playerA.club', 'playerB.club', 'table', 'pool', 'competition']);
            }

            if ($freshMatch->referee_id) {
                return ['error' => 'Ce match est déjà pris en charge par un autre arbitre.', 'status' => 403];
            }

            if ($freshMatch->pool_id) {
                $conflict = GameMatch::where('pool_id', $freshMatch->pool_id)
                    ->whereNotNull('referee_id')
                    ->where('referee_id', '!=', $userId)
                    ->exists();

                if ($conflict) {
                    return ['error' => 'Cette poule est déjà arbitrée par un autre arbitre.', 'status' => 403];
                }
            }

            $freshMatch->update(['referee_id' => $userId]);

            return $freshMatch->fresh()->load(['playerA.club', 'playerB.club', 'table', 'pool', 'competition']);
        });

        if (is_array($result) && isset($result['error'])) {
            return response()->json(['message' => $result['error']], $result['status']);
        }

        AuditLogService::matchClaimed($result, $request);

        return response()->json($result);
    }

    public function assignTable(Request $request, GameMatch $match): JsonResponse
    {
        $data = $request->validate([
            'table_id' => ['required', 'exists:pool_tables,id'],
        ]);

        $match->update(['pool_table_id' => $data['table_id']]);

        return response()->json($match->fresh()->load(['playerA.club', 'playerB.club', 'table', 'pool', 'competition']));
    }

    public function undoFrame(Request $request, GameMatch $match): JsonResponse
    {
        $data = $request->validate([
            'player' => ['required', 'in:A,B'],
        ]);

        if ($data['player'] === 'A' && $match->score_a > 0) {
            $match->decrement('score_a');
        } elseif ($data['player'] === 'B' && $match->score_b > 0) {
            $match->decrement('score_b');
        }

        return response()->json(['match' => $match->fresh()->load(['playerA.club', 'playerB.club', 'table', 'pool', 'competition'])]);
    }

    public function addWarning(Request $request, GameMatch $match): JsonResponse
    {
        $data = $request->validate([
            'player' => ['required', 'in:A,B'],
        ]);

        if ($data['player'] === 'A') {
            $match->update(['warning_a' => true]);
        } else {
            $match->update(['warning_b' => true]);
        }

        return response()->json(['match' => $match->fresh()->load(['playerA.club', 'playerB.club', 'table', 'pool', 'competition'])]);
    }

    public function recordEvent(Request $request, GameMatch $match): JsonResponse
    {
        if ($match->referee_id && $match->referee_id !== $request->user()->id && $request->user()->role !== 'admin') {
            return response()->json(['message' => 'Vous n\'êtes pas l\'arbitre assigné à ce match.'], 403);
        }

        $data = $request->validate([
            'event_type'   => ['required', 'string', 'in:foul,safety,warning,miss,break_and_run,shot_clock_extension,shot_clock_violation,re_rack,timeout,coaching_request,other'],
            'player'       => ['nullable', 'in:A,B'],
            'frame_number' => ['nullable', 'integer', 'min:1'],
            'metadata'     => ['nullable', 'array'],
        ]);

        $playerId = null;
        if (isset($data['player'])) {
            $playerId = $data['player'] === 'A' ? $match->player_a_id : $match->player_b_id;
        }

        $event = MatchStatisticsService::recordEvent(
            $match,
            $data['event_type'],
            $playerId,
            $data['frame_number'] ?? null,
            $data['metadata'] ?? []
        );

        return response()->json($event);
    }

    public function createIncident(Request $request, GameMatch $match): JsonResponse
    {
        $data = $request->validate([
            'type'        => ['required', 'string', 'in:rule_dispute,score_dispute,player_absence,equipment_issue,conduct_warning,correction_request,other'],
            'description' => ['required', 'string', 'min:5', 'max:1000'],
            'severity'    => ['nullable', 'string', 'in:low,medium,high,critical'],
        ]);

        $incident = MatchIncident::create([
            'competition_id'  => $match->competition_id,
            'match_id'        => $match->id,
            'reported_by_id'  => $request->user()->id,
            'type'            => $data['type'],
            'severity'        => $data['severity'] ?? 'medium',
            'status'          => 'open',
            'description'     => $data['description'],
        ]);

        return response()->json($incident, 201);
    }

    public function resolveTiebreak(Request $request, GameMatch $match): JsonResponse
    {
        $data = $request->validate([
            'mode'             => ['required', 'string', 'in:shootout,race_to_one'],
            'winner_player_id' => ['required', 'exists:players,id'],
            'score_a'          => ['nullable', 'integer', 'min:0'],
            'score_b'          => ['nullable', 'integer', 'min:0'],
        ]);

        $freshMatch = DB::transaction(function () use ($data, $match, $request) {
            $tiebreak = DB::table('match_tiebreaks')
                ->where('match_id', $match->id)
                ->first();

            $tiebreakData = [
                'mode'             => $data['mode'],
                'winner_player_id' => $data['winner_player_id'],
                'score_a'          => $data['score_a'] ?? null,
                'score_b'          => $data['score_b'] ?? null,
                'decided_by_id'    => $request->user()->id,
                'decided_at'       => now(),
                'updated_at'       => now(),
            ];

            if ($tiebreak) {
                DB::table('match_tiebreaks')
                    ->where('match_id', $match->id)
                    ->update($tiebreakData);
            } else {
                DB::table('match_tiebreaks')->insert(array_merge($tiebreakData, [
                    'match_id'   => $match->id,
                    'created_at' => now(),
                ]));
            }

            $m = GameMatch::lockForUpdate()->find($match->id);

            if ($m->is_draw || $m->score_a === $m->score_b) {
                // Determine winner column by checking which player won the tiebreak
                $winnerUpdate = [];
                if ($m->player_a_id == $data['winner_player_id']) {
                    $winnerUpdate['score_a'] = ($m->score_a ?? 0) + 1;
                } elseif ($m->player_b_id == $data['winner_player_id']) {
                    $winnerUpdate['score_b'] = ($m->score_b ?? 0) + 1;
                }
                $m->update(array_merge($winnerUpdate, [
                    'status'  => 'done',
                    'is_draw' => false,
                ]));
            }

            return $m->fresh()->load(['playerA.club', 'playerB.club', 'table', 'pool', 'competition']);
        });

        return response()->json(['match' => $freshMatch]);
    }
}
