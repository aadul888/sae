/**
 * ==========================================================================
 * SAE (Sistem Aplikasi Edukasi) — Progressive Web App Client Controller
 * File: public/js/pwa.js
 * ==========================================================================
 */

(function () {
    'use strict';

    let deferredPrompt = null;
    let newWorkerWaiting = null;

    // Deteksi apakah sedang berjalan dalam mode Standalone PWA
    const isStandalone = window.matchMedia('(display-mode: standalone)').matches || 
                         window.navigator.standalone === true;

    if (isStandalone) {
        document.documentElement.classList.add('pwa-standalone');
        console.log('[SAE-PWA] Running in Standalone Application Mode');
    }

    // 1. Sinkronisasi Warna Theme-Color dengan Tema Gelap/Terang
    function syncThemeColor() {
        const metaThemeColor = document.getElementById('pwaThemeColorMeta');
        if (!metaThemeColor) return;
        const currentTheme = document.documentElement.getAttribute('data-theme');
        if (currentTheme === 'light') {
            metaThemeColor.setAttribute('content', '#FFFFFF');
        } else {
            metaThemeColor.setAttribute('content', '#0B0F19');
        }
    }

    // Amati perubahan atribut tema pada <html>
    const themeObserver = new MutationObserver(syncThemeColor);
    themeObserver.observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme'] });
    syncThemeColor();

    // 2. Registrasi Service Worker & Deteksi Pembaruan
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('/sw.js', { scope: '/' })
                .then((registration) => {
                    console.log('[SAE-PWA] ServiceWorker registered with scope:', registration.scope);

                    // Cek jika sudah ada worker yang waiting
                    if (registration.waiting) {
                        newWorkerWaiting = registration.waiting;
                        showUpdateNotification();
                    }

                    // Deteksi jika ada update baru yang sedang diunduh
                    registration.addEventListener('updatefound', () => {
                        const installingWorker = registration.installing;
                        if (!installingWorker) return;

                        installingWorker.addEventListener('statechange', () => {
                            if (installingWorker.state === 'installed' && navigator.serviceWorker.controller) {
                                newWorkerWaiting = installingWorker;
                                showUpdateNotification();
                            }
                        });
                    });
                })
                .catch((err) => {
                    console.warn('[SAE-PWA] ServiceWorker registration failed:', err);
                });

            // Reload otomatis saat controller berganti (setelah SKIP_WAITING)
            let refreshing = false;
            navigator.serviceWorker.addEventListener('controllerchange', () => {
                if (!refreshing) {
                    refreshing = true;
                    window.location.reload();
                }
            });
        });
    }

    // 3. Menampilkan Toast Notifikasi Pembaruan
    function showUpdateNotification() {
        if (document.getElementById('saePwaUpdateBanner')) return;

        const banner = document.createElement('div');
        banner.id = 'saePwaUpdateBanner';
        banner.style.cssText = `
            position: fixed;
            bottom: 24px;
            right: 24px;
            z-index: 1000001;
            background: var(--bg-card, #131b2e);
            border: 1px solid var(--border-glow, rgba(79,110,247,0.4));
            box-shadow: 0 16px 36px rgba(0,0,0,0.5);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-radius: 14px;
            padding: 14px 18px;
            display: flex;
            align-items: center;
            gap: 14px;
            max-width: 380px;
            animation: pwaSlideUp 0.3s ease;
        `;

        banner.innerHTML = `
            <div style="width: 36px; height: 36px; border-radius: 10px; background: rgba(79,110,247,0.15); color: var(--primary, #4f6ef7); display: flex; align-items: center; justify-content: center; font-size: 1.1rem; flex-shrink: 0;">
                <i class="fas fa-arrows-rotate"></i>
            </div>
            <div style="flex: 1; min-width: 0;">
                <div style="font-size: 0.88rem; font-weight: 700; color: var(--text-main, #f8fafc); margin-bottom: 2px;">
                    Pembaruan Tersedia
                </div>
                <div style="font-size: 0.76rem; color: var(--text-muted, #94a3b8); line-height: 1.3;">
                    Versi aplikasi baru siap digunakan.
                </div>
            </div>
            <button type="button" id="btnApplyPwaUpdate" style="background: var(--primary, #4f6ef7); color: #ffffff; border: none; border-radius: 8px; padding: 7px 12px; font-size: 0.8rem; font-weight: 600; cursor: pointer; white-space: nowrap;">
                Muat Ulang
            </button>
        `;

        document.body.appendChild(banner);

        document.getElementById('btnApplyPwaUpdate')?.addEventListener('click', () => {
            if (newWorkerWaiting) {
                newWorkerWaiting.postMessage({ type: 'SKIP_WAITING' });
            }
        });
    }

    // 4. Tangani Event Instalasi PWA (beforeinstallprompt)
    window.addEventListener('beforeinstallprompt', (e) => {
        // Mencegah prompt bawaan browser muncul seketika
        e.preventDefault();
        deferredPrompt = e;
        console.log('[SAE-PWA] beforeinstallprompt event captured');

        // Tampilkan tombol instalasi jika belum dalam mode standalone
        if (!isStandalone) {
            updateInstallUI(true);
        }
    });

    // Fungsi Global untuk Memanggil Prompt Instalasi Native
    window.installSaePwa = async function () {
        if (!deferredPrompt) {
            if (window.Swal) {
                Swal.fire({
                    icon: 'info',
                    title: 'Instal Aplikasi SAE',
                    text: 'Untuk menginstal di perangkat Anda, gunakan menu browser (titik tiga atau tombol Share) lalu pilih "Tambahkan ke Layar Utama" (Add to Home Screen).',
                    confirmButtonColor: '#4f6ef7'
                });
            } else {
                alert('Gunakan menu browser lalu pilih "Tambahkan ke Layar Utama" / "Add to Home Screen" untuk menginstal SAE.');
            }
            return;
        }

        deferredPrompt.prompt();
        const { outcome } = await deferredPrompt.userChoice;
        console.log('[SAE-PWA] User response to install prompt:', outcome);

        if (outcome === 'accepted') {
            console.log('[SAE-PWA] User accepted the install prompt');
        }
        deferredPrompt = null;
        updateInstallUI(false);
    };

    // Event saat aplikasi telah berhasil diinstal
    window.addEventListener('appinstalled', () => {
        console.log('[SAE-PWA] Application was successfully installed!');
        deferredPrompt = null;
        updateInstallUI(false);

        if (window.Swal) {
            Swal.fire({
                icon: 'success',
                title: 'Aplikasi Terpasang!',
                text: 'SAE telah ditambahkan ke layar utama perangkat Anda.',
                timer: 3000,
                showConfirmButton: false
            });
        }
    });

    // Helper untuk mengaktifkan/menonaktifkan tombol instalasi di UI
    function updateInstallUI(available) {
        const installBtns = document.querySelectorAll('.btn-pwa-install, #btnPwaInstallNav');
        installBtns.forEach(btn => {
            if (available && !isStandalone) {
                btn.style.display = 'inline-flex';
            } else {
                btn.style.display = 'none';
            }
        });
    }

    // Inisialisasi awal UI instalasi
    document.addEventListener('DOMContentLoaded', () => {
        updateInstallUI(false);
    });

})();
