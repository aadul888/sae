<!DOCTYPE html>
<html lang="id" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Terminal Presensi &amp; Scanner RFID — SAE</title>

    <!-- Global CSS & Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- SAE Design System & Presensi CSS with Cache Busting -->
    <link rel="stylesheet" href="{{ asset('css/sae.css') }}?v={{ file_exists(public_path('css/sae.css')) ? filemtime(public_path('css/sae.css')) : time() }}">
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}?v={{ file_exists(public_path('css/dashboard.css')) ? filemtime(public_path('css/dashboard.css')) : time() }}">
    <link rel="stylesheet" href="{{ asset('css/presensi.css') }}?v={{ file_exists(public_path('css/presensi.css')) ? filemtime(public_path('css/presensi.css')) : time() }}">
    <link rel="stylesheet" href="{{ asset('vendor/sweetalert2/sweetalert2.min.css') }}">
</head>
<body class="kiosk-wrapper">
    <!-- Kiosk Header Bar -->
    <header class="kiosk-header">
        <div class="kiosk-header-left">
            <img src="{{ asset('img/logo-dark.png') }}" alt="SAE Logo" class="kiosk-logo" onerror="this.src='/img/logo-dark.png';">
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
                <button type="button" id="btnToggleSpeech" class="btn btn-primary btn-kiosk-action">
                    <i class="fas fa-volume-high"></i> <span class="btn-text">Suara Aktif</span>
                </button>
                <a href="{{ route('dashboard.presensi.index') }}" class="btn btn-outline btn-kiosk-action">
                    <i class="fas fa-arrow-left"></i> <span class="btn-text">Dashboard</span>
                </a>
            </div>
        </div>
    </header>

    @if (isset($statusHari) && $statusHari['mode'] === 'libur')
        <div class="card" style="margin-bottom: 20px; border-left: 4px solid var(--danger); background: rgba(239, 68, 68, 0.1); padding: 14px 20px; text-align: center;">
            <div style="font-weight: 800; font-size: 1rem; color: var(--danger);">
                <i class="fas fa-umbrella-beach me-2"></i> HARI LIBUR SEKOLAH: {{ $agendaLibur ? $agendaLibur->nama_kegiatan : 'Kalender Akademik' }}
            </div>
            <div style="font-size: 0.82rem; color: var(--text-muted); margin-top: 2px;">
                Terminal presensi tidak menerima pencatatan kehadiran pada hari libur resmi.
            </div>
        </div>
    @elseif (isset($statusHari) && $statusHari['mode'] === 'daring')
        <div class="card" style="margin-bottom: 20px; border-left: 4px solid #3b82f6; background: rgba(59, 130, 246, 0.1); padding: 14px 20px; text-align: center;">
            <div style="font-weight: 800; font-size: 1rem; color: #3b82f6;">
                <i class="fas fa-laptop-house me-2"></i> PEMBELAJARAN DARING (PJJ): {{ $agendaLibur ? $agendaLibur->nama_kegiatan : 'Jadwal PJJ / Daring' }}
            </div>
            <div style="font-size: 0.82rem; color: var(--text-muted); margin-top: 2px;">
                Hari ini kegiatan belajar mengajar dilaksanakan secara daring (PJJ). Terminal presensi gerbang sekolah dinonaktifkan.
            </div>
        </div>
    @elseif ($isLibur)
        <div class="card" style="margin-bottom: 20px; border-left: 4px solid var(--danger); background: rgba(239, 68, 68, 0.1); padding: 14px 20px; text-align: center;">
            <div style="font-weight: 800; font-size: 1rem; color: var(--danger);">
                <i class="fas fa-umbrella-beach me-2"></i> HARI LIBUR SEKOLAH: {{ $agendaLibur ? $agendaLibur->nama_kegiatan : 'Kalender Akademik' }}
            </div>
            <div style="font-size: 0.82rem; color: var(--text-muted); margin-top: 2px;">
                Terminal presensi tidak menerima pencatatan kehadiran pada hari libur resmi.
            </div>
        </div>
    @endif

    <!-- Geolocation Configuration for Kiosk Terminal -->
    <div id="kioskGeoConfig" 
         data-require-location="{{ $requireLocation ? '1' : '0' }}"
         data-school-lat="{{ $schoolLat ?? '' }}"
         data-school-lon="{{ $schoolLon ?? '' }}"
         data-school-radius="{{ $schoolRadius }}"
         style="display: none;">
    </div>

    <!-- Main Kiosk Body -->
    <main class="kiosk-grid">
        <!-- Left Column: Camera Viewfinder & Scanner Station -->
        <div class="kiosk-scanner-card">
            <!-- Mode Switcher (Modern Segmented Bar) -->
            <div class="kiosk-mode-pills">
                <label class="kiosk-mode-pill">
                    <input type="radio" name="kiosk_mode" value="auto" checked>
                    <span class="pill-content">
                        <i class="fas fa-rotate"></i>
                        <span class="pill-label-desktop">Mode Otomatis</span>
                        <span class="pill-label-mobile">Otomatis</span>
                    </span>
                </label>
                <label class="kiosk-mode-pill">
                    <input type="radio" name="kiosk_mode" value="masuk">
                    <span class="pill-content">
                        <i class="fas fa-right-to-bracket"></i>
                        <span class="pill-label-desktop">Presensi Masuk</span>
                        <span class="pill-label-mobile">Masuk</span>
                    </span>
                </label>
                <label class="kiosk-mode-pill">
                    <input type="radio" name="kiosk_mode" value="pulang">
                    <span class="pill-content">
                        <i class="fas fa-right-from-bracket"></i>
                        <span class="pill-label-desktop">Presensi Pulang</span>
                        <span class="pill-label-mobile">Pulang</span>
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

                <div style="position: absolute; bottom: 12px; left: 14px; display: flex; gap: 6px; flex-wrap: wrap;">
                    <span id="cameraStatusBadge" class="badge badge-success" style="font-size: 0.72rem; padding: 4px 8px; backdrop-filter: blur(4px);">
                        <i class="fas fa-video"></i> Kamera Siap
                    </span>
                    <span class="badge badge-primary" style="font-size: 0.72rem; padding: 4px 8px; backdrop-filter: blur(4px);">
                        <i class="fas fa-wifi"></i> RFID Online
                    </span>
                    <span id="gpsStatusBadge" class="badge badge-secondary" style="font-size: 0.72rem; padding: 4px 8px; backdrop-filter: blur(4px); background: rgba(15, 23, 42, 0.8); border: 1px solid rgba(255,255,255,0.1); color: #fff;">
                        <i class="fas fa-location-crosshairs"></i> GPS: Menghubungkan...
                    </span>
                </div>
            </div>

            <!-- Instruction Box -->
            <div style="margin-top: 18px; text-align: center;">
                <div style="font-size: 1.15rem; font-weight: 800; color: var(--text-color); margin-bottom: 4px;">
                    <i class="fas fa-id-card text-primary me-2"></i> Tempelkan Kartu RFID atau Arahkan QR Code
                </div>
                <div style="font-size: 0.82rem; color: var(--text-muted); max-width: 440px;">
                    Sistem mendeteksi secara instan. Pastikan wajah terlihat di area kamera untuk verifikasi visual kehadiran ganda.
                </div>
            </div>

            <!-- Hidden Input for RFID USB Keyboard-wedge reader (inputmode none agar keyboard hp tidak muncul) -->
            <input type="text" id="kioskScannerInput" class="kiosk-hidden-input" autocomplete="off" inputmode="none" tabindex="-1">

            <!-- Manual Barcode/NISN Input Fallback -->
            <form id="formManualScan" style="display: flex; gap: 8px; margin-top: 18px; width: 100%; max-width: 440px;">
                <input type="text" id="manualScanInput" placeholder="Ketik NISN atau scan manual..." class="form-control"
                    style="flex: 1; padding: 9px 14px; font-size: 0.85rem; font-family: monospace;">
                <button type="submit" class="btn btn-primary" style="padding: 9px 20px; font-size: 0.85rem; font-weight: 600;">
                    Scan
                </button>
            </form>
        </div>

        <!-- Right Column: Status Summary & Live Recent Scans Feed -->
        <div style="display: flex; flex-direction: column; gap: 20px;">
            <!-- Jadwal Info Card -->
            <div class="card" style="padding: 18px; border-radius: 16px;">
                <div style="font-size: 0.8rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 10px;">
                    <i class="fas fa-clock text-primary me-1"></i> Jadwal Operasional Hari Ini
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; font-size: 0.85rem;">
                    <div style="background: rgba(255,255,255,0.03); padding: 10px 12px; border-radius: 10px; border: 1px solid var(--border-color);">
                        <div style="color: var(--text-muted); font-size: 0.72rem;">Batas Masuk Tepat Waktu:</div>
                        <div style="font-weight: 800; font-size: 1.05rem; color: var(--success); font-family: monospace;">
                            {{ substr($pengaturan->jam_masuk_selesai, 0, 5) }} WIB
                        </div>
                    </div>

                    <div style="background: rgba(255,255,255,0.03); padding: 10px 12px; border-radius: 10px; border: 1px solid var(--border-color);">
                        <div style="color: var(--text-muted); font-size: 0.72rem;">Jam Buka Presensi Pulang:</div>
                        <div style="font-weight: 800; font-size: 1.05rem; color: var(--primary); font-family: monospace;">
                            {{ substr($pengaturan->jam_pulang_mulai, 0, 5) }} WIB
                        </div>
                    </div>
                </div>
            </div>

            <!-- Lokasi & Radius Presensi Card -->
            <div class="card" style="padding: 18px; border-radius: 16px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                    <div style="font-size: 0.8rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                        <i class="fas fa-location-dot text-primary me-1"></i> Lokasi &amp; Radius Presensi
                    </div>
                    @if ($requireLocation)
                        <span class="badge badge-warning" style="font-size: 0.7rem; font-weight: 700;">Wajib Radius</span>
                    @else
                        <span class="badge badge-secondary" style="font-size: 0.7rem; font-weight: 600;">Radius Opsional</span>
                    @endif
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; font-size: 0.85rem;">
                    <div style="background: rgba(255,255,255,0.03); padding: 10px 12px; border-radius: 10px; border: 1px solid var(--border-color);">
                        <div style="color: var(--text-muted); font-size: 0.72rem;">Batas Toleransi:</div>
                        <div style="font-weight: 800; font-size: 1.05rem; color: var(--primary); font-family: monospace;">
                            {{ number_format($schoolRadius, 0, ',', '.') }} Meter
                        </div>
                    </div>

                    <div style="background: rgba(255,255,255,0.03); padding: 10px 12px; border-radius: 10px; border: 1px solid var(--border-color);">
                        <div style="color: var(--text-muted); font-size: 0.72rem;">Jarak Perangkat:</div>
                        <div id="kioskGpsDistanceText" style="font-weight: 800; font-size: 0.92rem; color: var(--text-color); font-family: monospace;">
                            <i class="fas fa-spinner fa-spin me-1" style="font-size: 0.75rem;"></i> Mendeteksi...
                        </div>
                    </div>
                </div>
                @if ($schoolLat && $schoolLon)
                    <div style="margin-top: 10px; font-size: 0.74rem; color: var(--text-muted); display: flex; justify-content: space-between; align-items: center; background: rgba(0,0,0,0.2); padding: 6px 10px; border-radius: 8px;">
                        <span><i class="fas fa-map-pin text-danger me-1"></i> Titik Sekolah: {{ number_format($schoolLat, 5) }}, {{ number_format($schoolLon, 5) }}</span>
                        <a href="https://maps.google.com/?q={{ $schoolLat }},{{ $schoolLon }}" target="_blank" rel="noopener noreferrer" style="color: var(--primary); text-decoration: none; font-weight: 600; font-size: 0.72rem;">
                            Buka Peta <i class="fas fa-external-link-alt ms-1" style="font-size: 0.65rem;"></i>
                        </a>
                    </div>
                @endif
            </div>

            <!-- Recent Scans Live Feed -->
            <div class="card" style="padding: 18px; border-radius: 16px; flex: 1; display: flex; flex-direction: column;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
                    <div style="font-size: 0.9rem; font-weight: 800; color: var(--text-color); display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-bolt text-warning"></i> Presensi Terkini
                    </div>
                    <span class="badge" style="font-size: 0.7rem; font-weight: 600;">Auto-Refresh Live</span>
                </div>

                <div class="recent-scans-list" id="kioskRecentFeed" style="flex: 1; overflow-y: auto; max-height: 420px;">
                    @forelse ($recentScans as $sc)
                        <div class="recent-scan-item">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <img src="{{ $sc->foto_url ?: asset('img/logo-dark.png') }}" class="recent-scan-avatar" onerror="this.src='/img/logo-dark.png';">
                                <div>
                                    <div style="font-weight: 700; font-size: 0.88rem; color: var(--text-color);">{{ $sc->nama }}</div>
                                    <div style="font-size: 0.75rem; color: var(--text-muted);">{{ $sc->rombel }} &bull; NISN: {{ $sc->nisn }}</div>
                                </div>
                            </div>
                            <div style="text-align: right;">
                                <div style="font-weight: 700; font-family: monospace; font-size: 0.85rem; color: var(--text-color);">
                                    {{ $sc->jam_pulang ? substr($sc->jam_pulang, 0, 5) : substr($sc->jam_masuk, 0, 5) }} WIB
                                </div>
                                <div>
                                    @if ($sc->status === 'H')
                                        <span class="badge badge-success" style="font-size: 0.7rem;">Hadir</span>
                                    @elseif ($sc->status === 'T')
                                        <span class="badge badge-warning" style="font-size: 0.7rem;">+{{ $sc->menit_terlambat }}m</span>
                                    @else
                                        <span class="badge badge-primary" style="font-size: 0.7rem;">{{ $sc->status }}</span>
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
            <img id="popupFotoSiswa" src="{{ asset('img/logo-dark.png') }}" alt="Foto Peserta Didik" class="verify-avatar" onerror="this.src='/img/logo-dark.png';">
            
            <div style="margin-bottom: 8px;">
                <span id="popupStatusBadge" class="badge badge-success" style="font-size: 0.85rem; padding: 6px 14px; font-weight: 800;">
                    <i class="fas fa-check-circle"></i> TEPAT WAKTU
                </span>
            </div>

            <h2 id="popupNamaSiswa" style="font-size: 1.35rem; font-weight: 800; color: var(--text-color); margin: 6px 0 2px 0;">
                Nama Peserta Didik
            </h2>
            <div id="popupRombelSiswa" style="font-size: 0.95rem; font-weight: 600; color: var(--primary); margin-bottom: 4px;">
                Kelas X RPL 1
            </div>
            <div id="popupNisnSiswa" style="font-size: 0.8rem; color: var(--text-muted); font-family: monospace; margin-bottom: 16px;">
                NISN: 0012345678
            </div>

            <div style="background: rgba(255,255,255,0.04); border: 1px solid var(--border-color); border-radius: 12px; padding: 12px; margin-bottom: 12px;">
                <div id="popupWaktuPresensi" style="font-size: 1.15rem; font-weight: 800; font-family: monospace; color: var(--text-color);">
                    Pukul 06:45 WIB
                </div>
                <div id="popupPesanDetail" style="font-size: 0.82rem; color: var(--text-muted); margin-top: 4px;">
                    Presensi masuk berhasil dicatat dalam database.
                </div>
                <div id="popupLokasiPresensi" style="display: none; font-size: 0.78rem; color: var(--text-muted); margin-top: 8px; padding-top: 8px; border-top: 1px dashed var(--border-color);">
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
    <script src="{{ asset('vendor/sweetalert2/sweetalert2.all.min.js') }}"></script>
    <script src="{{ asset('js/presensi-scan.js') }}?v={{ file_exists(public_path('js/presensi-scan.js')) ? filemtime(public_path('js/presensi-scan.js')) : time() }}"></script>
</body>
</html>
