@extends('layouts.dashboard')

@section('title', 'Administrasi Kesiswaan (Buku Klaper, Mutasi & Kelulusan) — SAE')
@section('dash_title', 'Administrasi Kesiswaan')

@section('content')
    <!-- 1. Header Banner & Actions -->
    <div class="dash-banner"
        style="display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; margin-bottom: 20px;">
        <div style="display: flex; align-items: center; gap: 12px;">
            <div
                style="width: 44px; height: 44px; border-radius: var(--radius-lg, 12px); background: rgba(59,130,246,0.12); color: var(--primary-hover, #2563eb); display: flex; align-items: center; justify-content: center; font-size: var(--text-xl, 1.25rem); flex-shrink: 0;">
                <i class="fas fa-folder-closed"></i>
            </div>
            <div>
                <h2 style="font-size: var(--text-xl, 1.25rem); font-weight: 800; color: var(--text-color); margin: 0;">
                    Administrasi Kesiswaan
                </h2>
                <p style="margin: 2px 0 0 0; font-size: var(--text-sm, 0.8125rem); color: var(--text-muted);">
                    Pengelolaan Buku Klaper abjad, mutasi masuk/keluar, dan penetapan kelulusan serta SKL digital.
                </p>
            </div>
        </div>

        <div class="dash-banner-actions" style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
            @if ($canCreate)
                @if ($activeTab === 'klaper')
                    <button type="button" class="btn btn-outline" id="btnSyncKlaper"
                        title="Sinkronkan Buku Klaper dari Dapodik"
                        style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px);">
                        <i class="fas fa-rotate text-primary"></i>
                    </button>
                @elseif ($activeTab === 'mutasi')
                    <button type="button" class="btn btn-primary" id="btnOpenMutasiModal" title="Catat Mutasi Siswa"
                        style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px);">
                        <i class="fas fa-plus"></i>
                    </button>
                @elseif ($activeTab === 'kelulusan')
                    <button type="button" class="btn btn-primary" id="btnOpenKelulusanModal"
                        title="Catat / Update Kelulusan Siswa"
                        style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px);">
                        <i class="fas fa-plus"></i>
                    </button>
                @endif
            @endif
        </div>
    </div>

    <!-- 2. Stat Grid Baku SAE -->
    <div class="dash-stat-grid" style="margin-bottom: 20px;">
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(59,130,246,0.12); color: var(--primary-hover, #2563eb);">
                <i class="fas fa-user-graduate"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ number_format($stats['total_aktif'] ?? 0) }}</div>
                <div class="dash-stat-label">Siswa Aktif</div>
            </div>
        </div>
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(16,185,129,0.12); color: var(--success, #10b981);">
                <i class="fas fa-book-bookmark"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ number_format($stats['total_klaper'] ?? 0) }}</div>
                <div class="dash-stat-label">Buku Klaper</div>
            </div>
        </div>
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(239,68,68,0.12); color: var(--danger, #ef4444);">
                <i class="fas fa-person-walking-dashed-line-arrow-right"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ number_format($stats['total_mutasi'] ?? 0) }}</div>
                <div class="dash-stat-label">Total Mutasi</div>
            </div>
        </div>
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(245,158,11,0.12); color: var(--warning, #f59e0b);">
                <i class="fas fa-graduation-cap"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ number_format($stats['total_lulus'] ?? 0) }}</div>
                <div class="dash-stat-label">Siswa Lulus</div>
            </div>
        </div>
    </div>

    <!-- 3. Navigation Tabs Wrapper -->
    <div class="periode-nav-wrapper" style="margin-bottom: 20px;">
        <div class="periode-nav-desktop">
            <a href="{{ route('dashboard.kesiswaan.administrasi.index', ['tab' => 'klaper']) }}"
                class="periode-nav-tab {{ $activeTab === 'klaper' ? 'active' : '' }}">
                <i class="fas fa-address-book"></i> Buku Klaper
            </a>
            <a href="{{ route('dashboard.kesiswaan.administrasi.index', ['tab' => 'mutasi']) }}"
                class="periode-nav-tab {{ $activeTab === 'mutasi' ? 'active' : '' }}">
                <i class="fas fa-right-left"></i> Mutasi Siswa
            </a>
            <a href="{{ route('dashboard.kesiswaan.administrasi.index', ['tab' => 'kelulusan']) }}"
                class="periode-nav-tab {{ $activeTab === 'kelulusan' ? 'active' : '' }}">
                <i class="fas fa-graduation-cap"></i> Kelulusan &amp; SKL
            </a>
        </div>
    </div>

    <!-- 4. Content Sesuai Tab Aktif -->
    @if ($activeTab === 'klaper')
        <!-- TAB 1: BUKU KLAPER (Unified Datatable Baku SAE) -->
        <div class="card datatable-card-wrapper" id="tableDataContainer">
            <!-- 1. Toolbar Header (Entries, Abjad, Filter & Live Search) -->
            <div class="datatable-toolbar">
                <div style="display: flex; gap: 4px; flex-wrap: wrap; margin-bottom: 14px; align-items: center;">
                    <span
                        style="font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); margin-right: 8px;">Abjad:</span>
                    <a href="{{ route('dashboard.kesiswaan.administrasi.index', ['tab' => 'klaper', 'perPage' => $perPage]) }}"
                        class="badge {{ empty($abjad) ? 'badge-primary' : 'badge-outline' }}"
                        style="text-decoration: none; padding: 4px 8px;">Semua</a>
                    @foreach (range('A', 'Z') as $char)
                        <a href="{{ route('dashboard.kesiswaan.administrasi.index', ['tab' => 'klaper', 'abjad' => $char, 'perPage' => $perPage]) }}"
                            class="badge {{ $abjad === $char ? 'badge-primary' : 'badge-outline' }}"
                            style="text-decoration: none; padding: 4px 8px;">{{ $char }}</a>
                    @endforeach
                </div>

                <div
                    style="display: flex; flex-wrap: wrap; gap: 12px; justify-content: space-between; align-items: center;">
                    <div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
                        <div class="toolbar-entries">
                            <label for="perPageSelect" style="margin: 0;">Tampilkan</label>
                            <select id="perPageSelect" name="perPage" class="per-page-select" style="height: 38px;">
                                @foreach ([10, 15, 25, 50, 100] as $n)
                                    <option value="{{ $n }}" {{ ($perPage ?? 25) == $n ? 'selected' : '' }}>
                                        {{ $n }}</option>
                                @endforeach
                            </select>
                            <span>entri</span>
                        </div>

                        @if (!empty($q) || !empty($abjad))
                            <a href="{{ route('dashboard.kesiswaan.administrasi.index', ['tab' => 'klaper']) }}"
                                class="btn btn-outline btn-reset-filter"
                                style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px);"
                                title="Reset filter" data-live-reset>
                                <i class="fas fa-undo"></i>
                            </a>
                        @endif
                    </div>

                    <div class="live-search-wrap" style="height: 38px;">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" id="liveSearch" name="q"
                            placeholder="Cari nama / NISN / no. klaper..." value="{{ $q ?? '' }}"
                            autocomplete="off">
                        <button type="button" id="clearSearch" class="clear-search {{ !empty($q) ? 'visible' : '' }}"
                            title="Hapus pencarian">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- 2. Konten Datatable Responsive Stack -->
            <div class="table-responsive-stack" style="overflow-x: auto; width: 100%;">
                <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                    <thead>
                        <tr style="background: rgba(0,0,0,0.02); border-bottom: 1px solid var(--border-color);">
                            <th
                                style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 140px;">
                                No. Klaper</th>
                            <th
                                style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Nama Peserta Didik</th>
                            <th
                                style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 160px;">
                                NISN / NIPD</th>
                            <th
                                style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 80px;">
                                L/P</th>
                            <th
                                style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 130px;">
                                Rombel</th>
                            <th
                                style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 120px; text-align: center;">
                                Status</th>
                            <th
                                style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 100px; text-align: center;">
                                Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($klaperList as $item)
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <td
                                    style="padding: 12px 16px; font-family: monospace; font-weight: 700; color: var(--primary);">
                                    {{ $item->nomor_klaper ?: '-' }}
                                </td>
                                <td style="padding: 12px 16px; font-weight: 700; color: var(--text-color);">
                                    {{ $item->nama }}
                                </td>
                                <td style="padding: 12px 16px; font-family: monospace; font-size: var(--text-sm, 0.8125rem);">
                                    {{ $item->nisn ?: '-' }} / {{ $item->nipd ?: '-' }}
                                </td>
                                <td style="padding: 12px 16px; text-align: center;">
                                    <span
                                        class="badge {{ $item->jenis_kelamin === 'L' ? 'badge-primary' : 'badge-accent' }}">
                                        {{ $item->jenis_kelamin }}
                                    </span>
                                </td>
                                <td style="padding: 12px 16px; font-size: var(--text-base, 0.875rem);">
                                    {{ $item->rombel_nama ?: '-' }}
                                </td>
                                <td style="padding: 12px 16px; text-align: center;">
                                    @php
                                        $badgeColor = match ($item->status_klaper) {
                                            'aktif' => 'badge-success',
                                            'lulus' => 'badge-primary',
                                            'mutasi_keluar' => 'badge-danger',
                                            default => 'badge-outline',
                                        };
                                    @endphp
                                    <span
                                        class="badge {{ $badgeColor }}">{{ strtoupper($item->status_klaper ?: 'AKTIF') }}</span>
                                </td>
                                <td style="padding: 12px 16px; text-align: center;">
                                    <div class="table-actions" style="display: flex; gap: 4px; justify-content: center;">
                                        @if ($canUpdate)
                                            <button type="button" class="btn-icon btn-edit-klaper"
                                                data-id="{{ $item->klaper_id }}" data-nama="{{ $item->nama }}"
                                                data-nomor-klaper="{{ $item->nomor_klaper }}"
                                                data-nomor-induk="{{ $item->nomor_induk }}"
                                                data-tahun-masuk="{{ $item->tahun_masuk }}"
                                                data-status="{{ $item->status_klaper }}" title="Edit Nomor Klaper">
                                                <i class="fas fa-pen-to-square"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7"
                                    style="text-align: center; padding: 40px 16px; color: var(--text-muted);">
                                    <i class="fas fa-folder-open"
                                        style="font-size: var(--text-3xl, 1.875rem); opacity: 0.3; margin-bottom: 10px; display: block;"></i>
                                    Tidak ada data Buku Klaper yang cocok dengan filter pencarian.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- 3. Footer Paginasi Terpadu Datatable -->
            @if ($klaperList->hasPages())
                <div class="datatable-footer">
                    @include('partials.datatable-pagination', ['paginator' => $klaperList])
                </div>
            @endif
        </div>
    @elseif ($activeTab === 'mutasi')
        <!-- TAB 2: MUTASI SISWA (Unified Datatable Baku SAE) -->
        <div class="card datatable-card-wrapper" id="tableDataContainer">
            <!-- 1. Toolbar Header (Entries, Filters & Live Search) -->
            <div class="datatable-toolbar">
                <div
                    style="display: flex; flex-wrap: wrap; gap: 12px; justify-content: space-between; align-items: center;">
                    <div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
                        <div class="toolbar-entries">
                            <label for="perPageSelect" style="margin: 0;">Tampilkan</label>
                            <select id="perPageSelect" name="perPage" class="per-page-select" style="height: 38px;">
                                @foreach ([10, 15, 25, 50, 100] as $n)
                                    <option value="{{ $n }}" {{ ($perPage ?? 25) == $n ? 'selected' : '' }}>
                                        {{ $n }}</option>
                                @endforeach
                            </select>
                            <span>entri</span>
                        </div>

                        @if (!empty($q))
                            <a href="{{ route('dashboard.kesiswaan.administrasi.index', ['tab' => 'mutasi']) }}"
                                class="btn btn-outline btn-reset-filter"
                                style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px);"
                                title="Reset filter" data-live-reset>
                                <i class="fas fa-undo"></i>
                            </a>
                        @endif
                    </div>

                    <div class="live-search-wrap" style="height: 38px;">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" id="liveSearch" name="q"
                            placeholder="Cari siswa / no. surat / sekolah..." value="{{ $q ?? '' }}"
                            autocomplete="off">
                        <button type="button" id="clearSearch" class="clear-search {{ !empty($q) ? 'visible' : '' }}"
                            title="Hapus pencarian">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- 2. Konten Datatable Responsive Stack -->
            <div class="table-responsive-stack" style="overflow-x: auto; width: 100%;">
                <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                    <thead>
                        <tr style="background: rgba(0,0,0,0.02); border-bottom: 1px solid var(--border-color);">
                            <th
                                style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 160px;">
                                No. Surat & Tanggal</th>
                            <th
                                style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Siswa & Rombel</th>
                            <th
                                style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 140px; text-align: center;">
                                Jenis / Alasan</th>
                            <th
                                style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Sekolah Tujuan / Asal</th>
                            <th
                                style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 170px; text-align: center;">
                                Berkas Dokumen</th>
                            <th
                                style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 130px; text-align: center;">
                                Status Alur</th>
                            <th
                                style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 110px; text-align: center;">
                                Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($mutasiList as $item)
                            @php
                                $s = $item->siswa;
                                $namaPd = $s?->nama ?? ($item->nama_siswa ?: 'Peserta Didik');
                                $nisnPd = $s?->nisn ?? ($item->nisn_siswa ?: '-');
                                $nipdPd = $s?->nipd ?? ($item->nipd_siswa ?: '-');
                                $rombelPd = $s?->nama_rombel ?? ($item->rombel_terakhir ?: '-');
                            @endphp
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <td style="padding: 12px 16px;">
                                    <div style="font-family: monospace; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--primary);">
                                        {{ $item->nomor_surat_mutasi ?: '-' }}
                                    </div>
                                    <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-top: 2px;">
                                        <i class="far fa-calendar-alt"></i> {{ date('d/m/Y', strtotime($item->tanggal_mutasi)) }}
                                    </div>
                                </td>
                                <td style="padding: 12px 16px;">
                                    <div style="font-weight: 700; color: var(--text-color); font-size: var(--text-base, 0.875rem);">
                                        {{ $namaPd }}
                                    </div>
                                    <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-top: 2px;">
                                        NISN: {{ $nisnPd }} &bull; Rombel: <span class="badge badge-secondary" style="font-size: 10px; padding: 2px 6px;">{{ $rombelPd }}</span>
                                    </div>
                                </td>
                                <td style="padding: 12px 16px; text-align: center;">
                                    <span class="badge {{ in_array($item->jenis_mutasi, ['Mutasi', 'masuk']) ? 'badge-primary' : (in_array($item->jenis_mutasi, ['Dikeluarkan', 'Putus Sekolah', 'Wafat']) ? 'badge-danger' : 'badge-warning') }}" style="text-transform: capitalize;">
                                        {{ $item->jenis_mutasi }}
                                    </span>
                                    @if ($item->alasan)
                                        <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-top: 4px; max-width: 140px; margin-inline: auto; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $item->alasan }}">
                                            {{ $item->alasan }}
                                        </div>
                                    @endif
                                </td>
                                <td style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem);">
                                    <div style="font-weight: 600; color: var(--text-color);">{{ $item->sekolah_tujuan_asal ?: '-' }}</div>
                                    @if ($item->nomor_surat_dapodik)
                                        <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-top: 2px; font-family: monospace;">
                                            Dapodik: {{ $item->nomor_surat_dapodik }}
                                        </div>
                                    @endif
                                </td>
                                <td style="padding: 12px 16px; text-align: center;">
                                    <div style="display: flex; flex-direction: column; gap: 4px; align-items: center;">
                                        {{-- Surat 1: Permohonan / Penerimaan --}}
                                        @if ($item->file_surat_penerimaan || $item->file_berkas)
                                            <button type="button" class="btn-review-berkas"
                                                    data-id="{{ $item->id }}"
                                                    data-jenis="penerimaan"
                                                    data-nama="{{ $namaPd }}"
                                                    data-judul="Surat Penerimaan / Permohonan (Surat 1)"
                                                    data-nosurat="{{ $item->nomor_surat_penerimaan ?: '-' }}"
                                                    data-url="{{ route('dashboard.kesiswaan.administrasi.mutasi.berkas', ['id' => $item->id, 'jenis' => 'penerimaan']) }}"
                                                    style="background: rgba(59,130,246,0.1); color: var(--primary); font-size: 11px; padding: 3px 8px; border-radius: 4px; border: 1px solid rgba(59,130,246,0.25); cursor: pointer; display: inline-flex; align-items: center; gap: 4px;"
                                                    title="Klik untuk tinjau dokumen PDF & ganti berkas">
                                                <i class="fas fa-file-pdf"></i> S-1 Penerimaan
                                            </button>
                                        @else
                                            <span style="font-size: 11px; color: var(--text-muted);"><i class="fas fa-minus"></i> S-1 Kosong</span>
                                        @endif

                                        {{-- Surat 2: Surat Mutasi Dapodik --}}
                                        @if ($item->file_surat_mutasi)
                                            <button type="button" class="btn-review-berkas"
                                                    data-id="{{ $item->id }}"
                                                    data-jenis="dapodik"
                                                    data-nama="{{ $namaPd }}"
                                                    data-judul="Surat Mutasi Resmi Dapodik (Surat 2)"
                                                    data-nosurat="{{ $item->nomor_surat_dapodik ?: ($item->nomor_surat_mutasi ?: '-') }}"
                                                    data-url="{{ route('dashboard.kesiswaan.administrasi.mutasi.berkas', ['id' => $item->id, 'jenis' => 'dapodik']) }}"
                                                    style="background: rgba(16,185,129,0.1); color: var(--success); font-size: 11px; padding: 3px 8px; border-radius: 4px; border: 1px solid rgba(16,185,129,0.25); cursor: pointer; display: inline-flex; align-items: center; gap: 4px;"
                                                    title="Klik untuk tinjau dokumen PDF & ganti berkas">
                                                <i class="fas fa-file-circle-check"></i> S-2 Dapodik
                                            </button>
                                        @else
                                            <button type="button" class="btn-upload-dapodik"
                                                    data-id="{{ $item->id }}"
                                                    data-nama="{{ $namaPd }}"
                                                    data-nosurat="{{ $item->nomor_surat_penerimaan ?: '-' }}"
                                                    data-suggested-no="{{ $item->nomor_surat_mutasi ?: ($nextNomorSuratKeluar ?? '') }}"
                                                    style="background: rgba(245,158,11,0.12); color: #d97706; border: 1px dashed #d97706; font-size: 11px; padding: 3px 8px; border-radius: 4px; cursor: pointer; display: inline-flex; align-items: center; gap: 4px;">
                                                <i class="fas fa-upload"></i> Unggah S-2
                                            </button>
                                        @endif
                                    </div>
                                </td>
                                <td style="padding: 12px 16px; text-align: center;">
                                    @if ($item->status_proses === 'selesai' || (!empty($item->file_surat_mutasi) && $item->dapodik_synced_at))
                                        <span class="badge badge-success" style="font-size: 11px; padding: 4px 8px;" title="Tersinkron Feeder dan Berkas Arsip Lengkap">
                                            <i class="fas fa-check-double"></i> Selesai
                                        </span>
                                    @elseif ($item->status_proses === 'tersinkron_dapodik' || $item->dapodik_synced_at)
                                        <span class="badge badge-info" style="font-size: 11px; padding: 4px 8px;" title="Sudah sinkron Feeder ke Tidak Aktif, menunggu upload surat Dapodik">
                                            <i class="fas fa-cloud-arrow-down"></i> Di Feeder
                                        </span>
                                    @else
                                        <span class="badge badge-warning" style="font-size: 11px; padding: 4px 8px;" title="Tahap 1 dicatat, menunggu operator mutasi Dapodik & sinkron Feeder">
                                            <i class="fas fa-clock"></i> Tunggu Dapodik
                                        </span>
                                    @endif
                                </td>
                                <td style="padding: 12px 16px; text-align: center;">
                                    <div class="table-actions" style="display: flex; gap: 4px; justify-content: center;">
                                        @if (!empty($item->file_surat_mutasi))
                                            <a href="{{ route('dashboard.kesiswaan.administrasi.mutasi.cetak', $item->id) }}"
                                                target="_blank" class="btn-icon" title="Cetak / Buka Surat Mutasi Dapodik (S-2)">
                                                <i class="fas fa-print"></i>
                                            </a>
                                        @else
                                            <button type="button" class="btn-icon" disabled
                                                    title="Aksi cetak aktif setelah surat mutasi Dapodik (Surat 2) selesai diunggah"
                                                    style="opacity: 0.35; cursor: not-allowed; border: none; background: transparent; color: var(--text-muted);">
                                                <i class="fas fa-print"></i>
                                            </button>
                                        @endif

                                        {{-- Tombol Update / Edit Mutasi (Termasuk S-1 & S-2) --}}
                                        @if ($canUpdate)
                                            <button type="button" class="btn-icon btn-edit-mutasi"
                                                    data-id="{{ $item->id }}"
                                                    data-nama="{{ $namaPd }}"
                                                    data-nisn="{{ $nisnPd }}"
                                                    data-rombel="{{ $rombelPd }}"
                                                    data-jenis="{{ $item->jenis_mutasi }}"
                                                    data-tanggal="{{ date('Y-m-d', strtotime($item->tanggal_mutasi)) }}"
                                                    data-nopenerimaan="{{ $item->nomor_surat_penerimaan }}"
                                                    data-sekolah="{{ $item->sekolah_tujuan_asal }}"
                                                    data-alasan="{{ $item->alasan }}"
                                                    data-catatan="{{ $item->catatan }}"
                                                    data-s1="{{ $item->file_surat_penerimaan || $item->file_berkas ? '1' : '0' }}"
                                                    data-s2="{{ $item->file_surat_mutasi ? '1' : '0' }}"
                                                    title="Edit Data & Berkas Mutasi (S-1 / S-2)"
                                                    style="border: none; background: transparent; cursor: pointer; color: var(--warning, #f59e0b);">
                                                <i class="fas fa-pen-to-square"></i>
                                            </button>
                                        @endif

                                        {{-- Tombol Hapus Mutasi --}}
                                        @if ($canDelete)
                                            <button type="button" class="btn-icon btn-delete-mutasi"
                                                    data-id="{{ $item->id }}"
                                                    data-nama="{{ $namaPd }}"
                                                    title="Hapus Catatan Mutasi"
                                                    style="border: none; background: transparent; cursor: pointer; color: var(--danger, #ef4444);">
                                                <i class="fas fa-trash-can"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7"
                                    style="text-align: center; padding: 40px 16px; color: var(--text-muted);">
                                    <i class="fas fa-folder-open"
                                        style="font-size: var(--text-3xl, 1.875rem); opacity: 0.3; margin-bottom: 10px; display: block;"></i>
                                    Belum ada catatan mutasi / siswa keluar tercatat.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- 3. Footer Paginasi Terpadu Datatable -->
            @if ($mutasiList->hasPages())
                <div class="datatable-footer">
                    @include('partials.datatable-pagination', ['paginator' => $mutasiList])
                </div>
            @endif
        </div>
    @elseif ($activeTab === 'kelulusan')
        <!-- TAB 3: KELULUSAN & SKL (Unified Datatable Baku SAE) -->
        <div class="card datatable-card-wrapper" id="tableDataContainer">
            <!-- 1. Toolbar Header (Entries, Filters & Live Search) -->
            <div class="datatable-toolbar">
                <div
                    style="display: flex; flex-wrap: wrap; gap: 12px; justify-content: space-between; align-items: center;">
                    <div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
                        <div class="toolbar-entries">
                            <label for="perPageSelect" style="margin: 0;">Tampilkan</label>
                            <select id="perPageSelect" name="perPage" class="per-page-select" style="height: 38px;">
                                @foreach ([10, 15, 25, 50, 100] as $n)
                                    <option value="{{ $n }}" {{ ($perPage ?? 25) == $n ? 'selected' : '' }}>
                                        {{ $n }}</option>
                                @endforeach
                            </select>
                            <span>entri</span>
                        </div>

                        @if (!empty($q) || !empty($tahunAjaran))
                            <a href="{{ route('dashboard.kesiswaan.administrasi.index', ['tab' => 'kelulusan']) }}"
                                class="btn btn-outline btn-reset-filter"
                                style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px);"
                                title="Reset filter" data-live-reset>
                                <i class="fas fa-undo"></i>
                            </a>
                        @endif
                    </div>

                    <div class="live-search-wrap" style="height: 38px;">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" id="liveSearch" name="q" placeholder="Cari nama / NISN / no. SKL..."
                            value="{{ $q ?? '' }}" autocomplete="off">
                        <button type="button" id="clearSearch" class="clear-search {{ !empty($q) ? 'visible' : '' }}"
                            title="Hapus pencarian">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- 2. Konten Datatable Responsive Stack -->
            <div class="table-responsive-stack" style="overflow-x: auto; width: 100%;">
                <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                    <thead>
                        <tr style="background: rgba(0,0,0,0.02); border-bottom: 1px solid var(--border-color);">
                            <th
                                style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 170px;">
                                No. SKL</th>
                            <th
                                style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Nama Siswa</th>
                            <th
                                style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 130px;">
                                Tahun Ajaran</th>
                            <th
                                style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                No. Ujian / Ijazah</th>
                            <th
                                style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 120px; text-align: center;">
                                Status</th>
                            <th
                                style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 100px; text-align: center;">
                                Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($kelulusanList as $item)
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <td
                                    style="padding: 12px 16px; font-family: monospace; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--primary);">
                                    {{ $item->nomor_skl ?: '-' }}
                                </td>
                                <td style="padding: 12px 16px; font-weight: 700; color: var(--text-color);">
                                    {{ $item->siswa?->nama ?: 'Siswa #' . $item->peserta_didik_id }}
                                    <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted);">NISN:
                                        {{ $item->siswa?->nisn ?: '-' }}</div>
                                </td>
                                <td style="padding: 12px 16px; font-size: var(--text-base, 0.875rem);">
                                    {{ $item->tahun_ajaran }}
                                </td>
                                <td style="padding: 12px 16px; font-family: monospace; font-size: var(--text-sm, 0.8125rem);">
                                    {{ $item->nomor_peserta_ujian ?: '-' }} / {{ $item->nomor_ijazah ?: '-' }}
                                </td>
                                <td style="padding: 12px 16px; text-align: center;">
                                    <span
                                        class="badge {{ $item->status_kelulusan === 'lulus' ? 'badge-success' : 'badge-danger' }}">
                                        {{ strtoupper($item->status_kelulusan) }}
                                    </span>
                                </td>
                                <td style="padding: 12px 16px; text-align: center;">
                                    <div class="table-actions" style="display: flex; gap: 4px; justify-content: center;">
                                        <a href="{{ route('dashboard.kesiswaan.administrasi.kelulusan.cetak-skl', $item->id) }}"
                                            target="_blank" class="btn-icon" title="Cetak Surat Keterangan Lulus (SKL)">
                                            <i class="fas fa-print"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6"
                                    style="text-align: center; padding: 40px 16px; color: var(--text-muted);">
                                    <i class="fas fa-folder-open"
                                        style="font-size: var(--text-3xl, 1.875rem); opacity: 0.3; margin-bottom: 10px; display: block;"></i>
                                    Tidak ada data penetapan kelulusan siswa yang cocok dengan filter pencarian.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- 3. Footer Paginasi Terpadu Datatable -->
            @if ($kelulusanList->hasPages())
                <div class="datatable-footer">
                    @include('partials.datatable-pagination', ['paginator' => $kelulusanList])
                </div>
            @endif
        </div>
    @endif

    <!-- MODAL 1: CATAT MUTASI SISWA -->
    <div id="modalMutasi" class="modal-backdrop"
        style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.65); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(5px);">
        <div class="card"
            style="max-width: 520px; width: 92%; max-height: 90vh; display: flex; flex-direction: column; margin: 0; border-radius: var(--radius-xl, 16px); padding: 22px;">
            <div
                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 1px solid var(--border-color);">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div
                        style="width: 36px; height: 36px; border-radius: var(--radius-md, 8px); background: rgba(239,68,68,0.15); display: flex; align-items: center; justify-content: center; color: var(--danger, #ef4444);">
                        <i class="fas fa-person-walking-dashed-line-arrow-right"></i>
                    </div>
                    <div>
                        <h3 style="font-size: var(--text-lg, 1.125rem); font-weight: 700; color: var(--text-color); margin: 0;">Catat Mutasi
                            Siswa</h3>
                    </div>
                </div>
                <button type="button" class="close-modal" data-target="#modalMutasi"
                    style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: var(--text-lg, 1.125rem); padding: 4px; display: inline-flex; align-items: center; justify-content: center; transition: color 0.2s ease;" aria-label="Tutup">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div style="overflow-y: auto; flex: 1; padding-right: 4px;">
                <form id="formMutasi" method="POST" enctype="multipart/form-data"
                    action="{{ route('dashboard.kesiswaan.administrasi.mutasi.store') }}">
                    @csrf
                    <div class="form-group" style="margin-bottom: 14px;">
                        <label
                            style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">Pilih
                            Peserta Didik <span class="text-danger">*</span></label>
                        <input type="search" id="mutasiSiswaLookup" class="form-control"
                            placeholder="Cari nama, NISN, atau NIPD..." autocomplete="off"
                            data-people-search="{{ route('dashboard.people.search') }}" data-people-kind="siswa"
                            data-people-target="#mutasiSiswaId" data-people-require-selection="true" required
                            style="width: 100%; border-radius: var(--radius-md, 8px);">
                        <input type="hidden" id="mutasiSiswaId" name="peserta_didik_id">
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                        <div class="form-group">
                            <label
                                style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">Jenis
                                Mutasi/Keluar <span class="text-danger">*</span></label>
                            <select name="jenis_mutasi" class="form-control" required
                                style="width: 100%; border-radius: var(--radius-md, 8px);">
                                <option value="Mutasi">Mutasi (Pindah Sekolah)</option>
                                <option value="Dikeluarkan">Dikeluarkan</option>
                                <option value="Mengundurkan diri">Mengundurkan diri</option>
                                <option value="Putus Sekolah">Putus Sekolah</option>
                                <option value="Wafat">Wafat</option>
                                <option value="Hilang">Hilang</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label
                                style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">Tanggal
                                Mutasi <span class="text-danger">*</span></label>
                            <input type="date" name="tanggal_mutasi" class="form-control"
                                value="{{ date('Y-m-d') }}" required style="width: 100%; border-radius: var(--radius-md, 8px);">
                        </div>
                    </div>
                    <div class="form-group" style="margin-bottom: 14px;">
                        <label
                            style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">
                            Nomor Surat Permohonan / Sekolah Tujuan <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="nomor_surat_penerimaan" class="form-control"
                            placeholder="Contoh: 421.5/089/SMKN2-BDG/2026 atau No. Permohonan Ortu" required
                            style="width: 100%; border-radius: var(--radius-md, 8px);">
                        <p style="margin: 4px 0 0 0; font-size: 11px; color: var(--text-muted);">
                            <i class="fas fa-info-circle"></i> Nomor ini otomatis dicatat sebagai nomor surat pada modul <strong>Surat Masuk</strong> persuratan.
                        </p>
                    </div>
                    <div class="form-group" style="margin-bottom: 14px;">
                        <label
                            style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">Sekolah
                            Tujuan / Instansi Asal Pengirim</label>
                        <input type="text" name="sekolah_tujuan_asal" class="form-control"
                            placeholder="Contoh: SMKN 1 Pagelaran / Orang Tua Siswa" style="width: 100%; border-radius: var(--radius-md, 8px);">
                    </div>
                    <div class="form-group" style="margin-bottom: 14px;">
                        <label
                            style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">Alasan
                            Mutasi / Keluar <span class="text-danger">*</span></label>
                        <input type="text" name="alasan" class="form-control"
                            placeholder="Contoh: Mengikuti domisili orang tua / pindah tugas kerja" required
                            style="width: 100%; border-radius: var(--radius-md, 8px);">
                    </div>
                    <div class="form-group" style="margin-bottom: 18px;">
                        <label
                            style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); margin-bottom: 4px;">
                            Berkas Surat Bersedia Menerima / Permohonan (Wajib PDF) <span class="text-danger">*</span>
                        </label>
                        <p style="margin: 0 0 6px 0; font-size: 11px; color: var(--text-muted);">
                            Hanya berkas format PDF (maksimal 5 MB). Berkas otomatis diarsipkan ke sistem persuratan.
                        </p>
                        <input type="file" name="file_surat_penerimaan" class="form-control" accept="application/pdf,.pdf" required
                            style="width: 100%; border-radius: var(--radius-md, 8px); padding: 6px 10px;">
                    </div>
                    <div
                        style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 18px; border-top: 1px solid var(--border-color); padding-top: 14px;">
                        <button type="button" class="btn btn-outline close-modal" data-target="#modalMutasi"
                            style="padding: 8px 16px; font-size: var(--text-sm, 0.8125rem);">Batal</button>
                        <button type="submit" class="btn btn-primary" style="padding: 8px 18px; font-size: var(--text-sm, 0.8125rem);"><i
                                class="fas fa-save me-1"></i> Simpan Mutasi (Tahap 1)</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL 2: CATAT KELULUSAN SISWA -->
    <div id="modalKelulusan" class="modal-backdrop"
        style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.65); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(5px);">
        <div class="card"
            style="max-width: 520px; width: 92%; max-height: 90vh; display: flex; flex-direction: column; margin: 0; border-radius: var(--radius-xl, 16px); padding: 22px;">
            <div
                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 1px solid var(--border-color);">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div
                        style="width: 36px; height: 36px; border-radius: var(--radius-md, 8px); background: rgba(245,158,11,0.15); display: flex; align-items: center; justify-content: center; color: var(--warning, #f59e0b);">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                    <div>
                        <h3 style="font-size: var(--text-lg, 1.125rem); font-weight: 700; color: var(--text-color); margin: 0;">Penetapan
                            Kelulusan &amp; SKL</h3>
                    </div>
                </div>
                <button type="button" class="close-modal" data-target="#modalKelulusan"
                    style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: var(--text-lg, 1.125rem); padding: 4px; display: inline-flex; align-items: center; justify-content: center; transition: color 0.2s ease;" aria-label="Tutup">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div style="overflow-y: auto; flex: 1; padding-right: 4px;">
                <form id="formKelulusan" method="POST"
                    action="{{ route('dashboard.kesiswaan.administrasi.kelulusan.store') }}">
                    @csrf
                    <div class="form-group" style="margin-bottom: 14px;">
                        <label
                            style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">Pilih
                            Peserta Didik <span class="text-danger">*</span></label>
                        <input type="search" id="kelulusanSiswaLookup" class="form-control"
                            placeholder="Cari nama, NISN, atau NIPD..." autocomplete="off"
                            data-people-search="{{ route('dashboard.people.search') }}" data-people-kind="siswa"
                            data-people-target="#kelulusanSiswaId" data-people-require-selection="true" required
                            style="width: 100%; border-radius: var(--radius-md, 8px);">
                        <input type="hidden" id="kelulusanSiswaId" name="peserta_didik_id">
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                        <div class="form-group">
                            <label
                                style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">Tahun
                                Ajaran <span class="text-danger">*</span></label>
                            <input type="text" name="tahun_ajaran" class="form-control"
                                value="{{ date('n') >= 7 ? date('Y') . '/' . (date('Y') + 1) : date('Y') - 1 . '/' . date('Y') }}"
                                required style="width: 100%; border-radius: var(--radius-md, 8px);">
                        </div>
                        <div class="form-group">
                            <label
                                style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">Status
                                Kelulusan <span class="text-danger">*</span></label>
                            <select name="status_kelulusan" class="form-control" required
                                style="width: 100%; border-radius: var(--radius-md, 8px);">
                                <option value="lulus">Lulus</option>
                                <option value="tidak_lulus">Tidak Lulus</option>
                                <option value="ditunda">Ditunda</option>
                            </select>
                        </div>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 18px;">
                        <div class="form-group">
                            <label
                                style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">No.
                                Peserta Ujian</label>
                            <input type="text" name="nomor_peserta_ujian" class="form-control"
                                placeholder="Contoh: 04-001-001-8" style="width: 100%; border-radius: var(--radius-md, 8px);">
                        </div>
                        <div class="form-group">
                            <label
                                style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">No.
                                Ijazah Nasional</label>
                            <input type="text" name="nomor_ijazah" class="form-control"
                                placeholder="Contoh: M-SMK/06-000123" style="width: 100%; border-radius: var(--radius-md, 8px);">
                        </div>
                    </div>
                    <div
                        style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 18px; border-top: 1px solid var(--border-color); padding-top: 14px;">
                        <button type="button" class="btn btn-outline close-modal" data-target="#modalKelulusan"
                            style="padding: 8px 16px; font-size: var(--text-sm, 0.8125rem);">Batal</button>
                        <button type="submit" class="btn btn-primary" style="padding: 8px 18px; font-size: var(--text-sm, 0.8125rem);"><i
                                class="fas fa-save me-1"></i> Simpan Kelulusan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL 3: UNGGAH SURAT MUTASI DAPODIK (TAHAP 2) -->
    <div id="modalUploadSuratDapodik" class="modal-backdrop"
        style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.65); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(5px);">
        <div class="card"
            style="max-width: 500px; width: 92%; max-height: 90vh; display: flex; flex-direction: column; margin: 0; border-radius: var(--radius-xl, 16px); padding: 22px;">
            <div
                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 1px solid var(--border-color);">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div
                        style="width: 36px; height: 36px; border-radius: var(--radius-md, 8px); background: rgba(16,185,129,0.15); display: flex; align-items: center; justify-content: center; color: var(--success, #10b981);">
                        <i class="fas fa-file-circle-check"></i>
                    </div>
                    <div>
                        <h3 style="font-size: var(--text-lg, 1.125rem); font-weight: 700; color: var(--text-color); margin: 0;">Unggah Surat Mutasi Dapodik</h3>
                        <div style="font-size: 11px; color: var(--text-muted); margin-top: 2px;">Tahap 2: Arsip resmi setelah mutasi disetujui & disinkronkan</div>
                    </div>
                </div>
                <button type="button" class="close-modal" data-target="#modalUploadSuratDapodik"
                    style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: var(--text-lg, 1.125rem); padding: 4px; display: inline-flex; align-items: center; justify-content: center; transition: color 0.2s ease;" aria-label="Tutup">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div style="overflow-y: auto; flex: 1; padding-right: 4px;">
                <form id="formUploadSuratDapodik" method="POST" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" id="uploadMutasiId" name="mutasi_id">

                    <div style="background: rgba(59,130,246,0.06); border: 1px solid rgba(59,130,246,0.15); border-radius: var(--radius-md, 8px); padding: 12px; margin-bottom: 14px;">
                        <div style="font-size: 11px; text-transform: uppercase; font-weight: 700; color: var(--text-muted);">Siswa Mutasi / Keluar</div>
                        <div id="uploadMutasiSiswaNama" style="font-size: var(--text-base, 0.875rem); font-weight: 700; color: var(--text-color); margin-top: 2px;">-</div>
                        <div id="uploadMutasiNoSurat" style="font-size: 11px; font-family: monospace; color: var(--primary); margin-top: 2px;">-</div>
                    </div>

                    <div class="form-group" style="margin-bottom: 14px;">
                        <label style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">
                            Nomor Surat Mutasi Dapodik / Registrasi Keluar <span class="badge badge-secondary" style="font-size: 10px; font-weight: 600; padding: 2px 6px;">Otomatis Modul Persuratan</span>
                        </label>
                        <input type="text" id="uploadMutasiNoDapodik" name="nomor_surat_dapodik" class="form-control"
                            readonly
                            style="width: 100%; border-radius: var(--radius-md, 8px); background-color: var(--bg-hover, rgba(0,0,0,0.04)); cursor: not-allowed; font-family: monospace; font-weight: 700; color: var(--primary);">
                        <p style="margin: 4px 0 0 0; font-size: 11px; color: var(--text-muted);">
                            <i class="fas fa-lock"></i> Terbaca otomatis dari antrean nomor Surat Keluar yang belum digunakan.
                        </p>
                    </div>

                    <div class="form-group" style="margin-bottom: 18px;">
                        <label style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); margin-bottom: 4px;">
                            Berkas Surat Mutasi Dapodik (Wajib PDF) <span class="text-danger">*</span>
                        </label>
                        <p style="margin: 0 0 6px 0; font-size: 11px; color: var(--text-muted);">
                            Hanya format PDF (maks. 5 MB). Berkas otomatis tercatat di <strong>Surat Keluar</strong> persuratan.
                        </p>
                        <input type="file" name="file_surat_mutasi" class="form-control" accept="application/pdf,.pdf" required
                            style="width: 100%; border-radius: var(--radius-md, 8px); padding: 6px 10px;">
                    </div>

                    <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 18px; border-top: 1px solid var(--border-color); padding-top: 14px;">
                        <button type="button" class="btn btn-outline close-modal" data-target="#modalUploadSuratDapodik"
                            style="padding: 8px 16px; font-size: var(--text-sm, 0.8125rem);">Batal</button>
                        <button type="submit" class="btn btn-primary" style="padding: 8px 18px; font-size: var(--text-sm, 0.8125rem); background: var(--success); border-color: var(--success);">
                            <i class="fas fa-upload me-1"></i> Simpan Arsip Dapodik
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL 4: REVIEW & UPDATE DOKUMEN MUTASI LANGSUNG (SURAT 1 ATAU SURAT 2) -->
    <div id="modalReviewDokumenMutasi" class="modal-backdrop"
        style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.65); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(5px);">
        <div class="card"
            style="max-width: 820px; width: 94%; height: 90vh; display: flex; flex-direction: column; margin: 0; border-radius: var(--radius-xl, 16px); padding: 20px;">
            <div
                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; padding-bottom: 10px; border-bottom: 1px solid var(--border-color);">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div
                        style="width: 36px; height: 36px; border-radius: var(--radius-md, 8px); background: rgba(59,130,246,0.15); display: flex; align-items: center; justify-content: center; color: var(--primary);">
                        <i class="fas fa-file-pdf"></i>
                    </div>
                    <div>
                        <h3 id="reviewDokumenJudul" style="font-size: var(--text-base, 1rem); font-weight: 700; color: var(--text-color); margin: 0;">Pratinjau Dokumen</h3>
                        <div id="reviewDokumenSub" style="font-size: 11px; color: var(--text-muted); margin-top: 2px;">-</div>
                    </div>
                </div>
                <div style="display: flex; gap: 8px; align-items: center;">
                    <a id="reviewDokumenDownloadBtn" href="#" target="_blank" class="btn btn-outline"
                       style="padding: 5px 10px; font-size: 12px; display: inline-flex; align-items: center; gap: 4px;">
                        <i class="fas fa-external-link-alt"></i> Tab Baru
                    </a>
                    <button type="button" class="close-modal" data-target="#modalReviewDokumenMutasi"
                        style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: var(--text-lg, 1.125rem); padding: 4px; display: inline-flex; align-items: center; justify-content: center;" aria-label="Tutup">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>

            <!-- PDF Viewer Frame -->
            <div style="flex: 1; min-height: 250px; background: #2d3748; border-radius: var(--radius-md, 8px); overflow: hidden; margin-bottom: 12px; position: relative;">
                <iframe id="reviewDokumenIframe" src="about:blank" style="width: 100%; height: 100%; border: none;"></iframe>
            </div>

            <!-- Form Ganti / Perbarui Berkas Dokumen -->
            <form id="formUpdateBerkasMutasi" method="POST" enctype="multipart/form-data"
                  style="border-top: 1px solid var(--border-color); padding-top: 12px;">
                @csrf
                <div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center; justify-content: space-between;">
                    <div style="flex: 1; min-width: 250px;">
                        <label style="display: block; font-size: 11px; font-weight: 700; color: var(--text-muted); margin-bottom: 4px;">
                            Ganti / Unggah Ulang Dokumen (Hanya PDF, maks 5 MB):
                        </label>
                        <input type="file" id="reviewDokumenFileInput" name="file_dokumen" class="form-control" accept="application/pdf,.pdf" required
                               style="width: 100%; font-size: 12px; padding: 5px 8px; border-radius: var(--radius-md, 8px);">
                    </div>
                    <div style="display: flex; gap: 8px; align-items: flex-end;">
                        <button type="submit" class="btn btn-primary" style="padding: 7px 16px; font-size: 12px; white-space: nowrap;">
                            <i class="fas fa-sync-alt me-1"></i> Perbarui Dokumen
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <!-- MODAL 5: EDIT DATA MUTASI & BERKAS (S-1 / S-2) -->
    <div id="modalEditMutasi" class="modal-backdrop"
        style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.65); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(5px);">
        <div class="card"
            style="max-width: 560px; width: 92%; max-height: 90vh; display: flex; flex-direction: column; margin: 0; border-radius: var(--radius-xl, 16px); padding: 22px;">
            <div
                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 1px solid var(--border-color);">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div
                        style="width: 36px; height: 36px; border-radius: var(--radius-md, 8px); background: rgba(245,158,11,0.15); display: flex; align-items: center; justify-content: center; color: var(--warning, #f59e0b);">
                        <i class="fas fa-pen-to-square"></i>
                    </div>
                    <div>
                        <h3 style="font-size: var(--text-lg, 1.125rem); font-weight: 700; color: var(--text-color); margin: 0;">Ubah Data Mutasi</h3>
                        <div id="editMutasiSiswaSub" style="font-size: 11px; color: var(--text-muted); margin-top: 2px;">-</div>
                    </div>
                </div>
                <button type="button" class="close-modal" data-target="#modalEditMutasi"
                    style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: var(--text-lg, 1.125rem); padding: 4px; display: inline-flex; align-items: center; justify-content: center;" aria-label="Tutup">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div style="overflow-y: auto; flex: 1; padding-right: 4px;">
                <form id="formEditMutasi" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <input type="hidden" id="editMutasiId" name="mutasi_id">

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                        <div class="form-group">
                            <label style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">
                                Jenis Mutasi/Keluar <span class="text-danger">*</span>
                            </label>
                            <select id="editMutasiJenis" name="jenis_mutasi" class="form-control" required style="width: 100%; border-radius: var(--radius-md, 8px);">
                                <option value="Mutasi">Mutasi (Pindah Sekolah)</option>
                                <option value="Dikeluarkan">Dikeluarkan</option>
                                <option value="Mengundurkan diri">Mengundurkan diri</option>
                                <option value="Putus Sekolah">Putus Sekolah</option>
                                <option value="Wafat">Wafat</option>
                                <option value="Hilang">Hilang</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">
                                Tanggal Mutasi <span class="text-danger">*</span>
                            </label>
                            <input type="date" id="editMutasiTanggal" name="tanggal_mutasi" class="form-control" required style="width: 100%; border-radius: var(--radius-md, 8px);">
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: 14px;">
                        <label style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">
                            Nomor Surat Permohonan / Sekolah Tujuan <span class="text-danger">*</span>
                        </label>
                        <input type="text" id="editMutasiNoPenerimaan" name="nomor_surat_penerimaan" class="form-control" required style="width: 100%; border-radius: var(--radius-md, 8px);">
                    </div>

                    <div class="form-group" style="margin-bottom: 14px;">
                        <label style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">
                            Sekolah Tujuan / Instansi Asal Pengirim
                        </label>
                        <input type="text" id="editMutasiSekolah" name="sekolah_tujuan_asal" class="form-control" style="width: 100%; border-radius: var(--radius-md, 8px);">
                    </div>

                    <div class="form-group" style="margin-bottom: 14px;">
                        <label style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">
                            Alasan Mutasi / Keluar <span class="text-danger">*</span>
                        </label>
                        <input type="text" id="editMutasiAlasan" name="alasan" class="form-control" required style="width: 100%; border-radius: var(--radius-md, 8px);">
                    </div>

                    <div class="form-group" style="margin-bottom: 14px;">
                        <label style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">
                            Catatan Tambahan
                        </label>
                        <textarea id="editMutasiCatatan" name="catatan" rows="2" class="form-control" style="width: 100%; border-radius: var(--radius-md, 8px);"></textarea>
                    </div>

                    <div style="background: rgba(0,0,0,0.02); border: 1px solid var(--border-color); border-radius: var(--radius-md, 8px); padding: 12px; margin-bottom: 16px;">
                        <div style="font-size: 12px; font-weight: 700; color: var(--text-color); margin-bottom: 10px;">
                            <i class="fas fa-paperclip"></i> Perbarui Lampiran Berkas (Opsional - Hanya PDF)
                        </div>
                        <div class="form-group" style="margin-bottom: 10px;">
                            <label style="display: flex; justify-content: space-between; font-size: 11px; font-weight: 700; color: var(--text-muted); margin-bottom: 4px;">
                                <span>Surat 1 (Penerimaan / Permohonan)</span>
                                <span id="editMutasiS1Status" class="badge badge-secondary" style="font-size: 10px; padding: 1px 5px;">-</span>
                            </label>
                            <input type="file" name="file_surat_penerimaan" class="form-control" accept="application/pdf,.pdf" style="width: 100%; font-size: 12px; padding: 4px 8px; border-radius: var(--radius-md, 8px);">
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="display: flex; justify-content: space-between; font-size: 11px; font-weight: 700; color: var(--text-muted); margin-bottom: 4px;">
                                <span>Surat 2 (Surat Mutasi Dapodik)</span>
                                <span id="editMutasiS2Status" class="badge badge-secondary" style="font-size: 10px; padding: 1px 5px;">-</span>
                            </label>
                            <input type="file" name="file_surat_mutasi" class="form-control" accept="application/pdf,.pdf" style="width: 100%; font-size: 12px; padding: 4px 8px; border-radius: var(--radius-md, 8px);">
                        </div>
                    </div>

                    <div style="display: flex; justify-content: flex-end; gap: 10px; border-top: 1px solid var(--border-color); padding-top: 14px;">
                        <button type="button" class="btn btn-outline close-modal" data-target="#modalEditMutasi" style="padding: 8px 16px; font-size: var(--text-sm, 0.8125rem);">Batal</button>
                        <button type="submit" class="btn btn-primary" style="padding: 8px 18px; font-size: var(--text-sm, 0.8125rem);">
                            <i class="fas fa-save me-1"></i> Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script
        src="{{ asset('js/kesiswaan-administrasi.js') }}?v={{ file_exists(public_path('js/kesiswaan-administrasi.js')) ? filemtime(public_path('js/kesiswaan-administrasi.js')) : time() }}">
    </script>
@endpush
