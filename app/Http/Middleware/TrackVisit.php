<?php

namespace App\Http\Middleware;

use App\Services\Statistiques\VisitTracker;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Compte la page vue du site public une fois la réponse prête (statistiques de fréquentation). */
class TrackVisit
{
    public function __construct(private readonly VisitTracker $tracker) {}

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($this->tracker->shouldCount($request, $response)) {
            $this->tracker->record($request);
        }

        return $response;
    }
}
