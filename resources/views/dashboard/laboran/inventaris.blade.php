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
                                    @if (!empty($item->tahun_pengadaan) || !empty($item->tanggal_pengadaan))
                                        <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-top: 2px;"
                                            title="Tanggal / Tahun Pengadaan">
                                            <i class="fas fa-calendar-plus me-1"></i>
                                            @if (!empty($item->tanggal_pengadaan))
                                                {{ \Carbon\Carbon::parse($item->tanggal_pengadaan)->translatedFormat('d M Y') }}
                                            @else
                                                {{ $item->tahun_pengadaan }}
                                            @endif
                                        </div>
                                    @endif
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

                                    @php
                                        $jBaik = (float) ($item->jumlah_baik ?? 0);
                                        $jRr   = (float) ($item->jumlah_rusak_ringan ?? 0);
                                        $jRb   = (float) ($item->jumlah_rusak_berat ?? 0);
                                        $jKad  = (float) ($item->jumlah_kedaluwarsa ?? 0);
                                        $adaRincian = ($jBaik + $jRr + $jRb + $jKad) > 0;
                                    @endphp

                                    @if ($adaRincian)
                                        <div style="margin-top: 5px; display: flex; flex-direction: column; gap: 2px; font-size: var(--text-2xs, 0.6875rem); line-height: 1.35;">
                                            @if ($jBaik > 0)
                                                <span style="color: var(--success, #10b981);" title="Jumlah kondisi baik">
                                                    <i class="fas fa-circle-check me-1"></i>{{ $jBaik }} baik
                                                </span>
                                            @endif
                                            @if ($jRr > 0)
                                                <span style="color: var(--warning, #f59e0b);" title="Jumlah rusak ringan">
                                                    <i class="fas fa-triangle-exclamation me-1"></i>{{ $jRr }} rusak ringan
                                                </span>
                                            @endif
                                            @if ($jRb > 0)
                                                <span style="color: var(--danger, #ef4444);" title="Jumlah rusak berat">
                                                    <i class="fas fa-circle-exclamation me-1"></i>{{ $jRb }} rusak berat
                                                </span>
                                            @endif
                                            @if ($jKad > 0)
                                                <span style="color: var(--purple, #8b5cf6);" title="Jumlah kedaluwarsa">
                                                    <i class="fas fa-hourglass-end me-1"></i>{{ $jKad }} kedaluwarsa
                                                </span>
                                            @endif
                                        </div>
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
                                                data-jumlahbaik="{{ (float) ($item->jumlah_baik ?? 0) }}"
                                                data-jumlahrusakringan="{{ (float) ($item->jumlah_rusak_ringan ?? 0) }}"
                                                data-jumlahrusakberat="{{ (float) ($item->jumlah_rusak_berat ?? 0) }}"
                                                data-jumlahkedaluwarsa="{{ (float) ($item->jumlah_kedaluwarsa ?? 0) }}"
                                                data-spek="{{ $item->spesifikasi }}"
                                                data-rak="{{ $item->lokasi_lemari_rak }}"
                                                data-jurusan="{{ $item->jurusan_id }}"
                                                data-exp="{{ $item->tgl_kedaluwarsa }}"
                                                data-tglpengadaan="{{ $item->tanggal_pengadaan }}"
                                                data-tahunpengadaan="{{ $item->tahun_pengadaan }}" title="Edit Item">
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

        @include('dashboard.laboran.partials.inventaris-modal-form')

        @include('dashboard.laboran.partials.inventaris-modal-import')
    </div>

        @push('scripts')
            <script
                src="{{ asset('js/laboran-inventaris.js') }}?v={{ file_exists(public_path('js/laboran-inventaris.js')) ? filemtime(public_path('js/laboran-inventaris.js')) : time() }}">
            </script>
        @endpush
    @endsection
