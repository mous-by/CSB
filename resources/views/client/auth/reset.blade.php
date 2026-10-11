@extends('layouts.site')

@section('content')
    @component('client._auth-card', ['title' => 'Nouveau mot de passe'])
        <form method="POST" action="{{ route('client.password.update') }}" novalidate>
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <div class="mb-3">@include('site.demandes._field', ['name' => 'email', 'label' => 'E-mail du compte', 'type' => 'email', 'required' => true, 'value' => $email])</div>
            <div class="mb-3">
                <label for="f-password" class="form-label required">Nouveau mot de passe</label>
                <input type="password" class="form-control @error('password') is-invalid @enderror" id="f-password" name="password" required autocomplete="new-password">
                @error('password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label for="f-password-confirm" class="form-label required">Confirmation</label>
                <input type="password" class="form-control" id="f-password-confirm" name="password_confirmation" required autocomplete="new-password">
            </div>
            <button type="submit" class="btn btn-accent w-100">Enregistrer</button>
        </form>
    @endcomponent
@endsection
