// Service Worker for E-Library Offline Reading
const CACHE_NAME = 'elibrary-cache-v1';
const BOOK_CACHE_NAME = 'elibrary-books-v1';

// Files to cache for offline use
const STATIC_ASSETS = [
    './',
    './index.php',
    './reader.php',
    './login.php',
    './css/style.css',
    './js/main.js',
    './js/reader.js',
    './js/auth.js',
    './manifest.json'
];

// Install event
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME).then(cache => {
            return cache.addAll(STATIC_ASSETS);
        })
    );
    self.skipWaiting();
});

// Activate event
self.addEventListener('activate', event => {
    event.waitUntil(
        Promise.all([
            caches.keys().then(keys => {
                return Promise.all(
                    keys.map(key => {
                        if (key !== CACHE_NAME && key !== BOOK_CACHE_NAME) {
                            return caches.delete(key);
                        }
                    })
                );
            }),
            self.clients.claim()
        ])
    );
});

// Fetch event - Cache First strategy for static assets, Network First for books
self.addEventListener('fetch', event => {
    // Only handle GET requests
    if (event.request.method !== 'GET') return;

    const url = new URL(event.request.url);
    const isBookRequest = url.pathname.includes('/uploads/books/');

    if (isBookRequest) {
        // For books: Network first, fallback to cache for offline
        event.respondWith(
            fetch(event.request).then(response => {
                // Cache successful book responses
                if (response.ok) {
                    const cache = caches.open(BOOK_CACHE_NAME);
                    cache.then(c => c.put(event.request, response.clone()));
                }
                return response;
            }).catch(() => {
                // If network fails, try to get from cache
                return caches.match(event.request).then(cachedResponse => {
                    if (cachedResponse) {
                        return cachedResponse;
                    }
                    // Return offline page or error
                    return new Response('Book not available offline', {
                        status: 503,
                        statusText: 'Service Unavailable'
                    });
                });
            })
        );
    } else {
        // For static assets: Cache first, fallback to network
        event.respondWith(
            caches.match(event.request).then(cachedResponse => {
                if (cachedResponse) {
                    return cachedResponse;
                }
                return fetch(event.request).then(response => {
                    // Don't cache if not a successful response
                    if (!response || response.status !== 200 || response.type !== 'basic') {
                        return response;
                    }
                    // Cache static assets
                    const cache = caches.open(CACHE_NAME);
                    cache.then(c => c.put(event.request, response.clone()));
                    return response;
                }).catch(() => {
                    // If both fail, return nothing or a fallback
                });
            })
        );
    }
});

// Handle messages from client
self.addEventListener('message', event => {
    if (event.data && event.data.action === 'cacheBook') {
        const bookUrl = event.data.bookUrl;
        const bookId = event.data.bookId;
        
        event.waitUntil(
            caches.open(BOOK_CACHE_NAME).then(cache => {
                return fetch(bookUrl).then(response => {
                    if (response.ok) {
                        cache.put(bookUrl, response);
                        // Notify clients that book is cached
                        self.clients.matchAll().then(clients => {
                            clients.forEach(client => {
                                client.postMessage({
                                    type: 'bookCached',
                                    bookId: bookId
                                });
                            });
                        });
                    }
                }).catch(err => {
                    console.error('SW: Failed to fetch book for caching:', err);
                });
            })
        );
    } else if (event.data && event.data.action === 'getCachedBooks') {
        event.waitUntil(
            caches.keys().then(keys => {
                return Promise.all(
                    keys.map(key => caches.open(key).then(cache => cache.keys()))
                ).then(results => {
                    const allUrls = results.flat().map(req => req.url);
                    if (event.ports && event.ports[0]) {
                        event.ports[0].postMessage(allUrls);
                    }
                });
            })
        );
    }
});
