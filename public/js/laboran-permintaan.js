/**
 * Modul Bon / Form Permintaan Alat & Bahan (Tupoksi #2).
 * Bergantung pada window.SAELaboran (laboran-common.js).
 */
document.addEventListener('DOMContentLoaded', function () {
    const path = window.location.pathname;
    if (!path.includes('/dashboard/laboran/permintaan')) return;

    const L = window.SAELaboran;
    if (!L) return;

    const BASE = '/dashboard/laboran/permintaan';

    function today() {
        const now = new Date();
        return new Date(now.getTime() - now.getTimezoneOffset() * 60000).toISOString().split('T')[0];
    }

    // =====================================================================
    // 1. Indeks: modal buat bon
    // =====================================================================
    const modal = document.getElementById('modalPermintaan');
    const form = document.getElementById('formPermintaan');
    const btnOpen = document.getElementById('btnOpenPermintaan');

    if (btnOpen) {
        btnOpen.addEventListener('click', function () {
            if (form) form.reset();
            const tgl = document.getElementById('pmTanggal');
            if (tgl) tgl.value = today();
            L.openModal(modal);
        });
    }

    if (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();

            const checked = form.querySelectorAll('.pm-item:checked');
            if (checked.length === 0) {
                L.notifyError('Data Belum Lengkap', 'Pilih minimal satu alat/bahan yang diminta.');
                return;
            }

            L.submitForm(form, {
                onSuccess: function (data) {
                    L.closeModal(modal);
                    if (data && data.redirect) {
                        window.location.href = data.redirect;
                    } else {
                        L.notifySuccess(data.message || 'Bon permintaan berhasil dibuat.');
                    }
                }
            });
        });
    }

    // Hapus
    document.addEventListener('click', function (e) {
        const del = e.target.closest('.btn-delete-permintaan');
        if (del) {
            L.deleteData(BASE + '/' + del.dataset.id, del.dataset.nama || 'bon permintaan ini');
        }
    });

    // =====================================================================
    // 2. Detail: periksa ketersediaan
    // =====================================================================
    const btnPeriksa = document.getElementById('btnPeriksaKetersediaan');
    if (btnPeriksa) {
        btnPeriksa.addEventListener('click', function () {
            const id = path.split('/').filter(Boolean).pop();
            L.setBusy(btnPeriksa, true, 'Memeriksa...');

            fetch(BASE + '/' + id + '/periksa', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': L.csrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                credentials: 'same-origin'
            })
                .then(async function (res) {
                    const data = await res.json().catch(function () { return {}; });
                    if (!res.ok) throw new Error(L.extractError(data, 'Gagal memeriksa ketersediaan.'));
                    return data;
                })
                .then(function (data) {
                    L.notifySuccess(data.message || 'Pemeriksaan ketersediaan selesai.');
                })
                .catch(function (err) {
                    L.notifyError('Gagal', err.message);
                })
                .finally(function () {
                    L.setBusy(btnPeriksa, false);
                });
        });
    }

    // =====================================================================
    // 3. Detail: serah terima
    // =====================================================================
    const btnSerahkan = document.getElementById('btnSerahkan');
    const modalSerah = document.getElementById('modalSerah');
    const formSerah = document.getElementById('formSerah');

    if (btnSerahkan) {
        btnSerahkan.addEventListener('click', function () {
            L.openModal(modalSerah);
        });
    }

    if (formSerah) {
        formSerah.addEventListener('submit', function (e) {
            e.preventDefault();
            L.submitForm(formSerah, {
                onSuccess: function (data) {
                    L.closeModal(modalSerah);
                    L.notifySuccess(data.message || 'Serah terima berhasil dicatat.');
                }
            });
        });
    }
});
