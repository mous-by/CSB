{{-- Identité et moyens de réponse (cahier §6.2 : au moins un moyen de réponse obligatoire), préremplis pour un client
     connecté à son espace. --}}
@php
    $me = auth('client')->user();
@endphp
<div class="col-md-6">@include('site.demandes._field', ['name' => 'name', 'label' => 'Nom complet', 'required' => true, 'max' => 150, 'autocomplete' => 'name', 'value' => $me?->name])</div>
<div class="col-md-6">@include('site.demandes._field', ['name' => 'organization', 'label' => 'Organisation ou entreprise', 'max' => 150, 'autocomplete' => 'organization', 'value' => $me?->organization])</div>
<div class="col-md-6">@include('site.demandes._field', ['name' => 'email', 'label' => 'E-mail', 'type' => 'email', 'max' => 190, 'autocomplete' => 'email', 'placeholder' => 'vous@exemple.com', 'value' => $me?->email])</div>
<div class="col-md-6">@include('site.demandes._field', ['name' => 'phone', 'label' => 'Téléphone', 'type' => 'tel', 'max' => 30, 'autocomplete' => 'tel', 'placeholder' => '70 00 00 00', 'value' => $me?->phone])</div>
<div class="col-12"><p class="form-text mt-0"><i class='bx bx-info-circle' aria-hidden="true"></i> Indiquez au moins un moyen de vous répondre : e-mail ou téléphone.@if (! $me && Route::has('client.login')) Vous avez un espace client ? <a href="{{ route('client.login') }}">Connectez-vous</a> pour suivre votre demande.@endif</p></div>
