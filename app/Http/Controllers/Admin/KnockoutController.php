<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Competition;
use App\Models\GameMatch;
use App\Models\PoolTable;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\KnockoutGenerator;
use App\Services\PlayerRatingService;
use App\Services\SeedingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class KnockoutController extends Controller
{
    public function show(): \Illuminate\Http\RedirectResponse
    {
        $competition = Competition::current()
            ?? Competition::orderByDesc('starts_on')->firstOrFail();

        return redirect()->route('admin.competition.knockout', $competition);
    }

    public function showCompetition(Competition $competition): Response
    {
        $competition->load('pools');
        $generator = new KnockoutGenerator();

        $qualifiers = $generator->qualifiers($competition);
        $ties = $generator->ties($qualifiers, $competition);

        // Apply seeding strategy to get an ordered flat list of qualifiers,
        // then produce bracket pairs from that ordering.
        $seeder = new SeedingService();
        $orderedFlat = $seeder->orderQualifiers($competition, $qualifiers);
        $pairs = $generator->seedPairs($qualifiers, $orderedFlat);

        // Matchs déjà créés s'ils existent
        $existing = GameMatch::with(['playerA', 'playerB'])
            ->where('competition_id', $competition->id)
            ->where('phase', 'knockout')
            ->orderBy('round')
            ->orderBy('round_position')
            ->get()
            ->groupBy('round');

        $poolMatches = GameMatch::where('competition_id', $competition->id)
            ->where('phase', 'pool')
            ->get();
        $poolDone = $poolMatches->where('status', 'done')->count();
        $poolTotal = $poolMatches->count();

        return Inertia::render('Admin/Knockout', [
            'competition' => $competition,
            'qualifiers'  => $qualifiers,
            'ties'        => $ties,
            'pairs'       => $pairs,
            'existing'    => $existing,
            'tables'      => PoolTable::orderBy('id')->get(),
            'referees'    => User::where('role', 'referee')->orderBy('name')->get(['id', 'name', 'title']),
            'progress'    => [
                'pool_done'  => $poolDone,
                'pool_total' => $poolTotal,
                'pool_ready' => $poolDone === $poolTotal && $poolTotal > 0,
            ],
        ]);
    }

    public function startMatch(Request $request, GameMatch $match): RedirectResponse
    {
        $data = $request->validate([
            'pool_table_id' => ['required', 'exists:pool_tables,id'],
            'referee_id'    => ['nullable', 'exists:users,id'],
        ]);
        $match->update([
            'status'       => 'live',
            'pool_table_id' => $data['pool_table_id'],
            'referee_id'   => $data['referee_id'] ?? null,
            'started_at'   => now(),
        ]);
        return back()->with('success', 'Match lancé.');
    }

    public function scoreFrame(Request $request, GameMatch $match): RedirectResponse
    {
        $data = $request->validate(['player' => ['required', 'in:A,B']]);
        if ($data['player'] === 'A') $match->increment('score_a');
        else $match->increment('score_b');
        if ($match->status !== 'live') {
            $match->update(['status' => 'live', 'started_at' => $match->started_at ?? now()]);
        }
        return back();
    }

    public function undoFrame(Request $request, GameMatch $match): RedirectResponse
    {
        $data = $request->validate(['player' => ['required', 'in:A,B']]);
        if ($data['player'] === 'A' && $match->score_a > 0) $match->decrement('score_a');
        elseif ($data['player'] === 'B' && $match->score_b > 0) $match->decrement('score_b');
        return back();
    }

    public function closeMatch(Request $request, GameMatch $match): RedirectResponse
    {
        $this->authorize('scoreFrame', $match);

        $data = $request->validate([
            'score_a' => ['required', 'integer', 'min:0'],
            'score_b' => ['required', 'integer', 'min:0'],
        ]);

        if ($match->phase === 'knockout' && $data['score_a'] === $data['score_b']) {
            return back()->withErrors(['score_a' => 'Un match de phase finale ne peut pas se terminer sur une égalité.']);
        }

        $before = [
            'score_a' => $match->score_a,
            'score_b' => $match->score_b,
            'status'  => $match->status,
        ];

        $data['status']   = 'done';
        $data['ended_at'] = now();
        if ($match->started_at && ! $match->ended_at) {
            $data['duration_seconds'] = (int) $match->started_at->diffInSeconds(now());
        }
        $match->update($data);

        try {
            (new \App\Services\BracketProgression())->advanceWinner($match->fresh());
            (new PlayerRatingService())->applyMatchResult($match->fresh());
            AuditLogService::matchClosed($match->fresh(), $before);
        } catch (\Throwable $e) {
            // Log the error but don't block the response
            \Illuminate\Support\Facades\Log::error('closeMatch post-processing failed', [
                'match_id' => $match->id,
                'error'    => $e->getMessage(),
            ]);
        }

        return back()->with('success', 'Match clôturé.');
    }

    public function generateForCompetition(Request $request, Competition $competition): RedirectResponse
    {
        $data = $request->validate([
            'pairs' => ['required', 'array', 'min:1'],
            'pairs.*' => ['array', 'size:2'],
            'pairs.*.0.player_id' => ['nullable', 'integer', 'exists:players,id'],
            'pairs.*.1.player_id' => ['nullable', 'integer', 'exists:players,id'],
        ]);

        $this->authorize('generateBracket', $competition);

        DB::transaction(function () use ($competition, $data) {
            (new KnockoutGenerator())->generate($competition, $data['pairs']);
            AuditLogService::bracketGenerated($competition);
        });

        return back()->with('success', count($data['pairs']) . ' matchs de phase finale créés.');
    }

    public function generate(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'pairs' => ['required', 'array', 'min:1'],
            'pairs.*' => ['array', 'size:2'],
            'pairs.*.0.player_id' => ['nullable', 'integer', 'exists:players,id'],
            'pairs.*.1.player_id' => ['nullable', 'integer', 'exists:players,id'],
        ]);

        $competition = Competition::current()
            ?? Competition::orderByDesc('starts_on')->firstOrFail();
        $this->authorize('generateBracket', $competition);

        DB::transaction(function () use ($competition, $data) {
            (new KnockoutGenerator())->generate($competition, $data['pairs']);
            AuditLogService::bracketGenerated($competition);
        });

        return back()->with('success', count($data['pairs']) . ' matchs de phase finale créés.');
    }
}
