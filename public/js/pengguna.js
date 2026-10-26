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
    const passwordLabel = document.getElementById("passwordLabel");

    // --- Create Modal (Tambah Pengguna Baru) ---
    window.openCreateModal = function () {
        if (!modal || !form) return;
        title.textContent = "Tambah Pengguna Baru";
        form.action = "/dashboard/pengguna";
        methodField.innerHTML = "";
        document.getElementById("inputNama").value = "";
        document.getElementById("inputUsername").value = "";

        const urlParams = new URLSearchParams(window.location.search);
        const currentTab = urlParams.get("tab") || "admin";
        const peranMap = {
            admin: "Administrator",
            guru: "Guru",
            tendik: "Tenaga Kependidikan",
            peserta_didik: "Peserta Didik",
        };
        const inputPeran = document.getElementById("inputPeran");
        if (inputPeran) inputPeran.value = peranMap[currentTab] || "Guru";

        document.getElementById("inputHp").value = "";
        document.getElementById("inputAlamat").value = "";
        const inputRfid = document.getElementById("inputRfidUid");
        if (inputRfid) inputRfid.value = "";
        inputPassword.value = "";
        inputPassword.required = true;
        if (passwordLabel) passwordLabel.innerHTML = 'Password Awal <span style="color:#ef4444;">*</span>';
        if (passwordHelp) passwordHelp.style.display = "none";
        modal.style.display = "flex";
    };

    // --- Edit Modal ---
    window.openEditModal = function (user) {
        if (!modal || !form) return;
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
        if (passwordLabel) passwordLabel.textContent = "Password Baru";
        if (passwordHelp) passwordHelp.style.display = "block";
        modal.style.display = "flex";
    };

    window.closeUserModal = function () {
        if (modal) modal.style.display = "none";
    };

    // --- Live Search with Debounce ---
    let debounceTimer;
    const liveSearch = document.getElementById("liveSearch");
    const clearSearch = document.getElementById("clearSearch");

    function cleanUrlParams(url) {
        url.searchParams.delete("admin_page");
        url.searchParams.delete("guru_page");
        url.searchParams.delete("tendik_page");
        url.searchParams.delete("peserta_didik_page");
        url.searchParams.delete("tugas_page");
        if (!url.searchParams.get("tab")) {
            const activeTabBtn = document.querySelector(".dash-desktop-tabs .btn-primary");
            if (activeTabBtn && activeTabBtn.href) {
                try {
                    const tabUrlObj = new URL(activeTabBtn.href, window.location.origin);
                    const t = tabUrlObj.searchParams.get("tab");
                    if (t) url.searchParams.set("tab", t);
                } catch (e) {}
            }
        }
    }

    if (liveSearch) {
        const triggerSearch = (val) => {
            const url = new URL(window.location.href);
            cleanUrlParams(url);
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
            cleanUrlParams(url);
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
            cleanUrlParams(url);
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
            cleanUrlParams(url);
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
        const role = (targetForm.dataset.role || "").toLowerCase();
        const isDelete = type === "delete" || type === "delete-duty";

        const title = isDelete ? (type === "delete-duty" ? "Hapus Penugasan?" : "Hapus Pengguna?") : "Reset Password?";
        let msg = "";
        if (type === "delete-duty") {
            msg = `Yakin ingin menghapus penugasan tugas tambahan untuk <strong>${nama}</strong>?<br><span style="color:#ef4444; font-size:0.8rem;">Tindakan ini tidak dapat dibatalkan.</span>`;
        } else if (isDelete) {
            msg = `Yakin ingin menghapus akun pengguna <strong>${nama}</strong>?<br><span style="color:#ef4444; font-size:0.8rem;">Tindakan ini tidak dapat dibatalkan.</span>`;
        } else if (role.includes("peserta didik")) {
            msg = `Reset password akun peserta didik <strong>${nama}</strong> ke default (NISN)?`;
        } else if (role.includes("guru")) {
            msg = `Reset password akun guru <strong>${nama}</strong> ke default (NIP / Geteka543!)?`;
        } else if (role.includes("tendik") || role.includes("kependidikan")) {
            msg = `Reset password akun tendik <strong>${nama}</strong> ke default (NIP / Geteka543!)?`;
        } else {
            msg = `Reset password akun <strong>${nama}</strong> ke password default sistem?`;
        }

        const confirmBtnText = isDelete ? (type === "delete-duty" ? "Ya, Hapus Penugasan" : "Ya, Hapus Akun") : "Ya, Reset Password";

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
    const dutyJurusanWrap = document.getElementById("dutyJurusanWrap");
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

    window.openDutyModal = function () {
        const modal = document.getElementById("dutyModal");
        if (modal) modal.style.display = "flex";
    };

    window.closeDutyModal = function () {
        const modal = document.getElementById("dutyModal");
        if (modal) {
            modal.style.display = "none";
            const form = document.getElementById("formAddDuty");
            if (form) form.reset();
            const rombelWrap = document.getElementById("dutyRombelWrap");
            const rombelSelect = document.getElementById("dutyRombelSelect");
            if (rombelWrap) {
                rombelWrap.style.display = "none";
                if (rombelSelect) rombelSelect.required = false;
            }
            const jurusanWrap = document.getElementById("dutyJurusanWrap");
            if (jurusanWrap) jurusanWrap.style.display = "none";
        }
    };

    if (btnOpenAssignModal) btnOpenAssignModal.addEventListener("click", window.openDutyModal);
    if (btnCloseDutyModal) btnCloseDutyModal.addEventListener("click", window.closeDutyModal);
    if (btnCancelDutyModal) btnCancelDutyModal.addEventListener("click", window.closeDutyModal);

    // Tugas yang memerlukan penetapan jurusan (scoping data per jurusan)
    const JURUSAN_DUTIES = ["LABORAN", "KAPROG", "KEPALA_LAB"];

    if (dutyTugasSelect && dutyRombelWrap) {
        dutyTugasSelect.addEventListener("change", () => {
            const selectedOpt = dutyTugasSelect.options[dutyTugasSelect.selectedIndex];
            const kode = selectedOpt ? selectedOpt.dataset.kode : "";
            const isWali = kode === "WALI_KELAS";
            dutyRombelWrap.style.display = isWali ? "block" : "none";
            if (dutyRombelSelect) dutyRombelSelect.required = isWali;

            if (dutyJurusanWrap) {
                const needsJurusan = JURUSAN_DUTIES.includes(kode);
                dutyJurusanWrap.style.display = needsJurusan ? "block" : "none";
            }
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
                        "X-CSRF-TOKEN": csrfToken || document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") || ""
                    },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (data.status === "success") {
                    showToast(data.message, "success");
                    window.closeDutyModal();
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

    // =========================================================================
    // Ubah Penugasan Tugas Tambahan (termasuk penetapan Jurusan)
    // =========================================================================
    const editDutyModal = document.getElementById("modalEditDuty");
    const btnCloseEditDuty = document.getElementById("btnCloseEditDuty");
    const btnCancelEditDuty = document.getElementById("btnCancelEditDuty");
    const formEditDuty = document.getElementById("formEditDuty");

    window.closeEditDutyModal = function () {
        const modal = document.getElementById("modalEditDuty");
        if (modal) modal.style.display = "none";
    };

    if (btnCloseEditDuty) btnCloseEditDuty.addEventListener("click", window.closeEditDutyModal);
    if (btnCancelEditDuty) btnCancelEditDuty.addEventListener("click", window.closeEditDutyModal);

    window.openEditDutyModal = function (trigger) {
        const modal = document.getElementById("modalEditDuty");
        if (!modal) return;

        const d = (trigger instanceof HTMLElement) ? trigger.dataset : (trigger || {});
        const kode = d.tugasKode || d.tugas_kode || "";
        const needsJurusan = JURUSAN_DUTIES.includes(kode);
        const isWali = kode === "WALI_KELAS";

        const ptkNama = d.ptkNama || d.ptk_nama || "-";
        const tugasNama = d.tugasNama || d.tugas_nama || "-";

        const info = document.getElementById("editDutyInfo");
        if (info) info.value = `${ptkNama} — ${tugasNama}`;

        const form = document.getElementById("formEditDuty");
        if (form) {
            const dutyModalEl = document.getElementById("dutyModal");
            const base = dutyModalEl?.dataset.destroyBaseUrl || "/dashboard/pengguna/tugas-tambahan";
            const dutyId = d.id;
            form.action = `${base}/${dutyId}`;
            form.dataset.id = dutyId;
        }

        const editDutyJurusanWrap = document.getElementById("editDutyJurusanWrap");
        const editDutyRombelWrap = document.getElementById("editDutyRombelWrap");
        const editDutyJurusan = document.getElementById("editDutyJurusan");
        const editDutyRombel = document.getElementById("editDutyRombel");

        if (editDutyJurusanWrap) editDutyJurusanWrap.style.display = needsJurusan ? "block" : "none";
        if (editDutyRombelWrap) editDutyRombelWrap.style.display = isWali ? "block" : "none";
        if (editDutyJurusan) editDutyJurusan.value = d.jurusan || d.jurusan_id || "";
        if (editDutyRombel) editDutyRombel.value = d.rombel || d.rombel_id || "";

        const setVal = (id, val) => {
            const el = document.getElementById(id);
            if (el) el.value = val || "";
        };
        setVal("editDutyNomorSk", d.nomorSk || d.nomor_sk);
        setVal("editDutyTmt", d.tmt || d.tmt_tugas);
        setVal("editDutyTst", d.tst || d.tst_tugas);
        setVal("editDutyKeterangan", d.keterangan);
        setVal("editDutyAktif", (d.aktif === "1" || d.is_active == 1) ? "1" : "0");

        modal.style.display = "flex";
    };

    // Delegasi click untuk tombol edit duty
    document.addEventListener("click", function (e) {
        const btn = e.target.closest(".btn-edit-duty");
        if (btn) {
            e.preventDefault();
            window.openEditDutyModal(btn);
        }
    });

    if (formEditDuty) {
        formEditDuty.addEventListener("submit", async (e) => {
            e.preventDefault();
            const btnSubmit = document.getElementById("btnSubmitEditDuty");
            const originalText = btnSubmit ? btnSubmit.innerHTML : "";
            if (btnSubmit) {
                btnSubmit.disabled = true;
                btnSubmit.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Menyimpan...';
            }

            const payload = Object.fromEntries(new FormData(formEditDuty).entries());
            delete payload._method;
            delete payload._token;

            const token = csrfToken || formEditDuty.querySelector('input[name="_token"]')?.value || document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") || "";

            try {
                const res = await fetch(formEditDuty.action, {
                    method: "PUT",
                    headers: {
                        "Content-Type": "application/json",
                        "Accept": "application/json",
                        "X-CSRF-TOKEN": token
                    },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (res.ok && data.status === "success") {
                    showToast(data.message || "Penugasan berhasil diperbarui.", "success");
                    window.closeEditDutyModal();
                    setTimeout(() => window.location.reload(), 600);
                } else {
                    throw new Error(data.message || "Gagal memperbarui penugasan");
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

    // Fallback Hapus Penugasan Tugas Tambahan (jika tombol tidak berada di dalam form[data-action-type])
    document.addEventListener("click", async (e) => {
        const btn = e.target.closest(".btn-delete-duty");
        if (!btn || btn.closest("form[data-action-type]")) return;

        e.preventDefault();
        const id = btn.dataset.id;
        const name = btn.dataset.name || "item ini";
        const dutyModalEl = document.getElementById("dutyModal");
        const destroyBaseUrl = dutyModalEl?.dataset.destroyBaseUrl || "/dashboard/pengguna/tugas-tambahan";

        let confirmed = false;
        const confirmMsg = `Hapus penugasan tugas tambahan untuk <strong>${name}</strong>?`;
        if (window.SAE && typeof window.SAE.confirm === "function") {
            confirmed = await window.SAE.confirm(confirmMsg, "Hapus Penugasan?", "danger", "Ya, Hapus Penugasan", "Batal");
        } else if (typeof Swal !== "undefined") {
            const result = await Swal.fire({
                title: "Hapus Penugasan?",
                html: confirmMsg,
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Ya, Hapus Penugasan",
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
                    "X-CSRF-TOKEN": csrfToken || document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") || ""
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
                        "X-CSRF-TOKEN": csrfToken || document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") || ""
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

    // Backdrop click dan ESC key untuk seluruh modal pengguna & tugas tambahan
    window.addEventListener("click", function (event) {
        const userM = document.getElementById("userModal");
        if (event.target === userM) closeUserModal();
        const dutyM = document.getElementById("dutyModal");
        if (event.target === dutyM) window.closeDutyModal();
        const editDutyM = document.getElementById("modalEditDuty");
        if (event.target === editDutyM) window.closeEditDutyModal();
    });

    document.addEventListener("keydown", function (e) {
        if (e.key === "Escape") {
            closeUserModal();
            window.closeDutyModal();
            window.closeEditDutyModal();
        }
    });
});
