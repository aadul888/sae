<!DOCTYPE html>
<html lang="id" data-theme="dark">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Otorisasi Terminal Kiosk Presensi — SAE</title>

    <!-- Local CSS & Fonts -->
    <link rel="stylesheet" href="{{ asset('css/fonts.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">

    <!-- SAE Design System & CSS -->
    <link rel="stylesheet" href="{{ asset('css/sae.css') }}?v={{ file_exists(public_path('css/sae.css')) ? filemtime(public_path('css/sae.css')) : time() }}">

    <style>
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: radial-gradient(circle at 50% 20%, #1e1b4b 0%, #0f172a 60%, #020617 100%);
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: #f8fafc;
            padding: 20px;
            margin: 0;
            overflow-x: hidden;
            position: relative;
        }

        .auth-card {
            background: rgba(15, 23, 42, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 24px;
            padding: 40px 36px;
            width: 100%;
            max-width: 440px;
            box-shadow: 0 25px 60px -15px rgba(0, 0, 0, 0.7), 0 0 40px rgba(99, 102, 241, 0.15);
            backdrop-filter: blur(20px);
            text-align: center;
            position: relative;
            z-index: 10;
            animation: fadeInScale 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes fadeInScale {
            from {
                opacity: 0;
                transform: scale(0.96) translateY(10px);
            }
            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        .kiosk-auth-header {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            width: 100%;
            margin-bottom: 20px;
            text-align: center;
        }

        .kiosk-brand-logo {
            display: block;
            height: 48px;
            width: auto;
            max-width: 220px;
            object-fit: contain;
            margin: 0 auto 16px auto;
            filter: drop-shadow(0 4px 12px rgba(99, 102, 241, 0.3));
        }

        .kiosk-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 6px 16px;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 700;
            background: rgba(99, 102, 241, 0.18);
            border: 1px solid rgba(99, 102, 241, 0.4);
            color: #a5b4fc;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin: 0 auto 18px auto;
        }

        .kiosk-title {
            font-size: 1.45rem;
            font-weight: 800;
            color: #ffffff;
            margin: 0 0 6px 0;
            letter-spacing: -0.02em;
        }

        .kiosk-subtitle {
            font-size: 0.84rem;
            color: #94a3b8;
            margin: 0 0 20px 0;
            line-height: 1.5;
        }

        .pin-input-wrap {
            position: relative;
            margin-bottom: 24px;
        }

        .pin-input {
            width: 100%;
            padding: 16px 20px;
            font-size: 1.35rem;
            font-weight: 800;
            letter-spacing: 0.25em;
            text-align: center;
            background: rgba(2, 6, 23, 0.6);
            border: 2px solid rgba(99, 102, 241, 0.3);
            border-radius: 14px;
            color: #ffffff;
            outline: none;
            transition: all 0.25s ease;
            box-sizing: border-box;
            text-transform: uppercase;
        }

        .pin-input:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.25);
            background: rgba(2, 6, 23, 0.85);
        }

        .btn-unlock {
            width: 100%;
            padding: 14px 20px;
            background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%);
            color: #ffffff;
            border: none;
            border-radius: 14px;
            font-size: 0.95rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: 0 8px 20px rgba(79, 70, 229, 0.35);
        }

        .btn-unlock:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 28px rgba(79, 70, 229, 0.45);
        }

        .btn-unlock:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .info-footer {
            margin-top: 20px;
            font-size: 0.78rem;
            color: #64748b;
            line-height: 1.45;
        }

        .kiosk-nav-actions {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 18px;
            flex-wrap: wrap;
        }

        .btn-kiosk-nav {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 8px 16px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 10px;
            color: #94a3b8;
            font-size: 0.82rem;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-kiosk-nav:hover {
            background: rgba(255, 255, 255, 0.12);
            border-color: rgba(56, 189, 248, 0.4);
            color: #f8fafc;
            transform: translateY(-1px);
        }

        .live-clock-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-family: monospace;
            font-size: 0.85rem;
            color: #38bdf8;
            background: rgba(56, 189, 248, 0.1);
            padding: 4px 12px;
            border-radius: 20px;
            margin-bottom: 16px;
        }
    </style>
</head>

