/**
 * Modul Dashboard Wali Kelas — SAE Interactive Charts & Dual Datatables
 * Standar Resmi SAE (Vanilla JS + Chart.js)
 */

document.addEventListener('DOMContentLoaded', function () {
    // 1. Inisialisasi Chart Analitik Interaktif
    initWaliKelasCharts();

    // 2. Inisialisasi Filter & Live Search Datatable 1 (Log Aktivitas)
    initTableAktivitasFilter();

    // 3. Inisialisasi Filter & Live Search Datatable 2 (Log Presensi Harian)
    initTablePresensiFilter();
});

/**
 * Render 4 Kombinasi Chart Interaktif dengan Chart.js
 */
function initWaliKelasCharts() {
    const payloadEl = document.getElementById('waliChartPayload');
    if (!payloadEl) return;

    let payload = {};
    try {
        payload = JSON.parse(payloadEl.textContent);
    } catch (e) {
        console.error('Gagal membaca payload chart wali kelas:', e);
        return;
    }

    if (typeof Chart === 'undefined') {
        console.warn('Library Chart.js belum tersedia.');
        return;
    }

    const computedStyle = getComputedStyle(document.documentElement);
    const isLight = document.documentElement.getAttribute('data-theme') === 'light';
    const textColor = computedStyle.getPropertyValue('--text-color').trim() || (isLight ? '#0f172a' : '#f8fafc');
    const textMuted = computedStyle.getPropertyValue('--text-muted').trim() || (isLight ? '#64748b' : '#94a3b8');
    const cardBg    = computedStyle.getPropertyValue('--card-bg').trim() || (isLight ? '#ffffff' : '#1e293b');
    const borderColor = computedStyle.getPropertyValue('--border-color').trim() || 'rgba(255,255,255,0.08)';

    // -------------------------------------------------------------
    // CHART 1: Tren Presensi Kehadiran Siswa (Kombinasi Garis & Area)
    // -------------------------------------------------------------
    const ctxTrend = document.getElementById('chartWaliTrend')?.getContext('2d');
    if (ctxTrend && payload.trend && payload.trend.labels && payload.trend.labels.length > 0) {
        const gradHadir = ctxTrend.createLinearGradient(0, 0, 0, 220);
        gradHadir.addColorStop(0, 'rgba(16, 185, 129, 0.35)');
        gradHadir.addColorStop(1, 'rgba(16, 185, 129, 0.02)');

        new Chart(ctxTrend, {
            type: 'line',
            data: {
                labels: payload.trend.labels,
                datasets: [
                    {
                        label: 'Tingkat Kehadiran (%)',
                        data: payload.trend.hadir_pct,
                        borderColor: '#10b981',
                        backgroundColor: gradHadir,
                        fill: true,
                        tension: 0.36,
                        borderWidth: 2.5,
                        pointBackgroundColor: '#10b981',
                        pointBorderColor: cardBg,
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        yAxisID: 'yPct'
                    },
                    {
                        label: 'Terlambat (Siswa)',
                        data: payload.trend.terlambat,
                        borderColor: '#f59e0b',
                        backgroundColor: 'rgba(245, 158, 11, 0.85)',
                        type: 'bar',
                        borderRadius: 4,
                        maxBarThickness: 16,
                        yAxisID: 'yCount'
                    },
                    {
                        label: 'Sakit & Izin (Siswa)',
                        data: payload.trend.izin_sakit,
                        borderColor: '#8b5cf6',
                        backgroundColor: 'rgba(139, 92, 246, 0.85)',
                        type: 'bar',
                        borderRadius: 4,
                        maxBarThickness: 16,
                        yAxisID: 'yCount'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        align: 'end',
                        labels: {
                            color: textColor,
                            boxWidth: 12,
                            boxHeight: 12,
                            font: { size: 11, family: 'Inter, system-ui, sans-serif', weight: '600' }
                        }
                    },
                    tooltip: {
                        backgroundColor: isLight ? 'rgba(15,23,42,0.95)' : 'rgba(30,41,59,0.98)',
                        titleColor: '#ffffff',
                        bodyColor: '#e2e8f0',
                        borderColor: borderColor,
                        borderWidth: 1,
                        padding: 10,
                        cornerRadius: 8
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { color: textMuted, font: { size: 10 } }
                    },
                    yPct: {
                        type: 'linear',
                        position: 'left',
                        min: 0,
                        max: 100,
                        grid: { color: isLight ? 'rgba(0,0,0,0.05)' : 'rgba(255,255,255,0.06)' },
                        ticks: {
                            color: textMuted,
                            font: { size: 10 },
                            callback: v => v + '%'
                        }
                    },
                    yCount: {
                        type: 'linear',
                        position: 'right',
                        min: 0,
                        grid: { display: false },
                        ticks: {
                            color: textMuted,
                            font: { size: 10 },
                            stepSize: 1
                        }
                    }
                }
            }
        });
    }

    // -------------------------------------------------------------
    // CHART 2: Komposisi Status Kehadiran Hari Ini (Doughnut)
    // -------------------------------------------------------------
    const ctxKomposisi = document.getElementById('chartWaliKomposisi')?.getContext('2d');
    if (ctxKomposisi && payload.komposisi && payload.komposisi.data) {
        const hadirPersen = payload.komposisi.persen || 0;

        // Custom Plugin: Total & Persen di Tengah Donat
        const centerTextPlugin = {
            id: 'waliCenterText',
            beforeDraw(chart) {
                const { ctx, chartArea } = chart;
                if (!chartArea) return;
                const centerX = (chartArea.left + chartArea.right) / 2;
                const centerY = (chartArea.top + chartArea.bottom) / 2;

                ctx.save();
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';

                ctx.fillStyle = textMuted;
                ctx.font = '700 9px Inter, system-ui, sans-serif';
                ctx.fillText('KEHADIRAN', centerX, centerY - 10);

                ctx.fillStyle = textColor;
                ctx.font = '800 18px Inter, system-ui, sans-serif';
                ctx.fillText(hadirPersen + '%', centerX, centerY + 8);

                ctx.restore();
            }
        };

        new Chart(ctxKomposisi, {
            type: 'doughnut',
            data: {
                labels: payload.komposisi.labels,
                datasets: [{
                    data: payload.komposisi.data,
                    backgroundColor: [
                        '#10b981', // Hadir Tepat
                        '#f59e0b', // Terlambat
                        '#8b5cf6', // Sakit
                        '#3b82f6', // Izin
                        '#ef4444', // Alpha
                        '#64748b'  // Belum
                    ],
                    borderWidth: 2,
                    borderColor: cardBg,
                    hoverOffset: 6
                }]
            },
            plugins: [centerTextPlugin],
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: {
                        display: true,
                        position: 'bottom',
                        labels: {
                            color: textColor,
                            boxWidth: 10,
                            boxHeight: 10,
                            padding: 10,
                            font: { size: 10, family: 'Inter, system-ui, sans-serif' }
                        }
                    }
                }
            }
        });
    }

    // -------------------------------------------------------------
    // CHART 3: Komposisi Gender Siswa Kelas (Doughnut)
    // -------------------------------------------------------------
    const ctxGender = document.getElementById('chartWaliGender')?.getContext('2d');
    if (ctxGender && payload.gender && payload.gender.data) {
        const totalGender = (payload.gender.data[0] || 0) + (payload.gender.data[1] || 0);

        const genderCenterPlugin = {
            id: 'genderCenterText',
            beforeDraw(chart) {
                const { ctx, chartArea } = chart;
                if (!chartArea) return;
                const centerX = (chartArea.left + chartArea.right) / 2;
                const centerY = (chartArea.top + chartArea.bottom) / 2;

                ctx.save();
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';

                ctx.fillStyle = textMuted;
                ctx.font = '700 9px Inter, system-ui, sans-serif';
                ctx.fillText('TOTAL', centerX, centerY - 10);

                ctx.fillStyle = textColor;
                ctx.font = '800 18px Inter, system-ui, sans-serif';
                ctx.fillText(totalGender + ' SISWA', centerX, centerY + 8);

                ctx.restore();
            }
        };

        new Chart(ctxGender, {
            type: 'doughnut',
            data: {
                labels: payload.gender.labels,
                datasets: [{
                    data: payload.gender.data,
                    backgroundColor: ['#3b82f6', '#ec4899'],
                    borderWidth: 2,
                    borderColor: cardBg,
                    hoverOffset: 6
                }]
            },
            plugins: [genderCenterPlugin],
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '68%',
                plugins: {
                    legend: {
                        display: true,
                        position: 'bottom',
                        labels: {
                            color: textColor,
                            boxWidth: 10,
                            boxHeight: 10,
                            padding: 10,
                            font: { size: 11, family: 'Inter, system-ui, sans-serif', weight: '600' }
                        }
                    }
                }
            }
        });
    }

    // -------------------------------------------------------------
    // CHART 4: Evaluasi Ketepatan Waktu & Kedisiplinan (Bar)
    // -------------------------------------------------------------
    const ctxDisiplin = document.getElementById('chartWaliDisiplin')?.getContext('2d');
    if (ctxDisiplin && payload.disiplin && payload.disiplin.data) {
        new Chart(ctxDisiplin, {
            type: 'bar',
            data: {
                labels: payload.disiplin.labels,
                datasets: [{
                    label: 'Jumlah Siswa',
                    data: payload.disiplin.data,
                    backgroundColor: [
                        'rgba(16, 185, 129, 0.85)',
                        'rgba(245, 158, 11, 0.85)',
                        'rgba(239, 68, 68, 0.85)',
                        'rgba(139, 92, 246, 0.85)'
                    ],
                    borderRadius: 6,
                    maxBarThickness: 32
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: isLight ? 'rgba(15,23,42,0.95)' : 'rgba(30,41,59,0.98)',
                        titleColor: '#ffffff',
                        bodyColor: '#e2e8f0',
                        padding: 10,
                        cornerRadius: 8
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { color: textMuted, font: { size: 10 } }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: isLight ? 'rgba(0,0,0,0.05)' : 'rgba(255,255,255,0.06)' },
                        ticks: {
                            color: textMuted,
                            font: { size: 10 },
                            stepSize: 2
                        }
                    }
                }
            }
        });
    }
}

