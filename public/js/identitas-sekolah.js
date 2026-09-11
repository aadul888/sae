/**
 * Sistem Aplikasi Edukasi (SAE)
 * Script Modul Identitas Sekolah — Manajemen Data, Logo & Kop Surat Resmi
 */

let currentSekolahLogoUrl = "";
let currentSekolahKopUrl = "";

// Modal Edit Identitas Sekolah
window.openEditSekolahModal = function () {
    const modal = document.getElementById("modalEditSekolah");
    if (modal) modal.style.display = "flex";
};

window.closeEditSekolahModal = function () {
    const modal = document.getElementById("modalEditSekolah");
    if (modal) modal.style.display = "none";
};

function formatBytes(bytes, decimals = 1) {
    if (!bytes || bytes === 0) return "0 B";
    const k = 1024;
    const dm = decimals < 0 ? 0 : decimals;
    const sizes = ["B", "KB", "MB", "GB"];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + " " + sizes[i];
}

/* =========================================================
   1. LOGO RESMI SATUAN PENDIDIKAN
   ========================================================= */

function handleSekolahFilePreview(file) {
    if (!file) return;

    // Validasi ekstensi dan tipe file khusus PNG
    const ext = file.name.split(".").pop().toLowerCase();
    if (ext !== "png" || (file.type && file.type !== "image/png" && file.type !== "image/x-png")) {
        if (window.SAE && typeof window.SAE.toast === "function") {
            window.SAE.toast("Logo sekolah wajib berformat PNG (.png) untuk kebutuhan kartu pelajar dan dokumen resmi.", "warning");
        } else {
            alert("Logo sekolah wajib berformat PNG (.png).");
        }
        const fileInput = document.getElementById("cardLogoFileInput");
        if (fileInput) fileInput.value = "";
        return;
    }

    if (file.size > 5 * 1024 * 1024) {
        if (window.SAE && typeof window.SAE.toast === "function") {
            window.SAE.toast("Ukuran file logo sekolah maksimal 5 MB sebelum dikompresi.", "danger");
        } else {
            alert("Ukuran file maksimal 5 MB.");
        }
        const fileInput = document.getElementById("cardLogoFileInput");
        if (fileInput) fileInput.value = "";
        return;
    }

    const previewImg = document.getElementById("cardLogoPreviewImg");
    const placeholder = document.getElementById("cardLogoPlaceholder");
    const specsEl = document.getElementById("cardLogoFileSpecs");

    const reader = new FileReader();
    reader.onload = function (e) {
        const dataUrl = e.target.result;
        const tempImg = new Image();
        tempImg.onload = function () {
            if (previewImg) {
                previewImg.src = dataUrl;
                previewImg.style.display = "block";
            }
            if (placeholder) placeholder.style.display = "none";

            if (specsEl) {
                specsEl.style.display = "block";
                specsEl.innerHTML = `
                    <span style="color: #10b981; font-weight: 600;">
                        <i class="fas fa-file-circle-check me-1"></i> PNG Siap: ${formatBytes(file.size)} (${tempImg.width} &times; ${tempImg.height} px)
                    </span>
                    <div style="font-size: 0.68rem; color: var(--text-muted); margin-top: 1px;">
                        Otomatis dikompresi lossless &amp; disimpan persisten
                    </div>`;
            }
        };
        tempImg.src = dataUrl;
    };
    reader.readAsDataURL(file);
}

