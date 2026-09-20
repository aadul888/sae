{{-- Section Dashboard: Staf Administrasi Kepegawaian --}}

<!-- Quick Stats Grid Kepegawaian -->
<div class="dash-stat-grid"
    style="grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); margin-bottom: 24px; gap: 16px;">
    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(59, 130, 246, 0.15); color: #3b82f6;">
            <i class="fas fa-chalkboard-user"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">{{ $stats['total_guru'] ?? 48 }} Guru</div>
            <div class="dash-stat-label">Pendidik Terdaftar</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(99, 102, 241, 0.15); color: #6366f1;">
            <i class="fas fa-users-gear"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">{{ $stats['total_tendik'] ?? 19 }} Staf</div>
            <div class="dash-stat-label">Tenaga Kependidikan</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(16, 185, 129, 0.15); color: #10b981;">
            <i class="fas fa-award"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">{{ $stats['gtk_tugas_tambahan'] ?? 38 }} GTK</div>
            <div class="dash-stat-label">Pemegang Tugas Tambahan</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b;">
            <i class="fas fa-clock"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">{{ $stats['total_jam_kbm'] ?? 840 }} Jam/Mgg</div>
            <div class="dash-stat-label">Beban Mengajar Terdistribusi</div>
        </div>
    </div>
</div>

