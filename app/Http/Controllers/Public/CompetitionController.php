<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Competition;
use App\Models\GameMatch;
use App\Models\Player;
use Inertia\Inertia;
use Inertia\Response;

class CompetitionController extends Controller
{
    public function show(?string $slug = null): Response
    {
        $competition = $slug
            ? Competition::where('slug', $slug)->firstOrFail()
            : Competition::firstOrFail();

        $matches = GameMatch::with(['playerA.club', 'playerB.club', 'table', 'referee'])
            ->where('competition_id', $competition->id)
            ->orderBy('round')
            ->orderBy('round_position')
            ->get()
            ->groupBy('round');

        $ranking = Player::with('club')
            ->orderByDesc('rating')
            ->limit(16)
            ->get();

        return Inertia::render('Public/Competition', [
            'competition' => $competition,
            'matches' => $matches,
            'ranking' => $ranking,
            'liveMatches' => GameMatch::with(['playerA.club', 'playerB.club', 'table'])
                ->where('competition_id', $competition->id)
                ->where('status', 'live')
                ->get(),
            'schedule' => GameMatch::with(['playerA', 'playerB', 'table'])
                ->where('competition_id', $competition->id)
                ->orderBy('scheduled_at')
                ->get(),
        ]);
    }
}
