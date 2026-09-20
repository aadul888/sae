@extends('layouts.app')

@section('title', 'Data Ruang & Gedung — Sarpras SAE')

@section('content')
<div class="dash-container">
    {{-- 1. Header Banner & Action Button --}}
    <div class="dash-header-block" style="margin-bottom: 24px;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
            <div>
                <h1 style="font-size: 1.4rem; font-weight: 700; color: var(--text-color); margin: 0;">
                    <i class="fas fa-door-open" style="color: var(--primary); margin-right: 8px;"></i>
                    Data Ruang &amp; Gedung
                </h1>
                <p style="font-size: 0.85rem; color: var(--text-muted); margin: 4px 0 0 0;">
                    Pemetaan gedung, ruang kelas, laboratorium, dan kondisi fisik fasilitas sekolah.
                </p>
            </div>
            @if ($canCreate)
            <div>
                <button type="button" class="btn btn-primary" id="btnOpenModalRuang">
                    <i class="fas fa-plus me-1"></i> Tambah Ruang
                </button>
            </div>
            @endif
        </div>
    </div>

    {{-- 2. Stat Grid (4 Kartu Ringkasan) --}}
    <div class="dash-stat-grid" style="margin-bottom: 24px; gap: 14px;">
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(99, 102, 241, 0.15); color: var(--primary);">
                <i class="fas fa-building"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ $statTotal }}</div>
                <div class="dash-stat-label">Total Ruangan</div>
            </div>
        </div>
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(16, 185, 129, 0.15); color: #10b981;">
                <i class="fas fa-circle-check"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ $statBaik }}</div>
                <div class="dash-stat-label">Kondisi Baik</div>
            </div>
        </div>
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b;">
                <i class="fas fa-triangle-exclamation"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ $statRusakRingan }}</div>
                <div class="dash-stat-label">Rusak Ringan</div>
            </div>
        </div>
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(239, 68, 68, 0.15); color: #ef4444;">
                <i class="fas fa-circle-xmark"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ $statRusakBerat }}</div>
                <div class="dash-stat-label">Rusak Berat</div>
            </div>
        </div>
    </div>

    {{-- 3. Toolbar Tabel (LiveSearch & Filter) --}}
    <div class="card" style="padding: 14px 18px; margin-bottom: 20px;">
        <form method="GET" action="{{ route('dashboard.sarpras.ruang.index') }}" id="filterForm" style="display: flex; gap: 10px; flex-wrap: wrap; align-items: center;">
            <div style="flex: 1; min-width: 200px; position: relative;">
                <input type="text" name="q" value="{{ $search }}" class="form-control" placeholder="Cari kode ruang, nama, gedung, atau PJ..." style="padding-left: 36px;">
                <i class="fas fa-search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 0.85rem;"></i>
            </div>
            <div style="min-width: 150px;">
                <select name="gedung" class="form-control" onchange="this.form.submit()">
                    <option value="">-- Semua Gedung --</option>
                    @foreach ($gedungList as $g)
                    <option value="{{ $g }}" {{ $gedungFilter === $g ? 'selected' : '' }}>{{ $g }}</option>
                    @endforeach
                </select>
            </div>
            <div style="min-width: 150px;">
                <select name="kondisi" class="form-control" onchange="this.form.submit()">
                    <option value="">-- Semua Kondisi --</option>
                    <option value="baik" {{ $kondisiFilter === 'baik' ? 'selected' : '' }}>Baik</option>
                    <option value="rusak_ringan" {{ $kondisiFilter === 'rusak_ringan' ? 'selected' : '' }}>Rusak Ringan</option>
                    <option value="rusak_berat" {{ $kondisiFilter === 'rusak_berat' ? 'selected' : '' }}>Rusak Berat</option>
                </select>
            </div>
            <button type="submit" class="btn btn-secondary" title="Cari"><i class="fas fa-filter"></i></button>
            @if ($search || $gedungFilter || $kondisiFilter)
            <a href="{{ route('dashboard.sarpras.ruang.index') }}" class="btn btn-outline" title="Reset"><i class="fas fa-undo"></i></a>
            @endif
        </form>
    </div>

    {{-- 4. Datatable Responsif --}}
    <div class="card table-responsive-stack" id="tableDataContainer" style="padding: 0; margin-bottom: 24px;">
        <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
            <thead>
                <tr style="border-bottom: 1px solid var(--border-color); background: rgba(255, 255, 255, 0.02);">
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">No</th>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Kode &amp; Nama Ruang</th>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Gedung &amp; Lantai</th>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Penanggung Jawab</th>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center;">Kondisi</th>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: right;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($list as $idx => $item)
                <tr style="border-bottom: 1px solid var(--border-color); transition: background 0.2s ease;">
                    <td data-label="No" style="padding: 14px 18px; font-size: 0.88rem; color: var(--text-muted);">
                        {{ $list->firstItem() + $idx }}
                    </td>
                    <td data-label="Ruang" style="padding: 14px 18px;">
                        <div style="font-weight: 700; color: var(--text-color); font-size: 0.92rem;">{{ $item->nama_ruang }}</div>
                        <div style="font-size: 0.78rem; color: var(--primary); font-family: monospace;">{{ $item->kode_ruang }}</div>
                    </td>
                    <td data-label="Gedung" style="padding: 14px 18px; font-size: 0.88rem; color: var(--text-color);">
                        <div><i class="fas fa-landmark text-muted me-1"></i> {{ $item->gedung ?: '-' }}</div>
                        <div style="font-size: 0.78rem; color: var(--text-muted);">Lantai: {{ $item->lantai ?: '1' }}</div>
                    </td>
                    <td data-label="PJ" style="padding: 14px 18px; font-size: 0.88rem;">
                        <div style="font-weight: 600; color: var(--text-color);">{{ $item->pj_nama ?: '-' }}</div>
                        @if ($item->pj_nip)
                        <div style="font-size: 0.78rem; color: var(--text-muted);">NIP: {{ $item->pj_nip }}</div>
                        @endif
                    </td>
                    <td data-label="Kondisi" style="padding: 14px 18px; text-align: center;">
                        @if ($item->kondisi === 'baik')
                            <span class="badge badge-success">Baik</span>
                        @elseif ($item->kondisi === 'rusak_ringan')
                            <span class="badge badge-warning">Rusak Ringan</span>
                        @else
                            <span class="badge badge-danger">Rusak Berat</span>
                        @endif
                    </td>
                    <td data-label="Aksi" style="padding: 14px 18px; text-align: right;">
                        <div class="table-actions" style="justify-content: flex-end; gap: 6px;">
                            @if ($canUpdate)
                            <button type="button" class="btn-icon btn-edit-ruang"
                                data-id="{{ $item->id }}"
                                data-kode="{{ $item->kode_ruang }}"
                                data-nama="{{ $item->nama_ruang }}"
                                data-gedung="{{ $item->gedung }}"
                                data-lantai="{{ $item->lantai }}"
                                data-pj="{{ $item->penanggung_jawab_ptk_id }}"
                                data-kondisi="{{ $item->kondisi }}"
                                data-keterangan="{{ $item->keterangan }}"
                                title="Edit Ruang">
                                <i class="fas fa-pen-to-square"></i>
                            </button>
                            @endif
                            @if ($canDelete)
                            <button type="button" class="btn-icon btn-delete-ruang"
                                data-id="{{ $item->id }}"
                                data-nama="{{ $item->nama_ruang }}"
                                title="Hapus Ruang" style="color: #ef4444;">
                                <i class="fas fa-trash-can"></i>
                            </button>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" style="padding: 30px; text-align: center; color: var(--text-muted);">
                        <i class="fas fa-folder-open mb-2" style="font-size: 1.8rem; opacity: 0.5;"></i>
                        <div>Belum ada data ruang yang cocok.</div>
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

