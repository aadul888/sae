@extends('layouts.dashboard')

@section('title', 'Tarik Data Dapodik — Sistem Aplikasi Edukasi (SAE)')
@section('dash_title', 'Tarik Data Dapodik')

@section('content')
    <!-- Welcome / Header Banner -->
    <div class="dash-banner">
        <div>
            <h2 style="font-size: 1.4rem; font-weight: 800; color: var(--text-color); margin-bottom: 4px;">
                <i class="fas fa-cloud-arrow-down text-primary me-2"></i> Tarik Data Dapodik
            </h2>
            <p style="color: var(--text-muted); font-size: 0.88rem;">
                Integrasi dan sinkronisasi data satuan pendidikan langsung dari server Dapodik Lokal via Feeder Agent.
            </p>
        </div>
        <div class="dash-banner-actions">
            <a href="#feeder-guide" class="btn btn-outline" style="padding: 9px 16px; font-size: 0.85rem;">
                <i class="fas fa-book-open"></i> Panduan Feeder
            </a>
        </div>
    </div>

    <!-- Sync Data Summary Counters -->
    <div class="dash-stat-grid">
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(99,102,241,0.15); color: var(--primary);">
                <i class="fas fa-school"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="font-size: 1.15rem; font-weight: 700;">
                    {{ $sekolah->nama ?? 'Belum Terkoneksi' }}
                </div>
                <div class="dash-stat-label">NPSN: {{ $sekolah->npsn ?? '-' }}</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(16,185,129,0.15); color: #10b981;">
                <i class="fas fa-chalkboard-user"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ number_format($totalGtk) }}</div>
                <div class="dash-stat-label">Guru &amp; Tendik Terdata</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(6,182,212,0.15); color: var(--accent);">
                <i class="fas fa-user-graduate"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ number_format($totalSiswa) }}</div>
                <div class="dash-stat-label">Peserta Didik Terdata</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(245,158,11,0.15); color: #f59e0b;">
                <i class="fas fa-users-rectangle"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ number_format($totalRombel) }}</div>
                <div class="dash-stat-label">Rombongan Belajar</div>
            </div>
        </div>
    </div>

    <!-- Main Section: Config & Bridge Info -->
    <div class="dash-grid-2">
        <!-- Feeder Bridge Endpoint Card -->
        <div class="card" style="margin-bottom: 0;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px;">
                <h3 style="font-size: 1.05rem; font-weight: 700; color: var(--text-color);">
                    <i class="fas fa-satellite-dish text-primary me-2"></i> Konfigurasi Endpoint SAE Feeder
                </h3>
                <span class="badge badge-primary">Aktif</span>
            </div>

            <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1.25rem;">
                Masukkan URL target dan API Key ini ke file konfigurasi <code
                    style="color: var(--primary);">dynamic_config.json</code> pada aplikasi <b>SAE Feeder</b> di komputer
                server Dapodik.
            </p>

            <div class="form-group mb-3">
                <label class="form-label" style="font-size: 0.8rem;"><i class="fas fa-link me-1"></i> URL Target Endpoint
                    SAE</label>
                <div style="display: flex; gap: 8px;">
                    <input type="text" class="input-control" value="{{ url('/api/receive-data') }}" readonly
                        id="targetUrlInput" style="font-family: monospace; font-size: 0.85rem;">
                    <button type="button" class="btn btn-outline" data-copy="#targetUrlInput"
                        data-copy-label="URL Target Endpoint" title="Salin URL">
                        <i class="fas fa-copy"></i>
                    </button>
                </div>
            </div>

            <div class="form-group mb-4">
                <label class="form-label" style="font-size: 0.8rem;"><i class="fas fa-key me-1"></i> API Secret Key</label>
                <div style="display: flex; gap: 8px;">
                    <input type="text" class="input-control" value="{{ $apiKey }}" readonly id="apiKeyInput"
                        style="font-family: monospace; font-size: 0.85rem;">
                    <button type="button" class="btn btn-outline" data-copy="#apiKeyInput" data-copy-label="API Secret Key"
                        title="Salin Key">
                        <i class="fas fa-copy"></i>
                    </button>
                    <form action="{{ route('dashboard.dapodik.apikey') }}" method="POST"
                        data-confirm="Apakah Anda yakin ingin memperbarui API Key? Jangan lupa perbarui konfigurasi pada SAE Feeder."
                        data-confirm-title="Perbarui API Key">
                        @csrf
                        <button type="submit" class="btn btn-outline" title="Generate Ulang Key">
                            <i class="fas fa-rotate"></i>
                        </button>
                    </form>
                </div>
            </div>

            <div
                style="background: rgba(59, 130, 246, 0.08); border: 1px solid rgba(59, 130, 246, 0.2); border-radius: 12px; padding: 12px; font-size: 0.8rem;">
                <div style="font-weight: 700; color: var(--text-color); margin-bottom: 4px;">
                    <i class="fas fa-info-circle text-primary me-1"></i> Status Penarikan Terakhir
                </div>
                <div style="color: var(--text-muted);">
                    Waktu Sinkronisasi: <b>{{ $lastSync }}</b>
                </div>
            </div>
        </div>

        <!-- Instructions & Steps Card -->
        <div class="card" id="feeder-guide" style="margin-bottom: 0;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px;">
                <h3 style="font-size: 1.05rem; font-weight: 700; color: var(--text-color);">
                    <i class="fas fa-circle-nodes text-primary me-2"></i> Langkah Tarik Data dari Dapodik
                </h3>
            </div>

            <div style="display: flex; flex-direction: column; gap: 14px; font-size: 0.85rem;">
                <div style="display: flex; gap: 12px; align-items: flex-start;">
                    <div
                        style="width: 28px; height: 28px; border-radius: 50%; background: rgba(59, 130, 246, 0.15); color: var(--primary); display: flex; align-items: center; justify-content: center; font-weight: 700; flex-shrink: 0;">
                        1</div>
                    <div>
                        <div style="font-weight: 700; color: var(--text-color);">Buka Folder SAE Feeder</div>
                        <div style="color: var(--text-muted); font-size: 0.8rem;">Jalankan <code
                                style="color: var(--primary);">run_feeder.bat</code> pada komputer yang terpasang aplikasi
                            Dapodik lokal.</div>
                    </div>
                </div>

                <div style="display: flex; gap: 12px; align-items: flex-start;">
                    <div
                        style="width: 28px; height: 28px; border-radius: 50%; background: rgba(59, 130, 246, 0.15); color: var(--primary); display: flex; align-items: center; justify-content: center; font-weight: 700; flex-shrink: 0;">
                        2</div>
                    <div>
                        <div style="font-weight: 700; color: var(--text-color);">Masukkan Pengaturan &amp; Token WebService
                        </div>
                        <div style="color: var(--text-muted); font-size: 0.8rem;">Isi NPSN, Token Dapodik WebService, serta
                            URL Endpoint &amp; API Key di atas.</div>
                    </div>
                </div>

                <div style="display: flex; gap: 12px; align-items: flex-start;">
                    <div
                        style="width: 28px; height: 28px; border-radius: 50%; background: rgba(59, 130, 246, 0.15); color: var(--primary); display: flex; align-items: center; justify-content: center; font-weight: 700; flex-shrink: 0;">
                        3</div>
                    <div>
                        <div style="font-weight: 700; color: var(--text-color);">Klik "Tarik Semua Data" di Feeder</div>
                        <div style="color: var(--text-muted); font-size: 0.8rem;">Data Sekolah, Rombel, GTK, dan Siswa akan
                            otomatis diekstrak dan dikirim langsung ke sistem SAE.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
