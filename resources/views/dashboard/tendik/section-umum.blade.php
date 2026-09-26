{{-- Section: Portal Umum Tenaga Kependidikan & Administrasi (Universal untuk seluruh Tendik) --}}

<!-- 1. Quick Stats Grid Universal (4 Cards Maksimal) -->
<div class="dash-stat-grid" style="margin-bottom: 24px; gap: 14px;">
    {{-- Card 1: Siswa Aktif --}}
    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(59, 130, 246, 0.15); color: #3b82f6;">
            <i class="fas fa-user-graduate"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value" style="color: #3b82f6;">{{ number_format($chartsUmum['populasi']['Peserta Didik'] ?? ($stats['total_siswa'] ?? 0), 0, ',', '.') }} Siswa</div>
            <div class="dash-stat-label">Peserta Didik Aktif</div>
            <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 2px;">
                {{ $chartsUmum['genderSiswa']['L'] ?? 0 }} L / {{ $chartsUmum['genderSiswa']['P'] ?? 0 }} P
            </div>
        </div>
    </div>

    {{-- Card 2: Guru & Pendidik --}}
    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(16, 185, 129, 0.15); color: #10b981;">
            <i class="fas fa-chalkboard-user"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value" style="color: #10b981;">{{ $chartsUmum['populasi']['Guru Pendidik'] ?? ($stats['total_guru'] ?? 0) }} Guru</div>
            <div class="dash-stat-label">Tenaga Pendidik</div>
            <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 2px;">
                Beban KBM &amp; Pembelajaran
            </div>
        </div>
    </div>

    {{-- Card 3: Tenaga Kependidikan --}}
    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(99, 102, 241, 0.15); color: var(--primary);">
            <i class="fas fa-users-gear"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value" style="color: var(--primary);">{{ $chartsUmum['populasi']['Tenaga Tendik'] ?? ($stats['total_tendik'] ?? 0) }} Tendik</div>
            <div class="dash-stat-label">Tenaga Kependidikan (TAS)</div>
            <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 2px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="{{ $bagianTugas ?? 'Tenaga Kependidikan' }}">
                {{ Str::limit($bagianTugas ?? 'Tenaga Kependidikan', 28) }}
            </div>
        </div>
    </div>

    {{-- Card 4: Rombongan Belajar --}}
    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b;">
            <i class="fas fa-door-open"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value" style="color: #f59e0b;">{{ $chartsUmum['populasi']['Rombel Belajar'] ?? ($stats['total_rombel'] ?? 0) }} Rombel</div>
            <div class="dash-stat-label">Rombongan Belajar</div>
            <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 2px;">
                Tingkat 10, 11 &amp; 12
            </div>
        </div>
    </div>
</div>

