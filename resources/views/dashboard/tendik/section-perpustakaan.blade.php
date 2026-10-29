{{-- Section Dashboard: Pustakawan (Staf Pelayanan Perpustakaan) --}}

<!-- 1. Akses Cepat Menu Perpustakaan (Responsive Grid Seimbang & Genap) -->
<div class="kepegawaian-quick-grid">
    <a href="{{ route('dashboard.perpustakaan.koleksi.index') }}"
        class="kepegawaian-quick-btn" style="--quick-color: var(--primary, var(--primary,#3b82f6));" title="Katalog & Koleksi Buku">
        <div class="kepegawaian-quick-icon" style="background: rgba(59, 130, 246, 0.15); color: var(--primary, var(--primary,#3b82f6));">
            <i class="fas fa-book"></i>
        </div>
        <span class="kepegawaian-quick-label">Katalog Buku</span>
    </a>

    <a href="{{ route('dashboard.perpustakaan.sirkulasi') }}"
        class="kepegawaian-quick-btn" style="--quick-color: var(--success, var(--success,#10b981));" title="Sirkulasi & Pengembalian">
        <div class="kepegawaian-quick-icon" style="background: rgba(16, 185, 129, 0.15); color: var(--success, var(--success,#10b981));">
            <i class="fas fa-exchange-alt"></i>
        </div>
        <span class="kepegawaian-quick-label">Sirkulasi</span>
    </a>

    <a href="{{ route('dashboard.perpustakaan.kunjungan') }}"
        class="kepegawaian-quick-btn" style="--quick-color: var(--warning, var(--warning,#f59e0b));" title="Buku Tamu Pengunjung">
        <div class="kepegawaian-quick-icon" style="background: rgba(245, 158, 11, 0.15); color: var(--warning, var(--warning,#f59e0b));">
            <i class="fas fa-users-line"></i>
        </div>
        <span class="kepegawaian-quick-label">Kunjungan</span>
    </a>

    <a href="{{ route('dashboard.tendik.target.index', ['bidang' => 'perpustakaan']) }}"
        class="kepegawaian-quick-btn" style="--quick-color: var(--primary-light, #6366f1);" title="Target &amp; Capaian Kinerja">
        <div class="kepegawaian-quick-icon" style="background: rgba(99, 102, 241, 0.15); color: var(--primary-light, #6366f1);">
            <i class="fas fa-bullseye"></i>
        </div>
        <span class="kepegawaian-quick-label">Target Capaian</span>
    </a>

    <a href="{{ route('dashboard.tendik.aktivitas.index', ['bidang' => 'perpustakaan']) }}"
        class="kepegawaian-quick-btn" style="--quick-color: var(--accent, var(--accent,#06b6d4));" title="Input Log Aktivitas Harian">
        <div class="kepegawaian-quick-icon" style="background: rgba(6, 182, 212, 0.15); color: var(--accent, var(--accent,#06b6d4));">
            <i class="fas fa-clipboard-check"></i>
        </div>
        <span class="kepegawaian-quick-label">Aktivitas Harian</span>
    </a>

    <a href="{{ route('dashboard.tendik.laporan.index', ['bidang' => 'perpustakaan']) }}"
        class="kepegawaian-quick-btn" style="--quick-color: #ec4899;" title="Laporan &amp; Rekapitulasi Kinerja">
        <div class="kepegawaian-quick-icon" style="background: rgba(236, 72, 153, 0.15); color: #ec4899;">
            <i class="fas fa-file-invoice"></i>
        </div>
        <span class="kepegawaian-quick-label">Laporan Kinerja</span>
    </a>

    <a href="{{ route('dashboard.perpustakaan.laporan.index') }}"
        class="kepegawaian-quick-btn" style="--quick-color: var(--purple, var(--purple,#8b5cf6));" title="Rekap Koleksi, Sirkulasi &amp; Kunjungan">
        <div class="kepegawaian-quick-icon" style="background: rgba(139, 92, 246, 0.15); color: var(--purple, var(--purple,#8b5cf6));">
            <i class="fas fa-file-lines"></i>
        </div>
        <span class="kepegawaian-quick-label">Laporan Pustaka</span>
    </a>

    <a href="{{ route('dashboard.perpustakaan.anggota.index') }}"
        class="kepegawaian-quick-btn" style="--quick-color: var(--purple,#8b5cf6);" title="Registrasi Anggota &amp; Kartu Digital">
        <div class="kepegawaian-quick-icon" style="background: rgba(139, 92, 246, 0.15); color: var(--purple,#8b5cf6);">
            <i class="fas fa-id-card"></i>
        </div>
        <span class="kepegawaian-quick-label">Anggota</span>
    </a>

    <a href="{{ route('dashboard.perpustakaan.import.form') }}"
        class="kepegawaian-quick-btn" style="--quick-color: var(--accent,#06b6d4);" title="Import Katalog &amp; Scan Barcode">
        <div class="kepegawaian-quick-icon" style="background: rgba(6, 182, 212, 0.15); color: var(--accent,#06b6d4);">
            <i class="fas fa-file-import"></i>
        </div>
        <span class="kepegawaian-quick-label">Import &amp; Scan</span>
    </a>

    <a href="{{ route('dashboard.perpustakaan.laporan.tahunan') }}"
        class="kepegawaian-quick-btn" style="--quick-color: var(--warning, var(--warning,#f59e0b));" title="Laporan Tahunan per Bulan (A3)">
        <div class="kepegawaian-quick-icon" style="background: rgba(245, 158, 11, 0.15); color: var(--warning, var(--warning,#f59e0b));">
            <i class="fas fa-chart-column"></i>
        </div>
        <span class="kepegawaian-quick-label">Laporan Tahunan</span>
    </a>

    <a href="{{ route('perpustakaan.index') }}" target="_blank"
        class="kepegawaian-quick-btn" style="--quick-color: var(--success, var(--success,#10b981));" title="Halaman Publik Perpustakaan">
        <div class="kepegawaian-quick-icon" style="background: rgba(16, 185, 129, 0.15); color: var(--success, var(--success,#10b981));">
            <i class="fas fa-globe"></i>
        </div>
        <span class="kepegawaian-quick-label">Situs Publik</span>
    </a>
