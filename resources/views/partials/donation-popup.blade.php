@php
    // Iklan donasi dikendalikan pusat (sae-core): tampil hanya bila sekolah
    // belum donatur dan admin pusat mengaktifkan iklan. Ditampilkan sebagai
    // pop-up berkala (maksimal sekali per 6 jam per browser).
    // HANYA di panel admin — bukan di halaman publik/portal siswa/guru.
    $saeAuthUser = session('user');
    $saeRole = is_array($saeAuthUser)
        ? ($saeAuthUser['role'] ?? '')
        : ($saeAuthUser->role ?? '');
    $saeShowAds = $saeRole === 'admin'
        && \App\Services\CoreControlService::showAds()
        && !request()->routeIs('login', 'auth.*', 'installer.*', 'install.*');

    // Tautan donasi: Saweria bila pusat mengonfigurasi username, jika tidak
    // jatuh ke WhatsApp. NPSN ditampilkan agar donatur menuliskannya di kolom
    // pesan → webhook pusat menandai sekolah ini sebagai donatur otomatis.
    $saeSaweriaUser = trim(\App\Services\CoreControlService::saweriaUsername());
    $saeNpsn = \App\Services\CoreControlService::npsn();
    $saeDonateUrl = $saeSaweriaUser !== ''
        ? 'https://saweria.co/' . rawurlencode($saeSaweriaUser)
        : 'https://wa.me/6285860605060';
@endphp

