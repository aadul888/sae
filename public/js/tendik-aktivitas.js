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
    const selectTupoksi = document.getElementById('selectTupoksiPreset');
    const inputIndikatorId = document.getElementById('inputIndikatorId');
    const inputPegawaiPtk = document.getElementById('inputPegawaiPtk');
    const inputTanggal = document.getElementById('inputTanggal');
    const inputBidang = document.getElementById('inputBidang');
    const inputJamMulai = document.getElementById('inputJamMulai');
    const inputJamSelesai = document.getElementById('inputJamSelesai');
    const inputJudul = document.getElementById('inputJudul');
    const inputUraian = document.getElementById('inputUraian');
    const inputOutput = document.getElementById('inputOutput');
    const inputStatus = document.getElementById('inputStatus');

    // Data Templates Tupoksi
    let tupoksiTemplates = {};
    const templatesEl = document.getElementById('tupoksiTemplatesData');
    if (templatesEl) {
        try {
            tupoksiTemplates = JSON.parse(templatesEl.textContent);
        } catch (e) {
            console.error('Gagal parsing tupoksi templates', e);
        }
    }

    function filterIndikatorByBidang(bidangKey) {
        if (!inputIndikatorId) return;
        const opts = inputIndikatorId.querySelectorAll('option');
        opts.forEach(opt => {
            if (!opt.value) {
                opt.style.display = 'block';
                return;
            }
            const b = opt.getAttribute('data-bidang');
            if (!bidangKey || b === bidangKey || b === 'umum') {
                opt.style.display = 'block';
            } else {
                opt.style.display = 'none';
            }
        });
    }

    function populateTupoksiOptions(bidangKey) {
        if (!selectTupoksi) return;
        selectTupoksi.innerHTML = '<option value="">-- Pilih dari Rekomendasi Tupoksi Bidang (Opsional) --</option>';
        const list = tupoksiTemplates[bidangKey] || tupoksiTemplates['umum'] || [];
        list.forEach((item, index) => {
            const opt = document.createElement('option');
            opt.value = index;
            opt.textContent = item.judul;
            selectTupoksi.appendChild(opt);
        });
        filterIndikatorByBidang(bidangKey);
    }

    if (inputBidang) {
        inputBidang.addEventListener('change', function () {
            populateTupoksiOptions(this.value);
        });
    }

    if (inputIndikatorId) {
        inputIndikatorId.addEventListener('change', function () {
            const opt = this.options[this.selectedIndex];
            if (opt && opt.value) {
                const sasaran = opt.getAttribute('data-sasaran');
                if (inputJudul && !inputJudul.value.trim()) {
                    inputJudul.value = sasaran || '';
                }
            }
        });
    }

    if (selectTupoksi) {
        selectTupoksi.addEventListener('change', function () {
            const bidangKey = inputBidang ? inputBidang.value : 'umum';
            const list = tupoksiTemplates[bidangKey] || tupoksiTemplates['umum'] || [];
            const idx = parseInt(this.value, 10);
            if (!isNaN(idx) && list[idx]) {
                const item = list[idx];
                if (inputJudul) inputJudul.value = item.judul || '';
                if (inputUraian) inputUraian.value = item.uraian || '';
                if (inputOutput) inputOutput.value = item.output || '';
            }
        });
    }

    const defaultStoreUrl = form ? form.getAttribute('action') : '';

    function getNowTimeString() {
        const now = new Date();
        const h = String(now.getHours()).padStart(2, '0');
        const m = String(now.getMinutes()).padStart(2, '0');
        return `${h}:${m}`;
    }

    function getTodayDateString() {
        const now = new Date();
        const y = now.getFullYear();
        const m = String(now.getMonth() + 1).padStart(2, '0');
        const d = String(now.getDate()).padStart(2, '0');
        return `${y}-${m}-${d}`;
    }

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
        if (selectTupoksi) selectTupoksi.value = '';
        if (inputIndikatorId) inputIndikatorId.value = '';
    }

    function prepareCreateModal() {
        closeModal();
        if (inputTanggal) inputTanggal.value = getTodayDateString();
        if (inputJamMulai) inputJamMulai.value = getNowTimeString();
        if (inputJamSelesai) inputJamSelesai.value = '';
        if (inputPegawaiPtk) inputPegawaiPtk.value = '';
        if (inputStatus) inputStatus.value = 'selesai';
        if (inputIndikatorId) inputIndikatorId.value = '';
        if (inputBidang) populateTupoksiOptions(inputBidang.value);
        openModal();
    }

    if (btnTambah) {
        btnTambah.addEventListener('click', prepareCreateModal);
    }

    // Tombol tambah di empty state
    document.addEventListener('click', function (e) {
        if (e.target.closest('.btn-open-modal-empty')) {
            prepareCreateModal();
        }
    });

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
        const ptk = btnEdit.getAttribute('data-ptk');
        const indikator = btnEdit.getAttribute('data-indikator');

        if (form) {
            form.action = '/dashboard/tendik/aktivitas/' + id;
            methodOverride.innerHTML = '<input type="hidden" name="_method" value="PUT">';
        }

        if (modalTitle) {
            modalTitle.innerHTML = '<i class="fas fa-pen-to-square text-warning me-2"></i> Edit Aktivitas Harian';
        }

        if (inputTanggal) inputTanggal.value = tanggal || '';
        if (inputBidang) {
            inputBidang.value = bidang || 'umum';
            populateTupoksiOptions(inputBidang.value);
        }
        if (inputPegawaiPtk && ptk) inputPegawaiPtk.value = ptk;
        if (inputIndikatorId) inputIndikatorId.value = indikator || '';
        if (inputJamMulai) inputJamMulai.value = jamMulai || '';
        if (inputJamSelesai) inputJamSelesai.value = jamSelesai || '';
        if (inputJudul) inputJudul.value = judul || '';
        if (inputUraian) inputUraian.value = uraian || '';
        if (inputOutput) inputOutput.value = output || '';
        if (inputStatus) inputStatus.value = status || 'selesai';
        if (selectTupoksi) selectTupoksi.value = '';

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
    const filterIndikator = document.getElementById("filterIndikator");

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

        if (filterIndikator && filterIndikator.value) {
            url.searchParams.set("indikator_id", filterIndikator.value);
        } else {
            url.searchParams.delete("indikator_id");
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
    if (filterIndikator) filterIndikator.addEventListener("change", applyFilter);
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
