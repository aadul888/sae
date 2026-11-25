/**
 * Manajemen Data — Tendik Aktif Frontend JS
 */

window.closeTendikModal = function () {
    const modal = document.getElementById("modalDetailTendik");
    if (modal) modal.style.display = "none";
};

/**
 * Simpan gelar Tendik lalu tutup modal (satu tombol "Simpan & Tutup").
 * Logika bersama ada di `public/js/gtk-gelar.js`.
 */
window.saveGelarTendik = async function () {
    const ptkId = window.currentTendikId;
    if (!ptkId) return;

    const ok = await window.saveGtkGelar({
        ptkId,
        endpoint:
            "/dashboard/manajemen-data/tendik-aktif/" +
            encodeURIComponent(ptkId) +
            "/gelar",
        btn: document.getElementById("tendikGelarSave"),
        getDepan: () => document.getElementById("tendikGelarDepan")?.value,
        getBelakang: () => document.getElementById("tendikGelarBelakang")?.value,
        onSuccess: (data) => {
            const lengkap = window.gtkNamaLengkap(
                window.currentTendikNama || "",
                data.gelar_depan || "",
                data.gelar_belakang || "",
            );

            const namaEl = document.getElementById("tendikJkNama");
            if (namaEl) namaEl.textContent = lengkap;

            const row = document.querySelector(
                'tr[data-ptk-id="' + (window.currentTendikId || "") + '"]',
            );
            const rowName = row?.querySelector(".gtk-nama-lengkap");
            if (rowName) rowName.textContent = lengkap;
        },
    });

    // Tombol tunggal: simpan lalu tutup.
    if (ok) window.closeTendikModal();
};

