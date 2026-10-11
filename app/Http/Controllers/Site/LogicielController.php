<?php

namespace App\Http\Controllers\Site;

use App\Enums\CategoryType;
use App\Enums\LogicielStatus;
use App\Enums\Platform;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Logiciel;
use App\Support\Markdown;
use App\Support\Seo;
use App\Support\SiteSettings;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Solutions logicielles (cahier §4) : catalogue filtrable par secteur (catégorie) et plateforme, avec recherche, puis
 * fiche détaillée (résumé, problème traité, fonctionnalités, captures, plateformes, statut, démonstration / devis).
 */
class LogicielController extends Controller
{
    public function index(Request $request): View
    {
        $categories = Category::ofType(CategoryType::Logiciel)->active()->ordered()->get();
        $category = $categories->firstWhere('slug', (string) $request->query('categorie'));
        $platform = Platform::tryFrom((string) $request->query('plateforme'));
        $search = trim((string) $request->query('q', ''));

        $logiciels = Logiciel::with(['category', 'logo', 'gallery'])->inCatalogue()
            ->when($category, fn ($q) => $q->where(fn ($q) => $q->where('category_id', $category->id)->orWhereHas('secondaryCategories', fn ($q) => $q->whereKey($category->id))))
            ->when($platform, fn ($q) => $q->where('platforms', 'like', '%"'.$platform->value.'"%'))
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('summary', 'like', "%{$search}%")->orWhere('features', 'like', "%{$search}%")))
            ->ordered()->paginate(12)->withQueryString();

        $filtered = $category || $platform || $search !== '';
        $seo = Seo::make(
            $category ? "Logiciels — {$category->name}" : 'Solutions logicielles',
            $category?->description ?: 'Logiciels de gestion, solutions métiers et applications web et mobiles conçus par CSB : découvrez nos solutions et demandez une démonstration.',
            canonical: $category ? route('site.logiciels.index', ['categorie' => $category->slug]) : route('site.logiciels.index'),
        );

        return view('site.logiciels.index', [
            'logiciels' => $logiciels,
            'categories' => $categories,
            'category' => $category,
            'platform' => $platform,
            'search' => $search,
            'filtered' => $filtered,
            'seo' => ($platform || $search !== '' || $logiciels->currentPage() > 1) ? $seo->noIndex() : $seo,
        ]);
    }

    public function show(Logiciel $logiciel): View
    {
        abort_unless($logiciel->is_published, 404);
        $logiciel->load(['category', 'secondaryCategories', 'logo', 'gallery', 'seoImage']);

        $image = $logiciel->shareImage();
        $jsonLd = [array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'SoftwareApplication',
            'name' => $logiciel->name,
            'description' => $logiciel->summary,
            'applicationCategory' => $logiciel->category?->name,
            'operatingSystem' => collect($logiciel->platformList())->map->label()->implode(', ') ?: null,
            'image' => $image?->url(),
            'url' => route('site.logiciels.show', $logiciel->slug),
            'publisher' => ['@type' => 'Organization', 'name' => app(SiteSettings::class)->get('company.name')],
        ])];

        return view('site.logiciels.show', [
            'logiciel' => $logiciel,
            'descriptionHtml' => Markdown::toHtml($logiciel->description),
            'related' => Logiciel::with(['logo', 'gallery', 'category'])->inCatalogue()->where('category_id', $logiciel->category_id)->whereKeyNot($logiciel->id)->ordered()->limit(3)->get(),
            'seo' => Seo::make($logiciel->seoTitle(), $logiciel->seo_description ?: $logiciel->summary, $image?->url(), route('site.logiciels.show', $logiciel->slug), 'product', jsonLd: $jsonLd),
            'archived' => $logiciel->status === LogicielStatus::Archive,
        ]);
    }
}
