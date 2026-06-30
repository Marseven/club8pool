<?php

namespace App\Http\Controllers\Player;

use App\Http\Controllers\Controller;
use App\Http\Requests\Player\PlayerLoginRequest;
use App\Http\Requests\Player\PlayerPasswordChangeRequest;
use App\Services\PlayerLoginResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class PlayerAuthController extends Controller
{
    public function __construct(private PlayerLoginResolver $resolver) {}

    public function showLogin(): Response|RedirectResponse
    {
        if (Auth::guard('player')->check()) {
            return redirect()->route('player.dashboard');
        }

        return Inertia::render('Player/Login');
    }

    public function login(PlayerLoginRequest $request): RedirectResponse
    {
        $error = null;
        $player = $this->resolver->resolve($request->login_name, $error);

        if (!$player) {
            return back()->withErrors(['login_name' => $error])->onlyInput('login_name');
        }

        if (!$player->is_player_account_enabled) {
            return back()->withErrors(['login_name' => 'Ce compte joueur est désactivé. Contactez l\'organisation.'])->onlyInput('login_name');
        }

        if (!$player->password) {
            return back()->withErrors(['login_name' => 'Aucun compte configuré pour ce joueur. Contactez l\'organisation.'])->onlyInput('login_name');
        }

        if (!Hash::check($request->password, $player->password)) {
            return back()->withErrors(['password' => 'Mot de passe incorrect.'])->onlyInput('login_name');
        }

        Auth::guard('player')->login($player, $request->boolean('remember'));
        $request->session()->regenerate();

        // Update last login
        $player->update(['last_login_at' => now()]);

        if ($player->must_change_password) {
            return redirect()->route('player.password.change');
        }

        return redirect()->intended(route('player.dashboard'));
    }

    public function showPasswordChange(): Response
    {
        $player = Auth::guard('player')->user();

        return Inertia::render('Player/ChangePassword', [
            'forced' => $player->must_change_password,
        ]);
    }

    public function changePassword(PlayerPasswordChangeRequest $request): RedirectResponse
    {
        $player = Auth::guard('player')->user();

        // Validate current password
        if (!Hash::check($request->current_password, $player->password)) {
            return back()->withErrors(['current_password' => 'Mot de passe actuel incorrect.']);
        }

        $player->update([
            'password'             => Hash::make($request->password),
            'must_change_password' => false,
        ]);

        return redirect()->route('player.dashboard')
            ->with('success', 'Mot de passe mis à jour avec succès.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('player')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('player.login');
    }
}
