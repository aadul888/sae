{{-- Section Dashboard: Staf Administrasi Kesiswaan --}}

<!-- Quick Stats Grid Kesiswaan -->
<div class="dash-stat-grid"
    style="grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); margin-bottom: 24px; gap: 16px;">
    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(59, 130, 246, 0.15); color: #3b82f6;">
            <i class="fas fa-user-graduate"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">{{ number_format($stats['total_siswa'] ?? 1126, 0, ',', '.') }} Siswa</div>
            <div class="dash-stat-label">Total Peserta Didik Aktif</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(16, 185, 129, 0.15); color: #10b981;">
            <i class="fas fa-id-card"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">{{ $stats['siswa_berfoto'] ?? 0 }} Siswa</div>
            <div class="dash-stat-label">Pasfoto Kartu Pelajar Siap</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b;">
            <i class="fas fa-door-open"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">{{ $stats['total_rombel'] ?? 70 }} Kelas</div>
            <div class="dash-stat-label">Rombongan Belajar (Rombel)</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(236, 72, 153, 0.15); color: #ec4899;">
            <i class="fas fa-venus-mars"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">{{ $stats['siswa_laki'] ?? '-' }} L / {{ $stats['siswa_perempuan'] ?? '-' }} P</div>
            <div class="dash-stat-label">Komposisi Gender Siswa</div>
        </div>
    </div>
</div>

