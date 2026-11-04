const CACHE_NAME = 'app-shell-v1';
const PRECACHE = ['/', '/offline', '/icons/icon.svg', '/manifest.webmanifest'];

self.addEventListener('install', (e) => {
    e.waitUntil(caches.open(CACHE_NAME).then((c) => c.addAll(PRECACHE)).then(() => self.skipWaiting()));
});

self.addEventListener('activate', (e) => {
    e.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(keys.filter((k) => k !== CACHE_NAME).map((k) => caches.delete(k))))
            .then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (e) => {
    if (e.request.method !== 'GET') return;
    if (e.request.url.includes('/api/') || e.request.url.includes('/push/')) return;

    e.respondWith(
        fetch(e.request)
            .then((res) => {
                if (res.ok && e.request.destination !== 'document') {
                    caches.open(CACHE_NAME).then((c) => c.put(e.request, res.clone()));
                }
                return res;
            })
            .catch(() => caches.match(e.request).then((cached) => cached || caches.match('/offline')))
    );
});

self.addEventListener('push', (e) => {
    let data = { title: 'Notification', body: '', url: '/', icon: '/icons/icon.svg' };
    try { data = { ...data, ...e.data.json() }; } catch (_) { data.body = e.data?.text() ?? ''; }

    e.waitUntil(self.registration.showNotification(data.title, {
        body: data.body, icon: data.icon, badge: '/icons/icon.svg',
        vibrate: [100, 50, 100], data: { url: data.url },
    }));
});

self.addEventListener('notificationclick', (e) => {
    e.notification.close();
    const url = e.notification.data?.url || '/';
    e.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true })
            .then((list) => { const w = list.find((c) => c.url === url && 'focus' in c); return w ? w.focus() : clients.openWindow(url); })
    );
});