document.addEventListener("DOMContentLoaded", function () {
    const modal = document.getElementById("modalDetailTendik");
    const modalTitle = document.getElementById("tendikModalTitle");
    const modalSubtitle = document.getElementById("tendikModalSubtitle");
    const modalBody = document.getElementById("modalBodyTendik");

    if (modal) {
        modal.addEventListener("click", function (e) {
            if (e.target === modal) window.closeTendikModal();
        });
    }

    document.addEventListener("keydown", function (e) {
        if (e.key === "Escape") window.closeTendikModal();
    });

    // Detail Button (Event Delegation)
    document.addEventListener("click", async function (e) {
        const btn = e.target.closest(".btn-detail-tendik");
        if (!btn) return;
        const id = btn.getAttribute("data-id");
        if (!id || !modal || !modalBody) return;

        window.currentTendikId = id;
        window.currentTendikNama = "";

            if (modalTitle)
                modalTitle.textContent = "Profil Tenaga Kependidikan";
            if (modalSubtitle) modalSubtitle.textContent = "Memuat data...";
            modal.style.display = "flex";
            modalBody.innerHTML = `
                <div style="text-align: center; padding: 30px;">
                    <i class="fas fa-spinner fa-spin" style="font-size: 1.8rem; color: var(--primary);"></i>
                    <p style="margin-top: 10px; color: var(--text-muted);">Memuat data tenaga kependidikan...</p>
                </div>
            `;

            try {
                const res = await fetch(
                    `/dashboard/manajemen-data/tendik-aktif/${encodeURIComponent(id)}`,
                    {
                        headers: { Accept: "application/json" },
                    },
                );
                const json = await res.json();
                if (json.status === "success" && json.data) {
                    const d = json.data;
                    window.currentTendikNama = d.nama || "";
                    const namaLengkap = window.gtkNamaLengkap(
                        d.nama || "",
                        d.gelar_depan || "",
                        d.gelar_belakang || "",
                    );
                    if (modalTitle)
                        modalTitle.textContent = namaLengkap;
                    if (modalSubtitle)
                        modalSubtitle.textContent =
                            [
                                d.jabatan_ptk,
                                d.jenis_ptk,
                                d.status_kepegawaian,
                            ]
                                .filter(Boolean)
                                .join(" • ") || "-";

                    modalBody.innerHTML = `
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-top: 8px;">
                            <div style="grid-column: span 2; background: rgba(99,102,241,0.06); padding: 12px; border-radius: 8px; border-left: 4px solid var(--primary);">
                                <div id="tendikJkNama" style="font-size: 1.05rem; font-weight: 800; color: var(--text-color);">${escapeHtml(namaLengkap)}</div>
                                <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 2px;">
                                    ${escapeHtml(d.jabatan_ptk || d.jenis_ptk || "Tenaga Kependidikan")} • ${escapeHtml(d.status_kepegawaian || "-")}
                                </div>
                            </div>

                            <div style="grid-column: span 2; background: rgba(99,102,241,0.04); padding: 12px; border-radius: 8px; border: 1px solid var(--border-color);">
                                <div style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 6px;">Gelar (tersimpan lokal, tidak terhapus saat tarik Dapodik)</div>
                                <div style="display: flex; flex-wrap: wrap; gap: 6px; align-items: center;">
                                    <input type="text" id="tendikGelarDepan" maxlength="50" placeholder="Gelar depan" autocomplete="off"
                                        value="${escapeHtml(d.gelar_depan || "")}"
                                        style="flex: 1 1 100px; min-width: 90px; padding: 6px 9px; border: 1px solid var(--border-color); border-radius: 8px; background: var(--surface-2, transparent); color: var(--text-color); font-size: 0.8125rem;">
                                    <input type="text" id="tendikGelarBelakang" maxlength="100" placeholder="Gelar belakang" autocomplete="off"
                                        value="${escapeHtml(d.gelar_belakang || "")}"
                                        style="flex: 2 1 150px; min-width: 120px; padding: 6px 9px; border: 1px solid var(--border-color); border-radius: 8px; background: var(--surface-2, transparent); color: var(--text-color); font-size: 0.8125rem;">
                                </div>
                            </div>

                            <div>
                                <div style="font-size: 0.75rem; color: var(--text-muted);">NUPTK</div>
                                <div style="font-weight: 600;">${escapeHtml(d.nuptk || "-")}</div>
                            </div>
                            <div>
                                <div style="font-size: 0.75rem; color: var(--text-muted);">NIP</div>
                                <div style="font-weight: 600;">${escapeHtml(d.nip || "-")}</div>
                            </div>

                            <div>
                                <div style="font-size: 0.75rem; color: var(--text-muted);">NIK</div>
                                <div style="font-weight: 600;">${escapeHtml(d.nik || "-")}</div>
                            </div>
                            <div>
                                <div style="font-size: 0.75rem; color: var(--text-muted);">Jenis Kelamin</div>
                                <div style="font-weight: 600;">${d.jenis_kelamin === "L" ? "Laki-Laki (L)" : d.jenis_kelamin === "P" ? "Perempuan (P)" : "-"}</div>
                            </div>

                            <div>
                                <div style="font-size: 0.75rem; color: var(--text-muted);">Tempat, Tgl Lahir</div>
                                <div style="font-weight: 600;">${escapeHtml([d.tempat_lahir, d.tanggal_lahir].filter(Boolean).join(", ") || "-")}</div>
                            </div>
                            <div>
                                <div style="font-size: 0.75rem; color: var(--text-muted);">Agama</div>
                                <div style="font-weight: 600;">${escapeHtml(d.agama || "-")}</div>
                            </div>

                            <div>
                                <div style="font-size: 0.75rem; color: var(--text-muted);">Pendidikan Terakhir</div>
                                <div style="font-weight: 600;">${escapeHtml(d.pendidikan_terakhir || "-")}</div>
                            </div>
                            <div>
                                <div style="font-size: 0.75rem; color: var(--text-muted);">Bidang Studi / Jurusan</div>
                                <div style="font-weight: 600;">${escapeHtml(d.bidang_studi_terakhir || "-")}</div>
                            </div>

                            <div>
                                <div style="font-size: 0.75rem; color: var(--text-muted);">Pangkat / Golongan</div>
                                <div style="font-weight: 600;">${escapeHtml(d.pangkat_golongan_terakhir || "-")}</div>
                            </div>
                            <div>
                                <div style="font-size: 0.75rem; color: var(--text-muted);">Jenjang / Jabatan</div>
                                <div style="font-weight: 600;">${escapeHtml(d.jabatan_ptk || d.jenis_ptk || "-")}</div>
                            </div>

                            <div>
                                <div style="font-size: 0.75rem; color: var(--text-muted);">No. Telepon / HP</div>
                                <div style="font-weight: 600;">${escapeHtml(d.no_hp || "-")}</div>
                            </div>
                            <div>
                                <div style="font-size: 0.75rem; color: var(--text-muted);">Email</div>
                                <div style="font-weight: 600;">${escapeHtml(d.email || "-")}</div>
                            </div>

                            <div style="grid-column: span 2;">
                                <div style="font-size: 0.75rem; color: var(--text-muted);">Alamat Tempat Tinggal</div>
                                <div style="font-weight: 600;">${escapeHtml(d.alamat_jalan || "-")}</div>
                            </div>
                        </div>
                    `;
                } else {
                    modalBody.innerHTML = `<p style="text-align: center; color: var(--danger); padding: 20px;">Data gagal dimuat.</p>`;
                }
            } catch (err) {
                modalBody.innerHTML = `<p style="text-align: center; color: var(--danger); padding: 20px;">Terjadi kesalahan memuat data.</p>`;
            }
        });

    // Filters and search
    const searchInput = document.getElementById("liveSearch");
    const clearBtn = document.getElementById("clearSearch");
    const perPageSelect = document.getElementById("perPageSelect");
    const filterStatus = document.getElementById("filterStatus");
    const filterGender = document.getElementById("filterGender");

    function applyFilter(overrideParams = {}) {
        if (typeof window.collectDatatableUrl === "function") {
            const tableWrap = document.getElementById("tableDataContainer");
            const targetUrl = window.collectDatatableUrl(tableWrap, overrideParams);
            if (typeof window.refreshLiveTable === "function") {
                window.refreshLiveTable(targetUrl);
            } else {
                window.location.href = targetUrl;
            }
            return;
        }

        const url = new URL(window.location.href);
        const searchInput = document.getElementById("liveSearch");
        const perPageSelect = document.getElementById("perPageSelect");
        const filterStatus = document.getElementById("filterStatus");
        const filterGender = document.getElementById("filterGender");

        if (searchInput && searchInput.value.trim()) {
            url.searchParams.set("q", searchInput.value.trim());
        } else {
            url.searchParams.delete("q");
        }

        if (filterStatus && filterStatus.value) {
            url.searchParams.set("status", filterStatus.value);
        } else {
            url.searchParams.delete("status");
        }

        if (filterGender && filterGender.value) {
            url.searchParams.set("gender", filterGender.value);
        } else {
            url.searchParams.delete("gender");
        }

        if (perPageSelect && perPageSelect.value) {
            url.searchParams.set("perPage", perPageSelect.value);
        }

        Object.keys(overrideParams).forEach((k) => {
            const v = overrideParams[k];
            if (v !== null && v !== undefined && v !== "") url.searchParams.set(k, v);
            else url.searchParams.delete(k);
        });

        if (!("page" in overrideParams)) url.searchParams.set("page", "1");

        if (typeof window.refreshLiveTable === "function") {
            window.refreshLiveTable(url.toString());
        } else {
            window.location.href = url.toString();
        }
    }

    // Delegated Change Handler untuk filter Tendik Aktif
    document.addEventListener("change", function (e) {
        if (!e.target || e.target.tagName !== "SELECT") return;
        if (
            e.target.closest("#tableDataContainer, .datatable-toolbar") &&
            (e.target.id === "filterStatus" || e.target.id === "filterGender" || e.target.id === "perPageSelect")
        ) {
            e.preventDefault();
            e.stopPropagation();
            applyFilter();
        }
    });

    // Delegated Sort Click Handler untuk tabel Tendik Aktif
    document.addEventListener("click", function (e) {
        const th = e.target.closest(".sortable-th");
        if (!th || !th.closest("#tableDataContainer")) return;
        const sortField = th.getAttribute("data-sort");
        if (!sortField) return;
        e.preventDefault();
        e.stopPropagation();

        const url = new URL(window.location.href);
        const currentSort = url.searchParams.get("sort") || "nama";
        const currentDir = url.searchParams.get("sort_dir") || "asc";
        const newDir = currentSort === sortField && currentDir === "asc" ? "desc" : "asc";
        applyFilter({ sort: sortField, sort_dir: newDir });
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
