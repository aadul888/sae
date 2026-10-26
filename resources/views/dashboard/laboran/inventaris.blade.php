@extends('layouts.dashboard')

@section('title', 'Inventaris Alat & Bahan — Laboratorium SAE')
@section('dash_title', 'Inventaris Lab')

@section('content')
    <div class="dash-content-inner">
        {{-- 1. Header Banner & Action Button --}}
        <div class="dash-banner"
            style="display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; margin-bottom: 20px;">
            <div style="display: flex; align-items: center; gap: 14px;">
                <div
                    style="width: 48px; height: 48px; border-radius: var(--radius-lg, 12px); background: rgba(59,130,246,0.12); color: var(--primary, #3b82f6); display: flex; align-items: center; justify-content: center; font-size: var(--text-2xl, 1.5rem); flex-shrink: 0;">
                    <i class="fas fa-flask"></i>
                </div>
                <div>
                    <h2 style="font-size: var(--text-xl, 1.25rem); font-weight: 800; color: var(--text-color); margin: 0;">
                        Inventaris Alat &amp; Bahan Lab
                    </h2>
                    <div style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); margin-top: 2px;">
                        Pengelolaan alat praktikum, bahan habis pakai, stok, dan kontrol masa kadaluarsa laboratorium.
                    </div>
                </div>
            </div>
            <div class="dash-banner-actions" style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                <span class="badge"
                    style="background: rgba(59,130,246,0.12); color: var(--primary, #3b82f6); border: 1px solid rgba(59,130,246,0.25); padding: 6px 12px; border-radius: var(--radius-md, 8px); font-size: var(--text-xs, 0.75rem); font-weight: 700; display: inline-flex; align-items: center; gap: 6px;">
                    <i class="fas {{ $scopeBadge['icon'] ?? 'fa-graduation-cap' }}"></i> {{ $scopeBadge['label'] ?? 'Semua Jurusan' }}
                </span>
                @if ($canCreate)
                    <button type="button" class="btn btn-outline" id="btnOpenImportInventaris"
                        style="height: 38px; padding: 0 14px; display: inline-flex; align-items: center; gap: 6px; border-radius: var(--radius-md, 8px); font-size: var(--text-sm, 0.8125rem);"
                        title="Import Alat &amp; Bahan via Excel">
                        <i class="fas fa-file-excel text-success"></i> Import Excel
                    </button>
                    <button type="button" class="btn btn-primary" id="btnOpenModalLab"
                        style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px); font-size: var(--text-md, 1rem);"
                        title="Tambah Alat / Bahan Laboratorium">
                        <i class="fas fa-plus"></i>
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
                <div class="dash-stat-icon" style="background: rgba(16, 185, 129, 0.15); color: var(--success, #10b981);">
                    <i class="fas fa-microscope"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ $statAlat }}</div>
                    <div class="dash-stat-label">Alat Praktikum</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(59, 130, 246, 0.15); color: var(--primary, #3b82f6);">
                    <i class="fas fa-vial"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ $statBahan }}</div>
                    <div class="dash-stat-label">Bahan Habis Pakai</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(239, 68, 68, 0.15); color: var(--danger, #ef4444);">
                    <i class="fas fa-triangle-exclamation"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ $statKritis }}</div>
                    <div class="dash-stat-label">Stok Kritis / Rusak</div>
                </div>
            </div>
        </div>

        {{-- Unified Datatable Alat & Bahan Lab (Toolbar + Table + Pagination dalam 1 Card Terpadu) --}}
        <div class="card datatable-card-wrapper" id="tableDataContainer">
            <!-- 1. Toolbar Header (Entries, Filters & Live Search) -->
            <div class="datatable-toolbar">
                <form method="GET" action="{{ route('dashboard.laboran.inventaris.index') }}" id="filterForm"
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

                            <select name="lab" id="filterLab" class="toolbar-filter-select" style="height: 38px;">
                                <option value="">Semua Ruang</option>
                                @if (isset($daftarRuangSarpras) && $daftarRuangSarpras->isNotEmpty())
                                    @foreach ($daftarRuangSarpras as $r)
                                        <option value="{{ $r->nama_ruang }}"
                                            {{ $labFilter === $r->nama_ruang ? 'selected' : '' }}>
                                            {{ $r->nama_ruang }} {{ $r->gedung ? "({$r->gedung})" : '' }}
                                        </option>
                                    @endforeach
                                @else
                                    @foreach ($daftarLab as $lab)
                                        <option value="{{ $lab }}" {{ $labFilter === $lab ? 'selected' : '' }}>
                                            {{ $lab }}</option>
                                    @endforeach
                                @endif
                            </select>

                            <select name="jenis" id="filterJenis" class="toolbar-filter-select" style="height: 38px;">
                                <option value="">Semua Jenis</option>
                                <option value="alat" {{ $jenisFilter === 'alat' ? 'selected' : '' }}>Alat</option>
                                <option value="bahan_habis_pakai"
                                    {{ $jenisFilter === 'bahan_habis_pakai' ? 'selected' : '' }}>
                                    Bahan Habis Pakai</option>
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
                                <option value="kedaluwarsa" {{ $kondisiFilter === 'kedaluwarsa' ? 'selected' : '' }}>
                                    Kedaluwarsa
                                </option>
                            </select>

                            @if ($search || $labFilter || $jenisFilter || $kondisiFilter)
                                <a href="{{ route('dashboard.laboran.inventaris.index') }}"
                                    class="btn btn-outline btn-reset-filter"
                                    style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px);"
                                    title="Reset filter" data-live-reset><i class="fas fa-undo"></i></a>
                            @endif
                        </div>

                        <div class="live-search-wrap" style="height: 38px;">
                            <i class="fas fa-search search-icon"></i>
                            <input type="text" name="q" id="liveSearch" value="{{ $search }}"
                                placeholder="Cari nama item, kode, lemari rak..." autocomplete="off">
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
                                Kode &amp; Nama Item</th>
                            <th
                                style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Ruang Lab &amp; Rak</th>
                            <th
                                style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Jenis &amp; Spek</th>
                            <th
                                style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center;">
                                Stok</th>
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
                            @php
                                $isLow = $item->stok_tersedia <= 2;
                            @endphp
                            <tr style="border-bottom: 1px solid var(--border-color); transition: background 0.2s ease;">
                                <td data-label="No"
                                    style="padding: 14px 18px; font-size: var(--text-base, 0.875rem); color: var(--text-muted);">
                                    {{ $list->firstItem() + $idx }}
                                </td>
                                <td data-label="Item" style="padding: 14px 18px;">
                                    <div style="font-weight: 700; color: var(--text-color); font-size: var(--text-base, 0.875rem);">
                                        {{ $item->nama_item }}</div>
                                    <div style="font-size: var(--text-sm, 0.8125rem); color: var(--primary); font-family: monospace;">
                                        {{ $item->kode_item }}</div>
                                    @if (!empty($item->nama_jurusan) || !empty($item->singkatan))
                                        <div style="margin-top: 3px;">
                                            <span class="badge badge-outline" style="font-size: var(--text-2xs, 0.6875rem);">
                                                <i class="fas fa-graduation-cap me-1"></i>{{ $item->singkatan ?: $item->nama_jurusan }}
                                            </span>
                                        </div>
                                    @elseif (empty($item->jurusan_id))
                                        <div style="margin-top: 3px;">
                                            <span class="badge badge-warning" style="font-size: var(--text-2xs, 0.6875rem);">Jurusan belum diset</span>
                                        </div>
                                    @endif
                                    @if ($item->tgl_kedaluwarsa)
                                        <div style="font-size: var(--text-xs, 0.75rem); color: var(--warning, #f59e0b);">Exp:
                                            {{ \Carbon\Carbon::parse($item->tgl_kedaluwarsa)->format('d/m/Y') }}</div>
                                    @endif
                                </td>
                                <td data-label="Lab" style="padding: 14px 18px; font-size: var(--text-base, 0.875rem);">
                                    <div style="font-weight: 600; color: var(--text-color);">{{ $item->ruang_lab_nama }}
                                    </div>
                                    <div style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted);">Rak:
                                        {{ $item->lokasi_lemari_rak ?: '-' }}</div>
                                </td>
                                <td data-label="Jenis" style="padding: 14px 18px; font-size: var(--text-base, 0.875rem);">
                                    <span class="badge {{ $item->jenis === 'alat' ? 'badge-info' : 'badge-primary' }}"
                                        style="font-size: var(--text-xs, 0.75rem);">
                                        {{ $item->jenis === 'alat' ? 'Alat' : 'Bahan Habis Pakai' }}
                                    </span>
                                    @if ($item->spesifikasi)
                                        <div style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); margin-top: 3px; max-width: 200px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"
                                            title="{{ $item->spesifikasi }}">
                                            {{ $item->spesifikasi }}
                                        </div>
                                    @endif
                                </td>
                                <td data-label="Stok" style="padding: 14px 18px; text-align: center;">
                                    <div
                                        style="font-weight: 700; font-size: var(--text-md, 1rem); color: {{ $isLow ? 'var(--danger, #ef4444);' : 'var(--text-color)' }};">
                                        {{ (float) $item->stok_tersedia }} / {{ (float) $item->stok_total }}
                                        {{ $item->satuan }}
                                    </div>
                                    @if ($isLow)
                                        <span class="badge badge-danger" style="font-size: var(--text-2xs, 0.6875rem);">Stok Menipis</span>
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
                                                data-id="{{ $item->id }}" data-lab="{{ $item->ruang_lab_nama }}"
                                                data-kode="{{ $item->kode_item }}" data-nama="{{ $item->nama_item }}"
                                                data-jenis="{{ $item->jenis }}"
                                                data-stoktotal="{{ (float) $item->stok_total }}"
                                                data-stoktersedia="{{ (float) $item->stok_tersedia }}"
                                                data-satuan="{{ $item->satuan }}" data-kondisi="{{ $item->kondisi }}"
                                                data-spek="{{ $item->spesifikasi }}"
                                                data-rak="{{ $item->lokasi_lemari_rak }}"
                                                data-jurusan="{{ $item->jurusan_id }}"
                                                data-exp="{{ $item->tgl_kedaluwarsa }}" title="Edit Item">
                                                <i class="fas fa-pen-to-square"></i>
                                            </button>
                                        @endif
                                        @if ($canDelete)
                                            <button type="button" class="btn-icon btn-delete-lab"
                                                data-id="{{ $item->id }}" data-nama="{{ $item->nama_item }}"
                                                title="Hapus Item" style="color: var(--danger, #ef4444);">
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
                                    <div>Belum ada data alat/bahan lab yang cocok.</div>
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

        {{-- Modal Tambah / Edit Alat Bahan Lab --}}
        <div id="modalLab" class="modal-backdrop"
            style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
            <div class="card"
                style="width: 100%; max-width: 580px; max-height: 90vh; overflow-y: auto; padding: 24px; border-radius: var(--radius-lg, 12px);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h3 id="modalLabTitle"
                        style="margin: 0; font-size: var(--text-xl, 1.25rem); font-weight: 700; color: var(--text-color);">
                        Tambah Alat / Bahan Lab
                    </h3>
                    <button type="button" id="btnCloseModalLab"
                        style="background: none; border: none; font-size: var(--text-xl, 1.25rem); color: var(--text-muted); cursor: pointer;">&times;</button>
                </div>

                <form id="formLab" method="POST" action="{{ route('dashboard.laboran.inventaris.store') }}">
                    @csrf
                    <input type="hidden" name="_method" id="labMethod" value="POST">
                    <input type="hidden" name="id" id="labId">

                    @if (isset($daftarAsetSarpras) && $daftarAsetSarpras->isNotEmpty())
                        <div id="wrapperAsetSarpras"
                            style="margin-bottom: 14px; padding: 10px 12px; background: rgba(99,102,241,0.06); border: 1px dashed rgba(99,102,241,0.3); border-radius: var(--radius-md, 8px);">
                            <div
                                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                                <label style="font-size: var(--text-sm, 0.8125rem); font-weight: 600; color: var(--primary); margin: 0;">
                                    <i class="fas fa-boxes-stacked me-1"></i> Referensi / Salin dari Aset Sarpras
                                    (Opsional)
                                </label>
                                <small style="color: var(--text-muted); font-size: var(--text-xs, 0.75rem);">Isi otomatis data</small>
                            </div>
                            <select id="selectAsetSarpras" class="form-control"
                                style="font-size: var(--text-sm, 0.8125rem); height: 36px;">
                                <option value="">-- Ketik Baru atau Pilih Referensi Aset Sarpras --</option>
                                @foreach ($daftarAsetSarpras as $ast)
                                    <option value="{{ $ast->id }}" data-nama="{{ $ast->nama_barang }}"
                                        data-kode="{{ $ast->kode_aset }}" data-ruang="{{ $ast->ruang_nama }}"
                                        data-satuan="{{ $ast->satuan }}" data-kondisi="{{ $ast->kondisi }}"
                                        data-spek="{{ $ast->merk_tipe ? 'Merk/Tipe: ' . $ast->merk_tipe : '' }}">
                                        {{ $ast->nama_barang }} &bull; {{ $ast->kode_aset }} (Lokasi:
                                        {{ $ast->ruang_nama ?: 'Sarpras' }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                        <div>
                            <div
                                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                                <label style="font-size: var(--text-base, 0.875rem); font-weight: 600; color: var(--text-color); margin: 0;">
                                    Ruang Lab / Bengkel <span style="color: var(--danger, #ef4444);">*</span>
                                </label>
                                <a href="{{ route('dashboard.sarpras.ruang.index') }}" target="_blank"
                                    style="font-size: var(--text-xs, 0.75rem); color: var(--primary); text-decoration: none;"
                                    title="Kelola Ruang di Sarpras & Aset">
                                    <i class="fas fa-arrow-up-right-from-square me-1"></i> Data Sarpras
                                </a>
                            </div>
                            <select name="ruang_lab_nama" id="labRuang" class="form-control" required>
                                <option value="">-- Pilih Ruang dari Sarpras &amp; Aset --</option>
                                @if (isset($daftarRuangSarpras) && $daftarRuangSarpras->isNotEmpty())
                                    @foreach ($daftarRuangSarpras as $r)
                                        <option value="{{ $r->nama_ruang }}">
                                            {{ $r->nama_ruang }} &bull; {{ $r->gedung ?: 'Sarpras' }}
                                            {{ $r->lantai ? '(Lt. ' . $r->lantai . ')' : '' }}
                                        </option>
                                    @endforeach
                                @else
                                    @foreach ($daftarLab as $l)
                                        <option value="{{ $l }}">{{ $l }}</option>
                                    @endforeach
                                @endif
                            </select>
                            <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-top: 4px;">
                                <i class="fas fa-link me-1 text-primary"></i> Terhubung langsung dengan <strong>Ruang &amp;
                                    Gedung Sarpras</strong>.
                            </div>
                        </div>
                        <div>
                            <label
                                style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Jenis
                                Item <span style="color: var(--danger, #ef4444);">*</span></label>
                            <select name="jenis" id="labJenis" class="form-control" required>
                                <option value="alat">Alat Praktikum</option>
                                <option value="bahan_habis_pakai">Bahan Habis Pakai</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: 14px;">
                        <label
                            style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Jurusan
                            / Kompetensi Keahlian</label>
                        <select name="jurusan_id" id="labJurusan" class="form-control" {{ !empty($context['unrestricted']) ? '' : 'disabled' }}>
                            @if (!empty($context['unrestricted']))
                                <option value="">-- Ikut Penugasan --</option>
                            @endif
                            @foreach (($jurusanOptions ?? []) as $j)
                                <option value="{{ $j->jurusan_id }}" {{ ($context['jurusan_id'] ?? null) === $j->jurusan_id ? 'selected' : '' }}>
                                    {{ $j->nama_jurusan }}{{ !empty($j->singkatan) ? ' (' . $j->singkatan . ')' : '' }}
                                </option>
                            @endforeach
                        </select>
                        <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-top: 4px;">
                            @if (!empty($context['unrestricted']))
                                <i class="fas fa-info-circle me-1"></i> Kosongkan agar mengikuti jurusan penugasan Anda.
                            @else
                                <i class="fas fa-lock me-1"></i> Terkunci pada jurusan penugasan Anda.
                            @endif
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 12px; margin-bottom: 14px;">
                        <div>
                            <label
                                style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Kode
                                Item <span style="color: var(--danger, #ef4444);">*</span></label>
                            <input type="text" name="kode_item" id="labKode" class="form-control"
                                placeholder="LAB-IPA-001" required>
                        </div>
                        <div>
                            <label
                                style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Nama
                                Alat / Bahan <span style="color: var(--danger, #ef4444);">*</span></label>
                            <input type="text" name="nama_item" id="labNama" class="form-control"
                                placeholder="Mikroskop Binokuler / Asam Klorida" required>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                        <div>
                            <label
                                style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Stok
                                Total <span style="color: var(--danger, #ef4444);">*</span></label>
                            <input type="number" step="0.01" name="stok_total" id="labStokTotal"
                                class="form-control" value="1" required>
                        </div>
                        <div id="wrapperStokTersedia" style="display: none;">
                            <label
                                style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Stok
                                Tersedia</label>
                            <input type="number" step="0.01" name="stok_tersedia" id="labStokTersedia"
                                class="form-control" value="1">
                        </div>
                        <div>
                            <label
                                style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Satuan
                                <span style="color: var(--danger, #ef4444);">*</span></label>
                            <input type="text" name="satuan" id="labSatuan" class="form-control" value="unit"
                                placeholder="unit/botol/pcs" required>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                        <div>
                            <label
                                style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Kondisi
                                <span style="color: var(--danger, #ef4444);">*</span></label>
                            <select name="kondisi" id="labKondisi" class="form-control" required>
                                <option value="baik">Baik</option>
                                <option value="rusak_ringan">Rusak Ringan</option>
                                <option value="rusak_berat">Rusak Berat</option>
                                <option value="kedaluwarsa">Kedaluwarsa</option>
                            </select>
                        </div>
                        <div>
                            <label
                                style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Tanggal
                                Kedaluwarsa</label>
                            <input type="date" name="tgl_kedaluwarsa" id="labExp" class="form-control">
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: 14px;">
                        <label
                            style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Lokasi
                            Lemari / Rak</label>
                        <input type="text" name="lokasi_lemari_rak" id="labRak" class="form-control"
                            placeholder="Lemari A - Rak 2">
                    </div>

                    <div class="form-group" style="margin-bottom: 20px;">
                        <label
                            style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Spesifikasi
                            / Keterangan</label>
                        <textarea name="spesifikasi" id="labSpek" class="form-control" rows="2"
                            placeholder="Merk, ukuran, konsentrasi bahan..."></textarea>
                    </div>

                    <div style="display: flex; justify-content: flex-end; gap: 8px;">
                        <button type="button" class="btn btn-secondary" id="btnCancelModalLab">Batal</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Simpan
                            Item</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Modal Import Inventaris via Excel (Point 7) --}}
        <div id="modalImportInventaris" class="modal-backdrop"
            style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
            <div class="card" style="width: 100%; max-width: 520px; padding: 24px; border-radius: var(--radius-lg, 12px);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px;">
                    <h3 style="margin: 0; font-size: var(--text-xl, 1.25rem); font-weight: 700; color: var(--text-color);">
                        <i class="fas fa-file-excel text-success me-2"></i> Import Inventaris via Excel
                    </h3>
                    <button type="button" id="btnCloseModalImport"
                        style="background: none; border: none; font-size: var(--text-xl, 1.25rem); color: var(--text-muted); cursor: pointer;">&times;</button>
                </div>

                <div style="margin-bottom: 18px; padding: 12px 14px; background: rgba(16,185,129,0.08); border: 1px solid rgba(16,185,129,0.25); border-radius: var(--radius-md, 8px);">
                    <div style="font-size: var(--text-sm, 0.8125rem); color: var(--text-color); font-weight: 600; margin-bottom: 4px;">
                        Unduh Format Template:
                    </div>
                    <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-bottom: 10px;">
                        Gunakan berkas template resmi agar nama kolom sesuai dengan sistem.
                    </div>
                    <a href="{{ route('dashboard.laboran.inventaris.template') }}" class="btn btn-outline"
                        style="font-size: var(--text-xs, 0.75rem); padding: 6px 12px; display: inline-flex; align-items: center; gap: 6px; text-decoration: none;">
                        <i class="fas fa-download text-success"></i> Unduh Format Excel (.xlsx)
                    </a>
                </div>

                <form id="formImportInventaris" method="POST" action="{{ route('dashboard.laboran.inventaris.import') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="form-group" style="margin-bottom: 18px;">
                        <label style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">
                            Pilih Berkas Excel / CSV <span style="color: var(--danger, #ef4444);">*</span>
                        </label>
                        <input type="file" name="file" id="fileImportInventaris" class="form-control" accept=".xlsx,.xls,.csv" required>
                        <small style="color: var(--text-muted); font-size: var(--text-xs, 0.75rem); display: block; margin-top: 4px;">
                            Mendukung .xlsx, .xls, atau .csv (Maks. 10 MB).
                        </small>
                    </div>

                    <div style="display: flex; justify-content: flex-end; gap: 8px;">
                        <button type="button" class="btn btn-secondary" id="btnCancelModalImport">Batal</button>
                        <button type="submit" class="btn btn-primary" id="btnSubmitImport">
                            <i class="fas fa-upload me-1"></i> Mulai Unggah &amp; Import
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

        @push('scripts')
            <script
                src="{{ asset('js/laboran-inventaris.js') }}?v={{ file_exists(public_path('js/laboran-inventaris.js')) ? filemtime(public_path('js/laboran-inventaris.js')) : time() }}">
            </script>
        @endpush
    @endsection
