(function () {
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


window.initTendikGroup2 = function() {
    if (typeof initTendik_kepegawaian === 'function') initTendik_kepegawaian();
    if (typeof initTendik_kesiswaan === 'function') initTendik_kesiswaan();
};
document.addEventListener('DOMContentLoaded', window.initTendikGroup2);
})();
