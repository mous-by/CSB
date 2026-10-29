{{-- Fenêtre « Installer l'application » (public/assets/js/pwa-install.js). $app : « site » ou « team ».
     Elle s'ouvre seule à la page qui suit la connexion (clé de session `pwa_prompt` posée par l'événement Login,
     AppServiceProvider) ; tout élément [data-pwa-open] l'ouvre ensuite à la demande (pied de page, menu du profil). --}}
@php
    $pwaPrompt = session('pwa_prompt') === $app;
    if ($pwaPrompt) {
        session()->forget('pwa_prompt');
    }
    $pwaTitle = $app === 'team' ? 'Installer l\'administration CSB' : 'Installer l\'application CSB';
    $pwaText = $app === 'team'
        ? 'Ouvrez l\'administration en un geste depuis l\'écran d\'accueil de votre téléphone, en plein écran, comme une application. Activez ensuite les notifications pour être prévenu des nouveaux tickets.'
        : 'Ajoutez CSB à l\'écran d\'accueil de votre téléphone : vos demandes, commandes et tickets en un geste, comme une application.';
@endphp
<div class="modal fade" id="pwa-install" tabindex="-1" aria-labelledby="pwa-install-title" aria-hidden="true"
     data-pwa-modal data-prompt="{{ $pwaPrompt ? '1' : '0' }}">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 400px;">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 18px; overflow: hidden;">
            <div class="text-center px-4 pt-4" style="background: linear-gradient(180deg, #eef3ff, #fff);">
                <img src="{{ asset($app === 'team' ? 'assets/pwa/admin-icon-192.png' : 'assets/pwa/icon-192.png') }}" alt="" width="84" height="84" style="border-radius: 20px; box-shadow: 0 10px 24px rgba(16, 59, 142, .2);">
                <h2 class="h5 fw-bold mt-3 mb-2" id="pwa-install-title" style="color: #14213d;">{{ $pwaTitle }}</h2>
            </div>
            <div class="modal-body px-4 pt-1 text-center" style="color: #4a5568;">
                <p class="mb-3">{{ $pwaText }}</p>
                <div class="text-start small rounded-3 p-3" style="background: #f4f7fc;" data-pwa-help="ios" hidden>
                    <strong class="d-block mb-1" style="color: #14213d;">Sur iPhone / iPad (Safari) :</strong>
                    1. Touchez le bouton <strong>Partager</strong> <i class='bx bx-export' aria-hidden="true"></i> en bas de l'écran ;<br>
                    2. choisissez <strong>« Sur l'écran d'accueil »</strong> puis <strong>Ajouter</strong>.
                </div>
                <div class="text-start small rounded-3 p-3" style="background: #f4f7fc;" data-pwa-help="other" hidden>
                    <strong class="d-block mb-1" style="color: #14213d;">Dans votre navigateur :</strong>
                    ouvrez le menu <strong>⋮</strong> puis <strong>« Installer l'application »</strong> ou <strong>« Ajouter à l'écran d'accueil »</strong>.
                </div>
            </div>
            <div class="modal-footer border-0 px-4 pb-4 pt-0 d-grid gap-2">
                <button type="button" class="btn btn-lg fw-bold text-white" style="background: #103b8e; border-radius: 12px;" data-pwa-install hidden>
                    <i class='bx bx-download me-1' aria-hidden="true"></i>Installer
                </button>
                <button type="button" class="btn btn-link text-decoration-none" style="color: #4a5568;" data-bs-dismiss="modal">Plus tard</button>
            </div>
        </div>
    </div>
</div>
<script src="{{ asset('assets/js/pwa-install.js') }}?v={{ filemtime(public_path('assets/js/pwa-install.js')) }}" defer></script>
