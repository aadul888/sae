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
        window.SAE.confirm("Dokumen dan QR Code verifikasi telah siap. Apakah Anda ingin langsung mencetak lembar resmi sekarang?", "Surat Keterangan Berhasil Diterbitkan!", 'success', "<i class=\"fas fa-print me-1\"></i> Cetak Sekarang", "Nanti Saja").then(ok => {
            if (ok) {
                window.open(suratKetCetakBaseUrl + '/' + cetakSuratKetId + '/cetak', '_blank');
            }
        });
    }

    const cetakTemplateId = container?.dataset?.cetakTemplateId || '';
    const templateCetakBaseUrl = container?.dataset?.cetakTemplateBaseUrl || '';

    // Notifikasi Pratinjau Setelah Draf Surat Disimpan di Buat Surat
    if (cetakTemplateId && templateCetakBaseUrl) {
        window.SAE.confirm("Draf naskah dinas berhasil disimpan. Anda dapat meninjau pratinjau sekarang atau langsung mengirimkannya ke Surat Keluar pada kolom Aksi.", "Draf Naskah Tersimpan!", 'info', "<i class=\"fas fa-eye me-1\"></i> Pratinjau Draf", "Tutup").then(ok => {
            if (ok) {
                window.open(templateCetakBaseUrl + '/' + cetakTemplateId + '/cetak', '_blank');
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
    const selectSarprasAsetKeluar = document.getElementById('selectSarprasAsetKeluar');
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
            if (selectSarprasAsetKeluar) selectSarprasAsetKeluar.value = data.sarpras_aset_id || '';
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
            if (selectSarprasAsetKeluar) selectSarprasAsetKeluar.value = '';
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
    const selectJenisSuratKet = document.getElementById('selectJenisSuratKet');
    const sectionPanggilanOrtu = document.getElementById('sectionPanggilanOrtu');
    const modalKetTitle = document.getElementById('modalKetTitle');
    const btnAutoNumberKet = document.getElementById('btnAutoNumberKet');
    const searchSiswaInput = document.getElementById('searchSiswaInput');
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

    if (selectJenisSuratKet) {
        selectJenisSuratKet.addEventListener('change', function () {
            const jenis = selectJenisSuratKet.value;
            if (sectionPanggilanOrtu) {
                sectionPanggilanOrtu.style.display = (jenis === 'panggilan_ortu') ? 'flex' : 'none';
            }

            if (jenis === 'panggilan_ortu') {
                if (modalKetTitle) modalKetTitle.innerText = 'Terbitkan Surat Panggilan Orang Tua / Wali';
                if (inputKeperluan) inputKeperluan.value = 'Konseling & Pembinaan Kedisiplinan Peserta Didik';
            } else if (jenis === 'kelakuan_baik') {
                if (modalKetTitle) modalKetTitle.innerText = 'Terbitkan Surat Keterangan Berkelakuan Baik';
                if (inputKeperluan) inputKeperluan.value = 'Persyaratan Melanjutkan Pendidikan / Kedinasan / Melamar Kerja';
            } else if (jenis === 'rekomendasi') {
                if (modalKetTitle) modalKetTitle.innerText = 'Terbitkan Surat Rekomendasi Siswa';
                if (inputKeperluan) inputKeperluan.value = 'Mengikuti Seleksi Beasiswa / Prestasi Siswa';
            } else {
                if (modalKetTitle) modalKetTitle.innerText = 'Terbitkan Surat Keterangan Siswa Aktif';
                if (inputKeperluan) inputKeperluan.value = '';
            }
        });
    }

    function openKetModal() {
        if (!modalSuratKeterangan) return;

        formSuratKeterangan.action = suratKetStoreUrl;
        if (inputPesertaDidikId) inputPesertaDidikId.value = '';
        if (searchSiswaInput) {
            searchSiswaInput.value = '';
            searchSiswaInput.setCustomValidity('');
            searchSiswaInput.dispatchEvent(new Event('input'));
        }
        if (selectedSiswaCard) selectedSiswaCard.style.display = 'none';
        if (selectJenisSuratKet) selectJenisSuratKet.value = 'siswa_aktif';
        if (sectionPanggilanOrtu) sectionPanggilanOrtu.style.display = 'none';
        if (modalKetTitle) modalKetTitle.innerText = 'Terbitkan Surat Keterangan / Panggilan Siswa';
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

    if (searchSiswaInput) {
        searchSiswaInput.addEventListener('people:selected', function (event) {
            const siswa = event.detail;
            if (selectedSiswaNama) selectedSiswaNama.textContent = siswa.nama;
            if (selectedSiswaNisn) selectedSiswaNisn.textContent = siswa.identifier || '-';
            if (selectedSiswaKelas) selectedSiswaKelas.textContent = siswa.context || '-';
            if (selectedSiswaCard) selectedSiswaCard.style.display = 'block';
        });
    }

    if (btnChangeSiswa) {
        btnChangeSiswa.addEventListener('click', function () {
            inputPesertaDidikId.value = '';
            selectedSiswaCard.style.display = 'none';
            searchSiswaInput.value = '';
            searchSiswaInput.setCustomValidity('');
            searchSiswaInput.dispatchEvent(new Event('input'));
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

    // 5. Event Delegation: Tambah, Edit, Preview, dan Delete
    document.addEventListener('click', function (e) {
        // Tombol Pemilih Jenis Surat (Selector Utama)
        if (e.target.closest('#btnOpenPilihJenisSurat, .btn-open-pilih-jenis')) {
            e.preventDefault();
            openPilihJenisModal();
            return;
        }

        // Tombol Catat Surat Keluar Baru
        if (e.target.closest('#btnOpenCreateKeluar, .btn-open-create-keluar')) {
            e.preventDefault();
            openKeluarModal(false);
            return;
        }

        // Tombol Buat Surat Keterangan Siswa
        if (e.target.closest('#btnOpenCreateKet, .btn-open-create-ket')) {
            e.preventDefault();
            openKetModal();
            return;
        }

        // Tombol Terbitkan Surat Perintah Tugas (SPT)
        if (e.target.closest('#btnOpenModalSpt, .btn-open-modal-spt')) {
            e.preventDefault();
            openSptModal();
            return;
        }

        // Tombol Buat Surat dari Template Autocrat
        if (e.target.closest('#btnOpenModalTemplate, .btn-open-modal-template')) {
            e.preventDefault();
            openTemplateModal();
            return;
        }

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
                sarpras_aset_id: btnEdit.dataset.asetId || '',
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

    // 7. Datatable Realtime Live Search, Entri perPage, Filter Indeks & Status, and Sorting Standar SAE
    const searchInput = document.getElementById("liveSearch");
    const clearBtn = document.getElementById("clearSearch");
    const perPageSelect = document.getElementById("perPageSelect");
    const filterKodeIndeks = document.getElementById("filterKodeIndeks");
    const filterStatus = document.getElementById("filterStatus");

    function applyFilter(overrideParams = {}) {
        if (typeof window.collectDatatableUrl === "function") {
            const tableWrap = document.getElementById("tableDataContainer");
            const targetUrl = window.collectDatatableUrl(tableWrap, overrideParams);
            if (typeof window.refreshLiveTable === "function") {
                window.refreshLiveTable(targetUrl);
            } else {
                window.location.href = targetUrl;
            }
            return;
        }

        const url = new URL(window.location.href);
        if (searchInput && searchInput.value.trim()) {
            url.searchParams.set("q", searchInput.value.trim());
        } else {
            url.searchParams.delete("q");
        }

        if (filterKodeIndeks && filterKodeIndeks.value) {
            url.searchParams.set("kode_indeks", filterKodeIndeks.value);
        } else {
            url.searchParams.delete("kode_indeks");
        }

        if (filterStatus && filterStatus.value) {
            url.searchParams.set("status", filterStatus.value);
        } else {
            url.searchParams.delete("status");
        }

        if (perPageSelect && perPageSelect.value) {
            url.searchParams.set("perPage", perPageSelect.value);
        }

        Object.keys(overrideParams).forEach((k) => {
            const v = overrideParams[k];
            if (v !== null && v !== undefined && v !== "") url.searchParams.set(k, v);
            else url.searchParams.delete(k);
        });

        if (!("page" in overrideParams)) url.searchParams.set("page", "1");

        if (typeof window.refreshLiveTable === "function") {
            window.refreshLiveTable(url.toString());
        } else {
            window.location.href = url.toString();
        }
    }

    // Delegated Change Handler untuk filter Surat Keluar
    document.addEventListener("change", function (e) {
        if (!e.target || e.target.tagName !== "SELECT") return;
        if (
            e.target.closest("#tableDataContainer, .datatable-toolbar, .dash-table-toolbar, .card") &&
            (e.target.id === "filterKodeIndeks" || e.target.id === "filterStatus" || e.target.id === "perPageSelect")
        ) {
            e.preventDefault();
            e.stopPropagation();
            applyFilter();
        }
    });

    // Delegated Sort Click Handler untuk tabel Surat Keluar
    document.addEventListener("click", function (e) {
        const th = e.target.closest(".sortable-th");
        if (!th || !th.closest("#tableDataContainer")) return;
        const sortField = th.getAttribute("data-sort");
        if (!sortField) return;
        e.preventDefault();
        e.stopPropagation();

        const url = new URL(window.location.href);
        const currentSort = url.searchParams.get("sort") || "tanggal_surat";
        const currentDir = url.searchParams.get("sort_dir") || url.searchParams.get("dir") || "desc";
        const newDir = currentSort === sortField && currentDir === "asc" ? "desc" : "asc";
        applyFilter({ sort: sortField, sort_dir: newDir });
    });

    // 5. Handler Modal Surat Perintah Tugas (SPT)
    const modalSpt = document.getElementById('modalSpt');
    const btnOpenModalSpt = document.getElementById('btnOpenModalSpt');
    const btnCloseModalSpt = document.getElementById('btnCloseModalSpt');
    const btnCancelModalSpt = document.getElementById('btnCancelModalSpt');
    const formSpt = document.getElementById('formSpt');
    const selectKodeIndeksSpt = document.getElementById('selectKodeIndeksSpt');
    const inputNomorSpt = document.getElementById('inputNomorSpt');
    const btnAutoNumberSpt = document.getElementById('btnAutoNumberSpt');

    function fetchAutoNumberSpt(kodeIndeks) {
        if (!inputNomorSpt || !suratKeluarNextNumberUrl) return;
        fetch(suratKeluarNextNumberUrl + '?type=keluar&kode_indeks=' + encodeURIComponent(kodeIndeks))
            .then(res => res.json())
            .then(data => {
                if (data.success && data.nomor_surat) {
                    inputNomorSpt.value = data.nomor_surat;
                }
            })
            .catch(() => {});
    }

    if (selectKodeIndeksSpt) {
        selectKodeIndeksSpt.addEventListener('change', function () {
            if (selectKodeIndeksSpt.value) {
                fetchAutoNumberSpt(selectKodeIndeksSpt.value);
            }
        });
    }

    if (btnAutoNumberSpt) {
        btnAutoNumberSpt.addEventListener('click', function () {
            const idx = selectKodeIndeksSpt ? selectKodeIndeksSpt.value : 'KPG.11.01';
            fetchAutoNumberSpt(idx || 'KPG.11.01');
        });
    }

    function openSptModal() {
        if (!modalSpt) return;
        if (formSpt) formSpt.reset();
        const defaultSptIdx = selectKodeIndeksSpt ? selectKodeIndeksSpt.value : 'KPG.11.01';
        fetchAutoNumberSpt(defaultSptIdx || 'KPG.11.01');
        modalSpt.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    if (btnOpenModalSpt && modalSpt) {
        btnOpenModalSpt.addEventListener('click', openSptModal);
    }

    function closeModalSpt() {
        if (!modalSpt) return;
        modalSpt.style.display = 'none';
        document.body.style.overflow = '';
    }

    if (btnCloseModalSpt) btnCloseModalSpt.addEventListener('click', closeModalSpt);
    if (btnCancelModalSpt) btnCancelModalSpt.addEventListener('click', closeModalSpt);

    if (modalSpt) {
        modalSpt.addEventListener('click', function (e) {
            if (e.target === modalSpt) {
                closeModalSpt();
            }
        });
    }

    // 5b. Handler Modal Surat Keluar Template Autocrat
    const modalTemplate = document.getElementById('modalTemplateSurat');
    const btnOpenModalTemplate = document.getElementById('btnOpenModalTemplate');
    const btnCloseModalTemplate = document.getElementById('btnCloseModalTemplate');
    const btnCancelModalTemplate = document.getElementById('btnCancelModalTemplate');
    const formTemplateSurat = document.getElementById('formTemplateSurat');
    const selectTemplateType = document.getElementById('selectTemplateType');
    const inputTmplPerihal = document.getElementById('inputTmplPerihal');
    const inputTmplKodeIndeks = document.getElementById('inputTmplKodeIndeks');
    const inputTmplSalam = document.getElementById('inputTmplSalam');
    const inputTmplPembuka = document.getElementById('inputTmplPembuka');
    const inputTmplPenutup = document.getElementById('inputTmplPenutup');

    function openTemplateModal() {
        if (!modalTemplate) return;
        modalTemplate.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    if (btnOpenModalTemplate && modalTemplate) {
        btnOpenModalTemplate.addEventListener('click', openTemplateModal);
    }

    function closeModalTemplate() {
        if (!modalTemplate) return;
        modalTemplate.style.display = 'none';
        document.body.style.overflow = '';
    }

    // 5c. Master Dictionary Template Naskah Dinas Berbasis 62 Indeks Klasifikasi
    const INDEKS_TEMPLATES = {
        // Kesiswaan
        'KS.02.23': {
            target: 'keterangan',
            icon: 'fa-user-graduate',
            title: 'Surat Keterangan / Panggilan Siswa',
            kategori: 'Kesiswaan',
            desc: 'Membuka formulir penerbitan Surat Keterangan Siswa Aktif, Berkelakuan Baik, Panggilan Ortu (BK), atau Rekomendasi Siswa berbasis data Dapodik.',
            perihal: 'Surat Keterangan Siswa Aktif / Panggilan Siswa'
        },
        // Kepegawaian - SPT
        'KPG.11.01': {
            target: 'spt',
            icon: 'fa-file-contract',
            title: 'Surat Perintah Tugas (SPT) GTK',
            kategori: 'Kepegawaian',
            desc: 'Membuka formulir penerbitan Surat Perintah Tugas resmi: multi-petugas GTK, tanggal tugas, lokasi, dan pembebanan anggaran BOS/Komite.',
            perihal: 'Surat Perintah Tugas (SPT) Pelaksanaan Kegiatan Dinas'
        },
        // Tata Usaha
        'TU.04': {
            target: 'template',
            type: 'undangan',
            icon: 'fa-envelope-open-text',
            title: 'Undangan Rapat & Rakor Kedinasan',
            kategori: 'Tata Usaha',
            perihal: 'Undangan Rapat Koordinasi dan Evaluasi Kinerja Satuan Pendidikan',
            salam: 'Dengan hormat,',
            pembuka: 'Sehubungan dengan agenda evaluasi program kerja serta koordinasi teknis satuan pendidikan, kami mengharapkan kehadiran Bapak/Ibu pada:',
            penutup: 'Mengingat pentingnya agenda tersebut, kami sangat mengharapkan kehadiran Bapak/Ibu tepat pada waktu yang telah ditentukan. Atas perhatian dan kerja samanya, disampaikan terima kasih.',
            hasRincian: true,
            desc: 'Membuka template undangan rapat dinas lengkap dengan rincian hari, waktu, tempat, dan agenda pembahasan.'
        },
        'TU.01': {
            target: 'template',
            type: 'pengantar',
            icon: 'fa-paper-plane',
            title: 'Surat Pengantar Dokumen / Berkas',
            kategori: 'Tata Usaha',
            perihal: 'Pengantar Pengiriman Dokumen / Laporan Resmi Satuan Pendidikan',
            salam: 'Dengan hormat,',
            pembuka: 'Bersama surat ini kami kirimkan dokumen/berkas resmi satuan pendidikan sebagaimana rincian berikut untuk dapat dipergunakan dan ditindaklanjuti sebagaimana mestinya:',
            penutup: 'Demikian surat pengantar ini dibuat untuk diketahui dan dapat dipergunakan sebagaimana mestinya.',
            hasRincian: false,
            desc: 'Membuka template surat pengantar pengiriman berkas/laporan resmi sekolah ke Cabang Dinas atau instansi terkait.'
        },
        // Kepegawaian
        'KP.11.08': {
            target: 'template',
            type: 'rekomendasi',
            icon: 'fa-award',
            title: 'Surat Rekomendasi Kepegawaian',
            kategori: 'Kepegawaian',
            perihal: 'Surat Rekomendasi Kepegawaian Pendidik dan Tenaga Kependidikan',
            salam: 'Dengan hormat,',
            pembuka: 'Yang bertanda tangan di bawah ini Kepala Sekolah menerangkan dan memberikan rekomendasi resmi kepegawaian kepada:',
            penutup: 'Demikian surat rekomendasi ini kami berikan dengan penuh tanggung jawab untuk dipergunakan sebagaimana mestinya.',
            desc: 'Membuka template surat rekomendasi resmi Kepala Sekolah untuk keperluan karir atau studi GTK.'
        },
        'KPG.01': {
            target: 'template',
            type: 'kustom',
            icon: 'fa-users-gear',
            title: 'Usulan Formasi Kebutuhan Pegawai',
            kategori: 'Kepegawaian',
            perihal: 'Penyampaian Usulan Kebutuhan Formasi Pendidik dan Tenaga Kependidikan',
            salam: 'Dengan hormat,',
            pembuka: 'Berdasarkan rekapitulasi data rombongan belajar dan ketersediaan tenaga pendidik, bersama ini kami sampaikan usulan kebutuhan formasi GTK:',
            penutup: 'Demikian usulan formasi kebutuhan ini kami sampaikan, atas perhatian dan tindak lanjutnya diucapkan terima kasih.',
            desc: 'Template naskah usulan formasi kebutuhan guru/tendik ke instansi pembina.'
        },
        'KPG.01.01': {
            target: 'template',
            type: 'pengantar',
            icon: 'fa-id-card-clip',
            title: 'Laporan Analisis Jabatan (Anjab)',
            kategori: 'Kepegawaian',
            perihal: 'Pengantar Dokumen Analisis Jabatan (Anjab) Satuan Pendidikan',
            salam: 'Dengan hormat,',
            pembuka: 'Bersama surat ini kami lampirkan dokumen Analisis Jabatan (Anjab) satuan pendidikan untuk diverifikasi sesuai ketentuan perundang-undangan:',
            penutup: 'Demikian pengantar ini kami sampaikan untuk menjadi bahan telaah dan penetapan formasi kepegawaian.',
            desc: 'Template pengantar penyampaian dokumen Analisis Jabatan (Anjab).'
        },
        'KPG.01.02': {
            target: 'template',
            type: 'pengantar',
            icon: 'fa-business-time',
            title: 'Laporan Analisis Beban Kerja (ABK)',
            kategori: 'Kepegawaian',
            perihal: 'Pengantar Dokumen Analisis Beban Kerja (ABK) Pendidik dan Tendik',
            salam: 'Dengan hormat,',
            pembuka: 'Menindaklanjuti penghitungan beban kerja aparatur dan pendidik, bersama ini kami sampaikan laporan Analisis Beban Kerja (ABK) satuan pendidikan:',
            penutup: 'Demikian dokumen ini kami sampaikan untuk dipergunakan sebagaimana mestinya.',
            desc: 'Template pengantar penyampaian Analisis Beban Kerja (ABK).'
        },
        'KPG.02': {
            target: 'template',
            type: 'kustom',
            icon: 'fa-user-plus',
            title: 'Usulan Pengadaan Pegawai',
            kategori: 'Kepegawaian',
            perihal: 'Permohonan Pengadaan Pendidik dan Tenaga Kependidikan',
            salam: 'Dengan hormat,',
            pembuka: 'Sehubungan dengan adanya kekosongan formasi pada beberapa mata pelajaran dan layanan administrasi, dengan ini kami mengajukan permohonan pengadaan pegawai:',
            penutup: 'Besar harapan kami usulan ini dapat diakomodir guna kelancaran kegiatan belajar mengajar.',
            desc: 'Template permohonan pengadaan penambahan GTK baru.'
        },
        'KPG.02.04.02': {
            target: 'template',
            type: 'rekomendasi',
            icon: 'fa-graduation-cap',
            title: 'Surat Keterangan Keabsahan Ijazah Pegawai',
            kategori: 'Kepegawaian',
            perihal: 'Surat Keterangan Verifikasi Keabsahan Ijazah Pegawai',
            salam: 'Dengan hormat,',
            pembuka: 'Yang bertanda tangan di bawah ini Kepala Sekolah menerangkan bahwa berdasarkan arsip data kepegawaian, dokumen ijazah pegawai berikut adalah sah dan sesuai:',
            penutup: 'Surat keterangan ini diberikan untuk melengkapi berkas administrasi kepegawaian yang bersangkutan.',
            desc: 'Template keterangan verifikasi keabsahan ijazah pegawai.'
        },
        'KPG.03': {
            target: 'template',
            type: 'pemberitahuan',
            icon: 'fa-chalkboard-user',
            title: 'Pemberitahuan Pembinaan Pegawai',
            kategori: 'Kepegawaian',
            perihal: 'Pemberitahuan Pelaksanaan Agenda Pembinaan Kinerja Pegawai',
            salam: 'Dengan hormat,',
            pembuka: 'Dalam rangka peningkatan mutu layanan pendidikan dan evaluasi kinerja aparatur, diberitahukan agenda pembinaan pegawai sebagai berikut:',
            penutup: 'Diharapkan seluruh pegawai yang ditugaskan dapat mengikuti agenda ini dengan seksama dan bertanggung jawab.',
            desc: 'Template surat pembinaan dan peningkatan kinerja GTK.'
        },
        'KPG.03.01.01': {
            target: 'template',
            type: 'kustom',
            icon: 'fa-stamp',
            title: 'Surat Izin / Penugasan Khusus Pegawai',
            kategori: 'Kepegawaian',
            perihal: 'Surat Izin / Penugasan Khusus Pendidik dan Tenaga Kependidikan',
            salam: 'Dengan hormat,',
            pembuka: 'Memberikan izin resmi dan penugasan khusus kepada pegawai yang bersangkutan untuk melaksanakan tugas kedinasan:',
            penutup: 'Surat izin ini berlaku selama masa penugasan dan agar dilaksanakan dengan penuh tanggung jawab.',
            desc: 'Template surat izin resmi atau penugasan khusus pegawai.'
        },
        'KPG.03.03': {
            target: 'template',
            type: 'pengantar',
            icon: 'fa-file-lines',
            title: 'Pengantar Usulan Angka Kredit (DUPAK)',
            kategori: 'Kepegawaian',
            perihal: 'Pengantar Daftar Usul Penetapan Angka Kredit (DUPAK) GTK',
            salam: 'Dengan hormat,',
            pembuka: 'Bersama ini kami kirimkan berkas usulan Penetapan Angka Kredit (PAK) atas nama pegawai berikut untuk dinilai oleh Tim Penilai Angka Kredit:',
            penutup: 'Demikian usulan ini disampaikan, atas bantuan dan kerjasamanya diucapkan terima kasih.',
            desc: 'Template pengantar berkas DUPAK/PAK guru dan tendik.'
        },
        'KPG.03.04': {
            target: 'template',
            type: 'pemberitahuan',
            icon: 'fa-triangle-exclamation',
            title: 'Pemberitahuan Disiplin Pegawai',
            kategori: 'Kepegawaian',
            perihal: 'Surat Pembinaan dan Peringatan Disiplin Pegawai',
            salam: 'Dengan hormat,',
            pembuka: 'Berdasarkan hasil rekapitulasi kehadiran dan evaluasi kedisiplinan kerja, dengan ini disampaikan surat pembinaan kedisiplinan kepada:',
            penutup: 'Surat pembinaan ini diterbitkan untuk diperhatikan dan menjadi bahan perbaikan kinerja ke depan.',
            desc: 'Template surat pembinaan/peringatan disiplin pegawai.'
        },
        'KPG.03.06': {
            target: 'template',
            type: 'rekomendasi',
            icon: 'fa-medal',
            title: 'Usulan Penghargaan dan Tanda Jasa GTK',
            kategori: 'Kepegawaian',
            perihal: 'Usulan Penganugerahan Tanda Kehormatan dan Penghargaan GTK',
            salam: 'Dengan hormat,',
            pembuka: 'Dengan hormat, kami mengajukan usulan penganugerahan tanda jasa dan penghargaan pengabdian atas dedikasi luar biasa pegawai berikut:',
            penutup: 'Demikian usulan penganugerahan tanda jasa ini kami sampaikan untuk dapat dipertimbangkan.',
            desc: 'Template usulan pemberian penghargaan pengabdian GTK.'
        },
        'KPG.04': {
            target: 'template',
            type: 'rekomendasi',
            icon: 'fa-arrows-split-up-and-left',
            title: 'Rekomendasi Mutasi Pegawai',
            kategori: 'Kepegawaian',
            perihal: 'Surat Rekomendasi Permohonan Mutasi Pegawai',
            salam: 'Dengan hormat,',
            pembuka: 'Menindaklanjuti permohonan yang bersangkutan, Kepala Sekolah memberikan rekomendasi atas permohonan alih tugas/mutasi pegawai:',
            penutup: 'Demikian surat rekomendasi ini kami buat untuk dapat diproses sesuai ketentuan peraturan perundang-undangan.',
            desc: 'Template rekomendasi permohonan mutasi pegawai.'
        },
        'KPG.04.01': {
            target: 'template',
            type: 'pengantar',
            icon: 'fa-right-left',
            title: 'Pengantar Alih Status & Pindah Instansi',
            kategori: 'Kepegawaian',
            perihal: 'Pengantar Berkas Usulan Alih Status dan Pindah Instansi Pegawai',
            salam: 'Dengan hormat,',
            pembuka: 'Bersama ini kami sampaikan kelengkapan berkas usulan alih status dan mutasi antar-instansi bagi pegawai bersangkutan:',
            penutup: 'Demikian berkas pengantar ini kami kirimkan untuk proses penetapan lebih lanjut.',
            desc: 'Template pengantar berkas alih status atau mutasi keluar/masuk.'
        },
        'KPG.05.01': {
            target: 'template',
            type: 'rekomendasi',
            icon: 'fa-heart-circle-check',
            title: 'Surat Izin Pernikahan / Perceraian Pegawai',
            kategori: 'Kepegawaian',
            perihal: 'Surat Keterangan / Izin Melangsungkan Pernikahan Pegawai',
            salam: 'Dengan hormat,',
            pembuka: 'Berdasarkan permohonan tertulis dan telaah administrasi kepegawaian, Kepala Sekolah memberikan izin kepada:',
            penutup: 'Demikian surat izin ini diterbitkan untuk dipergunakan sebagaimana ketentuan yang berlaku.',
            desc: 'Template izin pernikahan atau dispensasi status pegawai.'
        },
        'KPG.05.03': {
            target: 'template',
            type: 'pemberitahuan',
            icon: 'fa-circle-xmark',
            title: 'Surat Penolakan Izin Perceraian/Pernikahan',
            kategori: 'Kepegawaian',
            perihal: 'Pemberitahuan Penolakan Permohonan Izin Perceraian Pegawai',
            salam: 'Dengan hormat,',
            pembuka: 'Setelah melalui proses mediasi dan pertimbangan administratif kepegawaian, pimpinan menyampaikan penolakan atas permohonan:',
            penutup: 'Demikian surat penolakan izin ini disampaikan untuk menjadi maklum adanya.',
            desc: 'Template surat penolakan izin perceraian/pernikahan pegawai.'
        },
        'KPG.06': {
            target: 'template',
            type: 'pengantar',
            icon: 'fa-chart-line',
            title: 'Pengantar Usulan Kenaikan Pangkat (UKP)',
            kategori: 'Kepegawaian',
            perihal: 'Pengantar Berkas Usulan Kenaikan Pangkat (UKP) Pendidik dan Tendik',
            salam: 'Dengan hormat,',
            pembuka: 'Bersama ini kami sampaikan berkas usulan kenaikan pangkat/golongan ruang bagi pendidik dan tenaga kependidikan periode berjalan:',
            penutup: 'Demikian pengantar ini disampaikan, atas perhatian dan persetujuannya kami ucapkan terima kasih.',
            desc: 'Template pengantar berkas usulan kenaikan pangkat GTK.'
        },
        'KPG.11': {
            target: 'template',
            type: 'kustom',
            icon: 'fa-id-card',
            title: 'Administrasi Kepegawaian GTK',
            kategori: 'Kepegawaian',
            perihal: 'Surat Keterangan Administrasi dan Status Kepegawaian GTK',
            salam: 'Dengan hormat,',
            pembuka: 'Menerangkan dengan sebenarnya status kelengkapan data administratif kepegawaian bagi pendidik/tendik berikut:',
            penutup: 'Surat keterangan ini dibuat dengan sesungguhnya untuk keperluan kelengkapan arsip kepegawaian.',
            desc: 'Template keterangan administrasi umum pegawai.'
        },
        'KPG.11.02': {
            target: 'template',
            type: 'kustom',
            icon: 'fa-calendar-days',
            title: 'Surat Izin Cuti Besar Pegawai',
            kategori: 'Kepegawaian',
            perihal: 'Surat Izin Pelaksanaan Cuti Besar Pegawai',
            salam: 'Dengan hormat,',
            pembuka: 'Memberikan izin resmi cuti besar kepada pegawai bersangkutan terhitung mulai tanggal yang ditetapkan:',
            penutup: 'Demikian surat izin cuti besar ini diberikan untuk dapat dipergunakan sebagaimana mestinya.',
            desc: 'Template surat izin cuti besar pegawai.'
        },
        'KPG.11.03': {
            target: 'template',
            type: 'kustom',
            icon: 'fa-notes-medical',
            title: 'Surat Izin Cuti Sakit / Melahirkan / Tahunan',
            kategori: 'Kepegawaian',
            perihal: 'Surat Izin Pelaksanaan Cuti Pegawai (Sakit/Bersalin/Tahunan)',
            salam: 'Dengan hormat,',
            pembuka: 'Berdasarkan surat permohonan yang diajukan beserta lampiran keterangan resmi, dengan ini diberikan hak cuti kepada:',
            penutup: 'Demikian surat cuti ini diterbitkan untuk dilaksanakan sebagaimana mestinya.',
            desc: 'Template izin cuti sakit, persalinan, atau cuti tahunan pegawai.'
        },
        'KPG.11.04': {
            target: 'template',
            type: 'kustom',
            icon: 'fa-house-chimney-medical',
            title: 'Surat Izin Cuti Alasan Penting (CAP)',
            kategori: 'Kepegawaian',
            perihal: 'Surat Izin Cuti Alasan Penting (CAP) Pegawai',
            salam: 'Dengan hormat,',
            pembuka: 'Mempertimbangkan permohonan mendesak keluarga pegawai, pimpinan memberikan izin cuti karena alasan penting kepada:',
            penutup: 'Demikian surat izin ini dibuat untuk dipergunakan sebagaimana mestinya.',
            desc: 'Template surat izin cuti karena alasan penting.'
        },
        'KPG.11.05': {
            target: 'template',
            type: 'pengantar',
            icon: 'fa-person-walking-luggage',
            title: 'Pengantar Usulan Cuti di Luar Tanggungan Negara',
            kategori: 'Kepegawaian',
            perihal: 'Pengantar Usulan Cuti di Luar Tanggungan Negara (CLTN)',
            salam: 'Dengan hormat,',
            pembuka: 'Bersama ini kami sampaikan usulan permohonan Cuti di Luar Tanggungan Negara (CLTN) atas nama pegawai berikut untuk diproses lebih lanjut:',
            penutup: 'Demikian permohonan ini disampaikan untuk menjadi pertimbangan pimpinan instansi.',
            desc: 'Template pengantar permohonan rekomendasi CLTN.'
        },
        'KPG.12': {
            target: 'template',
            type: 'pengantar',
            icon: 'fa-folder-open',
            title: 'Pengantar Dokumentasi Identitas Pegawai',
            kategori: 'Kepegawaian',
            perihal: 'Penyampaian Berkas Kelengkapan Dokumentasi Identitas Pegawai',
            salam: 'Dengan hormat,',
            pembuka: 'Bersama surat ini kami sampaikan kelengkapan arsip dan dokumen identitas kepegawaian bagi pegawai berikut:',
            penutup: 'Demikian berkas ini disampaikan untuk pemutakhiran data kepegawaian.',
            desc: 'Template penyampaian dokumen identitas GTK.'
        },
        'KPG.12.01': {
            target: 'template',
            type: 'pengantar',
            icon: 'fa-address-card',
            title: 'Pengantar Usulan Karpeg / KPE / Karis / Karsu',
            kategori: 'Kepegawaian',
            perihal: 'Pengantar Usulan Penerbitan Karpeg / KPE / Karis / Karsu',
            salam: 'Dengan hormat,',
            pembuka: 'Dengan hormat, bersama ini kami sampaikan kelengkapan berkas usulan pembuatan Kartu Pegawai bagi pegawai di bawah ini:',
            penutup: 'Demikian usulan ini disampaikan, atas bantuannya diucapkan terima kasih.',
            desc: 'Template pengantar usulan kartu identitas pegawai.'
        },
        'KPG.14': {
            target: 'template',
            type: 'pemberitahuan',
            icon: 'fa-money-bill-trend-up',
            title: 'Pemberitahuan Kenaikan Gaji Berkala (KGB)',
            kategori: 'Kepegawaian',
            perihal: 'Surat Pemberitahuan Kenaikan Gaji Berkala (KGB) Pegawai',
            salam: 'Dengan hormat,',
            pembuka: 'Diberitahukan bahwa sehubungan telah dipenuhinya masa kerja dan syarat kepangkatan, pegawai berikut berhak memperoleh Kenaikan Gaji Berkala:',
            penutup: 'Demikian surat pemberitahuan KGB ini diterbitkan untuk dipergunakan dalam penyesuaian hak penggajian.',
            desc: 'Template surat pemberitahuan Kenaikan Gaji Berkala (KGB).'
        },
        'KPG.15.02': {
            target: 'template',
            type: 'pengantar',
            icon: 'fa-shield-halved',
            title: 'Pengantar Layanan Asuransi BPJS Pegawai',
            kategori: 'Kepegawaian',
            perihal: 'Pengantar Berkas Pendaftaran dan Layanan Asuransi BPJS GTK',
            salam: 'Dengan hormat,',
            pembuka: 'Bersama ini kami kirimkan berkas administrasi kepesertaan jaminan kesehatan dan ketenagakerjaan bagi GTK berikut:',
            penutup: 'Demikian surat pengantar ini dibuat untuk dapat diproses lebih lanjut.',
            desc: 'Template pengantar pendaftaran atau klaim BPJS pegawai.'
        },
        'KPG.15.08': {
            target: 'template',
            type: 'rekomendasi',
            icon: 'fa-certificate',
            title: 'Piagam Penghargaan & Tanda Jasa GTK',
            kategori: 'Kepegawaian',
            perihal: 'Pemberian Piagam Penghargaan dan Tanda Jasa Kehormatan GTK',
            salam: 'Dengan hormat,',
            pembuka: 'Memberikan penghargaan setinggi-tingginya atas prestasi, dedikasi, serta loyalitas dalam memajukan satuan pendidikan kepada:',
            penutup: 'Piagam ini diberikan sebagai wujud apresiasi resmi dan motivasi dalam mengabdi di dunia pendidikan.',
            desc: 'Template piagam/sertifikat apresiasi dedikasi GTK.'
        },
        'KPG.16.01': {
            target: 'template',
            type: 'pengantar',
            icon: 'fa-user-clock',
            title: 'Pengantar Usulan Penetapan Pensiun (BUP)',
            kategori: 'Kepegawaian',
            perihal: 'Pengantar Usulan Pemberhentian dan Penetapan Pensiun Pegawai (BUP)',
            salam: 'Dengan hormat,',
            pembuka: 'Bersama ini kami sampaikan berkas usulan penetapan pensiun batas usia pensiun (BUP) bagi pegawai negeri sipil berikut:',
            penutup: 'Demikian berkas usulan pensiun ini disampaikan untuk dapat diproses sebagaimana mestinya.',
            desc: 'Template pengantar penetapan pensiun pegawai.'
        },

        // Keuangan
        'KU.01': {
            target: 'template',
            type: 'pengantar',
            icon: 'fa-receipt',
            title: 'Penyampaian RAPBD & Anggaran Pendapatan',
            kategori: 'Keuangan',
            perihal: 'Penyampaian Rancangan Anggaran Pendapatan dan Belanja Sekolah (RAPBS)',
            salam: 'Dengan hormat,',
            pembuka: 'Bersama surat ini kami sampaikan rancangan dokumen penerimaan dan belanja anggaran satuan pendidikan tahun anggaran berjalan:',
            penutup: 'Demikian penyampaian rancangan anggaran ini untuk menjadi bahan telaah dan penetapan bersama.',
            desc: 'Template penyampaian rancangan pendapatan & belanja sekolah.'
        },
        'KU.01.02': {
            target: 'template',
            type: 'pengantar',
            icon: 'fa-file-invoice',
            title: 'Pengantar Berkas Penyusunan RKA-SKPD',
            kategori: 'Keuangan',
            perihal: 'Pengantar Berkas Dokumen Rencana Kerja dan Anggaran (RKA-SKPD)',
            salam: 'Dengan hormat,',
            pembuka: 'Bersama ini kami sampaikan usulan Rencana Kerja dan Anggaran (RKA) satuan pendidikan untuk diverifikasi:',
            penutup: 'Demikian dokumen ini disampaikan untuk diproses pada tahapan penyusunan anggaran selanjutnya.',
            desc: 'Template pengantar dokumen RKA sekolah.'
        },
        'KU.02': {
            target: 'template',
            type: 'pemberitahuan',
            icon: 'fa-coins',
            title: 'Penyusunan Anggaran Satuan Pendidikan',
            kategori: 'Keuangan',
            perihal: 'Pemberitahuan Sosialisasi dan Sinkronisasi Penyusunan Anggaran Sekolah',
            salam: 'Dengan hormat,',
            pembuka: 'Diberitahukan bahwa satuan pendidikan akan menyelenggarakan sinkronisasi dan evaluasi penyusunan anggaran kegiatan operasional:',
            penutup: 'Diharapkan seluruh pengelola anggaran hadir dan menyiapkan dokumen pendukung yang diperlukan.',
            desc: 'Template surat sosialisasi/koordinasi anggaran.'
        },
        'KU.03': {
            target: 'template',
            type: 'pengantar',
            icon: 'fa-chart-pie',
            title: 'Laporan Realisasi Pelaksanaan Anggaran',
            kategori: 'Keuangan',
            perihal: 'Laporan Realisasi Penyerapan Pelaksanaan Anggaran Sekolah',
            salam: 'Dengan hormat,',
            pembuka: 'Bersama ini kami sampaikan laporan berkala realisasi penyerapan anggaran satuan pendidikan untuk periode berjalan:',
            penutup: 'Demikian laporan realisasi ini disampaikan sebagai wujud akuntabilitas pengelolaan keuangan sekolah.',
            desc: 'Template laporan berkala realisasi belanja anggaran.'
        },
        'KU.03.01': {
            target: 'template',
            type: 'pengantar',
            icon: 'fa-file-invoice-dollar',
            title: 'Pengantar Berkas SPP / SPM / SP2D',
            kategori: 'Keuangan',
            perihal: 'Pengantar Berkas Pengajuan Surat Penyedia Dana (SPP / SPM / SP2D)',
            salam: 'Dengan hormat,',
            pembuka: 'Bersama surat ini kami sampaikan berkas kelengkapan administrasi keuangan guna penerbitan pembayaran kegiatan operasional:',
            penutup: 'Demikian berkas pengajuan ini disampaikan untuk dapat diproses verifikasinya.',
            desc: 'Template pengantar kelengkapan berkas SPP/SPM/SP2D.'
        },
        'KU.03.03.05': {
            target: 'template',
            type: 'pengantar',
            icon: 'fa-landmark',
            title: 'Laporan Realisasi Dana Alokasi Khusus (DAK)',
            kategori: 'Keuangan',
            perihal: 'Laporan Kemajuan dan Realisasi Pemanfaatan Dana Alokasi Khusus (DAK)',
            salam: 'Dengan hormat,',
            pembuka: 'Bersama ini kami laporkan capaian kemajuan fisik dan keuangan pemanfaatan Dana Alokasi Khusus (DAK) fisik sekolah:',
            penutup: 'Demikian laporan pelaksanaan DAK ini kami sampaikan untuk menjadi bahan monitoring dan evaluasi.',
            desc: 'Template laporan pelaksanaan & SPJ DAK fisik.'
        },
        'KU.03.10.01': {
            target: 'template',
            type: 'pengantar',
            icon: 'fa-money-check-dollar',
            title: 'Pengantar SPJ Belanja Pegawai / Honorarium',
            kategori: 'Keuangan',
            perihal: 'Surat Pengantar Pertanggungjawaban (SPJ) Belanja Gaji dan Honorarium Pegawai',
            salam: 'Dengan hormat,',
            pembuka: 'Bersama ini disampaikan Surat Pertanggungjawaban (SPJ) Belanja Gaji/Honorarium GTK satuan pendidikan untuk diverifikasi:',
            penutup: 'Demikian berkas SPJ ini kami serahkan untuk diteliti keabsahannya.',
            desc: 'Template pengantar SPJ belanja gaji/honorarium.'
        },
        'KU.03.10.02': {
            target: 'template',
            type: 'pengantar',
            icon: 'fa-cart-flatbed',
            title: 'Pengantar SPJ Belanja Barang dan Jasa',
            kategori: 'Keuangan',
            perihal: 'Pengantar Berkas SPJ Belanja Barang dan Jasa Keperluan Operasional Sekolah',
            salam: 'Dengan hormat,',
            pembuka: 'Bersama surat ini kami lampirkan dokumen SPJ pengadaan barang dan operasional jasa keperluan sekolah periode berjalan:',
            penutup: 'Demikian laporan SPJ barang dan jasa ini kami sampaikan.',
            desc: 'Template pengantar SPJ belanja operasional barang/jasa.'
        },
        'KU.03.10.03': {
            target: 'template',
            type: 'pengantar',
            icon: 'fa-boxes-packing',
            title: 'Pengantar SPJ Belanja Modal Aset Sekolah',
            kategori: 'Keuangan',
            perihal: 'Pengantar Berita Acara dan SPJ Belanja Modal Sarana Pendidikan',
            salam: 'Dengan hormat,',
            pembuka: 'Menyampaikan berkas berita acara serah terima dan pertanggungjawaban belanja modal sarana sekolah sebagaimana terlampir:',
            penutup: 'Demikian berkas belanja modal ini disampaikan untuk pencatatan aset inventaris.',
            desc: 'Template pengantar BAST & SPJ belanja modal aset.'
        },
        'KU.03.11.02': {
            target: 'template',
            type: 'pengantar',
            icon: 'fa-hand-holding-dollar',
            title: 'Pengantar LPJ Dana Hibah Pendidikan',
            kategori: 'Keuangan',
            perihal: 'Pengantar Laporan Pertanggungjawaban (LPJ) Penggunaan Dana Hibah Daerah',
            salam: 'Dengan hormat,',
            pembuka: 'Bersama ini kami sampaikan laporan penggunaan dan naskah perjanjian hibah daerah (NPHD) satuan pendidikan tahun anggaran berjalan:',
            penutup: 'Demikian laporan pertanggungjawaban hibah ini disampaikan untuk dievaluasi.',
            desc: 'Template laporan pertanggungjawaban dana hibah daerah.'
        },
        'KU.05': {
            target: 'template',
            type: 'pengantar',
            icon: 'fa-book-bookmark',
            title: 'Dokumen Penatausahaan Keuangan',
            kategori: 'Keuangan',
            perihal: 'Penyampaian Buku Kas Umum dan Berkas Penatausahaan Keuangan Sekolah',
            salam: 'Dengan hormat,',
            pembuka: 'Menyampaikan buku kas umum dan rekapitulasi penatausahaan kas keuangan satuan pendidikan untuk dilakukan penutupan buku:',
            penutup: 'Demikian berkas penatausahaan ini disampaikan sebagaimana ketentuan perbendaharaan.',
            desc: 'Template penyampaian berkas penatausahaan keuangan.'
        },
        'KU.05.01': {
            target: 'template',
            type: 'kustom',
            icon: 'fa-wallet',
            title: 'Permohonan Surat Penyedia Dana (SPD)',
            kategori: 'Keuangan',
            perihal: 'Surat Permohonan Penerbitan Surat Penyediaan Dana (SPD)',
            salam: 'Dengan hormat,',
            pembuka: 'Dengan hormat, kami mengajukan permohonan alokasi penyediaan dana operasional sekolah untuk mendukung kelancaran kegiatan:',
            penutup: 'Besar harapan kami permohonan SPD ini dapat diproses dalam waktu dekat.',
            desc: 'Template surat permohonan penyediaan alokasi dana SPD.'
        },
        'KU.05.02': {
            target: 'template',
            type: 'kustom',
            icon: 'fa-money-bill-wave',
            title: 'Permohonan Pembayaran Dana (SPP)',
            kategori: 'Keuangan',
            perihal: 'Surat Pengajuan Permohonan Pembayaran (SPP) Beban Kegiatan Sekolah',
            salam: 'Dengan hormat,',
            pembuka: 'Bersama surat ini diajukan dokumen permohonan pembayaran belanja beban tetap kegiatan satuan pendidikan sebagai berikut:',
            penutup: 'Demikian permohonan pembayaran ini diajukan agar dapat diverifikasi lebih lanjut.',
            desc: 'Template pengajuan surat permohonan pembayaran (SPP).'
        },
        'KU.05.03': {
            target: 'template',
            type: 'pengantar',
            icon: 'fa-file-signature',
            title: 'Pengantar Surat Perintah Membayar (SPM)',
            kategori: 'Keuangan',
            perihal: 'Pengantar Verifikasi dan Penerbitan Surat Perintah Membayar (SPM)',
            salam: 'Dengan hormat,',
            pembuka: 'Menyampaikan lembar Surat Perintah Membayar (SPM) beban belanja sekolah untuk diteliti dan diproses pencairan anggarannya:',
            penutup: 'Demikian pengantar ini disampaikan atas kerjasamanya diucapkan terima kasih.',
            desc: 'Template pengantar verifikasi penerbitan SPM.'
        },
        'KU.05.04': {
            target: 'template',
            type: 'pemberitahuan',
            icon: 'fa-circle-check',
            title: 'Konfirmasi Penerimaan Dana SP2D',
            kategori: 'Keuangan',
            perihal: 'Konfirmasi Penerimaan Pencairan Dana SP2D pada Kas Sekolah',
            salam: 'Dengan hormat,',
            pembuka: 'Dengan ini diberitahukan bahwa dana belanja kegiatan telah diterima secara utuh pada rekening kas satuan pendidikan via SP2D:',
            penutup: 'Demikian konfirmasi ini disampaikan untuk melengkapi bukti penatausahaan kas.',
            desc: 'Template konfirmasi penerimaan pencairan via SP2D.'
        },

        // Pendidikan
        'PK': {
            target: 'template',
            type: 'pemberitahuan',
            icon: 'fa-school',
            title: 'Agenda Pendidikan & Akademik Sekolah',
            kategori: 'Pendidikan',
            perihal: 'Pemberitahuan Pelaksanaan Agenda Pendidikan dan Program Akademik',
            salam: 'Dengan hormat,',
            pembuka: 'Diberitahukan kepada seluruh pendidik, tenaga kependidikan, serta warga sekolah mengenai agenda kegiatan akademik berikut:',
            penutup: 'Demikian pemberitahuan ini disampaikan untuk dipedomani bersama dengan sebaik-baiknya.',
            desc: 'Template pengumuman kebijakan agenda pendidikan satuan pendidikan.'
        },
        'PK.01': {
            target: 'template',
            type: 'pemberitahuan',
            icon: 'fa-book-open',
            title: 'Surat Edaran Kebijakan Pengaturan Sekolah',
            kategori: 'Pendidikan',
            perihal: 'Surat Edaran Kebijakan Teknis Pembelajaran dan Tata Tertib Sekolah',
            salam: 'Dengan hormat,',
            pembuka: 'Diberitahukan kepada seluruh warga satuan pendidikan terkait penetapan regulasi dan pedoman teknis pembelajaran:',
            penutup: 'Demikian edaran ini disampaikan untuk menjadi acuan dan dilaksanakan secara tertib.',
            desc: 'Template surat edaran tata tertib dan kebijakan pembelajaran.'
        },
        'PK.01.02': {
            target: 'template',
            type: 'kustom',
            icon: 'fa-handshake',
            title: 'Naskah Kerjasama / MoU Kemitraan',
            kategori: 'Pendidikan',
            perihal: 'Naskah Nota Kesepahaman / Kerjasama (MoU) Kemitraan Pendidikan',
            salam: 'Dengan hormat,',
            pembuka: 'Kesepakatan bersama dan naskah kerjasama program peningkatan mutu pendidikan antara satuan pendidikan dengan pihak mitra:',
            penutup: 'Naskah kerjasama ini dibuat untuk dilaksanakan dengan komitmen dan itikad baik kedua belah pihak.',
            desc: 'Template dokumen naskah kerjasama/MoU kemitraan sekolah.'
        },
        'PK.02.01': {
            target: 'template',
            type: 'pemberitahuan',
            icon: 'fa-calendar-week',
            title: 'Penetapan Kalender Akademik Sekolah',
            kategori: 'Pendidikan',
            perihal: 'Surat Penetapan Kalender Pendidikan dan Kegiatan Belajar Mengajar',
            salam: 'Dengan hormat,',
            pembuka: 'Menetapkan jadwal efektif pembelajaran, pekan evaluasi belajar, serta libur satuan pendidikan tahun ajaran berjalan:',
            penutup: 'Demikian ketetapan kalender akademik ini disampaikan untuk dilaksanakan sebagaimana mestinya.',
            desc: 'Template surat penetapan jadwal kalender pendidikan.'
        },
        'PK.03.01.04': {
            target: 'template',
            type: 'pemberitahuan',
            icon: 'fa-hand-holding-heart',
            title: 'Pemberitahuan Program Bansos Pendidikan',
            kategori: 'Pendidikan',
            perihal: 'Pemberitahuan Pendataan dan Penyaluran Bantuan Sosial Pendidikan',
            salam: 'Dengan hormat,',
            pembuka: 'Diberitahukan perihal pendataan dan penyaluran program bantuan pendidikan bagi peserta didik yang memenuhi kriteria:',
            penutup: 'Demikian pemberitahuan ini disampaikan untuk diteruskan kepada pihak yang berkepentingan.',
            desc: 'Template surat pemberitahuan program beasiswa/bansos pendidikan.'
        },
        'PK.03.01.05': {
            target: 'template',
            type: 'rekomendasi',
            icon: 'fa-trophy',
            title: 'Rekomendasi Keikutsertaan Lomba / Festival',
            kategori: 'Pendidikan',
            perihal: 'Surat Rekomendasi / Dispensasi Keikutsertaan Lomba dan Prestasi Siswa',
            salam: 'Dengan hormat,',
            pembuka: 'Memberikan rekomendasi dan dispensasi resmi kepada siswa/guru berprestasi untuk mewakili sekolah pada ajang perlombaan:',
            penutup: 'Demikian rekomendasi ini diberikan agar dapat berpartisipasi dengan penuh semangat dan integritas.',
            desc: 'Template surat rekomendasi keikutsertaan lomba & festival.'
        },
        'PK.03.01.15': {
            target: 'template',
            type: 'rekomendasi',
            icon: 'fa-chart-column',
            title: 'Keterangan Capaian Prestasi Kerja Guru (PKG)',
            kategori: 'Pendidikan',
            perihal: 'Surat Keterangan Hasil Penilaian Kinerja Guru (PKG) dan Angka Kredit',
            salam: 'Dengan hormat,',
            pembuka: 'Menerangkan perolehan predikat kinerja dan capaian angka kredit pendidik dalam pelaksanaan tugas pokok pembelajaran:',
            penutup: 'Surat keterangan ini diterbitkan sebagai bukti otentik capaian kinerja pendidik yang bersangkutan.',
            desc: 'Template keterangan hasil evaluasi kinerja PKG.'
        },
        'PK.03.01.16': {
            target: 'template',
            type: 'rekomendasi',
            icon: 'fa-ribbon',
            title: 'Piagam Apresiasi Pendidik & Tendik',
            kategori: 'Pendidikan',
            perihal: 'Pemberian Penghargaan dan Apresiasi Dedikasi Pendidik / Tendik Berprestasi',
            salam: 'Dengan hormat,',
            pembuka: 'Diberikan kepada pendidik/tenaga kependidikan atas dedikasi dan kontribusi luar biasa dalam program sekolah:',
            penutup: 'Semoga penghargaan ini menjadi inspirasi dan pemacu semangat dalam memajukan dunia pendidikan.',
            desc: 'Template piagam penghargaan apresiasi prestasi GTK.'
        },
        'PK.03.02.11': {
            target: 'template',
            type: 'rekomendasi',
            icon: 'fa-child-reaching',
            title: 'Keterangan Layanan Pendidikan Khusus / Inklusi',
            kategori: 'Pendidikan',
            perihal: 'Surat Keterangan Pendampingan Layanan Pendidikan Khusus / Inklusif',
            salam: 'Dengan hormat,',
            pembuka: 'Menerangkan pendampingan khusus dan penyesuaian kurikulum layanan pendidikan inklusif peserta didik berikut:',
            penutup: 'Demikian surat keterangan ini diberikan untuk digunakan dalam koordinasi layanan pendidikan khusus.',
            desc: 'Template surat keterangan layanan kelas inklusi/khusus.'
        },
        'PK.03.03.01': {
            target: 'template',
            type: 'pengantar',
            icon: 'fa-book-bookmark',
            title: 'Pengantar Kurikulum Operasional Sekolah (KOSP)',
            kategori: 'Pendidikan',
            perihal: 'Pengantar Dokumen Kurikulum Operasional Satuan Pendidikan (KOSP)',
            salam: 'Dengan hormat,',
            pembuka: 'Menyampaikan dokumen Kurikulum Operasional Satuan Pendidikan (KOSP) beserta perangkat ajar untuk diverifikasi dan disahkan:',
            penutup: 'Demikian pengantar dokumen kurikulum ini disampaikan sebagaimana ketentuan yang berlaku.',
            desc: 'Template pengantar berkas kurikulum KOSP ke dinas.'
        },
        'PK.03.03.02': {
            target: 'template',
            type: 'pengantar',
            icon: 'fa-handshake-angle',
            title: 'Laporan Realisasi Bantuan Operasional Sekolah (BOS)',
            kategori: 'Pendidikan',
            perihal: 'Pengantar Laporan Realisasi Bantuan Operasional Sekolah (BOS)',
            salam: 'Dengan hormat,',
            pembuka: 'Bersama surat ini kami sampaikan laporan realisasi pertanggungjawaban dana BOS dan bantuan operasional siswa:',
            penutup: 'Demikian laporan realisasi ini disampaikan untuk memenuhi kewajiban akuntabilitas pelaporan.',
            desc: 'Template pengantar laporan pertanggungjawaban BOS.'
        },
        'PK.09.01': {
            target: 'template',
            type: 'rekomendasi',
            icon: 'fa-user-tie',
            title: 'Rekomendasi Pengembangan Profesi Pendidik',
            kategori: 'Pendidikan',
            perihal: 'Surat Rekomendasi Mengikuti Diklat / Pelatihan Keprofesian Pendidik',
            salam: 'Dengan hormat,',
            pembuka: 'Memberikan izin dan rekomendasi kepada guru bersangkutan untuk mengikuti agenda peningkatan kompetensi keprofesian:',
            penutup: 'Demikian rekomendasi ini diberikan untuk dipergunakan sebagaimana mestinya.',
            desc: 'Template surat rekomendasi diklat pengembangan keprofesian.'
        },
        'PK.09.01.02': {
            target: 'template',
            type: 'rekomendasi',
            icon: 'fa-certificate',
            title: 'Keterangan Beban Mengajar Tunjangan Profesi (TPG)',
            kategori: 'Pendidikan',
            perihal: 'Surat Keterangan Pemenuhan Beban Mengajar Kelayakan Tunjangan Profesi',
            salam: 'Dengan hormat,',
            pembuka: 'Menerangkan bahwa pendidik yang bersangkutan telah memenuhi beban kerja 24 jam tatap muka untuk kelayakan TPG:',
            penutup: 'Surat keterangan ini diterbitkan untuk melengkapi persyaratan penerbitan Surat Keputusan Tunjangan Profesi (SKTP).',
            desc: 'Template surat keterangan kelayakan jam mengajar TPG.'
        },
        'PK.11.01': {
            target: 'template',
            type: 'rekomendasi',
            icon: 'fa-database',
            title: 'Keterangan Validasi Data Pokok Pendidikan (Dapodik)',
            kategori: 'Pendidikan',
            perihal: 'Surat Keterangan / Validasi Data Pokok Pendidikan (Dapodik)',
            salam: 'Dengan hormat,',
            pembuka: 'Menerangkan keabsahan identitas data warga sekolah sesuai dengan catatan mutakhir Data Pokok Pendidikan (Dapodik):',
            penutup: 'Demikian surat keterangan validasi data ini dibuat untuk keperluan verifikasi administrasi.',
            desc: 'Template keterangan validitas data pokok Dapodik.'
        },

        // Sarpras
        'RT.05.01': {
            target: 'template',
            type: 'kustom',
            icon: 'fa-car-side',
            title: 'Permohonan Servis / Izin Kendaraan Dinas',
            kategori: 'Sarpras',
            perihal: 'Permohonan Pemeliharaan Rutin dan Operasional Kendaraan Dinas Sekolah',
            salam: 'Dengan hormat,',
            pembuka: 'Mengajukan permohonan servis berkala serta perpanjangan dokumen operasional kendaraan dinas inventaris sekolah:',
            penutup: 'Demikian permohonan pemeliharaan kendaraan ini diajukan atas persetujuannya diucapkan terima kasih.',
            desc: 'Template permohonan pemeliharaan kendaraan dinas sekolah.'
        },
        'RT.05.03': {
            target: 'template',
            type: 'kustom',
            icon: 'fa-wifi',
            title: 'Permohonan Pemeliharaan Jaringan Internet',
            kategori: 'Sarpras',
            perihal: 'Permohonan Pemeliharaan dan Peningkatan Kapasitas Sarana Telekomunikasi',
            salam: 'Dengan hormat,',
            pembuka: 'Mengajukan permohonan pemeliharaan dan peningkatan stabilitas jaringan internet sekolah guna mendukung pembelajaran digital:',
            penutup: 'Demikian permohonan ini kami sampaikan agar dapat segera ditindaklanjuti penyedia layanan.',
            desc: 'Template permohonan perbaikan/pemeliharaan jaringan internet sekolah.'
        }
    };

    function getIndeksTemplateData(kode, judul = '', kategori = '') {
        if (kode && INDEKS_TEMPLATES[kode]) {
            return INDEKS_TEMPLATES[kode];
        }

        // Fallback untuk indeks di luar kamus
        const isKesiswaan = (kode && kode.startsWith('KS.')) || (kategori && kategori.toLowerCase().includes('kesiswaan'));
        const isSpt = kode === 'KPG.11.01' || (judul && judul.toLowerCase().includes('surat tugas'));

        if (isSpt) {
            return {
                target: 'spt',
                icon: 'fa-file-contract',
                title: 'Surat Perintah Tugas (SPT)',
                kategori: kategori || 'Kepegawaian',
                desc: 'Formulir Surat Perintah Tugas resmi untuk penugasan GTK.',
                perihal: 'Surat Perintah Tugas Kedinasan'
            };
        }

        if (isKesiswaan) {
            return {
                target: 'keterangan',
                icon: 'fa-user-graduate',
                title: 'Surat Keterangan Siswa',
                kategori: kategori || 'Kesiswaan',
                desc: 'Formulir Surat Keterangan / Panggilan Siswa Dapodik.',
                perihal: 'Surat Keterangan Kesiswaan'
            };
        }

        return {
            target: 'template',
            type: 'kustom',
            icon: 'fa-file-lines',
            title: judul || 'Naskah Dinas Sekolah',
            kategori: kategori || 'Umum',
            perihal: judul ? ('Penyampaian Naskah Dinas: ' + judul) : 'Surat Keluar Resmi Sekolah',
            salam: 'Dengan hormat,',
            pembuka: 'Bersama ini kami sampaikan hal-hal terkait ' + (judul || 'urusan kedinasan') + ' sebagai berikut:',
            penutup: 'Demikian surat ini kami sampaikan atas perhatian dan kerja samanya diucapkan terima kasih.',
            desc: 'Membuka formulir template naskah dinas resmi untuk indeks ' + (kode || '') + '.',
            hasRincian: false
        };
    }

    // 5c. Handler Modal Pemilih Indeks Dropdown (Selector)
    const modalPilihJenis = document.getElementById('modalPilihJenisSurat');
    const btnCloseModalPilihJenis = document.getElementById('btnCloseModalPilihJenis');
    const btnCancelPilihIndeks = document.getElementById('btnCancelPilihIndeks');
    const selectIndeksPembuatanSurat = document.getElementById('selectIndeksPembuatanSurat');
    const btnSubmitPilihIndeks = document.getElementById('btnSubmitPilihIndeks');

    const cardIndeksPreview = document.getElementById('cardIndeksPreview');
    const iconIndeksPreview = document.getElementById('iconIndeksPreview');
    const badgeKodeIndeksPreview = document.getElementById('badgeKodeIndeksPreview');
    const badgeKategoriIndeksPreview = document.getElementById('badgeKategoriIndeksPreview');
    const titleIndeksPreview = document.getElementById('titleIndeksPreview');
    const descIndeksPreview = document.getElementById('descIndeksPreview');
    const perihalIndeksPreview = document.getElementById('perihalIndeksPreview');

    const lblTextModalTemplate = document.getElementById('lblTextModalTemplate');
    const badgeIndeksModalTemplate = document.getElementById('badgeIndeksModalTemplate');
    const sectionRincianKegiatanTemplate = document.getElementById('sectionRincianKegiatanTemplate');

    function updateIndeksPreview(kode) {
        if (!selectIndeksPembuatanSurat) return;
        const opt = selectIndeksPembuatanSurat.querySelector(`option[value="${kode}"]`);
        const judul = opt ? opt.dataset.judul : '';
        const kategori = opt ? opt.dataset.kategori : '';
        const data = getIndeksTemplateData(kode, judul, kategori);

        if (badgeKodeIndeksPreview) badgeKodeIndeksPreview.textContent = kode || '-';
        if (badgeKategoriIndeksPreview) badgeKategoriIndeksPreview.textContent = data.kategori || kategori || 'Umum';
        if (titleIndeksPreview) titleIndeksPreview.textContent = data.title || judul || 'Pilih Indeks';
        if (descIndeksPreview) descIndeksPreview.textContent = data.desc || '';
        if (perihalIndeksPreview) perihalIndeksPreview.textContent = data.perihal || '';

        if (iconIndeksPreview) {
            iconIndeksPreview.innerHTML = `<i class="fas ${data.icon || 'fa-file-lines'}"></i>`;
        }
    }

    if (selectIndeksPembuatanSurat) {
        selectIndeksPembuatanSurat.addEventListener('change', function () {
            updateIndeksPreview(this.value);
        });
        // Inisialisasi awal preview bila ada nilai terpilih
        if (selectIndeksPembuatanSurat.value) {
            updateIndeksPreview(selectIndeksPembuatanSurat.value);
        }
    }

    function openPilihJenisModal() {
        if (!modalPilihJenis) return;
        modalPilihJenis.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        if (selectIndeksPembuatanSurat && selectIndeksPembuatanSurat.value) {
            updateIndeksPreview(selectIndeksPembuatanSurat.value);
        }
    }

    function closePilihJenisModal() {
        if (!modalPilihJenis) return;
        modalPilihJenis.style.display = 'none';
        document.body.style.overflow = '';
    }

    if (btnCloseModalPilihJenis) btnCloseModalPilihJenis.addEventListener('click', closePilihJenisModal);
    if (btnCancelPilihIndeks) btnCancelPilihIndeks.addEventListener('click', closePilihJenisModal);

    if (modalPilihJenis) {
        modalPilihJenis.addEventListener('click', function (e) {
            if (e.target === modalPilihJenis) closePilihJenisModal();
        });
    }

    // Aksi submit pilihan indeks: buka modal formulir yang tepat
    if (btnSubmitPilihIndeks && selectIndeksPembuatanSurat) {
        btnSubmitPilihIndeks.addEventListener('click', function () {
            const kode = selectIndeksPembuatanSurat.value;
            if (!kode) {
                if (window.SAE && window.SAE.toast) {
                    window.SAE.toast('Pilih indeks klasifikasi surat terlebih dahulu.', 'warning');
                } else {
                    alert('Pilih indeks klasifikasi surat terlebih dahulu.');
                }
                return;
            }

            const opt = selectIndeksPembuatanSurat.querySelector(`option[value="${kode}"]`);
            const judul = opt ? opt.dataset.judul : '';
            const kategori = opt ? opt.dataset.kategori : '';
            const data = getIndeksTemplateData(kode, judul, kategori);

            closePilihJenisModal();

            if (data.target === 'spt') {
                openSptModal();
            } else if (data.target === 'keterangan') {
                const selectKodeIndeksKet = document.getElementById('selectKodeIndeksKet');
                if (selectKodeIndeksKet && selectKodeIndeksKet.querySelector(`option[value="${kode}"]`)) {
                    selectKodeIndeksKet.value = kode;
                    selectKodeIndeksKet.dispatchEvent(new Event('change', { bubbles: true }));
                }
                openKetModal();
            } else {
                // Target: Template Naskah Dinas per Indeks
                if (inputTmplKodeIndeks) {
                    inputTmplKodeIndeks.value = kode;
                    inputTmplKodeIndeks.dispatchEvent(new Event('change', { bubbles: true }));
                }
                if (selectTemplateType && data.type) {
                    selectTemplateType.value = data.type;
                }
                if (inputTmplPerihal && data.perihal) {
                    inputTmplPerihal.value = data.perihal;
                }
                if (inputTmplSalam && data.salam) {
                    inputTmplSalam.value = data.salam;
                }
                if (inputTmplPembuka && data.pembuka) {
                    inputTmplPembuka.value = data.pembuka;
                }
                if (inputTmplPenutup && data.penutup) {
                    inputTmplPenutup.value = data.penutup;
                }

                // Update visual header modal template
                if (lblTextModalTemplate) {
                    lblTextModalTemplate.textContent = data.title || ('Template Naskah ' + (judul || kode));
                }
                if (badgeIndeksModalTemplate) {
                    badgeIndeksModalTemplate.textContent = kode;
                    badgeIndeksModalTemplate.style.display = 'inline-block';
                }

                openTemplateModal();
            }
        });
    }

    window.openPilihJenisModal = openPilihJenisModal;
    window.closePilihJenisModal = closePilihJenisModal;

    // Event delegation klik tombol "Buat Surat" dari baris katalog template di datatable
    document.addEventListener('click', function (e) {
        const btnRow = e.target.closest('.btn-trigger-template-row');
        if (!btnRow) return;

        const code = btnRow.getAttribute('data-code');
        const judul = btnRow.getAttribute('data-judul') || '';
        const data = getIndeksTemplateData(code);

        if (data.target === 'spt') {
            openSptModal();
            return;
        }

        if (data.target === 'keterangan') {
            const selectKodeIndeksKet = document.getElementById('selectKodeIndeksKet');
            if (selectKodeIndeksKet && selectKodeIndeksKet.querySelector(`option[value="${code}"]`)) {
                selectKodeIndeksKet.value = code;
                selectKodeIndeksKet.dispatchEvent(new Event('change', { bubbles: true }));
            }
            openKetModal();
            return;
        }

        // Buka modal template naskah dinas
        if (selectTemplateType) {
            selectTemplateType.value = data.type || 'undangan';
            const ev = new Event('change');
            ev.isTriggerByQuickTemplate = true;
            selectTemplateType.dispatchEvent(ev);
        }

        if (inputTmplKodeIndeks) {
            inputTmplKodeIndeks.value = code;
            const ev2 = new Event('change');
            ev2.isTriggerByQuickTemplate = true;
            inputTmplKodeIndeks.dispatchEvent(ev2);
        }

        if (inputTmplPerihal) inputTmplPerihal.value = data.perihal || judul;
        if (inputTmplSalam) inputTmplSalam.value = data.salam || 'Dengan hormat,';
        if (inputTmplPembuka) inputTmplPembuka.value = data.pembuka || '';
        if (inputTmplPenutup) inputTmplPenutup.value = data.penutup || '';

        if (sectionRincianKegiatanTemplate) {
            sectionRincianKegiatanTemplate.style.display = (data.type === 'undangan' || data.type === 'kustom' || data.hasRincian) ? 'block' : 'none';
        }

        if (lblTextModalTemplate) {
            lblTextModalTemplate.textContent = data.title || judul;
        }
        if (badgeIndeksModalTemplate) {
            badgeIndeksModalTemplate.textContent = code;
            badgeIndeksModalTemplate.style.display = 'inline-block';
        }

        openTemplateModal();
    });

    // Filter pencarian dan klik cepat kartu template di tab Buat Surat
    const filterTemplateCards = document.getElementById('filterTemplateCards');
    const gridQuickTemplates = document.getElementById('gridQuickTemplates');

    if (filterTemplateCards && gridQuickTemplates) {
        filterTemplateCards.addEventListener('input', function () {
            const query = this.value.toLowerCase().trim();
            const cards = gridQuickTemplates.querySelectorAll('.template-card-item');
            cards.forEach(card => {
                const searchData = card.getAttribute('data-search') || '';
                if (!query || searchData.includes(query)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    }

    if (gridQuickTemplates) {
        gridQuickTemplates.addEventListener('click', function (e) {
            const card = e.target.closest('.template-card-item');
            if (!card) return;

            const target = card.getAttribute('data-target');
            const code = card.getAttribute('data-code');
            const type = card.getAttribute('data-type');

            if (target === 'spt') {
                openSptModal();
            } else if (target === 'keterangan') {
                const selectKodeIndeksKet = document.getElementById('selectKodeIndeksKet');
                if (selectKodeIndeksKet && selectKodeIndeksKet.querySelector(`option[value="${code}"]`)) {
                    selectKodeIndeksKet.value = code;
                    selectKodeIndeksKet.dispatchEvent(new Event('change', { bubbles: true }));
                }
                openKetModal();
            } else {
                // Template Naskah Dinas per Indeks
                const data = getIndeksTemplateData(code);

                if (selectTemplateType) {
                    selectTemplateType.value = data.type || type || 'undangan';
                    const ev = new Event('change');
                    ev.isTriggerByQuickTemplate = true;
                    selectTemplateType.dispatchEvent(ev);
                }

                if (inputTmplKodeIndeks) {
                    inputTmplKodeIndeks.value = code;
                    const ev2 = new Event('change');
                    ev2.isTriggerByQuickTemplate = true;
                    inputTmplKodeIndeks.dispatchEvent(ev2);
                }

                if (inputTmplPerihal) inputTmplPerihal.value = data.perihal;
                if (inputTmplSalam) inputTmplSalam.value = data.salam;
                if (inputTmplPembuka) inputTmplPembuka.value = data.pembuka;
                if (inputTmplPenutup) inputTmplPenutup.value = data.penutup;

                if (sectionRincianKegiatanTemplate) {
                    sectionRincianKegiatanTemplate.style.display = (data.target === 'template' && (data.type === 'undangan' || data.type === 'kustom' || data.hasRincian)) ? 'block' : 'none';
                }

                if (lblTextModalTemplate) {
                    lblTextModalTemplate.textContent = data.title;
                }
                if (badgeIndeksModalTemplate) {
                    badgeIndeksModalTemplate.textContent = code;
                    badgeIndeksModalTemplate.style.display = 'inline-block';
                }

                openTemplateModal();
            }
        });
    }

    // Ekspor fungsi modal ke window untuk akses global
    window.openKeluarModal = openKeluarModal;
    window.closeKeluarModal = closeKeluarModal;
    window.openKetModal = openKetModal;
    window.closeKetModal = closeKetModal;
    window.openSptModal = openSptModal;
    window.closeModalSpt = closeModalSpt;
    window.openTemplateModal = openTemplateModal;
    window.closeModalTemplate = closeModalTemplate;

    if (btnCloseModalTemplate) btnCloseModalTemplate.addEventListener('click', closeModalTemplate);
    if (btnCancelModalTemplate) btnCancelModalTemplate.addEventListener('click', closeModalTemplate);

    if (modalTemplate) {
        modalTemplate.addEventListener('click', function (e) {
            if (e.target === modalTemplate) closeModalTemplate();
        });
    }

    if (selectTemplateType) {
        selectTemplateType.addEventListener('change', function (e) {
            if (e.isTriggerByQuickTemplate) return;
            const opt = this.options[this.selectedIndex];
            if (!opt) return;
            if (inputTmplPerihal && opt.dataset.perihal) inputTmplPerihal.value = opt.dataset.perihal;
            if (inputTmplKodeIndeks && opt.dataset.indeks) {
                inputTmplKodeIndeks.value = opt.dataset.indeks;
                inputTmplKodeIndeks.dispatchEvent(new Event('change'));
            }
            if (inputTmplSalam && opt.dataset.salam) inputTmplSalam.value = opt.dataset.salam;
            if (inputTmplPembuka && opt.dataset.pembuka) inputTmplPembuka.value = opt.dataset.pembuka;
            if (inputTmplPenutup && opt.dataset.penutup) inputTmplPenutup.value = opt.dataset.penutup;
        });
    }

    // 6. Konfirmasi Form Submit (Kirim ke Surat Keluar & Delete) via SweetAlert2
    document.addEventListener('submit', function (e) {
        const formKirim = e.target.closest('form[data-confirm="kirim-keluar"]');
        if (formKirim && !formKirim.dataset.confirmed) {
            e.preventDefault();
            const name = formKirim.getAttribute('data-name') || 'surat ini';
            window.SAE.confirm(
                `Kirim dan terbitkan surat "${name}" ke Surat Keluar? Nomor surat resmi akan digenerate otomatis dan tercatat dalam buku agenda keluar.`,
                'Kirim ke Surat Keluar?',
                'info',
                'Ya, Kirim Sekarang',
                'Batal'
            ).then((ok) => {
                if (ok) {
                    formKirim.dataset.confirmed = 'true';
                    formKirim.submit();
                }
            });
            return;
        }

        const form = e.target.closest('form[data-confirm="delete"]');
        if (form && !form.dataset.confirmed) {
            e.preventDefault();
            const name = form.getAttribute('data-name') || 'data ini';
            window.SAE.confirm(`Apakah Anda yakin ingin menghapus ${name}? Tindakan ini tidak dapat dibatalkan.`, "Konfirmasi Hapus", 'danger', "Ya, Hapus", "Batal").then((ok) => {
                if (ok) {
                    form.dataset.confirmed = 'true';
                    form.submit();
                }
            });
        }
    });
});
