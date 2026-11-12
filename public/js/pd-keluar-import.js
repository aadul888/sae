/**
 * Import Excel Peserta Didik Keluar & Perbarui Kelengkapan Alumni (Format Resmi Dapodik).
 * Dipakai bersama oleh halaman Peserta Didik Tidak Aktif & tab Alumni.
 */

window.openPdKeluarImportModal = function () {
    const modal = document.getElementById("modalImportExcel");
    if (!modal) return;

    ["formImportExcelKeluar", "formImportExcel", "formUpdateKelengkapan"].forEach(function (id) {
        const f = document.getElementById(id);
        if (f) {
            f.reset();
            const btn = f.querySelector('button[type="submit"]');
            if (btn) btn.disabled = false;
        }
    });

    modal.querySelectorAll(".import-excel-progress").forEach(function (p) {
        p.style.display = "none";
    });

    modal.style.display = "flex";
    document.body.style.overflow = "hidden";
};

window.closePdKeluarImportModal = function () {
    const modal = document.getElementById("modalImportExcel");
    if (!modal) return;
    modal.style.display = "none";
    document.body.style.overflow = "";
};

// Aliases agar pemanggil lama tetap kompatibel
window.openImportExcelModal = window.openPdKeluarImportModal;
window.closeImportExcelModal = window.closePdKeluarImportModal;

window.handlePdKeluarImportSubmit = async function (e) {
    e.preventDefault();
    const form = e.target;
    const fileInput = form.querySelector('input[type="file"]');

    if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
        const isUpdateForm = form.id === "formUpdateKelengkapan";
        const namaBerkas = isUpdateForm
            ? "Berkas Daftar Peserta Didik Lengkap"
            : "Berkas Peserta Didik Keluar";

        if (typeof Swal !== "undefined") {
            Swal.fire({
                title: "Berkas Belum Dipilih",
                text: "Silakan pilih " + namaBerkas + " terlebih dahulu.",
                icon: "warning",
                confirmButtonColor: "#6366f1",
            });
        } else if (window.SAE && typeof window.SAE.alert === "function") {
            window.SAE.alert("Silakan pilih " + namaBerkas + " terlebih dahulu.", "Informasi", "info");
        } else {
            alert("Silakan pilih " + namaBerkas + " terlebih dahulu.");
        }
        return;
    }

    const modeInput = form.querySelector('[name="mode"]');
    const isUpdate = !!(modeInput && modeInput.value === "update");

    const progress = form.querySelector(".import-excel-progress");
    const btnSubmit = form.querySelector('button[type="submit"]');
    const btnIcon = btnSubmit ? btnSubmit.querySelector("i") : null;
    const iconAwal = btnIcon ? btnIcon.className : "";

    if (progress) progress.style.display = "block";
    if (btnSubmit) btnSubmit.disabled = true;
    if (btnIcon) btnIcon.className = "fas fa-spinner fa-spin";

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
            window.closePdKeluarImportModal();

            const escapeText = function (str) {
                const d = document.createElement("div");
                d.textContent = str || "";
                return d.innerHTML;
            };

            const detailHtml = escapeText(json.message);

            if (typeof Swal !== "undefined") {
                await Swal.fire({
                    title: isUpdate ? "Kelengkapan Diperbarui!" : "Import Berhasil!",
                    html: detailHtml,
                    icon: "success",
                    confirmButtonColor: "#10b981",
                });
            } else if (window.SAE && typeof window.SAE.alert === "function") {
                window.SAE.alert(json.message, "Informasi", "info");
            } else {
                alert(json.message);
            }

            const targetUrl = window.location.pathname + window.location.search;
            if (typeof window.refreshLiveTable === "function" && document.getElementById("tableDataContainer")) {
                window.refreshLiveTable(targetUrl);
            } else {
                window.location.href = targetUrl;
            }
        } else {
            let errorMsg = json.message || "Gagal memproses berkas Excel.";
            const escapeText = function (str) {
                const d = document.createElement("div");
                d.textContent = str || "";
                return d.innerHTML;
            };
            let errorHtml = escapeText(errorMsg);
            if (json.errors && Array.isArray(json.errors) && json.errors.length > 0) {
                errorHtml +=
                    "<br><br><small style='color: #ef4444; text-align: left; display: block; max-height: 140px; overflow-y: auto;'>" +
                    json.errors.map((err) => escapeText(err)).join("<br>") +
                    "</small>";
            } else if (json.errors && typeof json.errors === "object") {
                const flat = Object.values(json.errors).flat().join("<br>");
                errorHtml +=
                    "<br><br><small style='color: #ef4444; text-align: left; display: block;'>" +
                    escapeText(flat) +
                    "</small>";
            }

            if (typeof Swal !== "undefined") {
                Swal.fire({
                    title: isUpdate ? "Gagal Memperbarui" : "Gagal Mengimpor",
                    html: errorHtml,
                    icon: "error",
                    confirmButtonColor: "#ef4444",
                });
            } else if (window.SAE && typeof window.SAE.alert === "function") {
                window.SAE.alert(errorMsg, "Error", "error");
            } else {
                alert(errorMsg);
            }

            if (btnSubmit) btnSubmit.disabled = false;
            if (btnIcon && iconAwal) btnIcon.className = iconAwal;
            if (progress) progress.style.display = "none";
        }
    } catch (err) {
        if (progress) progress.style.display = "none";
        if (btnSubmit) btnSubmit.disabled = false;
        if (btnIcon && iconAwal) btnIcon.className = iconAwal;

        const msg = err && err.message ? err.message : "Terjadi kesalahan jaringan.";
        if (typeof Swal !== "undefined") {
            Swal.fire({
                title: "Terjadi Kesalahan",
                text: msg,
                icon: "error",
                confirmButtonColor: "#ef4444",
            });
        } else if (window.SAE && typeof window.SAE.alert === "function") {
            window.SAE.alert(msg, "Error", "error");
        } else {
            alert(msg);
        }
    }
};

// Aliaskan juga handleImportExcelSubmit ke handlePdKeluarImportSubmit
window.handleImportExcelSubmit = window.handlePdKeluarImportSubmit;
