/**
 * Wali Kelas — Peserta Didik Tidak Aktif
 * Modular JavaScript untuk Datatable Baku SAE, Live Search Asinkron, Sortable Header, dan Modal Detail
 */

document.addEventListener("DOMContentLoaded", function () {
    const modalDetail = document.getElementById("modalDetailTidakAktif");
    const modalBody = document.getElementById("modalTidakAktifBody");
    const btnCloseModal = document.getElementById("btnCloseModalTidakAktif");
    const btnTutupModal = document.getElementById("btnTutupModalTidakAktif");

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

    // Event Delegation: Tombol Detail Siswa Tidak Aktif
    document.addEventListener("click", async function (e) {
        const btn = e.target.closest(".btn-detail-tidak-aktif");
        if (!btn) return;

        const id = btn.getAttribute("data-id");
        if (!id) return;

        openModal();
        modalBody.innerHTML = `
            <div style="text-align: center; padding: 40px; color: var(--text-muted);">
                <i class="fas fa-spinner fa-spin fa-2x" style="color: var(--primary);"></i>
                <p style="margin-top: 12px; font-size: 0.9rem;">Mengambil riwayat siswa keluar...</p>
            </div>
        `;

        try {
            const res = await fetch(`/dashboard/wali-kelas/peserta-didik-tidak-aktif/${encodeURIComponent(id)}`, {
                headers: {
                    Accept: "application/json",
                    "X-Requested-With": "XMLHttpRequest",
                },
            });

            const json = await res.json();
            if (!json.success || !json.data) {
                throw new Error(json.message || "Gagal memuat data riwayat siswa.");
            }

            const d = json.data;

            const fotoHtml = d.foto_url
                ? `<img src="${d.foto_url}" alt="${escapeHtml(d.nama)}" style="width: 100px; height: 120px; object-fit: cover; border-radius: 8px; border: 2px solid var(--border-color); box-shadow: 0 4px 10px rgba(0,0,0,0.2);">`
                : `<div style="width: 100px; height: 120px; border-radius: 8px; background: rgba(239,68,68,0.1); color: #ef4444; display: flex; flex-direction: column; align-items: center; justify-content: center; border: 2px dashed var(--border-color);">
                    <i class="fas fa-user-slash" style="font-size: 2.2rem; margin-bottom: 6px;"></i>
                    <span style="font-size: 0.7rem; font-weight: 600; text-transform: uppercase;">Tanpa Foto</span>
                   </div>`;

            const jkLabel = d.jenis_kelamin === "L" ? "Laki-Laki (L)" : d.jenis_kelamin === "P" ? "Perempuan (P)" : "-";
            const ttl = [d.tempat_lahir, d.tanggal_lahir].filter(Boolean).join(", ") || "-";

            const badgeStatus = d.status_keluar === "Alumni"
                ? `<span class="badge badge-success" style="font-weight: 700;">Alumni / Lulus</span>`
                : `<span class="badge badge-warning" style="font-weight: 700;">${escapeHtml(d.status_keluar || "Mutasi / Keluar")}</span>`;

            modalBody.innerHTML = `
                <div style="display: flex; flex-wrap: wrap; gap: 24px; align-items: flex-start;">
                    <div style="flex: 0 0 110px; text-align: center;">
                        ${fotoHtml}
                        <div style="margin-top: 10px;">
                            ${badgeStatus}
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
                                <div style="color: var(--text-muted); font-size: 0.75rem; text-transform: uppercase; font-weight: 600;">Rombel Terakhir</div>
                                <div style="color: var(--text-color); font-weight: 600;">${escapeHtml(d.nama_rombel_terakhir || "-")}</div>
                            </div>
                            <div>
                                <div style="color: var(--text-muted); font-size: 0.75rem; text-transform: uppercase; font-weight: 600;">Tahun / Tgl Keluar</div>
                                <div style="color: var(--text-color); font-weight: 600;">
                                    ${escapeHtml(d.tahun_lulus ? 'Tahun ' + d.tahun_lulus : '')} ${d.tanggal_keluar ? '(' + d.tanggal_keluar + ')' : ''}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <hr style="border: 0; border-top: 1px solid var(--border-color); margin: 20px 0;">

                <div style="background: rgba(0,0,0,0.15); padding: 16px; border-radius: 8px; border: 1px solid var(--border-color); font-size: 0.85rem;">
                    <div style="font-weight: 700; color: var(--text-color); margin-bottom: 8px;">
                        <i class="fas fa-circle-info text-primary me-2"></i> Keterangan & Alasan Keluar
                    </div>
                    <div style="color: var(--text-color); font-size: 0.9rem; line-height: 1.5; background: rgba(0,0,0,0.2); padding: 12px; border-radius: 6px; border-left: 3px solid var(--primary);">
                        ${escapeHtml(d.alasan_keluar || "Tidak ada rincian alasan keluar yang dicatat.")}
                    </div>

                    <div style="margin-top: 12px; display: flex; flex-wrap: wrap; gap: 16px; color: var(--text-muted); font-size: 0.8rem;">
                        <div>
                            <i class="fas fa-phone-alt me-1"></i> Kontak: 
                            <strong style="color: var(--text-color);">${escapeHtml(d.nomor_telepon_seluler || "-")}</strong>
                        </div>
                        <div>
                            <i class="fas fa-location-dot me-1"></i> Alamat: 
                            <strong style="color: var(--text-color);">${escapeHtml(d.alamat_jalan || "-")}</strong>
                        </div>
                    </div>
                </div>
            `;
        } catch (err) {
            if (typeof Swal !== "undefined") {
                Swal.fire({
                    icon: "error",
                    title: "Gagal Mengambil Data",
                    text: err.message || "Terjadi kesalahan saat memuat riwayat siswa.",
                });
            } else {
                alert("Error: " + err.message);
            }
            closeModal();
        }
    });
});