<!-- 2. Section: Visualisasi Statistik & Demografi Satuan Pendidikan -->
<div class="card" style="padding: 20px 22px; margin-bottom: 24px; border-radius: 16px; border: 1px solid var(--border-color); background: var(--card-bg);">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; flex-wrap: wrap; gap: 10px;">
        <div>
            <h3 style="font-size: 1.05rem; font-weight: 800; color: var(--text-color); margin: 0; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-chart-pie text-primary"></i> Statistik &amp; Demografi Satuan Pendidikan
            </h3>
            <p style="font-size: 0.78rem; color: var(--text-muted); margin: 3px 0 0 0;">
                Visualisasi data pokok kependidikan: perbandingan populasi, gender, sebaran jenjang kelas, dan jurusan keahlian.
            </p>
        </div>
        <div style="font-size: 0.76rem; font-weight: 700; color: var(--primary); background: rgba(99,102,241,0.1); padding: 4px 10px; border-radius: 20px; border: 1px solid rgba(99,102,241,0.25);">
            <i class="fas fa-school me-1"></i> Data Pokok Sekolah
        </div>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px;">
        <!-- Chart 1: Komposisi Warga Sekolah (Doughnut) -->
        <div style="background: var(--bg-hover); border-radius: 12px; padding: 16px; border: 1px solid var(--border-color); display: flex; flex-direction: column;">
            <div style="font-size: 0.82rem; font-weight: 700; color: var(--text-color); margin-bottom: 4px; display: flex; align-items: center; gap: 6px;">
                <i class="fas fa-users text-primary"></i> Komposisi Sivitas Sekolah
            </div>
            <div style="font-size: 0.72rem; color: var(--text-muted); margin-bottom: 10px;">Perbandingan Siswa, Guru &amp; Tendik</div>
            <div style="height: 180px; position: relative; flex: 1;">
                <canvas id="chartUmumPopulasi"></canvas>
            </div>
            <div style="display: flex; justify-content: center; gap: 12px; margin-top: 10px; font-size: 0.75rem; border-top: 1px solid var(--border-color); padding-top: 8px; flex-wrap: wrap;">
                <span style="display: inline-flex; align-items: center; gap: 4px;">
                    <span style="width: 8px; height: 8px; border-radius: 50%; background: #3b82f6;"></span>
                    <span>Siswa: <strong>{{ $chartsUmum['populasi']['Peserta Didik'] ?? 0 }}</strong></span>
                </span>
                <span style="display: inline-flex; align-items: center; gap: 4px;">
                    <span style="width: 8px; height: 8px; border-radius: 50%; background: #10b981;"></span>
                    <span>Guru: <strong>{{ $chartsUmum['populasi']['Guru Pendidik'] ?? 0 }}</strong></span>
                </span>
                <span style="display: inline-flex; align-items: center; gap: 4px;">
                    <span style="width: 8px; height: 8px; border-radius: 50%; background: #6366f1;"></span>
                    <span>Tendik: <strong>{{ $chartsUmum['populasi']['Tenaga Tendik'] ?? 0 }}</strong></span>
                </span>
            </div>
        </div>

        <!-- Chart 2: Gender Peserta Didik (Doughnut) -->
        <div style="background: var(--bg-hover); border-radius: 12px; padding: 16px; border: 1px solid var(--border-color); display: flex; flex-direction: column;">
            <div style="font-size: 0.82rem; font-weight: 700; color: var(--text-color); margin-bottom: 4px; display: flex; align-items: center; gap: 6px;">
                <i class="fas fa-venus-mars text-accent"></i> Proporsi Gender Peserta Didik
            </div>
            <div style="font-size: 0.72rem; color: var(--text-muted); margin-bottom: 10px;">Perbandingan Laki-laki vs Perempuan</div>
            <div style="height: 180px; position: relative; flex: 1;">
                <canvas id="chartUmumGender"></canvas>
            </div>
            <div style="display: flex; justify-content: center; gap: 16px; margin-top: 10px; font-size: 0.78rem; border-top: 1px solid var(--border-color); padding-top: 8px;">
                <span style="display: inline-flex; align-items: center; gap: 6px;">
                    <span style="width: 10px; height: 10px; border-radius: 50%; background: #3b82f6;"></span>
                    <span>Laki-laki: <strong>{{ $chartsUmum['genderSiswa']['L'] ?? 0 }}</strong></span>
                </span>
                <span style="display: inline-flex; align-items: center; gap: 6px;">
                    <span style="width: 10px; height: 10px; border-radius: 50%; background: #ec4899;"></span>
                    <span>Perempuan: <strong>{{ $chartsUmum['genderSiswa']['P'] ?? 0 }}</strong></span>
                </span>
            </div>
        </div>

        <!-- Chart 3: Peserta Didik per Tingkat (Bar) -->
        <div style="background: var(--bg-hover); border-radius: 12px; padding: 16px; border: 1px solid var(--border-color); display: flex; flex-direction: column;">
            <div style="font-size: 0.82rem; font-weight: 700; color: var(--text-color); margin-bottom: 4px; display: flex; align-items: center; gap: 6px;">
                <i class="fas fa-layer-group text-warning"></i> Sebaran Siswa per Tingkat
            </div>
            <div style="font-size: 0.72rem; color: var(--text-muted); margin-bottom: 10px;">Distribusi populasi kelas 10, 11, dan 12</div>
            <div style="height: 180px; position: relative; flex: 1;">
                <canvas id="chartUmumTingkat"></canvas>
            </div>
        </div>

        <!-- Chart 4: Rombel per Program / Jurusan (Horizontal Bar) -->
        <div style="background: var(--bg-hover); border-radius: 12px; padding: 16px; border: 1px solid var(--border-color); display: flex; flex-direction: column;">
            <div style="font-size: 0.82rem; font-weight: 700; color: var(--text-color); margin-bottom: 4px; display: flex; align-items: center; gap: 6px;">
                <i class="fas fa-graduation-cap text-success"></i> Rombongan Belajar per Jurusan
            </div>
            <div style="font-size: 0.72rem; color: var(--text-muted); margin-bottom: 10px;">Distribusi kelas kompetensi keahlian</div>
            <div style="height: 180px; position: relative; flex: 1;">
                <canvas id="chartUmumJurusan"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- 3. Quick Links Modul Tendik Universal -->
