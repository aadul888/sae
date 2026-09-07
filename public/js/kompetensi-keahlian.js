/**
 * Master Data — Kompetensi Keahlian (Sumber: Rombongan Belajar) - Frontend JS
 */
document.addEventListener("DOMContentLoaded", function () {
    const modal = document.getElementById("rombelModal");
    const modalTitle = document.getElementById("rombelModalTitle");
    const modalSubtitle = document.getElementById("rombelModalSubtitle");
    const loading = document.getElementById("rombelLoading");
    const tableWrapper = document.getElementById("rombelTableWrapper");
    const tableBody = document.getElementById("rombelTableBody");

    window.openRombelModal = async function (kode, nama) {
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
                            ${r.nama_rombel}
                        </td>
                        <td style="padding: 10px 14px; color: var(--text-color);">
                            <span class="badge badge-outline" style="font-size: 0.72rem; padding: 2px 7px;">
                                ${r.tingkat || "-"}
                            </span>
                        </td>
                        <td style="padding: 10px 14px; color: var(--text-color);">
                            ${r.wali_kelas || '<span style="color: var(--text-muted);">-</span>'}
                        </td>
                        <td style="padding: 10px 14px; color: var(--text-muted);">
                            ${r.ruang || "-"}
