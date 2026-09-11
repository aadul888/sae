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
            window.SAE.toast("Wajib format PNG (.png) untuk pasfoto kartu pelajar!", "warning");
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
            window.SAE.toast("Ukuran file terlalu besar! Maksimal 5 MB.", "danger");
        } else {
            alert("Ukuran file maksimal 5 MB.");
        }
        const fileInput = document.getElementById("fotoFileInput");
        if (fileInput) fileInput.value = "";
        return;
    }

    const reader = new FileReader();
    reader.onload = function (e) {
        const previewImg = document.getElementById("fotoPreviewImg");
        const placeholder = document.getElementById("fotoPreviewPlaceholder");
        const specs = document.getElementById("fotoFileSpecs");

        if (previewImg) {
            previewImg.src = e.target.result;
            previewImg.style.display = "block";
        }
        if (placeholder) placeholder.style.display = "none";

        const img = new Image();
        img.onload = function () {
            if (specs) {
                specs.style.display = "block";
                specs.innerHTML = `<i class="fas fa-file-image me-1"></i> ${img.width} &times; ${img.height} px • ${formatBytes(file.size)} (Siap dikompresi)`;
            }
        };
        img.src = e.target.result;
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
            const dt = e.dataTransfer;
            const files = dt.files;
            if (files && files.length > 0) {
                fileInput.files = files;
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
