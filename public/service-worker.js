// Minimal offline support: caches the shell/static assets so the app can at
// least display something offline; does NOT cache API/data responses or
// support offline transaction entry (see Phase 1 architecture notes — full
// offline write + background sync is out of scope for this pass).

const CACHE_NAME = 'sabay-finance-shell-v8';

const SHELL_ASSETS = [
    '/offline.html',
    '/offline-dashboard.html',
    '/manifest.json',

    // Application CSS
    '/assets/css/app.min.css',

    // Application JavaScript
    '/assets/js/alpine.min.js',
    '/assets/js/chart.umd.min.js',
    '/assets/js/sortable.min.js',
    '/assets/js/storage.js',
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
//     // Never intercept POST requests.
//     if (event.request.method !== 'GET') {
//         return;
//     }

//     // Never intercept application data/API requests.
//     if (event.request.url.includes('/dashboard/data')) {
//         return;
//     }

//     event.respondWith(
//         fetch(event.request)
//             .then((response) => {
//                 return response;
//             })
//             .catch(() => {
//                 return caches.match(event.request)
//                     .then((cached) => {
//                         if (cached) {
//                             return cached;
//                         }

//                         // If this is a page/navigation request,
//                         // show our offline page.
//                         if (event.request.mode === 'navigate') {
//                             return caches.match('/offline.html');
//                         }

//                         return new Response(
//                             'You are currently offline.',
//                             {
//                                 status: 503,
//                                 statusText: 'Service Unavailable',
//                                 headers: {
//                                     'Content-Type': 'text/plain; charset=utf-8'
//                                 }
//                             }
//                         );
//                     });
//             })
//     );
// });

self.addEventListener('fetch', (event) => {
    const request = event.request;
    const url = new URL(request.url);

    // Never intercept POST/PUT/PATCH/DELETE requests.
    if (request.method !== 'GET') {
        return;
    }

    // Never cache or replace API/data responses.
    if (url.pathname === '/dashboard/data') {
        return;
    }

    event.respondWith(
        fetch(request)
            .catch(async () => {
                // When navigating to the dashboard while offline,
                // show the read-only offline dashboard shell.
                if (
                    request.mode === 'navigate' &&
                    url.pathname === '/dashboard'
                ) {
                    const offlineDashboard =
                        await caches.match('/offline-dashboard.html');

                    if (offlineDashboard) {
                        return offlineDashboard;
                    }
                }

                // For other requests, try the normal cache.
                const cached = await caches.match(request);

                if (cached) {
                    return cached;
                }

                // Nothing available offline.
                return caches.match('/offline.html');
            })
    );
});