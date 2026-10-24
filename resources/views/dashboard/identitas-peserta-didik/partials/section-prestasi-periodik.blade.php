                <!-- ========================================== -->
                <div class="card identitas-form-section" data-section="prestasi_minat"
                    style="margin-bottom: 24px; padding: 22px 24px; border-radius: 14px;">
                    <div
                        style="font-weight: 800; font-size: 1.05rem; color: #ea580c; margin-bottom: 14px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-trophy"></i> Bagian 6 &amp; 9: Prestasi serta Minat &amp; Bakat
                        </div>
                        <button type="button" class="btn btn-outline" id="btnAddPrestasi"
                            style="font-size: 0.78rem; padding: 5px 12px; border-radius: 6px;">
                            <i class="fas fa-plus me-1"></i> Tambah Prestasi
                        </button>
                    </div>

                    <div
                        style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px; margin-bottom: 20px;">
                        <div>
                            <label class="form-label" style="font-size: 0.82rem; font-weight: 700;">Hobi / Kegemaran
                                Siswa</label>
                            <select name="hobi_id" class="form-select" style="font-size: 0.85rem;">
                                @foreach ($ref['hobi'] as $hbId => $hbName)
                                    <option value="{{ $hbId }}"
                                        {{ old('hobi_id', $identitas->hobi_id) == $hbId ? 'selected' : '' }}>
                                        {{ $hbName }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="form-label" style="font-size: 0.82rem; font-weight: 700;">Cita-cita / Impian
                                Profesi</label>
                            <select name="cita_cita_id" class="form-select" style="font-size: 0.85rem;">
                                @foreach ($ref['cita_cita'] as $ctId => $ctName)
                                    <option value="{{ $ctId }}"
                                        {{ old('cita_cita_id', $identitas->cita_cita_id) == $ctId ? 'selected' : '' }}>
                                        {{ $ctName }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div style="overflow-x: auto;">
                        <table class="table mb-0" style="width: 100%; border-collapse: collapse; font-size: 0.84rem;">
                            <thead>
                                <tr style="background: var(--bg-hover); border-bottom: 1px solid var(--border-color);">
                                    <th
                                        style="padding: 10px 8px; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                        Bidang</th>
                                    <th
                                        style="padding: 10px 8px; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                        Tingkat</th>
                                    <th
                                        style="padding: 10px 8px; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                        Nama Kejuaraan / Lomba</th>
                                    <th
                                        style="padding: 10px 8px; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 90px;">
                                        Tahun</th>
                                    <th
                                        style="padding: 10px 8px; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                        Penyelenggara</th>
                                    <th
                                        style="padding: 10px 8px; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 100px;">
                                        Peringkat</th>
                                    <th
                                        style="padding: 10px 8px; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 45px;">
                                        Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="tbodyPrestasi">
                                @php
                                    $prestasiRows = old('riwayat_prestasi', $identitas->riwayat_prestasi ?? []);
                                @endphp
                                @if (!empty($prestasiRows) && is_array($prestasiRows))
                                    @foreach ($prestasiRows as $idx => $p)
                                        <tr class="row-prestasi" style="border-bottom: 1px solid var(--border-color);">
                                            <td style="padding: 10px 8px;">
                                                <select name="riwayat_prestasi[{{ $idx }}][jenis]"
                                                    class="form-select form-select-sm" style="font-size: 0.82rem;">
                                                    @foreach ($ref['jenis_prestasi'] as $jp)
                                                        <option value="{{ $jp }}"
                                                            {{ ($p['jenis'] ?? '') === $jp ? 'selected' : '' }}>
                                                            {{ $jp }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td style="padding: 10px 8px;">
                                                <select name="riwayat_prestasi[{{ $idx }}][tingkat]"
                                                    class="form-select form-select-sm" style="font-size: 0.82rem;">
                                                    @foreach ($ref['tingkat_prestasi'] as $tp)
                                                        <option value="{{ $tp }}"
                                                            {{ ($p['tingkat'] ?? '') === $tp ? 'selected' : '' }}>
                                                            {{ $tp }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td style="padding: 10px 8px;">
                                                <input type="text" name="riwayat_prestasi[{{ $idx }}][nama]"
                                                    class="form-control form-control-sm" placeholder="Nama Lomba"
                                                    value="{{ $p['nama'] ?? '' }}" style="font-size: 0.82rem;" required>
                                            </td>
                                            <td style="padding: 10px 8px; width: 90px;">
                                                <input type="number"
                                                    name="riwayat_prestasi[{{ $idx }}][tahun]"
                                                    class="form-control form-control-sm" placeholder="Tahun"
                                                    value="{{ $p['tahun'] ?? '' }}" style="font-size: 0.82rem;">
                                            </td>
                                            <td style="padding: 10px 8px;">
                                                <input type="text"
                                                    name="riwayat_prestasi[{{ $idx }}][penyelenggara]"
                                                    class="form-control form-control-sm"
                                                    placeholder="Instansi Penyelenggara"
                                                    value="{{ $p['penyelenggara'] ?? '' }}" style="font-size: 0.82rem;">
                                            </td>
                                            <td style="padding: 10px 8px; width: 100px;">
                                                <input type="text"
                                                    name="riwayat_prestasi[{{ $idx }}][peringkat]"
                                                    class="form-control form-control-sm" placeholder="Juara 1 / dll"
                                                    value="{{ $p['peringkat'] ?? '' }}" style="font-size: 0.82rem;">
                                            </td>
                                            <td style="padding: 10px 8px; text-align: center; width: 45px;">
                                                <button type="button" class="btn-icon text-danger btn-remove-row"
                                                    title="Hapus Baris">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                @endif
                                <tr id="emptyPrestasi"
                                    style="{{ !empty($prestasiRows) && count($prestasiRows) > 0 ? 'display: none;' : '' }}">
                                    <td colspan="7"
                                        style="padding: 24px; text-align: center; color: var(--text-muted); font-size: 0.82rem;">
                                        Belum ada catatan riwayat prestasi. Klik tombol <strong>+ Tambah Prestasi</strong>
                                        jika
                                        pernah menjuarai lomba atau kejuaraan.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- ========================================== -->
                <!-- SEKSI 10: DATA PERIODIK PESERTA DIDIK -->
                <!-- ========================================== -->
                <div class="card identitas-form-section" data-section="periodik"
                    style="margin-bottom: 24px; padding: 22px 24px; border-radius: 14px;">
                    <div
                        style="font-weight: 800; font-size: 1.05rem; color: #0f766e; margin-bottom: 18px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-ruler-combined"></i> Bagian 10: Data Periodik Peserta Didik
                        </div>
                        <span style="font-size: 0.75rem; color: var(--text-muted); font-weight: normal;">Kondisi fisik dan
                            jarak ke sekolah</span>
                    </div>

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px;">
                        <div>
                            <label class="form-label" style="font-size: 0.82rem; font-weight: 700;">Tinggi Badan (cm)
                                <span class="text-danger">*</span></label>
                            <input type="number" name="tinggi_badan" class="form-control" min="1"
                                max="250" required
                                value="{{ old('tinggi_badan', $identitas->tinggi_badan ?: $pd->tinggi_badan) }}">
                        </div>
                        <div>
                            <label class="form-label" style="font-size: 0.82rem; font-weight: 700;">Berat Badan (kg) <span
                                    class="text-danger">*</span></label>
                            <input type="number" name="berat_badan" class="form-control" min="1" max="300"
                                required value="{{ old('berat_badan', $identitas->berat_badan ?: $pd->berat_badan) }}">
                        </div>
                        <div>
                            <label class="form-label" style="font-size: 0.82rem; font-weight: 700;">Lingkar Kepala
                                (cm)</label>
                            <input type="number" name="lingkar_kepala" class="form-control" min="1"
                                max="150" value="{{ old('lingkar_kepala', $identitas->lingkar_kepala) }}">
                        </div>
                        <div>
                            <label class="form-label" style="font-size: 0.82rem; font-weight: 700;">Jarak Rumah ke Sekolah
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
                            <label class="form-label" style="font-size: 0.82rem; font-weight: 700;">Jarak Rumah
                                (km)</label>
                            <input type="number" name="jarak_rumah_sekolah_km" class="form-control" min="0"
                                max="999.99" step="0.01"
                                value="{{ old('jarak_rumah_sekolah_km', $identitas->jarak_rumah_sekolah_km) }}">
                        </div>
                        <div>
                            <label class="form-label" style="font-size: 0.82rem; font-weight: 700;">Waktu Tempuh
                                (Jam)</label>
                            <input type="number" name="waktu_tempuh_jam" class="form-control" min="0"
                                max="99" value="{{ old('waktu_tempuh_jam', $identitas->waktu_tempuh_jam) }}">
                        </div>
                        <div>
                            <label class="form-label" style="font-size: 0.82rem; font-weight: 700;">Waktu Tempuh
                                (Menit)</label>
                            <input type="number" name="waktu_tempuh_menit" class="form-control" min="0"
                                max="59" value="{{ old('waktu_tempuh_menit', $identitas->waktu_tempuh_menit) }}">
                        </div>
                        <div>
                            <label class="form-label" style="font-size: 0.82rem; font-weight: 700;">Jumlah Saudara Kandung
                                <span class="text-danger">*</span></label>
                            <input type="number" name="jumlah_saudara_kandung" class="form-control" min="0"
                                max="99" required
                                value="{{ old('jumlah_saudara_kandung', $identitas->jumlah_saudara_kandung) }}">
                        </div>
                    </div>
                </div>
            </fieldset>

