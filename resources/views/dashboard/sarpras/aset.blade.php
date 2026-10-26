@extends('layouts.dashboard')

@section('title', 'Inventaris Aset & Sarana — Sarpras SAE')
@section('dash_title', 'Inventaris Aset')

@section('content')
    <div class="dash-content-inner">
        {{-- 1. Header Banner & Action Button --}}
        <div class="dash-banner"
            style="display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; margin-bottom: 20px;">
            <div style="display: flex; align-items: center; gap: 14px;">
                <div
                    style="width: 48px; height: 48px; border-radius: var(--radius-lg, 12px); background: rgba(59,130,246,0.12); color: var(--primary, #3b82f6); display: flex; align-items: center; justify-content: center; font-size: var(--text-2xl, 1.5rem); flex-shrink: 0;">
                    <i class="fas fa-boxes-stacked"></i>
                </div>
                <div>
                    <h2 style="font-size: var(--text-xl, 1.25rem); font-weight: 800; color: var(--text-color); margin: 0;">
                        Inventaris Aset &amp; Sarana
                    </h2>
                    <div style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); margin-top: 2px;">
                        Pencatatan barang milik sekolah, aset elektronik, mebel, mesin, dan status ketersediaannya.
                    </div>
                </div>
            </div>
            <div class="dash-banner-actions" style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                @if ($canCreate && $tab === 'sarpras')
                    <button type="button" class="btn btn-outline" id="btnOpenImportAset"
                        style="height: 38px; padding: 0 14px; display: inline-flex; align-items: center; gap: 6px; border-radius: var(--radius-md, 8px); font-size: var(--text-sm, 0.8125rem);"
                        title="Import Aset via Excel">
                        <i class="fas fa-file-excel text-success"></i> Import Excel
                    </button>
                    <button type="button" class="btn btn-primary" id="btnOpenModalAset"
                        style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px); font-size: var(--text-md, 1rem);"
                        title="Tambah Aset Baru">
                        <i class="fas fa-plus"></i>
                    </button>
                @elseif ($tab === 'laboran')
                    <a href="{{ route('dashboard.laboran.inventaris.index') }}" class="btn btn-outline"
                        style="height: 38px; padding: 0 14px; display: inline-flex; align-items: center; gap: 6px; border-radius: var(--radius-md, 8px); font-size: var(--text-sm, 0.8125rem); text-decoration: none;"
                        title="Buka Modul Inventaris Laboran Penuh">
                        <i class="fas fa-flask text-primary"></i> Modul Laboran Penuh
                    </a>
                @endif
            </div>
        </div>

        {{-- 2. Stat Grid (4 Kartu Ringkasan) --}}
        @if ($tab === 'laboran')
            <div class="dash-stat-grid" style="margin-bottom: 24px; gap: 14px;">
                <div class="dash-stat-card">
                    <div class="dash-stat-icon" style="background: rgba(6, 182, 212, 0.15); color: var(--accent, #06b6d4);">
                        <i class="fas fa-vials"></i>
                    </div>
                    <div class="dash-stat-info">
                        <div class="dash-stat-value">{{ number_format($statLabTotal ?? 0, 0, ',', '.') }}</div>
                        <div class="dash-stat-label">Total Item Jurusan</div>
                    </div>
                </div>
                <div class="dash-stat-card">
                    <div class="dash-stat-icon" style="background: rgba(59, 130, 246, 0.15); color: var(--primary, #3b82f6);">
                        <i class="fas fa-screwdriver-wrench"></i>
                    </div>
                    <div class="dash-stat-info">
                        <div class="dash-stat-value">{{ number_format($statLabAlat ?? 0, 0, ',', '.') }}</div>
                        <div class="dash-stat-label">Alat Praktik</div>
                    </div>
                </div>
                <div class="dash-stat-card">
                    <div class="dash-stat-icon" style="background: rgba(16, 185, 129, 0.15); color: var(--success, #10b981);">
                        <i class="fas fa-box-open"></i>
                    </div>
                    <div class="dash-stat-info">
                        <div class="dash-stat-value">{{ number_format($statLabBahan ?? 0, 0, ',', '.') }}</div>
                        <div class="dash-stat-label">Bahan Habis Pakai</div>
                    </div>
                </div>
                <div class="dash-stat-card">
                    <div class="dash-stat-icon" style="background: rgba(239, 68, 68, 0.15); color: var(--danger, #ef4444);">
                        <i class="fas fa-triangle-exclamation"></i>
                    </div>
                    <div class="dash-stat-info">
                        <div class="dash-stat-value">{{ number_format($statLabKritis ?? 0, 0, ',', '.') }}</div>
                        <div class="dash-stat-label">Stok Kritis / Rusak</div>
                    </div>
                </div>
            </div>
        @else
            <div class="dash-stat-grid" style="margin-bottom: 24px; gap: 14px;">
                <div class="dash-stat-card">
                    <div class="dash-stat-icon" style="background: rgba(99, 102, 241, 0.15); color: var(--primary);">
                        <i class="fas fa-cubes"></i>
                    </div>
                    <div class="dash-stat-info">
                        <div class="dash-stat-value">{{ number_format($statTotal ?? 0, 0, ',', '.') }}</div>
                        <div class="dash-stat-label">Total Unit Aset</div>
                    </div>
                </div>
                <div class="dash-stat-card">
                    <div class="dash-stat-icon" style="background: rgba(16, 185, 129, 0.15); color: var(--success, #10b981);">
                        <i class="fas fa-check-double"></i>
                    </div>
                    <div class="dash-stat-info">
                        <div class="dash-stat-value">{{ number_format($statTersedia ?? 0, 0, ',', '.') }}</div>
                        <div class="dash-stat-label">Unit Tersedia</div>
                    </div>
                </div>
                <div class="dash-stat-card">
                    <div class="dash-stat-icon" style="background: rgba(59, 130, 246, 0.15); color: var(--primary, #3b82f6);">
                        <i class="fas fa-hand-holding-box"></i>
                    </div>
                    <div class="dash-stat-info">
                        <div class="dash-stat-value">{{ number_format($statDipinjam ?? 0, 0, ',', '.') }}</div>
                        <div class="dash-stat-label">Sedang Dipinjam</div>
                    </div>
                </div>
                <div class="dash-stat-card">
                    <div class="dash-stat-icon" style="background: rgba(239, 68, 68, 0.15); color: var(--danger, #ef4444);">
                        <i class="fas fa-wrench"></i>
                    </div>
                    <div class="dash-stat-info">
                        <div class="dash-stat-value">{{ number_format($statPerbaikan ?? 0, 0, ',', '.') }}</div>
                        <div class="dash-stat-label">Perbaikan / Rusak</div>
                    </div>
                </div>
            </div>
        @endif

        {{-- 3. Navigasi Tab Baku SAE (Posisi Di Bawah Card Statistik) --}}
        <!-- Desktop Segmented Tabs -->
        <div class="dash-tabs-nav dash-desktop-tabs" style="margin-bottom: 20px;">
            <a href="{{ route('dashboard.sarpras.aset.index', ['tab' => 'sarpras']) }}"
                class="btn {{ $tab === 'sarpras' ? 'btn-primary' : 'btn-outline' }}" style="text-decoration: none;">
                <i class="fas fa-boxes-stacked me-1"></i> Aset Sekolah &amp; Sarpras ({{ number_format($statTotal ?? 0) }})
            </a>
            <a href="{{ route('dashboard.sarpras.aset.index', ['tab' => 'laboran']) }}"
                class="btn {{ $tab === 'laboran' ? 'btn-primary' : 'btn-outline' }}" style="text-decoration: none;">
                <i class="fas fa-flask-vial me-1"></i> Inventaris Jurusan (Laboran) ({{ number_format($statLabTotal ?? 0) }})
            </a>
        </div>

        <!-- Mobile Custom Dropdown Selector Baku SAE -->
        <div class="dash-mobile-tab-select-wrap" style="margin-bottom: 20px;">
            <div class="dash-custom-dropdown">
                <button type="button" class="custom-dropdown-trigger" id="asetMobileDropdownTrigger" aria-haspopup="listbox" aria-expanded="false">
                    <div class="custom-dropdown-trigger-label">
                        <i class="fas {{ $tab === 'sarpras' ? 'fa-boxes-stacked' : 'fa-flask-vial' }} text-primary me-2"></i>
                        <span>{{ $tab === 'sarpras' ? 'Aset Sekolah & Sarpras' : 'Inventaris Jurusan (Laboran)' }}</span>
                        <span class="badge badge-primary badge-sm ms-2">{{ $tab === 'sarpras' ? number_format($statTotal ?? 0) : number_format($statLabTotal ?? 0) }}</span>
                    </div>
                    <i class="fas fa-chevron-down custom-dropdown-arrow"></i>
                </button>
                <div class="custom-dropdown-menu" role="listbox">
                    <a href="{{ route('dashboard.sarpras.aset.index', ['tab' => 'sarpras']) }}"
                        class="custom-dropdown-item {{ $tab === 'sarpras' ? 'active' : '' }}">
                        <div class="dropdown-item-left">
                            <i class="fas fa-boxes-stacked text-primary me-2"></i>
                            <span>Aset Sekolah &amp; Sarpras</span>
                        </div>
                        <span class="badge {{ $tab === 'sarpras' ? 'badge-primary' : 'badge-outline' }} badge-sm">{{ number_format($statTotal ?? 0) }}</span>
                    </a>
                    <a href="{{ route('dashboard.sarpras.aset.index', ['tab' => 'laboran']) }}"
                        class="custom-dropdown-item {{ $tab === 'laboran' ? 'active' : '' }}">
                        <div class="dropdown-item-left">
                            <i class="fas fa-flask-vial text-primary me-2"></i>
                            <span>Inventaris Jurusan (Laboran)</span>
                        </div>
                        <span class="badge {{ $tab === 'laboran' ? 'badge-primary' : 'badge-outline' }} badge-sm">{{ number_format($statLabTotal ?? 0) }}</span>
                    </a>
                </div>
            </div>
        </div>

        @if ($tab === 'laboran')
            @include('dashboard.sarpras.partials-aset-laboran')
        @else
            {{-- Unified Datatable Aset & Inventaris (Toolbar + Table + Pagination dalam 1 Card Terpadu) --}}
            <div class="card datatable-card-wrapper" id="tableDataContainer">
                <!-- 1. Toolbar Header (Entries, Filters & Live Search) -->
                <div class="datatable-toolbar">
                    <form method="GET" action="{{ route('dashboard.sarpras.aset.index') }}" id="filterForm"
                        style="margin: 0;">
                        <input type="hidden" name="tab" value="sarpras">
                        <div
                            style="display: flex; flex-wrap: wrap; gap: 12px; justify-content: space-between; align-items: center;">
                            <div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
                                <div class="toolbar-entries">
                                    <label for="perPageSelect" style="margin: 0;">Tampilkan</label>
                                    <select id="perPageSelect" name="perPage" class="per-page-select" style="height: 38px;">
                                        @foreach ([10, 15, 25, 50, 100] as $n)
                                            <option value="{{ $n }}"
                                                {{ request('perPage', 15) == $n ? 'selected' : '' }}>
                                            {{ $n }}</option>
                                    @endforeach
                                </select>
                                <span>entri</span>
                            </div>

                            <select name="kategori" id="filterKategori" class="toolbar-filter-select" style="height: 38px;">
                                <option value="">Semua Kategori</option>
                                @foreach ($kategoriList as $kat)
                                    <option value="{{ $kat }}" {{ $kategoriFilter === $kat ? 'selected' : '' }}>
                                        {{ $kat }}</option>
                                @endforeach
                            </select>

                            <select name="kondisi" id="filterKondisi" class="toolbar-filter-select" style="height: 38px;">
                                <option value="">Semua Kondisi</option>
                                <option value="baik" {{ $kondisiFilter === 'baik' ? 'selected' : '' }}>Baik</option>
                                <option value="rusak_ringan" {{ $kondisiFilter === 'rusak_ringan' ? 'selected' : '' }}>
                                    Rusak Ringan</option>
                                <option value="rusak_berat" {{ $kondisiFilter === 'rusak_berat' ? 'selected' : '' }}>Rusak
                                    Berat</option>
                            </select>

                            <select name="ruang_id" id="filterRuang" class="toolbar-filter-select" style="height: 38px;">
                                <option value="">Semua Ruang</option>
                                @foreach ($ruangList as $r)
                                    <option value="{{ $r->id }}" {{ $ruangFilter == $r->id ? 'selected' : '' }}>
                                        {{ $r->nama_ruang }}</option>
                                @endforeach
                            </select>

                            <select name="jurusan_id" id="filterJurusan" class="toolbar-filter-select" style="height: 38px;">
                                <option value="">Semua Alokasi Unit</option>
                                <option value="umum" {{ $jurusanFilter === 'umum' ? 'selected' : '' }}>Umum Sekolah</option>
                                @foreach ($jurusanList as $jur)
                                    <option value="{{ $jur->jurusan_id }}" {{ $jurusanFilter == $jur->jurusan_id ? 'selected' : '' }}>
                                        {{ $jur->singkatan ? $jur->singkatan . ' — ' : '' }}{{ $jur->nama_jurusan }}
                                    </option>
                                @endforeach
                            </select>

                            @if ($search || $kategoriFilter || $kondisiFilter || $ruangFilter || $jurusanFilter)
                                <a href="{{ route('dashboard.sarpras.aset.index', ['tab' => 'sarpras']) }}"
                                    class="btn btn-outline btn-reset-filter"
                                    style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px);"
                                    title="Reset filter" data-live-reset><i class="fas fa-undo"></i></a>
                            @endif
                        </div>

                        <div class="live-search-wrap" style="height: 38px;">
                            <i class="fas fa-search search-icon"></i>
                            <input type="text" name="q" id="liveSearch" value="{{ $search }}"
                                placeholder="Cari kode aset, nama barang, merk..." autocomplete="off">
                            <button type="button" id="clearSearch" class="clear-search {{ $search ? 'visible' : '' }}"
                                title="Hapus pencarian">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- 2. Konten Datatable Responsive Stack -->
            <div class="table-responsive-stack" style="overflow-x: auto; width: 100%;">
                <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                    <thead>
                        <tr style="border-bottom: 1px solid var(--border-color); background: rgba(255, 255, 255, 0.02);">
                            <th
                                style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                No</th>
                            <th
                                style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Aset / Barang</th>
                            <th
                                style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Kategori &amp; Sumber</th>
                            <th
                                style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Ruang / Lokasi</th>
                            <th
                                style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center;">
                                Jumlah</th>
                            <th
                                style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center;">
                                Kondisi &amp; Status</th>
                            <th
                                style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: right;">
                                Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($list as $idx => $item)
                            <tr style="border-bottom: 1px solid var(--border-color); transition: background 0.2s ease;">
                                <td data-label="No"
                                    style="padding: 14px 18px; font-size: var(--text-base, 0.875rem); color: var(--text-muted);">
                                    {{ $list->firstItem() + $idx }}
                                </td>
                                <td data-label="Aset" style="padding: 14px 18px;">
                                    <div style="display: flex; align-items: center; gap: 12px;">
                                        @if ($item->foto)
                                            <div
                                                style="width: 42px; height: 42px; border-radius: var(--radius-md, 8px); overflow: hidden; border: 1px solid var(--border-color); flex-shrink: 0;">
                                                <img src="{{ asset('storage/' . $item->foto) }}"
                                                    alt="{{ $item->nama_barang }}"
                                                    style="width: 100%; height: 100%; object-fit: cover;">
                                            </div>
                                        @else
                                            <div
                                                style="width: 42px; height: 42px; border-radius: var(--radius-md, 8px); background: var(--bg-hover); border: 1px solid var(--border-color); display: flex; align-items: center; justify-content: center; color: var(--primary); flex-shrink: 0;">
                                                <i class="fas fa-box"></i>
                                            </div>
                                        @endif
                                        <div>
                                            <div style="font-weight: 700; color: var(--text-color); font-size: var(--text-base, 0.875rem);">
                                                {{ $item->nama_barang }}</div>
                                            <div
                                                style="font-size: var(--text-sm, 0.8125rem); color: var(--primary); font-family: monospace;">
                                                {{ $item->kode_aset }}</div>
                                            @if ($item->merk_tipe)
                                                <div style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted);">
                                                    {{ $item->merk_tipe }}</div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td data-label="Kategori" style="padding: 14px 18px; font-size: var(--text-base, 0.875rem);">
                                    <span class="badge badge-info"
                                        style="font-size: var(--text-xs, 0.75rem);">{{ $item->kategori }}</span>
                                    <div style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); margin-top: 3px;">Sumber:
                                        {{ $item->sumber_dana }}</div>
                                </td>
                                <td data-label="Lokasi" style="padding: 14px 18px; font-size: var(--text-base, 0.875rem);">
                                    <div style="font-weight: 600; color: var(--text-color);">
                                        {{ $item->nama_ruang ?: 'Belum Ditentukan' }}</div>
                                    @if ($item->gedung)
                                        <div style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted);">{{ $item->gedung }}
                                        </div>
                                    @endif
                                    <div style="margin-top: 4px;">
                                        @if ($item->jurusan_singkatan || $item->nama_jurusan)
                                            <span class="badge" style="font-size: var(--text-2xs, 0.6875rem); padding: 2px 6px; background: rgba(99,102,241,0.15); color: var(--primary-light, #6366f1); border: 1px solid rgba(99,102,241,0.25);" title="{{ $item->nama_jurusan }}">
                                                <i class="fas fa-graduation-cap me-1"></i>{{ $item->jurusan_singkatan ?: $item->nama_jurusan }}
                                            </span>
                                        @else
                                            <span class="badge" style="font-size: var(--text-2xs, 0.6875rem); padding: 2px 6px; background: rgba(148,163,184,0.15); color: var(--text-muted);">
                                                Umum
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td data-label="Jumlah"
                                    style="padding: 14px 18px; text-align: center; font-size: var(--text-base, 0.875rem); font-weight: 700;">
                                    {{ $item->jumlah }} {{ $item->satuan }}
                                </td>
                                <td data-label="Status" style="padding: 14px 18px; text-align: center;">
                                    <div style="display: flex; flex-direction: column; gap: 4px; align-items: center;">
                                        @if ($item->kondisi === 'baik')
                                            <span class="badge badge-success" style="font-size: var(--text-xs, 0.75rem);">Kondisi
                                                Baik</span>
                                        @elseif ($item->kondisi === 'rusak_ringan')
                                            <span class="badge badge-warning" style="font-size: var(--text-xs, 0.75rem);">Rusak
                                                Ringan</span>
                                        @else
                                            <span class="badge badge-danger" style="font-size: var(--text-xs, 0.75rem);">Rusak
                                                Berat</span>
                                        @endif

                                        @if (($item->jumlah_baik ?? 0) > 0 || ($item->jumlah_rusak_ringan ?? 0) > 0 || ($item->jumlah_rusak_berat ?? 0) > 0)
                                            <div style="display: inline-flex; gap: 4px; font-size: var(--text-2xs, 0.6875rem); flex-wrap: wrap; justify-content: center; margin: 2px 0;">
                                                @if (($item->jumlah_baik ?? 0) > 0)
                                                    <span class="badge badge-success" style="font-size: var(--text-2xs, 0.6875rem); padding: 1px 5px;">{{ $item->jumlah_baik }} Baik</span>
                                                @endif
                                                @if (($item->jumlah_rusak_ringan ?? 0) > 0)
                                                    <span class="badge badge-warning" style="font-size: var(--text-2xs, 0.6875rem); padding: 1px 5px;">{{ $item->jumlah_rusak_ringan }} RR</span>
                                                @endif
                                                @if (($item->jumlah_rusak_berat ?? 0) > 0)
                                                    <span class="badge badge-danger" style="font-size: var(--text-2xs, 0.6875rem); padding: 1px 5px;">{{ $item->jumlah_rusak_berat }} RB</span>
                                                @endif
                                            </div>
                                        @endif

                                        @if ($item->status_ketersediaan === 'tersedia')
                                            <span class="badge badge-outline"
                                                style="font-size: var(--text-xs, 0.75rem); border-color: var(--success, #10b981); color: var(--success, #10b981);">Tersedia</span>
                                        @elseif ($item->status_ketersediaan === 'dipinjam')
                                            <span class="badge badge-outline"
                                                style="font-size: var(--text-xs, 0.75rem); border-color: var(--primary, #3b82f6); color: var(--primary, #3b82f6);">Dipinjam</span>
                                        @elseif ($item->status_ketersediaan === 'perbaikan')
                                            <span class="badge badge-outline"
                                                style="font-size: var(--text-xs, 0.75rem); border-color: var(--warning, #f59e0b); color: var(--warning, #f59e0b);">Perbaikan</span>
                                        @else
                                            <span class="badge badge-outline"
                                                style="font-size: var(--text-xs, 0.75rem); border-color: var(--danger, #ef4444); color: var(--danger, #ef4444);">Dihapuskan</span>
                                        @endif
                                    </div>
                                </td>
                                <td data-label="Aksi" style="padding: 14px 18px; text-align: right;">
                                    <div class="table-actions" style="justify-content: flex-end; gap: 6px;">
                                        @if ($canUpdate)
                                            <button type="button" class="btn-icon btn-edit-aset"
                                                data-id="{{ $item->id }}" data-kode="{{ $item->kode_aset }}"
                                                data-nama="{{ $item->nama_barang }}"
                                                data-kategori="{{ $item->kategori }}" data-merk="{{ $item->merk_tipe }}"
                                                data-noseri="{{ $item->no_seri_pabrik }}"
                                                data-tahun="{{ $item->tahun_perolehan }}"
                                                data-tglperolehan="{{ $item->tanggal_perolehan }}"
                                                data-sumber="{{ $item->sumber_dana }}"
                                                data-harga="{{ $item->harga_perolehan }}"
                                                data-kondisi="{{ $item->kondisi }}"
                                                data-baik="{{ $item->jumlah_baik }}"
                                                data-rr="{{ $item->jumlah_rusak_ringan }}"
                                                data-rb="{{ $item->jumlah_rusak_berat }}"
                                                data-ruang="{{ $item->ruang_id }}"
                                                data-jurusan="{{ $item->jurusan_id }}"
                                                data-jumlah="{{ $item->jumlah }}" data-satuan="{{ $item->satuan }}"
                                                data-status="{{ $item->status_ketersediaan }}" title="Edit Aset">
                                                <i class="fas fa-pen-to-square"></i>
                                            </button>
                                        @endif
                                        @if ($canDelete)
                                            <button type="button" class="btn-icon btn-delete-aset"
                                                data-id="{{ $item->id }}" data-nama="{{ $item->nama_barang }}"
                                                title="Hapus Aset" style="color: var(--danger, #ef4444);">
                                                <i class="fas fa-trash-can"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" style="padding: 30px; text-align: center; color: var(--text-muted);">
                                    <i class="fas fa-folder-open mb-2" style="font-size: var(--text-3xl, 1.875rem); opacity: 0.5;"></i>
                                    <div>Belum ada data barang aset yang cocok.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- 3. Pagination Footer Resmi SAE --}}
            @if ($list->hasPages())
                <div class="datatable-footer">
                    @include('partials.datatable-pagination', ['paginator' => $list])
                </div>
            @endif
        </div>
        @endif

        {{-- Modal Tambah / Edit Aset --}}
        @include('dashboard.sarpras.partials.aset-modal-form')

        {{-- Modal Import Aset via Excel --}}
        @include('dashboard.sarpras.partials.aset-modal-import')
    </div>

        @push('scripts')
            <script
                src="{{ asset('js/sarpras-aset.js') }}?v={{ file_exists(public_path('js/sarpras-aset.js')) ? filemtime(public_path('js/sarpras-aset.js')) : time() }}">
            </script>
        @endpush
    @endsection
