/**
 * SAE - Home Landing Page Charts Script
 */

document.addEventListener('DOMContentLoaded', () => {
    const payloadEl = document.getElementById('homeChartsPayload');
    if (!payloadEl || typeof Chart === 'undefined') return;

    let payload = {};
    try {
        payload = JSON.parse(payloadEl.textContent);
    } catch (e) {
        console.error('Gagal parsing homeChartsPayload', e);
        return;
    }

    const stats = payload.stats || {};
    const majorData = payload.major_data || [];
    const chartDetail = payload.chart_detail || [];

    const filterMajor = document.getElementById('filterMajor');
    const filterGrade = document.getElementById('filterGrade');

    let barChart = null;
    let pieChart = null;

    // Custom Plugin: Angka Permanen di Atas Batang Diagram Jurusan
    const barDataLabelsPlugin = {
        id: 'barDataLabels',
        afterDatasetsDraw(chart) {
            const { ctx, data } = chart;
            const isLight = document.documentElement.getAttribute('data-theme') === 'light';
            ctx.save();
            chart.getDatasetMeta(0).data.forEach((bar, index) => {
                const val = data.datasets[0].data[index];
                if (val !== undefined && val !== null && val > 0) {
                    ctx.fillStyle = isLight ? '#1e293b' : '#f8fafc';
                    ctx.font = 'bold 11px Inter, system-ui, sans-serif';
                    ctx.textAlign = 'center';
                    ctx.textBaseline = 'bottom';
                    ctx.fillText(Number(val).toLocaleString('id-ID'), bar.x, bar.y - 4);
                }
            });
            ctx.restore();
        }
    };

    // Custom Plugin: Angka Permanen di Segmen Lingkaran & Total Tengah Donat
    const doughnutDataLabelsPlugin = {
        id: 'doughnutDataLabels',
        afterDatasetsDraw(chart) {
            const { ctx, data } = chart;
            const isLight = document.documentElement.getAttribute('data-theme') === 'light';
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
                ctx.font = 'bold 12px Inter, system-ui, sans-serif';
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';

                if (pct >= 8) {
                    ctx.fillText(Number(val).toLocaleString('id-ID'), pos.x, pos.y - 6);
                    ctx.font = '600 10px Inter, system-ui, sans-serif';
                    ctx.fillStyle = 'rgba(255,255,255,0.88)';
                    ctx.fillText(pct + '%', pos.x, pos.y + 7);
                } else {
                    ctx.fillText(Number(val).toLocaleString('id-ID'), pos.x, pos.y);
                }
            });

            if (total > 0 && meta.data[0]) {
                const centerX = (chart.chartArea.left + chart.chartArea.right) / 2;
                const centerY = (chart.chartArea.top + chart.chartArea.bottom) / 2;

                ctx.fillStyle = isLight ? '#64748b' : '#94a3b8';
                ctx.font = '700 9px Inter, system-ui, sans-serif';
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                ctx.fillText('TOTAL', centerX, centerY - 10);

                ctx.fillStyle = isLight ? '#0f172a' : '#f8fafc';
                ctx.font = '800 16px Inter, system-ui, sans-serif';
                ctx.fillText(Number(total).toLocaleString('id-ID'), centerX, centerY + 8);
            }
            ctx.restore();
        }
    };

    // Bar Chart (Jurusan)
    const ctxBar = document.getElementById('barMajorChart')?.getContext('2d');
    if (ctxBar) {
        barChart = new Chart(ctxBar, {
            type: 'bar',
            data: {
                labels: majorData.map(m => m.code || m.nama_jurusan),
                datasets: [{
                    label: 'Jumlah Peserta Didik',
                    data: majorData.map(m => m.total_peserta_didik),
                    backgroundColor: '#3b82f6',
                    borderRadius: 8,
                }]
            },
            plugins: [barDataLabelsPlugin],
            options: {
                responsive: true,
                maintainAspectRatio: false,
                layout: {
                    padding: {
                        top: 16
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        enabled: true
                    }
                },
                scales: {
                    x: {
                        grid: {
                            color: 'rgba(255,255,255,0.05)'
                        },
                        ticks: {
                            color: '#94a3b8',
                            font: {
                                size: 11
                            }
                        }
                    },
                    y: {
                        grace: '15%',
                        grid: {
                            color: 'rgba(255,255,255,0.05)'
                        },
                        ticks: {
                            color: '#94a3b8',
                            font: {
                                size: 11
                            }
                        }
                    }
                }
            }
        });
    }

    // Pie/Doughnut Chart (Tingkat)
    const ctxPie = document.getElementById('pieGradeChart')?.getContext('2d');
    if (ctxPie) {
        pieChart = new Chart(ctxPie, {
            type: 'doughnut',
            data: {
                labels: ['Kelas X', 'Kelas XI', 'Kelas XII'],
                datasets: [{
                    data: [stats.grade_x || 0, stats.grade_xi || 0, stats.grade_xii || 0],
                    backgroundColor: ['#3b82f6', '#06b6d4', '#8b5cf6'],
                    borderWidth: 2,
                    borderColor: 'transparent'
                }]
            },
            plugins: [doughnutDataLabelsPlugin],
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '62%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: '#94a3b8',
                            font: {
                                size: 11
                            }
                        }
                    },
                    tooltip: {
                        enabled: true
                    }
                }
            }
        });
    }

    function updateCharts() {
        const selectedMajor = filterMajor ? filterMajor.value : '';
        const selectedGrade = filterGrade ? filterGrade.value : '';

        let filtered = chartDetail;
        if (selectedMajor) {
            filtered = filtered.filter(item => item.j === selectedMajor);
        }
        if (selectedGrade) {
            filtered = filtered.filter(item => item.tg === selectedGrade);
        }

        if (barChart) {
            let barLabels = [];
            let barValues = [];

            if (selectedMajor) {
                barLabels = filtered.map(item => item.k);
                barValues = filtered.map(item => item.L + item.P);
            } else if (selectedGrade) {
                const majorMap = {};
                filtered.forEach(item => {
                    majorMap[item.j] = (majorMap[item.j] || 0) + (item.L + item.P);
                });
                majorData.forEach(m => {
                    if (majorMap[m.nama_jurusan] !== undefined) {
                        barLabels.push(m.code || m.nama_jurusan);
                        barValues.push(majorMap[m.nama_jurusan]);
                    }
                });
            } else {
                barLabels = majorData.map(m => m.code || m.nama_jurusan);
                barValues = majorData.map(m => m.total_peserta_didik);
            }

            barChart.data.labels = barLabels;
            barChart.data.datasets[0].data = barValues;
            barChart.update();
        }

        if (pieChart) {
            let countX = 0,
                countXI = 0,
                countXII = 0;
            if (selectedMajor) {
                filtered.forEach(item => {
                    if (item.tg === 'X') countX += (item.L + item.P);
                    else if (item.tg === 'XI') countXI += (item.L + item.P);
                    else if (item.tg === 'XII') countXII += (item.L + item.P);
                });
            } else {
                countX = stats.grade_x || 0;
                countXI = stats.grade_xi || 0;
                countXII = stats.grade_xii || 0;
            }

            pieChart.data.datasets[0].data = [countX, countXI, countXII];
            pieChart.update();
        }
    }

    if (filterMajor) filterMajor.addEventListener('change', updateCharts);
    if (filterGrade) filterGrade.addEventListener('change', updateCharts);

    const themeObserver = new MutationObserver(() => {
        if (barChart) barChart.update();
        if (pieChart) pieChart.update();
    });
    themeObserver.observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme'] });
});
