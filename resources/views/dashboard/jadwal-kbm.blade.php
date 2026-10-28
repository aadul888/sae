@extends('layouts.dashboard')

@section('title', 'Master Data — Jadwal KBM — SAE')
@section('dash_title', 'Jadwal Kegiatan Belajar Mengajar (KBM)')

@push('styles')
    <style>
        .banner-action-buttons {
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .jadwal-subnav-desktop {
            background: var(--bg-card);
            padding: 4px;
            border-radius: var(--radius-lg, 12px);
            border: 1px solid var(--border-color);
            display: inline-flex;
            gap: 4px;
        }

        @media (max-width: 768px) {
            .dash-banner {
                flex-direction: column !important;
                align-items: stretch !important;
                gap: 12px !important;
            }

            .dash-banner-actions {
                width: 100% !important;
                display: flex !important;
                flex-direction: column !important;
                gap: 10px !important;
            }

            .pemberlakuan-badge-wrap {
                display: flex !important;
                flex-direction: column !important;
                width: 100% !important;
                gap: 6px !important;
            }

            .pemberlakuan-badge-wrap .badge,
            .pemberlakuan-badge-wrap .btn {
                width: 100% !important;
                text-align: center !important;
                justify-content: center !important;
            }

            .jadwal-view-switcher {
                display: flex !important;
                width: 100% !important;
            }

            .jadwal-view-switcher .btn {
                flex: 1 !important;
                text-align: center !important;
                justify-content: center !important;
            }

            .banner-action-buttons {
                display: flex !important;
                width: 100% !important;
                gap: 6px !important;
                justify-content: space-between !important;
            }

            .banner-action-buttons>button,
            .banner-action-buttons>div,
            .banner-action-buttons>div>button {
                flex: 1 !important;
                min-width: 0 !important;
                padding: 9px 6px !important;
                display: inline-flex !important;
                align-items: center !important;
                justify-content: center !important;
            }

            .jadwal-subnav-desktop {
                display: flex !important;
                width: 100% !important;
            }

            .jadwal-subnav-desktop .periode-nav-tab {
                flex: 1 !important;
                text-align: center !important;
                justify-content: center !important;
                font-size: var(--text-sm, 0.8125rem) !important;
                padding: 8px 8px !important;
            }

            #jadwalTabBarContainer {
                padding: 10px 12px !important;
            }

            #jadwalTabBarContainer>div:first-child {
                flex-direction: column !important;
                align-items: stretch !important;
                gap: 12px !important;
            }

            .jadwal-days-strip {
                display: flex !important;
                width: 100% !important;
                overflow-x: auto !important;
                flex-wrap: nowrap !important;
                -webkit-overflow-scrolling: touch;
                padding-bottom: 6px;
                gap: 6px !important;
            }

            .jadwal-days-strip .btn-day-tab {
                flex-shrink: 0 !important;
                padding: 6px 12px !important;
                font-size: var(--text-sm, 0.8125rem) !important;
            }

            .jadwal-tingkat-strip {
                display: flex !important;
                width: 100% !important;
                gap: 6px !important;
                justify-content: space-between !important;
            }

            .jadwal-tingkat-strip .btn-tingkat-filter {
                flex: 1 !important;
                text-align: center !important;
                justify-content: center !important;
                padding: 6px 6px !important;
                font-size: var(--text-sm, 0.8125rem) !important;
            }
        }
    </style>
    <style>
        /* Grup kolom per jurusan pada matriks grid jadwal KBM */
        .jadwal-group-row th {
            position: sticky;
            top: 0;
            z-index: 15;
            background: var(--bg-hover);
            border-bottom: 1px solid var(--border-color);
            border-right: 2px solid var(--border-color);
            padding: 6px 10px;
            text-align: center;
            font-size: var(--text-xs, 0.75rem);
            font-weight: 800;
            letter-spacing: 0.03em;
            text-transform: uppercase;
            color: var(--text-color);
            white-space: nowrap;
        }

        .jadwal-group-row th.jadwal-group-start {
            border-left: 3px solid var(--primary);
        }

        .jadwal-group-row .jadwal-group-count {
            font-weight: 600;
            color: var(--text-muted);
            text-transform: none;
            letter-spacing: 0;
        }

        /* Sel dengan lebih dari satu jadwal: tampil berderet, tidak saling menimpa */
        .jadwal-cell-stack {
            display: flex;
            flex-direction: column;
            gap: 4px;
            height: 100%;
        }

        .jadwal-cell-stack .jadwal-grid-card {
            flex: 0 0 auto;
        }
    </style>
@endpush

