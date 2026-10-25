@extends('layouts.dashboard')

@section('title', 'Administrasi Kepegawaian GTK - SAE')

@section('content')
    <!-- 1. Header Banner Baku SAE -->
    <div class="dash-banner">
        <div>
            <h2 style="font-size: var(--text-2xl, 1.5rem); font-weight: 800; color: var(--text-color); margin-bottom: 4px;">
                <i class="fas fa-id-card-alt text-primary me-2"></i> Administrasi Kepegawaian GTK
            </h2>
            <p style="color: var(--text-muted); font-size: var(--text-base, 0.875rem); margin: 0;">
                Pengelolaan arsip berkas pegawai guru &amp; tendik, kenaikan gaji berkala (KGB), serta permohonan cuti dan
                izin resmi.
            </p>
        </div>

        <div class="dash-banner-actions">
            <a href="{{ route('dashboard.tendik', ['bidang' => 'kepegawaian']) }}" class="btn btn-outline"
                style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px); font-size: var(--text-md, 1rem);"
                title="Dashboard Ringkasan Kepegawaian">
                <i class="fas fa-gauge-high text-primary"></i>
            </a>

            @if ($tab === 'pegawai' || $tab === 'berkas' || $tab === 'guru' || $tab === 'tendik')
                <button type="button" class="btn btn-primary" id="btnOpenModalUploadBerkas"
                    style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px); font-size: var(--text-md, 1rem);"
                    title="Unggah Berkas Digital GTK">
                    <i class="fas fa-upload"></i>
                </button>
            @elseif($tab === 'kgb')
                <button type="button" class="btn btn-primary" id="btnOpenModalKgb"
                    style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px); font-size: var(--text-md, 1rem);"
                    title="Catat Usulan KGB Baru">
                    <i class="fas fa-plus"></i>
                </button>
            @elseif($tab === 'cuti')
                <button type="button" class="btn btn-primary" id="btnOpenModalCuti"
                    style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px); font-size: var(--text-md, 1rem);"
                    title="Buat Pengajuan Cuti / Izin">
                    <i class="fas fa-plus"></i>
                </button>
            @endif
        </div>
    </div>

    <!-- 2. Flash Messages -->
    @if (session('success'))
        <div
            style="background: rgba(16,185,129,0.12); border: 1px solid rgba(16,185,129,0.3); color: #10b981; padding: 12px 16px; border-radius: var(--radius-lg, 12px); margin-bottom: 18px; font-size: var(--text-base, 0.875rem); display: flex; align-items: center; gap: 8px;">
            <i class="fas fa-check-circle"></i>
            <div>{{ session('success') }}</div>
        </div>
    @endif
    @if (session('error'))
        <div
            style="background: rgba(239,68,68,0.12); border: 1px solid rgba(239,68,68,0.3); color: #ef4444; padding: 12px 16px; border-radius: var(--radius-lg, 12px); margin-bottom: 18px; font-size: var(--text-base, 0.875rem); display: flex; align-items: center; gap: 8px;">
            <i class="fas fa-circle-exclamation"></i>
            <div>{{ session('error') }}</div>
        </div>
    @endif

    <!-- 3. Stat Grid Baku SAE -->
    <div class="dash-stat-grid" style="margin-bottom: 20px;">
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(99,102,241,0.12); color: var(--primary);">
                <i class="fas fa-users"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">
                    {{ number_format(($stats['total_guru'] ?? 0) + ($stats['total_tendik'] ?? 0)) }}</div>
                <div class="dash-stat-label">Total Pegawai GTK</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(16,185,129,0.12); color: #10b981;">
                <i class="fas fa-chalkboard-user"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ number_format($stats['total_guru'] ?? 0) }}</div>
                <div class="dash-stat-label">Pendidik (Guru)</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(6,182,212,0.12); color: var(--accent);">
                <i class="fas fa-id-badge"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ number_format($stats['total_tendik'] ?? 0) }}</div>
                <div class="dash-stat-label">Tenaga Kependidikan</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(245,158,11,0.12); color: #f59e0b;">
                <i class="fas fa-business-time"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ number_format($stats['kgb_jatuh_tempo'] ?? 0) }}</div>
                <div class="dash-stat-label">KGB Jatuh Tempo (&le; 90 Hari)</div>
            </div>
        </div>
    </div>

    <!-- Alert Notifikasi KGB Jatuh Tempo -->
    @if ($kgbJatuhTempoCount > 0)
        <div
            style="background: rgba(245, 158, 11, 0.1); border-left: 4px solid #f59e0b; padding: 12px 16px; border-radius: var(--radius-md, 8px); margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; gap: 12px;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-bell" style="color: #f59e0b; font-size: var(--text-xl, 1.25rem);"></i>
                <span style="font-size: var(--text-base, 0.875rem); color: #92400e;">
                    <strong>{{ $kgbJatuhTempoCount }} GTK</strong> jatuh tempo Kenaikan Gaji Berkala (KGB) dalam kurun waktu
                    90 hari ke depan.
                </span>
            </div>
            <a href="{{ route('dashboard.kepegawaian.index', ['tab' => 'kgb']) }}" class="btn btn-outline"
                style="border-color: #f59e0b; color: #b45309; padding: 4px 12px; font-size: var(--text-sm, 0.8125rem); font-weight: 600;"
                title="Buka KGB Tracker">
                Buka KGB Tracker <i class="fas fa-arrow-right ms-1"></i>
            </a>
        </div>
    @endif

    <!-- 4. Tab Navigasi Baku SAE -->
    <div class="periode-nav-wrapper" style="margin-bottom: 20px;">
        <div class="periode-nav-desktop">
            <a href="{{ route('dashboard.kepegawaian.index', ['tab' => 'pegawai']) }}"
                class="periode-nav-tab {{ $tab === 'pegawai' || $tab === 'guru' ? 'active' : '' }}">
                <i class="fas fa-users"></i> Pegawai
            </a>
            <a href="{{ route('dashboard.kepegawaian.index', ['tab' => 'berkas']) }}"
                class="periode-nav-tab {{ $tab === 'berkas' || $tab === 'tendik' ? 'active' : '' }}">
                <i class="fas fa-folder-open"></i> Berkas Pegawai
            </a>
            <a href="{{ route('dashboard.kepegawaian.index', ['tab' => 'kgb']) }}"
                class="periode-nav-tab {{ $tab === 'kgb' ? 'active' : '' }}">
                <i class="fas fa-business-time"></i> KGB Tracker
                @if ($kgbJatuhTempoCount > 0)
                    <span
                        style="background: #f59e0b; color: white; font-size: var(--text-xs, 0.75rem); padding: 2px 7px; border-radius: var(--radius-lg, 12px); margin-left: 4px;">{{ $kgbJatuhTempoCount }}</span>
                @endif
            </a>
            <a href="{{ route('dashboard.kepegawaian.index', ['tab' => 'cuti']) }}"
                class="periode-nav-tab {{ $tab === 'cuti' ? 'active' : '' }}">
                <i class="fas fa-plane-departure"></i> Cuti &amp; Izin
            </a>
        </div>
    </div>

    <!-- Unified Datatable Kepegawaian GTK (Toolbar + Table + Pagination dalam 1 Card Terpadu) -->
    <div class="card datatable-card-wrapper" id="tableDataContainer">
        <!-- 1. Toolbar Header Datatable Baku SAE (Entries, Filters & Live Search) -->
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

                    @if ($tab === 'pegawai' || $tab === 'berkas' || $tab === 'guru' || $tab === 'tendik')
                        <!-- Filter Jenis PTK -->
                        <select id="filterJenis" name="jenis_ptk" class="toolbar-filter-select" style="height: 38px;">
                            <option value="">Semua Jenis PTK</option>
                            @foreach ($jenisPtkList as $j)
                                <option value="{{ $j }}" {{ ($filterJenis ?? '') === $j ? 'selected' : '' }}>
                                    {{ $j }}</option>
                            @endforeach
                        </select>

                        <!-- Filter Status Kepegawaian -->
                        <select id="filterStatus" name="status" class="toolbar-filter-select" style="height: 38px;">
                            <option value="">Semua Status Kepegawaian</option>
                            @foreach ($statusKepegawaianList as $s)
                                <option value="{{ $s }}" {{ ($filterStatus ?? '') === $s ? 'selected' : '' }}>
                                    {{ $s }}</option>
                            @endforeach
                        </select>

                        @if ($tab === 'pegawai' || $tab === 'guru')
                            <!-- Filter Gender -->
                            <select id="filterGender" name="gender" class="toolbar-filter-select"
                                style="min-width: 120px; height: 38px;">
                                <option value="">Semua Gender</option>
                                <option value="L" {{ ($filterGender ?? '') === 'L' ? 'selected' : '' }}>Laki-laki (L)
                                </option>
                                <option value="P" {{ ($filterGender ?? '') === 'P' ? 'selected' : '' }}>Perempuan (P)
                                </option>
                            </select>
                        @endif
                    @endif

                    @if (!empty($search) || !empty($filterJenis) || !empty($filterStatus) || !empty($filterGender))
                        <a href="{{ route('dashboard.kepegawaian.index', ['tab' => $tab]) }}"
                            class="btn btn-outline btn-reset-filter"
                            style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px);"
                            title="Reset filter & pencarian" data-live-reset>
                            <i class="fas fa-undo"></i>
                        </a>
                    @endif
                </div>

                <div class="live-search-wrap" style="height: 38px;">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" id="liveSearch" name="q"
                        placeholder="{{ $tab === 'kgb' ? 'Cari nama GTK / NIP / nomor SK...' : ($tab === 'cuti' ? 'Cari nama GTK / keperluan...' : 'Cari nama / NIP / NUPTK / NIK...') }}"
                        value="{{ $search ?? '' }}" autocomplete="off">
                    <button type="button" id="clearSearch" class="clear-search {{ !empty($search) ? 'visible' : '' }}"
                        title="Hapus pencarian">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- 2. Konten Data Sesuai Tab Aktif -->

        {{-- TAB 1: PEGAWAI (GURU & TENDIK LENGKAP) --}}
        @if ($tab === 'pegawai' || $tab === 'guru')
            <div class="table-responsive-stack" style="overflow-x: auto; width: 100%;">
                <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                    <thead>
                        <tr style="border-bottom: 1px solid var(--border-color); background: rgba(0,0,0,0.02);">
                            <th
                                style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 50px; text-align: center;">
                                No</th>
                            <th
                                style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Nama Lengkap Pegawai</th>
                            <th
                                style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                NUPTK / NIP</th>
                            <th
                                style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 70px;">
                                L/P</th>
                            <th
                                style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Jenis PTK</th>
                            <th
                                style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Status</th>
                            <th
                                style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Pendidikan Terakhir</th>
                            <th
                                style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 110px;">
                                Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($pegawaiList as $idx => $item)
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <td data-label="No"
                                    style="padding: 12px 18px; font-size: var(--text-base, 0.875rem); color: var(--text-muted); text-align: center;">
                                    {{ $pegawaiList->firstItem() + $idx }}
                                </td>
                                <td data-label="Nama" style="padding: 12px 18px;">
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <div
                                            style="width: 32px; height: 32px; border-radius: var(--radius-md, 8px); background: rgba(99,102,241,0.12); display: flex; align-items: center; justify-content: center; color: var(--primary); font-size: var(--text-base, 0.875rem); flex-shrink: 0;">
                                            <i
                                                class="fas {{ str_contains(strtolower($item->jenis_ptk_id_str ?? ''), 'guru') ? 'fa-chalkboard-user' : 'fa-id-badge' }}"></i>
                                        </div>
                                        <div>
                                            <div style="font-weight: 700; color: var(--text-color); font-size: var(--text-base, 0.875rem);">
                                                {{ $item->nama }}</div>
                                            @if (!empty($item->nik))
                                                <div
                                                    style="font-size: var(--text-xs, 0.75rem); font-family: monospace; color: var(--text-muted);">
                                                    NIK: <span class="copyable" data-copy="{{ $item->nik }}"
                                                        title="Klik salin">{{ $item->nik }}</span>
                                                </div>
                                            @endif
                                            @if ($item->email)
                                                <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted);">
                                                    {{ $item->email }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td data-label="NUPTK/NIP"
                                    style="padding: 12px 18px; font-family: monospace; font-size: var(--text-base, 0.875rem);">
                                    <div style="color: var(--primary); font-weight: 600;">
                                        {{ $item->nuptk ? $item->nuptk : '-' }}
                                    </div>
                                    @if ($item->nip)
                                        <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted);">
                                            NIP: {{ $item->nip }}
                                        </div>
                                    @endif
                                </td>
                                <td data-label="L/P" style="padding: 12px 18px; text-align: center;">
                                    <span
                                        class="badge {{ $item->jenis_kelamin === 'L' ? 'badge-primary' : 'badge-danger' }}"
                                        style="font-size: var(--text-xs, 0.75rem); padding: 2px 7px;">
                                        {{ $item->jenis_kelamin ?: '-' }}
                                    </span>
                                </td>
                                <td data-label="Jenis PTK"
                                    style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); color: var(--text-color);">
                                    <span class="badge-compact"
                                        style="background: rgba(99,102,241,0.1); color: var(--primary);">
                                        {{ $item->jenis_ptk_id_str ?: 'GTK' }}
                                    </span>
                                </td>
                                <td data-label="Status" style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem);">
                                    <span
                                        class="badge {{ str_contains(strtoupper($item->status_kepegawaian_id_str ?? ''), 'PNS') ? 'badge-primary' : 'badge-outline' }}"
                                        style="font-size: var(--text-xs, 0.75rem); padding: 3px 8px;">
                                        {{ $item->status_kepegawaian_id_str ?: '-' }}
                                    </span>
                                </td>
                                <td data-label="Pendidikan"
                                    style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); color: var(--text-muted);">
                                    <div style="color: var(--text-color); font-weight: 600;">
                                        {{ $item->pendidikan_terakhir ?: '-' }}</div>
                                    @if ($item->bidang_studi_terakhir)
                                        <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted);">
                                            {{ $item->bidang_studi_terakhir }}</div>
                                    @endif
                                </td>
                                <td data-label="Aksi" style="padding: 12px 18px; text-align: center;">
                                    <div class="table-actions" style="justify-content: center; gap: 6px;">
                                        <button type="button" class="btn-icon btn-detail-pegawai"
                                            data-id="{{ $item->ptk_id }}" title="Rincian Lengkap Pegawai">
                                            <i class="fas fa-id-card" style="color: var(--primary);"></i>
                                        </button>
                                        <button type="button" class="btn-icon btn-upload-gtk-berkas"
                                            data-id="{{ $item->ptk_id }}" data-nama="{{ $item->nama }}"
                                            title="Unggah Berkas">
                                            <i class="fas fa-upload" style="color: #10b981;"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8"
                                    style="text-align: center; padding: 36px 16px; color: var(--text-muted);">
                                    <i class="fas fa-users mb-2"
                                        style="font-size: var(--text-3xl, 1.875rem); opacity: 0.5; display: block;"></i>
                                    <div>Tidak ada data pegawai GTK ditemukan.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($pegawaiList->hasPages())
                <div class="datatable-footer">
                    @include('partials.datatable-pagination', ['paginator' => $pegawaiList])
                </div>
            @endif

            {{-- TAB 2: BERKAS PEGAWAI --}}
        @elseif($tab === 'berkas' || $tab === 'tendik')
            <div class="table-responsive-stack" style="overflow-x: auto; width: 100%;">
                <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                    <thead>
                        <tr style="border-bottom: 1px solid var(--border-color); background: rgba(0,0,0,0.02);">
                            <th
                                style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 50px; text-align: center;">
                                No</th>
                            <th
                                style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Identitas Pegawai</th>
                            <th
                                style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Jenis PTK &amp; Status</th>
                            <th
                                style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Berkas Digital Tersimpan</th>
                            <th
                                style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 100px;">
                                Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($berkasList as $idx => $gtk)
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <td data-label="No"
                                    style="padding: 12px 18px; font-size: var(--text-base, 0.875rem); color: var(--text-muted); text-align: center;">
                                    {{ $berkasList->firstItem() + $idx }}
                                </td>
                                <td data-label="Identitas Pegawai" style="padding: 12px 18px;">
                                    <div style="font-weight: 700; color: var(--text-color); font-size: var(--text-base, 0.875rem);">
                                        {{ $gtk->nama }}</div>
                                    <div style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); margin-top: 2px;">
                                        NIP: {{ $gtk->nip ?: '-' }} &bull; NUPTK: {{ $gtk->nuptk ?: '-' }}
                                    </div>
                                </td>
                                <td data-label="Jenis PTK & Status" style="padding: 12px 18px;">
                                    <div style="display: flex; flex-direction: column; gap: 4px;">
                                        <span class="badge-compact"
                                            style="background: rgba(99,102,241,0.1); color: var(--primary);">
                                            <i
                                                class="fas {{ str_contains(strtolower($gtk->jenis_ptk_id_str ?? ''), 'guru') ? 'fa-chalkboard-user' : 'fa-id-badge' }} me-1"></i>
                                            {{ $gtk->jenis_ptk_id_str ?: 'GTK' }}
                                        </span>
                                        <span style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted);">
                                            {{ $gtk->status_kepegawaian_id_str ?: 'Aktif' }}
                                        </span>
                                    </div>
                                </td>
                                <td data-label="Berkas Digital" style="padding: 12px 18px;">
                                    @if ($gtk->berkas->isNotEmpty())
                                        <div style="display: flex; flex-wrap: wrap; gap: 6px;">
                                            @foreach ($gtk->berkas as $b)
                                                <span
                                                    style="display: inline-flex; align-items: center; gap: 4px; padding: 4px 8px; background: var(--bg-hover); border: 1px solid var(--border-color); border-radius: var(--radius-sm, 6px); font-size: var(--text-sm, 0.8125rem);">
                                                    <i class="fas fa-file-pdf" style="color: #ef4444;"></i>
                                                    <a href="{{ asset('storage/' . $b->file_path) }}" target="_blank"
                                                        style="color: var(--text-color); text-decoration: none; font-weight: 500;">
                                                        {{ $b->judul_dokumen }}
                                                    </a>
                                                    <form
                                                        action="{{ route('dashboard.kepegawaian.berkas.delete', $b->id) }}"
                                                        method="POST" style="display: inline;" data-confirm="delete"
                                                        data-name="{{ $b->judul_dokumen }}">
                                                        @csrf
                                                        @method('DELETE')
                                                        <input type="hidden" name="tab_redirect" value="berkas">
                                                        <button type="submit"
                                                            style="background: none; border: none; color: #ef4444; cursor: pointer; padding: 0 2px; font-size: var(--text-base, 0.875rem);"
                                                            title="Hapus berkas">&times;</button>
                                                    </form>
                                                </span>
                                            @endforeach
                                        </div>
                                    @else
                                        <span
                                            style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); font-style: italic;">Belum
                                            ada berkas digital</span>
                                    @endif
                                </td>
                                <td data-label="Aksi" style="padding: 12px 18px; text-align: center;">
                                    <div class="table-actions" style="justify-content: center;">
                                        <button type="button" class="btn-icon btn-upload-gtk-berkas"
                                            data-id="{{ $gtk->ptk_id }}" data-nama="{{ $gtk->nama }}"
                                            title="Unggah Berkas Pegawai">
                                            <i class="fas fa-upload"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5"
                                    style="text-align: center; padding: 36px 16px; color: var(--text-muted);">
                                    <i class="fas fa-folder-open mb-2"
                                        style="font-size: var(--text-3xl, 1.875rem); opacity: 0.5; display: block;"></i>
                                    <div>Tidak ada data berkas pegawai ditemukan.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($berkasList->hasPages())
                <div class="datatable-footer">
                    @include('partials.datatable-pagination', ['paginator' => $berkasList])
                </div>
            @endif

            {{-- TAB 3: KGB TRACKER --}}
        @elseif($tab === 'kgb')
            <div class="table-responsive-stack" style="overflow-x: auto; width: 100%;">
                <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                    <thead>
                        <tr style="border-bottom: 1px solid var(--border-color); background: rgba(0,0,0,0.02);">
                            <th
                                style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 60px; text-align: center;">
                                No</th>
                            <th
                                style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Nama &amp; NIP Pegawai</th>
                            <th
                                style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                TMT KGB Terakhir</th>
                            <th
                                style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Target KGB Berikutnya</th>
                            <th
                                style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Status Usulan</th>
                            <th
                                style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 100px;">
                                Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($kgbList as $idx => $item)
                            @php
                                $target = \Carbon\Carbon::parse($item->tmt_baru_target);
                                $diffDays = (int) \Carbon\Carbon::today()->diffInDays($target, false);
                                $isJatuhTempo = $diffDays <= 90 && $diffDays >= 0;
                                $isLewat = $diffDays < 0;
                            @endphp
                            <tr
                                style="border-bottom: 1px solid var(--border-color); {{ $isJatuhTempo ? 'background: rgba(245, 158, 11, 0.05);' : '' }}">
                                <td data-label="No"
                                    style="padding: 12px 18px; font-size: var(--text-base, 0.875rem); color: var(--text-muted); text-align: center;">
                                    {{ $kgbList->firstItem() + $idx }}
                                </td>
                                <td data-label="Nama & NIP" style="padding: 12px 18px;">
                                    <div style="font-weight: 700; color: var(--text-color); font-size: var(--text-base, 0.875rem);">
                                        {{ $item->gtk?->nama ?? '-' }}</div>
                                    <div style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); margin-top: 2px;">
                                        NIP: {{ $item->gtk?->nip ?: '-' }} &bull; MKG: {{ $item->mkg_tahun }} Thn
                                        {{ $item->mkg_bulan }} Bln
                                    </div>
                                </td>
                                <td data-label="TMT Terakhir" style="padding: 12px 18px;">
                                    <div style="font-size: var(--text-base, 0.875rem); color: var(--text-color);">
                                        <i class="far fa-calendar-alt text-muted me-1"></i>
                                        {{ \Carbon\Carbon::parse($item->tmt_lama)->format('d/m/Y') }}
                                    </div>
                                    <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-top: 2px;">
                                        SK: {{ $item->nomor_sk_terakhir ?: '-' }}
                                    </div>
                                </td>
                                <td data-label="Target Berikutnya" style="padding: 12px 18px;">
                                    <div
                                        style="font-size: var(--text-base, 0.875rem); font-weight: 600; color: {{ $isLewat ? '#ef4444' : ($isJatuhTempo ? '#d97706' : 'var(--text-color)') }};">
                                        <i class="far fa-clock me-1"></i> {{ $target->format('d/m/Y') }}
                                    </div>
                                    <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-top: 2px;">
                                        @if ($isLewat)
                                            <span style="color: #ef4444; font-weight: 600;">Lewat {{ abs($diffDays) }}
                                                hari</span>
                                        @elseif($isJatuhTempo)
                                            <span style="color: #d97706; font-weight: 600;">Jatuh tempo dlm
                                                {{ $diffDays }} hari</span>
                                        @else
                                            <span>{{ $diffDays }} hari lagi</span>
                                        @endif
                                    </div>
                                </td>
                                <td data-label="Status" style="padding: 12px 18px;">
                                    @php
                                        $statusLabels = [
                                            'belum_waktunya' => ['Belum Waktunya', 'badge-secondary'],
                                            'siap_diajukan' => ['Siap Diajukan', 'badge-warning'],
                                            'diproses' => ['Sedang Diproses', 'badge-info'],
                                            'terbit_sk' => ['Terbit SK', 'badge-success'],
                                        ];
                                        $st = $statusLabels[$item->status_usulan] ?? ['Status', 'badge-secondary'];
                                    @endphp
                                    <span class="badge {{ $st[1] }}">{{ $st[0] }}</span>
                                </td>
                                <td data-label="Aksi" style="padding: 12px 18px; text-align: center;">
                                    <div class="table-actions" style="justify-content: center;">
                                        <button type="button" class="btn-icon btn-edit-kgb"
                                            data-ptk-id="{{ $item->ptk_id }}" data-tmt-terakhir="{{ $item->tmt_lama }}"
                                            data-sk-terakhir="{{ $item->nomor_sk_terakhir }}"
                                            data-tmt-berikutnya="{{ $item->tmt_baru_target }}"
                                            data-mk-tahun="{{ $item->mkg_tahun }}"
                                            data-mk-bulan="{{ $item->mkg_bulan }}"
                                            data-status="{{ $item->status_usulan }}" data-catatan="{{ $item->catatan }}"
                                            title="Perbarui KGB">
                                            <i class="fas fa-pen-to-square"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6"
                                    style="text-align: center; padding: 36px 16px; color: var(--text-muted);">
                                    <i class="fas fa-business-time mb-2"
                                        style="font-size: var(--text-3xl, 1.875rem); opacity: 0.5; display: block;"></i>
                                    <div>Belum ada data tracker KGB yang dicatat.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($kgbList->hasPages())
                <div class="datatable-footer">
                    @include('partials.datatable-pagination', ['paginator' => $kgbList])
                </div>
            @endif

            {{-- TAB 4: CUTI & IZIN GTK --}}
        @elseif($tab === 'cuti')
            <div class="table-responsive-stack" style="overflow-x: auto; width: 100%;">
                <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                    <thead>
                        <tr style="border-bottom: 1px solid var(--border-color); background: rgba(0,0,0,0.02);">
                            <th
                                style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 60px; text-align: center;">
                                No</th>
                            <th
                                style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Nama Pegawai</th>
                            <th
                                style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Jenis &amp; Keperluan</th>
                            <th
                                style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Rentang Waktu</th>
                            <th
                                style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Lampiran</th>
                            <th
                                style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 100px;">
                                Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($cutiList as $idx => $cuti)
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <td data-label="No"
                                    style="padding: 12px 18px; font-size: var(--text-base, 0.875rem); color: var(--text-muted); text-align: center;">
                                    {{ $cutiList->firstItem() + $idx }}
                                </td>
                                <td data-label="Nama Pegawai" style="padding: 12px 18px;">
                                    <div style="font-weight: 700; color: var(--text-color); font-size: var(--text-base, 0.875rem);">
                                        {{ $cuti->gtk?->nama ?? '-' }}</div>
                                    <div style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); margin-top: 2px;">
                                        NIP: {{ $cuti->gtk?->nip ?: '-' }}
                                    </div>
                                </td>
                                <td data-label="Jenis & Keperluan" style="padding: 12px 18px;">
                                    <span class="badge-compact"
                                        style="background: rgba(99,102,241,0.1); color: var(--primary);">
                                        {{ $cuti->jenis }}
                                    </span>
                                    <div style="font-size: var(--text-base, 0.875rem); color: var(--text-color); margin-top: 4px;">
                                        {{ $cuti->keperluan }}
                                    </div>
                                </td>
                                <td data-label="Rentang Waktu" style="padding: 12px 18px;">
                                    <div style="font-size: var(--text-base, 0.875rem); color: var(--text-color);">
                                        <i class="far fa-calendar-alt text-muted me-1"></i>
                                        {{ \Carbon\Carbon::parse($cuti->tanggal_mulai)->format('d/m/Y') }} s/d
                                        {{ \Carbon\Carbon::parse($cuti->tanggal_selesai)->format('d/m/Y') }}
                                    </div>
                                    <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-top: 2px;">
                                        Durasi: <strong>{{ $cuti->jumlah_hari }} Hari</strong>
                                    </div>
                                </td>
                                <td data-label="Lampiran" style="padding: 12px 18px;">
                                    @if ($cuti->file_pendukung)
                                        <a href="{{ asset('storage/' . $cuti->file_pendukung) }}" target="_blank"
                                            class="btn btn-outline"
                                            style="padding: 4px 10px; font-size: var(--text-sm, 0.8125rem); border-radius: var(--radius-sm, 6px);">
                                            <i class="fas fa-paperclip text-primary me-1"></i> Berkas
                                        </a>
                                    @else
                                        <span
                                            style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); font-style: italic;">Tanpa
                                            Lampiran</span>
                                    @endif
                                </td>
                                <td data-label="Aksi" style="padding: 12px 18px; text-align: center;">
                                    <div class="table-actions" style="justify-content: center;">
                                        <a href="{{ route('dashboard.kepegawaian.cuti.cetak', $cuti->id) }}"
                                            target="_blank" class="btn-icon" style="color: #2563eb;"
                                            title="Cetak Lembar Cuti Resmi">
                                            <i class="fas fa-print"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6"
                                    style="text-align: center; padding: 36px 16px; color: var(--text-muted);">
                                    <i class="fas fa-plane-departure mb-2"
                                        style="font-size: var(--text-3xl, 1.875rem); opacity: 0.5; display: block;"></i>
                                    <div>Belum ada data permohonan cuti / izin yang tercatat.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($cutiList->hasPages())
                <div class="datatable-footer">
                    @include('partials.datatable-pagination', ['paginator' => $cutiList])
                </div>
            @endif
        @endif
    </div>

    <!-- MODAL 1: Upload Berkas Digital GTK -->
    @include('dashboard.kepegawaian.partials.modal-kepegawaian')
@endsection

@push('scripts')
    <script
        src="{{ asset('js/kepegawaian.js') }}?v={{ file_exists(public_path('js/kepegawaian.js')) ? filemtime(public_path('js/kepegawaian.js')) : time() }}">
    </script>
@endpush
