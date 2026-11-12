{{-- Modal Import Excel Peserta Didik Keluar / Alumni (Format Resmi Dapodik 43 kolom).
     Param: $importAction (route POST), $templateUrl (route GET template). --}}
<div id="modalImportExcel" class="modal-backdrop"
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
                        Import Peserta Didik Keluar / Alumni
                    </h3>
                    <div style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); margin-top: 2px;">
                        Format Resmi Ekspor Dapodik (43 Kolom)
                    </div>
                </div>
            </div>
            <div style="display: flex; align-items: center; gap: 8px;">
                <button type="submit" form="formImportExcel" id="btnSubmitImportExcel" class="btn btn-primary"
                    title="Import Sekarang" aria-label="Import Sekarang"
                    style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px);">
                    <i class="fas fa-cloud-arrow-up" id="btnSubmitImportExcelIcon"></i>
                </button>
                <button type="button" onclick="closeImportExcelModal()"
                    style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: var(--text-xl, 1.25rem); padding: 4px; width: 38px; height: 38px; display: inline-flex; align-items: center; justify-content: center;"
                    title="Tutup" aria-label="Tutup">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>

        <form id="formImportExcel" action="{{ $importAction }}" method="POST" enctype="multipart/form-data"
            onsubmit="handleImportExcelSubmit(event)">
            @csrf

            {{-- Alert Box Info Petunjuk --}}
            <div
                style="margin-bottom: 16px; padding: 12px 14px; border-radius: var(--radius-lg, 12px); background: rgba(99,102,241,0.08); border: 1px solid rgba(99,102,241,0.25); font-size: var(--text-sm, 0.8125rem); line-height: 1.45; color: var(--text-color);">
                <div
                    style="font-weight: 700; color: var(--primary); margin-bottom: 4px; display: flex; align-items: center; gap: 6px;">
                    <i class="fas fa-info-circle"></i> Kompatibel Format Dapodik
                </div>
                <div>
                    Unggah langsung berkas Excel/CSV hasil unduhan dari Dapodik menu <strong>Peserta Didik
                        Keluar</strong> (baris kop/judul otomatis dilewati) tanpa perlu mengubah urutan kolom. Sistem
                    otomatis mendeteksi status keluar (Alumni/Mutasi/Keluar) serta tahun kelulusan.
                </div>
            </div>

            {{-- Unduh Template Card --}}
            <div
                style="margin-bottom: 18px; padding: 12px 14px; border-radius: var(--radius-lg, 12px); background: rgba(255,255,255,0.02); border: 1px dashed var(--border-color); display: flex; align-items: center; justify-content: space-between; gap: 12px;">
                <div>
                    <div style="font-size: var(--text-base, 0.875rem); font-weight: 700; color: var(--text-color);">Format Excel
                        Baku Dapodik</div>
                    <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted);">Unduh contoh template untuk
                        melihat susunan kolom yang diterima</div>
                </div>
                <a href="{{ $templateUrl }}" class="btn btn-outline"
                    style="padding: 6px 12px; font-size: var(--text-sm, 0.8125rem); display: inline-flex; align-items: center; gap: 6px; border-color: var(--success, #10b981); color: var(--success, #10b981); text-decoration: none; white-space: nowrap;">
                    <i class="fas fa-download"></i> Unduh Template
                </a>
            </div>

            {{-- Berkas Input --}}
            <div style="margin-bottom: 16px;">
                <label for="inputExcelFile"
                    style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-color); margin-bottom: 6px;">
                    Pilih Berkas Excel / CSV <span style="color: var(--danger, #ef4444);">*</span>
                </label>
                <input type="file" id="inputExcelFile" name="file_excel" accept=".xlsx,.xls,.csv,.txt"
                    style="width: 100%; padding: 8px 12px; font-size: var(--text-sm, 0.8125rem); border-radius: var(--radius-md, 8px); border: 1px solid var(--border-color); background: rgba(255,255,255,0.03); color: var(--text-color);"
                    required>
                <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-top: 4px;">
                    Mendukung format .xlsx, .xls, atau .csv dari Dapodik (Maksimal 30 MB).
                </div>
            </div>

            {{-- Loading / Status State --}}
            <div id="importExcelProgress" class="import-excel-progress"
                style="display: none; margin-bottom: 14px; padding: 12px; border-radius: var(--radius-md, 8px); background: rgba(16,185,129,0.08); border: 1px solid rgba(16,185,129,0.25); text-align: center; font-size: var(--text-sm, 0.8125rem); color: var(--success, #10b981);">
                <i class="fas fa-spinner fa-spin me-2"></i> Sedang membaca dan memproses data peserta didik...
            </div>

        </form>

        {{-- Perbarui kelengkapan data alumni: cocok NISN, tanpa menambah data --}}
        <div style="margin-top: 18px; padding-top: 16px; border-top: 1px dashed var(--border-color);">
            <div style="font-weight: 700; color: var(--text-color); font-size: var(--text-base, 0.875rem); margin-bottom: 2px;">
                Perbarui Kelengkapan Data Alumni
            </div>
            <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-bottom: 10px;">
                Unggah ekspor Dapodik <strong>Daftar Peserta Didik</strong> (data mulai baris 7). Hanya baris yang
                <strong>NISN</strong>-nya sudah terdaftar yang diperbarui — tidak menambah data baru.
            </div>
            <form id="formUpdateKelengkapan" action="{{ $importAction }}" method="POST" enctype="multipart/form-data"
                onsubmit="handleImportExcelSubmit(event)">
                @csrf
                <input type="hidden" name="mode" value="update">
                <div style="display: flex; gap: 8px; align-items: center;">
                    <input type="file" name="file_excel" accept=".xlsx,.xls,.csv,.txt" required
                        style="flex: 1; min-width: 0; padding: 8px 12px; font-size: var(--text-sm, 0.8125rem); border-radius: var(--radius-md, 8px); border: 1px solid var(--border-color); background: rgba(255,255,255,0.03); color: var(--text-color);">
                    <button type="submit" class="btn btn-primary"
                        style="white-space: nowrap; display: inline-flex; align-items: center; gap: 6px; padding: 8px 14px;">
                        <i class="fas fa-rotate"></i> Perbarui
                    </button>
                </div>
                <div class="import-excel-progress"
                    style="display: none; margin-top: 10px; padding: 10px; border-radius: var(--radius-md, 8px); background: rgba(16,185,129,0.08); border: 1px solid rgba(16,185,129,0.25); text-align: center; font-size: var(--text-sm, 0.8125rem); color: var(--success, #10b981);">
                    <i class="fas fa-spinner fa-spin me-2"></i> Memperbarui kelengkapan data...
                </div>
            </form>
        </div>
    </div>
</div>
