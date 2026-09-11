/**
 * Manajemen Data — Peserta Didik Aktif - Frontend JS
 */

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

    try {
        const res = await fetch(
            "/dashboard/manajemen-data/peserta-didik-aktif/" +
                encodeURIComponent(id),
            {
                headers: { Accept: "application/json" },
            },
        );
        const json = await res.json();

        bioLoading.style.display = "none";
        bioContent.style.display = "block";

        if (json.status === "success" && json.data) {
            const d = json.data;
            bioNama.textContent = d.nama || "Tanpa Nama";
            bioRombel.textContent =
                [d.nama_rombel, d.kurikulum_id_str]
                    .filter(Boolean)
                    .join(" • ") || "-";

            const fotoBox = document.getElementById("bioFotoContainer");
            if (fotoBox) {
                if (json.foto_url) {
                    fotoBox.innerHTML = `<img src="${json.foto_url}?v=${Date.now()}" alt="Foto ${d.nama}" style="width: 100%; height: 100%; object-fit: cover;">`;
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
                [d.tempat_lahir, d.tanggal_lahir].filter(Boolean).join(", ") ||
                "-";
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
        }
    } catch (e) {
        bioLoading.style.display = "none";
        bioContent.style.display = "block";
        bioRombel.textContent = "Gagal memuat biodata.";
    }
};

window.closeBiodataModal = function () {
    const modal = document.getElementById("biodataModal");
    if (modal) modal.style.display = "none";
};

// ==========================================
// 2. MODAL & UPLOAD PASFOTO PESERTA DIDIK (PNG)
// ==========================================
let currentSelectedPdId = "";
let currentFotoUrl = "";

function formatBytes(bytes) {
    if (!bytes || bytes <= 0) return "0 B";
    const k = 1024;
    const sizes = ["B", "KB", "MB"];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + " " + sizes[i];
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

    // Validasi ekstensi dan MIME khusus PNG
    const ext = file.name.split(".").pop().toLowerCase();
    if (ext !== "png" || (file.type && file.type !== "image/png" && file.type !== "image/x-png")) {
        if (window.SAE && typeof window.SAE.toast === "function") {
            window.SAE.toast("Pasfoto peserta didik wajib berformat PNG (.png) untuk kebutuhan kartu pelajar digital.", "warning");
        } else {
            alert("File harus berformat PNG (.png).");
        }
        const fileInput = document.getElementById("fotoFileInput");
        if (fileInput) fileInput.value = "";
        return;
    }

    // Validasi ukuran maksimal (5 MB)
    if (file.size > 5 * 1024 * 1024) {
        if (window.SAE && typeof window.SAE.toast === "function") {
            window.SAE.toast("Ukuran file pasfoto maksimal 5 MB sebelum dikompresi.", "danger");
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
                specsEl.innerHTML = `
                    <span style="color: #10b981;">
                        <i class="fas fa-file-circle-check me-1"></i> PNG Siap: ${formatBytes(file.size)} (${tempImg.width} &times; ${tempImg.height} px)
                    </span>
                    <div style="font-size: 0.68rem; color: var(--text-muted); margin-top: 2px;">
                        Otomatis dikompresi lossless & disesuaikan ke rasio 3:4 kartu pelajar
                    </div>`;
            }
        };
        tempImg.src = dataUrl;
    };
    reader.readAsDataURL(file);
}

window.handleSubmitFoto = async function () {
    const fileInput = document.getElementById("fotoFileInput");
    const pdId = document.getElementById("fotoUploadPdId")?.value;
    const btnSubmit = document.getElementById("btnSubmitFoto");

    if (!pdId) {
        if (window.SAE && typeof window.SAE.toast === "function") {
            window.SAE.toast("Data peserta didik tidak valid.", "danger");
        }
        return;
    }

    if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
        if (window.SAE && typeof window.SAE.toast === "function") {
            window.SAE.toast("Silakan pilih berkas foto PNG terlebih dahulu.", "warning");
        }
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
                "Accept": "application/json",
            },
            body: formData,
        });

        const json = await res.json();

        if (json.status === "success") {
            if (window.SAE && typeof window.SAE.toast === "function") {
                const savingsTxt = json.data.savings ? ` (Hemat ${json.data.savings}%)` : "";
                window.SAE.toast(`Foto ${json.data.nama || "peserta didik"} berhasil disimpan! Ukuran: ${json.data.foto_size}${savingsTxt}`, "success");
            }

            updateRowFotoUI(pdId, json.data.foto_url, json.data.foto_size);
            window.closeUploadFotoModal();
        } else {
            throw new Error(json.message || "Gagal mengunggah foto.");
        }
    } catch (err) {
        if (window.SAE && typeof window.SAE.toast === "function") {
            window.SAE.toast(err.message || "Terjadi kesalahan saat mengunggah foto.", "danger");
        }
    } finally {
        if (btnSubmit) {
            btnSubmit.disabled = false;
            btnSubmit.innerHTML = '<i class="fas fa-cloud-arrow-up me-1"></i> Simpan Foto';
        }
    }
};

