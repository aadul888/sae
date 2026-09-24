{{-- Section Dashboard: Laboran (Staf Khusus Ruang Laboratorium) --}}

<!-- Quick Stats Grid Laboran (Responsive) -->
<div class="dash-stat-grid" style="margin-bottom: 24px; gap: 14px;">
    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(16, 185, 129, 0.15); color: #10b981;">
            <i class="fas fa-flask"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">6 Ruang</div>
            <div class="dash-stat-label">Laboratorium &amp; Bengkel</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(59, 130, 246, 0.15); color: #3b82f6;">
            <i class="fas fa-clock"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">{{ $stats['jam_praktik'] ?? 48 }} JP</div>
            <div class="dash-stat-label">Praktik Kejuruan/Mgg</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b;">
            <i class="fas fa-microscope"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">96%</div>
            <div class="dash-stat-label">Alat Siap Pakai</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(99, 102, 241, 0.15); color: var(--primary);">
            <i class="fas fa-shield-virus"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">K3 Laik</div>
            <div class="dash-stat-label">Standar Keselamatan</div>
        </div>
    </div>
</div>

<!-- Main Content Grid Laboran (Responsive: 2fr 1fr desktop, 1fr mobile) -->
<div class="dash-layout-grid">
    <!-- Left Column: Jadwal Penggunaan Lab & Checklist Kesiapan -->
    <div style="display: flex; flex-direction: column; gap: 20px; min-width: 0;">
        <div class="card" style="padding: 0; overflow: hidden; border-radius: 14px; border: 1px solid var(--border-color); background: var(--card-bg);">
            <div style="padding: 14px 18px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; background: rgba(255,255,255,0.02); flex-wrap: wrap; gap: 8px;">
                <div style="font-weight: 700; font-size: 0.92rem; color: var(--text-color); display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-calendar-day text-primary"></i> Jadwal Pemakaian Laboratorium
                </div>
                <span class="badge badge-info" style="font-size: 0.7rem; padding: 3px 8px; border-radius: 6px;">
                    Semester {{ \App\Support\SemesterHelper::getActiveSemesterLabel() }}
                </span>
            </div>

            <div class="table-responsive-stack" style="overflow-x: auto; -webkit-overflow-scrolling: touch; margin: 0;">
                <table class="table-minimal-compact">
                    <thead>
                        <tr>
                            <th style="min-width: 140px;">Mapel Praktik</th>
                            <th style="min-width: 90px;">Kelas</th>
                            <th style="min-width: 120px;">Guru Pengampu</th>
                            <th style="min-width: 70px; text-align: center;">JJM</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($jadwalLab ?? [] as $jb)
                            <tr>
                                <td>
                                    <div style="font-weight: 700; color: var(--text-color); font-size: 0.84rem;">
                                        <i class="fas fa-vial text-info me-1"></i> {{ $jb->nama_mata_pelajaran }}
                                    </div>
                                </td>
                                <td>
                                    <span class="badge-compact" style="background: rgba(59,130,246,0.1); color: #3b82f6;">
                                        {{ $jb->nama_rombel }}
                                    </span>
                                </td>
                                <td>
                                    <div style="font-size: 0.8rem; color: var(--text-color); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 150px;" title="{{ $jb->nama_guru ?: '-' }}">
                                        {{ $jb->nama_guru ?: '-' }}
                                    </div>
                                </td>
                                <td style="text-align: center;">
                                    <span class="badge-compact" style="background: rgba(99,102,241,0.12); color: #6366f1;">
                                        {{ $jb->jam_mengajar_per_minggu }} JP
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" style="text-align: center; padding: 22px; color: var(--text-muted); font-size: 0.82rem;">
                                    <i class="fas fa-flask-vial me-1"></i> Belum ada jadwal pemakaian laboratorium.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Checklist Kesiapan Bahan & Alat -->
        <div class="card" style="padding: 0; overflow: hidden; border-radius: 14px; border: 1px solid var(--border-color); background: var(--card-bg);">
            <div style="padding: 14px 18px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; background: rgba(255,255,255,0.02); flex-wrap: wrap; gap: 8px;">
                <div style="font-weight: 700; font-size: 0.92rem; color: var(--text-color); display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-clipboard-check text-success"></i> Kesiapan Fasilitas Lab
                </div>
            </div>

            <div style="padding: 14px 18px; display: flex; flex-direction: column; gap: 10px;">
                <div style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 8px; border-bottom: 1px solid rgba(255,255,255,0.05); gap: 10px;">
                    <div style="min-width: 0; flex: 1;">
                        <div style="font-size: 0.82rem; font-weight: 600; color: var(--text-color);">
                            <i class="fas fa-laptop text-primary me-1"></i> Lab Komputer &amp; Multimedia
                        </div>
                        <div style="font-size: 0.7rem; color: var(--text-muted);">36 PC Klien &bull; LAN Gigabyte &bull; AC Normal</div>
                    </div>
                    <span class="badge-compact" style="background: rgba(16,185,129,0.12); color: #10b981;">
                        <i class="fas fa-check"></i> Siap
                    </span>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 8px; border-bottom: 1px solid rgba(255,255,255,0.05); gap: 10px;">
                    <div style="min-width: 0; flex: 1;">
                        <div style="font-size: 0.82rem; font-weight: 600; color: var(--text-color);">
                            <i class="fas fa-seedling text-success me-1"></i> Ruang Praktik Pertanian / Green House
                        </div>
                        <div style="font-size: 0.7rem; color: var(--text-muted);">Instalasi Hidroponik &bull; Alat Semprot Siap</div>
                    </div>
                    <span class="badge-compact" style="background: rgba(16,185,129,0.12); color: #10b981;">
                        <i class="fas fa-check"></i> Siap
                    </span>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; gap: 10px;">
                    <div style="min-width: 0; flex: 1;">
                        <div style="font-size: 0.82rem; font-weight: 600; color: var(--text-color);">
                            <i class="fas fa-tree text-warning me-1"></i> Bengkel Kehutanan &amp; Pengolahan Kayu
                        </div>
                        <div style="font-size: 0.7rem; color: var(--text-muted);">Peralatan Ukur &bull; APD Keselamatan Kerja Lengkap</div>
                    </div>
                    <span class="badge-compact" style="background: rgba(16,185,129,0.12); color: #10b981;">
                        <i class="fas fa-check"></i> Siap
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column: Akses Cepat Laboran (Stack di Mobile) -->
    <div style="display: flex; flex-direction: column; gap: 20px; min-width: 0;">
        <div class="card" style="padding: 18px; border-radius: 14px; border: 1px solid var(--border-color); background: var(--card-bg);">
            <div style="font-weight: 700; font-size: 0.9rem; color: var(--text-color); margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-bolt text-warning"></i> Menu Laboran
            </div>
            <div style="display: flex; flex-direction: column; gap: 8px;">
                <a href="{{ route('dashboard.sarpras.aset.index') }}" class="btn btn-outline"
                    style="display: flex; justify-content: space-between; align-items: center; padding: 9px 12px; font-size: 0.8rem; border-radius: 8px; text-decoration: none;">
                    <span><i class="fas fa-boxes-stacked text-primary me-2"></i> Inventaris &amp; Aset</span>
                    <i class="fas fa-chevron-right text-muted" style="font-size: 0.7rem;"></i>
                </a>
                <a href="{{ route('dashboard.sarpras.peminjaman.index') }}" class="btn btn-outline"
                    style="display: flex; justify-content: space-between; align-items: center; padding: 9px 12px; font-size: 0.8rem; border-radius: 8px; text-decoration: none;">
                    <span><i class="fas fa-hand-holding text-warning me-2"></i> Peminjaman Sarpras</span>
                    <i class="fas fa-chevron-right text-muted" style="font-size: 0.7rem;"></i>
                </a>
                <a href="{{ route('dashboard.pembelajaran.index') }}" class="btn btn-outline"
                    style="display: flex; justify-content: space-between; align-items: center; padding: 9px 12px; font-size: 0.8rem; border-radius: 8px; text-decoration: none;">
                    <span><i class="fas fa-book-open text-primary me-2"></i> Jadwal Mapel Praktik</span>
                    <i class="fas fa-chevron-right text-muted" style="font-size: 0.7rem;"></i>
                </a>
                <a href="{{ route('dashboard.rombel.index') }}" class="btn btn-outline"
                    style="display: flex; justify-content: space-between; align-items: center; padding: 9px 12px; font-size: 0.8rem; border-radius: 8px; text-decoration: none;">
                    <span><i class="fas fa-door-open text-info me-2"></i> Ruang &amp; Rombel Praktik</span>
                    <i class="fas fa-chevron-right text-muted" style="font-size: 0.7rem;"></i>
                </a>
                <a href="{{ route('dashboard.guru-aktif.index') }}" class="btn btn-outline"
                    style="display: flex; justify-content: space-between; align-items: center; padding: 9px 12px; font-size: 0.8rem; border-radius: 8px; text-decoration: none;">
                    <span><i class="fas fa-chalkboard-user text-success me-2"></i> Guru Pengampu Praktik</span>
                    <i class="fas fa-chevron-right text-muted" style="font-size: 0.7rem;"></i>
                </a>
            </div>
        </div>

        <div class="card" style="padding: 16px; border-radius: 14px; background: linear-gradient(135deg, rgba(16,185,129,0.06) 0%, rgba(59,130,246,0.04) 100%); border: 1px solid rgba(16,185,129,0.18);">
            <div style="font-weight: 700; font-size: 0.86rem; color: var(--text-color); margin-bottom: 10px; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-circle-exclamation text-primary"></i> Standar K3 Laboratorium
            </div>
            <p style="font-size: 0.78rem; color: var(--text-muted); line-height: 1.45; margin: 0 0 10px 0;">
                Setiap pemakaian wajib mencatat log instrumen, menjaga kebersihan meja kerja, dan memastikan kelistrikan mati setelah KBM.
            </p>
            <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.78rem;">
                <span style="color: var(--text-muted);">Status Keamanan:</span>
                <span class="badge badge-success" style="font-size: 0.68rem; padding: 2px 6px;">Terkendali</span>
            </div>
        </div>
    </div>
</div>
