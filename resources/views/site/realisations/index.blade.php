@extends('layouts.site')

@section('content')
    @include('site.partials.page-hero', ['title' => 'Nos réalisations', 'intro' => 'Quelques projets menés par CSB pour ses clients, publiés avec leur autorisation.'])

    <section class="section">
        <div class="container">
            <form method="GET" action="{{ route('site.realisations.index') }}" class="filter-bar" role="search" aria-label="Rechercher une réalisation" data-ajax-filter="#realisations-results">
                <div class="row g-3 align-items-end">
                    <div class="col-md-9">
                        <label for="q" class="form-label">Rechercher un projet</label>
                        <input type="search" class="form-control" id="q" name="q" value="{{ $search }}" placeholder="Projet, client, technologie…">
                        <input type="hidden" name="secteur" value="{{ $sector }}">
                    </div>
                    <div class="col-md-3 d-grid">
                        <button type="submit" class="btn btn-primary"><i class='bx bx-search' aria-hidden="true"></i> Rechercher</button>
                    </div>
                </div>
            </form>

            <div id="realisations-results" data-ajax-results>
                @if ($sectors->isNotEmpty())
                    <nav class="filter-chips mb-4" aria-label="Secteurs">
                        <a href="{{ route('site.realisations.index', array_filter(['q' => $search])) }}" class="filter-chip {{ $sector ? '' : 'active' }}" data-ajax-link @if (! $sector) aria-current="true" @endif>Tous</a>
                        @foreach ($sectors as $item)
                            <a href="{{ route('site.realisations.index', array_filter(['secteur' => $item, 'q' => $search])) }}" class="filter-chip {{ $sector === $item ? 'active' : '' }}" data-ajax-link @if ($sector === $item) aria-current="true" @endif>{{ $item }}</a>
                        @endforeach
                    </nav>
                @endif
                <p class="results-count mb-3" data-results-count>{{ $realisations->total() }} réalisation{{ $realisations->total() > 1 ? 's' : '' }}{{ $sector ? ' — '.$sector : '' }}</p>
                @if ($realisations->isEmpty())
                    <div class="empty-state">
                        <i class='bx bx-briefcase-alt-2' aria-hidden="true"></i>
                        <h2 class="h5 mt-3">{{ $filtered ? 'Aucune réalisation ne correspond' : 'Nos réalisations seront bientôt présentées ici' }}</h2>
                        @if ($filtered)
                            <a href="{{ route('site.realisations.index') }}" class="btn btn-outline-primary mt-2" data-ajax-link>Voir toutes les réalisations</a>
                        @endif
                    </div>
                @else
                    <div class="row g-4">
                        @foreach ($realisations as $realisation)
                            <div class="col-md-6 col-lg-4">
                                @include('site.realisations._card', ['realisation' => $realisation])
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-5 d-flex justify-content-center">{{ $realisations->onEachSide(1)->links() }}</div>
                @endif
            </div>
        </div>
    </section>

    @include('site.partials.cta', ['title' => 'Et si votre projet était le prochain ?', 'text' => 'Présentez-nous votre besoin : nous vous proposons une solution adaptée et chiffrée.'])
@endsection
