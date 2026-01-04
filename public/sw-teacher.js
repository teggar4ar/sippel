// SIPPEL Teacher Service Worker
const CACHE_NAME = 'sippel-teacher-v1';
const OFFLINE_URL = '/offline.html';

// Assets to cache on install
const PRECACHE_ASSETS = [
    '/offline.html',
    '/icons/teacher-icon-192.svg',
    '/icons/teacher-icon-512.svg',
];

// Install event - cache essential assets
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => {
                console.log('[SW Teacher] Precaching assets');
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
                        console.log('[SW Teacher] Deleting old cache:', name);
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

    // Skip API requests and form submissions
    if (event.request.url.includes('/livewire/') ||
        event.request.url.includes('/api/') ||
        event.request.headers.get('X-Livewire')) {
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

// Helper function to check if URL is a static asset
function isStaticAsset(url) {
    return url.match(/\.(js|css|png|jpg|jpeg|gif|svg|ico|woff|woff2|ttf|eot)(\?.*)?$/i);
}

// Listen for messages from the main thread
self.addEventListener('message', (event) => {
    if (event.data && event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
});
