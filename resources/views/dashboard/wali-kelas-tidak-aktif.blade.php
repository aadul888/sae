@extends('layouts.dashboard')

@section('title', 'Wali Kelas — Peserta Didik Tidak Aktif — SAE')
@section('dash_title', 'Wali Kelas — Peserta Didik Tidak Aktif')

@section('content')
    <!-- Header Banner -->
    <div class="dash-banner">
        <div>
            <h2 style="font-size: var(--text-2xl, 1.5rem); font-weight: 800; color: var(--text-color); margin-bottom: 4px;">
                <i class="fas fa-user-xmark text-primary me-2"></i> Wali Kelas — Peserta Didik Tidak Aktif
            </h2>
            <p style="color: var(--text-muted); font-size: var(--text-base, 0.875rem); margin: 0;">
                @if ($hasRombel && $activeRombel)
                    Kelas Binaan: <strong style="color: var(--text-color);">{{ $activeRombel->nama }}</strong>
                    &bull; Wali Kelas: <strong style="color: var(--primary);">{{ $waliNama }}</strong>
                    &bull; Riwayat peserta didik nonaktif (Alumni / Mutasi).
                @else
                    Direktori riwayat peserta didik tidak aktif kelas binaan wali kelas.
                @endif
            </p>
        </div>
        <div class="dash-banner-actions" style="display: flex; gap: 10px; align-items: center;">
            @if ($isAdmin && $rombelList->isNotEmpty())
                <div style="display: flex; align-items: center; gap: 8px;">
                    <label for="adminRombelSelect"
                        style="font-size: var(--text-sm, 0.8125rem); font-weight: 600; color: var(--text-muted); margin: 0; white-space: nowrap;">
                        <i class="fas fa-filter me-1"></i> Pilih Rombel:
                    </label>
                    <select id="adminRombelSelect" class="form-control"
                        style="font-size: var(--text-base, 0.875rem); padding: 6px 12px; border-radius: var(--radius-md, 8px); border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-color); min-width: 170px;">
                        @foreach ($rombelList as $r)
                            <option value="{{ $r->rombongan_belajar_id }}"
                                {{ $activeRombel?->rombongan_belajar_id === $r->rombongan_belajar_id ? 'selected' : '' }}>
                                {{ $r->nama }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif
        </div>
    </div>

    @if (!$hasRombel)
        <div class="card" style="padding: 40px 20px; text-align: center; margin-top: 20px;">
            <div
                style="width: 70px; height: 70px; border-radius: 50%; background: rgba(245, 158, 11, 0.15); color: var(--warning, #f59e0b); display: inline-flex; align-items: center; justify-content: center; font-size: var(--text-3xl, 1.875rem); margin-bottom: 16px;">
                <i class="fas fa-triangle-exclamation"></i>
            </div>
            <h3 style="font-size: var(--text-xl, 1.25rem); font-weight: 700; color: var(--text-color); margin-bottom: 8px;">Belum Ada Rombel
                Binaan Terdaftar</h3>
            <p style="color: var(--text-muted); font-size: var(--text-base, 0.875rem); max-width: 540px; margin: 0 auto 20px;">
                Akun Anda belum terikat dengan rombongan belajar aktif sebagai Wali Kelas pada data Dapodik sekolah. Silakan
                hubungi Administrator sistem atau Operator Dapodik untuk pembaruan penugasan.
            </p>
        </div>
    @else
        <!-- Summary Stats Grid -->
        <div class="dash-stat-grid"
            style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); margin-bottom: 20px;">
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(239,68,68,0.15); color: var(--danger, #ef4444);">
                    <i class="fas fa-user-slash"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem);">
                        {{ number_format($summary['total'], 0, ',', '.') }}
                    </div>
                    <div class="dash-stat-label">Total Peserta Didik Tidak Aktif</div>
                </div>
            </div>

            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(16,185,129,0.15); color: var(--success, #10b981);">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem);">
                        {{ number_format($summary['alumni'], 0, ',', '.') }}
                    </div>
                    <div class="dash-stat-label">Alumni / Lulus</div>
                </div>
            </div>

            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(245,158,11,0.15); color: var(--warning, #f59e0b);">
                    <i class="fas fa-arrow-right-from-bracket"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem);">
                        {{ number_format($summary['mutasi'], 0, ',', '.') }}
                    </div>
                    <div class="dash-stat-label">Mutasi / Keluar</div>
                </div>
            </div>

            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(99,102,241,0.15); color: var(--primary);">
                    <i class="fas fa-camera"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem);">
                        {{ number_format($summary['berfoto'], 0, ',', '.') }}
                    </div>
                    <div class="dash-stat-label">Arsip Pasfoto</div>
                </div>
            </div>
        </div>

        <!-- Unified Datatable Peserta Didik Tidak Aktif (Toolbar + Table + Pagination dalam 1 Card Terpadu) -->
        <div class="card datatable-card-wrapper" id="tableDataContainer">
            <!-- 1. Toolbar Header (Entries, Filters & Live Search) -->
            <div class="datatable-toolbar">
                <div
                    style="display: flex; flex-wrap: wrap; gap: 12px; justify-content: space-between; align-items: center;">
                    <div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
                        <div class="toolbar-entries">
                            <label for="perPageSelect"
                                style="margin: 0; font-size: var(--text-base, 0.875rem); color: var(--text-muted);">Tampilkan</label>
                            <select id="perPageSelect" name="perPage" class="per-page-select" style="height: 38px;">
                                @foreach ([10, 15, 25, 50, 100] as $n)
                                    <option value="{{ $n }}" {{ $perPage == $n ? 'selected' : '' }}>
                                        {{ $n }}</option>
                                @endforeach
                            </select>
                            <span style="font-size: var(--text-base, 0.875rem); color: var(--text-muted);">entri</span>
                        </div>

                        <select id="filterStatus" name="status" class="toolbar-filter-select"
                            style="min-width: 140px; height: 38px;">
                            <option value="">Semua Status</option>
                            <option value="Alumni" {{ $status === 'Alumni' ? 'selected' : '' }}>Alumni / Lulus</option>
                            <option value="Mutasi" {{ $status === 'Mutasi' ? 'selected' : '' }}>Mutasi / Keluar</option>
                        </select>

                        @if ($filterTahun->isNotEmpty())
                            <select id="filterTahun" name="tahun" class="toolbar-filter-select"
                                style="min-width: 130px; height: 38px;">
                                <option value="">Semua Tahun</option>
                                @foreach ($filterTahun as $th)
                                    <option value="{{ $th }}" {{ $tahun === $th ? 'selected' : '' }}>Tahun
                                        {{ $th }}</option>
                                @endforeach
                            </select>
                        @endif

                        @if ($q || $status || $tahun)
                            <a href="{{ route('dashboard.wali-kelas.peserta-didik-tidak-aktif.index', $isAdmin ? ['rombel_id' => $activeRombel?->rombongan_belajar_id] : []) }}"
                                class="btn btn-outline btn-reset-filter"
                                style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px);"
                                title="Reset filter" data-live-reset>
                                <i class="fas fa-undo"></i>
                            </a>
                        @endif
                    </div>

                    <!-- Live Search Box -->
                    <div class="live-search-wrap" style="height: 38px;">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" id="liveSearch" name="q" placeholder="Cari nama / NISN / NIK..."
                            value="{{ $q }}" autocomplete="off">
                        <button type="button" id="clearSearch" class="clear-search {{ $q ? 'visible' : '' }}"
                            title="Hapus pencarian">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- 2. Konten Datatable Responsive Stack -->
            <div class="table-responsive-stack" style="overflow-x: auto; width: 100%;">
                <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                    <thead>
                        <tr style="background: rgba(255, 255, 255, 0.02); border-bottom: 1px solid var(--border-color);">
                            @php
                                $cols = [
                                    ['nama', 'Nama Lengkap'],
                                    ['nisn', 'NISN / NIPD'],
                                    ['jenis_kelamin', 'L/P'],
                                    ['nama_rombel_terakhir', 'Rombel Terakhir'],
                                    ['tingkat_pendidikan_terakhir', 'Tingkat'],
                                    ['tahun_lulus', 'Status & Tanggal'],
                                ];
                            @endphp
                            @foreach ($cols as [$key, $label])
                                <th class="sortable-th {{ $sort === $key ? 'sorted' : '' }}"
                                    style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; {{ $key === 'jenis_kelamin' ? 'text-align: center;' : '' }}"
                                    data-sort="{{ $key }}">
                                    {{ $label }}
                                    <span class="sort-icon">{!! $sort === $key ? ($sortDir === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                                </th>
                            @endforeach
                            <th
                                style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Kontak &amp; Alamat
                            </th>
                            <th
                                style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: right;">
                                Aksi
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($list as $item)
                            <tr style="border-bottom: 1px solid var(--border-color); transition: background 0.2s ease;">
                                <!-- Kolom Nama Lengkap & Foto -->
                                <td class="cell-pd-nama"
                                    style="padding: 14px 18px; font-weight: 700; color: var(--text-color); font-size: var(--text-base, 0.875rem);"
                                    data-label="Nama">
                                    <div class="pd-main-wrapper" style="display: flex; align-items: center; gap: 12px;">
                                        @if (!empty($item->foto_url))
                                            <div class="pd-foto-thumb" id="pdFotoThumb_{{ $item->peserta_didik_id }}"
                                                onclick="openPhotoPreviewModal('{{ $item->foto_url }}', '{{ addslashes($item->nama) }}')"
                                                title="Klik untuk memperbesar pasfoto"
                                                style="width: 42px; height: 42px; border-radius: var(--radius-lg, 12px); background: repeating-conic-gradient(#2a3447 0% 25%, #182030 0% 50%) 50% / 8px 8px; border: 1.5px solid var(--border-color); display: flex; align-items: center; justify-content: center; overflow: hidden; cursor: pointer; flex-shrink: 0; box-shadow: 0 2px 6px rgba(0,0,0,0.25); transition: transform 0.2s ease, border-color 0.2s ease;">
                                                <img src="{{ $item->foto_url }}" alt="Foto {{ $item->nama }}"
                                                    style="width: 100%; height: 100%; object-fit: cover;">
                                            </div>
                                        @else
                                            <div class="pd-foto-thumb empty"
                                                style="width: 42px; height: 42px; border-radius: var(--radius-lg, 12px); background: rgba(239,68,68,0.08); border: 1.5px dashed rgba(239,68,68,0.4); display: flex; align-items: center; justify-content: center; color: var(--danger, #ef4444); font-size: var(--text-lg, 1.125rem); flex-shrink: 0;">
                                                <i class="fas fa-user-slash" style="opacity: 0.7;"></i>
                                            </div>
                                        @endif
                                        <div class="pd-info">
                                            <div class="pd-title-row"
                                                style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                                                <span class="pd-nama"
                                                    style="font-weight: 700; color: var(--text-color);">{{ $item->nama }}</span>
                                                @if ($item->status_keluar === 'Alumni')
                                                    <span class="badge"
                                                        style="font-size: var(--text-2xs, 0.6875rem); background: rgba(16,185,129,0.12); color: var(--success, #10b981); padding: 2px 6px; white-space: nowrap;">
                                                        <i class="fas fa-graduation-cap me-1"></i>Alumni
                                                    </span>
                                                @else
                                                    <span class="badge"
                                                        style="font-size: var(--text-2xs, 0.6875rem); background: rgba(245,158,11,0.12); color: var(--warning, #f59e0b); padding: 2px 6px; white-space: nowrap;">
                                                        <i
                                                            class="fas fa-right-from-bracket me-1"></i>{{ $item->status_keluar ?: 'Keluar' }}
                                                    </span>
                                                @endif
                                            </div>
                                            @if ($item->nik)
                                                <div
                                                    style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); font-family: monospace; margin-top: 2px;">
                                                    <span class="copyable" data-copy="{{ $item->nik }}"
                                                        data-label="NIK" title="Klik untuk salin NIK">
                                                        NIK: {{ $item->nik }}
                                                    </span>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </td>

                                <!-- Kolom NISN / NIPD -->
                                <td class="cell-pd-nisn"
                                    style="padding: 14px 18px; font-family: monospace; font-size: var(--text-base, 0.875rem); color: var(--primary);"
                                    data-label="NISN/NIPD">
                                    <div class="cell-col-right">
                                        <div>
                                            @if ($item->nisn)
                                                <span class="copyable" data-copy="{{ $item->nisn }}" data-label="NISN"
                                                    title="Klik untuk salin NISN">{{ $item->nisn }}</span>
                                            @else
                                                -
                                            @endif
                                        </div>
                                        @if ($item->nipd)
                                            <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted);">
                                                <span class="copyable" data-copy="{{ $item->nipd }}" data-label="NIPD"
                                                    title="Klik untuk salin NIPD">NIPD: {{ $item->nipd }}</span>
                                            </div>
                                        @endif
                                    </div>
                                </td>

                                <!-- Kolom Gender L/P -->
                                <td class="cell-pd-gender" style="padding: 14px 18px; text-align: center;"
                                    data-label="L/P">
                                    <span
                                        class="badge {{ $item->jenis_kelamin === 'L' ? 'badge-primary' : 'badge-danger' }}"
                                        style="font-size: var(--text-xs, 0.75rem); padding: 2px 7px;">
                                        {{ $item->jenis_kelamin ?: '-' }}
                                    </span>
                                </td>

                                <!-- Kolom Rombel Terakhir -->
                                <td class="cell-pd-rombel"
                                    style="padding: 14px 18px; font-weight: 600; color: var(--text-color); font-size: var(--text-base, 0.875rem);"
                                    data-label="Rombel">
                                    <span class="badge badge-outline" style="font-size: var(--text-sm, 0.8125rem); padding: 3px 8px;">
                                        {{ $item->nama_rombel_terakhir ?: '-' }}
                                    </span>
                                </td>

                                <!-- Kolom Tingkat -->
                                <td class="cell-pd-tingkat"
                                    style="padding: 14px 18px; font-size: var(--text-sm, 0.8125rem); color: var(--text-muted);"
                                    data-label="Tingkat">
                                    {{ $item->tingkat_pendidikan_terakhir ?: '-' }}
                                </td>

                                <!-- Kolom Status & Tanggal -->
                                <td class="cell-pd-ttl"
                                    style="padding: 14px 18px; font-size: var(--text-sm, 0.8125rem); color: var(--text-muted);"
                                    data-label="Status & Tanggal">
                                    <div class="cell-col-right">
                                        <div style="font-weight: 600; color: var(--text-color);">
                                            {{ $item->status_keluar === 'Alumni' ? 'Lulus ' . ($item->tahun_lulus ?: '-') : ($item->status_keluar ?: 'Keluar') }}
                                        </div>
                                        <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted);">
                                            {{ $item->tanggal_keluar ? date('d/m/Y', strtotime($item->tanggal_keluar)) : '-' }}
                                        </div>
                                    </div>
                                </td>

                                <!-- Kolom Kontak & Alamat -->
                                <td class="cell-pd-kontak"
                                    style="padding: 14px 18px; font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); max-width: 220px;"
                                    data-label="Kontak & Alamat">
                                    <div class="cell-col-right">
                                        @if ($item->nomor_telepon_seluler)
                                            <div>
                                                <i class="fas fa-phone-alt me-1 text-primary"
                                                    style="font-size: var(--text-xs, 0.75rem);"></i>
                                                <span class="copyable" data-copy="{{ $item->nomor_telepon_seluler }}"
                                                    data-label="No HP"
                                                    title="Klik untuk salin No HP">{{ $item->nomor_telepon_seluler }}</span>
                                            </div>
                                        @endif
                                        @if ($item->alamat_jalan)
                                            <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 200px;"
                                                title="{{ $item->alamat_jalan }}">
                                                <i class="fas fa-map-marker-alt me-1"
                                                    style="font-size: var(--text-xs, 0.75rem);"></i>{{ $item->alamat_jalan }}
                                            </div>
                                        @endif
                                        @if (!$item->nomor_telepon_seluler && !$item->alamat_jalan)
                                            -
                                        @endif
                                    </div>
                                </td>

                                <!-- Kolom Aksi -->
                                <td class="cell-pd-aksi" style="padding: 14px 18px; text-align: right;"
                                    data-label="Aksi">
                                    <div class="table-actions">
                                        @if (!empty($item->foto_url))
                                            <button type="button" class="btn-icon" title="Lihat Pasfoto"
                                                onclick="openPhotoPreviewModal('{{ $item->foto_url }}', '{{ addslashes($item->nama) }}')">
                                                <i class="fas fa-image" style="color: var(--success, #10b981);"></i>
                                            </button>
                                        @endif
                                        <button type="button" class="btn-icon btn-detail-tidak-aktif"
                                            onclick="openBiodataModal('{{ $item->id }}')"
                                            data-id="{{ $item->id }}" title="Lihat Detail Riwayat">
                                            <i class="fas fa-id-card"></i>
                                        </button>
                                        @if (!empty($item->nisn))
                                            <button type="button" class="btn-icon"
                                                title="Pratinjau / Cetak Kartu Pelajar Digital"
                                                onclick="openKartuPelajarModal('{{ $item->nisn }}')">
                                                <i class="fas fa-address-card" style="color: var(--accent, #0284c7);"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8"
                                    style="padding: 30px; text-align: center; color: var(--text-muted); font-size: var(--text-base, 0.875rem);">
                                    <i class="fas fa-user-check mb-2" style="font-size: var(--text-3xl, 1.875rem); opacity: 0.5;"></i>
                                    <div>Tidak ada data peserta didik tidak aktif di kelas binaan ini.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                <!-- Footer Keterangan Entri -->
                @if ($total > 0 && !$list->hasPages())
                    <div
                        style="padding: 14px 18px; border-top: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                        <div class="total-count-text" style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted);">
                            Menampilkan <strong>{{ $list->firstItem() ?: 0 }}</strong> sampai
                            <strong>{{ $list->lastItem() ?: 0 }}</strong> dari
                            <strong>{{ number_format($total, 0, ',', '.') }}</strong> peserta didik
                        </div>
                    </div>
                @endif
            </div>

            <!-- 3. Footer Paginasi Terpadu Datatable -->
            @if ($list->hasPages())
                <div class="datatable-footer">
                    @include('partials.datatable-pagination', ['paginator' => $list])
                </div>
            @endif
        </div>
    @endif

    {{-- Modal Biodata Peserta Didik Tidak Aktif / Alumni --}}
    <div id="biodataModal" class="modal-backdrop"
        style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.65); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
        <div class="card"
            style="max-width: 720px; width: 94%; max-height: 85vh; display: flex; flex-direction: column; margin: 0; border-radius: var(--radius-xl, 16px); padding: 22px; box-shadow: 0 20px 40px rgba(0,0,0,0.4); border: 1px solid var(--border-color);">
            <div
                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; padding-bottom: 12px; border-bottom: 1px solid var(--border-color);">
                <div style="display: flex; align-items: center; gap: 14px;">
                    <div id="bioFotoContainer"
                        style="width: 50px; height: 50px; border-radius: var(--radius-lg, 12px); background: rgba(99,102,241,0.12); display: flex; align-items: center; justify-content: center; overflow: hidden; border: 1.5px solid var(--border-color); flex-shrink: 0; box-shadow: 0 2px 8px rgba(0,0,0,0.25);">
                        <i class="fas fa-user-graduate text-primary" style="font-size: var(--text-xl, 1.25rem);"></i>
                    </div>
                    <div>
                        <h3 id="bioNama"
                            style="font-size: var(--text-lg, 1.125rem); font-weight: 700; color: var(--text-color); margin: 0;">
                            Biodata Arsip Peserta Didik</h3>
                        <div id="bioRombel" style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); margin-top: 2px;">-</div>
                    </div>
                </div>
                <button type="button" onclick="closeBiodataModal()"
                    style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: var(--text-lg, 1.125rem);">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div id="bioLoading" style="text-align: center; padding: 40px; color: var(--text-muted);">
                <i class="fas fa-spinner fa-spin me-2" style="font-size: var(--text-2xl, 1.5rem);"></i>
                <div>Memuat rincian arsip peserta didik...</div>
            </div>

            <div id="bioContent" style="overflow-y: auto; flex: 1; display: none; font-size: var(--text-base, 0.875rem);">
                <div style="margin-bottom: 12px;">
                    <div
                        style="font-weight: 700; color: var(--primary); font-size: var(--text-sm, 0.8125rem); margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.5px;">
                        <i class="fas fa-user me-1"></i> Data Pribadi Peserta Didik
                    </div>
                    <table style="width: 100%; border-collapse: collapse; margin-bottom: 8px;">
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted); width: 140px;">NISN / NIPD</td>
                            <td id="bioNisn" style="font-weight: 600; font-family: monospace; color: var(--primary);">-
                            </td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted);">NIK</td>
                            <td id="bioNik" style="font-family: monospace;">-</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted);">Jenis Kelamin</td>
                            <td id="bioJk">-</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted);">Tempat, Tgl Lahir</td>
                            <td id="bioTtl">-</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted);">Agama</td>
                            <td id="bioAgama">-</td>
                        </tr>
                    </table>
                </div>

                <div style="margin-bottom: 12px; padding-top: 8px; border-top: 1px dashed var(--border-color);">
                    <div
                        style="font-weight: 700; color: var(--primary); font-size: var(--text-sm, 0.8125rem); margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.5px;">
                        <i class="fas fa-graduation-cap me-1"></i> Status Pengarsipan &amp; Akademik
                    </div>
                    <table style="width: 100%; border-collapse: collapse; margin-bottom: 8px;">
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted); width: 140px;">Status Keluar</td>
                            <td id="bioStatus">-</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted);">Tahun Lulus / Keluar</td>
                            <td id="bioTahunLulus">-</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted);">Rombel Terakhir</td>
                            <td id="bioRombelDetail">-</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted);">Keterangan / Alasan</td>
                            <td id="bioAlasan">-</td>
                        </tr>
                    </table>
                </div>

                <div style="margin-bottom: 12px; padding-top: 8px; border-top: 1px dashed var(--border-color);">
                    <div
                        style="font-weight: 700; color: var(--primary); font-size: var(--text-sm, 0.8125rem); margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.5px;">
                        <i class="fas fa-address-book me-1"></i> Kontak &amp; Domisili
                    </div>
                    <table style="width: 100%; border-collapse: collapse; margin-bottom: 8px;">
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted); width: 140px;">No. HP / Telepon</td>
                            <td id="bioHp">-</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted);">Email</td>
                            <td id="bioEmail">-</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted);">Alamat Jalan</td>
                            <td id="bioAlamat">-</td>
                        </tr>
                    </table>
                </div>
            </div>

            <div
                style="padding-top: 14px; border-top: 1px solid var(--border-color); display: flex; justify-content: flex-end;">
                <button type="button" onclick="closeBiodataModal()" class="btn btn-outline"
                    style="padding: 8px 18px; font-size: var(--text-base, 0.875rem);">
                    Tutup
                </button>
            </div>
        </div>
    </div>
    {{-- Modal Pratinjau Kartu Pelajar Digital --}}
    @include('kartu-pelajar.modal-preview')

    {{-- Modal Preview Pasfoto Siswa Tidak Aktif --}}
    <div id="photoPreviewModal" class="modal-backdrop"
        style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.7); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);"
        onclick="if(typeof closePhotoPreviewModal === 'function') closePhotoPreviewModal();">
        <div class="card"
            style="max-width: 420px; width: 90%; text-align: center; border-radius: var(--radius-xl, 16px); padding: 20px; box-shadow: 0 16px 40px rgba(0,0,0,0.5);"
            onclick="event.stopPropagation();">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
                <h4 id="photoPreviewName"
                    style="font-size: var(--text-md, 1rem); font-weight: 700; color: var(--text-color); margin: 0;">Pasfoto</h4>
                <button type="button"
                    onclick="if(typeof closePhotoPreviewModal === 'function') closePhotoPreviewModal();"
                    style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: var(--text-lg, 1.125rem);">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div
                style="width: 100%; max-height: 480px; overflow: hidden; border-radius: var(--radius-lg, 12px); border: 1px solid var(--border-color); background: #000; margin-bottom: 12px;">
                <img id="photoPreviewImg" src="" alt="Pasfoto"
                    style="width: 100%; height: auto; max-height: 480px; object-fit: contain;">
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/kartu-pelajar.css') }}">
@endpush

@push('scripts')
    <script
        src="{{ asset('js/wali-kelas-tidak-aktif.js') }}?v={{ file_exists(public_path('js/wali-kelas-tidak-aktif.js')) ? filemtime(public_path('js/wali-kelas-tidak-aktif.js')) : time() }}">
    </script>
@endpush
