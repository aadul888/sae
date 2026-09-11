/**
 * Master Data — Kompetensi Keahlian (Sumber: Rombongan Belajar) - Frontend JS
 * Terintegrasi dengan fitur Upload & Auto-Kompresi Logo Jurusan (PNG Transparan)
 */

let currentSelectedKode = "";
let currentLogoUrl = "";

// Helper escape HTML
function escapeHtml(str) {
    if (!str) return "";
    return String(str)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

// Format ukuran byte ke string terbaca
function formatBytes(bytes) {
    if (!bytes || bytes <= 0) return "0 B";
    const k = 1024;
    const sizes = ["B", "KB", "MB"];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + " " + sizes[i];
}

// ==========================================
// 1. MODAL DETAIL ROMBONGAN BELAJAR
// ==========================================
window.openRombelModal = async function (kode, nama) {
    const modal = document.getElementById("rombelModal");
    const modalTitle = document.getElementById("rombelModalTitle");
    const modalSubtitle = document.getElementById("rombelModalSubtitle");
    const loading = document.getElementById("rombelLoading");
    const tableWrapper = document.getElementById("rombelTableWrapper");
    const tableBody = document.getElementById("rombelTableBody");

    if (!modal) return;

    modalTitle.textContent = nama || "Daftar Rombel";
    modalSubtitle.textContent = "Kode Jurusan: " + kode;
    tableBody.innerHTML = "";
    loading.style.display = "block";
    tableWrapper.style.display = "none";
    modal.style.display = "flex";

    try {
        const res = await fetch(
            "/dashboard/master-data/kompetensi-keahlian/" +
                encodeURIComponent(kode) +
                "/rombel",
            {
                headers: {
                    Accept: "application/json",
                },
            }
        );
        const json = await res.json();

        loading.style.display = "none";
        tableWrapper.style.display = "block";

        if (json.status === "success" && json.data && json.data.length > 0) {
            tableBody.innerHTML = json.data
                .map(
                    (r) => `
                <tr style="border-bottom: 1px solid var(--border-color);">
                    <td style="padding: 10px 14px; font-weight: 700; color: var(--primary);">
                        ${escapeHtml(r.nama_rombel || "-")}
                    </td>
                    <td style="padding: 10px 14px; color: var(--text-color);">
                        <span class="badge badge-outline" style="font-size: 0.72rem; padding: 2px 7px;">
                            ${escapeHtml(r.tingkat || "-")}
                        </span>
                    </td>
                    <td style="padding: 10px 14px; color: var(--text-color);">
                        ${escapeHtml(r.wali_kelas || "-")}
                    </td>
                    <td style="padding: 10px 14px; color: var(--text-muted);">
                        ${escapeHtml(r.ruang || "-")}
                    </td>
                    <td style="padding: 10px 14px; color: var(--text-muted); font-size: 0.8rem;">
                        ${escapeHtml(r.kurikulum || "-")}
                    </td>
                    <td style="padding: 10px 14px; text-align: right; font-weight: 700; color: var(--primary);">
                        ${Number(r.total_peserta_didik ?? r.jumlah_peserta_didik ?? 0).toLocaleString("id-ID")}
                    </td>
                </tr>`
                )
                .join("");
        } else {
            tableBody.innerHTML = `
                <tr>
                    <td colspan="6" style="padding: 30px; text-align: center; color: var(--text-muted);">
                        Tidak ada rombongan belajar terdaftar untuk jurusan ini.
                    </td>
                </tr>`;
        }
    } catch (e) {
        loading.style.display = "none";
        tableWrapper.style.display = "block";
        tableBody.innerHTML = `
            <tr>
                <td colspan="6" style="padding: 30px; text-align: center; color: var(--danger, #ef4444);">
                    Gagal memuat rincian rombongan belajar.
                </td>
            </tr>`;
    }
};

window.closeRombelModal = function () {
    const modal = document.getElementById("rombelModal");
    if (modal) modal.style.display = "none";
};

// ==========================================
// 2. MODAL UNGGAH & KELOLA LOGO JURUSAN
// ==========================================
window.openUploadLogoModal = function (kode, nama, logoUrl, logoSize) {
    const modal = document.getElementById("logoUploadModal");
    if (!modal) return;

    currentSelectedKode = kode;
    currentLogoUrl = logoUrl || "";

    const titleEl = document.getElementById("logoModalTitle");
    const subtitleEl = document.getElementById("logoModalSubtitle");
    const previewImg = document.getElementById("logoPreviewImg");
    const placeholder = document.getElementById("logoPreviewPlaceholder");
    const specsEl = document.getElementById("logoFileSpecs");
    const fileInput = document.getElementById("logoFileInput");
    const kodeInput = document.getElementById("logoUploadKode");
    const btnDelete = document.getElementById("btnDeleteLogo");
    const btnSubmit = document.getElementById("btnSubmitLogo");

    titleEl.textContent = currentLogoUrl ? "Kelola Logo Jurusan" : "Unggah Logo Jurusan";
    subtitleEl.textContent = `${nama} (Kode: ${kode})`;
    kodeInput.value = kode;
    fileInput.value = "";

    if (currentLogoUrl) {
        previewImg.src = currentLogoUrl;
        previewImg.style.display = "block";
        placeholder.style.display = "none";
        specsEl.style.display = "block";
        specsEl.innerHTML = `<i class="fas fa-check-circle me-1"></i> Logo Aktif: PNG ${logoSize ? '(' + logoSize + ')' : ''}`;
        btnDelete.style.display = "inline-flex";
        btnSubmit.innerHTML = `<i class="fas fa-cloud-arrow-up me-1"></i> Ganti Logo`;
    } else {
        previewImg.src = "";
        previewImg.style.display = "none";
        placeholder.style.display = "block";
        specsEl.style.display = "none";
        specsEl.textContent = "";
        btnDelete.style.display = "none";
        btnSubmit.innerHTML = `<i class="fas fa-cloud-arrow-up me-1"></i> Simpan Logo`;
    }

    modal.style.display = "flex";
};

window.closeLogoUploadModal = function () {
    const modal = document.getElementById("logoUploadModal");
    if (modal) modal.style.display = "none";
    const fileInput = document.getElementById("logoFileInput");
    if (fileInput) fileInput.value = "";
    currentSelectedKode = "";
    currentLogoUrl = "";
};

// Preview file terpilih pada modal
function handleFilePreview(file) {
    if (!file) return;

    // Validasi ekstensi dan tipe file
    const ext = file.name.split(".").pop().toLowerCase();
    if (ext !== "png" || (file.type && file.type !== "image/png" && file.type !== "image/x-png")) {
        window.SAE.toast("Logo jurusan wajib berformat PNG (.png) untuk transparansi kartu pelajar.", "warning");
        document.getElementById("logoFileInput").value = "";
        return;
    }

    if (file.size > 5 * 1024 * 1024) {
        window.SAE.toast("Ukuran file logo maksimal 5 MB sebelum dikompresi.", "warning");
        document.getElementById("logoFileInput").value = "";
        return;
    }

    const previewImg = document.getElementById("logoPreviewImg");
    const placeholder = document.getElementById("logoPreviewPlaceholder");
    const specsEl = document.getElementById("logoFileSpecs");

    const reader = new FileReader();
    reader.onload = function (e) {
        const dataUrl = e.target.result;
        const tempImg = new Image();
        tempImg.onload = function () {
            previewImg.src = dataUrl;
            previewImg.style.display = "block";
            placeholder.style.display = "none";

            specsEl.style.display = "block";
            specsEl.innerHTML = `
                <span style="color: #10b981;">
                    <i class="fas fa-file-circle-check me-1"></i> PNG Siap: ${formatBytes(file.size)} (${tempImg.width} &times; ${tempImg.height} px)
                </span>
                <div style="font-size: 0.68rem; color: var(--text-muted); margin-top: 2px;">
                    Otomatis dikompresi lossless & disesuaikan saat disimpan
                </div>`;
        };
        tempImg.src = dataUrl;
    };
    reader.readAsDataURL(file);
}

// Kirim Form Upload
window.handleSubmitLogo = async function () {
    const fileInput = document.getElementById("logoFileInput");
    const btnSubmit = document.getElementById("btnSubmitLogo");
    const kode = currentSelectedKode;

    if (!kode) {
        window.SAE.toast("Kode jurusan tidak valid.", "danger");
        return;
    }

    if (!fileInput.files || fileInput.files.length === 0) {
        if (currentLogoUrl) {
            window.SAE.toast("Logo saat ini sudah tersimpan. Pilih file PNG baru jika ingin mengganti logo.", "info");
            return;
        }
        window.SAE.toast("Silakan pilih file logo PNG terlebih dahulu.", "warning");
        return;
    }

    const file = fileInput.files[0];
    const formData = new FormData();
    formData.append("logo", file);

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute("content")
        || document.querySelector('input[name="_token"]')?.value;
    if (csrfToken) {
        formData.append("_token", csrfToken);
    }

    // Tampilkan loading status langsung pada tombol modal
    const originalBtnText = btnSubmit.innerHTML;
    btnSubmit.disabled = true;
    btnSubmit.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Mengompresi & Menyimpan...';

    try {
        const response = await fetch(
            "/dashboard/master-data/kompetensi-keahlian/" +
                encodeURIComponent(kode) +
                "/logo",
            {
                method: "POST",
                headers: {
                    Accept: "application/json",
                    "X-CSRF-TOKEN": csrfToken || "",
                },
                body: formData,
            }
        );

        const result = await response.json();

        if (response.ok && result.status === "success") {
            // Update tampilan baris di tabel secara instan
            updateRowLogoUI(kode, result.logo_url, result.logo_size);

            window.closeLogoUploadModal();

            // Notifikasi sukses menggunakan SAE Toast yang elegan
            window.SAE.toast(`Logo ${kode} berhasil disimpan! (${result.dimensions}, ${result.logo_size})`, "success");
        } else {
            window.SAE.toast(result.message || "Gagal mengunggah logo.", "danger");
        }
    } catch (err) {
        window.SAE.toast("Terjadi kesalahan jaringan atau server.", "danger");
    } finally {
        btnSubmit.disabled = false;
        btnSubmit.innerHTML = originalBtnText;
    }
};

// Hapus Logo
window.handleDeleteLogo = async function () {
    const kode = currentSelectedKode;
    if (!kode) return;

    const confirmed = await window.SAE.confirm(
        "File gambar logo jurusan ini akan dihapus dari penyimpanan sistem.",
        "Hapus Logo Jurusan?",
        "danger",
        "Ya, Hapus Logo",
        "Batal"
    );

    if (!confirmed) return;

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute("content")
        || document.querySelector('input[name="_token"]')?.value;

    const btnDelete = document.getElementById("btnDeleteLogo");
    if (btnDelete) {
        btnDelete.disabled = true;
        btnDelete.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Menghapus...';
    }

    try {
        const res = await fetch(
            "/dashboard/master-data/kompetensi-keahlian/" +
                encodeURIComponent(kode) +
                "/logo",
            {
                method: "DELETE",
                headers: {
                    Accept: "application/json",
                    "X-CSRF-TOKEN": csrfToken || "",
                    "Content-Type": "application/json",
                },
            }
        );

        const result = await res.json();

        if (res.ok && result.status === "success") {
            updateRowLogoUI(kode, null, null);
            window.closeLogoUploadModal();
            window.SAE.toast("Logo jurusan berhasil dihapus.", "success");
        } else {
            window.SAE.toast(result.message || "Gagal menghapus logo.", "danger");
        }
    } catch (e) {
        window.SAE.toast("Terjadi kesalahan jaringan.", "danger");
    } finally {
        if (btnDelete) {
            btnDelete.disabled = false;
            btnDelete.innerHTML = '<i class="fas fa-trash-can me-1"></i> Hapus Logo';
        }
    }
};

// Update tampilan DOM di tabel tanpa refresh
function updateRowLogoUI(kode, logoUrl, logoSize) {
    const thumbContainer = document.getElementById("logoThumb_" + kode);
    const badgeContainer = document.getElementById("logoBadge_" + kode);

    if (thumbContainer) {
        if (logoUrl) {
            thumbContainer.className = "jurusan-logo-thumb";
            thumbContainer.style.background = "repeating-conic-gradient(#2a3447 0% 25%, #182030 0% 50%) 50% / 8px 8px";
            thumbContainer.style.border = "1.5px solid var(--border-color)";
            thumbContainer.style.color = "";
            thumbContainer.innerHTML = `
                <img src="${logoUrl}?v=${Date.now()}"
                     alt="Logo Jurusan"
                     style="max-width: 92%; max-height: 92%; object-fit: contain; filter: drop-shadow(0 1px 2px rgba(0,0,0,0.3));">`;
        } else {
            thumbContainer.className = "jurusan-logo-thumb empty";
            thumbContainer.style.background = "rgba(99,102,241,0.08)";
            thumbContainer.style.border = "1.5px dashed rgba(99,102,241,0.4)";
            thumbContainer.style.color = "var(--primary)";
            thumbContainer.innerHTML = `
                <i class="fas fa-arrow-up-from-bracket" style="font-size: 0.78rem;"></i>
                <span style="font-size: 0.55rem; font-weight: 800; letter-spacing: 0.5px; margin-top: 2px;">PNG</span>`;
        }
    }

    if (badgeContainer) {
        badgeContainer.style.whiteSpace = "nowrap";
        if (logoUrl) {
            badgeContainer.className = "badge";
            badgeContainer.style.background = "rgba(16,185,129,0.12)";
            badgeContainer.style.color = "#10b981";
            badgeContainer.style.padding = "2px 6px";
            badgeContainer.title = `Logo PNG tersimpan persisten (${logoSize || ''})`;
            badgeContainer.innerHTML = `<i class="fas fa-check-circle me-1"></i>PNG ${logoSize || ''}`;
        } else {
            badgeContainer.className = "badge badge-outline";
            badgeContainer.style.background = "";
            badgeContainer.style.color = "var(--text-muted)";
            badgeContainer.style.opacity = "0.7";
            badgeContainer.style.padding = "1px 5px";
            badgeContainer.title = "";
            badgeContainer.textContent = "Belum ada logo";
        }
    }
}

// ==========================================
// 3. EVENT LISTENERS & SEARCH / PAGINATION
// ==========================================
document.addEventListener("DOMContentLoaded", function () {
    // Backdrop modal close handlers
    const rombelModal = document.getElementById("rombelModal");
    if (rombelModal) {
        rombelModal.addEventListener("click", function (e) {
            if (e.target === rombelModal) {
                window.closeRombelModal();
            }
        });
    }

    const logoModal = document.getElementById("logoUploadModal");
    if (logoModal) {
        logoModal.addEventListener("click", function (e) {
            if (e.target === logoModal) {
                window.closeLogoUploadModal();
            }
        });
    }

    document.addEventListener("keydown", function (e) {
        if (e.key === "Escape") {
            window.closeRombelModal();
            window.closeLogoUploadModal();
        }
    });

    // File Input Listener
    const fileInput = document.getElementById("logoFileInput");
    if (fileInput) {
        fileInput.addEventListener("change", function (e) {
            if (e.target.files && e.target.files.length > 0) {
                handleFilePreview(e.target.files[0]);
            }
        });
    }

    // Drag & Drop Listener
    const dropZone = document.getElementById("logoDropZone");
    if (dropZone) {
        ["dragenter", "dragover"].forEach((eventName) => {
            dropZone.addEventListener(eventName, function (e) {
                e.preventDefault();
                e.stopPropagation();
                dropZone.style.borderColor = "#6366f1";
                dropZone.style.background = "rgba(99,102,241,0.08)";
            });
        });

        ["dragleave", "drop"].forEach((eventName) => {
            dropZone.addEventListener(eventName, function (e) {
                e.preventDefault();
                e.stopPropagation();
                dropZone.style.borderColor = "rgba(99,102,241,0.4)";
                dropZone.style.background = "rgba(255,255,255,0.01)";
            });
        });

        dropZone.addEventListener("drop", function (e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            if (files && files.length > 0) {
                fileInput.files = files;
                handleFilePreview(files[0]);
            }
        });
    }

    // Search Table Handler
    const searchInput = document.getElementById("liveSearch") || document.getElementById("searchTable");
    const clearSearch = document.getElementById("clearSearch");

    if (searchInput) {
        let timer = null;
        searchInput.addEventListener("input", function () {
            if (clearSearch) {
                if (this.value.trim().length > 0) {
                    clearSearch.classList.add("visible");
                } else {
                    clearSearch.classList.remove("visible");
                }
            }
            clearTimeout(timer);
            timer = setTimeout(() => {
                const url = new URL(window.location.href);
                if (this.value.trim()) {
                    url.searchParams.set("q", this.value.trim());
                } else {
                    url.searchParams.delete("q");
                }
                url.searchParams.set("page", "1");
                window.location.href = url.toString();
            }, 500);
        });

        if (clearSearch) {
            clearSearch.addEventListener("click", function () {
                searchInput.value = "";
                clearSearch.classList.remove("visible");
                const url = new URL(window.location.href);
                url.searchParams.delete("q");
                url.searchParams.set("page", "1");
                window.location.href = url.toString();
            });
        }
    }

    // Per Page Select Handler
    const perPageSelect = document.getElementById("perPageSelect");
    if (perPageSelect) {
        perPageSelect.addEventListener("change", function () {
            const url = new URL(window.location.href);
            url.searchParams.set("perPage", this.value);
            url.searchParams.set("page", "1");
            window.location.href = url.toString();
        });
    }

    // Sortable Column Headers Click Handler
    document.querySelectorAll(".sortable-th").forEach(function (th) {
        th.style.cursor = "pointer";
        th.addEventListener("click", function () {
            const sortField = this.getAttribute("data-sort");
            if (!sortField) return;

            const url = new URL(window.location.href);
            const currentSort = url.searchParams.get("sort") || "nama";
            const currentDir = url.searchParams.get("sort_dir") || "asc";

            let newDir = "asc";
            if (currentSort === sortField && currentDir === "asc") {
                newDir = "desc";
            }

            url.searchParams.set("sort", sortField);
            url.searchParams.set("sort_dir", newDir);
            url.searchParams.set("page", "1");
            window.location.href = url.toString();
        });
    });
});
