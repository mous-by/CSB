{{-- Cadre des pages de connexion / inscription de l'espace client. $title, $intro, $slot. En fond, les réalisations
     et matériels de CSB défilent (site.partials.showcase) ; sans contenu illustré, un simple dégradé aux couleurs de CSB. --}}
@php
    $showcaseHtml = trim(view('site.partials.showcase')->render());
@endphp
<section class="section showcase-stage client-auth {{ $showcaseHtml === '' ? '' : 'has-showcase' }}">
    {!! $showcaseHtml !!}
    <div class="container position-relative">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6 col-xl-5">
                <div class="form-card">
                    <div class="text-center mb-4">
                        <span class="icon-box mb-2"><i class='bx bx-user-circle' aria-hidden="true"></i></span>
                        <h1 class="h3 mb-1">{{ $title }}</h1>
                        @if (! empty($intro))<p class="text-muted mb-0">{{ $intro }}</p>@endif
                    </div>
                    @if (session('status'))
                        <div class="alert alert-success small" role="status">{{ session('status') }}</div>
                    @endif
                    {{ $slot }}
                </div>
                @if ($showcaseHtml !== '')
                    <p class="auth-showcase-note">En fond : quelques réalisations et matériels de CSB.@if (Route::has('site.realisations.index')) <a href="{{ route('site.realisations.index') }}">Les découvrir</a>@endif</p>
                @endif
            </div>
        </div>
    </div>
</section>
