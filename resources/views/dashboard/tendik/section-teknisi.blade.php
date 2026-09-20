{{-- Section Dashboard: Teknisi IT & Jaringan --}}

<!-- Quick Stats Grid Teknisi IT -->
<div class="dash-stat-grid"
    style="grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); margin-bottom: 24px; gap: 16px;">
    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(16, 185, 129, 0.15); color: #10b981;">
            <i class="fas fa-desktop"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">Kiosk Aktif</div>
            <div class="dash-stat-label">Terminal Presensi Scanner</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(59, 130, 246, 0.15); color: #3b82f6;">
            <i class="fas fa-server"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">PHP 8.3 / MySQL</div>
            <div class="dash-stat-label">Server Lokal &amp; Database</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(99, 102, 241, 0.15); color: #6366f1;">
            <i class="fas fa-users"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">{{ number_format($stats['total_pengguna'] ?? 1230, 0, ',', '.') }} Akun</div>
            <div class="dash-stat-label">Akun Pengguna Sistem</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b;">
            <i class="fas fa-network-wired"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value" style="font-size: 1.05rem;">Online (1 Gbps)</div>
            <div class="dash-stat-label">Jaringan LAN / WiFi Sekolah</div>
        </div>
    </div>
</div>

<!-- Main Content Grid Teknisi IT -->
<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-bottom: 24px;">
    <!-- Left Column: Status Layanan & Gateway Kiosk -->
    <div style="display: flex; flex-direction: column; gap: 20px;">
        <div class="card" style="padding: 0; overflow: hidden; border-radius: 14px;">
            <div
                style="padding: 16px 20px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; background: rgba(255,255,255,0.02);">
                <div style="font-weight: 700; font-size: 0.95rem; color: var(--text-color);">
                    <i class="fas fa-heart-pulse text-primary me-2"></i> Status Kesehatan Layanan &amp; Infrastruktur Digital
                </div>
                <span class="badge badge-success" style="font-size: 0.72rem; padding: 4px 8px;">
                    Semua Layanan Normal
                </span>
            </div>

            <div class="table-responsive">
                <table class="table" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                    <thead>
                        <tr style="border-bottom: 1px solid var(--border-color); background: rgba(255,255,255,0.01);">
                            <th style="padding: 10px 16px; font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted);">Nama Layanan</th>
                            <th style="padding: 10px 14px; font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted);">Protokol / Endpoint</th>
                            <th style="padding: 10px 14px; font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted);">Latensi</th>
                            <th style="padding: 10px 16px; font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); text-align: center;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 12px 16px; font-size: 0.82rem; font-weight: 600; color: var(--text-color);">
                                <i class="fas fa-barcode text-success me-1"></i> Kiosk Scanner Terminal
                            </td>
                            <td style="padding: 12px 14px; font-size: 0.8rem; font-family: monospace; color: var(--text-muted);">
                                /presensi/scan
                            </td>
                            <td style="padding: 12px 14px; font-size: 0.8rem; color: #10b981;">&lt; 15 ms</td>
                            <td style="padding: 12px 16px; text-align: center;">
                                <span class="badge badge-success" style="font-size: 0.74rem;">Running</span>
                            </td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 12px 16px; font-size: 0.82rem; font-weight: 600; color: var(--text-color);">
                                <i class="fas fa-database text-primary me-1"></i> Database MySQL (db_sae)
                            </td>
                            <td style="padding: 12px 14px; font-size: 0.8rem; font-family: monospace; color: var(--text-muted);">
                                127.0.0.1:3306
                            </td>
                            <td style="padding: 12px 14px; font-size: 0.8rem; color: #10b981;">&lt; 5 ms</td>
                            <td style="padding: 12px 16px; text-align: center;">
                                <span class="badge badge-success" style="font-size: 0.74rem;">Connected</span>
                            </td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 12px 16px; font-size: 0.82rem; font-weight: 600; color: var(--text-color);">
                                <i class="fas fa-cloud-arrow-down text-info me-1"></i> Feeder Receiver API Dapodik
                            </td>
                            <td style="padding: 12px 14px; font-size: 0.8rem; font-family: monospace; color: var(--text-muted);">
                                /api/receive-data
                            </td>
                            <td style="padding: 12px 14px; font-size: 0.8rem; color: #10b981;">Siap Sinkron</td>
                            <td style="padding: 12px 16px; text-align: center;">
                                <span class="badge badge-primary" style="font-size: 0.74rem;">Ready</span>
                            </td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 12px 16px; font-size: 0.82rem; font-weight: 600; color: var(--text-color);">
                                <i class="fas fa-id-card text-warning me-1"></i> RFID Auto-Assignment Handler
                            </td>
                            <td style="padding: 12px 14px; font-size: 0.8rem; font-family: monospace; color: var(--text-muted);">
                                /presensi/rfid/assign
                            </td>
                            <td style="padding: 12px 14px; font-size: 0.8rem; color: #10b981;">Aktif</td>
                            <td style="padding: 12px 16px; text-align: center;">
                                <span class="badge badge-success" style="font-size: 0.74rem;">Active</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Log Aktivitas Sistem & Pemeliharaan -->
        <div class="card" style="padding: 0; overflow: hidden; border-radius: 14px;">
            <div
                style="padding: 16px 20px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; background: rgba(255,255,255,0.02);">
                <div style="font-weight: 700; font-size: 0.95rem; color: var(--text-color);">
                    <i class="fas fa-clock-rotate-left text-warning me-2"></i> Log Pemeliharaan Server &amp; Jaringan
                </div>
            </div>

            <div style="padding: 18px 20px; display: flex; flex-direction: column; gap: 12px;">
                <div style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 10px; border-bottom: 1px solid rgba(255,255,255,0.05);">
                    <div>
                        <div style="font-size: 0.84rem; font-weight: 600; color: var(--text-color);">Pengecekan Gateway Kiosk Scanner Presensi</div>
                        <div style="font-size: 0.74rem; color: var(--text-muted);">Reader RFID merespon stabil dengan latensi &lt; 20ms</div>
                    </div>
                    <span class="badge badge-success" style="font-size: 0.74rem;">Normal</span>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <div style="font-size: 0.84rem; font-weight: 600; color: var(--text-color);">Pencadangan Otomatis Database (db_sae)</div>
                        <div style="font-size: 0.74rem; color: var(--text-muted);">Tersimpan aman di direktori backup sistem</div>
                    </div>
                    <span class="badge badge-primary" style="font-size: 0.74rem;">Berhasil</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column: Akses Cepat Teknisi IT -->
    <div style="display: flex; flex-direction: column; gap: 20px;">
        <div class="card" style="padding: 20px; border-radius: 14px;">
            <div style="font-weight: 700; font-size: 0.95rem; color: var(--text-color); margin-bottom: 14px; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-bolt text-warning"></i> Menu Kontrol IT &amp; Jaringan
            </div>
            <div style="display: flex; flex-direction: column; gap: 8px;">
                <a href="{{ route('presensi.scan') }}" target="_blank" class="btn btn-outline"
                    style="display: flex; justify-content: space-between; align-items: center; padding: 11px 14px; font-size: 0.82rem; border-radius: 8px; text-align: left;">
                    <span><i class="fas fa-desktop text-success me-2"></i> Buka Terminal Kiosk Scan</span>
                    <i class="fas fa-arrow-up-right-from-square text-muted" style="font-size: 0.72rem;"></i>
                </a>
                <a href="{{ route('dashboard.dapodik') }}" class="btn btn-outline"
                    style="display: flex; justify-content: space-between; align-items: center; padding: 11px 14px; font-size: 0.82rem; border-radius: 8px; text-align: left;">
                    <span><i class="fas fa-cloud-arrow-down text-primary me-2"></i> Tarik Data Dapodik</span>
                    <i class="fas fa-chevron-right text-muted" style="font-size: 0.72rem;"></i>
                </a>
                <a href="{{ route('dashboard.pengguna.index') }}" class="btn btn-outline"
                    style="display: flex; justify-content: space-between; align-items: center; padding: 11px 14px; font-size: 0.82rem; border-radius: 8px; text-align: left;">
                    <span><i class="fas fa-users-gear text-info me-2"></i> Manajemen Akun Pengguna</span>
                    <i class="fas fa-chevron-right text-muted" style="font-size: 0.72rem;"></i>
                </a>
                <a href="{{ route('dashboard.update') }}" class="btn btn-outline"
                    style="display: flex; justify-content: space-between; align-items: center; padding: 11px 14px; font-size: 0.82rem; border-radius: 8px; text-align: left;">
                    <span><i class="fas fa-arrows-rotate text-warning me-2"></i> Pembaruan Sistem</span>
                    <i class="fas fa-chevron-right text-muted" style="font-size: 0.72rem;"></i>
                </a>
            </div>
        </div>

        <div class="card" style="padding: 20px; border-radius: 14px; background: linear-gradient(135deg, rgba(99,102,241,0.08) 0%, rgba(16,185,129,0.05) 100%); border: 1px solid rgba(99,102,241,0.2);">
            <div style="font-weight: 700; font-size: 0.9rem; color: var(--text-color); margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-shield-halved text-primary"></i> Keamanan Gateway Kiosk
            </div>
            <p style="font-size: 0.82rem; color: var(--text-muted); line-height: 1.5; margin: 0 0 12px 0;">
                Terminal Kiosk diproteksi oleh kode akses administrator atau tap kartu master GTK berwenang untuk mencegah penyalahgunaan di area publik.
            </p>
            <div style="display: flex; justify-content: space-between; font-size: 0.8rem;">
                <span style="color: var(--text-muted);">Status Proteksi:</span>
                <strong style="color: #10b981;">Terkunci &amp; Aman</strong>
            </div>
        </div>
    </div>
</div>
