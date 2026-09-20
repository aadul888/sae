/**
 * JavaScript Modul Perpustakaan - Buku Kunjungan
 * Sistem Aplikasi Edukasi (SAE)
 */

document.addEventListener('DOMContentLoaded', function () {
    const modalKunjungan = document.getElementById('modalKunjungan');

    // Live search & debounce
    const searchInput = document.getElementById('searchKunjungan');
    let searchTimeout = null;

    if (searchInput) {
        searchInput.addEventListener('input', function () {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                const url = new URL(window.location.href);
                if (this.value.trim()) {
                    url.searchParams.set('q', this.value.trim());
                } else {
                    url.searchParams.delete('q');
                }
                url.searchParams.set('page', '1');
                if (typeof window.refreshLiveTable === 'function') {
                    window.refreshLiveTable(url.toString());
                } else {
                    window.location.href = url.toString();
                }
            }, 450);
        });
    }

    // Filter Tanggal
    const filterTanggal = document.getElementById('filterTanggal');
    if (filterTanggal) {
        filterTanggal.addEventListener('change', function () {
            const url = new URL(window.location.href);
            if (this.value) {
                url.searchParams.set('tanggal', this.value);
            } else {
                url.searchParams.delete('tanggal');
            }
            url.searchParams.set('page', '1');
            if (typeof window.refreshLiveTable === 'function') {
                window.refreshLiveTable(url.toString());
            } else {
                window.location.href = url.toString();
            }
        });
    }

    // Filter Tipe
    const filterTipe = document.getElementById('filterTipe');
    if (filterTipe) {
        filterTipe.addEventListener('change', function () {
            const url = new URL(window.location.href);
            if (this.value) {
                url.searchParams.set('tipe', this.value);
            } else {
                url.searchParams.delete('tipe');
            }
            url.searchParams.set('page', '1');
            if (typeof window.refreshLiveTable === 'function') {
                window.refreshLiveTable(url.toString());
            } else {
                window.location.href = url.toString();
            }
        });
    }

    // Event Delegation: Open Modal / Delete
    document.addEventListener('click', function (e) {
        // Tombol Catat Kunjungan
        if (e.target.closest('#btnTambahKunjungan')) {
            if (modalKunjungan) {
                modalKunjungan.style.display = 'flex';
            }
        }

        // Tombol Hapus Log (SweetAlert2)
        const btnDelete = e.target.closest('.btn-delete-kunjungan');
        if (btnDelete) {
            e.preventDefault();
            const formDel = btnDelete.closest('form');
            const nama = btnDelete.getAttribute('data-nama') || 'log ini';

            Swal.fire({
                title: 'Hapus Kunjungan?',
                text: `Apakah Anda yakin ingin menghapus data kunjungan dari "${nama}"?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    formDel.submit();
                }
            });
        }
    });

    window.closeModalKunjungan = function () {
        if (modalKunjungan) modalKunjungan.style.display = 'none';
    };

    // Close on click outside
    if (modalKunjungan) {
        modalKunjungan.addEventListener('click', function (e) {
            if (e.target === modalKunjungan) closeModalKunjungan();
        });
    }
});
