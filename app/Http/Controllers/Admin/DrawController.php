<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Competition;
use App\Models\GameMatch;
use App\Models\Registration;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DrawController extends Controller
{
    public function show(): Response
    {
        $competition = Competition::firstOrFail();
        $registrations = Registration::with('player.club')
            ->where('competition_id', $competition->id)
            ->orderBy('seed')
            ->get();

        return Inertia::render('Admin/Draw', [
            'competition' => $competition,
            'players' => $registrations->map(fn ($r) => [
                'id' => $r->player->id,
                'name' => trim($r->player->first_name . ' ' . strtoupper($r->player->last_name)),
                'rating' => $r->player->rating,
                'club' => $r->player->club?->name . ' · ' . $r->player->club?->city,
                'seed' => $r->seed,
            ]),
        ]);
    }

    public function commit(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'pairings' => ['required', 'array'],
            'pairings.*' => ['array', 'size:2'],
        ]);

        $competition = Competition::firstOrFail();

        GameMatch::where('competition_id', $competition->id)->where('round', 'R16')->delete();

        foreach ($data['pairings'] as $idx => $pair) {
            GameMatch::create([
                'competition_id' => $competition->id,
                'round' => 'R16',
                'round_position' => $idx,
                'player_a_id' => $pair[0],
                'player_b_id' => $pair[1],
                'status' => 'scheduled',
            ]);
        }

        return redirect()->route('admin.dashboard')->with('success', 'Tirage validé.');
    }
}
