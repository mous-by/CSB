@extends('layouts.site')

@section('content')
    @include('site.partials.page-hero', ['title' => 'À propos de '.$site->get('company.short_name'), 'crumb' => 'À propos', 'intro' => $site->get('company.tagline')])

    <section class="section">
        <div class="container">
            <div class="row g-5 align-items-start">
                <div class="col-lg-7">
                    <div class="section-title mb-4">
                        <span class="eyebrow">Qui sommes-nous</span>
                        <h2>{{ $site->get('company.name') }}</h2>
                    </div>
                    <div class="prose">{!! $site->html('about.presentation') !!}</div>
                </div>
                <div class="col-lg-5">
                    <div class="row g-3">
                        <div class="col-12">
                            <div class="detail-panel">
                                <div class="d-flex gap-3">
                                    <span class="icon-box"><i class='bx bx-target-lock' aria-hidden="true"></i></span>
                                    <div>
                                        <h3 class="h5">Notre mission</h3>
                                        <p class="mb-0">{!! nl2br(e($site->get('about.mission'))) !!}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="detail-panel">
                                <div class="d-flex gap-3">
                                    <span class="icon-box orange"><i class='bx bx-show' aria-hidden="true"></i></span>
                                    <div>
                                        <h3 class="h5">Notre vision</h3>
                                        <p class="mb-0">{!! nl2br(e($site->get('about.vision'))) !!}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @if ($site->lines('about.values'))
        <section class="section section-alt">
            <div class="container">
                <div class="section-title text-center">
                    <span class="eyebrow">Nos valeurs</span>
                    <h2>Ce qui guide notre travail</h2>
                </div>
                <div class="row g-4 justify-content-center">
                    @foreach ($site->lines('about.values') as $index => $value)
                        <div class="col-sm-6 col-lg-4">
                            <div class="csb-card text-center">
                                <div class="csb-card-body align-items-center">
                                    <span class="icon-box {{ $index % 2 ? 'orange' : '' }}"><i class='bx {{ ['bx-medal', 'bx-check-shield', 'bx-rocket', 'bx-heart', 'bx-bulb', 'bx-group'][$index % 6] }}' aria-hidden="true"></i></span>
                                    <h3>{{ $value }}</h3>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @if ($site->lines('about.method'))
        <section class="section">
            <div class="container">
                <div class="row g-5">
                    <div class="col-lg-5">
                        <div class="section-title">
                            <span class="eyebrow">Méthode de travail</span>
                            <h2>Comment nous menons vos projets</h2>
                            <p>Une démarche simple et transparente, de l'écoute de votre besoin jusqu'à l'accompagnement après la livraison.</p>
                        </div>
                    </div>
                    <div class="col-lg-7">
                        <ol class="steps">
                            @foreach ($site->lines('about.method') as $step)
                                <li>{{ $step }}</li>
                            @endforeach
                        </ol>
                    </div>
                </div>
            </div>
        </section>
    @endif

    @include('site.partials.cta')
@endsection
