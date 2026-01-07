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
            const incoming = event.data.json();

            payload.title = incoming.title ?? payload.title;
            payload.body = incoming.body ?? payload.body;
            payload.icon = incoming.icon ?? payload.icon;
            payload.badge = incoming.badge ?? payload.badge;

            payload.data = {
                ...payload.data,
                ...(incoming.data ?? {})
            };
        } catch (e) {
            payload.body = event.data.text();
        }
    }

    event.waitUntil(
        self.registration.showNotification(payload.title, {
            body: payload.body,
            icon: payload.icon,
            badge: payload.badge,
            data: payload.data
        })
    );
});
