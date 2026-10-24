function escapeHtml(str) {
    if (str === null || str === undefined) return "";
    return String(str).replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
}

// ==========================================================================
// 4. BULK / BATCH UPLOAD PASFOTO PESERTA DIDIK PER KELAS (36 - 52 PESERTA DIDIK)
// ==========================================================================
let bulkStudents = [];
let bulkFileMatches = {}; // { peserta_didik_id: { file: File, previewUrl: string, matchedBy: string } }
let isBulkUploading = false;
let bulkZipFile = null; // ZIP file pending upload

window.openBulkUploadFotoModal = function (defaultRombel) {
    const modal = document.getElementById("bulkFotoUploadModal");
    if (!modal) return;

    modal.style.display = "flex";

    const select = document.getElementById("bulkRombelSelect");
    if (select) {
        if (defaultRombel) {
            select.value = defaultRombel;
            handleBulkRombelChange(defaultRombel);
        } else if (select.value) {
            handleBulkRombelChange(select.value);
        }
    }
};

window.closeBulkUploadFotoModal = function () {
    if (isBulkUploading) {
        const leave = confirm(
            "Proses unggah masal sedang berlangsung. Yakin ingin menutup?",
        );
        if (!leave) return;
    }

    const modal = document.getElementById("bulkFotoUploadModal");
    if (modal) modal.style.display = "none";
};

window.handleBulkRombelChange = async function (rombelName) {
    bulkStudents = [];
    bulkFileMatches = {};

    const statsBadge = document.getElementById("bulkRombelStatsBadge");
    const dropzoneWrap = document.getElementById("bulkDropZoneWrapper");
    const summaryBanner = document.getElementById("bulkMatchSummaryBanner");
    const progressWrap = document.getElementById("bulkProgressContainer");
    const loadingSpinner = document.getElementById("bulkLoadingSpinner");
    const emptyState = document.getElementById("bulkEmptyState");
    const tableWrap = document.getElementById("bulkTableWrapper");
    const btnStart = document.getElementById("btnStartBulkUpload");

    if (summaryBanner) summaryBanner.style.display = "none";
    if (progressWrap) progressWrap.style.display = "none";
    if (btnStart) {
        btnStart.disabled = true;
        document.getElementById("btnStartBulkUploadText").textContent =
            "Mulai Unggah & Kompresi Masal (0 Foto)";
    }

    if (!rombelName) {
        if (statsBadge) statsBadge.style.display = "none";
        if (dropzoneWrap) dropzoneWrap.style.display = "none";
        if (tableWrap) tableWrap.style.display = "none";
        if (emptyState) emptyState.style.display = "block";
        return;
    }

    if (emptyState) emptyState.style.display = "none";
    if (tableWrap) tableWrap.style.display = "none";
    if (dropzoneWrap) dropzoneWrap.style.display = "none";
    if (loadingSpinner) loadingSpinner.style.display = "block";

    try {
        const res = await fetch(
            "/dashboard/manajemen-data/peserta-didik-aktif/rombel-members?rombel=" +
                encodeURIComponent(rombelName),
            {
                headers: { Accept: "application/json" },
            },
        );
        const json = await res.json();

        if (json.status === "success") {
            bulkStudents = json.data || [];

            // Update statistik rombel
            const totalPdEl = document.getElementById("bulkTotalPdCount");
            if (totalPdEl) totalPdEl.textContent = json.total || 0;
            document.getElementById("bulkSudahFotoCount").textContent =
                json.total_with_foto || 0;
            document.getElementById("bulkBelumFotoCount").textContent =
                json.total_without_foto || 0;
            if (statsBadge) statsBadge.style.display = "flex";

            if (dropzoneWrap) dropzoneWrap.style.display = "block";
            if (tableWrap) tableWrap.style.display = "block";

            renderBulkStudentsTable();
        } else {
            throw new Error(
                json.message || "Gagal memuat peserta didik rombel.",
            );
        }
    } catch (err) {
        if (window.SAE && typeof window.SAE.toast === "function") {
            window.SAE.toast(
                err.message || "Gagal memuat peserta didik.",
                "danger",
            );
        } else {
            alert(err.message);
        }
    } finally {
        if (loadingSpinner) loadingSpinner.style.display = "none";
    }
};

