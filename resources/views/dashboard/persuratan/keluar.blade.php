@extends('layouts.dashboard')

@section('title', 'Buku Agenda Surat Keluar & Surat Keterangan — SAE')
@section('dash_title', 'Surat Keluar')

@section('content')
    <div id="suratKeluarContainer" data-cetak-id="{{ session('cetak_id') ?? '' }}"
        data-cetak-template-id="{{ session('cetak_template_id') ?? '' }}"
        data-cetak-base-url="{{ url('/dashboard/persuratan/keluar/keterangan') }}"
        data-cetak-template-base-url="{{ url('/dashboard/persuratan/keluar/template') }}"
        data-store-url="{{ route('dashboard.persuratan.keluar.store') }}"
        data-base-url="{{ url('/dashboard/persuratan/keluar') }}"
        data-next-number-url="{{ route('dashboard.persuratan.keluar.next-number') }}"
        data-search-siswa-url="{{ route('dashboard.persuratan.keluar.search-siswa') }}"
        data-store-ket-url="{{ route('dashboard.persuratan.keterangan.store') }}">

        <!-- 1. Header Banner & Actions -->
        <div class="dash-banner"
            style="display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; margin-bottom: 20px;">
            <div style="display: flex; align-items: center; gap: 14px;">
                <div
                    style="width: 48px; height: 48px; border-radius: 12px; background: rgba(16,185,129,0.12); color: #10b981; display: flex; align-items: center; justify-content: center; font-size: 1.35rem; flex-shrink: 0;">
                    <i class="fas fa-paper-plane"></i>
                </div>
                <div>
                    <h2 style="font-size: 1.3rem; font-weight: 800; color: var(--text-color); margin: 0;">
                        Buku Agenda Surat Keluar
                    </h2>
                    <div style="font-size: 0.82rem; color: var(--text-muted); margin-top: 2px;">
                        Penomoran surat dinas otomatis, penerbitan surat dari template Autocrat, dan arsip dokumen di
                        harddisk.
                    </div>
                </div>
            </div>

            <div class="dash-banner-actions" style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                <a href="{{ route('dashboard.persuratan.masuk.index') }}" class="btn btn-outline"
                    style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; font-size: 0.95rem;"
                    title="Buku Agenda Surat Masuk">
                    <i class="fas fa-inbox text-primary"></i>
                </a>
                <a href="{{ route('dashboard.persuratan.pengaturan.index') }}" class="btn btn-outline"
                    style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; font-size: 0.95rem;"
                    title="Pengaturan Persuratan &amp; Harddisk">
                    <i class="fas fa-sliders text-muted"></i>
                </a>
                <button type="button" class="btn btn-outline" id="btnOpenCetakAgendaKeluar"
                    style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; font-size: 0.95rem;"
                    title="Cetak Buku Agenda Surat Keluar">
                    <i class="fas fa-print text-primary"></i>
                </button>
                <a href="{{ route('dashboard.persuratan.keluar.ekspedisi.cetak') }}" target="_blank"
                    class="btn btn-outline"
                    style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; font-size: 0.95rem;"
                    title="Cetak Buku Ekspedisi Pengiriman Surat Keluar">
                    <i class="fas fa-truck-fast text-success"></i>
                </a>
                @if ($canCreate)
                    @if (($tab ?? '') === 'spt')
                        <button type="button" class="btn btn-primary" id="btnOpenModalSpt"
                            style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; font-size: 0.95rem;"
                            title="Terbitkan Surat Perintah Tugas (SPT)">
                            <i class="fas fa-plus"></i>
                        </button>
                    @elseif (($tab ?? '') === 'template')
                        <button type="button" class="btn btn-primary" id="btnOpenModalTemplate"
                            style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; font-size: 0.95rem; background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); border: none; box-shadow: 0 4px 12px rgba(79, 70, 229, 0.35);"
                            title="Buat Surat Keluar dari Template Autocrat">
                            <i class="fas fa-wand-magic-sparkles"></i>
                        </button>
                    @else
                        <button type="button" class="btn btn-primary" id="btnOpenModalTemplate"
                            style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; font-size: 0.95rem; background: rgba(99,102,241,0.15); color: var(--primary); border: 1px solid rgba(99,102,241,0.3);"
                            title="Buat Surat dari Template Autocrat">
                            <i class="fas fa-wand-magic-sparkles"></i>
                        </button>
                        <button type="button" class="btn btn-outline" id="btnOpenCreateKet"
                            style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; font-size: 0.95rem; border-color: rgba(99,102,241,0.4); color: var(--primary);"
                            title="Buat Surat Keterangan Siswa Aktif">
                            <i class="fas fa-file-signature text-primary"></i>
                        </button>
                        <button type="button" class="btn btn-primary" id="btnOpenCreateKeluar"
                            style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; font-size: 0.95rem;"
                            title="Catat Surat Keluar Baru">
                            <i class="fas fa-plus"></i>
                        </button>
                    @endif
                @endif
            </div>
        </div>

        <!-- 2. Flash Messages -->
        @if (session('success'))
            <div
                style="background: rgba(16,185,129,0.12); border: 1px solid rgba(16,185,129,0.3); color: #10b981; padding: 12px 16px; border-radius: 10px; margin-bottom: 18px; font-size: 0.86rem; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-check-circle"></i>
                <div>{{ session('success') }}</div>
            </div>
        @endif
        @if (session('error'))
            <div
                style="background: rgba(239,68,68,0.12); border: 1px solid rgba(239,68,68,0.3); color: #ef4444; padding: 12px 16px; border-radius: 10px; margin-bottom: 18px; font-size: 0.86rem; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-circle-exclamation"></i>
                <div>{{ session('error') }}</div>
            </div>
        @endif

        <!-- 3. Stat Grid Baku SAE (4 Card Simetris) -->
        <div class="dash-stat-grid" style="margin-bottom: 20px;">
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(16,185,129,0.12); color: #10b981;">
                    <i class="fas fa-paper-plane"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ number_format($stats['total'] ?? 0) }}</div>
                    <div class="dash-stat-label">Total Surat Keluar</div>
                </div>
            </div>

            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(99,102,241,0.12); color: var(--primary);">
                    <i class="fas fa-user-graduate"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ number_format($stats['surat_keterangan'] ?? 0) }}</div>
                    <div class="dash-stat-label">Surat Keterangan Siswa</div>
                </div>
            </div>

            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(37,99,235,0.12); color: #2563eb;">
                    <i class="fas fa-file-contract"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ number_format($stats['spt'] ?? 0) }}</div>
                    <div class="dash-stat-label">Surat Perintah Tugas (SPT)</div>
                </div>
            </div>

            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(124,58,237,0.12); color: #7c3aed;">
                    <i class="fas fa-wand-magic-sparkles"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ number_format($stats['template'] ?? 0) }}</div>
                    <div class="dash-stat-label">Template Surat Autocrat</div>
                </div>
            </div>
        </div>

        <!-- 4. Tab Navigasi Baku SAE -->
        <div class="periode-nav-wrapper" style="margin-bottom: 20px;">
            <div class="periode-nav-desktop">
                <a href="{{ route('dashboard.persuratan.keluar.index') }}"
                    class="periode-nav-tab {{ ($tab ?? 'keluar') === 'keluar' ? 'active' : '' }}">
                    <i class="fas fa-paper-plane"></i> Semua Surat Keluar
                </a>
                <a href="{{ route('dashboard.persuratan.keluar.index', ['tab' => 'keterangan']) }}"
                    class="periode-nav-tab {{ ($tab ?? '') === 'keterangan' ? 'active' : '' }}">
                    <i class="fas fa-graduation-cap"></i> Surat Keterangan Siswa
                </a>
                <a href="{{ route('dashboard.persuratan.keluar.index', ['tab' => 'spt']) }}"
                    class="periode-nav-tab {{ ($tab ?? '') === 'spt' ? 'active' : '' }}">
                    <i class="fas fa-file-contract"></i> Surat Perintah Tugas (SPT)
                </a>
                <a href="{{ route('dashboard.persuratan.keluar.index', ['tab' => 'template']) }}"
                    class="periode-nav-tab {{ ($tab ?? '') === 'template' ? 'active' : '' }}">
                    <i class="fas fa-wand-magic-sparkles"></i> Template Surat (Autocrat)
                </a>
                <a href="{{ route('dashboard.persuratan.keluar.index', ['tab' => 'ekspedisi']) }}"
                    class="periode-nav-tab {{ ($tab ?? '') === 'ekspedisi' ? 'active' : '' }}">
                    <i class="fas fa-truck-fast"></i> Buku Ekspedisi
                </a>
            </div>
        </div>

        @php
            $activeTab = $tab ?? 'keluar';
            $tabConfigs = [
                'keluar' => [
                    'label' => 'Nomor Surat Keluar Terakhir',
                    'nomor' => $stats['nomor_terakhir'] ?? '-',
                    'badge' => 'Umum',
                    'icon' => 'fas fa-paper-plane',
                    'color' => '#10b981',
                    'bg' => 'rgba(16,185,129,0.12)',
                    'border' => 'rgba(16,185,129,0.25)',
                    'gradient' => 'linear-gradient(135deg, rgba(16,185,129,0.06) 0%, rgba(99,102,241,0.03) 100%)',
                ],
                'keterangan' => [
                    'label' => 'Nomor Surat Keterangan Siswa Terakhir',
                    'nomor' => $stats['nomor_terakhir_keterangan'] ?? '-',
                    'badge' => 'Siswa Aktif',
                    'icon' => 'fas fa-graduation-cap',
                    'color' => '#6366f1',
                    'bg' => 'rgba(99,102,241,0.12)',
                    'border' => 'rgba(99,102,241,0.25)',
                    'gradient' => 'linear-gradient(135deg, rgba(99,102,241,0.06) 0%, rgba(59,130,246,0.03) 100%)',
                ],
                'spt' => [
                    'label' => 'Nomor Surat Perintah Tugas (SPT) Terakhir',
                    'nomor' => $stats['nomor_terakhir_spt'] ?? '-',
                    'badge' => 'SPT Dinas',
                    'icon' => 'fas fa-file-contract',
                    'color' => '#2563eb',
                    'bg' => 'rgba(37,99,235,0.12)',
                    'border' => 'rgba(37,99,235,0.25)',
                    'gradient' => 'linear-gradient(135deg, rgba(37,99,235,0.06) 0%, rgba(16,185,129,0.03) 100%)',
                ],
                'template' => [
                    'label' => 'Nomor Template Surat Terakhir',
                    'nomor' => $stats['nomor_terakhir_template'] ?? '-',
                    'badge' => 'Autocrat',
                    'icon' => 'fas fa-wand-magic-sparkles',
                    'color' => '#7c3aed',
                    'bg' => 'rgba(124,58,237,0.12)',
                    'border' => 'rgba(124,58,237,0.25)',
                    'gradient' => 'linear-gradient(135deg, rgba(124,58,237,0.06) 0%, rgba(99,102,241,0.03) 100%)',
                ],
                'ekspedisi' => [
                    'label' => 'Buku Ekspedisi Pengiriman Surat Keluar',
                    'nomor' => $stats['nomor_terakhir'] ?? '-',
                    'badge' => 'Tanda Terima',
                    'icon' => 'fas fa-truck-fast',
                    'color' => '#f59e0b',
                    'bg' => 'rgba(245,158,11,0.12)',
                    'border' => 'rgba(245,158,11,0.25)',
                    'gradient' => 'linear-gradient(135deg, rgba(245,158,11,0.06) 0%, rgba(16,185,129,0.03) 100%)',
                ],
            ];
            $curCfg = $tabConfigs[$activeTab] ?? $tabConfigs['keluar'];
        @endphp

        <!-- Card Interaktif: Nomor Surat Terakhir (Diatas Datatable) -->
        <div class="card card-nomor-terakhir"
            style="padding: 16px 20px; margin-bottom: 20px; border-radius: 14px; border: 1px solid {{ $curCfg['border'] }}; background: {{ $curCfg['gradient'] }}; display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; box-shadow: 0 2px 8px rgba(0,0,0,0.03);">
            <div style="display: flex; align-items: center; gap: 16px; min-width: 260px; flex: 1;">
                <div
                    style="width: 48px; height: 48px; border-radius: 12px; background: {{ $curCfg['bg'] }}; color: {{ $curCfg['color'] }}; display: flex; align-items: center; justify-content: center; font-size: 1.35rem; flex-shrink: 0;">
                    <i class="{{ $curCfg['icon'] }}"></i>
                </div>
                <div style="min-width: 0;">
                    <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                        <span
                            style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px;">
                            {{ $curCfg['label'] }}
                        </span>
                        <span class="badge"
                            style="font-size: 0.68rem; padding: 2px 8px; border-radius: 12px; background: {{ $curCfg['bg'] }}; color: {{ $curCfg['color'] }}; border: 1px solid {{ $curCfg['border'] }};">
                            {{ $curCfg['badge'] }}
                        </span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 10px; margin-top: 4px; flex-wrap: wrap;">
                        <span id="labelNomorSuratKeluar"
                            style="font-family: 'JetBrains Mono', 'Fira Code', 'Courier New', monospace; font-size: 1.15rem; font-weight: 800; color: {{ $curCfg['color'] }}; background: {{ $curCfg['bg'] }}; padding: 4px 12px; border-radius: 8px; border: 1px solid {{ $curCfg['border'] }}; letter-spacing: 0.5px;">
                            {{ $curCfg['nomor'] }}
                        </span>
                    </div>
                </div>
            </div>

            <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                <button type="button" class="btn btn-outline" onclick="copyNomorSurat(this, '{{ $curCfg['nomor'] }}')"
                    style="padding: 8px 14px; font-size: 0.84rem; border-radius: 8px; display: inline-flex; align-items: center; gap: 7px; background: var(--card-bg);"
                    title="Salin nomor surat ke clipboard">
                    <i class="fas fa-copy" style="color: {{ $curCfg['color'] }};"></i> <span>Salin Nomor</span>
                </button>

                @if ($curCfg['nomor'] !== '-')
                    <button type="button" class="btn btn-primary"
                        onclick="filterByNomorSurat('{{ $curCfg['nomor'] }}')"
                        style="padding: 8px 14px; font-size: 0.84rem; border-radius: 8px; display: inline-flex; align-items: center; gap: 7px; background: {{ $curCfg['color'] }}; border-color: {{ $curCfg['color'] }};"
                        title="Filter / temukan nomor ini pada datatable">
                        <i class="fas fa-search"></i> <span>Cari di Datatable</span>
                    </button>
                @endif
            </div>
        </div>

        <!-- Unified Datatable Surat Keluar (Toolbar + Table + Pagination dalam 1 Card Terpadu) -->
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

                        @if (($tab ?? '') !== 'spt' && ($tab ?? '') !== 'template')
                            <select id="filterKodeIndeks" name="kode_indeks" class="toolbar-filter-select"
                                style="height: 38px;">
                                <option value="">Semua Indeks</option>
                                @foreach ($indeksList as $idx)
                                    <option value="{{ $idx->kode }}"
                                        {{ ($kodeIndeks ?? '') === $idx->kode ? 'selected' : '' }}>
                                        {{ $idx->kode }} - {{ $idx->judul }}
                                    </option>
                                @endforeach
                            </select>

                            <select id="filterStatus" name="status" class="toolbar-filter-select"
                                style="height: 38px;">
                                <option value="">Semua Status</option>
                                <option value="selesai" {{ ($status ?? '') === 'selesai' ? 'selected' : '' }}>Selesai /
                                    Terbit
                                </option>
                                <option value="draf" {{ ($status ?? '') === 'draf' ? 'selected' : '' }}>Draf</option>
                                <option value="diarsipkan" {{ ($status ?? '') === 'diarsipkan' ? 'selected' : '' }}>
                                    Diarsipkan
                                </option>
                            </select>
                        @endif

                        @if (!empty($q) || !empty($status) || !empty($kodeIndeks))
                            <a href="{{ route('dashboard.persuratan.keluar.index', ['tab' => $tab ?? 'keluar']) }}"
                                class="btn btn-outline btn-reset-filter"
                                style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px;"
                                title="Reset filter" data-live-reset>
                                <i class="fas fa-undo"></i>
                            </a>
                        @endif

                        @if (($tab ?? '') === 'ekspedisi')
                            <a href="{{ route('dashboard.persuratan.keluar.ekspedisi.cetak', array_filter(['q' => $q ?? '', 'status' => $status ?? '', 'kode_indeks' => $kodeIndeks ?? ''])) }}"
                                target="_blank" class="btn btn-outline"
                                style="padding: 7px 12px; font-size: 0.84rem; display: inline-flex; align-items: center; gap: 6px; color: #10b981; border-color: rgba(16,185,129,0.35);"
                                title="Cetak Buku Ekspedisi Kolektif Sesuai Filter">
                                <i class="fas fa-print"></i> Cetak Rekap Ekspedisi
                            </a>
                        @endif
                    </div>

                    <div class="live-search-wrap" style="height: 38px;">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" id="liveSearch" name="q"
                            placeholder="{{ ($tab ?? '') === 'spt' ? 'Cari nomor SPT, kegiatan, tujuan...' : 'Cari nomor surat, perihal, tujuan...' }}"
                            value="{{ $q ?? '' }}" autocomplete="off">
                        <button type="button" id="clearSearch" class="clear-search {{ !empty($q) ? 'visible' : '' }}"
                            title="Hapus pencarian">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- 2. Konten Datatable Sesuai Tab Aktif -->
            @if (($tab ?? '') === 'spt')
                <!-- Container Datatable Responsive-Stack: Surat Perintah Tugas (SPT) -->
                <div class="table-responsive-stack" style="overflow-x: auto; width: 100%;">
                    <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                        <thead>
                            <tr style="border-bottom: 1px solid var(--border-color); background: rgba(0,0,0,0.02);">
                                <th
                                    style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 60px; text-align: center;">
                                    No</th>
                                <th
                                    style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                    Nomor SPT &amp; Anggaran</th>
                                <th
                                    style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                    Kegiatan &amp; Lokasi</th>
                                <th
                                    style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                    Waktu Penugasan</th>
                                <th
                                    style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                    GTK Yang Ditugaskan</th>
                                <th
                                    style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 120px;">
                                    Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($items as $idx => $spt)
                                @php
                                    $ptkIds = json_decode($spt->daftar_ptk_id ?? '[]', true) ?: [];
                                    $assignedGtk = isset($allGtk) ? $allGtk->whereIn('ptk_id', $ptkIds) : collect();
                                @endphp
                                <tr style="border-bottom: 1px solid var(--border-color);">
                                    <td style="padding: 12px 16px; text-align: center; font-size: 0.88rem; color: var(--text-muted);"
                                        data-label="No">
                                        {{ $items->firstItem() + $idx }}
                                    </td>
                                    <td style="padding: 12px 16px;" data-label="Nomor SPT">
                                        <div
                                            style="font-family: monospace; font-weight: 700; color: var(--text-color); font-size: 0.88rem;">
                                            {{ $spt->nomor_spt }}</div>
                                        <div style="font-size: 0.76rem; color: var(--text-muted); margin-top: 2px;">
                                            Beban: <span class="badge-compact"
                                                style="background: rgba(16,185,129,0.1); color: #10b981;">{{ $spt->beban_anggaran }}</span>
                                        </div>
                                    </td>
                                    <td style="padding: 12px 16px;" data-label="Kegiatan & Lokasi">
                                        <div style="font-weight: 600; color: var(--text-color); font-size: 0.85rem;">
                                            {{ $spt->nama_kegiatan }}</div>
                                        <div style="font-size: 0.76rem; color: var(--text-muted);">
                                            <i class="fas fa-map-marker-alt text-danger me-1"></i>
                                            {{ $spt->lokasi_tujuan }}
                                        </div>
                                        @if (!empty($spt->dasar_penugasan))
                                            <div
                                                style="font-size: 0.72rem; color: var(--text-muted); margin-top: 2px; font-style: italic;">
                                                Dasar: {{ \Illuminate\Support\Str::limit($spt->dasar_penugasan, 60) }}
                                            </div>
                                        @endif
                                    </td>
                                    <td style="padding: 12px 16px;" data-label="Waktu">
                                        <div style="font-size: 0.82rem; color: var(--text-color);">
                                            <i class="far fa-calendar-alt text-muted me-1"></i>
                                            {{ \Carbon\Carbon::parse($spt->tanggal_berangkat)->translatedFormat('d M Y') }}
                                            s/d
                                            {{ \Carbon\Carbon::parse($spt->tanggal_kembali)->translatedFormat('d M Y') }}
                                        </div>
                                        <div style="font-size: 0.74rem; color: var(--text-muted); margin-top: 2px;">
                                            Durasi: <strong>{{ $spt->lama_hari }} Hari</strong>
                                        </div>
                                    </td>
                                    <td style="padding: 12px 16px;" data-label="GTK Ditugaskan">
                                        <div style="display: flex; flex-wrap: wrap; gap: 4px;">
                                            @forelse($assignedGtk as $g)
                                                <span class="badge-compact"
                                                    style="background: rgba(99,102,241,0.1); color: var(--primary); font-size: 0.76rem;">
                                                    <i class="fas fa-user-check me-1"></i> {{ $g->nama }}
                                                </span>
                                            @empty
                                                <span
                                                    style="font-size: 0.78rem; color: var(--text-muted); font-style: italic;">-</span>
                                            @endforelse
                                        </div>
                                    </td>
                                    <td style="padding: 12px 16px; text-align: center;" data-label="Aksi">
                                        <div class="table-actions"
                                            style="display: flex; gap: 4px; justify-content: center;">
                                            <a href="{{ route('dashboard.persuratan.keluar.spt.cetak', $spt->id) }}"
                                                target="_blank" class="btn-icon" style="color: #2563eb;"
                                                title="Cetak Lembar SPT Resmi (A4 Ber-KOP)">
                                                <i class="fas fa-print"></i>
                                            </a>
                                            @if ($canDelete)
                                                <form
                                                    action="{{ route('dashboard.persuratan.keluar.spt.delete', $spt->id) }}"
                                                    method="POST" style="display: inline;" data-confirm="delete"
                                                    data-name="{{ $spt->nomor_spt }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn-icon text-danger"
                                                        title="Hapus SPT">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6"
                                        style="padding: 30px; text-align: center; color: var(--text-muted);">
                                        <i class="fas fa-file-contract mb-2"
                                            style="font-size: 1.8rem; opacity: 0.5; display: block;"></i>
                                        <div>Belum ada Surat Perintah Tugas (SPT) yang diterbitkan.</div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @elseif (($tab ?? '') === 'template')
                <!-- Container Datatable: Surat Keluar dari Template Autocrat -->
                <div class="table-responsive-stack" style="overflow-x: auto; width: 100%;">
                    <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                        <thead>
                            <tr style="border-bottom: 1px solid var(--border-color); background: rgba(0,0,0,0.02);">
                                <th
                                    style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 50px; text-align: center;">
                                    No</th>
                                <th
                                    style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                    Nomor Surat &amp; Indeks</th>
                                <th
                                    style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                    Jenis Template &amp; Perihal</th>
                                <th
                                    style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                    Tujuan Penerima</th>
                                <th
                                    style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                    Tanggal &amp; Penandatangan</th>
                                <th
                                    style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 140px;">
                                    Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($items as $idx => $tmpl)
                                <tr style="border-bottom: 1px solid var(--border-color);">
                                    <td style="padding: 12px 16px; text-align: center; font-size: 0.88rem; color: var(--text-muted);"
                                        data-label="No">
                                        {{ $items->firstItem() + $idx }}
                                    </td>
                                    <td style="padding: 12px 16px;" data-label="Nomor Surat">
                                        <div
                                            style="font-family: monospace; font-weight: 700; color: var(--text-color); font-size: 0.88rem;">
                                            {{ $tmpl->nomor_surat }}</div>
                                        <div style="font-size: 0.74rem; color: var(--text-muted); margin-top: 2px;">
                                            Indeks: <span class="badge-compact"
                                                style="background: rgba(99,102,241,0.1); color: var(--primary);">{{ $tmpl->kode_indeks ?: '-' }}</span>
                                        </div>
                                    </td>
                                    <td style="padding: 12px 16px;" data-label="Template &amp; Perihal">
                                        <div style="font-weight: 700; color: var(--text-color); font-size: 0.88rem;">
                                            {{ $tmpl->perihal }}</div>
                                        <div style="font-size: 0.74rem; color: var(--text-muted); margin-top: 2px;">
                                            <span class="badge-compact"
                                                style="background: rgba(16,185,129,0.1); color: #10b981; text-transform: uppercase;">
                                                <i class="fas fa-wand-magic-sparkles me-1"></i>
                                                {{ ucfirst($tmpl->template_type) }}
                                            </span>
                                            @if ($tmpl->lampiran && $tmpl->lampiran !== '-')
                                                <span style="margin-left: 6px;">Lampiran: {{ $tmpl->lampiran }}</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td style="padding: 12px 16px;" data-label="Tujuan">
                                        <div style="font-weight: 600; color: var(--text-color); font-size: 0.84rem;">
                                            <i class="fas fa-location-arrow text-success me-1"></i>
                                            {{ $tmpl->tujuan_penerima }}
                                        </div>
                                        <div style="font-size: 0.74rem; color: var(--text-muted);">
                                            {{ $tmpl->tujuan_lokasi ?: 'Di Tempat' }}
                                        </div>
                                    </td>
                                    <td style="padding: 12px 16px;" data-label="Tanggal &amp; Pejabat">
                                        <div style="font-size: 0.82rem; color: var(--text-color);">
                                            <i class="far fa-calendar-alt text-muted me-1"></i>
                                            {{ \Carbon\Carbon::parse($tmpl->tanggal_surat)->translatedFormat('d F Y') }}
                                        </div>
                                        <div style="font-size: 0.74rem; color: var(--text-muted); margin-top: 2px;">
                                            TTD: {{ $tmpl->pejabat_nama ?: 'Kepala Sekolah' }}
                                        </div>
                                    </td>
                                    <td style="padding: 12px 16px; text-align: center;" data-label="Aksi">
                                        <div class="table-actions"
                                            style="display: flex; gap: 4px; justify-content: center;">
                                            <a href="{{ route('dashboard.persuratan.keluar.template.cetak', $tmpl->id) }}"
                                                target="_blank" class="btn-icon" style="color: #2563eb;"
                                                title="Cetak Lembar Resmi A4 (Print / PDF)">
                                                <i class="fas fa-print"></i>
                                            </a>
                                            <a href="{{ url('/v/doc/' . $tmpl->doc_id) }}" target="_blank"
                                                class="btn-icon text-info" title="Halaman Verifikasi Publik (QR Code)">
                                                <i class="fas fa-qrcode"></i>
                                            </a>
                                            @if ($canDelete)
                                                <form
                                                    action="{{ route('dashboard.persuratan.keluar.template.delete', $tmpl->id) }}"
                                                    method="POST" style="display: inline;" data-confirm="delete"
                                                    data-name="{{ $tmpl->nomor_surat }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn-icon text-danger"
                                                        title="Hapus Dokumen Template">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6"
                                        style="padding: 30px; text-align: center; color: var(--text-muted);">
                                        <i class="fas fa-wand-magic-sparkles mb-2"
                                            style="font-size: 1.8rem; opacity: 0.5; display: block;"></i>
                                        <div>Belum ada surat keluar yang dibuat dari template Autocrat.</div>
                                        @if ($canCreate)
                                            <button type="button" class="btn btn-outline btn-sm mt-3"
                                                onclick="document.getElementById('btnOpenModalTemplate')?.click()">
                                                <i class="fas fa-plus me-1"></i> Buat Surat Sekarang
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @else
                <!-- Container Datatable Responsive-Stack Standar SAE -->
                <div class="table-responsive-stack" style="overflow-x: auto; width: 100%;">
                    <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                        <thead>
                            <tr style="border-bottom: 1px solid var(--border-color); background: rgba(0,0,0,0.02);">
                                <th class="sortable-th {{ ($sort ?? '') === 'nomor_surat' ? 'sorted' : '' }}"
                                    data-sort="nomor_surat"
                                    style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                    No. Surat &amp; Indeks
                                    <span class="sort-icon">{!! ($sort ?? '') === 'nomor_surat' ? (($sortDir ?? '') === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                                </th>
                                <th
                                    style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                    Perihal &amp; Tujuan Penerima
                                </th>
                                <th class="sortable-th {{ ($sort ?? '') === 'tanggal_surat' ? 'sorted' : '' }}"
                                    data-sort="tanggal_surat"
                                    style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 130px;">
                                    Tgl Surat
                                    <span class="sort-icon">{!! ($sort ?? '') === 'tanggal_surat' ? (($sortDir ?? '') === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                                </th>
                                <th
                                    style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 100px; text-align: center;">
                                    Arsip HDD
                                </th>
                                <th
                                    style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 110px; text-align: center;">
                                    Status
                                </th>
                                <th
                                    style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 120px; text-align: center;">
                                    Aksi
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($items as $item)
                                @php
                                    $suratKet = $item->suratKeterangan;
                                    $isKet = !empty($suratKet);
                                    $jenisKet = $suratKet?->jenis_surat;
                                    $labelKet = match ($jenisKet) {
                                        'kelakuan_baik' => 'Berkelakuan Baik',
                                        'panggilan_ortu' => 'Panggilan Ortu',
                                        'rekomendasi' => 'Rekomendasi',
                                        default => 'Siswa Aktif',
                                    };
                                    $badgeKetStyle = match ($jenisKet) {
                                        'kelakuan_baik' => 'background: rgba(16,185,129,0.1); color: #10b981;',
                                        'panggilan_ortu' => 'background: rgba(239,68,68,0.1); color: #ef4444;',
                                        'rekomendasi' => 'background: rgba(245,158,11,0.1); color: #f59e0b;',
                                        default => 'background: rgba(99,102,241,0.1); color: var(--primary);',
                                    };
                                @endphp
                                <tr style="border-bottom: 1px solid var(--border-color);">
                                    <td style="padding: 12px 16px;">
                                        <div
                                            style="font-family: monospace; font-weight: 700; font-size: 0.88rem; color: var(--text-color);">
                                            {{ $item->nomor_surat }}
                                        </div>
                                        <div
                                            style="font-size: 0.72rem; color: var(--text-muted); margin-top: 2px; display: flex; flex-wrap: wrap; gap: 4px; align-items: center;">
                                            <span class="badge-compact"
                                                style="background: rgba(16,185,129,0.1); color: #10b981;">Idx:
                                                {{ $item->kode_indeks ?: '-' }}</span>
                                            @if ($isKet)
                                                <span class="badge-compact"
                                                    style="{{ $badgeKetStyle }}">{{ $labelKet }}</span>
                                            @endif
                                            @if ($item->sarprasAset)
                                                <span class="badge-compact"
                                                    style="background: rgba(245,158,11,0.12); color: #d97706; border: 1px solid rgba(245,158,11,0.25);">
                                                    <i class="fas fa-boxes-stacked me-1"></i> Aset:
                                                    {{ $item->sarprasAset->kode_aset }}
                                                </span>
                                            @endif
                                        </div>
                                    </td>

                                    <td style="padding: 12px 16px;">
                                        <div
                                            style="font-weight: 600; font-size: 0.84rem; color: var(--text-color); margin-bottom: 2px;">
                                            {{ $item->perihal }}
                                        </div>
                                        <div style="font-size: 0.76rem; color: var(--text-muted);">
                                            <i class="fas fa-location-arrow text-success me-1"></i> Kepada:
                                            <strong>{{ $item->tujuan_penerima ?: '-' }}</strong>
                                        </div>
                                        @if ($item->sarprasAset)
                                            <div style="font-size: 0.73rem; color: #d97706; margin-top: 3px;">
                                                <i class="fas fa-box me-1"></i> Barang:
                                                <strong>{{ $item->sarprasAset->nama_barang }}</strong>
                                                ({{ $item->sarprasAset->merk_tipe ?: 'Merk -' }} &bull; Kondisi:
                                                {{ ucfirst($item->sarprasAset->kondisi) }})
                                            </div>
                                        @endif
                                        @if ($isKet && $suratKet->siswa)
                                            <div style="font-size: 0.73rem; color: var(--primary); margin-top: 3px;">
                                                <i class="fas fa-user-graduate me-1"></i> Siswa:
                                                <strong>{{ $suratKet->siswa->nama }}</strong> (NISN:
                                                {{ $suratKet->siswa->nisn ?: '-' }})
                                            </div>
                                        @endif
                                    </td>

                                    <td style="padding: 12px 16px;">
                                        <div style="font-size: 0.8rem; color: var(--text-color);">
                                            <i class="far fa-calendar-alt text-muted me-1"></i>
                                            {{ date('d/m/Y', strtotime($item->tanggal_surat)) }}
                                        </div>
                                        <div style="font-size: 0.7rem; color: var(--text-muted); margin-top: 2px;">
                                            Oleh: {{ $item->created_by ?: 'Petugas' }}
                                        </div>
                                    </td>

                                    <td style="padding: 12px 16px; text-align: center;">
                                        @if (!empty($item->file_path))
                                            <button type="button" class="btn-icon text-primary btn-view-dokumen"
                                                title="Pratinjau Berkas dari HDD"
                                                data-view-url="{{ route('dashboard.persuratan.dokumen.view', $item->id) }}"
                                                data-download-url="{{ route('dashboard.persuratan.dokumen.download', $item->id) }}"
                                                data-title="Surat Keluar: {{ $item->nomor_surat }} - {{ $item->perihal }}"
                                                data-mime="{{ str_contains($item->file_path, '.pdf') ? 'application/pdf' : 'image/jpeg' }}">
                                                <i class="fas fa-file-pdf" style="font-size: 1.1rem; color: #ef4444;"></i>
                                            </button>
                                        @else
                                            <span
                                                style="font-size: 0.72rem; color: var(--text-muted); font-style: italic;">
                                                Tanpa Berkas
                                            </span>
                                        @endif
                                    </td>

                                    <td style="padding: 12px 16px; text-align: center;">
                                        <span
                                            class="badge-compact {{ $item->status === 'selesai' ? 'badge-success' : ($item->status === 'draf' ? 'badge-warning' : 'badge-secondary') }}">
                                            {{ $item->status === 'selesai' ? 'Terbit' : ucfirst($item->status) }}
                                        </span>
                                    </td>

                                    <td style="padding: 12px 16px; text-align: center;">
                                        <div class="table-actions"
                                            style="display: flex; gap: 4px; justify-content: center;">
                                            <!-- Jika Surat Keterangan Siswa: Tombol Cetak Resmi -->
                                            @if ($isKet && $suratKet)
                                                <a href="{{ route('dashboard.persuratan.keterangan.cetak', $suratKet->id) }}"
                                                    target="_blank" class="btn-icon text-primary"
                                                    title="Cetak Lembar Resmi Surat Keterangan (Kop &amp; QR Code)">
                                                    <i class="fas fa-print"></i>
                                                </a>
                                                <a href="{{ url('/v/doc/' . $suratKet->doc_id) }}" target="_blank"
                                                    class="btn-icon text-info"
                                                    title="Lihat Halaman Verifikasi Publik (QR)">
                                                    <i class="fas fa-qrcode"></i>
                                                </a>
                                            @endif

                                            <!-- Tombol Cetak Lembar Ekspedisi / Tanda Terima -->
                                            <a href="{{ route('dashboard.persuratan.keluar.ekspedisi.cetak', $item->id) }}"
                                                target="_blank" class="btn-icon text-success"
                                                title="Cetak Tanda Terima Ekspedisi Surat Ini">
                                                <i class="fas fa-receipt"></i>
                                            </a>

                                            @if ($canUpdate)
                                                <button type="button" class="btn-icon btn-edit-keluar"
                                                    title="Edit Surat Keluar" data-id="{{ $item->id }}"
                                                    data-nomor="{{ $item->nomor_surat }}"
                                                    data-indeks="{{ $item->kode_indeks }}"
                                                    data-perihal="{{ $item->perihal }}"
                                                    data-tujuan="{{ $item->tujuan_penerima }}"
                                                    data-tgl-surat="{{ $item->tanggal_surat }}"
                                                    data-aset-id="{{ $item->sarpras_aset_id }}"
                                                    data-status="{{ $item->status }}"
                                                    data-keterangan="{{ $item->keterangan }}"
                                                    data-file-path="{{ $item->file_path }}"
                                                    data-file-name="{{ $item->file_name_original }}">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                            @endif

                                            @if ($canDelete)
                                                <form
                                                    action="{{ route('dashboard.persuratan.keluar.destroy', $item->id) }}"
                                                    method="POST" data-confirm="delete"
                                                    data-name="Surat Keluar No. {{ $item->nomor_surat }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn-icon text-danger"
                                                        title="Hapus Surat Keluar">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6"
                                        style="text-align: center; padding: 40px 16px; color: var(--text-muted);">
                                        <i class="fas fa-paper-plane"
                                            style="font-size: 2.2rem; opacity: 0.3; margin-bottom: 10px; display: block;"></i>
                                        Tidak ada data surat keluar yang sesuai kriteria pencarian.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @endif

            <!-- 3. Footer Paginasi Terpadu Datatable -->
            @if ($items->hasPages())
                <div class="datatable-footer">
                    @include('partials.datatable-pagination', ['paginator' => $items])
                </div>
            @endif
        </div>

        @include('dashboard.persuratan.partials.modal-surat-keluar')

        <!-- Modal Cetak Buku Agenda Surat Keluar -->
        <div class="modal-overlay" id="modalAgendaKeluar"
            style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.55); backdrop-filter: blur(4px); z-index: 99999 !important; align-items: center; justify-content: center;">
            <div class="modal-container"
                style="background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 14px; width: 100%; max-width: 480px; box-shadow: 0 20px 40px rgba(0,0,0,0.3); overflow: hidden;">
                <div
                    style="padding: 16px 20px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
                    <h4
                        style="font-size: 1rem; font-weight: 700; color: var(--text-color); margin: 0; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-print text-primary"></i> Cetak Buku Agenda Surat Keluar
                    </h4>
                    <button type="button" id="btnCloseModalAgendaKeluar"
                        style="background: none; border: none; font-size: 1.1rem; color: var(--text-muted); cursor: pointer;">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <form action="{{ route('dashboard.persuratan.keluar.agenda.cetak') }}" method="GET" target="_blank"
                    style="padding: 20px; display: flex; flex-direction: column; gap: 14px;">
                    <div>
                        <label
                            style="display: block; font-size: 0.82rem; font-weight: 600; color: var(--text-color); margin-bottom: 4px;">
                            Rentang Tanggal Surat (Opsional):
                        </label>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                            <input type="date" name="tanggal_mulai" class="form-control"
                                style="height: 38px; border-radius: 8px; font-size: 0.82rem;">
                            <input type="date" name="tanggal_selesai" class="form-control"
                                style="height: 38px; border-radius: 8px; font-size: 0.82rem;">
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                        <div>
                            <label
                                style="display: block; font-size: 0.82rem; font-weight: 600; color: var(--text-color); margin-bottom: 4px;">
                                Tahun:
                            </label>
                            <select name="tahun" class="form-control"
                                style="height: 38px; border-radius: 8px; font-size: 0.84rem;">
                                @for ($y = date('Y'); $y >= 2024; $y--)
                                    <option value="{{ $y }}">{{ $y }}</option>
                                @endfor
                            </select>
                        </div>
                        <div>
                            <label
                                style="display: block; font-size: 0.82rem; font-weight: 600; color: var(--text-color); margin-bottom: 4px;">
                                Bulan (Opsional):
                            </label>
                            <select name="bulan" class="form-control"
                                style="height: 38px; border-radius: 8px; font-size: 0.84rem;">
                                <option value="">Semua Bulan (1 Tahun)</option>
                                @foreach ([1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'] as $mNum => $mName)
                                    <option value="{{ $mNum }}">{{ $mName }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <label
                            style="display: block; font-size: 0.82rem; font-weight: 600; color: var(--text-color); margin-bottom: 4px;">
                            Kode Indeks Klasifikasi (Opsional):
                        </label>
                        <select name="kode_indeks" class="form-control"
                            style="height: 38px; border-radius: 8px; font-size: 0.84rem;">
                            <option value="">Semua Indeks Klasifikasi</option>
                            @foreach ($indeksList as $idx)
                                <option value="{{ $idx->kode }}">{{ $idx->kode }} - {{ $idx->judul }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 6px;">
                        <button type="button" id="btnCancelModalAgendaKeluar" class="btn btn-outline"
                            style="padding: 8px 16px; border-radius: 8px; font-size: 0.84rem;">Batal</button>
                        <button type="submit" class="btn btn-primary"
                            style="padding: 8px 18px; border-radius: 8px; font-size: 0.84rem; display: inline-flex; align-items: center; gap: 6px;">
                            <i class="fas fa-print"></i> Buka Lembar Cetak
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endsection

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const modalAgenda = document.getElementById('modalAgendaKeluar');
                const btnOpenAgenda = document.getElementById('btnOpenCetakAgendaKeluar');
                const btnCloseAgenda = document.getElementById('btnCloseModalAgendaKeluar');
                const btnCancelAgenda = document.getElementById('btnCancelModalAgendaKeluar');

                if (btnOpenAgenda && modalAgenda) {
                    btnOpenAgenda.addEventListener('click', () => {
                        modalAgenda.style.display = 'flex';
                        document.body.classList.add('modal-open');
                    });
                }

                function closeAgendaModal() {
                    if (!modalAgenda) return;
                    modalAgenda.style.display = 'none';
                    document.body.classList.remove('modal-open');
                }

                if (btnCloseAgenda) btnCloseAgenda.addEventListener('click', closeAgendaModal);
                if (btnCancelAgenda) btnCancelAgenda.addEventListener('click', closeAgendaModal);
                if (modalAgenda) {
                    modalAgenda.addEventListener('click', (e) => {
                        if (e.target === modalAgenda) closeAgendaModal();
                    });
                }
            });

            function copyNomorSurat(btn, text) {
                if (!text || text === '-') return;
                const oldHtml = btn.innerHTML;
                const updateBtn = () => {
                    btn.innerHTML =
                        '<i class="fas fa-check text-success"></i> <span style="color:#10b981;">Tersalin!</span>';
                    setTimeout(() => {
                        btn.innerHTML = oldHtml;
                    }, 2000);
                };
                if (navigator.clipboard && window.isSecureContext) {
                    navigator.clipboard.writeText(text).then(updateBtn).catch(() => fallbackCopy(text, updateBtn));
                } else {
                    fallbackCopy(text, updateBtn);
                }
            }

            function fallbackCopy(text, cb) {
                const ta = document.createElement('textarea');
                ta.value = text;
                ta.style.position = 'fixed';
                ta.style.opacity = '0';
                document.body.appendChild(ta);
                ta.select();
                try {
                    document.execCommand('copy');
                    if (cb) cb();
                } catch (e) {}
                document.body.removeChild(ta);
            }

            function filterByNomorSurat(text) {
                if (!text || text === '-') return;
                const input = document.getElementById('liveSearch');
                if (input) {
                    input.value = text;
                    input.focus();
                    input.dispatchEvent(new Event('input', {
                        bubbles: true
                    }));
                    const clearBtn = document.getElementById('clearSearch');
                    if (clearBtn) clearBtn.classList.add('visible');
                } else {
                    const url = new URL(window.location.href);
                    url.searchParams.set('q', text);
                    url.searchParams.set('page', '1');
                    window.location.href = url.toString();
                }
            }
        </script>
        <script
            src="{{ asset('js/persuratan-keluar.js') }}?v={{ file_exists(public_path('js/persuratan-keluar.js')) ? filemtime(public_path('js/persuratan-keluar.js')) : time() }}">
        </script>
    @endpush
