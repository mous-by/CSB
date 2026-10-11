@extends('layouts.site')

@section('content')
    @include('site.partials.page-hero', ['title' => 'Mon panier', 'crumb' => 'Panier', 'intro' => 'Vérifiez vos articles, puis finalisez votre commande : nous vous appelons pour confirmer la disponibilité avant tout paiement.'])

    <section class="section">
        <div class="container">
            @if (session('status'))<div class="alert alert-success" role="status">{{ session('status') }}</div>@endif
            @if (session('error'))<div class="alert alert-warning" role="alert">{{ session('error') }}</div>@endif

            @if ($lines->isEmpty())
                <div class="detail-panel text-center py-5">
                    <i class='bx bx-cart display-4 text-muted' aria-hidden="true"></i>
                    <h2 class="h4 mt-3">Votre panier est vide</h2>
                    <p class="text-muted">Parcourez notre catalogue de matériel informatique : ordinateurs, imprimantes, accessoires…</p>
                    <a href="{{ route('site.materiels.index') }}" class="btn btn-accent">Voir le matériel</a>
                </div>
            @else
                <div class="row g-4" data-cart-page>
                    <div class="col-lg-8">
                        <div class="detail-panel">
                            @foreach ($lines as $line)
                                @php
                                    $materiel = $line['materiel'];
                                    $visual = $materiel->images()->first();
                                @endphp
                                <div class="cart-line" data-cart-line="{{ $materiel->id }}">
                                    <a href="{{ route('site.materiels.show', $materiel->slug) }}" class="cart-thumb">
                                        @if ($visual)<img src="{{ $visual->thumbUrl() }}" alt="" width="96" height="72" loading="lazy">@else<i class='bx bx-laptop' aria-hidden="true"></i>@endif
                                    </a>
                                    <div class="cart-info">
                                        <a href="{{ route('site.materiels.show', $materiel->slug) }}" class="fw-semibold">{{ $materiel->designation }}</a>
                                        <div class="small">@include('site.materiels._price', ['materiel' => $materiel])</div>
                                    </div>
                                    <form method="POST" action="{{ route('site.panier.update', $materiel) }}" class="cart-qty" data-cart-qty>
                                        @csrf
                                        @method('PATCH')
                                        <button type="button" class="qty-btn" data-qty-step="-1" aria-label="Diminuer la quantité">−</button>
                                        <label class="visually-hidden" for="qty-{{ $materiel->id }}">Quantité</label>
                                        <input type="number" id="qty-{{ $materiel->id }}" name="quantity" value="{{ $line['quantity'] }}" min="1" max="{{ \App\Services\Boutique\CartService::maxFor($materiel) }}" inputmode="numeric">
                                        <button type="button" class="qty-btn" data-qty-step="1" aria-label="Augmenter la quantité">+</button>
                                        <noscript><button type="submit" class="btn btn-sm btn-outline-primary">OK</button></noscript>
                                    </form>
                                    <strong class="cart-line-total" data-line-total>{{ \App\Models\Materiel::money($line['line_total']) }}</strong>
                                    <form method="POST" action="{{ route('site.panier.remove', $materiel) }}" data-cart-remove>
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="cart-remove" aria-label="Retirer {{ $materiel->designation }}"><i class='bx bx-trash' aria-hidden="true"></i></button>
                                    </form>
                                </div>
                            @endforeach
                        </div>
                        <a href="{{ route('site.materiels.index') }}" class="btn btn-link px-0"><i class='bx bx-left-arrow-alt' aria-hidden="true"></i> Continuer mes achats</a>
                    </div>
                    <div class="col-lg-4">
                        <div class="detail-panel cart-summary">
                            <h2 class="h5">Récapitulatif</h2>
                            <div class="d-flex justify-content-between align-items-baseline my-3">
                                <span>Total</span>
                                <strong class="h4 mb-0 text-blue" data-cart-total>{{ \App\Models\Materiel::money($total) }}</strong>
                            </div>
                            <p class="small text-muted">Livraison éventuelle en sus, confirmée par téléphone. Aucun paiement en ligne : vous réglez après notre appel de confirmation.</p>
                            <a href="{{ route('site.commande') }}" class="btn btn-accent btn-lg w-100">Commander <i class='bx bx-right-arrow-alt' aria-hidden="true"></i></a>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </section>
@endsection
