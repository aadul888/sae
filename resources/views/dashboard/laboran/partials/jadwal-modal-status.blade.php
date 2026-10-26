        {{-- Modal Update Status & Laporan Kerusakan --}}
        <div id="modalStatus" class="modal-backdrop"
            style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
            <div class="card" style="width: 100%; max-width: 480px; padding: var(--space-10); border-radius: var(--radius-lg);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--space-8);">
                    <h3 style="margin: 0; font-size: var(--text-xl); font-weight: var(--font-bold); color: var(--text-color);">
                        Update Status Praktik
                    </h3>
                    <button type="button" id="btnCloseModalStatus"
                        style="background: none; border: none; font-size: var(--text-xl); color: var(--text-muted); cursor: pointer;"
                        title="Tutup">&times;</button>
                </div>

                <form id="formStatus" method="POST" action="">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="form_mode" value="status">
                    <p id="statusDesc" style="font-size: var(--text-base); color: var(--text-color); margin-bottom: var(--space-8);"></p>

                    <div class="form-group" style="margin-bottom: var(--space-7);">
                        <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">
                            Pilih Status <span style="color: var(--danger, #ef4444);">*</span>
                        </label>
                        <select name="status" id="selectStatusLab" class="form-control" required>
                            @foreach (\App\Http\Controllers\LaboranJadwalController::STATUS_LIST as $sKey => $sLabel)
                                <option value="{{ $sKey }}">{{ $sLabel }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group" style="margin-bottom: var(--space-9);">
                        <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">
                            Laporan Kerusakan / Insiden Pasca-Praktik
                        </label>
                        <textarea name="laporan_kerusakan" id="inputLaporanKerusakan" class="form-control" rows="2"
                            placeholder="Catat jika ada alat rusak, lahan terganggu, atau insiden lain..."></textarea>
                    </div>

                    <div style="display: flex; justify-content: flex-end; gap: var(--space-4);">
                        <button type="button" class="btn btn-secondary" id="btnCancelModalStatus">Batal</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-check me-1"></i> Simpan Status</button>
                    </div>
                </form>
            </div>
        </div>
