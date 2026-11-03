{{-- Modal Atur Nilai Buku Induk (dipakai di tab Buku Induk & tab Alumni peserta didik) --}}
<div id="modalNilaiBukuInduk" class="modal-backdrop"
    style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.65); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
    <div class="card"
        style="max-width: 880px; width: 95%; max-height: 90vh; display: flex; flex-direction: column; margin: 0; border-radius: var(--radius-xl, 16px); padding: 20px;">
        <div
            style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; padding-bottom: 12px; border-bottom: 1px solid var(--border-color); flex-shrink: 0;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div
                    style="width: 42px; height: 42px; border-radius: var(--radius-lg, 12px); background: rgba(245,158,11,0.12); color: var(--warning, #f59e0b); display: flex; align-items: center; justify-content: center; font-size: var(--text-xl, 1.25rem); flex-shrink: 0;">
                    <i class="fas fa-table-list"></i>
                </div>
                <div>
                    <h3 id="modalNilaiTitle"
                        style="font-size: var(--text-lg, 1.125rem); font-weight: 800; color: var(--text-color); margin: 0;">
                        Atur Nilai Buku Induk
                    </h3>
                    <div id="modalNilaiSubtitle"
                        style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); margin-top: 2px;">
                        Memuat nilai...
                    </div>
                </div>
            </div>
            <button type="button" class="close-modal" data-target="#modalNilaiBukuInduk" aria-label="Tutup"
                title="Tutup"
                style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: var(--text-lg, 1.125rem); padding: 4px;">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div id="nilaiLoading" style="text-align: center; padding: 40px; color: var(--text-muted);">
            <i class="fas fa-spinner fa-spin me-2" style="font-size: var(--text-2xl, 1.5rem);"></i>
            <div>Memuat nilai siswa...</div>
        </div>

        <form id="formNilaiBukuInduk"
            style="display: none; flex-direction: column; flex: 1; min-height: 0; overflow: hidden;">
            @csrf
            <input type="hidden" id="nilaiPesertaDidikId">
            <input type="hidden" id="nilaiAkademik" value="aktif">
            <div id="nilaiTableWrap"
                style="overflow: auto; flex: 1; min-height: 0; -webkit-overflow-scrolling: touch;">
            </div>
            <div
                style="display: flex; justify-content: flex-end; gap: 8px; margin-top: 12px; padding-top: 12px; border-top: 1px solid var(--border-color); flex-shrink: 0;">
                <button type="button" class="btn btn-outline close-modal" data-target="#modalNilaiBukuInduk"
                    style="border-radius: var(--radius-md, 8px); padding: 8px 16px; font-size: var(--text-sm, 0.8125rem);">
                    Batal
                </button>
                <button type="submit" id="btnSimpanNilai" class="btn btn-primary"
                    style="border-radius: var(--radius-md, 8px); padding: 8px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; display: inline-flex; align-items: center; gap: 6px;">
                    <i class="fas fa-save"></i> Simpan Nilai
                </button>
            </div>
        </form>
    </div>
</div>
