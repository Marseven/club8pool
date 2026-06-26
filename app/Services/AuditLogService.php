<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuditLogService
{
    public static function log(
        string $action,
        ?object $auditable = null,
        array $before = [],
        array $after = [],
        ?int $competitionId = null,
        ?Request $request = null
    ): AuditLog {
        $user = Auth::user();
        $req = $request ?? request();

        return AuditLog::create([
            'actor_id'       => $user?->id,
            'actor_role'     => $user?->role,
            'action'         => $action,
            'auditable_type' => $auditable ? get_class($auditable) : null,
            'auditable_id'   => $auditable?->id,
            'competition_id' => $competitionId,
            'payload_before' => !empty($before) ? $before : null,
            'payload_after'  => !empty($after) ? $after : null,
            'ip_address'     => $req?->ip(),
            'user_agent'     => $req?->userAgent() ? substr($req->userAgent(), 0, 255) : null,
        ]);
    }

    public static function matchClosed(object $match, array $before, ?Request $r = null): AuditLog
    {
        return self::log(
            'match.close',
            $match,
            $before,
            ['status' => 'done', 'score_a' => $match->score_a, 'score_b' => $match->score_b],
            $match->competition_id,
            $r
        );
    }

    public static function scoreCorrection(object $match, array $before, string $reason, ?Request $r = null): AuditLog
    {
        return self::log(
            'score.corrected',
            $match,
            $before,
            ['score_a' => $match->score_a, 'score_b' => $match->score_b, 'reason' => $reason],
            $match->competition_id,
            $r
        );
    }

    public static function bracketGenerated(object $competition, ?Request $r = null): AuditLog
    {
        return self::log(
            'bracket.generated',
            $competition,
            [],
            ['competition_id' => $competition->id],
            $competition->id,
            $r
        );
    }

    public static function matchClaimed(object $match, ?Request $r = null): AuditLog
    {
        return self::log(
            'match.claimed',
            $match,
            [],
            ['referee_id' => Auth::id()],
            $match->competition_id,
            $r
        );
    }

    public static function signatureRecorded(object $sig, int $matchId, ?int $competitionId, ?Request $r = null): AuditLog
    {
        // Never store signature_data in audit — store only metadata
        return self::log(
            'signature.recorded',
            $sig,
            [],
            [
                'match_id'  => $matchId,
                'player_id' => $sig->player_id,
                'signed_at' => $sig->signed_at,
            ],
            $competitionId,
            $r
        );
    }
}
