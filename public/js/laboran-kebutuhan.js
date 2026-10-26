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

    // Helper bersama (laboran-common.js dimuat lebih dulu oleh Blade).
    const L = window.SAELaboran;

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
                Swal.fire({
                    title: 'Menganalisis Ketersediaan...',
                    html: 'Sedang memindai inventaris dan riwayat kerusakan...',
                    allowOutsideClick: false,
                    didOpen: function () {
                        Swal.showLoading();
                    }
                });

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
                        Swal.fire({
                            title: 'Analisis Selesai',
                            text: data.message || 'Hasil analisis berhasil diterapkan.',
                            icon: 'success',
                            confirmButtonText: 'OK'
                        }).then(function () {
                            window.location.reload();
                        });
                    } else {
                        Swal.fire({
                            title: 'Galat',
                            text: data.message || 'Gagal menjalankan analisis kebutuhan.',
                            icon: 'error'
                        });
                    }
                })
                .catch(function (err) {
                    Swal.fire({
                        title: 'Kesalahan Sistem',
                        text: err.message || 'Terjadi kesalahan saat memproses analisis.',
                        icon: 'error'
                    });
                });
            };

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
