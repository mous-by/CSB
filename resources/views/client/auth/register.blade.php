@extends('layouts.site')

@section('content')
    @component('client._auth-card', ['title' => 'Créer mon espace client', 'intro' => 'Gratuit : vos demandes, devis, factures et tickets au même endroit.'])
        <form method="POST" action="{{ route('client.register.store') }}" novalidate>
            @csrf
            <div class="row g-3">
                <div class="col-12">@include('site.demandes._field', ['name' => 'name', 'label' => 'Nom complet', 'required' => true, 'max' => 150, 'autocomplete' => 'name'])</div>
                <div class="col-12">@include('site.demandes._field', ['name' => 'organization', 'label' => 'Organisation ou entreprise', 'max' => 150, 'autocomplete' => 'organization'])</div>
                <div class="col-md-6">@include('site.demandes._field', ['name' => 'email', 'label' => 'E-mail', 'type' => 'email', 'required' => true, 'autocomplete' => 'email'])</div>
                <div class="col-md-6">@include('site.demandes._field', ['name' => 'phone', 'label' => 'Téléphone', 'type' => 'tel', 'max' => 30, 'autocomplete' => 'tel'])</div>
                <div class="col-md-6">
                    <label for="f-password" class="form-label required">Mot de passe</label>
                    <input type="password" class="form-control @error('password') is-invalid @enderror" id="f-password" name="password" required autocomplete="new-password" aria-describedby="f-password-help">
                    @error('password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    <div class="form-text" id="f-password-help">4 caractères au moins (ex. 1234).</div>
                </div>
                <div class="col-md-6">
                    <label for="f-password-confirm" class="form-label required">Confirmation</label>
                    <input type="password" class="form-control" id="f-password-confirm" name="password_confirmation" required autocomplete="new-password">
                </div>
                <div class="col-12">
                    <div class="form-check">
                        <input class="form-check-input @error('consent') is-invalid @enderror" type="checkbox" id="f-consent" name="consent" value="1" @checked(old('consent'))>
                        <label class="form-check-label small" for="f-consent">J'accepte la <a href="{{ route('site.privacy') }}" target="_blank" rel="noopener">politique de confidentialité</a>.</label>
                    </div>
                    @error('consent')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <div class="col-12"><button type="submit" class="btn btn-accent w-100 btn-lg">Créer mon compte</button></div>
            </div>
        </form>
        <p class="text-center small mt-4 mb-0">Déjà un compte ? <a href="{{ route('client.login') }}" class="fw-bold">Se connecter</a></p>
    @endcomponent
@endsection
