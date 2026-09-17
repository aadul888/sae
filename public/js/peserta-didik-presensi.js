/**
 * Modul Riwayat Presensi Peserta Didik (SAE)
 * Standar Resmi: Interaksi Toolbar Filter Periode, Auto Submit, Modal Snapshot
 */
document.addEventListener("DOMContentLoaded", function () {
    const formFilterPresensi = document.getElementById("formFilterPresensi");
    const filterPeriodeTipe = document.getElementById("filterPeriodeTipe");
    const filterWrapBulan = document.getElementById("filterWrapBulan");
    const filterWrapSemester = document.getElementById("filterWrapSemester");
    const filterWrapTa = document.getElementById("filterWrapTa");

    const filterBulan = document.getElementById("filterBulan");
    const filterTahun = document.getElementById("filterTahun");
    const filterSemester = document.getElementById("filterSemester");
    const filterTa = document.getElementById("filterTa");
    const perPageSelect = document.getElementById("perPageSelect");

    // 1. Toggle Periode Filter & Auto-Submit saat filter diganti
    if (filterPeriodeTipe) {
        filterPeriodeTipe.addEventListener("change", function () {
            const val = this.value;
            if (filterWrapBulan) {
                filterWrapBulan.style.display = val === "bulan" ? "inline-flex" : "none";
            }
            if (filterWrapSemester) {
                filterWrapSemester.style.display = val === "semester" ? "inline-flex" : "none";
            }
            if (filterWrapTa) {
                filterWrapTa.style.display = val !== "bulan" ? "inline-flex" : "none";
            }
            if (formFilterPresensi) formFilterPresensi.submit();
        });
    }

    // Auto submit dropdown perubahan
    [filterBulan, filterTahun, filterSemester, filterTa, perPageSelect].forEach((el) => {
        if (el && formFilterPresensi) {
            el.addEventListener("change", function () {
                formFilterPresensi.submit();
            });
        }
    });

    // 2. Live Search Box (Debounce 450ms & Clear Button)
    const searchInput = document.getElementById("liveSearchInput");
    const clearSearchBtn = document.getElementById("clearSearch");
    let searchDebounce = null;

    if (searchInput) {
        searchInput.addEventListener("input", function () {
            if (clearSearchBtn) {
                if (this.value.trim()) {
                    clearSearchBtn.classList.add("visible");
                } else {
                    clearSearchBtn.classList.remove("visible");
                }
            }
            clearTimeout(searchDebounce);
            searchDebounce = setTimeout(() => {
                if (formFilterPresensi) formFilterPresensi.submit();
            }, 450);
        });
    }

    if (clearSearchBtn && searchInput) {
        clearSearchBtn.addEventListener("click", function () {
            searchInput.value = "";
            clearSearchBtn.classList.remove("visible");
            if (formFilterPresensi) formFilterPresensi.submit();
        });
    }

    // 3. Modal View Snapshot Kamera Gerbang
    const modalSnapshot = document.getElementById("modalSnapshotSaya");
    const imgSnapshot = document.getElementById("imgSnapshotSaya");
    const captionSnapshot = document.getElementById("snapshotSayaCaption");
    const btnCloseSnapshot = document.getElementById("btnCloseSnapshotSaya");

    document.querySelectorAll(".btn-view-snapshot").forEach((btn) => {
        btn.addEventListener("click", function () {
            const url = this.getAttribute("data-url");
            const caption = this.getAttribute("data-title") || this.getAttribute("data-caption");
            if (imgSnapshot) imgSnapshot.src = url;
            if (captionSnapshot) captionSnapshot.textContent = caption || "Foto Bukti Presensi";
            if (modalSnapshot) modalSnapshot.style.display = "flex";
        });
    });

    if (btnCloseSnapshot && modalSnapshot) {
        btnCloseSnapshot.addEventListener("click", function () {
            modalSnapshot.style.display = "none";
        });
    }

    if (modalSnapshot) {
        modalSnapshot.addEventListener("click", function (e) {
            if (e.target === modalSnapshot) {
                modalSnapshot.style.display = "none";
            }
        });
    }

    // 4. ESC key modal closer
    document.addEventListener("keydown", function (e) {
        if (e.key === "Escape") {
            if (modalSnapshot && modalSnapshot.style.display !== "none") {
                modalSnapshot.style.display = "none";
            }
        }
    });
});
