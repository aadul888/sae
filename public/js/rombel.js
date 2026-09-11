/**
 * Master Data — Rombongan Belajar (Sumber: Rombongan Belajar, Peserta Didik, & Pembelajaran) - Frontend JS
 */

let activeRombelTab = "anggota";

window.switchRombelModalTab = function (tab) {
    activeRombelTab = tab;
    const tabBtnAnggota = document.getElementById("tabBtnAnggota");
    const tabBtnPembelajaran = document.getElementById("tabBtnPembelajaran");
    const tabDropdown = document.getElementById("rombelTabDropdown");
    const pesertaDidikTableWrapper = document.getElementById(
        "pesertaDidikTableWrapper",
    );
    const pembelajaranTableWrapper = document.getElementById(
        "pembelajaranTableWrapper",
    );

    if (tabDropdown) {
        tabDropdown.value = tab;
    }

    if (tab === "anggota") {
        if (tabBtnAnggota) {
            tabBtnAnggota.className = "btn btn-primary";
        }
        if (tabBtnPembelajaran) {
            tabBtnPembelajaran.className = "btn btn-outline";
        }
        if (pesertaDidikTableWrapper)
            pesertaDidikTableWrapper.style.display = "block";
        if (pembelajaranTableWrapper)
            pembelajaranTableWrapper.style.display = "none";
    } else {
        if (tabBtnAnggota) {
            tabBtnAnggota.className = "btn btn-outline";
        }
        if (tabBtnPembelajaran) {
            tabBtnPembelajaran.className = "btn btn-primary";
        }
        if (pesertaDidikTableWrapper)
            pesertaDidikTableWrapper.style.display = "none";
        if (pembelajaranTableWrapper)
            pembelajaranTableWrapper.style.display = "block";
    }
};