window.handleDeleteFoto = async function () {
    const pdId = currentSelectedPdId;
    if (!pdId) return;

    const confirmed = window.SAE && typeof window.SAE.confirm === "function"
        ? await window.SAE.confirm(
              "Foto peserta didik ini akan dihapus permanen dari sistem.",
              "Hapus Pasfoto Peserta Didik?",
              "danger",
              "Ya, Hapus!",
              "Batal"
          )
        : confirm("Hapus foto peserta didik ini?");

    if (!confirmed) return;

    const btnDelete = document.getElementById("btnDeleteFoto");
    if (btnDelete) {
        btnDelete.disabled = true;
        btnDelete.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Menghapus...';
    }

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || "";

    try {
        const res = await fetch(
            "/dashboard/manajemen-data/peserta-didik-aktif/" + encodeURIComponent(pdId) + "/delete-foto",
            {
                method: "DELETE",
                headers: {
                    "X-CSRF-TOKEN": csrfToken,
                    "Accept": "application/json",
                },
            }
        );

        const json = await res.json();

        if (json.status === "success") {
            if (window.SAE && typeof window.SAE.toast === "function") {
                window.SAE.toast("Pasfoto peserta didik berhasil dihapus.", "success");
            }
            updateRowFotoUI(pdId, "", "");
            window.closeUploadFotoModal();
        } else {
            throw new Error(json.message || "Gagal menghapus foto.");
        }
    } catch (err) {
        if (window.SAE && typeof window.SAE.toast === "function") {
            window.SAE.toast(err.message || "Terjadi kesalahan saat menghapus foto.", "danger");
        }
    } finally {
        if (btnDelete) {
            btnDelete.disabled = false;
            btnDelete.innerHTML = '<i class="fas fa-trash-can me-1"></i> Hapus Foto';
        }
    }
};

// Update tampilan DOM di tabel tanpa refresh halaman
function updateRowFotoUI(id, fotoUrl, fotoSize) {
    const thumbContainer = document.getElementById("pdFotoThumb_" + id);
    const badgeContainer = document.getElementById("pdFotoBadge_" + id);

    if (thumbContainer) {
        if (fotoUrl) {
            thumbContainer.className = "pd-foto-thumb";
            thumbContainer.style.background = "repeating-conic-gradient(#2a3447 0% 25%, #182030 0% 50%) 50% / 8px 8px";
            thumbContainer.style.border = "1.5px solid var(--border-color)";
            thumbContainer.style.color = "";
            thumbContainer.title = "Klik untuk melihat / mengubah pasfoto peserta didik";
            thumbContainer.innerHTML = `
                <img src="${fotoUrl}?v=${Date.now()}"
                     alt="Foto Peserta Didik"
                     style="width: 100%; height: 100%; object-fit: cover;">`;
        } else {
            thumbContainer.className = "pd-foto-thumb empty";
            thumbContainer.style.background = "rgba(99,102,241,0.08)";
            thumbContainer.style.border = "1.5px dashed rgba(99,102,241,0.4)";
            thumbContainer.style.color = "var(--primary)";
            thumbContainer.title = "Klik untuk mengunggah pasfoto peserta didik (PNG)";
            thumbContainer.innerHTML = `
                <i class="fas fa-camera" style="font-size: 0.82rem;"></i>
                <span style="font-size: 0.52rem; font-weight: 800; letter-spacing: 0.5px; margin-top: 2px;">PNG</span>`;
        }
    }

    if (badgeContainer) {
        badgeContainer.style.whiteSpace = "nowrap";
        if (fotoUrl) {
            badgeContainer.className = "badge";
            badgeContainer.style.background = "rgba(16,185,129,0.12)";
            badgeContainer.style.color = "#10b981";
            badgeContainer.style.padding = "2px 6px";
            badgeContainer.title = `Foto PNG tersimpan persisten (${fotoSize || ''})`;
            badgeContainer.innerHTML = `<i class="fas fa-check-circle me-1"></i>PNG ${fotoSize || ''}`;
        } else {
            badgeContainer.className = "badge badge-outline";
            badgeContainer.style.background = "";
            badgeContainer.style.color = "var(--text-muted)";
            badgeContainer.style.opacity = "0.7";
            badgeContainer.style.padding = "1px 5px";
            badgeContainer.title = "";
            badgeContainer.textContent = "Belum ada foto";
        }
    }

    // Juga update di bioFotoContainer jika modal biodata sedang dibuka
    const bioFotoBox = document.getElementById("bioFotoContainer");
    if (bioFotoBox) {
        if (fotoUrl) {
            bioFotoBox.innerHTML = `<img src="${fotoUrl}?v=${Date.now()}" style="width: 100%; height: 100%; object-fit: cover;">`;
        } else {
            bioFotoBox.innerHTML = `<i class="fas fa-user-graduate text-primary" style="font-size: 1.3rem;"></i>`;
        }
    }
}

