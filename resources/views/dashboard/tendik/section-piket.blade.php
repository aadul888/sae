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

<script>
document.addEventListener('DOMContentLoaded', function () {
    const payloadEl = document.getElementById('sectionPiketChartPayload');
    if (!payloadEl || typeof Chart === 'undefined') return;

    let payload = {};
    try {
        payload = JSON.parse(payloadEl.textContent);
    } catch (e) {
        console.error('Gagal parsing sectionPiketChartPayload', e);
        return;
    }

    const isLight = document.documentElement.getAttribute('data-theme') === 'light';
    const textColor = isLight ? '#1e293b' : '#f8fafc';
    const textMuted = isLight ? '#64748b' : '#94a3b8';
    const gridColor = isLight ? 'rgba(0,0,0,0.06)' : 'rgba(255,255,255,0.08)';

    // Plugin Angka Permanen pada Bar
    const barDataLabelsPlugin = {
        id: 'barDataLabelsPiket',
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
        id: 'doughnutDataLabelsPiket',
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

    // 1. Chart Alasan e-Izin (Doughnut)
    const ctxIzin = document.getElementById('piketChartIzin')?.getContext('2d');
    if (ctxIzin && payload.alasanIzin) {
        new Chart(ctxIzin, {
            type: 'doughnut',
            data: {
                labels: Object.keys(payload.alasanIzin),
                datasets: [{
                    data: Object.values(payload.alasanIzin),
                    backgroundColor: ['#ef4444', '#f59e0b', '#3b82f6'],
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

    // 2. Chart Durasi Terlambat (Bar)
    const ctxTer = document.getElementById('piketChartTerlambat')?.getContext('2d');
    if (ctxTer && payload.kategoriTerlambat) {
        new Chart(ctxTer, {
            type: 'bar',
            data: {
                labels: Object.keys(payload.kategoriTerlambat),
                datasets: [{
                    data: Object.values(payload.kategoriTerlambat),
                    backgroundColor: ['rgba(245,158,11,0.85)', 'rgba(239,68,68,0.85)', 'rgba(139,92,246,0.85)'],
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

    // 3. Chart Keterisian Jurnal KBM (Doughnut)
    const ctxJur = document.getElementById('piketChartJurnal')?.getContext('2d');
    if (ctxJur && payload.keterisianJurnal) {
        new Chart(ctxJur, {
            type: 'doughnut',
            data: {
                labels: Object.keys(payload.keterisianJurnal),
                datasets: [{
                    data: Object.values(payload.keterisianJurnal),
                    backgroundColor: ['#10b981', '#64748b'],
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
