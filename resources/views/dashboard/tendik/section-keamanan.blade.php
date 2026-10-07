{{-- Section Dashboard: Petugas Keamanan & Satpam Sekolah --}}

<!-- 1. Akses Cepat Menu Keamanan (Responsive Grid Seimbang & Genap) -->
<div class="kepegawaian-quick-grid">
    <a href="{{ route('dashboard.keamanan.buku-tamu.index') }}"
        class="kepegawaian-quick-btn" style="--quick-color: #3b82f6;" title="Buku Tamu Pos Depan">
        <div class="kepegawaian-quick-icon" style="background: rgba(59, 130, 246, 0.15); color: #3b82f6;">
            <i class="fas fa-book-open"></i>
        </div>
        <span class="kepegawaian-quick-label">Buku Tamu</span>
    </a>

    <a href="{{ route('dashboard.keamanan.patroli.index') }}"
        class="kepegawaian-quick-btn" style="--quick-color: #10b981;" title="Patroli & Log Insiden">
        <div class="kepegawaian-quick-icon" style="background: rgba(16, 185, 129, 0.15); color: #10b981;">
            <i class="fas fa-shield-virus"></i>
        </div>
        <span class="kepegawaian-quick-label">Patroli</span>
    </a>

    <a href="{{ route('dashboard.presensi.scan') }}" target="_blank"
        class="kepegawaian-quick-btn" style="--quick-color: #f59e0b;" title="Pos Scanner RFID">
        <div class="kepegawaian-quick-icon" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b;">
            <i class="fas fa-qrcode"></i>
        </div>
        <span class="kepegawaian-quick-label">Pos RFID</span>
    </a>

    <a href="{{ route('dashboard.tendik.target.index', ['bidang' => 'keamanan']) }}"
        class="kepegawaian-quick-btn" style="--quick-color: #6366f1;" title="Target &amp; Capaian Kinerja">
        <div class="kepegawaian-quick-icon" style="background: rgba(99, 102, 241, 0.15); color: #6366f1;">
            <i class="fas fa-bullseye"></i>
        </div>
        <span class="kepegawaian-quick-label">Target Capaian</span>
    </a>

    <a href="{{ route('dashboard.tendik.aktivitas.index', ['bidang' => 'keamanan']) }}"
        class="kepegawaian-quick-btn" style="--quick-color: #06b6d4;" title="Input Log Aktivitas Harian">
        <div class="kepegawaian-quick-icon" style="background: rgba(6, 182, 212, 0.15); color: #06b6d4;">
            <i class="fas fa-clipboard-check"></i>
        </div>
        <span class="kepegawaian-quick-label">Aktivitas Harian</span>
    </a>

    <a href="{{ route('dashboard.tendik.laporan.index', ['bidang' => 'keamanan']) }}"
        class="kepegawaian-quick-btn" style="--quick-color: #ec4899;" title="Laporan &amp; Rekapitulasi Kinerja">
        <div class="kepegawaian-quick-icon" style="background: rgba(236, 72, 153, 0.15); color: #ec4899;">
            <i class="fas fa-file-invoice"></i>
        </div>
        <span class="kepegawaian-quick-label">Laporan Kinerja</span>
    </a>
</div>

<!-- 2. Quick Stats Grid Keamanan (Responsive) -->
<div class="dash-stat-grid" style="margin-bottom: 24px; gap: 14px;">
    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(59, 130, 246, 0.15); color: #3b82f6;">
            <i class="fas fa-user-shield"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">Aman &amp; Kondusif</div>
            <div class="dash-stat-label">Status Lingkungan</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(16, 185, 129, 0.15); color: #10b981;">
            <i class="fas fa-id-card-clip"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">Aktif</div>
            <div class="dash-stat-label">Pos Gerbang Depan</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b;">
            <i class="fas fa-clock"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">24 Jam</div>
            <div class="dash-stat-label">Penjagaan Shift</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(139, 92, 246, 0.15); color: #8b5cf6;">
            <i class="fas fa-bell"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">0 Insiden</div>
            <div class="dash-stat-label">Laporan Kejadian</div>
        </div>
    </div>
