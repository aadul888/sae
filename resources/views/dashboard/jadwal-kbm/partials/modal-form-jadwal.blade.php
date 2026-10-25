    <!-- Modal Form Tambah / Edit Jadwal KBM (Standard SAE with z-index 99999) -->
    <div id="modalJadwalKbm" class="sae-modal"
        data-route-pembelajaran="{{ route('dashboard.jadwal-kbm.pembelajaran-by-rombel') }}"
        data-route-conflict="{{ route('dashboard.jadwal-kbm.check-conflict') }}"
        data-route-base="{{ route('dashboard.jadwal-kbm.index') }}" data-slots='@json($timeSlots)'
        style="display: none; position: fixed; inset: 0; z-index: 99999 !important; background: rgba(0,0,0,0.55); align-items: center; justify-content: center; backdrop-filter: blur(3px);">
        <div class="card"
            style="width: 100%; max-width: 580px; max-height: 90vh; overflow-y: auto; margin: 16px; padding: 24px; border-radius: var(--radius-lg, 12px); box-shadow: 0 20px 25px -5px rgba(0,0,0,0.3);">
            <div
                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 12px;">
                <h3 id="modalJadwalTitle"
                    style="margin: 0; font-size: var(--text-xl, 1.25rem); font-weight: 800; color: var(--text-color);">
                    <i class="fas fa-calendar-plus text-primary me-2"></i> Tambah Jadwal KBM
                </h3>
                <button type="button" id="btnTutupModalJadwal"
                    style="background: transparent; border: none; font-size: var(--text-xl, 1.25rem); color: var(--text-muted); cursor: pointer;">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form id="formJadwalKbm">
                @csrf
                <input type="hidden" id="jadwalId" name="id" value="">

                <!-- Feedback Validasi Anti-Bentrok -->
                <div id="conflictAlertBox"
                    style="display: none; padding: 12px; border-radius: var(--radius-md, 8px); margin-bottom: 16px; font-size: var(--text-base, 0.875rem);">
                </div>

                <div class="form-group" style="margin-bottom: 16px;">
                    <label for="modalRombelId"
                        style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 700; margin-bottom: 6px; color: var(--text-color);">
                        Rombongan Belajar (Kelas) <span style="color: #ef4444;">*</span>
                    </label>
                    <select id="modalRombelId" name="rombongan_belajar_id" class="form-control"
                        style="width: 100%; padding: 9px 12px; border: 1px solid var(--border-color); border-radius: var(--radius-sm, 6px); background: var(--bg-card); color: var(--text-color);"
                        required>
                        <option value="">-- Pilih Rombel --</option>
                        @foreach ($rombelList as $r)
                            <option value="{{ $r->rombongan_belajar_id }}">{{ $r->nama }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group" style="margin-bottom: 16px;">
                    <label for="modalPembelajaranId"
                        style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 700; margin-bottom: 6px; color: var(--text-color);">
                        Mata Pelajaran &amp; Guru Pengampu <span style="color: #ef4444;">*</span>
                    </label>
                    <select id="modalPembelajaranId" name="pembelajaran_id" class="form-control"
                        style="width: 100%; padding: 9px 12px; border: 1px solid var(--border-color); border-radius: var(--radius-sm, 6px); background: var(--bg-card); color: var(--text-color);"
                        required disabled>
                        <option value="">-- Pilih Rombel Terlebih Dahulu --</option>
                    </select>
                    <small id="pembelajaranHint"
                        style="color: var(--text-muted); font-size: var(--text-sm, 0.8125rem); margin-top: 4px; display: block;">
                        Daftar mapel dan alokasi jam mengajar disinkronkan langsung dari data Pembelajaran Dapodik.
                    </small>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 16px;">
                    <div class="form-group">
                        <label for="modalHari"
                            style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 700; margin-bottom: 6px; color: var(--text-color);">
                            Hari <span style="color: #ef4444;">*</span>
                        </label>
                        <select id="modalHari" name="hari" class="form-control"
                            style="width: 100%; padding: 9px 12px; border: 1px solid var(--border-color); border-radius: var(--radius-sm, 6px); background: var(--bg-card); color: var(--text-color);"
                            required>
                            @foreach (\App\Models\JadwalKbm::HARI_LIST as $h)
                                <option value="{{ $h }}">{{ $h }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="modalRuangan"
                            style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 700; margin-bottom: 6px; color: var(--text-color);">
                            Ruangan / Lab (Opsional)
                        </label>
                        <input type="text" id="modalRuangan" name="ruangan" class="form-control"
                            placeholder="Contoh: Lab RPL 1, R. 12"
                            style="width: 100%; padding: 9px 12px; border: 1px solid var(--border-color); border-radius: var(--radius-sm, 6px); background: var(--bg-card); color: var(--text-color);">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 16px;">
                    <div class="form-group">
                        <label for="modalJamKeMulai"
                            style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 700; margin-bottom: 6px; color: var(--text-color);">
                            Jam Pelajaran Mulai (JP Ke-) <span style="color: #ef4444;">*</span>
                        </label>
                        <input type="number" id="modalJamKeMulai" name="jam_ke_mulai" min="1" max="20"
                            value="1" class="form-control"
                            style="width: 100%; padding: 9px 12px; border: 1px solid var(--border-color); border-radius: var(--radius-sm, 6px); background: var(--bg-card); color: var(--text-color);"
                            required>
                    </div>

                    <div class="form-group">
                        <label for="modalJamKeSelesai"
                            style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 700; margin-bottom: 6px; color: var(--text-color);">
                            Jam Pelajaran Selesai (JP Ke-) <span style="color: #ef4444;">*</span>
                        </label>
                        <input type="number" id="modalJamKeSelesai" name="jam_ke_selesai" min="1"
                            max="20" value="2" class="form-control"
                            style="width: 100%; padding: 9px 12px; border: 1px solid var(--border-color); border-radius: var(--radius-sm, 6px); background: var(--bg-card); color: var(--text-color);"
                            required>
                    </div>
                </div>

                <!-- Durasi JP Quick Helper Buttons -->
                <div style="margin-bottom: 16px; padding: 10px; background: var(--bg-hover); border-radius: var(--radius-sm, 6px);">
                    <span
                        style="font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); display: block; margin-bottom: 6px;">
                        Pilih Cepat Durasi JP:
                    </span>
                    <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                        @foreach ([1 => '1 JP', 2 => '2 JP', 3 => '3 JP', 4 => '4 JP', 5 => '5 JP', 6 => '6 JP', 8 => '8 JP', 9 => '9 JP (Maks)'] as $durVal => $durText)
                            <button type="button" class="btn btn-outline btn-quick-durasi"
                                data-durasi="{{ $durVal }}"
                                style="padding: 3px 8px; font-size: var(--text-xs, 0.75rem); border-radius: var(--radius-xs, 4px);">
                                {{ $durText }}
                            </button>
                        @endforeach
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 16px;">
                    <div class="form-group">
                        <label for="modalJamMulai"
                            style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 700; margin-bottom: 6px; color: var(--text-color);">
                            Jam Waktu Mulai <span style="color: #ef4444;">*</span>
                        </label>
                        <input type="time" id="modalJamMulai" name="jam_mulai" value="07:15" class="form-control"
                            style="width: 100%; padding: 9px 12px; border: 1px solid var(--border-color); border-radius: var(--radius-sm, 6px); background: var(--bg-card); color: var(--text-color);"
                            required>
                    </div>

                    <div class="form-group">
                        <label for="modalJamSelesai"
                            style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 700; margin-bottom: 6px; color: var(--text-color);">
                            Jam Waktu Selesai <span style="color: #ef4444;">*</span>
                        </label>
                        <input type="time" id="modalJamSelesai" name="jam_selesai" value="08:45"
                            class="form-control"
                            style="width: 100%; padding: 9px 12px; border: 1px solid var(--border-color); border-radius: var(--radius-sm, 6px); background: var(--bg-card); color: var(--text-color);"
                            required>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 20px;">
                    <label for="modalKeterangan"
                        style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 700; margin-bottom: 6px; color: var(--text-color);">
                        Keterangan Tambahan (Opsional)
                    </label>
                    <textarea id="modalKeterangan" name="keterangan" rows="2" class="form-control"
                        placeholder="Catatan jam KBM, materi khusus, dll."
                        style="width: 100%; padding: 9px 12px; border: 1px solid var(--border-color); border-radius: var(--radius-sm, 6px); background: var(--bg-card); color: var(--text-color);"></textarea>
                </div>

                <div
                    style="display: flex; justify-content: flex-end; gap: 10px; border-top: 1px solid var(--border-color); padding-top: 16px;">
                    <button type="button" id="btnBatalJadwal" class="btn btn-outline"
                        style="padding: 9px 18px; font-size: var(--text-base, 0.875rem);">
                        Batal
                    </button>
                    <button type="submit" id="btnSimpanJadwal" class="btn btn-primary"
                        style="padding: 9px 20px; font-size: var(--text-base, 0.875rem);">
                        <i class="fas fa-save me-1"></i> Simpan Jadwal
                    </button>
                </div>
            </form>
        </div>
    </div>

