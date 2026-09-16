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
});
