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
    const searchInput = document.getElementById("liveSearch") || document.getElementById("liveSearchInput");
    const clearBtn = document.getElementById("clearSearch") || document.getElementById("clearSearchBtn");

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
        const searchEl = document.getElementById("liveSearch") || document.getElementById("liveSearchInput");
        if (searchEl) {
            const qVal = searchEl.value.trim();
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

    // Live search asinkron dengan debounce 450ms
    let searchDebounce = null;
    document.addEventListener("input", function (e) {
        if (e.target && (e.target.id === "liveSearch" || e.target.id === "liveSearchInput")) {
            const cBtn = document.getElementById("clearSearch") || document.getElementById("clearSearchBtn");
            if (cBtn) {
                cBtn.classList.toggle("visible", e.target.value.trim().length > 0);
            }
            clearTimeout(searchDebounce);
            searchDebounce = setTimeout(() => {
                applyFilter();
            }, 450);
        }
    });

    document.addEventListener("keydown", function (e) {
        if (e.target && (e.target.id === "liveSearch" || e.target.id === "liveSearchInput") && e.key === "Enter") {
            e.preventDefault();
            clearTimeout(searchDebounce);
            applyFilter();
        }
    });

    document.addEventListener("click", function (e) {
        const cBtn = e.target.closest("#clearSearch, #clearSearchBtn");
        if (cBtn) {
            const sEl = document.getElementById("liveSearch") || document.getElementById("liveSearchInput");
            if (sEl) {
                sEl.value = "";
                cBtn.classList.remove("visible");
                sEl.focus();
            }
            applyFilter({ q: "" });
        }
    });

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
    const btnDownload = document.getElementById("btnDownloadFoto");
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
        if (btnDownload) btnDownload.style.display = "inline-flex";
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
        if (btnDownload) btnDownload.style.display = "none";
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
            window.SAE.alert("File harus berformat PNG (.png).", 'Informasi', 'info');
        }
        const fileInput = document.getElementById("fotoFileInput");
        if (fileInput) fileInput.value = "";
        return;
    }

    if (file.size > 5 * 1024 * 1024) {
        if (window.SAE && typeof window.SAE.toast === "function") {
            window.SAE.toast("Ukuran file pasfoto maksimal 5 MB.", "danger");
        } else {
            window.SAE.alert("Ukuran file maksimal 5 MB.", 'Informasi', 'info');
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

            const btnDownload = document.getElementById("btnDownloadFoto");
            if (btnDownload) btnDownload.style.display = "inline-flex";

            if (specsEl) {
                specsEl.style.display = "block";
                specsEl.innerHTML = `<span style="color: #10b981;"><i class="fas fa-file-circle-check me-1"></i> PNG Siap: ${formatBytes(file.size)} (${tempImg.width} &times; ${tempImg.height} px)</span>`;
            }
        };
        tempImg.src = dataUrl;
    };
    reader.readAsDataURL(file);
}

window.handleDownloadFoto = function () {
    const pdId = currentSelectedPdId || document.getElementById("fotoUploadPdId")?.value;
    if (!pdId) {
        if (window.SAE && typeof window.SAE.toast === "function") {
            window.SAE.toast("Peserta didik belum dipilih.", "warning");
        } else {
            window.SAE.alert("Peserta didik belum dipilih.", 'Informasi', 'info');
        }
        return;
    }

    if (currentFotoUrl) {
        const downloadUrl = `/dashboard/manajemen-data/peserta-didik-aktif/${encodeURIComponent(pdId)}/download-foto`;
        const link = document.createElement("a");
        link.href = downloadUrl;
        link.setAttribute("download", "");
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        return;
    }

    const fileInput = document.getElementById("fotoFileInput");
    if (fileInput && fileInput.files && fileInput.files[0]) {
        const file = fileInput.files[0];
        const url = URL.createObjectURL(file);
        const link = document.createElement("a");
        link.href = url;
        link.download = file.name || "pasfoto.png";
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(url);
        return;
    }

    if (window.SAE && typeof window.SAE.toast === "function") {
        window.SAE.toast("Belum ada pasfoto yang dapat diunduh.", "warning");
    } else {
        window.SAE.alert("Belum ada pasfoto yang dapat diunduh.", 'Informasi', 'info');
    }
};

window.handleSubmitFoto = async function () {
    const fileInput = document.getElementById("fotoFileInput");
    const pdId = currentSelectedPdId || document.getElementById("fotoUploadPdId")?.value;
    const btnSubmit = document.getElementById("btnSubmitFoto");

    if (!pdId) {
        window.SAE.alert("ID peserta didik tidak valid.", 'Informasi', 'info');
        return;
    }

    if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
        window.SAE.alert("Silakan pilih file foto terlebih dahulu.", 'Informasi', 'info');
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
                window.SAE.alert("Foto berhasil disimpan!", 'Informasi', 'info');
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
            window.SAE.alert(err.message || "Gagal mengunggah foto.", 'Informasi', 'info');
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

    if (!(await window.SAE.confirm("Apakah Anda yakin ingin menghapus pasfoto peserta didik ini?", "Hapus Pasfoto?", "danger", "Ya, Hapus"))) return;

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
        window.SAE.alert(err.message || "Terjadi kesalahan.", 'Informasi', 'info');
    }
};

// =========================================================================
// 4. RESET PASSWORD PESERTA DIDIK KE DEFAULT (NISN)
// =========================================================================
document.addEventListener("click", async function (e) {
    const btnReset = e.target.closest(".btn-reset-password");
    if (!btnReset) return;

    const pdId = btnReset.dataset.id;
    const pdNama = btnReset.dataset.nama || "Peserta Didik";
    const pdNisn = btnReset.dataset.nisn || "NISN";

    const result = await window.SAE.confirm("Apakah Anda yakin ingin mereset password akun untuk <b>${pdNama}</b>?<br><br>` +\n            `<div style=\"font-size: 0.85rem; padding: 10px 14px; background: rgba(245,158,11,0.1); border: 1px dashed rgba(245,158,11,0.4); border-radius: 8px; text-align: left; color: var(--text-color); line-height: 1.5;\">` +\n            `<i class=\"fas fa-info-circle text-warning me-1\"></i> Password akan dikembalikan ke default: <b>${pdNisn}</b>.<br>` +\n            `Peserta didik akan diminta memperbarui password saat login pertama kali.</div>", "Reset Password Akun?", 'warning', "<i class=\"fas fa-key me-1\"></i> Ya, Reset Password", "Batal");

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
        window.SAE.alert("Gagal terhubung ke server. Silakan coba kembali.", "Kesalahan Jaringan", 'error');
    }
});

// =========================================================================
// 5. TUNJUK / CABUT STATUS KOORDINATOR KELAS (ASISTEN WALI KELAS)
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
                badge.classList.toggle("badge-koordinator--off", !newStatus);
                badge.style.display = newStatus ? "inline-flex" : "none";
            }

            Swal.fire({
                title: "Berhasil!",
                html: data.message,
                icon: "success",
                confirmButtonColor: "#10b981",
            });
        } else {
            Swal.fire({
                title: "Gagal",
                text: data.message || "Gagal memperbarui status koordinator.",
                icon: "error",
                confirmButtonColor: "#ef4444",
            });
        }
    } catch (err) {
        window.SAE.alert("Gagal terhubung ke server.", "Kesalahan Jaringan", 'error');
    }
});
