/**
 * Handler Import Excel Surat Masuk & Surat Keluar — SAE.
 * Satu skrip dipakai kedua halaman (modal partial `modal-import-persuratan`).
 */
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('modalImportPersuratan');
    const form = document.getElementById('formImportPersuratan');
    if (!modal || !form) return;

    const fileInput = document.getElementById('inputImportPersuratanFile');
    const progress = document.getElementById('importPersuratanProgress');
    const btnSubmit = document.getElementById('btnSubmitImportPersuratan');
    const btnText = document.getElementById('btnSubmitImportPersuratanText');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || window.CSRF_TOKEN || '';

    function openModal() {
        form.reset();
        if (progress) progress.style.display = 'none';
        if (btnSubmit) btnSubmit.disabled = false;
        if (btnText) btnText.textContent = 'Import Sekarang';
        modal.style.display = 'flex';
        document.body.classList.add('modal-open');
    }

    function closeModal() {
        modal.style.display = 'none';
        document.body.classList.remove('modal-open');
    }

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = String(str ?? '');
        return div.innerHTML;
    }

    document.addEventListener('click', function (e) {
        if (e.target.closest('#btnOpenImportMasuk, #btnOpenImportKeluar')) {
            e.preventDefault();
            openModal();
            return;
        }
        if (e.target.closest('#btnCloseModalImportPersuratan, #btnCancelModalImportPersuratan')) {
            closeModal();
            return;
        }
        if (e.target === modal) closeModal();
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && modal.style.display === 'flex') closeModal();
    });

    form.addEventListener('submit', async function (e) {
        e.preventDefault();

        if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
            window.Swal
                ? Swal.fire({ title: 'Berkas Belum Dipilih', text: 'Pilih berkas Excel atau CSV terlebih dahulu.', icon: 'warning', confirmButtonColor: '#6366f1' })
                : alert('Pilih berkas Excel atau CSV terlebih dahulu.');
            return;
        }

        if (progress) progress.style.display = 'block';
        if (btnSubmit) btnSubmit.disabled = true;
        if (btnText) btnText.textContent = 'Mengimpor...';

        try {
            const res = await fetch(form.action, {
                method: 'POST',
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrf },
                body: new FormData(form),
            });

            const json = await res.json();

            if (res.ok && json.status === 'success') {
                closeModal();
                if (window.Swal) {
                    await Swal.fire({ title: 'Import Berhasil!', text: json.message || '', icon: 'success', confirmButtonColor: '#10b981' });
                }
                if (typeof window.refreshLiveTable === 'function') {
                    window.refreshLiveTable(window.location.href);
                } else {
                    window.location.reload();
                }
            } else {
                let html = escapeHtml(json.message || 'Gagal mengimpor berkas.');
                if (json.errors && typeof json.errors === 'object') {
                    html += "<br><br><small style='color:#ef4444; display:block; text-align:left;'>" +
                        escapeHtml(Object.values(json.errors).flat().join('<br>')) + '</small>';
                }
                window.Swal
                    ? Swal.fire({ title: 'Gagal Mengimpor', html: html, icon: 'error', confirmButtonColor: '#ef4444' })
                    : alert(json.message || 'Gagal mengimpor berkas.');
            }
        } catch (err) {
            const msg = 'Gagal terhubung ke server: ' + (err.message || 'kesalahan tidak diketahui');
            window.Swal ? Swal.fire({ title: 'Kesalahan Jaringan', text: msg, icon: 'error' }) : alert(msg);
        } finally {
            if (progress) progress.style.display = 'none';
            if (btnSubmit) btnSubmit.disabled = false;
            if (btnText) btnText.textContent = 'Import Sekarang';
        }
    });
});