function renderBulkStudentsTable() {
    const tbody = document.getElementById("bulkStudentsTableBody");
    if (!tbody) return;

    if (bulkStudents.length === 0) {
        tbody.innerHTML = `<tr><td colspan="7" style="padding: 30px; text-align: center; color: var(--text-muted);">Tidak ada peserta didik di kelas ini.</td></tr>`;
        return;
    }

    let html = "";
    bulkStudents.forEach(function (s) {
        const match = bulkFileMatches[s.peserta_didik_id];
        const hasExisting = Boolean(s.foto_url);

        // Thumbnail foto lama
        let oldThumbHtml = "";
        if (hasExisting) {
            oldThumbHtml = `<div style="width: 38px; height: 38px; border-radius: 8px; border: 1px solid var(--border-color); overflow: hidden; background: #182030; margin: 0 auto;">
                <img src="${s.foto_url}" alt="Foto" style="width: 100%; height: 100%; object-fit: cover;">
            </div>`;
        } else {
            oldThumbHtml = `<div style="width: 38px; height: 38px; border-radius: 8px; border: 1px dashed rgba(255,255,255,0.15); display: flex; align-items: center; justify-content: center; color: var(--text-muted); font-size: 0.7rem; margin: 0 auto;">
                <i class="fas fa-user-slash"></i>
            </div>`;
        }

        // Berkas Baru & Status
        let matchInfoHtml = "";
        let previewHtml = "";
        let statusBadgeHtml = "";
        let actionBtnHtml = "";

        if (match && match.file) {
            const sizeStr = formatBytes(match.file.size);
            matchInfoHtml = `
                <div style="font-weight: 600; color: var(--primary); word-break: break-all;">${escapeHtml(match.file.name)}</div>
                <div style="font-size: 0.72rem; color: var(--text-muted);">${sizeStr} • Cocok via ${escapeHtml(match.matchedBy)}</div>
            `;
            previewHtml = `
                <div style="width: 38px; height: 50px; border-radius: 6px; border: 1.5px solid var(--primary); overflow: hidden; background: #000; margin: 0 auto; box-shadow: 0 2px 8px rgba(99,102,241,0.3);">
                    <img src="${match.previewUrl}" alt="Preview" style="width: 100%; height: 100%; object-fit: cover;">
                </div>
            `;
            statusBadgeHtml = `<span class="badge badge-success" style="font-size: 0.72rem; padding: 3px 8px;"><i class="fas fa-check me-1"></i> Siap Unggah</span>`;
            actionBtnHtml = `
                <button type="button" class="btn-icon" onclick="removeBulkStudentMatch('${s.peserta_didik_id}')" title="Batalkan berkas ini" style="color: var(--danger); width: 32px; height: 32px;">
                    <i class="fas fa-times"></i>
                </button>
            `;
        } else {
            matchInfoHtml = `<span style="color: var(--text-muted); font-style: italic; font-size: 0.78rem;">Belum ada berkas yang dicocokkan</span>`;
            previewHtml = `<span style="color: var(--text-muted); font-size: 0.75rem;">-</span>`;
            statusBadgeHtml = hasExisting
                ? `<span class="badge badge-outline" style="font-size: 0.7rem; color: var(--text-muted);">Tetap Foto Lama</span>`
                : `<span class="badge" style="background: rgba(239,68,68,0.1); color: #ef4444; font-size: 0.7rem;">Belum Ada</span>`;
            actionBtnHtml = `
                <label for="manualInput_${s.peserta_didik_id}" class="btn-icon" title="Pilih foto manual untuk peserta didik ini" style="cursor: pointer; width: 32px; height: 32px;">
                    <i class="fas fa-folder-open"></i>
                </label>
                <input type="file" id="manualInput_${s.peserta_didik_id}" accept="image/png" style="display: none;" onchange="handleManualSingleFile(this, '${s.peserta_didik_id}')">
            `;
        }

        html += `
            <tr id="bulkRow_${s.peserta_didik_id}" style="border-bottom: 1px solid var(--border-color); transition: background 0.2s ease;">
                <td style="padding: 10px 12px; text-align: center; font-weight: 700; color: var(--text-muted);">${s.no_urut}</td>
                <td style="padding: 8px 12px; text-align: center;">${oldThumbHtml}</td>
                <td style="padding: 10px 12px;">
                    <div style="font-weight: 700; color: var(--text-color); font-size: 0.86rem;">${escapeHtml(s.nama)}</div>
                    <div style="font-size: 0.72rem; color: var(--text-muted); font-family: monospace;">NISN: ${escapeHtml(s.nisn || "-")} ${s.nipd ? "• NIPD: " + escapeHtml(s.nipd) : ""}</div>
                </td>
                <td style="padding: 10px 12px;">${matchInfoHtml}</td>
                <td style="padding: 8px 12px; text-align: center;">${previewHtml}</td>
                <td style="padding: 10px 12px; text-align: center;" id="bulkStatusCell_${s.peserta_didik_id}">${statusBadgeHtml}</td>
                <td style="padding: 10px 12px; text-align: right;">${actionBtnHtml}</td>
            </tr>
        `;
    });

    tbody.innerHTML = html;
    updateBulkSummaryUI();
}