// ==========================================
// 3. EVENT LISTENERS & FILTER / PAGINATION
// ==========================================
document.addEventListener("DOMContentLoaded", function () {
    const bioModal = document.getElementById("biodataModal");
    if (bioModal) {
        bioModal.addEventListener("click", function (e) {
            if (e.target === bioModal) window.closeBiodataModal();
        });
    }

    const fotoModal = document.getElementById("fotoUploadModal");
    if (fotoModal) {
        fotoModal.addEventListener("click", function (e) {
            if (e.target === fotoModal) window.closeUploadFotoModal();
        });
    }

    document.addEventListener("keydown", function (e) {
        if (e.key === "Escape") {
            window.closeBiodataModal();
            window.closeUploadFotoModal();
        }
    });

    // File input & Drag Drop handlers for Foto
    const fileInput = document.getElementById("fotoFileInput");
    if (fileInput) {
        fileInput.addEventListener("change", function (e) {
            if (e.target.files && e.target.files.length > 0) {
                handleFilePreview(e.target.files[0]);
            }
        });
    }

    const dropZone = document.getElementById("fotoDropZone");
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
            e.preventDefault();
            e.stopPropagation();
            dropZone.style.borderColor = "rgba(99,102,241,0.4)";
            dropZone.style.background = "rgba(255,255,255,0.01)";

            const files = e.dataTransfer?.files;
            if (files && files.length > 0) {
                try {
                    fileInput.files = files;
                } catch (err) {
                    const dt = new DataTransfer();
                    dt.items.add(files[0]);
                    fileInput.files = dt.files;
                }
                handleFilePreview(files[0]);
            }
        });
    }

    const previewContainer = document.getElementById("fotoPreviewContainer");
    if (previewContainer) {
        ["dragenter", "dragover"].forEach((eventName) => {
            previewContainer.addEventListener(eventName, function (e) {
                e.preventDefault();
                e.stopPropagation();
                previewContainer.style.borderColor = "#6366f1";
                previewContainer.style.boxShadow = "0 0 16px rgba(99,102,241,0.45)";
            });
        });

        ["dragleave", "drop"].forEach((eventName) => {
            previewContainer.addEventListener(eventName, function (e) {
                e.preventDefault();
                e.stopPropagation();
                previewContainer.style.borderColor = "var(--border-color)";
                previewContainer.style.boxShadow = "0 4px 16px rgba(0,0,0,0.35)";
            });
        });

        previewContainer.addEventListener("drop", function (e) {
            e.preventDefault();
            e.stopPropagation();
            previewContainer.style.borderColor = "var(--border-color)";
            previewContainer.style.boxShadow = "0 4px 16px rgba(0,0,0,0.35)";

            const files = e.dataTransfer?.files;
            if (files && files.length > 0) {
                try {
                    fileInput.files = files;
                } catch (err) {
                    const dt = new DataTransfer();
                    dt.items.add(files[0]);
                    fileInput.files = dt.files;
                }
                handleFilePreview(files[0]);
            }
        });
    }

    const searchInput = document.getElementById("liveSearch");
    const clearBtn = document.getElementById("clearSearch");
    const perPageSelect = document.getElementById("perPageSelect");
    const filterRombel = document.getElementById("filterRombel");
    const filterGender = document.getElementById("filterGender");

    function applyFilter() {
        const url = new URL(window.location.href);
        if (searchInput && searchInput.value.trim()) {
            url.searchParams.set("q", searchInput.value.trim());
        } else {
            url.searchParams.delete("q");
        }

        if (filterRombel && filterRombel.value) {
            url.searchParams.set("rombel", filterRombel.value);
        } else {
            url.searchParams.delete("rombel");
        }

        if (filterGender && filterGender.value) {
            url.searchParams.set("gender", filterGender.value);
        } else {
            url.searchParams.delete("gender");
        }

        if (perPageSelect && perPageSelect.value) {
            url.searchParams.set("perPage", perPageSelect.value);
        }

        url.searchParams.set("page", "1");
        window.location.href = url.toString();
    }

    if (searchInput) {
        let timer = null;
        searchInput.addEventListener("input", function () {
            if (clearBtn)
                clearBtn.classList.toggle(
                    "visible",
                    this.value.trim().length > 0,
                );
            clearTimeout(timer);
            timer = setTimeout(applyFilter, 500);
        });
    }

    if (clearBtn) {
        clearBtn.addEventListener("click", function () {
            if (searchInput) {
                searchInput.value = "";
                clearBtn.classList.remove("visible");
                applyFilter();
            }
        });
    }

    if (filterRombel) filterRombel.addEventListener("change", applyFilter);
    if (filterGender) filterGender.addEventListener("change", applyFilter);
    if (perPageSelect) perPageSelect.addEventListener("change", applyFilter);

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
            window.location.href = url.toString();
        });
    });
});

