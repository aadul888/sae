/**
 * Script Modul Surat Keluar & Pembuatan Surat Keterangan Siswa Aktif — SAE
 */

document.addEventListener('DOMContentLoaded', function () {
    const container = document.getElementById('suratKeluarContainer');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || window.CSRF_TOKEN || '';
    const cetakSuratKetId = container?.dataset?.cetakId || window.CETAK_SURAT_KET_ID || '';
    const suratKetCetakBaseUrl = container?.dataset?.cetakBaseUrl || window.SURAT_KET_CETAK_BASE_URL || '';
    const suratKeluarStoreUrl = container?.dataset?.storeUrl || window.SURAT_KELUAR_STORE_URL || '';
    const suratKeluarBaseUrl = container?.dataset?.baseUrl || window.SURAT_KELUAR_BASE_URL || '';
    const suratKeluarNextNumberUrl = container?.dataset?.nextNumberUrl || window.SURAT_KELUAR_NEXT_NUMBER_URL || '';
    const suratKeluarSearchSiswaUrl = container?.dataset?.searchSiswaUrl || window.SURAT_KELUAR_SEARCH_SISWA_URL || '';
    const suratKetStoreUrl = container?.dataset?.storeKetUrl || window.SURAT_KET_STORE_URL || '';

    // 1. Notifikasi Cetak Langsung Setelah Surat Keterangan Diterbitkan
    if (cetakSuratKetId && suratKetCetakBaseUrl) {
        Swal.fire({
            icon: 'success',
            title: 'Surat Keterangan Berhasil Diterbitkan!',
            text: 'Dokumen dan QR Code verifikasi telah siap. Apakah Anda ingin langsung mencetak lembar resmi sekarang?',
            showCancelButton: true,
            confirmButtonColor: '#6366f1',
            cancelButtonColor: '#6b7280',
            confirmButtonText: '<i class="fas fa-print me-1"></i> Cetak Sekarang',
            cancelButtonText: 'Nanti Saja'
        }).then(res => {
            if (res.isConfirmed) {
                window.open(suratKetCetakBaseUrl + '/' + cetakSuratKetId + '/cetak', '_blank');
            }
        });
    }

    // 2. Modal Catat / Edit Surat Keluar Umum
    const modalSuratKeluar = document.getElementById('modalSuratKeluar');
    const formSuratKeluar = document.getElementById('formSuratKeluar');
    const modalKeluarTitle = document.getElementById('modalKeluarTitle');
    const methodSpoofKeluar = document.getElementById('methodSpoofKeluar');
    const inputNomorSuratKeluar = document.getElementById('inputNomorSuratKeluar');
    const selectKodeIndeksKeluar = document.getElementById('selectKodeIndeksKeluar');
    const inputPerihalKeluar = document.getElementById('inputPerihalKeluar');
    const inputTujuanKeluar = document.getElementById('inputTujuanKeluar');
    const inputTanggalSuratKeluar = document.getElementById('inputTanggalSuratKeluar');
    const selectStatusKeluar = document.getElementById('selectStatusKeluar');
    const inputKeteranganKeluar = document.getElementById('inputKeteranganKeluar');
    const fileInfoExistingKeluar = document.getElementById('fileInfoExistingKeluar');
    const btnAutoNumberKeluar = document.getElementById('btnAutoNumberKeluar');

    const btnOpenCreateKeluar = document.getElementById('btnOpenCreateKeluar');
    const btnCloseModalKeluar = document.getElementById('btnCloseModalKeluar');
    const btnCancelModalKeluar = document.getElementById('btnCancelModalKeluar');

    function openKeluarModal(isEdit = false, data = {}) {
        if (!modalSuratKeluar) return;

        if (isEdit) {
            modalKeluarTitle.innerText = 'Edit Data Surat Keluar';
            formSuratKeluar.action = suratKeluarBaseUrl + '/' + data.id;
            methodSpoofKeluar.value = 'PUT';

            inputNomorSuratKeluar.value = data.nomor_surat || '';
            if (selectKodeIndeksKeluar) selectKodeIndeksKeluar.value = data.kode_indeks || '';
            inputPerihalKeluar.value = data.perihal || '';
            inputTujuanKeluar.value = data.tujuan_penerima || '';
            inputTanggalSuratKeluar.value = data.tanggal_surat || '';
            if (selectStatusKeluar) selectStatusKeluar.value = data.status || 'selesai';
            inputKeteranganKeluar.value = data.keterangan || '';

            if (fileInfoExistingKeluar) {
                if (data.file_path) {
                    fileInfoExistingKeluar.style.display = 'block';
                    fileInfoExistingKeluar.innerHTML = `<i class="fas fa-file-pdf text-danger me-1"></i> Berkas saat ini: <strong>${data.file_name_original || 'Dokumen Arsip'}</strong> (Unggah berkas baru untuk mengganti)`;
                } else {
                    fileInfoExistingKeluar.style.display = 'none';
                }
            }
        } else {
            modalKeluarTitle.innerText = 'Catat Surat Keluar Baru';
            formSuratKeluar.action = suratKeluarStoreUrl;
            methodSpoofKeluar.value = 'POST';

            inputNomorSuratKeluar.value = '';
            if (selectKodeIndeksKeluar) {
                const defaultIdx = selectKodeIndeksKeluar.value || 'KPG.11.01';
                fetchAutoNumber(defaultIdx);
            }
            inputPerihalKeluar.value = '';
            inputTujuanKeluar.value = '';
            inputTanggalSuratKeluar.value = new Date().toISOString().split('T')[0];
            if (selectStatusKeluar) selectStatusKeluar.value = 'selesai';
            inputKeteranganKeluar.value = '';

            if (fileInfoExistingKeluar) fileInfoExistingKeluar.style.display = 'none';
        }

        modalSuratKeluar.style.display = 'flex';
        document.body.classList.add('modal-open');
    }

    function closeKeluarModal() {
        if (!modalSuratKeluar) return;
        modalSuratKeluar.style.display = 'none';
        document.body.classList.remove('modal-open');
    }

    function fetchAutoNumber(kodeIndeks) {
        if (!inputNomorSuratKeluar || !suratKeluarNextNumberUrl) return;
        fetch(suratKeluarNextNumberUrl + '?type=keluar&kode_indeks=' + encodeURIComponent(kodeIndeks))
            .then(res => res.json())
            .then(data => {
                if (data.success && data.nomor_surat) {
                    inputNomorSuratKeluar.value = data.nomor_surat;
                }
            })
            .catch(() => {});
    }

    if (selectKodeIndeksKeluar) {
        selectKodeIndeksKeluar.addEventListener('change', function () {
            if (selectKodeIndeksKeluar.value) {
                fetchAutoNumber(selectKodeIndeksKeluar.value);
            }
        });
    }

    if (btnAutoNumberKeluar) {
        btnAutoNumberKeluar.addEventListener('click', function () {
            const idx = selectKodeIndeksKeluar ? selectKodeIndeksKeluar.value : 'KPG.11.01';
            fetchAutoNumber(idx || 'KPG.11.01');
        });
    }

    if (btnOpenCreateKeluar) btnOpenCreateKeluar.addEventListener('click', () => openKeluarModal(false));
    if (btnCloseModalKeluar) btnCloseModalKeluar.addEventListener('click', closeKeluarModal);
    if (btnCancelModalKeluar) btnCancelModalKeluar.addEventListener('click', closeKeluarModal);

    // 3. Modal Pembuatan Surat Keterangan Siswa Aktif
    const modalSuratKeterangan = document.getElementById('modalSuratKeterangan');
    const formSuratKeterangan = document.getElementById('formSuratKeterangan');
    const selectKodeIndeksKet = document.getElementById('selectKodeIndeksKet');
    const btnAutoNumberKet = document.getElementById('btnAutoNumberKet');
    const searchSiswaInput = document.getElementById('searchSiswaInput');
    const siswaSearchResults = document.getElementById('siswaSearchResults');
    const selectedSiswaCard = document.getElementById('selectedSiswaCard');
    const selectedSiswaNama = document.getElementById('selectedSiswaNama');
    const selectedSiswaNisn = document.getElementById('selectedSiswaNisn');
    const selectedSiswaKelas = document.getElementById('selectedSiswaKelas');
    const inputPesertaDidikId = document.getElementById('inputPesertaDidikId');
    const inputNomorSuratKet = document.getElementById('inputNomorSuratKet');
    const selectKeperluanPreset = document.getElementById('selectKeperluanPreset');
    const inputKeperluan = document.getElementById('inputKeperluan');
    const btnOpenCreateKet = document.getElementById('btnOpenCreateKet');
    const btnCloseModalKet = document.getElementById('btnCloseModalKet');
    const btnCancelModalKet = document.getElementById('btnCancelModalKet');
    const btnChangeSiswa = document.getElementById('btnChangeSiswa');

    function fetchAutoNumberKet(kodeIndeks) {
        if (!inputNomorSuratKet || !suratKeluarNextNumberUrl) return;
        fetch(suratKeluarNextNumberUrl + '?type=keterangan&kode_indeks=' + encodeURIComponent(kodeIndeks))
            .then(res => res.json())
            .then(data => {
                if (data.success && data.nomor_surat) {
                    inputNomorSuratKet.value = data.nomor_surat;
                }
            })
            .catch(() => {});
    }

    if (selectKodeIndeksKet) {
        selectKodeIndeksKet.addEventListener('change', function () {
            if (selectKodeIndeksKet.value) {
                fetchAutoNumberKet(selectKodeIndeksKet.value);
            }
        });
    }

    if (btnAutoNumberKet) {
        btnAutoNumberKet.addEventListener('click', function () {
            const idx = selectKodeIndeksKet ? selectKodeIndeksKet.value : 'KS.02.23';
            fetchAutoNumberKet(idx || 'KS.02.23');
        });
    }

    function openKetModal() {
        if (!modalSuratKeterangan) return;

        formSuratKeterangan.action = suratKetStoreUrl;
        if (inputPesertaDidikId) inputPesertaDidikId.value = '';
        if (searchSiswaInput) searchSiswaInput.value = '';
        if (selectedSiswaCard) selectedSiswaCard.style.display = 'none';
        if (siswaSearchResults) siswaSearchResults.style.display = 'none';
        if (inputKeperluan) inputKeperluan.value = '';
        if (selectKeperluanPreset) selectKeperluanPreset.value = '';

        if (selectKodeIndeksKet) {
            const defaultKetIdx = selectKodeIndeksKet.value || 'KS.02.23';
            fetchAutoNumberKet(defaultKetIdx);
        }

        modalSuratKeterangan.style.display = 'flex';
        document.body.classList.add('modal-open');
        setTimeout(() => searchSiswaInput && searchSiswaInput.focus(), 150);
    }

    function closeKetModal() {
        if (!modalSuratKeterangan) return;
        modalSuratKeterangan.style.display = 'none';
        document.body.classList.remove('modal-open');
    }

    if (btnOpenCreateKet) btnOpenCreateKet.addEventListener('click', openKetModal);
    if (btnCloseModalKet) btnCloseModalKet.addEventListener('click', closeKetModal);
    if (btnCancelModalKet) btnCancelModalKet.addEventListener('click', closeKetModal);

    // Preset Keperluan Surat Keterangan
    if (selectKeperluanPreset && inputKeperluan) {
        selectKeperluanPreset.addEventListener('change', function () {
            if (selectKeperluanPreset.value) {
                inputKeperluan.value = selectKeperluanPreset.value;
            }
        });
    }

    // Live Search Autocomplete Siswa Dapodik
    let searchDebounceTimer = null;
    if (searchSiswaInput) {
        searchSiswaInput.addEventListener('input', function () {
            clearTimeout(searchDebounceTimer);
            const q = searchSiswaInput.value.trim();

            if (q.length < 2) {
                if (siswaSearchResults) siswaSearchResults.style.display = 'none';
                return;
            }

            searchDebounceTimer = setTimeout(() => {
                fetch(window.SURAT_KELUAR_SEARCH_SISWA_URL + '?q=' + encodeURIComponent(q))
                    .then(res => res.json())
                    .then(data => {
                        if (!siswaSearchResults) return;
                        siswaSearchResults.innerHTML = '';

                        if (data.results && data.results.length > 0) {
                            data.results.forEach(s => {
                                const item = document.createElement('div');
                                item.className = 'search-result-item';
                                item.style.cssText = 'padding: 10px 14px; border-bottom: 1px solid var(--border-color); cursor: pointer; display: flex; justify-content: space-between; align-items: center; transition: background 0.15s ease;';
                                item.innerHTML = `
                                    <div>
                                        <div style="font-weight: 700; font-size: 0.86rem; color: var(--text-color);">${s.nama}</div>
                                        <div style="font-size: 0.74rem; color: var(--text-muted);">
                                            NISN: <span style="font-family: monospace;">${s.nisn || '-'}</span> | Kelas: <strong>${s.rombel_nama || '-'}</strong> (${s.jurusan_id_str || '-'})
                                        </div>
                                    </div>
                                    <span class="badge-compact badge-primary" style="font-size: 0.72rem;">Pilih</span>
                                `;

                                item.addEventListener('mouseenter', () => item.style.background = 'var(--bg-hover)');
                                item.addEventListener('mouseleave', () => item.style.background = 'transparent');

                                item.addEventListener('click', () => {
                                    inputPesertaDidikId.value = s.peserta_didik_id;
                                    selectedSiswaNama.innerText = s.nama;
                                    selectedSiswaNisn.innerText = s.nisn || '-';
                                    selectedSiswaKelas.innerText = (s.rombel_nama || '-') + ' (' + (s.jurusan_id_str || '-') + ')';

                                    searchSiswaInput.value = s.nama;
                                    siswaSearchResults.style.display = 'none';
                                    selectedSiswaCard.style.display = 'block';
                                });

                                siswaSearchResults.appendChild(item);
                            });
                            siswaSearchResults.style.display = 'block';
                        } else {
                            siswaSearchResults.innerHTML = '<div style="padding: 12px 14px; font-size: 0.8rem; color: var(--text-muted); text-align: center;">Tidak ada siswa ditemukan dengan kata kunci tersebut.</div>';
                            siswaSearchResults.style.display = 'block';
                        }
                    })
                    .catch(() => {});
            }, 300);
        });
    }

    if (btnChangeSiswa) {
        btnChangeSiswa.addEventListener('click', function () {
            inputPesertaDidikId.value = '';
            selectedSiswaCard.style.display = 'none';
            searchSiswaInput.value = '';
            searchSiswaInput.focus();
        });
    }

    // 4. Modal Preview Berkas Dokumen dari HDD
    const modalPreview = document.getElementById('modalPreviewDokumen');
    const previewFrame = document.getElementById('previewIframe');
    const previewImage = document.getElementById('previewImage');
    const previewTitle = document.getElementById('previewTitle');
    const previewDownloadBtn = document.getElementById('previewDownloadBtn');
    const btnClosePreview = document.getElementById('btnClosePreview');

    function openPreviewModal(url, downloadUrl, title, isImage = false) {
        if (!modalPreview) return;

        if (previewTitle) previewTitle.innerText = title || 'Pratinjau Dokumen Arsip';
        if (previewDownloadBtn) previewDownloadBtn.href = downloadUrl || url;

        if (isImage) {
            if (previewFrame) previewFrame.style.display = 'none';
            if (previewImage) {
                previewImage.style.display = 'block';
                previewImage.src = url;
            }
        } else {
            if (previewImage) previewImage.style.display = 'none';
            if (previewFrame) {
                previewFrame.style.display = 'block';
                previewFrame.src = url;
            }
        }

        modalPreview.style.display = 'flex';
        document.body.classList.add('modal-open');
    }

    function closePreviewModal() {
        if (!modalPreview) return;
        if (previewFrame) previewFrame.src = 'about:blank';
        if (previewImage) previewImage.src = '';
        modalPreview.style.display = 'none';
        document.body.classList.remove('modal-open');
    }

    if (btnClosePreview) btnClosePreview.addEventListener('click', closePreviewModal);

    // 5. Event Delegation: Edit, Preview, dan Delete
    document.addEventListener('click', function (e) {
        // Tombol Edit Surat Keluar
        const btnEdit = e.target.closest('.btn-edit-keluar');
        if (btnEdit) {
            const data = {
                id: btnEdit.dataset.id,
                nomor_surat: btnEdit.dataset.nomor,
                kode_indeks: btnEdit.dataset.indeks,
                perihal: btnEdit.dataset.perihal,
                tujuan_penerima: btnEdit.dataset.tujuan,
                tanggal_surat: btnEdit.dataset.tglSurat,
                status: btnEdit.dataset.status,
                keterangan: btnEdit.dataset.keterangan,
                file_path: btnEdit.dataset.filePath,
                file_name_original: btnEdit.dataset.fileName
            };
            openKeluarModal(true, data);
            return;
        }

        // Tombol Preview Dokumen
        const btnView = e.target.closest('.btn-view-dokumen');
        if (btnView) {
            const viewUrl = btnView.dataset.viewUrl;
            const downloadUrl = btnView.dataset.downloadUrl;
            const title = btnView.dataset.title;
            const isImage = (btnView.dataset.mime || '').includes('image');

            openPreviewModal(viewUrl, downloadUrl, title, isImage);
            return;
        }
    });

    // 6. Konfirmasi Hapus Surat Keluar dengan SweetAlert2
    document.addEventListener('submit', function (e) {
        const form = e.target;
        if (form.getAttribute('data-confirm') === 'delete') {
            e.preventDefault();
            const name = form.getAttribute('data-name') || 'Surat ini';

            Swal.fire({
                title: 'Hapus Surat Keluar?',
                text: `Apakah Anda yakin ingin menghapus "${name}" beserta arsip dokumennya di harddisk? Tindakan ini permanen.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Ya, Hapus',
                cancelButtonText: 'Batal'
            }).then(result => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        }
    });
});
