@extends('layouts.site')

@section('whatsapp_message', 'Bonjour CSB, j\'ai vu votre réalisation « '.$realisation->title.' » et j\'ai un projet similaire.')

@section('content')
    @include('site.partials.page-hero', ['title' => $realisation->title, 'crumbs' => [['Réalisations', route('site.realisations.index')]], 'intro' => $realisation->summary])

    <section class="section">
        <div class="container">
            <div class="row g-5">
                <div class="col-lg-8">
                    @include('site.partials.gallery', ['images' => $realisation->images()])

                    @foreach ([['context', 'Le contexte', 'bx-buildings'], ['need', 'Le besoin', 'bx-target-lock']] as [$field, $title, $icon])
                        @if ($realisation->{$field})
                            <div class="d-flex gap-3 mt-5">
                                <span class="icon-box {{ $field === 'need' ? 'orange' : '' }}"><i class='bx {{ $icon }}' aria-hidden="true"></i></span>
                                <div>
                                    <h2 class="h3">{{ $title }}</h2>
                                    <p class="lead-muted mb-0">{!! nl2br(e($realisation->{$field})) !!}</p>
                                </div>
                            </div>
                        @endif
                    @endforeach

                    @if ($solutionHtml)
                        <div class="d-flex gap-3 mt-5">
                            <span class="icon-box"><i class='bx bx-bulb' aria-hidden="true"></i></span>
                            <div>
                                <h2 class="h3">La solution apportée</h2>
                                <div class="prose">{!! $solutionHtml !!}</div>
                            </div>
                        </div>
                    @endif
                </div>
                <aside class="col-lg-4">
                    <div class="detail-aside">
                        <div class="detail-panel">
                            <h2 class="h5">Le projet</h2>
                            <dl class="spec-list">
                                @if ($realisation->client_name)
                                    <div><dt>Client</dt><dd>{{ $realisation->client_name }}</dd></div>
                                @endif
                                @if ($realisation->sector)
                                    <div><dt>Secteur</dt><dd>{{ $realisation->sector }}</dd></div>
                                @endif
                                @if ($realisation->year)
                                    <div><dt>Année</dt><dd>{{ $realisation->year }}</dd></div>
                                @endif
                            </dl>
                            @if ($realisation->technologyList())
                                <h3 class="h6 mt-4">Technologies</h3>
                                <div class="d-flex flex-wrap gap-2">
                                    @foreach ($realisation->technologyList() as $technology)
                                        <span class="tag">{{ $technology }}</span>
                                    @endforeach
                                </div>
                            @endif
                            @if ($realisation->logiciel?->is_published)
                                <a href="{{ route('site.logiciels.show', $realisation->logiciel->slug) }}" class="btn btn-outline-primary w-100 mt-4"><i class='bx bx-code-block' aria-hidden="true"></i> Découvrir {{ $realisation->logiciel->name }}</a>
                            @endif
                            @if (Route::has('site.devis'))
                                <a href="{{ route('site.devis') }}" class="btn btn-accent w-100 mt-2">Un projet similaire ? Demander un devis</a>
                            @endif
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </section>

    @if ($others->isNotEmpty())
        <section class="section section-alt">
            <div class="container">
                <div class="section-title"><span class="eyebrow">Autres projets</span><h2>Découvrez aussi</h2></div>
                <div class="row g-4">
                    @foreach ($others as $item)
                        <div class="col-md-6 col-lg-4">@include('site.realisations._card', ['realisation' => $item])</div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif
@endsection
