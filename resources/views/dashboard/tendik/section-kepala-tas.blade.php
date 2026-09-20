{{-- Section Dashboard: Kepala Tenaga Administrasi Sekolah (Kepala TAS / KTU) --}}

<!-- Quick Stats Grid Kepala TAS -->
<div class="dash-stat-grid"
    style="grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); margin-bottom: 24px; gap: 16px;">
    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(99, 102, 241, 0.15); color: #6366f1;">
            <i class="fas fa-users-gear"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">{{ $stats['total_tendik'] ?? 19 }} Staf</div>
            <div class="dash-stat-label">Tenaga Kependidikan (TAS)</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(59, 130, 246, 0.15); color: #3b82f6;">
            <i class="fas fa-chalkboard-user"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">{{ $stats['total_guru'] ?? 48 }} Guru</div>
            <div class="dash-stat-label">Pendidik &amp; Pengampu Mapel</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(16, 185, 129, 0.15); color: #10b981;">
            <i class="fas fa-file-signature"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">{{ $stats['total_persuratan'] ?? 5 }} Dokumen</div>
            <div class="dash-stat-label">Total Arsip &amp; Persuratan</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b;">
            <i class="fas fa-user-graduate"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">{{ number_format($stats['total_siswa'] ?? 1126, 0, ',', '.') }} Siswa</div>
            <div class="dash-stat-label">Total Peserta Didik Aktif</div>
        </div>
    </div>
</div>

