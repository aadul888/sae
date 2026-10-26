        {{-- ================= MODAL PERAWATAN ================= --}}
        <div id="modalPerawatan" class="modal-backdrop"
            style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
            <div class="card" style="width: 100%; max-width: 760px; max-height: 92vh; overflow-y: auto; padding: var(--space-10); border-radius: var(--radius-lg);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--space-9);">
                    <h3 id="modalPerawatanTitle" style="margin: 0; font-size: var(--text-xl); font-weight: var(--font-bold); color: var(--text-color);">Catatan Perawatan Alat</h3>
                    <button type="button" id="btnClosePerawatan" style="background: none; border: none; font-size: var(--text-xl); color: var(--text-muted); cursor: pointer;" title="Tutup">&times;</button>
                </div>

                <form id="formPerawatan" method="POST" action="{{ route('dashboard.laboran.perawatan.store') }}">
                    @csrf
                    <input type="hidden" name="_method" id="perawatanMethod" value="POST">

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-6); margin-bottom: var(--space-6);">
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Pilih Alat dari Inventaris</label>
                            <select name="item_id" id="prwItem" class="form-control">
                                <option value="">-- Atau isi nama alat manual --</option>
                                @foreach ($itemOptions as $io)
                                    <option value="{{ $io->id }}">{{ $io->nama_item }} ({{ $io->kode_item }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Nama Alat</label>
                            <input type="text" name="nama_alat" id="prwNama" class="form-control" placeholder="Nama alat jika tidak dipilih dari inventaris">
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-6); margin-bottom: var(--space-6);">
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">
                                Jenis Perawatan <span style="color: var(--danger, #ef4444);">*</span>
                            </label>
                            <select name="jenis_perawatan" id="prwJenis" class="form-control" required>
                                @foreach ($jenisList as $k => $v)
                                    <option value="{{ $k }}">{{ $v }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Kategori Risiko K3</label>
                            <select name="kategori_k3" id="prwK3" class="form-control">
                                @foreach ($k3List as $k => $v)
                                    <option value="{{ $k }}" {{ $k === 'umum' ? 'selected' : '' }}>{{ $v }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: var(--space-6); margin-bottom: var(--space-6);">
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">
                                Tanggal Perawatan <span style="color: var(--danger, #ef4444);">*</span>
                            </label>
                            <input type="date" name="tanggal_perawatan" id="prwTanggal" class="form-control" required>
                        </div>
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Periode Berikutnya</label>
                            <input type="date" name="periode_berikutnya" id="prwBerikut" class="form-control">
                        </div>
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Lokasi</label>
                            <select name="ruang_lab_nama" id="prwLokasi" class="form-control">
                                <option value="">-- Pilih --</option>
                                @foreach ($lokasiOptions as $lok)
                                    <option value="{{ $lok }}">{{ $lok }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-6); margin-bottom: var(--space-6);">
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Pelaksana</label>
                            <input type="text" name="pelaksana_nama" id="prwPelaksana" class="form-control" placeholder="Nama petugas pelaksana">
                        </div>
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Status</label>
                            <select name="status" id="prwStatus" class="form-control">
                                @foreach ($statusList as $k => $v)
                                    <option value="{{ $k }}">{{ $v }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: var(--space-6);">
                        <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Uraian Pekerjaan</label>
                        <textarea name="uraian_pekerjaan" id="prwUraian" class="form-control" rows="2" placeholder="Contoh: Pembersihan lensa, pengecekan kalibrasi, pelumasan bagian mekanik..."></textarea>
                    </div>

                    <div class="form-group" style="margin-bottom: var(--space-6);">
                        <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Prosedur K3 yang Diterapkan</label>
                        <textarea name="prosedur_k3" id="prwProsedur" class="form-control" rows="2" placeholder="Contoh: Alat dimatikan & dicabut dari listrik sebelum dibersihkan, area kerja diberi tanda..."></textarea>
                    </div>

                    <div class="form-group" style="margin-bottom: var(--space-6);">
                        <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">
                            <i class="fas fa-shield-halved me-1 text-primary"></i> APD yang Digunakan
                        </label>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: var(--space-3); padding: var(--space-5); border: 1px solid var(--border-color); border-radius: var(--radius-md);">
                            @foreach ($apdList as $i => $apd)
                                <label style="display: flex; align-items: center; gap: var(--space-3); font-size: var(--text-sm); color: var(--text-color); cursor: pointer; margin: 0;">
                                    <input type="checkbox" name="apd_digunakan[]" value="{{ $apd }}" class="prw-apd" style="cursor: pointer;">
                                    {{ $apd }}
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: var(--space-6); margin-bottom: var(--space-6);">
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Kondisi Akhir</label>
                            <select name="kondisi_akhir" id="prwKondisi" class="form-control">
                                <option value="">-- Pilih --</option>
                                @foreach ($kondisiList as $k => $v)
                                    <option value="{{ $k }}">{{ $v }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Biaya (Rp)</label>
                            <input type="number" step="0.01" min="0" name="biaya" id="prwBiaya" class="form-control" placeholder="0">
                        </div>
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Kode Alat</label>
                            <input type="text" name="kode_alat" id="prwKode" class="form-control" placeholder="Otomatis dari inventaris">
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: var(--space-6);">
                        <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Hasil Perawatan</label>
                        <textarea name="hasil" id="prwHasil" class="form-control" rows="2" placeholder="Hasil pengecekan / perbaikan yang dilakukan..."></textarea>
                    </div>

                    <div class="form-group" style="margin-bottom: var(--space-9);">
                        <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Catatan</label>
                        <textarea name="catatan" id="prwCatatan" class="form-control" rows="2" placeholder="Catatan tambahan..."></textarea>
                    </div>

                    <div style="display: flex; justify-content: flex-end; gap: var(--space-4);">
                        <button type="button" class="btn btn-secondary" id="btnCancelPerawatan">Batal</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Simpan</button>
                    </div>
                </form>
            </div>
        </div>