window.handleUploadSekolahLogo = async function () {
    const fileInput = document.getElementById("cardLogoFileInput");
    const btnSubmit = document.getElementById("btnCardSaveLogo");

    if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
        if (currentSekolahLogoUrl) {
            if (window.SAE && typeof window.SAE.toast === "function") {
                window.SAE.toast("Logo saat ini sudah tersimpan. Pilih berkas PNG baru jika ingin mengganti logo.", "info");
            }
            return;
        }
        if (window.SAE && typeof window.SAE.toast === "function") {
            window.SAE.toast("Silakan pilih atau seret file logo PNG terlebih dahulu.", "warning");
        }
        return;
    }

    const file = fileInput.files[0];
    const formData = new FormData();
    formData.append("logo", file);

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ||
                      document.querySelector('input[name="_token"]')?.value || "";

    if (btnSubmit) {
        btnSubmit.disabled = true;
        btnSubmit.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Menyimpan...';
    }

    try {
        const res = await fetch("/dashboard/identitas-sekolah/upload-logo", {
            method: "POST",
            headers: {
                "X-CSRF-TOKEN": csrfToken,
                "Accept": "application/json",
            },
            body: formData,
        });

        const json = await res.json();

        if (json.status === "success") {
            currentSekolahLogoUrl = json.logo_url;
            updateSekolahLogoUI(json.logo_url, json.logo_size);

            if (fileInput) fileInput.value = "";

            if (window.SAE && typeof window.SAE.toast === "function") {
                const savings = json.savings_percent ? ` (Hemat ${json.savings_percent}%)` : "";
                window.SAE.toast(`Logo sekolah berhasil disimpan! ${json.dimensions} • ${json.logo_size}${savings}`, "success");
            }
        } else {
            throw new Error(json.message || "Gagal mengunggah logo sekolah.");
        }
    } catch (err) {
        if (window.SAE && typeof window.SAE.toast === "function") {
            window.SAE.toast(err.message || "Terjadi kesalahan saat mengunggah logo sekolah.", "danger");
        }
    } finally {
        if (btnSubmit) {
            btnSubmit.disabled = false;
            btnSubmit.innerHTML = '<i class="fas fa-cloud-arrow-up me-1"></i> Simpan Logo';
        }
    }
};

window.handleDeleteSekolahLogo = async function () {
    const confirmed = await window.SAE.confirm(
        "File logo sekolah ini akan dihapus dari penyimpanan sistem.",
        "Hapus Logo Sekolah?",
        "danger",
        "Ya, Hapus Logo",
        "Batal"
    );

    if (!confirmed) return;

    const btnDelete = document.getElementById("btnCardDeleteLogo");
    if (btnDelete) {
        btnDelete.disabled = true;
        btnDelete.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Menghapus...';
    }

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ||
                      document.querySelector('input[name="_token"]')?.value || "";

    try {
        const res = await fetch("/dashboard/identitas-sekolah/delete-logo", {
            method: "DELETE",
            headers: {
                "X-CSRF-TOKEN": csrfToken,
                "Accept": "application/json",
            },
        });

        const json = await res.json();

        if (json.status === "success") {
            currentSekolahLogoUrl = "";
            updateSekolahLogoUI("", "");

            const fileInput = document.getElementById("cardLogoFileInput");
            if (fileInput) fileInput.value = "";

            if (window.SAE && typeof window.SAE.toast === "function") {
                window.SAE.toast("Logo sekolah berhasil dihapus.", "info");
            }
        } else {
            throw new Error(json.message || "Gagal menghapus logo sekolah.");
        }
    } catch (err) {
        if (window.SAE && typeof window.SAE.toast === "function") {
            window.SAE.toast(err.message || "Terjadi kesalahan saat menghapus logo.", "danger");
        }
    } finally {
        if (btnDelete) {
            btnDelete.disabled = false;
            btnDelete.innerHTML = '<i class="fas fa-trash-can me-1"></i> Hapus';
        }
    }
};

