{{-- Section Dashboard: Guru / Petugas Piket Sekolah --}}

<!-- 1. Akses Cepat Menu Piket (Responsive Grid Seimbang & Genap) -->
<div class="kepegawaian-quick-grid">
    <a href="{{ route('dashboard.piket.izin.index') }}"
        class="kepegawaian-quick-btn" style="--quick-color: #3b82f6;" title="Perizinan Keluar-Masuk Siswa">
        <div class="kepegawaian-quick-icon" style="background: rgba(59, 130, 246, 0.15); color: #3b82f6;">
            <i class="fas fa-ticket-alt"></i>
        </div>
        <span class="kepegawaian-quick-label">e-Izin Siswa</span>
    </a>

    <a href="{{ route('dashboard.piket.jurnal.index') }}"
        class="kepegawaian-quick-btn" style="--quick-color: #10b981;" title="Jurnal Harian Guru Piket">
        <div class="kepegawaian-quick-icon" style="background: rgba(16, 185, 129, 0.15); color: #10b981;">
            <i class="fas fa-clipboard-list"></i>
        </div>
        <span class="kepegawaian-quick-label">Jurnal Piket</span>
    </a>

    <a href="{{ route('dashboard.presensi.scan') }}" target="_blank"
        class="kepegawaian-quick-btn" style="--quick-color: #f59e0b;" title="Scanner RFID Presensi Siswa">
        <div class="kepegawaian-quick-icon" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b;">
            <i class="fas fa-qrcode"></i>
        </div>
        <span class="kepegawaian-quick-label">Terminal RFID</span>
    </a>

    <a href="{{ route('dashboard.tendik.target.index', ['bidang' => 'piket']) }}"
        class="kepegawaian-quick-btn" style="--quick-color: #6366f1;" title="Target &amp; Capaian Kinerja">
        <div class="kepegawaian-quick-icon" style="background: rgba(99, 102, 241, 0.15); color: #6366f1;">
            <i class="fas fa-bullseye"></i>
        </div>
        <span class="kepegawaian-quick-label">Target Capaian</span>
    </a>

    <a href="{{ route('dashboard.tendik.aktivitas.index', ['bidang' => 'piket']) }}"
        class="kepegawaian-quick-btn" style="--quick-color: #06b6d4;" title="Input Log Aktivitas Harian">
        <div class="kepegawaian-quick-icon" style="background: rgba(6, 182, 212, 0.15); color: #06b6d4;">
            <i class="fas fa-clipboard-check"></i>
        </div>
        <span class="kepegawaian-quick-label">Aktivitas Harian</span>
    </a>

    <a href="{{ route('dashboard.tendik.laporan.index', ['bidang' => 'piket']) }}"
        class="kepegawaian-quick-btn" style="--quick-color: #ec4899;" title="Laporan &amp; Rekapitulasi Kinerja">
        <div class="kepegawaian-quick-icon" style="background: rgba(236, 72, 153, 0.15); color: #ec4899;">
            <i class="fas fa-file-invoice"></i>
        </div>
        <span class="kepegawaian-quick-label">Laporan Kinerja</span>
    </a>
</div>

<!-- 2. Quick Stats Grid Piket (Responsive) -->
<div class="dash-stat-grid" style="margin-bottom: 24px; gap: 14px;">
    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(59, 130, 246, 0.15); color: #3b82f6;">
            <i class="fas fa-person-walking-dashed-line-arrow-right"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">{{ $piketStats['izin_hari_ini'] ?? 0 }} Siswa</div>
            <div class="dash-stat-label">e-Izin Keluar Hari Ini</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(16, 185, 129, 0.15); color: #10b981;">
            <i class="fas fa-clipboard-check"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">{{ $piketStats['jurnal_terisi'] ?? 0 }} Kelas</div>
            <div class="dash-stat-label">Jurnal KBM Terisi</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b;">
            <i class="fas fa-chalkboard-user"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">{{ $piketStats['guru_hadir'] ?? 0 }} Guru</div>
            <div class="dash-stat-label">Pendidik Mengajar</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(139, 92, 246, 0.15); color: #8b5cf6;">
            <i class="fas fa-clock"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">Aktif</div>
            <div class="dash-stat-label">Status Petugas Piket</div>
        </div>
    </div>
