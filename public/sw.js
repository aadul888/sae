/* ==========================================================================
   SAE (Sistem Aplikasi Edukasi) — Progressive Web App Service Worker
   Version: 1.0.8
   Scope: /
   ========================================================================== */

const CACHE_NAME = 'sae-pwa-v1.0.8';
const OFFLINE_URL = '/offline';

// Aset inti yang di-precache saat instalasi service worker
const PRECACHE_ASSETS = [
    OFFLINE_URL,
    '/css/sae.css',
    '/css/dashboard.css',
    '/js/sae.js',
    '/js/pwa.js',
    '/img/logo-icon.png',
    '/img/icons/icon-192x192.png',
    '/img/icons/icon-512x512.png',
    '/img/icons/icon-dark-192x192.png',
    '/img/icons/icon-dark-512x512.png',
    '/img/icons/icon-light-192x192.png',
    '/img/icons/icon-light-512x512.png',
    '/vendor/fontawesome/css/all.min.css'
];

// Alamat URL yang WAJIB Network-Only (TIDAK BOLEH di-cache demi keamanan & keakuratan data)
const NETWORK_ONLY_PATTERNS = [
    /^\/api\//i,
    /^\/manifest/i,
    /^\/login/i,
    /^\/logout/i,
    /^\/auth\//i,
    /^\/presensi\/scan\/process/i,
    /^\/presensi\/scan\/unlock/i,
    /^\/presensi\/scan\/lock/i,
    /^\/receive-data/i,
    /^\/install/i
];

// 1. Install Event: Cache Core Shell
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then(async (cache) => {
            console.log('[SAE-PWA] Pre-caching core shell assets...');
            // Gunakan addAll dengan toleransi kegagalan parsial jika beberapa vendor asset belum ada
            for (const asset of PRECACHE_ASSETS) {
                try {
                    await cache.add(asset);
                } catch (err) {
                    console.warn('[SAE-PWA] Failed to precache:', asset, err);
                }
            }
        }).then(() => self.skipWaiting())
    );
});

// 2. Activate Event: Clean up old caches
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) => {
            return Promise.all(
                keys.map((key) => {
                    if (key !== CACHE_NAME) {
                        console.log('[SAE-PWA] Removing legacy cache:', key);
                        return caches.delete(key);
                    }
                })
            );
        }).then(() => self.clients.claim())
    );
});

// 3. Helper: Periksa apakah request harus di-bypass
function shouldBypassCache(request) {
    if (request.method !== 'GET') return true;

    const url = new URL(request.url);
    // Hanya tangani request dari origin yang sama
    if (url.origin !== self.location.origin) {
        // Izinkan caching untuk Google Fonts & CDN fontawesome jika perlu
        return !url.hostname.includes('fonts.googleapis.com') && 
               !url.hostname.includes('fonts.gstatic.com') &&
               !url.hostname.includes('cdnjs.cloudflare.com');
    }

    // Cek pattern Network-Only
    return NETWORK_ONLY_PATTERNS.some(pattern => pattern.test(url.pathname));
}

// 4. Fetch Event: Intelligent Strategy
self.addEventListener('fetch', (event) => {
    const request = event.request;

    // Abaikan jika bukan GET atau masuk kriteria bypass
    if (shouldBypassCache(request)) {
        return;
    }

    const isHtmlNavigation = request.mode === 'navigate' || 
        (request.headers.get('accept') && request.headers.get('accept').includes('text/html'));

    if (isHtmlNavigation) {
        // Strategi: Network-First dengan Offline Fallback
        event.respondWith(
            fetch(request)
                .then((networkResponse) => {
                    // Update cache jika response valid
                    if (networkResponse && networkResponse.status === 200) {
                        const responseClone = networkResponse.clone();
                        caches.open(CACHE_NAME).then((cache) => {
                            cache.put(request, responseClone);
                        });
                    }
                    return networkResponse;
                })
                .catch(async () => {
                    // Coba ambil dari cache halaman yang bersangkutan
                    const cachedResponse = await caches.match(request);
                    if (cachedResponse) {
                        return cachedResponse;
                    }
                    // Jika tidak ada di cache, tampilkan halaman offline terstandarisasi SAE
                    const offlinePage = await caches.match(OFFLINE_URL);
                    return offlinePage || new Response(
                        '<h1>Offline</h1><p>Anda sedang tidak terhubung ke internet. Silakan periksa koneksi Anda.</p>',
                        { headers: { 'Content-Type': 'text/html; charset=utf-8' } }
                    );
                })
        );
        return;
    }

    // Untuk aset statis (CSS, JS, Gambar, Web Fonts): Stale-While-Revalidate
    event.respondWith(
        caches.match(request).then((cachedResponse) => {
            const fetchPromise = fetch(request).then((networkResponse) => {
                if (networkResponse && networkResponse.status === 200 && networkResponse.type === 'basic') {
                    const responseClone = networkResponse.clone();
                    caches.open(CACHE_NAME).then((cache) => {
                        cache.put(request, responseClone);
                    });
                }
                return networkResponse;
            }).catch(() => {
                // Ignore background fetch error if offline
            });

            return cachedResponse || fetchPromise;
        })
    );
});

// 5. Message Event: Skip waiting saat tombol update ditekan
self.addEventListener('message', (event) => {
    if (event.data && event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
});

// 6. Push Event: Menerima Pesan Web Push dari Server (Presensi Siswa, E-Izin Wali Kelas, Broadcast Sekolah)
self.addEventListener('push', (event) => {
    let data = {};
    if (event.data) {
        try {
            data = event.data.json();
        } catch (e) {
            data = { title: 'SAE Notifikasi', body: event.data.text() };
        }
    } else {
        data = { title: 'SAE Notifikasi', body: 'Ada informasi dan pembaruan terbaru dari sekolah.' };
    }

    const title = data.title || 'SAE - Sistem Aplikasi Edukasi';
    const options = {
        body: data.body || '',
        icon: data.icon || '/img/icons/icon-192x192.png',
        badge: data.badge || '/img/icons/icon-96x96.png',
        image: data.image || undefined,
        tag: data.tag || 'sae-push-' + Date.now(),
        renotify: data.renotify !== false,
        vibrate: data.vibrate || [200, 100, 200],
        data: data.data || { url: '/' },
        actions: data.actions || [
            { action: 'open', title: 'Buka Aplikasi' },
            { action: 'close', title: 'Tutup' }
        ]
    };

    event.waitUntil(
        self.registration.showNotification(title, options)
    );
});

// 7. Notification Click Event: Navigasi Cerdas saat Notifikasi Diketuk
self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    if (event.action === 'close') {
        return;
    }

    const targetUrl = (event.notification.data && event.notification.data.url) 
        ? event.notification.data.url 
        : '/';

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clientList) => {
            // Jika ada jendela atau tab SAE yang sedang terbuka, fokuskan dan arahkan
            for (const client of clientList) {
                if (client.url.includes(self.location.origin) && 'focus' in client) {
                    if ('navigate' in client && targetUrl !== '/') {
                        client.navigate(targetUrl);
                    }
                    return client.focus();
                }
            }
            // Jika tidak ada jendela yang terbuka, buka jendela/tab baru
            if (clients.openWindow) {
                return clients.openWindow(targetUrl);
            }
        })
    );
});

// 8. Notification Close Event
self.addEventListener('notificationclose', (event) => {
    // Hook aman untuk analitik atau pembersihan data
});

