@extends('layouts.dashboard')

@section('title', 'Rekap Laporan Presensi Tendik — SAE')
@section('dash_title', 'Rekap Presensi & Kehadiran Tendik')

@section('content')
    <!-- Banner Identitas Pegawai & Aksi Cetak -->
    <div class="dash-banner"
        style="margin-bottom: 24px; background: linear-gradient(135deg, rgba(59, 130, 246, 0.12) 0%, rgba(16, 185, 129, 0.08) 100%); border: 1px solid rgba(59, 130, 246, 0.25); display: flex; align-items: center; justify-content: space-between; gap: 20px; flex-wrap: wrap; border-radius: 16px; padding: 20px 24px;">
        <div style="display: flex; align-items: center; gap: 16px; flex: 1; min-width: 260px;">
            @if (!empty($pegawai['foto_url']))
                <div style="flex-shrink: 0; width: 72px; height: 96px; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
                    <img src="{{ $pegawai['foto_url'] }}" alt="{{ $pegawai['nama'] }}" style="width: 100%; height: 100%; object-fit: contain;">
                </div>
            @endif

            <div>
                <h2 style="font-size: 1.35rem; font-weight: 800; color: var(--text-color); margin-bottom: 4px; line-height: 1.25;">
                    <i class="fas fa-file-invoice text-primary me-2"></i> Laporan Rekapitulasi Presensi
                </h2>
                <div style="display: flex; gap: 14px; font-size: 0.82rem; color: var(--text-muted); flex-wrap: wrap; margin-bottom: 6px;">
                    <span title="Nama Pegawai"><i class="fas fa-user text-primary me-1"></i> <strong>{{ $pegawai['nama'] }}</strong></span>
                    <span title="Nomor Induk Pegawai"><i class="fas fa-id-badge text-warning me-1"></i> <strong>{{ $pegawai['nip'] }}</strong></span>
                    <span title="Penugasan"><i class="fas fa-briefcase text-info me-1"></i> <strong>{{ $pegawai['jabatan'] }}</strong></span>
                    <span title="Status Kepegawaian"><i class="fas fa-id-card-clip text-success me-1"></i> <span class="badge badge-info" style="font-size: 0.72rem; padding: 2px 6px;">{{ $pegawai['status_kepegawaian'] }}</span></span>
                </div>
                <div style="display: inline-flex; align-items: center; gap: 6px; padding: 3px 10px; background: rgba(59,130,246,0.1); border: 1px solid rgba(59,130,246,0.2); border-radius: 20px; font-size: 0.75rem; font-weight: 700; color: #3b82f6;">
                    <i class="fas fa-calendar-check"></i> Periode: {{ $range['label'] }}
                </div>
            </div>
        </div>

        <div class="dash-banner-actions" style="display: flex; gap: 10px; flex-wrap: wrap;">
            <a href="{{ route('dashboard.tendik.presensi.cetak', [
                'periode' => $periodeTipe,
                'bulan' => $bulan,
                'tahun' => $tahun,
                'triwulan' => $triwulan,
                'semester' => $semester,
                'tahun_ajaran' => $tahunAjaran
            ]) }}" target="_blank" class="btn btn-primary"
                style="background: #3b82f6; border: none; padding: 10px 18px; font-size: 0.85rem; border-radius: 8px; font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 12px rgba(59,130,246,0.3);">
                <i class="fas fa-print"></i> Cetak Laporan Resmi
            </a>
            <a href="{{ route('dashboard.tendik.aktivitas.index') }}" class="btn btn-outline"
                style="padding: 10px 16px; font-size: 0.85rem; border-radius: 8px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 8px;">
                <i class="fas fa-clipboard-check text-success"></i> Catat Aktivitas
            </a>
        </div>
    </div>

    <!-- Periode Navigation Wrapper: Desktop Pill Tabs & Mobile Dropdown Switcher -->
    <div class="periode-nav-wrapper">
        {{-- 1. Desktop & Tablet Pill Tabs --}}
        <div class="periode-nav-desktop">
            <a href="{{ route('dashboard.tendik.presensi.index', ['periode' => 'bulan', 'bulan' => $bulan, 'tahun' => $tahun]) }}"
                class="periode-nav-tab {{ $periodeTipe === 'bulan' ? 'active' : '' }}">
                <i class="fas fa-calendar-day"></i> Bulanan
            </a>
            <a href="{{ route('dashboard.tendik.presensi.index', ['periode' => 'triwulan', 'triwulan' => $triwulan, 'tahun' => $tahun]) }}"
                class="periode-nav-tab {{ $periodeTipe === 'triwulan' ? 'active' : '' }}">
                <i class="fas fa-chart-pie"></i> Triwulan (3 Bulan)
            </a>
            <a href="{{ route('dashboard.tendik.presensi.index', ['periode' => 'semester', 'semester' => $semester, 'tahun_ajaran' => $tahunAjaran]) }}"
                class="periode-nav-tab {{ $periodeTipe === 'semester' ? 'active' : '' }}">
                <i class="fas fa-layer-group"></i> Semester (6 Bulan)
            </a>
            <a href="{{ route('dashboard.tendik.presensi.index', ['periode' => 'tahun', 'tahun_ajaran' => $tahunAjaran]) }}"
                class="periode-nav-tab {{ $periodeTipe === 'tahun' ? 'active' : '' }}">
                <i class="fas fa-calendar-check"></i> Tahun Ajaran
            </a>
        </div>

        {{-- 2. Mobile Responsive Dropdown Switcher --}}
        <div class="periode-nav-mobile">
            <div class="periode-mobile-select-box">
                <label><i class="fas fa-sliders text-primary me-1"></i> Mode Rentang Periode Laporan:</label>
                <select class="periode-mobile-select" onchange="if(this.value) window.location.href=this.value;">
                    <option value="{{ route('dashboard.tendik.presensi.index', ['periode' => 'bulan', 'bulan' => $bulan, 'tahun' => $tahun]) }}"
                        {{ $periodeTipe === 'bulan' ? 'selected' : '' }}>
                        📅 Periode Bulanan
                    </option>
                    <option value="{{ route('dashboard.tendik.presensi.index', ['periode' => 'triwulan', 'triwulan' => $triwulan, 'tahun' => $tahun]) }}"
                        {{ $periodeTipe === 'triwulan' ? 'selected' : '' }}>
                        📊 Periode Triwulan (3 Bulan)
                    </option>
                    <option value="{{ route('dashboard.tendik.presensi.index', ['periode' => 'semester', 'semester' => $semester, 'tahun_ajaran' => $tahunAjaran]) }}"
                        {{ $periodeTipe === 'semester' ? 'selected' : '' }}>
                        📑 Periode Semester (6 Bulan)
                    </option>
                    <option value="{{ route('dashboard.tendik.presensi.index', ['periode' => 'tahun', 'tahun_ajaran' => $tahunAjaran]) }}"
                        {{ $periodeTipe === 'tahun' ? 'selected' : '' }}>
                        🎓 Periode 1 Tahun Ajaran Penuh
                    </option>
                </select>
            </div>
        </div>
    </div>

    <!-- Filter Form Parameters Card -->
    <div class="filter-card-container">
        <form action="{{ route('dashboard.tendik.presensi.index') }}" method="GET" class="filter-form-row">
            <input type="hidden" name="periode" value="{{ $periodeTipe }}">

            @if ($periodeTipe === 'triwulan')
                <div class="filter-item-group">
                    <label style="font-size: 0.78rem; font-weight: 700; color: var(--text-muted); margin: 0;">Pilih Triwulan:</label>
                    <select name="triwulan" class="filter-select" onchange="this.form.submit()">
                        <option value="1" {{ (int)$triwulan === 1 ? 'selected' : '' }}>Triwulan I (Januari - Maret)</option>
                        <option value="2" {{ (int)$triwulan === 2 ? 'selected' : '' }}>Triwulan II (April - Juni)</option>
                        <option value="3" {{ (int)$triwulan === 3 ? 'selected' : '' }}>Triwulan III (Juli - September)</option>
                        <option value="4" {{ (int)$triwulan === 4 ? 'selected' : '' }}>Triwulan IV (Oktober - Desember)</option>
                    </select>
                </div>
                <div class="filter-item-group">
                    <label style="font-size: 0.78rem; font-weight: 700; color: var(--text-muted); margin: 0;">Tahun Kalender:</label>
                    <select name="tahun" class="filter-select" onchange="this.form.submit()">
                        @for ($y = date('Y') + 1; $y >= date('Y') - 3; $y--)
                            <option value="{{ $y }}" {{ (int)$tahun === $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>
                </div>
            @elseif ($periodeTipe === 'semester')
                <div class="filter-item-group">
                    <label style="font-size: 0.78rem; font-weight: 700; color: var(--text-muted); margin: 0;">Pilih Semester:</label>
                    <select name="semester" class="filter-select" onchange="this.form.submit()">
                        <option value="1" {{ (string)$semester === '1' ? 'selected' : '' }}>Semester Ganjil (Juli - Desember)</option>
                        <option value="2" {{ (string)$semester === '2' ? 'selected' : '' }}>Semester Genap (Januari - Juni)</option>
                    </select>
                </div>
                <div class="filter-item-group">
                    <label style="font-size: 0.78rem; font-weight: 700; color: var(--text-muted); margin: 0;">Tahun Ajaran:</label>
                    <select name="tahun_ajaran" class="filter-select" onchange="this.form.submit()">
                        @php $baseY = (int)date('Y'); @endphp
                        @for ($y = $baseY + 1; $y >= $baseY - 2; $y--)
                            @php $taVal = "{$y}/" . ($y + 1); @endphp
                            <option value="{{ $taVal }}" {{ $tahunAjaran === $taVal ? 'selected' : '' }}>TA {{ $taVal }}</option>
                        @endfor
                    </select>
                </div>
            @elseif ($periodeTipe === 'tahun')
                <div class="filter-item-group">
                    <label style="font-size: 0.78rem; font-weight: 700; color: var(--text-muted); margin: 0;">Tahun Ajaran Penuh:</label>
                    <select name="tahun_ajaran" class="filter-select" onchange="this.form.submit()">
                        @php $baseY = (int)date('Y'); @endphp
                        @for ($y = $baseY + 1; $y >= $baseY - 2; $y--)
                            @php $taVal = "{$y}/" . ($y + 1); @endphp
                            <option value="{{ $taVal }}" {{ $tahunAjaran === $taVal ? 'selected' : '' }}>TA {{ $taVal }} (1 Juli {{ $y }} - 30 Juni {{ $y+1 }})</option>
                        @endfor
                    </select>
                </div>
            @else
                <!-- Bulan (Default) -->
                <div class="filter-item-group">
                    <label style="font-size: 0.78rem; font-weight: 700; color: var(--text-muted); margin: 0;">Bulan:</label>
                    <select name="bulan" class="filter-select" onchange="this.form.submit()">
                        @for ($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" {{ (int)$bulan === $m ? 'selected' : '' }}>
                                {{ \Carbon\Carbon::create(null, $m, 1)->translatedFormat('F') }}
                            </option>
                        @endfor
                    </select>
                </div>
                <div class="filter-item-group">
                    <label style="font-size: 0.78rem; font-weight: 700; color: var(--text-muted); margin: 0;">Tahun:</label>
                    <select name="tahun" class="filter-select" onchange="this.form.submit()">
                        @for ($y = date('Y') + 1; $y >= date('Y') - 3; $y--)
                            <option value="{{ $y }}" {{ (int)$tahun === $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>
                </div>
            @endif

            <div style="display: flex; gap: 8px;">
                <button type="submit" class="btn btn-primary" style="padding: 8px 18px; font-size: 0.85rem; border-radius: 8px; font-weight: 700; display: inline-flex; align-items: center; gap: 6px;">
                    <i class="fas fa-arrows-rotate"></i> Terapkan
                </button>
            </div>

            <div style="margin-left: auto;">
                <span class="badge" style="background: rgba(59,130,246,0.12); color: #3b82f6; border: 1px solid rgba(59,130,246,0.25); padding: 8px 14px; border-radius: 8px; font-size: 0.82rem; font-weight: 700; display: inline-flex; align-items: center; gap: 6px;">
                    <i class="fas fa-calendar-check"></i> {{ $range['label'] }}
                </span>
            </div>
        </form>
    </div>

    <!-- Quick Stats Grid -->
    <div class="dash-stat-grid" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; margin-bottom: 24px;">
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(16, 185, 129, 0.15); color: #10b981;">
                <i class="fas fa-calendar-check"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="color: #10b981;">{{ $stats['persen'] }}%</div>
                <div class="dash-stat-label">Persentase Kehadiran</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(59, 130, 246, 0.15); color: #3b82f6;">
                <i class="fas fa-business-time"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ $stats['hari_efektif'] }} Hari</div>
                <div class="dash-stat-label">Hari Efektif Kerja (HEB)</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(99, 102, 241, 0.15); color: var(--primary);">
                <i class="fas fa-user-check"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ $stats['hadir'] }} Hari</div>
                <div class="dash-stat-label">Hadir Tepat Waktu</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b;">
                <i class="fas fa-clock"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="color: #f59e0b;">{{ $stats['terlambat'] }} Hari</div>
                <div class="dash-stat-label">Terlambat Datang</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(6, 182, 212, 0.15); color: #06b6d4;">
                <i class="fas fa-notes-medical"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ $stats['izin'] + $stats['sakit'] }} Hari</div>
                <div class="dash-stat-label">Izin / Sakit / Cuti</div>
            </div>
        </div>
    </div>

    <!-- Table Rekap Presensi Harian -->
    <div class="card table-responsive-stack" style="padding: 0; border-radius: 14px; overflow: hidden; margin-bottom: 24px;">
        <div style="padding: 16px 20px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; background: rgba(255,255,255,0.02);">
            <div style="font-weight: 700; font-size: 0.95rem; color: var(--text-color);">
                <i class="fas fa-table-list text-primary me-2"></i> Log Presensi &amp; Ketepatan Waktu Kerja
            </div>
            <span class="badge badge-info" style="font-size: 0.74rem; padding: 4px 10px;">
                {{ $range['label'] }}
            </span>
        </div>

        <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
            <thead>
                <tr style="border-bottom: 1px solid var(--border-color); background: rgba(255,255,255,0.02);">
                    <th style="padding: 12px 18px; font-size: 0.78rem; text-transform: uppercase; color: var(--text-muted); width: 150px;">Tanggal</th>
                    <th style="padding: 12px 14px; font-size: 0.78rem; text-transform: uppercase; color: var(--text-muted); text-align: center;">Jam Masuk</th>
                    <th style="padding: 12px 14px; font-size: 0.78rem; text-transform: uppercase; color: var(--text-muted); text-align: center;">Jam Pulang</th>
                    <th style="padding: 12px 16px; font-size: 0.78rem; text-transform: uppercase; color: var(--text-muted);">Status Kehadiran</th>
                    <th style="padding: 12px 16px; font-size: 0.78rem; text-transform: uppercase; color: var(--text-muted);">Keterangan / Catatan</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($logs as $log)
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 12px 18px; font-size: 0.82rem; font-weight: 600; color: var(--text-color);">
                            {{ \Carbon\Carbon::parse($log->tanggal)->translatedFormat('l, d M Y') }}
                        </td>
                        <td style="padding: 12px 14px; text-align: center; font-family: monospace; font-size: 0.82rem;">
                            {{ $log->jam_masuk ? substr($log->jam_masuk, 0, 5) . ' WIB' : '—' }}
                        </td>
                        <td style="padding: 12px 14px; text-align: center; font-family: monospace; font-size: 0.82rem;">
                            {{ $log->jam_pulang ? substr($log->jam_pulang, 0, 5) . ' WIB' : '—' }}
                        </td>
                        <td style="padding: 12px 16px; font-size: 0.82rem;">
                            @if ($log->status === 'H')
                                <span class="badge badge-success" style="font-size: 0.74rem; padding: 4px 8px;">
                                    <i class="fas fa-circle-check me-1"></i> Hadir Tepat Waktu
                                </span>
                            @elseif ($log->status === 'T')
                                <span class="badge badge-warning" style="font-size: 0.74rem; padding: 4px 8px;">
                                    <i class="fas fa-clock me-1"></i> Terlambat ({{ $log->menit_terlambat }} m)
                                </span>
                            @elseif ($log->status === 'I')
                                <span class="badge badge-info" style="font-size: 0.74rem; padding: 4px 8px;">
                                    <i class="fas fa-envelope-open-text me-1"></i> Izin
                                </span>
                            @elseif ($log->status === 'S')
                                <span class="badge badge-info" style="font-size: 0.74rem; padding: 4px 8px;">
                                    <i class="fas fa-hospital me-1"></i> Sakit
                                </span>
                            @else
                                <span class="badge badge-danger" style="font-size: 0.74rem; padding: 4px 8px;">
                                    <i class="fas fa-times-circle me-1"></i> Alpha
                                </span>
                            @endif
                        </td>
                        <td style="padding: 12px 16px; font-size: 0.82rem; color: var(--text-muted);">
                            {{ $log->keterangan ?: 'Presensi terverifikasi via RFID Gateway' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 36px 16px; color: var(--text-muted);">
                            <i class="fas fa-calendar-check" style="font-size: 2.2rem; opacity: 0.3; margin-bottom: 10px; display: block;"></i>
                            <div style="font-weight: 600;">Rekap presensi pegawai tercatat 100% aktif ({{ $stats['hadir'] }} hari kerja efektif).</div>
                            <div style="font-size: 0.78rem; margin-top: 4px;">Klik tombol <strong>"Cetak Laporan Resmi"</strong> di atas untuk mengunduh berkas laporan cetak resmi.</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($logs->hasPages())
        <div class="custom-pagination" style="margin-bottom: 24px;">
            {{-- Paginasi Baku --}}
            @if ($logs->onFirstPage())
                <span class="page-btn disabled"><i class="fas fa-chevron-left"></i></span>
            @else
                <a href="{{ $logs->previousPageUrl() }}" class="page-btn"><i class="fas fa-chevron-left"></i></a>
            @endif
            @for ($i = max(1, $logs->currentPage() - 2); $i <= min($logs->lastPage(), $logs->currentPage() + 2); $i++)
                <a href="{{ $logs->url($i) }}" class="page-btn {{ $i === $logs->currentPage() ? 'current' : '' }}">{{ $i }}</a>
            @endfor
            @if ($logs->hasMorePages())
                <a href="{{ $logs->nextPageUrl() }}" class="page-btn"><i class="fas fa-chevron-right"></i></a>
            @else
                <span class="page-btn disabled"><i class="fas fa-chevron-right"></i></span>
            @endif
        </div>
    @endif
@endsection
