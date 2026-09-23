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
        const inputRfid = document.getElementById("inputRfidUid");
        if (inputRfid) inputRfid.value = user.rfid_uid || "";
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

    // =========================================================================
    // TUGAS TAMBAHAN & PENUGASAN (TAB 5)
    // =========================================================================
    const dutyModal = document.getElementById("dutyModal");
    const btnOpenAssignModal = document.getElementById("btnOpenAssignModal");
    const btnCloseDutyModal = document.getElementById("btnCloseDutyModal");
    const btnCancelDutyModal = document.getElementById("btnCancelDutyModal");
    const dutyTugasSelect = document.getElementById("dutyTugasSelect");
    const dutyRombelWrap = document.getElementById("dutyRombelWrap");
    const dutyRombelSelect = document.getElementById("dutyRombelSelect");
    const formAddDuty = document.getElementById("formAddDuty");
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") || "";

    const showToast = (title, icon = "success") => {
        const type = (icon === "error" || icon === "danger") ? "danger" : (icon === "warning" ? "warning" : "success");
        if (window.SAE && typeof window.SAE.toast === "function") {
            window.SAE.toast(title, type);
        } else if (typeof Swal !== "undefined") {
            Swal.fire({
                toast: true,
                position: "top-end",
                icon: type === "danger" ? "error" : type,
                title: title,
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            });
        }
    };

    if (btnOpenAssignModal && dutyModal) {
        btnOpenAssignModal.addEventListener("click", () => {
            dutyModal.style.display = "flex";
        });
    }

    const closeDutyModal = () => {
        if (dutyModal) {
            dutyModal.style.display = "none";
            if (formAddDuty) formAddDuty.reset();
            if (dutyRombelWrap) {
                dutyRombelWrap.style.display = "none";
                if (dutyRombelSelect) dutyRombelSelect.required = false;
            }
        }
    };

    if (btnCloseDutyModal) btnCloseDutyModal.addEventListener("click", closeDutyModal);
    if (btnCancelDutyModal) btnCancelDutyModal.addEventListener("click", closeDutyModal);

    if (dutyModal) {
        dutyModal.addEventListener("click", (e) => {
            if (e.target === dutyModal) closeDutyModal();
        });
    }

    if (dutyTugasSelect && dutyRombelWrap) {
        dutyTugasSelect.addEventListener("change", () => {
            const selectedOpt = dutyTugasSelect.options[dutyTugasSelect.selectedIndex];
            const kode = selectedOpt ? selectedOpt.dataset.kode : "";
            const isWali = kode === "WALI_KELAS";
            dutyRombelWrap.style.display = isWali ? "block" : "none";
            if (dutyRombelSelect) dutyRombelSelect.required = isWali;
        });
    }

    if (formAddDuty) {
        formAddDuty.addEventListener("submit", async (e) => {
            e.preventDefault();
            const btnSubmit = document.getElementById("btnSubmitDutyModal");
            const originalText = btnSubmit ? btnSubmit.innerHTML : "";
            if (btnSubmit) {
                btnSubmit.disabled = true;
                btnSubmit.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Menyimpan...';
            }

            const formData = new FormData(formAddDuty);
            const payload = Object.fromEntries(formData.entries());
            const storeUrl = dutyModal?.dataset.storeUrl || "/dashboard/pengguna/tugas-tambahan/store";

            try {
                const res = await fetch(storeUrl, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "Accept": "application/json",
                        "X-CSRF-TOKEN": csrfToken
                    },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (data.status === "success") {
                    showToast(data.message, "success");
                    closeDutyModal();
                    setTimeout(() => window.location.reload(), 600);
                } else {
                    throw new Error(data.message || "Gagal menyimpan penugasan");
                }
            } catch (err) {
                showToast(err.message, "danger");
                if (btnSubmit) {
                    btnSubmit.disabled = false;
                    btnSubmit.innerHTML = originalText;
                }
            }
        });
    }

    // Hapus Penugasan Tugas Tambahan
    document.querySelectorAll(".btn-delete-duty").forEach((btn) => {
        btn.addEventListener("click", async () => {
            const id = btn.dataset.id;
            const name = btn.dataset.name;
            const destroyBaseUrl = dutyModal?.dataset.destroyBaseUrl || "/dashboard/pengguna/tugas-tambahan";

            let confirmed = false;
            const confirmMsg = `Hapus penugasan tugas tambahan untuk <strong>${name}</strong>?`;
            if (window.SAE && typeof window.SAE.confirm === "function") {
                confirmed = await window.SAE.confirm(confirmMsg, "Hapus Penugasan?", "danger", "Ya, Hapus", "Batal");
            } else if (typeof Swal !== "undefined") {
                const result = await Swal.fire({
                    title: "Hapus Penugasan?",
                    html: confirmMsg,
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonText: "Ya, Hapus",
                    cancelButtonText: "Batal"
                });
                confirmed = result.isConfirmed;
            }

            if (!confirmed) return;

            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

            try {
                const res = await fetch(`${destroyBaseUrl}/${id}`, {
                    method: "DELETE",
                    headers: {
                        "Content-Type": "application/json",
                        "Accept": "application/json",
                        "X-CSRF-TOKEN": csrfToken
                    }
                });
                const data = await res.json();
                if (data.status === "success") {
                    showToast(data.message || "Penugasan berhasil dihapus", "success");
                    setTimeout(() => window.location.reload(), 600);
                } else {
                    throw new Error(data.message || "Gagal menghapus penugasan");
                }
            } catch (err) {
                showToast(err.message, "danger");
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-trash-can"></i>';
            }
        });
    });

    // Pemicu Sinkronisasi Wali Kelas dari Dapodik
    const btnSyncWaliKelas = document.getElementById("btnSyncWaliKelas");
    if (btnSyncWaliKelas) {
        btnSyncWaliKelas.addEventListener("click", async () => {
            const syncUrl = dutyModal?.dataset.syncWaliUrl || "/dashboard/pengguna/tugas-tambahan/sync-wali";
            const originalHtml = btnSyncWaliKelas.innerHTML;

            let confirmed = false;
            const confirmMsg = "Sistem akan membaca wali kelas seluruh rombel reguler aktif dari Dapodik dan menyinkronkan penugasan tugas tambahan secara otomatis.";
            if (window.SAE && typeof window.SAE.confirm === "function") {
                confirmed = await window.SAE.confirm(confirmMsg, "Tarik Wali Kelas?", "info", "Ya, Sinkronkan", "Batal");
            } else if (typeof Swal !== "undefined") {
                const result = await Swal.fire({
                    title: "Tarik Wali Kelas?",
                    text: confirmMsg,
                    icon: "info",
                    showCancelButton: true,
                    confirmButtonText: "Ya, Sinkronkan",
                    cancelButtonText: "Batal"
                });
                confirmed = result.isConfirmed;
            }

            if (!confirmed) return;

            btnSyncWaliKelas.disabled = true;
            btnSyncWaliKelas.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Menyinkronkan...';

            try {
                const res = await fetch(syncUrl, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "Accept": "application/json",
                        "X-CSRF-TOKEN": csrfToken
                    }
                });
                const data = await res.json();
                if (data.status === "success") {
                    showToast(data.message, "success");
                    setTimeout(() => window.location.reload(), 800);
                } else {
                    throw new Error(data.message || "Gagal menyinkronkan");
                }
            } catch (err) {
                showToast(err.message, "danger");
            } finally {
                btnSyncWaliKelas.disabled = false;
                btnSyncWaliKelas.innerHTML = originalHtml;
            }
        });
    }
});
