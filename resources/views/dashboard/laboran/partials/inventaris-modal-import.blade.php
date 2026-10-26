        {{-- Modal Import Inventaris via Excel (Point 7) --}}
        <div id="modalImportInventaris" class="modal-backdrop"
            style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
            <div class="card" style="width: 100%; max-width: 520px; padding: 24px; border-radius: var(--radius-lg, 12px);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px;">
                    <h3 style="margin: 0; font-size: var(--text-xl, 1.25rem); font-weight: 700; color: var(--text-color);">
                        <i class="fas fa-file-excel text-success me-2"></i> Import Inventaris via Excel
                    </h3>
                    <button type="button" id="btnCloseModalImport"
                        style="background: none; border: none; font-size: var(--text-xl, 1.25rem); color: var(--text-muted); cursor: pointer;">&times;</button>
                </div>

                <div style="margin-bottom: 18px; padding: 12px 14px; background: rgba(16,185,129,0.08); border: 1px solid rgba(16,185,129,0.25); border-radius: var(--radius-md, 8px);">
                    <div style="font-size: var(--text-sm, 0.8125rem); color: var(--text-color); font-weight: 600; margin-bottom: 4px;">
                        Unduh Format Template:
                    </div>
                    <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-bottom: 10px;">
                        Gunakan berkas template resmi agar nama kolom sesuai dengan sistem.
                    </div>
                    <a href="{{ route('dashboard.laboran.inventaris.template') }}" class="btn btn-outline"
                        style="font-size: var(--text-xs, 0.75rem); padding: 6px 12px; display: inline-flex; align-items: center; gap: 6px; text-decoration: none;">
                        <i class="fas fa-download text-success"></i> Unduh Format Excel (.xlsx)
                    </a>
                </div>

                <form id="formImportInventaris" method="POST" action="{{ route('dashboard.laboran.inventaris.import') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="form-group" style="margin-bottom: 18px;">
                        <label style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">
                            Pilih Berkas Excel / CSV <span style="color: var(--danger, #ef4444);">*</span>
                        </label>
                        <input type="file" name="file" id="fileImportInventaris" class="form-control" accept=".xlsx,.xls,.csv" required>
                        <small style="color: var(--text-muted); font-size: var(--text-xs, 0.75rem); display: block; margin-top: 4px;">
                            Mendukung .xlsx, .xls, atau .csv (Maks. 10 MB).
                        </small>
                    </div>

                    <div style="display: flex; justify-content: flex-end; gap: 8px;">
                        <button type="button" class="btn btn-secondary" id="btnCancelModalImport">Batal</button>
                        <button type="submit" class="btn btn-primary" id="btnSubmitImport">
                            <i class="fas fa-upload me-1"></i> Mulai Unggah &amp; Import
                        </button>
                    </div>
                </form>
            </div>
        </div>
