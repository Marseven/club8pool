<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Club;
use App\Models\Competition;
use App\Models\Player;
use App\Models\Registration;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RegisterController extends Controller
{
    public function show(): Response
    {
        $competition = Competition::firstOrFail();
        $slots = $competition->player_slots;
        $registered = Registration::where('competition_id', $competition->id)->count();

        return Inertia::render('Public/Register', [
            'competition' => $competition,
            'clubs' => Club::orderBy('name')->get(),
            'slots' => $slots,
            'registered' => $registered,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'first_name' => ['required', 'string'],
            'last_name' => ['required', 'string'],
            'birthdate' => ['required', 'date'],
            'fgb_card' => ['required', 'string'],
            'phone' => ['required', 'string'],
            'email' => ['required', 'email'],
            'address' => ['required', 'string'],
            'club_id' => ['nullable', 'exists:clubs,id'],
            'cue' => ['nullable', 'string'],
            'competition_id' => ['required', 'exists:competitions,id'],
        ]);

        $player = Player::firstOrCreate(
            ['fgb_card' => $data['fgb_card']],
            collect($data)->except('competition_id')->toArray()
        );

        Registration::firstOrCreate(
            ['competition_id' => $data['competition_id'], 'player_id' => $player->id],
            ['status' => 'pending', 'registered_at' => now()]
        );

        return back()->with('success', 'Inscription envoyée. En attente de validation.');
    }
}
