/* Service worker des notifications push de l'équipe CSB : affiche la notification même site fermé, et ouvre la page
   du ticket au clic (onglet existant réutilisé). */
self.addEventListener('push', (event) => {
    let data = {};
    try { data = event.data ? event.data.json() : {}; } catch (e) { data = { body: event.data ? event.data.text() : '' }; }
    event.waitUntil(self.registration.showNotification(data.title || 'CSB', {
        body: data.body || 'Nouvelle activité sur le site CSB.',
        icon: '/assets/site/img/logo-csb.png',
        badge: '/assets/site/img/logo-csb.png',
        tag: data.tag || undefined,
        renotify: Boolean(data.tag),
        requireInteraction: true,
        data: { url: data.url || '/' },
    }));
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    const url = new URL(event.notification.data.url, self.location.origin).href;
    event.waitUntil(self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((windows) => {
        for (const win of windows) {
            if (win.url === url && 'focus' in win) return win.focus();
        }
        return self.clients.openWindow(url);
    }));
});
