{{-- Cadre des pages de connexion / inscription de l'espace client. $title, $intro, $slot. --}}
<section class="section client-auth">
    <div class="container">
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
            </div>
        </div>
    </div>
</section>
