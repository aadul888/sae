/**
 * Manajemen Pengguna - Frontend JS
 * Dipisahkan dari blade untuk pemisahan frontend/backend.
 */
document.addEventListener("DOMContentLoaded", function () {
    const modal = document.getElementById("userModal");
    const form = document.getElementById("userForm");
    const title = document.getElementById("modalTitle");
    const methodField = document.getElementById("methodField");
    const passwordHelp = document.getElementById("passwordHelp");
    const inputPassword = document.getElementById("inputPassword");

    // --- Edit Modal ---
    window.openEditModal = function (user) {
        title.textContent = "Edit Data Pengguna";
        form.action = "/dashboard/pengguna/" + user.pengguna_id;
        methodField.innerHTML =
            '<input type="hidden" name="_method" value="PUT">';
        document.getElementById("inputNama").value = user.nama || "";
        document.getElementById("inputUsername").value = user.username || "";
        document.getElementById("inputPeran").value =
            user.peran_id_str || "Peserta Didik";
        document.getElementById("inputHp").value = user.no_hp || "";
        document.getElementById("inputAlamat").value = user.alamat || "";
        inputPassword.value = "";
        inputPassword.required = false;
        passwordHelp.style.display = "block";
        modal.style.display = "flex";
    };

    window.closeUserModal = function () {
        modal.style.display = "none";
    };

    window.onclick = function (event) {
        if (event.target === modal) closeUserModal();
    };

    // --- Live Search with Debounce ---
    let debounceTimer;
    const liveSearch = document.getElementById("liveSearch");
    const clearSearch = document.getElementById("clearSearch");

    if (liveSearch) {
        const triggerSearch = (val) => {
            const url = new URL(window.location.href);
            if (val) {
                url.searchParams.set("q", val);
            } else {
                url.searchParams.delete("q");
            }
            url.searchParams.set("page", "1");
            if (typeof window.refreshLiveTable === "function") {
                window.refreshLiveTable(url.toString());
            } else {
                window.location.href = url.toString();
            }
        };

        liveSearch.addEventListener("input", function () {
            clearSearch.classList.toggle("visible", this.value.length > 0);
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                triggerSearch(this.value);
            }, 300);
        });

        liveSearch.addEventListener("keydown", function (e) {
            if (e.key === "Enter") {
                e.preventDefault();
                clearTimeout(debounceTimer);
                triggerSearch(this.value);
            }
        });
    }

    if (clearSearch) {
        clearSearch.addEventListener("click", function () {
            liveSearch.value = "";
            clearSearch.classList.remove("visible");
            const url = new URL(window.location.href);
            url.searchParams.delete("q");
            url.searchParams.set("page", "1");
            if (typeof window.refreshLiveTable === "function") {
                window.refreshLiveTable(url.toString());
            } else {
                window.location.href = url.toString();
            }
        });
    }

    // --- Per Page Select ---
    const perPageSelect = document.getElementById("perPageSelect");
    if (perPageSelect) {
        perPageSelect.addEventListener("change", function () {
            const url = new URL(window.location.href);
            url.searchParams.set("perPage", this.value);
            url.searchParams.set("page", "1");
            if (typeof window.refreshLiveTable === "function") {
                window.refreshLiveTable(url.toString());
            } else {
                window.location.href = url.toString();
            }
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
            if (typeof window.refreshLiveTable === "function") {
                window.refreshLiveTable(url.toString());
            } else {
                window.location.href = url.toString();
            }
        });
    });

    // --- Form Confirmations (SAE Unified Dialog System) ---
    document.addEventListener("submit", async function (e) {
        const targetForm = e.target.closest("form[data-action-type]");
        if (!targetForm) return;

        e.preventDefault();
        const type = targetForm.dataset.actionType;
        const nama = targetForm.dataset.name || "item ini";
        const isDelete = type === "delete";

        const title = isDelete ? "Hapus Pengguna?" : "Reset Password?";
        const msg = isDelete
            ? `Yakin ingin menghapus akun pengguna <strong>${nama}</strong>?<br><span style="color:#ef4444; font-size:0.8rem;">Tindakan ini tidak dapat dibatalkan.</span>`
            : `Reset password akun <strong>${nama}</strong> ke default (NISN / Sae12345!)?`;
        const confirmBtnText = isDelete ? "Ya, Hapus Akun" : "Ya, Reset Password";

        const confirmed = await window.SAE.confirm(
            msg,
            title,
            isDelete ? "danger" : "warning",
            confirmBtnText,
            "Batal"
        );

        if (confirmed) {
            targetForm.submit();
        }
    });
});