@section('content')
    <div class="dash-banner">
        <div>
            <h2 style="font-size: var(--text-2xl, 1.5rem); font-weight: 800; color: var(--text-color); margin-bottom: 4px;">
                <i class="fas fa-calendar-alt text-primary me-2"></i> Master Data — Jadwal KBM
            </h2>
            <p style="color: var(--text-muted); font-size: var(--text-base, 0.875rem);">
                Visualisasi jadwal mengajar guru per rombel secara modular dan anti-bentrok, terintegrasi ke presensi dan
                agenda KBM.
            </p>
        </div>
        <div class="dash-banner-actions" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
            <!-- Badge Pekan Aktif (lapis 2: pemberlakuan per pekan) -->
            @if (!empty($ringkasanPekan['aktif_ke']))
                <a href="{{ route('dashboard.jadwal-kbm.index', array_merge(request()->query(), ['view_mode' => 'pekan'])) }}"
                    class="badge"
                    style="background: rgba(99,102,241,0.15); color: var(--primary); border: 1px solid rgba(99,102,241,0.3); font-size: var(--text-sm, 0.8125rem); padding: 6px 12px; border-radius: var(--radius-md, 8px); display: inline-flex; align-items: center; gap: 6px; text-decoration: none;"
                    title="Pekan yang sedang berjalan — klik untuk mengelola pemberlakuan pekan">
                    <i class="fas fa-calendar-week"></i> Pekan aktif: ke-{{ $ringkasanPekan['aktif_ke'] }}
                    <span style="color: var(--text-muted); font-weight: 500;">({{ $ringkasanPekan['aktif_rentang'] }})</span>
                </a>
            @endif

            <!-- Pemberlakuan Jadwal Aktif oleh Admin (Eksklusif: Otomatis vs Manual) -->
            @if ($canUpdate)
                <div class="pemberlakuan-badge-wrap" style="display: inline-flex; align-items: center; gap: 8px;">
                    @if ($isOtomatisAktif)
                        <span class="badge"
                            style="background: rgba(16,185,129,0.15); color: var(--success, #10b981); border: 1px solid rgba(16,185,129,0.3); font-size: var(--text-sm, 0.8125rem); padding: 6px 12px; border-radius: var(--radius-md, 8px); display: inline-flex; align-items: center; gap: 6px;">
                            <i class="fas fa-check-circle"></i> Jadwal Otomatis Diberlakukan (Aktif)
                        </span>
                        <button type="button" class="btn btn-outline btn-toggle-pemberlakuan" data-mode="otomatis"
                            data-status="draft" data-url="{{ route('dashboard.jadwal-kbm.toggle-pemberlakuan') }}"
                            onclick="if(window.handleTogglePemberlakuan){window.handleTogglePemberlakuan(this);}"
                            title="Alihkan ke Status Draft (Sembunyikan dari Guru/Siswa)"
                            style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px); border-color: rgba(245,158,11,0.4); color: var(--warning, #f59e0b); cursor: pointer;">
                            <i class="fas fa-pause"></i>
                        </button>
                    @elseif ($isManualAktif)
                        <span class="badge"
                            style="background: rgba(245,158,11,0.15); color: var(--warning, #f59e0b); border: 1px solid rgba(245,158,11,0.3); font-size: var(--text-sm, 0.8125rem); padding: 6px 12px; border-radius: var(--radius-md, 8px); display: inline-flex; align-items: center; gap: 6px;">
                            <i class="fas fa-clock"></i> Status Draft (Jadwal Manual Aktif)
                        </span>
                        <button type="button" class="btn btn-toggle-pemberlakuan" data-mode="otomatis" data-status="aktif"
                            data-url="{{ route('dashboard.jadwal-kbm.toggle-pemberlakuan') }}"
                            onclick="if(window.handleTogglePemberlakuan){window.handleTogglePemberlakuan(this);}"
                            title="Berlakukan Jadwal Otomatis Ini ke Seluruh Guru & Siswa (Menonaktifkan Jadwal Manual)"
                            style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px); background: var(--success, #10b981); color: #fff; font-weight: 700; border: none; box-shadow: 0 2px 8px rgba(16,185,129,0.3); cursor: pointer;">
                            <i class="fas fa-play"></i>
                        </button>
                    @else
                        <span class="badge"
                            style="background: rgba(245,158,11,0.15); color: var(--warning, #f59e0b); border: 1px solid rgba(245,158,11,0.3); font-size: var(--text-sm, 0.8125rem); padding: 6px 12px; border-radius: var(--radius-md, 8px); display: inline-flex; align-items: center; gap: 6px;">
                            <i class="fas fa-clock"></i> Status Draft (Dalam Penyusunan)
                        </span>
                        <button type="button" class="btn btn-toggle-pemberlakuan" data-mode="otomatis" data-status="aktif"
                            data-url="{{ route('dashboard.jadwal-kbm.toggle-pemberlakuan') }}"
                            onclick="if(window.handleTogglePemberlakuan){window.handleTogglePemberlakuan(this);}"
                            title="Berlakukan Jadwal Otomatis Ini ke Seluruh Guru & Siswa"
                            style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px); background: var(--success, #10b981); color: #fff; font-weight: 700; border: none; box-shadow: 0 2px 8px rgba(16,185,129,0.3); cursor: pointer;">
                            <i class="fas fa-play"></i>
                        </button>
                    @endif
                </div>
            @endif

            <!-- View Mode Switcher -->
            <div class="jadwal-view-switcher"
                style="display: inline-flex; background: var(--bg-hover); padding: 3px; border-radius: var(--radius-md, 8px); border: 1px solid var(--border-color);">
                <a href="{{ route('dashboard.jadwal-kbm.index', array_merge(request()->query(), ['view_mode' => 'grid', 'hari' => $selectedHari])) }}"
                    class="btn {{ $viewMode === 'grid' ? 'btn-primary' : 'btn-outline' }}"
                    style="padding: 6px 12px; font-size: var(--text-sm, 0.8125rem); border: none; border-radius: var(--radius-sm, 6px); box-shadow: none;">
                    <i class="fas fa-table-cells me-1"></i> Matriks Grid
                </a>
                <a href="{{ route('dashboard.jadwal-kbm.index', array_merge(request()->query(), ['view_mode' => 'table'])) }}"
                    class="btn {{ $viewMode === 'table' ? 'btn-primary' : 'btn-outline' }}"
                    style="padding: 6px 12px; font-size: var(--text-sm, 0.8125rem); border: none; border-radius: var(--radius-sm, 6px); box-shadow: none;">
                    <i class="fas fa-list me-1"></i> Rekap Tabel
                </a>
                <a href="{{ route('dashboard.jadwal-kbm.index', array_merge(request()->query(), ['view_mode' => 'pekan'])) }}"
                    class="btn {{ $viewMode === 'pekan' ? 'btn-primary' : 'btn-outline' }}"
                    style="padding: 6px 12px; font-size: var(--text-sm, 0.8125rem); border: none; border-radius: var(--radius-sm, 6px); box-shadow: none;"
                    title="Pemberlakuan jadwal per pekan (mingguan)">
                    <i class="fas fa-calendar-week me-1"></i> Pekan
                </a>
            </div>

            <!-- Toolbar Action Buttons (Cetak, Preferensi, Slot, Auto, Tambah) -->
            <div class="banner-action-buttons">
                <!-- Dropdown Cetak Jadwal Multi-Format -->
                <div style="position: relative; display: inline-block;">
                    <button type="button" class="btn btn-outline" id="btnDropdownCetak"
                        style="padding: 8px 14px; font-size: var(--text-base, 0.875rem);" title="Cetak Jadwal (Induk, Guru, Kelas)">
                        <i class="fas fa-print"></i>
                    </button>
                    <div id="dropdownMenuCetak"
                        style="display: none; position: absolute; right: 0; top: 110%; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius-md, 8px); box-shadow: 0 10px 25px rgba(0,0,0,0.15); min-width: 220px; z-index: 1000; overflow: hidden;">
                        <a href="{{ route('dashboard.jadwal-kbm.cetak-induk') }}" target="_blank"
                            style="display: flex; align-items: center; gap: 10px; padding: 10px 16px; color: var(--text-color); text-decoration: none; font-size: var(--text-base, 0.875rem); border-bottom: 1px solid var(--border-color);">
                            <i class="fas fa-table text-primary" style="width: 18px;"></i>
                            <div>
                                <strong>Jadwal Induk Sekolah</strong>
                                <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted);">Matriks besar seluruh kelas</div>
                            </div>
                        </a>
                        <a href="{{ route('dashboard.jadwal-kbm.cetak-guru') }}" target="_blank"
                            style="display: flex; align-items: center; gap: 10px; padding: 10px 16px; color: var(--text-color); text-decoration: none; font-size: var(--text-base, 0.875rem); border-bottom: 1px solid var(--border-color);">
                            <i class="fas fa-chalkboard-user text-success" style="width: 18px;"></i>
                            <div>
                                <strong>Jadwal per Guru</strong>
                                <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted);">Kartu jadwal mengajar GTK</div>
                            </div>
                        </a>
                        <a href="{{ route('dashboard.jadwal-kbm.cetak-rombel') }}" target="_blank"
                            style="display: flex; align-items: center; gap: 10px; padding: 10px 16px; color: var(--text-color); text-decoration: none; font-size: var(--text-base, 0.875rem); border-bottom: 1px solid var(--border-color);">
                            <i class="fas fa-school text-warning" style="width: 18px;"></i>
                            <div>
                                <strong>Jadwal per Kelas</strong>
                                <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted);">Format siap tempel di kelas</div>
                            </div>
                        </a>
                        <a href="{{ route('dashboard.jadwal-kbm.cetak-bulanan', ['bulan' => now()->month, 'tahun' => now()->year, 'format' => 'rombel']) }}"
                            target="_blank"
                            style="display: flex; align-items: center; gap: 10px; padding: 10px 16px; color: var(--text-color); text-decoration: none; font-size: var(--text-base, 0.875rem);">
                            <i class="fas fa-calendar-days text-danger" style="width: 18px;"></i>
                            <div>
                                <strong>Cetak Bulanan</strong>
                                <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted);">Jadwal per tanggal (per bulan)</div>
                            </div>
                        </a>
                    </div>
                </div>

                @if ($canUpdate)
                    <button type="button" class="btn btn-outline" id="btnBukaPreferensiGuru"
                        style="padding: 8px 14px; font-size: var(--text-base, 0.875rem);" title="Atur Hari Off dan Jam Berhalangan Guru">
                        <i class="fas fa-user-clock"></i>
                    </button>
                    <button type="button" class="btn btn-outline" id="btnBukaPengaturanSlot"
                        style="padding: 8px 14px; font-size: var(--text-base, 0.875rem);" title="Atur Jam Pelajaran, Durasi JP, dan Istirahat">
                        <i class="fas fa-sliders"></i>
                    </button>
                @endif

                @if ($canCreate)
                    <button type="button" class="btn" id="btnBukaAutoGenerate"
                        style="padding: 8px 14px; font-size: var(--text-base, 0.875rem); background: linear-gradient(135deg, var(--primary-light, #6366f1) 0%, #8b5cf6 50%, #ec4899 100%); color: #fff; font-weight: 700; border: none; box-shadow: 0 4px 12px rgba(99,102,241,0.35);"
                        title="Auto-Generate Jadwal Otomatis">
                        <i class="fas fa-wand-magic-sparkles"></i>
                    </button>
                    <button type="button" class="btn btn-primary" id="btnTambahJadwal"
                        style="padding: 8px 14px; font-size: var(--text-base, 0.875rem);" title="Tambah Jadwal Manual">
                        <i class="fas fa-plus"></i>
                    </button>
                @endif
            </div>
        </div>
    </div>

    <!-- Sub-Menu Navigasi Jadwal KBM (Generate vs Manual) -->
    <div class="periode-nav-wrapper" style="margin-bottom: 20px;">
        <div class="jadwal-subnav-desktop">
            <a href="{{ route('dashboard.jadwal-kbm.index') }}" class="periode-nav-tab active">
                <i class="fas fa-wand-magic-sparkles me-1"></i> Generate Jadwal (Otomatis)
            </a>
            <a href="{{ route('dashboard.jadwal-kbm.manual.index') }}" class="periode-nav-tab">
                <i class="fas fa-pen-to-square me-1"></i> Jadwal Manual (Per Kelas)
            </a>
        </div>
    </div>

    <!-- Banner Integrasi Kalender Pendidikan -->
    <div class="card"
        style="padding: 12px 18px; margin-bottom: 18px; border-radius: var(--radius-lg, 12px); background: var(--bg-card); border: 1px solid var(--border-color); display: flex; align-items: center; justify-content: space-between; gap: 14px; flex-wrap: wrap;">
        <div style="display: flex; align-items: center; gap: 12px;">
            @php
                $isLibur =
                    ($statusHariIni['is_libur'] ?? false) ||
                    ($statusHariIni['libur_gtk'] ?? false) ||
                    ($statusHariIni['libur_pd'] ?? false);
                $bgIcon = $isLibur
                    ? 'rgba(239,68,68,0.12)'
                    : (!empty($agendaHariIni)
                        ? 'rgba(99,102,241,0.12)'
                        : 'rgba(16,185,129,0.12)');
                $colIcon = $isLibur ? '#ef4444' : (!empty($agendaHariIni) ? 'var(--primary)' : '#10b981');
                $iconClass = $isLibur
                    ? 'fa-umbrella-beach'
                    : (!empty($agendaHariIni)
                        ? 'fa-calendar-star'
                        : 'fa-calendar-check');
            @endphp
            <div
                style="width: 40px; height: 40px; border-radius: var(--radius-lg, 12px); background: {{ $bgIcon }}; color: {{ $colIcon }}; display: flex; align-items: center; justify-content: center; font-size: var(--text-xl, 1.25rem); flex-shrink: 0;">
                <i class="fas {{ $iconClass }}"></i>
            </div>
            <div>
                <div
                    style="font-size: var(--text-base, 0.875rem); font-weight: 700; color: var(--text-color); display: flex; align-items: center; gap: 8px;">
                    <span>Kalender Akademik: TA {{ $tahunAjaranAktif }} (Semester
                        {{ $semesterAktif == '1' ? '1 Ganjil' : '2 Genap' }})</span>
                    @if ($isLibur)
                        <span class="badge"
                            style="background: rgba(239,68,68,0.15); color: var(--danger, #ef4444); border: 1px solid rgba(239,68,68,0.3); font-size: var(--text-xs, 0.75rem); padding: 2px 8px; border-radius: var(--radius-lg, 12px);">
                            <i class="fas fa-ban me-1"></i> Libur Sekolah / KBM Off
                        </span>
                    @elseif (!empty($agendaHariIni))
                        <span class="badge"
                            style="background: rgba(99,102,241,0.15); color: var(--primary); border: 1px solid rgba(99,102,241,0.3); font-size: var(--text-xs, 0.75rem); padding: 2px 8px; border-radius: var(--radius-lg, 12px);">
                            <i class="fas fa-flag me-1"></i> Agenda Khusus
                        </span>
                    @else
                        <span class="badge"
                            style="background: rgba(16,185,129,0.15); color: var(--success, #10b981); border: 1px solid rgba(16,185,129,0.3); font-size: var(--text-xs, 0.75rem); padding: 2px 8px; border-radius: var(--radius-lg, 12px);">
                            <i class="fas fa-check me-1"></i> Hari Efektif Belajar (HEB)
                        </span>
                    @endif
                </div>
                <div style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); margin-top: 2px;">
                    @if (!empty($agendaHariIni))
                        <strong>Hari Ini ({{ \Carbon\Carbon::today()->translatedFormat('l, d F Y') }}):</strong>
                        {{ $agendaHariIni->nama_agenda }}
                        @if ($agendaHariIni->keterangan)
                            &bull; {{ $agendaHariIni->keterangan }}
                        @endif
                    @else
                        <strong>Hari Ini:</strong> {{ \Carbon\Carbon::today()->translatedFormat('l, d F Y') }} &bull; KBM
                        reguler berlangsung sesuai jam pelajaran aktif.
                    @endif
                </div>
            </div>
        </div>
        <div>
            <a href="{{ route('dashboard.kalender-pendidikan.index') }}" class="btn btn-outline"
                style="padding: 6px 14px; font-size: var(--text-sm, 0.8125rem);" title="Lihat Detail Kalender Pendidikan">
                <i class="fas fa-calendar-alt me-1"></i> Buka Kalender Pendidikan
            </a>
        </div>
    </div>

    <!-- Summary Stats Grid -->
    <div class="dash-stat-grid" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); margin-bottom: 20px;">
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(16,185,129,0.15); color: var(--success, #10b981);">
                <i class="fas fa-calendar-days"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem); color: var(--success, #10b981);">
                    {{ number_format($summary['hari_efektif_semester'] ?? 0, 0, ',', '.') }} <span
                        style="font-size: var(--text-xs, 0.75rem); font-weight: 500;">Hari</span>
                </div>
                <div class="dash-stat-label">HEB Semester (Jalan: {{ $summary['hari_efektif_berjalan'] ?? 0 }})</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(99,102,241,0.15); color: var(--primary);">
                <i class="fas fa-calendar-check"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem);">
                    {{ number_format($summary['total_jadwal'], 0, ',', '.') }}
                </div>
                <div class="dash-stat-label">Total Jadwal KBM</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(16,185,129,0.15); color: var(--success, #10b981);">
                <i class="fas fa-school"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem);">
                    {{ $summary['total_rombel'] }}
                </div>
                <div class="dash-stat-label">Rombel Terjadwal</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(6,182,212,0.15); color: var(--accent);">
                <i class="fas fa-chalkboard-user"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem);">
                    {{ $summary['total_guru'] }}
                </div>
                <div class="dash-stat-label">Guru Terjadwal</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(245,158,11,0.15); color: var(--warning, #f59e0b);">
                <i class="fas fa-clock"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem);">
                    {{ number_format($summary['total_jp'], 0, ',', '.') }} JP
                </div>
                <div class="dash-stat-label">Total Jam Pelajaran</div>
            </div>
        </div>
    </div>

    @if ($viewMode === 'grid')
        <!-- ==================== TAMPILAN MATRIKS GRID MODULAR ==================== -->
        <!-- Tab Pemilihan Hari -->
        <div class="card" id="jadwalTabBarContainer" style="padding: 12px 16px; margin-bottom: 16px;">
            <div style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 12px;">
                <!-- Tab Hari Buttons -->
                <div class="jadwal-days-strip" style="display: flex; gap: 8px; flex-wrap: wrap; align-items: center;">
                    <span
                        style="font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-right: 4px; white-space: nowrap;">
                        <i class="fas fa-calendar-day me-1"></i> Hari:
                    </span>
                    @foreach (\App\Models\JadwalKbm::HARI_LIST as $h)
                        @php
                            $hJp = $dailySlotCounts[$h] ?? ($h === 'Jumat' ? 5 : $pengaturan->total_slot_jp ?? 10);
                            $isHariAktif =
                                in_array(
                                    $h,
                                    $pengaturan->hari_aktif ?? ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'],
                                    true,
                                ) && $hJp > 0;
                        @endphp
                        @if ($isHariAktif || $hJp > 0)
                            @php
                                $isDayActive = $selectedHari === $h;
                                $count = $countPerHari[$h] ?? 0;
                            @endphp
                            <a href="{{ route('dashboard.jadwal-kbm.index', array_merge(request()->query(), ['hari' => $h, 'view_mode' => 'grid'])) }}"
                                class="btn {{ $isDayActive ? 'btn-primary' : 'btn-outline' }} btn-day-tab"
                                data-hari="{{ $h }}"
                                style="padding: 6px 14px; font-size: var(--text-sm, 0.8125rem); border-radius: var(--radius-md, 8px); display: inline-flex; align-items: center; gap: 6px; {{ $isDayActive ? 'color: #ffffff !important;' : '' }}">
                                <span
                                    style="{{ $isDayActive ? 'color: #ffffff !important; font-weight: 700;' : '' }}">{{ $h }}</span>
                                <span class="badge"
                                    style="{{ $isDayActive ? 'background: rgba(255,255,255,0.25); color: #fff;' : 'background: var(--bg-hover); color: var(--text-muted);' }} font-size: var(--text-xs, 0.75rem); padding: 2px 6px; border-radius: var(--radius-lg, 12px);"
                                    title="{{ $hJp }} JP ({{ $count }} jadwal)">
                                    {{ $hJp }} JP
                                </span>
                            </a>
                        @endif
                    @endforeach
                </div>

                <!-- Filter Tingkat Rombel -->
                <div class="jadwal-tingkat-strip" style="display: flex; gap: 6px; align-items: center; flex-wrap: wrap;">
                    <span
                        style="font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-right: 4px; white-space: nowrap;">
                        Tingkat:
                    </span>
                    @foreach (['' => 'Semua', '10' => 'Kelas X', '11' => 'Kelas XI', '12' => 'Kelas XII'] as $tVal => $tLabel)
                        <a href="{{ route('dashboard.jadwal-kbm.index', array_merge(request()->query(), ['tingkat' => $tVal, 'view_mode' => 'grid', 'hari' => $selectedHari])) }}"
                            class="btn {{ $tingkat === $tVal ? 'btn-primary' : 'btn-outline' }} btn-tingkat-filter"
                            data-tingkat="{{ $tVal }}"
                            style="padding: 4px 10px; font-size: var(--text-sm, 0.8125rem); border-radius: var(--radius-sm, 6px);">
                            {{ $tLabel }}
                        </a>
                    @endforeach
                </div>
            </div>

            @if (($jurusanList ?? collect())->isNotEmpty())
                <div
                    style="margin-top: 10px; padding-top: 10px; border-top: 1px solid var(--border-color); display: flex; gap: 6px; align-items: center; flex-wrap: wrap;">
                    <span
                        style="font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-right: 4px; white-space: nowrap;">
                        Jurusan:
                    </span>
                    <a href="{{ route('dashboard.jadwal-kbm.index', array_merge(request()->query(), ['jurusan' => '', 'view_mode' => 'grid', 'hari' => $selectedHari])) }}"
                        class="btn {{ $jurusan === '' ? 'btn-primary' : 'btn-outline' }}"
                        style="padding: 4px 10px; font-size: var(--text-sm, 0.8125rem); border-radius: var(--radius-sm, 6px);">
                        Semua
                    </a>
                    @foreach ($jurusanList as $j)
                        @php
                            $jVal = (string) $j->jurusan_id;
                            $jLabel = trim((string) ($j->singkatan ?? '')) ?: (trim((string) $j->jurusan_id_str) ?: 'Tanpa Jurusan');
                        @endphp
                        <a href="{{ route('dashboard.jadwal-kbm.index', array_merge(request()->query(), ['jurusan' => $jVal, 'view_mode' => 'grid', 'hari' => $selectedHari])) }}"
                            class="btn {{ $jurusan === $jVal ? 'btn-primary' : 'btn-outline' }}"
                            title="{{ $j->jurusan_id_str }} ({{ $j->total_rombel }} kelas)"
                            style="padding: 4px 10px; font-size: var(--text-sm, 0.8125rem); border-radius: var(--radius-sm, 6px);">
                            {{ $jLabel }}
                            <span class="badge"
                                style="{{ $jurusan === $jVal ? 'background: rgba(255,255,255,0.25); color: #fff;' : 'background: var(--bg-hover); color: var(--text-muted);' }} font-size: var(--text-2xs, 0.6875rem); padding: 1px 5px; border-radius: var(--radius-md, 8px);">
                                {{ $j->total_rombel }}
                            </span>
                        </a>
                    @endforeach
                </div>
            @endif

            <div
                style="margin-top: 10px; padding-top: 10px; border-top: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; font-size: var(--text-sm, 0.8125rem); color: var(--text-muted);">
                <div style="display: flex; align-items: center; gap: 8px;">
                    @if (count($timeSlots) > 0)
                        <span class="badge badge-primary" style="font-size: var(--text-sm, 0.8125rem); padding: 4px 8px;">
                            <i class="fas fa-clock me-1"></i> KBM {{ $selectedHari }}: {{ count($timeSlots) }} JP
                        </span>
                        <span style="font-weight: 600; color: var(--text-color);">
                            {{ $timeSlots[1]['mulai'] ?? '07:15' }} s/d {{ end($timeSlots)['selesai'] ?? '' }} WIB
                        </span>
                    @else
                        <span class="badge"
                            style="background: var(--bg-hover); color: var(--text-muted); font-size: var(--text-sm, 0.8125rem); padding: 4px 8px; border: 1px solid var(--border-color);">
                            <i class="fas fa-calendar-xmark me-1"></i> KBM {{ $selectedHari }}: 0 JP
                        </span>
                        <span style="font-weight: 600; color: var(--text-muted);">
                            Libur / Tanpa Jam Pelajaran
                        </span>
                    @endif
                </div>
                <div style="display: flex; gap: 14px;">
                    <span><span
                            style="display: inline-block; width: 10px; height: 10px; border-radius: var(--radius-xs, 4px); background: rgba(99,102,241,0.2); border: 1px solid var(--primary); margin-right: 4px;"></span>
                        Terjadwal</span>
                    <span><span
                            style="display: inline-block; width: 10px; height: 10px; border-radius: var(--radius-xs, 4px); background: var(--bg-hover); border: 1px dashed var(--border-color); margin-right: 4px;"></span>
                        Kosong (Bisa Diisi)</span>
                </div>
            </div>
        </div>

        <!-- Timetable Matrix Grid Container -->
        <div class="card table-responsive-stack" id="gridMatrixContainer"
            style="padding: 0; margin-bottom: 24px; overflow-x: auto;">
            <table class="table" style="width: 100%; border-collapse: separate; border-spacing: 0; min-width: 900px;">
                <thead>
                    <tr class="jadwal-group-row">
                        <th style="width: 130px; min-width: 130px; background: var(--bg-card);">Grup</th>
                        @foreach ($gridGroups as $group)
                            @php $gCount = count($group['rombel']); @endphp
                            <th colspan="{{ $gCount }}" class="jadwal-group-start"
                                title="{{ $group['nama'] }} — {{ $gCount }} kelas">
                                {{ $group['label'] }}
                                <span class="jadwal-group-count">({{ $gCount }})</span>
                            </th>
                        @endforeach
                        @if (count($gridGroups) === 0)
                            <th>&nbsp;</th>
                        @endif
                    </tr>
                    <tr>
                        <!-- Header Kolom Jam Pelajaran (Sticky Left) -->
                        <th
                            style="position: sticky; left: 0; z-index: 20; background: var(--bg-card); width: 130px; min-width: 130px; padding: 14px 12px; border-bottom: 2px solid var(--border-color); border-right: 2px solid var(--border-color); font-size: var(--text-sm, 0.8125rem); font-weight: 800; color: var(--text-color); text-transform: uppercase;">
                            <div style="display: flex; align-items: center; gap: 6px;">
                                <i class="fas fa-clock text-primary"></i> Jam Pelajaran
                            </div>
                        </th>

                        <!-- Header Kolom Seluruh Kelas / Rombel -->
                        @forelse ($gridRombels as $r)
                            <th
                                style="padding: 12px 10px; border-bottom: 2px solid var(--border-color); border-right: 1px solid var(--border-color); text-align: center; min-width: 180px; max-width: 220px; background: var(--bg-card);">
                                <div
                                    style="font-weight: 800; font-size: var(--text-base, 0.875rem); color: var(--text-color); margin-bottom: 2px;">
                                    {{ $r->nama }}
                                </div>
                                <span class="badge"
                                    style="background: rgba(99,102,241,0.12); color: var(--primary); font-size: var(--text-xs, 0.75rem); padding: 2px 6px; font-weight: 700;">
                                    Tingkat {{ $r->tingkat_pendidikan_id }}
                                </span>
                            </th>
                        @empty
                            <th style="padding: 14px; text-align: center; color: var(--text-muted); font-size: var(--text-base, 0.875rem);">
                                Tidak ada rombel pada tingkat ini.
                            </th>
                        @endforelse
                    </tr>
                </thead>
                <tbody>
                    @forelse ($timeSlots as $slotKey => $slot)
                        <tr style="height: 64px;">
                            <!-- Sticky Left: Label Jam Pelajaran -->
                            <td
                                style="position: sticky; left: 0; z-index: 10; background: var(--bg-card); height: 64px; box-sizing: border-box; padding: 6px 10px; border-bottom: 1px solid var(--border-color); border-right: 2px solid var(--border-color); vertical-align: middle;">
                                <div
                                    style="font-weight: 800; font-size: var(--text-sm, 0.8125rem); color: var(--text-color); display: flex; align-items: center; justify-content: space-between;">
                                    <span>Jam Ke-{{ $slot['ke'] }}</span>
                                    <span class="badge"
                                        style="background: var(--bg-hover); color: var(--text-muted); font-size: var(--text-2xs, 0.6875rem); font-weight: 700; padding: 2px 5px;">JP</span>
                                </div>
                                <div
                                    style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-top: 3px; font-weight: 600;">
                                    {{ $slot['mulai'] }} - {{ $slot['selesai'] }}
                                </div>
                            </td>

                            <!-- Cell Per Rombel -->
                            @foreach ($gridRombels as $r)
                                @php
                                    $rId = $r->rombongan_belajar_id;
                                    $items = $gridMatrix[$rId][$slotKey] ?? [];
                                    $isOccupied = isset($gridOccupied[$rId][$slotKey]);
                                    $isBerderet = count($items) > 1;
                                @endphp

                                @if (count($items) > 0)
                                    @php
                                        // Blok terpanjang menentukan rowspan sel; jadwal lain pada
                                        // jam sama ditumpuk berderet di dalam sel (tidak menimpa).
                                        $durasiSel = 1;
                                        foreach ($items as $it) {
                                            $durasiSel = max($durasiSel, max(1, (int) $it->jam_ke_selesai - (int) $it->jam_ke_mulai + 1));
                                        }
                                    @endphp
                                    <!-- Sel Terisi Jadwal (Spanning Multi JP, boleh memuat >1 jadwal) -->
                                    <td rowspan="{{ $durasiSel }}"
                                        style="height: {{ $durasiSel * 64 }}px; padding: 4px; border-bottom: 1px solid var(--border-color); border-right: 1px solid var(--border-color); vertical-align: top; background: rgba(99,102,241,0.03); box-sizing: border-box;">
                                        <div class="jadwal-cell-stack" style="height: 100%;">
                                            @foreach ($items as $item)
                                                @include('dashboard.jadwal-kbm.partials.kartu-jadwal-sel', [
                                                    'item' => $item,
                                                    'durasi' => max(1, (int) $item->jam_ke_selesai - (int) $item->jam_ke_mulai + 1),
                                                    'isBerderet' => $isBerderet,
                                                    'namaRombel' => $r->nama,
                                                    'canUpdate' => $canUpdate,
                                                    'canDelete' => $canDelete,
                                                ])
                                            @endforeach
                                        </div>
                                    </td>
                                @elseif (!$isOccupied)
                                    <!-- Sel Kosong (Interaktif Click-to-Assign) -->
                                    <td
                                        style="padding: 4px; border-bottom: 1px solid var(--border-color); border-right: 1px solid var(--border-color); vertical-align: middle; text-align: center; height: 64px; box-sizing: border-box;">
                                        @if ($canCreate)
                                            <button type="button" class="btn-quick-slot"
                                                title="Klik untuk mengisi jadwal di kelas {{ $r->nama }} Jam Ke-{{ $slot['ke'] }}"
                                                data-rombel-id="{{ $r->rombongan_belajar_id }}"
                                                data-rombel-nama="{{ $r->nama }}" data-hari="{{ $selectedHari }}"
                                                data-jam-ke="{{ $slot['ke'] }}" data-jam-mulai="{{ $slot['mulai'] }}"
                                                data-jam-selesai="{{ $slot['selesai'] }}"
                                                style="width: 100%; height: 100%; min-height: 54px; border: 1px dashed var(--border-color); border-radius: var(--radius-sm, 6px); background: transparent; cursor: pointer; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 2px; color: var(--text-muted); transition: all 0.2s ease;">
                                                <i class="fas fa-plus" style="font-size: var(--text-xs, 0.75rem);"></i>
                                                <span style="font-size: var(--text-2xs, 0.6875rem); font-weight: 600;">Isi Jadwal</span>
                                            </button>
                                        @else
                                            <div
                                                style="min-height: 54px; display: flex; align-items: center; justify-content: center; color: var(--text-muted); font-size: var(--text-xs, 0.75rem);">
                                                -
                                            </div>
                                        @endif
                                    </td>
                                @endif
                                <!-- Jika isOccupied bernilai true, jangan render <td> karena cell atasnya sudah me-rowspan -->
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ max(1, count($gridRombels) + 1) }}"
                                style="padding: 48px 24px; text-align: center; color: var(--text-muted); background: var(--bg-card);">
                                <i class="fas fa-calendar-xmark mb-3" style="font-size: var(--text-3xl, 1.875rem); opacity: 0.4;"></i>
                                <div
                                    style="font-weight: 700; font-size: var(--text-md, 1rem); color: var(--text-color); margin-bottom: 4px;">
                                    Tidak Ada KBM pada Hari {{ $selectedHari }}</div>
                                <div style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted);">Total JP untuk hari
                                    {{ $selectedHari }} diatur 0 JP (Libur). Klik tombol <strong>Atur Jam
                                        Pelajaran</strong> untuk menyesuaikan jam pelajaran.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @elseif ($viewMode === 'pekan')
        @include('dashboard.jadwal-kbm.partials.tab-pekan')
    @else
        @include('dashboard.jadwal-kbm.partials.tab-rekap-tabel')
    @endif

    @include('dashboard.jadwal-kbm.partials.modal-form-jadwal')
    @include('dashboard.jadwal-kbm.partials.modal-auto-scheduler')
    @include('dashboard.jadwal-kbm.partials.modal-preferensi-guru')
    @include('dashboard.jadwal-kbm.partials.modal-pekan')
@endsection

@push('scripts')
    <script
        src="{{ asset('js/jadwal-kbm.js') }}?v={{ file_exists(public_path('js/jadwal-kbm.js')) ? filemtime(public_path('js/jadwal-kbm.js')) : time() }}">
    </script>
    <script
        src="{{ asset('js/jadwal-kbm-auto.js') }}?v={{ file_exists(public_path('js/jadwal-kbm-auto.js')) ? filemtime(public_path('js/jadwal-kbm-auto.js')) : time() }}">
    </script>
    <script
        src="{{ asset('js/jadwal-kbm-pekan.js') }}?v={{ file_exists(public_path('js/jadwal-kbm-pekan.js')) ? filemtime(public_path('js/jadwal-kbm-pekan.js')) : time() }}">
    </script>
@endpush
