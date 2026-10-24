// =========================================================================
// SAE GLOBAL LIVE DATATABLE SEARCH & PAGINATION ENGINE
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
            type === "hidden"
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
            id.includes("filter")
        ) {
            return true;
        }
        const name = (el.name || "").toLowerCase();
        if (
            name === "q" ||
            name === "search" ||
            name === "cari" ||
            name === "filter" ||
            name === "keyword"
        ) {
            return true;
        }
        return false;
    }

    let searchTimer = null;
    let pendingSearchInput = null;

    // Fast-typing debounce listener on keyup/input
    document.addEventListener("input", function (e) {
        const input = e.target;
        if (!isDatatableSearchInput(input)) return;

        pendingSearchInput = input;
        clearTimeout(searchTimer);
        searchTimer = setTimeout(function () {
            executeDatatableSearch(pendingSearchInput);
        }, 320);
    });

    document.addEventListener("keydown", function (e) {
        const input = e.target;
        if (!isDatatableSearchInput(input)) return;

        if (e.key === "Enter") {
            e.preventDefault();
            clearTimeout(searchTimer);
            executeDatatableSearch(input);
        }
    });

    function executeDatatableSearch(input) {
        if (!input) return;
        const val = input.value.trim();
        const paramName = input.name || "q";

        const currentUrl = new URL(window.location.href);
        if (val) {
            currentUrl.searchParams.set(paramName, val);
        } else {
            currentUrl.searchParams.delete(paramName);
        }

        // Reset page to 1 on new search
        currentUrl.searchParams.delete("page");
        const customPageParam = input.getAttribute("data-page-param");
        if (customPageParam) {
            currentUrl.searchParams.delete(customPageParam);
        }

        if (typeof window.refreshLiveTable === "function") {
            window.refreshLiveTable(currentUrl.toString());
        } else {
            const form = input.closest("form");
            if (form && !form.hasAttribute("data-no-ajax")) {
                form.submit();
            } else {
                window.location.href = currentUrl.toString();
            }
        }
    }
})();
