{{-- Section Dashboard: Penjaga Sekolah / Staf Kebersihan & Rumah Tangga --}}

<!-- Quick Stats Grid Penjaga Sekolah -->
<div class="dash-stat-grid"
    style="grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); margin-bottom: 24px; gap: 16px;">
    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(16, 185, 129, 0.15); color: #10b981;">
            <i class="fas fa-broom"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">36 Ruangan</div>
            <div class="dash-stat-label">Area Gedung Terawat</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(59, 130, 246, 0.15); color: #3b82f6;">
            <i class="fas fa-faucet-drip"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">Normal</div>
            <div class="dash-stat-label">Instalasi Air &amp; Sanitasi</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b;">
            <i class="fas fa-bolt"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">Siap Pakai</div>
            <div class="dash-stat-label">Kelistrikan Gedung</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(99, 102, 241, 0.15); color: var(--primary);">
            <i class="fas fa-key"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value" style="font-size: 1.05rem;">Aman Terkunci</div>
            <div class="dash-stat-label">Pengamanan Kunci Ruang</div>
        </div>
    </div>
</div>

<!-- Main Content Grid Penjaga Sekolah -->
<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-bottom: 24px;">
    <!-- Left Column: Checklist Harian Kesiapan Gedung -->
    <div style="display: flex; flex-direction: column; gap: 20px;">
        <div class="card" style="padding: 0; overflow: hidden; border-radius: 14px;">
            <div
                style="padding: 16px 20px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; background: rgba(255,255,255,0.02);">
                <div style="font-weight: 700; font-size: 0.95rem; color: var(--text-color);">
                    <i class="fas fa-clipboard-check text-primary me-2"></i> Checklist Kesiapan Ruang Belajar &amp; Fasilitas Harian
                </div>
                <span class="badge badge-success" style="font-size: 0.72rem; padding: 4px 8px;">
                    Shift Pagi &amp; Sore
                </span>
            </div>

            <div class="table-responsive">
                <table class="table" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                    <thead>
                        <tr style="border-bottom: 1px solid var(--border-color); background: rgba(255,255,255,0.01);">
                            <th style="padding: 10px 16px; font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted);">Area / Ruangan</th>
                            <th style="padding: 10px 14px; font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted);">Tugas Pemeriksaan</th>
                            <th style="padding: 10px 14px; font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted);">Waktu Rutin</th>
                            <th style="padding: 10px 16px; font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); text-align: center;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 12px 16px; font-size: 0.82rem; font-weight: 600; color: var(--text-color);">
                                Ruang Kelas X, XI, XII (Gedung Belajar)
                            </td>
                            <td style="padding: 12px 14px; font-size: 0.8rem; color: var(--text-muted);">
                                Membuka pintu &amp; jendela, sapu/pel lantai, cek papan tulis
                            </td>
                            <td style="padding: 12px 14px; font-size: 0.8rem;">06:00 - 06:45 WIB</td>
                            <td style="padding: 12px 16px; text-align: center;">
                                <span class="badge badge-success" style="font-size: 0.74rem;">Selesai Bersih</span>
                            </td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 12px 16px; font-size: 0.82rem; font-weight: 600; color: var(--text-color);">
                                Ruang Guru, Kepala Sekolah &amp; Kantor TAS
                            </td>
                            <td style="padding: 12px 14px; font-size: 0.8rem; color: var(--text-muted);">
                                Pengadaan air galon, pembersihan meja, buang tempat sampah
                            </td>
                            <td style="padding: 12px 14px; font-size: 0.8rem;">06:30 - 07:00 WIB</td>
                            <td style="padding: 12px 16px; text-align: center;">
                                <span class="badge badge-success" style="font-size: 0.74rem;">Siap Pakai</span>
                            </td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 12px 16px; font-size: 0.82rem; font-weight: 600; color: var(--text-color);">
                                Fasilitas Toilet &amp; Tempat Wudhu Siswa
                            </td>
                            <td style="padding: 12px 14px; font-size: 0.8rem; color: var(--text-muted);">
                                Cek kelancaran air tandon, sabun cuci tangan, pembersihan lantai
                            </td>
                            <td style="padding: 12px 14px; font-size: 0.8rem;">07:00 &amp; 12:30 WIB</td>
                            <td style="padding: 12px 16px; text-align: center;">
                                <span class="badge badge-success" style="font-size: 0.74rem;">Bersih &amp; Mengalir</span>
                            </td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 12px 16px; font-size: 0.82rem; font-weight: 600; color: var(--text-color);">
                                Penguncian Gedung &amp; Pemadaman Lampu
                            </td>
                            <td style="padding: 12px 14px; font-size: 0.8rem; color: var(--text-muted);">
                                Memastikan seluruh pintu kelas terkunci dan lampu tidak terpakai mati
                            </td>
                            <td style="padding: 12px 14px; font-size: 0.8rem;">16:00 - 17:00 WIB</td>
                            <td style="padding: 12px 16px; text-align: center;">
                                <span class="badge badge-info" style="font-size: 0.74rem;">Shift Sore</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Standar Kebersihan & Rumah Tangga Sekolah -->
        <div class="card" style="padding: 0; overflow: hidden; border-radius: 14px;">
            <div
                style="padding: 16px 20px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; background: rgba(255,255,255,0.02);">
                <div style="font-weight: 700; font-size: 0.95rem; color: var(--text-color);">
                    <i class="fas fa-trash-can text-success me-2"></i> Pengelolaan Sampah &amp; Halaman Sekolah
                </div>
            </div>

            <div style="padding: 18px 20px; display: flex; flex-direction: column; gap: 12px;">
                <div style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 10px; border-bottom: 1px solid rgba(255,255,255,0.05);">
                    <div>
                        <div style="font-size: 0.84rem; font-weight: 600; color: var(--text-color);">Pengangkutan Sampah Organik &amp; Anorganik</div>
                        <div style="font-size: 0.74rem; color: var(--text-muted);">Tempat pembuangan akhir sekolah bersih sebelum jam 08:00 WIB</div>
                    </div>
                    <span class="badge badge-success" style="font-size: 0.74rem;">Selesai</span>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <div style="font-size: 0.84rem; font-weight: 600; color: var(--text-color);">Penyiraman Tanaman &amp; Halaman Depan</div>
                        <div style="font-size: 0.74rem; color: var(--text-muted);">Area taman sekolah asri dan hijau</div>
                    </div>
                    <span class="badge badge-success" style="font-size: 0.74rem;">Terawat</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column: Akses Cepat Penjaga Sekolah -->
    <div style="display: flex; flex-direction: column; gap: 20px;">
        <div class="card" style="padding: 20px; border-radius: 14px;">
            <div style="font-weight: 700; font-size: 0.95rem; color: var(--text-color); margin-bottom: 14px; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-bolt text-warning"></i> Menu Tugas Penjaga
            </div>
            <div style="display: flex; flex-direction: column; gap: 8px;">
                <a href="{{ route('dashboard.rombel.index') }}" class="btn btn-outline"
                    style="display: flex; justify-content: space-between; align-items: center; padding: 11px 14px; font-size: 0.82rem; border-radius: 8px; text-align: left;">
                    <span><i class="fas fa-door-open text-primary me-2"></i> Daftar Ruang Kelas Belajar</span>
                    <i class="fas fa-chevron-right text-muted" style="font-size: 0.72rem;"></i>
                </a>
                <a href="{{ route('dashboard.profile') }}" class="btn btn-outline"
                    style="display: flex; justify-content: space-between; align-items: center; padding: 11px 14px; font-size: 0.82rem; border-radius: 8px; text-align: left;">
                    <span><i class="fas fa-user-gear text-info me-2"></i> Profil &amp; Akun Penjaga</span>
                    <i class="fas fa-chevron-right text-muted" style="font-size: 0.72rem;"></i>
                </a>
            </div>
        </div>

        <div class="card" style="padding: 20px; border-radius: 14px; background: linear-gradient(135deg, rgba(16,185,129,0.08) 0%, rgba(59,130,246,0.05) 100%); border: 1px solid rgba(16,185,129,0.2);">
            <div style="font-weight: 700; font-size: 0.9rem; color: var(--text-color); margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-leaf text-success"></i> Standar Sekolah Sehat &amp; Nyaman
            </div>
            <p style="font-size: 0.82rem; color: var(--text-muted); line-height: 1.5; margin: 0 0 12px 0;">
                Menciptakan lingkungan belajar yang bersih, higienis, dan ramah anak demi kenyamanan seluruh warga SMK.
            </p>
            <div style="display: flex; justify-content: space-between; font-size: 0.8rem;">
                <span style="color: var(--text-muted);">Status Lingkungan:</span>
                <strong style="color: #10b981;">Bersih &amp; Asri</strong>
            </div>
        </div>
    </div>
</div>
