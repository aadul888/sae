/**
 * Master Data — Kompetensi Keahlian (Sumber: Rombongan Belajar) - Frontend JS
 */

window.openRombelModal = async function (kode, nama) {
    const modal = document.getElementById("rombelModal");
    const modalTitle = document.getElementById("rombelModalTitle");
    const modalSubtitle = document.getElementById("rombelModalSubtitle");
    const loading = document.getElementById("rombelLoading");
    const tableWrapper = document.getElementById("rombelTableWrapper");
    const tableBody = document.getElementById("rombelTableBody");

    if (!modal) return;

    modalTitle.textContent = nama || "Daftar Rombel";
    modalSubtitle.textContent = "Kode Jurusan: " + kode;
    tableBody.innerHTML = "";
    loading.style.display = "block";
    tableWrapper.style.display = "none";
    modal.style.display = "flex";

    try {
        const res = await fetch(
            "/dashboard/master-data/kompetensi-keahlian/" +
                encodeURIComponent(kode) +
                "/rombel",
            {
                headers: {
                    Accept: "application/json",
                },
            },
        );
        const json = await res.json();

        loading.style.display = "none";
        tableWrapper.style.display = "block";

        if (json.status === "success" && json.data && json.data.length > 0) {
            tableBody.innerHTML = json.data
                .map(
                    (r) => `
                <tr style="border-bottom: 1px solid var(--border-color);">
                    <td style="padding: 10px 14px; font-weight: 700; color: var(--primary);">
                        ${escapeHtml(r.nama_rombel || "-")}
                    </td>
                    <td style="padding: 10px 14px; color: var(--text-color);">
                        <span class="badge badge-outline" style="font-size: 0.72rem; padding: 2px 7px;">
                            ${escapeHtml(r.tingkat || "-")}
                        </span>
                    </td>
                    <td style="padding: 10px 14px; color: var(--text-color);">
                        ${escapeHtml(r.wali_kelas || "-")}
                    </td>
                    <td style="padding: 10px 14px; color: var(--text-muted);">
                        ${escapeHtml(r.ruang || "-")}
                    </td>
                    <td style="padding: 10px 14px; color: var(--text-muted); font-size: 0.8rem;">
                        ${escapeHtml(r.kurikulum || "-")}
                    </td>
                    <td style="padding: 10px 14px; text-align: right; font-weight: 700; color: var(--primary);">
                        ${Number(r.jumlah_peserta_didik || 0).toLocaleString("id-ID")}
                    </td>
                </tr>`,
                )
                .join("");
        } else {
            tableBody.innerHTML = `
                <tr>
                    <td colspan="6" style="padding: 30px; text-align: center; color: var(--text-muted);">
                        Tidak ada rombongan belajar terdaftar untuk jurusan ini.
                    </td>
                </tr>`;
        }
    } catch (e) {
        loading.style.display = "none";
        tableWrapper.style.display = "block";
        tableBody.innerHTML = `
            <tr>
                <td colspan="6" style="padding: 30px; text-align: center; color: var(--danger, #ef4444);">
                    Gagal memuat rincian rombongan belajar.
                </td>
            </tr>`;
    }
};

window.closeRombelModal = function () {
    const modal = document.getElementById("rombelModal");
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
    const modal = document.getElementById("rombelModal");
    if (modal) {
        modal.addEventListener("click", function (e) {
            if (e.target === modal) {
                window.closeRombelModal();
            }
        });
    }

    document.addEventListener("keydown", function (e) {
        if (e.key === "Escape") {
            window.closeRombelModal();
        }
    });

    const searchInput = document.getElementById("searchTable");
    if (searchInput) {
        let timer = null;
        searchInput.addEventListener("input", function () {
            clearTimeout(timer);
            timer = setTimeout(() => {
                const url = new URL(window.location.href);
                if (this.value.trim()) {
                    url.searchParams.set("search", this.value.trim());
                } else {
                    url.searchParams.delete("search");
                }
                url.searchParams.set("page", "1");
                window.location.href = url.toString();
            }, 500);
        });
    }

    const perPageSelect = document.getElementById("perPageSelect");
    if (perPageSelect) {
        perPageSelect.addEventListener("change", function () {
            const url = new URL(window.location.href);
            url.searchParams.set("per_page", this.value);
            url.searchParams.set("page", "1");
            window.location.href = url.toString();
        });
    }
});
