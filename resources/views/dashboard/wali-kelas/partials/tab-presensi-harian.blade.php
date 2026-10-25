{{-- TAB 1: PRESENSI HARIAN KELAS BINAAN --}}

<!-- Banner Kalender / Status Hari -->
@if (!empty($isLiburHari) || ($statusHari['mode'] ?? '') === 'libur')
    <div class="card"
        style="padding: 14px 18px; margin-bottom: 20px; background: rgba(239, 68, 68, 0.08); border: 1px solid rgba(239, 68, 68, 0.25); border-radius: var(--radius-lg, 12px); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
        <div style="display: flex; align-items: center; gap: 12px;">
            <div
                style="width: 40px; height: 40px; border-radius: var(--radius-lg, 12px); background: rgba(239, 68, 68, 0.18); color: #ef4444; display: flex; align-items: center; justify-content: center; font-size: var(--text-xl, 1.25rem); flex-shrink: 0;">
                <i class="fas {{ $statusHari['is_weekend'] ?? false ? 'fa-bed' : 'fa-calendar-xmark' }}"></i>
            </div>
            <div>
                <div style="font-weight: 700; color: #ef4444; font-size: var(--text-base, 0.875rem);">
                    {{ $statusHari['label'] ?? 'Hari Libur Sekolah' }}:
                    {{ $statusHari['nama_agenda'] ?? ($statusHari['agenda']?->nama_kegiatan ?? 'Libur') }}
                </div>
                <div style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted);">
                    {{ $statusHari['keterangan'] ?? 'Berdasarkan Kalender Pendidikan & Skema Hari Sekolah, pencatatan presensi dinonaktifkan pada hari libur / non-aktif.' }}
                </div>
            </div>
        </div>
        <span class="badge badge-danger">{{ $statusHari['is_weekend'] ?? false ? 'LIBUR AKHIR PEKAN' : 'HARI LIBUR' }}</span>
    </div>
@elseif (($statusHari['mode'] ?? '') === 'daring')
    <div class="card"
        style="padding: 14px 18px; margin-bottom: 20px; background: rgba(59, 130, 246, 0.08); border: 1px solid rgba(59, 130, 246, 0.25); border-radius: var(--radius-lg, 12px); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
        <div style="display: flex; align-items: center; gap: 12px;">
            <div
                style="width: 40px; height: 40px; border-radius: var(--radius-lg, 12px); background: rgba(59, 130, 246, 0.18); color: #3b82f6; display: flex; align-items: center; justify-content: center; font-size: var(--text-xl, 1.25rem); flex-shrink: 0;">
                <i class="fas fa-laptop-house"></i>
            </div>
            <div>
                <div style="font-weight: 700; color: #3b82f6; font-size: var(--text-base, 0.875rem);">
                    Mode Pembelajaran Daring: {{ $statusHari['agenda']?->nama_kegiatan ?? 'Kegiatan Khusus' }}
                </div>
                <div style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted);">
                    Peserta Didik dapat melakukan presensi secara daring tanpa validasi geolokasi radius.
                </div>
            </div>
        </div>
        <span class="badge badge-primary">DARING</span>
    </div>
@endif

<!-- Summary Stats Grid Baku SAE -->
<div class="dash-stat-grid stat-grid-presensi" style="margin-bottom: 20px;">
    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(99,102,241,0.15); color: var(--primary);">
            <i class="fas fa-users"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem);">
                {{ $summary['total'] }}
            </div>
            <div class="dash-stat-label">Total Peserta Didik</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(16,185,129,0.15); color: #10b981;">
            <i class="fas fa-check-double"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem); color: #10b981;">
                {{ $summary['hadir'] }}
            </div>
            <div class="dash-stat-label">Hadir Tepat Waktu</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(245,158,11,0.15); color: #f59e0b;">
            <i class="fas fa-clock"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem); color: #f59e0b;">
                {{ $summary['terlambat'] }}
            </div>
            <div class="dash-stat-label">Terlambat Masuk</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(59,130,246,0.15); color: #3b82f6;">
            <i class="fas fa-envelope-open-text"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem); color: #3b82f6;">
                {{ $summary['izin'] }}
            </div>
            <div class="dash-stat-label">Izin Tercatat</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(236,72,153,0.15); color: #ec4899;">
            <i class="fas fa-notes-medical"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem); color: #ec4899;">
                {{ $summary['sakit'] }}
            </div>
            <div class="dash-stat-label">Sakit</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(239,68,68,0.15); color: #ef4444;">
            <i class="fas fa-circle-xmark"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem); color: #ef4444;">
                {{ $summary['alpha'] }}
            </div>
            <div class="dash-stat-label">Alpha</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(100,116,139,0.15); color: #64748b;">
            <i class="fas fa-user-clock"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem); color: #64748b;">
                {{ $summary['belum'] }}
            </div>
            <div class="dash-stat-label">Belum Presensi</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(6,182,212,0.15); color: #06b6d4;">
            <i class="fas fa-chart-pie"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem); color: #06b6d4;">
                {{ $summary['persen'] }}%
            </div>
            <div class="dash-stat-label">% Kehadiran</div>
        </div>
    </div>
