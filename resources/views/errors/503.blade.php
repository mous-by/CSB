{{-- Page d'erreur 503 : au gabarit de l'administration sous /{admin}, au gabarit du site public ailleurs. --}}
@php
    $inAdmin = request()->is(config('site.admin_path'), config('site.admin_path').'/*');
    $seo = \App\Support\Seo::make("Service indisponible")->noIndex();
@endphp
@extends($inAdmin ? 'layouts.guest' : 'layouts.site')

@section('title', "Service indisponible")

@section('content')
    @if ($inAdmin)
    <div class="text-center py-3">
        <div class="display-4 fw-bold text-primary">503</div>
        <h5 class="mt-2">Service indisponible</h5>
        <p class="text-muted">La plateforme est en maintenance. Revenez dans quelques instants.</p>
        <a href="{{ Route::has('home') && auth()->check() ? route('home') : route('login') }}" class="btn login-button px-4"><i class="bx bx-home-alt"></i> Retour à l'administration</a>
    </div>
    @else
        @include('errors._site', ['code' => '503', 'title' => "Service indisponible", 'message' => "La plateforme est en maintenance. Revenez dans quelques instants."])
    @endif
@endsection
