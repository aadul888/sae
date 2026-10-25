/**
 * SAE - Kartu Pelajar Tap Reader RFID Selesai Cetak & Registrasi Presensi
 * Memproses verifikasi kartu selesai cetak via tap reader RFID USB dan registrasi ke sistem presensi secara berkelanjutan.
 */

(function () {
    let isProcessing = false;
    let currentIdentifiedStudent = null;
    let sessionSuccessList = [];
    let liveRefreshDebounceTimer = null;
    let rfidBurstTimer = null;
    let studentSearchTimer = null;

    // Helper Toast Non-Blocking
    function showNotification(message, type = "success") {
        if (window.SAE && typeof window.SAE.toast === "function") {
            window.SAE.toast(message, type, 2800);
            return;
        }
        if (typeof Swal !== "undefined") {
            const Toast = Swal.mixin({
                toast: true,
                position: "top-end",
                showConfirmButton: false,
                timer: 2600,
                timerProgressBar: true,
            });
            Toast.fire({
                icon: type === "danger" ? "error" : type,
                title: message,
            });
        }
    }

    // Suara Beep Sukses & Gagal Menggunakan Web Audio API
    function playBeepSound() {
        try {
            const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
            const osc = audioCtx.createOscillator();
            const gain = audioCtx.createGain();
            osc.connect(gain);
            gain.connect(audioCtx.destination);
            osc.type = "sine";
            // Melodi dua nada cepat (high-pitched chime)
            osc.frequency.setValueAtTime(880, audioCtx.currentTime); // A5
            osc.frequency.setValueAtTime(1320, audioCtx.currentTime + 0.08); // E6
            gain.gain.setValueAtTime(0.2, audioCtx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 0.2);
            osc.start();
            osc.stop(audioCtx.currentTime + 0.2);
        } catch (e) {}
    }

    function playErrorSound() {
        try {
            const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
            const osc = audioCtx.createOscillator();
            const gain = audioCtx.createGain();
            osc.connect(gain);
            gain.connect(audioCtx.destination);
            osc.type = "sawtooth";
            osc.frequency.setValueAtTime(220, audioCtx.currentTime); // Low A3
            gain.gain.setValueAtTime(0.25, audioCtx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 0.3);
            osc.start();
            osc.stop(audioCtx.currentTime + 0.3);
        } catch (e) {}
    }

    // Buka modal tap RFID selesai, mendukung pre-fill siswa jika diklik dari baris antrean
    window.openModalScanRfidSelesai = function (nisn = null, nama = null, requestId = null, pdId = null) {
        const m = document.getElementById("modalScanRfidSelesai");
        if (!m) return;
        m.style.display = "flex";
        document.body.style.overflow = "hidden";

        if (nisn || pdId) {
            currentIdentifiedStudent = {
                nisn: nisn || "",
                nama: nama || "Peserta Didik",
                requestId: requestId || "",
                peserta_didik_id: pdId || "",
                rombel: "",
                rfid_uid: "",
            };
            displayIdentifiedStudent(currentIdentifiedStudent);
        } else {
            window.resetIdentifiedStudent();
        }

        focusActiveInput();
    };

    window.closeModalScanRfidSelesai = function () {
        const m = document.getElementById("modalScanRfidSelesai");
        if (!m) return;
        m.style.display = "none";
        document.body.style.overflow = "";
        window.resetIdentifiedStudent();
    };

    function focusActiveInput() {
        setTimeout(() => {
            if (currentIdentifiedStudent) {
                const input = document.getElementById("inputScanCode");
                if (input) input.focus();
            } else {
                const searchInput = document.getElementById("scanSearchStudentInput");
                if (searchInput) searchInput.focus();
            }
        }, 60);
    }

    function displayIdentifiedStudent(s) {
        const selectStep = document.getElementById("scanSelectStudentStep");
        const card = document.getElementById("scanIdentifiedCard");
        const namaEl = document.getElementById("scanStudentNama");
        const nisnEl = document.getElementById("scanStudentNisn");
        const rombelEl = document.getElementById("scanStudentRombel");
        const photoEl = document.getElementById("scanStudentPhoto");
        const rfidStatus = document.getElementById("scanStudentCurrentRfid");

        if (selectStep) selectStep.style.display = "none";
        if (card) card.style.display = "block";
        if (namaEl) namaEl.textContent = s.nama || "Peserta Didik";
        if (nisnEl) nisnEl.textContent = s.nisn || "-";
        if (rombelEl) rombelEl.textContent = s.nama_rombel || s.rombel || "";
        if (photoEl && s.foto_url) photoEl.src = s.foto_url;
        if (rfidStatus) rfidStatus.textContent = s.rfid_uid || "Belum Ada (Silakan Tap Kartu Fisik)";

        // Highlight kotak tap RFID
        const tapBox = document.getElementById("scanRfidTapBox");
        if (tapBox) {
            tapBox.style.borderColor = "#059669";
            tapBox.style.background = "rgba(5, 150, 105, 0.05)";
        }

        const inputScan = document.getElementById("inputScanCode");
        if (inputScan) {
            inputScan.value = "";
            inputScan.placeholder = `Tempelkan kartu RFID untuk ${s.nama}...`;
            setTimeout(() => inputScan.focus(), 80);
        }
    }

    window.resetIdentifiedStudent = function () {
        currentIdentifiedStudent = null;
        const selectStep = document.getElementById("scanSelectStudentStep");
        const card = document.getElementById("scanIdentifiedCard");
        if (card) card.style.display = "none";
        if (selectStep) selectStep.style.display = "block";

        const tapBox = document.getElementById("scanRfidTapBox");
        if (tapBox) {
            tapBox.style.borderColor = "var(--border-color)";
            tapBox.style.background = "var(--bg-hover)";
        }

        const inputScan = document.getElementById("inputScanCode");
        if (inputScan) {
            inputScan.value = "";
            inputScan.placeholder = "Pilih peserta didik terlebih dahulu...";
        }

        const searchInput = document.getElementById("scanSearchStudentInput");
        if (searchInput) {
            searchInput.value = "";
            setTimeout(() => searchInput.focus(), 60);
        }
    };

    window.submitScanSelesai = function () {
        const input = document.getElementById("inputScanCode");
        const val = input ? input.value.trim() : "";

        if (!currentIdentifiedStudent) {
            playErrorSound();
            showNotification("Silakan pilih peserta didik terlebih dahulu sebelum menempelkan kartu RFID.", "warning");
            const searchInput = document.getElementById("scanSearchStudentInput");
            if (searchInput) searchInput.focus();
            return;
        }

        if (!val) {
            playErrorSound();
            showNotification("Tempelkan kartu RFID pada reader USB untuk membaca UID kartu.", "warning");
            focusActiveInput();
            return;
        }

        const payload = {
            peserta_didik_id: currentIdentifiedStudent.peserta_didik_id || "",
            code: currentIdentifiedStudent.nisn || "",
            rfid_uid: val,
        };

        window.kirimScanPayload(payload);
    };

    window.kirimScanPayload = async function (payload) {
        if (isProcessing) return;
        isProcessing = true;

        const csrfToken =
            document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") ||
            window.CSRF_TOKEN ||
            "";

        const btnSubmit = document.getElementById("btnProsesScan");
        const origBtnText = btnSubmit ? btnSubmit.innerHTML : "";
        if (btnSubmit) {
            btnSubmit.disabled = true;
            btnSubmit.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Memproses...';
        }

        try {
            const res = await fetch("/dashboard/kartu-pelajar/request/scan-selesai", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": csrfToken,
                    Accept: "application/json",
                },
                body: JSON.stringify(payload),
            });
            const data = await res.json();

            if (btnSubmit) {
                btnSubmit.disabled = false;
                btnSubmit.innerHTML = origBtnText;
            }

            if (res.ok && data.success) {
                playBeepSound();

                // Tampilkan card hasil dengan animasi flash hijau
                const resultBox = document.getElementById("scanResultBox");
                const resultNama = document.getElementById("scanResultNama");
                const resultDetail = document.getElementById("scanResultDetail");
                const resultTime = document.getElementById("scanResultTime");

                if (resultBox && resultNama && resultDetail) {
                    resultBox.style.display = "block";
                    resultNama.textContent = (data.data.nama || "Siswa") + " (" + (data.data.nama_rombel || "-") + ")";
                    const rfidLabel = data.data.rfid_uid
                        ? `<span class="badge badge-success" style="font-size: 0.72rem; padding: 2px 7px;"><i class="fas fa-check-circle me-1"></i> RFID: ${data.data.rfid_uid} (Aktif di Presensi)</span>`
                        : `<span class="badge badge-outline" style="font-size: 0.72rem; padding: 2px 7px;">Tanpa RFID</span>`;
                    resultDetail.innerHTML = `NISN: <strong>${data.data.nisn}</strong> &bull; Status: <strong>Selesai Dicetak</strong> &bull; ${rfidLabel}`;
                    if (resultTime) resultTime.textContent = data.data.selesai_at || "";
                }

                // Tambahkan ke riwayat sesi ini
                addSessionLog(data.data);

                // Toast notifikasi ringkas non-blocking
                showNotification(data.message, "success");

                // Bersihkan siswa yang baru saja selesai agar langsung siap memilih siswa berikutnya
                window.resetIdentifiedStudent();

                // Trigger live refresh tabel antrean di latar belakang dengan debounce
                triggerLiveRefresh();
            } else {
                playErrorSound();
                showNotification(data.message || "Gagal memproses kartu RFID.", "danger");
                const inputScan = document.getElementById("inputScanCode");
                if (inputScan) {
                    inputScan.value = "";
                    inputScan.focus();
                }
            }
        } catch (err) {
            playErrorSound();
            if (btnSubmit) {
                btnSubmit.disabled = false;
                btnSubmit.innerHTML = origBtnText;
            }
            showNotification("Gagal menghubungi server: " + (err.message || err), "danger");
            focusActiveInput();
        } finally {
            setTimeout(() => {
                isProcessing = false;
            }, 250);
        }
    };

    function addSessionLog(d) {
        sessionSuccessList.unshift(d);
        if (sessionSuccessList.length > 15) sessionSuccessList.pop();

        const wrap = document.getElementById("scanSessionLogWrap");
        const list = document.getElementById("scanSessionLogList");
        const count = document.getElementById("scanSuccessCount");

        if (wrap) wrap.style.display = "block";
        if (count) count.textContent = sessionSuccessList.length;

        if (list) {
            list.innerHTML = sessionSuccessList
                .map((item) => {
                    const rfidTag = item.rfid_uid
                        ? `<span class="badge badge-success" style="font-size: 0.68rem; padding: 1px 6px;"><i class="fas fa-check me-1"></i>RFID: ${item.rfid_uid}</span>`
                        : `<span class="badge badge-outline" style="font-size: 0.68rem; padding: 1px 6px;">Non-RFID</span>`;
                    return `
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 6px 10px; background: var(--bg-hover); border-radius: 6px; font-size: 0.76rem; border: 1px solid var(--border-color);">
                            <div>
                                <strong>${item.nama}</strong> (${item.nama_rombel}) &bull; NISN: ${item.nisn}
                            </div>
                            <div style="display: flex; align-items: center; gap: 6px;">
                                ${rfidTag}
                                <span style="font-size: 0.68rem; color: var(--text-muted);">${item.selesai_at || ""}</span>
                            </div>
                        </div>
                    `;
                })
                .join("");
        }
    }

    window.clearSessionScanLog = function () {
        sessionSuccessList = [];
        const wrap = document.getElementById("scanSessionLogWrap");
        const list = document.getElementById("scanSessionLogList");
        const count = document.getElementById("scanSuccessCount");
        if (wrap) wrap.style.display = "none";
        if (list) list.innerHTML = "";
        if (count) count.textContent = "0";
    };

    function triggerLiveRefresh() {
        clearTimeout(liveRefreshDebounceTimer);
        liveRefreshDebounceTimer = setTimeout(() => {
            if (typeof window.refreshLiveTable === "function") {
                window.refreshLiveTable(window.location.href);
            }
        }, 1200);
    }

    // Auto submit jika reader RFID USB mengetik cepat (burst input dari hardware)
    document.addEventListener("DOMContentLoaded", function () {
        const inputScan = document.getElementById("inputScanCode");
        const searchInput = document.getElementById("scanSearchStudentInput");
        const modal = document.getElementById("modalScanRfidSelesai");

        if (inputScan) {
            inputScan.addEventListener("input", function () {
                const val = this.value.trim();
                // Jika input berupa burst dari reader RFID (biasanya >= 6 karakter angka/hex)
                if (val.length >= 6) {
                    clearTimeout(rfidBurstTimer);
                    rfidBurstTimer = setTimeout(() => {
                        if (inputScan.value.trim().length >= 6 && !isProcessing) {
                            window.submitScanSelesai();
                        }
                    }, 120);
                }
            });
        }

        // Klik di area modal otomatis memfokuskan kembali input yang aktif
        if (modal) {
            modal.addEventListener("click", function (e) {
                if (
                    e.target.tagName !== "INPUT" &&
                    e.target.tagName !== "BUTTON" &&
                    e.target.tagName !== "A" &&
                    !e.target.closest("button") &&
                    !e.target.closest("input") &&
                    !e.target.closest(".kp-ac-list")
                ) {
                    focusActiveInput();
                }
            });
        }

        // Live Autocomplete Pencarian Siswa Manual di Modal
        if (searchInput) {
            searchInput.addEventListener("input", function () {
                const q = this.value.trim();
                const list = document.getElementById("scanStudentResultsList");
                clearTimeout(studentSearchTimer);

                if (q.length < 2) {
                    if (list) list.style.display = "none";
                    return;
                }

                studentSearchTimer = setTimeout(async () => {
                    try {
                        const res = await fetch(`/dashboard/kartu-pelajar/cari-siswa?q=${encodeURIComponent(q)}`, {
                            headers: { "X-Requested-With": "XMLHttpRequest" },
                        });
                        const items = await res.json();
                        if (!list) return;

                        if (items && items.length > 0) {
                            list.innerHTML = items
                                .map((s) => `
                                    <div class="kp-ac-item" onclick='window.selectStudentFromSearch(${JSON.stringify(s)})'
                                        style="padding: 9px 12px; cursor: pointer; border-bottom: 1px solid var(--border-color); font-size: 0.82rem; display: flex; justify-content: space-between; align-items: center;">
                                        <div>
                                            <div style="font-weight: 700; color: var(--text-color);">${s.nama}</div>
                                            <div style="font-size: 0.72rem; color: var(--text-muted); font-family: monospace;">
                                                NISN: ${s.nisn} &bull; ${s.nama_rombel || "-"}
                                            </div>
                                        </div>
                                        <span class="badge badge-primary" style="font-size: 0.7rem; padding: 3px 8px;">Pilih Siswa</span>
                                    </div>
                                `)
                                .join("");
                            list.style.display = "block";
                        } else {
                            list.innerHTML = '<div style="padding: 10px; font-size: 0.76rem; color: var(--text-muted); text-align: center;">Tidak ditemukan siswa dengan kata kunci tersebut.</div>';
                            list.style.display = "block";
                        }
                    } catch (e) {}
                }, 300);
            });
        }
    });

    window.selectStudentFromSearch = function (student) {
        const list = document.getElementById("scanStudentResultsList");
        const searchInput = document.getElementById("scanSearchStudentInput");
        if (list) list.style.display = "none";
        if (searchInput) searchInput.value = "";

        currentIdentifiedStudent = {
            nisn: student.nisn,
            nama: student.nama,
            peserta_didik_id: student.peserta_didik_id,
            rombel: student.nama_rombel || "",
            rfid_uid: "",
        };
        displayIdentifiedStudent(currentIdentifiedStudent);
    };
})();