</div>

<!-- Unified Datatable Presensi Harian (Card Terpadu) -->
<div class="card datatable-card-wrapper" id="tableDataContainer">
    <!-- Toolbar Header -->
    <div class="datatable-toolbar">
        <div class="presensi-toolbar-flex">
            <div class="presensi-filters-group">
                <div class="toolbar-entries">
                    <label for="perPageSelect" style="margin: 0;">Tampilkan</label>
                    <select id="perPageSelect" name="perPage" class="per-page-select" style="height: 38px;">
                        @foreach ([10, 15, 25, 50, 100] as $n)
                            <option value="{{ $n }}" {{ $perPage == $n ? 'selected' : '' }}>{{ $n }}</option>
                        @endforeach
                    </select>
                    <span>entri</span>
                </div>

                <!-- Filter Tanggal -->
                <div class="toolbar-entries toolbar-date-box">
                    <label for="filterTanggal" style="margin: 0; white-space: nowrap;">
                        <i class="fas fa-calendar-day text-primary me-1"></i> Tanggal:
                    </label>
                    <input type="date" id="filterTanggal" name="tanggal" class="form-control"
                        value="{{ $tanggal }}" style="height: 38px; font-size: var(--text-base, 0.875rem); padding: 7px 10px; border-radius: var(--radius-md, 8px);">
                </div>

                @if ($isAdmin && $rombelList->isNotEmpty())
                    <select id="adminRombelSelect" name="rombel_id" class="toolbar-filter-select"
                        style="min-width: 160px; height: 38px;">
                        @foreach ($rombelList as $r)
                            <option value="{{ $r->rombongan_belajar_id }}"
                                {{ $activeRombel?->rombongan_belajar_id === $r->rombongan_belajar_id ? 'selected' : '' }}>
                                {{ $r->nama }}
                            </option>
                        @endforeach
                    </select>
                @endif

                <select id="filterStatus" name="status" class="toolbar-filter-select"
                    style="min-width: 140px; height: 38px;">
                    <option value="">Semua Status</option>
                    <option value="H" {{ $statusFilter === 'H' ? 'selected' : '' }}>Hadir (H)</option>
                    <option value="T" {{ $statusFilter === 'T' ? 'selected' : '' }}>Terlambat (T)</option>
                    <option value="I" {{ $statusFilter === 'I' ? 'selected' : '' }}>Izin (I)</option>
                    <option value="S" {{ $statusFilter === 'S' ? 'selected' : '' }}>Sakit (S)</option>
                    <option value="A" {{ $statusFilter === 'A' ? 'selected' : '' }}>Alpha (A)</option>
                    <option value="pulang" {{ $statusFilter === 'pulang' ? 'selected' : '' }}>Sudah Pulang</option>
                    <option value="belum" {{ $statusFilter === 'belum' ? 'selected' : '' }}>Belum Absen</option>
                </select>

                @if ($q || $statusFilter || $tanggal !== now()->toDateString())
                    <button type="button" id="btnResetFilter"
                        class="btn btn-outline btn-responsive-icon btn-reset-filter"
                        style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px);"
                        title="Reset filter" data-live-reset>
                        <i class="fas fa-undo"></i>
                    </button>
                @endif
            </div>

            <!-- Live Search Box Baku SAE -->
            <div class="live-search-wrap" style="height: 38px;">
                <i class="fas fa-search search-icon"></i>
                <input type="text" id="liveSearchInput" name="q" placeholder="Cari nama / NISN..."
                    value="{{ $q }}" autocomplete="off">
                <button type="button" id="clearSearchBtn" class="clear-search {{ $q ? 'visible' : '' }}"
                    title="Hapus pencarian">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Konten Datatable Responsive Stack -->
    <div class="table-responsive-stack" style="overflow-x: auto; width: 100%;">
        <table class="table table-pd table-presensi-wali" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
            <thead>
                <tr style="background: rgba(255, 255, 255, 0.02); border-bottom: 1px solid var(--border-color);">
                    <th class="th-no d-none-mobile" style="padding: 12px 14px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 48px; text-align: center;">
                        No
                    </th>
                    <th class="sortable-th {{ $sort === 'nama' ? 'sorted' : '' }}"
                        style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; min-width: 220px;"
                        data-sort="nama">
                        Peserta Didik
                        <span class="sort-icon">{!! $sort === 'nama' ? ($sortDir === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                    </th>
                    <th class="sortable-th d-none-mobile {{ $sort === 'nisn' ? 'sorted' : '' }}"
                        style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 140px;"
                        data-sort="nisn">
                        NISN
                        <span class="sort-icon">{!! $sort === 'nisn' ? ($sortDir === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                    </th>
                    <th class="sortable-th {{ $sort === 'status' ? 'sorted' : '' }}"
                        style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 140px;"
                        data-sort="status">
                        Status Kehadiran
                        <span class="sort-icon">{!! $sort === 'status' ? ($sortDir === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                    </th>
                    <th class="sortable-th {{ $sort === 'jam_masuk' ? 'sorted' : '' }}"
                        style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; min-width: 150px;"
                        data-sort="jam_masuk">
                        Waktu Masuk &amp; Pulang
                        <span class="sort-icon">{!! $sort === 'jam_masuk' ? ($sortDir === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                    </th>
                    <th style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; min-width: 140px;">
                        Catatan / Verifikasi
                    </th>
                    <th style="padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: right; min-width: 220px;">
                        Aksi Presensi
                    </th>
                </tr>
            </thead>
            <tbody>
                @forelse ($list as $index => $item)
                    @php
                        $st = strtoupper($item->status ?? '');
                        $rowNum = ($list->currentPage() - 1) * $list->perPage() + $index + 1;
                    @endphp
                    <tr id="row-siswa-{{ $item->peserta_didik_id }}" class="presensi-row"
                        style="border-bottom: 1px solid var(--border-color); transition: background 0.18s ease;">
                        <!-- No (Desktop Only) -->
                        <td class="cell-pd-no d-none-mobile" style="padding: 12px 14px; text-align: center; color: var(--text-muted); font-size: var(--text-sm, 0.8125rem); font-weight: 700;" data-label="No">
                            {{ $rowNum }}
                        </td>

                        <!-- Kolom Peserta Didik -->
                        <td class="cell-pd-nama" style="padding: 12px 18px;" data-label="Peserta Didik">
                            <div class="pd-main-wrapper" style="display: flex; align-items: center; gap: 12px;">
                                <div class="user-avatar pd-foto-thumb"
                                    style="width: 42px; height: 42px; border-radius: var(--radius-lg, 12px); overflow: hidden; background: rgba(99,102,241,0.08); border: 1.5px solid var(--border-color); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                    @if (!empty($item->foto_path))
                                        <img src="{{ asset('storage/' . ltrim($item->foto_path, '/')) }}"
                                            alt="{{ $item->nama }}" style="width: 100%; height: 100%; object-fit: cover;">
                                    @else
                                        <i class="fas fa-user-graduate text-primary" style="font-size: var(--text-xl, 1.25rem);"></i>
                                    @endif
                                </div>
                                <div class="pd-info" style="min-width: 0; flex: 1;">
                                    <div class="pd-title-row" style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                                        <span class="badge badge-outline badge-row-no d-none-desktop d-block-mobile"
                                            style="font-size: var(--text-2xs, 0.6875rem); padding: 1px 6px; font-weight: 700; color: var(--text-muted);">
                                            #{{ $rowNum }}
                                        </span>
                                        <div class="pd-nama" style="font-weight: 700; font-size: var(--text-base, 0.875rem); color: var(--text-color); line-height: 1.35; word-break: break-word;">
                                            {{ $item->nama }}
                                        </div>
                                    </div>
                                    <div class="pd-meta-row" style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); font-family: monospace; margin-top: 2px;">
                                        NISN: {{ $item->nisn ?: '-' }}
                                    </div>
                                </div>
                            </div>
                        </td>

                        <!-- Kolom NISN (Desktop Only) -->
                        <td class="cell-pd-nisn d-none-mobile" style="padding: 12px 16px; font-family: monospace; font-size: var(--text-base, 0.875rem); color: var(--text-color);" data-label="NISN">
                            {{ $item->nisn ?: '-' }}
                        </td>

                        <!-- Kolom Status Kehadiran -->
                        <td class="cell-pd-status" id="badge-status-{{ $item->peserta_didik_id }}"
                            style="padding: 12px 16px; text-align: center;" data-label="Status Kehadiran">
                            @if ($st === 'H')
                                <span class="badge badge-success" style="font-weight: 700; font-size: var(--text-sm, 0.8125rem); padding: 4px 10px; border-radius: var(--radius-sm, 6px);">
                                    <i class="fas fa-check-circle me-1"></i> Hadir
                                </span>
                            @elseif ($st === 'T')
                                <span class="badge badge-warning" style="font-weight: 700; font-size: var(--text-sm, 0.8125rem); padding: 4px 10px; border-radius: var(--radius-sm, 6px);">
                                    <i class="fas fa-clock me-1"></i> Terlambat (+{{ $item->menit_terlambat }}m)
                                </span>
                            @elseif ($st === 'I')
                                <span class="badge badge-primary" style="font-weight: 700; font-size: var(--text-sm, 0.8125rem); padding: 4px 10px; border-radius: var(--radius-sm, 6px);">
                                    <i class="fas fa-envelope-open-text me-1"></i> Izin
                                </span>
                            @elseif ($st === 'S')
                                <span class="badge" style="background: rgba(139,92,246,0.15); color: #8b5cf6; border: 1px solid rgba(139,92,246,0.3); font-weight: 700; font-size: var(--text-sm, 0.8125rem); padding: 4px 10px; border-radius: var(--radius-sm, 6px);">
                                    <i class="fas fa-notes-medical me-1"></i> Sakit
                                </span>
                            @elseif ($st === 'D')
                                <span class="badge badge-info" style="font-weight: 700; font-size: var(--text-sm, 0.8125rem); padding: 4px 10px; border-radius: var(--radius-sm, 6px);">
                                    <i class="fas fa-award me-1"></i> Dispensasi
                                </span>
                            @elseif ($st === 'A')
                                <span class="badge badge-danger" style="font-weight: 700; font-size: var(--text-sm, 0.8125rem); padding: 4px 10px; border-radius: var(--radius-sm, 6px);">
                                    <i class="fas fa-circle-xmark me-1"></i> Alpha
                                </span>
                            @else
                                <span class="badge badge-outline" style="font-size: var(--text-xs, 0.75rem); padding: 3px 8px; color: var(--text-muted);">
                                    Belum Absen
                                </span>
                            @endif
                        </td>

                        <!-- Kolom Waktu Masuk & Pulang -->
                        <td class="cell-pd-waktu" id="waktu-status-{{ $item->peserta_didik_id }}"
                            style="padding: 12px 16px; text-align: center;" data-label="Waktu Masuk & Pulang">
                            <div class="waktu-pill-wrapper" style="display: inline-flex; align-items: center; gap: 8px; font-size: var(--text-sm, 0.8125rem); color: var(--text-color);">
                                <span title="Jam Masuk" style="display: inline-flex; align-items: center; gap: 4px;">
                                    <i class="fas fa-arrow-right-to-bracket text-success" style="font-size: var(--text-xs, 0.75rem);"></i>
                                    <strong class="text-jam-masuk">{{ $item->jam_masuk ? substr($item->jam_masuk, 0, 5) : '—' }}</strong>
                                </span>
                                <span style="color: var(--border-color);">&bull;</span>
                                <span title="Jam Pulang" style="display: inline-flex; align-items: center; gap: 4px;">
                                    <i class="fas fa-arrow-right-from-bracket text-info" style="font-size: var(--text-xs, 0.75rem);"></i>
                                    <strong class="text-jam-pulang">{{ $item->jam_pulang ? substr($item->jam_pulang, 0, 5) : '—' }}</strong>
                                </span>
                            </div>
                        </td>

                        <!-- Kolom Catatan / Verifikasi -->
                        <td class="cell-pd-ket" id="ket-status-{{ $item->peserta_didik_id }}"
                            style="padding: 12px 18px;" data-label="Catatan">
                            @php
                                $isAutoKet = !empty($item->keterangan) && str_starts_with($item->keterangan, 'Dicatat manual');
                            @endphp
                            @if (!empty($item->keterangan) && !$isAutoKet)
                                <span style="font-size: var(--text-sm, 0.8125rem); color: var(--text-color); line-height: 1.35;" title="{{ $item->keterangan }}">
                                    {{ $item->keterangan }}
                                </span>
                            @elseif ($item->verified_by)
                                <span style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted);" title="Diverifikasi oleh {{ $item->verified_by }}">
                                    <i class="fas fa-user-pen text-primary me-1"></i>{{ $item->verified_by }}
                                </span>
                            @else
                                <span style="color: var(--text-muted); font-size: var(--text-sm, 0.8125rem);">&mdash;</span>
                            @endif
                        </td>

                        <!-- Kolom Aksi Presensi -->
                        <td class="cell-pd-aksi" style="padding: 12px 18px; text-align: right;" data-label="Aksi Presensi">
                            <div class="presensi-action-box">
                                <div class="d-none-desktop d-block-mobile presensi-mobile-action-label"
                                    style="font-size: var(--text-xs, 0.75rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 6px;">
                                    Presensi Cepat:
                                </div>
                                @if ($item->is_mandiri)
                                    <span class="badge badge-success badge-mandiri-full" style="font-size: var(--text-xs, 0.75rem); padding: 6px 12px;"
                                        title="Presensi mandiri oleh siswa via {{ strtoupper($item->metode_masuk) }}">
                                        <i class="fas fa-id-card-clip me-1"></i> MANDIRI ({{ strtoupper($item->metode_masuk) }})
                                    </span>
                                @elseif ($canUpdate)
                                    @if (!empty($isLiburHari))
                                        <span class="badge badge-libur-full"
                                            style="background: rgba(239,68,68,0.1); color: #ef4444; border: 1px solid rgba(239,68,68,0.25); font-size: var(--text-xs, 0.75rem); padding: 6px 12px;"
                                            title="Presensi dinonaktifkan pada hari libur / non-aktif belajar">
                                            <i class="fas fa-ban me-1"></i> Libur Sekolah
                                        </span>
                                    @else
                                        <div class="table-actions presensi-btn-group">
                                            <button type="button"
                                                class="btn-status-toggle btn-presensi-manual {{ $st === 'H' ? 'active-H' : '' }}"
                                                data-id="{{ $item->peserta_didik_id }}" data-nama="{{ $item->nama }}"
                                                data-action="masuk" data-status="H" title="Catat Hadir">
                                                H
                                            </button>
                                            <button type="button"
                                                class="btn-status-toggle btn-presensi-manual {{ $st === 'T' ? 'active-T' : '' }}"
                                                data-id="{{ $item->peserta_didik_id }}" data-nama="{{ $item->nama }}"
                                                data-action="terlambat" data-status="T" title="Catat Terlambat">
                                                T
                                            </button>
                                            <button type="button"
                                                class="btn-status-toggle btn-presensi-manual {{ $st === 'I' ? 'active-I' : '' }}"
                                                data-id="{{ $item->peserta_didik_id }}" data-nama="{{ $item->nama }}"
                                                data-action="izin" data-status="I" title="Catat Izin">
                                                I
                                            </button>
                                            <button type="button"
                                                class="btn-status-toggle btn-presensi-manual {{ $st === 'S' ? 'active-S' : '' }}"
                                                data-id="{{ $item->peserta_didik_id }}" data-nama="{{ $item->nama }}"
                                                data-action="sakit" data-status="S" title="Catat Sakit">
                                                S
                                            </button>
                                            <button type="button"
                                                class="btn-status-toggle btn-presensi-manual {{ $st === 'A' ? 'active-A' : '' }}"
                                                data-id="{{ $item->peserta_didik_id }}" data-nama="{{ $item->nama }}"
                                                data-action="alpha" data-status="A" title="Catat Alpha">
                                                A
                                            </button>
                                            <button type="button"
                                                class="btn-status-toggle btn-presensi-manual {{ !empty($item->jam_pulang) ? 'active-P active-D' : '' }}"
                                                data-id="{{ $item->peserta_didik_id }}" data-nama="{{ $item->nama }}"
                                                data-action="pulang" data-status="pulang"
                                                title="{{ !empty($item->jam_pulang) ? 'Sudah Pulang (' . substr($item->jam_pulang, 0, 5) . ')' : 'Catat Pulang' }}">
                                                P
                                            </button>
                                        </div>
                                    @endif
                                @else
                                    <span class="badge badge-outline" style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted);">
                                        Read-Only
                                    </span>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="cell-empty" style="padding: 40px 20px; text-align: center; color: var(--text-muted);">
                            <div style="font-size: var(--text-3xl, 1.875rem); opacity: 0.35; margin-bottom: 8px;">
                                <i class="fas fa-folder-open"></i>
                            </div>
                            <div style="font-weight: 700; color: var(--text-color); margin-bottom: 4px;">
                                Tidak Ada Data Presensi Siswa
                            </div>
                            <div style="font-size: var(--text-sm, 0.8125rem);">Tidak ditemukan catatan peserta didik untuk filter tanggal yang dipilih.</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- 3. Footer Paginasi Terpadu Datatable -->
    @if ($list->hasPages())
        <div class="datatable-footer">
            @include('partials.datatable-pagination', ['paginator' => $list])
        </div>
    @endif
</div>
