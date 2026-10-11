<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Realisation;
use App\Support\Markdown;
use App\Support\Seo;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Réalisations (cahier §4) : projets publiés, filtrables par secteur, et fiche (contexte, besoin, solution, visuels,
 * technologies).
 */
class RealisationController extends Controller
{
    public function index(Request $request): View
    {
        $sectors = Realisation::published()->whereNotNull('sector')->distinct()->orderBy('sector')->pluck('sector');
        $sector = $sectors->first(fn ($s) => $s === $request->query('secteur'));
        $search = trim((string) $request->query('q', ''));

        $realisations = Realisation::with(['cover', 'gallery'])->published()
            ->when($sector, fn ($q) => $q->where('sector', $sector))
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q->where('title', 'like', "%{$search}%")->orWhere('summary', 'like', "%{$search}%")->orWhere('client_name', 'like', "%{$search}%")->orWhere('technologies', 'like', "%{$search}%")))
            ->ordered()->paginate(9)->withQueryString();

        $seo = Seo::make('Nos réalisations', 'Projets réalisés par CSB : logiciels, applications web et mobiles, installations et accompagnement de nos clients.', canonical: route('site.realisations.index'));

        return view('site.realisations.index', [
            'realisations' => $realisations,
            'sectors' => $sectors,
            'sector' => $sector,
            'search' => $search,
            'filtered' => $sector || $search !== '',
            'seo' => ($sector || $search !== '' || $realisations->currentPage() > 1) ? $seo->noIndex() : $seo,
        ]);
    }

    public function show(Realisation $realisation): View
    {
        abort_unless($realisation->is_published, 404);
        $realisation->load(['cover', 'gallery', 'seoImage', 'logiciel']);
        $image = $realisation->shareImage();

        return view('site.realisations.show', [
            'realisation' => $realisation,
            'solutionHtml' => Markdown::toHtml($realisation->solution),
            'others' => Realisation::with(['cover', 'gallery'])->published()->whereKeyNot($realisation->id)->ordered()->limit(3)->get(),
            'seo' => Seo::make($realisation->seo_title ?: $realisation->title, $realisation->seo_description ?: $realisation->summary, $image?->url(), route('site.realisations.show', $realisation->slug), 'article'),
        ]);
    }
}
