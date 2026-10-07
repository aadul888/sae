{{-- Section Dashboard: Staf Administrasi Kesiswaan --}}

<!-- 1. Akses Cepat Menu Kesiswaan (Responsive Grid Seimbang & Genap) -->
<div class="kepegawaian-quick-grid">
    <a href="{{ route('dashboard.peserta-didik-aktif.index') }}"
        class="kepegawaian-quick-btn" style="--quick-color: #3b82f6;" title="Buku Induk & Data Pokok Siswa">
        <div class="kepegawaian-quick-icon" style="background: rgba(59, 130, 246, 0.15); color: #3b82f6;">
            <i class="fas fa-user-graduate"></i>
        </div>
        <span class="kepegawaian-quick-label">Buku Induk</span>
    </a>

    <a href="{{ route('dashboard.rombel.index') }}"
        class="kepegawaian-quick-btn" style="--quick-color: #06b6d4;" title="Rombongan Belajar">
        <div class="kepegawaian-quick-icon" style="background: rgba(6, 182, 212, 0.15); color: #06b6d4;">
            <i class="fas fa-door-open"></i>
        </div>
        <span class="kepegawaian-quick-label">Rombel</span>
    </a>

    <a href="{{ route('dashboard.peserta-didik-tidak-aktif.index') }}"
        class="kepegawaian-quick-btn" style="--quick-color: #ef4444;" title="Mutasi, Keluar & Alumni">
        <div class="kepegawaian-quick-icon" style="background: rgba(239, 68, 68, 0.15); color: #ef4444;">
            <i class="fas fa-user-xmark"></i>
        </div>
        <span class="kepegawaian-quick-label">Mutasi &amp; Alumni</span>
    </a>

    <a href="{{ route('dashboard.kompetensi-keahlian.index') }}"
        class="kepegawaian-quick-btn" style="--quick-color: #f59e0b;" title="Kompetensi Keahlian / Jurusan">
        <div class="kepegawaian-quick-icon" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b;">
            <i class="fas fa-layer-group"></i>
        </div>
        <span class="kepegawaian-quick-label">Jurusan</span>
    </a>

    <a href="{{ route('dashboard.tendik.target.index', ['bidang' => 'kesiswaan']) }}"
        class="kepegawaian-quick-btn" style="--quick-color: #6366f1;" title="Target &amp; Capaian Kinerja">
        <div class="kepegawaian-quick-icon" style="background: rgba(99, 102, 241, 0.15); color: #6366f1;">
            <i class="fas fa-bullseye"></i>
        </div>
        <span class="kepegawaian-quick-label">Target Capaian</span>
    </a>

    <a href="{{ route('dashboard.tendik.laporan.index', ['bidang' => 'kesiswaan']) }}"
        class="kepegawaian-quick-btn" style="--quick-color: #ec4899;" title="Laporan &amp; Rekapitulasi Kinerja">
        <div class="kepegawaian-quick-icon" style="background: rgba(236, 72, 153, 0.15); color: #ec4899;">
            <i class="fas fa-file-invoice"></i>
        </div>
        <span class="kepegawaian-quick-label">Laporan Kinerja</span>
    </a>
</div>

<!-- 2. Quick Stats Grid Kesiswaan -->
<div class="dash-stat-grid" style="margin-bottom: 24px; gap: 14px;">
    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(59, 130, 246, 0.15); color: #3b82f6;">
            <i class="fas fa-user-graduate"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">{{ number_format($stats['total_siswa'] ?? 1126, 0, ',', '.') }}</div>
            <div class="dash-stat-label">Siswa Aktif</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(16, 185, 129, 0.15); color: #10b981;">
            <i class="fas fa-id-card"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">{{ $stats['siswa_berfoto'] ?? 0 }}</div>
            <div class="dash-stat-label">Foto Kartu Pelajar</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b;">
            <i class="fas fa-door-open"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">{{ $stats['total_rombel'] ?? 70 }}</div>
            <div class="dash-stat-label">Rombel / Kelas</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(236, 72, 153, 0.15); color: #ec4899;">
            <i class="fas fa-venus-mars"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value" style="font-size: 1.1rem;">{{ $stats['siswa_laki'] ?? '-' }} L / {{ $stats['siswa_perempuan'] ?? '-' }} P</div>
            <div class="dash-stat-label">Gender Siswa</div>
        </div>
    </div>
</div>

