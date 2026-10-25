<!-- MODAL 8: Tap Kartu RFID Selesai Cetak & Registrasi Presensi -->
<div id="modalScanRfidSelesai" class="modal-backdrop"
    style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.72); backdrop-filter: blur(5px); z-index: 99999 !important; align-items: center; justify-content: center; padding: 16px;">
    <div class="card kp-modal-card"
        style="max-width: 540px; width: 100%; max-height: 92vh; background: var(--card-bg); border-radius: var(--radius-xl, 16px); overflow: hidden; box-shadow: 0 25px 60px rgba(0,0,0,0.5); display: flex; flex-direction: column;">

        <!-- Header -->
        <div class="kp-modal-header"
            style="background: linear-gradient(135deg, var(--success, #059669) 0%, #047857 100%); color: #ffffff; padding: 14px 20px; flex-shrink: 0; display: flex; justify-content: space-between; align-items: center;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <div
                    style="width: 36px; height: 36px; border-radius: var(--radius-lg, 12px); background: rgba(255,255,255,0.2); display: flex; align-items: center; justify-content: center; font-size: var(--text-xl, 1.25rem);">
                    <i class="fas fa-satellite-dish"></i>
                </div>
                <div>
                    <h4 class="kp-modal-title" style="color: #ffffff; font-size: var(--text-md, 1rem); font-weight: 800; margin: 0;">
                        Tap Kartu RFID Selesai Cetak
                    </h4>
                    <div style="font-size: var(--text-xs, 0.75rem); color: rgba(255,255,255,0.9); margin-top: 2px;">
                        Pilih siswa lalu tempelkan kartu RFID pada reader USB untuk aktivasi presensi.
                    </div>
                </div>
            </div>
            <button type="button" class="kp-modal-close" onclick="closeModalScanRfidSelesai()"
                style="color: #ffffff; opacity: 0.85; font-size: var(--text-2xl, 1.5rem); line-height: 1; border: none; background: transparent; cursor: pointer;"
                aria-label="Tutup">&times;</button>
        </div>

        <div class="kp-modal-body" style="padding: 16px 20px; overflow-y: auto; flex: 1;">

            <!-- STEP 1: PILIH SISWA -->
            <div id="scanSelectStudentStep" style="margin-bottom: 14px;">
                <label for="scanSearchStudentInput"
                    style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-color); margin-bottom: 5px;">
                    <i class="fas fa-barcode text-primary me-1"></i> Langkah 1: Scan Barcode / Cari Peserta Didik
                </label>
                <div style="position: relative;">
                    <input type="text" id="scanSearchStudentInput" class="form-control"
                        placeholder="Scan barcode/QR NISN atau ketik nama siswa..." autocomplete="off"
                        style="width: 100%; height: 38px; padding-left: 36px; font-size: var(--text-base, 0.875rem); font-weight: 600;">
                    <i class="fas fa-search"
                        style="position: absolute; left: 12px; top: 12px; color: var(--text-muted); font-size: var(--text-base, 0.875rem);"></i>
                    <div id="scanStudentResultsList" class="kp-ac-list"
                        style="display: none; position: absolute; left: 0; right: 0; top: 100%; z-index: 100; max-height: 180px; overflow-y: auto; background: var(--card-bg); border: 1px solid var(--border-color); border-radius: var(--radius-md, 8px); box-shadow: 0 8px 20px rgba(0,0,0,0.2);">
                    </div>
                </div>
                <div class="kp-form-hint" style="margin-top: 4px; font-size: var(--text-xs, 0.75rem);">
                    Scan barcode/QR NISN pada kartu untuk memilih langsung, atau ketik nama siswa.
                </div>
            </div>

            <!-- STEP 1B: KARTU SISWA TERPILIH (Muncul Saat Siswa Terpilih) -->
            <div id="scanIdentifiedCard"
                style="display: none; margin-bottom: 14px; padding: 12px 14px; border-radius: var(--radius-lg, 12px); background: rgba(5, 150, 105, 0.08); border: 1.5px solid var(--success, #059669);">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 10px;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <img id="scanStudentPhoto" src="{{ asset('img/logo-dark.png') }}" alt="Foto Siswa"
                            style="width: 44px; height: 44px; border-radius: 50%; object-fit: cover; border: 2px solid var(--success, #059669); flex-shrink: 0;"
                            onerror="this.src='/img/logo-dark.png'">
                        <div>
                            <div id="scanStudentNama"
                                style="font-weight: 800; font-size: var(--text-md, 1rem); color: var(--text-color);">Nama Siswa</div>
                            <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); font-family: monospace;">
                                NISN: <span id="scanStudentNisn">-</span> &bull; <span id="scanStudentRombel">-</span>
                            </div>
                            <div id="scanStudentRfidStatus"
                                style="font-size: var(--text-xs, 0.75rem); color: var(--success, #059669); font-weight: 700; margin-top: 2px;">
                                <i class="fas fa-id-card me-1"></i> RFID Terdaftar: <span
                                    id="scanStudentCurrentRfid">Belum Ada</span>
                            </div>
                        </div>
                    </div>
                    <button type="button" onclick="resetIdentifiedStudent()" class="btn btn-outline btn-sm"
                        style="padding: 3px 8px; font-size: var(--text-xs, 0.75rem); border-color: var(--text-muted, #94a3b8); color: var(--text-muted);"
                        title="Pilih siswa lain">
                        <i class="fas fa-times me-1"></i> Ganti
                    </button>
                </div>
            </div>

            <!-- STEP 2: KOTAK TAP KARTU RFID FISIK -->
            <div id="scanRfidTapBox"
                style="background: var(--bg-hover); border: 2px dashed var(--success, #059669); border-radius: var(--radius-xl, 16px); padding: 20px 16px; text-align: center; margin-bottom: 14px;">
                <div
                    style="width: 56px; height: 56px; border-radius: 50%; background: rgba(5,150,105,0.12); color: var(--success, #059669); display: inline-flex; align-items: center; justify-content: center; font-size: var(--text-2xl, 1.5rem); margin-bottom: 8px;">
                    <i class="fas fa-satellite-dish"></i>
                </div>
                <div style="font-size: var(--text-base, 0.875rem); font-weight: 800; color: var(--text-color); margin-bottom: 2px;">
                    Langkah 2: Tempelkan Kartu RFID pada Reader USB
                </div>
                <div
                    style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); max-width: 380px; margin: 0 auto 12px auto; line-height: 1.4;">
                    Dekatkan fisik kartu RFID ke alat pembaca USB. UID kartu otomatis terbaca, kartu ditandai selesai
                    cetak, dan langsung aktif di presensi.
                </div>

                <form id="formScanRfidSelesai" onsubmit="event.preventDefault(); submitScanSelesai();"
                    style="max-width: 440px; margin: 0 auto;">
                    <div style="display: flex; gap: 8px;">
                        <div style="position: relative; flex: 1;">
                            <input type="text" id="inputScanCode" class="form-control"
                                placeholder="Menunggu tap kartu RFID fisik..." autocomplete="off" autofocus
                                style="width: 100%; height: 40px; padding-left: 38px; font-size: var(--text-base, 0.875rem); font-weight: 800; font-family: monospace; letter-spacing: 0.5px; border-color: var(--success, #059669);">
                            <i class="fas fa-microchip"
                                style="position: absolute; left: 13px; top: 13px; color: var(--success, #059669); font-size: var(--text-md, 1rem);"></i>
                        </div>
                        <button type="submit" id="btnProsesScan" class="btn btn-primary"
                            style="background: var(--success, #059669); border-color: var(--success, #059669); height: 40px; padding: 0 18px; font-weight: 700; font-size: var(--text-sm, 0.8125rem); display: inline-flex; align-items: center; gap: 6px;">
                            <i class="fas fa-bolt"></i> <span>Kirim</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Hasil Terakhir Scan / Live Feedback Flash Card -->
            <div id="scanResultBox"
                style="display: none; padding: 12px 14px; border-radius: var(--radius-lg, 12px); background: rgba(16,185,129,0.1); border: 1.5px solid var(--success, #10b981); margin-bottom: 6px; animation: kpResultFlash 0.35s ease;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 4px;">
                    <div style="font-size: var(--text-xs, 0.75rem); font-weight: 800; color: var(--success, #10b981); text-transform: uppercase;">
                        <i class="fas fa-circle-check me-1"></i> Selesai Dicetak &amp; Terdaftar ke Presensi
                    </div>
                    <span id="scanResultTime" style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted);"></span>
                </div>
                <div id="scanResultNama" style="font-size: var(--text-md, 1rem); font-weight: 800; color: var(--text-color);">
                </div>
                <div id="scanResultDetail" style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); margin-top: 2px;">
                </div>
            </div>

        </div>

        <div class="kp-modal-footer"
            style="padding: 10px 20px 14px; background: var(--bg-hover); display: flex; justify-content: space-between; align-items: center; flex-shrink: 0;">
            <span style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted);">
                <i class="fas fa-shield-alt text-success me-1"></i> Data RFID otomatis masuk ke Manajemen Kartu RFID
                Presensi.
            </span>
            <button type="button" onclick="closeModalScanRfidSelesai()" class="btn btn-outline btn-sm"
                style="padding: 6px 14px; font-size: var(--text-sm, 0.8125rem);">Tutup</button>
        </div>
    </div>
</div>

<style>
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
</style>
