/* Bouton « Notifications sur cet appareil » ([data-push-toggle] avec data-key, data-store, data-test) : enregistre le
   service worker /sw-push.js, abonne le navigateur avec la clé VAPID et envoie l'abonnement au serveur. */
(function () {
    const box = document.querySelector('[data-push-toggle]');
    if (!box) return;
    const button = box.querySelector('button.js-push-toggle');
    const testButton = box.querySelector('button.js-push-test');
    const status = box.querySelector('.js-push-status');
    const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const say = (text) => { status.textContent = text; };

    if (!('serviceWorker' in navigator) || !('PushManager' in window) || !window.isSecureContext) {
        button.disabled = true;
        say(window.isSecureContext ? 'Ce navigateur ne gère pas les notifications push. Sur iPhone : ajoutez d\'abord le site à l\'écran d\'accueil.' : 'Les notifications exigent une adresse en https.');
        return;
    }

    const toKey = (b64) => {
        const raw = atob((b64 + '='.repeat((4 - (b64.length % 4)) % 4)).replace(/-/g, '+').replace(/_/g, '/'));
        return Uint8Array.from(raw, (c) => c.charCodeAt(0));
    };
    const send = (method, url, body) => fetch(url, {
        method, credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
        body: JSON.stringify(body),
    }).then((r) => r.json().then((json) => { if (!r.ok) throw new Error(json.message || 'Erreur ' + r.status); return json; }));

    let registration = null;
    const render = (subscription) => {
        button.innerHTML = subscription ? "<i class='bx bx-bell-off me-1'></i>Désactiver sur cet appareil" : "<i class='bx bx-bell-plus me-1'></i>Activer les notifications sur cet appareil";
        button.classList.toggle('btn-light', !subscription);
        button.classList.toggle('btn-outline-light', Boolean(subscription));
        if (testButton) testButton.classList.toggle('d-none', !subscription);
        if (!status.dataset.locked) say(subscription ? 'Activées : un nouveau ticket s\'affiche ici même site fermé.' : (Notification.permission === 'denied' ? 'Notifications bloquées dans les réglages du navigateur pour ce site.' : ''));
    };

    navigator.serviceWorker.register('/sw-push.js').then((reg) => {
        registration = reg;
        return reg.pushManager.getSubscription();
    }).then((subscription) => {
        // Abonnement encore présent dans le navigateur : on le renvoie (il peut appartenir à un autre membre de l'équipe).
        if (subscription) send('POST', box.dataset.store, subscription.toJSON()).catch(() => {});
        render(subscription);
    });

    button.addEventListener('click', () => {
        if (!registration) return;
        button.disabled = true;
        registration.pushManager.getSubscription().then((current) => {
            if (current) {
                return send('DELETE', box.dataset.store, { endpoint: current.endpoint }).finally(() => current.unsubscribe()).then(() => render(null));
            }
            return Notification.requestPermission().then((permission) => {
                if (permission !== 'granted') throw new Error('Autorisation refusée : les notifications restent désactivées.');
                return registration.pushManager.subscribe({ userVisibleOnly: true, applicationServerKey: toKey(box.dataset.key) });
            }).then((subscription) => send('POST', box.dataset.store, subscription.toJSON()).then(() => render(subscription)));
        }).catch((e) => say(e.message)).finally(() => { button.disabled = false; });
    });

    if (testButton) {
        testButton.addEventListener('click', () => {
            registration.pushManager.getSubscription().then((current) => current && send('POST', box.dataset.test, { endpoint: current.endpoint }))
                .then((json) => json && say(json.message)).catch((e) => say(e.message));
        });
    }
})();
