// =========================================================================
// AKSI 1: RESET PASSWORD PESERTA DIDIK KE DEFAULT (NISN)
// =========================================================================
document.addEventListener("click", async function (e) {
    const btnReset = e.target.closest(".btn-reset-password");
    if (!btnReset) return;

    const pdId = btnReset.dataset.id;
    const pdNama = btnReset.dataset.nama || "Peserta Didik";
    const pdNisn = btnReset.dataset.nisn || "NISN";

    const result = await Swal.fire({
        title: "Reset Password Akun?",
        html:
            `Apakah Anda yakin ingin mereset password akun untuk <b>${pdNama}</b>?<br><br>` +
            `<div style="font-size: 0.85rem; padding: 10px 14px; background: rgba(245,158,11,0.1); border: 1px dashed rgba(245,158,11,0.4); border-radius: 8px; text-align: left; color: var(--text-color); line-height: 1.5;">` +
            `<i class="fas fa-info-circle text-warning me-1"></i> Password akan dikembalikan ke default: <b>${pdNisn}</b>.<br>` +
            `Peserta didik akan diminta memperbarui password saat login pertama kali.</div>`,
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#f59e0b",
        cancelButtonColor: "#64748b",
        confirmButtonText: '<i class="fas fa-key me-1"></i> Ya, Reset Password',
        cancelButtonText: "Batal",
        reverseButtons: true,
    });

    if (!result.isConfirmed) return;

    Swal.fire({
        title: "Memproses Reset...",
        text: "Menyimpan pengaturan password default.",
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading(),
    });

    try {
        const csrfToken =
            document.querySelector('meta[name="csrf-token"]')?.content || "";
        const res = await fetch(
            "/dashboard/manajemen-data/peserta-didik-aktif/reset-password",
            {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    Accept: "application/json",
                    "X-CSRF-TOKEN": csrfToken,
                },
                body: JSON.stringify({ peserta_didik_id: pdId }),
            },
        );

        const data = await res.json();

        if (res.ok && data.status === "success") {
            Swal.fire({
                title: "Password Berhasil Direset!",
                html: data.message,
                icon: "success",
                confirmButtonColor: "#10b981",
            });
        } else {
            Swal.fire({
                title: "Gagal Mereset",
                text:
                    data.message ||
                    "Terjadi kesalahan sistem saat mereset password.",
                icon: "error",
                confirmButtonColor: "#ef4444",
            });
        }
    } catch (err) {
        Swal.fire({
            title: "Kesalahan Jaringan",
            text: "Gagal terhubung ke server. Silakan coba kembali.",
            icon: "error",
            confirmButtonColor: "#ef4444",
        });
    }
});

