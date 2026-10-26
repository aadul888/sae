/**
 * Modul Analisis & Kebutuhan Alat/Bahan Laboratorium.
 * Bergantung pada window.SAELaboran (laboran-common.js).
 */
document.addEventListener('DOMContentLoaded', function () {
    if (!window.location.pathname.includes('/dashboard/laboran/kebutuhan')) return;

    const L = window.SAELaboran;
    if (!L) return;

    const modal = document.getElementById('modalKebutuhan');
    const form = document.getElementById('formKebutuhan');
    const title = document.getElementById('modalKebutuhanTitle');
    const methodInput = document.getElementById('kebutuhanMethod');
    const btnOpen = document.getElementById('btnOpenKebutuhan');
    const btnClose = document.getElementById('btnCloseKebutuhan');
    const btnCancel = document.getElementById('btnCancelKebutuhan');

    const STORE_URL = '/dashboard/laboran/kebutuhan';

    function openCreate() {
        if (!form) return;
        form.reset();
        form.action = STORE_URL;
        if (methodInput) methodInput.value = 'POST';
        if (title) title.textContent = 'Tambah Kebutuhan';

        const tgl = document.getElementById('kbTanggal');
        if (tgl) {
            const now = new Date();
            tgl.value = new Date(now.getTime() - now.getTimezoneOffset() * 60000).toISOString().split('T')[0];
        }

        L.openModal(modal);
    }

    function openEdit(d) {
        if (!form) return;
        form.reset();
        form.action = STORE_URL + '/' + d.id;
        if (methodInput) methodInput.value = 'PUT';
        if (title) title.textContent = 'Ubah Data Kebutuhan';

        const set = function (id, val) {
            const el = document.getElementById(id);
            if (el) el.value = val != null ? val : '';
        };

        set('kbNama', d.nama);
        set('kbJenis', d.jenis);
        set('kbKategori', d.kategori);
        set('kbSpesifikasi', d.spesifikasi);
        set('kbKebutuhan', d.kebutuhan);
        set('kbTersedia', d.tersedia);
        set('kbSatuan', d.satuan);
        set('kbPrioritas', d.prioritas);
        set('kbSumber', d.sumber);
        set('kbAnalisis', d.analisis);
        set('kbMapel', d.mapel);
        set('kbLokasi', d.lokasi);
        set('kbTA', d.ta);
        set('kbSemester', d.semester);
        set('kbTanggal', d.tanggal);
        set('kbStatus', d.status);
        set('kbJurusan', d.jurusan);

        L.openModal(modal);
    }

    if (btnOpen) btnOpen.addEventListener('click', openCreate);
    if (btnClose) btnClose.addEventListener('click', function () { L.closeModal(modal); });
    if (btnCancel) btnCancel.addEventListener('click', function () { L.closeModal(modal); });

    document.addEventListener('click', function (e) {
        const editBtn = e.target.closest('.btn-edit-kebutuhan');
        if (editBtn) {
            openEdit({
                id: editBtn.dataset.id,
                nama: editBtn.dataset.nama,
                jenis: editBtn.dataset.jenis,
                kategori: editBtn.dataset.kategori,
                spesifikasi: editBtn.dataset.spesifikasi,
                kebutuhan: editBtn.dataset.kebutuhan,
                tersedia: editBtn.dataset.tersedia,
                satuan: editBtn.dataset.satuan,
                prioritas: editBtn.dataset.prioritas,
                sumber: editBtn.dataset.sumber,
                analisis: editBtn.dataset.analisis,
                mapel: editBtn.dataset.mapel,
                lokasi: editBtn.dataset.lokasi,
                ta: editBtn.dataset.ta,
                semester: editBtn.dataset.semester,
                tanggal: editBtn.dataset.tanggal,
                status: editBtn.dataset.status,
                jurusan: editBtn.dataset.jurusan
            });
            return;
        }

        const delBtn = e.target.closest('.btn-delete-kebutuhan');
        if (delBtn) {
            L.deleteData(STORE_URL + '/' + delBtn.dataset.id, delBtn.dataset.nama || 'data ini');
        }
    });

    if (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            L.submitForm(form, {
                onSuccess: function (data) {
                    L.closeModal(modal);
                    L.notifySuccess(data.message || 'Data kebutuhan berhasil disimpan.');
                }
            });
        });
    }
});
