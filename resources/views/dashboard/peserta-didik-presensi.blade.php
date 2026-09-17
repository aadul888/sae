@extends('layouts.dashboard')

@section('title', 'Riwayat Presensi Harian — SAE')
@section('dash_title', 'Riwayat Presensi Harian')

@section('content')
    <!-- 1. Header Banner & Actions Baku SAE -->
    <div class="dash-banner" style="display: flex; align-items: center; justify-content: space-between; gap: 14px; flex-wrap: wrap; margin-bottom: 20px;">
        <div style="display: flex; align-items: center; gap: 12px;">
            <div style="width: 44px; height: 44px; border-radius: 12px; background: rgba(99,102,241,0.12); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 1.25rem; flex-shrink: 0;">
                <i class="fas fa-calendar-check"></i>
            </div>
            <div>
                <h2 style="font-size: 1.25rem; font-weight: 800; color: var(--text-color); margin: 0 0 2px 0;">
                    Riwayat Presensi Harian
                </h2>
                <p style="color: var(--text-muted); font-size: 0.82rem; margin: 0;">
                    Catatan kehadiran, ketepatan waktu, bukti snapshot terminal, dan cetak laporan resmi.
                </p>
            </div>
        </div>

        <div class="dash-banner-actions" style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
            <a href="{{ route('dashboard.peserta-didik.presensi.cetak', request()->all()) }}" target="_blank" class="btn btn-outline btn-responsive-icon"
                title="Cetak Laporan Rekapitulasi Presensi"
                style="padding: 8px 14px; font-size: 0.82rem; border-radius: 8px; text-decoration: none; border-color: var(--border-color); color: var(--text-color);">
                <i class="fas fa-print text-primary"></i>
                <span class="btn-responsive-text">Cetak Laporan</span>
            </a>

            @if ($canCreate)
                <a href="{{ route('dashboard.peserta-didik.izin.index') }}" class="btn btn-primary btn-responsive-icon"
                    title="Buka Modul Surat Izin & Sakit"
                    style="padding: 8px 14px; font-size: 0.82rem; border-radius: 8px; font-weight: 600;">
                    <i class="fas fa-file-signature"></i>
                    <span class="btn-responsive-text">Ajukan Surat Izin</span>
                </a>
            @endif
        </div>
    </div>

    <!-- 2. Biodata Siswa & QR Code Dinamis (.dash-grid-2 Responsif Otomatis) -->
    <div class="dash-grid-2" style="margin-bottom: 20px;">
        <!-- Profil Siswa Card -->
        <div class="card" style="margin-bottom: 0; padding: 16px 20px; display: flex; align-items: center; gap: 16px;">
            <div style="position: relative; flex-shrink: 0;">
                @if (!empty($siswa->foto_url))
                    <img src="{{ $siswa->foto_url }}" alt="{{ $siswa->nama }}"
                        style="width: 68px; height: 68px; border-radius: 14px; object-fit: cover; border: 2px solid var(--primary); box-shadow: 0 4px 14px var(--primary-glow);"
                        onerror="this.src='/img/logo-dark.png';">
                @else
                    <div style="width: 68px; height: 68px; border-radius: 14px; background: rgba(99,102,241,0.1); border: 2px dashed var(--primary); display: flex; align-items: center; justify-content: center; color: var(--primary); font-size: 1.5rem;">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                @endif
            </div>
            <div style="min-width: 0; flex: 1;">
                <div style="font-size: 1.15rem; font-weight: 800; color: var(--text-color); margin-bottom: 2px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                    {{ $siswa->nama }}
                </div>
                <div style="font-size: 0.84rem; font-weight: 700; color: var(--primary); margin-bottom: 4px;">
                    {{ $siswa->nama_rombel ?: 'Rombel Belum Ditentukan' }} &bull; {{ $siswa->jurusan_id_str ?: 'Umum' }}
                </div>
                <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                    <span class="badge badge-outline" style="font-family: monospace; font-size: 0.75rem;">NISN: {{ $siswa->nisn }}</span>
                    @if ($siswa->wali_nama)
                        <span style="font-size: 0.76rem; color: var(--text-muted);"><i class="fas fa-chalkboard-user me-1 text-accent"></i> Wali: {{ $siswa->wali_nama }}</span>
                    @endif
                </div>
            </div>
        </div>

        <!-- Fast Scan QR Dinamis Card -->
        <div class="card" style="margin-bottom: 0; padding: 14px 18px; display: flex; align-items: center; justify-content: space-between; gap: 14px;">
            <div style="background: #ffffff; padding: 6px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.18); flex-shrink: 0;">
                <img src="{{ $dynamicQrUri }}" alt="QR Presensi" style="width: 64px; height: 64px; display: block;">
            </div>
            <div style="flex: 1; min-width: 0;">
                <div style="font-size: 0.88rem; font-weight: 800; color: var(--text-color); margin-bottom: 2px;">
                    <i class="fas fa-qrcode text-primary me-1"></i> QR Digital Presensi
                </div>
                <div style="font-size: 0.74rem; color: var(--text-muted); line-height: 1.35;">
                    Pindai di kamera terminal gerbang sekolah saat masuk dan pulang (Anti-Screenshot).
                </div>
            </div>
        </div>
    </div>

    <!-- 3. Stat Grid Baku SAE (Responsif 2 Kolom di Mobile, 4+ di Desktop) -->
    <div class="dash-stat-grid" style="margin-bottom: 20px;">
        <!-- Tepat Waktu -->
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(16,185,129,0.12); color: var(--success);">
                <i class="fas fa-calendar-check"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="color: var(--success);">{{ $stats['hadir'] }}</div>
                <div class="dash-stat-label">Tepat Waktu</div>
            </div>
        </div>

        <!-- Terlambat -->
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(245,158,11,0.12); color: var(--warning);">
                <i class="fas fa-clock"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="color: var(--warning);">{{ $stats['terlambat'] }}</div>
                <div class="dash-stat-label">Terlambat ({{ $stats['menit_terlambat'] }}m)</div>
            </div>
        </div>

        <!-- Izin & Sakit -->
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(99,102,241,0.12); color: var(--primary);">
                <i class="fas fa-file-signature"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ $stats['izin'] + $stats['sakit'] }}</div>
                <div class="dash-stat-label">Izin: {{ $stats['izin'] }} &bull; Sakit: {{ $stats['sakit'] }}</div>
            </div>
        </div>

        <!-- Alpha -->
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(239,68,68,0.12); color: var(--danger);">
                <i class="fas fa-circle-xmark"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="color: var(--danger);">{{ $stats['alpha'] }}</div>
                <div class="dash-stat-label">Alpha / Dispen: {{ $stats['dispen'] }}</div>
            </div>
        </div>

        <!-- Tingkat Kehadiran -->
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(139,92,246,0.12); color: var(--purple);">
                <i class="fas fa-percent"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="color: var(--purple);">{{ $stats['persen'] }}%</div>
                <div class="dash-stat-label">Tingkat Kehadiran</div>
            </div>
        </div>
    </div>

    <!-- 4. Card Utama: Toolbar Filter Periode & Datatable Responsif SAE -->
    <div class="card" style="padding: 16px 18px; border-radius: 14px; border: 1px solid var(--border-color); margin-bottom: 24px;">
        <!-- Filter Form & Toolbar Row -->
        <form method="GET" action="{{ route('dashboard.peserta-didik.presensi.index') }}" id="formFilterPresensi" style="margin-bottom: 14px;">
            <div style="display: flex; justify-content: space-between; align-items: center; gap: 10px; flex-wrap: wrap; margin-bottom: 12px;">
                <!-- Left: Live Search Wrap Baku SAE -->
                <div style="flex: 1; min-width: 200px;">
                    <div class="live-search-wrap" style="width: 100%;">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" name="q" id="liveSearchInput" placeholder="Cari tanggal, status, keterangan..." value="{{ $q }}" autocomplete="off">
                        <button type="button" class="clear-search" title="Hapus pencarian">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>

                <!-- Right: Segmented Filter Controls -->
                <div style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                    <!-- Dropdown Mode Periode -->
                    <select name="periode" id="filterPeriodeTipe" class="toolbar-filter-select"
                        style="height: 36px; padding: 0 8px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.8rem; font-weight: 700;">
                        <option value="bulan" {{ $periodeTipe === 'bulan' ? 'selected' : '' }}>Per Bulan</option>
                        <option value="semester" {{ $periodeTipe === 'semester' ? 'selected' : '' }}>Per Semester</option>
                        <option value="tahun" {{ $periodeTipe === 'tahun' ? 'selected' : '' }}>Per Tahun</option>
                    </select>

                    <!-- Filter Mode Bulanan -->
                    <div id="filterWrapBulan" style="display: {{ $periodeTipe === 'bulan' ? 'flex' : 'none' }}; gap: 6px;">
                        <select name="bulan" class="toolbar-filter-select" style="height: 36px; padding: 0 8px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.8rem;">
                            @php
                                $namaBulan = [
                                    '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
                                    '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
                                    '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
                                ];
                            @endphp
                            @foreach ($namaBulan as $mKey => $mLabel)
                                <option value="{{ $mKey }}" {{ str_pad((string)$bulan, 2, '0', STR_PAD_LEFT) === $mKey ? 'selected' : '' }}>
                                    {{ $mLabel }}
                                </option>
                            @endforeach
                        </select>
                        <select name="tahun" class="toolbar-filter-select" style="height: 36px; padding: 0 8px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.8rem;">
                            @for ($y = date('Y') + 1; $y >= 2024; $y--)
                                <option value="{{ $y }}" {{ (int)$tahun === $y ? 'selected' : '' }}>{{ $y }}</option>
                            @endfor
                        </select>
                    </div>

                    <!-- Filter Mode Semester -->
                    <div id="filterWrapSemester" style="display: {{ $periodeTipe === 'semester' ? 'flex' : 'none' }}; gap: 6px;">
                        <select name="semester" class="toolbar-filter-select" style="height: 36px; padding: 0 8px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.8rem;">
                            <option value="1" {{ $semester == '1' ? 'selected' : '' }}>Semester 1 (Ganjil)</option>
                            <option value="2" {{ $semester == '2' ? 'selected' : '' }}>Semester 2 (Genap)</option>
                        </select>
                    </div>

                    <!-- Filter Tahun Ajaran -->
                    <div id="filterWrapTa" style="display: {{ $periodeTipe !== 'bulan' ? 'flex' : 'none' }}; gap: 6px;">
                        <select name="tahun_ajaran" class="toolbar-filter-select" style="height: 36px; padding: 0 8px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.8rem;">
                            @for ($y = date('Y') + 1; $y >= 2024; $y--)
                                @php $ta = ($y - 1) . '/' . $y; @endphp
                                <option value="{{ $ta }}" {{ $tahunAjaran === $ta ? 'selected' : '' }}>TA {{ $ta }}</option>
                            @endfor
                        </select>
                    </div>

                    <!-- Per Page Select -->
                    <select name="per_page" id="perPageSelect" class="per-page-select" style="height: 36px; padding: 0 8px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.8rem;">
                        <option value="10" {{ $perPage == 10 ? 'selected' : '' }}>10</option>
                        <option value="15" {{ $perPage == 15 ? 'selected' : '' }}>15</option>
                        <option value="25" {{ $perPage == 25 ? 'selected' : '' }}>25</option>
                        <option value="50" {{ $perPage == 50 ? 'selected' : '' }}>50</option>
                    </select>

                    <!-- Submit Filter Button -->
                    <button type="submit" class="btn btn-primary" style="height: 36px; padding: 0 12px; font-size: 0.8rem; border-radius: 8px; font-weight: 700;">
                        <i class="fas fa-filter me-1"></i> Terapkan
                    </button>
                </div>
            </div>
        </form>

        <!-- Informasi Periode Aktif -->
        <div style="font-size: 0.78rem; color: var(--text-muted); margin-bottom: 12px; padding: 6px 12px; background: rgba(99,102,241,0.04); border-left: 3px solid var(--primary); border-radius: 4px;">
            <i class="fas fa-info-circle me-1 text-primary"></i> Menampilkan data kehadiran: <strong style="color: var(--text-color);">{{ $range['label'] }}</strong>
        </div>

        <!-- 5. Datatable Responsif Baku SAE (.table-responsive-stack) -->
        <div class="table-responsive-stack" id="tableDataContainer" style="padding: 0; margin-bottom: 0;">
            <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                <thead>
                    <tr style="background: rgba(255, 255, 255, 0.02); border-bottom: 1px solid var(--border-color);">
                        <th style="padding: 12px 14px; font-size: 0.76rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 45px; text-align: center;">No</th>
                        <th style="padding: 12px 14px; font-size: 0.76rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 110px;">Tanggal</th>
                        <th style="padding: 12px 14px; font-size: 0.76rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 90px;">Hari</th>
                        <th style="padding: 12px 14px; font-size: 0.76rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 130px;">Jam Masuk</th>
                        <th style="padding: 12px 14px; font-size: 0.76rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 130px;">Jam Pulang</th>
                        <th style="padding: 12px 14px; font-size: 0.76rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 130px;">Status</th>
                        <th style="padding: 12px 14px; font-size: 0.76rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 90px; text-align: center;">Foto Bukti</th>
                        <th style="padding: 12px 14px; font-size: 0.76rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $index => $log)
                        <tr style="border-bottom: 1px solid var(--border-color); transition: background 0.2s ease;">
                            <td style="padding: 12px 14px; text-align: center; font-size: 0.82rem; color: var(--text-muted);" data-label="No">
                                {{ ($logs->currentPage() - 1) * $logs->perPage() + $index + 1 }}
                            </td>
                            <td style="padding: 12px 14px; font-weight: 700; font-size: 0.84rem; color: var(--text-color); font-family: monospace;" data-label="Tanggal">
                                {{ \Carbon\Carbon::parse($log->tanggal)->format('d/m/Y') }}
                            </td>
                            <td style="padding: 12px 14px; font-size: 0.82rem; color: var(--text-muted);" data-label="Hari">
                                {{ \Carbon\Carbon::parse($log->tanggal)->translatedFormat('l') }}
                            </td>
                            <td style="padding: 12px 14px;" data-label="Jam Masuk">
                                @if ($log->jam_masuk)
                                    <div style="font-weight: 700; font-family: monospace; font-size: 0.84rem; color: var(--text-color);">
                                        {{ substr($log->jam_masuk, 0, 5) }} WIB
                                    </div>
                                    <div style="font-size: 0.68rem; color: var(--text-muted);">
                                        Metode: {{ strtoupper($log->metode_masuk ?: 'RFID') }}
                                    </div>
                                @else
                                    <span style="color: var(--text-muted); font-size: 0.8rem;">-</span>
                                @endif
                            </td>
                            <td style="padding: 12px 14px;" data-label="Jam Pulang">
                                @if ($log->jam_pulang)
                                    <div style="font-weight: 700; font-family: monospace; font-size: 0.84rem; color: var(--text-color);">
                                        {{ substr($log->jam_pulang, 0, 5) }} WIB
                                    </div>
                                    <div style="font-size: 0.68rem; color: var(--text-muted);">
                                        Metode: {{ strtoupper($log->metode_pulang ?: 'RFID') }}
                                    </div>
                                @else
                                    <span style="color: var(--text-muted); font-size: 0.8rem;">-</span>
                                @endif
                            </td>
                            <td style="padding: 12px 14px;" data-label="Status">
                                @if ($log->status === 'H')
                                    <span class="badge badge-success" style="font-size: 0.72rem;">
                                        <i class="fas fa-check-circle me-1"></i> Tepat Waktu
                                    </span>
                                @elseif ($log->status === 'T')
                                    <span class="badge badge-warning" style="font-size: 0.72rem;">
                                        <i class="fas fa-clock me-1"></i> Terlambat {{ $log->menit_terlambat }}m
                                    </span>
                                @elseif ($log->status === 'I')
                                    <span class="badge badge-primary" style="font-size: 0.72rem;">
                                        <i class="fas fa-file-signature me-1"></i> Izin
                                    </span>
                                @elseif ($log->status === 'S')
                                    <span class="badge" style="background: rgba(139,92,246,0.15); color: #8b5cf6; font-size: 0.72rem;">
                                        <i class="fas fa-notes-medical me-1"></i> Sakit
                                    </span>
                                @elseif ($log->status === 'D')
                                    <span class="badge badge-accent" style="font-size: 0.72rem;">
                                        <i class="fas fa-award me-1"></i> Dispen
                                    </span>
                                @else
                                    <span class="badge badge-danger" style="font-size: 0.72rem;">
                                        <i class="fas fa-circle-xmark me-1"></i> Alpha
                                    </span>
                                @endif
                            </td>
                            <td style="padding: 12px 14px; text-align: center;" data-label="Foto Bukti">
                                @php
                                    $fotoMasukUrl = !empty($log->foto_masuk) ? asset('storage/' . ltrim($log->foto_masuk, '/')) : null;
                                    $fotoPulangUrl = !empty($log->foto_pulang) ? asset('storage/' . ltrim($log->foto_pulang, '/')) : null;
                                @endphp
                                <div class="table-actions" style="justify-content: center;">
                                    @if ($fotoMasukUrl)
                                        <button type="button" class="btn-icon btn-view-snapshot"
                                            data-url="{{ $fotoMasukUrl }}"
                                            data-caption="Snapshot Masuk — {{ \Carbon\Carbon::parse($log->tanggal)->translatedFormat('d M Y') }}"
                                            title="Foto Masuk">
                                            <i class="fas fa-camera text-primary"></i>
                                        </button>
                                    @endif
                                    @if ($fotoPulangUrl)
                                        <button type="button" class="btn-icon btn-view-snapshot"
                                            data-url="{{ $fotoPulangUrl }}"
                                            data-caption="Snapshot Pulang — {{ \Carbon\Carbon::parse($log->tanggal)->translatedFormat('d M Y') }}"
                                            title="Foto Pulang">
                                            <i class="fas fa-camera text-success"></i>
                                        </button>
                                    @endif
                                    @if (!$fotoMasukUrl && !$fotoPulangUrl)
                                        <span style="color: var(--text-muted); font-size: 0.75rem;">-</span>
                                    @endif
                                </div>
                            </td>
                            <td style="padding: 12px 14px; font-size: 0.82rem; color: var(--text-muted);" data-label="Keterangan">
                                {{ $log->keterangan ?: '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 36px 16px; color: var(--text-muted);">
                                <i class="fas fa-calendar-xmark mb-2" style="font-size: 2rem; opacity: 0.4;"></i>
                                <div style="font-weight: 700; margin-top: 6px;">Belum ada rekaman data presensi</div>
                                <div style="font-size: 0.8rem;">Tidak ditemukan catatan presensi pada periode yang Anda pilih.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- 6. Custom Pagination Baku SAE (DILARANG MEMAKAI $logs->links()) -->
        @if ($logs->hasPages())
            <div style="padding-top: 16px; border-top: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; margin-top: 12px;">
                <div style="font-size: 0.78rem; color: var(--text-muted);">
                    Menampilkan {{ $logs->firstItem() ?? 0 }} - {{ $logs->lastItem() ?? 0 }} dari {{ $logs->total() }} data
                </div>
                <div class="custom-pagination">
                    @if ($logs->onFirstPage())
                        <span class="page-btn disabled"><i class="fas fa-chevron-left"></i></span>
                    @else
                        <a href="{{ $logs->previousPageUrl() }}" class="page-btn" title="Sebelumnya"><i class="fas fa-chevron-left"></i></a>
                    @endif
                    @php
                        $cur = $logs->currentPage();
                        $last = $logs->lastPage();
                        $from = max(1, $cur - 2);
                        $to = min($last, $cur + 2);
                    @endphp
                    @if ($from > 1)
                        <a href="{{ $logs->url(1) }}" class="page-btn">1</a>
                        @if ($from > 2)
                            <span class="page-info">&hellip;</span>
                        @endif
                    @endif
                    @for ($i = $from; $i <= $to; $i++)
                        <a href="{{ $logs->url($i) }}" class="page-btn {{ $i === $cur ? 'current' : '' }}">{{ $i }}</a>
                    @endfor
                    @if ($to < $last)
                        @if ($to < $last - 1)
                            <span class="page-info">&hellip;</span>
                        @endif
                        <a href="{{ $logs->url($last) }}" class="page-btn">{{ $last }}</a>
                    @endif
                    @if ($logs->hasMorePages())
                        <a href="{{ $logs->nextPageUrl() }}" class="page-btn" title="Selanjutnya"><i class="fas fa-chevron-right"></i></a>
                    @else
                        <span class="page-btn disabled"><i class="fas fa-chevron-right"></i></span>
                    @endif
                </div>
            </div>
        @endif
    </div>

    <!-- 7. Modal View Snapshot Kamera Baku (z-index 99999 !important) -->
    <div id="modalSnapshotSaya" class="modal-backdrop" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.65); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
        <div class="card" style="max-width: 440px; width: 92%; margin: auto; padding: 20px; border-radius: 16px; text-align: center; border: 1px solid var(--border-color); box-shadow: 0 16px 40px rgba(0,0,0,0.5);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; border-bottom: 1px solid var(--border-color); padding-bottom: 8px;">
                <h4 id="snapshotSayaCaption" style="font-size: 0.95rem; font-weight: 800; color: var(--text-color); margin: 0;">
                    Foto Bukti Presensi
                </h4>
                <button type="button" id="btnCloseSnapshotSaya" style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 1.2rem;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div style="margin: 10px 0;">
                <img id="imgSnapshotSaya" src="" alt="Snapshot" style="width: 100%; border-radius: 12px; object-fit: cover; max-height: 380px; border: 1px solid var(--border-color);">
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script src="{{ asset('js/peserta-didik-presensi.js') }}?v={{ file_exists(public_path('js/peserta-didik-presensi.js')) ? filemtime(public_path('js/peserta-didik-presensi.js')) : time() }}"></script>
@endpush
