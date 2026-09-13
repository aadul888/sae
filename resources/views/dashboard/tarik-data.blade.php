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
                <div class="dash-stat-value">{{ number_format($totalPesertaDidik) }}</div>
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
                @if($syncAllowed)
                    <span class="badge" style="background: rgba(16,185,129,0.15); color: #10b981; border: 1px solid rgba(16,185,129,0.3);"><i class="fas fa-unlock me-1"></i> Siap Sinkron</span>
                @else
                    <span class="badge" style="background: rgba(239,68,68,0.15); color: #ef4444; border: 1px solid rgba(239,68,68,0.3);"><i class="fas fa-lock me-1"></i> Terkunci (Wajib Arsip)</span>
                @endif
            </div>

            @if(!$syncAllowed)
                <div style="background: rgba(239, 68, 68, 0.08); border: 1px solid rgba(239, 68, 68, 0.25); border-radius: 10px; padding: 12px 16px; margin-bottom: 1.25rem; font-size: 0.85rem; color: #ef4444; display: flex; align-items: center; justify-content: space-between; gap: 12px;">
                    <div>
                        <strong><i class="fas fa-triangle-exclamation me-1"></i> Proteksi Data Aktif:</strong>
                        Sinkronisasi ditolak sampai Anda mencadangkan data aktif semester ini.
                    </div>
                    <a href="{{ route('dashboard.maintenance.index') }}" class="btn btn-primary btn-sm" style="white-space: nowrap; font-size: 0.8rem; padding: 6px 14px;">
                        <i class="fas fa-file-zipper me-1"></i> Unduh Arsip Sekarang
                    </a>
                </div>
            @endif

            <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1.25rem; line-height: 1.5;">
                Salin <b>URL Target SAE</b> dan <b>API Key</b> di bawah ini, lalu tempelkan (paste) pada bagian <b>APLIKASI SAE TARGET</b> di antarmuka web <b>SAE Feeder</b> pada komputer Dapodik Anda.
            </p>

            <div class="form-group mb-3">
                <label class="form-label" style="font-size: 0.8rem;"><i class="fas fa-link me-1"></i> URL Target Endpoint SAE</label>
                <div style="display: flex; gap: 8px;">
                    <input type="text" class="input-control" value="{{ url('/api/receive-data') }}" readonly
                        id="targetUrlInput" style="font-family: monospace; font-size: 0.85rem;">
                    <button type="button" class="btn btn-outline" data-copy="#targetUrlInput"
                        data-copy-label="URL Target Endpoint" title="Salin URL">
                        <i class="fas fa-copy"></i>
                    </button>
                </div>
                <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 4px;">
                    *Bisa juga memasukkan URL domain utama saja: <code style="color: var(--primary);">{{ url('/') }}</code>
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
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                    <div style="font-weight: 700; color: var(--text-color);">
                        <i class="fas fa-info-circle text-primary me-1"></i> Status Penarikan Terakhir
                    </div>
                    <span style="font-size: 0.75rem; color: var(--text-muted);">
                        Arsip Terakhir: <b>{{ $archiveDownloadedAt ? date('d M Y, H:i', strtotime($archiveDownloadedAt)) . ' WIB' : 'Belum Pernah' }}</b>
                    </span>
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
                    <i class="fas fa-circle-nodes text-primary me-2"></i> Langkah Pengiriman Data dari Dapodik
                </h3>
            </div>

            <div style="display: flex; flex-direction: column; gap: 16px; font-size: 0.85rem;">
                <div style="display: flex; gap: 12px; align-items: flex-start;">
                    <div
                        style="width: 28px; height: 28px; border-radius: 50%; background: rgba(59, 130, 246, 0.15); color: var(--primary); display: flex; align-items: center; justify-content: center; font-weight: 700; flex-shrink: 0;">
                        1</div>
                    <div>
                        <div style="font-weight: 700; color: var(--text-color); margin-bottom: 2px;">Buka Aplikasi SAE Feeder</div>
                        <div style="color: var(--text-muted); font-size: 0.8rem; line-height: 1.4;">
                            Di komputer server yang terpasang Dapodik lokal, buka folder aplikasi SAE Feeder lalu jalankan file <code style="color: var(--primary);">run_feeder.bat</code>. Jendela antarmuka SAE Feeder akan terbuka di browser Anda.
                        </div>
                    </div>
                </div>

                <div style="display: flex; gap: 12px; align-items: flex-start;">
                    <div
                        style="width: 28px; height: 28px; border-radius: 50%; background: rgba(59, 130, 246, 0.15); color: var(--primary); display: flex; align-items: center; justify-content: center; font-weight: 700; flex-shrink: 0;">
                        2</div>
                    <div>
                        <div style="font-weight: 700; color: var(--text-color); margin-bottom: 2px;">Hubungkan Dapodik &amp; Masukkan Kunci SAE</div>
                        <div style="color: var(--text-muted); font-size: 0.8rem; line-height: 1.4;">
                            <ul style="margin: 4px 0 0 16px; padding: 0;">
                                <li>Pada <b>DAPODIK WEB SERVICE</b>: Masukkan NPSN dan Token Web Service Dapodik, lalu klik <b>Simpan</b>.</li>
                                <li>Pada <b>APLIKASI SAE TARGET</b>: Masukkan <b>URL Target SAE</b> dan <b>API Key</b> di samping ini, lalu klik <b>Simpan</b>.</li>
                            </ul>
                            <span style="font-size: 0.78rem; color: #10b981; margin-top: 4px; display: inline-block;">
                                <i class="fas fa-check-circle me-1"></i>Pastikan kedua status indikator di atas bertuliskan <b>Terhubung</b>.
                            </span>
                        </div>
                    </div>
                </div>

                <div style="display: flex; gap: 12px; align-items: flex-start;">
                    <div
                        style="width: 28px; height: 28px; border-radius: 50%; background: rgba(59, 130, 246, 0.15); color: var(--primary); display: flex; align-items: center; justify-content: center; font-weight: 700; flex-shrink: 0;">
                        3</div>
                    <div>
                        <div style="font-weight: 700; color: var(--text-color); margin-bottom: 2px;">Klik "Kirim Semua Data" di Feeder</div>
                        <div style="color: var(--text-muted); font-size: 0.8rem; line-height: 1.4;">
                            Klik tombol biru <b>"Kirim Semua Data"</b> pada SAE Feeder. Sistem akan mengekstrak data Sekolah, Rombel, GTK, dan Peserta Didik dari server Dapodik lokal lalu mengirimkannya langsung ke server SAE.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
