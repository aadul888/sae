{{-- Section Dashboard: Laboran (Staf Khusus Ruang Laboratorium) --}}

<!-- 1. Akses Cepat Menu Laboran (Responsive Grid Seimbang & Genap) -->
<div class="kepegawaian-quick-grid">
    <a href="{{ route('dashboard.sarpras.aset.index') }}"
        class="kepegawaian-quick-btn" style="--quick-color: #3b82f6;" title="Inventaris Peralatan Praktikum">
        <div class="kepegawaian-quick-icon" style="background: rgba(59, 130, 246, 0.15); color: #3b82f6;">
            <i class="fas fa-microscope"></i>
        </div>
        <span class="kepegawaian-quick-label">Alat Praktik</span>
    </a>

    <a href="{{ route('dashboard.sarpras.peminjaman.index') }}"
        class="kepegawaian-quick-btn" style="--quick-color: #f59e0b;" title="Peminjaman Alat Praktik">
        <div class="kepegawaian-quick-icon" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b;">
            <i class="fas fa-hand-holding"></i>
        </div>
        <span class="kepegawaian-quick-label">Peminjaman</span>
    </a>

    <a href="{{ route('dashboard.pembelajaran.index') }}"
        class="kepegawaian-quick-btn" style="--quick-color: #10b981;" title="Jadwal Mapel Praktik KBM">
        <div class="kepegawaian-quick-icon" style="background: rgba(16, 185, 129, 0.15); color: #10b981;">
            <i class="fas fa-calendar-day"></i>
        </div>
        <span class="kepegawaian-quick-label">Jadwal Praktik</span>
    </a>

    <a href="{{ route('dashboard.tendik.target.index', ['bidang' => 'laboran']) }}"
        class="kepegawaian-quick-btn" style="--quick-color: #6366f1;" title="Target &amp; Capaian Kinerja">
        <div class="kepegawaian-quick-icon" style="background: rgba(99, 102, 241, 0.15); color: #6366f1;">
            <i class="fas fa-bullseye"></i>
        </div>
        <span class="kepegawaian-quick-label">Target Capaian</span>
    </a>

    <a href="{{ route('dashboard.tendik.aktivitas.index', ['bidang' => 'laboran']) }}"
        class="kepegawaian-quick-btn" style="--quick-color: #06b6d4;" title="Input Log Aktivitas Harian">
        <div class="kepegawaian-quick-icon" style="background: rgba(6, 182, 212, 0.15); color: #06b6d4;">
            <i class="fas fa-clipboard-check"></i>
        </div>
        <span class="kepegawaian-quick-label">Aktivitas Harian</span>
    </a>

    <a href="{{ route('dashboard.tendik.laporan.index', ['bidang' => 'laboran']) }}"
        class="kepegawaian-quick-btn" style="--quick-color: #ec4899;" title="Laporan &amp; Rekapitulasi Kinerja">
        <div class="kepegawaian-quick-icon" style="background: rgba(236, 72, 153, 0.15); color: #ec4899;">
            <i class="fas fa-file-invoice"></i>
        </div>
        <span class="kepegawaian-quick-label">Laporan Kinerja</span>
    </a>
</div>

<!-- 2. Quick Stats Grid Laboran (Responsive) -->
<div class="dash-stat-grid" style="margin-bottom: 24px; gap: 14px;">
    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(16, 185, 129, 0.15); color: #10b981;">
            <i class="fas fa-flask"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">6 Ruang</div>
            <div class="dash-stat-label">Laboratorium &amp; Bengkel</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(59, 130, 246, 0.15); color: #3b82f6;">
            <i class="fas fa-clock"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">{{ $stats['jam_praktik'] ?? 48 }} JP</div>
            <div class="dash-stat-label">Praktik Kejuruan/Mgg</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b;">
            <i class="fas fa-microscope"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">96%</div>
            <div class="dash-stat-label">Alat Siap Pakai</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(99, 102, 241, 0.15); color: var(--primary);">
            <i class="fas fa-shield-virus"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">K3 Laik</div>
            <div class="dash-stat-label">Standar Keselamatan</div>
        </div>
    </div>
