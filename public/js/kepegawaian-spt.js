/**
 * JavaScript Modul Surat Perintah Tugas (SPT) Kepegawaian
 * Standar Baku SAE: Vanilla JS, Modal Handling (z-index: 99999), SweetAlert2
 */

document.addEventListener('DOMContentLoaded', function () {
    const btnOpenModalSpt = document.getElementById('btnOpenModalSpt');
    const modalSpt = document.getElementById('modalSpt');
    const btnCloseModalSpt = document.getElementById('btnCloseModalSpt');
    const formSpt = document.getElementById('formSpt');

    if (btnOpenModalSpt && modalSpt) {
        btnOpenModalSpt.addEventListener('click', function () {
            modalSpt.style.display = 'flex';
        });
    }

    if (btnCloseModalSpt && modalSpt) {
        btnCloseModalSpt.addEventListener('click', function () {
            modalSpt.style.display = 'none';
        });
    }

    // Tutup jika klik backdrop
    if (modalSpt) {
        modalSpt.addEventListener('click', function (e) {
            if (e.target === modalSpt) {
                modalSpt.style.display = 'none';
            }
        });
    }

    // Event delegation untuk hapus dengan SweetAlert2
    document.addEventListener('submit', function (e) {
        const form = e.target.closest('form[data-confirm="delete"]');
        if (form && !form.dataset.confirmed) {
            e.preventDefault();
            const name = form.getAttribute('data-name') || 'data ini';
            if (window.Swal) {
                Swal.fire({
                    title: 'Konfirmasi Hapus',
                    text: `Apakah Anda yakin ingin menghapus ${name}?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ef4444',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: 'Ya, Hapus',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.dataset.confirmed = 'true';
                        form.submit();
                    }
                });
            } else {
                if (confirm(`Apakah Anda yakin ingin menghapus ${name}?`)) {
                    form.dataset.confirmed = 'true';
                    form.submit();
                }
            }
        }
    });
});
