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

    <style>
        html,
        body {
            height: 100%;
            margin: 0;
            padding: 0;
            overflow: hidden;
        }

        body.kiosk-wrapper {
            height: 100vh;
            max-height: 100vh;
            display: flex;
            flex-direction: column;
            padding: 12px 18px;
            box-sizing: border-box;
            background: radial-gradient(circle at 50% 10%, rgba(59, 130, 246, 0.12), transparent 50%),
                var(--bg-main, #0b0f19);
            color: var(--text-color, #f1f5f9);
            overflow: hidden;
        }

        .kiosk-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--border-color, rgba(255, 255, 255, 0.1));
            margin-bottom: 12px;
            gap: 12px;
            flex-shrink: 0;
        }

        .kiosk-izin-grid {
            display: grid;
            grid-template-columns: 1.05fr 0.95fr;
            gap: 14px;
            flex: 1;
            min-height: 0;
            width: 100%;
            max-width: 100%;
            margin: 0;
            overflow: hidden;
        }

        @media (max-width: 992px) {
            html,
            body,
            body.kiosk-wrapper {
                overflow-y: auto !important;
                height: auto !important;
                max-height: none !important;
            }

            .kiosk-izin-grid {
                grid-template-columns: 1fr;
                height: auto;
            }
        }

        /* Station Kiri: Scan & Kamera */
        .kiosk-scan-box {
            background: var(--bg-card, #1e293b);
            border: 1px solid var(--border-color, rgba(255, 255, 255, 0.1));
            border-radius: var(--radius-2xl, 20px);
            padding: 16px 20px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            align-items: center;
            position: relative;
            overflow: hidden;
            height: 100%;
            min-height: 0;
            box-sizing: border-box;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.25);
        }

        .scan-prompt-title {
            font-size: var(--text-xl, 1.25rem);
            font-weight: 800;
            color: var(--text-color, #f8fafc);
            margin: 0 0 4px 0;
            text-align: center;
            letter-spacing: -0.01em;
        }

        .scan-prompt-desc {
            font-size: var(--text-sm, 0.8125rem);
            color: var(--text-muted, #94a3b8);
            text-align: center;
            max-width: 440px;
            margin: 0 0 8px 0;
            line-height: 1.35;
        }

        /* Camera Viewfinder Box */
        .camera-container-kiosk {
            width: 100%;
            max-width: 460px;
            flex: 1;
            min-height: 160px;
            max-height: 300px;
            background: #050811;
            border-radius: var(--radius-lg, 14px);
            position: relative;
            overflow: hidden;
            border: 2px solid var(--primary, #3b82f6);
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.4);
            margin: 6px 0 10px 0;
        }

        .camera-video-kiosk {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .scanner-laser-kiosk {
            position: absolute;
            left: 8%;
            right: 8%;
            height: 2px;
            background: #ef4444;
            box-shadow: 0 0 12px 2px #ef4444;
            animation: laserScan 2s ease-in-out infinite;
            pointer-events: none;
            z-index: 5;
        }

        @keyframes laserScan {
            0% {
                top: 12%;
                opacity: 0.8;
            }

            50% {
                top: 88%;
                opacity: 1;
            }

            100% {
                top: 12%;
                opacity: 0.8;
            }
        }

        .scanner-frame-corner {
            position: absolute;
            width: 22px;
            height: 22px;
            border-color: var(--primary, #3b82f6);
            border-style: solid;
            pointer-events: none;
            z-index: 4;
        }

        .frame-tl {
            top: 10px;
            left: 10px;
            border-width: 3px 0 0 3px;
            border-top-left-radius: 6px;
        }

        .frame-tr {
            top: 10px;
            right: 10px;
            border-width: 3px 3px 0 0;
            border-top-right-radius: 6px;
        }

        .frame-bl {
            bottom: 10px;
            left: 10px;
            border-width: 0 0 3px 3px;
            border-bottom-left-radius: 6px;
        }

        .frame-br {
            bottom: 10px;
            right: 10px;
            border-width: 0 3px 3px 0;
            border-bottom-right-radius: 6px;
        }

        .kiosk-cam-controls {
            position: absolute;
            top: 10px;
            right: 10px;
            display: flex;
            gap: 6px;
            z-index: 10;
        }

        .kiosk-cam-badge {
            width: 32px;
            height: 32px;
            border-radius: var(--radius-md, 8px);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: var(--text-sm, 0.8125rem);
            cursor: pointer;
            backdrop-filter: blur(4px);
            transition: all 0.2s ease;
            outline: none;
        }

        .kiosk-cam-badge.status-ok {
            background: rgba(16, 185, 129, 0.25);
            border: 1px solid #10b981;
            color: #34d399;
            cursor: default;
        }

        .kiosk-cam-badge.status-warn {
            background: rgba(245, 158, 11, 0.25);
            border: 1px solid #f59e0b;
            color: #fbbf24;
            cursor: default;
        }

        .kiosk-cam-badge.status-err {
            background: rgba(239, 68, 68, 0.25);
            border: 1px solid #ef4444;
            color: #f87171;
            cursor: default;
        }

        .kiosk-cam-badge.btn-cam-action {
            background: rgba(15, 23, 42, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: #f1f5f9;
        }

        .kiosk-cam-badge.btn-cam-action:hover {
            background: var(--primary, #3b82f6);
            color: #fff;
            border-color: var(--primary, #3b82f6);
        }

        /* Camera Notice Overlay */
        .camera-notice-kiosk {
            position: absolute;
            inset: 0;
            background: rgba(11, 15, 25, 0.92);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 16px;
            z-index: 12;
        }

        /* Input Wedge */
        .manual-input-wrap {
            display: flex;
            gap: 8px;
            width: 100%;
            max-width: 460px;
            margin-bottom: 8px;
        }

        .manual-input {
            flex: 1;
            height: 44px;
            background: rgba(0, 0, 0, 0.25);
            border: 2px solid var(--border-color, rgba(255, 255, 255, 0.15));
            border-radius: var(--radius-lg, 12px);
            padding: 0 14px;
            color: var(--text-color, #ffffff);
            font-family: monospace;
            font-size: var(--text-md, 1rem);
            text-align: center;
            transition: all 0.2s ease;
        }

        .manual-input:focus {
            outline: none;
            border-color: var(--primary, #3b82f6);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.25);
        }

        /* SOP Notice Box */
        .sop-note-box {
            padding: 10px 14px;
            border-radius: var(--radius-lg, 12px);
            background: rgba(59, 130, 246, 0.06);
            border: 1px solid rgba(59, 130, 246, 0.18);
            font-size: var(--text-xs, 0.73rem);
            color: var(--text-muted);
            width: 100%;
            max-width: 460px;
            text-align: left;
            line-height: 1.35;
        }

        /* Kolom Kanan */
        .kiosk-right-col {
            height: 100%;
            min-height: 0;
            display: flex;
            flex-direction: column;
            gap: 10px;
            overflow: hidden;
        }

        .kiosk-stats-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            flex-shrink: 0;
        }

        .stat-mini-card {
            background: var(--bg-card, #1e293b);
            border: 1px solid var(--border-color, rgba(255, 255, 255, 0.08));
            border-radius: var(--radius-lg, 12px);
            padding: 8px 12px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .stat-mini-val {
            font-size: var(--text-xl, 1.3rem);
            font-weight: 800;
            font-family: monospace;
            color: var(--text-color);
            line-height: 1.1;
        }

        .stat-mini-lbl {
            font-size: var(--text-2xs, 0.7rem);
            color: var(--text-muted);
            margin-top: 3px;
        }

        /* Chart Card */
        .kiosk-chart-card {
            background: var(--bg-card, #1e293b);
            border: 1px solid var(--border-color, rgba(255, 255, 255, 0.08));
            border-radius: var(--radius-xl, 16px);
            padding: 10px 14px;
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
        }

        .kiosk-chart-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 6px;
        }

        .kiosk-chart-title {
            font-size: var(--text-sm, 0.8125rem);
            font-weight: 800;
            color: var(--text-color);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .kiosk-chart-wrapper {
            height: 135px;
            position: relative;
            width: 100%;
        }

        /* Live Feed Card */
        .kiosk-feed-card {
            background: var(--bg-card, #1e293b);
            border: 1px solid var(--border-color, rgba(255, 255, 255, 0.08));
            border-radius: var(--radius-xl, 16px);
            padding: 12px 14px;
            flex: 1;
            min-height: 0;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .kiosk-feed-list {
            flex: 1;
            min-height: 0;
            overflow-y: auto;
            padding-right: 4px;
        }

        .live-log-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 7px 10px;
            border-radius: var(--radius-md, 8px);
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--border-color, rgba(255, 255, 255, 0.05));
            margin-bottom: 6px;
            transition: background 0.15s ease;
        }

        .live-log-item:hover {
            background: rgba(255, 255, 255, 0.06);
        }

        /* Modal Hasil Popup */
        .result-card-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.75);
            backdrop-filter: blur(8px);
            z-index: 99999;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .result-card {
            background: var(--bg-card, #1e293b);
            border: 2px solid var(--border-color);
            border-radius: var(--radius-2xl, 24px);
            width: 100%;
            max-width: 480px;
            padding: 24px;
            text-align: center;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5);
            animation: zoomIn 0.25s ease-out;
        }

        @keyframes zoomIn {
            from {
                opacity: 0;
                transform: scale(0.92);
            }

            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        .result-avatar {
            width: 84px;
            height: 84px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--border-color);
            margin: 0 auto 10px auto;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }
    </style>
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

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const initialPayload = JSON.parse(document.getElementById('initialKioskPayload')?.textContent || '{}');
            let soundEnabled = true;
            let autoCloseTimer = null;
            let isProcessing = false;

            // 1. Digital Clock Live
            const clockEl = document.getElementById('kioskLiveClock');
            setInterval(() => {
                if (clockEl) {
                    const now = new Date();
                    clockEl.textContent = now.toLocaleTimeString('id-ID', { hour12: false });
                }
            }, 1000);

            // 2. Fullscreen Toggle
            const btnFullscreen = document.getElementById('btnToggleFullscreen');
            if (btnFullscreen) {
                btnFullscreen.addEventListener('click', () => {
                    if (!document.fullscreenElement) {
                        document.documentElement.requestFullscreen().catch(() => {});
                        btnFullscreen.innerHTML = '<i class="fas fa-compress"></i>';
                        btnFullscreen.title = 'Keluar Layar Penuh';
                    } else {
                        document.exitFullscreen().catch(() => {});
                        btnFullscreen.innerHTML = '<i class="fas fa-expand"></i>';
                        btnFullscreen.title = 'Layar Penuh (Monitor Kiosk)';
                    }
                });
            }

            // 3. Audio & Web Speech Synthesis
            const btnSound = document.getElementById('btnToggleSound');
            if (btnSound) {
                btnSound.addEventListener('click', () => {
                    soundEnabled = !soundEnabled;
                    btnSound.className = soundEnabled ? 'btn btn-primary btn-kiosk-action' : 'btn btn-outline btn-kiosk-action';
                    btnSound.innerHTML = soundEnabled ? '<i class="fas fa-volume-high"></i>' : '<i class="fas fa-volume-xmark"></i>';
                    btnSound.title = soundEnabled ? 'Suara Aktif' : 'Suara Dibisukan';
                });
            }

            const playSoundEffect = (type = 'success') => {
                if (!soundEnabled) return;
                try {
                    const ctx = new (window.AudioContext || window.webkitAudioContext)();
                    const now = ctx.currentTime;
                    if (type === 'success') {
                        const notes = [523.25, 659.25, 783.99];
                        notes.forEach((freq, idx) => {
                            const osc = ctx.createOscillator();
                            const gain = ctx.createGain();
                            osc.type = 'triangle';
                            osc.frequency.value = freq;
                            gain.gain.setValueAtTime(0.2, now + idx * 0.08);
                            gain.gain.exponentialRampToValueAtTime(0.001, now + idx * 0.08 + 0.3);
                            osc.connect(gain);
                            gain.connect(ctx.destination);
                            osc.start(now + idx * 0.08);
                            osc.stop(now + idx * 0.08 + 0.35);
                        });
                    } else if (type === 'warning') {
                        [440, 440].forEach((freq, idx) => {
                            const osc = ctx.createOscillator();
                            const gain = ctx.createGain();
                            osc.type = 'square';
                            osc.frequency.value = freq;
                            gain.gain.setValueAtTime(0.15, now + idx * 0.15);
                            gain.gain.exponentialRampToValueAtTime(0.001, now + idx * 0.15 + 0.12);
                            osc.connect(gain);
                            gain.connect(ctx.destination);
                            osc.start(now + idx * 0.15);
                            osc.stop(now + idx * 0.15 + 0.15);
                        });
                    } else {
                        const osc = ctx.createOscillator();
                        const gain = ctx.createGain();
                        osc.type = 'sawtooth';
                        osc.frequency.value = 160;
                        gain.gain.setValueAtTime(0.25, now);
                        gain.gain.exponentialRampToValueAtTime(0.001, now + 0.45);
                        osc.connect(gain);
                        gain.connect(ctx.destination);
                        osc.start(now);
                        osc.stop(now + 0.5);
                    }
                } catch (e) {
                    console.warn('AudioContext error:', e);
                }
            };

            const speakAnnouncement = (text) => {
                if (!soundEnabled || !window.speechSynthesis) return;
                try {
                    window.speechSynthesis.cancel();
                    const u = new SpeechSynthesisUtterance(text);
                    u.lang = 'id-ID';
                    u.rate = 1.05;
                    u.pitch = 1.0;
                    window.speechSynthesis.speak(u);
                } catch (e) {
                    console.warn('TTS error:', e);
                }
            };

            // 4. Input Elements & Keyboard Gun Autofocus
            const inputCode = document.getElementById('kioskInputCode');
            const resultModal = document.getElementById('resultModal');
            const btnCloseResult = document.getElementById('btnCloseResult');
            const resFotoSiswa = document.getElementById('resFotoSiswa');
            const resBadgeStatus = document.getElementById('resBadgeStatus');
            const resNamaSiswa = document.getElementById('resNamaSiswa');
            const resRombelSiswa = document.getElementById('resRombelSiswa');
            const resNisnSiswa = document.getElementById('resNisnSiswa');
            const resPesanJudul = document.getElementById('resPesanJudul');
            const resPesanDetail = document.getElementById('resPesanDetail');

            const focusInput = () => {
                if (inputCode && resultModal.style.display !== 'flex') {
                    inputCode.focus();
                }
            };

            document.addEventListener('click', (e) => {
                if (!e.target.closest('button') && !e.target.closest('a') && !e.target.closest('input')) {
                    focusInput();
                }
            });

            const hideResult = () => {
                if (autoCloseTimer) {
                    clearTimeout(autoCloseTimer);
                    autoCloseTimer = null;
                }
                resultModal.style.display = 'none';
                if (inputCode) {
                    inputCode.disabled = false;
                    inputCode.value = '';
                    inputCode.focus();
                }
            };

            if (btnCloseResult) btnCloseResult.addEventListener('click', hideResult);
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') hideResult();
            });

            const showResultCard = (data) => {
                if (autoCloseTimer) {
                    clearTimeout(autoCloseTimer);
                    autoCloseTimer = null;
                }
                const isSuccess = (data.status === 'success');
                const isWarning = (data.status === 'warning');

                const siswa = data.siswa || {};
                resFotoSiswa.src = siswa.foto || '/img/avatar-default.png';
                resNamaSiswa.textContent = siswa.nama || (data.title === 'Identitas Tidak Dikenali' ? 'Kartu / Kode Tidak Terdaftar' : (isSuccess ? 'Peserta Didik' : (data.title || 'Pemeriksaan Gerbang')));
                resRombelSiswa.textContent = siswa.rombel ? `Kelas ${siswa.rombel}` : '';
                resNisnSiswa.textContent = siswa.nisn ? `NISN: ${siswa.nisn}` : '';

                if (isSuccess) {
                    const isCheckout = (data.action === 'checkout');
                    resBadgeStatus.className = isCheckout ? 'badge badge-warning' : 'badge badge-success';
                    resBadgeStatus.style.background = isCheckout ? '#ea580c' : '#10b981';
                    resBadgeStatus.style.color = '#ffffff';
                    resBadgeStatus.innerHTML = isCheckout
                        ? '<i class="fas fa-door-open"></i> VERIFIKASI KELUAR BERHASIL'
                        : '<i class="fas fa-right-to-bracket"></i> VERIFIKASI KEMBALI BERHASIL';

                    playSoundEffect('success');
                    speakAnnouncement(`${siswa.nama || 'Siswa'}. ${data.title || 'Verifikasi berhasil'}.`);
                } else if (isWarning) {
                    resBadgeStatus.className = 'badge badge-warning';
                    resBadgeStatus.style.background = '#f59e0b';
                    resBadgeStatus.style.color = '#000000';
                    resBadgeStatus.innerHTML = '<i class="fas fa-triangle-exclamation"></i> TIDAK ADA IZIN AKTIF';

                    playSoundEffect('warning');
                    speakAnnouncement(data.message || 'Tidak ada tiket e-izin aktif.');
                } else {
                    resBadgeStatus.className = 'badge badge-danger';
                    resBadgeStatus.style.background = '#ef4444';
                    resBadgeStatus.style.color = '#ffffff';
                    const badgeText = (data.title && data.title.length <= 25) ? data.title.toUpperCase() : 'DITOLAK SESUAI SOP';
                    resBadgeStatus.innerHTML = `<i class="fas fa-circle-xmark"></i> ${badgeText}`;

                    playSoundEffect('error');
                    speakAnnouncement(data.title || 'SOP Ditolak');
                }

                resPesanJudul.textContent = data.title || (isSuccess ? 'Pemeriksaan Berhasil' : 'Perhatian');
                resPesanDetail.textContent = data.message || '';

                resultModal.style.display = 'flex';

                // Auto-close modal otomatis untuk semua status (sukses, warning, error, identitas tidak dikenali)
                const closeDelay = isSuccess ? 4000 : 3500;
                autoCloseTimer = setTimeout(() => {
                    hideResult();
                    if (isSuccess) {
                        refreshLiveFeed();
                    }
                }, closeDelay);
            };

            const processScan = (raw) => {
                if (isProcessing) return;
                const code = raw.trim();
                if (!code) return;

                isProcessing = true;
                if (inputCode) inputCode.disabled = true;

                fetch('/e-izin/kiosk/scan', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({ raw_input: code })
                })
                    .then((res) => res.json())
                    .then((data) => {
                        showResultCard(data);
                    })
                    .catch((err) => {
                        showResultCard({
                            status: 'error',
                            title: 'Koneksi Terganggu',
                            message: err.message || 'Gagal memproses kode pemindaian.'
                        });
                    })
                    .finally(() => {
                        isProcessing = false;
                        if (inputCode) {
                            inputCode.disabled = false;
                            inputCode.value = '';
                            if (resultModal.style.display !== 'flex') {
                                inputCode.focus();
                            }
                        }
                    });
            };

            const formKioskScan = document.getElementById('formKioskScan');
            if (formKioskScan) {
                formKioskScan.addEventListener('submit', (e) => {
                    e.preventDefault();
                    if (inputCode && inputCode.value) {
                        processScan(inputCode.value);
                    }
                });
            }

            // 5. Chart.js Inisialisasi & Live Update
            let kioskChart = null;
            const initChart = (stats) => {
                const canvas = document.getElementById('kioskActivityChart');
                if (!canvas || typeof Chart === 'undefined') return;

                const hourly = stats?.hourly || {
                    labels: ['07:00', '08:00', '09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00'],
                    keluar: [0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
                    kembali: [0, 0, 0, 0, 0, 0, 0, 0, 0, 0]
                };

                kioskChart = new Chart(canvas.getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: hourly.labels,
                        datasets: [
                            {
                                label: 'Keluar Gerbang',
                                data: hourly.keluar,
                                backgroundColor: 'rgba(234, 88, 12, 0.75)',
                                borderColor: '#ea580c',
                                borderWidth: 1.5,
                                borderRadius: 4,
                                barPercentage: 0.75,
                                categoryPercentage: 0.8
                            },
                            {
                                label: 'Kembali Masuk',
                                data: hourly.kembali,
                                backgroundColor: 'rgba(16, 185, 129, 0.75)',
                                borderColor: '#10b981',
                                borderWidth: 1.5,
                                borderRadius: 4,
                                barPercentage: 0.75,
                                categoryPercentage: 0.8
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        animation: { duration: 400 },
                        plugins: {
                            legend: {
                                position: 'top',
                                align: 'end',
                                labels: {
                                    boxWidth: 10,
                                    font: { size: 10, weight: '700' },
                                    color: '#94a3b8',
                                    padding: 8
                                }
                            },
                            tooltip: {
                                mode: 'index',
                                intersect: false,
                                padding: 8,
                                bodyFont: { size: 11 },
                                titleFont: { size: 11, weight: '700' }
                            }
                        },
                        scales: {
                            x: {
                                grid: { display: false },
                                ticks: { font: { size: 9 }, color: '#94a3b8', maxRotation: 0 }
                            },
                            y: {
                                beginAtZero: true,
                                ticks: { stepSize: 1, precision: 0, font: { size: 9 }, color: '#94a3b8' },
                                grid: { color: 'rgba(255, 255, 255, 0.06)' }
                            }
                        }
                    }
                });
            };

            const updateChartData = (hourly) => {
                if (!kioskChart || !hourly) return;
                kioskChart.data.labels = hourly.labels;
                kioskChart.data.datasets[0].data = hourly.keluar;
                kioskChart.data.datasets[1].data = hourly.kembali;
                kioskChart.update('none');
            };

            initChart(initialPayload?.stats);

            // 6. Live Feed & Polling
            const refreshLiveFeed = () => {
                fetch('/e-izin/kiosk/live-log', {
                    headers: { 'Accept': 'application/json' }
                })
                    .then((res) => res.json())
                    .then((data) => {
                        if (data.status === 'success') {
                            if (data.stats) {
                                document.getElementById('statTotal').textContent = data.stats.total;
                                document.getElementById('statDiLuar').textContent = data.stats.di_luar;
                                document.getElementById('statKembali').textContent = data.stats.kembali;
                                document.getElementById('statPulang').textContent = data.stats.pulang;
                                if (data.stats.hourly) {
                                    updateChartData(data.stats.hourly);
                                }
                            }

                            const container = document.getElementById('liveFeedContainer');
                            if (container && data.logs && data.logs.length > 0) {
                                let html = '';
                                data.logs.forEach((log) => {
                                    let badgeHtml = '';
                                    const keluarTime = log.satpam_checkout_at ? log.satpam_checkout_at.substring(11, 16) : (log.jam_izin_keluar ? log.jam_izin_keluar.substring(0, 5) : '--:--');
                                    const kembaliTime = log.jam_kembali_aktual ? log.jam_kembali_aktual.substring(0, 5) : (log.satpam_checkin_at ? log.satpam_checkin_at.substring(11, 16) : '--:--');

                                    if (log.status === 'di_luar') {
                                        badgeHtml = `<span class="badge" style="background: rgba(234,88,12,0.15); color: #ea580c; font-size: var(--text-2xs, 0.68rem); padding: 2px 6px; font-weight: 800;"><i class="fas fa-door-open"></i> KELUAR</span><div style="font-size: var(--text-2xs, 0.68rem); font-family: monospace; color: var(--text-muted); margin-top: 2px;">${keluarTime} WIB</div>`;
                                    } else if (log.status === 'kembali') {
                                        badgeHtml = `<span class="badge" style="background: rgba(16,185,129,0.15); color: #10b981; font-size: var(--text-2xs, 0.68rem); padding: 2px 6px; font-weight: 800;"><i class="fas fa-right-to-bracket"></i> KEMBALI</span><div style="font-size: var(--text-2xs, 0.68rem); font-family: monospace; color: var(--text-muted); margin-top: 2px;">${kembaliTime} WIB</div>`;
                                    } else {
                                        badgeHtml = `<span class="badge" style="background: rgba(99,102,241,0.15); color: #6366f1; font-size: var(--text-2xs, 0.68rem); padding: 2px 6px; font-weight: 800;"><i class="fas fa-house-chimney"></i> PULANG</span><div style="font-size: var(--text-2xs, 0.68rem); font-family: monospace; color: var(--text-muted); margin-top: 2px;">${keluarTime} WIB</div>`;
                                    }

                                    html += `
                                        <div class="live-log-item">
                                            <div style="display: flex; align-items: center; gap: 10px;">
                                                <img src="${log.foto_url}" alt="${log.nama_siswa}"
                                                    style="width: 36px; height: 36px; border-radius: 50%; object-fit: cover; border: 1.5px solid var(--border-color);">
                                                <div>
                                                    <div style="font-weight: 700; font-size: var(--text-sm, 0.8125rem); color: var(--text-color);">
                                                        ${log.nama_siswa}
                                                    </div>
                                                    <div style="font-size: var(--text-2xs, 0.7rem); color: var(--text-muted);">
                                                        ${log.nama_rombel || 'Siswa'} &bull; Tiket: ${log.nomor_tiket}
                                                    </div>
                                                </div>
                                            </div>
                                            <div style="text-align: right;">${badgeHtml}</div>
                                        </div>
                                    `;
                                });
                                container.innerHTML = html;
                            }
                        }
                    })
                    .catch((err) => console.warn('Gagal poll live feed:', err));
            };

            setInterval(refreshLiveFeed, 6000);

            // 7. Kamera Webcam Live & Auto-Decoder QR (jsQR)
            const videoEl = document.getElementById('videoPreview');
            const cameraNoticeOverlay = document.getElementById('cameraNoticeOverlay');
            const cameraNoticeTitle = document.getElementById('cameraNoticeTitle');
            const cameraNoticeDesc = document.getElementById('cameraNoticeDesc');
            const cameraStatusBadge = document.getElementById('cameraStatusBadge');
            const btnSwitchCamera = document.getElementById('btnSwitchCamera');
            const btnToggleCamera = document.getElementById('btnToggleCamera');
            const btnRetryCamera = document.getElementById('btnRetryCamera');

            let videoStream = null;
            let cameraActive = false;
            let cameraAnimFrame = null;
            let scanCanvas = null;
            let scanCanvasCtx = null;
            let lastScanTick = 0;

            const isTouchDevice = ('ontouchstart' in window) || (navigator.maxTouchPoints > 0) || window.innerWidth <= 768;
            let currentFacingMode = isTouchDevice ? 'environment' : 'user';

            const showCameraNotice = (title, desc) => {
                if (cameraNoticeOverlay) {
                    cameraNoticeOverlay.style.display = 'flex';
                    if (cameraNoticeTitle) cameraNoticeTitle.textContent = title;
                    if (cameraNoticeDesc) cameraNoticeDesc.textContent = desc;
                }
                if (cameraStatusBadge) {
                    cameraStatusBadge.className = 'kiosk-cam-badge status-warn';
                    cameraStatusBadge.innerHTML = '<i class="fas fa-video-slash"></i>';
                    cameraStatusBadge.title = title;
                }
            };

            const hideCameraNotice = () => {
                if (cameraNoticeOverlay) cameraNoticeOverlay.style.display = 'none';
                if (cameraStatusBadge) {
                    cameraStatusBadge.className = 'kiosk-cam-badge status-ok';
                    cameraStatusBadge.innerHTML = '<i class="fas fa-video"></i>';
                    cameraStatusBadge.title = `Kamera Aktif (${currentFacingMode === 'environment' ? 'Belakang' : 'Depan'})`;
                }
            };

            const checkAvailableCameras = async () => {
                if (!navigator.mediaDevices || !navigator.mediaDevices.enumerateDevices) return;
                try {
                    const devices = await navigator.mediaDevices.enumerateDevices();
                    const videoDevices = devices.filter((d) => d.kind === 'videoinput');
                    if (btnSwitchCamera) {
                        btnSwitchCamera.style.display = videoDevices.length > 1 ? 'inline-flex' : 'none';
                    }
                } catch (e) {}
            };

            const stopCamera = () => {
                if (videoStream) {
                    videoStream.getTracks().forEach((t) => t.stop());
                    videoStream = null;
                }
                if (cameraAnimFrame) {
                    cancelAnimationFrame(cameraAnimFrame);
                    cameraAnimFrame = null;
                }
                cameraActive = false;
                if (cameraStatusBadge) {
                    cameraStatusBadge.className = 'kiosk-cam-badge status-err';
                    cameraStatusBadge.innerHTML = '<i class="fas fa-video-slash"></i>';
                    cameraStatusBadge.title = 'Kamera Nonaktif';
                }
            };

            const scanVideoFrame = () => {
                if (!cameraActive) return;

                if (!videoEl || videoEl.readyState !== videoEl.HAVE_ENOUGH_DATA) {
                    cameraAnimFrame = requestAnimationFrame(scanVideoFrame);
                    return;
                }

                const now = Date.now();
                // Throttle decode ke 120ms
                if (now - lastScanTick > 120) {
                    lastScanTick = now;

                    if (!scanCanvas) {
                        scanCanvas = document.createElement('canvas');
                        scanCanvasCtx = scanCanvas.getContext('2d', { willReadFrequently: true });
                    }

                    scanCanvas.width = videoEl.videoWidth;
                    scanCanvas.height = videoEl.videoHeight;
                    scanCanvasCtx.drawImage(videoEl, 0, 0, scanCanvas.width, scanCanvas.height);

                    if (typeof jsQR !== 'undefined') {
                        try {
                            const imageData = scanCanvasCtx.getImageData(0, 0, scanCanvas.width, scanCanvas.height);
                            const qr = jsQR(imageData.data, imageData.width, imageData.height, {
                                inversionAttempts: 'dontInvert'
                            });

                            if (qr && qr.data && !isProcessing && resultModal.style.display !== 'flex') {
                                processScan(qr.data);
                                // Jeda 3.5 detik setelah berhasil membaca QR agar modal tampil dan auto-close
                                setTimeout(() => {
                                    if (cameraActive) cameraAnimFrame = requestAnimationFrame(scanVideoFrame);
                                }, 3500);
                                return;
                            }
                        } catch (err) {
                            console.warn('Decode frame error:', err);
                        }
                    }
                }

                cameraAnimFrame = requestAnimationFrame(scanVideoFrame);
            };

            const startCamera = async () => {
                if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                    showCameraNotice(
                        'Kamera Tidak Didukung',
                        'Browser ini tidak menyediakan MediaDevices API atau koneksi HTTP non-aman.'
                    );
                    return;
                }

                stopCamera();

                let stream = null;
                try {
                    // Percobaan 1: facingMode ideal
                    stream = await navigator.mediaDevices.getUserMedia({
                        video: {
                            facingMode: { ideal: currentFacingMode },
                            width: { ideal: 1280 },
                            height: { ideal: 720 }
                        },
                        audio: false
                    });
                } catch (err1) {
                    console.warn(`Gagal buka kamera facingMode ${currentFacingMode}, mencoba kebalikannya...`, err1);
                    try {
                        // Percobaan 2: fallback opposite facingMode
                        const opposite = (currentFacingMode === 'user' ? 'environment' : 'user');
                        stream = await navigator.mediaDevices.getUserMedia({
                            video: {
                                facingMode: { ideal: opposite },
                                width: { ideal: 640 },
                                height: { ideal: 480 }
                            },
                            audio: false
                        });
                        currentFacingMode = opposite;
                    } catch (err2) {
                        console.warn('Gagal opposite facingMode, fallback video: true...', err2);
                        try {
                            // Percobaan 3: tanpa constraint sama sekali
                            stream = await navigator.mediaDevices.getUserMedia({ video: true, audio: false });
                        } catch (errFinal) {
                            console.error('Kamera gagal dibuka:', errFinal);
                            let msg = errFinal.message || 'Tidak dapat mengakses perangkat kamera.';
                            if (errFinal.name === 'NotAllowedError' || errFinal.name === 'PermissionDeniedError') {
                                msg = 'Izin kamera ditolak. Izinkan akses kamera pada ikon gembok di address bar browser.';
                            } else if (errFinal.name === 'NotFoundError' || errFinal.name === 'DevicesNotFoundError') {
                                msg = 'Tidak ada perangkat webcam atau kamera terdeteksi di perangkat ini.';
                            } else if (errFinal.name === 'NotReadableError' || errFinal.name === 'TrackStartError') {
                                msg = 'Kamera sedang digunakan oleh aplikasi lain.';
                            }
                            showCameraNotice('Akses Kamera Tertahan', msg);
                            return;
                        }
                    }
                }

                if (stream && videoEl) {
                    videoStream = stream;
                    videoEl.srcObject = stream;
                    videoEl.muted = true;
                    videoEl.setAttribute('playsinline', 'true');
                    videoEl.setAttribute('webkit-playsinline', 'true');

                    try {
                        await videoEl.play();
                    } catch (playErr) {
                        console.warn('Play delayed:', playErr);
                    }

                    cameraActive = true;
                    hideCameraNotice();
                    checkAvailableCameras();
                    cameraAnimFrame = requestAnimationFrame(scanVideoFrame);
                }
            };

            // Event Listeners Kamera
            if (btnToggleCamera) {
                btnToggleCamera.addEventListener('click', () => {
                    if (cameraActive) {
                        stopCamera();
                    } else {
                        startCamera();
                    }
                });
            }

            if (btnSwitchCamera) {
                btnSwitchCamera.addEventListener('click', () => {
                    currentFacingMode = (currentFacingMode === 'user') ? 'environment' : 'user';
                    startCamera();
                });
            }

            if (btnRetryCamera) {
                btnRetryCamera.addEventListener('click', () => {
                    startCamera();
                });
            }

            // Inisialisasi otomatis kamera saat terminal dimuat
            startCamera();
            focusInput();
        });
    </script>
</body>

</html>
