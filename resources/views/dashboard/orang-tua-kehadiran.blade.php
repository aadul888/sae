@extends('layouts.dashboard')

@section('title', 'Riwayat Kehadiran Siswa — Portal Orang Tua SAE')
@section('dash_title', 'Kehadiran Siswa')

@push('styles')
    <style>
        .cal-legend-bar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 8px 14px;
            padding: 10px 14px;
            background: var(--bg-card, rgba(255, 255, 255, 0.03));
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg, 12px);
            margin-bottom: 16px;
            font-size: var(--text-xs, 0.75rem);
            color: var(--text-muted);
        }
        .cal-legend-item {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-weight: 600;
        }
        .cal-legend-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
        }
        .cal-table-wrap {
            width: 100%;
            overflow-x: auto;
            margin-bottom: 10px;
            -webkit-overflow-scrolling: touch;
        }
        .cal-table {
            width: 100%;
            min-width: 660px;
            border-collapse: separate;
            border-spacing: 3px;
            table-layout: fixed;
        }
        .cal-th {
            padding: 5px 2px;
            text-align: center;
            font-size: var(--text-xs, 0.75rem);
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-muted);
        }
        .cal-day-cell {
            position: relative;
            padding: 5px 6px;
            border-radius: var(--radius-md, 8px);
            border: 1px solid var(--border-color);
            background: var(--bg-card);
            min-height: 54px;
            vertical-align: top;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            gap: 2px;
            box-sizing: border-box;
        }
        .cal-day-cell:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        .cal-day-cell.is-selected {
            outline: 2px solid var(--primary);
            outline-offset: 1px;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.25);
        }
        .cal-day-num {
            font-size: 0.8125rem;
            font-weight: 800;
            line-height: 1;
        }
        .cal-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 0.65rem;
            font-weight: 700;
            padding: 1px 5px;
            border-radius: var(--radius-sm, 5px);
            width: fit-content;
            max-width: 100%;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            margin-top: 1px;
        }
        .cal-time {
            font-size: 0.625rem;
            color: var(--text-muted);
            margin-top: 1px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .cal-detail-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg, 12px);
            padding: 10px 14px;
            margin-top: 10px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
            transition: all 0.2s ease;
        }
        .cal-header-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 12px;
            flex-wrap: wrap;
        }
        .cal-header-title {
            font-weight: 700;
            font-size: var(--text-base, 0.875rem);
            color: var(--text-color);
        }
        .cal-text-desktop {
            display: inline;
            margin-left: 3px;
        }
        .cal-dot-mobile {
            display: none;
        }
        .cal-toolbar-group {
            display: flex;
            align-items: center;
            gap: 6px;
            flex-wrap: nowrap !important;
        }
        .cal-tabs-segmented {
            display: inline-flex;
            align-items: center;
            background: var(--bg-card, rgba(255, 255, 255, 0.04));
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg, 10px);
            padding: 3px;
            gap: 4px;
        }
        .cal-tab-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 4px 12px;
            border: none;
            background: transparent;
            color: var(--text-muted);
            border-radius: var(--radius-md, 7px);
            font-size: var(--text-xs, 0.75rem);
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
        }
        .cal-tab-btn:hover {
            color: var(--text-color);
            background: rgba(255, 255, 255, 0.06);
        }
        .cal-tab-btn.active {
            background: var(--primary, #3b82f6);
            color: #fff;
            box-shadow: 0 2px 8px rgba(59, 130, 246, 0.35);
        }
        .cal-btn-nav {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            height: 32px;
            padding: 0 8px;
            border-radius: var(--radius-md, 7px);
            border: 1px solid var(--border-color);
            background: var(--bg-card);
            color: var(--text-color);
            text-decoration: none;
            font-size: 0.8125rem;
            transition: all 0.15s ease;
            flex-shrink: 0;
            white-space: nowrap;
        }
        .cal-btn-nav:hover {
            background: var(--bg-hover, rgba(255,255,255,0.06));
            border-color: var(--primary);
            color: var(--primary);
        }
        .cal-badge-text {
            display: inline;
        }
        .cal-indicators {
            display: flex;
            gap: 2px;
            flex-wrap: wrap;
            margin-top: 2px;
        }

        /* Mode Filter Kalender berdasarkan Dropdown Tampilkan */
        .cal-filter-izin-keluar .cal-day-cell:not([data-has-izin-keluar="1"]) {
            opacity: 0.22 !important;
            filter: grayscale(80%);
        }
        .cal-filter-izin-keluar .cal-day-cell[data-has-izin-keluar="1"] {
            border-color: #f59e0b !important;
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.5) !important;
            background: rgba(245, 158, 11, 0.18) !important;
            transform: scale(1.03);
            position: relative;
            z-index: 2;
        }

        .cal-filter-surat-izin .cal-day-cell:not([data-has-surat-izin="1"]) {
            opacity: 0.22 !important;
            filter: grayscale(80%);
        }
        .cal-filter-surat-izin .cal-day-cell[data-has-surat-izin="1"] {
            border-color: #6366f1 !important;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.5) !important;
            background: rgba(99, 102, 241, 0.18) !important;
            transform: scale(1.03);
            position: relative;
            z-index: 2;
        }

        .cal-filter-kedisiplinan .cal-day-cell:not([data-has-kedisiplinan="1"]) {
            opacity: 0.22 !important;
            filter: grayscale(80%);
        }
        .cal-filter-kedisiplinan .cal-day-cell[data-has-kedisiplinan="1"] {
            border-color: #ef4444 !important;
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.5) !important;
            background: rgba(239, 68, 68, 0.18) !important;
            transform: scale(1.03);
            position: relative;
            z-index: 2;
        }

        .cal-filter-mapel .cal-day-cell:not([data-has-mapel="1"]) {
            opacity: 0.22 !important;
            filter: grayscale(80%);
        }
        .cal-filter-mapel .cal-day-cell[data-has-mapel="1"] {
            border-color: #10b981 !important;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.5) !important;
            background: rgba(16, 185, 129, 0.18) !important;
            transform: scale(1.03);
            position: relative;
            z-index: 2;
        }

        /* Mobile / Compact Mode (< 768px): Minimalisir teks, icon & warna saja, toolbar sejajar */
        @media (max-width: 768px) {
            .cal-text-desktop {
                display: none !important;
            }
            .cal-dot-mobile {
                display: inline-block;
                width: 6px;
                height: 6px;
                border-radius: 50%;
                background: #fff;
                vertical-align: middle;
            }
            .cal-header-bar {
                flex-direction: column;
                align-items: center;
                gap: 8px;
                margin-bottom: 12px;
            }
            .cal-header-title {
                text-align: center;
            }
            .cal-tabs-segmented {
                margin: 0 auto;
            }
            .cal-tab-btn {
                width: 40px;
                height: 32px;
                padding: 0;
                font-size: 0.9rem;
            }
            /* Sejajarkan maju mundur, dropdown, dan reset dalam 1 baris tanpa wrap */
            .cal-toolbar-group {
                width: 100% !important;
                max-width: 360px !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                gap: 4px !important;
                flex-wrap: nowrap !important;
                margin: 0 auto;
            }
            .cal-btn-nav {
                width: 32px !important;
                min-width: 32px !important;
                height: 32px !important;
                padding: 0 !important;
                flex: 0 0 32px !important;
            }
            #selectBulanKalender {
                flex: 1 1 auto !important;
                min-width: 0 !important;
                width: auto !important;
                height: 32px !important;
                font-size: 0.78rem !important;
                padding: 2px 4px !important;
                text-align: center !important;
            }
            .cal-table {
                min-width: 480px;
                border-spacing: 2px;
            }
            .cal-day-cell {
                min-height: 48px;
                padding: 3px 2px;
                text-align: center;
                align-items: center;
            }
            .cal-day-num {
                font-size: 0.78rem;
            }
            /* Minimalisir teks: hanya warna dan icon saja */
            .cal-badge-text {
                display: none !important;
            }
            .cal-time {
                display: none !important;
            }
            .cal-badge {
                width: 22px;
                height: 22px;
                border-radius: 50%;
                padding: 0;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                font-size: 0.65rem;
                margin: 1px auto 0 auto;
            }
            .cal-indicators {
                justify-content: center;
                margin-top: 1px;
                gap: 2px;
            }
            .cal-indicator-badge {
                padding: 1px 3px !important;
                font-size: 0.55rem !important;
            }
        }

        @media (max-width: 576px) {
            .dash-stat-grid {
                grid-template-columns: repeat(2, 1fr) !important;
                gap: 8px !important;
                margin-bottom: 14px !important;
            }
            .dash-stat-card {
                padding: 10px 12px !important;
            }
            .dash-stat-card .dash-stat-icon {
                width: 34px !important;
                height: 34px !important;
                font-size: 0.875rem !important;
            }
            .dash-stat-card .dash-stat-value {
                font-size: 1.05rem !important;
            }
            .dash-stat-card .dash-stat-label {
                font-size: 0.6875rem !important;
            }
            .dash-banner {
                padding: 12px 14px !important;
                gap: 12px !important;
                margin-bottom: 14px !important;
            }
            .cal-table-wrap {
                margin-left: -4px;
                margin-right: -4px;
                width: calc(100% + 8px);
                margin-bottom: 10px;
            }
            .cal-table {
                min-width: 420px;
                border-spacing: 2px;
            }
            .cal-th {
                padding: 6px 1px;
                font-size: 0.6875rem;
            }
            .cal-day-cell {
                min-height: 48px;
                padding: 3px 2px;
                border-radius: var(--radius-sm, 6px);
            }
            .cal-day-num {
                font-size: 0.75rem;
            }
            .cal-legend-bar {
                gap: 4px 8px;
                padding: 4px 0;
                font-size: 0.6875rem;
                margin-bottom: 10px;
            }
            .cal-detail-card {
                padding: 12px 14px;
                flex-direction: column;
                align-items: stretch;
                gap: 10px;
            }
            .cal-detail-body {
                display: grid !important;
                grid-template-columns: repeat(2, 1fr);
                gap: 8px !important;
                width: 100%;
            }
            #selectBulanKalender {
                font-size: 0.75rem !important;
                height: 32px !important;
            }
            #selectKategoriKehadiran {
                min-width: 100% !important;
                width: 100% !important;
                font-size: 0.8125rem !important;
            }
            .table-pd th, .table-pd td {
                padding: 8px 10px !important;
                font-size: var(--text-xs, 0.75rem) !important;
            }
        }

        @media (max-width: 420px) {
            .cal-table {
                min-width: 360px;
            }
            .cal-day-cell {
                min-height: 44px;
                padding: 2px 1px;
            }
            .cal-badge {
                width: 22px;
                height: 22px;
                font-size: 0.65rem;
            }
        }
    </style>
