@extends('layouts.dashboard')

@section('title', 'Kedisiplinan Siswa (Tata Tertib, Poin, Pembinaan & Pemanggilan) — SAE')
@section('dash_title', 'Kedisiplinan Siswa')

@section('content')
    <!-- 1. Header Banner & Actions -->
    <div class="dash-banner"
        style="display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; margin-bottom: 20px;">
        <div style="display: flex; align-items: center; gap: 12px;">
            <div
                style="width: 44px; height: 44px; border-radius: var(--radius-lg, 12px); background: rgba(239,68,68,0.12); color: var(--danger, #ef4444); display: flex; align-items: center; justify-content: center; font-size: var(--text-xl, 1.25rem); flex-shrink: 0;">
                <i class="fas fa-shield-halved"></i>
            </div>
            <div>
                <h2 style="font-size: var(--text-xl, 1.25rem); font-weight: 800; color: var(--text-color); margin: 0;">
                    Kedisiplinan Siswa
                </h2>
                <p style="margin: 2px 0 0 0; font-size: var(--text-sm, 0.8125rem); color: var(--text-muted);">
                    Tata tertib sekolah, buku saku poin pelanggaran, sesi pembinaan konseling, pemanggilan orang tua, dan
                    rekapitulasi.
                </p>
            </div>
        </div>

        <div class="dash-banner-actions" style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
            @if ($canCreate)
                @if ($activeTab === 'tata_tertib')
                    <button type="button" class="btn btn-outline" id="btnOpenImportTatibModal" title="Import Aturan Tata Tertib (Excel/CSV)"
                        style="height: 38px; padding: 0 12px; display: inline-flex; align-items: center; gap: 6px; border-radius: var(--radius-md, 8px);">
                        <i class="fas fa-file-excel text-success"></i> <span>Import Excel</span>
                    </button>
                    <button type="button" class="btn btn-primary" id="btnOpenTataTertibModal" title="Tambah Aturan Tata Tertib"
                        style="height: 38px; padding: 0 14px; display: inline-flex; align-items: center; gap: 6px; border-radius: var(--radius-md, 8px);">
                        <i class="fas fa-plus"></i> <span>Tambah Aturan</span>
                    </button>
                @else
                    <button type="button" class="btn btn-outline" id="btnOpenTataTertibModal" title="Tambah Aturan Tata Tertib"
                        style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px);">
                        <i class="fas fa-book-bookmark text-primary"></i>
                    </button>
                    <button type="button" class="btn btn-primary" id="btnOpenPoinModal" title="Catat Pelanggaran Poin Siswa"
                        style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px); background: var(--danger, #ef4444); border-color: var(--danger, #ef4444);">
                        <i class="fas fa-triangle-exclamation"></i>
                    </button>
                    <button type="button" class="btn btn-outline" id="btnOpenPembinaanModal"
                        title="Catat Sesi Pembinaan Konseling"
                        style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px);">
                        <i class="fas fa-user-doctor text-success"></i>
                    </button>
                    <button type="button" class="btn btn-outline" id="btnOpenPanggilanModal"
                        title="Terbitkan Surat Panggilan Wali"
                        style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px);">
                        <i class="fas fa-envelope-open-text text-warning"></i>
                    </button>
                @endif
            @endif
        </div>
    </div>

    <!-- 2. Stat Grid Baku SAE -->
    <div class="dash-stat-grid" style="margin-bottom: 20px;">
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(59,130,246,0.12); color: var(--primary-hover, #2563eb);">
                <i class="fas fa-list-check"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ number_format($stats['total_aturan'] ?? 0) }}</div>
                <div class="dash-stat-label">Aturan Tata Tertib</div>
                <div style="display: flex; gap: 6px; font-size: 0.6875rem; font-weight: 700; margin-top: 4px;">
                    <span style="color: #059669;">{{ $stats['total_ringan'] ?? 0 }} Ringan</span>
                    <span style="color: var(--text-muted);">&bull;</span>
                    <span style="color: #d97706;">{{ $stats['total_sedang'] ?? 0 }} Sedang</span>
                    <span style="color: var(--text-muted);">&bull;</span>
                    <span style="color: #dc2626;">{{ $stats['total_berat'] ?? 0 }} Berat</span>
                </div>
            </div>
        </div>
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(239,68,68,0.12); color: var(--danger, #ef4444);">
                <i class="fas fa-triangle-exclamation"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ number_format($stats['total_pelanggaran'] ?? 0) }}</div>
                <div class="dash-stat-label">Kasus Pelanggaran</div>
            </div>
        </div>
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(16,185,129,0.12); color: var(--success, #10b981);">
                <i class="fas fa-user-doctor"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ number_format($stats['total_pembinaan'] ?? 0) }}</div>
                <div class="dash-stat-label">Sesi Pembinaan</div>
            </div>
        </div>
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(245,158,11,0.12); color: var(--warning, #f59e0b);">
                <i class="fas fa-envelope-open-text"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ number_format($stats['total_panggilan'] ?? 0) }}</div>
                <div class="dash-stat-label">Panggilan Ortu</div>
            </div>
        </div>
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(16,185,129,0.12); color: var(--success, #10b981);">
                <i class="fas fa-hand-holding-hand"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ number_format($stats['total_disanggah'] ?? 0) }}</div>
                <div class="dash-stat-label">Sanggah / Bakti</div>
                <div style="font-size: 0.6875rem; font-weight: 700; color: #059669; margin-top: 4px;">
                    -{{ number_format($stats['total_poin_sanggah'] ?? 0) }} Poin Diputihkan
                </div>
            </div>
        </div>
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(168,85,247,0.12); color: #a855f7;">
                <i class="fas fa-clock"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ number_format($stats['total_pending'] ?? 0) }}</div>
                <div class="dash-stat-label">Menunggu Tindak Lanjut</div>
            </div>
        </div>
    </div>

    <!-- 3. Navigation Tabs Wrapper -->
    <div class="periode-nav-wrapper" style="margin-bottom: 20px;">
        <div class="periode-nav-desktop" style="flex-wrap: wrap;">
            <a href="{{ route('dashboard.kesiswaan.kedisiplinan.index', ['tab' => 'rekap']) }}"
                class="periode-nav-tab {{ $activeTab === 'rekap' ? 'active' : '' }}">
                <i class="fas fa-chart-pie"></i> Rekapitulasi &amp; Analisis
            </a>
            <a href="{{ route('dashboard.kesiswaan.kedisiplinan.index', ['tab' => 'poin']) }}"
                class="periode-nav-tab {{ $activeTab === 'poin' ? 'active' : '' }}">
                <i class="fas fa-triangle-exclamation"></i> Poin &amp; Sanggah
            </a>
            <a href="{{ route('dashboard.kesiswaan.kedisiplinan.index', ['tab' => 'riwayat']) }}"
                class="periode-nav-tab {{ $activeTab === 'riwayat' ? 'active' : '' }}">
                <i class="fas fa-clock-rotate-left"></i> Riwayat Pelanggaran
            </a>
            <a href="{{ route('dashboard.kesiswaan.kedisiplinan.index', ['tab' => 'tata_tertib']) }}"
                class="periode-nav-tab {{ $activeTab === 'tata_tertib' ? 'active' : '' }}">
                <i class="fas fa-book-bookmark"></i> Tata Tertib
            </a>
            <a href="{{ route('dashboard.kesiswaan.kedisiplinan.index', ['tab' => 'pembinaan']) }}"
                class="periode-nav-tab {{ $activeTab === 'pembinaan' ? 'active' : '' }}">
                <i class="fas fa-user-doctor"></i> Pembinaan
            </a>
            <a href="{{ route('dashboard.kesiswaan.kedisiplinan.index', ['tab' => 'pemanggilan']) }}"
                class="periode-nav-tab {{ $activeTab === 'pemanggilan' ? 'active' : '' }}">
                <i class="fas fa-envelope-open-text"></i> Pemanggilan Wali
            </a>
            <a href="{{ route('dashboard.kesiswaan.kedisiplinan.index', ['tab' => 'tindak_lanjut']) }}"
                class="periode-nav-tab {{ $activeTab === 'tindak_lanjut' ? 'active' : '' }}">
                <i class="fas fa-scale-balanced"></i> Tindak Lanjut
            </a>
        </div>
    </div>

    <!-- Unified Datatable Kedisiplinan Siswa (Toolbar + Table + Pagination dalam 1 Card Terpadu) -->
    <div class="card datatable-card-wrapper" id="tableDataContainer">
        <!-- 1. Toolbar Header (Entries, Filters & Live Search) -->
        <div class="datatable-toolbar">
            <div style="display: flex; flex-wrap: wrap; gap: 12px; justify-content: space-between; align-items: center;">
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

                    @if ($activeTab === 'riwayat' || $activeTab === 'poin')
                        <select id="filterStatus" name="status" class="toolbar-filter-select" style="height: 38px;">
                            <option value="">Semua Status Tindak Lanjut</option>
                            <option value="pending" {{ ($status ?? '') === 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="proses" {{ ($status ?? '') === 'proses' ? 'selected' : '' }}>Proses</option>
                            <option value="selesai" {{ ($status ?? '') === 'selesai' ? 'selected' : '' }}>Selesai</option>
                        </select>
                    @endif

                    @if ($activeTab === 'tata_tertib')
                        <select id="filterTingkat" name="tingkat" class="toolbar-filter-select" style="height: 38px;">
                            <option value="">Semua Tingkat (Ringan / Sedang / Berat)</option>
                            <option value="ringan" {{ ($tingkat ?? '') === 'ringan' ? 'selected' : '' }}>Ringan</option>
                            <option value="sedang" {{ ($tingkat ?? '') === 'sedang' ? 'selected' : '' }}>Sedang</option>
                            <option value="berat" {{ ($tingkat ?? '') === 'berat' ? 'selected' : '' }}>Berat</option>
                        </select>

                        <select id="filterKategori" name="kategori" class="toolbar-filter-select" style="height: 38px;">
                            <option value="">Semua Kategori</option>
                            <option value="kerapian" {{ ($kategori ?? '') === 'kerapian' ? 'selected' : '' }}>Kerapian</option>
                            <option value="kehadiran" {{ ($kategori ?? '') === 'kehadiran' ? 'selected' : '' }}>Kehadiran</option>
                            <option value="perilaku" {{ ($kategori ?? '') === 'perilaku' ? 'selected' : '' }}>Perilaku</option>
                            <option value="larangan_berat" {{ ($kategori ?? '') === 'larangan_berat' ? 'selected' : '' }}>Larangan Berat</option>
                        </select>
                    @endif

                    @if (!empty($q) || !empty($status) || !empty($kategori) || !empty($tingkat))
                        <a href="{{ route('dashboard.kesiswaan.kedisiplinan.index', ['tab' => $activeTab]) }}"
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
                        placeholder="Cari siswa / nomor / pelanggaran..." value="{{ $q ?? '' }}"
                        autocomplete="off">
                    <button type="button" id="clearSearch" class="clear-search {{ !empty($q) ? 'visible' : '' }}"
                        title="Hapus pencarian">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- 2. Content Sesuai Tab -->
        @if ($activeTab === 'tata_tertib')
            <!-- TAB 1: MASTER TATA TERTIB -->
            <div class="table-responsive-stack" style="overflow-x: auto; width: 100%;">
                <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                    <thead>
                        <tr style="background: rgba(0,0,0,0.02); border-bottom: 1px solid var(--border-color);">
                            <th
                                style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 90px;">
                                Kode</th>
                            <th
                                style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 110px; text-align: center;">
                                Tingkat</th>
                            <th
                                style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 130px;">
                                Kategori</th>
                            <th
                                style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Uraian Tata Tertib &amp; Pelanggaran</th>
                            <th
                                style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 110px;">
                                Bobot Poin</th>
                            <th
                                style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 220px;">
                                Sanksi Rekomendasi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($tataTertibList as $item)
                            @php
                                $tingkat = $item->tingkat;
                                $tingkatStyle = match ($tingkat) {
                                    'ringan' => 'background: rgba(16, 185, 129, 0.12); color: #059669; border: 1px solid rgba(16, 185, 129, 0.25);',
                                    'sedang' => 'background: rgba(245, 158, 11, 0.12); color: #d97706; border: 1px solid rgba(245, 158, 11, 0.25);',
                                    'berat'  => 'background: rgba(239, 68, 68, 0.12); color: #dc2626; border: 1px solid rgba(239, 68, 68, 0.25);',
                                    default  => 'background: rgba(100, 116, 139, 0.12); color: #475569; border: 1px solid rgba(100, 116, 139, 0.25);',
                                };
                                $tingkatIcon = match ($tingkat) {
                                    'ringan' => 'fa-circle-check',
                                    'sedang' => 'fa-triangle-exclamation',
                                    'berat'  => 'fa-circle-radiation',
                                    default  => 'fa-circle',
                                };
                                $borderAccent = match ($tingkat) {
                                    'ringan' => 'border-left: 3px solid #10b981;',
                                    'sedang' => 'border-left: 3px solid #f59e0b;',
                                    'berat'  => 'border-left: 3px solid #ef4444;',
                                    default  => '',
                                };
                            @endphp
                            <tr style="border-bottom: 1px solid var(--border-color); {{ $borderAccent }}">
                                <td
                                    style="padding: 12px 16px; font-family: monospace; font-weight: 700; color: var(--primary);">
                                    {{ $item->kode }}</td>
                                <td style="padding: 12px 16px; text-align: center;">
                                    <span style="display: inline-flex; align-items: center; gap: 5px; padding: 4px 10px; border-radius: 9999px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.03em; {{ $tingkatStyle }}">
                                        <i class="fas {{ $tingkatIcon }}"></i> {{ $tingkat }}
                                    </span>
                                </td>
                                <td style="padding: 12px 16px;">
                                    @php
                                        $katColor = match ($item->kategori) {
                                            'kerapian' => 'badge-outline',
                                            'kehadiran' => 'badge-primary',
                                            'perilaku' => 'badge-warning',
                                            'larangan_berat' => 'badge-danger',
                                            default => 'badge-outline',
                                        };
                                    @endphp
                                    <span
                                        class="badge {{ $katColor }}">{{ strtoupper(str_replace('_', ' ', $item->kategori)) }}</span>
                                </td>
                                <td style="padding: 12px 16px; font-weight: 700; color: var(--text-color);">
                                    {{ $item->nama_aturan }}</td>
                                <td
                                    style="padding: 12px 16px; text-align: center; font-weight: 800; font-size: var(--text-lg, 1.125rem); {{ $tingkat === 'berat' ? 'color: var(--danger, #ef4444);' : ($tingkat === 'sedang' ? 'color: var(--warning, #f59e0b);' : 'color: var(--success, #10b981);') }}">
                                    +{{ $item->bobot_poin }}
                                </td>
                                <td style="padding: 12px 16px; font-size: var(--text-base, 0.875rem); color: var(--text-muted);">
                                    {{ $item->sanksi_rekomendasi ?: '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6"
                                    style="text-align: center; padding: 40px 16px; color: var(--text-muted);">
                                    <i class="fas fa-folder-open"
                                        style="font-size: var(--text-3xl, 1.875rem); opacity: 0.3; margin-bottom: 10px; display: block;"></i>
                                    Belum ada aturan tata tertib yang cocok dengan pencarian.
                                    @if ($canCreate)
                                        <div style="margin-top: 14px; display: flex; gap: 8px; justify-content: center;">
                                            <button type="button" class="btn btn-outline" onclick="document.getElementById('btnOpenImportTatibModal')?.click()" style="border-radius: var(--radius-md, 8px);">
                                                <i class="fas fa-file-excel text-success"></i> Import Excel / CSV
                                            </button>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @elseif ($activeTab === 'poin' || $activeTab === 'riwayat' || $activeTab === 'tindak_lanjut')
            <!-- TAB 2 & 3 & 6: RIWAYAT PELANGGARAN & SANGGAH -->
            <div class="table-responsive-stack" style="overflow-x: auto; width: 100%;">
                <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                    <thead>
                        <tr style="background: rgba(0,0,0,0.02); border-bottom: 1px solid var(--border-color);">
                            <th
                                style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 100px;">
                                Tanggal</th>
                            <th
                                style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Nama Siswa</th>
                            <th
                                style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Jenis Pelanggaran</th>
                            <th
                                style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 95px;">
                                Poin</th>
                            <th
                                style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 130px;">
                                Pelapor</th>
                            <th
                                style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 150px; text-align: center;">
                                Status &amp; Resolusi</th>
                            <th
                                style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 120px; text-align: center;">
                                Sanggah</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($riwayatList as $item)
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <td style="padding: 12px 16px; font-size: var(--text-base, 0.875rem);">
                                    {{ date('d/m/Y', strtotime($item->tanggal_kejadian)) }}</td>
                                <td style="padding: 12px 16px; font-weight: 700; color: var(--text-color);">
                                    {{ $item->siswa?->nama ?: 'Siswa #' . $item->peserta_didik_id }}
                                    <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted);">NISN:
                                        {{ $item->siswa?->nisn ?: '-' }}</div>
                                </td>
                                <td style="padding: 12px 16px; font-size: var(--text-base, 0.875rem);">
                                    <strong>{{ $item->aturan?->nama_aturan ?: 'Pelanggaran Khusus' }}</strong>
                                    @if ($item->aturan?->tingkat)
                                        <span class="badge {{ $item->aturan->tingkat === 'berat' ? 'badge-danger' : ($item->aturan->tingkat === 'sedang' ? 'badge-warning' : 'badge-success') }}" style="font-size: 0.65rem; margin-left: 4px;">
                                            {{ strtoupper($item->aturan->tingkat) }}
                                        </span>
                                    @endif
                                    @if ($item->keterangan)
                                        <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-top: 2px;">Ket:
                                            {{ $item->keterangan }}</div>
                                    @endif
                                </td>
                                <td style="padding: 12px 16px; text-align: center;">
                                    @if ($item->sanggah_status)
                                        <div style="font-size: var(--text-sm, 0.8125rem); text-decoration: line-through; color: var(--text-muted);">+{{ $item->poin }}</div>
                                        <div style="font-weight: 800; font-size: var(--text-base, 0.875rem); color: #059669;">
                                            +{{ $item->poin_netto }}
                                        </div>
                                    @else
                                        <div style="font-weight: 800; color: var(--danger, #ef4444); font-size: var(--text-lg, 1.125rem);">
                                            +{{ $item->poin }}
                                        </div>
                                    @endif
                                </td>
                                <td style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); color: var(--text-muted);">
                                    {{ $item->pelapor_nama ?: 'Guru/Piket' }}</td>
                                <td style="padding: 12px 16px; text-align: center;">
                                    @if ($item->sanggah_status)
                                        <span class="badge badge-success" style="display: inline-flex; align-items: center; gap: 4px; padding: 4px 8px; font-size: 0.75rem; border-radius: 9999px;">
                                            <i class="fas fa-circle-check"></i> DISANGGAH
                                        </span>
                                        <div style="font-size: 0.6875rem; font-weight: 700; color: #059669; margin-top: 3px;">
                                            {{ ucwords(str_replace('_', ' ', $item->sanggah_jenis)) }} (-{{ $item->sanggah_poin_potong }})
                                        </div>
                                    @else
                                        @php
                                            $stColor = match ($item->status_tindak_lanjut) {
                                                'selesai' => 'badge-success',
                                                'proses'  => 'badge-warning',
                                                default   => 'badge-danger',
                                            };
                                        @endphp
                                        <span class="badge {{ $stColor }}">{{ strtoupper($item->status_tindak_lanjut) }}</span>
                                    @endif
                                </td>
                                <td style="padding: 12px 16px; text-align: center;">
                                    @if ($item->sanggah_status)
                                        <button type="button" class="btn btn-sm btn-outline btn-detail-sanggah"
                                            data-sanggah-jenis="{{ $item->sanggah_jenis }}"
                                            data-sanggah-ket="{{ $item->sanggah_keterangan }}"
                                            data-sanggah-potong="{{ $item->sanggah_poin_potong }}"
                                            data-sanggah-validator="{{ $item->sanggah_validator }}"
                                            data-sanggah-tanggal="{{ $item->sanggah_tanggal ? date('d/m/Y', strtotime($item->sanggah_tanggal)) : '-' }}"
                                            data-sanggah-bukti="{{ $item->sanggah_bukti ? asset($item->sanggah_bukti) : '' }}"
                                            data-siswa="{{ $item->siswa?->nama }}"
                                            data-aturan="{{ $item->aturan?->nama_aturan }}"
                                            title="Lihat Detail Kompensasi Sanggahan"
                                            style="border-radius: var(--radius-md, 8px); padding: 4px 10px; font-size: var(--text-xs, 0.75rem); display: inline-flex; align-items: center; gap: 4px; border-color: #059669; color: #059669;">
                                            <i class="fas fa-eye"></i> Detail
                                        </button>
                                    @elseif ($canUpdate)
                                        <button type="button" class="btn btn-sm btn-outline btn-open-sanggah"
                                            data-id="{{ $item->id }}"
                                            data-siswa="{{ $item->siswa?->nama }}"
                                            data-nisn="{{ $item->siswa?->nisn }}"
                                            data-aturan="{{ $item->aturan?->nama_aturan }}"
                                            data-poin="{{ $item->poin }}"
                                            title="Ajukan Sanggah / Kompensasi Bakti Sekolah"
                                            style="border-radius: var(--radius-md, 8px); padding: 4px 10px; font-size: var(--text-xs, 0.75rem); display: inline-flex; align-items: center; gap: 4px; border-color: var(--primary); color: var(--primary);">
                                            <i class="fas fa-scale-balanced"></i> Sanggah
                                        </button>
                                    @else
                                        <span style="color: var(--text-muted); font-size: var(--text-xs, 0.75rem);">-</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7"
                                    style="text-align: center; padding: 40px 16px; color: var(--text-muted);">
                                    <i class="fas fa-folder-open"
                                        style="font-size: var(--text-3xl, 1.875rem); opacity: 0.3; margin-bottom: 10px; display: block;"></i>
                                    Belum ada riwayat pelanggaran poin siswa yang cocok dengan filter pencarian.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($riwayatList->hasPages())
                <div class="datatable-footer">
                    @include('partials.datatable-pagination', ['paginator' => $riwayatList])
                </div>
            @endif
        @elseif ($activeTab === 'pembinaan')
            <!-- TAB 4: SESI PEMBINAAN KONSELING -->
            <div class="table-responsive-stack" style="overflow-x: auto; width: 100%;">
                <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                    <thead>
                        <tr style="background: rgba(0,0,0,0.02); border-bottom: 1px solid var(--border-color);">
                            <th
                                style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 110px;">
                                Tanggal</th>
                            <th
                                style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Nama Siswa</th>
                            <th
                                style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 160px;">
                                Bentuk Pembinaan</th>
                            <th
                                style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Hasil / Catatan Konseling</th>
                            <th
                                style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 150px;">
                                Pembina</th>
                            <th
                                style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 110px; text-align: center;">
                                Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($pembinaanList as $item)
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <td style="padding: 12px 16px; font-size: var(--text-base, 0.875rem);">
                                    {{ date('d/m/Y', strtotime($item->tanggal_pembinaan)) }}</td>
                                <td style="padding: 12px 16px; font-weight: 700; color: var(--text-color);">
                                    {{ $item->siswa?->nama ?: '-' }}</td>
                                <td style="padding: 12px 16px; font-size: var(--text-base, 0.875rem);"><span
                                        class="badge badge-primary">{{ $item->bentuk_pembinaan }}</span></td>
                                <td style="padding: 12px 16px; font-size: var(--text-base, 0.875rem);">{{ $item->hasil_pembinaan }}</td>
                                <td style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); color: var(--text-muted);">
                                    {{ $item->guruBk?->nama ?: ($item->waliKelas?->nama ?: 'Guru BK / Wali') }}</td>
                                <td style="padding: 12px 16px; text-align: center;">
                                    <span
                                        class="badge {{ $item->status === 'selesai' ? 'badge-success' : 'badge-warning' }}">{{ strtoupper($item->status) }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6"
                                    style="text-align: center; padding: 40px 16px; color: var(--text-muted);">
                                    <i class="fas fa-folder-open"
                                        style="font-size: var(--text-3xl, 1.875rem); opacity: 0.3; margin-bottom: 10px; display: block;"></i>
                                    Belum ada sesi pembinaan konseling yang cocok dengan filter pencarian.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- 3. Footer Paginasi Terpadu Datatable -->
            @if ($pembinaanList->hasPages())
                <div class="datatable-footer">
                    @include('partials.datatable-pagination', ['paginator' => $pembinaanList])
                </div>
            @endif
        @elseif ($activeTab === 'pemanggilan')
            <!-- TAB 5: SURAT PEMANGGILAN WALI MURID -->
            <div class="table-responsive-stack" style="overflow-x: auto; width: 100%;">
                <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                    <thead>
                        <tr style="background: rgba(0,0,0,0.02); border-bottom: 1px solid var(--border-color);">
                            <th
                                style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 160px;">
                                No. Surat</th>
                            <th
                                style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Nama Siswa</th>
                            <th
                                style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 170px;">
                                Jadwal Kehadiran Ortu</th>
                            <th
                                style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Alasan Pemanggilan</th>
                            <th
                                style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 140px;">
                                Menghadap Ke</th>
                            <th
                                style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 90px; text-align: center;">
                                Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($pemanggilanList as $item)
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <td
                                    style="padding: 12px 16px; font-family: monospace; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--primary);">
                                    {{ $item->nomor_surat }}</td>
                                <td style="padding: 12px 16px; font-weight: 700; color: var(--text-color);">
                                    {{ $item->siswa?->nama ?: '-' }}</td>
                                <td style="padding: 12px 16px; font-size: var(--text-base, 0.875rem);">
                                    <strong>{{ date('d/m/Y', strtotime($item->tanggal_hadir)) }}</strong> pk
                                    {{ substr($item->jam_hadir, 0, 5) }}
                                    <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted);">Tempat: {{ $item->tempat }}
                                    </div>
                                </td>
                                <td style="padding: 12px 16px; font-size: var(--text-base, 0.875rem);">{{ $item->alasan }}</td>
                                <td style="padding: 12px 16px; font-size: var(--text-base, 0.875rem);">{{ $item->menghadap_ke }}</td>
                                <td style="padding: 12px 16px; text-align: center;">
                                    <div class="table-actions" style="display: flex; gap: 4px; justify-content: center;">
                                        <a href="{{ route('dashboard.kesiswaan.kedisiplinan.panggilan-wali.cetak', $item->id) }}"
                                            target="_blank" class="btn-icon" title="Cetak Surat Pemanggilan">
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
                                    Belum ada surat pemanggilan wali murid yang cocok dengan pencarian.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- 3. Footer Paginasi Terpadu Datatable -->
            @if ($pemanggilanList->hasPages())
                <div class="datatable-footer">
                    @include('partials.datatable-pagination', ['paginator' => $pemanggilanList])
                </div>
            @endif
        @elseif ($activeTab === 'rekap')
            <!-- TAB 1: REKAPITULASI & CHART INFORMATIF KEDISIPLINAN -->
            <div style="padding: 20px 20px 0 20px;">
                <!-- Grid 4 Chart Informatif Kedisiplinan -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 18px; margin-bottom: 24px;">
                    <!-- Chart 1: Tren Kasus Pelanggaran -->
                    <div style="background: var(--bg-card, #fff); border: 1px solid var(--border-color, #e2e8f0); border-radius: var(--radius-md, 10px); padding: 18px; box-shadow: 0 1px 3px rgba(0,0,0,0.03);">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px;">
                            <div style="font-weight: 800; font-size: var(--text-base, 0.9375rem); color: var(--text-color); display: flex; align-items: center; gap: 8px;">
                                <i class="fas fa-chart-line" style="color: var(--primary);"></i> Tren Pelanggaran (6 Bulan)
                            </div>
                            <span class="badge badge-primary" style="font-size: 0.7rem;">Realtime</span>
                        </div>
                        <div style="height: 220px; position: relative;">
                            <canvas id="chartTrenPelanggaran"></canvas>
                        </div>
                    </div>

                    <!-- Chart 2: Proporsi Tingkat Keparahan -->
                    <div style="background: var(--bg-card, #fff); border: 1px solid var(--border-color, #e2e8f0); border-radius: var(--radius-md, 10px); padding: 18px; box-shadow: 0 1px 3px rgba(0,0,0,0.03);">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px;">
                            <div style="font-weight: 800; font-size: var(--text-base, 0.9375rem); color: var(--text-color); display: flex; align-items: center; gap: 8px;">
                                <i class="fas fa-chart-pie" style="color: #f59e0b;"></i> Tingkat Keparahan
                            </div>
                            <span style="font-size: 0.75rem; color: var(--text-muted);">Ringan vs Sedang vs Berat</span>
                        </div>
                        <div style="height: 220px; position: relative;">
                            <canvas id="chartTingkatPelanggaran"></canvas>
                        </div>
                    </div>

                    <!-- Chart 3: Kategori Pelanggaran -->
                    <div style="background: var(--bg-card, #fff); border: 1px solid var(--border-color, #e2e8f0); border-radius: var(--radius-md, 10px); padding: 18px; box-shadow: 0 1px 3px rgba(0,0,0,0.03);">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px;">
                            <div style="font-weight: 800; font-size: var(--text-base, 0.9375rem); color: var(--text-color); display: flex; align-items: center; gap: 8px;">
                                <i class="fas fa-chart-column" style="color: #6366f1;"></i> Distribusi Kategori Aturan
                            </div>
                            <span style="font-size: 0.75rem; color: var(--text-muted);">4 Kategori</span>
                        </div>
                        <div style="height: 220px; position: relative;">
                            <canvas id="chartKategoriPelanggaran"></canvas>
                        </div>
                    </div>

                    <!-- Chart 4: Status & Sanggah Resolusi -->
                    <div style="background: var(--bg-card, #fff); border: 1px solid var(--border-color, #e2e8f0); border-radius: var(--radius-md, 10px); padding: 18px; box-shadow: 0 1px 3px rgba(0,0,0,0.03);">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px;">
                            <div style="font-weight: 800; font-size: var(--text-base, 0.9375rem); color: var(--text-color); display: flex; align-items: center; gap: 8px;">
                                <i class="fas fa-hand-holding-hand" style="color: #10b981;"></i> Status &amp; Sanggah Bakti
                            </div>
                            <span style="font-size: 0.75rem; color: #10b981; font-weight: 700;">{{ $stats['total_disanggah'] ?? 0 }} Sanggah</span>
                        </div>
                        <div style="height: 220px; position: relative;">
                            <canvas id="chartStatusPelanggaran"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Top 5 Pelanggaran Terbanyak -->
                @if (isset($topAturan) && count($topAturan) > 0)
                    <div style="margin-bottom: 24px; padding: 16px 18px; border-radius: var(--radius-md, 10px); background: rgba(59,130,246,0.04); border: 1px solid rgba(59,130,246,0.15);">
                        <div style="font-weight: 800; font-size: var(--text-sm, 0.875rem); color: var(--text-color); margin-bottom: 10px; display: flex; align-items: center; gap: 6px;">
                            <i class="fas fa-fire text-danger"></i> Top 5 Aturan Paling Sering Dilanggar:
                        </div>
                        <div style="display: flex; flex-wrap: wrap; gap: 10px;">
                            @foreach ($topAturan as $top)
                                <div style="display: flex; align-items: center; gap: 8px; padding: 6px 12px; border-radius: var(--radius-md, 8px); background: var(--bg-card, #fff); border: 1px solid var(--border-color); font-size: var(--text-xs, 0.75rem);">
                                    <span style="font-family: monospace; font-weight: 700; color: var(--primary);">{{ $top->kode }}</span>
                                    <span style="font-weight: 600; color: var(--text-color); max-width: 220px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $top->nama_aturan }}">{{ $top->nama_aturan }}</span>
                                    <span class="badge {{ $top->tingkat === 'berat' ? 'badge-danger' : ($top->tingkat === 'sedang' ? 'badge-warning' : 'badge-success') }}" style="font-size: 0.65rem;">
                                        {{ strtoupper($top->tingkat) }}
                                    </span>
                                    <span style="font-weight: 800; color: var(--danger, #ef4444); background: rgba(239,68,68,0.1); padding: 2px 6px; border-radius: 4px;">
                                        {{ $top->total_pelanggaran }}x
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            <!-- TABEL AKUMULASI POIN SISWA -->
            <div style="padding: 0 20px 10px 20px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                    <div style="font-weight: 800; font-size: var(--text-base, 0.9375rem); color: var(--text-color); display: flex; align-items: center; gap: 6px;">
                        <i class="fas fa-trophy" style="color: #f59e0b;"></i> Leaderboard Akumulasi Poin Siswa (Netto)
                    </div>
                    <span style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted);">Akumulasi poin setelah dikurangi kompensasi sanggah/bakti sekolah</span>
                </div>
            </div>

            <div class="table-responsive-stack" style="overflow-x: auto; width: 100%;">
                <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                    <thead>
                        <tr style="background: rgba(0,0,0,0.02); border-bottom: 1px solid var(--border-color);">
                            <th
                                style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 70px; text-align: center;">
                                Peringkat</th>
                            <th
                                style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Nama Siswa</th>
                            <th
                                style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 140px;">
                                NISN</th>
                            <th
                                style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 120px;">
                                Rombel</th>
                            <th
                                style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 100px;">
                                Total Kasus</th>
                            <th
                                style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 110px;">
                                Poin Awal</th>
                            <th
                                style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 110px;">
                                Sanggah/Bakti</th>
                            <th
                                style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 110px;">
                                Poin Netto</th>
                            <th
                                style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 220px;">
                                Tindakan Rekomendasi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rekapSiswa as $idx => $sw)
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <td
                                    style="padding: 12px 16px; font-weight: 800; color: var(--text-muted); text-align: center;">
                                    #{{ $idx + 1 }}</td>
                                <td style="padding: 12px 16px; font-weight: 700; color: var(--text-color);">
                                    {{ $sw->nama }}</td>
                                <td style="padding: 12px 16px; font-family: monospace; font-size: var(--text-sm, 0.8125rem);">
                                    {{ $sw->nisn ?: '-' }}</td>
                                <td style="padding: 12px 16px; font-size: var(--text-base, 0.875rem);">{{ $sw->rombel_nama ?: '-' }}</td>
                                <td style="padding: 12px 16px; text-align: center; font-weight: 700;">
                                    {{ $sw->total_kasus }}x
                                </td>
                                <td style="padding: 12px 16px; text-align: center; font-size: var(--text-sm, 0.8125rem); color: var(--text-muted);">
                                    +{{ $sw->total_poin_kotor ?? $sw->total_poin }}
                                </td>
                                <td style="padding: 12px 16px; text-align: center; font-size: var(--text-sm, 0.8125rem); color: #059669; font-weight: 700;">
                                    {{ ($sw->total_disanggah ?? 0) > 0 ? '-' . $sw->total_disanggah : '-' }}
                                </td>
                                <td
                                    style="padding: 12px 16px; text-align: center; font-weight: 800; font-size: var(--text-lg, 1.125rem); color: {{ $sw->total_poin >= 50 ? '#ef4444' : ($sw->total_poin >= 25 ? '#f59e0b' : 'var(--text-color)') }};">
                                    {{ $sw->total_poin }}
                                </td>
                                <td style="padding: 12px 16px;">
                                    @if ($sw->total_poin >= 100)
                                        <span class="badge badge-danger">Dikeluarkan / SP 3</span>
                                    @elseif ($sw->total_poin >= 50)
                                        <span class="badge badge-danger">Panggilan Ortu &amp; Skorsing (SP 2)</span>
                                    @elseif ($sw->total_poin >= 25)
                                        <span class="badge badge-warning">Peringatan Tertulis &amp; SP 1</span>
                                    @elseif ($sw->total_poin > 0)
                                        <span class="badge badge-primary">Pembinaan Wali Kelas &amp; BK</span>
                                    @else
                                        <span class="badge badge-success"><i class="fas fa-shield-heart"></i> Bersih (Disanggah)</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9"
                                    style="text-align: center; padding: 40px 16px; color: var(--text-muted);">
                                    <i class="fas fa-folder-open"
                                        style="font-size: var(--text-3xl, 1.875rem); opacity: 0.3; margin-bottom: 10px; display: block;"></i>
                                    Tidak ada rekaman pelanggaran poin. Seluruh siswa bersih.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <!-- MODAL 1: ATURAN TATA TERTIB -->
    <div id="modalTataTertib" class="modal-backdrop"
        style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
        <div class="card" style="width: 100%; max-width: 500px; padding: 24px; border-radius: var(--radius-lg, 12px); margin: 16px;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px;">
                <h3 style="font-size: var(--text-xl, 1.25rem); font-weight: 800; color: var(--text-color); margin: 0;">Tambah Aturan Tata
                    Tertib</h3>
                <button type="button" class="close-modal" data-target="#modalTataTertib"
                    style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: var(--text-lg, 1.125rem); padding: 4px; display: inline-flex; align-items: center; justify-content: center; transition: color 0.2s ease;" aria-label="Tutup"><i
                        class="fas fa-times"></i></button>
            </div>
            <form id="formTataTertib" method="POST"
                action="{{ route('dashboard.kesiswaan.kedisiplinan.tatib.store') }}"
                data-next-kode-url="{{ route('dashboard.kesiswaan.kedisiplinan.tatib.next_kode') }}">
                @csrf
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                    <div class="form-group">
                        <label
                            style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">Kode
                            Aturan <small style="font-weight: 500; color: var(--primary);">(Otomatis)</small></label>
                        <input type="text" id="inputTatibKode" name="kode" class="form-control" placeholder="Otomatis"
                            style="width: 100%; border-radius: var(--radius-md, 8px); font-family: monospace; font-weight: 700;">
                    </div>
                    <div class="form-group">
                        <label
                            style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">Tingkat
                            <span class="text-danger">*</span></label>
                        <select name="tingkat" class="form-control" required style="width: 100%; border-radius: var(--radius-md, 8px);">
                            <option value="ringan">Ringan</option>
                            <option value="sedang">Sedang</option>
                            <option value="berat">Berat</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label
                            style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">Kategori
                            <span class="text-danger">*</span></label>
                        <select id="selectTatibKategori" name="kategori" class="form-control" required style="width: 100%; border-radius: var(--radius-md, 8px);">
                            <option value="kerapian">Kerapian</option>
                            <option value="kehadiran">Kehadiran</option>
                            <option value="perilaku">Perilaku</option>
                            <option value="larangan_berat">Larangan Berat</option>
                        </select>
                    </div>
                </div>
                <div class="form-group" style="margin-bottom: 14px;">
                    <label
                        style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">Uraian
                        Pelanggaran <span class="text-danger">*</span></label>
                    <input type="text" name="nama_aturan" class="form-control"
                        placeholder="Tuliskan nama pelanggaran" required style="width: 100%; border-radius: var(--radius-md, 8px);">
                </div>
                <div style="display: grid; grid-template-columns: 100px 1fr; gap: 12px; margin-bottom: 18px;">
                    <div class="form-group">
                        <label
                            style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">Poin
                            <span class="text-danger">*</span></label>
                        <input type="number" name="bobot_poin" class="form-control" value="5" min="1"
                            required style="width: 100%; border-radius: var(--radius-md, 8px);">
                    </div>
                    <div class="form-group">
                        <label
                            style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">Sanksi
                            Rekomendasi</label>
                        <input type="text" name="sanksi_rekomendasi" class="form-control"
                            placeholder="Contoh: Teguran lisan" style="width: 100%; border-radius: var(--radius-md, 8px);">
                    </div>
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 8px;">
                    <button type="button" class="btn btn-outline close-modal" data-target="#modalTataTertib"
                        style="border-radius: var(--radius-md, 8px);">Batal</button>
                    <button type="submit" class="btn btn-primary" style="border-radius: var(--radius-md, 8px);"><i
                            class="fas fa-save"></i> Simpan Aturan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 2: CATAT PELANGGARAN POIN -->
    <div id="modalPoin" class="modal-backdrop"
        style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
        <div class="card" style="width: 100%; max-width: 520px; padding: 24px; border-radius: var(--radius-lg, 12px); margin: 16px;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px;">
                <h3 style="font-size: var(--text-xl, 1.25rem); font-weight: 800; color: var(--text-color); margin: 0;">Catat Pelanggaran
                    Poin Siswa</h3>
                <button type="button" class="close-modal" data-target="#modalPoin"
                    style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: var(--text-lg, 1.125rem); padding: 4px; display: inline-flex; align-items: center; justify-content: center; transition: color 0.2s ease;" aria-label="Tutup"><i
                        class="fas fa-times"></i></button>
            </div>
            <form id="formPoin" method="POST"
                action="{{ route('dashboard.kesiswaan.kedisiplinan.pelanggaran.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="form-group" style="margin-bottom: 14px;">
                    <label
                        style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">Pilih
                        Siswa <span class="text-danger">*</span></label>
                    <input type="search" id="poinSiswaLookup" class="form-control"
                        placeholder="Cari nama, NISN, atau NIPD..." autocomplete="off"
                        data-people-search="{{ route('dashboard.people.search') }}" data-people-kind="siswa"
                        data-people-target="#poinSiswaId" data-people-require-selection="true" required
                        style="width: 100%; border-radius: var(--radius-md, 8px);">
                    <input type="hidden" id="poinSiswaId" name="peserta_didik_id">
                </div>
                <div class="form-group" style="margin-bottom: 14px;">
                    <label
                        style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">Pilih
                        Pelanggaran Tata Tertib <span class="text-danger">*</span></label>
                    <select name="tata_tertib_id" class="form-control" required style="width: 100%; border-radius: var(--radius-md, 8px);">
                        <option value="">-- Pilih Jenis Pelanggaran --</option>
                        @foreach ($tataTertibList as $tt)
                            <option value="{{ $tt->id }}">[{{ strtoupper($tt->tingkat) }}] [{{ $tt->kode }}] {{ $tt->nama_aturan }}
                                (+{{ $tt->bobot_poin }} Poin)
                            </option>
                        @endforeach
                    </select>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                    <div class="form-group">
                        <label
                            style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">Tanggal
                            Kejadian <span class="text-danger">*</span></label>
                        <input type="date" name="tanggal_kejadian" class="form-control" value="{{ date('Y-m-d') }}"
                            required style="width: 100%; border-radius: var(--radius-md, 8px);">
                    </div>
                    <div class="form-group">
                        <label
                            style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">Tempat
                            Kejadian</label>
                        <input type="text" name="tempat_kejadian" class="form-control"
                            placeholder="Contoh: Depan Gerbang / Kantin" style="width: 100%; border-radius: var(--radius-md, 8px);">
                    </div>
                </div>
                <div class="form-group" style="margin-bottom: 14px;">
                    <label
                        style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">Keterangan
                        / Catatan Tambahan</label>
                    <input type="text" name="keterangan" class="form-control" placeholder="Rincian kronologi singkat"
                        style="width: 100%; border-radius: var(--radius-md, 8px);">
                </div>
                <div class="form-group" style="margin-bottom: 18px;">
                    <label
                        style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">Unggah
                        Foto Bukti (Opsional)</label>
                    <input type="file" name="foto_bukti" class="form-control" accept="image/*"
                        style="width: 100%; border-radius: var(--radius-md, 8px);">
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 8px;">
                    <button type="button" class="btn btn-outline close-modal" data-target="#modalPoin"
                        style="border-radius: var(--radius-md, 8px);">Batal</button>
                    <button type="submit" class="btn btn-primary"
                        style="border-radius: var(--radius-md, 8px); background: var(--danger, #ef4444); border-color: var(--danger, #ef4444);"><i
                            class="fas fa-save"></i> Catat Poin</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 3: PEMBINAAN SISWA -->
    <div id="modalPembinaan" class="modal-backdrop"
        style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
        <div class="card" style="width: 100%; max-width: 520px; padding: 24px; border-radius: var(--radius-lg, 12px); margin: 16px;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px;">
                <h3 style="font-size: var(--text-xl, 1.25rem); font-weight: 800; color: var(--text-color); margin: 0;">Catat Sesi Pembinaan
                    Konseling</h3>
                <button type="button" class="close-modal" data-target="#modalPembinaan"
                    style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: var(--text-lg, 1.125rem); padding: 4px; display: inline-flex; align-items: center; justify-content: center; transition: color 0.2s ease;" aria-label="Tutup"><i
                        class="fas fa-times"></i></button>
            </div>
            <form id="formPembinaan" method="POST"
                action="{{ route('dashboard.kesiswaan.kedisiplinan.pembinaan.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="form-group" style="margin-bottom: 14px;">
                    <label
                        style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">Pilih
                        Siswa <span class="text-danger">*</span></label>
                    <input type="search" id="pembinaanSiswaLookup" class="form-control"
                        placeholder="Cari nama, NISN, atau NIPD..." autocomplete="off"
                        data-people-search="{{ route('dashboard.people.search') }}" data-people-kind="siswa"
                        data-people-target="#pembinaanSiswaId" data-people-require-selection="true" required
                        style="width: 100%; border-radius: var(--radius-md, 8px);">
                    <input type="hidden" id="pembinaanSiswaId" name="peserta_didik_id">
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                    <div class="form-group">
                        <label
                            style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">Tanggal
                            Pembinaan <span class="text-danger">*</span></label>
                        <input type="date" name="tanggal_pembinaan" class="form-control" value="{{ date('Y-m-d') }}"
                            required style="width: 100%; border-radius: var(--radius-md, 8px);">
                    </div>
                    <div class="form-group">
                        <label
                            style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">Bentuk
                            Pembinaan <span class="text-danger">*</span></label>
                        <select name="bentuk_pembinaan" class="form-control" required
                            style="width: 100%; border-radius: var(--radius-md, 8px);">
                            <option value="Konseling Individual BK">Konseling Individual BK</option>
                            <option value="Peringatan Lisan & Refleksi">Peringatan Lisan & Refleksi</option>
                            <option value="Surat Perjanjian Bermaterai">Surat Perjanjian Bermaterai</option>
                            <option value="Restorative Justice / Kerja Sosial">Restorative Justice / Kerja Sosial</option>
                        </select>
                    </div>
                </div>
                <div class="form-group" style="margin-bottom: 14px;">
                    <label
                        style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">Hasil
                        / Komitmen Siswa <span class="text-danger">*</span></label>
                    <textarea name="hasil_pembinaan" class="form-control" rows="3"
                        placeholder="Tuliskan hasil sesi konseling dan komitmen siswa..." required
                        style="width: 100%; border-radius: var(--radius-md, 8px);"></textarea>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 18px;">
                    <div class="form-group">
                        <label
                            style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">Status
                            Kasus <span class="text-danger">*</span></label>
                        <select name="status" class="form-control" required style="width: 100%; border-radius: var(--radius-md, 8px);">
                            <option value="selesai">Selesai / Tuntas</option>
                            <option value="proses">Masih Dalam Pemantauan</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label
                            style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">File
                            Surat Perjanjian (Jika Ada)</label>
                        <input type="file" name="surat_perjanjian_file" class="form-control" accept=".pdf,image/*"
                            style="width: 100%; border-radius: var(--radius-md, 8px);">
                    </div>
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 8px;">
                    <button type="button" class="btn btn-outline close-modal" data-target="#modalPembinaan"
                        style="border-radius: var(--radius-md, 8px);">Batal</button>
                    <button type="submit" class="btn btn-primary" style="border-radius: var(--radius-md, 8px);"><i
                            class="fas fa-save"></i> Simpan Pembinaan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 4: PEMANGGILAN ORANG TUA / WALI -->
    <div id="modalPanggilan" class="modal-backdrop"
        style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
        <div class="card" style="width: 100%; max-width: 520px; padding: 24px; border-radius: var(--radius-lg, 12px); margin: 16px;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px;">
                <h3 style="font-size: var(--text-xl, 1.25rem); font-weight: 800; color: var(--text-color); margin: 0;">Terbitkan Surat
                    Panggilan Orang Tua</h3>
                <button type="button" class="close-modal" data-target="#modalPanggilan"
                    style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: var(--text-lg, 1.125rem); padding: 4px; display: inline-flex; align-items: center; justify-content: center; transition: color 0.2s ease;" aria-label="Tutup"><i
                        class="fas fa-times"></i></button>
            </div>
            <form id="formPanggilan" method="POST"
                action="{{ route('dashboard.kesiswaan.kedisiplinan.panggilan-wali.store') }}">
                @csrf
                <div class="form-group" style="margin-bottom: 14px;">
                    <label
                        style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">Pilih
                        Siswa <span class="text-danger">*</span></label>
                    <input type="search" id="panggilanSiswaLookup" class="form-control"
                        placeholder="Cari nama, NISN, atau NIPD..." autocomplete="off"
                        data-people-search="{{ route('dashboard.people.search') }}" data-people-kind="siswa"
                        data-people-target="#panggilanSiswaId" data-people-require-selection="true" required
                        style="width: 100%; border-radius: var(--radius-md, 8px);">
                    <input type="hidden" id="panggilanSiswaId" name="peserta_didik_id">
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                    <div class="form-group">
                        <label
                            style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">Tanggal
                            Surat <span class="text-danger">*</span></label>
                        <input type="date" name="tanggal_surat" class="form-control" value="{{ date('Y-m-d') }}"
                            required style="width: 100%; border-radius: var(--radius-md, 8px);">
                    </div>
                    <div class="form-group">
                        <label
                            style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">Tanggal
                            Hadir Ortu <span class="text-danger">*</span></label>
                        <input type="date" name="tanggal_hadir" class="form-control"
                            value="{{ date('Y-m-d', strtotime('+1 day')) }}" required
                            style="width: 100%; border-radius: var(--radius-md, 8px);">
                    </div>
                </div>
                <div style="display: grid; grid-template-columns: 120px 1fr; gap: 12px; margin-bottom: 14px;">
                    <div class="form-group">
                        <label
                            style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">Pukul
                            / Jam <span class="text-danger">*</span></label>
                        <input type="time" name="jam_hadir" class="form-control" value="08:30" required
                            style="width: 100%; border-radius: var(--radius-md, 8px);">
                    </div>
                    <div class="form-group">
                        <label
                            style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">Ruang
                            / Tempat <span class="text-danger">*</span></label>
                        <input type="text" name="tempat" class="form-control"
                            value="Ruang Bimbingan Konseling / Kesiswaan" required
                            style="width: 100%; border-radius: var(--radius-md, 8px);">
                    </div>
                </div>
                <div class="form-group" style="margin-bottom: 14px;">
                    <label
                        style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">Alasan
                        Pemanggilan <span class="text-danger">*</span></label>
                    <input type="text" name="alasan" class="form-control"
                        placeholder="Contoh: Akumulasi poin pelanggaran mencapai 50 poin" required
                        style="width: 100%; border-radius: var(--radius-md, 8px);">
                </div>
                <div class="form-group" style="margin-bottom: 18px;">
                    <label
                        style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">Menghadap
                        Ke <span class="text-danger">*</span></label>
                    <input type="text" name="menghadap_ke" class="form-control"
                        value="Guru BK / Waka Bidang Kesiswaan" required style="width: 100%; border-radius: var(--radius-md, 8px);">
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 8px;">
                    <button type="button" class="btn btn-outline close-modal" data-target="#modalPanggilan"
                        style="border-radius: var(--radius-md, 8px);">Batal</button>
                    <button type="submit" class="btn btn-primary" style="border-radius: var(--radius-md, 8px);"><i
                            class="fas fa-paper-plane"></i> Terbitkan Surat</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 5: IMPORT TATA TERTIB -->
    @if ($canCreate)
        <div id="modalImportTataTertib" class="modal-backdrop"
            style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
            <div class="card" style="width: 100%; max-width: 520px; padding: 24px; border-radius: var(--radius-lg, 12px); margin: 16px;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <div style="width: 38px; height: 38px; border-radius: var(--radius-md, 8px); background: rgba(16,185,129,0.12); color: var(--success, #10b981); display: flex; align-items: center; justify-content: center; font-size: var(--text-lg, 1.125rem);">
                            <i class="fas fa-file-excel"></i>
                        </div>
                        <div>
                            <h3 style="font-size: var(--text-lg, 1.125rem); font-weight: 800; color: var(--text-color); margin: 0;">Import Aturan Tata Tertib</h3>
                            <p style="margin: 2px 0 0 0; font-size: var(--text-xs, 0.75rem); color: var(--text-muted);">Unggah berkas Excel / CSV aturan tata tertib &amp; poin</p>
                        </div>
                    </div>
                    <button type="button" class="close-modal" data-target="#modalImportTataTertib"
                        style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: var(--text-lg, 1.125rem); padding: 4px;" aria-label="Tutup">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div style="margin-bottom: 14px; padding: 12px 14px; border-radius: var(--radius-md, 8px); background: rgba(59,130,246,0.06); border: 1px solid rgba(59,130,246,0.2); font-size: var(--text-xs, 0.75rem); color: var(--text-color);">
                    <div style="font-weight: 700; color: var(--primary); margin-bottom: 4px;">
                        <i class="fas fa-circle-info"></i> Format Kolom Excel:
                    </div>
                    <div><code>kode</code>, <code>tingkat</code>, <code>kategori</code>, <code>nama_aturan</code>, <code>bobot_poin</code>, <code>sanksi_rekomendasi</code></div>
                    <div style="margin-top: 4px; color: var(--text-muted);">Tingkat: <em>ringan</em>, <em>sedang</em>, <em>berat</em> (otomatis terdeteksi jika kosong). Kategori: <em>kerapian</em>, <em>kehadiran</em>, <em>perilaku</em>, <em>larangan_berat</em>. Aturan dengan kode yang sama akan diperbarui otomatis.</div>
                </div>

                <div style="margin-bottom: 16px; padding: 10px 14px; border-radius: var(--radius-md, 8px); background: rgba(255,255,255,0.02); border: 1px dashed var(--border-color); display: flex; align-items: center; justify-content: space-between; gap: 10px;">
                    <div>
                        <div style="font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-color);">Belum punya format?</div>
                        <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted);">Unduh template contoh resmi (.xlsx)</div>
                    </div>
                    <a href="{{ route('dashboard.kesiswaan.kedisiplinan.tatib.template') }}" class="btn btn-outline"
                        style="padding: 6px 12px; font-size: var(--text-xs, 0.75rem); display: inline-flex; align-items: center; gap: 6px; border-color: var(--success, #10b981); color: var(--success, #10b981); text-decoration: none;">
                        <i class="fas fa-download"></i> Unduh Template
                    </a>
                </div>

                <form id="formImportTataTertib" method="POST" action="{{ route('dashboard.kesiswaan.kedisiplinan.tatib.import') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="form-group" style="margin-bottom: 18px;">
                        <label style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">
                            Pilih Berkas Excel / CSV <span class="text-danger">*</span>
                        </label>
                        <input type="file" name="file_excel" class="form-control" accept=".xlsx,.xls,.csv,.txt" required
                            style="width: 100%; border-radius: var(--radius-md, 8px); padding: 8px 12px;">
                    </div>

                    <div style="display: flex; justify-content: flex-end; gap: 8px;">
                        <button type="button" class="btn btn-outline close-modal" data-target="#modalImportTataTertib"
                            style="border-radius: var(--radius-md, 8px);">Batal</button>
                        <button type="submit" class="btn btn-primary" style="border-radius: var(--radius-md, 8px); background: var(--success, #10b981); border-color: var(--success, #10b981);">
                            <i class="fas fa-cloud-arrow-up"></i> Unggah &amp; Import
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- MODAL 6: SANGGAH / KOMPENSASI PELANGGARAN POIN SISWA -->
    <div id="modalSanggah" class="modal-backdrop"
        style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
        <div class="card" style="width: 100%; max-width: 520px; padding: 24px; border-radius: var(--radius-lg, 12px); margin: 16px; max-height: 90vh; overflow-y: auto;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div style="width: 38px; height: 38px; border-radius: var(--radius-md, 8px); background: rgba(16,185,129,0.12); color: #059669; display: flex; align-items: center; justify-content: center; font-size: var(--text-lg, 1.125rem);">
                        <i class="fas fa-scale-balanced"></i>
                    </div>
                    <div>
                        <h3 style="font-size: var(--text-lg, 1.125rem); font-weight: 800; color: var(--text-color); margin: 0;">Sanggah / Kompensasi Poin</h3>
                        <p style="margin: 2px 0 0 0; font-size: var(--text-xs, 0.75rem); color: var(--text-muted);">Penebusan pelanggaran siswa melalui bakti sekolah / literasi</p>
                    </div>
                </div>
                <button type="button" class="close-modal" data-target="#modalSanggah"
                    style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: var(--text-lg, 1.125rem); padding: 4px;" aria-label="Tutup">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <!-- Detail Pelanggaran Target -->
            <div style="padding: 12px 14px; border-radius: var(--radius-md, 8px); background: rgba(0,0,0,0.03); border: 1px solid var(--border-color); margin-bottom: 16px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                    <div style="font-weight: 800; font-size: var(--text-sm, 0.875rem); color: var(--text-color);" id="sanggahNamaSiswa">-</div>
                    <span class="badge badge-danger" id="sanggahPoinAwal">+0 Poin</span>
                </div>
                <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted);" id="sanggahNamaAturan">-</div>
            </div>

            <form id="formSanggah" method="POST" action="" enctype="multipart/form-data">
                @csrf
                <div class="form-group" style="margin-bottom: 14px;">
                    <label style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">
                        Jenis Sanggah / Bentuk Kompensasi <span class="text-danger">*</span>
                    </label>
                    <select name="sanggah_jenis" id="sanggahJenisSelect" class="form-control" required style="width: 100%; border-radius: var(--radius-md, 8px);">
                        <option value="bakti_sekolah">Bakti Lingkungan &amp; Sosial Sekolah (Kerja bakti taman, perpustakaan, pilah sampah) (Rekomendasi)</option>
                        <option value="tugas_literasi">Tugas Akademik &amp; Literasi (Resume buku BK / makalah tata tertib)</option>
                        <option value="klarifikasi">Klarifikasi / Pembatalan Sah (Salah identifikasi / memiliki surat izin resmi)</option>
                        <option value="pembinaan_karakter">Pembiasaan &amp; Pembinaan Karakter (Piket ibadah, pembiasaan pagi)</option>
                    </select>
                </div>

                <div style="display: grid; grid-template-columns: 140px 1fr; gap: 12px; margin-bottom: 14px;">
                    <div class="form-group">
                        <label style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">
                            Poin Diputihkan <span class="text-danger">*</span>
                        </label>
                        <input type="number" name="sanggah_poin_potong" id="sanggahPoinPotong" class="form-control" min="1" required
                            style="width: 100%; border-radius: var(--radius-md, 8px); font-weight: 800; color: #059669;">
                    </div>
                    <div class="form-group">
                        <label style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">
                            Guru / Pembina Validasi
                        </label>
                        <input type="text" name="sanggah_validator" class="form-control" placeholder="Nama Guru BK / Wali Kelas"
                            value="{{ session('user.nama') ?? '' }}" style="width: 100%; border-radius: var(--radius-md, 8px);">
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 14px;">
                    <label style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">
                        Uraian Kegiatan Bakti / Catatan Sanggahan <span class="text-danger">*</span>
                    </label>
                    <textarea name="sanggah_keterangan" rows="3" class="form-control" required
                        placeholder="Contoh: Siswa telah menyelesaikan kerja bakti pembersihan area taman sekolah selama 60 menit dengan tertib..."
                        style="width: 100%; border-radius: var(--radius-md, 8px);"></textarea>
                </div>

                <div class="form-group" style="margin-bottom: 18px;">
                    <label style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">
                        Foto Bukti Kegiatan / Dokumen Pendukung <small class="text-muted">(Opsional, maks 5 MB)</small>
                    </label>
                    <input type="file" name="sanggah_bukti" class="form-control" accept="image/*,.pdf"
                        style="width: 100%; border-radius: var(--radius-md, 8px);">
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 8px;">
                    <button type="button" class="btn btn-outline close-modal" data-target="#modalSanggah"
                        style="border-radius: var(--radius-md, 8px);">Batal</button>
                    <button type="submit" class="btn btn-primary" style="border-radius: var(--radius-md, 8px); background: #059669; border-color: #059669;">
                        <i class="fas fa-check-circle"></i> Setujui Sanggahan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 7: DETAIL SANGGAHAN / KOMPENSASI -->
    <div id="modalDetailSanggah" class="modal-backdrop"
        style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
        <div class="card" style="width: 100%; max-width: 480px; padding: 24px; border-radius: var(--radius-lg, 12px); margin: 16px;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px;">
                <h3 style="font-size: var(--text-lg, 1.125rem); font-weight: 800; color: var(--text-color); margin: 0;">
                    <i class="fas fa-circle-check text-success me-1"></i> Informasi Sanggahan
                </h3>
                <button type="button" class="close-modal" data-target="#modalDetailSanggah"
                    style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: var(--text-lg, 1.125rem); padding: 4px;" aria-label="Tutup">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div id="detailSanggahBody" style="font-size: var(--text-sm, 0.875rem);">
                <!-- Dynamic injected via JS -->
            </div>
            <div style="display: flex; justify-content: flex-end; margin-top: 18px;">
                <button type="button" class="btn btn-outline close-modal" data-target="#modalDetailSanggah"
                    style="border-radius: var(--radius-md, 8px);">Tutup</button>
            </div>
        </div>
    </div>

    {{-- Pass chart data for JS --}}
    <script id="kedisiplinanChartData" type="application/json">
        {!! json_encode([
            'activeTab' => $activeTab,
            'tren'      => $chartTren ?? null,
            'tingkat'   => $chartTingkat ?? null,
            'kategori'  => $chartKategori ?? null,
            'status'    => $chartStatus ?? null,
        ]) !!}
    </script>
@endsection

@push('scripts')
    <script
        src="{{ asset('js/kesiswaan-kedisiplinan.js') }}?v={{ file_exists(public_path('js/kesiswaan-kedisiplinan.js')) ? filemtime(public_path('js/kesiswaan-kedisiplinan.js')) : time() }}">
    </script>
@endpush
