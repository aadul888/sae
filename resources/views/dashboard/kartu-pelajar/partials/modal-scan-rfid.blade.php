<!-- MODAL 8: Scanner QR Code & Reader RFID Selesai Cetak -->
<div id="modalScanRfidSelesai" class="modal-backdrop"
    style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.72); backdrop-filter: blur(5px); z-index: 99999 !important; align-items: center; justify-content: center; padding: 16px;">
    <div class="card kp-modal-card"
        style="max-width: 580px; width: 100%; max-height: 92vh; background: var(--card-bg); border-radius: 16px; overflow: hidden; box-shadow: 0 25px 60px rgba(0,0,0,0.5); display: flex; flex-direction: column;">

        <!-- Header -->
        <div class="kp-modal-header"
            style="background: linear-gradient(135deg, #059669 0%, #047857 100%); color: #ffffff; padding: 12px 18px; flex-shrink: 0; display: flex; justify-content: space-between; align-items: center;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <div
                    style="width: 34px; height: 34px; border-radius: 10px; background: rgba(255,255,255,0.2); display: flex; align-items: center; justify-content: center; font-size: 1.1rem;">
                    <i class="fas fa-qrcode"></i>
                </div>
                <div>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <h4 class="kp-modal-title"
                            style="color: #ffffff; font-size: 0.98rem; font-weight: 800; margin: 0;">
                            Verifikasi Selesai &amp; Registrasi RFID
                        </h4>
                        <span
                            style="background: rgba(255,255,255,0.25); color: #ffffff; font-size: 0.68rem; font-weight: 700; padding: 2px 7px; border-radius: 10px; display: inline-flex; align-items: center; gap: 4px;">
                            <span class="kp-scan-pulse-dot"></span> Siap Memindai
                        </span>
                    </div>
                    <div style="font-size: 0.72rem; color: rgba(255,255,255,0.9); margin-top: 1px;">
                        Setiap scan/tap otomatis tersimpan &amp; terdaftar ke presensi tanpa jeda.
                    </div>
                </div>
            </div>
            <button type="button" class="kp-modal-close" onclick="closeModalScanRfidSelesai()"
                style="color: #ffffff; opacity: 0.85; font-size: 1.4rem; line-height: 1; border: none; background: transparent; cursor: pointer;"
                aria-label="Tutup">&times;</button>
        </div>

        <div class="kp-modal-body" style="padding: 16px 20px; overflow-y: auto; flex: 1;">

            <!-- Mode Switcher Tabs -->
            <div
                style="display: flex; gap: 6px; margin-bottom: 12px; background: var(--bg-hover); padding: 4px; border-radius: 10px; border: 1px solid var(--border-color);">
                <button type="button" id="tabBtnScanTap" onclick="switchScanMode('tap')" class="btn btn-sm"
                    style="flex: 1; font-size: 0.78rem; font-weight: 700; border-radius: 8px; border: none; padding: 7px 10px; background: #059669; color: #ffffff;">
                    <i class="fas fa-satellite-dish me-1"></i> Tap RFID / Barcode USB
                </button>
                <button type="button" id="tabBtnScanCamera" onclick="switchScanMode('camera')" class="btn btn-sm"
                    style="flex: 1; font-size: 0.78rem; font-weight: 700; border-radius: 8px; border: none; padding: 7px 10px; background: transparent; color: var(--text-muted);">
                    <i class="fas fa-camera me-1"></i> Kamera Scanner QR
                </button>
            </div>

            <!-- Banner Kartu RFID Belum Terhubung (Muncul jika tap RFID baru yang belum punya pemilik) -->
            <div id="scanUnlinkedRfidBanner"
                style="display: none; margin-bottom: 12px; padding: 10px 14px; border-radius: 10px; background: rgba(245, 158, 11, 0.12); border: 1.5px solid #f59e0b;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div
                        style="font-size: 0.8rem; font-weight: 700; color: #b45309; display: flex; align-items: center; gap: 6px;">
                        <i class="fas fa-microchip"></i>
                        <span>Kartu RFID Terdeteksi: <strong id="unlinkedRfidDisplay"
                                style="font-family: monospace; font-size: 0.9rem; color: #92400e;">-</strong></span>
                    </div>
                    <button type="button" onclick="cancelUnlinkedRfid()"
                        style="background: none; border: none; color: #b45309; font-size: 0.75rem; font-weight: 600; cursor: pointer; text-decoration: underline;">
                        Batal Pasangkan
                    </button>
                </div>
                <div style="font-size: 0.72rem; color: #78350f; margin-top: 3px;">
                    Scan QR kartu siswa atau cari nama/NISN di bawah untuk langsung menautkan RFID ini ke siswa.
                </div>
            </div>

            <!-- Card Identitas Siswa Terpilih (Muncul jika ada siswa yang dipilih/terdeteksi) -->
            <div id="scanIdentifiedCard"
                style="display: none; margin-bottom: 12px; padding: 12px 14px; border-radius: 12px; background: rgba(5, 150, 105, 0.08); border: 1.5px solid #059669;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 10px;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <img id="scanStudentPhoto" src="{{ asset('img/logo-dark.png') }}" alt="Foto Siswa"
                            style="width: 44px; height: 44px; border-radius: 50%; object-fit: cover; border: 2px solid #059669; flex-shrink: 0;"
                            onerror="this.src='/img/logo-dark.png'">
                        <div>
                            <div id="scanStudentNama"
                                style="font-weight: 800; font-size: 0.95rem; color: var(--text-color);">Nama Siswa</div>
                            <div style="font-size: 0.74rem; color: var(--text-muted); font-family: monospace;">
                                NISN: <span id="scanStudentNisn">-</span> &bull; <span id="scanStudentRombel">-</span>
                            </div>
                            <div id="scanStudentRfidStatus"
                                style="font-size: 0.7rem; color: #059669; font-weight: 700; margin-top: 2px;">
                                <i class="fas fa-id-card me-1"></i> RFID Terdaftar: <span
                                    id="scanStudentCurrentRfid">Belum Ada</span>
                            </div>
                        </div>
                    </div>
                    <button type="button" onclick="resetIdentifiedStudent()" class="btn btn-outline btn-sm"
                        style="padding: 3px 8px; font-size: 0.72rem; border-color: #94a3b8; color: var(--text-muted);"
                        title="Ganti siswa">
                        <i class="fas fa-times me-1"></i> Ganti
                    </button>
                </div>

                <div style="margin-top: 10px; padding-top: 10px; border-top: 1px dashed rgba(5, 150, 105, 0.3);">
                    <label for="inputRfidForStudent"
                        style="display: block; font-size: 0.76rem; font-weight: 700; color: #047857; margin-bottom: 4px;">
                        <i class="fas fa-satellite-dish me-1"></i> Tempelkan Kartu RFID untuk Siswa Ini:
                    </label>
                    <div style="display: flex; gap: 6px;">
                        <input type="text" id="inputRfidForStudent" class="form-control"
                            placeholder="Tempelkan kartu RFID pada reader USB..." autocomplete="off"
                            style="flex: 1; height: 36px; font-size: 0.82rem; font-weight: 700; font-family: monospace;">
                        <button type="button" onclick="submitBindStudentRfid()" class="btn btn-primary btn-sm"
                            style="background: #059669; border-color: #059669; font-weight: 700; font-size: 0.78rem; padding: 0 14px;">
                            <i class="fas fa-save me-1"></i> Simpan
                        </button>
                    </div>
                    <div style="display: flex; justify-content: flex-end; margin-top: 6px;">
                        <button type="button" onclick="submitCompleteWithoutRfid()" class="btn btn-link btn-sm"
                            style="font-size: 0.72rem; color: var(--text-muted); text-decoration: underline; padding: 0;">
                            Selesaikan Tanpa Registrasi RFID Baru
                        </button>
                    </div>
                </div>
            </div>

            <!-- MODE 1: Tap RFID Fisik / Barcode Scanner USB -->
            <div id="scanModeTapWrap">
                <div
                    style="background: var(--bg-hover); border: 1.5px dashed var(--border-color); border-radius: 12px; padding: 16px 14px; text-align: center; margin-bottom: 12px;">
                    <div
                        style="width: 48px; height: 48px; border-radius: 50%; background: rgba(5,150,105,0.12); color: #059669; display: inline-flex; align-items: center; justify-content: center; font-size: 1.5rem; margin-bottom: 6px;">
                        <i class="fas fa-satellite-dish"></i>
                    </div>
                    <div style="font-size: 0.86rem; font-weight: 800; color: var(--text-color); margin-bottom: 2px;">
                        Siap Membaca Tap Kartu RFID / Scanner USB
                    </div>
                    <div
                        style="font-size: 0.74rem; color: var(--text-muted); max-width: 380px; margin: 0 auto 10px auto; line-height: 1.4;">
                        Tempelkan kartu RFID atau scan barcode kartu. Data otomatis diproses &amp; langsung siap untuk
                        kartu berikutnya.
                    </div>

                    <form id="formScanRfidSelesai" onsubmit="event.preventDefault(); submitScanSelesai();"
                        style="max-width: 440px; margin: 0 auto;">
                        <div style="display: flex; gap: 8px;">
                            <div style="position: relative; flex: 1;">
                                <input type="text" id="inputScanCode" class="form-control"
                                    placeholder="Tempelkan kartu RFID atau masukkan NISN..." autocomplete="off"
                                    autofocus
                                    style="width: 100%; height: 38px; padding-left: 36px; font-size: 0.85rem; font-weight: 700; font-family: monospace;">
                                <i class="fas fa-id-badge"
                                    style="position: absolute; left: 12px; top: 12px; color: var(--text-muted); font-size: 0.9rem;"></i>
                            </div>
                            <button type="submit" id="btnProsesScan" class="btn btn-primary"
                                style="background: #059669; border-color: #059669; height: 38px; padding: 0 16px; font-weight: 700; font-size: 0.82rem; display: inline-flex; align-items: center; gap: 6px;">
                                <i class="fas fa-bolt"></i> <span>Proses</span>
                            </button>
                        </div>
                    </form>

                    <!-- Pencarian Siswa Cepat / Manual Autocomplete -->
                    <div style="position: relative; max-width: 440px; margin: 10px auto 0 auto; text-align: left;">
                        <div
                            style="font-size: 0.72rem; color: var(--text-muted); margin-bottom: 4px; display: flex; align-items: center; justify-content: space-between;">
                            <span><i class="fas fa-search me-1"></i> Atau cari siswa secara manual:</span>
                        </div>
                        <input type="text" id="scanSearchStudentInput" class="form-control"
                            placeholder="Ketik nama atau NISN siswa..." autocomplete="off"
                            style="width: 100%; height: 34px; font-size: 0.8rem; padding: 0 10px;">
                        <div id="scanStudentResultsList" class="kp-ac-list"
                            style="display: none; position: absolute; left: 0; right: 0; top: 100%; z-index: 100; max-height: 160px; overflow-y: auto; background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 8px; box-shadow: 0 6px 16px rgba(0,0,0,0.15);">
                        </div>
                    </div>
                </div>
            </div>

            <!-- MODE 2: Video Camera Box untuk QR Code Scanner -->
            <div id="scanModeCameraWrap" style="display: none;">
                <div
                    style="position: relative; width: 100%; height: 230px; background: #0f172a; border-radius: 12px; overflow: hidden; display: flex; align-items: center; justify-content: center; border: 1.5px solid var(--border-color); margin-bottom: 12px;">
                    <video id="scanQrVideo" playsinline
                        style="width: 100%; height: 100%; object-fit: cover; display: none;"></video>
                    <canvas id="scanQrCanvas" style="display: none;"></canvas>

                    <!-- Overlay Target Frame Scanner -->
                    <div id="scanQrOverlay"
                        style="position: absolute; inset: 0; display: none; pointer-events: none; align-items: center; justify-content: center;">
                        <div
                            style="width: 160px; height: 160px; border: 2.5px solid #10b981; border-radius: 12px; box-shadow: 0 0 0 9999px rgba(0,0,0,0.5); position: relative;">
                            <div
                                style="position: absolute; top: 0; left: 0; right: 0; height: 2px; background: #34d399; box-shadow: 0 0 8px #10b981; animation: qrLaser 1.8s infinite ease-in-out;">
                            </div>
                        </div>
                    </div>

                    <!-- Kamera Mati / State Placeholder -->
                    <div id="scanCameraPlaceholder" style="text-align: center; color: #94a3b8; padding: 14px;">
                        <i class="fas fa-camera"
                            style="font-size: 2.2rem; opacity: 0.5; margin-bottom: 8px; display: block;"></i>
                        <div style="font-size: 0.84rem; font-weight: 700; color: #e2e8f0; margin-bottom: 4px;">Kamera
                            Scanner QR Code</div>
                        <div style="font-size: 0.74rem; line-height: 1.4; max-width: 320px; margin: 0 auto 10px auto;">
                            Arahkan kamera ke QR Code di kartu pelajar digital untuk verifikasi otomatis &amp;
                            berkelanjutan.
                        </div>
                        <button type="button" id="btnToggleCameraScan" onclick="toggleCameraScanner()"
                            class="btn btn-outline btn-sm"
                            style="border-color: #38bdf8; color: #38bdf8; font-size: 0.78rem; font-weight: 700; padding: 5px 14px; border-radius: 8px;">
                            <i class="fas fa-video me-1"></i> Aktifkan Kamera Scanner
                        </button>
                    </div>
                </div>
            </div>

            <!-- Hasil Terakhir Scan / Live Feedback Flash Card -->
            <div id="scanResultBox"
                style="display: none; padding: 12px 14px; border-radius: 10px; background: rgba(16,185,129,0.1); border: 1.5px solid #10b981; margin-bottom: 12px; animation: kpResultFlash 0.35s ease;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 4px;">
                    <div style="font-size: 0.74rem; font-weight: 800; color: #10b981; text-transform: uppercase;">
                        <i class="fas fa-circle-check me-1"></i> Berhasil Diverifikasi &amp; Terdaftar
                    </div>
                    <span id="scanResultTime" style="font-size: 0.7rem; color: var(--text-muted);"></span>
                </div>
                <div id="scanResultNama" style="font-size: 0.95rem; font-weight: 800; color: var(--text-color);">
                </div>
                <div id="scanResultDetail" style="font-size: 0.76rem; color: var(--text-muted); margin-top: 2px;">
                </div>
            </div>

            <!-- Riwayat Verifikasi Sesi Ini -->
            <div id="scanSessionLogWrap" style="display: none;">
                <div
                    style="font-size: 0.74rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 6px; display: flex; align-items: center; justify-content: space-between;">
                    <span>Kartu Selesai Sesi Ini (<span id="scanSuccessCount">0</span>)</span>
                    <button type="button" onclick="clearSessionScanLog()"
                        style="background: none; border: none; font-size: 0.7rem; color: #ef4444; cursor: pointer; text-decoration: underline;">Bersihkan</button>
                </div>
                <div id="scanSessionLogList"
                    style="display: flex; flex-direction: column; gap: 4px; max-height: 120px; overflow-y: auto;">
                </div>
            </div>

        </div>

        <div class="kp-modal-footer"
            style="padding: 10px 20px 14px; background: var(--bg-hover); display: flex; justify-content: space-between; align-items: center; flex-shrink: 0;">
            <span style="font-size: 0.74rem; color: var(--text-muted);">
                <i class="fas fa-circle-check text-success me-1"></i> Presensi gerbang RFID langsung aktif setelah
                diproses.
            </span>
            <button type="button" onclick="closeModalScanRfidSelesai()" class="btn btn-outline btn-sm"
                style="padding: 6px 14px; font-size: 0.82rem;">Tutup</button>
        </div>
    </div>
