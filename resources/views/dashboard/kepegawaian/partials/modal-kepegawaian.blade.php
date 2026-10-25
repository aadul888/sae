    <div class="modal-overlay" id="modalUploadBerkas"
        style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.65); backdrop-filter: blur(4px); z-index: 99999 !important; align-items: center; justify-content: center; padding: 20px;">
        <div class="modal-container"
            style="background: var(--card-bg); border: 1px solid var(--border-color); border-radius: var(--radius-xl, 16px); width: 100%; max-width: 580px; max-height: 90vh; overflow-y: auto; box-shadow: 0 25px 50px rgba(0,0,0,0.4); padding: 24px;">
            <div
                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; border-bottom: 1px solid var(--border-color); padding-bottom: 12px;">
                <h4
                    style="margin: 0; font-size: var(--text-lg, 1.125rem); color: var(--text-color); font-weight: 700; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-file-arrow-up text-primary"></i> Unggah Berkas Digital GTK
                </h4>
                <button type="button" id="btnCloseModalUpload"
                    style="background: none; border: none; font-size: var(--text-xl, 1.25rem); color: var(--text-muted); cursor: pointer;">&times;</button>
            </div>

            <form action="{{ route('dashboard.kepegawaian.berkas.upload') }}" method="POST"
                enctype="multipart/form-data" id="formUploadBerkas">
                @csrf
                <input type="hidden" name="tab_redirect" value="{{ $tab }}">
                <div class="form-group" style="margin-bottom: 14px;">
                    <label
                        style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Pilih
                        GTK Pegawai <span style="color: var(--danger, #ef4444);">*</span></label>
                    <select name="ptk_id" id="upload_ptk_id" class="form-control" required
                        style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: var(--radius-md, 8px); font-size: var(--text-base, 0.875rem);">
                        <option value="">-- Pilih Pegawai --</option>
                        @foreach ($allGtk as $g)
                            <option value="{{ $g->ptk_id }}">{{ $g->nama }} (NIP: {{ $g->nip ?: '-' }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group" style="margin-bottom: 14px;">
                    <label
                        style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Jenis
                        Dokumen <span style="color: var(--danger, #ef4444);">*</span></label>
                    <select name="jenis_dokumen" class="form-control" required
                        style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: var(--radius-md, 8px); font-size: var(--text-base, 0.875rem);">
                        @foreach ($jenisBerkasOptions as $k => $lbl)
                            <option value="{{ $k }}">{{ $lbl }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group" style="margin-bottom: 14px;">
                    <label
                        style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Judul
                        Dokumen / Nama Berkas <span style="color: var(--danger, #ef4444);">*</span></label>
                    <input type="text" name="judul_dokumen" class="form-control"
                        placeholder="Contoh: SK Kenaikan Pangkat Gol. IV/a" required
                        style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: var(--radius-md, 8px); font-size: var(--text-base, 0.875rem);">
                </div>

                <div class="form-group" style="margin-bottom: 14px;">
                    <label
                        style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Pilih
                        File Berkas (PDF, JPG, PNG, Maks 5MB) <span style="color: var(--danger, #ef4444);">*</span></label>
                    <input type="file" name="file_berkas" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required
                        style="width: 100%; padding: 8px 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: var(--radius-md, 8px); font-size: var(--text-base, 0.875rem);">
                </div>

                <div class="form-group" style="margin-bottom: 20px;">
                    <label
                        style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Catatan
                        / Keterangan (Opsional)</label>
                    <textarea name="keterangan" class="form-control" rows="2" placeholder="Catatan tambahan..."
                        style="width: 100%; padding: 8px 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: var(--radius-md, 8px); font-size: var(--text-base, 0.875rem);"></textarea>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 8px;">
                    <button type="button" id="btnCancelModalUpload" class="btn btn-outline"
                        style="padding: 8px 16px; border-radius: var(--radius-md, 8px); font-size: var(--text-base, 0.875rem);">Batal</button>
                    <button type="submit" class="btn btn-primary"
                        style="padding: 8px 18px; border-radius: var(--radius-md, 8px); font-size: var(--text-base, 0.875rem);"><i
                            class="fas fa-upload me-1"></i> Unggah Berkas</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 2: Catat / Update KGB Tracker -->
    <div class="modal-overlay" id="modalKgb"
        style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.65); backdrop-filter: blur(4px); z-index: 99999 !important; align-items: center; justify-content: center; padding: 20px;">
        <div class="modal-container"
            style="background: var(--card-bg); border: 1px solid var(--border-color); border-radius: var(--radius-xl, 16px); width: 100%; max-width: 600px; max-height: 90vh; overflow-y: auto; box-shadow: 0 25px 50px rgba(0,0,0,0.4); padding: 24px;">
            <div
                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; border-bottom: 1px solid var(--border-color); padding-bottom: 12px;">
                <h4
                    style="margin: 0; font-size: var(--text-lg, 1.125rem); color: var(--text-color); font-weight: 700; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-business-time text-primary"></i> Catat / Perbarui Kenaikan Gaji Berkala (KGB)
                </h4>
                <button type="button" id="btnCloseModalKgb"
                    style="background: none; border: none; font-size: var(--text-xl, 1.25rem); color: var(--text-muted); cursor: pointer;">&times;</button>
            </div>

            <form action="{{ route('dashboard.kepegawaian.kgb.store') }}" method="POST" id="formKgb">
                @csrf
                <div class="form-group" style="margin-bottom: 14px;">
                    <label
                        style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Pilih
                        GTK <span style="color: var(--danger, #ef4444);">*</span></label>
                    <select name="ptk_id" id="kgb_ptk_id" class="form-control" required
                        style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: var(--radius-md, 8px); font-size: var(--text-base, 0.875rem);">
                        <option value="">-- Pilih GTK --</option>
                        @foreach ($allGtk as $g)
                            <option value="{{ $g->ptk_id }}">{{ $g->nama }} (NIP: {{ $g->nip ?: '-' }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                    <div>
                        <label
                            style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Masa
                            Kerja (Tahun)</label>
                        <input type="number" name="mkg_tahun" id="kgb_mk_tahun" class="form-control"
                            placeholder="Tahun" min="0" value="0"
                            style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: var(--radius-md, 8px); font-size: var(--text-base, 0.875rem);">
                    </div>
                    <div>
                        <label
                            style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Masa
                            Kerja (Bulan)</label>
                        <input type="number" name="mkg_bulan" id="kgb_mk_bulan" class="form-control"
                            placeholder="Bulan" min="0" max="11" value="0"
                            style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: var(--radius-md, 8px); font-size: var(--text-base, 0.875rem);">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                    <div>
                        <label
                            style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">TMT
                            KGB Terakhir <span style="color: var(--danger, #ef4444);">*</span></label>
                        <input type="date" name="tmt_lama" id="kgb_tmt_lama" class="form-control" required
                            style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: var(--radius-md, 8px); font-size: var(--text-base, 0.875rem);">
                    </div>
                    <div>
                        <label
                            style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">TMT
                            Target (+2 Thn) <span style="color: var(--danger, #ef4444);">*</span></label>
                        <input type="date" name="tmt_baru_target" id="kgb_tmt_baru_target" class="form-control"
                            required
                            style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: var(--radius-md, 8px); font-size: var(--text-base, 0.875rem);">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                    <div>
                        <label
                            style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Nomor
                            SK Terakhir</label>
                        <input type="text" name="nomor_sk_terakhir" id="kgb_sk_terakhir" class="form-control"
                            placeholder="Contoh: 822/012/2024"
                            style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: var(--radius-md, 8px); font-size: var(--text-base, 0.875rem);">
                    </div>
                    <div>
                        <label
                            style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Status
                            Usulan <span style="color: var(--danger, #ef4444);">*</span></label>
                        <select name="status_usulan" id="kgb_status_usulan" class="form-control" required
                            style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: var(--radius-md, 8px); font-size: var(--text-base, 0.875rem);">
                            <option value="belum_waktunya">Belum Waktunya</option>
                            <option value="siap_diajukan">Siap Diajukan ke BKD</option>
                            <option value="diproses">Sedang Diproses BKD</option>
                            <option value="terbit_sk">Terbit SK KGB</option>
                        </select>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 20px;">
                    <label
                        style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Catatan
                        Tambahan</label>
                    <textarea name="catatan" id="kgb_catatan" class="form-control" rows="2" placeholder="Catatan berkas..."
                        style="width: 100%; padding: 8px 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: var(--radius-md, 8px); font-size: var(--text-base, 0.875rem);"></textarea>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 8px;">
                    <button type="button" id="btnCancelModalKgb" class="btn btn-outline"
                        style="padding: 8px 16px; border-radius: var(--radius-md, 8px); font-size: var(--text-base, 0.875rem);">Batal</button>
                    <button type="submit" class="btn btn-primary"
                        style="padding: 8px 18px; border-radius: var(--radius-md, 8px); font-size: var(--text-base, 0.875rem);"><i
                            class="fas fa-save me-1"></i> Simpan Data KGB</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 3: Form Cuti & Izin GTK -->
    <div class="modal-overlay" id="modalCuti"
        style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.65); backdrop-filter: blur(4px); z-index: 99999 !important; align-items: center; justify-content: center; padding: 20px;">
        <div class="modal-container"
            style="background: var(--card-bg); border: 1px solid var(--border-color); border-radius: var(--radius-xl, 16px); width: 100%; max-width: 600px; max-height: 90vh; overflow-y: auto; box-shadow: 0 25px 50px rgba(0,0,0,0.4); padding: 24px;">
            <div
                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; border-bottom: 1px solid var(--border-color); padding-bottom: 12px;">
                <h4
                    style="margin: 0; font-size: var(--text-lg, 1.125rem); color: var(--text-color); font-weight: 700; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-plane-departure text-primary"></i> Permohonan Cuti / Izin Pegawai
                </h4>
                <button type="button" id="btnCloseModalCuti"
                    style="background: none; border: none; font-size: var(--text-xl, 1.25rem); color: var(--text-muted); cursor: pointer;">&times;</button>
            </div>

            <form action="{{ route('dashboard.kepegawaian.cuti.store') }}" method="POST" enctype="multipart/form-data"
                id="formCuti">
                @csrf
                <div class="form-group" style="margin-bottom: 14px;">
                    <label
                        style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Pilih
                        Pegawai GTK <span style="color: var(--danger, #ef4444);">*</span></label>
                    <select name="ptk_id" id="cuti_ptk_id" class="form-control" required
                        style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: var(--radius-md, 8px); font-size: var(--text-base, 0.875rem);">
                        <option value="">-- Pilih GTK --</option>
                        @foreach ($allGtk as $g)
                            <option value="{{ $g->ptk_id }}">{{ $g->nama }} (NIP: {{ $g->nip ?: '-' }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group" style="margin-bottom: 14px;">
                    <label
                        style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Jenis
                        Permohonan <span style="color: var(--danger, #ef4444);">*</span></label>
                    <select name="jenis" id="cuti_jenis" class="form-control" required
                        style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: var(--radius-md, 8px); font-size: var(--text-base, 0.875rem);">
                        <option value="Cuti Tahunan">Cuti Tahunan</option>
                        <option value="Cuti Sakit">Cuti Sakit</option>
                        <option value="Cuti Melahirkan">Cuti Melahirkan</option>
                        <option value="Cuti Alasan Penting">Cuti Alasan Penting</option>
                        <option value="Cuti Besar">Cuti Besar</option>
                        <option value="Izin">Izin Tidak Masuk</option>
                    </select>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                    <div>
                        <label
                            style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Tanggal
                            Mulai <span style="color: var(--danger, #ef4444);">*</span></label>
                        <input type="date" name="tanggal_mulai" id="cuti_tgl_mulai" class="form-control" required
                            style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: var(--radius-md, 8px); font-size: var(--text-base, 0.875rem);">
                    </div>
                    <div>
                        <label
                            style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Tanggal
                            Selesai <span style="color: var(--danger, #ef4444);">*</span></label>
                        <input type="date" name="tanggal_selesai" id="cuti_tgl_selesai" class="form-control" required
                            style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: var(--radius-md, 8px); font-size: var(--text-base, 0.875rem);">
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 14px;">
                    <label
                        style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Keperluan
                        / Alasan <span style="color: var(--danger, #ef4444);">*</span></label>
                    <textarea name="keperluan" id="cuti_keperluan" class="form-control" rows="2"
                        placeholder="Jelaskan alasan permohonan cuti atau izin..." required
                        style="width: 100%; padding: 8px 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: var(--radius-md, 8px); font-size: var(--text-base, 0.875rem);"></textarea>
                </div>

                <div class="form-group" style="margin-bottom: 20px;">
                    <label
                        style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Surat
                        Keterangan Dokter / Pendukung (Opsional)</label>
                    <input type="file" name="surat_pendukung" class="form-control" accept=".pdf,.jpg,.jpeg,.png"
                        style="width: 100%; padding: 8px 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: var(--radius-md, 8px); font-size: var(--text-base, 0.875rem);">
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 8px;">
                    <button type="button" id="btnCancelModalCuti" class="btn btn-outline"
                        style="padding: 8px 16px; border-radius: var(--radius-md, 8px); font-size: var(--text-base, 0.875rem);">Batal</button>
                    <button type="submit" class="btn btn-primary"
                        style="padding: 8px 18px; border-radius: var(--radius-md, 8px); font-size: var(--text-base, 0.875rem);"><i
                            class="fas fa-check-circle me-1"></i> Simpan Permohonan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 4: Rincian Lengkap Pegawai GTK (Biodata Lengkap) -->
    <div id="pegawaiModal" class="modal-backdrop"
        style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.65); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px); padding: 12px; box-sizing: border-box;">
        <div class="card modal-card-responsive"
            style="max-width: 650px; width: 94%; max-height: 88vh; display: flex; flex-direction: column; margin: auto; border-radius: var(--radius-xl, 16px); padding: 20px; box-shadow: 0 16px 40px rgba(0,0,0,0.5); border: 1px solid var(--border-color); background: var(--bg-card); overflow: hidden;">
            <div
                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; padding-bottom: 10px; border-bottom: 1px solid var(--border-color); flex-shrink: 0;">
                <div>
                    <h3 id="pegawaiModalTitle"
                        style="font-size: var(--text-lg, 1.125rem); font-weight: 700; color: var(--text-color); margin: 0; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-id-card text-primary"></i> Rincian Lengkap Pegawai GTK
                    </h3>
                    <div id="pegawaiSubtitle" style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); margin-top: 2px;">Data
                        induk kependidikan bersumber dari Dapodik</div>
                </div>
                <button type="button" onclick="closeBiodataPegawaiModal()"
                    style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: var(--text-xl, 1.25rem); padding: 4px;" aria-label="Tutup">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div id="pegawaiLoading" style="text-align: center; padding: 40px; color: var(--text-muted);">
                <i class="fas fa-spinner fa-spin me-2" style="font-size: var(--text-2xl, 1.5rem); color: var(--primary);"></i>
                <div style="margin-top: 8px; font-size: var(--text-base, 0.875rem);">Memuat rincian data pegawai...</div>
            </div>

            <div id="pegawaiContent" class="modal-body-scroll"
                style="overflow-y: auto; flex: 1; min-height: 0; display: none; font-size: var(--text-base, 0.875rem); padding-right: 4px;">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 8px 0; color: var(--text-muted); width: 150px;">Nama Lengkap</td>
                        <td id="pegawaiNama" style="font-weight: 700; color: var(--text-color);">-</td>
                    </tr>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 8px 0; color: var(--text-muted);">NUPTK / NIP</td>
                        <td id="pegawaiNuptk" style="font-family: monospace; color: var(--primary); font-weight: 600;">-
                        </td>
                    </tr>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 8px 0; color: var(--text-muted);">NIK</td>
                        <td id="pegawaiNik" style="font-family: monospace;">-</td>
                    </tr>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 8px 0; color: var(--text-muted);">Jenis Kelamin</td>
                        <td id="pegawaiGender">-</td>
                    </tr>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 8px 0; color: var(--text-muted);">Tempat, Tgl Lahir</td>
                        <td id="pegawaiTtl">-</td>
                    </tr>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 8px 0; color: var(--text-muted);">Agama</td>
                        <td id="pegawaiAgama">-</td>
                    </tr>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 8px 0; color: var(--text-muted);">Status Kepegawaian</td>
                        <td id="pegawaiStatus">-</td>
                    </tr>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 8px 0; color: var(--text-muted);">Pendidikan Terakhir</td>
                        <td id="pegawaiPend">-</td>
                    </tr>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 8px 0; color: var(--text-muted);">Bidang Studi / Mapel</td>
                        <td id="pegawaiMapel">-</td>
                    </tr>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 8px 0; color: var(--text-muted);">Status Induk / Tgl Tugas</td>
                        <td id="pegawaiInduk">-</td>
                    </tr>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 8px 0; color: var(--text-muted);">No. HP / Email</td>
                        <td id="pegawaiHp">-</td>
                    </tr>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 8px 0; color: var(--text-muted);">Alamat Tempat Tinggal</td>
                        <td id="pegawaiAlamat">-</td>
                    </tr>
                </table>

                <div id="pegawaiBebanSection"
                    style="margin-top: 14px; padding-top: 12px; border-top: 1px solid var(--border-color); display: none;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                        <span style="font-weight: 700; color: var(--text-color); font-size: var(--text-base, 0.875rem);"><i
                                class="fas fa-book-bookmark text-primary me-1"></i> Beban Jam Mengajar (KBM)</span>
                        <span id="pegawaiJmlJam" class="badge"
                            style="background: rgba(245,158,11,0.15); color: var(--warning, #f59e0b); font-size: var(--text-xs, 0.75rem); padding: 2px 7px;">0
                            JP</span>
                    </div>
                    <div style="overflow-x: auto;">
                        <table class="table"
                            style="width: 100%; border-collapse: collapse; font-size: var(--text-sm, 0.8125rem); margin-bottom: 0;">
                            <thead>
                                <tr
                                    style="background: rgba(255,255,255,0.02); border-bottom: 1px solid var(--border-color);">
                                    <th style="padding: 6px 10px; color: var(--text-muted);">Mata Pelajaran</th>
                                    <th style="padding: 6px 10px; color: var(--text-muted);">Rombel</th>
                                    <th style="padding: 6px 10px; color: var(--text-muted); text-align: center;">Jam/Mg
                                    </th>
                                </tr>
                            </thead>
                            <tbody id="pegawaiBebanList"></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div
                style="display: flex; justify-content: flex-end; margin-top: 12px; padding-top: 10px; border-top: 1px solid var(--border-color); flex-shrink: 0;">
                <button type="button" class="btn btn-outline" onclick="closeBiodataPegawaiModal()"
                    style="padding: 7px 18px; font-size: var(--text-sm, 0.8125rem); border-radius: var(--radius-md, 8px);">Tutup</button>
            </div>
        </div>
    </div>
