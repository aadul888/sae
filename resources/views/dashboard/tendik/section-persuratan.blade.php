{{-- Section Dashboard: Staf Administrasi Persuratan & Tata Usaha Umum — SAE --}}

@php
    $hdd = $stats['hdd_status'] ?? [
        'is_ready' => true,
        'free_formatted' => '54.2 GB',
        'total_formatted' => '256 GB',
        'percent_used' => 21.2,
        'path' => storage_path('app/arsip_persuratan'),
    ];
@endphp

<!-- Quick Stats Grid Persuratan -->
<div class="dash-stat-grid" style="margin-bottom: 24px; gap: 14px;">
    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(59, 130, 246, 0.15); color: #3b82f6;">
            <i class="fas fa-inbox"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">{{ number_format($stats['total_surat_masuk'] ?? 0) }} Dok</div>
            <div class="dash-stat-label">Surat Masuk</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(16, 185, 129, 0.15); color: #10b981;">
            <i class="fas fa-paper-plane"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">{{ number_format($stats['total_surat_keluar'] ?? 0) }} Dok</div>
            <div class="dash-stat-label">Surat Keluar</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(99, 102, 241, 0.15); color: var(--primary);">
            <i class="fas fa-user-graduate"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">{{ number_format($stats['total_surat_ket'] ?? 0) }} Dok</div>
            <div class="dash-stat-label">Surat Keterangan Siswa</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b;">
            <i class="fas fa-hard-drive"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value" style="font-size: 1.05rem;">{{ $hdd['free_formatted'] ?? '-' }}</div>
            <div class="dash-stat-label">Sisa Ruang HDD (Bebas)</div>
        </div>
    </div>
</div>