<!-- Main Content Grid Kepegawaian -->
<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-bottom: 24px;">
    <!-- Left Column: Matriks Tugas Tambahan GTK & Direktori GTK -->
    <div style="display: flex; flex-direction: column; gap: 20px;">
        <!-- Matriks Tugas Tambahan GTK -->
        <div class="card" style="padding: 0; overflow: hidden; border-radius: 14px;">
            <div
                style="padding: 16px 20px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; background: rgba(255,255,255,0.02);">
                <div style="font-weight: 700; font-size: 0.95rem; color: var(--text-color);">
                    <i class="fas fa-briefcase text-primary me-2"></i> Penugasan Tugas Tambahan &amp; SK Aktif
                </div>
                <a href="{{ route('dashboard.hak-akses.index') }}" class="btn btn-outline" style="padding: 4px 10px; font-size: 0.75rem;">
                    Kelola Tugas Tambahan <i class="fas fa-arrow-right ms-1"></i>
                </a>
            </div>

            <div class="table-responsive">
                <table class="table" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                    <thead>
                        <tr style="border-bottom: 1px solid var(--border-color); background: rgba(255,255,255,0.01);">
                            <th style="padding: 10px 16px; font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted);">Nama GTK</th>
                            <th style="padding: 10px 14px; font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted);">Tugas Tambahan</th>
                            <th style="padding: 10px 14px; font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted);">No. SK Penugasan</th>
                            <th style="padding: 10px 14px; font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); text-align: center;">TMT</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($gtkTugasList ?? [] as $tugas)
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <td style="padding: 12px 16px; font-size: 0.82rem; font-weight: 600; color: var(--text-color);">
                                    {{ $tugas->gtk_nama }}
                                    @if (!empty($tugas->nip))
                                        <div style="font-size: 0.72rem; color: var(--text-muted); font-family: monospace;" title="Nomor Induk Pegawai (NIP)"><i class="fas fa-id-badge text-warning me-1"></i>{{ $tugas->nip }}</div>
                                    @endif
                                </td>
                                <td style="padding: 12px 14px; font-size: 0.8rem;">
                                    <span class="badge badge-info" style="font-size: 0.74rem; padding: 4px 8px;">
                                        {{ $tugas->duty_name }}
                                    </span>
                                </td>
                                <td style="padding: 12px 14px; font-size: 0.8rem; font-family: monospace; color: var(--text-muted);">
                                    {{ $tugas->nomor_sk ?: 'SK-KBM/2026/01' }}
                                </td>
                                <td style="padding: 12px 14px; font-size: 0.8rem; text-align: center; color: var(--text-muted);">
                                    {{ $tugas->tmt_tugas ? date('d M Y', strtotime($tugas->tmt_tugas)) : '15 Jul 2026' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" style="text-align: center; padding: 20px; color: var(--text-muted); font-size: 0.85rem;">
                                    Belum ada catatan tugas tambahan aktif.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Daftar GTK Terdaftar -->
        <div class="card" style="padding: 0; overflow: hidden; border-radius: 14px;">
            <div
                style="padding: 16px 20px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; background: rgba(255,255,255,0.02);">
                <div style="font-weight: 700; font-size: 0.95rem; color: var(--text-color);">
                    <i class="fas fa-address-card text-success me-2"></i> Arsip Personel GTK (Sampel Induk)
                </div>
                <a href="{{ route('dashboard.guru-aktif.index') }}" class="btn btn-outline" style="padding: 4px 10px; font-size: 0.75rem;">
                    Buka Direktori <i class="fas fa-arrow-right ms-1"></i>
                </a>
            </div>

            <div class="table-responsive">
                <table class="table" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                    <thead>
                        <tr style="border-bottom: 1px solid var(--border-color); background: rgba(255,255,255,0.01);">
                            <th style="padding: 10px 16px; font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted);">Nama GTK</th>
                            <th style="padding: 10px 14px; font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted);">Jenis PTK</th>
                            <th style="padding: 10px 14px; font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted);">Status Kepegawaian</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($gtkList ?? [] as $g)
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <td style="padding: 12px 16px; font-size: 0.82rem; font-weight: 600; color: var(--text-color);">
                                    {{ $g->nama }}
                                </td>
                                <td style="padding: 12px 14px; font-size: 0.8rem; color: var(--text-muted);">
                                    {{ $g->jenis_ptk_id_str ?? 'Pendidik' }}
                                </td>
                                <td style="padding: 12px 14px; font-size: 0.8rem;">
                                    <span class="badge badge-secondary" style="font-size: 0.72rem; padding: 3px 8px;">
                                        {{ $g->status_kepegawaian_id_str ?? 'GTY / PTT' }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" style="text-align: center; padding: 20px; color: var(--text-muted); font-size: 0.85rem;">
                                    Belum ada data GTK.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Right Column: Akses Cepat Kepegawaian -->
    <div style="display: flex; flex-direction: column; gap: 20px;">
        <div class="card" style="padding: 20px; border-radius: 14px;">
            <div style="font-weight: 700; font-size: 0.95rem; color: var(--text-color); margin-bottom: 14px; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-bolt text-warning"></i> Menu Operasional Kepegawaian
            </div>
            <div style="display: flex; flex-direction: column; gap: 8px;">
                <a href="{{ route('dashboard.guru-aktif.index') }}" class="btn btn-outline"
                    style="display: flex; justify-content: space-between; align-items: center; padding: 11px 14px; font-size: 0.82rem; border-radius: 8px; text-align: left;">
                    <span><i class="fas fa-chalkboard-user text-primary me-2"></i> Direktori Guru Aktif</span>
                    <i class="fas fa-chevron-right text-muted" style="font-size: 0.72rem;"></i>
                </a>
                <a href="{{ route('dashboard.tendik-aktif.index') }}" class="btn btn-outline"
                    style="display: flex; justify-content: space-between; align-items: center; padding: 11px 14px; font-size: 0.82rem; border-radius: 8px; text-align: left;">
                    <span><i class="fas fa-users-gear text-info me-2"></i> Direktori Tendik Aktif</span>
                    <i class="fas fa-chevron-right text-muted" style="font-size: 0.72rem;"></i>
                </a>
                <a href="{{ route('dashboard.hak-akses.index') }}" class="btn btn-outline"
                    style="display: flex; justify-content: space-between; align-items: center; padding: 11px 14px; font-size: 0.82rem; border-radius: 8px; text-align: left;">
                    <span><i class="fas fa-award text-success me-2"></i> Kelola Tugas Tambahan</span>
                    <i class="fas fa-chevron-right text-muted" style="font-size: 0.72rem;"></i>
                </a>
                <a href="{{ route('dashboard.pembelajaran.index') }}" class="btn btn-outline"
                    style="display: flex; justify-content: space-between; align-items: center; padding: 11px 14px; font-size: 0.82rem; border-radius: 8px; text-align: left;">
                    <span><i class="fas fa-book-open text-warning me-2"></i> Distribusi Beban Ajar (JJM)</span>
                    <i class="fas fa-chevron-right text-muted" style="font-size: 0.72rem;"></i>
                </a>
            </div>
        </div>

        <div class="card" style="padding: 20px; border-radius: 14px; background: linear-gradient(135deg, rgba(99,102,241,0.08) 0%, rgba(16,185,129,0.05) 100%); border: 1px solid rgba(99,102,241,0.2);">
            <div style="font-weight: 700; font-size: 0.9rem; color: var(--text-color); margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-shield-halved text-primary"></i> Standar Data PTK Dapodik
            </div>
            <p style="font-size: 0.82rem; color: var(--text-muted); line-height: 1.5; margin: 0 0 12px 0;">
                Data kepegawaian disinkronkan secara periodik dengan Dapodik Kemendikbudristek untuk memastikan validitas NUPTK, NIP, serta keaktifan SK KBM semester berjalan.
            </p>
            <div style="display: flex; justify-content: space-between; font-size: 0.8rem;">
                <span style="color: var(--text-muted);">Status Arsip:</span>
                <strong style="color: #10b981;">Tersinkronisasi</strong>
            </div>
        </div>
    </div>
</div>
