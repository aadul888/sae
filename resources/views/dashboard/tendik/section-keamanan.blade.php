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

<script>
document.addEventListener('DOMContentLoaded', function () {
    const payloadEl = document.getElementById('sectionKeamananChartPayload');
    if (!payloadEl || typeof Chart === 'undefined') return;

    let payload = {};
    try {
        payload = JSON.parse(payloadEl.textContent);
    } catch (e) {
        console.error('Gagal parsing sectionKeamananChartPayload', e);
        return;
    }

    const isLight = document.documentElement.getAttribute('data-theme') === 'light';
    const textColor = isLight ? '#1e293b' : '#f8fafc';
    const textMuted = isLight ? '#64748b' : '#94a3b8';
    const gridColor = isLight ? 'rgba(0,0,0,0.06)' : 'rgba(255,255,255,0.08)';

    // Plugin Angka Permanen pada Bar
    const barDataLabelsPlugin = {
        id: 'barDataLabelsKeamanan',
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
        id: 'doughnutDataLabelsKeamanan',
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

    // 1. Chart Kategori Tamu (Doughnut)
    const ctxTamu = document.getElementById('keamananChartTamu')?.getContext('2d');
    if (ctxTamu && payload.kategoriTamu) {
        new Chart(ctxTamu, {
            type: 'doughnut',
            data: {
                labels: Object.keys(payload.kategoriTamu),
                datasets: [{
                    data: Object.values(payload.kategoriTamu),
                    backgroundColor: ['#3b82f6', '#10b981', '#f59e0b', '#8b5cf6'],
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

    // 2. Chart Patroli Zona (Bar)
    const ctxPat = document.getElementById('keamananChartPatroli')?.getContext('2d');
    if (ctxPat && payload.zonaPatroli) {
        new Chart(ctxPat, {
            type: 'bar',
            data: {
                labels: Object.keys(payload.zonaPatroli),
                datasets: [{
                    data: Object.values(payload.zonaPatroli),
                    backgroundColor: ['rgba(59,130,246,0.85)', 'rgba(16,185,129,0.85)', 'rgba(245,158,11,0.85)', 'rgba(239,68,68,0.85)'],
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
                    x: {
                        grid: { display: false },
                        ticks: {
                            color: textMuted,
                            font: { size: 9 },
                            callback: function (val, index) {
                                const lbl = Object.keys(payload.zonaPatroli)[index] || '';
                                return lbl.length > 14 ? lbl.substring(0, 12) + '...' : lbl;
                            }
                        }
                    },
                    y: { grid: { color: gridColor }, ticks: { color: textMuted, font: { size: 10 } }, beginAtZero: true }
                }
            }
        });
    }

    // 3. Chart Kondisi Keamanan (Doughnut)
    const ctxSts = document.getElementById('keamananChartStatus')?.getContext('2d');
    if (ctxSts && payload.kondisiKeamanan) {
        new Chart(ctxSts, {
            type: 'doughnut',
            data: {
                labels: Object.keys(payload.kondisiKeamanan),
                datasets: [{
                    data: Object.values(payload.kondisiKeamanan),
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
