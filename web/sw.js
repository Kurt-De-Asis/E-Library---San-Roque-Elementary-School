// Service Worker for E-Library Offline Reading
const CACHE_NAME = 'elibrary-cache-v1';
const BOOK_CACHE_NAME = 'elibrary-books-v1';

// Files to cache for offline use
const STATIC_ASSETS = [
    '/e-library/web/',
    '/e-library/web/index.php',
    '/e-library/web/reader.php',
    '/e-library/web/login.php',
    '/e-library/web/css/style.css',
    '/e-library/web/js/main.js',
    '/e-library/web/js/reader.js',
    '/e-library/web/js/auth.js',
    '/e-library/web/manifest.json',
    'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
    'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js'
];

// Install event - cache static assets
self.addEventListener('install', event => {
    console.log('Service Worker: Installing...');
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => {
                console.log('Service Worker: Caching static assets');
                return cache.addAll(STATIC_ASSETS);
            })
            .then(() => self.skipWaiting())
            .catch(err => console.log('Cache failed:', err))
    );
});

// Activate event - clean up old caches
self.addEventListener('activate', event => {
    console.log('Service Worker: Activating...');
    event.waitUntil(
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames.map(cache => {
                    if (cache !== CACHE_NAME && cache !== BOOK_CACHE_NAME) {
                        console.log('Service Worker: Clearing old cache');
                        return caches.delete(cache);
                    }
                })
            );
        }).then(() => self.clients.claim())
    );
});

// Fetch event - serve from cache, fallback to network
self.addEventListener('fetch', event => {
    const url = new URL(event.request.url);
    
    // Handle book PDF files specially
    if (url.pathname.includes('/uploads/books/') && url.pathname.endsWith('.pdf')) {
        event.respondWith(handleBookRequest(event.request));
        return;
    }
    
    // Handle API requests - network first
    if (url.pathname.includes('/api/')) {
        event.respondWith(networkFirst(event.request));
        return;
    }
    
    // Static assets - cache first
    event.respondWith(cacheFirst(event.request));
});

// Cache first strategy
async function cacheFirst(request) {
    const cachedResponse = await caches.match(request);
    if (cachedResponse) {
        return cachedResponse;
    }
    
    try {
        const networkResponse = await fetch(request);
        if (networkResponse.ok) {
            const cache = await caches.open(CACHE_NAME);
            cache.put(request, networkResponse.clone());
        }
        return networkResponse;
    } catch (error) {
        return new Response('Offline - Content not available', {
            status: 503,
            statusText: 'Service Unavailable'
        });
    }
}

// Network first strategy
async function networkFirst(request) {
    try {
        const networkResponse = await fetch(request);
        return networkResponse;
    } catch (error) {
        const cachedResponse = await caches.match(request);
        if (cachedResponse) {
            return cachedResponse;
        }
        return new Response(JSON.stringify({ success: false, message: 'Offline' }), {
            headers: { 'Content-Type': 'application/json' }
        });
    }
}

// Handle book PDF requests with caching
async function handleBookRequest(request) {
    // Try cache first for books
    const cachedResponse = await caches.match(request);
    if (cachedResponse) {
        console.log('Service Worker: Serving book from cache');
        return cachedResponse;
    }
    
    // Not in cache, fetch from network
    try {
        const networkResponse = await fetch(request);
        if (networkResponse.ok) {
            // Cache the book for offline reading
            const cache = await caches.open(BOOK_CACHE_NAME);
            cache.put(request, networkResponse.clone());
            console.log('Service Worker: Book cached for offline');
        }
        return networkResponse;
    } catch (error) {
        return new Response('Book not available offline', {
            status: 503,
            statusText: 'Service Unavailable'
        });
    }
}

// Message handler for caching specific books
self.addEventListener('message', event => {
    if (event.data.action === 'cacheBook') {
        cacheBookForOffline(event.data.bookUrl, event.data.bookId);
    }
    
    if (event.data.action === 'getCachedBooks') {
        getCachedBooks().then(books => {
            event.ports[0].postMessage(books);
        });
    }
    
    if (event.data.action === 'removeBook') {
        removeBookFromCache(event.data.bookUrl);
    }
});

// Cache a specific book for offline reading
async function cacheBookForOffline(bookUrl, bookId) {
    try {
        const cache = await caches.open(BOOK_CACHE_NAME);
        const response = await fetch(bookUrl);
        if (response.ok) {
            await cache.put(bookUrl, response);
            console.log('Book cached:', bookId);
            
            // Notify clients
            const clients = await self.clients.matchAll();
            clients.forEach(client => {
                client.postMessage({
                    type: 'bookCached',
                    bookId: bookId,
                    success: true
                });
            });
        }
    } catch (error) {
        console.error('Failed to cache book:', error);
    }
}

// Get list of cached books
async function getCachedBooks() {
    const cache = await caches.open(BOOK_CACHE_NAME);
    const keys = await cache.keys();
    return keys.map(request => request.url);
}

// Remove a book from cache
async function removeBookFromCache(bookUrl) {
    const cache = await caches.open(BOOK_CACHE_NAME);
    await cache.delete(bookUrl);
    console.log('Book removed from cache');
}
