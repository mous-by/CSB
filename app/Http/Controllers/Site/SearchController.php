<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Logiciel;
use App\Models\Materiel;
use App\Models\Prestation;
use App\Models\Realisation;
use App\Support\Seo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Recherche dans tout le site (logiciels, matériel, services, réalisations, actualités) : page de résultats et
 * suggestions en direct (JSON) pour la recherche de l'en-tête. Contenus publiés seulement.
 */
class SearchController extends Controller
{
    public function index(Request $request): View
    {
        $term = $this->term($request);

        return view('site.search', [
            'term' => $term,
            'groups' => $term === '' ? collect() : $this->search($term, 12),
            'seo' => Seo::make($term === '' ? 'Recherche' : 'Recherche : '.$term)->noIndex(),
        ]);
    }

    public function suggest(Request $request): JsonResponse
    {
        $term = $this->term($request);
        if (mb_strlen($term) < 2) {
            return response()->json(['groups' => []]);
        }

        return response()->json([
            'groups' => $this->search($term, 4)->map(fn ($group) => ['label' => $group['label'], 'items' => $group['items']])->values(),
            'all' => route('site.search', ['q' => $term]),
        ]);
    }

    private function term(Request $request): string
    {
        return mb_substr(trim((string) $request->query('q', '')), 0, 80);
    }

    /**
     * @return Collection<int, array{label: string, icon: string, items: list<array{title: string, text: string, url: string}>}>
     */
    private function search(string $term, int $limit): Collection
    {
        $like = '%'.addcslashes($term, '%_\\').'%';
        $item = fn (string $title, ?string $text, string $url) => ['title' => $title, 'text' => (string) str($text)->limit(110), 'url' => $url];

        return collect([
            ['Logiciels', 'bx-code-block', Logiciel::inCatalogue()->where(fn ($q) => $q->where('name', 'like', $like)->orWhere('summary', 'like', $like)->orWhere('features', 'like', $like))
                ->ordered()->limit($limit)->get()->map(fn ($l) => $item($l->name, $l->summary, route('site.logiciels.show', $l->slug)))],
            ['Matériel', 'bx-laptop', Materiel::published()->where(fn ($q) => $q->where('designation', 'like', $like)->orWhere('brand', 'like', $like)->orWhere('model', 'like', $like)->orWhere('specs', 'like', $like))
                ->ordered()->limit($limit)->get()->map(fn ($m) => $item($m->designation, $m->priceForDisplay() ?? $m->brandModel(), route('site.materiels.show', $m->slug)))],
            ['Services', 'bx-wrench', Prestation::published()->where(fn ($q) => $q->where('title', 'like', $like)->orWhere('summary', 'like', $like))
                ->ordered()->limit($limit)->get()->map(fn ($p) => $item($p->title, $p->summary, route('site.services').'#'.$p->slug))],
            ['Réalisations', 'bx-briefcase-alt-2', Realisation::published()->where(fn ($q) => $q->where('title', 'like', $like)->orWhere('summary', 'like', $like)->orWhere('technologies', 'like', $like))
                ->ordered()->limit($limit)->get()->map(fn ($r) => $item($r->title, $r->summary, route('site.realisations.show', $r->slug)))],
            ['Actualités', 'bx-news', Article::published()->where(fn ($q) => $q->where('title', 'like', $like)->orWhere('excerpt', 'like', $like)->orWhere('body', 'like', $like))
                ->latestFirst()->limit($limit)->get()->map(fn ($a) => $item($a->title, $a->excerptText(110), route('site.actualites.show', $a->slug)))],
        ])->map(fn ($g) => ['label' => $g[0], 'icon' => $g[1], 'items' => $g[2]->all()])->filter(fn ($g) => $g['items'] !== [])->values();
    }
}
