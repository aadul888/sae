@extends('layouts.dashboard')

@section('title', 'Log Presensi — Laboratorium SAE')
@section('dash_title', 'Log Presensi')

@push('styles')
    <link rel="stylesheet"
        href="{{ asset('css/presensi.css') }}?v={{ file_exists(public_path('css/presensi.css')) ? filemtime(public_path('css/presensi.css')) : time() }}">
    <style>
        .log-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: var(--space-4);
        }

        .log-toolbar__filters {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: var(--space-4);
            min-width: 0;
        }

        .log-toolbar__search {
            flex: 0 1 280px;
            min-width: 200px;
            height: 38px;
        }

        .log-toolbar__search input {
            width: 100%;
            height: 38px;
        }

        /* ---- Segmented Control Tabs untuk Modal Cetak Compact ---- */
        .modal-segmented {
            display: grid;
            background: var(--bg-surface, rgba(0, 0, 0, 0.04));
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md, 8px);
            padding: 3px;
            gap: 3px;
            user-select: none;
        }

        .modal-segmented--2 {
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        }

        .modal-segmented--4 {
            grid-template-columns: repeat(4, minmax(0, 1fr)) !important;
        }

        .modal-segmented-item {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            margin: 0;
            padding: 7px 10px;
            font-size: var(--text-xs, 0.75rem);
            font-weight: var(--font-semibold, 600);
            color: var(--text-muted);
            border-radius: var(--radius-sm, 6px);
            cursor: pointer;
            transition: all 0.15s ease-in-out;
            border: 1px solid transparent;
            text-align: center;
            white-space: nowrap;
        }

        .modal-segmented-item input[type="radio"] {
            position: absolute;
            opacity: 0;
            pointer-events: none;
            width: 0;
            height: 0;
            margin: 0;
        }

        .modal-segmented-item:hover {
            color: var(--text-color);
            background: rgba(255, 255, 255, 0.5);
        }

        .modal-segmented-item:has(input:checked) {
            background: var(--bg-card, #ffffff);
            color: var(--primary, #6366f1);
            border-color: rgba(99, 102, 241, 0.25);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
        }

        [data-theme="dark"] .modal-segmented-item:hover {
            background: rgba(255, 255, 255, 0.05);
        }

        [data-theme="dark"] .modal-segmented-item:has(input:checked) {
            background: var(--bg-card, #1e293b);
            color: var(--primary, #818cf8);
            border-color: rgba(129, 140, 248, 0.3);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
        }

        /* Modal Select & Grid Fixes (selalu berdampingan & teks tidak tabrakan dengan icon) */
        .modal-row-grid-2 {
            display: grid !important;
            grid-template-columns: 1fr 1fr !important;
            gap: 10px !important;
        }

        .modal-row-grid-2-1 {
            display: grid !important;
            grid-template-columns: 2fr 1fr !important;
            gap: 10px !important;
        }

        #modalCetakPresensi select.form-control,
        #modalCetakPresensi select.toolbar-filter-select,
        #modalCetakPresensi .modal-select {
            height: 38px !important;
            padding: 0 32px 0 10px !important;
            appearance: none !important;
            -webkit-appearance: none !important;
            -moz-appearance: none !important;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2364748b' stroke-width='2.2'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E") !important;
            background-repeat: no-repeat !important;
            background-position: right 10px center !important;
            background-size: 13px 13px !important;
            text-overflow: ellipsis !important;
            white-space: nowrap !important;
            overflow: hidden !important;
            font-size: var(--text-xs, 0.75rem) !important;
            box-sizing: border-box !important;
            width: 100% !important;
        }

        [data-theme="dark"] #modalCetakPresensi select.form-control,
        [data-theme="dark"] #modalCetakPresensi select.toolbar-filter-select,
        [data-theme="dark"] #modalCetakPresensi .modal-select {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2394a3b8' stroke-width='2.2'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E") !important;
        }

        #modalCetakPresensi input.form-control {
            height: 38px !important;
            font-size: var(--text-xs, 0.75rem) !important;
            padding: 0 10px !important;
            box-sizing: border-box !important;
            width: 100% !important;
        }

        /* Empty State Table Centering (Desktop & Mobile) */
        .table-responsive-stack:has(tr.empty-row) table,
        .table-presensi-log:has(tr.empty-row) {
            width: 100% !important;
            min-width: 100% !important;
        }

        .table-presensi-log tbody tr.empty-row,
        .table-presensi-log tbody tr.empty-row td,
        .table-presensi-log tbody td.cell-empty,
        .table-presensi-log tbody td[colspan] {
            text-align: center !important;
            white-space: normal !important;
        }

        .table-presensi-log tbody td.cell-empty .empty-state-wrap,
        .table-presensi-log tbody td[colspan] .empty-state-wrap {
            display: flex !important;
            flex-direction: column !important;
            align-items: center !important;
            justify-content: center !important;
            text-align: center !important;
            margin: 0 auto !important;
            width: 100% !important;
        }

        .table-presensi-log tbody td.cell-empty .empty-state-wrap i,
        .table-presensi-log tbody td[colspan] .empty-state-wrap i {
            display: block !important;
            margin: 0 auto 8px auto !important;
            text-align: center !important;
        }

        .table-presensi-log tbody td.cell-empty .empty-state-wrap div,
        .table-presensi-log tbody td[colspan] .empty-state-wrap div {
            text-align: center !important;
            white-space: normal !important;
            word-break: normal !important;
            margin: 0 auto !important;
        }

        @media (max-width: 768px) {
            .log-toolbar {
                flex-direction: column;
                align-items: stretch;
                gap: var(--space-4);
            }

            .log-toolbar__filters {
                width: 100%;
                display: flex;
                flex-wrap: wrap;
                gap: var(--space-3);
                align-items: stretch;
            }

            .log-toolbar__filters .toolbar-entries {
                width: auto;
                margin-right: 0;
            }

            .log-toolbar__filters #filterTanggal {
                flex: 1 1 calc(50% - 6px);
                min-width: 130px;
                height: 38px !important;
                box-sizing: border-box !important;
            }

            .log-toolbar__filters select.toolbar-filter-select {
                flex: 1 1 calc(50% - 6px);
                min-width: 130px;
                height: 38px !important;
                box-sizing: border-box !important;
            }

            .log-toolbar__filters .btn-reset-filter {
                width: 38px !important;
                height: 38px !important;
                flex: 0 0 38px !important;
            }

            .log-toolbar__search {
                width: 100%;
                flex: 1 1 100%;
                min-width: 0;
            }
        }
    </style>
