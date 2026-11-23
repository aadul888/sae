                <!-- ========================================== -->
                <!-- SEKSI: BAGIAN 5 - KONTAK -->
                <!-- ========================================== -->
                <div class="card identitas-form-section" data-section="registrasi_kesejahteraan"
                    style="margin-bottom: 24px; padding: 22px 24px; border-radius: var(--radius-xl, 16px);">
                    <div
                        style="font-weight: 800; font-size: var(--text-lg, 1.125rem); color: #9333ea; margin-bottom: 18px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-address-book"></i> Bagian 5: Kontak
                        </div>
                        <span style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); font-weight: normal;">Telepon
                            &amp; Email</span>
                    </div>

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 16px;">
                        <!-- Nomor Telepon Rumah -->
                        <div>
                            <label class="form-label" style="font-size: var(--text-sm, 0.8125rem); font-weight: 700;">No. Telepon Rumah
                                (Kabel)</label>
                            <input type="text" name="nomor_telepon_rumah" class="form-control"
                                placeholder="0263xxxxxx"
                                value="{{ old('nomor_telepon_rumah', $identitas->nomor_telepon_rumah ?: $pd->nomor_telepon_rumah) }}"
                                style="font-size: var(--text-base, 0.875rem);">
                        </div>

                        <!-- Nomor HP -->
                        <div>
                            <label class="form-label" style="font-size: var(--text-sm, 0.8125rem); font-weight: 700;">No. HP / WhatsApp
                                Siswa
                                <span class="text-danger">*</span></label>
                            <input type="text" name="nomor_telepon_seluler" class="form-control"
                                placeholder="08xxxxxxxxxx"
                                value="{{ old('nomor_telepon_seluler', $identitas->nomor_telepon_seluler ?: $pd->nomor_telepon_seluler) }}"
                                required style="font-size: var(--text-base, 0.875rem);">
                        </div>

                        <!-- Email -->
                        <div>
                            <label class="form-label" style="font-size: var(--text-sm, 0.8125rem); font-weight: 700;">Email Siswa
                                Aktif</label>
                            <input type="email" name="email" class="form-control" placeholder="siswa@belajar.id"
                                value="{{ old('email', $identitas->email ?: $pd->email) }}" style="font-size: var(--text-base, 0.875rem);">
                        </div>
                    </div>
                </div>

                <!-- ========================================== -->
                <!-- SEKSI: BAGIAN 6 - PRESTASI -->
                <!-- ========================================== -->
                <div class="card identitas-form-section" data-section="prestasi_minat"
                    style="margin-bottom: 24px; padding: 22px 24px; border-radius: var(--radius-xl, 16px);">
                    <div
                        style="font-weight: 800; font-size: var(--text-lg, 1.125rem); color: #ea580c; margin-bottom: 14px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-trophy"></i> Bagian 6: Prestasi
                        </div>
                        <button type="button" class="btn btn-outline" id="btnAddPrestasi"
                            style="font-size: var(--text-sm, 0.8125rem); padding: 5px 12px; border-radius: var(--radius-sm, 6px);">
                            <i class="fas fa-plus me-1"></i> Tambah Prestasi
                        </button>
                    </div>

                    <div style="overflow-x: auto;">
                        <table class="table mb-0" style="width: 100%; border-collapse: collapse; font-size: var(--text-base, 0.875rem);">
                            <thead>
                                <tr style="background: var(--bg-hover); border-bottom: 1px solid var(--border-color);">
                                    <th
                                        style="padding: 10px 8px; font-size: var(--text-xs, 0.75rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                        Bidang</th>
                                    <th
                                        style="padding: 10px 8px; font-size: var(--text-xs, 0.75rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                        Tingkat</th>
                                    <th
                                        style="padding: 10px 8px; font-size: var(--text-xs, 0.75rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                        Nama Kejuaraan / Lomba</th>
                                    <th
                                        style="padding: 10px 8px; font-size: var(--text-xs, 0.75rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 90px;">
                                        Tahun</th>
                                    <th
                                        style="padding: 10px 8px; font-size: var(--text-xs, 0.75rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                        Penyelenggara</th>
                                    <th
                                        style="padding: 10px 8px; font-size: var(--text-xs, 0.75rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 100px;">
                                        Peringkat</th>
                                    <th
                                        style="padding: 10px 8px; font-size: var(--text-xs, 0.75rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 45px;">
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
                                                    class="form-select form-select-sm" style="font-size: var(--text-sm, 0.8125rem);">
                                                    @foreach ($ref['jenis_prestasi'] as $jp)
                                                        <option value="{{ $jp }}"
                                                            {{ ($p['jenis'] ?? '') === $jp ? 'selected' : '' }}>
                                                            {{ $jp }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td style="padding: 10px 8px;">
                                                <select name="riwayat_prestasi[{{ $idx }}][tingkat]"
                                                    class="form-select form-select-sm" style="font-size: var(--text-sm, 0.8125rem);">
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
                                                    value="{{ $p['nama'] ?? '' }}" style="font-size: var(--text-sm, 0.8125rem);" required>
                                            </td>
                                            <td style="padding: 10px 8px; width: 90px;">
                                                <input type="number"
                                                    name="riwayat_prestasi[{{ $idx }}][tahun]"
                                                    class="form-control form-control-sm" placeholder="Tahun"
                                                    value="{{ $p['tahun'] ?? '' }}" style="font-size: var(--text-sm, 0.8125rem);">
                                            </td>
                                            <td style="padding: 10px 8px;">
                                                <input type="text"
                                                    name="riwayat_prestasi[{{ $idx }}][penyelenggara]"
                                                    class="form-control form-control-sm"
                                                    placeholder="Instansi Penyelenggara"
                                                    value="{{ $p['penyelenggara'] ?? '' }}" style="font-size: var(--text-sm, 0.8125rem);">
                                            </td>
                                            <td style="padding: 10px 8px; width: 100px;">
                                                <input type="text"
                                                    name="riwayat_prestasi[{{ $idx }}][peringkat]"
                                                    class="form-control form-control-sm" placeholder="Juara 1 / dll"
                                                    value="{{ $p['peringkat'] ?? '' }}" style="font-size: var(--text-sm, 0.8125rem);">
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
                                        style="padding: 24px; text-align: center; color: var(--text-muted); font-size: var(--text-sm, 0.8125rem);">
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
                <!-- SEKSI: BAGIAN 7 - KESEJAHTERAAN -->
                <!-- ========================================== -->
                <div class="card identitas-form-section" data-section="registrasi_kesejahteraan"
                    style="margin-bottom: 24px; padding: 22px 24px; border-radius: var(--radius-xl, 16px);">
                    <div
                        style="font-weight: 800; font-size: var(--text-lg, 1.125rem); color: var(--primary-hover, #2563eb); margin-bottom: 14px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-hand-holding-heart"></i> Bagian 7: Kesejahteraan
                        </div>
                        <button type="button" class="btn btn-outline" id="btnAddSosial"
                            style="font-size: var(--text-sm, 0.8125rem); padding: 5px 12px; border-radius: var(--radius-sm, 6px);">
                            <i class="fas fa-plus me-1"></i> Tambah Bantuan
                        </button>
                    </div>

                    <div style="overflow-x: auto;">
                        <table class="table mb-0" style="width: 100%; border-collapse: collapse; font-size: var(--text-base, 0.875rem);">
                            <thead>
                                <tr style="background: var(--bg-hover); border-bottom: 1px solid var(--border-color);">
                                    <th
                                        style="padding: 10px 8px; font-size: var(--text-xs, 0.75rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                        Jenis Bantuan</th>
                                    <th
                                        style="padding: 10px 8px; font-size: var(--text-xs, 0.75rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                        Nomor Kartu</th>
                                    <th
                                        style="padding: 10px 8px; font-size: var(--text-xs, 0.75rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                        Nama Tertera di Kartu</th>
                                    <th
                                        style="padding: 10px 8px; font-size: var(--text-xs, 0.75rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 95px;">
                                        Tahun Mulai</th>
                                    <th
                                        style="padding: 10px 8px; font-size: var(--text-xs, 0.75rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 95px;">
                                        Tahun Selesai</th>
                                    <th
                                        style="padding: 10px 8px; font-size: var(--text-xs, 0.75rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 45px;">
                                        Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="tbodySosial">
                                @php
                                    $sosialRows = old('perlindungan_sosial', $identitas->perlindungan_sosial ?? []);
                                @endphp
                                @if (!empty($sosialRows) && is_array($sosialRows))
                                    @foreach ($sosialRows as $idx => $s)
                                        <tr class="row-sosial" style="border-bottom: 1px solid var(--border-color);">
                                            <td style="padding: 10px 8px;">
                                                <select name="perlindungan_sosial[{{ $idx }}][jenis]"
                                                    class="form-select form-select-sm" style="font-size: var(--text-sm, 0.8125rem);">
                                                    @foreach ($ref['kesejahteraan'] as $kj)
                                                        <option value="{{ $kj }}"
                                                            {{ ($s['jenis'] ?? '') === $kj ? 'selected' : '' }}>
                                                            {{ $kj }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td style="padding: 10px 8px;">
                                                <input type="text"
                                                    name="perlindungan_sosial[{{ $idx }}][no_kartu]"
                                                    class="form-control form-control-sm" placeholder="Nomor Fisik Kartu"
                                                    value="{{ $s['no_kartu'] ?? '' }}" style="font-size: var(--text-sm, 0.8125rem);"
                                                    required>
                                            </td>
                                            <td style="padding: 10px 8px;">
                                                <input type="text"
                                                    name="perlindungan_sosial[{{ $idx }}][nama_di_kartu]"
                                                    class="form-control form-control-sm"
                                                    placeholder="Nama Tertera di Kartu"
                                                    value="{{ $s['nama_di_kartu'] ?? '' }}" style="font-size: var(--text-sm, 0.8125rem);">
                                            </td>
                                            <td style="padding: 10px 8px; width: 95px;">
                                                <input type="number"
                                                    name="perlindungan_sosial[{{ $idx }}][tahun_mulai]"
                                                    class="form-control form-control-sm" placeholder="Mulai"
                                                    value="{{ $s['tahun_mulai'] ?? '' }}" style="font-size: var(--text-sm, 0.8125rem);">
                                            </td>
                                            <td style="padding: 10px 8px; width: 95px;">
                                                <input type="number"
                                                    name="perlindungan_sosial[{{ $idx }}][tahun_selesai]"
                                                    class="form-control form-control-sm" placeholder="Selesai"
                                                    value="{{ $s['tahun_selesai'] ?? '' }}" style="font-size: var(--text-sm, 0.8125rem);">
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
                                <tr id="emptySosial"
                                    style="{{ !empty($sosialRows) && count($sosialRows) > 0 ? 'display: none;' : '' }}">
                                    <td colspan="6"
                                        style="padding: 24px; text-align: center; color: var(--text-muted); font-size: var(--text-sm, 0.8125rem);">
                                        Belum ada data kartu bantuan yang ditambahkan. Klik tombol <strong>+ Tambah
                                            Bantuan</strong> jika menerima KIP, KKS, KIS, atau bansos lainnya.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- ========================================== -->
                <!-- SEKSI: BAGIAN 8 - PENDAFTARAN MASUK -->
                <!-- ========================================== -->
                <div class="card identitas-form-section" data-section="registrasi_kesejahteraan"
                    style="margin-bottom: 24px; padding: 22px 24px; border-radius: var(--radius-xl, 16px);">
                    <div
                        style="font-weight: 800; font-size: var(--text-lg, 1.125rem); color: #9333ea; margin-bottom: 18px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-right-to-bracket"></i> Bagian 8: Pendaftaran Masuk
                        </div>
                    </div>

                    <div
                        style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 16px; margin-bottom: 20px;">
                        <!-- Jenis Pendaftaran -->
                        <div>
                            <label class="form-label" style="font-size: var(--text-sm, 0.8125rem); font-weight: 700;">Jenis
                                Pendaftaran</label>
                            <select name="jenis_pendaftaran_id" class="form-select" required style="font-size: var(--text-base, 0.875rem);">
                                @foreach ($ref['jenis_pendaftaran'] as $jpId => $jpLabel)
                                    <option value="{{ $jpId }}"
                                        {{ old('jenis_pendaftaran_id', $identitas->jenis_pendaftaran_id ?: $pd->jenis_pendaftaran_id) == $jpId ? 'selected' : '' }}>
                                        {{ $jpLabel }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Tanggal Masuk Sekolah -->
                        <div>
                            <label class="form-label" style="font-size: var(--text-sm, 0.8125rem); font-weight: 700;">Tanggal Masuk
                                Sekolah</label>
                            <input type="date" name="tanggal_masuk_sekolah" class="form-control"
                                value="{{ old('tanggal_masuk_sekolah', $identitas->tanggal_masuk_sekolah ? $identitas->tanggal_masuk_sekolah->format('Y-m-d') : '') }}"
                                style="font-size: var(--text-base, 0.875rem);">
                        </div>

                        <!-- Program / Kompetensi Keahlian -->
                        <div>
                            <label class="form-label" style="font-size: var(--text-sm, 0.8125rem); font-weight: 700;">Program / Kompetensi
                                Keahlian</label>
                            <input type="text" name="program_keahlian" class="form-control"
                                value="{{ old('program_keahlian', $identitas->program_keahlian) }}"
                                placeholder="Contoh: Teknik Komputer dan Jaringan" style="font-size: var(--text-base, 0.875rem);">
                        </div>

                        <!-- Sekolah Asal -->
                        <div>
                            <label class="form-label" style="font-size: var(--text-sm, 0.8125rem); font-weight: 700;">Sekolah Asal (SMP /
                                MTs)</label>
                            <input type="text" name="sekolah_asal" class="form-control"
                                value="{{ old('sekolah_asal', $identitas->sekolah_asal ?: $pd->sekolah_asal) }}"
                                style="font-size: var(--text-base, 0.875rem);">
                        </div>
                    </div>

                    <div
                        style="display: flex; gap: 24px; flex-wrap: wrap; padding-top: 10px; border-top: 1px dashed var(--border-color);">
                        <div style="font-size: var(--text-base, 0.875rem);">
                            <strong>PAUD Formal (TK / RA)</strong>
                            <label style="margin-left: 12px; cursor: pointer;"><input type="radio"
                                    name="pernah_paud_formal" value="1" required
                                    {{ old('pernah_paud_formal', $identitas->pernah_paud_formal) ? 'checked' : '' }}>
                                Ya</label>
                            <label style="margin-left: 8px; cursor: pointer;"><input type="radio"
                                    name="pernah_paud_formal" value="0" required
                                    {{ old('pernah_paud_formal', $identitas->pernah_paud_formal) == 0 ? 'checked' : '' }}>
                                Tidak</label>
                        </div>

                        <div style="font-size: var(--text-base, 0.875rem);">
                            <strong>PAUD Non Formal (KB / TPA / SPS)</strong>
                            <label style="margin-left: 12px; cursor: pointer;"><input type="radio"
                                    name="pernah_paud_non_formal" value="1" required
                                    {{ old('pernah_paud_non_formal', $identitas->pernah_paud_non_formal) ? 'checked' : '' }}>
                                Ya</label>
                            <label style="margin-left: 8px; cursor: pointer;"><input type="radio"
                                    name="pernah_paud_non_formal" value="0" required
                                    {{ old('pernah_paud_non_formal', $identitas->pernah_paud_non_formal) == 0 ? 'checked' : '' }}>
                                Tidak</label>
                        </div>
                    </div>

                    <div
                        style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px; margin-top: 16px; padding-top: 10px; border-top: 1px dashed var(--border-color);">
                        <div>
                            <label class="form-label" style="font-size: var(--text-sm, 0.8125rem); font-weight: 700;">Hobi / Kegemaran
                                Siswa</label>
                            <select name="hobi_id" class="form-select" style="font-size: var(--text-base, 0.875rem);">
                                @foreach ($ref['hobi'] as $hbId => $hbName)
                                    <option value="{{ $hbId }}"
                                        {{ old('hobi_id', $identitas->hobi_id) == $hbId ? 'selected' : '' }}>
                                        {{ $hbName }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="form-label" style="font-size: var(--text-sm, 0.8125rem); font-weight: 700;">Cita-cita / Impian
                                Profesi</label>
                            <select name="cita_cita_id" class="form-select" style="font-size: var(--text-base, 0.875rem);">
                                @foreach ($ref['cita_cita'] as $ctId => $ctName)
                                    <option value="{{ $ctId }}"
                                        {{ old('cita_cita_id', $identitas->cita_cita_id) == $ctId ? 'selected' : '' }}>
                                        {{ $ctName }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
