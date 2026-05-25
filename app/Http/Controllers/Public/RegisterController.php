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
            'isOpen' => $this->registrationsOpen($competition),
            'closedReason' => $this->closedReason($competition, $registered, $slots),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $competition = Competition::findOrFail($request->input('competition_id'));

        if (! $this->registrationsOpen($competition)) {
            return back()->with('error', $this->closedReason(
                $competition,
                Registration::where('competition_id', $competition->id)->count(),
                $competition->player_slots
            ));
        }

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

    private function registrationsOpen(Competition $competition): bool
    {
        if (! in_array($competition->status, ['draft', 'registration'])) {
            return false;
        }
        if ($competition->registration_closes_at && $competition->registration_closes_at->isPast()) {
            return false;
        }
        $registered = Registration::where('competition_id', $competition->id)->count();
        return $registered < $competition->player_slots;
    }

    private function closedReason(Competition $competition, int $registered, int $slots): ?string
    {
        if ($registered >= $slots) {
            return 'Tableau complet — '.$slots.' joueurs déjà inscrits.';
        }
        if ($competition->registration_closes_at && $competition->registration_closes_at->isPast()) {
            return 'Inscriptions closes depuis le '.$competition->registration_closes_at->format('d/m/Y à H\\hi').'.';
        }
        if ($competition->status === 'in_progress') {
            return 'La compétition a démarré — les inscriptions sont fermées.';
        }
        if ($competition->status === 'finished') {
            return 'Compétition terminée.';
        }
        return null;
    }
}
