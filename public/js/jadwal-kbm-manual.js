/**
 * JavaScript Modular: Jadwal KBM Manual (Koordinator Kelas, Wali Kelas & Admin)
 * Sistem Aplikasi Edukasi (SAE)
 */

// ==========================================
// TOGGLE PEMBERLAKUAN JADWAL AKTIF OLEH ADMIN
// ==========================================
window.handleTogglePemberlakuan = function (btn) {
    if (!btn) return;
    const targetStatus = btn.dataset.status;
    const targetMode = btn.dataset.mode || "manual";
    const url = btn.dataset.url;
    const isAktifkan = (targetStatus === "aktif");

    const modeLabel = targetMode === "manual" ? "Manual (Wali Kelas/Koordinator)" : "Otomatis";
    const otherLabel = targetMode === "manual" ? "Otomatis" : "Manual";

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
            const swalObj = typeof Swal !== "undefined" ? Swal : (window.Swal || null);
            if (swalObj) {
                swalObj.fire({
                    icon: "success",
                    title: "Berhasil!",
                    text: data.message || "Status pemberlakuan jadwal berhasil diperbarui.",
                    timer: 1800,
                    showConfirmButton: false,
                }).then(() => {
                    window.location.reload();
                });
            } else {
                alert(data.message || "Status jadwal berhasil diperbarui.");
                window.location.reload();
            }
        })
        .catch((err) => {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
            const swalObj = typeof Swal !== "undefined" ? Swal : (window.Swal || null);
            if (swalObj) {
                swalObj.fire({
                    icon: "error",
                    title: "Gagal Mengubah Status",
                    text: err.message,
                });
            } else {
                alert("Error: " + err.message);
            }
        });
    };

    const swalObj = typeof Swal !== "undefined" ? Swal : (window.Swal || null);
    if (swalObj) {
        swalObj.fire({
            title: title,
            text: text,
            icon: isAktifkan ? "question" : "warning",
            showCancelButton: true,
            confirmButtonColor: confirmBtnColor,
            cancelButtonColor: "#64748b",
            confirmButtonText: confirmBtnText,
            cancelButtonText: "Batal",
        }).then((result) => {
            if (result.isConfirmed) {
                executeToggle();
            }
        });
    } else {
        if (confirm(`${title}\n\n${text}`)) {
            executeToggle();
        }
    }
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
    const modal = document.getElementById("modalSlotManual");
    if (!modal) return;

    const form = document.getElementById("formSlotManual");
    const modalTitle = document.getElementById("modalSlotTitle");
    const btnTutup = document.getElementById("btnTutupModalSlot");
    const btnBatal = document.getElementById("btnBatalSlot");
    const btnSimpan = document.getElementById("btnSimpanSlot");
    const btnTambah = document.getElementById("btnTambahSlotManual");
    const btnResetRombel = document.getElementById("btnResetRombel");
    const adminSelectRombel = document.getElementById("adminSelectRombel");

    const inputSlotId = document.getElementById("slotId");
    const selectPembelajaran = document.getElementById("slotPembelajaranId");
    const selectHari = document.getElementById("slotHari");
    const inputRuangan = document.getElementById("slotRuangan");
    const selectJamKeMulai = document.getElementById("slotJamKeMulai");
    const selectJamKeSelesai = document.getElementById("slotJamKeSelesai");
    const slotDurasiJp = document.getElementById("slotDurasiJp");
    const slotDurasiWaktu = document.getElementById("slotDurasiWaktu");
    const conflictAlertBox = document.getElementById("conflictAlertBoxManual");
    const inputKeterangan = document.getElementById("slotKeterangan");
    const chkOverwriteSlot = document.getElementById("chkOverwriteSlot");
    const boxOverwriteSlot = document.getElementById("boxOverwriteSlot");

    const routeStore = modal.dataset.routeStore;
    const routeUpdateBase = modal.dataset.routeUpdate;
    const routeDeleteBase = modal.dataset.routeDelete;
    const routeClear = modal.dataset.routeClear;
    const routeConflict = modal.dataset.routeConflict;
    const defaultRombelId = modal.dataset.rombelId;
    const defaultRombelName = modal.dataset.rombelName;

    let slotsData = {};
    try {
        slotsData = JSON.parse(modal.dataset.slots || "{}");
    } catch (e) {
        slotsData = {};
    }

    let routinesData = {};
    try {
        routinesData = JSON.parse(modal.dataset.routines || "{}");
    } catch (e) {
        routinesData = {};
    }

    function getDaySlots(hari) {
        const raw = slotsData[hari] || [];
        return Array.isArray(raw) ? raw : Object.values(raw);
    }

    let conflictTimeout = null;

    // Helper: Ambil CSRF Token
    function getCsrfToken() {
        return (
            document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") ||
            document.querySelector('input[name="_token"]')?.value ||
            ""
        );
    }

    // Dynamic slot dropdowns based on school settings per day
    function updateSlotOptions(targetStart = null, targetEnd = null) {
        const hari = selectHari?.value || "Senin";
        const daySlots = getDaySlots(hari);

        if (!selectJamKeMulai || !selectJamKeSelesai) return;

        const curStart = targetStart !== null ? parseInt(targetStart) : parseInt(selectJamKeMulai.value || "1");
        const curEnd = targetEnd !== null ? parseInt(targetEnd) : parseInt(selectJamKeSelesai.value || curStart);

        selectJamKeMulai.innerHTML = "";
        selectJamKeSelesai.innerHTML = "";

        const dayRoutines = routinesData[hari] || {};

        daySlots.forEach((s) => {
            const ke = parseInt(s.jam_ke || s.ke);
            const mulai = (s.jam_mulai || s.mulai || "").substring(0, 5);
            const selesai = (s.jam_selesai || s.selesai || "").substring(0, 5);
            let label = `Jam Ke-${ke} (${mulai} - ${selesai})`;

            const routine = dayRoutines[ke] || (s.is_break ? { nama: s.break_name || 'Istirahat' } : null);
            if (routine) {
                label += ` [${routine.nama}]`;
            }

            const optMulai = document.createElement("option");
            optMulai.value = ke;
            optMulai.textContent = label;
            if (ke === curStart) optMulai.selected = true;
            if (routine) optMulai.dataset.isRoutine = "1";
            selectJamKeMulai.appendChild(optMulai);

            const optSelesai = document.createElement("option");
            optSelesai.value = ke;
            optSelesai.textContent = label;
            if (ke === curEnd) optSelesai.selected = true;
            if (routine) optSelesai.dataset.isRoutine = "1";
            selectJamKeSelesai.appendChild(optSelesai);
        });

        if (selectJamKeMulai.selectedIndex < 0 && selectJamKeMulai.options.length > 0) {
            selectJamKeMulai.selectedIndex = 0;
        }
        if (selectJamKeSelesai.selectedIndex < 0 && selectJamKeSelesai.options.length > 0) {
            selectJamKeSelesai.selectedIndex = selectJamKeMulai.selectedIndex;
        }
    }

    // Helper: Buka Modal
    function bukaModal(isEdit = false, data = {}) {
        modal.style.display = "flex";
        document.body.style.overflow = "hidden";
        conflictAlertBox.style.display = "none";
        conflictAlertBox.innerHTML = "";
        if (chkOverwriteSlot) chkOverwriteSlot.checked = false;

        if (isEdit) {
            modalTitle.innerHTML = '<i class="fas fa-pen text-primary me-2"></i> Edit Slot Jadwal';
            inputSlotId.value = data.id || "";
            if (selectPembelajaran) selectPembelajaran.value = data.pembelajaran_id || "";
            if (selectHari) selectHari.value = data.hari || "Senin";
            if (inputRuangan) inputRuangan.value = data.ruangan || defaultRombelName || "";
            updateSlotOptions(data.jam_mulai || "1", data.jam_selesai || data.jam_mulai || "1");
            if (inputKeterangan) inputKeterangan.value = data.keterangan || "";
            btnSimpan.innerHTML = '<i class="fas fa-save me-1"></i> Perbarui Slot';
        } else {
            modalTitle.innerHTML = '<i class="fas fa-calendar-plus text-primary me-2"></i> Jadwalkan Pelajaran';
            inputSlotId.value = "";
            if (data.pembelajaran_id && selectPembelajaran) {
                selectPembelajaran.value = data.pembelajaran_id;
            }
            if (data.hari && selectHari) {
                selectHari.value = data.hari;
            }
            updateSlotOptions(data.jam_ke || "1", data.jam_ke || "1");
            if (inputRuangan && !inputRuangan.value) {
                inputRuangan.value = defaultRombelName || "";
            }
            if (inputKeterangan) inputKeterangan.value = "";
            btnSimpan.innerHTML = '<i class="fas fa-save me-1"></i> Simpan Slot';
        }

        btnSimpan.disabled = false;
        updateDurasiInfo();
        triggerConflictCheck();
    }

    // Helper: Tutup Modal
    function tutupModal() {
        modal.style.display = "none";
        document.body.style.overflow = "";
        form.reset();
        inputSlotId.value = "";
        conflictAlertBox.style.display = "none";
        conflictAlertBox.innerHTML = "";
        if (chkOverwriteSlot) chkOverwriteSlot.checked = false;
        btnSimpan.disabled = false;
    }

    // Hitung dan Perbarui Alokasi Durasi JP
    function updateDurasiInfo() {
        const start = parseInt(selectJamKeMulai?.value || "1");
        let end = parseInt(selectJamKeSelesai?.value || "1");

        if (end < start) {
            end = start;
            if (selectJamKeSelesai) selectJamKeSelesai.value = start;
        }

        const totalJp = end - start + 1;
        const hari = selectHari?.value || "Senin";
        const daySlots = getDaySlots(hari);
        const startSlot = daySlots.find((s) => parseInt(s.jam_ke || s.ke) === start);
        const endSlot = daySlots.find((s) => parseInt(s.jam_ke || s.ke) === end);

        if (slotDurasiJp) {
            slotDurasiJp.textContent = `${totalJp} JP`;
        }
        if (slotDurasiWaktu) {
            if (startSlot && endSlot) {
                const tStart = (startSlot.jam_mulai || startSlot.mulai || "").substring(0, 5);
                const tEnd = (endSlot.jam_selesai || endSlot.selesai || "").substring(0, 5);
                slotDurasiWaktu.textContent = `(${tStart} - ${tEnd})`;
            } else {
                slotDurasiWaktu.textContent = `(${totalJp * 45} Menit)`;
            }
        }
    }

    // Validasi Anti-Bentrok secara Realtime (AJAX Debounce)
    function triggerConflictCheck() {
        clearTimeout(conflictTimeout);
        conflictTimeout = setTimeout(checkConflict, 280);
    }

    function checkConflict() {
        if (!routeConflict) return;

        const selectedOption = selectPembelajaran?.options[selectPembelajaran.selectedIndex];
        const ptkId = selectedOption?.dataset?.ptkId;
        const hari = selectHari?.value;
        const jamMulai = selectJamKeMulai?.value;
        const jamSelesai = selectJamKeSelesai?.value;
        const excludeId = inputSlotId?.value || null;
        const ruangan = inputRuangan?.value;

        if (!ptkId || !hari || !jamMulai || !jamSelesai) {
            conflictAlertBox.style.display = "none";
            btnSimpan.disabled = false;
            return;
        }

        const start = parseInt(jamMulai);
        const end = parseInt(jamSelesai);
        const daySlots = getDaySlots(hari);
        const startSlot = daySlots.find((s) => parseInt(s.jam_ke || s.ke) === start);
        const endSlot = daySlots.find((s) => parseInt(s.jam_ke || s.ke) === end);

        const params = new URLSearchParams({
            ptk_id: ptkId,
            rombongan_belajar_id: defaultRombelId,
            hari: hari,
            jam_ke_mulai: jamMulai,
            jam_ke_selesai: jamSelesai,
        });

        const startJam = startSlot?.jam_mulai || (startSlot?.mulai ? (startSlot.mulai.length === 5 ? startSlot.mulai + ":00" : startSlot.mulai) : null);
        const endJam = endSlot?.jam_selesai || (endSlot?.selesai ? (endSlot.selesai.length === 5 ? endSlot.selesai + ":00" : endSlot.selesai) : null);
        if (startJam) params.append("jam_mulai", startJam);
        if (endJam) params.append("jam_selesai", endJam);
        if (excludeId) params.append("exclude_id", excludeId);
        if (ruangan) params.append("ruangan", ruangan);
        params.append("sumber", "manual");

        fetch(`${routeConflict}?${params.toString()}`, {
            headers: { Accept: "application/json" },
        })
            .then((res) => res.json())
            .then((res) => {
                if (res.has_conflict) {
                    if (res.type === "rombel") {
                        // Bentrok jadwal kelas sendiri -> Bisa ditimpa
                        conflictAlertBox.style.display = "block";
                        conflictAlertBox.style.background = "rgba(245, 158, 11, 0.12)";
                        conflictAlertBox.style.border = "1px solid rgba(245, 158, 11, 0.4)";
                        conflictAlertBox.style.color = "#d97706";
                        const namaLama = res.conflict_with?.nama_mata_pelajaran || "Pelajaran lain";
                        conflictAlertBox.innerHTML = `
                            <div style="display: flex; align-items: flex-start; gap: 8px;">
                                <i class="fas fa-triangle-exclamation" style="margin-top: 2px; color: #f59e0b;"></i>
                                <div>
                                    <strong>Slot Waktu Sudah Terisi:</strong>
                                    <div>Jam ini telah terisi oleh <strong>${namaLama}</strong>. Simpan slot akan mengganti/menimpa jadwal tersebut.</div>
                                </div>
                            </div>
                        `;
                        if (chkOverwriteSlot) chkOverwriteSlot.checked = true;
                        btnSimpan.disabled = false;
                    } else {
                        // Bentrok guru di kelas lain atau ketersediaan
                        conflictAlertBox.style.display = "block";
                        conflictAlertBox.style.background = "rgba(239, 68, 68, 0.12)";
                        conflictAlertBox.style.border = "1px solid rgba(239, 68, 68, 0.35)";
                        conflictAlertBox.style.color = "#ef4444";
                        conflictAlertBox.innerHTML = `
                            <div style="display: flex; align-items: flex-start; gap: 8px;">
                                <i class="fas fa-triangle-exclamation" style="margin-top: 2px;"></i>
                                <div>
                                    <strong>Peringatan Bentrok:</strong>
                                    <div>${res.message || "Terdapat jadwal lain pada jam tersebut."}</div>
                                </div>
                            </div>
                        `;
                        btnSimpan.disabled = true;
                    }
                } else {
                    conflictAlertBox.style.display = "block";
                    conflictAlertBox.style.background = "rgba(16, 185, 129, 0.1)";
                    conflictAlertBox.style.border = "1px solid rgba(16, 185, 129, 0.3)";
                    conflictAlertBox.style.color = "#10b981";
                    conflictAlertBox.innerHTML = `
                        <div style="display: flex; align-items: center; gap: 6px;">
                            <i class="fas fa-circle-check"></i>
                            <span>Guru pengampu dan jam belajar tersedia tanpa bentrok.</span>
                        </div>
                    `;
                    btnSimpan.disabled = false;
                }
            })
            .catch(() => {
                conflictAlertBox.style.display = "none";
                btnSimpan.disabled = false;
            });
    }

    // Event Listeners Input
    if (btnTambah) {
        btnTambah.addEventListener("click", () => bukaModal(false));
    }
    if (btnTutup) {
        btnTutup.addEventListener("click", tutupModal);
    }
    if (btnBatal) {
        btnBatal.addEventListener("click", tutupModal);
    }
    modal.addEventListener("click", function (e) {
        if (e.target === modal) tutupModal();
    });

    if (selectPembelajaran) {
        selectPembelajaran.addEventListener("change", triggerConflictCheck);
    }
    if (selectHari) {
        selectHari.addEventListener("change", function () {
            updateSlotOptions();
            updateDurasiInfo();
            triggerConflictCheck();
        });
    }
    if (selectJamKeMulai) {
        selectJamKeMulai.addEventListener("change", function () {
            updateDurasiInfo();
            triggerConflictCheck();
        });
    }
    if (selectJamKeSelesai) {
        selectJamKeSelesai.addEventListener("change", function () {
            updateDurasiInfo();
            triggerConflictCheck();
        });
    }
    if (inputRuangan) {
        inputRuangan.addEventListener("input", triggerConflictCheck);
    }

    // Switch Kelas oleh Admin
    if (adminSelectRombel) {
        adminSelectRombel.addEventListener("change", function () {
            const newRombelId = this.value;
            const currentUrl = new URL(window.location.href);
            currentUrl.searchParams.set("rombel_id", newRombelId);
            window.location.href = currentUrl.toString();
        });
    }

    // Tab Switcher Mobile (Matriks Jadwal vs Guru & Mapel)
    window.switchManualTab = function (targetTab) {
        document.querySelectorAll(".manual-tab-btn").forEach((btn) => {
            if (btn.dataset.tab === targetTab) {
                btn.classList.add("active");
            } else {
                btn.classList.remove("active");
            }
        });

        const panelMapel = document.getElementById("panelMapelWrapper");
        const panelMatriks = document.getElementById("panelMatriksWrapper");

        if (window.innerWidth < 992) {
            if (targetTab === "mapel") {
                if (panelMapel) panelMapel.style.display = "block";
                if (panelMatriks) panelMatriks.style.display = "none";
            } else {
                if (panelMapel) panelMapel.style.display = "none";
                if (panelMatriks) panelMatriks.style.display = "block";
            }
        } else {
            if (panelMapel) panelMapel.style.display = "block";
            if (panelMatriks) panelMatriks.style.display = "block";
        }
    };

    // Helper: Beralih Antara Mode Harian & Mode Tabel di Mobile
    window.switchMatriksMode = function (targetMode) {
        document.querySelectorAll(".matriks-mode-btn").forEach((btn) => {
            if (btn.dataset.mode === targetMode) {
                btn.classList.add("active");
            } else {
                btn.classList.remove("active");
            }
        });

        const viewHarian = document.getElementById("viewHarianMobile");
        const viewTabel = document.getElementById("viewTabelWeekly");

        if (targetMode === "tabel") {
            if (viewHarian) viewHarian.classList.remove("mode-active");
            if (viewTabel) viewTabel.classList.add("mode-active");
        } else {
            if (viewTabel) viewTabel.classList.remove("mode-active");
            if (viewHarian) viewHarian.classList.add("mode-active");
        }
    };

    // Helper: Beralih Hari di Mode Harian
    window.switchHarianDay = function (targetDay) {
        document.querySelectorAll(".harian-day-pill").forEach((pill) => {
            if (pill.dataset.day === targetDay) {
                pill.classList.add("active");
            } else {
                pill.classList.remove("active");
            }
        });

        document.querySelectorAll(".harian-day-feed").forEach((feed) => {
            if (feed.dataset.day === targetDay) {
                feed.classList.add("active");
            } else {
                feed.classList.remove("active");
            }
        });
    };

    window.addEventListener("resize", function () {
        const panelMapel = document.getElementById("panelMapelWrapper");
        const panelMatriks = document.getElementById("panelMatriksWrapper");
        if (window.innerWidth >= 992) {
            if (panelMapel) panelMapel.style.display = "block";
            if (panelMatriks) panelMatriks.style.display = "block";
        } else {
            const activeTabBtn = document.querySelector(".manual-tab-btn.active");
            const activeTab = activeTabBtn ? activeTabBtn.dataset.tab : "matriks";
            window.switchManualTab(activeTab);
        }
    });

    // Inisialisasi default tampilan mobile: Tab Matriks langsung terlihat dengan Mode Harian aktif
    if (window.innerWidth < 992) {
        window.switchManualTab("matriks");
        window.switchMatriksMode("harian");
    }

    // =========================================================================
    // GLOBAL EVENT DELEGATION: Seluruh Aksi Interaktif Jadwal KBM Manual
    // (Menjamin tombol selalu responsif berulang kali tanpa reload halaman)
    // =========================================================================
    document.addEventListener("click", function (e) {
        // 1. Mobile Segmented Tab Switcher (Mapel vs Matriks)
        const tabBtn = e.target.closest(".manual-tab-btn");
        if (tabBtn) {
            e.preventDefault();
            const targetTab = tabBtn.dataset.tab;
            window.switchManualTab(targetTab);
            return;
        }

        // 2. Mobile Matriks Mode Switcher (Harian vs Tabel)
        const modeBtn = e.target.closest(".matriks-mode-btn");
        if (modeBtn) {
            e.preventDefault();
            const targetMode = modeBtn.dataset.mode;
            window.switchMatriksMode(targetMode);
            return;
        }

        // 3. Mobile Day Pill Switcher
        const dayPill = e.target.closest(".harian-day-pill");
        if (dayPill) {
            e.preventDefault();
            const targetDay = dayPill.dataset.day;
            window.switchHarianDay(targetDay);
            return;
        }

        // 4. Klik Edit Slot dari Kartu Pelajaran Terjadwal (Harian / Tabel)
        const btnEdit = e.target.closest(".btn-edit-slot");
        if (btnEdit) {
            e.preventDefault();
            e.stopPropagation();
            const data = {
                id: btnEdit.dataset.id,
                pembelajaran_id: btnEdit.dataset.pembelajaranId,
                hari: btnEdit.dataset.hari,
                jam_mulai: btnEdit.dataset.jamMulai,
                jam_selesai: btnEdit.dataset.jamSelesai,
                ruangan: btnEdit.dataset.ruangan,
                keterangan: btnEdit.dataset.keterangan,
            };
            bukaModal(true, data);
            return;
        }

        // 3. Klik Hapus Slot dari Kartu Pelajaran Terjadwal
        const btnHapus = e.target.closest(".btn-hapus-slot");
        if (btnHapus) {
            e.preventDefault();
            e.stopPropagation();
            const id = btnHapus.dataset.id;
            const namaMapel = btnHapus.dataset.namaMapel || "pelajaran ini";
            const hari = btnHapus.dataset.hari || "";
            const jam = btnHapus.dataset.jam || "";
            handleKonfirmasiHapusSlot(id, namaMapel, hari, jam);
            return;
        }

        // 4. Klik Tombol "+ Jadwalkan" dari Panel Kiri
        const btnJadwalkan = e.target.closest(".btn-jadwalkan-mapel");
        if (btnJadwalkan) {
            e.preventDefault();
            e.stopPropagation();
            const data = {
                pembelajaran_id: btnJadwalkan.dataset.pembelajaranId,
                mapel_id: btnJadwalkan.dataset.mapelId,
                ptk_id: btnJadwalkan.dataset.ptkId,
            };
            bukaModal(false, data);
            return;
        }

        // 5. Klik Slot Kosong dari Tabel Matriks
        const btnEmpty = e.target.closest(".btn-empty-slot");
        if (btnEmpty) {
            e.preventDefault();
            e.stopPropagation();
            const data = {
                hari: btnEmpty.dataset.hari,
                jam_ke: btnEmpty.dataset.jamKe,
            };
            bukaModal(false, data);
            return;
        }
    });

    // Helper Konfirmasi Hapus Slot (SweetAlert2 Bersih tanpa Nested Promise Trap)
    function handleKonfirmasiHapusSlot(id, namaMapel, hari, jam) {
        const swalObj = typeof Swal !== "undefined" ? Swal : (window.Swal || null);
        if (!swalObj) {
            if (confirm(`Hapus jadwal ${namaMapel} pada ${hari} (${jam})?`)) {
                hapusSlotRequest(id);
            }
            return;
        }

        swalObj.fire({
            title: "Hapus Slot Jadwal?",
            html: `Apakah Anda yakin ingin menghapus jadwal <strong>${namaMapel}</strong> pada hari <strong>${hari} (${jam})</strong>?`,
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#ef4444",
            cancelButtonColor: "#64748b",
            confirmButtonText: '<i class="fas fa-trash me-1"></i> Ya, Hapus Slot',
            cancelButtonText: "Batal",
        }).then((result) => {
            if (result.isConfirmed) {
                hapusSlotRequest(id);
            }
        });
    }

    function hapusSlotRequest(id) {
        const deleteUrl = `${routeDeleteBase}/${id}`;
        const swalObj = typeof Swal !== "undefined" ? Swal : (window.Swal || null);

        if (swalObj) {
            swalObj.fire({
                title: "Menghapus Slot...",
                text: "Mohon tunggu sebentar",
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    swalObj.showLoading();
                }
            });
        }

        fetch(deleteUrl, {
            method: "DELETE",
            headers: {
                "X-CSRF-TOKEN": getCsrfToken(),
                Accept: "application/json",
            },
        })
            .then(async (res) => {
                const data = await res.json().catch(() => ({}));
                if (!res.ok) throw new Error(data.message || "Gagal menghapus jadwal.");
                return data;
            })
            .then((res) => {
                if (swalObj) {
                    swalObj.fire({
                        icon: "success",
                        title: "Terhapus!",
                        text: res.message || "Slot jadwal berhasil dihapus.",
                        timer: 1500,
                        showConfirmButton: false,
                    }).then(() => window.location.reload());
                } else {
                    alert(res.message || "Slot jadwal berhasil dihapus.");
                    window.location.reload();
                }
            })
            .catch((err) => {
                if (swalObj) {
                    swalObj.fire({
                        icon: "error",
                        title: "Gagal Menghapus",
                        text: err.message || "Terjadi kesalahan saat menghapus slot jadwal.",
                    });
                } else {
                    alert("Error: " + err.message);
                }
            });
    }

    // Reset Seluruh Jadwal Rombel (Bersih tanpa Nested Swal Trap)
    if (btnResetRombel) {
        btnResetRombel.addEventListener("click", function () {
            const rombelId = this.dataset.rombelId;
            const rombelName = this.dataset.rombelName || "kelas ini";
            const swalObj = typeof Swal !== "undefined" ? Swal : (window.Swal || null);

            if (!swalObj) {
                if (confirm(`Kosongkan seluruh jadwal mata pelajaran kelas ${rombelName}?`)) {
                    resetRombelRequest(rombelId);
                }
                return;
            }

            swalObj.fire({
                title: `Reset Jadwal Kelas ${rombelName}?`,
                html: `Seluruh slot mata pelajaran yang telah dijadwalkan pada kelas <strong>${rombelName}</strong> akan dihapus (kegiatan rutin sekolah tetap dipertahankan). Tindakan ini tidak dapat dibatalkan!`,
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#ef4444",
                cancelButtonColor: "#64748b",
                confirmButtonText: '<i class="fas fa-rotate-left me-1"></i> Ya, Kosongkan Jadwal',
                cancelButtonText: "Batal",
            }).then((result) => {
                if (result.isConfirmed) {
                    resetRombelRequest(rombelId);
                }
            });
        });
    }

    function resetRombelRequest(rombelId) {
        const swalObj = typeof Swal !== "undefined" ? Swal : (window.Swal || null);
        if (swalObj) {
            swalObj.fire({
                title: "Mereset Jadwal...",
                text: "Mengosongkan seluruh slot jadwal kelas...",
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    swalObj.showLoading();
                }
            });
        }

        fetch(routeClear, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": getCsrfToken(),
                Accept: "application/json",
            },
            body: JSON.stringify({ rombongan_belajar_id: rombelId }),
        })
            .then(async (res) => {
                const data = await res.json().catch(() => ({}));
                if (!res.ok) throw new Error(data.message || "Gagal mengosongkan jadwal kelas.");
                return data;
            })
            .then((res) => {
                if (swalObj) {
                    swalObj.fire({
                        icon: "success",
                        title: "Berhasil Direset!",
                        text: res.message || "Jadwal kelas berhasil dikosongkan.",
                        timer: 1500,
                        showConfirmButton: false,
                    }).then(() => window.location.reload());
                } else {
                    alert(res.message);
                    window.location.reload();
                }
            })
            .catch((err) => {
                if (swalObj) {
                    swalObj.fire({
                        icon: "error",
                        title: "Gagal Mengosongkan",
                        text: err.message || "Terjadi kesalahan saat mengosongkan jadwal kelas.",
                    });
                } else {
                    alert(err.message);
                }
            });
    }

    // Submit Formulir Simpan / Update Slot
    function submitFormSlot(forceOverwrite = false) {
        const slotId = inputSlotId.value;
        const isEdit = Boolean(slotId);
        const url = isEdit ? `${routeUpdateBase}/${slotId}` : routeStore;
        const method = isEdit ? "PUT" : "POST";

        const shouldOverwrite = forceOverwrite || Boolean(chkOverwriteSlot?.checked);

        const payload = {
            rombongan_belajar_id: defaultRombelId,
            pembelajaran_id: selectPembelajaran.value,
            hari: selectHari.value,
            jam_ke_mulai: selectJamKeMulai.value,
            jam_ke_selesai: selectJamKeSelesai.value,
            ruangan: inputRuangan.value,
            keterangan: inputKeterangan.value,
            overwrite: shouldOverwrite,
        };

        btnSimpan.disabled = true;
        btnSimpan.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Menyimpan...';

        fetch(url, {
            method: method,
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": getCsrfToken(),
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
                                    if (chkOverwriteSlot) chkOverwriteSlot.checked = true;
                                    submitFormSlot(true);
                                } else {
                                    btnSimpan.disabled = false;
                                    btnSimpan.innerHTML = isEdit
                                        ? '<i class="fas fa-save me-1"></i> Perbarui Slot'
                                        : '<i class="fas fa-save me-1"></i> Simpan Slot';
                                }
                            });
                            return null;
                        }
                    }
                    throw new Error(data.message || "Gagal menyimpan slot jadwal.");
                }
                return data;
            })
            .then((res) => {
                if (!res) return;
                tutupModal();
                const swalObj = typeof Swal !== "undefined" ? Swal : (window.Swal || null);
                if (swalObj) {
                    swalObj.fire({
                        icon: "success",
                        title: "Berhasil!",
                        text: res.message || "Slot jadwal berhasil disimpan.",
                        timer: 1800,
                        showConfirmButton: false,
                    }).then(() => window.location.reload());
                } else {
                    alert(res.message);
                    window.location.reload();
                }
            })
            .catch((err) => {
                const swalObj = typeof Swal !== "undefined" ? Swal : (window.Swal || null);
                if (swalObj) {
                    swalObj.fire({
                        icon: "error",
                        title: "Tidak Dapat Menyimpan",
                        text: err.message,
                    });
                } else {
                    alert(err.message);
                }
            })
            .finally(() => {
                btnSimpan.disabled = false;
                btnSimpan.innerHTML = isEdit
                    ? '<i class="fas fa-save me-1"></i> Perbarui Slot'
                    : '<i class="fas fa-save me-1"></i> Simpan Slot';
            });
    }

    form.addEventListener("submit", function (e) {
        e.preventDefault();
        submitFormSlot(false);
    });
});
