@extends('layouts.dashboard')

@section('title', 'Laporan Penggunaan Lokasi Praktik — Laboratorium SAE')
@section('dash_title', 'Laporan Praktik')

@section('content')
    @include('dashboard.laboran.partials.header-actions')
    <div class="dash-content-inner">
        {{-- 1. Header Banner & Actions --}}
        <div class="dash-banner"
            style="display: flex; align-items: center; justify-content: space-between; gap: var(--space-8); flex-wrap: wrap; margin-bottom: var(--space-9);">
            <div style="display: flex; align-items: center; gap: var(--space-7);">
                <div
                    style="width: 48px; height: 48px; border-radius: var(--radius-lg); background: rgba(16,185,129,0.12); color: var(--success, #10b981); display: flex; align-items: center; justify-content: center; font-size: var(--text-2xl); flex-shrink: 0;">
                    <i class="fas fa-file-lines"></i>
                </div>
                <div>
                    <h2 style="font-size: var(--text-xl); font-weight: var(--font-extrabold); color: var(--text-color); margin: 0;">
                        Laporan Penggunaan Lokasi Praktik
                    </h2>
                    <div style="font-size: var(--text-sm); color: var(--text-muted); margin-top: var(--space-1);">
                        Rekapitulasi sesi praktik pada laboratorium, bengkel, lahan/kebun, dan lapangan sekolah.
                    </div>
                </div>
            </div>
            <div class="dash-banner-actions dash-banner-access">
                <a href="{{ route('dashboard.laboran.jadwal.index') }}" class="btn btn-outline"
                    title="Kembali ke Jadwal Praktik" aria-label="Kembali ke Jadwal Praktik">
                    <i class="fas fa-calendar-days"></i>
                </a>
                <a href="{{ route('dashboard.laboran.laporan.cetak', request()->all()) }}" target="_blank" class="btn btn-primary"
                    title="Cetak Laporan Periode Ini" aria-label="Cetak Laporan Periode Ini">
                    <i class="fas fa-print"></i>
                </a>
            </div>
        </div>

        {{-- 2. Navigasi Periode Laporan (Bulan / Triwulan / Semester / Tahun) --}}
        <div class="periode-nav-wrapper" style="margin-bottom: var(--space-7);">
            <div class="periode-nav-desktop">
                <a href="{{ route('dashboard.laboran.laporan.index', ['periode' => 'bulan', 'bulan' => $bulan, 'tahun' => $tahun]) }}"
                    class="periode-nav-tab {{ $periodeTipe === 'bulan' ? 'active' : '' }}">
                    <i class="fas fa-calendar-day"></i> Bulanan
                </a>
                <a href="{{ route('dashboard.laboran.laporan.index', ['periode' => 'triwulan', 'triwulan' => $triwulan, 'tahun' => $tahun]) }}"
                    class="periode-nav-tab {{ $periodeTipe === 'triwulan' ? 'active' : '' }}">
                    <i class="fas fa-chart-pie"></i> Triwulan (3 Bulan)
                </a>
                <a href="{{ route('dashboard.laboran.laporan.index', ['periode' => 'semester', 'semester' => $semester, 'tahun_ajaran' => $tahunAjaran]) }}"
                    class="periode-nav-tab {{ $periodeTipe === 'semester' ? 'active' : '' }}">
                    <i class="fas fa-layer-group"></i> Semester (6 Bulan)
                </a>
                <a href="{{ route('dashboard.laboran.laporan.index', ['periode' => 'tahun', 'tahun_ajaran' => $tahunAjaran]) }}"
                    class="periode-nav-tab {{ $periodeTipe === 'tahun' ? 'active' : '' }}">
                    <i class="fas fa-calendar-check"></i> Tahun Ajaran
                </a>
            </div>

            <div class="periode-nav-mobile">
                <div class="periode-mobile-select-box" style="margin-bottom: 0;">
                    <label><i class="fas fa-sliders text-primary me-1"></i> Mode Rentang Periode Laporan:</label>
                    <select class="periode-mobile-select"
                        onchange="if(this.value) window.location.href=this.value;">
                        <option
                            value="{{ route('dashboard.laboran.laporan.index', ['periode' => 'bulan', 'bulan' => $bulan, 'tahun' => $tahun]) }}"
                            {{ $periodeTipe === 'bulan' ? 'selected' : '' }}>Bulanan</option>
                        <option
                            value="{{ route('dashboard.laboran.laporan.index', ['periode' => 'triwulan', 'triwulan' => $triwulan, 'tahun' => $tahun]) }}"
                            {{ $periodeTipe === 'triwulan' ? 'selected' : '' }}>Triwulan (3 Bulan)</option>
                        <option
                            value="{{ route('dashboard.laboran.laporan.index', ['periode' => 'semester', 'semester' => $semester, 'tahun_ajaran' => $tahunAjaran]) }}"
                            {{ $periodeTipe === 'semester' ? 'selected' : '' }}>Semester (6 Bulan)</option>
                        <option
                            value="{{ route('dashboard.laboran.laporan.index', ['periode' => 'tahun', 'tahun_ajaran' => $tahunAjaran]) }}"
                            {{ $periodeTipe === 'tahun' ? 'selected' : '' }}>Tahun Ajaran</option>
                    </select>
                </div>
            </div>
        </div>

        {{-- 3. Filter Periode Spesifik & Opsi Tambahan --}}
        <div class="filter-card-container" style="margin-bottom: var(--space-7);">
            <form action="{{ route('dashboard.laboran.laporan.index') }}" method="GET" class="filter-form-row">
                <input type="hidden" name="periode" value="{{ $periodeTipe }}">

                @if ($periodeTipe === 'bulan')
                    <div class="filter-item-group">
                        <label>Bulan:</label>
                        <select name="bulan" class="filter-select">
                            @for ($m = 1; $m <= 12; $m++)
                                <option value="{{ $m }}" {{ $bulan == $m ? 'selected' : '' }}>
                                    {{ \Carbon\Carbon::create(null, $m, 1)->translatedFormat('F') }}
                                </option>
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
                            <option value="1" {{ $triwulan == 1 ? 'selected' : '' }}>Triwulan I (Jan - Mar)</option>
                            <option value="2" {{ $triwulan == 2 ? 'selected' : '' }}>Triwulan II (Apr - Jun)</option>
                            <option value="3" {{ $triwulan == 3 ? 'selected' : '' }}>Triwulan III (Jul - Sep)</option>
                            <option value="4" {{ $triwulan == 4 ? 'selected' : '' }}>Triwulan IV (Okt - Des)</option>
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
                            <option value="1" {{ $semester === '1' ? 'selected' : '' }}>Ganjil (Jul - Des)</option>
                            <option value="2" {{ $semester === '2' ? 'selected' : '' }}>Genap (Jan - Jun)</option>
                        </select>
                    </div>
                    <div class="filter-item-group">
                        <label>Tahun Ajaran:</label>
                        <input type="text" name="tahun_ajaran" value="{{ $tahunAjaran }}" class="filter-select"
                            placeholder="2025/2026" style="width: 130px;">
                    </div>
                @else
                    <div class="filter-item-group">
                        <label>Tahun Ajaran:</label>
                        <input type="text" name="tahun_ajaran" value="{{ $tahunAjaran }}" class="filter-select"
                            placeholder="2025/2026" style="width: 150px;">
                    </div>
                @endif

                <div class="filter-item-group">
                    <label>Jenis Lokasi:</label>
                    <select name="jenis" class="filter-select">
                        <option value="">Semua Jenis</option>
                        @foreach ($jenisLokasiLabels as $jKey => $jLabel)
                            <option value="{{ $jKey }}" {{ $jenisFilter === $jKey ? 'selected' : '' }}>
                                {{ $jLabel }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="filter-item-group">
                    <label>Lokasi:</label>
                    <select name="lab" class="filter-select">
                        <option value="">Semua Lokasi</option>
                        @foreach ($daftarLokasi as $lok)
                            <option value="{{ $lok->ruang_lab_nama }}"
                                {{ $lokasiFilter === $lok->ruang_lab_nama ? 'selected' : '' }}>
                                {{ $lok->ruang_lab_nama }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="filter-item-group">
                    <label>Guru:</label>
                    <select name="ptk_id" class="filter-select">
                        <option value="">Semua Guru</option>
                        @foreach ($daftarGuru as $g)
                            <option value="{{ $g->ptk_id }}" {{ $guruFilter === $g->ptk_id ? 'selected' : '' }}>
                                {{ $g->nama }}</option>
                        @endforeach
                    </select>
                </div>

                <div style="display: flex; gap: var(--space-4);">
                    <button type="submit" class="btn btn-primary" title="Terapkan Filter Periode"
                        style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md);">
                        <i class="fas fa-filter"></i>
                    </button>
                    <a href="{{ route('dashboard.laboran.laporan.index', ['periode' => $periodeTipe]) }}"
                        class="btn btn-outline" title="Reset Filter"
                        style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md);">
                        <i class="fas fa-undo"></i>
                    </a>
                </div>

                <div style="margin-left: auto;">
                    <span class="badge"
                        style="background: rgba(59, 130, 246, 0.12); color: var(--primary, #3b82f6); border: 1px solid rgba(59, 130, 246, 0.25); padding: var(--space-4) var(--space-7); border-radius: var(--radius-md); font-size: var(--text-sm); font-weight: var(--font-bold); display: inline-flex; align-items: center; gap: var(--space-3);">
                        <i class="fas fa-calendar-check"></i> {{ $range['label'] }}
                    </span>
                </div>
            </form>
        </div>

        {{-- 4. Statistik Ringkasan (4 Kartu) --}}
        <div class="dash-stat-grid"
            style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); margin-bottom: var(--space-10); gap: var(--space-7);">
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(59, 130, 246, 0.15); color: var(--primary, #3b82f6);">
                    <i class="fas fa-layer-group"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ number_format($totalSesi, 0, ',', '.') }}</div>
                    <div class="dash-stat-label">Total Sesi Praktik</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(16, 185, 129, 0.15); color: var(--success, #10b981);">
                    <i class="fas fa-circle-check"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ number_format($totalSelesai, 0, ',', '.') }}</div>
                    <div class="dash-stat-label">Selesai ({{ $persentaseSelesai }}%)</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(245, 158, 11, 0.15); color: var(--warning, #f59e0b);">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ number_format($totalJam, 0, ',', '.') }} Jam</div>
                    <div class="dash-stat-label">Akumulasi Durasi ({{ $sisaMenit }} menit)</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(139, 92, 246, 0.15); color: var(--purple, #8b5cf6);">
                    <i class="fas fa-location-dot"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ number_format($lokasiUnik, 0, ',', '.') }}</div>
                    <div class="dash-stat-label">Lokasi Terpakai</div>
                </div>
            </div>
        </div>

        {{-- 5. Rekapitulasi per Jenis Lokasi --}}
        <div class="card" style="padding: 0; margin-bottom: var(--space-9); overflow: hidden;">
            <div style="padding: var(--space-8) var(--space-9); border-bottom: 1px solid var(--border-color);">
                <h3 style="font-size: var(--text-md); font-weight: var(--font-bold); margin: 0; color: var(--text-color); display: flex; align-items: center; gap: var(--space-4);">
                    <i class="fas fa-chart-pie text-primary"></i> Rekapitulasi per Jenis Lokasi
                </h3>
                <p style="margin: var(--space-1) 0 0 0; font-size: var(--text-sm); color: var(--text-muted);">
                    Sebaran pemanfaatan laboratorium, bengkel, lahan, dan lapangan pada periode terpilih.
                </p>
            </div>
            <div style="overflow-x: auto;">
                <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                    <thead>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <th style="padding: var(--space-6) var(--space-8); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; text-align: left;">Jenis Lokasi</th>
                            <th style="padding: var(--space-6) var(--space-8); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; text-align: center;">Jumlah Lokasi</th>
                            <th style="padding: var(--space-6) var(--space-8); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; text-align: center;">Sesi</th>
                            <th style="padding: var(--space-6) var(--space-8); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; text-align: center;">Selesai</th>
                            <th style="padding: var(--space-6) var(--space-8); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; text-align: right;">Durasi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rekapJenis as $rj)
                            @php
                                $rjIcon = match ($rj['key']) {
                                    'lab' => 'fa-flask',
                                    'bengkel' => 'fa-screwdriver-wrench',
                                    'lahan' => 'fa-seedling',
                                    'lapangan' => 'fa-futbol',
                                    default => 'fa-location-dot',
                                };
                            @endphp
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <td data-label="Jenis Lokasi" style="padding: var(--space-7) var(--space-8); font-weight: var(--font-semibold);">
                                    <i class="fas {{ $rjIcon }} me-1 text-primary"></i> {{ $rj['label'] }}
                                </td>
                                <td data-label="Jumlah Lokasi" style="padding: var(--space-7) var(--space-8); text-align: center;">{{ $rj['lokasi'] }}</td>
                                <td data-label="Sesi" style="padding: var(--space-7) var(--space-8); text-align: center; font-weight: var(--font-bold);">{{ $rj['sesi'] }}</td>
                                <td data-label="Selesai" style="padding: var(--space-7) var(--space-8); text-align: center; color: var(--success, #10b981); font-weight: var(--font-bold);">{{ $rj['selesai'] }}</td>
                                <td data-label="Durasi" style="padding: var(--space-7) var(--space-8); text-align: right; font-family: var(--font-family-mono);">{{ $rj['jam'] }}j {{ $rj['menit'] }}m</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" style="padding: var(--space-11); text-align: center; color: var(--text-muted);">
                                    <i class="fas fa-inbox mb-2" style="font-size: var(--text-3xl); opacity: 0.5;"></i>
                                    <div>Tidak ada data praktik pada periode ini.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- 6. Rekap Bulanan (hanya untuk periode > 1 bulan) --}}
        @if (count($rekapBulanan) > 1)
            <div class="card" style="padding: 0; margin-bottom: var(--space-9); overflow: hidden;">
                <div style="padding: var(--space-8) var(--space-9); border-bottom: 1px solid var(--border-color);">
                    <h3 style="font-size: var(--text-md); font-weight: var(--font-bold); margin: 0; color: var(--text-color); display: flex; align-items: center; gap: var(--space-4);">
                        <i class="fas fa-calendar-days text-primary"></i> Rekapitulasi Progres Bulanan
                    </h3>
                </div>
                <div style="overflow-x: auto;">
                    <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                        <thead>
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <th style="padding: var(--space-6) var(--space-8); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; text-align: left;">Bulan</th>
                                <th style="padding: var(--space-6) var(--space-8); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; text-align: center;">Hari Aktif</th>
                                <th style="padding: var(--space-6) var(--space-8); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; text-align: center;">Sesi</th>
                                <th style="padding: var(--space-6) var(--space-8); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; text-align: center;">Selesai</th>
                                <th style="padding: var(--space-6) var(--space-8); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; text-align: center;">Batal</th>
                                <th style="padding: var(--space-6) var(--space-8); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; text-align: right;">Durasi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rekapBulanan as $rb)
                                <tr style="border-bottom: 1px solid var(--border-color);">
                                    <td data-label="Bulan" style="padding: var(--space-7) var(--space-8); font-weight: var(--font-bold);">{{ $rb['label'] }}</td>
                                    <td data-label="Hari Aktif" style="padding: var(--space-7) var(--space-8); text-align: center;">{{ $rb['hari'] }} hari</td>
                                    <td data-label="Sesi" style="padding: var(--space-7) var(--space-8); text-align: center; font-weight: var(--font-bold);">{{ $rb['sesi'] }}</td>
                                    <td data-label="Selesai" style="padding: var(--space-7) var(--space-8); text-align: center; color: var(--success, #10b981); font-weight: var(--font-bold);">{{ $rb['selesai'] }}</td>
                                    <td data-label="Batal" style="padding: var(--space-7) var(--space-8); text-align: center; color: var(--danger, #ef4444); font-weight: var(--font-bold);">{{ $rb['batal'] }}</td>
                                    <td data-label="Durasi" style="padding: var(--space-7) var(--space-8); text-align: right; font-family: var(--font-family-mono);">{{ $rb['jam'] }}j {{ $rb['menit'] }}m</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- 7. Rekap per Lokasi --}}
        <div class="card" style="padding: 0; margin-bottom: var(--space-9); overflow: hidden;">
            <div style="padding: var(--space-8) var(--space-9); border-bottom: 1px solid var(--border-color);">
                <h3 style="font-size: var(--text-md); font-weight: var(--font-bold); margin: 0; color: var(--text-color); display: flex; align-items: center; gap: var(--space-4);">
                    <i class="fas fa-map-location-dot text-primary"></i> Rekapitulasi per Lokasi Praktik
                </h3>
            </div>
            <div style="overflow-x: auto;">
                <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                    <thead>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <th style="padding: var(--space-6) var(--space-8); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; text-align: left;">Lokasi</th>
                            <th style="padding: var(--space-6) var(--space-8); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; text-align: left;">Jenis</th>
                            <th style="padding: var(--space-6) var(--space-8); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; text-align: center;">Sesi</th>
                            <th style="padding: var(--space-6) var(--space-8); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; text-align: center;">Selesai</th>
                            <th style="padding: var(--space-6) var(--space-8); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; text-align: right;">Durasi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rekapLokasi as $rl)
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <td data-label="Lokasi" style="padding: var(--space-7) var(--space-8); font-weight: var(--font-semibold);">{{ $rl->nama }}</td>
                                <td data-label="Jenis" style="padding: var(--space-7) var(--space-8);">
                                    <span class="badge badge-outline" style="font-size: var(--text-xs);">{{ $rl->label }}</span>
                                </td>
                                <td data-label="Sesi" style="padding: var(--space-7) var(--space-8); text-align: center; font-weight: var(--font-bold);">{{ $rl->sesi }}</td>
                                <td data-label="Selesai" style="padding: var(--space-7) var(--space-8); text-align: center; color: var(--success, #10b981); font-weight: var(--font-bold);">{{ $rl->selesai }}</td>
                                <td data-label="Durasi" style="padding: var(--space-7) var(--space-8); text-align: right; font-family: var(--font-family-mono);">{{ $rl->jam }}j {{ $rl->menit }}m</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" style="padding: var(--space-11); text-align: center; color: var(--text-muted);">
                                    <i class="fas fa-inbox mb-2" style="font-size: var(--text-3xl); opacity: 0.5;"></i>
                                    <div>Tidak ada data lokasi pada periode ini.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- 8. Rekap per Guru & per Mata Pelajaran --}}
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap: var(--space-7); margin-bottom: var(--space-9);">
            <div class="card" style="padding: 0; overflow: hidden;">
                <div style="padding: var(--space-8) var(--space-9); border-bottom: 1px solid var(--border-color);">
                    <h3 style="font-size: var(--text-md); font-weight: var(--font-bold); margin: 0; color: var(--text-color); display: flex; align-items: center; gap: var(--space-4);">
                        <i class="fas fa-user-tie text-primary"></i> Rekap per Guru Pengampu
                    </h3>
                </div>
                <div style="overflow-x: auto;">
                    <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                        <thead>
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <th style="padding: var(--space-6) var(--space-8); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; text-align: left;">Guru</th>
                                <th style="padding: var(--space-6) var(--space-8); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; text-align: center;">Sesi</th>
                                <th style="padding: var(--space-6) var(--space-8); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; text-align: center;">Mapel</th>
                                <th style="padding: var(--space-6) var(--space-8); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; text-align: right;">Durasi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rekapGuru as $rg)
                                <tr style="border-bottom: 1px solid var(--border-color);">
                                    <td data-label="Guru" style="padding: var(--space-7) var(--space-8);">
                                        <div style="font-weight: var(--font-semibold);">{{ $rg->nama }}</div>
                                        <div style="font-size: var(--text-xs); color: var(--text-muted);">{{ $rg->nip ?: '—' }}</div>
                                    </td>
                                    <td data-label="Sesi" style="padding: var(--space-7) var(--space-8); text-align: center; font-weight: var(--font-bold);">{{ $rg->sesi }}</td>
                                    <td data-label="Mapel" style="padding: var(--space-7) var(--space-8); text-align: center;">{{ $rg->mapel }}</td>
                                    <td data-label="Durasi" style="padding: var(--space-7) var(--space-8); text-align: right; font-family: var(--font-family-mono);">{{ $rg->jam }}j {{ $rg->menit }}m</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" style="padding: var(--space-11); text-align: center; color: var(--text-muted);">
                                        <div>Belum ada data guru.</div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card" style="padding: 0; overflow: hidden;">
                <div style="padding: var(--space-8) var(--space-9); border-bottom: 1px solid var(--border-color);">
                    <h3 style="font-size: var(--text-md); font-weight: var(--font-bold); margin: 0; color: var(--text-color); display: flex; align-items: center; gap: var(--space-4);">
                        <i class="fas fa-book text-primary"></i> Rekap per Mata Pelajaran
                    </h3>
                </div>
                <div style="overflow-x: auto;">
                    <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                        <thead>
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <th style="padding: var(--space-6) var(--space-8); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; text-align: left;">Mata Pelajaran</th>
                                <th style="padding: var(--space-6) var(--space-8); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; text-align: center;">Sesi</th>
                                <th style="padding: var(--space-6) var(--space-8); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; text-align: center;">Kelas</th>
                                <th style="padding: var(--space-6) var(--space-8); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; text-align: right;">Durasi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rekapMapel as $rm)
                                <tr style="border-bottom: 1px solid var(--border-color);">
                                    <td data-label="Mata Pelajaran" style="padding: var(--space-7) var(--space-8); font-weight: var(--font-semibold);">{{ $rm->nama }}</td>
                                    <td data-label="Sesi" style="padding: var(--space-7) var(--space-8); text-align: center; font-weight: var(--font-bold);">{{ $rm->sesi }}</td>
                                    <td data-label="Kelas" style="padding: var(--space-7) var(--space-8); text-align: center;">{{ $rm->kelas }}</td>
                                    <td data-label="Durasi" style="padding: var(--space-7) var(--space-8); text-align: right; font-family: var(--font-family-mono);">{{ $rm->jam }}j {{ $rm->menit }}m</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" style="padding: var(--space-11); text-align: center; color: var(--text-muted);">
                                        <div>Belum ada data mata pelajaran.</div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- 9. Rincian Sesi Praktik --}}
        <div class="card" id="tableDataContainer" style="padding: 0; overflow: hidden;">
            <div style="padding: var(--space-8) var(--space-9); border-bottom: 1px solid var(--border-color); display: flex; align-items: center; justify-content: space-between; gap: var(--space-4); flex-wrap: wrap;">
                <div>
                    <h3 style="font-size: var(--text-md); font-weight: var(--font-bold); margin: 0; color: var(--text-color); display: flex; align-items: center; gap: var(--space-4);">
                        <i class="fas fa-list-check text-primary"></i> Rincian Sesi Praktik
                    </h3>
                    <p style="margin: var(--space-1) 0 0 0; font-size: var(--text-sm); color: var(--text-muted);">
                        Daftar seluruh sesi praktik pada {{ $range['label'] }}.
                    </p>
                </div>
                <span class="badge badge-outline" style="font-size: var(--text-xs);">{{ $totalSesi }} sesi</span>
            </div>
            <div class="table-responsive-stack" style="overflow-x: auto; width: 100%;">
                <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                    <thead>
                        <tr style="border-bottom: 1px solid var(--border-color); background: rgba(255, 255, 255, 0.02);">
                            <th style="padding: var(--space-6) var(--space-8); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase;">No</th>
                            <th style="padding: var(--space-6) var(--space-8); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase;">Tanggal</th>
                            <th style="padding: var(--space-6) var(--space-8); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase;">Lokasi</th>
                            <th style="padding: var(--space-6) var(--space-8); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase;">Kelas &amp; Mapel</th>
                            <th style="padding: var(--space-6) var(--space-8); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase;">Guru</th>
                            <th style="padding: var(--space-6) var(--space-8); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase;">Topik / Materi</th>
                            <th style="padding: var(--space-6) var(--space-8); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; text-align: center;">Jam</th>
                            <th style="padding: var(--space-6) var(--space-8); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; text-align: center;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $idx => $it)
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <td data-label="No" style="padding: var(--space-6) var(--space-8); color: var(--text-muted); font-size: var(--text-sm);">{{ $idx + 1 }}</td>
                                <td data-label="Tanggal" style="padding: var(--space-6) var(--space-8); font-size: var(--text-sm); white-space: nowrap;">
                                    {{ \Carbon\Carbon::parse($it->tanggal)->translatedFormat('d/m/Y') }}
                                </td>
                                <td data-label="Lokasi" style="padding: var(--space-6) var(--space-8); font-size: var(--text-sm);">
                                    <div style="font-weight: var(--font-semibold);">{{ $it->ruang_lab_nama }}</div>
                                    <div style="font-size: var(--text-xs); color: var(--text-muted);">{{ $jenisLokasiLabels[$it->jenis_lokasi] ?? 'Lokasi Lainnya' }}</div>
                                </td>
                                <td data-label="Kelas & Mapel" style="padding: var(--space-6) var(--space-8); font-size: var(--text-sm);">
                                    <div style="font-weight: var(--font-semibold);">{{ $it->rombel_nama ?: '—' }}</div>
                                    <div style="font-size: var(--text-xs); color: var(--text-muted);">{{ $it->mata_pelajaran }}</div>
                                </td>
                                <td data-label="Guru" style="padding: var(--space-6) var(--space-8); font-size: var(--text-sm);">{{ $it->guru_nama ?: '—' }}</td>
                                <td data-label="Topik" style="padding: var(--space-6) var(--space-8); font-size: var(--text-sm);">
                                    {{ $it->topik_praktik ?: '—' }}
                                </td>
                                <td data-label="Jam" style="padding: var(--space-6) var(--space-8); font-size: var(--text-sm); text-align: center; font-family: var(--font-family-mono); white-space: nowrap;">
                                    {{ substr((string) $it->jam_mulai, 0, 5) }} - {{ substr((string) $it->jam_selesai, 0, 5) }}
                                </td>
                                <td data-label="Status" style="padding: var(--space-6) var(--space-8); text-align: center;">
                                    @if ($it->status === 'dijadwalkan')
                                        <span class="badge badge-info">Dijadwalkan</span>
                                    @elseif ($it->status === 'berlangsung')
                                        <span class="badge badge-warning">Berlangsung</span>
                                    @elseif ($it->status === 'selesai')
                                        <span class="badge badge-success">Selesai</span>
                                    @else
                                        <span class="badge badge-danger">Batal</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" style="padding: var(--space-11); text-align: center; color: var(--text-muted);">
                                    <i class="fas fa-folder-open mb-2" style="font-size: var(--text-3xl); opacity: 0.5;"></i>
                                    <div>Belum ada sesi praktik pada periode ini.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
