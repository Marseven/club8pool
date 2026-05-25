<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GameMatch;
use App\Models\Signature;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class RefereeApiController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string'],
            'pin'  => ['required', 'string'],
        ]);

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

        return response()->json($matches);
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
        return response()->json($match->load(['playerA.club', 'playerB.club', 'table', 'competition', 'signatures']));
    }

    public function frame(Request $request, GameMatch $match): JsonResponse
    {
        $data = $request->validate([
            'winner' => ['required', 'in:A,B,draw'],
            'warning_a' => ['boolean'],
            'warning_b' => ['boolean'],
        ]);

        if ($data['winner'] === 'A') {
            $match->increment('score_a');
        } elseif ($data['winner'] === 'B') {
            $match->increment('score_b');
        } else {
            $match->increment('score_a');
            $match->increment('score_b');
        }
        $match->update([
            'status' => 'live',
            'is_draw' => $data['winner'] === 'draw' ? true : $match->is_draw,
            'warning_a' => $data['warning_a'] ?? $match->warning_a,
            'warning_b' => $data['warning_b'] ?? $match->warning_b,
        ]);

        return response()->json(['match' => $match->fresh()->load(['playerA.club', 'playerB.club', 'table', 'pool', 'competition'])]);
    }

    public function start(GameMatch $match): JsonResponse
    {
        $match->update(['status' => 'live', 'started_at' => now()]);
        return response()->json($match->fresh()->load(['playerA.club', 'playerB.club', 'table', 'pool', 'competition']));
    }

    public function end(Request $request, GameMatch $match): JsonResponse
    {
        $data = $request->validate([
            'referee_note' => ['nullable', 'string'],
        ]);

        $match->update([
            'status' => 'done',
            'ended_at' => now(),
            'duration_seconds' => $match->started_at ? now()->diffInSeconds($match->started_at) : null,
            'referee_note' => $data['referee_note'] ?? null,
        ]);
        (new \App\Services\BracketProgression())->advanceWinner($match->fresh());

        return response()->json($match->fresh());
    }

    public function sign(Request $request, GameMatch $match): JsonResponse
    {
        $data = $request->validate([
            'player_id' => ['required', 'exists:players,id'],
            'signature_data' => ['nullable', 'string'],
        ]);

        $sig = Signature::updateOrCreate(
            ['match_id' => $match->id, 'player_id' => $data['player_id']],
            ['signature_data' => $data['signature_data'] ?? '✓', 'signed_at' => now()]
        );

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

        if ($match->referee_id === $userId) {
            return response()->json($match->load(['playerA.club', 'playerB.club', 'table', 'pool', 'competition']));
        }
        if ($match->referee_id) {
            return response()->json(['message' => 'Ce match est déjà pris en charge par un autre arbitre.'], 403);
        }
        if ($match->pool_id) {
            $conflict = GameMatch::where('pool_id', $match->pool_id)
                ->whereNotNull('referee_id')
                ->where('referee_id', '!=', $userId)
                ->exists();
            if ($conflict) {
                return response()->json(['message' => 'Cette poule est déjà arbitrée par un autre arbitre.'], 403);
            }
        }

        $match->update(['referee_id' => $userId]);
        return response()->json($match->fresh()->load(['playerA.club', 'playerB.club', 'table', 'pool', 'competition']));
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
}
