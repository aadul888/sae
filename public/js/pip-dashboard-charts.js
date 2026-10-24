// =========================================================================
// PIP DASHBOARD CHARTS & ANALYTICS (Chart.js & Rekap Filter)
// =========================================================================
document.addEventListener("DOMContentLoaded", function () {
    window._pipCdInterval = null;

    function initPipCountdown() {
        const wrap = document.getElementById("pipCountdownWrap");
        if (!wrap) return;

        if (window._pipCdInterval) {
            clearInterval(window._pipCdInterval);
            window._pipCdInterval = null;
        }

        const deadlineIso = wrap.getAttribute("data-deadline");
        const isLocked = wrap.getAttribute("data-locked") === "1";

        const daysEl = document.getElementById("pipCdDays");
        const hoursEl = document.getElementById("pipCdHours");
        const minsEl = document.getElementById("pipCdMinutes");
        const secsEl = document.getElementById("pipCdSeconds");
        const badgeEl = document.getElementById("pipCdBadge");

        if (isLocked || !deadlineIso) return;

        const targetTime = new Date(deadlineIso).getTime();

        function tick() {
            const now = new Date().getTime();
            const diff = targetTime - now;

            if (diff <= 0) {
                if (daysEl) daysEl.textContent = "00";
                if (hoursEl) hoursEl.textContent = "00";
                if (minsEl) minsEl.textContent = "00";
                if (secsEl) secsEl.textContent = "00";
                if (badgeEl) {
                    badgeEl.className = "badge badge-danger";
                    badgeEl.innerHTML = '<i class="fas fa-clock-rotate-left me-1"></i> Batas Waktu Berakhir';
                }
                return;
            }

            const totalSec = Math.floor(diff / 1000);
            const d = Math.floor(totalSec / 86400);
            const h = Math.floor((totalSec % 86400) / 3600);
            const m = Math.floor((totalSec % 3600) / 60);
            const s = totalSec % 60;

            if (daysEl) daysEl.textContent = String(d).padStart(2, "0");
            if (hoursEl) hoursEl.textContent = String(h).padStart(2, "0");
            if (minsEl) minsEl.textContent = String(m).padStart(2, "0");
            if (secsEl) secsEl.textContent = String(s).padStart(2, "0");
        }

        tick();
        window._pipCdInterval = setInterval(tick, 1000);
    }

    window._pipChartStatus = null;
    window._pipChartAlasan = null;
    window._pipChartTingkat = null;
    window._pipChartRiwayatTahun = null;

    function initPipDashboardCharts() {
        const payloadEl = document.getElementById("pipChartPayload");
        if (!payloadEl || typeof Chart === "undefined") return;

        let payload = {};
        try {
            payload = JSON.parse(payloadEl.textContent || "{}");
        } catch (e) {
            return;
        }

        const isDark = document.documentElement.getAttribute("data-theme") === "dark" ||
            document.body.classList.contains("dark") ||
            (window.matchMedia && window.matchMedia("(prefers-color-scheme: dark)").matches);

        const textColor = isDark ? "#e2e8f0" : "#1e293b";
        const textMuted = isDark ? "#94a3b8" : "#64748b";
        const borderColor = isDark ? "rgba(255,255,255,0.08)" : "rgba(0,0,0,0.06)";

        // 1. Chart Status Usulan (Doughnut)
        const ctxStatus = document.getElementById("pipChartStatus");
        if (ctxStatus && payload.status) {
            if (window._pipChartStatus) window._pipChartStatus.destroy();
            const totalStatus = (payload.status.data || []).reduce((a, b) => a + b, 0);
            const hasData = totalStatus > 0;

            window._pipChartStatus = new Chart(ctxStatus, {
                type: "doughnut",
                data: {
                    labels: hasData ? payload.status.labels : ["Belum Ada Data"],
                    datasets: [{
                        data: hasData ? payload.status.data : [1],
                        backgroundColor: hasData
                            ? payload.status.colors
                            : [isDark ? "#334155" : "#e2e8f0"],
                        borderWidth: 2,
                        borderColor: isDark ? "#1e293b" : "#ffffff",
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: "68%",
                    plugins: {
                        legend: {
                            position: "bottom",
                            labels: {
                                color: textColor,
                                font: { size: 11, weight: 600 },
                                padding: 12,
                                boxWidth: 12,
                                boxHeight: 12,
                            },
                        },
                        tooltip: {
                            enabled: hasData,
                            callbacks: {
                                label: (item) => {
                                    const val = item.raw || 0;
                                    const pct = totalStatus > 0 ? ((val / totalStatus) * 100).toFixed(1) : 0;
                                    return ` ${item.label}: ${val} siswa (${pct}%)`;
                                },
                            },
                        },
                    },
                },
            });
        }

        // 2. Chart Alasan Kriteria Usulan (Horizontal Bar)
        const ctxAlasan = document.getElementById("pipChartAlasan");
        if (ctxAlasan && payload.alasan) {
            if (window._pipChartAlasan) window._pipChartAlasan.destroy();
            const totalAlasan = (payload.alasan.data || []).reduce((a, b) => a + b, 0);
            const hasData = totalAlasan > 0;

            window._pipChartAlasan = new Chart(ctxAlasan, {
                type: "bar",
                data: {
                    labels: hasData ? payload.alasan.labels : ["Belum Ada Data"],
                    datasets: [{
                        label: "Jumlah Siswa",
                        data: hasData ? payload.alasan.data : [0],
                        backgroundColor: hasData
                            ? payload.alasan.colors
                            : ["#94a3b8"],
                        borderRadius: 6,
                        maxBarThickness: 24,
                    }],
                },
                options: {
                    indexAxis: "y",
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            enabled: hasData,
                            callbacks: {
                                label: (item) => ` ${item.raw} siswa`,
                            },
                        },
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            ticks: { precision: 0, color: textMuted },
                            grid: { color: borderColor },
                        },
                        y: {
                            ticks: { color: textColor, font: { weight: 600, size: 11 } },
                            grid: { display: false },
                        },
                    },
                },
            });
        }

        // 3. Chart Kuota vs Usulan per Tingkat (Grouped Bar)
        const ctxTingkat = document.getElementById("pipChartTingkat");
        if (ctxTingkat && payload.tingkat) {
            if (window._pipChartTingkat) window._pipChartTingkat.destroy();

            window._pipChartTingkat = new Chart(ctxTingkat, {
                type: "bar",
                data: {
                    labels: payload.tingkat.labels || ["Tingkat X", "Tingkat XI", "Tingkat XII"],
                    datasets: [
                        {
                            label: "Usulan Pengajuan",
                            data: payload.tingkat.usulan || [0, 0, 0],
                            backgroundColor: "rgba(99, 102, 241, 0.85)",
                            borderRadius: 6,
                            maxBarThickness: 28,
                        },
                        {
                            label: "Kuota Disediakan",
                            data: payload.tingkat.kuota || [0, 0, 0],
                            backgroundColor: "rgba(16, 185, 129, 0.85)",
                            borderRadius: 6,
                            maxBarThickness: 28,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: "top",
                            labels: {
                                color: textColor,
                                font: { size: 11, weight: 600 },
                                padding: 12,
                                boxWidth: 12,
                            },
                        },
                        tooltip: {
                            callbacks: {
                                label: (item) => ` ${item.dataset.label}: ${item.raw} siswa`,
                            },
                        },
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { precision: 0, color: textMuted },
                            grid: { color: borderColor },
                        },
                        x: {
                            ticks: { color: textColor, font: { weight: 700 } },
                            grid: { display: false },
                        },
                    },
                },
            });
        }

        // 4. Chart Tren Riwayat Pencairan PIP per Tahun (Bar Chart)
        const ctxRiwayat = document.getElementById("pipChartRiwayatTahun");
        if (ctxRiwayat && payload.riwayat_tahun) {
            if (window._pipChartRiwayatTahun) window._pipChartRiwayatTahun.destroy();
            const hasRiwayat = (payload.riwayat_tahun.counts || []).some((c) => c > 0);

            const pipRiwayatBarLabelsPlugin = {
                id: "pipRiwayatBarLabels",
                afterDatasetsDraw(chart) {
                    const ctx = chart.ctx;
                    chart.data.datasets.forEach((dataset, i) => {
                        const meta = chart.getDatasetMeta(i);
                        meta.data.forEach((bar, index) => {
                            const count = dataset.data[index] || 0;
                            if (count <= 0) return;
                            const nominal = (payload.riwayat_tahun.nominals && payload.riwayat_tahun.nominals[index]) || 0;
                            let formattedNom = "";
                            if (nominal >= 1000000000) {
                                formattedNom = "Rp " + (nominal / 1000000000).toFixed(1) + " M";
                            } else if (nominal >= 1000000) {
                                formattedNom = "Rp " + (nominal / 1000000).toFixed(1) + " Jt";
                            }
                            ctx.save();
                            ctx.fillStyle = isDark ? "#f1f5f9" : "#0f172a";
                            ctx.font = "bold 10px sans-serif";
                            ctx.textAlign = "center";
                            ctx.fillText(count + " Siswa", bar.x, bar.y - 14);
                            if (formattedNom) {
                                ctx.font = "normal 9px sans-serif";
                                ctx.fillStyle = textMuted;
                                ctx.fillText(formattedNom, bar.x, bar.y - 3);
                            }
                            ctx.restore();
                        });
                    });
                },
            };

            window._pipChartRiwayatTahun = new Chart(ctxRiwayat, {
                type: "bar",
                data: {
                    labels: hasRiwayat ? payload.riwayat_tahun.labels : ["Belum Ada Data"],
                    datasets: [{
                        label: "Penerima PIP (Siswa)",
                        data: hasRiwayat ? payload.riwayat_tahun.counts : [0],
                        backgroundColor: "rgba(16, 185, 129, 0.85)",
                        borderRadius: 6,
                        maxBarThickness: 32,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    layout: { padding: { top: 18 } },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: (item) => {
                                    const count = item.raw || 0;
                                    const nominal = payload.riwayat_tahun.nominals ? (payload.riwayat_tahun.nominals[item.dataIndex] || 0) : 0;
                                    const nomStr = new Intl.NumberFormat("id-ID").format(nominal);
                                    return ` ${count} siswa (Rp ${nomStr})`;
                                },
                            },
                        },
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grace: "15%",
                            ticks: { precision: 0, color: textMuted },
                            grid: { color: borderColor },
                        },
                        x: {
                            ticks: { color: textColor, font: { weight: 700 } },
                            grid: { display: false },
                        },
                    },
                },
                plugins: hasRiwayat ? [pipRiwayatBarLabelsPlugin] : [],
            });
        }
    }

    function initPipRekapRombelFilter() {
        const filterStatusBtns = document.querySelectorAll(".btn-rekap-filter");
        const filterTingkatBtns = document.querySelectorAll(".btn-rekap-tingkat-filter");
        const searchInp = document.getElementById("filterRekapRombel");
        if (!filterStatusBtns.length && !filterTingkatBtns.length && !searchInp) return;

        let currentStatus = "all";
        let currentTingkat = "all";
        let searchQuery = "";

        function applyRekapFilter() {
            const rows = document.querySelectorAll(".rekap-rombel-row");
            let visIndex = 1;
            rows.forEach((row) => {
                const status = row.getAttribute("data-status");
                const tingkat = row.getAttribute("data-tingkat");
                const name = row.getAttribute("data-name") || "";
                const matchStatus = currentStatus === "all" || status === currentStatus;
                const matchTingkat = currentTingkat === "all" || tingkat === currentTingkat;
                const matchSearch = searchQuery === "" || name.includes(searchQuery);
                const isVisible = matchStatus && matchTingkat && matchSearch;
                row.style.display = isVisible ? "" : "none";
                if (isVisible) {
                    const noCell = row.querySelector(".rekap-row-no");
                    if (noCell) noCell.textContent = visIndex++;
                }
            });
        }

        filterStatusBtns.forEach((btn) => {
            btn.addEventListener("click", function () {
                filterStatusBtns.forEach((b) => {
                    b.classList.remove("active");
                    b.style.background = "transparent";
                    b.style.color = "var(--text-muted)";
                });
                this.classList.add("active");
                this.style.background = "var(--primary)";
                this.style.color = "#fff";
                currentStatus = this.getAttribute("data-status") || "all";
                applyRekapFilter();
            });
        });

        filterTingkatBtns.forEach((btn) => {
            btn.addEventListener("click", function () {
                filterTingkatBtns.forEach((b) => {
                    b.classList.remove("active");
                    b.style.background = "transparent";
                    b.style.color = "var(--text-muted)";
                });
                this.classList.add("active");
                this.style.background = "var(--primary)";
                this.style.color = "#fff";
                currentTingkat = this.getAttribute("data-tingkat") || "all";
                applyRekapFilter();
            });
        });

        if (searchInp) {
            searchInp.addEventListener("input", function () {
                searchQuery = this.value.trim().toLowerCase();
                applyRekapFilter();
            });
        }
    }

    initPipDashboardCharts();
    initPipRekapRombelFilter();
    initPipCountdown();
    window.addEventListener("sae:tableRefreshed", function () {
        initPipDashboardCharts();
        initPipRekapRombelFilter();
        initPipCountdown();
    });
});
