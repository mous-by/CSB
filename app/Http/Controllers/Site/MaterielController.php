<?php

namespace App\Http\Controllers\Site;

use App\Enums\Availability;
use App\Enums\CategoryType;
use App\Enums\MaterielCondition;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Materiel;
use App\Support\Markdown;
use App\Support\Seo;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Matériel informatique (cahier §4, §5.3, §5.4) : catalogue filtrable (catégorie, état, disponibilité, recherche) et
 * fiche (photos, marque / modèle, caractéristiques, état, prix indicatif si renseigné, disponibilité à confirmer).
 */
class MaterielController extends Controller
{
    public function index(Request $request): View
    {
        $categories = Category::ofType(CategoryType::Materiel)->active()->ordered()->get();
        $category = $categories->firstWhere('slug', (string) $request->query('categorie'));
        $condition = MaterielCondition::tryFrom((string) $request->query('etat'));
        $availability = Availability::tryFrom((string) $request->query('disponibilite'));
        $search = trim((string) $request->query('q', ''));

        $materiels = Materiel::with(['category', 'mainImage', 'gallery'])->published()
            ->when($category, fn ($q) => $q->where('category_id', $category->id))
            ->when($condition, fn ($q) => $q->where('condition', $condition->value))
            ->when($availability, fn ($q) => $q->where('availability', $availability->value))
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q->where('designation', 'like', "%{$search}%")->orWhere('brand', 'like', "%{$search}%")->orWhere('model', 'like', "%{$search}%")->orWhere('specs', 'like', "%{$search}%")))
            ->ordered()->paginate(12)->withQueryString();

        $filtered = $category || $condition || $availability || $search !== '';
        $seo = Seo::make(
            $category ? "Matériel — {$category->name}" : 'Matériel informatique',
            $category?->description ?: 'Ordinateurs, imprimantes, consommables, accessoires et équipements réseau, neufs ou d\'occasion : le matériel informatique proposé par CSB.',
            canonical: $category ? route('site.materiels.index', ['categorie' => $category->slug]) : route('site.materiels.index'),
        );

        return view('site.materiels.index', [
            'materiels' => $materiels,
            'categories' => $categories,
            'category' => $category,
            'condition' => $condition,
            'availability' => $availability,
            'search' => $search,
            'filtered' => $filtered,
            'seo' => ($condition || $availability || $search !== '' || $materiels->currentPage() > 1) ? $seo->noIndex() : $seo,
        ]);
    }

    public function show(Materiel $materiel): View
    {
        abort_unless($materiel->is_published, 404);
        $materiel->load(['category', 'mainImage', 'gallery', 'seoImage']);

        $image = $materiel->shareImage();
        $product = array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $materiel->designation,
            'description' => $materiel->summary ?: Markdown::toText($materiel->description),
            'brand' => $materiel->brand ? ['@type' => 'Brand', 'name' => $materiel->brand] : null,
            'model' => $materiel->model,
            'category' => $materiel->category?->name,
            'image' => $image?->url(),
            'url' => route('site.materiels.show', $materiel->slug),
            // Prix seulement s'il est renseigné (validé par CSB) : jamais de prix inventé (cahier §11.1).
            'offers' => $materiel->price !== null ? [
                '@type' => 'Offer', 'price' => (string) $materiel->currentPrice(), 'priceCurrency' => 'XOF',
                'priceValidUntil' => $materiel->isOnPromo() ? $materiel->promo_ends_on?->toDateString() : null,
                'availability' => $materiel->availability->schema(), 'itemCondition' => $materiel->condition->schema(),
            ] : null,
        ]);

        return view('site.materiels.show', [
            'materiel' => $materiel,
            'descriptionHtml' => Markdown::toHtml($materiel->description),
            'related' => Materiel::with(['mainImage', 'gallery', 'category'])->published()->where('category_id', $materiel->category_id)->whereKeyNot($materiel->id)->ordered()->limit(3)->get(),
            'seo' => Seo::make($materiel->seoTitle(), $materiel->seo_description ?: ($materiel->summary ?: $materiel->designation.' — '.$materiel->condition->label().', '.$materiel->availability->label().'.'), $image?->url(), route('site.materiels.show', $materiel->slug), 'product', jsonLd: [$product]),
        ]);
    }
}
