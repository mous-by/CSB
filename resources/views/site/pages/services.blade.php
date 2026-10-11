@extends('layouts.site')

@section('whatsapp_message', 'Bonjour CSB, je souhaite des informations sur vos services.')

@section('content')
    @include('site.partials.page-hero', ['title' => 'Nos services', 'intro' => 'Vente, réparation, maintenance, développement, réseaux et accompagnement : des prestations informatiques fiables pour les professionnels.'])

    <section class="section">
        <div class="container">
            @forelse ($prestations as $index => $prestation)
                <article id="{{ $prestation->slug }}" class="row g-4 align-items-start py-4 {{ $loop->last ? '' : 'border-bottom' }}" style="scroll-margin-top: 110px">
                    <div class="col-md-4 col-lg-3">
                        <span class="icon-box {{ $index % 2 ? 'orange' : '' }}"><i class='bx {{ $prestation->icon }}' aria-hidden="true"></i></span>
                        <h2 class="h4">{{ $prestation->title }}</h2>
                    </div>
                    <div class="col-md-8 col-lg-9">
                        <p class="lead-muted">{{ $prestation->summary }}</p>
                        @if ($prestation->description)
                            <div class="prose">{!! \App\Support\Markdown::toHtml($prestation->description) !!}</div>
                        @endif
                        @if (Route::has('site.devis'))
                            <a href="{{ route('site.devis', ['besoin' => $prestation->title]) }}" class="btn btn-outline-primary btn-sm">Demander un devis pour ce service</a>
                        @endif
                    </div>
                </article>
            @empty
                <div class="empty-state">
                    <i class='bx bx-wrench' aria-hidden="true"></i>
                    <h2 class="h5 mt-3">Nos services seront bientôt présentés ici</h2>
                    <p class="text-muted mb-0">En attendant, contactez-nous pour toute demande : vente, réparation, maintenance ou développement.</p>
                </div>
            @endforelse
        </div>
    </section>

    @include('site.partials.cta')
@endsection
