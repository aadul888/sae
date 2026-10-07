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

<!-- 1. Akses Cepat Menu Persuratan (Responsive Grid Seimbang & Genap) -->
<div class="kepegawaian-quick-grid">
    <a href="{{ route('dashboard.persuratan.masuk.index') }}"
        class="kepegawaian-quick-btn" style="--quick-color: #3b82f6;" title="Buku Agenda Surat Masuk">
        <div class="kepegawaian-quick-icon" style="background: rgba(59, 130, 246, 0.15); color: #3b82f6;">
            <i class="fas fa-inbox"></i>
        </div>
        <span class="kepegawaian-quick-label">Surat Masuk</span>
    </a>

    <a href="{{ route('dashboard.persuratan.keluar.index') }}"
        class="kepegawaian-quick-btn" style="--quick-color: #10b981;" title="Buku Agenda Surat Keluar">
        <div class="kepegawaian-quick-icon" style="background: rgba(16, 185, 129, 0.15); color: #10b981;">
            <i class="fas fa-paper-plane"></i>
        </div>
        <span class="kepegawaian-quick-label">Surat Keluar</span>
    </a>

    <a href="{{ route('dashboard.persuratan.pengaturan.index') }}"
        class="kepegawaian-quick-btn" style="--quick-color: #f59e0b;" title="Pengaturan &amp; Kearsipan HDD">
        <div class="kepegawaian-quick-icon" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b;">
            <i class="fas fa-sliders"></i>
        </div>
        <span class="kepegawaian-quick-label">Pengaturan &amp; Arsip</span>
    </a>

    <a href="{{ route('dashboard.tendik.target.index', ['bidang' => 'persuratan']) }}"
        class="kepegawaian-quick-btn" style="--quick-color: #6366f1;" title="Target &amp; Capaian Kinerja">
        <div class="kepegawaian-quick-icon" style="background: rgba(99, 102, 241, 0.15); color: #6366f1;">
            <i class="fas fa-bullseye"></i>
        </div>
        <span class="kepegawaian-quick-label">Target Capaian</span>
    </a>

    <a href="{{ route('dashboard.tendik.aktivitas.index', ['bidang' => 'persuratan']) }}"
        class="kepegawaian-quick-btn" style="--quick-color: #06b6d4;" title="Input Log Aktivitas Harian">
        <div class="kepegawaian-quick-icon" style="background: rgba(6, 182, 212, 0.15); color: #06b6d4;">
            <i class="fas fa-clipboard-check"></i>
        </div>
        <span class="kepegawaian-quick-label">Aktivitas Harian</span>
    </a>

    <a href="{{ route('dashboard.tendik.laporan.index', ['bidang' => 'persuratan']) }}"
        class="kepegawaian-quick-btn" style="--quick-color: #ec4899;" title="Laporan &amp; Rekapitulasi Kinerja">
        <div class="kepegawaian-quick-icon" style="background: rgba(236, 72, 153, 0.15); color: #ec4899;">
            <i class="fas fa-file-invoice"></i>
        </div>
        <span class="kepegawaian-quick-label">Laporan Kinerja</span>
    </a>
</div>

<!-- 2. Quick Stats Grid Persuratan -->
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

<!-- Section: Visualisasi Statistik & Distribusi Persuratan -->
<div class="card" style="padding: 20px 22px; margin-bottom: 24px; border-radius: 16px; border: 1px solid var(--border-color); background: var(--card-bg);">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; flex-wrap: wrap; gap: 10px;">
        <div>
            <h3 style="font-size: 1.05rem; font-weight: 800; color: var(--text-color); margin: 0; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-chart-pie text-primary"></i> Statistik &amp; Distribusi Persuratan
            </h3>
            <p style="font-size: 0.78rem; color: var(--text-muted); margin: 3px 0 0 0;">
                Visualisasi volume surat berdasarkan jenis administrasi serta status pemrosesan dokumen resmi.
            </p>
        </div>
        <div style="font-size: 0.76rem; font-weight: 700; color: #10b981; background: rgba(16,185,129,0.1); padding: 4px 10px; border-radius: 20px; border: 1px solid rgba(16,185,129,0.25);">
            <i class="fas fa-envelope-open-text me-1"></i> Tata Kelola Arsip Digital
        </div>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px;">
        <!-- Chart 1: Jenis Surat (Doughnut) -->
        <div style="background: var(--bg-hover); border-radius: 12px; padding: 16px; border: 1px solid var(--border-color); display: flex; flex-direction: column;">
            <div style="font-size: 0.82rem; font-weight: 700; color: var(--text-color); margin-bottom: 4px; display: flex; align-items: center; gap: 6px;">
                <i class="fas fa-mail-bulk text-primary"></i> Distribusi Jenis Surat
            </div>
            <div style="font-size: 0.72rem; color: var(--text-muted); margin-bottom: 10px;">Surat Masuk, Keluar, SK, &amp; Tugas</div>
            <div style="height: 180px; position: relative; flex: 1;">
                <canvas id="persuratanChartJenis"></canvas>
            </div>
        </div>

        <!-- Chart 2: Status Pemrosesan Surat (Bar) -->
        <div style="background: var(--bg-hover); border-radius: 12px; padding: 16px; border: 1px solid var(--border-color); display: flex; flex-direction: column;">
            <div style="font-size: 0.82rem; font-weight: 700; color: var(--text-color); margin-bottom: 4px; display: flex; align-items: center; gap: 6px;">
                <i class="fas fa-tasks text-success"></i> Status Pemrosesan Dokumen
            </div>
            <div style="font-size: 0.72rem; color: var(--text-muted); margin-bottom: 10px;">Diproses, Selesai, &amp; Diarsipkan</div>
            <div style="height: 180px; position: relative; flex: 1;">
                <canvas id="persuratanChartStatus"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- 4. Section: Target & Capaian Aktivitas & Indikator Kinerja Persuratan -->
@include('dashboard.tendik.partials-kinerja-chart', [
    'bidangKey' => 'persuratan',
    'bidangTitle' => 'Persuratan & Tata Usaha'
])

<!-- Payload JSON Data Chart Persuratan untuk JS -->
<script type="application/json" id="sectionPersuratanChartPayload">
    {!! json_encode($persuratanCharts ?? [], JSON_UNESCAPED_UNICODE) !!}
</script>


