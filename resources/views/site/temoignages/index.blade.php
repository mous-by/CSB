@extends('layouts.site')

@section('content')
    @include('site.partials.page-hero', ['title' => 'Ils nous font confiance', 'crumb' => 'Témoignages', 'intro' => 'Écoles, commerces, entreprises et particuliers racontent leur expérience avec Computer Service Barry.'])

    <section class="section">
        <div class="container">
            @if ($videos->isEmpty() && $ecrits->isEmpty())
                <div class="detail-panel text-center py-5">
                    <i class='bx bx-video display-4 text-muted' aria-hidden="true"></i>
                    <h2 class="h4 mt-3">Les premiers témoignages arrivent bientôt</h2>
                    <p class="text-muted">Vous êtes client de CSB ? Soyez le premier à partager votre expérience.</p>
                </div>
            @endif

            @if ($videos->isNotEmpty())
                <div class="block-head">
                    <h2><i class='bx bx-video text-orange' aria-hidden="true"></i> En vidéo</h2>
                    <span class="text-muted small">{{ $videos->count() }} témoignage(s)</span>
                </div>
                <div class="row g-4 mb-5">
                    @foreach ($videos as $temoignage)
                        <div class="col-md-6 col-lg-4">@include('site.temoignages._video-card')</div>
                    @endforeach
                </div>
            @endif

            @if ($ecrits->isNotEmpty())
                <div class="block-head">
                    <h2><i class='bx bx-message-square-dots text-orange' aria-hidden="true"></i> Ils l'ont écrit</h2>
                </div>
                <div class="row g-4">
                    @foreach ($ecrits as $temoignage)
                        <div class="col-md-6 col-lg-4">@include('site.temoignages._text-card')</div>
                    @endforeach
                </div>
            @endif

            <div class="share-cta mt-5">
                <div>
                    <h2 class="h3 mb-1">Vous êtes client de CSB ?</h2>
                    <p class="mb-0">Racontez votre expérience en vidéo (même filmée au téléphone) ou en quelques lignes. Nous la publions avec votre accord.</p>
                </div>
                <a href="{{ route('site.temoignages.create') }}" class="btn btn-accent btn-lg"><i class='bx bx-video-recording' aria-hidden="true"></i> Partager mon expérience</a>
            </div>
        </div>
    </section>
@endsection
