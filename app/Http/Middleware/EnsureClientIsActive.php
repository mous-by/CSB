<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/** Un compte client désactivé perd l'accès immédiatement, sans attendre la fin de sa session. */
class EnsureClientIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $client = $request->user('client');

        if ($client === null || ($client->is_active && ! $client->trashed())) {
            return $next($request);
        }

        Auth::guard('client')->logout();
        $request->session()->invalidate();

        return redirect()->route('client.login')->withErrors(['login' => 'Ce compte est désactivé. Contactez CSB.']);
    }
}
