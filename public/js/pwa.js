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
        const isMobileScreen = window.innerWidth <= 768;
        const bottomOffset = isMobileScreen 
            ? 'calc(80px + env(safe-area-inset-bottom, 12px))' 
            : 'max(24px, env(safe-area-inset-bottom, 24px))';
        const rightOffset = isMobileScreen ? '12px' : '24px';
        const leftOffset = isMobileScreen ? '12px' : 'auto';

        banner.style.cssText = `
            position: fixed;
            bottom: ${bottomOffset};
            right: ${rightOffset};
            left: ${leftOffset};
            z-index: 1000001;
            background: var(--bg-card, #131b2e);
            border: 1px solid var(--border-glow, rgba(79,110,247,0.4));
            box-shadow: 0 16px 36px rgba(0,0,0,0.5);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-radius: 14px;
            padding: 12px 16px;
            display: flex;
            align-items: center;
            gap: 12px;
            max-width: ${isMobileScreen ? 'none' : '380px'};
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

    // 4. Penawaran Instalasi Aplikasi Halaman (In-Page Install Offer Banner)
    function showInstallPromptBanner() {
        if (isStandalone) return;
        if (document.getElementById('saePwaInstallBanner')) return;

        // Cek apakah baru saja ditolak dalam 24 jam terakhir
        const dismissedAt = localStorage.getItem('sae_pwa_install_dismissed');
        if (dismissedAt) {
            const diffHours = (Date.now() - parseInt(dismissedAt, 10)) / (1000 * 60 * 60);
            if (diffHours < 24) {
                return; // Jangan ganggu pengguna jika sudah menolak dalam 24 jam
            }
        }

        const banner = document.createElement('div');
        banner.className = 'sae-pwa-banner';
        banner.id = 'saePwaInstallBanner';

        banner.innerHTML = `
            <div class="sae-pwa-banner-header">
                <img src="/img/icons/icon-96x96.png" class="sae-pwa-banner-icon" alt="SAE App Icon" onerror="this.src='/img/logo-icon.png'">
                <div class="sae-pwa-banner-info">
                    <div class="sae-pwa-banner-title">
                        <span>Pasang Aplikasi SAE</span>
                        <span class="sae-pwa-banner-badge">Resmi</span>
                    </div>
                    <div class="sae-pwa-banner-desc">
                        Akses lebih cepat, terminal presensi realtime, dan hemat kuota langsung di layar utama.
                    </div>
                </div>
                <button type="button" class="sae-pwa-banner-close" onclick="dismissPwaInstallPrompt()" aria-label="Tutup">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="sae-pwa-banner-actions">
                <button type="button" class="btn-pwa-action-dismiss" onclick="dismissPwaInstallPrompt()">
                    Nanti Saja
                </button>
                <button type="button" class="btn-pwa-action-install" onclick="installSaePwa()">
                    <i class="fas fa-download"></i> Pasang Sekarang
                </button>
            </div>
        `;

        document.body.appendChild(banner);
    }

    // Fungsi Global untuk Menutup Penawaran Instalasi
    window.dismissPwaInstallPrompt = function () {
        const banner = document.getElementById('saePwaInstallBanner');
        if (banner) {
            banner.style.opacity = '0';
            banner.style.transform = 'translateY(20px)';
            setTimeout(() => {
                banner.remove();
            }, 300);
        }
        localStorage.setItem('sae_pwa_install_dismissed', Date.now().toString());
    };

    // 5. Tangani Event Instalasi PWA (beforeinstallprompt)
    window.addEventListener('beforeinstallprompt', (e) => {
        // Mencegah prompt mini-infobar default browser agar penawaran kustom kita yang tampil rapi
        e.preventDefault();
        deferredPrompt = e;
        console.log('[SAE-PWA] beforeinstallprompt event captured');

        if (!isStandalone) {
            updateInstallUI(true);
            // Tampilkan penawaran halaman setelah jeda 1.5 detik
            setTimeout(() => {
                showInstallPromptBanner();
            }, 1500);
        }
    });

    // Fungsi Global untuk Memanggil Prompt Instalasi Native
    window.installSaePwa = async function () {
        const banner = document.getElementById('saePwaInstallBanner');

        if (!deferredPrompt) {
            // Panduan alternatif jika browser belum memicu prompt otomatis (misal iOS Safari / Chrome tertentu)
            if (window.Swal) {
                Swal.fire({
                    title: 'Pasang Aplikasi SAE',
                    html: `
                        <div style="text-align: left; font-size: 0.9rem; line-height: 1.6; color: var(--text-main, #f8fafc);">
                            <p style="margin-bottom: 12px;">Untuk menambahkan aplikasi SAE ke layar utama perangkat Anda:</p>
                            <ol style="padding-left: 20px; margin-bottom: 12px;">
                                <li style="margin-bottom: 6px;">Ketuk menu browser (<strong>titik tiga ⋮</strong> di Chrome atau ikon <strong>Bagikan 📤</strong> di Safari).</li>
                                <li style="margin-bottom: 6px;">Pilih <strong>"Tambahkan ke Layar Utama"</strong> (Add to Home Screen) atau <strong>"Instal Aplikasi"</strong>.</li>
                                <li>Konfirmasi dengan menekan <strong>Tambahkan / Instal</strong>.</li>
                            </ol>
                            <p style="font-size: 0.8rem; color: var(--text-muted, #94a3b8); margin: 0;">Ikon SAE akan langsung terpasang di beranda handphone Anda.</p>
                        </div>
                    `,
                    icon: 'info',
                    confirmButtonText: 'Mengerti',
                    confirmButtonColor: '#4f6ef7'
                });
            } else {
                alert('Untuk memasang di perangkat Anda, buka menu browser (titik tiga atau tombol Share) lalu pilih "Tambahkan ke Layar Utama" (Add to Home Screen).');
            }
            if (banner) banner.remove();
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
        if (banner) banner.remove();
    };

    // Event saat aplikasi telah berhasil diinstal
    window.addEventListener('appinstalled', () => {
        console.log('[SAE-PWA] Application was successfully installed!');
        deferredPrompt = null;
        updateInstallUI(false);

        const banner = document.getElementById('saePwaInstallBanner');
        if (banner) banner.remove();

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

        // Munculkan penawaran halaman secara elegan setelah 3 detik jika belum standalone
        if (!isStandalone) {
            setTimeout(() => {
                showInstallPromptBanner();
            }, 3000);
        }
    });

})();
