<?php

namespace App\Http\Controllers\Site;

use App\Enums\CategoryType;
use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Category;
use App\Models\Logiciel;
use App\Models\Materiel;
use App\Models\Prestation;
use App\Models\Realisation;
use App\Models\Temoignage;
use App\Support\Markdown;
use App\Support\Seo;
use App\Support\SiteSettings;
use Illuminate\View\View;

/**
 * Pages institutionnelles du site public (cahier §4) : accueil, à propos, pages légales. Leur contenu se règle dans
 * l'administration (Pages et paramètres, App\Support\SiteSettings).
 */
class PageController extends Controller
{
    public function __construct(private readonly SiteSettings $settings) {}

    /**
     * Accueil (cahier §4) : chaque section n'apparaît que si elle a du contenu publié.
     */
    public function home(): View
    {
        $sections = [];

        $temoignages = Temoignage::published()->ordered()->limit(6)->get();
        if ($temoignages->isNotEmpty()) {
            $sections[] = ['view' => 'site.home._temoignages', 'data' => ['temoignages' => $temoignages]];
        }

        $articles = Article::with(['category', 'cover'])->published()->latestFirst()->limit(3)->get();
        if ($articles->isNotEmpty()) {
            $sections[] = ['view' => 'site.home._actualites', 'data' => ['articles' => $articles]];
        }

        // Matériel : les catégories qui ont des produits publiés, avec leur image (cartes de la maquette).
        $categories = Category::ofType(CategoryType::Materiel)->active()->with('image')->ordered()
            ->withCount(['materiels' => fn ($q) => $q->where('is_published', true)])
            ->withMax(['materiels as last_published_at' => fn ($q) => $q->where('is_published', true)], 'published_at')
            ->get()->filter(fn ($c) => $c->materiels_count > 0)->take(12)
            ->each(fn ($c) => $c->has_new = $c->last_published_at && now()->subDays(30)->lt($c->last_published_at));

        return view('site.pages.home', [
            'logiciels' => Logiciel::with(['category', 'logo', 'gallery'])->inCatalogue()->ordered()->limit(12)->get(),
            'categories' => $categories->values(),
            'realisations' => Realisation::with(['cover', 'gallery'])->published()->ordered()->limit(10)->get(),
            // Bande défilante du matériel : promotions d'abord, puis produits mis en avant.
            'materiels' => Materiel::with(['category', 'mainImage', 'gallery'])->published()
                ->orderByRaw('CASE WHEN promo_price IS NOT NULL AND price IS NOT NULL AND promo_price < price AND (promo_ends_on IS NULL OR promo_ends_on >= ?) THEN 0 ELSE 1 END', [today()->toDateString()])
                ->ordered()->limit(12)->get(),
            'sections' => $sections,
            'seo' => Seo::make(canonical: route('site.home')),
        ]);
    }

    /** Services (cahier §4 : vente, réparation, maintenance, développement, réseaux, accompagnement). */
    public function services(): View
    {
        return view('site.pages.services', [
            'prestations' => Prestation::published()->ordered()->get(),
            'seo' => Seo::make('Nos services', 'Vente de matériel, réparation et maintenance, développement de logiciels, réseaux et accompagnement informatique : les services de '.$this->settings->get('company.name').'.'),
        ]);
    }

    public function about(): View
    {
        return view('site.pages.about', [
            'seo' => Seo::make('À propos', $this->settings->get('about.presentation') ? Markdown::toText($this->settings->get('about.presentation')) : null),
        ]);
    }

    public function legal(): View
    {
        return view('site.pages.legal', [
            'title' => 'Mentions légales',
            'content' => $this->settings->html('legal.mentions'),
            'seo' => Seo::make('Mentions légales', 'Mentions légales du site de '.$this->settings->get('company.name').'.'),
        ]);
    }

    public function privacy(): View
    {
        return view('site.pages.legal', [
            'title' => 'Politique de confidentialité',
            'content' => $this->settings->html('legal.privacy'),
            'seo' => Seo::make('Politique de confidentialité', 'Utilisation des données personnelles collectées par les formulaires du site de '.$this->settings->get('company.name').'.'),
        ]);
    }
}
