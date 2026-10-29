{{-- Section Dashboard: Teknisi IT & Infrastruktur Jaringan --}}

<!-- 2. Quick Stats Grid Teknisi IT (Responsive) -->
<div class="dash-stat-grid" style="margin-bottom: 24px; gap: 14px;">
    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(59, 130, 246, 0.15); color: var(--primary, #3b82f6);">
            <i class="fas fa-screwdriver-wrench"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">{{ number_format($teknisiStats['wo_total'] ?? 0, 0, ',', '.') }} Tiket</div>
            <div class="dash-stat-label">Total Work Order</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(16, 185, 129, 0.15); color: var(--success, #10b981);">
            <i class="fas fa-circle-check"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">{{ number_format($teknisiStats['wo_selesai'] ?? 0, 0, ',', '.') }} Tiket</div>
            <div class="dash-stat-label">Perbaikan Selesai</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(245, 158, 11, 0.15); color: var(--warning, #f59e0b);">
            <i class="fas fa-list-check"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">{{ number_format(($teknisiStats['wo_antrean'] ?? 0) + ($teknisiStats['wo_proses'] ?? 0), 0, ',', '.') }} Tiket</div>
            <div class="dash-stat-label">Antrean &amp; Proses</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(139, 92, 246, 0.15); color: var(--purple, #8b5cf6);">
            <i class="fas fa-box-open"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">{{ number_format($teknisiStats['wo_menunggu_part'] ?? 0, 0, ',', '.') }} Tiket</div>
            <div class="dash-stat-label">Menunggu Sparepart</div>
        </div>
    </div>
</div>

<!-- Tab bidang tugas (desktop: tab, mobile: dropdown) -->
@include('dashboard.tendik.partials.domain-tabs')

<!-- 1. Akses Cepat Menu Teknisi IT (Responsive Grid Seimbang & Genap) -->
<div class="kepegawaian-quick-grid">
    <a href="{{ route('dashboard.teknisi.work-order') }}"
        class="kepegawaian-quick-btn" style="--quick-color: var(--primary, #3b82f6);" title="Work Order & Pelaporan Masalah">
        <div class="kepegawaian-quick-icon" style="background: rgba(59, 130, 246, 0.15); color: var(--primary, #3b82f6);">
            <i class="fas fa-wrench"></i>
        </div>
        <span class="kepegawaian-quick-label">Work Order</span>
    </a>

    <a href="{{ route('dashboard.teknisi.pemeliharaan') }}"
        class="kepegawaian-quick-btn" style="--quick-color: var(--success, #10b981);" title="Jadwal Pemeliharaan Berkala">
        <div class="kepegawaian-quick-icon" style="background: rgba(16, 185, 129, 0.15); color: var(--success, #10b981);">
            <i class="fas fa-calendar-check"></i>
        </div>
        <span class="kepegawaian-quick-label">Pemeliharaan</span>
    </a>

    <a href="{{ route('dashboard.sarpras.aset.index') }}"
        class="kepegawaian-quick-btn" style="--quick-color: var(--warning, #f59e0b);" title="Aset Perangkat IT">
        <div class="kepegawaian-quick-icon" style="background: rgba(245, 158, 11, 0.15); color: var(--warning, #f59e0b);">
            <i class="fas fa-network-wired"></i>
        </div>
        <span class="kepegawaian-quick-label">Aset IT</span>
    </a>

    <a href="{{ route('dashboard.tendik.target.index', ['bidang' => 'teknisi']) }}"
        class="kepegawaian-quick-btn" style="--quick-color: var(--primary-light, #6366f1);" title="Target &amp; Capaian Kinerja">
        <div class="kepegawaian-quick-icon" style="background: rgba(99, 102, 241, 0.15); color: var(--primary-light, #6366f1);">
            <i class="fas fa-bullseye"></i>
        </div>
        <span class="kepegawaian-quick-label">Target Capaian</span>
    </a>

    <a href="{{ route('dashboard.tendik.aktivitas.index', ['bidang' => 'teknisi']) }}"
        class="kepegawaian-quick-btn" style="--quick-color: var(--accent, #06b6d4);" title="Input Log Aktivitas Harian">
        <div class="kepegawaian-quick-icon" style="background: rgba(6, 182, 212, 0.15); color: var(--accent, #06b6d4);">
            <i class="fas fa-clipboard-check"></i>
        </div>
        <span class="kepegawaian-quick-label">Aktivitas Harian</span>
    </a>

    <a href="{{ route('dashboard.tendik.laporan.index', ['bidang' => 'teknisi']) }}"
        class="kepegawaian-quick-btn" style="--quick-color: var(--pink, #ec4899);" title="Laporan &amp; Rekapitulasi Kinerja">
        <div class="kepegawaian-quick-icon" style="background: rgba(236, 72, 153, 0.15); color: var(--pink, #ec4899);">
            <i class="fas fa-file-invoice"></i>
        </div>
        <span class="kepegawaian-quick-label">Laporan Kinerja</span>
    </a>
</div>

<!-- 1b. Akses Cepat Kelengkapan TUPOKSI Teknisi (5 Indikator) -->
<div class="kepegawaian-quick-grid">
    <a href="{{ route('dashboard.teknisi.area-tugas.index') }}"
        class="kepegawaian-quick-btn" style="--quick-color: var(--primary, #3b82f6);" title="Jadwal Area Tugas Kebersihan (Indikator #1)">
        <div class="kepegawaian-quick-icon" style="background: rgba(59, 130, 246, 0.15); color: var(--primary, #3b82f6);">
            <i class="fas fa-clipboard-list"></i>
        </div>
        <span class="kepegawaian-quick-label">Area Tugas</span>
    </a>

    <a href="{{ route('dashboard.teknisi.kebersihan-ruang.index') }}"
        class="kepegawaian-quick-btn" style="--quick-color: var(--success, #10b981);" title="Kebersihan Lingkungan & Ruang Kerja (Indikator #2)">
        <div class="kepegawaian-quick-icon" style="background: rgba(16, 185, 129, 0.15); color: var(--success, #10b981);">
            <i class="fas fa-broom"></i>
        </div>
        <span class="kepegawaian-quick-label">Kebersihan Ruang</span>
    </a>

    <a href="{{ route('dashboard.teknisi.kebersihan-rapat.index') }}"
        class="kepegawaian-quick-btn" style="--quick-color: var(--primary-light, #6366f1);" title="Kebersihan Lingkungan Ruang Rapat (Indikator #3)">
        <div class="kepegawaian-quick-icon" style="background: rgba(99, 102, 241, 0.15); color: var(--primary-light, #6366f1);">
            <i class="fas fa-people-roof"></i>
        </div>
        <span class="kepegawaian-quick-label">Kebersihan Rapat</span>
    </a>

    <a href="{{ route('dashboard.teknisi.sarana-rapat.index') }}"
        class="kepegawaian-quick-btn" style="--quick-color: var(--warning, #f59e0b);" title="Laporan Sarana Kegiatan Rapat (Indikator #4)">
        <div class="kepegawaian-quick-icon" style="background: rgba(245, 158, 11, 0.15); color: var(--warning, #f59e0b);">
            <i class="fas fa-clipboard-check"></i>
        </div>
        <span class="kepegawaian-quick-label">Sarana Rapat</span>
    </a>

    <a href="{{ route('dashboard.teknisi.jurnal-perbaikan.index') }}"
        class="kepegawaian-quick-btn" style="--quick-color: var(--accent, #06b6d4);" title="Pengarsipan & Laporan Perbaikan Sarpras (Indikator #5)">
        <div class="kepegawaian-quick-icon" style="background: rgba(6, 182, 212, 0.15); color: var(--accent, #06b6d4);">
            <i class="fas fa-screwdriver-wrench"></i>
        </div>
        <span class="kepegawaian-quick-label">Jurnal Perbaikan</span>
    </a>
</div>


<!-- 2b. Rekap Kelengkapan TUPOKSI Teknisi -->
@php
    $rekap = $teknisiTupoksiRekap ?? [];
    $stat = $teknisiStatistik ?? [];
    $rekapRows = [
        1 => ['label' => 'Area Tugas Kebersihan', 'route' => 'area-tugas', 'icon' => 'fa-clipboard-list', 'color' => '#3b82f6'],
        2 => ['label' => 'Kebersihan Lingkungan & Ruang', 'route' => 'kebersihan-ruang', 'icon' => 'fa-broom', 'color' => '#10b981'],
        3 => ['label' => 'Kebersihan Ruang Rapat', 'route' => 'kebersihan-rapat', 'icon' => 'fa-people-roof', 'color' => '#6366f1'],
        4 => ['label' => 'Laporan Sarana Rapat', 'route' => 'sarana-rapat', 'icon' => 'fa-clipboard-check', 'color' => '#f59e0b'],
        5 => ['label' => 'Jurnal Perbaikan Sarpras', 'route' => 'jurnal-perbaikan', 'icon' => 'fa-screwdriver-wrench', 'color' => '#06b6d4'],
    ];
@endphp
<div class="card" style="padding: 20px 22px; margin-bottom: 24px; border-radius: var(--radius-xl, 16px); border: 1px solid var(--border-color); background: var(--card-bg);">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; flex-wrap: wrap; gap: 10px;">
        <div>
            <h3 style="font-size: var(--text-lg, 1.125rem); font-weight: 800; color: var(--text-color); margin: 0; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-list-check text-success"></i> Kelengkapan TUPOKSI Teknisi
            </h3>
            <p style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); margin: 3px 0 0 0;">
                Rekapitulasi bukti fisik 5 indikator tupoksi teknisi.
            </p>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: 14px;">
        @foreach ($rekapRows as $no => $row)
            <a href="{{ route('dashboard.teknisi.' . $row['route'] . '.index') }}"
                style="text-decoration: none; background: var(--bg-hover); border-radius: var(--radius-lg, 12px); padding: 14px 16px; border: 1px solid var(--border-color); display: flex; align-items: center; gap: 12px; transition: transform .15s, border-color .15s;"
                onmouseover="this.style.borderColor='{{ $row['color'] }}'" onmouseout="this.style.borderColor='var(--border-color)'">
                <div style="width: 40px; height: 40px; border-radius: var(--radius-md, 8px); display: flex; align-items: center; justify-content: center; font-size: var(--text-base, 1rem); flex-shrink: 0; background: {{ $row['color'] }}22; color: {{ $row['color'] }};">
                    <i class="fas {{ $row['icon'] }}"></i>
                </div>
                <div style="min-width: 0;">
                    <div style="font-size: var(--text-xl, 1.25rem); font-weight: 800; color: var(--text-color); line-height: 1.1;">
                        {{ number_format($rekap[$no]->jumlah ?? 0, 0, ',', '.') }}
                    </div>
                    <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                        {{ $row['label'] }}
                    </div>
                </div>
            </a>
        @endforeach
    </div>
</div>

<!-- 3. Section: Visualisasi Statistik Pemeliharaan & Perangkat IT -->
<div class="card" style="padding: 20px 22px; margin-bottom: 24px; border-radius: var(--radius-xl, 16px); border: 1px solid var(--border-color); background: var(--card-bg);">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; flex-wrap: wrap; gap: 10px;">
        <div>
            <h3 style="font-size: var(--text-lg, 1.125rem); font-weight: 800; color: var(--text-color); margin: 0; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-chart-pie text-primary"></i> Statistik Pemeliharaan &amp; Infrastruktur IT
            </h3>
            <p style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); margin: 3px 0 0 0;">
                Visualisasi kategori perbaikan work order, status penyelesaian tiket, dan sebaran unit komputer sekolah.
            </p>
        </div>
        <div style="font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--primary-light, #6366f1); background: rgba(99,102,241,0.1); padding: 4px 10px; border-radius: var(--radius-2xl, 20px); border: 1px solid rgba(99,102,241,0.25);">
            <i class="fas fa-network-wired me-1"></i> Infrastruktur Jaringan
        </div>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px;">
        <!-- Chart 1: Kategori Perbaikan (Bar) -->
        <div style="background: var(--bg-hover); border-radius: var(--radius-lg, 12px); padding: 16px; border: 1px solid var(--border-color); display: flex; flex-direction: column;">
            <div style="font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-color); margin-bottom: 4px; display: flex; align-items: center; gap: 6px;">
                <i class="fas fa-wrench text-primary"></i> Kategori Perbaikan IT
            </div>
            <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-bottom: 10px;">Jaringan, hardware, printer &amp; sistem</div>
            <div style="height: 180px; position: relative; flex: 1;">
                <canvas id="teknisiChartKategori"></canvas>
            </div>
        </div>

        <!-- Chart 2: Status Work Order (Doughnut) -->
        <div style="background: var(--bg-hover); border-radius: var(--radius-lg, 12px); padding: 16px; border: 1px solid var(--border-color); display: flex; flex-direction: column;">
            <div style="font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-color); margin-bottom: 4px; display: flex; align-items: center; gap: 6px;">
                <i class="fas fa-ticket-simple text-success"></i> Status Work Order (WO)
            </div>
            <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-bottom: 10px;">Tiket perbaikan selesai vs proses</div>
            <div style="height: 180px; position: relative; flex: 1;">
                <canvas id="teknisiChartWO"></canvas>
            </div>
        </div>

        <!-- Chart 3: Sebaran Perangkat Komputer (Doughnut) -->
        <div style="background: var(--bg-hover); border-radius: var(--radius-lg, 12px); padding: 16px; border: 1px solid var(--border-color); display: flex; flex-direction: column;">
            <div style="font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-color); margin-bottom: 4px; display: flex; align-items: center; gap: 6px;">
                <i class="fas fa-desktop text-warning"></i> Sebaran Unit PC &amp; Klien
            </div>
            <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-bottom: 10px;">Distribusi lab komputer, TAS &amp; guru</div>
            <div style="height: 180px; position: relative; flex: 1;">
                <canvas id="teknisiChartSebaran"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- 4. Section: Target & Capaian Aktivitas & Indikator Kinerja Teknisi IT -->
@include('dashboard.tendik.partials-kinerja-chart', [
    'bidangKey' => 'teknisi',
    'bidangTitle' => 'Teknisi IT & Infrastruktur'
])

<!-- Payload JSON Data Chart Teknisi untuk JS -->
<script type="application/json" id="sectionTeknisiChartPayload">
    {!! json_encode($teknisiCharts ?? [], JSON_UNESCAPED_UNICODE) !!}
</script>


