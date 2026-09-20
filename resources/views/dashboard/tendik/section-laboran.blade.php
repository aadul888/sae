{{-- Section Dashboard: Laboran (Staf Khusus Ruang Laboratorium) --}}

<!-- Quick Stats Grid Laboran -->
<div class="dash-stat-grid"
    style="grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); margin-bottom: 24px; gap: 16px;">
    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(16, 185, 129, 0.15); color: #10b981;">
            <i class="fas fa-flask"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">6 Ruangan</div>
            <div class="dash-stat-label">Laboratorium &amp; Bengkel</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(59, 130, 246, 0.15); color: #3b82f6;">
            <i class="fas fa-clock"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">{{ $stats['jam_praktik'] ?? 48 }} Jam/Mgg</div>
            <div class="dash-stat-label">Jadwal Praktik Kejuruan</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b;">
            <i class="fas fa-microscope"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">96% Siap</div>
            <div class="dash-stat-label">Kondisi Alat Praktik</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(99, 102, 241, 0.15); color: var(--primary);">
            <i class="fas fa-shield-virus"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value" style="font-size: 1.05rem;">Aman &amp; Tertib</div>
            <div class="dash-stat-label">Standar K3 Laboratorium</div>
        </div>
    </div>
</div>

<!-- Main Content Grid Laboran -->
<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-bottom: 24px;">
    <!-- Left Column: Jadwal Penggunaan Lab & Checklist Kesiapan -->
    <div style="display: flex; flex-direction: column; gap: 20px;">
        <div class="card" style="padding: 0; overflow: hidden; border-radius: 14px;">
            <div
                style="padding: 16px 20px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; background: rgba(255,255,255,0.02);">
                <div style="font-weight: 700; font-size: 0.95rem; color: var(--text-color);">
                    <i class="fas fa-calendar-day text-primary me-2"></i> Jadwal Pemakaian Laboratorium &amp; Mapel Praktik
                </div>
                <span class="badge badge-info" style="font-size: 0.72rem; padding: 4px 8px;">
                    Semester {{ \App\Support\SemesterHelper::getActiveSemesterLabel() }}
                </span>
            </div>

            <div class="table-responsive">
                <table class="table" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                    <thead>
                        <tr style="border-bottom: 1px solid var(--border-color); background: rgba(255,255,255,0.01);">
                            <th style="padding: 10px 16px; font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted);">Mata Pelajaran Praktik</th>
                            <th style="padding: 10px 14px; font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted);">Kelas / Rombel</th>
                            <th style="padding: 10px 14px; font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted);">Guru Pengampu</th>
                            <th style="padding: 10px 16px; font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); text-align: center;">Beban JJM</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($jadwalLab ?? [] as $jb)
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <td style="padding: 12px 16px; font-size: 0.82rem; font-weight: 600; color: var(--text-color);">
                                    <i class="fas fa-vial text-info me-1"></i> {{ $jb->nama_mata_pelajaran }}
                                </td>
                                <td style="padding: 12px 14px; font-size: 0.8rem; color: var(--text-muted);">
                                    {{ $jb->nama_rombel }}
                                </td>
                                <td style="padding: 12px 14px; font-size: 0.8rem; color: var(--text-color);">
                                    {{ $jb->nama_guru ?: '-' }}
                                </td>
                                <td style="padding: 12px 16px; text-align: center;">
                                    <span class="badge badge-primary" style="font-size: 0.76rem; padding: 4px 8px;">
                                        {{ $jb->jam_mengajar_per_minggu }} JP
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" style="text-align: center; padding: 20px; color: var(--text-muted); font-size: 0.85rem;">
                                    Belum ada jadwal pemakaian laboratorium.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Standar K3 & Checklist Kesiapan Ruang Lab -->
        <div class="card" style="padding: 0; overflow: hidden; border-radius: 14px;">
            <div
                style="padding: 16px 20px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; background: rgba(255,255,255,0.02);">
                <div style="font-weight: 700; font-size: 0.95rem; color: var(--text-color);">
                    <i class="fas fa-shield-halved text-success me-2"></i> Checklist Kesiapan &amp; Keselamatan Kerja (K3)
                </div>
            </div>

            <div style="padding: 18px 20px; display: flex; flex-direction: column; gap: 12px;">
                <div style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 10px; border-bottom: 1px solid rgba(255,255,255,0.05);">
                    <div>
                        <div style="font-size: 0.84rem; font-weight: 600; color: var(--text-color);">Pemeriksaan APAR &amp; Kotak P3K Laboratorium</div>
                        <div style="font-size: 0.74rem; color: var(--text-muted);">Tekanan tabung dalam batas hijau, obat P3K tersegel lengkap</div>
                    </div>
                    <span class="badge badge-success" style="font-size: 0.74rem;">Siap Operasi</span>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 10px; border-bottom: 1px solid rgba(255,255,255,0.05);">
                    <div>
                        <div style="font-size: 0.84rem; font-weight: 600; color: var(--text-color);">Kelistrikan &amp; Jalur Grounding Meja Praktik</div>
                        <div style="font-size: 0.74rem; color: var(--text-muted);">MCB stabil, tidak ada kabel terkelupas</div>
                    </div>
                    <span class="badge badge-success" style="font-size: 0.74rem;">Normal</span>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <div style="font-size: 0.84rem; font-weight: 600; color: var(--text-color);">Kebersihan Meja &amp; Penyimpanan Alat Praktik</div>
                        <div style="font-size: 0.74rem; color: var(--text-muted);">Peralatan tertata rapi di lemari alat dan siap digunakan siswa</div>
                    </div>
                    <span class="badge badge-primary" style="font-size: 0.74rem;">Terverifikasi</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column: Akses Cepat Laboran -->
    <div style="display: flex; flex-direction: column; gap: 20px;">
        <div class="card" style="padding: 20px; border-radius: 14px;">
            <div style="font-weight: 700; font-size: 0.95rem; color: var(--text-color); margin-bottom: 14px; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-bolt text-warning"></i> Menu Operasional Laboran
            </div>
            <div style="display: flex; flex-direction: column; gap: 8px;">
                <a href="{{ route('dashboard.agenda-kbm.index') }}" class="btn btn-outline"
                    style="display: flex; justify-content: space-between; align-items: center; padding: 11px 14px; font-size: 0.82rem; border-radius: 8px; text-align: left;">
                    <span><i class="fas fa-calendar-check text-primary me-2"></i> Log Agenda KBM Praktek</span>
                    <i class="fas fa-chevron-right text-muted" style="font-size: 0.72rem;"></i>
                </a>
                <a href="{{ route('dashboard.pembelajaran.index') }}" class="btn btn-outline"
                    style="display: flex; justify-content: space-between; align-items: center; padding: 11px 14px; font-size: 0.82rem; border-radius: 8px; text-align: left;">
                    <span><i class="fas fa-book-open text-info me-2"></i> Jadwal Mata Pelajaran</span>
                    <i class="fas fa-chevron-right text-muted" style="font-size: 0.72rem;"></i>
                </a>
                <a href="{{ route('dashboard.kompetensi-keahlian.index') }}" class="btn btn-outline"
                    style="display: flex; justify-content: space-between; align-items: center; padding: 11px 14px; font-size: 0.82rem; border-radius: 8px; text-align: left;">
                    <span><i class="fas fa-screwdriver-wrench text-success me-2"></i> Kompetensi Keahlian</span>
                    <i class="fas fa-chevron-right text-muted" style="font-size: 0.72rem;"></i>
                </a>
            </div>
        </div>

        <div class="card" style="padding: 20px; border-radius: 14px; background: linear-gradient(135deg, rgba(16,185,129,0.08) 0%, rgba(6,182,212,0.05) 100%); border: 1px solid rgba(16,185,129,0.2);">
            <div style="font-weight: 700; font-size: 0.9rem; color: var(--text-color); margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-flask-vial text-success"></i> Tata Tertib Penggunaan Lab
            </div>
            <p style="font-size: 0.82rem; color: var(--text-muted); line-height: 1.5; margin: 0 0 12px 0;">
                Siswa dan guru wajib mengisi buku register pemakaian ruang praktik, mematuhi instruksi keselamatan, dan mengembalikan peralatan ke tempat semula dalam keadaan bersih.
            </p>
            <div style="display: flex; justify-content: space-between; font-size: 0.8rem;">
                <span style="color: var(--text-muted);">Status Ruang Praktik:</span>
                <strong style="color: #10b981;">Siap Pakai</strong>
            </div>
        </div>
    </div>
</div>
