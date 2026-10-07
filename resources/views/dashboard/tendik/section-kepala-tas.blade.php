{{-- Section Dashboard: Kepala Tenaga Administrasi Sekolah (Kepala TAS / KTU) --}}

<!-- 1. Akses Cepat Menu Kepala TAS (Responsive Grid Seimbang & Genap) -->
<div class="kepegawaian-quick-grid">
    <a href="{{ route('dashboard.tendik-aktif.index') }}"
        class="kepegawaian-quick-btn" style="--quick-color: #3b82f6;" title="Direktori Staf Tendik">
        <div class="kepegawaian-quick-icon" style="background: rgba(59, 130, 246, 0.15); color: #3b82f6;">
            <i class="fas fa-users"></i>
        </div>
        <span class="kepegawaian-quick-label">Data Tendik</span>
    </a>

    <a href="{{ route('dashboard.guru-aktif.index') }}"
        class="kepegawaian-quick-btn" style="--quick-color: #10b981;" title="Direktori Guru Pendidik">
        <div class="kepegawaian-quick-icon" style="background: rgba(16, 185, 129, 0.15); color: #10b981;">
            <i class="fas fa-chalkboard-user"></i>
        </div>
        <span class="kepegawaian-quick-label">Data Guru</span>
    </a>

    <a href="{{ route('dashboard.peserta-didik-aktif.index') }}"
        class="kepegawaian-quick-btn" style="--quick-color: #06b6d4;" title="Data Pokok Siswa">
        <div class="kepegawaian-quick-icon" style="background: rgba(6, 182, 212, 0.15); color: #06b6d4;">
            <i class="fas fa-user-graduate"></i>
        </div>
        <span class="kepegawaian-quick-label">Buku Induk</span>
    </a>

    <a href="{{ route('dashboard.tendik.target.index') }}"
        class="kepegawaian-quick-btn" style="--quick-color: #6366f1;" title="Target &amp; Capaian Kinerja">
        <div class="kepegawaian-quick-icon" style="background: rgba(99, 102, 241, 0.15); color: #6366f1;">
            <i class="fas fa-bullseye"></i>
        </div>
        <span class="kepegawaian-quick-label">Target Capaian</span>
    </a>

    <a href="{{ route('dashboard.tendik.aktivitas.index') }}"
        class="kepegawaian-quick-btn" style="--quick-color: #f59e0b;" title="Monitoring Aktivitas Tendik">
        <div class="kepegawaian-quick-icon" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b;">
            <i class="fas fa-clipboard-check"></i>
        </div>
        <span class="kepegawaian-quick-label">Aktivitas Harian</span>
    </a>

    <a href="{{ route('dashboard.tendik.laporan.index') }}"
        class="kepegawaian-quick-btn" style="--quick-color: #ec4899;" title="Laporan &amp; Rekapitulasi Kinerja">
        <div class="kepegawaian-quick-icon" style="background: rgba(236, 72, 153, 0.15); color: #ec4899;">
            <i class="fas fa-file-invoice"></i>
        </div>
        <span class="kepegawaian-quick-label">Laporan Kinerja</span>
    </a>
</div>

<!-- 2. Quick Stats Grid Kepala TAS (Responsive) -->
<div class="dash-stat-grid" style="margin-bottom: 24px; gap: 14px;">
    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(59, 130, 246, 0.15); color: #3b82f6;">
            <i class="fas fa-users-gear"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">{{ $stats['total_tendik'] ?? 19 }} Staf</div>
            <div class="dash-stat-label">Tenaga Administrasi</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(16, 185, 129, 0.15); color: #10b981;">
            <i class="fas fa-award"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">{{ $stats['gtk_tugas_tambahan'] ?? 38 }} GTK</div>
            <div class="dash-stat-label">SK Tugas Tambahan</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b;">
            <i class="fas fa-inbox"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">{{ ($stats['total_surat_masuk'] ?? 0) + ($stats['total_surat_keluar'] ?? 0) }} Dok</div>
            <div class="dash-stat-label">Tata Kelola Surat</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(99, 102, 241, 0.15); color: var(--primary);">
            <i class="fas fa-school"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">Aktif</div>
            <div class="dash-stat-label">Operasional Satuan</div>
        </div>
    </div>
