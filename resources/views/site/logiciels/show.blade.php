@extends('layouts.site')

@section('whatsapp_message', 'Bonjour CSB, je souhaite des informations sur le logiciel « '.$logiciel->name.' ».')

@section('content')
    @include('site.partials.page-hero', [
        'title' => $logiciel->name,
        'crumbs' => array_values(array_filter([['Logiciels', route('site.logiciels.index')], $logiciel->category?->is_active ? [$logiciel->category->name, route('site.logiciels.index', ['categorie' => $logiciel->category->slug])] : null])),
        'intro' => $logiciel->summary,
    ])

    <section class="section">
        <div class="container">
            @if ($archived)
                <div class="alert alert-secondary d-flex gap-2 align-items-center mb-4" role="status">
                    <i class='bx bx-archive fs-4' aria-hidden="true"></i>
                    <div>Ce logiciel est <strong>archivé</strong> : il n'est plus proposé. Contactez-nous pour connaître les solutions qui le remplacent.</div>
                </div>
            @endif
            <div class="row g-5">
                <div class="col-lg-8">
                    @include('site.partials.gallery', ['images' => $logiciel->gallery])

                    @if ($logiciel->problem)
                        <h2 class="h3 mt-5">Le besoin auquel il répond</h2>
                        <p class="lead-muted">{!! nl2br(e($logiciel->problem)) !!}</p>
                    @endif

                    @if ($logiciel->featureList())
                        <h2 class="h3 mt-5 mb-3">Fonctionnalités</h2>
                        <ul class="feature-list">
                            @foreach ($logiciel->featureList() as $feature)
                                <li><i class='bx bx-check-circle' aria-hidden="true"></i><span>{{ $feature }}</span></li>
                            @endforeach
                        </ul>
                    @endif

                    @if ($descriptionHtml)
                        <h2 class="h3 mt-5">Présentation</h2>
                        <div class="prose">{!! $descriptionHtml !!}</div>
                    @endif
                </div>

                <aside class="col-lg-4">
                    <div class="detail-aside">
                        <div class="detail-panel">
                            @if ($logiciel->logo)
                                <img src="{{ $logiciel->logo->thumbUrl() }}" alt="Logo {{ $logiciel->name }}" class="mb-3" style="max-height: 70px; width: auto">
                            @endif
                            <dl class="spec-list">
                                <div><dt>Statut</dt><dd><span class="tag {{ $logiciel->status->tag() }}">{{ $logiciel->status->label() }}</span></dd></div>
                                @if ($logiciel->category)
                                    <div><dt>Secteur</dt><dd>{{ $logiciel->category->name }}</dd></div>
                                @endif
                                @if ($logiciel->platformList())
                                    <div><dt>Plateformes</dt><dd>{{ collect($logiciel->platformList())->map->label()->implode(', ') }}</dd></div>
                                @endif
                                <div><dt>Tarif</dt><dd>{{ $logiciel->price_text ?: 'Sur devis' }}</dd></div>
                            </dl>
                            @unless ($archived)
                                <div class="d-grid gap-2 mt-4">
                                    @if (Route::has('site.demonstration'))
                                        <a href="{{ route('site.demonstration', $logiciel->slug) }}" class="btn btn-accent">Demander une démonstration</a>
                                    @endif
                                    @if (Route::has('site.devis'))
                                        <a href="{{ route('site.devis', ['type' => 'logiciel', 'logiciel' => $logiciel->slug]) }}" class="btn btn-outline-primary">Demander un devis</a>
                                    @endif
                                    @if ($site->whatsappLink())
                                        <a href="{{ $site->whatsappLink('Bonjour CSB, je souhaite des informations sur le logiciel « '.$logiciel->name.' ».') }}" class="btn btn-whatsapp" target="_blank" rel="noopener"><i class='bx bxl-whatsapp' aria-hidden="true"></i> Écrire sur WhatsApp</a>
                                    @endif
                                </div>
                            @endunless
                            @if ($logiciel->video_url)
                                <a href="{{ $logiciel->video_url }}" class="btn btn-link px-0 mt-2" target="_blank" rel="noopener"><i class='bx bx-play-circle' aria-hidden="true"></i> Voir la vidéo de présentation</a>
                            @endif
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </section>

    @if ($related->isNotEmpty())
        <section class="section section-alt">
            <div class="container">
                <div class="section-title">
                    <span class="eyebrow">Dans le même secteur</span>
                    <h2>Autres solutions</h2>
                </div>
                <div class="row g-4">
                    @foreach ($related as $item)
                        <div class="col-md-6 col-lg-4">
                            @include('site.logiciels._card', ['logiciel' => $item])
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @include('site.partials.cta')
@endsection
