@extends('layouts.site')

{{-- Accueil — maquette validée par CSB le 2026-10-11 : bandeau (surtitre, titre dont le dernier mot est orange,
     sous-titre, devis / WhatsApp, matériel, points), bande des valeurs, solutions logicielles et matériel côte à côte,
     réalisations et « Pourquoi choisir CSB ? », chiffres clés. Tous les textes et images se règlent dans
     Pages et paramètres → Accueil ; les blocs de contenu viennent des modules (seuls les éléments publiés). --}}
@section('content')
    @php
        $titleWords = preg_split('/\s+/', trim($site->get('home.hero_title')));
        $lastWord = count($titleWords) > 1 ? array_pop($titleWords) : null;
    @endphp

    <section class="home-hero" data-spotlight>
        <canvas class="hero-particles" aria-hidden="true"></canvas>
        <div class="home-hero-glow" aria-hidden="true"></div>
        <div class="container position-relative">
            <div class="row align-items-center g-4">
                <div class="col-lg-5 home-hero-text">
                    <p class="home-kicker">{{ $site->get('home.kicker') }}</p>
                    <h1>{{ implode(' ', $titleWords) }} @if ($lastWord)<span class="accent">{{ $lastWord }}</span>@endif</h1>
                    <p class="home-subtitle">{{ $site->get('home.hero_subtitle') }}</p>
                    <div class="hero-actions">
                        @if (Route::has('site.devis'))
                            <a href="{{ route('site.devis') }}" class="btn btn-accent btn-lg btn-glow" data-magnetic><i class='bx bxs-send' aria-hidden="true"></i> Demander un devis</a>
                        @endif
                        @if ($site->whatsappLink())
                            <a href="{{ $site->whatsappLink('Bonjour CSB, je souhaite échanger avec vous.') }}" target="_blank" rel="noopener" class="btn btn-glass btn-lg" data-magnetic><i class='bx bxl-whatsapp' aria-hidden="true"></i> Nous écrire sur WhatsApp</a>
                        @endif
                    </div>
                </div>
                <div class="col-lg-4 col-md-7 home-hero-visual" aria-hidden="true">
                    <img src="{{ $site->imageUrl('home.hero_image', 'assets/site/img/hero-devices.webp') }}" alt="" width="1200" height="647" fetchpriority="high">
                </div>
                <div class="col-lg-3 col-md-5">
                    <ul class="hero-points">
                        @foreach ($site->rows('home.hero_points', 3) as [$icon, $pointTitle, $pointHint])
                            <li>
                                <span class="hero-point-icon"><i class='bx {{ $icon ?: 'bx-check' }}' aria-hidden="true"></i></span>
                                <span><strong>{{ $pointTitle }}</strong>{{ $pointHint }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </section>

    @if ($site->rows('home.values', 3))
        <div class="container values-strip-wrap">
            <div class="values-strip">
                @foreach ($site->rows('home.values', 3) as $index => [$icon, $valueTitle, $valueText])
                    <div class="value-item">
                        <span class="value-icon v{{ $index % 4 }}"><i class='bx {{ $icon ?: 'bx-check' }}' aria-hidden="true"></i></span>
                        <div><strong>{{ $valueTitle }}</strong><span>{{ $valueText }}</span></div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <section class="home-block">
        <div class="container">
            <div class="block-head">
                <h2>Nos solutions logicielles</h2>
                <a href="{{ route('site.logiciels.index') }}">Voir toutes les solutions <i class='bx bx-right-arrow-alt' aria-hidden="true"></i></a>
            </div>
            @if ($logiciels->isEmpty())
                <p class="text-muted">Nos solutions seront présentées ici très prochainement. <a href="{{ route('site.devis', ['type' => 'sur_mesure']) }}">Parlez-nous de votre projet</a>.</p>
            @else
                {{-- Bande en boucle continue : site.js duplique les cartes pour que la fin enchaîne sur le début. --}}
                <div class="loop-rail" data-loop data-speed="40" aria-label="Nos solutions logicielles">
                    <div class="loop-track">
                        @foreach ($logiciels as $logiciel)
                            @php
                                $visual = $logiciel->gallery->first() ?? $logiciel->logo;
                            @endphp
                            <article class="solution-card loop-item">
                                <a href="{{ route('site.logiciels.show', $logiciel->slug) }}" class="solution-media" tabindex="-1" aria-hidden="true">
                                    @if ($visual)
                                        <img src="{{ $visual->thumbUrl() }}" alt="" loading="lazy" width="480" height="300">
                                    @else
                                        <i class='bx bx-code-block' aria-hidden="true"></i>
                                    @endif
                                </a>
                                <div class="solution-body">
                                    <h3><a href="{{ route('site.logiciels.show', $logiciel->slug) }}">{{ $logiciel->name }}</a></h3>
                                    <p class="solution-tagline">{{ $logiciel->tagline ?? $logiciel->category?->name }}</p>
                                    <p class="solution-text">{{ $logiciel->summary }}</p>
                                    <div class="solution-foot">
                                        <span class="solution-tags">
                                            @foreach (array_slice($logiciel->platformList(), 0, 2) as $platform)
                                                <span>{{ $platform->label() }}</span>
                                            @endforeach
                                        </span>
                                        <a href="{{ route('site.logiciels.show', $logiciel->slug) }}" class="btn btn-primary btn-sm">Découvrir <i class='bx bx-right-arrow-alt' aria-hidden="true"></i></a>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </div>
            @endif

            @if ($categories->isNotEmpty())
                <div class="block-head mt-4">
                    <h2>Matériel informatique</h2>
                    <a href="{{ route('site.materiels.index') }}">Voir tout le catalogue <i class='bx bx-right-arrow-alt' aria-hidden="true"></i></a>
                </div>
                <div class="loop-rail" data-loop data-speed="35" data-reverse aria-label="Matériel informatique">
                    <div class="loop-track">
                        @foreach ($categories as $category)
                            <article class="hardware-card loop-item">
                                @if ($category->has_new)
                                    <span class="hardware-badge">Nouveau</span>
                                @endif
                                <div class="hardware-media">
                                    @if ($category->image)
                                        <img src="{{ $category->image->thumbUrl() }}" alt="" loading="lazy" width="480" height="360">
                                    @else
                                        <i class='bx bx-laptop' aria-hidden="true"></i>
                                    @endif
                                </div>
                                <h3>{{ $category->name }}</h3>
                                <p>{{ $category->description }}</p>
                                <a href="{{ route('site.materiels.index', ['categorie' => $category->slug]) }}" class="btn btn-primary btn-sm w-100">Voir les modèles</a>
                            </article>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </section>

    @if ($materiels->isNotEmpty())
        <section class="home-block pt-0">
            <div class="container">
                <div class="block-head">
                    <h2>{{ $materiels->contains->isOnPromo() ? 'Promotions et matériel du moment' : 'Matériel du moment' }}</h2>
                    <a href="{{ route('site.materiels.index') }}">Tout le catalogue <i class='bx bx-right-arrow-alt' aria-hidden="true"></i></a>
                </div>
                <div class="csb-carousel" data-carousel data-interval="3500" aria-roledescription="carrousel" aria-label="Matériel du moment">
                    <div class="csb-carousel-viewport">
                        <div class="csb-carousel-track">
                            @foreach ($materiels as $materiel)
                                <div class="csb-carousel-slide per-4" role="group" aria-roledescription="diapositive" aria-label="{{ $loop->iteration }} sur {{ $loop->count }}">
                                    @include('site.materiels._card', ['materiel' => $materiel])
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <button type="button" class="csb-carousel-btn prev" data-carousel-prev aria-label="Précédent"><i class='bx bx-chevron-left' aria-hidden="true"></i></button>
                    <button type="button" class="csb-carousel-btn next" data-carousel-next aria-label="Suivant"><i class='bx bx-chevron-right' aria-hidden="true"></i></button>
                    <div class="csb-carousel-dots" data-carousel-dots></div>
                </div>
            </div>
        </section>
    @endif

    @if ($realisations->isNotEmpty())
        <section class="home-block projects-showcase">
            <div class="container">
                <div class="block-head">
                    <h2>Nos réalisations</h2>
                    <a href="{{ route('site.realisations.index') }}">Voir toutes les réalisations <i class='bx bx-right-arrow-alt' aria-hidden="true"></i></a>
                </div>
                <div class="csb-carousel showcase" data-carousel data-interval="5500" data-thumbs aria-roledescription="carrousel" aria-label="Nos réalisations">
                    <div class="csb-carousel-viewport">
                        <div class="csb-carousel-track">
                            @foreach ($realisations as $realisation)
                                @php
                                    $visual = $realisation->images()->first();
                                    $techs = array_slice($realisation->technologyList(), 0, 4);
                                @endphp
                                <div class="csb-carousel-slide per-1" role="group" aria-roledescription="diapositive" aria-label="{{ $loop->iteration }} sur {{ $loop->count }} : {{ $realisation->title }}">
                                    <article class="showcase-slide">
                                        <a href="{{ route('site.realisations.show', $realisation->slug) }}" class="showcase-media" tabindex="-1" aria-hidden="true">
                                            @if ($visual)
                                                <img src="{{ $visual->url() }}" alt="" loading="{{ $loop->first ? 'eager' : 'lazy' }}" width="1200" height="750">
                                            @else
                                                <i class='bx bx-briefcase-alt-2' aria-hidden="true"></i>
                                            @endif
                                        </a>
                                        <div class="showcase-body">
                                            <span class="showcase-count">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }} / {{ str_pad($loop->count, 2, '0', STR_PAD_LEFT) }}</span>
                                            @if ($realisation->sector)
                                                <span class="tag">{{ $realisation->sector }}</span>
                                            @endif
                                            <h3><a href="{{ route('site.realisations.show', $realisation->slug) }}">{{ $realisation->title }}</a></h3>
                                            @if ($realisation->client_name)
                                                <p class="showcase-client"><i class='bx bx-buildings' aria-hidden="true"></i> {{ $realisation->client_name }}@if ($realisation->year) · {{ $realisation->year }}@endif</p>
                                            @endif
                                            @if ($realisation->summary)
                                                <p class="showcase-summary">{{ \Illuminate\Support\Str::limit($realisation->summary, 220) }}</p>
                                            @endif
                                            @if ($techs)
                                                <div class="showcase-techs">
                                                    @foreach ($techs as $tech)
                                                        <span>{{ $tech }}</span>
                                                    @endforeach
                                                </div>
                                            @endif
                                            <a href="{{ route('site.realisations.show', $realisation->slug) }}" class="btn btn-primary">Voir le projet <i class='bx bx-right-arrow-alt' aria-hidden="true"></i></a>
                                        </div>
                                    </article>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <button type="button" class="csb-carousel-btn prev" data-carousel-prev aria-label="Réalisation précédente"><i class='bx bx-chevron-left' aria-hidden="true"></i></button>
                    <button type="button" class="csb-carousel-btn next" data-carousel-next aria-label="Réalisation suivante"><i class='bx bx-chevron-right' aria-hidden="true"></i></button>
                    <div class="csb-carousel-progress" aria-hidden="true"><span></span></div>
                    <div class="showcase-thumbs" data-carousel-thumbs>
                        @foreach ($realisations as $realisation)
                            @php
                                $visual = $realisation->images()->first();
                            @endphp
                            <button type="button" class="showcase-thumb" data-carousel-goto="{{ $loop->index }}" aria-label="Afficher : {{ $realisation->title }}">
                                @if ($visual)
                                    <img src="{{ $visual->thumbUrl() }}" alt="" loading="lazy" width="160" height="100">
                                @else
                                    <i class='bx bx-briefcase-alt-2' aria-hidden="true"></i>
                                @endif
                                <span>{{ $realisation->title }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>
    @endif

    <section class="home-block pt-0">
        <div class="container">
            <div class="why-box">
                <div class="why-text">
                    <h2 class="block-title">{{ $site->get('home.why_title') }}</h2>
                    <ul class="why-list">
                        @foreach ($site->lines('home.why_points') as $point)
                            <li><i class='bx bxs-check-circle' aria-hidden="true"></i>{{ $point }}</li>
                        @endforeach
                    </ul>
                    <a href="{{ route('site.about') }}" class="btn btn-outline-primary btn-sm mt-2">Découvrir CSB</a>
                </div>
                <div class="why-photo">
                    <img src="{{ $site->imageUrl('home.why_image', 'assets/site/img/why-csb.webp') }}" alt="Au service de vos besoins informatiques" loading="lazy" width="800" height="600">
                </div>
            </div>
        </div>
    </section>

    @if ($site->rows('home.stats', 3))
        <section class="stats-band">
            <div class="container">
                <div class="stats-grid" style="--stats: {{ count($site->rows('home.stats', 3)) }}">
                    @foreach ($site->rows('home.stats', 3) as [$value, $statLabel, $icon])
                        @php
                            $value = strtr($value, $counters ?? []);
                            preg_match('/^(\D*)(\d+)(\D*)$/u', $value, $parts);
                        @endphp
                        <div class="stat-item">
                            <i class='bx {{ $icon ?: 'bx-check' }}' aria-hidden="true"></i>
                            <div>
                                <strong>@if ($parts){{ $parts[1] }}<span data-count="{{ $parts[2] }}">{{ $parts[2] }}</span>{{ $parts[3] }}@else{{ $value }}@endif</strong>
                                <span>{{ $statLabel }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @foreach ($sections ?? [] as $section)
        @include($section['view'], $section['data'])
    @endforeach

    @include('site.partials.cta')
@endsection
