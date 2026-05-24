<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureReferee
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->isReferee()) {
            return redirect()->route('login')->with('error', 'Accès arbitre requis.');
        }

        return $next($request);
    }
}