<div class="kepegawaian-quick-grid" style="margin-bottom: 24px;">
    <a href="{{ route('dashboard.tendik.target.index') }}"
        class="kepegawaian-quick-btn" style="--quick-color: #6366f1;" title="Target &amp; Capaian Kinerja">
        <div class="kepegawaian-quick-icon" style="background: rgba(99, 102, 241, 0.15); color: #6366f1;">
            <i class="fas fa-bullseye"></i>
        </div>
        <span class="kepegawaian-quick-label">Target &amp; Capaian</span>
    </a>

    <a href="{{ route('dashboard.tendik.aktivitas.index') }}"
        class="kepegawaian-quick-btn" style="--quick-color: #10b981;" title="Input Aktivitas Harian">
        <div class="kepegawaian-quick-icon" style="background: rgba(16, 185, 129, 0.15); color: #10b981;">
            <i class="fas fa-clipboard-check"></i>
        </div>
        <span class="kepegawaian-quick-label">Aktivitas Harian</span>
    </a>

    <a href="{{ route('dashboard.tendik.laporan.index') }}"
        class="kepegawaian-quick-btn" style="--quick-color: #ec4899;" title="Laporan Kinerja Tendik">
        <div class="kepegawaian-quick-icon" style="background: rgba(236, 72, 153, 0.15); color: #ec4899;">
            <i class="fas fa-file-invoice"></i>
        </div>
        <span class="kepegawaian-quick-label">Laporan Kinerja</span>
    </a>

    <a href="{{ route('dashboard.tendik-aktif.index') }}"
        class="kepegawaian-quick-btn" style="--quick-color: #3b82f6;" title="Direktori Data Tendik">
        <div class="kepegawaian-quick-icon" style="background: rgba(59, 130, 246, 0.15); color: #3b82f6;">
            <i class="fas fa-id-badge"></i>
        </div>
        <span class="kepegawaian-quick-label">Data Tendik</span>
    </a>

    <a href="{{ route('dashboard.guru-aktif.index') }}"
        class="kepegawaian-quick-btn" style="--quick-color: #f59e0b;" title="Direktori Data Guru">
        <div class="kepegawaian-quick-icon" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b;">
            <i class="fas fa-chalkboard-user"></i>
        </div>
        <span class="kepegawaian-quick-label">Data Guru</span>
    </a>

    <a href="{{ route('dashboard.peserta-didik-aktif.index') }}"
        class="kepegawaian-quick-btn" style="--quick-color: #14b8a6;" title="Data Pokok Siswa">
        <div class="kepegawaian-quick-icon" style="background: rgba(20, 184, 166, 0.15); color: #14b8a6;">
            <i class="fas fa-user-graduate"></i>
        </div>
        <span class="kepegawaian-quick-label">Buku Induk Siswa</span>
    </a>
</div>

