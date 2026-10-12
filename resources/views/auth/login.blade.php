@extends('layouts.guest')

@section('title', 'Connexion')

{{-- Écran partagé en deux : l'affiche de Computer Service Barry à gauche, le formulaire à droite (demande de CSB, 2026-10-11). --}}
@section('page_class', 'login-page--right')

@section('background')
    <div class="bg-aurora"></div>
    <div class="csb-login-scene" aria-hidden="true">
        <span class="csb-glow csb-glow--a"></span>
        <span class="csb-glow csb-glow--b"></span>
    </div>
    <div class="csb-login-brand">
        {{-- Affiche officielle de CSB (logo, services, coordonnées), fournie le 2026-10-11. --}}
        <div class="csb-login-poster">
            <img src="{{ asset('assets/site/img/csb-affiche.jpg') }}" alt="Computer Service Barry — vente d'ordinateurs, réparation et maintenance, développement de logiciels web et mobile, solutions informatiques" width="1280" height="853">
        </div>
    </div>
@endsection

@push('styles')
    <style>
        /* Écran partagé en deux (demande de CSB) : à gauche l'affiche sur fond blanc, à droite le formulaire sur fond bleu. */
        .login-page--right { justify-content: center; margin-left: 50%; width: 50%; }
        .csb-login-scene { position: fixed; top: 0; bottom: 0; right: 0; width: 50%; z-index: 0; overflow: hidden; background: radial-gradient(circle at 60% 40%, #1d5bc4 0%, #103b8e 50%, #0a2560 100%); }
        .csb-glow { position: absolute; border-radius: 50%; filter: blur(70px); opacity: .55; animation: csbFloat 12s ease-in-out infinite alternate; }
        .csb-glow--a { width: 380px; height: 380px; left: 10%; top: 12%; background: #2f7bff; }
        .csb-glow--b { width: 300px; height: 300px; right: 5%; bottom: 10%; background: #f58220; opacity: .35; animation-delay: -6s; }
        @keyframes csbFloat { from { transform: translate(0, 0) scale(1); } to { transform: translate(30px, -25px) scale(1.1); } }
        .csb-login-brand { position: fixed; z-index: 1; left: 0; top: 0; bottom: 0; width: 50%; display: flex; align-items: center; justify-content: center; padding: 2rem; background: #fff; box-shadow: 12px 0 40px rgba(10, 37, 96, .25); pointer-events: none; }
        .csb-login-poster { width: 100%; animation: csbLogoIn .9s cubic-bezier(.2, .8, .2, 1) both; }
        .csb-login-poster img { display: block; width: 100%; height: auto; max-height: calc(100vh - 4rem); object-fit: contain; }
        .module-wave { left: 50%; }
        @keyframes csbLogoIn { from { opacity: 0; transform: translateY(24px) scale(.9); } to { opacity: 1; transform: none; } }
        @media (max-width: 900px) {
            .csb-login-brand { display: none; }
            .csb-login-scene { width: 100%; }
            .login-page--right { margin-left: 0; width: 100%; }
            .module-wave { left: 0; }
        }
        @media (prefers-reduced-motion: reduce) {
            .csb-glow, .csb-login-poster, .bg-aurora { animation: none; }
        }
    </style>
@endpush

@section('content')
    @if ($errors->any())
        <div class="alert alert-danger py-2">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <div class="form-divider"><span>Connexion</span></div>

    <form method="POST" action="{{ route('login.store') }}" id="loginForm">
        @csrf

        <div class="mb-3">
            <label for="phone" class="form-label">Numéro de téléphone</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bx bx-phone"></i></span>
                <input type="tel" id="phone" name="phone" value="{{ old('phone') }}"
                       class="form-control @error('phone') is-invalid @enderror"
                       inputmode="tel" autocomplete="username" placeholder="70 00 00 00" autofocus required>
            </div>
            <div class="form-text">Indicatif +223 ajouté automatiquement pour un numéro de 8 chiffres.</div>
        </div>

        <div class="mb-4">
            <label for="password" class="form-label">Mot de passe</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bx bx-lock-alt"></i></span>
                <input type="password" id="password" name="password"
                       class="form-control @error('password') is-invalid @enderror"
                       autocomplete="current-password" required>
                <button type="button" class="btn password-toggle" id="togglePassword" aria-label="Afficher le mot de passe">
                    <i class="bx bx-show"></i>
                </button>
            </div>
        </div>

        <div class="d-grid">
            <button type="submit" class="btn login-button" id="loginButton">
                <i class="bx bx-log-in-circle"></i> Connexion
            </button>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        document.getElementById('loginForm').addEventListener('submit', function () {
            const btn = document.getElementById('loginButton');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Connexion en cours…';
        });

        document.getElementById('togglePassword').addEventListener('click', function () {
            const input = document.getElementById('password');
            const icon = this.querySelector('i');
            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            icon.classList.toggle('bx-show', !isPassword);
            icon.classList.toggle('bx-hide', isPassword);
        });
    </script>
@endpush
