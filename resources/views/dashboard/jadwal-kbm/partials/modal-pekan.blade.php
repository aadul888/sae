    <!-- Modal Atur Pekan Jadwal (Standard SAE — z-index 99999) -->
    <div id="modalPekanJadwal" class="sae-modal"
        style="display: none; position: fixed; inset: 0; z-index: 99999 !important; background: rgba(0,0,0,0.55); align-items: center; justify-content: center; backdrop-filter: blur(3px);">
        <div class="card"
            style="width: 100%; max-width: 520px; max-height: 90vh; overflow-y: auto; margin: 16px; padding: 24px; border-radius: var(--radius-lg); box-shadow: 0 20px 25px -5px rgba(0,0,0,0.3);">
            <div
                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; border-bottom: 1px solid var(--border-color); padding-bottom: 12px;">
                <h3 style="margin: 0; font-size: var(--text-xl); font-weight: 800; color: var(--text-color);">
                    <i class="fas fa-calendar-week text-primary me-2"></i>
                    Atur <span id="pekanNomorLabel">Pekan</span>
                </h3>
                <button type="button" id="btnTutupModalPekan"
                    style="background: transparent; border: none; font-size: var(--text-xl); color: var(--text-muted); cursor: pointer;"
                    aria-label="Tutup">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form id="formPekanJadwal">
                @csrf
                <input type="hidden" id="pekanId" value="">
                <input type="hidden" id="pekanUrl" value="">

                <div id="pekanFormError"
                    style="display: none; padding: 10px 12px; border-radius: var(--radius-md); margin-bottom: 14px; background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.3); color: var(--danger); font-size: var(--text-base);">
                </div>

                <div class="form-group" style="margin-bottom: 14px;">
                    <label for="pekanTanggalMulai"
                        style="display: block; font-size: var(--text-base); font-weight: 700; margin-bottom: 6px; color: var(--text-color);">
                        Tanggal Mulai <span style="color: var(--danger);">*</span>
                    </label>
                    <input type="date" id="pekanTanggalMulai" class="form-control" required
                        style="width: 100%; padding: 9px 12px; border: 1px solid var(--border-color); border-radius: var(--radius-sm); background: var(--bg-card); color: var(--text-color);">
                </div>

                <div class="form-group" style="margin-bottom: 14px;">
                    <label for="pekanTanggalSelesai"
                        style="display: block; font-size: var(--text-base); font-weight: 700; margin-bottom: 6px; color: var(--text-color);">
                        Tanggal Selesai <span style="color: var(--danger);">*</span>
                    </label>
                    <input type="date" id="pekanTanggalSelesai" class="form-control" required
                        style="width: 100%; padding: 9px 12px; border: 1px solid var(--border-color); border-radius: var(--radius-sm); background: var(--bg-card); color: var(--text-color);">
                    <small style="color: var(--text-muted); font-size: var(--text-sm); margin-top: 4px; display: block;">
                        Default Senin–Sabtu. Koreksi manual bila sekolah memakai pekan jeda/asesmen.
                    </small>
                </div>

                <div class="form-group" style="margin-bottom: 14px;">
                    <label for="pekanStatus"
                        style="display: block; font-size: var(--text-base); font-weight: 700; margin-bottom: 6px; color: var(--text-color);">
                        Status Pekan <span style="color: var(--danger);">*</span>
                    </label>
                    <select id="pekanStatus" class="form-control" required
                        style="width: 100%; padding: 9px 12px; border: 1px solid var(--border-color); border-radius: var(--radius-sm); background: var(--bg-card); color: var(--text-color);">
                        @foreach (\App\Models\JadwalKbmPekan::STATUS_LABELS as $sKey => $sLabel)
                            <option value="{{ $sKey }}">{{ $sLabel }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group" style="margin-bottom: 14px;">
                    <label for="pekanSumber"
                        style="display: block; font-size: var(--text-base); font-weight: 700; margin-bottom: 6px; color: var(--text-color);">
                        Sumber Jadwal Pekan Ini
                    </label>
                    <select id="pekanSumber" class="form-control"
                        style="width: 100%; padding: 9px 12px; border: 1px solid var(--border-color); border-radius: var(--radius-sm); background: var(--bg-card); color: var(--text-color);">
                        <option value="">Ikut pengaturan global</option>
                        <option value="otomatis">Otomatis (Generate)</option>
                        <option value="manual">Manual (Wali Kelas)</option>
                    </select>
                </div>

                <div class="form-group" style="margin-bottom: 20px;">
                    <label for="pekanCatatan"
                        style="display: block; font-size: var(--text-base); font-weight: 700; margin-bottom: 6px; color: var(--text-color);">
                        Catatan
                    </label>
                    <textarea id="pekanCatatan" rows="3" maxlength="500" class="form-control"
                        placeholder="Misal: pekan asesmen tengah semester"
                        style="width: 100%; padding: 9px 12px; border: 1px solid var(--border-color); border-radius: var(--radius-sm); background: var(--bg-card); color: var(--text-color); resize: vertical;"></textarea>
                </div>

                <div style="display: flex; gap: 10px; justify-content: flex-end; flex-wrap: wrap;">
                    <button type="button" class="btn btn-outline" id="btnBatalPekan" style="padding: 9px 18px;">
                        Batal
                    </button>
                    <button type="submit" class="btn btn-primary" id="btnSimpanPekan" style="padding: 9px 18px;">
                        <i class="fas fa-save me-1"></i> Simpan Pekan
                    </button>
                </div>
            </form>
        </div>
    </div>
