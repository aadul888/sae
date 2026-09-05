@extends('layouts.dashboard')

@section('title', 'Admin Dashboard — Sistem Aplikasi Edukasi (SAE)')
@section('dash_title', 'Dashboard Administrator')

@section('content')
<!-- Welcome Banner -->
<div class="dash-banner">
    <div>
        <h2 style="font-size: 1.4rem; font-weight: 800; color: var(--text-color); margin-bottom: 4px;">
            Selamat Datang, {{ session('user.name', 'Admin') }}! 👋
        </h2>
        <p style="color: var(--text-muted); font-size: 0.88rem;">
            Pusat Kendali Administrasi &amp; Manajemen Data Satuan Pendidikan Terintegrasi.
        </p>
    </div>
    <div class="dash-banner-actions">
        <button class="btn btn-outline" style="padding: 9px 16px; font-size: 0.85rem;">
            <i class="fas fa-rotate"></i> Sinkron Dapodik
        </button>
        <button class="btn btn-primary" style="padding: 9px 16px; font-size: 0.85rem;">
            <i class="fas fa-file-export"></i> Rekap Presensi
        </button>
    </div>
</div>

<!-- Stats Counter -->
<div class="dash-stat-grid">
    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(99,102,241,0.15); color: var(--primary);">
            <i class="fas fa-user-graduate"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">{{ number_format($stats['total_siswa']) }}</div>
            <div class="dash-stat-label">Total Peserta Didik</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(16,185,129,0.15); color: #10b981;">
            <i class="fas fa-chalkboard-user"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">{{ $stats['total_guru'] }}</div>
            <div class="dash-stat-label">Guru &amp; Pendidik</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(6,182,212,0.15); color: var(--accent);">
            <i class="fas fa-id-card-clip"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">{{ $stats['presensi_today'] }}%</div>
            <div class="dash-stat-label">Presensi Masuk Hari Ini</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(245,158,11,0.15); color: #f59e0b;">
            <i class="fas fa-wifi"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">{{ number_format($stats['rfid_taps']) }}</div>
            <div class="dash-stat-label">Total Tap RFID Hari Ini</div>
        </div>
    </div>
</div>

<!-- Main Section: Grid 2 Columns -->
<div class="dash-grid-2">
    <!-- Recent Activity Card -->
    <div class="card" style="margin-bottom: 0;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px;">
            <h3 style="font-size: 1.05rem; font-weight: 700; color: var(--text-color);">
                <i class="fas fa-clock-rotate-left text-primary"></i> Aktivitas Sistem Terkini
            </h3>
            <span style="font-size: 0.75rem; color: var(--text-muted);">Realtime</span>
        </div>
        
        <div style="display: flex; flex-direction: column; gap: 12px;">
            @foreach($recent_logs as $log)
                <div style="display: flex; align-items: flex-start; gap: 12px; padding: 12px; background: rgba(255,255,255,0.02); border: 1px solid var(--border-color); border-radius: 12px;">
                    <div style="width: 38px; height: 38px; border-radius: 10px; background: var(--input-bg); display: flex; align-items: center; justify-content: center; font-size: 0.85rem; font-weight: 700; color: var(--text-color);">
                        {{ $log['time'] }}
                    </div>
                    <div style="flex: 1;">
                        <div style="font-size: 0.88rem; font-weight: 600; color: var(--text-color);">{{ $log['action'] }}</div>
                        <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 2px;">Oleh: {{ $log['user'] }}</div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Quick Status & System Node -->
    <div class="card" style="margin-bottom: 0;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px;">
            <h3 style="font-size: 1.05rem; font-weight: 700; color: var(--text-color);">
                <i class="fas fa-server text-accent"></i> Status Perangkat &amp; Server
            </h3>
            <span class="badge" style="background: rgba(16,185,129,0.15); color: #10b981; border: 1px solid rgba(16,185,129,0.3); font-size: 0.7rem; padding: 3px 8px; border-radius: 6px;">Normal</span>
        </div>

        <div style="display: flex; flex-direction: column; gap: 14px;">
            <div style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 10px; border-bottom: 1px dashed var(--border-color);">
                <span style="font-size: 0.85rem; color: var(--text-muted);"><i class="fas fa-microchip text-primary"></i> Gateway RFID Gerbang 1</span>
                <span style="font-size: 0.8rem; font-weight: 700; color: #10b981;"><i class="fas fa-circle-check"></i> Terhubung (192.168.1.101)</span>
            </div>
            <div style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 10px; border-bottom: 1px dashed var(--border-color);">
                <span style="font-size: 0.85rem; color: var(--text-muted);"><i class="fas fa-microchip text-primary"></i> Gateway RFID Gerbang 2</span>
                <span style="font-size: 0.8rem; font-weight: 700; color: #10b981;"><i class="fas fa-circle-check"></i> Terhubung (192.168.1.102)</span>
            </div>
            <div style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 10px; border-bottom: 1px dashed var(--border-color);">
                <span style="font-size: 0.85rem; color: var(--text-muted);"><i class="fas fa-database text-accent"></i> Database MySQL Cluster</span>
                <span style="font-size: 0.8rem; font-weight: 700; color: #10b981;"><i class="fas fa-circle-check"></i> OK (Latency 2ms)</span>
            </div>
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span style="font-size: 0.85rem; color: var(--text-muted);"><i class="fas fa-cloud-arrow-up text-warning"></i> Sinkron Dapodik Terakhir</span>
                <span style="font-size: 0.8rem; font-weight: 700; color: var(--text-color);">{{ $stats['sync_dapodik'] }}</span>
            </div>
        </div>
    </div>
</div>
@endsection
