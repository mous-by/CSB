@extends('layouts.site')

@section('content')
    @include('site.partials.page-hero', ['title' => 'Contacter CSB', 'crumb' => 'Contact', 'intro' => 'Une question, un besoin, un projet ? Appelez-nous, écrivez-nous sur WhatsApp ou envoyez-nous un message.'])

    <section class="section">
        <div class="container">
            <div class="row g-5">
                <div class="col-lg-5">
                    <h2 class="h3 mb-4">Nos coordonnées</h2>
                    <ul class="contact-list">
                        @foreach (array_filter([$site->get('contact.phone_1'), $site->get('contact.phone_2')]) as $phone)
                            @php
                                $tel = \App\Support\PhoneNumber::normalize($phone);
                            @endphp
                            <li>
                                <span class="icon-box"><i class='bx bx-phone' aria-hidden="true"></i></span>
                                <div><strong>Téléphone</strong>@if ($tel)<a href="tel:{{ $tel }}">{{ $phone }}</a>@else{{ $phone }}@endif</div>
                            </li>
                        @endforeach
                        @if ($site->whatsappLink())
                            <li>
                                <span class="icon-box" style="background:#e3f6ea;color:#16723d"><i class='bx bxl-whatsapp' aria-hidden="true"></i></span>
                                <div><strong>WhatsApp</strong><a href="{{ $site->whatsappLink('Bonjour CSB,') }}" target="_blank" rel="noopener">{{ $site->get('contact.whatsapp') }}</a></div>
                            </li>
                        @endif
                        @if ($site->get('contact.email'))
                            <li>
                                <span class="icon-box orange"><i class='bx bx-envelope' aria-hidden="true"></i></span>
                                <div><strong>E-mail</strong><a href="mailto:{{ $site->get('contact.email') }}">{{ $site->get('contact.email') }}</a></div>
                            </li>
                        @endif
                        @if ($site->get('contact.address'))
                            <li>
                                <span class="icon-box"><i class='bx bx-map' aria-hidden="true"></i></span>
                                <div><strong>Adresse</strong><span>{!! nl2br(e($site->get('contact.address'))) !!}</span>
                                    @if ($site->get('contact.map_url'))
                                        <br><a href="{{ $site->get('contact.map_url') }}" target="_blank" rel="noopener" class="small">Voir sur la carte <i class='bx bx-link-external' aria-hidden="true"></i></a>
                                    @endif
                                </div>
                            </li>
                        @endif
                        @if ($site->get('contact.hours'))
                            <li>
                                <span class="icon-box orange"><i class='bx bx-time-five' aria-hidden="true"></i></span>
                                <div><strong>Horaires</strong><span>{!! nl2br(e($site->get('contact.hours'))) !!}</span></div>
                            </li>
                        @endif
                    </ul>
                    @if (Route::has('site.devis'))
                        <div class="detail-panel mt-4">
                            <h3 class="h6">Vous avez un projet précis ?</h3>
                            <p class="small text-muted">Logiciel, développement sur mesure, matériel ou maintenance : décrivez votre besoin pour recevoir une proposition.</p>
                            <a href="{{ route('site.devis') }}" class="btn btn-outline-primary btn-sm">Demander un devis</a>
                        </div>
                    @endif
                </div>
                <div class="col-lg-7">
                    @component('site.demandes._form', ['action' => route('site.contact.store'), 'submit' => 'Envoyer le message'])
                        @include('site.demandes._identity')
                        <div class="col-12">@include('site.demandes._field', ['name' => 'subject', 'label' => 'Objet', 'required' => true, 'max' => 200])</div>
                        <div class="col-12">@include('site.demandes._field', ['name' => 'message', 'label' => 'Message', 'type' => 'textarea', 'required' => true, 'max' => 5000, 'rows' => 6])</div>
                    @endcomponent
                </div>
            </div>
        </div>
    </section>
@endsection
