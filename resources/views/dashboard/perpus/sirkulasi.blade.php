@extends('layouts.dashboard')

@section('title', 'Sirkulasi Peminjaman Buku - Perpustakaan')

@section('content')
    <div class="dash-content-inner">
        <!-- 1. Header Banner & Actions -->
        <div class="dash-banner"
            style="display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; margin-bottom: 20px;">
            <div style="display: flex; align-items: center; gap: 14px;">
                <div
                    style="width: 48px; height: 48px; border-radius: var(--radius-lg, 12px); background: rgba(59,130,246,0.12); color: #3b82f6; display: flex; align-items: center; justify-content: center; font-size: var(--text-2xl, 1.5rem); flex-shrink: 0;">
                    <i class="fas fa-exchange-alt"></i>
                </div>
                <div>
                    <h2 style="font-size: var(--text-xl, 1.25rem); font-weight: 800; color: var(--text-color); margin: 0;">
                        Sirkulasi Peminjaman &amp; Pengembalian
                    </h2>
                    <div style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); margin-top: 2px;">
                        Manajemen peminjaman buku, pengembalian, perpanjangan, dan pencatatan denda keterlambatan.
                    </div>
                </div>
            </div>

            <div class="dash-banner-actions" style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                <button type="button" class="btn btn-primary" id="btnTambahPeminjaman" onclick="openModalPinjam()"
                    style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px); font-size: var(--text-md, 1rem);"
                    title="Transaksi Peminjaman Buku">
                    <i class="fas fa-plus"></i>
                </button>
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
                <div class="dash-stat-icon" style="background: rgba(59,130,246,0.12); color: #3b82f6;">
                    <i class="fas fa-book-reader"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ number_format($stats['total_dipinjam'] ?? 0) }}</div>
                    <div class="dash-stat-label">Sedang Dipinjam</div>
                </div>
            </div>

            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(239,68,68,0.12); color: #ef4444;">
                    <i class="fas fa-triangle-exclamation"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ number_format($stats['terlambat'] ?? 0) }}</div>
                    <div class="dash-stat-label">Jatuh Tempo / Terlambat</div>
                </div>
            </div>

            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(16,185,129,0.12); color: #10b981;">
                    <i class="fas fa-circle-check"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ number_format($stats['total_kembali'] ?? 0) }}</div>
                    <div class="dash-stat-label">Total Sudah Kembali</div>
                </div>
            </div>

            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(245,158,11,0.12); color: #f59e0b;">
                    <i class="fas fa-coins"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">Rp {{ number_format($stats['denda_terkumpul'] ?? 0, 0, ',', '.') }}</div>
                    <div class="dash-stat-label">Denda Terkumpul</div>
                </div>
            </div>
        </div>

        <!-- Unified Datatable Sirkulasi Perpustakaan (Toolbar + Table + Pagination dalam 1 Card Terpadu) -->
        <div class="card datatable-card-wrapper" id="tableDataContainer">
            <!-- 1. Toolbar Header (Entries, Filters & Live Search) -->
            <div class="datatable-toolbar">
                <form method="GET" action="{{ route('dashboard.perpustakaan.sirkulasi') }}" id="filterForm"
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

                            <select name="status" id="filterStatus" class="toolbar-filter-select"
                                style="height: 38px; min-width: 160px;">
                                <option value="">Semua Status</option>
                                <option value="dipinjam" {{ ($status ?? '') === 'dipinjam' ? 'selected' : '' }}>Dipinjam
                                </option>
                                <option value="kembali" {{ ($status ?? '') === 'kembali' ? 'selected' : '' }}>Kembali
                                </option>
                                <option value="terlambat" {{ ($status ?? '') === 'terlambat' ? 'selected' : '' }}>Terlambat
                                </option>
                                <option value="hilang" {{ ($status ?? '') === 'hilang' ? 'selected' : '' }}>Hilang / Rusak
                                </option>
                            </select>

                            @if (!empty($q) || !empty($status))
                                <a href="{{ route('dashboard.perpustakaan.sirkulasi') }}"
                                    class="btn btn-outline btn-reset-filter"
                                    style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px);"
                                    title="Reset filter" data-live-reset><i class="fas fa-undo"></i></a>
                            @endif
                        </div>

                        <div class="live-search-wrap" style="height: 38px;">
                            <i class="fas fa-search search-icon"></i>
                            <input type="text" name="q" id="liveSearch" value="{{ $q ?? '' }}"
                                placeholder="Cari kode transaksi, peminjam, atau judul buku..." autocomplete="off">
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
                            <th class="sortable-th {{ ($sort ?? '') === 'kode_transaksi' ? 'sorted' : '' }}" data-sort="kode_transaksi"
                                style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Kode &amp; Buku
                                <span class="sort-icon">{!! ($sort ?? '') === 'kode_transaksi' ? (($sortDir ?? '') === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                            </th>
                            <th class="sortable-th {{ ($sort ?? '') === 'peminjam_nama' ? 'sorted' : '' }}" data-sort="peminjam_nama"
                                style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Peminjam
                                <span class="sort-icon">{!! ($sort ?? '') === 'peminjam_nama' ? (($sortDir ?? '') === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                            </th>
                            <th class="sortable-th {{ ($sort ?? '') === 'tgl_pinjam' ? 'sorted' : '' }}" data-sort="tgl_pinjam"
                                style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Tgl Pinjam / Tempo
                                <span class="sort-icon">{!! ($sort ?? '') === 'tgl_pinjam' ? (($sortDir ?? '') === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                            </th>
                            <th class="sortable-th {{ ($sort ?? '') === 'status' ? 'sorted' : '' }}" data-sort="status"
                                style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 120px;">
                                Status
                                <span class="sort-icon">{!! ($sort ?? '') === 'status' ? (($sortDir ?? '') === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                            </th>
                            <th class="sortable-th {{ ($sort ?? '') === 'denda' ? 'sorted' : '' }}" data-sort="denda"
                                style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Denda
                                <span class="sort-icon">{!! ($sort ?? '') === 'denda' ? (($sortDir ?? '') === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                            </th>
                            <th
                                style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 110px;">
                                Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($sirkulasi as $idx => $row)
                            @php
                                $isOverdue =
                                    $row->status === 'dipinjam' &&
                                    \Carbon\Carbon::parse($row->tgl_jatuh_tempo)->isPast();
                            @endphp
                            <tr style="border-bottom: 1px solid var(--border-color); transition: background 0.2s ease;">
                                <td style="padding: 14px 18px; text-align: center; font-weight: 700; color: var(--text-muted); font-size: var(--text-sm, 0.8125rem);"
                                    data-label="No">
                                    {{ ($sirkulasi->currentPage() - 1) * $sirkulasi->perPage() + $loop->iteration }}
                                </td>
                                <td style="padding: 14px 18px;" data-label="Kode & Buku">
                                    <div style="font-weight: 600; color: var(--text-heading); font-size: var(--text-base, 0.875rem);">
                                        {{ $row->judul_buku ?? ($row->buku->judul ?? '-') }}
                                    </div>
                                    <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); font-family: monospace;">
                                        TR: {{ $row->kode_transaksi }} | Kode: {{ $row->kode_buku ?? ($row->buku->kode_buku ?? '-') }}
                                    </div>
                                </td>
                                <td style="padding: 14px 18px;" data-label="Peminjam">
                                    <div style="font-weight: 600; color: var(--text-heading); font-size: var(--text-base, 0.875rem);">
                                        {{ $row->peminjam_nama }}</div>
                                    <span class="badge {{ $row->peminjam_tipe === 'siswa' ? 'badge-primary' : 'badge-outline' }}"
                                        style="font-size: var(--text-xs, 0.75rem); padding: 2px 8px;">
                                        {{ strtoupper($row->peminjam_tipe) }}
                                    </span>
                                </td>
                                <td style="padding: 14px 18px; font-size: var(--text-base, 0.875rem);" data-label="Tgl Pinjam / Tempo">
                                    <div>Pinjam: {{ \Carbon\Carbon::parse($row->tgl_pinjam)->format('d M Y') }}</div>
                                    <div
                                        style="color: {{ $isOverdue ? '#ef4444' : 'var(--text-muted)' }}; font-weight: {{ $isOverdue ? '600' : 'normal' }}; font-size: var(--text-sm, 0.8125rem);">
                                        Tempo: {{ \Carbon\Carbon::parse($row->tgl_jatuh_tempo)->format('d M Y') }}
                                        @if ($isOverdue)
                                            <i class="fas fa-exclamation-circle text-danger ms-1" title="Terlambat"></i>
                                        @endif
                                    </div>
                                    @if ($row->tgl_kembali)
                                        <div style="color: #10b981; font-size: var(--text-sm, 0.8125rem);">Kembali:
                                            {{ \Carbon\Carbon::parse($row->tgl_kembali)->format('d M Y') }}</div>
                                    @endif
                                </td>
                                <td style="padding: 14px 18px; text-align: center;" data-label="Status">
                                    @if ($row->status === 'dipinjam')
                                        @if ($isOverdue)
                                            <span class="badge badge-danger" style="font-size: var(--text-xs, 0.75rem); padding: 2px 7px;">
                                                Terlambat
                                            </span>
                                        @else
                                            <span class="badge badge-warning" style="font-size: var(--text-xs, 0.75rem); padding: 2px 7px;">
                                                Dipinjam
                                            </span>
                                        @endif
                                    @elseif ($row->status === 'kembali')
                                        <span class="badge badge-success" style="font-size: var(--text-xs, 0.75rem); padding: 2px 7px;">
                                            Kembali
                                        </span>
                                    @else
                                        <span class="badge badge-outline" style="font-size: var(--text-xs, 0.75rem); padding: 2px 7px;">
                                            {{ ucfirst($row->status) }}
                                        </span>
                                    @endif
                                </td>
                                <td style="padding: 14px 18px; font-size: var(--text-base, 0.875rem);" data-label="Denda">
                                    @if ($row->denda > 0)
                                        <div style="font-weight: 700; color: #d97706;">Rp
                                            {{ number_format($row->denda, 0, ',', '.') }}</div>
                                        <span class="badge {{ $row->status_denda === 'lunas' ? 'badge-success' : 'badge-danger' }}"
                                            style="font-size: var(--text-xs, 0.75rem); padding: 2px 6px;">
                                            {{ strtoupper($row->status_denda) }}
                                        </span>
                                    @else
                                        <span style="color: var(--text-muted); font-size: var(--text-sm, 0.8125rem);">-</span>
                                    @endif
                                </td>
                                <td style="padding: 14px 18px; text-align: center;" data-label="Aksi">
                                    <div class="table-actions" style="display: flex; gap: 6px; justify-content: center;">
                                        @if ($row->status === 'dipinjam')
                                            <button type="button" class="btn-icon btn-kembalikan"
                                                data-id="{{ $row->id }}" data-kode="{{ $row->kode_transaksi }}"
                                                data-judul="{{ $row->judul_buku ?? ($row->buku->judul ?? 'Buku') }}"
                                                title="Proses Pengembalian"
                                                style="color: #10b981;">
                                                <i class="fas fa-undo-alt"></i>
                                            </button>
                                        @endif
                                        <form action="{{ route('dashboard.perpustakaan.sirkulasi.destroy', $row->id) }}"
                                            method="POST" style="display: inline;" data-confirm="delete">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-icon btn-delete-sirkulasi"
                                                data-kode="{{ $row->kode_transaksi }}" title="Hapus Transaksi"
                                                style="color: #ef4444;">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 36px 14px; color: var(--text-muted); font-size: var(--text-base, 0.875rem);">
                                    <i class="fas fa-folder-open mb-2" style="font-size: var(--text-3xl, 1.875rem); opacity: 0.4;"></i>
                                    <div>Belum ada data sirkulasi peminjaman buku yang tercatat di sistem.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- 3. Footer Paginasi Terpadu Datatable -->
            @if ($sirkulasi->hasPages())
                <div class="datatable-footer">
                    @include('partials.datatable-pagination', ['paginator' => $sirkulasi])
                </div>
            @endif
        </div>

        <!-- Modal Tambah Transaksi Pinjam -->
        <div id="modalPinjam" class="modal-backdrop"
            style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.5); z-index: 99999 !important; justify-content: center; align-items: center; padding: 20px;">
            <div class="modal-card"
                style="background: var(--card-bg, #fff); border-radius: var(--radius-lg, 12px); max-width: 600px; width: 100%; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2); overflow: hidden;">
                <div
                    style="padding: 16px 20px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
                    <h3 style="margin: 0; font-size: var(--text-lg, 1.125rem); font-weight: 700; color: var(--text-heading);">
                        <i class="fas fa-plus-circle" style="color: var(--primary-color); margin-right: 8px;"></i>
                        Tambah Transaksi Pinjam Buku
                    </h3>
                    <button type="button" onclick="closeModalPinjam()"
                        style="background: none; border: none; font-size: var(--text-xl, 1.25rem); cursor: pointer; color: var(--text-muted);">&times;</button>
                </div>
                <form action="{{ route('dashboard.perpustakaan.sirkulasi.store') }}" method="POST"
                    style="padding: 20px;">
                    @csrf
                    <div style="display: grid; grid-template-columns: 1fr; gap: 14px;">
                        <div>
                            <label class="form-label"
                                style="font-size: var(--text-base, 0.875rem); font-weight: 600; color: var(--text-heading); margin-bottom: 4px;">Pilih
                                Buku *</label>
                            <select name="buku_id" id="pinjam_buku_id" class="form-control" required>
                                <option value="">-- Cari Judul Buku / Kode --</option>
                                @foreach ($bukuList as $buku)
                                    <option value="{{ $buku->id }}">
                                        [{{ $buku->kode_buku }}] {{ $buku->judul }} (Tersedia:
                                        {{ $buku->eksemplar_tersedia }} eks)
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 12px;">
                            <div>
                                <label class="form-label"
                                    style="font-size: var(--text-base, 0.875rem); font-weight: 600; color: var(--text-heading); margin-bottom: 4px;">Tipe
                                    Peminjam *</label>
                                <select name="peminjam_tipe" id="pinjam_tipe" class="form-control" required>
                                    <option value="siswa">Peserta Didik</option>
                                    <option value="guru">Guru / Tendik</option>
                                    <option value="umum">Umum</option>
                                </select>
                            </div>
                            <div>
                                <div
                                    style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                                    <label class="form-label"
                                        style="font-size: var(--text-base, 0.875rem); font-weight: 600; color: var(--text-heading); margin: 0;">Nama
                                        Peminjam *</label>
                                    <span style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted);"><i
                                            class="fas fa-users me-1"></i>Relasi Siswa / GTK</span>
                                </div>
                                <input type="hidden" name="peminjam_id" id="pinjam_id_val" value="">
                                <input type="search" name="peminjam_nama" id="pinjam_nama" class="form-control"
                                    placeholder="Cari siswa/GTK atau ketik nama..." required autocomplete="off"
                                    data-people-search="{{ route('dashboard.people.search') }}" data-people-kind="all"
                                    data-people-target="#pinjam_id_val" data-people-type-target="#pinjam_tipe"
                                    data-people-gtk-type="guru">
                            </div>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                            <div>
                                <label class="form-label"
                                    style="font-size: var(--text-base, 0.875rem); font-weight: 600; color: var(--text-heading); margin-bottom: 4px;">Tanggal
                                    Pinjam *</label>
                                <input type="date" name="tgl_pinjam" id="pinjam_tgl" class="form-control"
                                    value="{{ date('Y-m-d') }}" required>
                            </div>
                            <div>
                                <label class="form-label"
                                    style="font-size: var(--text-base, 0.875rem); font-weight: 600; color: var(--text-heading); margin-bottom: 4px;">Jatuh
                                    Tempo (Kembali) *</label>
                                <input type="date" name="tgl_jatuh_tempo" id="pinjam_tempo" class="form-control"
                                    value="{{ date('Y-m-d', strtotime('+7 days')) }}" required>
                            </div>
                        </div>
                        <div>
                            <label class="form-label"
                                style="font-size: var(--text-base, 0.875rem); font-weight: 600; color: var(--text-heading); margin-bottom: 4px;">Catatan
                                Tambahan</label>
                            <textarea name="catatan" class="form-control" rows="2"
                                placeholder="Catatan kondisi buku atau kontak peminjam"></textarea>
                        </div>
                    </div>
                    <div
                        style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px; border-top: 1px solid var(--border-color); padding-top: 16px;">
                        <button type="button" onclick="closeModalPinjam()" class="btn btn-secondary">Batal</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-check"></i> Proses
                            Pinjam</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal Proses Pengembalian -->
        <div id="modalKembalikan" class="modal-backdrop"
            style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.5); z-index: 99999 !important; justify-content: center; align-items: center; padding: 20px;">
            <div class="modal-card"
                style="background: var(--card-bg, #fff); border-radius: var(--radius-lg, 12px); max-width: 500px; width: 100%; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2); overflow: hidden;">
                <div
                    style="padding: 16px 20px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
                    <h3 style="margin: 0; font-size: var(--text-lg, 1.125rem); font-weight: 700; color: var(--text-heading);">
                        <i class="fas fa-undo-alt" style="color: #10b981; margin-right: 8px;"></i>
                        Pengembalian Buku
                    </h3>
                    <button type="button" onclick="closeModalKembalikan()"
                        style="background: none; border: none; font-size: var(--text-xl, 1.25rem); cursor: pointer; color: var(--text-muted);">&times;</button>
                </div>
                <form id="formKembalikan" action="" method="POST" style="padding: 20px;">
                    @csrf
                    <div style="display: grid; grid-template-columns: 1fr; gap: 14px;">
                        <div style="background: var(--table-header-bg, #f8fafc); padding: 12px; border-radius: var(--radius-md, 8px);">
                            <div style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted);">Transaksi / Judul:</div>
                            <div id="kembaliDetailJudul"
                                style="font-weight: 600; color: var(--text-heading); font-size: var(--text-md, 1rem);">-</div>
                        </div>
                        <div>
                            <label class="form-label"
                                style="font-size: var(--text-base, 0.875rem); font-weight: 600; color: var(--text-heading); margin-bottom: 4px;">Tanggal
                                Kembali *</label>
                            <input type="date" name="tgl_kembali" class="form-control" value="{{ date('Y-m-d') }}"
                                required>
                        </div>
                        <div>
                            <label class="form-label"
                                style="font-size: var(--text-base, 0.875rem); font-weight: 600; color: var(--text-heading); margin-bottom: 4px;">Denda
                                Keterlambatan / Kerusakan (Rp)</label>
                            <input type="number" name="denda" class="form-control" value="0" min="0"
                                step="500">
                        </div>
                        <div>
                            <label class="form-label"
                                style="font-size: var(--text-base, 0.875rem); font-weight: 600; color: var(--text-heading); margin-bottom: 4px;">Status
                                Denda</label>
                            <select name="status_denda" class="form-control">
                                <option value="lunas">Lunas</option>
                                <option value="belum">Belum Dibayar</option>
                            </select>
                        </div>
                    </div>
                    <div
                        style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px; border-top: 1px solid var(--border-color); padding-top: 16px;">
                        <button type="button" onclick="closeModalKembalikan()" class="btn btn-secondary">Batal</button>
                        <button type="submit" class="btn btn-primary"
                            style="background-color: #10b981; border-color: #10b981;"><i class="fas fa-check"></i> Simpan
                            Pengembalian</button>
                    </div>
                </form>
            </div>
        </div>
    @endsection

    @push('scripts')
        <script
            src="{{ asset('js/perpus-sirkulasi.js') }}?v={{ file_exists(public_path('js/perpus-sirkulasi.js')) ? filemtime(public_path('js/perpus-sirkulasi.js')) : time() }}">
        </script>
    @endpush