window.handleBulkFilesSelection = function (files) {
    if (!files || files.length === 0) return;
    if (bulkStudents.length === 0) {
        if (window.SAE && typeof window.SAE.toast === "function") {
            window.SAE.toast(
                "Pilih kelas terlebih dahulu sebelum memilih berkas foto!",
                "warning",
            );
        }
        return;
    }

    const zipFiles = Array.from(files).filter((file) =>
        file.name.toLowerCase().endsWith(".zip"),
    );
    if (zipFiles.length > 0) {
        bulkZipFile = zipFiles[0];
        bulkFileMatches = {};
        updateBulkSummaryUI();
        if (window.SAE && typeof window.SAE.toast === "function") {
            window.SAE.toast(
                `ZIP "${bulkZipFile.name}" siap diunggah. Klik tombol Mulai Unggah untuk memproses.`,
                "info",
            );
        }
        if (zipFiles.length > 1 && window.SAE && typeof window.SAE.toast === "function") {
            window.SAE.toast(
                "Hanya 1 file ZIP diproses dalam sekali unggah.",
                "warning",
            );
        }
        return;
    }

    let newlyMatched = 0;
    let nonPngCount = 0;

    // Normalisasi string bantu
    const cleanStr = (str) =>
        String(str || "")
            .toLowerCase()
            .replace(/[^a-z0-9]/g, "");

    Array.from(files).forEach(function (file) {
        // Cek ekstensi dan MIME
        const ext = file.name.split(".").pop().toLowerCase();
        if (
            ext !== "png" &&
            file.type !== "image/png" &&
            file.type !== "image/x-png"
        ) {
            nonPngCount++;
            return;
        }

        const rawFilename = file.name;
        const nameWithoutExt = rawFilename.substring(
            0,
            rawFilename.lastIndexOf("."),
        );
        const cleanFile = cleanStr(nameWithoutExt);

        let matchedStudent = null;
        let matchedBy = "";

        // 1. Cek kecocokan NISN
        for (const s of bulkStudents) {
            if (
                s.nisn &&
                cleanStr(s.nisn) &&
                cleanFile.includes(cleanStr(s.nisn))
            ) {
                matchedStudent = s;
                matchedBy = "NISN";
                break;
            }
        }

        // 2. Cek kecocokan NIPD
        if (!matchedStudent) {
            for (const s of bulkStudents) {
                if (
                    s.nipd &&
                    cleanStr(s.nipd) &&
                    cleanFile.includes(cleanStr(s.nipd))
                ) {
                    matchedStudent = s;
                    matchedBy = "NIPD";
                    break;
                }
            }
        }

        // 3. Cek kecocokan NIK
        if (!matchedStudent) {
            for (const s of bulkStudents) {
                if (
                    s.nik &&
                    cleanStr(s.nik) &&
                    cleanFile.includes(cleanStr(s.nik))
                ) {
                    matchedStudent = s;
                    matchedBy = "NIK";
                    break;
                }
            }
        }

        // 4. Cek kecocokan Nama Lengkap
        if (!matchedStudent) {
            for (const s of bulkStudents) {
                const sNameClean = cleanStr(s.nama);
                if (
                    sNameClean.length >= 4 &&
                    (cleanFile.includes(sNameClean) ||
                        sNameClean.includes(cleanFile))
                ) {
                    matchedStudent = s;
                    matchedBy = "Nama Peserta Didik";
                    break;
                }
            }
        }

        // 5. Cek kecocokan Nomor Urut Absen (contoh: 01.png, 02.png, 1.png, atau prefix '01_...')
        if (!matchedStudent) {
            const numMatch = nameWithoutExt.match(/^0*(\d{1,2})([_\s-]|$)/);
            if (numMatch) {
                const numVal = parseInt(numMatch[1], 10);
                if (numVal >= 1 && numVal <= bulkStudents.length) {
                    matchedStudent = bulkStudents[numVal - 1];
                    matchedBy = "No. Urut " + numVal;
                }
            }
        }

        if (matchedStudent) {
            const previewUrl = URL.createObjectURL(file);
            bulkFileMatches[matchedStudent.peserta_didik_id] = {
                file: file,
                previewUrl: previewUrl,
                matchedBy: matchedBy,
            };
            newlyMatched++;
        }
    });

    renderBulkStudentsTable();

    if (nonPngCount > 0) {
        if (window.SAE && typeof window.SAE.toast === "function") {
            window.SAE.toast(
                `${nonPngCount} file diabaikan karena bukan format PNG.`,
                "warning",
            );
        }
    }

    if (newlyMatched > 0) {
        if (window.SAE && typeof window.SAE.toast === "function") {
            window.SAE.toast(
                `${newlyMatched} pasfoto berhasil dipetakan ke peserta didik di kelas ini!`,
                "success",
            );
        }
    } else {
        if (window.SAE && typeof window.SAE.toast === "function") {
            window.SAE.toast(
                "Tidak ada nama berkas yang cocok dengan NISN/Nama/No Urut peserta didik kelas ini.",
                "warning",
            );
        }
    }
};

