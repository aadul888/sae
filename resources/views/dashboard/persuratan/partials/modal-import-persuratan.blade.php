{{-- Modal Import Excel Surat (dipakai Surat Masuk & Surat Keluar) --}}
{{-- Variabel wajib: $importJenis ('masuk'|'keluar'), $importStoreUrl, $importTemplateUrl, $importKolom --}}
<div class="modal-overlay" id="modalImportPersuratan"
    style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); backdrop-filter: blur(4px); z-index: 99999 !important; align-items: center; justify-content: center;">
    <div class="modal-container"
        style="background: var(--card-bg); border: 1px solid var(--border-color); border-radius: var(--radius-xl, 16px); width: 100%; max-width: 560px; max-height: 90vh; display: flex; flex-direction: column; overflow: hidden; box-shadow: 0 20px 40px rgba(0,0,0,0.3);">
        <div
            style="padding: 16px 20px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; flex-shrink: 0;">
            <div>
                <h4 style="font-size: var(--text-md, 1rem); font-weight: 700; color: var(--text-color); margin: 0;">
                    <i class="fas fa-file-excel text-success me-2"></i> Import Surat {{ ucfirst($importJenis) }} via Excel
                </h4>
                <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-top: 2px;">
                    Unggah berkas .xlsx / .xls / .csv sesuai format template resmi.
                </div>
            </div>
            <button type="button" id="btnCloseModalImportPersuratan"
                style="background: none; border: none; font-size: var(--text-lg, 1.125rem); color: var(--text-muted); cursor: pointer;" aria-label="Tutup">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form id="formImportPersuratan" action="{{ $importStoreUrl }}" method="POST" enctype="multipart/form-data"
            style="overflow-y: auto; flex: 1;">
            @csrf
            <div style="padding: 20px; display: flex; flex-direction: column; gap: 16px;">
                <div
                    style="background: rgba(99,102,241,0.08); border: 1px solid rgba(99,102,241,0.25); border-radius: var(--radius-lg, 12px); padding: 12px 14px; font-size: var(--text-sm, 0.8125rem); line-height: 1.45; color: var(--text-color);">
                    <div
                        style="font-weight: 700; color: var(--primary); margin-bottom: 4px; display: flex; align-items: center; gap: 6px;">
                        <i class="fas fa-info-circle"></i> Kolom yang dikenali
                    </div>
                    <div>
                        @foreach ($importKolom as $kolom)
                            <code>{{ $kolom }}</code>@if (!$loop->last), @endif
                        @endforeach
                    </div>
                </div>

                <div
                    style="background: rgba(255,255,255,0.02); border: 1px dashed var(--border-color); border-radius: var(--radius-lg, 12px); padding: 12px 14px; display: flex; align-items: center; justify-content: space-between; gap: 12px;">
                    <div>
                        <div style="font-size: var(--text-base, 0.875rem); font-weight: 700; color: var(--text-color);">Belum
                            punya berkas?</div>
                        <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted);">Unduh template kosong
                            berisi contoh baris</div>
                    </div>
                    <a href="{{ $importTemplateUrl }}" class="btn btn-outline"
                        style="padding: 6px 12px; font-size: var(--text-sm, 0.8125rem); display: inline-flex; align-items: center; gap: 6px; border-color: var(--success, #10b981); color: var(--success, #10b981); text-decoration: none; white-space: nowrap;">
                        <i class="fas fa-download"></i> Unduh Template
                    </a>
                </div>

                <div>
                    <label
                        style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 600; color: var(--text-color); margin-bottom: 4px;">
                        Pilih Berkas Excel / CSV: <span class="text-danger">*</span>
                    </label>
                    <input type="file" name="file_excel" id="inputImportPersuratanFile" accept=".xlsx,.xls,.csv,.txt"
                        style="width: 100%; padding: 8px 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: var(--radius-md, 8px); font-size: var(--text-sm, 0.8125rem);"
                        required>
                    <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-top: 3px;">
                        Maksimal 20MB. Nama kolom header mengikuti template di atas.
                    </div>
                </div>

                <div id="importPersuratanProgress"
                    style="display: none; padding: 12px; border-radius: var(--radius-md, 8px); background: rgba(16,185,129,0.08); border: 1px solid rgba(16,185,129,0.25); text-align: center; font-size: var(--text-sm, 0.8125rem); color: var(--success, #10b981);">
                    <i class="fas fa-spinner fa-spin me-2"></i> Sedang memproses berkas...
                </div>
            </div>

            <div
                style="padding: 14px 20px; border-top: 1px solid var(--border-color); background: rgba(0,0,0,0.02); display: flex; justify-content: flex-end; gap: 10px; flex-shrink: 0;">
                <button type="button" id="btnCancelModalImportPersuratan" class="btn btn-outline"
                    style="padding: 8px 16px; border-radius: var(--radius-md, 8px); font-size: var(--text-base, 0.875rem);">Batal</button>
                <button type="submit" class="btn btn-primary" id="btnSubmitImportPersuratan"
                    style="padding: 8px 18px; border-radius: var(--radius-md, 8px); font-size: var(--text-base, 0.875rem);">
                    <i class="fas fa-cloud-arrow-up me-1"></i> <span id="btnSubmitImportPersuratanText">Import Sekarang</span>
                </button>
            </div>
        </form>
    </div>
</div>
