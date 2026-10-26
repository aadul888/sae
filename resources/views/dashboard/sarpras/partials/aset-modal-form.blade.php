{{-- Modal Tambah / Edit Aset Sarpras (Standar Lega & Rincian Kondisi seperti Laboran) --}}
<div id="modalAset" class="modal-backdrop"
    style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
    <div class="card"
        style="width: 100%; max-width: 780px; max-height: 92vh; overflow-y: auto; padding: 24px; border-radius: var(--radius-lg, 12px);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 id="modalAsetTitle"
                style="margin: 0; font-size: var(--text-xl, 1.25rem); font-weight: 700; color: var(--text-color);">
                Tambah Aset Sarpras
            </h3>
            <button type="button" id="btnCloseModalAset"
                style="background: none; border: none; font-size: var(--text-xl, 1.25rem); color: var(--text-muted); cursor: pointer;"
                aria-label="Tutup Modal">&times;</button>
        </div>

        <form id="formAset" method="POST" action="{{ route('dashboard.sarpras.aset.store') }}" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="_method" id="asetMethod" value="POST">
            <input type="hidden" name="id" id="asetId">

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px; margin-bottom: 14px;">
                <div>
                    <label style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">
                        Kode Aset
                        <span style="font-size: var(--text-xs, 0.75rem); font-weight: 400; color: var(--text-muted);">(opsional)</span>
                    </label>
                    <input type="text" name="kode_aset" id="asetKode" class="form-control"
                        placeholder="Kosongkan untuk dibuat otomatis">
                    <small style="display: block; margin-top: 4px; font-size: var(--text-xs, 0.75rem); color: var(--text-muted);">
                        <i class="fas fa-wand-magic-sparkles"></i> Bila kosong, sistem membuat kode otomatis (mis. AST-202610-0001).
                    </small>
                </div>
                <div>
                    <label style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">
                        Nama Barang / Aset <span style="color: var(--danger, #ef4444);">*</span>
                    </label>
                    <input type="text" name="nama_barang" id="asetNama" class="form-control"
                        placeholder="Proyektor Epson EB-X500 / Meja Guru" required>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 12px; margin-bottom: 14px;">
                <div>
                    <label style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">
                        Kategori <span style="color: var(--danger, #ef4444);">*</span>
                    </label>
                    <select name="kategori" id="asetKategori" class="form-control" required>
                        @foreach ($kategoriList as $kat)
                            <option value="{{ $kat }}">{{ $kat }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">
                        Merk / Model / Tipe
                    </label>
                    <input type="text" name="merk_tipe" id="asetMerk" class="form-control"
                        placeholder="Epson / Lenovo / Olympic / Quick Boxer">
                </div>
                <div>
                    <label style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">
                        No. Seri Pabrik
                    </label>
                    <input type="text" name="no_seri_pabrik" id="asetNoSeri" class="form-control"
                        placeholder="SN-EPS-9921">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 12px; margin-bottom: 14px;">
                <div>
                    <label style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">
                        Tanggal Perolehan
                    </label>
                    <input type="date" name="tanggal_perolehan" id="asetTanggalPerolehan" class="form-control">
                </div>
                <div>
                    <label style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">
                        Tahun Beli / Perolehan
                    </label>
                    <input type="number" name="tahun_perolehan" id="asetTahun" class="form-control"
                        value="{{ date('Y') }}" min="1990">
                </div>
                <div>
                    <label style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">
                        Harga Perolehan (Rp)
                    </label>
                    <input type="number" step="0.01" min="0" name="harga_perolehan" id="asetHarga" class="form-control"
                        placeholder="0">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 12px; margin-bottom: 14px;">
                <div>
                    <label style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">
                        Jumlah Unit <span style="color: var(--danger, #ef4444);">*</span>
                    </label>
                    <input type="number" name="jumlah" id="asetJumlah" class="form-control" value="1"
                        min="1" required>
                </div>
                <div>
                    <label style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">
                        Satuan <span style="color: var(--danger, #ef4444);">*</span>
                    </label>
                    <input type="text" name="satuan" id="asetSatuan" class="form-control" value="unit"
                        placeholder="unit/set/buah" required>
                </div>
                <div>
                    <label style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">
                        Sumber Dana <span style="color: var(--danger, #ef4444);">*</span>
                    </label>
                    <select name="sumber_dana" id="asetSumber" class="form-control" required>
                        <option value="BOS Reguler">BOS Reguler</option>
                        <option value="BOS Kinerja">BOS Kinerja</option>
                        <option value="DAK Fisik">DAK Fisik</option>
                        <option value="Komite / Yayasan">Komite / Yayasan</option>
                        <option value="Hibah / Bantuan">Hibah / Bantuan</option>
                    </select>
                </div>
            </div>

            {{-- Rincian Kondisi Berdasarkan Jumlah Unit (Persis Seperti Laboran) --}}
            <div style="margin-bottom: 14px; padding: 12px 14px; background: rgba(59,130,246,0.05); border: 1px solid rgba(59,130,246,0.2); border-radius: var(--radius-md, 8px);">
                <div style="display: flex; justify-content: space-between; align-items: center; gap: 8px; flex-wrap: wrap; margin-bottom: 8px;">
                    <label style="font-size: var(--text-base, 0.875rem); font-weight: 600; color: var(--primary); margin: 0;">
                        <i class="fas fa-layer-group me-1"></i> Rincian Kondisi per Jumlah
                        <span style="font-size: var(--text-xs, 0.75rem); font-weight: 400; color: var(--text-muted);">(opsional)</span>
                    </label>
                    <span id="asetRincianHint" style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted);">
                        Total rincian: <strong id="asetRincianSum">0</strong> / <strong id="asetRincianTotal">1</strong>
                    </span>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 10px;">
                    <div>
                        <label style="display: block; font-size: var(--text-xs, 0.75rem); font-weight: 600; margin-bottom: 4px; color: var(--success, #10b981);">
                            <i class="fas fa-circle-check me-1"></i> Baik
                        </label>
                        <input type="number" min="0" name="jumlah_baik" id="asetJumlahBaik"
                            class="form-control aset-jumlah-kondisi" value="0"
                            style="height: 36px; font-size: var(--text-sm, 0.8125rem);">
                    </div>
                    <div>
                        <label style="display: block; font-size: var(--text-xs, 0.75rem); font-weight: 600; margin-bottom: 4px; color: var(--warning, #f59e0b);">
                            <i class="fas fa-triangle-exclamation me-1"></i> Rusak Ringan
                        </label>
                        <input type="number" min="0" name="jumlah_rusak_ringan" id="asetJumlahRusakRingan"
                            class="form-control aset-jumlah-kondisi" value="0"
                            style="height: 36px; font-size: var(--text-sm, 0.8125rem);">
                    </div>
                    <div>
                        <label style="display: block; font-size: var(--text-xs, 0.75rem); font-weight: 600; margin-bottom: 4px; color: var(--danger, #ef4444);">
                            <i class="fas fa-circle-exclamation me-1"></i> Rusak Berat
                        </label>
                        <input type="number" min="0" name="jumlah_rusak_berat" id="asetJumlahRusakBerat"
                            class="form-control aset-jumlah-kondisi" value="0"
                            style="height: 36px; font-size: var(--text-sm, 0.8125rem);">
                    </div>
                </div>

                <div style="display: flex; gap: 8px; margin-top: 8px; flex-wrap: wrap;">
                    <button type="button" id="btnAsetSemuaBaik" class="btn btn-outline btn-sm"
                        style="font-size: var(--text-xs, 0.75rem); padding: 4px 10px;">
                        <i class="fas fa-check-double me-1"></i> Semua Baik
                    </button>
                    <small style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); align-self: center;">
                        Contoh: 10 unit &rarr; 8 Baik, 2 Rusak Ringan.
                    </small>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 12px; margin-bottom: 14px;">
                <div>
                    <label style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">
                        Ruang / Lokasi Penempatan
                    </label>
                    <select name="ruang_id" id="asetRuang" class="form-control">
                        <option value="">-- Pilih Ruang dari Sarpras --</option>
                        @foreach ($ruangList as $r)
                            <option value="{{ $r->id }}">
                                {{ $r->nama_ruang }} &bull; {{ $r->gedung ?: 'Gedung' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">
                        Alokasi Unit / Jurusan
                    </label>
                    <select name="jurusan_id" id="asetJurusan" class="form-control">
                        <option value="">Umum (Seluruh Sekolah / Non-Jurusan)</option>
                        @foreach ($jurusanList as $jur)
                            <option value="{{ $jur->jurusan_id }}">
                                {{ $jur->singkatan ? $jur->singkatan . ' — ' : '' }}{{ $jur->nama_jurusan }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 12px; margin-bottom: 14px;">
                <div>
                    <label style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">
                        Kondisi Dominan <span style="color: var(--danger, #ef4444);">*</span>
                    </label>
                    <select name="kondisi" id="asetKondisi" class="form-control" required>
                        <option value="baik">Baik</option>
                        <option value="rusak_ringan">Rusak Ringan</option>
                        <option value="rusak_berat">Rusak Berat</option>
                    </select>
                </div>
                <div>
                    <label style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">
                        Status Ketersediaan <span style="color: var(--danger, #ef4444);">*</span>
                    </label>
                    <select name="status_ketersediaan" id="asetStatus" class="form-control" required>
                        <option value="tersedia">Tersedia</option>
                        <option value="dipinjam">Dipinjam</option>
                        <option value="perbaikan">Dalam Perbaikan</option>
                        <option value="dihapuskan">Dihapuskan</option>
                    </select>
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 20px;">
                <label style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">
                    Foto Aset / Barang (Opsional)
                </label>
                <input type="file" name="foto" id="asetFoto" class="form-control" accept="image/*">
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 8px;">
                <button type="button" class="btn btn-secondary" id="btnCancelModalAset">Batal</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i> Simpan Aset
                </button>
            </div>
        </form>
    </div>
</div>