async function handleBulkZipUpload(file) {
    const csrfToken =
        document.querySelector('meta[name="csrf-token"]')?.content || "";
    const btnStart = document.getElementById("btnStartBulkUpload");
    const btnText = document.getElementById("btnStartBulkUploadText");
    const progressWrap = document.getElementById("bulkProgressContainer");
    const progressBar = document.getElementById("bulkProgressBar");
    const progressPercent = document.getElementById("bulkProgressPercent");
    const progressDetail = document.getElementById("bulkProgressDetail");
    const progressTitle = document.getElementById("bulkProgressTitle");

    const formData = new FormData();
    formData.append("zip", file);

    const rombelSelect = document.getElementById("bulkRombelSelect");
    const rombelVal = rombelSelect ? rombelSelect.value : "";
    if (rombelVal) {
        formData.append("rombel", rombelVal);
    }

    isBulkUploading = true;
    if (btnStart) {
        btnStart.disabled = true;
        btnStart.style.opacity = "0.75";
    }
    if (btnText) {
        btnText.innerHTML =
            '<i class="fas fa-spinner fa-spin me-1"></i> Memproses ZIP & Kompresi...';
    }
    if (progressWrap) progressWrap.style.display = "block";
    if (progressBar) progressBar.style.width = "30%";
    if (progressPercent) progressPercent.textContent = "30%";
    if (progressDetail)
        progressDetail.textContent = `Mengunggah berkas ZIP ${file.name}...`;
    if (progressTitle)
        progressTitle.innerHTML =
            '<i class="fas fa-spinner fa-spin me-1"></i> Mengekstrak ZIP & mencocokkan pasfoto...';

    try {
        const res = await fetch(
            "/dashboard/manajemen-data/peserta-didik-aktif/bulk-upload-foto",
            {
                method: "POST",
                headers: {
                    "X-CSRF-TOKEN": csrfToken,
                    Accept: "application/json",
                },
                body: formData,
            },
        );

        const json = await res.json();
        if (json.status !== "success")
            throw new Error(json.message || "Gagal memproses ZIP.");

        (json.data || []).forEach((item) => {
            if (item.status === "success") {
                const s = bulkStudents.find(
                    (st) => st.peserta_didik_id === item.peserta_didik_id,
                );
                if (s) {
                    s.foto_url = item.foto_url;
                    s.foto_size = item.foto_size;
                }
                updateRowFotoUI(
                    item.peserta_didik_id,
                    item.foto_url,
                    item.foto_size,
                );
            }
        });

        // Render ulang tabel modal agar foto baru langsung tampil
        renderBulkStudentsTable();

        // Update counter statistik foto di modal
        const withFoto = bulkStudents.filter((s) => Boolean(s.foto_url)).length;
        const withoutFoto = Math.max(0, bulkStudents.length - withFoto);
        const elSudah = document.getElementById("bulkSudahFotoCount");
        const elBelum = document.getElementById("bulkBelumFotoCount");
        if (elSudah) elSudah.textContent = withFoto;
        if (elBelum) elBelum.textContent = withoutFoto;

        if (progressBar) progressBar.style.width = "100%";
        if (progressPercent) progressPercent.textContent = "100%";
        if (progressDetail)
            progressDetail.textContent = `${json.success_count || 0} berhasil disimpan, ${json.failed_count || 0} gagal`;
        if (progressTitle)
            progressTitle.innerHTML = `<i class="fas fa-circle-check text-success me-1"></i> ${escapeHtml(json.message)}`;

        const isFull =
            (json.failed_count || 0) === 0 && (json.success_count || 0) > 0;
        const toastType = isFull
            ? "success"
            : (json.success_count || 0) > 0
              ? "warning"
              : "danger";
        if (window.SAE && typeof window.SAE.toast === "function") {
            window.SAE.toast(json.message, toastType);
        }

        if (btnStart) {
            btnStart.disabled = false;
            btnStart.style.opacity = "1";
        }
        if (btnText) {
            btnText.innerHTML = `<i class="fas fa-check-circle me-1"></i> Selesai (${json.success_count || 0} Tersimpan)`;
        }
    } catch (err) {
        if (progressTitle)
            progressTitle.innerHTML = `<i class="fas fa-exclamation-triangle text-danger me-1"></i> Gagal memproses ZIP: ${escapeHtml(err.message || "")}`;
        if (window.SAE && typeof window.SAE.toast === "function") {
            window.SAE.toast(err.message || "Gagal memproses ZIP.", "danger");
        }
        if (btnStart) {
            btnStart.disabled = false;
            btnStart.style.opacity = "1";
        }
        if (btnText) {
            btnText.innerHTML = '<i class="fas fa-redo me-1"></i> Coba Unggah Lagi';
        }
    } finally {
        isBulkUploading = false;
        bulkZipFile = null;
        updateBulkSummaryUI();
    }
}

