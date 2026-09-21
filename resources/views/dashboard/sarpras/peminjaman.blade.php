@extends('layouts.dashboard')

@section('title', 'Peminjaman Sarpras — Sarpras SAE')
@section('dash_title', 'Peminjaman Sarpras')

@section('content')
<div class="dash-content-inner">
    {{-- 1. Header Banner & Action Button --}}
    <div class="dash-banner" style="display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; margin-bottom: 20px;">
        <div style="display: flex; align-items: center; gap: 14px;">
            <div style="width: 48px; height: 48px; border-radius: 12px; background: rgba(59,130,246,0.12); color: #3b82f6; display: flex; align-items: center; justify-content: center; font-size: 1.35rem; flex-shrink: 0;">
                <i class="fas fa-hand-holding-hand"></i>
            </div>
            <div>
                <h2 style="font-size: 1.3rem; font-weight: 800; color: var(--text-color); margin: 0;">
                    Peminjaman &amp; Pengembalian Sarpras
                </h2>
                <div style="font-size: 0.82rem; color: var(--text-muted); margin-top: 2px;">
                    Pencatatan peminjaman alat, ruang, atau sarana sekolah oleh GTK, siswa, atau kegiatan luar.
                </div>
            </div>
        </div>
        <div class="dash-banner-actions" style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
            @if ($canCreate)
                <button type="button" class="btn btn-primary" id="btnOpenModalPinjam" style="display: inline-flex; align-items: center; gap: 8px; padding: 8px 16px; border-radius: 8px; font-weight: 600;">
                    <i class="fas fa-plus"></i> Catat Peminjaman
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

    {{-- 3. Toolbar Tabel (LiveSearch & Filter) --}}
    <div class="card" style="padding: 14px 18px; margin-bottom: 20px;">
        <form method="GET" action="{{ route('dashboard.sarpras.peminjaman.index') }}" id="filterForm" style="display: flex; gap: 10px; flex-wrap: wrap; align-items: center;">
            <div style="flex: 1; min-width: 200px; position: relative;">
                <input type="text" name="q" value="{{ $search }}" class="form-control" placeholder="Cari nomor pinjam, peminjam, barang, keperluan..." style="padding-left: 36px;">
                <i class="fas fa-search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 0.85rem;"></i>
            </div>
            <div style="min-width: 150px;">
                <select name="status" class="form-control" onchange="this.form.submit()">
                    <option value="">-- Semua Status --</option>
                    <option value="dipinjam" {{ $statusFilter === 'dipinjam' ? 'selected' : '' }}>Sedang Dipinjam</option>
                    <option value="kembali" {{ $statusFilter === 'kembali' ? 'selected' : '' }}>Sudah Kembali</option>
                </select>
            </div>
            <button type="submit" class="btn btn-secondary" title="Cari"><i class="fas fa-filter"></i></button>
            @if ($search || $statusFilter)
            <a href="{{ route('dashboard.sarpras.peminjaman.index') }}" class="btn btn-outline" title="Reset"><i class="fas fa-undo"></i></a>
            @endif
        </form>
    </div>

    {{-- 4. Datatable Responsif --}}
    <div class="card table-responsive-stack" id="tableDataContainer" style="padding: 0; margin-bottom: 24px;">
        <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
            <thead>
                <tr style="border-bottom: 1px solid var(--border-color); background: rgba(255, 255, 255, 0.02);">
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">No</th>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">No Pinjam &amp; Barang</th>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Peminjam</th>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Keperluan</th>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Tanggal &amp; Batas</th>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center;">Status</th>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: right;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($list as $idx => $item)
                @php
                    $isLate = ($item->status === 'dipinjam') && ($item->tanggal_kembali_rencana < now()->toDateString());
                @endphp
                <tr style="border-bottom: 1px solid var(--border-color); transition: background 0.2s ease;">
                    <td data-label="No" style="padding: 14px 18px; font-size: 0.88rem; color: var(--text-muted);">
                        {{ $list->firstItem() + $idx }}
                    </td>
                    <td data-label="Barang" style="padding: 14px 18px;">
                        <div style="font-weight: 700; color: var(--text-color); font-size: 0.92rem;">{{ $item->nama_barang }}</div>
                        <div style="font-size: 0.78rem; color: var(--primary); font-family: monospace;">{{ $item->nomor_pinjam }}</div>
                        <div style="font-size: 0.76rem; color: var(--text-muted);">Kode: {{ $item->kode_aset }} | {{ $item->kategori }}</div>
                    </td>
                    <td data-label="Peminjam" style="padding: 14px 18px; font-size: 0.88rem;">
                        <div style="font-weight: 600; color: var(--text-color);">{{ $item->peminjam_nama }}</div>
                        <span class="badge {{ $item->peminjam_tipe === 'gtk' ? 'badge-info' : ($item->peminjam_tipe === 'siswa' ? 'badge-primary' : 'badge-secondary') }}" style="font-size: 0.7rem; text-transform: uppercase;">
                            {{ $item->peminjam_tipe }}
                        </span>
                    </td>
                    <td data-label="Keperluan" style="padding: 14px 18px; font-size: 0.86rem; color: var(--text-color);">
                        {{ $item->keperluan }}
                    </td>
                    <td data-label="Tanggal" style="padding: 14px 18px; font-size: 0.85rem;">
                        <div>Pinjam: {{ \Carbon\Carbon::parse($item->tanggal_pinjam)->format('d/m/Y') }}</div>
                        <div style="color: {{ $isLate ? '#ef4444; font-weight: 700;' : 'var(--text-muted)' }};">
                            Batas: {{ \Carbon\Carbon::parse($item->tanggal_kembali_rencana)->format('d/m/Y') }}
                            @if ($isLate) <span class="badge badge-danger" style="font-size: 0.65rem;">Terlambat</span> @endif
                        </div>
                        @if ($item->tanggal_kembali_aktual)
                        <div style="font-size: 0.78rem; color: #10b981;">Kembali: {{ \Carbon\Carbon::parse($item->tanggal_kembali_aktual)->format('d/m/Y') }}</div>
                        @endif
                    </td>
                    <td data-label="Status" style="padding: 14px 18px; text-align: center;">
                        @if ($item->status === 'dipinjam')
                            <span class="badge badge-warning">Sedang Dipinjam</span>
                        @else
                            <span class="badge badge-success">Sudah Kembali</span>
                            @if ($item->kondisi_sesudah)
                            <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 3px;">Kondisi: {{ str_replace('_', ' ', $item->kondisi_sesudah) }}</div>
                            @endif
                        @endif
                    </td>
                    <td data-label="Aksi" style="padding: 14px 18px; text-align: right;">
                        <div class="table-actions" style="justify-content: flex-end; gap: 6px;">
                            @if ($item->status === 'dipinjam' && $canUpdate)
                            <button type="button" class="btn btn-sm btn-outline btn-kembalikan"
                                data-id="{{ $item->id }}"
                                data-nomor="{{ $item->nomor_pinjam }}"
                                data-barang="{{ $item->nama_barang }}"
                                title="Catat Pengembalian">
                                <i class="fas fa-box-archive me-1"></i> Kembali
                            </button>
                            @endif
                            @if ($canDelete)
                            <button type="button" class="btn-icon btn-delete-pinjam"
                                data-id="{{ $item->id }}"
                                data-nomor="{{ $item->nomor_pinjam }}"
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
                        <i class="fas fa-folder-open mb-2" style="font-size: 1.8rem; opacity: 0.5;"></i>
                        <div>Belum ada transaksi peminjaman.</div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- 5. Pagination Footer Resmi SAE --}}
    @if ($list->hasPages())
        <div class="custom-pagination">
            @if ($list->onFirstPage())
                <span class="page-btn disabled"><i class="fas fa-chevron-left"></i></span>
            @else
                <a href="{{ $list->previousPageUrl() }}" class="page-btn" title="Sebelumnya"><i class="fas fa-chevron-left"></i></a>
            @endif
            @php
                $cur = $list->currentPage();
                $last = $list->lastPage();
                $from = max(1, $cur - 2);
                $to = min($last, $cur + 2);
            @endphp
            @if ($from > 1)
                <a href="{{ $list->url(1) }}" class="page-btn">1</a>
                @if ($from > 2) <span class="page-info">&hellip;</span> @endif
            @endif
            @for ($i = $from; $i <= $to; $i++)
                <a href="{{ $list->url($i) }}" class="page-btn {{ $i === $cur ? 'current' : '' }}">{{ $i }}</a>
            @endfor
            @if ($to < $last)
                @if ($to < $last - 1) <span class="page-info">&hellip;</span> @endif
                <a href="{{ $list->url($last) }}" class="page-btn">{{ $last }}</a>
            @endif
            @if ($list->hasMorePages())
                <a href="{{ $list->nextPageUrl() }}" class="page-btn" title="Selanjutnya"><i class="fas fa-chevron-right"></i></a>
            @else
                <span class="page-btn disabled"><i class="fas fa-chevron-right"></i></span>
            @endif
        </div>
    @endif