<!-- Payload JSON Data Chart Umum untuk JS -->
<script type="application/json" id="sectionUmumChartPayload">
    {!! json_encode($chartsUmum ?? [], JSON_UNESCAPED_UNICODE) !!}
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const payloadEl = document.getElementById('sectionUmumChartPayload');
    if (!payloadEl || typeof Chart === 'undefined') return;

    let payload = {};
    try {
        payload = JSON.parse(payloadEl.textContent);
    } catch (e) {
        console.error('Gagal parsing sectionUmumChartPayload', e);
        return;
    }

    const isLight = document.documentElement.getAttribute('data-theme') === 'light';
    const textColor = isLight ? '#1e293b' : '#f8fafc';
    const textMuted = isLight ? '#64748b' : '#94a3b8';
    const gridColor = isLight ? 'rgba(0,0,0,0.06)' : 'rgba(255,255,255,0.08)';

    // Plugin Angka Permanen pada Bar
    const barDataLabelsPlugin = {
        id: 'barDataLabelsUmum',
        afterDatasetsDraw(chart) {
            const { ctx } = chart;
            const isHorizontal = chart.config.options.indexAxis === 'y';
            ctx.save();
            chart.data.datasets.forEach((dataset, dIdx) => {
                const meta = chart.getDatasetMeta(dIdx);
                if (meta.hidden) return;
                meta.data.forEach((bar, index) => {
                    const val = dataset.data[index];
                    if (val !== undefined && val !== null && val > 0) {
                        ctx.fillStyle = textColor;
                        ctx.font = 'bold 10px Inter, system-ui, sans-serif';
                        if (isHorizontal) {
                            ctx.textAlign = 'left';
                            ctx.textBaseline = 'middle';
                            ctx.fillText(Number(val).toLocaleString('id-ID'), bar.x + 4, bar.y);
                        } else {
                            ctx.textAlign = 'center';
                            ctx.textBaseline = 'bottom';
                            ctx.fillText(Number(val).toLocaleString('id-ID'), bar.x, bar.y - 3);
                        }
                    }
                });
            });
            ctx.restore();
        }
    };

    // Plugin Doughnut Data Labels Permanen di Segmen Lingkaran (Angka & Persen)
    const doughnutDataLabelsPlugin = {
        id: 'doughnutDataLabelsUmum',
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

            // Total di Tengah Lingkaran Donat
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

    // 1. Chart Populasi (Siswa, Guru, Tendik)
    const ctxPop = document.getElementById('chartUmumPopulasi')?.getContext('2d');
    if (ctxPop && payload.populasi) {
        new Chart(ctxPop, {
            type: 'doughnut',
            data: {
                labels: ['Siswa', 'Guru', 'Tendik'],
                datasets: [{
                    data: [
                        payload.populasi['Peserta Didik'] || 0,
                        payload.populasi['Guru Pendidik'] || 0,
                        payload.populasi['Tenaga Tendik'] || 0
                    ],
                    backgroundColor: ['#3b82f6', '#10b981', '#6366f1'],
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

    // 2. Chart Gender Siswa
    const ctxGen = document.getElementById('chartUmumGender')?.getContext('2d');
    if (ctxGen && payload.genderSiswa) {
        new Chart(ctxGen, {
            type: 'doughnut',
            data: {
                labels: ['Laki-laki', 'Perempuan'],
                datasets: [{
                    data: [payload.genderSiswa.L || 0, payload.genderSiswa.P || 0],
                    backgroundColor: ['#3b82f6', '#ec4899'],
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

    // 3. Chart Siswa per Tingkat (Bar)
    const ctxTkt = document.getElementById('chartUmumTingkat')?.getContext('2d');
    if (ctxTkt && payload.siswaTingkat) {
        const labels = Object.keys(payload.siswaTingkat).map(k => 'Kelas ' + k);
        const data = Object.values(payload.siswaTingkat);
        new Chart(ctxTkt, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: ['rgba(59,130,246,0.85)', 'rgba(16,185,129,0.85)', 'rgba(245,158,11,0.85)'],
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

    // 4. Chart Jurusan Rombel (Horizontal Bar)
    const ctxJur = document.getElementById('chartUmumJurusan')?.getContext('2d');
    if (ctxJur && payload.jurusanRombel) {
        const labels = Object.keys(payload.jurusanRombel);
        const data = Object.values(payload.jurusanRombel);
        new Chart(ctxJur, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: 'rgba(99,102,241,0.85)',
                    borderRadius: 4,
                    maxBarThickness: 18
                }]
            },
            plugins: [barDataLabelsPlugin],
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { color: gridColor }, ticks: { color: textMuted, font: { size: 10 } }, beginAtZero: true },
                    y: {
                        grid: { display: false },
                        ticks: {
                            color: textMuted,
                            font: { size: 9 },
                            callback: function (val, index) {
                                const lbl = labels[index] || '';
                                return lbl.length > 20 ? lbl.substring(0, 18) + '...' : lbl;
                            }
                        }
                    }
                }
            }
        });
    }
});
</script>
