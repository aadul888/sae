        <!-- Modal 1: Catat / Edit Surat Masuk -->
        <div class="modal-overlay" id="modalSuratMasuk"
            style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.55); backdrop-filter: blur(4px); z-index: 99999 !important; align-items: center; justify-content: center;">
            <div class="modal-container"
                style="background: var(--card-bg); border: 1px solid var(--border-color); border-radius: var(--radius-xl, 16px); width: 100%; max-width: 600px; box-shadow: 0 20px 40px rgba(0,0,0,0.3); overflow: hidden; max-height: 90vh; display: flex; flex-direction: column;">
                <div
                    style="padding: 16px 20px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; flex-shrink: 0;">
                    <h4 id="modalMasukTitle"
                        style="font-size: var(--text-md, 1rem); font-weight: 700; color: var(--text-color); margin: 0;">
                        Catat Surat Masuk Baru
                    </h4>
                    <button type="button" id="btnCloseModalMasuk"
                        style="background: none; border: none; font-size: var(--text-lg, 1.125rem); color: var(--text-muted); cursor: pointer;">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <form id="formSuratMasuk" action="" method="POST" enctype="multipart/form-data"
                    style="overflow-y: auto; flex: 1;">
                    @csrf
                    <input type="hidden" name="_method" id="methodSpoofMasuk" value="POST">

                    <div style="padding: 20px; display: flex; flex-direction: column; gap: 14px;">
                        <div>
                            <label
                                style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 600; color: var(--text-color); margin-bottom: 4px;">
                                Nomor Surat Pengirim: <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="nomor_surat" id="inputNomorSurat"
                                placeholder="Nomor resmi pada surat masuk (format bebas sesuai instansi pengirim)..."
                                style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: var(--radius-md, 8px); font-family: monospace; font-size: var(--text-base, 0.875rem);"
                                required>
                        </div>

                        <div>
                            <label
                                style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 600; color: var(--text-color); margin-bottom: 4px;">
                                Instansi / Pengirim Asal: <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="pengirim_asal" id="inputPengirimAsal"
                                placeholder="Contoh: Dinas Pendidikan Prov. Jawa Barat"
                                style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: var(--radius-md, 8px); font-size: var(--text-base, 0.875rem);"
                                required>
                        </div>

                        <div>
                            <label
                                style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 600; color: var(--text-color); margin-bottom: 4px;">
                                Perihal Surat: <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="perihal" id="inputPerihal"
                                placeholder="Contoh: Undangan Rapat Koordinasi Kurikulum Merdeka"
                                style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: var(--radius-md, 8px); font-size: var(--text-base, 0.875rem);"
                                required>
                        </div>

                        <div style="display: flex; gap: 12px;">
                            <div style="flex: 1;">
                                <label
                                    style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 600; color: var(--text-color); margin-bottom: 4px;">
                                    Tanggal Surat: <span class="text-danger">*</span>
                                </label>
                                <input type="date" name="tanggal_surat" id="inputTanggalSurat"
                                    style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: var(--radius-md, 8px); font-size: var(--text-base, 0.875rem);"
                                    required>
                            </div>
                            <div style="flex: 1;">
                                <label
                                    style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 600; color: var(--text-color); margin-bottom: 4px;">
                                    Tanggal Diterima:
                                </label>
                                <input type="date" name="tanggal_diterima" id="inputTanggalDiterima"
                                    style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: var(--radius-md, 8px); font-size: var(--text-base, 0.875rem);">
                            </div>
                        </div>

                        <div>
                            <label
                                style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 600; color: var(--text-color); margin-bottom: 4px;">
                                Unggah Berkas Fisik / Scan (Disimpan Langsung ke Harddisk):
                            </label>
                            <input type="file" name="file_arsip" accept=".pdf,.jpg,.jpeg,.png"
                                style="width: 100%; padding: 6px 10px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: var(--radius-md, 8px); font-size: var(--text-sm, 0.8125rem);">
                            <div id="fileInfoExisting" style="display: none; font-size: var(--text-xs, 0.75rem); margin-top: 4px;">
                            </div>
                            <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-top: 3px;">
                                Maksimal 25MB (PDF, JPG, PNG). Berkas otomatis disimpan di direktori HDD:
                                <code>{{ $hddStatus['path'] }}</code>
                            </div>
                        </div>

                        <div>
                            <label
                                style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 600; color: var(--text-color); margin-bottom: 4px;">
                                Ringkasan Isi / Catatan Tambahan:
                            </label>
                            <textarea name="keterangan" id="inputKeterangan" rows="2"
                                placeholder="Catatan singkat perihal atau tujuan surat..."
                                style="width: 100%; padding: 8px 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: var(--radius-md, 8px); font-size: var(--text-sm, 0.8125rem);"></textarea>
                        </div>

                        <div>
                            <label
                                style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 600; color: var(--text-color); margin-bottom: 4px;">
                                Status Surat:
                            </label>
                            <select name="status" id="selectStatus"
                                style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: var(--radius-md, 8px); font-size: var(--text-base, 0.875rem);">
                                <option value="menunggu_disposisi">Menunggu Disposisi</option>
                                <option value="diproses">Sedang Diproses</option>
                                <option value="selesai">Selesai</option>
                                <option value="diarsipkan">Diarsipkan</option>
                            </select>
                        </div>
                    </div>

                    <div
                        style="padding: 14px 20px; border-top: 1px solid var(--border-color); background: rgba(0,0,0,0.02); display: flex; justify-content: flex-end; gap: 10px; flex-shrink: 0;">
                        <button type="button" id="btnCancelModalMasuk" class="btn btn-outline"
                            style="padding: 8px 16px; border-radius: var(--radius-md, 8px); font-size: var(--text-base, 0.875rem);">
                            Batal
                        </button>
                        <button type="submit" class="btn btn-primary"
                            style="padding: 8px 18px; border-radius: var(--radius-md, 8px); font-size: var(--text-base, 0.875rem);">
                            <i class="fas fa-save me-1"></i> Simpan Surat Masuk
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal 2: Lembar Disposisi Surat -->
        <div class="modal-overlay" id="modalDisposisi"
            style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.55); backdrop-filter: blur(4px); z-index: 99999 !important; align-items: center; justify-content: center;">
            <div class="modal-container"
                style="background: var(--card-bg); border: 1px solid var(--border-color); border-radius: var(--radius-xl, 16px); width: 100%; max-width: 540px; box-shadow: 0 20px 40px rgba(0,0,0,0.3); overflow: hidden;">
                <div
                    style="padding: 16px 20px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h4 style="font-size: var(--text-md, 1rem); font-weight: 700; color: var(--text-color); margin: 0;">
                            Lembar Disposisi Surat Masuk
                        </h4>
                        <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-top: 2px;">
                            No: <strong id="disposisiNomorSuratText">-</strong>
                        </div>
                    </div>
                    <button type="button" id="btnCloseModalDisposisi"
                        style="background: none; border: none; font-size: var(--text-lg, 1.125rem); color: var(--text-muted); cursor: pointer;">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <form id="formDisposisi" action="" method="POST">
                    @csrf
                    <div style="padding: 20px; display: flex; flex-direction: column; gap: 14px;">
                        <div
                            style="background: rgba(99,102,241,0.06); padding: 10px 14px; border-radius: var(--radius-md, 8px); border: 1px solid rgba(99,102,241,0.15);">
                            <div style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted);">Perihal Surat:</div>
                            <div id="disposisiPerihalText"
                                style="font-size: var(--text-base, 0.875rem); font-weight: 600; color: var(--text-color);">-</div>
                        </div>

                        <!-- Riwayat Disposisi Sebelumnya (Jika Ada) -->
                        <div id="existingDisposisiContainer"
                            style="display: none; background: var(--bg-hover); border: 1px solid var(--border-color); border-radius: var(--radius-md, 8px); padding: 10px 14px;">
                            <div
                                style="font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-color); margin-bottom: 6px; display: flex; align-items: center; gap: 6px;">
                                <i class="fas fa-clock-rotate-left text-primary"></i> Riwayat Disposisi Surat Ini:
                            </div>
                            <div id="existingDisposisiList"
                                style="display: flex; flex-direction: column; gap: 6px; font-size: var(--text-sm, 0.8125rem);"></div>
                        </div>

                        <div>
                            <label
                                style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 600; color: var(--text-color); margin-bottom: 4px;">
                                Disposisi Dari:
                            </label>
                            <input type="text" name="disposisi_dari" id="inputDisposisiDari"
                                value="Kepala Sekolah"
                                style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: var(--radius-md, 8px); font-size: var(--text-base, 0.875rem);"
                                required>
                        </div>

                        <div>
                            <label
                                style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 600; color: var(--text-color); margin-bottom: 4px;">
                                Tujuan Disposisi (Pilih PTK / Bidang): <span class="text-danger">*</span>
                            </label>
                            <input type="search" id="selectPtkTujuan" class="form-control"
                                placeholder="Cari nama, NIP, NUPTK, atau NIK..." autocomplete="off"
                                data-people-search="{{ route('dashboard.people.search') }}" data-people-kind="gtk"
                                data-people-target="#ptkIdTujuan" data-people-name-target="#inputDisposisiKe"
                                style="margin-bottom: 6px;">
                            <input type="hidden" id="ptkIdTujuan" name="ptk_id_tujuan">
                            <input type="text" name="disposisi_ke" id="inputDisposisiKe"
                                placeholder="Atau ketik nama/bagian (misal: Waka Kurikulum, KTU)"
                                style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: var(--radius-md, 8px); font-size: var(--text-base, 0.875rem);"
                                required>
                        </div>

                        <div style="display: flex; gap: 12px;">
                            <div style="flex: 1;">
                                <label
                                    style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 600; color: var(--text-color); margin-bottom: 4px;">
                                    Instruksi Disposisi: <span class="text-danger">*</span>
                                </label>
                                <select name="instruksi" id="selectInstruksi"
                                    style="width: 100%; height: 38px; padding: 0 10px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: var(--radius-md, 8px); font-size: var(--text-base, 0.875rem);"
                                    required>
                                    <option value="Tindak Lanjuti">Tindak Lanjuti</option>
                                    <option value="Pelajari / Teliti">Pelajari / Teliti</option>
                                    <option value="Hadiri / Wakili">Hadiri / Wakili</option>
                                    <option value="Koordinasikan">Koordinasikan</option>
                                    <option value="Buatkan Tanggapan / Balasan">Buatkan Tanggapan / Balasan</option>
                                    <option value="Arsipkan">Arsipkan</option>
                                </select>
                            </div>
                            <div style="flex: 1;">
                                <label
                                    style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 600; color: var(--text-color); margin-bottom: 4px;">
                                    Tanggal Disposisi:
                                </label>
                                <input type="date" name="tanggal_disposisi" id="inputTanggalDisposisi"
                                    style="width: 100%; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: var(--radius-md, 8px); font-size: var(--text-base, 0.875rem);">
                            </div>
                        </div>

                        <div>
                            <label
                                style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 600; color: var(--text-color); margin-bottom: 4px;">
                                Catatan / Arahan Pimpinan:
                            </label>
                            <textarea name="catatan" id="inputDisposisiCatatan" rows="3"
                                placeholder="Catatan instruksi khusus dari Kepala Sekolah..."
                                style="width: 100%; padding: 8px 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: var(--radius-md, 8px); font-size: var(--text-sm, 0.8125rem);"></textarea>
                        </div>
                    </div>

                    <div
                        style="padding: 14px 20px; border-top: 1px solid var(--border-color); background: rgba(0,0,0,0.02); display: flex; justify-content: flex-end; gap: 10px;">
                        <button type="button" id="btnCancelModalDisposisi" class="btn btn-outline"
                            style="padding: 8px 16px; border-radius: var(--radius-md, 8px); font-size: var(--text-base, 0.875rem);">
                            Batal
                        </button>
                        <button type="submit" class="btn btn-primary"
                            style="padding: 8px 18px; border-radius: var(--radius-md, 8px); font-size: var(--text-base, 0.875rem);">
                            <i class="fas fa-check me-1"></i> Simpan Disposisi
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal 3: Pratinjau Dokumen Berkas dari HDD Komputer -->
        <div class="modal-overlay" id="modalPreviewDokumen"
            style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.65); backdrop-filter: blur(4px); z-index: 99999 !important; align-items: center; justify-content: center; padding: 20px;">
            <div class="modal-container"
                style="background: var(--card-bg); border: 1px solid var(--border-color); border-radius: var(--radius-xl, 16px); width: 100%; max-width: 900px; height: 85vh; box-shadow: 0 25px 50px rgba(0,0,0,0.4); display: flex; flex-direction: column; overflow: hidden;">
                <div
                    style="padding: 14px 20px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; background: rgba(0,0,0,0.02); flex-shrink: 0;">
                    <div
                        style="display: flex; align-items: center; gap: 8px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 70%;">
                        <i class="fas fa-file-lines text-primary"></i>
                        <span id="previewTitle"
                            style="font-weight: 700; font-size: var(--text-base, 0.875rem); color: var(--text-color); overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                            Pratinjau Dokumen Arsip
                        </span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <a href="#" id="previewDownloadBtn" class="btn btn-outline"
                            style="padding: 6px 12px; font-size: var(--text-sm, 0.8125rem); border-radius: var(--radius-sm, 6px);"
                            title="Unduh Berkas ke Komputer">
                            <i class="fas fa-download me-1"></i> Unduh
                        </a>
                        <button type="button" id="btnClosePreview"
                            style="background: none; border: none; font-size: var(--text-xl, 1.25rem); color: var(--text-muted); cursor: pointer; padding: 4px 8px;">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>

                <div
                    style="flex: 1; background: #525659; display: flex; align-items: center; justify-content: center; overflow: hidden;">
                    <iframe id="previewIframe" src="about:blank"
                        style="width: 100%; height: 100%; border: none; display: none;"></iframe>
                    <img id="previewImage" src="" alt="Preview Berkas"
                        style="max-width: 100%; max-height: 100%; object-fit: contain; display: none;">
                </div>
            </div>
        </div>

        <!-- Modal 4: Filter & Cetak Buku Agenda Surat Masuk -->
        <div class="modal-overlay" id="modalAgendaMasuk"
            style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.55); backdrop-filter: blur(4px); z-index: 99999 !important; align-items: center; justify-content: center;">
            <div class="modal-container"
                style="background: var(--card-bg); border: 1px solid var(--border-color); border-radius: var(--radius-xl, 16px); width: 100%; max-width: 480px; box-shadow: 0 20px 40px rgba(0,0,0,0.3); overflow: hidden;">
                <div
                    style="padding: 16px 20px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
                    <h4
                        style="font-size: var(--text-md, 1rem); font-weight: 700; color: var(--text-color); margin: 0; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-print text-primary"></i> Cetak Buku Agenda Surat Masuk
                    </h4>
                    <button type="button" id="btnCloseModalAgendaMasuk"
                        style="background: none; border: none; font-size: var(--text-lg, 1.125rem); color: var(--text-muted); cursor: pointer;">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <form action="{{ route('dashboard.persuratan.masuk.agenda.cetak') }}" method="GET" target="_blank"
                    style="padding: 20px; display: flex; flex-direction: column; gap: 14px;">
                    <div>
                        <label
                            style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 600; color: var(--text-color); margin-bottom: 4px;">
                            Rentang Tanggal Surat (Opsional):
                        </label>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                            <input type="date" name="tanggal_mulai" class="form-control"
                                style="height: 38px; border-radius: var(--radius-md, 8px); font-size: var(--text-sm, 0.8125rem);">
                            <input type="date" name="tanggal_selesai" class="form-control"
                                style="height: 38px; border-radius: var(--radius-md, 8px); font-size: var(--text-sm, 0.8125rem);">
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                        <div>
                            <label
                                style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 600; color: var(--text-color); margin-bottom: 4px;">
                                Tahun:
                            </label>
                            <select name="tahun" class="form-control"
                                style="height: 38px; border-radius: var(--radius-md, 8px); font-size: var(--text-base, 0.875rem);">
                                @for ($y = date('Y'); $y >= 2024; $y--)
                                    <option value="{{ $y }}">{{ $y }}</option>
                                @endfor
                            </select>
                        </div>
                        <div>
                            <label
                                style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 600; color: var(--text-color); margin-bottom: 4px;">
                                Bulan (Opsional):
                            </label>
                            <select name="bulan" class="form-control"
                                style="height: 38px; border-radius: var(--radius-md, 8px); font-size: var(--text-base, 0.875rem);">
                                <option value="">Semua Bulan (1 Tahun)</option>
                                @foreach ([1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'] as $mNum => $mName)
                                    <option value="{{ $mNum }}">{{ $mName }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <label
                            style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 600; color: var(--text-color); margin-bottom: 4px;">
                            Status Pemrosesan:
                        </label>
                        <select name="status" class="form-control"
                            style="height: 38px; border-radius: var(--radius-md, 8px); font-size: var(--text-base, 0.875rem);">
                            <option value="">Semua Status</option>
                            <option value="selesai">Selesai / Ditindaklanjuti</option>
                            <option value="diproses">Sedang Diproses</option>
                            <option value="menunggu_disposisi">Menunggu Disposisi</option>
                            <option value="diarsipkan">Diarsipkan</option>
                        </select>
                    </div>

                    <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 6px;">
                        <button type="button" id="btnCancelModalAgendaMasuk" class="btn btn-outline"
                            style="padding: 8px 16px; border-radius: var(--radius-md, 8px); font-size: var(--text-base, 0.875rem);">Batal</button>
                        <button type="submit" class="btn btn-primary"
                            style="padding: 8px 18px; border-radius: var(--radius-md, 8px); font-size: var(--text-base, 0.875rem); display: inline-flex; align-items: center; gap: 6px;">
                            <i class="fas fa-print"></i> Buka Lembar Cetak
                        </button>
                    </div>
                </form>
            </div>
        </div>
