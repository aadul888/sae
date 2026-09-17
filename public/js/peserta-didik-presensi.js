/**
 * Modul Riwayat Presensi Peserta Didik (SAE)
 * Standar Resmi: Interaksi Toolbar Filter Periode, Live Search, Modal Snapshot & Pengajuan E-Izin
 */
document.addEventListener("DOMContentLoaded", function () {
    // 1. Dynamic Toggle Periode Filter (Bulan / Semester / Tahun)
    const filterPeriodeTipe = document.getElementById("filterPeriodeTipe");
    const filterWrapBulan = document.getElementById("filterWrapBulan");
    const filterWrapSemester = document.getElementById("filterWrapSemester");
    const filterWrapTa = document.getElementById("filterWrapTa");

    if (filterPeriodeTipe) {
        filterPeriodeTipe.addEventListener("change", function () {
            const val = this.value;
            if (filterWrapBulan) {
                filterWrapBulan.style.display = val === "bulan" ? "flex" : "none";
            }
            if (filterWrapSemester) {
                filterWrapSemester.style.display = val === "semester" ? "flex" : "none";
            }
            if (filterWrapTa) {
                filterWrapTa.style.display = val !== "bulan" ? "flex" : "none";
            }
        });
    }

    // 2. Per-page select otomatis submit form
    const perPageSelect = document.getElementById("perPageSelect");
    const formFilterPresensi = document.getElementById("formFilterPresensi");
    if (perPageSelect && formFilterPresensi) {
        perPageSelect.addEventListener("change", function () {
            formFilterPresensi.submit();
        });
    }

    // 3. Clear Search Button
    const searchInput = document.getElementById("liveSearchInput");
    const clearSearchBtn = document.querySelector(".live-search-wrap .clear-search");

    if (searchInput && clearSearchBtn) {
        const toggleClearBtn = () => {
            clearSearchBtn.style.display = searchInput.value.trim() ? "flex" : "none";
        };
        toggleClearBtn();

        searchInput.addEventListener("input", toggleClearBtn);

        clearSearchBtn.addEventListener("click", function () {
            searchInput.value = "";
            toggleClearBtn();
            if (formFilterPresensi) formFilterPresensi.submit();
        });
    }

    // 4. Modal View Snapshot Kamera Gerbang
    const modalSnapshot = document.getElementById("modalSnapshotSaya");
    const imgSnapshot = document.getElementById("imgSnapshotSaya");
    const captionSnapshot = document.getElementById("snapshotSayaCaption");
    const btnCloseSnapshot = document.getElementById("btnCloseSnapshotSaya");

    document.querySelectorAll(".btn-view-snapshot").forEach((btn) => {
        btn.addEventListener("click", function () {
            const url = this.getAttribute("data-url");
            const caption = this.getAttribute("data-caption");
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

    // 5. ESC key modal closer
    document.addEventListener("keydown", function (e) {
        if (e.key === "Escape") {
            if (modalSnapshot && modalSnapshot.style.display !== "none") {
                modalSnapshot.style.display = "none";
            }
        }
    });
});

