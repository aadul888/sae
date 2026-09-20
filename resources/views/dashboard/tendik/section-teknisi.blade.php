{{-- Section Dashboard: Teknisi IT & Jaringan --}}

<!-- Quick Stats Grid Teknisi IT (Responsive) -->
<div class="dash-stat-grid" style="margin-bottom: 24px; gap: 14px;">
    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(16, 185, 129, 0.15); color: #10b981;">
            <i class="fas fa-desktop"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">Kiosk Aktif</div>
            <div class="dash-stat-label">Scanner Terminal</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(59, 130, 246, 0.15); color: #3b82f6;">
            <i class="fas fa-server"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">PHP 8.3</div>
            <div class="dash-stat-label">Server &amp; MySQL</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(99, 102, 241, 0.15); color: #6366f1;">
            <i class="fas fa-users"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">{{ number_format($stats['total_pengguna'] ?? 1230, 0, ',', '.') }}</div>
            <div class="dash-stat-label">Akun Pengguna</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b;">
            <i class="fas fa-network-wired"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">1 Gbps</div>
            <div class="dash-stat-label">LAN &amp; WiFi Sekolah</div>
        </div>
    </div>
</div>

<!-- Main Content Grid Teknisi IT (Responsive: 2fr 1fr desktop, 1fr mobile) -->
<div class="dash-layout-grid">
    <!-- Left Column: Status Layanan & Gateway Kiosk -->
    <div style="display: flex; flex-direction: column; gap: 20px; min-width: 0;">
        <div class="card" style="padding: 0; overflow: hidden; border-radius: 14px; border: 1px solid var(--border-color); background: var(--card-bg);">
            <div style="padding: 14px 18px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; background: rgba(255,255,255,0.02); flex-wrap: wrap; gap: 8px;">
                <div style="font-weight: 700; font-size: 0.92rem; color: var(--text-color); display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-heart-pulse text-primary"></i> Status Layanan Digital
                </div>
                <span class="badge badge-success" style="font-size: 0.7rem; padding: 3px 8px; border-radius: 6px;">
                    Normal
                </span>
            </div>

            <div class="table-responsive-stack" style="overflow-x: auto; -webkit-overflow-scrolling: touch; margin: 0;">
                <table class="table-minimal-compact">
                    <thead>
                        <tr>
                            <th style="min-width: 130px;">Layanan</th>
                            <th style="min-width: 100px;">Endpoint</th>
                            <th style="min-width: 70px;">Latensi</th>
                            <th style="min-width: 80px; text-align: center;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <div style="font-weight: 700; color: var(--text-color); font-size: 0.84rem;">
                                    <i class="fas fa-barcode text-success me-1"></i> Kiosk Scanner
                                </div>
                            </td>
                            <td><span style="font-family: monospace; font-size: 0.74rem; color: var(--text-muted);">/presensi/scan</span></td>
                            <td><span style="color: #10b981; font-size: 0.78rem;">&lt; 15 ms</span></td>
                            <td style="text-align: center;">
                                <span class="badge-compact" style="background: rgba(16,185,129,0.12); color: #10b981;">
                                    <i class="fas fa-circle-check"></i> Run
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div style="font-weight: 700; color: var(--text-color); font-size: 0.84rem;">
                                    <i class="fas fa-database text-primary me-1"></i> MySQL (db_sae)
                                </div>
                            </td>
                            <td><span style="font-family: monospace; font-size: 0.74rem; color: var(--text-muted);">127.0.0.1:3306</span></td>
                            <td><span style="color: #10b981; font-size: 0.78rem;">&lt; 5 ms</span></td>
                            <td style="text-align: center;">
                                <span class="badge-compact" style="background: rgba(16,185,129,0.12); color: #10b981;">
                                    <i class="fas fa-circle-check"></i> Conn
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div style="font-weight: 700; color: var(--text-color); font-size: 0.84rem;">
                                    <i class="fas fa-cloud-arrow-down text-info me-1"></i> Feeder Dapodik
                                </div>
                            </td>
                            <td><span style="font-family: monospace; font-size: 0.74rem; color: var(--text-muted);">/api/receive-data</span></td>
                            <td><span style="color: #10b981; font-size: 0.78rem;">Ready</span></td>
                            <td style="text-align: center;">
                                <span class="badge-compact" style="background: rgba(59,130,246,0.12); color: #3b82f6;">
                                    <i class="fas fa-shield-halved"></i> Guard
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div style="font-weight: 700; color: var(--text-color); font-size: 0.84rem;">
                                    <i class="fas fa-wifi text-warning me-1"></i> Access Point Lab
                                </div>
                            </td>
                            <td><span style="font-family: monospace; font-size: 0.74rem; color: var(--text-muted);">192.168.1.1/24</span></td>
                            <td><span style="color: #10b981; font-size: 0.78rem;">&lt; 8 ms</span></td>
                            <td style="text-align: center;">
                                <span class="badge-compact" style="background: rgba(16,185,129,0.12); color: #10b981;">
                                    <i class="fas fa-circle-check"></i> Up
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Log Backup & Maintenance IT -->
        <div class="card" style="padding: 0; overflow: hidden; border-radius: 14px; border: 1px solid var(--border-color); background: var(--card-bg);">
            <div style="padding: 14px 18px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; background: rgba(255,255,255,0.02); flex-wrap: wrap; gap: 8px;">
                <div style="font-weight: 700; font-size: 0.92rem; color: var(--text-color); display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-clock-rotate-left text-warning"></i> Log Pemeliharaan IT
                </div>
            </div>

            <div style="padding: 14px 18px; display: flex; flex-direction: column; gap: 10px;">
                <div style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 8px; border-bottom: 1px solid rgba(255,255,255,0.05); gap: 10px;">
                    <div style="min-width: 0; flex: 1;">
                        <div style="font-size: 0.82rem; font-weight: 600; color: var(--text-color);">Backup Otomatis Basis Data db_sae</div>
                        <div style="font-size: 0.7rem; color: var(--text-muted);">Snapshot tersimpan di Laragon &bull; 03:00 WIB</div>
                    </div>
                    <span class="badge-compact" style="background: rgba(16,185,129,0.12); color: #10b981;">
                        <i class="fas fa-check"></i> Sukses
                    </span>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; gap: 10px;">
                    <div style="min-width: 0; flex: 1;">
                        <div style="font-size: 0.82rem; font-weight: 600; color: var(--text-color);">Optimasi Index Tabel Presensi &amp; Pengguna</div>
                        <div style="font-size: 0.7rem; color: var(--text-muted);">Cache optimize:clear &bull; Dijalankan hari ini</div>
                    </div>
                    <span class="badge-compact" style="background: rgba(59,130,246,0.12); color: #3b82f6;">
                        <i class="fas fa-bolt"></i> Optimal
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column: Akses Cepat Teknisi IT (Stack di Mobile) -->
    <div style="display: flex; flex-direction: column; gap: 20px; min-width: 0;">
        <div class="card" style="padding: 18px; border-radius: 14px; border: 1px solid var(--border-color); background: var(--card-bg);">
            <div style="font-weight: 700; font-size: 0.9rem; color: var(--text-color); margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-bolt text-warning"></i> Menu Teknisi IT
            </div>
            <div style="display: flex; flex-direction: column; gap: 8px;">
                <a href="{{ route('dashboard.pengguna.index') }}" class="btn btn-outline"
                    style="display: flex; justify-content: space-between; align-items: center; padding: 9px 12px; font-size: 0.8rem; border-radius: 8px; text-decoration: none;">
                    <span><i class="fas fa-users-gear text-primary me-2"></i> Manajemen Akun</span>
                    <i class="fas fa-chevron-right text-muted" style="font-size: 0.7rem;"></i>
                </a>
                <a href="{{ route('dashboard.maintenance.index') }}" class="btn btn-outline"
                    style="display: flex; justify-content: space-between; align-items: center; padding: 9px 12px; font-size: 0.8rem; border-radius: 8px; text-decoration: none;">
                    <span><i class="fas fa-server text-info me-2"></i> Backup &amp; Maintenance</span>
                    <i class="fas fa-chevron-right text-muted" style="font-size: 0.7rem;"></i>
                </a>
                <a href="{{ route('dashboard.update') }}" class="btn btn-outline"
                    style="display: flex; justify-content: space-between; align-items: center; padding: 9px 12px; font-size: 0.8rem; border-radius: 8px; text-decoration: none;">
                    <span><i class="fas fa-cloud-arrow-down text-warning me-2"></i> Rilis &amp; Update SAE</span>
                    <i class="fas fa-chevron-right text-muted" style="font-size: 0.7rem;"></i>
                </a>
            </div>
        </div>

        <div class="card" style="padding: 16px; border-radius: 14px; background: linear-gradient(135deg, rgba(99,102,241,0.06) 0%, rgba(59,130,246,0.04) 100%); border: 1px solid rgba(99,102,241,0.18);">
            <div style="font-weight: 700; font-size: 0.86rem; color: var(--text-color); margin-bottom: 10px; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-shield-halved text-primary"></i> Keamanan Jaringan
            </div>
            <p style="font-size: 0.78rem; color: var(--text-muted); line-height: 1.45; margin: 0 0 10px 0;">
                Koneksi server lokal terisolasi melalui intranet sekolah. Pastikan firewall membatasi port database hanya untuk localhost.
            </p>
            <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.78rem;">
                <span style="color: var(--text-muted);">Status Firewall:</span>
                <span class="badge badge-success" style="font-size: 0.68rem; padding: 2px 6px;">Terkunci Aman</span>
            </div>
        </div>
    </div>
</div>