<body>
    <div class="auth-card">
        <div class="kiosk-auth-header">
            <img src="{{ asset('img/logo-light.png') }}" alt="Logo SAE" class="kiosk-brand-logo" onerror="this.src='/img/logo-light.png';">

            <div class="kiosk-badge">
                <i class="fas fa-id-card-clip"></i> Terminal Scanner Kiosk
            </div>

            <h1 class="kiosk-title">Otorisasi Terminal</h1>
            <div class="kiosk-subtitle">
                <div style="font-weight: 700; color: #cbd5e1; margin-bottom: 2px;">{{ $sekolah->nama ?? 'Sistem Aplikasi Edukasi' }}</div>
                Tempelkan kartu RFID Guru / Tendik atau masukkan kode akses untuk mengaktifkan pemindai.
            </div>

            <div style="display: flex; align-items: center; justify-content: center; gap: 8px; margin-bottom: 12px; font-size: 0.8rem; color: #94a3b8;">
                <i class="fas fa-satellite-dish text-primary"></i>
                <span>Siap memindai kartu RFID fisik</span>
            </div>

            <div class="live-clock-pill">
                <i class="fas fa-clock"></i> <span id="kioskClock">--:--:--</span>
            </div>
        </div>

        <form id="formKioskAuth">
            @csrf
            <div class="pin-input-wrap">
                <input type="password" id="inputKodeAkses" name="kode_akses" class="pin-input" placeholder="••••••••••••" aria-label="KODE AKSES" required autofocus autocomplete="off" spellcheck="false" oninput="this.value = this.value.toUpperCase().replace(/\s/g, '')">
            </div>

            <button type="submit" id="btnSubmitKiosk" class="btn-unlock">
                <i class="fas fa-key"></i> <span>Buka Terminal Pemindai</span>
            </button>
        </form>

        <div class="kiosk-nav-actions">
            <button type="button" onclick="goBackOrHome()" class="btn-kiosk-nav" title="Kembali ke halaman sebelumnya">
                <i class="fas fa-arrow-left"></i> <span>Kembali</span>
            </button>
            <a href="{{ url('/') }}" class="btn-kiosk-nav" title="Kembali ke Beranda Utama / Publik">
                <i class="fas fa-house"></i> <span>Home Publik</span>
            </a>
        </div>

        <div class="info-footer">
            <i class="fas fa-shield-halved me-1"></i> Mode Kiosk Publik Terisolasi.<br>
            Dapat dibuka dengan kartu RFID Guru/Tendik terdaftar atau PIN resmi.
        </div>
    </div>

    <script src="{{ asset('js/sae.js') }}?v={{ file_exists(public_path('js/sae.js')) ? filemtime(public_path('js/sae.js')) : time() }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Live Clock
            const clockEl = document.getElementById('kioskClock');
            function tick() {
                if (!clockEl) return;
                const now = new Date();
                clockEl.textContent = now.toTimeString().split(' ')[0] + ' WIB';
            }
            setInterval(tick, 1000);
            tick();

            // Submit Handler & RFID Auto-Focus
            const form = document.getElementById('formKioskAuth');
            const input = document.getElementById('inputKodeAkses');
            const btn = document.getElementById('btnSubmitKiosk');

            // Pastikan reader RFID selalu mengetik ke input
            // Guard: jangan paksa fokus saat dialog/Swal aktif atau sedang submit
            function shouldAutoFocus() {
                if (typeof Swal !== 'undefined' && Swal.isVisible()) return false;
                if (document.querySelector('.sae-dialog-overlay')) return false;
                return true;
            }
            window.addEventListener('click', (e) => {
                if (input && document.activeElement !== input && shouldAutoFocus()) input.focus();
            });
            window.addEventListener('keydown', (e) => {
                if (input && document.activeElement !== input && shouldAutoFocus()) input.focus();
            });

            // Helper: tampilkan alert dengan fallback jika SAE.alert belum siap
            function showAlert(msg, title, type, timeout) {
                if (window.SAE && typeof window.SAE.alert === 'function') {
                    return window.SAE.alert(msg, title, type, timeout);
                }
                // Fallback native — selalu resolve
                return new Promise((resolve) => {
                    alert(title + '\n' + msg);
                    resolve();
                });
            }

            // Helper: reset tombol ke keadaan semula
            // Catatan: fokus ke input dilakukan SETELAH dialog tertutup agar tidak
            // memicu loop rekursif antara keydown-listener dan SweetAlert2
            function resetBtn(focusInput = false) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-key"></i> <span>Buka Terminal Pemindai</span>';
                if (input) input.value = '';
                if (focusInput && input) input.focus();
            }

            if (form) {
                form.addEventListener('submit', async function (e) {
                    e.preventDefault();
                    const code = input.value.trim().toUpperCase().replace(/\s/g, '');
                    if (!code) {
                        input.focus();
                        return;
                    }

                    btn.disabled = true;
                    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <span>Memvalidasi...</span>';

                    try {
                        const res = await fetch("{{ route('presensi.kiosk.unlock') }}", {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({ kode_akses: code })
                        });

                        // Pastikan response benar-benar JSON (bukan HTML redirect/error page)
                        const contentType = res.headers.get('Content-Type') || '';
                        if (!contentType.includes('application/json')) {
                            // Session expired / redirect — reload paksa agar CSRF token segar
                            resetBtn();
                            await showAlert(
                                'Sesi habis atau server mengarahkan ulang. Halaman akan dimuat ulang.',
                                'Sesi Berakhir',
                                'warning',
                                2000
                            );
                            window.location.reload();
                            return;
                        }

                        const data = await res.json();

                        if (res.ok && data.status === 'success') {
                            btn.innerHTML = '<i class="fas fa-check-circle"></i> <span>Akses Diterima!</span>';
                            await showAlert(
                                data.message || 'Membuka layar terminal scanner presensi...',
                                'Akses Diterima',
                                'success',
                                1200
                            );
                            window.location.href = data.redirect_url || "{{ route('presensi.scan') }}";
                        } else {
                            // Tangani pesan dari errors Laravel (422) maupun message biasa
                            let errMsg = data.message || '';
                            if (!errMsg && data.errors) {
                                errMsg = Object.values(data.errors).flat().join(' ');
                            }
                            errMsg = errMsg || 'Kode akses atau kartu RFID tidak valid.';

                            resetBtn(false); // reset dulu, fokus setelah Swal tutup
                            await showAlert(errMsg, 'Akses Ditolak', 'danger', 2500);
                            if (input) input.focus();
                        }
                    } catch (err) {
                        resetBtn(false);
                        await showAlert(
                            'Gagal terhubung ke server. Periksa jaringan Anda dan coba lagi.',
                            'Kesalahan Jaringan',
                            'danger',
                            2500
                        );
                        if (input) input.focus();
                    }
                });
            }

            window.goBackOrHome = function() {
                if (window.history.length > 1 && document.referrer && !document.referrer.includes('/presensi/scan/lock')) {
                    window.history.back();
                } else {
                    window.location.href = "{{ url('/') }}";
                }
            };
        });
    </script>
</body>

</html>
