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
                @if ($canCreate)
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
                    <div class="dash-stat-value">{{ $statTotal }}</div>
                    <div class="dash-stat-label">Total Unit Aset</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(16, 185, 129, 0.15); color: var(--success, #10b981);">
                    <i class="fas fa-check-double"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ $statTersedia }}</div>
                    <div class="dash-stat-label">Unit Tersedia</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(59, 130, 246, 0.15); color: var(--primary, #3b82f6);">
                    <i class="fas fa-hand-holding-box"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ $statDipinjam }}</div>
                    <div class="dash-stat-label">Sedang Dipinjam</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(239, 68, 68, 0.15); color: var(--danger, #ef4444);">
                    <i class="fas fa-wrench"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ $statPerbaikan }}</div>
                    <div class="dash-stat-label">Perbaikan / Rusak</div>
                </div>
            </div>
        </div>

        {{-- Unified Datatable Aset & Inventaris (Toolbar + Table + Pagination dalam 1 Card Terpadu) --}}
        <div class="card datatable-card-wrapper" id="tableDataContainer">
            <!-- 1. Toolbar Header (Entries, Filters & Live Search) -->
            <div class="datatable-toolbar">
                <form method="GET" action="{{ route('dashboard.sarpras.aset.index') }}" id="filterForm"
                    style="margin: 0;">
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

                            @if ($search || $kategoriFilter || $kondisiFilter || $ruangFilter)
                                <a href="{{ route('dashboard.sarpras.aset.index') }}"
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
                                                data-sumber="{{ $item->sumber_dana }}"
                                                data-harga="{{ $item->harga_perolehan }}"
                                                data-kondisi="{{ $item->kondisi }}" data-ruang="{{ $item->ruang_id }}"
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

        {{-- Modal Tambah / Edit Aset --}}
        <div id="modalAset" class="modal-backdrop"
            style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
            <div class="card"
                style="width: 100%; max-width: 620px; max-height: 90vh; overflow-y: auto; padding: 24px; border-radius: var(--radius-lg, 12px);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h3 id="modalAsetTitle"
                        style="margin: 0; font-size: var(--text-xl, 1.25rem); font-weight: 700; color: var(--text-color);">
                        Tambah Aset Sarpras
                    </h3>
                    <button type="button" id="btnCloseModalAset"
                        style="background: none; border: none; font-size: var(--text-xl, 1.25rem); color: var(--text-muted); cursor: pointer;">&times;</button>
                </div>

                <form id="formAset" method="POST" action="{{ route('dashboard.sarpras.aset.store') }}"
                    enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="_method" id="asetMethod" value="POST">
                    <input type="hidden" name="id" id="asetId">

                    <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 12px; margin-bottom: 14px;">
                        <div>
                            <label
                                style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Kode
                                Aset <span style="color: var(--danger, #ef4444);">*</span></label>
                            <input type="text" name="kode_aset" id="asetKode" class="form-control"
                                placeholder="AST-2026-001" required>
                        </div>
                        <div>
                            <label
                                style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Nama
                                Barang / Aset <span style="color: var(--danger, #ef4444);">*</span></label>
                            <input type="text" name="nama_barang" id="asetNama" class="form-control"
                                placeholder="Proyektor Epson EB-X500" required>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                        <div>
                            <label
                                style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Kategori
                                <span style="color: var(--danger, #ef4444);">*</span></label>
                            <select name="kategori" id="asetKategori" class="form-control" required>
                                @foreach ($kategoriList as $kat)
                                    <option value="{{ $kat }}">{{ $kat }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label
                                style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Merk
                                / Model / Tipe</label>
                            <input type="text" name="merk_tipe" id="asetMerk" class="form-control"
                                placeholder="Epson / Lenovo / Olympic">
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                        <div>
                            <label
                                style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Jumlah
                                <span style="color: var(--danger, #ef4444);">*</span></label>
                            <input type="number" name="jumlah" id="asetJumlah" class="form-control" value="1"
                                min="1" required>
                        </div>
                        <div>
                            <label
                                style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Satuan
                                <span style="color: var(--danger, #ef4444);">*</span></label>
                            <input type="text" name="satuan" id="asetSatuan" class="form-control" value="unit"
                                placeholder="unit/buah/set" required>
                        </div>
                        <div>
                            <label
                                style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Tahun
                                Beli</label>
                            <input type="number" name="tahun_perolehan" id="asetTahun" class="form-control"
                                value="{{ date('Y') }}" min="1990">
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                        <div>
                            <label
                                style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Sumber
                                Dana <span style="color: var(--danger, #ef4444);">*</span></label>
                            <select name="sumber_dana" id="asetSumber" class="form-control" required>
                                <option value="BOS Reguler">BOS Reguler</option>
                                <option value="BOS Kinerja">BOS Kinerja</option>
                                <option value="DAK Fisik">DAK Fisik</option>
                                <option value="Komite / Yayasan">Komite / Yayasan</option>
                                <option value="Hibah / Bantuan">Hibah / Bantuan</option>
                            </select>
                        </div>
                        <div>
                            <label
                                style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Ruang
                                / Penempatan</label>
                            <select name="ruang_id" id="asetRuang" class="form-control">
                                <option value="">-- Pilih Ruang --</option>
                                @foreach ($ruangList as $r)
                                    <option value="{{ $r->id }}">{{ $r->nama_ruang }}
                                        ({{ $r->gedung ?: 'Gedung' }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                        <div>
                            <label
                                style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Kondisi
                                <span style="color: var(--danger, #ef4444);">*</span></label>
                            <select name="kondisi" id="asetKondisi" class="form-control" required>
                                <option value="baik">Baik</option>
                                <option value="rusak_ringan">Rusak Ringan</option>
                                <option value="rusak_berat">Rusak Berat</option>
                            </select>
                        </div>
                        <div>
                            <label
                                style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Status
                                Ketersediaan <span style="color: var(--danger, #ef4444);">*</span></label>
                            <select name="status_ketersediaan" id="asetStatus" class="form-control" required>
                                <option value="tersedia">Tersedia</option>
                                <option value="dipinjam">Dipinjam</option>
                                <option value="perbaikan">Dalam Perbaikan</option>
                                <option value="dihapuskan">Dihapuskan</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: 20px;">
                        <label
                            style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Foto
                            Aset / Barang (Opsional)</label>
                        <input type="file" name="foto" id="asetFoto" class="form-control" accept="image/*">
                    </div>

                    <div style="display: flex; justify-content: flex-end; gap: 8px;">
                        <button type="button" class="btn btn-secondary" id="btnCancelModalAset">Batal</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Simpan
                            Aset</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Modal Import Aset via Excel (Point 7) --}}
        <div id="modalImportAset" class="modal-backdrop"
            style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
            <div class="card" style="width: 100%; max-width: 520px; padding: 24px; border-radius: var(--radius-lg, 12px);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px;">
                    <h3 style="margin: 0; font-size: var(--text-xl, 1.25rem); font-weight: 700; color: var(--text-color);">
                        <i class="fas fa-file-excel text-success me-2"></i> Import Aset via Excel
                    </h3>
                    <button type="button" id="btnCloseModalImportAset"
                        style="background: none; border: none; font-size: var(--text-xl, 1.25rem); color: var(--text-muted); cursor: pointer;">&times;</button>
                </div>

                <div style="margin-bottom: 18px; padding: 12px 14px; background: rgba(16,185,129,0.08); border: 1px solid rgba(16,185,129,0.25); border-radius: var(--radius-md, 8px);">
                    <div style="font-size: var(--text-sm, 0.8125rem); color: var(--text-color); font-weight: 600; margin-bottom: 4px;">
                        Unduh Format Template:
                    </div>
                    <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-bottom: 10px;">
                        Gunakan format berkas resmi agar nama kolom dikenali sistem.
                    </div>
                    <a href="{{ route('dashboard.sarpras.aset.template') }}" class="btn btn-outline"
                        style="font-size: var(--text-xs, 0.75rem); padding: 6px 12px; display: inline-flex; align-items: center; gap: 6px; text-decoration: none;">
                        <i class="fas fa-download text-success"></i> Unduh Format Excel (.xlsx)
                    </a>
                </div>

                <form id="formImportAset" method="POST" action="{{ route('dashboard.sarpras.aset.import') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="form-group" style="margin-bottom: 18px;">
                        <label style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">
                            Pilih Berkas Excel / CSV <span style="color: var(--danger, #ef4444);">*</span>
                        </label>
                        <input type="file" name="file" id="fileImportAset" class="form-control" accept=".xlsx,.xls,.csv" required>
                        <small style="color: var(--text-muted); font-size: var(--text-xs, 0.75rem); display: block; margin-top: 4px;">
                            Mendukung .xlsx, .xls, atau .csv (Maks. 10 MB).
                        </small>
                    </div>

                    <div style="display: flex; justify-content: flex-end; gap: 8px;">
                        <button type="button" class="btn btn-secondary" id="btnCancelModalImportAset">Batal</button>
                        <button type="submit" class="btn btn-primary" id="btnSubmitImportAset">
                            <i class="fas fa-upload me-1"></i> Mulai Unggah &amp; Import
                        </button>
                    </div>
                </form>
            </div>
        </div>

        @push('scripts')
            <script
                src="{{ asset('js/sarpras-aset.js') }}?v={{ file_exists(public_path('js/sarpras-aset.js')) ? filemtime(public_path('js/sarpras-aset.js')) : time() }}">
            </script>
        @endpush
    @endsection
