@extends('layouts.dashboard')

@section('title', 'Update Sistem — Sistem Aplikasi Edukasi (SAE)')
@section('dash_title', 'Update Sistem')

@section('content')
    <!-- Welcome / Header Banner -->
    <div class="dash-banner">
        <div>
            <h2 style="font-size: var(--text-2xl, 1.5rem); font-weight: 800; color: var(--text-color); margin-bottom: 4px;">
                <i class="fas fa-arrows-rotate text-primary me-2"></i> Update Sistem
            </h2>
            <p style="color: var(--text-muted); font-size: var(--text-base, 0.875rem);">
                Deteksi dan instalasi otomatis pembaruan file kode, assets, dan migrasi skema database SAE.
            </p>
        </div>
        <div class="dash-banner-actions">
            <button type="button" id="btnCheckUpdate" class="btn btn-outline"
                style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px); font-size: var(--text-md, 1rem);"
                title="Periksa Pembaruan Sistem" aria-label="Periksa Pembaruan Sistem">
                <i class="fas fa-sync-alt" id="checkIcon"></i>
            </button>
        </div>
    </div>

    <!-- Status Stats Grid -->
    <div class="dash-stat-grid update-stat-grid">
        <div class="dash-stat-card" title="Versi Sistem Terpasang: v{{ $status['current_version'] }}">
            <div class="dash-stat-icon" style="background: rgba(99,102,241,0.15); color: var(--primary);">
                <i class="fas fa-code-branch"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" id="currentVer">
                    v{{ $status['current_version'] }}
                </div>
                <div class="dash-stat-label">Versi Terpasang</div>
            </div>
        </div>

        <div class="dash-stat-card" title="Branch Repositori: {{ $status['has_git'] ? $status['git_branch'] : 'Standalone' }}">
            <div class="dash-stat-icon" style="background: rgba(6,182,212,0.15); color: var(--accent);">
                <i class="fas fa-code-commit"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" id="gitBranch">
                    {{ $status['has_git'] ? $status['git_branch'] : 'Standalone' }}
                </div>
                <div class="dash-stat-label">Branch Repositori</div>
            </div>
        </div>

        <div class="dash-stat-card" title="{{ count($status['pending_migrations']) }} Migrasi Database Pending">
            <div class="dash-stat-icon"
                style="background: {{ count($status['pending_migrations']) > 0 ? 'rgba(245,158,11,0.15)' : 'rgba(16,185,129,0.15)' }}; color: {{ count($status['pending_migrations']) > 0 ? '#f59e0b' : 'var(--success, #10b981)' }};">
                <i class="fas fa-database"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" id="pendingMigCount">
                    {{ count($status['pending_migrations']) }}
                </div>
                <div class="dash-stat-label">Migrasi Pending</div>
            </div>
        </div>

        <div class="dash-stat-card" title="Riwayat Update Terakhir: {{ $status['last_update_at'] ?? 'Belum Ada Riwayat' }}">
            <div class="dash-stat-icon" style="background: rgba(147, 51, 234, 0.15); color: #9333ea;">
                <i class="fas fa-clock-rotate-left"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" id="lastUpdateText">
                    {{ $status['last_update_at'] ? date('d/m/Y H:i', strtotime($status['last_update_at'])) : 'Belum Ada' }}
                </div>
                <div class="dash-stat-label">Update Terakhir</div>
            </div>
        </div>
    </div>

    <!-- Main Section: Grid 2 Column -->
    <div class="dash-grid-2 update-main-grid">
        <!-- Status & Action Card -->
        <div class="card" style="margin-bottom: 0;">
            <div style="display: flex; justify-content: space-between; align-items: center; gap: 10px; flex-wrap: wrap; margin-bottom: 18px;">
                <h3 style="font-size: var(--text-lg, 1.125rem); font-weight: 700; color: var(--text-color); margin: 0;">
                    <i class="fas fa-shield-halved text-primary me-2"></i> Status Integritas Sistem
                </h3>
                <span class="badge {{ $status['updates_available'] ? 'badge-warning' : 'badge-primary' }}" id="statusBadge">
                    {{ $status['updates_available'] ? 'Pembaruan Siap' : 'Versi Terkini' }}
                </span>
            </div>

            <!-- Update Alert Banner -->
            <div id="updateBanner" class="update-banner-box"
                style="padding: 16px; border-radius: var(--radius-lg, 12px); margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; gap: 14px; flex-wrap: wrap; background: {{ $status['updates_available'] ? 'rgba(245, 158, 11, 0.1)' : 'rgba(16, 185, 129, 0.1)' }}; border: 1px solid {{ $status['updates_available'] ? 'rgba(245, 158, 11, 0.3)' : 'rgba(16, 185, 129, 0.3)' }};">
                <div style="display: flex; align-items: center; gap: 12px; min-width: 0; flex: 1 1 220px;">
                    <div
                        style="width: 38px; height: 38px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; background: {{ $status['updates_available'] ? 'rgba(245, 158, 11, 0.2)' : 'rgba(16, 185, 129, 0.2)' }}; color: {{ $status['updates_available'] ? '#f59e0b' : 'var(--success, #10b981)' }};">
                        <i class="fas {{ $status['updates_available'] ? 'fa-triangle-exclamation' : 'fa-check' }}"></i>
                    </div>
                    <div style="min-width: 0; flex: 1;">
                        <div style="font-weight: 700; color: var(--text-color); word-break: break-word;" id="bannerTitle">
                            {{ $status['updates_available'] ? 'Pembaruan Tersedia!' : 'Sistem Sudah yang Terbaru' }}
                        </div>
                        <div style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); word-break: break-word;" id="bannerDesc">
                            {{ $status['updates_available'] ? 'Ditemukan file kode atau migrasi database baru yang siap dipasang.' : 'Semua file kode dan struktur database sudah dalam kondisi prima.' }}
                        </div>
                    </div>
                </div>
                <button type="button" id="btnRunUpdate"
                    class="btn btn-primary {{ $status['updates_available'] ? '' : 'hidden' }}"
                    style="padding: 9px 16px; font-size: var(--text-sm, 0.8125rem); white-space: nowrap; display: {{ $status['updates_available'] ? 'inline-flex' : 'none' }}; align-items: center; justify-content: center; gap: 6px;">
                    <i class="fas fa-download me-1"></i> Pasang Sekarang
                </button>
            </div>

            <!-- Migration Details if any -->
            <div id="migrationList" class="{{ empty($status['pending_migrations']) ? 'hidden' : '' }}"
                style="margin-top: 15px;">
                <div style="font-weight: 700; font-size: var(--text-base, 0.875rem); color: var(--text-color); margin-bottom: 8px;">
                    <i class="fas fa-database text-warning me-1"></i> Migrasi Database yang Belum Dipasang:
                </div>
                <div
                    style="background: var(--bg-hover); border-radius: var(--radius-md, 8px); padding: 10px; max-height: 140px; overflow-y: auto; overflow-x: hidden;">
                    <ul style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin: 0; padding-left: 18px; word-break: break-all;" id="migUl">
                        @foreach ($status['pending_migrations'] as $mig)
                            <li><code>{{ $mig }}</code></li>
                        @endforeach
                    </ul>
                </div>
            </div>

            <div style="margin-top: 20px; font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); line-height: 1.5;">
                <i class="fas fa-circle-info text-primary me-1"></i> <b>Catatan Pembaruan:</b>
                Proses update akan melakukan migrasi database (jika ada skema baru), sinkronisasi file kode, dan
                membersihkan cache aplikasi secara otomatis.
            </div>
        </div>

        <!-- Terminal Execution Log Card -->
        <div class="card" style="margin-bottom: 0; display: flex; flex-direction: column;">
            <div style="display: flex; justify-content: space-between; align-items: center; gap: 10px; flex-wrap: wrap; margin-bottom: 14px;">
                <h3 style="font-size: var(--text-lg, 1.125rem); font-weight: 700; color: var(--text-color); margin: 0;">
                    <i class="fas fa-terminal text-primary me-2"></i> Log Eksekusi Pembaruan
                </h3>
                <span class="badge" style="background: rgba(148, 163, 184, 0.15); color: var(--text-muted);">Console</span>
            </div>

            <div id="updateLogBox"
                style="flex: 1; min-height: 250px; max-height: 340px; background: #0b0f19; color: #e2e8f0; font-family: monospace; font-size: var(--text-xs, 0.75rem); padding: 14px; border-radius: var(--radius-lg, 12px); overflow-y: auto; overflow-x: hidden; word-break: break-all; border: 1px solid rgba(255,255,255,0.08); -webkit-text-fill-color: currentColor;">
                <div style="color: #94a3b8;">// Siap menerima proses update sistem...</div>
            </div>
        </div>
    </div>

