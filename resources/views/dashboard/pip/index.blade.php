@extends('layouts.dashboard')

@section('title', 'Usulan & Riwayat PIP — SAE')
@section('dash_title', 'Usulan PIP')

@section('content')
    @php
        $routeParams = fn(array $extra = []) => array_filter(
            array_merge(
                ['rombel_id' => $isAdmin ? $activeRombel?->rombongan_belajar_id : null, 'tahun' => $activeTahun],
                $extra,
            ),
            fn($v) => $v !== null && $v !== '',
        );
    @endphp

    <!-- 1. Header Banner -->
    <div class="dash-banner"
        style="display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; margin-bottom: 20px;">
        <div style="display: flex; align-items: center; gap: 14px;">
            <div
                style="width: 48px; height: 48px; border-radius: var(--radius-lg, 12px); background: rgba(16,185,129,0.12); color: #10b981; display: flex; align-items: center; justify-content: center; font-size: var(--text-2xl, 1.5rem); flex-shrink: 0;">
                <i class="fas fa-hand-holding-dollar"></i>
            </div>
            <div>
                <h2 style="font-size: var(--text-xl, 1.25rem); font-weight: 800; color: var(--text-color); margin: 0;">
                    Usulan &amp; Riwayat PIP
                </h2>
                <div style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); margin-top: 2px;">
                    @if ($isAdmin)
                        Kelola kuota, pantau usulan, verifikasi pengajuan, dan riwayat penerima PIP.
                    @else
                        Usulkan peserta didik kelas
                        <strong>{{ $activeRombel->nama ?? '-' }}</strong> beserta kriteria alasan dan urutan prioritasnya.
                    @endif
                </div>
            </div>
        </div>

        <div class="dash-banner-actions" style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
            @if ($tab === 'dashboard')
                <button type="button" class="btn btn-primary" onclick="openTambahUsulan()"
                    style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px); font-size: var(--text-md, 1rem);"
                    title="Tambah Usulan Siswa PIP Baru">
                    <i class="fas fa-plus"></i>
                </button>
                @if ($isAdmin)
                    <button type="button" class="btn btn-outline" onclick="openImportRiwayat()"
                        style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px); font-size: var(--text-md, 1rem);"
                        title="Import Excel SK SiPintar Kemdikbud">
                        <i class="fas fa-file-excel text-success"></i>
                    </button>
                @endif
            @elseif ($tab === 'usulan')
                <button type="button" class="btn btn-outline" onclick="pipExportUsulanExcel()"
                    style="height: 38px; padding: 0 14px; display: inline-flex; align-items: center; gap: 6px; border-radius: var(--radius-md, 8px); font-size: var(--text-base, 0.875rem); font-weight: 700; border-color: #10b981; color: #10b981;"
                    title="Export Seluruh Data Usulan PIP ke Format Excel (.xlsx) Lengkap">
                    <i class="fas fa-file-excel"></i> Export Excel
                </button>
                @if (!$isAdmin && !empty($isWaliLocked))
                    <button type="button" class="btn btn-outline" disabled
                        style="height: 38px; padding: 0 14px; display: inline-flex; align-items: center; gap: 6px; border-radius: var(--radius-md, 8px); font-size: var(--text-base, 0.875rem); font-weight: 700; opacity: 0.6; cursor: not-allowed;"
                        title="Pengusulan PIP saat ini terkunci / telah melewati batas waktu pengisian">
                        <i class="fas fa-lock"></i> Usulan Ditutup
                    </button>
                @else
                    <button type="button" class="btn btn-primary" onclick="openTambahUsulan()"
                        style="height: 38px; padding: 0 14px; display: inline-flex; align-items: center; gap: 6px; border-radius: var(--radius-md, 8px); font-size: var(--text-base, 0.875rem); font-weight: 700;"
                        title="Tambah Usulan Siswa PIP Baru">
                        <i class="fas fa-plus"></i> Usulan
                    </button>
                @endif
            @elseif (in_array($tab, ['riwayat', 'riwayat_alumni'], true) && $isAdmin)
                <button type="button" class="btn btn-outline" onclick="openTambahRiwayat()"
                    style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px); font-size: var(--text-md, 1rem);"
                    title="Tambah Data Riwayat Manual">
                    <i class="fas fa-plus"></i>
                </button>
                <button type="button" class="btn btn-primary" onclick="openImportRiwayat()"
                    style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px); font-size: var(--text-md, 1rem);"
                    title="Import Excel SK SiPintar Kemdikbud">
                    <i class="fas fa-file-excel"></i>
                </button>
            @endif
        </div>
    </div>

    <!-- 2. Flash Messages -->
    @if (session('success'))
        <div
            style="background: rgba(16,185,129,0.12); border: 1px solid rgba(16,185,129,0.3); color: #10b981; padding: 12px 16px; border-radius: var(--radius-lg, 12px); margin-bottom: 18px; font-size: var(--text-base, 0.875rem); display: flex; align-items: center; gap: 8px;">
            <i class="fas fa-check-circle"></i>
            <div>{{ session('success') }}</div>
        </div>
    @endif
    @if (session('error'))
        <div
            style="background: rgba(239,68,68,0.12); border: 1px solid rgba(239,68,68,0.3); color: #ef4444; padding: 12px 16px; border-radius: var(--radius-lg, 12px); margin-bottom: 18px; font-size: var(--text-base, 0.875rem); display: flex; align-items: center; gap: 8px;">
            <i class="fas fa-circle-exclamation"></i>
            <div>{{ session('error') }}</div>
        </div>
    @endif

    <!-- 3. Banner Hitung Mundur Realtime & Status Batas Waktu PIP -->
    @include('dashboard.pip.partials.countdown-banner')

    <!-- 4. Segmented Navigation Tabs -->
    <div class="dash-segmented-tabs" style="margin-bottom: 20px;">
        <a href="{{ route('dashboard.pip.index', $routeParams(['tab' => 'dashboard'])) }}"
            class="tab-btn btn {{ $tab === 'dashboard' ? 'btn-primary active' : 'btn-outline' }}">
            <i class="fas fa-chart-pie me-1"></i> Dashboard
        </a>
        <a href="{{ route('dashboard.pip.index', $routeParams(['tab' => 'usulan'])) }}"
            class="tab-btn btn {{ $tab === 'usulan' ? 'btn-primary active' : 'btn-outline' }}">
            <i class="fas fa-list-ol me-1"></i> Usulan
        </a>
        @if ($isAdmin)
            <a href="{{ route('dashboard.pip.index', $routeParams(['tab' => 'kuota'])) }}"
                class="tab-btn btn {{ $tab === 'kuota' ? 'btn-primary active' : 'btn-outline' }}">
                <i class="fas fa-sliders me-1"></i> Kuota Kelas
            </a>
        @endif
        <a href="{{ route('dashboard.pip.index', $routeParams(['tab' => 'riwayat'])) }}"
            class="tab-btn btn {{ $tab === 'riwayat' ? 'btn-primary active' : 'btn-outline' }}">
            <i class="fas fa-clock-rotate-left me-1"></i> Riwayat (Siswa Aktif)
        </a>
        @if ($isAdmin)
            <a href="{{ route('dashboard.pip.index', $routeParams(['tab' => 'riwayat_alumni'])) }}"
                class="tab-btn btn {{ $tab === 'riwayat_alumni' ? 'btn-primary active' : 'btn-outline' }}">
                <i class="fas fa-user-graduate me-1"></i> Riwayat Kelas Lain &amp; Alumni
            </a>
            <a href="{{ route('dashboard.pip.index', $routeParams(['tab' => 'pengaturan'])) }}"
                class="tab-btn btn {{ $tab === 'pengaturan' ? 'btn-primary active' : 'btn-outline' }}">
                <i class="fas fa-gear me-1"></i> Pengaturan
            </a>
        @endif
    </div>

    @if ($tab === 'dashboard')
        @include('dashboard.pip.partials.tab-dashboard')
    @elseif ($tab === 'usulan')
        @include('dashboard.pip.partials.tab-usulan')
    @elseif ($tab === 'kuota' && $isAdmin)
        @include('dashboard.pip.partials.tab-kuota')
    @elseif ($tab === 'riwayat_alumni' && $isAdmin)
        @include('dashboard.pip.partials.tab-riwayat-alumni')
    @elseif ($tab === 'pengaturan' && $isAdmin)
        @include('dashboard.pip.partials.tab-pengaturan')
    @else
        @include('dashboard.pip.partials.tab-riwayat')
    @endif

    @include('dashboard.pip.partials.modals')