/**
 * Filter & Live Search untuk Datatable 1: Log Aktivitas Siswa Perwalian
 */
function initTableAktivitasFilter() {
    const searchInput = document.getElementById('searchAktivitas');
    const filterKategori = document.getElementById('filterKategoriAktivitas');
    const tbody = document.getElementById('tbodyAktivitas');
    const countEl = document.getElementById('countAktivitasFilter');

    if (!tbody) return;

    function applyAktivitasFilter() {
        const query = searchInput ? searchInput.value.toLowerCase().trim() : '';
        const kat = filterKategori ? filterKategori.value : '';
        const rows = tbody.querySelectorAll('tr.row-aktivitas');

        let visibleCount = 0;
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            const rowKat = row.getAttribute('data-kategori') || '';

            const matchQuery = !query || text.includes(query);
            const matchKat = !kat || rowKat === kat;

            if (matchQuery && matchKat) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        const emptyRow = document.getElementById('emptyAktivitasRow');
        if (emptyRow) {
            emptyRow.style.display = visibleCount === 0 ? '' : 'none';
        }

        if (countEl) {
            countEl.textContent = `${visibleCount} Log`;
        }
    }

    if (searchInput) {
        searchInput.addEventListener('input', applyAktivitasFilter);
    }
    if (filterKategori) {
        filterKategori.addEventListener('change', applyAktivitasFilter);
    }
}