<!-- Main Content Grid Kesiswaan -->
<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-bottom: 24px;">
    <!-- Left Column: Rekap Rombel & Daftar Siswa Terbaru -->
    <div style="display: flex; flex-direction: column; gap: 20px;">
        <!-- Rekap Rombel -->
        <div class="card" style="padding: 0; overflow: hidden; border-radius: 14px;">
            <div
                style="padding: 16px 20px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; background: rgba(255,255,255,0.02);">
                <div style="font-weight: 700; font-size: 0.95rem; color: var(--text-color);">
                    <i class="fas fa-chalkboard-user text-primary me-2"></i> Rekapitulasi Rombongan Belajar &amp; Kapasitas Siswa
                </div>
                <a href="{{ route('dashboard.rombel.index') }}" class="btn btn-outline" style="padding: 4px 10px; font-size: 0.75rem;">
                    Lihat Semua Rombel <i class="fas fa-arrow-right ms-1"></i>
                </a>
            </div>

            <div class="table-responsive">
                <table class="table" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                    <thead>
                        <tr style="border-bottom: 1px solid var(--border-color); background: rgba(255,255,255,0.01);">
                            <th style="padding: 10px 16px; font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted);">Nama Rombel</th>
                            <th style="padding: 10px 14px; font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted);">Tingkat</th>
                            <th style="padding: 10px 14px; font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted);">Kompetensi / Jurusan</th>
                            <th style="padding: 10px 16px; font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); text-align: center;">Jumlah Siswa</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rombelRekap ?? [] as $r)
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <td style="padding: 12px 16px; font-size: 0.82rem; font-weight: 600; color: var(--text-color);">
                                    <i class="fas fa-folder text-primary me-1"></i> {{ $r->nama }}
                                </td>
                                <td style="padding: 12px 14px; font-size: 0.8rem; color: var(--text-muted);">
                                    Tingkat {{ $r->tingkat ?: '-' }}
                                </td>
                                <td style="padding: 12px 14px; font-size: 0.8rem; color: var(--text-color);">
                                    {{ $r->jurusan ?: 'Umum / Reguler' }}
                                </td>
                                <td style="padding: 12px 16px; text-align: center;">
                                    <span class="badge badge-primary" style="font-size: 0.76rem; padding: 4px 10px;">
                                        {{ $r->total_siswa }} Siswa
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" style="text-align: center; padding: 20px; color: var(--text-muted); font-size: 0.85rem;">
                                    Belum ada data rombel reguler.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Siswa Terbaru Terdaftar -->
        <div class="card" style="padding: 0; overflow: hidden; border-radius: 14px;">
            <div
                style="padding: 16px 20px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; background: rgba(255,255,255,0.02);">
                <div style="font-weight: 700; font-size: 0.95rem; color: var(--text-color);">
                    <i class="fas fa-users text-success me-2"></i> Data Peserta Didik Terbaru (Sampel Induk)
                </div>
                <a href="{{ route('dashboard.peserta-didik-aktif.index') }}" class="btn btn-outline" style="padding: 4px 10px; font-size: 0.75rem;">
                    Buka Buku Induk <i class="fas fa-arrow-right ms-1"></i>
                </a>
            </div>

            <div class="table-responsive">
                <table class="table" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                    <thead>
                        <tr style="border-bottom: 1px solid var(--border-color); background: rgba(255,255,255,0.01);">
                            <th style="padding: 10px 16px; font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted);">Nama Peserta Didik</th>
                            <th style="padding: 10px 14px; font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted);">NISN</th>
                            <th style="padding: 10px 14px; font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted);">Rombel</th>
                            <th style="padding: 10px 14px; font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); text-align: center;">Gender</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($siswaTerbaru ?? [] as $s)
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <td style="padding: 12px 16px; font-size: 0.82rem; font-weight: 600; color: var(--text-color);">
                                    {{ $s->nama }}
                                </td>
                                <td style="padding: 12px 14px; font-size: 0.8rem; font-family: monospace; color: var(--text-muted);">
                                    {{ $s->nisn ?? '-' }}
                                </td>
                                <td style="padding: 12px 14px; font-size: 0.8rem;">
                                    <span class="badge badge-info" style="font-size: 0.72rem; padding: 3px 8px;">
                                        {{ $s->nama_rombel ?? '-' }}
                                    </span>
                                </td>
                                <td style="padding: 12px 14px; font-size: 0.8rem; text-align: center;">
                                    <span class="badge {{ ($s->jenis_kelamin ?? 'L') === 'L' ? 'badge-primary' : 'badge-warning' }}" style="font-size: 0.72rem;">
                                        {{ ($s->jenis_kelamin ?? 'L') === 'L' ? 'Laki-laki' : 'Perempuan' }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" style="text-align: center; padding: 20px; color: var(--text-muted); font-size: 0.85rem;">
                                    Belum ada data peserta didik.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Right Column: Akses Cepat Kesiswaan -->
    <div style="display: flex; flex-direction: column; gap: 20px;">
        <div class="card" style="padding: 20px; border-radius: 14px;">
            <div style="font-weight: 700; font-size: 0.95rem; color: var(--text-color); margin-bottom: 14px; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-bolt text-warning"></i> Menu Operasional Kesiswaan
            </div>
            <div style="display: flex; flex-direction: column; gap: 8px;">
                <a href="{{ route('dashboard.peserta-didik-aktif.index') }}" class="btn btn-outline"
                    style="display: flex; justify-content: space-between; align-items: center; padding: 11px 14px; font-size: 0.82rem; border-radius: 8px; text-align: left;">
                    <span><i class="fas fa-user-graduate text-primary me-2"></i> Data Induk Siswa Aktif</span>
                    <i class="fas fa-chevron-right text-muted" style="font-size: 0.72rem;"></i>
                </a>
                <a href="{{ route('dashboard.rombel.index') }}" class="btn btn-outline"
                    style="display: flex; justify-content: space-between; align-items: center; padding: 11px 14px; font-size: 0.82rem; border-radius: 8px; text-align: left;">
                    <span><i class="fas fa-door-open text-info me-2"></i> Manajemen Rombel</span>
                    <i class="fas fa-chevron-right text-muted" style="font-size: 0.72rem;"></i>
                </a>
                <a href="{{ route('dashboard.peserta-didik-tidak-aktif.index') }}" class="btn btn-outline"
                    style="display: flex; justify-content: space-between; align-items: center; padding: 11px 14px; font-size: 0.82rem; border-radius: 8px; text-align: left;">
                    <span><i class="fas fa-user-xmark text-danger me-2"></i> Arsip Mutasi / Alumni</span>
                    <i class="fas fa-chevron-right text-muted" style="font-size: 0.72rem;"></i>
                </a>
                <a href="{{ route('dashboard.kompetensi-keahlian.index') }}" class="btn btn-outline"
                    style="display: flex; justify-content: space-between; align-items: center; padding: 11px 14px; font-size: 0.82rem; border-radius: 8px; text-align: left;">
                    <span><i class="fas fa-layer-group text-warning me-2"></i> Jurusan &amp; Kompetensi</span>
                    <i class="fas fa-chevron-right text-muted" style="font-size: 0.72rem;"></i>
                </a>
            </div>
        </div>

        <div class="card" style="padding: 20px; border-radius: 14px; background: linear-gradient(135deg, rgba(16,185,129,0.08) 0%, rgba(59,130,246,0.05) 100%); border: 1px solid rgba(16,185,129,0.2);">
            <div style="font-weight: 700; font-size: 0.9rem; color: var(--text-color); margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-id-badge text-success"></i> Standar Kartu Pelajar Digital
            </div>
            <p style="font-size: 0.82rem; color: var(--text-muted); line-height: 1.5; margin: 0 0 12px 0;">
                Kelengkapan pasfoto digital langsung terintegrasi dengan kode QR dan barcode NISN untuk pencetakan kartu pelajar maupun pemindaian RFID Kiosk.
            </p>
            <div style="display: flex; justify-content: space-between; font-size: 0.8rem;">
                <span style="color: var(--text-muted);">Tingkat Kelengkapan:</span>
                <strong style="color: #10b981;">{{ $stats['persen_foto'] ?? 'Ready' }}</strong>
            </div>
        </div>
    </div>
</div>
