// Service worker for Papyrus Photo Jogja — Antrian PWA.
// Purpose: receive Web Push messages even when the ticket page tab/browser
// is closed, and show a system notification for them.

self.addEventListener('install', (event) => {
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(self.clients.claim());
});

// Show the notification when a push arrives.
self.addEventListener('push', (event) => {
    let payload = { title: '🔔 Papyrus Photo', body: 'Ada pembaruan status antrian Anda.', data: {} };

    if (event.data) {
        try {
            payload = { ...payload, ...event.data.json() };
        } catch (e) {
            payload.body = event.data.text();
        }
    }

    const options = {
        body: payload.body,
        icon: '/images/logo-papyrus.png',
        badge: '/images/logo-papyrus.png',
        // Pola getar lebih panjang & berjeda supaya lebih kerasa dipanggil
        // (angka = ms getar, jeda, getar, jeda, dst). Catatan: iPhone/Safari
        // tidak mendukung opsi `vibrate` sama sekali, jadi ini hanya efektif
        // di Android.
        vibrate: [300, 200, 300, 200, 300, 400, 300, 200, 300, 200, 300],
        requireInteraction: true,
        data: payload.data || {},
        tag: 'ticket-called',
        renotify: true,
    };

    event.waitUntil(self.registration.showNotification(payload.title, options));
});

// Focus an existing tab for this ticket if one is open, otherwise open a new one.
self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    const targetUrl = event.notification.data?.url || '/';

    event.waitUntil(
        self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clientList) => {
            for (const client of clientList) {
                if (client.url.includes(targetUrl.replace(self.location.origin, '')) && 'focus' in client) {
                    return client.focus();
                }
            }
            if (self.clients.openWindow) {
                return self.clients.openWindow(targetUrl);
            }
        })
    );
});
