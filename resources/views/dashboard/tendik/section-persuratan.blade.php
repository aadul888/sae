{{-- Section Dashboard: Staf Administrasi Persuratan & Tata Usaha Umum --}}

<!-- Quick Stats Grid Persuratan -->
<div class="dash-stat-grid" style="margin-bottom: 24px; gap: 14px;">
    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(59, 130, 246, 0.15); color: #3b82f6;">
            <i class="fas fa-inbox"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">{{ $stats['total_surat_masuk'] ?? 0 }} Dok</div>
            <div class="dash-stat-label">Surat Masuk</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(16, 185, 129, 0.15); color: #10b981;">
            <i class="fas fa-paper-plane"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">{{ $stats['total_surat_keluar'] ?? 0 }} Dok</div>
            <div class="dash-stat-label">Surat Keluar</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b;">
            <i class="fas fa-address-book"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">{{ $stats['buku_tamu_hari_ini'] ?? 12 }} Tamu</div>
            <div class="dash-stat-label">Buku Tamu Hari Ini</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(99, 102, 241, 0.15); color: var(--primary);">
            <i class="fas fa-user-check"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value" style="font-size: 1.05rem;">{{ $stats['status_presensi'] ?? 'Hadir Tepat Waktu' }}</div>
            <div class="dash-stat-label">Masuk: {{ $stats['presensi_masuk'] ?? '06:50 WIB' }}</div>
        </div>
    </div>
</div>