</div>

<!-- 3. Section: Visualisasi Statistik Buku Tamu & Patroli Keamanan -->
<div class="card" style="padding: 20px 22px; margin-bottom: 24px; border-radius: 16px; border: 1px solid var(--border-color); background: var(--card-bg);">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; flex-wrap: wrap; gap: 10px;">
        <div>
            <h3 style="font-size: 1.05rem; font-weight: 800; color: var(--text-color); margin: 0; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-chart-pie text-primary"></i> Statistik Kunjungan Tamu &amp; Patroli Keamanan
            </h3>
            <p style="font-size: 0.78rem; color: var(--text-muted); margin: 3px 0 0 0;">
                Visualisasi asal instansi tamu dinas, frekuensi patroli keamanan per zona, serta evaluasi situasi sekolah.
            </p>
        </div>
        <div style="font-size: 0.76rem; font-weight: 700; color: #ef4444; background: rgba(239,68,68,0.1); padding: 4px 10px; border-radius: 20px; border: 1px solid rgba(239,68,68,0.25);">
            <i class="fas fa-shield-halved me-1"></i> Pos Satpam Terintegrasi
        </div>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px;">
        <!-- Chart 1: Kategori Tamu (Doughnut) -->
        <div style="background: var(--bg-hover); border-radius: 12px; padding: 16px; border: 1px solid var(--border-color); display: flex; flex-direction: column;">
            <div style="font-size: 0.82rem; font-weight: 700; color: var(--text-color); margin-bottom: 4px; display: flex; align-items: center; gap: 6px;">
                <i class="fas fa-address-book text-primary"></i> Kategori Kunjungan Tamu
            </div>
            <div style="font-size: 0.72rem; color: var(--text-muted); margin-bottom: 10px;">Orang tua, dinas, mitra industri &amp; umum</div>
            <div style="height: 180px; position: relative; flex: 1;">
                <canvas id="keamananChartTamu"></canvas>
            </div>
        </div>

        <!-- Chart 2: Patroli per Zona (Bar) -->
        <div style="background: var(--bg-hover); border-radius: 12px; padding: 16px; border: 1px solid var(--border-color); display: flex; flex-direction: column;">
            <div style="font-size: 0.82rem; font-weight: 700; color: var(--text-color); margin-bottom: 4px; display: flex; align-items: center; gap: 6px;">
                <i class="fas fa-route text-success"></i> Intensitas Patroli per Zona
            </div>
            <div style="font-size: 0.72rem; color: var(--text-muted); margin-bottom: 10px;">Gerbang, kelas, lab &amp; pagar keliling</div>
            <div style="height: 180px; position: relative; flex: 1;">
                <canvas id="keamananChartPatroli"></canvas>
            </div>
        </div>

        <!-- Chart 3: Kondisi Keamanan (Doughnut) -->
        <div style="background: var(--bg-hover); border-radius: 12px; padding: 16px; border: 1px solid var(--border-color); display: flex; flex-direction: column;">
            <div style="font-size: 0.82rem; font-weight: 700; color: var(--text-color); margin-bottom: 4px; display: flex; align-items: center; gap: 6px;">
                <i class="fas fa-shield-virus text-warning"></i> Status Situasi Lingkungan
            </div>
            <div style="font-size: 0.72rem; color: var(--text-muted); margin-bottom: 10px;">Kondusif vs laporan temuan catatan</div>
            <div style="height: 180px; position: relative; flex: 1;">
                <canvas id="keamananChartStatus"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- 4. Section: Target & Capaian Aktivitas & Indikator Kinerja Keamanan -->
@include('dashboard.tendik.partials-kinerja-chart', [
    'bidangKey' => 'keamanan',
    'bidangTitle' => 'Keamanan & Pos Satpam'
])

<!-- Payload JSON Data Chart Keamanan untuk JS -->
<script type="application/json" id="sectionKeamananChartPayload">
    {!! json_encode($keamananCharts ?? [], JSON_UNESCAPED_UNICODE) !!}
</script>


