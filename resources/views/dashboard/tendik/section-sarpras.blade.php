{{-- Section Dashboard: Staf Administrasi Sarpras --}}

<!-- Quick Stats Grid Sarpras (Responsive) -->
<div class="dash-stat-grid" style="margin-bottom: 24px; gap: 14px;">
    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(59, 130, 246, 0.15); color: #3b82f6;">
            <i class="fas fa-building"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">{{ $stats['total_ruangan'] ?? 33 }} Ruang</div>
            <div class="dash-stat-label">Kelas &amp; Teori</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(16, 185, 129, 0.15); color: #10b981;">
            <i class="fas fa-flask"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">6 Unit</div>
            <div class="dash-stat-label">Lab &amp; Bengkel</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b;">
            <i class="fas fa-boxes-stacked"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">148 Unit</div>
            <div class="dash-stat-label">Sarana Aset</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(139, 92, 246, 0.15); color: #8b5cf6;">
            <i class="fas fa-screwdriver-wrench"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">98%</div>
            <div class="dash-stat-label">Laik Fasilitas</div>
        </div>
    </div>
</div>

<!-- Main Content Grid Sarpras (Responsive: 2fr 1fr desktop, 1fr mobile) -->
<div class="dash-layout-grid">
    <!-- Left Column: Daftar Ruangan & Status Fasilitas -->
    <div style="display: flex; flex-direction: column; gap: 20px; min-width: 0;">
        <div class="card" style="padding: 0; overflow: hidden; border-radius: 14px; border: 1px solid var(--border-color); background: var(--card-bg);">
            <div style="padding: 14px 18px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; background: rgba(255,255,255,0.02); flex-wrap: wrap; gap: 8px;">
                <div style="font-weight: 700; font-size: 0.92rem; color: var(--text-color); display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-door-open text-primary"></i> Pemantauan Ruang Kelas
                </div>
                <a href="{{ route('dashboard.rombel.index') }}" class="btn btn-outline btn-icon" style="width: 30px; height: 30px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px;" title="Lihat Rombel">
                    <i class="fas fa-arrow-right" style="font-size: 0.76rem;"></i>
                </a>
            </div>

            <div class="table-responsive-stack" style="overflow-x: auto; -webkit-overflow-scrolling: touch; margin: 0;">
                <table class="table-minimal-compact">
                    <thead>
                        <tr>
                            <th style="min-width: 130px;">Rombel / Kelas</th>
                            <th style="min-width: 100px;">Ruang</th>
                            <th style="min-width: 90px; text-align: center;">Fasilitas</th>
                            <th style="min-width: 80px; text-align: center;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($ruangList ?? [] as $rg)
                            <tr>
                                <td>
                                    <div style="font-weight: 700; color: var(--text-color); font-size: 0.84rem;">
                                        {{ $rg->nama_rombel }}
                                    </div>
                                </td>
                                <td>
                                    <span class="badge-compact" style="background: rgba(59,130,246,0.1); color: #3b82f6;" title="{{ $rg->ruang }}">
                                        <i class="fas fa-tag"></i> {{ \Illuminate\Support\Str::limit($rg->ruang, 14) }}
                                    </span>
                                </td>
                                <td style="text-align: center;">
                                    <span class="badge-compact" style="background: rgba(16,185,129,0.1); color: #10b981;" title="36 Set Lengkap Meja & Kursi">
                                        <i class="fas fa-chair"></i> 36 Set
                                    </span>
                                </td>
                                <td style="text-align: center;">
                                    <span class="badge-compact" style="background: rgba(16,185,129,0.12); color: #10b981;" title="Siap Pakai">
                                        <i class="fas fa-circle-check"></i>
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" style="text-align: center; padding: 22px; color: var(--text-muted); font-size: 0.82rem;">
                                    <i class="fas fa-building-circle-exclamation me-1"></i> Belum ada data ruangan kelas.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Log Pengadaan & Pemeliharaan Sarpras -->
        <div class="card" style="padding: 0; overflow: hidden; border-radius: 14px; border: 1px solid var(--border-color); background: var(--card-bg);">
            <div style="padding: 14px 18px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; background: rgba(255,255,255,0.02); flex-wrap: wrap; gap: 8px;">
                <div style="font-weight: 700; font-size: 0.92rem; color: var(--text-color); display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-clipboard-check text-warning"></i> Log Pemeliharaan Fasilitas
                </div>
            </div>

            <div style="padding: 14px 18px; display: flex; flex-direction: column; gap: 10px;">
                <div style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 8px; border-bottom: 1px solid rgba(255,255,255,0.05); gap: 10px;">
                    <div style="min-width: 0; flex: 1;">
                        <div style="font-size: 0.82rem; font-weight: 600; color: var(--text-color); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                            <i class="fas fa-video text-primary me-1"></i> Cek Proyektor &amp; Audio Lab Komputer
                        </div>
                        <div style="font-size: 0.7rem; color: var(--text-muted);">Lab Komputer &bull; 12 Sep 2026</div>
                    </div>
                    <span class="badge-compact" style="background: rgba(16,185,129,0.12); color: #10b981;">
                        <i class="fas fa-check"></i> Selesai
                    </span>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 8px; border-bottom: 1px solid rgba(255,255,255,0.05); gap: 10px;">
                    <div style="min-width: 0; flex: 1;">
                        <div style="font-size: 0.82rem; font-weight: 600; color: var(--text-color); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                            <i class="fas fa-print text-info me-1"></i> Pengadaan Kertas HVS &amp; Tinta Kantor TAS
                        </div>
                        <div style="font-size: 0.7rem; color: var(--text-muted);">Ruang TAS &bull; 08 Sep 2026</div>
                    </div>
                    <span class="badge-compact" style="background: rgba(59,130,246,0.12); color: #3b82f6;">
                        <i class="fas fa-truck-ramp-box"></i> Terdistribusi
                    </span>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; gap: 10px;">
                    <div style="min-width: 0; flex: 1;">
                        <div style="font-size: 0.82rem; font-weight: 600; color: var(--text-color); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                            <i class="fas fa-plug text-warning me-1"></i> Perbaikan Stop Kontak Ruang X ATPH 2
                        </div>
                        <div style="font-size: 0.7rem; color: var(--text-muted);">Gedung B &bull; 04 Sep 2026</div>
                    </div>
                    <span class="badge-compact" style="background: rgba(245,158,11,0.12); color: #f59e0b;">
                        <i class="fas fa-wrench"></i> Proses
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column: Akses Cepat Sarpras (Stack di Mobile) -->
    <div style="display: flex; flex-direction: column; gap: 20px; min-width: 0;">
        <div class="card" style="padding: 18px; border-radius: 14px; border: 1px solid var(--border-color); background: var(--card-bg);">
            <div style="font-weight: 700; font-size: 0.9rem; color: var(--text-color); margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-bolt text-warning"></i> Menu Sarpras
            </div>
            <div style="display: flex; flex-direction: column; gap: 8px;">
                <a href="{{ route('dashboard.rombel.index') }}" class="btn btn-outline"
                    style="display: flex; justify-content: space-between; align-items: center; padding: 9px 12px; font-size: 0.8rem; border-radius: 8px; text-decoration: none;">
                    <span><i class="fas fa-door-open text-primary me-2"></i> Pemetaan Ruang Belajar</span>
                    <i class="fas fa-chevron-right text-muted" style="font-size: 0.7rem;"></i>
                </a>
                <a href="{{ route('dashboard.identitas-sekolah.index') }}" class="btn btn-outline"
                    style="display: flex; justify-content: space-between; align-items: center; padding: 9px 12px; font-size: 0.8rem; border-radius: 8px; text-decoration: none;">
                    <span><i class="fas fa-school text-info me-2"></i> Fasilitas Gedung</span>
                    <i class="fas fa-chevron-right text-muted" style="font-size: 0.7rem;"></i>
                </a>
                <a href="{{ route('dashboard.persuratan.index') }}" class="btn btn-outline"
                    style="display: flex; justify-content: space-between; align-items: center; padding: 9px 12px; font-size: 0.8rem; border-radius: 8px; text-decoration: none;">
                    <span><i class="fas fa-file-contract text-warning me-2"></i> Berita Acara Sarpras</span>
                    <i class="fas fa-chevron-right text-muted" style="font-size: 0.7rem;"></i>
                </a>
            </div>
        </div>

        <div class="card" style="padding: 16px; border-radius: 14px; background: linear-gradient(135deg, rgba(59,130,246,0.06) 0%, rgba(139,92,246,0.04) 100%); border: 1px solid rgba(59,130,246,0.18);">
            <div style="font-weight: 700; font-size: 0.86rem; color: var(--text-color); margin-bottom: 10px; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-clipboard-list text-primary"></i> Standar Aset KIB
            </div>
            <p style="font-size: 0.78rem; color: var(--text-muted); line-height: 1.45; margin: 0 0 10px 0;">
                Semua ruangan &amp; sarana pembelajaran terdaftar dalam basis data Dapodik untuk standar kelayakan fasilitas SMK.
            </p>
            <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.78rem;">
                <span style="color: var(--text-muted);">Status Inventaris:</span>
                <span class="badge badge-success" style="font-size: 0.68rem; padding: 2px 6px;">Terverifikasi</span>
            </div>
        </div>
    </div>
</div>
