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
                Manajemen agenda akademik, hari libur, dan jadwal asesmen yang terintegrasi langsung dengan sistem presensi
                peserta didik, guru, serta tendik.
            </p>
        </div>
        <div class="dash-banner-actions">
            @if ($canCreate)
                <button type="button" class="btn btn-primary btn-responsive-icon" onclick="openAddModal()"
                    style="padding: 9px 18px; font-size: 0.85rem; font-weight: 600;"
                    title="Tambah Agenda">
                    <i class="fas fa-plus"></i> <span class="btn-responsive-text">Tambah Agenda</span>
                </button>
            @endif
        </div>
    </div>

    <!-- Summary Stats Grid -->
    <div class="dash-stat-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); margin-bottom: 20px;">
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(99,102,241,0.15); color: var(--primary);">
                <i class="fas fa-calendar-check"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="font-size: 1.35rem;">
                    {{ number_format($totalAgenda) }}
                </div>
                <div class="dash-stat-label">Total Agenda</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(239,68,68,0.15); color: #ef4444;">
                <i class="fas fa-umbrella-beach"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="font-size: 1.35rem;">
                    {{ number_format($totalLiburPd) }}
                </div>
                <div class="dash-stat-label">Libur Peserta Didik</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(245,158,11,0.15); color: #f59e0b;">
                <i class="fas fa-person-walking-arrow-right"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="font-size: 1.35rem;">
                    {{ number_format($totalLiburGtk) }}
                </div>
                <div class="dash-stat-label">Libur Guru / Tendik</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(16,185,129,0.15); color: #10b981;">
                <i class="fas fa-file-signature"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="font-size: 1.35rem;">
                    {{ number_format($totalUjian) }}
                </div>
                <div class="dash-stat-label">Agenda Asesmen / Ujian</div>
            </div>
        </div>
    </div>

    <!-- Toolbar Filters -->
    <div class="toolbar-row" style="flex-wrap: wrap; gap: 10px;">
        <div class="toolbar-entries">
            <label for="perPageSelect" style="margin: 0;">Tampilkan</label>
            <select id="perPageSelect" class="per-page-select">
                @foreach ([10, 15, 25, 50, 100] as $n)
                    <option value="{{ $n }}" {{ $perPage == $n ? 'selected' : '' }}>{{ $n }}</option>
                @endforeach
            </select>
            <span>entri</span>
        </div>

        <div style="display: flex; flex-wrap: wrap; gap: 8px; flex: 1; min-width: 240px;">
            <select id="filterTipe" class="toolbar-filter-select" style="min-width: 150px;">
                <option value="">Semua Kategori</option>
                <option value="libur_nasional" {{ $tipe === 'libur_nasional' ? 'selected' : '' }}>Libur Nasional</option>
                <option value="libur_semester" {{ $tipe === 'libur_semester' ? 'selected' : '' }}>Libur Semester</option>
                <option value="libur_khusus" {{ $tipe === 'libur_khusus' ? 'selected' : '' }}>Libur Khusus Satpen</option>
                <option value="kegiatan_sekolah" {{ $tipe === 'kegiatan_sekolah' ? 'selected' : '' }}>Kegiatan Sekolah
                </option>
                <option value="ujian_asesmen" {{ $tipe === 'ujian_asesmen' ? 'selected' : '' }}>Ujian &amp; Asesmen
                </option>
                <option value="hari_efektif" {{ $tipe === 'hari_efektif' ? 'selected' : '' }}>Hari Efektif</option>
            </select>

            <select id="filterDampak" class="toolbar-filter-select" style="min-width: 160px;">
                <option value="">Semua Dampak Presensi</option>
                <option value="libur_pd" {{ $dampak === 'libur_pd' ? 'selected' : '' }}>Libur Peserta Didik</option>
                <option value="libur_guru" {{ $dampak === 'libur_guru' ? 'selected' : '' }}>Libur Guru</option>
                <option value="libur_tendik" {{ $dampak === 'libur_tendik' ? 'selected' : '' }}>Libur Tendik</option>
                <option value="libur_semua" {{ $dampak === 'libur_semua' ? 'selected' : '' }}>Libur Semua (PD &amp; GTK)
                </option>
            </select>

            <select id="filterBulan" class="toolbar-filter-select" style="min-width: 110px;">
                <option value="">Semua Bulan</option>
                @for ($m = 1; $m <= 12; $m++)
                    @php $mStr = sprintf('%02d', $m); @endphp
                    <option value="{{ $mStr }}" {{ $bulan === $mStr ? 'selected' : '' }}>
                        {{ \Carbon\Carbon::createFromDate(null, $m, 1)->translatedFormat('F') }}
                    </option>
                @endfor
            </select>
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

    <!-- Data Table -->
    <div class="card table-responsive-stack" style="padding: 0; margin-bottom: 24px;">
        <table class="table" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
            <thead>
                <tr style="background: rgba(255, 255, 255, 0.02); border-bottom: 1px solid var(--border-color);">
                    @php
                        $cols = [
                            ['tanggal_mulai', 'Tanggal Pelaksanaan'],
                            ['nama_kegiatan', 'Nama Agenda & Kategori'],
                            ['dampak', 'Dampak Presensi'],
                            ['created_by', 'Petugas'],
                        ];
                    @endphp
                    @foreach ($cols as [$key, $label])
                        <th class="sortable-th {{ $sort === $key ? 'sorted' : '' }}"
                            style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; user-select: none; {{ in_array($key, ['tanggal_mulai', 'nama_kegiatan']) ? 'cursor: pointer;' : '' }}"
                            data-sort="{{ in_array($key, ['tanggal_mulai', 'nama_kegiatan']) ? $key : '' }}">
                            <div style="display: inline-flex; align-items: center; gap: 6px;">
                                <span>{{ $label }}</span>
                                @if (in_array($key, ['tanggal_mulai', 'nama_kegiatan']))
                                    <i class="fas fa-sort{{ $sort === $key ? ($sortDir === 'desc' ? '-down text-primary' : '-up text-primary') : ' text-muted' }}"
                                        style="font-size: 0.75rem;"></i>
                                @endif
                            </div>
                        </th>
                    @endforeach
                    <th
                        style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: right;">
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
                                'bg' => 'rgba(16,185,129,0.15)',
                                'color' => '#10b981',
                                'border' => 'rgba(16,185,129,0.3)',
                            ],
                            'hari_efektif' => [
                                'bg' => 'rgba(6,182,212,0.15)',
                                'color' => '#06b6d4',
                                'border' => 'rgba(6,182,212,0.3)',
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
                            <div style="display: flex; flex-direction: column; align-items: flex-end; text-align: right; width: 100%; min-width: 0;">
                                <div style="font-weight: 700; color: var(--text-color); font-size: 0.9rem; margin-bottom: 4px; display: flex; align-items: center; gap: 6px; justify-content: flex-end;">
                                    <span style="display: inline-block; width: 10px; height: 10px; border-radius: 50%; background-color: {{ $item->warna }}; flex-shrink: 0;"></span>
                                    <span>{{ $item->nama_kegiatan }}</span>
                                </div>
                                <div style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap; justify-content: flex-end;">
                                    <span class="badge"
                                        style="background: {{ $styleBadge['bg'] }}; color: {{ $styleBadge['color'] }}; border: 1px solid {{ $styleBadge['border'] }}; font-size: 0.68rem; padding: 2px 7px; border-radius: 6px; white-space: nowrap;">
                                        {{ $item->tipe_label }}
                                    </span>
                                    @if ($item->keterangan)
                                        <span style="font-size: 0.74rem; color: var(--text-muted); max-width: 200px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"
                                            title="{{ $item->keterangan }}">
                                            {{ $item->keterangan }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </td>

                        <td style="padding: 14px 18px;" data-label="Presensi">
                            <div style="display: flex; flex-direction: column; gap: 4px; font-size: 0.75rem;">
                                <div>
                                    <span
                                        style="font-weight: 600; color: var(--text-muted); display: inline-block; width: 55px;">Siswa:</span>
                                    @if ($item->libur_pd)
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
                                        style="font-weight: 600; color: var(--text-muted); display: inline-block; width: 55px;">Guru:</span>
                                    @if ($item->libur_guru)
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
                                    @if ($item->libur_tendik)
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
                            style="padding: 36px; text-align: center; color: var(--text-muted); font-size: 0.86rem;">
                            <i class="fas fa-calendar-xmark mb-2" style="font-size: 2rem; opacity: 0.4;"></i>
                            <div>Belum ada agenda kalender pendidikan yang sesuai filter.</div>
                            @if ($canCreate)
                                <div style="margin-top: 8px;">
                                    <button type="button" class="btn btn-outline" id="btnEmptyTambahAgenda"
                                        style="font-size: 0.8rem; padding: 6px 14px;">
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

    <!-- Pagination -->
    @if ($list->hasPages())
        <div class="custom-pagination">
            @if ($list->onFirstPage())
                <span class="page-btn disabled"><i class="fas fa-chevron-left"></i></span>
            @else
                <a href="{{ $list->previousPageUrl() }}" class="page-btn" title="Sebelumnya"><i
                        class="fas fa-chevron-left"></i></a>
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
                    <span class="page-btn disabled">...</span>
                @endif
            @endif

            @for ($p = $from; $p <= $to; $p++)
                <a href="{{ $list->url($p) }}"
                    class="page-btn {{ $p == $cur ? 'active' : '' }}">{{ $p }}</a>
            @endfor

            @if ($to < $last)
                @if ($to < $last - 1)
                    <span class="page-btn disabled">...</span>
                @endif
                <a href="{{ $list->url($last) }}" class="page-btn">{{ $last }}</a>
            @endif

            @if ($list->hasMorePages())
                <a href="{{ $list->nextPageUrl() }}" class="page-btn" title="Berikutnya"><i
                        class="fas fa-chevron-right"></i></a>
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
                        placeholder="Contoh: Libur Hari Raya Idul Fitri / Asesmen Sumatif Akhir Semester" maxlength="200">
                </div>

                <div class="modal-form-grid"
                    style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                    <div class="form-group-compact">
                        <label>Kategori Agenda <span style="color: #ef4444;">*</span></label>
                        <select name="tipe" id="inputTipe" required>
                            <option value="libur_nasional">Libur Nasional</option>
                            <option value="libur_semester">Libur Semester / Akhir Tahun</option>
                            <option value="libur_khusus">Libur Khusus Satpen</option>
                            <option value="kegiatan_sekolah" selected>Kegiatan Sekolah / Upacara</option>
                            <option value="ujian_asesmen">Ujian &amp; Asesmen</option>
                            <option value="hari_efektif">Hari Efektif Belajar</option>
                        </select>
                    </div>

                    <div class="form-group-compact">
                        <label>Warna Penanda</label>
                        <input type="color" name="warna" id="inputWarna" value="#3b82f6"
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
                        <i class="fas fa-fingerprint text-primary me-1"></i> Integrasi Dampak Presensi
                    </div>
                    <p style="font-size: 0.74rem; color: var(--text-muted); margin-bottom: 10px;">
                        Centang peran yang diliburkan pada tanggal ini agar sistem presensi tidak mencatat ketidakhadiran
                        (alpa).
                    </p>

                    <div style="display: flex; flex-direction: column; gap: 8px;">
                        <label
                            style="display: flex; align-items: center; gap: 8px; font-size: 0.82rem; color: var(--text-color); cursor: pointer; margin: 0;">
                            <input type="checkbox" name="libur_pd" id="inputLiburPd" value="1"
                                style="width: 16px; height: 16px;">
                            <span>Liburkan <strong>Peserta Didik</strong> (Siswa Bebas Presensi)</span>
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
                        style="padding: 8px 20px; font-size: 0.85rem; font-weight: 600;">Simpan</button>
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
                    <span id="viewTipeBadge" class="badge" style="font-size: 0.72rem; margin-bottom: 6px;"></span>
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
    <script src="{{ asset('js/kalender-pendidikan.js') }}"></script>
@endpush
