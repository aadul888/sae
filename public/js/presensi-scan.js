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
                ? '<i class="fas fa-volume-high"></i> Suara Aktif'
                : '<i class="fas fa-volume-xmark"></i> Suara Nonaktif';
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

    // 4. Inisialisasi Kamera Live Snapshot
    const videoEl = document.getElementById('cameraVideo');
    const canvasEl = document.getElementById('snapshotCanvas');
    let videoStream = null;

    async function initCamera() {
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            console.warn('Browser tidak mendukung akses kamera.');
            return;
        }

        try {
            videoStream = await navigator.mediaDevices.getUserMedia({
                video: {
                    facingMode: 'user',
                    width: { ideal: 640 },
                    height: { ideal: 480 }
                },
                audio: false
            });

            if (videoEl) {
                videoEl.srcObject = videoStream;
            }
        } catch (err) {
            console.warn('Gagal membuka kamera / izin ditolak:', err);
            const statusEl = document.getElementById('cameraStatusBadge');
            if (statusEl) {
                statusEl.innerHTML = '<i class="fas fa-video-slash"></i> Kamera Nonaktif';
                statusEl.className = 'badge badge-warning';
            }
        }
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
            // Un-mirror when capturing
            ctx.translate(width, 0);
            ctx.scale(-1, 1);
            ctx.drawImage(videoEl, 0, 0, width, height);

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
                    gpsBadge.className = 'badge badge-success';
                    gpsBadge.innerHTML = `<i class="fas fa-location-dot"></i> GPS: Radius OK (${currentDistance}m)`;
                } else {
                    gpsBadge.className = 'badge badge-danger';
                    gpsBadge.innerHTML = `<i class="fas fa-triangle-exclamation"></i> GPS: Luar Radius (${currentDistance}m / maks ${schoolRadius}m)`;
                }
            }

            if (gpsDistanceText) {
                gpsDistanceText.innerHTML = inRadius
                    ? `<span style="color: var(--success);"><i class="fas fa-circle-check me-1"></i> ${currentDistance} m (Dalam Radius)</span>`
                    : `<span style="color: var(--danger);"><i class="fas fa-triangle-exclamation me-1"></i> ${currentDistance} m (Di Luar Radius)</span>`;
            }
        } else {
            if (gpsBadge) {
                gpsBadge.className = 'badge badge-primary';
                gpsBadge.innerHTML = `<i class="fas fa-location-dot"></i> GPS: Aktif (&plusmn;${Math.round(accuracy)}m)`;
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
                gpsBadge.className = 'badge badge-danger';
                gpsBadge.innerHTML = '<i class="fas fa-location-slash"></i> GPS: Izin Ditolak';
            } else {
                gpsBadge.className = 'badge badge-warning';
                gpsBadge.innerHTML = '<i class="fas fa-location-crosshairs"></i> GPS: Mencari Sinyal...';
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
                gpsBadge.className = 'badge badge-warning';
                gpsBadge.innerHTML = '<i class="fas fa-location-slash"></i> GPS: Tidak Didukung';
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

    // 5. Global RFID / Barcode Scanner Listener (Keyboard Wedge)
    const hiddenInput = document.getElementById('kioskScannerInput');
    let scanBuffer = '';
    let lastKeyTime = Date.now();
    let isProcessing = false;
    const isTouchDevice = ('ontouchstart' in window) || (navigator.maxTouchPoints > 0) || window.innerWidth <= 768;

    // Pastikan hidden input hanya auto-focus di PC/kiosk station non-touch agar keyboard virtual HP tidak terbuka
    function ensureFocus() {
        if (!isTouchDevice && hiddenInput && document.activeElement !== hiddenInput && document.activeElement.tagName !== 'INPUT') {
            hiddenInput.focus();
        }
    }
    if (!isTouchDevice) {
        ensureFocus();
        document.addEventListener('click', ensureFocus);
    }

    // Scanner Keyboard Wedge Listener
    window.addEventListener('keydown', function (e) {
        const currentTime = Date.now();

        // Jika karakter dikirim dengan interval cepat khas scanner (< 50ms)
        if (e.key === 'Enter') {
            e.preventDefault();
            const identifier = hiddenInput ? hiddenInput.value.trim() : scanBuffer.trim();
            if (identifier && !isProcessing) {
                processScanAttendance(identifier);
            }
            if (hiddenInput) hiddenInput.value = '';
            scanBuffer = '';
        } else if (e.key.length === 1) {
            // Buffer karakter
            if (currentTime - lastKeyTime > 200) {
                scanBuffer = '';
            }
            scanBuffer += e.key;
            lastKeyTime = currentTime;
        }
    });

    // Form manual scan submit (jika di-klik Enter atau tombol Cari)
    const formManual = document.getElementById('formManualScan');
    if (formManual) {
        formManual.addEventListener('submit', function (e) {
            e.preventDefault();
            const inputVal = document.getElementById('manualScanInput')?.value.trim();
            if (inputVal && !isProcessing) {
                processScanAttendance(inputVal);
                document.getElementById('manualScanInput').value = '';
            }
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
            const response = await fetch('/dashboard/presensi/scan/process', {
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
                ensureFocus();
            }, 1000);
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

    // 8. Update Recent Scans Feed
    function addRecentScanFeed(d) {
        const feedList = document.getElementById('kioskRecentFeed');
        if (!feedList) return;

        const timeStr = d.jam_pulang || d.jam_masuk || '--:--';
        const badgeHtml = d.status === 'H'
            ? '<span class="badge badge-success" style="font-size: 0.7rem;">Hadir</span>'
            : (d.status === 'T'
                ? `<span class="badge badge-warning" style="font-size: 0.7rem;">+${d.menit_terlambat}m</span>`
                : `<span class="badge badge-primary" style="font-size: 0.7rem;">${d.status}</span>`);

        const itemHtml = `
            <div class="recent-scan-item" style="animation: slideInDown 0.3s ease;">
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