</div>

{{-- Modal Catat Peminjaman --}}
<div id="modalPinjam" class="modal-backdrop" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
    <div class="card" style="width: 100%; max-width: 560px; max-height: 90vh; overflow-y: auto; padding: 24px; border-radius: 12px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="margin: 0; font-size: 1.15rem; font-weight: 700; color: var(--text-color);">
                Catat Peminjaman Sarpras
            </h3>
            <button type="button" id="btnCloseModalPinjam" style="background: none; border: none; font-size: 1.25rem; color: var(--text-muted); cursor: pointer;">&times;</button>
        </div>

        <form id="formPinjam" method="POST" action="{{ route('dashboard.sarpras.peminjaman.store') }}">
            @csrf
            <div class="form-group" style="margin-bottom: 14px;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Pilih Barang / Aset Tersedia <span style="color: #ef4444;">*</span></label>
                <select name="aset_id" id="pinjamAsetId" class="form-control" required>
                    <option value="">-- Pilih Barang --</option>
                    @foreach ($asetTersedia as $ast)
                    <option value="{{ $ast->id }}">{{ $ast->nama_barang }} ({{ $ast->kode_aset }}) - {{ $ast->jumlah }} {{ $ast->satuan }}</option>
                    @endforeach
                </select>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 12px; margin-bottom: 14px;">
                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Tipe Peminjam <span style="color: #ef4444;">*</span></label>
                    <select name="peminjam_tipe" class="form-control" required>
                        <option value="gtk">Guru / GTK</option>
                        <option value="siswa">Peserta Didik</option>
                        <option value="umum">Pihak Luar / Umum</option>
                    </select>
                </div>
                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Nama Lengkap Peminjam <span style="color: #ef4444;">*</span></label>
                    <input type="text" name="peminjam_nama" class="form-control" placeholder="Nama guru / siswa / organisasi" required>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Tanggal Pinjam <span style="color: #ef4444;">*</span></label>
                    <input type="date" name="tanggal_pinjam" value="{{ date('Y-m-d') }}" class="form-control" required>
                </div>
                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Rencana Kembali <span style="color: #ef4444;">*</span></label>
                    <input type="date" name="tanggal_kembali_rencana" value="{{ date('Y-m-d', strtotime('+1 day')) }}" class="form-control" required>
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 14px;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Keperluan Peminjaman <span style="color: #ef4444;">*</span></label>
                <textarea name="keperluan" class="form-control" rows="2" placeholder="Contoh: Kegiatan presentasi KBM di Aula / Lomba OSIS" required></textarea>
            </div>

            <div class="form-group" style="margin-bottom: 20px;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Catatan Tambahan (Opsional)</label>
                <input type="text" name="catatan" class="form-control" placeholder="Kelengkapan kabel adaptor, remote, tas bawaan...">
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 8px;">
                <button type="button" class="btn btn-secondary" id="btnCancelModalPinjam">Batal</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Simpan Peminjaman</button>
            </div>
        </form>
    </div>
