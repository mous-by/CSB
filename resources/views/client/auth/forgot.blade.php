@extends('layouts.site')

@section('content')
    @component('client._auth-card', ['title' => 'Mot de passe oublié', 'intro' => 'Recevez par e-mail un lien pour choisir un nouveau mot de passe.'])
        <form method="POST" action="{{ route('client.password.email') }}" novalidate>
            @csrf
            <div class="mb-3">@include('site.demandes._field', ['name' => 'email', 'label' => 'E-mail du compte', 'type' => 'email', 'required' => true, 'autocomplete' => 'email'])</div>
            <button type="submit" class="btn btn-accent w-100">Envoyer le lien</button>
        </form>
        <p class="text-center small mt-4 mb-0"><a href="{{ route('client.login') }}">Retour à la connexion</a></p>
    @endcomponent
@endsection
