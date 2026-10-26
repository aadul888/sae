@extends('layouts.dashboard')

@section('title', 'Analisis & Kebutuhan Alat/Bahan — Laboratorium SAE')
@section('dash_title', 'Analisis Kebutuhan')

@section('content')
    <div class="dash-content-inner">
        {{-- 1. Banner Header --}}
        <div class="dash-banner"
            style="display: flex; align-items: center; justify-content: space-between; gap: var(--space-8); flex-wrap: wrap; margin-bottom: var(--space-9);">
            <div style="display: flex; align-items: center; gap: var(--space-7);">
                <div style="width: 48px; height: 48px; border-radius: var(--radius-lg); background: rgba(6,182,212,0.12); color: var(--accent, #06b6d4); display: flex; align-items: center; justify-content: center; font-size: var(--text-2xl); flex-shrink: 0;">
                    <i class="fas fa-clipboard-list"></i>
                </div>
                <div>
                    <h2 style="font-size: var(--text-xl); font-weight: var(--font-extrabold); color: var(--text-color); margin: 0;">
                        Analisis &amp; Kebutuhan Alat/Bahan
                    </h2>
                    <div style="font-size: var(--text-sm); color: var(--text-muted); margin-top: var(--space-1);">
                        Identifikasi kekurangan alat &amp; bahan praktik berdasarkan ketersediaan dan kebutuhan ideal.
                    </div>
                </div>
            </div>
            <div class="dash-banner-actions" style="display: flex; gap: var(--space-4); align-items: center; flex-wrap: wrap;">
                <span class="badge" style="background: rgba(59,130,246,0.12); color: var(--primary, #3b82f6); border: 1px solid rgba(59,130,246,0.25); padding: var(--space-3) var(--space-6); border-radius: var(--radius-md); font-size: var(--text-xs); font-weight: var(--font-bold); display: inline-flex; align-items: center; gap: var(--space-2);">
                    <i class="fas {{ $scopeBadge['icon'] }}"></i> {{ $scopeBadge['label'] }}
                </span>
                @if ($canCreate)
                    <button type="button" class="btn btn-outline" id="btnJalankanAnalisis"
                        style="height: 38px; padding: 0 14px; display: inline-flex; align-items: center; gap: 6px; border-radius: var(--radius-md); font-size: var(--text-sm);"
                        title="Jalankan Analisis Otomatis dari Kondisi Stok &amp; Kerusakan">
                        <i class="fas fa-wand-magic-sparkles text-primary"></i> Jalankan Analisis
                    </button>
                    <button type="button" class="btn btn-primary" id="btnOpenKebutuhan"
                        style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md); font-size: var(--text-md);"
                        title="Tambah Data Kebutuhan Manual">
                        <i class="fas fa-plus"></i>
                    </button>
                @endif
            </div>
        </div>

        {{-- 2. Statistik (4 Kartu) --}}
        <div class="dash-stat-grid" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); margin-bottom: var(--space-9); gap: var(--space-7);">
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(59, 130, 246, 0.15); color: var(--primary, #3b82f6);"><i class="fas fa-clipboard-list"></i></div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ number_format($statTotal, 0, ',', '.') }}</div>
                    <div class="dash-stat-label">Total Kebutuhan</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(239, 68, 68, 0.15); color: var(--danger, #ef4444);"><i class="fas fa-fire"></i></div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ number_format($statKritis, 0, ',', '.') }}</div>
                    <div class="dash-stat-label">Prioritas Kritis</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(245, 158, 11, 0.15); color: var(--warning, #f59e0b);"><i class="fas fa-pen-to-square"></i></div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ number_format($statBelum, 0, ',', '.') }}</div>
                    <div class="dash-stat-label">Belum Diverifikasi</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(16, 185, 129, 0.15); color: var(--success, #10b981);"><i class="fas fa-circle-check"></i></div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ number_format($statDisetujui, 0, ',', '.') }}</div>
                    <div class="dash-stat-label">Disetujui</div>
                </div>
            </div>
        </div>

        {{-- 3. Panel Analisis Otomatis Stok --}}
        <div class="card" style="padding: var(--space-9); margin-bottom: var(--space-9);">
            <div style="display: flex; align-items: center; justify-content: space-between; gap: var(--space-5); flex-wrap: wrap; margin-bottom: var(--space-7);">
                <div>
                    <h3 style="font-size: var(--text-md); font-weight: var(--font-bold); margin: 0; color: var(--text-color); display: flex; align-items: center; gap: var(--space-3);">
                        <i class="fas fa-wand-magic-sparkles text-primary"></i> Analisis Otomatis Ketersediaan
                    </h3>
                    <p style="font-size: var(--text-xs); color: var(--text-muted); margin: var(--space-1) 0 0 0;">
                        Dihitung dari {{ $analisis['total_item'] }} item inventaris dalam cakupan Anda.
                    </p>
                </div>
                @if ($canCreate)
                    <button type="button" class="btn btn-primary btn-sm btn-trigger-analisis"
                        style="display: inline-flex; align-items: center; gap: 6px; padding: 7px 14px; font-size: var(--text-xs); border-radius: var(--radius-md);"
                        title="Proses &amp; masukkan hasil analisis stok kritis ke daftar kebutuhan">
                        <i class="fas fa-sync-alt"></i> Terapkan ke Daftar Kebutuhan
                    </button>
                @endif
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: var(--space-7);">
                {{-- Kritis --}}
                <div style="border: 1px solid rgba(239,68,68,0.3); border-radius: var(--radius-lg); padding: var(--space-7); background: rgba(239,68,68,0.04); display: flex; flex-direction: column;">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: var(--space-5);">
                        <span style="font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--danger, #ef4444);">
                            <i class="fas fa-circle-exclamation me-1"></i> Stok Kritis / Rusak Berat
                        </span>
                        <span class="badge badge-danger" style="font-size: var(--text-2xs);">{{ $analisis['jumlah_kritis'] }}</span>
                    </div>
                    <div style="flex: 1; display: flex; flex-direction: column; gap: var(--space-3);">
                        @forelse ($analisis['kritis'] as $it)
                            <div style="display: flex; justify-content: space-between; align-items: center; gap: var(--space-3); padding: var(--space-2) 0; border-bottom: 1px dashed var(--border-color); font-size: var(--text-xs);">
                                <div style="min-width: 0; flex: 1;">
                                    <div style="font-weight: var(--font-semibold); color: var(--text-color); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $it->nama_item }}</div>
                                    <div style="color: var(--danger, #ef4444); font-size: var(--text-2xs);">{{ $it->ringkasan_masalah ?? ((float)$it->stok_tersedia . ' ' . $it->satuan) }}</div>
                                </div>
                                @if ($canCreate)
                                    <button type="button" class="btn btn-outline btn-quick-kebutuhan"
                                        style="font-size: var(--text-2xs); padding: 3px 8px; height: 26px; border-radius: var(--radius-sm); white-space: nowrap;"
                                        data-nama="{{ $it->nama_item }}"
                                        data-jenis="{{ $it->jenis }}"
                                        data-kategori="{{ $it->kategori ?? '' }}"
                                        data-lokasi="{{ $it->ruang_lab_nama ?? '' }}"
                                        data-satuan="{{ $it->satuan }}"
                                        data-kebutuhan="{{ (float) $it->stok_total }}"
                                        data-tersedia="{{ (float) ($it->jumlah_baik > 0 ? $it->jumlah_baik : $it->stok_tersedia) }}"
                                        data-prioritas="kritis"
                                        data-sumber="kerusakan"
                                        data-analisis="Kondisi kritis/rusak berat: {{ $it->ringkasan_masalah ?? 'membutuhkan pengadaan unit baru' }}."
                                        title="Buat usulan kebutuhan untuk item ini">
                                        <i class="fas fa-plus me-1"></i> Analisis
                                    </button>
                                @endif
                            </div>
                        @empty
                            <p style="font-size: var(--text-xs); color: var(--text-muted); margin: 0;">Tidak ada item kritis.</p>
                        @endforelse
                    </div>
                </div>

                {{-- Perlu perhatian --}}
                <div style="border: 1px solid rgba(245,158,11,0.3); border-radius: var(--radius-lg); padding: var(--space-7); background: rgba(245,158,11,0.04); display: flex; flex-direction: column;">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: var(--space-5);">
                        <span style="font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--warning, #f59e0b);">
                            <i class="fas fa-triangle-exclamation me-1"></i> Stok Menipis / Rusak Ringan
                        </span>
                        <span class="badge badge-warning" style="font-size: var(--text-2xs);">{{ $analisis['jumlah_perhatian'] }}</span>
                    </div>
                    <div style="flex: 1; display: flex; flex-direction: column; gap: var(--space-3);">
                        @forelse ($analisis['perlu_perhatian'] as $it)
                            <div style="display: flex; justify-content: space-between; align-items: center; gap: var(--space-3); padding: var(--space-2) 0; border-bottom: 1px dashed var(--border-color); font-size: var(--text-xs);">
                                <div style="min-width: 0; flex: 1;">
                                    <div style="font-weight: var(--font-semibold); color: var(--text-color); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $it->nama_item }}</div>
                                    <div style="color: var(--warning, #f59e0b); font-size: var(--text-2xs);">{{ $it->ringkasan_masalah ?? ((float)$it->stok_tersedia . '/' . (float)$it->stok_total . ' ' . $it->satuan) }}</div>
                                </div>
                                @if ($canCreate)
                                    <button type="button" class="btn btn-outline btn-quick-kebutuhan"
                                        style="font-size: var(--text-2xs); padding: 3px 8px; height: 26px; border-radius: var(--radius-sm); white-space: nowrap;"
                                        data-nama="{{ $it->nama_item }}"
                                        data-jenis="{{ $it->jenis }}"
                                        data-kategori="{{ $it->kategori ?? '' }}"
                                        data-lokasi="{{ $it->ruang_lab_nama ?? '' }}"
                                        data-satuan="{{ $it->satuan }}"
                                        data-kebutuhan="{{ (float) $it->stok_total }}"
                                        data-tersedia="{{ (float) ($it->jumlah_baik > 0 ? $it->jumlah_baik : $it->stok_tersedia) }}"
                                        data-prioritas="tinggi"
                                        data-sumber="praktik"
                                        data-analisis="Perhatian: {{ $it->ringkasan_masalah ?? 'stok menipis' }}."
                                        title="Buat usulan kebutuhan untuk item ini">
                                        <i class="fas fa-plus me-1"></i> Analisis
                                    </button>
                                @endif
                            </div>
                        @empty
                            <p style="font-size: var(--text-xs); color: var(--text-muted); margin: 0;">Tidak ada item menipis.</p>
                        @endforelse
                    </div>
                </div>

                {{-- Kedaluwarsa --}}
                <div style="border: 1px solid rgba(139,92,246,0.3); border-radius: var(--radius-lg); padding: var(--space-7); background: rgba(139,92,246,0.04); display: flex; flex-direction: column;">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: var(--space-5);">
                        <span style="font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--purple, #8b5cf6);">
                            <i class="fas fa-hourglass-end me-1"></i> Mendekati / Lewat Kedaluwarsa
                        </span>
                        <span class="badge" style="font-size: var(--text-2xs); background: rgba(139,92,246,0.15); color: var(--purple, #8b5cf6);">{{ $analisis['jumlah_kadaluwarsa'] }}</span>
                    </div>
                    <div style="flex: 1; display: flex; flex-direction: column; gap: var(--space-3);">
                        @forelse ($analisis['kadaluwarsa'] as $it)
                            <div style="display: flex; justify-content: space-between; align-items: center; gap: var(--space-3); padding: var(--space-2) 0; border-bottom: 1px dashed var(--border-color); font-size: var(--text-xs);">
                                <div style="min-width: 0; flex: 1;">
                                    <div style="font-weight: var(--font-semibold); color: var(--text-color); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $it->nama_item }}</div>
                                    <div style="color: var(--purple, #8b5cf6); font-size: var(--text-2xs);">{{ $it->ringkasan_masalah ?? 'Kedaluwarsa' }}</div>
                                </div>
                                @if ($canCreate)
                                    <button type="button" class="btn btn-outline btn-quick-kebutuhan"
                                        style="font-size: var(--text-2xs); padding: 3px 8px; height: 26px; border-radius: var(--radius-sm); white-space: nowrap;"
                                        data-nama="{{ $it->nama_item }}"
                                        data-jenis="{{ $it->jenis }}"
                                        data-kategori="{{ $it->kategori ?? '' }}"
                                        data-lokasi="{{ $it->ruang_lab_nama ?? '' }}"
                                        data-satuan="{{ $it->satuan }}"
                                        data-kebutuhan="{{ (float) $it->stok_total }}"
                                        data-tersedia="0"
                                        data-prioritas="tinggi"
                                        data-sumber="praktik"
                                        data-analisis="Bahan kedaluwarsa: {{ $it->ringkasan_masalah ?? 'butuh penggantian bahan segar' }}."
                                        title="Buat usulan kebutuhan untuk item ini">
                                        <i class="fas fa-plus me-1"></i> Analisis
                                    </button>
                                @endif
                            </div>
                        @empty
                            <p style="font-size: var(--text-xs); color: var(--text-muted); margin: 0;">Tidak ada item kedaluwarsa.</p>
                        @endforelse
                    </div>
                </div>

                {{-- Temuan Berita Acara Kerusakan & Ceklist Pemeriksaan --}}
                @if (($analisis['jumlah_ba'] ?? 0) > 0 || ($analisis['jumlah_ceklist'] ?? 0) > 0)
                    <div style="border: 1px solid rgba(59,130,246,0.3); border-radius: var(--radius-lg); padding: var(--space-7); background: rgba(59,130,246,0.04); display: flex; flex-direction: column;">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: var(--space-5);">
                            <span style="font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--primary, #3b82f6);">
                                <i class="fas fa-clipboard-check me-1"></i> Temuan Lapangan &amp; BA
                            </span>
                            <span class="badge badge-primary" style="font-size: var(--text-2xs);">{{ ($analisis['jumlah_ba'] ?? 0) + ($analisis['jumlah_ceklist'] ?? 0) }}</span>
                        </div>
                        <div style="flex: 1; display: flex; flex-direction: column; gap: var(--space-3);">
                            @foreach ($analisis['ba_kerusakan'] ?? [] as $ba)
                                <div style="display: flex; justify-content: space-between; align-items: center; gap: var(--space-3); padding: var(--space-2) 0; border-bottom: 1px dashed var(--border-color); font-size: var(--text-xs);">
                                    <div style="min-width: 0; flex: 1;">
                                        <div style="font-weight: var(--font-semibold); color: var(--text-color); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $ba->nama_alat }}</div>
                                        <div style="color: var(--danger, #ef4444); font-size: var(--text-2xs);">BA: {{ $ba->nomor_ba }} &bull; {{ (float)$ba->jumlah_rusak }} {{ $ba->satuan }} rusak ({{ $ba->tingkat_kerusakan }})</div>
                                    </div>
                                    @if ($canCreate)
                                        <button type="button" class="btn btn-outline btn-quick-kebutuhan"
                                            style="font-size: var(--text-2xs); padding: 3px 8px; height: 26px; border-radius: var(--radius-sm); white-space: nowrap;"
                                            data-nama="{{ $ba->nama_alat }}"
                                            data-jenis="alat"
                                            data-satuan="{{ $ba->satuan ?: 'unit' }}"
                                            data-kebutuhan="{{ (float) $ba->jumlah_rusak }}"
                                            data-tersedia="0"
                                            data-prioritas="kritis"
                                            data-sumber="kerusakan"
                                            data-analisis="Berdasarkan Berita Acara Kerusakan {{ $ba->nomor_ba }} ({{ (float)$ba->jumlah_rusak }} {{ $ba->satuan }} rusak {{ $ba->tingkat_kerusakan }}). Penyebab: {{ $ba->penyebab ?: 'Kerusakan saat praktikum' }}."
                                            title="Buat usulan kebutuhan dari BA Kerusakan">
                                            <i class="fas fa-plus me-1"></i> Analisis
                                        </button>
                                    @endif
                                </div>
                            @endforeach

                            @foreach ($analisis['ceklist_temuan'] ?? [] as $ck)
                                <div style="display: flex; justify-content: space-between; align-items: center; gap: var(--space-3); padding: var(--space-2) 0; border-bottom: 1px dashed var(--border-color); font-size: var(--text-xs);">
                                    <div style="min-width: 0; flex: 1;">
                                        <div style="font-weight: var(--font-semibold); color: var(--text-color); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $ck->nama_item }}</div>
                                        <div style="color: var(--warning, #f59e0b); font-size: var(--text-2xs);">Ceklist: {{ $ck->ruang_lab_nama }} &bull; {{ $ck->hasil === 'tidak_ada' ? 'Alat Tidak Ditemukan' : 'Tidak Sesuai Standar' }}</div>
                                    </div>
                                    @if ($canCreate)
                                        <button type="button" class="btn btn-outline btn-quick-kebutuhan"
                                            style="font-size: var(--text-2xs); padding: 3px 8px; height: 26px; border-radius: var(--radius-sm); white-space: nowrap;"
                                            data-nama="{{ $ck->nama_item }}"
                                            data-jenis="alat"
                                            data-lokasi="{{ $ck->ruang_lab_nama }}"
                                            data-satuan="unit"
                                            data-kebutuhan="1"
                                            data-tersedia="0"
                                            data-prioritas="{{ $ck->hasil === 'tidak_ada' ? 'kritis' : 'sedang' }}"
                                            data-sumber="praktik"
                                            data-analisis="Temuan pemeriksaan di {{ $ck->ruang_lab_nama }}: {{ $ck->hasil === 'tidak_ada' ? 'Alat hilang/tidak ditemukan' : 'Kondisi fisik/fungsi tidak sesuai kelayakan' }}. Catatan: {{ $ck->keterangan ?: ($ck->tindak_lanjut ?: '-') }}."
                                            title="Buat usulan kebutuhan dari Temuan Ceklist">
                                            <i class="fas fa-plus me-1"></i> Analisis
                                        </button>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- 4. Datatable Terpadu --}}
        <div class="card datatable-card-wrapper" id="tableDataContainer">
            <div class="datatable-toolbar">
                <form method="GET" action="{{ route('dashboard.laboran.kebutuhan.index') }}" id="filterForm" style="margin: 0;">
                    <div style="display: flex; flex-wrap: wrap; gap: var(--space-6); justify-content: space-between; align-items: center;">
                        <div style="display: flex; flex-wrap: wrap; gap: var(--space-5); align-items: center;">
                            <div class="toolbar-entries">
                                <label for="perPageSelect" style="margin: 0;">Tampilkan</label>
                                <select id="perPageSelect" name="perPage" class="per-page-select" style="height: 38px;">
                                    @foreach ([10, 15, 25, 50, 100] as $n)
                                        <option value="{{ $n }}" {{ $perPage == $n ? 'selected' : '' }}>{{ $n }}</option>
                                    @endforeach
                                </select>
                                <span>entri</span>
                            </div>

                            <select name="jenis" id="filterJenis" class="toolbar-filter-select" style="height: 38px;" title="Filter Jenis">
                                <option value="">Semua Jenis</option>
                                @foreach ($jenisList as $k => $v)
                                    <option value="{{ $k }}" {{ $jenisFilter === $k ? 'selected' : '' }}>{{ $v }}</option>
                                @endforeach
                            </select>

                            <select name="prioritas" id="filterPrioritas" class="toolbar-filter-select" style="height: 38px;" title="Filter Prioritas">
                                <option value="">Semua Prioritas</option>
                                @foreach ($prioritasList as $k => $v)
                                    <option value="{{ $k }}" {{ $prioritasFilter === $k ? 'selected' : '' }}>{{ $v }}</option>
                                @endforeach
                            </select>

                            <select name="status" id="filterStatus" class="toolbar-filter-select" style="height: 38px;" title="Filter Status">
                                <option value="">Semua Status</option>
                                @foreach ($statusList as $k => $v)
                                    <option value="{{ $k }}" {{ $statusFilter === $k ? 'selected' : '' }}>{{ $v }}</option>
                                @endforeach
                            </select>

                            <select name="lab" id="filterLab" class="toolbar-filter-select" style="height: 38px;" title="Filter Lokasi">
                                <option value="">Semua Lokasi</option>
                                @foreach ($lokasiOptions as $lok)
                                    <option value="{{ $lok }}" {{ $labFilter === $lok ? 'selected' : '' }}>{{ $lok }}</option>
                                @endforeach
                            </select>

                            @if ($search || $jenisFilter || $prioritasFilter || $statusFilter || $labFilter)
                                <a href="{{ route('dashboard.laboran.kebutuhan.index') }}" class="btn btn-outline btn-reset-filter"
                                    style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md);"
                                    title="Reset filter" data-live-reset><i class="fas fa-undo"></i></a>
                            @endif
                        </div>

                        <div class="live-search-wrap" style="height: 38px;">
                            <i class="fas fa-search search-icon"></i>
                            <input type="text" name="q" id="liveSearch" value="{{ $search }}"
                                placeholder="Cari nama item, spesifikasi, atau mapel..." autocomplete="off">
                            <button type="button" id="clearSearch" class="clear-search {{ $search ? 'visible' : '' }}" title="Hapus pencarian">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="table-responsive-stack" style="overflow-x: auto; width: 100%;">
                <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                    <thead>
                        <tr style="border-bottom: 1px solid var(--border-color); background: rgba(255, 255, 255, 0.02);">
                            <th style="padding: var(--space-6) var(--space-7); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase;">No</th>
                            <th style="padding: var(--space-6) var(--space-7); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase;">Item &amp; Spesifikasi</th>
                            <th style="padding: var(--space-6) var(--space-7); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; text-align: center;">Kebutuhan</th>
                            <th style="padding: var(--space-6) var(--space-7); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; text-align: center;">Tersedia</th>
                            <th style="padding: var(--space-6) var(--space-7); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; text-align: center;">Kekurangan</th>
                            <th style="padding: var(--space-6) var(--space-7); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; text-align: center;">Prioritas</th>
                            <th style="padding: var(--space-6) var(--space-7); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; text-align: center;">Status</th>
                            <th style="padding: var(--space-6) var(--space-7); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; text-align: right;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($list as $idx => $item)
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <td data-label="No" style="padding: var(--space-6) var(--space-7); font-size: var(--text-base); color: var(--text-muted);">{{ $list->firstItem() + $idx }}</td>
                                <td data-label="Item" style="padding: var(--space-6) var(--space-7);">
                                    <div style="font-weight: var(--font-bold); color: var(--text-color); font-size: var(--text-base);">{{ $item->nama_item }}</div>
                                    @if ($item->spesifikasi)
                                        <div style="font-size: var(--text-xs); color: var(--text-muted);">{{ \Illuminate\Support\Str::limit($item->spesifikasi, 70) }}</div>
                                    @endif
                                    <div style="display: flex; gap: var(--space-3); flex-wrap: wrap; margin-top: var(--space-2);">
                                        <span class="badge badge-outline" style="font-size: var(--text-2xs);">{{ $jenisList[$item->jenis] ?? $item->jenis }}</span>
                                        @if ($item->nama_jurusan)
                                            <span class="badge badge-outline" style="font-size: var(--text-2xs);">{{ $item->singkatan ?: $item->nama_jurusan }}</span>
                                        @endif
                                        @if ($item->ruang_lab_nama)
                                            <span class="badge badge-outline" style="font-size: var(--text-2xs);">{{ $item->ruang_lab_nama }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td data-label="Kebutuhan" style="padding: var(--space-6) var(--space-7); text-align: center; font-size: var(--text-base); font-weight: var(--font-semibold);">
                                    {{ (float) $item->jumlah_kebutuhan }} <span style="font-size: var(--text-2xs); color: var(--text-muted);">{{ $item->satuan }}</span>
                                </td>
                                <td data-label="Tersedia" style="padding: var(--space-6) var(--space-7); text-align: center; font-size: var(--text-base);">{{ (float) $item->jumlah_tersedia }}</td>
                                <td data-label="Kekurangan" style="padding: var(--space-6) var(--space-7); text-align: center; font-size: var(--text-base); font-weight: var(--font-bold); color: {{ (float) $item->selisih > 0 ? 'var(--danger, #ef4444)' : 'var(--success, #10b981)' }};">
                                    {{ (float) $item->selisih }}
                                </td>
                                <td data-label="Prioritas" style="padding: var(--space-6) var(--space-7); text-align: center;">
                                    @php
                                        $pc = match ($item->prioritas) {
                                            'kritis' => 'badge-danger',
                                            'tinggi' => 'badge-warning',
                                            'sedang' => 'badge-info',
                                            default => 'badge-outline',
                                        };
                                    @endphp
                                    <span class="badge {{ $pc }}" style="font-size: var(--text-2xs);">{{ $prioritasList[$item->prioritas] ?? $item->prioritas }}</span>
                                </td>
                                <td data-label="Status" style="padding: var(--space-6) var(--space-7); text-align: center;">
                                    @php
                                        $sc = match ($item->status) {
                                            'disetujui', 'dipenuhi' => 'badge-success',
                                            'ditolak' => 'badge-danger',
                                            'diverifikasi' => 'badge-info',
                                            default => 'badge-outline',
                                        };
                                    @endphp
                                    <span class="badge {{ $sc }}" style="font-size: var(--text-2xs);">{{ $statusList[$item->status] ?? $item->status }}</span>
                                </td>
                                <td data-label="Aksi" style="padding: var(--space-6) var(--space-7); text-align: right;">
                                    <div class="table-actions" style="justify-content: flex-end; gap: var(--space-3);">
                                        @if ($canUpdate)
                                            <button type="button" class="btn-icon btn-edit-kebutuhan"
                                                data-id="{{ $item->id }}"
                                                data-nama="{{ $item->nama_item }}"
                                                data-jenis="{{ $item->jenis }}"
                                                data-kategori="{{ $item->kategori }}"
                                                data-spesifikasi="{{ $item->spesifikasi }}"
                                                data-kebutuhan="{{ (float) $item->jumlah_kebutuhan }}"
                                                data-tersedia="{{ (float) $item->jumlah_tersedia }}"
                                                data-satuan="{{ $item->satuan }}"
                                                data-prioritas="{{ $item->prioritas }}"
                                                data-sumber="{{ $item->sumber_analisis }}"
                                                data-analisis="{{ $item->analisis_kebutuhan }}"
                                                data-mapel="{{ $item->referensi_mapel }}"
                                                data-lokasi="{{ $item->ruang_lab_nama }}"
                                                data-ta="{{ $item->tahun_ajaran }}"
                                                data-semester="{{ $item->semester }}"
                                                data-tanggal="{{ $item->tanggal_input }}"
                                                data-status="{{ $item->status }}"
                                                data-catatan="{{ $item->catatan_verifikasi }}"
                                                data-jurusan="{{ $item->jurusan_id }}"
                                                title="Ubah Data Kebutuhan">
                                                <i class="fas fa-pen-to-square" style="color: var(--primary);"></i>
                                            </button>
                                        @endif
                                        @if ($canDelete)
                                            <button type="button" class="btn-icon btn-delete-kebutuhan"
                                                data-id="{{ $item->id }}" data-nama="{{ $item->nama_item }}"
                                                title="Hapus Data Kebutuhan" style="color: var(--danger, #ef4444);">
                                                <i class="fas fa-trash-can"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" style="padding: var(--space-11); text-align: center; color: var(--text-muted);">
                                    <i class="fas fa-folder-open mb-2" style="font-size: var(--text-3xl); opacity: 0.5;"></i>
                                    <div>Belum ada data kebutuhan alat/bahan.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($list->hasPages())
                <div class="datatable-footer">
                    @include('partials.datatable-pagination', ['paginator' => $list])
                </div>
            @endif
        </div>

        @include('dashboard.laboran.partials.kebutuhan-modal-form')
    </div>

        @push('scripts')
            <script src="{{ asset('js/laboran-common.js') }}?v={{ file_exists(public_path('js/laboran-common.js')) ? filemtime(public_path('js/laboran-common.js')) : time() }}"></script>
            <script src="{{ asset('js/laboran-kebutuhan.js') }}?v={{ file_exists(public_path('js/laboran-kebutuhan.js')) ? filemtime(public_path('js/laboran-kebutuhan.js')) : time() }}"></script>
        @endpush
    @endsection
