    <!-- MODAL 5: Dialog Pilih Tanggal Cetak untuk Lembar Pengambilan -->
    <div id="modalCetakPengambilan" class="modal-backdrop"
        style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.65); backdrop-filter: blur(4px); z-index: 99999 !important; align-items: center; justify-content: center; padding: 20px;">
        <div class="card"
            style="background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 14px; width: 100%; max-width: 480px; box-shadow: 0 25px 50px rgba(0,0,0,0.4); padding: 22px;">
            <div
                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">
                <h4
                    style="margin: 0; font-size: 1.05rem; color: var(--text-color); font-weight: 700; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-clipboard-check text-primary"></i> Cetak Lembar Pengambilan Kartu
                </h4>
                <button type="button" onclick="closeModalCetakPengambilan()"
                    style="background: none; border: none; font-size: 1.2rem; color: var(--text-muted); cursor: pointer;">&times;</button>
            </div>
            <form action="{{ route('dashboard.kartu-pelajar.cetak-pengambilan') }}" method="GET" target="_blank">
                <div style="margin-bottom: 14px;">
                    <label
                        style="display: block; font-size: 0.82rem; font-weight: 700; margin-bottom: 4px; color: var(--text-color);">Pilih
                        Tanggal Selesai Dicetak <span style="color: #ef4444;">*</span></label>
                    <input type="date" name="tanggal_cetak" value="{{ date('Y-m-d') }}" required class="form-control"
                        style="width: 100%; height: 38px; padding: 0 10px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 6px; font-size: 0.86rem;">
                </div>
                <div style="margin-bottom: 18px;">
                    <label
                        style="display: block; font-size: 0.82rem; font-weight: 700; margin-bottom: 4px; color: var(--text-color);">Filter
                        Rombel / Kelas (Opsional)</label>
                    <select name="rombel" class="form-control"
                        style="width: 100%; height: 38px; padding: 0 10px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 6px; font-size: 0.84rem;">
                        <option value="">Semua Rombel (Seluruh Siswa yang Dicetak)</option>
                        @foreach ($rombelList as $r)
                            <option value="{{ $r->nama }}">{{ $r->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 8px;">
                    <button type="button" onclick="closeModalCetakPengambilan()" class="btn btn-outline"
                        style="padding: 7px 14px; font-size: 0.82rem;">Batal</button>
                    <button type="submit" class="btn btn-primary" onclick="closeModalCetakPengambilan()"
                        style="padding: 7px 18px; font-size: 0.82rem; font-weight: 700; display: inline-flex; align-items: center; gap: 6px;"><i
                            class="fas fa-print"></i> Buka Lembar Cetak</button>
                </div>
            </form>
        </div>
    </div>
