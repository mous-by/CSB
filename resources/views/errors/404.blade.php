{{-- Page d'erreur 404 : au gabarit de l'administration sous /{admin}, au gabarit du site public ailleurs. --}}
@php
    $inAdmin = request()->is(config('site.admin_path'), config('site.admin_path').'/*');
    $seo = \App\Support\Seo::make("Page introuvable")->noIndex();
@endphp
@extends($inAdmin ? 'layouts.guest' : 'layouts.site')

@section('title', "Page introuvable")

@section('content')
    @if ($inAdmin)
    <div class="text-center py-3">
        <div class="display-4 fw-bold text-primary">404</div>
        <h5 class="mt-2">Page introuvable</h5>
        <p class="text-muted">La page demandée n'existe pas.</p>
        <a href="{{ Route::has('home') && auth()->check() ? route('home') : route('login') }}" class="btn login-button px-4"><i class="bx bx-home-alt"></i> Retour à l'administration</a>
    </div>
    @else
        @include('errors._site', ['code' => '404', 'title' => "Page introuvable", 'message' => "La page demandée n'existe pas."])
    @endif
@endsection
