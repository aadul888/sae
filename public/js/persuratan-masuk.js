/**
 * Script Modul Surat Masuk & Disposisi — SAE
 */

document.addEventListener('DOMContentLoaded', function () {
    const container = document.getElementById('suratMasukContainer');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || window.CSRF_TOKEN || '';
    const suratMasukStoreUrl = container?.dataset?.storeUrl || window.SURAT_MASUK_STORE_URL || '';
    const suratMasukBaseUrl = container?.dataset?.baseUrl || window.SURAT_MASUK_BASE_URL || '';

    // 1. Modal Catat / Edit Surat Masuk
    const modalSuratMasuk = document.getElementById('modalSuratMasuk');
    const formSuratMasuk = document.getElementById('formSuratMasuk');
    const modalMasukTitle = document.getElementById('modalMasukTitle');
    const methodSpoofMasuk = document.getElementById('methodSpoofMasuk');
    const inputNomorSurat = document.getElementById('inputNomorSurat');
    const selectKodeIndeks = document.getElementById('selectKodeIndeks');
    const inputPerihal = document.getElementById('inputPerihal');
    const inputPengirimAsal = document.getElementById('inputPengirimAsal');
    const inputTanggalSurat = document.getElementById('inputTanggalSurat');
    const inputTanggalDiterima = document.getElementById('inputTanggalDiterima');
    const selectStatus = document.getElementById('selectStatus');
    const inputKeterangan = document.getElementById('inputKeterangan');
    const fileInfoExisting = document.getElementById('fileInfoExisting');

    const btnOpenCreateMasuk = document.getElementById('btnOpenCreateMasuk');
    const btnCloseModalMasuk = document.getElementById('btnCloseModalMasuk');
    const btnCancelModalMasuk = document.getElementById('btnCancelModalMasuk');

    function openMasukModal(isEdit = false, data = {}) {
        if (!modalSuratMasuk) return;

        if (isEdit) {
            modalMasukTitle.innerText = 'Edit Data Surat Masuk';
            formSuratMasuk.action = suratMasukBaseUrl + '/' + data.id;
            methodSpoofMasuk.value = 'PUT';

            inputNomorSurat.value = data.nomor_surat || '';
            if (selectKodeIndeks) selectKodeIndeks.value = data.kode_indeks || '';
            inputPerihal.value = data.perihal || '';
            inputPengirimAsal.value = data.pengirim_asal || '';
            inputTanggalSurat.value = data.tanggal_surat || '';
            inputTanggalDiterima.value = data.tanggal_diterima || '';
            if (selectStatus) selectStatus.value = data.status || 'menunggu_disposisi';
            inputKeterangan.value = data.keterangan || '';

            if (fileInfoExisting) {
                if (data.file_path) {
                    fileInfoExisting.style.display = 'block';
                    fileInfoExisting.innerHTML = `<i class="fas fa-file-pdf text-danger me-1"></i> Berkas saat ini: <strong>${data.file_name_original || 'Dokumen Scan'}</strong> (Unggah berkas baru untuk mengganti)`;
                } else {
                    fileInfoExisting.style.display = 'none';
                }
            }
        } else {
            modalMasukTitle.innerText = 'Catat Surat Masuk Baru';
            formSuratMasuk.action = suratMasukStoreUrl;
            methodSpoofMasuk.value = 'POST';

            inputNomorSurat.value = '';
            if (selectKodeIndeks) selectKodeIndeks.value = '';
            inputPerihal.value = '';
            inputPengirimAsal.value = '';
            inputTanggalSurat.value = new Date().toISOString().split('T')[0];
            inputTanggalDiterima.value = new Date().toISOString().split('T')[0];
            if (selectStatus) selectStatus.value = 'menunggu_disposisi';
            inputKeterangan.value = '';

            if (fileInfoExisting) fileInfoExisting.style.display = 'none';
        }

        modalSuratMasuk.style.display = 'flex';
        document.body.classList.add('modal-open');
    }

    function closeMasukModal() {
        if (!modalSuratMasuk) return;
        modalSuratMasuk.style.display = 'none';
        document.body.classList.remove('modal-open');
    }

    if (btnOpenCreateMasuk) btnOpenCreateMasuk.addEventListener('click', () => openMasukModal(false));
    if (btnCloseModalMasuk) btnCloseModalMasuk.addEventListener('click', closeMasukModal);
    if (btnCancelModalMasuk) btnCancelModalMasuk.addEventListener('click', closeMasukModal);

    // 2. Modal Lembar Disposisi
    const modalDisposisi = document.getElementById('modalDisposisi');
    const formDisposisi = document.getElementById('formDisposisi');
    const disposisiNomorSuratText = document.getElementById('disposisiNomorSuratText');
    const disposisiPerihalText = document.getElementById('disposisiPerihalText');
    const inputDisposisiDari = document.getElementById('inputDisposisiDari');
    const inputDisposisiKe = document.getElementById('inputDisposisiKe');
    const selectPtkTujuan = document.getElementById('selectPtkTujuan');
    const selectInstruksi = document.getElementById('selectInstruksi');
    const inputDisposisiCatatan = document.getElementById('inputDisposisiCatatan');
    const inputTanggalDisposisi = document.getElementById('inputTanggalDisposisi');
    const btnCloseModalDisposisi = document.getElementById('btnCloseModalDisposisi');
    const btnCancelModalDisposisi = document.getElementById('btnCancelModalDisposisi');

    function openDisposisiModal(id, nomor, perihal) {
        if (!modalDisposisi) return;

        formDisposisi.action = suratMasukBaseUrl + '/' + id + '/disposisi';
        if (disposisiNomorSuratText) disposisiNomorSuratText.innerText = nomor;
        if (disposisiPerihalText) disposisiPerihalText.innerText = perihal;

        inputDisposisiDari.value = 'Kepala Sekolah';
        inputDisposisiKe.value = '';
        if (selectPtkTujuan) selectPtkTujuan.value = '';
        if (selectInstruksi) selectInstruksi.value = 'Tindak Lanjuti';
        inputDisposisiCatatan.value = '';
        inputTanggalDisposisi.value = new Date().toISOString().split('T')[0];

        modalDisposisi.style.display = 'flex';
        document.body.classList.add('modal-open');
    }

    function closeDisposisiModal() {
        if (!modalDisposisi) return;
        modalDisposisi.style.display = 'none';
        document.body.classList.remove('modal-open');
    }

    if (btnCloseModalDisposisi) btnCloseModalDisposisi.addEventListener('click', closeDisposisiModal);
    if (btnCancelModalDisposisi) btnCancelModalDisposisi.addEventListener('click', closeDisposisiModal);

    // Event listener untuk memilih PTK dan otomatis mengisi text Disposisi Ke
    if (selectPtkTujuan) {
        selectPtkTujuan.addEventListener('change', function () {
            const selectedOpt = selectPtkTujuan.options[selectPtkTujuan.selectedIndex];
            if (selectedOpt && selectedOpt.value) {
                inputDisposisiKe.value = selectedOpt.dataset.nama || selectedOpt.text;
            }
        });
    }

    // 3. Modal Preview Berkas Dokumen dari HDD
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

    // Event Delegation: Tombol Edit, Disposisi, dan Preview
    document.addEventListener('click', function (e) {
        // Tombol Edit
        const btnEdit = e.target.closest('.btn-edit-masuk');
        if (btnEdit) {
            const data = {
                id: btnEdit.dataset.id,
                nomor_surat: btnEdit.dataset.nomor,
                kode_indeks: btnEdit.dataset.indeks,
                perihal: btnEdit.dataset.perihal,
                pengirim_asal: btnEdit.dataset.pengirim,
                tanggal_surat: btnEdit.dataset.tglSurat,
                tanggal_diterima: btnEdit.dataset.tglDiterima,
                status: btnEdit.dataset.status,
                keterangan: btnEdit.dataset.keterangan,
                file_path: btnEdit.dataset.filePath,
                file_name_original: btnEdit.dataset.fileName
            };
            openMasukModal(true, data);
            return;
        }

        // Tombol Disposisi
        const btnDisp = e.target.closest('.btn-disposisi-masuk');
        if (btnDisp) {
            openDisposisiModal(btnDisp.dataset.id, btnDisp.dataset.nomor, btnDisp.dataset.perihal);
            return;
        }

        // Tombol Preview Berkas dari HDD
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

    // 4. Konfirmasi Hapus Surat Masuk dengan SweetAlert2
    document.addEventListener('submit', function (e) {
        const form = e.target;
        if (form.getAttribute('data-confirm') === 'delete') {
            e.preventDefault();
            const name = form.getAttribute('data-name') || 'Surat ini';

            Swal.fire({
                title: 'Hapus Surat Masuk?',
                text: `Apakah Anda yakin ingin menghapus "${name}" beserta berkas arsipnya di harddisk? Tindakan ini permanen.`,
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

    // 5. Datatable Realtime Live Search, Entri perPage, Filter Status, and Sorting Standar SAE
    const searchInput = document.getElementById("liveSearch");
    const clearBtn = document.getElementById("clearSearch");
    const perPageSelect = document.getElementById("perPageSelect");
    const filterStatus = document.getElementById("filterStatus");

    function applyFilter() {
        const url = new URL(window.location.href);
        if (searchInput && searchInput.value.trim()) {
            url.searchParams.set("q", searchInput.value.trim());
        } else {
            url.searchParams.delete("q");
        }

        if (filterStatus && filterStatus.value) {
            url.searchParams.set("status", filterStatus.value);
        } else {
            url.searchParams.delete("status");
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

    if (filterStatus) filterStatus.addEventListener("change", applyFilter);
    if (perPageSelect) perPageSelect.addEventListener("change", applyFilter);

    document.querySelectorAll(".sortable-th").forEach(function (th) {
        th.style.cursor = "pointer";
        th.addEventListener("click", function () {
            const sortField = this.getAttribute("data-sort");
            if (!sortField) return;

            const url = new URL(window.location.href);
            const currentSort = url.searchParams.get("sort") || "tanggal_surat";
            const currentDir = url.searchParams.get("sort_dir") || url.searchParams.get("dir") || "desc";

            let newDir = "asc";
            if (currentSort === sortField && currentDir === "asc") {
                newDir = "desc";
            }

            url.searchParams.set("sort", sortField);
            url.searchParams.set("sort_dir", newDir);
            if (typeof window.refreshLiveTable === "function") {
                window.refreshLiveTable(url.toString());
            } else {
                window.location.href = url.toString();
            }
        });
    });
});
