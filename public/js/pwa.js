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
            const swPath = (window.__SAE_PWA__ && window.__SAE_PWA__.swUrl) || '/sw.js';
            const swScope = (window.__SAE_PWA__ && window.__SAE_PWA__.scope) || '/';

            navigator.serviceWorker.register(swPath, { scope: swScope })
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
        // Mencegah mini-infobar default browser agar banner kustom SAE yang tampil rapi
        e.preventDefault();
        deferredPrompt = e;
        console.log('[SAE-PWA] Native beforeinstallprompt captured and ready!');

        if (!isStandalone) {
            updateInstallUI(true);
            // Tampilkan penawaran halaman setelah jeda 1 detik
            setTimeout(() => {
                showInstallPromptBanner();
            }, 1000);
        }
    });

    // Fungsi Global untuk Memanggil Prompt Instalasi Native
    window.installSaePwa = async function () {
        const banner = document.getElementById('saePwaInstallBanner');
        const installBtn = banner ? banner.querySelector('.btn-pwa-action-install') : document.querySelector('.btn-pwa-install');
        const originalBtnHtml = installBtn ? installBtn.innerHTML : '';

        if (installBtn) {
            installBtn.disabled = true;
            installBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyiapkan...';
        }

        const isSecure = window.isSecureContext || location.protocol === 'https:' || location.hostname === 'localhost' || location.hostname === '127.0.0.1';

        // Jika deferredPrompt belum ada tetapi konteks aman (HTTPS/localhost), tunggu sejenak untuk menangkap event
        if (!deferredPrompt && isSecure) {
            let attempts = 0;
            while (!deferredPrompt && attempts < 5) {
                await new Promise((resolve) => setTimeout(resolve, 500));
                attempts++;
            }
        }

        // Jika deferredPrompt siap, panggil dialog instalasi native browser
        if (deferredPrompt) {
            try {
                deferredPrompt.prompt();
                const { outcome } = await deferredPrompt.userChoice;
                console.log('[SAE-PWA] User response to install prompt:', outcome);

                if (outcome === 'accepted') {
                    console.log('[SAE-PWA] User accepted the install prompt');
                    if (banner) banner.remove();
                    updateInstallUI(false);
                }
            } catch (err) {
                console.warn('[SAE-PWA] Error launching native prompt:', err);
            } finally {
                deferredPrompt = null;
                if (installBtn) {
                    installBtn.disabled = false;
                    installBtn.innerHTML = originalBtnHtml;
                }
            }
            return;
        }

        // Kembalikan tombol ke keadaan normal
        if (installBtn) {
            installBtn.disabled = false;
            installBtn.innerHTML = originalBtnHtml;
        }

        // Jika deferredPrompt tidak tersedia (misal: koneksi HTTP lokal di Android, iOS Safari, dll.)
        const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
        const isAndroid = /Android/i.test(navigator.userAgent);

        let guideHtml = '';

        if (!isSecure && isAndroid) {
            guideHtml = `
                <div style="text-align: left; font-size: 0.88rem; line-height: 1.6; color: var(--text-main, #f8fafc);">
                    <div style="background: rgba(79, 110, 247, 0.1); border: 1px solid rgba(79, 110, 247, 0.25); border-radius: 10px; padding: 10px 14px; margin-bottom: 14px; display: flex; align-items: flex-start; gap: 10px;">
                        <i class="fas fa-mobile-screen" style="color: var(--primary, #4f6ef7); font-size: 1.3rem; margin-top: 2px;"></i>
                        <div style="font-size: 0.82rem; color: var(--text-muted, #94a3b8); line-height: 1.4;">
                            Aplikasi SAE siap dipasang langsung ke layar beranda HP Anda!
                        </div>
                    </div>
                    <p style="margin-bottom: 8px; font-weight: 700; color: var(--text-main, #f8fafc);">Langkah Pemasangan (Chrome Android):</p>
                    <ol style="padding-left: 20px; margin-bottom: 14px;">
                        <li style="margin-bottom: 8px;">Ketuk menu <strong>titik tiga (⋮)</strong> di pojok kanan atas browser Chrome.</li>
                        <li style="margin-bottom: 8px;">Pilih <strong>"Tambahkan ke Layar Utama"</strong> (atau <strong>"Instal Aplikasi"</strong>).</li>
                        <li>Ketuk <strong>Instal / Tambahkan</strong> untuk mengonfirmasi.</li>
                    </ol>
                    <p style="font-size: 0.76rem; color: var(--text-muted, #94a3b8); margin: 0; line-height: 1.4;">
                        <em>Catatan: Karena saat ini dibuka melalui IP lokal (HTTP), Chrome membatasi dialog 1-klik otomatis, namun pemasangan via menu titik tiga tetap berfungsi penuh dan membuat ikon resmi di layar utama.</em>
                    </p>
                </div>
            `;
        } else if (isIOS) {
            guideHtml = `
                <div style="text-align: left; font-size: 0.88rem; line-height: 1.6; color: var(--text-main, #f8fafc);">
                    <p style="margin-bottom: 10px; font-weight: 700; color: var(--text-main, #f8fafc);">Langkah Pemasangan di iPhone / iPad:</p>
                    <ol style="padding-left: 20px; margin-bottom: 14px;">
                        <li style="margin-bottom: 8px;">Ketuk ikon <strong>Bagikan (Share 📤)</strong> di bilah navigasi Safari.</li>
                        <li style="margin-bottom: 8px;">Gulir ke bawah dan pilih <strong>"Tambah ke Layar Utama" (Add to Home Screen)</strong>.</li>
                        <li>Ketuk <strong>Tambah</strong> di pojok kanan atas.</li>
                    </ol>
                    <p style="font-size: 0.76rem; color: var(--text-muted, #94a3b8); margin: 0;">Ikon aplikasi SAE akan langsung muncul di beranda iOS Anda.</p>
                </div>
            `;
        } else {
            guideHtml = `
                <div style="text-align: left; font-size: 0.88rem; line-height: 1.6; color: var(--text-main, #f8fafc);">
                    <p style="margin-bottom: 10px;">Untuk memasang aplikasi SAE di perangkat Anda:</p>
                    <ol style="padding-left: 20px; margin-bottom: 14px;">
                        <li style="margin-bottom: 8px;">Buka menu peramban browser Anda (<strong>titik tiga ⋮</strong>).</li>
                        <li style="margin-bottom: 8px;">Pilih <strong>"Instal Aplikasi"</strong> atau <strong>"Tambahkan ke Layar Utama"</strong>.</li>
                        <li>Konfirmasi pemasangan.</li>
                    </ol>
                </div>
            `;
        }

        if (window.Swal) {
            Swal.fire({
                title: 'Pasang Aplikasi SAE',
                html: guideHtml,
                imageUrl: '/img/icons/icon-96x96.png',
                imageWidth: 54,
                imageHeight: 54,
                imageAlt: 'SAE App Icon',
                confirmButtonText: '<i class="fas fa-check"></i> Saya Mengerti',
                confirmButtonColor: '#4f6ef7',
                background: 'var(--bg-card, #131b2e)',
                color: 'var(--text-main, #f8fafc)'
            });
        } else {
            alert('Buka menu browser (titik tiga) lalu pilih "Tambahkan ke Layar Utama" / "Instal Aplikasi" untuk memasang SAE.');
        }

        if (banner) banner.remove();
    };

    // Event saat aplikasi telah berhasil diinstal (baik via prompt maupun via menu browser!)
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
                text: 'SAE telah berhasil ditambahkan ke layar utama perangkat Anda.',
                timer: 3500,
                showConfirmButton: false,
                background: 'var(--bg-card, #131b2e)',
                color: 'var(--text-main, #f8fafc)'
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

        // Jika belum standalone, rencanakan pemunculan penawaran instalasi
        if (!isStandalone) {
            const isSecure = window.isSecureContext || location.protocol === 'https:' || location.hostname === 'localhost' || location.hostname === '127.0.0.1';
            
            // Pada konteks aman (HTTPS/localhost), beri jeda waktu untuk beforeinstallprompt tertangkap terlebih dahulu (4.5 detik)
            // Pada konteks insecure (HTTP LAN), munculkan banner setelah 3.5 detik dengan panduan menu
            const delay = isSecure ? 4500 : 3500;
            setTimeout(() => {
                if (!isStandalone) {
                    showInstallPromptBanner();
                }
            }, delay);
        }
    });

})();
