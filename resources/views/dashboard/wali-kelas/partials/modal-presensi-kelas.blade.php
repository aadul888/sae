    <!-- Modal Unduh Laporan PDF Multi-Periode -->
    <div id="modalDownloadPdf" class="modal-backdrop"
        style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.65); backdrop-filter: blur(4px); z-index: 99999 !important; align-items: center; justify-content: center;">
        <div class="card"
            style="width: 100%; max-width: 520px; margin: 16px; padding: 24px; border-radius: var(--radius-xl, 16px); box-shadow: 0 20px 40px rgba(0,0,0,0.5); border: 1px solid var(--border-color); background: var(--bg-card);">
            <div
                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; border-bottom: 1px solid var(--border-color); padding-bottom: 12px;">
                <h3 style="font-size: var(--text-xl, 1.25rem); font-weight: 700; color: var(--text-color); margin: 0;">
                    <i class="fas fa-print text-primary me-2"></i> Cetak Laporan Presensi Kelas
                </h3>
                <button type="button" id="btnCloseModalPdf" class="btn-icon"
                    style="background: none; border: none; font-size: var(--text-lg, 1.125rem); color: var(--text-muted); cursor: pointer;">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form id="formDownloadPdf" method="GET"
                action="{{ route('dashboard.wali-kelas.presensi.pdf', ['tipe' => 'siswa']) }}" target="_blank">
                <div style="margin-bottom: 14px;">
                    <label for="selectTipeLaporan"
                        style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 600; color: var(--text-color); margin-bottom: 6px;">
                        Jenis Laporan Presensi <span style="color: var(--danger);">*</span>
                    </label>
                    <select id="selectTipeLaporan" class="form-control"
                        style="width: 100%; font-size: var(--text-base, 0.875rem); padding: 8px 12px; border-radius: var(--radius-md, 8px);">
                        <option value="siswa" selected>1. Laporan Per Peserta Didik (Kartu Presensi Individu)</option>
                        <option value="hari">2. Laporan Per Hari (Presensi Harian Kelas)</option>
                        <option value="bulan">3. Laporan Per Bulan (Matriks Presensi Bulanan)</option>
                        <option value="semester">4. Laporan Per Semester (Rekapitulasi Semester)</option>
                        <option value="tahun">5. Laporan Per Tahun (Rekapitulasi Tahun Ajaran)</option>
                    </select>
                </div>

                <!-- 1. Filter Peserta Didik Dropdown -->
                <div id="groupFilterSiswa" style="margin-bottom: 14px;">
                    <label
                        style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 600; color: var(--text-color); margin-bottom: 6px;">
                        Pilih Peserta Didik <span style="color: var(--danger);">*</span>
                    </label>
                    <select name="siswa_id" class="form-control"
                        style="width: 100%; font-size: var(--text-base, 0.875rem); padding: 8px 12px; border-radius: var(--radius-md, 8px);">
                        @foreach ($allSiswa ?? $list as $s)
                            <option value="{{ $s->peserta_didik_id }}">{{ $s->nama }} ({{ $s->nisn ?: '-' }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- 2. Filter Tanggal -->
                <div id="groupFilterTanggal" style="margin-bottom: 14px; display: none;">
                    <label
                        style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 600; color: var(--text-color); margin-bottom: 6px;">
                        Tanggal Presensi
                    </label>
                    <input type="date" name="tanggal" value="{{ $tanggal }}" class="form-control"
                        style="width: 100%; font-size: var(--text-base, 0.875rem); padding: 8px 12px; border-radius: var(--radius-md, 8px);">
                </div>

                <!-- 3. Filter Bulan -->
                <div id="groupFilterBulan" style="margin-bottom: 14px;">
                    <label
                        style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 600; color: var(--text-color); margin-bottom: 6px;">
                        Bulan &amp; Tahun
                    </label>
                    <input type="month" name="bulan" value="{{ date('Y-m') }}" class="form-control"
                        style="width: 100%; font-size: var(--text-base, 0.875rem); padding: 8px 12px; border-radius: var(--radius-md, 8px);">
                </div>

                <!-- 4. Filter Semester -->
                <div id="groupFilterSemester" style="margin-bottom: 14px; display: none;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                        <div>
                            <label
                                style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 600; color: var(--text-color); margin-bottom: 6px;">Semester</label>
                            <select name="semester" class="form-control"
                                style="width: 100%; font-size: var(--text-base, 0.875rem); padding: 8px 12px; border-radius: var(--radius-md, 8px);">
                                <option value="1" {{ now()->month >= 7 ? 'selected' : '' }}>Semester Ganjil (1)
                                </option>
                                <option value="2" {{ now()->month < 7 ? 'selected' : '' }}>Semester Genap (2)
                                </option>
                            </select>
                        </div>
                        <div>
                            <label
                                style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 600; color: var(--text-color); margin-bottom: 6px;">Tahun</label>
                            <input type="number" name="tahun" value="{{ now()->year }}" class="form-control"
                                style="width: 100%; font-size: var(--text-base, 0.875rem); padding: 8px 12px; border-radius: var(--radius-md, 8px);">
                        </div>
                    </div>
                </div>

                <!-- 5. Filter Tahun Ajaran -->
                <div id="groupFilterTahun" style="margin-bottom: 14px; display: none;">
                    <label
                        style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 600; color: var(--text-color); margin-bottom: 6px;">
                        Tahun Pelajaran
                    </label>
                    @php
                        $currTahunAjar = now()->month >= 7 ? now()->year : now()->year - 1;
                    @endphp
                    <select name="tahun_ajaran" class="form-control"
                        style="width: 100%; font-size: var(--text-base, 0.875rem); padding: 8px 12px; border-radius: var(--radius-md, 8px);">
                        @for ($y = $currTahunAjar + 1; $y >= $currTahunAjar - 3; $y--)
                            <option value="{{ $y }}" {{ $y == $currTahunAjar ? 'selected' : '' }}>Tahun
                                Pelajaran {{ $y }}/{{ $y + 1 }}</option>
                        @endfor
                    </select>
                </div>

                <div
                    style="background: rgba(99,102,241,0.06); border: 1px solid rgba(99,102,241,0.2); border-radius: var(--radius-md, 8px); padding: 10px 12px; margin-bottom: 18px; font-size: var(--text-sm, 0.8125rem); color: var(--text-muted);">
                    <i class="fas fa-info-circle text-primary me-1"></i> Dokumen PDF dilengkapi dengan <strong>Kop Surat
                        Sekolah Resmi</strong>, identitas kelas binaan, dan blok tanda tangan Wali Kelas serta Kepala
                    Sekolah.
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 8px;">
                    <button type="button" class="btn btn-outline"
                        onclick="document.getElementById('modalDownloadPdf').style.display='none'"
                        style="padding: 8px 16px; font-size: var(--text-base, 0.875rem);">
                        Batal
                    </button>
                    <button type="submit" class="btn btn-primary"
                        style="padding: 8px 18px; font-size: var(--text-base, 0.875rem); font-weight: 600;">
                        <i class="fas fa-print me-1"></i> Buka Lembar Cetak
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Verifikasi / Approval Izin & Sakit (z-index 99999 !important) -->
    <div id="modalVerifikasiIzin" class="modal-backdrop"
        style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.68); backdrop-filter: blur(4px); z-index: 99999 !important; align-items: center; justify-content: center;">
        <div class="card"
            style="width: 100%; max-width: 480px; margin: 16px; padding: 22px; border-radius: var(--radius-xl, 16px); box-shadow: 0 20px 40px rgba(0,0,0,0.5); border: 1px solid var(--border-color); background: var(--bg-card);">
            <div
                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">
                <h3
                    style="font-size: var(--text-lg, 1.125rem); font-weight: 700; color: var(--text-color); margin: 0; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-signature text-primary"></i> Verifikasi Surat Izin / Sakit
                </h3>
                <button type="button" id="btnCloseModalVerifikasi" class="btn-icon"
                    style="background: none; border: none; font-size: var(--text-xl, 1.25rem); color: var(--text-muted); cursor: pointer;">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form id="formSubmitVerifikasiIzin">
                <input type="hidden" id="verifIzinId">

                <div
                    style="background: rgba(255,255,255,0.03); border: 1px solid var(--border-color); border-radius: var(--radius-lg, 12px); padding: 12px 14px; margin-bottom: 16px;">
                    <div
                        style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); text-transform: uppercase; font-weight: 700;">
                        Peserta Didik</div>
                    <div id="verifSiswaNama"
                        style="font-size: var(--text-md, 1rem); font-weight: 800; color: var(--text-color); margin-top: 2px;">-</div>
                    <div
                        style="display: flex; gap: 12px; margin-top: 6px; font-size: var(--text-sm, 0.8125rem); color: var(--text-muted);">
                        <span>Jenis: <strong id="verifJenisSurat" style="color: var(--primary);">-</strong></span>
                        <span>Periode: <strong id="verifRentangTgl"
                                style="color: var(--text-color);">-</strong></span>
                    </div>
                </div>

                <div style="margin-bottom: 16px;">
                    <label
                        style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-color); margin-bottom: 8px;">
                        Keputusan Wali Kelas: <span style="color: var(--danger, #ef4444);">*</span>
                    </label>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                        <label
                            style="display: flex; align-items: center; gap: 8px; padding: 10px 14px; border-radius: var(--radius-lg, 12px); border: 1.5px solid rgba(16,185,129,0.3); background: rgba(16,185,129,0.08); cursor: pointer;">
                            <input type="radio" name="verif_status" value="disetujui" checked
                                style="accent-color: var(--success, #10b981);">
                            <span style="font-weight: 700; color: var(--success, #10b981); font-size: var(--text-base, 0.875rem);"><i
                                    class="fas fa-check-circle me-1"></i> Setujui</span>
                        </label>
                        <label
                            style="display: flex; align-items: center; gap: 8px; padding: 10px 14px; border-radius: var(--radius-lg, 12px); border: 1.5px solid rgba(239,68,68,0.3); background: rgba(239,68,68,0.08); cursor: pointer;">
                            <input type="radio" name="verif_status" value="ditolak"
                                style="accent-color: var(--danger, #ef4444);">
                            <span style="font-weight: 700; color: var(--danger, #ef4444); font-size: var(--text-base, 0.875rem);"><i
                                    class="fas fa-times-circle me-1"></i> Tolak</span>
                        </label>
                    </div>
                </div>

                <div style="margin-bottom: 18px;">
                    <label
                        style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-color); margin-bottom: 6px;">
                        Catatan Petugas / Alasan (Opsional):
                    </label>
                    <textarea id="verifCatatan" rows="3" class="form-control"
                        placeholder="Tuliskan catatan verifikasi atau alasan jika permohonan ditolak..."
                        style="width: 100%; font-size: var(--text-base, 0.875rem); padding: 9px 12px; border-radius: var(--radius-md, 8px);"></textarea>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 8px;">
                    <button type="button" id="btnCancelVerifikasi" class="btn btn-outline"
                        style="padding: 8px 16px; font-size: var(--text-base, 0.875rem);">
                        Batal
                    </button>
                    <button type="submit" id="btnSaveVerifikasi" class="btn btn-primary"
                        style="padding: 8px 20px; font-size: var(--text-base, 0.875rem); font-weight: 700;">
                        <i class="fas fa-save me-1"></i> Simpan Keputusan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Pratinjau Dokumen Lampiran (z-index 99999 !important) -->
    <div id="modalPreviewLampiranWali" class="modal-backdrop"
        style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.72); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
        <div class="card"
            style="max-width: 680px; width: 92%; max-height: 90vh; margin: auto; padding: 20px; border-radius: var(--radius-xl, 16px); display: flex; flex-direction: column; border: 1px solid var(--border-color); box-shadow: 0 16px 40px rgba(0,0,0,0.5);">
            <div
                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; border-bottom: 1px solid var(--border-color); padding-bottom: 8px;">
                <h4 id="previewLampiranWaliTitle"
                    style="font-size: var(--text-md, 1rem); font-weight: 800; color: var(--text-color); margin: 0;">
                    Berkas Lampiran
                </h4>
                <button type="button" id="btnClosePreviewLampiranWali"
                    style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: var(--text-xl, 1.25rem);">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div id="previewContainerWali"
                style="flex: 1; overflow: auto; text-align: center; min-height: 240px; display: flex; align-items: center; justify-content: center;">
                <!-- Konten Gambar / PDF dinamis -->
            </div>
        </div>
    </div>

    <!-- Modal Form Pencatatan Izin / Sakit Harian oleh Wali Kelas -->
    <div id="modalIzinSakitWali" class="modal-backdrop"
        style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.65); backdrop-filter: blur(4px); z-index: 99999 !important; align-items: center; justify-content: center; padding: 12px; box-sizing: border-box;">
        <div class="card"
            style="max-width: 480px; width: 92%; margin: auto; padding: 22px; border-radius: var(--radius-xl, 16px); box-shadow: 0 16px 40px rgba(0,0,0,0.5); border: 1px solid var(--border-color); background: var(--bg-card);">
            <div
                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">
                <h3 id="titleModalIzinWali"
                    style="font-size: var(--text-lg, 1.125rem); font-weight: 800; color: var(--text-color); margin: 0; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-notes-medical text-primary"></i> Pencatatan Izin / Sakit Harian
                </h3>
                <button type="button" id="btnCloseIzinModalWali"
                    style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: var(--text-xl, 1.25rem);">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="formIzinSakitWali">
                <input type="hidden" id="izinWaliPdId">
                <input type="hidden" id="izinWaliAction">
                <div style="margin-bottom: 14px;">
                    <label
                        style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 600; color: var(--text-muted); margin-bottom: 4px;">Peserta
                        Didik:</label>
                    <div id="izinWaliPdNama" style="font-size: var(--text-md, 1rem); font-weight: 700; color: var(--text-color);">-
                    </div>
                </div>
                <div style="margin-bottom: 16px;">
                    <label for="izinWaliKeteranganInput"
                        style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 600; color: var(--text-color); margin-bottom: 6px;">
                        Keterangan / Alasan <span style="color: var(--danger);">*</span>:
                    </label>
                    <textarea id="izinWaliKeteranganInput" rows="3" required class="form-control"
                        placeholder="Tuliskan keterangan izin atau surat dokter..."
                        style="width: 100%; font-size: var(--text-base, 0.875rem); border-radius: var(--radius-md, 8px); resize: vertical;"></textarea>
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 8px;">
                    <button type="button" id="btnCancelIzinModalWali" class="btn btn-outline"
                        style="padding: 7px 14px; font-size: var(--text-sm, 0.8125rem); border-radius: var(--radius-md, 8px);">Batal</button>
                    <button type="submit" id="btnSaveIzinModalWali" class="btn btn-primary"
                        style="padding: 7px 18px; font-size: var(--text-sm, 0.8125rem); border-radius: var(--radius-md, 8px); font-weight: 600;">
                        <i class="fas fa-check me-1"></i> Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
