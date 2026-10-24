@extends('layouts.dashboard')

@section('title', 'Master Data — Kalender Pendidikan — SAE')
@section('dash_title', 'Kalender Pendidikan')

@push('styles')
    <style>
        .periode-nav-wrapper .periode-nav-scope,
        .periode-nav-wrapper .scope-subnav-bulan,
        .periode-nav-wrapper .scope-subnav-semester,
        .periode-nav-wrapper .view-switcher-group {
            background: var(--bg-card);
            padding: 3px;
            border-radius: 10px;
            border: 1px solid var(--border-color);
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .periode-nav-wrapper .periode-nav-tab {
            background: transparent !important;
            border: none !important;
            outline: none !important;
            cursor: pointer;
            padding: 6px 14px !important;
            border-radius: 7px !important;
            color: var(--text-muted) !important;
            font-size: 0.8rem !important;
            font-weight: 600 !important;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.15s ease;
            white-space: nowrap;
            line-height: 1.4;
        }

        .periode-nav-wrapper .periode-nav-tab:hover:not(.active):not(:disabled) {
            color: var(--text-color) !important;
            background: var(--bg-hover, rgba(255, 255, 255, 0.06)) !important;
        }

        .periode-nav-wrapper .periode-nav-tab.active {
            background: var(--primary) !important;
            color: #ffffff !important;
            font-weight: 700 !important;
            box-shadow: 0 2px 8px rgba(59, 130, 246, 0.35);
        }

        .periode-nav-wrapper .btn-nav-month {
            background: transparent !important;
            border: none !important;
            outline: none !important;
            color: var(--text-muted) !important;
            padding: 6px 10px !important;
            border-radius: 7px !important;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.15s ease;
            line-height: 1.4;
        }

        .periode-nav-wrapper .btn-nav-month:hover:not(:disabled) {
            color: var(--text-color) !important;
            background: var(--bg-hover, rgba(255, 255, 255, 0.06)) !important;
        }

        .periode-nav-wrapper .btn-nav-month:disabled {
            opacity: 0.3 !important;
            cursor: not-allowed !important;
        }

        @media (max-width: 768px) {
            .dash-banner {
                flex-direction: column !important;
                align-items: stretch !important;
                gap: 12px !important;
            }

            .dash-banner-actions {
                width: 100% !important;
            }

            .dash-banner-actions .btn {
                width: 100% !important;
                justify-content: center !important;
                display: inline-flex !important;
            }

            .periode-nav-wrapper .periode-nav-inner {
                flex-direction: column !important;
                align-items: stretch !important;
                gap: 10px !important;
            }

            .periode-nav-wrapper .periode-nav-scope {
                display: flex !important;
                width: 100% !important;
                justify-content: space-between !important;
            }

            .periode-nav-wrapper .periode-nav-scope .periode-nav-tab {
                flex: 1 !important;
                text-align: center !important;
                justify-content: center !important;
                padding: 7px 8px !important;
                font-size: 0.76rem !important;
            }

            .periode-nav-wrapper .scope-subnav-bulan,
            .periode-nav-wrapper .scope-subnav-semester {
                display: flex !important;
                width: 100% !important;
                justify-content: space-between !important;
                box-sizing: border-box !important;
            }

            .periode-nav-wrapper .scope-subnav-semester .periode-nav-tab {
                flex: 1 !important;
                text-align: center !important;
                justify-content: center !important;
            }

            .periode-nav-wrapper .view-switcher-group {
                display: flex !important;
                width: 100% !important;
            }

            .periode-nav-wrapper .view-switcher-group .periode-nav-tab {
                flex: 1 !important;
                text-align: center !important;
                justify-content: center !important;
                padding: 7px 10px !important;
                font-size: 0.78rem !important;
            }
        }
    </style>
@endpush

@section('content')
    <div class="dash-banner">
        <div>
            <h2 style="font-size: 1.35rem; font-weight: 800; color: var(--text-color); margin-bottom: 4px;">
                <i class="fas fa-calendar-days text-primary me-2"></i> Master Data — Kalender Pendidikan
            </h2>
            <p style="color: var(--text-muted); font-size: 0.85rem; margin-bottom: 6px;">
                Manajemen agenda akademik, hari libur, dan jadwal KBM (Luring, Daring, Libur) yang terintegrasi langsung
                dengan sistem presensi
                peserta didik, guru, serta tendik.
            </p>
            <a href="{{ route('dashboard.jadwal-kbm.index') }}" class="badge"
                style="background: rgba(99,102,241,0.12); color: var(--primary); text-decoration: none; border: 1px solid rgba(99,102,241,0.25); font-size: 0.76rem; padding: 4px 10px; border-radius: 6px; display: inline-flex; align-items: center; gap: 5px;"
                title="Atur Skema Hari Sekolah di Master Jadwal KBM">
                <i class="fas fa-sliders"></i> Skema:
                {{ ($skemaHari ?? '5_hari') === '6_hari' ? '6 Hari Sekolah (Senin - Sabtu)' : '5 Hari Sekolah (Senin - Jumat)' }}
            </a>
        </div>
        <div class="dash-banner-actions">
            @if ($canCreate)
                <button type="button" class="btn btn-primary" id="btnTambahAgenda"
                    onclick="if(typeof openAddModal === 'function') openAddModal();"
                    style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; font-size: 0.95rem;"
                    title="Tambah Agenda Baru">
                    <i class="fas fa-plus"></i>
                </button>
            @endif
        </div>
    </div>

    <!-- Summary Stats Grid Baku SAE (Mendukung Realtime refreshLiveTable) -->
    <div class="dash-stat-grid" style="grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); margin-bottom: 20px;">
        <!-- Card 1: Hari Efektif Belajar (Baru) -->
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(16,185,129,0.15); color: #10b981;">
                <i class="fas fa-calendar-check"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="font-size: 1.35rem; color: #10b981;">
                    {{ number_format($hariEfektif) }}
                </div>
                <div class="dash-stat-label">Hari Efektif Belajar</div>
                <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 2px;">
                    <i class="fas fa-clock-rotate-left me-1"></i> {{ number_format($hariEfektifTerlewati) }} hari terlewati
                </div>
            </div>
        </div>

        <!-- Card 2: Total Agenda Kegiatan -->
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(99,102,241,0.15); color: var(--primary);">
                <i class="fas fa-calendar-days"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="font-size: 1.35rem;">
                    {{ number_format($totalAgenda) }}
                </div>
                <div class="dash-stat-label">Total Agenda</div>
                <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 2px;">
                    {{ $periode['label'] ?? 'Periode Terpilih' }}
                </div>
            </div>
        </div>

        <!-- Card 3: Libur Peserta Didik -->
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(239,68,68,0.15); color: #ef4444;">
                <i class="fas fa-umbrella-beach"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="font-size: 1.35rem; color: #ef4444;">
                    {{ number_format($totalLiburPd) }}
                </div>
                <div class="dash-stat-label">Libur Peserta Didik</div>
                <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 2px;">
                    Bebas Presensi Siswa
                </div>
            </div>
        </div>

        <!-- Card 4: Agenda Daring (PJJ) -->
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(59,130,246,0.15); color: #3b82f6;">
                <i class="fas fa-laptop-house"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="font-size: 1.35rem; color: #3b82f6;">
                    {{ number_format($totalDaring ?? 0) }}
                </div>
                <div class="dash-stat-label">Agenda Daring (PJJ)</div>
                <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 2px;">
                    Belajar dari Rumah
                </div>
            </div>
        </div>

        <!-- Card 5: Libur Guru / Tendik -->
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(245,158,11,0.15); color: #f59e0b;">
                <i class="fas fa-id-badge"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="font-size: 1.35rem; color: #f59e0b;">
                    {{ number_format($totalLiburGtk) }}
                </div>
                <div class="dash-stat-label">Libur Guru / Tendik</div>
                <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 2px;">
                    Bebas Presensi GTK
                </div>
            </div>
        </div>

        <!-- Card 6: Agenda Asesmen / Ujian -->
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(168,85,247,0.15); color: #a855f7;">
                <i class="fas fa-file-signature"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="font-size: 1.35rem; color: #a855f7;">
                    {{ number_format($totalUjian) }}
                </div>
                <div class="dash-stat-label">Agenda Asesmen / Ujian</div>
                <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 2px;">
                    Evaluasi Akademik
                </div>
            </div>
        </div>
    </div>

    <!-- Navigasi Periode & View Switcher (Berada di Bawah Card Statistik) -->
    <div class="periode-nav-wrapper" style="margin-bottom: 20px;">
        <div class="periode-nav-inner"
            style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 12px; width: 100%;">
            <!-- Scope Switcher: Bulan, Semester, Tahun (Pills) -->
            <div class="periode-nav-scope">
                <button type="button" class="periode-nav-tab btn-scope-switch {{ $scope === 'bulan' ? 'active' : '' }}"
                    data-scope="bulan">
                    <i class="fas fa-calendar-day me-1"></i> Per Bulan
                </button>
                <button type="button" class="periode-nav-tab btn-scope-switch {{ $scope === 'semester' ? 'active' : '' }}"
                    data-scope="semester">
                    <i class="fas fa-layer-group me-1"></i> Per Semester
                </button>
                <button type="button" class="periode-nav-tab btn-scope-switch {{ $scope === 'tahun' ? 'active' : '' }}"
                    data-scope="tahun">
                    <i class="fas fa-calendar-alt me-1"></i> 1 Tahun Ajaran
                </button>
            </div>

            <!-- Sub Navigasi Dinamis untuk Bulan atau Semester -->
            @php
                $academicMonthsList = $calendarData['all_academic_months'] ?? [];
                $curMNum = !empty($bulan) ? (int) $bulan : (int) now()->month;
                $curMonthIndex = 0;
                foreach ($academicMonthsList as $idx => $am) {
                    if ($am['month'] === $curMNum) {
                        $curMonthIndex = $idx;
                        break;
                    }
                }
                $prevMonthStr = $curMonthIndex > 0 ? $academicMonthsList[$curMonthIndex - 1]['month_str'] : null;
                $nextMonthStr =
                    $curMonthIndex < count($academicMonthsList) - 1
                        ? $academicMonthsList[$curMonthIndex + 1]['month_str']
                        : null;
                $curMonthName = $academicMonthsList[$curMonthIndex]['name'] ?? 'Bulan';
                $curMonthYear = $academicMonthsList[$curMonthIndex]['year'] ?? now()->year;
            @endphp

            @if ($scope === 'bulan')
                <div class="scope-subnav-bulan">
                    <button type="button" class="btn-nav-month" data-month="{{ $prevMonthStr }}"
                        {{ !$prevMonthStr ? 'disabled' : '' }} title="Bulan Sebelumnya">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <span
                        style="font-size: 0.8rem; font-weight: 700; color: var(--text-color); min-width: 135px; text-align: center;">
                        <i class="fas fa-calendar-check text-primary me-1"></i> {{ $curMonthName }} {{ $curMonthYear }}
                    </span>
                    <button type="button" class="btn-nav-month" data-month="{{ $nextMonthStr }}"
                        {{ !$nextMonthStr ? 'disabled' : '' }} title="Bulan Berikutnya">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
            @elseif ($scope === 'semester')
                <div class="scope-subnav-semester">
                    <button type="button" class="periode-nav-tab btn-sem-switch {{ $semester === '1' ? 'active' : '' }}"
                        data-sem="1">
                        <i class="fas fa-1 me-1"></i> Semester 1 (Ganjil)
                    </button>
                    <button type="button" class="periode-nav-tab btn-sem-switch {{ $semester === '2' ? 'active' : '' }}"
                        data-sem="2">
                        <i class="fas fa-2 me-1"></i> Semester 2 (Genap)
                    </button>
                </div>
            @endif

            <!-- View Switcher: Kalender Visual vs Tabel Datatable -->
            <div class="view-switcher-group">
                <button type="button" id="btnViewKalender"
                    class="periode-nav-tab {{ $view === 'kalender' ? 'active' : '' }}">
                    <i class="fas fa-calendar-days"></i> <span>Tampilan Kalender</span>
                </button>
                <button type="button" id="btnViewTabel"
                    class="periode-nav-tab {{ $view === 'tabel' ? 'active' : '' }}">
                    <i class="fas fa-table-list"></i> <span>Tampilan Tabel</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Toolbar Filters Baku SAE -->
    <div class="toolbar-row" style="flex-wrap: wrap; gap: 10px; margin-bottom: 20px;">
        <div class="toolbar-entries">
            <label for="perPageSelect" style="margin: 0;">Tampilkan</label>
            <select id="perPageSelect" class="per-page-select">
                @foreach ([10, 15, 25, 50, 100] as $n)
                    <option value="{{ $n }}" {{ $perPage == $n ? 'selected' : '' }}>{{ $n }}
                    </option>
                @endforeach
            </select>
            <span>entri</span>
        </div>

        <div style="display: flex; flex-wrap: wrap; gap: 8px; flex: 1; min-width: 240px; align-items: center;">
            <!-- Filter Tahun Pelajaran -->
            <select id="filterTahunAjaran" class="toolbar-filter-select" style="min-width: 140px;"
                title="Pilih Tahun Pelajaran">
                @foreach ($taOptions as $opt)
                    <option value="{{ $opt }}" {{ $tahunAjaran === $opt ? 'selected' : '' }}>TP
                        {{ $opt }}</option>
                @endforeach
            </select>

            <!-- Filter Semester (2 Semester per Tahun Ajaran) -->
            <select id="filterSemester" class="toolbar-filter-select" style="min-width: 140px;" title="Pilih Semester">
                <option value="semua" {{ $semester === 'semua' ? 'selected' : '' }}>Semua Semester (1 TP)</option>
                <option value="1" {{ $semester === '1' ? 'selected' : '' }}>Semester 1 (Ganjil)</option>
                <option value="2" {{ $semester === '2' ? 'selected' : '' }}>Semester 2 (Genap)</option>
            </select>

            <!-- Filter Bulan Akademik (Juli s.d. Juni) -->
            <select id="filterBulan" class="toolbar-filter-select" style="min-width: 120px;" title="Pilih Bulan">
                <option value="">Semua Bulan</option>
                @foreach ([7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember', 1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni'] as $mNum => $mName)
                    @php $mStr = sprintf('%02d', $mNum); @endphp
                    <option value="{{ $mStr }}" {{ $bulan === $mStr ? 'selected' : '' }}>{{ $mName }}
                    </option>
                @endforeach
            </select>

            <!-- Filter Mode Presensi -->
            <select id="filterModePresensi" class="toolbar-filter-select" style="min-width: 135px;"
                title="Filter Mode Presensi">
                <option value="">Semua Mode</option>
                <option value="luring" {{ ($modePresensi ?? '') === 'luring' ? 'selected' : '' }}>Luring (Efektif)
                </option>
                <option value="daring" {{ ($modePresensi ?? '') === 'daring' ? 'selected' : '' }}>Daring (PJJ)</option>
                <option value="libur" {{ ($modePresensi ?? '') === 'libur' ? 'selected' : '' }}>Libur</option>
            </select>

            <!-- Filter Kategori Agenda -->
            <select id="filterTipe" class="toolbar-filter-select" style="min-width: 140px;" title="Filter Kategori">
                <option value="">Semua Kategori</option>
                <option value="hari_efektif" {{ $tipe === 'hari_efektif' ? 'selected' : '' }}>Hari Efektif</option>
                <option value="pembelajaran_daring" {{ $tipe === 'pembelajaran_daring' ? 'selected' : '' }}>Daring (PJJ)
                </option>
                <option value="kegiatan_sekolah" {{ $tipe === 'kegiatan_sekolah' ? 'selected' : '' }}>Kegiatan Sekolah
                </option>
                <option value="ujian_asesmen" {{ $tipe === 'ujian_asesmen' ? 'selected' : '' }}>Ujian &amp; Asesmen
                </option>
                <option value="libur_nasional" {{ $tipe === 'libur_nasional' ? 'selected' : '' }}>Libur Nasional</option>
                <option value="libur_semester" {{ $tipe === 'libur_semester' ? 'selected' : '' }}>Libur Semester</option>
                <option value="libur_khusus" {{ $tipe === 'libur_khusus' ? 'selected' : '' }}>Libur Khusus</option>
            </select>

            <!-- Filter Dampak Presensi -->
            <select id="filterDampak" class="toolbar-filter-select" style="min-width: 135px;"
                title="Filter Dampak Presensi">
                <option value="">Semua Dampak</option>
                <option value="libur_pd" {{ $dampak === 'libur_pd' ? 'selected' : '' }}>Libur Siswa</option>
                <option value="libur_guru" {{ $dampak === 'libur_guru' ? 'selected' : '' }}>Libur Guru</option>
                <option value="libur_tendik" {{ $dampak === 'libur_tendik' ? 'selected' : '' }}>Libur Tendik</option>
                <option value="libur_semua" {{ $dampak === 'libur_semua' ? 'selected' : '' }}>Libur Semua (PD &amp; GTK)
                </option>
            </select>

            @if ($q || $tipe || $modePresensi || $dampak || $bulan || $semester !== 'semua')
                <button type="button" id="btnResetFilter" class="btn btn-outline"
                    style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px;"
                    title="Reset filter">
                    <i class="fas fa-undo"></i>
                </button>
            @endif
        </div>

        <div class="live-search-wrap">
            <i class="fas fa-search search-icon"></i>
            <input type="text" id="liveSearch" placeholder="Cari agenda / keterangan..." value="{{ $q }}"
                autocomplete="off">
            <button type="button" id="clearSearch" class="clear-search {{ $q ? 'visible' : '' }}"
                title="Hapus pencarian">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>

    <!-- 1. TAMPILAN KALENDER VISUAL (Grid Per Bulan / Semester / Tahun) -->
    <div id="viewKalenderVisual" class="view-kalender-wrapper"
        style="{{ $view === 'kalender' ? '' : 'display: none;' }} margin-bottom: 24px;">
        <!-- Grid Kartu Bulan -->
        <div class="calendar-months-grid"
            style="display: grid; grid-template-columns: repeat(auto-fit, minmax({{ $scope === 'bulan' ? '320px' : '340px' }}, 1fr)); gap: 20px; align-items: start;">
            @foreach ($calendarData['months'] as $mItem)
                <div class="card cal-month-card"
                    style="padding: 16px 18px; border-radius: 14px; border: 1px solid var(--border-color); background: var(--bg-card); box-shadow: 0 4px 16px rgba(0,0,0,0.12); display: flex; flex-direction: column;">
                    <!-- Month Header -->
                    <div
                        style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; border-bottom: 1px solid var(--border-color); padding-bottom: 8px;">
                        <div>
                            <h4
                                style="font-size: 1rem; font-weight: 800; color: var(--text-color); margin: 0; display: flex; align-items: center; gap: 6px;">
                                <i class="fas fa-calendar text-primary" style="font-size: 0.9rem;"></i>
                                {{ $mItem['full_label'] }}
                            </h4>
                            <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 2px;">
                                Semester {{ $mItem['semester'] === '1' ? '1 (Ganjil)' : '2 (Genap)' }} &bull;
                                {{ $mItem['heb_count'] }} Hari Efektif Belajar
                            </div>
                        </div>
                        <span class="badge"
                            style="background: rgba(99,102,241,0.12); color: var(--primary); font-size: 0.72rem; font-weight: 700; padding: 3px 8px; border-radius: 6px;">
                            {{ $mItem['events_count'] }} Agenda
                        </span>
                    </div>

                    <!-- Mini Calendar Table Grid (Senin - Minggu) -->
                    <div class="cal-matrix-wrap" style="width: 100%; overflow-x: auto;">
                        <table class="cal-mini-table"
                            style="width: 100%; border-collapse: separate; border-spacing: 2px; text-align: center; margin-bottom: 10px;">
                            <thead>
                                <tr style="font-size: 0.7rem; font-weight: 800; color: var(--text-muted);">
                                    <th style="padding: 4px 2px; width: 14.28%;">Sen</th>
                                    <th style="padding: 4px 2px; width: 14.28%;">Sel</th>
                                    <th style="padding: 4px 2px; width: 14.28%;">Rab</th>
                                    <th style="padding: 4px 2px; width: 14.28%;">Kam</th>
                                    <th style="padding: 4px 2px; width: 14.28%;">Jum</th>
                                    <th
                                        style="padding: 4px 2px; width: 14.28%; color: {{ ($skemaHari ?? '5_hari') === '5_hari' ? '#ef4444' : '#3b82f6' }};">
                                        Sab</th>
                                    <th style="padding: 4px 2px; width: 14.28%; color: #ef4444;">Min</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($mItem['weeks'] as $wDays)
                                    <tr>
                                        @foreach ($wDays as $dInfo)
                                            @if (!$dInfo)
                                                <td style="padding: 3px; background: transparent;"></td>
                                            @else
                                                @php
                                                    $isSun = $dInfo['is_sunday'];
                                                    $isSat = $dInfo['is_saturday'];
                                                    $isWeekend =
                                                        $dInfo['is_weekend'] ??
                                                        $isSun || ($isSat && ($skemaHari ?? '5_hari') === '5_hari');
                                                    $isToday = $dInfo['is_today'];
                                                    $evCount = count($dInfo['events']);
                                                    $pEv = $dInfo['primary_event'];

                                                    $bgColor = 'rgba(255,255,255,0.02)';
                                                    $textColor = 'var(--text-color)';
                                                    $borderColor = 'var(--border-color)';

                                                    if ($isSun) {
                                                        $textColor = '#ef4444';
                                                    } elseif ($isSat) {
                                                        $textColor =
                                                            ($skemaHari ?? '5_hari') === '5_hari'
                                                                ? '#ef4444'
                                                                : '#3b82f6';
                                                    }

                                                    if ($isWeekend && !$pEv) {
                                                        $bgColor = 'rgba(239, 68, 68, 0.04)';
                                                        $borderColor = 'rgba(239, 68, 68, 0.15)';
                                                    }

                                                    if ($pEv) {
                                                        if ($pEv->libur_pd || $pEv->mode_presensi === 'libur') {
                                                            $bgColor = 'rgba(239, 68, 68, 0.2)';
                                                            $borderColor = '#ef4444';
                                                            $textColor = '#fca5a5';
                                                        } elseif ($pEv->tipe === 'ujian_asesmen') {
                                                            $bgColor = 'rgba(168, 85, 247, 0.2)';
                                                            $borderColor = '#a855f7';
                                                            $textColor = '#d8b4fe';
                                                        } elseif ($pEv->mode_presensi === 'daring') {
                                                            $bgColor = 'rgba(59, 130, 246, 0.2)';
                                                            $borderColor = '#3b82f6';
                                                            $textColor = '#93c5fd';
                                                        } else {
                                                            $bgColor = 'rgba(16, 185, 129, 0.18)';
                                                            $borderColor = '#10b981';
                                                            $textColor = '#86efac';
                                                        }
                                                    }

                                                    if ($isToday) {
                                                        $borderColor = 'var(--primary)';
                                                    }
                                                @endphp
                                                <td style="padding: 2px;">
                                                    <div class="cal-day-cell {{ $evCount > 0 ? 'has-event' : '' }}"
                                                        data-date="{{ $dInfo['date'] }}"
                                                        @if ($evCount > 0) title="{{ implode(' | ', array_column($dInfo['events'], 'nama_kegiatan')) }}" @endif
                                                        style="width: 100%; min-height: 28px; border-radius: 6px; background: {{ $bgColor }}; border: 1px solid {{ $borderColor }}; display: flex; flex-direction: column; align-items: center; justify-content: center; position: relative; cursor: {{ $evCount > 0 ? 'pointer' : 'default' }};">
                                                        <span
                                                            style="font-size: 0.76rem; font-weight: {{ $isToday || $evCount > 0 ? '800' : '600' }}; color: {{ $textColor }}; line-height: 1;">
                                                            {{ $dInfo['day'] }}
                                                        </span>
                                                        @if ($evCount > 0)
                                                            <span
                                                                style="display: inline-block; width: 4px; height: 4px; border-radius: 50%; background: {{ $pEv->warna ?? '#3b82f6' }}; margin-top: 2px;"></span>
                                                        @endif
                                                    </div>
                                                </td>
                                            @endif
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Detail Keterangan Kalender di Bawah Tanggal Bulan -->
                    <div class="cal-month-notes"
                        style="margin-top: auto; padding-top: 10px; border-top: 1px dashed var(--border-color); font-size: 0.74rem;">
                        <div
                            style="font-weight: 700; color: var(--text-color); margin-bottom: 6px; display: flex; align-items: center; justify-content: space-between;">
                            <span style="display: inline-flex; align-items: center; gap: 4px;">
                                <i class="fas fa-list-check text-primary" style="font-size: 0.7rem;"></i> Keterangan
                                Kegiatan:
                            </span>
                        </div>

                        @if ($mItem['events']->isEmpty())
                            <div style="font-size: 0.72rem; color: var(--text-muted); font-style: italic; padding: 4px 0;">
                                Tidak ada agenda khusus pada bulan ini (KBM Reguler).
                            </div>
                        @else
                            <div
                                style="display: flex; flex-direction: column; gap: 5px; max-height: 160px; overflow-y: auto; padding-right: 4px;">
                                @foreach ($mItem['events'] as $ag)
                                    @php
                                        $tglM = $ag->tanggal_mulai->format('d');
                                        $tglS = $ag->tanggal_selesai->format('d');
                                        $rentangTgl = $tglM === $tglS ? $tglM : "{$tglM}-{$tglS}";
                                        $agDotColor = $ag->warna ?: '#3b82f6';
                                    @endphp
                                    <div class="cal-note-item btn-view-agenda" data-item='@json($ag)'
                                        style="display: flex; align-items: flex-start; gap: 6px; padding: 4px 6px; border-radius: 6px; background: rgba(255,255,255,0.02); cursor: pointer; transition: background 0.15s ease;"
                                        title="Klik untuk melihat detail agenda {{ $ag->nama_kegiatan }}">
                                        <span class="badge"
                                            style="background: rgba(255,255,255,0.06); color: var(--text-color); font-size: 0.68rem; font-weight: 700; padding: 1px 5px; border-radius: 4px; flex-shrink: 0;">
                                            {{ $rentangTgl }}
                                        </span>
                                        <div style="flex: 1; min-width: 0; line-height: 1.3;">
                                            <span
                                                style="display: inline-block; width: 6px; height: 6px; border-radius: 50%; background: {{ $agDotColor }}; margin-right: 2px;"></span>
                                            <strong
                                                style="color: var(--text-color); font-size: 0.73rem;">{{ $ag->nama_kegiatan }}</strong>
                                            @if ($ag->keterangan)
                                                <div
                                                    style="color: var(--text-muted); font-size: 0.68rem; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;">
                                                    {{ $ag->keterangan }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- 2. TAMPILAN TABEL DATA (Data Table Container Baku SAE) -->
    <div id="viewTabelContainer" style="{{ $view === 'tabel' ? '' : 'display: none;' }}">
        <!-- Data Table Container Baku SAE (#tableDataContainer untuk refreshLiveTable) -->
        <div class="card datatable-card-wrapper" id="tableDataContainer">
            <div class="table-responsive-stack" style="overflow-x: auto; width: 100%;">
                <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                    <thead>
                        <tr style="background: rgba(255, 255, 255, 0.02); border-bottom: 1px solid var(--border-color);">
                            <th class="sortable-th {{ $sort === 'tanggal_mulai' ? 'sorted' : '' }}"
                                data-sort="tanggal_mulai"
                                style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Tanggal Pelaksanaan
                                <span class="sort-icon">{!! $sort === 'tanggal_mulai' ? ($sortDir === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                            </th>
                            <th class="sortable-th {{ $sort === 'nama_kegiatan' ? 'sorted' : '' }}"
                                data-sort="nama_kegiatan"
                                style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Nama Agenda &amp; Kategori
                                <span class="sort-icon">{!! $sort === 'nama_kegiatan' ? ($sortDir === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                            </th>
                            <th class="sortable-th {{ $sort === 'mode_presensi' ? 'sorted' : '' }}"
                                data-sort="mode_presensi"
                                style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Mode &amp; Dampak Presensi
                                <span class="sort-icon">{!! $sort === 'mode_presensi' ? ($sortDir === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                            </th>
                            <th class="sortable-th {{ $sort === 'created_by' ? 'sorted' : '' }}" data-sort="created_by"
                                style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: right;">
                                Petugas
                                <span class="sort-icon">{!! $sort === 'created_by' ? ($sortDir === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                            </th>
                            <th
                                style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: right; width: 110px;">
                                Aksi
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($list as $item)
                            @php
                                $isSingleDay =
                                    $item->tanggal_mulai->format('Y-m-d') === $item->tanggal_selesai->format('Y-m-d');
                                $daysDiff = $item->tanggal_mulai->diffInDays($item->tanggal_selesai) + 1;
                                $tipeColors = [
                                    'libur_nasional' => [
                                        'bg' => 'rgba(239,68,68,0.15)',
                                        'color' => '#ef4444',
                                        'border' => 'rgba(239,68,68,0.3)',
                                    ],
                                    'libur_semester' => [
                                        'bg' => 'rgba(245,158,11,0.15)',
                                        'color' => '#f59e0b',
                                        'border' => 'rgba(245,158,11,0.3)',
                                    ],
                                    'libur_khusus' => [
                                        'bg' => 'rgba(236,72,153,0.15)',
                                        'color' => '#ec4899',
                                        'border' => 'rgba(236,72,153,0.3)',
                                    ],
                                    'kegiatan_sekolah' => [
                                        'bg' => 'rgba(99,102,241,0.15)',
                                        'color' => '#6366f1',
                                        'border' => 'rgba(99,102,241,0.3)',
                                    ],
                                    'ujian_asesmen' => [
                                        'bg' => 'rgba(168,85,247,0.15)',
                                        'color' => '#a855f7',
                                        'border' => 'rgba(168,85,247,0.3)',
                                    ],
                                    'hari_efektif' => [
                                        'bg' => 'rgba(16,185,129,0.15)',
                                        'color' => '#10b981',
                                        'border' => 'rgba(16,185,129,0.3)',
                                    ],
                                    'pembelajaran_daring' => [
                                        'bg' => 'rgba(59,130,246,0.15)',
                                        'color' => '#3b82f6',
                                        'border' => 'rgba(59,130,246,0.3)',
                                    ],
                                ];
                                $styleBadge = $tipeColors[$item->tipe] ?? [
                                    'bg' => 'rgba(148,163,184,0.15)',
                                    'color' => '#94a3b8',
                                    'border' => 'rgba(148,163,184,0.3)',
                                ];
                            @endphp
                            <tr style="border-bottom: 1px solid var(--border-color); transition: background 0.15s ease;">
                                <td style="padding: 14px 18px; font-size: 0.85rem;" data-label="Tanggal">
                                    <div
                                        style="display: flex; flex-direction: column; align-items: flex-start; text-align: left; width: 100%;">
                                        <div
                                            style="font-weight: 700; color: var(--text-color); display: flex; align-items: center; gap: 6px;">
                                            <i class="fas fa-calendar-day text-primary"></i>
                                            <span>{{ $item->tanggal_mulai->format('d/m/Y') }}@if (!$isSingleDay)
                                                    s.d. {{ $item->tanggal_selesai->format('d/m/Y') }}
                                                @endif
                                            </span>
                                        </div>
                                        <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 3px;">
                                            <i class="fas fa-hourglass-half me-1"></i> {{ $daysDiff }} hari
                                            pelaksanaan
                                        </div>
                                    </div>
                                </td>

                                <td style="padding: 14px 18px;" data-label="Agenda">
                                    <div
                                        style="display: flex; flex-direction: column; align-items: flex-start; text-align: left; width: 100%; min-width: 0;">
                                        <div
                                            style="font-weight: 700; color: var(--text-color); font-size: 0.9rem; margin-bottom: 4px; display: flex; align-items: center; gap: 6px;">
                                            <span
                                                style="display: inline-block; width: 10px; height: 10px; border-radius: 50%; background-color: {{ $item->warna }}; flex-shrink: 0;"></span>
                                            <span>{{ $item->nama_kegiatan }}</span>
                                        </div>
                                        <div style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                                            <span class="badge"
                                                style="background: {{ $styleBadge['bg'] }}; color: {{ $styleBadge['color'] }}; border: 1px solid {{ $styleBadge['border'] }}; font-size: 0.68rem; padding: 2px 7px; border-radius: 6px; white-space: nowrap;">
                                                {{ $item->tipe_label }}
                                            </span>
                                            @if ($item->keterangan)
                                                <span
                                                    style="font-size: 0.74rem; color: var(--text-muted); max-width: 250px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"
                                                    title="{{ $item->keterangan }}">
                                                    {{ $item->keterangan }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </td>

                                <td style="padding: 14px 18px;" data-label="Presensi">
                                    <div style="display: flex; flex-direction: column; gap: 6px; font-size: 0.75rem;">
                                        <div>
                                            {!! $item->mode_presensi_badge !!}
                                        </div>
                                        <div>
                                            <span
                                                style="font-weight: 600; color: var(--text-muted); display: inline-block; min-width: 85px;">Peserta
                                                Didik:</span>
                                            @if ($item->libur_pd || $item->mode_presensi === 'libur')
                                                <span class="badge"
                                                    style="background: rgba(239,68,68,0.15); color: #ef4444; border: 1px solid rgba(239,68,68,0.3); padding: 1px 6px; font-size: 0.68rem; border-radius: 4px;">
                                                    <i class="fas fa-ban me-1"></i> Libur
                                                </span>
                                            @else
                                                <span class="badge"
                                                    style="background: rgba(16,185,129,0.15); color: #10b981; border: 1px solid rgba(16,185,129,0.3); padding: 1px 6px; font-size: 0.68rem; border-radius: 4px;">
                                                    <i class="fas fa-check me-1"></i>
                                                    {{ $item->mode_presensi === 'daring' ? 'Daring' : 'Efektif' }}
                                                </span>
                                            @endif
                                        </div>
                                        <div>
                                            <span
                                                style="font-weight: 600; color: var(--text-muted); display: inline-block; width: 55px;">Guru:</span>
                                            @if ($item->libur_guru || $item->mode_presensi === 'libur')
                                                <span class="badge"
                                                    style="background: rgba(239,68,68,0.15); color: #ef4444; border: 1px solid rgba(239,68,68,0.3); padding: 1px 6px; font-size: 0.68rem; border-radius: 4px;">
                                                    <i class="fas fa-ban me-1"></i> Libur
                                                </span>
                                            @else
                                                <span class="badge"
                                                    style="background: rgba(16,185,129,0.15); color: #10b981; border: 1px solid rgba(16,185,129,0.3); padding: 1px 6px; font-size: 0.68rem; border-radius: 4px;">
                                                    <i class="fas fa-check me-1"></i> Efektif
                                                </span>
                                            @endif
                                        </div>
                                        <div>
                                            <span
                                                style="font-weight: 600; color: var(--text-muted); display: inline-block; width: 55px;">Tendik:</span>
                                            @if ($item->libur_tendik || $item->mode_presensi === 'libur')
                                                <span class="badge"
                                                    style="background: rgba(239,68,68,0.15); color: #ef4444; border: 1px solid rgba(239,68,68,0.3); padding: 1px 6px; font-size: 0.68rem; border-radius: 4px;">
                                                    <i class="fas fa-ban me-1"></i> Libur
                                                </span>
                                            @else
                                                <span class="badge"
                                                    style="background: rgba(16,185,129,0.15); color: #10b981; border: 1px solid rgba(16,185,129,0.3); padding: 1px 6px; font-size: 0.68rem; border-radius: 4px;">
                                                    <i class="fas fa-check me-1"></i> Efektif
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </td>

                                <td style="padding: 14px 18px; font-size: 0.8rem; color: var(--text-muted);"
                                    data-label="Petugas">
                                    <div
                                        style="display: flex; flex-direction: column; align-items: flex-end; text-align: right;">
                                        <div style="font-weight: 600; color: var(--text-color);">
                                            <i class="fas fa-user-pen me-1 text-primary"></i>
                                            {{ $item->created_by ?: 'Sistem' }}
                                        </div>
                                        <div style="font-size: 0.72rem; opacity: 0.85;">
                                            {{ $item->created_at ? $item->created_at->format('d/m/Y H:i') : '-' }}
                                        </div>
                                    </div>
                                </td>

                                <td style="padding: 14px 18px; text-align: right;" data-label="Aksi">
                                    <div class="table-actions"
                                        style="display: inline-flex; align-items: center; gap: 6px;">
                                        <button type="button" class="btn-icon btn-view-agenda"
                                            data-item='@json($item)' title="Lihat Detail Agenda">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        @if ($canUpdate)
                                            <button type="button" class="btn-icon btn-edit-agenda"
                                                data-item='@json($item)' title="Ubah Agenda">
                                                <i class="fas fa-pen-to-square"></i>
                                            </button>
                                        @endif
                                        @if ($canDelete)
                                            <form action="{{ route('dashboard.kalender-pendidikan.destroy', $item->id) }}"
                                                method="POST" data-confirm="delete"
                                                data-name="{{ $item->nama_kegiatan }}"
                                                style="margin: 0; display: inline;">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn-icon danger" title="Hapus Agenda">
                                                    <i class="fas fa-trash-can"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @empty
                                <tr>
                                    <td colspan="5"
                                        style="padding: 40px 20px; text-align: center; color: var(--text-muted); font-size: 0.86rem;">
                                        <div style="font-size: 2.2rem; opacity: 0.35; margin-bottom: 8px;">
                                            <i class="fas fa-calendar-xmark"></i>
                                        </div>
                                        <div style="font-weight: 700; color: var(--text-color); margin-bottom: 4px;">Belum Ada
                                            Agenda Kalender Pendidikan</div>
                                        <div style="font-size: 0.82rem;">Tidak ada agenda kalender pendidikan yang sesuai
                                            filter
                                            pada periode {{ $periode['label'] ?? '' }}.</div>
                                        @if ($canCreate)
                                            <div style="margin-top: 14px;">
                                                <button type="button" class="btn btn-primary" id="btnEmptyTambahAgenda"
                                                    style="font-size: 0.82rem; padding: 7px 16px;">
                                                    <i class="fas fa-plus me-1"></i> Tambah Agenda Baru
                                                </button>
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Footer Paginasi Terpadu Datatable -->
                @if ($list->hasPages())
                    <div class="datatable-footer">
                        @include('partials.datatable-pagination', ['paginator' => $list])
                    </div>
                @endif
            </div>
            <!-- End Tampilan Tabel -->

            @include('dashboard.kalender-pendidikan.partials.modal-kalender')
                            style="padding: 7px 18px; font-size: 0.85rem;">Tutup</button>
                    </div>
                </div>
            </div>
        @endsection

        @push('scripts')
            <script
                src="{{ asset('js/kalender-pendidikan.js') }}?v={{ file_exists(public_path('js/kalender-pendidikan.js')) ? filemtime(public_path('js/kalender-pendidikan.js')) : time() }}">
            </script>
        @endpush
