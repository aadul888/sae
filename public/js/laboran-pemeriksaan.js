/**
 * Modul Pemeriksaan / Daftar Ceklist Alat & Bahan (halaman indeks).
 * Bergantung pada window.SAELaboran (laboran-common.js).
 */
document.addEventListener('DOMContentLoaded', function () {
    const path = window.location.pathname;
    if (!path.includes('/dashboard/laboran/pemeriksaan')) return;

    const L = window.SAELaboran;
    if (!L) return;

    const BASE = '/dashboard/laboran/pemeriksaan';

    // =====================================================================
    // 1. Modal buat pemeriksaan (halaman indeks)
    // =====================================================================
    const modal = document.getElementById('modalPemeriksaan');
    const form = document.getElementById('formPemeriksaan');
    const btnOpen = document.getElementById('btnOpenPemeriksaan');
    const btnClose = document.getElementById('btnClosePemeriksaan');
    const btnCancel = document.getElementById('btnCancelPemeriksaan');

    function today() {
        const now = new Date();
        return new Date(now.getTime() - now.getTimezoneOffset() * 60000).toISOString().split('T')[0];
    }

    if (btnOpen) {
        btnOpen.addEventListener('click', function () {
            if (form) form.reset();
            const tgl = document.getElementById('pmTanggal');
            if (tgl) tgl.value = today();
            L.openModal(modal);
        });
    }
    if (btnClose) btnClose.addEventListener('click', function () { L.closeModal(modal); });
    if (btnCancel) btnCancel.addEventListener('click', function () { L.closeModal(modal); });

    if (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            L.submitForm(form, {
                onSuccess: function (data) {
                    L.closeModal(modal);
                    const target = (data && data.redirect) ? data.redirect : null;
                    if (target) {
                        window.location.href = target;
                    } else {
                        L.notifySuccess(data.message || 'Pemeriksaan berhasil dibuat.');
                    }
                }
            });
        });
    }

    // Hapus pemeriksaan
    document.addEventListener('click', function (e) {
        const delBtn = e.target.closest('.btn-delete-pemeriksaan');
        if (delBtn) {
            L.deleteData(BASE + '/' + delBtn.dataset.id, delBtn.dataset.nama || 'pemeriksaan ini');
        }
    });

    // =====================================================================
    // 2. Halaman detail: pengisian ceklist
    // =====================================================================
    const detailForm = document.getElementById('formCeklist');
    if (detailForm) {
        const PEMERIKSAAN_ID = detailForm.dataset.id;

        // Simpan hasil ceklist
        detailForm.addEventListener('submit', function (e) {
            e.preventDefault();

            const items = [];
            document.querySelectorAll('tr[data-item-row]').forEach(function (row) {
                const id = row.dataset.itemRow;
                const hasil = row.querySelector('.ck-hasil');
                if (!hasil) return;

                items.push({
                    id: id,
                    hasil: hasil.value,
                    jumlah_diperiksa: (row.querySelector('.ck-jumlah') || {}).value || null,
                    keterangan: (row.querySelector('.ck-keterangan') || {}).value || null,
                    tindak_lanjut: (row.querySelector('.ck-tindak') || {}).value || null
                });
            });

            if (items.length === 0) {
                L.notifyError('Tidak Ada Item', 'Belum ada item ceklist untuk disimpan.');
                return;
            }

            const btn = detailForm.querySelector('button[type="submit"]');
            L.setBusy(btn, true, 'Menyimpan...');

            fetch(BASE + '/' + PEMERIKSAAN_ID + '/simpan-item', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': L.csrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                credentials: 'same-origin',
                body: JSON.stringify({ items: items })
            })
                .then(async function (res) {
                    const data = await res.json().catch(function () { return {}; });
                    if (!res.ok) throw new Error(L.extractError(data, 'Gagal menyimpan hasil ceklist.'));
                    return data;
                })
                .then(function (data) {
                    L.notifySuccess(data.message || 'Hasil pemeriksaan berhasil disimpan.');

                    // Perbarui angka rekap di header
                    if (data.rekap) {
                        const map = {
                            rekapTotal: data.rekap.total_item,
                            rekapSesuai: data.rekap.total_sesuai,
                            rekapTidakSesuai: data.rekap.total_tidak_sesuai,
                            rekapTidakAda: data.rekap.total_tidak_ada
                        };
                        Object.keys(map).forEach(function (id) {
                            const el = document.getElementById(id);
                            if (el) el.textContent = map[id];
                        });
                    }
                })
                .catch(function (err) {
                    L.notifyError('Gagal Menyimpan', err.message);
                })
                .finally(function () {
                    L.setBusy(btn, false);
                });
        });

        // Tambah item ceklist
        const btnTambahItem = document.getElementById('btnTambahItem');
        if (btnTambahItem) {
            btnTambahItem.addEventListener('click', function () {
                const modalItem = document.getElementById('modalTambahItem');
                const formItem = document.getElementById('formTambahItem');
                if (formItem) formItem.reset();
                L.openModal(modalItem);
            });
        }

        const formItem = document.getElementById('formTambahItem');
        if (formItem) {
            formItem.addEventListener('submit', function (e) {
                e.preventDefault();
                L.submitForm(formItem, {
                    onSuccess: function (data) {
                        L.closeModal(document.getElementById('modalTambahItem'));
                        L.notifySuccess(data.message || 'Item berhasil ditambahkan.');
                    }
                });
            });
        }

        // Hapus item ceklist
        document.addEventListener('click', function (e) {
            const delItem = e.target.closest('.btn-hapus-item');
            if (!delItem) return;

            const itemId = delItem.dataset.id;
            const nama = delItem.dataset.nama || 'item ini';

            const doDelete = function () {
                fetch(BASE + '/' + PEMERIKSAAN_ID + '/item/' + itemId, {
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

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Hapus Item?',
                    text: 'Hapus "' + nama + '" dari daftar ceklist?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ef4444',
                    confirmButtonText: 'Ya, Hapus!',
                    cancelButtonText: 'Batal'
                }).then(function (r) { if (r.isConfirmed) doDelete(); });
            } else if (confirm('Hapus "' + nama + '"?')) {
                doDelete();
            }
        });
    }
});
