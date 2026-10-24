                <!-- ========================================== -->
                <!-- SEKSI 1: DATA PRIBADI PESERTA DIDIK -->
                <!-- ========================================== -->
                <div class="card identitas-form-section" data-section="pribadi_alamat"
                    style="margin-bottom: 24px; padding: 22px 24px; border-radius: 14px;">
                    <div
                        style="font-weight: 800; font-size: 1.05rem; color: var(--primary); margin-bottom: 18px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-id-card"></i> Bagian 1: Data Pribadi Peserta Didik
                        </div>
                        <span style="font-size: 0.75rem; color: var(--text-muted); font-weight: normal;">Sesuai Akta
                            Kelahiran
                            &amp; Kartu Keluarga</span>
                    </div>

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 16px;">
                        <!-- Nama Lengkap -->
                        <div>
                            <label class="form-label" style="font-size: 0.82rem; font-weight: 700;">Nama Lengkap Siswa
                                <span class="text-danger">*</span></label>
                            <input type="text" name="nama" class="form-control"
                                value="{{ old('nama', $identitas->nama ?: $pd->nama) }}" required
                                style="font-size: 0.85rem;">
                        </div>

                        <!-- Jenis Kelamin -->
                        <div>
                            <label class="form-label" style="font-size: 0.82rem; font-weight: 700;">Jenis Kelamin <span
                                    class="text-danger">*</span></label>
                            <div style="display: flex; gap: 18px; align-items: center; height: 38px;">
                                <label
                                    style="font-size: 0.85rem; display: flex; align-items: center; gap: 6px; cursor: pointer;">
                                    <input type="radio" name="jenis_kelamin" value="L"
                                        {{ old('jenis_kelamin', $identitas->jenis_kelamin ?: $pd->jenis_kelamin) === 'L' ? 'checked' : '' }}>
                                    Laki-laki
                                </label>
                                <label
                                    style="font-size: 0.85rem; display: flex; align-items: center; gap: 6px; cursor: pointer;">
                                    <input type="radio" name="jenis_kelamin" value="P"
                                        {{ old('jenis_kelamin', $identitas->jenis_kelamin ?: $pd->jenis_kelamin) === 'P' ? 'checked' : '' }}>
                                    Perempuan
                                </label>
                            </div>
                        </div>

                        <!-- NISN (Permanen - Readonly) -->
                        <div>
                            <div
                                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                                <label class="form-label"
                                    style="font-size: 0.82rem; font-weight: 700; margin: 0;">NISN</label>
                                <span class="badge"
                                    style="background: rgba(148,163,184,0.15); color: var(--text-muted); font-size: 0.68rem; padding: 2px 6px;">
                                    <i class="fas fa-lock me-1"></i>Permanen Dapodik
                                </span>
                            </div>
                            <input type="text" value="{{ $identitas->nisn ?: $pd->nisn }}" class="form-control"
                                readonly disabled
                                style="font-size: 0.85rem; background: var(--bg-hover); color: var(--text-muted); font-family: monospace;"
                                title="Nomor Induk Siswa Nasional bersifat unik & permanen dari Pusdatin Kemdikbud.">
                        </div>

                        <!-- NIPD (Permanen - Readonly) -->
                        <div>
                            <div
                                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                                <label class="form-label" style="font-size: 0.82rem; font-weight: 700; margin: 0;">Nomor
                                    Induk
                                    Siswa (NIPD)</label>
                                <span class="badge"
                                    style="background: rgba(148,163,184,0.15); color: var(--text-muted); font-size: 0.68rem; padding: 2px 6px;">
                                    <i class="fas fa-lock me-1"></i>Permanen Sekolah
                                </span>
                            </div>
                            <input type="text" value="{{ $identitas->nipd ?: ($pd->nipd ?: '-') }}"
                                class="form-control" readonly disabled
                                style="font-size: 0.85rem; background: var(--bg-hover); color: var(--text-muted); font-family: monospace;"
                                title="Nomor Induk Peserta Didik lokal sekolah bersifat permanen.">
                        </div>

                        <!-- NIK Siswa -->
                        <div>
                            <label class="form-label" style="font-size: 0.82rem; font-weight: 700;">NIK Siswa (16 Digit)
                                <span class="text-danger">*</span></label>
                            <input type="text" name="nik" maxlength="16" class="form-control"
                                value="{{ old('nik', $identitas->nik ?: $pd->nik) }}" placeholder="3203xxxxxxxxxxxx"
                                style="font-size: 0.85rem; font-family: monospace;">
                        </div>

                        <!-- No. Kartu Keluarga (KK) -->
                        <div>
                            <label class="form-label" style="font-size: 0.82rem; font-weight: 700;">Nomor Kartu Keluarga
                                (No.
                                KK)</label>
                            <input type="text" name="no_kk" maxlength="16" class="form-control"
                                value="{{ old('no_kk', $identitas->no_kk) }}" placeholder="3203xxxxxxxxxxxx"
                                style="font-size: 0.85rem; font-family: monospace;">
                        </div>

                        <!-- No. Registrasi Akta Lahir -->
                        <div>
                            <label class="form-label" style="font-size: 0.82rem; font-weight: 700;">No. Registrasi Akta
                                Lahir</label>
                            <input type="text" name="no_registrasi_akta_lahir" class="form-control"
                                value="{{ old('no_registrasi_akta_lahir', $identitas->no_registrasi_akta_lahir) }}"
                                placeholder="Contoh: 3203-LT-xxxx" style="font-size: 0.85rem;">
                        </div>

                        <!-- Kewarganegaraan -->
                        <div>
                            <label class="form-label"
                                style="font-size: 0.82rem; font-weight: 700;">Kewarganegaraan</label>
                            <select name="kewarganegaraan" class="form-select" style="font-size: 0.85rem;">
                                <option value="WNI"
                                    {{ old('kewarganegaraan', $identitas->kewarganegaraan) === 'WNI' ? 'selected' : '' }}>
                                    Warga
                                    Negara Indonesia (WNI)</option>
                                <option value="WNA"
                                    {{ old('kewarganegaraan', $identitas->kewarganegaraan) === 'WNA' ? 'selected' : '' }}>
                                    Warga
                                    Negara Asing (WNA)</option>
                            </select>
                        </div>

                        <!-- Tempat Lahir -->
                        <div>
                            <label class="form-label" style="font-size: 0.82rem; font-weight: 700;">Tempat Lahir</label>
                            <input type="text" name="tempat_lahir" class="form-control"
                                value="{{ old('tempat_lahir', $identitas->tempat_lahir ?: $pd->tempat_lahir) }}"
                                style="font-size: 0.85rem;">
                        </div>

                        <!-- Tanggal Lahir -->
                        <div>
                            <label class="form-label" style="font-size: 0.82rem; font-weight: 700;">Tanggal Lahir</label>
                            <input type="date" name="tanggal_lahir" class="form-control"
                                value="{{ old('tanggal_lahir', $identitas->tanggal_lahir ? $identitas->tanggal_lahir->format('Y-m-d') : ($pd->tanggal_lahir ? \Carbon\Carbon::parse($pd->tanggal_lahir)->format('Y-m-d') : '')) }}"
                                style="font-size: 0.85rem;">
                        </div>

                        <!-- Agama & Kepercayaan -->
                        <div>
                            <label class="form-label" style="font-size: 0.82rem; font-weight: 700;">Agama &amp;
                                Kepercayaan</label>
                            <select name="agama_id" class="form-select" required style="font-size: 0.85rem;">
                                @foreach ($ref['agama'] as $aId => $aName)
                                    <option value="{{ $aId }}"
                                        {{ old('agama_id', $identitas->agama_id ?: $pd->agama_id) == $aId ? 'selected' : '' }}>
                                        {{ $aName }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Kebutuhan Khusus -->
                        <div>
                            <label class="form-label" style="font-size: 0.82rem; font-weight: 700;">Berkebutuhan
                                Khusus</label>
                            <select name="kebutuhan_khusus_id" class="form-select" style="font-size: 0.85rem;">
                                @foreach ($ref['kebutuhan_khusus'] as $kId => $kName)
                                    <option value="{{ $kId }}"
                                        {{ old('kebutuhan_khusus_id', $identitas->kebutuhan_khusus_id) == $kId ? 'selected' : '' }}>
                                        {{ $kName }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Anak ke-berapa -->
                        <div>
                            <label class="form-label" style="font-size: 0.82rem; font-weight: 700;">Anak Ke-berapa (di
                                KK)</label>
                            <input type="number" name="anak_keberapa" class="form-control" min="1"
                                max="25" required
                                value="{{ old('anak_keberapa', $identitas->anak_keberapa ?: ($pd->anak_keberapa ?: 1)) }}"
                                style="font-size: 0.85rem;">
                        </div>

                    </div>
                </div>

                <!-- ========================================== -->
                <!-- SEKSI 2: ALAMAT & DOMISILI -->
                <!-- ========================================== -->
                <div class="card identitas-form-section" data-section="pribadi_alamat"
                    style="margin-bottom: 24px; padding: 22px 24px; border-radius: 14px;">
                    <div
                        style="font-weight: 800; font-size: 1.05rem; color: #0284c7; margin-bottom: 18px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-location-dot"></i> Bagian 1: Data Pribadi &amp; Domisili
                        </div>
                        <span style="font-size: 0.75rem; color: var(--text-muted); font-weight: normal;">Sesuai Kartu
                            Keluarga
                            / Domisili Aktual</span>
                    </div>

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 16px;">
                        <!-- 1. Provinsi (Dropdown Bertingkat) -->
                        <div>
                            <label class="form-label"
                                style="font-size: 0.82rem; font-weight: 700; display: flex; align-items: center; justify-content: space-between;">
                                <span>Provinsi <span class="text-danger">*</span></span>
                                <span id="badgeProvLoading" class="badge"
                                    style="display: none; font-size: 0.68rem; background: rgba(59,130,246,0.15); color: #3b82f6;">Memuat...</span>
                            </label>
                            <select name="provinsi" id="selectProvinsi" class="form-select"
                                data-initial="{{ old('provinsi', $identitas->provinsi) }}" required
                                style="font-size: 0.85rem;">
                                @if (old('provinsi', $identitas->provinsi))
                                    <option value="{{ old('provinsi', $identitas->provinsi) }}" selected>
                                        {{ old('provinsi', $identitas->provinsi) }}</option>
                                @else
                                    <option value="">-- Pilih Provinsi --</option>
                                @endif
                            </select>
                        </div>

                        <!-- 2. Kabupaten / Kota (Dropdown Bertingkat) -->
                        <div>
                            <label class="form-label" style="font-size: 0.82rem; font-weight: 700;">Kabupaten / Kota <span
                                    class="text-danger">*</span></label>
                            <select name="kabupaten_kota" id="selectKabupaten" class="form-select"
                                data-initial="{{ old('kabupaten_kota', $identitas->kabupaten_kota) }}" required
                                style="font-size: 0.85rem;">
                                @if (old('kabupaten_kota', $identitas->kabupaten_kota))
                                    <option value="{{ old('kabupaten_kota', $identitas->kabupaten_kota) }}" selected>
                                        {{ old('kabupaten_kota', $identitas->kabupaten_kota) }}</option>
                                @else
                                    <option value="">-- Pilih Kabupaten / Kota --</option>
                                @endif
                            </select>
                        </div>

                        <!-- 3. Kecamatan (Dropdown Bertingkat) -->
                        <div>
                            <label class="form-label" style="font-size: 0.82rem; font-weight: 700;">Kecamatan <span
                                    class="text-danger">*</span></label>
                            <select name="kecamatan" id="selectKecamatan" class="form-select"
                                data-initial="{{ old('kecamatan', $identitas->kecamatan) }}" required
                                style="font-size: 0.85rem;">
                                @if (old('kecamatan', $identitas->kecamatan))
                                    <option value="{{ old('kecamatan', $identitas->kecamatan) }}" selected>
                                        {{ old('kecamatan', $identitas->kecamatan) }}</option>
                                @else
                                    <option value="">-- Pilih Kecamatan --</option>
                                @endif
                            </select>
                        </div>

                        <!-- 4. Desa / Kelurahan (Dropdown Bertingkat) -->
                        <div>
                            <label class="form-label" style="font-size: 0.82rem; font-weight: 700;">Desa / Kelurahan <span
                                    class="text-danger">*</span></label>
                            <select name="desa_kelurahan" id="selectDesa" class="form-select"
                                data-initial="{{ old('desa_kelurahan', $identitas->desa_kelurahan) }}" required
                                style="font-size: 0.85rem;">
                                @if (old('desa_kelurahan', $identitas->desa_kelurahan))
                                    <option value="{{ old('desa_kelurahan', $identitas->desa_kelurahan) }}" selected>
                                        {{ old('desa_kelurahan', $identitas->desa_kelurahan) }}</option>
                                @else
                                    <option value="">-- Pilih Desa / Kelurahan --</option>
                                @endif
                            </select>
                        </div>

                        <!-- Kode Pos -->
                        <div>
                            <label class="form-label" style="font-size: 0.82rem; font-weight: 700;">Kode Pos</label>
                            <input type="text" name="kode_pos" id="inputKodePos" maxlength="5" class="form-control"
                                placeholder="43266" value="{{ old('kode_pos', $identitas->kode_pos) }}"
                                style="font-size: 0.85rem; font-family: monospace;">
                        </div>

                        <!-- Alamat Jalan / Kampung / Dusun -->
                        <div style="grid-column: 1 / -1;">
                            <label class="form-label" style="font-size: 0.82rem; font-weight: 700;">Alamat Jalan / Kampung
                                / Dusun <span class="text-danger">*</span></label>
                            <input type="text" name="alamat_jalan" class="form-control"
                                value="{{ old('alamat_jalan', $identitas->alamat_jalan ?: $pd->alamat_jalan) }}"
                                placeholder="Nama jalan, nomor rumah, blok/gang (Sesuai Kartu Keluarga)" required
                                style="font-size: 0.85rem;">
                            <span style="font-size: 0.72rem; color: var(--text-muted); margin-top: 3px; display: block;">
                                Sesuai KK: Alamat mikro tingkat lingkungan/blok (misal: Kp. Saluyu / Jl. Pasirpari No. 12).
                            </span>
                        </div>

                        <!-- RT -->
                        <div>
                            <label class="form-label" style="font-size: 0.82rem; font-weight: 700;">RT</label>
                            <input type="text" name="rt" maxlength="4" class="form-control" placeholder="001"
                                value="{{ old('rt', $identitas->rt) }}" style="font-size: 0.85rem;">
                        </div>

                        <!-- RW -->
                        <div>
                            <label class="form-label" style="font-size: 0.82rem; font-weight: 700;">RW</label>
                            <input type="text" name="rw" maxlength="4" class="form-control" placeholder="002"
                                value="{{ old('rw', $identitas->rw) }}" style="font-size: 0.85rem;">
                        </div>

                        <!-- Nama Dusun -->
                        <div>
                            <label class="form-label" style="font-size: 0.82rem; font-weight: 700;">Nama Dusun /
                                Wilayah</label>
                            <input type="text" name="nama_dusun" class="form-control"
                                placeholder="Contoh: Dusun Saluyu"
                                value="{{ old('nama_dusun', $identitas->nama_dusun) }}" style="font-size: 0.85rem;">
                        </div>

                        <!-- Tempat Tinggal -->
                        <div>
                            <label class="form-label" style="font-size: 0.82rem; font-weight: 700;">Status Tempat
                                Tinggal</label>
                            <select name="tempat_tinggal_id" class="form-select" required style="font-size: 0.85rem;">
                                @foreach ($ref['tempat_tinggal'] as $tId => $tName)
                                    <option value="{{ $tId }}"
                                        {{ old('tempat_tinggal_id', $identitas->tempat_tinggal_id) == $tId ? 'selected' : '' }}>
                                        {{ $tName }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Moda Transportasi -->
                        <div>
                            <label class="form-label" style="font-size: 0.82rem; font-weight: 700;">Moda Transportasi ke
                                Sekolah</label>
                            <select name="transportasi_id" class="form-select" required style="font-size: 0.85rem;">
                                @foreach ($ref['transportasi'] as $trId => $trName)
                                    <option value="{{ $trId }}"
                                        {{ old('transportasi_id', $identitas->transportasi_id) == $trId ? 'selected' : '' }}>
                                        {{ $trName }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Titik Lintang & Bujur (Geotagging) -->
                        <div>
                            <label class="form-label" style="font-size: 0.82rem; font-weight: 700;">Titik Lintang
                                (Latitude)</label>
                            <input type="text" name="lintang" class="form-control" placeholder="-7.1234567"
                                value="{{ old('lintang', $identitas->lintang) }}"
                                style="font-size: 0.85rem; font-family: monospace;">
                        </div>

                        <div>
                            <label class="form-label" style="font-size: 0.82rem; font-weight: 700;">Titik Bujur
                                (Longitude)</label>
                            <input type="text" name="bujur" class="form-control" placeholder="107.1234567"
                                value="{{ old('bujur', $identitas->bujur) }}"
                                style="font-size: 0.85rem; font-family: monospace;">
                        </div>
                    </div>
                </div>

                <!-- ========================================== -->
                <!-- SEKSI 3: DATA ORANG TUA (AYAH & IBU) -->
