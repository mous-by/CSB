{{-- Écoles qui utilisent KalanNet et ont un logo (envoyées par l'application, sans saisie) : défilement lent en boucle,
     pause au survol. La liste est répétée pour remplir la largeur, puis doublée : le second exemplaire enchaîne sans saut. --}}
@php
    $tour = $ecoles;
    while ($tour->count() < 12) {
        $tour = $tour->concat($ecoles);
    }
@endphp
<section class="home-block schools-band" aria-labelledby="ecoles-titre">
    <div class="container">
        <div class="schools-head">
            <span class="schools-eyebrow">KalanNet</span>
            <h2 id="ecoles-titre" class="schools-title">Ils nous font confiance</h2>
            <p class="schools-sub">{{ $total > 1 ? number_format($total, 0, ',', ' ').' écoles gèrent' : 'Une école gère' }} leur quotidien avec KalanNet, notre logiciel de gestion scolaire.</p>
        </div>
    </div>
    <div class="schools-track-wrap">
        <div class="schools-track" style="--schools-duration: {{ $tour->count() * 9 }}s">
            @foreach ([false, true] as $copie)
                @foreach ($tour as $ecole)
                    <article class="school-card" @if ($copie || $loop->index >= $ecoles->count()) aria-hidden="true" @endif>
                        <div class="school-logo">
                            <img src="{{ asset('storage/'.$ecole->logo_path) }}" alt="Logo de {{ $ecole->name }}" loading="lazy">
                        </div>
                        <strong class="school-name">{{ $ecole->name }}</strong>
                        @if ($ecole->address)
                            <span class="school-place"><i class='bx bx-map' aria-hidden="true"></i><span>{{ $ecole->address }}</span></span>
                        @endif
                    </article>
                @endforeach
            @endforeach
        </div>
    </div>
</section>
