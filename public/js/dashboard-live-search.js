// =========================================================================
// SAE GLOBAL LIVE DATATABLE SEARCH, FILTER, RESET & COPY ENGINE
// =========================================================================
(function () {
    // Universal Helper: Check if input is a Datatable search field
    function isDatatableSearchInput(el) {
        if (!el || el.tagName !== "INPUT") return false;
        const type = (el.type || "").toLowerCase();
        if (
            type === "button" ||
            type === "submit" ||
            type === "checkbox" ||
            type === "radio" ||
            type === "file" ||
            type === "password" ||
            type === "hidden" ||
            type === "date"
        ) {
            return false;
        }
        if (
            el.hasAttribute("data-manual-search") ||
            el.hasAttribute("data-no-ajax") ||
            el.closest(".modal, .swal2-container, #actionModal, .modal-backdrop, [data-manual-search], [data-no-ajax]")
        ) {
            return false;
        }
        if (el.closest(".live-search-wrap")) return true;
        const id = (el.id || "").toLowerCase();
        if (
            id.includes("search") ||
            id.includes("cari") ||
            id === "livesearch" ||
            id === "livesearchinput" ||
            id === "livesearchadmin" ||
            id === "searchtable" ||
            id === "livesearchindeks" ||
            id === "livesearchkelasinput" ||
            id === "livesearchizininput" ||
            id === "searchpip" ||
            id === "searchkartupelajar" ||
            id === "inputsearchizin" ||
            id === "livesearchmonitorinput"
        ) {
            return true;
        }
        const name = (el.name || "").toLowerCase();
        if (name === "q" || name === "search" || name === "cari" || name === "keyword" || name === "monitor_q") {
            return true;
        }
        if (el.classList.contains("live-search-input") || el.classList.contains("table-search-input")) {
            return true;
        }

        // Jika input teks berada di dalam form GET yang terhubung ke datatable (dan bukan filter khusus seperti #filterTahunLulus)
        if (!id.startsWith("filter") && !id.startsWith("adminfilter")) {
            const form = el.closest("form[method='GET'], form[method='get']");
            if (form && document.querySelector("#tableDataContainer, #tableMonitoringContainer, .datatable-card-wrapper, .table-responsive-stack, table.table-pd")) {
                return true;
            }
        }

        return false;
    }

    window.isDatatableSearchInput = isDatatableSearchInput;

    // Universal Helper: Collect all search & filter parameters for the active datatable
    window.collectDatatableUrl = function (sourceEl, overrideParams = {}) {
        const url = new URL(window.location.href);
        url.searchParams.delete("perPageSelect");
        url.searchParams.delete("_token");

        // 1. Temukan kontainer datatable atau toolbar terdekat
        const cardContainer =
            sourceEl?.closest("#tableDataContainer, #tableMonitoringContainer, .datatable-card-wrapper, .dash-table-card, .card.table-responsive-stack, .table-responsive-stack") ||
            document.querySelector("#tableDataContainer, #tableMonitoringContainer, .datatable-card-wrapper, .card.table-responsive-stack");

        const toolbarContainer =
            sourceEl?.closest(".datatable-toolbar, .dash-table-toolbar, .toolbar-entries") ||
            (cardContainer ? cardContainer.querySelector(".datatable-toolbar, .dash-table-toolbar, .toolbar-entries") : null) ||
            document.querySelector(".datatable-toolbar, .dash-table-toolbar");

        // 2. Ambil nilai query pencarian
        const searchInput =
            (toolbarContainer ? toolbarContainer.querySelector("input#liveSearch, input#liveSearchInput, input#liveSearchAdmin, input#searchPip, input#searchKartuPelajar, input#inputSearchIzin, input#liveSearchMonitorInput, .live-search-wrap input") : null) ||
            (cardContainer ? cardContainer.querySelector("input#liveSearch, input#liveSearchInput, input#liveSearchAdmin, input#searchPip, input#searchKartuPelajar, input#inputSearchIzin, input#liveSearchMonitorInput, .live-search-wrap input") : null) ||
            document.getElementById("liveSearch") ||
            document.getElementById("liveSearchInput") ||
            document.getElementById("searchPip") ||
            document.getElementById("searchKartuPelajar") ||
            document.getElementById("inputSearchIzin") ||
            document.getElementById("liveSearchMonitorInput");

        if (searchInput && isDatatableSearchInput(searchInput)) {
            const qParam = searchInput.name || "q";
            const qVal = searchInput.value.trim();
            if (qVal) url.searchParams.set(qParam, qVal);
            else url.searchParams.delete(qParam);
        }

        // 3. Kumpulkan seluruh dropdown filter yang ada di toolbar / card
        const scopes = [];
        if (toolbarContainer) scopes.push(toolbarContainer);
        if (cardContainer && cardContainer !== toolbarContainer) scopes.push(cardContainer);
        if (scopes.length === 0) scopes.push(document);

        const processedSelects = new Set();
        scopes.forEach((scope) => {
            const selects = scope.querySelectorAll(
                "select.per-page-select, select.toolbar-filter-select, select.filter-select, select[data-live-filter], select[id*='filter'], select[id*='Filter'], select[id*='perPage'], select[name]"
            );
            selects.forEach((sel) => {
                if (processedSelects.has(sel)) return;
                processedSelects.add(sel);

                let pName = sel.name;
                if (!pName) {
                    if (sel.classList.contains("per-page-select") || sel.id === "perPageSelect" || sel.id === "perPageSelectAdmin") {
                        pName = "perPage";
                    } else if (sel.id === "filterRombel" || sel.id === "adminRombelSelect") {
                        pName = url.searchParams.has("rombongan_belajar_id") ? "rombongan_belajar_id" : (url.searchParams.has("rombel_id") ? "rombel_id" : "rombel");
                    } else if (sel.id === "filterJenis") {
                        pName = (url.searchParams.has("jenis_ptk") || window.location.pathname.includes("kepegawaian")) ? "jenis_ptk" : "jenis";
                    } else if (sel.id === "filterPtk") {
                        pName = url.searchParams.has("ptk_id") ? "ptk_id" : "filter_ptk_id";
                    } else if (sel.id === "filterStatusKbm") {
                        pName = "status_kbm";
                    } else if (sel.id === "filterStatusPip") {
                        pName = "status_pip";
                    } else if (sel.id === "filterJenisSk") {
                        pName = "jenis_sk";
                    } else if (sel.id === "filterStatusVerif") {
                        pName = "status_verifikasi";
                    } else if (sel.id === "filterStatusKeluar") {
                        pName = "status_keluar";
                    } else if (sel.id === "filterStatusBayar") {
                        pName = "status_pembayaran";
                    } else if (sel.id === "filterReqStatus") {
                        pName = "req_status";
                    } else if (sel.id === "filterKonfirmasi") {
                        pName = "konfirmasi";
                    } else if (sel.id === "filterBerkas") {
                        pName = "berkas";
                    } else if (sel.id === "filterKodeIndeks") {
                        pName = "kode_indeks";
                    } else if (sel.id.startsWith("filter") || sel.id.startsWith("adminFilter")) {
                        const raw = sel.id.replace(/^(?:admin)?Filter_?/, "");
                        pName = raw.charAt(0).toLowerCase() + raw.slice(1);
                    } else {
                        pName = sel.id;
                    }
                }

                if (pName === "perPage") {
                    url.searchParams.delete("perPageSelect");
                }

                if (pName) {
                    if (sel.value) url.searchParams.set(pName, sel.value);
                    else url.searchParams.delete(pName);
                }
            });

            // 4. Input tanggal inline (seperti agenda-kbm)
            const dateInputs = scope.querySelectorAll(
                "input[type='date']#filterTanggalMulai, input[type='date']#filterTanggalSelesai, input[type='date']#filterTanggal, input[type='date'][id*='filter'], input[type='date'][data-live-filter], input[type='date'][name]"
            );
            dateInputs.forEach((dInput) => {
                const dName = dInput.name || (dInput.id === "filterTanggalMulai" ? "tanggal_mulai" : (dInput.id === "filterTanggalSelesai" ? "tanggal_selesai" : "tanggal"));
                if (dInput.value) url.searchParams.set(dName, dInput.value);
                else url.searchParams.delete(dName);
            });

            // 4b. Input filter teks/angka lainnya (seperti #filterTahunLulus)
            const textFilterInputs = scope.querySelectorAll(
                "input[id*='filter']:not(#liveSearch):not(#liveSearchInput):not(#liveSearchAdmin):not(#searchPip):not(#searchKartuPelajar):not(#inputSearchIzin):not(#liveSearchMonitorInput), input[id*='Filter']:not(#liveSearch):not(#liveSearchInput):not(#liveSearchAdmin):not(#searchPip):not(#searchKartuPelajar):not(#inputSearchIzin):not(#liveSearchMonitorInput)"
            );
            textFilterInputs.forEach((tInput) => {
                if (tInput.type === "date" || tInput.type === "submit" || tInput.type === "button" || isDatatableSearchInput(tInput)) return;
                let pName = tInput.name;
                if (!pName) {
                    const raw = tInput.id.replace(/^(?:admin)?Filter_?/, "");
                    pName = raw.charAt(0).toLowerCase() + raw.slice(1);
                }
                if (pName) {
                    if (tInput.value.trim()) url.searchParams.set(pName, tInput.value.trim());
                    else url.searchParams.delete(pName);
                }
            });
        });

        // 5. Terapkan override parameter khusus (misal { page: 1, sort: 'nama', sort_dir: 'asc' })
        Object.keys(overrideParams).forEach((k) => {
            const v = overrideParams[k];
            if (v !== null && v !== undefined && v !== "") {
                url.searchParams.set(k, v);
            } else {
                url.searchParams.delete(k);
            }
        });

        if (!("page" in overrideParams)) {
            url.searchParams.set("page", "1");
            [
                "aktif_page", "tidak_aktif_page", "alumni_page", "berkas_page",
                "usulan_page", "kuota_page", "riwayat_page", "klaper_page",
                "mutasi_page", "kelulusan_page", "admin_page", "guru_page",
                "tendik_page", "peserta_didik_page", "tugas_page",
                "patroli_page", "insiden_page"
            ].forEach((p) => url.searchParams.delete(p));
        }

        return url.toString();
    };

    // Universal Helper: Execute Datatable search (works for forms and standalone toolbars)
    function executeDatatableSearch(input) {
        if (!input) return;

        // 1. Jika berada di dalam form GET, submit form secara asinkron
        const form = input.closest("form");
        if (form && !form.hasAttribute("data-manual-search") && (form.method || "get").toLowerCase() === "get") {
            const formData = new FormData(form);
            const url = new URL(form.action || window.location.href, window.location.origin);
            for (const [key, val] of formData.entries()) {
                if (val !== "") url.searchParams.set(key, val);
                else url.searchParams.delete(key);
            }
            url.searchParams.set("page", "1");
            if (typeof window.refreshLiveTable === "function") {
                window.refreshLiveTable(url.toString());
            } else {
                window.location.href = url.toString();
            }
            return;
        }

        // 2. Standalone datatable toolbar (tanpa form GET)
        const targetUrl = window.collectDatatableUrl(input, { [input.name || "q"]: input.value.trim() });
        if (typeof window.refreshLiveTable === "function") {
            window.refreshLiveTable(targetUrl);
        } else {
            window.location.href = targetUrl;
        }
    }

    let globalLiveSearchTimer = null;

    // Global Delegated Handler for Clear Search Buttons (.clear-search, #clearSearch, dsb)
    document.addEventListener("click", function (e) {
        const clearBtn = e.target.closest(".live-search-wrap .clear-search, #clearSearch, #clearSearchBtn, #clearSearchPip, #clearSearchKartuPelajar, #clearSearchMonitorBtn, #btnClearSearch, .clear-search");
        if (!clearBtn) return;
        e.preventDefault();

        const wrap = clearBtn.closest(".live-search-wrap") || clearBtn.parentElement;
        const input = wrap
            ? wrap.querySelector("input")
            : (document.getElementById("liveSearch") || document.getElementById("liveSearchInput") || document.getElementById("searchPip") || document.getElementById("searchKartuPelajar") || document.getElementById("inputSearchIzin") || document.getElementById("liveSearchMonitorInput"));

        if (input) {
            input.value = "";
            clearBtn.classList.remove("visible");
            input.focus();
            clearTimeout(globalLiveSearchTimer);
            if (typeof window.abortLiveTable === "function") window.abortLiveTable();
            executeDatatableSearch(input);
        }
    });

    // Universal Real-Time Live Search Engine across ALL modules
    document.addEventListener("input", function (e) {
        const input = e.target;
        if (!isDatatableSearchInput(input)) return;

        // Toggle clear button visibility
        const wrap = input.closest(".live-search-wrap") || input.parentElement;
        const clearBtn = wrap
            ? wrap.querySelector(".clear-search, #clearSearch, #clearSearchBtn, #clearSearchPip, #clearSearchKartuPelajar, #clearSearchMonitorBtn, #btnClearSearch")
            : (document.getElementById("clearSearch") || document.getElementById("clearSearchBtn") || document.getElementById("clearSearchPip") || document.getElementById("clearSearchKartuPelajar") || document.getElementById("clearSearchMonitorBtn") || document.getElementById("btnClearSearch"));
        if (clearBtn) {
            clearBtn.classList.toggle("visible", input.value.trim().length > 0);
        }

        // Segera batalkan fetch yang sedang berjalan agar respon lama tidak menimpa ketikan baru
        if (typeof window.abortLiveTable === "function") window.abortLiveTable();

        clearTimeout(globalLiveSearchTimer);
        globalLiveSearchTimer = setTimeout(function () {
            executeDatatableSearch(input);
        }, 350);
    });

    // Global Keydown Handler: Tekan Enter langsung lakukan pencarian tanpa menunggu debounce
    document.addEventListener("keydown", function (e) {
        if (e.key === "Enter") {
            const input = e.target;
            if (isDatatableSearchInput(input)) {
                e.preventDefault();
                clearTimeout(globalLiveSearchTimer);
                if (typeof window.abortLiveTable === "function") window.abortLiveTable();
                executeDatatableSearch(input);
            }
        }
    });

    // Global Interception for Search Forms submitting with live-search-wrap or datatable GET forms
    document.addEventListener("submit", function (e) {
        const form = e.target;
        if (
            form &&
            !form.hasAttribute("data-no-ajax") &&
            (form.method || "get").toLowerCase() === "get"
        ) {
            const hasTable = document.querySelector("#tableDataContainer, #tableMonitoringContainer, .datatable-card-wrapper, .table-responsive-stack, table.table-pd");
            if (!hasTable) return;

            e.preventDefault();
            e.stopImmediatePropagation();
            clearTimeout(globalLiveSearchTimer);
            if (typeof window.abortLiveTable === "function") window.abortLiveTable();
            const formData = new FormData(form);
            const url = new URL(form.action || window.location.href, window.location.origin);
            for (const [k, v] of formData.entries()) {
                if (v !== "") url.searchParams.set(k, v);
                else url.searchParams.delete(k);
            }
            url.searchParams.set("page", "1");
            if (typeof window.refreshLiveTable === "function") {
                window.refreshLiveTable(url.toString());
            } else {
                window.location.href = url.toString();
            }
        }
    });

    // Universal Delegated Change Handler for Dropdown & Date Filters across ALL modules (Capture Phase)
    document.addEventListener("change", function (e) {
        const el = e.target;
        if (!el) return;

        if (
            e.defaultPrevented ||
            el.hasAttribute("data-no-ajax") ||
            el.hasAttribute("data-manual-filter") ||
            el.closest("[data-manual-filter]") ||
            el.closest("[data-no-ajax]") ||
            el.closest(".modal, .swal2-container, #actionModal, .modal-backdrop") ||
            isDatatableSearchInput(el)
        ) {
            return;
        }

        // 1. Jika elemen berada di dalam form
        const form = el.closest("form");
        if (form) {
            if ((form.method || "get").toLowerCase() === "get" && !form.hasAttribute("data-manual-filter")) {
                const hasTable = document.querySelector("#tableDataContainer, #tableMonitoringContainer, .datatable-card-wrapper, .table-responsive-stack, table.table-pd");
                if (hasTable) {
                    e.stopImmediatePropagation();
                    const formData = new FormData(form);
                    const url = new URL(form.action || window.location.href, window.location.origin);
                    for (const [k, v] of formData.entries()) {
                        if (v !== "") url.searchParams.set(k, v);
                        else url.searchParams.delete(k);
                    }
                    url.searchParams.set("page", "1");
                    if (typeof window.refreshLiveTable === "function") {
                        window.refreshLiveTable(url.toString());
                    } else {
                        window.location.href = url.toString();
                    }
                }
            }
            return;
        }

        // 2. Standalone datatable toolbar (tanpa form GET)
        const hasTable = document.querySelector("#tableDataContainer, #tableMonitoringContainer, .datatable-card-wrapper, .table-responsive-stack, table.table-pd, #viewKalenderVisual");
        if (!hasTable) return;

        const isFilter =
            (el.tagName === "SELECT" && (
                el.classList.contains("per-page-select") ||
                el.classList.contains("toolbar-filter-select") ||
                el.classList.contains("filter-select") ||
                el.hasAttribute("data-live-filter") ||
                el.id.startsWith("filter") ||
                el.id.startsWith("adminFilter") ||
                el.id.startsWith("perPage") ||
                el.closest(".toolbar-entries, .dash-table-toolbar, .datatable-toolbar, #tableDataContainer, .datatable-card-wrapper")
            )) ||
            (el.tagName === "INPUT" && !isDatatableSearchInput(el) && (
                el.id.startsWith("filter") ||
                el.id.startsWith("adminFilter") ||
                el.classList.contains("toolbar-filter-date") ||
                el.hasAttribute("data-live-filter") ||
                el.closest(".toolbar-entries, .dash-table-toolbar, .datatable-toolbar")
            ));

        if (!isFilter) return;

        e.stopImmediatePropagation();
        const targetUrl = window.collectDatatableUrl(el);
        if (typeof window.refreshLiveTable === "function") {
            window.refreshLiveTable(targetUrl);
        } else {
            window.location.href = targetUrl;
        }
    }, true);

    // Universal Delegated Handler for Reset Filter Buttons
    document.addEventListener("click", function (e) {
        const resetBtn = e.target.closest("[data-live-reset], .btn-reset-filter, #resetFilterAdmin, #btnResetFilter, a[title*='Reset filter'], a[title*='Reset']");
        if (resetBtn && resetBtn.href) {
            e.preventDefault();
            const wrap = resetBtn.closest(".datatable-toolbar, .dash-table-toolbar, .card, body");
            const sInput = wrap ? wrap.querySelector("input#liveSearch, input#liveSearchInput, input#liveSearchAdmin, .live-search-wrap input") : null;
            if (sInput) {
                sInput.value = "";
                const cBtn = wrap ? wrap.querySelector(".clear-search") : null;
                if (cBtn) cBtn.classList.remove("visible");
            }
            if (typeof window.refreshLiveTable === "function") {
                window.refreshLiveTable(resetBtn.href);
            } else {
                window.location.href = resetBtn.href;
            }
        }
    });

    // Universal Delegated Click-to-Copy Handler (.copyable, [data-copy])
    document.addEventListener("click", function (e) {
        const copyEl = e.target.closest(".copyable, [data-copy]");
        if (!copyEl) return;
        e.preventDefault();
        e.stopPropagation();

        const raw = copyEl.getAttribute("data-copy") || copyEl.innerText.trim();
        const textToCopy = raw
            .replace(/^(?:NIK|NISN|NIPD|NUPTK|NIP|ID)\s*:\s*/i, "")
            .trim();
        const label = copyEl.getAttribute("data-label") || "Teks";

        if (!textToCopy || textToCopy === "-") return;

        if (window.SAE && typeof window.SAE.copy === "function") {
            window.SAE.copy(textToCopy, label);
        } else if (navigator.clipboard) {
            navigator.clipboard.writeText(textToCopy);
        }

        copyEl.classList.add("copied");
        setTimeout(() => copyEl.classList.remove("copied"), 1800);
    });
})();