@endpush

@section('content')
    @php
        $studentPhoto = $pd->foto_url ?? null;
    @endphp

    <!-- 1. Header Banner Baku SAE -->
    <div class="dash-banner"
        style="margin-bottom: 14px; background: linear-gradient(135deg, rgba(16, 185, 129, 0.12) 0%, rgba(59, 130, 246, 0.08) 100%); border: 1px solid rgba(16, 185, 129, 0.25); display: flex; align-items: center; justify-content: space-between; gap: 14px; flex-wrap: wrap; border-radius: var(--radius-lg, 12px); padding: 14px 18px;">
        <div style="display: flex; align-items: center; gap: 14px; flex: 1; min-width: 0;">
            <div style="flex: 1; min-width: 0;">
                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 2px;">
                    <a href="{{ route('dashboard.orang-tua') }}" class="btn btn-outline btn-sm"
                        style="padding: 3px 8px; font-size: var(--text-xs, 0.75rem); border-radius: var(--radius-sm, 6px);">
                        <i class="fas fa-arrow-left me-1"></i> Dashboard
                    </a>
                    <span style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted);">Portal Orang Tua &bull;
                        {{ $parentName }}</span>
                </div>
                <h2
                    style="font-size: var(--text-xl, 1.25rem); font-weight: 800; color: var(--text-color); margin: 0 0 4px 0; line-height: 1.25;">
                    <i class="fas fa-calendar-check text-success me-1"></i> Riwayat Kehadiran: <span
                        style="color: var(--success, #10b981);">{{ $pd->nama ?? 'Peserta Didik' }}</span>
                </h2>
                <div
                    style="display: flex; align-items: center; gap: 12px; font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); flex-wrap: wrap;">
                    <span><i class="fas fa-id-card text-primary me-1"></i> NISN:
                        <strong>{{ $pd->nisn ?? '-' }}</strong></span>
                    <span><i class="fas fa-door-open text-info me-1"></i> Kelas:
                        <strong>{{ $pd->nama_rombel ?? 'Reguler' }}</strong></span>
                    @if ($waliKelas)
                        <span><i class="fas fa-chalkboard-user text-warning me-1"></i> Wali:
                            <strong>{{ $waliKelas['nama'] }}</strong></span>
                    @endif
                </div>
            </div>
        </div>

        <div class="dash-banner-actions" style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
            <a href="{{ route('dashboard.orang-tua') }}" class="btn btn-outline"
                style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px); font-size: var(--text-md, 1rem); text-decoration: none;"
                title="Beranda Orang Tua">
                <i class="fas fa-house"></i>
            </a>
            <a href="{{ route('dashboard.orang-tua.izin') }}" class="btn btn-outline"
                style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px); font-size: var(--text-md, 1rem); text-decoration: none;"
                title="e-Izin Keluar Sekolah">
                <i class="fas fa-ticket-alt text-warning"></i>
            </a>
        </div>
    </div>

    <!-- 2. Quick Stats Grid Kehadiran -->
    <div class="dash-stat-grid" style="margin-bottom: 14px; gap: 10px;">
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(16, 185, 129, 0.15); color: var(--success, #10b981);">
                <i class="fas fa-percent"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" id="statPersen" style="color: var(--success, #10b981);">{{ $statsHarian['persen'] }}%</div>
                <div class="dash-stat-label">Kehadiran {{ $monthLabel ?? 'Bulan Ini' }}</div>
                <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-top: 2px;">
                    HEB Berjalan: {{ $statsHarian['hari_efektif'] }} Hari
                </div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(59, 130, 246, 0.15); color: var(--primary, #3b82f6);">
                <i class="fas fa-fingerprint"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" id="statHadir" style="color: var(--primary, #3b82f6);">{{ $statsHarian['hadir'] }} Hari</div>
                <div class="dash-stat-label">Hadir Tepat Waktu</div>
                <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-top: 2px;">
                    Terlambat: <span id="statTerlambat">{{ $statsHarian['terlambat'] }} Hari</span>
                </div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(245, 158, 11, 0.15); color: var(--warning, #f59e0b);">
                <i class="fas fa-notes-medical"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" id="statIzinSakit" style="color: var(--warning, #f59e0b);">{{ $statsHarian['izin'] + $statsHarian['sakit'] }} Hari</div>
                <div class="dash-stat-label">Izin &amp; Sakit Resmi</div>
                <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-top: 2px;">
                    Dispensasi: {{ $statsHarian['dispen'] }} Hari
                </div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(239, 68, 68, 0.15); color: var(--danger, #ef4444);">
                <i class="fas fa-triangle-exclamation"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" id="statAlpha" style="color: {{ $statsHarian['alpha'] > 0 ? '#ef4444' : 'var(--success, #10b981)' }};">
                    {{ $statsHarian['alpha'] }} Hari
                </div>
                <div class="dash-stat-label">Alpha / Tanpa Ket.</div>
                <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-top: 2px;">
                    {{ $statsHarian['alpha'] === 0 ? 'Disiplin Sangat Baik' : 'Perlu Diperhatikan' }}
                </div>
            </div>
        </div>
    </div>

    <!-- 3. Konten Utama: Kehadiran Terpadu Siswa dengan Dropdown Selector (Point 5) -->
    <div class="card"
        style="padding: 16px 18px; border-radius: var(--radius-lg, 12px); border: 1px solid var(--border-color); background: var(--card-bg); margin-bottom: 16px;">
        <div
            style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; flex-wrap: wrap; gap: 10px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">
            <div>
                <h3
                    style="font-size: var(--text-md, 1rem); font-weight: 800; color: var(--text-color); margin: 0; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-list-check text-primary"></i> Data Rincian Presensi Siswa
                </h3>
                <p style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin: 2px 0 0 0;">
                    Pilih kategori di bawah untuk beralih antara Presensi Gerbang (RFID) dan Presensi per Mata Pelajaran.
                </p>
            </div>

            <!-- Dropdown Selector Kategori Kehadiran -->
            <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                <label for="selectKategoriKehadiran"
                    style="font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-color); margin: 0;">
                    <i class="fas fa-filter text-primary me-1"></i> Tampilkan:
                </label>
                <select id="selectKategoriKehadiran" class="ot-filter-select form-control" data-no-live-search="1"
                    style="min-width: 260px; font-weight: 700; height: 38px; border-radius: var(--radius-md, 8px); font-size: var(--text-base, 0.875rem); background: var(--bg-card); color: var(--text-color); border: 1px solid var(--border-color);">
                    <option value="subHarian" selected>
                        Presensi Harian (RFID Gerbang) &bull; {{ count($riwayatHarian) }} Catatan
                    </option>
                    <option value="subMapel">
                        Presensi Per Mata Pelajaran &bull; {{ $statsMapel['total'] }} Sesi KBM
                    </option>
                    <option value="subIzinKeluar">
                        e-Izin Keluar Sekolah &bull; {{ count($izinKeluarList ?? []) }} Tiket
                    </option>
                    <option value="subSuratIzin">
                        Surat Permohonan Izin / Sakit &bull; {{ count($suratIzinList ?? []) }} Pengajuan
                    </option>
                    <option value="subKedisiplinan">
                        Catatan Tata Tertib / Kedisiplinan &bull; {{ count($kedisiplinanList ?? []) }} Kasus
                    </option>
                </select>
            </div>
        </div>

        <!-- Section Kalender & Tabel Presensi Terpadu -->
        <div id="calMainSection">
            <div class="cal-header-bar">
                <div class="cal-header-title" id="calHeaderTitle">
                    <i class="fas fa-id-card-clip text-primary me-1"></i> Log Presensi Harian Masuk &amp; Pulang
                </div>

                <!-- Tab di tengah-tengah (Icon Only, Minimalis) -->
                <div class="cal-tabs-segmented" role="tablist">
                    <button type="button" id="btnViewKalender" class="cal-tab-btn active" title="Tampilan Kalender">
                        <i class="fas fa-calendar-days"></i>
                    </button>
                    <button type="button" id="btnViewTabel" class="cal-tab-btn" title="Tampilan Tabel">
                        <i class="fas fa-table-list"></i>
                    </button>
                </div>

                <!-- Month Navigation Toolbar -->
                <div class="cal-toolbar-group">
                    <a href="{{ request()->fullUrlWithQuery(['bulan' => $prevMonth]) }}" class="cal-btn-nav"
                        id="btnPrevMonth" data-month="{{ $prevMonth }}"
                        title="Bulan Sebelumnya ({{ $prevMonth }})">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                    <select id="selectBulanKalender" class="ot-filter-select" data-no-live-search="1"
                        style="height: 32px; padding: 2px 8px; font-weight: 700; font-size: var(--text-sm, 0.8125rem); border-radius: var(--radius-md, 8px); border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-color); width: auto;">
                        @foreach ($availableMonths as $mKey => $mLabel)
                            <option value="{{ $mKey }}" {{ $mKey === $selectedMonth ? 'selected' : '' }}>
                                {{ $mLabel }}
                            </option>
                        @endforeach
                    </select>
                    <a href="{{ request()->fullUrlWithQuery(['bulan' => $nextMonth]) }}" class="cal-btn-nav"
                        id="btnNextMonth" data-month="{{ $nextMonth }}"
                        title="Bulan Berikutnya ({{ $nextMonth }})">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                    <a href="{{ request()->fullUrlWithQuery(['bulan' => now()->format('Y-m')]) }}" class="cal-btn-nav"
                        id="btnThisMonth" data-month="{{ now()->format('Y-m') }}"
                        style="{{ $selectedMonth === now()->format('Y-m') ? 'display: none;' : '' }}"
                        title="Kembali ke Bulan Berjalan">
                        <i class="fas fa-rotate-left"></i>
                    </a>
                </div>
            </div>

            <!-- Legenda Warna Status Presensi (Simpel & di Tengah) -->
            <div id="calLegendBar" class="cal-legend-bar" style="justify-content: center; margin-bottom: 12px; gap: 6px 14px; border: none; background: transparent; padding: 0;">
                <span class="cal-legend-item">
                    <span class="cal-legend-dot" style="background: #10b981;"></span> Hadir
                </span>
                <span class="cal-legend-item">
                    <span class="cal-legend-dot" style="background: #f59e0b;"></span> Terlambat
                </span>
                <span class="cal-legend-item">
                    <span class="cal-legend-dot" style="background: #3b82f6;"></span> Izin
                </span>
                <span class="cal-legend-item">
                    <span class="cal-legend-dot" style="background: #8b5cf6;"></span> Sakit
                </span>
                <span class="cal-legend-item">
                    <span class="cal-legend-dot" style="background: #06b6d4;"></span> Dispen
                </span>
                <span class="cal-legend-item">
                    <span class="cal-legend-dot" style="background: #ef4444;"></span> Alpha
                </span>
                <span class="cal-legend-item">
                    <span class="cal-legend-dot" style="background: #64748b;"></span> Libur
                </span>
            </div>

            <!-- Banner Filter Kalender Aktif -->
            <div id="calFilterAlert" style="display: none; margin-bottom: 12px; padding: 10px 14px; border-radius: 8px; font-size: 0.8125rem; font-weight: 600; align-items: center; justify-content: space-between;">
                <div id="calFilterAlertText"></div>
                <button type="button" id="btnResetFilter" title="Kembali ke Presensi Harian" style="background: transparent; border: 1px solid currentColor; border-radius: 6px; padding: 3px 8px; font-size: 0.75rem; font-weight: 700; cursor: pointer; color: inherit;">
                    <i class="fas fa-xmark"></i>
                </button>
            </div>

            <!-- 1. Tampilan Kalender Visual Harian -->
            <div id="viewKalenderHarian">
                @include('dashboard.partials.orang-tua-kalender-grid')
            </div>

            <!-- 2. Kontainer Panel Tabel (Sesuai Kategori Dropdown yang Terpilih) -->
            <div id="panelTabelContainer" style="display: none; margin-top: 14px;">
                <!-- Tabel A: Presensi Harian Masuk & Pulang (RFID Gerbang) -->
                <div id="subPanelHarian" class="kehadiran-sub-panel">
                    <div id="viewTabelHarian">
                        @include('dashboard.partials.orang-tua-tabel-harian')
                    </div>
                </div>

        <!-- Panel B: Presensi per Mata Pelajaran (KBM) -->
        <div id="subPanelMapel" class="kehadiran-sub-panel" style="display: none;">
            <div
                style="margin-bottom: 14px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;">
                <div style="font-weight: 700; font-size: var(--text-base, 0.875rem); color: var(--text-color);">
                    <i class="fas fa-book-open text-success me-1"></i> Kehadiran Siswa di Tiap Mata Pelajaran Kelas
                </div>
                <div style="display: flex; gap: 10px; font-size: var(--text-sm, 0.8125rem);">
                    <span><i class="fas fa-circle-check text-success"></i> Hadir:
                        <strong>{{ $statsMapel['hadir'] }}</strong></span>
                    <span><i class="fas fa-circle-info text-primary"></i> Izin:
                        <strong>{{ $statsMapel['izin'] }}</strong></span>
                    <span><i class="fas fa-circle-xmark text-danger"></i> Alpha:
                        <strong>{{ $statsMapel['alpha'] }}</strong></span>
                </div>
            </div>

            @if ($riwayatMapel->isNotEmpty())
                <div class="table-responsive-stack" style="overflow-x: auto; width: 100%; margin: 0;">
                    <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                        <thead>
                            <tr
                                style="border-bottom: 1px solid var(--border-color);">
                                <th
                                    style="min-width: 110px; padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                    Tanggal</th>
                                <th
                                    style="min-width: 160px; padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                    Mata Pelajaran</th>
                                <th
                                    style="min-width: 140px; padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                    Guru Pengampu</th>
                                <th
                                    style="width: 80px; text-align: center; padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                    Jam Ke</th>
                                <th
                                    style="width: 100px; text-align: center; padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                    Status</th>
                                <th
                                    style="min-width: 140px; padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                    Catatan / Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($riwayatMapel as $pm)
                                @php
                                    $badgeStatus = match ($pm->status) {
                                        'H' => [
                                            'label' => 'Hadir',
                                            'bg' => 'rgba(16,185,129,0.15)',
                                            'color' => '#10b981',
                                        ],
                                        'T' => [
                                            'label' => 'Terlambat',
                                            'bg' => 'rgba(245,158,11,0.15)',
                                            'color' => '#f59e0b',
                                        ],
                                        'I' => [
                                            'label' => 'Izin',
                                            'bg' => 'rgba(59,130,246,0.15)',
                                            'color' => '#3b82f6',
                                        ],
                                        'S' => [
                                            'label' => 'Sakit',
                                            'bg' => 'rgba(139,92,246,0.15)',
                                            'color' => '#8b5cf6',
                                        ],
                                        'D' => [
                                            'label' => 'Dispen',
                                            'bg' => 'rgba(6,182,212,0.15)',
                                            'color' => '#06b6d4',
                                        ],
                                        'A' => [
                                            'label' => 'Alpha',
                                            'bg' => 'rgba(239,68,68,0.15)',
                                            'color' => '#ef4444',
                                        ],
                                        default => [
                                            'label' => $pm->status,
                                            'bg' => 'rgba(100,116,139,0.15)',
                                            'color' => '#64748b',
                                        ],
                                    };
                                @endphp
                                <tr style="border-bottom: 1px solid var(--border-color);">
                                    <td data-label="Tanggal">
                                        <div style="font-weight: 700; color: var(--text-color); font-size: var(--text-sm, 0.8125rem);">
                                            {{ \Carbon\Carbon::parse($pm->tanggal)->translatedFormat('d M Y') }}
                                        </div>
                                    </td>
                                    <td data-label="Mata Pelajaran">
                                        <div style="font-weight: 700; color: var(--text-color); font-size: var(--text-base, 0.875rem);">
                                            <i class="fas fa-book text-primary me-1"></i> {{ $pm->nama_mata_pelajaran }}
                                        </div>
                                    </td>
                                    <td data-label="Guru Pengampu">
                                        <div style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted);">
                                            {{ $pm->nama_guru ?: 'Guru Pengampu' }}
                                        </div>
                                    </td>
                                    <td data-label="Jam Ke"
                                        style="text-align: center; font-size: var(--text-sm, 0.8125rem); font-weight: 600;">
                                        Jam ke-{{ $pm->jam_ke }}
                                    </td>
                                    <td data-label="Status" style="text-align: center;">
                                        <span class="badge"
                                            style="background: {{ $badgeStatus['bg'] }}; color: {{ $badgeStatus['color'] }}; font-size: var(--text-xs, 0.75rem); padding: 4px 8px; font-weight: 700; border-radius: var(--radius-sm, 6px);">
                                            {{ $badgeStatus['label'] }}
                                        </span>
                                    </td>
                                    <td data-label="Keterangan" style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted);">
                                        {{ $pm->keterangan ?: 'Tercatat di sistem' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div
                    style="padding: 32px 16px; text-align: center; color: var(--text-muted); border: 1px dashed var(--border-color); border-radius: var(--radius-lg, 12px);">
                    <i class="fas fa-chalkboard-user"
                        style="font-size: var(--text-3xl, 1.875rem); color: var(--text-muted); margin-bottom: 8px; display: block;"></i>
                    <div style="font-weight: 700; font-size: var(--text-base, 0.875rem); color: var(--text-color);">Belum Ada Riwayat Presensi
                        Mata Pelajaran</div>
                </div>
            @endif
        </div>

        <!-- Panel C: e-Izin Keluar Sekolah -->
        <div id="subPanelIzinKeluar" class="kehadiran-sub-panel" style="display: none;">
            <div
                style="margin-bottom: 14px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;">
                <div style="font-weight: 700; font-size: var(--text-base, 0.875rem); color: var(--text-color);">
                    <i class="fas fa-ticket-alt text-warning me-1"></i> Riwayat Transaksi e-Izin Keluar / Masuk Siswa
                </div>
                <div style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted);">
                    Total: <strong>{{ count($izinKeluarList ?? []) }}</strong> Tiket
                </div>
            </div>

            @if (!empty($izinKeluarList) && count($izinKeluarList) > 0)
                <div class="table-responsive-stack" style="overflow-x: auto; width: 100%; margin: 0;">
                    <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                        <thead>
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <th style="min-width: 130px; padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                    Tiket &amp; Tanggal
                                </th>
                                <th style="min-width: 120px; padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                    Jenis Izin
                                </th>
                                <th style="min-width: 130px; padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center;">
                                    Jam Keluar - Kembali
                                </th>
                                <th style="min-width: 140px; padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                    Alasan &amp; Petugas Piket
                                </th>
                                <th style="min-width: 110px; padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center;">
                                    Status
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($izinKeluarList as $iz)
                                @php
                                    $statusBadge = match ($iz->status ?? '') {
                                        'di_luar' => ['label' => 'Di Luar Sekolah', 'bg' => 'rgba(245,158,11,0.15)', 'color' => '#d97706'],
                                        'kembali' => ['label' => 'Sudah Kembali', 'bg' => 'rgba(16,185,129,0.15)', 'color' => '#10b981'],
                                        'pulang_selesai' => ['label' => 'Pulang Selesai', 'bg' => 'rgba(99,102,241,0.15)', 'color' => '#6366f1'],
                                        'dibatalkan' => ['label' => 'Dibatalkan', 'bg' => 'rgba(239,68,68,0.15)', 'color' => '#ef4444'],
                                        default => ['label' => 'Menunggu Satpam', 'bg' => 'rgba(100,116,139,0.15)', 'color' => '#64748b'],
                                    };
                                    $jenisLabel = match ($iz->jenis_izin ?? '') {
                                        'pulang_cepat' => 'Pulang Cepat',
                                        'terlambat_masuk' => 'Terlambat Masuk',
                                        default => 'Izin Keluar Sebentar',
                                    };
                                @endphp
                                <tr style="border-bottom: 1px solid var(--border-color);">
                                    <td data-label="Tiket &amp; Tanggal">
                                        <div style="font-weight: 700; color: var(--text-color); font-size: var(--text-sm, 0.8125rem);">
                                            {{ $iz->nomor_tiket }}
                                        </div>
                                        <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted);">
                                            {{ \Carbon\Carbon::parse($iz->tanggal)->translatedFormat('d M Y') }}
                                        </div>
                                    </td>
                                    <td data-label="Jenis Izin">
                                        <span class="badge" style="background: rgba(245,158,11,0.15); color: #d97706; font-weight: 700; padding: 4px 8px; border-radius: var(--radius-sm, 6px); font-size: var(--text-xs, 0.75rem);">
                                            {{ $jenisLabel }}
                                        </span>
                                    </td>
                                    <td data-label="Jam Keluar - Kembali" style="text-align: center; font-size: var(--text-sm, 0.8125rem);">
                                        <strong>{{ $iz->jam_izin_keluar ? substr($iz->jam_izin_keluar, 0, 5) : '--:--' }}</strong>
                                        <span style="color: var(--text-muted);">s/d</span>
                                        <strong>{{ $iz->jam_rencana_kembali ? substr($iz->jam_rencana_kembali, 0, 5) : ($iz->jam_kembali_aktual ? substr($iz->jam_kembali_aktual, 0, 5) : '--:--') }}</strong>
                                    </td>
                                    <td data-label="Alasan &amp; Piket">
                                        <div style="font-size: var(--text-sm, 0.8125rem); color: var(--text-color); font-weight: 600;">
                                            {{ $iz->alasan ?: '-' }}
                                        </div>
                                        @if (!empty($iz->nama_piket))
                                            <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted);">
                                                <i class="fas fa-user-shield me-1"></i> Piket: {{ $iz->nama_piket }}
                                            </div>
                                        @endif
                                    </td>
                                    <td data-label="Status" style="text-align: center;">
                                        <span class="badge" style="background: {{ $statusBadge['bg'] }}; color: {{ $statusBadge['color'] }}; font-size: var(--text-xs, 0.75rem); padding: 4px 8px; font-weight: 700; border-radius: var(--radius-sm, 6px);">
                                            {{ $statusBadge['label'] }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div style="padding: 32px 16px; text-align: center; color: var(--text-muted); border: 1px dashed var(--border-color); border-radius: var(--radius-lg, 12px);">
                    <i class="fas fa-ticket-alt" style="font-size: var(--text-3xl, 1.875rem); color: var(--text-muted); margin-bottom: 8px; display: block;"></i>
                    <div style="font-weight: 700; font-size: var(--text-base, 0.875rem); color: var(--text-color);">
                        Belum Ada Catatan Tiket e-Izin Keluar
                    </div>
                </div>
            @endif
        </div>

        <!-- Panel D: Surat Permohonan Izin / Sakit -->
        <div id="subPanelSuratIzin" class="kehadiran-sub-panel" style="display: none;">
            <div
                style="margin-bottom: 14px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;">
                <div style="font-weight: 700; font-size: var(--text-base, 0.875rem); color: var(--text-color);">
                    <i class="fas fa-envelope-open-text text-primary me-1"></i> Daftar Pengajuan Surat Izin / Sakit
                </div>
                <div style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted);">
                    Total: <strong>{{ count($suratIzinList ?? []) }}</strong> Surat
                </div>
            </div>

            @if (!empty($suratIzinList) && count($suratIzinList) > 0)
                <div class="table-responsive-stack" style="overflow-x: auto; width: 100%; margin: 0;">
                    <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                        <thead>
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <th style="min-width: 140px; padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                    Rentang Tanggal
                                </th>
                                <th style="min-width: 100px; padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center;">
                                    Jenis
                                </th>
                                <th style="min-width: 160px; padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                    Alasan Permohonan
                                </th>
                                <th style="min-width: 120px; padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center;">
                                    Status Verifikasi
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($suratIzinList as $si)
                                @php
                                    $siStatusBadge = match ($si->status ?? '') {
                                        'disetujui' => ['label' => 'Disetujui', 'bg' => 'rgba(16,185,129,0.15)', 'color' => '#10b981'],
                                        'ditolak' => ['label' => 'Ditolak', 'bg' => 'rgba(239,68,68,0.15)', 'color' => '#ef4444'],
                                        default => ['label' => 'Menunggu Verifikasi', 'bg' => 'rgba(245,158,11,0.15)', 'color' => '#d97706'],
                                    };
                                    $tMulai = \Carbon\Carbon::parse($si->tanggal_mulai)->translatedFormat('d M Y');
                                    $tSelesai = !empty($si->tanggal_selesai) ? \Carbon\Carbon::parse($si->tanggal_selesai)->translatedFormat('d M Y') : $tMulai;
                                @endphp
                                <tr style="border-bottom: 1px solid var(--border-color);">
                                    <td data-label="Rentang Tanggal">
                                        <div style="font-weight: 700; color: var(--text-color); font-size: var(--text-sm, 0.8125rem);">
                                            {{ $tMulai }}
                                        </div>
                                        @if ($tMulai !== $tSelesai)
                                            <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted);">
                                                s/d {{ $tSelesai }}
                                            </div>
                                        @endif
                                    </td>
                                    <td data-label="Jenis" style="text-align: center;">
                                        <span class="badge" style="background: rgba(99,102,241,0.15); color: #6366f1; font-weight: 700; padding: 4px 8px; border-radius: var(--radius-sm, 6px); font-size: var(--text-xs, 0.75rem);">
                                            {{ strtoupper($si->jenis) }}
                                        </span>
                                    </td>
                                    <td data-label="Alasan Permohonan">
                                        <div style="font-size: var(--text-sm, 0.8125rem); color: var(--text-color); font-weight: 600;">
                                            {{ $si->alasan ?: '-' }}
                                        </div>
                                        @if (!empty($si->catatan_petugas))
                                            <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-top: 2px;">
                                                Catatan: {{ $si->catatan_petugas }}
                                            </div>
                                        @endif
                                    </td>
                                    <td data-label="Status Verifikasi" style="text-align: center;">
                                        <span class="badge" style="background: {{ $siStatusBadge['bg'] }}; color: {{ $siStatusBadge['color'] }}; font-size: var(--text-xs, 0.75rem); padding: 4px 8px; font-weight: 700; border-radius: var(--radius-sm, 6px);">
                                            {{ $siStatusBadge['label'] }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div style="padding: 32px 16px; text-align: center; color: var(--text-muted); border: 1px dashed var(--border-color); border-radius: var(--radius-lg, 12px);">
                    <i class="fas fa-envelope-open-text" style="font-size: var(--text-3xl, 1.875rem); color: var(--text-muted); margin-bottom: 8px; display: block;"></i>
                    <div style="font-weight: 700; font-size: var(--text-base, 0.875rem); color: var(--text-color);">
                        Belum Ada Pengajuan Surat Permohonan Izin / Sakit
                    </div>
                </div>
            @endif
        </div>

        <!-- Panel E: Catatan Tata Tertib & Kedisiplinan -->
        <div id="subPanelKedisiplinan" class="kehadiran-sub-panel" style="display: none;">
            <div
                style="margin-bottom: 14px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;">
                <div style="font-weight: 700; font-size: var(--text-base, 0.875rem); color: var(--text-color);">
                    <i class="fas fa-triangle-exclamation text-danger me-1"></i> Catatan Tata Tertib &amp; Kedisiplinan Siswa
                </div>
                <div style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted);">
                    Total: <strong>{{ count($kedisiplinanList ?? []) }}</strong> Catatan
                </div>
            </div>

            @if (!empty($kedisiplinanList) && count($kedisiplinanList) > 0)
                <div class="table-responsive-stack" style="overflow-x: auto; width: 100%; margin: 0;">
                    <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                        <thead>
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <th style="min-width: 110px; padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                    Tanggal
                                </th>
                                <th style="min-width: 160px; padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                    Aturan / Pelanggaran
                                </th>
                                <th style="min-width: 80px; padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center;">
                                    Poin
                                </th>
                                <th style="min-width: 160px; padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                    Keterangan &amp; Lokasi
                                </th>
                                <th style="min-width: 120px; padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center;">
                                    Tindak Lanjut
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($kedisiplinanList as $kp)
                                <tr style="border-bottom: 1px solid var(--border-color);">
                                    <td data-label="Tanggal">
                                        <div style="font-weight: 700; color: var(--text-color); font-size: var(--text-sm, 0.8125rem);">
                                            {{ \Carbon\Carbon::parse($kp->tanggal_kejadian)->translatedFormat('d M Y') }}
                                        </div>
                                    </td>
                                    <td data-label="Aturan / Pelanggaran">
                                        <div style="font-weight: 700; color: var(--text-color); font-size: var(--text-sm, 0.8125rem);">
                                            {{ $kp->nama_aturan ?: 'Tata Tertib Sekolah' }}
                                        </div>
                                        @if (!empty($kp->kode_aturan))
                                            <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted);">
                                                Kode: {{ $kp->kode_aturan }}
                                            </div>
                                        @endif
                                    </td>
                                    <td data-label="Poin" style="text-align: center;">
                                        <span class="badge" style="background: rgba(239,68,68,0.15); color: #ef4444; font-weight: 800; padding: 4px 8px; border-radius: var(--radius-sm, 6px); font-size: var(--text-xs, 0.75rem);">
                                            +{{ $kp->poin }}
                                        </span>
                                    </td>
                                    <td data-label="Keterangan &amp; Lokasi">
                                        <div style="font-size: var(--text-sm, 0.8125rem); color: var(--text-color); font-weight: 600;">
                                            {{ $kp->keterangan ?: '-' }}
                                        </div>
                                        @if (!empty($kp->tempat_kejadian))
                                            <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-top: 2px;">
                                                <i class="fas fa-location-dot me-1"></i> {{ $kp->tempat_kejadian }}
                                            </div>
                                        @endif
                                    </td>
                                    <td data-label="Tindak Lanjut" style="text-align: center;">
                                        <span class="badge" style="background: rgba(100,116,139,0.15); color: #64748b; font-size: var(--text-xs, 0.75rem); padding: 4px 8px; font-weight: 700; border-radius: var(--radius-sm, 6px);">
                                            {{ $kp->status_tindak_lanjut ?: 'Dicatat' }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div style="padding: 32px 16px; text-align: center; color: var(--text-muted); border: 1px dashed var(--border-color); border-radius: var(--radius-lg, 12px);">
                    <i class="fas fa-shield-heart" style="font-size: var(--text-3xl, 1.875rem); color: #10b981; margin-bottom: 8px; display: block;"></i>
                    <div style="font-weight: 700; font-size: var(--text-base, 0.875rem); color: var(--text-color);">
                        Tidak Ada Catatan Pelanggaran Tata Tertib
                    </div>
                    <div style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); margin-top: 4px;">
                        Pertahankan kedisiplinan dan perilaku positif ananda.
                    </div>
                </div>
            @endif
        </div>
            </div> <!-- End #panelTabelContainer -->
        </div> <!-- End #calMainSection -->
    </div> <!-- End .card -->
@endsection

@push('scripts')
    <script
        src="{{ asset('js/orang-tua-kehadiran.js') }}?v={{ file_exists(public_path('js/orang-tua-kehadiran.js')) ? filemtime(public_path('js/orang-tua-kehadiran.js')) : time() }}">
    </script>
@endpush
