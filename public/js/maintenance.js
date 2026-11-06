/**
 * Maintenance Dashboard - Frontend JS
 * Tab Backup/Restore, proses backup & restore via AJAX + SweetAlert + auto refresh.
 */
document.addEventListener("DOMContentLoaded", function () {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || "";

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

    function startDownload(url) {
        const a = document.createElement("a");
        a.href = url;
        a.style.display = "none";
        document.body.appendChild(a);
        a.click();
        setTimeout(() => a.remove(), 1000);
    }

    // Baca respons dan laporkan pesan error yang sebenarnya (termasuk saat server
    // membalas HTML karena sesi berakhir / URL meleset).
    async function readJson(res) {
        const raw = await res.text().catch(() => "");
        let data = null;
        try {
            data = raw ? JSON.parse(raw) : null;
        } catch (_) {
            data = null;
        }
        return { data, raw };
    }

    function errorMessage(res, data, raw, fallback) {
        if (data && data.message) return data.message;
        if (data && data.errors) return Object.values(data.errors).flat().join(" ");
        if (res.status === 401 || res.status === 419) {
            return "Sesi login berakhir. Muat ulang halaman lalu coba lagi.";
        }
        if (res.status === 403) return "Akses ditolak (hanya administrator).";
        if (res.status === 413) return "Berkas terlalu besar untuk diproses server.";
        if (/<html/i.test(raw || "")) {
            return "Server membalas halaman HTML, bukan JSON (HTTP " + res.status + "). Kemungkinan sesi berakhir atau alamat aksi salah.";
        }
        return fallback + " (HTTP " + res.status + ")";
    }

    // =========================================================================
    // 2. Tombol Backup: buat paket via AJAX, lalu unduh + SweetAlert + refresh
    // =========================================================================
    const backupUrl = document.getElementById("tab-backup")?.dataset.backupUrl;
    const backupButtons = document.querySelectorAll(".js-backup-btn");

    backupButtons.forEach((btn) => {
        btn.addEventListener("click", async function (e) {
            e.preventDefault();
            if (!backupUrl || this.dataset.busy === "1") return;

            const original = this.innerHTML;
            this.dataset.busy = "1";
            this.style.pointerEvents = "none";
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyiapkan...';

            const targetUrl = this.href;
            const backupType = this.dataset.backupType || "full";

            try {
                if (backupUrl) {
                    const res = await fetch(backupUrl, {
                        method: "POST",
                        headers: {
                            "X-CSRF-TOKEN": csrfToken,
                            "Accept": "application/json",
                            "Content-Type": "application/json",
                        },
                        body: JSON.stringify({ type: backupType }),
                    });

                    const { data, raw } = await readJson(res);
                    if (!res.ok || !data || data.status !== "success") {
                        throw new Error(errorMessage(res, data, raw, "Gagal membuat paket arsip."));
                    }

                    startDownload(data.download_url);

                    window.SAE.alert(
                        `${data.message}<br><br><small style="color:#6b7280;">Berkas: <b>${data.filename}</b></small>`,
                        "Backup Berhasil",
                        "success"
                    ).then(function () {
                        window.location.reload();
                    });
                } else if (targetUrl) {
                    // Fallback direct download stream
                    window.location.href = targetUrl;
                }
            } catch (error) {
                // Bila AJAX timeout (mis. Cloudflare 524) atau gagal network, beri opsi unduh langsung
                const errMsg = error.message || "Gagal memproses paket arsip.";
                if (targetUrl && (errMsg.includes("524") || errMsg.includes("504") || errMsg.includes("Failed to fetch"))) {
                    const retryDirect = await window.SAE.confirm(
                        errMsg + "<br><br>Koneksi AJAX terputus oleh proxy/Cloudflare. Coba unduh langsung melalui browser stream?",
                        "Koneksi Terputus",
                        "warning",
                        "Unduh Langsung",
                        "Batal"
                    );
                    if (retryDirect) {
                        window.location.href = targetUrl;
                        return;
                    }
                } else if (window.SAE && typeof window.SAE.alert === "function") {
                    window.SAE.alert(errMsg, "Backup Gagal", "error");
                }
            } finally {
                this.dataset.busy = "";
                this.style.pointerEvents = "";
                this.innerHTML = original;
            }
        });
    });

    // =========================================================================
    // 3. Form Restore: unggah via AJAX + SweetAlert + auto refresh
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

            const btn = formRestore.querySelector("button[type='submit']");
            const original = btn ? btn.innerHTML : "";
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memulihkan...';
            }

            try {
                const res = await fetch(formRestore.action, {
                    method: "POST",
                    headers: {
                        "X-CSRF-TOKEN": csrfToken,
                        "Accept": "application/json",
                    },
                    body: new FormData(formRestore),
                });

                const { data, raw } = await readJson(res);

                if (!res.ok || !data || data.status !== "success") {
                    throw new Error(errorMessage(res, data, raw, "Gagal memulihkan database."));
                }

                window.SAE.alert(data.message, "Restore Berhasil", "success").then(function () {
                    window.location.reload();
                });
            } catch (error) {
                if (window.SAE && typeof window.SAE.alert === "function") {
                    window.SAE.alert(error.message || "Gagal memulihkan database.", "Restore Gagal", "error");
                }
            } finally {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = original;
                }
            }
        });
    }

    // =========================================================================
    // 4. Form Pembersihan Sistem
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
