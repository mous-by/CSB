@extends('layouts.site')

@section('whatsapp_message', 'Bonjour CSB, je souhaite des informations sur vos solutions logicielles.')

@section('content')
    @include('site.partials.page-hero', [
        'title' => $category ? $category->name : 'Solutions logicielles',
        'crumb' => $category ? $category->name : null,
        'crumbs' => $category ? [['Logiciels', route('site.logiciels.index')]] : [],
        'intro' => $category?->description ?: 'Logiciels de gestion, solutions métiers et applications web et mobiles : des outils conçus pour simplifier votre activité.',
    ])

    <section class="section">
        <div class="container">
            <form method="GET" action="{{ route('site.logiciels.index') }}" class="filter-bar" role="search" aria-label="Filtrer les logiciels" data-ajax-filter="#catalogue-results">
                <div class="row g-3 align-items-end">
                    <div class="col-md-5">
                        <label for="q" class="form-label">Rechercher</label>
                        <input type="search" class="form-control" id="q" name="q" value="{{ $search }}" placeholder="Nom, fonctionnalité…">
                    </div>
                    <div class="col-sm-6 col-md-3">
                        <label for="categorie" class="form-label">Secteur</label>
                        <select class="form-select" id="categorie" name="categorie">
                            <option value="">Tous les secteurs</option>
                            @foreach ($categories as $item)
                                <option value="{{ $item->slug }}" @selected($category?->id === $item->id)>{{ $item->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-6 col-md-2">
                        <label for="plateforme" class="form-label">Plateforme</label>
                        <select class="form-select" id="plateforme" name="plateforme">
                            <option value="">Toutes</option>
                            @foreach (\App\Enums\Platform::cases() as $item)
                                <option value="{{ $item->value }}" @selected($platform === $item)>{{ $item->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 d-grid">
                        <button type="submit" class="btn btn-primary"><i class='bx bx-filter-alt' aria-hidden="true"></i> Filtrer</button>
                    </div>
                </div>
            </form>

            <div id="catalogue-results" data-ajax-results>
            <p class="results-count mb-3" data-results-count>
                {{ $logiciels->total() }} logiciel{{ $logiciels->total() > 1 ? 's' : '' }}{{ $filtered ? ' trouvé'.($logiciels->total() > 1 ? 's' : '') : '' }}
                @if ($filtered)
                    · <a href="{{ route('site.logiciels.index') }}" data-ajax-link>Effacer les filtres</a>
                @endif
            </p>
            @if ($logiciels->isEmpty())
                <div class="empty-state">
                    <i class='bx bx-search-alt' aria-hidden="true"></i>
                    <h2 class="h5 mt-3">{{ $filtered ? 'Aucun logiciel ne correspond à votre recherche' : 'Notre catalogue de logiciels arrive bientôt' }}</h2>
                    <p class="text-muted">{{ $filtered ? 'Essayez un autre secteur ou une autre plateforme, ou décrivez-nous votre besoin : nous concevons aussi des solutions sur mesure.' : 'Décrivez-nous votre besoin : nous concevons aussi des solutions sur mesure.' }}</p>
                    <div class="d-flex flex-wrap justify-content-center gap-2">
                        @if ($filtered)
                            <a href="{{ route('site.logiciels.index') }}" class="btn btn-outline-primary" data-ajax-link>Voir tous les logiciels</a>
                        @endif
                        @if (Route::has('site.devis'))
                            <a href="{{ route('site.devis', ['type' => 'sur_mesure']) }}" class="btn btn-accent">Demander une solution sur mesure</a>
                        @endif
                    </div>
                </div>
            @else
                <div class="row g-4">
                    @foreach ($logiciels as $logiciel)
                        <div class="col-md-6 col-lg-4">
                            @include('site.logiciels._card', ['logiciel' => $logiciel])
                        </div>
                    @endforeach
                </div>
                <div class="mt-5 d-flex justify-content-center">{{ $logiciels->onEachSide(1)->links() }}</div>
            @endif
            </div>
        </div>
    </section>

    @include('site.partials.cta', ['title' => 'Vous ne trouvez pas la solution idéale ?', 'text' => 'CSB développe des logiciels et des applications web et mobiles sur mesure, adaptés à votre activité.'])
@endsection