window.handleManualSingleFile = function (input, pdId) {
    if (!input || !input.files || !input.files[0]) return;
    const file = input.files[0];

    const ext = file.name.split(".").pop().toLowerCase();
    if (ext !== "png" && file.type !== "image/png") {
        if (window.SAE && typeof window.SAE.toast === "function") {
            window.SAE.toast("Berkas wajib format PNG (.png).", "warning");
        }
        input.value = "";
        return;
    }

    const previewUrl = URL.createObjectURL(file);
    bulkFileMatches[pdId] = {
        file: file,
        previewUrl: previewUrl,
        matchedBy: "Manual",
    };

    renderBulkStudentsTable();
};

window.removeBulkStudentMatch = function (pdId) {
    if (bulkFileMatches[pdId]) {
        delete bulkFileMatches[pdId];
        renderBulkStudentsTable();
    }
};

window.resetBulkMatches = function () {
    bulkFileMatches = {};
    bulkZipFile = null;
    const input = document.getElementById("bulkFilesInput");
    if (input) input.value = "";
    renderBulkStudentsTable();
    updateBulkSummaryUI();
};

function updateBulkSummaryUI() {
    const summaryBanner = document.getElementById("bulkMatchSummaryBanner");
    const summaryText = document.getElementById("bulkMatchSummaryText");
    const filesBadge = document.getElementById("bulkFilesCountBadge");
    const btnReset = document.getElementById("btnClearBulkMatches");
    const btnStart = document.getElementById("btnStartBulkUpload");
    const btnText = document.getElementById("btnStartBulkUploadText");

    const matchedCount = Object.keys(bulkFileMatches).length;
    const totalCount = bulkStudents.length;

    if (bulkZipFile) {
        if (summaryBanner) summaryBanner.style.display = "flex";
        if (summaryText) {
            summaryText.innerHTML = `<strong>ZIP: ${escapeHtml(bulkZipFile.name)}</strong> siap diunggah &amp; dikompresi untuk kelas ini.`;
        }
        if (filesBadge) filesBadge.textContent = `1 Berkas ZIP Siap`;
        if (btnReset) btnReset.style.display = "inline-flex";
        if (btnStart) {
            btnStart.disabled = false;
            btnStart.style.opacity = "1";
            btnStart.style.cursor = "pointer";
        }
        if (btnText) {
            btnText.innerHTML = `<i class="fas fa-cloud-arrow-up me-1"></i> Mulai Unggah &amp; Kompresi ZIP (${totalCount} Peserta Didik)`;
        }
    } else if (matchedCount > 0) {
        if (summaryBanner) summaryBanner.style.display = "flex";
        if (summaryText) {
            summaryText.innerHTML = `<strong>${matchedCount} dari ${totalCount} peserta didik</strong> berhasil dipasangkan berkas foto PNG.`;
        }
        if (filesBadge) filesBadge.textContent = `${matchedCount} Foto Siap`;
        if (btnReset) btnReset.style.display = "inline-flex";
        if (btnStart) {
            btnStart.disabled = false;
            btnStart.style.opacity = "1";
            btnStart.style.cursor = "pointer";
        }
        if (btnText) {
            btnText.innerHTML = `<i class="fas fa-cloud-arrow-up me-1"></i> Mulai Unggah &amp; Kompresi Masal (${matchedCount} Foto)`;
        }
    } else {
        if (summaryBanner) summaryBanner.style.display = "none";
        if (btnReset) btnReset.style.display = "none";
        if (btnStart) {
            btnStart.disabled = false;
            btnStart.style.opacity = "0.7";
            btnStart.style.cursor = "pointer";
        }
        if (btnText) {
            btnText.innerHTML = `<i class="fas fa-cloud-arrow-up me-1"></i> Mulai Unggah &amp; Kompresi Masal (0 Foto)`;
        }
    }
}