@endpush

@section('content')
    @include('dashboard.laboran.partials.header-actions')

    <div class="dash-content-inner">
        {{-- 1. Banner Header --}}
        <div class="dash-banner"
            style="display: flex; align-items: center; justify-content: space-between; gap: var(--space-8); flex-wrap: wrap; margin-bottom: var(--space-9);">
            <div style="display: flex; align-items: center; gap: var(--space-7);">
                <div
                    style="width: 48px; height: 48px; border-radius: var(--radius-lg); background: rgba(99,102,241,0.12); color: var(--primary, #6366f1); display: flex; align-items: center; justify-content: center; font-size: var(--text-2xl); flex-shrink: 0;">
                    <i class="fas fa-list-check"></i>
                </div>
                <div>
                    <h2
                        style="font-size: var(--text-xl); font-weight: var(--font-extrabold); color: var(--text-color); margin: 0;">
                        Log Presensi Harian
                    </h2>
                    <div style="font-size: var(--text-sm); color: var(--text-muted); margin-top: var(--space-1);">
                        Catatan kehadiran peserta didik jurusan
                        <strong>{{ $context['jurusan_nama'] ?: ($context['jurusan_id'] ?: 'Anda') }}</strong>
                        &mdash; hasil tap RFID / QR / input manual. Data baca-saja.
                    </div>
                </div>
            </div>
            <div class="dash-banner-actions dash-banner-access">
                <button type="button" class="btn btn-outline" id="btnOpenModalCetak"
                    title="Cetak Laporan Presensi Jurusan (A4)" aria-label="Cetak Laporan Presensi">
                    <i class="fas fa-print"></i>
                </button>
            </div>
        </div>

        {{-- 2. Ringkasan tanggal terpilih --}}
        <div class="dash-stat-grid"
            style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); margin-bottom: var(--space-9); gap: var(--space-7);">
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(99,102,241,0.15); color: var(--primary, #6366f1);">
                    <i class="fas fa-users"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ number_format($stats['total'], 0, ',', '.') }}</div>
                    <div class="dash-stat-label">Total Siswa (Hadir: {{ $stats['persen'] }}%)</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(16,185,129,0.15); color: var(--success, #10b981);">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" style="color: var(--success, #10b981);">
                        {{ number_format($stats['hadir'], 0, ',', '.') }}</div>
                    <div class="dash-stat-label">Hadir Tepat Waktu</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(245,158,11,0.15); color: var(--warning, #f59e0b);">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" style="color: var(--warning, #f59e0b);">
                        {{ number_format($stats['terlambat'], 0, ',', '.') }}</div>
                    <div class="dash-stat-label">Terlambat Masuk</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(239,68,68,0.15); color: var(--danger, #ef4444);">
                    <i class="fas fa-file-circle-exclamation"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" style="color: var(--danger, #ef4444);">
                        {{ number_format($stats['izin'] + $stats['sakit'] + $stats['alpha'], 0, ',', '.') }}</div>
                    <div class="dash-stat-label">
                        Tidak Hadir (I: {{ $stats['izin'] }} &bull; S: {{ $stats['sakit'] }} &bull; A: {{ $stats['alpha'] }})
                    </div>
                </div>
            </div>
        </div>

        {{-- 3. Datatable Terpadu --}}
        <div class="card datatable-card-wrapper" id="tableDataContainer">
            <div class="datatable-toolbar">
                <div class="log-toolbar">
                    <div class="log-toolbar__filters">
                        <div class="toolbar-entries">
                            <label for="perPageSelect" style="margin: 0;">Tampilkan</label>
                            <select id="perPageSelect" name="perPage" class="per-page-select" style="height: 38px;">
                                @foreach ([10, 15, 25, 50, 100] as $n)
                                    <option value="{{ $n }}" {{ $perPage == $n ? 'selected' : '' }}>{{ $n }}
                                    </option>
                                @endforeach
                            </select>
                            <span>entri</span>
                        </div>

                        <input type="date" id="filterTanggal" name="tanggal" value="{{ $tanggal }}"
                            class="form-control" title="Pilih tanggal presensi"
                            style="height: 38px; padding: 0 10px; font-size: var(--text-sm); border-radius: var(--radius-md, 8px); width: auto;">

                        <select name="rombel_id" id="filterRombel" class="toolbar-filter-select"
                            style="min-width: 150px; height: 38px;" title="Filter kelas / rombel">
                            <option value="">Semua Kelas</option>
                            @foreach ($rombelList as $r)
                                <option value="{{ $r->rombongan_belajar_id }}"
                                    {{ $rombelId === (string) $r->rombongan_belajar_id ? 'selected' : '' }}>
                                    {{ $r->nama }}
                                </option>
                            @endforeach
                        </select>

                        <select name="status" id="filterStatus" class="toolbar-filter-select"
                            style="min-width: 140px; height: 38px;" title="Filter status kehadiran">
                            <option value="">Semua Status</option>
                            <option value="H" {{ $status === 'H' ? 'selected' : '' }}>Hadir Tepat Waktu</option>
                            <option value="T" {{ $status === 'T' ? 'selected' : '' }}>Terlambat</option>
                            <option value="I" {{ $status === 'I' ? 'selected' : '' }}>Izin</option>
                            <option value="S" {{ $status === 'S' ? 'selected' : '' }}>Sakit</option>
                            <option value="D" {{ $status === 'D' ? 'selected' : '' }}>Dispen</option>
                            <option value="A" {{ $status === 'A' ? 'selected' : '' }}>Alpha</option>
                        </select>

                        @if ($q || $rombelId || $status || $tanggal !== now()->toDateString())
                            <a href="{{ route('dashboard.laboran.log-presensi') }}"
                                class="btn btn-outline btn-reset-filter"
                                style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px);"
                                title="Reset filter" aria-label="Reset filter" data-live-reset>
                                <i class="fas fa-undo"></i>
                            </a>
                        @endif
                    </div>

                    <div class="live-search-wrap log-toolbar__search">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" id="liveSearch" name="q" value="{{ $q }}"
                            placeholder="Cari nama / NISN..." autocomplete="off">
                        <button type="button" id="clearSearch" class="clear-search {{ $q ? 'visible' : '' }}"
                            title="Hapus pencarian">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div class="table-responsive-stack" style="overflow-x: auto; width: 100%;">
                <table class="table table-pd table-presensi-log"
                    style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                    <thead>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <th
                                style="padding: var(--space-5) var(--space-6); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; width: 50px; text-align: center;">
                                No</th>
                            @foreach ([
            ['nama_siswa', 'Nama Peserta Didik', ''],
            ['nama_rombel', 'Rombel', ''],
            ['jam_masuk', 'Jam Masuk', ''],
            ['jam_pulang', 'Jam Pulang', ''],
            ['status', 'Status', 'center'],
        ] as [$key, $label, $align])
                                <th class="sortable-th {{ $sort === $key ? 'sorted' : '' }}" data-sort="{{ $key }}"
                                    style="padding: var(--space-5) var(--space-6); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; cursor: pointer; {{ $align === 'center' ? 'text-align: center;' : '' }}">
                                    {{ $label }}
                                    <span
                                        class="sort-icon">{!! $sort === $key ? ($sortDir === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                                </th>
                            @endforeach
                            <th
                                style="padding: var(--space-5) var(--space-6); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase;">
                                Keterangan</th>
                            <th
                                style="padding: var(--space-5) var(--space-6); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; text-align: right;">
                                Snapshot</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($logs as $index => $log)
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <td class="cell-log-no" style="padding: var(--space-6); text-align: center; color: var(--text-muted);"
                                    data-label="No">
                                    {{ $logs->firstItem() + $index }}
                                </td>
                                <td class="cell-log-siswa" style="padding: var(--space-6) var(--space-7);"
                                    data-label="Nama Lengkap">
                                    <div class="log-siswa-wrapper"
                                        style="display: flex; align-items: center; gap: var(--space-5);">
                                        <img src="{{ $log->foto_profil_url ?: asset('img/logo-dark.png') }}"
                                            alt="{{ $log->nama_siswa }}" class="log-avatar"
                                            style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 1.5px solid var(--border-color); flex-shrink: 0; {{ $log->foto_profil_url ? 'cursor: pointer;' : '' }}"
                                            onerror="this.src='/img/logo-dark.png';">
                                        <div>
                                            <div
                                                style="font-weight: var(--font-bold); color: var(--text-color); font-size: var(--text-base);">
                                                {{ $log->nama_siswa }}</div>
                                            <div
                                                style="font-size: var(--text-xs); color: var(--text-muted); font-family: var(--font-family-mono); margin-top: 2px;">
                                                NISN: {{ $log->nisn_siswa }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="cell-log-rombel" style="padding: var(--space-6) var(--space-7);"
                                    data-label="Rombel Kelas">
                                    <span class="badge badge-outline"
                                        style="font-size: var(--text-sm); padding: 3px 8px;">{{ $log->nama_rombel ?: '-' }}</span>
                                </td>
                                <td class="cell-log-masuk" style="padding: var(--space-6) var(--space-7);"
                                    data-label="Presensi Masuk">
                                    @if ($log->jam_masuk)
                                        <div
                                            style="font-weight: var(--font-bold); font-family: var(--font-family-mono); color: var(--text-color); font-size: var(--text-base);">
                                            {{ substr($log->jam_masuk, 0, 5) }} WIB
                                        </div>
                                        <div style="font-size: var(--text-xs); color: var(--text-muted);">
                                            <i class="fas fa-rss me-1"></i>{{ strtoupper($log->metode_masuk ?: 'manual') }}
                                        </div>
                                    @else
                                        <span style="color: var(--text-muted);">-</span>
                                    @endif
                                </td>
                                <td class="cell-log-pulang" style="padding: var(--space-6) var(--space-7);"
                                    data-label="Presensi Pulang">
                                    @if ($log->jam_pulang)
                                        <div
                                            style="font-weight: var(--font-bold); font-family: var(--font-family-mono); color: var(--text-color); font-size: var(--text-base);">
                                            {{ substr($log->jam_pulang, 0, 5) }} WIB
                                        </div>
                                        <div style="font-size: var(--text-xs); margin-top: 2px;">
                                            @if ($log->status_ketepatan_pulang === 'pulang_cepat')
                                                <span class="badge badge-warning"
                                                    style="font-size: var(--text-2xs); padding: 1px 6px;">
                                                    <i class="fas fa-person-walking-arrow-right me-1"></i> Pulang Cepat
                                                </span>
                                            @else
                                                <span style="color: var(--text-muted);"><i
                                                        class="fas fa-rss me-1"></i>{{ strtoupper($log->metode_pulang ?: 'manual') }}</span>
                                            @endif
                                        </div>
                                    @elseif ($log->status_ketepatan_pulang === 'pulang_cepat')
                                        <span class="badge badge-warning" style="font-size: var(--text-xs); padding: 2px 7px;">
                                            <i class="fas fa-person-walking-arrow-right me-1"></i> Pulang Cepat
                                        </span>
                                    @else
                                        <span style="color: var(--text-muted);">-</span>
                                    @endif
                                </td>
                                <td class="cell-log-status"
                                    style="padding: var(--space-6) var(--space-7); text-align: center;"
                                    data-label="Status Kehadiran">
                                    @if ($log->status === 'H')
                                        <span class="badge badge-success" style="font-size: var(--text-xs); padding: 3px 8px;"><i
                                                class="fas fa-check-circle me-1"></i> Hadir</span>
                                    @elseif ($log->status === 'T')
                                        <span class="badge badge-warning" style="font-size: var(--text-xs); padding: 3px 8px;"
                                            title="Terlambat {{ $log->menit_terlambat }} menit">
                                            <i class="fas fa-clock me-1"></i> +{{ $log->menit_terlambat }}m
                                        </span>
                                    @elseif ($log->status === 'I')
                                        <span class="badge badge-primary" style="font-size: var(--text-xs); padding: 3px 8px;"><i
                                                class="fas fa-file-signature me-1"></i> Izin</span>
                                    @elseif ($log->status === 'S')
                                        <span class="badge badge-info" style="font-size: var(--text-xs); padding: 3px 8px;"><i
                                                class="fas fa-notes-medical me-1"></i> Sakit</span>
                                    @elseif ($log->status === 'D')
                                        <span class="badge badge-accent" style="font-size: var(--text-xs); padding: 3px 8px;"><i
                                                class="fas fa-award me-1"></i> Dispen</span>
                                    @else
                                        <span class="badge badge-danger" style="font-size: var(--text-xs); padding: 3px 8px;"><i
                                                class="fas fa-times-circle me-1"></i> Alpha</span>
                                    @endif
                                </td>
                                <td class="cell-log-keterangan" style="padding: var(--space-6) var(--space-7);"
                                    data-label="Keterangan">
                                    <div style="max-width: 220px; font-size: var(--text-sm); color: var(--text-color); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"
                                        title="{{ $log->keterangan }}">
                                        {{ $log->keterangan ?: '-' }}
                                    </div>
                                </td>
                                <td class="cell-log-snapshot"
                                    style="padding: var(--space-6) var(--space-7); text-align: right;"
                                    data-label="Snapshot Foto">
                                    <div class="snapshot-actions"
                                        style="display: flex; justify-content: flex-end; gap: var(--space-3);">
                                        @if ($log->foto_masuk_url)
                                            <button type="button" class="btn btn-outline btn-preview-snapshot"
                                                data-url="{{ $log->foto_masuk_url }}"
                                                data-title="Snapshot Masuk — {{ $log->nama_siswa }}"
                                                style="padding: 5px 9px; font-size: var(--text-xs);"
                                                title="Lihat snapshot masuk">
                                                <i class="fas fa-camera text-primary"></i> Masuk
                                            </button>
                                        @endif
                                        @if ($log->foto_pulang_url)
                                            <button type="button" class="btn btn-outline btn-preview-snapshot"
                                                data-url="{{ $log->foto_pulang_url }}"
                                                data-title="Snapshot Pulang — {{ $log->nama_siswa }}"
                                                style="padding: 5px 9px; font-size: var(--text-xs);"
                                                title="Lihat snapshot pulang">
                                                <i class="fas fa-camera text-success"></i> Pulang
                                            </button>
                                        @endif
                                        @if (!$log->foto_masuk_url && !$log->foto_pulang_url)
                                            <span style="color: var(--text-muted); font-size: var(--text-sm);">-</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr class="empty-row">
                                <td colspan="8" class="cell-empty"
                                    style="padding: var(--space-11) var(--space-4); text-align: center !important; white-space: normal !important; color: var(--text-muted); font-size: var(--text-base);">
                                    <div class="empty-state-wrap"
                                        style="display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; margin: 0 auto; width: 100%;">
                                        <i class="fas fa-clipboard-question mb-2"
                                            style="font-size: var(--text-3xl); opacity: 0.4; display: block; margin: 0 auto 8px auto;"></i>
                                        <div style="text-align: center !important; white-space: normal !important; max-width: 480px; margin: 0 auto;">Belum ada data presensi yang tercatat untuk filter tanggal dan kelas ini.</div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($logs->hasPages())
                <div class="datatable-footer">
                    @include('partials.datatable-pagination', ['paginator' => $logs])
                </div>
            @endif
        </div>
    </div>

    {{-- ===================== MODAL OPSION CETAK PRESENSI ===================== --}}
    <div id="modalCetakPresensi" class="modal-backdrop"
        style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.65); z-index: 9999; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
        <div class="card"
            style="max-width: 480px; width: 92%; max-height: 90vh; display: flex; flex-direction: column; margin: 0; border-radius: var(--radius-xl, 16px); padding: 18px; background: var(--bg-card); border: 1px solid var(--border-color); box-shadow: 0 10px 30px rgba(0,0,0,0.4);">
            <div
                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; padding-bottom: 10px; border-bottom: 1px solid var(--border-color);">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div
                        style="width: 36px; height: 36px; border-radius: var(--radius-md, 8px); background: rgba(99,102,241,0.12); color: var(--primary, #6366f1); display: flex; align-items: center; justify-content: center; font-size: var(--text-base); flex-shrink: 0;">
                        <i class="fas fa-print"></i>
                    </div>
                    <div>
                        <h4 style="font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-color); margin: 0;">
                            Cetak Laporan Presensi
                        </h4>
                        <div style="font-size: var(--text-2xs, 0.7rem); color: var(--text-muted); margin-top: 1px;">
                            Jurusan: {{ $context['jurusan_nama'] ?: ($context['jurusan_id'] ?: 'Semua Jurusan') }}
                        </div>
                    </div>
                </div>
                <button type="button" id="btnCloseModalCetak"
                    style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: var(--text-base); padding: 4px;"
                    aria-label="Tutup">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form id="formCetakPresensi" action="{{ route('dashboard.laboran.log-presensi.cetak') }}" method="GET" target="_blank"
                style="display: flex; flex-direction: column; gap: var(--space-3); overflow-y: auto;">
                
                {{-- 1. Tipe Laporan: Per Kelas vs Per Murid --}}
                <div class="form-group" style="margin: 0;">
                    <label class="form-label" style="font-size: var(--text-2xs, 0.72rem); font-weight: var(--font-bold); margin-bottom: 4px; text-transform: uppercase; color: var(--text-muted);">
                        Tipe Laporan
                    </label>
                    <div class="modal-segmented modal-segmented--2">
                        <label class="modal-segmented-item" id="labelTipeKelas">
                            <input type="radio" name="tipe_laporan" value="kelas" checked id="radioTipeKelas">
                            <i class="fas fa-users"></i>
                            <span>Per Kelas</span>
                        </label>
                        <label class="modal-segmented-item" id="labelTipeMurid">
                            <input type="radio" name="tipe_laporan" value="murid" id="radioTipeMurid">
                            <i class="fas fa-user-graduate"></i>
                            <span>Per Murid</span>
                        </label>
                    </div>
                </div>

                {{-- 2. Pilih Kelas / Rombel --}}
                <div class="form-group" id="groupPilihRombel" style="margin: 0;">
                    <label for="modalCetakRombelId" class="form-label" style="font-size: var(--text-2xs, 0.72rem); font-weight: var(--font-bold); margin-bottom: 4px; text-transform: uppercase; color: var(--text-muted);">
                        Kelas / Rombel
                    </label>
                    <select name="rombel_id" id="modalCetakRombelId" class="form-control" style="height: 36px; font-size: var(--text-xs); padding: 0 10px;">
                        <option value="">Semua Kelas (Jurusan)</option>
                        @foreach ($rombelList as $r)
                            <option value="{{ $r->rombongan_belajar_id }}" {{ $rombelId === (string) $r->rombongan_belajar_id ? 'selected' : '' }}>
                                {{ $r->nama }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- 3. Pilih Peserta Didik (Hanya tampil saat tipe_laporan = murid) --}}
                <div class="form-group" id="groupPilihSiswa" style="margin: 0; display: none;">
                    <label for="modalCetakSiswaId" class="form-label" style="font-size: var(--text-2xs, 0.72rem); font-weight: var(--font-bold); margin-bottom: 4px; text-transform: uppercase; color: var(--text-muted);">
                        Peserta Didik <span style="color: var(--danger);">*</span>
                    </label>
                    <select name="peserta_didik_id" id="modalCetakSiswaId" class="form-control" style="height: 36px; font-size: var(--text-xs); padding: 0 10px;">
                        <option value="">-- Pilih Siswa --</option>
                    </select>
                    <div id="siswaLoadingHelp" style="font-size: 11px; color: var(--text-muted); margin-top: 3px; display: none;">
                        <i class="fas fa-spinner fa-spin me-1"></i> Memuat daftar siswa...
                    </div>
                </div>

                {{-- 4. Rentang Periode: Hari, Bulan, Semester, Tahun Ajaran --}}
                <div class="form-group" style="margin: 0;">
                    <label class="form-label" style="font-size: var(--text-2xs, 0.72rem); font-weight: var(--font-bold); margin-bottom: 4px; text-transform: uppercase; color: var(--text-muted);">
                        Rentang Periode
                    </label>
                    <div class="modal-segmented modal-segmented--4">
                        <label class="modal-segmented-item">
                            <input type="radio" name="periode" value="hari" checked>
                            <span>Hari</span>
                        </label>
                        <label class="modal-segmented-item">
                            <input type="radio" name="periode" value="bulan">
                            <span>Bulan</span>
                        </label>
                        <label class="modal-segmented-item">
                            <input type="radio" name="periode" value="semester">
                            <span>Semester</span>
                        </label>
                        <label class="modal-segmented-item">
                            <input type="radio" name="periode" value="tahun">
                            <span>Tahun</span>
                        </label>
                    </div>
                </div>

                {{-- Input dinamis berdasarkan periode --}}
                <div id="fieldPeriodeHari" class="form-group" style="margin: 0;">
                    <label for="modalCetakTanggal" class="form-label" style="font-size: var(--text-2xs, 0.72rem); font-weight: var(--font-bold); margin-bottom: 4px; text-transform: uppercase; color: var(--text-muted);">
                        Pilih Tanggal
                    </label>
                    <input type="date" name="tanggal" id="modalCetakTanggal" class="form-control" value="{{ $tanggal }}">
                </div>

                <div id="fieldPeriodeBulan" class="form-group" style="margin: 0; display: none;">
                    <div class="modal-row-grid-2-1">
                        <div>
                            <label class="form-label" style="font-size: var(--text-2xs, 0.72rem); font-weight: var(--font-bold); margin-bottom: 4px; text-transform: uppercase; color: var(--text-muted);">Bulan</label>
                            <select name="bulan" class="form-control">
                                @foreach ([1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'] as $num => $namaBulan)
                                    <option value="{{ $num }}" {{ (int) date('n') === $num ? 'selected' : '' }}>{{ $namaBulan }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="form-label" style="font-size: var(--text-2xs, 0.72rem); font-weight: var(--font-bold); margin-bottom: 4px; text-transform: uppercase; color: var(--text-muted);">Tahun</label>
                            <input type="number" name="tahun" class="form-control" value="{{ date('Y') }}">
                        </div>
                    </div>
                </div>

                <div id="fieldPeriodeSemester" class="form-group" style="margin: 0; display: none;">
                    <div class="modal-row-grid-2">
                        <div>
                            <label class="form-label" style="font-size: var(--text-2xs, 0.72rem); font-weight: var(--font-bold); margin-bottom: 4px; text-transform: uppercase; color: var(--text-muted);">Semester</label>
                            <select name="semester" class="form-control">
                                <option value="1" {{ (int) date('n') >= 7 ? 'selected' : '' }}>Semester Ganjil</option>
                                <option value="2" {{ (int) date('n') < 7 ? 'selected' : '' }}>Semester Genap</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label" style="font-size: var(--text-2xs, 0.72rem); font-weight: var(--font-bold); margin-bottom: 4px; text-transform: uppercase; color: var(--text-muted);">Tahun Ajaran</label>
                            @php
                                $curY = (int) date('Y');
                                $activeTa = date('n') >= 7 ? "{$curY}/" . ($curY + 1) : ($curY - 1) . "/{$curY}";
                            @endphp
                            <select name="tahun_ajaran" class="form-control">
                                @for ($i = $curY - 1; $i <= $curY + 1; $i++)
                                    @php $ta = $i . '/' . ($i + 1); @endphp
                                    <option value="{{ $ta }}" {{ $ta === $activeTa ? 'selected' : '' }}>{{ $ta }}</option>
                                @endfor
                            </select>
                        </div>
                    </div>
                </div>

                <div id="fieldPeriodeTahun" class="form-group" style="margin: 0; display: none;">
                    <label class="form-label" style="font-size: var(--text-2xs, 0.72rem); font-weight: var(--font-bold); margin-bottom: 4px; text-transform: uppercase; color: var(--text-muted);">Tahun Ajaran</label>
                    <select name="tahun_ajaran" class="form-control">
                        @for ($i = $curY - 1; $i <= $curY + 1; $i++)
                            @php $ta = $i . '/' . ($i + 1); @endphp
                            <option value="{{ $ta }}" {{ $ta === $activeTa ? 'selected' : '' }}>Tahun Ajaran {{ $ta }}</option>
                        @endfor
                    </select>
                </div>

                {{-- 5. Format Tampilan (Khusus Per Kelas) --}}
                <div class="form-group" id="groupModeTampilan" style="margin: 0;">
                    <label class="form-label" style="font-size: var(--text-2xs, 0.72rem); font-weight: var(--font-bold); margin-bottom: 4px; text-transform: uppercase; color: var(--text-muted);">Mode Tampilan</label>
                    <div class="modal-segmented modal-segmented--2">
                        <label class="modal-segmented-item">
                            <input type="radio" name="mode" value="ringkasan" checked>
                            <span>Ringkasan</span>
                        </label>
                        <label class="modal-segmented-item">
                            <input type="radio" name="mode" value="lengkap">
                            <span>Lengkap</span>
                        </label>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 2fr; gap: var(--space-3); margin-top: 6px; padding-top: 10px; border-top: 1px solid var(--border-color);">
                    <button type="button" class="btn btn-secondary" id="btnCancelModalCetak" style="height: 38px; font-size: var(--text-xs);">Batal</button>
                    <button type="submit" class="btn btn-primary" id="btnSubmitModalCetak" style="height: 38px; font-size: var(--text-xs);">
                        <i class="fas fa-print me-1"></i> Buka Lembar Cetak
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ===================== MODAL SNAPSHOT FOTO ===================== --}}
    <div id="modalFotoSnapshot" class="modal-backdrop"
        style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.75); z-index: 9999; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
        <div class="card"
            style="max-width: 640px; width: 94%; max-height: 90vh; display: flex; flex-direction: column; margin: 0; border-radius: var(--radius-xl, 16px); padding: 20px; background: var(--bg-card); border: 1px solid var(--border-color);">
            <div
                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; padding-bottom: 10px; border-bottom: 1px solid var(--border-color);">
                <h4 id="fotoSnapshotTitle"
                    style="font-size: var(--text-base); font-weight: var(--font-bold); color: var(--text-color); margin: 0;">
                    Foto Bukti Presensi</h4>
                <button type="button" id="btnCloseFotoModal"
                    style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: var(--text-lg);"
                    aria-label="Tutup">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div style="overflow: auto; text-align: center;">
                <img id="imgFotoSnapshot" src="" alt="Snapshot presensi"
                    style="max-width: 100%; max-height: 70vh; border-radius: var(--radius-lg, 12px);">
            </div>
        </div>
    </div>

    @push('scripts')
        <script
            src="{{ asset('js/laboran-log-presensi.js') }}?v={{ file_exists(public_path('js/laboran-log-presensi.js')) ? filemtime(public_path('js/laboran-log-presensi.js')) : time() }}"></script>
    @endpush
@endsection
