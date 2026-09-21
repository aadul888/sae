@extends('layouts.dashboard')

@section('title', 'Inventaris Alat & Bahan — Laboratorium SAE')
@section('dash_title', 'Inventaris Lab')

@section('content')
<div class="dash-content-inner">
    {{-- 1. Header Banner & Action Button --}}
    <div class="dash-banner" style="display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; margin-bottom: 20px;">
        <div style="display: flex; align-items: center; gap: 14px;">
            <div style="width: 48px; height: 48px; border-radius: 12px; background: rgba(59,130,246,0.12); color: #3b82f6; display: flex; align-items: center; justify-content: center; font-size: 1.35rem; flex-shrink: 0;">
                <i class="fas fa-flask"></i>
            </div>
            <div>
                <h2 style="font-size: 1.3rem; font-weight: 800; color: var(--text-color); margin: 0;">
                    Inventaris Alat &amp; Bahan Lab
                </h2>
                <div style="font-size: 0.82rem; color: var(--text-muted); margin-top: 2px;">
                    Pengelolaan alat praktikum, bahan habis pakai, stok, dan kontrol masa kadaluarsa laboratorium.
                </div>
            </div>
        </div>
        <div class="dash-banner-actions" style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
            @if ($canCreate)
                <button type="button" class="btn btn-primary" id="btnOpenModalLab" style="display: inline-flex; align-items: center; gap: 8px; padding: 8px 16px; border-radius: 8px; font-weight: 600;">
                    <i class="fas fa-plus"></i> Tambah Alat / Bahan
                </button>
            @endif
        </div>
    </div>

    {{-- 2. Stat Grid (4 Kartu Ringkasan) --}}
    <div class="dash-stat-grid" style="margin-bottom: 24px; gap: 14px;">
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(99, 102, 241, 0.15); color: var(--primary);">
                <i class="fas fa-boxes-stacked"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ $statTotal }}</div>
                <div class="dash-stat-label">Total Item Lab</div>
            </div>
        </div>
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(16, 185, 129, 0.15); color: #10b981;">
                <i class="fas fa-microscope"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ $statAlat }}</div>
                <div class="dash-stat-label">Alat Praktikum</div>
            </div>
        </div>
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(59, 130, 246, 0.15); color: #3b82f6;">
                <i class="fas fa-vial"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ $statBahan }}</div>
                <div class="dash-stat-label">Bahan Habis Pakai</div>
            </div>
        </div>
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(239, 68, 68, 0.15); color: #ef4444;">
                <i class="fas fa-triangle-exclamation"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ $statKritis }}</div>
                <div class="dash-stat-label">Stok Kritis / Rusak</div>
            </div>
        </div>
    </div>

    {{-- 3. Toolbar Tabel (LiveSearch & Filter) --}}
    <div class="card" style="padding: 14px 18px; margin-bottom: 20px;">
        <form method="GET" action="{{ route('dashboard.laboran.inventaris.index') }}" id="filterForm" style="display: flex; gap: 10px; flex-wrap: wrap; align-items: center;">
            <div style="flex: 1; min-width: 200px; position: relative;">
                <input type="text" name="q" value="{{ $search }}" class="form-control" placeholder="Cari nama item, kode, lemari rak..." style="padding-left: 36px;">
                <i class="fas fa-search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 0.85rem;"></i>
            </div>
            <div style="min-width: 180px;">
                <select name="lab" class="form-control" onchange="this.form.submit()">
                    <option value="">-- Semua Ruang (Sarpras) --</option>
                    @if(isset($daftarRuangSarpras) && $daftarRuangSarpras->isNotEmpty())
                        @foreach ($daftarRuangSarpras as $r)
                        <option value="{{ $r->nama_ruang }}" {{ $labFilter === $r->nama_ruang ? 'selected' : '' }}>
                            {{ $r->nama_ruang }} {{ $r->gedung ? "({$r->gedung})" : '' }}
                        </option>
                        @endforeach
                    @else
                        @foreach ($daftarLab as $lab)
                        <option value="{{ $lab }}" {{ $labFilter === $lab ? 'selected' : '' }}>{{ $lab }}</option>
                        @endforeach
                    @endif
                </select>
            </div>
            <div style="min-width: 140px;">
                <select name="jenis" class="form-control" onchange="this.form.submit()">
                    <option value="">-- Semua Jenis --</option>
                    <option value="alat" {{ $jenisFilter === 'alat' ? 'selected' : '' }}>Alat</option>
                    <option value="bahan_habis_pakai" {{ $jenisFilter === 'bahan_habis_pakai' ? 'selected' : '' }}>Bahan Habis Pakai</option>
                </select>
            </div>
            <div style="min-width: 140px;">
                <select name="kondisi" class="form-control" onchange="this.form.submit()">
                    <option value="">-- Semua Kondisi --</option>
                    <option value="baik" {{ $kondisiFilter === 'baik' ? 'selected' : '' }}>Baik</option>
                    <option value="rusak_ringan" {{ $kondisiFilter === 'rusak_ringan' ? 'selected' : '' }}>Rusak Ringan</option>
                    <option value="rusak_berat" {{ $kondisiFilter === 'rusak_berat' ? 'selected' : '' }}>Rusak Berat</option>
                    <option value="kedaluwarsa" {{ $kondisiFilter === 'kedaluwarsa' ? 'selected' : '' }}>Kedaluwarsa</option>
                </select>
            </div>
            <button type="submit" class="btn btn-secondary" title="Cari"><i class="fas fa-filter"></i></button>
            @if ($search || $labFilter || $jenisFilter || $kondisiFilter)
            <a href="{{ route('dashboard.laboran.inventaris.index') }}" class="btn btn-outline" title="Reset"><i class="fas fa-undo"></i></a>
            @endif
        </form>
    </div>

    {{-- 4. Datatable Responsif --}}
    <div class="card table-responsive-stack" id="tableDataContainer" style="padding: 0; margin-bottom: 24px;">
        <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
            <thead>
                <tr style="border-bottom: 1px solid var(--border-color); background: rgba(255, 255, 255, 0.02);">
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">No</th>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Kode &amp; Nama Item</th>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Ruang Lab &amp; Rak</th>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Jenis &amp; Spek</th>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center;">Stok</th>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center;">Kondisi</th>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: right;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($list as $idx => $item)
                @php
                    $isLow = $item->stok_tersedia <= 2;
                @endphp
                <tr style="border-bottom: 1px solid var(--border-color); transition: background 0.2s ease;">
                    <td data-label="No" style="padding: 14px 18px; font-size: 0.88rem; color: var(--text-muted);">
                        {{ $list->firstItem() + $idx }}
                    </td>
                    <td data-label="Item" style="padding: 14px 18px;">
                        <div style="font-weight: 700; color: var(--text-color); font-size: 0.92rem;">{{ $item->nama_item }}</div>
                        <div style="font-size: 0.78rem; color: var(--primary); font-family: monospace;">{{ $item->kode_item }}</div>
                        @if ($item->tgl_kedaluwarsa)
                        <div style="font-size: 0.75rem; color: #f59e0b;">Exp: {{ \Carbon\Carbon::parse($item->tgl_kedaluwarsa)->format('d/m/Y') }}</div>
                        @endif
                    </td>
                    <td data-label="Lab" style="padding: 14px 18px; font-size: 0.88rem;">
                        <div style="font-weight: 600; color: var(--text-color);">{{ $item->ruang_lab_nama }}</div>
                        <div style="font-size: 0.78rem; color: var(--text-muted);">Rak: {{ $item->lokasi_lemari_rak ?: '-' }}</div>
                    </td>
                    <td data-label="Jenis" style="padding: 14px 18px; font-size: 0.85rem;">
                        <span class="badge {{ $item->jenis === 'alat' ? 'badge-info' : 'badge-primary' }}" style="font-size: 0.72rem;">
                            {{ $item->jenis === 'alat' ? 'Alat' : 'Bahan Habis Pakai' }}
                        </span>
                        @if ($item->spesifikasi)
                        <div style="font-size: 0.76rem; color: var(--text-muted); margin-top: 3px; max-width: 200px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $item->spesifikasi }}">
                            {{ $item->spesifikasi }}
                        </div>
                        @endif
                    </td>
                    <td data-label="Stok" style="padding: 14px 18px; text-align: center;">
                        <div style="font-weight: 700; font-size: 0.95rem; color: {{ $isLow ? '#ef4444;' : 'var(--text-color)' }};">
                            {{ (float)$item->stok_tersedia }} / {{ (float)$item->stok_total }} {{ $item->satuan }}
                        </div>
                        @if ($isLow)
                        <span class="badge badge-danger" style="font-size: 0.65rem;">Stok Menipis</span>
                        @endif
                    </td>
                    <td data-label="Kondisi" style="padding: 14px 18px; text-align: center;">
                        @if ($item->kondisi === 'baik')
                            <span class="badge badge-success">Baik</span>
                        @elseif ($item->kondisi === 'rusak_ringan')
                            <span class="badge badge-warning">Rusak Ringan</span>
                        @elseif ($item->kondisi === 'rusak_berat')
                            <span class="badge badge-danger">Rusak Berat</span>
                        @else
                            <span class="badge badge-danger">Kedaluwarsa</span>
                        @endif
                    </td>
                    <td data-label="Aksi" style="padding: 14px 18px; text-align: right;">
                        <div class="table-actions" style="justify-content: flex-end; gap: 6px;">
                            @if ($canUpdate)
                            <button type="button" class="btn-icon btn-edit-lab"
                                data-id="{{ $item->id }}"
                                data-lab="{{ $item->ruang_lab_nama }}"
                                data-kode="{{ $item->kode_item }}"
                                data-nama="{{ $item->nama_item }}"
                                data-jenis="{{ $item->jenis }}"
                                data-stoktotal="{{ (float)$item->stok_total }}"
                                data-stoktersedia="{{ (float)$item->stok_tersedia }}"
                                data-satuan="{{ $item->satuan }}"
                                data-kondisi="{{ $item->kondisi }}"
                                data-spek="{{ $item->spesifikasi }}"
                                data-rak="{{ $item->lokasi_lemari_rak }}"
                                data-exp="{{ $item->tgl_kedaluwarsa }}"
                                title="Edit Item">
                                <i class="fas fa-pen-to-square"></i>
                            </button>
                            @endif
                            @if ($canDelete)
                            <button type="button" class="btn-icon btn-delete-lab"
                                data-id="{{ $item->id }}"
                                data-nama="{{ $item->nama_item }}"
                                title="Hapus Item" style="color: #ef4444;">
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
                        <div>Belum ada data alat/bahan lab yang cocok.</div>
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

