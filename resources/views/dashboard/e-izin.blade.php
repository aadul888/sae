@extends('layouts.dashboard')

@section('title', 'e-Izin Siswa — SAE (Sistem Aplikasi Edukasi)')
@section('dash_title', 'e-Izin Siswa')

@section('content')
<div class="content-wrapper">
    <!-- 1. Header Banner & Actions -->
    <div class="dash-banner" style="display: flex; align-items: center; justify-content: space-between; gap: var(--space-4, 16px); flex-wrap: wrap; margin-bottom: var(--space-5, 20px);">
        <div style="display: flex; align-items: center; gap: var(--space-4, 14px);">
            <div style="width: 48px; height: 48px; border-radius: var(--radius-lg, 12px); background: rgba(59,130,246,0.12); color: var(--primary, #3b82f6); display: flex; align-items: center; justify-content: center; font-size: var(--text-2xl, 1.5rem); flex-shrink: 0;">
                <i class="fas fa-file-signature"></i>
            </div>
            <div>
                <h2 style="font-size: var(--text-xl, 1.25rem); font-weight: 800; color: var(--text-color); margin: 0;">
                    e-Izin Keluar-Masuk &amp; Pulang Siswa
                </h2>
                <div style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); margin-top: 2px;">
                    Alur persetujuan terpadu administrasi izin KBM: Siswa &rarr; Wali Kelas &rarr; Guru Piket &rarr; Security Gerbang.
                </div>
            </div>
        </div>

        <div class="dash-banner-actions" style="display: flex; gap: var(--space-2, 8px); align-items: center; flex-wrap: wrap;">
            <a href="{{ route('kiosk.e-izin') }}" target="_blank" class="btn btn-outline"
                style="width: 38px; height: 38px; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px); padding: 0;"
                title="Buka Terminal Kiosk Gerbang" aria-label="Buka Terminal Kiosk Gerbang">
                <i class="fas fa-desktop text-primary"></i>
            </a>

            @if ($isSecurity || $isGuruPiket || $isSuperAdmin)
                <button type="button" class="btn btn-outline" id="btnOpenScanModal"
                    style="width: 38px; height: 38px; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px); padding: 0;"
                    title="Buka Pos Scanner / Verifikasi Gerbang" aria-label="Buka Pos Scanner / Verifikasi Gerbang">
                    <i class="fas fa-qrcode text-primary"></i>
                </button>
            @endif

            @if ($canCreate || $isStudent || $isGuruPiket || $isSuperAdmin)
                <button type="button" class="btn btn-primary" id="btnOpenAjukanModal"
                    style="width: 38px; height: 38px; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px); padding: 0;"
                    title="Ajukan e-Izin Baru" aria-label="Ajukan e-Izin Baru">
                    <i class="fas fa-plus"></i>
                </button>
            @endif
        </div>
    </div>

    <!-- 2. Stat Grid Baku SAE (4 Kartu Utama) -->
    <div class="dash-stat-grid" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); margin-bottom: var(--space-5, 20px);">
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(59,130,246,0.12); color: var(--primary, #3b82f6);">
                <i class="fas fa-ticket-alt"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ $stats['total'] }}</div>
                <div class="dash-stat-label">Total Izin Hari Ini</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(245,158,11,0.12); color: var(--warning, #f59e0b);">
                <i class="fas fa-user-clock"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="color: var(--warning, #f59e0b);">
                    {{ $stats['menunggu_wali'] + $stats['menunggu_piket'] }}
                </div>
                <div class="dash-stat-label">
                    Menunggu Approval
                    <span style="font-size: var(--text-xs, 0.75rem); opacity: 0.8; display: block;">
                        ({{ $stats['menunggu_wali'] }} wali kelas &bull; {{ $stats['menunggu_piket'] }} piket)
                    </span>
                </div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(234,88,12,0.12); color: #ea580c;">
                <i class="fas fa-person-walking"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="color: #ea580c;">
                    {{ $stats['menunggu_satpam'] + $stats['di_luar'] }}
                </div>
                <div class="dash-stat-label">
                    Izin Aktif / Di Luar
                    <span style="font-size: var(--text-xs, 0.75rem); opacity: 0.8; display: block;">
                        ({{ $stats['menunggu_satpam'] }} siap gerbang &bull; {{ $stats['di_luar'] }} di luar)
                    </span>
                </div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(16,185,129,0.12); color: var(--success, #10b981);">
                <i class="fas fa-circle-check"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="color: var(--success, #10b981);">
                    {{ $stats['kembali'] + $stats['pulang'] }}
                </div>
                <div class="dash-stat-label">
                    Selesai Izin
                    <span style="font-size: var(--text-xs, 0.75rem); opacity: 0.8; display: block;">
                        ({{ $stats['kembali'] }} kembali &bull; {{ $stats['pulang'] }} pulang)
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- 3. Tab Nav Kontekstual Berbasis Peran -->
    <div class="dash-tab-nav" style="display: flex; gap: var(--space-2, 8px); margin-bottom: var(--space-4, 16px); flex-wrap: wrap;">
        @if ($isSuperAdmin || !$isStudent)
            <a href="{{ route('dashboard.e-izin.index', array_merge(request()->except('tab', 'page'), ['tab' => 'semua'])) }}"
                class="btn {{ $tab === 'semua' ? 'btn-primary' : 'btn-outline' }}"
                style="height: 38px; border-radius: var(--radius-md, 8px); font-size: var(--text-sm, 0.875rem); display: inline-flex; align-items: center; gap: var(--space-2, 6px);">
                <i class="fas fa-list-check"></i> Semua Data
            </a>
        @endif

        @if ($isStudent || $isSuperAdmin)
            <a href="{{ route('dashboard.e-izin.index', array_merge(request()->except('tab', 'page'), ['tab' => 'saya'])) }}"
                class="btn {{ $tab === 'saya' ? 'btn-primary' : 'btn-outline' }}"
                style="height: 38px; border-radius: var(--radius-md, 8px); font-size: var(--text-sm, 0.875rem); display: inline-flex; align-items: center; gap: var(--space-2, 6px);">
                <i class="fas fa-user-graduate"></i> Izin Saya
            </a>
        @endif

        @if ($isWaliKelas || $isSuperAdmin)
            <a href="{{ route('dashboard.e-izin.index', array_merge(request()->except('tab', 'page'), ['tab' => 'wali'])) }}"
                class="btn {{ $tab === 'wali' ? 'btn-primary' : 'btn-outline' }}"
                style="height: 38px; border-radius: var(--radius-md, 8px); font-size: var(--text-sm, 0.875rem); display: inline-flex; align-items: center; gap: var(--space-2, 6px);">
                <i class="fas fa-chalkboard-user"></i> Persetujuan Wali Kelas
                @if ($stats['menunggu_wali'] > 0)
                    <span class="badge badge-warning" style="font-size: var(--text-xs, 0.75rem); padding: 2px 6px; border-radius: var(--radius-full, 9999px);">
                        {{ $stats['menunggu_wali'] }}
                    </span>
                @endif
            </a>
        @endif

        @if ($isGuruPiket || $isSuperAdmin)
            <a href="{{ route('dashboard.e-izin.index', array_merge(request()->except('tab', 'page'), ['tab' => 'piket'])) }}"
                class="btn {{ $tab === 'piket' ? 'btn-primary' : 'btn-outline' }}"
                style="height: 38px; border-radius: var(--radius-md, 8px); font-size: var(--text-sm, 0.875rem); display: inline-flex; align-items: center; gap: var(--space-2, 6px);">
                <i class="fas fa-clipboard-user"></i> Persetujuan Guru Piket
                @if ($stats['menunggu_piket'] > 0)
                    <span class="badge badge-primary" style="font-size: var(--text-xs, 0.75rem); padding: 2px 6px; border-radius: var(--radius-full, 9999px);">
                        {{ $stats['menunggu_piket'] }}
                    </span>
                @endif
            </a>
        @endif

        @if ($isSecurity || $isSuperAdmin)
            <a href="{{ route('dashboard.e-izin.index', array_merge(request()->except('tab', 'page'), ['tab' => 'security'])) }}"
                class="btn {{ $tab === 'security' ? 'btn-primary' : 'btn-outline' }}"
                style="height: 38px; border-radius: var(--radius-md, 8px); font-size: var(--text-sm, 0.875rem); display: inline-flex; align-items: center; gap: var(--space-2, 6px);">
                <i class="fas fa-shield-halved"></i> Pos Gerbang Security
                @if ($stats['menunggu_satpam'] + $stats['di_luar'] > 0)
                    <span class="badge badge-info" style="font-size: var(--text-xs, 0.75rem); padding: 2px 6px; border-radius: var(--radius-full, 9999px);">
                        {{ $stats['menunggu_satpam'] + $stats['di_luar'] }}
                    </span>
                @endif
            </a>
        @endif
    </div>

    <!-- 4. Unified Datatable Card Wrapper -->
    <div class="card datatable-card-wrapper" id="tableDataContainer">
        <!-- Toolbar Header (Entries, Filters & Live Search) -->
        <div class="datatable-toolbar">
            <form method="GET" action="{{ route('dashboard.e-izin.index') }}" id="filterForm" style="margin: 0;">
                <input type="hidden" name="tab" value="{{ $tab }}">

                <div style="display: flex; flex-wrap: wrap; gap: var(--space-3, 12px); justify-content: space-between; align-items: center;">
                    <div style="display: flex; flex-wrap: wrap; gap: var(--space-2, 10px); align-items: center;">
                        <div class="toolbar-entries">
                            <label for="perPageSelect" style="margin: 0; font-size: var(--text-sm, 0.875rem);">Tampilkan</label>
                            <select id="perPageSelect" name="perPage" class="per-page-select" style="height: 38px;">
                                @foreach ([10, 15, 25, 50, 100] as $n)
                                    <option value="{{ $n }}" {{ request('perPage', 15) == $n ? 'selected' : '' }}>
                                        {{ $n }}
                                    </option>
                                @endforeach
                            </select>
                            <span style="font-size: var(--text-sm, 0.875rem);">entri</span>
                        </div>

                        <input type="date" name="tanggal" value="{{ $tanggal }}" class="form-control"
                            style="height: 38px; width: auto; min-width: 140px; padding: 4px 10px; border-radius: var(--radius-md, 8px);"
                            title="Filter Tanggal">

                        <select name="jenis_izin" class="toolbar-filter-select" style="height: 38px; min-width: 150px; border-radius: var(--radius-md, 8px);">
                            <option value="">Semua Jenis Izin</option>
                            <option value="keluar_sebentar" {{ $jenis === 'keluar_sebentar' ? 'selected' : '' }}>Keluar-Masuk (KBM)</option>
                            <option value="pulang_cepat" {{ $jenis === 'pulang_cepat' ? 'selected' : '' }}>Izin Pulang (Cepat)</option>
                        </select>

                        <select name="status" class="toolbar-filter-select" style="height: 38px; min-width: 160px; border-radius: var(--radius-md, 8px);">
                            <option value="">Semua Status</option>
                            <option value="menunggu_wali" {{ $status === 'menunggu_wali' ? 'selected' : '' }}>Menunggu Wali Kelas</option>
                            <option value="menunggu_piket" {{ $status === 'menunggu_piket' ? 'selected' : '' }}>Menunggu Guru Piket</option>
                            <option value="menunggu_satpam" {{ $status === 'menunggu_satpam' ? 'selected' : '' }}>Disetujui (Siap Gerbang)</option>
                            <option value="di_luar" {{ $status === 'di_luar' ? 'selected' : '' }}>Sedang di Luar</option>
                            <option value="kembali" {{ $status === 'kembali' ? 'selected' : '' }}>Sudah Kembali</option>
                            <option value="pulang_selesai" {{ $status === 'pulang_selesai' ? 'selected' : '' }}>Pulang Selesai</option>
                            <option value="ditolak_wali" {{ $status === 'ditolak_wali' ? 'selected' : '' }}>Ditolak Wali Kelas</option>
                            <option value="ditolak_piket" {{ $status === 'ditolak_piket' ? 'selected' : '' }}>Ditolak Guru Piket</option>
                            <option value="dibatalkan" {{ $status === 'dibatalkan' ? 'selected' : '' }}>Dibatalkan</option>
                        </select>

                        @if (!$isStudent && $rombelList->isNotEmpty())
                            <select name="rombel_id" class="toolbar-filter-select" style="height: 38px; min-width: 140px; border-radius: var(--radius-md, 8px);">
                                <option value="">Semua Rombel</option>
                                @foreach ($rombelList as $rb)
                                    <option value="{{ $rb->rombongan_belajar_id }}" {{ $rombelId === $rb->rombongan_belajar_id ? 'selected' : '' }}>
                                        {{ $rb->nama }}
                                    </option>
                                @endforeach
                            </select>
                        @endif

                        @if ($search || $jenis || $status || $rombelId || ($tanggal && $tanggal !== date('Y-m-d')))
                            <a href="{{ route('dashboard.e-izin.index', ['tab' => $tab]) }}"
                                class="btn btn-outline btn-reset-filter"
                                style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px);"
                                title="Reset filter" data-live-reset>
                                <i class="fas fa-undo"></i>
                            </a>
                        @endif
                    </div>

                    <div class="live-search-wrap" style="height: 38px;">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" name="search" id="searchEIzin" value="{{ $search }}"
                            placeholder="Cari tiket, siswa, NISN, alasan..." autocomplete="off">
                        <button type="button" id="clearSearch"
                            class="clear-search {{ !empty($search) ? 'visible' : '' }}" title="Hapus pencarian">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Konten Datatable Responsive Stack -->
        <div class="table-responsive-stack" style="overflow-x: auto; width: 100%;">
            <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                <thead>
                    <tr style="border-bottom: 1px solid var(--border-color); background: var(--bg-card);">
                        <th style="padding: 12px 14px; text-align: center; width: 48px; font-size: var(--text-xs, 0.75rem);">No</th>
                        <th style="padding: 12px 14px; font-size: var(--text-xs, 0.75rem);">Tiket &amp; Jenis</th>
                        <th style="padding: 12px 14px; font-size: var(--text-xs, 0.75rem);">Siswa &amp; Kelas</th>
                        <th style="padding: 12px 14px; font-size: var(--text-xs, 0.75rem);">Waktu &amp; Keperluan</th>
                        <th style="padding: 12px 14px; text-align: center; font-size: var(--text-xs, 0.75rem);">Alur Persetujuan (4 Pihak)</th>
                        <th style="padding: 12px 14px; text-align: center; font-size: var(--text-xs, 0.75rem);">Status</th>
                        <th style="padding: 12px 14px; text-align: right; font-size: var(--text-xs, 0.75rem);">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $index => $row)
                        @php
                            $isRowWali = $isSuperAdmin || ($waliRombel && $waliRombel->rombongan_belajar_id === $row->rombel_id);
                            $isRowPiket = $isGuruPiket || $isSuperAdmin;
                            $isRowSecurity = $isSecurity || $isSuperAdmin;
                            $isMyPermit = $siswaLogin && ($siswaLogin->peserta_didik_id === $row->peserta_didik_id);
                        @endphp
                        <tr style="border-bottom: 1px solid var(--border-color); transition: background 0.15s ease;">
                            <td style="padding: 12px 14px; text-align: center; font-weight: 700; color: var(--text-muted); font-size: var(--text-xs, 0.75rem);" data-label="No">
                                {{ $items->firstItem() + $index }}
                            </td>

                            <td style="padding: 12px 14px;" data-label="Tiket &amp; Jenis">
                                <div style="display: flex; flex-direction: column; gap: 4px;">
                                    <span style="font-weight: 800; font-family: monospace; font-size: var(--text-sm, 0.875rem); color: var(--text-color);">
                                        {{ $row->nomor_tiket }}
                                    </span>
                                    <div style="display: flex; gap: 4px; align-items: center; flex-wrap: wrap;">
                                        @if ($row->jenis_izin === 'pulang_cepat')
                                            <span class="badge badge-danger" style="font-size: var(--text-xs, 0.6875rem); padding: 2px 6px; border-radius: var(--radius-xs, 4px);">
                                                <i class="fas fa-house-user"></i> Izin Pulang
                                            </span>
                                        @else
                                            <span class="badge badge-primary" style="font-size: var(--text-xs, 0.6875rem); padding: 2px 6px; border-radius: var(--radius-xs, 4px);">
                                                <i class="fas fa-person-walking"></i> Keluar-Masuk KBM
                                            </span>
                                        @endif
                                        <span style="font-size: var(--text-xs, 0.6875rem); color: var(--text-muted);">
                                            {{ \Carbon\Carbon::parse($row->tanggal)->translatedFormat('d M Y') }}
                                        </span>
                                    </div>
                                </div>
                            </td>

                            <td style="padding: 12px 14px;" data-label="Siswa &amp; Kelas">
                                <div style="display: flex; flex-direction: column; gap: 2px;">
                                    <span style="font-weight: 700; color: var(--text-color); font-size: var(--text-sm, 0.875rem);">
                                        {{ $row->nama_siswa ?? 'Siswa' }}
                                    </span>
                                    <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted);">
                                        <span>NISN: {{ $row->nisn ?? '-' }}</span> &bull;
                                        <span style="font-weight: 600; color: var(--primary, #3b82f6);">{{ $row->nama_rombel ?? 'Kelas -' }}</span>
                                    </div>
                                </div>
                            </td>

                            <td style="padding: 12px 14px;" data-label="Waktu &amp; Keperluan">
                                <div style="display: flex; flex-direction: column; gap: 4px;">
                                    <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-color);">
                                        <i class="fas fa-clock text-primary"></i> Keluar: <strong>{{ substr($row->jam_izin_keluar, 0, 5) }}</strong>
                                        @if ($row->jenis_izin === 'keluar_sebentar')
                                            &rarr; Rencana Kembali: <strong>{{ $row->jam_rencana_kembali ? substr($row->jam_rencana_kembali, 0, 5) : '-' }}</strong>
                                            @if ($row->jam_kembali_aktual)
                                                <span style="color: var(--success, #10b981); font-weight: 700;">(Aktual: {{ substr($row->jam_kembali_aktual, 0, 5) }})</span>
                                            @endif
                                        @endif
                                    </div>
                                    <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); line-height: 1.35; max-width: 280px;">
                                        &ldquo;{{ Str::limit($row->alasan, 80) }}&rdquo;
                                    </div>
                                </div>
                            </td>

                            <td style="padding: 12px 14px; text-align: center;" data-label="Alur Persetujuan">
                                <div style="display: inline-flex; align-items: center; gap: 6px; flex-wrap: wrap; justify-content: center;">
                                    <!-- 1. Siswa -->
                                    <span class="badge badge-success" title="1. Siswa: Diajukan"
                                        style="font-size: var(--text-xs, 0.6875rem); padding: 3px 6px; border-radius: var(--radius-xs, 4px);">
                                        <i class="fas fa-check"></i> Siswa
                                    </span>
                                    <i class="fas fa-chevron-right text-muted" style="font-size: var(--text-xs, 0.6875rem);"></i>

                                    <!-- 2. Wali Kelas -->
                                    @if ($row->wali_status === 'disetujui')
                                        <span class="badge badge-success" title="2. Wali Kelas: Disetujui ({{ $row->nama_wali ?? 'Wali Kelas' }})"
                                            style="font-size: var(--text-xs, 0.6875rem); padding: 3px 6px; border-radius: var(--radius-xs, 4px);">
                                            <i class="fas fa-check"></i> Wali Kelas
                                        </span>
                                    @elseif ($row->wali_status === 'ditolak')
                                        <span class="badge badge-danger" title="2. Wali Kelas: Ditolak"
                                            style="font-size: var(--text-xs, 0.6875rem); padding: 3px 6px; border-radius: var(--radius-xs, 4px);">
                                            <i class="fas fa-times"></i> Wali Kelas
                                        </span>
                                    @else
                                        <span class="badge badge-warning" title="2. Wali Kelas: Menunggu Persetujuan"
                                            style="font-size: var(--text-xs, 0.6875rem); padding: 3px 6px; border-radius: var(--radius-xs, 4px);">
                                            <i class="fas fa-hourglass-half"></i> Wali Kelas
                                        </span>
                                    @endif
                                    <i class="fas fa-chevron-right text-muted" style="font-size: var(--text-xs, 0.6875rem);"></i>

                                    <!-- 3. Guru Piket -->
                                    @if ($row->piket_status === 'disetujui')
                                        <span class="badge badge-success" title="3. Guru Piket: Disetujui ({{ $row->nama_piket ?? 'Guru Piket' }})"
                                            style="font-size: var(--text-xs, 0.6875rem); padding: 3px 6px; border-radius: var(--radius-xs, 4px);">
                                            <i class="fas fa-check"></i> Piket
                                        </span>
                                    @elseif ($row->piket_status === 'ditolak')
                                        <span class="badge badge-danger" title="3. Guru Piket: Ditolak"
                                            style="font-size: var(--text-xs, 0.6875rem); padding: 3px 6px; border-radius: var(--radius-xs, 4px);">
                                            <i class="fas fa-times"></i> Piket
                                        </span>
                                    @else
                                        <span class="badge {{ $row->wali_status === 'disetujui' ? 'badge-info' : 'badge-secondary' }}"
                                            title="3. Guru Piket: {{ $row->wali_status === 'disetujui' ? 'Menunggu Persetujuan' : 'Antre Wali' }}"
                                            style="font-size: var(--text-xs, 0.6875rem); padding: 3px 6px; border-radius: var(--radius-xs, 4px);">
                                            <i class="fas fa-hourglass-half"></i> Piket
                                        </span>
                                    @endif
                                    <i class="fas fa-chevron-right text-muted" style="font-size: var(--text-xs, 0.6875rem);"></i>

                                    <!-- 4. Security Gerbang -->
                                    @if ($row->status === 'kembali' || $row->status === 'pulang_selesai')
                                        <span class="badge badge-success" title="4. Security: Selesai diverifikasi ({{ $row->satpam_checkin_by ?? $row->satpam_checkout_by ?? 'Security' }})"
                                            style="font-size: var(--text-xs, 0.6875rem); padding: 3px 6px; border-radius: var(--radius-xs, 4px);">
                                            <i class="fas fa-shield-halved"></i> Verif Selesai
                                        </span>
                                    @elseif ($row->status === 'di_luar')
                                        <span class="badge badge-warning" title="4. Security: Diverifikasi Keluar, Sedang di Luar"
                                            style="font-size: var(--text-xs, 0.6875rem); padding: 3px 6px; border-radius: var(--radius-xs, 4px);">
                                            <i class="fas fa-person-walking"></i> Di Luar
                                        </span>
                                    @elseif ($row->status === 'menunggu_satpam')
                                        <span class="badge badge-primary" title="4. Security: Siap di Gerbang Satpam"
                                            style="font-size: var(--text-xs, 0.6875rem); padding: 3px 6px; border-radius: var(--radius-xs, 4px);">
                                            <i class="fas fa-door-open"></i> Siap Keluar
                                        </span>
                                    @else
                                        <span class="badge badge-secondary" title="4. Security: Menunggu Persetujuan Guru"
                                            style="font-size: var(--text-xs, 0.6875rem); padding: 3px 6px; border-radius: var(--radius-xs, 4px);">
                                            <i class="fas fa-shield-halved"></i> Security
                                        </span>
                                    @endif
                                </div>
                            </td>

                            <td style="padding: 12px 14px; text-align: center;" data-label="Status">
                                @if ($row->status === 'menunggu_wali')
                                    <span class="badge badge-warning" style="font-size: var(--text-xs, 0.75rem); padding: 3px 8px; border-radius: var(--radius-full, 9999px);">
                                        <i class="fas fa-clock"></i> Menunggu Wali
                                    </span>
                                @elseif ($row->status === 'menunggu_piket')
                                    <span class="badge badge-info" style="font-size: var(--text-xs, 0.75rem); padding: 3px 8px; border-radius: var(--radius-full, 9999px);">
                                        <i class="fas fa-clipboard-user"></i> Menunggu Piket
                                    </span>
                                @elseif ($row->status === 'menunggu_satpam')
                                    <span class="badge badge-primary" style="font-size: var(--text-xs, 0.75rem); padding: 3px 8px; border-radius: var(--radius-full, 9999px);">
                                        <i class="fas fa-shield-halved"></i> Siap Gerbang
                                    </span>
                                @elseif ($row->status === 'di_luar')
                                    <span class="badge badge-warning" style="font-size: var(--text-xs, 0.75rem); padding: 3px 8px; border-radius: var(--radius-full, 9999px); background: #ea580c; color: #fff;">
                                        <i class="fas fa-person-walking"></i> Sedang di Luar
                                    </span>
                                @elseif ($row->status === 'kembali')
                                    <span class="badge badge-success" style="font-size: var(--text-xs, 0.75rem); padding: 3px 8px; border-radius: var(--radius-full, 9999px);">
                                        <i class="fas fa-circle-check"></i> Sudah Kembali
                                    </span>
                                @elseif ($row->status === 'pulang_selesai')
                                    <span class="badge badge-success" style="font-size: var(--text-xs, 0.75rem); padding: 3px 8px; border-radius: var(--radius-full, 9999px);">
                                        <i class="fas fa-house-circle-check"></i> Pulang Selesai
                                    </span>
                                @elseif ($row->status === 'ditolak_wali')
                                    <span class="badge badge-danger" style="font-size: var(--text-xs, 0.75rem); padding: 3px 8px; border-radius: var(--radius-full, 9999px);">
                                        <i class="fas fa-times-circle"></i> Ditolak Wali
                                    </span>
                                @elseif ($row->status === 'ditolak_piket')
                                    <span class="badge badge-danger" style="font-size: var(--text-xs, 0.75rem); padding: 3px 8px; border-radius: var(--radius-full, 9999px);">
                                        <i class="fas fa-times-circle"></i> Ditolak Piket
                                    </span>
                                @elseif ($row->status === 'dibatalkan')
                                    <span class="badge badge-secondary" style="font-size: var(--text-xs, 0.75rem); padding: 3px 8px; border-radius: var(--radius-full, 9999px);">
                                        <i class="fas fa-ban"></i> Dibatalkan
                                    </span>
                                @else
                                    <span class="badge badge-secondary" style="font-size: var(--text-xs, 0.75rem); padding: 3px 8px; border-radius: var(--radius-full, 9999px);">
                                        {{ ucfirst(str_replace('_', ' ', $row->status)) }}
                                    </span>
                                @endif
                            </td>

                            <td style="padding: 12px 14px; text-align: right;" data-label="Aksi">
                                <div class="table-actions" style="display: flex; gap: 4px; justify-content: flex-end; align-items: center; flex-wrap: wrap;">
                                    <!-- Tombol Detail Modal -->
                                    <button type="button" class="btn-icon btn-view-detail" data-id="{{ $row->id }}"
                                        title="Detail Riwayat e-Izin" aria-label="Detail e-Izin" style="color: var(--primary, #3b82f6);">
                                        <i class="fas fa-eye"></i>
                                    </button>

                                    <!-- AKSI 1: Persetujuan Wali Kelas -->
                                    @if ($row->status === 'menunggu_wali' && $isRowWali)
                                        <button type="button" class="btn-icon btn-approve-wali" data-id="{{ $row->id }}" data-tiket="{{ $row->nomor_tiket }}" data-nama="{{ $row->nama_siswa }}"
                                            title="Setujui sebagai Wali Kelas" aria-label="Setujui Wali Kelas" style="color: var(--success, #10b981);">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <button type="button" class="btn-icon btn-reject-wali" data-id="{{ $row->id }}" data-tiket="{{ $row->nomor_tiket }}" data-nama="{{ $row->nama_siswa }}"
                                            title="Tolak sebagai Wali Kelas" aria-label="Tolak Wali Kelas" style="color: var(--danger, #ef4444);">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    @endif

                                    <!-- AKSI 2: Persetujuan Guru Piket -->
                                    @if ($row->status === 'menunggu_piket' && $isRowPiket)
                                        <button type="button" class="btn-icon btn-approve-piket" data-id="{{ $row->id }}" data-tiket="{{ $row->nomor_tiket }}" data-nama="{{ $row->nama_siswa }}"
                                            title="Setujui sebagai Guru Piket" aria-label="Setujui Guru Piket" style="color: #6366f1;">
                                            <i class="fas fa-check-double"></i>
                                        </button>
                                        <button type="button" class="btn-icon btn-reject-piket" data-id="{{ $row->id }}" data-tiket="{{ $row->nomor_tiket }}" data-nama="{{ $row->nama_siswa }}"
                                            title="Tolak sebagai Guru Piket" aria-label="Tolak Guru Piket" style="color: var(--danger, #ef4444);">
                                            <i class="fas fa-ban"></i>
                                        </button>
                                    @endif

                                    <!-- AKSI 3: Verifikasi Security Gerbang -->
                                    @if ($row->status === 'menunggu_satpam' && $isRowSecurity)
                                        <button type="button" class="btn-icon btn-checkout-gate" data-id="{{ $row->id }}" data-tiket="{{ $row->nomor_tiket }}" data-nama="{{ $row->nama_siswa }}" data-jenis="{{ $row->jenis_izin }}"
                                            title="Verifikasi KELUAR Gerbang (Security)" aria-label="Verifikasi Keluar" style="color: #ea580c;">
                                            <i class="fas fa-door-open"></i>
                                        </button>
                                    @endif

                                    @if ($row->status === 'di_luar' && $isRowSecurity && $row->jenis_izin === 'keluar_sebentar')
                                        <button type="button" class="btn-icon btn-checkin-gate" data-id="{{ $row->id }}" data-tiket="{{ $row->nomor_tiket }}" data-nama="{{ $row->nama_siswa }}"
                                            title="Verifikasi KEMBALI Masuk Gerbang (Security)" aria-label="Verifikasi Masuk Kembali" style="color: var(--success, #10b981);">
                                            <i class="fas fa-right-to-bracket"></i>
                                        </button>
                                    @endif

                                    <!-- Tiket / Slip Digital Siswa (Modal Pass & Barcode) -->
                                    <button type="button" class="btn-icon btn-slip-digital" data-id="{{ $row->id }}"
                                        title="Buka Tiket / Slip Digital (Pass & Barcode)" aria-label="Tiket Digital" style="color: #6366f1;">
                                        <i class="fas fa-ticket-simple"></i>
                                    </button>

                                    <!-- Cetak Slip Izin (Jika sudah disetujui piket / keluar) -->
                                    @if (in_array($row->status, ['menunggu_satpam', 'di_luar', 'kembali', 'pulang_selesai']))
                                        <a href="{{ route('dashboard.e-izin.cetak', $row->id) }}" target="_blank" class="btn-icon"
                                            title="Cetak Slip e-Izin Fisik" aria-label="Cetak Slip e-Izin Fisik" style="color: var(--text-color);">
                                            <i class="fas fa-print"></i>
                                        </a>
                                    @endif

                                    <!-- Batalkan (Oleh Siswa / Admin jika masih menunggu) -->
                                    @if (in_array($row->status, ['menunggu_wali', 'menunggu_piket']) && ($isMyPermit || $isSuperAdmin))
                                        <button type="button" class="btn-icon btn-cancel-permit" data-id="{{ $row->id }}" data-tiket="{{ $row->nomor_tiket }}"
                                            title="Batalkan Pengajuan e-Izin" aria-label="Batalkan e-Izin" style="color: var(--text-muted);">
                                            <i class="fas fa-trash-can"></i>
                                        </button>
                                    @endif

                                    <!-- Hapus Fisik (Super Admin) -->
                                    @if ($isSuperAdmin)
                                        <form action="{{ route('dashboard.e-izin.destroy', $row->id) }}" method="POST" data-confirm="delete" data-name="{{ $row->nomor_tiket }}" style="display: inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-icon" title="Hapus Permanen Data" aria-label="Hapus Permanen" style="color: var(--danger, #ef4444);">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 48px 16px; color: var(--text-muted); font-size: var(--text-sm, 0.875rem);">
                                <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; gap: var(--space-2, 8px);">
                                    <i class="fas fa-file-circle-xmark" style="font-size: var(--text-3xl, 2.5rem); opacity: 0.35;"></i>
                                    <span style="font-weight: 600;">Tidak ada data e-Izin yang sesuai dengan filter.</span>
                                    <span style="font-size: var(--text-xs, 0.75rem);">Silakan ubah tanggal atau tab filter di bagian atas.</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- 5. Footer Paginasi Terpadu -->
        @if ($items instanceof \Illuminate\Pagination\LengthAwarePaginator && $items->hasPages())
            <div class="datatable-footer">
                @include('partials.datatable-pagination', ['paginator' => $items])
            </div>
        @endif
    </div>
