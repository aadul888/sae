/**
 * Dashboard Administrator — SAE Interactive Charts & Log Datatable Module
 */

document.addEventListener('DOMContentLoaded', function () {
    // 1. Inisialisasi Seluruh Chart Interaktif
    initAdminCharts();

    // 2. Inisialisasi Filter & Live Search Log Aktivitas Administrator
    initAdminActivityDatatable();
});

/**
 * Visualisasi Interaktif Campuran (Garis, Lingkaran/Doughnut, Batang, Polar Area)
 */
function initAdminCharts() {
    if (typeof Chart === 'undefined') return;

    const dataEl = document.getElementById('adminChartPayload');
    if (!dataEl) return;

    let payload = {};
    try {
        payload = JSON.parse(dataEl.textContent);
    } catch (e) {
        console.error('Gagal parsing data chart admin:', e);
        return;
    }

    const isLight = document.documentElement.getAttribute('data-theme') === 'light';
    const textColor = isLight ? '#0f172a' : '#f8fafc';
    const textMuted = isLight ? '#64748b' : '#94a3b8';
    const gridColor = isLight ? 'rgba(0, 0, 0, 0.06)' : 'rgba(255, 255, 255, 0.07)';
    const cardBg = isLight ? '#ffffff' : '#1e293b';

    // Global default font
    Chart.defaults.font.family = 'Plus Jakarta Sans, sans-serif';
    Chart.defaults.color = textMuted;

    // =============================================================
    // PLUGIN CUSTOM DATA LABELS UNTUK SETIAP JENIS CHART
    // =============================================================

    // 1. Plugin Angka Langsung pada Chart Garis (Line)
    const lineDataLabelsPlugin = {
        id: 'adminLineDataLabels',
        afterDatasetsDraw(chart) {
            const { ctx, data } = chart;
            ctx.save();

            data.datasets.forEach((dataset, dIdx) => {
                const meta = chart.getDatasetMeta(dIdx);
                if (!meta || meta.hidden) return;

                meta.data.forEach((point, pIdx) => {
                    const val = dataset.data[pIdx];
                    if (val === undefined || val === null) return;

                    const text = val + '%';
                    ctx.font = 'bold 10px Plus Jakarta Sans, sans-serif';
                    const textWidth = ctx.measureText(text).width;

                    // Posisi label: Siswa ke atas (-13px), GTK sedikit lebih atas (-28px) agar tidak bertabrakan
                    const yOffset = dIdx === 0 ? -13 : -28;
                    const posX = point.x;
                    const posY = point.y + yOffset;

                    // Badge pill background
                    const padH = 4;
                    const padV = 2;
                    const bgX = posX - (textWidth / 2) - padH;
                    const bgY = posY - 8 - padV;
                    const bgW = textWidth + (padH * 2);
                    const bgH = 13 + (padV * 2);

                    ctx.fillStyle = dIdx === 0 ? 'rgba(99, 102, 241, 0.92)' : 'rgba(16, 185, 129, 0.92)';
                    if (typeof ctx.roundRect === 'function') {
                        ctx.beginPath();
                        ctx.roundRect(bgX, bgY, bgW, bgH, 4);
                        ctx.fill();
                    } else {
                        ctx.fillRect(bgX, bgY, bgW, bgH);
                    }

                    // Teks persentase
                    ctx.fillStyle = '#ffffff';
                    ctx.textAlign = 'center';
                    ctx.textBaseline = 'middle';
                    ctx.fillText(text, posX, posY - 1);
                });
            });

            ctx.restore();
        }
    };

    // 2. Plugin Angka Langsung pada Chart Lingkaran / Donat (Doughnut)
    const doughnutDataLabelsPlugin = {
        id: 'adminDoughnutDataLabels',
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
                ctx.font = 'bold 12px Plus Jakarta Sans, sans-serif';
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                ctx.fillText(Number(val).toLocaleString('id-ID') + ' Org', pos.x, pos.y - 6);

                ctx.font = '600 10px Plus Jakarta Sans, sans-serif';
                ctx.fillStyle = 'rgba(255, 255, 255, 0.92)';
                ctx.fillText(pct + '%', pos.x, pos.y + 8);
            });

            // Angka Total di Tengah Donat
            if (total > 0 && meta.data[0]) {
                const centerX = (chart.chartArea.left + chart.chartArea.right) / 2;
                const centerY = (chart.chartArea.top + chart.chartArea.bottom) / 2;

                ctx.fillStyle = isLight ? '#64748b' : '#94a3b8';
                ctx.font = '700 9px Plus Jakarta Sans, sans-serif';
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                ctx.fillText('TOTAL GTK', centerX, centerY - 10);

                ctx.fillStyle = isLight ? '#0f172a' : '#f8fafc';
                ctx.font = '800 17px Plus Jakarta Sans, sans-serif';
                ctx.fillText(Number(total).toLocaleString('id-ID'), centerX, centerY + 8);
            }
            ctx.restore();
        }
    };

    // 3. Plugin Angka Langsung di Atas Batang (Bar)
    const barDataLabelsPlugin = {
        id: 'adminBarDataLabels',
        afterDatasetsDraw(chart) {
            const { ctx, data } = chart;
            ctx.save();
            chart.getDatasetMeta(0).data.forEach((bar, index) => {
                const val = data.datasets[0].data[index];
                if (val !== undefined && val !== null && val > 0) {
                    ctx.fillStyle = isLight ? '#0f172a' : '#f8fafc';
                    ctx.font = 'bold 11px Plus Jakarta Sans, sans-serif';
                    ctx.textAlign = 'center';
                    ctx.textBaseline = 'bottom';
                    ctx.fillText(Number(val).toLocaleString('id-ID'), bar.x, bar.y - 4);
                }
            });
            ctx.restore();
        }
    };

    // 4. Plugin Angka Langsung pada Chart Polar Area
    const polarDataLabelsPlugin = {
        id: 'adminPolarDataLabels',
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
                ctx.font = 'bold 11px Plus Jakarta Sans, sans-serif';
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                ctx.fillText(Number(val).toLocaleString('id-ID'), pos.x, pos.y - 5);

                ctx.font = '600 9px Plus Jakarta Sans, sans-serif';
                ctx.fillStyle = 'rgba(255, 255, 255, 0.9)';
                ctx.fillText(pct + '%', pos.x, pos.y + 7);
            });
            ctx.restore();
        }
    };

    // -------------------------------------------------------------
    // 1. CHART GARIS (LINE): Tren Presensi Siswa & GTK 7 Hari Terakhir
    // -------------------------------------------------------------
    const ctxTrend = document.getElementById('chartPresensiTrend')?.getContext('2d');
    if (ctxTrend && payload.trend) {
        // Gradient fill untuk Siswa
        const gradientSiswa = ctxTrend.createLinearGradient(0, 0, 0, 240);
        gradientSiswa.addColorStop(0, 'rgba(99, 102, 241, 0.32)');
        gradientSiswa.addColorStop(1, 'rgba(99, 102, 241, 0.0)');

        // Gradient fill untuk GTK
        const gradientGtk = ctxTrend.createLinearGradient(0, 0, 0, 240);
        gradientGtk.addColorStop(0, 'rgba(16, 185, 129, 0.25)');
        gradientGtk.addColorStop(1, 'rgba(16, 185, 129, 0.0)');

        window.adminChartTrend = new Chart(ctxTrend, {
            type: 'line',
            data: {
                labels: payload.trend.labels || [],
                datasets: [
                    {
                        label: 'Peserta Didik (%)',
                        data: payload.trend.siswa || [],
                        borderColor: '#6366f1',
                        backgroundColor: gradientSiswa,
                        fill: true,
                        tension: 0.38,
                        pointBackgroundColor: '#6366f1',
                        pointBorderColor: cardBg,
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                    },
                    {
                        label: 'GTK (Guru & Tendik) (%)',
                        data: payload.trend.gtk || [],
                        borderColor: '#10b981',
                        backgroundColor: gradientGtk,
                        fill: true,
                        tension: 0.38,
                        pointBackgroundColor: '#10b981',
                        pointBorderColor: cardBg,
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                    }
                ]
            },
            plugins: [lineDataLabelsPlugin],
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                layout: {
                    padding: {
                        top: 25,
                        bottom: 5
                    }
                },
                plugins: {
                    legend: {
                        position: 'top',
                        align: 'end',
                        labels: {
                            boxWidth: 12,
                            boxHeight: 12,
                            usePointStyle: true,
                            color: textColor,
                            font: { size: 11, weight: '600' }
                        }
                    },
                    tooltip: {
                        backgroundColor: isLight ? '#0f172a' : '#1e293b',
                        titleColor: '#ffffff',
                        bodyColor: '#e2e8f0',
                        borderColor: isLight ? '#334155' : '#475569',
                        borderWidth: 1,
                        padding: 10,
                        callbacks: {
                            label: function (ctx) {
                                return ` ${ctx.dataset.label}: ${ctx.parsed.y}% Kehadiran`;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { color: textMuted, font: { size: 11 } }
                    },
                    y: {
                        min: 88,
                        max: 105,
                        grid: { color: gridColor },
                        ticks: {
                            color: textMuted,
                            font: { size: 11 },
                            callback: v => v + '%'
                        }
                    }
                }
            }
        });
    }

    // -------------------------------------------------------------
    // 2. CHART LINGKARAN (DOUGHNUT): Komposisi GTK Guru vs Tendik
    // -------------------------------------------------------------
    const ctxGtk = document.getElementById('chartGtkComposition')?.getContext('2d');
    if (ctxGtk && payload.gtk) {
        const totalGtk = (payload.gtk.guru || 0) + (payload.gtk.tendik || 0);

        window.adminChartGtk = new Chart(ctxGtk, {
            type: 'doughnut',
            data: {
                labels: ['Guru & Pendidik', 'Tenaga Kependidikan'],
                datasets: [{
                    data: [payload.gtk.guru || 0, payload.gtk.tendik || 0],
                    backgroundColor: ['#6366f1', '#06b6d4'],
                    borderColor: cardBg,
                    borderWidth: 3,
                    hoverOffset: 6
                }]
            },
            plugins: [doughnutDataLabelsPlugin],
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '66%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            boxWidth: 12,
                            boxHeight: 12,
                            usePointStyle: true,
                            color: textColor,
                            font: { size: 11, weight: '600' },
                            padding: 14
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function (ctx) {
                                const val = ctx.parsed;
                                const pct = totalGtk > 0 ? Math.round((val / totalGtk) * 100) : 0;
                                return ` ${ctx.label}: ${val} Orang (${pct}%)`;
                            }
                        }
                    }
                }
            }
        });
    }

    // -------------------------------------------------------------
    // 3. CHART BATANG (BAR): Sebaran Siswa per Konsentrasi Keahlian
    // -------------------------------------------------------------
    const ctxJurusan = document.getElementById('chartJurusan')?.getContext('2d');
    if (ctxJurusan && payload.jurusan) {
        const labels = payload.jurusan.map(j => {
            let name = j.jurusan;
            if (name.length > 24) {
                return name.substring(0, 22) + '…';
            }
            return name;
        });
        const values = payload.jurusan.map(j => j.total);
        const palette = [
            'rgba(99, 102, 241, 0.85)',
            'rgba(6, 182, 212, 0.85)',
            'rgba(16, 185, 129, 0.85)',
            'rgba(245, 158, 11, 0.85)',
            'rgba(168, 85, 247, 0.85)',
            'rgba(236, 72, 153, 0.85)'
        ];

        const maxVal = Math.max(...values, 0);

        window.adminChartJurusan = new Chart(ctxJurusan, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Jumlah Siswa',
                    data: values,
                    backgroundColor: palette.slice(0, values.length),
                    borderRadius: 6,
                    maxBarThickness: 34
                }]
            },
            plugins: [barDataLabelsPlugin],
            options: {
                responsive: true,
                maintainAspectRatio: false,
                layout: {
                    padding: {
                        top: 20
                    }
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: ctx => ` Jumlah: ${ctx.parsed.y} Peserta Didik`
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { color: textMuted, font: { size: 10 } }
                    },
                    y: {
                        max: Math.ceil((maxVal * 1.15) / 50) * 50,
                        grid: { color: gridColor },
                        ticks: { color: textMuted, font: { size: 10 }, stepSize: 50 },
                        beginAtZero: true
                    }
                }
            }
        });
    }

    // -------------------------------------------------------------
    // 4. CHART POLAR AREA: Distribusi Siswa per Tingkat Kelas
    // -------------------------------------------------------------
    const ctxTingkat = document.getElementById('chartTingkat')?.getContext('2d');
    if (ctxTingkat && payload.tingkat) {
        const labels = payload.tingkat.map(t => 'Kelas ' + t.tingkat);
        const values = payload.tingkat.map(t => t.total);

        window.adminChartTingkat = new Chart(ctxTingkat, {
            type: 'polarArea',
            data: {
                labels: labels,
                datasets: [{
                    data: values,
                    backgroundColor: [
                        'rgba(59, 130, 246, 0.75)',
                        'rgba(16, 185, 129, 0.75)',
                        'rgba(245, 158, 11, 0.75)'
                    ],
                    borderColor: cardBg,
                    borderWidth: 2
                }]
            },
            plugins: [polarDataLabelsPlugin],
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    r: {
                        grid: { color: gridColor },
                        ticks: { display: false },
                        angleLines: { color: gridColor }
                    }
                },
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            boxWidth: 12,
                            boxHeight: 12,
                            usePointStyle: true,
                            color: textColor,
                            font: { size: 11, weight: '600' }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: ctx => ` ${ctx.label}: ${ctx.parsed.r} Peserta Didik`
                        }
                    }
                }
            }
        });
    }
}

