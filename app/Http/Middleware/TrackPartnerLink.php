<?php

namespace App\Http\Middleware;

use App\Services\Partenaires\PartnerTracker;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Repère le lien d'un partenaire (`?ref=…`) sur les pages du site. */
class TrackPartnerLink
{
    public function __construct(private readonly PartnerTracker $tracker) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('GET') && $request->query->has('ref')) {
            $this->tracker->track($request);
        }

        return $next($request);
    }
}
