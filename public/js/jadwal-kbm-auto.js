document.addEventListener("DOMContentLoaded", function () {
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

        const upacaraAktif = !!document.getElementById("setUpacaraAktif")?.checked;
        const upacaraJamKe = parseInt(document.getElementById("setUpacaraJamKe")?.value) || 1;
        const upacaraDurasi = parseInt(document.getElementById("setUpacaraDurasi")?.value) || 45;

        const pembiasaanAktif = !!document.getElementById("setPembiasaanAktif")?.checked;
        const pembiasaanJamKe = parseInt(document.getElementById("setPembiasaanJamKe")?.value) || 1;
        const pembiasaanDurasi = parseInt(document.getElementById("setPembiasaanDurasi")?.value) || 40;

        const parts = jamMulaiVal.split(":");
        const startHour = parseInt(parts[0]) || 7;
        const startMin = parseInt(parts[1]) || 15;
        const baseMinutes = startHour * 60 + startMin;

        const calcTime = (jp, dh) => {
            if (jp <= 0) return "(Libur)";
            let totalMins = baseMinutes;
            for (let k = 1; k <= jp; k++) {
                let slotDur = durasiVal;
                if (dh === "Senin" && upacaraAktif && k === upacaraJamKe) {
                    slotDur = upacaraDurasi;
                } else if (dh === "Jumat" && pembiasaanAktif && k === pembiasaanJamKe) {
                    slotDur = pembiasaanDurasi;
                } else if (istirahatAktif && k === istirahatJamKe && dh !== "Jumat") {
                    slotDur = istirahatDurasi;
                }
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
                    const timeStr = calcTime(jp, dh);
                    pulangSpan.textContent = `Pulang: ${timeStr}`;
                    pulangSpan.style.color = "var(--text-muted)";
                }
            }

            // Hitung net KBM
            if (jp > 0) {
                let netJp = jp;
                if (dh === "Senin" && upacaraAktif && jp >= upacaraJamKe) netJp -= 1;
                if (dh === "Jumat" && pembiasaanAktif && jp >= pembiasaanJamKe) netJp -= 1;
                if (istirahatAktif && dh !== "Jumat" && jp >= istirahatJamKe) netJp -= 1;
                sumKbm[t] = (sumKbm[t] || 0) + Math.max(0, netJp);
            }
        });

        // Update Label & Hidden Inputs Total KBM
        ["10", "11", "12"].forEach((t) => {
            const chk = document.querySelector(`.chk-tingkat-scope[data-tingkat="${t}"]`);
            const isExcluded = chk && !chk.checked;
            const totalSpan = document.getElementById(t === "10" ? "totalKbmX" : (t === "11" ? "totalKbmXI" : "totalKbmXII"));
            if (totalSpan) {
                if (isExcluded) {
                    totalSpan.textContent = "PKL / Off";
                    totalSpan.style.color = "#ef4444";
                } else {
                    totalSpan.textContent = `${sumKbm[t] || 0} JP`;
                    totalSpan.style.color = (t === "10" ? "#3b82f6" : (t === "11" ? "#8b5cf6" : "#ec4899"));
                }
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

            const formatted = calcTime(jp, dh);
            selesaiSpan.textContent = formatted;
            selesaiSpan.style.color = "var(--primary)";
        });
    }

    // SINKRONISASI CAKUPAN TINGKAT & PENGECUALIAN KELAS (PKL)
    const selectCakupanTingkat = document.getElementById("selectCakupanTingkat");
    const chkTingkatScopes = document.querySelectorAll(".chk-tingkat-scope");

    function applyTingkatScopeState() {
        chkTingkatScopes.forEach((chk) => {
            const t = chk.dataset.tingkat;
            const isChecked = chk.checked;

            const th = document.querySelector(`.th-col-tingkat[data-col-tingkat="${t}"]`);
            const tds = document.querySelectorAll(`.td-col-tingkat[data-col-tingkat="${t}"]`);
            const badgeHdr = th?.querySelector(".badge-tingkat-header");

            if (isChecked) {
                if (th) {
                    th.style.opacity = "1";
                    th.style.pointerEvents = "auto";
                }
                tds.forEach((td) => {
                    td.style.opacity = "1";
                    td.style.pointerEvents = "auto";
                    const inp = td.querySelector(".input-tingkat-slot");
                    if (inp) inp.disabled = false;
                });
                if (badgeHdr) {
                    badgeHdr.style.background = (t === "10" ? "#3b82f6" : (t === "11" ? "#8b5cf6" : "#ec4899"));
                    badgeHdr.textContent = (t === "10" ? "Kelas X" : (t === "11" ? "Kelas XI" : "Kelas XII"));
                }
            } else {
                if (th) {
                    th.style.opacity = "0.35";
                    th.style.pointerEvents = "none";
                }
                tds.forEach((td) => {
                    td.style.opacity = "0.35";
                    td.style.pointerEvents = "none";
                    const inp = td.querySelector(".input-tingkat-slot");
                    if (inp) inp.disabled = true;
                });
                if (badgeHdr) {
                    badgeHdr.style.background = "#ef4444";
                    badgeHdr.textContent = (t === "12" ? "Kelas XII (PKL)" : `Kelas ${t === "10" ? "X" : "XI"} (Off)`);
                }
            }
        });

        // Tampilkan/sembunyikan badge PKL pada pil checkbox Kelas XII
        const chk12 = document.querySelector('.chk-tingkat-scope[data-tingkat="12"]');
        const badgePkl = document.querySelector(".badge-pkl-status");
        if (badgePkl && chk12) {
            badgePkl.style.display = chk12.checked ? "none" : "inline-block";
        }

        updateRealtimeModalJamSelesai();
    }

    if (selectCakupanTingkat) {
        selectCakupanTingkat.addEventListener("change", function () {
            const val = this.value;
            if (val === "" || val === "all") {
                chkTingkatScopes.forEach((chk) => (chk.checked = true));
            } else if (val === "no_12") {
                chkTingkatScopes.forEach((chk) => (chk.checked = chk.dataset.tingkat !== "12"));
            } else if (val === "no_11") {
                chkTingkatScopes.forEach((chk) => (chk.checked = chk.dataset.tingkat !== "11"));
            } else if (val === "no_10") {
                chkTingkatScopes.forEach((chk) => (chk.checked = chk.dataset.tingkat !== "10"));
            } else if (val === "10" || val === "11" || val === "12") {
                chkTingkatScopes.forEach((chk) => (chk.checked = chk.dataset.tingkat === val));
            }
            applyTingkatScopeState();
        });
    }

    chkTingkatScopes.forEach((chk) => {
        chk.addEventListener("change", function () {
            const checkedVals = Array.from(chkTingkatScopes).filter((c) => c.checked).map((c) => c.dataset.tingkat);
            if (checkedVals.length === 3) {
                if (selectCakupanTingkat) selectCakupanTingkat.value = "";
            } else if (checkedVals.length === 2 && !checkedVals.includes("12")) {
                if (selectCakupanTingkat) selectCakupanTingkat.value = "no_12";
            } else if (checkedVals.length === 2 && !checkedVals.includes("11")) {
                if (selectCakupanTingkat) selectCakupanTingkat.value = "no_11";
            } else if (checkedVals.length === 2 && !checkedVals.includes("10")) {
                if (selectCakupanTingkat) selectCakupanTingkat.value = "no_10";
            } else if (checkedVals.length === 1) {
                if (selectCakupanTingkat) selectCakupanTingkat.value = checkedVals[0];
            } else {
                if (selectCakupanTingkat) selectCakupanTingkat.value = "custom";
            }
            applyTingkatScopeState();
        });
    });

    document.addEventListener("input", function (e) {
        if (
            e.target.matches(".input-slot-harian, .input-tingkat-slot") ||
            e.target.id === "setJamMulai" ||
            e.target.id === "setDurasiJp" ||
            e.target.id === "setIstirahatJamKe" ||
            e.target.id === "setIstirahatDurasi" ||
            e.target.id === "setUpacaraJamKe" ||
            e.target.id === "setUpacaraDurasi" ||
            e.target.id === "setPembiasaanJamKe" ||
            e.target.id === "setPembiasaanDurasi"
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
            e.target.id === "setUpacaraAktif" ||
            e.target.id === "setUpacaraJamKe" ||
            e.target.id === "setUpacaraDurasi" ||
            e.target.id === "setPembiasaanAktif" ||
            e.target.id === "setPembiasaanJamKe" ||
            e.target.id === "setPembiasaanDurasi" ||
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

