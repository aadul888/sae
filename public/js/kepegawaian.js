/**
 * SAE - Kepegawaian GTK (Berkas Digital, KGB Tracker, Cuti & SPT)
 * Mematuhi Standar Pemisahan JS & Blade SAE
 */

document.addEventListener('DOMContentLoaded', function () {
    // 1. Modal Upload Berkas GTK
    const modalUpload = document.getElementById('modalUploadBerkas');
    const btnOpenUpload = document.getElementById('btnOpenModalUploadBerkas');
    const btnCloseUpload = document.getElementById('btnCloseModalUpload');
    const btnCancelUpload = document.getElementById('btnCancelModalUpload');
    const selectUploadPtk = document.getElementById('upload_ptk_id');

    if (btnOpenUpload && modalUpload) {
        btnOpenUpload.addEventListener('click', function () {
            modalUpload.style.display = 'flex';
        });
    }

    [btnCloseUpload, btnCancelUpload].forEach(btn => {
        if (btn && modalUpload) {
            btn.addEventListener('click', function () {
                modalUpload.style.display = 'none';
            });
        }
    });

    if (modalUpload) {
        modalUpload.addEventListener('click', function (e) {
            if (e.target === modalUpload) {
                modalUpload.style.display = 'none';
            }
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
            }
        });
    });

    // 2. Modal KGB Tracker
    const modalKgb = document.getElementById('modalKgb');
    const btnOpenKgb = document.getElementById('btnOpenModalKgb');
    const btnCloseKgb = document.getElementById('btnCloseModalKgb');
    const btnCancelKgb = document.getElementById('btnCancelModalKgb');
    const formKgb = document.getElementById('formKgb');

    if (btnOpenKgb && modalKgb) {
        btnOpenKgb.addEventListener('click', function () {
            if (formKgb) formKgb.reset();
            modalKgb.style.display = 'flex';
        });
    }

    [btnCloseKgb, btnCancelKgb].forEach(btn => {
        if (btn && modalKgb) {
            btn.addEventListener('click', function () {
                modalKgb.style.display = 'none';
            });
        }
    });

    if (modalKgb) {
        modalKgb.addEventListener('click', function (e) {
            if (e.target === modalKgb) {
                modalKgb.style.display = 'none';
            }
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

            if (modalKgb) modalKgb.style.display = 'flex';
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

    // 3. Modal Cuti & Tugas Dinas
    const modalCuti = document.getElementById('modalCuti');
    const btnOpenCuti = document.getElementById('btnOpenModalCuti');
    const btnCloseCuti = document.getElementById('btnCloseModalCuti');
    const btnCancelCuti = document.getElementById('btnCancelModalCuti');
    const formCuti = document.getElementById('formCuti');

    if (btnOpenCuti && modalCuti) {
        btnOpenCuti.addEventListener('click', function () {
            if (formCuti) formCuti.reset();
            modalCuti.style.display = 'flex';
        });
    }

    [btnCloseCuti, btnCancelCuti].forEach(btn => {
        if (btn && modalCuti) {
            btn.addEventListener('click', function () {
                modalCuti.style.display = 'none';
            });
        }
    });

    if (modalCuti) {
        modalCuti.addEventListener('click', function (e) {
            if (e.target === modalCuti) {
                modalCuti.style.display = 'none';
            }
        });
    }

    // 4. Konfirmasi SweetAlert2 untuk form hapus berkas
    document.querySelectorAll('form[data-confirm="delete"]').forEach(form => {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            const name = this.dataset.name || 'berkas ini';

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
        });
    });
});
