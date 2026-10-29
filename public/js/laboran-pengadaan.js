/**
 * Modul Usulan Pengadaan Alat & Bahan (indeks + detail).
 * Bergantung pada window.SAELaboran (laboran-common.js).
 */
document.addEventListener('DOMContentLoaded', function () {
    const path = window.location.pathname;
    if (!path.includes('/dashboard/laboran/pengadaan')) return;

    const L = window.SAELaboran;
    if (!L) return;

    const BASE = '/dashboard/laboran/pengadaan';

    function today() {
        const now = new Date();
        return new Date(now.getTime() - now.getTimezoneOffset() * 60000).toISOString().split('T')[0];
    }

    // =====================================================================
    // 1. INDEKS: modal usulan
    // =====================================================================
    const modal = document.getElementById('modalPengadaan');
    const form = document.getElementById('formPengadaan');
    const title = document.getElementById('modalPengadaanTitle');
    const method = document.getElementById('pengadaanMethod');
    const btnOpen = document.getElementById('btnOpenPengadaan');
    const kebutuhanWrap = document.getElementById('kebutuhanWrap');
    const catatanWrap = document.getElementById('catatanWrap');

    function openCreate() {
        if (!form) return;
        form.reset();
        form.action = BASE;
        if (method) method.value = 'POST';
        if (title) title.textContent = 'Buat Usulan Pengadaan';
        if (kebutuhanWrap) kebutuhanWrap.style.display = '';
        if (catatanWrap) catatanWrap.style.display = 'none';

        const tgl = document.getElementById('pgTanggal');
        if (tgl) tgl.value = today();

        L.openModal(modal);
    }

    function openEdit(d) {
        if (!form) return;
        form.reset();
        form.action = BASE + '/' + d.id;
        if (method) method.value = 'PUT';
        if (title) title.textContent = 'Ubah Usulan Pengadaan';
        // Item ditarik hanya saat pembuatan
        if (kebutuhanWrap) kebutuhanWrap.style.display = 'none';
        if (catatanWrap) catatanWrap.style.display = '';

        const set = function (id, val) {
            const el = document.getElementById(id);
            if (el) el.value = val != null ? val : '';
        };

        set('pgTanggal', d.tanggal);
        set('pgTA', d.ta);
        set('pgSemester', d.semester);
        set('pgJenis', d.jenis || 'semua');
        set('pgDana', d.dana);
        set('pgUrgensi', d.urgensi);
        set('pgStatus', d.status);
        set('pgJustifikasi', d.justifikasi);
        set('pgCatatan', d.catatan);

        L.openModal(modal);
    }

    if (btnOpen) btnOpen.addEventListener('click', openCreate);

    if (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            L.submitForm(form, {
                onSuccess: function (data) {
                    L.closeModal(modal);
                    if (data && data.redirect) {
                        window.location.href = data.redirect;
                    } else {
                        L.notifySuccess(data.message || 'Usulan pengadaan berhasil disimpan.');
                    }
                }
            });
        });
    }

    document.addEventListener('click', function (e) {
        const editBtn = e.target.closest('.btn-edit-pengadaan');
        if (editBtn) {
            openEdit({
                id: editBtn.dataset.id,
                ta: editBtn.dataset.ta,
                semester: editBtn.dataset.semester,
                tanggal: editBtn.dataset.tanggal,
                jenis: editBtn.dataset.jenis,
                dana: editBtn.dataset.dana,
                urgensi: editBtn.dataset.urgensi,
                status: editBtn.dataset.status,
                justifikasi: editBtn.dataset.justifikasi,
                catatan: editBtn.dataset.catatan
            });
            return;
        }

        const delBtn = e.target.closest('.btn-delete-pengadaan');
        if (delBtn) {
            L.deleteData(BASE + '/' + delBtn.dataset.id, delBtn.dataset.nama || 'usulan ini');
            return;
        }

        // =================================================================
        // 2. DETAIL: item usulan
        // =================================================================
        const delItem = e.target.closest('.btn-hapus-item-usulan');
        if (delItem) {
            const usulanId = delItem.dataset.usulan;
            const itemId = delItem.dataset.id;
            const nama = delItem.dataset.nama || 'item ini';

            const doDelete = function () {
                fetch(BASE + '/' + usulanId + '/item/' + itemId, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': L.csrfToken(),
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    credentials: 'same-origin'
                })
                    .then(async function (res) {
                        const data = await res.json().catch(function () { return {}; });
                        if (!res.ok) throw new Error(data.message || 'Gagal menghapus item.');
                        return data;
                    })
                    .then(function (data) {
                        L.notifySuccess(data.message || 'Item berhasil dihapus.');
                    })
                    .catch(function (err) {
                        L.notifyError('Gagal Menghapus', err.message);
                    });
            };

            window.SAE.confirm('Hapus "' + nama + '" dari usulan?', 'Hapus Item?', 'danger', 'Ya, Hapus!').then(function (ok) {
                if (ok) doDelete();
            });
            return;
        }

        // Buka modal tambah item (halaman detail)
        const openItem = e.target.closest('#btnTambahItemUsulan');
        if (openItem) {
            const formItem = document.getElementById('formItemUsulan');
            if (formItem) formItem.reset();
            L.openModal(document.getElementById('modalItemUsulan'));
            return;
        }

        // Edit item usulan
        const editItem = e.target.closest('.btn-edit-item-usulan');
        if (editItem) {
            const formItem = document.getElementById('formItemUsulan');
            if (!formItem) return;

            formItem.reset();
            formItem.action = BASE + '/' + editItem.dataset.usulan + '/item/' + editItem.dataset.id;
            const m = document.getElementById('itemUsulanMethod');
            if (m) m.value = 'PUT';
            const t = document.getElementById('modalItemUsulanTitle');
            if (t) t.textContent = 'Ubah Item Usulan';

            const set = function (id, val) {
                const el = document.getElementById(id);
                if (el) el.value = val != null ? val : '';
            };

            set('iuNama', editItem.dataset.nama);
            set('iuJenis', editItem.dataset.jenis);
            set('iuSpesifikasi', editItem.dataset.spesifikasi);
            set('iuJumlah', editItem.dataset.jumlah);
            set('iuSatuan', editItem.dataset.satuan);
            set('iuHarga', editItem.dataset.harga);
            set('iuPrioritas', editItem.dataset.prioritas);
            set('iuKeterangan', editItem.dataset.keterangan);

            L.openModal(document.getElementById('modalItemUsulan'));
        }
    });

    // Form item usulan (tambah/ubah)
    const formItem = document.getElementById('formItemUsulan');
    if (formItem) {
        formItem.addEventListener('submit', function (e) {
            e.preventDefault();
            L.submitForm(formItem, {
                onSuccess: function (data) {
                    L.closeModal(document.getElementById('modalItemUsulan'));
                    L.notifySuccess(data.message || 'Item usulan berhasil disimpan.');
                }
            });
        });
    }
});
