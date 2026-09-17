/**
 * Modul Riwayat Presensi Peserta Didik (SAE)
 * Menangani modal foto snapshot dan pengajuan formulir izin/sakit
 */
document.addEventListener("DOMContentLoaded", function () {
    // 1. Modal View Snapshot Kamera
    const modalSnapshot = document.getElementById("modalSnapshotSaya");
    const imgSnapshot = document.getElementById("imgSnapshotSaya");
    const captionSnapshot = document.getElementById("snapshotSayaCaption");
    const btnCloseSnapshot = document.getElementById("btnCloseSnapshotSaya");

    document.querySelectorAll(".btn-view-snapshot").forEach((btn) => {
        btn.addEventListener("click", function () {
            const url = this.getAttribute("data-url");
            const caption = this.getAttribute("data-caption");
            if (imgSnapshot) imgSnapshot.src = url;
            if (captionSnapshot) captionSnapshot.textContent = caption || "Foto Bukti Presensi";
            if (modalSnapshot) modalSnapshot.style.display = "flex";
        });
    });

    if (btnCloseSnapshot && modalSnapshot) {
        btnCloseSnapshot.addEventListener("click", function () {
            modalSnapshot.style.display = "none";
        });
    }

    if (modalSnapshot) {
        modalSnapshot.addEventListener("click", function (e) {
            if (e.target === modalSnapshot) {
                modalSnapshot.style.display = "none";
            }
        });
    }

    // 2. Modal Pengajuan Izin / Sakit
    const modalIzin = document.getElementById("modalPengajuanIzin");
    const btnBukaModalIzin = document.getElementById("btnBukaModalIzin");
    const btnCloseModalIzin = document.getElementById("btnCloseModalIzin");
    const btnCancelModalIzin = document.getElementById("btnCancelModalIzin");
    const formPengajuanIzin = document.getElementById("formPengajuanIzin");

    const tutupModalIzin = () => {
        if (modalIzin) modalIzin.style.display = "none";
        if (formPengajuanIzin) formPengajuanIzin.reset();
    };

    if (btnBukaModalIzin && modalIzin) {
        btnBukaModalIzin.addEventListener("click", function () {
            modalIzin.style.display = "flex";
        });
    }

    if (btnCloseModalIzin) btnCloseModalIzin.addEventListener("click", tutupModalIzin);
    if (btnCancelModalIzin) btnCancelModalIzin.addEventListener("click", tutupModalIzin);

    if (modalIzin) {
        modalIzin.addEventListener("click", function (e) {
            if (e.target === modalIzin) {
                tutupModalIzin();
            }
        });
    }

    // 3. Submit Form Izin via AJAX & SweetAlert2
    if (formPengajuanIzin) {
        formPengajuanIzin.addEventListener("submit", async function (e) {
            e.preventDefault();

            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn ? submitBtn.innerHTML : "";

            const formData = new FormData(this);
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") || "";

            try {
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Mengirim...';
                }

                const res = await fetch("/dashboard/presensi/saya/izin", {
                    method: "POST",
                    headers: {
                        "X-CSRF-TOKEN": csrfToken,
                        Accept: "application/json",
                    },
                    body: formData,
                });

                const data = await res.json();

                if (res.ok && data.status === "success") {
                    tutupModalIzin();
                    if (typeof Swal !== "undefined") {
                        Swal.fire({
                            icon: "success",
                            title: "Berhasil Diajukan!",
                            text: data.message || "Permohonan surat izin Anda berhasil dikirim untuk diverifikasi.",
                            confirmButtonColor: "#6366f1",
                        }).then(() => {
                            window.location.reload();
                        });
                    } else {
                        alert(data.message || "Permohonan berhasil dikirim.");
                        window.location.reload();
                    }
                } else {
                    const errMsg = data.message || (data.errors ? Object.values(data.errors).flat().join("\n") : "Gagal mengajukan izin.");
                    if (typeof Swal !== "undefined") {
                        Swal.fire({
                            icon: "error",
                            title: "Pengajuan Gagal",
                            text: errMsg,
                            confirmButtonColor: "#ef4444",
                        });
                    } else {
                        alert(errMsg);
                    }
                }
            } catch (err) {
                if (typeof Swal !== "undefined") {
                    Swal.fire({
                        icon: "error",
                        title: "Terjadi Kesalahan",
                        text: "Gagal terhubung ke server. Silakan periksa koneksi internet Anda.",
                        confirmButtonColor: "#ef4444",
                    });
                } else {
                    alert("Gagal terhubung ke server.");
                }
            } finally {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                }
            }
        });
    }
});
