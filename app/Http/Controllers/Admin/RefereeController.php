<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class RefereeController extends Controller
{
    public function index(): Response
    {
        $referees = User::where('role', 'referee')->orderBy('name')->get();

        return Inertia::render('Admin/Referees/Index', [
            'referees' => $referees,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string'],
            'email' => ['nullable', 'email', 'unique:users,email'],
            'fgb_card' => ['required', 'string', 'unique:users,fgb_card'],
            'pin' => ['required', 'string', 'min:4'],
            'title' => ['nullable', 'string'],
        ]);

        User::create([
            'name' => $data['name'],
            'email' => $data['email'] ?? null,
            'fgb_card' => $data['fgb_card'],
            'pin' => Hash::make($data['pin']),
            'password' => Hash::make($data['pin']),
            'role' => 'referee',
            'title' => $data['title'] ?? 'Arbitre',
        ]);

        return back()->with('success', 'Arbitre ajouté.');
    }
}
