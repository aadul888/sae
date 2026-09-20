@extends('layouts.dashboard')

@section('title', 'Laporan Kinerja & Aktivitas Tendik — SAE')
@section('dash_title', 'Laporan Kinerja Tendik')

@section('content')
    <div class="dash-page-container">
        {{-- Banner Header & Quick Action --}}
        <div class="dash-banner" style="margin-bottom: 22px;">
            <div style="display: flex; align-items: center; gap: 16px; flex: 1; min-width: 260px;">
                @if (!empty($profile['foto_url']))
                    <div style="flex-shrink: 0; width: 64px; height: 80px; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.2); border: 2px solid rgba(255,255,255,0.1);">
                        <img src="{{ $profile['foto_url'] }}" alt="{{ $profile['nama'] }}" style="width: 100%; height: 100%; object-fit: cover;">
                    </div>
                @endif
                <div>
                    <h2 style="margin: 0 0 4px 0; font-size: 1.35rem; font-weight: 800; color: var(--text-color); line-height: 1.25;">
                        <i class="fas fa-file-lines text-primary me-2"></i> Laporan Kinerja &amp; Aktivitas
                    </h2>
                    <p style="margin: 0 0 8px 0; font-size: 0.82rem; color: var(--text-muted); line-height: 1.4;">
                        Rekapitulasi log aktivitas harian, capaian tugas, dan akumulasi durasi penugasan tenaga kependidikan.
                    </p>
                    <div style="display: flex; gap: 10px; font-size: 0.78rem; color: var(--text-muted); flex-wrap: wrap;">
                        <span><i class="fas fa-user text-primary me-1"></i> <strong>{{ $profile['nama'] }}</strong></span>
                        @if (!empty($profile['nip']))
                            <span><i class="fas fa-id-badge text-warning me-1"></i> <strong>{{ $profile['nip'] }}</strong></span>
                        @endif
                        <span><i class="fas fa-briefcase text-info me-1"></i> <span class="badge" style="background: rgba(59,130,246,0.15); color: #3b82f6; font-size: 0.72rem; padding: 2px 6px;">{{ $profile['tugas_tambahan'] ?? $profile['jabatan'] }}</span></span>
                    </div>
                </div>
            </div>

            <div class="dash-banner-actions" style="display: flex; gap: 8px; align-items: center;">
                <a href="{{ route('dashboard.tendik.laporan.cetak', request()->all()) }}" target="_blank"
                    class="btn btn-primary" style="padding: 8px 14px; font-size: 0.9rem; border-radius: 8px; font-weight: 700; text-decoration: none; box-shadow: 0 4px 12px rgba(59,130,246,0.35);" title="Cetak Dokumen Resmi">
                    <i class="fas fa-print"></i>
                </a>
                <a href="{{ route('dashboard.tendik.aktivitas.index') }}" class="btn btn-outline"
                    style="padding: 8px 14px; font-size: 0.9rem; border-radius: 8px; font-weight: 700; text-decoration: none;" title="Catat Aktivitas Baru">
                    <i class="fas fa-plus text-success"></i>
                </a>
            </div>
        </div>

        {{-- Periode Navigation Wrapper: Desktop Pill Tabs & Mobile Dropdown Switcher --}}
        <div class="periode-nav-wrapper">
            {{-- 1. Desktop & Tablet Pill Tabs --}}
            <div class="periode-nav-desktop">
                <a href="{{ route('dashboard.tendik.laporan.index', ['periode' => 'bulan', 'bulan' => $bulan, 'tahun' => $tahun]) }}"
                    class="periode-nav-tab {{ $periodeTipe === 'bulan' ? 'active' : '' }}">
                    <i class="fas fa-calendar-day"></i> Bulanan
                </a>
                <a href="{{ route('dashboard.tendik.laporan.index', ['periode' => 'triwulan', 'triwulan' => $triwulan, 'tahun' => $tahun]) }}"
                    class="periode-nav-tab {{ $periodeTipe === 'triwulan' ? 'active' : '' }}">
                    <i class="fas fa-chart-pie"></i> Triwulan (3 Bulan)
                </a>
                <a href="{{ route('dashboard.tendik.laporan.index', ['periode' => 'semester', 'semester' => $semester, 'tahun_ajaran' => $tahunAjaran]) }}"
                    class="periode-nav-tab {{ $periodeTipe === 'semester' ? 'active' : '' }}">
                    <i class="fas fa-layer-group"></i> Semester (6 Bulan)
                </a>
                <a href="{{ route('dashboard.tendik.laporan.index', ['periode' => 'tahun', 'tahun_ajaran' => $tahunAjaran]) }}"
                    class="periode-nav-tab {{ $periodeTipe === 'tahun' ? 'active' : '' }}">
                    <i class="fas fa-calendar-check"></i> Tahun Ajaran
                </a>
            </div>

            {{-- 2. Mobile Responsive Dropdown Switcher --}}
            <div class="periode-nav-mobile">
                <div class="periode-mobile-select-box">
                    <label><i class="fas fa-sliders text-primary me-1"></i> Mode Rentang Periode Laporan:</label>
                    <select class="periode-mobile-select" onchange="if(this.value) window.location.href=this.value;">
                        <option value="{{ route('dashboard.tendik.laporan.index', ['periode' => 'bulan', 'bulan' => $bulan, 'tahun' => $tahun]) }}"
                            {{ $periodeTipe === 'bulan' ? 'selected' : '' }}>
                            📅 Periode Bulanan
                        </option>
                        <option value="{{ route('dashboard.tendik.laporan.index', ['periode' => 'triwulan', 'triwulan' => $triwulan, 'tahun' => $tahun]) }}"
                            {{ $periodeTipe === 'triwulan' ? 'selected' : '' }}>
                            📊 Periode Triwulan (3 Bulan)
                        </option>
                        <option value="{{ route('dashboard.tendik.laporan.index', ['periode' => 'semester', 'semester' => $semester, 'tahun_ajaran' => $tahunAjaran]) }}"
                            {{ $periodeTipe === 'semester' ? 'selected' : '' }}>
                            📑 Periode Semester (6 Bulan)
                        </option>
                        <option value="{{ route('dashboard.tendik.laporan.index', ['periode' => 'tahun', 'tahun_ajaran' => $tahunAjaran]) }}"
                            {{ $periodeTipe === 'tahun' ? 'selected' : '' }}>
                            🎓 Periode 1 Tahun Ajaran Penuh
                        </option>
                    </select>
                </div>
            </div>
        </div>

        {{-- Filter Box Spesifik Periode --}}
        <div class="filter-card-container">
            <form action="{{ route('dashboard.tendik.laporan.index') }}" method="GET" class="filter-form-row">
                <input type="hidden" name="periode" value="{{ $periodeTipe }}">

                @if ($periodeTipe === 'bulan')
                    <div class="filter-item-group">
                        <label style="font-size: 0.78rem; font-weight: 700; color: var(--text-muted); margin: 0;">Bulan:</label>
                        <select name="bulan" class="filter-select">
                            @for ($m = 1; $m <= 12; $m++)
                                <option value="{{ $m }}" {{ $bulan == $m ? 'selected' : '' }}>
                                    {{ \Carbon\Carbon::create(null, $m, 1)->translatedFormat('F') }}
                                </option>
                            @endfor
                        </select>
                    </div>
                    <div class="filter-item-group">
                        <label style="font-size: 0.78rem; font-weight: 700; color: var(--text-muted); margin: 0;">Tahun:</label>
                        <select name="tahun" class="filter-select">
                            @for ($y = date('Y') + 1; $y >= date('Y') - 4; $y--)
                                <option value="{{ $y }}" {{ $tahun == $y ? 'selected' : '' }}>{{ $y }}</option>
                            @endfor
                        </select>
                    </div>
                @elseif ($periodeTipe === 'triwulan')
                    <div class="filter-item-group">
                        <label style="font-size: 0.78rem; font-weight: 700; color: var(--text-muted); margin: 0;">Pilih Triwulan:</label>
                        <select name="triwulan" class="filter-select">
                            <option value="1" {{ $triwulan == 1 ? 'selected' : '' }}>Triwulan I (Jan - Mar)</option>
                            <option value="2" {{ $triwulan == 2 ? 'selected' : '' }}>Triwulan II (Apr - Jun)</option>
                            <option value="3" {{ $triwulan == 3 ? 'selected' : '' }}>Triwulan III (Jul - Sep)</option>
                            <option value="4" {{ $triwulan == 4 ? 'selected' : '' }}>Triwulan IV (Okt - Des)</option>
                        </select>
                    </div>
                    <div class="filter-item-group">
                        <label style="font-size: 0.78rem; font-weight: 700; color: var(--text-muted); margin: 0;">Tahun:</label>
                        <select name="tahun" class="filter-select">
                            @for ($y = date('Y') + 1; $y >= date('Y') - 4; $y--)
                                <option value="{{ $y }}" {{ $tahun == $y ? 'selected' : '' }}>{{ $y }}</option>
                            @endfor
                        </select>
                    </div>
                @elseif ($periodeTipe === 'semester')
                    <div class="filter-item-group">
                        <label style="font-size: 0.78rem; font-weight: 700; color: var(--text-muted); margin: 0;">Semester:</label>
                        <select name="semester" class="filter-select">
                            <option value="1" {{ $semester == '1' ? 'selected' : '' }}>Semester 1 (Ganjil - Jul s/d Des)</option>
                            <option value="2" {{ $semester == '2' ? 'selected' : '' }}>Semester 2 (Genap - Jan s/d Jun)</option>
                        </select>
                    </div>
                    <div class="filter-item-group">
                        <label style="font-size: 0.78rem; font-weight: 700; color: var(--text-muted); margin: 0;">Tahun Ajaran:</label>
                        <input type="text" name="tahun_ajaran" value="{{ $tahunAjaran }}"
                            class="filter-select" placeholder="2025/2026" style="width: 120px;">
                    </div>
                @elseif ($periodeTipe === 'tahun')
                    <div class="filter-item-group">
                        <label style="font-size: 0.78rem; font-weight: 700; color: var(--text-muted); margin: 0;">Tahun Ajaran:</label>
                        <input type="text" name="tahun_ajaran" value="{{ $tahunAjaran }}"
                            class="filter-select" placeholder="2025/2026" style="width: 140px;">
                    </div>
                @endif

                <div style="display: flex; gap: 8px;">
                    <button type="submit" class="btn btn-primary" title="Terapkan Filter"
                        style="padding: 8px 14px; font-size: 0.84rem; border-radius: 8px; font-weight: 700;">
                        <i class="fas fa-filter"></i>
                    </button>
                </div>

                <div style="margin-left: auto;">
                    <span class="badge" style="background: rgba(59, 130, 246, 0.12); color: #3b82f6; border: 1px solid rgba(59, 130, 246, 0.25); padding: 8px 14px; border-radius: 8px; font-size: 0.82rem; font-weight: 700; display: inline-flex; align-items: center; gap: 6px;">
                        <i class="fas fa-calendar-check"></i> {{ $range['label'] }}
                    </span>
                </div>
            </form>
        </div>

        {{-- Summary Stats Grid (Standar Baku SAE sama seperti Peserta Didik Aktif) --}}
        <div class="dash-stat-grid" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); margin-bottom: 24px;">
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(59, 130, 246, 0.15); color: #3b82f6;">
                    <i class="fas fa-tasks"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ number_format($totalAktivitas, 0, ',', '.') }}</div>
                    <div class="dash-stat-label">Total Aktivitas / Tugas</div>
                </div>
            </div>

            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(16, 185, 129, 0.15); color: #10b981;">
                    <i class="fas fa-circle-check"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ number_format($totalSelesai, 0, ',', '.') }}</div>
                    <div class="dash-stat-label">Tuntas ({{ $persentaseSelesai }}%)</div>
                </div>
            </div>

            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b;">
                    <i class="fas fa-spinner"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ number_format($totalProses, 0, ',', '.') }}</div>
                    <div class="dash-stat-label">Dalam Pengerjaan</div>
                </div>
            </div>

            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(99, 102, 241, 0.15); color: var(--primary);">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ $totalJam }}j {{ $sisaMenit }}m</div>
                    <div class="dash-stat-label">Total Durasi Kerja</div>
                </div>
            </div>

            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(139, 92, 246, 0.15); color: #8b5cf6;">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ $hariAktifBekerja }} Hari</div>
                    <div class="dash-stat-label">Hari Kerja Aktif</div>
                </div>
            </div>
        </div>

        {{-- Ringkasan Bulanan (Jika Triwulan, Semester, atau Tahun) --}}
        @if (!empty($rekapBulanan))
            <div class="card table-responsive-stack" style="padding: 0; margin-bottom: 24px;">
                <div style="padding: 14px 20px; border-bottom: 1px solid var(--border-color, rgba(255,255,255,0.08)); display: flex; align-items: center; justify-content: space-between;">
                    <h3 style="font-size: 0.95rem; font-weight: 700; margin: 0; color: var(--text-color, #f8fafc);">
                        <i class="fas fa-chart-line text-primary me-2"></i> Rekap Akumulasi per Bulan
                    </h3>
                    <span class="badge" style="background: rgba(255,255,255,0.05); color: var(--text-muted, #94a3b8); font-size: 0.75rem;">
                        {{ count($rekapBulanan) }} Bulan Terdata
                    </span>
                </div>
                <div style="overflow-x: auto;">
                    <table class="table-laporan">
                        <thead>
                            <tr>
                                <th>Bulan</th>
                                <th style="text-align: center;">Hari Aktif</th>
                                <th style="text-align: center;">Total Pekerjaan</th>
                                <th style="text-align: center;">Selesai</th>
                                <th style="text-align: center;">Proses</th>
                                <th style="text-align: right;">Durasi Kerja</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rekapBulanan as $rb)
                                <tr>
                                    <td data-label="Bulan" style="font-weight: 700;">{{ $rb['bulan_nama'] }}</td>
                                    <td data-label="Hari Aktif" style="text-align: center;">{{ $rb['hari_aktif'] }} hari</td>
                                    <td data-label="Total Pekerjaan" style="text-align: center; font-weight: 700;">{{ $rb['total'] }}</td>
                                    <td data-label="Selesai" style="text-align: center; color: #10b981; font-weight: 700;">{{ $rb['selesai'] }}</td>
                                    <td data-label="Proses" style="text-align: center; color: #f59e0b; font-weight: 700;">{{ $rb['proses'] }}</td>
                                    <td data-label="Durasi Kerja" style="text-align: right; font-weight: 700; font-family: monospace;">{{ $rb['durasi_jam'] }}j {{ $rb['durasi_menit'] }}m</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- Tabel Rincian Aktivitas / Pekerjaan Harian --}}
        <div class="card table-responsive-stack" id="tableDataContainer" style="padding: 0; margin-bottom: 24px;">
            <div style="display: flex; align-items: center; justify-content: space-between; padding: 16px 20px; border-bottom: 1px solid var(--border-color, rgba(255,255,255,0.08)); flex-wrap: wrap; gap: 8px;">
                <div>
                    <h3 style="font-size: 1rem; font-weight: 700; margin: 0; color: var(--text-color, #f8fafc);">
                        <i class="fas fa-clipboard-list text-primary me-2"></i> Rincian Buku Jurnal Pekerjaan Harian
                    </h3>
                    <p style="margin: 2px 0 0 0; font-size: 0.78rem; color: var(--text-muted, #94a3b8);">
                        Daftar catatan harian aktivitas kerja yang tercatat pada sistem
                    </p>
                </div>
                <span class="badge" style="background: rgba(59,130,246,0.12); color: #3b82f6; font-size: 0.78rem; padding: 5px 10px; border-radius: 6px;">
                    {{ $aktivitasList->count() }} Kegiatan
                </span>
            </div>

            <div style="overflow-x: auto;">
                <table class="table-laporan">
                    <thead>
                        <tr>
                            <th style="width: 45px; text-align: center;">No</th>
                            <th style="width: 120px;">Tanggal</th>
                            <th style="width: 120px;">Jam Kerja</th>
                            <th>Uraian Aktivitas / Pekerjaan</th>
                            <th style="width: 140px;">Output / Volume</th>
                            <th style="width: 110px;">Durasi</th>
                            <th style="width: 110px;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($aktivitasList as $idx => $akt)
                            <tr>
                                <td data-label="No" style="text-align: center; color: var(--text-muted, #94a3b8);">{{ $idx + 1 }}</td>
                                <td data-label="Tanggal">
                                    <div style="font-weight: 700;">{{ \Carbon\Carbon::parse($akt->tanggal)->translatedFormat('d M Y') }}</div>
                                    <small style="color: var(--text-muted, #94a3b8);">{{ \Carbon\Carbon::parse($akt->tanggal)->translatedFormat('l') }}</small>
                                </td>
                                <td data-label="Jam Kerja">
                                    <span class="badge" style="background: rgba(255,255,255,0.06); color: var(--text-color, #f8fafc); border: 1px solid var(--border-color, rgba(255,255,255,0.1)); font-family: monospace; font-size: 0.76rem; padding: 4px 8px;">
                                        {{ $akt->jam_mulai ? substr($akt->jam_mulai, 0, 5) : '07:30' }} - {{ $akt->jam_selesai ? substr($akt->jam_selesai, 0, 5) : '15:00' }}
                                    </span>
                                </td>
                                <td data-label="Uraian Aktivitas">
                                    <div style="font-weight: 700; color: var(--text-color, #f8fafc); font-size: 0.9rem;">
                                        {{ $akt->judul_aktivitas }}
                                    </div>
                                    @if ($akt->uraian_pekerjaan)
                                        <div style="color: var(--text-muted, #94a3b8); font-size: 0.8rem; margin-top: 4px; line-height: 1.4;">
                                            {{ $akt->uraian_pekerjaan }}
                                        </div>
                                    @endif
                                    <div style="margin-top: 5px;">
                                        <span class="badge" style="background: rgba(255,255,255,0.05); color: var(--text-muted, #94a3b8); font-size: 0.7rem; padding: 2px 6px; border-radius: 4px;">
                                            Bidang: {{ $akt->bidang }}
                                        </span>
                                    </div>
                                </td>
                                <td data-label="Output">
                                    <span style="font-weight: 700;">{{ $akt->output_hasil ?: '-' }}</span>
                                </td>
                                <td data-label="Durasi">
                                    <span class="badge" style="background: rgba(59,130,246,0.1); color: #3b82f6; border: 1px solid rgba(59,130,246,0.2); font-size: 0.76rem; padding: 4px 8px;">
                                        <i class="far fa-clock me-1"></i> {{ $akt->durasi_menit }} mnt
                                    </span>
                                </td>
                                <td data-label="Status">
                                    @if ($akt->status === 'selesai')
                                        <span class="badge-status badge-selesai">
                                            <i class="fas fa-check-circle"></i> Selesai
                                        </span>
                                    @elseif ($akt->status === 'proses')
                                        <span class="badge-status badge-proses">
                                            <i class="fas fa-spinner fa-spin"></i> Proses
                                        </span>
                                    @else
                                        <span class="badge-status badge-tertunda">
                                            <i class="fas fa-pause-circle"></i> Tertunda
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 48px 16px; color: var(--text-muted, #94a3b8);">
                                    <i class="fas fa-folder-open fa-3x" style="opacity: 0.3; margin-bottom: 12px; display: block;"></i>
                                    <p style="font-size: 0.95rem; margin-bottom: 8px; color: var(--text-color, #f8fafc); font-weight: 600;">
                                        Belum ada catatan aktivitas harian pada periode ini.
                                    </p>
                                    <p style="font-size: 0.8rem; margin-bottom: 16px;">
                                        Log aktivitas yang dicatat akan secara otomatis direkap dan siap dicetak menjadi dokumen resmi.
                                    </p>
                                    <a href="{{ route('dashboard.tendik.aktivitas.index') }}" class="btn btn-primary"
                                        style="font-size: 0.84rem; padding: 8px 18px; border-radius: 8px; text-decoration: none; font-weight: 700; display: inline-flex; align-items: center; gap: 6px;">
                                        <i class="fas fa-plus"></i> Tambah Aktivitas Sekarang
                                    </a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