function updateSekolahLogoUI(logoUrl, logoSize) {
    // 1. Banner Thumbnail
    const bannerImg = document.getElementById("bannerLogoImg");
    const bannerPlaceholder = document.getElementById("bannerLogoPlaceholder");

    if (logoUrl) {
        if (bannerImg) {
            bannerImg.src = logoUrl + "?v=" + Date.now();
            bannerImg.style.display = "block";
        }
        if (bannerPlaceholder) bannerPlaceholder.style.display = "none";
    } else {
        if (bannerImg) {
            bannerImg.src = "";
            bannerImg.style.display = "none";
        }
        if (bannerPlaceholder) bannerPlaceholder.style.display = "flex";
    }

    // 2. Card Logo
    const cardImg = document.getElementById("cardLogoPreviewImg");
    const cardPlaceholder = document.getElementById("cardLogoPlaceholder");
    const cardBadge = document.getElementById("cardLogoBadge");
    const cardSpecs = document.getElementById("cardLogoFileSpecs");
    const btnDelete = document.getElementById("btnCardDeleteLogo");

    if (logoUrl) {
        if (cardImg) {
            cardImg.src = logoUrl + "?v=" + Date.now();
            cardImg.style.display = "block";
        }
        if (cardPlaceholder) cardPlaceholder.style.display = "none";
        if (cardBadge) {
            cardBadge.className = "badge badge-success";
            cardBadge.textContent = "Tersimpan (" + (logoSize || "PNG") + ")";
        }
        if (cardSpecs) {
            cardSpecs.innerHTML = `<span style="color: #10b981;"><i class="fas fa-circle-check me-1"></i> PNG Aktif: ${logoSize || "PNG"}</span>`;
        }
        if (btnDelete) btnDelete.style.display = "inline-flex";
    } else {
        if (cardImg) {
            cardImg.src = "";
            cardImg.style.display = "none";
        }
        if (cardPlaceholder) cardPlaceholder.style.display = "flex";
        if (cardBadge) {
            cardBadge.className = "badge badge-outline";
            cardBadge.textContent = "Belum Ada Logo";
        }
        if (cardSpecs) cardSpecs.textContent = "";
        if (btnDelete) btnDelete.style.display = "none";
    }
}

/* =========================================================
   2. KOP SURAT RESMI (LETTERHEAD)
   ========================================================= */

function handleSekolahKopPreview(file) {
    if (!file) return;

    const ext = file.name.split(".").pop().toLowerCase();
    if (ext !== "png" || (file.type && file.type !== "image/png" && file.type !== "image/x-png")) {
        if (window.SAE && typeof window.SAE.toast === "function") {
            window.SAE.toast("Kop surat sekolah wajib berformat PNG (.png) untuk kebutuhan surat resmi, rapor, dan cetak.", "warning");
        } else {
            alert("Kop surat wajib berformat PNG (.png).");
        }
        const fileInput = document.getElementById("cardKopFileInput");
        if (fileInput) fileInput.value = "";
        return;
    }

    if (file.size > 6 * 1024 * 1024) {
        if (window.SAE && typeof window.SAE.toast === "function") {
            window.SAE.toast("Ukuran berkas kop surat maksimal 6 MB sebelum dikompresi.", "danger");
        } else {
            alert("Ukuran berkas kop surat maksimal 6 MB.");
        }
        const fileInput = document.getElementById("cardKopFileInput");
        if (fileInput) fileInput.value = "";
        return;
    }

    const previewImg = document.getElementById("cardKopPreviewImg");
    const placeholder = document.getElementById("cardKopPlaceholder");
    const specsEl = document.getElementById("cardKopFileSpecs");

    const reader = new FileReader();
    reader.onload = function (e) {
        const dataUrl = e.target.result;
        const tempImg = new Image();
        tempImg.onload = function () {
            if (previewImg) {
                previewImg.src = dataUrl;
                previewImg.style.display = "block";
            }
            if (placeholder) placeholder.style.display = "none";

            if (specsEl) {
                specsEl.style.display = "block";
                specsEl.innerHTML = `
                    <span style="color: #10b981; font-weight: 600;">
                        <i class="fas fa-file-circle-check me-1"></i> PNG Siap: ${formatBytes(file.size)} (${tempImg.width} &times; ${tempImg.height} px)
                    </span>
                    <div style="font-size: 0.68rem; color: var(--text-muted); margin-top: 1px;">
                        Otomatis dikompresi lossless &amp; disimpan persisten
                    </div>`;
            }
        };
        tempImg.src = dataUrl;
    };
    reader.readAsDataURL(file);
}

