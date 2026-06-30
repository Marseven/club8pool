<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class LoginController extends Controller
{
    public function showLogin(): Response
    {
        return Inertia::render('Auth/Login');
    }

    public function login(Request $request): RedirectResponse
    {
        $mode = $request->input('mode', 'admin');

        return $mode === 'referee'
            ? $this->loginReferee($request)
            : $this->loginAdmin($request);
    }

    private function loginAdmin(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors([
                'email' => 'Identifiants invalides.',
            ])->onlyInput('email');
        }

        $request->session()->regenerate();
        $user = $request->user();

        return $user->isReferee()
            ? redirect()->route('referee.queue')
            : redirect()->route('admin.dashboard');
    }

    private function loginReferee(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string'],
            'pin'  => ['required', 'string'],
        ]);

        $resolver = new \App\Services\RefereeLoginResolver();
        $error = null;
        $user = $resolver->resolve($data['name'], $error);

        if (!$user) {
            return back()->withErrors(['name' => $error ?? 'Prénom ou PIN invalide.'])->onlyInput('name');
        }

        if (!($user->is_referee_active ?? true)) {
            return back()->withErrors(['name' => 'Ce compte arbitre est désactivé.'])->onlyInput('name');
        }

        if (!$user->pin || !Hash::check($data['pin'], $user->pin)) {
            return back()->withErrors(['name' => 'Prénom ou PIN invalide.'])->onlyInput('name');
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->route('referee.queue');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
