        document.addEventListener('DOMContentLoaded', () => {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const initialPayload = JSON.parse(document.getElementById('initialKioskPayload')?.textContent || '{}');
            let soundEnabled = true;
            let autoCloseTimer = null;
            let isProcessing = false;

            // 1. Digital Clock Live
            const clockEl = document.getElementById('kioskLiveClock');
            setInterval(() => {
                if (clockEl) {
                    const now = new Date();
                    clockEl.textContent = now.toLocaleTimeString('id-ID', { hour12: false });
                }
            }, 1000);

            // 2. Fullscreen Toggle
            const btnFullscreen = document.getElementById('btnToggleFullscreen');
            if (btnFullscreen) {
                btnFullscreen.addEventListener('click', () => {
                    if (!document.fullscreenElement) {
                        document.documentElement.requestFullscreen().catch(() => {});
                        btnFullscreen.innerHTML = '<i class="fas fa-compress"></i>';
                        btnFullscreen.title = 'Keluar Layar Penuh';
                    } else {
                        document.exitFullscreen().catch(() => {});
                        btnFullscreen.innerHTML = '<i class="fas fa-expand"></i>';
                        btnFullscreen.title = 'Layar Penuh (Monitor Kiosk)';
                    }
                });
            }

            // 3. Audio & Web Speech Synthesis
            const btnSound = document.getElementById('btnToggleSound');
            if (btnSound) {
                btnSound.addEventListener('click', () => {
                    soundEnabled = !soundEnabled;
                    btnSound.className = soundEnabled ? 'btn btn-primary btn-kiosk-action' : 'btn btn-outline btn-kiosk-action';
                    btnSound.innerHTML = soundEnabled ? '<i class="fas fa-volume-high"></i>' : '<i class="fas fa-volume-xmark"></i>';
                    btnSound.title = soundEnabled ? 'Suara Aktif' : 'Suara Dibisukan';
                });
            }

            const playSoundEffect = (type = 'success') => {
                if (!soundEnabled) return;
                try {
                    const ctx = new (window.AudioContext || window.webkitAudioContext)();
                    const now = ctx.currentTime;
                    if (type === 'success') {
                        const notes = [523.25, 659.25, 783.99];
                        notes.forEach((freq, idx) => {
                            const osc = ctx.createOscillator();
                            const gain = ctx.createGain();
                            osc.type = 'triangle';
                            osc.frequency.value = freq;
                            gain.gain.setValueAtTime(0.2, now + idx * 0.08);
                            gain.gain.exponentialRampToValueAtTime(0.001, now + idx * 0.08 + 0.3);
                            osc.connect(gain);
                            gain.connect(ctx.destination);
                            osc.start(now + idx * 0.08);
                            osc.stop(now + idx * 0.08 + 0.35);
                        });
                    } else if (type === 'warning') {
                        [440, 440].forEach((freq, idx) => {
                            const osc = ctx.createOscillator();
                            const gain = ctx.createGain();
                            osc.type = 'square';
                            osc.frequency.value = freq;
                            gain.gain.setValueAtTime(0.15, now + idx * 0.15);
                            gain.gain.exponentialRampToValueAtTime(0.001, now + idx * 0.15 + 0.12);
                            osc.connect(gain);
                            gain.connect(ctx.destination);
                            osc.start(now + idx * 0.15);
                            osc.stop(now + idx * 0.15 + 0.15);
                        });
                    } else {
                        const osc = ctx.createOscillator();
                        const gain = ctx.createGain();
                        osc.type = 'sawtooth';
                        osc.frequency.value = 160;
                        gain.gain.setValueAtTime(0.25, now);
                        gain.gain.exponentialRampToValueAtTime(0.001, now + 0.45);
                        osc.connect(gain);
                        gain.connect(ctx.destination);
                        osc.start(now);
                        osc.stop(now + 0.5);
                    }
                } catch (e) {
                    console.warn('AudioContext error:', e);
                }
            };

            const speakAnnouncement = (text) => {
                if (!soundEnabled || !window.speechSynthesis) return;
                try {
                    window.speechSynthesis.cancel();
                    const u = new SpeechSynthesisUtterance(text);
                    u.lang = 'id-ID';
                    u.rate = 1.05;
                    u.pitch = 1.0;
                    window.speechSynthesis.speak(u);
                } catch (e) {
                    console.warn('TTS error:', e);
                }
            };

            // 4. Input Elements & Keyboard Gun Autofocus
            const inputCode = document.getElementById('kioskInputCode');
            const resultModal = document.getElementById('resultModal');
            const btnCloseResult = document.getElementById('btnCloseResult');
            const resFotoSiswa = document.getElementById('resFotoSiswa');
            const resBadgeStatus = document.getElementById('resBadgeStatus');
            const resNamaSiswa = document.getElementById('resNamaSiswa');
            const resRombelSiswa = document.getElementById('resRombelSiswa');
            const resNisnSiswa = document.getElementById('resNisnSiswa');
            const resPesanJudul = document.getElementById('resPesanJudul');
            const resPesanDetail = document.getElementById('resPesanDetail');

            const focusInput = () => {
                if (inputCode && resultModal.style.display !== 'flex') {
                    inputCode.focus();
                }
            };

            document.addEventListener('click', (e) => {
                if (!e.target.closest('button') && !e.target.closest('a') && !e.target.closest('input')) {
                    focusInput();
                }
            });

            const hideResult = () => {
                if (autoCloseTimer) {
                    clearTimeout(autoCloseTimer);
                    autoCloseTimer = null;
                }
                resultModal.style.display = 'none';
                if (inputCode) {
                    inputCode.disabled = false;
                    inputCode.value = '';
                    inputCode.focus();
                }
            };

            if (btnCloseResult) btnCloseResult.addEventListener('click', hideResult);
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') hideResult();
            });

            const showResultCard = (data) => {
                if (autoCloseTimer) {
                    clearTimeout(autoCloseTimer);
                    autoCloseTimer = null;
                }
                const isSuccess = (data.status === 'success');
                const isWarning = (data.status === 'warning');

                const siswa = data.siswa || {};
                resFotoSiswa.src = siswa.foto || '/img/avatar-default.png';
                resNamaSiswa.textContent = siswa.nama || (data.title === 'Identitas Tidak Dikenali' ? 'Kartu / Kode Tidak Terdaftar' : (isSuccess ? 'Peserta Didik' : (data.title || 'Pemeriksaan Gerbang')));
                resRombelSiswa.textContent = siswa.rombel ? `Kelas ${siswa.rombel}` : '';
                resNisnSiswa.textContent = siswa.nisn ? `NISN: ${siswa.nisn}` : '';

                if (isSuccess) {
                    const isCheckout = (data.action === 'checkout');
                    resBadgeStatus.className = isCheckout ? 'badge badge-warning' : 'badge badge-success';
                    resBadgeStatus.style.background = isCheckout ? '#ea580c' : '#10b981';
                    resBadgeStatus.style.color = '#ffffff';
                    resBadgeStatus.innerHTML = isCheckout
                        ? '<i class="fas fa-door-open"></i> VERIFIKASI KELUAR BERHASIL'
                        : '<i class="fas fa-right-to-bracket"></i> VERIFIKASI KEMBALI BERHASIL';

                    playSoundEffect('success');
                    speakAnnouncement(`${siswa.nama || 'Siswa'}. ${data.title || 'Verifikasi berhasil'}.`);
                } else if (isWarning) {
                    resBadgeStatus.className = 'badge badge-warning';
                    resBadgeStatus.style.background = '#f59e0b';
                    resBadgeStatus.style.color = '#000000';
                    resBadgeStatus.innerHTML = '<i class="fas fa-triangle-exclamation"></i> TIDAK ADA IZIN AKTIF';

                    playSoundEffect('warning');
                    speakAnnouncement(data.message || 'Tidak ada tiket e-izin aktif.');
                } else {
                    resBadgeStatus.className = 'badge badge-danger';
                    resBadgeStatus.style.background = '#ef4444';
                    resBadgeStatus.style.color = '#ffffff';
                    const badgeText = (data.title && data.title.length <= 25) ? data.title.toUpperCase() : 'DITOLAK SESUAI SOP';
                    resBadgeStatus.innerHTML = `<i class="fas fa-circle-xmark"></i> ${badgeText}`;

                    playSoundEffect('error');
                    speakAnnouncement(data.title || 'SOP Ditolak');
                }

                resPesanJudul.textContent = data.title || (isSuccess ? 'Pemeriksaan Berhasil' : 'Perhatian');
                resPesanDetail.textContent = data.message || '';

                resultModal.style.display = 'flex';

                // Auto-close modal otomatis untuk semua status (sukses, warning, error, identitas tidak dikenali)
                const closeDelay = isSuccess ? 4000 : 3500;
                autoCloseTimer = setTimeout(() => {
                    hideResult();
                    if (isSuccess) {
                        refreshLiveFeed();
                    }
                }, closeDelay);
            };

            const processScan = (raw) => {
                if (isProcessing) return;
                const code = raw.trim();
                if (!code) return;

                isProcessing = true;
                if (inputCode) inputCode.disabled = true;

                fetch('/e-izin/kiosk/scan', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({ raw_input: code })
                })
                    .then((res) => res.json())
                    .then((data) => {
                        showResultCard(data);
                    })
                    .catch((err) => {
                        showResultCard({
                            status: 'error',
                            title: 'Koneksi Terganggu',
                            message: err.message || 'Gagal memproses kode pemindaian.'
                        });
                    })
                    .finally(() => {
                        isProcessing = false;
                        if (inputCode) {
                            inputCode.disabled = false;
                            inputCode.value = '';
                            if (resultModal.style.display !== 'flex') {
                                inputCode.focus();
                            }
                        }
                    });
            };

            const formKioskScan = document.getElementById('formKioskScan');
            if (formKioskScan) {
                formKioskScan.addEventListener('submit', (e) => {
                    e.preventDefault();
                    if (inputCode && inputCode.value) {
                        processScan(inputCode.value);
                    }
                });
            }

            // 5. Chart.js Inisialisasi & Live Update
            let kioskChart = null;
            const initChart = (stats) => {
                const canvas = document.getElementById('kioskActivityChart');
                if (!canvas || typeof Chart === 'undefined') return;

                const hourly = stats?.hourly || {
                    labels: ['07:00', '08:00', '09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00'],
                    keluar: [0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
                    kembali: [0, 0, 0, 0, 0, 0, 0, 0, 0, 0]
                };

                kioskChart = new Chart(canvas.getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: hourly.labels,
                        datasets: [
                            {
                                label: 'Keluar Gerbang',
                                data: hourly.keluar,
                                backgroundColor: 'rgba(234, 88, 12, 0.75)',
                                borderColor: '#ea580c',
                                borderWidth: 1.5,
                                borderRadius: 4,
                                barPercentage: 0.75,
                                categoryPercentage: 0.8
                            },
                            {
                                label: 'Kembali Masuk',
                                data: hourly.kembali,
                                backgroundColor: 'rgba(16, 185, 129, 0.75)',
                                borderColor: '#10b981',
                                borderWidth: 1.5,
                                borderRadius: 4,
                                barPercentage: 0.75,
                                categoryPercentage: 0.8
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        animation: { duration: 400 },
                        plugins: {
                            legend: {
                                position: 'top',
                                align: 'end',
                                labels: {
                                    boxWidth: 10,
                                    font: { size: 10, weight: '700' },
                                    color: '#94a3b8',
                                    padding: 8
                                }
                            },
                            tooltip: {
                                mode: 'index',
                                intersect: false,
                                padding: 8,
                                bodyFont: { size: 11 },
                                titleFont: { size: 11, weight: '700' }
                            }
                        },
                        scales: {
                            x: {
                                grid: { display: false },
                                ticks: { font: { size: 9 }, color: '#94a3b8', maxRotation: 0 }
                            },
                            y: {
                                beginAtZero: true,
                                ticks: { stepSize: 1, precision: 0, font: { size: 9 }, color: '#94a3b8' },
                                grid: { color: 'rgba(255, 255, 255, 0.06)' }
                            }
                        }
                    }
                });
            };

            const updateChartData = (hourly) => {
                if (!kioskChart || !hourly) return;
                kioskChart.data.labels = hourly.labels;
                kioskChart.data.datasets[0].data = hourly.keluar;
                kioskChart.data.datasets[1].data = hourly.kembali;
                kioskChart.update('none');
            };

            initChart(initialPayload?.stats);

            // 6. Live Feed & Polling
            const refreshLiveFeed = () => {
                fetch('/e-izin/kiosk/live-log', {
                    headers: { 'Accept': 'application/json' }
                })
                    .then((res) => res.json())
                    .then((data) => {
                        if (data.status === 'success') {
                            if (data.stats) {
                                document.getElementById('statTotal').textContent = data.stats.total;
                                document.getElementById('statDiLuar').textContent = data.stats.di_luar;
                                document.getElementById('statKembali').textContent = data.stats.kembali;
                                document.getElementById('statPulang').textContent = data.stats.pulang;
                                if (data.stats.hourly) {
                                    updateChartData(data.stats.hourly);
                                }
                            }

                            const container = document.getElementById('liveFeedContainer');
                            if (container && data.logs && data.logs.length > 0) {
                                let html = '';
                                data.logs.forEach((log) => {
                                    let badgeHtml = '';
                                    const keluarTime = log.satpam_checkout_at ? log.satpam_checkout_at.substring(11, 16) : (log.jam_izin_keluar ? log.jam_izin_keluar.substring(0, 5) : '--:--');
                                    const kembaliTime = log.jam_kembali_aktual ? log.jam_kembali_aktual.substring(0, 5) : (log.satpam_checkin_at ? log.satpam_checkin_at.substring(11, 16) : '--:--');

                                    if (log.status === 'di_luar') {
                                        badgeHtml = `<span class="badge" style="background: rgba(234,88,12,0.15); color: #ea580c; font-size: var(--text-2xs, 0.68rem); padding: 2px 6px; font-weight: 800;"><i class="fas fa-door-open"></i> KELUAR</span><div style="font-size: var(--text-2xs, 0.68rem); font-family: monospace; color: var(--text-muted); margin-top: 2px;">${keluarTime} WIB</div>`;
                                    } else if (log.status === 'kembali') {
                                        badgeHtml = `<span class="badge" style="background: rgba(16,185,129,0.15); color: #10b981; font-size: var(--text-2xs, 0.68rem); padding: 2px 6px; font-weight: 800;"><i class="fas fa-right-to-bracket"></i> KEMBALI</span><div style="font-size: var(--text-2xs, 0.68rem); font-family: monospace; color: var(--text-muted); margin-top: 2px;">${kembaliTime} WIB</div>`;
                                    } else {
                                        badgeHtml = `<span class="badge" style="background: rgba(99,102,241,0.15); color: #6366f1; font-size: var(--text-2xs, 0.68rem); padding: 2px 6px; font-weight: 800;"><i class="fas fa-house-chimney"></i> PULANG</span><div style="font-size: var(--text-2xs, 0.68rem); font-family: monospace; color: var(--text-muted); margin-top: 2px;">${keluarTime} WIB</div>`;
                                    }

                                    html += `
                                        <div class="live-log-item">
                                            <div style="display: flex; align-items: center; gap: 10px;">
                                                <img src="${log.foto_url}" alt="${log.nama_siswa}"
                                                    style="width: 36px; height: 36px; border-radius: 50%; object-fit: cover; border: 1.5px solid var(--border-color);">
                                                <div>
                                                    <div style="font-weight: 700; font-size: var(--text-sm, 0.8125rem); color: var(--text-color);">
                                                        ${log.nama_siswa}
                                                    </div>
                                                    <div style="font-size: var(--text-2xs, 0.7rem); color: var(--text-muted);">
                                                        ${log.nama_rombel || 'Siswa'} &bull; Tiket: ${log.nomor_tiket}
                                                    </div>
                                                </div>
                                            </div>
                                            <div style="text-align: right;">${badgeHtml}</div>
                                        </div>
                                    `;
                                });
                                container.innerHTML = html;
                            }
                        }
                    })
                    .catch((err) => console.warn('Gagal poll live feed:', err));
            };

            setInterval(refreshLiveFeed, 6000);

            // 7. Kamera Webcam Live & Auto-Decoder QR (jsQR)
            const videoEl = document.getElementById('videoPreview');
            const cameraNoticeOverlay = document.getElementById('cameraNoticeOverlay');
            const cameraNoticeTitle = document.getElementById('cameraNoticeTitle');
            const cameraNoticeDesc = document.getElementById('cameraNoticeDesc');
            const cameraStatusBadge = document.getElementById('cameraStatusBadge');
            const btnSwitchCamera = document.getElementById('btnSwitchCamera');
            const btnToggleCamera = document.getElementById('btnToggleCamera');
            const btnRetryCamera = document.getElementById('btnRetryCamera');

            let videoStream = null;
            let cameraActive = false;
            let cameraAnimFrame = null;
            let scanCanvas = null;
            let scanCanvasCtx = null;
            let lastScanTick = 0;

            const isTouchDevice = ('ontouchstart' in window) || (navigator.maxTouchPoints > 0) || window.innerWidth <= 768;
            let currentFacingMode = isTouchDevice ? 'environment' : 'user';

            const showCameraNotice = (title, desc) => {
                if (cameraNoticeOverlay) {
                    cameraNoticeOverlay.style.display = 'flex';
                    if (cameraNoticeTitle) cameraNoticeTitle.textContent = title;
                    if (cameraNoticeDesc) cameraNoticeDesc.textContent = desc;
                }
                if (cameraStatusBadge) {
                    cameraStatusBadge.className = 'kiosk-cam-badge status-warn';
                    cameraStatusBadge.innerHTML = '<i class="fas fa-video-slash"></i>';
                    cameraStatusBadge.title = title;
                }
            };

            const hideCameraNotice = () => {
                if (cameraNoticeOverlay) cameraNoticeOverlay.style.display = 'none';
                if (cameraStatusBadge) {
                    cameraStatusBadge.className = 'kiosk-cam-badge status-ok';
                    cameraStatusBadge.innerHTML = '<i class="fas fa-video"></i>';
                    cameraStatusBadge.title = `Kamera Aktif (${currentFacingMode === 'environment' ? 'Belakang' : 'Depan'})`;
                }
            };

            const checkAvailableCameras = async () => {
                if (!navigator.mediaDevices || !navigator.mediaDevices.enumerateDevices) return;
                try {
                    const devices = await navigator.mediaDevices.enumerateDevices();
                    const videoDevices = devices.filter((d) => d.kind === 'videoinput');
                    if (btnSwitchCamera) {
                        btnSwitchCamera.style.display = videoDevices.length > 1 ? 'inline-flex' : 'none';
                    }
                } catch (e) {}
            };

            const stopCamera = () => {
                if (videoStream) {
                    videoStream.getTracks().forEach((t) => t.stop());
                    videoStream = null;
                }
                if (cameraAnimFrame) {
                    cancelAnimationFrame(cameraAnimFrame);
                    cameraAnimFrame = null;
                }
                cameraActive = false;
                if (cameraStatusBadge) {
                    cameraStatusBadge.className = 'kiosk-cam-badge status-err';
                    cameraStatusBadge.innerHTML = '<i class="fas fa-video-slash"></i>';
                    cameraStatusBadge.title = 'Kamera Nonaktif';
                }
            };

            const scanVideoFrame = () => {
                if (!cameraActive) return;

                if (!videoEl || videoEl.readyState !== videoEl.HAVE_ENOUGH_DATA) {
                    cameraAnimFrame = requestAnimationFrame(scanVideoFrame);
                    return;
                }

                const now = Date.now();
                // Throttle decode ke 120ms
                if (now - lastScanTick > 120) {
                    lastScanTick = now;

                    if (!scanCanvas) {
                        scanCanvas = document.createElement('canvas');
                        scanCanvasCtx = scanCanvas.getContext('2d', { willReadFrequently: true });
                    }

                    scanCanvas.width = videoEl.videoWidth;
                    scanCanvas.height = videoEl.videoHeight;
                    scanCanvasCtx.drawImage(videoEl, 0, 0, scanCanvas.width, scanCanvas.height);

                    if (typeof jsQR !== 'undefined') {
                        try {
                            const imageData = scanCanvasCtx.getImageData(0, 0, scanCanvas.width, scanCanvas.height);
                            const qr = jsQR(imageData.data, imageData.width, imageData.height, {
                                inversionAttempts: 'dontInvert'
                            });

                            if (qr && qr.data && !isProcessing && resultModal.style.display !== 'flex') {
                                processScan(qr.data);
                                // Jeda 3.5 detik setelah berhasil membaca QR agar modal tampil dan auto-close
                                setTimeout(() => {
                                    if (cameraActive) cameraAnimFrame = requestAnimationFrame(scanVideoFrame);
                                }, 3500);
                                return;
                            }
                        } catch (err) {
                            console.warn('Decode frame error:', err);
                        }
                    }
                }

                cameraAnimFrame = requestAnimationFrame(scanVideoFrame);
            };

            const startCamera = async () => {
                if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                    showCameraNotice(
                        'Kamera Tidak Didukung',
                        'Browser ini tidak menyediakan MediaDevices API atau koneksi HTTP non-aman.'
                    );
                    return;
                }

                stopCamera();

                let stream = null;
                try {
                    // Percobaan 1: facingMode ideal
                    stream = await navigator.mediaDevices.getUserMedia({
                        video: {
                            facingMode: { ideal: currentFacingMode },
                            width: { ideal: 1280 },
                            height: { ideal: 720 }
                        },
                        audio: false
                    });
                } catch (err1) {
                    console.warn(`Gagal buka kamera facingMode ${currentFacingMode}, mencoba kebalikannya...`, err1);
                    try {
                        // Percobaan 2: fallback opposite facingMode
                        const opposite = (currentFacingMode === 'user' ? 'environment' : 'user');
                        stream = await navigator.mediaDevices.getUserMedia({
                            video: {
                                facingMode: { ideal: opposite },
                                width: { ideal: 640 },
                                height: { ideal: 480 }
                            },
                            audio: false
                        });
                        currentFacingMode = opposite;
                    } catch (err2) {
                        console.warn('Gagal opposite facingMode, fallback video: true...', err2);
                        try {
                            // Percobaan 3: tanpa constraint sama sekali
                            stream = await navigator.mediaDevices.getUserMedia({ video: true, audio: false });
                        } catch (errFinal) {
                            console.error('Kamera gagal dibuka:', errFinal);
                            let msg = errFinal.message || 'Tidak dapat mengakses perangkat kamera.';
                            if (errFinal.name === 'NotAllowedError' || errFinal.name === 'PermissionDeniedError') {
                                msg = 'Izin kamera ditolak. Izinkan akses kamera pada ikon gembok di address bar browser.';
                            } else if (errFinal.name === 'NotFoundError' || errFinal.name === 'DevicesNotFoundError') {
                                msg = 'Tidak ada perangkat webcam atau kamera terdeteksi di perangkat ini.';
                            } else if (errFinal.name === 'NotReadableError' || errFinal.name === 'TrackStartError') {
                                msg = 'Kamera sedang digunakan oleh aplikasi lain.';
                            }
                            showCameraNotice('Akses Kamera Tertahan', msg);
                            return;
                        }
                    }
                }

                if (stream && videoEl) {
                    videoStream = stream;
                    videoEl.srcObject = stream;
                    videoEl.muted = true;
                    videoEl.setAttribute('playsinline', 'true');
                    videoEl.setAttribute('webkit-playsinline', 'true');

                    try {
                        await videoEl.play();
                    } catch (playErr) {
                        console.warn('Play delayed:', playErr);
                    }

                    cameraActive = true;
                    hideCameraNotice();
                    checkAvailableCameras();
                    cameraAnimFrame = requestAnimationFrame(scanVideoFrame);
                }
            };

            // Event Listeners Kamera
            if (btnToggleCamera) {
                btnToggleCamera.addEventListener('click', () => {
                    if (cameraActive) {
                        stopCamera();
                    } else {
                        startCamera();
                    }
                });
            }

            if (btnSwitchCamera) {
                btnSwitchCamera.addEventListener('click', () => {
                    currentFacingMode = (currentFacingMode === 'user') ? 'environment' : 'user';
                    startCamera();
                });
            }

            if (btnRetryCamera) {
                btnRetryCamera.addEventListener('click', () => {
                    startCamera();
                });
            }

            // Inisialisasi otomatis kamera saat terminal dimuat
            startCamera();
            focusInput();
        });
