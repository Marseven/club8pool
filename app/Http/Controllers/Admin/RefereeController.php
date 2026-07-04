<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GameMatch;
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

    public function update(Request $request, User $user): RedirectResponse
    {
        abort_if($user->role !== 'referee', 403);

        $data = $request->validate([
            'name'     => ['required', 'string'],
            'email'    => ['nullable', 'email', "unique:users,email,{$user->id}"],
            'fgb_card' => ['required', 'string', "unique:users,fgb_card,{$user->id}"],
            'pin'      => ['nullable', 'string', 'min:4'],
            'title'    => ['nullable', 'string'],
        ]);

        $fill = [
            'name'     => $data['name'],
            'email'    => $data['email'] ?? null,
            'fgb_card' => $data['fgb_card'],
            'title'    => $data['title'] ?? 'Arbitre',
        ];

        if (!empty($data['pin'])) {
            $fill['pin']      = Hash::make($data['pin']);
            $fill['password'] = Hash::make($data['pin']);
        }

        $user->update($fill);

        return back()->with('success', 'Arbitre mis à jour.');
    }

    public function destroy(User $user): RedirectResponse
    {
        abort_if($user->role !== 'referee', 403);

        $hasLive = GameMatch::where('referee_id', $user->id)->where('status', 'live')->exists();
        if ($hasLive) {
            return back()->with('error', 'Impossible de supprimer un arbitre avec un match en cours.');
        }

        $user->delete();

        return back()->with('success', 'Arbitre supprimé.');
    }
}
