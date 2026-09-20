/**
 * SAE (Sistem Aplikasi Edukasi) - Modul Surat Izin & Sakit Peserta Didik
 * Logika Javascript Modular Mandiri (Vanilla JS + SweetAlert2)
 */

document.addEventListener('DOMContentLoaded', function () {
    const modalFormIzin = document.getElementById('modalFormIzin');
    const formSubmitIzin = document.getElementById('formSubmitIzin');
    const btnBukaModalIzin = document.getElementById('btnBukaModalIzin');
    const btnCloseModalIzin = document.getElementById('btnCloseModalIzin');
    const btnBatalModalIzin = document.getElementById('btnBatalModalIzin');
    const btnSubmitIzin = document.getElementById('btnSubmitIzin');

    const modalPreviewLampiran = document.getElementById('modalPreviewLampiran');
    const btnClosePreviewLampiran = document.getElementById('btnClosePreviewLampiran');
    const previewContainer = document.getElementById('previewContainer');
    const previewLampiranTitle = document.getElementById('previewLampiranTitle');

    const filterForm = document.getElementById('filterIzinForm');
    const inputSearchIzin = document.getElementById('inputSearchIzin');
    const btnClearSearch = document.getElementById('btnClearSearch');
    const filterJenis = document.getElementById('filterJenis');
    const filterStatus = document.getElementById('filterStatus');
    const filterPerPage = document.getElementById('filterPerPage');

    // 1. Kontrol Modal Form Izin
    function openModalIzin() {
        if (modalFormIzin) {
            modalFormIzin.style.display = 'flex';
            document.body.style.overflow = 'hidden';
            setTimeout(() => {
                const jenisSelect = document.getElementById('modalSelectJenis');
                if (jenisSelect) jenisSelect.focus();
            }, 50);
        }
    }

    function closeModalIzin() {
        if (modalFormIzin) {
            modalFormIzin.style.display = 'none';
            document.body.style.overflow = '';
            if (formSubmitIzin) {
                formSubmitIzin.reset();
            }
        }
    }

    if (btnBukaModalIzin) {
        btnBukaModalIzin.addEventListener('click', openModalIzin);
    }
    if (btnCloseModalIzin) {
        btnCloseModalIzin.addEventListener('click', closeModalIzin);
    }
    if (btnBatalModalIzin) {
        btnBatalModalIzin.addEventListener('click', closeModalIzin);
    }
    window.openModalIzinKeluar = function () {
        const m = document.getElementById('modalFormIzinKeluar');
        if (m) {
            m.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }
    };

    window.closeModalIzinKeluar = function () {
        const m = document.getElementById('modalFormIzinKeluar');
        if (m) {
            m.style.display = 'none';
            document.body.style.overflow = '';
        }
    };

    const modalFormIzinKeluar = document.getElementById('modalFormIzinKeluar');
    if (modalFormIzinKeluar) {
        modalFormIzinKeluar.addEventListener('click', function (e) {
            if (e.target === modalFormIzinKeluar) {
                closeModalIzinKeluar();
            }
        });
    }

    // 2. Kontrol Modal Preview Lampiran
    function openPreviewLampiran(url, title) {
        if (!modalPreviewLampiran || !previewContainer) return;

        if (previewLampiranTitle) {
            previewLampiranTitle.textContent = title || 'Berkas Lampiran';
        }

        const ext = url.split('.').pop().toLowerCase().split('?')[0];
        if (ext === 'pdf') {
            previewContainer.innerHTML = `
                <iframe src="${url}" style="width: 100%; height: 520px; border: 1px solid var(--border-color); border-radius: 8px;"></iframe>
                <div style="margin-top: 10px;">
                    <a href="${url}" target="_blank" class="btn btn-outline btn-sm" download style="font-size: 0.8rem;">
                        <i class="fas fa-download me-1"></i> Unduh Dokumen PDF
                    </a>
                </div>
            `;
        } else {
            previewContainer.innerHTML = `
                <img src="${url}" alt="Lampiran" style="max-width: 100%; max-height: 70vh; border-radius: 8px; object-fit: contain; box-shadow: 0 4px 14px rgba(0,0,0,0.3);">
            `;
        }

        modalPreviewLampiran.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    function closePreviewLampiran() {
        if (modalPreviewLampiran) {
            modalPreviewLampiran.style.display = 'none';
            document.body.style.overflow = '';
            if (previewContainer) previewContainer.innerHTML = '';
        }
    }

    if (btnClosePreviewLampiran) {
        btnClosePreviewLampiran.addEventListener('click', closePreviewLampiran);
    }
    if (modalPreviewLampiran) {
        modalPreviewLampiran.addEventListener('click', function (e) {
            if (e.target === modalPreviewLampiran) {
                closePreviewLampiran();
            }
        });
    }

    // Event delegation untuk tombol preview lampiran di tabel
    document.addEventListener('click', function (e) {
        const btnPreview = e.target.closest('.btn-preview-lampiran');
        if (btnPreview) {
            const url = btnPreview.getAttribute('data-url');
            const title = btnPreview.getAttribute('data-title');
            if (url) {
                openPreviewLampiran(url, title);
            }
        }
    });

    // ESC key untuk menutup modal yang aktif
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            if (modalFormIzin && modalFormIzin.style.display === 'flex') {
                closeModalIzin();
            }
            if (modalPreviewLampiran && modalPreviewLampiran.style.display === 'flex') {
                closePreviewLampiran();
            }
        }
    });

    // 3. Submit Pengajuan Izin via AJAX dengan SweetAlert2
    if (formSubmitIzin) {
        formSubmitIzin.addEventListener('submit', function (e) {
            e.preventDefault();

            const tglMulai = document.getElementById('modalTglMulai').value;
            const tglSelesai = document.getElementById('modalTglSelesai').value;

            if (tglSelesai < tglMulai) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Rentang Tanggal Tidak Valid',
                        text: 'Tanggal selesai tidak boleh sebelum tanggal mulai.',
                        confirmButtonColor: '#6366f1'
                    });
                } else {
                    alert('Tanggal selesai tidak boleh sebelum tanggal mulai.');
                }
                return;
            }

            const formData = new FormData(formSubmitIzin);
            const actionUrl = formSubmitIzin.getAttribute('action');

            if (btnSubmitIzin) {
                btnSubmitIzin.disabled = true;
                btnSubmitIzin.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Mengirim...';
            }

            fetch(actionUrl, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(async response => {
                const data = await response.json();
                if (!response.ok) {
                    throw new Error(data.message || 'Gagal mengirim permohonan.');
                }
                return data;
            })
            .then(res => {
                closeModalIzin();
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil Diajukan',
                        text: res.message || 'Permohonan surat izin berhasil dikirim.',
                        confirmButtonColor: '#6366f1'
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    alert(res.message || 'Permohonan berhasil dikirim.');
                    window.location.reload();
                }
            })
            .catch(err => {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Pengajuan Gagal',
                        text: err.message || 'Terjadi kesalahan sistem saat mengirim permohonan.',
                        confirmButtonColor: '#ef4444'
                    });
                } else {
                    alert('Gagal: ' + err.message);
                }
            })
            .finally(() => {
                if (btnSubmitIzin) {
                    btnSubmitIzin.disabled = false;
                    btnSubmitIzin.innerHTML = '<i class="fas fa-paper-plane me-1"></i> Kirim Permohonan';
                }
            });
        });
    }

    // 4. Pembatalan Pengajuan Surat (Event Delegation dengan SweetAlert2)
    document.addEventListener('click', function (e) {
        const btnCancel = e.target.closest('.btn-cancel-izin');
        if (!btnCancel) return;

        const id = btnCancel.getAttribute('data-id');
        const jenis = btnCancel.getAttribute('data-jenis') || 'Izin';
        if (!id) return;

        const runDelete = () => {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            btnCancel.disabled = true;

            fetch(`/peserta-didik/izin/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(async response => {
                const data = await response.json();
                if (!response.ok) {
                    throw new Error(data.message || 'Gagal membatalkan pengajuan.');
                }
                return data;
            })
            .then(res => {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Pengajuan Dibatalkan',
                        text: res.message || 'Permohonan surat izin berhasil dihapus.',
                        confirmButtonColor: '#6366f1',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    alert(res.message || 'Berhasil dibatalkan.');
                    window.location.reload();
                }
            })
            .catch(err => {
                btnCancel.disabled = false;
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal Membatalkan',
                        text: err.message || 'Terjadi kesalahan pada server.',
                        confirmButtonColor: '#ef4444'
                    });
                } else {
                    alert('Gagal: ' + err.message);
                }
            });
        };

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Batalkan Pengajuan ' + jenis + '?',
                text: 'Permohonan yang dibatalkan akan dihapus dari daftar dan tidak dapat dipulihkan.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Ya, Batalkan!',
                cancelButtonText: 'Tutup'
            }).then((result) => {
                if (result.isConfirmed) {
                    runDelete();
                }
            });
        } else {
            if (confirm('Yakin ingin membatalkan pengajuan ' + jenis + ' ini?')) {
                runDelete();
            }
        }
    });

    // 5. Live Search & Filter Otomatis
    let searchDebounceTimer = null;
    function applyFilter() {
        if (filterForm) {
            filterForm.submit();
        }
    }

    if (inputSearchIzin) {
        inputSearchIzin.addEventListener('input', function () {
            if (btnClearSearch) {
                if (this.value.trim()) {
                    btnClearSearch.classList.add('visible');
                } else {
                    btnClearSearch.classList.remove('visible');
                }
            }
            clearTimeout(searchDebounceTimer);
            searchDebounceTimer = setTimeout(applyFilter, 450);
        });
    }

    if (btnClearSearch) {
        btnClearSearch.addEventListener('click', function () {
            if (inputSearchIzin) inputSearchIzin.value = '';
            btnClearSearch.classList.remove('visible');
            applyFilter();
        });
    }

    if (filterJenis) {
        filterJenis.addEventListener('change', applyFilter);
    }
    if (filterStatus) {
        filterStatus.addEventListener('change', applyFilter);
    }
    if (filterPerPage) {
        filterPerPage.addEventListener('change', applyFilter);
    }
});
