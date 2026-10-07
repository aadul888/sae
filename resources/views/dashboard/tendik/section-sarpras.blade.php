{{-- Section Dashboard: Staf Administrasi Sarpras --}}

<!-- 1. Akses Cepat Menu Sarpras (Responsive Grid Seimbang & Genap) -->
<div class="kepegawaian-quick-grid">
    <a href="{{ route('dashboard.rombel.index') }}"
        class="kepegawaian-quick-btn" style="--quick-color: #3b82f6;" title="Pemetaan Ruang Kelas & Belajar">
        <div class="kepegawaian-quick-icon" style="background: rgba(59, 130, 246, 0.15); color: #3b82f6;">
            <i class="fas fa-door-open"></i>
        </div>
        <span class="kepegawaian-quick-label">Ruang Kelas</span>
    </a>

    <a href="{{ route('dashboard.identitas-sekolah.index') }}"
        class="kepegawaian-quick-btn" style="--quick-color: #10b981;" title="Fasilitas & Denah Gedung">
        <div class="kepegawaian-quick-icon" style="background: rgba(16, 185, 129, 0.15); color: #10b981;">
            <i class="fas fa-building"></i>
        </div>
        <span class="kepegawaian-quick-label">Fasilitas</span>
    </a>

    <a href="{{ route('dashboard.persuratan.index') }}"
        class="kepegawaian-quick-btn" style="--quick-color: #f59e0b;" title="Berita Acara & Pengadaan">
        <div class="kepegawaian-quick-icon" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b;">
            <i class="fas fa-file-contract"></i>
        </div>
        <span class="kepegawaian-quick-label">Berita Acara</span>
    </a>

    <a href="{{ route('dashboard.tendik.target.index', ['bidang' => 'sarpras']) }}"
        class="kepegawaian-quick-btn" style="--quick-color: #6366f1;" title="Target &amp; Capaian Kinerja">
        <div class="kepegawaian-quick-icon" style="background: rgba(99, 102, 241, 0.15); color: #6366f1;">
            <i class="fas fa-bullseye"></i>
        </div>
        <span class="kepegawaian-quick-label">Target Capaian</span>
    </a>

    <a href="{{ route('dashboard.tendik.aktivitas.index', ['bidang' => 'sarpras']) }}"
        class="kepegawaian-quick-btn" style="--quick-color: #06b6d4;" title="Input Log Aktivitas Harian">
        <div class="kepegawaian-quick-icon" style="background: rgba(6, 182, 212, 0.15); color: #06b6d4;">
            <i class="fas fa-clipboard-check"></i>
        </div>
        <span class="kepegawaian-quick-label">Aktivitas Harian</span>
    </a>

    <a href="{{ route('dashboard.tendik.laporan.index', ['bidang' => 'sarpras']) }}"
        class="kepegawaian-quick-btn" style="--quick-color: #ec4899;" title="Laporan &amp; Rekapitulasi Kinerja">
        <div class="kepegawaian-quick-icon" style="background: rgba(236, 72, 153, 0.15); color: #ec4899;">
            <i class="fas fa-file-invoice"></i>
        </div>
        <span class="kepegawaian-quick-label">Laporan Kinerja</span>
    </a>
</div>

<!-- 2. Quick Stats Grid Sarpras (Responsive) -->
<div class="dash-stat-grid" style="margin-bottom: 24px; gap: 14px;">
    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(59, 130, 246, 0.15); color: #3b82f6;">
            <i class="fas fa-building"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">{{ $stats['total_ruangan'] ?? 33 }} Ruang</div>
            <div class="dash-stat-label">Kelas &amp; Teori</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(16, 185, 129, 0.15); color: #10b981;">
            <i class="fas fa-flask"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">6 Unit</div>
            <div class="dash-stat-label">Lab &amp; Bengkel</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b;">
            <i class="fas fa-boxes-stacked"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">148 Unit</div>
            <div class="dash-stat-label">Sarana Aset</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(139, 92, 246, 0.15); color: #8b5cf6;">
            <i class="fas fa-screwdriver-wrench"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">98%</div>
            <div class="dash-stat-label">Laik Fasilitas</div>
        </div>
    </div>
</div>

<!-- Section: Visualisasi Statistik Ruang & Aset Sarpras -->
<div class="card" style="padding: 20px 22px; margin-bottom: 24px; border-radius: 16px; border: 1px solid var(--border-color); background: var(--card-bg);">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; flex-wrap: wrap; gap: 10px;">
        <div>
            <h3 style="font-size: 1.05rem; font-weight: 800; color: var(--text-color); margin: 0; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-chart-pie text-primary"></i> Statistik Fasilitas, Ruangan &amp; Aset
            </h3>
            <p style="font-size: 0.78rem; color: var(--text-muted); margin: 3px 0 0 0;">
                Visualisasi distribusi sebaran ruangan per kompleks gedung serta klasifikasi kategori aset sarpras.
            </p>
        </div>
        <div style="font-size: 0.76rem; font-weight: 700; color: #f59e0b; background: rgba(245,158,11,0.1); padding: 4px 10px; border-radius: 20px; border: 1px solid rgba(245,158,11,0.25);">
            <i class="fas fa-building me-1"></i> Manajemen Sarana Prasarana
        </div>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px;">
        <!-- Chart 1: Ruang per Gedung (Bar) -->
        <div style="background: var(--bg-hover); border-radius: 12px; padding: 16px; border: 1px solid var(--border-color); display: flex; flex-direction: column;">
            <div style="font-size: 0.82rem; font-weight: 700; color: var(--text-color); margin-bottom: 4px; display: flex; align-items: center; gap: 6px;">
                <i class="fas fa-city text-primary"></i> Sebaran Ruang per Gedung
            </div>
            <div style="font-size: 0.72rem; color: var(--text-muted); margin-bottom: 10px;">Distribusi ruangan di tiap kompleks gedung</div>
            <div style="height: 180px; position: relative; flex: 1;">
                <canvas id="sarprasChartGedung"></canvas>
            </div>
        </div>

        <!-- Chart 2: Kategori Aset (Doughnut) -->
        <div style="background: var(--bg-hover); border-radius: 12px; padding: 16px; border: 1px solid var(--border-color); display: flex; flex-direction: column;">
            <div style="font-size: 0.82rem; font-weight: 700; color: var(--text-color); margin-bottom: 4px; display: flex; align-items: center; gap: 6px;">
                <i class="fas fa-boxes-stacked text-warning"></i> Kategori Aset Inventaris
            </div>
            <div style="font-size: 0.72rem; color: var(--text-muted); margin-bottom: 10px;">Alat peraga, elektronik &amp; mesin</div>
            <div style="height: 180px; position: relative; flex: 1;">
                <canvas id="sarprasChartAset"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- 4. Section: Target & Capaian Aktivitas & Indikator Kinerja Sarpras -->
@include('dashboard.tendik.partials-kinerja-chart', [
    'bidangKey' => 'sarpras',
    'bidangTitle' => 'Sarana & Prasarana'
])

<!-- Payload JSON Data Chart Sarpras untuk JS -->
<script type="application/json" id="sectionSarprasChartPayload">
    {!! json_encode($sarprasCharts ?? [], JSON_UNESCAPED_UNICODE) !!}
</script>


