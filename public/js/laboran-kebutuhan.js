/**
 * Modul Analisis & Kebutuhan Alat/Bahan Laboratorium.
 * Standar Baku SAE: Vanilla JS, Modal Handling (z-index: 99999), SweetAlert2.
 */
document.addEventListener('DOMContentLoaded', function () {
    if (!window.location.pathname.includes('/dashboard/laboran/kebutuhan')) return;

    const modal = document.getElementById('modalKebutuhan');
    const form = document.getElementById('formKebutuhan');
    const title = document.getElementById('modalKebutuhanTitle');
    const methodInput = document.getElementById('kebutuhanMethod');
    const btnOpen = document.getElementById('btnOpenKebutuhan');
    const btnClose = document.getElementById('btnCloseKebutuhan');
    const btnCancel = document.getElementById('btnCancelKebutuhan');

    const STORE_URL = '/dashboard/laboran/kebutuhan';

    // Helper mandiri dengan fallback
    const L = window.SAELaboran || {
        openModal: function (m) {
            if (m) m.style.display = 'flex';
        },
        closeModal: function (m) {
            if (m) m.style.display = 'none';
        },
        notifySuccess: function (msg) {
            if (typeof Swal !== 'undefined') {
                return Swal.fire({
                    icon: 'success',
                    title: 'Berhasil',
                    text: msg,
                    timer: 1400,
                    showConfirmButton: false
                }).then(function () {
                    window.location.reload();
                });
            }
            alert(msg);
            window.location.reload();
            return Promise.resolve();
        },
        notifyError: function (t, msg) {
            if (typeof Swal !== 'undefined') {
                return Swal.fire({ icon: 'error', title: t, text: msg });
            }
            alert(t + ': ' + msg);
            return Promise.resolve();
        },
        deleteData: function (url, name) {
            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const doDel = function () {
                fetch(url, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }
                })
                .then(function (r) { return r.json(); })
                .then(function (d) {
                    if (d.status === 'success') {
                        if (typeof Swal !== 'undefined') {
                            Swal.fire('Berhasil', d.message || 'Data berhasil dihapus.', 'success').then(function () {
                                window.location.reload();
                            });
                        } else {
                            alert(d.message || 'Data berhasil dihapus.');
                            window.location.reload();
                        }
                    } else {
                        if (typeof Swal !== 'undefined') {
                            Swal.fire('Gagal', d.message || 'Terjadi kesalahan.', 'error');
                        } else {
                            alert(d.message || 'Terjadi kesalahan.');
                        }
                    }
                })
                .catch(function (err) {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire('Error', err.message || 'Gagal menghapus data.', 'error');
                    } else {
                        alert(err.message || 'Gagal menghapus data.');
                    }
                });
            };

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Hapus data kebutuhan?',
                    text: 'Hapus ' + name + '?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ef4444',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: 'Ya, Hapus',
                    cancelButtonText: 'Batal'
                }).then(function (res) {
                    if (res.isConfirmed) doDel();
                });
            } else if (confirm('Hapus ' + name + '?')) {
                doDel();
            }
        },
        submitForm: function (f, opts) {
            const btn = f.querySelector('button[type="submit"]');
            const orig = btn ? btn.innerHTML : '';
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Menyimpan...';
            }
            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const formData = new FormData(f);

            fetch(f.action, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                body: formData
            })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = orig;
                }
                if (d.status === 'success') {
                    if (opts && typeof opts.onSuccess === 'function') {
                        opts.onSuccess(d);
                    } else {
                        if (typeof Swal !== 'undefined') {
                            Swal.fire('Berhasil', d.message || 'Data berhasil disimpan.', 'success').then(function () {
                                window.location.reload();
                            });
                        } else {
                            alert(d.message || 'Data berhasil disimpan.');
                            window.location.reload();
                        }
                    }
                } else {
                    const err = (d && d.errors) ? Object.values(d.errors)[0][0] : (d.message || 'Gagal menyimpan data.');
                    if (typeof Swal !== 'undefined') {
                        Swal.fire('Gagal', err, 'error');
                    } else {
                        alert(err);
                    }
                }
            })
            .catch(function (err) {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = orig;
                }
                if (typeof Swal !== 'undefined') {
                    Swal.fire('Error', err.message || 'Terjadi kesalahan sistem.', 'error');
                } else {
                    alert(err.message || 'Terjadi kesalahan sistem.');
                }
            });
        }
    };

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

    if (modal) {
        modal.addEventListener('click', function (e) {
            if (e.target === modal) L.closeModal(modal);
        });
    }

    document.addEventListener('click', function (e) {
        // Tombol Tambah di header banner
        if (e.target.closest('#btnOpenKebutuhan')) {
            openCreate();
            return;
        }

        // Tombol Batal/Tutup
        if (e.target.closest('#btnCloseKebutuhan, #btnCancelKebutuhan')) {
            L.closeModal(modal);
            return;
        }

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
            return;
        }

        // Tombol Jalankan Analisis
        const triggerAnalisis = e.target.closest('#btnJalankanAnalisis, .btn-trigger-analisis');
        if (triggerAnalisis) {
            const doAnalisis = function () {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: 'Menganalisis Ketersediaan...',
                        html: 'Sedang memindai inventaris dan riwayat kerusakan...',
                        allowOutsideClick: false,
                        didOpen: function () {
                            Swal.showLoading();
                        }
                    });
                }

                fetch(STORE_URL + '/jalankan-analisis', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                    }
                })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (data.status === 'success' || data.success) {
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                title: 'Analisis Selesai',
                                text: data.message || 'Hasil analisis berhasil diterapkan.',
                                icon: 'success',
                                confirmButtonText: 'OK'
                            }).then(function () {
                                window.location.reload();
                            });
                        } else {
                            alert(data.message || 'Hasil analisis berhasil diterapkan.');
                            window.location.reload();
                        }
                    } else {
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                title: 'Galat',
                                text: data.message || 'Gagal menjalankan analisis kebutuhan.',
                                icon: 'error'
                            });
                        } else {
                            alert(data.message || 'Gagal menjalankan analisis kebutuhan.');
                        }
                    }
                })
                .catch(function (err) {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            title: 'Kesalahan Sistem',
                            text: err.message || 'Terjadi kesalahan saat memproses analisis.',
                            icon: 'error'
                        });
                    } else {
                        alert(err.message || 'Terjadi kesalahan saat memproses analisis.');
                    }
                });
            };

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Jalankan Analisis Kebutuhan?',
                    html: 'Sistem akan memindai stok inventaris yang <strong>kritis, habis, rusak</strong>, atau menipis dalam cakupan jurusan Anda dan otomatis memasukkannya ke daftar kebutuhan.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#3b82f6',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: '<i class="fas fa-wand-magic-sparkles me-1"></i> Jalankan Sekarang',
                    cancelButtonText: 'Batal'
                }).then(function (result) {
                    if (result.isConfirmed) doAnalisis();
                });
            } else if (confirm('Jalankan Analisis Kebutuhan dari stok inventaris?')) {
                doAnalisis();
            }
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
