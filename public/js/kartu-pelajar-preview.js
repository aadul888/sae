/**
 * SAE - Kartu Pelajar Modal Preview & Cetak Script
 * Handles card preview popup, flip side tabs, zoom QR code, and JPG/PDF exports.
 */

let currentKpNisn = '';

window.openKartuPelajarModal = function(nisn) {
    currentKpNisn = nisn;
    if (typeof window.closeKpQrZoom === 'function') {
        window.closeKpQrZoom();
    }
    const modal = document.getElementById('kartuPelajarModal');
    if (!modal) return;

    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';

    const loading = document.getElementById('kpModalLoading');
    const content = document.getElementById('kpModalContent');
    const container = document.getElementById('kpCardContainer');
    const title = document.getElementById('kpModalTitle');
    const printLink = document.getElementById('kpBtnPrintLink');
    const verifyLink = document.getElementById('kpBtnVerifyLink');

    if (loading) loading.style.display = 'flex';
    if (content) content.style.display = 'none';

    fetch('/kartu-pelajar/preview/' + encodeURIComponent(nisn), {
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(r => r.json())
    .then(data => {
        if (!data.success) {
            alert(data.message || 'Gagal memuat kartu pelajar.');
            window.closeKartuPelajarModal();
            return;
        }

        if (title) {
            title.innerHTML = 'Kartu Pelajar: ' + escapeKpHtml(data.card.nama) + ' (' + escapeKpHtml(data.card.nisn) + ')';
        }
        if (container) {
            container.innerHTML = data.html;
        }
        if (printLink) {
            printLink.href = '/dashboard/kartu-pelajar/cetak/' + encodeURIComponent(data.card.nisn);
        }
        if (verifyLink) {
            verifyLink.href = data.card.verify_url;
        }

        if (loading) loading.style.display = 'none';
        if (content) content.style.display = 'block';
        window.setKpModalTab('both');
    })
    .catch(err => {
        console.error(err);
        alert('Terjadi kesalahan saat memuat data kartu pelajar.');
        window.closeKartuPelajarModal();
    });
};

window.toggleKpUnduhDropdown = function(e) {
    if (e) e.stopPropagation();
    const menu = document.getElementById('kpUnduhJpgMenu');
    if (!menu) return;
    menu.style.display = (menu.style.display === 'none' || menu.style.display === '') ? 'block' : 'none';
};

window.closeKpUnduhDropdown = function() {
    const menu = document.getElementById('kpUnduhJpgMenu');
    if (menu) menu.style.display = 'none';
};

document.addEventListener('click', function(e) {
    const wrap = document.getElementById('kpDropdownUnduhJpgWrap');
    if (wrap && !wrap.contains(e.target)) {
        window.closeKpUnduhDropdown();
    }
});

async function loadHtml2CanvasLib() {
    if (typeof html2canvas !== 'undefined') return true;
    return new Promise((resolve, reject) => {
        const s = document.createElement('script');
        s.src = '/js/html2canvas.min.js';
        s.onload = () => resolve(true);
        s.onerror = () => reject(new Error('Gagal memuat pustaka html2canvas'));
        document.head.appendChild(s);
    });
}

window.unduhKartuJpg = async function(mode) {
    window.closeKpUnduhDropdown();
    const btn = document.getElementById('kpBtnUnduhJpg');
    const originalHtml = btn ? btn.innerHTML : '';

    if (btn) {
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <span>Menyiapkan...</span>';
        btn.disabled = true;
    }

    try {
        await loadHtml2CanvasLib();
    } catch (e) {
        alert('Gagal memuat script html2canvas untuk unduh JPG.');
        if (btn) {
            btn.innerHTML = originalHtml;
            btn.disabled = false;
        }
        return;
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
            link.download = `Kartu_Pelajar_${currentKpNisn || 'Siswa'}_${sideName}.jpg`;
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

    const frontEl = document.querySelector('#kpCardContainer .kp-card-front');
    const backEl = document.querySelector('#kpCardContainer .kp-card-back');

    try {
        if (mode === 'front') {
            await captureCardToJpg(frontEl, 'DEPAN');
        } else if (mode === 'back') {
            await captureCardToJpg(backEl, 'BELAKANG');
        } else if (mode === 'both') {
            await captureCardToJpg(frontEl, 'DEPAN');
            await new Promise(r => setTimeout(r, 400));
            await captureCardToJpg(backEl, 'BELAKANG');
        }

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Unduh Berhasil!',
                text: 'Kartu pelajar format JPG berhasil diunduh.',
                icon: 'success',
                confirmButtonColor: '#10b981',
                confirmButtonText: 'Selesai'
            });
        } else if (window.SAE && typeof window.SAE.toast === 'function') {
            window.SAE.toast('Kartu pelajar format JPG berhasil diunduh!', 'success');
        }
    } catch (err) {
        console.error(err);
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Gagal Mengunduh',
                text: 'Gagal mengunduh kartu dalam format JPG: ' + (err.message || 'Error'),
                icon: 'error',
                confirmButtonColor: '#ef4444'
            });
        } else {
            alert('Gagal mengunduh kartu dalam format JPG: ' + (err.message || 'Error'));
        }
    } finally {
        if (btn) {
            btn.innerHTML = originalHtml;
            btn.disabled = false;
        }
    }
};

