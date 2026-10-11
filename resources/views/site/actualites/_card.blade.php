<article class="csb-card" data-tilt>
    <div class="csb-card-media">
        @if ($article->cover)
            <img src="{{ $article->cover->thumbUrl() }}" alt="{{ $article->cover->altText() }}" loading="lazy" width="480" height="300">
        @else
            <i class='bx bx-news placeholder-icon' aria-hidden="true"></i>
        @endif
    </div>
    <div class="csb-card-body stretched">
        <div class="d-flex flex-wrap align-items-center gap-2 mb-2 small text-muted">
            @if ($article->category)
                <span class="tag">{{ $article->category->name }}</span>
            @endif
            <time datetime="{{ $article->published_at?->toDateString() }}">{{ $article->published_at?->translatedFormat('d F Y') }}</time>
        </div>
        <h3><a href="{{ route('site.actualites.show', $article->slug) }}">{{ $article->title }}</a></h3>
        <p>{{ $article->excerptText(160) }}</p>
        <div class="csb-card-footer">
            <span class="small text-muted"><i class='bx bx-time-five' aria-hidden="true"></i> {{ $article->readingMinutes() }} min</span>
            <span class="card-link-more">Lire <i class='bx bx-right-arrow-alt' aria-hidden="true"></i></span>
        </div>
    </div>
</article>
