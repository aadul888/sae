@extends('layouts.dashboard')

@section('title', 'Siswa Dashboard — Sistem Aplikasi Edukasi (SAE)')
@section('dash_title', 'Portal Peserta Didik')

@section('content')
<!-- Welcome Banner -->
<div class="dash-banner" style="background: linear-gradient(135deg, rgba(6,182,212,0.15) 0%, rgba(99,102,241,0.1) 100%);">
    <div>
        <h2 style="font-size: 1.4rem; font-weight: 800; color: var(--text-color); margin-bottom: 4px;">
            Halo, {{ session('user.name', 'Siswa') }}! 🎓
        </h2>
        <p style="color: var(--text-muted); font-size: 0.88rem;">
            NISN: <strong>{{ session('user.nisn', '0071234567') }}</strong> &bull; Kelas: <strong>{{ session('user.kelas', 'XII RPL 1') }}</strong> &bull; Status: <span class="text-success font-bold"><i class="fas fa-circle-check"></i> Aktif</span>
        </p>
    </div>
    <div class="dash-banner-actions">
        <button class="btn btn-outline" style="padding: 9px 16px; font-size: 0.85rem;">
            <i class="fas fa-qrcode"></i> Kartu Digital (QR)
        </button>
    </div>
</div>

<!-- Stats Counter -->
<div class="dash-stat-grid">
    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(6,182,212,0.15); color: var(--accent);">
            <i class="fas fa-calendar-check"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">{{ $stats['presensi_bulan_ini'] }}%</div>
            <div class="dash-stat-label">Tingkat Kehadiran Bulan Ini</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(16,185,129,0.15); color: #10b981;">
            <i class="fas fa-user-check"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">{{ $stats['hadir_hari'] }} Hari</div>
            <div class="dash-stat-label">Total Hadir (Izin: {{ $stats['izin_hari'] }})</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(99,102,241,0.15); color: var(--primary);">
            <i class="fas fa-star"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">{{ $stats['poin_prestasi'] }} Poin</div>
            <div class="dash-stat-label">Poin Prestasi &amp; Sikap</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(239,68,68,0.15); color: #ef4444;">
            <i class="fas fa-shield-halved"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">{{ $stats['poin_pelanggaran'] }} Poin</div>
            <div class="dash-stat-label">Pelanggaran / Disiplin</div>
        </div>
    </div>
</div>

<!-- 2 Columns: Attendance History & Schedule -->
<div class="dash-grid-2">
    <!-- Recent Attendance -->
    <div class="card" style="margin-bottom: 0;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px;">
            <h3 style="font-size: 1.05rem; font-weight: 700; color: var(--text-color);">
                <i class="fas fa-id-card-clip text-accent"></i> Riwayat Tap Presensi Terakhir
            </h3>
            <span style="font-size: 0.75rem; color: var(--text-muted);">RFID Live</span>
        </div>

        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.85rem;">
                <thead>
                    <tr style="border-bottom: 1px solid var(--border-color); color: var(--text-muted);">
                        <th style="padding: 10px;">Tanggal</th>
                        <th style="padding: 10px;">Masuk</th>
                        <th style="padding: 10px;">Pulang</th>
                        <th style="padding: 10px;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($presensi_terakhir as $p)
                        <tr style="border-bottom: 1px solid rgba(255,255,255,0.03);">
                            <td style="padding: 10px; font-weight: 600; color: var(--text-color);">{{ $p['tanggal'] }}</td>
                            <td style="padding: 10px; color: #10b981;">{{ $p['jam_masuk'] }}</td>
                            <td style="padding: 10px; color: var(--text-muted);">{{ $p['jam_pulang'] }}</td>
                            <td style="padding: 10px;">
                                <span style="background: rgba(16,185,129,0.15); color: #10b981; padding: 2px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 600;">
                                    {{ $p['status'] }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Today's Schedule -->
    <div class="card" style="margin-bottom: 0;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px;">
            <h3 style="font-size: 1.05rem; font-weight: 700; color: var(--text-color);">
                <i class="fas fa-book-bookmark text-primary"></i> Jadwal Pelajaran Hari Ini
            </h3>
            <span style="font-size: 0.75rem; color: var(--text-muted);">Kelas XII RPL 1</span>
        </div>

        <div style="display: flex; flex-direction: column; gap: 12px;">
            @foreach($jadwal_pelajaran as $jp)
                <div style="display: flex; align-items: flex-start; gap: 12px; padding: 12px; background: rgba(255,255,255,0.02); border: 1px solid var(--border-color); border-radius: 12px;">
                    <div style="min-width: 90px; font-size: 0.78rem; font-weight: 700; color: var(--accent); background: rgba(6,182,212,0.1); padding: 6px; border-radius: 8px; text-align: center;">
                        {{ $jp['jam'] }}
                    </div>
                    <div style="flex: 1;">
                        <div style="font-size: 0.88rem; font-weight: 700; color: var(--text-color);">{{ $jp['mapel'] }}</div>
                        <div style="font-size: 0.78rem; color: var(--text-muted); margin-top: 2px;">
                            <i class="fas fa-chalkboard-user"></i> {{ $jp['guru'] }} &bull; <i class="fas fa-location-dot"></i> {{ $jp['ruang'] }}
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
