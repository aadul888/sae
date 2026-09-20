@extends('layouts.app')

@section('title', 'Inventaris Aset & Sarana — Sarpras SAE')

@section('content')
<div class="dash-container">
    {{-- 1. Header Banner & Action Button --}}
    <div class="dash-header-block" style="margin-bottom: 24px;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
            <div>
                <h1 style="font-size: 1.4rem; font-weight: 700; color: var(--text-color); margin: 0;">
                    <i class="fas fa-boxes-stacked" style="color: var(--primary); margin-right: 8px;"></i>
                    Inventaris Aset &amp; Sarana
                </h1>
                <p style="font-size: 0.85rem; color: var(--text-muted); margin: 4px 0 0 0;">
                    Pencatatan barang milik sekolah, aset elektronik, mebel, mesin, dan status ketersediaannya.
                </p>
            </div>
            @if ($canCreate)
            <div>
                <button type="button" class="btn btn-primary" id="btnOpenModalAset">
                    <i class="fas fa-plus me-1"></i> Tambah Aset
                </button>
            </div>
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
            <div class="dash-stat-icon" style="background: rgba(16, 185, 129, 0.15); color: #10b981;">
                <i class="fas fa-check-double"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ $statTersedia }}</div>
                <div class="dash-stat-label">Unit Tersedia</div>
            </div>
        </div>
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(59, 130, 246, 0.15); color: #3b82f6;">
                <i class="fas fa-hand-holding-box"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ $statDipinjam }}</div>
                <div class="dash-stat-label">Sedang Dipinjam</div>
            </div>
        </div>
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(239, 68, 68, 0.15); color: #ef4444;">
                <i class="fas fa-wrench"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ $statPerbaikan }}</div>
                <div class="dash-stat-label">Perbaikan / Rusak</div>
            </div>
        </div>
    </div>

    {{-- 3. Toolbar Tabel (LiveSearch & Filter) --}}
    <div class="card" style="padding: 14px 18px; margin-bottom: 20px;">
        <form method="GET" action="{{ route('dashboard.sarpras.aset.index') }}" id="filterForm" style="display: flex; gap: 10px; flex-wrap: wrap; align-items: center;">
            <div style="flex: 1; min-width: 200px; position: relative;">
                <input type="text" name="q" value="{{ $search }}" class="form-control" placeholder="Cari kode aset, nama barang, merk..." style="padding-left: 36px;">
                <i class="fas fa-search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 0.85rem;"></i>
            </div>
            <div style="min-width: 140px;">
                <select name="kategori" class="form-control" onchange="this.form.submit()">
                    <option value="">-- Semua Kategori --</option>
                    @foreach ($kategoriList as $kat)
                    <option value="{{ $kat }}" {{ $kategoriFilter === $kat ? 'selected' : '' }}>{{ $kat }}</option>
                    @endforeach
                </select>
            </div>
            <div style="min-width: 140px;">
                <select name="kondisi" class="form-control" onchange="this.form.submit()">
                    <option value="">-- Semua Kondisi --</option>
                    <option value="baik" {{ $kondisiFilter === 'baik' ? 'selected' : '' }}>Baik</option>
                    <option value="rusak_ringan" {{ $kondisiFilter === 'rusak_ringan' ? 'selected' : '' }}>Rusak Ringan</option>
                    <option value="rusak_berat" {{ $kondisiFilter === 'rusak_berat' ? 'selected' : '' }}>Rusak Berat</option>
                </select>
            </div>
            <div style="min-width: 160px;">
                <select name="ruang_id" class="form-control" onchange="this.form.submit()">
                    <option value="">-- Semua Ruang --</option>
                    @foreach ($ruangList as $r)
                    <option value="{{ $r->id }}" {{ $ruangFilter == $r->id ? 'selected' : '' }}>{{ $r->nama_ruang }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn-secondary" title="Cari"><i class="fas fa-filter"></i></button>
            @if ($search || $kategoriFilter || $kondisiFilter || $ruangFilter)
            <a href="{{ route('dashboard.sarpras.aset.index') }}" class="btn btn-outline" title="Reset"><i class="fas fa-undo"></i></a>
            @endif
        </form>
    </div>

    {{-- 4. Datatable Responsif --}}
    <div class="card table-responsive-stack" id="tableDataContainer" style="padding: 0; margin-bottom: 24px;">
        <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
            <thead>
                <tr style="border-bottom: 1px solid var(--border-color); background: rgba(255, 255, 255, 0.02);">
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">No</th>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Aset / Barang</th>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Kategori &amp; Sumber</th>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Ruang / Lokasi</th>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center;">Jumlah</th>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center;">Kondisi &amp; Status</th>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: right;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($list as $idx => $item)
                <tr style="border-bottom: 1px solid var(--border-color); transition: background 0.2s ease;">
                    <td data-label="No" style="padding: 14px 18px; font-size: 0.88rem; color: var(--text-muted);">
                        {{ $list->firstItem() + $idx }}
                    </td>
                    <td data-label="Aset" style="padding: 14px 18px;">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            @if ($item->foto)
                            <div style="width: 42px; height: 42px; border-radius: 8px; overflow: hidden; border: 1px solid var(--border-color); flex-shrink: 0;">
                                <img src="{{ asset('storage/' . $item->foto) }}" alt="{{ $item->nama_barang }}" style="width: 100%; height: 100%; object-fit: cover;">
                            </div>
                            @else
                            <div style="width: 42px; height: 42px; border-radius: 8px; background: var(--bg-hover); border: 1px solid var(--border-color); display: flex; align-items: center; justify-content: center; color: var(--primary); flex-shrink: 0;">
                                <i class="fas fa-box"></i>
                            </div>
                            @endif
                            <div>
                                <div style="font-weight: 700; color: var(--text-color); font-size: 0.92rem;">{{ $item->nama_barang }}</div>
                                <div style="font-size: 0.78rem; color: var(--primary); font-family: monospace;">{{ $item->kode_aset }}</div>
                                @if ($item->merk_tipe)
                                <div style="font-size: 0.76rem; color: var(--text-muted);">{{ $item->merk_tipe }}</div>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td data-label="Kategori" style="padding: 14px 18px; font-size: 0.88rem;">
                        <span class="badge badge-info" style="font-size: 0.75rem;">{{ $item->kategori }}</span>
                        <div style="font-size: 0.78rem; color: var(--text-muted); margin-top: 3px;">Sumber: {{ $item->sumber_dana }}</div>
                    </td>
                    <td data-label="Lokasi" style="padding: 14px 18px; font-size: 0.88rem;">
                        <div style="font-weight: 600; color: var(--text-color);">{{ $item->nama_ruang ?: 'Belum Ditentukan' }}</div>
                        @if ($item->gedung)
                        <div style="font-size: 0.78rem; color: var(--text-muted);">{{ $item->gedung }}</div>
                        @endif
                    </td>
                    <td data-label="Jumlah" style="padding: 14px 18px; text-align: center; font-size: 0.9rem; font-weight: 700;">
                        {{ $item->jumlah }} {{ $item->satuan }}
                    </td>
                    <td data-label="Status" style="padding: 14px 18px; text-align: center;">
                        <div style="display: flex; flex-direction: column; gap: 4px; align-items: center;">
                            @if ($item->kondisi === 'baik')
                                <span class="badge badge-success" style="font-size: 0.72rem;">Kondisi Baik</span>
                            @elseif ($item->kondisi === 'rusak_ringan')
                                <span class="badge badge-warning" style="font-size: 0.72rem;">Rusak Ringan</span>
                            @else
                                <span class="badge badge-danger" style="font-size: 0.72rem;">Rusak Berat</span>
                            @endif

                            @if ($item->status_ketersediaan === 'tersedia')
                                <span class="badge badge-outline" style="font-size: 0.7rem; border-color: #10b981; color: #10b981;">Tersedia</span>
                            @elseif ($item->status_ketersediaan === 'dipinjam')
                                <span class="badge badge-outline" style="font-size: 0.7rem; border-color: #3b82f6; color: #3b82f6;">Dipinjam</span>
                            @elseif ($item->status_ketersediaan === 'perbaikan')
                                <span class="badge badge-outline" style="font-size: 0.7rem; border-color: #f59e0b; color: #f59e0b;">Perbaikan</span>
                            @else
                                <span class="badge badge-outline" style="font-size: 0.7rem; border-color: #ef4444; color: #ef4444;">Dihapuskan</span>
                            @endif
                        </div>
                    </td>
                    <td data-label="Aksi" style="padding: 14px 18px; text-align: right;">
                        <div class="table-actions" style="justify-content: flex-end; gap: 6px;">
                            @if ($canUpdate)
                            <button type="button" class="btn-icon btn-edit-aset"
                                data-id="{{ $item->id }}"
                                data-kode="{{ $item->kode_aset }}"
                                data-nama="{{ $item->nama_barang }}"
                                data-kategori="{{ $item->kategori }}"
                                data-merk="{{ $item->merk_tipe }}"
                                data-noseri="{{ $item->no_seri_pabrik }}"
                                data-tahun="{{ $item->tahun_perolehan }}"
                                data-sumber="{{ $item->sumber_dana }}"
                                data-harga="{{ $item->harga_perolehan }}"
                                data-kondisi="{{ $item->kondisi }}"
                                data-ruang="{{ $item->ruang_id }}"
                                data-jumlah="{{ $item->jumlah }}"
                                data-satuan="{{ $item->satuan }}"
                                data-status="{{ $item->status_ketersediaan }}"
                                title="Edit Aset">
                                <i class="fas fa-pen-to-square"></i>
                            </button>
                            @endif
                            @if ($canDelete)
                            <button type="button" class="btn-icon btn-delete-aset"
                                data-id="{{ $item->id }}"
                                data-nama="{{ $item->nama_barang }}"
                                title="Hapus Aset" style="color: #ef4444;">
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
                        <div>Belum ada data barang aset yang cocok.</div>
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

{{-- Modal Tambah / Edit Aset --}}
<div id="modalAset" class="modal-backdrop" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
    <div class="card" style="width: 100%; max-width: 620px; max-height: 90vh; overflow-y: auto; padding: 24px; border-radius: 12px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 id="modalAsetTitle" style="margin: 0; font-size: 1.15rem; font-weight: 700; color: var(--text-color);">
                Tambah Aset Sarpras
            </h3>
            <button type="button" id="btnCloseModalAset" style="background: none; border: none; font-size: 1.25rem; color: var(--text-muted); cursor: pointer;">&times;</button>
        </div>

        <form id="formAset" method="POST" action="{{ route('dashboard.sarpras.aset.store') }}" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="_method" id="asetMethod" value="POST">
            <input type="hidden" name="id" id="asetId">

            <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 12px; margin-bottom: 14px;">
                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Kode Aset <span style="color: #ef4444;">*</span></label>
                    <input type="text" name="kode_aset" id="asetKode" class="form-control" placeholder="AST-2026-001" required>
                </div>
                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Nama Barang / Aset <span style="color: #ef4444;">*</span></label>
                    <input type="text" name="nama_barang" id="asetNama" class="form-control" placeholder="Proyektor Epson EB-X500" required>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Kategori <span style="color: #ef4444;">*</span></label>
                    <select name="kategori" id="asetKategori" class="form-control" required>
                        @foreach ($kategoriList as $kat)
                        <option value="{{ $kat }}">{{ $kat }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Merk / Model / Tipe</label>
                    <input type="text" name="merk_tipe" id="asetMerk" class="form-control" placeholder="Epson / Lenovo / Olympic">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Jumlah <span style="color: #ef4444;">*</span></label>
                    <input type="number" name="jumlah" id="asetJumlah" class="form-control" value="1" min="1" required>
                </div>
                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Satuan <span style="color: #ef4444;">*</span></label>
                    <input type="text" name="satuan" id="asetSatuan" class="form-control" value="unit" placeholder="unit/buah/set" required>
                </div>
                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Tahun Beli</label>
                    <input type="number" name="tahun_perolehan" id="asetTahun" class="form-control" value="{{ date('Y') }}" min="1990">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Sumber Dana <span style="color: #ef4444;">*</span></label>
                    <select name="sumber_dana" id="asetSumber" class="form-control" required>
                        <option value="BOS Reguler">BOS Reguler</option>
                        <option value="BOS Kinerja">BOS Kinerja</option>
                        <option value="DAK Fisik">DAK Fisik</option>
                        <option value="Komite / Yayasan">Komite / Yayasan</option>
                        <option value="Hibah / Bantuan">Hibah / Bantuan</option>
                    </select>
                </div>
                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Ruang / Penempatan</label>
                    <select name="ruang_id" id="asetRuang" class="form-control">
                        <option value="">-- Pilih Ruang --</option>
                        @foreach ($ruangList as $r)
                        <option value="{{ $r->id }}">{{ $r->nama_ruang }} ({{ $r->gedung ?: 'Gedung' }})</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Kondisi <span style="color: #ef4444;">*</span></label>
                    <select name="kondisi" id="asetKondisi" class="form-control" required>
                        <option value="baik">Baik</option>
                        <option value="rusak_ringan">Rusak Ringan</option>
                        <option value="rusak_berat">Rusak Berat</option>
                    </select>
                </div>
                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Status Ketersediaan <span style="color: #ef4444;">*</span></label>
                    <select name="status_ketersediaan" id="asetStatus" class="form-control" required>
                        <option value="tersedia">Tersedia</option>
                        <option value="dipinjam">Dipinjam</option>
                        <option value="perbaikan">Dalam Perbaikan</option>
                        <option value="dihapuskan">Dihapuskan</option>
                    </select>
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 20px;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Foto Aset / Barang (Opsional)</label>
                <input type="file" name="foto" id="asetFoto" class="form-control" accept="image/*">
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 8px;">
                <button type="button" class="btn btn-secondary" id="btnCancelModalAset">Batal</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Simpan Aset</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script src="{{ asset('js/sarpras-aset.js') }}"></script>
@endpush
@endsection
