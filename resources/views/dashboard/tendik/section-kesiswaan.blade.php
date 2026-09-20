{{-- Section Dashboard: Staf Administrasi Kesiswaan --}}

<!-- Quick Stats Grid Kesiswaan -->
<div class="dash-stat-grid" style="margin-bottom: 24px; gap: 14px;">
    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(59, 130, 246, 0.15); color: #3b82f6;">
            <i class="fas fa-user-graduate"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">{{ number_format($stats['total_siswa'] ?? 1126, 0, ',', '.') }}</div>
            <div class="dash-stat-label">Siswa Aktif</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(16, 185, 129, 0.15); color: #10b981;">
            <i class="fas fa-id-card"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">{{ $stats['siswa_berfoto'] ?? 0 }}</div>
            <div class="dash-stat-label">Foto Kartu Pelajar</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b;">
            <i class="fas fa-door-open"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">{{ $stats['total_rombel'] ?? 70 }}</div>
            <div class="dash-stat-label">Rombel / Kelas</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(236, 72, 153, 0.15); color: #ec4899;">
            <i class="fas fa-venus-mars"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value" style="font-size: 1.1rem;">{{ $stats['siswa_laki'] ?? '-' }} L / {{ $stats['siswa_perempuan'] ?? '-' }} P</div>
            <div class="dash-stat-label">Gender Siswa</div>
        </div>
    </div>
</div>

