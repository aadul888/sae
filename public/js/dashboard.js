/**
 * SAE - Dashboard Script (Sidebar Mobile Toggle & Accordion Submenus)
 */

document.addEventListener("DOMContentLoaded", () => {
    const sidebar = document.getElementById("dashSidebar");
    const toggleBtn = document.getElementById("dashToggleBtn");
    const backdrop = document.getElementById("dashBackdrop");

    if (toggleBtn && sidebar && backdrop) {
        toggleBtn.addEventListener("click", () => {
            sidebar.classList.toggle("open");
            backdrop.classList.toggle("open");
        });

        backdrop.addEventListener("click", () => {
            sidebar.classList.remove("open");
            backdrop.classList.remove("open");
        });
    }

    // Sidebar Submenu Accordion Handler (Level 1 & Nested Level 2)
    // Standar SAE: Hanya membuka menu yang dibuka saat ini saja (Accordion Single-Open)
    document.querySelectorAll(".dash-nav-toggle").forEach((btn) => {
        btn.addEventListener("click", function (e) {
            e.preventDefault();
            const group = this.closest(".dash-nav-group");
            if (!group) return;

            const isOpen = group.classList.contains("open");

            // Tutup semua grup lain agar hanya menu yang dibuka saat ini saja yang terbuka
            document.querySelectorAll(".dash-nav-group.open").forEach((otherGroup) => {
                if (otherGroup !== group) {
                    otherGroup.classList.remove("open");
                }
            });

            group.classList.toggle("open", !isOpen);
        });
    });

    document.querySelectorAll(".dash-nav-nested-toggle").forEach((btn) => {
        btn.addEventListener("click", function (e) {
            e.preventDefault();
            e.stopPropagation();
            const nestedGroup = this.closest(".dash-nav-nested-group");
            if (!nestedGroup) return;

            const isOpen = nestedGroup.classList.contains("open");

            // Tutup sibling nested group di dalam grup induk yang sama
            const parentGroup = nestedGroup.closest(".dash-nav-group");
            if (parentGroup) {
                parentGroup.querySelectorAll(".dash-nav-nested-group.open").forEach((otherNested) => {
                    if (otherNested !== nestedGroup) {
                        otherNested.classList.remove("open");
                    }
                });
            }

            nestedGroup.classList.toggle("open", !isOpen);
        });
    });

    // Inisialisasi awal pada DOMContentLoaded: Pastikan hanya grup aktif yang terbuka jika ada lebih dari 1 grup yang terbuka
    const openGroups = document.querySelectorAll(".dash-nav-group.open");
    if (openGroups.length > 1) {
        const activeGroup = document.querySelector(".dash-nav-group.active-group");
        if (activeGroup) {
            openGroups.forEach((g) => {
                if (g !== activeGroup) {
                    g.classList.remove("open");
                }
            });
        } else {
            // Jika tidak ada active-group, pertahankan hanya yang pertama
            openGroups.forEach((g, idx) => {
                if (idx > 0) g.classList.remove("open");
            });
        }
    }

    // Auto-scroll Active Segmented Tab into view on Mobile
    const tabContainers = document.querySelectorAll(
        ".dash-tabs-nav, .dash-segmented-tabs, .dash-tabs, [style*='overflow-x: auto']",
    );
    tabContainers.forEach((container) => {
        const activeTab = container.querySelector(
            ".btn-primary, .active, .tab-btn.active, [aria-selected='true']",
        );
        if (activeTab) {
            setTimeout(() => {
                const containerRect = container.getBoundingClientRect();
                const activeRect = activeTab.getBoundingClientRect();
                if (
                    activeRect.left < containerRect.left ||
                    activeRect.right > containerRect.right
                ) {
                    activeTab.scrollIntoView({
                        behavior: "smooth",
                        block: "nearest",
                        inline: "center",
                    });
                }
            }, 100);
        }
    });

    // Custom Styled Dropdown Component Toggle & Outside Click
    document.querySelectorAll(".custom-dropdown-trigger").forEach((trigger) => {
        trigger.addEventListener("click", function (e) {
            e.preventDefault();
            e.stopPropagation();
            const dropdown = this.closest(".dash-custom-dropdown");
            if (!dropdown) return;
            const isOpen = dropdown.classList.contains("open");
            document
                .querySelectorAll(".dash-custom-dropdown.open")
                .forEach((d) => {
                    if (d !== dropdown) {
                        d.classList.remove("open");
                        const otherTrig = d.querySelector(
                            ".custom-dropdown-trigger",
                        );
                        if (otherTrig)
                            otherTrig.setAttribute("aria-expanded", "false");
                    }
                });
            dropdown.classList.toggle("open", !isOpen);
            this.setAttribute("aria-expanded", String(!isOpen));
        });
    });

    document.addEventListener("click", (e) => {
        if (!e.target.closest(".dash-custom-dropdown")) {
            document
                .querySelectorAll(".dash-custom-dropdown.open")
                .forEach((d) => {
                    d.classList.remove("open");
                    const trig = d.querySelector(".custom-dropdown-trigger");
                    if (trig) trig.setAttribute("aria-expanded", "false");
                });
        }
    });

    // Notification Bell & User Avatar Dropdown Toggles with Backdrop Blur
    const notifBtn = document.getElementById("notifBellBtn");
    const notifDropdown = document.getElementById("notifDropdown");
    const userBtn = document.getElementById("userMenuBtn");
    const userDropdown = document.getElementById("userDropdown");
    const dropdownBackdrop = document.getElementById("dashDropdownBackdrop");
    const dashHeader = document.querySelector(".dash-header");
    const notifContainer = document.querySelector(".dash-notif-container");
    const userContainer = document.querySelector(".dash-user-container");

    const updateHeaderBackdropState = (shouldOpen) => {
        if (dropdownBackdrop) {
            if (shouldOpen) {
                dropdownBackdrop.classList.add("active");
            } else {
                dropdownBackdrop.classList.remove("active");
            }
        }
        if (dashHeader) {
            if (shouldOpen) {
                dashHeader.classList.add("has-dropdown-open");
            } else {
                dashHeader.classList.remove("has-dropdown-open");
            }
        }
    };

    const closeAllHeaderDropdowns = () => {
        if (notifDropdown) {
            notifDropdown.style.display = "none";
            if (notifBtn) notifBtn.setAttribute("aria-expanded", "false");
            if (notifContainer) notifContainer.classList.remove("is-active");
        }
        if (userDropdown) {
            userDropdown.style.display = "none";
            if (userBtn) userBtn.setAttribute("aria-expanded", "false");
            if (userContainer) userContainer.classList.remove("is-active");
        }
        updateHeaderBackdropState(false);
    };

    if (dropdownBackdrop) {
        dropdownBackdrop.addEventListener("click", () => {
            closeAllHeaderDropdowns();
        });
    }

    if (notifBtn && notifDropdown) {
        notifBtn.addEventListener("click", (e) => {
            e.preventDefault();
            e.stopPropagation();
            if (userDropdown) {
                userDropdown.style.display = "none";
                if (userBtn) userBtn.setAttribute("aria-expanded", "false");
                if (userContainer) userContainer.classList.remove("is-active");
            }
            const isCurrentlyOpen = notifDropdown.style.display === "block";
            const willOpen = !isCurrentlyOpen;
            notifDropdown.style.display = willOpen ? "block" : "none";
            notifBtn.setAttribute("aria-expanded", String(willOpen));
            if (notifContainer) {
                if (willOpen) notifContainer.classList.add("is-active");
                else notifContainer.classList.remove("is-active");
            }
            updateHeaderBackdropState(willOpen);
        });

        document.addEventListener("click", (e) => {
            if (!e.target.closest(".dash-notif-container") && !e.target.closest(".dash-user-container")) {
                closeAllHeaderDropdowns();
            }
        });
    }

    if (userBtn && userDropdown) {
        userBtn.addEventListener("click", (e) => {
            e.preventDefault();
            e.stopPropagation();
            if (notifDropdown) {
                notifDropdown.style.display = "none";
                if (notifBtn) notifBtn.setAttribute("aria-expanded", "false");
                if (notifContainer) notifContainer.classList.remove("is-active");
            }
            const isCurrentlyOpen = userDropdown.style.display === "block";
            const willOpen = !isCurrentlyOpen;
            userDropdown.style.display = willOpen ? "block" : "none";
            userBtn.setAttribute("aria-expanded", String(willOpen));
            if (userContainer) {
                if (willOpen) userContainer.classList.add("is-active");
                else userContainer.classList.remove("is-active");
            }
            updateHeaderBackdropState(willOpen);
        });
    }

    document.addEventListener("keydown", (e) => {
        if (e.key === "Escape") {
            closeAllHeaderDropdowns();
        }
    });

    // Quick Mark All Read button in notification dropdown (Pengumuman)
    const btnQuickMark = document.getElementById("btnQuickMarkAllRead");
    if (btnQuickMark) {
        btnQuickMark.addEventListener("click", async (e) => {
            e.preventDefault();
            e.stopPropagation();

            const csrfToken =
                document
                    .querySelector('meta[name="csrf-token"]')
                    ?.getAttribute("content") || "";

            try {
                btnQuickMark.style.opacity = "0.5";
                btnQuickMark.disabled = true;

                const res = await fetch("/dashboard/informasi/mark-all-read", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        Accept: "application/json",
                        "X-CSRF-TOKEN": csrfToken,
                    },
                });

                const data = await res.json();
                if (data.status === "success") {
                    const pengumumanBadge = document.getElementById("badgePengumumanCount");
                    if (pengumumanBadge) pengumumanBadge.remove();

                    btnQuickMark.remove();

                    document
                        .querySelectorAll("#paneNotifPengumuman .dash-notif-dot")
                        .forEach((dot) => dot.remove());
                    document
                        .querySelectorAll("#paneNotifPengumuman .dash-notif-item.unread-item")
                        .forEach((el) => el.classList.remove("unread-item"));

                    // Cek jika kedua tab sudah 0 unread, hilangkan dot bell
                    const transBadge = document.getElementById("badgeTransaksiCount");
                    if (!transBadge) {
                        const bellDot = document.getElementById("bellNotifDot");
                        if (bellDot) bellDot.remove();
                    }

                    if (window.SAE && typeof window.SAE.toast === "function") {
                        window.SAE.toast(
                            data.message || "Semua pengumuman telah dibaca.",
                            "success",
                        );
                    }

                    if (
                        window.updateFeedAllRead &&
                        typeof window.updateFeedAllRead === "function"
                    ) {
                        window.updateFeedAllRead();
                    }
                }
            } catch (err) {
                btnQuickMark.style.opacity = "1";
                btnQuickMark.disabled = false;
            }
        });
    }

    // Tab Switcher Notification Dropdown (Tab Pengumuman vs Tab Transaksi)
    const tabHeaderPengumuman = document.getElementById("tabHeaderPengumuman");
    const tabHeaderTransaksi = document.getElementById("tabHeaderTransaksi");
    const paneNotifPengumuman = document.getElementById("paneNotifPengumuman");
    const paneNotifTransaksi = document.getElementById("paneNotifTransaksi");

    if (tabHeaderPengumuman && tabHeaderTransaksi) {
        tabHeaderPengumuman.addEventListener("click", (e) => {
            e.preventDefault();
            e.stopPropagation();
            tabHeaderPengumuman.classList.add("active");
            tabHeaderPengumuman.style.color = "var(--primary)";
            tabHeaderPengumuman.style.borderBottomColor = "var(--primary)";

            tabHeaderTransaksi.classList.remove("active");
            tabHeaderTransaksi.style.color = "var(--text-muted)";
            tabHeaderTransaksi.style.borderBottomColor = "transparent";

            if (paneNotifPengumuman) paneNotifPengumuman.style.display = "block";
            if (paneNotifTransaksi) paneNotifTransaksi.style.display = "none";
        });

        tabHeaderTransaksi.addEventListener("click", (e) => {
            e.preventDefault();
            e.stopPropagation();
            tabHeaderTransaksi.classList.add("active");
            tabHeaderTransaksi.style.color = "var(--primary)";
            tabHeaderTransaksi.style.borderBottomColor = "var(--primary)";

            tabHeaderPengumuman.classList.remove("active");
            tabHeaderPengumuman.style.color = "var(--text-muted)";
            tabHeaderPengumuman.style.borderBottomColor = "transparent";

            if (paneNotifPengumuman) paneNotifPengumuman.style.display = "none";
            if (paneNotifTransaksi) paneNotifTransaksi.style.display = "block";
        });
    }

    // Quick Mark All Read Transaksi
    const btnQuickMarkTrans = document.getElementById("btnQuickMarkAllTransRead");
    if (btnQuickMarkTrans) {
        btnQuickMarkTrans.addEventListener("click", async (e) => {
            e.preventDefault();
            e.stopPropagation();

            const csrfToken =
                document
                    .querySelector('meta[name="csrf-token"]')
                    ?.getAttribute("content") || "";

            try {
                btnQuickMarkTrans.style.opacity = "0.5";
                btnQuickMarkTrans.disabled = true;

                const res = await fetch("/dashboard/notifikasi-transaksi/mark-all-read", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        Accept: "application/json",
                        "X-CSRF-TOKEN": csrfToken,
                    },
                });

                const data = await res.json();
                if (data.status === "success") {
                    const transBadge = document.getElementById("badgeTransaksiCount");
                    if (transBadge) transBadge.remove();

                    btnQuickMarkTrans.remove();

                    document
                        .querySelectorAll("#paneNotifTransaksi .dash-notif-dot")
                        .forEach((dot) => dot.remove());
                    document
                        .querySelectorAll("#paneNotifTransaksi .dash-notif-item.unread-item")
                        .forEach((el) => el.classList.remove("unread-item"));

                    const pengumumanBadge = document.getElementById("badgePengumumanCount");
                    if (!pengumumanBadge) {
                        const bellDot = document.getElementById("bellNotifDot");
                        if (bellDot) bellDot.remove();
                    }

                    if (window.SAE && typeof window.SAE.toast === "function") {
                        window.SAE.toast(
                            data.message || "Aktivitas presensi telah ditandai sudah dibaca.",
                            "success",
                        );
                    }
                }
            } catch (err) {
                btnQuickMarkTrans.style.opacity = "1";
                btnQuickMarkTrans.disabled = false;
            }
        });
    }

    // Single Click Transaksi Mark Read
    document.querySelectorAll("#paneNotifTransaksi .dash-notif-item").forEach((item) => {
        item.addEventListener("click", function () {
            const transId = this.getAttribute("data-trans-id");
            if (!transId) return;

            const csrfToken =
                document
                    .querySelector('meta[name="csrf-token"]')
                    ?.getAttribute("content") || "";

            fetch(`/dashboard/notifikasi-transaksi/${transId}/mark-read`, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    Accept: "application/json",
                    "X-CSRF-TOKEN": csrfToken,
                },
            }).catch(() => {});
        });
    });

    // Global Modal Backdrop Close & ESC Handler
    document.querySelectorAll(".modal-backdrop").forEach((modal) => {
        modal.addEventListener("click", function (e) {
            if (e.target === modal) {
                modal.style.display = "none";
            }
        });
    });

    document.addEventListener("keydown", (e) => {
        if (e.key === "Escape") {
            document.querySelectorAll(".modal-backdrop").forEach((modal) => {
                modal.style.display = "none";
            });
        }
    });

    // Global Datatable Column Header Sorting Handler (Acuan: peserta-didik-aktif)
    document.addEventListener("click", function (e) {
        const th = e.target.closest(".sortable-th");
        if (!th) return;

        const sortField = th.getAttribute("data-sort");
        if (!sortField) return;

        // Jika halaman sudah memiliki AJAX filter / custom sort handler tersendiri, abaikan reload otomatis
        if (typeof window.applyDatatableFilter === "function" || th.getAttribute("data-custom-sort") === "true") {
            return;
        }

        const url = new URL(window.location.href);
        const currentSort = url.searchParams.get("sort") || "";
        const currentDir = url.searchParams.get("sort_dir") || "asc";

        let newDir = "asc";
        if (currentSort === sortField && currentDir === "asc") {
            newDir = "desc";
        }

        if (typeof window.collectDatatableUrl === "function") {
            const targetUrl = window.collectDatatableUrl(th, { sort: sortField, sort_dir: newDir });
            window.refreshLiveTable(targetUrl);
        } else {
            url.searchParams.set("sort", sortField);
            url.searchParams.set("sort_dir", newDir);
            url.searchParams.delete("page");
            if (typeof window.refreshLiveTable === "function") {
                window.refreshLiveTable(url.toString());
            } else {
                window.location.href = url.toString();
            }
        }
    });
});

