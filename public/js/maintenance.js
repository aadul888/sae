/**
 * Maintenance Dashboard - Frontend JS
 * Menggunakan style alert dan dialog terpadu SAE
 */
document.addEventListener("DOMContentLoaded", function () {
    const formClean = document.getElementById("formCleanData");
    if (!formClean) return;

    formClean.addEventListener("submit", async function (e) {
        e.preventDefault();

        const btn = formClean.querySelector("button[type='submit']");
        const originalBtnHtml = btn ? btn.innerHTML : "";

        const confirmed = window.SAE && typeof window.SAE.confirm === "function"
            ? await window.SAE.confirm(
                  "Anda akan membersihkan cache & log data lama aplikasi. Proses ini aman dan tidak menghapus data Dapodik.",
                  "Bersihkan Sistem?",
                  "danger",
                  "Ya, Bersihkan!",
                  "Batal"
              )
            : confirm("Anda akan membersihkan cache & log data lama aplikasi. Lanjutkan?");

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
                alert("Gagal: " + error.message);
            }
        } finally {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = originalBtnHtml;
            }
        }
    });
});
