@extends('layouts.site')

@section('content')
    @include('site.partials.page-hero', ['title' => $term === '' ? 'Rechercher sur le site' : 'Résultats pour « '.$term.' »', 'crumb' => 'Recherche'])

    <section class="section">
        <div class="container">
            <form method="GET" action="{{ route('site.search') }}" class="filter-bar" role="search" data-ajax-filter="#search-results">
                <div class="row g-3 align-items-end">
                    <div class="col-md-9">
                        <label for="search-q" class="form-label">Logiciel, matériel, service, projet, article…</label>
                        <input type="search" class="form-control form-control-lg" id="search-q" name="q" value="{{ $term }}" autofocus>
                    </div>
                    <div class="col-md-3 d-grid"><button type="submit" class="btn btn-primary btn-lg"><i class='bx bx-search' aria-hidden="true"></i> Rechercher</button></div>
                </div>
            </form>

            <div id="search-results" data-ajax-results>
                @if ($term === '')
                    <p class="results-count" data-results-count>Saisissez un mot-clé.</p>
                @elseif ($groups->isEmpty())
                    <p class="results-count" data-results-count>Aucun résultat.</p>
                    <div class="empty-state">
                        <i class='bx bx-search-alt' aria-hidden="true"></i>
                        <h2 class="h5 mt-3">Aucun résultat pour « {{ $term }} »</h2>
                        <p class="text-muted">Essayez un autre mot, ou posez-nous directement votre question.</p>
                        @if (Route::has('site.contact'))<a href="{{ route('site.contact') }}" class="btn btn-accent">Contacter CSB</a>@endif
                    </div>
                @else
                    <p class="results-count" data-results-count>{{ $groups->sum(fn ($g) => count($g['items'])) }} résultat(s) pour « {{ $term }} »</p>
                    @foreach ($groups as $group)
                        <h2 class="h4 mt-4 mb-3"><i class='bx {{ $group['icon'] }} text-orange' aria-hidden="true"></i> {{ $group['label'] }}</h2>
                        <div class="row g-3">
                            @foreach ($group['items'] as $item)
                                <div class="col-md-6">
                                    <article class="csb-card">
                                        <div class="csb-card-body stretched">
                                            <h3 class="h6 mb-1"><a href="{{ $item['url'] }}">{{ $item['title'] }}</a></h3>
                                            <p class="small mb-0">{{ $item['text'] }}</p>
                                        </div>
                                    </article>
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                @endif
            </div>
        </div>
    </section>
@endsection