{{-- Modal Tambah / Edit Alat Bahan Lab --}}
<div id="modalLab" class="modal-backdrop" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
    <div class="card" style="width: 100%; max-width: 580px; max-height: 90vh; overflow-y: auto; padding: 24px; border-radius: 12px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 id="modalLabTitle" style="margin: 0; font-size: 1.15rem; font-weight: 700; color: var(--text-color);">
                Tambah Alat / Bahan Lab
            </h3>
            <button type="button" id="btnCloseModalLab" style="background: none; border: none; font-size: 1.25rem; color: var(--text-muted); cursor: pointer;">&times;</button>
        </div>

        <form id="formLab" method="POST" action="{{ route('dashboard.laboran.inventaris.store') }}">
            @csrf
            <input type="hidden" name="_method" id="labMethod" value="POST">
            <input type="hidden" name="id" id="labId">

            @if(isset($daftarAsetSarpras) && $daftarAsetSarpras->isNotEmpty())
            <div id="wrapperAsetSarpras" style="margin-bottom: 14px; padding: 10px 12px; background: rgba(99,102,241,0.06); border: 1px dashed rgba(99,102,241,0.3); border-radius: 8px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                    <label style="font-size: 0.82rem; font-weight: 600; color: var(--primary); margin: 0;">
                        <i class="fas fa-boxes-stacked me-1"></i> Referensi / Salin dari Aset Sarpras (Opsional)
                    </label>
                    <small style="color: var(--text-muted); font-size: 0.72rem;">Isi otomatis data</small>
                </div>
                <select id="selectAsetSarpras" class="form-control" style="font-size: 0.82rem; height: 36px;">
                    <option value="">-- Ketik Baru atau Pilih Referensi Aset Sarpras --</option>
                    @foreach($daftarAsetSarpras as $ast)
                    <option value="{{ $ast->id }}"
                            data-nama="{{ $ast->nama_barang }}"
                            data-kode="{{ $ast->kode_aset }}"
                            data-ruang="{{ $ast->ruang_nama }}"
                            data-satuan="{{ $ast->satuan }}"
                            data-kondisi="{{ $ast->kondisi }}"
                            data-spek="{{ $ast->merk_tipe ? 'Merk/Tipe: ' . $ast->merk_tipe : '' }}">
                        {{ $ast->nama_barang }} &bull; {{ $ast->kode_aset }} (Lokasi: {{ $ast->ruang_nama ?: 'Sarpras' }})
                    </option>
                    @endforeach
                </select>
            </div>
            @endif

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                <div>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                        <label style="font-size: 0.85rem; font-weight: 600; color: var(--text-color); margin: 0;">
                            Ruang Lab / Bengkel <span style="color: #ef4444;">*</span>
                        </label>
                        <a href="{{ route('dashboard.sarpras.ruang.index') }}" target="_blank" style="font-size: 0.72rem; color: var(--primary); text-decoration: none;" title="Kelola Ruang di Sarpras & Aset">
                            <i class="fas fa-arrow-up-right-from-square me-1"></i> Data Sarpras
                        </a>
                    </div>
                    <select name="ruang_lab_nama" id="labRuang" class="form-control" required>
                        <option value="">-- Pilih Ruang dari Sarpras &amp; Aset --</option>
                        @if(isset($daftarRuangSarpras) && $daftarRuangSarpras->isNotEmpty())
                            @foreach ($daftarRuangSarpras as $r)
                            <option value="{{ $r->nama_ruang }}">
                                {{ $r->nama_ruang }} &bull; {{ $r->gedung ?: 'Sarpras' }} {{ $r->lantai ? '(Lt. ' . $r->lantai . ')' : '' }}
                            </option>
                            @endforeach
                        @else
                            @foreach ($daftarLab as $l)
                            <option value="{{ $l }}">{{ $l }}</option>
                            @endforeach
                        @endif
                    </select>
                    <div style="font-size: 0.71rem; color: var(--text-muted); margin-top: 4px;">
                        <i class="fas fa-link me-1 text-primary"></i> Terhubung langsung dengan <strong>Ruang &amp; Gedung Sarpras</strong>.
                    </div>
                </div>
                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Jenis Item <span style="color: #ef4444;">*</span></label>
                    <select name="jenis" id="labJenis" class="form-control" required>
                        <option value="alat">Alat Praktikum</option>
                        <option value="bahan_habis_pakai">Bahan Habis Pakai</option>
                    </select>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 12px; margin-bottom: 14px;">
                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Kode Item <span style="color: #ef4444;">*</span></label>
                    <input type="text" name="kode_item" id="labKode" class="form-control" placeholder="LAB-IPA-001" required>
                </div>
                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Nama Alat / Bahan <span style="color: #ef4444;">*</span></label>
                    <input type="text" name="nama_item" id="labNama" class="form-control" placeholder="Mikroskop Binokuler / Asam Klorida" required>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Stok Total <span style="color: #ef4444;">*</span></label>
                    <input type="number" step="0.01" name="stok_total" id="labStokTotal" class="form-control" value="1" required>
                </div>
                <div id="wrapperStokTersedia" style="display: none;">
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Stok Tersedia</label>
                    <input type="number" step="0.01" name="stok_tersedia" id="labStokTersedia" class="form-control" value="1">
                </div>
                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Satuan <span style="color: #ef4444;">*</span></label>
                    <input type="text" name="satuan" id="labSatuan" class="form-control" value="unit" placeholder="unit/botol/pcs" required>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Kondisi <span style="color: #ef4444;">*</span></label>
                    <select name="kondisi" id="labKondisi" class="form-control" required>
                        <option value="baik">Baik</option>
                        <option value="rusak_ringan">Rusak Ringan</option>
                        <option value="rusak_berat">Rusak Berat</option>
                        <option value="kedaluwarsa">Kedaluwarsa</option>
                    </select>
                </div>
                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Tanggal Kedaluwarsa</label>
                    <input type="date" name="tgl_kedaluwarsa" id="labExp" class="form-control">
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 14px;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Lokasi Lemari / Rak</label>
                <input type="text" name="lokasi_lemari_rak" id="labRak" class="form-control" placeholder="Lemari A - Rak 2">
            </div>

            <div class="form-group" style="margin-bottom: 20px;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Spesifikasi / Keterangan</label>
                <textarea name="spesifikasi" id="labSpek" class="form-control" rows="2" placeholder="Merk, ukuran, konsentrasi bahan..."></textarea>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 8px;">
                <button type="button" class="btn btn-secondary" id="btnCancelModalLab">Batal</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Simpan Item</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script src="{{ asset('js/laboran-inventaris.js') }}"></script>
@endpush
@endsection
