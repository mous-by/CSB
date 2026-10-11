{{-- Page d'erreur 419 : au gabarit de l'administration sous /{admin}, au gabarit du site public ailleurs. --}}
@php
    $inAdmin = request()->is(config('site.admin_path'), config('site.admin_path').'/*');
    $seo = \App\Support\Seo::make("Session expirée")->noIndex();
@endphp
@extends($inAdmin ? 'layouts.guest' : 'layouts.site')

@section('title', "Session expirée")

@section('content')
    @if ($inAdmin)
    <div class="text-center py-3">
        <div class="display-4 fw-bold text-primary">419</div>
        <h5 class="mt-2">Session expirée</h5>
        <p class="text-muted">Votre session a expiré. Rechargez la page et réessayez.</p>
        <a href="{{ Route::has('home') && auth()->check() ? route('home') : route('login') }}" class="btn login-button px-4"><i class="bx bx-home-alt"></i> Retour à l'administration</a>
    </div>
    @else
        @include('errors._site', ['code' => '419', 'title' => "Session expirée", 'message' => "Votre session a expiré. Rechargez la page et réessayez."])
    @endif
@endsection
