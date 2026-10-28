<?php

namespace App\Services\Statistiques;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Compte une page vue du site public par un visiteur humain. Ignorés : robots et aperçus de liens (WhatsApp,
 * Facebook…), requêtes AJAX et préchargements, pages en erreur, membres de l'équipe connectés. Le visiteur est une
 * empreinte anonyme du jour (adresse IP + navigateur + date + clé du site, hachés) : rien ne permet de le retrouver,
 * ni de le suivre d'un jour à l'autre. Un échec d'enregistrement n'empêche jamais l'affichage de la page.
 */
class VisitTracker
{
    /** Routes publiques qui ne sont pas des pages (fichiers techniques, réponses pour le JavaScript). */
    private const IGNORED_ROUTES = ['site.sitemap', 'site.robots', 'site.search.suggest'];

    private const BOTS = '/bot|crawl|spider|slurp|preview|facebookexternalhit|whatsapp|telegram|skype|discord|embedly|curl|wget|python|java\/|go-http|okhttp|axios|node-fetch|httpclient|headless|lighthouse|pingdom|uptime|monitor|scan|lynx/i';

    public function shouldCount(Request $request, Response $response): bool
    {
        $route = $request->route()?->getName();

        return $request->isMethod('GET') && is_string($route) && str_starts_with($route, 'site.') && ! in_array($route, self::IGNORED_ROUTES, true)
            && $response->getStatusCode() === 200 && str_contains((string) $response->headers->get('Content-Type'), 'text/html')
            && ! $request->ajax() && ! $request->expectsJson() && ! $this->isPrefetch($request)
            && ! preg_match(self::BOTS, (string) $request->userAgent()) && $request->userAgent()
            && ! Auth::guard('web')->check();
    }

    public function record(Request $request): void
    {
        try {
            DB::table('page_vues')->insert([
                'day' => today()->toDateString(),
                'visitor' => substr(hash('sha256', $request->ip().'|'.$request->userAgent().'|'.today()->toDateString().'|'.config('app.key')), 0, 32),
                'path' => mb_substr('/'.ltrim($request->path(), '/'), 0, 255),
                'route' => $request->route()?->getName(),
                'source' => $this->source($request),
                'referrer_host' => $this->referrerHost($request),
                'device' => $this->device((string) $request->userAgent()),
                'created_at' => now(),
            ]);
        } catch (Throwable $e) {
            report($e);
        }
    }

    /** D'où vient le visiteur : lien d'un partenaire, moteur de recherche, réseau social, autre site, accès direct. */
    public function source(Request $request): string
    {
        if ($request->query->has('ref') || $request->attributes->has('partenaire')) {
            return 'partenaire';
        }
        $utm = mb_strtolower((string) $request->query('utm_source'));
        $host = $utm !== '' ? $utm : (string) $this->referrerHost($request);
        if ($host === '') {
            return 'direct';
        }
        if ($host === $request->getHost()) {
            return 'interne';
        }

        return match (true) {
            str_contains($host, 'google') => 'google',
            (bool) preg_match('/bing|yahoo|duckduckgo|qwant|ecosia|yandex|baidu/', $host) => 'moteur',
            (bool) preg_match('/facebook|fb\.|messenger/', $host) => 'facebook',
            (bool) preg_match('/whatsapp|wa\.me/', $host) => 'whatsapp',
            (bool) preg_match('/instagram|tiktok|linkedin|youtube|twitter|x\.com|t\.co|telegram|snapchat/', $host) => 'reseaux',
            default => 'autre_site',
        };
    }

    private function referrerHost(Request $request): ?string
    {
        $host = parse_url((string) $request->headers->get('referer'), PHP_URL_HOST);

        return is_string($host) && $host !== '' ? mb_substr(mb_strtolower(preg_replace('/^www\./', '', $host)), 0, 150) : null;
    }

    private function device(string $agent): string
    {
        return match (true) {
            (bool) preg_match('/ipad|tablet|playbook|silk|(android(?!.*mobile))/i', $agent) => 'tablette',
            (bool) preg_match('/mobile|iphone|ipod|android|blackberry|opera mini|iemobile|windows phone/i', $agent) => 'telephone',
            default => 'ordinateur',
        };
    }

    private function isPrefetch(Request $request): bool
    {
        return str_contains(mb_strtolower($request->headers->get('Sec-Purpose', '').$request->headers->get('Purpose', '').$request->headers->get('X-Moz', '')), 'prefetch');
    }
}