window.closeKartuPelajarModal = function() {
    window.closeKpUnduhDropdown();
    window.closeKpQrZoom();
    const modal = document.getElementById('kartuPelajarModal');
    if (!modal) return;
    modal.style.display = 'none';
    document.body.style.overflow = '';
};

window.handleKpModalBackdropClick = function(e) {
    if (e.target.id === 'kartuPelajarModal') {
        window.closeKartuPelajarModal();
    }
};

window.setKpModalTab = function(tab) {
    const btnBoth = document.getElementById('kpBtnTabBoth');
    const btnFront = document.getElementById('kpBtnTabFront');
    const btnBack = document.getElementById('kpBtnTabBack');

    [btnBoth, btnFront, btnBack].forEach(b => {
        if (b) b.classList.remove('active');
    });

    const frontCard = document.querySelector('#kpCardContainer .kp-card-front');
    const backCard = document.querySelector('#kpCardContainer .kp-card-back');

    if (!frontCard || !backCard) return;

    if (tab === 'front') {
        if (btnFront) btnFront.classList.add('active');
        frontCard.style.display = 'flex';
        backCard.style.display = 'none';
    } else if (tab === 'back') {
        if (btnBack) btnBack.classList.add('active');
        frontCard.style.display = 'none';
        backCard.style.display = 'flex';
    } else {
        if (btnBoth) btnBoth.classList.add('active');
        frontCard.style.display = 'flex';
        backCard.style.display = 'flex';
    }
};

function escapeKpHtml(text) {
    if (!text) return '';
    return text.toString().replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
}

window.zoomKpQrCode = function(e, el) {
    if (e) {
        e.stopPropagation();
        if (e.preventDefault) e.preventDefault();
    }

    const overlay = document.getElementById('kpQrZoomOverlay');
    const box = document.getElementById('kpQrZoomBox');
    const nameEl = document.getElementById('kpQrZoomName');
    const nisnEl = document.getElementById('kpQrZoomNisn');
    const rombelEl = document.getElementById('kpQrZoomRombel');

    if (!overlay || !box) return;

    const targetBox = el || document.querySelector('.kp-qrcode-box');
    const qrSvg = targetBox ? targetBox.querySelector('svg') : null;
    if (qrSvg) {
        box.innerHTML = qrSvg.outerHTML;
    }

    const name = (targetBox && targetBox.dataset.studentName) ? targetBox.dataset.studentName : (document.querySelector('.kp-student-name')?.textContent?.trim() || '-');
    const nisn = (targetBox && targetBox.dataset.studentNisn) ? targetBox.dataset.studentNisn : (document.querySelector('.kp-nisn-value')?.textContent?.trim() || currentKpNisn);
    const rombel = (targetBox && targetBox.dataset.studentRombel) ? targetBox.dataset.studentRombel : (document.querySelector('.kp-rombel-name')?.textContent?.trim() || '-');

    if (nameEl) nameEl.textContent = name;
    if (nisnEl) nisnEl.innerHTML = '<i class="fas fa-id-badge"></i> NISN: ' + escapeKpHtml(nisn);
    if (rombelEl) {
        if (rombel && rombel !== '-') {
            rombelEl.innerHTML = '<i class="fas fa-users-rectangle"></i> ' + escapeKpHtml(rombel);
            rombelEl.style.display = 'inline-flex';
        } else {
            rombelEl.style.display = 'none';
        }
    }

    overlay.classList.add('show');
    overlay.style.setProperty('display', 'flex', 'important');
};

window.closeKpQrZoom = function() {
    const overlay = document.getElementById('kpQrZoomOverlay');
    if (overlay) {
        overlay.classList.remove('show');
        overlay.style.setProperty('display', 'none', 'important');
    }
};

// Cetak Rombel Modal Functions
window.openCetakRombelModal = function(defaultRombel = '') {
    const modal = document.getElementById('cetakRombelModal');
    if (!modal) return;
    const select = document.getElementById('selectCetakRombel');
    if (select && defaultRombel) {
        select.value = defaultRombel;
    }
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
};

window.closeCetakRombelModal = function() {
    const modal = document.getElementById('cetakRombelModal');
    if (!modal) return;
    modal.style.display = 'none';
    document.body.style.overflow = '';
};

window.handleCetakRombelBackdropClick = function(e) {
    if (e.target.id === 'cetakRombelModal') {
        window.closeCetakRombelModal();
    }
};

window.proceedCetakRombel = function() {
    const select = document.getElementById('selectCetakRombel');
    if (!select || !select.value) {
        alert('Silakan pilih rombongan belajar terlebih dahulu.');
        return;
    }
    const rombelName = select.value;
    window.open('/dashboard/kartu-pelajar/cetak-rombel/' + encodeURIComponent(rombelName), '_blank');
    window.closeCetakRombelModal();
};

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const qrOverlay = document.getElementById('kpQrZoomOverlay');
        if (qrOverlay && qrOverlay.style.display !== 'none') {
            window.closeKpQrZoom();
            return;
        }
        window.closeKartuPelajarModal();
        if (typeof window.closeCetakRombelModal === 'function') {
            window.closeCetakRombelModal();
        }
    }
});
