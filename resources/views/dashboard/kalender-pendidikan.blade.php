@extends('layouts.dashboard')

@section('title', 'Master Data — Kalender Pendidikan — SAE')
@section('dash_title', 'Kalender Pendidikan')

@section('content')
    <div class="dash-banner">
        <div>
            <h2 style="font-size: 1.35rem; font-weight: 800; color: var(--text-color); margin-bottom: 4px;">
                <i class="fas fa-calendar-days text-primary me-2"></i> Master Data — Kalender Pendidikan
            </h2>
            <p style="color: var(--text-muted); font-size: 0.85rem;">
                Manajemen agenda akademik, hari libur, dan jadwal KBM (Luring, Daring, Libur) yang terintegrasi langsung dengan sistem presensi
                peserta didik, guru, serta tendik.
            </p>
        </div>
        <div class="dash-banner-actions">
            @if ($canCreate)
                <button type="button" class="btn btn-primary btn-responsive-icon" id="btnTambahAgenda" onclick="if(typeof openAddModal === 'function') openAddModal();"
                    style="padding: 9px 18px; font-size: 0.85rem; font-weight: 600;"
                    title="Tambah Agenda">
                    <i class="fas fa-plus"></i> <span class="btn-responsive-text">Tambah Agenda</span>
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

    <!-- Toolbar Filters Baku SAE -->
    <div class="toolbar-row" style="flex-wrap: wrap; gap: 10px; margin-bottom: 20px;">
        <div class="toolbar-entries">
            <label for="perPageSelect" style="margin: 0;">Tampilkan</label>
            <select id="perPageSelect" class="per-page-select">
                @foreach ([10, 15, 25, 50, 100] as $n)
                    <option value="{{ $n }}" {{ $perPage == $n ? 'selected' : '' }}>{{ $n }}</option>
                @endforeach
            </select>
            <span>entri</span>
        </div>

        <div style="display: flex; flex-wrap: wrap; gap: 8px; flex: 1; min-width: 240px; align-items: center;">
            <!-- Filter Tahun Pelajaran -->
            <select id="filterTahunAjaran" class="toolbar-filter-select" style="min-width: 140px;" title="Pilih Tahun Pelajaran">
                @foreach ($taOptions as $opt)
                    <option value="{{ $opt }}" {{ $tahunAjaran === $opt ? 'selected' : '' }}>TP {{ $opt }}</option>
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
                    <option value="{{ $mStr }}" {{ $bulan === $mStr ? 'selected' : '' }}>{{ $mName }}</option>
                @endforeach
            </select>

            <!-- Filter Mode Presensi -->
            <select id="filterModePresensi" class="toolbar-filter-select" style="min-width: 135px;" title="Filter Mode Presensi">
                <option value="">Semua Mode</option>
                <option value="luring" {{ ($modePresensi ?? '') === 'luring' ? 'selected' : '' }}>Luring (Efektif)</option>
                <option value="daring" {{ ($modePresensi ?? '') === 'daring' ? 'selected' : '' }}>Daring (PJJ)</option>
                <option value="libur" {{ ($modePresensi ?? '') === 'libur' ? 'selected' : '' }}>Libur</option>
            </select>

            <!-- Filter Kategori Agenda -->
            <select id="filterTipe" class="toolbar-filter-select" style="min-width: 140px;" title="Filter Kategori">
                <option value="">Semua Kategori</option>
                <option value="hari_efektif" {{ $tipe === 'hari_efektif' ? 'selected' : '' }}>Hari Efektif</option>
                <option value="pembelajaran_daring" {{ $tipe === 'pembelajaran_daring' ? 'selected' : '' }}>Daring (PJJ)</option>
                <option value="kegiatan_sekolah" {{ $tipe === 'kegiatan_sekolah' ? 'selected' : '' }}>Kegiatan Sekolah</option>
                <option value="ujian_asesmen" {{ $tipe === 'ujian_asesmen' ? 'selected' : '' }}>Ujian &amp; Asesmen</option>
                <option value="libur_nasional" {{ $tipe === 'libur_nasional' ? 'selected' : '' }}>Libur Nasional</option>
                <option value="libur_semester" {{ $tipe === 'libur_semester' ? 'selected' : '' }}>Libur Semester</option>
                <option value="libur_khusus" {{ $tipe === 'libur_khusus' ? 'selected' : '' }}>Libur Khusus</option>
            </select>

            <!-- Filter Dampak Presensi -->
            <select id="filterDampak" class="toolbar-filter-select" style="min-width: 135px;" title="Filter Dampak Presensi">
                <option value="">Semua Dampak</option>
                <option value="libur_pd" {{ $dampak === 'libur_pd' ? 'selected' : '' }}>Libur Siswa</option>
                <option value="libur_guru" {{ $dampak === 'libur_guru' ? 'selected' : '' }}>Libur Guru</option>
                <option value="libur_tendik" {{ $dampak === 'libur_tendik' ? 'selected' : '' }}>Libur Tendik</option>
                <option value="libur_semua" {{ $dampak === 'libur_semua' ? 'selected' : '' }}>Libur Semua (PD &amp; GTK)</option>
            </select>

            @if ($q || $tipe || $modePresensi || $dampak || $bulan || $semester !== 'semua')
                <button type="button" id="btnResetFilter" class="btn btn-outline" style="padding: 7px 12px; font-size: 0.8rem;" title="Reset filter">
                    <i class="fas fa-undo me-1"></i> Reset
                </button>
            @endif
        </div>

        <div class="live-search-wrap">
            <i class="fas fa-search search-icon"></i>
            <input type="text" id="liveSearch" placeholder="Cari agenda / keterangan..." value="{{ $q }}"
                autocomplete="off">
            <button type="button" id="clearSearch" class="clear-search {{ $q ? 'visible' : '' }}" title="Hapus pencarian">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>

    <!-- Data Table Container Baku SAE (#tableDataContainer untuk refreshLiveTable) -->
    <div class="card table-responsive-stack" id="tableDataContainer" style="padding: 0; margin-bottom: 24px;">
        <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
            <thead>
                <tr style="background: rgba(255, 255, 255, 0.02); border-bottom: 1px solid var(--border-color);">
                    <th class="sortable-th {{ $sort === 'tanggal_mulai' ? 'sorted' : '' }}" data-sort="tanggal_mulai"
                        style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                        Tanggal Pelaksanaan
                        <span class="sort-icon">{!! $sort === 'tanggal_mulai' ? ($sortDir === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                    </th>
                    <th class="sortable-th {{ $sort === 'nama_kegiatan' ? 'sorted' : '' }}" data-sort="nama_kegiatan"
                        style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                        Nama Agenda &amp; Kategori
                        <span class="sort-icon">{!! $sort === 'nama_kegiatan' ? ($sortDir === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                    </th>
                    <th class="sortable-th {{ $sort === 'mode_presensi' ? 'sorted' : '' }}" data-sort="mode_presensi"
                        style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                        Mode &amp; Dampak Presensi
                        <span class="sort-icon">{!! $sort === 'mode_presensi' ? ($sortDir === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                    </th>
                    <th class="sortable-th {{ $sort === 'created_by' ? 'sorted' : '' }}" data-sort="created_by"
                        style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: right;">
                        Petugas
                        <span class="sort-icon">{!! $sort === 'created_by' ? ($sortDir === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                    </th>
                    <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: right; width: 110px;">
                        Aksi
                    </th>
                </tr>
            </thead>
            <tbody>
                @forelse ($list as $item)
                    @php
                        $isSingleDay = $item->tanggal_mulai->format('Y-m-d') === $item->tanggal_selesai->format('Y-m-d');
                        $daysDiff = $item->tanggal_mulai->diffInDays($item->tanggal_selesai) + 1;
                        $tipeColors = [
                            'libur_nasional' => ['bg' => 'rgba(239,68,68,0.15)', 'color' => '#ef4444', 'border' => 'rgba(239,68,68,0.3)'],
                            'libur_semester' => ['bg' => 'rgba(245,158,11,0.15)', 'color' => '#f59e0b', 'border' => 'rgba(245,158,11,0.3)'],
                            'libur_khusus' => ['bg' => 'rgba(236,72,153,0.15)', 'color' => '#ec4899', 'border' => 'rgba(236,72,153,0.3)'],
                            'kegiatan_sekolah' => ['bg' => 'rgba(99,102,241,0.15)', 'color' => '#6366f1', 'border' => 'rgba(99,102,241,0.3)'],
                            'ujian_asesmen' => ['bg' => 'rgba(168,85,247,0.15)', 'color' => '#a855f7', 'border' => 'rgba(168,85,247,0.3)'],
                            'hari_efektif' => ['bg' => 'rgba(16,185,129,0.15)', 'color' => '#10b981', 'border' => 'rgba(16,185,129,0.3)'],
                            'pembelajaran_daring' => ['bg' => 'rgba(59,130,246,0.15)', 'color' => '#3b82f6', 'border' => 'rgba(59,130,246,0.3)'],
                        ];
                        $styleBadge = $tipeColors[$item->tipe] ?? ['bg' => 'rgba(148,163,184,0.15)', 'color' => '#94a3b8', 'border' => 'rgba(148,163,184,0.3)'];
                    @endphp
                    <tr style="border-bottom: 1px solid var(--border-color); transition: background 0.15s ease;">
                        <td style="padding: 14px 18px; font-size: 0.85rem;" data-label="Tanggal">
                            <div style="display: flex; flex-direction: column; align-items: flex-start; text-align: left; width: 100%;">
                                <div style="font-weight: 700; color: var(--text-color); display: flex; align-items: center; gap: 6px;">
                                    <i class="fas fa-calendar-day text-primary"></i>
                                    <span>{{ $item->tanggal_mulai->format('d/m/Y') }}@if (!$isSingleDay) s.d. {{ $item->tanggal_selesai->format('d/m/Y') }}@endif</span>
                                </div>
                                <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 3px;">
                                    <i class="fas fa-hourglass-half me-1"></i> {{ $daysDiff }} hari pelaksanaan
                                </div>
                            </div>
                        </td>

                        <td style="padding: 14px 18px;" data-label="Agenda">
                            <div style="display: flex; flex-direction: column; align-items: flex-start; text-align: left; width: 100%; min-width: 0;">
                                <div style="font-weight: 700; color: var(--text-color); font-size: 0.9rem; margin-bottom: 4px; display: flex; align-items: center; gap: 6px;">
                                    <span style="display: inline-block; width: 10px; height: 10px; border-radius: 50%; background-color: {{ $item->warna }}; flex-shrink: 0;"></span>
                                    <span>{{ $item->nama_kegiatan }}</span>
                                </div>
                                <div style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                                    <span class="badge"
                                        style="background: {{ $styleBadge['bg'] }}; color: {{ $styleBadge['color'] }}; border: 1px solid {{ $styleBadge['border'] }}; font-size: 0.68rem; padding: 2px 7px; border-radius: 6px; white-space: nowrap;">
                                        {{ $item->tipe_label }}
                                    </span>
                                    @if ($item->keterangan)
                                        <span style="font-size: 0.74rem; color: var(--text-muted); max-width: 250px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"
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
                                    <span style="font-weight: 600; color: var(--text-muted); display: inline-block; min-width: 85px;">Peserta Didik:</span>
                                    @if ($item->libur_pd || $item->mode_presensi === 'libur')
                                        <span class="badge"
                                            style="background: rgba(239,68,68,0.15); color: #ef4444; border: 1px solid rgba(239,68,68,0.3); padding: 1px 6px; font-size: 0.68rem; border-radius: 4px;">
                                            <i class="fas fa-ban me-1"></i> Libur
                                        </span>
                                    @else
                                        <span class="badge"
                                            style="background: rgba(16,185,129,0.15); color: #10b981; border: 1px solid rgba(16,185,129,0.3); padding: 1px 6px; font-size: 0.68rem; border-radius: 4px;">
                                            <i class="fas fa-check me-1"></i> {{ $item->mode_presensi === 'daring' ? 'Daring' : 'Efektif' }}
                                        </span>
                                    @endif
                                </div>
                                <div>
                                    <span style="font-weight: 600; color: var(--text-muted); display: inline-block; width: 55px;">Guru:</span>
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
                                    <span style="font-weight: 600; color: var(--text-muted); display: inline-block; width: 55px;">Tendik:</span>
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

                        <td style="padding: 14px 18px; font-size: 0.8rem; color: var(--text-muted);" data-label="Petugas">
                            <div style="display: flex; flex-direction: column; align-items: flex-end; text-align: right;">
                                <div style="font-weight: 600; color: var(--text-color);">
                                    <i class="fas fa-user-pen me-1 text-primary"></i> {{ $item->created_by ?: 'Sistem' }}
                                </div>
                                <div style="font-size: 0.72rem; opacity: 0.85;">
                                    {{ $item->created_at ? $item->created_at->format('d/m/Y H:i') : '-' }}
                                </div>
                            </div>
                        </td>

                        <td style="padding: 14px 18px; text-align: right;" data-label="Aksi">
                            <div class="table-actions" style="display: inline-flex; align-items: center; gap: 6px;">
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
                                        method="POST" data-confirm="delete" data-name="{{ $item->nama_kegiatan }}"
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
                            <div style="font-weight: 700; color: var(--text-color); margin-bottom: 4px;">Belum Ada Agenda Kalender Pendidikan</div>
                            <div style="font-size: 0.82rem;">Tidak ada agenda kalender pendidikan yang sesuai filter pada periode {{ $periode['label'] ?? '' }}.</div>
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

    <!-- Custom Pagination Baku SAE -->
    @if ($list->hasPages())
        <div class="custom-pagination" style="margin-bottom: 24px;">
            @if ($list->onFirstPage())
                <span class="page-btn disabled"><i class="fas fa-chevron-left"></i></span>
            @else
                <a href="{{ $list->previousPageUrl() }}" class="page-btn" title="Sebelumnya"><i class="fas fa-chevron-left"></i></a>
            @endif

            @php
                $cur = $list->currentPage();
                $last = $list->lastPage();
                $from = max(1, $cur - 2);
                $to = min($last, $cur + 2);
            @endphp

            @if ($from > 1)
                <a href="{{ $list->url(1) }}" class="page-btn">1</a>
                @if ($from > 2)
                    <span class="page-info">&hellip;</span>
                @endif
            @endif

            @for ($p = $from; $p <= $to; $p++)
                <a href="{{ $list->url($p) }}" class="page-btn {{ $p == $cur ? 'current' : '' }}">{{ $p }}</a>
            @endfor

            @if ($to < $last)
                @if ($to < $last - 1)
                    <span class="page-info">&hellip;</span>
                @endif
                <a href="{{ $list->url($last) }}" class="page-btn">{{ $last }}</a>
            @endif

            @if ($list->hasMorePages())
                <a href="{{ $list->nextPageUrl() }}" class="page-btn" title="Berikutnya"><i class="fas fa-chevron-right"></i></a>
            @else
                <span class="page-btn disabled"><i class="fas fa-chevron-right"></i></span>
            @endif
        </div>
    @endif

    <!-- Modal Form (Create / Edit) -->
    <div id="agendaModal" class="modal-backdrop" style="display: none; z-index: 99999 !important;">
        <div class="card"
            style="max-width: 580px; width: 92%; margin: auto; padding: 24px; border-radius: 14px; box-shadow: 0 16px 40px rgba(0,0,0,0.4); max-height: 90vh; overflow-y: auto;">
            <div
                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; border-bottom: 1px solid var(--border-color); padding-bottom: 12px;">
                <h3 id="modalTitle" style="font-size: 1.05rem; font-weight: 700; color: var(--text-color); margin: 0;">
                    <i class="fas fa-calendar-plus text-primary me-2"></i> Tambah Agenda Kalender
                </h3>
                <button type="button" id="btnCloseModal"
                    style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 1.1rem; padding: 4px;">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form id="agendaForm" action="{{ route('dashboard.kalender-pendidikan.store') }}" method="POST">
                @csrf
                <div id="methodField"></div>

                <div class="form-group-compact" style="margin-bottom: 14px;">
                    <label>Nama Agenda / Kegiatan <span style="color: #ef4444;">*</span></label>
                    <input type="text" name="nama_kegiatan" id="inputNamaKegiatan" required
                        placeholder="Contoh: Pembelajaran Jarak Jauh (PJJ) / Ujian Asesmen / Libur Nasional" maxlength="200">
                </div>

                <div class="form-group-compact" style="margin-bottom: 14px;">
                    <label>Kondisi / Mode Presensi <span style="color: #ef4444;">*</span></label>
                    <select name="mode_presensi" id="inputModePresensi" required style="width: 100%; padding: 10px 12px; font-size: 0.88rem;">
                        <option value="luring" selected>Luring (Presensi Efektif Sekolah — Scanner Terminal Aktif)</option>
                        <option value="daring">Daring (Pembelajaran Jarak Jauh / Belajar Rumah — Scanner Terminal Non-Aktif)</option>
                        <option value="libur">Libur (Hari Libur Sekolah — Bebas Presensi)</option>
                    </select>
                    <small style="color: var(--text-muted); font-size: 0.72rem; display: block; margin-top: 4px;">
                        Menentukan aturan sistem presensi pada tanggal pelaksanaan agenda ini.
                    </small>
                </div>

                <div class="modal-form-grid"
                    style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                    <div class="form-group-compact">
                        <label>Kategori Agenda <span style="color: #ef4444;">*</span></label>
                        <select name="tipe" id="inputTipe" required>
                            <option value="hari_efektif" selected>Hari Efektif Belajar</option>
                            <option value="pembelajaran_daring">Pembelajaran Daring (PJJ)</option>
                            <option value="kegiatan_sekolah">Kegiatan Sekolah / Upacara</option>
                            <option value="ujian_asesmen">Ujian &amp; Asesmen</option>
                            <option value="libur_nasional">Libur Nasional</option>
                            <option value="libur_semester">Libur Semester / Akhir Tahun</option>
                            <option value="libur_khusus">Libur Khusus Satpen</option>
                        </select>
                    </div>

                    <div class="form-group-compact">
                        <label>Warna Penanda</label>
                        <input type="color" name="warna" id="inputWarna" value="#06b6d4"
                            style="height: 40px; padding: 4px; border-radius: 8px; cursor: pointer; width: 100%;">
                    </div>
                </div>

                <div class="modal-form-grid"
                    style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                    <div class="form-group-compact">
                        <label>Tanggal Mulai <span style="color: #ef4444;">*</span></label>
                        <input type="date" name="tanggal_mulai" id="inputTanggalMulai" required>
                    </div>

                    <div class="form-group-compact">
                        <label>Tanggal Selesai <span style="color: #ef4444;">*</span></label>
                        <input type="date" name="tanggal_selesai" id="inputTanggalSelesai" required>
                    </div>
                </div>

                <!-- Dampak Terhadap Presensi -->
                <div
                    style="margin-bottom: 16px; padding: 12px; background: rgba(255,255,255,0.02); border: 1px solid var(--border-color); border-radius: 10px;">
                    <div style="font-size: 0.82rem; font-weight: 700; color: var(--text-color); margin-bottom: 6px;">
                        <i class="fas fa-fingerprint text-primary me-1"></i> Integrasi Dampak Libur Presensi
                    </div>
                    <p style="font-size: 0.74rem; color: var(--text-muted); margin-bottom: 10px;">
                        Centang peran yang diliburkan pada tanggal ini agar sistem presensi tidak mencatat ketidakhadiran
                        (alpha).
                    </p>

                    <div style="display: flex; flex-direction: column; gap: 8px;">
                        <label
                            style="display: flex; align-items: center; gap: 8px; font-size: 0.82rem; color: var(--text-color); cursor: pointer; margin: 0;">
                            <input type="checkbox" name="libur_pd" id="inputLiburPd" value="1"
                                style="width: 16px; height: 16px;">
                            <span>Liburkan <strong>Peserta Didik</strong> (Peserta Didik Bebas Presensi)</span>
                        </label>
                        <label
                            style="display: flex; align-items: center; gap: 8px; font-size: 0.82rem; color: var(--text-color); cursor: pointer; margin: 0;">
                            <input type="checkbox" name="libur_guru" id="inputLiburGuru" value="1"
                                style="width: 16px; height: 16px;">
                            <span>Liburkan <strong>Guru</strong> (Pendidik Bebas Presensi Mengajar)</span>
                        </label>
                        <label
                            style="display: flex; align-items: center; gap: 8px; font-size: 0.82rem; color: var(--text-color); cursor: pointer; margin: 0;">
                            <input type="checkbox" name="libur_tendik" id="inputLiburTendik" value="1"
                                style="width: 16px; height: 16px;">
                            <span>Liburkan <strong>Tendik</strong> (Tenaga Kependidikan Bebas Presensi)</span>
                        </label>
                    </div>
                </div>

                <div class="form-group-compact" style="margin-bottom: 20px;">
                    <label>Keterangan / Catatan Tambahan</label>
                    <textarea name="keterangan" id="inputKeterangan" rows="3"
                        placeholder="Deskripsi kegiatan, instruksi panitia, atau surat edaran terkait..."
                        style="width: 100%; padding: 10px 12px; font-size: 0.85rem; border-radius: 8px; border: 1px solid var(--border-color); background: var(--input-bg); color: var(--text-color); resize: vertical; box-sizing: border-box; line-height: 1.5;"></textarea>
                </div>

                <div
                    style="display: flex; justify-content: flex-end; gap: 10px; border-top: 1px solid var(--border-color); padding-top: 16px;">
                    <button type="button" id="btnCancelModal" class="btn btn-outline"
                        style="padding: 8px 16px; font-size: 0.85rem;">Batal</button>
                    <button type="submit" class="btn btn-primary"
                        style="padding: 8px 20px; font-size: 0.85rem; font-weight: 600;">Simpan Agenda</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal View Detail Agenda -->
    <div id="viewModal" class="modal-backdrop" style="display: none; z-index: 99999 !important;">
        <div class="card"
            style="max-width: 520px; width: 92%; margin: auto; padding: 24px; border-radius: 14px; box-shadow: 0 16px 40px rgba(0,0,0,0.4);">
            <div
                style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 14px; border-bottom: 1px solid var(--border-color); padding-bottom: 12px;">
                <div>
                    <div style="display: flex; gap: 6px; align-items: center; margin-bottom: 6px;">
                        <span id="viewTipeBadge" class="badge" style="font-size: 0.72rem;"></span>
                        <span id="viewModeBadge"></span>
                    </div>
                    <h3 id="viewJudul"
                        style="font-size: 1.15rem; font-weight: 800; color: var(--text-color); margin: 6px 0 0 0; line-height: 1.3;">
                    </h3>
                </div>
                <button type="button" id="btnCloseViewModal"
                    style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 1.2rem; padding: 4px;">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div style="display: flex; flex-direction: column; gap: 12px; margin-bottom: 16px; font-size: 0.85rem;">
                <div>
                    <span style="color: var(--text-muted); display: block; font-size: 0.75rem; font-weight: 600;">RENTANG
                        TANGGAL:</span>
                    <span id="viewTanggal" style="font-weight: 700; color: var(--text-color);"></span>
                </div>

                <div>
                    <span
                        style="color: var(--text-muted); display: block; font-size: 0.75rem; font-weight: 600; margin-bottom: 4px;">STATUS
                        PRESENSI:</span>
                    <div id="viewDampakBadges" style="display: flex; gap: 8px; flex-wrap: wrap;"></div>
                </div>

                <div>
                    <span
                        style="color: var(--text-muted); display: block; font-size: 0.75rem; font-weight: 600;">KETERANGAN:</span>
                    <p id="viewKeterangan" style="margin: 4px 0 0 0; color: var(--text-color); line-height: 1.5;"></p>
                </div>

                <div
                    style="font-size: 0.75rem; color: var(--text-muted); border-top: 1px solid var(--border-color); padding-top: 10px;">
                    Dibuat oleh <strong id="viewPembuat"></strong> pada <span id="viewWaktu"></span>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end;">
                <button type="button" id="btnTutupView" class="btn btn-outline"
                    style="padding: 7px 18px; font-size: 0.85rem;">Tutup</button>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/kalender-pendidikan.js') }}?v={{ file_exists(public_path('js/kalender-pendidikan.js')) ? filemtime(public_path('js/kalender-pendidikan.js')) : time() }}"></script>
@endpush