window.handleUploadSekolahKop = async function () {
    const fileInput = document.getElementById("cardKopFileInput");
    const btnSubmit = document.getElementById("btnCardSaveKop");

    if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
        if (currentSekolahKopUrl) {
            if (window.SAE && typeof window.SAE.toast === "function") {
                window.SAE.toast("Kop surat saat ini sudah tersimpan. Pilih berkas PNG baru jika ingin mengganti kop surat.", "info");
            }
            return;
        }
        if (window.SAE && typeof window.SAE.toast === "function") {
            window.SAE.toast("Silakan pilih atau seret berkas kop surat PNG terlebih dahulu.", "warning");
        }
        return;
    }

    const file = fileInput.files[0];
    const formData = new FormData();
    formData.append("kop", file);

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ||
                      document.querySelector('input[name="_token"]')?.value || "";

    if (btnSubmit) {
        btnSubmit.disabled = true;
        btnSubmit.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Menyimpan...';
    }

    try {
        const res = await fetch("/dashboard/identitas-sekolah/upload-kop", {
            method: "POST",
            headers: {
                "X-CSRF-TOKEN": csrfToken,
                "Accept": "application/json",
            },
            body: formData,
        });

        const json = await res.json();

        if (json.status === "success") {
            currentSekolahKopUrl = json.kop_url;
            updateSekolahKopUI(json.kop_url, json.kop_size);

            if (fileInput) fileInput.value = "";

            if (window.SAE && typeof window.SAE.toast === "function") {
                const savings = json.savings_percent ? ` (Hemat ${json.savings_percent}%)` : "";
                window.SAE.toast(`Kop surat berhasil disimpan! ${json.dimensions} • ${json.kop_size}${savings}`, "success");
            }
        } else {
            throw new Error(json.message || "Gagal mengunggah kop surat sekolah.");
        }
    } catch (err) {
        if (window.SAE && typeof window.SAE.toast === "function") {
            window.SAE.toast(err.message || "Terjadi kesalahan saat mengunggah kop sekolah.", "danger");
        }
    } finally {
        if (btnSubmit) {
            btnSubmit.disabled = false;
            btnSubmit.innerHTML = '<i class="fas fa-cloud-arrow-up me-1"></i> Simpan Kop Surat';
        }
    }
};

window.handleDeleteSekolahKop = async function () {
    const confirmed = await window.SAE.confirm(
        "Berkas kop surat resmi sekolah ini akan dihapus dari penyimpanan sistem.",
        "Hapus Kop Surat Sekolah?",
        "danger",
        "Ya, Hapus Kop Surat",
        "Batal"
    );

    if (!confirmed) return;

    const btnDelete = document.getElementById("btnCardDeleteKop");
    if (btnDelete) {
        btnDelete.disabled = true;
        btnDelete.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Menghapus...';
    }

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ||
                      document.querySelector('input[name="_token"]')?.value || "";

    try {
        const res = await fetch("/dashboard/identitas-sekolah/delete-kop", {
            method: "DELETE",
            headers: {
                "X-CSRF-TOKEN": csrfToken,
                "Accept": "application/json",
            },
        });

        const json = await res.json();

        if (json.status === "success") {
            currentSekolahKopUrl = "";
            updateSekolahKopUI("", "");

            const fileInput = document.getElementById("cardKopFileInput");
            if (fileInput) fileInput.value = "";

            if (window.SAE && typeof window.SAE.toast === "function") {
                window.SAE.toast("Kop surat sekolah berhasil dihapus.", "info");
            }
        } else {
            throw new Error(json.message || "Gagal menghapus kop surat sekolah.");
        }
    } catch (err) {
        if (window.SAE && typeof window.SAE.toast === "function") {
            window.SAE.toast(err.message || "Terjadi kesalahan saat menghapus kop surat.", "danger");
        }
    } finally {
        if (btnDelete) {
            btnDelete.disabled = false;
            btnDelete.innerHTML = '<i class="fas fa-trash-can me-1"></i> Hapus';
        }
    }
};

