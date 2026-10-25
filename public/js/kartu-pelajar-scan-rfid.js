/**
 * SAE - Kartu Pelajar QR Code Scanner & RFID Tap Reader
 * Memproses verifikasi kartu selesai cetak via kamera/scanner dan registrasi RFID ke sistem presensi.
 */

(function () {
    let videoStream = null;
    let scanAnimationId = null;
    let isProcessing = false;
    let currentIdentifiedStudent = null;
    let sessionSuccessList = [];

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

        const input = currentIdentifiedStudent
            ? document.getElementById("inputRfidForStudent")
            : document.getElementById("inputScanCode");
        if (input) {
            input.value = "";
            setTimeout(() => input.focus(), 150);
        }
    };

    window.closeModalScanRfidSelesai = function () {
        const m = document.getElementById("modalScanRfidSelesai");
        if (!m) return;
        m.style.display = "none";
        document.body.style.overflow = "";
        window.stopCameraScanner();
    };

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

            const input = currentIdentifiedStudent
                ? document.getElementById("inputRfidForStudent")
                : document.getElementById("inputScanCode");
            if (input) {
                setTimeout(() => input.focus(), 100);
            }
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
            Swal.fire({
                icon: "warning",
                title: "Kamera Tidak Didukung",
                text: "Browser Anda tidak mendukung akses kamera atau izin kamera diblokir. Gunakan input scanner RFID USB.",
            });
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
                Swal.fire({
                    icon: "error",
                    title: "Gagal Mengakses Kamera",
                    text: "Izin kamera ditolak atau perangkat kamera tidak ditemukan: " + (err.message || err.name),
                });
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
                playBeepSound();
                window.kirimScanCode(rawData);
                return;
            }
        }

        if (videoStream) {
            scanAnimationId = requestAnimationFrame(scanQrTick);
        }
    }

    function playBeepSound() {
        try {
            const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
            const osc = audioCtx.createOscillator();
            const gain = audioCtx.createGain();
            osc.connect(gain);
            gain.connect(audioCtx.destination);
            osc.type = "sine";
            osc.frequency.setValueAtTime(1046.5, audioCtx.currentTime); // C6 tone
            gain.gain.setValueAtTime(0.18, audioCtx.currentTime);
            osc.start();
            osc.stop(audioCtx.currentTime + 0.14);
        } catch (e) {}
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

        const rfidInput = document.getElementById("inputRfidForStudent");
        if (rfidInput) {
            rfidInput.value = "";
            setTimeout(() => rfidInput.focus(), 100);
        }
    }

    window.resetIdentifiedStudent = function () {
        currentIdentifiedStudent = null;
        const card = document.getElementById("scanIdentifiedCard");
        if (card) card.style.display = "none";
        const input = document.getElementById("inputScanCode");
        if (input) {
            input.value = "";
            input.focus();
        }
    };

    window.submitScanSelesai = function () {
        const input = document.getElementById("inputScanCode");
        if (!input || !input.value.trim()) return;
        window.kirimScanCode(input.value.trim());
    };

    window.submitBindStudentRfid = function () {
        const rfidInput = document.getElementById("inputRfidForStudent");
        const rfidVal = rfidInput ? rfidInput.value.trim() : "";

        if (!rfidVal) {
            Swal.fire({
                icon: "warning",
                title: "UID RFID Kosong",
                text: "Tempelkan kartu RFID ke scanner USB atau masukkan nomor UID RFID fisik terlebih dahulu.",
            });
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
        const payload = {
            code: codeStr,
            peserta_didik_id: currentIdentifiedStudent ? currentIdentifiedStudent.peserta_didik_id : null,
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

                // Tampilkan card hasil
                const resultBox = document.getElementById("scanResultBox");
                const resultNama = document.getElementById("scanResultNama");
                const resultDetail = document.getElementById("scanResultDetail");
                const resultTime = document.getElementById("scanResultTime");

                if (resultBox && resultNama && resultDetail) {
                    resultBox.style.display = "block";
                    resultNama.textContent = (data.data.nama || "Siswa") + " (" + (data.data.nama_rombel || "-") + ")";
                    const rfidLabel = data.data.rfid_uid ? `RFID UID: ${data.data.rfid_uid} (Aktif)` : "RFID: -";
                    resultDetail.textContent = `NISN: ${data.data.nisn} \u2022 Status: Sudah Selesai Dicetak \u2022 ${rfidLabel}`;
                    if (resultTime) resultTime.textContent = data.data.selesai_at || "";
                }

                // Tambahkan ke riwayat sesi ini
                addSessionLog(data.data);

                // Toast notifikasi sukses
                if (window.SAE && typeof window.SAE.toast === "function") {
                    window.SAE.toast(data.message, "success");
                } else {
                    Swal.fire({
                        icon: "success",
                        title: "Cetak Selesai & RFID Terdaftar!",
                        text: data.message,
                        timer: 2000,
                        showConfirmButton: false,
                    });
                }

                // Reset siswa teridentifikasi setelah selesai
                window.resetIdentifiedStudent();

                // Segarkan tabel antrean secara live jika ada
                if (typeof window.refreshLiveTable === "function") {
                    window.refreshLiveTable(window.location.href);
                }
            } else {
                Swal.fire({
                    icon: "error",
                    title: "Gagal Verifikasi",
                    text: data.message || "Kode tidak valid.",
                });
            }
        } catch (err) {
            if (btnSubmit) {
                btnSubmit.disabled = false;
                btnSubmit.innerHTML = origBtnText;
            }
            Swal.fire("Error", "Gagal menghubungi server: " + (err.message || err), "error");
        } finally {
            setTimeout(() => {
                isProcessing = false;
                if (videoStream && !scanAnimationId) {
                    scanAnimationId = requestAnimationFrame(scanQrTick);
                }
            }, 800);
        }
    };

    function addSessionLog(d) {
        sessionSuccessList.unshift(d);
        if (sessionSuccessList.length > 10) sessionSuccessList.pop();

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
                        : "";
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

    // Auto submit jika input tap RFID mencapai batas atau Enter ditekan
    document.addEventListener("DOMContentLoaded", function () {
        const rfidInput = document.getElementById("inputRfidForStudent");
        if (rfidInput) {
            rfidInput.addEventListener("keydown", function (e) {
                if (e.key === "Enter") {
                    e.preventDefault();
                    window.submitBindStudentRfid();
                }
            });
        }
    });
})();
