{{-- Modal Import Excel generik modul Keamanan.
     Variabel: $modulKode, $modulJudul, $templateRoute, $importRoute --}}
<div id="modalImportKeamanan" class="modal-backdrop"
    style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
    <div class="card" style="width: 100%; max-width: 520px; padding: 24px; border-radius: var(--radius-lg, 12px);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px;">
            <h3 style="margin: 0; font-size: var(--text-xl, 1.25rem); font-weight: 700; color: var(--text-color);">
                <i class="fas fa-file-excel text-success me-2"></i> Import {{ $modulJudul }} via Excel
            </h3>
            <button type="button" id="btnCloseModalImportKeamanan"
                style="background: none; border: none; font-size: var(--text-xl, 1.25rem); color: var(--text-muted); cursor: pointer;"
                aria-label="Tutup Modal">&times;</button>
        </div>

        <div style="margin-bottom: 18px; padding: 12px 14px; background: rgba(16,185,129,0.08); border: 1px solid rgba(16,185,129,0.25); border-radius: var(--radius-md, 8px);">
            <div style="font-size: var(--text-sm, 0.8125rem); color: var(--text-color); font-weight: 600; margin-bottom: 4px;">
                Unduh Format Template Resmi:
            </div>
            <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-bottom: 10px;">
                Gunakan template resmi SAE agar nama kolom dan format tanggal/jam sesuai dengan sistem.
            </div>
            <a href="{{ $templateRoute }}" class="btn btn-outline"
                style="font-size: var(--text-xs, 0.75rem); padding: 6px 12px; display: inline-flex; align-items: center; gap: 6px; text-decoration: none;">
                <i class="fas fa-download text-success"></i> Unduh Format Excel (.xlsx)
            </a>
        </div>

        <form id="formImportKeamanan" method="POST" action="{{ $importRoute }}" enctype="multipart/form-data"
            data-modul="{{ $modulKode }}">
            @csrf
            <div class="form-group" style="margin-bottom: 18px;">
                <label style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; margin-bottom: 6px; color: var(--text-color);">
                    Pilih Berkas Excel / CSV <span style="color: var(--danger, #ef4444);">*</span>
                </label>
                <input type="file" name="file" id="fileImportKeamanan" class="form-control" accept=".xlsx,.xls,.csv" required>
                <small style="color: var(--text-muted); font-size: var(--text-xs, 0.75rem); display: block; margin-top: 4px;">
                    Mendukung .xlsx, .xls, atau .csv (Maks. 10 MB).
                </small>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 8px;">
                <button type="button" class="btn btn-secondary" id="btnCancelModalImportKeamanan">Batal</button>
                <button type="submit" class="btn btn-primary" id="btnSubmitImportKeamanan">
                    <i class="fas fa-upload me-1"></i> Mulai Unggah &amp; Import
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    (function () {
        const modal = document.getElementById('modalImportKeamanan');
        const form = document.getElementById('formImportKeamanan');
        if (!modal || !form) return;

        const btnOpen = document.getElementById('btnOpenImportKeamanan');
        const btnClose = document.getElementById('btnCloseModalImportKeamanan');
        const btnCancel = document.getElementById('btnCancelModalImportKeamanan');
        const submitBtn = document.getElementById('btnSubmitImportKeamanan');
        const fileInput = document.getElementById('fileImportKeamanan');

        function openModal() {
            form.reset();
            modal.style.display = 'flex';
        }
        function closeModal() {
            modal.style.display = 'none';
        }

        if (btnOpen) btnOpen.addEventListener('click', openModal);
        if (btnClose) btnClose.addEventListener('click', closeModal);
        if (btnCancel) btnCancel.addEventListener('click', closeModal);
        modal.addEventListener('click', function (e) {
            if (e.target === modal) closeModal();
        });

        form.addEventListener('submit', function (e) {
            e.preventDefault();

            if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
                if (typeof Swal !== 'undefined') Swal.fire({ title: 'Berkas Belum Dipilih', text: 'Pilih berkas Excel (.xlsx/.xls) atau CSV terlebih dahulu.', icon: 'error' });
                return;
            }

            const originalHtml = submitBtn ? submitBtn.innerHTML : '';
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Mengunggah &amp; Mengimpor...';
            }

            const csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';

            fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin',
                body: new FormData(form)
            })
            .then(function (res) {
                return res.text().then(function (text) {
                    let parsed = null;
                    try { parsed = JSON.parse(text); } catch (err) { parsed = null; }
                    if (parsed) return { ok: res.ok, status: res.status, data: parsed };
                    let msg = 'Server mengembalikan respons tidak valid (HTTP ' + res.status + ').';
                    if (res.status === 419) msg = 'Sesi halaman telah berakhir. Muat ulang halaman lalu ulangi unggahan.';
                    else if (res.status === 413) msg = 'Ukuran berkas terlalu besar untuk server. Perkecil berkas lalu ulangi.';
                    else if (res.status >= 500) msg = 'Terjadi kesalahan pada server saat memproses berkas.';
                    return { ok: false, status: res.status, data: { status: 'error', message: msg } };
                });
            })
            .then(function (result) {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalHtml;
                }
                const data = result.data || {};

                if (result.ok && data.status === 'success') {
                    closeModal();
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({ title: 'Import Berhasil', text: data.message || 'Data berhasil diimpor.', icon: 'success' })
                            .then(function () { window.location.reload(); });
                    } else {
                        window.location.reload();
                    }
                    return;
                }

                if (typeof Swal !== 'undefined') {
                    Swal.fire({ title: 'Import Gagal', text: data.message || 'Berkas tidak dapat diproses.', icon: 'error' });
                } else {
                    alert(data.message || 'Import gagal.');
                }
            })
            .catch(function () {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalHtml;
                }
                if (typeof Swal !== 'undefined') Swal.fire({ title: 'Kesalahan Sistem', text: 'Gagal mengirim berkas. Periksa koneksi lalu ulangi.', icon: 'error' });
            });
        });
    })();
</script>