// =========================================================================
// AKSI 2: TUNJUK / CABUT STATUS KOORDINATOR KELAS (ASISTEN WALI KELAS)
// =========================================================================
document.addEventListener("click", async function (e) {
    const btnKoord = e.target.closest(".btn-toggle-koordinator");
    if (!btnKoord) return;

    const pdId = btnKoord.dataset.id;
    const pdNama = btnKoord.dataset.nama || "Peserta Didik";
    const pdRombel = btnKoord.dataset.rombel || "Kelas";
    const isCurrent = btnKoord.dataset.status === "1";

    const confirmBtnColor = isCurrent ? "#ef4444" : "#6366f1";
    const confirmBtnIcon = isCurrent ? "fa-user-slash" : "fa-crown";

    const result = await Swal.fire({
        title: isCurrent
            ? "Cabut Status Koordinator?"
            : "Tunjuk Koordinator Kelas?",
        html: isCurrent
            ? `Apakah Anda yakin ingin mencabut wewenang Koordinator Kelas untuk <b>${pdNama}</b> (${pdRombel})?<br><br>` +
              `<small style="color: var(--text-muted);">Akses bantuan Wali Kelas (presensi kelas & daftar peserta didik kelas) akan dinonaktifkan untuk akun ini.</small>`
            : `Tunjuk <b>${pdNama}</b> (${pdRombel}) sebagai Koordinator Kelas?<br><br>` +
              `<div style="font-size: 0.85rem; padding: 10px 14px; background: rgba(99,102,241,0.08); border: 1px dashed rgba(99,102,241,0.3); border-radius: 8px; text-align: left; color: var(--text-color); line-height: 1.5;">` +
              `<i class="fas fa-crown text-warning me-1"></i> Peserta didik akan memiliki akses setara asisten Wali Kelas:<br>` +
              `• Mencatat presensi harian / KBM kelas binaannya.<br>` +
              `• Membantu tugas wali kelas dalam memantau kehadiran rombel.</div>`,
        icon: isCurrent ? "warning" : "question",
        showCancelButton: true,
        confirmButtonColor: confirmBtnColor,
        cancelButtonColor: "#64748b",
        confirmButtonText: `<i class="fas ${confirmBtnIcon} me-1"></i> Ya, ${isCurrent ? "Cabut" : "Tunjuk"}`,
        cancelButtonText: "Batal",
        reverseButtons: true,
    });

    if (!result.isConfirmed) return;

    Swal.fire({
        title: "Memproses...",
        text: "Memperbarui wewenang Koordinator Kelas.",
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading(),
    });

    try {
        const csrfToken =
            document.querySelector('meta[name="csrf-token"]')?.content || "";
        const res = await fetch(
            "/dashboard/manajemen-data/peserta-didik-aktif/toggle-koordinator",
            {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    Accept: "application/json",
                    "X-CSRF-TOKEN": csrfToken,
                },
                body: JSON.stringify({ peserta_didik_id: pdId }),
            },
        );

        const data = await res.json();

        if (res.ok && data.status === "success") {
            const newStatus = !!data.is_koordinator;

            // Update status tombol & UI badge
            btnKoord.dataset.status = newStatus ? "1" : "0";
            btnKoord.title = newStatus
                ? "Cabut Wewenang Koordinator Kelas"
                : "Tunjuk sebagai Koordinator Kelas (Asisten Wali Kelas)";

            const icon = btnKoord.querySelector("i");
            if (icon) {
                if (newStatus) {
                    icon.style.color = "#f59e0b";
                    icon.style.opacity = "1";
                    btnKoord.classList.add("is-active");
                } else {
                    icon.style.color = "";
                    icon.style.opacity = "0.55";
                    btnKoord.classList.remove("is-active");
                }
            }

            const badge = document.getElementById("badgeKoordinator_" + pdId);
            if (badge) {
                badge.style.display = newStatus ? "inline-flex" : "none";
            }

            Swal.fire({
                title: newStatus ? "Koordinator Ditunjuk!" : "Wewenang Dicabut",
                html: data.message,
                icon: "success",
                confirmButtonColor: "#10b981",
            });
        } else {
            Swal.fire({
                title: "Gagal Memperbarui",
                text:
                    data.message ||
                    "Terjadi kesalahan sistem saat memperbarui status koordinator.",
                icon: "error",
                confirmButtonColor: "#ef4444",
            });
        }
    } catch (err) {
        Swal.fire({
            title: "Kesalahan Jaringan",
            text: "Gagal terhubung ke server. Silakan coba kembali.",
            icon: "error",
            confirmButtonColor: "#ef4444",
        });
    }
});

// =========================================================================
// MODAL CETAK DATA ROMBEL
// =========================================================================
window.openCetakDataRombelModal = function (defaultRombel = "") {
    const modal = document.getElementById("cetakDataRombelModal");
    if (!modal) return;
    const select = document.getElementById("selectCetakDataRombel");
    if (select) {
        const currentFilter =
            document.getElementById("filterRombel")?.value || defaultRombel;
        if (currentFilter) {
            select.value = currentFilter;
        }
    }
    modal.style.display = "flex";
    document.body.style.overflow = "hidden";
};

window.closeCetakDataRombelModal = function () {
    const modal = document.getElementById("cetakDataRombelModal");
    if (!modal) return;
    modal.style.display = "none";
    document.body.style.overflow = "";
};

window.handleCetakDataRombelBackdropClick = function (e) {
    if (e.target.id === "cetakDataRombelModal") {
        window.closeCetakDataRombelModal();
    }
};

window.proceedCetakDataRombel = function () {
    const select = document.getElementById("selectCetakDataRombel");
    if (!select || !select.value) {
        window.SAE.alert("Silakan pilih rombongan belajar (kelas) terlebih dahulu.", 'Informasi', 'info');
        return;
    }
    const rombelName = select.value;
    const orientasi =
        document.getElementById("selectOrientasiCetakData")?.value ||
        "landscape";
    const url =
        "/dashboard/manajemen-data/peserta-didik-aktif/cetak-rombel/" +
        encodeURIComponent(rombelName) +
        "?orientasi=" +
        encodeURIComponent(orientasi);
    window.open(url, "_blank");
    window.closeCetakDataRombelModal();
};

// =========================================================================
// MODAL IMPORT EXCEL PESERTA DIDIK AKTIF (SEMENTARA)
// =========================================================================
window.openImportExcelModal = function () {
    const modal = document.getElementById("modalImportExcel");
    if (!modal) return;
    const form = document.getElementById("formImportExcel");
    if (form) form.reset();
    const progress = document.getElementById("importExcelProgress");
    if (progress) progress.style.display = "none";
    const btnSubmit = document.getElementById("btnSubmitImportExcel");
    if (btnSubmit) btnSubmit.disabled = false;
    modal.style.display = "flex";
    document.body.style.overflow = "hidden";
};

