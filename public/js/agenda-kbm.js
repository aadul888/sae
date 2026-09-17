/**
 * Modul JavaScript: Jurnal & Agenda KBM (SAE Standardized)
 * Menggunakan SweetAlert2 dan event delegation terpisah dari Blade.
 */
document.addEventListener('DOMContentLoaded', () => {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    // Modal elements
    const modalForm = document.getElementById('modalFormAgenda');
    const modalDetail = document.getElementById('modalDetailAgenda');
    const btnOpenCreate = document.getElementById('btnOpenCreateAgenda');
    const formAgenda = document.getElementById('formAgendaKbm');
    const modalTitle = document.getElementById('modalAgendaTitle');
    const agendaId = document.getElementById('agendaId');
    const wrapJadwalSelector = document.getElementById('wrapJadwalSelector');

    // Form inputs
    const selectJadwalKbm = document.getElementById('selectJadwalKbm');
    const inputJadwalKbmId = document.getElementById('inputJadwalKbmId');
    const inputPembelajaranId = document.getElementById('inputPembelajaranId');
    const inputMataPelajaranId = document.getElementById('inputMataPelajaranId');
    const inputRombel = document.getElementById('inputRombonganBelajarId');
    const inputMapel = document.getElementById('inputNamaMataPelajaran');
    const inputTanggal = document.getElementById('inputTanggal');
    const inputPertemuanKe = document.getElementById('inputPertemuanKe');
    const inputStatusKbm = document.getElementById('inputStatusKbm');
    const inputJamKeMulai = document.getElementById('inputJamKeMulai');
    const inputJamKeSelesai = document.getElementById('inputJamKeSelesai');
    const inputMateriPokok = document.getElementById('inputMateriPokok');
    const inputUraianKegiatan = document.getElementById('inputUraianKegiatan');
    const inputPenugasan = document.getElementById('inputPenugasan');
    const inputHambatanCatatan = document.getElementById('inputHambatanCatatan');
    const btnSaveAgenda = document.getElementById('btnSaveAgenda');

    // Filter & Search elements
    const liveSearchInput = document.getElementById('liveSearchInput');
    const clearSearchBtn = document.querySelector('.clear-search');
    const filterTanggalMulai = document.getElementById('filterTanggalMulai');
    const filterTanggalSelesai = document.getElementById('filterTanggalSelesai');
    const filterRombel = document.getElementById('filterRombel');
    const filterStatusKbm = document.getElementById('filterStatusKbm');
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

    // Fungsi fetch nomor pertemuan ke berikutnya otomatis
    const fetchNextPertemuan = (rombelId, pembelajaranId = '') => {
        if (!rombelId || !inputPertemuanKe) return;

        fetch(`/dashboard/agenda-kbm/next-pertemuan?rombongan_belajar_id=${encodeURIComponent(rombelId)}&pembelajaran_id=${encodeURIComponent(pembelajaranId)}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success' && data.next_pertemuan) {
                // Hanya perbarui jika sedang mode Tambah baru
                if (!agendaId || !agendaId.value) {
                    inputPertemuanKe.value = data.next_pertemuan;
                }
            }
        })
        .catch(err => console.error('Error fetching next pertemuan:', err));
    };

    const badgeAutoJamMulai = document.getElementById('badgeAutoJamMulai');
    const labelDurasiJp = document.getElementById('labelDurasiJp');
    const wrapWaktuKbm = document.getElementById('wrapWaktuKbm');
    const textWaktuKbm = document.getElementById('textWaktuKbm');
    const badgeHariJadwal = document.getElementById('badgeHariJadwal');

    // Helper: Terapkan data jadwal KBM secara otomatis dan kunci jam mulai/selesai
    const applyJadwalKbmOtomatis = (opt) => {
        if (opt && opt.value) {
            if (inputJadwalKbmId) inputJadwalKbmId.value = opt.value;
            if (inputRombel && opt.dataset.rombelId) inputRombel.value = opt.dataset.rombelId;
            if (inputMapel && opt.dataset.mapel) inputMapel.value = opt.dataset.mapel;
            if (inputPembelajaranId) inputPembelajaranId.value = opt.dataset.pembelajaranId || '';
            if (inputMataPelajaranId) inputMataPelajaranId.value = opt.dataset.mapelId || '';

            // Jam Ke Mulai & Selesai otomatis terisi dan terkunci (readonly)
            if (inputJamKeMulai && opt.dataset.jamMulai) {
                inputJamKeMulai.value = opt.dataset.jamMulai;
                inputJamKeMulai.readOnly = true;
                inputJamKeMulai.style.cursor = 'not-allowed';
            }
            if (inputJamKeSelesai && opt.dataset.jamSelesai) {
                inputJamKeSelesai.value = opt.dataset.jamSelesai;
                inputJamKeSelesai.readOnly = true;
                inputJamKeSelesai.style.cursor = 'not-allowed';
            }

            // Indikator visual otomatis
            if (badgeAutoJamMulai) badgeAutoJamMulai.style.display = 'inline-flex';
            if (labelDurasiJp) {
                const durasi = opt.dataset.durasiJp || (Math.max(1, (parseInt(opt.dataset.jamSelesai) || 1) - (parseInt(opt.dataset.jamMulai) || 1) + 1));
                labelDurasiJp.textContent = `${durasi} JP`;
                labelDurasiJp.style.display = 'inline';
            }

            // Banner Waktu KBM Nyata
            if (wrapWaktuKbm && textWaktuKbm) {
                const jamWaktu = opt.dataset.jamWaktu;
                const durasi = opt.dataset.durasiJp || '';
                textWaktuKbm.textContent = jamWaktu ? `${jamWaktu} (${durasi} JP)` : `Jam Pelajaran Ke-${opt.dataset.jamMulai} s.d. ${opt.dataset.jamSelesai}`;
                if (badgeHariJadwal) badgeHariJadwal.textContent = opt.dataset.hari || 'Jadwal Terpilih';
                wrapWaktuKbm.style.display = 'flex';
            }

            fetchNextPertemuan(opt.dataset.rombelId, opt.dataset.pembelajaranId);
        } else {
            // Mode Manual / Tanpa Jadwal
            if (inputJadwalKbmId) inputJadwalKbmId.value = '';
            if (inputJamKeMulai) {
                inputJamKeMulai.readOnly = false;
                inputJamKeMulai.style.cursor = 'auto';
            }
            if (inputJamKeSelesai) {
                inputJamKeSelesai.readOnly = false;
                inputJamKeSelesai.style.cursor = 'auto';
            }
            if (badgeAutoJamMulai) badgeAutoJamMulai.style.display = 'none';
            if (labelDurasiJp) labelDurasiJp.style.display = 'none';
            if (wrapWaktuKbm) wrapWaktuKbm.style.display = 'none';
        }
    };

    // Handle pemilihan Jadwal KBM di Form Modal
    if (selectJadwalKbm) {
        selectJadwalKbm.addEventListener('change', () => {
            const opt = selectJadwalKbm.selectedOptions[0];
            applyJadwalKbmOtomatis(opt);
        });
    }

    // Handle perubahan Kelas -> Otomatis cocokkan dengan opsi Jadwal KBM jika ada
    if (inputRombel) {
        inputRombel.addEventListener('change', () => {
            const rombelId = inputRombel.value;
            if (selectJadwalKbm && rombelId) {
                let foundMatch = false;
                for (let i = 1; i < selectJadwalKbm.options.length; i++) {
                    const opt = selectJadwalKbm.options[i];
                    if (opt.dataset.rombelId === rombelId) {
                        selectJadwalKbm.value = opt.value;
                        applyJadwalKbmOtomatis(opt);
                        foundMatch = true;
                        break;
                    }
                }
                if (!foundMatch) {
                    applyJadwalKbmOtomatis(null);
                    fetchNextPertemuan(rombelId, inputPembelajaranId?.value || '');
                }
            } else {
                applyJadwalKbmOtomatis(null);
                fetchNextPertemuan(rombelId, inputPembelajaranId?.value || '');
            }
        });
    }

    // Buka Modal Create Baru
    const openCreateModal = () => {
        if (formAgenda) formAgenda.reset();
        if (agendaId) agendaId.value = '';
        if (inputJadwalKbmId) inputJadwalKbmId.value = '';
        if (inputPembelajaranId) inputPembelajaranId.value = '';
        if (inputMataPelajaranId) inputMataPelajaranId.value = '';
        if (wrapJadwalSelector) wrapJadwalSelector.style.display = 'block';

        if (inputTanggal) {
            const todayStr = new Date().toISOString().split('T')[0];
            inputTanggal.value = todayStr;
        }
        if (inputPertemuanKe) inputPertemuanKe.value = 1;
        if (inputStatusKbm) inputStatusKbm.value = 'Terlaksana';

        if (modalTitle) {
            modalTitle.innerHTML = '<i class="fas fa-book-open-reader text-primary"></i> Tambah Jurnal & Agenda KBM';
        }

        // Otomatis pilih jadwal KBM hari ini jika tersedia
        const dayNames = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        const currentDayName = dayNames[new Date().getDay()];

        let autoSelectedOpt = null;
        if (selectJadwalKbm) {
            for (let i = 1; i < selectJadwalKbm.options.length; i++) {
                const opt = selectJadwalKbm.options[i];
                if (opt.dataset.hari === currentDayName) {
                    autoSelectedOpt = opt;
                    break;
                }
            }

            if (autoSelectedOpt) {
                selectJadwalKbm.value = autoSelectedOpt.value;
                applyJadwalKbmOtomatis(autoSelectedOpt);
            } else {
                selectJadwalKbm.value = '';
                applyJadwalKbmOtomatis(null);
            }
        }

        if (modalForm) modalForm.style.display = 'flex';

        if (inputTanggal && inputTanggal.value) {
            checkKalenderDateAgenda(inputTanggal.value);
        }
    };

    const infoKalenderAgendaBox = document.getElementById('infoKalenderTanggalAgenda');
    const checkKalenderDateAgenda = async (dateVal) => {
        if (!infoKalenderAgendaBox || !dateVal) return;
        try {
            const res = await fetch(`/dashboard/agenda-kbm/cek-kalender?tanggal=${encodeURIComponent(dateVal)}`, {
                headers: { 'Accept': 'application/json' }
            });
            if (!res.ok) return;
            const data = await res.json();
            if (data.is_libur) {
                infoKalenderAgendaBox.style.display = 'block';
                infoKalenderAgendaBox.style.background = 'rgba(239, 68, 68, 0.12)';
                infoKalenderAgendaBox.style.border = '1px solid rgba(239, 68, 68, 0.3)';
                infoKalenderAgendaBox.style.color = '#ef4444';
                infoKalenderAgendaBox.innerHTML = `<i class="fas fa-exclamation-triangle me-1"></i> <strong>Perhatian:</strong> Tanggal <b>${data.tanggal}</b> adalah <strong>${data.nama_agenda || 'Hari Libur'}</strong> di Kalender Pendidikan (${data.keterangan || 'KBM Reguler Diliburkan'}).`;
            } else if (data.nama_agenda) {
                infoKalenderAgendaBox.style.display = 'block';
                infoKalenderAgendaBox.style.background = 'rgba(99, 102, 241, 0.12)';
                infoKalenderAgendaBox.style.border = '1px solid rgba(99, 102, 241, 0.3)';
                infoKalenderAgendaBox.style.color = 'var(--primary)';
                infoKalenderAgendaBox.innerHTML = `<div style="display: flex; justify-content: space-between; align-items: center; gap: 8px; flex-wrap: wrap;">
                    <div><i class="fas fa-flag me-1"></i> <strong>Agenda Kalender:</strong> ${data.nama_agenda}${data.keterangan ? ' &mdash; ' + data.keterangan : ''}</div>
                    <button type="button" id="btnPakaiAgendaKalender" class="btn btn-outline" style="padding: 2px 8px; font-size: 0.72rem; border-color: var(--primary); color: var(--primary);">Gunakan Materi</button>
                </div>`;
                const btnPakai = document.getElementById('btnPakaiAgendaKalender');
                if (btnPakai) {
                    btnPakai.addEventListener('click', () => {
                        if (inputMateriPokok && !inputMateriPokok.value) {
                            inputMateriPokok.value = data.nama_agenda;
                        }
                        if (inputUraianKegiatan && !inputUraianKegiatan.value) {
                            inputUraianKegiatan.value = `Kegiatan agenda sekolah: ${data.nama_agenda}. ${data.keterangan || ''}`;
                        }
                        showToast('Agenda kalender diterapkan ke jurnal KBM.', 'info');
                    });
                }
            } else {
                infoKalenderAgendaBox.style.display = 'none';
            }
        } catch (e) {
            infoKalenderAgendaBox.style.display = 'none';
        }
    };

    if (inputTanggal) {
        inputTanggal.addEventListener('change', (e) => {
            checkKalenderDateAgenda(e.target.value);
        });
    }

    if (btnOpenCreate) {
        btnOpenCreate.addEventListener('click', openCreateModal);
    }

    // Tutup Modals
    const closeModals = () => {
        if (modalForm) modalForm.style.display = 'none';
        if (modalDetail) modalDetail.style.display = 'none';
    };

    document.querySelectorAll('.btn-close-modal, .btn-close-detail-agenda').forEach(btn => {
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
            if (filterStatusKbm?.value) params.set('status_kbm', filterStatusKbm.value);
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
    [filterTanggalMulai, filterTanggalSelesai, filterRombel, filterStatusKbm, filterPtk, perPageSelect].forEach(el => {
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
            if (filterStatusKbm) filterStatusKbm.value = '';
            if (filterPtk) filterPtk.value = '';
            if (perPageSelect) perPageSelect.value = '15';
            currentSort = 'tanggal';
            currentSortDir = 'desc';
            window.refreshLiveTable();
        });
    }

    // Submit Form Simpan / Update Jurnal Agenda KBM
    if (formAgenda) {
        formAgenda.addEventListener('submit', (e) => {
            e.preventDefault();

            const isEdit = agendaId && agendaId.value !== '';
            const url = isEdit ? `/dashboard/agenda-kbm/${agendaId.value}` : '/dashboard/agenda-kbm';
            const method = isEdit ? 'PUT' : 'POST';

            const formData = new FormData(formAgenda);
            const payload = {};
            formData.forEach((val, key) => {
                payload[key] = val;
            });

            if (btnSaveAgenda) {
                btnSaveAgenda.disabled = true;
                btnSaveAgenda.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Menyimpan...';
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
                if (btnSaveAgenda) {
                    btnSaveAgenda.disabled = false;
                    btnSaveAgenda.innerHTML = '<i class="fas fa-check me-1"></i> Simpan';
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
                            text: data.message || 'Terjadi kesalahan validasi data agenda.'
                        });
                    } else {
                        alert(data.message || 'Gagal menyimpan.');
                    }
                }
            })
            .catch(err => {
                console.error('Error submit agenda:', err);
                if (btnSaveAgenda) {
                    btnSaveAgenda.disabled = false;
                    btnSaveAgenda.innerHTML = '<i class="fas fa-check me-1"></i> Simpan';
                }
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Kesalahan Sistem',
                        text: 'Terjadi kesalahan saat menyimpan agenda KBM.'
                    });
                }
            });
        });
    }

    // Event Delegation: Tombol Edit Agenda
    document.addEventListener('click', (e) => {
        const editBtn = e.target.closest('.btn-edit-agenda');
        if (!editBtn) return;

        const id = editBtn.dataset.id;
        if (!id) return;

        fetch(`/dashboard/agenda-kbm/${id}`, {
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
            if (agendaId) agendaId.value = d.id;
            if (inputRombel) inputRombel.value = d.rombongan_belajar_id;
            if (inputMapel) inputMapel.value = d.nama_mata_pelajaran;
            if (inputTanggal) inputTanggal.value = d.tanggal;
            if (inputPertemuanKe) inputPertemuanKe.value = d.pertemuan_ke;
            if (inputStatusKbm) inputStatusKbm.value = d.status_kbm;
            if (inputJamKeMulai) {
                inputJamKeMulai.value = d.jam_ke_mulai;
                inputJamKeMulai.readOnly = true;
                inputJamKeMulai.style.cursor = 'not-allowed';
            }
            if (inputJamKeSelesai) {
                inputJamKeSelesai.value = d.jam_ke_selesai;
                inputJamKeSelesai.readOnly = true;
                inputJamKeSelesai.style.cursor = 'not-allowed';
            }
            if (badgeAutoJamMulai) badgeAutoJamMulai.style.display = 'inline-flex';
            if (wrapWaktuKbm) wrapWaktuKbm.style.display = 'none';
            if (inputMateriPokok) inputMateriPokok.value = d.materi_pokok;
            if (inputUraianKegiatan) inputUraianKegiatan.value = d.uraian_kegiatan;
            if (inputPenugasan) inputPenugasan.value = d.penugasan ?? '';
            if (inputHambatanCatatan) inputHambatanCatatan.value = d.hambatan_catatan ?? '';

            if (wrapJadwalSelector) wrapJadwalSelector.style.display = 'none';

            if (modalTitle) {
                modalTitle.innerHTML = `<i class="fas fa-pen-to-square text-warning"></i> Edit Agenda: Pertemuan #${d.pertemuan_ke}`;
            }

            if (modalForm) modalForm.style.display = 'flex';
        })
        .catch(err => {
            console.error('Error fetching detail for edit:', err);
            showToast('Gagal mengambil data agenda KBM.', 'error');
        });
    });

    // Event Delegation: Tombol Detail Agenda
    document.addEventListener('click', (e) => {
        const detailBtn = e.target.closest('.btn-detail-agenda');
        if (!detailBtn) return;

        const id = detailBtn.dataset.id;
        if (!id) return;

        fetch(`/dashboard/agenda-kbm/${id}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(res => res.json())
        .then(res => {
            if (res.status !== 'success') return;
            const d = res.data;
            const container = document.getElementById('detailAgendaContent');
            if (!container) return;

            container.innerHTML = `
                <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); padding-bottom: 8px;">
                    <span style="color: var(--text-muted); font-size: 0.8rem;">Tanggal & Hari</span>
                    <strong style="color: var(--text-color); font-size: 0.9rem;">${d.hari}, ${d.tanggal_formatted}</strong>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); padding-bottom: 8px;">
                    <span style="color: var(--text-muted); font-size: 0.8rem;">Kelas & Mapel</span>
                    <strong style="color: var(--text-color); font-size: 0.9rem;">${d.nama_rombel} &bull; ${d.nama_mata_pelajaran}</strong>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); padding-bottom: 8px;">
                    <span style="color: var(--text-muted); font-size: 0.8rem;">Pertemuan & Jam</span>
                    <strong style="color: var(--text-color); font-size: 0.9rem;">Pertemuan Ke-${d.pertemuan_ke} (Jam ke ${d.jam_ke_mulai}-${d.jam_ke_selesai})</strong>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); padding-bottom: 8px;">
                    <span style="color: var(--text-muted); font-size: 0.8rem;">Status Keterlaksanaan</span>
                    <div>${d.status_badge}</div>
                </div>
                <div style="margin-top: 4px; padding: 10px 12px; background: var(--bg-hover); border-radius: 8px;">
                    <span style="color: var(--primary); font-size: 0.78rem; font-weight: 700; display: block; margin-bottom: 3px;">
                        Materi Pokok / Indikator:
                    </span>
                    <div style="color: var(--text-color); font-size: 0.9rem; font-weight: 600;">${d.materi_pokok}</div>
                </div>
                <div style="padding: 10px 12px; background: var(--bg-hover); border-radius: 8px;">
                    <span style="color: var(--text-muted); font-size: 0.78rem; font-weight: 700; display: block; margin-bottom: 4px;">
                        Uraian Kegiatan Pembelajaran:
                    </span>
                    <div style="color: var(--text-color); font-size: 0.85rem; line-height: 1.5; white-space: pre-line;">${d.uraian_kegiatan}</div>
                </div>
                ${d.penugasan ? `
                    <div style="padding: 10px 12px; background: rgba(16,185,129,0.06); border: 1px solid rgba(16,185,129,0.2); border-radius: 8px;">
                        <span style="color: #10b981; font-size: 0.78rem; font-weight: 700; display: block; margin-bottom: 3px;">
                            <i class="fas fa-tasks me-1"></i> Penugasan / Asesmen:
                        </span>
                        <div style="color: var(--text-color); font-size: 0.84rem; white-space: pre-line;">${d.penugasan}</div>
                    </div>
                ` : ''}
                ${d.hambatan_catatan ? `
                    <div style="padding: 10px 12px; background: rgba(245,158,11,0.06); border: 1px solid rgba(245,158,11,0.2); border-radius: 8px;">
                        <span style="color: #f59e0b; font-size: 0.78rem; font-weight: 700; display: block; margin-bottom: 3px;">
                            <i class="fas fa-triangle-exclamation me-1"></i> Kendala / Catatan:
                        </span>
                        <div style="color: var(--text-color); font-size: 0.84rem;">${d.hambatan_catatan}</div>
                    </div>
                ` : ''}
            `;

            if (modalDetail) modalDetail.style.display = 'flex';
        });
    });

    // Event Delegation: Tombol Delete Agenda (SweetAlert2)
    document.addEventListener('click', (e) => {
        const delBtn = e.target.closest('.btn-delete-agenda');
        if (!delBtn) return;

        const id = delBtn.dataset.id;
        const name = delBtn.dataset.name || 'Data';
        if (!id) return;

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Hapus Jurnal KBM?',
                html: `Apakah Anda yakin ingin menghapus catatan agenda <strong>${name}</strong>?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Ya, Hapus',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch(`/dashboard/agenda-kbm/${id}`, {
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