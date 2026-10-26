/**
 * Modul Peminjaman Alat Laboratorium (indeks + detail).
 * Bergantung pada window.SAELaboran (laboran-common.js).
 */
document.addEventListener('DOMContentLoaded', function () {
    const path = window.location.pathname;
    if (!path.includes('/dashboard/laboran/peminjaman-alat')) return;

    const L = window.SAELaboran;
    if (!L) return;

    const BASE = '/dashboard/laboran/peminjaman-alat';

    function today() {
        const now = new Date();
        return new Date(now.getTime() - now.getTimezoneOffset() * 60000).toISOString().split('T')[0];
    }

    function daysFromNow(n) {
        const d = new Date();
        d.setDate(d.getDate() + n);
        return new Date(d.getTime() - d.getTimezoneOffset() * 60000).toISOString().split('T')[0];
    }

    // =====================================================================
    // 1. Modal ajukan peminjaman
    // =====================================================================
    const modal = document.getElementById('modalPeminjaman');
    const form = document.getElementById('formPeminjaman');
    const btnOpen = document.getElementById('btnOpenPeminjaman');
    const pjTipe = document.getElementById('pjTipe');
    const wrapPeminjamSiswa = document.getElementById('wrapPeminjamSiswa');
    const wrapPeminjamGtk = document.getElementById('wrapPeminjamGtk');
    const selectPeminjamSiswa = document.getElementById('selectPeminjamSiswa');
    const selectPeminjamGtk = document.getElementById('selectPeminjamGtk');
    const pjNama = document.getElementById('pjNama');
    const pjId = document.getElementById('pjId');
    const pjKelas = document.getElementById('pjKelas');

    function syncPeminjamTipeUI() {
        const tipe = pjTipe ? pjTipe.value : 'siswa';
        if (wrapPeminjamSiswa) wrapPeminjamSiswa.style.display = tipe === 'siswa' ? 'block' : 'none';
        if (wrapPeminjamGtk) wrapPeminjamGtk.style.display = (tipe === 'guru' || tipe === 'tendik') ? 'block' : 'none';
        if (pjNama) {
            pjNama.style.display = tipe === 'umum' ? 'block' : 'none';
            pjNama.required = tipe === 'umum';
        }
    }

    if (pjTipe) {
        pjTipe.addEventListener('change', function () {
            syncPeminjamTipeUI();
            if (pjId) pjId.value = '';
            if (pjNama) pjNama.value = '';
            if (pjKelas) pjKelas.value = '';
            if (selectPeminjamSiswa) selectPeminjamSiswa.value = '';
            if (selectPeminjamGtk) selectPeminjamGtk.value = '';
        });
    }

    if (selectPeminjamSiswa) {
        selectPeminjamSiswa.addEventListener('change', function () {
            const opt = this.options[this.selectedIndex];
            if (!opt || !this.value) {
                if (pjId) pjId.value = '';
                if (pjNama) pjNama.value = '';
                if (pjKelas) pjKelas.value = '';
                return;
            }
            if (pjId) pjId.value = this.value;
            if (pjNama) pjNama.value = opt.dataset.nama || '';
            if (pjKelas) pjKelas.value = opt.dataset.kelas || '';
        });
    }

    if (selectPeminjamGtk) {
        selectPeminjamGtk.addEventListener('change', function () {
            const opt = this.options[this.selectedIndex];
            if (!opt || !this.value) {
                if (pjId) pjId.value = '';
                if (pjNama) pjNama.value = '';
                if (pjKelas) pjKelas.value = '';
                return;
            }
            if (pjId) pjId.value = this.value;
            if (pjNama) pjNama.value = opt.dataset.nama || '';
            if (pjKelas) pjKelas.value = opt.dataset.kelas || '';
        });
    }

    if (btnOpen) {
        btnOpen.addEventListener('click', function () {
            if (form) form.reset();
            const tgl = document.getElementById('pjTanggal');
            if (tgl) tgl.value = today();
            const kembali = document.getElementById('pjKembali');
            if (kembali) kembali.value = daysFromNow(3);
            syncPeminjamTipeUI();
            L.openModal(modal);
        });
    }

    if (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();

            // Validasi minimal satu alat dipilih
            const checked = form.querySelectorAll('.pj-item:checked');
            if (checked.length === 0) {
                L.notifyError('Data Belum Lengkap', 'Pilih minimal satu alat yang akan dipinjam.');
                return;
            }

            L.submitForm(form, {
                onSuccess: function (data) {
                    L.closeModal(modal);
                    if (data && data.redirect) {
                        window.location.href = data.redirect;
                    } else {
                        L.notifySuccess(data.message || 'Pengajuan peminjaman berhasil dibuat.');
                    }
                }
            });
        });
    }

    // =====================================================================
    // 2. Modal pengembalian
    // =====================================================================
    const modalKembali = document.getElementById('modalKembali');
    const formKembali = document.getElementById('formKembali');
    const kembaliDesc = document.getElementById('kembaliDesc');

    if (formKembali) {
        formKembali.addEventListener('submit', function (e) {
            e.preventDefault();
            L.submitForm(formKembali, {
                onSuccess: function (data) {
                    L.closeModal(modalKembali);
                    L.notifySuccess(data.message || 'Pengembalian berhasil dicatat.');
                }
            });
        });
    }

    // =====================================================================
    // 3. Aksi status via AJAX
    // =====================================================================
    function ubahStatus(id, status, label, btn) {
        L.setBusy(btn, true, '');

        fetch(BASE + '/' + id + '/status', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': L.csrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            credentials: 'same-origin',
            body: JSON.stringify({ _method: 'PUT', status: status })
        })
            .then(async function (res) {
                const data = await res.json().catch(function () { return {}; });
                if (!res.ok) throw new Error(L.extractError(data, 'Gagal memperbarui status.'));
                return data;
            })
            .then(function (data) {
                L.notifySuccess(data.message || ('Status berubah menjadi ' + label + '.'));
            })
            .catch(function (err) {
                L.notifyError('Gagal', err.message);
            })
            .finally(function () {
                L.setBusy(btn, false);
            });
    }

    document.addEventListener('click', function (e) {
        // Setujui
        const setujui = e.target.closest('.btn-setujui-pinjam');
        if (setujui) {
            const nama = setujui.dataset.nama || 'peminjam ini';
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Setujui Peminjaman?',
                    text: 'Setujui pengajuan dari "' + nama + '"?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Setujui',
                    cancelButtonText: 'Batal'
                }).then(function (r) {
                    if (r.isConfirmed) ubahStatus(setujui.dataset.id, 'disetujui', 'Disetujui', setujui);
                });
            } else if (confirm('Setujui peminjaman dari "' + nama + '"?')) {
                ubahStatus(setujui.dataset.id, 'disetujui', 'Disetujui', setujui);
            }
            return;
        }

        // Serah terima
        const serah = e.target.closest('.btn-serah-terima');
        if (serah) {
            const nama = serah.dataset.nama || 'peminjam ini';
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Serah Terima Alat?',
                    text: 'Alat diserahkan kepada "' + nama + '". Stok tersedia akan berkurang.',
                    icon: 'info',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Serahkan',
                    cancelButtonText: 'Batal'
                }).then(function (r) {
                    if (r.isConfirmed) ubahStatus(serah.dataset.id, 'dipinjam', 'Dipinjam', serah);
                });
            } else if (confirm('Serahkan alat kepada "' + nama + '"?')) {
                ubahStatus(serah.dataset.id, 'dipinjam', 'Dipinjam', serah);
            }
            return;
        }

        // Proses pengembalian -> buka modal
        const kembali = e.target.closest('.btn-kembalikan-pinjam');
        if (kembali && formKembali) {
            formKembali.reset();
            formKembali.action = BASE + '/' + kembali.dataset.id + '/kembalikan';
            if (kembaliDesc) {
                kembaliDesc.textContent = 'Mencatat pengembalian alat dari: ' + (kembali.dataset.nama || '—');
            }
            L.openModal(modalKembali);
            return;
        }

        // Hapus peminjaman
        const del = e.target.closest('.btn-delete-pinjam');
        if (del) {
            L.deleteData(BASE + '/' + del.dataset.id, del.dataset.nama || 'peminjaman ini');
            return;
        }

        // Tandai semua terlambat
        const tandai = e.target.closest('#btnTandaiTerlambat');
        if (tandai) {
            const doPost = function () {
                fetch(BASE + '/tandai-terlambat', {
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
                        if (!res.ok) throw new Error(data.message || 'Gagal menandai.');
                        return data;
                    })
                    .then(function (data) {
                        L.notifySuccess(data.message || 'Peminjaman terlambat ditandai.');
                    })
                    .catch(function (err) {
                        L.notifyError('Gagal', err.message);
                    });
            };

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Tandai Peminjaman Terlambat?',
                    text: 'Semua peminjaman yang melewati tenggat akan ditandai terlambat.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Tandai',
                    cancelButtonText: 'Batal'
                }).then(function (r) { if (r.isConfirmed) doPost(); });
            } else if (confirm('Tandai semua peminjaman terlambat?')) {
                doPost();
            }
        }
    });
});