</div>

<!-- ============================================================== -->
<!-- 6. MODAL 1: Form Ajukan e-Izin Baru (z-index: 99999 !important) -->
<!-- ============================================================== -->
<div id="modalAjukanIzin" class="modal-backdrop" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.65); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
    <div class="card" style="max-width: 520px; width: 92%; margin: 0; border-radius: var(--radius-xl, 14px); padding: var(--space-5, 20px); box-shadow: 0 10px 30px rgba(0,0,0,0.4); border: 1px solid var(--border-color); background: var(--bg-card);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--space-4, 16px); border-bottom: 1px solid var(--border-color); padding-bottom: var(--space-3, 12px);">
            <h3 style="font-size: var(--text-md, 1.05rem); font-weight: 800; color: var(--text-color); margin: 0; display: flex; align-items: center; gap: var(--space-2, 8px);">
                <i class="fas fa-file-signature text-primary"></i> Ajukan e-Izin Keluar/Pulang
            </h3>
            <button type="button" class="btn-close-modal" aria-label="Tutup" title="Tutup" data-target="modalAjukanIzin" style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: var(--text-lg, 1.15rem); padding: 4px;">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form action="{{ route('dashboard.e-izin.store') }}" method="POST" id="formAjukanEIzin">
            @csrf

            @if ($isStudent && $siswaLogin)
                <div style="margin-bottom: var(--space-3, 12px); background: rgba(59,130,246,0.06); padding: 10px 14px; border-radius: var(--radius-md, 8px); border: 1px solid rgba(59,130,246,0.2);">
                    <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted);">Siswa Pemohon:</div>
                    <div style="font-weight: 700; color: var(--text-color); font-size: var(--text-sm, 0.875rem);">
                        {{ $siswaLogin->nama }} (NISN: {{ $siswaLogin->nisn ?? '-' }})
                    </div>
                    <div style="font-size: var(--text-xs, 0.75rem); color: var(--primary, #3b82f6);">
                        Kelas: {{ $siswaLogin->nama_rombel ?? 'Rombel' }} &bull; Wali Kelas: {{ $siswaLogin->wali_nama ?? 'Wali Kelas' }}
                    </div>
                </div>
            @else
                <div style="margin-bottom: var(--space-3, 12px);">
                    <label for="selectSiswaModal" style="display: block; font-weight: 700; font-size: var(--text-xs, 0.75rem); margin-bottom: 4px;">
                        Pilih Peserta Didik <span style="color: var(--danger, #ef4444);">*</span>
                    </label>
                    <select id="selectSiswaModal" name="peserta_didik_id" class="form-control" required style="width: 100%; height: 38px; border-radius: var(--radius-md, 8px);">
                        <option value="">-- Pilih Siswa --</option>
                    </select>
                    <div style="font-size: var(--text-xs, 0.6875rem); color: var(--text-muted); margin-top: 2px;">
                        Ketik nama atau NISN siswa untuk mencari.
                    </div>
                </div>
            @endif

            <div style="margin-bottom: var(--space-3, 12px);">
                <label for="inputJenisIzin" style="display: block; font-weight: 700; font-size: var(--text-xs, 0.75rem); margin-bottom: 4px;">
                    Jenis Izin <span style="color: var(--danger, #ef4444);">*</span>
                </label>
                <select id="inputJenisIzin" name="jenis_izin" class="form-control" required style="width: 100%; height: 38px; border-radius: var(--radius-md, 8px);">
                    <option value="keluar_sebentar">Izin Keluar-Masuk (Saat Jam KBM Aktif)</option>
                    <option value="pulang_cepat">Izin Pulang (Meninggalkan Sekolah Lebih Cepat)</option>
                </select>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-3, 12px); margin-bottom: var(--space-3, 12px);">
                <div>
                    <label for="inputJamKeluar" style="display: block; font-weight: 700; font-size: var(--text-xs, 0.75rem); margin-bottom: 4px;">
                        Jam Mulai Izin <span style="color: var(--danger, #ef4444);">*</span>
                    </label>
                    <input type="time" id="inputJamKeluar" name="jam_izin_keluar" value="{{ date('H:i') }}" class="form-control" required
                        style="width: 100%; height: 38px; border-radius: var(--radius-md, 8px);">
                </div>

                <div id="wrapRencanaKembali">
                    <label for="inputJamRencanaKembali" style="display: block; font-weight: 700; font-size: var(--text-xs, 0.75rem); margin-bottom: 4px;">
                        Rencana Kembali <span style="font-size: var(--text-xs, 0.7rem); color: var(--text-muted); font-weight: 400;">(Default 10 Menit)</span>
                    </label>
                    <input type="time" id="inputJamRencanaKembali" name="jam_rencana_kembali" value="{{ date('H:i', strtotime('+10 minutes')) }}" class="form-control"
                        style="width: 100%; height: 38px; border-radius: var(--radius-md, 8px);">
                </div>
            </div>

            <div style="margin-bottom: var(--space-4, 16px);">
                <label for="inputAlasanIzin" style="display: block; font-weight: 700; font-size: var(--text-xs, 0.75rem); margin-bottom: 4px;">
                    Alasan / Keperluan Izin <span style="color: var(--danger, #ef4444);">*</span>
                </label>
                <textarea id="inputAlasanIzin" name="alasan" rows="3" class="form-control" required maxlength="500"
                    placeholder="Contoh: Berobat ke Puskesmas karena pusing mendadak / Pengambilan berkas KK untuk PIP..."
                    style="width: 100%; border-radius: var(--radius-md, 8px); padding: 8px 12px; font-size: var(--text-sm, 0.875rem);"></textarea>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: var(--space-2, 8px);">
                <button type="button" class="btn btn-outline btn-close-modal" data-target="modalAjukanIzin"
                    style="height: 38px; border-radius: var(--radius-md, 8px); font-size: var(--text-sm, 0.875rem);">
                    Batal
                </button>
                <button type="submit" class="btn btn-primary" id="btnSubmitEIzin"
                    style="height: 38px; border-radius: var(--radius-md, 8px); font-size: var(--text-sm, 0.875rem);">
                    <i class="fas fa-paper-plane"></i> Kirim Pengajuan
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================================== -->
<!-- 7. MODAL 2: Modal Konfirmasi Persetujuan / Penolakan (Catatan)   -->
<!-- ============================================================== -->
<div id="modalApprovalAction" class="modal-backdrop" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.65); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
    <div class="card" style="max-width: 460px; width: 92%; margin: 0; border-radius: var(--radius-xl, 14px); padding: var(--space-5, 20px); box-shadow: 0 10px 30px rgba(0,0,0,0.4); border: 1px solid var(--border-color); background: var(--bg-card);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--space-4, 16px); border-bottom: 1px solid var(--border-color); padding-bottom: var(--space-3, 12px);">
            <h3 id="approvalModalTitle" style="font-size: var(--text-md, 1.05rem); font-weight: 800; color: var(--text-color); margin: 0; display: flex; align-items: center; gap: var(--space-2, 8px);">
                <i class="fas fa-check-circle text-primary"></i> Konfirmasi Tindakan
            </h3>
            <button type="button" class="btn-close-modal" aria-label="Tutup" title="Tutup" data-target="modalApprovalAction" style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: var(--text-lg, 1.15rem); padding: 4px;">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form id="formApprovalAction" method="POST" action="">
            @csrf
            <div id="approvalModalDesc" style="font-size: var(--text-sm, 0.875rem); color: var(--text-color); margin-bottom: var(--space-3, 12px);">
                Konfirmasi persetujuan tiket izin.
            </div>

            <div style="margin-bottom: var(--space-4, 16px);">
                <label for="approvalCatatan" style="display: block; font-weight: 700; font-size: var(--text-xs, 0.75rem); margin-bottom: 4px;">
                    Catatan Petugas (Opsional)
                </label>
                <textarea id="approvalCatatan" name="catatan" rows="3" class="form-control"
                    placeholder="Tuliskan pesan atau catatan tambahan..."
                    style="width: 100%; border-radius: var(--radius-md, 8px); padding: 8px 12px; font-size: var(--text-sm, 0.875rem);"></textarea>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: var(--space-2, 8px);">
                <button type="button" class="btn btn-outline btn-close-modal" data-target="modalApprovalAction"
                    style="height: 38px; border-radius: var(--radius-md, 8px); font-size: var(--text-sm, 0.875rem);">
                    Batal
                </button>
                <button type="submit" class="btn btn-primary" id="btnSubmitApprovalAction"
                    style="height: 38px; border-radius: var(--radius-md, 8px); font-size: var(--text-sm, 0.875rem);">
                    Simpan Tindakan
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================================== -->
<!-- 8. MODAL 3: Detail Lengkap & Timeline e-Izin                    -->
<!-- ============================================================== -->
<div id="modalDetailIzin" class="modal-backdrop" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.65); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
    <div class="card" style="max-width: 600px; width: 92%; margin: 0; border-radius: var(--radius-xl, 14px); padding: var(--space-5, 20px); box-shadow: 0 10px 30px rgba(0,0,0,0.4); border: 1px solid var(--border-color); background: var(--bg-card); max-height: 90vh; overflow-y: auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--space-4, 16px); border-bottom: 1px solid var(--border-color); padding-bottom: var(--space-3, 12px);">
            <h3 style="font-size: var(--text-md, 1.05rem); font-weight: 800; color: var(--text-color); margin: 0; display: flex; align-items: center; gap: var(--space-2, 8px);">
                <i class="fas fa-ticket text-primary"></i> Detail e-Izin Siswa
            </h3>
            <button type="button" class="btn-close-modal" aria-label="Tutup" title="Tutup" data-target="modalDetailIzin" style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: var(--text-lg, 1.15rem); padding: 4px;">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div id="detailIzinContent">
            <div style="text-align: center; padding: 24px; color: var(--text-muted);">
                <i class="fas fa-spinner fa-spin" style="font-size: var(--text-2xl, 1.5rem);"></i>
                <div style="margin-top: 8px;">Memuat rincian tiket...</div>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================== -->
