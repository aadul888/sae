/**
 * Import Excel Peserta Didik Keluar / Alumni (Format Resmi Dapodik 43 kolom).
 * Dipakai bersama oleh halaman Peserta Didik Tidak Aktif & tab Alumni.
 */

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
    const btnIcon = document.getElementById("btnSubmitImportExcelIcon");

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

            // Pertahankan query aktif (tab/filter) saat memuat ulang.
            const targetUrl = window.location.pathname + window.location.search;
            if (typeof window.refreshLiveTable === "function" && document.getElementById("tableDataContainer")) {
                window.refreshLiveTable(targetUrl);
            } else {
                window.location.href = targetUrl;
            }
        } else {
            let errorMsg = json.message || "Gagal mengimpor berkas Excel.";
            let errorHtml = escapeHtml(errorMsg);
            if (json.errors && Array.isArray(json.errors) && json.errors.length > 0) {
                errorHtml +=
                    "<br><br><small style='color: #ef4444; text-align: left; display: block; max-height: 140px; overflow-y: auto;'>" +
                    json.errors.map((err) => escapeHtml(err)).join("<br>") +
                    "</small>";
            } else if (json.errors && typeof json.errors === "object") {
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
        if (btnIcon) btnIcon.className = "fas fa-cloud-arrow-up";
    }
};

document.addEventListener("click", function (e) {
    if (e.target && e.target.id === "modalImportExcel") {
        window.closeImportExcelModal();
    }
});