/**
 * Filter, Pencarian Live Search, dan Pagination AJAX untuk Datatable Log Administrator
 */
function initAdminActivityDatatable() {
    const liveSearch = document.getElementById('liveSearchAdmin');
    const clearSearch = document.getElementById('clearSearchAdmin');
    const filterModul = document.getElementById('filterModulAdmin');
    const perPageSelect = document.getElementById('perPageSelectAdmin');

    let debounceTimer = null;

    const applyFilter = () => {
        const url = new URL(window.location.href);

        // Search
        if (liveSearch) {
            const query = liveSearch.value.trim();
            if (query) {
                url.searchParams.set('q', query);
            } else {
                url.searchParams.delete('q');
            }
        }

        // Modul
        if (filterModul) {
            const mod = filterModul.value;
            if (mod) {
                url.searchParams.set('modul', mod);
            } else {
                url.searchParams.delete('modul');
            }
        }

        // Per Page
        if (perPageSelect) {
            url.searchParams.set('perPage', perPageSelect.value);
        }

        url.searchParams.set('page', '1');

        if (typeof window.refreshLiveTable === 'function') {
            window.refreshLiveTable(url.toString());
        } else {
            window.location.href = url.toString();
        }
    };

    if (liveSearch) {
        liveSearch.addEventListener('input', function () {
            if (clearSearch) {
                clearSearch.classList.toggle('visible', this.value.trim().length > 0);
            }
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(applyFilter, 300);
        });

        liveSearch.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                clearTimeout(debounceTimer);
                applyFilter();
            }
        });
    }

    if (clearSearch) {
        clearSearch.addEventListener('click', function () {
            if (liveSearch) liveSearch.value = '';
            clearSearch.classList.remove('visible');
            applyFilter();
        });
    }

    if (filterModul) {
        filterModul.addEventListener('change', function () {
            applyFilter();
        });
    }

    if (perPageSelect) {
        perPageSelect.addEventListener('change', function () {
            applyFilter();
        });
    }
}
