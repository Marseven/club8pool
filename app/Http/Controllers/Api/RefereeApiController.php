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
            'fgb_card' => ['required', 'string'],
            'pin' => ['required', 'string'],
        ]);

        $user = User::where('fgb_card', $data['fgb_card'])->where('role', 'referee')->first();

        if (! $user || ! Hash::check($data['pin'], $user->pin)) {
            return response()->json(['message' => 'Identifiants invalides.'], 401);
        }

        $token = $user->createToken('referee-mobile')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'title' => $user->title,
                'fgb_card' => $user->fgb_card,
            ],
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json($request->user());
    }

    public function queue(Request $request): JsonResponse
    {
        $matches = GameMatch::with(['playerA.club', 'playerB.club', 'table', 'competition'])
            ->where('referee_id', $request->user()->id)
            ->orderBy('scheduled_at')
            ->get();

        return response()->json($matches);
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

        return response()->json(['match' => $match->fresh()]);
    }

    public function start(GameMatch $match): JsonResponse
    {
        $match->update(['status' => 'live', 'started_at' => now()]);
        return response()->json($match->fresh());
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
}
