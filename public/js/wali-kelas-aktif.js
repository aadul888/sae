/**
 * Wali Kelas — Peserta Didik Aktif
 * Modular JavaScript untuk Datatable Baku SAE, Live Search Asinkron, Sortable Header, dan Modal Detail Lengkap
 */

function escapeHtml(str) {
    if (!str) return "-";
    return String(str)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

// ==========================================
// 1. MODAL DETAIL BIODATA LENGKAP PESERTA DIDIK
// ==========================================
window.openBiodataPesertaDidikModal = async function (id) {
    const modal = document.getElementById("biodataModal");
    const bioNama = document.getElementById("bioNama");
    const bioRombel = document.getElementById("bioRombel");
    const bioLoading = document.getElementById("bioLoading");
    const bioContent = document.getElementById("bioContent");

    if (!modal) return;

    bioNama.textContent = "Biodata Peserta Didik";
    bioRombel.textContent = "Memuat data...";
    bioLoading.style.display = "block";
    bioContent.style.display = "none";
    modal.style.display = "flex";
    document.body.style.overflow = "hidden";

    try {
        const res = await fetch(
            "/dashboard/wali-kelas/peserta-didik/" + encodeURIComponent(id),
            {
                headers: {
                    Accept: "application/json",
                    "X-Requested-With": "XMLHttpRequest",
                },
            },
        );
        const json = await res.json();

        bioLoading.style.display = "none";
        bioContent.style.display = "block";

        if ((json.status === "success" || json.success) && json.data) {
            const d = json.data;
            bioNama.textContent = d.nama || "Tanpa Nama";
            bioRombel.textContent =
                [d.nama_rombel, d.kurikulum_id_str]
                    .filter(Boolean)
                    .join(" • ") || "-";

            const fotoBox = document.getElementById("bioFotoContainer");
            if (fotoBox) {
                const fUrl = json.foto_url || d.foto_url;
                if (fUrl) {
                    fotoBox.innerHTML = `<img src="${fUrl}?v=${Date.now()}" alt="Foto ${escapeHtml(d.nama)}" style="width: 100%; height: 100%; object-fit: cover;">`;
                } else {
                    fotoBox.innerHTML = `<i class="fas fa-user-graduate text-primary" style="font-size: 1.3rem;"></i>`;
                }
            }

            document.getElementById("bioNisn").textContent =
                [
                    d.nisn ? "NISN: " + d.nisn : null,
                    d.nipd ? "NIPD: " + d.nipd : null,
                ]
                    .filter(Boolean)
                    .join(" / ") || "-";
            document.getElementById("bioNik").textContent = d.nik || "-";
            document.getElementById("bioJk").textContent =
                d.jenis_kelamin === "L"
                    ? "Laki-Laki (L)"
                    : d.jenis_kelamin === "P"
                      ? "Perempuan (P)"
                      : "-";
            document.getElementById("bioTtl").textContent =
                [d.tempat_lahir, d.tanggal_lahir].filter(Boolean).join(", ") || "-";
            document.getElementById("bioAgama").textContent =
                d.agama_id_str || "-";
            document.getElementById("bioAnak").textContent = d.anak_keberapa
                ? "Anak ke-" + d.anak_keberapa
                : "-";

            const tb = d.tinggi_badan ? d.tinggi_badan + " cm" : null;
            const bb = d.berat_badan ? d.berat_badan + " kg" : null;
            document.getElementById("bioFisik").textContent =
                [tb, bb].filter(Boolean).join(" • ") || "Belum dicatat";

            document.getElementById("bioKhusus").textContent =
                d.kebutuhan_khusus || "Tidak Ada";

            const pendaftaranStr = [
                d.jenis_pendaftaran_id_str ||
                    (json.anggota
                        ? json.anggota.jenis_pendaftaran_id_str
                        : null) ||
                    "Peserta Didik Baru",
                d.sekolah_asal ? "Asal: " + d.sekolah_asal : null,
            ]
                .filter(Boolean)
                .join(" • ");
            document.getElementById("bioPendaftaran").textContent =
                pendaftaranStr || "-";

            document.getElementById("bioTglMasuk").textContent =
                d.tanggal_masuk_sekolah || "-";

            const regArr = [
                d.registrasi_id ? "Reg: " + d.registrasi_id : null,
                json.anggota && json.anggota.anggota_rombel_id
                    ? "Anggota ID: " + json.anggota.anggota_rombel_id
                    : d.anggota_rombel_id
                      ? "Anggota ID: " + d.anggota_rombel_id
                      : null,
            ].filter(Boolean);
            document.getElementById("bioRegId").textContent =
                regArr.length > 0 ? regArr.join(" • ") : "-";

            document.getElementById("bioAyah").textContent =
                [d.nama_ayah, d.pekerjaan_ayah_id_str]
                    .filter(Boolean)
                    .join(" • Pekerjaan: ") || "-";
            document.getElementById("bioIbu").textContent =
                [d.nama_ibu, d.pekerjaan_ibu_id_str]
                    .filter(Boolean)
                    .join(" • Pekerjaan: ") || "-";
            document.getElementById("bioWali").textContent =
                [d.nama_wali, d.pekerjaan_wali_id_str]
                    .filter(Boolean)
                    .join(" • Pekerjaan: ") || "Tidak Ada (Ikut Orang Tua)";

            const kontakArr = [
                d.nomor_telepon_seluler
                    ? "HP/WA: " + d.nomor_telepon_seluler
                    : null,
                d.nomor_telepon_rumah ? "Telp: " + d.nomor_telepon_rumah : null,
                d.email ? "Email: " + d.email : null,
            ].filter(Boolean);
            document.getElementById("bioHp").textContent =
                kontakArr.length > 0 ? kontakArr.join(" • ") : "-";

            document.getElementById("bioAlamat").textContent =
                d.alamat_jalan || "-";

            // Render Mata Pelajaran di Kelas
            const mapelSection = document.getElementById("bioMapelSection");
            const mapelList = document.getElementById("bioMapelList");
            const jmlMapel = document.getElementById("bioJmlMapel");
            const pemList = json.pembelajaran || [];

            if (mapelSection && mapelList) {
                if (pemList.length > 0) {
                    if (jmlMapel)
                        jmlMapel.textContent =
                            pemList.length +
                            " Mapel (" +
                            (json.total_jam || 0) +
                            " JP)";
                    mapelList.innerHTML = pemList
                        .map(
                            (p) => `
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 10px; font-weight: 600; color: var(--text-color);">${escapeHtml(p.nama_mata_pelajaran || p.mata_pelajaran_id_str || "-")}</td>
                            <td style="padding: 6px 10px; color: var(--text-color); font-size: 0.78rem;">${escapeHtml(p.nama_guru || "Belum Ditugaskan")}</td>
                            <td style="padding: 6px 10px; text-align: center; color: #f59e0b; font-weight: 700;">${escapeHtml(p.jam_mengajar_per_minggu || "0")} JP</td>
                        </tr>
                    `,
                        )
                        .join("");
                    mapelSection.style.display = "block";
                } else {
                    mapelSection.style.display = "none";
                }
            }
        } else {
            throw new Error(json.message || "Gagal memuat biodata peserta didik.");
        }
    } catch (e) {
        bioLoading.style.display = "none";
        bioContent.style.display = "block";
        bioRombel.textContent = e.message || "Gagal memuat biodata.";
    }
};

window.closeBiodataModal = function () {
    const modal = document.getElementById("biodataModal");
    if (modal) {
        modal.style.display = "none";
        document.body.style.overflow = "";
    }
};

document.addEventListener("keydown", function (e) {
    if (e.key === "Escape") {
        window.closeBiodataModal();
    }
});

document.addEventListener("click", function (e) {
    const modal = document.getElementById("biodataModal");
    if (modal && e.target === modal) {
        window.closeBiodataModal();
    }
});

// Event Delegation untuk tombol detail peserta didik
document.addEventListener("click", function (e) {
    const btn = e.target.closest(".btn-detail-siswa");
    if (!btn) return;
    const id = btn.getAttribute("data-id");
    if (id) {
        window.openBiodataPesertaDidikModal(id);
    }
});

// ==========================================
// 2. FILTER & LIVE TABLE ENGINE
// ==========================================
document.addEventListener("DOMContentLoaded", function () {
    const perPageSelect = document.getElementById("perPageSelect");
    const filterGender = document.getElementById("filterGender");
    const adminRombelSelect = document.getElementById("adminRombelSelect");
    const liveSearchInput = document.getElementById("liveSearchInput");
    const clearSearchBtn = document.getElementById("clearSearchBtn");

    function applyFilter(overrideParams = {}) {
        const url = new URL(window.location.href);

        if (perPageSelect) url.searchParams.set("perPage", perPageSelect.value);
        if (filterGender) {
            if (filterGender.value) url.searchParams.set("gender", filterGender.value);
            else url.searchParams.delete("gender");
        }
        if (adminRombelSelect) {
            if (adminRombelSelect.value) url.searchParams.set("rombel_id", adminRombelSelect.value);
            else url.searchParams.delete("rombel_id");
        }
        if (liveSearchInput) {
            const qVal = liveSearchInput.value.trim();
            if (qVal) url.searchParams.set("q", qVal);
            else url.searchParams.delete("q");
        }

        Object.keys(overrideParams).forEach((k) => {
            const v = overrideParams[k];
            if (v !== null && v !== undefined && v !== "") url.searchParams.set(k, v);
            else url.searchParams.delete(k);
        });

        // Reset ke page 1 saat ganti filter/sort kecuali page eksplisit
        if (!("page" in overrideParams)) {
            url.searchParams.delete("page");
        }

        if (typeof window.refreshLiveTable === "function") {
            window.refreshLiveTable(url.toString());
        } else {
            window.location.href = url.toString();
        }
    }

    if (perPageSelect) perPageSelect.addEventListener("change", () => applyFilter());
    if (filterGender) filterGender.addEventListener("change", () => applyFilter());
    if (adminRombelSelect) adminRombelSelect.addEventListener("change", () => applyFilter());

    // Live search asinkron dengan debounce
    let searchDebounce = null;
    if (liveSearchInput) {
        liveSearchInput.addEventListener("input", function () {
            clearTimeout(searchDebounce);
            if (clearSearchBtn) {
                clearSearchBtn.classList.toggle("visible", this.value.trim().length > 0);
            }
            searchDebounce = setTimeout(() => {
                applyFilter();
            }, 350);
        });

        liveSearchInput.addEventListener("keydown", function (e) {
            if (e.key === "Enter") {
                e.preventDefault();
                clearTimeout(searchDebounce);
                applyFilter();
            }
        });
    }

    if (clearSearchBtn) {
        clearSearchBtn.addEventListener("click", function () {
            if (liveSearchInput) {
                liveSearchInput.value = "";
                this.classList.remove("visible");
                liveSearchInput.focus();
            }
            applyFilter({ q: "" });
        });
    }

    // Sortable Column Header Server-Side
    document.addEventListener("click", function (e) {
        const th = e.target.closest(".sortable-th");
        if (!th) return;

        const sortField = th.getAttribute("data-sort");
        if (!sortField) return;

        const url = new URL(window.location.href);
        const currentSort = url.searchParams.get("sort") || "nama";
        const currentDir = url.searchParams.get("sort_dir") || "asc";

        let newDir = "asc";
        if (currentSort === sortField && currentDir === "asc") {
            newDir = "desc";
        }

        applyFilter({ sort: sortField, sort_dir: newDir });
    });

    // Wire up Drag & Drop and File Input for Photo Upload Modal
    const fileInput = document.getElementById("fotoFileInput");
    const dropZone = document.getElementById("fotoDropZone");

    if (fileInput) {
        fileInput.addEventListener("change", function () {
            if (this.files && this.files[0]) {
                handleFilePreview(this.files[0]);
            }
        });
    }

    if (dropZone) {
        ["dragenter", "dragover"].forEach((eventName) => {
            dropZone.addEventListener(eventName, (e) => {
                e.preventDefault();
                e.stopPropagation();
                dropZone.style.borderColor = "var(--primary)";
                dropZone.style.background = "rgba(99, 102, 241, 0.08)";
            });
        });

        ["dragleave", "drop"].forEach((eventName) => {
            dropZone.addEventListener(eventName, (e) => {
                e.preventDefault();
                e.stopPropagation();
                dropZone.style.borderColor = "rgba(99, 102, 241, 0.4)";
                dropZone.style.background = "rgba(255, 255, 255, 0.01)";
            });
        });

        dropZone.addEventListener("drop", (e) => {
            const dt = e.dataTransfer;
            if (dt && dt.files && dt.files[0]) {
                if (fileInput) {
                    fileInput.files = dt.files;
                }
                handleFilePreview(dt.files[0]);
            }
        });
    }
});

// ==========================================
// 2. MODAL UNGGAH & KELOLA PASFOTO PESERTA DIDIK
// ==========================================
let currentSelectedPdId = null;
let currentFotoUrl = null;

function formatBytes(bytes, decimals = 1) {
    if (!bytes || bytes === 0) return '0 B';
    const k = 1024;
    const dm = decimals < 0 ? 0 : decimals;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
}

window.openUploadFotoModal = function (id, nama, nisn, fotoUrl, fotoSize) {
    currentSelectedPdId = id;
    currentFotoUrl = fotoUrl || "";

    const modal = document.getElementById("fotoUploadModal");
    const title = document.getElementById("fotoModalTitle");
    const subtitle = document.getElementById("fotoModalSubtitle");
    const hiddenId = document.getElementById("fotoUploadPdId");
    const previewImg = document.getElementById("fotoPreviewImg");
    const placeholder = document.getElementById("fotoPreviewPlaceholder");
    const specs = document.getElementById("fotoFileSpecs");
    const btnDelete = document.getElementById("btnDeleteFoto");
    const fileInput = document.getElementById("fotoFileInput");

    if (!modal) return;

    if (fileInput) fileInput.value = "";
    if (hiddenId) hiddenId.value = id;
    if (title) title.textContent = "Pasfoto: " + (nama || "Peserta Didik");
    if (subtitle) subtitle.textContent = "NISN: " + (nisn || "-") + " • ID: " + id;

    if (fotoUrl) {
        if (previewImg) {
            previewImg.src = fotoUrl + "?v=" + Date.now();
            previewImg.style.display = "block";
        }
        if (placeholder) placeholder.style.display = "none";
        if (specs) {
            specs.style.display = "block";
            specs.innerHTML = `<i class="fas fa-circle-check me-1"></i> Format: PNG • Ukuran: ${fotoSize || "Tersimpan"}`;
        }
        if (btnDelete) btnDelete.style.display = "inline-flex";
    } else {
        if (previewImg) {
            previewImg.src = "";
            previewImg.style.display = "none";
        }
        if (placeholder) placeholder.style.display = "block";
        if (specs) {
            specs.style.display = "none";
            specs.textContent = "";
        }
        if (btnDelete) btnDelete.style.display = "none";
    }

    modal.style.display = "flex";
};

window.closeUploadFotoModal = function () {
    const modal = document.getElementById("fotoUploadModal");
    if (modal) modal.style.display = "none";
    const fileInput = document.getElementById("fotoFileInput");
    if (fileInput) fileInput.value = "";
};

function handleFilePreview(file) {
    if (!file) return;

    const ext = file.name.split(".").pop().toLowerCase();
    if (ext !== "png" || (file.type && file.type !== "image/png" && file.type !== "image/x-png")) {
        if (window.SAE && typeof window.SAE.toast === "function") {
            window.SAE.toast("Pasfoto peserta didik wajib berformat PNG (.png).", "warning");
        } else {
            alert("File harus berformat PNG (.png).");
        }
        const fileInput = document.getElementById("fotoFileInput");
        if (fileInput) fileInput.value = "";
        return;
    }

    if (file.size > 5 * 1024 * 1024) {
        if (window.SAE && typeof window.SAE.toast === "function") {
            window.SAE.toast("Ukuran file pasfoto maksimal 5 MB.", "danger");
        } else {
            alert("Ukuran file maksimal 5 MB.");
        }
        const fileInput = document.getElementById("fotoFileInput");
        if (fileInput) fileInput.value = "";
        return;
    }

    const previewImg = document.getElementById("fotoPreviewImg");
    const placeholder = document.getElementById("fotoPreviewPlaceholder");
    const specsEl = document.getElementById("fotoFileSpecs");

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
                specsEl.innerHTML = `<span style="color: #10b981;"><i class="fas fa-file-circle-check me-1"></i> PNG Siap: ${formatBytes(file.size)} (${tempImg.width} &times; ${tempImg.height} px)</span>`;
            }
        };
        tempImg.src = dataUrl;
    };
    reader.readAsDataURL(file);
}

