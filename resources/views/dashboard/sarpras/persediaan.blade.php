@extends('layouts.dashboard')

@section('title', 'Barang Habis Pakai & ATK — Sarpras SAE')
@section('dash_title', 'Barang Habis Pakai & ATK')

@section('content')
    <div class="dash-content-inner">
        {{-- 1. Header Banner & Action Button --}}
        <div class="dash-banner"
            style="display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; margin-bottom: 20px;">
            <div style="display: flex; align-items: center; gap: 14px;">
                <div
                    style="width: 48px; height: 48px; border-radius: var(--radius-lg, 12px); background: rgba(245,158,11,0.12); color: var(--warning, #f59e0b); display: flex; align-items: center; justify-content: center; font-size: var(--text-2xl, 1.5rem); flex-shrink: 0;">
                    <i class="fas fa-boxes-packing"></i>
                </div>
                <div>
                    <h2 style="font-size: var(--text-xl, 1.25rem); font-weight: 800; color: var(--text-color); margin: 0;">
                        Barang Habis Pakai &amp; ATK
                    </h2>
                    <div style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); margin-top: 2px;">
                        Pengelolaan stok ATK, kertas, perlengkapan KBM, bahan kebersihan, dan kartu kendali mutasi.
                    </div>
                </div>
            </div>
            <div class="dash-banner-actions" style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                @if ($canCreate)
                    <button type="button" class="btn btn-outline" id="btnOpenModalMutasiGlobal"
                        style="height: 38px; padding: 0 14px; display: inline-flex; align-items: center; gap: 6px; border-radius: var(--radius-md, 8px); font-size: var(--text-sm, 0.8125rem);"
                        title="Catat Mutasi Masuk / Keluar Barang">
                        <i class="fas fa-arrow-right-arrow-left text-warning"></i> Catat Mutasi
                    </button>
                    <button type="button" class="btn btn-primary" id="btnOpenModalBarang"
                        style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px); font-size: var(--text-md, 1rem);"
                        title="Tambah Barang Persediaan Baru">
                        <i class="fas fa-plus"></i>
                    </button>
                @endif
            </div>
        </div>

        {{-- 2. Stat Grid (4 Kartu Ringkasan) --}}
        <div class="dash-stat-grid" style="margin-bottom: 24px; gap: 14px;">
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(99, 102, 241, 0.15); color: var(--primary);">
                    <i class="fas fa-cubes"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ number_format($statTotalJenis, 0, ',', '.') }}</div>
                    <div class="dash-stat-label">Jenis Barang Habis Pakai</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(16, 185, 129, 0.15); color: var(--success, #10b981);">
                    <i class="fas fa-layer-group"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ number_format($statTotalStok, 0, ',', '.') }}</div>
                    <div class="dash-stat-label">Total Unit Fisik Tersedia</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(245, 158, 11, 0.15); color: var(--warning, #f59e0b);">
                    <i class="fas fa-triangle-exclamation"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ number_format($statMenipis, 0, ',', '.') }}</div>
                    <div class="dash-stat-label">Stok Menipis / Kritis</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(59, 130, 246, 0.15); color: var(--primary, #3b82f6);">
                    <i class="fas fa-arrow-right-arrow-left"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ number_format($statMutasiBulanIni, 0, ',', '.') }}</div>
                    <div class="dash-stat-label">Mutasi Bulan Ini</div>
                </div>
            </div>
        </div>

        {{-- 3. Navigasi Tab Baku SAE (Stok vs Mutasi) --}}
        <div class="dash-tabs-nav dash-desktop-tabs" style="margin-bottom: 20px;">
            <a href="{{ route('dashboard.sarpras.persediaan.index', ['tab' => 'stok']) }}"
                class="btn {{ $tab === 'stok' ? 'btn-primary' : 'btn-outline' }}" style="text-decoration: none;">
                <i class="fas fa-boxes-stacked me-1"></i> Stok Barang Persediaan ({{ number_format($statTotalJenis) }})
            </a>
            <a href="{{ route('dashboard.sarpras.persediaan.index', ['tab' => 'mutasi']) }}"
                class="btn {{ $tab === 'mutasi' ? 'btn-primary' : 'btn-outline' }}" style="text-decoration: none;">
                <i class="fas fa-clock-rotate-left me-1"></i> Riwayat Mutasi Keluar-Masuk ({{ number_format($statMutasiBulanIni) }})
            </a>
        </div>

        {{-- Mobile Dropdown Selector --}}
        <div class="dash-mobile-tab-select-wrap" style="margin-bottom: 20px;">
            <div class="dash-custom-dropdown">
                <button type="button" class="custom-dropdown-trigger" aria-haspopup="listbox" aria-expanded="false">
                    <div class="custom-dropdown-trigger-label">
                        <i class="fas {{ $tab === 'stok' ? 'fa-boxes-stacked' : 'fa-clock-rotate-left' }} text-primary me-2"></i>
                        <span>{{ $tab === 'stok' ? 'Stok Barang Persediaan' : 'Riwayat Mutasi Keluar-Masuk' }}</span>
                    </div>
                    <i class="fas fa-chevron-down custom-dropdown-arrow"></i>
                </button>
                <div class="custom-dropdown-menu" role="listbox">
                    <a href="{{ route('dashboard.sarpras.persediaan.index', ['tab' => 'stok']) }}"
                        class="custom-dropdown-item {{ $tab === 'stok' ? 'active' : '' }}">
                        <i class="fas fa-boxes-stacked text-primary me-2"></i> Stok Barang Persediaan
                    </a>
                    <a href="{{ route('dashboard.sarpras.persediaan.index', ['tab' => 'mutasi']) }}"
                        class="custom-dropdown-item {{ $tab === 'mutasi' ? 'active' : '' }}">
                        <i class="fas fa-clock-rotate-left text-primary me-2"></i> Riwayat Mutasi Keluar-Masuk
                    </a>
                </div>
            </div>
        </div>

        @if ($tab === 'stok')
            {{-- Datatable Stok Barang Persediaan --}}
            <div class="card datatable-card-wrapper" id="tableDataContainer">
                <div class="datatable-toolbar">
                    <form method="GET" action="{{ route('dashboard.sarpras.persediaan.index') }}" id="filterFormStok" style="margin: 0;">
                        <input type="hidden" name="tab" value="stok">
                        <div style="display: flex; flex-wrap: wrap; gap: 12px; justify-content: space-between; align-items: center;">
                            <div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
                                <div class="toolbar-entries">
                                    <label for="perPageSelect" style="margin: 0;">Tampilkan</label>
                                    <select id="perPageSelect" name="perPage" class="per-page-select" style="height: 38px;">
                                        @foreach ([10, 15, 25, 50, 100] as $n)
                                            <option value="{{ $n }}" {{ request('perPage', 15) == $n ? 'selected' : '' }}>
                                                {{ $n }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <span>entri</span>
                                </div>

                                <select name="kategori" id="filterKategoriPersediaan" class="toolbar-filter-select" style="height: 38px;">
                                    <option value="">Semua Kategori</option>
                                    @foreach (\App\Http\Controllers\SarprasPersediaanController::KATEGORI as $kKey => $kLabel)
                                        <option value="{{ $kKey }}" {{ $kategoriFilter === $kKey ? 'selected' : '' }}>{{ $kLabel }}</option>
                                    @endforeach
                                </select>

                                <select name="status_stok" id="filterStatusStok" class="toolbar-filter-select" style="height: 38px;">
                                    <option value="">Semua Status Stok</option>
                                    <option value="aman" {{ $statusStok === 'aman' ? 'selected' : '' }}>Stok Aman</option>
                                    <option value="menipis" {{ $statusStok === 'menipis' ? 'selected' : '' }}>Menipis (&le; Min)</option>
                                    <option value="habis" {{ $statusStok === 'habis' ? 'selected' : '' }}>Habis (0)</option>
                                </select>

                                @if ($search || $kategoriFilter || $statusStok)
                                    <a href="{{ route('dashboard.sarpras.persediaan.index', ['tab' => 'stok']) }}"
                                        class="btn btn-outline btn-reset-filter"
                                        style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px);"
                                        title="Reset filter"><i class="fas fa-undo"></i></a>
                                @endif
                            </div>

                            <div class="live-search-wrap" style="height: 38px;">
                                <i class="fas fa-search search-icon"></i>
                                <input type="text" name="q" id="liveSearch" value="{{ $search }}"
                                    placeholder="Cari kode, nama barang, spesifikasi..." autocomplete="off">
                                <button type="button" id="clearSearch" class="clear-search {{ $search ? 'visible' : '' }}"
                                    title="Hapus pencarian">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="table-responsive-stack" style="overflow-x: auto; width: 100%;">
                    <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                        <thead>
                            <tr style="border-bottom: 1px solid var(--border-color); ">
                                <th style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                    No</th>
                                <th style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                    Nama Barang &amp; Spesifikasi</th>
                                <th style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                    Kategori &amp; Lokasi</th>
                                <th style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center;">
                                    Stok Saat Ini</th>
                                <th style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center;">
                                    Status</th>
                                <th style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: right;">
                                    Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($list as $idx => $item)
                                @php
                                    $isHabis = $item->stok_saat_ini <= 0;
                                    $isMenipis = $item->stok_saat_ini <= $item->stok_minimum && !$isHabis;
                                @endphp
                                <tr style="border-bottom: 1px solid var(--border-color); transition: background 0.2s ease;">
                                    <td data-label="No" style="padding: 14px 18px; font-size: var(--text-base, 0.875rem); color: var(--text-muted);">
                                        {{ $list->firstItem() + $idx }}
                                    </td>
                                    <td data-label="Barang" style="padding: 14px 18px;">
                                        <div style="font-weight: 700; color: var(--text-color); font-size: var(--text-base, 0.875rem);">
                                            {{ $item->nama_barang }}
                                        </div>
                                        <div style="font-size: var(--text-sm, 0.8125rem); color: var(--primary); font-family: monospace;">
                                            {{ $item->kode_barang }}
                                        </div>
                                        @if ($item->spesifikasi)
                                            <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-top: 2px;">
                                                {{ $item->spesifikasi }}
                                            </div>
                                        @endif
                                    </td>
                                    <td data-label="Kategori & Lokasi" style="padding: 14px 18px; font-size: var(--text-base, 0.875rem);">
                                        <span class="badge badge-info" style="font-size: var(--text-2xs, 0.6875rem);">{{ $item->kategori }}</span>
                                        <div style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); margin-top: 4px;">
                                            <i class="fas fa-warehouse me-1 text-muted"></i> {{ $item->lokasi_simpan ?: 'Gudang Sarpras' }}
                                        </div>
                                    </td>
                                    <td data-label="Stok" style="padding: 14px 18px; text-align: center;">
                                        <div style="font-weight: 800; font-size: var(--text-lg, 1.125rem); color: {{ $isHabis ? 'var(--danger, #ef4444)' : ($isMenipis ? 'var(--warning, #f59e0b)' : 'var(--text-color)') }};">
                                            {{ number_format($item->stok_saat_ini, 0, ',', '.') }}
                                        </div>
                                        <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted);">
                                            {{ $item->satuan }} (Min: {{ $item->stok_minimum }})
                                        </div>
                                    </td>
                                    <td data-label="Status" style="padding: 14px 18px; text-align: center;">
                                        @if ($isHabis)
                                            <span class="badge badge-danger">Habis</span>
                                        @elseif ($isMenipis)
                                            <span class="badge badge-warning">Menipis</span>
                                        @else
                                            <span class="badge badge-success">Aman</span>
                                        @endif
                                    </td>
                                    <td data-label="Aksi" style="padding: 14px 18px; text-align: right;">
                                        <div class="table-actions" style="justify-content: flex-end; gap: 6px;">
                                            @if ($canCreate)
                                                <button type="button" class="btn-icon btn-mutasi-item"
                                                    data-id="{{ $item->id }}" data-nama="{{ $item->nama_barang }}"
                                                    data-satuan="{{ $item->satuan }}" data-stok="{{ $item->stok_saat_ini }}"
                                                    title="Catat Mutasi Keluar/Masuk Barang Ini" style="color: var(--warning, #f59e0b);">
                                                    <i class="fas fa-arrow-right-arrow-left"></i>
                                                </button>
                                            @endif
                                            @if ($canUpdate)
                                                <button type="button" class="btn-icon btn-edit-barang"
                                                    data-id="{{ $item->id }}" data-kode="{{ $item->kode_barang }}"
                                                    data-nama="{{ $item->nama_barang }}" data-kategori="{{ $item->kategori }}"
                                                    data-spesifikasi="{{ $item->spesifikasi }}" data-satuan="{{ $item->satuan }}"
                                                    data-minimum="{{ $item->stok_minimum }}" data-lokasi="{{ $item->lokasi_simpan }}"
                                                    data-sumber="{{ $item->sumber_dana }}" data-harga="{{ $item->harga_satuan }}"
                                                    data-keterangan="{{ $item->keterangan }}" title="Edit Barang">
                                                    <i class="fas fa-pen-to-square"></i>
                                                </button>
                                            @endif
                                            @if ($canDelete)
                                                <button type="button" class="btn-icon btn-delete-barang"
                                                    data-id="{{ $item->id }}" data-nama="{{ $item->nama_barang }}"
                                                    title="Hapus Barang" style="color: var(--danger, #ef4444);">
                                                    <i class="fas fa-trash-can"></i>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" style="padding: 30px; text-align: center; color: var(--text-muted);">
                                        <i class="fas fa-folder-open mb-2" style="font-size: var(--text-3xl, 1.875rem); opacity: 0.5;"></i>
                                        <div>Belum ada data barang persediaan yang cocok.</div>
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
        @else
            {{-- Tab 2: Datatable Riwayat Mutasi Keluar-Masuk --}}
            <div class="card datatable-card-wrapper" id="tableDataContainer">
                <div class="datatable-toolbar">
                    <form method="GET" action="{{ route('dashboard.sarpras.persediaan.index') }}" id="filterFormMutasi" style="margin: 0;">
                        <input type="hidden" name="tab" value="mutasi">
                        <div style="display: flex; flex-wrap: wrap; gap: 12px; justify-content: space-between; align-items: center;">
                            <div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
                                <div class="toolbar-entries">
                                    <label for="perPageSelectMutasi" style="margin: 0;">Tampilkan</label>
                                    <select id="perPageSelectMutasi" name="perPage" class="per-page-select" style="height: 38px;">
                                        @foreach ([10, 15, 25, 50, 100] as $n)
                                            <option value="{{ $n }}" {{ request('perPage', 15) == $n ? 'selected' : '' }}>
                                                {{ $n }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <span>entri</span>
                                </div>

                                <select name="jenis_mutasi" id="filterJenisMutasi" class="toolbar-filter-select" style="height: 38px;">
                                    <option value="">Semua Jenis Mutasi</option>
                                    <option value="keluar" {{ $jenisMutasi === 'keluar' ? 'selected' : '' }}>Pengeluaran (Keluar)</option>
                                    <option value="masuk" {{ $jenisMutasi === 'masuk' ? 'selected' : '' }}>Penerimaan (Masuk)</option>
                                </select>

                                @if ($search || $jenisMutasi)
                                    <a href="{{ route('dashboard.sarpras.persediaan.index', ['tab' => 'mutasi']) }}"
                                        class="btn btn-outline btn-reset-filter"
                                        style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px);"
                                        title="Reset filter"><i class="fas fa-undo"></i></a>
                                @endif
                            </div>

                            <div class="live-search-wrap" style="height: 38px;">
                                <i class="fas fa-search search-icon"></i>
                                <input type="text" name="q" id="liveSearchMutasi" value="{{ $search }}"
                                    placeholder="Cari penerima, barang, atau keperluan..." autocomplete="off">
                                <button type="button" class="clear-search {{ $search ? 'visible' : '' }}"
                                    onclick="document.getElementById('liveSearchMutasi').value=''; document.getElementById('filterFormMutasi').submit();"
                                    title="Hapus pencarian">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="table-responsive-stack" style="overflow-x: auto; width: 100%;">
                    <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                        <thead>
                            <tr style="border-bottom: 1px solid var(--border-color); ">
                                <th style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">No</th>
                                <th style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Tanggal</th>
                                <th style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Barang Persediaan</th>
                                <th style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center;">Jenis</th>
                                <th style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center;">Jumlah</th>
                                <th style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Penerima / Sumber &amp; Keperluan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($mutasiList as $idx => $m)
                                <tr style="border-bottom: 1px solid var(--border-color); transition: background 0.2s ease;">
                                    <td data-label="No" style="padding: 14px 18px; font-size: var(--text-base, 0.875rem); color: var(--text-muted);">
                                        {{ $mutasiList->firstItem() + $idx }}
                                    </td>
                                    <td data-label="Tanggal" style="padding: 14px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 600; color: var(--text-color);">
                                        {{ \Carbon\Carbon::parse($m->tanggal)->translatedFormat('d M Y') }}
                                    </td>
                                    <td data-label="Barang" style="padding: 14px 18px;">
                                        <div style="font-weight: 700; color: var(--text-color); font-size: var(--text-base, 0.875rem);">
                                            {{ $m->nama_barang }}
                                        </div>
                                        <div style="font-size: var(--text-2xs, 0.6875rem); color: var(--primary); font-family: monospace;">
                                            {{ $m->kode_barang }}
                                        </div>
                                    </td>
                                    <td data-label="Jenis" style="padding: 14px 18px; text-align: center;">
                                        @if ($m->jenis_mutasi === 'masuk')
                                            <span class="badge badge-success" style="font-size: var(--text-xs, 0.75rem);"><i class="fas fa-arrow-down me-1"></i> Masuk</span>
                                        @else
                                            <span class="badge badge-warning" style="font-size: var(--text-xs, 0.75rem);"><i class="fas fa-arrow-up me-1"></i> Keluar</span>
                                        @endif
                                    </td>
                                    <td data-label="Jumlah" style="padding: 14px 18px; text-align: center; font-size: var(--text-base, 0.875rem); font-weight: 800; color: {{ $m->jenis_mutasi === 'masuk' ? 'var(--success, #10b981)' : 'var(--warning, #f59e0b)' }};">
                                        {{ $m->jenis_mutasi === 'masuk' ? '+' : '-' }}{{ number_format($m->jumlah, 0, ',', '.') }} {{ $m->satuan }}
                                    </td>
                                    <td data-label="Keterangan" style="padding: 14px 18px; font-size: var(--text-sm, 0.8125rem);">
                                        <div style="font-weight: 700; color: var(--text-color);">
                                            <i class="fas fa-user text-muted me-1"></i> {{ $m->penerima_atau_sumber }}
                                        </div>
                                        @if ($m->keperluan)
                                            <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-top: 2px;">
                                                Keperluan: {{ $m->keperluan }}
                                            </div>
                                        @endif
                                        @if ($m->catatan)
                                            <div style="font-size: var(--text-2xs, 0.6875rem); color: var(--text-muted); font-style: italic;">
                                                Catatan: {{ $m->catatan }}
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" style="padding: 30px; text-align: center; color: var(--text-muted);">
                                        <i class="fas fa-clock-rotate-left mb-2" style="font-size: var(--text-3xl, 1.875rem); opacity: 0.5;"></i>
                                        <div>Belum ada catatan riwayat mutasi persediaan.</div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($mutasiList->hasPages())
                    <div class="datatable-footer">
                        @include('partials.datatable-pagination', ['paginator' => $mutasiList])
                    </div>
                @endif
            </div>
        @endif

        {{-- Modal Tambah / Edit Barang Persediaan --}}
        <div id="modalBarangPersediaan" class="modal-backdrop"
            style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
            <div class="card" style="width: 100%; max-width: 680px; max-height: 92vh; overflow-y: auto; padding: 24px; border-radius: var(--radius-lg, 12px);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h3 id="modalBarangTitle" style="margin: 0; font-size: var(--text-xl, 1.25rem); font-weight: 700; color: var(--text-color);">
                        Tambah Barang Habis Pakai (ATK)
                    </h3>
                    <button type="button" id="btnCloseModalBarang"
                        style="background: none; border: none; font-size: var(--text-xl, 1.25rem); color: var(--text-muted); cursor: pointer;"
                        aria-label="Tutup Modal">&times;</button>
                </div>

                <form id="formBarang" method="POST" action="{{ route('dashboard.sarpras.persediaan.store') }}">
                    @csrf
                    <input type="hidden" name="_method" id="barangMethod" value="POST">
                    <input type="hidden" name="id" id="barangId">

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; margin-bottom: 14px;">
                        <div>
                            <label style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">
                                Kode Barang <span style="font-size: var(--text-xs, 0.75rem); font-weight: 400; color: var(--text-muted);">(opsional)</span>
                            </label>
                            <input type="text" name="kode_barang" id="barangKode" class="form-control"
                                placeholder="Kosongkan untuk otomatis">
                            <small style="display: block; margin-top: 4px; font-size: var(--text-xs, 0.75rem); color: var(--text-muted);">
                                Otomatis berformat BHP-YYYYMM-NNNN.
                            </small>
                        </div>
                        <div>
                            <label style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">
                                Nama Barang <span style="color: var(--danger, #ef4444);">*</span>
                            </label>
                            <input type="text" name="nama_barang" id="barangNama" class="form-control"
                                placeholder="Kertas HVS A4 80gr / Spidol Whiteboard" required>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; margin-bottom: 14px;">
                        <div>
                            <label style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">
                                Kategori <span style="color: var(--danger, #ef4444);">*</span>
                            </label>
                            <select name="kategori" id="barangKategori" class="form-control" required>
                                @foreach (\App\Http\Controllers\SarprasPersediaanController::KATEGORI as $kKey => $kLabel)
                                    <option value="{{ $kKey }}">{{ $kLabel }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">
                                Spesifikasi / Merk
                            </label>
                            <input type="text" name="spesifikasi" id="barangSpesifikasi" class="form-control"
                                placeholder="PaperOne 80gr / Snowman Boardmarker">
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 12px; margin-bottom: 14px;">
                        <div id="wrapperBarangStokAwal">
                            <label style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">
                                Stok Awal <span style="color: var(--danger, #ef4444);">*</span>
                            </label>
                            <input type="number" min="0" name="stok_saat_ini" id="barangStokAwal" class="form-control" value="0" required>
                        </div>
                        <div>
                            <label style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">
                                Stok Minimum Peringatan <span style="color: var(--danger, #ef4444);">*</span>
                            </label>
                            <input type="number" min="0" name="stok_minimum" id="barangStokMin" class="form-control" value="5" required>
                        </div>
                        <div>
                            <label style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">
                                Satuan <span style="color: var(--danger, #ef4444);">*</span>
                            </label>
                            <input type="text" name="satuan" id="barangSatuan" class="form-control" value="pcs" placeholder="rim/pcs/dus/botol" required>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; margin-bottom: 14px;">
                        <div>
                            <label style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">
                                Lokasi Simpan
                            </label>
                            <input type="text" name="lokasi_simpan" id="barangLokasi" class="form-control" placeholder="Lemari ATK TU / Rak A-02">
                        </div>
                        <div>
                            <label style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">
                                Sumber Dana <span style="color: var(--danger, #ef4444);">*</span>
                            </label>
                            <select name="sumber_dana" id="barangSumber" class="form-control" required>
                                <option value="BOS Reguler">BOS Reguler</option>
                                <option value="BOS Kinerja">BOS Kinerja</option>
                                <option value="Komite / Yayasan">Komite / Yayasan</option>
                                <option value="Lainnya">Lainnya</option>
                            </select>
                        </div>
                        <div>
                            <label style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">
                                Estimasi Harga Satuan (Rp)
                            </label>
                            <input type="number" step="0.01" min="0" name="harga_satuan" id="barangHarga" class="form-control" placeholder="0">
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: 20px;">
                        <label style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">
                            Keterangan Tambahan
                        </label>
                        <textarea name="keterangan" id="barangKeterangan" class="form-control" rows="2" placeholder="Catatan pengadaan, batas penggunaan khusus, dsb..."></textarea>
                    </div>

                    <div style="display: flex; justify-content: flex-end; gap: 8px;">
                        <button type="button" class="btn btn-secondary" id="btnCancelModalBarang">Batal</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Simpan Barang</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Modal Catat Mutasi Keluar / Masuk --}}
        <div id="modalMutasiPersediaan" class="modal-backdrop"
            style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
            <div class="card" style="width: 100%; max-width: 580px; max-height: 90vh; overflow-y: auto; padding: 24px; border-radius: var(--radius-lg, 12px);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px;">
                    <h3 id="modalMutasiTitle" style="margin: 0; font-size: var(--text-xl, 1.25rem); font-weight: 700; color: var(--text-color);">
                        <i class="fas fa-arrow-right-arrow-left text-warning me-2"></i> Catat Mutasi Barang Persediaan
                    </h3>
                    <button type="button" id="btnCloseModalMutasi"
                        style="background: none; border: none; font-size: var(--text-xl, 1.25rem); color: var(--text-muted); cursor: pointer;"
                        aria-label="Tutup Modal">&times;</button>
                </div>

                <form id="formMutasiPersediaan" method="POST" action="">
                    @csrf
                    <div class="form-group" style="margin-bottom: 14px;">
                        <label style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">
                            Pilih Barang Persediaan <span style="color: var(--danger, #ef4444);">*</span>
                        </label>
                        <select id="mutasiSelectBarang" class="form-control" required style="font-weight: 600;">
                            <option value="">-- Pilih Barang --</option>
                            @foreach ($barangOptions as $bo)
                                <option value="{{ $bo->id }}" data-satuan="{{ $bo->satuan }}" data-stok="{{ $bo->stok_saat_ini }}">
                                    {{ $bo->nama_barang }} &bull; Sisa: {{ $bo->stok_saat_ini }} {{ $bo->satuan }} ({{ $bo->kode_barang }})
                                </option>
                            @endforeach
                        </select>
                        <div id="hintStokSisaMutasi" style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-top: 4px;"></div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                        <div>
                            <label style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">
                                Jenis Mutasi <span style="color: var(--danger, #ef4444);">*</span>
                            </label>
                            <select name="jenis_mutasi" id="mutasiJenis" class="form-control" required>
                                <option value="keluar">Pengeluaran (Barang Keluar / Dipakai)</option>
                                <option value="masuk">Penerimaan (Barang Masuk / Pembelian)</option>
                            </select>
                        </div>
                        <div>
                            <label style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">
                                Tanggal Mutasi <span style="color: var(--danger, #ef4444);">*</span>
                            </label>
                            <input type="date" name="tanggal" id="mutasiTanggal" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                        <div>
                            <label style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">
                                Jumlah <span style="color: var(--danger, #ef4444);">*</span>
                            </label>
                            <input type="number" min="1" name="jumlah" id="mutasiJumlah" class="form-control" value="1" required>
                        </div>
                        <div>
                            <label id="labelPenerimaMutasi" style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">
                                Penerima / Pengambil <span style="color: var(--danger, #ef4444);">*</span>
                            </label>
                            <input type="text" name="penerima_atau_sumber" id="mutasiPenerima" class="form-control" placeholder="Nama Guru / Staf / Unit Kerja" required>
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: 14px;">
                        <label style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">
                            Keperluan Penggunaan
                        </label>
                        <input type="text" name="keperluan" id="mutasiKeperluan" class="form-control" placeholder="Ujian Semester / Kegiatan KBM Kelas X / Administrasi Kantor">
                    </div>

                    <div class="form-group" style="margin-bottom: 20px;">
                        <label style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">
                            Catatan Tambahan
                        </label>
                        <textarea name="catatan" id="mutasiCatatan" class="form-control" rows="2" placeholder="Keterangan kondisi kemasan, nomor nota, dsb..."></textarea>
                    </div>

                    <div style="display: flex; justify-content: flex-end; gap: 8px;">
                        <button type="button" class="btn btn-secondary" id="btnCancelModalMutasi">Batal</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Simpan Mutasi</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const modalBarang = document.getElementById('modalBarangPersediaan');
            const btnOpenBarang = document.getElementById('btnOpenModalBarang');
            const btnCloseBarang = document.getElementById('btnCloseModalBarang');
            const btnCancelBarang = document.getElementById('btnCancelModalBarang');
            const formBarang = document.getElementById('formBarang');
            const modalBarangTitle = document.getElementById('modalBarangTitle');
            const methodBarang = document.getElementById('barangMethod');
            const wrapperStokAwal = document.getElementById('wrapperBarangStokAwal');

            function openModalBarang(isEdit = false, data = {}) {
                if (!modalBarang) return;
                modalBarang.style.display = 'flex';
                if (isEdit) {
                    modalBarangTitle.textContent = 'Edit Barang Persediaan';
                    methodBarang.value = 'PUT';
                    formBarang.action = `/dashboard/sarpras/persediaan/${data.id}`;
                    if (wrapperStokAwal) wrapperStokAwal.style.display = 'none';
                    document.getElementById('barangKode').value = data.kode || '';
                    document.getElementById('barangNama').value = data.nama || '';
                    document.getElementById('barangKategori').value = data.kategori || 'ATK Kantor';
                    document.getElementById('barangSpesifikasi').value = data.spesifikasi || '';
                    document.getElementById('barangStokMin').value = data.minimum || 5;
                    document.getElementById('barangSatuan').value = data.satuan || 'pcs';
                    document.getElementById('barangLokasi').value = data.lokasi || '';
                    document.getElementById('barangSumber').value = data.sumber || 'BOS Reguler';
                    document.getElementById('barangHarga').value = data.harga || '';
                    document.getElementById('barangKeterangan').value = data.keterangan || '';
                } else {
                    modalBarangTitle.textContent = 'Tambah Barang Habis Pakai (ATK)';
                    methodBarang.value = 'POST';
                    formBarang.action = '/dashboard/sarpras/persediaan';
                    if (wrapperStokAwal) wrapperStokAwal.style.display = 'block';
                    formBarang.reset();
                }
            }

            function closeModalBarang() {
                if (modalBarang) modalBarang.style.display = 'none';
            }

            if (btnOpenBarang) btnOpenBarang.addEventListener('click', () => openModalBarang(false));
            if (btnCloseBarang) btnCloseBarang.addEventListener('click', closeModalBarang);
            if (btnCancelBarang) btnCancelBarang.addEventListener('click', closeModalBarang);
            if (modalBarang) {
                modalBarang.addEventListener('click', e => { if (e.target === modalBarang) closeModalBarang(); });
            }

            document.addEventListener('click', function (e) {
                const btnEdit = e.target.closest('.btn-edit-barang');
                if (btnEdit) {
                    openModalBarang(true, {
                        id: btnEdit.dataset.id,
                        kode: btnEdit.dataset.kode,
                        nama: btnEdit.dataset.nama,
                        kategori: btnEdit.dataset.kategori,
                        spesifikasi: btnEdit.dataset.spesifikasi,
                        satuan: btnEdit.dataset.satuan,
                        minimum: btnEdit.dataset.minimum,
                        lokasi: btnEdit.dataset.lokasi,
                        sumber: btnEdit.dataset.sumber,
                        harga: btnEdit.dataset.harga,
                        keterangan: btnEdit.dataset.keterangan,
                    });
                }

                const btnDel = e.target.closest('.btn-delete-barang');
                if (btnDel) {
                    const id = btnDel.dataset.id;
                    const nama = btnDel.dataset.nama || 'barang ini';
                    const token = document.querySelector('meta[name="csrf-token"]')?.content || '';

                    if (window.Swal) {
                        Swal.fire({
                            title: 'Hapus Barang Persediaan?',
                            text: `Apakah Anda yakin ingin menghapus ${nama}? Riwayat mutasi terkait juga akan terhapus.`,
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#ef4444',
                            cancelButtonColor: '#64748b',
                            confirmButtonText: 'Ya, Hapus',
                            cancelButtonText: 'Batal'
                        }).then(res => {
                            if (res.isConfirmed) {
                                fetch(`/dashboard/sarpras/persediaan/${id}`, {
                                    method: 'DELETE',
                                    headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' }
                                })
                                .then(r => r.json())
                                .then(data => {
                                    if (data.status === 'success') {
                                        Swal.fire('Berhasil!', data.message, 'success').then(() => location.reload());
                                    } else {
                                        Swal.fire('Gagal', data.message || 'Gagal menghapus.', 'error');
                                    }
                                });
                            }
                        });
                    }
                }
            });

            // Modal Mutasi
            const modalMutasi = document.getElementById('modalMutasiPersediaan');
            const btnCloseMutasi = document.getElementById('btnCloseModalMutasi');
            const btnCancelMutasi = document.getElementById('btnCancelModalMutasi');
            const formMutasi = document.getElementById('formMutasiPersediaan');
            const selectBarangMutasi = document.getElementById('mutasiSelectBarang');
            const hintStokSisa = document.getElementById('hintStokSisaMutasi');
            const selectJenisMutasi = document.getElementById('mutasiJenis');
            const labelPenerima = document.getElementById('labelPenerimaMutasi');
            const btnOpenMutasiGlobal = document.getElementById('btnOpenModalMutasiGlobal');

            function openModalMutasi(barangId = null) {
                if (!modalMutasi) return;
                modalMutasi.style.display = 'flex';
                formMutasi.reset();
                document.getElementById('mutasiTanggal').value = new Date().toISOString().split('T')[0];
                if (barangId) {
                    selectBarangMutasi.value = barangId;
                    selectBarangMutasi.disabled = true;
                    formMutasi.action = `/dashboard/sarpras/persediaan/${barangId}/mutasi`;
                } else {
                    selectBarangMutasi.disabled = false;
                    formMutasi.action = '';
                }
                updateHintMutasi();
            }

            function closeModalMutasi() {
                if (modalMutasi) modalMutasi.style.display = 'none';
            }

            function updateHintMutasi() {
                const opt = selectBarangMutasi.selectedOptions[0];
                if (opt && opt.value) {
                    formMutasi.action = `/dashboard/sarpras/persediaan/${opt.value}/mutasi`;
                    hintStokSisa.innerHTML = `<i class="fas fa-info-circle text-primary me-1"></i> Sisa stok saat ini: <strong>${opt.dataset.stok} ${opt.dataset.satuan}</strong>`;
                } else {
                    hintStokSisa.textContent = '';
                }
            }

            if (selectBarangMutasi) selectBarangMutasi.addEventListener('change', updateHintMutasi);

            if (selectJenisMutasi) {
                selectJenisMutasi.addEventListener('change', function () {
                    if (this.value === 'masuk') {
                        labelPenerima.innerHTML = 'Sumber / Pengirim (Toko/Vendor) <span style="color: var(--danger, #ef4444);">*</span>';
                        document.getElementById('mutasiPenerima').placeholder = 'Toko ATK / Pengadaan BOS';
                    } else {
                        labelPenerima.innerHTML = 'Penerima / Pengambil <span style="color: var(--danger, #ef4444);">*</span>';
                        document.getElementById('mutasiPenerima').placeholder = 'Nama Guru / Staf / Unit Kerja';
                    }
                });
            }

            if (btnOpenMutasiGlobal) btnOpenMutasiGlobal.addEventListener('click', () => openModalMutasi(null));
            if (btnCloseMutasi) btnCloseMutasi.addEventListener('click', closeModalMutasi);
            if (btnCancelMutasi) btnCancelMutasi.addEventListener('click', closeModalMutasi);
            if (modalMutasi) {
                modalMutasi.addEventListener('click', e => { if (e.target === modalMutasi) closeModalMutasi(); });
            }

            document.addEventListener('click', function (e) {
                const btnMutasi = e.target.closest('.btn-mutasi-item');
                if (btnMutasi) {
                    openModalMutasi(btnMutasi.dataset.id);
                }
            });
        });
    </script>
@endpush
