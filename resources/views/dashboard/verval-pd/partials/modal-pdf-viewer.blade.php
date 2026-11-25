{{-- MODAL: Viewer Dokumen PDF Terpadu (dipakai tab Berkas & Residu) --}}
<div id="modalPdfViewer" class="modal-backdrop"
    style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.78); z-index: 100005 !important; align-items: center; justify-content: center; backdrop-filter: blur(5px);">
    <div class="card"
        style="max-width: 960px; width: 96%; height: 90vh; max-height: 90vh; display: flex; flex-direction: column; margin: 0; border-radius: var(--radius-xl, 16px); padding: 18px;">
        <div
            style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; padding-bottom: 10px; border-bottom: 1px solid var(--border-color);">
            <div style="display: flex; align-items: center; gap: 10px;">
                <div
                    style="width: 38px; height: 38px; border-radius: var(--radius-md, 8px); background: rgba(239,68,68,0.12); color: var(--danger, #ef4444); display: flex; align-items: center; justify-content: center; font-size: var(--text-xl, 1.25rem); flex-shrink: 0;">
                    <i class="fas fa-file-pdf"></i>
                </div>
                <div>
                    <h3 id="pdfViewerTitle"
                        style="font-size: var(--text-md, 1rem); font-weight: 800; color: var(--text-color); margin: 0;">
                        Pratinjau Dokumen PDF</h3>
                    <div id="pdfViewerSubtitle"
                        style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-top: 2px;">
                        Review berkas langsung di dalam sistem tanpa membuka tab baru
                    </div>
                </div>
            </div>
            <button type="button" class="close-pdf-viewer"
                style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: var(--text-xl, 1.25rem); padding: 4px;"
                aria-label="Tutup">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div
            style="flex: 1; min-height: 0; border-radius: var(--radius-lg, 12px); overflow: hidden; background: var(--text-main, #0f172a); border: 1px solid var(--border-color); position: relative;">
            <iframe id="pdfViewerFrame" src="" style="width: 100%; height: 100%; border: none;"></iframe>
        </div>

        <div id="pdfViewerActionBar"
            style="display: flex; justify-content: space-between; align-items: center; margin-top: 12px; padding-top: 10px; border-top: 1px solid var(--border-color); flex-wrap: wrap; gap: 10px;">
            <div id="pdfViewerVerifControls"
                style="display: none; align-items: center; gap: 12px; flex-wrap: wrap;">
                <span style="font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-color);">
                    <i class="fas fa-clipboard-check text-primary me-1"></i> Validasi:
                </span>
                <label
                    style="display: inline-flex; align-items: center; gap: 6px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--success, #10b981); cursor: pointer; margin: 0;">
                    <input type="radio" name="pdf_viewer_status" value="valid" id="pdfViewerRadioValid"
                        class="pdf-viewer-radio">
                    <i class="fas fa-circle-check"></i> Valid / Sesuai
                </label>
                <label
                    style="display: inline-flex; align-items: center; gap: 6px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--danger, #ef4444); cursor: pointer; margin: 0;">
                    <input type="radio" name="pdf_viewer_status" value="tidak_valid"
                        id="pdfViewerRadioTidakValid" class="pdf-viewer-radio">
                    <i class="fas fa-circle-xmark"></i> Tidak Valid / Tidak Sesuai
                </label>
                <input type="text" id="pdfViewerCatatan" placeholder="Catatan penolakan jika tidak valid..."
                    style="display: none; font-size: var(--text-sm, 0.8125rem); height: 32px; padding: 4px 10px; border-radius: var(--radius-sm, 6px); border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-color); width: 260px;">
                <button type="button" id="btnPdfViewerSimpan" class="btn btn-primary"
                    style="padding: 6px 14px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; border-radius: var(--radius-sm, 6px); display: inline-flex; align-items: center; gap: 6px;">
                    <i class="fas fa-save"></i> Simpan Status
                </button>
            </div>
            <div style="display: flex; gap: 8px; margin-left: auto;">
                <button type="button" class="btn btn-outline close-pdf-viewer"
                    style="border-radius: var(--radius-md, 8px); padding: 7px 18px; font-size: var(--text-sm, 0.8125rem);">
                    Tutup Pratinjau
                </button>
            </div>
        </div>
    </div>
</div>
