    <!-- MODAL 1: DETAIL BIODATA LENGKAP PESERTA DIDIK (Persis seperti Manajemen Data) -->
    <div id="biodataModal" class="modal-backdrop"
        style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
        <div class="card"
            style="max-width: 720px; width: 94%; max-height: 85vh; display: flex; flex-direction: column; margin: 0; border-radius: 14px; padding: 22px;">
            <div
                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; padding-bottom: 12px; border-bottom: 1px solid var(--border-color);">
                <div style="display: flex; align-items: center; gap: 14px;">
                    <div id="bioFotoContainer"
                        style="width: 50px; height: 50px; border-radius: 12px; background: rgba(99,102,241,0.12); display: flex; align-items: center; justify-content: center; overflow: hidden; border: 1.5px solid var(--border-color); flex-shrink: 0; box-shadow: 0 2px 8px rgba(0,0,0,0.25);">
                        <i class="fas fa-user-graduate text-primary" style="font-size: 1.3rem;"></i>
                    </div>
                    <div>
                        <h3 id="bioNama"
                            style="font-size: 1.05rem; font-weight: 700; color: var(--text-color); margin: 0;">
                            Biodata Peserta Didik</h3>
                        <div id="bioRombel" style="font-size: 0.8rem; color: var(--text-muted); margin-top: 2px;">-
                        </div>
                    </div>
                </div>
                <button type="button" class="close-modal" data-target="#biodataModal"
                    style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 1.1rem; padding: 4px; display: inline-flex; align-items: center; justify-content: center; transition: color 0.2s ease;">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div id="bioLoading" style="text-align: center; padding: 40px; color: var(--text-muted);">
                <i class="fas fa-spinner fa-spin me-2" style="font-size: 1.4rem;"></i>
                <div>Memuat biodata peserta didik...</div>
            </div>

            <div id="bioContent" style="overflow-y: auto; flex: 1; display: none; font-size: 0.84rem;">
                <div style="margin-bottom: 12px;">
                    <div
                        style="font-weight: 700; color: var(--primary); font-size: 0.82rem; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.5px;">
                        <i class="fas fa-user me-1"></i> Data Pribadi &amp; Fisik
                    </div>
                    <table style="width: 100%; border-collapse: collapse; margin-bottom: 8px;">
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted); width: 140px;">NISN / NIPD</td>
                            <td id="bioNisn" style="font-weight: 600; font-family: monospace; color: var(--primary);">
                                -
                            </td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted);">NIK</td>
                            <td id="bioNik" style="font-family: monospace;">-</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted);">Jenis Kelamin</td>
                            <td id="bioJk">-</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted);">Tempat, Tgl Lahir</td>
                            <td id="bioTtl">-</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted);">Agama</td>
                            <td id="bioAgama">-</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted);">Anak Keberapa</td>
                            <td id="bioAnak">-</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted);">Tinggi / Berat Badan</td>
                            <td id="bioFisik" style="font-weight: 600; color: #10b981;">-</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted);">Kebutuhan Khusus</td>
                            <td id="bioKhusus">-</td>
                        </tr>
                    </table>
                </div>

                <div style="margin-bottom: 12px; padding-top: 8px; border-top: 1px dashed var(--border-color);">
                    <div
                        style="font-weight: 700; color: var(--primary); font-size: 0.82rem; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.5px;">
                        <i class="fas fa-graduation-cap me-1"></i> Data Akademik &amp; Pendaftaran
                    </div>
                    <table style="width: 100%; border-collapse: collapse; margin-bottom: 8px;">
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted); width: 140px;">Pendaftaran / Asal</td>
                            <td id="bioPendaftaran">-</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted);">Tanggal Masuk</td>
                            <td id="bioTglMasuk">-</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted);">ID Registrasi / Anggota</td>
                            <td id="bioRegId"
                                style="font-family: monospace; font-size: 0.78rem; color: var(--text-muted);">-</td>
                        </tr>
                    </table>
                </div>

                <div style="margin-bottom: 12px; padding-top: 8px; border-top: 1px dashed var(--border-color);">
                    <div
                        style="font-weight: 700; color: var(--primary); font-size: 0.82rem; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.5px;">
                        <i class="fas fa-users me-1"></i> Data Orang Tua &amp; Wali
                    </div>
                    <table style="width: 100%; border-collapse: collapse; margin-bottom: 8px;">
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted); width: 140px;">Nama Ayah</td>
                            <td id="bioAyah">-</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted);">Nama Ibu</td>
                            <td id="bioIbu">-</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted);">Nama Wali</td>
                            <td id="bioWali">-</td>
                        </tr>
                    </table>
                </div>

                <div style="margin-bottom: 12px; padding-top: 8px; border-top: 1px dashed var(--border-color);">
                    <div
                        style="font-weight: 700; color: var(--primary); font-size: 0.82rem; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.5px;">
                        <i class="fas fa-address-book me-1"></i> Kontak &amp; Domisili
                    </div>
                    <table style="width: 100%; border-collapse: collapse; margin-bottom: 8px;">
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted); width: 140px;">Kontak (HP/Email)</td>
                            <td id="bioHp">-</td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 6px 0; color: var(--text-muted);">Alamat Jalan</td>
                            <td id="bioAlamat">-</td>
                        </tr>
                    </table>
                </div>

                <div id="bioMapelSection"
                    style="margin-top: 14px; padding-top: 12px; border-top: 1px solid var(--border-color); display: none;">
                    <div
                        style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                        <span style="font-weight: 700; color: var(--text-color); font-size: 0.85rem;"><i
                                class="fas fa-book-open text-primary me-1"></i> Mata Pelajaran di Kelas</span>
                        <span id="bioJmlMapel" class="badge"
                            style="background: rgba(99,102,241,0.15); color: var(--primary); font-size: 0.72rem; padding: 2px 7px;">0
                            Mapel</span>
                    </div>
                    <div style="overflow-x: auto; max-height: 180px;">
                        <table class="table"
                            style="width: 100%; border-collapse: collapse; font-size: 0.80rem; margin-bottom: 0;">
                            <thead>
                                <tr
                                    style="background: rgba(255,255,255,0.02); border-bottom: 1px solid var(--border-color);">
                                    <th style="padding: 6px 10px; color: var(--text-muted);">Mata Pelajaran</th>
                                    <th style="padding: 6px 10px; color: var(--text-muted);">Guru Pengampu</th>
                                    <th style="padding: 6px 10px; color: var(--text-muted); text-align: center;">Jam
                                    </th>
                                </tr>
                            </thead>
                            <tbody id="bioMapelList"></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div
                style="display: flex; justify-content: flex-end; margin-top: 14px; padding-top: 12px; border-top: 1px solid var(--border-color);">
                <button type="button" class="btn btn-outline close-modal" data-target="#biodataModal"
                    style="padding: 8px 18px; font-size: 0.82rem;">Tutup</button>
            </div>
        </div>
    </div>

    <!-- MODAL 2: AJUKAN USULAN PERUBAHAN DATA (Clean Global SAE Modal Style) -->
    <div id="modalUsulan" class="modal-backdrop"
        style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
        <div class="card" style="width: 100%; max-width: 520px; padding: 24px; border-radius: 12px; margin: 16px;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px;">
                <h3 style="font-size: 1.15rem; font-weight: 800; color: var(--text-color); margin: 0;">Ajukan Usulan
                    Perubahan Data</h3>
                <button type="button" class="close-modal" data-target="#modalUsulan"
                    style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 1.1rem; padding: 4px; display: inline-flex; align-items: center; justify-content: center; transition: color 0.2s ease;"><i
                        class="fas fa-times"></i></button>
            </div>
            <form id="formUsulan" method="POST"
                action="{{ route('dashboard.kesiswaan.peserta-didik.usulan.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="form-group" style="margin-bottom: 14px;">
                    <label
                        style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">Pilih
                        Siswa <span class="text-danger">*</span></label>
                    <input type="search" id="usulanSiswaLookup" class="form-control"
                        placeholder="Cari nama, NISN, atau NIPD..." autocomplete="off"
                        data-people-search="{{ route('dashboard.people.search') }}" data-people-kind="siswa"
                        data-people-target="#usulanSiswaId" data-people-require-selection="true" required
                        style="width: 100%; border-radius: 8px;">
                    <input type="hidden" id="usulanSiswaId" name="peserta_didik_id">
                </div>
                <div class="form-group" style="margin-bottom: 14px;">
                    <label
                        style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">Kolom
                        yang Ingin Diubah <span class="text-danger">*</span></label>
                    <select name="kolom_perubahan" class="form-control" required
                        style="width: 100%; border-radius: 8px;">
                        <option value="nama">Nama Lengkap</option>
                        <option value="nisn">NISN</option>
                        <option value="nik">NIK</option>
                        <option value="tempat_lahir">Tempat Lahir</option>
                        <option value="tanggal_lahir">Tanggal Lahir</option>
                        <option value="nama_ibu">Nama Ibu Kandung</option>
                        <option value="nama_ayah">Nama Ayah</option>
                        <option value="alamat_jalan">Alamat</option>
                    </select>
                </div>
                <div class="form-group" style="margin-bottom: 14px;">
                    <label
                        style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">Nilai
                        Baru yang Benar <span class="text-danger">*</span></label>
                    <input type="text" name="nilai_baru" class="form-control"
                        placeholder="Tuliskan data yang benar sesuai dokumen resmi" required
                        style="width: 100%; border-radius: 8px;">
                </div>
                <div class="form-group" style="margin-bottom: 14px;">
                    <label
                        style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">Alasan
                        Perubahan <span class="text-danger">*</span></label>
                    <input type="text" name="alasan" class="form-control"
                        placeholder="Contoh: Menyesuaikan Akta Kelahiran asli" required
                        style="width: 100%; border-radius: 8px;">
                </div>
                <div class="form-group" style="margin-bottom: 18px;">
                    <label
                        style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">Unggah
                        Berkas Bukti (PDF / Foto Akta / KK)</label>
                    <input type="file" name="berkas_bukti" class="form-control" accept=".pdf,.jpg,.jpeg,.png"
                        style="width: 100%; border-radius: 8px;">
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 8px;">
                    <button type="button" class="btn btn-outline close-modal" data-target="#modalUsulan"
                        style="border-radius: 8px;">Batal</button>
                    <button type="submit" class="btn btn-primary" style="border-radius: 8px;"><i
                            class="fas fa-paper-plane"></i> Kirim Usulan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 3: VERIFIKASI BERKAS DIGITAL SISWA (PDF ONLY, 2 STATUS) -->
    <div id="modalVerifikasiBerkasDigital" class="modal-backdrop"
        style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
        <div class="card"
            style="max-width: 860px; width: 94%; max-height: 88vh; display: flex; flex-direction: column; margin: 0; border-radius: 14px; padding: 22px;">
            <div
                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; padding-bottom: 12px; border-bottom: 1px solid var(--border-color);">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div
                        style="width: 44px; height: 44px; border-radius: 10px; background: rgba(99,102,241,0.12); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 1.3rem; flex-shrink: 0;">
                        <i class="fas fa-folder-open"></i>
                    </div>
                    <div>
                        <h3 id="modalVbNama"
                            style="font-size: 1.05rem; font-weight: 800; color: var(--text-color); margin: 0;">
                            Validasi Berkas Persyaratan Siswa</h3>
                        <div id="modalVbInfo" style="font-size: 0.8rem; color: var(--text-muted); margin-top: 2px;">
                            Memuat data siswa...
                        </div>
                    </div>
                </div>
                <button type="button" class="close-modal" data-target="#modalVerifikasiBerkasDigital"
                    style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 1.1rem; padding: 4px; display: inline-flex; align-items: center; justify-content: center; transition: color 0.2s ease;">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div id="modalVbLoading" style="text-align: center; padding: 40px; color: var(--text-muted);">
                <i class="fas fa-spinner fa-spin me-2" style="font-size: 1.4rem;"></i>
                <div>Memuat daftar berkas digital siswa...</div>
            </div>

            <div id="modalVbContent" style="overflow-y: auto; flex: 1; display: none; padding-right: 4px;">
                <!-- Diisi secara dinamis via JavaScript -->
            </div>

            <div
                style="display: flex; justify-content: space-between; align-items: center; margin-top: 14px; padding-top: 12px; border-top: 1px solid var(--border-color);">
                <div style="font-size: 0.76rem; color: var(--text-muted);">
                    <i class="fas fa-circle-info text-primary me-1"></i>
                    Status berkas hanya 2 pilihan: <strong>Valid / Sesuai</strong> atau <strong>Tidak Valid / Tidak
                        Sesuai</strong>.
                </div>
                <button type="button" class="btn btn-outline close-modal"
                    data-target="#modalVerifikasiBerkasDigital"
                    style="border-radius: 8px; padding: 7px 16px; font-size: 0.84rem;">
                    Tutup
                </button>
            </div>
        </div>
    </div>

    <!-- MODAL 4: DETAIL LENGKAP & PENGELOLAAN USULAN PERUBAHAN DATA (FIELD ASAL VS PENYESUAIAN) -->
    <div id="modalPengelolaanUsulan" class="modal-backdrop"
        style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.65); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
        <div class="card"
            style="max-width: 860px; width: 95%; max-height: 88vh; display: flex; flex-direction: column; margin: 0; border-radius: 14px; padding: 22px;">
            <div
                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; padding-bottom: 12px; border-bottom: 1px solid var(--border-color);">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div
                        style="width: 44px; height: 44px; border-radius: 10px; background: rgba(99,102,241,0.12); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 1.3rem; flex-shrink: 0;">
                        <i class="fas fa-file-pen"></i>
                    </div>
                    <div>
                        <h3 id="modalUsulanNama"
                            style="font-size: 1.05rem; font-weight: 800; color: var(--text-color); margin: 0;">
                            Pengelolaan Usulan Perubahan Data</h3>
                        <div id="modalUsulanSubtitle"
                            style="font-size: 0.8rem; color: var(--text-muted); margin-top: 2px;">
                            Memuat data usulan siswa...
                        </div>
                    </div>
                </div>
                <button type="button" class="close-modal" data-target="#modalPengelolaanUsulan"
                    style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 1.1rem; padding: 4px; display: inline-flex; align-items: center; justify-content: center; transition: color 0.2s ease;">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div id="modalUsulanLoading" style="text-align: center; padding: 40px; color: var(--text-muted);">
                <i class="fas fa-spinner fa-spin me-2" style="font-size: 1.4rem;"></i>
                <div>Memuat rincian usulan perubahan data...</div>
            </div>

            <div id="modalUsulanContent" style="overflow-y: auto; flex: 1; display: none; padding-right: 4px;">
                <!-- Diisi secara dinamis via JavaScript -->
            </div>

            <div
                style="display: flex; justify-content: space-between; align-items: center; margin-top: 14px; padding-top: 12px; border-top: 1px solid var(--border-color);">
                <div style="font-size: 0.76rem; color: var(--text-muted);">
                    <i class="fas fa-shield-halved text-success me-1"></i>
                    Verifikasi keputusan perubahan data resmi untuk sinkronisasi Dapodik.
                </div>
                <button type="button" class="btn btn-outline close-modal" data-target="#modalPengelolaanUsulan"
                    style="border-radius: 8px; padding: 7px 16px; font-size: 0.84rem;">
                    Tutup
                </button>
            </div>
        </div>
    </div>

    <!-- MODAL 4: VIEWER DOKUMEN PDF TERPADU (TANPA MEMBUKA TAB BARU) -->
    <div id="modalPdfViewer" class="modal-backdrop"
        style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.78); z-index: 100005 !important; align-items: center; justify-content: center; backdrop-filter: blur(5px);">
        <div class="card"
            style="max-width: 960px; width: 96%; height: 90vh; max-height: 90vh; display: flex; flex-direction: column; margin: 0; border-radius: 14px; padding: 18px;">
            <div
                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; padding-bottom: 10px; border-bottom: 1px solid var(--border-color);">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div
                        style="width: 38px; height: 38px; border-radius: 8px; background: rgba(239,68,68,0.12); color: #ef4444; display: flex; align-items: center; justify-content: center; font-size: 1.15rem; flex-shrink: 0;">
                        <i class="fas fa-file-pdf"></i>
                    </div>
                    <div>
                        <h3 id="pdfViewerTitle"
                            style="font-size: 1rem; font-weight: 800; color: var(--text-color); margin: 0;">
                            Pratinjau Dokumen PDF</h3>
                        <div id="pdfViewerSubtitle"
                            style="font-size: 0.74rem; color: var(--text-muted); margin-top: 2px;">
                            Review berkas langsung di dalam sistem tanpa membuka tab baru
                        </div>
                    </div>
                </div>
                <button type="button" class="close-pdf-viewer"
                    style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 1.2rem; padding: 4px; display: inline-flex; align-items: center; justify-content: center; transition: color 0.2s ease;">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div
                style="flex: 1; min-height: 0; border-radius: 10px; overflow: hidden; background: #0f172a; border: 1px solid var(--border-color); position: relative;">
                <iframe id="pdfViewerFrame" src="" style="width: 100%; height: 100%; border: none;"></iframe>
            </div>

            <!-- Action Bar Validasi Terpadu Langsung di Pratinjau PDF -->
            <div id="pdfViewerActionBar"
                style="display: flex; justify-content: space-between; align-items: center; margin-top: 12px; padding-top: 10px; border-top: 1px solid var(--border-color); flex-wrap: wrap; gap: 10px;">
                <div id="pdfViewerVerifControls"
                    style="display: none; align-items: center; gap: 12px; flex-wrap: wrap;">
                    <span style="font-size: 0.8rem; font-weight: 700; color: var(--text-color);">
                        <i class="fas fa-clipboard-check text-primary me-1"></i> Validasi:
                    </span>
                    <label
                        style="display: inline-flex; align-items: center; gap: 6px; font-size: 0.82rem; font-weight: 700; color: #10b981; cursor: pointer; margin: 0;">
                        <input type="radio" name="pdf_viewer_status" value="valid" id="pdfViewerRadioValid"
                            class="pdf-viewer-radio">
                        <i class="fas fa-circle-check"></i> Valid / Sesuai
                    </label>
                    <label
                        style="display: inline-flex; align-items: center; gap: 6px; font-size: 0.82rem; font-weight: 700; color: #ef4444; cursor: pointer; margin: 0;">
                        <input type="radio" name="pdf_viewer_status" value="tidak_valid"
                            id="pdfViewerRadioTidakValid" class="pdf-viewer-radio">
                        <i class="fas fa-circle-xmark"></i> Tidak Valid / Tidak Sesuai
                    </label>
                    <input type="text" id="pdfViewerCatatan" placeholder="Catatan penolakan jika tidak valid..."
                        style="display: none; font-size: 0.78rem; height: 32px; padding: 4px 10px; border-radius: 6px; border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-color); width: 260px;">
                    <button type="button" id="btnPdfViewerSimpan" class="btn btn-primary"
                        style="padding: 6px 14px; font-size: 0.8rem; font-weight: 700; border-radius: 6px; display: inline-flex; align-items: center; gap: 6px;">
                        <i class="fas fa-save"></i> Simpan Status
                    </button>
                </div>
                <div style="display: flex; gap: 8px; margin-left: auto;">
                    <button type="button" class="btn btn-outline close-pdf-viewer"
                        style="border-radius: 8px; padding: 7px 18px; font-size: 0.82rem;">
                        Tutup Pratinjau
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Unggah Pasfoto Peserta Didik (Sesuai Modul Administrator) --}}
    @include('partials.modal-upload-foto-pd')

    {{-- Modal Pratinjau Kartu Pelajar Digital (Sesuai Modul Administrator) --}}
    @include('kartu-pelajar.modal-preview')

    {{-- Modal Preview Pasfoto Siswa Tidak Aktif --}}
    <div id="photoPreviewModal" class="modal-backdrop"
        style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.7); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);"
        onclick="if(typeof closePhotoPreviewModal === 'function') closePhotoPreviewModal();">
        <div class="card"
            style="max-width: 420px; width: 90%; text-align: center; border-radius: 16px; padding: 20px; box-shadow: 0 16px 40px rgba(0,0,0,0.5);"
            onclick="event.stopPropagation();">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
                <h4 id="photoPreviewName"
                    style="font-size: 0.95rem; font-weight: 700; color: var(--text-color); margin: 0;">Pasfoto</h4>
                <button type="button"
                    onclick="if(typeof closePhotoPreviewModal === 'function') closePhotoPreviewModal();"
                    style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 1.1rem;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div
                style="width: 100%; max-height: 480px; overflow: hidden; border-radius: 12px; border: 1px solid var(--border-color); background: #000; margin-bottom: 12px;">
                <img id="photoPreviewImg" src="" alt="Pasfoto"
                    style="width: 100%; height: auto; max-height: 480px; object-fit: contain;">
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/kartu-pelajar.css') }}">
@endpush

