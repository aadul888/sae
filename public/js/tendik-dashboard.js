/**
 * SAE — Dashboard Tendik Script (Multi-Section & Kinerja Charts)
 */
(function () {
function initTendikKinerjaAktivitasCharts() {
    if (typeof Chart === 'undefined') return;

    document.querySelectorAll('script[type="application/json"][id^="payloadAktivitas_"]').forEach(function (payloadEl) {
        const payloadId = payloadEl.id;
        const bKey = payloadId.replace('payloadAktivitas_', '');

        let multiPeriode = {};
        try {
            multiPeriode = JSON.parse(payloadEl.textContent);
        } catch (e) {
            console.error('Gagal parsing ' + payloadId, e);
            return;
        }

        const isLight = document.documentElement.getAttribute('data-theme') === 'light';
        const textColor = isLight ? '#1e293b' : '#f8fafc';
        const textMuted = isLight ? '#64748b' : '#94a3b8';
        const gridColor = isLight ? 'rgba(0,0,0,0.06)' : 'rgba(255,255,255,0.08)';

        const chartId = 'chartAktivitas_' + bKey;
        const btnGroupId = 'btnGroupPeriode_' + bKey;
        const selectDropdownId = 'selectDropdownPeriode_' + bKey;
        const targetId = 'valTarget_' + bKey;
        const selesaiId = 'valSelesai_' + bKey;
        const prosesId = 'valProses_' + bKey;
        const persenId = 'valPersen_' + bKey;
        const descId = 'labelDeskripsi_' + bKey;

        let chartAktivitas = null;

        function renderAktivitasPeriode(pKey) {
            const pData = multiPeriode[pKey] || multiPeriode['hari'] || {};
            const canvasEl = document.getElementById(chartId);
            if (!canvasEl) return;
            const ctxAct = canvasEl.getContext('2d');
            if (!ctxAct) return;

            const tTarget = pData.total_target || 0;
            const tSelesai = pData.total_selesai || 0;
            const tProses = (pData.proses || []).reduce(function (a, b) { return a + b; }, 0);
            const pct = tTarget > 0 ? Math.round((tSelesai / tTarget) * 100) : 0;

            const elTarget = document.getElementById(targetId);
            const elSelesai = document.getElementById(selesaiId);
            const elProses = document.getElementById(prosesId);
            const elPersen = document.getElementById(persenId);
            const elDesk = document.getElementById(descId);

            if (elTarget) elTarget.textContent = tTarget.toLocaleString('id-ID');
            if (elSelesai) elSelesai.textContent = tSelesai.toLocaleString('id-ID');
            if (elProses) elProses.textContent = tProses.toLocaleString('id-ID');
            if (elPersen) elPersen.textContent = pct + '%';
            if (elDesk && pData.label) elDesk.textContent = 'Monitoring progres aktivitas dan target pekerjaan pada periode ' + pData.label + '.';

            if (chartAktivitas) {
                chartAktivitas.destroy();
            }

            chartAktivitas = new Chart(ctxAct, {
                data: {
                    labels: pData.labels || [],
                    datasets: [
                        {
                            type: 'line',
                            label: 'Target Pekerjaan',
                            data: pData.target || [],
                            borderColor: '#6366f1',
                            borderWidth: 2,
                            borderDash: [5, 4],
                            pointRadius: 3,
                            pointBackgroundColor: '#6366f1',
                            fill: false,
                            tension: 0.25,
                            order: 1
                        },
                        {
                            type: 'bar',
                            label: 'Selesai Dikerjakan',
                            data: pData.selesai || [],
                            backgroundColor: 'rgba(16, 185, 129, 0.85)',
                            borderColor: '#10b981',
                            borderWidth: 1,
                            borderRadius: 5,
                            maxBarThickness: 28,
                            order: 2
                        },
                        {
                            type: 'bar',
                            label: 'Dalam Proses',
                            data: pData.proses || [],
                            backgroundColor: 'rgba(245, 158, 11, 0.8)',
                            borderColor: '#f59e0b',
                            borderWidth: 1,
                            borderRadius: 5,
                            maxBarThickness: 28,
                            order: 3
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: {
                            position: 'top',
                            align: 'end',
                            labels: {
                                color: textColor,
                                font: { size: 11, weight: '600' },
                                usePointStyle: true,
                                boxWidth: 8
                            }
                        },
                        tooltip: {
                            padding: 10,
                            backgroundColor: isLight ? 'rgba(15, 23, 42, 0.9)' : 'rgba(30, 41, 59, 0.95)',
                            titleColor: '#ffffff',
                            bodyColor: '#ffffff',
                            cornerRadius: 8
                        }
                    },
                    scales: {
                        x: {
                            grid: { color: gridColor },
                            ticks: { color: textMuted, font: { size: 10 } }
                        },
                        y: {
                            grid: { color: gridColor },
                            ticks: { color: textMuted, font: { size: 10 }, precision: 0 },
                            beginAtZero: true
                        }
                    }
                }
            });
        }

        renderAktivitasPeriode('hari');

        const btnGroup = document.getElementById(btnGroupId);
        const selectDropdown = document.getElementById(selectDropdownId);

        if (btnGroup) {
            btnGroup.addEventListener('click', function (e) {
                const btn = e.target.closest('.btn-periode-pill');
                if (!btn) return;
                btnGroup.querySelectorAll('.btn-periode-pill').forEach(function (b) {
                    b.classList.remove('active');
                    b.style.background = 'transparent';
                    b.style.color = 'var(--text-muted)';
                });
                btn.classList.add('active');
                btn.style.background = 'var(--primary)';
                btn.style.color = '#fff';

                const p = btn.getAttribute('data-period');
                if (selectDropdown) selectDropdown.value = p;
                renderAktivitasPeriode(p);
            });
        }

        if (selectDropdown) {
            selectDropdown.addEventListener('change', function () {
                const p = this.value;
                if (btnGroup) {
                    btnGroup.querySelectorAll('.btn-periode-pill').forEach(function (b) {
                        const match = b.getAttribute('data-period') === p;
                        b.classList.toggle('active', match);
                        b.style.background = match ? 'var(--primary)' : 'transparent';
                        b.style.color = match ? '#fff' : 'var(--text-muted)';
                    });
                }
                renderAktivitasPeriode(p);
            });
        }
    });
}

function initTendik_keamanan() {
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
}

function initTendik_kepala_tas() {
const payloadEl = document.getElementById('sectionKepalaTasChartPayload');
    if (!payloadEl || typeof Chart === 'undefined') return;

    let payload = {};
    try {
        payload = JSON.parse(payloadEl.textContent);
    } catch (e) {
        console.error('Gagal parsing sectionKepalaTasChartPayload', e);
        return;
    }

    const isLight = document.documentElement.getAttribute('data-theme') === 'light';
    const textColor = isLight ? '#1e293b' : '#f8fafc';
    const textMuted = isLight ? '#64748b' : '#94a3b8';
    const gridColor = isLight ? 'rgba(0,0,0,0.06)' : 'rgba(255,255,255,0.08)';

    // Plugin Angka Permanen pada Bar
    const barDataLabelsPlugin = {
        id: 'barDataLabelsTas',
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
        id: 'doughnutDataLabelsTas',
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

    // 1. Chart Distribusi Tendik (Bar)
    const ctxDis = document.getElementById('tasChartDistribusi')?.getContext('2d');
    if (ctxDis && payload.distribusiTendik) {
        new Chart(ctxDis, {
            type: 'bar',
            data: {
                labels: Object.keys(payload.distribusiTendik),
                datasets: [{
                    data: Object.values(payload.distribusiTendik),
                    backgroundColor: ['rgba(59,130,246,0.85)', 'rgba(16,185,129,0.85)', 'rgba(6,182,212,0.85)', 'rgba(245,158,11,0.85)', 'rgba(99,102,241,0.85)', 'rgba(236,72,153,0.85)', 'rgba(139,92,246,0.85)', 'rgba(239,68,68,0.85)', 'rgba(132,204,22,0.85)'],
                    borderRadius: 6,
                    maxBarThickness: 30
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
                                const lbl = Object.keys(payload.distribusiTendik)[index] || '';
                                return lbl.length > 12 ? lbl.substring(0, 10) + '...' : lbl;
                            }
                        }
                    },
                    y: { grid: { color: gridColor }, ticks: { color: textMuted, font: { size: 10 } }, beginAtZero: true }
                }
            }
        });
    }

    // 2. Chart Evaluasi Kinerja (Doughnut)
    const ctxKin = document.getElementById('tasChartKinerja')?.getContext('2d');
    if (ctxKin && payload.statusKinerja) {
        new Chart(ctxKin, {
            type: 'doughnut',
            data: {
                labels: Object.keys(payload.statusKinerja),
                datasets: [{
                    data: Object.values(payload.statusKinerja),
                    backgroundColor: ['#10b981', '#3b82f6', '#f59e0b'],
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

    // 3. Chart Komposisi GTK (Doughnut)
    const ctxKomp = document.getElementById('tasChartKomposisi')?.getContext('2d');
    if (ctxKomp && payload.komposisiGtk) {
        new Chart(ctxKomp, {
            type: 'doughnut',
            data: {
                labels: Object.keys(payload.komposisiGtk),
                datasets: [{
                    data: Object.values(payload.komposisiGtk),
                    backgroundColor: ['#3b82f6', '#10b981'],
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
}

function initTendik_kepegawaian() {
const payloadEl = document.getElementById('sectionKepegawaianChartPayload');
    if (!payloadEl || typeof Chart === 'undefined') return;

    let payload = {};
    try {
        payload = JSON.parse(payloadEl.textContent);
    } catch (e) {
        console.error('Gagal parsing sectionKepegawaianChartPayload', e);
        return;
    }

    const isLight = document.documentElement.getAttribute('data-theme') === 'light';
    const textColor = isLight ? '#1e293b' : '#f8fafc';
    const textMuted = isLight ? '#64748b' : '#94a3b8';
    const gridColor = isLight ? 'rgba(0,0,0,0.06)' : 'rgba(255,255,255,0.08)';

    // Plugin 1: Angka Permanen di Atas Batang Diagram Bar
    const barDataLabelsPlugin = {
        id: 'barDataLabelsKepegawaian',
        afterDatasetsDraw(chart) {
            const { ctx, data } = chart;
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

    // Plugin 2: Angka Permanen di Segmen Lingkaran & Total Tengah Donat (Persis Demografi Peserta Didik)
    const doughnutDataLabelsPlugin = {
        id: 'doughnutDataLabelsKepegawaian',
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

            // Total GTK di Tengah Lingkaran Donat
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

    const demo = payload.demografi || {};

    // 1. Chart Jenis Kelamin (Doughnut dengan Total di Tengah)
    const ctxJK = document.getElementById('gtkChartJenisKelamin')?.getContext('2d');
    if (ctxJK && demo.jenisKelamin) {
        new Chart(ctxJK, {
            type: 'doughnut',
            data: {
                labels: ['Laki-laki', 'Perempuan'],
                datasets: [{
                    data: [demo.jenisKelamin.L || 0, demo.jenisKelamin.P || 0],
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

    // 2. Chart Pendidikan (Bar dengan Angka di Atas Batang)
    const ctxPend = document.getElementById('gtkChartPendidikan')?.getContext('2d');
    if (ctxPend && demo.pendidikan) {
        new Chart(ctxPend, {
            type: 'bar',
            data: {
                labels: Object.keys(demo.pendidikan),
                datasets: [{
                    label: 'Jumlah Personel',
                    data: Object.values(demo.pendidikan),
                    backgroundColor: '#6366f1',
                    borderRadius: 6,
                    maxBarThickness: 28
                }]
            },
            plugins: [barDataLabelsPlugin],
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                layout: { padding: { top: 14 } },
                scales: {
                    x: { grid: { display: false }, ticks: { color: textMuted, font: { size: 10 } } },
                    y: { beginAtZero: true, grid: { color: gridColor }, ticks: { color: textMuted, font: { size: 10 }, precision: 0 } }
                }
            }
        });
    }

    // 3. Chart Jenis PTK (Horizontal Bar dengan Angka di Kanan)
    const ctxJenis = document.getElementById('gtkChartJenisPtk')?.getContext('2d');
    if (ctxJenis && demo.jenisPtk) {
        new Chart(ctxJenis, {
            type: 'bar',
            data: {
                labels: Object.keys(demo.jenisPtk),
                datasets: [{
                    label: 'Jumlah',
                    data: Object.values(demo.jenisPtk),
                    backgroundColor: ['#10b981', '#3b82f6', '#f59e0b'],
                    borderRadius: 6,
                    maxBarThickness: 22
                }]
            },
            plugins: [barDataLabelsPlugin],
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                layout: { padding: { right: 24 } },
                scales: {
                    x: { beginAtZero: true, grid: { color: gridColor }, ticks: { color: textMuted, font: { size: 10 }, precision: 0 } },
                    y: { grid: { display: false }, ticks: { color: textMuted, font: { size: 10 } } }
                }
            }
        });
    }

    // 4. Chart Status Kepegawaian (Doughnut dengan Total di Tengah)
    const ctxStatus = document.getElementById('gtkChartStatusKepegawaian')?.getContext('2d');
    if (ctxStatus && demo.statusKepegawaian) {
        const sLabels = Object.keys(demo.statusKepegawaian);
        const sData = Object.values(demo.statusKepegawaian);
        const colors = ['#10b981', '#3b82f6', '#f59e0b', '#8b5cf6', '#06b6d4', '#ef4444'];
        new Chart(ctxStatus, {
            type: 'doughnut',
            data: {
                labels: sLabels,
                datasets: [{
                    data: sData,
                    backgroundColor: colors.slice(0, sData.length),
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

    // 5. Chart Aktivitas Multi-Periode Interaktif
    const multiPeriode = payload.multiPeriode || {};
    let chartAktivitas = null;

    function renderAktivitasPeriode(pKey) {
        const pData = multiPeriode[pKey] || multiPeriode['hari'] || {};
        const ctxAct = document.getElementById('gtkChartAktivitasPeriode')?.getContext('2d');
        if (!ctxAct) return;

        // Update Stat Strip
        const tTarget = pData.total_target || 0;
        const tSelesai = pData.total_selesai || 0;
        const tProses = (pData.proses || []).reduce((a, b) => a + b, 0);
        const pct = tTarget > 0 ? Math.round((tSelesai / tTarget) * 100) : 0;

        const elTarget = document.getElementById('valPeriodeTarget');
        const elSelesai = document.getElementById('valPeriodeSelesai');
        const elProses = document.getElementById('valPeriodeProses');
        const elPersen = document.getElementById('valPeriodePersen');
        const elDesk = document.getElementById('labelAktivitasPeriodeDeskripsi');

        if (elTarget) elTarget.textContent = Number(tTarget).toLocaleString('id-ID') + ' Tugas';
        if (elSelesai) elSelesai.textContent = Number(tSelesai).toLocaleString('id-ID');
        if (elProses) elProses.textContent = Number(tProses).toLocaleString('id-ID');
        if (elPersen) elPersen.textContent = pct + '%';
        if (elDesk && pData.label) elDesk.textContent = 'Monitoring progres aktivitas dan target pekerjaan pada periode ' + pData.label + '.';

        if (chartAktivitas) {
            chartAktivitas.destroy();
        }

        chartAktivitas = new Chart(ctxAct, {
            type: 'bar',
            data: {
                labels: pData.labels || [],
                datasets: [
                    {
                        type: 'line',
                        label: 'Target Pekerjaan',
                        data: pData.target || [],
                        borderColor: '#6366f1',
                        backgroundColor: 'transparent',
                        borderWidth: 2,
                        borderDash: [5, 5],
                        pointRadius: 3,
                        tension: 0.3,
                        order: 1
                    },
                    {
                        type: 'bar',
                        label: 'Tugas Selesai',
                        data: pData.selesai || [],
                        backgroundColor: '#10b981',
                        borderRadius: 5,
                        maxBarThickness: 26,
                        order: 2
                    },
                    {
                        type: 'bar',
                        label: 'Sedang Proses',
                        data: pData.proses || [],
                        backgroundColor: '#f59e0b',
                        borderRadius: 5,
                        maxBarThickness: 26,
                        order: 3
                    }
                ]
            },
            plugins: [barDataLabelsPlugin],
            options: {
                responsive: true,
                maintainAspectRatio: false,
                layout: { padding: { top: 14 } },
                plugins: {
                    legend: {
                        position: 'top',
                        align: 'end',
                        labels: {
                            color: textMuted,
                            boxWidth: 10,
                            padding: 10,
                            font: { size: 10, weight: '600' }
                        }
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        padding: 8,
                        backgroundColor: 'rgba(15, 23, 42, 0.9)'
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { color: textMuted, font: { size: 10 } }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: gridColor },
                        ticks: { color: textMuted, font: { size: 10 }, precision: 0 }
                    }
                }
            }
        });
    }

    // Inisialisasi awal pada periode 'hari'
    renderAktivitasPeriode('hari');

    // Event listener switch periode: Desktop Pills
    const btnGroup = document.getElementById('btnGroupPeriodeAktivitas');
    const selectDropdown = document.getElementById('selectPeriodeAktivitasDropdown');

    if (btnGroup) {
        btnGroup.querySelectorAll('.btn-periode-pill').forEach(btn => {
            btn.addEventListener('click', function () {
                const periodKey = this.getAttribute('data-period');
                btnGroup.querySelectorAll('.btn-periode-pill').forEach(b => {
                    b.classList.remove('active');
                    b.style.background = 'transparent';
                    b.style.color = 'var(--text-muted)';
                    b.style.fontWeight = '600';
                });
                this.classList.add('active');
                this.style.background = 'var(--primary)';
                this.style.color = '#fff';
                this.style.fontWeight = '700';

                if (selectDropdown) {
                    selectDropdown.value = periodKey;
                }

                renderAktivitasPeriode(periodKey);
            });
        });
    }

    // Event listener switch periode: Mobile Dropdown
    if (selectDropdown) {
        selectDropdown.addEventListener('change', function () {
            const periodKey = this.value;
            if (btnGroup) {
                btnGroup.querySelectorAll('.btn-periode-pill').forEach(b => {
                    if (b.getAttribute('data-period') === periodKey) {
                        b.classList.add('active');
                        b.style.background = 'var(--primary)';
                        b.style.color = '#fff';
                        b.style.fontWeight = '700';
                    } else {
                        b.classList.remove('active');
                        b.style.background = 'transparent';
                        b.style.color = 'var(--text-muted)';
                        b.style.fontWeight = '600';
                    }
                });
            }
            renderAktivitasPeriode(periodKey);
        });
    }
}

function initTendik_kesiswaan() {
const payloadEl = document.getElementById('sectionKesiswaanChartPayload');
    if (!payloadEl || typeof Chart === 'undefined') return;

    let payload = {};
    try {
        payload = JSON.parse(payloadEl.textContent);
    } catch (e) {
        console.error('Gagal parsing sectionKesiswaanChartPayload', e);
        return;
    }

    const isLight = document.documentElement.getAttribute('data-theme') === 'light';
    const textColor = isLight ? '#1e293b' : '#f8fafc';
    const textMuted = isLight ? '#64748b' : '#94a3b8';
    const gridColor = isLight ? 'rgba(0,0,0,0.06)' : 'rgba(255,255,255,0.08)';

    // Plugin Angka Permanen pada Bar
    const barDataLabelsPlugin = {
        id: 'barDataLabelsKesiswaan',
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
        id: 'doughnutDataLabelsKesiswaan',
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
                ctx.fillText('SISWA', centerX, centerY - 9);

                ctx.fillStyle = textColor;
                ctx.font = '800 15px Inter, system-ui, sans-serif';
                ctx.fillText(Number(total).toLocaleString('id-ID'), centerX, centerY + 7);
            }
            ctx.restore();
        }
    };

    // 1. Chart Gender Siswa
    const ctxGen = document.getElementById('kesiswaanChartGender')?.getContext('2d');
    if (ctxGen && payload.gender) {
        new Chart(ctxGen, {
            type: 'doughnut',
            data: {
                labels: ['Laki-laki', 'Perempuan'],
                datasets: [{
                    data: [payload.gender.L || 0, payload.gender.P || 0],
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

    // 2. Chart Tingkat (Bar)
    const ctxTkt = document.getElementById('kesiswaanChartTingkat')?.getContext('2d');
    if (ctxTkt && payload.tingkat) {
        const labels = Object.keys(payload.tingkat).map(k => 'Kelas ' + k);
        const data = Object.values(payload.tingkat);
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

    // 3. Chart Jurusan Rombel (Horizontal Bar)
    const ctxJur = document.getElementById('kesiswaanChartJurusan')?.getContext('2d');
    if (ctxJur && payload.jurusan) {
        const labels = Object.keys(payload.jurusan);
        const data = Object.values(payload.jurusan);
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
                                return lbl.length > 18 ? lbl.substring(0, 16) + '...' : lbl;
                            }
                        }
                    }
                }
            }
        });
    }
}

function initTendik_laboran() {
const payloadEl = document.getElementById('sectionLaboranChartPayload');
    if (!payloadEl || typeof Chart === 'undefined') return;

    let payload = {};
    try {
        payload = JSON.parse(payloadEl.textContent);
    } catch (e) {
        console.error('Gagal parsing sectionLaboranChartPayload', e);
        return;
    }

    const isLight = document.documentElement.getAttribute('data-theme') === 'light';
    const textColor = isLight ? '#1e293b' : '#f8fafc';
    const textMuted = isLight ? '#64748b' : '#94a3b8';
    const gridColor = isLight ? 'rgba(0,0,0,0.06)' : 'rgba(255,255,255,0.08)';

    // Plugin Angka Permanen pada Bar
    const barDataLabelsPlugin = {
        id: 'barDataLabelsLaboran',
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

    // Plugin Doughnut Data Labels Permanen di Segmen Lingkaran (Angka & Persen)
    const doughnutDataLabelsPlugin = {
        id: 'doughnutDataLabelsLaboran',
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

    // 1. Chart Unit Lab (Doughnut)
    const ctxUnit = document.getElementById('laboranChartUnit')?.getContext('2d');
    if (ctxUnit && payload.sebaranUnit) {
        new Chart(ctxUnit, {
            type: 'doughnut',
            data: {
                labels: Object.keys(payload.sebaranUnit),
                datasets: [{
                    data: Object.values(payload.sebaranUnit),
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

    // 2. Chart Status Alat (Doughnut)
    const ctxStatus = document.getElementById('laboranChartStatus')?.getContext('2d');
    if (ctxStatus && payload.statusAlat) {
        new Chart(ctxStatus, {
            type: 'doughnut',
            data: {
                labels: Object.keys(payload.statusAlat),
                datasets: [{
                    data: Object.values(payload.statusAlat),
                    backgroundColor: ['#10b981', '#f59e0b', '#ef4444'],
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

    // 3. Chart Bahan & APD (Bar)
    const ctxBahan = document.getElementById('laboranChartBahan')?.getContext('2d');
    if (ctxBahan && payload.kategoriBahan) {
        new Chart(ctxBahan, {
            type: 'bar',
            data: {
                labels: Object.keys(payload.kategoriBahan),
                datasets: [{
                    data: Object.values(payload.kategoriBahan),
                    backgroundColor: ['rgba(59,130,246,0.85)', 'rgba(16,185,129,0.85)', 'rgba(245,158,11,0.85)'],
                    borderRadius: 6,
                    maxBarThickness: 32
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
}

function initTendik_penjaga() {
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
}

function initTendik_perpustakaan() {
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
}

function initTendik_persuratan() {
const payloadEl = document.getElementById('sectionPersuratanChartPayload');
    if (!payloadEl || typeof Chart === 'undefined') return;

    let payload = {};
    try {
        payload = JSON.parse(payloadEl.textContent);
    } catch (e) {
        console.error('Gagal parsing sectionPersuratanChartPayload', e);
        return;
    }

    const isLight = document.documentElement.getAttribute('data-theme') === 'light';
    const textColor = isLight ? '#1e293b' : '#f8fafc';
    const textMuted = isLight ? '#64748b' : '#94a3b8';
    const gridColor = isLight ? 'rgba(0,0,0,0.06)' : 'rgba(255,255,255,0.08)';

    // Plugin Angka Permanen pada Bar
    const barDataLabelsPlugin = {
        id: 'barDataLabelsPersuratan',
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
        id: 'doughnutDataLabelsPersuratan',
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
                ctx.fillText('SURAT', centerX, centerY - 9);

                ctx.fillStyle = textColor;
                ctx.font = '800 15px Inter, system-ui, sans-serif';
                ctx.fillText(Number(total).toLocaleString('id-ID'), centerX, centerY + 7);
            }
            ctx.restore();
        }
    };

    // 1. Chart Jenis Surat (Doughnut)
    const ctxJenis = document.getElementById('persuratanChartJenis')?.getContext('2d');
    if (ctxJenis && payload.jenis) {
        const labels = Object.keys(payload.jenis).map(k => k.charAt(0).toUpperCase() + k.slice(1));
        const data = Object.values(payload.jenis);
        new Chart(ctxJenis, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: ['#3b82f6', '#10b981', '#f59e0b', '#ec4899', '#6366f1'],
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

    // 2. Chart Status Surat (Bar)
    const ctxStatus = document.getElementById('persuratanChartStatus')?.getContext('2d');
    if (ctxStatus && payload.status) {
        const labels = Object.keys(payload.status).map(k => k.charAt(0).toUpperCase() + k.slice(1));
        const data = Object.values(payload.status);
        new Chart(ctxStatus, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: ['rgba(245,158,11,0.85)', 'rgba(16,185,129,0.85)', 'rgba(99,102,241,0.85)'],
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
}

function initTendik_piket() {
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
}

function initTendik_sarpras() {
const payloadEl = document.getElementById('sectionSarprasChartPayload');
    if (!payloadEl || typeof Chart === 'undefined') return;

    let payload = {};
    try {
        payload = JSON.parse(payloadEl.textContent);
    } catch (e) {
        console.error('Gagal parsing sectionSarprasChartPayload', e);
        return;
    }

    const isLight = document.documentElement.getAttribute('data-theme') === 'light';
    const textColor = isLight ? '#1e293b' : '#f8fafc';
    const textMuted = isLight ? '#64748b' : '#94a3b8';
    const gridColor = isLight ? 'rgba(0,0,0,0.06)' : 'rgba(255,255,255,0.08)';

    // Plugin Angka Permanen pada Bar
    const barDataLabelsPlugin = {
        id: 'barDataLabelsSarpras',
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

    // Plugin Doughnut Data Labels Permanen di Segmen Lingkaran (Angka & Persen)
    const doughnutDataLabelsPlugin = {
        id: 'doughnutDataLabelsSarpras',
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
                ctx.fillText('ASET', centerX, centerY - 9);

                ctx.fillStyle = textColor;
                ctx.font = '800 15px Inter, system-ui, sans-serif';
                ctx.fillText(Number(total).toLocaleString('id-ID'), centerX, centerY + 7);
            }
            ctx.restore();
        }
    };

    // 1. Chart Ruang per Gedung (Bar)
    const ctxGedung = document.getElementById('sarprasChartGedung')?.getContext('2d');
    if (ctxGedung && payload.gedung) {
        const labels = Object.keys(payload.gedung);
        const data = Object.values(payload.gedung);
        new Chart(ctxGedung, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: ['rgba(59,130,246,0.85)', 'rgba(16,185,129,0.85)', 'rgba(245,158,11,0.85)', 'rgba(99,102,241,0.85)', 'rgba(6,182,212,0.85)', 'rgba(236,72,153,0.85)'],
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
                                const lbl = labels[index] || '';
                                return lbl.length > 14 ? lbl.substring(0, 12) + '...' : lbl;
                            }
                        }
                    },
                    y: { grid: { color: gridColor }, ticks: { color: textMuted, font: { size: 10 } }, beginAtZero: true }
                }
            }
        });
    }

    // 2. Chart Kategori Aset (Doughnut)
    const ctxAset = document.getElementById('sarprasChartAset')?.getContext('2d');
    if (ctxAset && payload.kategoriAset) {
        const labels = Object.keys(payload.kategoriAset);
        const data = Object.values(payload.kategoriAset);
        new Chart(ctxAset, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: ['#f59e0b', '#3b82f6', '#10b981', '#6366f1', '#ec4899'],
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
}

function initTendik_teknisi() {
const payloadEl = document.getElementById('sectionTeknisiChartPayload');
    if (!payloadEl || typeof Chart === 'undefined') return;

    let payload = {};
    try {
        payload = JSON.parse(payloadEl.textContent);
    } catch (e) {
        console.error('Gagal parsing sectionTeknisiChartPayload', e);
        return;
    }

    const isLight = document.documentElement.getAttribute('data-theme') === 'light';
    const textColor = isLight ? '#1e293b' : '#f8fafc';
    const textMuted = isLight ? '#64748b' : '#94a3b8';
    const gridColor = isLight ? 'rgba(0,0,0,0.06)' : 'rgba(255,255,255,0.08)';

    // Plugin Angka Permanen pada Bar
    const barDataLabelsPlugin = {
        id: 'barDataLabelsTeknisi',
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
        id: 'doughnutDataLabelsTeknisi',
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

    // 1. Chart Kategori Perbaikan (Bar)
    const ctxKat = document.getElementById('teknisiChartKategori')?.getContext('2d');
    if (ctxKat && payload.kategoriPerbaikan) {
        new Chart(ctxKat, {
            type: 'bar',
            data: {
                labels: Object.keys(payload.kategoriPerbaikan),
                datasets: [{
                    data: Object.values(payload.kategoriPerbaikan),
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
                    x: {
                        grid: { display: false },
                        ticks: {
                            color: textMuted,
                            font: { size: 9 },
                            callback: function (val, index) {
                                const lbl = Object.keys(payload.kategoriPerbaikan)[index] || '';
                                return lbl.length > 14 ? lbl.substring(0, 12) + '...' : lbl;
                            }
                        }
                    },
                    y: { grid: { color: gridColor }, ticks: { color: textMuted, font: { size: 10 } }, beginAtZero: true }
                }
            }
        });
    }

    // 2. Chart Status WO (Doughnut)
    const ctxWO = document.getElementById('teknisiChartWO')?.getContext('2d');
    if (ctxWO && payload.statusWO) {
        new Chart(ctxWO, {
            type: 'doughnut',
            data: {
                labels: Object.keys(payload.statusWO),
                datasets: [{
                    data: Object.values(payload.statusWO),
                    backgroundColor: ['#10b981', '#f59e0b', '#ef4444'],
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

    // 3. Chart Sebaran Unit PC (Doughnut)
    const ctxSeb = document.getElementById('teknisiChartSebaran')?.getContext('2d');
    if (ctxSeb && payload.sebaranPerangkat) {
        new Chart(ctxSeb, {
            type: 'doughnut',
            data: {
                labels: Object.keys(payload.sebaranPerangkat),
                datasets: [{
                    data: Object.values(payload.sebaranPerangkat),
                    backgroundColor: ['#3b82f6', '#06b6d4', '#8b5cf6', '#ec4899'],
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
}

function initTendik_umum() {
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
}

document.addEventListener('DOMContentLoaded', function () {
    initTendikKinerjaAktivitasCharts();
    if (typeof initTendik_keamanan === 'function') initTendik_keamanan();
    if (typeof initTendik_kepala_tas === 'function') initTendik_kepala_tas();
    if (typeof initTendik_kepegawaian === 'function') initTendik_kepegawaian();
    if (typeof initTendik_kesiswaan === 'function') initTendik_kesiswaan();
    if (typeof initTendik_laboran === 'function') initTendik_laboran();
    if (typeof initTendik_penjaga === 'function') initTendik_penjaga();
    if (typeof initTendik_perpustakaan === 'function') initTendik_perpustakaan();
    if (typeof initTendik_persuratan === 'function') initTendik_persuratan();
    if (typeof initTendik_piket === 'function') initTendik_piket();
    if (typeof initTendik_sarpras === 'function') initTendik_sarpras();
    if (typeof initTendik_teknisi === 'function') initTendik_teknisi();
    if (typeof initTendik_umum === 'function') initTendik_umum();
});
})();
