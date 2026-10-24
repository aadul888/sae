                <!-- ========================================== -->
                <div class="card identitas-form-section" data-section="orang_tua"
                    style="margin-bottom: 24px; padding: 22px 24px; border-radius: 14px;">
                    <div
                        style="font-weight: 800; font-size: 1.05rem; color: #16a34a; margin-bottom: 18px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-people-roof"></i> Bagian 2 &amp; 3: Data Ayah dan Ibu Kandung
                        </div>
                        <span style="font-size: 0.75rem; color: var(--text-muted); font-weight: normal;">Ayah Kandung &amp;
                            Ibu
                            Kandung</span>
                    </div>

                    <!-- Sub-Kolom 1: Data Ayah Kandung -->
                    <div
                        style="margin-bottom: 24px; background: var(--bg-hover); padding: 18px; border-radius: 12px; border: 1px solid var(--border-color);">
                        <div
                            style="font-weight: 700; font-size: 0.92rem; color: var(--text-color); margin-bottom: 14px; display: flex; align-items: center; justify-content: space-between;">
                            <span><i class="fas fa-person me-2 text-primary"></i> Data Ayah Kandung</span>
                            <div style="display: flex; gap: 14px; font-size: 0.82rem;">
                                <label style="cursor: pointer; display: flex; align-items: center; gap: 5px;">
                                    <input type="radio" name="status_hidup_ayah" value="1"
                                        {{ old('status_hidup_ayah', $identitas->status_hidup_ayah) !== '0' ? 'checked' : '' }}>
                                    Masih Hidup
                                </label>
                                <label style="cursor: pointer; display: flex; align-items: center; gap: 5px;">
                                    <input type="radio" name="status_hidup_ayah" value="0"
                                        {{ old('status_hidup_ayah', $identitas->status_hidup_ayah) === '0' ? 'checked' : '' }}>
                                    Sudah Meninggal
                                </label>
                            </div>
                        </div>

                        <div
                            style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 14px;">
                            <div>
                                <label class="form-label" style="font-size: 0.8rem; font-weight: 700;">Nama Ayah
                                    Kandung</label>
                                <input type="text" name="nama_ayah" class="form-control form-control-sm"
                                    value="{{ old('nama_ayah', $identitas->nama_ayah ?: $pd->nama_ayah) }}"
                                    style="font-size: 0.85rem;">
                            </div>

                            <div>
                                <label class="form-label" style="font-size: 0.8rem; font-weight: 700;">NIK Ayah (16
                                    Digit)</label>
                                <input type="text" name="nik_ayah" maxlength="16"
                                    class="form-control form-control-sm"
                                    value="{{ old('nik_ayah', $identitas->nik_ayah) }}" placeholder="3203xxxxxxxxxxxx"
                                    style="font-size: 0.85rem; font-family: monospace;">
                            </div>

                            <div>
                                <label class="form-label" style="font-size: 0.8rem; font-weight: 700;">Tahun Lahir
                                    Ayah</label>
                                <input type="number" name="tahun_lahir_ayah" min="1940" max="{{ date('Y') }}"
                                    class="form-control form-control-sm" placeholder="1975"
                                    value="{{ old('tahun_lahir_ayah', $identitas->tahun_lahir_ayah) }}"
                                    style="font-size: 0.85rem;">
                            </div>

                            <div>
                                <label class="form-label" style="font-size: 0.8rem; font-weight: 700;">Pendidikan Terakhir
                                    Ayah</label>
                                <select name="pendidikan_ayah_id" class="form-select form-select-sm"
                                    style="font-size: 0.85rem;">
                                    <option value="">Pilih Jenjang</option>
                                    @foreach ($ref['jenjang_pendidikan'] as $jpId => $jpName)
                                        <option value="{{ $jpId }}"
                                            {{ old('pendidikan_ayah_id', $identitas->pendidikan_ayah_id) == $jpId ? 'selected' : '' }}>
                                            {{ $jpName }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="form-label" style="font-size: 0.8rem; font-weight: 700;">Pekerjaan Utama
                                    Ayah</label>
                                <select name="pekerjaan_ayah_id" class="form-select form-select-sm" required
                                    style="font-size: 0.85rem;">
                                    <option value="">Pilih Pekerjaan</option>
                                    @foreach ($ref['pekerjaan'] as $pkId => $pkName)
                                        <option value="{{ $pkId }}"
                                            {{ old('pekerjaan_ayah_id', $identitas->pekerjaan_ayah_id ?: $pd->pekerjaan_ayah_id) == $pkId ? 'selected' : '' }}>
                                            {{ $pkName }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="form-label" style="font-size: 0.8rem; font-weight: 700;">Penghasilan Bulanan
                                    Ayah</label>
                                <select name="penghasilan_ayah_id" class="form-select form-select-sm" required
                                    style="font-size: 0.85rem;">
                                    <option value="">Pilih Rentang</option>
                                    @foreach ($ref['penghasilan'] as $phId => $phName)
                                        <option value="{{ $phId }}"
                                            {{ old('penghasilan_ayah_id', $identitas->penghasilan_ayah_id) == $phId ? 'selected' : '' }}>
                                            {{ $phName }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="form-label" style="font-size: 0.8rem; font-weight: 700;">Berkebutuhan Khusus
                                    Ayah</label>
                                <select name="kebutuhan_khusus_ayah_id" class="form-select form-select-sm"
                                    style="font-size: 0.85rem;">
                                    @foreach ($ref['kebutuhan_khusus'] as $kbId => $kbName)
                                        <option value="{{ $kbId }}"
                                            {{ old('kebutuhan_khusus_ayah_id', $identitas->kebutuhan_khusus_ayah_id) == $kbId ? 'selected' : '' }}>
                                            {{ $kbName }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Sub-Kolom 2: Data Ibu Kandung -->
                    <div
                        style="background: var(--bg-hover); padding: 18px; border-radius: 12px; border: 1px solid var(--border-color);">
                        <div
                            style="font-weight: 700; font-size: 0.92rem; color: var(--text-color); margin-bottom: 14px; display: flex; align-items: center; justify-content: space-between;">
                            <span><i class="fas fa-person-dress me-2 text-primary"></i> Data Ibu Kandung</span>
                            <div style="display: flex; gap: 14px; font-size: 0.82rem;">
                                <label style="cursor: pointer; display: flex; align-items: center; gap: 5px;">
                                    <input type="radio" name="status_hidup_ibu" value="1"
                                        {{ old('status_hidup_ibu', $identitas->status_hidup_ibu) !== '0' ? 'checked' : '' }}>
                                    Masih Hidup
                                </label>
                                <label style="cursor: pointer; display: flex; align-items: center; gap: 5px;">
                                    <input type="radio" name="status_hidup_ibu" value="0"
                                        {{ old('status_hidup_ibu', $identitas->status_hidup_ibu) === '0' ? 'checked' : '' }}>
                                    Sudah Meninggal
                                </label>
                            </div>
                        </div>

                        <div
                            style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 14px;">
                            <div>
                                <label class="form-label" style="font-size: 0.8rem; font-weight: 700;">Nama Ibu
                                    Kandung</label>
                                <input type="text" name="nama_ibu" class="form-control form-control-sm" required
                                    value="{{ old('nama_ibu', $identitas->nama_ibu ?: $pd->nama_ibu) }}"
                                    style="font-size: 0.85rem;">
                            </div>

                            <div>
                                <label class="form-label" style="font-size: 0.8rem; font-weight: 700;">NIK Ibu (16
                                    Digit)</label>
                                <input type="text" name="nik_ibu" maxlength="16" class="form-control form-control-sm"
                                    value="{{ old('nik_ibu', $identitas->nik_ibu) }}" placeholder="3203xxxxxxxxxxxx"
                                    style="font-size: 0.85rem; font-family: monospace;">
                            </div>

                            <div>
                                <label class="form-label" style="font-size: 0.8rem; font-weight: 700;">Tahun Lahir
                                    Ibu</label>
                                <input type="number" name="tahun_lahir_ibu" min="1940" max="{{ date('Y') }}"
                                    class="form-control form-control-sm" placeholder="1978"
                                    value="{{ old('tahun_lahir_ibu', $identitas->tahun_lahir_ibu) }}"
                                    style="font-size: 0.85rem;">
                            </div>

                            <div>
                                <label class="form-label" style="font-size: 0.8rem; font-weight: 700;">Pendidikan Terakhir
                                    Ibu</label>
                                <select name="pendidikan_ibu_id" class="form-select form-select-sm" required
                                    style="font-size: 0.85rem;">
                                    <option value="">Pilih Jenjang</option>
                                    @foreach ($ref['jenjang_pendidikan'] as $jpId => $jpName)
                                        <option value="{{ $jpId }}"
                                            {{ old('pendidikan_ibu_id', $identitas->pendidikan_ibu_id) == $jpId ? 'selected' : '' }}>
                                            {{ $jpName }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="form-label" style="font-size: 0.8rem; font-weight: 700;">Pekerjaan Utama
                                    Ibu</label>
                                <select name="pekerjaan_ibu_id" class="form-select form-select-sm" required
                                    style="font-size: 0.85rem;">
                                    <option value="">Pilih Pekerjaan</option>
                                    @foreach ($ref['pekerjaan'] as $pkId => $pkName)
                                        <option value="{{ $pkId }}"
                                            {{ old('pekerjaan_ibu_id', $identitas->pekerjaan_ibu_id ?: $pd->pekerjaan_ibu_id) == $pkId ? 'selected' : '' }}>
                                            {{ $pkName }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="form-label" style="font-size: 0.8rem; font-weight: 700;">Penghasilan Bulanan
                                    Ibu</label>
                                <select name="penghasilan_ibu_id" class="form-select form-select-sm" required
                                    style="font-size: 0.85rem;">
                                    <option value="">Pilih Rentang</option>
                                    @foreach ($ref['penghasilan'] as $phId => $phName)
                                        <option value="{{ $phId }}"
                                            {{ old('penghasilan_ibu_id', $identitas->penghasilan_ibu_id) == $phId ? 'selected' : '' }}>
                                            {{ $phName }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="form-label" style="font-size: 0.8rem; font-weight: 700;">Berkebutuhan Khusus
                                    Ibu</label>
                                <select name="kebutuhan_khusus_ibu_id" class="form-select form-select-sm"
                                    style="font-size: 0.85rem;">
                                    @foreach ($ref['kebutuhan_khusus'] as $kbId => $kbName)
                                        <option value="{{ $kbId }}"
                                            {{ old('kebutuhan_khusus_ibu_id', $identitas->kebutuhan_khusus_ibu_id) == $kbId ? 'selected' : '' }}>
                                            {{ $kbName }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ========================================== -->
                <!-- SEKSI 4: DATA WALI MURID (OPSIONAL) -->
                <!-- ========================================== -->
                <div class="card identitas-form-section" data-section="orang_tua"
                    style="margin-bottom: 24px; padding: 22px 24px; border-radius: 14px;">
                    <div
                        style="font-weight: 800; font-size: 1.05rem; color: #d97706; margin-bottom: 18px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-handshake-angle"></i> Bagian 4: Data Wali
                        </div>
                        <label
                            style="font-size: 0.85rem; font-weight: 700; color: var(--text-color); display: flex; align-items: center; gap: 8px; cursor: pointer;">
                            <input type="checkbox" name="mempunyai_wali" id="checkMempunyaiWali" value="1"
                                {{ old('mempunyai_wali', $identitas->mempunyai_wali) ? 'checked' : '' }}>
                            Mempunyai Wali (Tinggal bersama wali)
                        </label>
                    </div>

                    <div id="containerDataWali"
                        style="display: {{ old('mempunyai_wali', $identitas->mempunyai_wali) ? 'block' : 'none' }};">
                        <div
                            style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 14px;">
                            <div>
                                <label class="form-label" style="font-size: 0.8rem; font-weight: 700;">Nama Lengkap
                                    Wali</label>
                                <input type="text" name="nama_wali" class="form-control form-control-sm"
                                    value="{{ old('nama_wali', $identitas->nama_wali ?: $pd->nama_wali) }}"
                                    style="font-size: 0.85rem;">
                            </div>

                            <div>
                                <label class="form-label" style="font-size: 0.8rem; font-weight: 700;">NIK Wali (16
                                    Digit)</label>
                                <input type="text" name="nik_wali" maxlength="16"
                                    class="form-control form-control-sm"
                                    value="{{ old('nik_wali', $identitas->nik_wali) }}" placeholder="3203xxxxxxxxxxxx"
                                    style="font-size: 0.85rem; font-family: monospace;">
                            </div>

                            <div>
                                <label class="form-label" style="font-size: 0.8rem; font-weight: 700;">Tahun Lahir
                                    Wali</label>
                                <input type="number" name="tahun_lahir_wali" min="1930" max="{{ date('Y') }}"
                                    class="form-control form-control-sm" placeholder="1970"
                                    value="{{ old('tahun_lahir_wali', $identitas->tahun_lahir_wali) }}"
                                    style="font-size: 0.85rem;">
                            </div>

                            <div>
                                <label class="form-label" style="font-size: 0.8rem; font-weight: 700;">Pendidikan Terakhir
                                    Wali</label>
                                <select name="pendidikan_wali_id" class="form-select form-select-sm"
                                    style="font-size: 0.85rem;">
                                    <option value="">Pilih Jenjang</option>
                                    @foreach ($ref['jenjang_pendidikan'] as $jpId => $jpName)
                                        <option value="{{ $jpId }}"
                                            {{ old('pendidikan_wali_id', $identitas->pendidikan_wali_id) == $jpId ? 'selected' : '' }}>
                                            {{ $jpName }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="form-label" style="font-size: 0.8rem; font-weight: 700;">Pekerjaan Utama
                                    Wali</label>
                                <select name="pekerjaan_wali_id" class="form-select form-select-sm"
                                    style="font-size: 0.85rem;">
                                    <option value="">Pilih Pekerjaan</option>
                                    @foreach ($ref['pekerjaan'] as $pkId => $pkName)
                                        <option value="{{ $pkId }}"
                                            {{ old('pekerjaan_wali_id', $identitas->pekerjaan_wali_id ?: $pd->pekerjaan_wali_id) == $pkId ? 'selected' : '' }}>
                                            {{ $pkName }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="form-label" style="font-size: 0.8rem; font-weight: 700;">Penghasilan Bulanan
                                    Wali</label>
                                <select name="penghasilan_wali_id" class="form-select form-select-sm"
                                    style="font-size: 0.85rem;">
                                    <option value="">Pilih Rentang</option>
                                    @foreach ($ref['penghasilan'] as $phId => $phName)
                                        <option value="{{ $phId }}"
                                            {{ old('penghasilan_wali_id', $identitas->penghasilan_wali_id) == $phId ? 'selected' : '' }}>
                                            {{ $phName }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="form-label" style="font-size: 0.8rem; font-weight: 700;">Berkebutuhan Khusus
                                    Wali</label>
                                <select name="kebutuhan_khusus_wali_id" class="form-select form-select-sm"
                                    style="font-size: 0.85rem;">
                                    @foreach ($ref['kebutuhan_khusus'] as $kbId => $kbName)
                                        <option value="{{ $kbId }}"
                                            {{ old('kebutuhan_khusus_wali_id', $identitas->kebutuhan_khusus_wali_id) == $kbId ? 'selected' : '' }}>
                                            {{ $kbName }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ========================================== -->
                <!-- SEKSI 5: KONTAK DAN PENDAFTARAN MASUK -->
