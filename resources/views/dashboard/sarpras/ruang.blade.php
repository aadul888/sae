@extends('layouts.dashboard')

@section('title', 'Data Ruang & Gedung — Sarpras SAE')
@section('dash_title', 'Ruang & Gedung')

@section('content')
    @include('dashboard.laboran.partials.header-actions')

    <div class="dash-content-inner">
        {{-- 1. Header Banner & Action Button --}}
        <div class="dash-banner"
            style="display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; margin-bottom: 20px;">
            <div style="display: flex; align-items: center; gap: 14px;">
                <div
                    style="width: 48px; height: 48px; border-radius: var(--radius-lg, 12px); background: rgba(59,130,246,0.12); color: var(--primary, #3b82f6); display: flex; align-items: center; justify-content: center; font-size: var(--text-2xl, 1.5rem); flex-shrink: 0;">
                    <i class="fas fa-door-open"></i>
                </div>
                <div>
                    <h2 style="font-size: var(--text-xl, 1.25rem); font-weight: 800; color: var(--text-color); margin: 0;">
                        Data Ruang &amp; Gedung
                    </h2>
                    <div style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); margin-top: 2px;">
                        Pemetaan gedung, ruang kelas, laboratorium, dan kondisi fisik fasilitas sekolah.
                    </div>
                </div>
            </div>
            <div class="dash-banner-actions dash-banner-access">
                <a href="{{ route('dashboard.sarpras.ruang.export', request()->only(['q', 'gedung', 'kondisi'])) }}"
                    class="btn btn-outline"
                    title="Unduh daftar Ruang &amp; Gedung sebagai Excel sesuai filter"
                    aria-label="Unduh Excel">
                    <i class="fas fa-file-excel text-success"></i>
                </a>
                @if ($canCreate)
                    <button type="button" class="btn btn-primary" id="btnOpenModalRuang"
                        title="Tambah Ruangan Baru" aria-label="Tambah Ruangan Baru">
                        <i class="fas fa-plus"></i>
                    </button>
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
                <div class="dash-stat-icon" style="background: rgba(16, 185, 129, 0.15); color: var(--success, #10b981);">
                    <i class="fas fa-circle-check"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ $statBaik }}</div>
                    <div class="dash-stat-label">Kondisi Baik</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(245, 158, 11, 0.15); color: var(--warning, #f59e0b);">
                    <i class="fas fa-triangle-exclamation"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ $statRusakRingan }}</div>
                    <div class="dash-stat-label">Rusak Ringan</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(239, 68, 68, 0.15); color: var(--danger, #ef4444);">
                    <i class="fas fa-circle-xmark"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ $statRusakBerat }}</div>
                    <div class="dash-stat-label">Rusak Berat</div>
                </div>
            </div>
        </div>

        {{-- Unified Datatable Ruang & Gedung (Toolbar + Table + Pagination dalam 1 Card Terpadu) --}}
        <div class="card datatable-card-wrapper" id="tableDataContainer">
            <!-- 1. Toolbar Header (Entries, Filters & Live Search) -->
            <div class="datatable-toolbar">
                <form method="GET" action="{{ route('dashboard.sarpras.ruang.index') }}" id="filterForm"
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

                            <select name="gedung" id="filterGedung" class="toolbar-filter-select" style="height: 38px;">
                                <option value="">Semua Gedung</option>
                                @foreach ($gedungList as $g)
                                    <option value="{{ $g }}" {{ $gedungFilter === $g ? 'selected' : '' }}>
                                        {{ $g }}</option>
                                @endforeach
                            </select>

                            <select name="kondisi" id="filterKondisi" class="toolbar-filter-select" style="height: 38px;">
                                <option value="">Semua Kondisi</option>
                                <option value="baik" {{ $kondisiFilter === 'baik' ? 'selected' : '' }}>Baik</option>
                                <option value="rusak_ringan" {{ $kondisiFilter === 'rusak_ringan' ? 'selected' : '' }}>
                                    Rusak Ringan
                                </option>
                                <option value="rusak_berat" {{ $kondisiFilter === 'rusak_berat' ? 'selected' : '' }}>Rusak
                                    Berat
                                </option>
                            </select>

                            @if ($search || $gedungFilter || $kondisiFilter)
                                <a href="{{ route('dashboard.sarpras.ruang.index') }}"
                                    class="btn btn-outline btn-reset-filter"
                                    style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px);"
                                    title="Reset filter" data-live-reset><i class="fas fa-undo"></i></a>
                            @endif
                        </div>

                        <div class="live-search-wrap" style="height: 38px;">
                            <i class="fas fa-search search-icon"></i>
                            <input type="text" name="q" id="liveSearch" value="{{ $search }}"
                                placeholder="Cari kode ruang, nama, gedung, atau PJ..." autocomplete="off">
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
                                Kode &amp; Nama Ruang</th>
                            <th
                                style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Gedung &amp; Lantai</th>
                            <th
                                style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Penanggung Jawab</th>
                            <th
                                style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center;">
                                Kondisi</th>
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
                                <td data-label="Ruang" style="padding: 14px 18px;">
                                    <div style="font-weight: 700; color: var(--text-color); font-size: var(--text-base, 0.875rem);">
                                        {{ $item->nama_ruang }}</div>
                                    <div style="font-size: var(--text-sm, 0.8125rem); color: var(--primary); font-family: monospace;">
                                        {{ $item->kode_ruang }}</div>
                                </td>
                                <td data-label="Gedung"
                                    style="padding: 14px 18px; font-size: var(--text-base, 0.875rem); color: var(--text-color);">
                                    <div><i class="fas fa-landmark text-muted me-1"></i> {{ $item->gedung ?: '-' }}</div>
                                    <div style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted);">Lantai:
                                        {{ $item->lantai ?: '1' }}</div>
                                    @if ($item->panjang || $item->lebar || $item->luas)
                                        <div style="font-size: var(--text-xs, 0.75rem); color: var(--primary, #3b82f6); margin-top: 3px;" title="Ukuran &amp; Luas Ruang">
                                            <i class="fas fa-ruler-combined me-1"></i>
                                            @if ($item->panjang && $item->lebar)
                                                {{ (float) $item->panjang }}m × {{ (float) $item->lebar }}m
                                            @endif
                                            @if ($item->luas)
                                                ({{ (float) $item->luas }} m²)
                                            @endif
                                        </div>
                                    @endif
                                </td>
                                <td data-label="PJ" style="padding: 14px 18px; font-size: var(--text-base, 0.875rem);">
                                    <div style="font-weight: 600; color: var(--text-color);">{{ $item->pj_nama ?: '-' }}
                                    </div>
                                    @if ($item->pj_nip)
                                        <div style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted);">NIP:
                                            {{ $item->pj_nip }}
                                        </div>
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
                                                data-id="{{ $item->id }}" data-kode="{{ $item->kode_ruang }}"
                                                data-nama="{{ $item->nama_ruang }}" data-gedung="{{ $item->gedung }}"
                                                data-lantai="{{ $item->lantai }}"
                                                data-panjang="{{ $item->panjang }}"
                                                data-lebar="{{ $item->lebar }}"
                                                data-luas="{{ $item->luas }}"
                                                data-pj="{{ $item->penanggung_jawab_ptk_id }}"
                                                data-kondisi="{{ $item->kondisi }}"
                                                data-keterangan="{{ $item->keterangan }}" title="Edit Ruang">
                                                <i class="fas fa-pen-to-square"></i>
                                            </button>
                                        @endif
                                        @if ($canDelete)
                                            <button type="button" class="btn-icon btn-delete-ruang"
                                                data-id="{{ $item->id }}" data-nama="{{ $item->nama_ruang }}"
                                                title="Hapus Ruang" style="color: var(--danger, #ef4444);">
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
                                    <div>Belum ada data ruang yang cocok.</div>
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

        {{-- Modal Tambah / Edit Ruang --}}
        <div id="modalRuang" class="modal-backdrop">
            <div class="card" style="max-width: 550px; max-height: 90vh;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 12px; border-bottom: 1px solid var(--border-color);">
                    <h3 id="modalRuangTitle"
                        style="margin: 0; font-size: var(--text-xl, 1.25rem); font-weight: 700; color: var(--text-color);">
                        Tambah Data Ruang
                    </h3>
                    <button type="button" id="btnCloseModalRuang" aria-label="Tutup"
                        style="background: none; border: none; font-size: var(--text-xl, 1.25rem); color: var(--text-muted); cursor: pointer;">&times;</button>
                </div>

                <form id="formRuang" method="POST" action="{{ route('dashboard.sarpras.ruang.store') }}"
                    style="overflow-y: auto; min-height: 0;">
                    @csrf
                    <input type="hidden" name="_method" id="ruangMethod" value="POST">
                    <input type="hidden" name="id" id="ruangId">

                    <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 12px; margin-bottom: 14px;">
                        <div>
                            <label
                                style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Kode
                                Ruang <span style="color: var(--danger, #ef4444);">*</span></label>
                            <input type="text" name="kode_ruang" id="ruangKode" class="form-control"
                                placeholder="R-01" required>
                        </div>
                        <div>
                            <label
                                style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Nama
                                Ruang <span style="color: var(--danger, #ef4444);">*</span></label>
                            <input type="text" name="nama_ruang" id="ruangNama" class="form-control"
                                placeholder="Ruang Kelas X TKJ 1" required>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                        <div>
                            <label
                                style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Gedung</label>
                            <input type="text" name="gedung" id="ruangGedung" class="form-control"
                                placeholder="Gedung A">
                        </div>
                        <div>
                            <label
                                style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Lantai</label>
                            <input type="text" name="lantai" id="ruangLantai" class="form-control"
                                placeholder="Lantai 1">
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr 1.2fr; gap: 12px; margin-bottom: 14px;">
                        <div>
                            <label
                                style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Panjang (m)</label>
                            <input type="number" step="0.01" min="0" name="panjang" id="ruangPanjang" class="form-control"
                                placeholder="mis. 9.00">
                        </div>
                        <div>
                            <label
                                style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Lebar (m)</label>
                            <input type="number" step="0.01" min="0" name="lebar" id="ruangLebar" class="form-control"
                                placeholder="mis. 8.00">
                        </div>
                        <div>
                            <label
                                style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Luas (m²)</label>
                            <input type="number" step="0.01" min="0" name="luas" id="ruangLuas" class="form-control"
                                placeholder="Otomatis (P × L)">
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: 14px;">
                        <label
                            style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Penanggung
                            Jawab (GTK)</label>
                        <input type="search" id="ruangPj" class="form-control"
                            placeholder="Cari nama, NIP, NUPTK, atau NIK..." autocomplete="off"
                            data-people-search="{{ route('dashboard.people.search') }}" data-people-kind="personel"
                            data-people-target="#ruangPjId">
                        <input type="hidden" id="ruangPjId" name="penanggung_jawab_ptk_id">
                    </div>

                    <div class="form-group" style="margin-bottom: 14px;">
                        <label
                            style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Kondisi
                            Ruang <span style="color: var(--danger, #ef4444);">*</span></label>
                        <select name="kondisi" id="ruangKondisi" class="form-control" required>
                            <option value="baik">Baik</option>
                            <option value="rusak_ringan">Rusak Ringan</option>
                            <option value="rusak_berat">Rusak Berat</option>
                        </select>
                    </div>

                    <div class="form-group" style="margin-bottom: 20px;">
                        <label
                            style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Keterangan
                            / Fasilitas Ruang</label>
                        <textarea name="keterangan" id="ruangKeterangan" class="form-control" rows="2"
                            placeholder="Luas ruang, kelengkapan proyektor, AC, kapasitas kursi..."></textarea>
                    </div>

                    <div style="display: flex; justify-content: flex-end; gap: 8px; margin-top: 20px; padding-top: 14px; border-top: 1px solid var(--border-color);">
                        <button type="button" class="btn btn-secondary" id="btnCancelModalRuang">Batal</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Simpan
                            Data</button>
                    </div>
                </form>
            </div>
        </div>

        @push('scripts')
            <script
                src="{{ asset('js/sarpras-ruang.js') }}?v={{ file_exists(public_path('js/sarpras-ruang.js')) ? filemtime(public_path('js/sarpras-ruang.js')) : time() }}">
            </script>
        @endpush
    @endsection
