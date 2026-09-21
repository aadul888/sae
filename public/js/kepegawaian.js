/**
 * SAE - Kepegawaian GTK (Pegawai Guru, Pegawai Tendik, KGB Tracker, Cuti & Izin)
 * Mematuhi Standar Pemisahan JS & Blade SAE (Zero inline script)
 */

document.addEventListener('DOMContentLoaded', function () {
    // 1. Live Search & PerPage Handler
    const searchInput = document.getElementById('liveSearch');
    const clearBtn = document.getElementById('clearSearch');
    const perPageSelect = document.getElementById('perPageSelect');

    function applyFilter() {
        const url = new URL(window.location.href);

        if (searchInput && searchInput.value.trim()) {
            url.searchParams.set("q", searchInput.value.trim());
        } else {
            url.searchParams.delete("q");
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

    if (perPageSelect) {
        perPageSelect.addEventListener("change", applyFilter);
    }

    // 2. Modal Upload Berkas GTK
    const modalUpload = document.getElementById('modalUploadBerkas');
    const btnOpenUpload = document.getElementById('btnOpenModalUploadBerkas');
    const btnCloseUpload = document.getElementById('btnCloseModalUpload');
    const btnCancelUpload = document.getElementById('btnCancelModalUpload');
    const selectUploadPtk = document.getElementById('upload_ptk_id');

    if (btnOpenUpload && modalUpload) {
        btnOpenUpload.addEventListener('click', function () {
            modalUpload.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        });
    }

    function closeModalUpload() {
        if (!modalUpload) return;
        modalUpload.style.display = 'none';
        document.body.style.overflow = '';
    }

    [btnCloseUpload, btnCancelUpload].forEach(btn => {
        if (btn) btn.addEventListener('click', closeModalUpload);
    });

    if (modalUpload) {
        modalUpload.addEventListener('click', function (e) {
            if (e.target === modalUpload) closeModalUpload();
        });
    }

    // Tombol upload cepat per baris GTK
    document.querySelectorAll('.btn-upload-gtk-berkas').forEach(btn => {
        btn.addEventListener('click', function () {
            const ptkId = this.dataset.id;
            if (selectUploadPtk) {
                selectUploadPtk.value = ptkId;
            }
            if (modalUpload) {
                modalUpload.style.display = 'flex';
                document.body.style.overflow = 'hidden';
            }
        });
    });

    // 3. Modal KGB Tracker
    const modalKgb = document.getElementById('modalKgb');
    const btnOpenKgb = document.getElementById('btnOpenModalKgb');
    const btnCloseKgb = document.getElementById('btnCloseModalKgb');
    const btnCancelKgb = document.getElementById('btnCancelModalKgb');
    const formKgb = document.getElementById('formKgb');

    if (btnOpenKgb && modalKgb) {
        btnOpenKgb.addEventListener('click', function () {
            if (formKgb) formKgb.reset();
            modalKgb.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        });
    }

    function closeModalKgb() {
        if (!modalKgb) return;
        modalKgb.style.display = 'none';
        document.body.style.overflow = '';
    }

    [btnCloseKgb, btnCancelKgb].forEach(btn => {
        if (btn) btn.addEventListener('click', closeModalKgb);
    });

    if (modalKgb) {
        modalKgb.addEventListener('click', function (e) {
            if (e.target === modalKgb) closeModalKgb();
        });
    }

    // Edit KGB baris tertentu
    document.querySelectorAll('.btn-edit-kgb').forEach(btn => {
        btn.addEventListener('click', function () {
            const ptkId = this.dataset.ptkId;
            const tmtTerakhir = this.dataset.tmtTerakhir || '';
            const skTerakhir = this.dataset.skTerakhir || '';
            const tmtBerikutnya = this.dataset.tmtBerikutnya || '';
            const mkTahun = this.dataset.mkTahun || '0';
            const mkBulan = this.dataset.mkBulan || '0';
            const status = this.dataset.status || 'belum_waktunya';
            const catatan = this.dataset.catatan || '';

            const ptkSelect = document.getElementById('kgb_ptk_id');
            if (ptkSelect) ptkSelect.value = ptkId;

            const tmtAkhirInput = document.getElementById('kgb_tmt_lama');
            if (tmtAkhirInput) tmtAkhirInput.value = tmtTerakhir;

            const skAkhirInput = document.getElementById('kgb_sk_terakhir');
            if (skAkhirInput) skAkhirInput.value = skTerakhir;

            const tmtNextInput = document.getElementById('kgb_tmt_baru_target');
            if (tmtNextInput) tmtNextInput.value = tmtBerikutnya;

            const mkThnInput = document.getElementById('kgb_mk_tahun');
            if (mkThnInput) mkThnInput.value = mkTahun;

            const mkBlnInput = document.getElementById('kgb_mk_bulan');
            if (mkBlnInput) mkBlnInput.value = mkBulan;

            const statusInput = document.getElementById('kgb_status_usulan');
            if (statusInput) statusInput.value = status;

            const catInput = document.getElementById('kgb_catatan');
            if (catInput) catInput.value = catatan;

            if (modalKgb) {
                modalKgb.style.display = 'flex';
                document.body.style.overflow = 'hidden';
            }
        });
    });

    // Auto-calculate TMT berikutnya jika TMT terakhir diisi (+2 tahun)
    const inputTmtTerakhir = document.getElementById('kgb_tmt_lama');
    const inputTmtBerikutnya = document.getElementById('kgb_tmt_baru_target');
    if (inputTmtTerakhir && inputTmtBerikutnya) {
        inputTmtTerakhir.addEventListener('change', function () {
            if (this.value && !inputTmtBerikutnya.value) {
                const date = new Date(this.value);
                date.setFullYear(date.getFullYear() + 2);
                inputTmtBerikutnya.value = date.toISOString().split('T')[0];
            }
        });
    }

    // 4. Modal Cuti & Izin
    const modalCuti = document.getElementById('modalCuti');
    const btnOpenCuti = document.getElementById('btnOpenModalCuti');
    const btnCloseCuti = document.getElementById('btnCloseModalCuti');
    const btnCancelCuti = document.getElementById('btnCancelModalCuti');
    const formCuti = document.getElementById('formCuti');

    if (btnOpenCuti && modalCuti) {
        btnOpenCuti.addEventListener('click', function () {
            if (formCuti) formCuti.reset();
            modalCuti.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        });
    }

    function closeModalCuti() {
        if (!modalCuti) return;
        modalCuti.style.display = 'none';
        document.body.style.overflow = '';
    }

    [btnCloseCuti, btnCancelCuti].forEach(btn => {
        if (btn) btn.addEventListener('click', closeModalCuti);
    });

    if (modalCuti) {
        modalCuti.addEventListener('click', function (e) {
            if (e.target === modalCuti) closeModalCuti();
        });
    }

    // 5. Konfirmasi SweetAlert2 untuk form hapus berkas
    document.querySelectorAll('form[data-confirm="delete"]').forEach(form => {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            const name = this.dataset.name || 'berkas ini';

            if (window.Swal) {
                Swal.fire({
                    title: 'Hapus Berkas?',
                    text: `Apakah Anda yakin ingin menghapus "${name}"? Tindakan ini tidak dapat dibatalkan.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Hapus',
                    cancelButtonText: 'Batal',
                    confirmButtonColor: '#ef4444',
                    cancelButtonColor: '#6b7280'
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            } else {
                if (confirm(`Apakah Anda yakin ingin menghapus "${name}"?`)) {
                    form.submit();
                }
            }
        });
    });
});
