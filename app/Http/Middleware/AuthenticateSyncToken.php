<?php

namespace App\Http\Middleware;

use App\Models\ApiToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** API : jeton « Authorization: Bearer csb_… » actif et de la bonne portée (`sync.token:tickets`) obligatoire. */
class AuthenticateSyncToken
{
    public function handle(Request $request, Closure $next, string $scope = ApiToken::SCOPE_STOCK): Response
    {
        $token = ApiToken::findActive((string) $request->bearerToken(), $scope);
        if (! $token) {
            return response()->json(['message' => 'Jeton d\'accès absent, invalide ou révoqué.'], 401);
        }

        $token->forceFill(['last_used_at' => now()])->saveQuietly();
        $request->attributes->set('api_token', $token);

        return $next($request);
    }
}