<!-- Main Content Grid Kesiswaan (Responsive: 2fr 1fr desktop, 1fr mobile) -->
<div class="dash-layout-grid">
    <!-- Left Column: Rekap Rombel & Daftar Siswa Terbaru -->
    <div style="display: flex; flex-direction: column; gap: 20px; min-width: 0;">
        <!-- Rekap Rombel -->
        <div class="card" style="padding: 0; overflow: hidden; border-radius: 14px; border: 1px solid var(--border-color); background: var(--card-bg);">
            <div style="padding: 14px 18px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; background: rgba(255,255,255,0.02); flex-wrap: wrap; gap: 8px;">
                <div style="font-weight: 700; font-size: 0.92rem; color: var(--text-color); display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-chalkboard-user text-primary"></i> Rombongan Belajar &amp; Siswa
                </div>
                <a href="{{ route('dashboard.rombel.index') }}" class="btn btn-outline btn-icon" style="width: 30px; height: 30px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px;" title="Lihat Semua Rombel">
                    <i class="fas fa-arrow-right" style="font-size: 0.76rem;"></i>
                </a>
            </div>

            <div class="table-responsive-stack" style="overflow-x: auto; -webkit-overflow-scrolling: touch; margin: 0;">
                <table class="table-minimal-compact">
                    <thead>
                        <tr>
                            <th style="min-width: 120px;">Rombel</th>
                            <th style="min-width: 60px; text-align: center;">Tingkat</th>
                            <th style="min-width: 140px;">Kompetensi / Jurusan</th>
                            <th style="min-width: 70px; text-align: center;">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rombelRekap ?? [] as $r)
                            <tr>
                                <td>
                                    <div style="font-weight: 700; color: var(--text-color); font-size: 0.84rem;">
                                        <i class="fas fa-folder text-primary me-1"></i> {{ $r->nama }}
                                    </div>
                                </td>
                                <td style="text-align: center;">
                                    <span class="badge-compact" style="background: rgba(59,130,246,0.1); color: #3b82f6;">
                                        Tkt {{ $r->tingkat ?: '-' }}
                                    </span>
                                </td>
                                <td>
                                    <div style="font-size: 0.8rem; color: var(--text-color); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 200px;" title="{{ $r->jurusan ?: 'Umum / Reguler' }}">
                                        {{ $r->jurusan ?: 'Umum / Reguler' }}
                                    </div>
                                </td>
                                <td style="text-align: center;">
                                    <span class="badge-compact" style="background: rgba(16,185,129,0.12); color: #10b981; font-weight: 700;">
                                        <i class="fas fa-users"></i> {{ $r->total_siswa }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" style="text-align: center; padding: 20px; color: var(--text-muted); font-size: 0.82rem;">
                                    <i class="fas fa-folder-open me-1"></i> Belum ada data rombel reguler.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Siswa Terbaru Terdaftar -->
        <div class="card" style="padding: 0; overflow: hidden; border-radius: 14px; border: 1px solid var(--border-color); background: var(--card-bg);">
            <div style="padding: 14px 18px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; background: rgba(255,255,255,0.02); flex-wrap: wrap; gap: 8px;">
                <div style="font-weight: 700; font-size: 0.92rem; color: var(--text-color); display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-users text-success"></i> Data Peserta Didik Terbaru
                </div>
                <a href="{{ route('dashboard.peserta-didik-aktif.index') }}" class="btn btn-outline btn-icon" style="width: 30px; height: 30px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px;" title="Buka Buku Induk">
                    <i class="fas fa-arrow-right" style="font-size: 0.76rem;"></i>
                </a>
            </div>

            <div class="table-responsive-stack" style="overflow-x: auto; -webkit-overflow-scrolling: touch; margin: 0;">
                <table class="table-minimal-compact">
                    <thead>
                        <tr>
                            <th style="min-width: 140px;">Nama Siswa</th>
                            <th style="min-width: 90px;">NISN</th>
                            <th style="min-width: 90px;">Kelas</th>
                            <th style="min-width: 50px; text-align: center;">Gender</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($siswaTerbaru ?? [] as $s)
                            @php
                                $isL = (($s->jenis_kelamin ?? 'L') === 'L');
                            @endphp
                            <tr>
                                <td>
                                    <div style="font-weight: 700; font-size: 0.82rem; color: var(--text-color);">
                                        {{ $s->nama }}
                                    </div>
                                </td>
                                <td>
                                    <div style="font-size: 0.76rem; font-family: monospace; color: var(--text-muted);">
                                        {{ $s->nisn ?? '-' }}
                                    </div>
                                </td>
                                <td>
                                    <span class="badge-compact" style="background: rgba(59,130,246,0.1); color: #3b82f6;">
                                        <i class="fas fa-door-open"></i> {{ $s->nama_rombel ?? '-' }}
                                    </span>
                                </td>
                                <td style="text-align: center;">
                                    <span class="badge-compact" style="{{ $isL ? 'background: rgba(59,130,246,0.12); color: #3b82f6;' : 'background: rgba(236,72,153,0.12); color: #ec4899;' }}" title="{{ $isL ? 'Laki-laki' : 'Perempuan' }}">
                                        <i class="{{ $isL ? 'fas fa-mars' : 'fas fa-venus' }}"></i>
                                        <span class="d-none d-sm-inline">{{ $isL ? 'L' : 'P' }}</span>
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" style="text-align: center; padding: 20px; color: var(--text-muted); font-size: 0.82rem;">
                                    <i class="fas fa-user-slash me-1"></i> Belum ada data peserta didik.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Right Column: Akses Cepat Kesiswaan (Stack di Mobile) -->
    <div style="display: flex; flex-direction: column; gap: 16px; min-width: 0;">
        <div class="card" style="padding: 16px; border-radius: 14px; border: 1px solid var(--border-color); background: var(--card-bg);">
            <div style="font-weight: 700; font-size: 0.88rem; color: var(--text-color); margin-bottom: 10px; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-bolt text-warning"></i> Menu Kesiswaan
            </div>
            <div style="display: flex; flex-direction: column; gap: 8px;">
                <a href="{{ route('dashboard.peserta-didik-aktif.index') }}" class="btn btn-outline"
                    style="display: flex; justify-content: space-between; align-items: center; padding: 9px 12px; font-size: 0.8rem; border-radius: 8px; text-decoration: none;">
                    <span><i class="fas fa-user-graduate text-primary me-2"></i> Buku Induk Siswa</span>
                    <i class="fas fa-chevron-right text-muted" style="font-size: 0.7rem;"></i>
                </a>
                <a href="{{ route('dashboard.rombel.index') }}" class="btn btn-outline"
                    style="display: flex; justify-content: space-between; align-items: center; padding: 9px 12px; font-size: 0.8rem; border-radius: 8px; text-decoration: none;">
                    <span><i class="fas fa-door-open text-info me-2"></i> Rombongan Belajar</span>
                    <i class="fas fa-chevron-right text-muted" style="font-size: 0.7rem;"></i>
                </a>
                <a href="{{ route('dashboard.peserta-didik-tidak-aktif.index') }}" class="btn btn-outline"
                    style="display: flex; justify-content: space-between; align-items: center; padding: 9px 12px; font-size: 0.8rem; border-radius: 8px; text-decoration: none;">
                    <span><i class="fas fa-user-xmark text-danger me-2"></i> Mutasi &amp; Alumni</span>
                    <i class="fas fa-chevron-right text-muted" style="font-size: 0.7rem;"></i>
                </a>
                <a href="{{ route('dashboard.kompetensi-keahlian.index') }}" class="btn btn-outline"
                    style="display: flex; justify-content: space-between; align-items: center; padding: 9px 12px; font-size: 0.8rem; border-radius: 8px; text-decoration: none;">
                    <span><i class="fas fa-layer-group text-warning me-2"></i> Kompetensi Keahlian</span>
                    <i class="fas fa-chevron-right text-muted" style="font-size: 0.7rem;"></i>
                </a>
            </div>
        </div>

        <div class="card" style="padding: 16px; border-radius: 14px; background: linear-gradient(135deg, rgba(16,185,129,0.06) 0%, rgba(59,130,246,0.04) 100%); border: 1px solid rgba(16,185,129,0.2);">
            <div style="font-weight: 700; font-size: 0.84rem; color: var(--text-color); margin-bottom: 6px; display: flex; align-items: center; gap: 6px;">
                <i class="fas fa-id-badge text-success"></i> Pasfoto Kartu Pelajar
            </div>
            <p style="font-size: 0.76rem; color: var(--text-muted); line-height: 1.45; margin: 0 0 8px 0;">
                Pasfoto digital terintegrasi kode QR &amp; barcode NISN untuk pencetakan kartu dan scanner RFID.
            </p>
            <div style="display: flex; justify-content: space-between; font-size: 0.78rem;">
                <span style="color: var(--text-muted);">Kelengkapan:</span>
                <strong style="color: #10b981;">{{ $stats['persen_foto'] ?? 'Ready' }}</strong>
            </div>
        </div>
    </div>
</div>