<!-- 9. MODAL 4: Pos Scan & Verifikasi Gerbang Security              -->
<!-- ============================================================== -->
<div id="modalSecurityGate" class="modal-backdrop" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.65); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
    <div class="card" style="max-width: 540px; width: 92%; margin: 0; border-radius: var(--radius-xl, 14px); padding: var(--space-5, 20px); box-shadow: 0 10px 30px rgba(0,0,0,0.4); border: 1px solid var(--border-color); background: var(--bg-card);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--space-4, 16px); border-bottom: 1px solid var(--border-color); padding-bottom: var(--space-3, 12px);">
            <h3 style="font-size: var(--text-md, 1.05rem); font-weight: 800; color: var(--text-color); margin: 0; display: flex; align-items: center; gap: var(--space-2, 8px);">
                <i class="fas fa-shield-halved text-primary"></i> Pos Verifikasi Gerbang Satpam
            </h3>
            <button type="button" class="btn-close-modal" aria-label="Tutup" title="Tutup" data-target="modalSecurityGate" style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: var(--text-lg, 1.15rem); padding: 4px;">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div style="margin-bottom: var(--space-4, 16px);">
            <label for="inputScanTicket" style="display: block; font-weight: 700; font-size: var(--text-xs, 0.75rem); margin-bottom: 4px;">
                Scan Barcode / Input Nomor Tiket / NISN
            </label>
            <div style="display: flex; gap: var(--space-2, 8px);">
                <input type="text" id="inputScanTicket" class="form-control" placeholder="Contoh: IZN-20261102-0001 atau NISN..."
                    style="flex: 1; height: 42px; border-radius: var(--radius-md, 8px); font-family: monospace; font-size: var(--text-base, 1rem);">
                <button type="button" class="btn btn-primary" id="btnDoScanLookup"
                    style="height: 42px; padding: 0 16px; border-radius: var(--radius-md, 8px); font-size: var(--text-sm, 0.875rem);">
                    <i class="fas fa-search"></i> Periksa
                </button>
            </div>
        </div>

        <div id="scanResultArea" style="display: none; background: rgba(59,130,246,0.05); border: 1px solid var(--border-color); border-radius: var(--radius-lg, 10px); padding: var(--space-4, 14px); margin-bottom: var(--space-4, 16px);">
            <!-- Hasil pencarian tiket diinject via JS -->
        </div>

        <div style="display: flex; justify-content: flex-end;">
            <button type="button" class="btn btn-outline btn-close-modal" data-target="modalSecurityGate"
                style="height: 38px; border-radius: var(--radius-md, 8px); font-size: var(--text-sm, 0.875rem);">
                Tutup
            </button>
        </div>
    </div>
