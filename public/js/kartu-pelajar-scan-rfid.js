/**
 * SAE - Kartu Pelajar QR Code Scanner & RFID Tap Reader
 * Memproses verifikasi kartu selesai cetak via kamera/scanner dan registrasi RFID ke sistem presensi.
 */

/**
 * SAE - Kartu Pelajar QR Code Scanner & RFID Tap Reader
 * Memproses verifikasi kartu selesai cetak via kamera/scanner dan registrasi RFID ke sistem presensi secara berkelanjutan (continuous flow).
 */

(function () {
    let videoStream = null;
    let scanAnimationId = null;
    let isProcessing = false;
    let currentIdentifiedStudent = null;
    let activeUnlinkedRfid = null;
    let sessionSuccessList = [];
    let lastScannedCode = "";
    let lastScannedTime = 0;
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

    // Buka modal scan selesai, mendukung pre-fill siswa jika diklik dari baris antrean
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
        window.stopCameraScanner();
        window.resetIdentifiedStudent();
        window.cancelUnlinkedRfid();
    };

    function focusActiveInput() {
        setTimeout(() => {
            if (currentIdentifiedStudent) {
                const rfidInput = document.getElementById("inputRfidForStudent");
                if (rfidInput) rfidInput.focus();
            } else {
                const input = document.getElementById("inputScanCode");
                if (input) input.focus();
            }
        }, 60);
    }

    window.switchScanMode = function (mode) {
        const tapWrap = document.getElementById("scanModeTapWrap");
        const camWrap = document.getElementById("scanModeCameraWrap");
        const btnTap = document.getElementById("tabBtnScanTap");
        const btnCam = document.getElementById("tabBtnScanCamera");

        if (mode === "camera") {
            if (tapWrap) tapWrap.style.display = "none";
            if (camWrap) camWrap.style.display = "block";
            if (btnTap) {
                btnTap.style.background = "transparent";
                btnTap.style.color = "var(--text-muted)";
            }
            if (btnCam) {
                btnCam.style.background = "#059669";
                btnCam.style.color = "#ffffff";
            }
            window.startCameraScanner();
        } else {
            // mode tap
            if (camWrap) camWrap.style.display = "none";
            if (tapWrap) tapWrap.style.display = "block";
            if (btnCam) {
                btnCam.style.background = "transparent";
                btnCam.style.color = "var(--text-muted)";
            }
            if (btnTap) {
                btnTap.style.background = "#059669";
                btnTap.style.color = "#ffffff";
            }
            window.stopCameraScanner();
            focusActiveInput();
        }
    };

    window.toggleCameraScanner = function () {
        if (videoStream) {
            window.stopCameraScanner();
        } else {
            window.startCameraScanner();
        }
    };

    window.startCameraScanner = function () {
        const video = document.getElementById("scanQrVideo");
        const placeholder = document.getElementById("scanCameraPlaceholder");
        const overlay = document.getElementById("scanQrOverlay");
        const btnToggle = document.getElementById("btnToggleCameraScan");

        if (!video || !navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            showNotification("Browser tidak mendukung kamera. Gunakan scanner RFID/Barcode USB.", "warning");
            return;
        }

        navigator.mediaDevices
            .getUserMedia({
                video: { facingMode: "environment", width: { ideal: 1280 }, height: { ideal: 720 } },
            })
            .then((stream) => {
                videoStream = stream;
                video.srcObject = stream;
                video.setAttribute("playsinline", true);
                video.style.display = "block";
                if (placeholder) placeholder.style.display = "none";
                if (overlay) overlay.style.display = "flex";
                if (btnToggle) {
                    btnToggle.innerHTML = '<i class="fas fa-video-slash me-1"></i> Matikan Kamera';
                    btnToggle.style.color = "#ef4444";
                    btnToggle.style.borderColor = "#ef4444";
                }
                video.play();
                scanAnimationId = requestAnimationFrame(scanQrTick);
            })
            .catch((err) => {
                console.error("Camera access error:", err);
                showNotification("Gagal mengakses kamera: " + (err.message || err.name), "danger");
            });
    };

    window.stopCameraScanner = function () {
        if (scanAnimationId) {
            cancelAnimationFrame(scanAnimationId);
            scanAnimationId = null;
        }
        if (videoStream) {
            videoStream.getTracks().forEach((track) => track.stop());
            videoStream = null;
        }
        const video = document.getElementById("scanQrVideo");
        const placeholder = document.getElementById("scanCameraPlaceholder");
        const overlay = document.getElementById("scanQrOverlay");
        const btnToggle = document.getElementById("btnToggleCameraScan");

        if (video) video.style.display = "none";
        if (placeholder) placeholder.style.display = "block";
        if (overlay) overlay.style.display = "none";
        if (btnToggle) {
            btnToggle.innerHTML = '<i class="fas fa-video me-1"></i> Aktifkan Kamera Scanner';
            btnToggle.style.color = "#38bdf8";
            btnToggle.style.borderColor = "#38bdf8";
        }
    };

    function scanQrTick() {
        const video = document.getElementById("scanQrVideo");
        const canvas = document.getElementById("scanQrCanvas");

        if (!video || !canvas || video.readyState !== video.HAVE_ENOUGH_DATA) {
            if (videoStream) {
                scanAnimationId = requestAnimationFrame(scanQrTick);
            }
            return;
        }

        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        const ctx = canvas.getContext("2d");
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

        if (typeof jsQR !== "undefined") {
            const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
            const code = jsQR(imageData.data, imageData.width, imageData.height, {
                inversionAttempts: "dontInvert",
            });

            if (code && code.data && !isProcessing) {
                const rawData = code.data.trim();
                const now = Date.now();

                // Hindari memproses kode yang sama berulang kali dalam jeda 2.2 detik
                if (rawData === lastScannedCode && now - lastScannedTime < 2200) {
                    if (videoStream) {
                        scanAnimationId = requestAnimationFrame(scanQrTick);
                    }
                    return;
                }

                lastScannedCode = rawData;
                lastScannedTime = now;
                window.kirimScanCode(rawData);
                return;
            }
        }

        if (videoStream) {
            scanAnimationId = requestAnimationFrame(scanQrTick);
        }
    }

    function displayIdentifiedStudent(s) {
        const card = document.getElementById("scanIdentifiedCard");
        const namaEl = document.getElementById("scanStudentNama");
        const nisnEl = document.getElementById("scanStudentNisn");
        const rombelEl = document.getElementById("scanStudentRombel");
        const photoEl = document.getElementById("scanStudentPhoto");
        const rfidStatus = document.getElementById("scanStudentCurrentRfid");

        if (!card) return;
        card.style.display = "block";
        if (namaEl) namaEl.textContent = s.nama || "Peserta Didik";
        if (nisnEl) nisnEl.textContent = s.nisn || "-";
        if (rombelEl) rombelEl.textContent = s.nama_rombel || s.rombel || "";
        if (photoEl && s.foto_url) photoEl.src = s.foto_url;
        if (rfidStatus) rfidStatus.textContent = s.rfid_uid || "Belum Ada (Menunggu Tap)";

        focusActiveInput();
    }

    window.resetIdentifiedStudent = function () {
        currentIdentifiedStudent = null;
        const card = document.getElementById("scanIdentifiedCard");
        if (card) card.style.display = "none";
        const input = document.getElementById("inputScanCode");
        if (input) {
            input.value = "";
            focusActiveInput();
        }
    };

    window.cancelUnlinkedRfid = function () {
        activeUnlinkedRfid = null;
        const banner = document.getElementById("scanUnlinkedRfidBanner");
        if (banner) banner.style.display = "none";
        focusActiveInput();
    };

    window.submitScanSelesai = function () {
        const input = document.getElementById("inputScanCode");
        if (!input || !input.value.trim()) return;
        const val = input.value.trim();
        window.kirimScanCode(val);
    };

    window.submitBindStudentRfid = function () {
        const rfidInput = document.getElementById("inputRfidForStudent");
        const rfidVal = rfidInput ? rfidInput.value.trim() : "";

        if (!rfidVal) {
            showNotification("Tempelkan kartu RFID ke scanner USB terlebih dahulu.", "warning");
            focusActiveInput();
            return;
        }

        const payload = {
            code: currentIdentifiedStudent?.nisn || "",
            peserta_didik_id: currentIdentifiedStudent?.peserta_didik_id || "",
            rfid_uid: rfidVal,
        };

        window.kirimScanPayload(payload);
    };

    window.submitCompleteWithoutRfid = function () {
        const payload = {
            code: currentIdentifiedStudent?.nisn || "",
            peserta_didik_id: currentIdentifiedStudent?.peserta_didik_id || "",
        };
        window.kirimScanPayload(payload);
    };

    window.kirimScanCode = function (codeStr) {
        let payload = {
            code: codeStr,
            peserta_didik_id: currentIdentifiedStudent ? currentIdentifiedStudent.peserta_didik_id : null,
            rfid_uid: null,
        };

        // Jika sebelumnya ada kartu RFID belum tertaut yang di-tap terlebih dahulu
        if (activeUnlinkedRfid) {
            payload.rfid_uid = activeUnlinkedRfid;
        }

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
                        ? `<span class="badge badge-success" style="font-size: 0.72rem; padding: 2px 7px;"><i class="fas fa-check-circle me-1"></i> RFID: ${data.data.rfid_uid} (Aktif)</span>`
                        : `<span class="badge badge-outline" style="font-size: 0.72rem; padding: 2px 7px;">Tanpa RFID</span>`;
                    resultDetail.innerHTML = `NISN: <strong>${data.data.nisn}</strong> &bull; Status: <strong>Selesai Dicetak</strong> &bull; ${rfidLabel}`;
                    if (resultTime) resultTime.textContent = data.data.selesai_at || "";
                }

                // Tambahkan ke riwayat sesi ini
                addSessionLog(data.data);

                // Toast notifikasi ringkas non-blocking
                showNotification(data.message, "success");

                // Bersihkan state & siap untuk scan siswa / kartu berikutnya seketika
                window.resetIdentifiedStudent();
                window.cancelUnlinkedRfid();

                // Kosongkan input dan refocus seketika
                const inputScan = document.getElementById("inputScanCode");
                if (inputScan) inputScan.value = "";
                focusActiveInput();

                // Trigger live refresh tabel antrean di latar belakang dengan debounce
                triggerLiveRefresh();
            } else {
                playErrorSound();

                // Jika terdeteksi tap kartu RFID baru yang belum punya pemilik
                if (data.is_unlinked_rfid) {
                    activeUnlinkedRfid = data.unlinked_rfid || payload.code;
                    const banner = document.getElementById("scanUnlinkedRfidBanner");
                    const display = document.getElementById("unlinkedRfidDisplay");
                    if (banner && display) {
                        banner.style.display = "block";
                        display.textContent = activeUnlinkedRfid;
                    }
                    showNotification("Kartu RFID [" + activeUnlinkedRfid + "] terdeteksi! Silakan scan QR / cari siswa untuk menautkan.", "warning");

                    const inputScan = document.getElementById("inputScanCode");
                    if (inputScan) inputScan.value = "";
                    focusActiveInput();
                } else {
                    showNotification(data.message || "Kode tidak valid.", "danger");
                    const inputScan = document.getElementById("inputScanCode");
                    if (inputScan) {
                        inputScan.value = "";
                        focusActiveInput();
                    }
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
            // Jeda pemrosesan sangat singkat (250ms) agar operator bisa tap kartu berturut-turut tanpa jeda
            setTimeout(() => {
                isProcessing = false;
                if (videoStream && !scanAnimationId) {
                    scanAnimationId = requestAnimationFrame(scanQrTick);
                }
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
                        ? `<span class="badge badge-success" style="font-size: 0.68rem; padding: 1px 6px;">RFID: ${item.rfid_uid}</span>`
                        : `<span class="badge badge-outline" style="font-size: 0.68rem; padding: 1px 6px;">Non-RFID</span>`;
                    return `
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 4px 8px; background: var(--bg-hover); border-radius: 6px; font-size: 0.74rem;">
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

    // Auto submit jika scanner USB mengetik cepat (burst input dari hardware RFID / Barcode reader)
    document.addEventListener("DOMContentLoaded", function () {
        const inputScan = document.getElementById("inputScanCode");
        const rfidInputForStudent = document.getElementById("inputRfidForStudent");
        const searchInput = document.getElementById("scanSearchStudentInput");
        const modal = document.getElementById("modalScanRfidSelesai");

        if (inputScan) {
            inputScan.addEventListener("input", function () {
                const val = this.value.trim();
                // Jika input berupa burst dari scanner RFID (biasanya >= 8 digit angka/hex)
                if (val.length >= 8) {
                    clearTimeout(rfidBurstTimer);
                    rfidBurstTimer = setTimeout(() => {
                        if (inputScan.value.trim().length >= 8 && !isProcessing) {
                            window.submitScanSelesai();
                        }
                    }, 120);
                }
            });
        }

        if (rfidInputForStudent) {
            rfidInputForStudent.addEventListener("keydown", function (e) {
                if (e.key === "Enter") {
                    e.preventDefault();
                    window.submitBindStudentRfid();
                }
            });

            rfidInputForStudent.addEventListener("input", function () {
                const val = this.value.trim();
                if (val.length >= 8) {
                    clearTimeout(rfidBurstTimer);
                    rfidBurstTimer = setTimeout(() => {
                        if (rfidInputForStudent.value.trim().length >= 8 && !isProcessing) {
                            window.submitBindStudentRfid();
                        }
                    }, 120);
                }
            });
        }

        // Klik di mana saja dalam modal otomatis memfokuskan kembali input scanner
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
                                        style="padding: 8px 12px; cursor: pointer; border-bottom: 1px solid var(--border-color); font-size: 0.8rem; display: flex; justify-content: space-between; align-items: center;">
                                        <div>
                                            <div style="font-weight: 700; color: var(--text-color);">${s.nama}</div>
                                            <div style="font-size: 0.72rem; color: var(--text-muted); font-family: monospace;">
                                                NISN: ${s.nisn} &bull; ${s.nama_rombel || "-"}
                                            </div>
                                        </div>
                                        <span class="badge badge-outline" style="font-size: 0.68rem; padding: 2px 6px;">Pilih</span>
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

        // Jika sebelumnya ada kartu RFID belum tertaut yang di-tap terlebih dahulu
        if (activeUnlinkedRfid) {
            const payload = {
                code: student.nisn,
                peserta_didik_id: student.peserta_didik_id,
                rfid_uid: activeUnlinkedRfid,
            };
            window.kirimScanPayload(payload);
        } else {
            currentIdentifiedStudent = {
                nisn: student.nisn,
                nama: student.nama,
                peserta_didik_id: student.peserta_didik_id,
                rombel: student.nama_rombel || "",
                rfid_uid: "",
            };
            displayIdentifiedStudent(currentIdentifiedStudent);
        }
    };
})();