{{-- Modal Tambah / Edit Ruang --}}
<div id="modalRuang" class="modal-backdrop" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
    <div class="card" style="width: 100%; max-width: 550px; max-height: 90vh; overflow-y: auto; padding: 24px; border-radius: 12px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 id="modalRuangTitle" style="margin: 0; font-size: 1.15rem; font-weight: 700; color: var(--text-color);">
                Tambah Data Ruang
            </h3>
            <button type="button" id="btnCloseModalRuang" style="background: none; border: none; font-size: 1.25rem; color: var(--text-muted); cursor: pointer;">&times;</button>
        </div>

        <form id="formRuang" method="POST" action="{{ route('dashboard.sarpras.ruang.store') }}">
            @csrf
            <input type="hidden" name="_method" id="ruangMethod" value="POST">
            <input type="hidden" name="id" id="ruangId">

            <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 12px; margin-bottom: 14px;">
                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Kode Ruang <span style="color: #ef4444;">*</span></label>
                    <input type="text" name="kode_ruang" id="ruangKode" class="form-control" placeholder="R-01" required>
                </div>
                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Nama Ruang <span style="color: #ef4444;">*</span></label>
                    <input type="text" name="nama_ruang" id="ruangNama" class="form-control" placeholder="Ruang Kelas X TKJ 1" required>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Gedung</label>
                    <input type="text" name="gedung" id="ruangGedung" class="form-control" placeholder="Gedung A">
                </div>
                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Lantai</label>
                    <input type="text" name="lantai" id="ruangLantai" class="form-control" placeholder="Lantai 1">
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 14px;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Penanggung Jawab (GTK)</label>
                <select name="penanggung_jawab_ptk_id" id="ruangPj" class="form-control">
                    <option value="">-- Pilih Penanggung Jawab --</option>
                    @foreach ($allGtk as $g)
                    <option value="{{ $g->ptk_id }}">{{ $g->nama }} ({{ $g->nip ?: 'Non-NIP' }})</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group" style="margin-bottom: 14px;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Kondisi Ruang <span style="color: #ef4444;">*</span></label>
                <select name="kondisi" id="ruangKondisi" class="form-control" required>
                    <option value="baik">Baik</option>
                    <option value="rusak_ringan">Rusak Ringan</option>
                    <option value="rusak_berat">Rusak Berat</option>
                </select>
            </div>

            <div class="form-group" style="margin-bottom: 20px;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Keterangan / Fasilitas Ruang</label>
                <textarea name="keterangan" id="ruangKeterangan" class="form-control" rows="2" placeholder="Luas ruang, kelengkapan proyektor, AC, kapasitas kursi..."></textarea>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 8px;">
                <button type="button" class="btn btn-secondary" id="btnCancelModalRuang">Batal</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Simpan Data</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script src="{{ asset('js/sarpras-ruang.js') }}"></script>
@endpush
@endsection
