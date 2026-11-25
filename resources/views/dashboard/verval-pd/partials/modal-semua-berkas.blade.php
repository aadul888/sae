{{-- MODAL: Lihat SEMUA berkas sekaligus (grid) — sekali buka, tanpa tutup-buka per file --}}
<div id="modalSemuaBerkas" class="modal-backdrop"
    style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.85); z-index: 100010 !important; align-items: center; justify-content: center; backdrop-filter: blur(5px);">
    <div class="card"
        style="max-width: 1400px; width: 98%; height: 94vh; max-height: 94vh; display: flex; flex-direction: column; margin: 0; border-radius: var(--radius-xl, 16px); padding: 18px;">
        <div
            style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; padding-bottom: 10px; border-bottom: 1px solid var(--border-color); gap: 10px;">
            <div style="display: flex; align-items: center; gap: 10px; min-width: 0;">
                <div
                    style="width: 38px; height: 38px; border-radius: var(--radius-md, 8px); background: rgba(99,102,241,0.12); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: var(--text-xl, 1.25rem); flex-shrink: 0;">
                    <i class="fas fa-images"></i>
                </div>
                <div style="min-width: 0;">
                    <h3 id="semuaBerkasTitle"
                        style="font-size: var(--text-md, 1rem); font-weight: 800; color: var(--text-color); margin: 0;">
                        Semua Berkas</h3>
                    <div id="semuaBerkasSubtitle"
                        style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-top: 2px;">
                        Lihat seluruh dokumen sekaligus
                    </div>
                </div>
            </div>
            <button type="button" id="btnTutupSemuaBerkas"
                style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: var(--text-xl, 1.25rem); padding: 4px;"
                aria-label="Tutup">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div id="semuaBerkasGrid"
            style="flex: 1; min-height: 0; overflow-y: auto; display: grid; grid-template-columns: repeat(auto-fit, minmax(420px, 1fr)); gap: 14px; align-content: start; padding-right: 4px;">
        </div>
    </div>
</div>