@if ($saeShowAds)
    @once
        <div id="saeDonasiPopup" class="sae-donasi-popup" hidden aria-hidden="true">
            <div class="sae-donasi-popup__backdrop" data-sae-donasi-close></div>
            <div class="sae-donasi-popup__card" role="dialog" aria-modal="true"
                aria-labelledby="saeDonasiTitle" aria-describedby="saeDonasiDesc">
                <button type="button" class="sae-donasi-popup__close" data-sae-donasi-close
                    aria-label="Tutup iklan donasi">
                    <i class="fas fa-times" aria-hidden="true"></i>
                </button>

                <div class="sae-donasi-popup__icon" aria-hidden="true">
                    <i class="fas fa-heart"></i>
                </div>

                <h2 id="saeDonasiTitle" class="sae-donasi-popup__title">Dukung Kelangsungan SAE</h2>
                <p id="saeDonasiDesc" class="sae-donasi-popup__desc">
                    SAE dikembangkan dan dipelihara secara mandiri. Donasi Anda membantu
                    pembaruan fitur, perbaikan bug, dan layanan server tetap berjalan.
                    Iklan ini hilang otomatis setelah donasi tercatat.
                </p>

                @if ($saeNpsn !== '')
                    <p class="sae-donasi-popup__desc" style="margin-top:-6px;">
                        Tulis <b>NPSN {{ $saeNpsn }}</b> di kolom <b>pesan</b> saat berdonasi
                        agar iklan di sistem sekolah ini langsung berhenti.
                    </p>
                @endif

                <div class="sae-donasi-popup__actions">
                    <a href="{{ $saeDonateUrl }}" target="_blank" rel="noopener"
                        class="btn btn-primary sae-donasi-popup__btn" data-sae-donasi-donasi>
                        <i class="fas fa-heart" aria-hidden="true"></i> Donasi Sekarang
                    </a>
                    <button type="button" class="btn btn-outline sae-donasi-popup__btn" data-sae-donasi-close>
                        Nanti Saja
                    </button>
                </div>

                <p class="sae-donasi-popup__hint">
                    Iklan ini muncul berkala paling sering setiap 6 jam.
                </p>
            </div>
        </div>

        <style>
            .sae-donasi-popup {
                position: fixed;
                inset: 0;
                z-index: var(--z-modal, 1200);
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
                opacity: 0;
                transition: opacity 0.2s ease;
            }

            .sae-donasi-popup.is-open {
                opacity: 1;
            }

            .sae-donasi-popup__backdrop {
                position: absolute;
                inset: 0;
                background: rgba(15, 23, 42, 0.6);
                backdrop-filter: blur(2px);
            }

            .sae-donasi-popup__card {
                position: relative;
                width: 100%;
                max-width: 420px;
                background: var(--bg-card, #ffffff);
                color: var(--text-color, #111827);
                border: 1px solid var(--border-color, #e5e7eb);
                border-radius: var(--radius-lg, 12px);
                box-shadow: var(--shadow-lg, 0 20px 40px rgba(15, 23, 42, 0.2));
                padding: 28px 24px 20px;
                text-align: center;
                transform: translateY(12px) scale(0.98);
                transition: transform 0.2s ease;
            }

            .sae-donasi-popup.is-open .sae-donasi-popup__card {
                transform: translateY(0) scale(1);
            }

            .sae-donasi-popup__close {
                position: absolute;
                top: 10px;
                right: 10px;
                width: 34px;
                height: 34px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                border: 1px solid var(--border-color, #e5e7eb);
                border-radius: var(--radius-md, 8px);
                background: transparent;
                color: var(--text-muted, #6b7280);
                cursor: pointer;
                font-size: var(--text-sm, 0.8125rem);
            }

            .sae-donasi-popup__close:hover {
                color: var(--text-color, #111827);
                background: var(--bg-hover, #f9fafb);
            }

            .sae-donasi-popup__icon {
                width: 56px;
                height: 56px;
                margin: 0 auto 14px;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 50%;
                background: rgba(59, 130, 246, 0.15);
                color: var(--primary, #3b82f6);
                font-size: var(--text-xl, 1.25rem);
            }

            .sae-donasi-popup__title {
                font-size: var(--text-lg, 1.125rem);
                font-weight: 800;
                margin: 0 0 8px;
                color: var(--text-color, #111827);
            }

            .sae-donasi-popup__desc {
                font-size: var(--text-sm, 0.8125rem);
                line-height: 1.6;
                color: var(--text-muted, #6b7280);
                margin: 0 0 18px;
            }

            .sae-donasi-popup__actions {
                display: flex;
                gap: 8px;
                flex-wrap: wrap;
                justify-content: center;
            }

            .sae-donasi-popup__btn {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 6px;
                padding: 8px 16px;
                font-size: var(--text-sm, 0.8125rem);
                font-weight: 600;
                text-decoration: none;
            }

            .sae-donasi-popup__hint {
                margin: 14px 0 0;
                font-size: var(--text-2xs, 0.6875rem);
                color: var(--text-muted, #6b7280);
            }

            @media (max-width: 480px) {
                .sae-donasi-popup__actions {
                    flex-direction: column;
                }

                .sae-donasi-popup__btn {
                    width: 100%;
                }
            }
        </style>

        <script>
            (function () {
                var KEY = 'sae_donasi_popup_until';
                var INTERVAL = 6 * 60 * 60 * 1000; // 6 jam
                var DELAY = 4000; // muncul setelah 4 detik
                var el = document.getElementById('saeDonasiPopup');
                if (!el) return;

                var now = Date.now();
                var until = 0;
                try {
                    until = parseInt(localStorage.getItem(KEY) || '0', 10) || 0;
                } catch (e) { /* localStorage diblokir */ }
                if (until > now) return;

                var lastFocus = null;
                var closeTimer = null;

                function setCooldown() {
                    try {
                        localStorage.setItem(KEY, String(Date.now() + INTERVAL));
                    } catch (e) { /* abaikan */ }
                }

                function open() {
                    lastFocus = document.activeElement;
                    el.hidden = false;
                    el.setAttribute('aria-hidden', 'false');
                    requestAnimationFrame(function () {
                        el.classList.add('is-open');
                    });
                    var first = el.querySelector('.sae-donasi-popup__btn');
                    if (first) first.focus();
                }

                function close() {
                    if (el.hidden) return;
                    el.classList.remove('is-open');
                    el.setAttribute('aria-hidden', 'true');
                    clearTimeout(closeTimer);
                    closeTimer = setTimeout(function () { el.hidden = true; }, 220);
                    setCooldown();
                    if (lastFocus && typeof lastFocus.focus === 'function') {
                        lastFocus.focus();
                    }
                }

                el.addEventListener('click', function (e) {
                    var donasi = e.target.closest('[data-sae-donasi-donasi]');
                    if (donasi) {
                        setCooldown();
                        close();
                        return;
                    }
                    if (e.target.closest('[data-sae-donasi-close]')) {
                        e.preventDefault();
                        close();
                    }
                });

                document.addEventListener('keydown', function (e) {
                    if (e.key === 'Escape' && !el.hidden) close();
                });

                setTimeout(open, DELAY);
            })();
        </script>
    @endonce
@endif
