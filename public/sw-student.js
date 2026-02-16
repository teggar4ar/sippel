// SIPPEL Student Service Worker
const CACHE_NAME = 'sippel-student-v4';
const OFFLINE_URL = '/offline.html';

// Assets to cache on install
const PRECACHE_ASSETS = [
    '/offline.html',
    '/icons/student-icon-192.svg',
    '/icons/student-icon-512.svg',
];

// Install event - cache essential assets
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => {
                console.log('[SW Student] Precaching assets');
                return cache.addAll(PRECACHE_ASSETS);
            })
            .then(() => self.skipWaiting())
    );
});

// Activate event - clean up old caches
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames
                    .filter((name) => name !== CACHE_NAME)
                    .map((name) => {
                        console.log('[SW Student] Deleting old cache:', name);
                        return caches.delete(name);
                    })
            );
        }).then(() => self.clients.claim())
    );
});

// Fetch event - network first, fallback to cache
self.addEventListener('fetch', (event) => {
    // Skip non-GET requests
    if (event.request.method !== 'GET') return;

    // Skip cross-origin requests
    if (!event.request.url.startsWith(self.location.origin)) return;

    // Skip Livewire AJAX endpoints (but not HTML page requests)
    if (event.request.url.includes('/livewire/') ||
        event.request.url.includes('/api/')) {
        return;
    }

    // Handle HTML page requests (direct navigation or wire:navigate)
    const isHTMLRequest = event.request.mode === 'navigate' ||
                          event.request.destination === 'document' ||
                          event.request.headers.get('Accept')?.includes('text/html');

    if (isHTMLRequest) {
        event.respondWith(
            fetch(event.request)
                .catch(async () => {
                    // Network failed - serve offline page
                    const offlineResponse = await caches.match(OFFLINE_URL);
                    if (offlineResponse) {
                        return offlineResponse;
                    }
                    return new Response('Offline - No cached page available', {
                        status: 503,
                        statusText: 'Service Unavailable',
                    });
                })
        );
        return;
    }

    // Only cache static assets
    if (!isStaticAsset(event.request.url)) {
        return;
    }

    event.respondWith(
        fetch(event.request)
            .then((response) => {
                // Cache successful responses for static assets
                if (response.ok && isStaticAsset(event.request.url)) {
                    const responseClone = response.clone();
                    caches.open(CACHE_NAME).then((cache) => {
                        cache.put(event.request, responseClone);
                    });
                }
                return response;
            })
            .catch(async () => {
                // Try to get from cache
                const cachedResponse = await caches.match(event.request);
                if (cachedResponse) {
                    return cachedResponse;
                }

                // Return offline page for navigation requests
                if (event.request.mode === 'navigate') {
                    const offlineResponse = await caches.match(OFFLINE_URL);
                    if (offlineResponse) {
                        return offlineResponse;
                    }
                }

                // Return a basic offline response
                return new Response('Offline', {
                    status: 503,
                    statusText: 'Service Unavailable',
                });
            })
    );
});

// Helper function to check if URL is a static asset worth caching
// Excludes Vite-generated assets (they have hashes built-in for cache busting)
function isStaticAsset(url) {
    // Skip Vite build assets - they have content hashes and don't need SW caching
    if (url.includes('/build/assets/')) {
        return false;
    }
    return url.match(/\.(png|jpg|jpeg|gif|svg|ico|woff|woff2|ttf|eot)(\?.*)?$/i);
}

// Listen for messages from the main thread
self.addEventListener('message', (event) => {
    if (event.data && event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
});