function updateSekolahKopUI(kopUrl, kopSize) {
    const cardImg = document.getElementById("cardKopPreviewImg");
    const cardPlaceholder = document.getElementById("cardKopPlaceholder");
    const cardBadge = document.getElementById("cardKopBadge");
    const cardSpecs = document.getElementById("cardKopFileSpecs");
    const btnDelete = document.getElementById("btnCardDeleteKop");

    if (kopUrl) {
        if (cardImg) {
            cardImg.src = kopUrl + "?v=" + Date.now();
            cardImg.style.display = "block";
        }
        if (cardPlaceholder) cardPlaceholder.style.display = "none";
        if (cardBadge) {
            cardBadge.className = "badge badge-success";
            cardBadge.textContent = "Tersimpan (" + (kopSize || "PNG") + ")";
        }
        if (cardSpecs) {
            cardSpecs.innerHTML = `<span style="color: #10b981;"><i class="fas fa-circle-check me-1"></i> PNG Aktif: ${kopSize || "PNG"}</span>`;
        }
        if (btnDelete) btnDelete.style.display = "inline-flex";
    } else {
        if (cardImg) {
            cardImg.src = "";
            cardImg.style.display = "none";
        }
        if (cardPlaceholder) cardPlaceholder.style.display = "flex";
        if (cardBadge) {
            cardBadge.className = "badge badge-outline";
            cardBadge.textContent = "Belum Ada Kop";
        }
        if (cardSpecs) cardSpecs.textContent = "";
        if (btnDelete) btnDelete.style.display = "none";
    }
}

/* =========================================================
   3. INISIALISASI EVENT LISTENERS (DRAG & DROP)
   ========================================================= */

