@extends('layouts.dashboard')

@section('title', 'Bahan Habis Pakai — Laboratorium SAE')
@section('dash_title', 'Bahan Habis Pakai')

@section('content')
    @include('dashboard.laboran.partials.header-actions')
    <div class="dash-content-inner">
        {{-- 1. Header Banner & Action Button --}}
        <div class="dash-banner"
            style="display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; margin-bottom: 20px;">
            <div style="display: flex; align-items: center; gap: 14px;">
                <div
                    style="width: 48px; height: 48px; border-radius: var(--radius-lg, 12px); background: rgba(16,185,129,0.12); color: var(--success, #10b981); display: flex; align-items: center; justify-content: center; font-size: var(--text-2xl, 1.5rem); flex-shrink: 0;">
                    <i class="fas fa-vial"></i>
                </div>
                <div>
                    <h2 style="font-size: var(--text-xl, 1.25rem); font-weight: 800; color: var(--text-color); margin: 0;">
                        Bahan Habis Pakai
                    </h2>
                    <div style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); margin-top: 2px;">
                        Pengelolaan bahan habis pakai praktik: stok, pemakaian, dan kontrol kedaluwarsa.
                    </div>
                </div>
            </div>
            <div class="dash-banner-actions dash-banner-access">
                @if ($canRead)
                    <a href="{{ route('dashboard.laboran.bhp.cetak', request()->query()) }}" target="_blank"
                        class="btn btn-outline"
                        title="Cetak Laporan Bahan Habis Pakai" aria-label="Cetak Laporan Bahan Habis Pakai">
                        <i class="fas fa-print"></i>
                    </a>
                @endif
                @if ($canCreate)
                    <button type="button" class="btn btn-outline" id="btnOpenImportBhp"
                        title="Import Bahan via Excel" aria-label="Import Excel">
                        <i class="fas fa-file-excel text-success"></i>
                    </button>
                    <button type="button" class="btn btn-primary" id="btnOpenModalBhp"
                        title="Tambah Bahan Habis Pakai" aria-label="Tambah Bahan Habis Pakai">
                        <i class="fas fa-plus"></i>
                    </button>
                @endif
            </div>
        </div>

        {{-- 2. Stat Grid --}}
        <div class="dash-stat-grid" style="margin-bottom: 24px; gap: 14px;">
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(99, 102, 241, 0.15); color: var(--primary);">
                    <i class="fas fa-vials"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ $statTotal }}</div>
                    <div class="dash-stat-label">Jenis Bahan</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(16, 185, 129, 0.15); color: var(--success, #10b981);">
                    <i class="fas fa-boxes-stacked"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ number_format((float) $statStok, 0, ',', '.') }}</div>
                    <div class="dash-stat-label">Total Stok Tersedia</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(245, 158, 11, 0.15); color: var(--warning, #f59e0b);">
                    <i class="fas fa-triangle-exclamation"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ $statMenipis }}</div>
                    <div class="dash-stat-label">Stok Menipis</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(239, 68, 68, 0.15); color: var(--danger, #ef4444);">
                    <i class="fas fa-hourglass-end"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ $statExp }}</div>
                    <div class="dash-stat-label">Kedaluwarsa</div>
                </div>
            </div>
        </div>

        {{-- 3. Datatable --}}
        <div class="card datatable-card-wrapper" id="tableDataContainer">
            <div class="datatable-toolbar">
                <form method="GET" action="{{ route('dashboard.laboran.bhp.index') }}" id="filterForm"
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
                                @foreach ($daftarLab as $lab)
                                    <option value="{{ $lab }}" {{ $labFilter === $lab ? 'selected' : '' }}>
                                        {{ $lab }}</option>
                                @endforeach
                            </select>

                            <select name="kondisi" id="filterKondisi" class="toolbar-filter-select" style="height: 38px;">
                                <option value="">Semua Kondisi</option>
                                @foreach ($kondisiList as $kKey => $kLabel)
                                    <option value="{{ $kKey }}" {{ $kondisiFilter === $kKey ? 'selected' : '' }}>
                                        {{ $kLabel }}</option>
                                @endforeach
                            </select>

                            <select name="exp" id="filterExp" class="toolbar-filter-select" style="height: 38px;">
                                <option value="">Semua Masa Exp</option>
                                <option value="segera" {{ $expFilter === 'segera' ? 'selected' : '' }}>Segera (&le; 90 hari)</option>
                                <option value="kadaluarsa" {{ $expFilter === 'kadaluarsa' ? 'selected' : '' }}>Sudah Kedaluwarsa</option>
                            </select>

                            <select name="sumber_dana" id="filterSumberDanaBhp" class="toolbar-filter-select" style="height: 38px;">
                                <option value="">Semua Sumber Anggaran</option>
                                @foreach ($sumberDanaList as $sdKey => $sdLabel)
                                    <option value="{{ $sdKey }}" {{ ($sumberFilter ?? '') === $sdKey ? 'selected' : '' }}>
                                        {{ $sdLabel }}
                                    </option>
                                @endforeach
                            </select>

                            @if ($search || $labFilter || $kondisiFilter || $expFilter || !empty($sumberFilter))
                                <a href="{{ route('dashboard.laboran.bhp.index') }}"
                                    class="btn btn-outline btn-reset-filter"
                                    style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px);"
                                    title="Reset filter" data-live-reset><i class="fas fa-undo"></i></a>
                            @endif
                            @if ($canDelete)
                                <button type="button" id="btnBulkDeleteBhp" class="btn btn-danger" disabled
                                    style="height: 38px; padding: 0 14px; display: inline-flex; align-items: center; gap: 6px; border-radius: var(--radius-md, 8px); font-size: var(--text-sm, 0.8125rem); opacity: 0.5; cursor: not-allowed;"
                                    title="Hapus bahan terpilih">
                                    <i class="fas fa-trash-can"></i> Hapus (<span id="countBulkBhp">0</span>)
                                </button>
                            @endif
                        </div>

                        <div class="live-search-wrap" style="height: 38px;">
                            <i class="fas fa-search search-icon"></i>
                            <input type="text" name="q" id="liveSearch" value="{{ $search }}"
                                placeholder="Cari nama bahan, kode, rak..." autocomplete="off">
                            <button type="button" id="clearSearch" class="clear-search {{ $search ? 'visible' : '' }}"
                                title="Hapus pencarian">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="table-responsive-stack" style="overflow-x: auto; width: 100%;">
                <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                    <thead>
                        <tr style="border-bottom: 1px solid var(--border-color); ">
                            @if ($canDelete)
                                <th style="width: 34px; text-align: center; padding: 12px 8px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted);">
                                    <input type="checkbox" id="chkAllBhp" title="Pilih semua" style="cursor: pointer; accent-color: var(--primary);">
                                </th>
                            @endif
                            <th style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">No</th>
                            <th style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Kode & Nama Bahan</th>
                            <th style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Ruang & Rak</th>
                            <th style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center;">Stok</th>
                            <th style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center;">Kondisi</th>
                            <th style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center;">Kedaluwarsa</th>
                            <th style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: right;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($list as $idx => $item)
                            @php
                                $isLow = $item->stok_tersedia <= 2;
                                $isExpired = $item->tgl_kedaluwarsa && $item->tgl_kedaluwarsa < now()->toDateString();
                                $isSoon = $item->tgl_kedaluwarsa && !$isExpired && $item->tgl_kedaluwarsa <= now()->addDays(90)->toDateString();
                            @endphp
                            <tr style="border-bottom: 1px solid var(--border-color); transition: background 0.2s ease;">
                @if ($canDelete)
                                    <td data-label="Pilih" style="padding: 14px 8px; text-align: center;">
                                        <input type="checkbox" class="chk-item-bhp" value="{{ $item->id }}" style="cursor: pointer; accent-color: var(--primary);">
                                    </td>
                                @endif
                                <td data-label="No"
                                    style="padding: 14px 18px; font-size: var(--text-base, 0.875rem); color: var(--text-muted);">
                                    {{ $list->firstItem() + $idx }}
                                </td>
                                <td data-label="Bahan" style="padding: 14px 18px;">
                                    <div style="font-weight: 700; color: var(--text-color); font-size: var(--text-base, 0.875rem);">
                                        {{ $item->nama_item }}</div>
                                    <div style="font-size: var(--text-sm, 0.8125rem); color: var(--primary); font-family: monospace;">
                                        {{ $item->kode_item }}</div>
                                    @if (!empty($item->spesifikasi))
                                        <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-top: 2px;">
                                            {{ \Illuminate\Support\Str::limit($item->spesifikasi, 60) }}</div>
                                    @endif
                                </td>
                                <td data-label="Ruang" style="padding: 14px 18px; font-size: var(--text-base, 0.875rem);">
                                    <div style="font-weight: 600; color: var(--text-color);">{{ $item->ruang_lab_nama }}</div>
                                    <div style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted);">Rak:
                                        {{ $item->lokasi_lemari_rak ?: '-' }}</div>
                                </td>
                                <td data-label="Stok" style="padding: 14px 18px; text-align: center;">
                                    <div style="font-weight: 800; color: var(--text-color); font-size: var(--text-base, 0.875rem);">
                                        {{ rtrim(rtrim(number_format((float) $item->stok_tersedia, 2, '.', ''), '0'), '.') }} <span style="font-weight: 500; font-size: var(--text-sm, 0.8125rem); color: var(--text-muted);">{{ $item->satuan }}</span>
                                    </div>
                                    <div style="font-size: var(--text-2xs, 0.6875rem); color: var(--text-muted);">
                                        total {{ rtrim(rtrim(number_format((float) $item->stok_total, 2, '.', ''), '0'), '.') }}
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
                                <td data-label="Kedaluwarsa" style="padding: 14px 18px; text-align: center;">
                                    @if ($item->tgl_kedaluwarsa)
                                        <div style="font-size: var(--text-sm, 0.8125rem); color: {{ $isExpired ? 'var(--danger, #ef4444)' : ($isSoon ? 'var(--warning, #f59e0b)' : 'var(--text-color)') }}; font-weight: 600;">
                                            {{ \Carbon\Carbon::parse($item->tgl_kedaluwarsa)->format('d/m/Y') }}
                                        </div>
                                        @if ($isExpired)
                                            <span class="badge badge-danger" style="font-size: var(--text-2xs, 0.6875rem);">Kedaluwarsa</span>
                                        @elseif ($isSoon)
                                            <span class="badge badge-warning" style="font-size: var(--text-2xs, 0.6875rem);">Segera</span>
                                        @endif
                                    @else
                                        <span style="color: var(--text-muted); font-size: var(--text-sm, 0.8125rem);">-</span>
                                    @endif
                                </td>
                                <td data-label="Aksi" style="padding: 14px 18px; text-align: right;">
                                    <div class="table-actions" style="justify-content: flex-end; gap: 6px;">
                                        @if ($canUpdate)
                                            <button type="button" class="btn-icon btn-edit-bhp"
                                                data-id="{{ $item->id }}" data-lab="{{ $item->ruang_lab_nama }}"
                                                data-kode="{{ $item->kode_item }}" data-nama="{{ $item->nama_item }}"
                                                data-stoktotal="{{ (float) $item->stok_total }}"
                                                data-stoktersedia="{{ (float) $item->stok_tersedia }}"
                                                data-satuan="{{ $item->satuan }}" data-kondisi="{{ $item->kondisi }}"
                                                data-spek="{{ $item->spesifikasi }}"
                                                data-rak="{{ $item->lokasi_lemari_rak }}"
                                                data-jurusan="{{ $item->jurusan_id }}"
                                                data-exp="{{ $item->tgl_kedaluwarsa }}"
                                                data-tglpengadaan="{{ $item->tanggal_pengadaan }}"
                                                data-tahunpengadaan="{{ $item->tahun_pengadaan }}"
                                                data-sumber="{{ $item->sumber_dana }}" title="Edit Bahan">
                                                <i class="fas fa-pen-to-square"></i>
                                            </button>
                                        @endif
                                        @if ($canDelete)
                                            <button type="button" class="btn-icon btn-delete-bhp"
                                                data-id="{{ $item->id }}" data-nama="{{ $item->nama_item }}"
                                                title="Hapus Bahan" style="color: var(--danger, #ef4444);">
                                                <i class="fas fa-trash-can"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $canDelete ? 8 : 7 }}" style="padding: 30px; text-align: center; color: var(--text-muted);">
                                    <i class="fas fa-vial-circle-slash mb-2" style="font-size: var(--text-3xl, 1.875rem); opacity: 0.5;"></i>
                                    <div>Belum ada data bahan habis pakai yang cocok.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- 4. Pagination Footer --}}
            @if ($list->hasPages())
                <div class="datatable-footer">
                    @include('partials.datatable-pagination', ['paginator' => $list])
                </div>
            @endif
        </div>

        @include('dashboard.laboran.partials.bhp-modal-form')
    </div>

    @push('scripts')
        <script src="{{ asset('js/laboran-bhp.js') }}?v={{ file_exists(public_path('js/laboran-bhp.js')) ? filemtime(public_path('js/laboran-bhp.js')) : time() }}"></script>
    @endpush
@endsection
