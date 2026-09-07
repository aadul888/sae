/**
 * Update Sistem - Frontend JS
 * Dipisahkan dari blade untuk pemisahan frontend/backend.
 */
document.addEventListener("DOMContentLoaded", () => {
    const btnCheck = document.getElementById("btnCheckUpdate");
    const btnRun = document.getElementById("btnRunUpdate");
    const checkIcon = document.getElementById("checkIcon");
    const logBox = document.getElementById("updateLogBox");
    const updateBanner = document.getElementById("updateBanner");
    const bannerTitle = document.getElementById("bannerTitle");
    const bannerDesc = document.getElementById("bannerDesc");
    const pendingMigCount = document.getElementById("pendingMigCount");
    const statusBadge = document.getElementById("statusBadge");
    const lastUpdateText = document.getElementById("lastUpdateText");
    const migrationList = document.getElementById("migrationList");
    const migUl = document.getElementById("migUl");

    function getThemeColors() {
        const isLight =
            document.documentElement.getAttribute("data-theme") === "light";
        return {
            background: isLight ? "#ffffff" : "#0f172a",
            color: isLight ? "#0f172a" : "#f8fafc",
        };
    }

    function getCsrfToken() {
        const meta = document.querySelector('meta[name="csrf-token"]')?.getAttribute("content");
        if (meta) return meta;
        const cookie = document.cookie.split("; ").find((r) => r.startsWith("XSRF-TOKEN="));
        return cookie ? decodeURIComponent(cookie.split("=")[1]) : "";
    }

    btnCheck.addEventListener("click", async () => {
        checkIcon.classList.add("fa-spin");
        appendLog("Memeriksa update sistem...", "#94a3b8");
        try {
            const res = await fetch("/dashboard/update/check");
            const data = await res.json();
            if (data.status === "success") {
                const s = data.data;
                pendingMigCount.textContent = s.pending_migrations.length;
                if (s.last_update_at) {
                    lastUpdateText.textContent = s.last_update_at;
                }

                if (s.pending_migrations.length > 0) {
                    migUl.innerHTML = s.pending_migrations
                        .map((m) => `<li><code>${m}</code></li>`)
                        .join("");
                    migrationList.classList.remove("hidden");
                } else {
                    migrationList.classList.add("hidden");
                }

                if (s.updates_available) {
                    bannerTitle.textContent = "Pembaruan Tersedia!";
                    bannerDesc.textContent =
                        "Ditemukan file kode atau migrasi database baru yang siap dipasang.";
                    updateBanner.style.background = "rgba(245, 158, 11, 0.1)";
                    updateBanner.style.borderColor = "rgba(245, 158, 11, 0.3)";
                    statusBadge.className = "badge badge-warning";
                    statusBadge.textContent = "Pembaruan Siap";
                    btnRun.classList.remove("hidden");
                    btnRun.style.display = "inline-flex";
                    appendLog("Pembaruan terdeteksi!", "#fbbf24");
                } else {
                    bannerTitle.textContent = "Sistem Sudah yang Terbaru";
                    bannerDesc.textContent =
                        "Semua file kode dan struktur database sudah dalam kondisi prima.";
                    updateBanner.style.background = "rgba(16, 185, 129, 0.1)";
                    updateBanner.style.borderColor = "rgba(16, 185, 129, 0.3)";
                    statusBadge.className = "badge badge-primary";
                    statusBadge.textContent = "Versi Terkini";
                    btnRun.classList.add("hidden");
                    btnRun.style.display = "none";
                    appendLog("Sistem dalam versi terbaru.", "#34d399");
                }
            }
        } catch (err) {
            appendLog("Gagal memeriksa update: " + err.message, "#f87171");
        } finally {
            checkIcon.classList.remove("fa-spin");
        }
    });

    btnRun.addEventListener("click", async () => {
        const confirmed =
            typeof Swal !== "undefined"
                ? (
                      await Swal.fire({
                          title: "Jalankan Pembaruan Sistem?",
                          text: "Sistem akan memperbarui file kode dan migrasi database secara otomatis.",
                          icon: "question",
                          showCancelButton: true,
                          confirmButtonText:
                              '<i class="fas fa-download me-1"></i> Ya, Pasang Sekarang',
                          cancelButtonText: "Batal",
                          confirmButtonColor: "#6366f1",
                          cancelButtonColor: "#64748b",
                          ...getThemeColors(),
                      })
                  ).isConfirmed
                : confirm("Jalankan pembaruan sistem sekarang?");

        if (!confirmed) return;

        btnRun.disabled = true;
        btnRun.innerHTML =
            '<i class="fas fa-spinner fa-spin me-1"></i> Memproses...';
        appendLog("Memulai pembaruan sistem...", "#38bdf8");

        try {
            const token = getCsrfToken();
            const res = await fetch("/dashboard/update/execute", {
                method: "POST",
                headers: {
                    "X-CSRF-TOKEN": token,
                    "X-XSRF-TOKEN": token,
                    "Content-Type": "application/json",
                    Accept: "application/json",
                },
            });
            const text = await res.text();
            let data;
            try {
                data = JSON.parse(text);
            } catch (e) {
                throw new Error(
                    res.status === 419
                        ? "Sesi kedaluwarsa (CSRF token mismatch). Silakan refresh halaman."
                        : `Server mengembalikan respon HTML (HTTP ${res.status}). Cek error log server.`,
                );
            }
            if (data.status === "success") {
                data.data.logs.forEach((l) => appendLog(l, "#34d399"));
                appendLog("Update sistem selesai dengan sukses!", "#4ade80");
                btnRun.classList.add("hidden");
                btnRun.style.display = "none";
                bannerTitle.textContent = "Sistem Berhasil Diperbarui";
                bannerDesc.textContent =
                    "Semua file kode dan database telah disinkronkan.";
                updateBanner.style.background = "rgba(16, 185, 129, 0.1)";
                updateBanner.style.borderColor = "rgba(16, 185, 129, 0.3)";
                statusBadge.className = "badge badge-primary";
                statusBadge.textContent = "Versi Terkini";
                pendingMigCount.textContent = "0";
                migrationList.classList.add("hidden");
                if (data.data.timestamp) {
                    lastUpdateText.textContent = data.data.timestamp;
                }

                if (typeof Swal !== "undefined") {
                    Swal.fire({
                        title: "Berhasil!",
                        text: "Sistem berhasil diperbarui.",
                        icon: "success",
                        confirmButtonColor: "#10b981",
                        ...getThemeColors(),
                    });
                }
            } else {
                data.data.logs.forEach((l) => appendLog(l, "#f87171"));
                appendLog(
                    "Pembaruan selesai dengan catatan / error.",
                    "#fbbf24",
                );
                if (typeof Swal !== "undefined") {
                    Swal.fire({
                        title: "Perhatian",
                        text: "Pembaruan selesai dengan beberapa catatan.",
                        icon: "warning",
                        confirmButtonColor: "#f59e0b",
                        ...getThemeColors(),
                    });
                }
            }
        } catch (err) {
            appendLog("Error eksekusi update: " + err.message, "#f87171");
            if (typeof Swal !== "undefined") {
                Swal.fire({
                    title: "Gagal!",
                    text: "Terjadi kesalahan: " + err.message,
                    icon: "error",
                    confirmButtonColor: "#ef4444",
                    ...getThemeColors(),
                });
            }
        } finally {
            btnRun.disabled = false;
            btnRun.innerHTML =
                '<i class="fas fa-download me-1"></i> Pasang Sekarang';
        }
    });
});
