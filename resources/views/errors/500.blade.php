{{-- Page d'erreur 500 : au gabarit de l'administration sous /{admin}, au gabarit du site public ailleurs. --}}
@php
    $inAdmin = request()->is(config('site.admin_path'), config('site.admin_path').'/*');
    $seo = \App\Support\Seo::make("Erreur du serveur")->noIndex();
@endphp
@extends($inAdmin ? 'layouts.guest' : 'layouts.site')

@section('title', "Erreur du serveur")

@section('content')
    @if ($inAdmin)
    <div class="text-center py-3">
        <div class="display-4 fw-bold text-primary">500</div>
        <h5 class="mt-2">Erreur du serveur</h5>
        <p class="text-muted">Une erreur est survenue. Elle a été enregistrée.</p>
        <a href="{{ Route::has('home') && auth()->check() ? route('home') : route('login') }}" class="btn login-button px-4"><i class="bx bx-home-alt"></i> Retour à l'administration</a>
    </div>
    @else
        @include('errors._site', ['code' => '500', 'title' => "Erreur du serveur", 'message' => "Une erreur est survenue. Elle a été enregistrée."])
    @endif
@endsection
