{{-- Section Dashboard: Penjaga Sekolah / Staf Kebersihan & Rumah Tangga --}}

<!-- Quick Stats Grid Penjaga Sekolah (Responsive) -->
<div class="dash-stat-grid" style="margin-bottom: 24px; gap: 14px;">
    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(16, 185, 129, 0.15); color: #10b981;">
            <i class="fas fa-broom"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">36 Ruang</div>
            <div class="dash-stat-label">Area Gedung Terawat</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(59, 130, 246, 0.15); color: #3b82f6;">
            <i class="fas fa-faucet-drip"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">Normal</div>
            <div class="dash-stat-label">Air &amp; Sanitasi</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b;">
            <i class="fas fa-bolt"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">Siap</div>
            <div class="dash-stat-label">Kelistrikan Gedung</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(99, 102, 241, 0.15); color: var(--primary);">
            <i class="fas fa-key"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">Aman</div>
            <div class="dash-stat-label">Kunci Ruang Terkontrol</div>
        </div>
    </div>
</div>

<!-- Main Content Grid Penjaga Sekolah (Responsive: 2fr 1fr desktop, 1fr mobile) -->
<div class="dash-layout-grid">
    <!-- Left Column: Checklist Harian Kesiapan Gedung -->
    <div style="display: flex; flex-direction: column; gap: 20px; min-width: 0;">
        <div class="card" style="padding: 0; overflow: hidden; border-radius: 14px; border: 1px solid var(--border-color); background: var(--card-bg);">
            <div style="padding: 14px 18px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; background: rgba(255,255,255,0.02); flex-wrap: wrap; gap: 8px;">
                <div style="font-weight: 700; font-size: 0.92rem; color: var(--text-color); display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-clipboard-check text-primary"></i> Checklist Kesiapan Fasilitas
                </div>
                <span class="badge badge-success" style="font-size: 0.7rem; padding: 3px 8px; border-radius: 6px;">
                    Shift Harian
                </span>
            </div>

            <div class="table-responsive-stack" style="overflow-x: auto; -webkit-overflow-scrolling: touch; margin: 0;">
                <table class="table-minimal-compact">
                    <thead>
                        <tr>
                            <th style="min-width: 140px;">Area / Gedung</th>
                            <th style="min-width: 130px;">Tugas Utama</th>
                            <th style="min-width: 90px;">Waktu</th>
                            <th style="min-width: 70px; text-align: center;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <div style="font-weight: 700; color: var(--text-color); font-size: 0.84rem;">
                                    Ruang Kelas X-XII
                                </div>
                            </td>
                            <td>
                                <span style="font-size: 0.78rem; color: var(--text-muted);">
                                    Buka jendela, sapu/pel, cek papan tulis
                                </span>
                            </td>
                            <td><span style="font-size: 0.76rem; color: var(--text-muted);">06:00 - 06:45</span></td>
                            <td style="text-align: center;">
                                <span class="badge-compact" style="background: rgba(16,185,129,0.12); color: #10b981;">
                                    <i class="fas fa-check"></i> Bersih
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div style="font-weight: 700; color: var(--text-color); font-size: 0.84rem;">
                                    Ruang Guru &amp; TAS
                                </div>
                            </td>
                            <td>
                                <span style="font-size: 0.78rem; color: var(--text-muted);">
                                    Air galon, meja kerja, tempat sampah
                                </span>
                            </td>
                            <td><span style="font-size: 0.76rem; color: var(--text-muted);">06:30 - 07:00</span></td>
                            <td style="text-align: center;">
                                <span class="badge-compact" style="background: rgba(16,185,129,0.12); color: #10b981;">
                                    <i class="fas fa-check"></i> Siap
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div style="font-weight: 700; color: var(--text-color); font-size: 0.84rem;">
                                    Toilet &amp; Tempat Wudhu
                                </div>
                            </td>
                            <td>
                                <span style="font-size: 0.78rem; color: var(--text-muted);">
                                    Cek debit air kran, sabun, kuras bak
                                </span>
                            </td>
                            <td><span style="font-size: 0.76rem; color: var(--text-muted);">06:15 &amp; 12:00</span></td>
                            <td style="text-align: center;">
                                <span class="badge-compact" style="background: rgba(16,185,129,0.12); color: #10b981;">
                                    <i class="fas fa-check"></i> Higienis
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div style="font-weight: 700; color: var(--text-color); font-size: 0.84rem;">
                                    Halaman &amp; Lapangan
                                </div>
                            </td>
                            <td>
                                <span style="font-size: 0.78rem; color: var(--text-muted);">
                                    Sapu guguran daun, siapkan tiang bendera
                                </span>
                            </td>
                            <td><span style="font-size: 0.76rem; color: var(--text-muted);">06:00 - 06:30</span></td>
                            <td style="text-align: center;">
                                <span class="badge-compact" style="background: rgba(16,185,129,0.12); color: #10b981;">
                                    <i class="fas fa-check"></i> Rapi
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Log Pengamanan Sore -->
        <div class="card" style="padding: 0; overflow: hidden; border-radius: 14px; border: 1px solid var(--border-color); background: var(--card-bg);">
            <div style="padding: 14px 18px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; background: rgba(255,255,255,0.02); flex-wrap: wrap; gap: 8px;">
                <div style="font-weight: 700; font-size: 0.92rem; color: var(--text-color); display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-lock text-warning"></i> Protokol Penguncian Gedung (Sore)
                </div>
            </div>

            <div style="padding: 14px 18px; display: flex; flex-direction: column; gap: 10px;">
                <div style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 8px; border-bottom: 1px solid rgba(255,255,255,0.05); gap: 10px;">
                    <div style="min-width: 0; flex: 1;">
                        <div style="font-size: 0.82rem; font-weight: 600; color: var(--text-color);">Pemadaman Lampu Kelas &amp; Kipas Angin</div>
                        <div style="font-size: 0.7rem; color: var(--text-muted);">Pemeriksaan sakelar seluruh ruang belajar &bull; 16:00 WIB</div>
                    </div>
                    <span class="badge-compact" style="background: rgba(16,185,129,0.12); color: #10b981;">
                        <i class="fas fa-check"></i> Aman
                    </span>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; gap: 10px;">
                    <div style="min-width: 0; flex: 1;">
                        <div style="font-size: 0.82rem; font-weight: 600; color: var(--text-color);">Penguncian Pintu Gedung &amp; Penyerahan Kunci</div>
                        <div style="font-size: 0.7rem; color: var(--text-muted);">Serah terima master kunci ke Pos Satpam &bull; 17:00 WIB</div>
                    </div>
                    <span class="badge-compact" style="background: rgba(59,130,246,0.12); color: #3b82f6;">
                        <i class="fas fa-key"></i> Terkunci
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column: Akses Cepat Penjaga (Stack di Mobile) -->
    <div style="display: flex; flex-direction: column; gap: 20px; min-width: 0;">
        <div class="card" style="padding: 18px; border-radius: 14px; border: 1px solid var(--border-color); background: var(--card-bg);">
            <div style="font-weight: 700; font-size: 0.9rem; color: var(--text-color); margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-bolt text-warning"></i> Menu Kebersihan &amp; Gedung
            </div>
            <div style="display: flex; flex-direction: column; gap: 8px;">
                <a href="{{ route('dashboard.rombel.index') }}" class="btn btn-outline"
                    style="display: flex; justify-content: space-between; align-items: center; padding: 9px 12px; font-size: 0.8rem; border-radius: 8px; text-decoration: none;">
                    <span><i class="fas fa-door-open text-primary me-2"></i> Daftar Ruang Kelas</span>
                    <i class="fas fa-chevron-right text-muted" style="font-size: 0.7rem;"></i>
                </a>
                <a href="{{ route('dashboard.identitas-sekolah.index') }}" class="btn btn-outline"
                    style="display: flex; justify-content: space-between; align-items: center; padding: 9px 12px; font-size: 0.8rem; border-radius: 8px; text-decoration: none;">
                    <span><i class="fas fa-school text-info me-2"></i> Denah Area Sekolah</span>
                    <i class="fas fa-chevron-right text-muted" style="font-size: 0.7rem;"></i>
                </a>
                <a href="{{ route('dashboard.tendik-aktif.index') }}" class="btn btn-outline"
                    style="display: flex; justify-content: space-between; align-items: center; padding: 9px 12px; font-size: 0.8rem; border-radius: 8px; text-decoration: none;">
                    <span><i class="fas fa-users text-warning me-2"></i> Rekan Staf TAS</span>
                    <i class="fas fa-chevron-right text-muted" style="font-size: 0.7rem;"></i>
                </a>
            </div>
        </div>

        <div class="card" style="padding: 16px; border-radius: 14px; background: linear-gradient(135deg, rgba(132,204,22,0.06) 0%, rgba(59,130,246,0.04) 100%); border: 1px solid rgba(132,204,22,0.18);">
            <div style="font-weight: 700; font-size: 0.86rem; color: var(--text-color); margin-bottom: 10px; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-hand-holding-heart text-success"></i> Budaya 5R Sekolah
            </div>
            <p style="font-size: 0.78rem; color: var(--text-muted); line-height: 1.45; margin: 0 0 10px 0;">
                Terapkan Ringkas, Rapi, Resik, Rawat, dan Rajin pada setiap lorong, toilet, dan taman sekolah setiap hari.
            </p>
            <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.78rem;">
                <span style="color: var(--text-muted);">Status Lingkungan:</span>
                <span class="badge badge-success" style="font-size: 0.68rem; padding: 2px 6px;">Asri &amp; Terjaga</span>
            </div>
        </div>
    </div>
</div>