@endsection

@push('styles')
    <style>
        /* -------------------------------------------------------------
                 * Standarisasi Konsistensi Ukuran Kartu Statistik PIP (Genap & Rapi)
                 * ------------------------------------------------------------- */
        .dash-stat-grid {
            display: grid !important;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)) !important;
            grid-auto-rows: 1fr !important;
            gap: 14px !important;
            margin-bottom: 20px !important;
            align-items: stretch !important;
        }

        .dash-stat-card {
            min-height: 82px !important;
            height: 100% !important;
            box-sizing: border-box !important;
            padding: 14px 16px !important;
            border-radius: var(--radius-lg, 12px) !important;
            display: flex !important;
            align-items: center !important;
            gap: 12px !important;
        }

        .dash-stat-icon {
            width: 44px !important;
            height: 44px !important;
            min-width: 44px !important;
            min-height: 44px !important;
            border-radius: var(--radius-lg, 12px) !important;
            font-size: var(--text-xl, 1.25rem) !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            flex-shrink: 0 !important;
        }

        .dash-stat-info {
            display: flex !important;
            flex-direction: column !important;
            justify-content: center !important;
            min-width: 0 !important;
            flex: 1 1 auto !important;
        }

        .dash-stat-value {
            font-size: var(--text-2xl, 1.5rem) !important;
            font-weight: 800 !important;
            line-height: 1.2 !important;
            margin-bottom: 2px !important;
            white-space: nowrap !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
        }

        .dash-stat-label {
            font-size: var(--text-sm, 0.8125rem) !important;
            font-weight: 600 !important;
            color: var(--text-muted) !important;
            line-height: 1.25 !important;
            white-space: nowrap !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
        }

        /* -------------------------------------------------------------
                 * Tab Riwayat PIP — Desktop & Responsive Modern Table
                 * ------------------------------------------------------------- */
        .table-pip-riwayat {
            width: 100%;
            border-collapse: collapse;
        }

        .table-pip-riwayat thead th {
            vertical-align: middle;
            white-space: nowrap;
        }

        .table-pip-riwayat tbody tr.pip-riwayat-row {
            transition: background 0.18s ease;
        }

        .table-pip-riwayat tbody tr.pip-riwayat-row:hover {
            background: rgba(99, 102, 241, 0.04);
        }

        .table-pip-riwayat tbody td {
            vertical-align: middle;
            padding: 12px 14px;
            box-sizing: border-box;
        }

        /* Responsive Mobile Card View (< 768px) */
        @media (max-width: 768px) {
            .table-responsive-stack tbody tr.pip-riwayat-row {
                display: flex !important;
                flex-direction: column !important;
                padding: 14px 15px !important;
                margin-bottom: 14px !important;
                border-radius: var(--radius-xl, 16px) !important;
                background: var(--bg-card) !important;
                border: 1px solid var(--border-color) !important;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.03) !important;
            }

            .table-responsive-stack tbody td.cell-pd-no {
                display: none !important;
            }

            .table-responsive-stack tbody td.cell-pd-nama {
                order: 1 !important;
                display: flex !important;
                flex-direction: column !important;
                align-items: stretch !important;
                padding: 0 0 10px 0 !important;
                margin-bottom: 6px !important;
                border-bottom: 1px solid var(--border-color) !important;
                width: 100% !important;
                text-align: left !important;
            }

            .table-responsive-stack tbody td.cell-pd-nama::before {
                display: none !important;
            }

            .table-responsive-stack tbody td.cell-pd-kelas {
                order: 2 !important;
                display: flex !important;
                justify-content: space-between !important;
                align-items: center !important;
                padding: 8px 0 !important;
                border-bottom: 1px dashed rgba(255, 255, 255, 0.08) !important;
                width: 100% !important;
            }

            .table-responsive-stack tbody td.cell-pd-kelas::before {
                content: attr(data-label) !important;
                font-weight: 700 !important;
                font-size: var(--text-xs, 0.75rem) !important;
                color: var(--text-muted) !important;
                text-transform: uppercase !important;
                text-align: left !important;
            }

            .table-responsive-stack tbody td.cell-pd-status {
                order: 3 !important;
                display: flex !important;
                justify-content: space-between !important;
                align-items: flex-start !important;
                padding: 9px 0 !important;
                border-bottom: 1px dashed rgba(255, 255, 255, 0.08) !important;
                width: 100% !important;
            }

            .table-responsive-stack tbody td.cell-pd-status::before {
                content: attr(data-label) !important;
                font-weight: 700 !important;
                font-size: var(--text-xs, 0.75rem) !important;
                color: var(--text-muted) !important;
                text-transform: uppercase !important;
                text-align: left !important;
                margin-top: 3px !important;
            }

            .table-responsive-stack tbody td.cell-pd-status>* {
                text-align: right !important;
                align-items: flex-end !important;
                max-width: 70% !important;
            }

            .table-responsive-stack tbody td.cell-pd-nominal {
                order: 4 !important;
                display: flex !important;
                justify-content: space-between !important;
                align-items: center !important;
                padding: 8px 0 !important;
                border-bottom: 1px dashed rgba(255, 255, 255, 0.08) !important;
                width: 100% !important;
            }

            .table-responsive-stack tbody td.cell-pd-nominal::before {
                content: attr(data-label) !important;
                font-weight: 700 !important;
                font-size: var(--text-xs, 0.75rem) !important;
                color: var(--text-muted) !important;
                text-transform: uppercase !important;
                text-align: left !important;
            }

            .table-responsive-stack tbody td.cell-pd-cair {
                order: 5 !important;
                display: flex !important;
                justify-content: space-between !important;
                align-items: center !important;
                padding: 8px 0 !important;
                border-bottom: 1px dashed rgba(255, 255, 255, 0.08) !important;
                width: 100% !important;
            }

            .table-responsive-stack tbody td.cell-pd-cair::before {
                content: attr(data-label) !important;
                font-weight: 700 !important;
                font-size: var(--text-xs, 0.75rem) !important;
                color: var(--text-muted) !important;
                text-transform: uppercase !important;
                text-align: left !important;
            }

            .table-responsive-stack tbody td.cell-pd-cair>* {
                text-align: right !important;
                align-items: flex-end !important;
            }

            .table-responsive-stack tbody td.cell-pd-aksi {
                order: 10 !important;
                display: flex !important;
                justify-content: space-between !important;
                align-items: center !important;
                width: 100% !important;
                padding: 10px 0 0 0 !important;
                margin-top: 4px !important;
                border-top: 1px solid var(--border-color) !important;
                border-bottom: none !important;
            }

            .table-responsive-stack tbody td.cell-pd-aksi::before {
                content: attr(data-label) !important;
                font-weight: 700 !important;
                font-size: var(--text-xs, 0.75rem) !important;
                color: var(--text-muted) !important;
                text-transform: uppercase !important;
                text-align: left !important;
            }

            .d-block-mobile {
                display: inline-flex !important;
            }
        }

        @media (min-width: 769px) {
            .d-block-mobile {
                display: none !important;
            }
        }
    </style>
