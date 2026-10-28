@extends('layouts.dashboard')

@section('title', 'Dashboard Laboratorium — SAE')
@section('dash_title', 'Dashboard Laboratorium')

@section('content')
    @include('dashboard.laboran.partials.header-actions')
    <div class="dash-content-inner">
        {{-- 1. Banner Header --}}
        <div class="dash-banner"
            style="display: flex; align-items: center; justify-content: space-between; gap: var(--space-8); flex-wrap: wrap; margin-bottom: var(--space-9);">
            <div style="display: flex; align-items: center; gap: var(--space-7);">
                <div
                    style="width: 48px; height: 48px; border-radius: var(--radius-lg); background: rgba(59,130,246,0.12); color: var(--primary, #3b82f6); display: flex; align-items: center; justify-content: center; font-size: var(--text-2xl); flex-shrink: 0;">
                    <i class="fas fa-flask"></i>
                </div>
                <div>
                    <h2 style="font-size: var(--text-xl); font-weight: var(--font-extrabold); color: var(--text-color); margin: 0;">
                        Dashboard Laboratorium
                    </h2>
                    <div style="font-size: var(--text-sm); color: var(--text-muted); margin-top: var(--space-1);">
                        @if ($jurusan)
                            <i class="fas fa-graduation-cap me-1"></i>
                            {{ $jurusan->nama_jurusan }}{{ !empty($jurusan->singkatan) ? ' (' . $jurusan->singkatan . ')' : '' }}
                        @else
                            Tata kelola alat &amp; bahan praktik laboratorium / bengkel / lahan.
                        @endif
                    </div>
                </div>
            </div>
            <div class="dash-banner-actions dash-banner-access">
                <a href="{{ route('dashboard.laboran.dashboard.cetak') }}" class="btn btn-outline"
                    title="Cetak Rekapitulasi Supervisi Laboratorium (A4)"
                    aria-label="Cetak Rekapitulasi Supervisi Laboratorium">
                    <i class="fas fa-print"></i>
                </a>
                <a href="{{ route('dashboard.laboran.laporan-lengkap.index') }}" class="btn btn-primary"
                    title="Laporan Lengkap Penggunaan &amp; Kerusakan Alat"
                    aria-label="Laporan Lengkap Penggunaan dan Kerusakan Alat">
                    <i class="fas fa-file-lines"></i>
                </a>
            </div>
        </div>

        {{-- Peringatan: penugasan jurusan belum ditetapkan (scoping belum aktif) --}}
        @if (!empty($scopeContext['is_laboran']) && !empty($scopeContext['unrestricted']))
            <div class="dash-alert"
                style="display: flex; gap: var(--space-5); align-items: flex-start; background: rgba(245,158,11,0.10); border: 1px solid rgba(245,158,11,0.35); border-left: 4px solid var(--warning, #f59e0b); border-radius: var(--radius-md); padding: var(--space-6) var(--space-7); margin-bottom: var(--space-8);">
                <i class="fas fa-triangle-exclamation"
                    style="color: var(--warning, #f59e0b); font-size: var(--text-lg); margin-top: 2px; flex-shrink: 0;"></i>
                <div style="font-size: var(--text-sm); color: var(--text-color); line-height: 1.6;">
                    <strong>Penugasan jurusan belum ditetapkan.</strong>
                    Saat ini Anda dapat melihat &amp; mengelola data <em>semua jurusan</em>. Agar pembatasan data
                    per jurusan aktif, minta Administrator menetapkan jurusan pada penugasan Laboran Anda melalui
                    <strong>Manajemen Pengguna &rarr; tab Tugas Tambahan &rarr; ikon pensil</strong>.
                </div>
            </div>
        @endif

        {{-- 2. Statistik Utama (4 Kartu) --}}
        <div class="dash-stat-grid"
            style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); margin-bottom: var(--space-9); gap: var(--space-7);">
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(59, 130, 246, 0.15); color: var(--primary, #3b82f6);">
                    <i class="fas fa-vials"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ number_format($totalItem, 0, ',', '.') }}</div>
                    <div class="dash-stat-label">Total Item Inventaris</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(16, 185, 129, 0.15); color: var(--success, #10b981);">
                    <i class="fas fa-circle-check"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ $persenLaik }}%</div>
                    <div class="dash-stat-label">Laik Pakai ({{ $kondisiBaik }} item)</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(245, 158, 11, 0.15); color: var(--warning, #f59e0b);">
                    <i class="fas fa-screwdriver-wrench"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ number_format($rawatTotal, 0, ',', '.') }}</div>
                    <div class="dash-stat-label">Catatan Perawatan</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(239, 68, 68, 0.15); color: var(--danger, #ef4444);">
                    <i class="fas fa-triangle-exclamation"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ number_format($rusakTotal, 0, ',', '.') }}</div>
                    <div class="dash-stat-label">Berita Acara Kerusakan</div>
                </div>
            </div>
        </div>

        {{-- 3. Peringatan Operasional (bila ada) --}}
        @php
            $adaPeringatan = $stokMenipis->count() > 0 || $pinjamTerlambat > 0 || $kartuJatuhTempo->count() > 0 || $kebutuhanKritis > 0;
        @endphp
        @if ($adaPeringatan)
            <div class="card"
                style="padding: var(--space-8) var(--space-9); margin-bottom: var(--space-9); border-left: 4px solid var(--warning, #f59e0b);">
                <h3 style="font-size: var(--text-md); font-weight: var(--font-bold); margin: 0 0 var(--space-6) 0; color: var(--text-color); display: flex; align-items: center; gap: var(--space-3);">
                    <i class="fas fa-bell text-warning" style="color: var(--warning, #f59e0b);"></i> Perlu Perhatian
                </h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: var(--space-5);">
                    @if ($stokMenipis->count() > 0)
                        <a href="{{ route('dashboard.laboran.inventaris.index') }}"
                            style="display: flex; align-items: center; gap: var(--space-4); padding: var(--space-5) var(--space-6); border-radius: var(--radius-md); background: rgba(245,158,11,0.08); border: 1px solid rgba(245,158,11,0.25); text-decoration: none; color: var(--text-color);">
                            <i class="fas fa-box-open" style="color: var(--warning, #f59e0b); font-size: var(--text-lg);"></i>
                            <div>
                                <div style="font-weight: var(--font-bold); font-size: var(--text-base);">{{ $stokMenipis->count() }} item stok menipis</div>
                                <div style="font-size: var(--text-xs); color: var(--text-muted);">Perlu pengadaan ulang</div>
                            </div>
                        </a>
                    @endif
                    @if ($pinjamTerlambat > 0)
                        <a href="{{ route('dashboard.laboran.peminjaman-alat.index', ['overdue' => 1]) }}"
                            style="display: flex; align-items: center; gap: var(--space-4); padding: var(--space-5) var(--space-6); border-radius: var(--radius-md); background: rgba(239,68,68,0.08); border: 1px solid rgba(239,68,68,0.25); text-decoration: none; color: var(--text-color);">
                            <i class="fas fa-clock" style="color: var(--danger, #ef4444); font-size: var(--text-lg);"></i>
                            <div>
                                <div style="font-weight: var(--font-bold); font-size: var(--text-base);">{{ $pinjamTerlambat }} peminjaman terlambat</div>
                                <div style="font-size: var(--text-xs); color: var(--text-muted);">Belum dikembalikan</div>
                            </div>
                        </a>
                    @endif
                    @if ($kartuJatuhTempo->count() > 0)
                        <a href="{{ route('dashboard.laboran.perawatan.index', ['tab' => 'kartu']) }}"
                            style="display: flex; align-items: center; gap: var(--space-4); padding: var(--space-5) var(--space-6); border-radius: var(--radius-md); background: rgba(139,92,246,0.08); border: 1px solid rgba(139,92,246,0.25); text-decoration: none; color: var(--text-color);">
                            <i class="fas fa-calendar-check" style="color: var(--purple, #8b5cf6); font-size: var(--text-lg);"></i>
                            <div>
                                <div style="font-weight: var(--font-bold); font-size: var(--text-base);">{{ $kartuJatuhTempo->count() }} kartu jatuh tempo</div>
                                <div style="font-size: var(--text-xs); color: var(--text-muted);">Perawatan ≤ 7 hari</div>
                            </div>
                        </a>
                    @endif
                    @if ($kebutuhanKritis > 0)
                        <a href="{{ route('dashboard.laboran.kebutuhan.index', ['prioritas' => 'kritis']) }}"
                            style="display: flex; align-items: center; gap: var(--space-4); padding: var(--space-5) var(--space-6); border-radius: var(--radius-md); background: rgba(239,68,68,0.08); border: 1px solid rgba(239,68,68,0.25); text-decoration: none; color: var(--text-color);">
                            <i class="fas fa-fire" style="color: var(--danger, #ef4444); font-size: var(--text-lg);"></i>
                            <div>
                                <div style="font-weight: var(--font-bold); font-size: var(--text-base);">{{ $kebutuhanKritis }} kebutuhan kritis</div>
                                <div style="font-size: var(--text-xs); color: var(--text-muted);">Prioritas penanganan</div>
                            </div>
                        </a>
                    @endif
                </div>
            </div>
        @endif

        {{-- 4. Akses Cepat Modul --}}
        <div class="card" style="padding: var(--space-9); margin-bottom: var(--space-9);">
            <h3 style="font-size: var(--text-md); font-weight: var(--font-bold); margin: 0 0 var(--space-7) 0; color: var(--text-color); display: flex; align-items: center; gap: var(--space-3);">
                <i class="fas fa-grip text-primary"></i> Modul Laboratorium
            </h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: var(--space-5);">
                @foreach ([
                        ['route' => 'dashboard.laboran.kebutuhan.index', 'icon' => 'fa-clipboard-list', 'label' => 'Analisis Kebutuhan', 'desc' => 'Tupoksi #6', 'color' => 'var(--accent, #06b6d4)'],
                        ['route' => 'dashboard.laboran.pengadaan.index', 'icon' => 'fa-file-invoice-dollar', 'label' => 'Usulan Pengadaan', 'desc' => 'Tupoksi #6', 'color' => 'var(--purple, #8b5cf6)'],
                        ['route' => 'dashboard.laboran.jadwal.index', 'icon' => 'fa-calendar-days', 'label' => 'Jadwal Praktik', 'desc' => 'Tupoksi #1', 'color' => 'var(--primary, #3b82f6)'],
                        ['route' => 'dashboard.laboran.inventaris.index', 'icon' => 'fa-vials', 'label' => 'Inventaris Alat & Bahan', 'desc' => 'Tupoksi #4', 'color' => 'var(--primary, #3b82f6)'],
                        ['route' => 'dashboard.laboran.perawatan.index', 'icon' => 'fa-screwdriver-wrench', 'label' => 'Perawatan & K3', 'desc' => 'Tupoksi #3', 'color' => 'var(--warning, #f59e0b)'],
                        ['route' => 'dashboard.laboran.pemeriksaan.index', 'icon' => 'fa-list-check', 'label' => 'Pemeriksaan Ceklist', 'desc' => 'Tupoksi #5', 'color' => 'var(--success, #10b981)'],
                        ['route' => 'dashboard.laboran.kebersihan.index', 'icon' => 'fa-broom', 'label' => 'Kebersihan Jurusan', 'desc' => 'Tupoksi #9', 'color' => 'var(--success, #10b981)'],
                        ['route' => 'dashboard.laboran.kerusakan.index', 'icon' => 'fa-triangle-exclamation', 'label' => 'BA Kerusakan', 'desc' => 'Tupoksi #7', 'color' => 'var(--danger, #ef4444)'],
                        ['route' => 'dashboard.laboran.peminjaman-alat.index', 'icon' => 'fa-hand-holding', 'label' => 'Peminjaman Alat', 'desc' => 'Tupoksi #7', 'color' => 'var(--info, #0ea5e9)'],
                        ['route' => 'dashboard.laboran.jurnal.index', 'icon' => 'fa-book', 'label' => 'Jurnal Kegiatan', 'desc' => 'Tupoksi #8', 'color' => 'var(--success, #10b981)'],
                        ['route' => 'dashboard.laboran.laporan-lengkap.index', 'icon' => 'fa-file-lines', 'label' => 'Laporan Lengkap', 'desc' => 'Tupoksi #8', 'color' => 'var(--primary, #3b82f6)'],
                        ['route' => 'dashboard.laboran.arsip.index', 'icon' => 'fa-box-archive', 'label' => 'Arsip Dokumen', 'desc' => 'Tupoksi #7', 'color' => 'var(--purple, #8b5cf6)'],
                    ] as $mod)
                        <a href="{{ route($mod['route']) }}"
                            style="display: flex; align-items: flex-start; gap: var(--space-5); padding: var(--space-6); border-radius: var(--radius-lg); background: var(--bg-hover); border: 1px solid var(--border-color); text-decoration: none; color: var(--text-color); transition: var(--transition-base);"
                            title="{{ $mod['label'] }} — {{ $mod['desc'] }}">
                            <span style="width: 38px; height: 38px; flex-shrink: 0; border-radius: var(--radius-md); background: rgba(59,130,246,0.12); color: {{ $mod['color'] }}; display: inline-flex; align-items: center; justify-content: center; font-size: var(--text-md);">
                                <i class="fas {{ $mod['icon'] }}"></i>
                            </span>
                            <span style="min-width: 0;">
                                <span style="display: block; font-weight: var(--font-bold); font-size: var(--text-base);">{{ $mod['label'] }}</span>
                                <span style="display: block; font-size: var(--text-xs); color: var(--text-muted); margin-top: var(--space-1);">{{ $mod['desc'] }}</span>
                            </span>
                        </a>
                    @endforeach
            </div>
        </div>

        {{-- 5. Tren 6 Bulan + Sebaran Lokasi --}}
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: var(--space-7); margin-bottom: var(--space-9);">
            <div class="card" style="padding: var(--space-9);">
                <h3 style="font-size: var(--text-md); font-weight: var(--font-bold); margin: 0 0 var(--space-2) 0; color: var(--text-color); display: flex; align-items: center; gap: var(--space-3);">
                    <i class="fas fa-chart-column text-primary"></i> Tren 6 Bulan Terakhir
                </h3>
                <p style="font-size: var(--text-xs); color: var(--text-muted); margin: 0 0 var(--space-7) 0;">
                    Perbandingan sesi praktik dengan kejadian kerusakan alat.
                </p>
                <div style="display: flex; align-items: flex-end; justify-content: space-between; gap: var(--space-4); height: 160px;">
                    @foreach ($trenBulanan as $t)
                        @php
                            $hPraktik = $maxTren > 0 ? max(2, round(($t['praktik'] / $maxTren) * 120)) : 2;
                            $maxRusak = max(1, max(array_column($trenBulanan, 'rusak') ?: [1]));
                            $hRusak = $t['rusak'] > 0 ? max(2, round(($t['rusak'] / $maxRusak) * 120)) : 0;
                        @endphp
                        <div style="flex: 1; display: flex; flex-direction: column; align-items: center; gap: var(--space-2); height: 100%; justify-content: flex-end;">
                            <div style="display: flex; align-items: flex-end; gap: 2px; height: 130px;">
                                <div style="width: 12px; height: {{ $hPraktik }}px; background: var(--primary, #3b82f6); border-radius: var(--radius-xs) var(--radius-xs) 0 0;"
                                    title="{{ $t['praktik'] }} sesi praktik"></div>
                                <div style="width: 12px; height: {{ $hRusak }}px; background: var(--danger, #ef4444); border-radius: var(--radius-xs) var(--radius-xs) 0 0;"
                                    title="{{ $t['rusak'] }} kerusakan"></div>
                            </div>
                            <span style="font-size: var(--text-2xs); color: var(--text-muted); font-weight: var(--font-semibold);">{{ $t['label'] }}</span>
                        </div>
                    @endforeach
                </div>
                <div style="display: flex; gap: var(--space-6); margin-top: var(--space-6); font-size: var(--text-xs); flex-wrap: wrap;">
                    <span style="display: inline-flex; align-items: center; gap: var(--space-2);">
                        <span style="width: 10px; height: 10px; background: var(--primary, #3b82f6); border-radius: var(--radius-xs);"></span>
                        Sesi Praktik
                    </span>
                    <span style="display: inline-flex; align-items: center; gap: var(--space-2);">
                        <span style="width: 10px; height: 10px; background: var(--danger, #ef4444); border-radius: var(--radius-xs);"></span>
                        Kerusakan
                    </span>
                </div>
            </div>

            <div class="card" style="padding: var(--space-9);">
                <h3 style="font-size: var(--text-md); font-weight: var(--font-bold); margin: 0 0 var(--space-2) 0; color: var(--text-color); display: flex; align-items: center; gap: var(--space-3);">
                    <i class="fas fa-map-location-dot text-primary"></i> Sebaran Inventaris per Lokasi
                </h3>
                <p style="font-size: var(--text-xs); color: var(--text-muted); margin: 0 0 var(--space-7) 0;">
                    Konsentrasi alat &amp; bahan pada setiap ruang praktik.
                </p>
                @php $maxLokasi = max(1, (int) ($sebaranLokasi->max('total') ?: 1)); @endphp
                @forelse ($sebaranLokasi as $lok)
                    <div style="display: flex; align-items: center; gap: var(--space-4); margin-bottom: var(--space-5);">
                        <span style="width: 130px; flex-shrink: 0; font-size: var(--text-xs); color: var(--text-muted); font-weight: var(--font-semibold); text-align: right; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"
                            title="{{ $lok->ruang_lab_nama }}">{{ $lok->ruang_lab_nama }}</span>
                        <div style="flex: 1; background: var(--bg-hover); border-radius: var(--radius-xs); height: 12px; overflow: hidden;">
                            <div style="width: {{ round(($lok->total / $maxLokasi) * 100) }}%; height: 100%; background: var(--primary, #3b82f6); border-radius: var(--radius-xs);"></div>
                        </div>
                        <span style="width: 30px; text-align: right; font-size: var(--text-xs); font-weight: var(--font-bold); color: var(--text-color);">{{ $lok->total }}</span>
                    </div>
                @empty
                    <p style="font-size: var(--text-sm); color: var(--text-muted); margin: 0;">Belum ada data lokasi inventaris.</p>
                @endforelse
            </div>
        </div>

        {{-- 6. Ringkasan Modul (grid 4 kolom) --}}
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: var(--space-7); margin-bottom: var(--space-9);">
            {{-- Praktik --}}
            <div class="card" style="padding: var(--space-8);">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: var(--space-6);">
                    <h4 style="font-size: var(--text-base); font-weight: var(--font-bold); margin: 0; color: var(--text-color);">
                        <i class="fas fa-calendar-days me-1 text-primary"></i> Praktik
                    </h4>
                    <a href="{{ route('dashboard.laboran.jadwal.index') }}" class="btn-icon" title="Buka Jadwal Praktik">
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-4);">
                    <div><div style="font-size: var(--text-lg); font-weight: var(--font-extrabold); color: var(--text-color);">{{ $jadwalHariIni }}</div><div style="font-size: var(--text-2xs); color: var(--text-muted);">Hari Ini</div></div>
                    <div><div style="font-size: var(--text-lg); font-weight: var(--font-extrabold); color: var(--text-color);">{{ $jadwalMinggu }}</div><div style="font-size: var(--text-2xs); color: var(--text-muted);">Minggu Ini</div></div>
                    <div><div style="font-size: var(--text-lg); font-weight: var(--font-extrabold); color: var(--text-color);">{{ $jadwalBulan }}</div><div style="font-size: var(--text-2xs); color: var(--text-muted);">Bulan Ini</div></div>
                    <div><div style="font-size: var(--text-lg); font-weight: var(--font-extrabold); color: var(--text-color);">{{ $jadwalTotal }}</div><div style="font-size: var(--text-2xs); color: var(--text-muted);">Total</div></div>
                </div>
            </div>

            {{-- Peminjaman --}}
            <div class="card" style="padding: var(--space-8);">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: var(--space-6);">
                    <h4 style="font-size: var(--text-base); font-weight: var(--font-bold); margin: 0; color: var(--text-color);">
                        <i class="fas fa-hand-holding me-1" style="color: var(--info, #0ea5e9);"></i> Peminjaman
                    </h4>
                    <a href="{{ route('dashboard.laboran.peminjaman-alat.index') }}" class="btn-icon" title="Buka Peminjaman Alat">
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-4);">
                    <div><div style="font-size: var(--text-lg); font-weight: var(--font-extrabold); color: var(--warning, #f59e0b);">{{ $pinjamMenunggu }}</div><div style="font-size: var(--text-2xs); color: var(--text-muted);">Menunggu</div></div>
                    <div><div style="font-size: var(--text-lg); font-weight: var(--font-extrabold); color: var(--primary, #3b82f6);">{{ $pinjamAktif }}</div><div style="font-size: var(--text-2xs); color: var(--text-muted);">Dipinjam</div></div>
                    <div><div style="font-size: var(--text-lg); font-weight: var(--font-extrabold); color: var(--danger, #ef4444);">{{ $pinjamTerlambat }}</div><div style="font-size: var(--text-2xs); color: var(--text-muted);">Terlambat</div></div>
                    <div><div style="font-size: var(--text-lg); font-weight: var(--font-extrabold); color: var(--text-color);">{{ $pinjamTotal }}</div><div style="font-size: var(--text-2xs); color: var(--text-muted);">Total</div></div>
                </div>
            </div>

            {{-- Perawatan --}}
            <div class="card" style="padding: var(--space-8);">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: var(--space-6);">
                    <h4 style="font-size: var(--text-base); font-weight: var(--font-bold); margin: 0; color: var(--text-color);">
                        <i class="fas fa-screwdriver-wrench me-1" style="color: var(--warning, #f59e0b);"></i> Perawatan
                    </h4>
                    <a href="{{ route('dashboard.laboran.perawatan.index') }}" class="btn-icon" title="Buka Perawatan">
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-4);">
                    <div><div style="font-size: var(--text-lg); font-weight: var(--font-extrabold); color: var(--text-color);">{{ $rawatDijadwalkan }}</div><div style="font-size: var(--text-2xs); color: var(--text-muted);">Dijadwalkan</div></div>
                    <div><div style="font-size: var(--text-lg); font-weight: var(--font-extrabold); color: var(--warning, #f59e0b);">{{ $rawatProses }}</div><div style="font-size: var(--text-2xs); color: var(--text-muted);">Proses</div></div>
                    <div><div style="font-size: var(--text-lg); font-weight: var(--font-extrabold); color: var(--success, #10b981);">{{ $rawatSelesai }}</div><div style="font-size: var(--text-2xs); color: var(--text-muted);">Selesai</div></div>
                    <div><div style="font-size: var(--text-lg); font-weight: var(--font-extrabold); color: var(--purple, #8b5cf6);">{{ $kartuAktif }}</div><div style="font-size: var(--text-2xs); color: var(--text-muted);">Kartu Aktif</div></div>
                </div>
            </div>

            {{-- Pengadaan --}}
            <div class="card" style="padding: var(--space-8);">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: var(--space-6);">
                    <h4 style="font-size: var(--text-base); font-weight: var(--font-bold); margin: 0; color: var(--text-color);">
                        <i class="fas fa-file-invoice-dollar me-1" style="color: var(--purple, #8b5cf6);"></i> Pengadaan
                    </h4>
                    <a href="{{ route('dashboard.laboran.pengadaan.index') }}" class="btn-icon" title="Buka Usulan Pengadaan">
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-4);">
                    <div><div style="font-size: var(--text-lg); font-weight: var(--font-extrabold); color: var(--text-color);">{{ $pengadaanTotal }}</div><div style="font-size: var(--text-2xs); color: var(--text-muted);">Total Usulan</div></div>
                    <div><div style="font-size: var(--text-lg); font-weight: var(--font-extrabold); color: var(--warning, #f59e0b);">{{ $pengadaanDiajukan }}</div><div style="font-size: var(--text-2xs); color: var(--text-muted);">Diproses</div></div>
                    <div><div style="font-size: var(--text-lg); font-weight: var(--font-extrabold); color: var(--success, #10b981);">{{ $pengadaanDisetujui }}</div><div style="font-size: var(--text-2xs); color: var(--text-muted);">Disetujui</div></div>
                    <div><div style="font-size: var(--text-sm); font-weight: var(--font-extrabold); color: var(--text-color);">Rp {{ number_format($pengadaanNilai, 0, ',', '.') }}</div><div style="font-size: var(--text-2xs); color: var(--text-muted);">Estimasi</div></div>
                </div>
            </div>
        </div>

        {{-- 7. Dua Kolom: Jadwal Terdekat & Kerusakan Terbaru --}}
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap: var(--space-7); margin-bottom: var(--space-9);">
            <div class="card" style="padding: 0; overflow: hidden;">
                <div style="padding: var(--space-8) var(--space-9); border-bottom: 1px solid var(--border-color);">
                    <h4 style="font-size: var(--text-base); font-weight: var(--font-bold); margin: 0; color: var(--text-color);">
                        <i class="fas fa-clock me-1 text-primary"></i> Jadwal Praktik Terdekat
                    </h4>
                </div>
                <div style="overflow-x: auto;">
                    <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                        <thead>
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <th style="padding: var(--space-5) var(--space-7); font-size: var(--text-xs); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; text-align: left;">Tanggal</th>
                                <th style="padding: var(--space-5) var(--space-7); font-size: var(--text-xs); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; text-align: left;">Lokasi / Mapel</th>
                                <th style="padding: var(--space-5) var(--space-7); font-size: var(--text-xs); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; text-align: center;">Jam</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($jadwalTerdekat as $jt)
                                <tr style="border-bottom: 1px solid var(--border-color);">
                                    <td style="padding: var(--space-5) var(--space-7); font-size: var(--text-sm); white-space: nowrap;">
                                        {{ \Carbon\Carbon::parse($jt->tanggal)->translatedFormat('d M') }}
                                    </td>
                                    <td style="padding: var(--space-5) var(--space-7); font-size: var(--text-sm);">
                                        <div style="font-weight: var(--font-semibold);">{{ $jt->ruang_lab_nama }}</div>
                                        <div style="font-size: var(--text-xs); color: var(--text-muted);">{{ $jt->mata_pelajaran }}</div>
                                    </td>
                                    <td style="padding: var(--space-5) var(--space-7); font-size: var(--text-sm); text-align: center; font-family: var(--font-family-mono); white-space: nowrap;">
                                        {{ substr((string) $jt->jam_mulai, 0, 5) }}
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="3" style="padding: var(--space-9); text-align: center; color: var(--text-muted); font-size: var(--text-sm);">Belum ada jadwal praktik mendatang.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card" style="padding: 0; overflow: hidden;">
                <div style="padding: var(--space-8) var(--space-9); border-bottom: 1px solid var(--border-color);">
                    <h4 style="font-size: var(--text-base); font-weight: var(--font-bold); margin: 0; color: var(--text-color);">
                        <i class="fas fa-triangle-exclamation me-1" style="color: var(--danger, #ef4444);"></i> Kerusakan Terbaru
                    </h4>
                </div>
                <div style="overflow-x: auto;">
                    <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                        <thead>
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <th style="padding: var(--space-5) var(--space-7); font-size: var(--text-xs); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; text-align: left;">Tanggal</th>
                                <th style="padding: var(--space-5) var(--space-7); font-size: var(--text-xs); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; text-align: left;">Alat</th>
                                <th style="padding: var(--space-5) var(--space-7); font-size: var(--text-xs); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; text-align: center;">Tingkat</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rusakTerbaru as $rt)
                                <tr style="border-bottom: 1px solid var(--border-color);">
                                    <td style="padding: var(--space-5) var(--space-7); font-size: var(--text-sm); white-space: nowrap;">
                                        {{ \Carbon\Carbon::parse($rt->tanggal_kejadian)->translatedFormat('d M') }}
                                    </td>
                                    <td style="padding: var(--space-5) var(--space-7); font-size: var(--text-sm);">
                                        <div style="font-weight: var(--font-semibold);">{{ $rt->nama_alat }}</div>
                                        <div style="font-size: var(--text-xs); color: var(--text-muted);">{{ $rt->ruang_lab_nama ?: '—' }}</div>
                                    </td>
                                    <td style="padding: var(--space-5) var(--space-7); text-align: center;">
                                        @php $tk = $rt->tingkat_kerusakan; @endphp
                                        <span class="badge {{ in_array($tk, ['berat','total']) ? 'badge-danger' : ($tk === 'sedang' ? 'badge-warning' : 'badge-info') }}"
                                            style="font-size: var(--text-2xs);">
                                            {{ \App\Http\Controllers\Laboran\LaboranKerusakanController::TINGKAT[$tk] ?? $tk }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="3" style="padding: var(--space-9); text-align: center; color: var(--text-muted); font-size: var(--text-sm);">Tidak ada catatan kerusakan.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- 8. Kebutuhan Prioritas & Kartu Jatuh Tempo --}}
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap: var(--space-7);">
            <div class="card" style="padding: 0; overflow: hidden;">
                <div style="padding: var(--space-8) var(--space-9); border-bottom: 1px solid var(--border-color);">
                    <h4 style="font-size: var(--text-base); font-weight: var(--font-bold); margin: 0; color: var(--text-color);">
                        <i class="fas fa-fire me-1" style="color: var(--danger, #ef4444);"></i> Kebutuhan Prioritas Tinggi
                    </h4>
                </div>
                <div style="overflow-x: auto;">
                    <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                        <thead>
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <th style="padding: var(--space-5) var(--space-7); font-size: var(--text-xs); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; text-align: left;">Item</th>
                                <th style="padding: var(--space-5) var(--space-7); font-size: var(--text-xs); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; text-align: center;">Kurang</th>
                                <th style="padding: var(--space-5) var(--space-7); font-size: var(--text-xs); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; text-align: center;">Prioritas</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($kebutuhanPrioritas as $kp)
                                <tr style="border-bottom: 1px solid var(--border-color);">
                                    <td style="padding: var(--space-5) var(--space-7); font-size: var(--text-sm);">
                                        <div style="font-weight: var(--font-semibold);">{{ $kp->nama_item }}</div>
                                        <div style="font-size: var(--text-xs); color: var(--text-muted);">{{ $kp->satuan }}</div>
                                    </td>
                                    <td style="padding: var(--space-5) var(--space-7); text-align: center; font-weight: var(--font-bold); font-size: var(--text-sm);">{{ (float) $kp->selisih }}</td>
                                    <td style="padding: var(--space-5) var(--space-7); text-align: center;">
                                        <span class="badge {{ $kp->prioritas === 'kritis' ? 'badge-danger' : 'badge-warning' }}" style="font-size: var(--text-2xs);">
                                            {{ \App\Http\Controllers\Laboran\LaboranKebutuhanController::PRIORITAS[$kp->prioritas] ?? $kp->prioritas }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="3" style="padding: var(--space-9); text-align: center; color: var(--text-muted); font-size: var(--text-sm);">Tidak ada kebutuhan prioritas tinggi.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card" style="padding: 0; overflow: hidden;">
                <div style="padding: var(--space-8) var(--space-9); border-bottom: 1px solid var(--border-color);">
                    <h4 style="font-size: var(--text-base); font-weight: var(--font-bold); margin: 0; color: var(--text-color);">
                        <i class="fas fa-calendar-check me-1" style="color: var(--purple, #8b5cf6);"></i> Kartu Perawatan Jatuh Tempo
                    </h4>
                </div>
                <div style="overflow-x: auto;">
                    <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                        <thead>
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <th style="padding: var(--space-5) var(--space-7); font-size: var(--text-xs); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; text-align: left;">Alat</th>
                                <th style="padding: var(--space-5) var(--space-7); font-size: var(--text-xs); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; text-align: center;">Jadwal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($kartuJatuhTempo as $kj)
                                <tr style="border-bottom: 1px solid var(--border-color);">
                                    <td style="padding: var(--space-5) var(--space-7); font-size: var(--text-sm);">
                                        <div style="font-weight: var(--font-semibold);">{{ $kj->nama_alat }}</div>
                                        <div style="font-size: var(--text-xs); color: var(--text-muted);">{{ $kj->kode_kartu }}</div>
                                    </td>
                                    <td style="padding: var(--space-5) var(--space-7); text-align: center; font-size: var(--text-sm);">
                                        @php
                                            $tgl = \Carbon\Carbon::parse($kj->jadwal_berikutnya);
                                            $lewat = $tgl->isPast();
                                        @endphp
                                        <span style="font-weight: var(--font-bold); color: {{ $lewat ? 'var(--danger, #ef4444)' : 'var(--warning, #f59e0b)' }};">
                                            {{ $tgl->translatedFormat('d M Y') }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="2" style="padding: var(--space-9); text-align: center; color: var(--text-muted); font-size: var(--text-sm);">Tidak ada kartu perawatan jatuh tempo.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
