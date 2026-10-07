/**
 * SAE — Kartu Pelajar Cetak & Export JPG Script
 */

window.changeSideFilter = function (val) {
    document.body.classList.remove('filter-front-only', 'filter-back-only');
    if (val === 'front-only') {
        document.body.classList.add('filter-front-only');
    } else if (val === 'back-only') {
        document.body.classList.add('filter-back-only');
    }
};

window.toggleUnduhJpgDropdown = function (e) {
    if (e) e.stopPropagation();
    const m = document.getElementById('menuUnduhJpg');
    if (!m) return;
    m.style.display = (m.style.display === 'none' || m.style.display === '') ? 'block' : 'none';
};

document.addEventListener('click', function (e) {
    const wrap = document.getElementById('dropdownUnduhJpgWrap');
    if (wrap && !wrap.contains(e.target)) {
        const m = document.getElementById('menuUnduhJpg');
        if (m) m.style.display = 'none';
    }
});

window.unduhHalamanSingleJpg = async function (mode) {
    const m = document.getElementById('menuUnduhJpg');
    if (m) m.style.display = 'none';

    const btn = document.getElementById('btnUnduhJpg');
    const orig = btn ? btn.innerHTML : '';
    if (btn) {
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyiapkan...';
        btn.disabled = true;
    }

    const captureCardToJpg = async (cardEl, sideName) => {
        if (!cardEl) return false;
        let staging = null;
        try {
            staging = document.createElement('div');
            staging.style.position = 'fixed';
            staging.style.left = '-9999px';
            staging.style.top = '0';
            staging.style.width = '204px'; // 54mm @ 96dpi
            staging.style.height = '324px'; // 85.6mm @ 96dpi
            staging.style.overflow = 'hidden';
            staging.style.zIndex = '-9999';
            staging.style.background = '#ffffff';

            const clone = cardEl.cloneNode(true);
            clone.classList.add('kp-card-capture-target');
            clone.style.display = 'flex';
            clone.style.transform = 'none';
            clone.style.margin = '0';
            clone.style.boxShadow = 'none';
            staging.appendChild(clone);
            document.body.appendChild(staging);

            await new Promise(r => setTimeout(r, 120));

            const canvas = await html2canvas(clone, {
                scale: 3,
                useCORS: true,
                allowTaint: true,
                backgroundColor: '#ffffff',
                logging: false,
                width: clone.offsetWidth,
                height: clone.offsetHeight
            });

            const link = document.createElement('a');
            const nisn = cardEl.id?.replace(/card-(front|back)-/, '') || 'kartu';
            link.download = `Kartu_Pelajar_${nisn}_${sideName}.jpg`;
            link.href = canvas.toDataURL('image/jpeg', 0.95);
            link.click();
            return true;
        } catch (err) {
            console.error('Error saat capture JPG:', err);
            return false;
        } finally {
            if (staging && staging.parentNode) {
                staging.parentNode.removeChild(staging);
            }
        }
    };

    try {
        const front = document.querySelector('.kp-card-front');
        const back = document.querySelector('.kp-card-back');

        if (mode === 'front') {
            await captureCardToJpg(front, 'Depan');
        } else if (mode === 'back') {
            await captureCardToJpg(back, 'Belakang');
        } else {
            await captureCardToJpg(front, 'Depan');
            await new Promise(r => setTimeout(r, 400));
            await captureCardToJpg(back, 'Belakang');
        }

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'success',
                title: 'Berhasil Diunduh!',
                text: 'Berkas gambar kartu pelajar telah disimpan ke komputer Anda.',
                timer: 2000,
                showConfirmButton: false
            });
        }
    } catch (e) {
        console.error(e);
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'Gagal Mengunduh',
                text: 'Terjadi kendala saat merender berkas gambar.'
            });
        }
    } finally {
        if (btn) {
            btn.innerHTML = orig;
            btn.disabled = false;
        }
    }
};

window.unduhSemuaKartuRombelZip = async function () {
    const sideFilter = document.getElementById('sideFilter')?.value || 'both';
    return window.unduhMasalSemuaJpg(sideFilter);
};

