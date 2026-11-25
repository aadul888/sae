@extends('layouts.dashboard')

@section('title', 'Verval PD — SAE (Sistem Aplikasi Edukasi)')

@php
    $pageMeta = [
        'residu' => [
            'icon' => 'fa-file-circle-exclamation',
            'title' => 'Residu Verval PD',
            'desc' => 'Data residu hasil penyandingan Dukcapil (centang hijau/merah apa adanya).',
        ],
        'berkas' => [
            'icon' => 'fa-folder-open',
            'title' => 'Data & Berkas',
            'desc' => 'Identitas lengkap & validasi berkas peserta didik.',
        ],
        'perubahan' => [
            'icon' => 'fa-file-pen',
            'title' => 'Usulan Perubahan Data',
            'desc' => 'Verifikasi usulan perubahan data identitas peserta didik.',
        ],
    ][$page];
@endphp

@section('dash_title', 'Verval PD — ' . $pageMeta['title'])

@section('content')
    <!-- 1. Header Banner & Actions -->
    <div class="dash-banner" style="display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap;">
        <div style="display: flex; align-items: center; gap: 14px;">
            <div style="width: 48px; height: 48px; border-radius: var(--radius-lg, 12px); background: rgba(6,182,212,0.12); color: var(--accent); display: flex; align-items: center; justify-content: center; font-size: var(--text-2xl, 1.5rem); flex-shrink: 0;">
                <i class="fas {{ $pageMeta['icon'] }}"></i>
            </div>
            <div>
                <h2 style="font-size: var(--text-2xl, 1.5rem); font-weight: 800; color: var(--text-color); margin: 0 0 2px 0;">
                    {{ $pageMeta['title'] }}
                </h2>
                <p style="color: var(--text-muted); font-size: var(--text-sm, 0.8125rem); margin: 0;">
                    {{ $pageMeta['desc'] }}
                </p>
            </div>
        </div>

        <div class="dash-banner-actions" style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
            @if ($page === 'residu')
                <button type="button" class="btn btn-outline" id="btnExportResidu"
                    style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px);"
                    title="Cetak Laporan Residu" aria-label="Cetak Laporan Residu">
                    <i class="fas fa-print"></i>
                </button>
                <button type="button" class="btn btn-outline" id="btnTemplateResidu"
                    style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px);"
                    title="Unduh Template Excel Residu" aria-label="Unduh Template Excel Residu">
                    <i class="fas fa-file-arrow-down"></i>
                </button>
                @if ($canCreate)
                    <button type="button" class="btn btn-primary" id="btnImportResidu"
                        style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px);"
                        title="Import Berkas Residu (Excel)" aria-label="Import Berkas Residu">
                        <i class="fas fa-file-import"></i>
                    </button>
                @endif
                @if ($canDelete)
                    <button type="button" class="btn btn-outline" id="btnClearResidu"
                        style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px); color: var(--danger, #ef4444); border-color: rgba(239,68,68,0.4);"
                        title="Hapus Seluruh Data Residu" aria-label="Hapus Seluruh Data Residu">
                        <i class="fas fa-trash-can"></i>
                    </button>
                @endif
            @endif
        </div>
    </div>

    <!-- 2. Stat Grid -->
    <div class="dash-stat-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); margin-bottom: 20px;">
        @if ($page === 'residu')
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(6,182,212,0.15); color: var(--accent);">
                    <i class="fas fa-database"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem);">{{ number_format($stats['total'] ?? 0) }}</div>
                    <div class="dash-stat-label">Total Baris Residu</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(148,163,184,0.15); color: var(--text-muted);">
                    <i class="fas fa-circle-dashed"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem);">{{ number_format($stats['belum'] ?? 0) }}</div>
                    <div class="dash-stat-label">Belum Diproses</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(245,158,11,0.15); color: var(--warning, #f59e0b);">
                    <i class="fas fa-spinner"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem); color: var(--warning, #f59e0b);">{{ number_format($stats['proses'] ?? 0) }}</div>
                    <div class="dash-stat-label">Sedang Proses</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(16,185,129,0.15); color: var(--success, #10b981);">
                    <i class="fas fa-circle-check"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem); color: var(--success, #10b981);">{{ number_format($stats['selesai'] ?? 0) }}</div>
                    <div class="dash-stat-label">Selesai</div>
                </div>
            </div>
        @elseif ($page === 'berkas')
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(59,130,246,0.15); color: var(--primary, #3b82f6);">
                    <i class="fas fa-users"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem);">{{ number_format($stats['total'] ?? 0) }}</div>
                    <div class="dash-stat-label">Total Peserta Didik</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(245,158,11,0.15); color: var(--warning, #f59e0b);">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem); color: var(--warning, #f59e0b);">{{ number_format($stats['menunggu'] ?? 0) }}</div>
                    <div class="dash-stat-label">Menunggu Validasi</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(16,185,129,0.15); color: var(--success, #10b981);">
                    <i class="fas fa-circle-check"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem); color: var(--success, #10b981);">{{ number_format($stats['valid'] ?? 0) }}</div>
                    <div class="dash-stat-label">Berkas Valid</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(239,68,68,0.15); color: var(--danger, #ef4444);">
                    <i class="fas fa-circle-xmark"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem); color: var(--danger, #ef4444);">{{ number_format($stats['tidak_valid'] ?? 0) }}</div>
                    <div class="dash-stat-label">Berkas Ditolak</div>
                </div>
            </div>
        @else
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(59,130,246,0.15); color: var(--primary, #3b82f6);">
                    <i class="fas fa-file-pen"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem);">{{ number_format($stats['total'] ?? 0) }}</div>
                    <div class="dash-stat-label">Total Siswa Mengusulkan</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(245,158,11,0.15); color: var(--warning, #f59e0b);">
                    <i class="fas fa-clock-rotate-left"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem); color: var(--warning, #f59e0b);">{{ number_format($stats['menunggu'] ?? 0) }}</div>
                    <div class="dash-stat-label">Menunggu Review</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(16,185,129,0.15); color: var(--success, #10b981);">
                    <i class="fas fa-circle-check"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem); color: var(--success, #10b981);">{{ number_format($stats['disetujui'] ?? 0) }}</div>
                    <div class="dash-stat-label">Disetujui (Siap Dapodik)</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(239,68,68,0.15); color: var(--danger, #ef4444);">
                    <i class="fas fa-circle-xmark"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem); color: var(--danger, #ef4444);">{{ number_format($stats['ditolak'] ?? 0) }}</div>
                    <div class="dash-stat-label">Ditolak</div>
                </div>
            </div>
        @endif
    </div>

    <!-- 3. Tab Navigasi: Data & Riwayat Submodul -->
    <div class="periode-nav-wrapper" style="margin-bottom: 20px;">
        <div class="periode-nav-desktop">
            <a href="{{ route('dashboard.verval-pd.' . $page, ['tab' => 'data']) }}"
                class="periode-nav-tab {{ $activeTab === 'data' ? 'active' : '' }}">
                <i class="fas fa-table-list"></i> Data
            </a>
            <a href="{{ route('dashboard.verval-pd.' . $page, ['tab' => 'riwayat']) }}"
                class="periode-nav-tab {{ $activeTab === 'riwayat' ? 'active' : '' }}">
                <i class="fas fa-clock-rotate-left"></i> Riwayat
            </a>
        </div>
        <div class="periode-nav-mobile">
            <div class="periode-mobile-select-box" style="margin-bottom: 0;">
                <label><i class="fas fa-layer-group text-primary me-1"></i> Pilih Tampilan:</label>
                <select class="periode-mobile-select form-select"
                    onchange="if(this.value) window.location.href=this.value;"
                    style="font-size: var(--text-base, 0.875rem); height: 40px; border-radius: var(--radius-md, 8px);">
                    <option value="{{ route('dashboard.verval-pd.' . $page, ['tab' => 'data']) }}" {{ $activeTab === 'data' ? 'selected' : '' }}>
                        📋 Data
                    </option>
                    <option value="{{ route('dashboard.verval-pd.' . $page, ['tab' => 'riwayat']) }}" {{ $activeTab === 'riwayat' ? 'selected' : '' }}>
                        🕘 Riwayat Aktivitas
                    </option>
                </select>
            </div>
        </div>
    </div>

    <!-- 4. Datatable Card -->
    <div class="card datatable-card-wrapper" id="tableDataContainer">
        @if ($activeTab === 'data')
            <div class="datatable-toolbar">
                <div style="display: flex; flex-wrap: wrap; gap: 12px; justify-content: space-between; align-items: center;">
                    <div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
                        <div class="toolbar-entries">
                            <label for="perPageSelect" style="margin: 0;">Tampilkan</label>
                            <select id="perPageSelect" name="perPage" class="per-page-select" style="height: 38px;">
                                @foreach ([10, 15, 25, 50, 100] as $n)
                                    <option value="{{ $n }}" {{ ($perPage ?? 25) == $n ? 'selected' : '' }}>{{ $n }}</option>
                                @endforeach
                            </select>
                            <span>entri</span>
                        </div>

                        @if ($page === 'residu')
                            <select id="filterStatus" name="status" class="toolbar-filter-select" style="height: 38px;">
                                <option value="">Semua Status Proses</option>
                                <option value="belum" {{ $status === 'belum' ? 'selected' : '' }}>Belum Diproses</option>
                                <option value="proses" {{ $status === 'proses' ? 'selected' : '' }}>Sedang Proses</option>
                                <option value="selesai" {{ $status === 'selesai' ? 'selected' : '' }}>Selesai</option>
                            </select>
                        @elseif ($page === 'berkas')
                            <select id="filterRombel" name="rombel" class="toolbar-filter-select" style="height: 38px;">
                                <option value="">Semua Rombel</option>
                                @foreach ($filterRombel as $r)
                                    <option value="{{ $r }}" {{ $rombel === $r ? 'selected' : '' }}>{{ $r }}</option>
                                @endforeach
                            </select>
                            <select id="filterStatus" name="status" class="toolbar-filter-select" style="height: 38px;">
                                <option value="">Semua Status Validasi</option>
                                <option value="lengkap" {{ $status === 'lengkap' ? 'selected' : '' }}>Berkas Lengkap &amp; Valid</option>
                                <option value="belum_lengkap" {{ $status === 'belum_lengkap' ? 'selected' : '' }}>Belum Lengkap</option>
                                <option value="menunggu" {{ $status === 'menunggu' ? 'selected' : '' }}>Menunggu Validasi</option>
                                <option value="tidak_valid" {{ $status === 'tidak_valid' ? 'selected' : '' }}>Ada Berkas Ditolak</option>
                            </select>
                        @elseif ($page === 'perubahan')
                            <select id="filterRombel" name="rombel" class="toolbar-filter-select" style="height: 38px;">
                                <option value="">Semua Rombel</option>
                                @foreach ($filterRombel as $r)
                                    <option value="{{ $r }}" {{ $rombel === $r ? 'selected' : '' }}>{{ $r }}</option>
                                @endforeach
                            </select>
                            <select id="filterStatus" name="status" class="toolbar-filter-select" style="height: 38px;">
                                <option value="">Semua Status Usulan</option>
                                <option value="menunggu" {{ $status === 'menunggu' ? 'selected' : '' }}>Menunggu Review</option>
                                <option value="disetujui" {{ $status === 'disetujui' ? 'selected' : '' }}>Disetujui (Siap Dapodik)</option>
                                <option value="sudah_ke_dapodik" {{ $status === 'sudah_ke_dapodik' ? 'selected' : '' }}>Selesai di Dapodik</option>
                                <option value="ditolak" {{ $status === 'ditolak' ? 'selected' : '' }}>Ditolak</option>
                            </select>
                        @endif

                        @if (!empty($q) || !empty($rombel) || !empty($status))
                            <a href="{{ route('dashboard.verval-pd.' . $page, ['tab' => 'data']) }}"
                                class="btn btn-outline btn-reset-filter"
                                style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px);"
                                title="Reset filter" data-live-reset>
                                <i class="fas fa-undo"></i>
                            </a>
                        @endif

                        @if ($page === 'residu')
                            <button type="button" id="btnCetakTerpilihResidu" class="btn btn-primary"
                                style="height: 38px; padding: 0 12px; display: none; align-items: center; gap: 6px; border-radius: var(--radius-md, 8px);"
                                title="Cetak baris yang dicentang" disabled>
                                <i class="fas fa-print"></i> <span id="residuSelectedCount">0</span> Terpilih
                            </button>
                        @endif
                    </div>

                    <div class="live-search-wrap" style="height: 38px;">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" id="liveSearch" name="q" placeholder="Cari nama / NISN / NIK..."
                            value="{{ $q ?? '' }}" autocomplete="off">
                        <button type="button" id="clearSearch" class="clear-search {{ !empty($q) ? 'visible' : '' }}" title="Hapus pencarian">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>

            @include('dashboard.verval-pd.partials.tab-' . $page)
        @else
            @include('dashboard.verval-pd.partials.tab-riwayat', ['riwayatList' => $riwayatList])
        @endif
    </div>

    @if ($page === 'residu')
        @include('dashboard.verval-pd.partials.modal-import-residu')
    @else
        @include('dashboard.verval-pd.partials.modals')
    @endif
    @include('dashboard.verval-pd.partials.modal-pdf-viewer')
@endsection

@push('styles')
    <link rel="stylesheet"
        href="{{ asset('css/wali-kelas.css') }}?v={{ file_exists(public_path('css/wali-kelas.css')) ? filemtime(public_path('css/wali-kelas.css')) : time() }}">
@endpush

@push('scripts')
    <script src="{{ asset('js/verval-pd.js') }}?v={{ file_exists(public_path('js/verval-pd.js')) ? filemtime(public_path('js/verval-pd.js')) : time() }}"></script>
@endpush
