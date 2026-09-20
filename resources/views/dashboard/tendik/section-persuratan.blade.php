{{-- Section Dashboard: Staf Administrasi Persuratan & Tata Usaha Umum --}}

<!-- Quick Stats Grid Persuratan -->
<div class="dash-stat-grid"
    style="grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); margin-bottom: 24px; gap: 16px;">
    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(59, 130, 246, 0.15); color: #3b82f6;">
            <i class="fas fa-inbox"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">{{ $stats['total_surat_masuk'] ?? 0 }} Dokumen</div>
            <div class="dash-stat-label">Surat Masuk Bulan Ini</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(16, 185, 129, 0.15); color: #10b981;">
            <i class="fas fa-paper-plane"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">{{ $stats['total_surat_keluar'] ?? 0 }} Berkas</div>
            <div class="dash-stat-label">Surat Keluar / Keterangan</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b;">
            <i class="fas fa-address-book"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">{{ $stats['buku_tamu_hari_ini'] ?? 12 }} Tamu</div>
            <div class="dash-stat-label">Buku Tamu Digital Hari Ini</div>
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

<!-- Main Content Grid Persuratan -->
<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-bottom: 24px;">
    <!-- Left: Administrasi & Persuratan Terbaru -->
    <div class="card" style="padding: 0; overflow: hidden; border-radius: 14px;">
        <div
            style="padding: 16px 20px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; background: rgba(255,255,255,0.02);">
            <div style="font-weight: 700; font-size: 0.95rem; color: var(--text-color);">
                <i class="fas fa-file-invoice text-primary me-2"></i> Log Administrasi &amp; Surat Terakhir
            </div>
            <a href="{{ route('dashboard.persuratan.index') }}" class="btn btn-outline" style="padding: 4px 10px; font-size: 0.75rem;">
                Semua Surat <i class="fas fa-arrow-right ms-1"></i>
            </a>
        </div>

        <div class="table-responsive">
            <table class="table" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                <thead>
                    <tr style="border-bottom: 1px solid var(--border-color); background: rgba(255,255,255,0.01);">
                        <th style="padding: 10px 16px; font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted);">
                            No. Agenda / Surat</th>
                        <th style="padding: 10px 14px; font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted);">
                            Kategori</th>
                        <th style="padding: 10px 14px; font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted);">
                            Perihal Dokumen</th>
                        <th style="padding: 10px 14px; font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted);">
                            Status</th>
                        <th style="padding: 10px 16px; font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); text-align: center;">
                            Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($persuratanList ?? [] as $surat)
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 12px 16px; font-size: 0.8rem; font-family: monospace; font-weight: 600; color: var(--text-color);">
                                {{ $surat->nomor_surat ?? '-' }}
                                <div style="font-size: 0.72rem; color: var(--text-muted); font-family: sans-serif;">
                                    {{ date('d M Y', strtotime($surat->created_at)) }}
                                </div>
                            </td>
                            <td style="padding: 12px 14px; font-size: 0.8rem;">
                                <span class="badge {{ ($surat->jenis_surat ?? '') === 'masuk' ? 'badge-primary' : 'badge-success' }}"
                                    style="font-size: 0.72rem; padding: 3px 8px;">
                                    Surat {{ ucfirst($surat->jenis_surat ?? 'dokumen') }}
                                </span>
                            </td>
                            <td style="padding: 12px 14px; font-size: 0.82rem; color: var(--text-color);">
                                <div style="font-weight: 600;">{{ $surat->perihal ?? '-' }}</div>
                                <div style="font-size: 0.74rem; color: var(--text-muted); margin-top: 2px;">
                                    Dari/Tujuan: {{ $surat->pengirim ?? ($surat->tujuan ?? '-') }}
                                </div>
                            </td>
                            <td style="padding: 12px 14px; font-size: 0.8rem;">
                                <span style="display: inline-flex; align-items: center; gap: 5px; color: #10b981; font-weight: 600; font-size: 0.78rem;">
                                    <i class="fas fa-circle-check" style="font-size: 0.68rem;"></i>
                                    {{ $surat->status ?? 'Tercatat' }}
                                </span>
                            </td>
                            <td style="padding: 12px 16px; text-align: center;">
                                <a href="{{ route('dashboard.persuratan.index') }}" class="btn btn-outline"
                                    style="padding: 4px 8px; font-size: 0.75rem; border-radius: 6px;"
                                    title="Lihat Dokumen">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        @foreach ($administrasi_tugas ?? [] as $adm)
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <td style="padding: 12px 16px; font-size: 0.8rem; font-family: monospace; font-weight: 600; color: var(--text-color);">
                                    {{ $adm['nomor'] }}
                                    <div style="font-size: 0.72rem; color: var(--text-muted); font-family: sans-serif;">
                                        {{ $adm['tgl'] }}</div>
                                </td>
                                <td style="padding: 12px 14px; font-size: 0.8rem;">
                                    <span class="badge {{ str_contains($adm['kategori'], 'Masuk') ? 'badge-primary' : (str_contains($adm['kategori'], 'Keluar') ? 'badge-success' : 'badge-warning') }}"
                                        style="font-size: 0.72rem; padding: 3px 8px;">
                                        {{ $adm['kategori'] }}
                                    </span>
                                </td>
                                <td style="padding: 12px 14px; font-size: 0.82rem; color: var(--text-color);">
                                    <div style="font-weight: 600;">{{ $adm['perihal'] }}</div>
                                    <div style="font-size: 0.74rem; color: var(--text-muted); margin-top: 2px;">
                                        Dari/Tujuan: {{ $adm['pengirim'] }}
                                    </div>
                                </td>
                                <td style="padding: 12px 14px; font-size: 0.8rem;">
                                    <span style="display: inline-flex; align-items: center; gap: 5px; color: #10b981; font-weight: 600; font-size: 0.78rem;">
                                        <i class="fas fa-circle-check" style="font-size: 0.68rem;"></i>
                                        {{ $adm['status'] }}
                                    </span>
                                </td>
                                <td style="padding: 12px 16px; text-align: center;">
                                    <a href="{{ route('dashboard.persuratan.index') }}" class="btn btn-outline"
                                        style="padding: 4px 8px; font-size: 0.75rem; border-radius: 6px;"
                                        title="Lihat Dokumen">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Right: Menu Cepat Administrasi -->
    <div style="display: flex; flex-direction: column; gap: 16px;">
        <div class="card" style="padding: 18px; border-radius: 14px;">
            <div
                style="font-weight: 700; font-size: 0.92rem; color: var(--text-color); margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-bolt text-primary"></i> Akses Cepat Administrasi
            </div>
            <div style="display: flex; flex-direction: column; gap: 8px;">
                <a href="{{ route('dashboard.persuratan.index') }}" class="btn btn-outline"
                    style="display: flex; justify-content: space-between; align-items: center; padding: 10px 14px; font-size: 0.82rem; border-radius: 8px; text-align: left;">
                    <span><i class="fas fa-envelope text-primary me-2"></i> Surat Masuk &amp; Keluar</span>
                    <i class="fas fa-chevron-right text-muted" style="font-size: 0.72rem;"></i>
                </a>
                <a href="{{ route('dashboard.tendik-aktif.index') }}" class="btn btn-outline"
                    style="display: flex; justify-content: space-between; align-items: center; padding: 10px 14px; font-size: 0.82rem; border-radius: 8px; text-align: left;">
                    <span><i class="fas fa-id-badge text-info me-2"></i> Direktori Tendik Aktif</span>
                    <i class="fas fa-chevron-right text-muted" style="font-size: 0.72rem;"></i>
                </a>
                <a href="{{ route('dashboard.guru-aktif.index') }}" class="btn btn-outline"
                    style="display: flex; justify-content: space-between; align-items: center; padding: 10px 14px; font-size: 0.82rem; border-radius: 8px; text-align: left;">
                    <span><i class="fas fa-chalkboard-user text-warning me-2"></i> Direktori Guru Aktif</span>
                    <i class="fas fa-chevron-right text-muted" style="font-size: 0.72rem;"></i>
                </a>
                <a href="{{ route('dashboard.peserta-didik-aktif.index') }}" class="btn btn-outline"
                    style="display: flex; justify-content: space-between; align-items: center; padding: 10px 14px; font-size: 0.82rem; border-radius: 8px; text-align: left;">
                    <span><i class="fas fa-user-graduate text-success me-2"></i> Direktori Peserta Didik Aktif</span>
                    <i class="fas fa-chevron-right text-muted" style="font-size: 0.72rem;"></i>
                </a>
            </div>
        </div>

        <div class="card" style="padding: 18px; border-radius: 14px; background: linear-gradient(135deg, rgba(16,185,129,0.06) 0%, rgba(59,130,246,0.04) 100%); border: 1px solid rgba(16,185,129,0.2);">
            <div style="font-weight: 700; font-size: 0.88rem; color: var(--text-color); margin-bottom: 8px; display: flex; align-items: center; gap: 6px;">
                <i class="fas fa-circle-info text-success"></i> Catatan Pelayanan Dokumen
            </div>
            <p style="font-size: 0.8rem; color: var(--text-muted); line-height: 1.5; margin: 0;">
                Seluruh pembuatan surat dinas, surat tugas, dan surat keterangan siswa dicatat secara digital untuk kemudahan pelacakan arsip dan verifikasi keaslian dokumen.
            </p>
        </div>
    </div>
</div>
