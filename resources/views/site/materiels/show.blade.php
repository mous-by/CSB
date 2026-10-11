@extends('layouts.site')

@section('whatsapp_message', 'Bonjour CSB, je suis intéressé(e) par « '.$materiel->designation.' ». Est-il disponible ?')

@section('content')
    @include('site.partials.page-hero', [
        'title' => $materiel->designation,
        'crumbs' => array_values(array_filter([['Matériel', route('site.materiels.index')], $materiel->category?->is_active ? [$materiel->category->name, route('site.materiels.index', ['categorie' => $materiel->category->slug])] : null])),
        'intro' => $materiel->summary,
    ])

    <section class="section">
        <div class="container">
            <div class="row g-5">
                <div class="col-lg-7">
                    @if ($materiel->images()->isNotEmpty())
                        @include('site.partials.gallery', ['images' => $materiel->images()])
                    @else
                        <div class="gallery-main"><i class='bx bx-laptop placeholder-icon' style="font-size: 6rem; color: #b9c6e2" aria-hidden="true"></i></div>
                    @endif

                    @if ($materiel->specList())
                        <h2 class="h3 mt-5 mb-3">Caractéristiques</h2>
                        <div class="detail-panel">
                            <dl class="spec-list">
                                @foreach ($materiel->specList() as [$label, $value])
                                    <div><dt>{{ $label }}</dt><dd>{{ $value }}</dd></div>
                                @endforeach
                            </dl>
                        </div>
                    @endif

                    @if ($descriptionHtml)
                        <h2 class="h3 mt-5">Description</h2>
                        <div class="prose">{!! $descriptionHtml !!}</div>
                    @endif
                </div>

                <aside class="col-lg-5">
                    <div class="detail-aside">
                        <div class="detail-panel">
                            <div class="d-flex flex-wrap gap-2 mb-3">
                                <span class="tag {{ $materiel->condition->tag() }}">{{ $materiel->condition->label() }}</span>
                                <span class="tag {{ $materiel->availability->tag() }}">{{ $materiel->availability->label() }}</span>
                            </div>
                            @include('site.materiels._price', ['materiel' => $materiel, 'large' => true])
                            @if ($materiel->price !== null && $materiel->price_conditions)
                                <p class="small text-muted">{{ $materiel->price_conditions }}</p>
                            @endif
                            <dl class="spec-list mt-3">
                                @if ($materiel->brand)
                                    <div><dt>Marque</dt><dd>{{ $materiel->brand }}</dd></div>
                                @endif
                                @if ($materiel->model)
                                    <div><dt>Modèle</dt><dd>{{ $materiel->model }}</dd></div>
                                @endif
                                @if ($materiel->category)
                                    <div><dt>Catégorie</dt><dd>{{ $materiel->category->name }}</dd></div>
                                @endif
                            </dl>
                            @if ($materiel->stock_quantity !== null)
                                <p class="small mt-3 mb-0 {{ $materiel->stock_quantity > 0 ? 'text-success' : 'text-danger' }}"><i class='bx {{ $materiel->stock_quantity > 0 ? 'bx-check-circle' : 'bx-x-circle' }}' aria-hidden="true"></i> {{ $materiel->stock_quantity > 0 ? 'En stock : '.$materiel->stock_quantity.' disponible(s)' : 'Rupture de stock' }}{{ $materiel->stock_synced_at ? ' — mis à jour le '.$materiel->stock_synced_at->format('d/m/Y') : '' }}.</p>
                            @else
                                <p class="small text-muted mt-3 mb-0"><i class='bx bx-info-circle' aria-hidden="true"></i> Disponibilité et prix à confirmer auprès de CSB.</p>
                            @endif
                            <div class="d-grid gap-2 mt-4">
                                @if (Route::has('site.panier.add') && \App\Services\Boutique\CartService::purchasable($materiel))
                                    <form method="POST" action="{{ route('site.panier.add', $materiel) }}" class="buy-row" data-cart-add>
                                        @csrf
                                        <label class="visually-hidden" for="buy-qty">Quantité</label>
                                        <input type="number" id="buy-qty" name="quantity" value="1" min="1" max="{{ \App\Services\Boutique\CartService::maxFor($materiel) }}" class="form-control" inputmode="numeric">
                                        <button type="submit" class="btn btn-accent flex-grow-1"><i class='bx bx-cart-add' aria-hidden="true"></i> Ajouter au panier</button>
                                    </form>
                                @endif
                                @if (Route::has('site.disponibilite'))
                                    <a href="{{ route('site.disponibilite', $materiel->slug) }}" class="btn {{ \App\Services\Boutique\CartService::purchasable($materiel) ? 'btn-outline-primary' : 'btn-accent' }}">{{ $materiel->price === null ? 'Demander le prix' : 'Confirmer la disponibilité' }}</a>
                                @endif
                                @if (Route::has('site.devis'))
                                    <a href="{{ route('site.devis', ['type' => 'materiel', 'materiel' => $materiel->slug]) }}" class="btn btn-outline-primary">Demander un devis</a>
                                @endif
                                @if ($site->whatsappLink())
                                    <a href="{{ $site->whatsappLink('Bonjour CSB, je suis intéressé(e) par « '.$materiel->designation.' ». Est-il disponible ?') }}" class="btn btn-whatsapp" target="_blank" rel="noopener"><i class='bx bxl-whatsapp' aria-hidden="true"></i> Demander sur WhatsApp</a>
                                @endif
                            </div>
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </section>

    @if ($related->isNotEmpty())
        <section class="section section-alt">
            <div class="container">
                <div class="section-title">
                    <span class="eyebrow">Dans la même catégorie</span>
                    <h2>Vous aimerez aussi</h2>
                </div>
                <div class="row g-4">
                    @foreach ($related as $item)
                        <div class="col-sm-6 col-lg-4">
                            @include('site.materiels._card', ['materiel' => $item])
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @include('site.partials.cta')
@endsection