@push('scripts')
    <script
        src="{{ asset('js/peserta-didik-aktif.js') }}?v={{ file_exists(public_path('js/peserta-didik-aktif.js')) ? filemtime(public_path('js/peserta-didik-aktif.js')) : time() }}">
    </script>
    <script
        src="{{ asset('js/peserta-didik-bulk-foto.js') }}?v={{ file_exists(public_path('js/peserta-didik-bulk-foto.js')) ? filemtime(public_path('js/peserta-didik-bulk-foto.js')) : time() }}">
    </script>
    <script
        src="{{ asset('js/peserta-didik-actions.js') }}?v={{ file_exists(public_path('js/peserta-didik-actions.js')) ? filemtime(public_path('js/peserta-didik-actions.js')) : time() }}">
    </script>
    <script
        src="{{ asset('js/kesiswaan-peserta-didik.js') }}?v={{ file_exists(public_path('js/kesiswaan-peserta-didik.js')) ? filemtime(public_path('js/kesiswaan-peserta-didik.js')) : time() }}">
    </script>
    <script
        src="{{ asset('js/kesiswaan-usulan-manage.js') }}?v={{ file_exists(public_path('js/kesiswaan-usulan-manage.js')) ? filemtime(public_path('js/kesiswaan-usulan-manage.js')) : time() }}">
    </script>
@endpush