</div>

<!-- 3. Section: Visualisasi Statistik Koordinasi & Supervisi Tendik -->
<div class="card" style="padding: 20px 22px; margin-bottom: 24px; border-radius: 16px; border: 1px solid var(--border-color); background: var(--card-bg);">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; flex-wrap: wrap; gap: 10px;">
        <div>
            <h3 style="font-size: 1.05rem; font-weight: 800; color: var(--text-color); margin: 0; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-chart-pie text-primary"></i> Statistik Supervisi &amp; Manajemen Tenaga Administrasi (TAS)
            </h3>
            <p style="font-size: 0.78rem; color: var(--text-muted); margin: 3px 0 0 0;">
                Visualisasi pembagian penugasan staf per bidang, evaluasi pencapaian target kerja, dan rasio personel sekolah.
            </p>
        </div>
        <div style="font-size: 0.76rem; font-weight: 700; color: #10b981; background: rgba(16,185,129,0.1); padding: 4px 10px; border-radius: 20px; border: 1px solid rgba(16,185,129,0.25);">
            <i class="fas fa-crown me-1"></i> Koordinator TAS
        </div>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px;">
        <!-- Chart 1: Sebaran Staf per Bidang (Bar) -->
        <div style="background: var(--bg-hover); border-radius: 12px; padding: 16px; border: 1px solid var(--border-color); display: flex; flex-direction: column;">
            <div style="font-size: 0.82rem; font-weight: 700; color: var(--text-color); margin-bottom: 4px; display: flex; align-items: center; gap: 6px;">
                <i class="fas fa-users-gear text-primary"></i> Distribusi Personel per Urusan
            </div>
            <div style="font-size: 0.72rem; color: var(--text-muted); margin-bottom: 10px;">Sebaran staf administrasi di tiap bidang</div>
            <div style="height: 180px; position: relative; flex: 1;">
                <canvas id="tasChartDistribusi"></canvas>
            </div>
        </div>

        <!-- Chart 2: Status Capaian Target (Doughnut) -->
        <div style="background: var(--bg-hover); border-radius: 12px; padding: 16px; border: 1px solid var(--border-color); display: flex; flex-direction: column;">
            <div style="font-size: 0.82rem; font-weight: 700; color: var(--text-color); margin-bottom: 4px; display: flex; align-items: center; gap: 6px;">
                <i class="fas fa-bullseye text-success"></i> Evaluasi Ketercapaian KPI
            </div>
            <div style="font-size: 0.72rem; color: var(--text-muted); margin-bottom: 10px;">Progres pencapaian sasaran bidang</div>
            <div style="height: 180px; position: relative; flex: 1;">
                <canvas id="tasChartKinerja"></canvas>
            </div>
        </div>

        <!-- Chart 3: Rasio Pendidik vs Tendik (Doughnut) -->
        <div style="background: var(--bg-hover); border-radius: 12px; padding: 16px; border: 1px solid var(--border-color); display: flex; flex-direction: column;">
            <div style="font-size: 0.82rem; font-weight: 700; color: var(--text-color); margin-bottom: 4px; display: flex; align-items: center; gap: 6px;">
                <i class="fas fa-chalkboard-user text-warning"></i> Komposisi GTK Sekolah
            </div>
            <div style="font-size: 0.72rem; color: var(--text-muted); margin-bottom: 10px;">Perbandingan Guru vs Staf Tendik</div>
            <div style="height: 180px; position: relative; flex: 1;">
                <canvas id="tasChartKomposisi"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- 4. Section: Target & Capaian Aktivitas & Indikator Kinerja Kepala TAS -->
@include('dashboard.tendik.partials-kinerja-chart', [
    'bidangKey' => 'kepala-tas',
    'bidangTitle' => 'Kepala TAS / Koordinator'
])

<!-- Payload JSON Data Chart Kepala TAS untuk JS -->
<script type="application/json" id="sectionKepalaTasChartPayload">
    {!! json_encode($kepalaTasCharts ?? [], JSON_UNESCAPED_UNICODE) !!}
</script>


