        {{-- Modal Import Peminjaman via Excel --}}
        <div id="modalImportPeminjaman" class="modal-backdrop"
            style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
            <div class="card" style="width: 100%; max-width: 540px; padding: var(--space-10); border-radius: var(--radius-lg);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--space-8);">
                    <h3 style="margin: 0; font-size: var(--text-xl); font-weight: var(--font-bold); color: var(--text-color);">
                        <i class="fas fa-file-excel text-success me-2"></i> Import Peminjaman via Excel
                    </h3>
                    <button type="button" id="btnCloseModalImportPeminjaman"
                        style="background: none; border: none; font-size: var(--text-xl); color: var(--text-muted); cursor: pointer;"
                        title="Tutup">&times;</button>
                </div>

                <div style="margin-bottom: var(--space-7); padding: var(--space-5) var(--space-6); background: rgba(16,185,129,0.08); border: 1px solid rgba(16,185,129,0.25); border-radius: var(--radius-md);">
                    <div style="font-size: var(--text-sm); color: var(--text-color); font-weight: var(--font-semibold); margin-bottom: var(--space-2);">
                        Unduh Format Template Resmi:
                    </div>
                    <div style="font-size: var(--text-xs); color: var(--text-muted); margin-bottom: var(--space-4);">
                        Satu baris = satu alat. Baris dengan <strong>nomor_pinjam</strong> yang sama digabung menjadi satu dokumen peminjaman.
                    </div>
                    <a href="{{ route('dashboard.laboran.peminjaman-alat.template') }}" class="btn btn-outline"
                        style="font-size: var(--text-xs); padding: 6px 12px; display: inline-flex; align-items: center; gap: 6px; text-decoration: none;">
                        <i class="fas fa-download text-success"></i> Unduh Format Excel (.xlsx)
                    </a>
                </div>

                <form id="formImportPeminjaman" method="POST" action="{{ route('dashboard.laboran.peminjaman-alat.import') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="form-group" style="margin-bottom: var(--space-8);">
                        <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">
                            Pilih Berkas Excel / CSV <span style="color: var(--danger, #ef4444);">*</span>
                        </label>
                        <input type="file" name="file" id="fileImportPeminjaman" class="form-control" accept=".xlsx,.xls,.csv" required>
                        <small style="color: var(--text-muted); font-size: var(--text-xs); display: block; margin-top: var(--space-2);">
                            Mendukung .xlsx, .xls, atau .csv (Maks. 10 MB).
                        </small>
                    </div>

                    <div style="display: flex; justify-content: flex-end; gap: var(--space-4);">
                        <button type="button" class="btn btn-secondary" id="btnCancelImportPeminjaman">Batal</button>
                        <button type="submit" class="btn btn-primary" id="btnSubmitImportPeminjaman">
                            <i class="fas fa-upload me-1"></i> Mulai Unggah &amp; Import
                        </button>
                    </div>
                </form>
            </div>
        </div>
