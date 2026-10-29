{{-- Gabarit du site public (cahier §4, §8, §11) : distinct de l'administration (layouts.admin). Aucune ressource externe
     (D15) : Bootstrap, Boxicons et la police Inter sont servis localement. $seo : App\Support\Seo (titre, description,
     canonique, partage) ; $site : App\Support\SiteSettings (coordonnées, slogan…), fourni par AppServiceProvider. --}}
@php
    $seo = $seo ?? \App\Support\Seo::make();
    $whatsappMessage = trim($__env->yieldContent('whatsapp_message')) ?: 'Bonjour CSB, je vous contacte depuis votre site ('.$seo->title.').';
    $whatsappLink = $site->whatsappLink($whatsappMessage);
    $organization = \App\Support\Seo::organization();
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>{{ $seo->title }}</title>
    <meta name="description" content="{{ $seo->description }}">
    <link rel="canonical" href="{{ $seo->canonical }}">
    @unless ($seo->index)
        <meta name="robots" content="noindex, follow">
    @endunless
    @if ($site->get('seo.google_site_verification'))
        <meta name="google-site-verification" content="{{ $site->get('seo.google_site_verification') }}">
    @endif
    <meta name="theme-color" content="#103b8e">
    @include('partials.pwa-head', ['app' => 'site'])

    {{-- Partage (Open Graph : Facebook, WhatsApp, LinkedIn) --}}
    <meta property="og:site_name" content="{{ $site->get('company.name') }}">
    <meta property="og:locale" content="fr_FR">
    <meta property="og:type" content="{{ $seo->type }}">
    <meta property="og:title" content="{{ $seo->title }}">
    <meta property="og:description" content="{{ $seo->description }}">
    <meta property="og:url" content="{{ $seo->canonical }}">
    @if ($seo->image)
        <meta property="og:image" content="{{ $seo->image }}">
        <meta name="twitter:card" content="summary_large_image">
    @else
        <meta name="twitter:card" content="summary">
    @endif

    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link href="{{ asset('assets/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/plugins/boxicons/css/boxicons.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/fonts.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/site/css/site.css') }}?v={{ filemtime(public_path('assets/site/css/site.css')) }}" rel="stylesheet">
    <link href="{{ asset('assets/site/css/site-fx.css') }}?v={{ filemtime(public_path('assets/site/css/site-fx.css')) }}" rel="stylesheet">
    {{-- Classe « js » posée avant l'affichage : les effets ne s'appliquent que si JavaScript est actif (pas de flash). --}}
    <script>document.documentElement.classList.add('js');</script>

    <script type="application/ld+json">{!! json_encode($organization, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
    @foreach ($seo->jsonLd as $data)
        <script type="application/ld+json">{!! json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
    @endforeach
    @stack('styles')
</head>
<body>
    <a href="#contenu" class="skip-link">Aller au contenu</a>

    @include('site.partials.header')

    <main id="contenu" tabindex="-1">
        @yield('content')
    </main>

    @include('site.partials.footer')

    @if ($whatsappLink)
        <a href="{{ $whatsappLink }}" class="whatsapp-float" target="_blank" rel="noopener" aria-label="Écrire à CSB sur WhatsApp" data-track="whatsapp">
            <i class='bx bxl-whatsapp' aria-hidden="true"></i>
        </a>
    @endif
    @if ($site->telLink('contact.phone_1'))
        <a href="{{ $site->telLink('contact.phone_1') }}" class="call-float" aria-label="Appeler CSB au {{ $site->get('contact.phone_1') }}">
            <i class='bx bx-phone' aria-hidden="true"></i>
        </a>
    @endif

    <script src="{{ asset('assets/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('assets/js/submit-guard.js') }}?v={{ filemtime(public_path('assets/js/submit-guard.js')) }}"></script>
    <script src="{{ asset('assets/site/js/site.js') }}?v={{ filemtime(public_path('assets/site/js/site.js')) }}"></script>
    @include('partials.password-toggle')
    @include('partials.pwa-install', ['app' => 'site'])
    @stack('scripts')
</body>
</html>