@endpush

@push('scripts')
    <script>
        window.PIP_CONFIG = {
            isAdmin: @json($isAdmin),
            tahun: @json($activeTahun),
            rombelId: @json($isAdmin ? $activeRombel?->rombongan_belajar_id : null),
            routes: {
                cariSiswa: @json(Route::has('dashboard.pip.cari-siswa') ? route('dashboard.pip.cari-siswa') : url('/dashboard/pip/cari-siswa')),
                exportUsulan: @json(Route::has('dashboard.pip.export-usulan')
                        ? route('dashboard.pip.export-usulan')
                        : url('/dashboard/pip/usulan/export')),
                storeUsulan: @json(Route::has('dashboard.pip.store-usulan') ? route('dashboard.pip.store-usulan') : url('/dashboard/pip/usulan')),
                prioritas: @json(Route::has('dashboard.pip.update-prioritas')
                        ? route('dashboard.pip.update-prioritas')
                        : url('/dashboard/pip/usulan/prioritas')),
                bulkVerifikasi: @json(Route::has('dashboard.pip.bulk-verifikasi')
                        ? route('dashboard.pip.bulk-verifikasi')
                        : url('/dashboard/pip/usulan/bulk-verifikasi')),
                kuota: @json(Route::has('dashboard.pip.update-kuota') ? route('dashboard.pip.update-kuota') : url('/dashboard/pip/kuota')),
                importRiwayat: @json(Route::has('dashboard.pip.import-riwayat')
                        ? route('dashboard.pip.import-riwayat')
                        : url('/dashboard/pip/riwayat/import')),
                storeRiwayat: @json(Route::has('dashboard.pip.store-riwayat') ? route('dashboard.pip.store-riwayat') : url('/dashboard/pip/riwayat')),
                updatePengaturan: @json(Route::has('dashboard.pip.update-pengaturan')
                        ? route('dashboard.pip.update-pengaturan')
                        : url('/dashboard/pip/pengaturan')),
            },
            urlTemplates: {
                destroyUsulan: @json(Route::has('dashboard.pip.destroy-usulan')
                        ? route('dashboard.pip.destroy-usulan', ['id' => '__ID__'])
                        : url('/dashboard/pip/usulan/__ID__/delete')),
                updateUsulan: @json(Route::has('dashboard.pip.update-usulan')
                        ? route('dashboard.pip.update-usulan', ['id' => '__ID__'])
                        : url('/dashboard/pip/usulan/__ID__/update')),
                riwayatUsulan: @json(Route::has('dashboard.pip.riwayat-usulan')
                        ? route('dashboard.pip.riwayat-usulan', ['id' => '__ID__'])
                        : url('/dashboard/pip/usulan/__ID__/riwayat')),
                verifikasi: @json(Route::has('dashboard.pip.update-verifikasi')
                        ? route('dashboard.pip.update-verifikasi', ['id' => '__ID__'])
                        : url('/dashboard/pip/usulan/__ID__/verifikasi')),
                destroyRiwayat: @json(Route::has('dashboard.pip.destroy-riwayat')
                        ? route('dashboard.pip.destroy-riwayat', ['id' => '__ID__'])
                        : url('/dashboard/pip/riwayat/__ID__/delete')),
            },
        };
    </script>
    <script
        src="{{ asset('js/pip-manage.js') }}?v={{ file_exists(public_path('js/pip-manage.js')) ? filemtime(public_path('js/pip-manage.js')) : time() }}">
    </script>
    <script
        src="{{ asset('js/pip-dashboard-charts.js') }}?v={{ file_exists(public_path('js/pip-dashboard-charts.js')) ? filemtime(public_path('js/pip-dashboard-charts.js')) : time() }}">
    </script>
@endpush