</div>

<!-- ============================================================== -->
<!-- 10. MODAL 5: Tiket / Slip Digital Siswa (Pass & Barcode)      -->
<!-- ============================================================== -->
<div id="modalSlipDigital" class="modal-backdrop" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.65); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
    <div class="card" style="max-width: 440px; width: 92%; margin: 0; border-radius: var(--radius-xl, 14px); padding: 0; box-shadow: 0 10px 30px rgba(0,0,0,0.4); border: 1px solid var(--border-color); background: var(--bg-card); overflow: hidden;">
        <div style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); color: #ffffff; padding: 14px 18px; display: flex; justify-content: space-between; align-items: center;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-ticket text-primary" style="font-size: var(--text-lg, 1.15rem);"></i>
                <span style="font-weight: 800; font-size: var(--text-md, 0.95rem); letter-spacing: 0.5px;">TIKET PASS DIGITAL e-IZIN</span>
            </div>
            <button type="button" class="btn-close-modal" aria-label="Tutup" title="Tutup" data-target="modalSlipDigital" style="background: none; border: none; color: #94a3b8; cursor: pointer; font-size: var(--text-lg, 1.15rem); padding: 4px;">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div id="slipDigitalContent" style="padding: 16px; max-height: 82vh; overflow-y: auto;">
            <div style="text-align: center; padding: 24px; color: var(--text-muted);">
                <i class="fas fa-spinner fa-spin" style="font-size: var(--text-2xl, 1.5rem);"></i>
                <div style="margin-top: 8px;">Menyiapkan tiket digital...</div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <script src="{{ asset('js/e-izin.js') }}"></script>
@endpush
