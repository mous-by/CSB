@extends('layouts.site')

@section('content')
    @include('site.partials.page-hero', [
        'title' => $category ? $category->name : 'Actualités et conseils',
        'crumb' => $category?->name,
        'crumbs' => $category ? [['Actualités', route('site.actualites.index')]] : [],
        'intro' => $category?->description ?: 'Nos annonces, nos nouveautés et nos conseils pour tirer le meilleur de votre informatique.',
    ])

    <section class="section">
        <div class="container">
            <form method="GET" action="{{ route('site.actualites.index') }}" class="filter-bar" role="search" aria-label="Rechercher un article" data-ajax-filter="#actualites-results">
                <div class="row g-3 align-items-end">
                    <div class="col-md-9">
                        <label for="q" class="form-label">Rechercher un article</label>
                        <input type="search" class="form-control" id="q" name="q" value="{{ $search }}" placeholder="Mot-clé…">
                        <input type="hidden" name="categorie" value="{{ $category?->slug }}">
                    </div>
                    <div class="col-md-3 d-grid">
                        <button type="submit" class="btn btn-primary"><i class='bx bx-search' aria-hidden="true"></i> Rechercher</button>
                    </div>
                </div>
            </form>

            <div id="actualites-results" data-ajax-results>
                @if ($categories->count() > 1)
                    <nav class="filter-chips mb-4" aria-label="Catégories">
                        <a href="{{ route('site.actualites.index', array_filter(['q' => $search])) }}" class="filter-chip {{ $category ? '' : 'active' }}" data-ajax-link>Tout</a>
                        @foreach ($categories as $item)
                            <a href="{{ route('site.actualites.index', array_filter(['categorie' => $item->slug, 'q' => $search])) }}" class="filter-chip {{ $category?->id === $item->id ? 'active' : '' }}" data-ajax-link>{{ $item->name }}</a>
                        @endforeach
                    </nav>
                @endif
                <p class="results-count mb-3" data-results-count>{{ $articles->total() }} article{{ $articles->total() > 1 ? 's' : '' }}{{ $category ? ' — '.$category->name : '' }}</p>
                @if ($articles->isEmpty())
                    <div class="empty-state">
                        <i class='bx bx-news' aria-hidden="true"></i>
                        <h2 class="h5 mt-3">{{ $filtered ? 'Aucun article ne correspond' : 'Nos premiers articles arrivent bientôt' }}</h2>
                        @if ($filtered)
                            <a href="{{ route('site.actualites.index') }}" class="btn btn-outline-primary mt-2" data-ajax-link>Voir tous les articles</a>
                        @endif
                    </div>
                @else
                    <div class="row g-4">
                        @foreach ($articles as $article)
                            <div class="col-md-6 col-lg-4">@include('site.actualites._card', ['article' => $article])</div>
                        @endforeach
                    </div>
                    <div class="mt-5 d-flex justify-content-center">{{ $articles->onEachSide(1)->links() }}</div>
                @endif
            </div>
        </div>
    </section>
@endsection