// Queue Engine: Unggah dan kompresi secara paralel (concurrency 2)
window.executeBulkUploadQueue = async function () {
    if (isBulkUploading) return;

    // Jika ada ZIP pending, jalankan upload ZIP
    if (bulkZipFile) {
        const zipToUpload = bulkZipFile;
        await handleBulkZipUpload(zipToUpload);
        return;
    }

    const matchedEntries = Object.entries(bulkFileMatches);
    if (matchedEntries.length === 0) {
        if (window.SAE && typeof window.SAE.toast === "function") {
            window.SAE.toast(
                "Silakan pilih berkas ZIP atau seret pasfoto PNG ke kotak unggah terlebih dahulu.",
                "warning",
            );
        } else {
            alert(
                "Silakan pilih berkas ZIP atau pasfoto PNG terlebih dahulu.",
            );
        }
        return;
    }

    isBulkUploading = true;

    const btnStart = document.getElementById("btnStartBulkUpload");
    const btnText = document.getElementById("btnStartBulkUploadText");
    const progressWrap = document.getElementById("bulkProgressContainer");
    const progressBar = document.getElementById("bulkProgressBar");
    const progressPercent = document.getElementById("bulkProgressPercent");
    const progressDetail = document.getElementById("bulkProgressDetail");
    const progressTitle = document.getElementById("bulkProgressTitle");

    if (btnStart) {
        btnStart.disabled = true;
        btnStart.style.opacity = "0.75";
    }
    if (btnText) {
        btnText.innerHTML =
            '<i class="fas fa-spinner fa-spin me-1"></i> Mengompresi & Menyimpan...';
    }
    if (progressWrap) progressWrap.style.display = "block";

    let completed = 0;
    let failed = 0;
    const total = matchedEntries.length;
    const csrfToken =
        document.querySelector('meta[name="csrf-token"]')?.content || "";

    const updateProgress = () => {
        const pct = Math.round((completed / total) * 100);
        if (progressBar) progressBar.style.width = pct + "%";
        if (progressPercent) progressPercent.textContent = pct + "%";
        if (progressDetail) {
            progressDetail.textContent = `${completed} dari ${total} foto diproses (${failed} gagal)`;
        }
    };

    // Worker pengunggah tunggal
    const uploadSingle = async ([pdId, match]) => {
        const statusCell = document.getElementById("bulkStatusCell_" + pdId);
        if (statusCell) {
            statusCell.innerHTML = `<span class="badge" style="background: rgba(99,102,241,0.15); color: var(--primary); font-size: 0.72rem;"><i class="fas fa-spinner fa-spin me-1"></i> Mengompresi...</span>`;
        }

        const formData = new FormData();
        formData.append("peserta_didik_id", pdId);
        formData.append("foto", match.file);

        try {
            const res = await fetch(
                "/dashboard/manajemen-data/peserta-didik-aktif/upload-foto",
                {
                    method: "POST",
                    headers: {
                        "X-CSRF-TOKEN": csrfToken,
                        Accept: "application/json",
                    },
                    body: formData,
                },
            );

            const json = await res.json();
            if (json.status === "success") {
                if (statusCell) {
                    const savingsStr = json.data.savings
                        ? ` (Hemat ${json.data.savings}%)`
                        : "";
                    statusCell.innerHTML = `<span class="badge badge-success" style="font-size: 0.72rem; padding: 3px 8px;" title="${json.data.foto_size}${savingsStr}"><i class="fas fa-check-double me-1"></i> ${json.data.foto_size}</span>`;
                }

                // Update data di array bulkStudents
                const st = bulkStudents.find(
                    (s) => s.peserta_didik_id === pdId,
                );
                if (st) {
                    st.foto_url = json.data.foto_url;
                    st.foto_size = json.data.foto_size;
                }

                // Update baris tabel utama jika ada
                updateRowFotoUI(pdId, json.data.foto_url, json.data.foto_size);
            } else {
                throw new Error(json.message || "Gagal");
            }
        } catch (err) {
            failed++;
            if (statusCell) {
                statusCell.innerHTML = `<span class="badge badge-danger" style="font-size: 0.72rem; padding: 3px 8px;" title="${escapeHtml(err.message)}"><i class="fas fa-exclamation-triangle me-1"></i> Gagal</span>`;
            }
        } finally {
            completed++;
            updateProgress();
        }
    };

    // Eksekusi antrean dengan concurrency = 2
    const concurrency = 2;
    const queue = [...matchedEntries];
    const workers = [];

    for (let i = 0; i < concurrency; i++) {
        workers.push(
            (async () => {
                while (queue.length > 0) {
                    const item = queue.shift();
                    await uploadSingle(item);
                }
            })(),
        );
    }

    await Promise.all(workers);

    isBulkUploading = false;
    if (progressTitle) {
        progressTitle.innerHTML = `<i class="fas fa-circle-check text-success me-1"></i> Selesai! ${completed - failed} foto berhasil disimpan &amp; dikompresi.`;
    }

    if (window.SAE && typeof window.SAE.toast === "function") {
        window.SAE.toast(
            `Unggah masal selesai! ${completed - failed} pasfoto berhasil dikompresi dan disimpan.${failed > 0 ? " (" + failed + " berkas gagal)" : ""}`,
            failed > 0 ? "warning" : "success",
        );
    }

    // Render ulang tabel modal agar foto baru terlihat di modal
    renderBulkStudentsTable();

    // Update counter statistik foto rombel di modal
    const withFoto = bulkStudents.filter((s) => Boolean(s.foto_url)).length;
    const withoutFoto = Math.max(0, bulkStudents.length - withFoto);
    const elSudah = document.getElementById("bulkSudahFotoCount");
    const elBelum = document.getElementById("bulkBelumFotoCount");
    if (elSudah) elSudah.textContent = withFoto;
    if (elBelum) elBelum.textContent = withoutFoto;

    if (btnStart) {
        btnStart.disabled = false;
        btnStart.style.opacity = "1";
    }
    if (btnText) {
        btnText.innerHTML = `<i class="fas fa-check-circle me-1"></i> Selesai (${completed - failed} Tersimpan)`;
    }
};

