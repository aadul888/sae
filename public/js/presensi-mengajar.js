/**
 * Modul JavaScript: Presensi Mengajar (SAE Standardized)
 * Menggunakan SweetAlert2 dan event delegation terpisah dari Blade.
 */
document.addEventListener('DOMContentLoaded', () => {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    // Modal elements
    const modalForm = document.getElementById('modalFormPresensi');
    const modalDetail = document.getElementById('modalDetailPresensi');
    const btnOpenCreate = document.getElementById('btnOpenCreateModal');
    const formPresensi = document.getElementById('formPresensiMengajar');
    const modalTitle = document.getElementById('modalPresensiTitle');
    const presensiId = document.getElementById('presensiId');
    const wrapGuruPengganti = document.getElementById('wrapGuruPengganti');
    const wrapWaktuKbm = document.getElementById('wrapWaktuKbm');
    const textWaktuKbm = document.getElementById('textWaktuKbm');
    const badgeHariJadwal = document.getElementById('badgeHariJadwal');
    const labelDurasiJp = document.getElementById('labelDurasiJp');

    // Form inputs
    const selectJadwalKbm = document.getElementById('selectJadwalKbm');
    const inputJadwalKbmId = document.getElementById('inputJadwalKbmId');
    const inputRombel = document.getElementById('inputRombonganBelajarId');
    const inputMapel = document.getElementById('inputNamaMataPelajaran');
    const inputPembelajaranId = document.getElementById('inputPembelajaranId');
    const inputMataPelajaranId = document.getElementById('inputMataPelajaranId');
    const inputTanggal = document.getElementById('inputTanggal');
    const inputJamKeMulai = document.getElementById('inputJamKeMulai');
    const inputJamKeSelesai = document.getElementById('inputJamKeSelesai');
    const inputJamMasuk = document.getElementById('inputJamMasuk');
    const inputJamKeluar = document.getElementById('inputJamKeluar');
    const inputGuruPengganti = document.getElementById('inputNamaGuruPengganti');
    const inputKeterangan = document.getElementById('inputKeterangan');
    const btnSavePresensi = document.getElementById('btnSavePresensi');

    // Filter & Search elements
    const liveSearchInput = document.getElementById('liveSearchInput');
    const clearSearchBtn = document.querySelector('.clear-search');
    const filterTanggalMulai = document.getElementById('filterTanggalMulai');
    const filterTanggalSelesai = document.getElementById('filterTanggalSelesai');
    const filterRombel = document.getElementById('filterRombel');
    const filterStatus = document.getElementById('filterStatus');
    const filterPtk = document.getElementById('filterPtk');
    const perPageSelect = document.getElementById('perPageSelect');
    const btnResetFilter = document.getElementById('btnResetFilter');
    const tableDataContainer = document.getElementById('tableDataContainer');

    let currentSort = 'tanggal';
    let currentSortDir = 'desc';

    // Toast helper SweetAlert2
    const showToast = (message, type = 'success') => {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: type === 'danger' ? 'error' : type,
                title: message,
                showConfirmButton: false,
                timer: 3000
            });
        }
    };

    // Banner helper waktu KBM
    const applyWaktuKbm = (waktuRange, hari, durasiJp) => {
        if (wrapWaktuKbm && textWaktuKbm) {
            if (waktuRange) {
                textWaktuKbm.textContent = `${waktuRange} (${durasiJp || 1} JP)`;
                if (badgeHariJadwal) badgeHariJadwal.textContent = hari || 'Jadwal';
                wrapWaktuKbm.style.display = 'flex';
            } else {
                wrapWaktuKbm.style.display = 'none';
            }
        }
        if (labelDurasiJp && durasiJp) {
            labelDurasiJp.textContent = `${durasiJp} JP`;
            labelDurasiJp.style.display = 'inline';
        }
    };

    const resetWaktuKbm = () => {
        if (wrapWaktuKbm) wrapWaktuKbm.style.display = 'none';
        if (labelDurasiJp) {
            labelDurasiJp.textContent = '- JP';
            labelDurasiJp.style.display = 'none';
        }
    };

    // Toggle wrap guru pengganti dan update active class pada segmented pill item
    const checkStatusRadio = () => {
        const checkedRadio = document.querySelector('input[name="status"]:checked');
        const checkedVal = checkedRadio?.value || 'H';

        document.querySelectorAll('.status-pill-item').forEach(item => {
            const input = item.querySelector('input[type="radio"]');
            if (input && input.checked) {
                item.classList.add('active');
            } else {
                item.classList.remove('active');
            }
        });

        if (wrapGuruPengganti) {
            wrapGuruPengganti.style.display = (checkedVal === 'D') ? 'block' : 'none';
        }
    };

    document.querySelectorAll('input[name="status"]').forEach(radio => {
        radio.addEventListener('change', checkStatusRadio);
    });

    // Handle auto-fill dari select jadwal KBM
    if (selectJadwalKbm) {
        selectJadwalKbm.addEventListener('change', () => {
            const opt = selectJadwalKbm.selectedOptions[0];
            if (!opt || !opt.value) {
                if (inputJadwalKbmId) inputJadwalKbmId.value = '';
                resetWaktuKbm();
                return;
            }

            if (inputJadwalKbmId) inputJadwalKbmId.value = opt.value;
            if (inputRombel && opt.dataset.rombelId) inputRombel.value = opt.dataset.rombelId;
            if (inputMapel && opt.dataset.mapel) inputMapel.value = opt.dataset.mapel;
            if (inputPembelajaranId) inputPembelajaranId.value = opt.dataset.pembelajaranId || '';
            if (inputMataPelajaranId) inputMataPelajaranId.value = opt.dataset.mapelId || '';
            if (inputJamKeMulai && opt.dataset.jamMulai) inputJamKeMulai.value = opt.dataset.jamMulai;
            if (inputJamKeSelesai && opt.dataset.jamSelesai) inputJamKeSelesai.value = opt.dataset.jamSelesai;

            // Jam masuk & selesai otomatis dari jadwal
            if (inputJamMasuk && opt.dataset.jamMasuk) {
                inputJamMasuk.value = opt.dataset.jamMasuk;
            }
            if (inputJamKeluar && opt.dataset.jamKeluar) {
                inputJamKeluar.value = opt.dataset.jamKeluar;
            }

            applyWaktuKbm(opt.dataset.jamWaktu, opt.dataset.hari, opt.dataset.durasiJp);
        });
    }

    // Buka Modal Create Baru
    const openCreateModal = () => {
        if (formPresensi) formPresensi.reset();
        if (presensiId) presensiId.value = '';
        if (inputJadwalKbmId) inputJadwalKbmId.value = '';
        if (inputPembelajaranId) inputPembelajaranId.value = '';
        if (inputMataPelajaranId) inputMataPelajaranId.value = '';
        if (selectJadwalKbm) selectJadwalKbm.value = '';
        resetWaktuKbm();

        // Reset radio ke Hadir (H)
        const radioH = document.querySelector('input[name="status"][value="H"]');
        if (radioH) radioH.checked = true;
        checkStatusRadio();

        // Set waktu sekarang
        const now = new Date();
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        if (inputJamMasuk) inputJamMasuk.value = `${hours}:${minutes}`;
        if (inputJamKeluar) inputJamKeluar.value = '';

        if (modalTitle) {
            modalTitle.innerHTML = '<i class="fas fa-calendar-check text-primary"></i> Catat Presensi Mengajar';
        }
        if (modalForm) modalForm.style.display = 'flex';
    };

    if (btnOpenCreate) {
        btnOpenCreate.addEventListener('click', openCreateModal);
    }

    // Quick Check-in Hari Ini (Klik tombol Presensi pada Kartu Jadwal)
    document.querySelectorAll('.btn-quick-checkin').forEach(btn => {
        btn.addEventListener('click', () => {
            openCreateModal();
            const d = btn.dataset;
            if (inputJadwalKbmId) inputJadwalKbmId.value = d.jadwalId || '';
            if (selectJadwalKbm && d.jadwalId) selectJadwalKbm.value = d.jadwalId;
            if (inputRombel && d.rombelId) inputRombel.value = d.rombelId;
            if (inputMapel && d.mapel) inputMapel.value = d.mapel;
            if (inputPembelajaranId) inputPembelajaranId.value = d.pembelajaranId || '';
            if (inputMataPelajaranId) inputMataPelajaranId.value = d.mapelId || '';
            if (inputJamKeMulai && d.jamMulai) inputJamKeMulai.value = d.jamMulai;
            if (inputJamKeSelesai && d.jamSelesai) inputJamKeSelesai.value = d.jamSelesai;

            // Jam masuk & selesai otomatis dari jadwal KBM
            if (inputJamMasuk && d.jamMasuk) {
                inputJamMasuk.value = d.jamMasuk;
            }
            if (inputJamKeluar && d.jamKeluar) {
                inputJamKeluar.value = d.jamKeluar;
            }

            applyWaktuKbm(d.jamWaktu, d.hari, d.durasiJp);
        });
    });

    // Tutup Modals
    const closeModals = () => {
        if (modalForm) modalForm.style.display = 'none';
        if (modalDetail) modalDetail.style.display = 'none';
    };

    document.querySelectorAll('.btn-close-modal, .btn-close-detail').forEach(btn => {
        btn.addEventListener('click', closeModals);
    });

    [modalForm, modalDetail].forEach(modal => {
        if (modal) {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) closeModals();
            });
        }
    });

    // Refresh Live Table Asinkron Baku SAE
    window.refreshLiveTable = (url = null) => {
        if (!tableDataContainer) return;

        let targetUrl = url;
        if (!targetUrl) {
            const params = new URLSearchParams();
            if (liveSearchInput?.value) params.set('q', liveSearchInput.value.trim());
            if (filterTanggalMulai?.value) params.set('tanggal_mulai', filterTanggalMulai.value);
            if (filterTanggalSelesai?.value) params.set('tanggal_selesai', filterTanggalSelesai.value);
            if (filterRombel?.value) params.set('rombongan_belajar_id', filterRombel.value);
            if (filterStatus?.value) params.set('status', filterStatus.value);
            if (filterPtk?.value) params.set('filter_ptk_id', filterPtk.value);
            if (perPageSelect?.value) params.set('per_page', perPageSelect.value);
            params.set('sort', currentSort);
            params.set('sort_dir', currentSortDir);
            params.set('ajax_table', '1');
            targetUrl = `${window.location.pathname}?${params.toString()}`;
        } else {
            const separator = targetUrl.includes('?') ? '&' : '?';
            targetUrl = `${targetUrl}${separator}ajax_table=1`;
        }

        tableDataContainer.style.opacity = '0.5';

        fetch(targetUrl, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'text/html'
            }
        })
        .then(res => res.text())
        .then(html => {
            tableDataContainer.innerHTML = html;
            tableDataContainer.style.opacity = '1';
            bindTableEvents();
        })
        .catch(err => {
            console.error('Error refreshing table:', err);
            tableDataContainer.style.opacity = '1';
        });
    };

    // Binding event ke elemen datatable (Sorting & Pagination)
    const bindTableEvents = () => {
        // Sortable Headers
        document.querySelectorAll('.sortable-th').forEach(th => {
            th.addEventListener('click', () => {
                const sortKey = th.dataset.sort;
                if (currentSort === sortKey) {
                    currentSortDir = currentSortDir === 'asc' ? 'desc' : 'asc';
                } else {
                    currentSort = sortKey;
                    currentSortDir = 'asc';
                }
                window.refreshLiveTable();
            });
        });

        // Ajax pagination links
        document.querySelectorAll('.ajax-page-link').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                window.refreshLiveTable(link.getAttribute('href'));
            });
        });
    };

    bindTableEvents();

    // Live Search with Debounce
    let searchTimeout = null;
    if (liveSearchInput) {
        liveSearchInput.addEventListener('input', () => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                window.refreshLiveTable();
            }, 350);
        });
    }

    if (clearSearchBtn) {
        clearSearchBtn.addEventListener('click', () => {
            if (liveSearchInput) liveSearchInput.value = '';
            window.refreshLiveTable();
        });
    }

    // Filter Change Listeners
    [filterTanggalMulai, filterTanggalSelesai, filterRombel, filterStatus, filterPtk, perPageSelect].forEach(el => {
        if (el) {
            el.addEventListener('change', () => {
                window.refreshLiveTable();
            });
        }
    });

    if (btnResetFilter) {
        btnResetFilter.addEventListener('click', () => {
            if (liveSearchInput) liveSearchInput.value = '';
            if (filterTanggalMulai) filterTanggalMulai.value = '';
            if (filterTanggalSelesai) filterTanggalSelesai.value = '';
            if (filterRombel) filterRombel.value = '';
            if (filterStatus) filterStatus.value = '';
            if (filterPtk) filterPtk.value = '';
            if (perPageSelect) perPageSelect.value = '15';
            currentSort = 'tanggal';
            currentSortDir = 'desc';
            window.refreshLiveTable();
        });
    }

    // Submit Form Simpan / Update Presensi Mengajar
    if (formPresensi) {
        formPresensi.addEventListener('submit', (e) => {
            e.preventDefault();

            const isEdit = presensiId && presensiId.value !== '';
            const url = isEdit ? `/dashboard/presensi-mengajar/${presensiId.value}` : '/dashboard/presensi-mengajar';
            const method = isEdit ? 'PUT' : 'POST';

            const formData = new FormData(formPresensi);
            const payload = {};
            formData.forEach((val, key) => {
                payload[key] = val;
            });

            if (btnSavePresensi) {
                btnSavePresensi.disabled = true;
                btnSavePresensi.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Menyimpan...';
            }

            fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payload)
            })
            .then(res => res.json())
            .then(data => {
                if (btnSavePresensi) {
                    btnSavePresensi.disabled = false;
                    btnSavePresensi.innerHTML = '<i class="fas fa-check me-1"></i> Simpan';
                }

                if (data.status === 'success') {
                    closeModals();
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: data.message,
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => {
                            window.location.reload();
                        });
                    } else {
                        window.location.reload();
                    }
                } else {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal Menyimpan',
                            text: data.message || 'Terjadi kesalahan validasi.'
                        });
                    } else {
                        alert(data.message || 'Gagal menyimpan.');
                    }
                }
            })
            .catch(err => {
                console.error('Error submit presensi:', err);
                if (btnSavePresensi) {
                    btnSavePresensi.disabled = false;
                    btnSavePresensi.innerHTML = '<i class="fas fa-check me-1"></i> Simpan';
                }
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Kesalahan Sistem',
                        text: 'Terjadi kesalahan saat memproses data presensi.'
                    });
                }
            });
        });
    }

    // Event Delegation: Tombol Edit Row
    document.addEventListener('click', (e) => {
        const editBtn = e.target.closest('.btn-edit-row');
        if (!editBtn) return;

        const id = editBtn.dataset.id;
        if (!id) return;

        fetch(`/dashboard/presensi-mengajar/${id}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(res => res.json())
        .then(res => {
            if (res.status !== 'success') {
                showToast(res.message || 'Gagal mengambil data', 'error');
                return;
            }

            const d = res.data;
            if (presensiId) presensiId.value = d.id;
            if (inputJadwalKbmId) inputJadwalKbmId.value = d.jadwal_kbm_id || '';
            if (selectJadwalKbm) selectJadwalKbm.value = d.jadwal_kbm_id || '';
            if (inputRombel) inputRombel.value = d.rombongan_belajar_id;
            if (inputMapel) inputMapel.value = d.nama_mata_pelajaran;
            if (inputTanggal) inputTanggal.value = d.tanggal;
            if (inputJamKeMulai) inputJamKeMulai.value = d.jam_ke_mulai;
            if (inputJamKeSelesai) inputJamKeSelesai.value = d.jam_ke_selesai;
            if (inputJamMasuk) inputJamMasuk.value = d.jam_masuk ? d.jam_masuk.substring(0, 5) : '';
            if (inputJamKeluar) inputJamKeluar.value = d.jam_keluar ? d.jam_keluar.substring(0, 5) : '';
            if (inputGuruPengganti) inputGuruPengganti.value = d.nama_guru_pengganti ?? '';
            if (inputKeterangan) inputKeterangan.value = d.keterangan ?? '';

            if (d.jam_masuk && d.jam_keluar) {
                applyWaktuKbm(`${d.jam_masuk.substring(0, 5)} - ${d.jam_keluar.substring(0, 5)}`, d.hari, d.total_jp);
            } else {
                resetWaktuKbm();
            }

            // Set radio status
            const statusRadio = document.querySelector(`input[name="status"][value="${d.status}"]`);
            if (statusRadio) statusRadio.checked = true;
            checkStatusRadio();

            if (modalTitle) {
                modalTitle.innerHTML = `<i class="fas fa-pen-to-square text-warning"></i> Edit Presensi: ${d.nama_mata_pelajaran}`;
            }

            if (modalForm) modalForm.style.display = 'flex';
        })
        .catch(err => {
            console.error('Error fetching detail for edit:', err);
            showToast('Gagal mengambil data presensi.', 'error');
        });
    });

    // Event Delegation: Tombol Detail Row
    document.addEventListener('click', (e) => {
        const detailBtn = e.target.closest('.btn-detail-row');
        if (!detailBtn) return;

        const id = detailBtn.dataset.id;
        if (!id) return;

        fetch(`/dashboard/presensi-mengajar/${id}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(res => res.json())
        .then(res => {
            if (res.status !== 'success') return;
            const d = res.data;
            const container = document.getElementById('detailPresensiContent');
            if (!container) return;

            container.innerHTML = `
                <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); padding-bottom: 8px;">
                    <span style="color: var(--text-muted); font-size: 0.8rem;">Tanggal KBM</span>
                    <strong style="color: var(--text-color); font-size: 0.9rem;">${d.hari}, ${d.tanggal_formatted}</strong>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); padding-bottom: 8px;">
                    <span style="color: var(--text-muted); font-size: 0.8rem;">Kelas / Rombel</span>
                    <strong style="color: var(--text-color); font-size: 0.9rem;">${d.nama_rombel}</strong>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); padding-bottom: 8px;">
                    <span style="color: var(--text-muted); font-size: 0.8rem;">Mata Pelajaran</span>
                    <strong style="color: var(--text-color); font-size: 0.9rem;">${d.nama_mata_pelajaran}</strong>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); padding-bottom: 8px;">
                    <span style="color: var(--text-muted); font-size: 0.8rem;">Jam KBM</span>
                    <strong style="color: var(--text-color); font-size: 0.9rem;">Jam ke-${d.jam_ke_mulai} s.d ${d.jam_ke_selesai} (${d.total_jp} JP)</strong>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); padding-bottom: 8px;">
                    <span style="color: var(--text-muted); font-size: 0.8rem;">Status Kehadiran</span>
                    <div>${d.status_badge}</div>
                </div>
                ${d.status === 'D' && d.nama_guru_pengganti ? `
                    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); padding-bottom: 8px;">
                        <span style="color: var(--text-muted); font-size: 0.8rem;">Guru Pengganti</span>
                        <strong style="color: #ef4444;">${d.nama_guru_pengganti}</strong>
                    </div>
                ` : ''}
                <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); padding-bottom: 8px;">
                    <span style="color: var(--text-muted); font-size: 0.8rem;">Waktu Presensi</span>
                    <span style="color: var(--text-color); font-size: 0.86rem;">Masuk: <strong>${d.jam_masuk ? d.jam_masuk.substring(0, 5) : '-'}</strong> | Selesai: <strong>${d.jam_keluar ? d.jam_keluar.substring(0, 5) : '-'}</strong></span>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); padding-bottom: 8px;">
                    <span style="color: var(--text-muted); font-size: 0.8rem;">Kehadiran Siswa</span>
                    <span style="color: var(--text-color); font-size: 0.86rem;">Hadir: <strong style="color: #10b981;">${d.jumlah_siswa_hadir ?? '-'}</strong> | Absen: <strong style="color: #ef4444;">${d.jumlah_siswa_tidak_hadir ?? '-'}</strong></span>
                </div>
                ${d.keterangan ? `
                    <div style="margin-top: 4px; padding: 8px 12px; background: var(--bg-hover); border-radius: 8px;">
                        <span style="color: var(--text-muted); font-size: 0.76rem; display: block; margin-bottom: 2px;">Keterangan / Catatan:</span>
                        <div style="color: var(--text-color); font-size: 0.84rem;">${d.keterangan}</div>
                    </div>
                ` : ''}
                ${d.agenda ? `
                    <div style="margin-top: 6px; padding: 10px 12px; background: rgba(99,102,241,0.06); border: 1px solid rgba(99,102,241,0.2); border-radius: 8px;">
                        <span style="color: var(--primary); font-size: 0.78rem; font-weight: 700; display: block; margin-bottom: 2px;">
                            <i class="fas fa-book-open me-1"></i> Terhubung ke Jurnal KBM (Pertemuan ${d.agenda.pertemuan_ke}):
                        </span>
                        <div style="font-weight: 600; color: var(--text-color); font-size: 0.84rem;">${d.agenda.materi_pokok}</div>
                    </div>
                ` : ''}
            `;

            if (modalDetail) modalDetail.style.display = 'flex';
        });
    });

    // Event Delegation: Tombol Delete Row (SweetAlert2)
    document.addEventListener('click', (e) => {
        const delBtn = e.target.closest('.btn-delete-row');
        if (!delBtn) return;

        const id = delBtn.dataset.id;
        const name = delBtn.dataset.name || 'Data';
        if (!id) return;

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Hapus Catatan Presensi?',
                html: `Apakah Anda yakin ingin menghapus presensi mengajar <strong>${name}</strong>? Data jurnal yang terhubung akan dilepaskan relasinya.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Ya, Hapus Data',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch(`/dashboard/presensi-mengajar/${id}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        }
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Terhapus!',
                                text: data.message,
                                timer: 1500,
                                showConfirmButton: false
                            });
                            window.refreshLiveTable();
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal',
                                text: data.message || 'Tidak dapat menghapus data.'
                            });
                        }
                    })
                    .catch(() => {
                        Swal.fire({
                            icon: 'error',
                            title: 'Kesalahan',
                            text: 'Terjadi kesalahan sistem saat menghapus data.'
                        });
                    });
                }
            });
        }
    });
});