/**
 * Global HTML escaping helper
 */
window.escapeHtml = function (str) {
    if (str === null || str === undefined) return "";
    return String(str)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
};

/**
 * SAE Universal Live Table & Search Engine
 * Refreshes datatables and pagination via AJAX without reloading the entire page.
 * Keeps input focus, cursor position, and smooth transitions.
 */
window.liveTableAbortController = null;
window.abortLiveTable = function () {
    if (window.liveTableAbortController) {
        try {
            window.liveTableAbortController.abort();
        } catch (e) {}
        window.liveTableAbortController = null;
    }
};

window.refreshLiveTable = async function (url, options = {}) {
    if (!url) return;
    let targetUrl = typeof url === "string" ? url : url.toString();

    // Anti Mixed-Content Normalizer: Paksa protokol HTTPS jika halaman saat ini HTTPS
    if (
        window.location.protocol === "https:" &&
        targetUrl.startsWith("http:")
    ) {
        targetUrl = targetUrl.replace(/^http:/, "https:");
    }

    // 1. Locate the active table container in DOM
    const container =
        (document.querySelector("#viewKalenderVisual") && document.querySelector("#viewKalenderVisual").style.display !== "none")
            ? document.querySelector("#viewKalenderVisual")
            : (document.querySelector("#tableDataContainer") ||
               document.querySelector(
                   ".card.table-responsive-stack, .table-responsive-stack, .dash-table-card",
               ) ||
               document.querySelector("#viewKalenderVisual") ||
               document.querySelector("table"));

    if (!container) {
        window.location.href = targetUrl;
        return;
    }

    // Hindari pemanggilan ganda jika URL tujuan yang sama sedang diproses
    if (container.dataset.liveRefreshing === targetUrl) {
        return;
    }
    container.dataset.liveRefreshing = targetUrl;

    // Batalkan request fetch sebelumnya jika masih berjalan (mencegah race condition saat pengguna mengetik)
    if (window.liveTableAbortController) {
        try {
            window.liveTableAbortController.abort();
        } catch (e) {}
    }
    window.liveTableAbortController = new AbortController();
    const currentSignal = window.liveTableAbortController.signal;

    // 2. Subtle loading visual feedback
    const originalOpacity =
        container.style.opacity && container.style.opacity !== "0.45"
            ? container.style.opacity
            : "";
    container.style.transition = "opacity 0.15s ease";
    container.style.opacity = "0.45";

    // Remember focused input to restore focus only if element is recreated in DOM
    const isInputActive =
        document.activeElement &&
        (document.activeElement.tagName === "INPUT" ||
            document.activeElement.tagName === "TEXTAREA" ||
            document.activeElement.tagName === "SELECT");
    const activeEl = isInputActive ? document.activeElement : null;
    const focusedElId = activeEl ? activeEl.id : null;
    const activeElVal = activeEl && activeEl.value !== undefined ? activeEl.value : null;
    const activeCursorPos =
        activeEl && activeEl.selectionStart !== undefined
            ? activeEl.selectionStart
            : null;

    try {
        const response = await fetch(targetUrl, {
            signal: currentSignal,
            credentials: "same-origin",
            headers: {
                "X-Requested-With": "XMLHttpRequest",
                Accept: "text/html, application/xhtml+xml",
            },
        });

        if (!response.ok) {
            throw new Error(`HTTP error ${response.status}`);
        }

        const html = await response.text();
        const doc = new DOMParser().parseFromString(html, "text/html");

        // 3. Swap container contents
        const curTabBarContainer = document.querySelector("#jadwalTabBarContainer");
        const newTabBarContainer = doc.querySelector("#jadwalTabBarContainer");
        if (curTabBarContainer && newTabBarContainer) {
            curTabBarContainer.innerHTML = newTabBarContainer.innerHTML;
        }

        const curDataContainer = document.querySelector("#tableDataContainer");
        const newDataContainer = doc.querySelector("#tableDataContainer");
        let tableAnchor = null;

        if (curDataContainer && newDataContainer) {
            const curToolbar = curDataContainer.querySelector(".datatable-toolbar");
            const newToolbar = newDataContainer.querySelector(".datatable-toolbar");
            const curTableStack = curDataContainer.querySelector(".table-responsive-stack, table");
            const newTableStack = newDataContainer.querySelector(".table-responsive-stack, table");

            // Apakah pengguna sedang aktif mengetik di input pencarian datatable?
            const isTypingSearch =
                document.activeElement &&
                document.activeElement.tagName === "INPUT" &&
                typeof window.isDatatableSearchInput === "function" &&
                window.isDatatableSearchInput(document.activeElement);

            if (curToolbar && newToolbar && curTableStack && newTableStack && isTypingSearch) {
                // Pengguna sedang aktif mengetik: Hanya perbarui tabel & paginasi agar input & kursor tidak terganggu
                curTableStack.innerHTML = newTableStack.innerHTML;
                tableAnchor = curTableStack;

                const curPagination = curDataContainer.querySelector(
                    ".custom-pagination, .dash-pagination, .pagination, .datatable-footer",
                );
                const newPagination = newDataContainer.querySelector(
                    ".custom-pagination, .dash-pagination, .pagination, .datatable-footer",
                );
                if (curPagination && newPagination) {
                    curPagination.outerHTML = newPagination.outerHTML;
                } else if (curPagination && !newPagination) {
                    curPagination.remove();
                } else if (!curPagination && newPagination) {
                    curTableStack.insertAdjacentElement("afterend", newPagination);
                }

                // Sinkronkan tombol reset jika ada
                const curResetBtn = curToolbar.querySelector("[data-live-reset], .btn-reset-filter, #btnResetFilter, a[title*='Reset']");
                const newResetBtn = newToolbar.querySelector("[data-live-reset], .btn-reset-filter, #btnResetFilter, a[title*='Reset']");
                if (!curResetBtn && newResetBtn) {
                    const insertTarget = curToolbar.querySelector(".toolbar-filter-select, .toolbar-entries")?.parentElement;
                    if (insertTarget) insertTarget.insertAdjacentHTML("beforeend", newResetBtn.outerHTML);
                } else if (curResetBtn && !newResetBtn) {
                    curResetBtn.remove();
                } else if (curResetBtn && newResetBtn) {
                    curResetBtn.outerHTML = newResetBtn.outerHTML;
                }
            } else {
                // Pengguna TIDAK sedang mengetik (mengubah dropdown filter, klik reset, sort, paginasi, dll):
                // Perbarui seluruh container agar toolbar (dropdown options, selected state, reset button, action buttons)
                // dan tabel serta paginasi 100% selaras dengan response server!
                curDataContainer.innerHTML = newDataContainer.innerHTML;
                tableAnchor = curDataContainer;
            }
        }

        const curGridContainer = document.querySelector("#gridMatrixContainer");
        const newGridContainer = doc.querySelector("#gridMatrixContainer");
        if (curGridContainer && newGridContainer) {
            curGridContainer.innerHTML = newGridContainer.innerHTML;
            tableAnchor = curGridContainer;
        } else if (!curDataContainer && !curGridContainer) {
            // Find and swap the table card/container
            const curTable =
                document.querySelector(
                    ".card.table-responsive-stack, .table-responsive-stack, .dash-table-card",
                ) || document.querySelector("table");
            const newTable =
                doc.querySelector(
                    ".card.table-responsive-stack, .table-responsive-stack, .dash-table-card",
                ) || doc.querySelector("table");

            if (curTable && newTable) {
                curTable.innerHTML = newTable.innerHTML;
                tableAnchor = curTable;
            }
        }

        // Swap Kalender Visual View jika ada pada modul kalender pendidikan
        const curKalenderVisual = document.querySelector("#viewKalenderVisual");
        const newKalenderVisual = doc.querySelector("#viewKalenderVisual");
        if (curKalenderVisual && newKalenderVisual) {
            curKalenderVisual.innerHTML = newKalenderVisual.innerHTML;
            curKalenderVisual.style.display = newKalenderVisual.style.display;
            if (!tableAnchor) tableAnchor = curKalenderVisual;
        }

        const curTabelContainer = document.querySelector("#viewTabelContainer");
        const newTabelContainer = doc.querySelector("#viewTabelContainer");
        if (curTabelContainer && newTabelContainer) {
            curTabelContainer.style.display = newTabelContainer.style.display;
        }

        // Swap Periode Navigation Bar / Scope Switcher jika ada
        const curPeriodeNav = document.querySelector(".periode-nav-wrapper");
        const newPeriodeNav = doc.querySelector(".periode-nav-wrapper");
        if (curPeriodeNav && newPeriodeNav) {
            curPeriodeNav.innerHTML = newPeriodeNav.innerHTML;
        }

        // Swap Segmented Tab Navigation Bar jika ada (seperti Manajemen Pengguna, dsb)
        const curTabsNav = document.querySelector(".dash-tabs-nav, .dash-desktop-tabs");
        const newTabsNav = doc.querySelector(".dash-tabs-nav, .dash-desktop-tabs");
        if (curTabsNav && newTabsNav) {
            curTabsNav.innerHTML = newTabsNav.innerHTML;
        }

        const curMobileTabs = document.querySelector(".dash-mobile-tab-select-wrap");
        const newMobileTabs = doc.querySelector(".dash-mobile-tab-select-wrap");
        if (curMobileTabs && newMobileTabs) {
            curMobileTabs.innerHTML = newMobileTabs.innerHTML;
        }

        // 3b. Swap pagination universally across ALL modules (hanya jika di luar #tableDataContainer)
        const curPagination = document.querySelector(
            ".custom-pagination, .dash-pagination, .pagination",
        );
        const newPagination = doc.querySelector(
            ".custom-pagination, .dash-pagination, .pagination",
        );

        if (
            curPagination &&
            newPagination &&
            (!curDataContainer || !curDataContainer.contains(curPagination))
        ) {
            curPagination.outerHTML = newPagination.outerHTML;
        } else if (
            curPagination &&
            !newPagination &&
            (!curDataContainer || !curDataContainer.contains(curPagination))
        ) {
            curPagination.remove();
        } else if (
            !curPagination &&
            newPagination &&
            (!curDataContainer || !curDataContainer.contains(newPagination))
        ) {
            const anchor = tableAnchor || container;
            if (anchor) anchor.insertAdjacentElement("afterend", newPagination);
        }

        // 4. Update badge / total entries count if present in the page
        const countSelectors = [
            ".badge-total",
            "#totalBadge",
            ".total-badge",
            ".total-count-text",
            "#totalRiwayatBadge",
            ".badge-outline",
        ];
        countSelectors.forEach((sel) => {
            const curEls = document.querySelectorAll(sel);
            const newEls = doc.querySelectorAll(sel);
            curEls.forEach((curEl, idx) => {
                if (newEls[idx] && curEl.innerHTML !== newEls[idx].innerHTML) {
                    curEl.innerHTML = newEls[idx].innerHTML;
                }
            });
        });

        // 4b. Update Card Rekap / Stat Grid jika ada pada modul
        const statGridSelectors = [
            ".dash-stat-grid",
            ".form-stat-grid",
            ".rekap-stat-grid",
            ".stats-grid",
        ];
        statGridSelectors.forEach((sel) => {
            const curStatGrid = document.querySelector(sel);
            const newStatGrid = doc.querySelector(sel);
            if (curStatGrid && newStatGrid) {
                curStatGrid.innerHTML = newStatGrid.innerHTML;
            }
        });

        // 4c. Sync filter select values in toolbar if present
        const curSelects = document.querySelectorAll(
            ".per-page-select, #filterRombel, #filterGender, #filterStatus, #filterTahun, #filterTahunAjaran, #filterSemester, #filterBulan, #filterModePresensi, #filterTipe, #filterDampak"
        );
        if (curSelects.length > 0) {
            curSelects.forEach((curSel) => {
                const newSel = doc.getElementById(curSel.id);
                if (newSel && curSel.value !== newSel.value) {
                    curSel.value = newSel.value;
                }
            });
        }

        const curResetBtn = document.querySelector("#btnResetFilter");
        const newResetBtn = doc.querySelector("#btnResetFilter");
        if (curResetBtn && newResetBtn) {
            curResetBtn.style.display = "";
        } else if (curResetBtn && !newResetBtn) {
            curResetBtn.style.display = "none";
        }

        // 4d. Jaga agar kursor dan fokus input pengguna TIDAK terganggu saat mengetik
        // Hanya pulihkan fokus jika saat ini tidak ada elemen lain yang dipilih pengguna (document.activeElement === document.body)
        if (focusedElId && (!document.activeElement || document.activeElement === document.body)) {
            const reFocusEl = document.getElementById(focusedElId);
            if (reFocusEl) {
                if (activeElVal !== null && reFocusEl.value !== activeElVal) {
                    reFocusEl.value = activeElVal;
                }
                reFocusEl.focus({ preventScroll: true });
                const curPos =
                    activeCursorPos !== null
                        ? activeCursorPos
                        : (reFocusEl.value ? reFocusEl.value.length : 0);
                try {
                    reFocusEl.setSelectionRange(curPos, curPos);
                } catch (e) {}
            }
        }

        // 5. Update browser URL silently without page reload
        const isSearchField =
            activeEl &&
            activeEl.tagName === "INPUT" &&
            (activeEl.name === "q" ||
                activeEl.name === "search" ||
                activeEl.type === "search" ||
                Boolean(activeEl.closest(".live-search-wrap")) ||
                (activeEl.id && activeEl.id.toLowerCase().includes("search")));

        if (isSearchField && activeEl.value !== undefined) {
            try {
                const currentUrlObj = new URL(targetUrl, window.location.origin);
                const currentVal = activeEl.value.trim();
                if (currentVal) currentUrlObj.searchParams.set("q", currentVal);
                else currentUrlObj.searchParams.delete("q");
                window.history.replaceState(null, "", currentUrlObj.toString());
            } catch (e) {
                window.history.replaceState(null, "", targetUrl);
            }
        } else {
            window.history.replaceState(null, "", targetUrl);
        }

        // 6. Notify any custom listeners
        window.dispatchEvent(
            new CustomEvent("sae:tableRefreshed", {
                detail: { url: targetUrl, doc },
            }),
        );
    } catch (err) {
        if (err.name === "AbortError" || (currentSignal && currentSignal.aborted)) {
            // Permintaan dibatalkan karena ada ketikan baru yang lebih segar, abaikan hening
            return;
        }
        console.warn("[SAE LiveSearch] Refresh failed:", err);
        // JANGAN fallback reload penuh jika pengguna sedang aktif berada di input pencarian
        const isTypingNow =
            document.activeElement &&
            (document.activeElement.tagName === "INPUT" ||
                document.activeElement.tagName === "TEXTAREA");
        if (!isTypingNow) {
            let fallbackUrl = targetUrl;
            if (
                window.location.protocol === "https:" &&
                fallbackUrl.startsWith("http:")
            ) {
                fallbackUrl = fallbackUrl.replace(/^http:/, "https:");
            }
            window.location.href = fallbackUrl;
        }
    } finally {
        container.style.opacity = originalOpacity;
        container.style.pointerEvents = "";
        delete container.dataset.liveRefreshing;
    }
};

// Global Delegated Click Handler for Pagination Links (No Full Reload)
document.addEventListener("click", function (e) {
    const pageLink = e.target.closest(
        ".custom-pagination a, .dash-pagination a, .pagination a",
    );
    if (
        pageLink &&
        pageLink.href &&
        !pageLink.hasAttribute("data-no-ajax") &&
        !pageLink.classList.contains("disabled") &&
        !pageLink.getAttribute("href").startsWith("javascript:")
    ) {
        e.preventDefault();
        let targetHref = pageLink.href;
        if (
            window.location.protocol === "https:" &&
            targetHref.startsWith("http:")
        ) {
            targetHref = targetHref.replace(/^http:/, "https:");
        }
        window.refreshLiveTable(targetHref);
    }
});

