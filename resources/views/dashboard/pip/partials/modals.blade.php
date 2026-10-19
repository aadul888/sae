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
            <input type="hidden" name="fase" value="{{ $pipStatusInfo['fase'] ?? 'fase_1' }}">
            <input type="hidden" name="tahun_pelajaran" value="{{ $pipStatusInfo['tahun_pelajaran'] ?? '' }}">
            <input type="hidden" name="judul_usulan"
                value="{{ $pipStatusInfo['judul_usulan'] ?? 'Usulan PIP ' . $activeTahun }}">

            <div class="kp-modal-body">
                @php
                    $activeJudul = $pipStatusInfo['judul_usulan'] ?? 'Usulan PIP Tahun ' . ($activeTahun ?? date('Y'));
                    $activeFase = $pipStatusInfo['fase_label'] ?? 'Fase 1 (Januari - Juni)';
                    $activeTp = $pipStatusInfo['tahun_pelajaran'] ?? null;
                @endphp
                <div
                    style="margin-bottom: 14px; padding: 10px 14px; border-radius: 8px; background: rgba(99,102,241,0.08); border: 1px solid rgba(99,102,241,0.25); font-size: 0.8rem; display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap;">
                    <div>
                        <div style="font-weight: 800; color: var(--primary);">{{ $activeJudul }}</div>
                        <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 1px;">
                            {{ $activeFase }}@if ($activeTp)
                                &bull; TP {{ $activeTp }}
                            @endif
                        </div>
                    </div>
                    <span class="badge badge-primary" style="font-size: 0.68rem; padding: 2px 7px;">Tahun
                        {{ $activeTahun }}</span>
                </div>

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
    <!-- MODAL: Import Excel SiPintar (SK Pemberian & SK Nominasi) -->
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
                        <label class="kp-form-label" for="pipJenisSk">Jenis SK SiPintar <span
                                class="req">*</span></label>
                        <select id="pipJenisSk" name="jenis_sk" class="form-control" required style="font-weight: 700;"
                            onchange="pipOnJenisSkChange(this.value)">
                            <option value="pemberian" selected>SK Pemberian (Pencairan Dana PIP)</option>
                            <option value="nominasi">SK Nominasi (Aktivasi Rekening Siswa Baru/Penerima Baru)</option>
                        </select>
                        <div class="kp-form-hint" id="pipJenisSkHint" style="margin-top: 4px; font-weight: 600;">
                            <i class="fas fa-check-circle text-success me-1"></i> SK Pemberian: Menyimpan data penerima
                            resmi beserta status pencairan dana.
                        </div>
                    </div>

                    <div class="kp-form-group">
                        <label class="kp-form-label" for="pipFileExcel">Berkas Excel (.xls / .xlsx) / CSV <span
                                class="req">*</span></label>
                        <input type="file" id="pipFileExcel" name="file_excel" accept=".xlsx,.xls,.csv,.txt"
                            class="form-control" required>
                        <div class="kp-form-hint">
                            Mendukung file unduhan langsung dari SiPintar (.xls, .xlsx, .csv, maks. 30 MB).<br>
                            <span id="pipValidationRuleHint" style="color: var(--primary); font-weight: 600;">
                                &bull; Pada SK Nominasi, sistem otomatis memvalidasi agar peserta didik yang sudah ada
                                di SK Pemberian tidak tersimpan ganda.
                            </span>
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
                        <label class="kp-form-label" for="rwJenisSk">Jenis SK <span class="req">*</span></label>
                        <select id="rwJenisSk" name="jenis_sk" class="form-control" required
                            style="font-weight: 700;">
                            <option value="pemberian" selected>SK Pemberian (Pencairan Dana)</option>
                            <option value="nominasi">SK Nominasi (Aktivasi Rekening)</option>
                        </select>
                        <div class="kp-form-hint">Khusus SK Nominasi: Siswa yang sudah ada pada SK Pemberian akan
                            divalidasi dan ditolak.</div>
                    </div>

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
                                maxlength="100" placeholder="Misal: Tahap 1 / Tahap 2">
                        </div>
                        <div class="kp-form-group">
                            <label class="kp-form-label" for="rwSk">Nomor SK</label>
                            <input type="text" id="rwSk" name="nomor_sk" class="form-control"
                                maxlength="100">
                        </div>
                        <div class="kp-form-group">
                            <label class="kp-form-label" for="rwCair">Status Pencairan</label>
                            <input type="text" id="rwCair" name="status_cair" class="form-control"
                                maxlength="50" placeholder="Sudah Cair / Belum Cair / dsb.">
                        </div>
                        <div class="kp-form-group" style="grid-column: span 2;">
                            <label class="kp-form-label" for="rwAktivasi">Tanggal Aktivasi Rekening (Khusus
                                Nominasi)</label>
                            <input type="text" id="rwAktivasi" name="tanggal_aktivasi" class="form-control"
                                maxlength="30" placeholder="YYYY-MM-DD atau sesuai dokumen bank">
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