</div>

<!-- 3. Section: Visualisasi Statistik & Fasilitas Laboratorium -->
<div class="card" style="padding: 20px 22px; margin-bottom: 24px; border-radius: 16px; border: 1px solid var(--border-color); background: var(--card-bg);">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; flex-wrap: wrap; gap: 10px;">
        <div>
            <h3 style="font-size: 1.05rem; font-weight: 800; color: var(--text-color); margin: 0; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-chart-pie text-primary"></i> Statistik Peralatan &amp; Fasilitas Laboratorium
            </h3>
            <p style="font-size: 0.78rem; color: var(--text-muted); margin: 3px 0 0 0;">
                Visualisasi sebaran unit laboratorium, kondisi kesiapan peralatan, dan klasifikasi inventaris praktikum.
            </p>
        </div>
        <div style="font-size: 0.76rem; font-weight: 700; color: #10b981; background: rgba(16,185,129,0.1); padding: 4px 10px; border-radius: 20px; border: 1px solid rgba(16,185,129,0.25);">
            <i class="fas fa-flask me-1"></i> Standar K3 Lab
        </div>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px;">
        <!-- Chart 1: Sebaran Unit Lab (Doughnut) -->
        <div style="background: var(--bg-hover); border-radius: 12px; padding: 16px; border: 1px solid var(--border-color); display: flex; flex-direction: column;">
            <div style="font-size: 0.82rem; font-weight: 700; color: var(--text-color); margin-bottom: 4px; display: flex; align-items: center; gap: 6px;">
                <i class="fas fa-vial text-primary"></i> Sebaran Unit Laboratorium
            </div>
            <div style="font-size: 0.72rem; color: var(--text-muted); margin-bottom: 10px;">Distribusi lab komputer, sains &amp; bengkel</div>
            <div style="height: 180px; position: relative; flex: 1;">
                <canvas id="laboranChartUnit"></canvas>
            </div>
        </div>

        <!-- Chart 2: Status Kesiapan Alat (Doughnut) -->
        <div style="background: var(--bg-hover); border-radius: 12px; padding: 16px; border: 1px solid var(--border-color); display: flex; flex-direction: column;">
            <div style="font-size: 0.82rem; font-weight: 700; color: var(--text-color); margin-bottom: 4px; display: flex; align-items: center; gap: 6px;">
                <i class="fas fa-microscope text-success"></i> Status Kesiapan Instrumen
            </div>
            <div style="font-size: 0.72rem; color: var(--text-muted); margin-bottom: 10px;">Kondisi baik, kalibrasi, &amp; perbaikan</div>
            <div style="height: 180px; position: relative; flex: 1;">
                <canvas id="laboranChartStatus"></canvas>
            </div>
        </div>

        <!-- Chart 3: Klasifikasi Bahan & Alat (Bar) -->
        <div style="background: var(--bg-hover); border-radius: 12px; padding: 16px; border: 1px solid var(--border-color); display: flex; flex-direction: column;">
            <div style="font-size: 0.82rem; font-weight: 700; color: var(--text-color); margin-bottom: 4px; display: flex; align-items: center; gap: 6px;">
                <i class="fas fa-boxes-stacked text-warning"></i> Klasifikasi Inventaris Praktikum
            </div>
            <div style="font-size: 0.72rem; color: var(--text-muted); margin-bottom: 10px;">Bahan habis pakai, instrumen &amp; APD</div>
            <div style="height: 180px; position: relative; flex: 1;">
                <canvas id="laboranChartBahan"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- 4. Section: Target & Capaian Aktivitas & Indikator Kinerja Laboran -->
@include('dashboard.tendik.partials-kinerja-chart', [
    'bidangKey' => 'laboran',
    'bidangTitle' => 'Laboratorium & Bengkel'
])

<!-- Payload JSON Data Chart Laboran untuk JS -->
<script type="application/json" id="sectionLaboranChartPayload">
    {!! json_encode($laboranCharts ?? [], JSON_UNESCAPED_UNICODE) !!}
</script>


