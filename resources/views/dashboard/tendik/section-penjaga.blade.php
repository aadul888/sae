{{-- Section Dashboard: Fasilitas & Penjaga Sekolah --}}

<!-- 1. Akses Cepat Menu Penjaga (Responsive Grid Seimbang & Genap) -->
<div class="kepegawaian-quick-grid">
    <a href="{{ route('dashboard.penjaga.kebersihan.index') }}"
        class="kepegawaian-quick-btn" style="--quick-color: var(--success, #10b981);" title="Checklist Kebersihan Harian">
        <div class="kepegawaian-quick-icon" style="background: rgba(16, 185, 129, 0.15); color: var(--success, #10b981);">
            <i class="fas fa-broom"></i>
        </div>
        <span class="kepegawaian-quick-label">Kebersihan</span>
    </a>

    <a href="{{ route('dashboard.penjaga.ronda.index') }}"
        class="kepegawaian-quick-btn" style="--quick-color: var(--primary, #3b82f6);" title="Buku Jaga & Ronda Malam">
        <div class="kepegawaian-quick-icon" style="background: rgba(59, 130, 246, 0.15); color: var(--primary, #3b82f6);">
            <i class="fas fa-moon"></i>
        </div>
        <span class="kepegawaian-quick-label">Ronda Malam</span>
    </a>

    <a href="{{ route('dashboard.identitas-sekolah.index') }}"
        class="kepegawaian-quick-btn" style="--quick-color: var(--warning, #f59e0b);" title="Fasilitas & Denah Gedung">
        <div class="kepegawaian-quick-icon" style="background: rgba(245, 158, 11, 0.15); color: var(--warning, #f59e0b);">
            <i class="fas fa-building"></i>
        </div>
        <span class="kepegawaian-quick-label">Fasilitas</span>
    </a>

    <a href="{{ route('dashboard.tendik.target.index', ['bidang' => 'penjaga']) }}"
        class="kepegawaian-quick-btn" style="--quick-color: var(--primary-light, #6366f1);" title="Target &amp; Capaian Kinerja">
        <div class="kepegawaian-quick-icon" style="background: rgba(99, 102, 241, 0.15); color: var(--primary-light, #6366f1);">
            <i class="fas fa-bullseye"></i>
        </div>
        <span class="kepegawaian-quick-label">Target Capaian</span>
    </a>

    <a href="{{ route('dashboard.tendik.aktivitas.index', ['bidang' => 'penjaga']) }}"
        class="kepegawaian-quick-btn" style="--quick-color: var(--accent, #06b6d4);" title="Input Log Aktivitas Harian">
        <div class="kepegawaian-quick-icon" style="background: rgba(6, 182, 212, 0.15); color: var(--accent, #06b6d4);">
            <i class="fas fa-clipboard-check"></i>
        </div>
        <span class="kepegawaian-quick-label">Aktivitas Harian</span>
    </a>

    <a href="{{ route('dashboard.tendik.laporan.index', ['bidang' => 'penjaga']) }}"
        class="kepegawaian-quick-btn" style="--quick-color: #ec4899;" title="Laporan &amp; Rekapitulasi Kinerja">
        <div class="kepegawaian-quick-icon" style="background: rgba(236, 72, 153, 0.15); color: #ec4899;">
            <i class="fas fa-file-invoice"></i>
        </div>
        <span class="kepegawaian-quick-label">Laporan Kinerja</span>
    </a>
</div>

