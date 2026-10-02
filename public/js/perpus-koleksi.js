/**
 * JavaScript Modul Perpustakaan - Koleksi & Katalog Buku
 * Sistem Aplikasi Edukasi (SAE)
 * Standar Baku SAE: Vanilla JS, Zero Framework, Modal Handling, High Resilience
 */

// 1. Global Modal Functions (Exposed immediately for inline handlers & event delegation)
window.openModalCreate = function () {
    const modal = document.getElementById('modalBuku');
    const form = document.getElementById('formBuku');
    const modalTitle = document.getElementById('modalBukuTitle') || document.getElementById('modalTitle');
    const formMethod = document.getElementById('bukuMethod') || document.getElementById('formMethod');

    if (!modal || !form) return;

    if (modalTitle) modalTitle.textContent = 'Tambah Koleksi Buku';
    if (formMethod) formMethod.value = 'POST';

    form.action = form.getAttribute('data-store-url') || window.location.pathname;
    form.reset();

    const idInput = document.getElementById('buku_id');
    if (idInput) idInput.value = '';

    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
};

window.openModalBuku = window.openModalCreate;

window.closeModalBuku = function () {
    const modal = document.getElementById('modalBuku');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = '';
    }
};

window.openModalEdit = function (item) {
    if (typeof item === 'string') {
        try {
            item = JSON.parse(item);
        } catch (e) {
            console.error('Gagal parsing data item buku', e);
            return;
        }
    }
    const modal = document.getElementById('modalBuku');
    const form = document.getElementById('formBuku');
    const modalTitle = document.getElementById('modalBukuTitle') || document.getElementById('modalTitle');
    const formMethod = document.getElementById('bukuMethod') || document.getElementById('formMethod');

    if (!modal || !form || !item) return;

    if (modalTitle) modalTitle.textContent = 'Edit Data Buku';
    if (formMethod) formMethod.value = 'PUT';

    const updateBase = form.getAttribute('data-update-base') || '/dashboard/perpustakaan/koleksi';
    form.action = `${updateBase}/${item.id}`;

    const setVal = (id, val) => {
        const el = document.getElementById(id);
        if (el) el.value = val !== undefined && val !== null ? val : '';
    };

    setVal('buku_id', item.id);
    setVal('buku_kode', item.kode_buku);
    setVal('kode_buku', item.kode_buku);
    setVal('buku_isbn', item.isbn);
    setVal('isbn', item.isbn);
    setVal('buku_judul', item.judul);
    setVal('judul', item.judul);
    setVal('buku_penulis', item.penulis);
    setVal('penulis', item.penulis);
    setVal('buku_penerbit', item.penerbit);
    setVal('penerbit', item.penerbit);
    setVal('buku_tahun', item.tahun_terbit);
    setVal('tahun_terbit', item.tahun_terbit);
    setVal('buku_ddc', item.klasifikasi_ddc);
    setVal('klasifikasi_ddc', item.klasifikasi_ddc);
    setVal('buku_kategori', item.kategori || 'Umum');
    setVal('kategori', item.kategori || 'Umum');
    setVal('buku_eksemplar', item.jumlah_eksemplar || 1);
    setVal('jumlah_eksemplar', item.jumlah_eksemplar || 1);
    setVal('buku_rak', item.lokasi_rak);
    setVal('lokasi_rak', item.lokasi_rak);

    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
};

window.editBuku = window.openModalEdit;

// 2. Event Delegation & Search Filters
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('modalBuku');

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

    // Close on click outside
    if (modal) {
        modal.addEventListener('click', function (e) {
            if (e.target === modal) {
                window.closeModalBuku();
            }
        });
    }
});

// Event Delegation for Button Clicks (Works even if DOM dynamically updates)
document.addEventListener('click', function (e) {
    // Tombol Tambah Buku
    if (e.target.closest('#btnTambahBuku')) {
        window.openModalCreate();
        return;
    }

    // Tombol Edit Buku
    const btnEdit = e.target.closest('.btn-edit-buku');
    if (btnEdit) {
        const itemAttr = btnEdit.getAttribute('data-item');
        if (itemAttr) {
            window.openModalEdit(itemAttr);
        }
        return;
    }

    // Tombol Hapus Buku (SweetAlert2)
    const btnDelete = e.target.closest('.btn-delete-buku');
    if (btnDelete) {
        e.preventDefault();
        const formDel = btnDelete.closest('form');
        const judul = btnDelete.getAttribute('data-judul') || 'buku ini';

        if (typeof Swal !== 'undefined') {
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
        } else {
            if (confirm(`Apakah Anda yakin ingin menghapus buku "${judul}"?`)) {
                formDel.submit();
            }
        }
    }
});
