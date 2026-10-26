@extends('layouts.dashboard')

@section('title', 'Laporan Penggunaan & Kerusakan Alat — Laboratorium SAE')
@section('dash_title', 'Laporan Lengkap')

@section('content')
    <div class="dash-content-inner">
        {{-- 1. Banner Header --}}
        <div class="dash-banner"
            style="display: flex; align-items: center; justify-content: space-between; gap: var(--space-8); flex-wrap: wrap; margin-bottom: var(--space-9);">
            <div style="display: flex; align-items: center; gap: var(--space-7);">
                <div style="width: 48px; height: 48px; border-radius: var(--radius-lg); background: rgba(59,130,246,0.12); color: var(--primary, #3b82f6); display: flex; align-items: center; justify-content: center; font-size: var(--text-2xl); flex-shrink: 0;">
                    <i class="fas fa-file-lines"></i>
                </div>
                <div>
                    <h2 style="font-size: var(--text-xl); font-weight: var(--font-extrabold); color: var(--text-color); margin: 0;">
                        Laporan Lengkap Penggunaan &amp; Kerusakan Alat
                    </h2>
                    <div style="font-size: var(--text-sm); color: var(--text-muted); margin-top: var(--space-1);">
                        Rekapitulasi terpadu praktik, peminjaman, kerusakan, perawatan, pemeriksaan, dan pengadaan.
                    </div>
                </div>
            </div>
            <div class="dash-banner-actions" style="display: flex; gap: var(--space-4); align-items: center; flex-wrap: wrap;">
                <span class="badge" style="background: rgba(59,130,246,0.12); color: var(--primary, #3b82f6); border: 1px solid rgba(59,130,246,0.25); padding: var(--space-3) var(--space-6); border-radius: var(--radius-md); font-size: var(--text-xs); font-weight: var(--font-bold); display: inline-flex; align-items: center; gap: var(--space-2);">
                    <i class="fas {{ $scopeBadge['icon'] }}"></i> {{ $scopeBadge['label'] }}
                </span>
                <a href="{{ route('dashboard.laboran.laporan-lengkap.cetak', request()->all()) }}" target="_blank" class="btn btn-primary"
                    style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md); font-size: var(--text-md); text-decoration: none;"
                    title="Cetak Laporan Periode Ini">
                    <i class="fas fa-print"></i>
                </a>
            </div>
        </div>

        {{-- 2. Navigasi Periode --}}
        <div class="periode-nav-wrapper" style="margin-bottom: var(--space-7);">
            <div class="periode-nav-desktop">
                <a href="{{ route('dashboard.laboran.laporan-lengkap.index', ['periode' => 'bulan', 'bulan' => $bulan, 'tahun' => $tahun]) }}"
                    class="periode-nav-tab {{ $periodeTipe === 'bulan' ? 'active' : '' }}">
                    <i class="fas fa-calendar-day"></i> Bulanan
                </a>
                <a href="{{ route('dashboard.laboran.laporan-lengkap.index', ['periode' => 'triwulan', 'triwulan' => $triwulan, 'tahun' => $tahun]) }}"
                    class="periode-nav-tab {{ $periodeTipe === 'triwulan' ? 'active' : '' }}">
                    <i class="fas fa-chart-pie"></i> Triwulan
                </a>
                <a href="{{ route('dashboard.laboran.laporan-lengkap.index', ['periode' => 'semester', 'semester' => $semester, 'tahun_ajaran' => $tahunAjaran]) }}"
                    class="periode-nav-tab {{ $periodeTipe === 'semester' ? 'active' : '' }}">
                    <i class="fas fa-layer-group"></i> Semester
                </a>
                <a href="{{ route('dashboard.laboran.laporan-lengkap.index', ['periode' => 'tahun', 'tahun_ajaran' => $tahunAjaran]) }}"
                    class="periode-nav-tab {{ $periodeTipe === 'tahun' ? 'active' : '' }}">
                    <i class="fas fa-calendar-check"></i> Tahun Ajaran
                </a>
            </div>
            <div class="periode-nav-mobile">
                <div class="periode-mobile-select-box" style="margin-bottom: 0;">
                    <label><i class="fas fa-sliders text-primary me-1"></i> Mode Periode:</label>
                    <select class="periode-mobile-select" onchange="if(this.value) window.location.href=this.value;">
                        <option value="{{ route('dashboard.laboran.laporan-lengkap.index', ['periode' => 'bulan', 'bulan' => $bulan, 'tahun' => $tahun]) }}" {{ $periodeTipe === 'bulan' ? 'selected' : '' }}>Bulanan</option>
                        <option value="{{ route('dashboard.laboran.laporan-lengkap.index', ['periode' => 'triwulan', 'triwulan' => $triwulan, 'tahun' => $tahun]) }}" {{ $periodeTipe === 'triwulan' ? 'selected' : '' }}>Triwulan</option>
                        <option value="{{ route('dashboard.laboran.laporan-lengkap.index', ['periode' => 'semester', 'semester' => $semester, 'tahun_ajaran' => $tahunAjaran]) }}" {{ $periodeTipe === 'semester' ? 'selected' : '' }}>Semester</option>
                        <option value="{{ route('dashboard.laboran.laporan-lengkap.index', ['periode' => 'tahun', 'tahun_ajaran' => $tahunAjaran]) }}" {{ $periodeTipe === 'tahun' ? 'selected' : '' }}>Tahun Ajaran</option>
                    </select>
                </div>
            </div>
        </div>

        {{-- 3. Filter Periode --}}
        <div class="filter-card-container" style="margin-bottom: var(--space-7);">
            <form action="{{ route('dashboard.laboran.laporan-lengkap.index') }}" method="GET" class="filter-form-row">
                <input type="hidden" name="periode" value="{{ $periodeTipe }}">

                @if ($periodeTipe === 'bulan')
                    <div class="filter-item-group">
                        <label>Bulan:</label>
                        <select name="bulan" class="filter-select">
                            @for ($m = 1; $m <= 12; $m++)
                                <option value="{{ $m }}" {{ $bulan == $m ? 'selected' : '' }}>{{ \Carbon\Carbon::create(null, $m, 1)->translatedFormat('F') }}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="filter-item-group">
                        <label>Tahun:</label>
                        <select name="tahun" class="filter-select">
                            @for ($y = (int) date('Y') + 1; $y >= (int) date('Y') - 5; $y--)
                                <option value="{{ $y }}" {{ $tahun == $y ? 'selected' : '' }}>{{ $y }}</option>
                            @endfor
                        </select>
                    </div>
                @elseif ($periodeTipe === 'triwulan')
                    <div class="filter-item-group">
                        <label>Triwulan:</label>
                        <select name="triwulan" class="filter-select">
                            <option value="1" {{ $triwulan == 1 ? 'selected' : '' }}>I (Jan-Mar)</option>
                            <option value="2" {{ $triwulan == 2 ? 'selected' : '' }}>II (Apr-Jun)</option>
                            <option value="3" {{ $triwulan == 3 ? 'selected' : '' }}>III (Jul-Sep)</option>
                            <option value="4" {{ $triwulan == 4 ? 'selected' : '' }}>IV (Okt-Des)</option>
                        </select>
                    </div>
                    <div class="filter-item-group">
                        <label>Tahun:</label>
                        <select name="tahun" class="filter-select">
                            @for ($y = (int) date('Y') + 1; $y >= (int) date('Y') - 5; $y--)
                                <option value="{{ $y }}" {{ $tahun == $y ? 'selected' : '' }}>{{ $y }}</option>
                            @endfor
                        </select>
                    </div>
                @elseif ($periodeTipe === 'semester')
                    <div class="filter-item-group">
                        <label>Semester:</label>
                        <select name="semester" class="filter-select">
                            <option value="1" {{ $semester === '1' ? 'selected' : '' }}>Ganjil (Jul-Des)</option>
                            <option value="2" {{ $semester === '2' ? 'selected' : '' }}>Genap (Jan-Jun)</option>
                        </select>
                    </div>
                    <div class="filter-item-group">
                        <label>Tahun Ajaran:</label>
                        <input type="text" name="tahun_ajaran" value="{{ $tahunAjaran }}" class="filter-select" placeholder="2026/2027" style="width: 130px;">
                    </div>
                @else
                    <div class="filter-item-group">
                        <label>Tahun Ajaran:</label>
                        <input type="text" name="tahun_ajaran" value="{{ $tahunAjaran }}" class="filter-select" placeholder="2026/2027" style="width: 150px;">
                    </div>
                @endif

                <div class="filter-item-group">
                    <label>Jenis Lokasi:</label>
                    <select name="jenis" class="filter-select">
                        <option value="">Semua</option>
                        @foreach (\App\Http\Controllers\LaboranJadwalController::JENIS_LOKASI as $jk => $jv)
                            <option value="{{ $jk }}" {{ $jenisFilter === $jk ? 'selected' : '' }}>{{ $jv }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="filter-item-group">
                    <label>Lokasi:</label>
                    <select name="lab" class="filter-select">
                        <option value="">Semua</option>
                        @foreach ($daftarLokasi as $lok)
                            <option value="{{ $lok }}" {{ $lokasiFilter === $lok ? 'selected' : '' }}>{{ $lok }}</option>
                        @endforeach
                    </select>
                </div>

                <div style="display: flex; gap: var(--space-4);">
                    <button type="submit" class="btn btn-primary" title="Terapkan Filter"
                        style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md);">
                        <i class="fas fa-filter"></i>
                    </button>
                    <a href="{{ route('dashboard.laboran.laporan-lengkap.index', ['periode' => $periodeTipe]) }}"
                        class="btn btn-outline" title="Reset Filter"
                        style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md);">
                        <i class="fas fa-undo"></i>
                    </a>
                </div>

                <div style="margin-left: auto;">
                    <span class="badge" style="background: rgba(59, 130, 246, 0.12); color: var(--primary, #3b82f6); border: 1px solid rgba(59, 130, 246, 0.25); padding: var(--space-4) var(--space-7); border-radius: var(--radius-md); font-size: var(--text-sm); font-weight: var(--font-bold); display: inline-flex; align-items: center; gap: var(--space-3);">
                        <i class="fas fa-calendar-check"></i> {{ $range['label'] }}
                    </span>
                </div>
            </form>
        </div>

        {{-- 4. Statistik Utama --}}
        <div class="dash-stat-grid" style="grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); margin-bottom: var(--space-9); gap: var(--space-6);">
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(59, 130, 246, 0.15); color: var(--primary, #3b82f6);"><i class="fas fa-calendar-days"></i></div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ number_format($totalSesi, 0, ',', '.') }}</div>
                    <div class="dash-stat-label">Sesi Praktik</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(14, 165, 233, 0.15); color: var(--info, #0ea5e9);"><i class="fas fa-hand-holding"></i></div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ number_format($totalPeminjaman, 0, ',', '.') }}</div>
                    <div class="dash-stat-label">Peminjaman</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(239, 68, 68, 0.15); color: var(--danger, #ef4444);"><i class="fas fa-triangle-exclamation"></i></div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ number_format($totalKerusakan, 0, ',', '.') }}</div>
                    <div class="dash-stat-label">Kerusakan</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(245, 158, 11, 0.15); color: var(--warning, #f59e0b);"><i class="fas fa-screwdriver-wrench"></i></div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ number_format($totalPerawatan, 0, ',', '.') }}</div>
                    <div class="dash-stat-label">Perawatan</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(16, 185, 129, 0.15); color: var(--success, #10b981);"><i class="fas fa-list-check"></i></div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ number_format($totalPemeriksaan, 0, ',', '.') }}</div>
                    <div class="dash-stat-label">Pemeriksaan</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(139, 92, 246, 0.15); color: var(--purple, #8b5cf6);"><i class="fas fa-file-invoice-dollar"></i></div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ number_format($totalPengadaan, 0, ',', '.') }}</div>
                    <div class="dash-stat-label">Usulan Pengadaan</div>
                </div>
            </div>
        </div>

        {{-- 5. Ringkasan Biaya --}}
        <div class="card" style="padding: var(--space-9); margin-bottom: var(--space-9);">
            <h3 style="font-size: var(--text-md); font-weight: var(--font-bold); margin: 0 0 var(--space-7) 0; color: var(--text-color); display: flex; align-items: center; gap: var(--space-3);">
                <i class="fas fa-coins text-primary"></i> Ringkasan Angka Kunci
            </h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: var(--space-7);">
                <div style="padding: var(--space-6); border-radius: var(--radius-md); background: var(--bg-hover); border: 1px solid var(--border-color);">
                    <div style="font-size: var(--text-2xs); text-transform: uppercase; font-weight: var(--font-bold); color: var(--text-muted); margin-bottom: var(--space-2);">Akumulasi Durasi Praktik</div>
                    <div style="font-size: var(--text-xl); font-weight: var(--font-extrabold); color: var(--text-color);">
                        {{ intdiv($totalMenitPraktik, 60) }}j {{ $totalMenitPraktik % 60 }}m
                    </div>
                    <div style="font-size: var(--text-xs); color: var(--text-muted);">{{ $hariAktif }} hari aktif &bull; {{ $lokasiUnik }} lokasi &bull; {{ $guruUnik }} guru</div>
                </div>
                <div style="padding: var(--space-6); border-radius: var(--radius-md); background: var(--bg-hover); border: 1px solid var(--border-color);">
                    <div style="font-size: var(--text-2xs); text-transform: uppercase; font-weight: var(--font-bold); color: var(--text-muted); margin-bottom: var(--space-2);">Estimasi Kerusakan</div>
                    <div style="font-size: var(--text-xl); font-weight: var(--font-extrabold); color: var(--danger, #ef4444);">
                        Rp {{ number_format($totalEstimasi, 0, ',', '.') }}
                    </div>
                    <div style="font-size: var(--text-xs); color: var(--text-muted);">{{ $totalRusakBerat }} kerusakan berat/total</div>
                </div>
                <div style="padding: var(--space-6); border-radius: var(--radius-md); background: var(--bg-hover); border: 1px solid var(--border-color);">
                    <div style="font-size: var(--text-2xs); text-transform: uppercase; font-weight: var(--font-bold); color: var(--text-muted); margin-bottom: var(--space-2);">Biaya Perawatan</div>
                    <div style="font-size: var(--text-xl); font-weight: var(--font-extrabold); color: var(--warning, #f59e0b);">
                        Rp {{ number_format($totalBiayaRawat, 0, ',', '.') }}
                    </div>
                    <div style="font-size: var(--text-xs); color: var(--text-muted);">{{ $perawatanSelesai }} dari {{ $totalPerawatan }} selesai</div>
                </div>
                <div style="padding: var(--space-6); border-radius: var(--radius-md); background: var(--bg-hover); border: 1px solid var(--border-color);">
                    <div style="font-size: var(--text-2xs); text-transform: uppercase; font-weight: var(--font-bold); color: var(--text-muted); margin-bottom: var(--space-2);">Nilai Pengadaan Disetujui</div>
                    <div style="font-size: var(--text-xl); font-weight: var(--font-extrabold); color: var(--purple, #8b5cf6);">
                        Rp {{ number_format($totalNilaiPengadaan, 0, ',', '.') }}
                    </div>
                    <div style="font-size: var(--text-xs); color: var(--text-muted);">{{ $totalPengadaanDisetujui }} dari {{ $totalPengadaan }} usulan</div>
                </div>
            </div>
        </div>

        {{-- 6. Rekap Bulanan --}}
        @if (count($rekapBulanan) > 1)
            <div class="card" style="padding: 0; margin-bottom: var(--space-9); overflow: hidden;">
                <div style="padding: var(--space-8) var(--space-9); border-bottom: 1px solid var(--border-color);">
                    <h3 style="font-size: var(--text-md); font-weight: var(--font-bold); margin: 0; color: var(--text-color); display: flex; align-items: center; gap: var(--space-3);">
                        <i class="fas fa-chart-column text-primary"></i> Rekapitulasi Progres Bulanan
                    </h3>
                </div>
                <div style="overflow-x: auto;">
                    <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                        <thead>
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <th style="padding: var(--space-6) var(--space-8); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; text-align: left;">Bulan</th>
                                <th style="padding: var(--space-6) var(--space-8); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; text-align: center;">Praktik</th>
                                <th style="padding: var(--space-6) var(--space-8); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; text-align: center;">Peminjaman</th>
                                <th style="padding: var(--space-6) var(--space-8); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; text-align: center;">Kerusakan</th>
                                <th style="padding: var(--space-6) var(--space-8); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; text-align: center;">Perawatan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rekapBulanan as $rb)
                                <tr style="border-bottom: 1px solid var(--border-color);">
                                    <td style="padding: var(--space-6) var(--space-8); font-weight: var(--font-bold);">{{ $rb['label'] }}</td>
                                    <td style="padding: var(--space-6) var(--space-8); text-align: center; font-weight: var(--font-bold);">{{ $rb['praktik'] }}</td>
                                    <td style="padding: var(--space-6) var(--space-8); text-align: center; font-weight: var(--font-bold);">{{ $rb['pinjam'] }}</td>
                                    <td style="padding: var(--space-6) var(--space-8); text-align: center; font-weight: var(--font-bold); color: var(--danger, #ef4444);">{{ $rb['rusak'] }}</td>
                                    <td style="padding: var(--space-6) var(--space-8); text-align: center; font-weight: var(--font-bold); color: var(--warning, #f59e0b);">{{ $rb['rawat'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- 7. Rekap Lokasi Praktik --}}
        <div class="card" style="padding: 0; margin-bottom: var(--space-9); overflow: hidden;">
            <div style="padding: var(--space-8) var(--space-9); border-bottom: 1px solid var(--border-color);">
                <h3 style="font-size: var(--text-md); font-weight: var(--font-bold); margin: 0; color: var(--text-color); display: flex; align-items: center; gap: var(--space-3);">
                    <i class="fas fa-map-location-dot text-primary"></i> Penggunaan Lokasi Praktik
                </h3>
            </div>
            <div style="overflow-x: auto;">
                <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                    <thead>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <th style="padding: var(--space-6) var(--space-8); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; text-align: left;">Lokasi</th>
                            <th style="padding: var(--space-6) var(--space-8); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; text-align: center;">Sesi</th>
                            <th style="padding: var(--space-6) var(--space-8); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; text-align: center;">Selesai</th>
                            <th style="padding: var(--space-6) var(--space-8); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; text-align: right;">Durasi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rekapLokasi as $rl)
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <td style="padding: var(--space-6) var(--space-8); font-weight: var(--font-semibold);">{{ $rl->nama }}</td>
                                <td style="padding: var(--space-6) var(--space-8); text-align: center; font-weight: var(--font-bold);">{{ $rl->sesi }}</td>
                                <td style="padding: var(--space-6) var(--space-8); text-align: center; color: var(--success, #10b981); font-weight: var(--font-bold);">{{ $rl->selesai }}</td>
                                <td style="padding: var(--space-6) var(--space-8); text-align: right; font-family: var(--font-family-mono);">{{ $rl->jam }}j {{ $rl->menit }}m</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" style="padding: var(--space-9); text-align: center; color: var(--text-muted);">Tidak ada data praktik pada periode ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- 8. Kerusakan Alat --}}
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap: var(--space-7); margin-bottom: var(--space-9);">
            <div class="card" style="padding: 0; overflow: hidden;">
                <div style="padding: var(--space-8) var(--space-9); border-bottom: 1px solid var(--border-color);">
                    <h3 style="font-size: var(--text-md); font-weight: var(--font-bold); margin: 0; color: var(--text-color); display: flex; align-items: center; gap: var(--space-3);">
                        <i class="fas fa-triangle-exclamation" style="color: var(--danger, #ef4444);"></i> Rekap Tingkat Kerusakan
                    </h3>
                </div>
                <div style="overflow-x: auto;">
                    <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                        <thead>
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <th style="padding: var(--space-5) var(--space-7); font-size: var(--text-xs); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; text-align: left;">Tingkat</th>
                                <th style="padding: var(--space-5) var(--space-7); font-size: var(--text-xs); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; text-align: center;">Jumlah</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rekapTingkat as $rt)
                                <tr style="border-bottom: 1px solid var(--border-color);">
                                    <td style="padding: var(--space-5) var(--space-7); font-size: var(--text-sm); font-weight: var(--font-semibold);">{{ $rt->label }}</td>
                                    <td style="padding: var(--space-5) var(--space-7); text-align: center; font-weight: var(--font-bold);">{{ $rt->total }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="2" style="padding: var(--space-9); text-align: center; color: var(--text-muted); font-size: var(--text-sm);">Tidak ada kerusakan.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card" style="padding: 0; overflow: hidden;">
                <div style="padding: var(--space-8) var(--space-9); border-bottom: 1px solid var(--border-color);">
                    <h3 style="font-size: var(--text-md); font-weight: var(--font-bold); margin: 0; color: var(--text-color); display: flex; align-items: center; gap: var(--space-3);">
                        <i class="fas fa-tools" style="color: var(--danger, #ef4444);"></i> Alat Paling Sering Rusak
                    </h3>
                </div>
                <div style="overflow-x: auto;">
                    <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                        <thead>
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <th style="padding: var(--space-5) var(--space-7); font-size: var(--text-xs); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; text-align: left;">Alat</th>
                                <th style="padding: var(--space-5) var(--space-7); font-size: var(--text-xs); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; text-align: center;">Kejadian</th>
                                <th style="padding: var(--space-5) var(--space-7); font-size: var(--text-xs); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; text-align: right;">Biaya</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rekapAlatRusak as $ar)
                                <tr style="border-bottom: 1px solid var(--border-color);">
                                    <td style="padding: var(--space-5) var(--space-7); font-size: var(--text-sm); font-weight: var(--font-semibold);">{{ \Illuminate\Support\Str::limit($ar->nama, 34) }}</td>
                                    <td style="padding: var(--space-5) var(--space-7); text-align: center; font-weight: var(--font-bold);">{{ $ar->kejadian }}</td>
                                    <td style="padding: var(--space-5) var(--space-7); text-align: right; font-size: var(--text-xs); white-space: nowrap;">
                                        Rp {{ number_format((float) $ar->biaya, 0, ',', '.') }}
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="3" style="padding: var(--space-9); text-align: center; color: var(--text-muted); font-size: var(--text-sm);">Tidak ada data.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- 9. Peminjaman & Perawatan --}}
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap: var(--space-7); margin-bottom: var(--space-9);">
            <div class="card" style="padding: 0; overflow: hidden;">
                <div style="padding: var(--space-8) var(--space-9); border-bottom: 1px solid var(--border-color);">
                    <h3 style="font-size: var(--text-md); font-weight: var(--font-bold); margin: 0; color: var(--text-color); display: flex; align-items: center; gap: var(--space-3);">
                        <i class="fas fa-hand-holding" style="color: var(--info, #0ea5e9);"></i> Rekap Peminjaman
                    </h3>
                </div>
                <div style="padding: var(--space-8) var(--space-9);">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-5); margin-bottom: var(--space-7);">
                        <div><div style="font-size: var(--text-lg); font-weight: var(--font-extrabold); color: var(--text-color);">{{ $totalPeminjaman }}</div><div style="font-size: var(--text-2xs); color: var(--text-muted);">Total</div></div>
                        <div><div style="font-size: var(--text-lg); font-weight: var(--font-extrabold); color: var(--info, #0ea5e9);">{{ $totalDipinjam }}</div><div style="font-size: var(--text-2xs); color: var(--text-muted);">Dipinjam</div></div>
                        <div><div style="font-size: var(--text-lg); font-weight: var(--font-extrabold); color: var(--success, #10b981);">{{ $totalDikembalikan }}</div><div style="font-size: var(--text-2xs); color: var(--text-muted);">Kembali</div></div>
                        <div><div style="font-size: var(--text-lg); font-weight: var(--font-extrabold); color: var(--danger, #ef4444);">{{ $totalTerlambat }}</div><div style="font-size: var(--text-2xs); color: var(--text-muted);">Terlambat</div></div>
                    </div>
                    <div style="border-top: 1px solid var(--border-color); padding-top: var(--space-6);">
                        @forelse ($rekapPeminjam as $rp)
                            <div style="display: flex; justify-content: space-between; padding: var(--space-3) 0; font-size: var(--text-sm);">
                                <span style="color: var(--text-muted);">{{ $rp->label }}</span>
                                <span style="font-weight: var(--font-bold); color: var(--text-color);">{{ $rp->total }}</span>
                            </div>
                        @empty
                            <p style="font-size: var(--text-sm); color: var(--text-muted); margin: 0;">Tidak ada peminjaman.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="card" style="padding: 0; overflow: hidden;">
                <div style="padding: var(--space-8) var(--space-9); border-bottom: 1px solid var(--border-color);">
                    <h3 style="font-size: var(--text-md); font-weight: var(--font-bold); margin: 0; color: var(--text-color); display: flex; align-items: center; gap: var(--space-3);">
                        <i class="fas fa-screwdriver-wrench" style="color: var(--warning, #f59e0b);"></i> Rekap Perawatan
                    </h3>
                </div>
                <div style="padding: var(--space-8) var(--space-9);">
                    @forelse ($rekapJenisRawat as $jr)
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: var(--space-4) 0; border-bottom: 1px dashed var(--border-color); font-size: var(--text-sm);">
                            <span style="color: var(--text-color); font-weight: var(--font-semibold);">{{ $jr->label }}</span>
                            <span style="color: var(--text-muted);">{{ $jr->selesai }}/{{ $jr->total }} selesai</span>
                        </div>
                    @empty
                        <p style="font-size: var(--text-sm); color: var(--text-muted); margin: 0;">Tidak ada perawatan.</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- 10. Kondisi Inventaris & Pemeriksaan --}}
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap: var(--space-7);">
            <div class="card" style="padding: 0; overflow: hidden;">
                <div style="padding: var(--space-8) var(--space-9); border-bottom: 1px solid var(--border-color);">
                    <h3 style="font-size: var(--text-md); font-weight: var(--font-bold); margin: 0; color: var(--text-color); display: flex; align-items: center; gap: var(--space-3);">
                        <i class="fas fa-vials text-primary"></i> Kondisi Inventaris Saat Ini
                    </h3>
                </div>
                <div style="overflow-x: auto;">
                    <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                        <thead>
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <th style="padding: var(--space-5) var(--space-7); font-size: var(--text-xs); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; text-align: left;">Kondisi</th>
                                <th style="padding: var(--space-5) var(--space-7); font-size: var(--text-xs); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; text-align: center;">Item</th>
                                <th style="padding: var(--space-5) var(--space-7); font-size: var(--text-xs); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; text-align: right;">Stok</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rekapKondisi as $rk)
                                <tr style="border-bottom: 1px solid var(--border-color);">
                                    <td style="padding: var(--space-5) var(--space-7); font-size: var(--text-sm); font-weight: var(--font-semibold);">{{ $rk->label }}</td>
                                    <td style="padding: var(--space-5) var(--space-7); text-align: center; font-weight: var(--font-bold);">{{ $rk->total }}</td>
                                    <td style="padding: var(--space-5) var(--space-7); text-align: right; font-size: var(--text-sm);">{{ (float) $rk->stok }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" style="padding: var(--space-9); text-align: center; color: var(--text-muted); font-size: var(--text-sm);">Belum ada inventaris.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card" style="padding: var(--space-8) var(--space-9);">
                <h3 style="font-size: var(--text-md); font-weight: var(--font-bold); margin: 0 0 var(--space-7) 0; color: var(--text-color); display: flex; align-items: center; gap: var(--space-3);">
                    <i class="fas fa-list-check text-primary"></i> Hasil Pemeriksaan Ceklist
                </h3>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-5);">
                    <div style="padding: var(--space-5); border-radius: var(--radius-md); background: rgba(59,130,246,0.06); border: 1px solid rgba(59,130,246,0.2);">
                        <div style="font-size: var(--text-lg); font-weight: var(--font-extrabold); color: var(--primary, #3b82f6);">{{ $totalPemeriksaan }}</div>
                        <div style="font-size: var(--text-2xs); color: var(--text-muted);">Pemeriksaan</div>
                    </div>
                    <div style="padding: var(--space-5); border-radius: var(--radius-md); background: rgba(59,130,246,0.06); border: 1px solid rgba(59,130,246,0.2);">
                        <div style="font-size: var(--text-lg); font-weight: var(--font-extrabold); color: var(--text-color);">{{ $totalItemDiperiksa }}</div>
                        <div style="font-size: var(--text-2xs); color: var(--text-muted);">Item Diperiksa</div>
                    </div>
                    <div style="padding: var(--space-5); border-radius: var(--radius-md); background: rgba(245,158,11,0.06); border: 1px solid rgba(245,158,11,0.2);">
                        <div style="font-size: var(--text-lg); font-weight: var(--font-extrabold); color: var(--warning, #f59e0b);">{{ $totalItemTidakSesuai }}</div>
                        <div style="font-size: var(--text-2xs); color: var(--text-muted);">Tidak Sesuai</div>
                    </div>
                    <div style="padding: var(--space-5); border-radius: var(--radius-md); background: rgba(239,68,68,0.06); border: 1px solid rgba(239,68,68,0.2);">
                        <div style="font-size: var(--text-lg); font-weight: var(--font-extrabold); color: var(--danger, #ef4444);">{{ $totalItemTidakAda }}</div>
                        <div style="font-size: var(--text-2xs); color: var(--text-muted);">Tidak Ada</div>
                    </div>
                </div>

                <div style="margin-top: var(--space-7); padding-top: var(--space-6); border-top: 1px solid var(--border-color); display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-5);">
                    <div>
                        <div style="font-size: var(--text-2xs); text-transform: uppercase; font-weight: var(--font-bold); color: var(--text-muted); margin-bottom: var(--space-1);">Kebutuhan Tercatat</div>
                        <div style="font-size: var(--text-lg); font-weight: var(--font-extrabold); color: var(--text-color);">{{ $totalKebutuhan }}</div>
                        <div style="font-size: var(--text-2xs); color: var(--danger, #ef4444);">{{ $totalKebutuhanKritis }} kritis</div>
                    </div>
                    <div>
                        <div style="font-size: var(--text-2xs); text-transform: uppercase; font-weight: var(--font-bold); color: var(--text-muted); margin-bottom: var(--space-1);">Usulan Pengadaan</div>
                        <div style="font-size: var(--text-lg); font-weight: var(--font-extrabold); color: var(--text-color);">{{ $totalPengadaan }}</div>
                        <div style="font-size: var(--text-2xs); color: var(--success, #10b981);">{{ $totalPengadaanDisetujui }} disetujui</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