<!-- Main Content Grid Persuratan (Responsive Layout) -->
<div class="dash-layout-grid">
    <!-- Left: Administrasi & Persuratan Terbaru -->
    <div class="card" style="padding: 0; overflow: hidden; border-radius: 14px; border: 1px solid var(--border-color); background: var(--card-bg);">
        <div style="padding: 14px 18px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; background: rgba(255,255,255,0.02); flex-wrap: wrap; gap: 8px;">
            <div style="font-weight: 700; font-size: 0.92rem; color: var(--text-color); display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-file-invoice text-primary"></i> Log Administrasi &amp; Surat Terkini
            </div>
            <div style="display: flex; gap: 6px;">
                <a href="{{ route('dashboard.persuratan.masuk.index') }}" class="btn btn-outline" style="padding: 4px 10px; font-size: 0.76rem; border-radius: 6px;">
                    <i class="fas fa-inbox me-1"></i> Masuk
                </a>
                <a href="{{ route('dashboard.persuratan.keluar.index') }}" class="btn btn-outline" style="padding: 4px 10px; font-size: 0.76rem; border-radius: 6px;">
                    <i class="fas fa-paper-plane me-1"></i> Keluar
                </a>
            </div>
        </div>

        <div class="table-responsive-stack" style="overflow-x: auto; -webkit-overflow-scrolling: touch; margin: 0;">
            <table class="table-minimal-compact">
                <thead>
                    <tr>
                        <th style="min-width: 140px;">No. Agenda / Surat</th>
                        <th style="min-width: 80px; text-align: center;">Jenis</th>
                        <th style="min-width: 160px;">Perihal &amp; Pihak Terkait</th>
                        <th style="min-width: 80px; text-align: center;">Status</th>
                        <th style="min-width: 60px; text-align: center;">Aksi</th>
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
                                    <i class="far fa-calendar-alt me-1"></i>{{ date('d/m/Y', strtotime($surat->tanggal_surat ?? $surat->created_at)) }}
                                </div>
                            </td>
                            <td style="text-align: center;">
                                <span class="badge-compact" style="{{ $isMasuk ? 'background: rgba(59,130,246,0.12); color: #3b82f6;' : 'background: rgba(16,185,129,0.12); color: #10b981;' }}">
                                    <i class="{{ $isMasuk ? 'fas fa-inbox' : 'fas fa-paper-plane' }}"></i>
                                    {{ $isMasuk ? 'Masuk' : 'Keluar' }}
                                </span>
                            </td>
                            <td>
                                <div style="font-weight: 600; font-size: 0.8rem; color: var(--text-color); max-width: 240px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $surat->perihal ?? '-' }}">
                                    {{ $surat->perihal ?? '-' }}
                                </div>
                                <div style="font-size: 0.7rem; color: var(--text-muted); max-width: 240px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                    {{ $isMasuk ? 'Dari: ' . ($surat->pengirim_asal ?? '-') : 'Ke: ' . ($surat->tujuan_penerima ?? '-') }}
                                </div>
                            </td>
                            <td style="text-align: center;">
                                <span class="badge-compact" style="background: rgba(16,185,129,0.12); color: #10b981;">
                                    {{ ucfirst($surat->status ?? 'Tercatat') }}
                                </span>
                            </td>
                            <td style="text-align: center;">
                                <a href="{{ $isMasuk ? route('dashboard.persuratan.masuk.index') : route('dashboard.persuratan.keluar.index') }}"
                                   class="btn btn-outline btn-icon"
                                   style="width: 28px; height: 28px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 6px;"
                                   title="Buka Agenda">
                                    <i class="fas fa-arrow-right" style="font-size: 0.72rem;"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 30px 16px; color: var(--text-muted); font-size: 0.82rem;">
                                Belum ada catatan surat dalam sistem.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Right: Menu Cepat Administrasi & Status Harddisk -->
    <div style="display: flex; flex-direction: column; gap: 16px; min-width: 0;">
        <!-- Akses Cepat Persuratan -->
        <div class="card" style="padding: 16px; border-radius: 14px; border: 1px solid var(--border-color); background: var(--card-bg);">
            <div style="font-weight: 700; font-size: 0.88rem; color: var(--text-color); margin-bottom: 10px; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-bolt text-primary"></i> Akses Cepat Persuratan
            </div>
            <div style="display: flex; flex-direction: column; gap: 8px;">
                <a href="{{ route('dashboard.persuratan.masuk.index') }}" class="btn btn-outline"
                    style="display: flex; justify-content: space-between; align-items: center; padding: 9px 12px; font-size: 0.8rem; border-radius: 8px; text-decoration: none;">
                    <span><i class="fas fa-inbox text-primary me-2"></i> Surat Masuk &amp; Disposisi</span>
                    <i class="fas fa-chevron-right text-muted" style="font-size: 0.7rem;"></i>
                </a>
                <a href="{{ route('dashboard.persuratan.keluar.index') }}" class="btn btn-outline"
                    style="display: flex; justify-content: space-between; align-items: center; padding: 9px 12px; font-size: 0.8rem; border-radius: 8px; text-decoration: none;">
                    <span><i class="fas fa-paper-plane text-success me-2"></i> Surat Keluar &amp; Keterangan</span>
                    <i class="fas fa-chevron-right text-muted" style="font-size: 0.7rem;"></i>
                </a>
                <a href="{{ route('dashboard.persuratan.pengaturan.index') }}" class="btn btn-outline"
                    style="display: flex; justify-content: space-between; align-items: center; padding: 9px 12px; font-size: 0.8rem; border-radius: 8px; text-decoration: none;">
                    <span><i class="fas fa-sliders text-warning me-2"></i> Pengaturan &amp; Arsip HDD</span>
                    <i class="fas fa-chevron-right text-muted" style="font-size: 0.7rem;"></i>
                </a>
                <a href="{{ route('dashboard.tendik.aktivitas.index', ['bidang' => 'persuratan']) }}" class="btn btn-outline"
                    style="display: flex; justify-content: space-between; align-items: center; padding: 9px 12px; font-size: 0.8rem; border-radius: 8px; text-decoration: none;">
                    <span><i class="fas fa-clipboard-check text-info me-2"></i> Log Aktivitas Harian</span>
                    <i class="fas fa-chevron-right text-muted" style="font-size: 0.7rem;"></i>
                </a>
            </div>
        </div>

        <!-- Status Harddisk (HDD) -->
        <div class="card" style="padding: 16px; border-radius: 14px; background: linear-gradient(135deg, rgba(99,102,241,0.06) 0%, rgba(16,185,129,0.04) 100%); border: 1px solid rgba(99,102,241,0.2);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                <div style="font-weight: 700; font-size: 0.84rem; color: var(--text-color); display: flex; align-items: center; gap: 6px;">
                    <i class="fas fa-hard-drive text-primary"></i> Penyimpanan Arsip HDD
                </div>
                <span class="badge-compact {{ ($hdd['is_ready'] ?? false) ? 'badge-success' : 'badge-danger' }}" style="font-size: 0.7rem;">
                    {{ ($hdd['is_ready'] ?? false) ? 'Terhubung' : 'Terputus' }}
                </span>
            </div>

            <div style="font-size: 0.74rem; color: var(--text-muted); margin-bottom: 8px; font-family: monospace; word-break: break-all;">
                {{ $hdd['path'] ?? '-' }}
            </div>

            <div style="width: 100%; height: 6px; background: var(--border-color); border-radius: 3px; overflow: hidden; margin-bottom: 6px;">
                <div style="width: {{ min(100, $hdd['percent_used'] ?? 0) }}%; height: 100%; background: #10b981; border-radius: 3px;"></div>
            </div>

            <div style="display: flex; justify-content: space-between; font-size: 0.7rem; color: var(--text-muted);">
                <span>Tersedia: <strong>{{ $hdd['free_formatted'] ?? '-' }}</strong></span>
                <span>Total: <strong>{{ $hdd['total_formatted'] ?? '-' }}</strong></span>
            </div>
        </div>
    </div>
</div>
