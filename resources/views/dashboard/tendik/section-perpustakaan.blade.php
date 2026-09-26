{{-- Section Dashboard: Pustakawan (Staf Pelayanan Perpustakaan) --}}

<!-- 1. Akses Cepat Menu Perpustakaan (Responsive Grid Seimbang & Genap) -->
<div class="kepegawaian-quick-grid">
    <a href="{{ route('dashboard.perpustakaan.koleksi.index') }}"
        class="kepegawaian-quick-btn" style="--quick-color: #3b82f6;" title="Katalog & Koleksi Buku">
        <div class="kepegawaian-quick-icon" style="background: rgba(59, 130, 246, 0.15); color: #3b82f6;">
            <i class="fas fa-book"></i>
        </div>
        <span class="kepegawaian-quick-label">Katalog Buku</span>
    </a>

    <a href="{{ route('dashboard.perpustakaan.sirkulasi') }}"
        class="kepegawaian-quick-btn" style="--quick-color: #10b981;" title="Sirkulasi & Pengembalian">
        <div class="kepegawaian-quick-icon" style="background: rgba(16, 185, 129, 0.15); color: #10b981;">
            <i class="fas fa-exchange-alt"></i>
        </div>
        <span class="kepegawaian-quick-label">Sirkulasi</span>
    </a>

    <a href="{{ route('dashboard.perpustakaan.kunjungan') }}"
        class="kepegawaian-quick-btn" style="--quick-color: #f59e0b;" title="Buku Tamu Pengunjung">
        <div class="kepegawaian-quick-icon" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b;">
            <i class="fas fa-users-line"></i>
        </div>
        <span class="kepegawaian-quick-label">Kunjungan</span>
    </a>

    <a href="{{ route('dashboard.tendik.target.index', ['bidang' => 'perpustakaan']) }}"
        class="kepegawaian-quick-btn" style="--quick-color: #6366f1;" title="Target &amp; Capaian Kinerja">
        <div class="kepegawaian-quick-icon" style="background: rgba(99, 102, 241, 0.15); color: #6366f1;">
            <i class="fas fa-bullseye"></i>
        </div>
        <span class="kepegawaian-quick-label">Target Capaian</span>
    </a>

    <a href="{{ route('dashboard.tendik.aktivitas.index', ['bidang' => 'perpustakaan']) }}"
        class="kepegawaian-quick-btn" style="--quick-color: #06b6d4;" title="Input Log Aktivitas Harian">
        <div class="kepegawaian-quick-icon" style="background: rgba(6, 182, 212, 0.15); color: #06b6d4;">
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
</div>

<!-- 2. Quick Stats Grid Perpustakaan (Responsive) -->
<div class="dash-stat-grid" style="margin-bottom: 24px; gap: 14px;">
    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(59, 130, 246, 0.15); color: #3b82f6;">
            <i class="fas fa-book-bookmark"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">284 Judul</div>
            <div class="dash-stat-label">Buku Terdaftar</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(16, 185, 129, 0.15); color: #10b981;">
            <i class="fas fa-book"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">3.420 Eks</div>
            <div class="dash-stat-label">Koleksi Fisik</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b;">
            <i class="fas fa-users-line"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">42 Siswa</div>
            <div class="dash-stat-label">Kunjungan Hari Ini</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(139, 92, 246, 0.15); color: #8b5cf6;">
            <i class="fas fa-hand-holding-hand"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">18 Buku</div>
            <div class="dash-stat-label">Pinjaman Aktif</div>
        </div>
    </div>
</div>

