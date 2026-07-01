const CACHE_NAME = 'curtiss-erp-v1';
const ASSETS_TO_CACHE = [
  '/',
  '/manifest.json',
  '/icon-192.png',
  '/icon-512.png',
  'https://unpkg.com/@phosphor-icons/web'
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      return cache.addAll(ASSETS_TO_CACHE);
    })
  );
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames.map((cache) => {
          if (cache !== CACHE_NAME) {
            return caches.delete(cache);
          }
        })
      );
    })
  );
  self.clients.claim();
});

// Network-First with fallback to cache for static resources
self.addEventListener('fetch', (event) => {
  // Only handle GET requests
  if (event.request.method !== 'GET') {
    return;
  }

  // Suppress browser extension requests or non-http requests
  if (!event.request.url.startsWith(self.location.origin) && !event.request.url.startsWith('https://unpkg.com')) {
    return;
  }

  // Only intercept requests for static files or items explicitly listed in ASSETS_TO_CACHE.
  // This prevents tracking prevention from blocking cookie transmission on dynamic page loads and API calls.
  const urlPath = event.request.url.replace(self.location.origin, '');
  const isStatic = ASSETS_TO_CACHE.some(asset => urlPath === asset) ||
                   /\.(js|css|png|jpg|jpeg|gif|svg|ico|json|woff|woff2|ttf)$/i.test(event.request.url);

  if (!isStatic) {
    return; // Let the browser handle dynamic PHP controllers and API calls natively with full credentials
  }

  event.respondWith(
    fetch(event.request)
      .then((response) => {
        return response;
      })
      .catch(() => {
        return caches.match(event.request).then((cachedResponse) => {
          if (cachedResponse) {
            return cachedResponse;
          }
          return new Response('Network error occurred.', {
            status: 408,
            statusText: 'Network Connect Timeout'
          });
        });
      })
  );
});

