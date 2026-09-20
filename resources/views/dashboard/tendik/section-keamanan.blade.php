{{-- Section Dashboard: Petugas Keamanan (Satpam Sekolah) --}}

<!-- Quick Stats Grid Keamanan -->
<div class="dash-stat-grid"
    style="grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); margin-bottom: 24px; gap: 16px;">
    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(59, 130, 246, 0.15); color: #3b82f6;">
            <i class="fas fa-address-book"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">{{ $stats['buku_tamu_hari_ini'] ?? 12 }} Tamu</div>
            <div class="dash-stat-label">Buku Tamu Digital Hari Ini</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b;">
            <i class="fas fa-person-walking-arrow-right"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">3 Siswa</div>
            <div class="dash-stat-label">E-Izin Keluar Sekolah</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(16, 185, 129, 0.15); color: #10b981;">
            <i class="fas fa-id-card-clip"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">Aktif</div>
            <div class="dash-stat-label">Kiosk Gerbang Utama</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(99, 102, 241, 0.15); color: var(--primary);">
            <i class="fas fa-shield-halved"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value" style="font-size: 1.05rem;">Kondusif</div>
            <div class="dash-stat-label">Keamanan Gerbang &amp; Area</div>
        </div>
    </div>
</div>

<!-- Main Content Grid Keamanan -->
<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-bottom: 24px;">
    <!-- Left Column: Buku Tamu Hari Ini & Pemantauan E-Izin Gerbang -->
    <div style="display: flex; flex-direction: column; gap: 20px;">
        <div class="card" style="padding: 0; overflow: hidden; border-radius: 14px;">
            <div
                style="padding: 16px 20px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; background: rgba(255,255,255,0.02);">
                <div style="font-weight: 700; font-size: 0.95rem; color: var(--text-color);">
                    <i class="fas fa-clipboard-user text-primary me-2"></i> Log Kunjungan Tamu &amp; Wali Murid Gerbang
                </div>
                <span class="badge badge-info" style="font-size: 0.72rem; padding: 4px 8px;">
                    Hari Ini: {{ date('d F Y') }}
                </span>
            </div>

            <div class="table-responsive">
                <table class="table" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                    <thead>
                        <tr style="border-bottom: 1px solid var(--border-color); background: rgba(255,255,255,0.01);">
                            <th style="padding: 10px 16px; font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted);">Nama Tamu</th>
                            <th style="padding: 10px 14px; font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted);">Instansi / Asal</th>
                            <th style="padding: 10px 14px; font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted);">Tujuan Kunjungan</th>
                            <th style="padding: 10px 16px; font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); text-align: center;">Jam Masuk</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 12px 16px; font-size: 0.82rem; font-weight: 600; color: var(--text-color);">
                                Bpk. Hendra Kusuma
                            </td>
                            <td style="padding: 12px 14px; font-size: 0.8rem; color: var(--text-muted);">Dinas Pendidikan Wilayah</td>
                            <td style="padding: 12px 14px; font-size: 0.8rem; color: var(--text-color);">Koordinasi Bantuan Operasional Sekolah</td>
                            <td style="padding: 12px 16px; text-align: center;">
                                <span class="badge badge-primary" style="font-size: 0.74rem;">08:15 WIB</span>
                            </td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 12px 16px; font-size: 0.82rem; font-weight: 600; color: var(--text-color);">
                                Ibu Siti Maryam
                            </td>
                            <td style="padding: 12px 14px; font-size: 0.8rem; color: var(--text-muted);">Wali Murid (X ATPH 1)</td>
                            <td style="padding: 12px 14px; font-size: 0.8rem; color: var(--text-color);">Konsultasi Guru BK / Wali Kelas</td>
                            <td style="padding: 12px 16px; text-align: center;">
                                <span class="badge badge-primary" style="font-size: 0.74rem;">09:30 WIB</span>
                            </td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 12px 16px; font-size: 0.82rem; font-weight: 600; color: var(--text-color);">
                                Tim Pengiriman Logistik
                            </td>
                            <td style="padding: 12px 14px; font-size: 0.8rem; color: var(--text-muted);">PT. Agro Niaga Pratama</td>
                            <td style="padding: 12px 14px; font-size: 0.8rem; color: var(--text-color);">Pengiriman Bibit &amp; Pupuk Praktik</td>
                            <td style="padding: 12px 16px; text-align: center;">
                                <span class="badge badge-success" style="font-size: 0.74rem;">10:45 WIB</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Standar Prosedur Keamanan Gerbang -->
        <div class="card" style="padding: 0; overflow: hidden; border-radius: 14px;">
            <div
                style="padding: 16px 20px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; background: rgba(255,255,255,0.02);">
                <div style="font-weight: 700; font-size: 0.95rem; color: var(--text-color);">
                    <i class="fas fa-shield text-success me-2"></i> Ketertiban Gerbang &amp; E-Izin Keluar Siswa
                </div>
            </div>

            <div style="padding: 18px 20px; display: flex; flex-direction: column; gap: 12px;">
                <div style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 10px; border-bottom: 1px solid rgba(255,255,255,0.05);">
                    <div>
                        <div style="font-size: 0.84rem; font-weight: 600; color: var(--text-color);">Pemeriksaan Siswa Izin Meninggalkan Lingkungan Sekolah</div>
                        <div style="font-size: 0.74rem; color: var(--text-muted);">Hanya diizinkan jika telah disetujui Guru Piket / Wali Kelas di sistem</div>
                    </div>
                    <span class="badge badge-warning" style="font-size: 0.74rem;">Wajib Surat Izin</span>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <div style="font-size: 0.84rem; font-weight: 600; color: var(--text-color);">Penutupan Gerbang Utama Saat Jam Pembelajaran Berlangsung</div>
                        <div style="font-size: 0.74rem; color: var(--text-muted);">Pintu gerbang ditutup pukul 07:15 WIB setelah bel masuk</div>
                    </div>
                    <span class="badge badge-success" style="font-size: 0.74rem;">Terkunci Rapi</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column: Akses Cepat Satpam -->
    <div style="display: flex; flex-direction: column; gap: 20px;">
        <div class="card" style="padding: 20px; border-radius: 14px;">
            <div style="font-weight: 700; font-size: 0.95rem; color: var(--text-color); margin-bottom: 14px; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-bolt text-warning"></i> Menu Pos Keamanan
            </div>
            <div style="display: flex; flex-direction: column; gap: 8px;">
                <a href="{{ route('presensi.scan') }}" target="_blank" class="btn btn-outline"
                    style="display: flex; justify-content: space-between; align-items: center; padding: 11px 14px; font-size: 0.82rem; border-radius: 8px; text-align: left;">
                    <span><i class="fas fa-desktop text-success me-2"></i> Buka Terminal Scan Gerbang</span>
                    <i class="fas fa-arrow-up-right-from-square text-muted" style="font-size: 0.72rem;"></i>
                </a>
                <a href="{{ route('dashboard.persuratan.index') }}" class="btn btn-outline"
                    style="display: flex; justify-content: space-between; align-items: center; padding: 11px 14px; font-size: 0.82rem; border-radius: 8px; text-align: left;">
                    <span><i class="fas fa-book-open text-primary me-2"></i> Registrasi Surat Masuk Ekspedisi</span>
                    <i class="fas fa-chevron-right text-muted" style="font-size: 0.72rem;"></i>
                </a>
            </div>
        </div>

        <div class="card" style="padding: 20px; border-radius: 14px; background: linear-gradient(135deg, rgba(245,158,11,0.08) 0%, rgba(16,185,129,0.05) 100%); border: 1px solid rgba(245,158,11,0.2);">
            <div style="font-weight: 700; font-size: 0.9rem; color: var(--text-color); margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-id-badge text-warning"></i> Kartu Tamu &amp; Identitas Pengunjung
            </div>
            <p style="font-size: 0.82rem; color: var(--text-muted); line-height: 1.5; margin: 0 0 12px 0;">
                Setiap tamu wajib menyerahkan kartu identitas (KTP/SIM) dan memakai tanda pengenal tamu selama berada di dalam lingkungan sekolah.
            </p>
            <div style="display: flex; justify-content: space-between; font-size: 0.8rem;">
                <span style="color: var(--text-muted);">Status Pos Satpam:</span>
                <strong style="color: #10b981;">Siaga 24 Jam</strong>
            </div>
        </div>
    </div>
</div>
