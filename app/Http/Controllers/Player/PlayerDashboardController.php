<?php

namespace App\Http\Controllers\Player;

use App\Http\Controllers\Controller;
use App\Models\Competition;
use App\Models\GameMatch;
use App\Models\Registration;
use App\Services\PlayerCompetitionJourneyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class PlayerDashboardController extends Controller
{
    public function __construct(private PlayerCompetitionJourneyService $journeyService) {}

    public function dashboard(): Response
    {
        $player = Auth::guard('player')->user();

        // Active registrations with competition
        $registrations = Registration::with('competition')
            ->where('player_id', $player->id)
            ->orderByDesc('registered_at')
            ->get();

        // Recent matches (last 5)
        $recentMatches = GameMatch::with(['playerA', 'playerB', 'table', 'competition'])
            ->where(fn($q) => $q->where('player_a_id', $player->id)->orWhere('player_b_id', $player->id))
            ->whereIn('status', ['done', 'live'])
            ->orderByDesc('ended_at')
            ->limit(5)
            ->get()
            ->map(fn($m) => $this->formatMatch($m, $player->id));

        // Upcoming matches
        $nextMatch = GameMatch::with(['playerA', 'playerB', 'table', 'competition'])
            ->where(fn($q) => $q->where('player_a_id', $player->id)->orWhere('player_b_id', $player->id))
            ->whereIn('status', ['scheduled', 'pending'])
            ->orderBy('scheduled_at')
            ->first();

        // Rating
        $rating = $player->ratings()->where('discipline', '8-ball')->first();

        return Inertia::render('Player/Dashboard', [
            'player'        => [
                'id'                 => $player->id,
                'name'               => $player->name,
                'first_name'         => $player->first_name,
                'last_name'          => $player->last_name,
                'login_name'         => $player->login_name,
                'profile_photo_path' => $player->profile_photo_path,
                'wins'               => $player->wins,
                'losses'             => $player->losses,
                'win_rate'           => $player->win_rate,
            ],
            'registrations' => $registrations->map(fn($r) => [
                'id'          => $r->id,
                'status'      => $r->status,
                'competition' => [
                    'id'     => $r->competition->id,
                    'name'   => $r->competition->name,
                    'slug'   => $r->competition->slug,
                    'status' => $r->competition->status,
                ],
            ]),
            'recent_matches' => $recentMatches,
            'next_match'     => $nextMatch ? $this->formatMatch($nextMatch, $player->id) : null,
            'rating'         => $rating ? [
                'value'        => $rating->rating,
                'games_played' => $rating->games_played,
                'provisional'  => $rating->provisional,
            ] : null,
        ]);
    }

    public function competition(Competition $competition): Response|RedirectResponse
    {
        $player = Auth::guard('player')->user();

        // Ensure player is registered
        $registration = Registration::where('competition_id', $competition->id)
            ->where('player_id', $player->id)
            ->first();

        if (!$registration) {
            return redirect()->route('player.dashboard')
                ->with('error', 'Vous n\'êtes pas inscrit à cette compétition.');
        }

        $journey = $this->journeyService->getJourney($player, $competition);

        return Inertia::render('Player/Competition', [
            'player'      => [
                'id'   => $player->id,
                'name' => $player->name,
            ],
            'competition' => [
                'id'        => $competition->id,
                'name'      => $competition->name,
                'slug'      => $competition->slug,
                'status'    => $competition->status,
                'race_to'   => $competition->race_to,
                'structure' => $competition->structure,
            ],
            'journey'     => $journey,
        ]);
    }

    private function formatMatch($match, int $playerId): array
    {
        $isA = $match->player_a_id === $playerId;
        $myScore = $isA ? $match->score_a : $match->score_b;
        $opScore = $isA ? $match->score_b : $match->score_a;
        $opponent = $isA ? $match->playerB : $match->playerA;
        $result = null;

        if ($match->status === 'done') {
            if ($match->is_draw) {
                $result = 'draw';
            } elseif ($myScore > $opScore) {
                $result = 'win';
            } else {
                $result = 'loss';
            }
        }

        return [
            'id'           => $match->id,
            'phase'        => $match->phase,
            'round'        => $match->round,
            'status'       => $match->status,
            'my_score'     => $myScore,
            'op_score'     => $opScore,
            'result'       => $result,
            'is_draw'      => $match->is_draw,
            'opponent'     => $opponent ? ['id' => $opponent->id, 'name' => $opponent->name] : null,
            'table'        => $match->table?->name,
            'competition'  => $match->competition ? ['name' => $match->competition->name] : null,
            'scheduled_at' => $match->scheduled_at?->toIso8601String(),
            'ended_at'     => $match->ended_at?->toIso8601String(),
        ];
    }
}
