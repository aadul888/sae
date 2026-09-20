/**
 * SAE — Log & Input Aktivitas Kerja Harian Tendik
 * JS Modular (Kepatuhan Rule #2: Zero inline script di Blade)
 */

document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('modalAktivitas');
    const modalTitle = document.getElementById('modalAktivitasTitle');
    const form = document.getElementById('formAktivitas');
    const methodOverride = document.getElementById('methodOverride');
    const btnTambah = document.getElementById('btnOpenModalTambah');
    const btnClose = document.getElementById('btnCloseModalAktivitas');
    const btnCancel = document.getElementById('btnCancelModalAktivitas');

    // Input Fields
    const inputTanggal = document.getElementById('inputTanggal');
    const inputBidang = document.getElementById('inputBidang');
    const inputJamMulai = document.getElementById('inputJamMulai');
    const inputJamSelesai = document.getElementById('inputJamSelesai');
    const inputJudul = document.getElementById('inputJudul');
    const inputUraian = document.getElementById('inputUraian');
    const inputOutput = document.getElementById('inputOutput');
    const inputStatus = document.getElementById('inputStatus');

    const defaultStoreUrl = form ? form.getAttribute('action') : '';

    function openModal() {
        if (!modal) return;
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        if (!modal) return;
        modal.style.display = 'none';
        document.body.style.overflow = '';
        if (form) {
            form.reset();
            form.action = defaultStoreUrl;
            methodOverride.innerHTML = '';
        }
        if (modalTitle) {
            modalTitle.innerHTML = '<i class="fas fa-pen-to-square text-primary me-2"></i> Catat Aktivitas Harian';
        }
    }

    if (btnTambah) {
        btnTambah.addEventListener('click', function () {
            closeModal();
            openModal();
        });
    }

    if (btnClose) {
        btnClose.addEventListener('click', closeModal);
    }

    if (btnCancel) {
        btnCancel.addEventListener('click', closeModal);
    }

    // Tutup saat klik di luar box modal
    if (modal) {
        modal.addEventListener('click', function (e) {
            if (e.target === modal) {
                closeModal();
            }
        });
    }

    // Tombol Edit Aktivitas (Event Delegation)
    document.addEventListener('click', function (e) {
        const btnEdit = e.target.closest('.btn-edit-aktivitas');
        if (!btnEdit) return;

        const id = btnEdit.getAttribute('data-id');
        const tanggal = btnEdit.getAttribute('data-tanggal');
        const jamMulai = btnEdit.getAttribute('data-jam_mulai');
        const jamSelesai = btnEdit.getAttribute('data-jam_selesai');
        const bidang = btnEdit.getAttribute('data-bidang');
        const judul = btnEdit.getAttribute('data-judul');
        const uraian = btnEdit.getAttribute('data-uraian');
        const output = btnEdit.getAttribute('data-output');
        const status = btnEdit.getAttribute('data-status');

        if (form) {
            form.action = '/dashboard/tendik/aktivitas/' + id;
            methodOverride.innerHTML = '<input type="hidden" name="_method" value="PUT">';
        }

        if (modalTitle) {
            modalTitle.innerHTML = '<i class="fas fa-pen-to-square text-warning me-2"></i> Edit Aktivitas Harian';
        }

        if (inputTanggal) inputTanggal.value = tanggal || '';
        if (inputBidang) inputBidang.value = bidang || 'umum';
        if (inputJamMulai) inputJamMulai.value = jamMulai || '';
        if (inputJamSelesai) inputJamSelesai.value = jamSelesai || '';
        if (inputJudul) inputJudul.value = judul || '';
        if (inputUraian) inputUraian.value = uraian || '';
        if (inputOutput) inputOutput.value = output || '';
        if (inputStatus) inputStatus.value = status || 'selesai';

        openModal();
    });

    // Form Delete Confirmation dengan SweetAlert2
    document.addEventListener('submit', function (e) {
        const formTarget = e.target;
        if (formTarget.getAttribute('data-confirm') === 'delete') {
            e.preventDefault();
            const itemName = formTarget.getAttribute('data-name') || 'aktivitas ini';

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Konfirmasi Hapus?',
                    text: `Apakah Anda yakin ingin menghapus "${itemName}"? Tindakan ini tidak dapat dibatalkan.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ef4444',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: '<i class="fas fa-trash-can me-1"></i> Ya, Hapus',
                    cancelButtonText: 'Batal',
                    reverseButtons: true,
                    customClass: {
                        popup: 'sae-swal-popup',
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        formTarget.removeAttribute('data-confirm');
                        formTarget.submit();
                    }
                });
            } else {
                if (confirm(`Hapus ${itemName}?`)) {
                    formTarget.removeAttribute('data-confirm');
                    formTarget.submit();
                }
            }
        }
    });

    // Datatable Realtime Live Search, Entri perPage, Filter, dan Sorting Standar SAE
    const searchInput = document.getElementById("liveSearch");
    const clearBtn = document.getElementById("clearSearch");
    const perPageSelect = document.getElementById("perPageSelect");
    const filterBulan = document.getElementById("filterBulan");
    const filterTahun = document.getElementById("filterTahun");
    const filterStatus = document.getElementById("filterStatus");
    const filterBidang = document.getElementById("filterBidang");

    function applyFilter() {
        const url = new URL(window.location.href);
        if (searchInput && searchInput.value.trim()) {
            url.searchParams.set("q", searchInput.value.trim());
        } else {
            url.searchParams.delete("q");
        }

        if (filterBulan && filterBulan.value) {
            url.searchParams.set("bulan", filterBulan.value);
        }

        if (filterTahun && filterTahun.value) {
            url.searchParams.set("tahun", filterTahun.value);
        }

        if (filterStatus && filterStatus.value) {
            url.searchParams.set("status", filterStatus.value);
        } else {
            url.searchParams.delete("status");
        }

        if (filterBidang && filterBidang.value) {
            url.searchParams.set("bidang", filterBidang.value);
        } else {
            url.searchParams.delete("bidang");
        }

        if (perPageSelect && perPageSelect.value) {
            url.searchParams.set("perPage", perPageSelect.value);
        }

        url.searchParams.set("page", "1");
        if (typeof window.refreshLiveTable === "function") {
            window.refreshLiveTable(url.toString());
        } else {
            window.location.href = url.toString();
        }
    }

    if (searchInput) {
        let timer = null;
        searchInput.addEventListener("input", function () {
            if (clearBtn) clearBtn.classList.toggle("visible", this.value.trim().length > 0);
            clearTimeout(timer);
            timer = setTimeout(applyFilter, 300);
        });

        searchInput.addEventListener("keydown", function (e) {
            if (e.key === "Enter") {
                e.preventDefault();
                clearTimeout(timer);
                applyFilter();
            }
        });
    }

    if (clearBtn) {
        clearBtn.addEventListener("click", function () {
            if (searchInput) {
                searchInput.value = "";
                clearBtn.classList.remove("visible");
                applyFilter();
            }
        });
    }

    if (filterBulan) filterBulan.addEventListener("change", applyFilter);
    if (filterTahun) filterTahun.addEventListener("change", applyFilter);
    if (filterStatus) filterStatus.addEventListener("change", applyFilter);
    if (filterBidang) filterBidang.addEventListener("change", applyFilter);
    if (perPageSelect) perPageSelect.addEventListener("change", applyFilter);

    document.querySelectorAll(".sortable-th").forEach(function (th) {
        th.style.cursor = "pointer";
        th.addEventListener("click", function () {
            const sortField = this.getAttribute("data-sort");
            if (!sortField) return;

            const url = new URL(window.location.href);
            const currentSort = url.searchParams.get("sort") || "tanggal";
            const currentDir = url.searchParams.get("sort_dir") || url.searchParams.get("dir") || "desc";

            let newDir = "asc";
            if (currentSort === sortField && currentDir === "asc") {
                newDir = "desc";
            }

            url.searchParams.set("sort", sortField);
            url.searchParams.set("sort_dir", newDir);
            if (typeof window.refreshLiveTable === "function") {
                window.refreshLiveTable(url.toString());
            } else {
                window.location.href = url.toString();
            }
        });
    });
});
