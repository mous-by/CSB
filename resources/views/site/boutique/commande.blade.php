@extends('layouts.site')

@section('content')
    @include('site.partials.page-hero', ['title' => 'Finaliser ma commande', 'crumb' => 'Commande', 'intro' => 'Vos coordonnées pour que nous vous rappelions. Aucun paiement maintenant : vous réglez après confirmation de la disponibilité.'])

    <section class="section">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-7">
                    <form method="POST" action="{{ route('site.commande.store') }}" class="form-card" novalidate>
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-6">@include('site.demandes._field', ['name' => 'name', 'label' => 'Nom complet', 'required' => true, 'max' => 150, 'value' => $client?->name, 'autocomplete' => 'name'])</div>
                            <div class="col-md-6">@include('site.demandes._field', ['name' => 'organization', 'label' => 'Entreprise / organisation', 'max' => 150, 'value' => $client?->organization, 'autocomplete' => 'organization'])</div>
                            <div class="col-md-6">@include('site.demandes._field', ['name' => 'phone', 'label' => 'Téléphone', 'type' => 'tel', 'required' => true, 'max' => 30, 'value' => $client?->phone, 'placeholder' => '70 00 00 00', 'autocomplete' => 'tel'])</div>
                            <div class="col-md-6">@include('site.demandes._field', ['name' => 'email', 'label' => 'E-mail (pour le récapitulatif)', 'type' => 'email', 'max' => 150, 'value' => $client?->email, 'autocomplete' => 'email'])</div>

                            <div class="col-12">
                                <fieldset>
                                    <legend class="form-label required">Réception</legend>
                                    <div class="choice-grid">
                                        @foreach (\App\Enums\DeliveryMethod::cases() as $method)
                                            <label class="choice-card">
                                                <input type="radio" name="delivery_method" value="{{ $method->value }}" @checked(old('delivery_method', 'retrait') === $method->value) data-delivery>
                                                <span><strong><i class='bx {{ $method === \App\Enums\DeliveryMethod::Retrait ? 'bx-store' : 'bx-car' }}' aria-hidden="true"></i> {{ $method->label() }}</strong>
                                                    <small>{{ $site->get($method === \App\Enums\DeliveryMethod::Retrait ? 'shop.pickup_text' : 'shop.delivery_text') }}</small></span>
                                            </label>
                                        @endforeach
                                    </div>
                                    @error('delivery_method')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                </fieldset>
                            </div>
                            <div class="col-md-8" data-delivery-fields>@include('site.demandes._field', ['name' => 'address', 'label' => 'Adresse de livraison', 'max' => 300, 'value' => $client?->address, 'placeholder' => 'Quartier, rue, repère…', 'autocomplete' => 'street-address'])</div>
                            <div class="col-md-4" data-delivery-fields>@include('site.demandes._field', ['name' => 'city', 'label' => 'Ville', 'max' => 100, 'value' => $client?->city ?? 'Ségou', 'autocomplete' => 'address-level2'])</div>

                            <div class="col-12">
                                <fieldset>
                                    <legend class="form-label required">Paiement (après confirmation)</legend>
                                    <div class="choice-grid four">
                                        @foreach (\App\Enums\PaymentMethod::cases() as $method)
                                            <label class="choice-card compact">
                                                <input type="radio" name="payment_method" value="{{ $method->value }}" @checked(old('payment_method', 'especes') === $method->value)>
                                                <span><strong>{{ $method->label() }}</strong></span>
                                            </label>
                                        @endforeach
                                    </div>
                                    @error('payment_method')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                </fieldset>
                            </div>
                            <div class="col-12">@include('site.demandes._field', ['name' => 'notes', 'label' => 'Remarques', 'type' => 'textarea', 'max' => 1000, 'rows' => 3, 'placeholder' => 'Horaires de passage, précisions…'])</div>
                            @include('site.demandes._footer', ['submit' => 'Valider ma commande'])
                        </div>
                    </form>
                </div>
                <div class="col-lg-5">
                    <div class="detail-panel cart-summary">
                        <h2 class="h5">Votre commande</h2>
                        <ul class="list-unstyled mb-3">
                            @foreach ($lines as $line)
                                <li class="d-flex justify-content-between gap-3 py-2 border-bottom">
                                    <span>{{ $line['materiel']->designation }} <span class="text-muted">× {{ $line['quantity'] }}</span></span>
                                    <strong class="text-nowrap">{{ \App\Models\Materiel::money($line['line_total']) }}</strong>
                                </li>
                            @endforeach
                        </ul>
                        <div class="d-flex justify-content-between align-items-baseline">
                            <span>Total</span>
                            <strong class="h4 mb-0 text-blue">{{ \App\Models\Materiel::money($total) }}</strong>
                        </div>
                        <a href="{{ route('site.panier') }}" class="small d-inline-block mt-3">Modifier le panier</a>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
