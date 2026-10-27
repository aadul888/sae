{{-- Modal Tambah / Ubah Bahan Habis Pakai --}}
<div id="modalBhp" class="modal-backdrop"
    style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
    <div class="card"
        style="width: 100%; max-width: 620px; max-height: 92vh; overflow-y: auto; padding: var(--space-10); border-radius: var(--radius-lg);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--space-9);">
            <h3 id="modalBhpTitle"
                style="margin: 0; font-size: var(--text-xl); font-weight: var(--font-bold); color: var(--text-color);">
                Tambah Bahan Habis Pakai
            </h3>
            <button type="button" id="btnCloseModalBhp"
                style="background: none; border: none; font-size: var(--text-xl); color: var(--text-muted); cursor: pointer;"
                title="Tutup">&times;</button>
        </div>

        <form id="formBhp" method="POST" action="{{ route('dashboard.laboran.bhp.store') }}">
            @csrf
            <input type="hidden" name="_method" id="bhpMethod" value="POST">
            <input type="hidden" name="jurusan_id" id="bhpJurusan" value="{{ $context['jurusan_id'] ?? '' }}">

            <div style="display: grid; grid-template-columns: 1fr 1.4fr; gap: var(--space-6); margin-bottom: var(--space-7);">
                <div>
                    <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">
                        Kode Bahan <span style="font-size: var(--text-xs); font-weight: var(--font-normal); color: var(--text-muted);">(otomatis bila kosong)</span>
                    </label>
                    <input type="text" name="kode_item" id="bhpKode" class="form-control" placeholder="BHP-XXXXXX-XXXX">
                </div>
                <div>
                    <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">
                        Ruang Lab <span style="color: var(--danger, #ef4444);">*</span>
                    </label>
                    <input type="text" name="ruang_lab_nama" id="bhpRuang" class="form-control" list="daftarRuangBhp" required>
                    <datalist id="daftarRuangBhp">
                        @foreach ($daftarLab as $lab)
                            <option value="{{ $lab }}"></option>
                        @endforeach
                    </datalist>
                </div>
            </div>

            <div style="margin-bottom: var(--space-7);">
                <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">
                    Nama Bahan <span style="color: var(--danger, #ef4444);">*</span>
                </label>
                <input type="text" name="nama_item" id="bhpNama" class="form-control" required
                    placeholder="Contoh: Alkohol 70%, Aquades, Bibit Tanaman">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-6); margin-bottom: var(--space-7);">
                <div>
                    <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">
                        Stok Total <span style="color: var(--danger, #ef4444);">*</span>
                    </label>
                    <input type="number" name="stok_total" id="bhpStokTotal" class="form-control" min="0.01" step="0.01" required>
                </div>
                <div>
                    <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">
                        Satuan <span style="color: var(--danger, #ef4444);">*</span>
                    </label>
                    <input type="text" name="satuan" id="bhpSatuan" class="form-control" value="botol" required>
                </div>
            </div>

            <div id="wrapperStokTersediaBhp" style="display: none; margin-bottom: var(--space-7);">
                <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">
                    Stok Tersedia Saat Ini
                </label>
                <input type="number" name="stok_tersedia" id="bhpStokTersedia" class="form-control" min="0" step="0.01">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-6); margin-bottom: var(--space-7);">
                <div>
                    <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">
                        Kondisi <span style="color: var(--danger, #ef4444);">*</span>
                    </label>
                    <select name="kondisi" id="bhpKondisi" class="form-control" required>
                        <option value="baik">Baik</option>
                        <option value="rusak_ringan">Rusak Ringan</option>
                        <option value="rusak_berat">Rusak Berat</option>
                        <option value="kedaluwarsa">Kedaluwarsa</option>
                    </select>
                </div>
                <div>
                    <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">
                        Tanggal Kedaluwarsa <span style="font-size: var(--text-xs); font-weight: var(--font-normal); color: var(--text-muted);">(opsional)</span>
                    </label>
                    <input type="date" name="tgl_kedaluwarsa" id="bhpExp" class="form-control">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-6); margin-bottom: var(--space-7);">
                <div>
                    <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">
                        Tanggal Pengadaan <span style="font-size: var(--text-xs); font-weight: var(--font-normal); color: var(--text-muted);">(opsional)</span>
                    </label>
                    <input type="date" name="tanggal_pengadaan" id="bhpTglPengadaan" class="form-control">
                </div>
                <div>
                    <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">
                        Tahun Pengadaan
                    </label>
                    <input type="number" name="tahun_pengadaan" id="bhpTahunPengadaan" class="form-control" min="1900" max="{{ date('Y') + 1 }}">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: var(--space-6); margin-bottom: var(--space-9);">
                <div>
                    <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">
                        Sumber Anggaran
                    </label>
                    <select name="sumber_dana" id="bhpSumberDana" class="form-control">
                        <option value="BOS Reguler">BOS Reguler</option>
                        <option value="BOS Kinerja">BOS Kinerja</option>
                        <option value="DAK Fisik">DAK Fisik</option>
                        <option value="Komite / Yayasan">Komite / Yayasan</option>
                        <option value="Hibah / Bantuan">Hibah / Bantuan</option>
                    </select>
                </div>
                <div>
                    <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">
                        Lokasi Lemari / Rak
                    </label>
                    <input type="text" name="lokasi_lemari_rak" id="bhpRak" class="form-control" placeholder="Contoh: Rak Bahan C-03">
                </div>
                <div>
                    <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">
                        Spesifikasi
                    </label>
                    <input type="text" name="spesifikasi" id="bhpSpek" class="form-control" placeholder="Contoh: Kemasan botol 1000 ml teknis">
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: var(--space-4);">
                <button type="button" class="btn btn-secondary" id="btnCancelModalBhp">Batal</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Simpan Bahan</button>
            </div>
        </form>
    </div>
