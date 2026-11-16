/**
 * SAE — Log Aktivitas & Monitoring Realtime Database
 * Frontend controller untuk live search, detail modal, pembersihan log, dan grafik realtime.
 */

(function () {
    'use strict';

    function getCsrfToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    // Instance Chart.js
    let hourlyChartInstance = null;
    let crudChartInstance = null;
    let modulesChartInstance = null;
    let bidangChartInstance = null;
    let menuChartInstance = null;
    let fiturChartInstance = null;
    let livePollingTimer = null;
    let isLiveActive = true;
    let lastKnownMaxId = 0;

    // Deteksi tema tampilan
    function getThemeColors() {
        const isLight = document.documentElement.getAttribute('data-theme') === 'light';
        return {
            textColor: isLight ? '#0f172a' : '#f8fafc',
            textMuted: isLight ? '#64748b' : '#94a3b8',
            gridColor: isLight ? 'rgba(0, 0, 0, 0.06)' : 'rgba(255, 255, 255, 0.07)',
            bgCard: isLight ? '#ffffff' : '#1e293b',
            primary: '#3b82f6',
            success: '#10b981',
            warning: '#f59e0b',
            danger: '#ef4444',
            accent: '#06b6d4',
        };
    }

    // Inisialisasi Grafik Interaktif Chart.js
    function initMonitoringCharts() {
        if (typeof Chart === 'undefined') return;

        const payloadEl = document.getElementById('monitoringChartPayload');
        if (!payloadEl) return;

        let payload = {};
        try {
            payload = JSON.parse(payloadEl.textContent);
        } catch (e) {
            console.error('Gagal parsing payload grafik monitoring:', e);
            return;
        }

        const colors = getThemeColors();
        Chart.defaults.font.family = 'Inter, Segoe UI, system-ui, sans-serif';
        Chart.defaults.color = colors.textMuted;

        // 1. Grafik Tren Aktivitas 24 Jam (Line Chart)
        const hourlyCtx = document.getElementById('chartHourlyAktivitas');
        if (hourlyCtx && payload.hourly) {
            hourlyChartInstance = new Chart(hourlyCtx.getContext('2d'), {
                type: 'line',
                data: {
                    labels: payload.hourly.labels,
                    datasets: [
                        {
                            label: 'CREATE',
                            data: payload.hourly.create,
                            borderColor: colors.success,
                            backgroundColor: 'rgba(16, 185, 129, 0.12)',
                            fill: true,
                            tension: 0.35,
                            borderWidth: 2,
                            pointRadius: 2,
                            pointHoverRadius: 5,
                        },
                        {
                            label: 'UPDATE',
                            data: payload.hourly.update,
                            borderColor: colors.warning,
                            backgroundColor: 'rgba(245, 158, 11, 0.08)',
                            fill: true,
                            tension: 0.35,
                            borderWidth: 2,
                            pointRadius: 2,
                            pointHoverRadius: 5,
                        },
                        {
                            label: 'DELETE',
                            data: payload.hourly.delete,
                            borderColor: colors.danger,
                            backgroundColor: 'rgba(239, 68, 68, 0.08)',
                            fill: true,
                            tension: 0.35,
                            borderWidth: 2,
                            pointRadius: 2,
                            pointHoverRadius: 5,
                        },
                        {
                            label: 'READ',
                            data: payload.hourly.read,
                            borderColor: colors.primary,
                            backgroundColor: 'rgba(59, 130, 246, 0.06)',
                            fill: false,
                            tension: 0.35,
                            borderWidth: 1.5,
                            borderDash: [4, 4],
                            pointRadius: 1,
                            pointHoverRadius: 4,
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false,
                    },
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                boxWidth: 12,
                                boxHeight: 12,
                                usePointStyle: true,
                                pointStyle: 'circle',
                                font: { size: 11, weight: '600' },
                                color: colors.textColor
                            }
                        },
                        tooltip: {
                            padding: 10,
                            boxPadding: 4,
                            usePointStyle: true,
                            titleFont: { size: 12, weight: '700' },
                            bodyFont: { size: 11 }
                        }
                    },
                    scales: {
                        x: {
                            grid: { color: colors.gridColor },
                            ticks: {
                                maxRotation: 0,
                                autoSkip: true,
                                maxTicksLimit: 12,
                                font: { size: 10 }
                            }
                        },
                        y: {
                            beginAtZero: true,
                            grid: { color: colors.gridColor },
                            ticks: {
                                precision: 0,
                                font: { size: 10 }
                            }
                        }
                    }
                }
            });
        }

        // 2. Grafik Proporsi Operasi CRUD (Doughnut Chart)
        const crudCtx = document.getElementById('chartCrudDistribution');
        if (crudCtx && payload.crud) {
            crudChartInstance = new Chart(crudCtx.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: payload.crud.labels,
                    datasets: [{
                        data: payload.crud.data,
                        backgroundColor: [colors.success, colors.warning, colors.danger, colors.primary],
                        borderWidth: 2,
                        borderColor: colors.bgCard,
                        hoverOffset: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '62%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                boxWidth: 10,
                                boxHeight: 10,
                                usePointStyle: true,
                                pointStyle: 'circle',
                                font: { size: 10, weight: '600' },
                                color: colors.textColor,
                                padding: 8
                            }
                        },
                        tooltip: {
                            padding: 8,
                            callbacks: {
                                label: function (context) {
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0) || 1;
                                    const val = context.raw || 0;
                                    const pct = Math.round((val / total) * 100);
                                    return ` ${context.label}: ${val.toLocaleString('id-ID')} (${pct}%)`;
                                }
                            }
                        }
                    }
                }
            });
        }

        // 3. Grafik Modul Tersibuk (Horizontal Bar Chart)
        const modCtx = document.getElementById('chartTopModules');
        if (modCtx && payload.modules) {
            modulesChartInstance = new Chart(modCtx.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: payload.modules.labels.length > 0 ? payload.modules.labels : ['Sistem'],
                    datasets: [{
                        label: 'Transaksi',
                        data: payload.modules.data.length > 0 ? payload.modules.data : [0],
                        backgroundColor: 'rgba(99, 102, 241, 0.75)',
                        hoverBackgroundColor: 'rgba(99, 102, 241, 0.95)',
                        borderRadius: 4,
                        barThickness: 14,
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            padding: 8,
                            callbacks: {
                                label: function (ctx) {
                                    return ` ${ctx.raw} aktivitas`;
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            grid: { color: colors.gridColor },
                            ticks: { precision: 0, font: { size: 9 } }
                        },
                        y: {
                            grid: { display: false },
                            ticks: { font: { size: 10, weight: '600' }, color: colors.textColor }
                        }
                    }
                }
            });
        }
        // 4. Trafik per Bidang (Doughnut Chart)
        const bidangCtx = document.getElementById('chartBidangTraffic');
        if (bidangCtx && payload.bidang) {
            bidangChartInstance = new Chart(bidangCtx.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: payload.bidang.labels.length ? payload.bidang.labels : ['Belum ada data'],
                    datasets: [{
                        data: payload.bidang.data.length ? payload.bidang.data : [0],
                        backgroundColor: ['#3b82f6', '#06b6d4', '#8b5cf6', '#f59e0b', '#10b981', '#64748b'],
                        borderWidth: 2,
                        borderColor: colors.bgCard,
                        hoverOffset: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '58%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                boxWidth: 10,
                                boxHeight: 10,
                                usePointStyle: true,
                                pointStyle: 'circle',
                                font: { size: 10, weight: '600' },
                                color: colors.textColor,
                                padding: 8
                            }
                        },
                        tooltip: {
                            padding: 8,
                            callbacks: {
                                label: function (context) {
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0) || 1;
                                    const val = context.raw || 0;
                                    return ` ${context.label}: ${val.toLocaleString('id-ID')} (${Math.round((val / total) * 100)}%)`;
                                }
                            }
                        }
                    }
                }
            });
        }

        // 5. Trafik per Menu Dashboard (Horizontal Bar)
        const menuCtx = document.getElementById('chartMenuTraffic');
        if (menuCtx && payload.menu) {
            menuChartInstance = buildTrafficBar(menuCtx, payload.menu, 'rgba(6, 182, 212, 0.75)', colors);
        }

        // 6. Trafik per Fitur (Horizontal Bar)
        const fiturCtx = document.getElementById('chartFiturTraffic');
        if (fiturCtx && payload.fitur) {
            fiturChartInstance = buildTrafficBar(fiturCtx, payload.fitur, 'rgba(16, 185, 129, 0.75)', colors);
        }
    }

    // Grafik batang horizontal untuk dimensi trafik (menu / fitur)
    function buildTrafficBar(canvas, dataset, color, colors) {
        return new Chart(canvas.getContext('2d'), {
            type: 'bar',
            data: {
                labels: dataset.labels.length ? dataset.labels : ['Belum ada data'],
                datasets: [{
                    label: 'Aktivitas',
                    data: dataset.data.length ? dataset.data : [0],
                    backgroundColor: color,
                    borderRadius: 4,
                    barThickness: 14,
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        padding: 8,
                        callbacks: {
                            label: function (ctx) {
                                return ` ${ctx.raw} aktivitas`;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        grid: { color: colors.gridColor },
                        ticks: { precision: 0, font: { size: 9 } }
                    },
                    y: {
                        grid: { display: false },
                        ticks: { font: { size: 10, weight: '600' }, color: colors.textColor }
                    }
                }
            }
        });
    }

    // Sinkronkan data grafik trafik tanpa animasi ulang
    function syncTrafficBar(instance, dataset) {
        if (!instance || !dataset) return;
        instance.data.labels = dataset.labels.length ? dataset.labels : ['Belum ada data'];
        instance.data.datasets[0].data = dataset.data.length ? dataset.data : [0];
        instance.update('none');
    }

    // Perbarui angka kartu statistik dengan animasi highlight
    function updateStatValue(elId, newVal) {
        const el = document.getElementById(elId);
        if (!el) return;

        const currentText = el.textContent.trim().replace(/\D/g, '');
        const newTextStr = Number(newVal).toLocaleString('id-ID');

        if (currentText !== String(newVal)) {
            el.textContent = newTextStr;
            el.style.transition = 'all 0.3s cubic-bezier(0.4, 0, 0.2, 1)';
            el.style.transform = 'scale(1.18)';
            el.style.color = 'var(--accent, #06b6d4)';
            setTimeout(() => {
                el.style.transform = 'scale(1)';
                el.style.color = '';
            }, 700);
        }
    }

    // Sinkronisasi data realtime via endpoint /dashboard/aktivitas-database/live
    async function fetchRealtimeMonitoring() {
        if (!isLiveActive || document.hidden) return;

        const url = new URL(window.location.origin + '/dashboard/aktivitas-database/live');
        const startDate = document.getElementById('filterStartDate');
        const endDate = document.getElementById('filterEndDate');
        const rangePreset = document.querySelector('.btn-range-preset.active');

        if (startDate && startDate.value) url.searchParams.set('start_date', startDate.value);
        if (endDate && endDate.value) url.searchParams.set('end_date', endDate.value);
        if (rangePreset) url.searchParams.set('range', rangePreset.getAttribute('data-range'));

        try {
            const res = await fetch(url.toString(), {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!res.ok) return;
            const data = await res.json();
            if (data.status !== 'success') return;

            // 1. Update Stat Grid
            if (data.stats) {
                updateStatValue('statTotal', data.stats.total);
                updateStatValue('statToday', data.stats.today);
                updateStatValue('statCreate', data.stats.create);
                updateStatValue('statRead', data.stats.read);
                updateStatValue('statUpdate', data.stats.update);
                updateStatValue('statDelete', data.stats.delete);
            }

            // 2. Update Timestamp
            const tsEl = document.getElementById('chartHourlyTimestamp');
            if (tsEl && data.timestamp) {
                tsEl.textContent = data.timestamp;
            }

            // 3. Update Grafik Interaktif Chart.js
            if (data.charts) {
                if (hourlyChartInstance && data.charts.hourly) {
                    hourlyChartInstance.data.labels = data.charts.hourly.labels;
                    hourlyChartInstance.data.datasets[0].data = data.charts.hourly.create;
                    hourlyChartInstance.data.datasets[1].data = data.charts.hourly.update;
                    hourlyChartInstance.data.datasets[2].data = data.charts.hourly.delete;
                    hourlyChartInstance.data.datasets[3].data = data.charts.hourly.read;
                    hourlyChartInstance.update('none');
                }

                if (crudChartInstance && data.charts.crud) {
                    crudChartInstance.data.labels = data.charts.crud.labels;
                    crudChartInstance.data.datasets[0].data = data.charts.crud.data;
                    crudChartInstance.update('none');
                }

                if (modulesChartInstance && data.charts.modules) {
                    modulesChartInstance.data.labels = data.charts.modules.labels.length > 0 ? data.charts.modules.labels : ['Sistem'];
                    modulesChartInstance.data.datasets[0].data = data.charts.modules.data.length > 0 ? data.charts.modules.data : [0];
                    modulesChartInstance.update('none');
                }

                if (bidangChartInstance && data.charts.bidang) {
                    const b = data.charts.bidang;
                    bidangChartInstance.data.labels = b.labels.length > 0 ? b.labels : ['Belum ada data'];
                    bidangChartInstance.data.datasets[0].data = b.data.length > 0 ? b.data : [0];
                    bidangChartInstance.update('none');
                }

                syncTrafficBar(menuChartInstance, data.charts.menu);
                syncTrafficBar(fiturChartInstance, data.charts.fitur);
            }

            // 4. Deteksi data baru untuk memicu refresh tabel
            if (data.latest_id && lastKnownMaxId > 0 && data.latest_id > lastKnownMaxId) {
                const searchEl = document.getElementById('liveSearch');
                const isSearching = searchEl && searchEl.value.trim().length > 0;
                const isInputActive = document.activeElement && ['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement.tagName);

                if (!isSearching && !isInputActive) {
                    silentlyRefreshTable();
                }
            }

            if (data.latest_id) {
                lastKnownMaxId = data.latest_id;
            }
        } catch (err) {
            // silent fail on network hiccups
        }
    }

    // Muat ulang tabel data secara silent tanpa reload halaman utuh
    async function silentlyRefreshTable() {
        try {
            const res = await fetch(window.location.href, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'text/html'
                }
            });

            if (!res.ok) return;
            const html = await res.text();
            const doc = new DOMParser().parseFromString(html, 'text/html');

            const currentTable = document.querySelector('#tableDataContainer .table-responsive-stack');
            const newTable = doc.querySelector('#tableDataContainer .table-responsive-stack');
            if (currentTable && newTable) {
                currentTable.innerHTML = newTable.innerHTML;
            }

            const currentFooter = document.querySelector('#tableDataContainer .datatable-footer');
            const newFooter = doc.querySelector('#tableDataContainer .datatable-footer');
            if (currentFooter && newFooter) {
                currentFooter.innerHTML = newFooter.innerHTML;
            }
        } catch (e) {
            // ignore
        }
    }

    // Toggle live monitoring ON / OFF
    window.toggleLiveMonitoring = function () {
        isLiveActive = !isLiveActive;
        const btn = document.getElementById('btnToggleLive');
        const icon = document.getElementById('iconToggleLive');
        const indicator = document.getElementById('liveIndicator');
        const statusText = document.getElementById('liveStatusText');

        if (isLiveActive) {
            if (btn) {
                btn.style.color = 'var(--success, #10b981)';
                btn.style.borderColor = 'var(--success, #10b981)';
            }
            if (icon) icon.className = 'fas fa-tower-broadcast';
            if (indicator) {
                indicator.style.background = 'rgba(16, 185, 129, 0.12)';
                indicator.style.borderColor = 'rgba(16, 185, 129, 0.25)';
                indicator.style.color = 'var(--success, #10b981)';
            }
            if (statusText) statusText.textContent = 'LIVE MONITORING AKTIF';
            fetchRealtimeMonitoring();
        } else {
            if (btn) {
                btn.style.color = 'var(--text-muted, #94a3b8)';
                btn.style.borderColor = 'var(--border-color, #334155)';
            }
            if (icon) icon.className = 'fas fa-pause';
            if (indicator) {
                indicator.style.background = 'rgba(148, 163, 184, 0.12)';
                indicator.style.borderColor = 'rgba(148, 163, 184, 0.25)';
                indicator.style.color = 'var(--text-muted, #94a3b8)';
            }
            if (statusText) statusText.textContent = 'MONITORING DIJEDA';
        }
    };

    // Tampilkan rincian transaksi CRUD via SweetAlert2 modal
    window.showDetail = async function (id) {
        if (!window.Swal) return;

        Swal.fire({
            title: 'Memuat Rincian...',
            text: 'Mengambil data transaksi #' + id,
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        try {
            const res = await fetch('/dashboard/aktivitas-database/' + id, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!res.ok) throw new Error('Gagal mengambil data rincian transaksi.');
            const json = await res.json();
            if (json.status !== 'success' || !json.data) {
                throw new Error(json.message || 'Data tidak ditemukan.');
            }

            const d = json.data;
            const badge = d.crud_badge;
            const k = d.konteks || {
                bidang: 'Infrastruktur & Sistem',
                bidang_icon: 'fa-server',
                bidang_color: 'var(--secondary, #64748b)',
                bidang_bg: 'rgba(100, 116, 139, 0.12)',
                menu: 'Menu Utama > Monitoring',
                fitur: d.aktivitas,
                bagian: '-'
            };

            const html = `
                <div style="text-align: left; font-size: var(--text-sm); color: var(--text-color); line-height: 1.6;">
                    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); padding-bottom: 12px; margin-bottom: 14px;">
                        <div style="display: flex; gap: var(--space-2); align-items: center; flex-wrap: wrap;">
                            <span class="badge" style="background: ${badge.bg}; color: ${badge.color}; font-weight: 700; padding: var(--space-2) var(--space-4); border-radius: var(--radius-sm); font-size: var(--text-xs);">
                                <i class="fas ${badge.icon} me-1"></i> ${badge.label}
                            </span>
                            <span class="badge" style="background: ${k.bidang_bg}; color: ${k.bidang_color}; font-weight: 700; padding: var(--space-2) var(--space-4); border-radius: var(--radius-sm); font-size: var(--text-xs);">
                                <i class="fas ${k.bidang_icon} me-1"></i> ${k.bidang}
                            </span>
                        </div>
                        <span style="color: var(--text-muted); font-size: var(--text-xs);">
                            #ID: <strong>${d.id}</strong>
                        </span>
                    </div>

                    <table style="width: 100%; border-collapse: collapse; margin-bottom: 14px;">
                        <tr>
                            <td style="width: 130px; padding: 6px 0; color: var(--text-muted);"><i class="fas fa-layer-group me-1"></i> Menu Sistem</td>
                            <td style="padding: 6px 0; font-weight: 600;">${k.menu}</td>
                        </tr>
                        <tr>
                            <td style="padding: 6px 0; color: var(--text-muted);"><i class="fas fa-puzzle-piece me-1"></i> Fitur</td>
                            <td style="padding: 6px 0; font-weight: 600;">${k.fitur}</td>
                        </tr>
                        <tr>
                            <td style="padding: 6px 0; color: var(--text-muted);"><i class="fas fa-bullseye me-1"></i> Bagian / Target</td>
                            <td style="padding: 6px 0;"><span class="badge badge-outline" style="padding: var(--space-1) var(--space-2); border-radius: var(--radius-sm); font-size: var(--text-xs);"><i class="fas fa-tag me-1 text-primary"></i> ${k.bagian}</span></td>
                        </tr>
                        <tr>
                            <td style="width: 130px; padding: 6px 0; color: var(--text-muted);"><i class="fas fa-clock me-1"></i> Waktu</td>
                            <td style="padding: 6px 0;"><strong>${d.waktu}</strong> <span style="color: var(--text-muted); font-size: var(--text-xs);">(${d.waktu_relatif})</span></td>
                        </tr>
                        <tr>
                            <td style="padding: 6px 0; color: var(--text-muted);"><i class="fas fa-user-circle me-1"></i> Aktor / Pengguna</td>
                            <td style="padding: 6px 0;"><strong>${d.admin_name}</strong></td>
                        </tr>
                        <tr>
                            <td style="padding: 6px 0; color: var(--text-muted);"><i class="fas fa-id-badge me-1"></i> Akun / User ID</td>
                            <td style="padding: 6px 0;"><code>${d.admin_username}</code></td>
                        </tr>
                        <tr>
                            <td style="padding: 6px 0; color: var(--text-muted);"><i class="fas fa-network-wired me-1"></i> Alamat IP</td>
                            <td style="padding: 6px 0;"><code>${d.ip_address}</code></td>
                        </tr>
                        <tr>
                            <td style="padding: 6px 0; color: var(--text-muted);"><i class="fas fa-bolt me-1"></i> Aktivitas Lengkap</td>
                            <td style="padding: 6px 0; font-weight: 600;">${d.aktivitas}</td>
                        </tr>
                    </table>

                    <div style="background: var(--bg-hover); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 12px 14px;">
                        <div style="font-weight: 700; font-size: var(--text-xs); color: var(--text-muted); text-transform: uppercase; margin-bottom: 6px;">
                            <i class="fas fa-file-lines me-1"></i> Keterangan / Payload Transaksi
                        </div>
                        <div style="white-space: pre-wrap; word-break: break-word; font-family: inherit; font-size: var(--text-sm); color: var(--text-color);">
                            ${d.keterangan}
                        </div>
                    </div>
                </div>
            `;

            Swal.fire({
                title: 'Rincian Transaksi Database',
                html: html,
                width: 620,
                confirmButtonText: 'Tutup',
                confirmButtonColor: 'var(--primary, #3b82f6)',
                customClass: { popup: 'sae-swal-modal' }
            });
        } catch (err) {
            Swal.fire({
                icon: 'error',
                title: 'Gagal Memuat Detail',
                text: err.message || 'Terjadi kesalahan sistem.',
                confirmButtonColor: 'var(--primary, #3b82f6)'
            });
        }
    };

    // Hapus satu entri log aktivitas
    window.deleteLog = async function (id) {
        if (!window.Swal) return;

        const resConfirm = await Swal.fire({
            title: 'Hapus Baris Log?',
            text: 'Entri riwayat aktivitas #' + id + ' akan dihapus permanen dari basis data.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, Hapus',
            cancelButtonText: 'Batal',
            confirmButtonColor: 'var(--danger, #ef4444)',
            cancelButtonColor: 'var(--secondary, #64748b)'
        });

        if (!resConfirm.isConfirmed) return;

        try {
            const res = await fetch('/dashboard/aktivitas-database/' + id, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const json = await res.json();
            if (!res.ok || json.status !== 'success') {
                throw new Error(json.message || 'Gagal menghapus entri log.');
            }

            Swal.fire({
                icon: 'success',
                title: 'Terhapus',
                text: json.message,
                timer: 1500,
                showConfirmButton: false
            });

            silentlyRefreshTable();
            fetchRealtimeMonitoring();
        } catch (err) {
            Swal.fire({
                icon: 'error',
                title: 'Gagal',
                text: err.message || 'Terjadi kesalahan saat menghapus data.',
                confirmButtonColor: 'var(--primary, #3b82f6)'
            });
        }
    };

    // Buka dialog konfirmasi pembersihan massal (Purge)
    window.openPurgeModal = async function () {
        if (!window.Swal) return;

        const { value: range } = await Swal.fire({
            title: 'Pembersihan Massal Log',
            html: `
                <div style="text-align: left; font-size: var(--text-sm); color: var(--text-color); margin-bottom: 12px;">
                    Pilih batas usia riwayat log aktivitas database yang ingin dibersihkan:
                </div>
            `,
            input: 'select',
            inputOptions: {
                '90': 'Lebih dari 90 hari yang lalu (Disarankan)',
                '60': 'Lebih dari 60 hari yang lalu',
                '30': 'Lebih dari 30 hari yang lalu',
                'all': 'Seluruh riwayat log (Bersihkan Semua)'
            },
            inputValue: '90',
            showCancelButton: true,
            confirmButtonText: 'Lanjutkan Pembersihan',
            cancelButtonText: 'Batal',
            confirmButtonColor: 'var(--danger, #ef4444)',
            cancelButtonColor: 'var(--secondary, #64748b)'
        });

        if (!range) return;

        const confirmFinal = await Swal.fire({
            title: 'Konfirmasi Penghapusan',
            text: range === 'all'
                ? 'PERINGATAN: Seluruh riwayat transaksi log database akan dikosongkan. Tindakan ini tidak dapat dibatalkan.'
                : 'Data log transaksi lebih dari ' + range + ' hari akan dihapus permanen.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Bersihkan Sekarang',
            cancelButtonText: 'Batal',
            confirmButtonColor: 'var(--danger, #ef4444)',
            cancelButtonColor: 'var(--secondary, #64748b)'
        });

        if (!confirmFinal.isConfirmed) return;

        Swal.fire({
            title: 'Membersihkan Log...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        try {
            const res = await fetch('/dashboard/aktivitas-database/purge', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ range: range })
            });

            const json = await res.json();
            if (!res.ok || json.status !== 'success') {
                throw new Error(json.message || 'Pembersihan log gagal.');
            }

            await Swal.fire({
                icon: 'success',
                title: 'Pembersihan Selesai',
                text: json.message,
                confirmButtonColor: 'var(--primary, #3b82f6)'
            });

            window.location.reload();
        } catch (err) {
            Swal.fire({
                icon: 'error',
                title: 'Gagal',
                text: err.message || 'Terjadi kesalahan sistem saat pembersihan log.',
                confirmButtonColor: 'var(--primary, #3b82f6)'
            });
        }
    };

    // Filter & search helper
    function updateFilters() {
        const url = new URL(window.location.href);

        const perPage = document.getElementById('perPageSelect');
        if (perPage) url.searchParams.set('perPage', perPage.value);

        const bidang = document.getElementById('filterBidang');
        if (bidang) {
            if (bidang.value) url.searchParams.set('bidang', bidang.value);
            else url.searchParams.delete('bidang');
        }

        const modul = document.getElementById('filterModul');
        if (modul) {
            if (modul.value) url.searchParams.set('modul', modul.value);
            else url.searchParams.delete('modul');
        }

        const startDate = document.getElementById('filterStartDate');
        if (startDate) {
            if (startDate.value) url.searchParams.set('start_date', startDate.value);
            else url.searchParams.delete('start_date');
        }

        const endDate = document.getElementById('filterEndDate');
        if (endDate) {
            if (endDate.value) url.searchParams.set('end_date', endDate.value);
            else url.searchParams.delete('end_date');
        }

        const search = document.getElementById('liveSearch');
        if (search) {
            if (search.value.trim()) url.searchParams.set('q', search.value.trim());
            else url.searchParams.delete('q');
        }

        url.searchParams.delete('page');
        window.location.href = url.toString();
    }

    let searchTimer = null;

    document.addEventListener('DOMContentLoaded', function () {
        // 1. Inisialisasi Grafik
        initMonitoringCharts();

        // 2. Mulai Polling Realtime (interval 5 detik)
        livePollingTimer = setInterval(fetchRealtimeMonitoring, 5000);

        // 3. Tangani Realtime Event dari Bus Global SAE
        window.addEventListener('sae:realtime', function () {
            fetchRealtimeMonitoring();
        });

        // 4. Filter events
        const perPage = document.getElementById('perPageSelect');
        if (perPage) perPage.addEventListener('change', updateFilters);

        const filterBidang = document.getElementById('filterBidang');
        if (filterBidang) filterBidang.addEventListener('change', updateFilters);

        const filterModul = document.getElementById('filterModul');
        if (filterModul) filterModul.addEventListener('change', updateFilters);

        const filterStartDate = document.getElementById('filterStartDate');
        if (filterStartDate) filterStartDate.addEventListener('change', updateFilters);

        const filterEndDate = document.getElementById('filterEndDate');
        if (filterEndDate) filterEndDate.addEventListener('change', updateFilters);

        const liveSearch = document.getElementById('liveSearch');
        const clearSearch = document.getElementById('clearSearch');

        if (liveSearch) {
            liveSearch.addEventListener('input', function () {
                if (clearSearch) {
                    clearSearch.classList.toggle('visible', !!liveSearch.value.trim());
                }
                clearTimeout(searchTimer);
                searchTimer = setTimeout(updateFilters, 400);
            });
        }

        if (clearSearch && liveSearch) {
            clearSearch.addEventListener('click', function () {
                liveSearch.value = '';
                clearSearch.classList.remove('visible');
                updateFilters();
            });
        }

        // 5. Tangani preset rentang tanggal (Hari Ini, 7 Hari, 30 Hari, Semua)
        document.addEventListener('click', function (e) {
            const btnRange = e.target.closest('.btn-range-preset');
            if (btnRange) {
                e.preventDefault();
                const targetRange = btnRange.getAttribute('data-range');
                const url = new URL(window.location.href);
                url.searchParams.delete('page');

                if (targetRange === 'today') {
                    const todayStr = new Date().toISOString().slice(0, 10);
                    url.searchParams.set('start_date', todayStr);
                    url.searchParams.set('end_date', todayStr);
                    url.searchParams.delete('range');
                    url.searchParams.delete('all_time');
                } else if (targetRange === 'all') {
                    url.searchParams.set('start_date', '');
                    url.searchParams.set('end_date', '');
                    url.searchParams.set('all_time', '1');
                    url.searchParams.delete('range');
                } else {
                    url.searchParams.set('range', targetRange);
                    url.searchParams.delete('start_date');
                    url.searchParams.delete('end_date');
                    url.searchParams.delete('all_time');
                }

                window.location.href = url.toString();
            }
        });
    });
})();
