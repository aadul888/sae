@extends('layouts.dashboard')

@section('title', 'Katalog Koleksi Buku - Perpustakaan')

@section('content')
    <div class="dash-content-inner">
        <!-- 1. Header Banner & Actions -->
        <div class="dash-banner"
            style="display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; margin-bottom: 20px;">
            <div style="display: flex; align-items: center; gap: 14px;">
                <div
                    style="width: 48px; height: 48px; border-radius: var(--radius-lg, 12px); background: rgba(59,130,246,0.12); color: var(--primary, #3b82f6); display: flex; align-items: center; justify-content: center; font-size: var(--text-2xl, 1.5rem); flex-shrink: 0;">
                    <i class="fas fa-book"></i>
                </div>
                <div>
                    <h2 style="font-size: var(--text-xl, 1.25rem); font-weight: 800; color: var(--text-color); margin: 0;">
                        Katalog Koleksi Buku &amp; Bahan Pustaka
                    </h2>
                    <div style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); margin-top: 2px;">
                        Manajemen data inventaris buku perpustakaan, klasifikasi DDC, ISBN, dan ketersediaan eksemplar.
                    </div>
                </div>
            </div>

            <div class="dash-banner-actions" style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                @if ($canCreate)
                    <button type="button" class="btn btn-primary" id="btnTambahBuku" onclick="openModalBuku()"
                        style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px); font-size: var(--text-md, 1rem);"
                        title="Tambah Koleksi Buku">
                        <i class="fas fa-plus"></i>
                    </button>
                @endif
            </div>
        </div>

        <!-- 2. Tab Navigasi Sub-Modul Perpustakaan Baku SAE -->
        <div class="periode-nav-wrapper" style="margin-bottom: 20px;">
            <div class="periode-nav-desktop">
                <a href="{{ route('dashboard.perpustakaan.koleksi.index') }}"
                    class="periode-nav-tab {{ request()->routeIs('dashboard.perpustakaan.koleksi*') ? 'active' : '' }}">
                    <i class="fas fa-book"></i> Katalog Koleksi Buku
                </a>
                <a href="{{ route('dashboard.perpustakaan.sirkulasi') }}"
                    class="periode-nav-tab {{ request()->routeIs('dashboard.perpustakaan.sirkulasi*') ? 'active' : '' }}">
                    <i class="fas fa-exchange-alt"></i> Sirkulasi Peminjaman
                </a>
                <a href="{{ route('dashboard.perpustakaan.kunjungan') }}"
                    class="periode-nav-tab {{ request()->routeIs('dashboard.perpustakaan.kunjungan*') ? 'active' : '' }}">
                    <i class="fas fa-user-check"></i> Buku Kunjungan
                </a>
            </div>
        </div>

        <!-- 3. Stat Grid Baku SAE -->
        <div class="dash-stat-grid" style="margin-bottom: 20px;">
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(59,130,246,0.12); color: var(--primary, #3b82f6);">
                    <i class="fas fa-book-bookmark"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ number_format($stats['total_judul'] ?? 0) }}</div>
                    <div class="dash-stat-label">Total Judul Buku</div>
                </div>
            </div>

            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(99,102,241,0.12); color: var(--primary-light, #6366f1);">
                    <i class="fas fa-layer-group"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ number_format($stats['total_eksemplar'] ?? 0) }}</div>
                    <div class="dash-stat-label">Total Eksemplar</div>
                </div>
            </div>

            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(16,185,129,0.12); color: var(--success, #10b981);">
                    <i class="fas fa-circle-check"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ number_format($stats['tersedia'] ?? 0) }}</div>
                    <div class="dash-stat-label">Eksemplar Tersedia</div>
                </div>
            </div>

            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(245,158,11,0.12); color: var(--warning, #f59e0b);">
                    <i class="fas fa-book-reader"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ number_format($stats['dipinjam'] ?? 0) }}</div>
                    <div class="dash-stat-label">Sedang Dipinjam</div>
                </div>
            </div>
        </div>

        {{-- Unified Datatable Katalog Buku (Toolbar + Table + Pagination dalam 1 Card Terpadu) --}}
        <div class="card datatable-card-wrapper" id="tableDataContainer">
            <!-- 1. Toolbar Header (Entries, Filters & Live Search) -->
            <div class="datatable-toolbar">
                <form method="GET" action="{{ route('dashboard.perpustakaan.koleksi.index') }}" id="filterForm"
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
                                    <option value="{{ $kat }}" {{ $kategori === $kat ? 'selected' : '' }}>
                                        {{ $kat }}</option>
                                @endforeach
                            </select>

                            @if (!empty($q) || !empty($kategori))
                                <a href="{{ route('dashboard.perpustakaan.koleksi.index') }}"
                                    class="btn btn-outline btn-reset-filter"
                                    style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px);"
                                    title="Reset filter" data-live-reset><i class="fas fa-undo"></i></a>
                            @endif
                        </div>

                        <div class="live-search-wrap" style="height: 38px;">
                            <i class="fas fa-search search-icon"></i>
                            <input type="text" name="q" id="liveSearch" value="{{ $q ?? '' }}"
                                placeholder="Cari judul buku, kode, penulis, ISBN..." autocomplete="off">
                            <button type="button" id="clearSearch" class="clear-search {{ !empty($q) ? 'visible' : '' }}"
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
                        <tr
                            style="background: rgba(255, 255, 255, 0.02); border-bottom: 1px solid var(--border-color);">
                            <th
                                style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 50px; text-align: center;">
                                No</th>
                            <th class="sortable-th {{ ($sort ?? '') === 'kode_buku' ? 'sorted' : '' }}" data-sort="kode_buku"
                                style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Kode &amp; ISBN
                                <span class="sort-icon">{!! ($sort ?? '') === 'kode_buku' ? (($sortDir ?? '') === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                            </th>
                            <th class="sortable-th {{ ($sort ?? '') === 'judul' ? 'sorted' : '' }}" data-sort="judul"
                                style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Judul Buku
                                <span class="sort-icon">{!! ($sort ?? '') === 'judul' ? (($sortDir ?? '') === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                            </th>
                            <th class="sortable-th {{ ($sort ?? '') === 'penulis' ? 'sorted' : '' }}" data-sort="penulis"
                                style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Penulis &amp; Penerbit
                                <span class="sort-icon">{!! ($sort ?? '') === 'penulis' ? (($sortDir ?? '') === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                            </th>
                            <th class="sortable-th {{ ($sort ?? '') === 'kategori' ? 'sorted' : '' }}" data-sort="kategori"
                                style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Kategori &amp; DDC
                                <span class="sort-icon">{!! ($sort ?? '') === 'kategori' ? (($sortDir ?? '') === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                            </th>
                            <th class="sortable-th {{ ($sort ?? '') === 'eksemplar_tersedia' ? 'sorted' : '' }}" data-sort="eksemplar_tersedia"
                                style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center;">
                                Stok Tersedia
                                <span class="sort-icon">{!! ($sort ?? '') === 'eksemplar_tersedia' ? (($sortDir ?? '') === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                            </th>
                            <th class="sortable-th {{ ($sort ?? '') === 'lokasi_rak' ? 'sorted' : '' }}" data-sort="lokasi_rak"
                                style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Lokasi Rak
                                <span class="sort-icon">{!! ($sort ?? '') === 'lokasi_rak' ? (($sortDir ?? '') === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                            </th>
                            <th
                                style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 100px;">
                                Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($list as $item)
                            <tr style="border-bottom: 1px solid var(--border-color); transition: background 0.2s ease;">
                                <td style="padding: 14px 18px; text-align: center; font-weight: 700; color: var(--text-muted); font-size: var(--text-sm, 0.8125rem);"
                                    data-label="No">
                                    {{ ($list->currentPage() - 1) * $list->perPage() + $loop->iteration }}
                                </td>
                                <td style="padding: 14px 18px;" data-label="Kode & ISBN">
                                    <span
                                        style="font-weight: 700; font-family: monospace; color: var(--primary); font-size: var(--text-base, 0.875rem);">{{ $item->kode_buku }}</span>
                                    @if ($item->isbn)
                                        <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted);">ISBN:
                                            {{ $item->isbn }}
                                        </div>
                                    @endif
                                </td>
                                <td style="padding: 14px 18px;" data-label="Judul Buku">
                                    <div style="font-weight: 600; color: var(--text-heading); font-size: var(--text-base, 0.875rem);">{{ $item->judul }}</div>
                                    @if ($item->tahun_terbit)
                                        <div style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted);">Tahun:
                                            {{ $item->tahun_terbit }}</div>
                                    @endif
                                </td>
                                <td style="padding: 14px 18px; font-size: var(--text-base, 0.875rem);" data-label="Penulis & Penerbit">
                                    <div style="color: var(--text-heading);">{{ $item->penulis ?: '—' }}</div>
                                    <div style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted);">
                                        {{ $item->penerbit ?: '—' }}
                                    </div>
                                </td>
                                <td style="padding: 14px 18px;" data-label="Kategori & DDC">
                                    <span class="badge badge-outline" style="font-size: var(--text-xs, 0.75rem); padding: 2px 7px;">
                                        {{ $item->kategori }}
                                    </span>
                                    @if ($item->klasifikasi_ddc)
                                        <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-top: 2px;">DDC:
                                            {{ $item->klasifikasi_ddc }}</div>
                                    @endif
                                </td>
                                <td style="padding: 14px 18px; text-align: center;" data-label="Stok Tersedia">
                                    <span
                                        style="font-weight: 700; color: {{ $item->eksemplar_tersedia > 0 ? 'var(--success, #10b981)' : '#ef4444' }}; font-size: var(--text-md, 1rem);">
                                        {{ $item->eksemplar_tersedia }}
                                    </span>
                                    <span style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted);">/
                                        {{ $item->jumlah_eksemplar }}
                                        Eks</span>
                                </td>
                                <td style="padding: 14px 18px; font-size: var(--text-base, 0.875rem); color: var(--text-heading);" data-label="Lokasi Rak">
                                    {{ $item->lokasi_rak ?: '—' }}
                                </td>
                                <td style="padding: 14px 18px; text-align: center;" data-label="Aksi">
                                    <div class="table-actions" style="display: flex; gap: 6px; justify-content: center;">
                                        @if ($canUpdate)
                                            <button type="button" class="btn-icon btn-edit-buku" title="Edit Buku"
                                                onclick="editBuku({{ json_encode($item) }})"
                                                data-item="{{ json_encode($item) }}" style="color: var(--primary);">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        @endif
                                        @if ($canDelete)
                                            <form
                                                action="{{ route('dashboard.perpustakaan.koleksi.destroy', $item->id) }}"
                                                method="POST" data-confirm="delete" data-name="{{ $item->judul }}"
                                                style="display: inline;">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn-icon btn-delete-buku"
                                                    data-judul="{{ $item->judul }}" title="Hapus Buku" style="color: var(--danger, #ef4444);">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" style="text-align: center; padding: 36px 14px; color: var(--text-muted); font-size: var(--text-base, 0.875rem);">
                                    <i class="fas fa-folder-open mb-2" style="font-size: var(--text-3xl, 1.875rem); opacity: 0.4;"></i>
                                    <div>Belum ada data koleksi buku yang tercatat di sistem.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- 3. Footer Paginasi Terpadu Datatable -->
            @if ($list->hasPages())
                <div class="datatable-footer">
                    @include('partials.datatable-pagination', ['paginator' => $list])
                </div>
            @endif
        </div>

        {{-- Modal Tambah / Edit Koleksi Buku --}}
        <div id="modalBuku"
            style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.5); z-index: 99999 !important; justify-content: center; align-items: center;">
            <div class="card" style="width: 100%; max-width: 600px; margin: 20px; max-height: 90vh; overflow-y: auto;">
                <div
                    style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); padding-bottom: 12px; margin-bottom: 16px;">
                    <h3 id="modalBukuTitle"
                        style="font-size: var(--text-xl, 1.25rem); font-weight: 700; margin: 0; color: var(--text-heading);">Tambah Koleksi
                        Buku</h3>
                    <button type="button" onclick="closeModalBuku()"
                        style="background: none; border: none; font-size: var(--text-xl, 1.25rem); cursor: pointer; color: var(--text-muted);">&times;</button>
                </div>

                <form id="formBuku" method="POST" action="{{ route('dashboard.perpustakaan.koleksi.store') }}"
                    data-store-url="{{ route('dashboard.perpustakaan.koleksi.store') }}"
                    data-update-base="{{ url('/dashboard/perpustakaan/koleksi') }}"
                    data-update-url="{{ url('/dashboard/perpustakaan/koleksi') }}/:id">
                    @csrf
                    <input type="hidden" name="_method" id="bukuMethod" value="POST">
                    <input type="hidden" name="id" id="buku_id">

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                        <div>
                            <label class="form-label">Kode Buku <span style="color:red;">*</span></label>
                            <input type="text" name="kode_buku" id="buku_kode" class="form-control"
                                placeholder="Contoh: BK-001" required>
                        </div>
                        <div>
                            <label class="form-label">ISBN</label>
                            <input type="text" name="isbn" id="buku_isbn" class="form-control"
                                placeholder="978-xxxxxxxx">
                        </div>
                    </div>

                    <div style="margin-bottom: 12px;">
                        <label class="form-label">Judul Buku <span style="color:red;">*</span></label>
                        <input type="text" name="judul" id="buku_judul" class="form-control"
                            placeholder="Judul lengkap buku" required>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                        <div>
                            <label class="form-label">Penulis / Pengarang</label>
                            <input type="text" name="penulis" id="buku_penulis" class="form-control"
                                placeholder="Nama penulis">
                        </div>
                        <div>
                            <label class="form-label">Penerbit</label>
                            <input type="text" name="penerbit" id="buku_penerbit" class="form-control"
                                placeholder="Nama penerbit">
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                        <div>
                            <label class="form-label">Tahun Terbit</label>
                            <input type="number" name="tahun_terbit" id="buku_tahun" class="form-control"
                                placeholder="Contoh: 2024">
                        </div>
                        <div>
                            <label class="form-label">Klasifikasi DDC</label>
                            <input type="text" name="klasifikasi_ddc" id="buku_ddc" class="form-control"
                                placeholder="Contoh: 005.1 (Ilmu Komputer)">
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 16px;">
                        <div>
                            <label class="form-label">Kategori</label>
                            <select name="kategori" id="buku_kategori" class="form-control">
                                <option value="Buku Pelajaran">Buku Pelajaran</option>
                                <option value="Fiksi">Fiksi / Novel</option>
                                <option value="Non-Fiksi">Non-Fiksi</option>
                                <option value="Referensi">Referensi / Ensiklopedia</option>
                                <option value="Majalah">Majalah / Jurnal</option>
                                <option value="Umum" selected>Umum</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Jumlah Eksemplar <span style="color:red;">*</span></label>
                            <input type="number" name="jumlah_eksemplar" id="buku_eksemplar" class="form-control"
                                min="1" value="1" required>
                        </div>
                    </div>

                    <div style="margin-bottom: 16px;">
                        <label class="form-label">Lokasi Rak / Penempatan</label>
                        <input type="text" name="lokasi_rak" id="buku_rak" class="form-control"
                            placeholder="Contoh: Rak A-02, Baris 3">
                    </div>

                    <div style="display: flex; justify-content: flex-end; gap: 10px;">
                        <button type="button" class="btn btn-secondary" onclick="closeModalBuku()">Batal</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan Buku</button>
                    </div>
                </form>
            </div>
        </div>
    @endsection

    @push('scripts')
        <script
            src="{{ asset('js/perpus-koleksi.js') }}?v={{ file_exists(public_path('js/perpus-koleksi.js')) ? filemtime(public_path('js/perpus-koleksi.js')) : time() }}">
        </script>
    @endpush