<!-- 3. Section: Visualisasi Statistik Koleksi & Pengunjung Perpustakaan -->
<div class="card" style="padding: 20px 22px; margin-bottom: 24px; border-radius: 16px; border: 1px solid var(--border-color); background: var(--card-bg);">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; flex-wrap: wrap; gap: 10px;">
        <div>
            <h3 style="font-size: 1.05rem; font-weight: 800; color: var(--text-color); margin: 0; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-chart-pie text-primary"></i> Statistik Koleksi Pustaka &amp; Kunjungan
            </h3>
            <p style="font-size: 0.78rem; color: var(--text-muted); margin: 3px 0 0 0;">
                Visualisasi klasifikasi buku teks, sebaran jenjang pengunjung, dan ketersediaan sirkulasi buku.
            </p>
        </div>
        <div style="font-size: 0.76rem; font-weight: 700; color: #ec4899; background: rgba(236,72,153,0.1); padding: 4px 10px; border-radius: 20px; border: 1px solid rgba(236,72,153,0.25);">
            <i class="fas fa-book-open me-1"></i> Literasi Digital
        </div>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px;">
        <!-- Chart 1: Klasifikasi Buku (Doughnut) -->
        <div style="background: var(--bg-hover); border-radius: 12px; padding: 16px; border: 1px solid var(--border-color); display: flex; flex-direction: column;">
            <div style="font-size: 0.82rem; font-weight: 700; color: var(--text-color); margin-bottom: 4px; display: flex; align-items: center; gap: 6px;">
                <i class="fas fa-book-bookmark text-primary"></i> Klasifikasi Koleksi Pustaka
            </div>
            <div style="font-size: 0.72rem; color: var(--text-muted); margin-bottom: 10px;">Buku teks, vokasi, referensi &amp; fiksi</div>
            <div style="height: 180px; position: relative; flex: 1;">
                <canvas id="perpusChartKoleksi"></canvas>
            </div>
        </div>

        <!-- Chart 2: Pengunjung per Tingkat (Bar) -->
        <div style="background: var(--bg-hover); border-radius: 12px; padding: 16px; border: 1px solid var(--border-color); display: flex; flex-direction: column;">
            <div style="font-size: 0.82rem; font-weight: 700; color: var(--text-color); margin-bottom: 4px; display: flex; align-items: center; gap: 6px;">
                <i class="fas fa-users-line text-success"></i> Sebaran Pengunjung Literasi
            </div>
            <div style="font-size: 0.72rem; color: var(--text-muted); margin-bottom: 10px;">Pengunjung kelas 10, 11, 12 &amp; guru</div>
            <div style="height: 180px; position: relative; flex: 1;">
                <canvas id="perpusChartPengunjung"></canvas>
            </div>
        </div>

        <!-- Chart 3: Status Sirkulasi (Doughnut) -->
        <div style="background: var(--bg-hover); border-radius: 12px; padding: 16px; border: 1px solid var(--border-color); display: flex; flex-direction: column;">
            <div style="font-size: 0.82rem; font-weight: 700; color: var(--text-color); margin-bottom: 4px; display: flex; align-items: center; gap: 6px;">
                <i class="fas fa-exchange-alt text-warning"></i> Status Ketersediaan Buku
            </div>
            <div style="font-size: 0.72rem; color: var(--text-muted); margin-bottom: 10px;">Buku di rak vs sedang dipinjam</div>
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

