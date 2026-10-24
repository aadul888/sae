(function () {
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
                    backgroundColor: ['rgba(245,158,11,0.85)', 'rgba(59,130,246,0.85)', 'rgba(16,185,129,0.85)', 'rgba(99,102,241,0.85)', 'rgba(100,116,139,0.85)'],
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


window.initTendikGroup4 = function() {
    if (typeof initTendik_persuratan === 'function') initTendik_persuratan();
    if (typeof initTendik_piket === 'function') initTendik_piket();
    if (typeof initTendik_sarpras === 'function') initTendik_sarpras();
    if (typeof initTendik_teknisi === 'function') initTendik_teknisi();
    if (typeof initTendik_umum === 'function') initTendik_umum();
};
document.addEventListener('DOMContentLoaded', window.initTendikGroup4);
})();
