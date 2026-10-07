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
    <link rel="stylesheet"
        href="{{ asset('css/sae.css') }}?v={{ file_exists(public_path('css/sae.css')) ? filemtime(public_path('css/sae.css')) : time() }}">
    <link rel="stylesheet"
        href="{{ asset('css/kiosk-auth.css') }}?v={{ file_exists(public_path('css/kiosk-auth.css')) ? filemtime(public_path('css/kiosk-auth.css')) : time() }}">

    <!-- SweetAlert2: wajib dimuat sebelum sae.js agar shim fallback tidak aktif -->
    <script src="{{ asset('vendor/sweetalert2/sweetalert2.all.min.js') }}"></script>
</head>

<body class="kiosk-auth-body">
    <div class="auth-card">
        <div class="kiosk-auth-header">
            <img src="{{ asset('img/logo-light.png') }}" alt="Logo SAE" class="kiosk-brand-logo"
                onerror="this.src='/img/logo-light.png';">

            <div class="kiosk-badge">
                <i class="fas fa-id-card-clip"></i> Terminal Scanner Kiosk
            </div>

            <h1 class="kiosk-title">Otorisasi Terminal</h1>
            <div class="kiosk-subtitle">
                <div style="font-weight: 700; color: #cbd5e1; margin-bottom: 2px;">
                    {{ $sekolah->nama ?? 'Sistem Aplikasi Edukasi' }}</div>
                Tempelkan kartu RFID Guru / Tendik atau masukkan kode akses untuk mengaktifkan pemindai.
            </div>

            <div
                style="display: flex; align-items: center; justify-content: center; gap: 8px; margin-bottom: 12px; font-size: 0.8rem; color: #94a3b8;">
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
                <input type="password" id="inputKodeAkses" name="kode_akses" class="pin-input"
                    placeholder="••••••••••••" aria-label="KODE AKSES" required autofocus autocomplete="off"
                    spellcheck="false" oninput="this.value = this.value.toUpperCase().replace(/\s/g, '')">
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

    <script
        src="{{ asset('js/sae.js') }}?v={{ file_exists(public_path('js/sae.js')) ? filemtime(public_path('js/sae.js')) : time() }}">
    </script>
    <script
        src="{{ asset('js/kiosk-auth.js') }}?v={{ file_exists(public_path('js/kiosk-auth.js')) ? filemtime(public_path('js/kiosk-auth.js')) : time() }}">
    </script>
</body>

</html>
