/**
 * Logika JavaScript Modular: Administrasi Kesiswaan (Buku Klaper, Mutasi, Kelulusan)
 * Standar Resmi SAE (Vanilla JS + SweetAlert2)
 */

document.addEventListener('DOMContentLoaded', function () {
    // 1. Modal Helper
    function openModal(selector) {
        const modal = document.querySelector(selector);
        if (modal) {
            modal.style.display = 'flex';
        }
    }

    function closeModal(selector) {
        const modal = document.querySelector(selector);
        if (modal) {
            modal.style.display = 'none';
        }
    }

    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.close-modal');
        if (btn) {
            const target = btn.getAttribute('data-target');
            if (target) closeModal(target);
        }
    });

    // 2. Tombol Buka Modal
    const btnOpenMutasi = document.getElementById('btnOpenMutasiModal');
    if (btnOpenMutasi) {
        btnOpenMutasi.addEventListener('click', () => openModal('#modalMutasi'));
    }

    const btnOpenKelulusan = document.getElementById('btnOpenKelulusanModal');
    if (btnOpenKelulusan) {
        btnOpenKelulusan.addEventListener('click', () => openModal('#modalKelulusan'));
    }

    // 3. Sinkronisasi Buku Klaper
    const btnSyncKlaper = document.getElementById('btnSyncKlaper');
    if (btnSyncKlaper) {
        btnSyncKlaper.addEventListener('click', function () {
            window.SAE.confirm("Data seluruh peserta didik aktif dari Dapodik akan disinkronkan ke Buku Klaper.", "Sinkronkan Buku Klaper?", 'info', "Ya, Sinkronkan", "Batal").then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Memproses...',
                        text: 'Menyinkronkan Buku Klaper...',
                        allowOutsideClick: false,
                        didOpen: () => Swal.showLoading()
                    });

                    fetch('/dashboard/kesiswaan/administrasi/klaper/sync', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                            'Accept': 'application/json',
                        }
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.status === 'success') {
                            Swal.fire('Berhasil!', data.message, 'success').then(() => location.reload());
                        } else {
                            Swal.fire('Gagal!', data.message || 'Terjadi kesalahan.', 'error');
                        }
                    })
                    .catch(err => {
                        Swal.fire('Error!', 'Gagal menghubungi server.', 'error');
                    });
                }
            });
        });
    }

    // 4. Form Submit Mutasi Siswa
    const formMutasi = document.getElementById('formMutasi');
    if (formMutasi) {
        formMutasi.addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = new FormData(this);

            Swal.fire({
                title: 'Memproses...',
                text: 'Menyimpan data mutasi siswa & meneruskan ke modul persuratan...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            fetch(this.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    'Accept': 'application/json',
                },
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire({
                        title: 'Berhasil!',
                        text: data.message,
                        icon: 'success',
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#2563eb'
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('Gagal!', data.message || 'Terjadi kesalahan.', 'error');
                }
            })
            .catch(err => {
                Swal.fire('Error!', 'Gagal menyimpan mutasi.', 'error');
            });
        });
    }

    // 4b. Modal & Submit Unggah Surat Mutasi Dapodik (Tahap 2)
    const modalUploadDapodik = document.getElementById('modalUploadSuratDapodik');
    const formUploadDapodik = document.getElementById('formUploadSuratDapodik');
    const inputUploadMutasiId = document.getElementById('uploadMutasiId');
    const textUploadSiswaNama = document.getElementById('uploadMutasiSiswaNama');
    const textUploadNoSurat = document.getElementById('uploadMutasiNoSurat');
    const inputUploadNoDapodik = document.getElementById('uploadMutasiNoDapodik');

    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-upload-dapodik');
        if (!btn) return;

        const mutasiId = btn.getAttribute('data-id');
        const nama = btn.getAttribute('data-nama') || '-';
        const noSurat = btn.getAttribute('data-nosurat') || '-';
        const suggestedNo = btn.getAttribute('data-suggested-no') || btn.getAttribute('data-nodapodik') || '';

        if (inputUploadMutasiId) inputUploadMutasiId.value = mutasiId;
        if (textUploadSiswaNama) textUploadSiswaNama.textContent = nama;
        if (textUploadNoSurat) textUploadNoSurat.textContent = `No. Surat Permohonan: ${noSurat}`;
        if (inputUploadNoDapodik) inputUploadNoDapodik.value = suggestedNo;

        if (formUploadDapodik) {
            formUploadDapodik.action = `/dashboard/kesiswaan/administrasi/mutasi/${mutasiId}/upload-surat-dapodik`;
        }

        openModal('#modalUploadSuratDapodik');
    });

    if (formUploadDapodik) {
        formUploadDapodik.addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = new FormData(this);

            Swal.fire({
                title: 'Mengunggah Dokumen...',
                text: 'Menyimpan berkas Dapodik & mencatat di Surat Keluar...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            fetch(this.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    'Accept': 'application/json',
                },
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire({
                        title: 'Berhasil!',
                        text: data.message,
                        icon: 'success',
                        showCancelButton: true,
                        confirmButtonText: 'Cetak Surat Mutasi',
                        cancelButtonText: 'Tutup',
                        confirmButtonColor: '#10b981'
                    }).then((result) => {
                        if (result.isConfirmed && data.cetak_url) {
                            window.open(data.cetak_url, '_blank');
                        }
                        location.reload();
                    });
                } else {
                    Swal.fire('Gagal!', data.message || 'Terjadi kesalahan saat mengunggah surat Dapodik.', 'error');
                }
            })
            .catch(err => {
                Swal.fire('Error!', 'Gagal mengunggah berkas surat mutasi Dapodik.', 'error');
            });
        });
    }

    // 4c. Modal Review Dokumen Mutasi & Update Langsung (Surat 1 & Surat 2)
    const reviewModal = document.getElementById('modalReviewDokumenMutasi');
    const reviewIframe = document.getElementById('reviewDokumenIframe');
    const reviewJudul = document.getElementById('reviewDokumenJudul');
    const reviewSub = document.getElementById('reviewDokumenSub');
    const reviewDownloadBtn = document.getElementById('reviewDokumenDownloadBtn');
    const formUpdateBerkas = document.getElementById('formUpdateBerkasMutasi');
    const reviewFileInput = document.getElementById('reviewDokumenFileInput');

    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-review-berkas');
        if (!btn) return;

        const mutasiId = btn.getAttribute('data-id');
        const jenis = btn.getAttribute('data-jenis');
        const nama = btn.getAttribute('data-nama') || 'Peserta Didik';
        const judul = btn.getAttribute('data-judul') || 'Dokumen Mutasi';
        const noSurat = btn.getAttribute('data-nosurat') || '-';
        const fileUrl = btn.getAttribute('data-url');

        if (reviewJudul) reviewJudul.textContent = judul;
        if (reviewSub) reviewSub.textContent = `${nama} • No: ${noSurat}`;
        if (reviewIframe) reviewIframe.src = fileUrl;
        if (reviewDownloadBtn) reviewDownloadBtn.href = fileUrl;
        if (reviewFileInput) reviewFileInput.value = '';

        if (formUpdateBerkas) {
            formUpdateBerkas.action = `/dashboard/kesiswaan/administrasi/mutasi/${mutasiId}/upload-berkas/${jenis}`;
        }

        openModal('#modalReviewDokumenMutasi');
    });

    if (formUpdateBerkas) {
        formUpdateBerkas.addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = new FormData(this);

            Swal.fire({
                title: 'Memperbarui Dokumen...',
                text: 'Mengunggah file PDF baru dan menyelaraskan ke arsip persuratan...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            fetch(this.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    'Accept': 'application/json',
                },
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire('Berhasil!', data.message, 'success').then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('Gagal!', data.message || 'Terjadi kesalahan.', 'error');
                }
            })
            .catch(err => {
                Swal.fire('Error!', 'Gagal memperbarui berkas.', 'error');
            });
        });
    }

    // 4d. Modal & Submit Edit Mutasi Siswa (Data & Berkas S-1/S-2)
    const modalEditMutasi = document.getElementById('modalEditMutasi');
    const formEditMutasi = document.getElementById('formEditMutasi');
    const inputEditMutasiId = document.getElementById('editMutasiId');
    const textEditSiswaSub = document.getElementById('editMutasiSiswaSub');
    const selectEditJenis = document.getElementById('editMutasiJenis');
    const inputEditTanggal = document.getElementById('editMutasiTanggal');
    const inputEditNoPenerimaan = document.getElementById('editMutasiNoPenerimaan');
    const inputEditSekolah = document.getElementById('editMutasiSekolah');
    const inputEditAlasan = document.getElementById('editMutasiAlasan');
    const textareaEditCatatan = document.getElementById('editMutasiCatatan');
    const badgeEditS1 = document.getElementById('editMutasiS1Status');
    const badgeEditS2 = document.getElementById('editMutasiS2Status');

    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-edit-mutasi');
        if (!btn) return;

        const id = btn.getAttribute('data-id');
        const nama = btn.getAttribute('data-nama') || '-';
        const nisn = btn.getAttribute('data-nisn') || '-';
        const rombel = btn.getAttribute('data-rombel') || '-';
        const jenis = btn.getAttribute('data-jenis') || 'Mutasi';
        const tanggal = btn.getAttribute('data-tanggal') || '';
        const noPenerimaan = btn.getAttribute('data-nopenerimaan') || '';
        const sekolah = btn.getAttribute('data-sekolah') || '';
        const alasan = btn.getAttribute('data-alasan') || '';
        const catatan = btn.getAttribute('data-catatan') || '';
        const hasS1 = btn.getAttribute('data-s1') === '1';
        const hasS2 = btn.getAttribute('data-s2') === '1';

        if (inputEditMutasiId) inputEditMutasiId.value = id;
        if (textEditSiswaSub) textEditSiswaSub.textContent = `${nama} • NISN: ${nisn} • Rombel: ${rombel}`;
        if (selectEditJenis) selectEditJenis.value = jenis;
        if (inputEditTanggal) inputEditTanggal.value = tanggal;
        if (inputEditNoPenerimaan) inputEditNoPenerimaan.value = noPenerimaan;
        if (inputEditSekolah) inputEditSekolah.value = sekolah;
        if (inputEditAlasan) inputEditAlasan.value = alasan;
        if (textareaEditCatatan) textareaEditCatatan.value = catatan;

        if (badgeEditS1) {
            badgeEditS1.textContent = hasS1 ? 'Ada Berkas' : 'Kosong';
            badgeEditS1.className = hasS1 ? 'badge badge-success' : 'badge badge-secondary';
        }
        if (badgeEditS2) {
            badgeEditS2.textContent = hasS2 ? 'Ada Berkas' : 'Kosong';
            badgeEditS2.className = hasS2 ? 'badge badge-success' : 'badge badge-secondary';
        }

        if (formEditMutasi) {
            formEditMutasi.action = `/dashboard/kesiswaan/administrasi/mutasi/${id}`;
        }

        openModal('#modalEditMutasi');
    });

    if (formEditMutasi) {
        formEditMutasi.addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = new FormData(this);

            Swal.fire({
                title: 'Menyimpan Perubahan...',
                text: 'Memperbarui data mutasi & menyelaraskan ke arsip persuratan...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            fetch(this.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    'Accept': 'application/json',
                },
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire('Berhasil!', data.message, 'success').then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('Gagal!', data.message || 'Terjadi kesalahan.', 'error');
                }
            })
            .catch(err => {
                Swal.fire('Error!', 'Gagal memperbarui data mutasi.', 'error');
            });
        });
    }

    // 4e. Hapus Data Mutasi Siswa
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-delete-mutasi');
        if (!btn) return;

        const id = btn.getAttribute('data-id');
        const nama = btn.getAttribute('data-nama') || 'siswa ini';

        Swal.fire({
            title: 'Hapus Data Mutasi?',
            html: `Apakah Anda yakin ingin menghapus data mutasi a.n <strong>${nama}</strong>?<br><small style="color:var(--danger)">Berkas lampiran fisik serta arsip surat masuk/keluar terhubung akan ikut dihapus.</small>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Ya, Hapus',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Menghapus...',
                    text: 'Menghapus data mutasi...',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });

                fetch(`/dashboard/kesiswaan/administrasi/mutasi/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        'Accept': 'application/json',
                    }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        Swal.fire('Terhapus!', data.message, 'success').then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire('Gagal!', data.message || 'Gagal menghapus data.', 'error');
                    }
                })
                .catch(err => {
                    Swal.fire('Error!', 'Terjadi kesalahan saat menghapus data.', 'error');
                });
            }
        });
    });

    // 5. Form Submit Kelulusan Siswa
    const formKelulusan = document.getElementById('formKelulusan');
    if (formKelulusan) {
        formKelulusan.addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = new FormData(this);

            Swal.fire({
                title: 'Memproses...',
                text: 'Menyimpan data kelulusan & SKL...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            fetch(this.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    'Accept': 'application/json',
                },
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire({
                        title: 'Berhasil!',
                        text: data.message,
                        icon: 'success',
                        showCancelButton: true,
                        confirmButtonText: 'Cetak SKL',
                        cancelButtonText: 'Selesai',
                        confirmButtonColor: '#2563eb'
                    }).then((res) => {
                        if (res.isConfirmed && data.cetak_url) {
                            window.open(data.cetak_url, '_blank');
                        }
                        location.reload();
                    });
                } else {
                    Swal.fire('Gagal!', data.message || 'Terjadi kesalahan.', 'error');
                }
            })
            .catch(err => {
                Swal.fire('Error!', 'Gagal menyimpan kelulusan.', 'error');
            });
        });
    }

    // 6. Edit Nomor Klaper Inline Dialog
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-edit-klaper');
        if (!btn) return;

        const id = btn.getAttribute('data-id');
        const nama = btn.getAttribute('data-nama');
        const noKlaper = btn.getAttribute('data-nomor-klaper') || '';
        const noInduk = btn.getAttribute('data-nomor-induk') || '';
        const tahun = btn.getAttribute('data-tahun-masuk') || '';
        const status = btn.getAttribute('data-status') || 'aktif';

        Swal.fire({
            title: `Edit Buku Klaper: ${nama}`,
            html: `
                <div style="text-align: left; font-size: 0.88rem;">
                    <div style="margin-bottom: 10px;">
                        <label style="display:block; font-weight:700; margin-bottom:4px;">No. Klaper:</label>
                        <input id="swalNoKlaper" class="swal2-input" value="${noKlaper}" style="width: 100%; margin: 0; box-sizing: border-box;">
                    </div>
                    <div style="margin-bottom: 10px;">
                        <label style="display:block; font-weight:700; margin-bottom:4px;">No. Induk:</label>
                        <input id="swalNoInduk" class="swal2-input" value="${noInduk}" style="width: 100%; margin: 0; box-sizing: border-box;">
                    </div>
                    <div style="margin-bottom: 10px;">
                        <label style="display:block; font-weight:700; margin-bottom:4px;">Tahun Masuk:</label>
                        <input id="swalTahun" type="number" class="swal2-input" value="${tahun}" style="width: 100%; margin: 0; box-sizing: border-box;">
                    </div>
                    <div>
                        <label style="display:block; font-weight:700; margin-bottom:4px;">Status:</label>
                        <select id="swalStatus" class="swal2-select" style="width: 100%; margin: 0; box-sizing: border-box;">
                            <option value="aktif" ${status === 'aktif' ? 'selected' : ''}>Aktif</option>
                            <option value="lulus" ${status === 'lulus' ? 'selected' : ''}>Lulus</option>
                            <option value="mutasi_keluar" ${status === 'mutasi_keluar' ? 'selected' : ''}>Mutasi Keluar</option>
                            <option value="do" ${status === 'do' ? 'selected' : ''}>Drop Out (DO)</option>
                        </select>
                    </div>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: 'Simpan',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#2563eb',
            preConfirm: () => {
                return {
                    nomor_klaper: document.getElementById('swalNoKlaper').value,
                    nomor_induk: document.getElementById('swalNoInduk').value,
                    tahun_masuk: document.getElementById('swalTahun').value,
                    status_klaper: document.getElementById('swalStatus').value,
                };
            }
        }).then((result) => {
            if (result.isConfirmed) {
                fetch(`/dashboard/kesiswaan/administrasi/klaper/${id}`, {
                    method: 'PUT',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(result.value)
                })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        Swal.fire('Berhasil!', data.message, 'success').then(() => location.reload());
                    } else {
                        Swal.fire('Gagal!', data.message || 'Terjadi kesalahan.', 'error');
                    }
                })
                .catch(err => {
                    Swal.fire('Error!', 'Gagal memperbarui klaper.', 'error');
                });
            }
        });
    });

    // 7. Datatable Realtime Live Search & Entri perPage Standar SAE
    const searchInput = document.getElementById("liveSearch");
    const clearBtn = document.getElementById("clearSearch");
    const perPageSelect = document.getElementById("perPageSelect");

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

        url.searchParams.delete("klaper_page");
        url.searchParams.delete("mutasi_page");
        url.searchParams.delete("kelulusan_page");
        url.searchParams.delete("page");

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
});
