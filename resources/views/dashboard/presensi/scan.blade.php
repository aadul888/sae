<!DOCTYPE html>
<html lang="id" data-theme="dark">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Terminal Presensi &amp; Scanner RFID — SAE</title>

    <!-- Local CSS & Fonts -->
    <link rel="stylesheet" href="{{ asset('css/fonts.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">

    <!-- SAE Design System & Presensi CSS with Cache Busting -->
    <script>
        (function() {
            const t = localStorage.getItem('sae_theme') || (window.matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark');
            if (t === 'light') document.documentElement.setAttribute('data-theme', 'light');
        })();
    </script>
    <link rel="stylesheet"
        href="{{ asset('css/sae.css') }}?v={{ file_exists(public_path('css/sae.css')) ? filemtime(public_path('css/sae.css')) : time() }}">
    <link rel="stylesheet"
        href="{{ asset('css/dashboard.css') }}?v={{ file_exists(public_path('css/dashboard.css')) ? filemtime(public_path('css/dashboard.css')) : time() }}">
    <link rel="stylesheet"
        href="{{ asset('css/presensi.css') }}?v={{ file_exists(public_path('css/presensi.css')) ? filemtime(public_path('css/presensi.css')) : time() }}">
    <!-- SweetAlert2 (bundle CSS+JS): wajib di head sebelum sae.js agar shim tidak aktif -->
    <script src="{{ asset('vendor/sweetalert2/sweetalert2.all.min.js') }}"></script>
    <link rel="icon" type="image/png" href="{{ asset('img/logo-icon.png') }}?v={{ @filemtime(public_path('img/logo-icon.png')) ?: '1' }}">
    <link rel="shortcut icon" href="{{ asset('favicon.png') }}?v={{ @filemtime(public_path('favicon.png')) ?: '1' }}">
    @include('partials.pwa-head')
</head>

<body class="kiosk-wrapper">
    <!-- Kiosk Header Bar -->
    <header class="kiosk-header">
        <div class="kiosk-header-left">
            @php
                $kioskLogoDark = asset('img/logo-dark.png') . '?v=' . (@filemtime(public_path('img/logo-dark.png')) ?: '1');
                $kioskLogoLight = asset('img/logo-light.png') . '?v=' . (@filemtime(public_path('img/logo-light.png')) ?: '1');
            @endphp
            <a href="{{ route('presensi.kiosk.lock') }}?redirect={{ urlencode(url('/')) }}" class="kiosk-lock-link" title="Kembali ke Beranda" style="display: inline-flex; align-items: center; text-decoration: none;">
                <img src="{{ $kioskLogoDark }}" alt="SAE Logo" class="kiosk-logo" id="navLogo"
                    data-dark="{{ $kioskLogoDark }}" data-light="{{ $kioskLogoLight }}"
                    onerror="this.onerror=null; this.src='/img/logo-dark.png';">
            </a>
            <div class="kiosk-title-wrap">
                <h1 class="kiosk-header-title">
                    <span>Terminal Scanner Presensi</span>
                    <span class="badge badge-primary badge-live-station">LIVE STATION</span>
                </h1>
                <div class="kiosk-header-subtitle">
                    Sistem Pemindaian Kartu RFID &amp; Visual Live
                </div>
            </div>
        </div>

        <div class="kiosk-header-right">
            <div class="kiosk-clock-wrap">
                <div class="kiosk-clock" id="kioskLiveClock">--:--:--</div>
                <div class="kiosk-date">
                    {{ now()->translatedFormat('l, d F Y') }}
                </div>
            </div>

            <div class="kiosk-header-actions">
                <button type="button" id="themeToggleBtn" class="btn btn-outline btn-kiosk-action theme-toggle-btn"
                    aria-label="Ganti Tema" title="Ganti Mode Gelap / Terang">
                    <i class="fas fa-moon"></i>
                </button>
                <button type="button" id="btnToggleSpeech" class="btn btn-primary btn-kiosk-action" title="Suara Aktif (Klik untuk membisukan)">
                    <i class="fas fa-volume-high"></i>
                </button>
                <a href="{{ route('presensi.kiosk.lock') }}" id="btnKioskLock" class="btn btn-danger btn-kiosk-action" title="Kunci Sistem (Power Off Terminal)">
                    <i class="fas fa-power-off"></i>
                </a>
            </div>
        </div>
    </header>

    @if (isset($statusHari) && $statusHari['mode'] === 'libur')
        <div class="card"
            style="margin-bottom: 20px; border-left: 4px solid var(--danger); background: rgba(239, 68, 68, 0.1); padding: 14px 20px; text-align: center;">
            <div style="font-weight: 800; font-size: 1rem; color: var(--danger);">
                <i class="fas fa-umbrella-beach me-2"></i> HARI LIBUR SEKOLAH:
                {{ $agendaLibur ? $agendaLibur->nama_kegiatan : 'Kalender Akademik' }}
            </div>
            <div style="font-size: 0.82rem; color: var(--text-muted); margin-top: 2px;">
                Terminal presensi tidak menerima pencatatan kehadiran pada hari libur resmi.
            </div>
        </div>
    @elseif (!$isHariAktif)
        <div class="card"
            style="margin-bottom: 20px; border-left: 4px solid var(--warning); background: rgba(245, 158, 11, 0.1); padding: 14px 20px; text-align: center;">
            <div style="font-weight: 800; font-size: 1rem; color: var(--warning);">
                <i class="fas fa-calendar-minus me-2"></i> HARI NON-AKTIF BELAJAR: HARI {{ strtoupper(now()->translatedFormat('l')) }}
            </div>
            <div style="font-size: 0.82rem; color: var(--text-muted); margin-top: 2px;">
                Hari ini tidak termasuk dalam jadwal hari aktif belajar sekolah. Terminal scanner dalam mode siaga.
            </div>
        </div>
    @elseif (isset($statusHari) && $statusHari['mode'] === 'daring')
        <div class="card"
            style="margin-bottom: 20px; border-left: 4px solid #3b82f6; background: rgba(59, 130, 246, 0.1); padding: 14px 20px; text-align: center;">
            <div style="font-weight: 800; font-size: 1rem; color: #3b82f6;">
                <i class="fas fa-laptop-house me-2"></i> PEMBELAJARAN DARING (PJJ):
                {{ $agendaLibur ? $agendaLibur->nama_kegiatan : 'Jadwal PJJ / Daring' }}
            </div>
            <div style="font-size: 0.82rem; color: var(--text-muted); margin-top: 2px;">
                Hari ini kegiatan belajar mengajar dilaksanakan secara daring (PJJ). Terminal presensi gerbang sekolah
                dinonaktifkan.
            </div>
        </div>
    @endif

    <!-- Geolocation Configuration for Kiosk Terminal -->
    <div id="kioskGeoConfig" data-require-location="{{ $requireLocation ? '1' : '0' }}"
        data-school-lat="{{ $schoolLat ?? '' }}" data-school-lon="{{ $schoolLon ?? '' }}"
        data-school-radius="{{ $schoolRadius }}" style="display: none;">
    </div>

    <!-- Main Kiosk Body -->
    <main class="kiosk-grid">
        <!-- Left Column: Camera Viewfinder & Scanner Station -->
        <div class="kiosk-scanner-card">
            <!-- Mode Switcher (Modern Segmented Bar) -->
            <!-- Mode Switcher (Modern Segmented Bar - Icon Only) -->
            <div class="kiosk-mode-pills">
                <label class="kiosk-mode-pill" title="Mode Otomatis (Masuk/Pulang Otomatis)">
                    <input type="radio" name="kiosk_mode" value="auto" checked>
                    <span class="pill-content">
                        <i class="fas fa-rotate"></i>
                    </span>
                </label>
                <label class="kiosk-mode-pill" title="Presensi Masuk">
                    <input type="radio" name="kiosk_mode" value="masuk">
                    <span class="pill-content">
                        <i class="fas fa-right-to-bracket"></i>
                    </span>
                </label>
                <label class="kiosk-mode-pill" title="Presensi Pulang">
                    <input type="radio" name="kiosk_mode" value="pulang">
                    <span class="pill-content">
                        <i class="fas fa-right-from-bracket"></i>
                    </span>
                </label>
            </div>

            <!-- Live Camera Viewfinder -->
            <div class="camera-container">
                <video id="cameraVideo" class="camera-video" autoplay playsinline muted></video>
                <canvas id="snapshotCanvas" style="display: none;"></canvas>

                <div class="scanner-target-frame">
                    <div class="scanner-laser-line"></div>
                </div>

                <div class="kiosk-status-indicators">
                    <span id="cameraStatusBadge" class="badge-status-icon status-ok" title="Kamera Siap">
                        <i class="fas fa-video"></i>
                    </span>
                    <button type="button" id="btnSwitchCamera" class="badge-status-icon btn-cam-switch" title="Ganti Kamera Depan / Belakang" style="display: none;">
                        <i class="fas fa-camera-rotate"></i>
                    </button>
                    <span id="rfidStatusBadge" class="badge-status-icon status-ok" title="RFID Online (Siap Memindai)">
                        <i class="fas fa-wifi"></i>
                    </span>
                    <span id="gpsStatusBadge" class="badge-status-icon status-warn" title="GPS: Menghubungkan...">
                        <i class="fas fa-location-crosshairs"></i>
                    </span>
                </div>

                <!-- Overlay Pesan Error/Izin Kamera -->
                <div id="cameraNoticeOverlay" class="camera-notice-overlay" style="display: none;">
                    <i id="cameraNoticeIcon" class="fas fa-video-slash" style="font-size: 2.2rem; margin-bottom: 10px; color: var(--danger);"></i>
                    <div id="cameraNoticeTitle" style="font-weight: 800; font-size: 0.95rem; margin-bottom: 6px; color: var(--text-color);">Kamera Tidak Aktif</div>
                    <div id="cameraNoticeDesc" style="font-size: 0.78rem; max-width: 340px; color: var(--text-muted); line-height: 1.45;"></div>
                    <button type="button" id="btnRetryCamera" class="btn btn-primary btn-sm" style="margin-top: 14px; padding: 6px 16px; font-size: 0.8rem; border-radius: 8px; display: inline-flex; align-items: center; gap: 6px;">
                        <i class="fas fa-rotate-right"></i> <span>Coba Aktifkan Lagi</span>
                    </button>
                </div>
            </div>

            <!-- Instruction Box -->
            <div style="margin-top: 18px; text-align: center;">
                <div style="font-size: 1.15rem; font-weight: 800; color: var(--text-color); margin-bottom: 4px;">
                    <i class="fas fa-id-card text-primary me-2"></i> Tempelkan Kartu RFID atau Arahkan QR Code
                </div>
                <div style="font-size: 0.82rem; color: var(--text-muted); max-width: 440px;">
                    Sistem mendeteksi secara instan. Pastikan wajah terlihat di area kamera untuk verifikasi visual
                    kehadiran ganda.
                </div>
            </div>

            <!-- Hidden Focus Target for USB RFID & Barcode Reader (Caret hidden) -->
            <input type="text" id="kioskScannerInput" class="kiosk-scanner-input-hidden"
                autocomplete="off" spellcheck="false" inputmode="none" autofocus>


        </div>

        <!-- Right Column: Status Summary & Live Recent Scans Feed -->
        <div style="display: flex; flex-direction: column; gap: 20px;">
            <!-- Jadwal Info Card -->
            <div class="card" style="padding: 18px; border-radius: 16px;">
                @if ($isLibur)
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                        <div style="font-size: 0.8rem; font-weight: 700; color: var(--danger); text-transform: uppercase;">
                            <i class="fas fa-umbrella-beach me-1"></i> Jadwal Operasional Hari Ini
                        </div>
                        <span class="badge badge-danger" style="font-size: 0.7rem; font-weight: 700;">LIBUR SEKOLAH</span>
                    </div>
                    <div style="background: rgba(239,68,68,0.08); padding: 12px 14px; border-radius: 10px; border: 1px dashed rgba(239,68,68,0.3); text-align: center;">
                        <div style="color: #ef4444; font-weight: 800; font-size: 0.95rem; margin-bottom: 3px;">
                            <i class="fas fa-calendar-xmark me-1"></i> Tidak Beroperasi (Hari Libur)
                        </div>
                        <div style="color: var(--text-muted); font-size: 0.76rem; line-height: 1.4;">
                            {{ $agendaLibur ? $agendaLibur->nama_kegiatan : 'Libur Kalender Akademik' }}. Jam presensi masuk dan pulang tidak berlaku hari ini.
                        </div>
                    </div>
                @elseif (!$isHariAktif)
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                        <div style="font-size: 0.8rem; font-weight: 700; color: var(--warning); text-transform: uppercase;">
                            <i class="fas fa-clock text-warning me-1"></i> Jadwal Operasional Hari Ini
                        </div>
                        <span class="badge badge-warning" style="font-size: 0.7rem; font-weight: 700;">HARI NON-AKTIF</span>
                    </div>
                    <div style="background: rgba(245,158,11,0.08); padding: 12px 14px; border-radius: 10px; border: 1px dashed rgba(245,158,11,0.3); text-align: center;">
                        <div style="color: #f59e0b; font-weight: 800; font-size: 0.95rem; margin-bottom: 3px;">
                            <i class="fas fa-calendar-minus me-1"></i> Tidak Beroperasi (Hari Non-Aktif)
                        </div>
                        <div style="color: var(--text-muted); font-size: 0.76rem; line-height: 1.4;">
                            Hari {{ now()->translatedFormat('l') }} tidak termasuk dalam jadwal hari aktif belajar sekolah.
                        </div>
                    </div>
                @elseif (isset($statusHari) && $statusHari['mode'] === 'daring')
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                        <div style="font-size: 0.8rem; font-weight: 700; color: #06b6d4; text-transform: uppercase;">
                            <i class="fas fa-laptop-house text-accent me-1"></i> Jadwal Operasional Hari Ini
                        </div>
                        <span class="badge badge-primary" style="font-size: 0.7rem; font-weight: 700;">DARING (PJJ)</span>
                    </div>
                    <div style="background: rgba(6,182,212,0.08); padding: 12px 14px; border-radius: 10px; border: 1px dashed rgba(6,182,212,0.3); text-align: center;">
                        <div style="color: #06b6d4; font-weight: 800; font-size: 0.92rem; margin-bottom: 3px;">
                            <i class="fas fa-house-laptop me-1"></i> Pembelajaran Daring (PJJ)
                        </div>
                        <div style="color: var(--text-muted); font-size: 0.76rem; line-height: 1.4;">
                            {{ $agendaLibur ? $agendaLibur->nama_kegiatan : 'Belajar di Rumah' }}. Presensi gerbang dialihkan.
                        </div>
                    </div>
                @else
                    <div
                        style="font-size: 0.8rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 10px;">
                        <i class="fas fa-clock text-primary me-1"></i> Jadwal Operasional Hari Ini
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; font-size: 0.85rem;">
                        <div
                            style="background: rgba(255,255,255,0.03); padding: 10px 12px; border-radius: 10px; border: 1px solid var(--border-color);">
                            <div style="color: var(--text-muted); font-size: 0.72rem;">Batas Masuk Tepat Waktu:</div>
                            <div
                                style="font-weight: 800; font-size: 1.05rem; color: var(--success); font-family: monospace;">
                                {{ substr($pengaturan->jam_masuk_selesai, 0, 5) }} WIB
                            </div>
                        </div>

                        <div
                            style="background: rgba(255,255,255,0.03); padding: 10px 12px; border-radius: 10px; border: 1px solid var(--border-color);">
                            <div style="color: var(--text-muted); font-size: 0.72rem;">Jam Buka Presensi Pulang:</div>
                            <div
                                style="font-weight: 800; font-size: 1.05rem; color: var(--primary); font-family: monospace;">
                                {{ substr($pengaturan->jam_pulang_mulai, 0, 5) }} WIB
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Lokasi & Radius Presensi Card -->
            <div class="card" style="padding: 18px; border-radius: 16px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                    <div
                        style="font-size: 0.8rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                        <i class="fas fa-location-dot text-primary me-1"></i> Lokasi &amp; Radius Presensi
                    </div>
                    @if ($requireLocation)
                        <span class="badge badge-warning" style="font-size: 0.7rem; font-weight: 700;">Wajib
                            Radius</span>
                    @else
                        <span class="badge badge-secondary" style="font-size: 0.7rem; font-weight: 600;">Radius
                            Opsional</span>
                    @endif
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; font-size: 0.85rem;">
                    <div
                        style="background: rgba(255,255,255,0.03); padding: 10px 12px; border-radius: 10px; border: 1px solid var(--border-color);">
                        <div style="color: var(--text-muted); font-size: 0.72rem;">Batas Toleransi:</div>
                        <div
                            style="font-weight: 800; font-size: 1.05rem; color: var(--primary); font-family: monospace;">
                            {{ number_format($schoolRadius, 0, ',', '.') }} Meter
                        </div>
                    </div>

                    <div
                        style="background: rgba(255,255,255,0.03); padding: 10px 12px; border-radius: 10px; border: 1px solid var(--border-color);">
                        <div style="color: var(--text-muted); font-size: 0.72rem;">Jarak Perangkat:</div>
                        <div id="kioskGpsDistanceText"
                            style="font-weight: 800; font-size: 0.92rem; color: var(--text-color); font-family: monospace;">
                            <i class="fas fa-spinner fa-spin me-1" style="font-size: 0.75rem;"></i> Mendeteksi...
                        </div>
                    </div>
                </div>

            </div>

            <!-- Recent Scans Live Feed -->
            <div class="card"
                style="padding: 18px; border-radius: 16px; flex: 1; display: flex; flex-direction: column;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
                    <div
                        style="font-size: 0.9rem; font-weight: 800; color: var(--text-color); display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-bolt text-warning"></i> Presensi Terkini
                    </div>
                    <span class="badge" style="font-size: 0.7rem; font-weight: 600;">Auto-Refresh Live</span>
                </div>

                <div class="recent-scans-list" id="kioskRecentFeed"
                    style="flex: 1; overflow-y: auto; max-height: 420px;">
                    @forelse ($recentScans as $sc)
                        <div class="recent-scan-item">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <img src="{{ $sc->foto_url ?: asset('img/logo-dark.png') }}"
                                    class="recent-scan-avatar" onerror="this.src='/img/logo-dark.png';">
                                <div>
                                    <div style="font-weight: 700; font-size: 0.88rem; color: var(--text-color);">
                                        {{ $sc->nama }}</div>
                                    <div style="font-size: 0.75rem; color: var(--text-muted);">{{ $sc->rombel }}
                                        &bull; NISN: {{ $sc->nisn }}</div>
                                </div>
                            </div>
                            <div style="text-align: right;">
                                <div
                                    style="font-weight: 700; font-family: monospace; font-size: 0.85rem; color: var(--text-color);">
                                    {{ $sc->jam_pulang ? substr($sc->jam_pulang, 0, 5) : substr($sc->jam_masuk, 0, 5) }}
                                    WIB
                                </div>
                                <div>
                                    @if ($sc->status === 'H')
                                        <span class="badge badge-success" style="font-size: 0.7rem;">Hadir</span>
                                    @elseif ($sc->status === 'T')
                                        <span class="badge badge-warning"
                                            style="font-size: 0.7rem;">+{{ $sc->menit_terlambat }}m</span>
                                    @else
                                        <span class="badge badge-primary"
                                            style="font-size: 0.7rem;">{{ $sc->status }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div style="text-align: center; color: var(--text-muted); padding: 30px; font-size: 0.85rem;">
                            Belum ada peserta didik yang melakukan presensi pada sesi ini.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </main>

    <!-- Pop-up Verification Modal / Card (Overlay) -->
    <div id="verifyPopupOverlay" class="verify-popup-overlay">
        <div class="verify-popup-card">
            <img id="popupFotoSiswa" src="{{ asset('img/logo-dark.png') }}" alt="Foto Peserta Didik"
                class="verify-avatar" onerror="this.src='/img/logo-dark.png';">

            <div style="margin-bottom: 8px;">
                <span id="popupStatusBadge" class="badge badge-success"
                    style="font-size: 0.85rem; padding: 6px 14px; font-weight: 800;">
                    <i class="fas fa-check-circle"></i> TEPAT WAKTU
                </span>
            </div>

            <h2 id="popupNamaSiswa"
                style="font-size: 1.35rem; font-weight: 800; color: var(--text-color); margin: 6px 0 2px 0;">
                Nama Peserta Didik
            </h2>
            <div id="popupRombelSiswa"
                style="font-size: 0.95rem; font-weight: 600; color: var(--primary); margin-bottom: 4px;">
                Kelas X RPL 1
            </div>
            <div id="popupNisnSiswa"
                style="font-size: 0.8rem; color: var(--text-muted); font-family: monospace; margin-bottom: 16px;">
                NISN: 0012345678
            </div>

            <div
                style="background: rgba(255,255,255,0.04); border: 1px solid var(--border-color); border-radius: 12px; padding: 12px; margin-bottom: 12px;">
                <div id="popupWaktuPresensi"
                    style="font-size: 1.15rem; font-weight: 800; font-family: monospace; color: var(--text-color);">
                    Pukul 06:45 WIB
                </div>
                <div id="popupPesanDetail" style="font-size: 0.82rem; color: var(--text-muted); margin-top: 4px;">
                    Presensi masuk berhasil dicatat dalam database.
                </div>
                <div id="popupLokasiPresensi"
                    style="display: none; font-size: 0.78rem; color: var(--text-muted); margin-top: 8px; padding-top: 8px; border-top: 1px dashed var(--border-color);">
                    <i class="fas fa-location-dot text-primary me-1"></i> <span id="popupLokasiText"></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer Khusus Kiosk / Presensi -->
    <div class="kiosk-footer-wrap">
        @include('partials.dash-footer')
    </div>

    <!-- Vendor Scripts & Presensi Scan Logic -->
    {{-- sweetalert2.all.min.js sudah dimuat di <head>, tidak perlu dimuat ulang --}}
    <script src="{{ asset('vendor/jsqr/jsqr.min.js') }}"></script>
    <script
        src="{{ asset('js/sae.js') }}?v={{ file_exists(public_path('js/sae.js')) ? filemtime(public_path('js/sae.js')) : time() }}">
    </script>
    <script
        src="{{ asset('js/presensi-scan.js') }}?v={{ file_exists(public_path('js/presensi-scan.js')) ? filemtime(public_path('js/presensi-scan.js')) : time() }}">
    </script>
    <script
        src="{{ asset('js/sae-realtime.js') }}?v={{ file_exists(public_path('js/sae-realtime.js')) ? filemtime(public_path('js/sae-realtime.js')) : time() }}">
    </script>
    <script
        src="{{ asset('js/pwa.js') }}?v={{ file_exists(public_path('js/pwa.js')) ? filemtime(public_path('js/pwa.js')) : time() }}">
    </script>
</body>

</html>