<!-- 2. Quick Stats Grid Penjaga (Responsive) -->
<div class="dash-stat-grid" style="margin-bottom: 24px; gap: 14px;">
    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(16, 185, 129, 0.15); color: var(--success, #10b981);">
            <i class="fas fa-broom"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">{{ number_format($penjagaStats['checklist_hari_ini'] ?? 0, 0, ',', '.') }} Area</div>
            <div class="dash-stat-label">Checklist Hari Ini</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(59, 130, 246, 0.15); color: var(--primary, #3b82f6);">
            <i class="fas fa-door-closed"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">{{ number_format($penjagaStats['ronda_hari_ini'] ?? 0, 0, ',', '.') }} Kontrol</div>
            <div class="dash-stat-label">Ronda Malam Hari Ini</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(245, 158, 11, 0.15); color: var(--warning, #f59e0b);">
            <i class="fas fa-faucet-drip"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">{{ number_format($penjagaStats['area_perlu_tindakan'] ?? 0, 0, ',', '.') }} Area</div>
            <div class="dash-stat-label">Perlu Tindakan</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(99, 102, 241, 0.15); color: var(--primary);">
            <i class="fas fa-triangle-exclamation"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">{{ number_format($penjagaStats['temuan_malam'] ?? 0, 0, ',', '.') }} Temuan</div>
            <div class="dash-stat-label">Temuan Ronda</div>
        </div>
    </div>
</div>

<!-- Tab bidang tugas (desktop: tab, mobile: dropdown) -->
@include('dashboard.tendik.partials.domain-tabs')


<!-- 3. Section: Visualisasi Statistik Fasilitas & Kontrol Penjaga Sekolah -->
<div class="card" style="padding: 20px 22px; margin-bottom: 24px; border-radius: var(--radius-xl, 16px); border: 1px solid var(--border-color); background: var(--card-bg);">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; flex-wrap: wrap; gap: 10px;">
        <div>
            <h3 style="font-size: var(--text-lg, 1.125rem); font-weight: 800; color: var(--text-color); margin: 0; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-chart-pie text-primary"></i> Statistik Pemeliharaan &amp; Kontrol Fasilitas
            </h3>
            <p style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); margin: 3px 0 0 0;">
                Visualisasi pemantauan kebersihan area, kontrol keamanan malam, dan kesiapan operasional fasilitas.
            </p>
        </div>
        <div style="font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: #84cc16; background: rgba(132,204,22,0.1); padding: 4px 10px; border-radius: var(--radius-2xl, 20px); border: 1px solid rgba(132,204,22,0.25);">
            <i class="fas fa-broom me-1"></i> Kebersihan &amp; Ketertiban
        </div>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px;">
        <!-- Chart 1: Area Kebersihan (Bar) -->
        <div style="background: var(--bg-hover); border-radius: var(--radius-lg, 12px); padding: 16px; border: 1px solid var(--border-color); display: flex; flex-direction: column;">
            <div style="font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-color); margin-bottom: 4px; display: flex; align-items: center; gap: 6px;">
                <i class="fas fa-broom text-success"></i> Area Pembersihan Rutin
            </div>
            <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-bottom: 10px;">Kelas, selasar, toilet, &amp; halaman</div>
            <div style="height: 180px; position: relative; flex: 1;">
                <canvas id="penjagaChartArea"></canvas>
            </div>
        </div>

        <!-- Chart 2: Status Kontrol Malam (Doughnut) -->
        <div style="background: var(--bg-hover); border-radius: var(--radius-lg, 12px); padding: 16px; border: 1px solid var(--border-color); display: flex; flex-direction: column;">
            <div style="font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-color); margin-bottom: 4px; display: flex; align-items: center; gap: 6px;">
                <i class="fas fa-moon text-primary"></i> Kontrol Ronda Malam
            </div>
            <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-bottom: 10px;">Pintu, jendela, lampu &amp; gerbang</div>
            <div style="height: 180px; position: relative; flex: 1;">
                <canvas id="penjagaChartMalam"></canvas>
            </div>
        </div>

        <!-- Chart 3: Kondisi Fasilitas (Doughnut) -->
        <div style="background: var(--bg-hover); border-radius: var(--radius-lg, 12px); padding: 16px; border: 1px solid var(--border-color); display: flex; flex-direction: column;">
            <div style="font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-color); margin-bottom: 4px; display: flex; align-items: center; gap: 6px;">
                <i class="fas fa-check-double text-warning"></i> Kondisi Fisik Fasilitas
            </div>
            <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-bottom: 10px;">Bersih / laik vs perlu pemeliharaan</div>
            <div style="height: 180px; position: relative; flex: 1;">
                <canvas id="penjagaChartKondisi"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- 4. Section: Target & Capaian Aktivitas & Indikator Kinerja Penjaga -->
@include('dashboard.tendik.partials-kinerja-chart', [
    'bidangKey' => 'penjaga',
    'bidangTitle' => 'Fasilitas & Penjaga Sekolah'
])

<!-- Payload JSON Data Chart Penjaga untuk JS -->
<script type="application/json" id="sectionPenjagaChartPayload">
    {!! json_encode($penjagaCharts ?? [], JSON_UNESCAPED_UNICODE) !!}
</script>


