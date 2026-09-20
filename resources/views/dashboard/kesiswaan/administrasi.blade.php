@extends('layouts.dashboard')

@section('title', 'Administrasi Kesiswaan (Buku Klaper, Mutasi & Kelulusan) — SAE')
@section('dash_title', 'Administrasi Kesiswaan')

@section('content')
    <!-- 1. Header Banner & Actions -->
    <div class="dash-banner" style="display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; margin-bottom: 20px;">
        <div style="display: flex; align-items: center; gap: 12px;">
            <div style="width: 44px; height: 44px; border-radius: 10px; background: rgba(59,130,246,0.12); color: #2563eb; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; flex-shrink: 0;">
                <i class="fas fa-folder-closed"></i>
            </div>
            <div>
                <h2 style="font-size: 1.3rem; font-weight: 800; color: var(--text-color); margin: 0;">
                    Administrasi Kesiswaan
                </h2>
                <p style="margin: 2px 0 0 0; font-size: 0.82rem; color: var(--text-muted);">
                    Pengelolaan Buku Klaper abjad, mutasi masuk/keluar, dan penetapan kelulusan serta SKL digital.
                </p>
            </div>
        </div>

        <div class="dash-banner-actions" style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
            @if ($canCreate)
                @if ($activeTab === 'klaper')
                    <button type="button" class="btn btn-outline" id="btnSyncKlaper" title="Sinkronkan Buku Klaper dari Dapodik" style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px;">
                        <i class="fas fa-rotate text-primary"></i>
                    </button>
                @elseif ($activeTab === 'mutasi')
                    <button type="button" class="btn btn-primary" id="btnOpenMutasiModal" title="Catat Mutasi Siswa" style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px;">
                        <i class="fas fa-plus"></i>
                    </button>
                @elseif ($activeTab === 'kelulusan')
                    <button type="button" class="btn btn-primary" id="btnOpenKelulusanModal" title="Catat / Update Kelulusan Siswa" style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px;">
                        <i class="fas fa-plus"></i>
                    </button>
                @endif
            @endif
        </div>
    </div>

    <!-- 2. Stat Grid Baku SAE -->
    <div class="dash-stat-grid" style="margin-bottom: 20px;">
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(59,130,246,0.12); color: #2563eb;">
                <i class="fas fa-user-graduate"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ number_format($stats['total_aktif'] ?? 0) }}</div>
                <div class="dash-stat-label">Siswa Aktif</div>
            </div>
        </div>
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(16,185,129,0.12); color: #10b981;">
                <i class="fas fa-book-bookmark"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ number_format($stats['total_klaper'] ?? 0) }}</div>
                <div class="dash-stat-label">Buku Klaper</div>
            </div>
        </div>
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(239,68,68,0.12); color: #ef4444;">
                <i class="fas fa-person-walking-dashed-line-arrow-right"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ number_format($stats['total_mutasi'] ?? 0) }}</div>
                <div class="dash-stat-label">Total Mutasi</div>
            </div>
        </div>
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(245,158,11,0.12); color: #f59e0b;">
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
        <!-- TAB 1: BUKU KLAPER -->
        <div class="card" style="padding: 16px; margin-bottom: 20px;">
            <div style="display: flex; gap: 4px; flex-wrap: wrap; margin-bottom: 14px; align-items: center;">
                <span style="font-size: 0.8rem; font-weight: 700; color: var(--text-muted); margin-right: 8px;">Abjad:</span>
                <a href="{{ route('dashboard.kesiswaan.administrasi.index', ['tab' => 'klaper', 'perPage' => $perPage]) }}"
                    class="badge {{ empty($abjad) ? 'badge-primary' : 'badge-outline' }}" style="text-decoration: none; padding: 4px 8px;">Semua</a>
                @foreach (range('A', 'Z') as $char)
                    <a href="{{ route('dashboard.kesiswaan.administrasi.index', ['tab' => 'klaper', 'abjad' => $char, 'perPage' => $perPage]) }}"
                        class="badge {{ $abjad === $char ? 'badge-primary' : 'badge-outline' }}" style="text-decoration: none; padding: 4px 8px;">{{ $char }}</a>
                @endforeach
            </div>

            <div style="display: flex; flex-wrap: wrap; gap: 12px; justify-content: space-between; align-items: center;">
                <div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
                    <div class="toolbar-entries">
                        <label for="perPageSelect" style="margin: 0;">Tampilkan</label>
                        <select id="perPageSelect" class="per-page-select">
                            @foreach ([10, 15, 25, 50, 100] as $n)
                                <option value="{{ $n }}" {{ ($perPage ?? 25) == $n ? 'selected' : '' }}>{{ $n }}</option>
                            @endforeach
                        </select>
                        <span>entri</span>
                    </div>

                    @if (!empty($q) || !empty($abjad))
                        <a href="{{ route('dashboard.kesiswaan.administrasi.index', ['tab' => 'klaper']) }}"
                            class="btn btn-outline" style="padding: 7px 12px; font-size: 0.84rem;"
                            title="Reset filter">
                            <i class="fas fa-undo"></i>
                        </a>
                    @endif
                </div>

                <div class="live-search-wrap">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" id="liveSearch" placeholder="Cari nama / NISN / no. klaper..." value="{{ $q ?? '' }}" autocomplete="off">
                    <button type="button" id="clearSearch" class="clear-search {{ !empty($q) ? 'visible' : '' }}" title="Hapus pencarian">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        </div>

        <div class="card table-responsive-stack" id="tableDataContainer" style="padding: 0; margin-bottom: 24px;">
            <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                <thead>
                    <tr style="background: rgba(0,0,0,0.02); border-bottom: 1px solid var(--border-color);">
                        <th style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 140px;">No. Klaper</th>
                        <th style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Nama Peserta Didik</th>
                        <th style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 160px;">NISN / NIPD</th>
                        <th style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 80px;">L/P</th>
                        <th style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 130px;">Rombel</th>
                        <th style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 120px; text-align: center;">Status</th>
                        <th style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 100px; text-align: center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($klaperList as $item)
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 12px 16px; font-family: monospace; font-weight: 700; color: var(--primary);">
                                {{ $item->nomor_klaper ?: '-' }}
                            </td>
                            <td style="padding: 12px 16px; font-weight: 700; color: var(--text-color);">
                                {{ $item->nama }}
                            </td>
                            <td style="padding: 12px 16px; font-family: monospace; font-size: 0.82rem;">
                                {{ $item->nisn ?: '-' }} / {{ $item->nipd ?: '-' }}
                            </td>
                            <td style="padding: 12px 16px; text-align: center;">
                                <span class="badge {{ $item->jenis_kelamin === 'L' ? 'badge-primary' : 'badge-accent' }}">
                                    {{ $item->jenis_kelamin }}
                                </span>
                            </td>
                            <td style="padding: 12px 16px; font-size: 0.85rem;">
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
                                <span class="badge {{ $badgeColor }}">{{ strtoupper($item->status_klaper ?: 'AKTIF') }}</span>
                            </td>
                            <td style="padding: 12px 16px; text-align: center;">
                                <div class="table-actions" style="display: flex; gap: 4px; justify-content: center;">
                                    @if ($canUpdate)
                                        <button type="button" class="btn-icon btn-edit-klaper"
                                            data-id="{{ $item->klaper_id }}"
                                            data-nama="{{ $item->nama }}"
                                            data-nomor-klaper="{{ $item->nomor_klaper }}"
                                            data-nomor-induk="{{ $item->nomor_induk }}"
                                            data-tahun-masuk="{{ $item->tahun_masuk }}"
                                            data-status="{{ $item->status_klaper }}"
                                            title="Edit Nomor Klaper">
                                            <i class="fas fa-pen-to-square"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 40px 16px; color: var(--text-muted);">
                                <i class="fas fa-folder-open" style="font-size: 2.2rem; opacity: 0.3; margin-bottom: 10px; display: block;"></i>
                                Tidak ada data Buku Klaper yang cocok dengan filter pencarian.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($klaperList->hasPages())
            <div class="custom-pagination" style="margin-bottom: 24px;">
                @if ($klaperList->onFirstPage())
                    <span class="page-btn disabled"><i class="fas fa-chevron-left"></i></span>
                @else
                    <a href="{{ $klaperList->previousPageUrl() }}" class="page-btn" title="Sebelumnya"><i class="fas fa-chevron-left"></i></a>
                @endif
                @php
                    $cur = $klaperList->currentPage();
                    $last = $klaperList->lastPage();
                    $from = max(1, $cur - 2);
                    $to = min($last, $cur + 2);
                @endphp
                @if ($from > 1)
                    <a href="{{ $klaperList->url(1) }}" class="page-btn">1</a>
                    @if ($from > 2) <span class="page-info">&hellip;</span> @endif
                @endif
                @for ($i = $from; $i <= $to; $i++)
                    <a href="{{ $klaperList->url($i) }}" class="page-btn {{ $i === $cur ? 'current' : '' }}">{{ $i }}</a>
                @endfor
                @if ($to < $last)
                    @if ($to < $last - 1) <span class="page-info">&hellip;</span> @endif
                    <a href="{{ $klaperList->url($last) }}" class="page-btn">{{ $last }}</a>
                @endif
                @if ($klaperList->hasMorePages())
                    <a href="{{ $klaperList->nextPageUrl() }}" class="page-btn" title="Selanjutnya"><i class="fas fa-chevron-right"></i></a>
                @else
                    <span class="page-btn disabled"><i class="fas fa-chevron-right"></i></span>
                @endif
            </div>
        @endif

    @elseif ($activeTab === 'mutasi')
        <!-- TAB 2: MUTASI SISWA -->
        <div class="card" style="padding: 16px; margin-bottom: 20px;">
            <div style="display: flex; flex-wrap: wrap; gap: 12px; justify-content: space-between; align-items: center;">
                <div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
                    <div class="toolbar-entries">
                        <label for="perPageSelect" style="margin: 0;">Tampilkan</label>
                        <select id="perPageSelect" class="per-page-select">
                            @foreach ([10, 15, 25, 50, 100] as $n)
                                <option value="{{ $n }}" {{ ($perPage ?? 25) == $n ? 'selected' : '' }}>{{ $n }}</option>
                            @endforeach
                        </select>
                        <span>entri</span>
                    </div>

                    @if (!empty($q))
                        <a href="{{ route('dashboard.kesiswaan.administrasi.index', ['tab' => 'mutasi']) }}"
                            class="btn btn-outline" style="padding: 7px 12px; font-size: 0.84rem;"
                            title="Reset filter">
                            <i class="fas fa-undo"></i>
                        </a>
                    @endif
                </div>

                <div class="live-search-wrap">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" id="liveSearch" placeholder="Cari siswa / no. surat / sekolah..." value="{{ $q ?? '' }}" autocomplete="off">
                    <button type="button" id="clearSearch" class="clear-search {{ !empty($q) ? 'visible' : '' }}" title="Hapus pencarian">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        </div>

        <div class="card table-responsive-stack" id="tableDataContainer" style="padding: 0; margin-bottom: 24px;">
            <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                <thead>
                    <tr style="background: rgba(0,0,0,0.02); border-bottom: 1px solid var(--border-color);">
                        <th style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 170px;">No. Surat Mutasi</th>
                        <th style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Nama Siswa</th>
                        <th style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 120px; text-align: center;">Jenis Mutasi</th>
                        <th style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 120px;">Tanggal</th>
                        <th style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Sekolah Tujuan / Asal</th>
                        <th style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 100px; text-align: center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($mutasiList as $item)
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 12px 16px; font-family: monospace; font-size: 0.82rem; font-weight: 700; color: var(--primary);">
                                {{ $item->nomor_surat_mutasi ?: '-' }}
                            </td>
                            <td style="padding: 12px 16px; font-weight: 700; color: var(--text-color);">
                                {{ $item->siswa?->nama ?: 'Siswa #' . $item->peserta_didik_id }}
                                <div style="font-size: 0.75rem; color: var(--text-muted);">NISN: {{ $item->siswa?->nisn ?: '-' }}</div>
                            </td>
                            <td style="padding: 12px 16px; text-align: center;">
                                <span class="badge {{ $item->jenis_mutasi === 'masuk' ? 'badge-success' : 'badge-danger' }}">
                                    {{ strtoupper($item->jenis_mutasi) }}
                                </span>
                            </td>
                            <td style="padding: 12px 16px; font-size: 0.85rem;">
                                {{ date('d/m/Y', strtotime($item->tanggal_mutasi)) }}
                            </td>
                            <td style="padding: 12px 16px; font-size: 0.85rem;">
                                {{ $item->sekolah_tujuan_asal ?: '-' }}
                            </td>
                            <td style="padding: 12px 16px; text-align: center;">
                                <div class="table-actions" style="display: flex; gap: 4px; justify-content: center;">
                                    <a href="{{ route('dashboard.kesiswaan.administrasi.mutasi.cetak', $item->id) }}" target="_blank"
                                        class="btn-icon" title="Cetak Surat Mutasi">
                                        <i class="fas fa-print"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 40px 16px; color: var(--text-muted);">
                                <i class="fas fa-folder-open" style="font-size: 2.2rem; opacity: 0.3; margin-bottom: 10px; display: block;"></i>
                                Tidak ada catatan mutasi siswa yang cocok dengan filter pencarian.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($mutasiList->hasPages())
            <div class="custom-pagination" style="margin-bottom: 24px;">
                @if ($mutasiList->onFirstPage())
                    <span class="page-btn disabled"><i class="fas fa-chevron-left"></i></span>
                @else
                    <a href="{{ $mutasiList->previousPageUrl() }}" class="page-btn" title="Sebelumnya"><i class="fas fa-chevron-left"></i></a>
                @endif
                @php
                    $cur = $mutasiList->currentPage();
                    $last = $mutasiList->lastPage();
                    $from = max(1, $cur - 2);
                    $to = min($last, $cur + 2);
                @endphp
                @if ($from > 1)
                    <a href="{{ $mutasiList->url(1) }}" class="page-btn">1</a>
                    @if ($from > 2) <span class="page-info">&hellip;</span> @endif
                @endif
                @for ($i = $from; $i <= $to; $i++)
                    <a href="{{ $mutasiList->url($i) }}" class="page-btn {{ $i === $cur ? 'current' : '' }}">{{ $i }}</a>
                @endfor
                @if ($to < $last)
                    @if ($to < $last - 1) <span class="page-info">&hellip;</span> @endif
                    <a href="{{ $mutasiList->url($last) }}" class="page-btn">{{ $last }}</a>
                @endif
                @if ($mutasiList->hasMorePages())
                    <a href="{{ $mutasiList->nextPageUrl() }}" class="page-btn" title="Selanjutnya"><i class="fas fa-chevron-right"></i></a>
                @else
                    <span class="page-btn disabled"><i class="fas fa-chevron-right"></i></span>
                @endif
            </div>
        @endif

    @elseif ($activeTab === 'kelulusan')
        <!-- TAB 3: KELULUSAN & SKL -->
        <div class="card" style="padding: 16px; margin-bottom: 20px;">
            <div style="display: flex; flex-wrap: wrap; gap: 12px; justify-content: space-between; align-items: center;">
                <div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
                    <div class="toolbar-entries">
                        <label for="perPageSelect" style="margin: 0;">Tampilkan</label>
                        <select id="perPageSelect" class="per-page-select">
                            @foreach ([10, 15, 25, 50, 100] as $n)
                                <option value="{{ $n }}" {{ ($perPage ?? 25) == $n ? 'selected' : '' }}>{{ $n }}</option>
                            @endforeach
                        </select>
                        <span>entri</span>
                    </div>

                    @if (!empty($q) || !empty($tahunAjaran))
                        <a href="{{ route('dashboard.kesiswaan.administrasi.index', ['tab' => 'kelulusan']) }}"
                            class="btn btn-outline" style="padding: 7px 12px; font-size: 0.84rem;"
                            title="Reset filter">
                            <i class="fas fa-undo"></i>
                        </a>
                    @endif
                </div>

                <div class="live-search-wrap">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" id="liveSearch" placeholder="Cari nama / NISN / no. SKL..." value="{{ $q ?? '' }}" autocomplete="off">
                    <button type="button" id="clearSearch" class="clear-search {{ !empty($q) ? 'visible' : '' }}" title="Hapus pencarian">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        </div>

        <div class="card table-responsive-stack" id="tableDataContainer" style="padding: 0; margin-bottom: 24px;">
            <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                <thead>
                    <tr style="background: rgba(0,0,0,0.02); border-bottom: 1px solid var(--border-color);">
                        <th style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 170px;">No. SKL</th>
                        <th style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Nama Siswa</th>
                        <th style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 130px;">Tahun Ajaran</th>
                        <th style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">No. Ujian / Ijazah</th>
                        <th style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 120px; text-align: center;">Status</th>
                        <th style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 100px; text-align: center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($kelulusanList as $item)
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 12px 16px; font-family: monospace; font-size: 0.82rem; font-weight: 700; color: var(--primary);">
                                {{ $item->nomor_skl ?: '-' }}
                            </td>
                            <td style="padding: 12px 16px; font-weight: 700; color: var(--text-color);">
                                {{ $item->siswa?->nama ?: 'Siswa #' . $item->peserta_didik_id }}
                                <div style="font-size: 0.75rem; color: var(--text-muted);">NISN: {{ $item->siswa?->nisn ?: '-' }}</div>
                            </td>
                            <td style="padding: 12px 16px; font-size: 0.85rem;">
                                {{ $item->tahun_ajaran }}
                            </td>
                            <td style="padding: 12px 16px; font-family: monospace; font-size: 0.82rem;">
                                {{ $item->nomor_peserta_ujian ?: '-' }} / {{ $item->nomor_ijazah ?: '-' }}
                            </td>
                            <td style="padding: 12px 16px; text-align: center;">
                                <span class="badge {{ $item->status_kelulusan === 'lulus' ? 'badge-success' : 'badge-danger' }}">
                                    {{ strtoupper($item->status_kelulusan) }}
                                </span>
                            </td>
                            <td style="padding: 12px 16px; text-align: center;">
                                <div class="table-actions" style="display: flex; gap: 4px; justify-content: center;">
                                    <a href="{{ route('dashboard.kesiswaan.administrasi.kelulusan.cetak-skl', $item->id) }}" target="_blank"
                                        class="btn-icon" title="Cetak Surat Keterangan Lulus (SKL)">
                                        <i class="fas fa-print"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 40px 16px; color: var(--text-muted);">
                                <i class="fas fa-folder-open" style="font-size: 2.2rem; opacity: 0.3; margin-bottom: 10px; display: block;"></i>
                                Tidak ada data penetapan kelulusan siswa yang cocok dengan filter pencarian.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($kelulusanList->hasPages())
            <div class="custom-pagination" style="margin-bottom: 24px;">
                @if ($kelulusanList->onFirstPage())
                    <span class="page-btn disabled"><i class="fas fa-chevron-left"></i></span>
                @else
                    <a href="{{ $kelulusanList->previousPageUrl() }}" class="page-btn" title="Sebelumnya"><i class="fas fa-chevron-left"></i></a>
                @endif
                @php
                    $cur = $kelulusanList->currentPage();
                    $last = $kelulusanList->lastPage();
                    $from = max(1, $cur - 2);
                    $to = min($last, $cur + 2);
                @endphp
                @if ($from > 1)
                    <a href="{{ $kelulusanList->url(1) }}" class="page-btn">1</a>
                    @if ($from > 2) <span class="page-info">&hellip;</span> @endif
                @endif
                @for ($i = $from; $i <= $to; $i++)
                    <a href="{{ $kelulusanList->url($i) }}" class="page-btn {{ $i === $cur ? 'current' : '' }}">{{ $i }}</a>
                @endfor
                @if ($to < $last)
                    @if ($to < $last - 1) <span class="page-info">&hellip;</span> @endif
                    <a href="{{ $kelulusanList->url($last) }}" class="page-btn">{{ $last }}</a>
                @endif
                @if ($kelulusanList->hasMorePages())
                    <a href="{{ $kelulusanList->nextPageUrl() }}" class="page-btn" title="Selanjutnya"><i class="fas fa-chevron-right"></i></a>
                @else
                    <span class="page-btn disabled"><i class="fas fa-chevron-right"></i></span>
                @endif
            </div>
        @endif
    @endif

    <!-- MODAL 1: CATAT MUTASI SISWA -->
    <div id="modalMutasi" class="modal-backdrop" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.65); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(5px);">
        <div class="card" style="max-width: 520px; width: 92%; max-height: 90vh; display: flex; flex-direction: column; margin: 0; border-radius: 14px; padding: 22px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 1px solid var(--border-color);">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div style="width: 36px; height: 36px; border-radius: 8px; background: rgba(239,68,68,0.15); display: flex; align-items: center; justify-content: center; color: #ef4444;">
                        <i class="fas fa-person-walking-dashed-line-arrow-right"></i>
                    </div>
                    <div>
                        <h3 style="font-size: 1.05rem; font-weight: 700; color: var(--text-color); margin: 0;">Catat Mutasi Siswa</h3>
                    </div>
                </div>
                <button type="button" class="close-modal" data-target="#modalMutasi" style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 1.1rem; padding: 4px; display: inline-flex; align-items: center; justify-content: center; transition: color 0.2s ease;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div style="overflow-y: auto; flex: 1; padding-right: 4px;">
                <form id="formMutasi" method="POST" action="{{ route('dashboard.kesiswaan.administrasi.mutasi.store') }}">
                    @csrf
                    <div class="form-group" style="margin-bottom: 14px;">
                        <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">Pilih Peserta Didik <span class="text-danger">*</span></label>
                        <select name="peserta_didik_id" class="form-control" required style="width: 100%; border-radius: 8px;">
                            <option value="">-- Cari dan Pilih Siswa --</option>
                            @foreach ($siswaList as $sw)
                                <option value="{{ $sw->peserta_didik_id }}">{{ $sw->nama }} (NISN: {{ $sw->nisn ?: '-' }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                        <div class="form-group">
                            <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">Jenis Mutasi <span class="text-danger">*</span></label>
                            <select name="jenis_mutasi" class="form-control" required style="width: 100%; border-radius: 8px;">
                                <option value="keluar">Mutasi Keluar</option>
                                <option value="masuk">Mutasi Masuk</option>
                                <option value="do">Drop Out (DO)</option>
                                <option value="meninggal">Meninggal Dunia</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">Tanggal Mutasi <span class="text-danger">*</span></label>
                            <input type="date" name="tanggal_mutasi" class="form-control" value="{{ date('Y-m-d') }}" required style="width: 100%; border-radius: 8px;">
                        </div>
                    </div>
                    <div class="form-group" style="margin-bottom: 14px;">
                        <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">Sekolah Tujuan / Asal</label>
                        <input type="text" name="sekolah_tujuan_asal" class="form-control" placeholder="Contoh: SMKN 2 Bandung" style="width: 100%; border-radius: 8px;">
                    </div>
                    <div class="form-group" style="margin-bottom: 18px;">
                        <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">Alasan Mutasi <span class="text-danger">*</span></label>
                        <input type="text" name="alasan" class="form-control" placeholder="Contoh: Mengikuti domisili orang tua" required style="width: 100%; border-radius: 8px;">
                    </div>
                    <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 18px; border-top: 1px solid var(--border-color); padding-top: 14px;">
                        <button type="button" class="btn btn-outline close-modal" data-target="#modalMutasi" style="padding: 8px 16px; font-size: 0.82rem;">Batal</button>
                        <button type="submit" class="btn btn-primary" style="padding: 8px 18px; font-size: 0.82rem;"><i class="fas fa-save me-1"></i> Simpan Mutasi</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL 2: CATAT KELULUSAN SISWA -->
    <div id="modalKelulusan" class="modal-backdrop" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.65); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(5px);">
        <div class="card" style="max-width: 520px; width: 92%; max-height: 90vh; display: flex; flex-direction: column; margin: 0; border-radius: 14px; padding: 22px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 1px solid var(--border-color);">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div style="width: 36px; height: 36px; border-radius: 8px; background: rgba(245,158,11,0.15); display: flex; align-items: center; justify-content: center; color: #f59e0b;">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                    <div>
                        <h3 style="font-size: 1.05rem; font-weight: 700; color: var(--text-color); margin: 0;">Penetapan Kelulusan &amp; SKL</h3>
                    </div>
                </div>
                <button type="button" class="close-modal" data-target="#modalKelulusan" style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 1.1rem; padding: 4px; display: inline-flex; align-items: center; justify-content: center; transition: color 0.2s ease;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div style="overflow-y: auto; flex: 1; padding-right: 4px;">
                <form id="formKelulusan" method="POST" action="{{ route('dashboard.kesiswaan.administrasi.kelulusan.store') }}">
                    @csrf
                    <div class="form-group" style="margin-bottom: 14px;">
                        <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">Pilih Peserta Didik <span class="text-danger">*</span></label>
                        <select name="peserta_didik_id" class="form-control" required style="width: 100%; border-radius: 8px;">
                            <option value="">-- Cari dan Pilih Siswa --</option>
                            @foreach ($siswaList as $sw)
                                <option value="{{ $sw->peserta_didik_id }}">{{ $sw->nama }} (NISN: {{ $sw->nisn ?: '-' }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                        <div class="form-group">
                            <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">Tahun Ajaran <span class="text-danger">*</span></label>
                            <input type="text" name="tahun_ajaran" class="form-control" value="{{ (date('n') >= 7 ? date('Y').'/'.(date('Y')+1) : (date('Y')-1).'/'.date('Y')) }}" required style="width: 100%; border-radius: 8px;">
                        </div>
                        <div class="form-group">
                            <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">Status Kelulusan <span class="text-danger">*</span></label>
                            <select name="status_kelulusan" class="form-control" required style="width: 100%; border-radius: 8px;">
                                <option value="lulus">Lulus</option>
                                <option value="tidak_lulus">Tidak Lulus</option>
                                <option value="ditunda">Ditunda</option>
                            </select>
                        </div>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 18px;">
                        <div class="form-group">
                            <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">No. Peserta Ujian</label>
                            <input type="text" name="nomor_peserta_ujian" class="form-control" placeholder="Contoh: 04-001-001-8" style="width: 100%; border-radius: 8px;">
                        </div>
                        <div class="form-group">
                            <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">No. Ijazah Nasional</label>
                            <input type="text" name="nomor_ijazah" class="form-control" placeholder="Contoh: M-SMK/06-000123" style="width: 100%; border-radius: 8px;">
                        </div>
                    </div>
                    <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 18px; border-top: 1px solid var(--border-color); padding-top: 14px;">
                        <button type="button" class="btn btn-outline close-modal" data-target="#modalKelulusan" style="padding: 8px 16px; font-size: 0.82rem;">Batal</button>
                        <button type="submit" class="btn btn-primary" style="padding: 8px 18px; font-size: 0.82rem;"><i class="fas fa-save me-1"></i> Simpan Kelulusan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/kesiswaan-administrasi.js') }}"></script>
@endpush
