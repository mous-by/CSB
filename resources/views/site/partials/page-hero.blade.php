{{-- En-tête d'une page intérieure : fil d'Ariane, titre (h1 unique de la page) et introduction.
     $title, $intro (facultatif), $crumbs : [[libellé, url|null], …] entre « Accueil » et la page courante. --}}
<section class="page-hero">
    <div class="container">
        <nav aria-label="Fil d'Ariane">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('site.home') }}">Accueil</a></li>
                @foreach ($crumbs ?? [] as [$label, $url])
                    <li class="breadcrumb-item"><a href="{{ $url }}">{{ $label }}</a></li>
                @endforeach
                <li class="breadcrumb-item active" aria-current="page">{{ $crumb ?? $title }}</li>
            </ol>
        </nav>
        <h1>{{ $title }}</h1>
        @if (! empty($intro))
            <p>{{ $intro }}</p>
        @endif
    </div>
</section>
