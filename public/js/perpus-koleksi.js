/**
 * JavaScript Modul Perpustakaan - Koleksi & Katalog Buku
 * Sistem Aplikasi Edukasi (SAE)
 */

document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('modalBuku');
    const form = document.getElementById('formBuku');
    const modalTitle = document.getElementById('modalTitle');
    const btnSimpan = document.getElementById('btnSimpan');
    const formMethod = document.getElementById('formMethod');

    // Live search & debounce
    const searchInput = document.getElementById('searchKoleksi');
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

    // Filter Kategori
    const filterKategori = document.getElementById('filterKategori');
    if (filterKategori) {
        filterKategori.addEventListener('change', function () {
            const url = new URL(window.location.href);
            if (this.value) {
                url.searchParams.set('kategori', this.value);
            } else {
                url.searchParams.delete('kategori');
            }
            url.searchParams.set('page', '1');
            if (typeof window.refreshLiveTable === 'function') {
                window.refreshLiveTable(url.toString());
            } else {
                window.location.href = url.toString();
            }
        });
    }

    // Event Delegation: Open Modal Add / Edit / Delete
    document.addEventListener('click', function (e) {
        // Tombol Tambah Buku
        if (e.target.closest('#btnTambahBuku')) {
            openModalCreate();
        }

        // Tombol Edit Buku
        const btnEdit = e.target.closest('.btn-edit-buku');
        if (btnEdit) {
            const item = JSON.parse(btnEdit.getAttribute('data-item') || '{}');
            openModalEdit(item);
        }

        // Tombol Hapus Buku (SweetAlert2)
        const btnDelete = e.target.closest('.btn-delete-buku');
        if (btnDelete) {
            e.preventDefault();
            const formDel = btnDelete.closest('form');
            const judul = btnDelete.getAttribute('data-judul') || 'buku ini';

            Swal.fire({
                title: 'Konfirmasi Hapus',
                text: `Apakah Anda yakin ingin menghapus buku "${judul}"?`,
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

    function openModalCreate() {
        if (!modal || !form) return;
        modalTitle.textContent = 'Tambah Koleksi Buku';
        form.action = form.getAttribute('data-store-url') || window.location.pathname;
        formMethod.value = 'POST';
        form.reset();
        document.getElementById('buku_id').value = '';
        modal.style.display = 'flex';
    }

    function openModalEdit(item) {
        if (!modal || !form) return;
        modalTitle.textContent = 'Edit Data Buku';
        const updateBase = form.getAttribute('data-update-base') || window.location.pathname;
        form.action = `${updateBase}/${item.id}`;
        formMethod.value = 'PUT';

        document.getElementById('buku_id').value = item.id || '';
        document.getElementById('kode_buku').value = item.kode_buku || '';
        document.getElementById('isbn').value = item.isbn || '';
        document.getElementById('judul').value = item.judul || '';
        document.getElementById('penulis').value = item.penulis || '';
        document.getElementById('penerbit').value = item.penerbit || '';
        document.getElementById('tahun_terbit').value = item.tahun_terbit || '';
        document.getElementById('klasifikasi_ddc').value = item.klasifikasi_ddc || '';
        document.getElementById('kategori').value = item.kategori || 'Umum';
        document.getElementById('jumlah_eksemplar').value = item.jumlah_eksemplar || 1;
        document.getElementById('eksemplar_tersedia').value = item.eksemplar_tersedia ?? item.jumlah_eksemplar ?? 1;
        document.getElementById('lokasi_rak').value = item.lokasi_rak || '';

        modal.style.display = 'flex';
    }

    // Modal Close
    window.closeModalBuku = function () {
        if (modal) {
            modal.style.display = 'none';
        }
    };

    // Close on click outside
    if (modal) {
        modal.addEventListener('click', function (e) {
            if (e.target === modal) {
                closeModalBuku();
            }
        });
    }
});
