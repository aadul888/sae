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
    const filterGender = document.getElementById("filterGender");
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
        if (filterGender) {
            if (filterGender.value) url.searchParams.set("gender", filterGender.value);
            else url.searchParams.delete("gender");
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
            (e.target.id === "filterKonfirmasi" || e.target.id === "filterBerkas" || e.target.id === "filterGender" || e.target.id === "adminRombelSelect" || e.target.id === "perPageSelect")
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
            location.reload();
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
                location.reload();
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

        berkasStatusChanged = false;
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

            const rekomendasiList = json.rekomendasi_penolakan || [];
            let html = `<div style="display: flex; flex-direction: column; gap: 16px;">`;

            json.items.forEach((item) => {
                const statusBadge =
                    item.status === "valid"
                        ? `<span class="badge badge-success" style="font-size: 0.74rem; padding: 3px 8px;"><i class="fas fa-circle-check me-1"></i> Valid</span>`
                        : item.status === "tidak_valid"
                          ? `<span class="badge badge-danger" style="font-size: 0.74rem; padding: 3px 8px;"><i class="fas fa-circle-xmark me-1"></i> Tidak Valid</span>`
                          : item.is_uploaded
                            ? `<span class="badge badge-warning" style="font-size: 0.74rem; padding: 3px 8px;"><i class="fas fa-clock me-1"></i> Menunggu</span>`
                            : `<span class="badge badge-outline" style="font-size: 0.74rem; padding: 3px 8px;"><i class="fas fa-minus me-1"></i> Belum Unggah</span>`;

                html += `
                    <div class="card card-berkas-item" id="cardBerkas_${item.jenis_berkas}" data-key="${item.jenis_berkas}" data-selected-status="${item.status || ''}" style="padding: 14px 16px; border-radius: var(--radius-lg, 12px); border: 1px solid var(--border-color); background: var(--card-bg, #fff);">
                        <!-- Header Berkas & Status -->
                        <div style="display: flex; justify-content: space-between; align-items: center; gap: 10px; margin-bottom: 10px;">
                            <div style="display: flex; align-items: center; gap: 10px; min-width: 0;">
                                <div style="width: 34px; height: 34px; border-radius: var(--radius-md, 8px); background: rgba(99,102,241,0.12); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 1rem; flex-shrink: 0;">
                                    <i class="fas ${item.icon}"></i>
                                </div>
                                <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                                    <span style="font-weight: 700; font-size: 0.92rem; color: var(--text-color);">${escapeHtml(item.label)}</span>
                                    ${item.wajib ? '<span class="badge badge-outline" style="font-size: 0.65rem; padding: 1px 6px;">Wajib</span>' : ''}
                                </div>
                            </div>
                            <div class="status-badge-container">${statusBadge}</div>
                        </div>

                        <!-- Info File & Aksi Dokumen (Dropzone: tarik & lepas file di sini) -->
                        <div data-upload-dropzone="1" title="${canUpload ? 'Tarik & lepas file ke area ini, atau klik Unggah' : ''}" style="display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 8px 12px; background: var(--bg-hover); border: 1px solid var(--border-color); border-radius: var(--radius-md, 8px); margin-bottom: 12px; flex-wrap: wrap;">
                            <div style="display: flex; align-items: center; gap: 8px; min-width: 0; font-size: var(--text-sm, 0.8125rem);">
                                ${item.is_uploaded ? `
                                    <i class="fas fa-file-pdf text-danger" style="font-size: 1.15rem; flex-shrink: 0;"></i>
                                    <span style="font-weight: 600; color: var(--text-color); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 240px;" title="${escapeHtml(item.file_name)}">${escapeHtml(item.file_name)}</span>
                                    <span class="text-muted" style="font-size: var(--text-xs, 0.75rem);">(${item.file_size})</span>
                                ` : `
                                    <span class="text-muted" style="font-size: var(--text-sm, 0.8125rem);"><i class="fas fa-minus-circle me-1"></i>Belum ada file digital</span>
                                `}
                            </div>
                            <div style="display: flex; align-items: center; gap: 6px;">
                                ${item.is_uploaded ? `
                                    <button type="button" class="btn btn-outline btn-preview-pdf-inline" data-url="${item.file_url}" data-title="${escapeHtml(item.label)} - ${escapeHtml(siswa.nama)}" data-pd-id="${id}" data-key="${item.jenis_berkas}" data-label="${escapeHtml(item.label)}" data-status="${item.status || ''}" data-catatan="${escapeHtml(item.catatan_penolakan || '')}" style="padding: 4px 10px; font-size: var(--text-xs, 0.75rem); border-radius: var(--radius-md, 6px); cursor: pointer;">
                                        <i class="fas fa-eye me-1"></i> Lihat PDF
                                    </button>
                                ` : ''}
                                ${canUpload ? `
                                    <button type="button" class="btn btn-outline btn-upload-berkas" data-upload-trigger="1" data-key="${item.jenis_berkas}" style="padding: 4px 10px; font-size: var(--text-xs, 0.75rem); border-radius: var(--radius-md, 6px); cursor: pointer; display: inline-flex; align-items: center; gap: 4px;">
                                        <i class="fas fa-upload"></i> ${item.is_uploaded ? 'Ganti' : 'Unggah'}
                                    </button>
                                    <input type="file" class="input-upload-berkas" data-pd-id="${id}" data-key="${item.jenis_berkas}" data-label="${escapeHtml(item.label)}" accept=".pdf,.jpg,.jpeg,.png" style="display: none;">
                                ` : ''}
                            </div>
                        </div>

                        <!-- Baris Kontrol Validasi & Simpan -->
                        <div style="display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap;">
                            <div style="display: flex; gap: 16px; align-items: center;">
                                <label style="display: inline-flex; align-items: center; gap: 6px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--success, #10b981); cursor: pointer; margin: 0;">
                                    <input type="radio" name="status_${item.jenis_berkas}" value="valid" ${item.status === 'valid' ? 'checked' : ''} class="radio-status-berkas" data-key="${item.jenis_berkas}">
                                    <i class="fas fa-circle-check"></i> Valid
                                </label>
                                <label style="display: inline-flex; align-items: center; gap: 6px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--danger, #ef4444); cursor: pointer; margin: 0;">
                                    <input type="radio" name="status_${item.jenis_berkas}" value="tidak_valid" ${item.status === 'tidak_valid' ? 'checked' : ''} class="radio-status-berkas" data-key="${item.jenis_berkas}">
                                    <i class="fas fa-circle-xmark"></i> Tidak Valid
                                </label>
                            </div>
                            <button type="button" class="btn btn-primary btn-save-verif-berkas" data-pd-id="${id}" data-key="${item.jenis_berkas}" data-label="${escapeHtml(item.label)}" style="padding: 5px 14px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; border-radius: var(--radius-md, 6px); display: inline-flex; align-items: center; gap: 6px;">
                                <i class="fas fa-check"></i> Simpan
                            </button>
                        </div>

                        <!-- Area Catatan Penolakan (Hanya muncul jika status tidak_valid) -->
                        <div id="boxPenolakan_${item.jenis_berkas}" style="display: ${item.status === 'tidak_valid' ? 'block' : 'none'}; margin-top: 10px; padding-top: 10px; border-top: 1px dashed var(--border-color);">
                            ${rekomendasiList.length ? `
                                <div style="display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 8px;">
                                    ${rekomendasiList.map(rek => `
                                        <button type="button" class="btn btn-outline btn-chip-rekomendasi" data-target-text="textarea_${item.jenis_berkas}" data-text="${escapeHtml(rek)}" style="padding: 2px 8px; font-size: var(--text-xs, 0.75rem); border-radius: var(--radius-md, 6px); cursor: pointer;">
                                            ${escapeHtml(rek)}
                                        </button>
                                    `).join('')}
                                </div>
                            ` : ''}
                            <textarea id="textarea_${item.jenis_berkas}" class="form-control input-catatan-penolakan" rows="2" placeholder="Catatan perbaikan untuk siswa..." style="font-size: var(--text-sm, 0.8125rem); width: 100%; border-radius: var(--radius-md, 6px);">${escapeHtml(item.catatan_penolakan || '')}</textarea>
                        </div>
                    </div>
                `;
            });

            html += `</div>`;
            content.innerHTML = html;

            // Wire up radio toggle show/hide rejection box & card state sync
            content.querySelectorAll(".radio-status-berkas").forEach((radio) => {
                radio.addEventListener("change", function () {
                    const key = this.getAttribute("data-key");
                    const box = document.getElementById(`boxPenolakan_${key}`);
                    if (box) {
                        box.style.display = this.value === "tidak_valid" ? "block" : "none";
                    }
                    const card = document.getElementById(`cardBerkas_${key}`);
                    if (card) {
                        card.setAttribute("data-selected-status", this.value);
                    }
                });
            });

            // Wire up recommendation chips
            content.querySelectorAll(".btn-chip-rekomendasi").forEach((chip) => {
                chip.addEventListener("click", function () {
                    const targetId = this.getAttribute("data-target-text");
                    const text = this.getAttribute("data-text");
                    const textarea = document.getElementById(targetId);
                    if (textarea) {
                        textarea.value = text;
                        textarea.focus();
                    }
                });
            });

            // Wire up upload mandiri per berkas oleh Wali Kelas
            content.querySelectorAll(".input-upload-berkas").forEach((input) => {
                input.addEventListener("change", async function () {
                    const file = this.files && this.files[0];
                    if (!file) return;

                    const pdId = this.getAttribute("data-pd-id");
                    const key = this.getAttribute("data-key");
                    const card = document.getElementById(`cardBerkas_${key}`);
                    const triggerBtn = card ? card.querySelector(".btn-upload-berkas") : null;

                    if (file.size > 10 * 1024 * 1024) {
                        Swal.fire("File Terlalu Besar", "Ukuran file maksimal 10 MB.", "warning");
                        this.value = "";
                        return;
                    }

                    const csrfToken =
                        document.querySelector('meta[name="csrf-token"]')?.content ||
                        document.querySelector('input[name="_token"]')?.value ||
                        "";

                    if (triggerBtn) {
                        triggerBtn.disabled = true;
                        triggerBtn.style.opacity = "0.6";
                        triggerBtn.style.pointerEvents = "none";
                    }

                    const formData = new FormData();
                    formData.append("jenis_berkas", key);
                    formData.append("file", file);

                    try {
                        const res = await fetch(
                            `/dashboard/wali-kelas/validasi-data/berkas-upload/${encodeURIComponent(pdId)}`,
                            {
                                method: "POST",
                                headers: {
                                    "X-CSRF-TOKEN": csrfToken,
                                    Accept: "application/json",
                                    "X-Requested-With": "XMLHttpRequest",
                                },
                                body: formData,
                            },
                        );
                        const json = await res.json();

                        if (res.ok && json.status === "success") {
                            berkasStatusChanged = true;
                            await Swal.fire({
                                icon: "success",
                                title: "Berkas Terunggah!",
                                text: json.message,
                                timer: 1400,
                                showConfirmButton: false,
                            });
                            window.openModalVerifikasiBerkas(id, siswa.nama);
                            return;
                        }

                        const errMsg = json.message || (json.errors ? Object.values(json.errors).flat().join(" ") : "Gagal mengunggah berkas.");
                        Swal.fire("Gagal Unggah", errMsg, "error");
                    } catch (err) {
                        Swal.fire("Kesalahan Jaringan", "Tidak dapat terhubung ke server.", "error");
                    } finally {
                        if (triggerBtn) {
                            triggerBtn.disabled = false;
                            triggerBtn.style.opacity = "";
                            triggerBtn.style.pointerEvents = "";
                        }
                        this.value = "";
                    }
                });
            });

            // Wire up save button per berkas
            content.querySelectorAll(".btn-save-verif-berkas").forEach((btn) => {
                btn.addEventListener("click", async function () {
                    const pdId = this.getAttribute("data-pd-id");
                    const key = this.getAttribute("data-key");
                    const label = this.getAttribute("data-label");
                    const card = document.getElementById(`cardBerkas_${key}`) || this.closest(".card-berkas-item");
                    const selectedRadio = card
                        ? card.querySelector(`input[name="status_${key}"]:checked`)
                        : content.querySelector(`input[name="status_${key}"]:checked`);
                    const status = selectedRadio ? selectedRadio.value : (card ? card.getAttribute("data-selected-status") : "");

                    if (!status || (status !== "valid" && status !== "tidak_valid")) {
                        Swal.fire("Peringatan", "Silakan pilih status validasi (Valid atau Tidak Valid).", "warning");
                        return;
                    }

                    const textarea = document.getElementById(`textarea_${key}`);
                    const catatan = textarea ? textarea.value.trim() : "";

                    if (status === "tidak_valid" && !catatan) {
                        Swal.fire(
                            "Wajib Catatan",
                            "Untuk status Tidak Valid / Tidak Sesuai, Anda wajib mengisi catatan atau memilih rekomendasi alasan penolakan.",
                            "warning",
                        );
                        if (textarea) textarea.focus();
                        return;
                    }

                    const originalBtnText = this.innerHTML;
                    this.disabled = true;
                    this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';

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
                        this.disabled = false;
                        this.innerHTML = originalBtnText;

                        if (postRes.ok && postJson.status === "success") {
                            berkasStatusChanged = true;
                            Swal.fire({
                                icon: "success",
                                title: "Status Disimpan!",
                                text: postJson.message,
                                timer: 1500,
                                showConfirmButton: false,
                            });

                            // Update status badge & radio state on card langsung tanpa reload modal
                            if (card) {
                                const badgeContainer = card.querySelector(".status-badge-container");
                                if (badgeContainer) {
                                    badgeContainer.innerHTML =
                                        status === "valid"
                                            ? `<span class="badge badge-success" style="font-size: 0.74rem; padding: 3px 8px;"><i class="fas fa-circle-check me-1"></i> Valid</span>`
                                            : `<span class="badge badge-danger" style="font-size: 0.74rem; padding: 3px 8px;"><i class="fas fa-circle-xmark me-1"></i> Tidak Valid</span>`;
                                }
                                card.setAttribute("data-selected-status", status);
                                const currentRadio = card.querySelector(`input[name="status_${key}"][value="${status}"]`);
                                if (currentRadio) currentRadio.checked = true;
                                const prevBtn = card.querySelector(".btn-preview-pdf-inline");
                                if (prevBtn) {
                                    prevBtn.setAttribute("data-status", status);
                                    prevBtn.setAttribute("data-catatan", catatan);
                                }
                            }
                        } else {
                            Swal.fire("Gagal Validasi", postJson.message || "Terjadi kesalahan sistem.", "error");
                        }
                    } catch (err) {
                        this.disabled = false;
                        this.innerHTML = originalBtnText;
                        Swal.fire("Kesalahan Jaringan", "Tidak dapat terhubung ke server.", "error");
                    }
                });
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
