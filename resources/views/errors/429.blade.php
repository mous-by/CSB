{{-- Page d'erreur 429 : au gabarit de l'administration sous /{admin}, au gabarit du site public ailleurs. --}}
@php
    $inAdmin = request()->is(config('site.admin_path'), config('site.admin_path').'/*');
    $seo = \App\Support\Seo::make("Trop de requêtes")->noIndex();
@endphp
@extends($inAdmin ? 'layouts.guest' : 'layouts.site')

@section('title', "Trop de requêtes")

@section('content')
    @if ($inAdmin)
    <div class="text-center py-3">
        <div class="display-4 fw-bold text-primary">429</div>
        <h5 class="mt-2">Trop de requêtes</h5>
        <p class="text-muted">Veuillez patienter quelques instants avant de réessayer.</p>
        <a href="{{ Route::has('home') && auth()->check() ? route('home') : route('login') }}" class="btn login-button px-4"><i class="bx bx-home-alt"></i> Retour à l'administration</a>
    </div>
    @else
        @include('errors._site', ['code' => '429', 'title' => "Trop de requêtes", 'message' => "Veuillez patienter quelques instants avant de réessayer."])
    @endif
@endsection
