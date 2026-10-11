{{-- Prix d'un matériel : prix normal barré et prix promotionnel pendant une promotion. $materiel, $large (fiche). --}}
@php
    $large = $large ?? false;
@endphp
@if ($materiel->isOnPromo())
    <div class="price-block {{ $large ? 'price-lg mb-1' : '' }}">
        <del class="price-old" aria-label="Prix normal">{{ $materiel->priceForDisplay() }}</del>
        <strong class="price-promo" aria-label="Prix promotionnel">{{ $materiel->promoPriceForDisplay() }}</strong>
        @if ($large)
            <span class="price-badge">-{{ $materiel->promoPercent() }} %</span>
            @if ($materiel->promo_ends_on)
                <span class="price-until"><i class='bx bx-time-five' aria-hidden="true"></i> Jusqu'au {{ $materiel->promo_ends_on->translatedFormat('j F Y') }}</span>
            @endif
        @endif
    </div>
@elseif ($large)
    <p class="h3 text-blue mb-1">{{ $materiel->priceForDisplay() ?? 'Prix sur demande' }}</p>
@else
    <strong class="text-blue">{{ $materiel->priceForDisplay() ?? 'Prix sur demande' }}</strong>
@endif
