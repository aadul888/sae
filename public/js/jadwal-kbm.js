/**
 * JavaScript Modular Modul Jadwal KBM (Matriks Grid Interaktif & Anti-Bentrok)
 * Sistem Aplikasi Edukasi (SAE)
 */

// ==========================================
// TOGGLE PEMBERLAKUAN JADWAL AKTIF OLEH ADMIN
// ==========================================
window.handleTogglePemberlakuan = function (btn) {
    if (!btn) return;
    const targetStatus = btn.dataset.status;
    const targetMode = btn.dataset.mode || "otomatis";
    const url = btn.dataset.url;
    const isAktifkan = (targetStatus === "aktif");

    const modeLabel = targetMode === "otomatis" ? "Otomatis (Generate)" : "Manual (Wali Kelas)";
    const otherLabel = targetMode === "otomatis" ? "Manual" : "Otomatis";

    const title = isAktifkan
        ? `Berlakukan Jadwal KBM ${modeLabel}?`
        : "Alihkan Status Jadwal ke Draft?";
    const text = isAktifkan
        ? `Setelah diberlakukan, Jadwal ${modeLabel} akan resmi aktif di dashboard Guru, Siswa, dan Presensi. Jadwal ${otherLabel} otomatis dialihkan ke status Draft.`
        : "Saat berstatus draft, seluruh jadwal KBM dinonaktifkan sementara dan guru/siswa akan melihat info jadwal dalam tahap penyusunan.";
    const confirmBtnText = isAktifkan ? "Ya, Berlakukan Sekarang!" : "Ya, Jadikan Draft";
    const confirmBtnColor = isAktifkan ? "#10b981" : "#f59e0b";

    const executeToggle = () => {
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") ||
                      document.querySelector('input[name="_token"]')?.value;

        const originalHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Memproses...';

        fetch(url, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": token,
                Accept: "application/json",
            },
            body: JSON.stringify({ 
                status: targetStatus,
                mode: targetMode
            }),
        })
        .then(async (res) => {
            const data = await res.json().catch(() => ({}));
            if (!res.ok) {
                throw new Error(data.message || "Gagal mengubah status pemberlakuan jadwal.");
            }
            return data;
        })
        .then((data) => {
            window.SAE.alert(data.message || "Status pemberlakuan jadwal berhasil diperbarui.", "Berhasil!", "success", 1800)
                .then(() => { window.location.reload(); });
        })
        .catch((err) => {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
            window.SAE.alert(err.message, "Gagal Mengubah Status", "error");
        });
    };

    window.SAE.confirm(text, title, isAktifkan ? "info" : "warning", confirmBtnText).then((ok) => {
        if (ok) executeToggle();
    });
};

document.addEventListener("click", function (e) {
    const btn = e.target.closest(".btn-toggle-pemberlakuan");
    if (btn) {
        e.preventDefault();
        e.stopPropagation();
        window.handleTogglePemberlakuan(btn);
    }
});

