/**
 * SAE - Sistem Presensi Peserta Didik
 * Logic for Terminal Kiosk & Scanner Station
 */

document.addEventListener('DOMContentLoaded', function () {
    // 1. Digital Clock Ticker
    const clockEl = document.getElementById('kioskLiveClock');
    function updateClock() {
        if (!clockEl) return;
        const now = new Date();
        const hrs = String(now.getHours()).padStart(2, '0');
        const mins = String(now.getMinutes()).padStart(2, '0');
        const secs = String(now.getSeconds()).padStart(2, '0');
        clockEl.textContent = `${hrs}:${mins}:${secs}`;
    }
    setInterval(updateClock, 1000);
    updateClock();

    // 2. Audio Synthesizer (Web Audio API)
    let audioCtx = null;
    function getAudioContext() {
        if (!audioCtx) {
            audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        }
        if (audioCtx.state === 'suspended') {
            audioCtx.resume();
        }
        return audioCtx;
    }

    // Unlock Audio & Speech pada sentuhan pertama di perangkat mobile
    function unlockAudioGesture() {
        getAudioContext();
        if ('speechSynthesis' in window) {
            window.speechSynthesis.getVoices();
        }
        document.removeEventListener('touchstart', unlockAudioGesture);
        document.removeEventListener('click', unlockAudioGesture);
    }
    document.addEventListener('touchstart', unlockAudioGesture, { passive: true });
    document.addEventListener('click', unlockAudioGesture, { passive: true });

    function playSound(type) {
        try {
            const ctx = getAudioContext();
            const now = ctx.currentTime;

            if (type === 'success') {
                // Dual chord Ting!
                const osc1 = ctx.createOscillator();
                const osc2 = ctx.createOscillator();
                const gain = ctx.createGain();

                osc1.type = 'sine';
                osc2.type = 'sine';
                osc1.frequency.setValueAtTime(587.33, now); // D5
                osc2.frequency.setValueAtTime(880.00, now); // A5

                gain.gain.setValueAtTime(0.3, now);
                gain.gain.exponentialRampToValueAtTime(0.001, now + 0.5);

                osc1.connect(gain);
                osc2.connect(gain);
                gain.connect(ctx.destination);

                osc1.start(now);
                osc2.start(now);
                osc1.stop(now + 0.5);
                osc2.stop(now + 0.5);
            } else if (type === 'warning') {
                // Minor warning tone
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();

                osc.type = 'triangle';
                osc.frequency.setValueAtTime(440, now);
                osc.frequency.setValueAtTime(392, now + 0.15);

                gain.gain.setValueAtTime(0.3, now);
                gain.gain.exponentialRampToValueAtTime(0.001, now + 0.4);

                osc.connect(gain);
                gain.connect(ctx.destination);

                osc.start(now);
                osc.stop(now + 0.4);
            } else {
                // Error Buzz
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();

                osc.type = 'sawtooth';
                osc.frequency.setValueAtTime(180, now);

                gain.gain.setValueAtTime(0.3, now);
                gain.gain.exponentialRampToValueAtTime(0.001, now + 0.35);

                osc.connect(gain);
                gain.connect(ctx.destination);

                osc.start(now);
                osc.stop(now + 0.35);
            }
        } catch (e) {
            // Web Audio not allowed until user interaction
        }
    }

    // 3. Web Speech Synthesis (Text-To-Speech)
    let isSpeechEnabled = true;
    const btnToggleSpeech = document.getElementById('btnToggleSpeech');
    if (btnToggleSpeech) {
        btnToggleSpeech.addEventListener('click', function () {
            isSpeechEnabled = !isSpeechEnabled;
            this.innerHTML = isSpeechEnabled
                ? '<i class="fas fa-volume-high"></i>'
                : '<i class="fas fa-volume-xmark"></i>';
            this.setAttribute('title', isSpeechEnabled ? 'Suara Aktif (Klik untuk membisukan)' : 'Suara Nonaktif (Klik untuk mengaktifkan)');
            this.classList.toggle('btn-outline', !isSpeechEnabled);
            this.classList.toggle('btn-primary', isSpeechEnabled);
        });
    }

    function speakGreeting(text) {
        if (!isSpeechEnabled || !('speechSynthesis' in window) || !text) return;
        window.speechSynthesis.cancel(); // batalkan ucapan sebelumnya jika ada

        const utterance = new SpeechSynthesisUtterance(text);
        utterance.lang = 'id-ID';
        utterance.rate = 1.05;
        utterance.pitch = 1.0;

        // Cari suara Bahasa Indonesia jika ada
        const voices = window.speechSynthesis.getVoices();
        const idVoice = voices.find(v => v.lang.includes('id') || v.lang.includes('ID'));
        if (idVoice) utterance.voice = idVoice;

        window.speechSynthesis.speak(utterance);
    }

    // 4. Inisialisasi Kamera Live Snapshot & Scanner
    const videoEl = document.getElementById('cameraVideo');
    const canvasEl = document.getElementById('snapshotCanvas');
    const btnSwitchCamera = document.getElementById('btnSwitchCamera');
    const cameraNoticeOverlay = document.getElementById('cameraNoticeOverlay');
    const cameraNoticeTitle = document.getElementById('cameraNoticeTitle');
    const cameraNoticeDesc = document.getElementById('cameraNoticeDesc');
    const btnRetryCamera = document.getElementById('btnRetryCamera');
    const cameraStatusBadge = document.getElementById('cameraStatusBadge');

    let videoStream = null;
    const isTouchOrMobile = ('ontouchstart' in window) || (navigator.maxTouchPoints > 0) || window.innerWidth <= 768;

    // Preferensi kamera: Default 'environment' (kamera belakang) di HP/Tablet agar mudah scan kartu, atau 'user' di PC/Webcam
    let currentFacingMode = localStorage.getItem('sae_scanner_facing_mode') || (isTouchOrMobile ? 'environment' : 'user');

    function showCameraNotice(title, message, isError = true) {
        if (cameraNoticeOverlay) {
            cameraNoticeOverlay.style.display = 'flex';
            if (cameraNoticeTitle) cameraNoticeTitle.textContent = title;
            if (cameraNoticeDesc) cameraNoticeDesc.innerHTML = message;
        }
        if (cameraStatusBadge) {
            cameraStatusBadge.className = `badge-status-icon ${isError ? 'status-err' : 'status-warn'}`;
            cameraStatusBadge.innerHTML = `<i class="fas fa-${isError ? 'video-slash' : 'triangle-exclamation'}"></i>`;
            cameraStatusBadge.setAttribute('title', title);
        }
    }

    function hideCameraNotice() {
        if (cameraNoticeOverlay) cameraNoticeOverlay.style.display = 'none';
        if (cameraStatusBadge) {
            cameraStatusBadge.className = 'badge-status-icon status-ok';
            cameraStatusBadge.innerHTML = '<i class="fas fa-video"></i>';
            cameraStatusBadge.setAttribute('title', `Kamera Siap (${currentFacingMode === 'environment' ? 'Belakang' : 'Depan'})`);
        }
    }

    async function checkAvailableCameras() {
        if (!navigator.mediaDevices || !navigator.mediaDevices.enumerateDevices) return;
        try {
            const devices = await navigator.mediaDevices.enumerateDevices();
            const videoDevices = devices.filter(d => d.kind === 'videoinput');
            if (btnSwitchCamera) {
                if (videoDevices.length > 1 || isTouchOrMobile) {
                    btnSwitchCamera.style.display = 'inline-flex';
                } else {
                    btnSwitchCamera.style.display = 'none';
                }
            }
        } catch (e) {
            if (btnSwitchCamera && isTouchOrMobile) {
                btnSwitchCamera.style.display = 'inline-flex';
            }
        }
    }

    async function initCamera() {
        // Cek koneksi aman (HTTPS): Browser HP/Tablet strictly memblokir kamera di HTTP non-localhost
        if (!window.isSecureContext && location.hostname !== 'localhost' && location.hostname !== '127.0.0.1') {
            showCameraNotice(
                'Akses HTTPS Diperlukan',
                'Browser HP/Tablet membatasi izin kamera dan GPS pada koneksi HTTP biasa. Harap gunakan protokol HTTPS atau aktifkan izin di <code>chrome://flags/#unsafely-treat-insecure-origin-as-secure</code>.'
            );
            return;
        }

        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            showCameraNotice(
                'Kamera Tidak Didukung',
                'Browser atau webview ini tidak menyediakan API kamera (MediaDevices). Gunakan browser Chrome atau Safari versi terbaru.'
            );
            return;
        }

        // Hentikan stream kamera lama sebelum membuka yang baru
        if (videoStream) {
            videoStream.getTracks().forEach(track => track.stop());
            videoStream = null;
        }

        // Terapkan mirror visual hanya jika kamera depan (selfie/kiosk)
        if (videoEl) {
            if (currentFacingMode === 'user') {
                videoEl.classList.add('mirrored');
            } else {
                videoEl.classList.remove('mirrored');
            }
        }

        let stream = null;
        try {
            // Percobaan 1: Gunakan facingMode pilihan
            stream = await navigator.mediaDevices.getUserMedia({
                video: {
                    facingMode: { ideal: currentFacingMode },
                    width: { ideal: 1280 },
                    height: { ideal: 720 }
                },
                audio: false
            });
        } catch (err1) {
            console.warn(`Gagal getUserMedia dengan facingMode ${currentFacingMode}, mencoba fallback...`, err1);
            try {
                // Percobaan 2: Fallback ke mode kamera kebalikannya
                const fallbackMode = (currentFacingMode === 'user' ? 'environment' : 'user');
                stream = await navigator.mediaDevices.getUserMedia({
                    video: {
                        facingMode: { ideal: fallbackMode },
                        width: { ideal: 640 },
                        height: { ideal: 480 }
                    },
                    audio: false
                });
                currentFacingMode = fallbackMode;
                localStorage.setItem('sae_scanner_facing_mode', currentFacingMode);
                if (videoEl) {
                    if (currentFacingMode === 'user') videoEl.classList.add('mirrored');
                    else videoEl.classList.remove('mirrored');
                }
            } catch (err2) {
                try {
                    // Percobaan 3: Fallback tanpa constraint sama sekali
                    stream = await navigator.mediaDevices.getUserMedia({ video: true, audio: false });
                } catch (errFinal) {
                    console.error('Kamera gagal dibuka:', errFinal);
                    let msg = 'Tidak dapat mengakses perangkat kamera.';
                    if (errFinal.name === 'NotAllowedError' || errFinal.name === 'PermissionDeniedError') {
                        msg = 'Izin kamera ditolak oleh pengguna/browser. Buka setelan izin browser Anda dan aktifkan akses kamera untuk SAE.';
                    } else if (errFinal.name === 'NotFoundError' || errFinal.name === 'DevicesNotFoundError') {
                        msg = 'Tidak ada perangkat kamera yang terdeteksi.';
                    } else if (errFinal.name === 'NotReadableError' || errFinal.name === 'TrackStartError') {
                        msg = 'Kamera sedang digunakan oleh aplikasi lain.';
                    }
                    showCameraNotice('Izin Kamera Diperlukan', msg);
                    return;
                }
            }
        }

        if (stream && videoEl) {
            videoStream = stream;
            videoEl.srcObject = stream;
            videoEl.setAttribute('playsinline', 'true');
            videoEl.setAttribute('webkit-playsinline', 'true');

            try {
                await videoEl.play();
            } catch (playErr) {
                console.warn('Autoplay tertahan, menunggu interaksi:', playErr);
            }

            hideCameraNotice();
            checkAvailableCameras();
        }
    }

    // Handler Tombol Switch Kamera
    if (btnSwitchCamera) {
        btnSwitchCamera.addEventListener('click', function () {
            currentFacingMode = (currentFacingMode === 'user' ? 'environment' : 'user');
            localStorage.setItem('sae_scanner_facing_mode', currentFacingMode);
            this.setAttribute('title', `Beralih ke Kamera ${currentFacingMode === 'environment' ? 'Belakang' : 'Depan'}`);
            initCamera();
        });
    }

    // Handler Tombol Coba Lagi Kamera
    if (btnRetryCamera) {
        btnRetryCamera.addEventListener('click', function () {
            initCamera();
        });
    }

    initCamera();

    function captureSnapshot() {
        if (!videoEl || !canvasEl || !videoStream) return null;
        try {
            const width = videoEl.videoWidth || 640;
            const height = videoEl.videoHeight || 480;
            canvasEl.width = width;
            canvasEl.height = height;

            const ctx = canvasEl.getContext('2d');
            ctx.save();
            // Un-mirror snapshot hanya jika kamera depan
            if (currentFacingMode === 'user') {
                ctx.translate(width, 0);
                ctx.scale(-1, 1);
            }
            ctx.drawImage(videoEl, 0, 0, width, height);
            ctx.restore();

            return canvasEl.toDataURL('image/jpeg', 0.82);
        } catch (e) {
            return null;
        }
    }

    // 4.5. Pelacak Geolokasi & Radius Jarak Sekolah (GPS Kiosk)
    const geoConfigEl = document.getElementById('kioskGeoConfig');
    const requireLocation = geoConfigEl ? geoConfigEl.dataset.requireLocation === '1' : false;
    const schoolLat = geoConfigEl && geoConfigEl.dataset.schoolLat !== '' ? parseFloat(geoConfigEl.dataset.schoolLat) : null;
    const schoolLon = geoConfigEl && geoConfigEl.dataset.schoolLon !== '' ? parseFloat(geoConfigEl.dataset.schoolLon) : null;
    const schoolRadius = geoConfigEl ? (parseInt(geoConfigEl.dataset.schoolRadius, 10) || 100) : 100;

    let currentLat = null;
    let currentLon = null;
    let currentAccuracy = null;
    let currentDistance = null;

    function calculateClientDistance(lat1, lon1, lat2, lon2) {
        if (lat1 === null || lon1 === null || lat2 === null || lon2 === null) return null;
        const R = 6371000;
        const dLat = (lat2 - lat1) * Math.PI / 180;
        const dLon = (lon2 - lon1) * Math.PI / 180;
        const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
            Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
            Math.sin(dLon / 2) * Math.sin(dLon / 2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        return Math.round(R * c);
    }

    function updateGpsUI(lat, lon, accuracy) {
        currentLat = lat;
        currentLon = lon;
        currentAccuracy = accuracy;

        const gpsBadge = document.getElementById('gpsStatusBadge');
        const gpsDistanceText = document.getElementById('kioskGpsDistanceText');

        if (schoolLat !== null && schoolLon !== null) {
            currentDistance = calculateClientDistance(schoolLat, schoolLon, currentLat, currentLon);
            const inRadius = currentDistance <= schoolRadius;

            if (gpsBadge) {
                if (inRadius) {
                    gpsBadge.className = 'badge-status-icon status-ok';
                    gpsBadge.innerHTML = '<i class="fas fa-location-dot"></i>';
                    gpsBadge.setAttribute('title', `GPS: Radius OK (${currentDistance}m)`);
                } else {
                    gpsBadge.className = 'badge-status-icon status-err';
                    gpsBadge.innerHTML = '<i class="fas fa-triangle-exclamation"></i>';
                    gpsBadge.setAttribute('title', `GPS: Luar Radius (${currentDistance}m / maks ${schoolRadius}m)`);
                }
            }

            if (gpsDistanceText) {
                gpsDistanceText.innerHTML = inRadius
                    ? `<span style="color: var(--success);"><i class="fas fa-circle-check me-1"></i> ${currentDistance} m (Dalam Radius)</span>`
                    : `<span style="color: var(--danger);"><i class="fas fa-triangle-exclamation me-1"></i> ${currentDistance} m (Di Luar Radius)</span>`;
            }
        } else {
            if (gpsBadge) {
                gpsBadge.className = 'badge-status-icon status-ok';
                gpsBadge.innerHTML = '<i class="fas fa-location-dot"></i>';
                gpsBadge.setAttribute('title', `GPS: Aktif (±${Math.round(accuracy)}m)`);
            }
            if (gpsDistanceText) {
                gpsDistanceText.textContent = `${lat.toFixed(5)}, ${lon.toFixed(5)}`;
            }
        }
    }

    function handleGpsError(err) {
        const gpsBadge = document.getElementById('gpsStatusBadge');
        const gpsDistanceText = document.getElementById('kioskGpsDistanceText');

        console.warn('Geolocation error:', err.message);

        if (gpsBadge) {
            if (err.code === 1) {
                gpsBadge.className = 'badge-status-icon status-err';
                gpsBadge.innerHTML = '<i class="fas fa-location-slash"></i>';
                gpsBadge.setAttribute('title', 'GPS: Izin Lokasi Ditolak');
            } else {
                gpsBadge.className = 'badge-status-icon status-warn';
                gpsBadge.innerHTML = '<i class="fas fa-location-crosshairs"></i>';
                gpsBadge.setAttribute('title', 'GPS: Mencari Sinyal...');
            }
        }

        if (gpsDistanceText) {
            if (err.code === 1) {
                gpsDistanceText.innerHTML = '<span style="color: var(--danger);"><i class="fas fa-ban me-1"></i> Izin lokasi ditolak</span>';
            } else {
                gpsDistanceText.innerHTML = '<span style="color: var(--warning);"><i class="fas fa-satellite-dish me-1"></i> Mencari sinyal GPS...</span>';
            }
        }
    }

    function initGeolocation() {
        const gpsBadge = document.getElementById('gpsStatusBadge');
        const gpsDistanceText = document.getElementById('kioskGpsDistanceText');

        if (!navigator.geolocation) {
            if (gpsBadge) {
                gpsBadge.className = 'badge-status-icon status-err';
                gpsBadge.innerHTML = '<i class="fas fa-location-slash"></i>';
                gpsBadge.setAttribute('title', 'GPS: Tidak Didukung Browser');
            }
            if (gpsDistanceText) {
                gpsDistanceText.textContent = 'Browser tidak mendukung GPS';
            }
            return;
        }

        navigator.geolocation.getCurrentPosition(
            (pos) => updateGpsUI(pos.coords.latitude, pos.coords.longitude, pos.coords.accuracy),
            handleGpsError,
            { enableHighAccuracy: true, timeout: 10000, maximumAge: 10000 }
        );

        navigator.geolocation.watchPosition(
            (pos) => updateGpsUI(pos.coords.latitude, pos.coords.longitude, pos.coords.accuracy),
            handleGpsError,
            { enableHighAccuracy: true, timeout: 15000, maximumAge: 5000 }
        );
    }
    initGeolocation();

    // 5. Global RFID / Barcode Scanner Listener (Hardware Keyboard Wedge & Bluetooth)
    const scannerInput = document.getElementById('kioskScannerInput');
    const scannerInputBox = document.getElementById('scannerInputBox');
    const scannerStatusIndicator = document.getElementById('scannerStatusIndicator');
    const scannerStatusLabel = document.getElementById('scannerStatusLabel');
    const scannerFocusStatusText = document.getElementById('scannerFocusStatusText');

    let scanBuffer = '';
    let keyTimestamps = [];
    let isProcessing = false;

    function setScannerFocusUI(isFocused) {
        if (scannerInputBox) {
            scannerInputBox.classList.toggle('focused', isFocused);
        }
        if (scannerStatusIndicator) {
            scannerStatusIndicator.classList.toggle('unfocused', !isFocused);
        }
        if (scannerStatusLabel) {
            scannerStatusLabel.textContent = isFocused ? 'KURSOR AKTIF' : 'KLIK UNTUK FOKUS';
        }
        if (scannerFocusStatusText) {
            if (isFocused) {
                scannerFocusStatusText.className = 'focus-ok';
                scannerFocusStatusText.innerHTML = '<i class="fas fa-circle-dot me-1"></i> Scanner siap memindai (Kursor Aktif)';
            } else {
                scannerFocusStatusText.className = 'focus-warn';
                scannerFocusStatusText.innerHTML = '<i class="fas fa-hand-pointer me-1"></i> Klik kotak scanner untuk mengaktifkan kursor';
            }
        }
    }

    function ensureFocus() {
        if (scannerInput && document.activeElement !== scannerInput) {
            const tag = document.activeElement ? document.activeElement.tagName : '';
            if (tag !== 'BUTTON' && tag !== 'A' && tag !== 'SELECT') {
                scannerInput.focus();
            }
        }
    }

    if (scannerInput) {
        scannerInput.addEventListener('focus', function () {
            setScannerFocusUI(true);
        });

        scannerInput.addEventListener('blur', function () {
            setScannerFocusUI(false);
            // Kembalikan fokus otomatis setelah 250ms jika tidak sedang berinteraksi dengan tombol kontrol
            setTimeout(() => {
                if (!isProcessing) {
                    ensureFocus();
                }
            }, 250);
        });

        if (scannerInputBox) {
            scannerInputBox.addEventListener('click', function () {
                scannerInput.focus();
            });
        }

        scannerInput.addEventListener('input', function () {
            keyTimestamps.push(Date.now());
        });
    }

    // Inisialisasi fokus awal
    ensureFocus();
    document.addEventListener('click', function (e) {
        if (e.target.closest('button, a, select, .kiosk-mode-pill')) return;
        ensureFocus();
    });
    document.addEventListener('touchstart', function (e) {
        if (e.target.closest('button, a, select, .kiosk-mode-pill')) return;
        ensureFocus();
    }, { passive: true });

    // Hardware & Bluetooth Scanner Wedge Listener
    window.addEventListener('keydown', function (e) {
        const now = Date.now();

        if (e.key === 'Enter') {
            e.preventDefault();

            // Ambil identifier: dari input field ATAU dari buffer keydown
            const inputVal = (scannerInput ? scannerInput.value : '').trim();
            const bufferVal = scanBuffer.trim();
            const rawIdentifier = inputVal || bufferVal;

            if (rawIdentifier && !isProcessing) {
                const charCount = keyTimestamps.length;
                let isHardwareScan = true;

                if (charCount >= 4) {
                    const totalDuration = keyTimestamps[charCount - 1] - keyTimestamps[0];
                    const avgInterval = totalDuration / (charCount - 1);

                    // Toleransi jeda hingga 200ms atau total 3000ms untuk mendukung Bluetooth scanner & USB OTG lambat
                    if (avgInterval > 200 || totalDuration > 3000) {
                        isHardwareScan = false;
                    }
                }

                if (!isHardwareScan) {
                    playSound('warning');
                    showNoticeCard(
                        'Input Manual Dinonaktifkan',
                        'Sistem hanya menerima pemindaian fisik dari pembaca RFID atau scanner barcode/QR.',
                        'warning'
                    );
                    speakGreeting('Input manual dinonaktifkan. Silakan tempelkan kartu pada pemindai.');
                } else {
                    if (scannerInputBox) scannerInputBox.classList.add('processing');
                    if (scannerInput) scannerInput.value = rawIdentifier;
                    processScanAttendance(rawIdentifier);
                }
            }

            if (scannerInput) {
                setTimeout(() => {
                    scannerInput.value = '';
                    if (scannerInputBox) scannerInputBox.classList.remove('processing');
                }, 400);
            }
            scanBuffer = '';
            keyTimestamps = [];
        } else if (e.key.length === 1) {
            // Bersihkan buffer jika jeda dengan karakter sebelumnya terlalu lama (> 350ms)
            if (keyTimestamps.length > 0 && (now - keyTimestamps[keyTimestamps.length - 1]) > 350) {
                scanBuffer = '';
                keyTimestamps = [];
            }
            scanBuffer += e.key;
            keyTimestamps.push(now);

            // Pastikan kursor tetap fokus ke scanner input
            if (scannerInput && document.activeElement !== scannerInput) {
                ensureFocus();
            }
        }
    });

    // 5.1. Live Camera Barcode & QR Scanner Engine (Hybrid: BarcodeDetector + jsQR Fallback)
    let barcodeDetectorInstance = null;
    if ('BarcodeDetector' in window) {
        try {
            barcodeDetectorInstance = new BarcodeDetector({
                formats: ['qr_code', 'code_128', 'code_39', 'ean_13', 'data_matrix']
            });
        } catch (e) {
            console.warn('BarcodeDetector format error, fallback ke jsQR:', e);
            barcodeDetectorInstance = null;
        }
    }

    let isCameraScanning = false;
    let lastScannedCode = '';
    let lastScannedAt = 0;

    // Canvas terpisah untuk pemindaian video frame
    const qrScanCanvas = document.createElement('canvas');
    const qrScanCtx = qrScanCanvas.getContext('2d', { willReadFrequently: true });

    async function scanVideoFrame() {
        if (!videoEl || !videoStream || isProcessing || isCameraScanning) return;
        if (videoEl.readyState < 2 || videoEl.videoWidth === 0 || videoEl.videoHeight === 0) return;

        isCameraScanning = true;
        try {
            let detectedRaw = null;

            // 1. Coba BarcodeDetector native jika didukung browser
            if (barcodeDetectorInstance) {
                try {
                    const barcodes = await barcodeDetectorInstance.detect(videoEl);
                    if (barcodes && barcodes.length > 0) {
                        const raw = barcodes[0].rawValue;
                        if (raw && raw.trim().length >= 3) {
                            detectedRaw = raw.trim();
                        }
                    }
                } catch (detectorErr) {
                    // Fallback ke jsQR jika detector native error pada frame ini
                }
            }

            // 2. Fallback ke jsQR jika belum terdeteksi (sangat andal di Safari iOS, Android PWA, dan Chrome)
            if (!detectedRaw && typeof window.jsQR === 'function') {
                const width = videoEl.videoWidth;
                const height = videoEl.videoHeight;

                // Scale down jika resolusi kamera tinggi (> 640px) agar scanning cepat & hemat CPU di mobile
                const scale = Math.min(1, 640 / width);
                const targetW = Math.floor(width * scale);
                const targetH = Math.floor(height * scale);

                qrScanCanvas.width = targetW;
                qrScanCanvas.height = targetH;
                qrScanCtx.drawImage(videoEl, 0, 0, targetW, targetH);

                const imageData = qrScanCtx.getImageData(0, 0, targetW, targetH);
                const qrResult = window.jsQR(imageData.data, targetW, targetH, {
                    inversionAttempts: 'dontInvert'
                });

                if (qrResult && qrResult.data && qrResult.data.trim().length >= 3) {
                    detectedRaw = qrResult.data.trim();
                }
            }

            // 3. Proses pemindaian jika ada kode ditemukan
            if (detectedRaw && !isProcessing) {
                const now = Date.now();
                // Cegah pemindaian berulang kode yang sama dalam interval 3 detik
                if (detectedRaw !== lastScannedCode || (now - lastScannedAt) > 3000) {
                    lastScannedCode = detectedRaw;
                    lastScannedAt = now;
                    processScanAttendance(detectedRaw);
                }
            }
        } catch (err) {
            // Abaikan error per-frame
        } finally {
            isCameraScanning = false;
        }
    }

    // Interval scanning 200ms untuk performa responsif dan mulus
    setInterval(scanVideoFrame, 200);

    // 5.2. Handler Tombol Kunci Terminal Kiosk
    const btnKioskLock = document.getElementById('btnKioskLock');
    if (btnKioskLock) {
        btnKioskLock.addEventListener('click', function (e) {
            e.preventDefault();
            const lockUrl = this.getAttribute('href');
            Swal.fire({
                title: 'Kunci Terminal Kiosk?',
                text: 'Layar pemindai akan dikunci. Diperlukan kode akses untuk membukanya kembali.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#4b5563',
                confirmButtonText: '<i class="fas fa-lock me-1"></i> Kunci Sekarang',
                cancelButtonText: 'Batal'
            }).then((res) => {
                if (res.isConfirmed) {
                    window.location.href = lockUrl;
                }
            });
        });
    }

    // 6. Eksekusi Proses Presensi via AJAX
    async function processScanAttendance(identifier) {
        if (isProcessing) return;
        isProcessing = true;

        // Validasi Geolokasi jika diwajibkan oleh sekolah
        if (requireLocation) {
            if (currentLat === null || currentLon === null) {
                playSound('error');
                showNoticeCard(
                    'Izin Lokasi Diperlukan',
                    'Koordinat GPS belum terdeteksi. Harap aktifkan GPS dan berikan izin akses lokasi pada browser Anda.',
                    'error'
                );
                speakGreeting('Izin lokasi GPS diperlukan untuk presensi.');
                isProcessing = false;
                ensureFocus();
                return;
            }

            if (schoolLat !== null && schoolLon !== null && currentDistance !== null && currentDistance > schoolRadius) {
                playSound('error');
                showNoticeCard(
                    'Di Luar Radius Sekolah',
                    `Jarak Anda saat ini (${currentDistance} m) berada di luar batas toleransi radius sekolah (${schoolRadius} m). Presensi ditolak.`,
                    'error'
                );
                speakGreeting('Presensi ditolak. Lokasi Anda berada di luar radius sekolah.');
                isProcessing = false;
                ensureFocus();
                return;
            }
        }

        const snapshot = captureSnapshot();
        const modeEl = document.querySelector('input[name="kiosk_mode"]:checked');
        const modeVal = modeEl ? modeEl.value : 'auto';

        try {
            const response = await fetch('/presensi/scan/process', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    identifier: identifier,
                    mode: modeVal,
                    snapshot: snapshot,
                    latitude: currentLat,
                    longitude: currentLon
                })
            });

            const res = await response.json();

            if (response.ok && res.status === 'success') {
                playSound(res.data?.status === 'T' ? 'warning' : 'success');
                showVerificationPopup(res);
                if (res.speech_text) speakGreeting(res.speech_text);
                addRecentScanFeed(res.data);
            } else if (res.status === 'info') {
                playSound('warning');
                showVerificationPopup(res, 'info');
                if (res.speech_text) speakGreeting(res.speech_text);
            } else if (res.status === 'warning') {
                playSound('warning');
                showNoticeCard(res.title, res.message, 'warning');
                if (res.speech_text) speakGreeting(res.speech_text);
            } else {
                playSound('error');
                showNoticeCard(res.title || 'Gagal', res.message || 'Data tidak ditemukan.', 'error');
                if (res.speech_text) speakGreeting(res.speech_text);
            }
        } catch (err) {
            playSound('error');
            showNoticeCard('Kesalahan Jaringan', 'Gagal memproses presensi ke server.', 'error');
        } finally {
            setTimeout(() => {
                isProcessing = false;
                if (scannerInput) scannerInput.value = '';
                if (scannerInputBox) scannerInputBox.classList.remove('processing');
                ensureFocus();
            }, 800);
        }
    }

    // 7. Pop-up Kartu Verifikasi Siswa
    const popupOverlay = document.getElementById('verifyPopupOverlay');
    const popupFoto = document.getElementById('popupFotoSiswa');
    const popupNama = document.getElementById('popupNamaSiswa');
    const popupRombel = document.getElementById('popupRombelSiswa');
    const popupNisn = document.getElementById('popupNisnSiswa');
    const popupStatusBadge = document.getElementById('popupStatusBadge');
    const popupWaktu = document.getElementById('popupWaktuPresensi');
    const popupPesan = document.getElementById('popupPesanDetail');

    let popupTimer = null;

    function showVerificationPopup(res, type = 'success') {
        if (!popupOverlay) return;
        const d = res.data;

        if (popupFoto) popupFoto.src = d.foto_url || '/img/logo-dark.png';
        if (popupNama) popupNama.textContent = d.nama || 'Peserta Didik';
        if (popupRombel) popupRombel.textContent = d.rombel || '-';
        if (popupNisn) popupNisn.textContent = 'NISN: ' + (d.nisn || '-');

        const waktu = d.jam_pulang || d.jam_masuk || '--:--';
        if (popupWaktu) popupWaktu.textContent = `Pukul ${waktu} WIB`;
        if (popupPesan) popupPesan.textContent = res.message;

        const popupLokasiWrap = document.getElementById('popupLokasiPresensi');
        const popupLokasiText = document.getElementById('popupLokasiText');
        if (popupLokasiWrap && popupLokasiText) {
            if (d.jarak_meter !== null && d.jarak_meter !== undefined) {
                popupLokasiWrap.style.display = 'block';
                popupLokasiText.textContent = `Terverifikasi di radius sekolah (${Math.round(d.jarak_meter)} meter)`;
            } else {
                popupLokasiWrap.style.display = 'none';
            }
        }

        if (popupStatusBadge) {
            if (d.status === 'H') {
                popupStatusBadge.className = 'badge badge-success';
                popupStatusBadge.innerHTML = '<i class="fas fa-check-circle"></i> TEPAT WAKTU';
            } else if (d.status === 'T') {
                popupStatusBadge.className = 'badge badge-warning';
                popupStatusBadge.innerHTML = `<i class="fas fa-clock"></i> TERLAMBAT ${d.menit_terlambat}m`;
            } else {
                popupStatusBadge.className = 'badge badge-primary';
                popupStatusBadge.innerHTML = `<i class="fas fa-info-circle"></i> ${d.status_label || 'TERCATAT'}`;
            }
        }

        popupOverlay.classList.add('active');

        // Otomatis sembunyikan setelah 3.5 detik
        clearTimeout(popupTimer);
        popupTimer = setTimeout(() => {
            popupOverlay.classList.remove('active');
        }, 3500);
    }

    function showNoticeCard(title, message, iconType) {
        Swal.fire({
            icon: iconType,
            title: title,
            text: message,
            timer: 3000,
            showConfirmButton: false,
            toast: true,
            position: 'top-end',
            customClass: {
                popup: 'colored-toast'
            }
        });
    }

    // 8. Update Recent Scans Feed with Anti-Duplicate Filter
    function addRecentScanFeed(d) {
        const feedList = document.getElementById('kioskRecentFeed');
        if (!feedList || !d) return;

        const timeStr = (d.jam_pulang ? d.jam_pulang.substring(0, 5) : (d.jam_masuk ? d.jam_masuk.substring(0, 5) : '--:--'));
        const scanKey = `${d.nisn || d.peserta_didik_id || d.nama}_${timeStr}`;

        // Cegah duplikasi jika item dengan scanKey yang sama sudah ada di feed
        if (feedList.querySelector(`[data-scan-key="${scanKey}"]`)) {
            return;
        }

        // Hapus empty state placeholder jika ada
        const emptyNotice = feedList.querySelector('.empty-recent-feed, div[style*="text-align: center"]');
        if (emptyNotice) emptyNotice.remove();

        const badgeHtml = d.status === 'H'
            ? '<span class="badge badge-success" style="font-size: 0.7rem;">Hadir</span>'
            : (d.status === 'T'
                ? `<span class="badge badge-warning" style="font-size: 0.7rem;">+${d.menit_terlambat || 0}m</span>`
                : `<span class="badge badge-primary" style="font-size: 0.7rem;">${d.status}</span>`);

        const itemHtml = `
            <div class="recent-scan-item" data-scan-key="${scanKey}" style="animation: slideInDown 0.3s ease;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <img src="${d.foto_url || '/img/logo-dark.png'}" class="recent-scan-avatar" onerror="this.src='/img/logo-dark.png';">
                    <div>
                        <div style="font-weight: 700; font-size: 0.88rem; color: var(--text-color);">${d.nama}</div>
                        <div style="font-size: 0.75rem; color: var(--text-muted);">${d.rombel} &bull; NISN: ${d.nisn}</div>
                    </div>
                </div>
                <div style="text-align: right;">
                    <div style="font-weight: 700; font-family: monospace; font-size: 0.85rem; color: var(--text-color);">${timeStr} WIB</div>
                    <div>${badgeHtml}</div>
                </div>
            </div>
        `;

        feedList.insertAdjacentHTML('afterbegin', itemHtml);

        // Pertahankan maksimal 6 item di list
        while (feedList.children.length > 6) {
            feedList.removeChild(feedList.lastChild);
        }
    }

});

