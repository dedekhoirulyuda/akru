/**
 * AKRU Service Worker
 *
 * Handles: cache-first for static assets, network-first for API,
 * offline fallback page, and background sync support.
 *
 * Blueprint §2.12: PWA installable, offline draft, sync queue.
 */

const CACHE_VERSION = 'akru-v1.0.0';
const STATIC_CACHE = `${CACHE_VERSION}-static`;
const DYNAMIC_CACHE = `${CACHE_VERSION}-dynamic`;

// Static assets to pre-cache
const PRECACHE_URLS = [
    '/',
    '/offline',
    '/manifest.json',
];

// Install — pre-cache static assets
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(STATIC_CACHE)
            .then(cache => cache.addAll(PRECACHE_URLS))
            .then(() => self.skipWaiting())
    );
});

// Activate — clean old caches
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then(keys =>
            Promise.all(
                keys
                    .filter(key => key !== STATIC_CACHE && key !== DYNAMIC_CACHE)
                    .map(key => caches.delete(key))
            )
        ).then(() => self.clients.claim())
    );
});

// Fetch — network-first for API, cache-first for static
self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);

    // Skip non-GET requests
    if (request.method !== 'GET') return;

    // API requests — network first
    if (url.pathname.startsWith('/api/')) {
        event.respondWith(
            fetch(request)
                .then(response => {
                    const clone = response.clone();
                    caches.open(DYNAMIC_CACHE)
                        .then(cache => cache.put(request, clone));
                    return response;
                })
                .catch(() => caches.match(request))
        );
        return;
    }

    // Static assets — cache first
    event.respondWith(
        caches.match(request)
            .then(cached => {
                if (cached) return cached;
                return fetch(request).then(response => {
                    const clone = response.clone();
                    caches.open(DYNAMIC_CACHE)
                        .then(cache => cache.put(request, clone));
                    return response;
                });
            })
            .catch(() => {
                // Offline fallback for navigation requests
                if (request.mode === 'navigate') {
                    return caches.match('/offline');
                }
            })
    );
});

// Background Sync — for offline transaction queue
self.addEventListener('sync', (event) => {
    if (event.tag === 'akru-sync-queue') {
        event.waitUntil(syncOfflineTransactions());
    }
});

async function syncOfflineTransactions() {
    // Will be implemented by SyncEngine
    // Reads from IndexedDB queue and sends to server
    console.log('[AKRU SW] Background sync triggered');
}
