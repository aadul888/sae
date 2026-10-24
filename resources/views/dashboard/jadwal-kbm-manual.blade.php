@extends('layouts.dashboard')

@section('title', 'Jadwal KBM Manual — ' . ($activeRombel ? $activeRombel->nama : 'Kelas') . ' — SAE')
@section('dash_title', 'Jadwal KBM Manual' . ($activeRombel ? ' — ' . $activeRombel->nama : ''))

@push('styles')
    <link rel="stylesheet"
        href="{{ asset('css/jadwal-kbm-manual.css') }}?v={{ file_exists(public_path('css/jadwal-kbm-manual.css')) ? filemtime(public_path('css/jadwal-kbm-manual.css')) : time() }}">
    <style>
        .banner-action-buttons {
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .jadwal-subnav-desktop {
            background: var(--bg-card);
            padding: 4px;
            border-radius: 12px;
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

            .banner-action-buttons {
                display: flex !important;
                width: 100% !important;
                gap: 6px !important;
            }

            .banner-action-buttons>* {
                flex: 1 !important;
                min-width: 0 !important;
                padding: 8px 6px !important;
                display: inline-flex !important;
                align-items: center !important;
                justify-content: center !important;
                font-size: 0.78rem !important;
            }

            .jadwal-subnav-desktop {
                display: flex !important;
                width: 100% !important;
            }

            .jadwal-subnav-desktop .periode-nav-tab {
                flex: 1 !important;
                text-align: center !important;
                justify-content: center !important;
                font-size: 0.78rem !important;
                padding: 8px 8px !important;
            }
        }
    </style>
@endpush

@section('content')
    <!-- Header Banner -->
    <div class="dash-banner">
        <div>
            <h2 style="font-size: 1.35rem; font-weight: 800; color: var(--text-color); margin-bottom: 4px;">
                <i class="fas fa-calendar-plus text-primary me-2"></i> Input Jadwal KBM Manual
                @if ($activeRombel)
                    <span class="badge badge-primary"
                        style="font-size: 0.82rem; margin-left: 6px; padding: 4px 10px; border-radius: 6px;">
                        Kelas {{ $activeRombel->nama }}
                    </span>
                @endif
            </h2>
            <p style="color: var(--text-muted); font-size: 0.85rem;">
                Modul khusus Wali Kelas / Koordinator Kelas untuk menyusun jam dan mata pelajaran sesuai guru pengampu
                secara terstruktur dan anti-bentrok.
            </p>
        </div>
        <div class="dash-banner-actions" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
            <!-- Pemberlakuan Jadwal Aktif oleh Admin (Eksklusif: Otomatis vs Manual) -->
            @if ($isAdmin)
                <div class="pemberlakuan-badge-wrap" style="display: inline-flex; align-items: center; gap: 8px;">
                    @if ($isManualAktif)
                        <span class="badge"
                            style="background: rgba(16,185,129,0.15); color: #10b981; border: 1px solid rgba(16,185,129,0.3); font-size: 0.78rem; padding: 6px 12px; border-radius: 8px; display: inline-flex; align-items: center; gap: 6px;">
                            <i class="fas fa-check-circle"></i> Jadwal Manual Diberlakukan (Aktif)
                        </span>
                        <button type="button" class="btn btn-outline btn-toggle-pemberlakuan" data-mode="manual"
                            data-status="draft" data-url="{{ route('dashboard.jadwal-kbm.toggle-pemberlakuan') }}"
                            onclick="if(window.handleTogglePemberlakuan){window.handleTogglePemberlakuan(this);}"
                            title="Alihkan ke Status Draft (Sembunyikan dari Guru/Siswa)"
                            style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; border-color: rgba(245,158,11,0.4); color: #f59e0b; cursor: pointer;">
                            <i class="fas fa-pause"></i>
                        </button>
                    @elseif ($isOtomatisAktif)
                        <span class="badge"
                            style="background: rgba(245,158,11,0.15); color: #f59e0b; border: 1px solid rgba(245,158,11,0.3); font-size: 0.78rem; padding: 6px 12px; border-radius: 8px; display: inline-flex; align-items: center; gap: 6px;">
                            <i class="fas fa-clock"></i> Status Draft (Jadwal Otomatis Aktif)
                        </span>
                        <button type="button" class="btn btn-toggle-pemberlakuan" data-mode="manual" data-status="aktif"
                            data-url="{{ route('dashboard.jadwal-kbm.toggle-pemberlakuan') }}"
                            onclick="if(window.handleTogglePemberlakuan){window.handleTogglePemberlakuan(this);}"
                            title="Berlakukan Jadwal Manual Ini ke Seluruh Guru & Siswa (Menonaktifkan Jadwal Otomatis)"
                            style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; background: #10b981; color: #fff; font-weight: 700; border: none; box-shadow: 0 2px 8px rgba(16,185,129,0.3); cursor: pointer;">
                            <i class="fas fa-play"></i>
                        </button>
                    @else
                        <span class="badge"
                            style="background: rgba(245,158,11,0.15); color: #f59e0b; border: 1px solid rgba(245,158,11,0.3); font-size: 0.78rem; padding: 6px 12px; border-radius: 8px; display: inline-flex; align-items: center; gap: 6px;">
                            <i class="fas fa-clock"></i> Status Draft (Dalam Penyusunan)
                        </span>
                        <button type="button" class="btn btn-toggle-pemberlakuan" data-mode="manual" data-status="aktif"
                            data-url="{{ route('dashboard.jadwal-kbm.toggle-pemberlakuan') }}"
                            onclick="if(window.handleTogglePemberlakuan){window.handleTogglePemberlakuan(this);}"
                            title="Berlakukan Jadwal Manual Ini ke Seluruh Guru & Siswa"
                            style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; background: #10b981; color: #fff; font-weight: 700; border: none; box-shadow: 0 2px 8px rgba(16,185,129,0.3); cursor: pointer;">
                            <i class="fas fa-play"></i>
                        </button>
                    @endif
                </div>
            @endif

            <!-- Selector Rombel (Admin, Wali Kelas, & Guru Mapel) -->
            @if (($isAdmin || !$isLockedRombel || $rombelList->count() > 1) && $rombelList->isNotEmpty())
                <div style="display: flex; align-items: center; gap: 6px;">
                    <label for="adminSelectRombel"
                        style="font-size: 0.8rem; font-weight: 700; color: var(--text-muted); margin: 0;">Pilih
                        Kelas:</label>
                    <select id="adminSelectRombel" class="per-page-select"
                        style="padding: 6px 12px; font-weight: 700; border-radius: 8px;">
                        @foreach ($rombelList as $r)
                            <option value="{{ $r->rombongan_belajar_id }}"
                                {{ $activeRombel && $activeRombel->rombongan_belajar_id === $r->rombongan_belajar_id ? 'selected' : '' }}>
                                {{ $r->nama }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif

            @if ($activeRombel)
                <div class="banner-action-buttons" style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                    @if (!empty($hasOtomatisSchedule) && ($isAdmin || !empty($isWaliKelas) || !empty($isGuru)))
                        <button type="button" class="btn btn-outline" id="btnSalinOtomatis"
                            data-rombel-id="{{ $activeRombel->rombongan_belajar_id }}"
                            data-rombel-name="{{ $activeRombel->nama }}"
                            style="display: inline-flex; align-items: center; gap: 6px; padding: 7px 12px; border-radius: 8px; font-size: 0.82rem; font-weight: 700; border-color: rgba(99,102,241,0.35); color: var(--primary);"
                            title="Salin seluruh susunan mata pelajaran dari Jadwal Induk (Otomatis) ke kelas ini agar seluruh baris JP terisi padat">
                            <i class="fas fa-wand-magic-sparkles"></i>
                            <span class="d-none d-sm-inline">Penuhi dari Jadwal Induk</span>
                        </button>
                    @endif

                    <a href="{{ route('dashboard.jadwal-kbm.cetak-rombel', ['rombel_id' => $activeRombel->rombongan_belajar_id]) }}"
                        target="_blank" class="btn btn-outline"
                        style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; font-size: 0.95rem; text-decoration: none;"
                        title="Cetak Jadwal Kelas Ini (Format Siap Tempel)">
                        <i class="fas fa-print text-primary"></i>
                    </a>

                    @if ($isAdmin || !empty($isWaliKelas))
                        <button type="button" class="btn btn-outline" id="btnResetRombel"
                            data-rombel-id="{{ $activeRombel->rombongan_belajar_id }}"
                            data-rombel-name="{{ $activeRombel->nama }}"
                            style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; font-size: 0.95rem; border-color: rgba(239,68,68,0.4); color: #ef4444;"
                            title="Kosongkan Seluruh Jadwal Kelas Ini">
                            <i class="fas fa-rotate-left"></i>
                        </button>
                    @endif

                    <button type="button" class="btn btn-primary" id="btnTambahSlotManual"
                        style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; font-size: 0.95rem;"
                        title="Tambah Slot Pelajaran Baru">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
            @endif
        </div>
    </div>

    <!-- Sub-Menu Navigasi Jadwal KBM (Generate vs Manual vs Jadwal Mengajar) -->
    <div class="periode-nav-wrapper" style="margin-bottom: 20px;">
        <div class="jadwal-subnav-desktop">
            @if ($role === 'guru' || $isAdmin)
                <a href="{{ route('dashboard.jadwal-pelajaran.index', ['view' => 'guru']) }}" class="periode-nav-tab">
                    <i class="fas fa-calendar-days me-1"></i> Jadwal Mengajar Saya
                </a>
            @endif
            @if ($isAdmin || \App\Models\RolePermission::canAccess(session('user'), 'menu_jadwal_kbm', 'read'))
                <a href="{{ route('dashboard.jadwal-kbm.index') }}" class="periode-nav-tab">
                    <i class="fas fa-wand-magic-sparkles me-1"></i> Generate Jadwal (Otomatis)
                </a>
            @endif
            <a href="{{ route('dashboard.jadwal-kbm.manual.index', $activeRombel ? ['rombel_id' => $activeRombel->rombongan_belajar_id] : []) }}"
                class="periode-nav-tab active">
                <i class="fas fa-pen-to-square me-1"></i> Jadwal Manual (Per Kelas)
            </a>
        </div>
    </div>

    <!-- Banner Integrasi Kalender Pendidikan -->
    <div class="card"
        style="padding: 12px 18px; margin-bottom: 18px; border-radius: 12px; background: var(--bg-card); border: 1px solid var(--border-color); display: flex; align-items: center; justify-content: space-between; gap: 14px; flex-wrap: wrap;">
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
                style="width: 40px; height: 40px; border-radius: 10px; background: {{ $bgIcon }}; color: {{ $colIcon }}; display: flex; align-items: center; justify-content: center; font-size: 1.15rem; flex-shrink: 0;">
                <i class="fas {{ $iconClass }}"></i>
            </div>
            <div>
                <div
                    style="font-size: 0.88rem; font-weight: 700; color: var(--text-color); display: flex; align-items: center; gap: 8px;">
                    <span>Kalender Akademik: TA {{ $tahunAjaranAktif ?? '2026/2027' }} (Semester
                        {{ $semesterAktif ?? '1' }} {{ ($semesterAktif ?? '1') == '1' ? 'Ganjil' : 'Genap' }})</span>
                    @if ($isLibur)
                        <span class="badge"
                            style="background: rgba(239,68,68,0.15); color: #ef4444; font-size: 0.72rem; padding: 2px 8px; border-radius: 8px;">Libur</span>
                    @elseif (!empty($agendaHariIni))
                        <span class="badge"
                            style="background: rgba(99,102,241,0.15); color: var(--primary); font-size: 0.72rem; padding: 2px 8px; border-radius: 8px;">Agenda
                            Khusus</span>
                    @else
                        <span class="badge"
                            style="background: rgba(16,185,129,0.15); color: #10b981; font-size: 0.72rem; padding: 2px 8px; border-radius: 8px;">Hari
                            Efektif KBM</span>
                    @endif
                </div>
                <div style="font-size: 0.78rem; color: var(--text-muted); margin-top: 2px;">
                    Hari Ini ({{ \Carbon\Carbon::parse($tanggalHariIni ?? now())->translatedFormat('l, d F Y') }}):
                    @if ($isLibur)
                        <strong
                            style="color: #ef4444;">{{ $statusHariIni['keterangan'] ?? 'Hari Libur / Tidak Ada KBM' }}</strong>
                    @elseif (!empty($agendaHariIni))
                        <strong
                            style="color: var(--primary);">{{ $agendaHariIni->nama_agenda ?: ($agendaHariIni->nama_kegiatan ?: $agendaHariIni->kegiatan ?? 'Agenda Khusus') }}</strong>
                    @else
                        KBM Berjalan Normal
                    @endif
                </div>
            </div>
        </div>
        <div>
            <a href="{{ route('dashboard.kalender-pendidikan.index') }}" class="btn btn-outline"
                style="padding: 7px 14px; font-size: 0.82rem;">
                <i class="fas fa-calendar-alt me-1"></i> Buka Kalender Pendidikan
            </a>
        </div>
    </div>

    @if (!empty($errorNotice))
        <div class="card"
            style="padding: 24px; text-align: center; margin-bottom: 24px; border: 1.5px dashed rgba(239,68,68,0.4); background: rgba(239,68,68,0.04);">
            <div
                style="width: 52px; height: 52px; border-radius: 50%; background: rgba(239,68,68,0.12); color: #ef4444; display: inline-flex; align-items: center; justify-content: center; font-size: 1.5rem; margin-bottom: 12px;">
                <i class="fas fa-circle-exclamation"></i>
            </div>
            <h4 style="margin-bottom: 6px; font-weight: 700; color: var(--text-color);">Akses Belum Dikonfigurasi</h4>
            <p style="color: var(--text-muted); font-size: 0.88rem; max-width: 500px; margin: 0 auto;">
                {{ $errorNotice }}
            </p>
        </div>
    @elseif ($activeRombel)
        <!-- Summary Stats Grid & Progress Bar -->
        <div class="dash-stat-grid"
            style="grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); margin-bottom: 16px;">
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(99,102,241,0.15); color: var(--primary);">
                    <i class="fas fa-bullseye"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" style="font-size: 1.35rem;">
                        {{ $summary['target_jp'] }} <span style="font-size: 0.72rem; font-weight: 500;">JP</span>
                    </div>
                    <div class="dash-stat-label">Target JJM Mingguan</div>
                </div>
            </div>

            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(16,185,129,0.15); color: #10b981;">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" style="font-size: 1.35rem; color: #10b981;">
                        {{ $summary['total_terjadwal'] }} <span style="font-size: 0.72rem; font-weight: 500;">JP</span>
                    </div>
                    <div class="dash-stat-label">Sudah Terjadwal</div>
                </div>
            </div>

            <div class="dash-stat-card">
                <div class="dash-stat-icon"
                    style="background: {{ $summary['sisa_jp'] > 0 ? 'rgba(245,158,11,0.15)' : 'rgba(16,185,129,0.15)' }}; color: {{ $summary['sisa_jp'] > 0 ? '#f59e0b' : '#10b981' }};">
                    <i class="fas {{ $summary['sisa_jp'] > 0 ? 'fa-hourglass-half' : 'fa-circle-check' }}"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value"
                        style="font-size: 1.35rem; color: {{ $summary['sisa_jp'] > 0 ? '#f59e0b' : '#10b981' }};">
                        {{ $summary['sisa_jp'] }} <span style="font-size: 0.72rem; font-weight: 500;">JP</span>
                    </div>
                    <div class="dash-stat-label">Sisa Belum Terjadwal</div>
                </div>
            </div>

            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(14,165,233,0.15); color: #0ea5e9;">
                    <i class="fas fa-book-open"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" style="font-size: 1.35rem;">
                        {{ $summary['mapel_lengkap'] }} / {{ $summary['total_mapel'] }}
                    </div>
                    <div class="dash-stat-label">Mapel Lengkap Terisi</div>
                </div>
            </div>
        </div>

        <!-- Progress Bar Keterisian Jadwal -->
        <div class="card" style="padding: 14px 18px; margin-bottom: 22px; border-radius: 10px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                <span style="font-size: 0.82rem; font-weight: 700; color: var(--text-color);">
                    <i class="fas fa-chart-line text-primary me-1"></i> Progres Penyusunan Jadwal Kelas
                    {{ $activeRombel->nama }}
                </span>
                <span
                    style="font-size: 0.82rem; font-weight: 800; color: {{ $summary['persen_selesai'] >= 100 ? '#10b981' : 'var(--primary)' }};">
                    {{ $summary['persen_selesai'] }}% Selesai ({{ $summary['total_terjadwal'] }} dari
                    {{ $summary['target_jp'] }} JP)
                </span>
            </div>
            <div
                style="width: 100%; height: 9px; background: var(--bg-hover); border-radius: 8px; overflow: hidden; border: 1px solid var(--border-color);">
                <div
                    style="width: {{ $summary['persen_selesai'] }}%; height: 100%; background: {{ $summary['persen_selesai'] >= 100 ? 'linear-gradient(90deg, #10b981, #059669)' : 'linear-gradient(90deg, #6366f1, #8b5cf6)' }}; transition: width 0.4s ease;">
                </div>
            </div>
        </div>

        <!-- Mobile View Switcher (Khusus Layar < 992px) -->
        <div class="manual-mobile-switcher">
            <div class="manual-tab-bar">
                <button type="button" class="manual-tab-btn active" data-tab="matriks">
                    <i class="fas fa-table-cells me-1"></i> Matriks Jadwal
                </button>
                <button type="button" class="manual-tab-btn" data-tab="mapel">
                    <i class="fas fa-chalkboard-user me-1"></i> Guru &amp; Mapel ({{ $pembelajaranData->count() }})
                </button>
            </div>
        </div>

        <!-- Dual-Panel Layout: Panel Kiri (Mapel & Guru) & Panel Kanan (Matriks Slot Mingguan) -->
        <div class="jadwal-manual-layout-grid">

            <!-- Panel Kiri: Daftar Mapel & Guru Pengampu -->
            <div class="card panel-mapel-wrap" id="panelMapelWrapper">
                <div
                    style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; padding-bottom: 10px; border-bottom: 1px solid var(--border-color);">
                    <div>
                        <h4 style="margin: 0; font-size: 0.95rem; font-weight: 800; color: var(--text-color);">
                            <i class="fas fa-chalkboard-user text-primary me-1"></i> Guru Pengampu &amp; Mapel
                        </h4>
                        <div style="font-size: 0.74rem; color: var(--text-muted); margin-top: 2px;">
                            Berdasarkan data pembelajaran kelas
                        </div>
                    </div>
                    <span class="badge badge-outline" style="font-size: 0.72rem;">{{ $pembelajaranData->count() }}
                        Mapel</span>
                </div>

                <div style="display: flex; flex-direction: column; gap: 10px;" id="pembelajaranListWrap">
                    @forelse ($pembelajaranData as $p)
                        @php
                            $isLengkap = $p->is_complete;
                            $borderCol = $isLengkap
                                ? 'rgba(16,185,129,0.3)'
                                : ($p->scheduled_jp > 0
                                    ? 'rgba(99,102,241,0.3)'
                                    : 'var(--border-color)');
                            $bgCol = $isLengkap ? 'rgba(16,185,129,0.04)' : 'var(--bg-card)';
                        @endphp
                        <div class="card-mapel-item"
                            style="padding: 12px; border-radius: 10px; border: 1px solid {{ $borderCol }}; background: {{ $bgCol }}; transition: transform 0.15s ease, border-color 0.15s ease;">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 8px;">
                                <div style="flex: 1; min-width: 0;">
                                    <div style="font-size: 0.86rem; font-weight: 800; color: var(--text-color); margin-bottom: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"
                                        title="{{ $p->nama_mata_pelajaran }}">
                                        {{ $p->nama_mata_pelajaran }}
                                    </div>
                                    <div
                                        style="font-size: 0.76rem; color: var(--text-muted); display: flex; align-items: center; gap: 5px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                        <i class="fas fa-user-tie" style="font-size: 0.7rem; opacity: 0.7;"></i>
                                        <span
                                            title="{{ $p->nama_guru ?: 'Belum ditentukan' }}">{{ $p->nama_guru ?: 'Guru belum ditentukan' }}</span>
                                    </div>
                                </div>
                                @if ($p->is_pilihan)
                                    <span class="badge"
                                        style="background: rgba(14,165,233,0.12); color: #0ea5e9; font-size: 0.65rem; padding: 2px 6px; border-radius: 4px; flex-shrink: 0;">
                                        Pilihan
                                    </span>
                                @endif
                            </div>

                            <div
                                style="display: flex; justify-content: space-between; align-items: center; margin-top: 10px; padding-top: 8px; border-top: 1px dashed var(--border-color);">
                                <div style="font-size: 0.74rem;">
                                    <span style="color: var(--text-muted);">Alokasi:</span>
                                    <strong style="color: var(--text-color);">{{ $p->target_jp }} JP</strong>
                                    &bull;
                                    <span
                                        style="color: {{ $isLengkap ? '#10b981' : ($p->scheduled_jp > 0 ? 'var(--primary)' : '#f59e0b') }}; font-weight: 700;">
                                        {{ $p->scheduled_jp }}/{{ $p->target_jp }} JP
                                    </span>
                                </div>

                                <button type="button" class="btn btn-sm btn-jadwalkan-mapel"
                                    data-pembelajaran-id="{{ $p->pembelajaran_id }}"
                                    data-mapel-id="{{ $p->mata_pelajaran_id }}"
                                    data-nama-mapel="{{ $p->nama_mata_pelajaran }}" data-ptk-id="{{ $p->ptk_id }}"
                                    data-nama-guru="{{ $p->nama_guru }}" data-target-jp="{{ $p->target_jp }}"
                                    data-remaining-jp="{{ $p->remaining_jp }}"
                                    style="padding: 4px 10px; font-size: 0.74rem; font-weight: 700; border-radius: 6px; {{ $isLengkap ? 'border: 1px solid var(--border-color); color: var(--text-muted); background: transparent;' : 'background: var(--primary); color: #fff; border: none;' }}"
                                    title="{{ $isLengkap ? 'Target JP sudah terpenuhi (tetap bisa dijadwalkan jika perlu)' : 'Jadwalkan mapel ini ke slot kosong' }}">
                                    <i class="fas fa-plus me-1"></i> Jadwalkan
                                </button>
                            </div>
                        </div>
                    @empty
                        <div style="text-align: center; padding: 24px; color: var(--text-muted); font-size: 0.82rem;">
                            <i class="fas fa-folder-open mb-2" style="font-size: 1.5rem; opacity: 0.4;"></i>
                            <div>Belum ada data pembelajaran untuk kelas ini.</div>
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Panel Kanan: Matriks Slot Mingguan Kelas (Responsif Dual-Mode: Harian & Tabel) -->
            <div class="card panel-matriks-wrap" id="panelMatriksWrapper">
                <div
                    style="padding: 14px 18px; background: rgba(255,255,255,0.02); border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                    <div>
                        <h4 style="margin: 0; font-size: 0.95rem; font-weight: 800; color: var(--text-color);">
                            <i class="fas fa-table-cells text-primary me-1"></i> Matriks Jadwal Mingguan Kelas
                        </h4>
                        <div style="font-size: 0.74rem; color: var(--text-muted); margin-top: 2px;">
                            Klik slot kosong untuk memasukkan pelajaran atau klik kartu pelajaran untuk mengedit/menghapus.
                        </div>
                    </div>
                    <div style="display: flex; gap: 10px; font-size: 0.72rem; flex-wrap: wrap;">
                        <span style="display: inline-flex; align-items: center; gap: 4px; color: var(--text-muted);">
                            <span
                                style="width: 10px; height: 10px; border-radius: 3px; background: rgba(99,102,241,0.2); border: 1px solid var(--primary);"></span>
                            Terjadwal
                        </span>
                        <span style="display: inline-flex; align-items: center; gap: 4px; color: var(--text-muted);">
                            <span
                                style="width: 10px; height: 10px; border-radius: 3px; background: rgba(239,68,68,0.15); border: 1px solid #ef4444;"></span>
                            Upacara
                        </span>
                        <span style="display: inline-flex; align-items: center; gap: 4px; color: var(--text-muted);">
                            <span
                                style="width: 10px; height: 10px; border-radius: 3px; background: rgba(16,185,129,0.15); border: 1px solid #10b981;"></span>
                            Pembiasaan
                        </span>
                        <span style="display: inline-flex; align-items: center; gap: 4px; color: var(--text-muted);">
                            <span
                                style="width: 10px; height: 10px; border-radius: 3px; background: rgba(245,158,11,0.15); border: 1px solid #f59e0b;"></span>
                            Istirahat
                        </span>
                    </div>
                </div>

                <!-- Switcher Mode Tampilan Matriks Khusus Mobile (< 992px) -->
                <div class="mobile-matriks-switcher">
                    <div class="matriks-view-mode-bar">
                        <button type="button" class="matriks-mode-btn active" data-mode="harian">
                            <i class="fas fa-calendar-day me-1"></i> Mode Harian
                        </button>
                        <button type="button" class="matriks-mode-btn" data-mode="tabel">
                            <i class="fas fa-table-cells me-1"></i> Mode Tabel (Geser)
                        </button>
                    </div>
                </div>

                <!-- 1. TAMPILAN MODE HARIAN (Mobile-First Native Timeline) -->
                <div id="viewHarianMobile" class="view-harian-wrapper mode-active">
                    @php
                        $todayName = \Carbon\Carbon::now()->locale('id')->isoFormat('dddd');
                        $defaultActiveDay = in_array($todayName, $hariAktif, true)
                            ? $todayName
                            : $hariAktif[0] ?? 'Senin';
                    @endphp

                    <!-- Day Selector Pills (Scrollable Horizontal) -->
                    <div class="harian-day-pills-bar">
                        @foreach ($hariAktif as $h)
                            @php
                                $scheduledCount = 0;
                                if (isset($scheduleMatrix[$h])) {
                                    foreach ($scheduleMatrix[$h] as $sc) {
                                        if ($sc) {
                                            $scheduledCount++;
                                        }
                                    }
                                }
                            @endphp
                            <button type="button" class="harian-day-pill {{ $h === $defaultActiveDay ? 'active' : '' }}"
                                data-day="{{ $h }}">
                                <span>{{ $h }}</span>
                                @if ($scheduledCount > 0)
                                    <span class="day-count-badge">{{ $scheduledCount }} Mapel</span>
                                @endif
                            </button>
                        @endforeach
                    </div>

                    <!-- Feed List per Hari -->
                    @foreach ($hariAktif as $h)
                        @php
                            $daySlots = $timeSlotsByDay[$h] ?? [];
                        @endphp
                        <div class="harian-day-feed {{ $h === $defaultActiveDay ? 'active' : '' }}"
                            data-day="{{ $h }}">
                            @if (empty($daySlots))
                                <div class="harian-empty-day">
                                    <i class="fas fa-calendar-xmark mb-2" style="font-size: 2rem; opacity: 0.4;"></i>
                                    <div>Hari {{ $h }} tidak memiliki jam pelajaran atau berstatus libur.</div>
                                </div>
                            @else
                                <div class="harian-timeline-list">
                                    @foreach ($daySlots as $jamKe => $sInfo)
                                        @php
                                            $routine = $routinesMap[$h][$jamKe] ?? null;
                                            $schedule = $scheduleMatrix[$h][$jamKe] ?? null;
                                            $isOccupied = !empty($occupiedMatrix[$h][$jamKe]);
                                            $jamMulai = substr($sInfo['mulai'] ?? ($sInfo['jam_mulai'] ?? ''), 0, 5);
                                            $jamSelesai = substr(
                                                $sInfo['selesai'] ?? ($sInfo['jam_selesai'] ?? ''),
                                                0,
                                                5,
                                            );
                                        @endphp

                                        @if ($isOccupied)
                                            {{-- Slot ini terisi multi-JP lanjutan dari jam sebelumnya --}}
                                        @elseif ($schedule)
                                            @php
                                                $jpSpan = max(
                                                    1,
                                                    (int) $schedule->jam_ke_selesai - (int) $schedule->jam_ke_mulai + 1,
                                                );
                                                $isRoutine =
                                                    in_array(
                                                        $schedule->mata_pelajaran_id,
                                                        ['UPACARA', 'PEMBIASAAN', 'ISTIRAHAT'],
                                                        true,
                                                    ) ||
                                                    str_starts_with($schedule->pembelajaran_id ?? '', 'ROUTINE_') ||
                                                    in_array(
                                                        strtolower(trim($schedule->nama_mata_pelajaran)),
                                                        ['upacara', 'upacara bendera', 'istirahat', 'pembiasaan'],
                                                        true,
                                                    );
                                            @endphp
                                            <div class="harian-card-item harian-card-scheduled">
                                                <div class="harian-card-header">
                                                    <div class="harian-card-time">
                                                        <span class="harian-jp-badge">JP {{ $schedule->jam_ke_mulai }}
                                                            @if ($jpSpan > 1)
                                                                -{{ $schedule->jam_ke_selesai }}
                                                            @endif
                                                        </span>
                                                        <span class="harian-clock-text">{{ $jamMulai }} -
                                                            {{ $jamSelesai }} WIB</span>
                                                    </div>
                                                    <span class="badge"
                                                        style="background: {{ $isRoutine ? 'rgba(100,116,139,0.18)' : 'rgba(99,102,241,0.15)' }}; color: {{ $isRoutine ? 'var(--text-muted)' : 'var(--primary)' }}; font-size: 0.74rem; font-weight: 700; padding: 3px 8px; border-radius: 6px;">{{ $jpSpan }}
                                                        JP</span>
                                                </div>
                                                <div>
                                                    <h4 class="harian-mapel-name">{{ $schedule->nama_mata_pelajaran }}
                                                    </h4>
                                                    @if (!$isRoutine)
                                                        <div class="harian-guru-name">
                                                            <i class="fas fa-user-tie text-muted me-1"></i>
                                                            <span>{{ $schedule->nama_guru ?: 'Guru belum ditentukan' }}</span>
                                                        </div>
                                                    @endif
                                                    @if ($schedule->ruangan)
                                                        <div class="harian-room-name">
                                                            <i class="fas fa-location-dot text-success me-1"></i>
                                                            <span>{{ $schedule->ruangan }}</span>
                                                        </div>
                                                    @endif
                                                    @if ($routine && !$isRoutine)
                                                        <div class="harian-conflict-warn">
                                                            <i class="fas fa-triangle-exclamation me-1"></i>
                                                            <span>Bentrok dengan {{ $routine->nama }}</span>
                                                        </div>
                                                    @endif
                                                </div>
                                                <div class="harian-card-actions">
                                                    @if ($isRoutine)
                                                        <span class="badge"
                                                            style="background: rgba(100,116,139,0.15); color: var(--text-muted); font-size: 0.75rem; padding: 6px 12px; border-radius: 6px; display: inline-flex; align-items: center; gap: 5px;"
                                                            title="Kegiatan rutin sekolah otomatis berdasarkan Pengaturan Jam &amp; Bel KBM">
                                                            <i class="fas fa-lock" style="font-size: 0.65rem;"></i>
                                                            Pengaturan Jam
                                                        </span>
                                                    @else
                                                        <button type="button"
                                                            class="btn btn-sm btn-outline btn-edit-slot"
                                                            data-id="{{ $schedule->id }}"
                                                            data-pembelajaran-id="{{ $schedule->pembelajaran_id }}"
                                                            data-mapel-id="{{ $schedule->mata_pelajaran_id }}"
                                                            data-nama-mapel="{{ $schedule->nama_mata_pelajaran }}"
                                                            data-hari="{{ $schedule->hari }}"
                                                            data-jam-mulai="{{ $schedule->jam_ke_mulai }}"
                                                            data-jam-selesai="{{ $schedule->jam_ke_selesai }}"
                                                            data-ruangan="{{ $schedule->ruangan }}"
                                                            data-keterangan="{{ $schedule->keterangan }}"
                                                            style="padding: 6px 14px; font-size: 0.8rem; border-radius: 6px;">
                                                            <i class="fas fa-pen me-1"></i> Edit
                                                        </button>
                                                        <button type="button"
                                                            class="btn btn-sm btn-outline btn-hapus-slot"
                                                            data-id="{{ $schedule->id }}"
                                                            data-nama-mapel="{{ $schedule->nama_mata_pelajaran }}"
                                                            data-hari="{{ $schedule->hari }}"
                                                            data-jam="Jam ke-{{ $schedule->jam_ke_mulai }} s/d {{ $schedule->jam_ke_selesai }}"
                                                            style="padding: 6px 14px; font-size: 0.8rem; border-radius: 6px; color: #ef4444; border-color: rgba(239,68,68,0.3);">
                                                            <i class="fas fa-trash me-1"></i> Hapus
                                                        </button>
                                                    @endif
                                                </div>
                                            </div>
                                        @elseif ($routine)
                                            @php
                                                $rType = $routine->tipe ?? '';
                                                $rColor =
                                                    $rType === 'UPACARA'
                                                        ? '#ef4444'
                                                        : ($rType === 'PEMBIASAAN'
                                                            ? '#10b981'
                                                            : '#f59e0b');
                                            @endphp
                                            <div class="harian-card-item"
                                                style="border-left: 4px solid {{ $rColor }};">
                                                <div class="harian-card-header">
                                                    <div class="harian-card-time">
                                                        <span class="harian-jp-badge">JP {{ $jamKe }}</span>
                                                        <span class="harian-clock-text">{{ $jamMulai }} -
                                                            {{ $jamSelesai }} WIB</span>
                                                    </div>
                                                    <span class="badge"
                                                        style="background: rgba(255,255,255,0.06); color: {{ $rColor }}; font-size: 0.72rem;">{{ $routine->durasi }}</span>
                                                </div>
                                                <div>
                                                    <div
                                                        style="font-weight: 700; color: {{ $rColor }}; font-size: 0.9rem; display: flex; align-items: center; gap: 6px;">
                                                        <i class="fas {{ $routine->icon }}"></i>
                                                        <span>{{ $routine->nama }}</span>
                                                    </div>
                                                    <div
                                                        style="font-size: 0.74rem; color: var(--text-muted); margin-top: 2px;">
                                                        Slot kegiatan rutin sekolah terjadwal otomatis.
                                                    </div>
                                                </div>
                                            </div>
                                        @else
                                            <!-- Slot Kosong -->
                                            <div class="harian-card-item"
                                                style="border: 1.5px dashed var(--border-color);">
                                                <div class="harian-card-header"
                                                    style="border-bottom: none; margin-bottom: 0; padding-bottom: 0;">
                                                    <div class="harian-card-time">
                                                        <span class="harian-jp-badge">JP {{ $jamKe }}</span>
                                                        <span class="harian-clock-text">{{ $jamMulai }} -
                                                            {{ $jamSelesai }} WIB</span>
                                                    </div>
                                                    <button type="button" class="btn btn-sm btn-outline btn-empty-slot"
                                                        data-hari="{{ $h }}"
                                                        data-jam-ke="{{ $jamKe }}"
                                                        style="padding: 6px 14px; font-size: 0.78rem; font-weight: 700; border-radius: 6px; display: inline-flex; align-items: center; gap: 5px;">
                                                        <i class="fas fa-plus"></i> Tambah Pelajaran
                                                    </button>
                                                </div>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>

                <!-- 2. TAMPILAN MODE TABEL (Matriks Mingguan Horizontal Scrollable) -->
                <div id="viewTabelWeekly" class="view-tabel-wrapper">
                    <!-- Hint Scroll Horizontal Khusus Mobile -->
                    <div class="table-scroll-hint">
                        <i class="fas fa-arrows-left-right text-primary"></i>
                        <span>Geser tabel ke samping untuk melihat seluruh hari &rarr;</span>
                    </div>

                    <div class="matrix-scroll-container">
                        <table class="table matrix-table">
                            <thead>
                                <tr
                                    style="background: rgba(255,255,255,0.03); border-bottom: 1.5px solid var(--border-color);">
                                    <th class="sticky-jp-col"
                                        style="padding: 10px 8px; width: 88px; min-width: 88px; text-align: center; font-weight: 800; color: var(--text-muted); text-transform: uppercase; font-size: 0.72rem;">
                                        Jam / JP
                                    </th>
                                    @foreach ($hariAktif as $h)
                                        <th
                                            style="padding: 10px 8px; min-width: 155px; text-align: center; font-weight: 800; color: var(--text-color); font-size: 0.82rem; border-left: 1px solid var(--border-color);">
                                            {{ $h }}
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $maxSlots = 0;
                                    foreach ($hariAktif as $h) {
                                        $maxSlots = max($maxSlots, count($timeSlotsByDay[$h] ?? []));
                                    }
                                @endphp

                                @for ($jamKe = 1; $jamKe <= $maxSlots; $jamKe++)
                                    <tr style="height: 64px; border-bottom: 1px solid var(--border-color);">
                                        <!-- Kolom Jam Pelajaran (Sticky di Layar Mobile) -->
                                        <td class="sticky-jp-col"
                                            style="height: 64px; box-sizing: border-box; padding: 6px 8px; text-align: center; vertical-align: middle; border-bottom: 1px solid var(--border-color);">
                                            <div style="font-weight: 800; font-size: 0.82rem; color: var(--text-color);">JP
                                                {{ $jamKe }}</div>
                                            @php
                                                $firstDaySlot = $timeSlotsByDay[$hariAktif[0] ?? 'Senin'] ?? [];
                                                $slotInfo = $firstDaySlot[$jamKe] ?? null;
                                            @endphp
                                            @if ($slotInfo)
                                                <div
                                                    style="font-size: 0.67rem; color: var(--text-muted); margin-top: 2px; font-weight: 600;">
                                                    {{ substr($slotInfo['mulai'] ?? ($slotInfo['jam_mulai'] ?? ''), 0, 5) }}
                                                    -
                                                    {{ substr($slotInfo['selesai'] ?? ($slotInfo['jam_selesai'] ?? ''), 0, 5) }}
                                                </div>
                                            @endif
                                        </td>

                                        <!-- Kolom Per Hari -->
                                        @foreach ($hariAktif as $h)
                                            @php
                                                $daySlots = $timeSlotsByDay[$h] ?? [];
                                                $daySlotCount = count($daySlots);
                                                $isSlotExists = isset($daySlots[$jamKe]);
                                                $routine = $routinesMap[$h][$jamKe] ?? null;
                                                $schedule = $scheduleMatrix[$h][$jamKe] ?? null;
                                                $isOccupied = !empty($occupiedMatrix[$h][$jamKe]);
                                            @endphp

                                            @if (!$isSlotExists)
                                                @if ($jamKe === $daySlotCount + 1)
                                                    @php
                                                        $trailingSpan = max(1, $maxSlots - $daySlotCount);
                                                    @endphp
                                                    <td rowspan="{{ $trailingSpan }}"
                                                        style="height: {{ $trailingSpan * 64 }}px; padding: 3px 4px; border-left: 1px solid var(--border-color); border-bottom: 1px solid var(--border-color); vertical-align: middle; text-align: center; background: rgba(0,0,0,0.02); box-sizing: border-box;">
                                                        <div
                                                            style="height: calc(100% - 2px); min-height: {{ max(54, $trailingSpan * 64 - 8) }}px; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 4px; color: var(--text-muted); font-size: 0.74rem; background: rgba(0,0,0,0.04); border-radius: 7px; border: 1px dashed var(--border-color); padding: 6px; box-sizing: border-box;">
                                                            <i class="fas fa-door-open"
                                                                style="font-size: 0.95rem; opacity: 0.5;"></i>
                                                            <strong
                                                                style="font-size: 0.75rem; color: var(--text-muted);">KBM
                                                                Selesai ({{ $daySlotCount }} JP)</strong>
                                                            <span
                                                                style="font-size: 0.67rem; opacity: 0.8;">{{ $h === 'Jumat' ? 'Sholat Jumat & Kepulangan' : 'Di Luar Jam Belajar' }}</span>
                                                        </div>
                                                    </td>
                                                @endif
                                                {{-- Jika $jamKe > $daySlotCount + 1, lewati karena sudah di-cover oleh rowspan di atas --}}
                                            @elseif ($isOccupied)
                                                {{-- Slot ini terisi oleh pelajaran multi-JP dari jam sebelumnya, lewati perenderan terpisah --}}
                                            @elseif ($schedule)
                                                @php
                                                    $jpSpan = max(
                                                        1,
                                                        (int) $schedule->jam_ke_selesai -
                                                            (int) $schedule->jam_ke_mulai +
                                                            1,
                                                    );
                                                    $isUpacara = $schedule->mata_pelajaran_id === 'UPACARA';
                                                    $isPembiasaan = $schedule->mata_pelajaran_id === 'PEMBIASAAN';
                                                    $isIstirahat = $schedule->mata_pelajaran_id === 'ISTIRAHAT';
                                                    $isRoutine =
                                                        $isUpacara ||
                                                        $isPembiasaan ||
                                                        $isIstirahat ||
                                                        str_starts_with($schedule->pembelajaran_id ?? '', 'ROUTINE_') ||
                                                        in_array(
                                                            strtolower(trim($schedule->nama_mata_pelajaran)),
                                                            ['upacara', 'upacara bendera', 'istirahat', 'pembiasaan'],
                                                            true,
                                                        );
                                                    $borderCol = $isUpacara
                                                        ? '#ef4444'
                                                        : ($isPembiasaan
                                                            ? '#10b981'
                                                            : ($isIstirahat
                                                                ? '#f59e0b'
                                                                : 'var(--primary)'));
                                                    $bgCard = $isUpacara
                                                        ? 'rgba(239,68,68,0.04)'
                                                        : ($isPembiasaan
                                                            ? 'rgba(16,185,129,0.04)'
                                                            : ($isIstirahat
                                                                ? 'rgba(245,158,11,0.04)'
                                                                : 'var(--bg-card)'));
                                                @endphp
                                                <td rowspan="{{ $jpSpan }}"
                                                    style="height: {{ $jpSpan * 64 }}px; padding: 3px 4px; border-left: 1px solid var(--border-color); border-bottom: 1px solid var(--border-color); vertical-align: top; box-sizing: border-box; background: {{ $isRoutine ? 'rgba(100,116,139,0.02)' : 'rgba(99,102,241,0.03)' }};">
                                                    <div class="slot-card-active"
                                                        style="height: calc(100% - 2px); min-height: {{ max(54, $jpSpan * 64 - 8) }}px; padding: 7px 9px; border-radius: 7px; border: 1px solid {{ $isRoutine ? $borderCol : 'rgba(99,102,241,0.35)' }}; border-left: 4px solid {{ $borderCol }}; background: {{ $bgCard }}; box-shadow: 0 1px 3px rgba(0,0,0,0.08); box-sizing: border-box; display: flex; flex-direction: column; justify-content: space-between;">
                                                        <div>
                                                            <div
                                                                style="display: flex; justify-content: space-between; align-items: flex-start; gap: 4px; margin-bottom: 2px;">
                                                                <strong
                                                                    style="font-size: 0.81rem; color: var(--text-color); line-height: 1.25; display: -webkit-box; -webkit-line-clamp: {{ $jpSpan > 1 ? '3' : '1' }}; -webkit-box-orient: vertical; overflow: hidden;"
                                                                    title="{{ $schedule->nama_mata_pelajaran }}">
                                                                    @if ($isUpacara)
                                                                        <i class="fas fa-flag text-danger me-1"></i>
                                                                    @elseif ($isPembiasaan)
                                                                        <i
                                                                            class="fas fa-hands-praying text-success me-1"></i>
                                                                    @elseif ($isIstirahat)
                                                                        <i class="fas fa-mug-hot text-warning me-1"></i>
                                                                    @endif
                                                                    {{ $schedule->nama_mata_pelajaran }}
                                                                </strong>
                                                                <span class="badge"
                                                                    style="background: {{ $isRoutine ? 'rgba(100,116,139,0.18)' : 'rgba(99,102,241,0.15)' }}; color: {{ $isRoutine ? 'var(--text-muted)' : 'var(--primary)' }}; font-size: 0.65rem; padding: 1px 5px; border-radius: 4px; flex-shrink: 0; font-weight: 700;">
                                                                    {{ $jpSpan }} JP
                                                                </span>
                                                            </div>

                                                            @if (!$isRoutine)
                                                                <div
                                                                    style="font-size: 0.72rem; color: var(--text-muted); margin-bottom: 2px; display: flex; align-items: center; gap: 4px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                                                    <i class="fas fa-user-tie text-primary"
                                                                        style="font-size: 0.68rem; flex-shrink: 0;"></i>
                                                                    <span
                                                                        title="{{ $schedule->nama_guru }}">{{ $schedule->nama_guru ?: 'Guru belum ada' }}</span>
                                                                </div>
                                                            @endif

                                                            @if ($schedule->ruangan)
                                                                <div
                                                                    style="font-size: 0.7rem; color: #10b981; display: flex; align-items: center; gap: 4px;">
                                                                    <i class="fas fa-location-dot"
                                                                        style="font-size: 0.65rem;"></i>
                                                                    <span>{{ $schedule->ruangan }}</span>
                                                                </div>
                                                            @endif

                                                            @if ($routine && !$isRoutine)
                                                                <div
                                                                    style="margin-top: 2px; font-size: 0.65rem; color: #ef4444; background: rgba(239,68,68,0.1); border-radius: 4px; padding: 1px 4px; display: inline-flex; align-items: center; gap: 3px;">
                                                                    <i class="fas fa-triangle-exclamation"></i>
                                                                    <span>Bentrok {{ $routine->nama }}</span>
                                                                </div>
                                                            @endif
                                                        </div>

                                                        <!-- Tombol Aksi Slot Padat & Rapi -->
                                                        <div
                                                            style="display: flex; justify-content: {{ $isRoutine ? 'space-between' : 'flex-end' }}; align-items: center; gap: 6px; margin-top: 4px; padding-top: 4px; border-top: 1px dashed var(--border-color);">
                                                            @if ($isRoutine)
                                                                <span
                                                                    style="font-size: 0.65rem; color: var(--text-muted); display: inline-flex; align-items: center; gap: 4px;"
                                                                    title="Kegiatan rutin disinkronkan otomatis dari Pengaturan KBM">
                                                                    <i class="fas fa-lock" style="font-size: 0.6rem;"></i>
                                                                    Pengaturan KBM
                                                                </span>
                                                            @else
                                                                <button type="button"
                                                                    class="btn-slot-action btn-edit-slot"
                                                                    data-id="{{ $schedule->id }}"
                                                                    data-pembelajaran-id="{{ $schedule->pembelajaran_id }}"
                                                                    data-mapel-id="{{ $schedule->mata_pelajaran_id }}"
                                                                    data-nama-mapel="{{ $schedule->nama_mata_pelajaran }}"
                                                                    data-hari="{{ $schedule->hari }}"
                                                                    data-jam-mulai="{{ $schedule->jam_ke_mulai }}"
                                                                    data-jam-selesai="{{ $schedule->jam_ke_selesai }}"
                                                                    data-ruangan="{{ $schedule->ruangan }}"
                                                                    data-keterangan="{{ $schedule->keterangan }}"
                                                                    title="Edit Slot Ini" aria-label="Edit Slot Ini"
                                                                    style="width: 26px; height: 26px; min-width: 26px; min-height: 26px; font-size: 0.72rem; padding: 0;">
                                                                    <i class="fas fa-pen"></i>
                                                                </button>
                                                                <button type="button"
                                                                    class="btn-slot-action btn-slot-delete btn-hapus-slot"
                                                                    data-id="{{ $schedule->id }}"
                                                                    data-nama-mapel="{{ $schedule->nama_mata_pelajaran }}"
                                                                    data-hari="{{ $schedule->hari }}"
                                                                    data-jam="Jam ke-{{ $schedule->jam_ke_mulai }} s/d {{ $schedule->jam_ke_selesai }}"
                                                                    title="Hapus Slot Ini" aria-label="Hapus Slot Ini"
                                                                    style="width: 26px; height: 26px; min-width: 26px; min-height: 26px; font-size: 0.72rem; padding: 0;">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </td>
                                            @elseif ($routine)
                                                @php
                                                    $rType = $routine->tipe ?? '';
                                                    $rColor =
                                                        $rType === 'UPACARA'
                                                            ? '#ef4444'
                                                            : ($rType === 'PEMBIASAAN'
                                                                ? '#10b981'
                                                                : '#f59e0b');
                                                    $rBg =
                                                        $rType === 'UPACARA'
                                                            ? 'rgba(239,68,68,0.04)'
                                                            : ($rType === 'PEMBIASAAN'
                                                                ? 'rgba(16,185,129,0.04)'
                                                                : 'rgba(245,158,11,0.04)');
                                                    $rBadgeBg =
                                                        $rType === 'UPACARA'
                                                            ? 'rgba(239,68,68,0.12)'
                                                            : ($rType === 'PEMBIASAAN'
                                                                ? 'rgba(16,185,129,0.12)'
                                                                : 'rgba(245,158,11,0.12)');
                                                @endphp
                                                <td
                                                    style="height: 64px; padding: 3px 4px; border-left: 1px solid var(--border-color); border-bottom: 1px solid var(--border-color); vertical-align: middle; text-align: center; box-sizing: border-box; background: {{ $rBg }};">
                                                    <div
                                                        style="height: calc(100% - 2px); min-height: 54px; padding: 6px 8px; border-radius: 7px; border: 1px solid {{ $rColor }}; border-left: 4px solid {{ $rColor }}; background: {{ $rBadgeBg }}; color: {{ $rColor }}; box-sizing: border-box; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 2px;">
                                                        <div
                                                            style="font-weight: 700; font-size: 0.78rem; display: flex; align-items: center; gap: 4px;">
                                                            <i class="fas {{ $routine->icon }}"></i>
                                                            <span>{{ $routine->nama }}</span>
                                                        </div>
                                                        <div style="font-size: 0.67rem; opacity: 0.85;">
                                                            {{ $routine->durasi }}</div>
                                                    </div>
                                                </td>
                                            @else
                                                <!-- Slot Tersedia: Padat & Siap Diisi (Bukan Kotak Kosong Bolong) -->
                                                <td
                                                    style="height: 64px; padding: 3px 4px; border-left: 1px solid var(--border-color); border-bottom: 1px solid var(--border-color); vertical-align: middle; box-sizing: border-box;">
                                                    <button type="button" class="btn-empty-slot"
                                                        data-hari="{{ $h }}"
                                                        data-jam-ke="{{ $jamKe }}"
                                                        title="Klik untuk mengisi mata pelajaran pada hari {{ $h }} Jam Ke-{{ $jamKe }}"
                                                        style="width: 100%; height: calc(100% - 2px); min-height: 54px; background: rgba(99,102,241,0.02); border: 1px dashed rgba(99,102,241,0.22); border-radius: 7px; color: var(--text-muted); font-size: 0.72rem; cursor: pointer; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 3px; box-sizing: border-box; transition: all 0.15s ease;">
                                                        <i class="fas fa-plus text-primary"
                                                            style="font-size: 0.75rem; opacity: 0.75;"></i>
                                                        <span
                                                            style="font-size: 0.68rem; font-weight: 600; color: var(--text-muted);">+
                                                            Isi JP {{ $jamKe }}</span>
                                                    </button>
                                                </td>
                                            @endif
                                        @endforeach
                                    </tr>
                                @endfor
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @include('dashboard.jadwal-kbm.partials.modal-slot-manual')
@endsection

@push('scripts')
    <script
        src="{{ asset('js/jadwal-kbm-manual.js') }}?v={{ file_exists(public_path('js/jadwal-kbm-manual.js')) ? filemtime(public_path('js/jadwal-kbm-manual.js')) : time() }}">
    </script>
@endpush
