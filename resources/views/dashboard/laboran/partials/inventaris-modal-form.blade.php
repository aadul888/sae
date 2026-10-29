        {{-- Modal Tambah / Edit Alat Bahan Lab --}}
        <div id="modalLab" class="modal-backdrop"
            style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
            <div class="card"
                style="width: 100%; max-width: 780px; max-height: 92vh; overflow-y: auto; padding: 24px; border-radius: var(--radius-lg, 12px);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h3 id="modalLabTitle"
                        style="margin: 0; font-size: var(--text-xl, 1.25rem); font-weight: 700; color: var(--text-color);">
                        Tambah Alat / Bahan Lab
                    </h3>
                    <button type="button" id="btnCloseModalLab"
                        style="background: none; border: none; font-size: var(--text-xl, 1.25rem); color: var(--text-muted); cursor: pointer;">&times;</button>
                </div>

                <form id="formLab" method="POST" action="{{ route('dashboard.laboran.inventaris.store') }}">
                    @csrf
                    <input type="hidden" name="_method" id="labMethod" value="POST">
                    <input type="hidden" name="id" id="labId">

                    @if (isset($daftarAsetSarpras) && $daftarAsetSarpras->isNotEmpty())
                        <div id="wrapperAsetSarpras"
                            style="margin-bottom: 14px; padding: 10px 12px; background: rgba(99,102,241,0.06); border: 1px dashed rgba(99,102,241,0.3); border-radius: var(--radius-md, 8px);">
                            <div
                                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                                <label style="font-size: var(--text-sm, 0.8125rem); font-weight: 600; color: var(--primary); margin: 0;">
                                    <i class="fas fa-boxes-stacked me-1"></i> Referensi / Salin dari Aset Sarpras
                                    (Opsional)
                                </label>
                                <small style="color: var(--text-muted); font-size: var(--text-xs, 0.75rem);">Isi otomatis data</small>
                            </div>
                            <select id="selectAsetSarpras" class="form-control"
                                style="font-size: var(--text-sm, 0.8125rem); height: 36px;">
                                <option value="">-- Ketik Baru atau Pilih Referensi Aset Sarpras --</option>
                                @foreach ($daftarAsetSarpras as $ast)
                                    <option value="{{ $ast->id }}" data-nama="{{ $ast->nama_barang }}"
                                        data-kode="{{ $ast->kode_aset }}" data-ruang="{{ $ast->ruang_nama }}"
                                        data-satuan="{{ $ast->satuan }}" data-kondisi="{{ $ast->kondisi }}"
                                        data-spek="{{ $ast->merk_tipe ? 'Merk/Tipe: ' . $ast->merk_tipe : '' }}"
                                        data-merk="{{ $ast->merk_tipe }}">
                                        {{ $ast->nama_barang }} &bull; {{ $ast->kode_aset }} (Lokasi:
                                        {{ $ast->ruang_nama ?: 'Sarpras' }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 12px; margin-bottom: 14px;">
                        <div>
                            <div
                                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                                <label style="font-size: var(--text-base, 0.875rem); font-weight: 600; color: var(--text-color); margin: 0;">
                                    Ruang Lab / Bengkel <span style="color: var(--danger, #ef4444);">*</span>
                                </label>
                                <a href="{{ route('dashboard.sarpras.ruang.index') }}" target="_blank"
                                    style="font-size: var(--text-xs, 0.75rem); color: var(--primary); text-decoration: none;"
                                    title="Kelola Ruang di Sarpras & Aset">
                                    <i class="fas fa-arrow-up-right-from-square me-1"></i> Data Sarpras
                                </a>
                            </div>
                            <select name="ruang_lab_nama" id="labRuang" class="form-control" required>
                                <option value="">-- Pilih Ruang dari Sarpras &amp; Aset --</option>
                                @if (isset($daftarRuangSarpras) && $daftarRuangSarpras->isNotEmpty())
                                    @foreach ($daftarRuangSarpras as $r)
                                        <option value="{{ $r->nama_ruang }}">
                                            {{ $r->nama_ruang }} &bull; {{ $r->gedung ?: 'Sarpras' }}
                                            {{ $r->lantai ? '(Lt. ' . $r->lantai . ')' : '' }}
                                        </option>
                                    @endforeach
                                @else
                                    @foreach ($daftarLab as $l)
                                        <option value="{{ $l }}">{{ $l }}</option>
                                    @endforeach
                                @endif
                            </select>
                            <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-top: 4px;">
                                <i class="fas fa-link me-1 text-primary"></i> Terhubung langsung dengan <strong>Ruang &amp;
                                    Gedung Sarpras</strong>.
                            </div>
                        </div>
                        <div>
                            <label
                                style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Jenis
                                Item <span style="color: var(--danger, #ef4444);">*</span></label>
                            <select name="jenis" id="labJenis" class="form-control" required>
                                <option value="alat">Alat Praktikum</option>
                                <option value="bahan_habis_pakai">Bahan Habis Pakai</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: 14px;">
                        <label
                            style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Jurusan
                            / Kompetensi Keahlian</label>
                        <select name="jurusan_id" id="labJurusan" class="form-control" {{ !empty($context['unrestricted']) ? '' : 'disabled' }}>
                            @if (!empty($context['unrestricted']))
                                <option value="">-- Ikut Penugasan --</option>
                            @endif
                            @foreach (($jurusanOptions ?? []) as $j)
                                <option value="{{ $j->jurusan_id }}" {{ ($context['jurusan_id'] ?? null) === $j->jurusan_id ? 'selected' : '' }}>
                                    {{ $j->nama_jurusan }}{{ !empty($j->singkatan) ? ' (' . $j->singkatan . ')' : '' }}
                                </option>
                            @endforeach
                        </select>
                        <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-top: 4px;">
                            @if (!empty($context['unrestricted']))
                                <i class="fas fa-info-circle me-1"></i> Kosongkan agar mengikuti jurusan penugasan Anda.
                            @else
                                <i class="fas fa-lock me-1"></i> Terkunci pada jurusan penugasan Anda.
                            @endif
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px; margin-bottom: 14px;">
                        <div>
                            <label
                                style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Kode
                                Item
                                <span style="font-size: var(--text-xs, 0.75rem); font-weight: 400; color: var(--text-muted);">(opsional)</span>
                            </label>
                            <input type="text" name="kode_item" id="labKode" class="form-control"
                                placeholder="Kosongkan untuk dibuat otomatis">
                            <small style="display: block; margin-top: 4px; font-size: var(--text-xs, 0.75rem); color: var(--text-muted);">
                                <i class="fas fa-wand-magic-sparkles"></i> Bila kosong, sistem membuat kode otomatis
                                (mis. ALT-202610-0001).
                            </small>
                        </div>
                        <div>
                            <label
                                style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Nama
                                Alat / Bahan <span style="color: var(--danger, #ef4444);">*</span></label>
                            <input type="text" name="nama_item" id="labNama" class="form-control"
                                placeholder="Mikroskop Binokuler / Asam Klorida" required>
                        </div>
                        <div>
                            <label
                                style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Merk
                                <span style="font-size: var(--text-xs, 0.75rem); font-weight: 400; color: var(--text-muted);">(opsional)</span></label>
                            <input type="text" name="merk" id="labMerk" class="form-control" list="listMerkLab"
                                placeholder="Olympus / Yanmar / OneMed">
                            <datalist id="listMerkLab">
                                @foreach (($daftarMerk ?? []) as $m)
                                    <option value="{{ $m }}"></option>
                                @endforeach
                            </datalist>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 12px; margin-bottom: 14px;">
                        <div>
                            <label
                                style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Stok
                                Total <span style="color: var(--danger, #ef4444);">*</span></label>
                            <input type="number" step="0.01" name="stok_total" id="labStokTotal"
                                class="form-control" value="1" required>
                        </div>
                        <div id="wrapperStokTersedia" style="display: none;">
                            <label
                                style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Stok
                                Tersedia</label>
                            <input type="number" step="0.01" name="stok_tersedia" id="labStokTersedia"
                                class="form-control" value="1">
                        </div>
                        <div>
                            <label
                                style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Satuan
                                <span style="color: var(--danger, #ef4444);">*</span></label>
                            <input type="text" name="satuan" id="labSatuan" class="form-control" value="unit"
                                placeholder="unit/botol/pcs" required>
                        </div>
                    </div>

                    {{-- Rincian Kondisi Berdasarkan Jumlah Unit --}}
                    <div style="margin-bottom: 14px; padding: 12px 14px; background: rgba(59,130,246,0.05); border: 1px solid rgba(59,130,246,0.2); border-radius: var(--radius-md, 8px);">
                        <div style="display: flex; justify-content: space-between; align-items: center; gap: 8px; flex-wrap: wrap; margin-bottom: 8px;">
                            <label style="font-size: var(--text-base, 0.875rem); font-weight: 600; color: var(--primary); margin: 0;">
                                <i class="fas fa-layer-group me-1"></i> Rincian Kondisi per Jumlah
                                <span style="font-size: var(--text-xs, 0.75rem); font-weight: 400; color: var(--text-muted);">(opsional)</span>
                            </label>
                            <span id="labRincianHint" style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted);">
                                Total rincian: <strong id="labRincianSum">0</strong> / <strong id="labRincianTotal">1</strong>
                            </span>
                        </div>

                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 10px;">
                            <div>
                                <label style="display: block; font-size: var(--text-xs, 0.75rem); font-weight: 600; margin-bottom: 4px; color: var(--success, #10b981);">
                                    <i class="fas fa-circle-check me-1"></i> Baik
                                </label>
                                <input type="number" step="0.01" min="0" name="jumlah_baik" id="labJumlahBaik"
                                    class="form-control lab-jumlah-kondisi" value="0"
                                    style="height: 36px; font-size: var(--text-sm, 0.8125rem);">
                            </div>
                            <div>
                                <label style="display: block; font-size: var(--text-xs, 0.75rem); font-weight: 600; margin-bottom: 4px; color: var(--warning, #f59e0b);">
                                    <i class="fas fa-triangle-exclamation me-1"></i> Rusak Ringan
                                </label>
                                <input type="number" step="0.01" min="0" name="jumlah_rusak_ringan" id="labJumlahRusakRingan"
                                    class="form-control lab-jumlah-kondisi" value="0"
                                    style="height: 36px; font-size: var(--text-sm, 0.8125rem);">
                            </div>
                            <div>
                                <label style="display: block; font-size: var(--text-xs, 0.75rem); font-weight: 600; margin-bottom: 4px; color: var(--danger, #ef4444);">
                                    <i class="fas fa-circle-exclamation me-1"></i> Rusak Berat
                                </label>
                                <input type="number" step="0.01" min="0" name="jumlah_rusak_berat" id="labJumlahRusakBerat"
                                    class="form-control lab-jumlah-kondisi" value="0"
                                    style="height: 36px; font-size: var(--text-sm, 0.8125rem);">
                            </div>
                            <div>
                                <label style="display: block; font-size: var(--text-xs, 0.75rem); font-weight: 600; margin-bottom: 4px; color: var(--purple, #8b5cf6);">
                                    <i class="fas fa-hourglass-end me-1"></i> Kedaluwarsa
                                </label>
                                <input type="number" step="0.01" min="0" name="jumlah_kedaluwarsa" id="labJumlahKedaluwarsa"
                                    class="form-control lab-jumlah-kondisi" value="0"
                                    style="height: 36px; font-size: var(--text-sm, 0.8125rem);">
                            </div>
                        </div>

                        <div style="display: flex; gap: 8px; margin-top: 8px; flex-wrap: wrap;">
                            <button type="button" id="btnIsiSemuaBaik" class="btn btn-outline btn-sm"
                                style="font-size: var(--text-xs, 0.75rem); padding: 4px 10px;">
                                <i class="fas fa-check-double me-1"></i> Semua Baik
                            </button>
                            <small style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); align-self: center;">
                                Contoh: 10 traktor &rarr; 5 baik, 3 rusak ringan, 2 rusak berat.
                            </small>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 12px; margin-bottom: 14px;">
                        <div>
                            <label
                                style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Kondisi
                                Keseluruhan <span style="color: var(--danger, #ef4444);">*</span></label>
                            <select name="kondisi" id="labKondisi" class="form-control" required>
                                <option value="baik">Baik</option>
                                <option value="rusak_ringan">Rusak Ringan</option>
                                <option value="rusak_berat">Rusak Berat</option>
                                <option value="kedaluwarsa">Kedaluwarsa</option>
                            </select>
                            <small style="display: block; margin-top: 4px; font-size: var(--text-xs, 0.75rem); color: var(--text-muted);">
                                Otomatis mengikuti kondisi dengan jumlah terbanyak bila rincian diisi.
                            </small>
                        </div>
                        <div>
                            <label
                                style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Tanggal
                                Kedaluwarsa</label>
                            <input type="date" name="tgl_kedaluwarsa" id="labExp" class="form-control">
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; margin-bottom: 14px;">
                        <div>
                            <label
                                style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Tanggal
                                Pengadaan</label>
                            <input type="date" name="tanggal_pengadaan" id="labTglPengadaan" class="form-control">
                        </div>
                        <div>
                            <label
                                style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Tahun
                                Pengadaan</label>
                            <input type="number" name="tahun_pengadaan" id="labTahunPengadaan" class="form-control"
                                min="1900" max="{{ date('Y') + 1 }}" placeholder="{{ date('Y') }}">
                            <small style="display: block; margin-top: 4px; font-size: var(--text-xs, 0.75rem); color: var(--text-muted);">
                                Terisi otomatis dari tanggal pengadaan.
                            </small>
                        </div>
                        <div>
                            <label
                                style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Sumber Anggaran</label>
                            <select name="sumber_dana" id="labSumberDana" class="form-control">
                                <option value="BOS Reguler">BOS Reguler</option>
                                <option value="BOS Kinerja">BOS Kinerja</option>
                                <option value="DAK Fisik">DAK Fisik</option>
                                <option value="Komite / Yayasan">Komite / Yayasan</option>
                                <option value="Hibah / Bantuan">Hibah / Bantuan</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: 14px;">
                        <label
                            style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Lokasi
                            Lemari / Rak</label>
                        <input type="text" name="lokasi_lemari_rak" id="labRak" class="form-control"
                            placeholder="Lemari A - Rak 2">
                    </div>

                    <div class="form-group" style="margin-bottom: 20px;">
                        <label
                            style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">Spesifikasi
                            / Keterangan</label>
                        <textarea name="spesifikasi" id="labSpek" class="form-control" rows="2"
                            placeholder="Merk, ukuran, konsentrasi bahan..."></textarea>
                    </div>

                    <div style="display: flex; justify-content: flex-end; gap: 8px;">
                        <button type="button" class="btn btn-secondary" id="btnCancelModalLab">Batal</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Simpan
                            Item</button>
                    </div>
                </form>
            </div>
        </div>

