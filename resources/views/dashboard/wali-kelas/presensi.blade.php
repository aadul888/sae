@extends('layouts.dashboard')

@section('title', 'Presensi Kelas Binaan — Wali Kelas — SAE')
@section('dash_title', 'Presensi Kelas Binaan')

@section('content')
    <!-- Header Banner -->
    <div class="dash-banner" style="margin-bottom: 24px;">
        <div>
            <h2 style="font-size: 1.35rem; font-weight: 800; color: var(--text-color); margin-bottom: 4px;">
                <i class="fas fa-clipboard-user text-primary me-2"></i> Presensi Kelas Binaan
            </h2>
            <p style="color: var(--text-muted); font-size: 0.85rem; margin: 0;">
                @if ($hasRombel && $activeRombel)
                    Kelas Binaan: <strong style="color: var(--text-color);">{{ $activeRombel->nama }}</strong>
                    &bull; Wali Kelas: <strong style="color: var(--primary);">{{ $waliNama }}</strong>
                    @if (!empty($activeRombel->jurusan_id_str))
                        &bull; Jurusan: {{ $activeRombel->jurusan_id_str }}
                    @endif
                @else
                    Monitoring dan pencatatan presensi peserta didik kelas binaan.
                @endif
            </p>
        </div>
        <div class="dash-banner-actions">
            @if ($hasRombel && $activeRombel)
                <button type="button" id="btnOpenModalPdf" class="btn btn-outline btn-responsive-icon" style="border-color: var(--primary); color: var(--primary); padding: 8px 16px; font-size: 0.84rem; font-weight: 600;" title="Cetak Laporan Presensi">
                    <i class="fas fa-print"></i> <span class="btn-responsive-text">Cetak Laporan</span>
                </button>
            @endif
        </div>
    </div>

    @if (!$hasRombel)
        <!-- Empty State jika Guru belum ada tugas rombel -->
        <div class="card" style="padding: 40px 20px; text-align: center; margin-top: 20px;">
            <div style="width: 70px; height: 70px; border-radius: 50%; background: rgba(245, 158, 11, 0.15); color: #f59e0b; display: inline-flex; align-items: center; justify-content: center; font-size: 2rem; margin-bottom: 16px;">
                <i class="fas fa-triangle-exclamation"></i>
            </div>
            <h3 style="font-size: 1.15rem; font-weight: 700; color: var(--text-color); margin-bottom: 8px;">Belum Ada Rombel Binaan Terdaftar</h3>
            <p style="color: var(--text-muted); font-size: 0.9rem; max-width: 540px; margin: 0 auto 20px;">
                Akun Anda belum terikat dengan rombongan belajar aktif sebagai Wali Kelas pada data Dapodik sekolah. Silakan hubungi Administrator sistem atau Operator Dapodik untuk pembaruan penugasan tugas tambahan.
            </p>
        </div>
    @else
        <!-- Banner Kalender / Status Hari -->
        @if ($statusHari['mode'] === 'libur')
            <div class="card" style="padding: 14px 18px; margin-bottom: 24px; background: rgba(239, 68, 68, 0.08); border: 1px solid rgba(239, 68, 68, 0.25); border-radius: 12px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div style="width: 40px; height: 40px; border-radius: 10px; background: rgba(239, 68, 68, 0.18); color: #ef4444; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; flex-shrink: 0;">
                        <i class="fas fa-calendar-xmark"></i>
                    </div>
                    <div>
                        <div style="font-weight: 700; color: #ef4444; font-size: 0.92rem;">Hari Libur Sekolah Resmi: {{ $statusHari['agenda']?->nama_kegiatan ?? 'Libur' }}</div>
                        <div style="font-size: 0.8rem; color: var(--text-muted);">Berdasarkan Kalender Pendidikan, presensi dinonaktifkan pada hari libur resmi.</div>
                    </div>
                </div>
                <span class="badge badge-danger">LIBUR</span>
            </div>
        @elseif ($statusHari['mode'] === 'daring')
            <div class="card" style="padding: 14px 18px; margin-bottom: 24px; background: rgba(59, 130, 246, 0.08); border: 1px solid rgba(59, 130, 246, 0.25); border-radius: 12px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div style="width: 40px; height: 40px; border-radius: 10px; background: rgba(59, 130, 246, 0.18); color: #3b82f6; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; flex-shrink: 0;">
                        <i class="fas fa-laptop-house"></i>
                    </div>
                    <div>
                        <div style="font-weight: 700; color: #3b82f6; font-size: 0.92rem;">Mode Pembelajaran Daring: {{ $statusHari['agenda']?->nama_kegiatan ?? 'Kegiatan Khusus' }}</div>
                        <div style="font-size: 0.8rem; color: var(--text-muted);">Peserta Didik dapat melakukan presensi secara daring tanpa validasi geolokasi radius.</div>
                    </div>
                </div>
                <span class="badge badge-primary">DARING</span>
            </div>
        @endif

        <!-- Summary Stats Grid: Lebar & Proporsional Sesuai Standar SAE (4 kolom per baris) -->
        <div class="dash-stat-grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 24px;">
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(99,102,241,0.15); color: var(--primary);">
                    <i class="fas fa-users"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" style="font-size: 1.35rem;">
                        {{ $summary['total'] }}
                    </div>
                    <div class="dash-stat-label">Total Peserta Didik di Kelas</div>
                </div>
            </div>

            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(16,185,129,0.15); color: #10b981;">
                    <i class="fas fa-check-double"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" style="font-size: 1.35rem; color: #10b981;">
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
                    <div class="dash-stat-value" style="font-size: 1.35rem; color: #f59e0b;">
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
                    <div class="dash-stat-value" style="font-size: 1.35rem; color: #3b82f6;">
                        {{ $summary['izin'] }}
                    </div>
                    <div class="dash-stat-label">Izin / Dispensasi</div>
                </div>
            </div>

            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(139,92,246,0.15); color: #8b5cf6;">
                    <i class="fas fa-notes-medical"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" style="font-size: 1.35rem; color: #8b5cf6;">
                        {{ $summary['sakit'] }}
                    </div>
                    <div class="dash-stat-label">Sakit (Keterangan)</div>
                </div>
            </div>

            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(239,68,68,0.15); color: #ef4444;">
                    <i class="fas fa-door-open"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" style="font-size: 1.35rem; color: #ef4444;">
                        {{ $summary['pulang'] }}
                    </div>
                    <div class="dash-stat-label">Sudah Absen Pulang</div>
                </div>
            </div>

            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(100,116,139,0.15); color: #64748b;">
                    <i class="fas fa-user-xmark"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" style="font-size: 1.35rem; color: #64748b;">
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
                    <div class="dash-stat-value" style="font-size: 1.35rem; color: #06b6d4;">
                        {{ $summary['persen'] }}%
                    </div>
                    <div class="dash-stat-label">% Kehadiran Kelas</div>
                </div>
            </div>
        </div>

        <!-- Toolbar & Filter Baku SAE -->
        <div class="card" style="padding: 16px 20px; margin-bottom: 24px;">
            <div style="display: flex; flex-wrap: wrap; gap: 14px; justify-content: space-between; align-items: center;">
                <div style="display: flex; flex-wrap: wrap; gap: 12px; align-items: center;">
                    <div class="toolbar-entries">
                        <label for="perPageSelect" style="margin: 0; font-size: 0.85rem; color: var(--text-muted);">Tampilkan</label>
                        <select id="perPageSelect" class="per-page-select">
                            @foreach ([10, 15, 25, 50, 100] as $n)
                                <option value="{{ $n }}" {{ $perPage == $n ? 'selected' : '' }}>{{ $n }}</option>
                            @endforeach
                        </select>
                        <span style="font-size: 0.85rem; color: var(--text-muted);">entri</span>
                    </div>

                    <!-- Filter Tanggal -->
                    <div class="toolbar-entries">
                        <label for="filterTanggal" style="margin: 0; font-size: 0.85rem; color: var(--text-muted); white-space: nowrap;">
                            <i class="fas fa-calendar-day me-1"></i> Tanggal:
                        </label>
                        <input type="date" id="filterTanggal" class="form-control" value="{{ $tanggal }}" style="font-size: 0.85rem; padding: 7px 12px; border-radius: 8px; border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-color);">
                    </div>

                    @if ($isAdmin && $rombelList->isNotEmpty())
                        <select id="adminRombelSelect" class="toolbar-filter-select" style="min-width: 160px; font-size: 0.85rem; padding: 7px 12px;">
                            @foreach ($rombelList as $r)
                                <option value="{{ $r->rombongan_belajar_id }}" {{ ($activeRombel?->rombongan_belajar_id === $r->rombongan_belajar_id) ? 'selected' : '' }}>
                                    {{ $r->nama }}
                                </option>
                            @endforeach
                        </select>
                    @endif

                    <select id="filterStatus" class="toolbar-filter-select" style="min-width: 150px; font-size: 0.85rem; padding: 7px 12px;">
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
                        <button type="button" id="btnResetFilter" class="btn btn-outline btn-responsive-icon" style="padding: 7px 14px; font-size: 0.82rem;" title="Reset filter">
                            <i class="fas fa-undo"></i> <span class="btn-responsive-text">Reset</span>
                        </button>
                    @endif
                </div>

                <!-- Live Search Box Baku SAE -->
                <div class="live-search-wrap">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" id="liveSearchInput" placeholder="Cari nama / NISN..." value="{{ $q }}" autocomplete="off">
                    <button type="button" id="clearSearchBtn" class="clear-search {{ $q ? 'visible' : '' }}" title="Hapus pencarian">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Datatable Presensi Kelas Binaan Baku SAE (table-responsive-stack & table-pd) -->
        <div class="card table-responsive-stack" id="tableDataContainer" style="padding: 0; margin-bottom: 24px;">
            <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                <thead>
                    <tr style="background: rgba(255, 255, 255, 0.02); border-bottom: 1px solid var(--border-color);">
                        @php
                            $currentSort = $sort ?? 'nama';
                            $currentSortDir = $sortDir ?? 'asc';
                            $sortableCols = [
                                ['nama', 'Peserta Didik'],
                                ['nisn', 'NISN / NIPD'],
                                ['status', 'Status'],
                                ['jam_masuk', 'Masuk / Pulang'],
                            ];
                        @endphp
                        @foreach ($sortableCols as [$key, $label])
                            <th class="sortable-th {{ $currentSort === $key ? 'sorted' : '' }}"
                                style="padding: 14px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; {{ $key === 'status' ? 'text-align: center;' : '' }}"
                                data-sort="{{ $key }}">
                                {{ $label }}
                                <span class="sort-icon">{!! $currentSort === $key ? ($currentSortDir === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                            </th>
                        @endforeach
                        <th style="padding: 14px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; text-align: center;">
                            Metode
                        </th>
                        <th style="padding: 14px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px;">
                            Keterangan
                        </th>
                        <th style="padding: 14px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; text-align: right;">
                            Aksi
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($list as $item)
                        <tr style="border-bottom: 1px solid var(--border-color); transition: background 0.2s ease;">
                            <!-- Kolom Nama & Avatar (padding lapang & rapih seperti referensi) -->
                            <td class="cell-pd-nama" style="padding: 14px 18px; font-weight: 700; color: var(--text-color); font-size: 0.88rem;" data-label="Peserta Didik">
                                <div class="pd-main-wrapper" style="display: flex; align-items: center; gap: 14px;">
                                    @if (!empty($item->foto_url))
                                        <div class="pd-foto-thumb" style="width: 42px; height: 42px; border-radius: 10px; background: repeating-conic-gradient(#2a3447 0% 25%, #182030 0% 50%) 50% / 8px 8px; border: 1.5px solid var(--border-color); display: flex; align-items: center; justify-content: center; overflow: hidden; flex-shrink: 0; box-shadow: 0 2px 6px rgba(0,0,0,0.25);">
                                            <img src="{{ $item->foto_url }}" alt="{{ $item->nama }}" style="width: 100%; height: 100%; object-fit: cover;">
                                        </div>
                                    @else
                                        <div class="pd-foto-thumb empty" style="width: 42px; height: 42px; border-radius: 10px; background: rgba(99,102,241,0.08); border: 1.5px dashed rgba(99,102,241,0.4); display: flex; align-items: center; justify-content: center; color: var(--primary); font-size: 1.05rem; flex-shrink: 0;">
                                            <i class="fas fa-user-graduate" style="opacity: 0.7;"></i>
                                        </div>
                                    @endif
                                    <div class="pd-info">
                                        <div class="pd-title-row" style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                                            <span class="pd-nama" style="font-weight: 700; color: var(--text-color); font-size: 0.90rem;">{{ $item->nama }}</span>
                                        </div>
                                        <div style="font-size: 0.74rem; color: var(--text-muted); margin-top: 2px;">
                                            {{ $item->jenis_kelamin === 'L' ? 'Laki-Laki (L)' : 'Perempuan (P)' }}
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <!-- Kolom NISN / NIPD -->
                            <td class="cell-pd-nisn" style="padding: 14px 18px; font-family: monospace; font-size: 0.84rem; color: var(--primary);" data-label="NISN / NIPD">
                                <div class="cell-col-right">
                                    <div>{{ $item->nisn ?: '-' }}</div>
                                    @if ($item->nipd)
                                        <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 2px;">NIPD: {{ $item->nipd }}</div>
                                    @endif
                                </div>
                            </td>

                            <!-- Kolom Status Kehadiran -->
                            <td class="cell-pd-gender" style="padding: 14px 18px; text-align: center;" data-label="Status">
                                {!! $item->status_badge !!}
                                @if ($item->status === 'T' && $item->menit_terlambat > 0)
                                    <div style="font-size: 0.7rem; color: #f59e0b; margin-top: 3px; font-weight: 600;">+{{ $item->menit_terlambat }} mnt</div>
                                @endif
                            </td>

                            <!-- Kolom Jam Masuk / Pulang -->
                            <td class="cell-pd-ttl" style="padding: 14px 18px; font-size: 0.82rem;" data-label="Masuk / Pulang">
                                <div class="cell-col-right" style="font-family: monospace; display: flex; flex-direction: column; gap: 4px;">
                                    <div>
                                        <i class="fas fa-arrow-right-to-bracket me-1" style="color: #10b981; font-size: 0.75rem;"></i>
                                        <span style="color: var(--text-color); font-weight: 600;">
                                            {{ $item->jam_masuk ? substr($item->jam_masuk, 0, 5) . ' WIB' : '-' }}
                                        </span>
                                    </div>
                                    <div>
                                        <i class="fas fa-arrow-right-from-bracket me-1" style="color: #8b5cf6; font-size: 0.75rem;"></i>
                                        <span style="color: {{ $item->jam_pulang ? 'var(--text-color)' : 'var(--text-muted)' }};">
                                            {{ $item->jam_pulang ? substr($item->jam_pulang, 0, 5) . ' WIB' : '-' }}
                                        </span>
                                    </div>
                                </div>
                            </td>

                            <!-- Kolom Metode -->
                            <td class="cell-pd-rombel" style="padding: 14px 18px; text-align: center;" data-label="Metode">
                                @if ($item->metode_masuk === 'rfid')
                                    <span class="badge badge-primary" style="font-size: 0.72rem; padding: 4px 9px;" title="Presensi Mandiri via RFID">
                                        <i class="fas fa-id-card me-1"></i> RFID
                                    </span>
                                @elseif ($item->metode_masuk === 'qr')
                                    <span class="badge badge-info" style="font-size: 0.72rem; padding: 4px 9px;" title="Presensi Mandiri via QR Code">
                                        <i class="fas fa-qrcode me-1"></i> QR
                                    </span>
                                @elseif ($item->metode_masuk === 'manual')
                                    <span class="badge badge-warning" style="font-size: 0.72rem; padding: 4px 9px;" title="Presensi Dicatat Manual Wali Kelas">
                                        <i class="fas fa-hand-holding-hand me-1"></i> Manual
                                    </span>
                                @else
                                    <span style="color: var(--text-muted); font-size: 0.84rem;">-</span>
                                @endif
                            </td>

                            <!-- Kolom Keterangan -->
                            <td class="cell-pd-kontak" style="padding: 14px 18px; font-size: 0.82rem; color: var(--text-muted);" data-label="Keterangan">
                                <div class="cell-col-right" style="font-size: 0.82rem; color: var(--text-muted); max-width: 220px; white-space: normal; line-height: 1.35;">
                                    <div>{{ $item->keterangan ?: '-' }}</div>
                                    @if (!empty($item->verified_by))
                                        <div style="font-size: 0.7rem; color: var(--primary); margin-top: 3px; font-weight: 600;">
                                            <i class="fas fa-user-check me-1"></i> {{ $item->verified_by }}
                                        </div>
                                    @endif
                                </div>
                            </td>

                            <!-- Kolom Aksi Baku SAE: Tombol dengan Ukuran & Gap Proporsional -->
                            <td class="cell-pd-aksi" style="padding: 14px 18px; text-align: right;" data-label="Aksi">
                                <div class="table-actions" style="display: flex; align-items: center; justify-content: flex-end; gap: 8px; flex-wrap: nowrap;">
                                    @if ($item->is_mandiri)
                                        <span class="badge" style="background: rgba(16, 185, 129, 0.12); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.3); font-size: 0.72rem; padding: 5px 9px;" title="Presensi Peserta Didik Mandiri ({{ strtoupper($item->metode_masuk) }}). Tidak dapat diubah.">
                                            <i class="fas fa-lock me-1"></i> Mandiri
                                        </span>
                                    @elseif ($item->is_locked)
                                        <span class="badge badge-outline" style="font-size: 0.72rem; padding: 5px 9px;" title="{{ $item->lock_reason }}">
                                            <i class="fas fa-lock me-1"></i> Final
                                        </span>
                                    @elseif ($item->can_pulang)
                                        <button type="button" class="btn-icon btn-presensi-manual"
                                            data-id="{{ $item->peserta_didik_id }}"
                                            data-nama="{{ $item->nama }}"
                                            data-action="pulang"
                                            data-tanggal="{{ $tanggal }}"
                                            style="width: 32px; height: 32px; border-radius: 8px; border: 1px solid rgba(139, 92, 246, 0.35); background: rgba(139, 92, 246, 0.1); color: #8b5cf6; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s ease;"
                                            title="Catat Kepulangan Manual">
                                            <i class="fas fa-arrow-right-from-bracket"></i>
                                        </button>
                                    @else
                                        @if ($canUpdate)
                                            <!-- Ikon Ceklist Hijau (Icon Only) -->
                                            <button type="button" class="btn-icon btn-presensi-manual"
                                                data-id="{{ $item->peserta_didik_id }}"
                                                data-nama="{{ $item->nama }}"
                                                data-action="masuk"
                                                data-tanggal="{{ $tanggal }}"
                                                style="width: 32px; height: 32px; border-radius: 8px; border: 1px solid rgba(16, 185, 129, 0.35); background: rgba(16, 185, 129, 0.1); color: #10b981; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s ease;"
                                                title="Hadir Tepat Waktu (H)">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <button type="button" class="btn-icon btn-presensi-manual"
                                                data-id="{{ $item->peserta_didik_id }}"
                                                data-nama="{{ $item->nama }}"
                                                data-action="terlambat"
                                                data-tanggal="{{ $tanggal }}"
                                                style="width: 32px; height: 32px; border-radius: 8px; border: 1px solid rgba(245, 158, 11, 0.35); background: rgba(245, 158, 11, 0.1); color: #f59e0b; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s ease;"
                                                title="Terlambat (T)">
                                                <i class="fas fa-clock"></i>
                                            </button>
                                            <button type="button" class="btn-icon btn-presensi-manual"
                                                data-id="{{ $item->peserta_didik_id }}"
                                                data-nama="{{ $item->nama }}"
                                                data-action="izin"
                                                data-tanggal="{{ $tanggal }}"
                                                style="width: 32px; height: 32px; border-radius: 8px; border: 1px solid rgba(59, 130, 246, 0.35); background: rgba(59, 130, 246, 0.1); color: #3b82f6; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s ease;"
                                                title="Izin (I)">
                                                <i class="fas fa-envelope"></i>
                                            </button>
                                            <button type="button" class="btn-icon btn-presensi-manual"
                                                data-id="{{ $item->peserta_didik_id }}"
                                                data-nama="{{ $item->nama }}"
                                                data-action="sakit"
                                                data-tanggal="{{ $tanggal }}"
                                                style="width: 32px; height: 32px; border-radius: 8px; border: 1px solid rgba(99, 102, 241, 0.35); background: rgba(99, 102, 241, 0.1); color: #6366f1; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s ease;"
                                                title="Sakit (S)">
                                                <i class="fas fa-notes-medical"></i>
                                            </button>
                                        @endif
                                    @endif

                                    <!-- Unduh Rekap Presensi PDF Per Peserta Didik -->
                                    <a href="{{ route('dashboard.wali-kelas.presensi.pdf', ['tipe' => 'siswa', 'siswa_id' => $item->peserta_didik_id, 'bulan' => date('Y-m', strtotime($tanggal))]) }}"
                                        target="_blank"
                                        class="btn-icon"
                                        style="width: 32px; height: 32px; border-radius: 8px; border: 1px solid rgba(239, 68, 68, 0.35); background: rgba(239, 68, 68, 0.1); color: #ef4444; display: inline-flex; align-items: center; justify-content: center; text-decoration: none; transition: all 0.2s ease;"
                                        title="Unduh Lembar Presensi PDF ({{ $item->nama }})">
                                        <i class="fas fa-file-pdf"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="padding: 40px; text-align: center; color: var(--text-muted);">
                                <i class="fas fa-folder-open mb-2" style="font-size: 2rem; opacity: 0.4; display: block;"></i>
                                <div>Belum ada data presensi yang cocok dengan filter.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Custom Pagination Baku SAE (Dilarang Memakai $list->links() Mentah) -->
        @if (method_exists($list, 'hasPages') && $list->hasPages())
            <div class="custom-pagination">
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
                    @if ($from > 2) <span class="page-info">&hellip;</span> @endif
                @endif
                @for ($i = $from; $i <= $to; $i++)
                    <a href="{{ $list->url($i) }}" class="page-btn {{ $i === $cur ? 'current' : '' }}">{{ $i }}</a>
                @endfor
                @if ($to < $last)
                    @if ($to < $last - 1) <span class="page-info">&hellip;</span> @endif
                    <a href="{{ $list->url($last) }}" class="page-btn">{{ $last }}</a>
                @endif
                @if ($list->hasMorePages())
                    <a href="{{ $list->nextPageUrl() }}" class="page-btn" title="Selanjutnya"><i class="fas fa-chevron-right"></i></a>
                @else
                    <span class="page-btn disabled"><i class="fas fa-chevron-right"></i></span>
                @endif
            </div>
        @endif
    @endif

    <!-- Modal Unduh Laporan PDF Multi-Periode -->
    <div id="modalDownloadPdf" class="modal-backdrop" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.65); backdrop-filter: blur(4px); z-index: 99999 !important; align-items: center; justify-content: center;">
        <div class="card" style="width: 100%; max-width: 520px; margin: 16px; padding: 24px; border-radius: 14px; box-shadow: 0 20px 40px rgba(0,0,0,0.5); border: 1px solid var(--border-color); background: var(--bg-card);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; border-bottom: 1px solid var(--border-color); padding-bottom: 12px;">
                <h3 style="font-size: 1.15rem; font-weight: 700; color: var(--text-color); margin: 0;">
                    <i class="fas fa-print text-primary me-2"></i> Cetak Laporan Presensi Kelas
                </h3>
                <button type="button" id="btnCloseModalPdf" class="btn-icon" style="background: none; border: none; font-size: 1.1rem; color: var(--text-muted); cursor: pointer;">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form id="formDownloadPdf" method="GET" action="{{ route('dashboard.wali-kelas.presensi.pdf', ['tipe' => 'siswa']) }}" target="_blank">
                <!-- Pilihan Tipe Laporan (Sesuai Konsep: Peserta Didik, Hari, Bulan, Semester, Tahun) -->
                <div style="margin-bottom: 14px;">
                    <label for="selectTipeLaporan" style="display: block; font-size: 0.82rem; font-weight: 600; color: var(--text-color); margin-bottom: 6px;">
                        Jenis Laporan Presensi <span style="color: var(--danger);">*</span>
                    </label>
                    <select id="selectTipeLaporan" class="form-control" style="width: 100%; font-size: 0.88rem; padding: 8px 12px; border-radius: 8px;">
                        <option value="siswa" selected>1. Laporan Per Peserta Didik (Kartu Presensi Individu)</option>
                        <option value="hari">2. Laporan Per Hari (Presensi Harian Kelas)</option>
                        <option value="bulan">3. Laporan Per Bulan (Matriks Presensi Bulanan)</option>
                        <option value="semester">4. Laporan Per Semester (Rekapitulasi Semester)</option>
                        <option value="tahun">5. Laporan Per Tahun (Rekapitulasi Tahun Ajaran)</option>
                    </select>
                </div>

                <!-- 1. Filter Peserta Didik Dropdown (khusus Laporan Per Peserta Didik) -->
                <div id="groupFilterSiswa" style="margin-bottom: 14px;">
                    <label style="display: block; font-size: 0.82rem; font-weight: 600; color: var(--text-color); margin-bottom: 6px;">
                        Pilih Peserta Didik <span style="color: var(--danger);">*</span>
                    </label>
                    <select name="siswa_id" class="form-control" style="width: 100%; font-size: 0.88rem; padding: 8px 12px; border-radius: 8px;">
                        @foreach (($allSiswa ?? $list) as $s)
                            <option value="{{ $s->peserta_didik_id }}">{{ $s->nama }} ({{ $s->nisn ?: '-' }})</option>
                        @endforeach
                    </select>
                </div>

                <!-- 2. Filter Tanggal (untuk Laporan Harian) -->
                <div id="groupFilterTanggal" style="margin-bottom: 14px; display: none;">
                    <label style="display: block; font-size: 0.82rem; font-weight: 600; color: var(--text-color); margin-bottom: 6px;">
                        Tanggal Presensi
                    </label>
                    <input type="date" name="tanggal" value="{{ $tanggal }}" class="form-control" style="width: 100%; font-size: 0.88rem; padding: 8px 12px; border-radius: 8px;">
                </div>

                <!-- 3. Filter Bulan (untuk Laporan Bulanan & Per Peserta Didik) -->
                <div id="groupFilterBulan" style="margin-bottom: 14px;">
                    <label style="display: block; font-size: 0.82rem; font-weight: 600; color: var(--text-color); margin-bottom: 6px;">
                        Bulan &amp; Tahun
                    </label>
                    <input type="month" name="bulan" value="{{ date('Y-m') }}" class="form-control" style="width: 100%; font-size: 0.88rem; padding: 8px 12px; border-radius: 8px;">
                </div>

                <!-- 4. Filter Semester (untuk Rekap Semester) -->
                <div id="groupFilterSemester" style="margin-bottom: 14px; display: none;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                        <div>
                            <label style="display: block; font-size: 0.82rem; font-weight: 600; color: var(--text-color); margin-bottom: 6px;">Semester</label>
                            <select name="semester" class="form-control" style="width: 100%; font-size: 0.88rem; padding: 8px 12px; border-radius: 8px;">
                                <option value="1" {{ now()->month >= 7 ? 'selected' : '' }}>Semester Ganjil (1)</option>
                                <option value="2" {{ now()->month < 7 ? 'selected' : '' }}>Semester Genap (2)</option>
                            </select>
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.82rem; font-weight: 600; color: var(--text-color); margin-bottom: 6px;">Tahun</label>
                            <input type="number" name="tahun" value="{{ now()->year }}" class="form-control" style="width: 100%; font-size: 0.88rem; padding: 8px 12px; border-radius: 8px;">
                        </div>
                    </div>
                </div>

                <!-- 5. Filter Tahun Ajaran (untuk Rekap Tahunan) -->
                <div id="groupFilterTahun" style="margin-bottom: 14px; display: none;">
                    <label style="display: block; font-size: 0.82rem; font-weight: 600; color: var(--text-color); margin-bottom: 6px;">
                        Tahun Pelajaran
                    </label>
                    @php
                        $currTahunAjar = now()->month >= 7 ? now()->year : now()->year - 1;
                    @endphp
                    <select name="tahun_ajaran" class="form-control" style="width: 100%; font-size: 0.88rem; padding: 8px 12px; border-radius: 8px;">
                        @for ($y = $currTahunAjar + 1; $y >= $currTahunAjar - 3; $y--)
                            <option value="{{ $y }}" {{ $y == $currTahunAjar ? 'selected' : '' }}>Tahun Pelajaran {{ $y }}/{{ $y + 1 }}</option>
                        @endfor
                    </select>
                </div>

                <div style="background: rgba(99,102,241,0.06); border: 1px solid rgba(99,102,241,0.2); border-radius: 8px; padding: 10px 12px; margin-bottom: 18px; font-size: 0.78rem; color: var(--text-muted);">
                    <i class="fas fa-info-circle text-primary me-1"></i> Dokumen PDF dilengkapi dengan <strong>Kop Surat Sekolah Resmi</strong>, identitas kelas binaan, dan blok tanda tangan Wali Kelas serta Kepala Sekolah.
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 8px;">
                    <button type="button" class="btn btn-outline" onclick="document.getElementById('modalDownloadPdf').style.display='none'" style="padding: 8px 16px; font-size: 0.84rem;">
                        Batal
                    </button>
                    <button type="submit" class="btn btn-primary" style="padding: 8px 18px; font-size: 0.84rem; font-weight: 600;">
                        <i class="fas fa-print me-1"></i> Buka Lembar Cetak
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/wali-kelas-presensi.js') }}?v={{ file_exists(public_path('js/wali-kelas-presensi.js')) ? filemtime(public_path('js/wali-kelas-presensi.js')) : time() }}"></script>
@endpush
