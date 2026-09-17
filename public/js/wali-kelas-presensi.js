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

    // Intercept pagination clicks universally
    document.addEventListener('click', function (e) {
        const pageLink = e.target.closest('.custom-pagination a.page-btn');
        if (pageLink && pageLink.href) {
            e.preventDefault();
            if (typeof window.refreshLiveTable === 'function') {
                window.refreshLiveTable(pageLink.href);
            } else {
                window.location.href = pageLink.href;
            }
        }
    });

    // =========================================================================
    // 3. PRESENSI MANUAL OLEH WALI KELAS (Event Delegation agar tahan AJAX)
    // =========================================================================
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-presensi-manual');
        if (!btn) return;

        const pdId    = btn.getAttribute('data-id');
        const nama    = btn.getAttribute('data-nama');
        const action  = btn.getAttribute('data-action');
        const tanggal = btn.getAttribute('data-tanggal') || document.getElementById('filterTanggal')?.value || new Date().toISOString().slice(0, 10);

        let actionLabel = 'Hadir Tepat Waktu (H)';
        let actionColor = '#10b981';
        let iconHtml    = '<i class="fas fa-check-circle me-1"></i>';
        let needNote    = false;
        let notePlaceholder = 'Catatan kehadiran (opsional)...';

        switch (action) {
            case 'masuk':
                actionLabel = 'Hadir Tepat Waktu (H)';
                actionColor = '#10b981';
                iconHtml = '<i class="fas fa-check me-1"></i>';
                break;
            case 'terlambat':
                actionLabel = 'Terlambat (T)';
                actionColor = '#f59e0b';
                iconHtml = '<i class="fas fa-clock me-1"></i>';
                notePlaceholder = 'Alasan keterlambatan (opsional)...';
                break;
            case 'sakit':
                actionLabel = 'Sakit (S)';
                actionColor = '#6366f1';
                iconHtml = '<i class="fas fa-notes-medical me-1"></i>';
                needNote = true;
                notePlaceholder = 'Keterangan sakit / surat dokter...';
                break;
            case 'izin':
                actionLabel = 'Izin (I)';
                actionColor = '#3b82f6';
                iconHtml = '<i class="fas fa-envelope me-1"></i>';
                needNote = true;
                notePlaceholder = 'Keperluan izin...';
                break;
            case 'pulang':
                actionLabel = 'Presensi Kepulangan';
                actionColor = '#8b5cf6';
                iconHtml = '<i class="fas fa-arrow-right-from-bracket me-1"></i>';
                break;
        }

        Swal.fire({
            title: `Catat Presensi Manual?`,
            html: `
                <div style="text-align: left; font-size: 0.88rem; color: var(--text-color);">
                    <p style="margin-bottom: 8px;"><strong>Nama Peserta Didik:</strong> ${nama}</p>
                    <p style="margin-bottom: 8px;"><strong>Tanggal:</strong> ${tanggal}</p>
                    <p style="margin-bottom: 12px;"><strong>Tindakan:</strong> <span class="badge" style="background: ${actionColor}; color: #fff;">${actionLabel}</span></p>
                    <div style="background: rgba(245, 158, 11, 0.1); border: 1px solid rgba(245, 158, 11, 0.3); border-radius: 8px; padding: 10px 12px; margin-bottom: 14px; font-size: 0.78rem; color: #f59e0b;">
                        <i class="fas fa-triangle-exclamation me-1"></i> <strong>Perhatian:</strong> Tindakan presensi manual hanya dapat dicatat <strong>1 kali</strong> per peserta didik hari ini dan <strong>tidak dapat diubah kembali</strong>.
                    </div>
                    <label style="display:block; margin-bottom: 5px; font-weight: 600; font-size: 0.8rem;">Keterangan Manual ${needNote ? '<span style="color: var(--danger); font-weight: bold;">*</span>' : '(Opsional)'}:</label>
                    <input type="text" id="swalManualKeterangan" class="form-control" style="width: 100%; font-size: 0.84rem;" placeholder="${notePlaceholder}">
                </div>
            `,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: `${iconHtml} Simpan Presensi`,
            cancelButtonText: 'Batal',
            confirmButtonColor: actionColor,
            preConfirm: () => {
                const ket = document.getElementById('swalManualKeterangan')?.value.trim();
                if (needNote && !ket) {
                    Swal.showValidationMessage('Keterangan wajib diisi untuk izin atau sakit.');
                    return false;
                }
                return { keterangan: ket };
            }
        }).then(async (result) => {
            if (result.isConfirmed) {
                const payload = {
                    peserta_didik_id: pdId,
                    action: action,
                    tanggal: tanggal,
                    keterangan: result.value?.keterangan || ''
                };

                try {
                    Swal.fire({
                        title: 'Mencatat Presensi...',
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
                        body: JSON.stringify(payload)
                    });

                    const data = await res.json();

                    if (res.ok && data.status === 'success') {
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
                            title: 'Presensi Ditolak',
                            text: data.message || 'Gagal mencatat presensi manual.'
                        });
                    }
                } catch (e) {
                    Swal.fire('Kesalahan Sistem', 'Tidak dapat menghubungi server.', 'error');
                }
            }
        });
    });

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
