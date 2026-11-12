{{-- Modal Import & Perbarui Excel Peserta Didik Keluar / Alumni (Format Resmi Dapodik).
     Param: $importAction (route POST). --}}
<div id="modalImportExcel" class="modal-backdrop modal-import-pd-keluar"
    style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
    <div class="card"
        style="max-width: 580px; width: 94%; max-height: 90vh; display: flex; flex-direction: column; margin: 0; border-radius: var(--radius-xl, 16px); padding: 22px; background: var(--bg-card); border: 1px solid var(--border-color); box-shadow: 0 10px 30px rgba(0,0,0,0.4);">

        {{-- Header Modal --}}
        <div
            style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 1px solid var(--border-color);">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div
                    style="width: 42px; height: 42px; border-radius: var(--radius-lg, 12px); background: rgba(16,185,129,0.12); color: var(--success, #10b981); display: flex; align-items: center; justify-content: center; font-size: var(--text-xl, 1.25rem);">
                    <i class="fas fa-file-excel"></i>
                </div>
                <div>
                    <h3 style="font-size: var(--text-lg, 1.125rem); font-weight: 700; color: var(--text-color); margin: 0;">
                        Import &amp; Perbarui Data Peserta Didik
                    </h3>
                    <div style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); margin-top: 2px;">
                        Format Resmi Ekspor Dapodik Kemendikbudristek
                    </div>
                </div>
            </div>
            <button type="button" onclick="closeImportExcelModal()"
                style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: var(--text-xl, 1.25rem); padding: 4px; width: 38px; height: 38px; display: inline-flex; align-items: center; justify-content: center;"
                title="Tutup" aria-label="Tutup">
                <i class="fas fa-times"></i>
            </button>
        </div>

        {{-- 1. Berkas Peserta Didik Keluar (Dapodik 43 Kolom) --}}
        <form id="formImportExcelKeluar" action="{{ $importAction }}" method="POST" enctype="multipart/form-data"
            onsubmit="handlePdKeluarImportSubmit(event)" style="margin-bottom: 16px;">
            @csrf

            <div style="margin-bottom: 8px;">
                <label for="inputExcelFileKeluar"
                    style="display: block; font-weight: 700; color: var(--text-color); font-size: var(--text-base, 0.875rem); margin-bottom: 2px;">
                    Berkas Peserta Didik Keluar
                </label>
                <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); line-height: 1.4;">
                    Unggah ekspor dari Dapodik menu <strong>Peserta Didik Keluar</strong> (43 kolom). Otomatis mendeteksi status keluar (Alumni/Mutasi/Keluar) serta tahun kelulusan.
                </div>
            </div>

            <div style="display: flex; gap: 8px; align-items: center;">
                <input type="file" id="inputExcelFileKeluar" name="file_excel" accept=".xlsx,.xls,.csv,.txt"
                    style="flex: 1; min-width: 0; padding: 8px 12px; font-size: var(--text-sm, 0.8125rem); border-radius: var(--radius-md, 8px); border: 1px solid var(--border-color); background: rgba(255,255,255,0.03); color: var(--text-color);">
                <button type="submit" id="btnSubmitImportExcelKeluar" class="btn btn-primary"
                    style="white-space: nowrap; display: inline-flex; align-items: center; gap: 6px; padding: 8px 14px;">
                    <i class="fas fa-cloud-arrow-up"></i> Import
                </button>
            </div>

            <div id="importExcelProgressKeluar" class="import-excel-progress"
                style="display: none; margin-top: 10px; padding: 10px; border-radius: var(--radius-md, 8px); background: rgba(16,185,129,0.08); border: 1px solid rgba(16,185,129,0.25); text-align: center; font-size: var(--text-sm, 0.8125rem); color: var(--success, #10b981);">
                <i class="fas fa-spinner fa-spin me-2"></i> Sedang membaca dan memproses data peserta didik keluar...
            </div>
        </form>

        {{-- 2. Berkas Daftar Peserta Didik Lengkap (Dapodik 66 Kolom) --}}
        <div style="margin-top: 4px; padding-top: 16px; border-top: 1px dashed var(--border-color);">
            <form id="formUpdateKelengkapan" action="{{ $importAction }}" method="POST" enctype="multipart/form-data"
                onsubmit="handlePdKeluarImportSubmit(event)">
                @csrf
                <input type="hidden" name="mode" value="update">

                <div style="margin-bottom: 8px;">
                    <label for="inputExcelFileLengkap"
                        style="display: block; font-weight: 700; color: var(--text-color); font-size: var(--text-base, 0.875rem); margin-bottom: 2px;">
                        Berkas Daftar Peserta Didik Lengkap
                    </label>
                    <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); line-height: 1.4;">
                        Unggah ekspor dari Dapodik menu <strong>Peserta Didik</strong> (format lengkap / 66 kolom). Hanya memperbarui kelengkapan data alumni yang <strong>NISN</strong>-nya cocok — tidak menambah data baru.
                    </div>
                </div>

                <div style="display: flex; gap: 8px; align-items: center;">
                    <input type="file" id="inputExcelFileLengkap" name="file_excel" accept=".xlsx,.xls,.csv,.txt"
                        style="flex: 1; min-width: 0; padding: 8px 12px; font-size: var(--text-sm, 0.8125rem); border-radius: var(--radius-md, 8px); border: 1px solid var(--border-color); background: rgba(255,255,255,0.03); color: var(--text-color);">
                    <button type="submit" id="btnSubmitUpdateKelengkapan" class="btn btn-primary"
                        style="white-space: nowrap; display: inline-flex; align-items: center; gap: 6px; padding: 8px 14px;">
                        <i class="fas fa-rotate"></i> Perbarui
                    </button>
                </div>

                <div id="importExcelProgressLengkap" class="import-excel-progress"
                    style="display: none; margin-top: 10px; padding: 10px; border-radius: var(--radius-md, 8px); background: rgba(16,185,129,0.08); border: 1px solid rgba(16,185,129,0.25); text-align: center; font-size: var(--text-sm, 0.8125rem); color: var(--success, #10b981);">
                    <i class="fas fa-spinner fa-spin me-2"></i> Memperbarui kelengkapan data alumni...
                </div>
            </form>
        </div>
    </div>
</div>
