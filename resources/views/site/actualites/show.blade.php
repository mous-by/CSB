@extends('layouts.site')

@section('content')
    @if ($preview)
        <div class="bg-warning-subtle border-bottom py-2 text-center small" role="status">
            <i class='bx bx-show' aria-hidden="true"></i> <strong>Aperçu</strong> — {{ $article->isVisible() ? 'article publié' : ($article->isScheduled() ? 'publication programmée le '.$article->published_at->format('d/m/Y à H:i') : 'brouillon, invisible pour les visiteurs') }}.
        </div>
    @endif
    @include('site.partials.page-hero', [
        'title' => $article->title,
        'crumbs' => array_values(array_filter([['Actualités', route('site.actualites.index')], $article->category?->is_active ? [$article->category->name, route('site.actualites.index', ['categorie' => $article->category->slug])] : null])),
        'crumb' => \Illuminate\Support\Str::limit($article->title, 40),
    ])

    <section class="section">
        <div class="container">
            <div class="row justify-content-center">
                <article class="col-lg-8">
                    <div class="d-flex flex-wrap align-items-center gap-3 small text-muted mb-4">
                        @if ($article->category)
                            <span class="tag">{{ $article->category->name }}</span>
                        @endif
                        @if ($article->published_at)
                            <span><i class='bx bx-calendar' aria-hidden="true"></i> <time datetime="{{ $article->published_at->toIso8601String() }}">{{ $article->published_at->translatedFormat('d F Y') }}</time></span>
                        @endif
                        <span><i class='bx bx-time-five' aria-hidden="true"></i> {{ $article->readingMinutes() }} min de lecture</span>
                    </div>
                    @if ($article->cover)
                        <img src="{{ $article->cover->url() }}" alt="{{ $article->cover->altText() }}" class="rounded-4 mb-4 w-100" width="{{ $article->cover->width }}" height="{{ $article->cover->height }}">
                    @endif
                    @if ($article->excerpt)
                        <p class="lead-muted fs-5">{{ $article->excerpt }}</p>
                    @endif
                    <div class="prose">{!! $bodyHtml !!}</div>

                    <div class="detail-panel mt-5 d-flex flex-wrap align-items-center justify-content-between gap-3">
                        <div>
                            <strong class="d-block text-dark">Cet article vous a été utile ?</strong>
                            <span class="small text-muted">Partagez-le ou posez-nous vos questions.</span>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            <a href="{{ $site->whatsappShareLink($article->title.' — '.route('site.actualites.show', $article->slug)) }}" class="btn btn-whatsapp btn-sm" target="_blank" rel="noopener"><i class='bx bxl-whatsapp' aria-hidden="true"></i> Partager</a>
                            <button type="button" class="btn btn-outline-primary btn-sm" data-copy-link="{{ route('site.actualites.show', $article->slug) }}"><i class='bx bx-link' aria-hidden="true"></i> Copier le lien</button>
                        </div>
                    </div>
                </article>
            </div>
        </div>
    </section>

    @if ($related->isNotEmpty())
        <section class="section section-alt">
            <div class="container">
                <div class="section-title"><span class="eyebrow">À lire aussi</span><h2>Dans la même rubrique</h2></div>
                <div class="row g-4">
                    @foreach ($related as $item)
                        <div class="col-md-6 col-lg-4">@include('site.actualites._card', ['article' => $item])</div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @include('site.partials.cta')
@endsection
