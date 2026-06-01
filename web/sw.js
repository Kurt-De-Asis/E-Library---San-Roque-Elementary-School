// Service Worker for E-Library Offline Reading
const CACHE_NAME = 'elibrary-cache-v4';
const BOOK_CACHE_NAME = 'elibrary-books-v4';

// Files to cache for offline use
const STATIC_ASSETS = [
    './',
    './reader.php',
    './offline.html',
    './css/style.css',
    './js/main.js',
    './js/reader.js',
    './js/auth.js',
    './manifest.json',
    './assets/icons/icon-48x48.png',
    './assets/icons/icon-72x72.png',
    './assets/icons/icon-96x96.png',
    './assets/icons/icon-128x128.png',
    './assets/icons/icon-192x192.png',
    './assets/icons/icon-384x384.png',
    './assets/icons/icon-512x512.png',
    './assets/icons/maskable-icon-192x192.png',
    './assets/icons/maskable-icon-512x512.png',
    './assets/icons/apple-touch-icon.png',
    './assets/logos/school-logo.png'
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
    const isBookApiRequest = url.pathname.includes('/api/ebooks.php') && url.searchParams.get('action') === 'get_book';
    const isServeRequest = url.pathname.includes('/api/serve.php');

    // Always go to network for API calls (prevents stale session/auth responses), but allow serve.php
    const isApiCall = url.pathname.includes('/api/');
    if (isApiCall && !isBookApiRequest && !isServeRequest) {
        event.respondWith(fetch(event.request));
        return;
    }

    if (isServeRequest) {
        // For serve.php: Network first, fallback to cache for offline video playback
        event.respondWith(
            fetch(event.request).then(response => {
                if (response.ok) {
                    const clone = response.clone();
                    caches.open(BOOK_CACHE_NAME).then(c => c.put(event.request, clone).catch(() => {}));
                }
                return response;
            }).catch(() => {
                return caches.match(event.request).then(cachedResponse => {
                    if (cachedResponse) {
                        return cachedResponse;
                    }
                    return new Response('Video not available offline', {
                        status: 503,
                        statusText: 'Service Unavailable'
                    });
                });
            })
        );
        return;
    }

    const isReaderRequest = url.pathname.includes('/reader.php');

    if (isReaderRequest) {
        // For reader.php: Network first, fallback to cached reader page for offline
        event.respondWith(
            fetch(event.request).then(response => {
                if (response.ok) {
                    const clone = response.clone();
                    var normalizedUrl = new URL('./reader.php', self.location.href);
                    caches.open(CACHE_NAME).then(function(cache) {
                        cache.put(normalizedUrl, clone).catch(function() {});
                    });
                }
                return response;
            }).catch(function() {
                var normalizedUrl = new URL('./reader.php', self.location.href);
                return caches.match(normalizedUrl).then(function(cachedResponse) {
                    if (cachedResponse) {
                        return cachedResponse;
                    }
                    return caches.match('./offline.html');
                });
            })
        );
        return;
    }

    if (isBookRequest) {
        // For books: Network first, fallback to cache for offline
        event.respondWith(
            fetch(event.request).then(response => {
                // Cache successful book responses (clone BEFORE returning)
                if (response.ok) {
                    const clone = response.clone();
                    caches.open(BOOK_CACHE_NAME).then(c => c.put(event.request, clone).catch(() => {}));
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
    } else if (isBookApiRequest) {
        // For book API: Network first, fallback to cache for offline
        event.respondWith(
            fetch(event.request).then(response => {
                if (response.ok) {
                    const clone = response.clone();
                    caches.open(BOOK_CACHE_NAME).then(c => c.put(event.request, clone).catch(() => {}));
                }
                return response;
            }).catch(() => {
                return caches.match(event.request).then(cachedResponse => {
                    if (cachedResponse) {
                        return cachedResponse;
                    }
                    return new Response(JSON.stringify({ success: false, message: 'Book not available offline' }), {
                        status: 503,
                        headers: { 'Content-Type': 'application/json' }
                    });
                });
            })
        );
    } else {
        const isNavPage = url.pathname.endsWith('/login.php') || url.pathname === '/' || url.pathname.endsWith('/index.php') || url.pathname.endsWith('/offline.html') || url.pathname.includes('/admin/') || url.pathname.includes('/teacher/');
        
        // For navigation pages: Cache first (instant), background refresh for next visit
        if (isNavPage) {
            event.respondWith(
                caches.match(event.request, { ignoreSearch: true }).then(cached => {
                    if (cached) {
                        // Background fetch to update cache for next visit
                        fetch(event.request).then(response => {
                            if (response.ok) {
                                var cacheUrl = new URL(event.request.url);
                                cacheUrl.search = '';
                                caches.open(CACHE_NAME).then(c => {
                                    c.put(new Request(cacheUrl.toString(), {method: event.request.method, headers: event.request.headers, mode: event.request.mode, credentials: event.request.credentials}), response).catch(() => {});
                                });
                            }
                        }).catch(() => {});
                        return cached;
                    }
                    return fetch(event.request).then(response => {
                        if (response.ok) {
                            var cacheUrl = new URL(event.request.url);
                            cacheUrl.search = '';
                            const clone = response.clone();
                            caches.open(CACHE_NAME).then(c => {
                                c.put(new Request(cacheUrl.toString(), {method: event.request.method, headers: event.request.headers, mode: event.request.mode, credentials: event.request.credentials}), clone).catch(() => {});
                            });
                        }
                        return response;
                    }).catch(() => {
                        return caches.match('./offline.html').catch(() => new Response('Offline', { status: 503 }));
                    });
                })
            );
            return;
        }
        
        // For static assets (CSS, JS, images): Cache first, fallback to network
        // Use ignoreSearch: true so cache-busting query strings (e.g. ?v=1.0.6) don't break matching
        event.respondWith(
            caches.match(event.request, { ignoreSearch: true }).then(cachedResponse => {
                if (cachedResponse) {
                    return cachedResponse;
                }
                return fetch(event.request).then(response => {
                    if (!response || response.status !== 200 || response.type !== 'basic') {
                        return response;
                    }
                    const clone = response.clone();
                    // Normalize cache key by stripping query string
                    var cacheUrl = new URL(event.request.url);
                    cacheUrl.search = '';
                    var normalizedReq = new Request(cacheUrl.toString(), {
                        method: event.request.method,
                        headers: event.request.headers,
                        mode: event.request.mode,
                        credentials: event.request.credentials
                    });
                    caches.open(CACHE_NAME).then(c => c.put(normalizedReq, clone).catch(() => {}));
                    return response;
                }).catch(() => {
                    if (event.request.mode === 'navigate') {
                        return caches.match('./offline.html');
                    }
                });
            })
        );
    }
});

// Handle messages from client
self.addEventListener('message', event => {
    // Force new SW to activate immediately (sent when a waiting worker is detected)
    if (event.data && event.data.action === 'skipWaiting') {
        self.skipWaiting();
        return;
    }
    
    if (event.data && event.data.action === 'cacheBook') {
        const bookUrl = event.data.bookUrl;
        const bookId = event.data.bookId;
        
        event.waitUntil(
            caches.open(BOOK_CACHE_NAME).then(cache => {
                const controller = new AbortController();
                const fetchTimeout = setTimeout(() => controller.abort(), 120000);
                return fetch(bookUrl, { signal: controller.signal }).then(response => {
                    clearTimeout(fetchTimeout);
                    if (response.ok) {
                        return cache.put(bookUrl, response).then(() => {
                            self.clients.matchAll().then(clients => {
                                clients.forEach(client => {
                                    client.postMessage({
                                        type: 'bookCached',
                                        bookId: bookId
                                    });
                                });
                            });
                        });
                    } else {
                        self.clients.matchAll().then(clients => {
                            clients.forEach(client => {
                                client.postMessage({
                                    type: 'bookCacheFailed',
                                    bookId: bookId,
                                    error: 'Server returned ' + response.status
                                });
                            });
                        });
                    }
                }).catch(err => {
                    clearTimeout(fetchTimeout);
                    console.error('SW: Failed to fetch book for caching:', err);
                    self.clients.matchAll().then(clients => {
                        clients.forEach(client => {
                            client.postMessage({
                                type: 'bookCacheFailed',
                                bookId: bookId,
                                error: err.message
                            });
                        });
                    });
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
