{{-- Section Dashboard: Petugas Keamanan (Satpam Sekolah) --}}

<!-- Quick Stats Grid Keamanan (Responsive) -->
<div class="dash-stat-grid" style="margin-bottom: 24px; gap: 14px;">
    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(59, 130, 246, 0.15); color: #3b82f6;">
            <i class="fas fa-address-book"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">{{ $stats['buku_tamu_hari_ini'] ?? 12 }} Tamu</div>
            <div class="dash-stat-label">Buku Tamu Hari Ini</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b;">
            <i class="fas fa-person-walking-arrow-right"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">3 Siswa</div>
            <div class="dash-stat-label">Izin Keluar Gerbang</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(16, 185, 129, 0.15); color: #10b981;">
            <i class="fas fa-id-card-clip"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">Online</div>
            <div class="dash-stat-label">Kiosk Gerbang</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(99, 102, 241, 0.15); color: var(--primary);">
            <i class="fas fa-shield-halved"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">Kondusif</div>
            <div class="dash-stat-label">Keamanan Area</div>
        </div>
    </div>
</div>

<!-- Main Content Grid Keamanan (Responsive: 2fr 1fr desktop, 1fr mobile) -->
<div class="dash-layout-grid">
    <!-- Left Column: Buku Tamu Hari Ini & Pemantauan E-Izin Gerbang -->
    <div style="display: flex; flex-direction: column; gap: 20px; min-width: 0;">
        <div class="card" style="padding: 0; overflow: hidden; border-radius: 14px; border: 1px solid var(--border-color); background: var(--card-bg);">
            <div style="padding: 14px 18px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; background: rgba(255,255,255,0.02); flex-wrap: wrap; gap: 8px;">
                <div style="font-weight: 700; font-size: 0.92rem; color: var(--text-color); display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-clipboard-user text-primary"></i> Log Tamu Hari Ini
                </div>
                <span class="badge badge-info" style="font-size: 0.7rem; padding: 3px 8px; border-radius: 6px;">
                    {{ date('d M Y') }}
                </span>
            </div>

            <div class="table-responsive-stack" style="overflow-x: auto; -webkit-overflow-scrolling: touch; margin: 0;">
                <table class="table-minimal-compact">
                    <thead>
                        <tr>
                            <th style="min-width: 130px;">Nama Tamu</th>
                            <th style="min-width: 110px;">Instansi / Asal</th>
                            <th style="min-width: 130px;">Keperluan</th>
                            <th style="min-width: 70px; text-align: center;">Jam</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <div style="font-weight: 700; color: var(--text-color); font-size: 0.84rem;">
                                    Bpk. Hendra Kusuma
                                </div>
                            </td>
                            <td>
                                <span class="badge-compact" style="background: rgba(59,130,246,0.1); color: #3b82f6;">
                                    <i class="fas fa-landmark"></i> Disdik Wilayah
                                </span>
                            </td>
                            <td>
                                <div style="font-size: 0.8rem; color: var(--text-color); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 150px;" title="Koordinasi Bantuan Operasional Sekolah">
                                    Koordinasi BOSP
                                </div>
                            </td>
                            <td style="text-align: center;">
                                <span class="badge-compact" style="background: rgba(59,130,246,0.12); color: #3b82f6;">
                                    08:15
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div style="font-weight: 700; color: var(--text-color); font-size: 0.84rem;">
                                    Ibu Siti Maryam
                                </div>
                            </td>
                            <td>
                                <span class="badge-compact" style="background: rgba(245,158,11,0.1); color: #f59e0b;">
                                    <i class="fas fa-user-group"></i> Wali X ATPH 1
                                </span>
                            </td>
                            <td>
                                <div style="font-size: 0.8rem; color: var(--text-color); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 150px;" title="Konsultasi Guru BK / Wali Kelas">
                                    Konsultasi BK
                                </div>
                            </td>
                            <td style="text-align: center;">
                                <span class="badge-compact" style="background: rgba(59,130,246,0.12); color: #3b82f6;">
                                    09:30
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div style="font-weight: 700; color: var(--text-color); font-size: 0.84rem;">
                                    Kurir Logistik
                                </div>
                            </td>
                            <td>
                                <span class="badge-compact" style="background: rgba(16,185,129,0.1); color: #10b981;">
                                    <i class="fas fa-truck"></i> PT Agro Niaga
                                </span>
                            </td>
                            <td>
                                <div style="font-size: 0.8rem; color: var(--text-color); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 150px;" title="Pengiriman Bibit Praktik">
                                    Kirim Bibit Praktik
                                </div>
                            </td>
                            <td style="text-align: center;">
                                <span class="badge-compact" style="background: rgba(16,185,129,0.12); color: #10b981;">
                                    10:45
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Monitoring Izin Keluar Gerbang -->
        <div class="card" style="padding: 0; overflow: hidden; border-radius: 14px; border: 1px solid var(--border-color); background: var(--card-bg);">
            <div style="padding: 14px 18px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; background: rgba(255,255,255,0.02); flex-wrap: wrap; gap: 8px;">
                <div style="font-weight: 700; font-size: 0.92rem; color: var(--text-color); display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-person-walking-arrow-right text-warning"></i> Validasi Izin Siswa di Gerbang
                </div>
            </div>

            <div style="padding: 14px 18px; display: flex; flex-direction: column; gap: 10px;">
                <div style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 8px; border-bottom: 1px solid rgba(255,255,255,0.05); gap: 10px;">
                    <div style="min-width: 0; flex: 1;">
                        <div style="font-size: 0.82rem; font-weight: 600; color: var(--text-color);">Rafi Pratama &bull; XI TKJ 1</div>
                        <div style="font-size: 0.7rem; color: var(--text-muted);">Dispensasi Lomba FLS2N &bull; Surat Pembina OSIS</div>
                    </div>
                    <span class="badge-compact" style="background: rgba(16,185,129,0.12); color: #10b981;">
                        <i class="fas fa-check"></i> Izin Keluar
                    </span>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; gap: 10px;">
                    <div style="min-width: 0; flex: 1;">
                        <div style="font-size: 0.82rem; font-weight: 600; color: var(--text-color);">Siti Nurhaliza &bull; X Kehutanan 2</div>
                        <div style="font-size: 0.7rem; color: var(--text-muted);">Sakit / UKS &bull; Dijemput Orang Tua</div>
                    </div>
                    <span class="badge-compact" style="background: rgba(245,158,11,0.12); color: #f59e0b;">
                        <i class="fas fa-house-medical"></i> Pulang Sakit
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column: Akses Cepat Keamanan (Stack di Mobile) -->
    <div style="display: flex; flex-direction: column; gap: 20px; min-width: 0;">
        <div class="card" style="padding: 18px; border-radius: 14px; border: 1px solid var(--border-color); background: var(--card-bg);">
            <div style="font-weight: 700; font-size: 0.9rem; color: var(--text-color); margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-bolt text-warning"></i> Menu Keamanan
            </div>
            <div style="display: flex; flex-direction: column; gap: 8px;">
                <a href="{{ route('dashboard.peserta-didik.izin.index') }}" class="btn btn-outline"
                    style="display: flex; justify-content: space-between; align-items: center; padding: 9px 12px; font-size: 0.8rem; border-radius: 8px; text-decoration: none;">
                    <span><i class="fas fa-qrcode text-primary me-2"></i> Scanner e-Izin Siswa</span>
                    <i class="fas fa-chevron-right text-muted" style="font-size: 0.7rem;"></i>
                </a>
                <a href="{{ route('dashboard.peserta-didik-aktif.index') }}" class="btn btn-outline"
                    style="display: flex; justify-content: space-between; align-items: center; padding: 9px 12px; font-size: 0.8rem; border-radius: 8px; text-decoration: none;">
                    <span><i class="fas fa-users text-info me-2"></i> Direktori Siswa</span>
                    <i class="fas fa-chevron-right text-muted" style="font-size: 0.7rem;"></i>
                </a>
                <a href="{{ route('dashboard.guru-aktif.index') }}" class="btn btn-outline"
                    style="display: flex; justify-content: space-between; align-items: center; padding: 9px 12px; font-size: 0.8rem; border-radius: 8px; text-decoration: none;">
                    <span><i class="fas fa-chalkboard-user text-warning me-2"></i> Direktori Guru &amp; TAS</span>
                    <i class="fas fa-chevron-right text-muted" style="font-size: 0.7rem;"></i>
                </a>
            </div>
        </div>

        <div class="card" style="padding: 16px; border-radius: 14px; background: linear-gradient(135deg, rgba(239,68,68,0.06) 0%, rgba(59,130,246,0.04) 100%); border: 1px solid rgba(239,68,68,0.18);">
            <div style="font-weight: 700; font-size: 0.86rem; color: var(--text-color); margin-bottom: 10px; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-shield-halved text-danger"></i> Protokol Gerbang
            </div>
            <p style="font-size: 0.78rem; color: var(--text-muted); line-height: 1.45; margin: 0 0 10px 0;">
                Setiap tamu wajib menyerahkan identitas (KTP/SIM) dan mengenakan ID Card Tamu selama di lingkungan sekolah.
            </p>
            <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.78rem;">
                <span style="color: var(--text-muted);">Status Pos:</span>
                <span class="badge badge-success" style="font-size: 0.68rem; padding: 2px 6px;">Siaga Penuh</span>
            </div>
        </div>
    </div>
</div>
