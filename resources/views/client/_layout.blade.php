{{-- Cadre des pages de l'espace client : en-tête, menu de l'espace, contenu. $title, $slot. --}}
@php
    $client = auth('client')->user();
    $nav = collect([
        ['client.home', 'Tableau de bord', 'bx-grid-alt'],
        ['client.demandes', 'Mes demandes', 'bx-envelope'],
        ['client.commandes', 'Mes commandes', 'bx-cart'],
        ['client.tickets', 'Mes tickets', 'bx-support'],
        ['client.documents', 'Mes documents', 'bx-folder'],
        ['client.profile', 'Mon profil', 'bx-user'],
    ])->filter(fn ($item) => Route::has($item[0]));
@endphp
<section class="page-hero client-hero">
    <div class="container">
        <p class="mb-1 text-white-75">Espace client</p>
        <h1>{{ $title }}</h1>
        <p>{{ $client->name }}@if ($client->organization) · {{ $client->organization }}@endif</p>
    </div>
</section>
<section class="section pt-4">
    <div class="container">
        @if (session('status'))
            <div class="alert alert-success" role="status">{{ session('status') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger" role="alert">{{ session('error') }}</div>
        @endif
        @if (! $client->hasVerifiedEmail())
            <div class="alert alert-warning d-flex flex-wrap align-items-center gap-2">
                <i class='bx bx-envelope fs-4' aria-hidden="true"></i>
                <span class="flex-grow-1">Confirmez votre adresse <strong>{{ $client->email }}</strong> (lien envoyé par e-mail) pour retrouver vos demandes antérieures.</span>
                <form method="POST" action="{{ route('client.verification.send') }}">@csrf<button type="submit" class="btn btn-sm btn-outline-dark">Renvoyer le lien</button></form>
            </div>
        @endif
        <div class="row g-4">
            <aside class="col-lg-3">
                <nav class="client-nav" aria-label="Espace client">
                    @foreach ($nav as [$route, $label, $icon])
                        <a href="{{ route($route) }}" class="{{ request()->routeIs($route.'*') ? 'active' : '' }}" @if (request()->routeIs($route.'*')) aria-current="page" @endif><i class='bx {{ $icon }}' aria-hidden="true"></i>{{ $label }}</a>
                    @endforeach
                    <form method="POST" action="{{ route('client.logout') }}">
                        @csrf
                        <button type="submit"><i class='bx bx-log-out' aria-hidden="true"></i>Déconnexion</button>
                    </form>
                </nav>
            </aside>
            <div class="col-lg-9">
                {{ $slot }}
            </div>
        </div>
    </div>
</section>
