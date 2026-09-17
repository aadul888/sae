<!-- Modal Daftar Aplikasi & Layanan SAE -->
<div id="modalDaftarAplikasi" class="modal-backdrop modal-layanan-backdrop"
    style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0, 0, 0, 0.75); z-index: 999999 !important; align-items: center; justify-content: center; backdrop-filter: blur(8px); padding: 16px; overflow-y: auto; box-sizing: border-box;">
    <div class="modal-card modal-layanan-card"
        style="background: var(--bg-card, #121a2b); border: 1px solid var(--border-glass, rgba(255,255,255,0.1)); border-radius: 20px; width: 100%; max-width: 840px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.6); overflow: hidden; display: flex; flex-direction: column; max-height: 90vh; margin: auto; animation: modalPop 0.25s ease-out;">

        <!-- Modal Header -->
        <div
            style="padding: 20px 24px; border-bottom: 1px solid var(--border-color, rgba(255,255,255,0.08)); display: flex; justify-content: space-between; align-items: center; background: rgba(255,255,255,0.02);">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div
                    style="width: 42px; height: 42px; border-radius: 12px; background: linear-gradient(135deg, rgba(59,130,246,0.2), rgba(6,182,212,0.2)); border: 1px solid rgba(59,130,246,0.3); display: flex; align-items: center; justify-content: center; color: var(--primary); font-size: 1.2rem;">
                    <i class="fas fa-cubes"></i>
                </div>
                <div>
                    <h3 style="font-size: 1.15rem; font-weight: 800; color: var(--text-color); margin: 0;">
                        Ekosistem Aplikasi &amp; Layanan SAE
                    </h3>
                    <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 2px;">
                        Pilih aplikasi atau modul digital yang ingin Anda akses
                    </div>
                </div>
            </div>
            <button type="button" onclick="closeLayananModal()" class="btn-close-modal" aria-label="Tutup"
                style="background: rgba(255,255,255,0.06); border: 1px solid var(--border-color); color: var(--text-muted); width: 34px; height: 34px; border-radius: 10px; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s ease;">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- Modal Body (Grid of Applications) -->
        <div style="padding: 22px 24px; overflow-y: auto; flex: 1;">
            <div class="modal-layanan-grid"
                style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 14px;">

                <!-- 1. Presensi Live Scanner (Utama) -->
                <a href="{{ route('presensi.scan') }}" class="app-item-card"
                    style="text-decoration: none; display: flex; flex-direction: column; justify-content: space-between; padding: 18px; border-radius: 16px; background: linear-gradient(135deg, rgba(16,185,129,0.14), rgba(6,182,212,0.08)); border: 1px solid rgba(16,185,129,0.38); box-shadow: 0 4px 15px rgba(16,185,129,0.12); transition: all 0.2s ease;">
                    <div>
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px;">
                            <div
                                style="width: 44px; height: 44px; border-radius: 12px; background: #10b981; color: #ffffff; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; box-shadow: 0 4px 12px rgba(16,185,129,0.45);">
                                <i class="fas fa-qrcode"></i>
                            </div>
                            <span class="badge" style="font-size: 0.7rem; font-weight: 700; background: rgba(16,185,129,0.22); color: #34d399; border: 1px solid rgba(16,185,129,0.45); padding: 3px 8px;">Presensi Live</span>
                        </div>
                        <div style="font-weight: 800; font-size: 1rem; color: var(--text-color); margin-bottom: 4px;">
                            Terminal Scanner Presensi
                        </div>
                        <div style="font-size: 0.8rem; color: var(--text-muted); line-height: 1.45;">
                            Pemindaian kehadiran kartu RFID dan visual webcam live untuk peserta didik dan GTK.
                        </div>
                    </div>
                    <div style="margin-top: 16px; display: flex; align-items: center; justify-content: space-between; font-size: 0.8rem; font-weight: 800; color: #10b981;">
                        <span>Buka Terminal Presensi</span>
                        <i class="fas fa-arrow-right"></i>
                    </div>
                </a>

                <!-- 2. Pengecekan Data NISN -->
                <a href="{{ url('/') }}#nisn" onclick="closeLayananModal()" class="app-item-card"
                    style="text-decoration: none; display: flex; flex-direction: column; justify-content: space-between; padding: 16px; border-radius: 14px; background: rgba(255,255,255,0.03); border: 1px solid var(--border-color); transition: all 0.2s ease;">
                    <div>
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px;">
                            <div
                                style="width: 40px; height: 40px; border-radius: 10px; background: rgba(6,182,212,0.15); border: 1px solid rgba(6,182,212,0.3); display: flex; align-items: center; justify-content: center; color: var(--accent); font-size: 1.1rem;">
                                <i class="fas fa-id-card-clip"></i>
                            </div>
                            <span class="badge" style="font-size: 0.68rem; background: rgba(6,182,212,0.15); color: var(--accent); border: 1px solid rgba(6,182,212,0.3);">Publik</span>
                        </div>
                        <div style="font-weight: 700; font-size: 0.95rem; color: var(--text-color); margin-bottom: 4px;">
                            Pengecekan Data NISN
                        </div>
                        <div style="font-size: 0.78rem; color: var(--text-muted); line-height: 1.4;">
                            Verifikasi status keaktifan dan validitas data peserta didik secara online.
                        </div>
                    </div>
                    <div style="margin-top: 14px; display: flex; align-items: center; justify-content: space-between; font-size: 0.76rem; font-weight: 700; color: var(--accent);">
                        <span>Cek Sekarang</span>
                        <i class="fas fa-arrow-right"></i>
                    </div>
                </a>

                <!-- 3. Statistik & Demografi -->
                <a href="{{ url('/') }}#statistik" onclick="closeLayananModal()" class="app-item-card"
                    style="text-decoration: none; display: flex; flex-direction: column; justify-content: space-between; padding: 16px; border-radius: 14px; background: rgba(255,255,255,0.03); border: 1px solid var(--border-color); transition: all 0.2s ease;">
                    <div>
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px;">
                            <div
                                style="width: 40px; height: 40px; border-radius: 10px; background: rgba(245,158,11,0.15); border: 1px solid rgba(245,158,11,0.3); display: flex; align-items: center; justify-content: center; color: var(--warning); font-size: 1.1rem;">
                                <i class="fas fa-chart-pie"></i>
                            </div>
                            <span class="badge" style="font-size: 0.68rem; background: rgba(245,158,11,0.15); color: var(--warning); border: 1px solid rgba(245,158,11,0.3);">Publik</span>
                        </div>
                        <div style="font-weight: 700; font-size: 0.95rem; color: var(--text-color); margin-bottom: 4px;">
                            Statistik &amp; Demografi
                        </div>
                        <div style="font-size: 0.78rem; color: var(--text-muted); line-height: 1.4;">
                            Visualisasi data perbandingan jurusan, sebaran tingkat kelas, dan gender siswa.
                        </div>
                    </div>
                    <div style="margin-top: 14px; display: flex; align-items: center; justify-content: space-between; font-size: 0.76rem; font-weight: 700; color: var(--warning);">
                        <span>Lihat Grafik</span>
                        <i class="fas fa-arrow-right"></i>
                    </div>
                </a>

                <!-- 4. Kartu Pelajar Digital -->
                <a href="{{ url('/') }}#fitur" onclick="closeLayananModal()" class="app-item-card"
                    style="text-decoration: none; display: flex; flex-direction: column; justify-content: space-between; padding: 16px; border-radius: 14px; background: rgba(255,255,255,0.03); border: 1px solid var(--border-color); transition: all 0.2s ease;">
                    <div>
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px;">
                            <div
                                style="width: 40px; height: 40px; border-radius: 10px; background: rgba(139,92,246,0.15); border: 1px solid rgba(139,92,246,0.3); display: flex; align-items: center; justify-content: center; color: var(--purple); font-size: 1.1rem;">
                                <i class="fas fa-id-badge"></i>
                            </div>
                            <span class="badge" style="font-size: 0.68rem; background: rgba(139,92,246,0.15); color: var(--purple); border: 1px solid rgba(139,92,246,0.3);">Ekosistem</span>
                        </div>
                        <div style="font-weight: 700; font-size: 0.95rem; color: var(--text-color); margin-bottom: 4px;">
                            Kartu Pelajar Digital
                        </div>
                        <div style="font-size: 0.78rem; color: var(--text-muted); line-height: 1.4;">
                            Layanan verifikasi QR-Code kartu identitas siswa dengan standar keamanan tinggi.
                        </div>
                    </div>
                    <div style="margin-top: 14px; display: flex; align-items: center; justify-content: space-between; font-size: 0.76rem; font-weight: 700; color: var(--purple);">
                        <span>Pelajari Fitur</span>
                        <i class="fas fa-arrow-right"></i>
                    </div>
                </a>

                <!-- 5. Kelulusan & Legalisir SKL -->
                <a href="{{ url('/') }}#fitur" onclick="closeLayananModal()" class="app-item-card"
                    style="text-decoration: none; display: flex; flex-direction: column; justify-content: space-between; padding: 16px; border-radius: 14px; background: rgba(255,255,255,0.03); border: 1px solid var(--border-color); transition: all 0.2s ease;">
                    <div>
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px;">
                            <div
                                style="width: 40px; height: 40px; border-radius: 10px; background: rgba(59,130,246,0.15); border: 1px solid rgba(59,130,246,0.3); display: flex; align-items: center; justify-content: center; color: var(--primary); font-size: 1.1rem;">
                                <i class="fas fa-award"></i>
                            </div>
                            <span class="badge" style="font-size: 0.68rem; background: rgba(59,130,246,0.15); color: var(--primary); border: 1px solid rgba(59,130,246,0.3);">Ekosistem</span>
                        </div>
                        <div style="font-weight: 700; font-size: 0.95rem; color: var(--text-color); margin-bottom: 4px;">
                            Kelulusan &amp; Legalisir SKL
                        </div>
                        <div style="font-size: 0.78rem; color: var(--text-muted); line-height: 1.4;">
                            Pengumuman kelulusan digital, unduh SKL terenkripsi, dan validasi dokumen resmi.
                        </div>
                    </div>
                    <div style="margin-top: 14px; display: flex; align-items: center; justify-content: space-between; font-size: 0.76rem; font-weight: 700; color: var(--primary);">
                        <span>Pelajari Fitur</span>
                        <i class="fas fa-arrow-right"></i>
                    </div>
                </a>

                <!-- 6. Portal Terpadu Masuk Sistem -->
                <a href="{{ route('login') }}" class="app-item-card"
                    style="text-decoration: none; display: flex; flex-direction: column; justify-content: space-between; padding: 16px; border-radius: 14px; background: linear-gradient(135deg, rgba(37,99,235,0.15), rgba(6,182,212,0.1)); border: 1px solid rgba(37,99,235,0.35); transition: all 0.2s ease;">
                    <div>
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px;">
                            <div
                                style="width: 40px; height: 40px; border-radius: 10px; background: var(--primary); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; box-shadow: 0 4px 12px rgba(37,99,235,0.4);">
                                <i class="fas fa-right-to-bracket"></i>
                            </div>
                            <span class="badge" style="font-size: 0.68rem; background: var(--primary); color: #fff;">Portal Utama</span>
                        </div>
                        <div style="font-weight: 700; font-size: 0.95rem; color: var(--text-color); margin-bottom: 4px;">
                            Portal Masuk Pengguna
                        </div>
                        <div style="font-size: 0.78rem; color: var(--text-muted); line-height: 1.4;">
                            Login multi-role terpadu untuk Guru, Tenaga Kependidikan, Peserta Didik, dan Administrator.
                        </div>
                    </div>
                    <div style="margin-top: 14px; display: flex; align-items: center; justify-content: space-between; font-size: 0.76rem; font-weight: 700; color: var(--primary);">
                        <span>Masuk Portal</span>
                        <i class="fas fa-arrow-right"></i>
                    </div>
                </a>

            </div>
        </div>

        <!-- Modal Footer -->
        <div
            style="padding: 14px 24px; border-top: 1px solid var(--border-color, rgba(255,255,255,0.08)); display: flex; justify-content: space-between; align-items: center; background: rgba(255,255,255,0.01);">
            <div style="font-size: 0.76rem; color: var(--text-muted); display: flex; align-items: center; gap: 6px;">
                <i class="fas fa-shield-halved text-primary"></i>
                <span>Sistem Aplikasi Edukasi terintegrasi Dapodik</span>
            </div>
            <button type="button" onclick="closeLayananModal()" class="btn btn-outline"
                style="padding: 6px 16px; font-size: 0.8rem; border-radius: 8px;">
                Tutup
            </button>
        </div>
    </div>
</div>
