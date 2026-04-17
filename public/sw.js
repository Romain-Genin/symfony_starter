const CACHE_NAME = 'app-shell-v1';

// Assets à mettre en cache à l'installation
const PRECACHE_ASSETS = [
    '/',
    '/offline',
    '/icons/icon.svg',
    '/manifest.webmanifest',
];

// ── Install ──────────────────────────────────────────────────
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => cache.addAll(PRECACHE_ASSETS))
            .then(() => self.skipWaiting())
    );
});

// ── Activate ─────────────────────────────────────────────────
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(
                keys.filter((key) => key !== CACHE_NAME).map((key) => caches.delete(key))
            ))
            .then(() => self.clients.claim())
    );
});

// ── Fetch — Network first, fallback cache ────────────────────
self.addEventListener('fetch', (event) => {
    // Ne pas intercepter les requêtes non-GET ni les API
    if (event.request.method !== 'GET') return;
    if (event.request.url.includes('/api/') || event.request.url.includes('/push/')) return;

    event.respondWith(
        fetch(event.request)
            .then((response) => {
                // Mettre en cache les assets statiques
                if (response.ok && event.request.destination !== 'document') {
                    const clone = response.clone();
                    caches.open(CACHE_NAME).then((cache) => cache.put(event.request, clone));
                }
                return response;
            })
            .catch(() => caches.match(event.request)
                .then((cached) => cached || caches.match('/offline'))
            )
    );
});

// ── Push Notifications ───────────────────────────────────────
self.addEventListener('push', (event) => {
    let data = { title: 'Notification', body: '', url: '/', icon: '/icons/icon.svg' };

    try {
        data = { ...data, ...event.data.json() };
    } catch (_) {
        data.body = event.data?.text() ?? '';
    }

    event.waitUntil(
        self.registration.showNotification(data.title, {
            body:    data.body,
            icon:    data.icon,
            badge:   '/icons/icon.svg',
            vibrate: [100, 50, 100],
            data:    { url: data.url },
            actions: data.actions ?? [],
        })
    );
});

// ── Notification click ───────────────────────────────────────
self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    const url = event.notification.data?.url || '/';

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true })
            .then((clientList) => {
                const existing = clientList.find((c) => c.url === url && 'focus' in c);
                return existing ? existing.focus() : clients.openWindow(url);
            })
    );
});

// ── Background Sync ──────────────────────────────────────────
self.addEventListener('sync', (event) => {
    if (event.tag === 'background-sync') {
        event.waitUntil(Promise.resolve());
    }
});