/**
 * Filter & Live Search untuk Datatable 2: Log Khusus Presensi Harian Siswa Perwalian
 */
function initTablePresensiFilter() {
    const searchInput = document.getElementById('searchPresensi');
    const filterStatus = document.getElementById('filterStatusPresensi');
    const tbody = document.getElementById('tbodyPresensi');
    const countEl = document.getElementById('countPresensiFilter');

    if (!tbody) return;

    function applyPresensiFilter() {
        const query = searchInput ? searchInput.value.toLowerCase().trim() : '';
        const st = filterStatus ? filterStatus.value : '';
        const rows = tbody.querySelectorAll('tr.row-presensi');

        let visibleCount = 0;
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            const rowStatus = row.getAttribute('data-status') || '';

            const matchQuery = !query || text.includes(query);
            const matchStatus = !st || rowStatus === st;

            if (matchQuery && matchStatus) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        const emptyRow = document.getElementById('emptyPresensiRow');
        if (emptyRow) {
            emptyRow.style.display = visibleCount === 0 ? '' : 'none';
        }

        if (countEl) {
            countEl.textContent = `${visibleCount} Log`;
        }
    }

    if (searchInput) {
        searchInput.addEventListener('input', applyPresensiFilter);
    }
    if (filterStatus) {
        filterStatus.addEventListener('change', applyPresensiFilter);
    }
}
