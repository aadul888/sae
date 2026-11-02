{{-- Section Dashboard: Petugas Keamanan & Satpam Sekolah --}}



<!-- 2. Quick Stats Grid Keamanan (Responsive): Tamu, Pelanggaran, e-Izin, Insiden -->
<div class="dash-stat-grid" style="margin-bottom: 24px; gap: 14px;">
    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(59, 130, 246, 0.15); color: var(--primary, #3b82f6);">
            <i class="fas fa-address-book"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">{{ number_format($keamananStats['tamu_hari_ini'] ?? 0, 0, ',', '.') }} Tamu</div>
            <div class="dash-stat-label">Buku Tamu Hari Ini</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(239, 68, 68, 0.15); color: var(--danger, #ef4444);">
            <i class="fas fa-triangle-exclamation"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">{{ number_format($keamananStats['pelanggaran_hari_ini'] ?? 0, 0, ',', '.') }} Kasus</div>
            <div class="dash-stat-label">Pelanggaran Siswa Hari Ini</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(14, 165, 233, 0.15); color: var(--info, #0ea5e9);">
            <i class="fas fa-door-open"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">{{ number_format($keamananStats['izin_hari_ini'] ?? 0, 0, ',', '.') }} Izin</div>
            <div class="dash-stat-label">e-Izin Gerbang Hari Ini</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(139, 92, 246, 0.15); color: var(--purple, #8b5cf6);">
            <i class="fas fa-bell"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">{{ number_format($keamananStats['insiden_aktif'] ?? 0, 0, ',', '.') }} Insiden</div>
            <div class="dash-stat-label">Insiden Dalam Penanganan</div>
        </div>
    </div>
</div>

<!-- Tab bidang tugas (desktop: tab, mobile: dropdown) -->
@include('dashboard.tendik.partials.domain-tabs')

<!-- 1. Akses Cepat Menu Keamanan — 6 tile: 4 operasional pos + 2 kinerja -->
<div class="kepegawaian-quick-grid">
    {{-- Operasional Pos --}}
    <a href="{{ route('dashboard.keamanan.buku-tamu.index') }}"
        class="kepegawaian-quick-btn" style="--quick-color: var(--primary, #3b82f6);" title="Buku Tamu">
        <div class="kepegawaian-quick-icon" style="background: rgba(59, 130, 246, 0.15); color: var(--primary, #3b82f6);">
            <i class="fas fa-book-open"></i>
        </div>
        <span class="kepegawaian-quick-label">Buku Tamu</span>
    </a>

    <a href="{{ route('dashboard.keamanan.patroli.index') }}"
        class="kepegawaian-quick-btn" style="--quick-color: var(--success, #10b981);" title="Patroli Keliling &amp; Log Insiden">
        <div class="kepegawaian-quick-icon" style="background: rgba(16, 185, 129, 0.15); color: var(--success, #10b981);">
            <i class="fas fa-shield-virus"></i>
        </div>
        <span class="kepegawaian-quick-label">Patroli</span>
    </a>

    <a href="{{ route('dashboard.keamanan.izin.index') }}"
        class="kepegawaian-quick-btn" style="--quick-color: var(--info, #0ea5e9);" title="Verifikasi e-Izin">
        <div class="kepegawaian-quick-icon" style="background: rgba(14, 165, 233, 0.15); color: var(--info, #0ea5e9);">
            <i class="fas fa-door-open"></i>
        </div>
        <span class="kepegawaian-quick-label">Verifikasi e-Izin</span>
    </a>

    <a href="{{ route('dashboard.keamanan.pelanggaran.index') }}"
        class="kepegawaian-quick-btn" style="--quick-color: var(--danger, #ef4444);" title="Pencatatan Pelanggaran Siswa di Pos Satpam">
        <div class="kepegawaian-quick-icon" style="background: rgba(239, 68, 68, 0.15); color: var(--danger, #ef4444);">
            <i class="fas fa-triangle-exclamation"></i>
        </div>
        <span class="kepegawaian-quick-label">Pelanggaran Siswa</span>
    </a>

    {{-- Kinerja & Tupoksi --}}
    <a href="{{ route('dashboard.tendik.target.index', ['bidang' => 'keamanan']) }}"
        class="kepegawaian-quick-btn" style="--quick-color: var(--primary-light, #6366f1);" title="Target &amp; Capaian Kinerja">
        <div class="kepegawaian-quick-icon" style="background: rgba(99, 102, 241, 0.15); color: var(--primary-light, #6366f1);">
            <i class="fas fa-bullseye"></i>
        </div>
        <span class="kepegawaian-quick-label">Target Capaian</span>
    </a>

    <a href="{{ route('dashboard.tendik.aktivitas.index', ['bidang' => 'keamanan']) }}"
        class="kepegawaian-quick-btn" style="--quick-color: var(--accent, #06b6d4);" title="Input Log Aktivitas Harian">
        <div class="kepegawaian-quick-icon" style="background: rgba(6, 182, 212, 0.15); color: var(--accent, #06b6d4);">
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



<!-- 3. Section: Visualisasi Statistik Buku Tamu & Patroli Keamanan -->
<div class="card" style="padding: 20px 22px; margin-bottom: 24px; border-radius: var(--radius-xl, 16px); border: 1px solid var(--border-color); background: var(--card-bg);">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; flex-wrap: wrap; gap: 10px;">
        <div>
            <h3 style="font-size: var(--text-lg, 1.125rem); font-weight: 800; color: var(--text-color); margin: 0; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-chart-pie text-primary"></i> Statistik Kunjungan Tamu &amp; Patroli Keamanan
            </h3>
            <p style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); margin: 3px 0 0 0;">
                Visualisasi asal instansi tamu dinas, frekuensi patroli keamanan per zona, serta evaluasi situasi sekolah.
            </p>
        </div>
        <div style="font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--danger, #ef4444); background: rgba(239,68,68,0.1); padding: 4px 10px; border-radius: var(--radius-2xl, 20px); border: 1px solid rgba(239,68,68,0.25);">
            <i class="fas fa-shield-halved me-1"></i> Pos Satpam Terintegrasi
        </div>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px;">
        <!-- Chart 1: Kategori Tamu (Doughnut) -->
        <div style="background: var(--bg-hover); border-radius: var(--radius-lg, 12px); padding: 16px; border: 1px solid var(--border-color); display: flex; flex-direction: column;">
            <div style="font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-color); margin-bottom: 4px; display: flex; align-items: center; gap: 6px;">
                <i class="fas fa-address-book text-primary"></i> Kategori Kunjungan Tamu
            </div>
            <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-bottom: 10px;">Orang tua, dinas, mitra industri &amp; umum</div>
            <div style="height: 180px; position: relative; flex: 1;">
                <canvas id="keamananChartTamu"></canvas>
            </div>
        </div>

        <!-- Chart 2: Kategori Pelanggaran Siswa (Bar) -->
        <div style="background: var(--bg-hover); border-radius: var(--radius-lg, 12px); padding: 16px; border: 1px solid var(--border-color); display: flex; flex-direction: column;">
            <div style="font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-color); margin-bottom: 4px; display: flex; align-items: center; gap: 6px;">
                <i class="fas fa-triangle-exclamation text-danger"></i> Kategori Pelanggaran Siswa
            </div>
            <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-bottom: 10px;">Kerapian, kehadiran, perilaku &amp; larangan berat</div>
            <div style="height: 180px; position: relative; flex: 1;">
                <canvas id="keamananChartPelanggaran"></canvas>
            </div>
        </div>

        <!-- Chart 3: Jenis Izin Keluar (Doughnut) -->
        <div style="background: var(--bg-hover); border-radius: var(--radius-lg, 12px); padding: 16px; border: 1px solid var(--border-color); display: flex; flex-direction: column;">
            <div style="font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-color); margin-bottom: 4px; display: flex; align-items: center; gap: 6px;">
                <i class="fas fa-door-open text-info"></i> Jenis e-Izin Keluar Gerbang
            </div>
            <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-bottom: 10px;">Keluar sebentar, pulang cepat &amp; terlambat masuk</div>
            <div style="height: 180px; position: relative; flex: 1;">
                <canvas id="keamananChartIzin"></canvas>
            </div>
        </div>

        <!-- Chart 4: Intensitas Patroli per Zona (Bar) -->
        <div style="background: var(--bg-hover); border-radius: var(--radius-lg, 12px); padding: 16px; border: 1px solid var(--border-color); display: flex; flex-direction: column;">
            <div style="font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-color); margin-bottom: 4px; display: flex; align-items: center; gap: 6px;">
                <i class="fas fa-route text-success"></i> Intensitas Patroli per Zona
            </div>
            <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-bottom: 10px;">Gerbang, kelas, lab &amp; pagar keliling</div>
            <div style="height: 180px; position: relative; flex: 1;">
                <canvas id="keamananChartPatroli"></canvas>
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


