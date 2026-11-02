<!DOCTYPE html>
<html lang="id" data-theme="dark">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Terminal Kiosk e-Izin Pos Gerbang — SAE</title>

    <link rel="stylesheet" href="{{ asset('css/fonts.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">

    <script src="{{ asset('js/theme-init.js') }}?v={{ @filemtime(public_path('js/theme-init.js')) ?: '1' }}"></script>
    <link rel="stylesheet"
        href="{{ asset('css/sae.css') }}?v={{ file_exists(public_path('css/sae.css')) ? filemtime(public_path('css/sae.css')) : time() }}">
    <link rel="stylesheet"
        href="{{ asset('css/dashboard.css') }}?v={{ file_exists(public_path('css/dashboard.css')) ? filemtime(public_path('css/dashboard.css')) : time() }}">
    <link rel="stylesheet"
        href="{{ asset('css/presensi.css') }}?v={{ file_exists(public_path('css/presensi.css')) ? filemtime(public_path('css/presensi.css')) : time() }}">

    <link rel="icon" type="image/png"
        href="{{ asset('img/logo-icon.png') }}?v={{ @filemtime(public_path('img/logo-icon.png')) ?: '1' }}">

    <link rel="stylesheet"
        href="{{ asset('css/e-izin-kiosk.css') }}?v={{ file_exists(public_path('css/e-izin-kiosk.css')) ? filemtime(public_path('css/e-izin-kiosk.css')) : time() }}">
</head>

