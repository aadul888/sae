{{-- Section Dashboard: Fasilitas & Penjaga Sekolah --}}

<!-- 1. Akses Cepat Menu Penjaga (Responsive Grid Seimbang & Genap) -->
<div class="kepegawaian-quick-grid">
    <a href="{{ route('dashboard.penjaga.kebersihan.index') }}"
        class="kepegawaian-quick-btn" style="--quick-color: #10b981;" title="Checklist Kebersihan Harian">
        <div class="kepegawaian-quick-icon" style="background: rgba(16, 185, 129, 0.15); color: #10b981;">
            <i class="fas fa-broom"></i>
        </div>
        <span class="kepegawaian-quick-label">Kebersihan</span>
    </a>

    <a href="{{ route('dashboard.penjaga.ronda.index') }}"
        class="kepegawaian-quick-btn" style="--quick-color: #3b82f6;" title="Buku Jaga & Ronda Malam">
        <div class="kepegawaian-quick-icon" style="background: rgba(59, 130, 246, 0.15); color: #3b82f6;">
            <i class="fas fa-moon"></i>
        </div>
        <span class="kepegawaian-quick-label">Ronda Malam</span>
    </a>

    <a href="{{ route('dashboard.identitas-sekolah.index') }}"
        class="kepegawaian-quick-btn" style="--quick-color: #f59e0b;" title="Fasilitas & Denah Gedung">
        <div class="kepegawaian-quick-icon" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b;">
            <i class="fas fa-building"></i>
        </div>
        <span class="kepegawaian-quick-label">Fasilitas</span>
    </a>

    <a href="{{ route('dashboard.tendik.target.index', ['bidang' => 'penjaga']) }}"
        class="kepegawaian-quick-btn" style="--quick-color: #6366f1;" title="Target &amp; Capaian Kinerja">
        <div class="kepegawaian-quick-icon" style="background: rgba(99, 102, 241, 0.15); color: #6366f1;">
            <i class="fas fa-bullseye"></i>
        </div>
        <span class="kepegawaian-quick-label">Target Capaian</span>
    </a>

    <a href="{{ route('dashboard.tendik.aktivitas.index', ['bidang' => 'penjaga']) }}"
        class="kepegawaian-quick-btn" style="--quick-color: #06b6d4;" title="Input Log Aktivitas Harian">
        <div class="kepegawaian-quick-icon" style="background: rgba(6, 182, 212, 0.15); color: #06b6d4;">
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
        <div class="dash-stat-icon" style="background: rgba(16, 185, 129, 0.15); color: #10b981;">
            <i class="fas fa-broom"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">Bersih &amp; Rapi</div>
            <div class="dash-stat-label">Kondisi Fasilitas</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(59, 130, 246, 0.15); color: #3b82f6;">
            <i class="fas fa-door-closed"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">Terkunci Aman</div>
            <div class="dash-stat-label">Akses Gedung &amp; Pintu</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b;">
            <i class="fas fa-faucet-drip"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">Lancar</div>
            <div class="dash-stat-label">Sanitasi &amp; Air Bersih</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(99, 102, 241, 0.15); color: var(--primary);">
            <i class="fas fa-lightbulb"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">Terkontrol</div>
            <div class="dash-stat-label">Penerangan Malam</div>
        </div>
    </div>
</div>

