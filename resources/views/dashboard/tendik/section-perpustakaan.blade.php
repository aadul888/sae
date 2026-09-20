{{-- Section Dashboard: Pustakawan (Staf Pelayanan Perpustakaan) --}}

<!-- Quick Stats Grid Perpustakaan (Responsive) -->
<div class="dash-stat-grid" style="margin-bottom: 24px; gap: 14px;">
    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(59, 130, 246, 0.15); color: #3b82f6;">
            <i class="fas fa-book-bookmark"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">284 Judul</div>
            <div class="dash-stat-label">Buku Terdaftar</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(16, 185, 129, 0.15); color: #10b981;">
            <i class="fas fa-book"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">3.420 Eks</div>
            <div class="dash-stat-label">Koleksi Fisik</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b;">
            <i class="fas fa-users-line"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">42 Siswa</div>
            <div class="dash-stat-label">Kunjungan Hari Ini</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(139, 92, 246, 0.15); color: #8b5cf6;">
            <i class="fas fa-hand-holding-hand"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">18 Buku</div>
            <div class="dash-stat-label">Pinjaman Aktif</div>
        </div>
    </div>
</div>

<!-- Main Content Grid Perpustakaan (Responsive: 2fr 1fr desktop, 1fr mobile) -->
<div class="dash-layout-grid">
    <!-- Left Column: Koleksi Buku Wajib & Log Peminjaman -->
    <div style="display: flex; flex-direction: column; gap: 20px; min-width: 0;">
        <div class="card" style="padding: 0; overflow: hidden; border-radius: 14px; border: 1px solid var(--border-color); background: var(--card-bg);">
            <div style="padding: 14px 18px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; background: rgba(255,255,255,0.02); flex-wrap: wrap; gap: 8px;">
                <div style="font-weight: 700; font-size: 0.92rem; color: var(--text-color); display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-book-open-reader text-primary"></i> Buku Teks Kurikulum Terpopuler
                </div>
            </div>

            <div class="table-responsive-stack" style="overflow-x: auto; -webkit-overflow-scrolling: touch; margin: 0;">
                <table class="table-minimal-compact">
                    <thead>
                        <tr>
                            <th style="min-width: 140px;">Judul Buku</th>
                            <th style="min-width: 100px;">Kategori</th>
                            <th style="min-width: 80px;">Tingkat</th>
                            <th style="min-width: 90px; text-align: center;">Ketersediaan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <div style="font-weight: 700; color: var(--text-color); font-size: 0.84rem;">
                                    Dasar Agribisnis Tanaman
                                </div>
                            </td>
                            <td>
                                <span class="badge-compact" style="background: rgba(59,130,246,0.1); color: #3b82f6;">
                                    <i class="fas fa-seedling"></i> Kejuruan
                                </span>
                            </td>
                            <td><span style="font-size: 0.78rem; color: var(--text-muted);">Kelas X</span></td>
                            <td style="text-align: center;">
                                <span class="badge-compact" style="background: rgba(16,185,129,0.12); color: #10b981;">
                                    72 Eks
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div style="font-weight: 700; color: var(--text-color); font-size: 0.84rem;">
                                    Pengelolaan Hutan Produksi
                                </div>
                            </td>
                            <td>
                                <span class="badge-compact" style="background: rgba(16,185,129,0.1); color: #10b981;">
                                    <i class="fas fa-tree"></i> Kehutanan
                                </span>
                            </td>
                            <td><span style="font-size: 0.78rem; color: var(--text-muted);">Kelas XI</span></td>
                            <td style="text-align: center;">
                                <span class="badge-compact" style="background: rgba(16,185,129,0.12); color: #10b981;">
                                    48 Eks
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div style="font-weight: 700; color: var(--text-color); font-size: 0.84rem;">
                                    Matematika Terapan SMK
                                </div>
                            </td>
                            <td>
                                <span class="badge-compact" style="background: rgba(99,102,241,0.1); color: #6366f1;">
                                    <i class="fas fa-calculator"></i> Umum
                                </span>
                            </td>
                            <td><span style="font-size: 0.78rem; color: var(--text-muted);">Kelas X-XII</span></td>
                            <td style="text-align: center;">
                                <span class="badge-compact" style="background: rgba(16,185,129,0.12); color: #10b981;">
                                    110 Eks
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div style="font-weight: 700; color: var(--text-color); font-size: 0.84rem;">
                                    Bahasa Inggris Vokasi
                                </div>
                            </td>
                            <td>
                                <span class="badge-compact" style="background: rgba(245,158,11,0.1); color: #f59e0b;">
                                    <i class="fas fa-language"></i> Umum
                                </span>
                            </td>
                            <td><span style="font-size: 0.78rem; color: var(--text-muted);">Kelas XI</span></td>
                            <td style="text-align: center;">
                                <span class="badge-compact" style="background: rgba(16,185,129,0.12); color: #10b981;">
                                    85 Eks
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Log Peminjaman Terkini -->
        <div class="card" style="padding: 0; overflow: hidden; border-radius: 14px; border: 1px solid var(--border-color); background: var(--card-bg);">
            <div style="padding: 14px 18px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; background: rgba(255,255,255,0.02); flex-wrap: wrap; gap: 8px;">
                <div style="font-weight: 700; font-size: 0.92rem; color: var(--text-color); display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-clock-rotate-left text-info"></i> Peminjaman Buku Terbaru
                </div>
            </div>

            <div style="padding: 14px 18px; display: flex; flex-direction: column; gap: 10px;">
                <div style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 8px; border-bottom: 1px solid rgba(255,255,255,0.05); gap: 10px;">
                    <div style="min-width: 0; flex: 1;">
                        <div style="font-size: 0.82rem; font-weight: 600; color: var(--text-color);">Ahmad Fauzi &bull; X ATPH 1</div>
                        <div style="font-size: 0.7rem; color: var(--text-muted);">Dasar Budidaya Tanaman &bull; Tempo: 24 Sep 2026</div>
                    </div>
                    <span class="badge-compact" style="background: rgba(59,130,246,0.12); color: #3b82f6;">
                        <i class="fas fa-book-open"></i> Dipinjam
                    </span>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 8px; border-bottom: 1px solid rgba(255,255,255,0.05); gap: 10px;">
                    <div style="min-width: 0; flex: 1;">
                        <div style="font-size: 0.82rem; font-weight: 600; color: var(--text-color);">Dewi Anggraeni &bull; XI TKJ 2</div>
                        <div style="font-size: 0.7rem; color: var(--text-muted);">Administrasi Infrastruktur Jaringan &bull; Tempo: 22 Sep 2026</div>
                    </div>
                    <span class="badge-compact" style="background: rgba(59,130,246,0.12); color: #3b82f6;">
                        <i class="fas fa-book-open"></i> Dipinjam
                    </span>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; gap: 10px;">
                    <div style="min-width: 0; flex: 1;">
                        <div style="font-size: 0.82rem; font-weight: 600; color: var(--text-color);">Rizky Maulana &bull; XII Kehutanan</div>
                        <div style="font-size: 0.7rem; color: var(--text-muted);">Pengukuran &amp; Pemetaan Hutan &bull; Dikembalikan hari ini</div>
                    </div>
                    <span class="badge-compact" style="background: rgba(16,185,129,0.12); color: #10b981;">
                        <i class="fas fa-check"></i> Kembali
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column: Akses Cepat Perpustakaan (Stack di Mobile) -->
    <div style="display: flex; flex-direction: column; gap: 20px; min-width: 0;">
        <div class="card" style="padding: 18px; border-radius: 14px; border: 1px solid var(--border-color); background: var(--card-bg);">
            <div style="font-weight: 700; font-size: 0.9rem; color: var(--text-color); margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-bolt text-warning"></i> Menu Perpustakaan
            </div>
            <div style="display: flex; flex-direction: column; gap: 8px;">
                <a href="{{ route('dashboard.peserta-didik-aktif.index') }}" class="btn btn-outline"
                    style="display: flex; justify-content: space-between; align-items: center; padding: 9px 12px; font-size: 0.8rem; border-radius: 8px; text-decoration: none;">
                    <span><i class="fas fa-users text-primary me-2"></i> Data Anggota Siswa</span>
                    <i class="fas fa-chevron-right text-muted" style="font-size: 0.7rem;"></i>
                </a>
                <a href="{{ route('dashboard.pembelajaran.index') }}" class="btn btn-outline"
                    style="display: flex; justify-content: space-between; align-items: center; padding: 9px 12px; font-size: 0.8rem; border-radius: 8px; text-decoration: none;">
                    <span><i class="fas fa-book-bookmark text-info me-2"></i> Referensi Mata Pelajaran</span>
                    <i class="fas fa-chevron-right text-muted" style="font-size: 0.7rem;"></i>
                </a>
                <a href="{{ route('dashboard.identitas-sekolah.index') }}" class="btn btn-outline"
                    style="display: flex; justify-content: space-between; align-items: center; padding: 9px 12px; font-size: 0.8rem; border-radius: 8px; text-decoration: none;">
                    <span><i class="fas fa-building text-warning me-2"></i> Profil Unit Perpustakaan</span>
                    <i class="fas fa-chevron-right text-muted" style="font-size: 0.7rem;"></i>
                </a>
            </div>
        </div>

        <div class="card" style="padding: 16px; border-radius: 14px; background: linear-gradient(135deg, rgba(236,72,153,0.06) 0%, rgba(59,130,246,0.04) 100%); border: 1px solid rgba(236,72,153,0.18);">
            <div style="font-weight: 700; font-size: 0.86rem; color: var(--text-color); margin-bottom: 10px; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-circle-info text-pink"></i> Layanan Sirkulasi
            </div>
            <p style="font-size: 0.78rem; color: var(--text-muted); line-height: 1.45; margin: 0 0 10px 0;">
                Maksimal peminjaman mandiri buku teks adalah 3 eksemplar per siswa dengan batas pengembalian 7 hari kalender.
            </p>
            <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.78rem;">
                <span style="color: var(--text-muted);">Sistem Katalog:</span>
                <span class="badge badge-success" style="font-size: 0.68rem; padding: 2px 6px;">Aktif</span>
            </div>
        </div>
    </div>
</div>