// Pasang event listener dropzone masal
document.addEventListener("DOMContentLoaded", function () {
    const bulkDropZone = document.getElementById("bulkDropZone");
    const bulkFilesInput = document.getElementById("bulkFilesInput");

    if (bulkDropZone && bulkFilesInput) {
        bulkDropZone.addEventListener("dragover", function (e) {
            e.preventDefault();
            bulkDropZone.style.borderColor = "var(--primary)";
            bulkDropZone.style.background = "rgba(99,102,241,0.12)";
        });

        bulkDropZone.addEventListener("dragleave", function () {
            bulkDropZone.style.borderColor = "rgba(99,102,241,0.45)";
            bulkDropZone.style.background = "rgba(99,102,241,0.03)";
        });

        bulkDropZone.addEventListener("drop", function (e) {
            e.preventDefault();
            bulkDropZone.style.borderColor = "rgba(99,102,241,0.45)";
            bulkDropZone.style.background = "rgba(99,102,241,0.03)";
            if (e.dataTransfer.files && e.dataTransfer.files.length > 0) {
                handleBulkFilesSelection(e.dataTransfer.files);
            }
        });

        bulkFilesInput.addEventListener("change", function () {
            if (this.files && this.files.length > 0) {
                handleBulkFilesSelection(this.files);
            }
        });
    }
});