<!-- 3. Section: Visualisasi Statistik Fasilitas & Kontrol Penjaga Sekolah -->
<div class="card" style="padding: 20px 22px; margin-bottom: 24px; border-radius: 16px; border: 1px solid var(--border-color); background: var(--card-bg);">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; flex-wrap: wrap; gap: 10px;">
        <div>
            <h3 style="font-size: 1.05rem; font-weight: 800; color: var(--text-color); margin: 0; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-chart-pie text-primary"></i> Statistik Pemeliharaan &amp; Kontrol Fasilitas
            </h3>
            <p style="font-size: 0.78rem; color: var(--text-muted); margin: 3px 0 0 0;">
                Visualisasi pemantauan kebersihan area, kontrol keamanan malam, dan kesiapan operasional fasilitas.
            </p>
        </div>
        <div style="font-size: 0.76rem; font-weight: 700; color: #84cc16; background: rgba(132,204,22,0.1); padding: 4px 10px; border-radius: 20px; border: 1px solid rgba(132,204,22,0.25);">
            <i class="fas fa-broom me-1"></i> Kebersihan &amp; Ketertiban
        </div>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px;">
        <!-- Chart 1: Area Kebersihan (Bar) -->
        <div style="background: var(--bg-hover); border-radius: 12px; padding: 16px; border: 1px solid var(--border-color); display: flex; flex-direction: column;">
            <div style="font-size: 0.82rem; font-weight: 700; color: var(--text-color); margin-bottom: 4px; display: flex; align-items: center; gap: 6px;">
                <i class="fas fa-broom text-success"></i> Area Pembersihan Rutin
            </div>
            <div style="font-size: 0.72rem; color: var(--text-muted); margin-bottom: 10px;">Kelas, selasar, toilet, &amp; halaman</div>
            <div style="height: 180px; position: relative; flex: 1;">
                <canvas id="penjagaChartArea"></canvas>
            </div>
        </div>

        <!-- Chart 2: Status Kontrol Malam (Doughnut) -->
        <div style="background: var(--bg-hover); border-radius: 12px; padding: 16px; border: 1px solid var(--border-color); display: flex; flex-direction: column;">
            <div style="font-size: 0.82rem; font-weight: 700; color: var(--text-color); margin-bottom: 4px; display: flex; align-items: center; gap: 6px;">
                <i class="fas fa-moon text-primary"></i> Kontrol Ronda Malam
            </div>
            <div style="font-size: 0.72rem; color: var(--text-muted); margin-bottom: 10px;">Pintu, jendela, lampu &amp; gerbang</div>
            <div style="height: 180px; position: relative; flex: 1;">
                <canvas id="penjagaChartMalam"></canvas>
            </div>
        </div>

        <!-- Chart 3: Kondisi Fasilitas (Doughnut) -->
        <div style="background: var(--bg-hover); border-radius: 12px; padding: 16px; border: 1px solid var(--border-color); display: flex; flex-direction: column;">
            <div style="font-size: 0.82rem; font-weight: 700; color: var(--text-color); margin-bottom: 4px; display: flex; align-items: center; gap: 6px;">
                <i class="fas fa-check-double text-warning"></i> Kondisi Fisik Fasilitas
            </div>
            <div style="font-size: 0.72rem; color: var(--text-muted); margin-bottom: 10px;">Bersih / laik vs perlu pemeliharaan</div>
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

<script>
document.addEventListener('DOMContentLoaded', function () {
    const payloadEl = document.getElementById('sectionPenjagaChartPayload');
    if (!payloadEl || typeof Chart === 'undefined') return;

    let payload = {};
    try {
        payload = JSON.parse(payloadEl.textContent);
    } catch (e) {
        console.error('Gagal parsing sectionPenjagaChartPayload', e);
        return;
    }

    const isLight = document.documentElement.getAttribute('data-theme') === 'light';
    const textColor = isLight ? '#1e293b' : '#f8fafc';
    const textMuted = isLight ? '#64748b' : '#94a3b8';
    const gridColor = isLight ? 'rgba(0,0,0,0.06)' : 'rgba(255,255,255,0.08)';

    // Plugin Angka Permanen pada Bar
    const barDataLabelsPlugin = {
        id: 'barDataLabelsPenjaga',
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
        id: 'doughnutDataLabelsPenjaga',
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

    // 1. Chart Area Kebersihan (Bar)
    const ctxArea = document.getElementById('penjagaChartArea')?.getContext('2d');
    if (ctxArea && payload.areaKebersihan) {
        new Chart(ctxArea, {
            type: 'bar',
            data: {
                labels: Object.keys(payload.areaKebersihan),
                datasets: [{
                    data: Object.values(payload.areaKebersihan),
                    backgroundColor: ['rgba(16,185,129,0.85)', 'rgba(59,130,246,0.85)', 'rgba(245,158,11,0.85)', 'rgba(132,204,22,0.85)'],
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
                                const lbl = Object.keys(payload.areaKebersihan)[index] || '';
                                return lbl.length > 14 ? lbl.substring(0, 12) + '...' : lbl;
                            }
                        }
                    },
                    y: { grid: { color: gridColor }, ticks: { color: textMuted, font: { size: 10 } }, beginAtZero: true }
                }
            }
        });
    }

    // 2. Chart Kontrol Malam (Doughnut)
    const ctxMlm = document.getElementById('penjagaChartMalam')?.getContext('2d');
    if (ctxMlm && payload.statusKontrolMalam) {
        new Chart(ctxMlm, {
            type: 'doughnut',
            data: {
                labels: Object.keys(payload.statusKontrolMalam),
                datasets: [{
                    data: Object.values(payload.statusKontrolMalam),
                    backgroundColor: ['#3b82f6', '#f59e0b', '#10b981'],
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

    // 3. Chart Kondisi Fasilitas (Doughnut)
    const ctxKon = document.getElementById('penjagaChartKondisi')?.getContext('2d');
    if (ctxKon && payload.kondisiFasilitas) {
        new Chart(ctxKon, {
            type: 'doughnut',
            data: {
                labels: Object.keys(payload.kondisiFasilitas),
                datasets: [{
                    data: Object.values(payload.kondisiFasilitas),
                    backgroundColor: ['#10b981', '#ef4444'],
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
