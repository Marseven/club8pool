<?php

namespace App\Http\Controllers\Referee;

use App\Http\Controllers\Controller;
use App\Models\GameMatch;
use App\Models\MatchEvent;
use App\Models\Signature;
use App\Services\AuditLogService;
use App\Services\BracketProgression;
use App\Services\MatchStatisticsService;
use App\Services\PlayerRatingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class RefereeController extends Controller
{
    // ── Queue : matchs à arbitrer (hors done/cancelled) ──────────────────────

    public function queue(Request $request): Response
    {
        $userId = $request->user()->id;

        $myMatches = GameMatch::with(['playerA', 'playerB', 'table', 'competition', 'pool'])
            ->where('referee_id', $userId)
            ->whereNotIn('status', ['done', 'cancelled'])
            ->orderByRaw("CASE WHEN status = 'live' THEN 0 ELSE 1 END")
            ->orderBy('scheduled_at')
            ->get();

        $blockedPools = GameMatch::whereNotNull('referee_id')
            ->where('referee_id', '!=', $userId)
            ->whereNotNull('pool_id')
            ->pluck('pool_id')
            ->unique();

        $available = GameMatch::with(['playerA', 'playerB', 'table', 'competition', 'pool'])
            ->whereIn('status', ['pending', 'scheduled'])
            ->whereNull('referee_id')
            ->when($blockedPools->isNotEmpty(), fn ($q) => $q->whereNotIn('pool_id', $blockedPools))
            ->orderBy('scheduled_at')
            ->get();

        return Inertia::render('Referee/Queue', [
            'matches'   => $myMatches,
            'available' => $available,
        ]);
    }

    // ── Archive : matchs terminés arbitrés par cet arbitre ───────────────────

    public function archive(Request $request): Response
    {
        $matches = GameMatch::with(['playerA', 'playerB', 'table', 'competition', 'pool'])
            ->where('referee_id', $request->user()->id)
            ->whereIn('status', ['done'])
            ->orderByDesc('ended_at')
            ->limit(200)
            ->get();

        return Inertia::render('Referee/Archive', [
            'matches' => $matches,
        ]);
    }

    // ── Tables ───────────────────────────────────────────────────────────────

    public function tables(): Response
    {
        $tables = \App\Models\PoolTable::with([
            'liveMatch.playerA', 'liveMatch.playerB', 'liveMatch.referee', 'liveMatch.pool',
            'competition',
        ])->orderBy('id')->get();

        return Inertia::render('Referee/Tables', [
            'tables' => $tables,
        ]);
    }

    // ── Pré-match ─────────────────────────────────────────────────────────────

    public function preMatch(GameMatch $match): Response
    {
        return Inertia::render('Referee/PreMatch', [
            'match' => $match->load(['playerA.club', 'playerB.club', 'table', 'competition']),
        ]);
    }

    // ── Page d'arbitrage live ─────────────────────────────────────────────────

    public function live(GameMatch $match): Response
    {
        $match->load(['playerA.club', 'playerB.club', 'table', 'competition', 'pool']);

        $competition = $match->competition;

        // Effective race-to for this match
        $raceTo = (int) ($match->race_to_override
            ?? ($match->phase === 'pool'
                ? ($competition->pool_race_to ?? $competition->race_to)
                : $competition->raceForRound($match->round ?? ''))
            ?? $competition->race_to);

        // Extension state (via match_events)
        $extensionUsedA = MatchEvent::where('match_id', $match->id)
            ->where('event_type', 'shot_clock_extension')
            ->where('player_id', $match->player_a_id)
            ->exists();

        $extensionUsedB = MatchEvent::where('match_id', $match->id)
            ->where('event_type', 'shot_clock_extension')
            ->where('player_id', $match->player_b_id)
            ->exists();

        return Inertia::render('Referee/Live', [
            'match'          => $match,
            'raceTo'         => $raceTo,
            'extensionUsedA' => $extensionUsedA,
            'extensionUsedB' => $extensionUsedB,
        ]);
    }

    // ── Page fin de match (lecture seule après clôture) ───────────────────────

    public function endMatch(GameMatch $match): Response
    {
        return Inertia::render('Referee/EndMatch', [
            'match' => $match->load(['playerA.club', 'playerB.club', 'table', 'competition', 'signatures']),
        ]);
    }

    // ── Prendre un match disponible ───────────────────────────────────────────

    public function claim(Request $request, GameMatch $match): RedirectResponse
    {
        $userId = $request->user()->id;

        if ($match->referee_id === $userId) {
            return back();
        }
        if ($match->referee_id) {
            return back()->with('error', 'Ce match est déjà pris en charge par un autre arbitre.');
        }
        if ($match->pool_id) {
            $conflict = GameMatch::where('pool_id', $match->pool_id)
                ->whereNotNull('referee_id')
                ->where('referee_id', '!=', $userId)
                ->exists();
            if ($conflict) {
                return back()->with('error', 'Cette poule est déjà arbitrée par un autre arbitre.');
            }
        }

        DB::transaction(function () use ($match, $userId) {
            GameMatch::lockForUpdate()->find($match->id)->update(['referee_id' => $userId]);
        });

        AuditLogService::matchClaimed($match->fresh(), $request);

        return back()->with('success', 'Match pris en charge.');
    }

    // ── Démarrer un match ────────────────────────────────────────────────────

    public function start(Request $request, GameMatch $match): RedirectResponse
    {
        $this->authorize('start', $match);

        if ($match->status === 'done') {
            return back()->with('error', 'Ce match est déjà terminé.');
        }
        if ($match->status === 'live') {
            return redirect()->route('referee.match.live', $match);
        }

        $match->update([
            'status'     => 'live',
            'started_at' => $match->started_at ?? now(),
        ]);

        return redirect()->route('referee.match.live', $match)->with('success', 'Match démarré.');
    }

    // ── Ajouter une manche ────────────────────────────────────────────────────

    public function commitFrame(Request $request, GameMatch $match): RedirectResponse
    {
        $this->authorize('scoreFrame', $match);

        $data = $request->validate(['winner' => ['required', 'in:A,B']]);

        if ($match->status === 'done') {
            return back()->with('error', 'Impossible de scorer un match terminé.');
        }

        if ($data['winner'] === 'A') {
            $match->increment('score_a');
        } else {
            $match->increment('score_b');
        }

        if ($match->status !== 'live') {
            $match->update(['status' => 'live', 'started_at' => $match->started_at ?? now()]);
        }

        return back();
    }

    // ── Annuler la dernière manche ────────────────────────────────────────────

    public function undoFrame(Request $request, GameMatch $match): RedirectResponse
    {
        $this->authorize('undoFrame', $match);

        $data = $request->validate(['player' => ['required', 'in:A,B']]);

        if ($match->status === 'done') {
            return back()->with('error', 'Impossible de modifier un match terminé.');
        }

        if ($data['player'] === 'A' && $match->score_a > 0) {
            $match->decrement('score_a');
        } elseif ($data['player'] === 'B' && $match->score_b > 0) {
            $match->decrement('score_b');
        }

        return back();
    }

    // ── Extension joueur ─────────────────────────────────────────────────────

    public function extension(Request $request, GameMatch $match): RedirectResponse
    {
        $this->authorize('scoreFrame', $match);

        $data = $request->validate(['player' => ['required', 'in:A,B']]);
        $side = $data['player'];

        if ($match->status !== 'live') {
            return back()->with('error', 'Le match doit être en cours.');
        }

        $competition = $match->competition;
        $allowed = (int) ($competition->shot_clock_extensions_per_player ?? 0);
        if ($allowed < 1) {
            return back()->with('error', 'Les extensions ne sont pas autorisées dans cette compétition.');
        }

        $playerId = $side === 'A' ? $match->player_a_id : $match->player_b_id;

        $used = MatchEvent::where('match_id', $match->id)
            ->where('event_type', 'shot_clock_extension')
            ->where('player_id', $playerId)
            ->count();

        if ($used >= $allowed) {
            return back()->with('error', 'Extension déjà utilisée pour ce joueur.');
        }

        MatchStatisticsService::recordEvent($match, 'shot_clock_extension', $playerId);

        return back()->with('success', "Extension utilisée — Joueur {$side}.");
    }

    // ── Clôturer le match ────────────────────────────────────────────────────

    public function close(Request $request, GameMatch $match): RedirectResponse
    {
        $this->authorize('close', $match);

        if ($match->status === 'done') {
            return back()->with('error', 'Ce match est déjà clôturé.');
        }

        $competition = $match->competition;
        $race = (int) ($match->race_to_override
            ?? ($match->phase === 'pool'
                ? ($competition->pool_race_to ?? $competition->race_to)
                : $competition->raceForRound($match->round ?? ''))
            ?? $competition->race_to);

        if ($match->score_a < $race && $match->score_b < $race) {
            return back()->with('error', "Race à {$race} non atteint ({$match->score_a}–{$match->score_b}).");
        }

        if ($match->phase === 'knockout' && $match->score_a === $match->score_b) {
            return back()->with('error', 'Égalité impossible en phase finale.');
        }

        $before = [
            'score_a' => $match->score_a,
            'score_b' => $match->score_b,
            'status'  => $match->status,
        ];

        DB::transaction(function () use ($match) {
            $m = GameMatch::lockForUpdate()->find($match->id);
            $m->update([
                'status'           => 'done',
                'ended_at'         => now(),
                'duration_seconds' => $m->started_at ? (int) $m->started_at->diffInSeconds(now()) : null,
            ]);
        });

        $fresh = $match->fresh();

        try {
            (new PlayerRatingService())->applyMatchResult($fresh);
            MatchStatisticsService::aggregateForMatch($fresh);
            (new BracketProgression())->advanceWinner($fresh);
            AuditLogService::matchClosed($fresh, $before, $request);
        } catch (\Throwable $e) {
            Log::error('referee close post-processing failed', [
                'match_id' => $match->id,
                'error'    => $e->getMessage(),
            ]);
        }

        return redirect()->route('referee.queue')->with('success', 'Match clôturé.');
    }

    // ── Signature (conservé pour backward compat API/anciens matchs) ──────────

    public function sign(Request $request, GameMatch $match): RedirectResponse
    {
        $data = $request->validate([
            'player_id'      => ['required', 'exists:players,id'],
            'signature_data' => ['nullable', 'string'],
        ]);

        Signature::updateOrCreate(
            ['match_id' => $match->id, 'player_id' => $data['player_id']],
            ['signature_data' => $data['signature_data'] ?? '✓', 'signed_at' => now()]
        );

        return back()->with('success', 'Signature enregistrée.');
    }
}