<body class="kiosk-wrapper">
    <!-- Header Terminal Kiosk -->
    <header class="kiosk-header">
        <div class="kiosk-header-left">
            <a href="{{ route('e-izin.kiosk.lock') }}?redirect={{ urlencode(url('/')) }}" class="kiosk-lock-link" title="Kunci Terminal &amp; Kembali ke Beranda"
                style="display: inline-flex; align-items: center; text-decoration: none;">
                <img src="{{ asset('img/logo-dark.png') }}" alt="SAE Logo" class="kiosk-logo" id="navLogo"
                    onerror="this.src='/img/logo-dark.png';">
            </a>
            <div class="kiosk-title-wrap">
                <h1 class="kiosk-header-title" style="font-size: var(--text-lg, 1.15rem); margin: 0; display: flex; align-items: center; gap: 8px;">
                    <span>Pos Gerbang e-Izin</span>
                    <span class="badge badge-primary badge-live-station">KIOSK VERIFIKASI</span>
                </h1>
                <div class="kiosk-header-subtitle" style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted);">
                    {{ $sekolah->nama ?? 'Sistem Aplikasi Edukasi' }} &bull; Verifikasi Keluar-Masuk Siswa
                </div>
            </div>
        </div>

        <div class="kiosk-header-right" style="display: flex; align-items: center; gap: 14px;">
            <div class="kiosk-clock-wrap" style="text-align: right;">
                <div class="kiosk-clock" id="kioskLiveClock" style="font-size: var(--text-xl, 1.25rem); font-weight: 800; font-family: monospace;">--:--:--</div>
                <div class="kiosk-date" style="font-size: var(--text-xs, 0.72rem); color: var(--text-muted);">{{ now()->translatedFormat('l, d F Y') }}</div>
            </div>

            <div class="kiosk-header-actions" style="display: flex; gap: 6px;">
                <button type="button" id="btnToggleFullscreen" class="btn btn-outline btn-kiosk-action"
                    title="Layar Penuh (Monitor Kiosk)" aria-label="Layar Penuh">
                    <i class="fas fa-expand"></i>
                </button>
                <button type="button" id="btnToggleSound" class="btn btn-primary btn-kiosk-action"
                    title="Suara Aktif (Klik untuk Bisukan)" aria-label="Suara Aktif">
                    <i class="fas fa-volume-high"></i>
                </button>
                <button type="button" id="themeToggleBtn" class="btn btn-outline btn-kiosk-action theme-toggle-btn"
                    title="Ganti Tema Gelap / Terang" aria-label="Ganti Tema">
                    <i class="fas fa-moon"></i>
                </button>
                <a href="{{ route('dashboard.e-izin.index') }}" class="btn btn-outline btn-kiosk-action"
                    title="Buka Dashboard e-Izin" aria-label="Buka Dashboard">
                    <i class="fas fa-id-card"></i>
                </a>
                <a href="{{ route('e-izin.kiosk.lock') }}" id="btnKioskLock" class="btn btn-danger btn-kiosk-action"
                    title="Kunci Terminal Pos Gerbang (Power Off)" aria-label="Kunci Terminal">
                    <i class="fas fa-power-off"></i>
                </a>
            </div>
        </div>
    </header>

    <!-- Main Kiosk Grid (Fit Screen) -->
    <div class="kiosk-izin-grid">
        <!-- Station Kiri: Pemindai & Interaksi Utama -->
        <div class="kiosk-scan-box">
            <div style="width: 100%; text-align: center;">
                <h2 class="scan-prompt-title" id="scanPromptTitle">
                    SIAP TAP KARTU / SCAN TIKET
                </h2>
                <p class="scan-prompt-desc">
                    Arahkan QR Code / Barcode tiket e-Izin ke kamera, atau tempelkan kartu RFID pada scanner.
                </p>
            </div>

            <!-- Video Kamera Viewfinder Live -->
            <div class="camera-container-kiosk" id="cameraWrap">
                <video id="videoPreview" class="camera-video-kiosk" autoplay playsinline muted></video>
                <div class="scanner-laser-kiosk"></div>

                <!-- Corner Target Frame -->
                <div class="scanner-frame-corner frame-tl"></div>
                <div class="scanner-frame-corner frame-tr"></div>
                <div class="scanner-frame-corner frame-bl"></div>
                <div class="scanner-frame-corner frame-br"></div>

                <!-- Kontrol Kamera -->
                <div class="kiosk-cam-controls">
                    <span id="cameraStatusBadge" class="kiosk-cam-badge status-ok" title="Kamera Siap">
                        <i class="fas fa-video"></i>
                    </span>
                    <button type="button" id="btnSwitchCamera" class="kiosk-cam-badge btn-cam-action"
                        title="Ganti Kamera Depan / Belakang" style="display: none;">
                        <i class="fas fa-camera-rotate"></i>
                    </button>
                    <button type="button" id="btnToggleCamera" class="kiosk-cam-badge btn-cam-action"
                        title="Aktifkan / Matikan Kamera">
                        <i class="fas fa-power-off"></i>
                    </button>
                </div>

                <!-- Overlay Pesan Kamera Tertahan -->
                <div id="cameraNoticeOverlay" class="camera-notice-kiosk" style="display: none;">
                    <i id="cameraNoticeIcon" class="fas fa-video-slash"
                        style="font-size: var(--text-3xl, 2rem); color: var(--danger); margin-bottom: 6px;"></i>
                    <div id="cameraNoticeTitle"
                        style="font-weight: 800; font-size: var(--text-md, 0.95rem); color: #fff; margin-bottom: 4px;">
                        Kamera Memerlukan Akses
                    </div>
                    <div id="cameraNoticeDesc"
                        style="font-size: var(--text-xs, 0.75rem); color: #cbd5e1; max-width: 320px; line-height: 1.4;">
                        Klik tombol di bawah untuk memberikan izin akses kamera perangkat.
                    </div>
                    <button type="button" id="btnRetryCamera" class="btn btn-primary btn-sm"
                        style="margin-top: 10px; font-size: var(--text-xs, 0.75rem); padding: 6px 16px; border-radius: var(--radius-md, 8px);">
                        <i class="fas fa-rotate-right me-1"></i> Coba Aktifkan Kamera
                    </button>
                </div>
            </div>

            <!-- Input Tangkapan Scan (Otomatis Fokus / Keyboard Wedge RFID & Barcode Scanner) -->
            <form id="formKioskScan" class="manual-input-wrap" onsubmit="return false;">
                <input type="text" id="kioskInputCode" class="manual-input"
                    placeholder="Menunggu input RFID / Barcode..." autofocus autocomplete="off">
                <button type="submit" class="btn btn-primary" id="btnSubmitScan"
                    style="height: 44px; border-radius: var(--radius-lg, 12px); padding: 0 18px;" title="Kirim Kode">
                    <i class="fas fa-arrow-right"></i>
                </button>
            </form>

            <!-- Panduan SOP 4 Pihak -->
            <div class="sop-note-box">
                <div style="font-weight: 800; color: var(--text-color); margin-bottom: 3px; display: flex; align-items: center; gap: 6px;">
                    <i class="fas fa-shield-halved text-primary"></i> SOP Gerbang Keamanan Ketat:
                </div>
                <div>1. Siswa wajib disetujui <strong>Wali Kelas</strong> &amp; <strong>Guru Piket</strong> sebelum gerbang terbuka.</div>
                <div>2. Tap gerbang pertama: <strong>Verifikasi Keluar</strong> &bull; Tap kedua: <strong>Verifikasi Masuk</strong>.</div>
            </div>
        </div>

        <!-- Kolom Kanan: Statistik Ringkas, Chart Informatif & Live Feed Gerbang -->
        <div class="kiosk-right-col">
            <!-- Stat Mini Cards -->
            <div class="kiosk-stats-row">
                <div class="stat-mini-card">
                    <div class="stat-mini-val" id="statTotal">{{ $stats['total'] }}</div>
                    <div class="stat-mini-lbl">Izin Hari Ini</div>
                </div>
                <div class="stat-mini-card" style="border-left: 3px solid #ea580c;">
                    <div class="stat-mini-val" style="color: #ea580c;" id="statDiLuar">{{ $stats['di_luar'] }}</div>
                    <div class="stat-mini-lbl">Sedang di Luar</div>
                </div>
                <div class="stat-mini-card" style="border-left: 3px solid var(--success, #10b981);">
                    <div class="stat-mini-val" style="color: var(--success, #10b981);" id="statKembali">{{ $stats['kembali'] }}</div>
                    <div class="stat-mini-lbl">Sudah Kembali</div>
                </div>
                <div class="stat-mini-card" style="border-left: 3px solid #6366f1;">
                    <div class="stat-mini-val" style="color: #6366f1;" id="statPulang">{{ $stats['pulang'] }}</div>
                    <div class="stat-mini-lbl">Pulang Cepat</div>
                </div>
            </div>

            <!-- Chart Statistik Aktivitas Gerbang -->
            <div class="kiosk-chart-card">
                <div class="kiosk-chart-head">
                    <div class="kiosk-chart-title">
                        <i class="fas fa-chart-column text-primary"></i>
                        <span>Distribusi Verifikasi Gerbang per Jam</span>
                    </div>
                    <span class="badge badge-primary" style="font-size: var(--text-2xs, 0.65rem); font-weight: 700; padding: 2px 6px;">
                        REALTIME
                    </span>
                </div>
                <div class="kiosk-chart-wrapper">
                    <canvas id="kioskActivityChart"></canvas>
                </div>
            </div>

            <!-- Live Feed Gerbang Terkini -->
            <div class="kiosk-feed-card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; border-bottom: 1px solid var(--border-color, rgba(255,255,255,0.08)); padding-bottom: 6px;">
                    <div style="font-weight: 800; font-size: var(--text-base, 0.85rem); color: var(--text-color); display: flex; align-items: center; gap: 6px;">
                        <i class="fas fa-clock-rotate-left text-primary"></i> Aktivitas Gerbang Terkini
                    </div>
                    <span class="badge badge-primary" style="font-size: var(--text-2xs, 0.65rem); font-weight: 700; padding: 2px 6px;">
                        LIVE FEED
                    </span>
                </div>

                <div id="liveFeedContainer" class="kiosk-feed-list">
                    @forelse ($liveLogs as $log)
                        <div class="live-log-item">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <img src="{{ $log->foto_url }}" alt="{{ $log->nama_siswa }}"
                                    style="width: 36px; height: 36px; border-radius: 50%; object-fit: cover; border: 1.5px solid var(--border-color);">
                                <div>
                                    <div style="font-weight: 700; font-size: var(--text-sm, 0.8125rem); color: var(--text-color);">
                                        {{ $log->nama_siswa }}
                                    </div>
                                    <div style="font-size: var(--text-2xs, 0.7rem); color: var(--text-muted);">
                                        {{ $log->nama_rombel ?? 'Siswa' }} &bull; Tiket: {{ $log->nomor_tiket }}
                                    </div>
                                </div>
                            </div>

                            <div style="text-align: right;">
                                @if ($log->status === 'di_luar')
                                    <span class="badge"
                                        style="background: rgba(234,88,12,0.15); color: #ea580c; font-size: var(--text-2xs, 0.68rem); padding: 2px 6px; font-weight: 800;">
                                        <i class="fas fa-door-open"></i> KELUAR
                                    </span>
                                    <div style="font-size: var(--text-2xs, 0.68rem); font-family: monospace; color: var(--text-muted); margin-top: 2px;">
                                        {{ $log->satpam_checkout_at ? \Carbon\Carbon::parse($log->satpam_checkout_at)->format('H:i') : substr($log->jam_izin_keluar, 0, 5) }} WIB
                                    </div>
                                @elseif ($log->status === 'kembali')
                                    <span class="badge"
                                        style="background: rgba(16,185,129,0.15); color: #10b981; font-size: var(--text-2xs, 0.68rem); padding: 2px 6px; font-weight: 800;">
                                        <i class="fas fa-right-to-bracket"></i> KEMBALI
                                    </span>
                                    <div style="font-size: var(--text-2xs, 0.68rem); font-family: monospace; color: var(--text-muted); margin-top: 2px;">
                                        {{ $log->jam_kembali_aktual ? substr($log->jam_kembali_aktual, 0, 5) : ($log->satpam_checkin_at ? \Carbon\Carbon::parse($log->satpam_checkin_at)->format('H:i') : '--:--') }} WIB
                                    </div>
                                @elseif ($log->status === 'pulang_selesai')
                                    <span class="badge"
                                        style="background: rgba(99,102,241,0.15); color: #6366f1; font-size: var(--text-2xs, 0.68rem); padding: 2px 6px; font-weight: 800;">
                                        <i class="fas fa-house-chimney"></i> PULANG
                                    </span>
                                    <div style="font-size: var(--text-2xs, 0.68rem); font-family: monospace; color: var(--text-muted); margin-top: 2px;">
                                        {{ $log->satpam_checkout_at ? \Carbon\Carbon::parse($log->satpam_checkout_at)->format('H:i') : substr($log->jam_izin_keluar, 0, 5) }} WIB
                                    </div>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div style="text-align: center; padding: 30px 16px; color: var(--text-muted); font-size: var(--text-sm, 0.8125rem);">
                            <i class="fas fa-shield-heart" style="font-size: var(--text-3xl, 1.8rem); opacity: 0.4; margin-bottom: 6px;"></i>
                            <div>Belum ada aktivitas verifikasi gerbang hari ini.</div>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Popup Hasil Verifikasi Visual Kiosk -->
    <div id="resultModal" class="result-card-overlay">
        <div class="result-card" id="resultCardBox">
            <img id="resFotoSiswa" src="{{ asset('img/avatar-default.png') }}" alt="Foto Siswa" class="result-avatar">

            <div style="margin-bottom: 8px;">
                <span id="resBadgeStatus" class="badge" style="font-size: var(--text-base, 0.85rem); padding: 6px 14px; font-weight: 800;">
                    STATUS VERIFIKASI
                </span>
            </div>

            <h3 id="resNamaSiswa"
                style="font-size: var(--text-xl, 1.25rem); font-weight: 800; color: var(--text-color); margin: 4px 0 2px 0;">
                Nama Siswa
            </h3>
            <div id="resRombelSiswa"
                style="font-size: var(--text-base, 0.9rem); font-weight: 700; color: var(--primary); margin-bottom: 2px;">
                Kelas X
            </div>
            <div id="resNisnSiswa"
                style="font-size: var(--text-xs, 0.78rem); font-family: monospace; color: var(--text-muted); margin-bottom: 14px;">
                NISN: 0012345678
            </div>

            <div id="resMessageArea"
                style="background: rgba(255, 255, 255, 0.04); border: 1px solid var(--border-color); border-radius: var(--radius-lg, 12px); padding: 12px 14px; margin-bottom: 14px; text-align: left;">
                <div id="resPesanJudul"
                    style="font-weight: 800; font-size: var(--text-base, 0.9rem); color: var(--text-color); margin-bottom: 3px;">
                    Judul Status
                </div>
                <div id="resPesanDetail" style="font-size: var(--text-sm, 0.8rem); color: var(--text-muted); line-height: 1.35;">
                    Rincian pesan verifikasi gerbang...
                </div>
            </div>

            <button type="button" class="btn btn-primary" id="btnCloseResult"
                style="width: 100%; height: 40px; border-radius: var(--radius-md, 10px); font-weight: 700;">
                Lanjut Scan Berikutnya (ESC / Auto Close)
            </button>
        </div>
    </div>

    <!-- Scripts: jsQR & Chart.js -->
    <script src="{{ asset('vendor/jsqr/jsqr.min.js') }}"></script>
    <script src="{{ asset('vendor/chartjs/chart.umd.min.js') }}"></script>

    <script id="initialKioskPayload" type="application/json">
        {!! json_encode(['stats' => $stats], JSON_UNESCAPED_UNICODE) !!}
    </script>

    <script src="{{ asset('js/e-izin-kiosk.js') }}?v={{ file_exists(public_path('js/e-izin-kiosk.js')) ? filemtime(public_path('js/e-izin-kiosk.js')) : time() }}"></script>
</body>

</html>
