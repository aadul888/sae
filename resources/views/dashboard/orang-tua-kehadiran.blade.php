@extends('layouts.dashboard')

@section('title', 'Riwayat Kehadiran Siswa — Portal Orang Tua SAE')
@section('dash_title', 'Kehadiran Siswa')

@section('content')
    @php
        $studentPhoto = $pd->foto_url ?? null;
    @endphp

    <!-- 1. Header Banner Baku SAE -->
    <div class="dash-banner"
        style="margin-bottom: 24px; background: linear-gradient(135deg, rgba(16, 185, 129, 0.12) 0%, rgba(59, 130, 246, 0.08) 100%); border: 1px solid rgba(16, 185, 129, 0.25); display: flex; align-items: center; justify-content: space-between; gap: 20px; flex-wrap: wrap; border-radius: 16px; padding: 20px 24px;">
        <div style="display: flex; align-items: center; gap: 16px; flex: 1; min-width: 0;">
            <div style="flex: 1; min-width: 0;">
                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                    <a href="{{ route('dashboard.orang-tua') }}" class="btn btn-outline btn-sm" style="padding: 4px 10px; font-size: 0.74rem; border-radius: 6px;">
                        <i class="fas fa-arrow-left me-1"></i> Dashboard
                    </a>
                    <span style="font-size: 0.78rem; color: var(--text-muted);">Portal Orang Tua &bull; {{ $parentName }}</span>
                </div>
                <h2 style="font-size: 1.35rem; font-weight: 800; color: var(--text-color); margin: 0 0 6px 0; line-height: 1.25;">
                    <i class="fas fa-calendar-check text-success me-1"></i> Riwayat Kehadiran: <span style="color: #10b981;">{{ $pd->nama ?? 'Peserta Didik' }}</span>
                </h2>
                <div style="display: flex; align-items: center; gap: 12px; font-size: 0.8rem; color: var(--text-muted); flex-wrap: wrap;">
                    <span><i class="fas fa-id-card text-primary me-1"></i> NISN: <strong>{{ $pd->nisn ?? '-' }}</strong></span>
                    <span><i class="fas fa-door-open text-info me-1"></i> Kelas: <strong>{{ $pd->nama_rombel ?? 'Reguler' }}</strong></span>
                    @if ($waliKelas)
                        <span><i class="fas fa-chalkboard-user text-warning me-1"></i> Wali: <strong>{{ $waliKelas['nama'] }}</strong></span>
                    @endif
                </div>
            </div>
        </div>

        <div class="dash-banner-actions">
            <a href="{{ route('dashboard.orang-tua') }}" class="btn btn-outline" style="padding: 8px 14px; font-size: 0.85rem; border-radius: 8px; text-decoration: none;">
                <i class="fas fa-house me-1"></i> Beranda
            </a>
            <a href="{{ route('dashboard.orang-tua.izin') }}" class="btn btn-outline" style="padding: 8px 14px; font-size: 0.85rem; border-radius: 8px; text-decoration: none;">
                <i class="fas fa-ticket-alt me-1 text-warning"></i> e-Izin Keluar
            </a>
        </div>
    </div>

    <!-- 2. Quick Stats Grid Kehadiran -->
    <div class="dash-stat-grid" style="margin-bottom: 24px; gap: 14px;">
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(16, 185, 129, 0.15); color: #10b981;">
                <i class="fas fa-percent"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="color: #10b981;">{{ $statsHarian['persen'] }}%</div>
                <div class="dash-stat-label">Kehadiran Bulan Ini</div>
                <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 2px;">
                    HEB Berjalan: {{ $statsHarian['hari_efektif'] }} Hari
                </div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(59, 130, 246, 0.15); color: #3b82f6;">
                <i class="fas fa-fingerprint"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="color: #3b82f6;">{{ $statsHarian['hadir'] }} Hari</div>
                <div class="dash-stat-label">Hadir Tepat Waktu</div>
                <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 2px;">
                    Terlambat: {{ $statsHarian['terlambat'] }} Hari
                </div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b;">
                <i class="fas fa-notes-medical"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="color: #f59e0b;">{{ $statsHarian['izin'] + $statsHarian['sakit'] }} Hari</div>
                <div class="dash-stat-label">Izin &amp; Sakit Resmi</div>
                <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 2px;">
                    Dispensasi: {{ $statsHarian['dispen'] }} Hari
                </div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(239, 68, 68, 0.15); color: #ef4444;">
                <i class="fas fa-triangle-exclamation"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="color: {{ $statsHarian['alpha'] > 0 ? '#ef4444' : '#10b981' }};">
                    {{ $statsHarian['alpha'] }} Hari
                </div>
                <div class="dash-stat-label">Alpha / Tanpa Ket.</div>
                <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 2px;">
                    {{ $statsHarian['alpha'] === 0 ? 'Disiplin Sangat Baik' : 'Perlu Diperhatikan' }}
                </div>
            </div>
        </div>
    </div>

    <!-- 3. Konten Utama: Kehadiran Terpadu Siswa dengan Dropdown Selector (Point 5) -->
    <div class="card" style="padding: 20px 22px; border-radius: 16px; border: 1px solid var(--border-color); background: var(--card-bg); margin-bottom: 24px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; flex-wrap: wrap; gap: 12px; border-bottom: 1px solid var(--border-color); padding-bottom: 14px;">
            <div>
                <h3 style="font-size: 1.05rem; font-weight: 800; color: var(--text-color); margin: 0; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-list-check text-primary"></i> Data Rincian Presensi Siswa
                </h3>
                <p style="font-size: 0.78rem; color: var(--text-muted); margin: 3px 0 0 0;">
                    Pilih kategori di bawah untuk beralih antara Presensi Gerbang (RFID) dan Presensi per Mata Pelajaran.
                </p>
            </div>

            <!-- Dropdown Selector Kategori Kehadiran -->
            <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                <label for="selectKategoriKehadiran" style="font-size: 0.82rem; font-weight: 700; color: var(--text-color); margin: 0;">
                    <i class="fas fa-filter text-primary me-1"></i> Tampilkan:
                </label>
                <select id="selectKategoriKehadiran" class="toolbar-filter-select form-control" style="min-width: 260px; font-weight: 700; height: 38px; border-radius: 8px; font-size: 0.84rem;">
                    <option value="subHarian" selected>
                        Presensi Harian (RFID Gerbang) &bull; {{ count($riwayatHarian) }} Catatan
                    </option>
                    <option value="subMapel">
                        Presensi Per Mata Pelajaran &bull; {{ $statsMapel['total'] }} Sesi KBM
                    </option>
                </select>
            </div>
        </div>

        <!-- Panel A: Presensi Harian (RFID Gerbang) -->
        <div id="subPanelHarian" class="kehadiran-sub-panel">
            <div style="margin-bottom: 14px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;">
                <div style="font-weight: 700; font-size: 0.9rem; color: var(--text-color);">
                    <i class="fas fa-id-card-clip text-primary me-1"></i> Log Presensi Harian Masuk &amp; Pulang
                </div>
                <span class="badge badge-success" style="font-size: 0.7rem; padding: 4px 8px;">
                    Sinkron Real-time
                </span>
            </div>

            @if (!empty($riwayatHarian) && count($riwayatHarian) > 0)
                <div class="table-responsive-stack" style="overflow-x: auto; -webkit-overflow-scrolling: touch; margin: 0;">
                    <table class="table-minimal-compact" style="width: 100%;">
                        <thead>
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <th style="min-width: 140px;">Hari &amp; Tanggal</th>
                                <th style="min-width: 110px; text-align: center;">Jam Masuk</th>
                                <th style="min-width: 110px; text-align: center;">Jam Pulang</th>
                                <th style="min-width: 100px; text-align: center;">Metode</th>
                                <th style="min-width: 120px; text-align: center;">Status Kehadiran</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($riwayatHarian as $rh)
                                <tr style="border-bottom: 1px solid var(--border-color);">
                                    <td data-label="Hari &amp; Tanggal">
                                        <div style="font-weight: 700; color: var(--text-color); font-size: 0.84rem;">
                                            {{ $rh['tanggal'] }}
                                        </div>
                                    </td>
                                    <td data-label="Jam Masuk" style="text-align: center; font-weight: 700; color: var(--text-color); font-size: 0.84rem;">
                                        <i class="far fa-clock text-primary me-1"></i> {{ $rh['jam_masuk'] }}
                                    </td>
                                    <td data-label="Jam Pulang" style="text-align: center; font-weight: 600; color: var(--text-muted); font-size: 0.84rem;">
                                        <i class="far fa-clock text-warning me-1"></i> {{ $rh['jam_pulang'] }}
                                    </td>
                                    <td data-label="Metode" style="text-align: center;">
                                        <span class="badge" style="background: rgba(99,102,241,0.1); color: var(--primary); font-size: 0.72rem; padding: 3px 8px; border-radius: 6px;">
                                            {{ $rh['metode'] }}
                                        </span>
                                    </td>
                                    <td data-label="Status" style="text-align: center;">
                                        <span class="badge" style="background: {{ $rh['badge_bg'] }}; color: {{ $rh['badge_color'] }}; font-size: 0.74rem; padding: 4px 10px; font-weight: 700; border-radius: 6px;">
                                            {{ $rh['status'] }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div style="padding: 32px 16px; text-align: center; color: var(--text-muted); border: 1px dashed var(--border-color); border-radius: 10px;">
                    <i class="fas fa-fingerprint" style="font-size: 2rem; color: var(--text-muted); margin-bottom: 8px; display: block;"></i>
                    <div style="font-weight: 700; font-size: 0.9rem; color: var(--text-color);">Belum Ada Catatan Presensi Harian</div>
                </div>
            @endif
        </div>

        <!-- Panel B: Presensi per Mata Pelajaran (KBM) -->
        <div id="subPanelMapel" class="kehadiran-sub-panel" style="display: none;">
            <div style="margin-bottom: 14px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;">
                <div style="font-weight: 700; font-size: 0.9rem; color: var(--text-color);">
                    <i class="fas fa-book-open text-success me-1"></i> Kehadiran Siswa di Tiap Mata Pelajaran Kelas
                </div>
                <div style="display: flex; gap: 10px; font-size: 0.78rem;">
                    <span><i class="fas fa-circle-check text-success"></i> Hadir: <strong>{{ $statsMapel['hadir'] }}</strong></span>
                    <span><i class="fas fa-circle-info text-primary"></i> Izin: <strong>{{ $statsMapel['izin'] }}</strong></span>
                    <span><i class="fas fa-circle-xmark text-danger"></i> Alpha: <strong>{{ $statsMapel['alpha'] }}</strong></span>
                </div>
            </div>

            @if ($riwayatMapel->isNotEmpty())
                <div class="table-responsive-stack" style="overflow-x: auto; -webkit-overflow-scrolling: touch; margin: 0;">
                    <table class="table-minimal-compact" style="width: 100%;">
                        <thead>
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <th style="min-width: 110px;">Tanggal</th>
                                <th style="min-width: 160px;">Mata Pelajaran</th>
                                <th style="min-width: 140px;">Guru Pengampu</th>
                                <th style="width: 80px; text-align: center;">Jam Ke</th>
                                <th style="width: 100px; text-align: center;">Status</th>
                                <th style="min-width: 140px;">Catatan / Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($riwayatMapel as $pm)
                                @php
                                    $badgeStatus = match($pm->status) {
                                        'H' => ['label' => 'Hadir', 'bg' => 'rgba(16,185,129,0.15)', 'color' => '#10b981'],
                                        'T' => ['label' => 'Terlambat', 'bg' => 'rgba(245,158,11,0.15)', 'color' => '#f59e0b'],
                                        'I' => ['label' => 'Izin', 'bg' => 'rgba(59,130,246,0.15)', 'color' => '#3b82f6'],
                                        'S' => ['label' => 'Sakit', 'bg' => 'rgba(139,92,246,0.15)', 'color' => '#8b5cf6'],
                                        'D' => ['label' => 'Dispen', 'bg' => 'rgba(6,182,212,0.15)', 'color' => '#06b6d4'],
                                        'A' => ['label' => 'Alpha', 'bg' => 'rgba(239,68,68,0.15)', 'color' => '#ef4444'],
                                        default => ['label' => $pm->status, 'bg' => 'rgba(100,116,139,0.15)', 'color' => '#64748b'],
                                    };
                                @endphp
                                <tr style="border-bottom: 1px solid var(--border-color);">
                                    <td data-label="Tanggal">
                                        <div style="font-weight: 700; color: var(--text-color); font-size: 0.82rem;">
                                            {{ \Carbon\Carbon::parse($pm->tanggal)->translatedFormat('d M Y') }}
                                        </div>
                                    </td>
                                    <td data-label="Mata Pelajaran">
                                        <div style="font-weight: 700; color: var(--text-color); font-size: 0.84rem;">
                                            <i class="fas fa-book text-primary me-1"></i> {{ $pm->nama_mata_pelajaran }}
                                        </div>
                                    </td>
                                    <td data-label="Guru Pengampu">
                                        <div style="font-size: 0.8rem; color: var(--text-muted);">
                                            {{ $pm->nama_guru ?: 'Guru Pengampu' }}
                                        </div>
                                    </td>
                                    <td data-label="Jam Ke" style="text-align: center; font-size: 0.82rem; font-weight: 600;">
                                        Jam ke-{{ $pm->jam_ke }}
                                    </td>
                                    <td data-label="Status" style="text-align: center;">
                                        <span class="badge" style="background: {{ $badgeStatus['bg'] }}; color: {{ $badgeStatus['color'] }}; font-size: 0.74rem; padding: 4px 8px; font-weight: 700; border-radius: 6px;">
                                            {{ $badgeStatus['label'] }}
                                        </span>
                                    </td>
                                    <td data-label="Keterangan" style="font-size: 0.78rem; color: var(--text-muted);">
                                        {{ $pm->keterangan ?: 'Tercatat di sistem' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div style="padding: 32px 16px; text-align: center; color: var(--text-muted); border: 1px dashed var(--border-color); border-radius: 10px;">
                    <i class="fas fa-chalkboard-user" style="font-size: 2rem; color: var(--text-muted); margin-bottom: 8px; display: block;"></i>
                    <div style="font-weight: 700; font-size: 0.9rem; color: var(--text-color);">Belum Ada Riwayat Presensi Mata Pelajaran</div>
                </div>
            @endif
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const selectKehadiran = document.getElementById('selectKategoriKehadiran');
            const panelHarian = document.getElementById('subPanelHarian');
            const panelMapel = document.getElementById('subPanelMapel');

            if (selectKehadiran) {
                selectKehadiran.addEventListener('change', function () {
                    const val = this.value;
                    if (panelHarian) panelHarian.style.display = (val === 'subHarian') ? 'block' : 'none';
                    if (panelMapel) panelMapel.style.display = (val === 'subMapel') ? 'block' : 'none';
                });
            }
        });
    </script>
@endsection
