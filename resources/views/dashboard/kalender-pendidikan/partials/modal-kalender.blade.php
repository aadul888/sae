            <!-- Modal Form (Create / Edit) -->
            <div id="agendaModal" class="modal-backdrop" style="display: none; z-index: 99999 !important;">
                <div class="card"
                    style="max-width: 580px; width: 92%; margin: auto; padding: 24px; border-radius: var(--radius-xl, 16px); box-shadow: 0 16px 40px rgba(0,0,0,0.4); max-height: 90vh; overflow-y: auto;">
                    <div
                        style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; border-bottom: 1px solid var(--border-color); padding-bottom: 12px;">
                        <h3 id="modalTitle"
                            style="font-size: var(--text-lg, 1.125rem); font-weight: 700; color: var(--text-color); margin: 0;">
                            <i class="fas fa-calendar-plus text-primary me-2"></i> Tambah Agenda Kalender
                        </h3>
                        <button type="button" id="btnCloseModal"
                            style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: var(--text-lg, 1.125rem); padding: 4px;">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    <form id="agendaForm" action="{{ route('dashboard.kalender-pendidikan.store') }}" method="POST">
                        @csrf
                        <div id="methodField"></div>

                        <div class="form-group-compact" style="margin-bottom: 14px;">
                            <label>Nama Agenda / Kegiatan <span style="color: #ef4444;">*</span></label>
                            <input type="text" name="nama_kegiatan" id="inputNamaKegiatan" required
                                placeholder="Contoh: Pembelajaran Jarak Jauh (PJJ) / Ujian Asesmen / Libur Nasional"
                                maxlength="200">
                        </div>

                        <div class="form-group-compact" style="margin-bottom: 14px;">
                            <label>Kondisi / Mode Presensi <span style="color: #ef4444;">*</span></label>
                            <select name="mode_presensi" id="inputModePresensi" required
                                style="width: 100%; padding: 10px 12px; font-size: var(--text-base, 0.875rem);">
                                <option value="luring" selected>Luring (Presensi Efektif Sekolah — Scanner Terminal Aktif)
                                </option>
                                <option value="daring">Daring (Pembelajaran Jarak Jauh / Belajar Rumah — Scanner Terminal
                                    Non-Aktif)</option>
                                <option value="libur">Libur (Hari Libur Sekolah — Bebas Presensi)</option>
                            </select>
                            <small style="color: var(--text-muted); font-size: var(--text-xs, 0.75rem); display: block; margin-top: 4px;">
                                Menentukan aturan sistem presensi pada tanggal pelaksanaan agenda ini.
                            </small>
                        </div>

                        <div class="modal-form-grid"
                            style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                            <div class="form-group-compact">
                                <label>Kategori Agenda <span style="color: #ef4444;">*</span></label>
                                <select name="tipe" id="inputTipe" required>
                                    <option value="hari_efektif" selected>Hari Efektif Belajar</option>
                                    <option value="pembelajaran_daring">Pembelajaran Daring (PJJ)</option>
                                    <option value="kegiatan_sekolah">Kegiatan Sekolah / Upacara</option>
                                    <option value="ujian_asesmen">Ujian &amp; Asesmen</option>
                                    <option value="libur_nasional">Libur Nasional</option>
                                    <option value="libur_semester">Libur Semester / Akhir Tahun</option>
                                    <option value="libur_khusus">Libur Khusus Satpen</option>
                                </select>
                            </div>

                            <div class="form-group-compact">
                                <label>Warna Penanda</label>
                                <input type="color" name="warna" id="inputWarna" value="#06b6d4"
                                    style="height: 40px; padding: 4px; border-radius: var(--radius-md, 8px); cursor: pointer; width: 100%;">
                            </div>
                        </div>

                        <div class="modal-form-grid"
                            style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                            <div class="form-group-compact">
                                <label>Tanggal Mulai <span style="color: #ef4444;">*</span></label>
                                <input type="date" name="tanggal_mulai" id="inputTanggalMulai" required>
                            </div>

                            <div class="form-group-compact">
                                <label>Tanggal Selesai <span style="color: #ef4444;">*</span></label>
                                <input type="date" name="tanggal_selesai" id="inputTanggalSelesai" required>
                            </div>
                        </div>

                        <!-- Dampak Terhadap Presensi -->
                        <div
                            style="margin-bottom: 16px; padding: 12px; background: rgba(255,255,255,0.02); border: 1px solid var(--border-color); border-radius: var(--radius-lg, 12px);">
                            <div style="font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-color); margin-bottom: 6px;">
                                <i class="fas fa-fingerprint text-primary me-1"></i> Integrasi Dampak Libur Presensi
                            </div>
                            <p style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-bottom: 10px;">
                                Centang peran yang diliburkan pada tanggal ini agar sistem presensi tidak mencatat
                                ketidakhadiran
                                (alpha).
                            </p>

                            <div style="display: flex; flex-direction: column; gap: 8px;">
                                <label
                                    style="display: flex; align-items: center; gap: 8px; font-size: var(--text-sm, 0.8125rem); color: var(--text-color); cursor: pointer; margin: 0;">
                                    <input type="checkbox" name="libur_pd" id="inputLiburPd" value="1"
                                        style="width: 16px; height: 16px;">
                                    <span>Liburkan <strong>Peserta Didik</strong> (Peserta Didik Bebas Presensi)</span>
                                </label>
                                <label
                                    style="display: flex; align-items: center; gap: 8px; font-size: var(--text-sm, 0.8125rem); color: var(--text-color); cursor: pointer; margin: 0;">
                                    <input type="checkbox" name="libur_guru" id="inputLiburGuru" value="1"
                                        style="width: 16px; height: 16px;">
                                    <span>Liburkan <strong>Guru</strong> (Pendidik Bebas Presensi Mengajar)</span>
                                </label>
                                <label
                                    style="display: flex; align-items: center; gap: 8px; font-size: var(--text-sm, 0.8125rem); color: var(--text-color); cursor: pointer; margin: 0;">
                                    <input type="checkbox" name="libur_tendik" id="inputLiburTendik" value="1"
                                        style="width: 16px; height: 16px;">
                                    <span>Liburkan <strong>Tendik</strong> (Tenaga Kependidikan Bebas Presensi)</span>
                                </label>
                            </div>
                        </div>

                        <div class="form-group-compact" style="margin-bottom: 20px;">
                            <label>Keterangan / Catatan Tambahan</label>
                            <textarea name="keterangan" id="inputKeterangan" rows="3"
                                placeholder="Deskripsi kegiatan, instruksi panitia, atau surat edaran terkait..."
                                style="width: 100%; padding: 10px 12px; font-size: var(--text-base, 0.875rem); border-radius: var(--radius-md, 8px); border: 1px solid var(--border-color); background: var(--input-bg); color: var(--text-color); resize: vertical; box-sizing: border-box; line-height: 1.5;"></textarea>
                        </div>

                        <div
                            style="display: flex; justify-content: flex-end; gap: 10px; border-top: 1px solid var(--border-color); padding-top: 16px;">
                            <button type="button" id="btnCancelModal" class="btn btn-outline"
                                style="padding: 8px 16px; font-size: var(--text-base, 0.875rem);">Batal</button>
                            <button type="submit" class="btn btn-primary"
                                style="padding: 8px 20px; font-size: var(--text-base, 0.875rem); font-weight: 600;">Simpan Agenda</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Modal View Detail Agenda -->
            <div id="viewModal" class="modal-backdrop" style="display: none; z-index: 99999 !important;">
                <div class="card"
                    style="max-width: 520px; width: 92%; margin: auto; padding: 24px; border-radius: var(--radius-xl, 16px); box-shadow: 0 16px 40px rgba(0,0,0,0.4);">
                    <div
                        style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 14px; border-bottom: 1px solid var(--border-color); padding-bottom: 12px;">
                        <div>
                            <div style="display: flex; gap: 6px; align-items: center; margin-bottom: 6px;">
                                <span id="viewTipeBadge" class="badge" style="font-size: var(--text-xs, 0.75rem);"></span>
                                <span id="viewModeBadge"></span>
                            </div>
                            <h3 id="viewJudul"
                                style="font-size: var(--text-xl, 1.25rem); font-weight: 800; color: var(--text-color); margin: 6px 0 0 0; line-height: 1.3;">
                            </h3>
                        </div>
                        <button type="button" id="btnCloseViewModal"
                            style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: var(--text-xl, 1.25rem); padding: 4px;">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    <div style="display: flex; flex-direction: column; gap: 12px; margin-bottom: 16px; font-size: var(--text-base, 0.875rem);">
                        <div>
                            <span
                                style="color: var(--text-muted); display: block; font-size: var(--text-xs, 0.75rem); font-weight: 600;">RENTANG
                                TANGGAL:</span>
                            <span id="viewTanggal" style="font-weight: 700; color: var(--text-color);"></span>
                        </div>

                        <div>
                            <span
                                style="color: var(--text-muted); display: block; font-size: var(--text-xs, 0.75rem); font-weight: 600; margin-bottom: 4px;">STATUS
                                PRESENSI:</span>
                            <div id="viewDampakBadges" style="display: flex; gap: 8px; flex-wrap: wrap;"></div>
                        </div>

                        <div>
                            <span
                                style="color: var(--text-muted); display: block; font-size: var(--text-xs, 0.75rem); font-weight: 600;">KETERANGAN:</span>
                            <p id="viewKeterangan" style="margin: 4px 0 0 0; color: var(--text-color); line-height: 1.5;"></p>
                        </div>

                        <div
                            style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); border-top: 1px solid var(--border-color); padding-top: 10px;">
                            Dibuat oleh <strong id="viewPembuat"></strong> pada <span id="viewWaktu"></span>
                        </div>
                    </div>

                    <div style="display: flex; justify-content: flex-end;">
                        <button type="button" id="btnTutupView" class="btn btn-outline"
