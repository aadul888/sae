{{-- Section Dashboard: Staf Administrasi Sarpras --}}

<!-- Quick Stats Grid Sarpras -->
<div class="dash-stat-grid"
    style="grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); margin-bottom: 24px; gap: 16px;">
    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(59, 130, 246, 0.15); color: #3b82f6;">
            <i class="fas fa-building"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">{{ $stats['total_ruangan'] ?? 33 }} Ruangan</div>
            <div class="dash-stat-label">Ruang Kelas &amp; Teori</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(16, 185, 129, 0.15); color: #10b981;">
            <i class="fas fa-flask"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">6 Unit</div>
            <div class="dash-stat-label">Ruang Lab &amp; Bengkel</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b;">
            <i class="fas fa-boxes-stacked"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">148 Unit</div>
            <div class="dash-stat-label">Aset Sarana Terdata</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(139, 92, 246, 0.15); color: #8b5cf6;">
            <i class="fas fa-screwdriver-wrench"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value" style="font-size: 1.05rem;">98% Laik Pakai</div>
            <div class="dash-stat-label">Kondisi Fasilitas Sekolah</div>
        </div>
    </div>
</div>

<!-- Main Content Grid Sarpras -->
<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-bottom: 24px;">
    <!-- Left Column: Daftar Ruangan & Status Fasilitas -->
    <div style="display: flex; flex-direction: column; gap: 20px;">
        <div class="card" style="padding: 0; overflow: hidden; border-radius: 14px;">
            <div
                style="padding: 16px 20px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; background: rgba(255,255,255,0.02);">
                <div style="font-weight: 700; font-size: 0.95rem; color: var(--text-color);">
                    <i class="fas fa-door-open text-primary me-2"></i> Pemantauan Ruang Pembelajaran &amp; Kelas
                </div>
                <span class="badge badge-primary" style="font-size: 0.72rem; padding: 4px 8px;">
                    Terdaftar di Dapodik
                </span>
            </div>

            <div class="table-responsive">
                <table class="table" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                    <thead>
                        <tr style="border-bottom: 1px solid var(--border-color); background: rgba(255,255,255,0.01);">
                            <th style="padding: 10px 16px; font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted);">Nama Rombel / Pengguna</th>
                            <th style="padding: 10px 14px; font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted);">Identitas Ruang</th>
                            <th style="padding: 10px 14px; font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); text-align: center;">Fasilitas Meja/Kursi</th>
                            <th style="padding: 10px 16px; font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); text-align: center;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($ruangList ?? [] as $rg)
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <td style="padding: 12px 16px; font-size: 0.82rem; font-weight: 600; color: var(--text-color);">
                                    {{ $rg->nama_rombel }}
                                </td>
                                <td style="padding: 12px 14px; font-size: 0.8rem; color: var(--text-muted);">
                                    <i class="fas fa-tag text-info me-1"></i> {{ $rg->ruang }}
                                </td>
                                <td style="padding: 12px 14px; font-size: 0.8rem; text-align: center;">
                                    <span class="badge badge-info" style="font-size: 0.72rem; padding: 3px 8px;">36 Set Lengkap</span>
                                </td>
                                <td style="padding: 12px 16px; text-align: center;">
                                    <span style="display: inline-flex; align-items: center; gap: 4px; color: #10b981; font-weight: 600; font-size: 0.76rem;">
                                        <i class="fas fa-circle-check"></i> Siap Pakai
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" style="text-align: center; padding: 20px; color: var(--text-muted); font-size: 0.85rem;">
                                    Belum ada data ruangan kelas.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Log Pengadaan & Pemeliharaan Sarpras -->
        <div class="card" style="padding: 0; overflow: hidden; border-radius: 14px;">
            <div
                style="padding: 16px 20px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; background: rgba(255,255,255,0.02);">
                <div style="font-weight: 700; font-size: 0.95rem; color: var(--text-color);">
                    <i class="fas fa-clipboard-check text-warning me-2"></i> Log Pemeliharaan &amp; Pengadaan Terkini
                </div>
            </div>

            <div style="padding: 18px 20px; display: flex; flex-direction: column; gap: 12px;">
                <div style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 10px; border-bottom: 1px solid rgba(255,255,255,0.05);">
                    <div>
                        <div style="font-size: 0.84rem; font-weight: 600; color: var(--text-color);">Pengecekan Rutin Proyektor &amp; Audio Lab Komputer</div>
                        <div style="font-size: 0.74rem; color: var(--text-muted);">Gedung Praktik Kejuruan &bull; Dilaporkan: 12 Sep 2026</div>
                    </div>
                    <span class="badge badge-success" style="font-size: 0.74rem;">Selesai Diperiksa</span>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 10px; border-bottom: 1px solid rgba(255,255,255,0.05);">
                    <div>
                        <div style="font-size: 0.84rem; font-weight: 600; color: var(--text-color);">Pengadaan Kertas HVS &amp; Tinta Printer Kantor TAS</div>
                        <div style="font-size: 0.74rem; color: var(--text-muted);">Ruang Tata Usaha &bull; Dilaporkan: 08 Sep 2026</div>
                    </div>
                    <span class="badge badge-primary" style="font-size: 0.74rem;">Sudah Didistribusikan</span>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <div style="font-size: 0.84rem; font-weight: 600; color: var(--text-color);">Perbaikan Instalasi Stop Kontak Ruang Kelas X ATPH 2</div>
                        <div style="font-size: 0.74rem; color: var(--text-muted);">Gedung Belajar B &bull; Dilaporkan: 04 Sep 2026</div>
                    </div>
                    <span class="badge badge-warning" style="font-size: 0.74rem;">Proses Teknisi</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column: Akses Cepat Sarpras -->
    <div style="display: flex; flex-direction: column; gap: 20px;">
        <div class="card" style="padding: 20px; border-radius: 14px;">
            <div style="font-weight: 700; font-size: 0.95rem; color: var(--text-color); margin-bottom: 14px; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-bolt text-warning"></i> Menu Operasional Sarpras
            </div>
            <div style="display: flex; flex-direction: column; gap: 8px;">
                <a href="{{ route('dashboard.rombel.index') }}" class="btn btn-outline"
                    style="display: flex; justify-content: space-between; align-items: center; padding: 11px 14px; font-size: 0.82rem; border-radius: 8px; text-align: left;">
                    <span><i class="fas fa-door-open text-primary me-2"></i> Pemetaan Ruang Belajar</span>
                    <i class="fas fa-chevron-right text-muted" style="font-size: 0.72rem;"></i>
                </a>
                <a href="{{ route('dashboard.identitas-sekolah.index') }}" class="btn btn-outline"
                    style="display: flex; justify-content: space-between; align-items: center; padding: 11px 14px; font-size: 0.82rem; border-radius: 8px; text-align: left;">
                    <span><i class="fas fa-school text-info me-2"></i> Identitas Fasilitas Sekolah</span>
                    <i class="fas fa-chevron-right text-muted" style="font-size: 0.72rem;"></i>
                </a>
                <a href="{{ route('dashboard.persuratan.index') }}" class="btn btn-outline"
                    style="display: flex; justify-content: space-between; align-items: center; padding: 11px 14px; font-size: 0.82rem; border-radius: 8px; text-align: left;">
                    <span><i class="fas fa-file-contract text-warning me-2"></i> Berkas Berita Acara Sarpras</span>
                    <i class="fas fa-chevron-right text-muted" style="font-size: 0.72rem;"></i>
                </a>
            </div>
        </div>

        <div class="card" style="padding: 20px; border-radius: 14px; background: linear-gradient(135deg, rgba(59,130,246,0.08) 0%, rgba(139,92,246,0.05) 100%); border: 1px solid rgba(59,130,246,0.2);">
            <div style="font-weight: 700; font-size: 0.9rem; color: var(--text-color); margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-clipboard-list text-primary"></i> Standar Aset &amp; KIB Sekolah
            </div>
            <p style="font-size: 0.82rem; color: var(--text-muted); line-height: 1.5; margin: 0 0 12px 0;">
                Seluruh ruangan dan sarana pembelajaran terdaftar dalam basis data Dapodik untuk mendukung akreditasi serta kelayakan standar sarana prasarana SMK.
            </p>
            <div style="display: flex; justify-content: space-between; font-size: 0.8rem;">
                <span style="color: var(--text-muted);">Status Inventaris:</span>
                <strong style="color: #10b981;">Terverifikasi</strong>
            </div>
        </div>
    </div>
</div>