<script>
document.addEventListener('DOMContentLoaded', function () {
    const payloadEl = document.getElementById('sectionPerpustakaanChartPayload');
    if (!payloadEl || typeof Chart === 'undefined') return;

    let payload = {};
    try {
        payload = JSON.parse(payloadEl.textContent);
    } catch (e) {
        console.error('Gagal parsing sectionPerpustakaanChartPayload', e);
        return;
    }

    const isLight = document.documentElement.getAttribute('data-theme') === 'light';
    const textColor = isLight ? '#1e293b' : '#f8fafc';
    const textMuted = isLight ? '#64748b' : '#94a3b8';
    const gridColor = isLight ? 'rgba(0,0,0,0.06)' : 'rgba(255,255,255,0.08)';

    // Plugin Angka Permanen pada Bar
    const barDataLabelsPlugin = {
        id: 'barDataLabelsPerpustakaan',
        afterDatasetsDraw(chart) {
            const { ctx } = chart;
            ctx.save();
            chart.data.datasets.forEach((dataset, dIdx) => {
                const meta = chart.getDatasetMeta(dIdx);
                if (meta.hidden) return;
                meta.data.forEach((bar, index) => {
                    const val = dataset.data[index];
                    if (val !== undefined && val !== null && val > 0) {
                        ctx.fillStyle = textColor;
                        ctx.font = 'bold 10px Inter, system-ui, sans-serif';
                        ctx.textAlign = 'center';
                        ctx.textBaseline = 'bottom';
                        ctx.fillText(Number(val).toLocaleString('id-ID'), bar.x, bar.y - 3);
                    }
                });
            });
            ctx.restore();
        }
    };

    // Plugin Doughnut Data Labels Permanen di Segmen Lingkaran
    const doughnutDataLabelsPlugin = {
        id: 'doughnutDataLabelsPerpustakaan',
        afterDatasetsDraw(chart) {
            const { ctx, data } = chart;
            const meta = chart.getDatasetMeta(0);
            if (!meta || !meta.data || !meta.data.length) return;

            const dataset = data.datasets[0];
            const total = dataset.data.reduce((a, b) => a + Number(b || 0), 0);

            ctx.save();
            meta.data.forEach((element, index) => {
                const val = dataset.data[index];
                if (!val || val <= 0) return;

                const pos = element.tooltipPosition();
                const pct = total > 0 ? Math.round((val / total) * 100) : 0;

                ctx.fillStyle = '#ffffff';
                ctx.font = 'bold 11px Inter, system-ui, sans-serif';
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';

                if (pct >= 10) {
                    ctx.fillText(Number(val).toLocaleString('id-ID'), pos.x, pos.y - 5);
                    ctx.font = '600 9px Inter, system-ui, sans-serif';
                    ctx.fillStyle = 'rgba(255,255,255,0.9)';
                    ctx.fillText(pct + '%', pos.x, pos.y + 6);
                } else {
                    ctx.fillText(Number(val).toLocaleString('id-ID'), pos.x, pos.y);
                }
            });

            if (total > 0 && meta.data[0]) {
                const centerX = (chart.chartArea.left + chart.chartArea.right) / 2;
                const centerY = (chart.chartArea.top + chart.chartArea.bottom) / 2;

                ctx.fillStyle = textMuted;
                ctx.font = '700 8px Inter, system-ui, sans-serif';
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                ctx.fillText('TOTAL', centerX, centerY - 9);

                ctx.fillStyle = textColor;
                ctx.font = '800 15px Inter, system-ui, sans-serif';
                ctx.fillText(Number(total).toLocaleString('id-ID'), centerX, centerY + 7);
            }
            ctx.restore();
        }
    };

    // 1. Chart Koleksi Buku (Doughnut)
    const ctxKol = document.getElementById('perpusChartKoleksi')?.getContext('2d');
    if (ctxKol && payload.kategoriBuku) {
        new Chart(ctxKol, {
            type: 'doughnut',
            data: {
                labels: Object.keys(payload.kategoriBuku),
                datasets: [{
                    data: Object.values(payload.kategoriBuku),
                    backgroundColor: ['#3b82f6', '#10b981', '#f59e0b', '#ec4899'],
                    borderWidth: 2,
                    borderColor: isLight ? '#ffffff' : '#1e293b',
                    hoverOffset: 4
                }]
            },
            plugins: [doughnutDataLabelsPlugin],
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                cutout: '62%'
            }
        });
    }

    // 2. Chart Pengunjung (Bar)
    const ctxPeng = document.getElementById('perpusChartPengunjung')?.getContext('2d');
    if (ctxPeng && payload.pengunjungTingkat) {
        new Chart(ctxPeng, {
            type: 'bar',
            data: {
                labels: Object.keys(payload.pengunjungTingkat),
                datasets: [{
                    data: Object.values(payload.pengunjungTingkat),
                    backgroundColor: ['rgba(59,130,246,0.85)', 'rgba(16,185,129,0.85)', 'rgba(245,158,11,0.85)', 'rgba(99,102,241,0.85)'],
                    borderRadius: 6,
                    maxBarThickness: 34
                }]
            },
            plugins: [barDataLabelsPlugin],
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { display: false }, ticks: { color: textMuted, font: { size: 10 } } },
                    y: { grid: { color: gridColor }, ticks: { color: textMuted, font: { size: 10 } }, beginAtZero: true }
                }
            }
        });
    }

    // 3. Chart Status Sirkulasi (Doughnut)
    const ctxSir = document.getElementById('perpusChartSirkulasi')?.getContext('2d');
    if (ctxSir && payload.statusSirkulasi) {
        new Chart(ctxSir, {
            type: 'doughnut',
            data: {
                labels: Object.keys(payload.statusSirkulasi),
                datasets: [{
                    data: Object.values(payload.statusSirkulasi),
                    backgroundColor: ['#10b981', '#f59e0b'],
                    borderWidth: 2,
                    borderColor: isLight ? '#ffffff' : '#1e293b',
                    hoverOffset: 4
                }]
            },
            plugins: [doughnutDataLabelsPlugin],
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                cutout: '62%'
            }
        });
    }
});
</script>