</div>

<style>
    @keyframes qrLaser {
        0% {
            top: 0%;
            opacity: 0.8;
        }

        50% {
            top: 98%;
            opacity: 1;
        }

        100% {
            top: 0%;
            opacity: 0.8;
        }
    }

    @keyframes kpResultFlash {
        0% {
            transform: scale(0.97);
            opacity: 0.6;
        }

        50% {
            transform: scale(1.02);
        }

        100% {
            transform: scale(1);
            opacity: 1;
        }
    }

    .kp-scan-pulse-dot {
        display: inline-block;
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: #ffffff;
        animation: kpDotPulse 1.4s infinite ease-in-out;
    }

    @keyframes kpDotPulse {

        0%,
        100% {
            opacity: 1;
            transform: scale(1);
        }

        50% {
            opacity: 0.3;
            transform: scale(0.7);
        }
    }
</style>
style="padding: 10px 20px 14px; background: var(--bg-hover); display: flex; justify-content: space-between; align-items: center; flex-shrink: 0;">
<span style="font-size: 0.74rem; color: var(--text-muted);">
    <i class="fas fa-circle-check text-success me-1"></i> Presensi gerbang RFID otomatis aktif.
</span>
<button type="button" onclick="closeModalScanRfidSelesai()" class="btn btn-outline btn-sm"
    style="padding: 6px 14px; font-size: 0.82rem;">Tutup</button>
</div>
</div>
</div>

<style>
    @keyframes qrLaser {
        0% {
            top: 0%;
            opacity: 0.8;
        }

        50% {
            top: 98%;
            opacity: 1;
        }

        100% {
            top: 0%;
            opacity: 0.8;
        }
    }
</style>
