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
    // MODAL PENGATURAN JAM & SLOT KBM
    // ==========================================
    const modalPengaturan = document.getElementById("modalPengaturanSlot");
    const formPengaturan = document.getElementById("formPengaturanSlot");
    const btnBukaPengaturan = document.getElementById("btnBukaPengaturanSlot");
    const btnTutupPengaturan = document.getElementById(
        "btnTutupModalPengaturan",
    );
    const btnBatalPengaturan = document.getElementById("btnBatalPengaturan");
    const btnSimpanPengaturan = document.getElementById("btnSimpanPengaturan");

    function bukaModalPengaturan() {
        if (!modalPengaturan) return;
        modalPengaturan.style.display = "flex";
        updateRealtimeModalJamSelesai();
    }

    function tutupModalPengaturan() {
        if (!modalPengaturan) return;
        modalPengaturan.style.display = "none";
    }

    // ==========================================
    // REALTIME CALCULATOR MODAL PENGATURAN SLOT
    // ==========================================
    function updateRealtimeModalJamSelesai() {
        const jamMulaiInput = document.getElementById("setJamMulai");
        const durasiInput = document.getElementById("setDurasiJp");
        const jamMulaiVal = jamMulaiInput?.value || "07:15";
        const durasiVal = parseInt(durasiInput?.value) || 45;

        const istirahatAktif = !!document.getElementById("setIstirahatAktif")?.checked;
        const istirahatJamKe = parseInt(document.getElementById("setIstirahatJamKe")?.value) || 0;
        const istirahatDurasi = parseInt(document.getElementById("setIstirahatDurasi")?.value) || durasiVal;

        const parts = jamMulaiVal.split(":");
        const startHour = parseInt(parts[0]) || 7;
        const startMin = parseInt(parts[1]) || 0;
        const baseMinutes = startHour * 60 + startMin;

        document.querySelectorAll(".input-slot-harian").forEach((input) => {
            const dh = input.dataset.hari;
            const selesaiSpan = document.querySelector(`.badge-jam-selesai[data-hari="${dh}"]`);
            if (!selesaiSpan) return;

            const rawVal = input.value.trim();
            const jp = (rawVal === "" || isNaN(rawVal)) ? 0 : parseInt(rawVal);

            if (jp <= 0) {
                selesaiSpan.textContent = "-";
                selesaiSpan.style.color = "var(--text-muted)";
                return;
            }

            let totalMins = baseMinutes;
            for (let k = 1; k <= jp; k++) {
                const slotDur = (istirahatAktif && k === istirahatJamKe) ? istirahatDurasi : durasiVal;
                totalMins += slotDur;
            }

            const endHour = Math.floor(totalMins / 60) % 24;
            const endMin = totalMins % 60;
            const formatted = `${String(endHour).padStart(2, "0")}:${String(endMin).padStart(2, "0")}`;
            selesaiSpan.textContent = formatted;
            selesaiSpan.style.color = "var(--primary)";
        });
    }

    // Listener realtime input & change di modal pengaturan slot
    document.addEventListener("input", function (e) {
        if (
            e.target.matches(".input-slot-harian") ||
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
            e.target.matches(".input-slot-harian") ||
            e.target.id === "setJamMulai" ||
            e.target.id === "setDurasiJp" ||
            e.target.id === "setIstirahatAktif" ||
            e.target.id === "setIstirahatJamKe" ||
            e.target.id === "setIstirahatDurasi" ||
            e.target.matches(".check-hari-aktif")
        ) {
            updateRealtimeModalJamSelesai();
        }
    });

    // Sinkronkan slot dinamis ketika table/page di-refresh via AJAX
    function refreshDynamicSlots(newDoc = null) {
        const docTarget = newDoc || document;
        const targetModal = docTarget.getElementById("modalJadwalKbm");
        if (targetModal && targetModal.dataset.slots) {
            try {
                const rawSlots = JSON.parse(targetModal.dataset.slots);
                if (Array.isArray(rawSlots) && rawSlots.length > 0) {
                    const dynamicMap = {};
                    rawSlots.forEach((s) => {
                        const k = parseInt(s.jam_ke);
                        if (k) {
                            dynamicMap[k] = { mulai: s.mulai, selesai: s.selesai };
                        }
                    });
                    slotMapping = dynamicMap;
                    if (modal) modal.dataset.slots = targetModal.dataset.slots;
                }
            } catch (err) {
                console.warn("Gagal refresh slot dinamis:", err);
            }
        }
    }

    window.addEventListener("sae:tableRefreshed", function (e) {
        if (e.detail && e.detail.doc) {
            refreshDynamicSlots(e.detail.doc);
        }
    });

    if (btnBukaPengaturan)
        btnBukaPengaturan.addEventListener("click", bukaModalPengaturan);
    if (btnTutupPengaturan)
        btnTutupPengaturan.addEventListener("click", tutupModalPengaturan);
    if (btnBatalPengaturan)
        btnBatalPengaturan.addEventListener("click", tutupModalPengaturan);

    if (modalPengaturan) {
        window.addEventListener("click", function (e) {
            if (e.target === modalPengaturan) tutupModalPengaturan();
        });
    }

    if (formPengaturan) {
        formPengaturan.addEventListener("submit", function (e) {
            e.preventDefault();
            if (btnSimpanPengaturan) {
                btnSimpanPengaturan.disabled = true;
                btnSimpanPengaturan.innerHTML =
                    '<i class="fas fa-spinner fa-spin me-1"></i> Menyimpan...';
            }

            const routeUrl =
                modalPengaturan?.dataset?.routePengaturan ||
                "/dashboard/master-data/jadwal-kbm/pengaturan";
            const token =
                document
                    .querySelector('meta[name="csrf-token"]')
                    ?.getAttribute("content") ||
                document.querySelector('input[name="_token"]')?.value;

            const formData = new FormData(formPengaturan);

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
                    if (!res.ok) {
                        throw new Error(
                            data.message || "Gagal menyimpan pengaturan.",
                        );
                    }
                    return data;
                })
                .then((data) => {
                    tutupModalPengaturan();
                    if (window.Swal) {
                        Swal.fire({
                            icon: "success",
                            title: "Berhasil Disimpan!",
                            text:
                                data.message ||
                                "Pengaturan slot jam KBM telah diperbarui.",
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
                            title: "Gagal Menyimpan",
                            text: err.message,
                        });
                    } else {
                        alert(err.message);
                    }
                })
                .finally(() => {
                    if (btnSimpanPengaturan) {
                        btnSimpanPengaturan.disabled = false;
                        btnSimpanPengaturan.innerHTML =
                            '<i class="fas fa-save me-1"></i> Simpan Pengaturan';
                    }
                });
        });
    }

    // ==========================================
    // MODAL TOMBOL SAKTI (AUTO-GENERATE JADWAL)
    // ==========================================
    const modalAuto = document.getElementById("modalAutoGenerate");
    const formAuto = document.getElementById("formAutoGenerate");
    const btnBukaAuto = document.getElementById("btnBukaAutoGenerate");
    const btnTutupAuto = document.getElementById("btnTutupModalAuto");
    const btnBatalAuto = document.getElementById("btnBatalAuto");
    const btnEksekusiAuto = document.getElementById("btnEksekusiAuto");

    function bukaModalAuto() {
        if (!modalAuto) return;
        modalAuto.style.display = "flex";
    }

    function tutupModalAuto() {
        if (!modalAuto) return;
        modalAuto.style.display = "none";
    }

    if (btnBukaAuto) btnBukaAuto.addEventListener("click", bukaModalAuto);
    if (btnTutupAuto) btnTutupAuto.addEventListener("click", tutupModalAuto);
    if (btnBatalAuto) btnBatalAuto.addEventListener("click", tutupModalAuto);

    if (modalAuto) {
        window.addEventListener("click", function (e) {
            if (e.target === modalAuto) tutupModalAuto();
        });
    }

    if (formAuto) {
        formAuto.addEventListener("submit", function (e) {
            e.preventDefault();

            const routeUrl =
                modalAuto?.dataset?.routeAutoGenerate ||
                "/dashboard/master-data/jadwal-kbm/auto-generate";
            const token =
                document
                    .querySelector('meta[name="csrf-token"]')
                    ?.getAttribute("content") ||
                document.querySelector('input[name="_token"]')?.value;

            const formData = new FormData(formAuto);

            const executeAutoSchedule = () => {
                if (btnEksekusiAuto) {
                    btnEksekusiAuto.disabled = true;
                    btnEksekusiAuto.innerHTML =
                        '<i class="fas fa-spinner fa-spin me-1"></i> Mengoptimalkan...';
                }

                if (window.Swal) {
                    Swal.fire({
                        title: "Menjalankan Tombol Sakti...",
                        html: '<div style="font-size: 0.9rem; color: #6b7280; margin-top: 8px;">Sistem AI Constraint Solver sedang memetakan data pembelajaran seluruh rombel dan guru secara optimal tanpa bentrok...</div>',
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
                        if (!res.ok) {
                            throw new Error(
                                data.message ||
                                    "Gagal menjalankan generate jadwal otomatis.",
                            );
                        }
                        return data;
                    })
                    .then((data) => {
                        tutupModalAuto();
                        if (window.Swal) {
                            Swal.fire({
                                icon: "success",
                                title: "Auto-Generate Selesai!",
                                html: `<strong>${data.message}</strong><br><small style="color: #6b7280;">Total ${data.total_generated || 0} slot pelajaran telah dijadwalkan tanpa bentrok.</small>`,
                                confirmButtonText: "Lihat Jadwal Sekarang",
                                confirmButtonColor: "#6366f1",
                            }).then(() => {
                                if (
                                    window.SAERealtime &&
                                    typeof window.SAERealtime.refreshCards ===
                                        "function"
                                ) {
                                    window.SAERealtime.refreshCards({
                                        refreshTable: true,
                                    });
                                } else if (
                                    typeof window.refreshLiveTable ===
                                    "function"
                                ) {
                                    window.refreshLiveTable(
                                        window.location.href,
                                    );
                                } else {
                                    window.location.reload();
                                }
                            });
                        } else {
                            alert(data.message || "Generate jadwal selesai!");
                            if (
                                window.SAERealtime &&
                                typeof window.SAERealtime.refreshCards ===
                                    "function"
                            ) {
                                window.SAERealtime.refreshCards({
                                    refreshTable: true,
                                });
                            } else if (
                                typeof window.refreshLiveTable === "function"
                            ) {
                                window.refreshLiveTable(window.location.href);
                            } else {
                                window.location.reload();
                            }
                        }
                    })
                    .catch((err) => {
                        if (window.Swal) {
                            Swal.fire({
                                icon: "error",
                                title: "Gagal Auto-Generate",
                                text: err.message,
                            });
                        } else {
                            alert(err.message);
                        }
                    })
                    .finally(() => {
                        if (btnEksekusiAuto) {
                            btnEksekusiAuto.disabled = false;
                            btnEksekusiAuto.innerHTML =
                                '<i class="fas fa-wand-magic-sparkles me-1"></i> Mulai Generate Otomatis';
                        }
                    });
            };

            if (window.Swal) {
                Swal.fire({
                    title: "Jalankan Auto-Generate?",
                    text: "Sistem akan membaca beban mengajar guru dari data Pembelajaran dan menyusun jadwal ke seluruh hari secara otomatis.",
                    icon: "question",
                    showCancelButton: true,
                    confirmButtonText: "Ya, Jalankan Sekarang!",
                    cancelButtonText: "Batal",
                    confirmButtonColor: "#6366f1",
                    cancelButtonColor: "#6b7280",
                }).then((res) => {
                    if (res.isConfirmed) {
                        executeAutoSchedule();
                    }
                });
            } else {
                if (confirm("Jalankan Auto-Generate Jadwal?")) {
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
