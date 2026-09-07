/**
 * Master Data — Rombongan Belajar (Sumber: Rombongan Belajar & Peserta Didik) - Frontend JS
 */

window.openSiswaModal = async function (id, nama) {
    const modal = document.getElementById("siswaModal");
    const modalTitle = document.getElementById("siswaModalTitle");
    const modalSubtitle = document.getElementById("siswaModalSubtitle");
    const loading = document.getElementById("siswaLoading");
    const tableWrapper = document.getElementById("siswaTableWrapper");
    const tableBody = document.getElementById("siswaTableBody");
    const footerCount = document.getElementById("siswaModalFooterCount");

    if (!modal) return;

    modalTitle.textContent = nama || "Daftar Peserta Didik";
    modalSubtitle.textContent = "Memuat rincian...";
    tableBody.innerHTML = "";
    loading.style.display = "block";
    tableWrapper.style.display = "none";
    modal.style.display = "flex";

    try {
        const res = await fetch(
            "/dashboard/master-data/rombel/" +
                encodeURIComponent(id) +
                "/siswa",
            {
                headers: {
                    Accept: "application/json",
                },
            },
        );
        const json = await res.json();

        loading.style.display = "none";
        tableWrapper.style.display = "block";

        if (json.status === "success" && json.rombel) {
            modalSubtitle.textContent = [
                json.rombel.tingkat,
                json.rombel.jurusan,
                json.rombel.wali_kelas
                    ? "Wali: " + json.rombel.wali_kelas
                    : null,
                json.rombel.ruang ? "Ruang: " + json.rombel.ruang : null,
            ]
                .filter(Boolean)
                .join(" • ");

            if (json.data && json.data.length > 0) {
                if (footerCount)
                    footerCount.textContent =
                        "Total: " + json.data.length + " Peserta Didik";
                tableBody.innerHTML = json.data
                    .map(
                        (s, idx) => `
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 10px 14px; text-align: center; color: var(--text-muted); font-size: 0.78rem;">
                            ${idx + 1}
                        </td>
                        <td style="padding: 10px 14px; font-weight: 600; color: var(--text-color);">
                            ${escapeHtml(s.nama || "-")}
                        </td>
                        <td style="padding: 10px 14px; font-family: monospace; font-size: 0.8rem; color: var(--primary);">
                            ${escapeHtml(s.nisn || s.nipd || "-")}
                        </td>
                        <td style="padding: 10px 14px; text-align: center;">
                            <span class="badge ${s.jenis_kelamin === "L" ? "badge-primary" : "badge-outline"}" style="font-size: 0.72rem; padding: 2px 7px;">
                                ${escapeHtml(s.jenis_kelamin || "-")}
                            </span>
                        </td>
                        <td style="padding: 10px 14px; color: var(--text-muted); font-size: 0.8rem;">
                            ${escapeHtml([s.tempat_lahir, s.tanggal_lahir].filter(Boolean).join(", ") || "-")}
                        </td>
                    </tr>`,
                    )
                    .join("");
            } else {
                if (footerCount)
                    footerCount.textContent = "Total: 0 Peserta Didik";
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="5" style="padding: 30px; text-align: center; color: var(--text-muted);">
                            Belum ada peserta didik terdaftar di rombongan belajar ini.
                        </td>
                    </tr>`;
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
                <td colspan="5" style="padding: 30px; text-align: center; color: var(--danger, #ef4444);">
                    Gagal memuat daftar peserta didik.
                </td>
            </tr>`;
    }
};

window.closeSiswaModal = function () {
    const modal = document.getElementById("siswaModal");
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
    const modal = document.getElementById("siswaModal");
    if (modal) {
        modal.addEventListener("click", function (e) {
            if (e.target === modal) {
                window.closeSiswaModal();
            }
        });
    }

    document.addEventListener("keydown", function (e) {
        if (e.key === "Escape") {
            window.closeSiswaModal();
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
