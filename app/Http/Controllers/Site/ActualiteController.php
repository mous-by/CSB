<?php

namespace App\Http\Controllers\Site;

use App\Enums\CategoryType;
use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Category;
use App\Support\Markdown;
use App\Support\Seo;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Actualités et conseils (cahier §4) : articles publiés (date atteinte), filtrables par catégorie, avec recherche.
 */
class ActualiteController extends Controller
{
    public function index(Request $request): View
    {
        $categories = Category::ofType(CategoryType::Article)->active()->ordered()->get();
        $category = $categories->firstWhere('slug', (string) $request->query('categorie'));
        $search = trim((string) $request->query('q', ''));

        $articles = Article::with(['category', 'cover'])->published()
            ->when($category, fn ($q) => $q->where('category_id', $category->id))
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q->where('title', 'like', "%{$search}%")->orWhere('excerpt', 'like', "%{$search}%")->orWhere('body', 'like', "%{$search}%")))
            ->latestFirst()->paginate(9)->withQueryString();

        $seo = Seo::make(
            $category ? "Actualités — {$category->name}" : 'Actualités et conseils',
            $category?->description ?: 'Actualités, annonces et conseils informatiques de CSB.',
            canonical: $category ? route('site.actualites.index', ['categorie' => $category->slug]) : route('site.actualites.index'),
        );

        return view('site.actualites.index', [
            'articles' => $articles,
            'categories' => $categories,
            'category' => $category,
            'search' => $search,
            'filtered' => $category || $search !== '',
            'seo' => ($search !== '' || $articles->currentPage() > 1) ? $seo->noIndex() : $seo,
        ]);
    }

    public function show(Article $article): View
    {
        abort_unless($article->isVisible(), 404);
        $article->load(['category', 'cover', 'seoImage', 'author']);
        $image = $article->shareImage();

        $jsonLd = [array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $article->title,
            'description' => $article->excerptText(160),
            'image' => $image?->url(),
            'datePublished' => $article->published_at->toIso8601String(),
            'dateModified' => $article->updated_at->toIso8601String(),
            'mainEntityOfPage' => route('site.actualites.show', $article->slug),
        ])];

        return view('site.actualites.show', [
            'article' => $article,
            'bodyHtml' => Markdown::toHtml($article->body),
            'related' => Article::with(['category', 'cover'])->published()->whereKeyNot($article->id)->where('category_id', $article->category_id)->latestFirst()->limit(3)->get(),
            'preview' => false,
            'seo' => Seo::make($article->seo_title ?: $article->title, $article->seo_description ?: $article->excerptText(160), $image?->url(), route('site.actualites.show', $article->slug), 'article', jsonLd: $jsonLd),
        ]);
    }
}
