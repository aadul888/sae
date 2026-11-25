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

    // 4. Ambil foto dokumen → otomatis jadi satu PDF multi-halaman
    initCameraToPdf();
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

                    // Jangan ganti bila hasil baca tidak utuh — mencegah berkas terpotong/kosong
                    // terkirim ke server hanya karena pembacaan sebagian gagal.
                    if (buffer.byteLength !== file.size) {
                        console.warn('[SAE-Berkas] Ukuran baca tak sama (' + buffer.byteLength + ' vs ' + file.size + '); input dibiarkan apa adanya.');
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

/**
 * Ambil Foto Dokumen → Otomatis Jadi PDF (multi-halaman).
 *
 * Alurnya:
 *   klik "Ambil Foto → PDF" → input `capture` membuka kamera OS → foto masuk
 *   (boleh menekan tombol berulang kali; tiap foto jadi 1 halaman) → semua foto
 *   digabung jadi satu berkas PDF di sisi klien (jsPDF) → berkas PDF itu dipasang
 *   ke input `.file-input-pdf` milik form. Server tetap menerima PDF biasa, jadi
 *   seluruh validasi/guard server (%PDF, polyglot) tak berubah.
 *
 * Foto diperkecil (sisi terpanjang MAX_DIM) dan dikompres JPEG agar PDF tetap
 * jauh di bawah batas 5 MB.
 */
function initCameraToPdf() {
    const MAX_DIM = 1600;   // px — sisi terpanjang gambar setelah diperkecil
    const JPEG_Q = 0.82;    // kualitas JPEG untuk foto dokumen
    const MAX_BYTES = 5 * 1024 * 1024;
    const JSPDF_LOCAL = (window.__SAE_JSPDF__ && window.__SAE_JSPDF__.local)
        || '/vendor/jspdf/jspdf.umd.min.js';
    const JSPDF_CDN = (window.__SAE_JSPDF__ && window.__SAE_JSPDF__.cdn)
        || 'https://cdn.jsdelivr.net/npm/jspdf@2.5.2/dist/jspdf.umd.min.js';

    // jsPDF (365 KB) dimuat malas — hanya saat tombol kamera benar-benar dipakai.
    // Halaman admin/tendik yang cuma mengunduh PDF tak perlu membayarnya.
    let jsPdfLoading = null;
    function loadJsPdf() {
        if (window.jspdf && window.jspdf.jsPDF) return Promise.resolve(window.jspdf.jsPDF);
        if (jsPdfLoading) return jsPdfLoading;
        jsPdfLoading = new Promise((resolve, reject) => {
            const s = document.createElement('script');
            s.src = JSPDF_LOCAL;
            s.onload = () => {
                if (window.jspdf && window.jspdf.jsPDF) resolve(window.jspdf.jsPDF);
                else reject(new Error('jsPDF tidak mengekspos global.'));
            };
            // Fallback CDN bila aset lokal hilang/diblokir.
            s.onerror = () => {
                const alt = document.createElement('script');
                alt.src = JSPDF_CDN;
                alt.onload = () => (window.jspdf && window.jspdf.jsPDF)
                    ? resolve(window.jspdf.jsPDF)
                    : reject(new Error('jsPDF gagal dimuat.'));
                alt.onerror = () => reject(new Error('jsPDF gagal dimuat (lokal & CDN).'));
                document.head.appendChild(alt);
            };
            document.head.appendChild(s);
        });
        return jsPdfLoading;
    }

    const forms = document.querySelectorAll('.form-upload-berkas');
    if (!forms.length || !document.querySelector('.btn-camera-pdf')) return;

    // Tombol tak bisa dipakai bila pustaka tak termuat → tandai saat gagal.
    const disableAll = (why) => {
        document.querySelectorAll('.btn-camera-pdf').forEach((b) => {
            b.disabled = true;
            b.title = why;
        });
    };

    /** Gambar File → dataURL JPEG yang sudah diperkecil. */
    function toDownscaledJpeg(file) {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.onerror = () => reject(new Error('Gagal membaca foto.'));
            reader.onload = () => {
                const img = new Image();
                img.onerror = () => reject(new Error('Foto tidak dapat diproses.'));
                img.onload = () => {
                    let w = img.naturalWidth || img.width;
                    let h = img.naturalHeight || img.height;
                    const scale = Math.min(1, MAX_DIM / Math.max(w, h));
                    w = Math.max(1, Math.round(w * scale));
                    h = Math.max(1, Math.round(h * scale));

                    const canvas = document.createElement('canvas');
                    canvas.width = w;
                    canvas.height = h;
                    const ctx = canvas.getContext('2d');
                    // Latar putih: JPEG tak mendukung transparansi (foto dokumen
                    // umumnya sudah putih, ini hanya pengaman).
                    ctx.fillStyle = '#ffffff';
                    ctx.fillRect(0, 0, w, h);
                    ctx.drawImage(img, 0, 0, w, h);
                    resolve(canvas.toDataURL('image/jpeg', JPEG_Q));
                };
                img.src = reader.result;
            };
            reader.readAsDataURL(file);
        });
    }

    forms.forEach((form) => {
        const trigger = form.querySelector('.btn-camera-pdf');
        const resetBtn = form.querySelector('.btn-camera-clear');
        const statusEl = form.querySelector('.camera-pdf-status');
        const cameraInput = form.querySelector('.file-input-camera');
        const pdfInput = form.querySelector('.file-input-pdf');
        if (!trigger || !cameraInput || !pdfInput) return;

        const pages = form._saeCameraPages || (form._saeCameraPages = []);
        let busy = false;

        const setStatus = (text) => {
            if (statusEl) statusEl.textContent = text || '';
            if (resetBtn) resetBtn.style.display = pages.length ? 'inline-flex' : 'none';
        };

        /** Bangun PDF dari semua foto dan pasang ke input PDF form. */
        const rebuildPdf = async () => {
            if (!pages.length) return;
            busy = true;
            setStatus('Menyiapkan…');

            try {
                const jsPDF = await loadJsPdf();
                setStatus('Menyusun PDF…');

                // A4 potret, satuan mm.
                const doc = new jsPDF({ unit: 'mm', format: 'a4', orientation: 'portrait' });
                const pageW = 210, pageH = 297, margin = 8;
                const boxW = pageW - margin * 2;
                const boxH = pageH - margin * 2;

                for (let i = 0; i < pages.length; i++) {
                    const dataUrl = await toDownscaledJpeg(pages[i]);
                    // Rasio dari dataURL: pakai Image sekali lagi (sudah ter-cache).
                    const dim = await new Promise((res, rej) => {
                        const im = new Image();
                        im.onload = () => res({ w: im.naturalWidth, h: im.naturalHeight });
                        im.onerror = () => rej(new Error('Dimensi foto gagal dibaca.'));
                        im.src = dataUrl;
                    });

                    const scale = Math.min(boxW / dim.w, boxH / dim.h);
                    const w = dim.w * scale;
                    const h = dim.h * scale;
                    const x = margin + (boxW - w) / 2;
                    const y = margin + (boxH - h) / 2;
                    if (i > 0) doc.addPage();
                    doc.addImage(dataUrl, 'JPEG', x, y, w, h, undefined, 'FAST');
                }

                const blob = doc.output('blob');
                const jenis = form.querySelector('input[name="jenis_berkas"]');
                const baseName = (jenis && jenis.value ? jenis.value : 'dokumen');
                const pdfFile = new File([blob], `${baseName}.pdf`, { type: 'application/pdf' });

                if (pdfFile.size > MAX_BYTES) {
                    busy = false;
                    setStatus('');
                    Swal.fire({
                        icon: 'warning',
                        title: 'Hasil PDF Terlalu Besar',
                        html: `PDF hasil foto berukuran <strong>${(pdfFile.size / 1024 / 1024).toFixed(2)} MB</strong> (batas 5 MB).<br>Kurangi jumlah foto atau ambil ulang dengan pencahayaan lebih baik.`,
                        confirmButtonColor: '#f59e0b',
                    });
                    return;
                }

                if (window.DataTransfer) {
                    const dt = new DataTransfer();
                    dt.items.add(pdfFile);
                    pdfInput.files = dt.files;
                }
                // Input diisi lewat JS (bukan change event) → tak ada normalisasi
                // in-memory yang menggantung di handler submit.
                pdfInput._saeNormalizeDone = true;
                delete pdfInput._saeNormalize;

                const sizeMb = (pdfFile.size / 1024 / 1024).toFixed(2);
                setStatus(`${pages.length} foto → PDF (${sizeMb} MB). Siap diunggah.`);
            } catch (err) {
                console.warn('[SAE-Kamera] Gagal menyusun PDF:', err);
                const libMissing = !(window.jspdf && window.jspdf.jsPDF);
                if (libMissing) disableAll('Fitur kamera nonaktif: pustaka PDF gagal dimuat (periksa koneksi lalu muat ulang).');
                Swal.fire({
                    icon: 'error',
                    title: libMissing ? 'Pustaka PDF Gagal Dimuat' : 'Gagal Membuat PDF',
                    text: libMissing
                        ? 'Periksa koneksi internet lalu muat ulang halaman. Unggah berkas PDF manual tetap bisa dipakai.'
                        : 'Foto tidak dapat diproses. Coba ambil ulang dengan kamera.',
                    confirmButtonColor: '#ef4444',
                });
                setStatus('');
            } finally {
                busy = false;
            }
        };

        trigger.addEventListener('click', () => {
            if (busy) return;
            cameraInput.click();
        });

        cameraInput.addEventListener('change', function () {
            const files = Array.from(this.files || []);
            this.value = ''; // biar pilih foto berikutnya tetap memicu change
            if (!files.length) return;

            files.forEach((f) => {
                if (f.type && f.type.indexOf('image/') === 0) pages.push(f);
            });
            if (!pages.length) {
                Swal.fire({
                    icon: 'error',
                    title: 'Bukan Gambar',
                    text: 'Berkas yang dipilih bukan gambar. Silakan ambil foto ulang.',
                    confirmButtonColor: '#ef4444',
                });
                return;
            }
            rebuildPdf();
        });

        if (resetBtn) {
            resetBtn.addEventListener('click', () => {
                pages.length = 0;
                if (window.DataTransfer) {
                    pdfInput.files = new DataTransfer().files;
                }
                pdfInput._saeNormalizeDone = true;
                delete pdfInput._saeNormalize;
                setStatus('');
            });
        }
    });
}
