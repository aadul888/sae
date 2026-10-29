/**
 * Manajemen Data — GTK Non-ASN - Frontend JS
 *
 * Data Non-ASN disimpan di tabel `gtk_non_asn`, terpisah dari `gtk`
 * (Dapodik), sehingga aman dari proses tarik data Dapodik.
 */

document.addEventListener("DOMContentLoaded", function () {
    const liveSearch = document.getElementById("liveSearch");
    const clearSearch = document.getElementById("clearSearch");
    const perPageSelect = document.getElementById("perPageSelect");

    let debounceTimer;

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

        if (liveSearch && liveSearch.value.trim()) {
            url.searchParams.set("q", liveSearch.value.trim());
        } else {
            url.searchParams.delete("q");
        }

        ["jenis", "status", "gender"].forEach((key) => {
            const el = document.getElementById("filter" + key.charAt(0).toUpperCase() + key.slice(1));
            if (el && el.value) url.searchParams.set(key, el.value);
            else url.searchParams.delete(key);
        });

        const curPerPage = perPageSelect ? perPageSelect.value : url.searchParams.get("perPage") || "15";
        if (curPerPage) url.searchParams.set("perPage", curPerPage);

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

    // Filter & per-halaman
    document.addEventListener("change", function (e) {
        if (!e.target || e.target.tagName !== "SELECT") return;
        if (
            e.target.closest("#tableDataContainer, .datatable-toolbar") &&
            ["filterJenis", "filterStatus", "perPageSelect"].includes(e.target.id)
        ) {
            e.preventDefault();
            e.stopPropagation();
            applyFilter();
        }
    });

    // Sort kolom
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

    if (liveSearch) {
        liveSearch.addEventListener("input", function () {
            clearTimeout(debounceTimer);
            if (clearSearch) clearSearch.classList.toggle("visible", this.value.trim() !== "");
            debounceTimer = setTimeout(() => applyFilter(), 450);
        });
    }

    if (clearSearch) {
        clearSearch.addEventListener("click", function () {
            if (liveSearch) liveSearch.value = "";
            this.classList.remove("visible");
            applyFilter();
        });
    }
});

/* -------------------------------------------------------------------- */
/* Modal Tambah / Ubah                                                   */
/* -------------------------------------------------------------------- */

const NON_ASN_FIELDS = [
    "nama", "jenis_ptk", "jabatan", "jenis_kelamin", "nik", "nuptk", "nip",
    "gelar_depan", "gelar_belakang", "status_kepegawaian", "pendidikan_terakhir",
    "bidang_studi", "email", "no_hp", "status", "alamat", "keterangan",
];

window.openNonAsnModal = function (row = null) {
    const modal = document.getElementById("modalNonAsn");
    if (!modal) return;

    const form = document.getElementById("formNonAsn");
    const method = document.getElementById("nonAsnMethod");
    const title = document.getElementById("nonAsnModalTitle");

    form.reset();

    if (row && row.id) {
        title.textContent = "Ubah Data GTK Non-ASN";
        form.action = `/dashboard/manajemen-data/gtk-non-asn/${row.id}`;
        method.value = "PUT";

        NON_ASN_FIELDS.forEach((field) => {
            const el = document.getElementById("na_" + field);
            if (!el) return;
            let value = row[field];
            el.value = value === null || value === undefined ? "" : value;
        });
    } else {
        title.textContent = "Tambah GTK Non-ASN";
        form.action = "/dashboard/manajemen-data/gtk-non-asn";
        method.value = "POST";

        const statusKep = document.getElementById("na_status_kepegawaian");
        if (statusKep) statusKep.value = "Non-ASN";
        const status = document.getElementById("na_status");
        if (status) status.value = "aktif";
    }

    modal.style.display = "flex";
    document.body.style.overflow = "hidden";
};

window.closeNonAsnModal = function () {
    const modal = document.getElementById("modalNonAsn");
    if (!modal) return;
    modal.style.display = "none";
    document.body.style.overflow = "";
};

// Tombol ubah per baris (data dikirim base64 dalam atribut data-row)
document.addEventListener("click", function (e) {
    const btn = e.target.closest(".btn-edit-non-asn");
    if (!btn) return;
    e.preventDefault();

    try {
        const raw = atob(btn.getAttribute("data-row"));
        const row = JSON.parse(raw);
        window.openNonAsnModal(row);
    } catch (err) {
        window.SAE?.alert("Gagal membaca data baris: " + err.message, "Kesalahan", "error");
    }
});

/* -------------------------------------------------------------------- */
/* Buat akun login                                                       */
/* -------------------------------------------------------------------- */

