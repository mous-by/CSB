{{-- Erreur sur le site public : message clair, retour à l'accueil, accès aux pages principales (cahier §4 « Page 404 »). --}}
<section class="error-page">
    <div class="container">
        <div class="code" aria-hidden="true">{{ substr($code, 0, 1) }}<span>{{ substr($code, 1, 1) }}</span>{{ substr($code, 2) }}</div>
        <h1 class="h2 mt-3">{{ $title }}</h1>
        <p class="lead-muted mb-4">{{ $message }}</p>
        <div class="d-flex flex-wrap justify-content-center gap-2 mb-5">
            <a href="{{ route('site.home') }}" class="btn btn-primary"><i class='bx bx-home-alt' aria-hidden="true"></i> Retour à l'accueil</a>
            @if (Route::has('site.contact'))
                <a href="{{ route('site.contact') }}" class="btn btn-outline-primary">Contacter CSB</a>
            @endif
        </div>
        <nav aria-label="Pages principales" class="filter-chips justify-content-center">
            @foreach ([['site.services', 'Services'], ['site.logiciels.index', 'Logiciels'], ['site.materiels.index', 'Matériel'], ['site.realisations.index', 'Réalisations'], ['site.actualites.index', 'Actualités'], ['site.about', 'À propos']] as [$route, $label])
                @if (Route::has($route))
                    <a class="filter-chip" href="{{ route($route) }}">{{ $label }}</a>
                @endif
            @endforeach
        </nav>
    </div>
</section>
