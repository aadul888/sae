{{-- Section Dashboard: Staf Administrasi Kepegawaian --}}

<!-- Quick Stats Grid Kepegawaian (Responsive) -->
<div class="dash-stat-grid" style="margin-bottom: 24px; gap: 14px;">
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
            <div class="dash-stat-label">Tugas Tambahan</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b;">
            <i class="fas fa-clock"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">{{ $stats['total_jam_kbm'] ?? 840 }} JP</div>
            <div class="dash-stat-label">Beban KBM Terdistribusi</div>
        </div>
    </div>
</div>

<!-- Main Content Grid Kepegawaian (Responsive: 2fr 1fr desktop, 1fr mobile) -->
<div class="dash-layout-grid">
    <!-- Left Column: Matriks Tugas Tambahan GTK & Direktori GTK -->
    <div style="display: flex; flex-direction: column; gap: 20px; min-width: 0;">
        <!-- Matriks Tugas Tambahan GTK -->
        <div class="card" style="padding: 0; overflow: hidden; border-radius: 14px; border: 1px solid var(--border-color); background: var(--card-bg);">
            <div style="padding: 14px 18px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; background: rgba(255,255,255,0.02); flex-wrap: wrap; gap: 8px;">
                <div style="font-weight: 700; font-size: 0.92rem; color: var(--text-color); display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-briefcase text-primary"></i> Penugasan Tugas Tambahan
                </div>
                <a href="{{ route('dashboard.hak-akses.index') }}" class="btn btn-outline btn-icon" style="width: 30px; height: 30px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px;" title="Kelola Tugas Tambahan">
                    <i class="fas fa-arrow-right" style="font-size: 0.76rem;"></i>
                </a>
            </div>

            <div class="table-responsive-stack" style="overflow-x: auto; -webkit-overflow-scrolling: touch; margin: 0;">
                <table class="table-minimal-compact">
                    <thead>
                        <tr>
                            <th style="min-width: 140px;">Staf GTK</th>
                            <th style="min-width: 130px;">Tugas Tambahan</th>
                            <th style="min-width: 110px;">No. SK</th>
                            <th style="min-width: 80px; text-align: center;">TMT</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($gtkTugasList ?? [] as $tugas)
                            <tr>
                                <td>
                                    <div style="font-weight: 700; color: var(--text-color); font-size: 0.84rem;">
                                        {{ $tugas->gtk_nama }}
                                    </div>
                                    @if (!empty($tugas->nip))
                                        <div style="font-size: 0.7rem; color: var(--text-muted); font-family: monospace;" title="NIP: {{ $tugas->nip }}">
                                            <i class="fas fa-fingerprint text-warning me-1"></i>{{ $tugas->nip }}
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge-compact" style="background: rgba(99,102,241,0.12); color: #6366f1;" title="{{ $tugas->duty_name }}">
                                        <i class="fas fa-award"></i> {{ \Illuminate\Support\Str::limit($tugas->duty_name, 20) }}
                                    </span>
                                </td>
                                <td>
                                    <span style="font-size: 0.76rem; font-family: monospace; color: var(--text-muted);">
                                        {{ $tugas->nomor_sk ?: '-' }}
                                    </span>
                                </td>
                                <td style="text-align: center;">
                                    <span style="font-size: 0.76rem; color: var(--text-muted);">
                                        {{ $tugas->tmt_tugas ? date('d/m/y', strtotime($tugas->tmt_tugas)) : '-' }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" style="text-align: center; padding: 22px; color: var(--text-muted); font-size: 0.82rem;">
                                    <i class="fas fa-briefcase me-1"></i> Belum ada catatan tugas tambahan aktif.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Daftar GTK Terdaftar -->
        <div class="card" style="padding: 0; overflow: hidden; border-radius: 14px; border: 1px solid var(--border-color); background: var(--card-bg);">
            <div style="padding: 14px 18px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; background: rgba(255,255,255,0.02); flex-wrap: wrap; gap: 8px;">
                <div style="font-weight: 700; font-size: 0.92rem; color: var(--text-color); display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-address-card text-success"></i> Personel GTK (Sampel Induk)
                </div>
                <a href="{{ route('dashboard.guru-aktif.index') }}" class="btn btn-outline btn-icon" style="width: 30px; height: 30px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px;" title="Buka Direktori Guru">
                    <i class="fas fa-arrow-right" style="font-size: 0.76rem;"></i>
                </a>
            </div>

            <div class="table-responsive-stack" style="overflow-x: auto; -webkit-overflow-scrolling: touch; margin: 0;">
                <table class="table-minimal-compact">
                    <thead>
                        <tr>
                            <th style="min-width: 140px;">Nama Personel</th>
                            <th style="min-width: 110px;">Jenis PTK</th>
                            <th style="min-width: 100px; text-align: center;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($gtkList ?? [] as $g)
                            <tr>
                                <td>
                                    <div style="font-weight: 700; color: var(--text-color); font-size: 0.84rem;">
                                        {{ $g->nama }}
                                    </div>
                                </td>
                                <td>
                                    <span class="badge-compact" style="background: rgba(59,130,246,0.1); color: #3b82f6;" title="{{ $g->jenis_ptk_id_str ?? 'Pendidik' }}">
                                        <i class="fas fa-chalkboard-user"></i> {{ \Illuminate\Support\Str::limit($g->jenis_ptk_id_str ?? 'Pendidik', 16) }}
                                    </span>
                                </td>
                                <td style="text-align: center;">
                                    <span class="badge-compact" style="background: rgba(16,185,129,0.12); color: #10b981;" title="{{ $g->status_kepegawaian_id_str ?? 'GTY / PTT' }}">
                                        <i class="fas fa-id-badge"></i> {{ \Illuminate\Support\Str::limit($g->status_kepegawaian_id_str ?? 'Aktif', 12) }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" style="text-align: center; padding: 22px; color: var(--text-muted); font-size: 0.82rem;">
                                    <i class="fas fa-user-slash me-1"></i> Belum ada data GTK.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Right Column: Akses Cepat Kepegawaian (Stack di Mobile) -->
    <div style="display: flex; flex-direction: column; gap: 20px; min-width: 0;">
        <div class="card" style="padding: 18px; border-radius: 14px; border: 1px solid var(--border-color); background: var(--card-bg);">
            <div style="font-weight: 700; font-size: 0.9rem; color: var(--text-color); margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-bolt text-warning"></i> Menu Kepegawaian
            </div>
            <div style="display: flex; flex-direction: column; gap: 8px;">
                <a href="{{ route('dashboard.guru-aktif.index') }}" class="btn btn-outline"
                    style="display: flex; justify-content: space-between; align-items: center; padding: 9px 12px; font-size: 0.8rem; border-radius: 8px; text-decoration: none;">
                    <span><i class="fas fa-chalkboard-user text-primary me-2"></i> Direktori Guru</span>
                    <i class="fas fa-chevron-right text-muted" style="font-size: 0.7rem;"></i>
                </a>
                <a href="{{ route('dashboard.tendik-aktif.index') }}" class="btn btn-outline"
                    style="display: flex; justify-content: space-between; align-items: center; padding: 9px 12px; font-size: 0.8rem; border-radius: 8px; text-decoration: none;">
                    <span><i class="fas fa-users-gear text-info me-2"></i> Direktori Tendik</span>
                    <i class="fas fa-chevron-right text-muted" style="font-size: 0.7rem;"></i>
                </a>
                <a href="{{ route('dashboard.hak-akses.index') }}" class="btn btn-outline"
                    style="display: flex; justify-content: space-between; align-items: center; padding: 9px 12px; font-size: 0.8rem; border-radius: 8px; text-decoration: none;">
                    <span><i class="fas fa-award text-success me-2"></i> Tugas Tambahan</span>
                    <i class="fas fa-chevron-right text-muted" style="font-size: 0.7rem;"></i>
                </a>
                <a href="{{ route('dashboard.pembelajaran.index') }}" class="btn btn-outline"
                    style="display: flex; justify-content: space-between; align-items: center; padding: 9px 12px; font-size: 0.8rem; border-radius: 8px; text-decoration: none;">
                    <span><i class="fas fa-book-open text-warning me-2"></i> Beban Ajar (JJM)</span>
                    <i class="fas fa-chevron-right text-muted" style="font-size: 0.7rem;"></i>
                </a>
            </div>
        </div>

        <div class="card" style="padding: 16px; border-radius: 14px; background: linear-gradient(135deg, rgba(99,102,241,0.06) 0%, rgba(16,185,129,0.04) 100%); border: 1px solid rgba(99,102,241,0.18);">
            <div style="font-weight: 700; font-size: 0.86rem; color: var(--text-color); margin-bottom: 10px; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-shield-halved text-primary"></i> Sinkronisasi Dapodik
            </div>
            <p style="font-size: 0.78rem; color: var(--text-muted); line-height: 1.45; margin: 0 0 10px 0;">
                Data kepegawaian sinkron dengan Dapodik Kemendikbudristek untuk NUPTK, NIP, &amp; SK semester berjalan.
            </p>
            <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.78rem;">
                <span style="color: var(--text-muted);">Status Arsip:</span>
                <span class="badge badge-success" style="font-size: 0.68rem; padding: 2px 6px;">Tersinkronisasi</span>
            </div>
        </div>
    </div>
</div>
