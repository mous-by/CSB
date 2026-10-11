{{-- Carte d'un matériel (catalogue, accueil, produits liés). --}}
@php
    $visual = $materiel->images()->first();
@endphp
<article class="csb-card" data-tilt>
    <div class="csb-card-media contain">
        @if ($materiel->isOnPromo())
            <span class="promo-ribbon">Promo -{{ $materiel->promoPercent() }} %</span>
        @endif
        @if ($visual)
            <img src="{{ $visual->thumbUrl() }}" alt="{{ $visual->altText() }}" loading="lazy" width="480" height="300">
        @else
            <i class='bx bx-laptop placeholder-icon' aria-hidden="true"></i>
        @endif
    </div>
    <div class="csb-card-body stretched">
        <div class="d-flex flex-wrap gap-2 mb-2">
            <span class="tag {{ $materiel->condition->tag() }}">{{ $materiel->condition->label() }}</span>
            <span class="tag {{ $materiel->availability->tag() }}">{{ $materiel->availability->label() }}</span>
        </div>
        <h3><a href="{{ route('site.materiels.show', $materiel->slug) }}">{{ $materiel->designation }}</a></h3>
        @if ($materiel->brandModel())
            <p class="mb-2 small">{{ $materiel->brandModel() }}</p>
        @endif
        <div class="csb-card-footer">
            @include('site.materiels._price', ['materiel' => $materiel])
            @if (Route::has('site.panier.add') && \App\Services\Boutique\CartService::purchasable($materiel))
                <form method="POST" action="{{ route('site.panier.add', $materiel) }}" class="card-cart-form" data-cart-add>
                    @csrf
                    <button type="submit" class="btn-cart-mini" aria-label="Ajouter « {{ $materiel->designation }} » au panier" title="Ajouter au panier"><i class='bx bx-cart-add' aria-hidden="true"></i></button>
                </form>
            @else
                <span class="card-link-more">Voir <i class='bx bx-right-arrow-alt' aria-hidden="true"></i></span>
            @endif
        </div>
    </div>
</article>
