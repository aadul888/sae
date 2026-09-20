{{-- Section Dashboard: Kepala Tenaga Administrasi Sekolah (Kepala TAS / KTU) --}}

<!-- Quick Stats Grid Kepala TAS (Responsive Grid) -->
<div class="dash-stat-grid" style="margin-bottom: 24px; gap: 14px;">
    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(99, 102, 241, 0.15); color: #6366f1;">
            <i class="fas fa-users-gear"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">{{ $stats['total_tendik'] ?? 19 }} Staf</div>
            <div class="dash-stat-label">Tendik Terdaftar</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(59, 130, 246, 0.15); color: #3b82f6;">
            <i class="fas fa-chalkboard-user"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">{{ $stats['total_guru'] ?? 48 }} Guru</div>
            <div class="dash-stat-label">Guru Terdaftar</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(16, 185, 129, 0.15); color: #10b981;">
            <i class="fas fa-file-signature"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">{{ $stats['total_persuratan'] ?? 5 }} Dok</div>
            <div class="dash-stat-label">Total Arsip Surat</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b;">
            <i class="fas fa-user-graduate"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">{{ number_format($stats['total_siswa'] ?? 1126, 0, ',', '.') }}</div>
            <div class="dash-stat-label">Siswa Aktif</div>
        </div>
    </div>
</div>