document.addEventListener("DOMContentLoaded", function () {
    const modal = document.getElementById("modalJadwalKbm");
    const form = document.getElementById("formJadwalKbm");
    const modalTitle = document.getElementById("modalJadwalTitle");
    const btnTambah = document.getElementById("btnTambahJadwal");
    const btnTutup = document.getElementById("btnTutupModalJadwal");
    const btnBatal = document.getElementById("btnBatalJadwal");
    const btnSimpan = document.getElementById("btnSimpanJadwal");
    const selectRombel = document.getElementById("modalRombelId");
    const selectPembelajaran = document.getElementById("modalPembelajaranId");
    const conflictAlertBox = document.getElementById("conflictAlertBox");
    const inputHari = document.getElementById("modalHari");
    const inputJamKeMulai = document.getElementById("modalJamKeMulai");
    const inputJamKeSelesai = document.getElementById("modalJamKeSelesai");
    const inputJamMulai = document.getElementById("modalJamMulai");
    const inputJamSelesai = document.getElementById("modalJamSelesai");
    const inputJadwalId = document.getElementById("jadwalId");

    // Pemetaan Jam Pelajaran (Dinamis dari Pengaturan Sistem)
    let slotMapping = {
        1: { mulai: "07:15", selesai: "08:00" },
        2: { mulai: "08:00", selesai: "08:45" },
        3: { mulai: "08:45", selesai: "09:30" },
        4: { mulai: "09:50", selesai: "10:35" },
        5: { mulai: "10:35", selesai: "11:20" },
        6: { mulai: "11:20", selesai: "12:05" },
        7: { mulai: "12:45", selesai: "13:30" },
        8: { mulai: "13:30", selesai: "14:15" },
        9: { mulai: "14:15", selesai: "15:00" },
        10: { mulai: "15:00", selesai: "15:45" },
    };

    if (modal && modal.dataset.slots) {
        try {
            const rawSlots = JSON.parse(modal.dataset.slots);
            if (Array.isArray(rawSlots) && rawSlots.length > 0) {
                const dynamicMap = {};
                rawSlots.forEach((s) => {
                    const k = parseInt(s.jam_ke);
                    if (k) {
                        dynamicMap[k] = { mulai: s.mulai, selesai: s.selesai };
                    }
                });
                slotMapping = dynamicMap;
            }
        } catch (err) {
            console.warn("Gagal memuat slot dinamis:", err);
        }
    }

    let conflictCheckTimeout = null;

    // Helper: Buka Modal
    function bukaModal(isEdit = false, data = null) {
        if (!modal) return;
        form.reset();
        conflictAlertBox.style.display = "none";
        conflictAlertBox.innerHTML = "";

        if (isEdit && data) {
            modalTitle.innerHTML =
                '<i class="fas fa-calendar-check text-primary me-2"></i> Edit Jadwal KBM';
            inputJadwalId.value = data.id || "";
            selectRombel.value = data.rombel || "";
            inputHari.value = data.hari || "Senin";
            inputJamKeMulai.value = data.jamKeMulai || 1;
            inputJamKeSelesai.value = data.jamKeSelesai || 1;
            inputJamMulai.value = data.jamMulai || "07:15";
            inputJamSelesai.value = data.jamSelesai || "08:00";
            document.getElementById("modalRuangan").value = data.ruangan || "";
            document.getElementById("modalKeterangan").value =
                data.keterangan || "";

            loadPembelajaran(data.rombel, data.pembelajaran);
        } else {
            modalTitle.innerHTML =
                '<i class="fas fa-calendar-plus text-primary me-2"></i> Tambah Jadwal KBM';
            inputJadwalId.value = "";

            if (data && data.rombel) {
                // Quick Slot Click dari Grid Sel Kosong
                selectRombel.value = data.rombel;
                inputHari.value = data.hari || "Senin";
                inputJamKeMulai.value = data.jamKe || 1;
                inputJamKeSelesai.value = data.jamKe || 1;
                inputJamMulai.value = data.jamMulai || "07:15";
                inputJamSelesai.value = data.jamSelesai || "08:00";

                loadPembelajaran(data.rombel, null);
            } else {
                selectPembelajaran.innerHTML =
                    '<option value="">-- Pilih Rombel Terlebih Dahulu --</option>';
                selectPembelajaran.disabled = true;
            }
        }

        modal.style.display = "flex";
    }

    // Helper: Tutup Modal
    function tutupModal() {
        if (!modal) return;
        modal.style.display = "none";
        form.reset();
    }

    if (btnTambah) btnTambah.addEventListener("click", () => bukaModal(false));
    if (btnTutup) btnTutup.addEventListener("click", tutupModal);
    if (btnBatal) btnBatal.addEventListener("click", tutupModal);

    window.addEventListener("click", function (e) {
        if (e.target === modal) tutupModal();
    });

    // Delegated click handler on document untuk elemen interaktif jadwal
    document.addEventListener("click", function (e) {
        // Quick Assign dari Sel Kosong Grid
        const btnQuick = e.target.closest(".btn-quick-slot");
        if (btnQuick) {
            const data = {
                rombel: btnQuick.dataset.rombelId,
                hari: btnQuick.dataset.hari,
                jamKe: parseInt(btnQuick.dataset.jamKe) || 1,
                jamMulai: btnQuick.dataset.jamMulai,
                jamSelesai: btnQuick.dataset.jamSelesai,
            };
            bukaModal(false, data);
            return;
        }

        // Tombol Edit di Baris Tabel atau Kartu Grid
        const btnEdit = e.target.closest(".btn-edit-jadwal");
        if (btnEdit) {
            const data = {
                id: btnEdit.dataset.id,
                rombel: btnEdit.dataset.rombel,
                pembelajaran: btnEdit.dataset.pembelajaran,
                hari: btnEdit.dataset.hari,
                jamKeMulai: btnEdit.dataset.jamKeMulai,
                jamKeSelesai: btnEdit.dataset.jamKeSelesai,
                jamMulai: btnEdit.dataset.jamMulai,
                jamSelesai: btnEdit.dataset.jamSelesai,
                ruangan: btnEdit.dataset.ruangan,
                keterangan: btnEdit.dataset.keterangan,
            };
            bukaModal(true, data);
            return;
        }

        // Navigasi Tab Hari & Filter Tingkat via refreshLiveTable (tanpa reload halaman)
        const tabBtn = e.target.closest(".btn-day-tab, .btn-tingkat-filter");
        if (
            tabBtn &&
            tabBtn.href &&
            !tabBtn.getAttribute("href").startsWith("javascript:") &&
            typeof window.refreshLiveTable === "function"
        ) {
            e.preventDefault();
            window.refreshLiveTable(tabBtn.href);
            return;
        }
    });

    // Helper Durasi JP Cepat
    document.querySelectorAll(".btn-quick-durasi").forEach((btn) => {
        btn.addEventListener("click", function () {
            const dur = parseInt(this.dataset.durasi) || 1;
            const startK = parseInt(inputJamKeMulai.value) || 1;
            const slotKeys = Object.keys(slotMapping).map(Number);
            const maxSlot = slotKeys.length > 0 ? Math.max(...slotKeys) : 16;
            const endK = Math.min(maxSlot, startK + dur - 1);

            inputJamKeSelesai.value = endK;
            syncTimesFromSlots();
            triggerConflictCheck();
        });
    });

    function syncTimesFromSlots() {
        const startK = parseInt(inputJamKeMulai.value);
        const endK = parseInt(inputJamKeSelesai.value);

        if (slotMapping[startK]) {
            inputJamMulai.value = slotMapping[startK].mulai;
        }
        if (slotMapping[endK]) {
            inputJamSelesai.value = slotMapping[endK].selesai;
        }
    }

    if (inputJamKeMulai) {
        inputJamKeMulai.addEventListener("change", function () {
            const startK = parseInt(this.value) || 1;
            const endK = parseInt(inputJamKeSelesai.value) || 1;
            if (endK < startK) {
                inputJamKeSelesai.value = startK;
            }
            syncTimesFromSlots();
            triggerConflictCheck();
        });
    }

    if (inputJamKeSelesai) {
        inputJamKeSelesai.addEventListener("change", function () {
            const startK = parseInt(inputJamKeMulai.value) || 1;
            const endK = parseInt(this.value) || 1;
            if (endK < startK) {
                inputJamKeMulai.value = endK;
            }
            syncTimesFromSlots();
            triggerConflictCheck();
        });
    }

    // Muat daftar pembelajaran saat rombel diganti
    function loadPembelajaran(rombelId, selectedPembelajaranId = null) {
        if (!rombelId) {
            selectPembelajaran.innerHTML =
                '<option value="">-- Pilih Rombel Terlebih Dahulu --</option>';
            selectPembelajaran.disabled = true;
            return;
        }

        selectPembelajaran.disabled = true;
        selectPembelajaran.innerHTML =
            '<option value="">Memuat data pembelajaran...</option>';

        const routePembelajaran =
            modal?.dataset?.routePembelajaran ||
            "/dashboard/master-data/jadwal-kbm/pembelajaran-by-rombel";

        fetch(`${routePembelajaran}?rombel_id=${encodeURIComponent(rombelId)}`)
            .then(async (res) => {
                if (!res.ok) {
                    const errText = await res.text();
                    throw new Error(`HTTP ${res.status}: ${errText}`);
                }
                return res.json();
            })
            .then((data) => {
                const items = data.pembelajaran || [];

                // Sembunyikan mapel yang kuota JP-nya sudah penuh, KECUALI mapel
                // yang sedang diedit (agar nilai lama tetap bisa dilihat/dipertahankan).
                const tersedia = items.filter(
                    (item) =>
                        !item.sudah_penuh ||
                        (selectedPembelajaranId &&
                            item.pembelajaran_id === selectedPembelajaranId)
                );

                const jumlahPenuh = items.length - tersedia.length;

                if (tersedia.length === 0) {
                    selectPembelajaran.innerHTML = items.length
                        ? '<option value="">Semua mata pelajaran sudah terjadwal penuh</option>'
                        : '<option value="">Tidak ada data pembelajaran di rombel ini</option>';
                    selectPembelajaran.disabled = true;
                    return;
                }

                let header =
                    '<option value="">-- Pilih Mata Pelajaran &amp; Guru --</option>';
                if (jumlahPenuh > 0) {
                    header += `<option value="" disabled>${jumlahPenuh} mapel disembunyikan (sudah penuh)</option>`;
                }
                selectPembelajaran.innerHTML = header;

                tersedia.forEach((item) => {
                    const opt = document.createElement("option");
                    opt.value = item.pembelajaran_id;
                    opt.dataset.ptk = item.ptk_id || "";
                    opt.dataset.mapel = item.nama_mata_pelajaran || "";
                    opt.dataset.guru = item.nama_guru || "Tanpa Guru";

                    const sisa =
                        item.sisa_jp === null || item.sisa_jp === undefined
                            ? null
                            : Number(item.sisa_jp);
                    const sisaText = sisa === null ? "" : ` - sisa ${sisa} JP`;
                    opt.textContent = `${item.nama_mata_pelajaran} (${item.nama_guru || "Belum Ditugaskan"} - ${item.jam_mengajar_per_minggu || 0} JP${sisaText})`;

                    if (
                        selectedPembelajaranId &&
                        item.pembelajaran_id === selectedPembelajaranId
                    ) {
                        opt.selected = true;
                    }
                    selectPembelajaran.appendChild(opt);
                });

                selectPembelajaran.disabled = false;
                triggerConflictCheck();
            })
            .catch((err) => {
                console.error("Gagal memuat pembelajaran:", err);
                selectPembelajaran.innerHTML =
                    '<option value="">Gagal memuat pembelajaran</option>';
                selectPembelajaran.disabled = true;
            });
    }

    if (selectRombel) {
        selectRombel.addEventListener("change", function () {
            loadPembelajaran(this.value);
            triggerConflictCheck();
        });
    }

    // Pemicu Live Conflict Check
    function triggerConflictCheck() {
        clearTimeout(conflictCheckTimeout);
        conflictCheckTimeout = setTimeout(checkConflictLive, 350);
    }

    [
        selectPembelajaran,
        inputHari,
        inputJamMulai,
        inputJamSelesai,
        inputJamKeMulai,
        inputJamKeSelesai,
        document.getElementById("modalRuangan"),
    ].forEach((el) => {
        if (el) el.addEventListener("change", triggerConflictCheck);
    });

    const modalRuanganInput = document.getElementById("modalRuangan");
    if (modalRuanganInput) {
        modalRuanganInput.addEventListener("input", triggerConflictCheck);
    }

    function checkConflictLive() {
        const rombelId = selectRombel ? selectRombel.value : "";
        const pembelajaranOpt = selectPembelajaran
            ? selectPembelajaran.selectedOptions[0]
            : null;
        const ptkId = pembelajaranOpt ? pembelajaranOpt.dataset.ptk || "" : "";
        const hari = inputHari ? inputHari.value : "";
        const jamMulai = inputJamMulai ? inputJamMulai.value : "";
        const jamSelesai = inputJamSelesai ? inputJamSelesai.value : "";
        const excludeId = inputJadwalId ? inputJadwalId.value : "";
        const ruangan = document.getElementById("modalRuangan")?.value || "";
        const jamKeMulai = inputJamKeMulai ? inputJamKeMulai.value : 1;
        const jamKeSelesai = inputJamKeSelesai ? inputJamKeSelesai.value : 1;

        if (!rombelId || !hari || !jamMulai || !jamSelesai) {
            conflictAlertBox.style.display = "none";
            return;
        }

        const token =
            document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute("content") ||
            document.querySelector('input[name="_token"]')?.value;

        const routeConflict =
            modal?.dataset?.routeConflict ||
            "/dashboard/master-data/jadwal-kbm/check-conflict";

        fetch(routeConflict, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": token,
                Accept: "application/json",
            },
            body: JSON.stringify({
                rombongan_belajar_id: rombelId,
                ptk_id: ptkId,
                hari: hari,
                jam_mulai: jamMulai,
                jam_selesai: jamSelesai,
                exclude_id: excludeId,
                ruangan: ruangan,
                jam_ke_mulai: jamKeMulai,
                jam_ke_selesai: jamKeSelesai,
            }),
        })
            .then((res) => res.json())
            .then((res) => {
                if (res.has_conflict) {
                    conflictAlertBox.style.display = "block";
                    conflictAlertBox.style.background =
                        "rgba(239, 68, 68, 0.12)";
                    conflictAlertBox.style.border = "1px solid #ef4444";
                    conflictAlertBox.style.color = "#ef4444";
                    conflictAlertBox.innerHTML = `<i class="fas fa-triangle-exclamation me-1"></i> <strong>Peringatan Bentrok:</strong> ${res.message}`;
                } else {
                    conflictAlertBox.style.display = "block";
                    conflictAlertBox.style.background =
                        "rgba(16, 185, 129, 0.12)";
                    conflictAlertBox.style.border = "1px solid #10b981";
                    conflictAlertBox.style.color = "#10b981";
                    conflictAlertBox.innerHTML = `<i class="fas fa-circle-check me-1"></i> Waktu, ruangan, dan guru tersedia (bebas bentrok).`;
                }
            })
            .catch(() => {
                conflictAlertBox.style.display = "none";
            });
    }

    // Submit Form Tambah/Edit Jadwal
    if (form) {
        function sendSaveJadwal(forceOverwrite = false) {
            btnSimpan.disabled = true;
            btnSimpan.innerHTML =
                '<i class="fas fa-spinner fa-spin me-1"></i> Memproses...';

            const id = inputJadwalId.value;
            const routeBase =
                modal?.dataset?.routeBase ||
                "/dashboard/master-data/jadwal-kbm";
            const url = id ? `${routeBase}/${id}` : routeBase;
            const method = id ? "PUT" : "POST";

            const payload = {
                rombongan_belajar_id: selectRombel.value,
                pembelajaran_id: selectPembelajaran.value,
                hari: inputHari.value,
                jam_ke_mulai: inputJamKeMulai.value,
                jam_ke_selesai: inputJamKeSelesai.value,
                jam_mulai: inputJamMulai.value,
                jam_selesai: inputJamSelesai.value,
                ruangan: document.getElementById("modalRuangan").value,
                keterangan: document.getElementById("modalKeterangan").value,
                overwrite: forceOverwrite,
            };

            const token =
                document
                    .querySelector('meta[name="csrf-token"]')
                    ?.getAttribute("content") ||
                document.querySelector('input[name="_token"]')?.value;

            fetch(url, {
                method: method,
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": token,
                    Accept: "application/json",
                },
                body: JSON.stringify(payload),
            })
                .then(async (res) => {
                    const data = await res.json();
                    if (!res.ok) {
                        if (data.can_overwrite) {
                            const swalObj = typeof Swal !== "undefined" ? Swal : (window.Swal || null);
                            if (swalObj) {
                                swalObj.fire({
                                    icon: "warning",
                                    title: "Slot Waktu Sudah Terisi",
                                    html: `Kelas ini sudah memiliki jadwal <strong>${data.conflicting_mapel || "mata pelajaran lain"}</strong> pada jam tersebut.<br><br>Apakah Anda ingin <strong>menimpa / mengganti</strong> jadwal lama tersebut dengan mata pelajaran baru ini?`,
                                    showCancelButton: true,
                                    confirmButtonColor: "#f59e0b",
                                    cancelButtonColor: "#64748b",
                                    confirmButtonText: '<i class="fas fa-sync-alt me-1"></i> Ya, Timpa Jadwal',
                                    cancelButtonText: "Batal",
                                }).then((swalRes) => {
                                    if (swalRes.isConfirmed) {
                                        sendSaveJadwal(true);
                                    } else {
                                        btnSimpan.disabled = false;
                                        btnSimpan.innerHTML = id
                                            ? '<i class="fas fa-save me-1"></i> Perbarui'
                                            : '<i class="fas fa-save me-1"></i> Simpan Jadwal';
                                    }
                                });
                                return null;
                            }
                        }
                        throw new Error(
                            data.message || "Terjadi kesalahan sistem.",
                        );
                    }
                    return data;
                })
                .then((data) => {
                    if (!data) return;
                    tutupModal();
                    if (window.Swal) {
                        Swal.fire({
                            icon: "success",
                            title: "Berhasil",
                            text:
                                data.message || "Jadwal KBM berhasil disimpan!",
                            timer: 1600,
                            showConfirmButton: false,
                        });
                    }
                    if (
                        window.SAERealtime &&
                        typeof window.SAERealtime.refreshCards === "function"
                    ) {
                        window.SAERealtime.refreshCards({ refreshTable: true });
                    } else if (typeof window.refreshLiveTable === "function") {
                        window.refreshLiveTable(window.location.href);
                    } else {
                        window.location.reload();
                    }
                })
                .catch((err) => {
                    {
                        Swal.fire({
                            icon: "error",
                            title: "Gagal Menyimpan",
                            text: err.message,
                        });
                    }
                })
                .finally(() => {
                    btnSimpan.disabled = false;
                    btnSimpan.innerHTML =
                        '<i class="fas fa-save me-1"></i> Simpan Jadwal';
                });
        }

        form.addEventListener("submit", function (e) {
            e.preventDefault();
            sendSaveJadwal(false);
        });
    }

    // Filter & Search Live Navigation untuk Mode Tabel
    function updateUrlParams() {
        const url = new URL(window.location.href);
        const perPage = document.getElementById("perPageSelect")?.value;
        const hari = document.getElementById("filterHari")?.value;
        const rombel = document.getElementById("filterRombel")?.value;
        const guru = document.getElementById("filterGuru")?.value;
        const q = document.getElementById("liveSearchInput")?.value.trim();

        if (perPage) url.searchParams.set("perPage", perPage);
        if (hari) url.searchParams.set("hari", hari);
        else url.searchParams.delete("hari");
        if (rombel) url.searchParams.set("rombel_id", rombel);
        else url.searchParams.delete("rombel_id");
        if (guru) url.searchParams.set("ptk_id", guru);
        else url.searchParams.delete("ptk_id");
        if (q) url.searchParams.set("q", q);
        else url.searchParams.delete("q");

        url.searchParams.delete("page");
        if (typeof window.refreshLiveTable === "function") {
            window.refreshLiveTable(url.toString());
        } else {
            window.location.href = url.toString();
        }
    }

    ["perPageSelect", "filterHari", "filterRombel", "filterGuru"].forEach(
        (id) => {
            const el = document.getElementById(id);
            if (el) el.addEventListener("change", updateUrlParams);
        },
    );

    const searchInput = document.getElementById("liveSearchInput");
    let searchTimer = null;
    if (searchInput) {
        searchInput.addEventListener("input", function () {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(updateUrlParams, 400);
        });
        searchInput.addEventListener("keypress", function (e) {
            if (e.key === "Enter") {
                clearTimeout(searchTimer);
                updateUrlParams();
            }
        });
    }

    // Sorting Klik Header Kolom pada Mode Tabel
    document.querySelectorAll(".sortable-th").forEach((th) => {
        th.addEventListener("click", function () {
            const sortKey = this.dataset.sort;
            const url = new URL(window.location.href);
            const currentSort = url.searchParams.get("sort");
            const currentDir = url.searchParams.get("sort_dir") || "asc";

            let nextDir = "asc";
            if (currentSort === sortKey && currentDir === "asc") {
                nextDir = "desc";
            }

            url.searchParams.set("sort", sortKey);
            url.searchParams.set("sort_dir", nextDir);
            if (typeof window.refreshLiveTable === "function") {
                window.refreshLiveTable(url.toString());
            } else {
                window.location.href = url.toString();
            }
        });
    });

    // Event Delegation: SweetAlert Konfirmasi Hapus via AJAX
    document.addEventListener("submit", function (e) {
        const formHapus = e.target.closest('form[data-confirm="delete"]');
        if (!formHapus) return;

        e.preventDefault();
        const namaItem = formHapus.dataset.name || "jadwal ini";

        const doDelete = () => {
            const actionUrl = formHapus.action;
            const token =
                document
                    .querySelector('meta[name="csrf-token"]')
                    ?.getAttribute("content") ||
                formHapus.querySelector('input[name="_token"]')?.value;

            fetch(actionUrl, {
                method: "DELETE",
                headers: {
                    "X-CSRF-TOKEN": token,
                    Accept: "application/json",
                    "Content-Type": "application/json",
                },
            })
                .then(async (res) => {
                    const data = await res.json();
                    if (!res.ok)
                        throw new Error(
                            data.message || "Gagal menghapus jadwal.",
                        );
                    return data;
                })
                .then((data) => {
                    if (window.Swal) {
                        Swal.fire({
                            icon: "success",
                            title: "Terhapus",
                            text: data.message || "Jadwal berhasil dihapus.",
                            timer: 1500,
                            showConfirmButton: false,
                        });
                    }
                    if (
                        window.SAERealtime &&
                        typeof window.SAERealtime.refreshCards === "function"
                    ) {
                        window.SAERealtime.refreshCards({ refreshTable: true });
                    } else if (typeof window.refreshLiveTable === "function") {
                        window.refreshLiveTable(window.location.href);
                    } else {
                        window.location.reload();
                    }
                })
                .catch((err) => {
                    {
                        Swal.fire({
                            icon: "error",
                            title: "Gagal Menghapus",
                            text: err.message,
                        });
                    }
                });
        };

        window.SAE.confirm(`Apakah Anda yakin ingin menghapus jadwal ${namaItem}? Tindakan ini tidak dapat dibatalkan.`, "Hapus Jadwal KBM?", 'danger', "Ya, Hapus!", "Batal").then(function(ok) {
            if (ok) {
                doDelete();
            }
        });
    });

});
