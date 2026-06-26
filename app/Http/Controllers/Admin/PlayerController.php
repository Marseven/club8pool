<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Club;
use App\Models\Player;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PlayerController extends Controller
{
    public function index(): Response
    {
        $players = Player::with('club')
            ->orderByDesc('rating')
            ->get();

        return Inertia::render('Admin/Players/Index', [
            'players' => $players,
            'clubs' => Club::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Player::class);

        $data = $request->validate([
            'first_name' => ['required', 'string'],
            'last_name' => ['required', 'string'],
            'club_id' => ['nullable', 'exists:clubs,id'],
            'fgb_card' => ['nullable', 'string'],
            'phone' => ['nullable', 'string'],
            'email' => ['nullable', 'email'],
            'rating' => ['nullable', 'integer'],
        ]);

        Player::create($data);

        return back()->with('success', 'Joueur ajouté.');
    }
}
