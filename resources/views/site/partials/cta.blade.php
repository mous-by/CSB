{{-- Bandeau d'appel à l'action (cahier §8 : « Demander un devis », « Demander une démonstration », « Contacter CSB »). --}}
<section class="section-sm">
    <div class="container">
        <div class="cta-band">
            <div class="row align-items-center g-4">
                <div class="col-lg-7 position-relative" style="z-index: 1">
                    <h2>{{ $title ?? 'Un projet, un besoin en informatique ?' }}</h2>
                    <p>{{ $text ?? 'Parlez-nous de votre besoin : nous vous répondons rapidement avec une proposition adaptée.' }}</p>
                </div>
                <div class="col-lg-5">
                    <div class="cta-actions justify-content-lg-end">
                        @if (Route::has('site.devis'))
                            <a href="{{ route('site.devis') }}" class="btn btn-accent btn-lg">Demander un devis</a>
                        @endif
                        @if (Route::has('site.contact'))
                            <a href="{{ route('site.contact') }}" class="btn btn-outline-light btn-lg">Contacter CSB</a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
