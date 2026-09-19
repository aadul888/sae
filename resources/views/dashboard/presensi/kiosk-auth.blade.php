<!DOCTYPE html>
<html lang="id" data-theme="dark">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Otorisasi Terminal Kiosk Presensi — SAE</title>

    <!-- Global CSS & Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- SAE Design System & CSS -->
    <link rel="stylesheet" href="{{ asset('css/sae.css') }}?v={{ file_exists(public_path('css/sae.css')) ? filemtime(public_path('css/sae.css')) : time() }}">
    <link rel="stylesheet" href="{{ asset('vendor/sweetalert2/sweetalert2.min.css') }}">

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
            margin-top: 24px;
            font-size: 0.78rem;
            color: #64748b;
            line-height: 1.45;
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
                <input type="text" id="inputKodeAkses" name="kode_akses" class="pin-input" placeholder="TAP KARTU / KODE AKSES" required autofocus autocomplete="off" spellcheck="false" oninput="this.value = this.value.toUpperCase().replace(/\s/g, '')">
            </div>

            <button type="submit" id="btnSubmitKiosk" class="btn-unlock">
                <i class="fas fa-key"></i> <span>Buka Terminal Pemindai</span>
            </button>
        </form>

        <div class="info-footer">
            <i class="fas fa-shield-halved me-1"></i> Mode Kiosk Publik Terisolasi.<br>
            Dapat dibuka dengan kartu RFID Guru/Tendik terdaftar atau PIN resmi.
        </div>
    </div>

    <script src="{{ asset('vendor/sweetalert2/sweetalert2.all.min.js') }}"></script>
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
            window.addEventListener('click', () => {
                if (input && document.activeElement !== input) input.focus();
            });
            window.addEventListener('keydown', () => {
                if (input && document.activeElement !== input) input.focus();
            });

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

                        const data = await res.json();

                        if (res.ok && data.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Akses Diterima',
                                text: data.message || 'Membuka layar terminal scanner presensi...',
                                timer: 1400,
                                showConfirmButton: false,
                                timerProgressBar: true
                            }).then(() => {
                                window.location.href = data.redirect_url || "{{ route('presensi.scan') }}";
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Akses Ditolak',
                                text: data.message || 'Kode akses atau kartu RFID tidak valid.',
                                confirmButtonColor: '#4f46e5'
                            });
                            input.value = '';
                            input.focus();
                            btn.disabled = false;
                            btn.innerHTML = '<i class="fas fa-key"></i> <span>Buka Terminal Pemindai</span>';
                        }
                    } catch (err) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Kesalahan Server',
                            text: 'Gagal terhubung ke server verifikasi. Silakan periksa jaringan Anda.',
                            confirmButtonColor: '#4f46e5'
                        });
                        btn.disabled = false;
                        btn.innerHTML = '<i class="fas fa-key"></i> <span>Buka Terminal Pemindai</span>';
                    }
                });
            }
        });
    </script>
</body>

</html>
