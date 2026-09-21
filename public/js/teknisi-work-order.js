/**
 * JavaScript Modul Teknisi - Work Order & Tiket Perbaikan
 * Sistem Aplikasi Edukasi (SAE)
 */

document.addEventListener('DOMContentLoaded', function () {
    const modalCreateWO = document.getElementById('modalCreateWO');
    const modalUpdateWO = document.getElementById('modalUpdateWO');
    const formUpdateWO = document.getElementById('formUpdateWO');
    const updateNomorWO = document.getElementById('updateNomorWO');
    const updateDeskripsiWO = document.getElementById('updateDeskripsiWO');

    // Live search & debounce
    const searchInput = document.getElementById('searchWorkOrder');
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

    // Filter Urgensi
    const filterUrgensi = document.getElementById('filterUrgensi');
    if (filterUrgensi) {
        filterUrgensi.addEventListener('change', function () {
            const url = new URL(window.location.href);
            if (this.value) {
                url.searchParams.set('urgensi', this.value);
            } else {
                url.searchParams.delete('urgensi');
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
        // Tombol Buat Work Order
        if (e.target.closest('#btnBuatWorkOrder')) {
            if (modalCreateWO) modalCreateWO.style.display = 'flex';
        }

    // Auto-fill aset Sarpras ke deskripsi kerusakan
    const selectAsetTeknisi = document.getElementById('selectAsetTeknisi');
    const deskripsiKerusakan = document.querySelector('textarea[name="deskripsi_kerusakan"]');
    if (selectAsetTeknisi) {
        selectAsetTeknisi.addEventListener('change', function () {
            if (this.value && deskripsiKerusakan) {
                if (!deskripsiKerusakan.value.includes(this.value)) {
                    deskripsiKerusakan.value = `[Kerusakan Aset: ${this.value}] ` + deskripsiKerusakan.value;
                }
            }
        });
    }

        // Tombol Update Status WO
        const btnUpdate = e.target.closest('.btn-update-wo');
        if (btnUpdate) {
            const item = JSON.parse(btnUpdate.getAttribute('data-item') || '{}');
            if (modalUpdateWO && formUpdateWO) {
                formUpdateWO.action = `/dashboard/teknisi/work-order/${item.id}/status`;
                if (updateNomorWO) updateNomorWO.textContent = item.nomor_wo;
                if (updateDeskripsiWO) updateDeskripsiWO.textContent = `${item.lokasi_unit} - ${item.deskripsi_kerusakan}`;

                const statusVal = document.getElementById('updateStatusVal');
                const teknisiVal = document.getElementById('updateTeknisiVal');
                const tindakanVal = document.getElementById('updateTindakanVal');
                const biayaVal = document.getElementById('updateBiayaVal');
                const tglSelesaiVal = document.getElementById('updateTglSelesaiVal');

                if (statusVal) statusVal.value = item.status || 'antrean';
                if (teknisiVal) teknisiVal.value = item.teknisi_ptk_id || '';
                if (tindakanVal) tindakanVal.value = item.tindakan_perbaikan || '';
                if (biayaVal) biayaVal.value = Math.round(item.estimasi_biaya_part || 0);
                if (tglSelesaiVal) tglSelesaiVal.value = item.tgl_selesai ? item.tgl_selesai.substring(0, 10) : '';

                modalUpdateWO.style.display = 'flex';
            }
        }

        // Tombol Hapus WO (SweetAlert2)
        const btnDelete = e.target.closest('.btn-delete-wo');
        if (btnDelete) {
            e.preventDefault();
            const formDel = btnDelete.closest('form');
            const nomor = btnDelete.getAttribute('data-nomor') || 'WO ini';

            Swal.fire({
                title: 'Hapus Work Order?',
                text: `Apakah Anda yakin ingin menghapus tiket Work Order "${nomor}"?`,
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

    window.closeModalCreateWO = function () {
        if (modalCreateWO) modalCreateWO.style.display = 'none';
    };

    window.closeModalUpdateWO = function () {
        if (modalUpdateWO) modalUpdateWO.style.display = 'none';
    };

    // Close on click outside
    if (modalCreateWO) {
        modalCreateWO.addEventListener('click', function (e) {
            if (e.target === modalCreateWO) closeModalCreateWO();
        });
    }
    if (modalUpdateWO) {
        modalUpdateWO.addEventListener('click', function (e) {
            if (e.target === modalUpdateWO) closeModalUpdateWO();
        });
    }
});
