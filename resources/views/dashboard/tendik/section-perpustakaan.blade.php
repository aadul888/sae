{{-- Section Dashboard: Pustakawan (Staf Pelayanan Perpustakaan) --}}

<!-- Quick Stats Grid Perpustakaan -->
<div class="dash-stat-grid"
    style="grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); margin-bottom: 24px; gap: 16px;">
    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(59, 130, 246, 0.15); color: #3b82f6;">
            <i class="fas fa-book-bookmark"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">284 Judul</div>
            <div class="dash-stat-label">Judul Buku Terdaftar</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(16, 185, 129, 0.15); color: #10b981;">
            <i class="fas fa-book"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">3.420 Eks</div>
            <div class="dash-stat-label">Total Koleksi Eksemplar</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b;">
            <i class="fas fa-users-line"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">42 Siswa</div>
            <div class="dash-stat-label">Pengunjung Hari Ini</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(139, 92, 246, 0.15); color: #8b5cf6;">
            <i class="fas fa-hand-holding-hand"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">18 Buku</div>
            <div class="dash-stat-label">Peminjaman Aktif</div>
        </div>
    </div>
</div>

<!-- Main Content Grid Perpustakaan -->
<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-bottom: 24px;">
    <!-- Left Column: Koleksi Buku Wajib & Log Peminjaman -->
    <div style="display: flex; flex-direction: column; gap: 20px;">
        <div class="card" style="padding: 0; overflow: hidden; border-radius: 14px;">
            <div
                style="padding: 16px 20px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; background: rgba(255,255,255,0.02);">
                <div style="font-weight: 700; font-size: 0.95rem; color: var(--text-color);">
                    <i class="fas fa-book-open-reader text-primary me-2"></i> Buku Teks Kurikulum Merdeka &amp; Kejuruan Terpopuler
                </div>
            </div>

            <div class="table-responsive">
                <table class="table" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                    <thead>
                        <tr style="border-bottom: 1px solid var(--border-color); background: rgba(255,255,255,0.01);">
                            <th style="padding: 10px 16px; font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted);">Judul Buku</th>
                            <th style="padding: 10px 14px; font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted);">Kategori / Mapel</th>
                            <th style="padding: 10px 14px; font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted);">Tingkat</th>
                            <th style="padding: 10px 16px; font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); text-align: center;">Ketersediaan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 12px 16px; font-size: 0.82rem; font-weight: 600; color: var(--text-color);">
                                Dasar-Dasar Agribisnis Tanaman (Edisi Revisi)
                            </td>
                            <td style="padding: 12px 14px; font-size: 0.8rem; color: var(--text-muted);">Dasar Kejuruan</td>
                            <td style="padding: 12px 14px; font-size: 0.8rem;">Kelas X</td>
                            <td style="padding: 12px 16px; text-align: center;">
                                <span class="badge badge-success" style="font-size: 0.74rem;">72 Eks Tersedia</span>
                            </td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 12px 16px; font-size: 0.82rem; font-weight: 600; color: var(--text-color);">
                                Konservasi &amp; Pengelolaan Hutan Produksi
                            </td>
                            <td style="padding: 12px 14px; font-size: 0.8rem; color: var(--text-muted);">Kehutanan</td>
                            <td style="padding: 12px 14px; font-size: 0.8rem;">Kelas XI</td>
                            <td style="padding: 12px 16px; text-align: center;">
                                <span class="badge badge-success" style="font-size: 0.74rem;">48 Eks Tersedia</span>
                            </td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 12px 16px; font-size: 0.82rem; font-weight: 600; color: var(--text-color);">
                                Matematika Terapan SMK Kelompok Vokasi
                            </td>
                            <td style="padding: 12px 14px; font-size: 0.8rem; color: var(--text-muted);">Mata Pelajaran Umum</td>
                            <td style="padding: 12px 14px; font-size: 0.8rem;">Kelas X - XII</td>
                            <td style="padding: 12px 16px; text-align: center;">
                                <span class="badge badge-success" style="font-size: 0.74rem;">110 Eks Tersedia</span>
                            </td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 12px 16px; font-size: 0.82rem; font-weight: 600; color: var(--text-color);">
                                Bahasa Indonesia untuk Komunikasi Kerja &amp; Literasi
                            </td>
                            <td style="padding: 12px 14px; font-size: 0.8rem; color: var(--text-muted);">Mata Pelajaran Umum</td>
                            <td style="padding: 12px 14px; font-size: 0.8rem;">Kelas X</td>
                            <td style="padding: 12px 16px; text-align: center;">
                                <span class="badge badge-success" style="font-size: 0.74rem;">95 Eks Tersedia</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Log Peminjaman Sirkulasi Terkini -->
        <div class="card" style="padding: 0; overflow: hidden; border-radius: 14px;">
            <div
                style="padding: 16px 20px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; background: rgba(255,255,255,0.02);">
                <div style="font-weight: 700; font-size: 0.95rem; color: var(--text-color);">
                    <i class="fas fa-arrows-rotate text-success me-2"></i> Sirkulasi Peminjaman &amp; Pengembalian Terkini
                </div>
            </div>

            <div style="padding: 18px 20px; display: flex; flex-direction: column; gap: 12px;">
                <div style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 10px; border-bottom: 1px solid rgba(255,255,255,0.05);">
                    <div>
                        <div style="font-size: 0.84rem; font-weight: 600; color: var(--text-color);">Peminjaman 36 Eks Buku Dasar Kehutanan (Kelas X TKH 1)</div>
                        <div style="font-size: 0.74rem; color: var(--text-muted);">Peminjam: Agum Bagja Gumelar &bull; Batas: 24 Sep 2026</div>
                    </div>
                    <span class="badge badge-primary" style="font-size: 0.74rem;">Sedang Dipinjam</span>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <div style="font-size: 0.84rem; font-weight: 600; color: var(--text-color);">Pengembalian 34 Eks Buku Agribisnis (Kelas XI ATPH 1)</div>
                        <div style="font-size: 0.74rem; color: var(--text-muted);">Peminjam: Bunyamin &bull; Dikembalikan: 18 Sep 2026</div>
                    </div>
                    <span class="badge badge-success" style="font-size: 0.74rem;">Lengkap Kembali</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column: Akses Cepat Perpustakaan -->
    <div style="display: flex; flex-direction: column; gap: 20px;">
        <div class="card" style="padding: 20px; border-radius: 14px;">
            <div style="font-weight: 700; font-size: 0.95rem; color: var(--text-color); margin-bottom: 14px; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-bolt text-warning"></i> Layanan Perpustakaan
            </div>
            <div style="display: flex; flex-direction: column; gap: 8px;">
                <a href="{{ route('dashboard.peserta-didik-aktif.index') }}" class="btn btn-outline"
                    style="display: flex; justify-content: space-between; align-items: center; padding: 11px 14px; font-size: 0.82rem; border-radius: 8px; text-align: left;">
                    <span><i class="fas fa-id-card text-primary me-2"></i> Verifikasi Anggota Siswa</span>
                    <i class="fas fa-chevron-right text-muted" style="font-size: 0.72rem;"></i>
                </a>
                <a href="{{ route('dashboard.guru-aktif.index') }}" class="btn btn-outline"
                    style="display: flex; justify-content: space-between; align-items: center; padding: 11px 14px; font-size: 0.82rem; border-radius: 8px; text-align: left;">
                    <span><i class="fas fa-chalkboard-user text-info me-2"></i> Pinjaman Buku Guru</span>
                    <i class="fas fa-chevron-right text-muted" style="font-size: 0.72rem;"></i>
                </a>
            </div>
        </div>

        <div class="card" style="padding: 20px; border-radius: 14px; background: linear-gradient(135deg, rgba(59,130,246,0.08) 0%, rgba(16,185,129,0.05) 100%); border: 1px solid rgba(59,130,246,0.2);">
            <div style="font-weight: 700; font-size: 0.9rem; color: var(--text-color); margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-bookmark text-primary"></i> Program Pojok Literasi Digital
            </div>
            <p style="font-size: 0.82rem; color: var(--text-muted); line-height: 1.5; margin: 0 0 12px 0;">
                Mendukung akses buku teks pelajaran, modul ajar digital kurikulum merdeka, dan referensi kejuruan pertanian/kehutanan.
            </p>
            <div style="display: flex; justify-content: space-between; font-size: 0.8rem;">
                <span style="color: var(--text-muted);">Status Ruang Baca:</span>
                <strong style="color: #10b981;">Buka &amp; Siap Melayani</strong>
            </div>
        </div>
    </div>
</div>