document.addEventListener("click", async function (e) {
    const btn = e.target.closest(".btn-buat-akun");
    if (!btn) return;
    e.preventDefault();

    const id = btn.getAttribute("data-id");
    const nama = btn.getAttribute("data-nama") || "GTK ini";

    const konfirmasi = window.Swal
        ? await Swal.fire({
              title: "Buat Akun Login?",
              html: `Akun login akan dibuat untuk <strong>${nama}</strong>.<br><small>Username otomatis dari NIP/NIK/email, password awal dibuat acak.</small>`,
              icon: "question",
              showCancelButton: true,
              confirmButtonText: "Ya, Buat Akun",
              cancelButtonText: "Batal",
              confirmButtonColor: "#6366f1",
          })
        : { isConfirmed: confirm("Buat akun login untuk " + nama + "?") };

    if (!konfirmasi.isConfirmed) return;

    btn.disabled = true;
    const original = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") || "";

    try {
        const res = await fetch(`/dashboard/manajemen-data/gtk-non-asn/${id}/buat-akun`, {
            method: "POST",
            headers: {
                Accept: "application/json",
                "X-Requested-With": "XMLHttpRequest",
                "X-CSRF-TOKEN": csrf,
            },
        });
        const json = await res.json();

        if (res.ok && json.status === "success") {
            if (window.Swal) {
                await Swal.fire({
                    title: "Akun Dibuat",
                    html: escapeHtmlNonAsn(json.message),
                    icon: "success",
                    confirmButtonColor: "#10b981",
                });
            }
            window.location.reload();
        } else {
            const msg = json.message || "Gagal membuat akun.";
            window.Swal ? Swal.fire({ title: "Gagal", text: msg, icon: "error" }) : alert(msg);
        }
    } catch (err) {
        const msg = "Kesalahan jaringan: " + (err.message || "tidak diketahui");
        window.Swal ? Swal.fire({ title: "Kesalahan", text: msg, icon: "error" }) : alert(msg);
    } finally {
        btn.disabled = false;
        btn.innerHTML = original;
    }
});

/* -------------------------------------------------------------------- */
/* Modal Import                                                          */
/* -------------------------------------------------------------------- */

window.openImportNonAsnModal = function () {
    const modal = document.getElementById("modalImportNonAsn");
    if (!modal) return;
    const form = document.getElementById("formImportNonAsn");
    if (form) form.reset();
    const progress = document.getElementById("importNonAsnProgress");
    if (progress) progress.style.display = "none";
    const btn = document.getElementById("btnSubmitImportNonAsn");
    if (btn) btn.disabled = false;
    modal.style.display = "flex";
    document.body.style.overflow = "hidden";
};

window.closeImportNonAsnModal = function () {
    const modal = document.getElementById("modalImportNonAsn");
    if (!modal) return;
    modal.style.display = "none";
    document.body.style.overflow = "";
};

window.handleImportNonAsnSubmit = async function (e) {
    e.preventDefault();
    const form = e.target;
    const fileInput = document.getElementById("inputNonAsnFile");

    if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
        window.Swal
            ? Swal.fire({ title: "Berkas Belum Dipilih", text: "Pilih berkas Excel atau CSV terlebih dahulu.", icon: "warning", confirmButtonColor: "#6366f1" })
            : alert("Pilih berkas Excel atau CSV terlebih dahulu.");
        return;
    }

    const progress = document.getElementById("importNonAsnProgress");
    const btnSubmit = document.getElementById("btnSubmitImportNonAsn");
    const btnText = document.getElementById("btnSubmitImportNonAsnText");

    if (progress) progress.style.display = "block";
    if (btnSubmit) btnSubmit.disabled = true;
    if (btnText) btnText.textContent = "Mengimpor Data...";

    try {
        const res = await fetch(form.action, {
            method: "POST",
            headers: { Accept: "application/json", "X-Requested-With": "XMLHttpRequest" },
            body: new FormData(form),
        });

        const json = await res.json();

        if (res.ok && json.status === "success") {
            window.closeImportNonAsnModal();

            let detailHtml = escapeHtmlNonAsn(json.message);
            const galat = json.summary?.galat || [];
            if (galat.length > 0) {
                detailHtml +=
                    "<br><br><small style='color:#f59e0b; text-align:left; display:block; max-height:140px; overflow-y:auto;'>" +
                    galat.map((g) => escapeHtmlNonAsn(g)).join("<br>") +
                    "</small>";
            }

            if (window.Swal) {
                await Swal.fire({ title: "Import Berhasil!", html: detailHtml, icon: "success", confirmButtonColor: "#10b981" });
            }
            window.location.reload();
        } else {
            let html = escapeHtmlNonAsn(json.message || "Gagal mengimpor berkas.");
            if (json.errors && typeof json.errors === "object") {
                const flat = Object.values(json.errors).flat().join("<br>");
                html +=
                    "<br><br><small style='color:#ef4444; text-align:left; display:block;'>" +
                    escapeHtmlNonAsn(flat) +
                    "</small>";
            }
            window.Swal
                ? Swal.fire({ title: "Gagal Mengimpor", html: html, icon: "error", confirmButtonColor: "#ef4444" })
                : alert(json.message || "Gagal mengimpor berkas.");
        }
    } catch (err) {
        const msg = "Gagal terhubung ke server: " + (err.message || "kesalahan tidak diketahui");
        window.Swal ? Swal.fire({ title: "Kesalahan Jaringan", text: msg, icon: "error" }) : alert(msg);
    } finally {
        if (progress) progress.style.display = "none";
        if (btnSubmit) btnSubmit.disabled = false;
        if (btnText) btnText.textContent = "Import Sekarang";
    }
};

/* -------------------------------------------------------------------- */

function escapeHtmlNonAsn(str) {
    const div = document.createElement("div");
    div.textContent = String(str ?? "");
    return div.innerHTML;
}

// Tutup modal dengan klik backdrop / tombol Escape
document.addEventListener("click", function (e) {
    if (e.target && e.target.id === "modalNonAsn") window.closeNonAsnModal();
    if (e.target && e.target.id === "modalImportNonAsn") window.closeImportNonAsnModal();
});

document.addEventListener("keydown", function (e) {
    if (e.key !== "Escape") return;
    window.closeNonAsnModal();
    window.closeImportNonAsnModal();
});
