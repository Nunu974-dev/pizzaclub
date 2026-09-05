// Service Worker - Pizza Club Admin
// Permet l'installation comme app + fonctionne si app fermée

const CACHE_NAME = 'pizzaclub-admin-v2';
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

// Push notification depuis un push server (Web Push)
self.addEventListener('push', event => {
    let data = { title: '🍕 NOUVELLE COMMANDE !', body: 'Une commande vient d\'arriver !' };
    try {
        if (event.data) data = event.data.json();
    } catch(e) {}

    event.waitUntil(
        self.registration.showNotification(data.title || '🍕 NOUVELLE COMMANDE !', {
            body: data.body || 'Vérifiez le dashboard commandes',
            icon: '/img/favicon.ico',
            badge: '/img/favicon-32x32.png',
            vibrate: [500, 200, 500, 200, 500],
            requireInteraction: true,
            tag: 'nouvelle-commande',
            renotify: true,
            actions: [
                { action: 'open', title: '✅ Voir la commande' }
            ]
        })
    );
});

// Clic sur la notification → ouvre le dashboard
self.addEventListener('notificationclick', event => {
    event.notification.close();
    event.waitUntil(
        self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then(clients => {
            for (const client of clients) {
                if (client.url.includes('orders-log.php') && 'focus' in client) {
                    return client.focus();
                }
            }
            return self.clients.openWindow('/orders-log.php');
        })
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

        // Afficher aussi une notification système
        self.registration.showNotification('🍕 NOUVELLE COMMANDE !', {
            body: event.data.order || 'Nouvelle commande reçue !',
            icon: '/img/favicon.ico',
            vibrate: [500, 200, 500, 200, 500],
            requireInteraction: true,
            tag: 'nouvelle-commande',
            renotify: true,
        });
    }
});
