@extends('layouts.dashboard')

@section('title', 'Guru Dashboard — Sistem Aplikasi Edukasi (SAE)')
@section('dash_title', 'Portal Guru & Pendidik')

@section('content')
<!-- Welcome Banner -->
<div class="dash-banner" style="background: linear-gradient(135deg, rgba(16,185,129,0.15) 0%, rgba(6,182,212,0.1) 100%);">
    <div>
        <h2 style="font-size: 1.4rem; font-weight: 800; color: var(--text-color); margin-bottom: 4px;">
            Selamat Pagi, {{ session('user.name', 'Guru') }}! 📚
        </h2>
        <p style="color: var(--text-muted); font-size: 0.88rem;">
            Mata Pelajaran: <strong>{{ session('user.mapel', 'Informatika & RPL') }}</strong> &bull; NIP: {{ session('user.nip', '197905122005011003') }}
        </p>
    </div>
    <div class="dash-banner-actions">
        <button class="btn btn-primary" style="padding: 9px 16px; font-size: 0.85rem; background: linear-gradient(135deg, #10b981, #059669);">
            <i class="fas fa-qrcode"></i> Buka Presensi Kelas
        </button>
    </div>
</div>

<!-- Stats Counter -->
<div class="dash-stat-grid">
    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(16,185,129,0.15); color: #10b981;">
            <i class="fas fa-clock"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">{{ $stats['total_jam_ajar'] }} JP</div>
            <div class="dash-stat-label">Beban Ajar Mingguan</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(99,102,241,0.15); color: var(--primary);">
            <i class="fas fa-chalkboard"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">{{ $stats['kelas_diampu'] }} Kelas</div>
            <div class="dash-stat-label">Rombel Diampu</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(6,182,212,0.15); color: var(--accent);">
            <i class="fas fa-users-viewfinder"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">{{ $stats['total_siswa'] }}</div>
            <div class="dash-stat-label">Siswa Terdaftar</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(245,158,11,0.15); color: #f59e0b;">
            <i class="fas fa-circle-check"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value" style="font-size: 1.15rem; color: #10b981;">{{ $stats['status_presensi'] }}</div>
            <div class="dash-stat-label">Masuk: {{ $stats['presensi_masuk'] }}</div>
        </div>
    </div>
</div>

<!-- Schedule Today -->
<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px;">
        <h3 style="font-size: 1.1rem; font-weight: 700; color: var(--text-color);">
            <i class="fas fa-calendar-day text-success"></i> Jadwal Mengajar Hari Ini
        </h3>
        <span style="font-size: 0.8rem; color: var(--text-muted);"><i class="fas fa-calendar"></i> {{ date('l, d F Y') }}</span>
    </div>

    <!-- Desktop Table View -->
    <div class="d-none d-md-block" style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.88rem;">
            <thead>
                <tr style="border-bottom: 1px solid var(--border-color); color: var(--text-muted);">
                    <th style="padding: 12px 14px;">Waktu</th>
                    <th style="padding: 12px 14px;">Rombel / Kelas</th>
                    <th style="padding: 12px 14px;">Materi / Mapel</th>
                    <th style="padding: 12px 14px;">Ruangan</th>
                    <th style="padding: 12px 14px;">Status</th>
                    <th style="padding: 12px 14px; text-align: right;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($jadwal_hari_ini as $j)
                    <tr style="border-bottom: 1px solid rgba(255,255,255,0.04);">
                        <td style="padding: 14px; font-weight: 700; color: var(--text-color); white-space: nowrap;">{{ $j['jam'] }}</td>
                        <td style="padding: 14px;"><span style="background: rgba(99,102,241,0.1); color: var(--primary); padding: 4px 10px; border-radius: 6px; font-weight: 600;">{{ $j['kelas'] }}</span></td>
                        <td style="padding: 14px; color: var(--text-color);">{{ $j['mapel'] }}</td>
                        <td style="padding: 14px; color: var(--text-muted);"><i class="fas fa-location-dot"></i> {{ $j['ruang'] }}</td>
                        <td style="padding: 14px;">
                            @if($j['status'] === 'Berlangsung')
                                <span style="background: rgba(16,185,129,0.15); color: #10b981; padding: 4px 10px; border-radius: 6px; font-weight: 600; font-size: 0.78rem;">
                                    <i class="fas fa-spinner fa-spin"></i> {{ $j['status'] }}
                                </span>
                            @else
                                <span style="background: rgba(255,255,255,0.05); color: var(--text-muted); padding: 4px 10px; border-radius: 6px; font-size: 0.78rem;">
                                    {{ $j['status'] }}
                                </span>
                            @endif
                        </td>
                        <td style="padding: 14px; text-align: right;">
                            <button class="btn btn-outline" style="padding: 6px 12px; font-size: 0.78rem;">
                                <i class="fas fa-pen-to-square"></i> Jurnal KBM
                            </button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Mobile Card List View -->
    <div class="d-md-none" style="display: flex; flex-direction: column; gap: 12px;">
        @foreach($jadwal_hari_ini as $j)
            <div style="background: rgba(255,255,255,0.02); border: 1px solid var(--border-color); border-radius: 12px; padding: 14px; display: flex; flex-direction: column; gap: 10px;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-weight: 700; color: var(--text-color); font-size: 0.88rem;">
                        <i class="fas fa-clock text-primary"></i> {{ $j['jam'] }}
                    </span>
                    @if($j['status'] === 'Berlangsung')
                        <span style="background: rgba(16,185,129,0.15); color: #10b981; padding: 3px 8px; border-radius: 6px; font-weight: 600; font-size: 0.72rem;">
                            <i class="fas fa-spinner fa-spin"></i> {{ $j['status'] }}
                        </span>
                    @else
                        <span style="background: rgba(255,255,255,0.05); color: var(--text-muted); padding: 3px 8px; border-radius: 6px; font-size: 0.72rem;">
                            {{ $j['status'] }}
                        </span>
                    @endif
                </div>

                <div>
                    <div style="font-weight: 700; color: var(--text-color); font-size: 0.95rem;">
                        {{ $j['mapel'] }}
                    </div>
                    <div style="display: flex; align-items: center; gap: 10px; margin-top: 4px; font-size: 0.78rem; color: var(--text-muted);">
                        <span style="background: rgba(99,102,241,0.1); color: var(--primary); padding: 2px 6px; border-radius: 4px; font-weight: 600;">{{ $j['kelas'] }}</span>
                        <span><i class="fas fa-location-dot"></i> {{ $j['ruang'] }}</span>
                    </div>
                </div>

                <div style="margin-top: 2px;">
                    <button class="btn btn-outline" style="width: 100%; justify-content: center; padding: 8px 12px; font-size: 0.8rem;">
                        <i class="fas fa-pen-to-square"></i> Jurnal KBM
                    </button>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection
