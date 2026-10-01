/**
 * Modul Validasi Berkas Peserta Didik — SAE Core JavaScript Module
 * Ketentuan: Hanya 1 jenis file (PDF), maksimal 5MB.
 */

document.addEventListener('DOMContentLoaded', function () {
    // 1. Validasi Input File Client-side (Strict PDF only)
    initPdfFileInputValidation();

    // 2. Loading state saat upload formulir
    initUploadFormSubmit();

    // 3. Pratinjau Dokumen PDF In-App (Tanpa Buka Tab Baru)
    initPdfPreviewModal();
});

/**
 * Validasi client-side: Tolak file jika bukan PDF atau > 5MB
 */
function initPdfFileInputValidation() {
    const fileInputs = document.querySelectorAll('.file-input-pdf');

    fileInputs.forEach(input => {
        input.addEventListener('change', function () {
            const file = this.files[0];
            if (!file) return;

            const fileName = file.name.toLowerCase();
            const isPdf = fileName.endsWith('.pdf') || file.type === 'application/pdf';

            if (!isPdf) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Format File Ditolak!',
                        html: 'Sistem hanya menerima file berformat <strong>PDF (.pdf)</strong>.<br>Silakan konversi dokumen Anda menjadi file PDF terlebih dahulu.',
                        confirmButtonColor: '#ef4444'
                    });
                } else {
                    alert('Format file ditolak! Sistem hanya menerima file PDF (.pdf).');
                }
                this.value = '';
                return;
            }

            // Batas ukuran 5MB (5 * 1024 * 1024 bytes)
            const maxBytes = 5 * 1024 * 1024;
            if (file.size > maxBytes) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Ukuran File Terlalu Besar!',
                        html: `Ukuran file Anda: <strong>${(file.size / 1024 / 1024).toFixed(2)} MB</strong>.<br>Batas maksimal ukuran dokumen PDF adalah <strong>5 MB</strong>.`,
                        confirmButtonColor: '#f59e0b'
                    });
                } else {
                    alert('Ukuran file terlalu besar! Maksimal ukuran file adalah 5 MB.');
                }
                this.value = '';
                return;
            }
        });
    });
}

/**
 * Loading indicator saat mengunggah berkas
 */
function initUploadFormSubmit() {
    const forms = document.querySelectorAll('.form-upload-berkas');

    forms.forEach(form => {
        form.addEventListener('submit', function (e) {
            const fileInput = this.querySelector('.file-input-pdf');
            if (!fileInput || !fileInput.files.length) {
                e.preventDefault();
                return;
            }

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Mengunggah Berkas PDF...',
                    text: 'Mohon tunggu, dokumen sedang diverifikasi dan disimpan ke server.',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
            }
        });
    });
}

/**
 * Pratinjau Dokumen PDF Terpadu (In-App Modal tanpa Buka Tab Baru)
 */
function initPdfPreviewModal() {
    const modal = document.getElementById('modalPdfViewer');
    const frame = document.getElementById('pdfViewerFrame');
    const titleEl = document.getElementById('pdfViewerTitle');

    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-preview-pdf-inline');
        if (btn) {
            e.preventDefault();
            e.stopPropagation();
            const url = btn.getAttribute('data-url');
            const title = btn.getAttribute('data-title');
            if (url && modal && frame) {
                if (titleEl) titleEl.textContent = title || 'Pratinjau Dokumen PDF';
                frame.src = url;
                modal.style.display = 'flex';
                document.body.style.overflow = 'hidden';
            }
        }

        const closeBtn = e.target.closest('.close-pdf-viewer');
        if (closeBtn) {
            e.preventDefault();
            e.stopPropagation();
            if (modal) modal.style.display = 'none';
            if (frame) frame.src = '';
            document.body.style.overflow = '';
        }
    });
}