</div>

<!-- 2. Quick Stats Grid Perpustakaan (Responsive) -->
<div class="dash-stat-grid" style="margin-bottom: 24px; gap: 14px;">
    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(59, 130, 246, 0.15); color: var(--primary, var(--primary,#3b82f6));">
            <i class="fas fa-book-bookmark"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">{{ number_format($perpusStats['total_judul'] ?? 0, 0, ',', '.') }} Judul</div>
            <div class="dash-stat-label">Buku Terdaftar</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(16, 185, 129, 0.15); color: var(--success, var(--success,#10b981));">
            <i class="fas fa-book"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">{{ number_format($perpusStats['total_eksemplar'] ?? 0, 0, ',', '.') }} Eks</div>
            <div class="dash-stat-label">Koleksi Fisik</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(245, 158, 11, 0.15); color: var(--warning, var(--warning,#f59e0b));">
            <i class="fas fa-users-line"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">{{ number_format($perpusStats['kunjungan_hari_ini'] ?? 0, 0, ',', '.') }} Orang</div>
            <div class="dash-stat-label">Kunjungan Hari Ini</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(139, 92, 246, 0.15); color: var(--purple, var(--purple,#8b5cf6));">
            <i class="fas fa-hand-holding-hand"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">{{ number_format($perpusStats['pinjam_aktif'] ?? 0, 0, ',', '.') }} Buku</div>
            <div class="dash-stat-label">Pinjaman Aktif</div>
        </div>
    </div>
</div>

<!-- Tab bidang tugas (desktop: tab, mobile: dropdown) -->
@include('dashboard.tendik.partials.domain-tabs')


<!-- 3. Section: Visualisasi Statistik Koleksi & Pengunjung Perpustakaan -->
<div class="card" style="padding: 20px 22px; margin-bottom: 24px; border-radius: var(--radius-xl, 16px); border: 1px solid var(--border-color); background: var(--card-bg);">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; flex-wrap: wrap; gap: 10px;">
        <div>
            <h3 style="font-size: var(--text-lg, 1.125rem); font-weight: 800; color: var(--text-color); margin: 0; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-chart-pie text-primary"></i> Statistik Koleksi Pustaka &amp; Kunjungan
            </h3>
            <p style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); margin: 3px 0 0 0;">
                Visualisasi klasifikasi buku teks, sebaran jenjang pengunjung, dan ketersediaan sirkulasi buku.
            </p>
        </div>
        <div style="font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: #ec4899; background: rgba(236,72,153,0.1); padding: 4px 10px; border-radius: var(--radius-2xl, 20px); border: 1px solid rgba(236,72,153,0.25);">
            <i class="fas fa-book-open me-1"></i> Literasi Digital
        </div>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px;">
        <!-- Chart 1: Klasifikasi Buku (Doughnut) -->
        <div style="background: var(--bg-hover); border-radius: var(--radius-lg, 12px); padding: 16px; border: 1px solid var(--border-color); display: flex; flex-direction: column;">
            <div style="font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-color); margin-bottom: 4px; display: flex; align-items: center; gap: 6px;">
                <i class="fas fa-book-bookmark text-primary"></i> Klasifikasi Koleksi Pustaka
            </div>
            <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-bottom: 10px;">Buku teks, vokasi, referensi &amp; fiksi</div>
            <div style="height: 180px; position: relative; flex: 1;">
                <canvas id="perpusChartKoleksi"></canvas>
            </div>
        </div>

        <!-- Chart 2: Pengunjung per Tingkat (Bar) -->
        <div style="background: var(--bg-hover); border-radius: var(--radius-lg, 12px); padding: 16px; border: 1px solid var(--border-color); display: flex; flex-direction: column;">
            <div style="font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-color); margin-bottom: 4px; display: flex; align-items: center; gap: 6px;">
                <i class="fas fa-users-line text-success"></i> Sebaran Pengunjung Literasi
            </div>
            <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-bottom: 10px;">Pengunjung kelas 10, 11, 12 &amp; guru</div>
            <div style="height: 180px; position: relative; flex: 1;">
                <canvas id="perpusChartPengunjung"></canvas>
            </div>
        </div>

        <!-- Chart 3: Status Sirkulasi (Doughnut) -->
        <div style="background: var(--bg-hover); border-radius: var(--radius-lg, 12px); padding: 16px; border: 1px solid var(--border-color); display: flex; flex-direction: column;">
            <div style="font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-color); margin-bottom: 4px; display: flex; align-items: center; gap: 6px;">
                <i class="fas fa-exchange-alt text-warning"></i> Status Ketersediaan Buku
            </div>
            <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-bottom: 10px;">Buku di rak vs sedang dipinjam</div>
            <div style="height: 180px; position: relative; flex: 1;">
                <canvas id="perpusChartSirkulasi"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- 4. Section: Target & Capaian Aktivitas & Indikator Kinerja Perpustakaan -->
@include('dashboard.tendik.partials-kinerja-chart', [
    'bidangKey' => 'perpustakaan',
    'bidangTitle' => 'Perpustakaan & Literasi'
])

<!-- Payload JSON Data Chart Perpustakaan untuk JS -->
<script type="application/json" id="sectionPerpustakaanChartPayload">
    {!! json_encode($perpustakaanCharts ?? [], JSON_UNESCAPED_UNICODE) !!}
</script>


