@extends('layouts.dashboard')

@section('title', 'Presensi Kelas & Mapel — SAE')
@section('dash_title', 'Presensi Kelas')

@push('styles')
    <link rel="stylesheet"
        href="{{ asset('css/presensi.css') }}?v={{ file_exists(public_path('css/presensi.css')) ? filemtime(public_path('css/presensi.css')) : time() }}">
@endpush

@section('content')
    <!-- Header Banner Baku SAE -->
    <div class="dash-banner" style="margin-bottom: 20px;">
        <div>
            <h2 style="font-size: var(--text-2xl, 1.5rem); font-weight: 800; color: var(--text-color); margin-bottom: 4px;">
                <i class="fas fa-users-viewfinder text-primary me-2"></i> Presensi &amp; Monitoring Kehadiran Kelas
            </h2>
            <p style="color: var(--text-muted); font-size: var(--text-base, 0.875rem); margin: 0;">
                Monitoring keterisian presensi rombel (otomatis RFID &amp; manual) serta pencatatan presensi per kelas KBM.
            </p>
        </div>
        <div class="dash-banner-actions" style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
            <a href="{{ route('dashboard.presensi-mengajar.index') }}" class="btn btn-outline"
                style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px); font-size: var(--text-md, 1rem); text-decoration: none;"
                title="Presensi Mengajar Guru">
                <i class="fas fa-calendar-check text-primary"></i>
            </a>
            @if ($selectedRombel && $siswaList->isNotEmpty())
                <button type="button" class="btn btn-primary btn-tab2-action" id="btnHadirSemua"
                    data-rombel="{{ $selectedRombelId }}" data-rombel-nama="{{ $selectedRombel->nama }}"
                    data-pembelajaran="{{ $selectedPembelajaranId }}" data-jam-ke="{{ $jamKe }}"
                    style="width: 38px; height: 38px; padding: 0; display: {{ ($activeTab ?? 'monitoring') === 'detail' ? 'inline-flex' : 'none' }}; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px); font-size: var(--text-md, 1rem);"
                    title="Tandai Hadir Seluruh Peserta Didik">
                    <i class="fas fa-check-double"></i>
                </button>
                <button type="button" class="btn btn-outline btn-tab2-action" id="btnResetPresensiKelas"
                    data-rombel="{{ $selectedRombelId }}" data-rombel-nama="{{ $selectedRombel->nama }}"
                    data-pembelajaran="{{ $selectedPembelajaranId }}"
                    style="width: 38px; height: 38px; padding: 0; display: {{ ($activeTab ?? 'monitoring') === 'detail' ? 'inline-flex' : 'none' }}; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px); font-size: var(--text-md, 1rem); color: var(--danger, #ef4444); border-color: rgba(239,68,68,0.4);"
                    title="Reset / Kosongkan Presensi Mapel Kelas">
                    <i class="fas fa-rotate-left"></i>
                </button>
            @endif
        </div>
    </div>

    <!-- 1. Navigasi 2 Tab Presensi Kelas & Monitoring (Baku SAE) -->
    <div class="dash-tabs-nav dash-desktop-tabs" style="margin-bottom: 20px;">
        <button type="button"
            class="btn {{ ($activeTab ?? 'monitoring') === 'monitoring' ? 'btn-primary' : 'btn-outline' }} pk-tab-btn"
            data-tab="monitoring">
            <i class="fas fa-chart-pie me-1"></i> Monitoring Presensi Seluruh Kelas
            <span class="badge"
                style="background: rgba(16,185,129,0.15); color: var(--success, #10b981); font-weight: 700; margin-left: 6px; font-size: var(--text-xs, 0.75rem);">
                {{ $monitoringStats['sudah_presensi'] ?? 0 }}/{{ $monitoringStats['total_rombel'] ?? 0 }} Selesai
            </span>
        </button>
        <button type="button"
            class="btn {{ ($activeTab ?? 'monitoring') === 'detail' ? 'btn-primary' : 'btn-outline' }} pk-tab-btn"
            data-tab="detail">
            <i class="fas fa-users-viewfinder me-1"></i> Presensi Per Kelas &amp; Mapel
            @if ($selectedRombel)
                <span class="badge badge-primary ms-1" style="font-size: var(--text-xs, 0.75rem);">{{ $selectedRombel->nama }}</span>
            @endif
        </button>
    </div>

    <!-- Mobile Custom Dropdown Selector Baku SAE -->
    <div class="dash-mobile-tab-select-wrap" style="margin-bottom: 20px;">
        <div class="dash-custom-dropdown">
            <button type="button" class="custom-dropdown-trigger" id="pkMobileDropdownTrigger">
                <div class="custom-dropdown-trigger-label">
                    <i
                        class="fas {{ ($activeTab ?? 'monitoring') === 'monitoring' ? 'fa-chart-pie' : 'fa-users-viewfinder' }} text-primary me-2"></i>
                    <span
                        id="pkMobileDropdownLabel">{{ ($activeTab ?? 'monitoring') === 'monitoring' ? 'Monitoring Seluruh Kelas' : 'Presensi Per Kelas & Mapel' }}</span>
                </div>
                <i class="fas fa-chevron-down custom-dropdown-arrow"></i>
            </button>
            <div class="custom-dropdown-menu" id="pkMobileDropdownMenu">
                <button type="button"
                    class="custom-dropdown-item pk-tab-btn {{ ($activeTab ?? 'monitoring') === 'monitoring' ? 'active' : '' }}"
                    data-tab="monitoring">
                    <i class="fas fa-chart-pie text-primary me-2"></i> Monitoring Seluruh Kelas
                </button>
                <button type="button"
                    class="custom-dropdown-item pk-tab-btn {{ ($activeTab ?? 'monitoring') === 'detail' ? 'active' : '' }}"
                    data-tab="detail">
                    <i class="fas fa-users-viewfinder text-primary me-2"></i> Presensi Per Kelas &amp; Mapel
                </button>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- TAB PANE 1: MONITORING PRESENSI SELURUH KELAS                             -->
    <!-- ========================================================================= -->
    <div id="tabPaneMonitoring" class="pk-tab-pane"
        style="{{ ($activeTab ?? 'monitoring') === 'monitoring' ? 'display: block;' : 'display: none;' }}">
        <!-- Mini-Stats Ringkasan Sekolah Hari Ini -->
        <div class="dash-stat-grid"
            style="grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 12px; margin-bottom: 20px;">
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(99,102,241,0.15); color: var(--primary);">
                    <i class="fas fa-school"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem);">{{ $monitoringStats['total_rombel'] ?? 0 }}
                    </div>
                    <div class="dash-stat-label">Total Rombel</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(16,185,129,0.15); color: var(--success, #10b981);">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem); color: var(--success, #10b981);">
                        {{ $monitoringStats['sudah_presensi'] ?? 0 }}</div>
                    <div class="dash-stat-label">Sudah Presensi</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(239,68,68,0.15); color: var(--danger, #ef4444);">
                    <i class="fas fa-times-circle"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem); color: var(--danger, #ef4444);">
                        {{ $monitoringStats['belum_presensi'] ?? 0 }}</div>
                    <div class="dash-stat-label">Belum Presensi</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(59,130,246,0.15); color: var(--primary, #3b82f6);">
                    <i class="fas fa-qrcode"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem); color: var(--primary, #3b82f6);">
                        {{ $monitoringStats['total_otomatis'] ?? 0 }}</div>
                    <div class="dash-stat-label">Otomatis (RFID/QR)</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(245,158,11,0.15); color: var(--warning, #f59e0b);">
                    <i class="fas fa-pen-to-square"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem); color: var(--warning, #f59e0b);">
                        {{ $monitoringStats['total_manual'] ?? 0 }}</div>
                    <div class="dash-stat-label">Manual (Wali/Guru)</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(6,182,212,0.15); color: var(--accent, #06b6d4);">
                    <i class="fas fa-user-check"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem); color: var(--accent, #06b6d4);">
                        {{ number_format($monitoringStats['total_hadir_sekolah'] ?? 0) }}</div>
                    <div class="dash-stat-label">Siswa Hadir Total</div>
                </div>
            </div>
        </div>

        <!-- Filter Bar Card Monitoring Baku SAE -->
        <div class="card"
            style="padding: 16px; margin-bottom: 20px; border-radius: var(--radius-xl, 16px); background: var(--bg-card); border: 1px solid var(--border-color);">
            <form action="{{ route('dashboard.presensi.kelas') }}" method="GET" id="formFilterMonitoring"
                style="margin: 0;">
                <input type="hidden" name="tab" value="monitoring">
                <div
                    style="display: flex; flex-wrap: wrap; gap: 12px; justify-content: space-between; align-items: center;">
                    <div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
                        <!-- Tanggal Monitoring -->
                        <div
                            style="display: inline-flex; align-items: center; gap: 6px; background: var(--card-bg); border: 1px solid var(--border-color); border-radius: var(--radius-md, 8px); padding: 0 10px; height: 38px; box-sizing: border-box; flex-shrink: 0;">
                            <i class="fas fa-calendar-day text-primary" style="font-size: var(--text-sm, 0.8125rem);"
                                title="Tanggal Monitoring"></i>
                            <input type="date" id="monitorTanggalInput" name="tanggal" value="{{ $tanggal }}"
                                style="background: transparent; border: none; color: var(--text-color); font-size: var(--text-sm, 0.8125rem); padding: 0; outline: none; width: 125px; cursor: pointer;"
                                onchange="this.form.submit()">
                        </div>

                        <!-- Filter Tingkat -->
                        <select name="monitor_tingkat" id="filterMonitorTingkat" class="toolbar-filter-select"
                            style="min-width: 135px; height: 38px;" onchange="this.form.submit()">
                            <option value="">Semua Tingkat</option>
                            <option value="10" {{ request('monitor_tingkat') == '10' ? 'selected' : '' }}>Kelas X
                            </option>
                            <option value="11" {{ request('monitor_tingkat') == '11' ? 'selected' : '' }}>Kelas XI
                            </option>
                            <option value="12" {{ request('monitor_tingkat') == '12' ? 'selected' : '' }}>Kelas XII
                            </option>
                        </select>

                        <!-- Filter Status Presensi -->
                        <select name="monitor_status" id="filterMonitorStatus" class="toolbar-filter-select"
                            style="min-width: 145px; height: 38px;" onchange="this.form.submit()">
                            <option value="">Semua Status</option>
                            <option value="sudah" {{ request('monitor_status') === 'sudah' ? 'selected' : '' }}>Sudah
                                Presensi</option>
                            <option value="belum" {{ request('monitor_status') === 'belum' ? 'selected' : '' }}>Belum
                                Presensi</option>
                        </select>

                        <!-- Filter Metode Presensi -->
                        <select name="monitor_metode" id="filterMonitorMetode" class="toolbar-filter-select"
                            style="min-width: 165px; height: 38px;" onchange="this.form.submit()">
                            <option value="">Semua Metode</option>
                            <option value="otomatis" {{ request('monitor_metode') === 'otomatis' ? 'selected' : '' }}>
                                Otomatis (RFID/QR)</option>
                            <option value="manual" {{ request('monitor_metode') === 'manual' ? 'selected' : '' }}>Manual
                                (Wali/Guru)</option>
                            <option value="kombinasi" {{ request('monitor_metode') === 'kombinasi' ? 'selected' : '' }}>
                                Otomatis &amp; Manual</option>
                        </select>

                        @if (request('monitor_tingkat') || request('monitor_status') || request('monitor_metode') || request('monitor_q'))
                            <a href="{{ route('dashboard.presensi.kelas', ['tab' => 'monitoring', 'tanggal' => $tanggal]) }}"
                                class="btn btn-outline"
                                style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px);"
                                title="Reset Filter">
                                <i class="fas fa-undo"></i>
                            </a>
                        @endif
                    </div>

                    <!-- Live Search Nama Kelas / Wali Kelas -->
                    <div class="live-search-wrap" style="height: 38px; min-width: 220px;">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" id="liveSearchMonitorInput" name="monitor_q"
                            value="{{ request('monitor_q') }}" placeholder="Cari kelas / wali kelas..."
                            autocomplete="off">
                        <button type="button" id="clearSearchMonitorBtn"
                            class="clear-search {{ request('monitor_q') ? 'visible' : '' }}" title="Hapus pencarian">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Tabel Monitoring Presensi Seluruh Kelas Baku SAE -->
        <div class="card table-responsive-stack"
            style="padding: 0; margin-bottom: 24px; border: 1px solid var(--border-color); border-radius: var(--radius-xl, 16px); overflow: hidden; background: var(--bg-card);">
            <table class="table table-pd" id="tableMonitoringKelas"
                style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                <thead>
                    <tr style="background: rgba(255, 255, 255, 0.02); border-bottom: 1px solid var(--border-color);">
                        <th
                            style="padding: 12px 14px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 50px; text-align: center;">
                            No</th>
                        <th
                            style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                            Kelas &amp; Jurusan</th>
                        <th
                            style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                            Wali Kelas</th>
                        <th
                            style="padding: 12px 14px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 85px;">
                            Siswa</th>
                        <th
                            style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 140px;">
                            Status Presensi</th>
                        <th
                            style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 165px;">
                            Metode Presensi</th>
                        <th
                            style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 200px;">
                            Rincian Kehadiran</th>
                        <th
                            style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 110px;">
                            % Hadir</th>
                        <th
                            style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: right; width: 120px;">
                            Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($filteredMonitoring as $index => $m)
                        <tr class="row-monitoring-item" data-nama="{{ strtolower($m->nama) }}"
                            data-wali="{{ strtolower($m->wali_kelas) }}"
                            data-status="{{ $m->sudah_presensi ? 'sudah' : 'belum' }}" data-metode="{{ $m->metode }}"
                            data-tingkat="{{ $m->tingkat_pendidikan_id }}"
                            style="border-bottom: 1px solid var(--border-color); transition: background 0.15s ease;">
                            <td
                                style="padding: 12px 14px; text-align: center; color: var(--text-muted); font-size: var(--text-sm, 0.8125rem);">
                                <span class="badge"
                                    style="background: rgba(99,102,241,0.08); color: var(--primary); font-weight: 700; font-size: var(--text-xs, 0.75rem);">#{{ $loop->iteration }}</span>
                            </td>
                            <td style="padding: 12px 16px;">
                                <div
                                    style="font-weight: 700; color: var(--text-color); font-size: var(--text-base, 0.875rem); display: flex; align-items: center; gap: 6px;">
                                    <i class="fas fa-chalkboard text-primary"></i>
                                    <span>{{ $m->nama }}</span>
                                </div>
                                <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-top: 2px;">
                                    Tingkat {{ $m->tingkat_pendidikan_id }} &bull; {{ $m->jurusan }}
                                </div>
                            </td>
                            <td style="padding: 12px 16px;">
                                <div
                                    style="font-weight: 600; color: var(--text-color); font-size: var(--text-base, 0.875rem); display: flex; align-items: center; gap: 6px;">
                                    <i class="fas fa-user-tie text-muted" style="font-size: var(--text-xs, 0.75rem);"></i>
                                    <span>{{ $m->wali_kelas }}</span>
                                </div>
                            </td>
                            <td style="padding: 12px 14px; text-align: center;">
                                <span class="badge"
                                    style="background: var(--bg-hover); color: var(--text-color); font-weight: 700; font-size: var(--text-sm, 0.8125rem); padding: 3px 8px; border: 1px solid var(--border-color);">
                                    {{ $m->total_siswa }}
                                </span>
                            </td>
                            <td style="padding: 12px 16px; text-align: center;">
                                @if ($m->sudah_presensi)
                                    <span class="badge badge-success" style="font-size: var(--text-sm, 0.8125rem); padding: 4px 9px;">
                                        <i class="fas fa-circle-check me-1"></i> Sudah Presensi
                                    </span>
                                @else
                                    <span class="badge badge-danger" style="font-size: var(--text-sm, 0.8125rem); padding: 4px 9px;">
                                        <i class="fas fa-circle-xmark me-1"></i> Belum Presensi
                                    </span>
                                @endif
                            </td>
                            <td style="padding: 12px 16px; text-align: center;">
                                @if ($m->metode === 'otomatis')
                                    <span class="badge"
                                        style="background: rgba(59,130,246,0.15); color: var(--primary, #3b82f6); font-size: var(--text-xs, 0.75rem); padding: 4px 8px;"
                                        title="{{ $m->total_otomatis }} siswa tap RFID/QR">
                                        <i class="fas fa-qrcode me-1"></i> Otomatis (RFID)
                                    </span>
                                    @if ($m->waktu_pertama)
                                        <div
                                            style="font-size: var(--text-2xs, 0.6875rem); color: var(--text-muted); margin-top: 2px; font-family: monospace;">
                                            {{ $m->waktu_pertama }} - {{ $m->waktu_terakhir }}
                                        </div>
                                    @endif
                                @elseif ($m->metode === 'manual')
                                    <span class="badge"
                                        style="background: rgba(245,158,11,0.15); color: var(--warning, #f59e0b); font-size: var(--text-xs, 0.75rem); padding: 4px 8px;"
                                        title="Dicatat manual">
                                        <i class="fas fa-pen-to-square me-1"></i> {{ $m->metode_label }}
                                    </span>
                                @elseif ($m->metode === 'kombinasi')
                                    <span class="badge"
                                        style="background: rgba(139,92,246,0.15); color: var(--purple, #8b5cf6); font-size: var(--text-xs, 0.75rem); padding: 4px 8px;"
                                        title="Otomatis ({{ $m->total_otomatis }}) & Manual ({{ $m->total_manual }})">
                                        <i class="fas fa-layer-group me-1"></i> Otomatis &amp; Manual
                                    </span>
                                @else
                                    <span class="badge"
                                        style="background: rgba(148,163,184,0.12); color: var(--text-muted); font-size: var(--text-xs, 0.75rem); padding: 4px 8px;">
                                        <i class="fas fa-minus me-1"></i> Belum Ada
                                    </span>
                                @endif
                            </td>
                            <td style="padding: 12px 16px; text-align: center;">
                                <div
                                    style="display: flex; gap: 4px; justify-content: center; align-items: center; flex-wrap: wrap;">
                                    <span class="badge badge-success" style="font-size: var(--text-xs, 0.75rem); padding: 2px 6px;"
                                        title="Hadir (termasuk tepat waktu & terlambat)">H: {{ $m->hadir }}</span>
                                    @if ($m->terlambat > 0)
                                        <span class="badge badge-warning" style="font-size: var(--text-xs, 0.75rem); padding: 2px 6px;"
                                            title="Terlambat">T: {{ $m->terlambat }}</span>
                                    @endif
                                    @if ($m->izin > 0)
                                        <span class="badge badge-primary" style="font-size: var(--text-xs, 0.75rem); padding: 2px 6px;"
                                            title="Izin">I: {{ $m->izin }}</span>
                                    @endif
                                    @if ($m->sakit > 0)
                                        <span class="badge"
                                            style="background: rgba(236,72,153,0.15); color: #ec4899; font-size: var(--text-xs, 0.75rem); padding: 2px 6px;"
                                            title="Sakit">S: {{ $m->sakit }}</span>
                                    @endif
                                    @if ($m->alpha > 0)
                                        <span class="badge badge-danger" style="font-size: var(--text-xs, 0.75rem); padding: 2px 6px;"
                                            title="Alpha">A: {{ $m->alpha }}</span>
                                    @endif
                                    @if ($m->belum > 0)
                                        <span class="badge"
                                            style="background: var(--bg-hover); color: var(--text-muted); font-size: var(--text-xs, 0.75rem); padding: 2px 6px;"
                                            title="Belum Presensi">-: {{ $m->belum }}</span>
                                    @endif
                                </div>
                            </td>
                            <td style="padding: 12px 16px; text-align: center;">
                                <div style="display: flex; align-items: center; gap: 6px; justify-content: center;">
                                    <div
                                        style="flex: 1; max-width: 55px; height: 6px; background: rgba(148,163,184,0.2); border-radius: var(--radius-xs, 4px); overflow: hidden;">
                                        <div
                                            style="width: {{ min(100, $m->persen) }}%; height: 100%; background: {{ $m->persen >= 85 ? 'var(--success, #10b981)' : ($m->persen >= 60 ? '#f59e0b' : '#ef4444') }}; border-radius: var(--radius-xs, 4px);">
                                        </div>
                                    </div>
                                    <span
                                        style="font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: {{ $m->persen >= 85 ? 'var(--success, #10b981)' : ($m->persen >= 60 ? '#f59e0b' : '#ef4444') }}; min-width: 38px; text-align: right;">{{ $m->persen }}%</span>
                                </div>
                            </td>
                            <td style="padding: 12px 18px; text-align: right;">
                                <a href="{{ route('dashboard.presensi.kelas', ['tab' => 'detail', 'rombel_id' => $m->rombongan_belajar_id, 'tanggal' => $tanggal]) }}"
                                    class="btn btn-outline btn-sm btn-open-class-detail"
                                    data-rombel="{{ $m->rombongan_belajar_id }}"
                                    style="font-size: var(--text-sm, 0.8125rem); padding: 4px 10px; border-radius: var(--radius-md, 8px); display: inline-flex; align-items: center; gap: 5px; white-space: nowrap;"
                                    title="Lihat & Catat Presensi Siswa Kelas {{ $m->nama }}">
                                    <span>Buka</span> <i class="fas fa-arrow-right"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" style="padding: 35px 20px; text-align: center; color: var(--text-muted);">
                                <i class="fas fa-filter-circle-xmark"
                                    style="font-size: var(--text-3xl, 1.875rem); opacity: 0.4; margin-bottom: 8px; display: block;"></i>
                                <span style="font-size: var(--text-base, 0.875rem); font-weight: 600;">Tidak ada data rombongan belajar yang
                                    sesuai filter.</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- TAB PANE 2: PRESENSI PER KELAS & MAPEL (DETAIL SISWA)                     -->
    <!-- ========================================================================= -->
    <div id="tabPaneDetail" class="pk-tab-pane"
        style="{{ ($activeTab ?? 'monitoring') === 'detail' ? 'display: block;' : 'display: none;' }}">

        @if ($rombelList->isEmpty())
            <!-- Empty State jika Guru belum ada tugas rombel / jadwal KBM -->
            <div class="card" style="padding: 40px 20px; text-align: center; margin-top: 20px;">
                <div
                    style="width: 70px; height: 70px; border-radius: 50%; background: rgba(99,102,241,0.12); color: var(--primary); display: inline-flex; align-items: center; justify-content: center; font-size: var(--text-3xl, 1.875rem); margin-bottom: 16px;">
                    <i class="fas fa-chalkboard-user"></i>
                </div>
                <h3 style="font-size: var(--text-xl, 1.25rem); font-weight: 700; color: var(--text-color); margin-bottom: 8px;">Belum Ada
                    Kelas
                    Mengajar Terdaftar</h3>
                <p style="color: var(--text-muted); font-size: var(--text-base, 0.875rem); max-width: 540px; margin: 0 auto;">
                    Akun Anda belum terdaftar mengampu rombongan belajar pada data Pembelajaran atau Jadwal KBM. Silakan
                    hubungi
                    Waka Kurikulum atau Operator Dapodik sekolah untuk sinkronisasi pembagian jam mengajar Anda.
                </p>
            </div>
        @else
            <!-- 1. Rekap Status Kelas Mini-Stats Baku SAE (Di Atas Toolbar) -->
            <div class="dash-stat-grid"
                style="grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 12px; margin-bottom: 20px;">
                <div class="dash-stat-card">
                    <div class="dash-stat-icon" style="background: rgba(99,102,241,0.15); color: var(--primary);">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="dash-stat-info">
                        <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem);">{{ $rekap['total'] }}</div>
                        <div class="dash-stat-label">Total Peserta Didik</div>
                    </div>
                </div>
                <div class="dash-stat-card">
                    <div class="dash-stat-icon" style="background: rgba(16,185,129,0.15); color: var(--success, #10b981);">
                        <i class="fas fa-check-double"></i>
                    </div>
                    <div class="dash-stat-info">
                        <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem); color: var(--success, #10b981);">{{ $rekap['hadir'] }}
                        </div>
                        <div class="dash-stat-label">Hadir Mapel</div>
                    </div>
                </div>
                <div class="dash-stat-card">
                    <div class="dash-stat-icon" style="background: rgba(245,158,11,0.15); color: var(--warning, #f59e0b);">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="dash-stat-info">
                        <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem); color: var(--warning, #f59e0b);">
                            {{ $rekap['terlambat'] }}
                        </div>
                        <div class="dash-stat-label">Terlambat</div>
                    </div>
                </div>
                <div class="dash-stat-card">
                    <div class="dash-stat-icon" style="background: rgba(59,130,246,0.15); color: var(--primary, #3b82f6);">
                        <i class="fas fa-envelope-open-text"></i>
                    </div>
                    <div class="dash-stat-info">
                        <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem); color: var(--primary, #3b82f6);">{{ $rekap['izin'] }}
                        </div>
                        <div class="dash-stat-label">Izin Mapel</div>
                    </div>
                </div>
                <div class="dash-stat-card">
                    <div class="dash-stat-icon" style="background: rgba(236,72,153,0.15); color: #ec4899;">
                        <i class="fas fa-notes-medical"></i>
                    </div>
                    <div class="dash-stat-info">
                        <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem); color: #ec4899;">{{ $rekap['sakit'] }}
                        </div>
                        <div class="dash-stat-label">Sakit</div>
                    </div>
                </div>
                <div class="dash-stat-card">
                    <div class="dash-stat-icon" style="background: rgba(239,68,68,0.15); color: var(--danger, #ef4444);">
                        <i class="fas fa-circle-xmark"></i>
                    </div>
                    <div class="dash-stat-info">
                        <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem); color: var(--danger, #ef4444);">{{ $rekap['alpha'] }}
                        </div>
                        <div class="dash-stat-label">Alpha Mapel</div>
                    </div>
                </div>
                <div class="dash-stat-card">
                    <div class="dash-stat-icon" style="background: rgba(100,116,139,0.15); color: var(--text-muted, #64748b);">
                        <i class="fas fa-user-clock"></i>
                    </div>
                    <div class="dash-stat-info">
                        <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem); color: var(--text-muted, #64748b);">{{ $rekap['belum'] }}
                        </div>
                        <div class="dash-stat-label">Belum Dicatat</div>
                    </div>
                </div>
                <div class="dash-stat-card">
                    <div class="dash-stat-icon" style="background: rgba(6,182,212,0.15); color: var(--accent, #06b6d4);">
                        <i class="fas fa-chart-pie"></i>
                    </div>
                    <div class="dash-stat-info">
                        <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem); color: var(--accent, #06b6d4);">{{ $rekap['persen'] }}%
                        </div>
                        <div class="dash-stat-label">% Kehadiran</div>
                    </div>
                </div>
            </div>

            <!-- 2. Filter Bar Card Baku SAE (Di Bawah Mini-Stats Card) -->
            <div class="card" style="padding: 16px; margin-bottom: 20px;">
                <form action="{{ route('dashboard.presensi.kelas') }}" method="GET" id="formFilterPresensiKelas"
                    style="margin: 0;">
                    <input type="hidden" name="sort" value="{{ $sort ?? 'nama' }}">
                    <input type="hidden" name="sort_dir" value="{{ $sortDir ?? 'asc' }}">

                    <div
                        style="display: flex; flex-wrap: wrap; gap: 12px; justify-content: space-between; align-items: center;">
                        <div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
                            <!-- Tampilkan Entri Selector -->
                            <div class="toolbar-entries">
                                <label for="perPageSelect" style="margin: 0;">Tampilkan</label>
                                <select id="perPageSelect" name="per_page" class="per-page-select" style="height: 38px;"
                                    onchange="this.form.submit()">
                                    @foreach ([15, 25, 35, 50, 'all'] as $n)
                                        <option value="{{ $n }}"
                                            {{ ($perPage ?? 25) == $n ? 'selected' : '' }}>
                                            {{ $n === 'all' ? 'Semua' : $n }}
                                        </option>
                                    @endforeach
                                </select>
                                <span>entri</span>
                            </div>

                            <!-- Filter Rombel -->
                            <select name="rombel_id" class="toolbar-filter-select"
                                style="min-width: 150px; height: 38px;" onchange="this.form.submit()"
                                title="Pilih Rombongan Belajar">
                                @foreach ($rombelList as $r)
                                    <option value="{{ $r->rombongan_belajar_id }}"
                                        {{ $selectedRombelId == $r->rombongan_belajar_id ? 'selected' : '' }}>
                                        Kelas {{ $r->nama }}
                                    </option>
                                @endforeach
                            </select>

                            <!-- Filter Mapel -->
                            <select name="pembelajaran_id" id="kelasPembelajaranSelect" class="toolbar-filter-select"
                                style="min-width: 170px; height: 38px;" onchange="this.form.submit()"
                                title="Pilih Mata Pelajaran">
                                @if ($pembelajaranList->isEmpty())
                                    <option value="">-- Tidak ada mapel --</option>
                                @else
                                    @foreach ($pembelajaranList as $p)
                                        <option value="{{ $p->pembelajaran_id }}"
                                            {{ $selectedPembelajaranId == $p->pembelajaran_id ? 'selected' : '' }}>
                                            {{ $p->nama_mata_pelajaran }}
                                        </option>
                                    @endforeach
                                @endif
                            </select>

                            <!-- Filter Tanggal Inline Baku SAE -->
                            <div
                                style="display: inline-flex; align-items: center; gap: 6px; background: var(--card-bg); border: 1px solid var(--border-color); border-radius: var(--radius-md, 8px); padding: 0 10px; height: 38px; box-sizing: border-box; flex-shrink: 0;">
                                <i class="fas fa-calendar-day text-primary" style="font-size: var(--text-sm, 0.8125rem);"
                                    title="Tanggal KBM"></i>
                                <input type="date" id="kelasTanggalInput" name="tanggal" value="{{ $tanggal }}"
                                    style="background: transparent; border: none; color: var(--text-color); font-size: var(--text-sm, 0.8125rem); padding: 0; outline: none; width: 125px; cursor: pointer;"
                                    onchange="this.form.submit()">
                            </div>

                            <!-- Jam Ke Input -->
                            <div
                                style="display: inline-flex; align-items: center; gap: 6px; background: var(--card-bg); border: 1px solid var(--border-color); border-radius: var(--radius-md, 8px); padding: 0 10px; height: 38px; box-sizing: border-box; flex-shrink: 0;">
                                <i class="fas fa-clock text-primary" style="font-size: var(--text-sm, 0.8125rem);" title="Jam Ke"></i>
                                <input type="text" id="kelasJamKeInput" name="jam_ke" value="{{ $jamKe }}"
                                    placeholder="Jam Ke"
                                    style="background: transparent; border: none; color: var(--text-color); font-size: var(--text-sm, 0.8125rem); padding: 0; outline: none; width: 65px;"
                                    onchange="this.form.submit()">
                            </div>
                        </div>

                        <!-- Live Search Box Baku SAE -->
                        <div class="live-search-wrap" style="height: 38px; min-width: 200px;">
                            <i class="fas fa-search search-icon"></i>
                            <input type="text" id="liveSearchKelasInput" name="q" value="{{ request('q') }}"
                                placeholder="Cari nama / NISN..." autocomplete="off">
                            <button type="button" id="clearSearchKelasBtn"
                                class="clear-search {{ request('q') ? 'visible' : '' }}" title="Hapus pencarian">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- 3. Datatable Presensi Kelas Baku SAE -->
            <div class="card table-responsive-stack" id="tableDataContainer"
                style="padding: 0; margin-bottom: 24px; border: 1px solid var(--border-color); border-radius: var(--radius-xl, 16px); overflow: hidden;">
                <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                    <thead>
                        <tr style="background: rgba(255, 255, 255, 0.02); border-bottom: 1px solid var(--border-color);">
                            <th
                                style="padding: 12px 14px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 55px; text-align: center;">
                                No
                            </th>
                            <th class="sortable-th {{ ($sort ?? 'nama') === 'nama' ? 'sorted' : '' }}" data-sort="nama"
                                style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; cursor: pointer;">
                                Peserta Didik
                                <span class="sort-icon">{!! ($sort ?? 'nama') === 'nama' ? (($sortDir ?? 'asc') === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                            </th>
                            <th class="sortable-th {{ ($sort ?? '') === 'nisn' ? 'sorted' : '' }}" data-sort="nisn"
                                style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; cursor: pointer; width: 130px;">
                                NISN
                                <span class="sort-icon">{!! ($sort ?? '') === 'nisn' ? (($sortDir ?? 'asc') === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                            </th>
                            <th class="sortable-th {{ ($sort ?? '') === 'gerbang' ? 'sorted' : '' }}" data-sort="gerbang"
                                style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; cursor: pointer; width: 140px;"
                                title="Status kedatangan di gerbang sekolah (RFID / Kiosk)">
                                Presensi Gerbang
                                <span class="sort-icon">{!! ($sort ?? '') === 'gerbang' ? (($sortDir ?? 'asc') === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                            </th>
                            <th class="sortable-th {{ ($sort ?? '') === 'status' ? 'sorted' : '' }}" data-sort="status"
                                style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; cursor: pointer; width: 130px;">
                                Status Mapel
                                <span class="sort-icon">{!! ($sort ?? '') === 'status' ? (($sortDir ?? 'asc') === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                            </th>
                            <th
                                style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Catatan Mapel
                            </th>
                            <th
                                style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: right; width: 160px;">
                                Aksi Presensi
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($siswaList as $s)
                            <tr id="row-siswa-{{ $s->peserta_didik_id }}" class="row-siswa-item"
                                style="border-bottom: 1px solid var(--border-color); transition: background 0.2s ease;">
                                <!-- Kolom No -->
                                <td data-label="No"
                                    style="padding: 14px 14px; text-align: center; color: var(--text-muted); font-size: var(--text-sm, 0.8125rem);">
                                    <span class="badge"
                                        style="background: rgba(99,102,241,0.08); color: var(--primary); font-weight: 700; font-size: var(--text-xs, 0.75rem);">
                                        #{{ method_exists($siswaList, 'firstItem') ? $siswaList->firstItem() + $loop->index : $loop->iteration }}
                                    </span>
                                </td>

                                <!-- Kolom Peserta Didik -->
                                <td class="cell-pd-nama" style="padding: 14px 18px;" data-label="Siswa">
                                    <div class="pd-main-wrapper" style="display: flex; align-items: center; gap: 12px;">
                                        @if (!empty($s->foto_path))
                                            <div class="pd-foto-thumb"
                                                style="width: 42px; height: 42px; border-radius: var(--radius-lg, 12px); border: 1.5px solid var(--border-color); display: flex; align-items: center; justify-content: center; overflow: hidden; flex-shrink: 0;">
                                                <img src="{{ asset('storage/' . ltrim($s->foto_path, '/')) }}"
                                                    alt="{{ $s->nama }}"
                                                    style="width: 100%; height: 100%; object-fit: cover;">
                                            </div>
                                        @else
                                            <div class="pd-foto-thumb empty"
                                                style="width: 42px; height: 42px; border-radius: var(--radius-lg, 12px); background: rgba(99,102,241,0.08); border: 1.5px dashed rgba(99,102,241,0.4); display: flex; align-items: center; justify-content: center; color: var(--primary); font-size: var(--text-md, 1rem); flex-shrink: 0;">
                                                <i class="fas fa-user"></i>
                                            </div>
                                        @endif
                                        <div class="pd-info">
                                            <div class="pd-title-row"
                                                style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                                                <span class="pd-nama text-nama-siswa"
                                                    style="font-weight: 700; color: var(--text-color);">{{ $s->nama }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Kolom NISN -->
                                <td class="cell-pd-nisn" style="padding: 14px 18px;" data-label="NISN">
                                    <div class="cell-col-right">
                                        <span class="text-nisn-siswa"
                                            style="font-family: monospace; font-size: var(--text-base, 0.875rem); color: var(--text-color);">{{ $s->nisn ?: '-' }}</span>
                                    </div>
                                </td>

                                <!-- Kolom Presensi Gerbang (Sekolah) -->
                                <td class="cell-pd-gerbang" style="padding: 14px 18px; text-align: center;"
                                    data-label="Gerbang">
                                    <div class="cell-col-right" style="align-items: center;">
                                        @if ($s->gerbang_status === 'H')
                                            <span class="badge badge-success" style="font-size: var(--text-xs, 0.75rem);"><i
                                                    class="fas fa-school me-1"></i> Hadir</span>
                                            @if ($s->gerbang_jam_masuk)
                                                <div
                                                    style="font-size: var(--text-xs, 0.75rem); font-family: monospace; color: var(--text-muted); margin-top: 2px;">
                                                    {{ substr($s->gerbang_jam_masuk, 0, 5) }} WIB
                                                </div>
                                            @endif
                                        @elseif ($s->gerbang_status === 'T')
                                            <span class="badge badge-warning" style="font-size: var(--text-xs, 0.75rem);"><i
                                                    class="fas fa-clock me-1"></i> Terlambat</span>
                                            @if ($s->gerbang_jam_masuk)
                                                <div
                                                    style="font-size: var(--text-xs, 0.75rem); font-family: monospace; color: var(--warning); margin-top: 2px;">
                                                    {{ substr($s->gerbang_jam_masuk, 0, 5) }}
                                                    (+{{ $s->gerbang_menit_terlambat }}m)
                                                </div>
                                            @endif
                                        @elseif ($s->gerbang_status === 'I')
                                            <span class="badge badge-primary" style="font-size: var(--text-xs, 0.75rem);"><i
                                                    class="fas fa-envelope me-1"></i> Izin</span>
                                        @elseif ($s->gerbang_status === 'S')
                                            <span class="badge"
                                                style="background: rgba(139,92,246,0.15); color: var(--purple, #8b5cf6); font-size: var(--text-xs, 0.75rem);"><i
                                                    class="fas fa-stethoscope me-1"></i> Sakit</span>
                                        @elseif ($s->gerbang_status === 'A')
                                            <span class="badge badge-danger" style="font-size: var(--text-xs, 0.75rem);"><i
                                                    class="fas fa-xmark me-1"></i> Alpha</span>
                                        @else
                                            <span class="badge"
                                                style="background: rgba(148,163,184,0.12); color: var(--text-muted); font-size: var(--text-xs, 0.75rem);"><i
                                                    class="fas fa-minus me-1"></i> Belum Tap</span>
                                        @endif

                                        @if ($s->gerbang_status_pulang === 'pulang_cepat')
                                            <div style="font-size: var(--text-2xs, 0.6875rem); color: var(--warning, #f59e0b); margin-top: 2px;"
                                                title="Pulang Cepat / Tidak Tap Pulang">
                                                <i class="fas fa-person-walking-arrow-right"></i> Pulang Cepat
                                            </div>
                                        @endif
                                    </div>
                                </td>

                                <!-- Kolom Status Mapel -->
                                <td class="cell-pd-status" id="badge-status-{{ $s->peserta_didik_id }}"
                                    style="padding: 14px 18px; text-align: center;" data-label="Status Mapel">
                                    <div class="cell-col-right" style="align-items: center;">
                                        {!! \App\Models\PresensiMapel::STATUS_BADGES[$s->mapel_status] ??
                                            '<span class="badge badge-outline" style="color: var(--text-muted); font-size: var(--text-xs, 0.75rem);">Belum</span>' !!}
                                    </div>
                                </td>

                                <!-- Kolom Catatan Mapel -->
                                <td class="cell-pd-ket" id="ket-status-{{ $s->peserta_didik_id }}"
                                    style="padding: 14px 18px;" data-label="Catatan">
                                    <div class="cell-col-right">
                                        <span style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted);">
                                            {{ $s->mapel_keterangan ?: '—' }}
                                        </span>
                                    </div>
                                </td>

                                <!-- Kolom Aksi Presensi -->
                                <td class="cell-pd-aksi" style="padding: 14px 18px; text-align: right;"
                                    data-label="Aksi">
                                    <div class="cell-col-right" style="align-items: flex-end;">
                                        <div class="table-actions"
                                            style="justify-content: flex-end; gap: 4px; display: inline-flex;">
                                            <button type="button"
                                                class="btn-status-toggle {{ $s->mapel_status === 'H' ? 'active-H' : '' }}"
                                                data-id="{{ $s->peserta_didik_id }}" data-nama="{{ $s->nama }}"
                                                data-status="H" title="Hadir pada KBM">
                                                H
                                            </button>
                                            <button type="button"
                                                class="btn-status-toggle {{ $s->mapel_status === 'T' ? 'active-T' : '' }}"
                                                data-id="{{ $s->peserta_didik_id }}" data-nama="{{ $s->nama }}"
                                                data-status="T" title="Terlambat Masuk Kelas">
                                                T
                                            </button>
                                            <button type="button"
                                                class="btn-status-toggle {{ $s->mapel_status === 'I' ? 'active-I' : '' }}"
                                                data-id="{{ $s->peserta_didik_id }}" data-nama="{{ $s->nama }}"
                                                data-status="I" title="Izin Mapel">
                                                I
                                            </button>
                                            <button type="button"
                                                class="btn-status-toggle {{ $s->mapel_status === 'S' ? 'active-S' : '' }}"
                                                data-id="{{ $s->peserta_didik_id }}" data-nama="{{ $s->nama }}"
                                                data-status="S" title="Sakit di Jam Mapel">
                                                S
                                            </button>
                                            <button type="button"
                                                class="btn-status-toggle {{ $s->mapel_status === 'A' ? 'active-A' : '' }}"
                                                data-id="{{ $s->peserta_didik_id }}" data-nama="{{ $s->nama }}"
                                                data-status="A" title="Alpha pada Jam Mapel">
                                                A
                                            </button>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7"
                                    style="padding: 40px 20px; text-align: center; color: var(--text-muted);">
                                    <div style="font-size: var(--text-3xl, 1.875rem); opacity: 0.35; margin-bottom: 8px;">
                                        <i class="fas fa-folder-open"></i>
                                    </div>
                                    <div style="font-weight: 700; color: var(--text-color); margin-bottom: 4px;">Tidak Ada
                                        Data
                                        Peserta Didik</div>
                                    <div style="font-size: var(--text-sm, 0.8125rem);">Silakan pilih rombongan belajar di atas untuk
                                        menampilkan
                                        daftar siswa.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Custom Pagination Baku SAE (Acuan: peserta-didik-aktif) --}}
            @include('partials.datatable-pagination', ['paginator' => $siswaList])
        @endif
    </div> <!-- end tabPaneDetail -->

    <!-- Modal Form Pencatatan Izin / Sakit Mapel -->
    <div id="modalIzinSakit" class="modal-backdrop"
        style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.65); backdrop-filter: blur(4px); z-index: 99999 !important; align-items: center; justify-content: center; padding: 12px; box-sizing: border-box;">
        <div class="card"
            style="max-width: 480px; width: 92%; margin: auto; padding: 22px; border-radius: var(--radius-xl, 16px); box-shadow: 0 16px 40px rgba(0,0,0,0.5); border: 1px solid var(--border-color); background: var(--bg-card);">
            <div
                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">
                <h3 id="titleModalIzin"
                    style="font-size: var(--text-lg, 1.125rem); font-weight: 800; color: var(--text-color); margin: 0; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-notes-medical text-primary"></i> Pencatatan Izin / Sakit Mapel
                </h3>
                <button type="button" id="btnCloseIzinModal"
                    style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: var(--text-xl, 1.25rem);" aria-label="Tutup">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form id="formIzinSakit">
                <input type="hidden" id="izinPdId">
                <input type="hidden" id="izinStatusVal">

                <div style="margin-bottom: 14px;">
                    <label
                        style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 600; color: var(--text-muted); margin-bottom: 4px;">Peserta
                        Didik:</label>
                    <div id="izinPdNama" style="font-size: var(--text-md, 1rem); font-weight: 700; color: var(--text-color);">-</div>
                </div>

                <div style="margin-bottom: 16px;">
                    <label for="izinKeteranganInput"
                        style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 600; color: var(--text-color); margin-bottom: 6px;">
                        Keterangan / Alasan KBM <span style="color: var(--danger);">*</span>:
                    </label>
                    <textarea name="keterangan" id="izinKeteranganInput" rows="3" required
                        placeholder="Tuliskan alasan izin/sakit pada jam mapel ini..." class="form-control"
                        style="width: 100%; font-size: var(--text-base, 0.875rem); border-radius: var(--radius-md, 8px); resize: vertical;"></textarea>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 8px;">
                    <button type="button" id="btnCancelIzinModal" class="btn btn-outline"
                        style="padding: 7px 14px; font-size: var(--text-sm, 0.8125rem); border-radius: var(--radius-md, 8px);">
                        Batal
                    </button>
                    <button type="submit" class="btn btn-primary"
                        style="padding: 7px 18px; font-size: var(--text-sm, 0.8125rem); border-radius: var(--radius-md, 8px); font-weight: 600;">
                        <i class="fas fa-check me-1"></i> Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script
        src="{{ asset('js/presensi-kelas.js') }}?v={{ file_exists(public_path('js/presensi-kelas.js')) ? filemtime(public_path('js/presensi-kelas.js')) : time() }}">
    </script>
@endpush
