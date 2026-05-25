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
    /**
     * Liste des compétitions ouvertes à l'inscription.
     * - 0 ouverte : page "aucune inscription possible" + comps en cours/terminées
     * - 1 ouverte : redirige vers son formulaire
     * - 2+ ouvertes : liste à choisir
     */
    public function index(): Response|RedirectResponse
    {
        $all = Competition::orderByDesc('starts_on')->get();
        $open = $all->filter(fn ($c) => $this->registrationsOpen($c));

        if ($open->count() === 1) {
            return redirect()->route('register.show', ['competition' => $open->first()->slug]);
        }

        return Inertia::render('Public/RegisterIndex', [
            'open' => $open->values()->map(fn ($c) => $this->payload($c)),
            'others' => $all->reject(fn ($c) => $this->registrationsOpen($c))
                ->values()
                ->map(fn ($c) => $this->payload($c)),
        ]);
    }

    public function show(Competition $competition): Response
    {
        $slots = $competition->player_slots;
        $registered = Registration::where('competition_id', $competition->id)->count();

        return Inertia::render('Public/Register', [
            'competition' => $competition,
            'clubs' => Club::orderBy('name')->get(),
            'slots' => $slots,
            'registered' => $registered,
            'isOpen' => $this->registrationsOpen($competition),
            'closedReason' => $this->closedReason($competition, $registered, $slots),
            'other' => Competition::where('id', '!=', $competition->id)
                ->orderByDesc('starts_on')
                ->get()
                ->map(fn ($c) => $this->payload($c)),
        ]);
    }

    public function store(Request $request, Competition $competition): RedirectResponse
    {
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
        ]);

        $player = Player::firstOrCreate(
            ['fgb_card' => $data['fgb_card']],
            $data
        );

        Registration::firstOrCreate(
            ['competition_id' => $competition->id, 'player_id' => $player->id],
            ['status' => 'pending', 'registered_at' => now()]
        );

        return back()->with('success', 'Inscription envoyée. En attente de validation.');
    }

    private function payload(Competition $c): array
    {
        $registered = Registration::where('competition_id', $c->id)->count();
        return [
            'id' => $c->id,
            'name' => $c->name,
            'slug' => $c->slug,
            'discipline' => $c->discipline,
            'structure' => $c->structure,
            'status' => $c->status,
            'starts_on' => $c->starts_on?->toIso8601String(),
            'ends_on' => $c->ends_on?->toIso8601String(),
            'venue' => $c->venue,
            'city' => $c->city,
            'race_to' => $c->race_to,
            'player_slots' => $c->player_slots,
            'registered' => $registered,
            'remaining' => max(0, $c->player_slots - $registered),
            'entry_fee' => $c->entry_fee,
            'prize_pool' => $c->prize_pool,
            'is_open' => $this->registrationsOpen($c),
            'closed_reason' => $this->closedReason($c, $registered, $c->player_slots),
        ];
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
