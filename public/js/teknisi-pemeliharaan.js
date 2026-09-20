/**
 * JavaScript Modul Teknisi - Pemeliharaan Preventif (Maintenance Routine)
 * Sistem Aplikasi Edukasi (SAE)
 */

document.addEventListener('DOMContentLoaded', function () {
    const modalCreatePM = document.getElementById('modalCreatePM');
    const modalUpdatePM = document.getElementById('modalUpdatePM');
    const formUpdatePM = document.getElementById('formUpdatePM');
    const updateNamaPM = document.getElementById('updateNamaPM');

    // Live search & debounce
    const searchInput = document.getElementById('searchPM');
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
    const filterKategori = document.getElementById('filterKategoriPM');
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

    // Filter Frekuensi
    const filterFrekuensi = document.getElementById('filterFrekuensi');
    if (filterFrekuensi) {
        filterFrekuensi.addEventListener('change', function () {
            const url = new URL(window.location.href);
            if (this.value) {
                url.searchParams.set('frekuensi', this.value);
            } else {
                url.searchParams.delete('frekuensi');
            }
            url.searchParams.set('page', '1');
            if (typeof window.refreshLiveTable === 'function') {
                window.refreshLiveTable(url.toString());
            } else {
                window.location.href = url.toString();
            }
        });
    }

    // Filter Status
    const filterStatus = document.getElementById('filterStatusPM');
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

    // Event Delegation: Create / Update / Delete
    document.addEventListener('click', function (e) {
        // Tombol Tambah Jadwal PM
        if (e.target.closest('#btnTambahJadwalPM')) {
            if (modalCreatePM) modalCreatePM.style.display = 'flex';
        }

        // Tombol Update Status / Realisasi PM
        const btnUpdate = e.target.closest('.btn-update-pm');
        if (btnUpdate) {
            const item = JSON.parse(btnUpdate.getAttribute('data-item') || '{}');
            if (modalUpdatePM && formUpdatePM) {
                formUpdatePM.action = `/dashboard/teknisi/pemeliharaan/${item.id}/status`;
                if (updateNamaPM) updateNamaPM.textContent = `[${item.kode_pemeliharaan}] ${item.nama_kegiatan}`;

                const statusVal = document.getElementById('updateStatusPMVal');
                const tglRealisasiVal = document.getElementById('updateTglRealisasiVal');
                const catatanVal = document.getElementById('updateCatatanHasilVal');
                const biayaVal = document.getElementById('updateBiayaPMVal');

                if (statusVal) statusVal.value = item.status || 'terjadwal';
                if (tglRealisasiVal) tglRealisasiVal.value = item.tgl_realisasi ? item.tgl_realisasi.substring(0, 10) : '';
                if (catatanVal) catatanVal.value = item.catatan_hasil || '';
                if (biayaVal) biayaVal.value = Math.round(item.biaya || 0);

                modalUpdatePM.style.display = 'flex';
            }
        }

        // Tombol Hapus PM (SweetAlert2)
        const btnDelete = e.target.closest('.btn-delete-pm');
        if (btnDelete) {
            e.preventDefault();
            const formDel = btnDelete.closest('form');
            const nama = btnDelete.getAttribute('data-nama') || 'jadwal ini';

            Swal.fire({
                title: 'Hapus Jadwal PM?',
                text: `Apakah Anda yakin ingin menghapus jadwal pemeliharaan "${nama}"?`,
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

    window.closeModalCreatePM = function () {
        if (modalCreatePM) modalCreatePM.style.display = 'none';
    };

    window.closeModalUpdatePM = function () {
        if (modalUpdatePM) modalUpdatePM.style.display = 'none';
    };

    // Close on click outside
    if (modalCreatePM) {
        modalCreatePM.addEventListener('click', function (e) {
            if (e.target === modalCreatePM) closeModalCreatePM();
        });
    }
    if (modalUpdatePM) {
        modalUpdatePM.addEventListener('click', function (e) {
            if (e.target === modalUpdatePM) closeModalUpdatePM();
        });
    }
});
