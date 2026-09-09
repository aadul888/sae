/**
 * Manajemen Data — Tendik Aktif Frontend JS
 */

document.addEventListener("DOMContentLoaded", function () {
    const modal = document.getElementById("modalDetailTendik");
    const modalBody = document.getElementById("modalBodyTendik");
    const closeBtn = document.querySelector(".btn-close-modal");

    function closeModal() {
        if (modal) modal.style.display = "none";
    }

    if (closeBtn) {
        closeBtn.addEventListener("click", closeModal);
    }

    if (modal) {
        modal.addEventListener("click", function (e) {
            if (e.target === modal) closeModal();
        });
    }

    document.addEventListener("keydown", function (e) {
        if (e.key === "Escape") closeModal();
    });

    // Detail Button
    document.querySelectorAll(".btn-detail-tendik").forEach(function (btn) {
        btn.addEventListener("click", async function () {
            const id = this.getAttribute("data-id");
            if (!id || !modal || !modalBody) return;

            modal.style.display = "flex";
            modalBody.innerHTML = `
                <div style="text-align: center; padding: 30px;">
                    <i class="fas fa-spinner fa-spin" style="font-size: 1.8rem; color: var(--primary);"></i>
                    <p style="margin-top: 10px; color: var(--text-muted);">Memuat data tenaga kependidikan...</p>
                </div>
            `;

            try {
                const res = await fetch(`/dashboard/manajemen-data/tendik-aktif/${encodeURIComponent(id)}`, {
                    headers: { Accept: "application/json" }
                });
                const json = await res.json();
                if (json.status === "success" && json.data) {
                    const d = json.data;
                    modalBody.innerHTML = `
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-top: 8px;">
                            <div style="grid-column: span 2; background: rgba(99,102,241,0.06); padding: 12px; border-radius: 8px; border-left: 4px solid var(--primary);">
                                <div style="font-size: 1.05rem; font-weight: 800; color: var(--text-color);">${d.nama || '-'}</div>
                                <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 2px;">
                                    ${d.jabatan_ptk_id_str || d.jenis_ptk_id_str || 'Tenaga Kependidikan'} • ${d.status_kepegawaian_id_str || '-'}
                                </div>
                            </div>

                            <div>
                                <div style="font-size: 0.75rem; color: var(--text-muted);">NUPTK</div>
                                <div style="font-weight: 600;">${d.nuptk || '-'}</div>
                            </div>
                            <div>
                                <div style="font-size: 0.75rem; color: var(--text-muted);">NIP</div>
                                <div style="font-weight: 600;">${d.nip || '-'}</div>
                            </div>

                            <div>
                                <div style="font-size: 0.75rem; color: var(--text-muted);">NIK</div>
                                <div style="font-weight: 600;">${d.nik || '-'}</div>
                            </div>
                            <div>
                                <div style="font-size: 0.75rem; color: var(--text-muted);">Jenis Kelamin</div>
                                <div style="font-weight: 600;">${d.jenis_kelamin === 'L' ? 'Laki-Laki' : (d.jenis_kelamin === 'P' ? 'Perempuan' : '-')}</div>
                            </div>

                            <div>
                                <div style="font-size: 0.75rem; color: var(--text-muted);">Tempat, Tgl Lahir</div>
                                <div style="font-weight: 600;">${[d.tempat_lahir, d.tanggal_lahir].filter(Boolean).join(', ') || '-'}</div>
                            </div>
                            <div>
                                <div style="font-size: 0.75rem; color: var(--text-muted);">Agama</div>
                                <div style="font-weight: 600;">${d.agama_id_str || '-'}</div>
                            </div>

                            <div>
                                <div style="font-size: 0.75rem; color: var(--text-muted);">Pendidikan Terakhir</div>
                                <div style="font-weight: 600;">${d.pendidikan_terakhir || '-'}</div>
                            </div>
                            <div>
                                <div style="font-size: 0.75rem; color: var(--text-muted);">Bidang Studi / Jurusan</div>
                                <div style="font-weight: 600;">${d.bidang_studi_terakhir || '-'}</div>
                            </div>

                            <div>
                                <div style="font-size: 0.75rem; color: var(--text-muted);">Pangkat / Golongan</div>
                                <div style="font-weight: 600;">${d.pangkat_golongan_terakhir || '-'}</div>
                            </div>
                            <div>
                                <div style="font-size: 0.75rem; color: var(--text-muted);">No. Telepon / HP</div>
                                <div style="font-weight: 600;">${d.no_hp || '-'}</div>
                            </div>

                            <div style="grid-column: span 2;">
                                <div style="font-size: 0.75rem; color: var(--text-muted);">Email</div>
                                <div style="font-weight: 600;">${d.email || '-'}</div>
                            </div>

                            <div style="grid-column: span 2;">
                                <div style="font-size: 0.75rem; color: var(--text-muted);">Alamat Tempat Tinggal</div>
                                <div style="font-weight: 600;">${d.alamat_jalan || '-'}</div>
                            </div>
                        </div>
                    `;
                } else {
                    modalBody.innerHTML = `<p style="text-align: center; color: var(--danger);">Data gagal dimuat.</p>`;
                }
            } catch (err) {
                modalBody.innerHTML = `<p style="text-align: center; color: var(--danger);">Terjadi kesalahan koneksi.</p>`;
            }
        });
    });

    // Filters and search
    const searchInput = document.getElementById("liveSearch");
    const clearBtn = document.getElementById("clearSearch");
    const perPageSelect = document.getElementById("perPageSelect");
    const filterStatus = document.getElementById("filterStatus");
    const filterGender = document.getElementById("filterGender");

    function applyFilter() {
        const url = new URL(window.location.href);
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

        url.searchParams.delete("page");
        window.location.href = url.toString();
    }

    let searchTimer = null;
    if (searchInput) {
        searchInput.addEventListener("input", function () {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(applyFilter, 500);
        });
    }

    if (clearBtn) {
        clearBtn.addEventListener("click", function () {
            if (searchInput) searchInput.value = "";
            applyFilter();
        });
    }

    if (perPageSelect) perPageSelect.addEventListener("change", applyFilter);
    if (filterStatus) filterStatus.addEventListener("change", applyFilter);
    if (filterGender) filterGender.addEventListener("change", applyFilter);

    // Sorting clicks
    document.querySelectorAll(".sortable-th").forEach(function (th) {
        th.addEventListener("click", function () {
            const sortField = this.getAttribute("data-sort");
            if (!sortField) return;

            const url = new URL(window.location.href);
            const currentSort = url.searchParams.get("sort");
            const currentDir = url.searchParams.get("sort_dir") || "asc";

            if (currentSort === sortField) {
                url.searchParams.set("sort_dir", currentDir === "asc" ? "desc" : "asc");
            } else {
                url.searchParams.set("sort", sortField);
                url.searchParams.set("sort_dir", "asc");
            }

            url.searchParams.delete("page");
            window.location.href = url.toString();
        });
    });
});