</div>

{{-- Modal Import Excel --}}
<div id="modalImportBhp" class="modal-backdrop"
    style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
    <div class="card" style="width: 100%; max-width: 520px; padding: 24px; border-radius: var(--radius-lg, 12px);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px;">
            <h3 style="margin: 0; font-size: var(--text-xl, 1.25rem); font-weight: 700; color: var(--text-color);">
                <i class="fas fa-file-excel text-success me-2"></i> Import Bahan Habis Pakai
            </h3>
            <button type="button" id="btnCloseModalImportBhp"
                style="background: none; border: none; font-size: var(--text-xl, 1.25rem); color: var(--text-muted); cursor: pointer;"
                aria-label="Tutup Modal">&times;</button>
        </div>

        <div style="margin-bottom: 18px; padding: 12px 14px; background: rgba(16,185,129,0.08); border: 1px solid rgba(16,185,129,0.25); border-radius: var(--radius-md, 8px);">
            <div style="font-size: var(--text-sm, 0.8125rem); color: var(--text-color); font-weight: 600; margin-bottom: 4px;">
                Unduh Format Template Resmi:
            </div>
            <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-bottom: 10px;">
                Gunakan template resmi SAE agar nama kolom, kode otomatis BHP, dan stok sesuai dengan sistem.
            </div>
            <a href="{{ route('dashboard.laboran.bhp.template') }}" class="btn btn-outline"
                style="font-size: var(--text-xs, 0.75rem); padding: 6px 12px; display: inline-flex; align-items: center; gap: 6px; text-decoration: none;">
                <i class="fas fa-download text-success"></i> Unduh Format Excel (.xlsx)
            </a>
        </div>

        <form id="formImportBhp" method="POST" action="{{ route('dashboard.laboran.bhp.import') }}" enctype="multipart/form-data">
            @csrf
            <div class="form-group" style="margin-bottom: 18px;">
                <label style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">
                    Pilih Berkas Excel / CSV <span style="color: var(--danger, #ef4444);">*</span>
                </label>
                <input type="file" name="file" id="fileImportBhp" class="form-control" accept=".xlsx,.xls,.csv" required>
                <small style="color: var(--text-muted); font-size: var(--text-xs, 0.75rem); display: block; margin-top: 4px;">
                    Mendukung .xlsx, .xls, atau .csv (Maks. 10 MB).
                </small>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 8px;">
                <button type="button" class="btn btn-secondary" id="btnCancelImportBhp">Batal</button>
                <button type="submit" class="btn btn-primary" id="btnSubmitImportBhp">
                    <i class="fas fa-upload me-1"></i> Mulai Unggah &amp; Import
                </button>
            </div>
        </form>
    </div>
</div>
