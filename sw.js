// Service Worker - Pizza Club Admin
// Permet l'installation comme app + fonctionne si app fermée

const CACHE_NAME = 'pizzaclub-admin-v1';
const CACHE_URLS = ['/orders-log.php'];

// Installation
self.addEventListener('install', event => {
    self.skipWaiting();
});

// Activation
self.addEventListener('activate', event => {
    event.waitUntil(self.clients.claim());
});

// Fetch - réseau en priorité, cache en fallback
self.addEventListener('fetch', event => {
    if (event.request.method !== 'GET') return;
    event.respondWith(
        fetch(event.request).catch(() => caches.match(event.request))
    );
});

// Message depuis la page (nouvelle commande détectée par polling)
self.addEventListener('message', event => {
    if (event.data && event.data.type === 'NEW_ORDER') {
        // Envoyer l'alarme à toutes les fenêtres ouvertes
        self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then(clients => {
            clients.forEach(client => {
                client.postMessage({ type: 'PLAY_ALARM', data: event.data.order });
            });
        });
    }
});
