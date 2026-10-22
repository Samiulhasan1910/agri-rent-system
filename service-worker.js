/* AgriRent service worker: caches the app shell and recently viewed pages for offline use. */
const VERSION = 'v3';
const STATIC_CACHE = 'agri-static-' + VERSION;
const PAGE_CACHE = 'agri-pages-' + VERSION;
const STATIC_FILES = [
  './offline.html',
  './manifest.json',
  './assets/css/style.css',
  './assets/js/app.js',
  './assets/js/charts.js',
  './assets/js/offline.js',
  './assets/js/pages.js',
  './assets/img/logo.svg',
  './assets/img/hero-tractor.jpg',
  './assets/img/equipment/tractor.svg',
  './assets/img/equipment/tiller.svg',
  './assets/img/equipment/harvester.svg',
  './assets/img/equipment/pump.svg',
  './assets/img/equipment/sprayer.svg',
  './assets/img/equipment/rotavator.svg',
  './assets/img/equipment/seeder.svg',
  './assets/img/equipment/thresher.svg',
];
// Pages that are useful offline ("basic features": browse & request)
const OFFLINE_ROUTES = ['dashboard/index', 'equipment/index', 'equipment/show', 'booking/index', 'wishlist/index', 'complaint/index', 'emergency/index', 'home/index'];

self.addEventListener('install', (event) => {
  event.waitUntil(caches.open(STATIC_CACHE).then((cache) => cache.addAll(STATIC_FILES)).then(() => self.skipWaiting()));
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys()
      .then((keys) => Promise.all(keys.filter((k) => k.startsWith('agri-') && k !== STATIC_CACHE && k !== PAGE_CACHE).map((k) => caches.delete(k))))
      .then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', (event) => {
  const request = event.request;
  if (request.method !== 'GET') return;
  const url = new URL(request.url);
  if (url.origin !== self.location.origin) return;

  // AJAX/JSON requests are handled by the page (offline queue), never cached here.
  if (request.headers.get('X-Requested-With') === 'XMLHttpRequest' || url.searchParams.has('partial')) return;

  // Static assets: cache first, refresh in background.
  if (url.pathname.includes('/assets/') || url.pathname.endsWith('manifest.json')) {
    event.respondWith(
      caches.match(request, { ignoreSearch: true }).then((cached) => {
        const network = fetch(request).then((res) => {
          if (res.ok) caches.open(STATIC_CACHE).then((c) => c.put(request, res.clone()));
          return res;
        }).catch(() => cached);
        return cached || network;
      })
    );
    return;
  }

  // Pages: network first, fall back to the last cached copy or the offline page.
  if (request.mode === 'navigate') {
    const route = url.searchParams.get('r') || 'home/index';
    const cacheable = OFFLINE_ROUTES.includes(route);
    event.respondWith(
      fetch(request).then((res) => {
        if (cacheable && res.ok && res.type === 'basic') {
          const copy = res.clone();
          caches.open(PAGE_CACHE).then((c) => c.put(request, copy));
        }
        return res;
      }).catch(() => caches.match(request).then((cached) => cached || caches.match('./offline.html')))
    );
  }
});
