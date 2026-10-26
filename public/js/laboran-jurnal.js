/**
 * Modul Jurnal Kegiatan Laboran (Tupoksi #8).
 * Bergantung pada window.SAELaboran (laboran-common.js).
 */
document.addEventListener('DOMContentLoaded', function () {
    if (!window.location.pathname.includes('/dashboard/laboran/jurnal')) return;

    const L = window.SAELaboran;
    if (!L) return;

    const BASE = '/dashboard/laboran/jurnal';

    const modal = document.getElementById('modalJurnal');
    const form = document.getElementById('formJurnal');
    const title = document.getElementById('modalJurnalTitle');
    const method = document.getElementById('jurnalMethod');
    const btnOpen = document.getElementById('btnOpenJurnal');

    function today() {
        const now = new Date();
        return new Date(now.getTime() - now.getTimezoneOffset() * 60000).toISOString().split('T')[0];
    }

    if (btnOpen) {
        btnOpen.addEventListener('click', function () {
            if (form) form.reset();
            if (form) form.action = BASE;
            if (method) method.value = 'POST';
            if (title) title.textContent = 'Catat Jurnal Kegiatan';
            const tgl = document.getElementById('jrTanggal');
            if (tgl) tgl.value = today();
            L.openModal(modal);
        });
    }

    document.addEventListener('click', function (e) {
        const editBtn = e.target.closest('.btn-edit-jurnal');
        if (editBtn && form) {
            form.reset();
            form.action = BASE + '/' + editBtn.dataset.id;
            if (method) method.value = 'PUT';
            if (title) title.textContent = 'Ubah Jurnal Kegiatan';

            const set = function (id, val) {
                const el = document.getElementById(id);
                if (el) el.value = val != null ? val : '';
            };

            set('jrTanggal', editBtn.dataset.tanggal);
            set('jrLokasi', editBtn.dataset.lokasi);
            set('jrShift', editBtn.dataset.shift);
            set('jrKegiatan', editBtn.dataset.kegiatan);
            set('jrKategori', editBtn.dataset.kategori);
            set('jrMulai', editBtn.dataset.mulai);
            set('jrSelesai', editBtn.dataset.selesai);
            set('jrUraian', editBtn.dataset.uraian);
            set('jrHasil', editBtn.dataset.hasil);
            set('jrKendala', editBtn.dataset.kendala);
            set('jrTindak', editBtn.dataset.tindak);
            set('jrSiswa', editBtn.dataset.siswa);
            set('jrGuru', editBtn.dataset.guru);
            set('jrCatatan', editBtn.dataset.catatan);
            set('jrStatus', editBtn.dataset.status);

            L.openModal(modal);
            return;
        }

        const delBtn = e.target.closest('.btn-delete-jurnal');
        if (delBtn) {
            L.deleteData(BASE + '/' + delBtn.dataset.id, delBtn.dataset.nama || 'jurnal ini');
        }
    });

    if (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            L.submitForm(form, {
                onSuccess: function (data) {
                    L.closeModal(modal);
                    L.notifySuccess(data.message || 'Jurnal kegiatan berhasil disimpan.');
                }
            });
        });
    }
});