window.unduhMasalSemuaJpg = async function (sideFilter) {
    const m = document.getElementById('menuUnduhJpg');
    if (m) m.style.display = 'none';

    if (typeof JSZip === 'undefined') {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'Pustaka Belum Siap',
                text: 'Pustaka kompresi ZIP sedang dimuat, silakan tunggu sebentar.'
            });
        }
        return;
    }

    const pairs = Array.from(document.querySelectorAll('.kp-card-pair'));
    if (!pairs.length) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({ icon: 'warning', title: 'Data Kosong', text: 'Tidak ada kartu pelajar yang ditemukan untuk diunduh.' });
        }
        return;
    }

    const btn = document.getElementById('btnUnduhJpg');
    const orig = btn ? btn.innerHTML : '';
    if (btn) {
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyiapkan...';
        btn.disabled = true;
    }

    const zip = new JSZip();

    const captureCardToBlob = async (cardEl) => {
        if (!cardEl) return null;
        let staging = null;
        try {
            staging = document.createElement('div');
            staging.style.position = 'fixed';
            staging.style.left = '-9999px';
            staging.style.top = '0';
            staging.style.width = '204px';
            staging.style.height = '324px';
            staging.style.overflow = 'hidden';
            staging.style.zIndex = '-9999';
            staging.style.background = '#ffffff';

            const clone = cardEl.cloneNode(true);
            clone.classList.add('kp-card-capture-target');
            clone.style.display = 'flex';
            clone.style.transform = 'none';
            clone.style.margin = '0';
            clone.style.boxShadow = 'none';
            staging.appendChild(clone);
            document.body.appendChild(staging);

            await new Promise(r => setTimeout(r, 90));

            const canvas = await html2canvas(clone, {
                scale: 3,
                useCORS: true,
                allowTaint: true,
                backgroundColor: '#ffffff',
                logging: false,
                width: clone.offsetWidth,
                height: clone.offsetHeight
            });

            return new Promise((resolve) => {
                canvas.toBlob((blob) => resolve(blob), 'image/jpeg', 0.95);
            });
        } catch (err) {
            console.error('Error saat capture JPG:', err);
            return null;
        } finally {
            if (staging && staging.parentNode) {
                staging.parentNode.removeChild(staging);
            }
        }
    };

    let progressSwal = null;
    if (typeof Swal !== 'undefined') {
        progressSwal = Swal.fire({
            title: 'Membuat Berkas ZIP...',
            html: `
                <div style="font-size: 0.85rem; color: #475569; margin-bottom: 12px;">
                    Mengonversi kartu pelajar menjadi JPG resolusi tinggi...
                </div>
                <div style="width: 100%; background: #e2e8f0; border-radius: 8px; height: 10px; overflow: hidden;">
                    <div id="swalZipProgressBar" style="width: 0%; height: 100%; background: #059669; transition: width 0.2s;"></div>
                </div>
                <div id="swalZipProgressText" style="margin-top: 8px; font-size: 0.8rem; font-weight: 700; color: #0f172a;">
                    0 / ${pairs.length} Peserta Didik
                </div>
            `,
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
    }

    try {
        let totalFiles = 0;
        const bar = document.getElementById('swalZipProgressBar');
        const txt = document.getElementById('swalZipProgressText');

        for (let i = 0; i < pairs.length; i++) {
            const pair = pairs[i];
            const front = pair.querySelector('.kp-card-front');
            const back = pair.querySelector('.kp-card-back');
            const nisn = front?.id?.replace('card-front-', '') || ('siswa_' + (i + 1));

            const pct = Math.round(((i + 1) / pairs.length) * 100);
            if (bar) bar.style.width = pct + '%';
            if (txt) txt.textContent = `${i + 1} / ${pairs.length} Peserta Didik (${pct}%)`;

            if (btn) {
                btn.innerHTML = `<i class="fas fa-spinner fa-spin"></i> Memproses ${i + 1}/${pairs.length}...`;
            }

            if (sideFilter === 'front-only') {
                const b = await captureCardToBlob(front);
                if (b) { zip.file(`${nisn}_depan.jpg`, b); totalFiles++; }
            } else if (sideFilter === 'back-only') {
                const b = await captureCardToBlob(back);
                if (b) { zip.file(`${nisn}_belakang.jpg`, b); totalFiles++; }
            } else {
                const bf = await captureCardToBlob(front);
                if (bf) { zip.file(`${nisn}_depan.jpg`, bf); totalFiles++; }
                const bb = await captureCardToBlob(back);
                if (bb) { zip.file(`${nisn}_belakang.jpg`, bb); totalFiles++; }
            }
        }

        if (totalFiles === 0) {
            throw new Error('Tidak ada berkas yang berhasil dirender.');
        }

        if (btn) btn.innerHTML = '<i class="fas fa-file-archive"></i> Mengompres ZIP...';
        if (txt) txt.textContent = 'Mengompres seluruh berkas JPG ke dalam ZIP...';

        const zipBlob = await zip.generateAsync({ type: 'blob', compression: 'DEFLATE', compressionOptions: { level: 6 } });
        const rombelTitle = document.title?.replace(/[^a-zA-Z0-9_\-]/g, '_') || 'Kartu_Pelajar_Rombel';
        const zipName = `${rombelTitle}.zip`;

        const link = document.createElement('a');
        link.href = URL.createObjectURL(zipBlob);
        link.download = zipName;
        link.click();
        URL.revokeObjectURL(link.href);

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'success',
                title: 'Berhasil Diunduh!',
                text: `${totalFiles} berkas JPG berhasil dikompresi ke dalam ZIP.`,
                timer: 2500,
                showConfirmButton: false
            });
        }
    } catch (err) {
        console.error('Error saat proses masal JPG:', err);
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'Gagal Membuat ZIP',
                text: err.message || 'Terjadi kesalahan sistem saat mengekspor gambar.'
            });
        }
    } finally {
        if (btn) {
            btn.innerHTML = orig;
            btn.disabled = false;
        }
    }
};
