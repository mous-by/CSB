<?php

namespace App\Http\Controllers\Web\Admin;

use App\Enums\CategoryType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Admin\ArticleRequest;
use App\Models\Article;
use App\Models\Category;
use App\Services\Contenus\ArticleService;
use App\Services\Contenus\ContentPublisher;
use App\Support\Markdown;
use App\Support\Seo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Actualités et conseils (cahier §4, §7) : liste, rédaction sur une page dédiée, aperçu avant publication,
 * publication / dépublication, suppression en soft delete.
 */
class ArticleController extends Controller
{
    public function __construct(private readonly ArticleService $service) {}

    public function index(): View
    {
        Gate::authorize('viewAny', Article::class);

        return view('articles.index', ['articles' => Article::with(['category', 'cover', 'author'])->orderByRaw('published_at IS NULL DESC')->latestFirst()->get()]);
    }

    public function create(): View
    {
        Gate::authorize('create', Article::class);

        return view('articles.form', $this->formData(null));
    }

    public function store(ArticleRequest $request): RedirectResponse
    {
        $article = $this->service->save(null, $request->validated(), $request->user(), $request->file('cover_upload'));

        return redirect()->route('articles.index')->with('status', "Article « {$article->title} » enregistré".($article->isVisible() ? ' et publié.' : ($article->isScheduled() ? ', programmé le '.$article->published_at->format('d/m/Y à H:i').'.' : ' (brouillon).')));
    }

    public function edit(Article $article): View
    {
        Gate::authorize('update', $article);

        return view('articles.form', $this->formData($article->load(['cover', 'seoImage'])));
    }

    public function update(ArticleRequest $request, Article $article): RedirectResponse
    {
        $article = $this->service->save($article, $request->validated(), $request->user(), $request->file('cover_upload'));

        return redirect()->route('articles.index')->with('status', "Article « {$article->title} » enregistré.");
    }

    /** Aperçu de l'article tel qu'il paraîtra sur le site, même non publié (non indexé). */
    public function preview(Article $article): View
    {
        Gate::authorize('view', $article);
        $article->load(['category', 'cover', 'author']);

        return view('site.actualites.show', [
            'article' => $article,
            'bodyHtml' => Markdown::toHtml($article->body),
            'related' => collect(),
            'preview' => true,
            'seo' => Seo::make('Aperçu — '.$article->title, $article->excerptText(160))->noIndex(),
        ]);
    }

    /** Rendu du texte en cours de rédaction (onglet « Aperçu » du formulaire, AJAX). */
    public function markdown(Request $request): JsonResponse
    {
        $data = $request->validate(['body' => ['nullable', 'string', 'max:100000']]);

        return response()->json(['html' => Markdown::toHtml($data['body'] ?? '')]);
    }

    public function publication(Article $article, ContentPublisher $publisher): RedirectResponse
    {
        Gate::authorize('publish', $article);

        $publisher->toggle($article, 'articles');

        return redirect()->route('articles.index')->with('status', "Article « {$article->title} » ".($article->is_published ? 'publié.' : 'dépublié.'));
    }

    public function destroy(Article $article): RedirectResponse
    {
        Gate::authorize('delete', $article);

        $article->delete();

        return redirect()->route('articles.index')->with('status', "Article « {$article->title} » supprimé.");
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(?Article $article): array
    {
        return [
            'article' => $article,
            'categories' => Category::ofType(CategoryType::Article)
                ->where(fn ($q) => $q->where('is_active', true)->when($article, fn ($q) => $q->orWhere('id', $article->category_id)))
                ->ordered()->get(),
        ];
    }
}
