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
        input.addEventListener('change', async function () {
            const file = this.files[0];
            if (!file) return;

            const fileName = file.name.toLowerCase();
            const mime = (file.type || '').toLowerCase();

            // Patokan utama: ekstensi .pdf. Sebagian browser — terutama saat aplikasi
            // berjalan sebagai PWA terpasang, atau berkas diambil dari aplikasi
            // "Files"/kamera — melaporkan file.type kosong atau application/octet-stream
            // walau berkasnya PDF sah. MIME PDF yang eksplisit juga diterima.
            // Isi berkas tetap diverifikasi ulang di server.
            const isPdf = fileName.endsWith('.pdf')
                || mime === 'application/pdf'
                || mime === 'application/x-pdf';

            if (!isPdf) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Format File Ditolak!',
                        html: 'Sistem hanya menerima file berformat <strong>PDF (.pdf)</strong>.<br>Silakan konversi dokumen Anda menjadi file PDF terlebih dahulu.',
                        confirmButtonColor: '#ef4444'
                    });
                } else {
                    window.SAE.alert('Format file ditolak! Sistem hanya menerima file PDF (.pdf).', 'Informasi', 'info');
                }
                this.value = '';
                return;
            }

            // Batas bawah: berkas 0 byte (placeholder cloud Drive/WhatsApp yang belum
            // benar-benar diunduh ke perangkat) tidak mungkin PDF sah.
            if (!file.size) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Berkas Kosong (0 byte)',
                        html: 'Berkas yang dipilih <strong>kosong</strong>. Ini biasanya terjadi bila berkas diambil langsung dari <strong>Google Drive / WhatsApp / cloud</strong> dan belum benar-benar tersimpan di perangkat.<br><br>Simpan (unduh) berkas ke perangkat dulu, lalu pilih ulang dari folder <strong>Unduhan/Files</strong>.',
                        confirmButtonColor: '#ef4444'
                    });
                } else {
                    window.SAE.alert('Berkas kosong (0 byte). Unduh dulu ke perangkat lalu pilih ulang.', 'Informasi', 'info');
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
                    window.SAE.alert('Ukuran file terlalu besar! Maksimal ukuran file adalah 5 MB.', 'Informasi', 'info');
                }
                this.value = '';
                return;
            }

            // Anti `net::ERR_UPLOAD_FILE_CHANGED`.
            //
            // Di Android (Chrome/PWA) berkas dari "Files"/Drive/Gallery disajikan sebagai
            // URI sementara yang `last_modified`-nya berubah antar query. Saat form dikirim,
            // Chromium membandingkan waktu berkas saat ini dengan saat dipilih → dianggap
            // "berubah" → unggahan dibatalkan sebelum request terkirim (tak ada jejak di
            // log server). Solusi: baca isi berkas ke memori sekarang, lalu ganti isi input
            // dengan Blob in-memory. Blob tanpa path disk → tak ada waktu berkas untuk
            // dibandingkan saat submit.
            const inputEl = this;
            // Simpan promise normalisasi: handler submit menunggu ini dulu agar berkas
            // in-memory terpasang sebelum form dikirim (user bisa menekan Unggah cepat).
            inputEl._saeNormalizeDone = false;
            const normalize = (async () => {
                try {
                    const buffer = await file.arrayBuffer();

                    // Pembacaan bisa mengembalikan 0 byte walau file.size > 0, bila sumber
                    // berkas cloud baru dilepas handle-nya. Jangan ganti input dengan berkas
                    // kosong — biarkan berkas asli (server akan menolak dengan pesan jelas).
                    if (!buffer || buffer.byteLength === 0) {
                        console.warn('[SAE-Berkas] arrayBuffer kosong; input dibiarkan apa adanya.');
                        return;
                    }

                    const inMemory = new File([buffer], file.name, { type: file.type || 'application/pdf' });
                    if (window.DataTransfer) {
                        const dt = new DataTransfer();
                        dt.items.add(inMemory);
                        inputEl.files = dt.files;
                    }
                } catch (err) {
                    // Gagal membaca (mis. berkas sudah hilang) → biarkan; server akan tolak.
                    console.warn('[SAE-Berkas] Gagal menormalisasi berkas ke memori:', err);
                } finally {
                    inputEl._saeNormalizeDone = true;
                }
            })();
            inputEl._saeNormalize = normalize;
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

            // Bila normalisasi in-memory belum selesai (user menekan Unggah segera
            // setelah memilih berkas), tunggu selesai dulu baru kirim — agar tidak
            // ada jendela waktu di mana berkas on-disk dikirim dan ERR_UPLOAD_FILE_CHANGED
            // muncul kembali.
            if (fileInput._saeNormalize && !fileInput._saeNormalizeDone) {
                e.preventDefault();
                const form = this;
                fileInput._saeNormalize.then(() => {
                    if (typeof form.requestSubmit === 'function') {
                        form.requestSubmit();
                    } else {
                        form.submit();
                    }
                });
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
