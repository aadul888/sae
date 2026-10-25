@extends('layouts.dashboard')

@section('title', 'Peminjaman Sarpras — Sarpras SAE')
@section('dash_title', 'Peminjaman Sarpras')

@section('content')
    <div class="dash-content-inner">
        {{-- 1. Header Banner & Action Button --}}
        <div class="dash-banner"
            style="display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; margin-bottom: 20px;">
            <div style="display: flex; align-items: center; gap: 14px;">
                <div
                    style="width: 48px; height: 48px; border-radius: var(--radius-lg, 12px); background: rgba(59,130,246,0.12); color: #3b82f6; display: flex; align-items: center; justify-content: center; font-size: var(--text-2xl, 1.5rem); flex-shrink: 0;">
                    <i class="fas fa-hand-holding-hand"></i>
                </div>
                <div>
                    <h2 style="font-size: var(--text-xl, 1.25rem); font-weight: 800; color: var(--text-color); margin: 0;">
                        Peminjaman &amp; Pengembalian Sarpras
                    </h2>
                    <div style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); margin-top: 2px;">
                        Pencatatan peminjaman alat, ruang, atau sarana sekolah oleh GTK, siswa, atau kegiatan luar.
                    </div>
                </div>
            </div>
            <div class="dash-banner-actions" style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                @if ($canCreate)
                    <button type="button" class="btn btn-primary" id="btnOpenModalPinjam"
                        style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px); font-size: var(--text-md, 1rem);"
                        title="Catat Peminjaman Sarpras">
                        <i class="fas fa-plus"></i>
                    </button>
                @endif
            </div>
        </div>

        {{-- 2. Stat Grid (4 Kartu Ringkasan) --}}
        <div class="dash-stat-grid" style="margin-bottom: 24px; gap: 14px;">
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(99, 102, 241, 0.15); color: var(--primary);">
                    <i class="fas fa-receipt"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ $statTotal }}</div>
                    <div class="dash-stat-label">Total Transaksi</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b;">
                    <i class="fas fa-hand-holding"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ $statDipinjam }}</div>
                    <div class="dash-stat-label">Sedang Dipinjam</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(16, 185, 129, 0.15); color: #10b981;">
                    <i class="fas fa-box-archive"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ $statKembali }}</div>
                    <div class="dash-stat-label">Sudah Kembali</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(239, 68, 68, 0.15); color: #ef4444;">
                    <i class="fas fa-clock-rotate-left"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ $statTerlambat }}</div>
                    <div class="dash-stat-label">Lewat Batas Waktu</div>
                </div>
            </div>
        </div>

        {{-- Unified Datatable Peminjaman Sarpras (Toolbar + Table + Pagination dalam 1 Card Terpadu) --}}
        <div class="card datatable-card-wrapper" id="tableDataContainer">
            <!-- 1. Toolbar Header (Entries, Filters & Live Search) -->
            <div class="datatable-toolbar">
                <form method="GET" action="{{ route('dashboard.sarpras.peminjaman.index') }}" id="filterForm"
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
                                style="min-width: 150px; height: 38px;">
                                <option value="">Semua Status</option>
                                <option value="dipinjam" {{ $statusFilter === 'dipinjam' ? 'selected' : '' }}>Sedang
                                    Dipinjam
                                </option>
                                <option value="kembali" {{ $statusFilter === 'kembali' ? 'selected' : '' }}>Sudah Kembali
                                </option>
                            </select>

                            @if ($search || $statusFilter)
                                <a href="{{ route('dashboard.sarpras.peminjaman.index') }}"
                                    class="btn btn-outline btn-reset-filter"
                                    style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px);"
                                    title="Reset filter" data-live-reset><i class="fas fa-undo"></i></a>
                            @endif
                        </div>

                        <div class="live-search-wrap" style="height: 38px;">
                            <i class="fas fa-search search-icon"></i>
                            <input type="text" name="q" id="liveSearch" value="{{ $search }}"
                                placeholder="Cari nomor pinjam, peminjam, barang, keperluan..." autocomplete="off">
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
                                No Pinjam &amp; Barang</th>
                            <th
                                style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Peminjam</th>
                            <th
                                style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Keperluan</th>
                            <th
                                style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Tanggal &amp; Batas</th>
                            <th
                                style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center;">
                                Status</th>
                            <th
                                style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: right;">
                                Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($list as $idx => $item)
                            @php
                                $isLate =
                                    $item->status === 'dipinjam' &&
                                    $item->tanggal_kembali_rencana < now()->toDateString();
                            @endphp
                            <tr style="border-bottom: 1px solid var(--border-color); transition: background 0.2s ease;">
                                <td data-label="No"
                                    style="padding: 14px 18px; font-size: var(--text-base, 0.875rem); color: var(--text-muted);">
                                    {{ $list->firstItem() + $idx }}
                                </td>
                                <td data-label="Barang" style="padding: 14px 18px;">
                                    <div style="font-weight: 700; color: var(--text-color); font-size: var(--text-base, 0.875rem);">
                                        {{ $item->nama_barang }}</div>
                                    <div style="font-size: var(--text-sm, 0.8125rem); color: var(--primary); font-family: monospace;">
                                        {{ $item->nomor_pinjam }}</div>
                                    <div style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted);">Kode:
                                        {{ $item->kode_aset }} |
                                        {{ $item->kategori }}</div>
                                </td>
                                <td data-label="Peminjam" style="padding: 14px 18px; font-size: var(--text-base, 0.875rem);">
                                    <div style="font-weight: 600; color: var(--text-color);">{{ $item->peminjam_nama }}
                                    </div>
                                    <span
                                        class="badge {{ $item->peminjam_tipe === 'gtk' ? 'badge-info' : ($item->peminjam_tipe === 'siswa' ? 'badge-primary' : 'badge-secondary') }}"
                                        style="font-size: var(--text-xs, 0.75rem); text-transform: uppercase;">
                                        {{ $item->peminjam_tipe }}
                                    </span>
                                </td>
                                <td data-label="Keperluan"
                                    style="padding: 14px 18px; font-size: var(--text-base, 0.875rem); color: var(--text-color);">
                                    {{ $item->keperluan }}
                                </td>
                                <td data-label="Tanggal" style="padding: 14px 18px; font-size: var(--text-base, 0.875rem);">
                                    <div>Pinjam: {{ \Carbon\Carbon::parse($item->tanggal_pinjam)->format('d/m/Y') }}</div>
                                    <div
                                        style="color: {{ $isLate ? '#ef4444; font-weight: 700;' : 'var(--text-muted)' }};">
                                        Batas: {{ \Carbon\Carbon::parse($item->tanggal_kembali_rencana)->format('d/m/Y') }}
                                        @if ($isLate)
                                            <span class="badge badge-danger" style="font-size: var(--text-2xs, 0.6875rem);">Terlambat</span>
                                        @endif
                                    </div>
                                    @if ($item->tanggal_kembali_aktual)
                                        <div style="font-size: var(--text-sm, 0.8125rem); color: #10b981;">Kembali:
                                            {{ \Carbon\Carbon::parse($item->tanggal_kembali_aktual)->format('d/m/Y') }}
                                        </div>
                                    @endif
                                </td>
                                <td data-label="Status" style="padding: 14px 18px; text-align: center;">
                                    @if ($item->status === 'dipinjam')
                                        <span class="badge badge-warning">Sedang Dipinjam</span>
                                    @else
                                        <span class="badge badge-success">Sudah Kembali</span>
                                        @if ($item->kondisi_sesudah)
                                            <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-top: 3px;">
                                                Kondisi: {{ str_replace('_', ' ', $item->kondisi_sesudah) }}</div>
                                        @endif
                                    @endif
                                </td>
                                <td data-label="Aksi" style="padding: 14px 18px; text-align: right;">
                                    <div class="table-actions" style="justify-content: flex-end; gap: 6px;">
                                        @if ($item->status === 'dipinjam' && $canUpdate)
                                            <button type="button" class="btn-icon btn-kembalikan"
                                                data-id="{{ $item->id }}" data-nomor="{{ $item->nomor_pinjam }}"
                                                data-barang="{{ $item->nama_barang }}" title="Catat Pengembalian">
                                                <i class="fas fa-box-archive" style="color: #10b981;"></i>
                                            </button>
                                        @endif
                                        @if ($canDelete)
                                            <button type="button" class="btn-icon btn-delete-pinjam"
                                                data-id="{{ $item->id }}" data-nomor="{{ $item->nomor_pinjam }}"
                                                title="Hapus Catatan" style="color: #ef4444;">
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
                                    <div>Belum ada transaksi peminjaman.</div>
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

        {{-- Modal Catat Peminjaman --}}
        <div id="modalPinjam" class="modal-backdrop"
            style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
            <div class="card"
                style="width: 100%; max-width: 560px; max-height: 90vh; overflow-y: auto; padding: 24px; border-radius: var(--radius-lg, 12px);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h3 style="margin: 0; font-size: var(--text-xl, 1.25rem); font-weight: 700; color: var(--text-color);">
                        Catat Peminjaman Sarpras
                    </h3>
                    <button type="button" id="btnCloseModalPinjam"
                        style="background: none; border: none; font-size: var(--text-xl, 1.25rem); color: var(--text-muted); cursor: pointer;">&times;</button>
                </div>

                <form id="formPinjam" method="POST" action="{{ route('dashboard.sarpras.peminjaman.store') }}">
                    @csrf
                    <div class="form-group" style="margin-bottom: 14px;">
                        <label
                            style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Pilih
                            Barang / Aset Tersedia <span style="color: #ef4444;">*</span></label>
                        <select name="aset_id" id="pinjamAsetId" class="form-control" required>
                            <option value="">-- Pilih Barang --</option>
                            @foreach ($asetTersedia as $ast)
                                <option value="{{ $ast->id }}">{{ $ast->nama_barang }} ({{ $ast->kode_aset }}) -
                                    {{ $ast->jumlah }} {{ $ast->satuan }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 12px; margin-bottom: 14px;">
                        <div>
                            <label
                                style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Tipe
                                Peminjam <span style="color: #ef4444;">*</span></label>
                            <select name="peminjam_tipe" id="pinjamTipeSelect" class="form-control" required>
                                <option value="gtk">Guru / Tendik</option>
                                <option value="siswa">Peserta Didik</option>
                                <option value="umum">Pihak Luar / Umum</option>
                            </select>
                        </div>
                        <div>
                            <label
                                style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Pilih
                                / Nama Peminjam <span style="color: #ef4444;">*</span></label>
                            <input type="hidden" name="peminjam_id" id="pinjamIdVal" value="">

                            {{-- 1. Pilihan GTK (Guru / Tendik) --}}
                            <div id="wrapSelectGtk">
                                <input type="search" id="selectPeminjamGtk" class="form-control"
                                    placeholder="Cari guru / tendik..." autocomplete="off"
                                    data-people-search="{{ route('dashboard.people.search') }}" data-people-kind="gtk"
                                    data-people-target="#pinjamIdVal" data-people-name-target="#realPeminjamNama"
                                    data-people-require-selection="true" data-people-gtk-type="gtk">
                            </div>

                            {{-- 2. Pilihan Peserta Didik --}}
                            <div id="wrapSelectSiswa" style="display: none;">
                                <input type="search" id="inputPeminjamSiswa" class="form-control"
                                    placeholder="Cari nama, NISN, atau NIPD..." autocomplete="off"
                                    data-people-search="{{ route('dashboard.people.search') }}" data-people-kind="siswa"
                                    data-people-target="#pinjamIdVal" data-people-name-target="#realPeminjamNama"
                                    data-people-require-selection="true">
                            </div>

                            {{-- 3. Input Bebas Pihak Luar --}}
                            <div id="wrapInputUmum" style="display: none;">
                                <input type="text" id="inputPeminjamUmum" class="form-control"
                                    placeholder="Ketik nama lengkap peminjam umum / lembaga...">
                            </div>

                            {{-- Input real peminjam_nama yang terkirim ke server --}}
                            <input type="hidden" name="peminjam_nama" id="realPeminjamNama" required>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                        <div>
                            <label
                                style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Tanggal
                                Pinjam <span style="color: #ef4444;">*</span></label>
                            <input type="date" name="tanggal_pinjam" value="{{ date('Y-m-d') }}"
                                class="form-control" required>
                        </div>
                        <div>
                            <label
                                style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Rencana
                                Kembali <span style="color: #ef4444;">*</span></label>
                            <input type="date" name="tanggal_kembali_rencana"
                                value="{{ date('Y-m-d', strtotime('+1 day')) }}" class="form-control" required>
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: 14px;">
                        <div
                            style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                            <label
                                style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin: 0; color: var(--text-color);">Keperluan
                                & Lokasi Ruang Pemakaian <span style="color: #ef4444;">*</span></label>
                            <span style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted);"><i
                                    class="fas fa-building me-1"></i>Ruang Sarpras:</span>
                        </div>
                        <div style="display: flex; gap: 8px; margin-bottom: 6px;">
                            <select id="suggestRuangSelect" class="form-control"
                                style="font-size: var(--text-sm, 0.8125rem); padding: 6px 10px;">
                                <option value="">-- Pilih Ruang Pemakaian (Opsional) --</option>
                                @foreach ($daftarRuang as $rng)
                                    <option value="di {{ $rng->nama_ruang }} ({{ $rng->gedung }})">
                                        {{ $rng->nama_ruang }} -
                                        {{ $rng->gedung }}</option>
                                @endforeach
                            </select>
                        </div>
                        <textarea name="keperluan" id="pinjamKeperluan" class="form-control" rows="2"
                            placeholder="Contoh: Praktik KBM di Lab Komputer 1 / Kegiatan OSIS di Aula..." required></textarea>
                    </div>

                    <div class="form-group" style="margin-bottom: 20px;">
                        <label
                            style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Catatan
                            Tambahan (Opsional)</label>
                        <input type="text" name="catatan" class="form-control"
                            placeholder="Kelengkapan kabel adaptor, remote, tas bawaan...">
                    </div>

                    <div style="display: flex; justify-content: flex-end; gap: 8px;">
                        <button type="button" class="btn btn-secondary" id="btnCancelModalPinjam">Batal</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Simpan
                            Peminjaman</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Modal Catat Pengembalian --}}
        <div id="modalKembali" class="modal-backdrop"
            style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
            <div class="card" style="width: 100%; max-width: 480px; padding: 24px; border-radius: var(--radius-lg, 12px);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                    <h3 style="margin: 0; font-size: var(--text-xl, 1.25rem); font-weight: 700; color: var(--text-color);">
                        Konfirmasi Pengembalian
                    </h3>
                    <button type="button" id="btnCloseModalKembali"
                        style="background: none; border: none; font-size: var(--text-xl, 1.25rem); color: var(--text-muted); cursor: pointer;">&times;</button>
                </div>

                <form id="formKembali" method="POST" action="">
                    @csrf
                    <p id="kembaliDesc" style="font-size: var(--text-base, 0.875rem); color: var(--text-color); margin-bottom: 16px;"></p>

                    <div class="form-group" style="margin-bottom: 14px;">
                        <label
                            style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Kondisi
                            Barang Saat Kembali <span style="color: #ef4444;">*</span></label>
                        <select name="kondisi_sesudah" class="form-control" required>
                            <option value="baik">Baik (Lengkap &amp; Berfungsi Normal)</option>
                            <option value="rusak_ringan">Rusak Ringan (Perlu Pembersihan / Setting)</option>
                            <option value="rusak_berat">Rusak Berat (Perlu Perbaikan / Komponen Rusak)</option>
                        </select>
                    </div>

                    <div class="form-group" style="margin-bottom: 20px;">
                        <label
                            style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Catatan
                            Pengembalian</label>
                        <textarea name="catatan" class="form-control" rows="2"
                            placeholder="Catatan kondisi fisik saat diterima kembali..."></textarea>
                    </div>

                    <div style="display: flex; justify-content: flex-end; gap: 8px;">
                        <button type="button" class="btn btn-secondary" id="btnCancelModalKembali">Batal</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-check me-1"></i> Selesaikan
                            Pengembalian</button>
                    </div>
                </form>
            </div>
        </div>

        @push('scripts')
            <script
                src="{{ asset('js/sarpras-peminjaman.js') }}?v={{ file_exists(public_path('js/sarpras-peminjaman.js')) ? filemtime(public_path('js/sarpras-peminjaman.js')) : time() }}">
            </script>
        @endpush
    @endsection
