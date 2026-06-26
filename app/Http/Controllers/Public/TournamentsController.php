<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Competition;
use App\Models\Registration;
use Inertia\Inertia;
use Inertia\Response;

class TournamentsController extends Controller
{
    public function index(): Response
    {
        $finished = Competition::where('status', 'finished')
            ->withCount(['registrations', 'matches'])
            ->orderByDesc('ends_on')
            ->get()
            ->map(fn ($c) => [
                'id'          => $c->id,
                'name'        => $c->name,
                'slug'        => $c->slug,
                'discipline'  => $c->discipline,
                'structure'   => $c->structure,
                'venue'       => $c->venue,
                'city'        => $c->city,
                'starts_on'   => $c->starts_on?->toDateString(),
                'ends_on'     => $c->ends_on?->toDateString(),
                'prize_pool'  => $c->prize_pool,
                'player_slots' => $c->player_slots,
                'registrations_count' => $c->registrations_count,
                'matches_count'       => $c->matches_count,
                'logo_url'    => $c->logo_url,
            ]);

        return Inertia::render('Public/Tournaments', [
            'competitions' => $finished,
        ]);
    }
}
