{{-- Écoles qui utilisent KalanNet (envoyées par l'application, sans saisie) : défilement en boucle, pause au survol.
     La liste est répétée pour remplir la largeur, puis doublée : le second exemplaire enchaîne sans saut. --}}
@php
    $tour = $ecoles;
    while ($tour->count() < 8) {
        $tour = $tour->concat($ecoles);
    }
@endphp
<section class="home-block schools-band" aria-labelledby="ecoles-titre">
    <div class="container">
        <h2 id="ecoles-titre" class="schools-title">Ils nous font confiance</h2>
        <p class="schools-sub">{{ $total > 1 ? number_format($total, 0, ',', ' ').' écoles utilisent' : 'Une école utilise' }} KalanNet, notre logiciel de gestion scolaire.</p>
    </div>
    <div class="schools-track-wrap">
        <div class="schools-track" style="--schools-duration: {{ max(30, $tour->count() * 5) }}s">
            @foreach ([false, true] as $copie)
                @foreach ($tour as $ecole)
                    <div class="school-card" @if ($copie || $loop->index >= $ecoles->count()) aria-hidden="true" @endif>
                        @if ($ecole->logo_path)
                            <img src="{{ asset('storage/'.$ecole->logo_path) }}" alt="" loading="lazy" width="64" height="64">
                        @else
                            <span class="school-initials" style="--hue: {{ $ecole->hue() }}" aria-hidden="true">{{ $ecole->initials() }}</span>
                        @endif
                        <div class="school-text">
                            <strong>{{ $ecole->name }}</strong>
                            @if ($ecole->address)
                                <span><i class='bx bx-map' aria-hidden="true"></i>{{ $ecole->address }}</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            @endforeach
        </div>
    </div>
</section>
