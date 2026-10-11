{{-- En-tête du site (maquette CSB 2026-10-11) : barre de contact (téléphones, e-mail, ville, réseaux, « Nous contacter »),
     logo et menu (« Nos solutions » déroule les catégories et logiciels publiés). Une entrée n'apparaît que si sa page
     existe (Route::has) : les modules s'ajoutent sans modifier ce fichier. $navSolutions : AppServiceProvider. --}}
@php
    $menu = collect([
        ['site.home', 'Accueil', 'site.home'],
        ['site.about', 'À propos', 'site.about'],
        ['site.services', 'Nos services', 'site.services'],
        ['site.logiciels.index', 'Nos solutions', 'site.logiciels.*', 'solutions'],
        ['site.materiels.index', 'Matériel informatique', 'site.materiels.*'],
        ['site.boutique.index', 'Boutique', 'site.boutique.*'],
        ['site.realisations.index', 'Nos réalisations', 'site.realisations.*'],
        ['site.actualites.index', 'Actualités', 'site.actualites.*'],
        ['site.contact', 'Contact', 'site.contact*'],
    ])->filter(fn ($item) => Route::has($item[0]));
    $phones = array_values(array_filter([$site->get('contact.phone_1'), $site->get('contact.phone_2')]));
    $tel = \App\Support\PhoneNumber::normalize($phones[0] ?? null);
    $logoUrl = $site->imageUrl('site.logo', 'assets/site/img/logo-csb.png');
@endphp
<div class="topbar">
    <div class="container">
        <div class="topbar-items">
            @if ($phones)
                <span><i class='bx bxs-phone' aria-hidden="true"></i>@if ($tel)<a href="tel:{{ $tel }}">{{ implode(' / ', $phones) }}</a>@else{{ implode(' / ', $phones) }}@endif</span>
            @endif
            @if ($site->get('contact.email'))
                <span><i class='bx bxs-envelope' aria-hidden="true"></i><a href="mailto:{{ $site->get('contact.email') }}">{{ $site->get('contact.email') }}</a></span>
            @endif
            @if ($site->get('contact.city'))
                <span class="d-none d-lg-inline"><i class='bx bxs-map' aria-hidden="true"></i>{{ $site->get('contact.city') }}</span>
            @endif
        </div>
        <div class="topbar-right">
            <div class="topbar-social">
                @foreach ($site->socialLinks() as $network => $url)
                    <a href="{{ $url }}" target="_blank" rel="noopener" class="social-{{ $network }}" aria-label="{{ ucfirst($network) }}"><i class='bx bxl-{{ $network }}' aria-hidden="true"></i></a>
                @endforeach
                @if ($site->whatsappLink())
                    <a href="{{ $site->whatsappLink('Bonjour CSB,') }}" target="_blank" rel="noopener" class="social-whatsapp" aria-label="WhatsApp"><i class='bx bxl-whatsapp' aria-hidden="true"></i></a>
                @endif
            </div>
            @if (Route::has('site.contact'))
                <a href="{{ route('site.contact') }}" class="topbar-cta">Nous contacter</a>
            @endif
        </div>
    </div>
</div>

<header class="site-header">
    <nav class="navbar navbar-expand-xl" aria-label="Navigation principale">
        <div class="container">
            <a class="brand-logo" href="{{ route('site.home') }}" aria-label="{{ $site->get('company.name') }} — accueil">
                <img src="{{ $logoUrl }}" alt="" width="84" height="64" class="brand-img">
                <span class="brand-text">
                    <strong><span>COMPUTER SERVICE</span> <em>BARRY</em></strong>
                    <small>{{ $site->get('company.slogan') }}</small>
                </span>
            </a>
            <div class="d-flex align-items-center gap-1 d-xl-none">
                @if (Route::has('site.panier'))
                    <a href="{{ route('site.panier') }}" class="nav-icon-btn" aria-label="Panier"><i class='bx bx-cart' aria-hidden="true"></i><span class="cart-count" data-cart-count @if (! ($cartCount ?? 0)) hidden @endif>{{ $cartCount ?? 0 }}</span></a>
                @endif
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Ouvrir le menu">
                    <i class='bx bx-menu' aria-hidden="true"></i>
                </button>
            </div>
            <div class="collapse navbar-collapse" id="mainNav">
                <ul class="navbar-nav ms-auto align-items-xl-center">
                    @foreach ($menu as $item)
                        @php
                            [$route, $label, $pattern] = $item;
                            $active = request()->routeIs($pattern);
                        @endphp
                        @if (($item[3] ?? null) === 'solutions' && ($navSolutions ?? collect())->isNotEmpty())
                            <li class="nav-item dropdown nav-mega">
                                {{-- Bouton et non lien : Bootstrap 5.0 lit le href d'un déclencheur comme un sélecteur (erreur, menu bloqué). --}}
                                <button type="button" class="nav-link dropdown-toggle btn btn-link {{ $active ? 'active' : '' }}" data-bs-toggle="dropdown" aria-expanded="false" @if ($active) aria-current="page" @endif>{{ $label }}</button>
                                <div class="dropdown-menu mega-menu">
                                    <div class="mega-grid">
                                        @foreach ($navSolutions as $solution)
                                            <a class="mega-item" href="{{ route('site.logiciels.show', $solution['slug']) }}">
                                                @if ($solution['logo'])
                                                    <img src="{{ $solution['logo'] }}" alt="" width="40" height="40">
                                                @else
                                                    <span class="mega-icon"><i class='bx bx-code-block' aria-hidden="true"></i></span>
                                                @endif
                                                <span><strong>{{ $solution['name'] }}</strong><small>{{ $solution['subtitle'] }}</small></span>
                                            </a>
                                        @endforeach
                                    </div>
                                    <a class="mega-all" href="{{ route($route) }}">Voir toutes les solutions <i class='bx bx-right-arrow-alt' aria-hidden="true"></i></a>
                                </div>
                            </li>
                        @else
                            <li class="nav-item">
                                <a class="nav-link {{ $active ? 'active' : '' }}" href="{{ route($route) }}" @if ($active) aria-current="page" @endif>{{ $label }}</a>
                            </li>
                        @endif
                    @endforeach
                </ul>
                <div class="nav-tools">
                    <button type="button" class="nav-icon-btn d-none d-xl-inline-flex" data-search-open aria-label="Rechercher sur le site (touche /)" aria-haspopup="dialog"><i class='bx bx-search' aria-hidden="true"></i></button>
                    @if (Route::has('site.panier'))
                        <a href="{{ route('site.panier') }}" class="nav-icon-btn d-none d-xl-inline-flex" aria-label="Panier"><i class='bx bx-cart' aria-hidden="true"></i><span class="cart-count" data-cart-count @if (! ($cartCount ?? 0)) hidden @endif>{{ $cartCount ?? 0 }}</span></a>
                    @endif
                    @if (Route::has('client.home'))
                        <a href="{{ route('client.home') }}" class="nav-icon-btn" aria-label="Mon espace client"><i class='bx bx-user-circle' aria-hidden="true"></i></a>
                    @endif
                    <a href="{{ route('site.search') }}" class="nav-link d-xl-none" data-search-open><i class='bx bx-search' aria-hidden="true"></i> Rechercher</a>
                </div>
            </div>
        </div>
    </nav>
</header>

{{-- Recherche en direct (site.js) : suggestions pendant la saisie, Entrée = page de résultats. --}}
<div class="search-overlay" id="search-overlay" role="dialog" aria-modal="true" aria-label="Rechercher sur le site" hidden data-suggest-url="{{ route('site.search.suggest') }}">
    <div class="search-box">
        <form method="GET" action="{{ route('site.search') }}" role="search">
            <i class='bx bx-search' aria-hidden="true"></i>
            <label for="search-overlay-input" class="visually-hidden">Rechercher</label>
            <input type="search" id="search-overlay-input" name="q" placeholder="Logiciel, matériel, service, article…" autocomplete="off" aria-controls="search-suggestions">
            <button type="button" class="search-close" data-search-close aria-label="Fermer la recherche"><i class='bx bx-x' aria-hidden="true"></i></button>
        </form>
        <div class="search-suggestions" id="search-suggestions" aria-live="polite"></div>
    </div>
</div>