@endsection

@push('styles')
    <style>
        /* Log box tetap dark di kedua tema agar teks selalu kontras */
        #updateLogBox,
        #updateLogBox div {
            background-color: #0b0f19 !important;
            color: #e2e8f0 !important;
            -webkit-text-fill-color: currentColor !important;
        }

        /* 1. Grid 4 card statistik seragam & simetris */
        .update-stat-grid {
            display: grid !important;
            grid-template-columns: repeat(4, minmax(0, 1fr)) !important;
            gap: 16px !important;
            margin-bottom: 20px !important;
            align-items: stretch !important;
        }

        .update-stat-grid .dash-stat-card {
            width: 100% !important;
            min-width: 0 !important;
            height: 100% !important;
            box-sizing: border-box !important;
        }

        .update-stat-grid .dash-stat-info {
            min-width: 0 !important;
            overflow: hidden !important;
            flex: 1 1 auto !important;
            width: 100% !important;
        }

        .update-stat-grid .dash-stat-value {
            font-size: var(--text-lg, 1.125rem) !important;
            font-weight: 700 !important;
            line-height: 1.2 !important;
            white-space: nowrap !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
            width: 100% !important;
        }

        .update-stat-grid .dash-stat-label {
            font-size: var(--text-xs, 0.75rem) !important;
            color: var(--text-muted) !important;
            white-space: nowrap !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
            width: 100% !important;
            margin-top: 2px !important;
        }

        /* 2. Main Grid: Status & Log Card */
        .update-main-grid {
            display: grid !important;
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            gap: 20px !important;
            align-items: stretch !important;
        }

        .update-main-grid .card {
            width: 100% !important;
            min-width: 0 !important;
            box-sizing: border-box !important;
            overflow: hidden !important;
        }

        /* 3. Responsive Breakpoints: Tablet (<= 991px) & Mobile (<= 768px) */
        @media (max-width: 991px) {
            .update-stat-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
                gap: 12px !important;
            }

            .update-main-grid {
                grid-template-columns: 1fr !important;
                gap: 16px !important;
            }
        }

        @media (max-width: 768px) {
            .update-stat-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
                gap: 10px !important;
                margin-bottom: 16px !important;
            }

            .update-stat-grid .dash-stat-card {
                padding: 12px !important;
                gap: 8px !important;
                flex-direction: column !important;
                align-items: flex-start !important;
                justify-content: flex-start !important;
            }

            .update-stat-grid .dash-stat-icon {
                width: 38px !important;
                height: 38px !important;
                font-size: var(--text-md, 1rem) !important;
                border-radius: var(--radius-md, 8px) !important;
            }

            .update-stat-grid .dash-stat-value {
                font-size: var(--text-md, 1rem) !important;
            }
        }

        @media (max-width: 576px) {
            .update-banner-box {
                flex-direction: column !important;
                align-items: stretch !important;
                gap: 12px !important;
            }

            .update-banner-box #btnRunUpdate {
                width: 100% !important;
                justify-content: center !important;
            }
        }
    </style>
@endpush

@push('scripts')
    <script src="{{ asset('js/update.js') }}?v={{ filemtime(public_path('js/update.js')) }}"></script>
@endpush
