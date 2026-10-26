/**
 * Modul Buku Administrasi Jurusan (Tupoksi #1).
 * Bergantung pada window.SAELaboran (laboran-common.js).
 */
document.addEventListener('DOMContentLoaded', function () {
    if (!window.location.pathname.includes('/dashboard/laboran/administrasi')) return;

    const L = window.SAELaboran;
    if (!L) return;

    const BASE = '/dashboard/laboran/administrasi';

    const modal = document.getElementById('modalAdministrasi');
    const form = document.getElementById('formAdministrasi');
    const title = document.getElementById('modalAdministrasiTitle');
    const method = document.getElementById('administrasiMethod');
    const btnOpen = document.getElementById('btnOpenAdministrasi');

    function today() {
        const now = new Date();
        return new Date(now.getTime() - now.getTimezoneOffset() * 60000).toISOString().split('T')[0];
    }

    if (btnOpen) {
        btnOpen.addEventListener('click', function () {
            if (form) form.reset();
            if (form) form.action = BASE;
            if (method) method.value = 'POST';
            if (title) title.textContent = 'Catat Dokumen Administrasi';
            const tgl = document.getElementById('adTanggal');
            if (tgl) tgl.value = today();
            L.openModal(modal);
        });
    }

    document.addEventListener('click', function (e) {
        const editBtn = e.target.closest('.btn-edit-administrasi');
        if (editBtn && form) {
            form.reset();
            form.action = BASE + '/' + editBtn.dataset.id;
            if (method) method.value = 'PUT';
            if (title) title.textContent = 'Ubah Dokumen Administrasi';

            const set = function (id, val) {
                const el = document.getElementById(id);
                if (el) el.value = val != null ? val : '';
            };

            set('adTanggal', editBtn.dataset.tanggal);
            set('adUrut', editBtn.dataset.urut);
            set('adTA', editBtn.dataset.ta);
            set('adSemester', editBtn.dataset.semester);
            set('adJenis', editBtn.dataset.jenis);
            set('adNomor', editBtn.dataset.nomor);
            set('adPerihal', editBtn.dataset.perihal);
            set('adAsal', editBtn.dataset.asal);
            set('adTglDok', editBtn.dataset.tglDok);
            set('adRingkasan', editBtn.dataset.ringkasan);
            set('adKeterangan', editBtn.dataset.keterangan);
            set('adStatus', editBtn.dataset.status);

            L.openModal(modal);
            return;
        }

        const delBtn = e.target.closest('.btn-delete-administrasi');
        if (delBtn) {
            L.deleteData(BASE + '/' + delBtn.dataset.id, delBtn.dataset.nama || 'dokumen ini');
        }
    });

    if (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            L.submitForm(form, {
                onSuccess: function (data) {
                    L.closeModal(modal);
                    L.notifySuccess(data.message || 'Dokumen administrasi berhasil disimpan.');
                }
            });
        });
    }
});
