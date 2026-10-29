/* Installer l'application (site public ou administration) : fenêtre [data-pwa-modal] de partials/pwa-install.
   - Enregistre le service worker /sw-push.js (même script que les notifications push, portée « / »).
   - Chrome, Edge, Samsung Internet : l'événement beforeinstallprompt est gardé, le bouton « Installer » l'ouvre.
   - iPhone / iPad : pas d'installation automatique, la fenêtre explique « Partager → Sur l'écran d'accueil ».
   - data-prompt="1" (page qui suit la connexion) : la fenêtre s'ouvre seule ; les éléments [data-pwa-open]
     (cachés au départ) apparaissent quand l'installation est possible et l'ouvrent à la demande.
   Déjà installée (affichage « standalone ») : rien ne s'affiche. */
(function () {
    const modalEl = document.querySelector('[data-pwa-modal]');
    if (!modalEl) return;

    if ('serviceWorker' in navigator && window.isSecureContext) {
        navigator.serviceWorker.register('/sw-push.js').catch(() => {});
    }

    const standalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
    if (standalone) return;

    const ua = navigator.userAgent;
    const ios = /iphone|ipad|ipod/i.test(ua) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
    const mobile = ios || /android|mobile/i.test(ua);
    const installButton = modalEl.querySelector('[data-pwa-install]');
    const openers = document.querySelectorAll('[data-pwa-open]');
    let deferred = null;
    let shown = false;

    // Bootstrap local 5.0.0 : pas de Modal.getOrCreateInstance.
    const modal = () => (window.bootstrap ? (window.bootstrap.Modal.getInstance(modalEl) || new window.bootstrap.Modal(modalEl)) : null);
    const reveal = (visible) => openers.forEach((el) => { el.hidden = !visible; });
    const open = () => {
        const mode = deferred ? 'prompt' : (ios ? 'ios' : 'other');
        installButton.hidden = mode !== 'prompt';
        modalEl.querySelectorAll('[data-pwa-help]').forEach((el) => { el.hidden = el.dataset.pwaHelp !== mode; });
        const instance = modal();
        if (!instance) return;
        shown = true;
        instance.show();
    };

    window.addEventListener('beforeinstallprompt', (event) => {
        event.preventDefault();
        deferred = event;
        reveal(true);
        if (modalEl.dataset.prompt === '1' && !shown) open();
    });

    window.addEventListener('appinstalled', () => {
        deferred = null;
        reveal(false);
        const instance = modal();
        if (instance) instance.hide();
    });

    installButton.addEventListener('click', () => {
        if (!deferred) return;
        const event = deferred;
        deferred = null;
        event.prompt();
        event.userChoice.finally(() => {
            const instance = modal();
            if (instance) instance.hide();
            reveal(false);
        });
    });

    openers.forEach((el) => el.addEventListener('click', (event) => { event.preventDefault(); open(); }));
    if (ios) reveal(true);

    // Après la connexion, sans événement d'installation (iPhone, Firefox…) : explications au téléphone seulement.
    if (modalEl.dataset.prompt === '1') {
        window.setTimeout(() => { if (!deferred && !shown && mobile) open(); }, 2500);
    }
})();
