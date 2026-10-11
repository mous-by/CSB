@extends('layouts.guest')

@section('title', 'Connexion')

{{-- Formulaire à droite ; à gauche, le logo de Computer Service Barry sur fond bleu (demande de CSB, 2026-10-11 : pas de diaporama). --}}
@section('page_class', 'login-page--right')

@section('background')
    <div class="bg-aurora"></div>
    <div class="csb-login-scene" aria-hidden="true">
        <span class="csb-glow csb-glow--a"></span>
        <span class="csb-glow csb-glow--b"></span>
    </div>
    <div class="csb-login-brand">
        <div class="csb-login-logo">
            <img src="{{ asset('assets/site/img/logo-csb.png') }}" alt="Computer Service Barry" width="260" height="198">
        </div>
        <p class="csb-login-name">COMPUTER SERVICE <span>BARRY</span></p>
        <p class="csb-login-slogan">Qualité • Fiabilité • Performance</p>
    </div>
@endsection

@push('styles')
    <style>
        .login-page--right { justify-content: flex-end; }
        .csb-login-scene { position: fixed; inset: 0; z-index: 0; overflow: hidden; background: radial-gradient(circle at 25% 45%, #1d5bc4 0%, #103b8e 45%, #0a2560 100%); }
        .csb-glow { position: absolute; border-radius: 50%; filter: blur(70px); opacity: .55; animation: csbFloat 12s ease-in-out infinite alternate; }
        .csb-glow--a { width: 420px; height: 420px; left: 8%; top: 18%; background: #2f7bff; }
        .csb-glow--b { width: 320px; height: 320px; left: 30%; bottom: 6%; background: #f58220; opacity: .35; animation-delay: -6s; }
        @keyframes csbFloat { from { transform: translate(0, 0) scale(1); } to { transform: translate(40px, -30px) scale(1.12); } }
        .csb-login-brand { position: fixed; z-index: 1; left: 0; top: 0; bottom: 0; width: calc(100% - 480px); display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; color: #fff; pointer-events: none; }
        .csb-login-logo { background: #fff; border-radius: 32px; padding: 28px 34px; box-shadow: 0 30px 80px rgba(0, 0, 0, .35), 0 0 0 10px rgba(255, 255, 255, .08); animation: csbLogoIn .9s cubic-bezier(.2, .8, .2, 1) both, csbBreath 6s ease-in-out 1s infinite; }
        .csb-login-logo img { display: block; width: clamp(160px, 18vw, 260px); height: auto; }
        @keyframes csbLogoIn { from { opacity: 0; transform: translateY(24px) scale(.9); } to { opacity: 1; transform: none; } }
        @keyframes csbBreath { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-8px); } }
        .csb-login-name { margin: 1.6rem 0 .3rem; font-weight: 800; font-size: clamp(1.2rem, 2.2vw, 2rem); letter-spacing: .04em; text-shadow: 0 2px 14px rgba(0, 0, 0, .35); }
        .csb-login-name span { color: #f58220; }
        .csb-login-slogan { margin: 0; color: rgba(255, 255, 255, .85); font-size: clamp(.85rem, 1.1vw, 1.05rem); letter-spacing: .08em; }
        @media (max-width: 900px) {
            .csb-login-brand { display: none; }
            .login-page--right { justify-content: center; }
        }
        @media (prefers-reduced-motion: reduce) {
            .csb-glow, .csb-login-logo, .bg-aurora { animation: none; }
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
