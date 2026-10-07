{{-- Section Dashboard: Teknisi IT & Infrastruktur Jaringan --}}

<!-- 1. Akses Cepat Menu Teknisi IT (Responsive Grid Seimbang & Genap) -->
<div class="kepegawaian-quick-grid">
    <a href="{{ route('dashboard.teknisi.work-order') }}"
        class="kepegawaian-quick-btn" style="--quick-color: #3b82f6;" title="Work Order & Pelaporan Masalah">
        <div class="kepegawaian-quick-icon" style="background: rgba(59, 130, 246, 0.15); color: #3b82f6;">
            <i class="fas fa-wrench"></i>
        </div>
        <span class="kepegawaian-quick-label">Work Order</span>
    </a>

    <a href="{{ route('dashboard.teknisi.pemeliharaan') }}"
        class="kepegawaian-quick-btn" style="--quick-color: #10b981;" title="Jadwal Pemeliharaan Berkala">
        <div class="kepegawaian-quick-icon" style="background: rgba(16, 185, 129, 0.15); color: #10b981;">
            <i class="fas fa-calendar-check"></i>
        </div>
        <span class="kepegawaian-quick-label">Pemeliharaan</span>
    </a>

    <a href="{{ route('dashboard.sarpras.aset.index') }}"
        class="kepegawaian-quick-btn" style="--quick-color: #f59e0b;" title="Aset Perangkat IT">
        <div class="kepegawaian-quick-icon" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b;">
            <i class="fas fa-network-wired"></i>
        </div>
        <span class="kepegawaian-quick-label">Aset IT</span>
    </a>

    <a href="{{ route('dashboard.tendik.target.index', ['bidang' => 'teknisi']) }}"
        class="kepegawaian-quick-btn" style="--quick-color: #6366f1;" title="Target &amp; Capaian Kinerja">
        <div class="kepegawaian-quick-icon" style="background: rgba(99, 102, 241, 0.15); color: #6366f1;">
            <i class="fas fa-bullseye"></i>
        </div>
        <span class="kepegawaian-quick-label">Target Capaian</span>
    </a>

    <a href="{{ route('dashboard.tendik.aktivitas.index', ['bidang' => 'teknisi']) }}"
        class="kepegawaian-quick-btn" style="--quick-color: #06b6d4;" title="Input Log Aktivitas Harian">
        <div class="kepegawaian-quick-icon" style="background: rgba(6, 182, 212, 0.15); color: #06b6d4;">
            <i class="fas fa-clipboard-check"></i>
        </div>
        <span class="kepegawaian-quick-label">Aktivitas Harian</span>
    </a>

    <a href="{{ route('dashboard.tendik.laporan.index', ['bidang' => 'teknisi']) }}"
        class="kepegawaian-quick-btn" style="--quick-color: #ec4899;" title="Laporan &amp; Rekapitulasi Kinerja">
        <div class="kepegawaian-quick-icon" style="background: rgba(236, 72, 153, 0.15); color: #ec4899;">
            <i class="fas fa-file-invoice"></i>
        </div>
        <span class="kepegawaian-quick-label">Laporan Kinerja</span>
    </a>
</div>

<!-- 2. Quick Stats Grid Teknisi IT (Responsive) -->
<div class="dash-stat-grid" style="margin-bottom: 24px; gap: 14px;">
    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(59, 130, 246, 0.15); color: #3b82f6;">
            <i class="fas fa-wifi"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">100 Mbps</div>
            <div class="dash-stat-label">Bandwidth Internet</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(16, 185, 129, 0.15); color: #10b981;">
            <i class="fas fa-server"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">99.8%</div>
            <div class="dash-stat-label">Server Uptime</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b;">
            <i class="fas fa-desktop"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">72 Unit</div>
            <div class="dash-stat-label">PC Kantor &amp; Lab</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(139, 92, 246, 0.15); color: #8b5cf6;">
            <i class="fas fa-check-double"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">Normal</div>
            <div class="dash-stat-label">Status Jaringan</div>
        </div>
    </div>
</div>

<!-- 3. Section: Visualisasi Statistik Pemeliharaan & Perangkat IT -->
<div class="card" style="padding: 20px 22px; margin-bottom: 24px; border-radius: 16px; border: 1px solid var(--border-color); background: var(--card-bg);">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; flex-wrap: wrap; gap: 10px;">
        <div>
            <h3 style="font-size: 1.05rem; font-weight: 800; color: var(--text-color); margin: 0; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-chart-pie text-primary"></i> Statistik Pemeliharaan &amp; Infrastruktur IT
            </h3>
            <p style="font-size: 0.78rem; color: var(--text-muted); margin: 3px 0 0 0;">
                Visualisasi kategori perbaikan work order, status penyelesaian tiket, dan sebaran unit komputer sekolah.
            </p>
        </div>
        <div style="font-size: 0.76rem; font-weight: 700; color: #6366f1; background: rgba(99,102,241,0.1); padding: 4px 10px; border-radius: 20px; border: 1px solid rgba(99,102,241,0.25);">
            <i class="fas fa-network-wired me-1"></i> Infrastruktur Jaringan
        </div>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px;">
        <!-- Chart 1: Kategori Perbaikan (Bar) -->
        <div style="background: var(--bg-hover); border-radius: 12px; padding: 16px; border: 1px solid var(--border-color); display: flex; flex-direction: column;">
            <div style="font-size: 0.82rem; font-weight: 700; color: var(--text-color); margin-bottom: 4px; display: flex; align-items: center; gap: 6px;">
                <i class="fas fa-wrench text-primary"></i> Kategori Perbaikan IT
            </div>
            <div style="font-size: 0.72rem; color: var(--text-muted); margin-bottom: 10px;">Jaringan, hardware, printer &amp; sistem</div>
            <div style="height: 180px; position: relative; flex: 1;">
                <canvas id="teknisiChartKategori"></canvas>
            </div>
        </div>

        <!-- Chart 2: Status Work Order (Doughnut) -->
        <div style="background: var(--bg-hover); border-radius: 12px; padding: 16px; border: 1px solid var(--border-color); display: flex; flex-direction: column;">
            <div style="font-size: 0.82rem; font-weight: 700; color: var(--text-color); margin-bottom: 4px; display: flex; align-items: center; gap: 6px;">
                <i class="fas fa-ticket-simple text-success"></i> Status Work Order (WO)
            </div>
            <div style="font-size: 0.72rem; color: var(--text-muted); margin-bottom: 10px;">Tiket perbaikan selesai vs proses</div>
            <div style="height: 180px; position: relative; flex: 1;">
                <canvas id="teknisiChartWO"></canvas>
            </div>
        </div>

        <!-- Chart 3: Sebaran Perangkat Komputer (Doughnut) -->
        <div style="background: var(--bg-hover); border-radius: 12px; padding: 16px; border: 1px solid var(--border-color); display: flex; flex-direction: column;">
            <div style="font-size: 0.82rem; font-weight: 700; color: var(--text-color); margin-bottom: 4px; display: flex; align-items: center; gap: 6px;">
                <i class="fas fa-desktop text-warning"></i> Sebaran Unit PC &amp; Klien
            </div>
            <div style="font-size: 0.72rem; color: var(--text-muted); margin-bottom: 10px;">Distribusi lab komputer, TAS &amp; guru</div>
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


