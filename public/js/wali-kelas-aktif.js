/**
 * Wali Kelas — Peserta Didik Aktif
 * Modular JavaScript untuk Datatable Baku SAE, Live Search Asinkron, Sortable Header, dan Modal Detail
 */

document.addEventListener("DOMContentLoaded", function () {
    const modalDetail = document.getElementById("modalDetailSiswa");
    const modalBody = document.getElementById("modalDetailBody");
    const btnCloseModal = document.getElementById("btnCloseModalDetail");
    const btnTutupModal = document.getElementById("btnTutupModal");

    const perPageSelect = document.getElementById("perPageSelect");
    const filterGender = document.getElementById("filterGender");
    const adminRombelSelect = document.getElementById("adminRombelSelect");
    const liveSearchInput = document.getElementById("liveSearchInput");
    const clearSearchBtn = document.getElementById("clearSearchBtn");

    // Fungsi pusat penyegaran tabel asinkron (AJAX)
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

    // Modal Helpers
    function closeModal() {
        if (modalDetail) {
            modalDetail.style.display = "none";
            document.body.style.overflow = "";
        }
    }

    function openModal() {
        if (modalDetail) {
            modalDetail.style.display = "flex";
            document.body.style.overflow = "hidden";
        }
    }

    if (btnCloseModal) btnCloseModal.addEventListener("click", closeModal);
    if (btnTutupModal) btnTutupModal.addEventListener("click", closeModal);

    if (modalDetail) {
        modalDetail.addEventListener("click", function (e) {
            if (e.target === modalDetail) {
                closeModal();
            }
        });
    }

    document.addEventListener("keydown", function (e) {
        if (e.key === "Escape" && modalDetail && modalDetail.style.display === "flex") {
            closeModal();
        }
    });

    function escapeHtml(str) {
        if (!str) return "-";
        return String(str)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    // Event Delegation: Tombol Detail Siswa (Bekerja otomatis bahkan setelah table refreshed)
    document.addEventListener("click", async function (e) {
        const btn = e.target.closest(".btn-detail-siswa");
        if (!btn) return;

        const id = btn.getAttribute("data-id");
        if (!id) return;

        openModal();
        modalBody.innerHTML = `
            <div style="text-align: center; padding: 40px; color: var(--text-muted);">
                <i class="fas fa-spinner fa-spin fa-2x" style="color: var(--primary);"></i>
                <p style="margin-top: 12px; font-size: 0.9rem;">Mengambil biodata siswa...</p>
            </div>
        `;

        try {
            const res = await fetch(`/dashboard/wali-kelas/peserta-didik/${encodeURIComponent(id)}`, {
                headers: {
                    Accept: "application/json",
                    "X-Requested-With": "XMLHttpRequest",
                },
            });

            const json = await res.json();
            if (!json.success || !json.data) {
                throw new Error(json.message || "Gagal memuat data peserta didik.");
            }

            const d = json.data;

            const fotoHtml = d.foto_url
                ? `<img src="${d.foto_url}" alt="${escapeHtml(d.nama)}" style="width: 100px; height: 120px; object-fit: cover; border-radius: 8px; border: 2px solid var(--border-color); box-shadow: 0 4px 10px rgba(0,0,0,0.2);">`
                : `<div style="width: 100px; height: 120px; border-radius: 8px; background: rgba(99,102,241,0.1); color: var(--primary); display: flex; flex-direction: column; align-items: center; justify-content: center; border: 2px dashed var(--border-color);">
                    <i class="fas fa-user-graduate" style="font-size: 2.2rem; margin-bottom: 6px;"></i>
                    <span style="font-size: 0.7rem; font-weight: 600; text-transform: uppercase;">Tanpa Foto</span>
                   </div>`;

            const jkLabel = d.jenis_kelamin === "L" ? "Laki-Laki (L)" : d.jenis_kelamin === "P" ? "Perempuan (P)" : "-";
            const ttl = [d.tempat_lahir, d.tanggal_lahir].filter(Boolean).join(", ") || "-";

            modalBody.innerHTML = `
                <div style="display: flex; flex-wrap: wrap; gap: 24px; align-items: flex-start;">
                    <div style="flex: 0 0 110px; text-align: center;">
                        ${fotoHtml}
                        <div style="margin-top: 10px;">
                            <span class="badge" style="background: rgba(99,102,241,0.15); color: var(--primary); font-weight: 700; font-size: 0.72rem; padding: 4px 8px;">
                                ${escapeHtml(d.nama_rombel || "-")}
                            </span>
                        </div>
                    </div>

                    <div style="flex: 1; min-width: 280px;">
                        <h3 style="font-size: 1.2rem; font-weight: 800; color: var(--text-color); margin-bottom: 4px;">
                            ${escapeHtml(d.nama)}
                        </h3>
                        <div style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 16px;">
                            NISN: <strong style="color: var(--text-color); font-family: monospace;">${escapeHtml(d.nisn)}</strong> &bull; 
                            NIPD: <strong style="color: var(--text-color); font-family: monospace;">${escapeHtml(d.nipd)}</strong> &bull; 
                            NIK: <strong style="color: var(--text-color); font-family: monospace;">${escapeHtml(d.nik)}</strong>
                        </div>

                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px; font-size: 0.85rem;">
                            <div>
                                <div style="color: var(--text-muted); font-size: 0.75rem; text-transform: uppercase; font-weight: 600;">Jenis Kelamin</div>
                                <div style="color: var(--text-color); font-weight: 600;">${jkLabel}</div>
                            </div>
                            <div>
                                <div style="color: var(--text-muted); font-size: 0.75rem; text-transform: uppercase; font-weight: 600;">Tempat, Tanggal Lahir</div>
                                <div style="color: var(--text-color); font-weight: 600;">${escapeHtml(ttl)}</div>
                            </div>
                            <div>
                                <div style="color: var(--text-muted); font-size: 0.75rem; text-transform: uppercase; font-weight: 600;">Agama</div>
                                <div style="color: var(--text-color); font-weight: 600;">${escapeHtml(d.agama_id_str || d.agama || "-")}</div>
                            </div>
                            <div>
                                <div style="color: var(--text-muted); font-size: 0.75rem; text-transform: uppercase; font-weight: 600;">No. Telepon / HP</div>
                                <div style="color: var(--text-color); font-weight: 600;">
                                    <i class="fas fa-phone-alt me-1 text-primary" style="font-size: 0.75rem;"></i>
                                    ${escapeHtml(d.nomor_telepon_seluler || d.no_hp || "-")}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <hr style="border: 0; border-top: 1px solid var(--border-color); margin: 20px 0;">

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px; font-size: 0.85rem;">
                    <div style="background: rgba(0,0,0,0.15); padding: 14px; border-radius: 8px; border: 1px solid var(--border-color);">
                        <div style="font-weight: 700; color: var(--text-color); margin-bottom: 8px;">
                            <i class="fas fa-users text-primary me-2"></i> Biodata Orang Tua / Wali
                        </div>
                        <div style="margin-bottom: 6px;">
                            <span style="color: var(--text-muted);">Nama Ayah:</span>
                            <strong style="color: var(--text-color); margin-left: 4px;">${escapeHtml(d.nama_ayah)}</strong>
                        </div>
                        <div style="margin-bottom: 6px;">
                            <span style="color: var(--text-muted);">Nama Ibu:</span>
                            <strong style="color: var(--text-color); margin-left: 4px;">${escapeHtml(d.nama_ibu)}</strong>
                        </div>
                        <div>
                            <span style="color: var(--text-muted);">Nama Wali:</span>
                            <strong style="color: var(--text-color); margin-left: 4px;">${escapeHtml(d.nama_wali)}</strong>
                        </div>
                    </div>

                    <div style="background: rgba(0,0,0,0.15); padding: 14px; border-radius: 8px; border: 1px solid var(--border-color);">
                        <div style="font-weight: 700; color: var(--text-color); margin-bottom: 8px;">
                            <i class="fas fa-map-location-dot text-primary me-2"></i> Tempat Tinggal & Kontak
                        </div>
                        <div style="margin-bottom: 6px;">
                            <span style="color: var(--text-muted);">Alamat Jalan:</span>
                            <div style="color: var(--text-color); font-weight: 500; margin-top: 2px;">
                                ${escapeHtml(d.alamat_jalan || "Tidak ada rincian jalan")}
                            </div>
                        </div>
                        <div>
                            <span style="color: var(--text-muted);">Email:</span>
                            <strong style="color: var(--text-color); margin-left: 4px;">${escapeHtml(d.email)}</strong>
                        </div>
                    </div>
                </div>
            `;
        } catch (err) {
            if (typeof Swal !== "undefined") {
                Swal.fire({
                    icon: "error",
                    title: "Gagal Mengambil Data",
                    text: err.message || "Terjadi kesalahan saat memuat biodata siswa.",
                });
            } else {
                alert("Error: " + err.message);
            }
            closeModal();
        }
    });
});
