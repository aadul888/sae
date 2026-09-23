/**
 * JavaScript Modular Modul Jadwal KBM (Matriks Grid Interaktif & Anti-Bentrok)
 * Sistem Aplikasi Edukasi (SAE)
 */

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
                selectPembelajaran.innerHTML =
                    '<option value="">-- Pilih Mata Pelajaran &amp; Guru --</option>';

                if (items.length === 0) {
                    selectPembelajaran.innerHTML =
                        '<option value="">Tidak ada data pembelajaran di rombel ini</option>';
                    selectPembelajaran.disabled = true;
                    return;
                }

                items.forEach((item) => {
                    const opt = document.createElement("option");
                    opt.value = item.pembelajaran_id;
                    opt.dataset.ptk = item.ptk_id || "";
                    opt.dataset.mapel = item.nama_mata_pelajaran || "";
                    opt.dataset.guru = item.nama_guru || "Tanpa Guru";
                    opt.textContent = `${item.nama_mata_pelajaran} (${item.nama_guru || "Belum Ditugaskan"} - ${item.jam_mengajar_per_minggu || 0} JP)`;

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
        form.addEventListener("submit", function (e) {
            e.preventDefault();
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
                        throw new Error(
                            data.message || "Terjadi kesalahan sistem.",
                        );
                    }
                    return data;
                })
                .then((data) => {
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
                    if (window.Swal) {
                        Swal.fire({
                            icon: "error",
                            title: "Gagal Menyimpan",
                            text: err.message,
                        });
                    } else {
                        alert(err.message);
                    }
                })
                .finally(() => {
                    btnSimpan.disabled = false;
                    btnSimpan.innerHTML =
                        '<i class="fas fa-save me-1"></i> Simpan Jadwal';
                });
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
                    if (window.Swal) {
                        Swal.fire({
                            icon: "error",
                            title: "Gagal Menghapus",
                            text: err.message,
                        });
                    } else {
                        alert(err.message);
                    }
                });
        };

        if (window.Swal) {
            Swal.fire({
                title: "Hapus Jadwal KBM?",
                text: `Apakah Anda yakin ingin menghapus jadwal ${namaItem}? Tindakan ini tidak dapat dibatalkan.`,
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#ef4444",
                cancelButtonColor: "#6b7280",
                confirmButtonText: "Ya, Hapus!",
                cancelButtonText: "Batal",
            }).then((result) => {
                if (result.isConfirmed) {
                    doDelete();
                }
            });
        } else {
            if (confirm(`Hapus jadwal ${namaItem}?`)) {
                doDelete();
            }
        }
    });

    // ==========================================
    // MODAL TERPADU: PENGATURAN WAKTU & AUTO-GENERATE
    // ==========================================
    const modalAturDanGenerate = document.getElementById("modalAturDanGenerate");
    const formAturDanGenerate = document.getElementById("formAturDanGenerate");
    const btnBukaPengaturan = document.getElementById("btnBukaPengaturanSlot");
    const btnBukaAuto = document.getElementById("btnBukaAutoGenerate");
    const btnTutupAturDanGenerate = document.getElementById("btnTutupModalAturDanGenerate");
    const btnBatalAturDanGenerate = document.getElementById("btnBatalAturDanGenerate");
    const btnSimpanPengaturanOnly = document.getElementById("btnSimpanPengaturanOnly");
    const btnSimpanDanGenerate = document.getElementById("btnSimpanDanGenerate");

    function bukaModalAturDanGenerate() {
        if (!modalAturDanGenerate) return;
        modalAturDanGenerate.style.display = "flex";
        updateRealtimeModalJamSelesai();
    }

    function tutupModalAturDanGenerate() {
        if (!modalAturDanGenerate) return;
        modalAturDanGenerate.style.display = "none";
    }

    if (btnBukaPengaturan) btnBukaPengaturan.addEventListener("click", bukaModalAturDanGenerate);
    if (btnBukaAuto) btnBukaAuto.addEventListener("click", bukaModalAturDanGenerate);
    if (btnTutupAturDanGenerate) btnTutupAturDanGenerate.addEventListener("click", tutupModalAturDanGenerate);
    if (btnBatalAturDanGenerate) btnBatalAturDanGenerate.addEventListener("click", tutupModalAturDanGenerate);

    if (modalAturDanGenerate) {
        window.addEventListener("click", function (e) {
            if (e.target === modalAturDanGenerate) tutupModalAturDanGenerate();
        });
    }

    // PRESET DEFAULT DISTRIBUSI SLOT HARIAN PER TINGKAT
    const defaultTingkatSlots = {
        "5_hari": {
            "10": { "Senin": 13, "Selasa": 12, "Rabu": 12, "Kamis": 12, "Jumat": 7, "Sabtu": 0 },
            "11": { "Senin": 13, "Selasa": 12, "Rabu": 11, "Kamis": 11, "Jumat": 7, "Sabtu": 0 },
            "12": { "Senin": 12, "Selasa": 11, "Rabu": 11, "Kamis": 11, "Jumat": 7, "Sabtu": 0 }
        },
        "6_hari": {
            "10": { "Senin": 11, "Selasa": 10, "Rabu": 10, "Kamis": 10, "Jumat": 6, "Sabtu": 10 },
            "11": { "Senin": 11, "Selasa": 10, "Rabu": 9,  "Kamis": 9,  "Jumat": 6, "Sabtu": 10 },
            "12": { "Senin": 10, "Selasa": 9,  "Rabu": 9,  "Kamis": 9,  "Jumat": 6, "Sabtu": 10 }
        }
    };

    // TOGGLE INTERAKTIF SKEMA HARI SEKOLAH (5 HARI VS 6 HARI)
    function applySkemaHari(skema) {
        const cards = document.querySelectorAll(".skema-option-card");
        cards.forEach((card) => {
            const radio = card.querySelector('input[type="radio"]');
            if (radio && radio.value === skema) {
                radio.checked = true;
                card.classList.add("active");
                card.style.borderColor = "var(--primary)";
                card.style.background = "rgba(99, 102, 241, 0.06)";
            } else if (radio) {
                radio.checked = false;
                card.classList.remove("active");
                card.style.borderColor = "var(--border-color)";
                card.style.background = "var(--bg-card)";
            }
        });

        // Set alokasi slot per tingkat
        const cfg = defaultTingkatSlots[skema] || defaultTingkatSlots["5_hari"];
        document.querySelectorAll(".input-tingkat-slot").forEach((inp) => {
            const t = inp.dataset.tingkat;
            const dh = inp.dataset.hari;
            if (cfg[t] && cfg[t][dh] !== undefined) {
                inp.value = cfg[t][dh];
            }
        });

        // Tampilkan/sembunyikan baris Sabtu pada tabel tingkat
        document.querySelectorAll('.row-hari-slot[data-hari="Sabtu"]').forEach((r) => {
            r.style.display = (skema === "5_hari" ? "none" : "table-row");
        });

        // Set alokasi slot default global berdasarkan skema
        if (skema === "5_hari") {
            const defaults5 = { "Senin": 13, "Selasa": 12, "Rabu": 12, "Kamis": 12, "Jumat": 7, "Sabtu": 0 };
            ["Senin", "Selasa", "Rabu", "Kamis", "Jumat", "Sabtu"].forEach((dh) => {
                const inp = document.querySelector(`.input-slot-harian[data-hari="${dh}"]`);
                if (inp) inp.value = defaults5[dh];
            });

            const sabtuCard = document.querySelector('.slot-day-card[data-hari="Sabtu"]');
            if (sabtuCard) {
                sabtuCard.style.opacity = "0.5";
            }
        } else {
            const defaults6 = { "Senin": 11, "Selasa": 10, "Rabu": 10, "Kamis": 10, "Jumat": 6, "Sabtu": 10 };
            ["Senin", "Selasa", "Rabu", "Kamis", "Jumat", "Sabtu"].forEach((dh) => {
                const inp = document.querySelector(`.input-slot-harian[data-hari="${dh}"]`);
                if (inp) inp.value = defaults6[dh];
            });

            const sabtuCard = document.querySelector('.slot-day-card[data-hari="Sabtu"]');
            if (sabtuCard) {
                sabtuCard.style.opacity = "1";
            }
        }

        updateRealtimeModalJamSelesai();
    }

    document.querySelectorAll(".skema-option-card").forEach((card) => {
        card.addEventListener("click", function () {
            const radio = this.querySelector('input[name="skema_hari"]');
            if (radio) {
                applySkemaHari(radio.value);
            }
        });
    });

    // REALTIME CALCULATOR JAM SELESAI & TOTAL KBM PER TINGKAT
    function updateRealtimeModalJamSelesai() {
        const jamMulaiInput = document.getElementById("setJamMulai");
        const durasiInput = document.getElementById("setDurasiJp");
        const jamMulaiVal = jamMulaiInput?.value || "07:15";
        const durasiVal = parseInt(durasiInput?.value) || 45;

        const istirahatAktif = !!document.getElementById("setIstirahatAktif")?.checked;
        const istirahatJamKe = parseInt(document.getElementById("setIstirahatJamKe")?.value) || 8;
        const istirahatDurasi = parseInt(document.getElementById("setIstirahatDurasi")?.value) || 30;

        const upacaraAktif = !!document.querySelector('input[name="upacara[aktif]"]')?.checked;
        const pembiasaanAktif = !!document.querySelector('input[name="pembiasaan[aktif]"]')?.checked;

        const parts = jamMulaiVal.split(":");
        const startHour = parseInt(parts[0]) || 7;
        const startMin = parseInt(parts[1]) || 15;
        const baseMinutes = startHour * 60 + startMin;

        const calcTime = (jp, isFriday = false) => {
            if (jp <= 0) return "(Libur)";
            let totalMins = baseMinutes;
            for (let k = 1; k <= jp; k++) {
                const slotDur = (istirahatAktif && k === istirahatJamKe && !isFriday) ? istirahatDurasi : durasiVal;
                totalMins += slotDur;
            }
            const endHour = Math.floor(totalMins / 60) % 24;
            const endMin = totalMins % 60;
            return `${String(endHour).padStart(2, "0")}:${String(endMin).padStart(2, "0")}`;
        };

        // 1. Update Jam Pulang per Tingkat & Hitung Total KBM per Tingkat
        const sumKbm = { "10": 0, "11": 0, "12": 0 };
        const maxDaySlots = {};

        document.querySelectorAll(".input-tingkat-slot").forEach((input) => {
            const t = input.dataset.tingkat;
            const dh = input.dataset.hari;
            const rawVal = input.value.trim();
            const jp = (rawVal === "" || isNaN(rawVal)) ? 0 : parseInt(rawVal);

            maxDaySlots[dh] = Math.max(maxDaySlots[dh] || 0, jp);

            // Hitung jam pulang
            const pulangSpan = document.querySelector(`.pulang-tingkat-info[data-tingkat="${t}"][data-hari="${dh}"]`);
            if (pulangSpan) {
                if (jp <= 0) {
                    pulangSpan.textContent = "(Libur)";
                    pulangSpan.style.color = "#ef4444";
                } else {
                    const timeStr = calcTime(jp, dh === "Jumat");
                    pulangSpan.textContent = `Pulang: ${timeStr}`;
                    pulangSpan.style.color = "var(--text-muted)";
                }
            }

            // Hitung net KBM
            if (jp > 0) {
                let netJp = jp;
                if (dh === "Senin" && upacaraAktif && jp >= 1) netJp -= 1;
                if (dh === "Jumat" && pembiasaanAktif && jp >= 1) netJp -= 1;
                if (istirahatAktif && dh !== "Jumat" && jp >= istirahatJamKe) netJp -= 1;
                sumKbm[t] = (sumKbm[t] || 0) + Math.max(0, netJp);
            }
        });

        // Update Label & Hidden Inputs Total KBM
        ["10", "11", "12"].forEach((t) => {
            const totalSpan = document.getElementById(t === "10" ? "totalKbmX" : (t === "11" ? "totalKbmXI" : "totalKbmXII"));
            if (totalSpan) {
                totalSpan.textContent = `${sumKbm[t] || 0} JP`;
            }
            const hiddenInp = document.getElementById(`inputJpTingkat${t}`);
            if (hiddenInp) {
                hiddenInp.value = sumKbm[t] || 0;
            }
        });

        // 2. Sinkronkan ke input slot harian global jika ada perubahan
        document.querySelectorAll(".input-slot-harian").forEach((input) => {
            const dh = input.dataset.hari;
            if (maxDaySlots[dh] !== undefined && maxDaySlots[dh] > 0) {
                input.value = maxDaySlots[dh];
            }

            const selesaiSpan = document.querySelector(`.badge-jam-selesai[data-hari="${dh}"]`);
            if (!selesaiSpan) return;

            const rawVal = input.value.trim();
            const jp = (rawVal === "" || isNaN(rawVal)) ? 0 : parseInt(rawVal);

            if (jp <= 0) {
                selesaiSpan.textContent = "(Libur)";
                selesaiSpan.style.color = "#ef4444";
                return;
            }

            const formatted = calcTime(jp, dh === "Jumat");
            selesaiSpan.textContent = formatted;
            selesaiSpan.style.color = "var(--primary)";
        });
    }

    document.addEventListener("input", function (e) {
        if (
            e.target.matches(".input-slot-harian, .input-tingkat-slot") ||
            e.target.id === "setJamMulai" ||
            e.target.id === "setDurasiJp" ||
            e.target.id === "setIstirahatJamKe" ||
            e.target.id === "setIstirahatDurasi"
        ) {
            updateRealtimeModalJamSelesai();
        }
    });

    document.addEventListener("change", function (e) {
        if (
            e.target.matches(".input-slot-harian, .input-tingkat-slot") ||
            e.target.id === "setJamMulai" ||
            e.target.id === "setDurasiJp" ||
            e.target.id === "setIstirahatAktif" ||
            e.target.id === "setIstirahatJamKe" ||
            e.target.id === "setIstirahatDurasi" ||
            e.target.name === "skema_hari" ||
            e.target.name === "upacara[aktif]" ||
            e.target.name === "pembiasaan[aktif]"
        ) {
            updateRealtimeModalJamSelesai();
        }
    });

    // Helper Refresh Halaman / Tabel
    function triggerPageRefresh() {
        if (window.SAERealtime && typeof window.SAERealtime.refreshCards === "function") {
            window.SAERealtime.refreshCards({ refreshTable: true });
        } else if (typeof window.refreshLiveTable === "function") {
            window.refreshLiveTable(window.location.href);
        } else {
            window.location.reload();
        }
    }

    // AKSI 1: SIMPAN PENGATURAN SAJA
    if (btnSimpanPengaturanOnly) {
        btnSimpanPengaturanOnly.addEventListener("click", function () {
            if (!formAturDanGenerate) return;

            btnSimpanPengaturanOnly.disabled = true;
            btnSimpanPengaturanOnly.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Menyimpan...';

            const routeUrl = modalAturDanGenerate?.dataset?.routePengaturan || "/dashboard/master-data/jadwal-kbm/pengaturan";
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") ||
                          formAturDanGenerate.querySelector('input[name="_token"]')?.value;
            const formData = new FormData(formAturDanGenerate);

            fetch(routeUrl, {
                method: "POST",
                headers: {
                    "X-CSRF-TOKEN": token,
                    Accept: "application/json",
                },
                body: formData,
            })
                .then(async (res) => {
                    const data = await res.json();
                    if (!res.ok) throw new Error(data.message || "Gagal menyimpan pengaturan.");
                    return data;
                })
                .then((data) => {
                    tutupModalAturDanGenerate();
                    if (window.Swal) {
                        Swal.fire({
                            icon: "success",
                            title: "Pengaturan Disimpan!",
                            text: data.message || "Pengaturan waktu KBM dan skema hari berhasil diperbarui.",
                            timer: 1500,
                            showConfirmButton: false,
                        });
                    }
                    triggerPageRefresh();
                })
                .catch((err) => {
                    if (window.Swal) {
                        Swal.fire({
                            icon: "error",
                            title: "Gagal Menyimpan",
                            text: err.message,
                        });
                    } else {
                        alert(err.message);
                    }
                })
                .finally(() => {
                    btnSimpanPengaturanOnly.disabled = false;
                    btnSimpanPengaturanOnly.innerHTML = '<i class="fas fa-save me-1"></i> Simpan Pengaturan Saja';
                });
        });
    }

    // AKSI 2: SIMPAN & MULAI GENERATE OTOMATIS
    if (formAturDanGenerate) {
        formAturDanGenerate.addEventListener("submit", function (e) {
            e.preventDefault();

            const routeUrl = modalAturDanGenerate?.dataset?.routeAutoGenerate || "/dashboard/master-data/jadwal-kbm/auto-generate";
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") ||
                          formAturDanGenerate.querySelector('input[name="_token"]')?.value;
            const formData = new FormData(formAturDanGenerate);

            const executeAutoSchedule = () => {
                if (btnSimpanDanGenerate) {
                    btnSimpanDanGenerate.disabled = true;
                    btnSimpanDanGenerate.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Mengoptimalkan...';
                }

                if (window.Swal) {
                    Swal.fire({
                        title: "Menyusun Jadwal Otomatis...",
                        html: '<div style="font-size: 0.88rem; color: #6b7280; margin-top: 8px;">AI Zero-Gap Scheduler sedang menyinkronkan pengaturan waktu dan menyusun jadwal seluruh kelas secara berkesinambungan tanpa bentrok...</div>',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        didOpen: () => {
                            Swal.showLoading();
                        },
                    });
                }

                fetch(routeUrl, {
                    method: "POST",
                    headers: {
                        "X-CSRF-TOKEN": token,
                        Accept: "application/json",
                    },
                    body: formData,
                })
                    .then(async (res) => {
                        const data = await res.json();
                        if (!res.ok || data.success === false) {
                            if (data.is_incomplete) {
                                throw data;
                            }
                            throw new Error(data.message || "Gagal menjalankan auto-generate jadwal.");
                        }
                        return data;
                    })
                    .then((data) => {
                        tutupModalAturDanGenerate();
                        if (window.Swal) {
                            Swal.fire({
                                icon: "success",
                                title: "Auto-Generate Sukses!",
                                html: `<strong>${data.message}</strong><br><small style="color: #6b7280;">Total ${data.total_generated || 0} jadwal (${data.total_jp || 0} JP) telah dipetakan secara optimal tanpa celah jam kosong.</small>`,
                                confirmButtonText: "Lihat Jadwal Sekarang",
                                confirmButtonColor: "#6366f1",
                            }).then(() => {
                                triggerPageRefresh();
                            });
                        } else {
                            alert(data.message || "Generate jadwal selesai!");
                            triggerPageRefresh();
                        }
                    })
                    .catch((err) => {
                        if (window.Swal) {
                            if (err && err.is_incomplete) {
                                let listHtml = '<div style="text-align: left; max-height: 250px; overflow-y: auto; font-size: 0.78rem; background: rgba(239, 68, 68, 0.05); border: 1px solid rgba(239, 68, 68, 0.25); border-radius: 8px; padding: 12px; margin-top: 10px;">';
                                listHtml += `<p style="font-weight: 700; color: #b91c1c; margin-bottom: 8px;">${err.message || 'Validasi Ketat: Generate Dibatalkan karena ada jadwal yang belum terpetakan penuh.'}</p>`;

                                if (Array.isArray(err.unfilled_rombels) && err.unfilled_rombels.length > 0) {
                                    listHtml += '<div style="margin-bottom: 8px;"><strong style="color: #991b1b;">Rombel Belum Terisi Penuh:</strong><ul style="padding-left: 18px; margin: 4px 0;">';
                                    err.unfilled_rombels.forEach(r => {
                                        listHtml += `<li><strong>${r.nama_rombel}</strong>: Terjadwal ${r.scheduled_jp} JP dari target ${r.required_jp} JP (Kurang ${r.missing_jp} JP)</li>`;
                                    });
                                    listHtml += '</ul></div>';
                                }

                                if (Array.isArray(err.unmapped_subjects) && err.unmapped_subjects.length > 0) {
                                    listHtml += '<div><strong style="color: #991b1b;">Mata Pelajaran Belum Terpetakan:</strong><ul style="padding-left: 18px; margin: 4px 0;">';
                                    err.unmapped_subjects.slice(0, 8).forEach(u => {
                                        listHtml += `<li><strong>${u.nama_rombel}</strong> &bull; ${u.nama_mata_pelajaran} (${u.durasi} JP) &bull; <em>${u.nama_guru || '-'}</em></li>`;
                                    });
                                    if (err.unmapped_subjects.length > 8) {
                                        listHtml += `<li><em>...dan ${err.unmapped_subjects.length - 8} mata pelajaran lainnya</em></li>`;
                                    }
                                    listHtml += '</ul></div>';
                                }
                                listHtml += '</div>';

                                Swal.fire({
                                    icon: "warning",
                                    title: "Validasi Ketat: Dibatalkan",
                                    html: listHtml,
                                    confirmButtonText: "Tutup & Evaluasi",
                                    confirmButtonColor: "#ef4444",
                                    width: "620px",
                                });
                            } else {
                                Swal.fire({
                                    icon: "error",
                                    title: "Gagal Generate",
                                    text: err.message || "Terjadi kendala saat menyusun jadwal KBM.",
                                });
                            }
                        } else {
                            alert(err.message || "Gagal generate");
                        }
                    })
                    .finally(() => {
                        if (btnSimpanDanGenerate) {
                            btnSimpanDanGenerate.disabled = false;
                            btnSimpanDanGenerate.innerHTML = '<i class="fas fa-wand-magic-sparkles me-1"></i> Simpan &amp; Mulai Generate Otomatis';
                        }
                    });
            };

            if (window.Swal) {
                Swal.fire({
                    title: "Simpan & Generate Jadwal?",
                    text: "Sistem akan menyimpan pengaturan waktu ini dan menyusun ulang seluruh jadwal KBM secara optimal dan tanpa bentrok.",
                    icon: "question",
                    showCancelButton: true,
                    confirmButtonText: "Ya, Generate Sekarang!",
                    cancelButtonText: "Batal",
                    confirmButtonColor: "#6366f1",
                    cancelButtonColor: "#6b7280",
                }).then((res) => {
                    if (res.isConfirmed) {
                        executeAutoSchedule();
                    }
                });
            } else {
                if (confirm("Simpan pengaturan dan jalankan auto-generate sekarang?")) {
                    executeAutoSchedule();
                }
            }
        });
    }

    // ==========================================
    // DROPDOWN CETAK JADWAL
    // ==========================================
    const btnDropdownCetak = document.getElementById("btnDropdownCetak");
    const dropdownMenuCetak = document.getElementById("dropdownMenuCetak");

    if (btnDropdownCetak && dropdownMenuCetak) {
        btnDropdownCetak.addEventListener("click", function (e) {
            e.stopPropagation();
            const isOpen = dropdownMenuCetak.style.display === "block";
            dropdownMenuCetak.style.display = isOpen ? "none" : "block";
        });

        window.addEventListener("click", function (e) {
            if (
                !dropdownMenuCetak.contains(e.target) &&
                e.target !== btnDropdownCetak
            ) {
                dropdownMenuCetak.style.display = "none";
            }
        });
    }

    // ==========================================
    // MODAL PREFERENSI KETERSEDIAAN GURU (OFF-DAYS)
    // ==========================================
    const modalPref = document.getElementById("modalPreferensiGuru");
    const formPref = document.getElementById("formPreferensiGuru");
    const btnBukaPref = document.getElementById("btnBukaPreferensiGuru");
    const btnTutupPref = document.getElementById("btnTutupModalPref");
    const btnBatalPref = document.getElementById("btnBatalPref");
    const btnSimpanPref = document.getElementById("btnSimpanPref");
    const selectPrefPtk = document.getElementById("prefPtkId");
    const prefLoadingIndicator = document.getElementById(
        "prefLoadingIndicator",
    );
    const prefFormBody = document.getElementById("prefFormBody");

    function bukaModalPref() {
        if (!modalPref) return;
        modalPref.style.display = "flex";
    }

    function tutupModalPref() {
        if (!modalPref) return;
        modalPref.style.display = "none";
    }

    if (btnBukaPref) btnBukaPref.addEventListener("click", bukaModalPref);
    if (btnTutupPref) btnTutupPref.addEventListener("click", tutupModalPref);
    if (btnBatalPref) btnBatalPref.addEventListener("click", tutupModalPref);

    if (modalPref) {
        window.addEventListener("click", function (e) {
            if (e.target === modalPref) tutupModalPref();
        });
    }

    // Saat Guru Dipilih, Muat Data Preferensi Eksisting
    if (selectPrefPtk) {
        selectPrefPtk.addEventListener("change", function () {
            const ptkId = this.value;
            if (!ptkId) {
                resetPrefForm();
                return;
            }

            const routeGet =
                modalPref?.dataset?.routeGet ||
                "/dashboard/master-data/jadwal-kbm/guru-preferensi";
            if (prefLoadingIndicator)
                prefLoadingIndicator.style.display = "block";
            if (prefFormBody) prefFormBody.style.opacity = "0.5";

            fetch(`${routeGet}?ptk_id=${encodeURIComponent(ptkId)}`)
                .then((res) => res.json())
                .then((res) => {
                    if (res.success && res.preferensi) {
                        populatePrefForm(res.preferensi);
                    } else {
                        resetPrefForm();
                    }
                })
                .catch(() => {
                    resetPrefForm();
                })
                .finally(() => {
                    if (prefLoadingIndicator)
                        prefLoadingIndicator.style.display = "none";
                    if (prefFormBody) prefFormBody.style.opacity = "1";
                });
        });
    }

    function resetPrefForm() {
        document
            .querySelectorAll(".pref-hari-off")
            .forEach((cb) => (cb.checked = false));
        const maxJp = document.getElementById("prefMaxJp");
        if (maxJp) maxJp.value = "";
        const ket = document.getElementById("prefKeterangan");
        if (ket) ket.value = "";
    }

    function populatePrefForm(pref) {
        resetPrefForm();
        const hariOff = Array.isArray(pref.hari_off) ? pref.hari_off : [];
        document.querySelectorAll(".pref-hari-off").forEach((cb) => {
            cb.checked = hariOff.includes(cb.value);
        });
        const maxJp = document.getElementById("prefMaxJp");
        if (maxJp) maxJp.value = pref.max_jp_per_hari || "";
        const ket = document.getElementById("prefKeterangan");
        if (ket) ket.value = pref.keterangan || "";
    }

    if (formPref) {
        formPref.addEventListener("submit", function (e) {
            e.preventDefault();
            if (!selectPrefPtk.value) {
                if (window.Swal) {
                    Swal.fire({
                        icon: "warning",
                        title: "Perhatian",
                        text: "Silakan pilih guru terlebih dahulu.",
                    });
                } else {
                    alert("Silakan pilih guru terlebih dahulu.");
                }
                return;
            }

            if (btnSimpanPref) {
                btnSimpanPref.disabled = true;
                btnSimpanPref.innerHTML =
                    '<i class="fas fa-spinner fa-spin me-1"></i> Menyimpan...';
            }

            const routeSave =
                modalPref?.dataset?.routeSave ||
                "/dashboard/master-data/jadwal-kbm/guru-preferensi";
            const token =
                document
                    .querySelector('meta[name="csrf-token"]')
                    ?.getAttribute("content") ||
                document.querySelector('input[name="_token"]')?.value;

            const formData = new FormData(formPref);

            fetch(routeSave, {
                method: "POST",
                headers: {
                    "X-CSRF-TOKEN": token,
                    Accept: "application/json",
                },
                body: formData,
            })
                .then(async (res) => {
                    const data = await res.json();
                    if (!res.ok) {
                        throw new Error(
                            data.message || "Gagal menyimpan preferensi.",
                        );
                    }
                    return data;
                })
                .then((data) => {
                    tutupModalPref();
                    if (window.Swal) {
                        Swal.fire({
                            icon: "success",
                            title: "Berhasil Disimpan!",
                            text:
                                data.message ||
                                "Preferensi ketersediaan guru berhasil diperbarui.",
                            timer: 1600,
                            showConfirmButton: false,
                        });
                    } else {
                        alert(data.message || "Preferensi berhasil disimpan!");
                    }
                })
                .catch((err) => {
                    if (window.Swal) {
                        Swal.fire({
                            icon: "error",
                            title: "Gagal Menyimpan",
                            text: err.message,
                        });
                    } else {
                        alert(err.message);
                    }
                })
                .finally(() => {
                    if (btnSimpanPref) {
                        btnSimpanPref.disabled = false;
                        btnSimpanPref.innerHTML =
                            '<i class="fas fa-save me-1"></i> Simpan Preferensi';
                    }
                });
        });
    }
});
