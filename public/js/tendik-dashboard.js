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


window.initTendikGroup1 = function() {
    initTendikKinerjaAktivitasCharts();
    if (typeof initTendik_keamanan === 'function') initTendik_keamanan();
    if (typeof initTendik_kepala_tas === 'function') initTendik_kepala_tas();
};
document.addEventListener('DOMContentLoaded', window.initTendikGroup1);
})();
