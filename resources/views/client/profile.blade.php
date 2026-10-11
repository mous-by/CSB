@extends('layouts.site')

@section('content')
    @component('client._layout', ['title' => 'Mon profil'])
        <div class="detail-panel">
            <h2 class="h5">Mes coordonnées</h2>
            <form method="POST" action="{{ route('client.profile.update') }}" novalidate>
                @csrf
                @method('PUT')
                <div class="row g-3">
                    <div class="col-md-6">@include('site.demandes._field', ['name' => 'name', 'label' => 'Nom complet', 'required' => true, 'value' => $client->name])</div>
                    <div class="col-md-6">@include('site.demandes._field', ['name' => 'organization', 'label' => 'Organisation', 'value' => $client->organization])</div>
                    <div class="col-md-6">
                        <label class="form-label" for="f-email-ro">E-mail</label>
                        <input type="email" id="f-email-ro" class="form-control" value="{{ $client->email }}" disabled>
                        <div class="form-text">Pour changer d'adresse, contactez CSB.</div>
                    </div>
                    <div class="col-md-6">@include('site.demandes._field', ['name' => 'phone', 'label' => 'Téléphone', 'type' => 'tel', 'value' => $client->phone])</div>
                    <div class="col-md-8">@include('site.demandes._field', ['name' => 'address', 'label' => 'Adresse (livraison)', 'max' => 300, 'value' => $client->address])</div>
                    <div class="col-md-4">@include('site.demandes._field', ['name' => 'city', 'label' => 'Ville', 'max' => 100, 'value' => $client->city])</div>
                    <div class="col-12"><button type="submit" class="btn btn-primary">Enregistrer</button></div>
                </div>
            </form>
        </div>
        <div class="detail-panel">
            <h2 class="h5">Mot de passe</h2>
            <form method="POST" action="{{ route('client.password.change') }}" novalidate>
                @csrf
                @method('PUT')
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="current_password" class="form-label required">Actuel</label>
                        <input type="password" class="form-control @error('current_password') is-invalid @enderror" id="current_password" name="current_password" autocomplete="current-password">
                        @error('current_password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label for="new_password" class="form-label required">Nouveau</label>
                        <input type="password" class="form-control @error('password') is-invalid @enderror" id="new_password" name="password" autocomplete="new-password">
                        @error('password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label for="new_password_confirmation" class="form-label required">Confirmation</label>
                        <input type="password" class="form-control" id="new_password_confirmation" name="password_confirmation" autocomplete="new-password">
                    </div>
                    <div class="col-12"><button type="submit" class="btn btn-outline-primary">Changer le mot de passe</button></div>
                </div>
            </form>
        </div>
    @endcomponent
@endsection
