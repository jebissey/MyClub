self.addEventListener('push', event => {
    if (!event.data) {
        console.error('[SW] No payload received');
        return;
    }

    let incoming;

    try {
        incoming = event.data.json();
    } catch (e) {
        console.error('[SW] Invalid JSON payload');
        return;
    }

    if (!incoming.title || !incoming.body) {
        console.error('[SW] Invalid payload structure', incoming);
        return;
    }

    const payload = {
        title: incoming.title,
        body: incoming.body,
        icon: incoming.icon ?? '/apple-touch-icon.png',
        badge: incoming.badge ?? '/apple-touch-icon.png',
        vibrate: [100, 50, 100],
        data: {
            url: incoming.data?.url ?? '/',
            ...incoming.data
        }
    };

    const tag = payload.data?.messageId
        ? `message-${payload.data.messageId}`
        : `notif-${Date.now()}`;

    event.waitUntil(
        self.registration.showNotification(payload.title, {
            body: payload.body,
            icon: payload.icon,
            badge: payload.badge,
            vibrate: payload.vibrate,
            data: payload.data,
            tag: tag,
            renotify: true,
            requireInteraction: false
        })
    );
});

self.addEventListener('notificationclick', event => {
    event.notification.close();

    const url = event.notification.data?.url ?? '/';

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true })
            .then(windowClients => {
                for (const client of windowClients) {
                    if (client.url.includes(self.location.origin) && 'focus' in client) {
                        client.focus();
                        return client.navigate(url);
                    }
                }
                return clients.openWindow(url);
            })
    );
});