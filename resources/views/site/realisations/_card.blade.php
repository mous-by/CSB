@php
    $visual = $realisation->images()->first();
@endphp
<article class="csb-card" data-tilt>
    <div class="csb-card-media">
        @if ($visual)
            <img src="{{ $visual->thumbUrl() }}" alt="{{ $visual->altText() }}" loading="lazy" width="480" height="300">
        @else
            <i class='bx bx-briefcase-alt-2 placeholder-icon' aria-hidden="true"></i>
        @endif
    </div>
    <div class="csb-card-body stretched">
        <div class="d-flex flex-wrap gap-2 mb-2">
            @if ($realisation->sector)
                <span class="tag">{{ $realisation->sector }}</span>
            @endif
            @if ($realisation->year)
                <span class="tag tag-muted">{{ $realisation->year }}</span>
            @endif
        </div>
        <h3><a href="{{ route('site.realisations.show', $realisation->slug) }}">{{ $realisation->title }}</a></h3>
        <p>{{ $realisation->summary }}</p>
        <div class="csb-card-footer">
            <span class="small text-muted">{{ $realisation->client_name }}</span>
            <span class="card-link-more">Voir le projet <i class='bx bx-right-arrow-alt' aria-hidden="true"></i></span>
        </div>
    </div>
</article>
