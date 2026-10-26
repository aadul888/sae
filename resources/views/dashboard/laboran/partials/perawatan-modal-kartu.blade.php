        {{-- ================= MODAL KARTU PERAWATAN ================= --}}
        <div id="modalKartu" class="modal-backdrop"
            style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
            <div class="card" style="width: 100%; max-width: 660px; max-height: 92vh; overflow-y: auto; padding: var(--space-10); border-radius: var(--radius-lg);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--space-9);">
                    <h3 id="modalKartuTitle" style="margin: 0; font-size: var(--text-xl); font-weight: var(--font-bold); color: var(--text-color);">Kartu Perawatan</h3>
                    <button type="button" id="btnCloseKartu" style="background: none; border: none; font-size: var(--text-xl); color: var(--text-muted); cursor: pointer;" title="Tutup">&times;</button>
                </div>

                <form id="formKartu" method="POST" action="{{ route('dashboard.laboran.perawatan.kartu.store') }}">
                    @csrf
                    <input type="hidden" name="_method" id="kartuMethod" value="POST">

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-6); margin-bottom: var(--space-6);">
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Alat dari Inventaris</label>
                            <select name="item_id" id="krtItem" class="form-control">
                                <option value="">-- Atau isi manual --</option>
                                @foreach ($itemOptions as $io)
                                    <option value="{{ $io->id }}">{{ $io->nama_item }} ({{ $io->kode_item }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">
                                Nama Alat <span style="color: var(--danger, #ef4444);">*</span>
                            </label>
                            <input type="text" name="nama_alat" id="krtNama" class="form-control" placeholder="Nama alat">
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-6); margin-bottom: var(--space-6);">
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Merk / Tipe</label>
                            <input type="text" name="merk_tipe" id="krtMerk" class="form-control" placeholder="Contoh: Olympus CX23">
                        </div>
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Kode Alat</label>
                            <input type="text" name="kode_alat" id="krtKode" class="form-control" placeholder="Otomatis dari inventaris">
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-6); margin-bottom: var(--space-6);">
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Lokasi</label>
                            <select name="ruang_lab_nama" id="krtLokasi" class="form-control">
                                <option value="">-- Pilih --</option>
                                @foreach ($lokasiOptions as $lok)
                                    <option value="{{ $lok }}">{{ $lok }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Penanggung Jawab</label>
                            <input type="text" name="penanggung_jawab_nama" id="krtPj" class="form-control" placeholder="Nama penanggung jawab">
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: var(--space-6); margin-bottom: var(--space-6);">
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Tanggal Mulai</label>
                            <input type="date" name="tanggal_mulai" id="krtMulai" class="form-control">
                        </div>
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">
                                Interval (hari) <span style="color: var(--danger, #ef4444);">*</span>
                            </label>
                            <input type="number" min="1" max="3650" name="interval_hari" id="krtInterval" class="form-control" value="30" required>
                        </div>
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Status</label>
                            <select name="status" id="krtStatus" class="form-control">
                                <option value="aktif">Aktif</option>
                                <option value="nonaktif">Nonaktif</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: var(--space-6);">
                        <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Instruksi Perawatan</label>
                        <textarea name="instruksi_perawatan" id="krtInstruksi" class="form-control" rows="3" placeholder="Langkah-langkah perawatan rutin yang harus dilakukan..."></textarea>
                    </div>

                    <div class="form-group" style="margin-bottom: var(--space-6);">
                        <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">
                            <i class="fas fa-shield-halved me-1 text-primary"></i> APD Wajib
                        </label>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: var(--space-3); padding: var(--space-5); border: 1px solid var(--border-color); border-radius: var(--radius-md);">
                            @foreach ($apdList as $apd)
                                <label style="display: flex; align-items: center; gap: var(--space-3); font-size: var(--text-sm); color: var(--text-color); cursor: pointer; margin: 0;">
                                    <input type="checkbox" name="apd_wajib[]" value="{{ $apd }}" class="krt-apd" style="cursor: pointer;">
                                    {{ $apd }}
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: var(--space-9);">
                        <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Catatan</label>
                        <textarea name="catatan" id="krtCatatan" class="form-control" rows="2"></textarea>
                    </div>

                    <div style="display: flex; justify-content: flex-end; gap: var(--space-4);">
                        <button type="button" class="btn btn-secondary" id="btnCancelKartu">Batal</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Simpan Kartu</button>
                    </div>
                </form>
            </div>
        </div>

