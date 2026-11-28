/**
 * Wali Kelas — Validasi Data Peserta Didik
 * Modular JavaScript: Datatable Filter, Live Search, Validasi Berkas Digital & PDF Viewer Terpadu
 */

function escapeHtml(str) {
    if (!str) return "-";
    return String(str)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

document.addEventListener("DOMContentLoaded", function () {
    // ==========================================
    // 1. FILTER & LIVE SEARCH
    // ==========================================
    const perPageSelect = document.getElementById("perPageSelect");
    const filterKonfirmasi = document.getElementById("filterKonfirmasi");
    const filterBerkas = document.getElementById("filterBerkas");
    const adminRombelSelect = document.getElementById("adminRombelSelect");

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

        if (perPageSelect) {
            url.searchParams.set("perPage", perPageSelect.value);
        }
        if (filterKonfirmasi) {
            if (filterKonfirmasi.value && filterKonfirmasi.value !== "semua") url.searchParams.set("konfirmasi", filterKonfirmasi.value);
            else url.searchParams.delete("konfirmasi");
        }
        if (filterBerkas) {
            if (filterBerkas.value) url.searchParams.set("berkas", filterBerkas.value);
            else url.searchParams.delete("berkas");
        }
        if (adminRombelSelect) {
            if (adminRombelSelect.value) url.searchParams.set("rombel_id", adminRombelSelect.value);
            else url.searchParams.delete("rombel_id");
        }

        const searchEl = document.getElementById("liveSearch");
        if (searchEl) {
            const qVal = searchEl.value.trim();
            if (qVal) url.searchParams.set("q", qVal);
            else url.searchParams.delete("q");
        }

        Object.keys(overrideParams).forEach((k) => {
            const v = overrideParams[k];
            if (v !== null && v !== undefined && v !== "") url.searchParams.set(k, v);
            else url.searchParams.delete(k);
        });

        // Reset pagination to page 1 on filter/search change
        if (!("page" in overrideParams)) {
            url.searchParams.delete("page");
        }

        if (typeof window.refreshLiveTable === "function") {
            window.refreshLiveTable(url.toString());
        } else {
            window.location.href = url.toString();
        }
    }

    // Delegated Change Handler untuk filter Validasi Data
    document.addEventListener("change", function (e) {
        if (!e.target || e.target.tagName !== "SELECT") return;
        if (
            e.target.closest("#tableDataContainer, .datatable-toolbar") &&
            (e.target.id === "filterKonfirmasi" || e.target.id === "filterBerkas" || e.target.id === "adminRombelSelect" || e.target.id === "perPageSelect")
        ) {
            e.preventDefault();
            e.stopPropagation();
            applyFilter();
        }
    });

    // Live search with 450ms debounce
    let searchDebounce = null;
    document.addEventListener("input", function (e) {
        if (e.target && e.target.id === "liveSearch") {
            const cBtn = document.getElementById("clearSearch");
            if (cBtn) {
                cBtn.classList.toggle("visible", e.target.value.trim().length > 0);
            }
            clearTimeout(searchDebounce);
            searchDebounce = setTimeout(() => {
                applyFilter();
            }, 450);
        }
    });

    document.addEventListener("keydown", function (e) {
        if (e.target && e.target.id === "liveSearch" && e.key === "Enter") {
            e.preventDefault();
            clearTimeout(searchDebounce);
            applyFilter();
        }
    });

    document.addEventListener("click", function (e) {
        const cBtn = e.target.closest("#clearSearch");
        if (cBtn) {
            const sEl = document.getElementById("liveSearch");
            if (sEl) {
                sEl.value = "";
                cBtn.classList.remove("visible");
                sEl.focus();
            }
            applyFilter({ q: "" });
        }
    });

    // Sortable Column Headers
    document.addEventListener("click", function (e) {
        const th = e.target.closest(".sortable-th");
        if (!th) return;

        const sortField = th.getAttribute("data-sort");
        if (!sortField) return;

        const url = new URL(window.location.href);
        const currentSort = url.searchParams.get("sort") || "nama";
        const currentDir = url.searchParams.get("sort_dir") || "asc";

        let nextDir = "asc";
        if (currentSort === sortField) {
            nextDir = currentDir === "asc" ? "desc" : "asc";
        }

        applyFilter({ sort: sortField, sort_dir: nextDir });
    });

    // ==========================================
    // 2. MODAL VALIDASI BERKAS DIGITAL SISWA
    // ==========================================
    let berkasStatusChanged = false;

    // Delegated click event listener untuk tombol Validasi Berkas
    document.addEventListener("click", function (e) {
        const btn = e.target.closest(".btn-validasi-berkas-detail");
        if (btn) {
            e.preventDefault();
            e.stopPropagation();
            const id = btn.getAttribute("data-id");
            const nama = btn.getAttribute("data-nama");
            if (id && typeof window.openModalVerifikasiBerkas === "function") {
                window.openModalVerifikasiBerkas(id, nama);
            }
        }
    });

    // Delegated close listener for modalVerifikasiBerkasDigital & backdrop/ESC sync
    function handleModalVbClose() {
        const modal = document.getElementById("modalVerifikasiBerkasDigital");
        if (modal) {
            modal.style.display = "none";
            document.body.style.overflow = "";
        }
        if (berkasStatusChanged) {
            berkasStatusChanged = false;
            window.refreshLiveTable ? window.refreshLiveTable(location.href) : location.reload();
        }
    }

    document.addEventListener("click", function (e) {
        const closeBtn = e.target.closest('.close-modal[data-target="#modalVerifikasiBerkasDigital"]');
        if (closeBtn) {
            e.preventDefault();
            handleModalVbClose();
        }
    });

    const modalVbEl = document.getElementById("modalVerifikasiBerkasDigital");
    if (modalVbEl) {
        modalVbEl.addEventListener("click", function (e) {
            if (e.target === modalVbEl) {
                handleModalVbClose();
            }
        });
    }

    document.addEventListener("keydown", function (e) {
        if (e.key === "Escape") {
            const modalVb = document.getElementById("modalVerifikasiBerkasDigital");
            const modalPdf = document.getElementById("modalPdfViewer");
            if (modalPdf && modalPdf.style.display !== "none") {
                window.closePdfViewer();
                return;
            }
            if (modalVb && modalVb.style.display !== "none" && berkasStatusChanged) {
                handleModalVbClose();
            }
        }
    });

    window.openModalVerifikasiBerkas = async function (id, nama) {
        const modal = document.getElementById("modalVerifikasiBerkasDigital");
        const loading = document.getElementById("modalVbLoading");
        const content = document.getElementById("modalVbContent");
        const namaEl = document.getElementById("modalVbNama");
        const infoEl = document.getElementById("modalVbInfo");

        if (!modal) return;

        // JANGAN reset berkasStatusChanged: modal bisa dibuka ulang setelah unggah/hapus.
        // Flag direset hanya saat refresh tabel benar-benar dijalankan.
        modal.style.display = "flex";
        document.body.style.overflow = "hidden";
        loading.style.display = "block";
        content.style.display = "none";
        content.innerHTML = "";
        namaEl.textContent = `Validasi Berkas: ${nama || "Peserta Didik"}`;
        infoEl.textContent = "Memuat data siswa dan berkas digital...";

        try {
            const res = await fetch(`/dashboard/wali-kelas/validasi-data/berkas-detail/${encodeURIComponent(id)}`, {
                headers: {
                    Accept: "application/json",
                    "X-Requested-With": "XMLHttpRequest",
                },
            });
            const json = await res.json();

            loading.style.display = "none";
            content.style.display = "block";

            if (json.status !== "success") {
                Swal.fire("Error", json.message || "Gagal memuat berkas siswa.", "error");
                modal.style.display = "none";
                document.body.style.overflow = "";
                return;
            }

            const siswa = json.siswa;
            const canUpload = modal?.getAttribute("data-can-update") === "1";
            infoEl.innerHTML = `NISN: <strong>${escapeHtml(siswa.nisn)}</strong> &bull; NIPD: <strong>${escapeHtml(siswa.nipd)}</strong> &bull; Rombel: <strong>${escapeHtml(siswa.rombel)}</strong>`;

            // Kartu stepper + PDF inline 2 kolom ditangani bersama oleh validasi-berkas-modal.js
            const rekomendasiList = json.rekomendasi_penolakan || [];
            window.SaeVbModal.render(content, {
                items: json.items,
                siswa,
                pdId: id,
                rekomendasiList,
                canUpload,
                uploadUrl: (pid) => `/dashboard/wali-kelas/validasi-data/berkas-upload/${encodeURIComponent(pid)}`,
                verifyUrl: (pid) => `/dashboard/wali-kelas/validasi-data/berkas-verifikasi/${encodeURIComponent(pid)}`,
                csrf: () => document.querySelector('meta[name="csrf-token"]')?.content || document.querySelector('input[name="_token"]')?.value || '',
                reopen: (pid, nm) => window.openModalVerifikasiBerkas(pid, nm),
                isAdmin: json.is_admin === true,
                onChanged: () => { berkasStatusChanged = true; },
                finish: () => { berkasStatusChanged = false; modal.style.display = 'none'; document.body.style.overflow = ''; window.refreshLiveTable ? window.refreshLiveTable(location.href) : location.reload(); },
            });
        } catch (err) {
            loading.style.display = "none";
            Swal.fire("Kesalahan Jaringan", "Gagal memuat berkas siswa.", "error");
            modal.style.display = "none";
            document.body.style.overflow = "";
        }
    };

    // ==========================================
    // 3. PRATINJAU DOKUMEN PDF TERPADU
    // ==========================================
    let activePdfViewerData = null;

    window.openPdfViewer = function (url, title, pdId = null, key = null, label = null, currentStatus = null, catatan = null) {
        const modal = document.getElementById("modalPdfViewer");
        const frame = document.getElementById("pdfViewerFrame");
        const titleEl = document.getElementById("pdfViewerTitle");
        if (!modal || !frame) return;

        if (titleEl) titleEl.textContent = title || "Pratinjau Dokumen PDF";
        frame.src = url;

        activePdfViewerData = pdId && key ? { pdId, key, label, currentStatus, catatan } : null;

        const controls = document.getElementById("pdfViewerVerifControls");
        const radioValid = document.getElementById("pdfViewerRadioValid");
        const radioTidakValid = document.getElementById("pdfViewerRadioTidakValid");
        const catatanInput = document.getElementById("pdfViewerCatatan");

        if (controls) {
            if (activePdfViewerData) {
                controls.style.display = "flex";
                const card = document.getElementById(`cardBerkas_${key}`);
                const effectiveStatus = card ? (card.getAttribute("data-selected-status") || currentStatus) : currentStatus;

                if (radioValid) radioValid.checked = (effectiveStatus === "valid");
                if (radioTidakValid) radioTidakValid.checked = (effectiveStatus === "tidak_valid");

                if (catatanInput) {
                    const existingCatatan = document.getElementById(`textarea_${key}`)?.value?.trim() || catatan || "";
                    catatanInput.value = existingCatatan;
                    catatanInput.style.display = (effectiveStatus === "tidak_valid") ? "inline-block" : "none";
                }
            } else {
                controls.style.display = "none";
            }
        }

        modal.style.display = "flex";
        document.body.style.overflow = "hidden";
    };

    window.closePdfViewer = function () {
        const modal = document.getElementById("modalPdfViewer");
        const frame = document.getElementById("pdfViewerFrame");
        if (modal) modal.style.display = "none";
        if (frame) frame.src = "";

        const closedKey = activePdfViewerData?.key;
        activePdfViewerData = null;

        // Restore active window focus immediately so parent clicks don't get swallowed
        window.focus();

        // Jika modal verifikasi masih aktif, pertahankan overflow hidden
        const modalVb = document.getElementById("modalVerifikasiBerkasDigital");
        if (!modalVb || modalVb.style.display === "none") {
            document.body.style.overflow = "";
        } else if (closedKey) {
            const card = document.getElementById(`cardBerkas_${closedKey}`);
            if (card) {
                card.scrollIntoView({ behavior: "smooth", block: "nearest" });
            }
        }
    };

    // Listener interaksi radio & simpan langsung di viewer PDF untuk wali kelas
    document.addEventListener("change", function (e) {
        if (e.target && e.target.classList.contains("pdf-viewer-radio")) {
            const catatanInput = document.getElementById("pdfViewerCatatan");
            if (catatanInput) {
                catatanInput.style.display = e.target.value === "tidak_valid" ? "inline-block" : "none";
                if (e.target.value === "tidak_valid") catatanInput.focus();
            }
            if (activePdfViewerData?.key) {
                const card = document.getElementById(`cardBerkas_${activePdfViewerData.key}`);
                if (card) {
                    card.setAttribute("data-selected-status", e.target.value);
                    const cardRadio = card.querySelector(`input[name="status_${activePdfViewerData.key}"][value="${e.target.value}"]`);
                    if (cardRadio) {
                        cardRadio.checked = true;
                        cardRadio.dispatchEvent(new Event("change", { bubbles: true }));
                    }
                }
            }
        }
    });

    document.addEventListener("click", async function (e) {
        const btnSimpan = e.target.closest("#btnPdfViewerSimpan");
        if (btnSimpan && activePdfViewerData) {
            e.preventDefault();
            const { pdId, key, label } = activePdfViewerData;
            const selectedRadio = document.querySelector('input[name="pdf_viewer_status"]:checked');
            if (!selectedRadio) {
                Swal.fire("Peringatan", "Silakan pilih status validasi (Valid atau Tidak Valid).", "warning");
                return;
            }

            const status = selectedRadio.value;
            const catatanInput = document.getElementById("pdfViewerCatatan");
            const catatan = catatanInput ? catatanInput.value.trim() : "";

            if (status === "tidak_valid" && !catatan) {
                Swal.fire("Wajib Catatan", "Untuk status Tidak Valid / Tidak Sesuai, Anda wajib mengisi catatan penolakan.", "warning");
                if (catatanInput) catatanInput.focus();
                return;
            }

            const origText = btnSimpan.innerHTML;
            btnSimpan.disabled = true;
            btnSimpan.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';

            const csrfToken =
                document.querySelector('meta[name="csrf-token"]')?.content ||
                document.querySelector('input[name="_token"]')?.value ||
                "";

            try {
                const postRes = await fetch(
                    `/dashboard/wali-kelas/validasi-data/berkas-verifikasi/${encodeURIComponent(pdId)}`,
                    {
                        method: "POST",
                        headers: {
                            "X-CSRF-TOKEN": csrfToken,
                            "Content-Type": "application/json",
                            Accept: "application/json",
                            "X-Requested-With": "XMLHttpRequest",
                        },
                        body: JSON.stringify({
                            jenis_berkas: key,
                            status: status,
                            catatan_penolakan: catatan,
                            rekomendasi_penolakan: catatan,
                        }),
                    },
                );

                const postJson = await postRes.json();
                btnSimpan.disabled = false;
                btnSimpan.innerHTML = origText;

                if (postRes.ok && postJson.status === "success") {
                    berkasStatusChanged = true;
                    const card = document.getElementById(`cardBerkas_${key}`);
                    if (card) {
                        const badgeContainer = card.querySelector(".status-badge-container");
                        if (badgeContainer) {
                            badgeContainer.innerHTML =
                                status === "valid"
                                    ? `<span class="badge badge-success" style="font-size: 0.74rem; padding: 4px 10px;"><i class="fas fa-circle-check me-1"></i> Valid / Sesuai</span>`
                                    : `<span class="badge badge-danger" style="font-size: 0.74rem; padding: 4px 10px;"><i class="fas fa-circle-xmark me-1"></i> Tidak Valid / Tidak Sesuai</span>`;
                        }
                        card.setAttribute("data-selected-status", status);
                        const cardRadio = card.querySelector(`input[name="status_${key}"][value="${status}"]`);
                        if (cardRadio) {
                            cardRadio.checked = true;
                            cardRadio.dispatchEvent(new Event("change", { bubbles: true }));
                        }
                        const cardTextarea = document.getElementById(`textarea_${key}`);
                        if (cardTextarea && catatan) {
                            cardTextarea.value = catatan;
                        }
                    }

                    window.closePdfViewer();

                    Swal.fire({
                        icon: "success",
                        title: "Status Disimpan!",
                        text: postJson.message,
                        timer: 1500,
                        showConfirmButton: false,
                    });
                } else {
                    Swal.fire("Gagal Validasi", postJson.message || "Terjadi kesalahan sistem.", "error");
                }
            } catch (err) {
                btnSimpan.disabled = false;
                btnSimpan.innerHTML = origText;
                Swal.fire("Kesalahan Jaringan", "Tidak dapat terhubung ke server.", "error");
            }
        }
    });

    document.addEventListener("click", function (e) {
        const btn = e.target.closest(".btn-preview-pdf-inline");
        if (btn) {
            e.preventDefault();
            e.stopPropagation();
            const url = btn.getAttribute("data-url");
            const title = btn.getAttribute("data-title");
            const pdId = btn.getAttribute("data-pd-id");
            const key = btn.getAttribute("data-key");
            const label = btn.getAttribute("data-label");
            const status = btn.getAttribute("data-status");
            const catatan = btn.getAttribute("data-catatan");
            if (url) window.openPdfViewer(url, title, pdId, key, label, status, catatan);
        }

        const closeBtn = e.target.closest(".close-pdf-viewer");
        if (closeBtn) {
            e.preventDefault();
            e.stopPropagation();
            window.closePdfViewer();
        }
    });
});
