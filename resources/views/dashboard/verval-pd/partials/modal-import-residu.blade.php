{{-- Modal Import Berkas Residu (Excel dari Dukcapil) --}}
<div id="modalImportResidu" class="modal-backdrop"
    style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
    <div class="card"
        style="width: 100%; max-width: 520px; padding: 24px; border-radius: var(--radius-lg, 12px); margin: 16px;">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div
                    style="width: 42px; height: 42px; border-radius: var(--radius-lg, 12px); background: rgba(6,182,212,0.12); color: var(--accent); display: flex; align-items: center; justify-content: center; font-size: var(--text-xl, 1.25rem); flex-shrink: 0;">
                    <i class="fas fa-file-import"></i>
                </div>
                <div>
                    <h3 style="font-size: var(--text-lg, 1.125rem); font-weight: 800; color: var(--text-color); margin: 0;">
                        Import Berkas Residu</h3>
                    <p style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); margin: 2px 0 0 0;">
                        Unggah berkas hasil penyandingan Dukcapil.
                    </p>
                </div>
            </div>
            <button type="button" class="close-modal" data-target="#modalImportResidu"
                style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: var(--text-lg, 1.125rem); padding: 4px;"
                aria-label="Tutup"><i class="fas fa-times"></i></button>
        </div>

        <form id="formImportResidu" enctype="multipart/form-data">
            @csrf
            <div style="background: var(--bg-hover); border: 1px solid var(--border-color); border-radius: var(--radius-md, 8px); padding: 12px 14px; margin-bottom: 16px;">
                <div
                    style="font-size: var(--text-xs, 0.75rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 4px;">
                    <i class="fas fa-circle-info me-1"></i> Catatan
                </div>
                <p style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); margin: 0; line-height: 1.5;">
                    Kolom centang (✓/✗) diambil <strong>apa adanya</strong> dari berkas — sistem tidak membandingkan
                    ulang. Import baru akan <strong>mengganti seluruh data Residu</strong> sebelumnya.
                </p>
            </div>

            <div class="form-group" style="margin-bottom: 16px;">
                <label
                    style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">Berkas
                    Residu (.xlsx / .xls / .csv) <span class="text-danger">*</span></label>
                <input type="file" id="fileResidu" name="file_residu" class="form-control" required
                    accept=".xlsx,.xls,.csv,.txt" style="width: 100%; border-radius: var(--radius-md, 8px);">
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center; gap: 8px;">
                <button type="button" class="btn btn-outline" id="btnTemplateFromModal"
                    style="border-radius: var(--radius-md, 8px);">
                    <i class="fas fa-file-arrow-down me-1"></i> Unduh Template
                </button>
                <div style="display: flex; gap: 8px;">
                    <button type="button" class="btn btn-outline close-modal" data-target="#modalImportResidu"
                        style="border-radius: var(--radius-md, 8px);">Batal</button>
                    <button type="submit" class="btn btn-primary" style="border-radius: var(--radius-md, 8px);">
                        <i class="fas fa-upload me-1"></i> Import
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
