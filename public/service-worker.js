// Minimal offline support: caches the shell/static assets so the app can at
// least display something offline; does NOT cache API/data responses or
// support offline transaction entry (see Phase 1 architecture notes — full
// offline write + background sync is out of scope for this pass).

const CACHE_NAME = 'sabay-finance-shell-v1';
const SHELL_ASSETS = [
    '/manifest.json',
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => cache.addAll(SHELL_ASSETS))
    );
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) =>
            Promise.all(keys.filter((key) => key !== CACHE_NAME).map((key) => caches.delete(key)))
        )
    );
    self.clients.claim();
});

// self.addEventListener('fetch', (event) => {
//     // Never intercept API/data calls or POSTs — always go to the network for those.
//     if (event.request.method !== 'GET' || event.request.url.includes('/dashboard/data')) {
//         return;
//     }

//     event.respondWith(
//         caches.match(event.request).then((cached) => cached || fetch(event.request).catch(() => cached))
//     );
// });

self.addEventListener('fetch', (event) => {
    if (event.request.method !== 'GET') {
        return;
    }

    const url = new URL(event.request.url);

    // Only serve cached assets
    if (!SHELL_ASSETS.includes(url.pathname)) {
        return;
    }

    event.respondWith(
        caches.match(event.request).then(cached => {
            return cached || fetch(event.request);
        })
    );
});