window.openPesertaDidikModal = async function (id, nama) {
    const modal = document.getElementById("pesertaDidikModal");
    const modalTitle = document.getElementById("pesertaDidikModalTitle");
    const modalSubtitle = document.getElementById("pesertaDidikModalSubtitle");
    const loading = document.getElementById("pesertaDidikLoading");
    const tableWrapper = document.getElementById("pesertaDidikTableWrapper");
    const tableBody = document.getElementById("pesertaDidikTableBody");
    const pemTableWrapper = document.getElementById("pembelajaranTableWrapper");
    const pemTableBody = document.getElementById("pembelajaranTableBody");
    const footerCount = document.getElementById("pesertaDidikModalFooterCount");
    const badgeAnggota = document.getElementById("badgeAnggotaCount");
    const badgePem = document.getElementById("badgePembelajaranCount");

    if (!modal) return;

    modalTitle.textContent = nama || "Rincian Rombongan Belajar";
    modalSubtitle.textContent = "Memuat rincian...";
    tableBody.innerHTML = "";
    if (pemTableBody) pemTableBody.innerHTML = "";
    loading.style.display = "block";
    tableWrapper.style.display = "none";
    if (pemTableWrapper) pemTableWrapper.style.display = "none";
    modal.style.display = "flex";

    // Default to anggota tab
    window.switchRombelModalTab("anggota");

    try {
        const res = await fetch(
            "/dashboard/master-data/rombel/" +
                encodeURIComponent(id) +
                "/peserta-didik",
            {
                headers: {
                    Accept: "application/json",
                },
            },
        );
        const json = await res.json();

        loading.style.display = "none";
        window.switchRombelModalTab(activeRombelTab);

        if (json.status === "success" && json.rombel) {
            modalSubtitle.textContent = [
                json.rombel.tingkat,
                json.rombel.jurusan,
                json.rombel.wali_kelas
                    ? "Wali: " + json.rombel.wali_kelas
                    : null,
                json.rombel.ruang ? "Ruang: " + json.rombel.ruang : null,
                json.rombel.kurikulum ? json.rombel.kurikulum : null,
            ]
                .filter(Boolean)
                .join(" • ");

            const pdList = json.data || [];
            const pemList = json.pembelajaran || [];

            if (badgeAnggota) badgeAnggota.textContent = pdList.length;
            if (badgePem) badgePem.textContent = pemList.length;

            if (footerCount) {
                footerCount.textContent = `Total: ${pdList.length} Peserta Didik • ${pemList.length} Mata Pelajaran (${json.rombel.total_jam || 0} JP/Mg)`;
            }

            // Render Peserta Didik
            if (pdList.length > 0) {
                tableBody.innerHTML = pdList
                    .map(
                        (s, idx) => `
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 10px 14px; text-align: center; color: var(--text-muted); font-size: 0.78rem;">
                            ${idx + 1}
                        </td>
                        <td style="padding: 10px 14px; font-weight: 600; color: var(--text-color);">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                ${s.foto_url ? `
                                    <div style="width: 32px; height: 42px; border-radius: 6px; overflow: hidden; border: 1.5px solid var(--border-color); flex-shrink: 0; box-shadow: 0 2px 5px rgba(0,0,0,0.15); background: repeating-conic-gradient(#2a3447 0% 25%, #182030 0% 50%) 50% / 6px 6px;">
                                        <img src="${s.foto_url}" alt="${escapeHtml(s.nama)}" style="width: 100%; height: 100%; object-fit: cover;">
                                    </div>
                                ` : `
                                    <div style="width: 32px; height: 42px; border-radius: 6px; background: rgba(99,102,241,0.06); border: 1px dashed var(--border-color); display: flex; align-items: center; justify-content: center; color: var(--text-muted); font-size: 0.75rem; flex-shrink: 0;">
                                        <i class="fas fa-user-graduate" style="opacity: 0.5;"></i>
                                    </div>
                                `}
                                <div>
                                    <div style="font-size: 0.86rem;">${escapeHtml(s.nama || "-")}</div>
                                    ${s.foto_url ? `<span style="font-size: 0.65rem; color: #10b981; font-weight: 600;"><i class="fas fa-check-circle"></i> Foto PNG Aktif</span>` : ''}
                                </div>
                            </div>
                        </td>
                        <td style="padding: 10px 14px; font-family: monospace; font-size: 0.8rem; color: var(--primary);">
                            ${escapeHtml(s.nisn || s.nipd || "-")}
                        </td>
                        <td style="padding: 10px 14px; text-align: center;">
                            <span class="badge ${s.jenis_kelamin === "L" ? "badge-primary" : "badge-danger"}" style="font-size: 0.72rem; padding: 2px 7px;">
                                ${escapeHtml(s.jenis_kelamin || "-")}
                            </span>
                        </td>
                        <td style="padding: 10px 14px; font-size: 0.8rem; color: var(--text-muted);">
                            ${escapeHtml(s.jenis_pendaftaran || "Peserta Didik Baru")}
                        </td>
                        <td style="padding: 10px 14px; color: var(--text-muted); font-size: 0.8rem;">
                            ${escapeHtml([s.tempat_lahir, s.tanggal_lahir].filter(Boolean).join(", ") || "-")}
                        </td>
                    </tr>`,
                    )
                    .join("");
            } else {
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="6" style="padding: 30px; text-align: center; color: var(--text-muted);">
                            Belum ada peserta didik terdaftar di rombongan belajar ini.
                        </td>
                    </tr>`;
            }

            // Render Pembelajaran
            if (pemTableBody) {
                if (pemList.length > 0) {
                    pemTableBody.innerHTML = pemList
                        .map(
                            (p, idx) => `
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 10px 14px; text-align: center; color: var(--text-muted); font-size: 0.78rem;">
                                ${idx + 1}
                            </td>
                            <td style="padding: 10px 14px; font-weight: 600; color: var(--text-color);">
                                <div>${escapeHtml(p.nama_mata_pelajaran || p.mata_pelajaran_id_str || "-")}</div>
                            </td>
                            <td style="padding: 10px 14px; font-size: 0.82rem; color: var(--text-color);">
                                <div>${escapeHtml(p.nama_guru || "Belum Ditugaskan")}</div>
                                ${p.nuptk || p.nip ? `<div style="font-size: 0.72rem; color: var(--text-muted); font-family: monospace;">${p.nuptk ? "NUPTK: " + escapeHtml(p.nuptk) : "NIP: " + escapeHtml(p.nip)}</div>` : ""}
                            </td>
                            <td style="padding: 10px 14px; text-align: center;">
                                <span class="badge" style="background: rgba(245,158,11,0.15); color: #f59e0b; font-size: 0.74rem; padding: 2px 8px; font-weight: 700;">
                                    ${escapeHtml(p.jam_mengajar_per_minggu || "0")} JP
                                </span>
                            </td>
                            <td style="padding: 10px 14px; font-size: 0.8rem; color: var(--text-muted);">
                                <span class="badge badge-outline" style="font-size: 0.70rem; padding: 2px 6px;">
                                    ${escapeHtml(p.status_di_kurikulum_str || "Wajib")}
                                </span>
                            </td>
                        </tr>`,
                        )
                        .join("");
                } else {
                    pemTableBody.innerHTML = `
                        <tr>
                            <td colspan="5" style="padding: 30px; text-align: center; color: var(--text-muted);">
                                Belum ada data pembelajaran di rombongan belajar ini.
                            </td>
                        </tr>`;
                }
            }
        } else {
            throw new Error(json.message || "Gagal memuat data");
        }
    } catch (e) {
        loading.style.display = "none";
        tableWrapper.style.display = "block";
        if (footerCount) footerCount.textContent = "";
        tableBody.innerHTML = `
            <tr>
                <td colspan="6" style="padding: 30px; text-align: center; color: var(--danger, #ef4444);">
                    Gagal memuat rincian rombongan belajar.
                </td>
            </tr>`;
    }
};

window.closePesertaDidikModal = function () {
    const modal = document.getElementById("pesertaDidikModal");
    if (modal) modal.style.display = "none";
};

function escapeHtml(str) {
    if (!str) return "";
    return String(str)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

document.addEventListener("DOMContentLoaded", function () {
    const modal = document.getElementById("pesertaDidikModal");
    if (modal) {
        modal.addEventListener("click", function (e) {
            if (e.target === modal) {
                window.closePesertaDidikModal();
            }
        });
    }

    document.addEventListener("keydown", function (e) {
        if (e.key === "Escape") {
            window.closePesertaDidikModal();
        }
    });

    const searchInput = document.getElementById("liveSearch");
    const clearBtn = document.getElementById("clearSearch");
    const perPageSelect = document.getElementById("perPageSelect");
    const filterTingkat = document.getElementById("filterTingkat");
    const filterJurusan = document.getElementById("filterJurusan");

    function applyFilter() {
        const url = new URL(window.location.href);
        if (searchInput && searchInput.value.trim()) {
            url.searchParams.set("q", searchInput.value.trim());
        } else {
            url.searchParams.delete("q");
        }

        if (filterTingkat && filterTingkat.value) {
            url.searchParams.set("tingkat", filterTingkat.value);
        } else {
            url.searchParams.delete("tingkat");
        }

        if (filterJurusan && filterJurusan.value) {
            url.searchParams.set("jurusan", filterJurusan.value);
        } else {
            url.searchParams.delete("jurusan");
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
            if (clearBtn) {
                clearBtn.classList.toggle(
                    "visible",
                    this.value.trim().length > 0,
                );
            }
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

    if (filterTingkat) {
        filterTingkat.addEventListener("change", applyFilter);
    }

    if (filterJurusan) {
        filterJurusan.addEventListener("change", applyFilter);
    }

    if (perPageSelect) {
        perPageSelect.addEventListener("change", applyFilter);
    }

    // Sortable column click
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
