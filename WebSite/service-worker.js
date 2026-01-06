/* ================================
   MyClub – Service Worker
   ================================ */

self.addEventListener('install', event => {
    console.log('[SW] Install');
    // Activation immédiate
    self.skipWaiting();
});

self.addEventListener('activate', event => {
    console.log('[SW] Activate');
    event.waitUntil(self.clients.claim());
});

self.addEventListener('push', event => {
    console.log('[SW] Push received', event.data?.text());
});


/* ================================
   PUSH NOTIFICATIONS
   ================================ */

self.addEventListener('push', event => {
    console.log('[SW] Push received...', event.data?.text());

    let payload = {
        title: 'MyClub',
        body: 'Nouvelle notification',
        icon: '/apple-touch-icon.png',
        badge: '/apple-touch-icon.png',
        data: { url: '/' }
    };

    if (event.data) {
        try {
            payload = { ...payload, ...event.data.json() };
        } catch (e) {
            payload.body = event.data.text();
        }
    }

    const options = {
        body: payload.body,
        icon: payload.icon,
        badge: payload.badge,
        data: payload.data
    };

    event.waitUntil(
        self.registration.showNotification(payload.title, options)
    );
});


/* ================================
   CLICK SUR NOTIFICATION
   ================================ */

self.addEventListener('notificationclick', event => {
    console.log('[SW] Notification click');

    event.notification.close();

    const urlToOpen = event.notification.data?.url || '/';

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true })
            .then(clientList => {
                for (const client of clientList) {
                    if (client.url === urlToOpen && 'focus' in client) {
                        return client.focus();
                    }
                }
                if (clients.openWindow) {
                    return clients.openWindow(urlToOpen);
                }
            })
    );
});
