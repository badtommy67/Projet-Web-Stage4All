const CACHE_NAME = 'stage4all-v1';
const ASSETS_TO_CACHE = [
  '/',
  'https://stage4all-static.alexis-sgl.fr//css/style.css',
  'https://stage4all-static.alexis-sgl.fr//js/script.js',
  '/offline.html'
];

// Installation du cache
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      return cache.addAll(ASSETS_TO_CACHE);
    })
  );
});

// Stratégie : Réseau d'abord, sinon Cache
self.addEventListener('fetch', (event) => {
  event.respondWith(
    fetch(event.request).catch(() => {
      return caches.match(event.request);
    })
  );
});