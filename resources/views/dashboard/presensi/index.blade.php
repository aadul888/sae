@extends('layouts.dashboard')

@section('title', 'Presensi Peserta Didik — SAE')
@section('dash_title', 'Presensi Peserta Didik')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/presensi.css') }}?v={{ file_exists(public_path('css/presensi.css')) ? filemtime(public_path('css/presensi.css')) : time() }}">
@endpush

@section('content')
    <!-- Dash Banner -->
    <div class="dash-banner">
        <div>
            <h2 style="font-size: 1.35rem; font-weight: 800; color: var(--text-color); margin-bottom: 4px;">
                <i class="fas fa-id-card text-primary me-2"></i> Presensi &amp; RFID Realtime
            </h2>
            <p style="color: var(--text-muted); font-size: 0.85rem;">
                Monitoring kehadiran peserta didik otomatis menggunakan kartu RFID fisik, QR Code digital, dan verifikasi kamera live terminal.
            </p>
        </div>
        <div class="dash-banner-actions" style="display: flex; gap: 10px; flex-wrap: wrap;">
            <a href="{{ route('dashboard.presensi.scan') }}" target="_blank" class="btn btn-primary"
                style="padding: 9px 18px; font-size: 0.85rem; font-weight: 700; display: inline-flex; align-items: center; gap: 8px;">
                <i class="fas fa-desktop"></i> Buka Terminal Kiosk Pemindai
            </a>
        </div>
    </div>

    @if ($isLiburKalender)
        <div class="card" style="margin-bottom: 20px; border-left: 4px solid var(--danger); background: rgba(239, 68, 68, 0.08); padding: 16px 20px;">
            <div style="display: flex; align-items: center; gap: 14px;">
                <div style="font-size: 1.8rem; color: var(--danger);"><i class="fas fa-umbrella-beach"></i></div>
                <div>
                    <h4 style="font-size: 1rem; font-weight: 800; color: var(--text-color); margin-bottom: 2px;">
                        Hari Libur Akademik — {{ $agendaHariIni ? $agendaHariIni->nama_kegiatan : 'Kalender Pendidikan' }}
                    </h4>
                    <p style="font-size: 0.82rem; color: var(--text-muted); margin: 0;">
                        Berdasarkan sinkronisasi Kalender Pendidikan, tanggal {{ \Carbon\Carbon::parse($tanggal)->translatedFormat('d F Y') }} adalah hari libur peserta didik. Presensi harian terminal dinonaktifkan secara otomatis.
                    </p>
                </div>
            </div>
        </div>
    @elseif ($isDaringKalender)
        <div class="card" style="margin-bottom: 20px; border-left: 4px solid var(--primary); background: rgba(59, 130, 246, 0.08); padding: 16px 20px;">
            <div style="display: flex; align-items: center; gap: 14px;">
                <div style="font-size: 1.8rem; color: var(--primary);"><i class="fas fa-laptop-house"></i></div>
                <div>
                    <h4 style="font-size: 1rem; font-weight: 800; color: var(--text-color); margin-bottom: 2px;">
                        Pembelajaran Daring (PJJ) — {{ $agendaHariIni ? $agendaHariIni->nama_kegiatan : 'Jadwal PJJ / Belajar Rumah' }}
                    </h4>
                    <p style="font-size: 0.82rem; color: var(--text-muted); margin: 0;">
                        Berdasarkan sinkronisasi Kalender Pendidikan, tanggal {{ \Carbon\Carbon::parse($tanggal)->translatedFormat('d F Y') }} dijadwalkan Pembelajaran Jarak Jauh (Daring/PJJ). Terminal gerbang sekolah dinonaktifkan.
                    </p>
                </div>
            </div>
        </div>
    @endif

    <!-- Summary Stats Grid -->
    <div class="dash-stat-grid" style="grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); margin-bottom: 22px;">
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(59,130,246,0.15); color: var(--primary);">
                <i class="fas fa-users"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="font-size: 1.35rem;">{{ number_format($totalSiswa) }}</div>
                <div class="dash-stat-label">Total Peserta Didik</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(16,185,129,0.15); color: var(--success);">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="font-size: 1.35rem;">{{ number_format($countHadir) }}</div>
                <div class="dash-stat-label">Tepat Waktu</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(245,158,11,0.15); color: var(--warning);">
                <i class="fas fa-clock"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="font-size: 1.35rem;">{{ number_format($countTerlambat) }}</div>
                <div class="dash-stat-label">Terlambat</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(6,182,212,0.15); color: var(--accent);">
                <i class="fas fa-file-signature"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="font-size: 1.35rem;">{{ number_format($countIzin) }}</div>
                <div class="dash-stat-label">Izin</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(139,92,246,0.15); color: var(--purple);">
                <i class="fas fa-notes-medical"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="font-size: 1.35rem;">{{ number_format($countSakit) }}</div>
                <div class="dash-stat-label">Sakit</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(239,68,68,0.15); color: var(--danger);">
                <i class="fas fa-times-circle"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="font-size: 1.35rem;">{{ number_format($countAlpha) }}</div>
                <div class="dash-stat-label">Alpha</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(100,116,139,0.15); color: var(--text-muted);">
                <i class="fas fa-hourglass-start"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="font-size: 1.35rem;">{{ number_format($countBelumAbsen) }}</div>
                <div class="dash-stat-label">Belum Presensi</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(16,185,129,0.15); color: var(--success);">
                <i class="fas fa-chart-pie"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="font-size: 1.35rem;">{{ $persenHadir }}%</div>
                <div class="dash-stat-label">Tingkat Hadir</div>
            </div>
        </div>
    </div>

    <!-- Desktop Navigation Tabs -->
    <div class="dash-desktop-tabs presensi-tabs">
        <button type="button" class="presensi-tab-btn {{ $activeTab === 'log' ? 'active' : '' }}" data-tab="log">
            <i class="fas fa-list-check"></i> Log Presensi Hari Ini
        </button>
        <button type="button" class="presensi-tab-btn {{ $activeTab === 'rekap' ? 'active' : '' }}" data-tab="rekap">
            <i class="fas fa-chart-column"></i> Rekapitulasi &amp; Laporan
        </button>
        <button type="button" class="presensi-tab-btn {{ $activeTab === 'rfid' ? 'active' : '' }}" data-tab="rfid">
            <i class="fas fa-id-card"></i> Manajemen Kartu RFID
        </button>
        <button type="button" class="presensi-tab-btn {{ $activeTab === 'izin' ? 'active' : '' }}" data-tab="izin">
            <i class="fas fa-envelope-open-text"></i> Pengajuan E-Izin
            @if ($izinPending->count() > 0)
                <span class="badge badge-danger" style="font-size: 0.65rem; padding: 2px 6px;">{{ $izinPending->count() }}</span>
            @endif
        </button>
        <button type="button" class="presensi-tab-btn {{ $activeTab === 'pengaturan' ? 'active' : '' }}" data-tab="pengaturan">
            <i class="fas fa-sliders"></i> Pengaturan Jam &amp; Jadwal
        </button>
    </div>

    <!-- Mobile Custom Dropdown Selector (Tab Presensi) -->
    <div class="dash-mobile-tab-select-wrap" style="margin-bottom: 20px;">
        <div class="dash-custom-dropdown">
            <button type="button" class="custom-dropdown-trigger" id="presensiTabDropdownTrigger" aria-haspopup="listbox" aria-expanded="false">
                <div class="custom-dropdown-trigger-label" id="presensiTabDropdownLabel">
                    @if ($activeTab === 'rekap')
                        <i class="fas fa-chart-column text-primary me-2"></i>
                        <span>Rekapitulasi &amp; Laporan</span>
                    @elseif ($activeTab === 'rfid')
                        <i class="fas fa-id-card text-primary me-2"></i>
                        <span>Manajemen Kartu RFID</span>
                    @elseif ($activeTab === 'izin')
                        <i class="fas fa-envelope-open-text text-primary me-2"></i>
                        <span>Pengajuan E-Izin</span>
                        @if ($izinPending->count() > 0)
                            <span class="badge badge-danger badge-sm ms-2">{{ $izinPending->count() }}</span>
                        @endif
                    @elseif ($activeTab === 'pengaturan')
                        <i class="fas fa-sliders text-primary me-2"></i>
                        <span>Pengaturan Jam &amp; Jadwal</span>
                    @else
                        <i class="fas fa-list-check text-primary me-2"></i>
                        <span>Log Presensi Hari Ini</span>
                    @endif
                </div>
                <i class="fas fa-chevron-down custom-dropdown-arrow"></i>
            </button>
            <div class="custom-dropdown-menu" role="listbox">
                <a href="{{ request()->fullUrlWithQuery(['tab' => 'log']) }}" data-tab="log"
                    class="custom-dropdown-item presensi-dropdown-tab {{ $activeTab === 'log' ? 'active' : '' }}">
                    <div class="dropdown-item-left">
                        <i class="fas fa-list-check text-primary me-2"></i>
                        <span>Log Presensi Hari Ini</span>
                    </div>
                </a>
                <a href="{{ request()->fullUrlWithQuery(['tab' => 'rekap']) }}" data-tab="rekap"
                    class="custom-dropdown-item presensi-dropdown-tab {{ $activeTab === 'rekap' ? 'active' : '' }}">
                    <div class="dropdown-item-left">
                        <i class="fas fa-chart-column text-primary me-2"></i>
                        <span>Rekapitulasi &amp; Laporan</span>
                    </div>
                </a>
                <a href="{{ request()->fullUrlWithQuery(['tab' => 'rfid']) }}" data-tab="rfid"
                    class="custom-dropdown-item presensi-dropdown-tab {{ $activeTab === 'rfid' ? 'active' : '' }}">
                    <div class="dropdown-item-left">
                        <i class="fas fa-id-card text-primary me-2"></i>
                        <span>Manajemen Kartu RFID</span>
                    </div>
                </a>
                <a href="{{ request()->fullUrlWithQuery(['tab' => 'izin']) }}" data-tab="izin"
                    class="custom-dropdown-item presensi-dropdown-tab {{ $activeTab === 'izin' ? 'active' : '' }}">
                    <div class="dropdown-item-left">
                        <i class="fas fa-envelope-open-text text-primary me-2"></i>
                        <span>Pengajuan E-Izin</span>
                    </div>
                    @if ($izinPending->count() > 0)
                        <span class="badge badge-danger badge-sm">{{ $izinPending->count() }}</span>
                    @endif
                </a>
                <a href="{{ request()->fullUrlWithQuery(['tab' => 'pengaturan']) }}" data-tab="pengaturan"
                    class="custom-dropdown-item presensi-dropdown-tab {{ $activeTab === 'pengaturan' ? 'active' : '' }}">
                    <div class="dropdown-item-left">
                        <i class="fas fa-sliders text-primary me-2"></i>
                        <span>Pengaturan Jam &amp; Jadwal</span>
                    </div>
                </a>
            </div>
        </div>
    </div>

    <!-- TAB 1: Log Presensi Hari Ini -->
    <div class="presensi-tab-pane" id="tab-log" style="display: {{ $activeTab === 'log' ? 'block' : 'none' }};">
        <!-- Toolbar & Filter Bar -->
        <div class="card" style="padding: 16px; margin-bottom: 20px;">
            <form id="formFilterLog" action="{{ route('dashboard.presensi.index') }}" method="GET">
                <input type="hidden" name="tab" value="log">
                <input type="hidden" name="perPageLog" id="inputHiddenPerPageLog" value="{{ $perPageLog ?? 15 }}">
                
                <div style="display: flex; flex-wrap: wrap; gap: 12px; justify-content: space-between; align-items: center;">
                    <div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
                        <div class="toolbar-entries">
                            <label for="perPageLogSelect" style="margin: 0;">Tampilkan</label>
                            <select id="perPageLogSelect" class="per-page-select">
                                @foreach ([10, 15, 25, 50, 100] as $n)
                                    <option value="{{ $n }}" {{ ($perPageLog ?? 15) == $n ? 'selected' : '' }}>{{ $n }}</option>
                                @endforeach
                            </select>
                            <span>entri</span>
                        </div>

                        <div>
                            <input type="date" name="tanggal" value="{{ $tanggal }}" class="form-control" style="padding: 7px 12px; font-size: 0.82rem;" title="Pilih Tanggal Presensi">
                        </div>

                        <select name="rombel_id" class="toolbar-filter-select" style="min-width: 150px;">
                            <option value="">Semua Kelas</option>
                            @foreach ($rombelList as $r)
                                <option value="{{ $r->rombongan_belajar_id }}" {{ request('rombel_id') == $r->rombongan_belajar_id ? 'selected' : '' }}>
                                    {{ $r->nama }}
                                </option>
                            @endforeach
                        </select>

                        <select name="status" class="toolbar-filter-select" style="min-width: 140px;">
                            <option value="">Semua Status</option>
                            <option value="H" {{ request('status') === 'H' ? 'selected' : '' }}>Hadir Tepat Waktu</option>
                            <option value="T" {{ request('status') === 'T' ? 'selected' : '' }}>Terlambat</option>
                            <option value="I" {{ request('status') === 'I' ? 'selected' : '' }}>Izin</option>
                            <option value="S" {{ request('status') === 'S' ? 'selected' : '' }}>Sakit</option>
                            <option value="A" {{ request('status') === 'A' ? 'selected' : '' }}>Alpha</option>
                        </select>

                        <button type="submit" class="btn btn-primary" style="padding: 7px 14px; font-size: 0.82rem; font-weight: 600;">
                            <i class="fas fa-filter me-1"></i> Filter
                        </button>

                        @if (request('rombel_id') || request('status') || request('search') || $tanggal !== now()->toDateString())
                            <a href="{{ route('dashboard.presensi.index', ['tab' => 'log']) }}" class="btn btn-outline" style="padding: 7px 12px; font-size: 0.82rem;" title="Reset filter">
                                <i class="fas fa-undo me-1"></i> Reset
                            </a>
                        @endif
                    </div>

                    <div class="live-search-wrap">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" id="logSearchInput" name="search" value="{{ request('search') }}" placeholder="Cari nama / NISN..." autocomplete="off">
                        <button type="button" id="clearLogSearch" class="clear-search {{ request('search') ? 'visible' : '' }}" title="Hapus pencarian">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Datatable Log Presensi -->
        <div class="card table-responsive-stack" style="padding: 0; margin-bottom: 24px;">
            <table class="table table-presensi-log" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                <thead>
                    <tr style="background: rgba(255, 255, 255, 0.02); border-bottom: 1px solid var(--border-color);">
                        <th style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 50px; text-align: center;">No</th>
                        <th class="sortable-th {{ $sortLog === 'nama_siswa' ? 'sorted' : '' }}"
                            data-sort="nama_siswa"
                            style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; cursor: pointer;">
                            Nama Peserta Didik
                            <span class="sort-icon">{!! $sortLog === 'nama_siswa' ? ($sortLogDir === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                        </th>
                        <th class="sortable-th {{ $sortLog === 'nama_rombel' ? 'sorted' : '' }}"
                            data-sort="nama_rombel"
                            style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; cursor: pointer;">
                            Rombel
                            <span class="sort-icon">{!! $sortLog === 'nama_rombel' ? ($sortLogDir === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                        </th>
                        <th class="sortable-th {{ $sortLog === 'jam_masuk' ? 'sorted' : '' }}"
                            data-sort="jam_masuk"
                            style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; cursor: pointer;">
                            Jam Masuk
                            <span class="sort-icon">{!! $sortLog === 'jam_masuk' ? ($sortLogDir === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                        </th>
                        <th class="sortable-th {{ $sortLog === 'jam_pulang' ? 'sorted' : '' }}"
                            data-sort="jam_pulang"
                            style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; cursor: pointer;">
                            Jam Pulang
                            <span class="sort-icon">{!! $sortLog === 'jam_pulang' ? ($sortLogDir === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                        </th>
                        <th class="sortable-th {{ $sortLog === 'status' ? 'sorted' : '' }}"
                            data-sort="status"
                            style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; cursor: pointer;">
                            Status
                            <span class="sort-icon">{!! $sortLog === 'status' ? ($sortLogDir === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                        </th>
                        <th style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Keterangan</th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: right;">Snapshot</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $index => $log)
                        <tr style="border-bottom: 1px solid var(--border-color); transition: background 0.2s ease;">
                            <td class="cell-log-no" style="padding: 14px 16px; text-align: center; color: var(--text-muted);" data-label="No">
                                {{ $logs->firstItem() + $index }}
                            </td>
                            <td class="cell-log-siswa" style="padding: 14px 18px;" data-label="Nama Lengkap">
                                <div class="log-siswa-wrapper" style="display: flex; align-items: center; gap: 12px;">
                                    <img src="{{ $log->foto_profil_url ?: asset('img/logo-dark.png') }}"
                                        alt="{{ $log->nama_siswa }}"
                                        class="log-avatar"
                                        style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 1.5px solid var(--border-color); flex-shrink: 0;"
                                        onerror="this.src='/img/logo-dark.png';">
                                    <div>
                                        <div style="font-weight: 700; color: var(--text-color); font-size: 0.9rem;">{{ $log->nama_siswa }}</div>
                                        <div style="font-size: 0.74rem; color: var(--text-muted); font-family: monospace; margin-top: 2px;">NISN: {{ $log->nisn_siswa }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="cell-log-rombel" style="padding: 14px 16px;" data-label="Rombel Kelas">
                                <span class="badge badge-outline" style="font-size: 0.76rem; padding: 3px 8px;">{{ $log->nama_rombel ?: '-' }}</span>
                            </td>
                            <td class="cell-log-masuk" style="padding: 14px 16px;" data-label="Presensi Masuk">
                                @if ($log->jam_masuk)
                                    <div style="font-weight: 700; font-family: monospace; color: var(--text-color); font-size: 0.88rem;">
                                        {{ substr($log->jam_masuk, 0, 5) }} WIB
                                    </div>
                                    <div style="font-size: 0.72rem; color: var(--text-muted);">
                                        <i class="fas fa-rss me-1"></i>{{ strtoupper($log->metode_masuk ?: 'manual') }}
                                    </div>
                                @else
                                    <span style="color: var(--text-muted); font-size: 0.84rem;">-</span>
                                @endif
                            </td>
                            <td class="cell-log-pulang" style="padding: 14px 16px;" data-label="Presensi Pulang">
                                @if ($log->jam_pulang)
                                    <div style="font-weight: 700; font-family: monospace; color: var(--text-color); font-size: 0.88rem;">
                                        {{ substr($log->jam_pulang, 0, 5) }} WIB
                                    </div>
                                    <div style="font-size: 0.72rem; margin-top: 2px;">
                                        @if ($log->status_ketepatan_pulang === 'pulang_cepat')
                                            <span class="badge" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b; border: 1px solid rgba(245, 158, 11, 0.3); font-size: 0.68rem; padding: 1px 6px;">
                                                <i class="fas fa-person-walking-arrow-right me-1"></i> Pulang Cepat
                                            </span>
                                        @else
                                            <span style="color: var(--text-muted);"><i class="fas fa-rss me-1"></i>{{ strtoupper($log->metode_pulang ?: 'manual') }}</span>
                                        @endif
                                    </div>
                                @elseif ($log->status_ketepatan_pulang === 'pulang_cepat')
                                    <div>
                                        <span class="badge" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b; border: 1px solid rgba(245, 158, 11, 0.3); font-size: 0.72rem; padding: 2px 7px;">
                                            <i class="fas fa-person-walking-arrow-right me-1"></i> Pulang Cepat
                                        </span>
                                        <div style="font-size: 0.68rem; color: var(--text-muted); margin-top: 2px;">Tanpa Tap Pulang</div>
                                    </div>
                                @else
                                    <span style="color: var(--text-muted); font-size: 0.84rem;">-</span>
                                @endif
                            </td>
                            <td class="cell-log-status" style="padding: 14px 16px; text-align: center;" data-label="Status Kehadiran">
                                @if ($log->status === 'H')
                                    <span class="badge badge-success" style="font-size: 0.74rem; padding: 3px 8px;"><i class="fas fa-check-circle me-1"></i> Hadir</span>
                                @elseif ($log->status === 'T')
                                    <span class="badge badge-warning" style="font-size: 0.74rem; padding: 3px 8px;" title="Terlambat {{ $log->menit_terlambat }} menit">
                                        <i class="fas fa-clock me-1"></i> +{{ $log->menit_terlambat }}m
                                    </span>
                                @elseif ($log->status === 'I')
                                    <span class="badge badge-primary" style="font-size: 0.74rem; padding: 3px 8px;"><i class="fas fa-file-signature me-1"></i> Izin</span>
                                @elseif ($log->status === 'S')
                                    <span class="badge" style="background: rgba(139,92,246,0.15); color: #8b5cf6; font-size: 0.74rem; padding: 3px 8px;"><i class="fas fa-notes-medical me-1"></i> Sakit</span>
                                @elseif ($log->status === 'D')
                                    <span class="badge badge-accent" style="font-size: 0.74rem; padding: 3px 8px;"><i class="fas fa-award me-1"></i> Dispen</span>
                                @else
                                    <span class="badge badge-danger" style="font-size: 0.74rem; padding: 3px 8px;"><i class="fas fa-times-circle me-1"></i> Alpha</span>
                                @endif
                            </td>
                            <td class="cell-log-keterangan" style="padding: 14px 16px;" data-label="Keterangan">
                                <div style="max-width: 220px; font-size: 0.82rem; color: var(--text-color); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $log->keterangan }}">
                                    {{ $log->keterangan ?: '-' }}
                                </div>
                            </td>
                            <td class="cell-log-snapshot" style="padding: 14px 18px; text-align: right;" data-label="Snapshot Foto">
                                <div class="snapshot-actions" style="display: flex; justify-content: flex-end; gap: 6px;">
                                    @if ($log->foto_masuk_url)
                                        <button type="button" class="btn btn-outline btn-preview-snapshot"
                                            data-url="{{ $log->foto_masuk_url }}"
                                            data-title="Snapshot Masuk — {{ $log->nama_siswa }}"
                                            style="padding: 5px 9px; font-size: 0.75rem;" title="Lihat Snapshot Masuk">
                                            <i class="fas fa-camera text-primary"></i> Masuk
                                        </button>
                                    @endif
                                    @if ($log->foto_pulang_url)
                                        <button type="button" class="btn btn-outline btn-preview-snapshot"
                                            data-url="{{ $log->foto_pulang_url }}"
                                            data-title="Snapshot Pulang — {{ $log->nama_siswa }}"
                                            style="padding: 5px 9px; font-size: 0.75rem;" title="Lihat Snapshot Pulang">
                                            <i class="fas fa-camera text-success"></i> Pulang
                                        </button>
                                    @endif
                                    @if (!$log->foto_masuk_url && !$log->foto_pulang_url)
                                        <span style="color: var(--text-muted); font-size: 0.78rem;">-</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 40px; color: var(--text-muted);">
                                <i class="fas fa-clipboard-question" style="font-size: 2.2rem; margin-bottom: 10px; display: block; opacity: 0.4;"></i>
                                Belum ada data presensi yang tercatat untuk filter tanggal dan rombel ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Custom Pagination for Logs with Entry Summary --}}
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-bottom: 24px; padding: 0 4px;">
            <div style="font-size: 0.82rem; color: var(--text-muted);">
                Menampilkan {{ $logs->firstItem() ?? 0 }} sampai {{ $logs->lastItem() ?? 0 }} dari {{ $logs->total() }} entri
            </div>
            @if ($logs->hasPages())
                <div class="custom-pagination" style="margin: 0; padding: 0;">
                    @if ($logs->onFirstPage())
                        <span class="page-btn disabled"><i class="fas fa-chevron-left"></i></span>
                    @else
                        <a href="{{ $logs->previousPageUrl() }}" class="page-btn" title="Sebelumnya"><i class="fas fa-chevron-left"></i></a>
                    @endif
                    @php
                        $cur = $logs->currentPage();
                        $last = $logs->lastPage();
                        $from = max(1, $cur - 2);
                        $to = min($last, $cur + 2);
                    @endphp
                    @if ($from > 1)
                        <a href="{{ $logs->url(1) }}" class="page-btn">1</a>
                        @if ($from > 2)
                            <span class="page-info">&hellip;</span>
                        @endif
                    @endif
                    @for ($i = $from; $i <= $to; $i++)
                        <a href="{{ $logs->url($i) }}" class="page-btn {{ $i === $cur ? 'current' : '' }}">{{ $i }}</a>
                    @endfor
                    @if ($to < $last)
                        @if ($to < $last - 1)
                            <span class="page-info">&hellip;</span>
                        @endif
                        <a href="{{ $logs->url($last) }}" class="page-btn">{{ $last }}</a>
                    @endif
                    @if ($logs->hasMorePages())
                        <a href="{{ $logs->nextPageUrl() }}" class="page-btn" title="Selanjutnya"><i class="fas fa-chevron-right"></i></a>
                    @else
                        <span class="page-btn disabled"><i class="fas fa-chevron-right"></i></span>
                    @endif
                </div>
            @endif
        </div>
    </div>

    <!-- TAB 2: Rekapitulasi & Laporan -->
    <div class="presensi-tab-pane" id="tab-rekap" style="display: {{ $activeTab === 'rekap' ? 'block' : 'none' }};">
        <div class="card" style="padding: 24px; border-radius: 14px; max-width: 680px; margin-bottom: 24px;">
            <h3 style="font-size: 1.1rem; font-weight: 800; color: var(--text-color); margin-bottom: 6px;">
                <i class="fas fa-file-excel text-success me-2"></i> Ekspor Rekapitulasi Presensi Bulanan
            </h3>
            <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 20px;">
                Unduh rekapitulasi kehadiran peserta didik dalam format CSV / Excel untuk keperluan administrasi kurikulum dan buku rapor.
            </p>

            <form action="{{ route('dashboard.presensi.export') }}" method="GET">
                <div style="display: flex; flex-direction: column; gap: 16px;">
                    <div>
                        <label style="display: block; font-size: 0.85rem; font-weight: 700; color: var(--text-color); margin-bottom: 6px;">
                            Pilih Rombongan Belajar:
                        </label>
                        <select name="rombel_id" class="form-control" style="width: 100%; padding: 10px 14px;">
                            <option value="">-- Semua Kelas / Seluruh Sekolah --</option>
                            @foreach ($rombelList as $r)
                                <option value="{{ $r->rombongan_belajar_id }}">{{ $r->nama }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label style="display: block; font-size: 0.85rem; font-weight: 700; color: var(--text-color); margin-bottom: 6px;">
                            Pilih Bulan &amp; Tahun:
                        </label>
                        <input type="month" name="bulan" value="{{ now()->format('Y-m') }}" class="form-control" style="width: 100%; padding: 10px 14px;">
                    </div>

                    <div style="margin-top: 10px;">
                        <button type="submit" class="btn btn-primary" style="padding: 10px 24px; font-weight: 700; display: inline-flex; align-items: center; gap: 8px;">
                            <i class="fas fa-download"></i> Unduh File Rekap (.CSV)
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- TAB 3: Manajemen Kartu RFID -->
    <div class="presensi-tab-pane" id="tab-rfid" style="display: {{ $activeTab === 'rfid' ? 'block' : 'none' }};">
        <!-- Toolbar & Filter Bar RFID -->
        <div class="card" style="padding: 16px; margin-bottom: 20px;">
            <form id="formFilterRfid" action="{{ route('dashboard.presensi.index') }}" method="GET">
                <input type="hidden" name="tab" value="rfid">
                <input type="hidden" name="tanggal" value="{{ $tanggal }}">
                <input type="hidden" name="perPageRfid" id="inputHiddenPerPageRfid" value="{{ $perPageRfid ?? 15 }}">

                <div style="display: flex; flex-wrap: wrap; gap: 12px; justify-content: space-between; align-items: center;">
                    <div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
                        <div class="toolbar-entries">
                            <label for="perPageRfidSelect" style="margin: 0;">Tampilkan</label>
                            <select id="perPageRfidSelect" class="per-page-select">
                                @foreach ([10, 15, 25, 50, 100] as $n)
                                    <option value="{{ $n }}" {{ ($perPageRfid ?? 15) == $n ? 'selected' : '' }}>{{ $n }}</option>
                                @endforeach
                            </select>
                            <span>entri</span>
                        </div>

                        <select name="rfid_status" class="toolbar-filter-select" style="min-width: 170px;">
                            <option value="">Semua Status Kartu</option>
                            <option value="terdaftar" {{ request('rfid_status') === 'terdaftar' ? 'selected' : '' }}>Sudah Memiliki Kartu</option>
                            <option value="belum" {{ request('rfid_status') === 'belum' ? 'selected' : '' }}>Belum Memiliki Kartu</option>
                        </select>

                        <button type="submit" class="btn btn-primary" style="padding: 7px 14px; font-size: 0.82rem; font-weight: 600;">
                            <i class="fas fa-filter me-1"></i> Filter
                        </button>

                        @if (request('rfid_search') || request('rfid_status'))
                            <a href="{{ route('dashboard.presensi.index', ['tab' => 'rfid', 'tanggal' => $tanggal]) }}" class="btn btn-outline" style="padding: 7px 12px; font-size: 0.82rem;" title="Reset filter">
                                <i class="fas fa-undo me-1"></i> Reset
                            </a>
                        @endif
                    </div>

                    <div class="live-search-wrap">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" id="rfidSearchInput" name="rfid_search" value="{{ request('rfid_search') }}" placeholder="Cari nama, NISN, UID kartu..." autocomplete="off">
                        <button type="button" id="clearRfidSearch" class="clear-search {{ request('rfid_search') ? 'visible' : '' }}" title="Hapus pencarian">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Datatable Siswa RFID -->
        <div class="card table-responsive-stack" style="padding: 0; margin-bottom: 24px;">
            <table class="table table-rfid" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                <thead>
                    <tr style="background: rgba(255, 255, 255, 0.02); border-bottom: 1px solid var(--border-color);">
                        <th style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 50px; text-align: center;">No</th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Nama Peserta Didik</th>
                        <th style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">NISN / NIPD</th>
                        <th style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Rombel</th>
                        <th style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Status Kartu</th>
                        <th style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">UID Kartu Fisik</th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: right; width: 140px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($siswaRfidList as $idx => $s)
                        <tr style="border-bottom: 1px solid var(--border-color); transition: background 0.2s ease;">
                            <td class="cell-rfid-no" style="padding: 14px 16px; text-align: center; color: var(--text-muted);" data-label="No">
                                {{ $siswaRfidList->firstItem() + $idx }}
                            </td>
                            <td class="cell-rfid-siswa" style="padding: 14px 18px;" data-label="Nama Lengkap">
                                <div class="rfid-siswa-wrapper" style="display: flex; align-items: center; gap: 12px;">
                                    <div class="rfid-avatar" style="width: 38px; height: 38px; border-radius: 50%; background: rgba(59,130,246,0.12); color: var(--primary); display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 0.9rem; border: 1px solid var(--border-color); flex-shrink: 0;">
                                        {{ strtoupper(substr($s->nama, 0, 1)) }}
                                    </div>
                                    <div>
                                        <div style="font-weight: 700; color: var(--text-color); font-size: 0.9rem;">{{ $s->nama }}</div>
                                        <div style="font-size: 0.74rem; color: var(--text-muted); font-family: monospace; margin-top: 2px;">ID: {{ substr($s->peserta_didik_id, 0, 8) }}...</div>
                                    </div>
                                </div>
                            </td>
                            <td class="cell-rfid-nisn" style="padding: 14px 16px; font-family: monospace; font-size: 0.84rem; color: var(--primary);" data-label="NISN / NIPD">
                                {{ $s->nisn ?: ($s->nipd ?: '-') }}
                            </td>
                            <td class="cell-rfid-rombel" style="padding: 14px 16px;" data-label="Rombel Kelas">
                                <span class="badge badge-outline" style="font-size: 0.76rem; padding: 3px 8px;">{{ $s->nama_rombel ?: '-' }}</span>
                            </td>
                            <td class="cell-rfid-status" style="padding: 14px 16px;" data-label="Status Kartu">
                                @if ($s->rfid_uid)
                                    <span class="badge badge-success" style="font-size: 0.74rem; padding: 3px 8px;"><i class="fas fa-check me-1"></i> Terpasang</span>
                                @else
                                    <span class="badge" style="background: rgba(148,163,184,0.15); color: var(--text-muted); font-size: 0.74rem; padding: 3px 8px;">Belum Ada Kartu</span>
                                @endif
                            </td>
                            <td class="cell-rfid-uid" style="padding: 14px 16px;" data-label="UID Kartu Fisik">
                                @if ($s->rfid_uid)
                                    <code style="font-size: 0.84rem; font-weight: 700; color: var(--primary);">{{ $s->rfid_uid }}</code>
                                @else
                                    <span style="color: var(--text-muted); font-size: 0.84rem;">-</span>
                                @endif
                            </td>
                            <td class="cell-rfid-aksi" style="padding: 14px 18px; text-align: right;" data-label="Aksi">
                                <div class="rfid-actions" style="display: flex; justify-content: flex-end; gap: 6px;">
                                    <button type="button" class="btn btn-outline btn-assign-rfid"
                                        data-id="{{ $s->peserta_didik_id }}"
                                        data-nama="{{ $s->nama }}"
                                        data-nisn="{{ $s->nisn ?: $s->nipd }}"
                                        data-rfid="{{ $s->rfid_uid }}"
                                        style="padding: 6px 12px; font-size: 0.78rem; font-weight: 600;">
                                        <i class="fas fa-link me-1"></i> {{ $s->rfid_uid ? 'Ubah Kartu' : 'Daftarkan' }}
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 40px; color: var(--text-muted);">
                                <i class="fas fa-id-card-clip" style="font-size: 2.2rem; margin-bottom: 10px; display: block; opacity: 0.4;"></i>
                                Tidak ada data peserta didik yang cocok.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Custom Pagination for RFID List with Entry Summary --}}
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-bottom: 24px; padding: 0 4px;">
            <div style="font-size: 0.82rem; color: var(--text-muted);">
                Menampilkan {{ $siswaRfidList->firstItem() ?? 0 }} sampai {{ $siswaRfidList->lastItem() ?? 0 }} dari {{ $siswaRfidList->total() }} entri
            </div>
            @if ($siswaRfidList->hasPages())
                <div class="custom-pagination" style="margin: 0; padding: 0;">
                    @if ($siswaRfidList->onFirstPage())
                        <span class="page-btn disabled"><i class="fas fa-chevron-left"></i></span>
                    @else
                        <a href="{{ $siswaRfidList->previousPageUrl() }}" class="page-btn" title="Sebelumnya"><i class="fas fa-chevron-left"></i></a>
                    @endif
                    @php
                        $cur = $siswaRfidList->currentPage();
                        $last = $siswaRfidList->lastPage();
                        $from = max(1, $cur - 2);
                        $to = min($last, $cur + 2);
                    @endphp
                    @if ($from > 1)
                        <a href="{{ $siswaRfidList->url(1) }}" class="page-btn">1</a>
                        @if ($from > 2)
                            <span class="page-info">&hellip;</span>
                        @endif
                    @endif
                    @for ($i = $from; $i <= $to; $i++)
                        <a href="{{ $siswaRfidList->url($i) }}" class="page-btn {{ $i === $cur ? 'current' : '' }}">{{ $i }}</a>
                    @endfor
                    @if ($to < $last)
                        @if ($to < $last - 1)
                            <span class="page-info">&hellip;</span>
                        @endif
                        <a href="{{ $siswaRfidList->url($last) }}" class="page-btn">{{ $last }}</a>
                    @endif
                    @if ($siswaRfidList->hasMorePages())
                        <a href="{{ $siswaRfidList->nextPageUrl() }}" class="page-btn" title="Selanjutnya"><i class="fas fa-chevron-right"></i></a>
                    @else
                        <span class="page-btn disabled"><i class="fas fa-chevron-right"></i></span>
                    @endif
                </div>
            @endif
        </div>
    </div>

    <!-- TAB 4: Pengajuan E-Izin -->
    <div class="presensi-tab-pane" id="tab-izin" style="display: {{ $activeTab === 'izin' ? 'block' : 'none' }};">
        <div class="card" style="padding: 18px 20px; border-radius: 14px; margin-bottom: 20px;">
            <h3 style="font-size: 1.05rem; font-weight: 800; color: var(--text-color); margin-bottom: 4px;">
                <i class="fas fa-envelope-open-text text-primary me-2"></i> Pengajuan Izin / Sakit Peserta Didik
            </h3>
            <p style="font-size: 0.82rem; color: var(--text-muted); margin: 0;">
                Daftar permohonan surat izin atau sakit yang diajukan oleh peserta didik untuk diverifikasi oleh Wali Kelas atau Administrator.
            </p>
        </div>

        <div class="card table-responsive-stack" style="padding: 0; margin-bottom: 24px;">
            <table class="table table-izin" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                <thead>
                    <tr style="background: rgba(255, 255, 255, 0.02); border-bottom: 1px solid var(--border-color);">
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Tanggal Pengajuan</th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Nama Peserta Didik</th>
                        <th style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Jenis</th>
                        <th style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Rentang Waktu</th>
                        <th style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Alasan / Keterangan</th>
                        <th style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Lampiran</th>
                        <th style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Status</th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: right; width: 130px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($izinPending as $iz)
                        @php
                            $siswaIzin = \DB::table('peserta_didik')->where('peserta_didik_id', $iz->peserta_didik_id)->first();
                        @endphp
                        <tr style="border-bottom: 1px solid var(--border-color); transition: background 0.2s ease;">
                            <td class="cell-izin-tgl" style="padding: 14px 18px; font-size: 0.82rem; color: var(--text-muted);" data-label="Tanggal Pengajuan">
                                {{ $iz->created_at->translatedFormat('d M Y, H:i') }} WIB
                            </td>
                            <td class="cell-izin-siswa" style="padding: 14px 18px;" data-label="Nama Lengkap">
                                <div class="izin-siswa-wrapper" style="display: flex; align-items: center; gap: 12px;">
                                    <div class="izin-avatar" style="width: 38px; height: 38px; border-radius: 50%; background: rgba(99,102,241,0.12); color: var(--primary); display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 0.9rem; border: 1px solid var(--border-color); flex-shrink: 0;">
                                        <i class="fas fa-user-graduate"></i>
                                    </div>
                                    <div>
                                        <div style="font-weight: 700; color: var(--text-color); font-size: 0.9rem;">{{ $siswaIzin ? $siswaIzin->nama : 'Peserta Didik' }}</div>
                                        <div style="font-size: 0.74rem; color: var(--text-muted); font-family: monospace; margin-top: 2px;">NISN: {{ $iz->nisn }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="cell-izin-jenis" style="padding: 14px 16px;" data-label="Jenis Izin">
                                <span class="badge {{ $iz->jenis === 'sakit' ? 'badge-purple' : 'badge-primary' }}" style="font-size: 0.74rem; padding: 3px 8px;">
                                    {{ $iz->jenis_label }}
                                </span>
                            </td>
                            <td class="cell-izin-rentang" style="padding: 14px 16px;" data-label="Rentang Waktu">
                                <div style="font-weight: 600; font-size: 0.84rem; color: var(--text-color);">
                                    {{ \Carbon\Carbon::parse($iz->tanggal_mulai)->format('d/m/Y') }} s/d {{ \Carbon\Carbon::parse($iz->tanggal_selesai)->format('d/m/Y') }}
                                </div>
                            </td>
                            <td class="cell-izin-alasan" style="padding: 14px 16px;" data-label="Alasan / Keterangan">
                                <div style="max-width: 250px; font-size: 0.82rem; color: var(--text-color); line-height: 1.35;" title="{{ $iz->alasan }}">
                                    {{ $iz->alasan }}
                                </div>
                            </td>
                            <td class="cell-izin-lampiran" style="padding: 14px 16px;" data-label="Lampiran Dokumen">
                                @if ($iz->lampiran_url)
                                    <a href="{{ $iz->lampiran_url }}" target="_blank" class="btn btn-outline" style="padding: 4px 8px; font-size: 0.75rem;">
                                        <i class="fas fa-paperclip me-1"></i> Buka Surat
                                    </a>
                                @else
                                    <span style="color: var(--text-muted); font-size: 0.78rem;">Tanpa Lampiran</span>
                                @endif
                            </td>
                            <td class="cell-izin-status" style="padding: 14px 16px;" data-label="Status">{!! $iz->status_badge !!}</td>
                            <td class="cell-izin-aksi" style="padding: 14px 18px; text-align: right;" data-label="Aksi">
                                <div class="izin-actions" style="display: flex; justify-content: flex-end; gap: 6px;">
                                    <button type="button" class="btn btn-primary btn-verif-izin"
                                        data-id="{{ $iz->id }}"
                                        data-nama="{{ $siswaIzin ? $siswaIzin->nama : 'Peserta Didik' }}"
                                        data-jenis="{{ $iz->jenis_label }}"
                                        data-rentang="{{ \Carbon\Carbon::parse($iz->tanggal_mulai)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($iz->tanggal_selesai)->format('d/m/Y') }}"
                                        data-alasan="{{ $iz->alasan }}"
                                        style="padding: 6px 14px; font-size: 0.78rem; font-weight: 600;">
                                        Verifikasi
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 40px; color: var(--text-muted);">
                                <i class="fas fa-envelope-circle-check" style="font-size: 2.2rem; margin-bottom: 10px; display: block; opacity: 0.4;"></i>
                                Tidak ada pengajuan izin atau sakit yang menunggu verifikasi saat ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- TAB 5: Pengaturan Jam & Jadwal -->
    <div class="presensi-tab-pane" id="tab-pengaturan" style="display: {{ $activeTab === 'pengaturan' ? 'block' : 'none' }};">
        <form id="formPengaturanPresensi">
            <!-- Row 1: 2-Column Grid (Jam Kerja vs Geolokasi GPS) -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(440px, 1fr)); gap: 20px; margin-bottom: 20px;">
                
                <!-- CARD 1: Konfigurasi Jam Kerja & Toleransi Waktu -->
                <div class="card" style="padding: 24px; border-radius: 14px;">
                    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px; border-bottom: 1px solid var(--border-color); padding-bottom: 14px;">
                        <div style="width: 40px; height: 40px; border-radius: 10px; background: rgba(99, 102, 241, 0.15); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 1.2rem; flex-shrink: 0;">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div>
                            <h3 style="font-size: 1.1rem; font-weight: 800; color: var(--text-color); margin: 0;">
                                Konfigurasi Jam Kerja &amp; Toleransi
                            </h3>
                            <p style="font-size: 0.8rem; color: var(--text-muted); margin: 2px 0 0 0;">
                                Jam operasional scan masuk, batas toleransi, dan jadwal kepulangan peserta didik.
                            </p>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 18px;">
                        <div>
                            <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-color); margin-bottom: 5px;">
                                Jam Buka Presensi Masuk:
                            </label>
                            <input type="time" name="jam_masuk_mulai" value="{{ substr($pengaturan->jam_masuk_mulai, 0, 5) }}" required class="form-control" style="width: 100%;">
                            <small style="color: var(--text-muted); font-size: 0.72rem;">Waktu awal scanner memproses tap.</small>
                        </div>

                        <div>
                            <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-color); margin-bottom: 5px;">
                                Batas Masuk Tepat Waktu:
                            </label>
                            <input type="time" name="jam_masuk_selesai" value="{{ substr($pengaturan->jam_masuk_selesai, 0, 5) }}" required class="form-control" style="width: 100%;">
                            <small style="color: var(--text-muted); font-size: 0.72rem;">Lewat dari ini dihitung Terlambat.</small>
                        </div>

                        <div>
                            <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-color); margin-bottom: 5px;">
                                Batas Akhir Scan Pagi:
                            </label>
                            <input type="time" name="jam_masuk_toleransi" value="{{ substr($pengaturan->jam_masuk_toleransi, 0, 5) }}" required class="form-control" style="width: 100%;">
                            <small style="color: var(--text-muted); font-size: 0.72rem;">Batas akhir toleransi scan pagi.</small>
                        </div>

                        <div>
                            <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-color); margin-bottom: 5px;">
                                Toleransi Keterlambatan:
                            </label>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <input type="number" name="toleransi_terlambat_menit" value="{{ $pengaturan->toleransi_terlambat_menit }}" min="0" max="120" required class="form-control" style="flex: 1;">
                                <span style="font-size: 0.8rem; color: var(--text-muted);">Menit</span>
                            </div>
                            <small style="color: var(--text-muted); font-size: 0.72rem;">Dispensasi sebelum status 'T'.</small>
                        </div>

                        <div>
                            <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-color); margin-bottom: 5px;">
                                Jam Buka Presensi Pulang:
                            </label>
                            <input type="time" name="jam_pulang_mulai" value="{{ substr($pengaturan->jam_pulang_mulai, 0, 5) }}" required class="form-control" style="width: 100%;">
                            <small style="color: var(--text-muted); font-size: 0.72rem;">Waktu awal scanner kepulangan.</small>
                        </div>

                        <div>
                            <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-color); margin-bottom: 5px;">
                                Batas Akhir Presensi Pulang:
                            </label>
                            <input type="time" name="jam_pulang_selesai" value="{{ substr($pengaturan->jam_pulang_selesai, 0, 5) }}" required class="form-control" style="width: 100%;">
                            <small style="color: var(--text-muted); font-size: 0.72rem;">Batas akhir tap kepulangan.</small>
                        </div>
                    </div>

                    <div style="border-top: 1px solid var(--border-color); padding-top: 14px;">
                        <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-color); margin-bottom: 8px;">
                            <i class="fas fa-calendar-week text-primary me-1"></i> Hari Aktif Belajar Sekolah:
                        </label>
                        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                            @php
                                $days = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
                                $activeDays = is_array($pengaturan->hari_aktif) ? $pengaturan->hari_aktif : [];
                            @endphp
                            @foreach ($days as $day)
                                @php $isDayActive = in_array($day, $activeDays); @endphp
                                <label style="display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; border: 1px solid {{ $isDayActive ? 'var(--primary)' : 'var(--border-color)' }}; border-radius: 8px; background: {{ $isDayActive ? 'rgba(99, 102, 241, 0.1)' : 'transparent' }}; font-size: 0.82rem; color: var(--text-color); cursor: pointer; transition: all 0.2s ease;">
                                    <input type="checkbox" name="hari_aktif[]" value="{{ $day }}" {{ $isDayActive ? 'checked' : '' }} style="accent-color: var(--primary);">
                                    {{ $day }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- CARD 2: Geolokasi & Radius Kehadiran (GPS) -->
                <div class="card" style="padding: 24px; border-radius: 14px;">
                    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px; border-bottom: 1px solid var(--border-color); padding-bottom: 14px;">
                        <div style="width: 40px; height: 40px; border-radius: 10px; background: rgba(16, 185, 129, 0.15); color: #10b981; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; flex-shrink: 0;">
                            <i class="fas fa-location-dot"></i>
                        </div>
                        <div>
                            <h3 style="font-size: 1.1rem; font-weight: 800; color: var(--text-color); margin: 0;">
                                Geolokasi &amp; Radius Titik Sekolah
                            </h3>
                            <p style="font-size: 0.8rem; color: var(--text-muted); margin: 2px 0 0 0;">
                                Validasi jarak GPS kehadiran dari koordinat pusat satuan pendidikan.
                            </p>
                        </div>
                    </div>

                    <!-- Profil Referensi Sekolah Dapodik -->
                    <div style="background: rgba(99, 102, 241, 0.05); border: 1px solid rgba(99, 102, 241, 0.2); border-radius: 10px; padding: 12px 14px; margin-bottom: 16px; display: flex; align-items: flex-start; gap: 12px;">
                        <div style="background: var(--primary); color: #fff; width: 34px; height: 34px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 0.95rem; flex-shrink: 0;">
                            <i class="fas fa-school"></i>
                        </div>
                        <div style="flex: 1; min-width: 0;">
                            <div style="font-weight: 700; font-size: 0.86rem; color: var(--text-color); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                {{ $sekolah->nama ?? 'Satuan Pendidikan' }} <span style="font-size: 0.72rem; color: var(--text-muted); font-weight: normal;">(NPSN: {{ $sekolah->npsn ?? '-' }})</span>
                            </div>
                            <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                {{ $sekolah->alamat_jalan ?? 'Alamat Sekolah' }}, {{ $sekolah->desa_kelurahan ?? '' }}
                            </div>
                            <div style="display: flex; gap: 12px; margin-top: 4px; font-size: 0.72rem; flex-wrap: wrap;">
                                <span style="color: var(--primary); font-weight: 600;">
                                    <i class="fas fa-crosshairs me-1"></i> Titik Dapodik: 
                                    <span id="labelDapodikCoord" data-lat="{{ $sekolah->lintang ?? '' }}" data-lon="{{ $sekolah->bujur ?? '' }}">{{ $sekolah->lintang ?? '-' }}, {{ $sekolah->bujur ?? '-' }}</span>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Switch Wajibkan Geolokasi -->
                    <div style="background: rgba(255, 255, 255, 0.02); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 14px; margin-bottom: 16px;">
                        <label style="display: flex; align-items: center; justify-content: space-between; cursor: pointer; gap: 12px;">
                            <div>
                                <strong style="font-size: 0.88rem; color: var(--text-color); display: block;">
                                    Wajibkan Geolokasi Presensi (GPS)
                                </strong>
                                <span style="font-size: 0.74rem; color: var(--text-muted); display: block; margin-top: 2px;">
                                    Hanya terima presensi jika berada dalam batas radius meter dari titik sekolah.
                                </span>
                            </div>
                            <input type="checkbox" id="settingRequireLocation" name="require_location" {{ $pengaturan->require_location ? 'checked' : '' }} style="width: 20px; height: 20px; accent-color: var(--primary); cursor: pointer; flex-shrink: 0;">
                        </label>
                    </div>

                    <!-- Pengaturan Radius Meter -->
                    <div style="margin-bottom: 16px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px;">
                            <label style="font-size: 0.82rem; font-weight: 700; color: var(--text-color);">
                                Radius Kehadiran (Meter):
                            </label>
                            <span id="labelRadiusDisplay" style="font-size: 0.76rem; font-weight: 700; color: #10b981;">
                                {{ $effectiveRadius }} Meter
                            </span>
                        </div>
                        <div style="display: flex; gap: 10px; align-items: center;">
                            <input type="number" name="radius_meter" id="inputRadiusMeter" value="{{ $effectiveRadius }}" min="10" max="50000" class="form-control" style="flex: 1;" placeholder="100">
                            <span style="font-size: 0.8rem; color: var(--text-muted); flex-shrink: 0;">m</span>
                        </div>
                        <!-- Quick Radius Chips -->
                        <div style="display: flex; gap: 6px; margin-top: 6px; flex-wrap: wrap;">
                            @foreach([50, 100, 200, 500, 1000] as $rPreset)
                                <button type="button" class="btn-radius-chip" data-radius="{{ $rPreset }}" style="padding: 2px 8px; font-size: 0.7rem; border-radius: 6px; border: 1px solid var(--border-color); background: rgba(255,255,255,0.03); color: var(--text-muted); cursor: pointer; transition: all 0.15s ease;">
                                    {{ $rPreset >= 1000 ? ($rPreset/1000).' km' : $rPreset.' m' }}
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <!-- Koordinat Titik Pusat Sekolah (Lintang & Bujur) -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                        <div>
                            <label style="display: block; font-size: 0.8rem; font-weight: 700; color: var(--text-color); margin-bottom: 4px;">
                                Lintang (Latitude):
                            </label>
                            <input type="text" name="latitude" id="inputLatitude" value="{{ $effectiveLat !== null ? $effectiveLat : '' }}" placeholder="-7.17840000" class="form-control" style="width: 100%; font-family: monospace; font-size: 0.82rem;">
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.8rem; font-weight: 700; color: var(--text-color); margin-bottom: 4px;">
                                Bujur (Longitude):
                            </label>
                            <input type="text" name="longitude" id="inputLongitude" value="{{ $effectiveLon !== null ? $effectiveLon : '' }}" placeholder="107.13570000" class="form-control" style="width: 100%; font-family: monospace; font-size: 0.82rem;">
                        </div>
                    </div>

                    <!-- Tombol Cepat Koordinat -->
                    <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-top: 8px;">
                        <button type="button" id="btnResetToDapodikLocation" class="btn btn-outline" style="padding: 5px 10px; font-size: 0.74rem;">
                            <i class="fas fa-rotate-left me-1"></i> Gunakan Titik Dapodik
                        </button>
                        <button type="button" id="btnDetectMyLocation" class="btn btn-outline" style="padding: 5px 10px; font-size: 0.74rem;">
                            <i class="fas fa-location-arrow me-1"></i> Deteksi GPS Saya
                        </button>
                        <a href="https://www.google.com/maps?q={{ $effectiveLat }},{{ $effectiveLon }}" id="btnOpenGoogleMaps" target="_blank" rel="noopener noreferrer" class="btn btn-outline" style="padding: 5px 10px; font-size: 0.74rem;">
                            <i class="fas fa-arrow-up-right-from-square me-1"></i> Buka Peta
                        </a>
                    </div>
                </div>

            </div>

            <!-- Row 2: Metode Identifikasi & Perangkat Terminal (Full Width Card) -->
            <div class="card" style="padding: 20px 24px; border-radius: 14px; margin-bottom: 20px;">
                <h4 style="font-size: 0.95rem; font-weight: 800; color: var(--text-color); margin: 0 0 14px 0; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-id-badge text-primary"></i> Metode Identifikasi &amp; Perangkat Terminal Kiosk
                </h4>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 14px;">
                    <label style="display: flex; align-items: flex-start; gap: 12px; padding: 12px 14px; border: 1px solid var(--border-color); border-radius: 10px; background: rgba(255,255,255,0.015); cursor: pointer;">
                        <input type="checkbox" id="settingRequireCamera" name="require_camera" {{ $pengaturan->require_camera ? 'checked' : '' }} style="width: 18px; height: 18px; margin-top: 2px; accent-color: var(--primary);">
                        <div>
                            <strong style="font-size: 0.85rem; color: var(--text-color); display: block;">Snapshot Kamera Live</strong>
                            <div style="font-size: 0.73rem; color: var(--text-muted); margin-top: 2px;">Kamera terminal otomatis memotret wajah peserta didik saat tap / scan kartu.</div>
                        </div>
                    </label>

                    <label style="display: flex; align-items: flex-start; gap: 12px; padding: 12px 14px; border: 1px solid var(--border-color); border-radius: 10px; background: rgba(255,255,255,0.015); cursor: pointer;">
                        <input type="checkbox" id="settingAllowRfid" name="allow_rfid" {{ $pengaturan->allow_rfid ? 'checked' : '' }} style="width: 18px; height: 18px; margin-top: 2px; accent-color: var(--primary);">
                        <div>
                            <strong style="font-size: 0.85rem; color: var(--text-color); display: block;">Izinkan Pemindaian RFID Reader</strong>
                            <div style="font-size: 0.73rem; color: var(--text-muted); margin-top: 2px;">Menerima tap kartu fisik contactless RFID (kartu pelajar/e-KTP).</div>
                        </div>
                    </label>

                    <label style="display: flex; align-items: flex-start; gap: 12px; padding: 12px 14px; border: 1px solid var(--border-color); border-radius: 10px; background: rgba(255,255,255,0.015); cursor: pointer;">
                        <input type="checkbox" id="settingAllowQr" name="allow_qr" {{ $pengaturan->allow_qr ? 'checked' : '' }} style="width: 18px; height: 18px; margin-top: 2px; accent-color: var(--primary);">
                        <div>
                            <strong style="font-size: 0.85rem; color: var(--text-color); display: block;">Izinkan Pemindaian QR / Barcode</strong>
                            <div style="font-size: 0.73rem; color: var(--text-muted); margin-top: 2px;">Menerima scan barcode atau QR Code digital dari aplikasi mobile peserta didik.</div>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Row 3: Kompetensi Keahlian (Jurusan) yang Diizinkan Presensi (Full Width Card) -->
            <div class="card" style="padding: 24px; border-radius: 14px; margin-bottom: 20px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; flex-wrap: wrap; gap: 10px;">
                    <div>
                        <h4 style="font-size: 1rem; font-weight: 800; color: var(--text-color); margin: 0 0 2px 0; display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-graduation-cap text-primary"></i> Kompetensi Keahlian (Jurusan) yang Diizinkan Presensi
                        </h4>
                        <small style="color: var(--text-muted); font-size: 0.75rem;">
                            Pilih jurusan yang aktif melakukan absensi gerbang/harian (misal: non-aktifkan jurusan yang sedang PKL / Praktik Kerja Lapangan).
                        </small>
                    </div>
                    <div style="display: flex; gap: 8px;">
                        <button type="button" id="btnSelectAllJurusan" class="btn btn-outline" style="padding: 5px 12px; font-size: 0.75rem;">
                            <i class="fas fa-check-double me-1"></i> Pilih Semua
                        </button>
                        <button type="button" id="btnDeselectAllJurusan" class="btn btn-outline" style="padding: 5px 12px; font-size: 0.75rem;">
                            <i class="fas fa-times me-1"></i> Hapus Semua
                        </button>
                    </div>
                </div>

                @php
                    $activeJurusans = is_array($pengaturan->jurusan_aktif) ? $pengaturan->jurusan_aktif : [];
                    $isAllJurusan = empty($activeJurusans);
                @endphp

                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 12px;">
                    @foreach ($jurusanList ?? [] as $j)
                        @php
                            $isChecked = $isAllJurusan || in_array((string)$j->kode, array_map('strval', $activeJurusans), true);
                        @endphp
                        <label style="display: flex; align-items: center; gap: 12px; padding: 12px 14px; border: 1px solid var(--border-color); border-radius: 10px; background: rgba(255,255,255,0.015); cursor: pointer; transition: all 0.2s ease;">
                            <input type="checkbox" name="jurusan_aktif[]" value="{{ $j->kode }}" class="chk-jurusan" {{ $isChecked ? 'checked' : '' }} style="width: 17px; height: 17px; flex-shrink: 0; accent-color: var(--primary);">
                            <img src="{{ $j->logo_url ?: asset('img/logo-dark.png') }}" alt="{{ $j->nama }}" style="width: 34px; height: 34px; border-radius: 6px; object-fit: contain; flex-shrink: 0; background: rgba(255,255,255,0.05); padding: 2px;" onerror="this.src='/img/logo-dark.png';">
                            <div style="min-width: 0; flex: 1;">
                                <div style="font-weight: 700; font-size: 0.82rem; color: var(--text-color); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $j->nama }}">
                                    {{ $j->nama }}
                                </div>
                                <div style="font-size: 0.7rem; color: var(--text-muted); display: flex; gap: 8px; margin-top: 2px;">
                                    <span style="font-family: monospace;">{{ $j->kode }}</span>
                                    <span>&bull;</span>
                                    <span>{{ $j->total_rombel }} Rombel</span>
                                    <span>&bull;</span>
                                    <span>{{ $j->total_siswa }} Peserta Didik</span>
                                </div>
                            </div>
                        </label>
                    @endforeach
                </div>
            </div>

            <!-- Row 4: Action Bar -->
            <div class="card" style="padding: 16px 24px; border-radius: 14px; display: flex; justify-content: space-between; align-items: center; background: rgba(255,255,255,0.02); border: 1px solid var(--border-color); flex-wrap: wrap; gap: 12px;">
                <div style="font-size: 0.82rem; color: var(--text-muted);">
                    <i class="fas fa-shield-halved text-primary me-1"></i> Perubahan konfigurasi akan langsung diterapkan ke seluruh terminal &amp; sistem presensi.
                </div>
                <div>
                    <button type="submit" class="btn btn-primary" style="padding: 10px 24px; font-weight: 700; display: inline-flex; align-items: center; gap: 8px;">
                        <i class="fas fa-save"></i> Simpan Konfigurasi Presensi
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Modal Pasangkan / Binding RFID Siswa -->
    <div id="modalAssignRfid" class="modal-backdrop" style="display: none; z-index: 99999 !important;">
        <div class="card" style="max-width: 480px; width: 92%; margin: auto; padding: 24px; border-radius: 16px; box-shadow: 0 16px 40px rgba(0,0,0,0.5);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">
                <h3 style="font-size: 1.1rem; font-weight: 800; color: var(--text-color); margin: 0;">
                    <i class="fas fa-id-card text-primary me-2"></i> Pendaftaran Kartu RFID
                </h3>
                <button type="button" id="btnCloseRfidModal" style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 1.2rem;">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form id="formAssignRfid">
                <input type="hidden" id="rfidPdId">
                <div style="margin-bottom: 14px; font-size: 0.85rem;">
                    <div style="color: var(--text-muted); margin-bottom: 2px;">Peserta Didik:</div>
                    <div id="rfidPdNama" style="font-size: 1.05rem; font-weight: 800; color: var(--text-color);"></div>
                    <div id="rfidPdNisn" style="font-family: monospace; color: var(--text-muted); font-size: 0.8rem;"></div>
                </div>

                <div style="margin-bottom: 18px;">
                    <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-color); margin-bottom: 6px;">
                        UID Kartu RFID:
                    </label>
                    <input type="text" id="rfidUidInput" required placeholder="Tap kartu pada reader atau ketik UID..."
                        class="form-control" style="width: 100%; padding: 10px 14px; font-family: monospace; font-size: 1rem; letter-spacing: 1px;">
                    <small style="color: var(--text-muted); font-size: 0.72rem; display: block; margin-top: 4px;">
                        Tempelkan kartu RFID pada card reader USB (reader akan otomatis mengetikkan nomor UID kartu).
                    </small>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 10px;">
                    <button type="button" id="btnCancelRfidModal" class="btn btn-outline" style="padding: 8px 18px; font-size: 0.85rem;">
                        Batal
                    </button>
                    <button type="submit" class="btn btn-primary" style="padding: 8px 22px; font-size: 0.85rem; font-weight: 700;">
                        Simpan Kartu
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Preview Snapshot Foto -->
    <div id="modalFotoSnapshot" class="modal-backdrop" style="display: none; z-index: 99999 !important;">
        <div class="card" style="max-width: 440px; width: 92%; margin: auto; padding: 20px; border-radius: 16px; box-shadow: 0 16px 40px rgba(0,0,0,0.5); text-align: center;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; border-bottom: 1px solid var(--border-color); padding-bottom: 8px;">
                <h4 id="fotoSnapshotTitle" style="font-size: 0.95rem; font-weight: 800; color: var(--text-color); margin: 0;">
                    Foto Bukti Presensi
                </h4>
                <button type="button" id="btnCloseFotoModal" style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 1.2rem;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div style="margin: 10px 0;">
                <img id="imgFotoSnapshot" src="" alt="Snapshot" style="width: 100%; border-radius: 12px; object-fit: cover; max-height: 380px; border: 1px solid var(--border-color);">
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/presensi.js') }}?v={{ file_exists(public_path('js/presensi.js')) ? filemtime(public_path('js/presensi.js')) : time() }}"></script>
@endpush
