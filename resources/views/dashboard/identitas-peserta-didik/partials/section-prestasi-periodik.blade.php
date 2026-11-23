                <!-- ========================================== -->
                <!-- SEKSI: BAGIAN 9 - DATA PERIODIK PESERTA DIDIK -->
                <!-- ========================================== -->
                <div class="card identitas-form-section" data-section="periodik"
                    style="margin-bottom: 24px; padding: 22px 24px; border-radius: var(--radius-xl, 16px);">
                    <div
                        style="font-weight: 800; font-size: var(--text-lg, 1.125rem); color: #0f766e; margin-bottom: 18px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-ruler-combined"></i> Bagian 9: Data Periodik Peserta Didik
                        </div>
                        <span style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); font-weight: normal;">Kondisi fisik dan
                            jarak ke sekolah</span>
                    </div>

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px;">
                        <div>
                            <label class="form-label" style="font-size: var(--text-sm, 0.8125rem); font-weight: 700;">Tinggi Badan (cm)
                                <span class="text-danger">*</span></label>
                            <input type="number" name="tinggi_badan" class="form-control" min="1"
                                max="250" required
                                value="{{ old('tinggi_badan', $identitas->tinggi_badan ?: $pd->tinggi_badan) }}">
                        </div>
                        <div>
                            <label class="form-label" style="font-size: var(--text-sm, 0.8125rem); font-weight: 700;">Berat Badan (kg) <span
                                    class="text-danger">*</span></label>
                            <input type="number" name="berat_badan" class="form-control" min="1" max="300"
                                required value="{{ old('berat_badan', $identitas->berat_badan ?: $pd->berat_badan) }}">
                        </div>
                        <div>
                            <label class="form-label" style="font-size: var(--text-sm, 0.8125rem); font-weight: 700;">Lingkar Kepala
                                (cm)</label>
                            <input type="number" name="lingkar_kepala" class="form-control" min="1"
                                max="150" value="{{ old('lingkar_kepala', $identitas->lingkar_kepala) }}">
                        </div>
                        <div>
                            <label class="form-label" style="font-size: var(--text-sm, 0.8125rem); font-weight: 700;">Jarak Rumah ke Sekolah
                                <span class="text-danger">*</span></label>
                            <select name="jarak_rumah_sekolah" class="form-select" required>
                                <option value="kurang_dari_1_km"
                                    {{ old('jarak_rumah_sekolah', $identitas->jarak_rumah_sekolah) === 'kurang_dari_1_km' ? 'selected' : '' }}>
                                    Kurang dari 1 km</option>
                                <option value="lebih_dari_1_km"
                                    {{ old('jarak_rumah_sekolah', $identitas->jarak_rumah_sekolah) === 'lebih_dari_1_km' ? 'selected' : '' }}>
                                    Lebih dari 1 km</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label" style="font-size: var(--text-sm, 0.8125rem); font-weight: 700;">Jarak Rumah
                                (km)</label>
                            <input type="number" name="jarak_rumah_sekolah_km" class="form-control" min="0"
                                max="999.99" step="0.01"
                                value="{{ old('jarak_rumah_sekolah_km', $identitas->jarak_rumah_sekolah_km) }}">
                        </div>
                        <div>
                            <label class="form-label" style="font-size: var(--text-sm, 0.8125rem); font-weight: 700;">Waktu Tempuh
                                (Jam)</label>
                            <input type="number" name="waktu_tempuh_jam" class="form-control" min="0"
                                max="99" value="{{ old('waktu_tempuh_jam', $identitas->waktu_tempuh_jam) }}">
                        </div>
                        <div>
                            <label class="form-label" style="font-size: var(--text-sm, 0.8125rem); font-weight: 700;">Waktu Tempuh
                                (Menit)</label>
                            <input type="number" name="waktu_tempuh_menit" class="form-control" min="0"
                                max="59" value="{{ old('waktu_tempuh_menit', $identitas->waktu_tempuh_menit) }}">
                        </div>
                        <div>
                            <label class="form-label" style="font-size: var(--text-sm, 0.8125rem); font-weight: 700;">Jumlah Saudara Kandung
                                <span class="text-danger">*</span></label>
                            <input type="number" name="jumlah_saudara_kandung" class="form-control" min="0"
                                max="99" required
                                value="{{ old('jumlah_saudara_kandung', $identitas->jumlah_saudara_kandung) }}">
                        </div>
                    </div>
                </div>
            </fieldset>