window.handleSubmitFoto = async function () {
    const fileInput = document.getElementById("fotoFileInput");
    const pdId = currentSelectedPdId || document.getElementById("fotoUploadPdId")?.value;
    const btnSubmit = document.getElementById("btnSubmitFoto");

    if (!pdId) {
        alert("ID peserta didik tidak valid.");
        return;
    }

    if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
        alert("Silakan pilih file foto terlebih dahulu.");
        return;
    }

    const file = fileInput.files[0];
    const formData = new FormData();
    formData.append("peserta_didik_id", pdId);
    formData.append("foto", file);

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || "";

    if (btnSubmit) {
        btnSubmit.disabled = true;
        btnSubmit.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Mengompresi & Menyimpan...';
    }

    try {
        const res = await fetch("/dashboard/manajemen-data/peserta-didik-aktif/upload-foto", {
            method: "POST",
            headers: {
                "X-CSRF-TOKEN": csrfToken,
                Accept: "application/json",
            },
            body: formData,
        });

        const json = await res.json();

        if (json.status === "success") {
            if (window.SAE && typeof window.SAE.toast === "function") {
                window.SAE.toast(`Foto ${json.data.nama || "peserta didik"} berhasil disimpan!`, "success");
            } else {
                alert("Foto berhasil disimpan!");
            }
            window.closeUploadFotoModal();
            setTimeout(() => window.location.reload(), 500);
        } else {
            throw new Error(json.message || "Gagal mengunggah foto.");
        }
    } catch (err) {
        if (window.SAE && typeof window.SAE.toast === "function") {
            window.SAE.toast(err.message || "Gagal mengunggah foto.", "danger");
        } else {
            alert(err.message || "Gagal mengunggah foto.");
        }
    } finally {
        if (btnSubmit) {
            btnSubmit.disabled = false;
            btnSubmit.innerHTML = '<i class="fas fa-cloud-arrow-up me-1"></i> Simpan Pasfoto';
        }
    }
};

window.handleDeleteFoto = async function () {
    const pdId = currentSelectedPdId || document.getElementById("fotoUploadPdId")?.value;
    if (!pdId) return;

    if (!confirm("Apakah Anda yakin ingin menghapus pasfoto peserta didik ini?")) return;

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || "";

    try {
        const res = await fetch(`/dashboard/manajemen-data/peserta-didik-aktif/${encodeURIComponent(pdId)}/delete-foto`, {
            method: "DELETE",
            headers: {
                "X-CSRF-TOKEN": csrfToken,
                Accept: "application/json",
            },
        });
        const json = await res.json();
        if (json.status === "success") {
            window.closeUploadFotoModal();
            setTimeout(() => window.location.reload(), 500);
        } else {
            throw new Error(json.message || "Gagal menghapus foto.");
        }
    } catch (err) {
        alert(err.message || "Terjadi kesalahan.");
    }
};
