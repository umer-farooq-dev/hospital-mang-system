// Installable app: shows offline.html when the internet is down (pages are never cached: data is always live).
const OFFLINE = '/offline.html';
self.addEventListener('install', (event) => {
    event.waitUntil(caches.open('hms-offline-v1').then((cache) => cache.add(OFFLINE)));
    self.skipWaiting();
});
self.addEventListener('activate', (event) => event.waitUntil(self.clients.claim()));
self.addEventListener('fetch', (event) => {
    if (event.request.mode !== 'navigate') return;
    event.respondWith(fetch(event.request).catch(() => caches.match(OFFLINE)));
});
