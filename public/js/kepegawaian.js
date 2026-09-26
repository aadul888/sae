/**
 * SAE - Kepegawaian GTK (Pegawai Guru, Pegawai Tendik, KGB Tracker, Cuti & Izin)
 * Mematuhi Standar Pemisahan JS & Blade SAE (Zero inline script)
 */

document.addEventListener('DOMContentLoaded', function () {
    // 1. Live Search & PerPage & Filter Handler
    const searchInput = document.getElementById('liveSearch');
    const clearBtn = document.getElementById('clearSearch');
    const perPageSelect = document.getElementById('perPageSelect');
    const filterJenis = document.getElementById('filterJenis');
    const filterStatus = document.getElementById('filterStatus');
    const filterGender = document.getElementById('filterGender');

    function applyFilter() {
        const url = new URL(window.location.href);

        if (searchInput && searchInput.value.trim()) {
            url.searchParams.set("q", searchInput.value.trim());
        } else {
            url.searchParams.delete("q");
        }

        if (filterJenis && filterJenis.value) {
            url.searchParams.set("jenis_ptk", filterJenis.value);
        } else {
            url.searchParams.delete("jenis_ptk");
        }

        if (filterStatus && filterStatus.value) {
            url.searchParams.set("status", filterStatus.value);
        } else {
            url.searchParams.delete("status");
        }

        if (filterGender && filterGender.value) {
            url.searchParams.set("gender", filterGender.value);
        } else {
            url.searchParams.delete("gender");
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

    if (filterJenis) filterJenis.addEventListener('change', applyFilter);
    if (filterStatus) filterStatus.addEventListener('change', applyFilter);
    if (filterGender) filterGender.addEventListener('change', applyFilter);

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

    // 6. Modal Biodata Lengkap Pegawai GTK
    const modalPegawai = document.getElementById('pegawaiModal');
    const pegawaiLoading = document.getElementById('pegawaiLoading');
    const pegawaiContent = document.getElementById('pegawaiContent');

    window.closeBiodataPegawaiModal = function () {
        if (!modalPegawai) return;
        modalPegawai.style.display = 'none';
        document.body.style.overflow = '';
    };

    window.openBiodataPegawaiModal = function (ptkId) {
        if (!modalPegawai) return;
        modalPegawai.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        if (pegawaiLoading) pegawaiLoading.style.display = 'block';
        if (pegawaiContent) pegawaiContent.style.display = 'none';

        fetch('/manajemen-data/guru-aktif/' + ptkId)
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success' && data.data) {
                    const d = data.data;
                    const setText = (id, val) => {
                        const el = document.getElementById(id);
                        if (el) el.textContent = val || '-';
                    };

                    setText('pegawaiNama', d.nama);
                    setText('pegawaiNuptk', (d.nuptk || '-') + (d.nip ? ' / NIP: ' + d.nip : ''));
                    setText('pegawaiNik', d.nik);
                    setText('pegawaiGender', d.jenis_kelamin === 'L' ? 'Laki-laki (L)' : (d.jenis_kelamin === 'P' ? 'Perempuan (P)' : '-'));
                    setText('pegawaiTtl', (d.tempat_lahir || '') + (d.tanggal_lahir ? ', ' + d.tanggal_lahir : '-'));
                    setText('pegawaiAgama', d.agama_id_str || d.agama);
                    setText('pegawaiStatus', d.status_kepegawaian_id_str || d.status_kepegawaian);
                    setText('pegawaiPend', d.pendidikan_terakhir);
                    setText('pegawaiMapel', d.bidang_studi_terakhir || d.jenis_ptk_id_str);
                    setText('pegawaiInduk', (d.ptk_induk ? 'Induk (' + d.ptk_induk + ')' : '-') + (d.tanggal_surat_tugas ? ' • TMT: ' + d.tanggal_surat_tugas : ''));
                    setText('pegawaiHp', (d.no_hp || '-') + (d.email ? ' / ' + d.email : ''));
                    setText('pegawaiAlamat', d.alamat_jalan);

                    const bebanSec = document.getElementById('pegawaiBebanSection');
                    const bebanList = document.getElementById('pegawaiBebanList');
                    const jmlJam = document.getElementById('pegawaiJmlJam');

                    if (data.pembelajaran && data.pembelajaran.length > 0) {
                        if (bebanSec) bebanSec.style.display = 'block';
                        if (jmlJam) jmlJam.textContent = (data.total_jam || 0) + ' JP';
                        if (bebanList) {
                            bebanList.innerHTML = data.pembelajaran.map(p => `
                                <tr>
                                    <td style="padding: 6px 10px;">${p.nama_mata_pelajaran || '-'}</td>
                                    <td style="padding: 6px 10px;">${p.nama_rombel || '-'}</td>
                                    <td style="padding: 6px 10px; text-align: center;">${p.jam_mengajar_per_minggu || 0} JP</td>
                                </tr>
                            `).join('');
                        }
                    } else {
                        if (bebanSec) bebanSec.style.display = 'none';
                    }

                    if (pegawaiLoading) pegawaiLoading.style.display = 'none';
                    if (pegawaiContent) pegawaiContent.style.display = 'block';
                } else {
                    if (pegawaiLoading) pegawaiLoading.innerHTML = '<div style="color: #ef4444; padding: 20px;">Gagal memuat profil pegawai</div>';
                }
            })
            .catch(err => {
                if (pegawaiLoading) pegawaiLoading.innerHTML = '<div style="color: #ef4444; padding: 20px;">Terjadi kesalahan memuat data.</div>';
            });
    };

    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-detail-pegawai');
        if (btn) {
            const id = btn.getAttribute('data-id');
            if (id) window.openBiodataPegawaiModal(id);
        }
    });

    if (modalPegawai) {
        modalPegawai.addEventListener('click', function (e) {
            if (e.target === modalPegawai) window.closeBiodataPegawaiModal();
        });
    }
});
