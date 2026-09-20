/**
 * JavaScript Modul Perpustakaan - Sirkulasi Peminjaman & Pengembalian
 * Sistem Aplikasi Edukasi (SAE)
 */

document.addEventListener('DOMContentLoaded', function () {
    const modalPinjam = document.getElementById('modalPinjam');
    const modalKembalikan = document.getElementById('modalKembalikan');
    const formKembalikan = document.getElementById('formKembalikan');
    const kembaliDetailJudul = document.getElementById('kembaliDetailJudul');

    // Live search & debounce
    const searchInput = document.getElementById('searchSirkulasi');
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

    // Filter Status
    const filterStatus = document.getElementById('filterStatus');
    if (filterStatus) {
        filterStatus.addEventListener('change', function () {
            const url = new URL(window.location.href);
            if (this.value) {
                url.searchParams.set('status', this.value);
            } else {
                url.searchParams.delete('status');
            }
            url.searchParams.set('page', '1');
            if (typeof window.refreshLiveTable === 'function') {
                window.refreshLiveTable(url.toString());
            } else {
                window.location.href = url.toString();
            }
        });
    }

    // Event Delegation: Open Modal Pinjam / Kembalikan / Delete
    document.addEventListener('click', function (e) {
        // Tombol Tambah Peminjaman
        if (e.target.closest('#btnTambahPeminjaman')) {
            if (modalPinjam) {
                modalPinjam.style.display = 'flex';
            }
        }

        // Tombol Proses Kembalikan
        const btnKembalikan = e.target.closest('.btn-kembalikan');
        if (btnKembalikan) {
            const id = btnKembalikan.getAttribute('data-id');
            const kode = btnKembalikan.getAttribute('data-kode');
            const judul = btnKembalikan.getAttribute('data-judul');

            if (formKembalikan && modalKembalikan) {
                formKembalikan.action = `/dashboard/perpustakaan/sirkulasi/${id}/kembalikan`;
                if (kembaliDetailJudul) {
                    kembaliDetailJudul.textContent = `[${kode}] ${judul}`;
                }
                modalKembalikan.style.display = 'flex';
            }
        }

        // Tombol Hapus Transaksi (SweetAlert2)
        const btnDelete = e.target.closest('.btn-delete-sirkulasi');
        if (btnDelete) {
            e.preventDefault();
            const formDel = btnDelete.closest('form');
            const kode = btnDelete.getAttribute('data-kode') || 'transaksi ini';

            Swal.fire({
                title: 'Hapus Transaksi?',
                text: `Apakah Anda yakin ingin menghapus data transaksi "${kode}"? Stok buku akan disesuaikan.`,
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

    window.closeModalPinjam = function () {
        if (modalPinjam) modalPinjam.style.display = 'none';
    };

    window.closeModalKembalikan = function () {
        if (modalKembalikan) modalKembalikan.style.display = 'none';
    };

    // Close on click outside
    if (modalPinjam) {
        modalPinjam.addEventListener('click', function (e) {
            if (e.target === modalPinjam) closeModalPinjam();
        });
    }
    if (modalKembalikan) {
        modalKembalikan.addEventListener('click', function (e) {
            if (e.target === modalKembalikan) closeModalKembalikan();
        });
    }
});
