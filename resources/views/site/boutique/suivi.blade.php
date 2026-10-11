@extends('layouts.site')

@section('content')
    @include('site.partials.page-hero', ['title' => 'Commande '.$commande->number, 'crumb' => 'Commande', 'intro' => $commande->status->meaning()])

    <section class="section">
        <div class="container">
            @if (session('commande_placed'))
                <div class="alert alert-success d-flex gap-3 align-items-start" role="status">
                    <i class='bx bx-check-circle fs-2' aria-hidden="true"></i>
                    <div><strong>Merci {{ $commande->name }} ! Votre commande est enregistrée.</strong><br>Nous vous appelons au {{ $commande->phone }} pour confirmer la disponibilité. Gardez cette page : son adresse vous permet de suivre votre commande{{ $commande->email ? ' (elle vous a aussi été envoyée par e-mail)' : '' }}.</div>
                </div>
            @endif
            <div class="row g-4">
                <div class="col-lg-7">
                    <div class="detail-panel">
                        <div class="d-flex flex-wrap justify-content-between gap-2 mb-3">
                            <h2 class="h5 mb-0">Articles</h2>
                            <span class="tag {{ $commande->status->tag() }}">{{ $commande->status->label() }}</span>
                        </div>
                        @foreach ($commande->lignes as $ligne)
                            <div class="d-flex justify-content-between gap-3 py-2 border-bottom">
                                <span>{{ $ligne->designation }} <span class="text-muted">× {{ $ligne->quantity }} à {{ \App\Models\Materiel::money($ligne->unit_price) }}</span>
                                    @if ($ligne->normal_price)<del class="price-old ms-1">{{ \App\Models\Materiel::money($ligne->normal_price) }}</del>@endif
                                </span>
                                <strong class="text-nowrap">{{ \App\Models\Materiel::money($ligne->line_total) }}</strong>
                            </div>
                        @endforeach
                        <div class="d-flex justify-content-between align-items-baseline mt-3">
                            <span>Total</span><strong class="h4 mb-0 text-blue">{{ $commande->totalForDisplay() }}</strong>
                        </div>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="detail-panel">
                        <h2 class="h6">Suivi</h2>
                        <ol class="order-steps">
                            @foreach ([\App\Enums\CommandeStatus::Nouvelle, \App\Enums\CommandeStatus::Confirmee, \App\Enums\CommandeStatus::Payee, \App\Enums\CommandeStatus::Livree] as $step)
                                @php
                                    $order = [\App\Enums\CommandeStatus::Nouvelle, \App\Enums\CommandeStatus::Confirmee, \App\Enums\CommandeStatus::Payee, \App\Enums\CommandeStatus::Livree];
                                    $done = $commande->status !== \App\Enums\CommandeStatus::Annulee && array_search($commande->status, $order, true) >= array_search($step, $order, true);
                                @endphp
                                <li class="{{ $done ? 'done' : '' }}">{{ $step->label() }}</li>
                            @endforeach
                        </ol>
                        @if ($commande->status === \App\Enums\CommandeStatus::Annulee && $commande->cancel_reason)
                            <p class="small text-muted">Motif : {{ $commande->cancel_reason }}</p>
                        @endif
                        @if ($commande->status->isOpen())
                            <div class="pay-box"><i class='bx bx-wallet' aria-hidden="true"></i> {{ $instructions }}</div>
                        @endif
                        <dl class="spec-list mt-3">
                            <div><dt>Réception</dt><dd>{{ $commande->delivery_method->label() }}</dd></div>
                            @if ($commande->address)<div><dt>Adresse</dt><dd>{{ $commande->address }}{{ $commande->city ? ', '.$commande->city : '' }}</dd></div>@endif
                            <div><dt>Paiement</dt><dd>{{ $commande->payment_method->label() }}</dd></div>
                            <div><dt>Passée le</dt><dd>{{ $commande->created_at->format('d/m/Y à H:i') }}</dd></div>
                        </dl>
                        @if ($site->whatsappLink())
                            <a href="{{ $site->whatsappLink('Bonjour CSB, je vous contacte au sujet de ma commande '.$commande->number.'.') }}" class="btn btn-whatsapp w-100" target="_blank" rel="noopener"><i class='bx bxl-whatsapp' aria-hidden="true"></i> Une question ? WhatsApp</a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