</div>

{{-- Modal Catat Pengembalian --}}
<div id="modalKembali" class="modal-backdrop" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
    <div class="card" style="width: 100%; max-width: 480px; padding: 24px; border-radius: 12px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <h3 style="margin: 0; font-size: 1.15rem; font-weight: 700; color: var(--text-color);">
                Konfirmasi Pengembalian
            </h3>
            <button type="button" id="btnCloseModalKembali" style="background: none; border: none; font-size: 1.25rem; color: var(--text-muted); cursor: pointer;">&times;</button>
        </div>

        <form id="formKembali" method="POST" action="">
            @csrf
            <p id="kembaliDesc" style="font-size: 0.88rem; color: var(--text-color); margin-bottom: 16px;"></p>

            <div class="form-group" style="margin-bottom: 14px;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Kondisi Barang Saat Kembali <span style="color: #ef4444;">*</span></label>
                <select name="kondisi_sesudah" class="form-control" required>
                    <option value="baik">Baik (Lengkap &amp; Berfungsi Normal)</option>
                    <option value="rusak_ringan">Rusak Ringan (Perlu Pembersihan / Setting)</option>
                    <option value="rusak_berat">Rusak Berat (Perlu Perbaikan / Komponen Rusak)</option>
                </select>
            </div>

            <div class="form-group" style="margin-bottom: 20px;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Catatan Pengembalian</label>
                <textarea name="catatan" class="form-control" rows="2" placeholder="Catatan kondisi fisik saat diterima kembali..."></textarea>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 8px;">
                <button type="button" class="btn btn-secondary" id="btnCancelModalKembali">Batal</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-check me-1"></i> Selesaikan Pengembalian</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script src="{{ asset('js/sarpras-peminjaman.js') }}"></script>
@endpush
@endsection