<!-- Main Content Grid Persuratan (Responsive Layout) -->
<div class="dash-layout-grid">
    <!-- Left: Administrasi & Persuratan Terbaru -->
    <div class="card" style="padding: 0; overflow: hidden; border-radius: 14px; border: 1px solid var(--border-color); background: var(--card-bg);">
        <div style="padding: 14px 18px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; background: rgba(255,255,255,0.02); flex-wrap: wrap; gap: 8px;">
            <div style="font-weight: 700; font-size: 0.92rem; color: var(--text-color); display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-file-invoice text-primary"></i> Log Administrasi &amp; Surat Terakhir
            </div>
            <a href="{{ route('dashboard.persuratan.index') }}" class="btn btn-outline btn-icon" style="width: 30px; height: 30px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px;" title="Lihat Semua Surat">
                <i class="fas fa-arrow-right" style="font-size: 0.76rem;"></i>
            </a>
        </div>

        <div class="table-responsive-stack" style="overflow-x: auto; -webkit-overflow-scrolling: touch; margin: 0;">
            <table class="table-minimal-compact">
                <thead>
                    <tr>
                        <th style="min-width: 130px;">No. Agenda</th>
                        <th style="min-width: 70px; text-align: center;">Tipe</th>
                        <th style="min-width: 130px;">Perihal</th>
                        <th style="min-width: 60px; text-align: center;">Status</th>
                        <th style="min-width: 40px; text-align: center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($persuratanList ?? [] as $surat)
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
                                <div style="font-size: 0.7rem; color: var(--text-muted); max-width: 220px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                    {{ $surat->pengirim ?? ($surat->tujuan ?? '-') }}
                                </div>
                            </td>
                            <td style="text-align: center;">
                                <span class="badge-compact" style="background: rgba(16,185,129,0.12); color: #10b981;" title="Status: {{ $surat->status ?? 'Tercatat' }}">
                                    <i class="fas fa-check"></i>
                                </span>
                            </td>
                            <td style="text-align: center;">
                                <a href="{{ route('dashboard.persuratan.index') }}" class="btn btn-outline btn-icon"
                                    style="width: 28px; height: 28px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 6px;"
                                    title="Lihat Dokumen">
                                    <i class="fas fa-eye" style="font-size: 0.72rem;"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        @foreach ($administrasi_tugas ?? [] as $adm)
                            @php
                                $isMasuk = str_contains($adm['kategori'], 'Masuk');
                            @endphp
                            <tr>
                                <td>
                                    <div style="font-size: 0.78rem; font-family: monospace; font-weight: 700; color: var(--text-color);">
                                        {{ $adm['nomor'] }}
                                    </div>
                                    <div style="font-size: 0.7rem; color: var(--text-muted);">
                                        <i class="far fa-calendar-alt me-1"></i>{{ $adm['tgl'] }}
                                    </div>
                                </td>
                                <td style="text-align: center;">
                                    <span class="badge-compact" style="{{ $isMasuk ? 'background: rgba(59,130,246,0.12); color: #3b82f6;' : 'background: rgba(16,185,129,0.12); color: #10b981;' }}">
                                        <i class="{{ $isMasuk ? 'fas fa-inbox' : 'fas fa-paper-plane' }}"></i>
                                        <span class="d-none d-sm-inline">{{ $isMasuk ? 'Masuk' : 'Keluar' }}</span>
                                    </span>
                                </td>
                                <td>
                                    <div style="font-weight: 600; font-size: 0.8rem; color: var(--text-color); max-width: 220px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $adm['perihal'] }}">
                                        {{ $adm['perihal'] }}
                                    </div>
                                </td>
                                <td style="text-align: center;">
                                    <span class="badge-compact" style="background: rgba(16,185,129,0.12); color: #10b981;" title="Status: {{ $adm['status'] }}">
                                        <i class="fas fa-check"></i>
                                    </span>
                                </td>
                                <td style="text-align: center;">
                                    <a href="{{ route('dashboard.persuratan.index') }}" class="btn btn-outline btn-icon"
                                        style="width: 28px; height: 28px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 6px;"
                                        title="Lihat Dokumen">
                                        <i class="fas fa-eye" style="font-size: 0.72rem;"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Right: Menu Cepat Administrasi (Stack di Mobile) -->
    <div style="display: flex; flex-direction: column; gap: 16px; min-width: 0;">
        <div class="card" style="padding: 16px; border-radius: 14px; border: 1px solid var(--border-color); background: var(--card-bg);">
            <div style="font-weight: 700; font-size: 0.88rem; color: var(--text-color); margin-bottom: 10px; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-bolt text-primary"></i> Akses Cepat Persuratan
            </div>
            <div style="display: flex; flex-direction: column; gap: 8px;">
                <a href="{{ route('dashboard.persuratan.index') }}" class="btn btn-outline"
                    style="display: flex; justify-content: space-between; align-items: center; padding: 9px 12px; font-size: 0.8rem; border-radius: 8px; text-decoration: none;">
                    <span><i class="fas fa-envelope text-primary me-2"></i> Surat Masuk &amp; Keluar</span>
                    <i class="fas fa-chevron-right text-muted" style="font-size: 0.7rem;"></i>
                </a>
                <a href="{{ route('dashboard.tendik-aktif.index') }}" class="btn btn-outline"
                    style="display: flex; justify-content: space-between; align-items: center; padding: 9px 12px; font-size: 0.8rem; border-radius: 8px; text-decoration: none;">
                    <span><i class="fas fa-id-badge text-info me-2"></i> Data Tendik</span>
                    <i class="fas fa-chevron-right text-muted" style="font-size: 0.7rem;"></i>
                </a>
                <a href="{{ route('dashboard.guru-aktif.index') }}" class="btn btn-outline"
                    style="display: flex; justify-content: space-between; align-items: center; padding: 9px 12px; font-size: 0.8rem; border-radius: 8px; text-decoration: none;">
                    <span><i class="fas fa-chalkboard-user text-warning me-2"></i> Data Guru</span>
                    <i class="fas fa-chevron-right text-muted" style="font-size: 0.7rem;"></i>
                </a>
                <a href="{{ route('dashboard.peserta-didik-aktif.index') }}" class="btn btn-outline"
                    style="display: flex; justify-content: space-between; align-items: center; padding: 9px 12px; font-size: 0.8rem; border-radius: 8px; text-decoration: none;">
                    <span><i class="fas fa-user-graduate text-success me-2"></i> Data Siswa</span>
                    <i class="fas fa-chevron-right text-muted" style="font-size: 0.7rem;"></i>
                </a>
            </div>
        </div>

        <div class="card" style="padding: 16px; border-radius: 14px; background: linear-gradient(135deg, rgba(16,185,129,0.06) 0%, rgba(59,130,246,0.04) 100%); border: 1px solid rgba(16,185,129,0.2);">
            <div style="font-weight: 700; font-size: 0.84rem; color: var(--text-color); margin-bottom: 6px; display: flex; align-items: center; gap: 6px;">
                <i class="fas fa-circle-info text-success"></i> Pelayanan Dokumen Digital
            </div>
            <p style="font-size: 0.76rem; color: var(--text-muted); line-height: 1.45; margin: 0;">
                Seluruh pembuatan surat dinas dan surat keterangan dicatat secara digital untuk kemudahan pelacakan arsip dan verifikasi.
            </p>
        </div>
    </div>
</div>
