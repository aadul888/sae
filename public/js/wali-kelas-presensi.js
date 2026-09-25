/**
 * Wali Kelas — Presensi Kelas Binaan
 * Modular JavaScript Baku SAE:
 * 1. Live Search Asinkron, refreshLiveTable, Sortable Header
 * 2. Presensi Manual Harian oleh Wali Kelas
 * 3. Approval & Verifikasi Pengajuan Surat Izin / Sakit Kelas Binaan
 * 4. Modal Pratinjau Lampiran Berkas & Unduh PDF Multi-Periode
 */

document.addEventListener('DOMContentLoaded', function () {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    // =========================================================================
    // 1. FILTER & LIVE TABLE ENGINE (TAB 1: PRESENSI HARIAN)
    // =========================================================================
    const perPageSelect     = document.getElementById('perPageSelect');
    const filterTanggal     = document.getElementById('filterTanggal');
    const filterStatus      = document.getElementById('filterStatus');
    const adminRombelSelect = document.getElementById('adminRombelSelect');
    const liveSearchInput   = document.getElementById('liveSearchInput');
    const clearSearchBtn    = document.getElementById('clearSearchBtn');
    const btnResetFilter    = document.getElementById('btnResetFilter');

    function applyFilter(overrideParams = {}) {
        const url = new URL(window.location.href);
        url.searchParams.set('tab', 'harian');

        if (perPageSelect) url.searchParams.set('perPage', perPageSelect.value);
        if (filterTanggal) {
            if (filterTanggal.value) url.searchParams.set('tanggal', filterTanggal.value);
            else url.searchParams.delete('tanggal');
        }
        if (filterStatus) {
            if (filterStatus.value) url.searchParams.set('status_filter', filterStatus.value);
            else url.searchParams.delete('status_filter');
        }
        if (adminRombelSelect) {
            if (adminRombelSelect.value) url.searchParams.set('rombel_id', adminRombelSelect.value);
            else url.searchParams.delete('rombel_id');
        }
        if (liveSearchInput) {
            const qVal = liveSearchInput.value.trim();
            if (qVal) url.searchParams.set('q', qVal);
            else url.searchParams.delete('q');
        }

        Object.keys(overrideParams).forEach((k) => {
            const v = overrideParams[k];
            if (v !== null && v !== undefined && v !== '') url.searchParams.set(k, v);
            else url.searchParams.delete(k);
        });

        // Reset page ke 1 saat ganti filter/sort kecuali page eksplisit
        if (!('page' in overrideParams)) {
            url.searchParams.delete('page');
        }

        if (typeof window.refreshLiveTable === 'function') {
            window.refreshLiveTable(url.toString());
        } else {
            window.location.href = url.toString();
        }
    }

    if (perPageSelect) perPageSelect.addEventListener('change', () => applyFilter());
    if (filterTanggal) filterTanggal.addEventListener('change', () => applyFilter());
    if (filterStatus) filterStatus.addEventListener('change', () => applyFilter());
    if (adminRombelSelect) adminRombelSelect.addEventListener('change', () => applyFilter());

    // Live search asinkron presensi harian dengan debounce
    let searchDebounce = null;
    if (liveSearchInput) {
        liveSearchInput.addEventListener('input', function () {
            clearTimeout(searchDebounce);
            if (clearSearchBtn) {
                clearSearchBtn.classList.toggle('visible', this.value.trim().length > 0);
            }
            searchDebounce = setTimeout(() => {
                applyFilter();
            }, 350);
        });

        liveSearchInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                clearTimeout(searchDebounce);
                applyFilter();
            }
        });
    }

    if (clearSearchBtn) {
        clearSearchBtn.addEventListener('click', function () {
            if (liveSearchInput) {
                liveSearchInput.value = '';
                this.classList.remove('visible');
                liveSearchInput.focus();
            }
            applyFilter({ q: '' });
        });
    }

    if (btnResetFilter) {
        btnResetFilter.addEventListener('click', function () {
            const url = new URL(window.location.href);
            const rombelId = url.searchParams.get('rombel_id');
            const cleanUrl = new URL(window.location.pathname, window.location.origin);
            cleanUrl.searchParams.set('tab', 'harian');
            if (rombelId) cleanUrl.searchParams.set('rombel_id', rombelId);

            if (typeof window.refreshLiveTable === 'function') {
                window.refreshLiveTable(cleanUrl.toString());
            } else {
                window.location.href = cleanUrl.toString();
            }
        });
    }

    // Sortable Column Header Server-Side (Event Delegation)
    document.addEventListener('click', function (e) {
        const th = e.target.closest('.sortable-th');
        if (!th) return;

        const sortField = th.getAttribute('data-sort');
        if (!sortField) return;

        const url = new URL(window.location.href);
        const currentSort = url.searchParams.get('sort') || 'nama';
        const currentDir = url.searchParams.get('sort_dir') || 'asc';

        let newDir = 'asc';
        if (currentSort === sortField && currentDir === 'asc') {
            newDir = 'desc';
        }

        applyFilter({ sort: sortField, sort_dir: newDir });
    });

    // =========================================================================
    // 2. FILTER & LIVE TABLE ENGINE (TAB 2: SURAT IZIN & SAKIT)
    // =========================================================================
    const perPageSelectIzin     = document.getElementById('perPageSelectIzin');
    const adminRombelSelectIzin = document.getElementById('adminRombelSelectIzin');
    const filterJenisIzin       = document.getElementById('filterJenisIzin');
    const filterStatusIzin      = document.getElementById('filterStatusIzin');
    const liveSearchIzinInput   = document.getElementById('liveSearchIzinInput');
    const clearSearchIzinBtn    = document.getElementById('clearSearchIzinBtn');
    const formFilterIzinWali    = document.getElementById('formFilterIzinWali');

    function applyFilterIzin(overrideParams = {}) {
        const url = new URL(window.location.href);
        url.searchParams.set('tab', 'izin');

        if (perPageSelectIzin) url.searchParams.set('perPage', perPageSelectIzin.value);
        if (adminRombelSelectIzin) {
            if (adminRombelSelectIzin.value) url.searchParams.set('rombel_id', adminRombelSelectIzin.value);
            else url.searchParams.delete('rombel_id');
        }
        if (filterJenisIzin) {
            if (filterJenisIzin.value) url.searchParams.set('jenis_izin', filterJenisIzin.value);
            else url.searchParams.delete('jenis_izin');
        }
        if (filterStatusIzin) {
            if (filterStatusIzin.value) url.searchParams.set('status_izin', filterStatusIzin.value);
            else url.searchParams.delete('status_izin');
        }
        if (liveSearchIzinInput) {
            const qVal = liveSearchIzinInput.value.trim();
            if (qVal) url.searchParams.set('q_izin', qVal);
            else url.searchParams.delete('q_izin');
        }

        Object.keys(overrideParams).forEach((k) => {
            const v = overrideParams[k];
            if (v !== null && v !== undefined && v !== '') url.searchParams.set(k, v);
            else url.searchParams.delete(k);
        });

        // Reset page ke 1 saat ganti filter kecuali page eksplisit
        if (!('page' in overrideParams)) {
            url.searchParams.delete('page');
        }

        if (typeof window.refreshLiveTable === 'function') {
            window.refreshLiveTable(url.toString());
        } else {
            window.location.href = url.toString();
        }
    }

    if (perPageSelectIzin)     perPageSelectIzin.addEventListener('change', () => applyFilterIzin());
    if (adminRombelSelectIzin) adminRombelSelectIzin.addEventListener('change', () => applyFilterIzin());
    if (filterJenisIzin)       filterJenisIzin.addEventListener('change', () => applyFilterIzin());
    if (filterStatusIzin)      filterStatusIzin.addEventListener('change', () => applyFilterIzin());

    let searchIzinDebounce = null;
    if (liveSearchIzinInput) {
        liveSearchIzinInput.addEventListener('input', function () {
            clearTimeout(searchIzinDebounce);
            if (clearSearchIzinBtn) {
                clearSearchIzinBtn.classList.toggle('visible', this.value.trim().length > 0);
            }
            searchIzinDebounce = setTimeout(() => {
                applyFilterIzin();
            }, 350);
        });

        liveSearchIzinInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                clearTimeout(searchIzinDebounce);
                applyFilterIzin();
            }
        });
    }

    if (clearSearchIzinBtn) {
        clearSearchIzinBtn.addEventListener('click', function () {
            if (liveSearchIzinInput) {
                liveSearchIzinInput.value = '';
                this.classList.remove('visible');
                liveSearchIzinInput.focus();
            }
            applyFilterIzin({ q_izin: '' });
        });
    }

    if (formFilterIzinWali) {
        formFilterIzinWali.addEventListener('submit', function (e) {
            e.preventDefault();
            applyFilterIzin();
        });
    }


    // =========================================================================
    // 3. PRESENSI MANUAL OLEH WALI KELAS (Event Delegation & Instant Toggle Baku SAE)
    // =========================================================================
    const modalIzinSakitWali    = document.getElementById('modalIzinSakitWali');
    const formIzinSakitWali     = document.getElementById('formIzinSakitWali');
    const izinWaliPdId          = document.getElementById('izinWaliPdId');
    const izinWaliAction        = document.getElementById('izinWaliAction');
    const izinWaliKeterangan    = document.getElementById('izinWaliKeteranganInput');
    const izinWaliPdNama        = document.getElementById('izinWaliPdNama');
    const titleModalIzinWali    = document.getElementById('titleModalIzinWali');
    const btnCloseIzinModalWali = document.getElementById('btnCloseIzinModalWali');
    const btnCancelIzinModalWali= document.getElementById('btnCancelIzinModalWali');

    function closeIzinModalWali() {
        if (modalIzinSakitWali) modalIzinSakitWali.style.display = 'none';
        if (formIzinSakitWali) formIzinSakitWali.reset();
    }

    if (btnCloseIzinModalWali)  btnCloseIzinModalWali.addEventListener('click', closeIzinModalWali);
    if (btnCancelIzinModalWali) btnCancelIzinModalWali.addEventListener('click', closeIzinModalWali);

    document.addEventListener('click', async function (e) {
        const btn = e.target.closest('.btn-presensi-manual, .btn-action-absen');
        if (!btn) return;

        const pdId    = btn.getAttribute('data-id');
        const nama    = btn.getAttribute('data-nama');
        const action  = (btn.getAttribute('data-action') || btn.getAttribute('data-status') || '').toLowerCase();
        const tanggal = btn.getAttribute('data-tanggal') || document.getElementById('filterTanggal')?.value || new Date().toISOString().slice(0, 10);

        if (!pdId || !action) return;

        const isActive = btn.classList.contains('active-' + action.toUpperCase()) ||
                         (action === 'pulang' && (btn.classList.contains('active-P') || btn.classList.contains('active-D')));
        const finalAction = isActive ? 'reset' : action;

        // Jika Izin atau Sakit dan belum aktif, tampilkan modal catatan/alasan
        if ((action === 'izin' || action === 'i' || action === 'sakit' || action === 's') && !isActive) {
            const isSakit = (action === 'sakit' || action === 's');
            if (izinWaliPdId) izinWaliPdId.value = pdId;
            if (izinWaliAction) izinWaliAction.value = isSakit ? 'sakit' : 'izin';
            if (izinWaliPdNama) izinWaliPdNama.textContent = nama || '-';
            if (izinWaliKeterangan) izinWaliKeterangan.value = '';
            if (titleModalIzinWali) {
                titleModalIzinWali.innerHTML = isSakit
                    ? '<i class="fas fa-notes-medical text-primary"></i> Pencatatan Sakit Harian'
                    : '<i class="fas fa-file-signature text-primary"></i> Pencatatan Izin Harian';
            }
            if (modalIzinSakitWali) modalIzinSakitWali.style.display = 'flex';
            return;
        }

        // Untuk Hadir (H/masuk), Terlambat (T), Alpha (A), Pulang, atau Reset: eksekusi cepat AJAX
        try {
            const res = await fetch('/dashboard/wali-kelas/presensi/manual', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    peserta_didik_id: pdId,
                    tanggal: tanggal,
                    action: finalAction
                })
            });

            const data = await res.json();

            if (res.ok && data.status === 'success') {
                const row = document.getElementById('row-siswa-' + pdId);
                if (row) {
                    if (finalAction === 'reset') {
                        row.querySelectorAll('.btn-status-toggle').forEach(b => {
                            b.className = 'btn-status-toggle btn-presensi-manual';
                            if (b.getAttribute('data-action') === 'pulang') {
                                b.textContent = 'P';
                                b.title = 'Catat Pulang';
                            }
                        });
                    } else {
                        const statusVal = data.status_val || (action === 'masuk' ? 'H' : (action === 'terlambat' ? 'T' : (action === 'alpha' ? 'A' : '')));
                        if (statusVal) {
                            row.querySelectorAll('.btn-status-toggle:not([data-action="pulang"])').forEach(b => {
                                const bStatus = (b.getAttribute('data-status') || b.getAttribute('data-action') || '').toUpperCase();
                                if (bStatus === statusVal || (statusVal === 'H' && bStatus === 'MASUK') || (statusVal === 'T' && bStatus === 'TERLAMBAT') || (statusVal === 'A' && bStatus === 'ALPHA')) {
                                    b.className = `btn-status-toggle btn-presensi-manual active-${statusVal}`;
                                } else {
                                    b.className = 'btn-status-toggle btn-presensi-manual';
                                }
                            });
                        }
                    }

                    const badgeCell = document.getElementById('badge-status-' + pdId);
                    if (badgeCell && data.badge) {
                        badgeCell.innerHTML = data.badge;
                    }

                    const waktuCell = document.getElementById('waktu-status-' + pdId);
                    if (waktuCell) {
                        const txtMasuk = waktuCell.querySelector('.text-jam-masuk');
                        if (txtMasuk) txtMasuk.textContent = data.jam_masuk || '—';
                        const txtPulang = waktuCell.querySelector('.text-jam-pulang');
                        if (txtPulang) txtPulang.textContent = data.jam_pulang || '—';
                    }

                    if (finalAction === 'pulang') {
                        const btnPulang = row.querySelector('.btn-status-toggle[data-action="pulang"]');
                        if (btnPulang) {
                            btnPulang.className = 'btn-status-toggle btn-presensi-manual active-P active-D';
                            btnPulang.textContent = 'P';
                            btnPulang.title = `Sudah Pulang (${data.jam_pulang || ''})`;
                        }
                    }
                }

                Swal.fire({
                    icon: 'success',
                    title: finalAction === 'reset' ? 'Presensi Dikosongkan' : 'Presensi Harian Disimpan',
                    text: data.message,
                    timer: 1200,
                    showConfirmButton: false,
                    toast: true,
                    position: 'top-end'
                });
            } else {
                Swal.fire('Presensi Gagal', data.message || 'Gagal mencatat presensi manual.', 'error');
            }
        } catch (e) {
            Swal.fire('Error', 'Terjadi kesalahan koneksi ke server.', 'error');
        }
    });

    // Form Submit Izin / Sakit Harian
    if (formIzinSakitWali) {
        formIzinSakitWali.addEventListener('submit', async function (e) {
            e.preventDefault();

            const pdId    = izinWaliPdId?.value;
            const action  = izinWaliAction?.value || 'izin';
            const ket     = izinWaliKeterangan?.value?.trim() || '';
            const tanggal = document.getElementById('filterTanggal')?.value || new Date().toISOString().slice(0, 10);

            if (!pdId) return;

            try {
                Swal.fire({
                    title: 'Menyimpan...',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });

                const res = await fetch('/dashboard/wali-kelas/presensi/manual', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        peserta_didik_id: pdId,
                        tanggal: tanggal,
                        action: action,
                        keterangan: ket
                    })
                });

                const data = await res.json();

                if (res.ok && data.status === 'success') {
                    closeIzinModalWali();

                    const row = document.getElementById('row-siswa-' + pdId);
                    if (row) {
                        const statusVal = data.status_val || (action === 'sakit' ? 'S' : 'I');
                        row.querySelectorAll('.btn-status-toggle:not([data-action="pulang"])').forEach(b => {
                            const bStatus = (b.getAttribute('data-status') || b.getAttribute('data-action') || '').toUpperCase();
                            if (bStatus === statusVal || (statusVal === 'I' && bStatus === 'IZIN') || (statusVal === 'S' && bStatus === 'SAKIT')) {
                                b.className = `btn-status-toggle btn-presensi-manual active-${statusVal}`;
                            } else {
                                b.className = 'btn-status-toggle btn-presensi-manual';
                            }
                        });

                        const badgeCell = document.getElementById('badge-status-' + pdId);
                        if (badgeCell && data.badge) {
                            badgeCell.innerHTML = data.badge;
                        }

                        const ketCell = document.getElementById('ket-status-' + pdId);
                        if (ketCell) {
                            ketCell.textContent = ket || '-';
                        }
                    }

                    Swal.fire({
                        icon: 'success',
                        title: 'Tersimpan!',
                        text: data.message,
                        timer: 1300,
                        showConfirmButton: false,
                        toast: true,
                        position: 'top-end'
                    });
                } else {
                    Swal.fire('Gagal', data.message || 'Gagal menyimpan data izin/sakit.', 'error');
                }
            } catch (err) {
                Swal.fire('Error', 'Gagal memproses data.', 'error');
            }
        });
    }

    // Tombol Bulk Reset Presensi Manual Kelas Binaan
    const btnResetPresensiWali = document.getElementById('btnResetPresensiWali');
    if (btnResetPresensiWali) {
        btnResetPresensiWali.addEventListener('click', function () {
            const rombelId = this.getAttribute('data-rombel');
            const tanggal = this.getAttribute('data-tanggal') || document.getElementById('filterTanggal')?.value || new Date().toISOString().slice(0, 10);

            Swal.fire({
                title: 'Reset Presensi Kelas?',
                text: 'Seluruh catatan presensi manual untuk tanggal terpilih akan dikosongkan kembali agar dapat diulangi. Presensi mandiri (RFID/QR) tetap terlindungi.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#64748b',
                confirmButtonText: '<i class="fas fa-rotate-left me-1"></i> Ya, Reset Presensi',
                cancelButtonText: 'Batal'
            }).then(async (result) => {
                if (result.isConfirmed) {
                    try {
                        Swal.fire({
                            title: 'Mereset Presensi...',
                            allowOutsideClick: false,
                            didOpen: () => Swal.showLoading()
                        });

                        const res = await fetch('/dashboard/wali-kelas/presensi/reset', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                rombongan_belajar_id: rombelId,
                                tanggal: tanggal
                            })
                        });

                        const data = await res.json();

                        if (res.ok && data.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Presensi Dikosongkan!',
                                text: data.message,
                                timer: 1500,
                                showConfirmButton: false
                            }).then(() => {
                                if (typeof window.refreshLiveTable === 'function') {
                                    window.refreshLiveTable(window.location.href);
                                } else {
                                    window.location.reload();
                                }
                            });
                        } else {
                            Swal.fire('Gagal', data.message || 'Gagal mereset presensi manual.', 'error');
                        }
                    } catch (e) {
                        Swal.fire('Error', 'Terjadi kesalahan sistem.', 'error');
                    }
                }
            });
        });
    }

    // =========================================================================
    // 4. APPROVAL & VERIFIKASI SURAT IZIN / SAKIT (TAB 2)
    // =========================================================================
    const modalVerifikasiIzin   = document.getElementById('modalVerifikasiIzin');
    const btnCloseModalVerif    = document.getElementById('btnCloseModalVerifikasi');
    const btnCancelVerifikasi   = document.getElementById('btnCancelVerifikasi');
    const formSubmitVerifikasi  = document.getElementById('formSubmitVerifikasiIzin');
    const verifIzinIdInput      = document.getElementById('verifIzinId');
    const verifSiswaNamaText    = document.getElementById('verifSiswaNama');
    const verifJenisSuratText   = document.getElementById('verifJenisSurat');
    const verifRentangTglText   = document.getElementById('verifRentangTgl');
    const verifCatatanInput     = document.getElementById('verifCatatan');

    function closeVerifikasiModal() {
        if (modalVerifikasiIzin) {
            modalVerifikasiIzin.style.display = 'none';
        }
    }

    if (btnCloseModalVerif)  btnCloseModalVerif.addEventListener('click', closeVerifikasiModal);
    if (btnCancelVerifikasi) btnCancelVerifikasi.addEventListener('click', closeVerifikasiModal);

    window.addEventListener('click', function (e) {
        if (e.target === modalVerifikasiIzin) {
            closeVerifikasiModal();
        }
    });

    // Buka Modal Verifikasi (Event Delegation)
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-open-verifikasi');
        if (!btn) return;

        const id      = btn.getAttribute('data-id');
        const nama    = btn.getAttribute('data-nama');
        const jenis   = btn.getAttribute('data-jenis');
        const status  = btn.getAttribute('data-status');
        const catatan = btn.getAttribute('data-catatan') || '';
        const rentang = btn.getAttribute('data-rentang');

        if (verifIzinIdInput)   verifIzinIdInput.value = id;
        if (verifSiswaNamaText) verifSiswaNamaText.textContent = nama;
        if (verifJenisSuratText) verifJenisSuratText.textContent = jenis;
        if (verifRentangTglText) verifRentangTglText.textContent = rentang;
        if (verifCatatanInput)   verifCatatanInput.value = catatan;

        // Set radio button status
        const radioStatus = document.querySelectorAll('input[name="verif_status"]');
        radioStatus.forEach((r) => {
            if (status === 'ditolak' && r.value === 'ditolak') {
                r.checked = true;
            } else if (status !== 'ditolak' && r.value === 'disetujui') {
                r.checked = true;
            }
        });

        if (modalVerifikasiIzin) {
            modalVerifikasiIzin.style.display = 'flex';
        }
    });

    // Submit Verifikasi Form
    if (formSubmitVerifikasi) {
        formSubmitVerifikasi.addEventListener('submit', async function (e) {
            e.preventDefault();

            const izinId = verifIzinIdInput?.value;
            if (!izinId) {
                Swal.fire('Error', 'ID permohonan surat tidak valid.', 'error');
                return;
            }

            const selectedStatusEl = document.querySelector('input[name="verif_status"]:checked');
            const selectedStatus   = selectedStatusEl ? selectedStatusEl.value : 'disetujui';
            const catatan          = verifCatatanInput ? verifCatatanInput.value.trim() : '';

            // Dialog konfirmasi akhir
            const confirmResult = await Swal.fire({
                title: selectedStatus === 'disetujui' ? 'Setujui Surat Izin?' : 'Tolak Surat Izin?',
                html: `
                    <div style="font-size: 0.88rem; color: var(--text-color); text-align: left;">
                        <p style="margin-bottom: 8px;">Peserta Didik: <strong>${verifSiswaNamaText?.textContent || '-'}</strong></p>
                        <p style="margin-bottom: 8px;">Keputusan: <strong style="color: ${selectedStatus === 'disetujui' ? '#10b981' : '#ef4444'};">${selectedStatus.toUpperCase()}</strong></p>
                        ${selectedStatus === 'disetujui' ? '<div style="font-size: 0.78rem; color: #10b981; background: rgba(16,185,129,0.1); padding: 8px 12px; border-radius: 8px;"><i class="fas fa-check-circle me-1"></i> Kehadiran siswa pada rentang tanggal izin akan otomatis diperbarui menjadi Izin/Sakit.</div>' : '<div style="font-size: 0.78rem; color: #ef4444; background: rgba(239,68,68,0.1); padding: 8px 12px; border-radius: 8px;"><i class="fas fa-info-circle me-1"></i> Pemberitahuan penolakan akan dikirimkan kepada peserta didik.</div>'}
                    </div>
                `,
                icon: selectedStatus === 'disetujui' ? 'question' : 'warning',
                showCancelButton: true,
                confirmButtonText: selectedStatus === 'disetujui' ? '<i class="fas fa-check me-1"></i> Ya, Setujui' : '<i class="fas fa-times me-1"></i> Ya, Tolak',
                confirmButtonColor: selectedStatus === 'disetujui' ? '#10b981' : '#ef4444',
                cancelButtonText: 'Kembali'
            });

            if (!confirmResult.isConfirmed) return;

            try {
                Swal.fire({
                    title: 'Menyimpan Verifikasi...',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });

                const res = await fetch(`/dashboard/wali-kelas/presensi/izin/${izinId}/verifikasi`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        status: selectedStatus,
                        catatan: catatan
                    })
                });

                const data = await res.json();

                if (res.ok && data.status === 'success') {
                    closeVerifikasiModal();
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: data.message,
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        if (typeof window.refreshLiveTable === 'function') {
                            window.refreshLiveTable(window.location.href);
                        } else {
                            window.location.reload();
                        }
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal Menyimpan',
                        text: data.message || 'Terjadi kesalahan saat verifikasi izin.'
                    });
                }
            } catch (err) {
                Swal.fire('Kesalahan Sistem', 'Tidak dapat menghubungi server verifikasi.', 'error');
            }
        });
    }

    // =========================================================================
    // 5. MODAL PRATINJAU BERKAS LAMPIRAN
    // =========================================================================
    const modalPreviewLampiranWali   = document.getElementById('modalPreviewLampiranWali');
    const btnClosePreviewLampiranWali = document.getElementById('btnClosePreviewLampiranWali');
    const previewLampiranWaliTitle   = document.getElementById('previewLampiranWaliTitle');
    const previewContainerWali       = document.getElementById('previewContainerWali');

    function closePreviewModal() {
        if (modalPreviewLampiranWali) {
            modalPreviewLampiranWali.style.display = 'none';
        }
        if (previewContainerWali) {
            previewContainerWali.innerHTML = '';
        }
    }

    if (btnClosePreviewLampiranWali) {
        btnClosePreviewLampiranWali.addEventListener('click', closePreviewModal);
    }

    window.addEventListener('click', function (e) {
        if (e.target === modalPreviewLampiranWali) {
            closePreviewModal();
        }
    });

    // Pratinjau Lampiran (Event Delegation)
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-preview-lampiran-wali');
        if (!btn) return;

        const url   = btn.getAttribute('data-url');
        const title = btn.getAttribute('data-title') || 'Berkas Lampiran Surat';

        if (!url || !previewContainerWali) return;

        if (previewLampiranWaliTitle) {
            previewLampiranWaliTitle.textContent = title;
        }

        const isPdf = url.toLowerCase().includes('.pdf');
        if (isPdf) {
            previewContainerWali.innerHTML = `
                <iframe src="${url}" style="width: 100%; height: 520px; border: none; border-radius: 8px;" title="Dokumen PDF Lampiran"></iframe>
            `;
        } else {
            previewContainerWali.innerHTML = `
                <img src="${url}" alt="Lampiran Surat" style="max-width: 100%; max-height: 70vh; border-radius: 8px; object-fit: contain; box-shadow: 0 4px 16px rgba(0,0,0,0.3);">
            `;
        }

        if (modalPreviewLampiranWali) {
            modalPreviewLampiranWali.style.display = 'flex';
        }
    });

    // =========================================================================
    // 6. MODAL UNDUH LAPORAN PDF MULTI-PERIODE
    // =========================================================================
    const btnOpenModalPdf  = document.getElementById('btnOpenModalPdf');
    const modalDownloadPdf = document.getElementById('modalDownloadPdf');
    const btnCloseModalPdf = document.getElementById('btnCloseModalPdf');

    if (btnOpenModalPdf && modalDownloadPdf) {
        btnOpenModalPdf.addEventListener('click', () => {
            modalDownloadPdf.style.display = 'flex';
        });
    }

    if (btnCloseModalPdf && modalDownloadPdf) {
        btnCloseModalPdf.addEventListener('click', () => {
            modalDownloadPdf.style.display = 'none';
        });
    }

    window.addEventListener('click', (e) => {
        if (e.target === modalDownloadPdf) {
            modalDownloadPdf.style.display = 'none';
        }
    });

    const selectTipeLaporan   = document.getElementById('selectTipeLaporan');
    const formDownloadPdf     = document.getElementById('formDownloadPdf');
    const groupFilterBulan    = document.getElementById('groupFilterBulan');
    const groupFilterSemester = document.getElementById('groupFilterSemester');
    const groupFilterTanggal  = document.getElementById('groupFilterTanggal');
    const groupFilterSiswa    = document.getElementById('groupFilterSiswa');
    const groupFilterTahun    = document.getElementById('groupFilterTahun');

    function syncPdfForm() {
        if (!selectTipeLaporan || !formDownloadPdf) return;
        const val = selectTipeLaporan.value;
        formDownloadPdf.action = `/dashboard/wali-kelas/presensi/pdf/${val}`;

        if (groupFilterSiswa)    groupFilterSiswa.style.display    = (val === 'siswa') ? 'block' : 'none';
        if (groupFilterTanggal)  groupFilterTanggal.style.display  = (val === 'hari') ? 'block' : 'none';
        if (groupFilterBulan)    groupFilterBulan.style.display    = (val === 'bulan' || val === 'siswa') ? 'block' : 'none';
        if (groupFilterSemester) groupFilterSemester.style.display = (val === 'semester') ? 'block' : 'none';
        if (groupFilterTahun)    groupFilterTahun.style.display    = (val === 'tahun') ? 'block' : 'none';
    }

    if (selectTipeLaporan) {
        selectTipeLaporan.addEventListener('change', syncPdfForm);
        syncPdfForm();
    }
});