<!-- Main Content Grid Kepala TAS (Responsive: 2fr 1fr desktop, 1fr mobile) -->
<div class="dash-layout-grid">
    <!-- Left Column: Matriks Pembagian Tugas Staf TAS & Pengawasan Dokumen -->
    <div style="display: flex; flex-direction: column; gap: 20px; min-width: 0;">
        <!-- Matriks Pembagian Tugas Staf TAS -->
        <div class="card" style="padding: 0; overflow: hidden; border-radius: 14px; border: 1px solid var(--border-color); background: var(--card-bg);">
            <div style="padding: 14px 18px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; background: rgba(255,255,255,0.02); flex-wrap: wrap; gap: 8px;">
                <div style="font-weight: 700; font-size: 0.92rem; color: var(--text-color); display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-sitemap text-primary"></i> Matriks Pembagian Tugas Staf TAS
                </div>
                <span class="badge badge-primary" style="font-size: 0.7rem; padding: 3px 8px; border-radius: 6px;">
                    <i class="fas fa-users me-1"></i> {{ count($stafTas ?? []) }} Staf
                </span>
            </div>

            <div class="table-responsive-stack" style="overflow-x: auto; -webkit-overflow-scrolling: touch; margin: 0;">
                <table class="table-minimal-compact">
                    <thead>
                        <tr>
                            <th style="min-width: 140px;">Staf</th>
                            <th style="min-width: 80px; text-align: center;">Jabatan</th>
                            <th style="min-width: 120px;">Tugas Bidang</th>
                            <th style="min-width: 70px; text-align: center;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($stafTas ?? [] as $staf)
                            @php
                                $dutyStr = strtolower($staf->tugas_tambahan ?? '');
                                $dutyBadge = match(true) {
                                    str_contains($dutyStr, 'laboran') => ['label' => 'Laboran', 'icon' => 'fas fa-flask', 'bg' => 'rgba(6,182,212,0.12)', 'color' => '#06b6d4'],
                                    str_contains($dutyStr, 'keamanan') || str_contains($dutyStr, 'satpam') => ['label' => 'Keamanan', 'icon' => 'fas fa-shield-halved', 'bg' => 'rgba(239,68,68,0.12)', 'color' => '#ef4444'],
                                    str_contains($dutyStr, 'kesiswaan') => ['label' => 'Kesiswaan', 'icon' => 'fas fa-user-graduate', 'bg' => 'rgba(59,130,246,0.12)', 'color' => '#3b82f6'],
                                    str_contains($dutyStr, 'persuratan') => ['label' => 'Persuratan', 'icon' => 'fas fa-envelope-open-text', 'bg' => 'rgba(20,184,166,0.12)', 'color' => '#14b8a6'],
                                    str_contains($dutyStr, 'kepegawaian') => ['label' => 'Kepegawaian', 'icon' => 'fas fa-id-card', 'bg' => 'rgba(139,92,246,0.12)', 'color' => '#8b5cf6'],
                                    str_contains($dutyStr, 'sarpras') => ['label' => 'Sarpras', 'icon' => 'fas fa-boxes-stacked', 'bg' => 'rgba(245,158,11,0.12)', 'color' => '#f59e0b'],
                                    str_contains($dutyStr, 'pustaka') => ['label' => 'Pustakawan', 'icon' => 'fas fa-book', 'bg' => 'rgba(236,72,153,0.12)', 'color' => '#ec4899'],
                                    str_contains($dutyStr, 'teknisi') => ['label' => 'Teknisi IT', 'icon' => 'fas fa-laptop-code', 'bg' => 'rgba(99,102,241,0.12)', 'color' => '#6366f1'],
                                    default => !empty($staf->tugas_tambahan) 
                                        ? ['label' => \Illuminate\Support\Str::limit($staf->tugas_tambahan, 14), 'icon' => 'fas fa-briefcase', 'bg' => 'rgba(59,130,246,0.12)', 'color' => '#3b82f6']
                                        : ['label' => 'Umum', 'icon' => 'fas fa-folder', 'bg' => 'rgba(100,116,139,0.12)', 'color' => '#64748b']
                                };
                            @endphp
                            <tr>
                                <td>
                                    <div style="font-weight: 700; color: var(--text-color); font-size: 0.84rem;">
                                        {{ $staf->nama }}
                                    </div>
                                    @if (!empty($staf->nip))
                                        <div style="font-size: 0.7rem; color: var(--text-muted); font-family: monospace;" title="NIP: {{ $staf->nip }}">
                                            <i class="fas fa-fingerprint text-warning me-1"></i>{{ $staf->nip }}
                                        </div>
                                    @endif
                                </td>
                                <td style="text-align: center;">
                                    <span class="badge-compact" style="background: rgba(59,130,246,0.1); color: #3b82f6;" title="{{ $staf->jabatan_ptk_id_str ?: 'Tenaga Administrasi Sekolah' }}">
                                        <i class="fas fa-id-badge"></i> TAS
                                    </span>
                                </td>
                                <td>
                                    <span class="badge-compact" style="background: {{ $dutyBadge['bg'] }}; color: {{ $dutyBadge['color'] }};" title="{{ $staf->tugas_tambahan ?: 'Staf Pelaksana Umum' }}">
                                        <i class="{{ $dutyBadge['icon'] }}"></i> {{ $dutyBadge['label'] }}
                                    </span>
                                </td>
                                <td style="text-align: center;">
                                    <span class="badge-compact" style="background: rgba(16,185,129,0.12); color: #10b981;" title="Status: Aktif">
                                        <i class="fas fa-circle-check"></i>
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" style="text-align: center; padding: 24px; color: var(--text-muted); font-size: 0.82rem;">
                                    <i class="fas fa-users-slash me-1"></i> Belum ada data personel staf TAS.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Log Persuratan & Disposisi Terbaru -->
        <div class="card" style="padding: 0; overflow: hidden; border-radius: 14px; border: 1px solid var(--border-color); background: var(--card-bg);">
            <div style="padding: 14px 18px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; background: rgba(255,255,255,0.02); flex-wrap: wrap; gap: 8px;">
                <div style="font-weight: 700; font-size: 0.92rem; color: var(--text-color); display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-file-invoice text-primary"></i> Pengawasan Dokumen Terkini
                </div>
                <a href="{{ route('dashboard.persuratan.index') }}" class="btn btn-outline btn-icon" style="width: 30px; height: 30px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px;" title="Buka Modul Persuratan">
                    <i class="fas fa-arrow-right" style="font-size: 0.76rem;"></i>
                </a>
            </div>

            <div class="table-responsive-stack" style="overflow-x: auto; -webkit-overflow-scrolling: touch; margin: 0;">
                <table class="table-minimal-compact">
                    <thead>
                        <tr>
                            <th style="min-width: 140px;">No. Dokumen</th>
                            <th style="min-width: 70px; text-align: center;">Tipe</th>
                            <th style="min-width: 130px;">Perihal</th>
                            <th style="min-width: 60px; text-align: center;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($persuratanTerbaru ?? [] as $surat)
                            @php
                                $isMasuk = (($surat->jenis_surat ?? '') === 'masuk');
                            @endphp
                            <tr>
                                <td>
                                    <div style="font-size: 0.78rem; font-family: monospace; font-weight: 700; color: var(--text-color);">
                                        {{ $surat->nomor_surat ?? '-' }}
                                    </div>
                                    <div style="font-size: 0.7rem; color: var(--text-muted);">
                                        <i class="far fa-calendar-alt me-1"></i>{{ date('d/m/Y', strtotime($surat->created_at)) }}
                                    </div>
                                </td>
                                <td style="text-align: center;">
                                    <span class="badge-compact" style="{{ $isMasuk ? 'background: rgba(59,130,246,0.12); color: #3b82f6;' : 'background: rgba(16,185,129,0.12); color: #10b981;' }}" title="Surat {{ ucfirst($surat->jenis_surat ?? 'dokumen') }}">
                                        <i class="{{ $isMasuk ? 'fas fa-inbox' : 'fas fa-paper-plane' }}"></i>
                                        <span class="d-none d-sm-inline">{{ $isMasuk ? 'Masuk' : 'Keluar' }}</span>
                                    </span>
                                </td>
                                <td>
                                    <div style="font-weight: 600; font-size: 0.8rem; color: var(--text-color); max-width: 220px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $surat->perihal ?? '-' }}">
                                        {{ $surat->perihal ?? '-' }}
                                    </div>
                                </td>
                                <td style="text-align: center;">
                                    <span class="badge-compact" style="background: rgba(16,185,129,0.12); color: #10b981;" title="Status: {{ $surat->status ?? 'Tercatat' }}">
                                        <i class="fas fa-check"></i>
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" style="text-align: center; padding: 20px; color: var(--text-muted); font-size: 0.82rem;">
                                    <i class="fas fa-folder-open me-1"></i> Belum ada catatan surat masuk/keluar.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Right Column: Menu Koordinasi & Info Kalender (Stack di Mobile) -->
    <div style="display: flex; flex-direction: column; gap: 20px; min-width: 0;">
        <!-- Card Menu Koordinasi Kepala TAS -->
        <div class="card" style="padding: 18px; border-radius: 14px; border: 1px solid var(--border-color); background: var(--card-bg);">
            <div style="font-weight: 700; font-size: 0.9rem; color: var(--text-color); margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-bolt text-warning"></i> Menu Koordinasi Kepala TAS
            </div>
            <div style="display: flex; flex-direction: column; gap: 8px;">
                <a href="{{ route('dashboard.tendik-aktif.index') }}" class="btn btn-outline"
                    style="display: flex; justify-content: space-between; align-items: center; padding: 9px 12px; font-size: 0.8rem; border-radius: 8px; text-decoration: none;">
                    <span><i class="fas fa-users text-primary me-2"></i> Data Tendik</span>
                    <i class="fas fa-chevron-right text-muted" style="font-size: 0.7rem;"></i>
                </a>
                <a href="{{ route('dashboard.guru-aktif.index') }}" class="btn btn-outline"
                    style="display: flex; justify-content: space-between; align-items: center; padding: 9px 12px; font-size: 0.8rem; border-radius: 8px; text-decoration: none;">
                    <span><i class="fas fa-chalkboard-user text-info me-2"></i> Direktori Guru</span>
                    <i class="fas fa-chevron-right text-muted" style="font-size: 0.7rem;"></i>
                </a>
                <a href="{{ route('dashboard.peserta-didik-aktif.index') }}" class="btn btn-outline"
                    style="display: flex; justify-content: space-between; align-items: center; padding: 9px 12px; font-size: 0.8rem; border-radius: 8px; text-decoration: none;">
                    <span><i class="fas fa-user-graduate text-success me-2"></i> Data Induk Siswa</span>
                    <i class="fas fa-chevron-right text-muted" style="font-size: 0.7rem;"></i>
                </a>
                <a href="{{ route('dashboard.persuratan.index') }}" class="btn btn-outline"
                    style="display: flex; justify-content: space-between; align-items: center; padding: 9px 12px; font-size: 0.8rem; border-radius: 8px; text-decoration: none;">
                    <span><i class="fas fa-envelope-open-text text-warning me-2"></i> Administrasi Surat</span>
                    <i class="fas fa-chevron-right text-muted" style="font-size: 0.7rem;"></i>
                </a>
                <a href="{{ route('dashboard.hak-akses.index') }}" class="btn btn-outline"
                    style="display: flex; justify-content: space-between; align-items: center; padding: 9px 12px; font-size: 0.8rem; border-radius: 8px; text-decoration: none;">
                    <span><i class="fas fa-shield-halved text-danger me-2"></i> SK Tugas Tambahan</span>
                    <i class="fas fa-chevron-right text-muted" style="font-size: 0.7rem;"></i>
                </a>
            </div>
        </div>

        <!-- Info Efektif & Kalender Sekolah -->
        <div class="card" style="padding: 16px; border-radius: 14px; background: linear-gradient(135deg, rgba(99,102,241,0.06) 0%, rgba(59,130,246,0.04) 100%); border: 1px solid rgba(99,102,241,0.18);">
            <div style="font-weight: 700; font-size: 0.86rem; color: var(--text-color); margin-bottom: 10px; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-calendar-check text-primary"></i> Info Efektif Sekolah
            </div>
            <div style="display: flex; flex-direction: column; gap: 8px; font-size: 0.78rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 6px; border-bottom: 1px solid rgba(255,255,255,0.05);">
                    <span style="color: var(--text-muted);">Hari &amp; Tanggal:</span>
                    <strong style="color: var(--text-color);">{{ \Carbon\Carbon::now()->translatedFormat('d M Y') }}</strong>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 6px; border-bottom: 1px solid rgba(255,255,255,0.05);">
                    <span style="color: var(--text-muted);">Status Hari:</span>
                    <span class="badge badge-success" style="font-size: 0.68rem; padding: 2px 6px;">Hari Efektif</span>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="color: var(--text-muted);">Semester:</span>
                    <strong style="color: #3b82f6;">TA. {{ \App\Support\SemesterHelper::getActiveSemesterLabel() }}</strong>
                </div>
            </div>
        </div>
    </div>
</div>
