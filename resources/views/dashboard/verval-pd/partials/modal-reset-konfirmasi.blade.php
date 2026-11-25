{{-- Modal: Reset status konfirmasi ke Belum Konfirmasi (semua / per kelas) --}}
<div id="modalResetKonfirmasi" class="modal-backdrop"
    style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
    <div class="card"
        style="width: 100%; max-width: 460px; padding: 24px; border-radius: var(--radius-lg, 12px); margin: 16px;">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div
                    style="width: 42px; height: 42px; border-radius: var(--radius-lg, 12px); background: rgba(245,158,11,0.12); color: var(--warning, #f59e0b); display: flex; align-items: center; justify-content: center; font-size: var(--text-xl, 1.25rem); flex-shrink: 0;">
                    <i class="fas fa-rotate-left"></i>
                </div>
                <div>
                    <h3 style="font-size: var(--text-lg, 1.125rem); font-weight: 800; color: var(--text-color); margin: 0;">
                        Reset Konfirmasi</h3>
                    <p style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); margin: 2px 0 0 0;">
                        Kembalikan status konfirmasi ke <strong>Belum Konfirmasi</strong>.
                    </p>
                </div>
            </div>
            <button type="button" class="close-modal" data-target="#modalResetKonfirmasi"
                style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: var(--text-lg, 1.125rem); padding: 4px;"
                aria-label="Tutup"><i class="fas fa-times"></i></button>
        </div>

        <div style="background: var(--bg-hover); border: 1px solid var(--border-color); border-radius: var(--radius-md, 8px); padding: 12px 14px; margin-bottom: 16px;">
            <p style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); margin: 0; line-height: 1.5;">
                Pilih cakupan reset. Riwayat usulan perubahan peserta didik <strong>tetap utuh</strong>.
            </p>
        </div>

        <div class="form-group" style="margin-bottom: 16px;">
            <label
                style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">
                Cakupan
            </label>
            <select id="resetKonfirmasiRombel" class="form-control" style="width: 100%; border-radius: var(--radius-md, 8px); height: 42px;">
                <option value="">Semua Kelas (seluruh peserta didik)</option>
                @foreach (($filterRombel ?? collect()) as $r)
                    <option value="{{ $r }}">Kelas {{ $r }}</option>
                @endforeach
            </select>
        </div>

        <div style="display: flex; justify-content: flex-end; gap: 8px;">
            <button type="button" class="btn btn-outline close-modal" data-target="#modalResetKonfirmasi"
                style="border-radius: var(--radius-md, 8px);">Batal</button>
            <button type="button" class="btn btn-primary" id="btnResetKonfirmasiProses"
                style="border-radius: var(--radius-md, 8px); color: #fff; background: var(--warning, #f59e0b); border-color: var(--warning, #f59e0b);">
                <i class="fas fa-rotate-left me-1"></i> Reset Sekarang
            </button>
        </div>
    </div>
</div>