function escapeHtml(str) {
    if (str === null || str === undefined) return "";
    return String(str)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

// ==========================================================================
// 4. BULK / BATCH UPLOAD PASFOTO PESERTA DIDIK PER KELAS (36 - 52 PESERTA DIDIK)
// ==========================================================================
let bulkStudents = [];
let bulkFileMatches = {}; // { peserta_didik_id: { file: File, previewUrl: string, matchedBy: string } }
let isBulkUploading = false;

window.openBulkUploadFotoModal = function (defaultRombel) {
    const modal = document.getElementById("bulkFotoUploadModal");
    if (!modal) return;

    modal.style.display = "flex";

    const select = document.getElementById("bulkRombelSelect");
    if (select) {
        if (defaultRombel) {
            select.value = defaultRombel;
            handleBulkRombelChange(defaultRombel);
        } else if (select.value) {
            handleBulkRombelChange(select.value);
        }
    }
};

window.closeBulkUploadFotoModal = function () {
    if (isBulkUploading) {
        const leave = confirm("Proses unggah masal sedang berlangsung. Yakin ingin menutup?");
        if (!leave) return;
    }

    const modal = document.getElementById("bulkFotoUploadModal");
    if (modal) modal.style.display = "none";
};

window.handleBulkRombelChange = async function (rombelName) {
    bulkStudents = [];
    bulkFileMatches = {};

    const statsBadge = document.getElementById("bulkRombelStatsBadge");
    const dropzoneWrap = document.getElementById("bulkDropZoneWrapper");
    const summaryBanner = document.getElementById("bulkMatchSummaryBanner");
    const progressWrap = document.getElementById("bulkProgressContainer");
    const loadingSpinner = document.getElementById("bulkLoadingSpinner");
    const emptyState = document.getElementById("bulkEmptyState");
    const tableWrap = document.getElementById("bulkTableWrapper");
    const btnStart = document.getElementById("btnStartBulkUpload");

    if (summaryBanner) summaryBanner.style.display = "none";
    if (progressWrap) progressWrap.style.display = "none";
    if (btnStart) {
        btnStart.disabled = true;
        document.getElementById("btnStartBulkUploadText").textContent = "Mulai Unggah & Kompresi Masal (0 Foto)";
    }

    if (!rombelName) {
        if (statsBadge) statsBadge.style.display = "none";
        if (dropzoneWrap) dropzoneWrap.style.display = "none";
        if (tableWrap) tableWrap.style.display = "none";
        if (emptyState) emptyState.style.display = "block";
        return;
    }

    if (emptyState) emptyState.style.display = "none";
    if (tableWrap) tableWrap.style.display = "none";
    if (dropzoneWrap) dropzoneWrap.style.display = "none";
    if (loadingSpinner) loadingSpinner.style.display = "block";

    try {
        const res = await fetch(
            "/dashboard/manajemen-data/peserta-didik-aktif/rombel-members?rombel=" + encodeURIComponent(rombelName),
            {
                headers: { Accept: "application/json" },
            }
        );
        const json = await res.json();

        if (json.status === "success") {
            bulkStudents = json.data || [];

            // Update statistik rombel
            const totalPdEl = document.getElementById("bulkTotalPdCount");
            if (totalPdEl) totalPdEl.textContent = json.total || 0;
            document.getElementById("bulkSudahFotoCount").textContent = json.total_with_foto || 0;
            document.getElementById("bulkBelumFotoCount").textContent = json.total_without_foto || 0;
            if (statsBadge) statsBadge.style.display = "flex";

            if (dropzoneWrap) dropzoneWrap.style.display = "block";
            if (tableWrap) tableWrap.style.display = "block";

            renderBulkStudentsTable();
        } else {
            throw new Error(json.message || "Gagal memuat peserta didik rombel.");
        }
    } catch (err) {
        if (window.SAE && typeof window.SAE.toast === "function") {
            window.SAE.toast(err.message || "Gagal memuat peserta didik.", "danger");
        } else {
            alert(err.message);
        }
    } finally {
        if (loadingSpinner) loadingSpinner.style.display = "none";
    }
};

function renderBulkStudentsTable() {
    const tbody = document.getElementById("bulkStudentsTableBody");
    if (!tbody) return;

    if (bulkStudents.length === 0) {
        tbody.innerHTML = `<tr><td colspan="7" style="padding: 30px; text-align: center; color: var(--text-muted);">Tidak ada peserta didik di kelas ini.</td></tr>`;
        return;
    }

    let html = "";
    bulkStudents.forEach(function (s) {
        const match = bulkFileMatches[s.peserta_didik_id];
        const hasExisting = Boolean(s.foto_url);

        // Thumbnail foto lama
        let oldThumbHtml = "";
        if (hasExisting) {
            oldThumbHtml = `<div style="width: 38px; height: 38px; border-radius: 8px; border: 1px solid var(--border-color); overflow: hidden; background: #182030; margin: 0 auto;">
                <img src="${s.foto_url}" alt="Foto" style="width: 100%; height: 100%; object-fit: cover;">
            </div>`;
        } else {
            oldThumbHtml = `<div style="width: 38px; height: 38px; border-radius: 8px; border: 1px dashed rgba(255,255,255,0.15); display: flex; align-items: center; justify-content: center; color: var(--text-muted); font-size: 0.7rem; margin: 0 auto;">
                <i class="fas fa-user-slash"></i>
            </div>`;
        }

        // Berkas Baru & Status
        let matchInfoHtml = "";
        let previewHtml = "";
        let statusBadgeHtml = "";
        let actionBtnHtml = "";

        if (match && match.file) {
            const sizeStr = formatBytes(match.file.size);
            matchInfoHtml = `
                <div style="font-weight: 600; color: var(--primary); word-break: break-all;">${escapeHtml(match.file.name)}</div>
                <div style="font-size: 0.72rem; color: var(--text-muted);">${sizeStr} • Cocok via ${escapeHtml(match.matchedBy)}</div>
            `;
            previewHtml = `
                <div style="width: 38px; height: 50px; border-radius: 6px; border: 1.5px solid var(--primary); overflow: hidden; background: #000; margin: 0 auto; box-shadow: 0 2px 8px rgba(99,102,241,0.3);">
                    <img src="${match.previewUrl}" alt="Preview" style="width: 100%; height: 100%; object-fit: cover;">
                </div>
            `;
            statusBadgeHtml = `<span class="badge badge-success" style="font-size: 0.72rem; padding: 3px 8px;"><i class="fas fa-check me-1"></i> Siap Unggah</span>`;
            actionBtnHtml = `
                <button type="button" class="btn-icon" onclick="removeBulkStudentMatch('${s.peserta_didik_id}')" title="Batalkan berkas ini" style="color: var(--danger); width: 32px; height: 32px;">
                    <i class="fas fa-times"></i>
                </button>
            `;
        } else {
            matchInfoHtml = `<span style="color: var(--text-muted); font-style: italic; font-size: 0.78rem;">Belum ada berkas yang dicocokkan</span>`;
            previewHtml = `<span style="color: var(--text-muted); font-size: 0.75rem;">-</span>`;
            statusBadgeHtml = hasExisting
                ? `<span class="badge badge-outline" style="font-size: 0.7rem; color: var(--text-muted);">Tetap Foto Lama</span>`
                : `<span class="badge" style="background: rgba(239,68,68,0.1); color: #ef4444; font-size: 0.7rem;">Belum Ada</span>`;
            actionBtnHtml = `
                <label for="manualInput_${s.peserta_didik_id}" class="btn-icon" title="Pilih foto manual untuk peserta didik ini" style="cursor: pointer; width: 32px; height: 32px;">
                    <i class="fas fa-folder-open"></i>
                </label>
                <input type="file" id="manualInput_${s.peserta_didik_id}" accept="image/png" style="display: none;" onchange="handleManualSingleFile(this, '${s.peserta_didik_id}')">
            `;
        }

        html += `
            <tr id="bulkRow_${s.peserta_didik_id}" style="border-bottom: 1px solid var(--border-color); transition: background 0.2s ease;">
                <td style="padding: 10px 12px; text-align: center; font-weight: 700; color: var(--text-muted);">${s.no_urut}</td>
                <td style="padding: 8px 12px; text-align: center;">${oldThumbHtml}</td>
                <td style="padding: 10px 12px;">
                    <div style="font-weight: 700; color: var(--text-color); font-size: 0.86rem;">${escapeHtml(s.nama)}</div>
                    <div style="font-size: 0.72rem; color: var(--text-muted); font-family: monospace;">NISN: ${escapeHtml(s.nisn || "-")} ${s.nipd ? "• NIPD: " + escapeHtml(s.nipd) : ""}</div>
                </td>
                <td style="padding: 10px 12px;">${matchInfoHtml}</td>
                <td style="padding: 8px 12px; text-align: center;">${previewHtml}</td>
                <td style="padding: 10px 12px; text-align: center;" id="bulkStatusCell_${s.peserta_didik_id}">${statusBadgeHtml}</td>
                <td style="padding: 10px 12px; text-align: right;">${actionBtnHtml}</td>
            </tr>
        `;
    });

    tbody.innerHTML = html;
    updateBulkSummaryUI();
}

window.handleBulkFilesSelection = function (files) {
    if (!files || files.length === 0) return;
    if (bulkStudents.length === 0) {
        if (window.SAE && typeof window.SAE.toast === "function") {
            window.SAE.toast("Pilih kelas terlebih dahulu sebelum memilih berkas foto!", "warning");
        }
        return;
    }

    let newlyMatched = 0;
    let nonPngCount = 0;

    // Normalisasi string bantu
    const cleanStr = (str) =>
        String(str || "")
            .toLowerCase()
            .replace(/[^a-z0-9]/g, "");

    Array.from(files).forEach(function (file) {
        // Cek ekstensi dan MIME
        const ext = file.name.split(".").pop().toLowerCase();
        if (ext !== "png" && file.type !== "image/png" && file.type !== "image/x-png") {
            nonPngCount++;
            return;
        }

        const rawFilename = file.name;
        const nameWithoutExt = rawFilename.substring(0, rawFilename.lastIndexOf("."));
        const cleanFile = cleanStr(nameWithoutExt);

        let matchedStudent = null;
        let matchedBy = "";

        // 1. Cek kecocokan NISN
        for (const s of bulkStudents) {
            if (s.nisn && cleanStr(s.nisn) && cleanFile.includes(cleanStr(s.nisn))) {
                matchedStudent = s;
                matchedBy = "NISN";
                break;
            }
        }

        // 2. Cek kecocokan NIPD
        if (!matchedStudent) {
            for (const s of bulkStudents) {
                if (s.nipd && cleanStr(s.nipd) && cleanFile.includes(cleanStr(s.nipd))) {
                    matchedStudent = s;
                    matchedBy = "NIPD";
                    break;
                }
            }
        }

        // 3. Cek kecocokan NIK
        if (!matchedStudent) {
            for (const s of bulkStudents) {
                if (s.nik && cleanStr(s.nik) && cleanFile.includes(cleanStr(s.nik))) {
                    matchedStudent = s;
                    matchedBy = "NIK";
                    break;
                }
            }
        }

        // 4. Cek kecocokan Nama Lengkap
        if (!matchedStudent) {
            for (const s of bulkStudents) {
                const sNameClean = cleanStr(s.nama);
                if (sNameClean.length >= 4 && (cleanFile.includes(sNameClean) || sNameClean.includes(cleanFile))) {
                    matchedStudent = s;
                    matchedBy = "Nama Peserta Didik";
                    break;
                }
            }
        }

        // 5. Cek kecocokan Nomor Urut Absen (contoh: 01.png, 02.png, 1.png, atau prefix '01_...')
        if (!matchedStudent) {
            const numMatch = nameWithoutExt.match(/^0*(\d{1,2})([_\s-]|$)/);
            if (numMatch) {
                const numVal = parseInt(numMatch[1], 10);
                if (numVal >= 1 && numVal <= bulkStudents.length) {
                    matchedStudent = bulkStudents[numVal - 1];
                    matchedBy = "No. Urut " + numVal;
                }
            }
        }

        if (matchedStudent) {
            const previewUrl = URL.createObjectURL(file);
            bulkFileMatches[matchedStudent.peserta_didik_id] = {
                file: file,
                previewUrl: previewUrl,
                matchedBy: matchedBy,
            };
            newlyMatched++;
        }
    });

    renderBulkStudentsTable();

    if (nonPngCount > 0) {
        if (window.SAE && typeof window.SAE.toast === "function") {
            window.SAE.toast(`${nonPngCount} file diabaikan karena bukan format PNG.`, "warning");
        }
    }

    if (newlyMatched > 0) {
        if (window.SAE && typeof window.SAE.toast === "function") {
            window.SAE.toast(`${newlyMatched} pasfoto berhasil dipetakan ke peserta didik di kelas ini!`, "success");
        }
    } else {
        if (window.SAE && typeof window.SAE.toast === "function") {
            window.SAE.toast("Tidak ada nama berkas yang cocok dengan NISN/Nama/No Urut peserta didik kelas ini.", "warning");
        }
    }
};

window.handleManualSingleFile = function (input, pdId) {
    if (!input || !input.files || !input.files[0]) return;
    const file = input.files[0];

    const ext = file.name.split(".").pop().toLowerCase();
    if (ext !== "png" && file.type !== "image/png") {
        if (window.SAE && typeof window.SAE.toast === "function") {
            window.SAE.toast("Berkas wajib format PNG (.png).", "warning");
        }
        input.value = "";
        return;
    }

    const previewUrl = URL.createObjectURL(file);
    bulkFileMatches[pdId] = {
        file: file,
        previewUrl: previewUrl,
        matchedBy: "Manual",
    };

    renderBulkStudentsTable();
};

window.removeBulkStudentMatch = function (pdId) {
    if (bulkFileMatches[pdId]) {
        delete bulkFileMatches[pdId];
        renderBulkStudentsTable();
    }
};

window.resetBulkMatches = function () {
    bulkFileMatches = {};
    const input = document.getElementById("bulkFilesInput");
    if (input) input.value = "";
    renderBulkStudentsTable();
};

function updateBulkSummaryUI() {
    const summaryBanner = document.getElementById("bulkMatchSummaryBanner");
    const summaryText = document.getElementById("bulkMatchSummaryText");
    const filesBadge = document.getElementById("bulkFilesCountBadge");
    const btnReset = document.getElementById("btnClearBulkMatches");
    const btnStart = document.getElementById("btnStartBulkUpload");
    const btnText = document.getElementById("btnStartBulkUploadText");

    const matchedCount = Object.keys(bulkFileMatches).length;
    const totalCount = bulkStudents.length;

    if (matchedCount > 0) {
        if (summaryBanner) summaryBanner.style.display = "flex";
        if (summaryText) {
            summaryText.innerHTML = `<strong>${matchedCount} dari ${totalCount} peserta didik</strong> berhasil dipasangkan berkas foto PNG.`;
        }
        if (filesBadge) filesBadge.textContent = `${matchedCount} Foto Siap`;
        if (btnReset) btnReset.style.display = "inline-flex";
        if (btnStart) btnStart.disabled = false;
        if (btnText) btnText.textContent = `Mulai Unggah & Kompresi Masal (${matchedCount} Foto)`;
    } else {
        if (summaryBanner) summaryBanner.style.display = "none";
        if (btnReset) btnReset.style.display = "none";
        if (btnStart) btnStart.disabled = true;
        if (btnText) btnText.textContent = `Mulai Unggah & Kompresi Masal (0 Foto)`;
    }
}

// Queue Engine: Unggah dan kompresi secara paralel (concurrency 2)
window.executeBulkUploadQueue = async function () {
    const matchedEntries = Object.entries(bulkFileMatches);
    if (matchedEntries.length === 0) return;

    isBulkUploading = true;

    const btnStart = document.getElementById("btnStartBulkUpload");
    const progressWrap = document.getElementById("bulkProgressContainer");
    const progressBar = document.getElementById("bulkProgressBar");
    const progressPercent = document.getElementById("bulkProgressPercent");
    const progressDetail = document.getElementById("bulkProgressDetail");
    const progressTitle = document.getElementById("bulkProgressTitle");

    if (btnStart) btnStart.disabled = true;
    if (progressWrap) progressWrap.style.display = "block";

    let completed = 0;
    let failed = 0;
    const total = matchedEntries.length;
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || "";

    const updateProgress = () => {
        const pct = Math.round((completed / total) * 100);
        if (progressBar) progressBar.style.width = pct + "%";
        if (progressPercent) progressPercent.textContent = pct + "%";
        if (progressDetail) {
            progressDetail.textContent = `${completed} dari ${total} foto diproses (${failed} gagal)`;
        }
    };

    // Worker pengunggah tunggal
    const uploadSingle = async ([pdId, match]) => {
        const statusCell = document.getElementById("bulkStatusCell_" + pdId);
        if (statusCell) {
            statusCell.innerHTML = `<span class="badge" style="background: rgba(99,102,241,0.15); color: var(--primary); font-size: 0.72rem;"><i class="fas fa-spinner fa-spin me-1"></i> Mengompresi...</span>`;
        }

        const formData = new FormData();
        formData.append("peserta_didik_id", pdId);
        formData.append("foto", match.file);

        try {
            const res = await fetch("/dashboard/manajemen-data/peserta-didik-aktif/upload-foto", {
                method: "POST",
                headers: {
                    "X-CSRF-TOKEN": csrfToken,
                    "Accept": "application/json",
                },
                body: formData,
            });

            const json = await res.json();
            if (json.status === "success") {
                if (statusCell) {
                    const savingsStr = json.data.savings ? ` (Hemat ${json.data.savings}%)` : "";
                    statusCell.innerHTML = `<span class="badge badge-success" style="font-size: 0.72rem; padding: 3px 8px;" title="${json.data.foto_size}${savingsStr}"><i class="fas fa-check-double me-1"></i> ${json.data.foto_size}</span>`;
                }
                // Update baris tabel utama jika ada
                updateRowFotoUI(pdId, json.data.foto_url, json.data.foto_size);
            } else {
                throw new Error(json.message || "Gagal");
            }
        } catch (err) {
            failed++;
            if (statusCell) {
                statusCell.innerHTML = `<span class="badge badge-danger" style="font-size: 0.72rem; padding: 3px 8px;" title="${escapeHtml(err.message)}"><i class="fas fa-exclamation-triangle me-1"></i> Gagal</span>`;
            }
        } finally {
            completed++;
            updateProgress();
        }
    };

    // Eksekusi antrean dengan concurrency = 2
    const concurrency = 2;
    const queue = [...matchedEntries];
    const workers = [];

    for (let i = 0; i < concurrency; i++) {
        workers.push(
            (async () => {
                while (queue.length > 0) {
                    const item = queue.shift();
                    await uploadSingle(item);
                }
            })()
        );
    }

    await Promise.all(workers);

    isBulkUploading = false;
    if (progressTitle) {
        progressTitle.innerHTML = `<i class="fas fa-circle-check text-success me-1"></i> Selesai! ${completed - failed} foto berhasil disimpan &amp; dikompresi.`;
    }

    if (window.SAE && typeof window.SAE.toast === "function") {
        window.SAE.toast(
            `Unggah masal selesai! ${completed - failed} pasfoto berhasil dikompresi dan disimpan.${failed > 0 ? " (" + failed + " berkas gagal)" : ""}`,
            failed > 0 ? "warning" : "success"
        );
    }

    if (btnStart) {
        btnStart.disabled = false;
        document.getElementById("btnStartBulkUploadText").textContent = "Selesai";
    }
};

// Pasang event listener dropzone masal
document.addEventListener("DOMContentLoaded", function () {
    const bulkDropZone = document.getElementById("bulkDropZone");
    const bulkFilesInput = document.getElementById("bulkFilesInput");

    if (bulkDropZone && bulkFilesInput) {
        bulkDropZone.addEventListener("dragover", function (e) {
            e.preventDefault();
            bulkDropZone.style.borderColor = "var(--primary)";
            bulkDropZone.style.background = "rgba(99,102,241,0.12)";
        });

        bulkDropZone.addEventListener("dragleave", function () {
            bulkDropZone.style.borderColor = "rgba(99,102,241,0.45)";
            bulkDropZone.style.background = "rgba(99,102,241,0.03)";
        });

        bulkDropZone.addEventListener("drop", function (e) {
            e.preventDefault();
            bulkDropZone.style.borderColor = "rgba(99,102,241,0.45)";
            bulkDropZone.style.background = "rgba(99,102,241,0.03)";
            if (e.dataTransfer.files && e.dataTransfer.files.length > 0) {
                handleBulkFilesSelection(e.dataTransfer.files);
            }
        });

        bulkFilesInput.addEventListener("change", function () {
            if (this.files && this.files.length > 0) {
                handleBulkFilesSelection(this.files);
            }
        });
    }
});