<!-- Main Content Grid Kepala TAS -->
<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-bottom: 24px;">
    <!-- Left Column: Matriks Pembagian Tugas Staf TAS & Log Persuratan -->
    <div style="display: flex; flex-direction: column; gap: 20px;">
        <!-- Matriks Pembagian Tugas Staf TAS -->
        <div class="card" style="padding: 0; overflow: hidden; border-radius: 14px;">
            <div
                style="padding: 16px 20px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; background: rgba(255,255,255,0.02);">
                <div style="font-weight: 700; font-size: 0.95rem; color: var(--text-color);">
                    <i class="fas fa-sitemap text-primary me-2"></i> Matriks Pembagian Tugas Staf Tata Usaha
                </div>
                <span class="badge badge-primary" style="font-size: 0.72rem; padding: 4px 8px;">
                    {{ count($stafTas ?? []) }} Personel TAS
                </span>
            </div>

            <div class="table-responsive">
                <table class="table" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                    <thead>
                        <tr style="border-bottom: 1px solid var(--border-color); background: rgba(255,255,255,0.01);">
                            <th style="padding: 10px 16px; font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted);">Nama Staf</th>
                            <th style="padding: 10px 14px; font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted);">Jabatan Induk</th>
                            <th style="padding: 10px 14px; font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted);">Bidang / Penugasan</th>
                            <th style="padding: 10px 16px; font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); text-align: center;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($stafTas ?? [] as $staf)
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <td style="padding: 12px 16px; font-size: 0.82rem; font-weight: 600; color: var(--text-color);">
                                    {{ $staf->nama }}
                                    @if (!empty($staf->nip))
                                        <div style="font-size: 0.72rem; color: var(--text-muted); font-family: monospace;" title="Nomor Induk Pegawai (NIP)"><i class="fas fa-id-badge text-warning me-1"></i>{{ $staf->nip }}</div>
                                    @endif
                                </td>
                                <td style="padding: 12px 14px; font-size: 0.8rem; color: var(--text-muted);">
                                    {{ $staf->jabatan_ptk_id_str ?: 'Tenaga Administrasi Sekolah' }}
                                </td>
                                <td style="padding: 12px 14px; font-size: 0.8rem;">
                                    @if (!empty($staf->tugas_tambahan))
                                        <span class="badge badge-info" style="font-size: 0.74rem; padding: 4px 8px; white-space: normal; line-height: 1.3;">
                                            <i class="fas fa-briefcase me-1"></i> {{ $staf->tugas_tambahan }}
                                        </span>
                                    @else
                                        <span class="badge badge-secondary" style="font-size: 0.72rem; padding: 3px 6px;">
                                            Staf Pelaksana Umum
                                        </span>
                                    @endif
                                </td>
                                <td style="padding: 12px 16px; text-align: center;">
                                    <span style="display: inline-flex; align-items: center; gap: 4px; color: #10b981; font-weight: 600; font-size: 0.76rem;">
                                        <i class="fas fa-circle-check"></i> Aktif
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" style="text-align: center; padding: 24px; color: var(--text-muted); font-size: 0.85rem;">
                                    Belum ada data personel staf TAS.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Log Persuratan & Disposisi Terbaru -->
        <div class="card" style="padding: 0; overflow: hidden; border-radius: 14px;">
            <div
                style="padding: 16px 20px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; background: rgba(255,255,255,0.02);">
                <div style="font-weight: 700; font-size: 0.95rem; color: var(--text-color);">
                    <i class="fas fa-file-lines text-primary me-2"></i> Pengawasan Dokumen &amp; Surat Terkini
                </div>
                <a href="{{ route('dashboard.persuratan.index') }}" class="btn btn-outline" style="padding: 4px 10px; font-size: 0.8rem;" title="Buka Persuratan">
                    <i class="fas fa-arrow-right"></i>
                </a>
            </div>

            <div class="table-responsive">
                <table class="table" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                    <thead>
                        <tr style="border-bottom: 1px solid var(--border-color); background: rgba(255,255,255,0.01);">
                            <th style="padding: 10px 16px; font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted);">No. Surat</th>
                            <th style="padding: 10px 14px; font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted);">Jenis</th>
                            <th style="padding: 10px 14px; font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted);">Perihal</th>
                            <th style="padding: 10px 14px; font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted);">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($persuratanTerbaru ?? [] as $surat)
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <td style="padding: 12px 16px; font-size: 0.8rem; font-family: monospace; font-weight: 600; color: var(--text-color);">
                                    {{ $surat->nomor_surat ?? '-' }}
                                    <div style="font-size: 0.72rem; color: var(--text-muted); font-family: sans-serif;">{{ date('d M Y', strtotime($surat->created_at)) }}</div>
                                </td>
                                <td style="padding: 12px 14px; font-size: 0.8rem;">
                                    <span class="badge {{ ($surat->jenis_surat ?? '') === 'masuk' ? 'badge-primary' : 'badge-success' }}" style="font-size: 0.72rem; padding: 3px 8px;">
                                        Surat {{ ucfirst($surat->jenis_surat ?? 'dokumen') }}
                                    </span>
                                </td>
                                <td style="padding: 12px 14px; font-size: 0.82rem; color: var(--text-color);">
                                    <div style="font-weight: 600;">{{ $surat->perihal ?? '-' }}</div>
                                    <div style="font-size: 0.72rem; color: var(--text-muted);">Pengirim/Tujuan: {{ $surat->pengirim ?? ($surat->tujuan ?? '-') }}</div>
                                </td>
                                <td style="padding: 12px 14px; font-size: 0.8rem;">
                                    <span style="color: #10b981; font-weight: 600; font-size: 0.76rem; display: inline-flex; align-items: center; gap: 4px;">
                                        <i class="fas fa-circle-check"></i> {{ $surat->status ?? 'Tercatat' }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" style="text-align: center; padding: 20px; color: var(--text-muted); font-size: 0.85rem;">
                                    Belum ada catatan surat masuk/keluar.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Right Column: Akses Cepat & Ringkasan Layanan -->
    <div style="display: flex; flex-direction: column; gap: 20px;">
        <!-- Card Akses Cepat Kepala TAS -->
        <div class="card" style="padding: 20px; border-radius: 14px;">
            <div style="font-weight: 700; font-size: 0.95rem; color: var(--text-color); margin-bottom: 14px; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-bolt text-warning"></i> Menu Koordinasi Kepala TAS
            </div>
            <div style="display: flex; flex-direction: column; gap: 8px;">
                <a href="{{ route('dashboard.tendik-aktif.index') }}" class="btn btn-outline"
                    style="display: flex; justify-content: space-between; align-items: center; padding: 11px 14px; font-size: 0.82rem; border-radius: 8px; text-align: left;">
                    <span><i class="fas fa-users text-primary me-2"></i> Data Tenaga Kependidikan</span>
                    <i class="fas fa-chevron-right text-muted" style="font-size: 0.72rem;"></i>
                </a>
                <a href="{{ route('dashboard.guru-aktif.index') }}" class="btn btn-outline"
                    style="display: flex; justify-content: space-between; align-items: center; padding: 11px 14px; font-size: 0.82rem; border-radius: 8px; text-align: left;">
                    <span><i class="fas fa-chalkboard-user text-info me-2"></i> Direktori Guru &amp; Pengampu</span>
                    <i class="fas fa-chevron-right text-muted" style="font-size: 0.72rem;"></i>
                </a>
                <a href="{{ route('dashboard.peserta-didik-aktif.index') }}" class="btn btn-outline"
                    style="display: flex; justify-content: space-between; align-items: center; padding: 11px 14px; font-size: 0.82rem; border-radius: 8px; text-align: left;">
                    <span><i class="fas fa-user-graduate text-success me-2"></i> Data Induk Peserta Didik</span>
                    <i class="fas fa-chevron-right text-muted" style="font-size: 0.72rem;"></i>
                </a>
                <a href="{{ route('dashboard.persuratan.index') }}" class="btn btn-outline"
                    style="display: flex; justify-content: space-between; align-items: center; padding: 11px 14px; font-size: 0.82rem; border-radius: 8px; text-align: left;">
                    <span><i class="fas fa-envelope-open-text text-warning me-2"></i> Administrasi Persuratan</span>
                    <i class="fas fa-chevron-right text-muted" style="font-size: 0.72rem;"></i>
                </a>
                <a href="{{ route('dashboard.hak-akses.index') }}" class="btn btn-outline"
                    style="display: flex; justify-content: space-between; align-items: center; padding: 11px 14px; font-size: 0.82rem; border-radius: 8px; text-align: left;">
                    <span><i class="fas fa-shield-halved text-danger me-2"></i> Penugasan Tugas Tambahan</span>
                    <i class="fas fa-chevron-right text-muted" style="font-size: 0.72rem;"></i>
                </a>
            </div>
        </div>

        <!-- Ringkasan Operasional Hari Ini -->
        <div class="card" style="padding: 20px; border-radius: 14px; background: linear-gradient(135deg, rgba(99,102,241,0.08) 0%, rgba(59,130,246,0.05) 100%); border: 1px solid rgba(99,102,241,0.2);">
            <div style="font-weight: 700; font-size: 0.9rem; color: var(--text-color); margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-calendar-check text-primary"></i> Info Efektif &amp; Kalender Sekolah
            </div>
            <div style="display: flex; flex-direction: column; gap: 10px; font-size: 0.82rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 8px; border-bottom: 1px solid rgba(255,255,255,0.05);">
                    <span style="color: var(--text-muted);">Hari &amp; Tanggal:</span>
                    <strong style="color: var(--text-color);">{{ \Carbon\Carbon::now()->translatedFormat('l, d F Y') }}</strong>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 8px; border-bottom: 1px solid rgba(255,255,255,0.05);">
                    <span style="color: var(--text-muted);">Status Hari Belajar:</span>
                    <span class="badge badge-success" style="font-size: 0.72rem;">Hari Efektif KBM</span>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="color: var(--text-muted);">Semester Aktif:</span>
                    <strong style="color: #3b82f6;">TA. {{ \App\Support\SemesterHelper::getActiveSemesterLabel() }}</strong>
                </div>
            </div>
        </div>
    </div>
</div>
