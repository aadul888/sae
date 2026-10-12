/**
 * Wali Kelas — Peserta Didik Tidak Aktif
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
// 1. MODAL DETAIL BIODATA LENGKAP ARSIP PESERTA DIDIK
// ==========================================
window.openBiodataModal = function (id) {
    const modal = document.getElementById("biodataModal");
    const loading = document.getElementById("bioLoading");
    const content = document.getElementById("bioContent");

    if (!modal) return;

    modal.style.display = "flex";
    document.body.style.overflow = "hidden";
    if (loading) loading.style.display = "block";
    if (content) content.style.display = "none";

    fetch("/dashboard/wali-kelas/peserta-didik-tidak-aktif/" + encodeURIComponent(id), {
        headers: {
            Accept: "application/json",
            "X-Requested-With": "XMLHttpRequest",
        },
    })
        .then((res) => res.json())
        .then((res) => {
            if (loading) loading.style.display = "none";
            if ((res.status === "success" || res.success) && res.data) {
                const d = res.data;
                if (content) content.style.display = "block";

                const setText = (elemId, val) => {
                    const el = document.getElementById(elemId);
                    if (el) el.textContent = val || "-";
                };

                setText("bioNama", d.nama);
                setText(
                    "bioRombel",
                    d.nama_rombel_terakhir
                        ? d.nama_rombel_terakhir +
                              " • Tingkat " +
                              (d.tingkat_pendidikan_terakhir || "-")
                        : "-",
                );
                setText(
                    "bioNisn",
                    (d.nisn || "-") + (d.nipd ? " / " + d.nipd : ""),
                );
                setText("bioNik", d.nik);
                setText(
                    "bioJk",
                    d.jenis_kelamin === "L"
                        ? "Laki-Laki (L)"
                        : d.jenis_kelamin === "P"
                          ? "Perempuan (P)"
                          : "-",
                );
                setText(
                    "bioTtl",
                    [d.tempat_lahir, d.tanggal_lahir]
                        .filter(Boolean)
                        .join(", ") || "-",
                );
                setText("bioAgama", d.agama_id_str);

                const bioStatusEl = document.getElementById("bioStatus");
                if (bioStatusEl) {
                    const statusBadge =
                        d.status_keluar === "Alumni"
                            ? '<span class="badge" style="background: rgba(16,185,129,0.15); color: #10b981; font-weight: 700; padding: 3px 8px;"><i class="fas fa-graduation-cap me-1"></i>Alumni (Lulus)</span>'
                            : '<span class="badge" style="background: rgba(245,158,11,0.15); color: #f59e0b; font-weight: 700; padding: 3px 8px;"><i class="fas fa-right-from-bracket me-1"></i>' +
                              (d.status_keluar || "Mutasi") +
                              "</span>";
                    bioStatusEl.innerHTML = statusBadge;
                }

                setText("bioTahunLulus", d.tahun_lulus || "-");
                setText(
                    "bioRombelDetail",
                    [
                        d.nama_rombel_terakhir,
                        d.tingkat_pendidikan_terakhir
                            ? "Tingkat " + d.tingkat_pendidikan_terakhir
                            : null,
                    ]
                        .filter(Boolean)
                        .join(" • ") || "-",
                );
                setText("bioAlasan", d.alasan_keluar || "-");
                setText("bioHp", d.nomor_telepon_seluler || "-");
                setText("bioEmail", d.email || "-");
                setText("bioAlamat", d.alamat_jalan || "-");

                const fotoBox = document.getElementById("bioFotoContainer");
                if (fotoBox) {
                    if (d.foto_url) {
                        fotoBox.innerHTML = `<img src="${d.foto_url}" alt="Foto ${escapeHtml(d.nama)}" style="width: 100%; height: 100%; object-fit: cover;">`;
                    } else {
                        fotoBox.innerHTML = `<i class="fas fa-user-graduate text-primary" style="font-size: 1.3rem;"></i>`;
                    }
                }
            } else {
                throw new Error(res.message || "Gagal memuat arsip peserta didik.");
            }
        })
        .catch((err) => {
            if (loading) loading.style.display = "none";
            if (content) content.style.display = "block";
            const rombelEl = document.getElementById("bioRombel");
            if (rombelEl) rombelEl.textContent = err.message || "Gagal memuat rincian arsip.";
        });
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

// Event Delegation untuk tombol detail peserta didik tidak aktif
document.addEventListener("click", function (e) {
    const btn = e.target.closest(".btn-detail-tidak-aktif");
    if (!btn) return;
    const id = btn.getAttribute("data-id");
    if (id) {
        window.openBiodataModal(id);
    }
});

// ==========================================
// 1b. MODAL PREVIEW PASFOTO SISWA
// ==========================================
window.openPhotoPreviewModal = function (url, name) {
    const modal = document.getElementById("photoPreviewModal");
    const img = document.getElementById("photoPreviewImg");
    const nameEl = document.getElementById("photoPreviewName");
    if (!modal || !img) return;
    img.src = url;
    if (nameEl) nameEl.textContent = name || "Pasfoto Peserta Didik";
    modal.style.display = "flex";
    document.body.style.overflow = "hidden";
};

window.closePhotoPreviewModal = function () {
    const modal = document.getElementById("photoPreviewModal");
    if (modal) modal.style.display = "none";
    document.body.style.overflow = "";
};

// ==========================================
// 2. FILTER & LIVE TABLE ENGINE
// ==========================================
document.addEventListener("DOMContentLoaded", function () {
    const perPageSelect = document.getElementById("perPageSelect");
    const filterStatus = document.getElementById("filterStatus");
    const filterTahun = document.getElementById("filterTahun");
    const adminRombelSelect = document.getElementById("adminRombelSelect");
    const liveSearchInput = document.getElementById("liveSearchInput");
    const clearSearchBtn = document.getElementById("clearSearchBtn");

    function applyFilter(overrideParams = {}) {
        const url = new URL(window.location.href);

        if (perPageSelect) url.searchParams.set("perPage", perPageSelect.value);
        if (filterStatus) {
            if (filterStatus.value) url.searchParams.set("status", filterStatus.value);
            else url.searchParams.delete("status");
        }
        if (filterTahun) {
            if (filterTahun.value) url.searchParams.set("tahun", filterTahun.value);
            else url.searchParams.delete("tahun");
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
    if (filterStatus) filterStatus.addEventListener("change", () => applyFilter());
    if (filterTahun) filterTahun.addEventListener("change", () => applyFilter());
    if (adminRombelSelect) adminRombelSelect.addEventListener("change", () => applyFilter());

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
