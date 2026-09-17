/**
 * SAE Realtime Client (Server-Sent Events with Polling Fallback)
 *
 * ponytail: browser EventSource + polling fallback. Upgrade to Laravel Echo + Reverb if bi-directional socket channels are required.
 */
(function () {
    if (window.__saeRealtimeInitialized) return;
    window.__saeRealtimeInitialized = true;

    let eventSource = null;
    let lastEventId = null;
    let pollTimer = null;
    let isConnected = false;
    let errorCount = 0;
    let isRefreshingCards = false;

    const streamUrl = "/dashboard/realtime/stream";
    const pollUrl = "/dashboard/realtime/poll";

    function showToast(title, message, icon = "bullhorn", link = null) {
        let container = document.getElementById("saeRealtimeToasts");
        if (!container) {
            container = document.createElement("div");
            container.id = "saeRealtimeToasts";
            container.style.cssText =
                "position: fixed; bottom: 24px; right: 24px; z-index: 999999; display: flex; flex-direction: column; gap: 10px; max-width: 360px; pointer-events: none;";
            document.body.appendChild(container);
        }

        const toast = document.createElement("div");
        toast.style.cssText =
            "background: var(--bg-card, #1e293b); color: var(--text-color, #f8fafc); border: 1px solid var(--primary, #6366f1); border-left: 4px solid var(--primary, #6366f1); border-radius: 10px; padding: 12px 16px; box-shadow: 0 10px 25px rgba(0,0,0,0.3); pointer-events: auto; transform: translateY(20px); opacity: 0; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); cursor: pointer;";
        toast.innerHTML = `
            <div style="display: flex; align-items: flex-start; gap: 12px;">
                <div style="width: 32px; height: 32px; border-radius: 8px; background: rgba(99, 102, 241, 0.15); display: flex; align-items: center; justify-content: center; color: var(--primary, #6366f1); flex-shrink: 0;">
                    <i class="fas fa-${icon}"></i>
                </div>
                <div style="flex: 1; min-width: 0;">
                    <div style="font-weight: 700; font-size: 0.85rem; margin-bottom: 2px; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;">${title}</div>
                    <div style="font-size: 0.78rem; color: var(--text-muted, #94a3b8); line-height: 1.35; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">${message}</div>
                </div>
            </div>
        `;

        if (link) {
            toast.addEventListener("click", () => {
                window.location.href = link;
            });
        }

        container.appendChild(toast);
        requestAnimationFrame(() => {
            toast.style.transform = "translateY(0)";
            toast.style.opacity = "1";
        });

        setTimeout(() => {
            toast.style.transform = "translateY(20px)";
            toast.style.opacity = "0";
            setTimeout(() => {
                if (toast.parentNode) toast.parentNode.removeChild(toast);
            }, 300);
        }, 6000);
    }

    /**
     * Memperbarui angka card rekap di setiap modul dashboard secara live tanpa reload
     */
    async function updateRekapCards(options = {}) {
        if (isRefreshingCards) return;
        if (document.hidden) return;

        const currentGrid = document.querySelector(
            ".dash-stat-grid, .form-stat-grid",
        );
        if (!currentGrid) return;

        isRefreshingCards = true;
        try {
            const res = await fetch(window.location.href, {
                credentials: "same-origin",
                headers: {
                    "X-Requested-With": "XMLHttpRequest",
                    Accept: "text/html, application/xhtml+xml",
                },
            });

            if (!res.ok) return;

            const html = await res.text();
            const doc = new DOMParser().parseFromString(html, "text/html");
            const newGrid = doc.querySelector(
                ".dash-stat-grid, .form-stat-grid",
            );

            if (newGrid && currentGrid) {
                const oldVals = Array.from(
                    currentGrid.querySelectorAll(
                        ".dash-stat-value, .kpi-num, .dash-stat-info > div:first-child",
                    ),
                );
                const newVals = Array.from(
                    newGrid.querySelectorAll(
                        ".dash-stat-value, .kpi-num, .dash-stat-info > div:first-child",
                    ),
                );

                if (oldVals.length === newVals.length && oldVals.length > 0) {
                    oldVals.forEach((oldEl, i) => {
                        const newEl = newVals[i];
                        const oldText = oldEl.textContent.trim();
                        const newText = newEl.textContent.trim();

                        if (oldText !== newText) {
                            oldEl.textContent = newText;
                            // Visual pulse animation agar perubahan realtime terlihat jelas
                            oldEl.style.transition =
                                "all 0.3s cubic-bezier(0.4, 0, 0.2, 1)";
                            oldEl.style.transform = "scale(1.15)";
                            oldEl.style.color = "var(--accent, #06b6d4)";
                            setTimeout(() => {
                                oldEl.style.transform = "scale(1)";
                                oldEl.style.color = "";
                            }, 800);
                        }
                    });
                } else {
                    currentGrid.innerHTML = newGrid.innerHTML;
                }
            }

            // Perbarui data tabel jika diminta dan tidak sedang membuka modal atau mengetik form
            if (options.refreshTable) {
                const isModalOpen = !!document.querySelector(
                    ".sae-modal[style*='flex'], .modal-backdrop[style*='flex'], .modal[style*='flex'], .modal[style*='block']",
                );
                const isInputActive =
                    document.activeElement &&
                    ["INPUT", "TEXTAREA", "SELECT"].includes(
                        document.activeElement.tagName,
                    );

                if (!isModalOpen && !isInputActive) {
                    const curTabBar = document.querySelector("#jadwalTabBarContainer");
                    const newTabBar = doc.querySelector("#jadwalTabBarContainer");
                    if (curTabBar && newTabBar) {
                        curTabBar.innerHTML = newTabBar.innerHTML;
                    }

                    const curTable = document.querySelector(
                        "#tableDataContainer, .table-responsive-stack",
                    );
                    const newTable = doc.querySelector(
                        "#tableDataContainer, .table-responsive-stack",
                    );
                    if (curTable && newTable) {
                        curTable.innerHTML = newTable.innerHTML;
                    }

                    const curGrid = document.querySelector(
                        "#gridMatrixContainer",
                    );
                    const newGridM = doc.querySelector("#gridMatrixContainer");
                    if (curGrid && newGridM) {
                        curGrid.innerHTML = newGridM.innerHTML;
                    }

                    // Dispatch event agar module-specific JS tahu ada pembaruan realtime
                    window.dispatchEvent(
                        new CustomEvent("sae:tableRefreshed", {
                            detail: { url: window.location.href, doc },
                        }),
                    );
                }
            }
        } catch (err) {
            // Abaikan kegagalan silent
        } finally {
            isRefreshingCards = false;
        }
    }

    function handleEvent(eventName, payload) {
        if (!payload) return;
        const data = payload.data || payload;

        // 1. Dispatch custom event ke DOM agar modul lain bisa bereaksi tanpa reload
        window.dispatchEvent(
            new CustomEvent("sae:realtime", {
                detail: { event: eventName, data: data, id: payload.id },
            }),
        );
        window.dispatchEvent(
            new CustomEvent(`sae:realtime:${eventName}`, {
                detail: data,
            }),
        );

        // 2. Handler otomatis Pengumuman Baru -> Update bell icon & badge tanpa reload
        if (eventName === "pengumuman.created") {
            const bellDot = document.getElementById("bellNotifDot");
            const headerBadge = document.getElementById("headerNotifBadge");
            const bellBtn = document.getElementById("notifBellBtn");

            if (!bellDot && bellBtn) {
                const newDot = document.createElement("span");
                newDot.id = "bellNotifDot";
                newDot.className = "dash-notif-badge";
                newDot.style.cssText =
                    "position: absolute; top: 4px; right: 4px; width: 8px; height: 8px; background: #ef4444; border-radius: 50%; border: 2px solid var(--nav-bg); box-shadow: 0 0 6px rgba(239, 68, 68, 0.8);";
                bellBtn.appendChild(newDot);
            }

            if (headerBadge) {
                const cur =
                    parseInt(headerBadge.textContent.replace(/\D/g, ""), 10) ||
                    0;
                headerBadge.textContent = `${cur + 1} Baru`;
            }

            showToast(
                "Pengumuman Baru",
                data.judul || "Ada pengumuman baru diterbitkan.",
                "bullhorn",
                "/dashboard/informasi",
            );
        }

        // 3. Handler otomatis Presensi Masuk / Pulang -> Update feed scanner tanpa reload
        if (eventName === "presensi.scanned") {
            const feed = document.getElementById("kioskRecentFeed");
            if (feed) {
                const emptyNotice = feed.querySelector(
                    'div[style*="text-align: center"]',
                );
                if (emptyNotice) emptyNotice.remove();

                const isPulang = data.action === "pulang";
                const statusBadge =
                    data.status === "H"
                        ? '<span class="badge badge-success" style="font-size: 0.7rem;">Hadir</span>'
                        : data.status === "T"
                          ? `<span class="badge badge-warning" style="font-size: 0.7rem;">+${data.menit_terlambat || ""}m</span>`
                          : `<span class="badge badge-primary" style="font-size: 0.7rem;">${data.status}</span>`;

                const item = document.createElement("div");
                item.className = "recent-scan-item";
                item.style.animation = "fadeInUp 0.3s ease-out";
                item.innerHTML = `
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <img src="${data.foto_url || "/img/logo-dark.png"}" class="recent-scan-avatar" onerror="this.src='/img/logo-dark.png';">
                        <div>
                            <div style="font-weight: 700; font-size: 0.88rem; color: var(--text-color);">${data.nama || "Peserta Didik"}</div>
                            <div style="font-size: 0.75rem; color: var(--text-muted);">${data.rombel || "-"} &bull; NISN: ${data.nisn || "-"}</div>
                        </div>
                    </div>
                    <div style="text-align: right;">
                        <div style="font-weight: 700; font-family: monospace; font-size: 0.85rem; color: var(--text-color);">
                            ${data.jam || "--:--"} WIB
                        </div>
                        <div>${statusBadge}</div>
                    </div>
                `;
                feed.prepend(item);

                // Batasi maksimal 15 item di feed tampilan
                while (feed.children.length > 15) {
                    feed.removeChild(feed.lastChild);
                }
            }
        }

        // 4. Perbarui otomatis card rekap (stat grid) di modul yang sedang terbuka
        updateRekapCards({
            refreshTable:
                eventName === "data.changed" ||
                eventName === "presensi.scanned" ||
                eventName === "jadwal.changed",
        });
    }

    function startSSE() {
        if (!window.EventSource) {
            startPolling();
            return;
        }

        const url = lastEventId
            ? `${streamUrl}?last_id=${encodeURIComponent(lastEventId)}`
            : streamUrl;
        eventSource = new EventSource(url);

        eventSource.onopen = function () {
            isConnected = true;
            errorCount = 0;
        };

        eventSource.onmessage = function (e) {
            try {
                const payload = JSON.parse(e.data);
                if (payload.id) lastEventId = payload.id;
                handleEvent(payload.event || "message", payload);
            } catch (err) {
                // Abaikan jika format data raw
            }
        };

        // Listen individual custom events emitted by SSE
        [
            "pengumuman.created",
            "pengumuman.updated",
            "presensi.scanned",
            "data.changed",
            "jadwal.changed",
        ].forEach((ev) => {
            eventSource.addEventListener(ev, function (e) {
                try {
                    const payload = JSON.parse(e.data);
                    if (payload.id) lastEventId = payload.id;
                    handleEvent(ev, payload);
                } catch (err) {}
            });
        });

        eventSource.onerror = function () {
            isConnected = false;
            errorCount++;
            if (eventSource) {
                eventSource.close();
                eventSource = null;
            }

            // Jika gagal 3x berturut-turut (misal firewall memblokir text/event-stream), beralih ke polling
            if (errorCount >= 3) {
                startPolling();
            } else {
                setTimeout(startSSE, 4000);
            }
        };
    }

    async function pollOnce() {
        try {
            const url = lastEventId
                ? `${pollUrl}?last_id=${encodeURIComponent(lastEventId)}`
                : pollUrl;
            const res = await fetch(url, {
                headers: { Accept: "application/json" },
            });
            if (!res.ok) return;

            const json = await res.json();
            if (json.status === "success" && Array.isArray(json.events)) {
                json.events.forEach((ev) => {
                    lastEventId = ev.id;
                    handleEvent(ev.event, ev);
                });
            }
        } catch (err) {
            // Jeda jaringan sementara
        }
    }

    function startPolling() {
        if (pollTimer) return;
        pollOnce();
        pollTimer = setInterval(pollOnce, 8000); // Polling setiap 8 detik jika SSE tidak aktif
    }

    // Jalankan realtime saat DOM siap
    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", () => {
            startSSE();
            setInterval(() => updateRekapCards({ refreshTable: false }), 10000);
        });
    } else {
        startSSE();
        setInterval(() => updateRekapCards({ refreshTable: false }), 10000);
    }

    // Expose API publik untuk script lain
    window.SAERealtime = {
        on: function (eventName, callback) {
            window.addEventListener(`sae:realtime:${eventName}`, (e) =>
                callback(e.detail),
            );
        },
        refreshCards: updateRekapCards,
        isConnected: function () {
            return isConnected;
        },
    };
})();