window.closeImportExcelModal = function () {
    const modal = document.getElementById("modalImportExcel");
    if (!modal) return;
    modal.style.display = "none";
    document.body.style.overflow = "";
};

window.handleImportExcelSubmit = async function (e) {
    e.preventDefault();
    const form = e.target;
    const fileInput = document.getElementById("inputExcelFile");
    if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
        if (typeof Swal !== "undefined") {
            Swal.fire({
                title: "Berkas Belum Dipilih",
                text: "Silakan pilih berkas Excel atau CSV terlebih dahulu.",
                icon: "warning",
                confirmButtonColor: "#6366f1",
            });
        } else {
            window.SAE.alert("Silakan pilih berkas Excel atau CSV terlebih dahulu.", 'Informasi', 'info');
        }
        return;
    }

    const progress = document.getElementById("importExcelProgress");
    const btnSubmit = document.getElementById("btnSubmitImportExcel");
    const btnText = document.getElementById("btnSubmitImportExcelText");

    if (progress) progress.style.display = "block";
    if (btnSubmit) btnSubmit.disabled = true;
    if (btnText) btnText.textContent = "Mengimpor Data...";

    const formData = new FormData(form);

    try {
        const res = await fetch(form.action, {
            method: "POST",
            headers: {
                Accept: "application/json",
                "X-Requested-With": "XMLHttpRequest",
            },
            body: formData,
        });

        const json = await res.json();

        if (res.ok && json.status === "success") {
            window.closeImportExcelModal();

            let detailHtml = escapeHtml(json.message);
            if (json.errors && json.errors.length > 0) {
                detailHtml +=
                    "<br><br><small style='color: #ef4444; text-align: left; display: block; max-height: 120px; overflow-y: auto;'>" +
                    json.errors.map((err) => escapeHtml(err)).join("<br>") +
                    "</small>";
            }

            if (typeof Swal !== "undefined") {
                await Swal.fire({
                    title: "Import Berhasil!",
                    html: detailHtml,
                    icon: "success",
                    confirmButtonColor: "#10b981",
                });
            } else {
                window.SAE.alert(json.message, 'Informasi', 'info');
            }

            // Bersihkan filter pencarian agar data yang baru diimpor langsung terlihat
            const targetUrl = new URL(window.location.origin + window.location.pathname);
            if (typeof window.refreshLiveTable === "function") {
                window.refreshLiveTable(targetUrl.toString());
            } else {
                window.location.href = targetUrl.toString();
            }
        } else {
            let errorMsg = json.message || "Gagal mengimpor berkas Excel.";
            let errorHtml = escapeHtml(errorMsg);
            if (json.errors && Array.isArray(json.errors) && json.errors.length > 0) {
                errorHtml +=
                    "<br><br><small style='color: #ef4444; text-align: left; display: block; max-height: 140px; overflow-y: auto;'>" +
                    json.errors.map((err) => escapeHtml(err)).join("<br>") +
                    "</small>";
            } else if (
                json.errors &&
                typeof json.errors === "object"
            ) {
                const flat = Object.values(json.errors).flat().join("<br>");
                errorHtml +=
                    "<br><br><small style='color: #ef4444; text-align: left; display: block;'>" +
                    escapeHtml(flat) +
                    "</small>";
            }

            if (typeof Swal !== "undefined") {
                Swal.fire({
                    title: "Gagal Mengimpor",
                    html: errorHtml,
                    icon: "error",
                    confirmButtonColor: "#ef4444",
                });
            } else {
                window.SAE.alert(errorMsg, 'Informasi', 'info');
            }
        }
    } catch (err) {
        if (typeof Swal !== "undefined") {
            Swal.fire({
                title: "Kesalahan Jaringan",
                text:
                    "Gagal terhubung ke server: " +
                    (err.message || "Terjadi kesalahan."),
                icon: "error",
                confirmButtonColor: "#ef4444",
            });
        } else {
            window.SAE.alert("Kesalahan jaringan: " + err.message, 'Informasi', 'info');
        }
    } finally {
        if (progress) progress.style.display = "none";
        if (btnSubmit) btnSubmit.disabled = false;
        if (btnText) btnText.textContent = "Import Sekarang";
    }
};

document.addEventListener("click", function (e) {
    if (e.target && e.target.id === "modalImportExcel") {
        window.closeImportExcelModal();
    }
});

