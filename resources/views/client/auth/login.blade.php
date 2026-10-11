@extends('layouts.site')

@section('content')
    @component('client._auth-card', ['title' => 'Mon espace client', 'intro' => 'Suivez vos demandes, commandes, tickets et documents.'])
        <form method="POST" action="{{ route('client.login.store') }}" novalidate>
            @csrf
            <div class="mb-3">
                @include('site.demandes._field', ['name' => 'login', 'label' => 'E-mail ou téléphone', 'required' => true, 'autocomplete' => 'username'])
            </div>
            <div class="mb-3">
                <label for="f-password" class="form-label required">Mot de passe</label>
                <input type="password" class="form-control" id="f-password" name="password" required autocomplete="current-password">
            </div>
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="remember" name="remember" value="1">
                    <label class="form-check-label small" for="remember">Rester connecté</label>
                </div>
                <a href="{{ route('client.password.request') }}" class="small">Mot de passe oublié ?</a>
            </div>
            <button type="submit" class="btn btn-accent w-100 btn-lg">Se connecter</button>
        </form>
        <p class="text-center small mt-4 mb-0">Pas encore de compte ? <a href="{{ route('client.register') }}" class="fw-bold">Créer mon espace client</a></p>
    @endcomponent
@endsection
