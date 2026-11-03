/**
 * Maintenance Dashboard - Frontend JS
 * Tab Backup/Restore, konfirmasi pemulihan, dan pembersihan sistem.
 */
document.addEventListener("DOMContentLoaded", function () {
    // =========================================================================
    // 1. Tab Switcher: Cadangkan (Backup) vs Pemulihan (Restore)
    // =========================================================================
    const tabButtons = document.querySelectorAll(".dash-tab-btn");
    const tabPanes = document.querySelectorAll(".dash-tab-pane");

    function switchTab(targetSelector) {
        tabButtons.forEach((btn) => {
            const on = btn.dataset.target === targetSelector;
            btn.classList.toggle("active", on);
            btn.style.color = on ? "var(--primary)" : "var(--text-muted)";
            btn.style.borderBottom = on ? "2px solid var(--primary)" : "transparent";
        });
        tabPanes.forEach((pane) => {
            pane.style.display = "#" + pane.id === targetSelector ? "block" : "none";
        });

        const url = new URL(window.location.href);
        url.searchParams.set("tab", targetSelector.replace("#tab-", ""));
        window.history.replaceState({}, "", url);
    }

    tabButtons.forEach((btn) => {
        btn.addEventListener("click", function () {
            if (this.dataset.target) switchTab(this.dataset.target);
        });
    });

    // =========================================================================
    // 2. Form Restore: tampilkan nama berkas + konfirmasi sebelum unggah
    // =========================================================================
    const formRestore = document.getElementById("formRestoreData");
    const restoreInput = document.getElementById("restoreFileInput");
    const restoreName = document.getElementById("restoreFileName");

    if (restoreInput && restoreName) {
        restoreInput.addEventListener("change", function () {
            const f = this.files && this.files[0];
            restoreName.textContent = f
                ? f.name + " (" + (f.size / 1048576).toFixed(1) + " MB)"
                : "Pilih berkas .sql atau .zip (maks. 500 MB)";
        });
    }

    if (formRestore) {
        formRestore.addEventListener("submit", async function (e) {
            if (formRestore.dataset.confirmed === "1") return;
            e.preventDefault();
            if (!restoreInput || !restoreInput.files.length) {
                if (window.SAE && typeof window.SAE.toast === "function") {
                    window.SAE.toast("Pilih berkas .sql atau .zip terlebih dahulu.", "warning");
                }
                return;
            }

            const ok = await window.SAE.confirm(
                "Seluruh data aplikasi saat ini akan DITIMPA dengan isi berkas arsip. Pastikan Anda sudah mengunduh cadangan terbaru. Lanjutkan?",
                "Pulihkan Database?",
                "warning",
                "Ya, Pulihkan!",
                "Batal"
            );
            if (!ok) return;

            formRestore.dataset.confirmed = "1";
            const btn = formRestore.querySelector("button[type='submit']");
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memulihkan...';
            }
            formRestore.submit();
        });
    }

    // =========================================================================
    // 3. Form Pembersihan Sistem
    // =========================================================================
    const formClean = document.getElementById("formCleanData");
    if (!formClean) return;

    formClean.addEventListener("submit", async function (e) {
        e.preventDefault();

        const btn = formClean.querySelector("button[type='submit']");
        const originalBtnHtml = btn ? btn.innerHTML : "";

        const confirmed = await window.SAE.confirm(
            "Anda akan membersihkan cache & log data lama aplikasi. Proses ini aman dan tidak menghapus data Dapodik.",
            "Bersihkan Sistem?",
            "danger",
            "Ya, Bersihkan!",
            "Batal"
        );

        if (!confirmed) return;

        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Membersihkan...';
        }

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || "";
            const res = await fetch(formClean.action, {
                method: "POST",
                headers: {
                    "X-CSRF-TOKEN": csrfToken,
                    "Accept": "application/json",
                    "Content-Type": "application/json",
                },
            });

            const data = await res.json();

            if (data.status === "success") {
                if (window.SAE && typeof window.SAE.toast === "function") {
                    window.SAE.toast(data.message || "Sistem berhasil dibersihkan!", "success");
                }
            } else {
                throw new Error(data.message || "Terjadi kesalahan saat membersihkan sistem.");
            }
        } catch (error) {
            if (window.SAE && typeof window.SAE.toast === "function") {
                window.SAE.toast(error.message || "Gagal membersihkan data sistem.", "danger");
            } else {
                window.SAE.alert("Gagal: " + error.message, 'Informasi', 'info');
            }
        } finally {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = originalBtnHtml;
            }
        }
    });
});
