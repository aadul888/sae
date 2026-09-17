@extends('layouts.dashboard')

@section('title', 'Presensi Kelas Binaan — Wali Kelas — SAE')
@section('dash_title', 'Presensi Kelas Binaan')

@section('content')
    <!-- Header Banner -->
    <div class="dash-banner" style="margin-bottom: 20px;">
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
                <button type="button" id="btnOpenModalPdf" class="btn btn-outline btn-responsive-icon" style="padding: 9px 16px; font-size: 0.85rem;" title="Cetak Laporan Presensi">
                    <i class="fas fa-print me-1"></i> <span class="btn-responsive-text">Cetak Laporan</span>
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
        <!-- Navigasi 2 Tab Baku SAE: Presensi Harian & Izin & Sakit -->
        <div class="dash-tab-nav" style="display: flex; gap: 8px; margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 2px;">
            <a href="{{ route('dashboard.wali-kelas.presensi.index', array_merge(request()->except('page_izin'), ['tab' => 'harian'])) }}"
               class="dash-tab-link {{ $activeTab === 'harian' ? 'active' : '' }}"
               style="display: inline-flex; align-items: center; gap: 8px; padding: 10px 18px; font-size: 0.88rem; font-weight: 700; border-radius: 10px 10px 0 0; text-decoration: none; transition: all 0.2s ease; color: {{ $activeTab === 'harian' ? 'var(--primary)' : 'var(--text-muted)' }}; border-bottom: 2px solid {{ $activeTab === 'harian' ? 'var(--primary)' : 'transparent' }}; background: {{ $activeTab === 'harian' ? 'rgba(99,102,241,0.08)' : 'transparent' }};">
                <i class="fas fa-calendar-day"></i> Presensi Harian
                <span class="badge badge-outline" style="font-size: 0.72rem; padding: 2px 6px;">{{ $totalSiswa }} Siswa</span>
            </a>

            <a href="{{ route('dashboard.wali-kelas.presensi.index', array_merge(request()->except('page'), ['tab' => 'izin'])) }}"
               class="dash-tab-link {{ $activeTab === 'izin' ? 'active' : '' }}"
               style="display: inline-flex; align-items: center; gap: 8px; padding: 10px 18px; font-size: 0.88rem; font-weight: 700; border-radius: 10px 10px 0 0; text-decoration: none; transition: all 0.2s ease; color: {{ $activeTab === 'izin' ? 'var(--primary)' : 'var(--text-muted)' }}; border-bottom: 2px solid {{ $activeTab === 'izin' ? 'var(--primary)' : 'transparent' }}; background: {{ $activeTab === 'izin' ? 'rgba(99,102,241,0.08)' : 'transparent' }};">
                <i class="fas fa-envelope-open-text"></i> Surat Izin &amp; Sakit
                @if ($pendingIzinCount > 0)
                    <span class="badge" style="background: rgba(245,158,11,0.18); color: #f59e0b; border: 1px solid rgba(245,158,11,0.4); font-size: 0.72rem; padding: 2px 7px; font-weight: 800; border-radius: 20px;">
                        {{ $pendingIzinCount }} Menunggu
                    </span>
                @else
                    <span class="badge badge-outline" style="font-size: 0.72rem; padding: 2px 6px;">{{ $totalIzinCount }}</span>
                @endif
            </a>
        </div>

        @if ($activeTab === 'harian')
            <!-- TAB 1: PRESENSI HARIAN KELAS BINAAN -->
            
            <!-- Banner Kalender / Status Hari -->
            @if ($statusHari['mode'] === 'libur')
                <div class="card" style="padding: 14px 18px; margin-bottom: 20px; background: rgba(239, 68, 68, 0.08); border: 1px solid rgba(239, 68, 68, 0.25); border-radius: 12px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
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
                <div class="card" style="padding: 14px 18px; margin-bottom: 20px; background: rgba(59, 130, 246, 0.08); border: 1px solid rgba(59, 130, 246, 0.25); border-radius: 12px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
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

            <!-- Summary Stats Grid Baku SAE -->
            <div class="dash-stat-grid" style="grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); margin-bottom: 20px;">
                <div class="dash-stat-card">
                    <div class="dash-stat-icon" style="background: rgba(99,102,241,0.15); color: var(--primary);">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="dash-stat-info">
                        <div class="dash-stat-value" style="font-size: 1.35rem;">
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
                        <div class="dash-stat-label">Izin Tercatat</div>
                    </div>
                </div>

                <div class="dash-stat-card">
                    <div class="dash-stat-icon" style="background: rgba(236,72,153,0.15); color: #ec4899;">
                        <i class="fas fa-notes-medical"></i>
                    </div>
                    <div class="dash-stat-info">
                        <div class="dash-stat-value" style="font-size: 1.35rem; color: #ec4899;">
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
                        <div class="dash-stat-value" style="font-size: 1.35rem; color: #ef4444;">
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
                        <div class="dash-stat-label">% Kehadiran</div>
                    </div>
                </div>
            </div>

            <!-- Toolbar & Filter Baku SAE -->
            <div class="card" style="padding: 16px; margin-bottom: 20px;">
                <div style="display: flex; flex-wrap: wrap; gap: 12px; justify-content: space-between; align-items: center;">
                    <div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
                        <div class="toolbar-entries">
                            <label for="perPageSelect" style="margin: 0;">Tampilkan</label>
                            <select id="perPageSelect" class="per-page-select">
                                @foreach ([10, 15, 25, 50, 100] as $n)
                                    <option value="{{ $n }}" {{ $perPage == $n ? 'selected' : '' }}>{{ $n }}</option>
                                @endforeach
                            </select>
                            <span>entri</span>
                        </div>

                        <!-- Filter Tanggal -->
                        <div class="toolbar-entries">
                            <label for="filterTanggal" style="margin: 0; white-space: nowrap;">
                                <i class="fas fa-calendar-day me-1"></i> Tanggal:
                            </label>
                            <input type="date" id="filterTanggal" class="form-control" value="{{ $tanggal }}" style="font-size: 0.85rem; padding: 7px 10px; border-radius: 8px;">
                        </div>

                        @if ($isAdmin && $rombelList->isNotEmpty())
                            <select id="adminRombelSelect" class="toolbar-filter-select" style="min-width: 160px;">
                                @foreach ($rombelList as $r)
                                    <option value="{{ $r->rombongan_belajar_id }}" {{ ($activeRombel?->rombongan_belajar_id === $r->rombongan_belajar_id) ? 'selected' : '' }}>
                                        {{ $r->nama }}
                                    </option>
                                @endforeach
                            </select>
                        @endif

                        <select id="filterStatus" class="toolbar-filter-select" style="min-width: 140px;">
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
                            <button type="button" id="btnResetFilter" class="btn btn-outline btn-responsive-icon" style="padding: 7px 12px; font-size: 0.8rem;" title="Reset filter">
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

            <!-- Datatable Presensi Harian Baku SAE -->
            <div class="card table-responsive-stack" id="tableDataContainer" style="padding: 0; margin-bottom: 24px;">
                <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                    <thead>
                        <tr style="background: rgba(255, 255, 255, 0.02); border-bottom: 1px solid var(--border-color);">
                            @php
                                $cols = [
                                    ['nama', 'Peserta Didik'],
                                    ['nisn', 'NISN / NIPD'],
                                    ['status', 'Status Kehadiran'],
                                    ['jam_masuk', 'Waktu Masuk & Pulang'],
                                ];
                            @endphp
                            @foreach ($cols as [$key, $label])
                                <th class="sortable-th {{ $sort === $key ? 'sorted' : '' }}"
                                    style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;"
                                    data-sort="{{ $key }}">
                                    {{ $label }}
                                    <span class="sort-icon">{!! $sort === $key ? ($sortDir === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                                </th>
                            @endforeach
                            <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Catatan / Verifikasi
                            </th>
                            <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: right;">
                                Aksi Presensi
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($list as $item)
                            <tr style="border-bottom: 1px solid var(--border-color); transition: background 0.2s ease;">
                                <!-- Kolom Peserta Didik -->
                                <td class="cell-pd-nama" style="padding: 14px 18px;" data-label="Peserta Didik">
                                    <div class="pd-main-wrapper" style="display: flex; align-items: center; gap: 12px;">
                                        @if (!empty($item->foto_path))
                                            <div class="pd-foto-thumb" style="width: 42px; height: 42px; border-radius: 10px; border: 1.5px solid var(--border-color); display: flex; align-items: center; justify-content: center; overflow: hidden; flex-shrink: 0;">
                                                <img src="{{ asset('storage/' . ltrim($item->foto_path, '/')) }}" alt="{{ $item->nama }}" style="width: 100%; height: 100%; object-fit: cover;">
                                            </div>
                                        @else
                                            <div class="pd-foto-thumb empty" style="width: 42px; height: 42px; border-radius: 10px; background: rgba(99,102,241,0.08); border: 1.5px dashed rgba(99,102,241,0.4); display: flex; align-items: center; justify-content: center; color: var(--primary); font-size: 0.95rem; flex-shrink: 0;">
                                                <i class="fas fa-user"></i>
                                            </div>
                                        @endif
                                        <div class="pd-info">
                                            <div class="pd-title-row" style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                                                <span class="pd-nama" style="font-weight: 700; color: var(--text-color);">{{ $item->nama }}</span>
                                                <span class="badge badge-outline" style="font-size: 0.7rem; padding: 1px 5px;">{{ $item->jenis_kelamin }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Kolom NISN / NIPD -->
                                <td class="cell-pd-nisn" style="padding: 14px 18px;" data-label="NISN / NIPD">
                                    <div style="font-family: monospace; font-size: 0.85rem; color: var(--text-color);">{{ $item->nisn ?: '-' }}</div>
                                    <div style="font-size: 0.74rem; color: var(--text-muted);">NIPD: {{ $item->nipd ?: '-' }}</div>
                                </td>

                                <!-- Kolom Status Kehadiran -->
                                <td class="cell-pd-status" style="padding: 14px 18px;" data-label="Status Kehadiran">
                                    @php
                                        $st = strtoupper($item->status ?? '');
                                    @endphp
                                    @if ($st === 'H')
                                        <span class="badge" style="background: rgba(16,185,129,0.15); color: #10b981; border: 1px solid rgba(16,185,129,0.3); font-weight: 700; font-size: 0.78rem; padding: 4px 10px; border-radius: 6px;">
                                            <i class="fas fa-check-circle me-1"></i> Hadir
                                        </span>
                                    @elseif ($st === 'T')
                                        <span class="badge" style="background: rgba(245,158,11,0.15); color: #f59e0b; border: 1px solid rgba(245,158,11,0.3); font-weight: 700; font-size: 0.78rem; padding: 4px 10px; border-radius: 6px;">
                                            <i class="fas fa-clock me-1"></i> Terlambat (+{{ $item->menit_terlambat }}m)
                                        </span>
                                    @elseif ($st === 'I')
                                        <span class="badge" style="background: rgba(59,130,246,0.15); color: #3b82f6; border: 1px solid rgba(59,130,246,0.3); font-weight: 700; font-size: 0.78rem; padding: 4px 10px; border-radius: 6px;">
                                            <i class="fas fa-envelope-open-text me-1"></i> Izin
                                        </span>
                                    @elseif ($st === 'S')
                                        <span class="badge" style="background: rgba(236,72,153,0.15); color: #ec4899; border: 1px solid rgba(236,72,153,0.3); font-weight: 700; font-size: 0.78rem; padding: 4px 10px; border-radius: 6px;">
                                            <i class="fas fa-notes-medical me-1"></i> Sakit
                                        </span>
                                    @elseif ($st === 'D')
                                        <span class="badge" style="background: rgba(168,85,247,0.15); color: #a855f7; border: 1px solid rgba(168,85,247,0.3); font-weight: 700; font-size: 0.78rem; padding: 4px 10px; border-radius: 6px;">
                                            <i class="fas fa-award me-1"></i> Dispensasi
                                        </span>
                                    @elseif ($st === 'A')
                                        <span class="badge" style="background: rgba(239,68,68,0.15); color: #ef4444; border: 1px solid rgba(239,68,68,0.3); font-weight: 700; font-size: 0.78rem; padding: 4px 10px; border-radius: 6px;">
                                            <i class="fas fa-circle-xmark me-1"></i> Alpha
                                        </span>
                                    @else
                                        <span class="badge badge-outline" style="font-size: 0.76rem; padding: 3px 8px; color: var(--text-muted);">
                                            Belum Absen
                                        </span>
                                    @endif
                                </td>

                                <!-- Kolom Waktu Masuk & Pulang -->
                                <td class="cell-pd-waktu" style="padding: 14px 18px;" data-label="Waktu Masuk & Pulang">
                                    <div style="font-size: 0.84rem; color: var(--text-color);">
                                        <span style="color: var(--text-muted); font-size: 0.74rem;">Masuk:</span>
                                        <strong>{{ $item->jam_masuk ? substr($item->jam_masuk, 0, 5) : '—' }}</strong>
                                        @if ($item->metode_masuk)
                                            <span style="font-size: 0.7rem; color: var(--primary);">({{ $item->metode_masuk }})</span>
                                        @endif
                                    </div>
                                    <div style="font-size: 0.84rem; color: var(--text-color); margin-top: 2px;">
                                        <span style="color: var(--text-muted); font-size: 0.74rem;">Pulang:</span>
                                        <strong>{{ $item->jam_pulang ? substr($item->jam_pulang, 0, 5) : '—' }}</strong>
                                        @if ($item->metode_pulang)
                                            <span style="font-size: 0.7rem; color: var(--accent);">({{ $item->metode_pulang }})</span>
                                        @endif
                                    </div>
                                </td>

                                <!-- Kolom Catatan / Verifikasi -->
                                <td class="cell-pd-ket" style="padding: 14px 18px;" data-label="Catatan / Verifikasi">
                                    @if ($item->keterangan)
                                        <div style="font-size: 0.82rem; color: var(--text-color); line-height: 1.35;">{{ $item->keterangan }}</div>
                                    @else
                                        <span style="color: var(--text-muted); font-size: 0.8rem;">&mdash;</span>
                                    @endif
                                    @if ($item->verified_by)
                                        <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 3px;">
                                            <i class="fas fa-user-check me-1 text-primary"></i> {{ $item->verified_by }}
                                        </div>
                                    @endif
                                </td>

                                <!-- Kolom Aksi Presensi -->
                                <td class="cell-pd-aksi" style="padding: 14px 18px; text-align: right;" data-label="Aksi Presensi">
                                    <div class="table-actions" style="justify-content: flex-end; gap: 4px;">
                                        @if ($canUpdate && !$item->is_locked)
                                            <button type="button" class="btn-action-absen btn btn-sm btn-outline"
                                                data-id="{{ $item->peserta_didik_id }}"
                                                data-nama="{{ $item->nama }}"
                                                data-action="masuk"
                                                title="Catat Hadir Manual"
                                                style="color: #10b981; border-color: rgba(16,185,129,0.3); padding: 3px 7px; font-size: 0.74rem;">
                                                H
                                            </button>
                                            <button type="button" class="btn-action-absen btn btn-sm btn-outline"
                                                data-id="{{ $item->peserta_didik_id }}"
                                                data-nama="{{ $item->nama }}"
                                                data-action="terlambat"
                                                title="Catat Terlambat Manual"
                                                style="color: #f59e0b; border-color: rgba(245,158,11,0.3); padding: 3px 7px; font-size: 0.74rem;">
                                                T
                                            </button>
                                            <button type="button" class="btn-action-absen btn btn-sm btn-outline"
                                                data-id="{{ $item->peserta_didik_id }}"
                                                data-nama="{{ $item->nama }}"
                                                data-action="izin"
                                                title="Catat Izin Manual"
                                                style="color: #3b82f6; border-color: rgba(59,130,246,0.3); padding: 3px 7px; font-size: 0.74rem;">
                                                I
                                            </button>
                                            <button type="button" class="btn-action-absen btn btn-sm btn-outline"
                                                data-id="{{ $item->peserta_didik_id }}"
                                                data-nama="{{ $item->nama }}"
                                                data-action="sakit"
                                                title="Catat Sakit Manual"
                                                style="color: #ec4899; border-color: rgba(236,72,153,0.3); padding: 3px 7px; font-size: 0.74rem;">
                                                S
                                            </button>
                                        @endif

                                        @if ($canUpdate && $item->can_pulang)
                                            <button type="button" class="btn-action-absen btn btn-sm btn-outline"
                                                data-id="{{ $item->peserta_didik_id }}"
                                                data-nama="{{ $item->nama }}"
                                                data-action="pulang"
                                                title="Catat Jam Pulang"
                                                style="color: #06b6d4; border-color: rgba(6,182,212,0.3); padding: 3px 7px; font-size: 0.74rem;">
                                                Pulang
                                            </button>
                                        @endif

                                        @if ($item->is_locked && !$item->can_pulang)
                                            <span class="badge badge-outline" style="font-size: 0.7rem; color: var(--text-muted);">
                                                <i class="fas fa-lock me-1"></i> Final
                                            </span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" style="padding: 40px 20px; text-align: center; color: var(--text-muted);">
                                    <div style="font-size: 2rem; opacity: 0.35; margin-bottom: 8px;">
                                        <i class="fas fa-folder-open"></i>
                                    </div>
                                    <div style="font-weight: 700; color: var(--text-color); margin-bottom: 4px;">Tidak Ada Data Presensi Siswa</div>
                                    <div style="font-size: 0.82rem;">Tidak ditemukan catatan peserta didik untuk filter tanggal yang dipilih.</div>
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

        @else
            <!-- TAB 2: SURAT IZIN & SAKIT KELAS BINAAN (APPROVAL & MONITORING WALI KELAS) -->

            <!-- Stat Grid 4 Kartu Baku SAE -->
            <div class="dash-stat-grid" style="grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); margin-bottom: 20px;">
                <div class="dash-stat-card">
                    <div class="dash-stat-icon" style="background: rgba(99,102,241,0.15); color: var(--primary);">
                        <i class="fas fa-folder-open"></i>
                    </div>
                    <div class="dash-stat-info">
                        <div class="dash-stat-value" style="font-size: 1.35rem;">
                            {{ number_format($izinSummary['total'], 0, ',', '.') }}
                        </div>
                        <div class="dash-stat-label">Total Pengajuan Kelas</div>
                    </div>
                </div>

                <div class="dash-stat-card">
                    <div class="dash-stat-icon" style="background: rgba(245,158,11,0.15); color: #f59e0b;">
                        <i class="fas fa-hourglass-half"></i>
                    </div>
                    <div class="dash-stat-info">
                        <div class="dash-stat-value" style="font-size: 1.35rem; color: #f59e0b;">
                            {{ number_format($izinSummary['menunggu'], 0, ',', '.') }}
                        </div>
                        <div class="dash-stat-label">Menunggu Validasi</div>
                    </div>
                </div>

                <div class="dash-stat-card">
                    <div class="dash-stat-icon" style="background: rgba(16,185,129,0.15); color: #10b981;">
                        <i class="fas fa-circle-check"></i>
                    </div>
                    <div class="dash-stat-info">
                        <div class="dash-stat-value" style="font-size: 1.35rem; color: #10b981;">
                            {{ number_format($izinSummary['disetujui'], 0, ',', '.') }}
                        </div>
                        <div class="dash-stat-label">Telah Disetujui</div>
                    </div>
                </div>

                <div class="dash-stat-card">
                    <div class="dash-stat-icon" style="background: rgba(239,68,68,0.15); color: #ef4444;">
                        <i class="fas fa-circle-xmark"></i>
                    </div>
                    <div class="dash-stat-info">
                        <div class="dash-stat-value" style="font-size: 1.35rem; color: #ef4444;">
                            {{ number_format($izinSummary['ditolak'], 0, ',', '.') }}
                        </div>
                        <div class="dash-stat-label">Permohonan Ditolak</div>
                    </div>
                </div>
            </div>

            <!-- Toolbar & Filter Izin Kelas -->
            <div class="card" style="padding: 16px; margin-bottom: 20px;">
                <form method="GET" action="{{ route('dashboard.wali-kelas.presensi.index') }}" id="formFilterIzinWali">
                    <input type="hidden" name="tab" value="izin">
                    <div style="display: flex; flex-wrap: wrap; gap: 12px; justify-content: space-between; align-items: center;">
                        <div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
                            <div class="toolbar-entries">
                                <label for="perPageSelectIzin" style="margin: 0;">Tampilkan</label>
                                <select name="perPage" id="perPageSelectIzin" class="per-page-select">
                                    @foreach ([10, 15, 25, 50] as $n)
                                        <option value="{{ $n }}" {{ $perPage == $n ? 'selected' : '' }}>{{ $n }}</option>
                                    @endforeach
                                </select>
                                <span>entri</span>
                            </div>

                            @if ($isAdmin && $rombelList->isNotEmpty())
                                <select name="rombel_id" id="adminRombelSelectIzin" class="toolbar-filter-select" style="min-width: 160px;">
                                    @foreach ($rombelList as $r)
                                        <option value="{{ $r->rombongan_belajar_id }}" {{ ($activeRombel?->rombongan_belajar_id === $r->rombongan_belajar_id) ? 'selected' : '' }}>
                                            {{ $r->nama }}
                                        </option>
                                    @endforeach
                                </select>
                            @endif

                            <select name="jenis_izin" id="filterJenisIzin" class="toolbar-filter-select">
                                <option value="">Semua Jenis</option>
                                <option value="izin" {{ $jenisIzinFilter === 'izin' ? 'selected' : '' }}>Izin</option>
                                <option value="sakit" {{ $jenisIzinFilter === 'sakit' ? 'selected' : '' }}>Sakit</option>
                                <option value="dispen" {{ $jenisIzinFilter === 'dispen' ? 'selected' : '' }}>Dispensasi</option>
                            </select>

                            <select name="status_izin" id="filterStatusIzin" class="toolbar-filter-select">
                                <option value="">Semua Status</option>
                                <option value="menunggu" {{ $statusIzinFilter === 'menunggu' ? 'selected' : '' }}>Menunggu Validasi</option>
                                <option value="disetujui" {{ $statusIzinFilter === 'disetujui' ? 'selected' : '' }}>Disetujui</option>
                                <option value="ditolak" {{ $statusIzinFilter === 'ditolak' ? 'selected' : '' }}>Ditolak</option>
                            </select>

                            @if ($qIzin || $statusIzinFilter || $jenisIzinFilter)
                                <a href="{{ route('dashboard.wali-kelas.presensi.index', ['tab' => 'izin', 'rombel_id' => request('rombel_id')]) }}"
                                    class="btn btn-outline btn-responsive-icon" style="padding: 7px 12px; font-size: 0.8rem;" title="Reset filter">
                                    <i class="fas fa-undo"></i> <span class="btn-responsive-text">Reset</span>
                                </a>
                            @endif
                        </div>

                        <!-- Live Search Box Izin -->
                        <div class="live-search-wrap">
                            <i class="fas fa-search search-icon"></i>
                            <input type="text" name="q_izin" id="liveSearchIzinInput" placeholder="Cari nama / NISN / alasan..." value="{{ $qIzin }}" autocomplete="off">
                            <button type="button" id="clearSearchIzinBtn" class="clear-search {{ $qIzin ? 'visible' : '' }}" title="Hapus pencarian">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Datatable Izin Kelas Binaan (.table-responsive-stack) -->
            <div class="card table-responsive-stack" id="tableDataContainer" style="padding: 0; margin-bottom: 24px;">
                <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                    <thead>
                        <tr style="background: rgba(255, 255, 255, 0.02); border-bottom: 1px solid var(--border-color);">
                            <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Peserta Didik
                            </th>
                            <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 130px;">
                                Jenis Surat
                            </th>
                            <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Rentang Waktu
                            </th>
                            <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Alasan &amp; Berkas
                            </th>
                            <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 150px;">
                                Status Validasi
                            </th>
                            <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 120px;">
                                Diajukan Pada
                            </th>
                            <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: right; width: 110px;">
                                Aksi Validasi
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($izinList as $iz)
                            @php
                                $sDate = \Carbon\Carbon::parse($iz->tanggal_mulai);
                                $eDate = \Carbon\Carbon::parse($iz->tanggal_selesai);
                                $durasi = $sDate->diffInDays($eDate) + 1;
                            @endphp
                            <tr style="border-bottom: 1px solid var(--border-color); transition: background 0.2s ease;">
                                <!-- Siswa -->
                                <td class="cell-pd-nama" style="padding: 14px 18px;" data-label="Peserta Didik">
                                    <div class="pd-main-wrapper" style="display: flex; align-items: center; gap: 12px;">
                                        @if (!empty($iz->foto_path))
                                            <div class="pd-foto-thumb" style="width: 40px; height: 40px; border-radius: 10px; border: 1.5px solid var(--border-color); display: flex; align-items: center; justify-content: center; overflow: hidden; flex-shrink: 0;">
                                                <img src="{{ asset('storage/' . ltrim($iz->foto_path, '/')) }}" alt="{{ $iz->siswa_nama }}" style="width: 100%; height: 100%; object-fit: cover;">
                                            </div>
                                        @else
                                            <div class="pd-foto-thumb empty" style="width: 40px; height: 40px; border-radius: 10px; background: rgba(99,102,241,0.08); border: 1.5px dashed rgba(99,102,241,0.4); display: flex; align-items: center; justify-content: center; color: var(--primary); font-size: 0.9rem; flex-shrink: 0;">
                                                <i class="fas fa-user"></i>
                                            </div>
                                        @endif
                                        <div class="pd-info">
                                            <div class="pd-title-row" style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                                                <span class="pd-nama" style="font-weight: 700; color: var(--text-color);">{{ $iz->siswa_nama }}</span>
                                            </div>
                                            <div style="font-size: 0.74rem; color: var(--text-muted); font-family: monospace;">
                                                NISN: {{ $iz->siswa_nisn ?: '-' }}
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Jenis Surat -->
                                <td class="cell-pd-jenis" style="padding: 14px 18px;" data-label="Jenis Surat">
                                    @if ($iz->jenis === 'sakit')
                                        <span class="badge" style="background: rgba(239,68,68,0.15); color: #ef4444; border: 1px solid rgba(239,68,68,0.3); font-weight: 700; font-size: 0.78rem; padding: 4px 10px; border-radius: 6px;">
                                            <i class="fas fa-notes-medical me-1"></i> Sakit
                                        </span>
                                    @elseif ($iz->jenis === 'dispen')
                                        <span class="badge" style="background: rgba(168,85,247,0.15); color: #a855f7; border: 1px solid rgba(168,85,247,0.3); font-weight: 700; font-size: 0.78rem; padding: 4px 10px; border-radius: 6px;">
                                            <i class="fas fa-award me-1"></i> Dispensasi
                                        </span>
                                    @else
                                        <span class="badge" style="background: rgba(59,130,246,0.15); color: #3b82f6; border: 1px solid rgba(59,130,246,0.3); font-weight: 700; font-size: 0.78rem; padding: 4px 10px; border-radius: 6px;">
                                            <i class="fas fa-file-lines me-1"></i> Izin
                                        </span>
                                    @endif
                                </td>

                                <!-- Rentang Waktu -->
                                <td class="cell-pd-tanggal" style="padding: 14px 18px;" data-label="Rentang Waktu">
                                    <div style="font-weight: 700; color: var(--text-color); font-size: 0.86rem;">
                                        {{ $sDate->translatedFormat('d M Y') }}
                                        @if ($iz->tanggal_mulai !== $iz->tanggal_selesai)
                                            <span style="color: var(--text-muted); font-weight: normal;"> s/d </span>
                                            {{ $eDate->translatedFormat('d M Y') }}
                                        @endif
                                    </div>
                                    <div style="font-size: 0.74rem; color: var(--text-muted); margin-top: 2px;">
                                        <i class="fas fa-clock text-primary me-1"></i> Durasi: <strong style="color: var(--text-color);">{{ $durasi }} hari</strong>
                                    </div>
                                </td>

                                <!-- Alasan & Berkas Lampiran -->
                                <td class="cell-pd-alasan" style="padding: 14px 18px;" data-label="Alasan & Berkas">
                                    <div style="font-size: 0.84rem; color: var(--text-color); font-weight: 500; line-height: 1.4; margin-bottom: 4px;">
                                        {{ $iz->alasan }}
                                    </div>
                                    @if (!empty($iz->lampiran_path))
                                        <div>
                                            <button type="button" class="btn btn-outline btn-sm btn-preview-lampiran-wali"
                                                data-url="{{ asset('storage/' . ltrim($iz->lampiran_path, '/')) }}"
                                                data-title="Lampiran Surat {{ ucfirst($iz->jenis) }} — {{ $iz->siswa_nama }}"
                                                style="font-size: 0.74rem; padding: 3px 8px; border-radius: 6px;">
                                                <i class="fas fa-paperclip me-1 text-primary"></i> Lihat Lampiran Surat
                                            </button>
                                        </div>
                                    @else
                                        <span style="font-size: 0.73rem; color: var(--text-muted); font-style: italic;">
                                            Tanpa lampiran berkas
                                        </span>
                                    @endif
                                </td>

                                <!-- Status Validasi -->
                                <td class="cell-pd-status" style="padding: 14px 18px;" data-label="Status Validasi">
                                    @if ($iz->status === 'disetujui')
                                        <span class="badge" style="background: rgba(16,185,129,0.15); color: #10b981; border: 1px solid rgba(16,185,129,0.3); font-weight: 700; font-size: 0.78rem; padding: 4px 8px; border-radius: 6px;">
                                            <i class="fas fa-check-circle me-1"></i> Disetujui
                                        </span>
                                        @if ($iz->disetujui_oleh)
                                            <div style="font-size: 0.71rem; color: var(--text-muted); margin-top: 3px;">
                                                Oleh: {{ $iz->disetujui_oleh }}
                                            </div>
                                        @endif
                                    @elseif ($iz->status === 'ditolak')
                                        <span class="badge" style="background: rgba(239,68,68,0.15); color: #ef4444; border: 1px solid rgba(239,68,68,0.3); font-weight: 700; font-size: 0.78rem; padding: 4px 8px; border-radius: 6px;">
                                            <i class="fas fa-times-circle me-1"></i> Ditolak
                                        </span>
                                        @if ($iz->catatan_petugas)
                                            <div style="font-size: 0.71rem; color: #ef4444; margin-top: 3px;">
                                                Catatan: {{ $iz->catatan_petugas }}
                                            </div>
                                        @endif
                                    @else
                                        <span class="badge" style="background: rgba(245,158,11,0.15); color: #f59e0b; border: 1px solid rgba(245,158,11,0.3); font-weight: 700; font-size: 0.78rem; padding: 4px 8px; border-radius: 6px;">
                                            <i class="fas fa-hourglass-half me-1"></i> Menunggu
                                        </span>
                                    @endif
                                </td>

                                <!-- Diajukan Pada -->
                                <td class="cell-pd-created" style="padding: 14px 18px; font-size: 0.8rem; color: var(--text-muted);" data-label="Diajukan Pada">
                                    {{ \Carbon\Carbon::parse($iz->created_at)->translatedFormat('d/m/Y') }}
                                    <div style="font-size: 0.71rem;">{{ \Carbon\Carbon::parse($iz->created_at)->format('H:i') }} WIB</div>
                                </td>

                                <!-- Aksi Verifikasi / Approval -->
                                <td class="cell-pd-aksi" style="padding: 14px 18px; text-align: right;" data-label="Aksi Validasi">
                                    <div class="table-actions" style="justify-content: flex-end;">
                                        @if ($canUpdate)
                                            <button type="button" class="btn btn-sm btn-primary btn-open-verifikasi"
                                                data-id="{{ $iz->id }}"
                                                data-nama="{{ $iz->siswa_nama }}"
                                                data-jenis="{{ ucfirst($iz->jenis) }}"
                                                data-status="{{ $iz->status }}"
                                                data-catatan="{{ $iz->catatan_petugas }}"
                                                data-rentang="{{ $sDate->format('d/m/Y') }} - {{ $eDate->format('d/m/Y') }}"
                                                title="Verifikasi Surat Izin"
                                                style="padding: 5px 10px; font-size: 0.76rem; border-radius: 6px; font-weight: 600;">
                                                <i class="fas fa-signature me-1"></i> Verifikasi
                                            </button>
                                        @else
                                            <span style="color: var(--text-muted); font-size: 0.8rem;">&mdash;</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" style="padding: 40px 20px; text-align: center; color: var(--text-muted);">
                                    <div style="font-size: 2.2rem; opacity: 0.35; margin-bottom: 8px;">
                                        <i class="fas fa-envelope-open-text"></i>
                                    </div>
                                    <div style="font-weight: 700; color: var(--text-color); margin-bottom: 4px;">Belum Ada Permohonan Surat Izin / Sakit</div>
                                    <div style="font-size: 0.82rem;">Tidak ada permohonan surat izin dari siswa kelas binaan untuk filter yang dipilih.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Custom Pagination Izin Baku SAE -->
            @if ($izinList->hasPages())
                <div class="custom-pagination" style="margin-bottom: 24px;">
                    @if ($izinList->onFirstPage())
                        <span class="page-btn disabled"><i class="fas fa-chevron-left"></i></span>
                    @else
                        <a href="{{ $izinList->previousPageUrl() }}" class="page-btn" title="Sebelumnya"><i class="fas fa-chevron-left"></i></a>
                    @endif
                    @php
                        $curIzin = $izinList->currentPage();
                        $lastIzin = $izinList->lastPage();
                        $fromIzin = max(1, $curIzin - 2);
                        $toIzin = min($lastIzin, $curIzin + 2);
                    @endphp
                    @if ($fromIzin > 1)
                        <a href="{{ $izinList->url(1) }}" class="page-btn">1</a>
                        @if ($fromIzin > 2) <span class="page-info">&hellip;</span> @endif
                    @endif
                    @for ($i = $fromIzin; $i <= $toIzin; $i++)
                        <a href="{{ $izinList->url($i) }}" class="page-btn {{ $i === $curIzin ? 'current' : '' }}">{{ $i }}</a>
                    @endfor
                    @if ($toIzin < $lastIzin)
                        @if ($toIzin < $lastIzin - 1) <span class="page-info">&hellip;</span> @endif
                        <a href="{{ $izinList->url($lastIzin) }}" class="page-btn">{{ $lastIzin }}</a>
                    @endif
                    @if ($izinList->hasMorePages())
                        <a href="{{ $izinList->nextPageUrl() }}" class="page-btn" title="Selanjutnya"><i class="fas fa-chevron-right"></i></a>
                    @else
                        <span class="page-btn disabled"><i class="fas fa-chevron-right"></i></span>
                    @endif
                </div>
            @endif

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

                <!-- 1. Filter Peserta Didik Dropdown -->
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

                <!-- 2. Filter Tanggal -->
                <div id="groupFilterTanggal" style="margin-bottom: 14px; display: none;">
                    <label style="display: block; font-size: 0.82rem; font-weight: 600; color: var(--text-color); margin-bottom: 6px;">
                        Tanggal Presensi
                    </label>
                    <input type="date" name="tanggal" value="{{ $tanggal }}" class="form-control" style="width: 100%; font-size: 0.88rem; padding: 8px 12px; border-radius: 8px;">
                </div>

                <!-- 3. Filter Bulan -->
                <div id="groupFilterBulan" style="margin-bottom: 14px;">
                    <label style="display: block; font-size: 0.82rem; font-weight: 600; color: var(--text-color); margin-bottom: 6px;">
                        Bulan &amp; Tahun
                    </label>
                    <input type="month" name="bulan" value="{{ date('Y-m') }}" class="form-control" style="width: 100%; font-size: 0.88rem; padding: 8px 12px; border-radius: 8px;">
                </div>

                <!-- 4. Filter Semester -->
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

                <!-- 5. Filter Tahun Ajaran -->
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

    <!-- Modal Verifikasi / Approval Izin & Sakit (z-index 99999 !important) -->
    <div id="modalVerifikasiIzin" class="modal-backdrop" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.68); backdrop-filter: blur(4px); z-index: 99999 !important; align-items: center; justify-content: center;">
        <div class="card" style="width: 100%; max-width: 480px; margin: 16px; padding: 22px; border-radius: 16px; box-shadow: 0 20px 40px rgba(0,0,0,0.5); border: 1px solid var(--border-color); background: var(--bg-card);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">
                <h3 style="font-size: 1.1rem; font-weight: 700; color: var(--text-color); margin: 0; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-signature text-primary"></i> Verifikasi Surat Izin / Sakit
                </h3>
                <button type="button" id="btnCloseModalVerifikasi" class="btn-icon" style="background: none; border: none; font-size: 1.2rem; color: var(--text-muted); cursor: pointer;">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form id="formSubmitVerifikasiIzin">
                <input type="hidden" id="verifIzinId">

                <div style="background: rgba(255,255,255,0.03); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 14px; margin-bottom: 16px;">
                    <div style="font-size: 0.76rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">Peserta Didik</div>
                    <div id="verifSiswaNama" style="font-size: 1rem; font-weight: 800; color: var(--text-color); margin-top: 2px;">-</div>
                    <div style="display: flex; gap: 12px; margin-top: 6px; font-size: 0.8rem; color: var(--text-muted);">
                        <span>Jenis: <strong id="verifJenisSurat" style="color: var(--primary);">-</strong></span>
                        <span>Periode: <strong id="verifRentangTgl" style="color: var(--text-color);">-</strong></span>
                    </div>
                </div>

                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-color); margin-bottom: 8px;">
                        Keputusan Wali Kelas: <span style="color: #ef4444;">*</span>
                    </label>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                        <label style="display: flex; align-items: center; gap: 8px; padding: 10px 14px; border-radius: 10px; border: 1.5px solid rgba(16,185,129,0.3); background: rgba(16,185,129,0.08); cursor: pointer;">
                            <input type="radio" name="verif_status" value="disetujui" checked style="accent-color: #10b981;">
                            <span style="font-weight: 700; color: #10b981; font-size: 0.85rem;"><i class="fas fa-check-circle me-1"></i> Setujui</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px; padding: 10px 14px; border-radius: 10px; border: 1.5px solid rgba(239,68,68,0.3); background: rgba(239,68,68,0.08); cursor: pointer;">
                            <input type="radio" name="verif_status" value="ditolak" style="accent-color: #ef4444;">
                            <span style="font-weight: 700; color: #ef4444; font-size: 0.85rem;"><i class="fas fa-times-circle me-1"></i> Tolak</span>
                        </label>
                    </div>
                </div>

                <div style="margin-bottom: 18px;">
                    <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-color); margin-bottom: 6px;">
                        Catatan Petugas / Alasan (Opsional):
                    </label>
                    <textarea id="verifCatatan" rows="3" class="form-control" placeholder="Tuliskan catatan verifikasi atau alasan jika permohonan ditolak..."
                        style="width: 100%; font-size: 0.84rem; padding: 9px 12px; border-radius: 8px;"></textarea>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 8px;">
                    <button type="button" id="btnCancelVerifikasi" class="btn btn-outline" style="padding: 8px 16px; font-size: 0.84rem;">
                        Batal
                    </button>
                    <button type="submit" id="btnSaveVerifikasi" class="btn btn-primary" style="padding: 8px 20px; font-size: 0.84rem; font-weight: 700;">
                        <i class="fas fa-save me-1"></i> Simpan Keputusan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Pratinjau Dokumen Lampiran (z-index 99999 !important) -->
    <div id="modalPreviewLampiranWali" class="modal-backdrop" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.72); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
        <div class="card" style="max-width: 680px; width: 92%; max-height: 90vh; margin: auto; padding: 20px; border-radius: 16px; display: flex; flex-direction: column; border: 1px solid var(--border-color); box-shadow: 0 16px 40px rgba(0,0,0,0.5);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; border-bottom: 1px solid var(--border-color); padding-bottom: 8px;">
                <h4 id="previewLampiranWaliTitle" style="font-size: 0.98rem; font-weight: 800; color: var(--text-color); margin: 0;">
                    Berkas Lampiran
                </h4>
                <button type="button" id="btnClosePreviewLampiranWali" style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 1.25rem;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div id="previewContainerWali" style="flex: 1; overflow: auto; text-align: center; min-height: 240px; display: flex; align-items: center; justify-content: center;">
                <!-- Konten Gambar / PDF dinamis -->
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/wali-kelas-presensi.js') }}?v={{ file_exists(public_path('js/wali-kelas-presensi.js')) ? filemtime(public_path('js/wali-kelas-presensi.js')) : time() }}"></script>
@endpush