</div>

<!-- 3. Section: Visualisasi Statistik Ketertiban & Jurnal KBM Piket -->
<div class="card" style="padding: 20px 22px; margin-bottom: 24px; border-radius: 16px; border: 1px solid var(--border-color); background: var(--card-bg);">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; flex-wrap: wrap; gap: 10px;">
        <div>
            <h3 style="font-size: 1.05rem; font-weight: 800; color: var(--text-color); margin: 0; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-chart-pie text-primary"></i> Statistik Ketertiban &amp; Pembelajaran KBM
            </h3>
            <p style="font-size: 0.78rem; color: var(--text-muted); margin: 3px 0 0 0;">
                Visualisasi alasan perizinan siswa, rekap durasi keterlambatan, dan pemantauan keterisian jurnal kelas.
            </p>
        </div>
        <div style="font-size: 0.76rem; font-weight: 700; color: #8b5cf6; background: rgba(139,92,246,0.1); padding: 4px 10px; border-radius: 20px; border: 1px solid rgba(139,92,246,0.25);">
            <i class="fas fa-clipboard-user me-1"></i> Tim Piket Harian
        </div>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px;">
        <!-- Chart 1: Alasan e-Izin (Doughnut) -->
        <div style="background: var(--bg-hover); border-radius: 12px; padding: 16px; border: 1px solid var(--border-color); display: flex; flex-direction: column;">
            <div style="font-size: 0.82rem; font-weight: 700; color: var(--text-color); margin-bottom: 4px; display: flex; align-items: center; gap: 6px;">
                <i class="fas fa-ticket-alt text-primary"></i> Klasifikasi Alasan e-Izin
            </div>
            <div style="font-size: 0.72rem; color: var(--text-muted); margin-bottom: 10px;">Sakit, keperluan keluarga, &amp; dinas</div>
            <div style="height: 180px; position: relative; flex: 1;">
                <canvas id="piketChartIzin"></canvas>
            </div>
        </div>

        <!-- Chart 2: Durasi Terlambat (Bar) -->
        <div style="background: var(--bg-hover); border-radius: 12px; padding: 16px; border: 1px solid var(--border-color); display: flex; flex-direction: column;">
            <div style="font-size: 0.82rem; font-weight: 700; color: var(--text-color); margin-bottom: 4px; display: flex; align-items: center; gap: 6px;">
                <i class="fas fa-clock-rotate-left text-warning"></i> Durasi Keterlambatan Masuk
            </div>
            <div style="font-size: 0.72rem; color: var(--text-muted); margin-bottom: 10px;">Sebaran menit keterlambatan pagi</div>
            <div style="height: 180px; position: relative; flex: 1;">
                <canvas id="piketChartTerlambat"></canvas>
            </div>
        </div>

        <!-- Chart 3: Keterisian Jurnal KBM (Doughnut) -->
        <div style="background: var(--bg-hover); border-radius: 12px; padding: 16px; border: 1px solid var(--border-color); display: flex; flex-direction: column;">
            <div style="font-size: 0.82rem; font-weight: 700; color: var(--text-color); margin-bottom: 4px; display: flex; align-items: center; gap: 6px;">
                <i class="fas fa-book-open-reader text-success"></i> Keterisian Jurnal KBM
            </div>
            <div style="font-size: 0.72rem; color: var(--text-muted); margin-bottom: 10px;">Jurnal terisi vs menunggu pengisian</div>
            <div style="height: 180px; position: relative; flex: 1;">
                <canvas id="piketChartJurnal"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- 4. Section: Target & Capaian Aktivitas & Indikator Kinerja Piket -->
@include('dashboard.tendik.partials-kinerja-chart', [
    'bidangKey' => 'piket',
    'bidangTitle' => 'Piket & Ketertiban Sekolah'
])

<!-- Payload JSON Data Chart Piket untuk JS -->
<script type="application/json" id="sectionPiketChartPayload">
    {!! json_encode($piketCharts ?? [], JSON_UNESCAPED_UNICODE) !!}
</script>


