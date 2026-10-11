<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Logiciel;
use App\Models\Materiel;
use App\Models\Realisation;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Référencement technique (cahier §11.1) : plan de site XML, robots.txt et redirections des anciennes adresses.
 */
class SeoController extends Controller
{
    public function sitemap(): Response
    {
        $urls = collect(['site.home', 'site.services', 'site.logiciels.index', 'site.materiels.index', 'site.realisations.index', 'site.actualites.index', 'site.about', 'site.contact', 'site.devis', 'site.legal', 'site.privacy'])
            ->filter(fn ($route) => Route::has($route))
            ->map(fn ($route) => ['loc' => route($route), 'lastmod' => null]);

        $add = function ($items, string $route) use (&$urls) {
            foreach ($items as $item) {
                $urls->push(['loc' => route($route, $item->slug), 'lastmod' => $item->updated_at]);
            }
        };
        $add(Logiciel::published()->get(['slug', 'updated_at']), 'site.logiciels.show');
        $add(Materiel::published()->get(['slug', 'updated_at']), 'site.materiels.show');
        $add(Realisation::published()->get(['slug', 'updated_at']), 'site.realisations.show');
        $add(Article::published()->get(['slug', 'updated_at']), 'site.actualites.show');

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n".'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
        foreach ($urls as $url) {
            $xml .= '  <url><loc>'.e($url['loc']).'</loc>'.($url['lastmod'] ? '<lastmod>'.$url['lastmod']->toAtomString().'</lastmod>' : '')."</url>\n";
        }

        return response($xml.'</urlset>'."\n", 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    /** Production : tout le site sauf l'administration ; ailleurs (essai, préproduction) : rien n'est indexé. */
    public function robots(): Response
    {
        $lines = app()->environment('production')
            ? ['User-agent: *', 'Disallow: /'.trim((string) config('site.admin_path'), '/').'/', '', 'Sitemap: '.route('site.sitemap')]
            : ['User-agent: *', 'Disallow: /'];

        return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    /** Adresse inconnue : 404 du site (une ancienne adresse est redirigée par bootstrap/app.php, Redirection::follow). */
    public function fallback(): never
    {
        throw new NotFoundHttpException;
    }
}
