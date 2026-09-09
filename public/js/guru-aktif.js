/**
 * Manajemen Data — Guru & Tendik Aktif - Frontend JS
 */

window.openBiodataGuruModal = async function (id) {
    const modal = document.getElementById("gtkModal");
    const gtkJabatan = document.getElementById("gtkJabatan");
    const gtkSubtitle = document.getElementById("gtkSubtitle");
    const gtkLoading = document.getElementById("gtkLoading");
    const gtkContent = document.getElementById("gtkContent");

    if (!modal) return;

    gtkJabatan.textContent = "Profil Guru & Tendik";
    gtkSubtitle.textContent = "Memuat data...";
    gtkLoading.style.display = "block";
    gtkContent.style.display = "none";
    modal.style.display = "flex";

    try {
        const res = await fetch(
            "/dashboard/manajemen-data/guru-aktif/" + encodeURIComponent(id),
            {
                headers: { Accept: "application/json" },
            },
        );
        const json = await res.json();

        gtkLoading.style.display = "none";
        gtkContent.style.display = "block";

        if (json.status === "success" && json.data) {
            const d = json.data;
            gtkJabatan.textContent = d.nama || "Tanpa Nama";
            gtkSubtitle.textContent =
                [d.jenis_ptk_id_str, d.jabatan_ptk_id_str]
                    .filter(Boolean)
                    .join(" • ") || "-";

            document.getElementById("gtkJkNama").textContent = d.nama || "-";
            document.getElementById("gtkJkNuptk").textContent =
                [
                    d.nuptk ? "NUPTK: " + d.nuptk : null,
                    d.nip ? "NIP: " + d.nip : null,
                ]
                    .filter(Boolean)
                    .join(" / ") || "-";
            document.getElementById("gtkJkNik").textContent = d.nik || "-";
            document.getElementById("gtkJkGender").textContent =
                d.jenis_kelamin === "L"
                    ? "Laki-Laki (L)"
                    : d.jenis_kelamin === "P"
                      ? "Perempuan (P)"
                      : "-";
            document.getElementById("gtkJkTtl").textContent =
                [d.tempat_lahir, d.tanggal_lahir].filter(Boolean).join(", ") ||
                "-";
            document.getElementById("gtkJkAgama").textContent =
                d.agama_id_str || "-";
            document.getElementById("gtkJkStatus").textContent =
                [d.status_kepegawaian_id_str, d.pangkat_golongan_terakhir]
                    .filter(Boolean)
                    .join(" / ") || "-";
            document.getElementById("gtkJkPend").textContent =
                d.pendidikan_terakhir || "-";
            document.getElementById("gtkJkMapel").textContent =
                d.bidang_studi_terakhir || "-";
            document.getElementById("gtkJkInduk").textContent =
                [
                    d.ptk_induk === "1" ? "PTK Induk" : null,
                    d.tanggal_surat_tugas
                        ? "Tgl Tugas: " + d.tanggal_surat_tugas
                        : null,
                ]
                    .filter(Boolean)
                    .join(" • ") || "-";
            document.getElementById("gtkJkHp").textContent =
                [
                    d.no_hp ? "HP: " + d.no_hp : null,
                    d.email ? "Email: " + d.email : null,
                ]
                    .filter(Boolean)
                    .join(" • ") || "-";
            document.getElementById("gtkJkAlamat").textContent =
                d.alamat_jalan || "-";

            // Render Pembelajaran / Beban Mengajar
            const bebanSection = document.getElementById("gtkBebanSection");
            const bebanList = document.getElementById("gtkBebanList");
            const jmlJam = document.getElementById("gtkJmlJam");
            const pemList = json.pembelajaran || [];

            if (bebanSection && bebanList) {
                if (pemList.length > 0) {
                    if (jmlJam)
                        jmlJam.textContent =
                            (json.total_jam || 0) + " JP/Minggu";
                    bebanList.innerHTML = pemList
                        .map(
                            (p) => `
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 10px; font-weight: 600; color: var(--text-color);">${escapeHtml(p.nama_mata_pelajaran || p.mata_pelajaran_id_str || "-")}</td>
                            <td style="padding: 6px 10px; color: var(--text-color);">${escapeHtml(p.nama_rombel || "-")}</td>
                            <td style="padding: 6px 10px; text-align: center; color: #f59e0b; font-weight: 700;">${escapeHtml(p.jam_mengajar_per_minggu || "0")} JP</td>
                            <td style="padding: 6px 10px; color: var(--text-muted);">${escapeHtml(p.status_di_kurikulum_str || "Wajib")}</td>
                        </tr>
                    `,
                        )
                        .join("");
                    bebanSection.style.display = "block";
                } else {
                    bebanSection.style.display = "none";
                }
            }
        }
    } catch (e) {
        gtkLoading.style.display = "none";
        gtkContent.style.display = "block";
        gtkSubtitle.textContent = "Gagal memuat profil.";
    }
};

window.closeBiodataGuruModal = function () {
    const modal = document.getElementById("gtkModal");
    if (modal) modal.style.display = "none";
};

document.addEventListener("DOMContentLoaded", function () {
    const modal = document.getElementById("gtkModal");
    if (modal) {
        modal.addEventListener("click", function (e) {
            if (e.target === modal) window.closeBiodataGuruModal();
        });
    }

    document.addEventListener("keydown", function (e) {
        if (e.key === "Escape") window.closeBiodataGuruModal();
    });

    const searchInput = document.getElementById("liveSearch");
    const clearBtn = document.getElementById("clearSearch");
    const perPageSelect = document.getElementById("perPageSelect");
    const filterJenis = document.getElementById("filterJenis");
    const filterStatus = document.getElementById("filterStatus");
    const filterGender = document.getElementById("filterGender");

    function applyFilter() {
        const url = new URL(window.location.href);
        if (searchInput && searchInput.value.trim()) {
            url.searchParams.set("q", searchInput.value.trim());
        } else {
            url.searchParams.delete("q");
        }

        if (filterJenis && filterJenis.value) {
            url.searchParams.set("jenis", filterJenis.value);
        } else {
            url.searchParams.delete("jenis");
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

    if (filterJenis) filterJenis.addEventListener("change", applyFilter);
    if (filterStatus) filterStatus.addEventListener("change", applyFilter);
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
