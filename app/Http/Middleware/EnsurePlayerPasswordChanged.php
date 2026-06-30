<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsurePlayerPasswordChanged
{
    public function handle(Request $request, Closure $next)
    {
        $player = Auth::guard('player')->user();

        if ($player && $player->must_change_password) {
            // Allow through if already on the change-password route (avoid redirect loop)
            if ($request->routeIs('player.password.change') || $request->routeIs('player.password.update')) {
                return $next($request);
            }

            return redirect('/joueur/password/change')
                ->with('warning', 'Vous devez changer votre mot de passe avant de continuer.');
        }

        return $next($request);
    }
}
