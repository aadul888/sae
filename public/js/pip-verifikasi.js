// =========================================================================
// PIP — Modal Verifikasi Laporan & Pratinjau Bukti (gambar / PDF)
// =========================================================================
(function () {
    'use strict';

    var modal = document.getElementById('pipVerifModal');
    if (!modal) return;

    var form = document.getElementById('pipVerifForm');
    var body = document.getElementById('pipVerifModalBody');
    var catatan = document.getElementById('pipVerifCatatan');
    var title = document.getElementById('pipVerifModalTitle');

    var viewer = document.getElementById('pipVerifBuktiViewer');
    var viewerBody = document.getElementById('pipVerifBuktiBody');
    var viewerName = document.getElementById('pipVerifBuktiName');

    function tutupViewer() {
        if (!viewer) return;
        viewer.hidden = true;
        if (viewerBody) viewerBody.textContent = '';
    }

    function bukaViewer(url, type, name) {
        if (!viewer || !viewerBody || !url) return;

        viewerBody.textContent = '';
        if (viewerName) viewerName.textContent = name || 'Bukti Lampiran';

        if (type === 'image') {
            var img = document.createElement('img');
            img.src = url;
            img.alt = name || 'Bukti Lampiran';
            viewerBody.appendChild(img);
        } else {
            var frame = document.createElement('iframe');
            frame.src = url;
            frame.title = name || 'Pratinjau PDF';
            viewerBody.appendChild(frame);
        }

        viewer.hidden = false;
    }

    function tutupModal() {
        modal.style.display = 'none';
        if (body) body.textContent = '';
        tutupViewer();
    }

    document.addEventListener('click', function (e) {
        var openBtn = e.target.closest('.pip-verif-open');
        if (openBtn) {
            var tpl = document.querySelector(openBtn.getAttribute('data-verif-template'));
            if (tpl && body) body.innerHTML = tpl.innerHTML;

            if (form) form.setAttribute('action', openBtn.getAttribute('data-verif-action') || '');
            if (catatan) catatan.value = '';
            if (title) {
                title.textContent = 'Tinjau Laporan — ' + (openBtn.getAttribute('data-verif-nama') || '-') +
                    ' (' + (openBtn.getAttribute('data-verif-tahap') || '-') + ')';
            }

            if (catatan && openBtn.getAttribute('data-verif-status') === 'ditolak') {
                catatan.focus();
            }

            tutupViewer();
            modal.style.display = 'flex';
            return;
        }

        var buktiBtn = e.target.closest('.pip-verif-bukti');
        if (buktiBtn && modal.contains(buktiBtn)) {
            e.preventDefault();
            bukaViewer(
                buktiBtn.getAttribute('data-bukti-url'),
                buktiBtn.getAttribute('data-bukti-type'),
                buktiBtn.getAttribute('data-bukti-name')
            );
            return;
        }

        if (e.target.closest('[data-verif-close]') || e.target.id === 'pipVerifBuktiClose') {
            if (e.target.id === 'pipVerifBuktiClose') tutupViewer();
            else tutupModal();
            return;
        }

        if (viewer && !viewer.hidden && e.target === viewer) {
            tutupViewer();
            return;
        }

        if (e.target === modal) tutupModal();
    });

    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape') return;
        if (viewer && !viewer.hidden) {
            tutupViewer();
            return;
        }
        if (modal.style.display === 'flex') tutupModal();
    });
})();
