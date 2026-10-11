{{-- Carte d'un logiciel (catalogue, accueil, logiciels liés). --}}
@php
    $visual = $logiciel->gallery->first() ?? $logiciel->logo;
@endphp
<article class="csb-card" data-tilt>
    <div class="csb-card-media {{ $logiciel->gallery->isEmpty() ? 'contain' : '' }}">
        @if ($visual)
            <img src="{{ $visual->thumbUrl() }}" alt="{{ $visual->altText() }}" loading="lazy" width="480" height="300">
        @else
            <i class='bx bx-code-block placeholder-icon' aria-hidden="true"></i>
        @endif
    </div>
    <div class="csb-card-body stretched">
        <div class="d-flex flex-wrap gap-2 mb-2">
            <span class="tag {{ $logiciel->status->tag() }}">{{ $logiciel->status->label() }}</span>
            @if ($logiciel->category)
                <span class="tag">{{ $logiciel->category->name }}</span>
            @endif
        </div>
        <h3><a href="{{ route('site.logiciels.show', $logiciel->slug) }}">{{ $logiciel->name }}</a></h3>
        <p>{{ $logiciel->summary }}</p>
        <div class="csb-card-footer">
            <span class="text-muted small d-flex gap-2" aria-label="Plateformes">
                @foreach ($logiciel->platformList() as $platform)
                    <i class='bx {{ $platform->icon() }} fs-5' title="{{ $platform->label() }}" aria-hidden="true"></i><span class="visually-hidden">{{ $platform->label() }}</span>
                @endforeach
            </span>
            <span class="card-link-more">Découvrir <i class='bx bx-right-arrow-alt' aria-hidden="true"></i></span>
        </div>
    </div>
</article>
