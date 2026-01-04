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

/* ================================
   PUSH NOTIFICATIONS
   ================================ */

self.addEventListener('push', event => {
    console.log('[SW] Push received');

    let data = {
        title: 'MyClub',
        body: 'Nouvelle notification',
        icon: '/apple-touch-icon.png',
        badge: '/apple-touch-icon.png',
        url: '/'
    };

    if (event.data) {
        try {
            data = { ...data, ...event.data.json() };
        } catch (e) {
            console.warn('[SW] Push data is not JSON');
            data.body = event.data.text();
        }
    }

    const options = {
        body: data.body,
        icon: data.icon,
        badge: data.badge,
        data: {
            url: data.url
        }
    };

    event.waitUntil(
        self.registration.showNotification(data.title, options)
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
