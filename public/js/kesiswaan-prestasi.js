/**
 * Logika JavaScript Modular: Prestasi Siswa (Akademik, Nonakademik, Rekap)
 * Standar Resmi SAE (Vanilla JS + SweetAlert2)
 */

document.addEventListener('DOMContentLoaded', function () {
    // 1. Modal Helper
    function openModal(selector) {
        const modal = document.querySelector(selector);
        if (modal) modal.style.display = 'flex';
    }

    function closeModal(selector) {
        const modal = document.querySelector(selector);
        if (modal) modal.style.display = 'none';
    }

    document.querySelectorAll('.close-modal').forEach(btn => {
        btn.addEventListener('click', function () {
            const target = this.getAttribute('data-target');
            if (target) closeModal(target);
        });
    });

    // 2. Tombol Buka Modal Prestasi
    const btnOpenPrestasi = document.getElementById('btnOpenPrestasiModal');
    if (btnOpenPrestasi) {
        btnOpenPrestasi.addEventListener('click', () => openModal('#modalPrestasi'));
    }

    // 3. Form Submit AJAX
    const formPrestasi = document.getElementById('formPrestasi');
    if (formPrestasi) {
        formPrestasi.addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = new FormData(this);

            Swal.fire({
                title: 'Menyimpan...',
                text: 'Sedang mencatat data prestasi siswa...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            fetch(this.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    'Accept': 'application/json',
                },
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: data.message,
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => location.reload());
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal!',
                        text: data.message || 'Terjadi kesalahan saat menyimpan data prestasi.'
                    });
                }
            })
            .catch(err => {
                Swal.fire({
                    icon: 'error',
                    title: 'Kesalahan!',
                    text: 'Gagal terhubung ke server.'
                });
            });
        });
    }

    // 4. Event Delegation Hapus Prestasi
    document.addEventListener('submit', function (e) {
        const form = e.target.closest('form[data-confirm="delete"]');
        if (!form) return;

        e.preventDefault();
        const itemName = form.getAttribute('data-name') || 'data ini';

        Swal.fire({
            title: 'Hapus Prestasi?',
            text: `Apakah Anda yakin ingin menghapus ${itemName}? Tindakan ini tidak dapat dibatalkan.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Menghapus...',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });

                fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        'Accept': 'application/json',
                    },
                    body: new FormData(form)
                })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        Swal.fire('Terhapus!', data.message, 'success').then(() => location.reload());
                    } else {
                        Swal.fire('Gagal!', data.message || 'Gagal menghapus prestasi.', 'error');
                    }
                })
                .catch(() => {
                    // Fallback normal submit jika bukan response JSON
                    form.submit();
                });
            }
        });
    });
});
