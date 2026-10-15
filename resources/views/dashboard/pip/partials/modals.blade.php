<!-- MODAL: Tambah Usulan PIP -->
<div id="modalTambahUsulan" class="modal-backdrop">
    <div class="card kp-modal-card">
        <div class="kp-modal-header">
            <h4 class="kp-modal-title"><i class="fas fa-hand-holding-dollar text-primary"></i> Tambah Usulan PIP</h4>
            <button type="button" class="kp-modal-close" onclick="closeTambahUsulan()" aria-label="Tutup">&times;</button>
        </div>
        <form id="formTambahUsulan" class="kp-modal-form" action="{{ route('dashboard.pip.store-usulan') }}"
            method="POST">
            @csrf
            <input type="hidden" name="peserta_didik_id" id="pipPdId">
            <input type="hidden" name="tahun" value="{{ $activeTahun }}">
            <div class="kp-modal-body">
                <div class="kp-form-group">
                    <label class="kp-form-label" for="pipCari">Cari Peserta Didik <span class="req">*</span></label>
                    <div class="kp-ac-wrap">
                        <input type="text" id="pipCari" class="form-control" autocomplete="off"
                            placeholder="Ketik nama / NISN / NIPD (min. 2 huruf)...">
                        <div id="pipAcList" class="kp-ac-list"></div>
                    </div>
                    <div class="kp-form-hint" id="pipPilihInfo">Belum ada peserta didik dipilih.</div>
                </div>

                <div class="kp-form-group">
                    <label class="kp-form-label">Kriteria / Alasan Diusulkan <span class="req">*</span></label>
                    <div style="display: grid; gap: 6px;">
                        @foreach ($kriteriaAlasan as $kKey => $kLabel)
                            <label
                                style="display: flex; align-items: center; gap: 8px; font-size: 0.84rem; cursor: pointer; padding: 6px 10px; border: 1px solid var(--border-color); border-radius: 8px;">
                                <input type="checkbox" name="alasan_usulan[]" value="{{ $kKey }}">
                                <span>{{ $kLabel }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="kp-form-group">
                    <label class="kp-form-label" for="pipKeterangan">Keterangan Tambahan</label>
                    <textarea id="pipKeterangan" name="keterangan" rows="3" maxlength="1000" class="form-control"
                        placeholder="Kondisi ekonomi / catatan pendukung usulan..."></textarea>
                </div>
            </div>
            <div class="kp-modal-footer">
                <button type="button" onclick="closeTambahUsulan()" class="btn btn-outline">Batal</button>
                <button type="submit" class="btn btn-primary" id="pipSubmitUsulan" disabled>
                    <i class="fas fa-save me-1"></i> Simpan Usulan
                </button>
            </div>
        </form>
    </div>
</div>

@if ($isAdmin)
    <!-- MODAL: Import Excel SiPintar -->
    <div id="modalImportRiwayat" class="modal-backdrop">
        <div class="card kp-modal-card">
            <div class="kp-modal-header">
                <h4 class="kp-modal-title"><i class="fas fa-file-excel text-success"></i> Import Riwayat dari SiPintar
                </h4>
                <button type="button" class="kp-modal-close" onclick="closeImportRiwayat()"
                    aria-label="Tutup">&times;</button>
            </div>
            <form id="formImportRiwayat" class="kp-modal-form" action="{{ route('dashboard.pip.import-riwayat') }}"
                method="POST" enctype="multipart/form-data">
                @csrf
                <div class="kp-modal-body">
                    <div class="kp-form-group">
                        <label class="kp-form-label" for="pipFileExcel">Berkas Excel / CSV <span
                                class="req">*</span></label>
                        <input type="file" id="pipFileExcel" name="file_excel" accept=".xlsx,.csv,.txt"
                            class="form-control" required>
                        <div class="kp-form-hint">
                            Gunakan file langsung dari SiPintar (.xlsx / .csv, maks. 20 MB). Kolom yang dibaca:
                            peserta_didik_id, nama_pd, nisn, rombel, kelas, nominal, nomor_sk, tanggal_sk,
                            tahap_keterangan, status_cair, tanggal_cair, no_rekening, nama_ibu_kandung, dan lainnya.
                            Data yang sama (peserta + SK + tahap) akan diperbarui, bukan digandakan.
                        </div>
                    </div>
                </div>
                <div class="kp-modal-footer">
                    <button type="button" onclick="closeImportRiwayat()" class="btn btn-outline">Batal</button>
                    <button type="submit" class="btn btn-primary" id="pipSubmitImport">
                        <i class="fas fa-upload me-1"></i> Import Sekarang
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL: Tambah Riwayat Manual -->
    <div id="modalTambahRiwayat" class="modal-backdrop">
        <div class="card kp-modal-card">
            <div class="kp-modal-header">
                <h4 class="kp-modal-title"><i class="fas fa-plus-circle text-primary"></i> Tambah Riwayat PIP</h4>
                <button type="button" class="kp-modal-close" onclick="closeTambahRiwayat()"
                    aria-label="Tutup">&times;</button>
            </div>
            <form id="formTambahRiwayat" class="kp-modal-form" action="{{ route('dashboard.pip.store-riwayat') }}"
                method="POST">
                @csrf
                <input type="hidden" name="peserta_didik_id" id="rwPdId">
                <div class="kp-modal-body">
                    <div class="kp-form-group">
                        <label class="kp-form-label" for="rwCari">Cari Peserta Didik (Opsional)</label>
                        <div class="kp-ac-wrap">
                            <input type="text" id="rwCari" class="form-control" autocomplete="off"
                                placeholder="Ketik nama / NISN...">
                            <div id="rwAcList" class="kp-ac-list"></div>
                        </div>
                    </div>
                    <div class="kp-form-grid">
                        <div class="kp-form-group">
                            <label class="kp-form-label" for="rwNama">Nama <span class="req">*</span></label>
                            <input type="text" id="rwNama" name="nama_pd" class="form-control" required>
                        </div>
                        <div class="kp-form-group">
                            <label class="kp-form-label" for="rwNisn">NISN</label>
                            <input type="text" id="rwNisn" name="nisn" class="form-control"
                                maxlength="20">
                        </div>
                        <div class="kp-form-group">
                            <label class="kp-form-label" for="rwRombel">Kelas / Rombel</label>
                            <input type="text" id="rwRombel" name="rombel" class="form-control"
                                maxlength="50">
                        </div>
                        <div class="kp-form-group">
                            <label class="kp-form-label" for="rwTahun">Tahun</label>
                            <input type="text" id="rwTahun" name="tahun" class="form-control"
                                value="{{ $activeTahun }}" maxlength="20">
                        </div>
                        <div class="kp-form-group">
                            <label class="kp-form-label" for="rwNominal">Nominal (Rp)</label>
                            <input type="number" id="rwNominal" name="nominal" class="form-control"
                                min="0">
                        </div>
                        <div class="kp-form-group">
                            <label class="kp-form-label" for="rwTahap">Tahap</label>
                            <input type="text" id="rwTahap" name="tahap" class="form-control"
                                maxlength="100">
                        </div>
                        <div class="kp-form-group">
                            <label class="kp-form-label" for="rwSk">Nomor SK</label>
                            <input type="text" id="rwSk" name="nomor_sk" class="form-control"
                                maxlength="100">
                        </div>
                        <div class="kp-form-group">
                            <label class="kp-form-label" for="rwCair">Status Cair</label>
                            <input type="text" id="rwCair" name="status_cair" class="form-control"
                                maxlength="50" placeholder="Sudah Cair / Belum Cair">
                        </div>
                    </div>
                </div>
                <div class="kp-modal-footer">
                    <button type="button" onclick="closeTambahRiwayat()" class="btn btn-outline">Batal</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Simpan</button>
                </div>
            </form>
        </div>
    </div>
@endif