document.addEventListener("DOMContentLoaded", function () {
    // Modal Edit Identitas Sekolah
    const editModal = document.getElementById("modalEditSekolah");
    if (editModal) {
        editModal.addEventListener("click", function (e) {
            if (e.target === editModal) window.closeEditSekolahModal();
        });
    }

    document.addEventListener("keydown", function (e) {
        if (e.key === "Escape") {
            window.closeEditSekolahModal();
        }
    });

    // File input change - Logo
    const logoFileInput = document.getElementById("cardLogoFileInput");
    if (logoFileInput) {
        logoFileInput.addEventListener("change", function (e) {
            if (e.target.files && e.target.files.length > 0) {
                handleSekolahFilePreview(e.target.files[0]);
            }
        });
    }

    // Drag & drop dropzone - Logo
    const logoDropZone = document.getElementById("cardLogoDropZone");
    if (logoDropZone) {
        ["dragenter", "dragover"].forEach((eventName) => {
            logoDropZone.addEventListener(eventName, function (e) {
                e.preventDefault();
                e.stopPropagation();
                logoDropZone.style.borderColor = "#6366f1";
                logoDropZone.style.background = "rgba(99,102,241,0.08)";
            });
        });

        ["dragleave", "drop"].forEach((eventName) => {
            logoDropZone.addEventListener(eventName, function (e) {
                e.preventDefault();
                e.stopPropagation();
                logoDropZone.style.borderColor = "rgba(99,102,241,0.35)";
                logoDropZone.style.background = "rgba(255,255,255,0.015)";
            });
        });

        logoDropZone.addEventListener("drop", function (e) {
            e.preventDefault();
            e.stopPropagation();
            logoDropZone.style.borderColor = "rgba(99,102,241,0.35)";
            logoDropZone.style.background = "rgba(255,255,255,0.015)";

            const files = e.dataTransfer?.files;
            if (files && files.length > 0) {
                try {
                    logoFileInput.files = files;
                } catch (err) {
                    const dt = new DataTransfer();
                    dt.items.add(files[0]);
                    logoFileInput.files = dt.files;
                }
                handleSekolahFilePreview(files[0]);
            }
        });
    }

    // Drag & drop on preview container - Logo
    const logoPreviewContainer = document.getElementById("cardLogoPreviewContainer");
    if (logoPreviewContainer) {
        ["dragenter", "dragover"].forEach((eventName) => {
            logoPreviewContainer.addEventListener(eventName, function (e) {
                e.preventDefault();
                e.stopPropagation();
                logoPreviewContainer.style.borderColor = "#6366f1";
                logoPreviewContainer.style.boxShadow = "0 0 16px rgba(99,102,241,0.45)";
            });
        });

        ["dragleave", "drop"].forEach((eventName) => {
            logoPreviewContainer.addEventListener(eventName, function (e) {
                e.preventDefault();
                e.stopPropagation();
                logoPreviewContainer.style.borderColor = "var(--border-color)";
                logoPreviewContainer.style.boxShadow = "0 4px 14px rgba(0,0,0,0.2)";
            });
        });

        logoPreviewContainer.addEventListener("drop", function (e) {
            e.preventDefault();
            e.stopPropagation();
            logoPreviewContainer.style.borderColor = "var(--border-color)";
            logoPreviewContainer.style.boxShadow = "0 4px 14px rgba(0,0,0,0.2)";

            const files = e.dataTransfer?.files;
            if (files && files.length > 0) {
                try {
                    logoFileInput.files = files;
                } catch (err) {
                    const dt = new DataTransfer();
                    dt.items.add(files[0]);
                    logoFileInput.files = dt.files;
                }
                handleSekolahFilePreview(files[0]);
            }
        });
    }

    // File input change - Kop Surat
    const kopFileInput = document.getElementById("cardKopFileInput");
    if (kopFileInput) {
        kopFileInput.addEventListener("change", function (e) {
            if (e.target.files && e.target.files.length > 0) {
                handleSekolahKopPreview(e.target.files[0]);
            }
        });
    }

    // Drag & drop dropzone - Kop Surat
    const kopDropZone = document.getElementById("cardKopDropZone");
    if (kopDropZone) {
        ["dragenter", "dragover"].forEach((eventName) => {
            kopDropZone.addEventListener(eventName, function (e) {
                e.preventDefault();
                e.stopPropagation();
                kopDropZone.style.borderColor = "#6366f1";
                kopDropZone.style.background = "rgba(99,102,241,0.08)";
            });
        });

        ["dragleave", "drop"].forEach((eventName) => {
            kopDropZone.addEventListener(eventName, function (e) {
                e.preventDefault();
                e.stopPropagation();
                kopDropZone.style.borderColor = "rgba(99,102,241,0.35)";
                kopDropZone.style.background = "rgba(255,255,255,0.015)";
            });
        });

        kopDropZone.addEventListener("drop", function (e) {
            e.preventDefault();
            e.stopPropagation();
            kopDropZone.style.borderColor = "rgba(99,102,241,0.35)";
            kopDropZone.style.background = "rgba(255,255,255,0.015)";

            const files = e.dataTransfer?.files;
            if (files && files.length > 0) {
                try {
                    kopFileInput.files = files;
                } catch (err) {
                    const dt = new DataTransfer();
                    dt.items.add(files[0]);
                    kopFileInput.files = dt.files;
                }
                handleSekolahKopPreview(files[0]);
            }
        });
    }

    // Drag & drop on preview container - Kop Surat
    const kopPreviewContainer = document.getElementById("cardKopPreviewContainer");
    if (kopPreviewContainer) {
        ["dragenter", "dragover"].forEach((eventName) => {
            kopPreviewContainer.addEventListener(eventName, function (e) {
                e.preventDefault();
                e.stopPropagation();
                kopPreviewContainer.style.borderColor = "#6366f1";
                kopPreviewContainer.style.boxShadow = "0 0 16px rgba(99,102,241,0.45)";
            });
        });

        ["dragleave", "drop"].forEach((eventName) => {
            kopPreviewContainer.addEventListener(eventName, function (e) {
                e.preventDefault();
                e.stopPropagation();
                kopPreviewContainer.style.borderColor = "var(--border-color)";
                kopPreviewContainer.style.boxShadow = "0 4px 14px rgba(0,0,0,0.2)";
            });
        });

        kopPreviewContainer.addEventListener("drop", function (e) {
            e.preventDefault();
            e.stopPropagation();
            kopPreviewContainer.style.borderColor = "var(--border-color)";
            kopPreviewContainer.style.boxShadow = "0 4px 14px rgba(0,0,0,0.2)";

            const files = e.dataTransfer?.files;
            if (files && files.length > 0) {
                try {
                    kopFileInput.files = files;
                } catch (err) {
                    const dt = new DataTransfer();
                    dt.items.add(files[0]);
                    kopFileInput.files = dt.files;
                }
                handleSekolahKopPreview(files[0]);
            }
        });
    }
});
