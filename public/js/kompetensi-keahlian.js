/**
 * Master Data — Kompetensi Keahlian - Frontend JS
 */
document.addEventListener("DOMContentLoaded", function () {
    const modal = document.getElementById("kkModal");
    const form = document.getElementById("kkForm");
    const title = document.getElementById("kkModalTitle");
    const methodField = document.getElementById("kkMethodField");

    // --- Add Modal ---
    window.openAddModal = function () {
        title.textContent = "Tambah Kompetensi Keahlian";
        form.action = "/dashboard/master-data/kompetensi-keahlian";
        methodField.innerHTML = "";
        document.getElementById("inputKode").value = "";
        document.getElementById("inputNama").value = "";
        document.getElementById("inputBidang").value = "";
        document.getElementById("inputProgram").value = "";
        document.getElementById("inputTahun").value = "";
        document.getElementById("inputActive").checked = true;
        modal.style.display = "flex";
    };

    // --- Edit Modal ---
    window.openEditModal = function (item) {
        title.textContent = "Edit Kompetensi Keahlian";
        form.action = "/dashboard/master-data/kompetensi-keahlian/" + item.id;
        methodField.innerHTML =
            '<input type="hidden" name="_method" value="PUT">';
        document.getElementById("inputKode").value = item.kode || "";
        document.getElementById("inputNama").value = item.nama || "";
        document.getElementById("inputBidang").value =
            item.bidang_keahlian || "";
        document.getElementById("inputProgram").value =
            item.program_keahlian || "";
        document.getElementById("inputTahun").value = item.tahun_berlaku || "";
        document.getElementById("inputActive").checked = !!item.is_active;
        modal.style.display = "flex";
    };

    window.closeKKModal = function () {
        modal.style.display = "none";
    };

    window.onclick = function (event) {
        if (event.target === modal) closeKKModal();
    };

    // --- Live Search with Debounce ---
    let debounceTimer;
    const liveSearch = document.getElementById("liveSearch");
    const clearSearch = document.getElementById("clearSearch");

    if (liveSearch) {
        liveSearch.addEventListener("input", function () {
            clearSearch.classList.toggle("visible", this.value.length > 0);
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                const url = new URL(window.location.href);
                if (this.value) {
                    url.searchParams.set("q", this.value);
                } else {
                    url.searchParams.delete("q");
                }
                url.searchParams.set("page", "1");
                window.location.href = url.toString();
            }, 400);
        });
    }

    if (clearSearch) {
        clearSearch.addEventListener("click", function () {
            liveSearch.value = "";
            clearSearch.classList.remove("visible");
            const url = new URL(window.location.href);
            url.searchParams.delete("q");
            url.searchParams.set("page", "1");
            window.location.href = url.toString();
        });
    }

    // --- Per Page Select ---
    const perPageSelect = document.getElementById("perPageSelect");
    if (perPageSelect) {
        perPageSelect.addEventListener("change", function () {
            const url = new URL(window.location.href);
            url.searchParams.set("perPage", this.value);
            url.searchParams.set("page", "1");
            window.location.href = url.toString();
        });
    }

    // --- Sortable Headers ---
    document.querySelectorAll(".sortable-th").forEach((th) => {
        th.addEventListener("click", function () {
            const sortKey = this.dataset.sort;
            const url = new URL(window.location.href);
            const currentSort = url.searchParams.get("sort");
            const currentDir = url.searchParams.get("sort_dir");
            if (currentSort === sortKey && currentDir === "asc") {
                url.searchParams.set("sort_dir", "desc");
            } else {
                url.searchParams.set("sort", sortKey);
                url.searchParams.set("sort_dir", "asc");
            }
            url.searchParams.set("page", "1");
            window.location.href = url.toString();
        });
    });

    // --- SweetAlert2 Delete Confirmations ---
    document.querySelectorAll("form[data-action-type]").forEach((form) => {
        form.addEventListener("submit", function (e) {
            e.preventDefault();
            const nama = this.dataset.name || "item ini";
            const targetForm = this;

            if (typeof Swal === "undefined") {
                if (confirm(`Yakin ingin menghapus ${nama}?`)) {
                    targetForm.submit();
                }
                return;
            }

            Swal.fire({
                title: "Hapus Kompetensi Keahlian?",
                html: `Yakin ingin menghapus <strong>${nama}</strong>?<br><small style="color:#ef4444;">Tindakan ini tidak dapat dibatalkan.</small>`,
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#ef4444",
                cancelButtonColor: "#64748b",
                confirmButtonText:
                    '<i class="fas fa-trash me-1"></i> Ya, Hapus',
                cancelButtonText: "Batal",
                reverseButtons: true,
            }).then((res) => {
                if (res.isConfirmed) {
                    targetForm.submit();
                }
            });
        });
    });
});