<!-- Section: Visualisasi Statistik & Analitik Kesiswaan -->
<div class="card" style="padding: 20px 22px; margin-bottom: 24px; border-radius: 16px; border: 1px solid var(--border-color); background: var(--card-bg);">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; flex-wrap: wrap; gap: 10px;">
        <div>
            <h3 style="font-size: 1.05rem; font-weight: 800; color: var(--text-color); margin: 0; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-chart-pie text-primary"></i> Statistik &amp; Demografi Kesiswaan
            </h3>
            <p style="font-size: 0.78rem; color: var(--text-muted); margin: 3px 0 0 0;">
                Visualisasi rasio gender, sebaran rombel per tingkat, serta distribusi jurusan kompetensi keahlian.
            </p>
        </div>
        <div style="font-size: 0.76rem; font-weight: 700; color: #3b82f6; background: rgba(59,130,246,0.1); padding: 4px 10px; border-radius: 20px; border: 1px solid rgba(59,130,246,0.25);">
            <i class="fas fa-user-graduate me-1"></i> {{ number_format($stats['total_siswa'] ?? 1126, 0, ',', '.') }} Siswa
        </div>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px;">
        <!-- Chart 1: Gender Siswa (Doughnut) -->
        <div style="background: var(--bg-hover); border-radius: 12px; padding: 16px; border: 1px solid var(--border-color); display: flex; flex-direction: column;">
            <div style="font-size: 0.82rem; font-weight: 700; color: var(--text-color); margin-bottom: 4px; display: flex; align-items: center; gap: 6px;">
                <i class="fas fa-venus-mars text-accent"></i> Proporsi Gender Siswa
            </div>
            <div style="font-size: 0.72rem; color: var(--text-muted); margin-bottom: 10px;">Rasio Laki-laki vs Perempuan</div>
            <div style="height: 180px; position: relative; flex: 1;">
                <canvas id="kesiswaanChartGender"></canvas>
            </div>
            <div style="display: flex; justify-content: center; gap: 16px; margin-top: 10px; font-size: 0.78rem; border-top: 1px solid var(--border-color); padding-top: 8px;">
                <span style="display: inline-flex; align-items: center; gap: 6px;">
                    <span style="width: 10px; height: 10px; border-radius: 50%; background: #3b82f6;"></span>
                    <span>Laki-laki: <strong>{{ $kesiswaanCharts['gender']['L'] ?? 0 }}</strong></span>
                </span>
                <span style="display: inline-flex; align-items: center; gap: 6px;">
                    <span style="width: 10px; height: 10px; border-radius: 50%; background: #ec4899;"></span>
                    <span>Perempuan: <strong>{{ $kesiswaanCharts['gender']['P'] ?? 0 }}</strong></span>
                </span>
            </div>
        </div>

        <!-- Chart 2: Siswa per Tingkat (Bar) -->
        <div style="background: var(--bg-hover); border-radius: 12px; padding: 16px; border: 1px solid var(--border-color); display: flex; flex-direction: column;">
            <div style="font-size: 0.82rem; font-weight: 700; color: var(--text-color); margin-bottom: 4px; display: flex; align-items: center; gap: 6px;">
                <i class="fas fa-layer-group text-primary"></i> Siswa per Jenjang Kelas
            </div>
            <div style="font-size: 0.72rem; color: var(--text-muted); margin-bottom: 10px;">Populasi Siswa Kelas 10, 11, &amp; 12</div>
            <div style="height: 180px; position: relative; flex: 1;">
                <canvas id="kesiswaanChartTingkat"></canvas>
            </div>
        </div>

        <!-- Chart 3: Jurusan Keahlian (Horizontal Bar) -->
        <div style="background: var(--bg-hover); border-radius: 12px; padding: 16px; border: 1px solid var(--border-color); display: flex; flex-direction: column;">
            <div style="font-size: 0.82rem; font-weight: 700; color: var(--text-color); margin-bottom: 4px; display: flex; align-items: center; gap: 6px;">
                <i class="fas fa-graduation-cap text-success"></i> Rombel per Kompetensi Keahlian
            </div>
            <div style="font-size: 0.72rem; color: var(--text-muted); margin-bottom: 10px;">Distribusi kelas jurusan sekolah</div>
            <div style="height: 180px; position: relative; flex: 1;">
                <canvas id="kesiswaanChartJurusan"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- 4. Section: Target & Capaian Aktivitas & Indikator Kinerja Kesiswaan -->
@include('dashboard.tendik.partials-kinerja-chart', [
    'bidangKey' => 'kesiswaan',
    'bidangTitle' => 'Kesiswaan'
])

<!-- Payload JSON Data Chart Kesiswaan untuk JS -->
<script type="application/json" id="sectionKesiswaanChartPayload">
    {!! json_encode($kesiswaanCharts ?? [], JSON_UNESCAPED_UNICODE) !!}
</script>


