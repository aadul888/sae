/**
 * Script Pengaturan Sistem Persuratan, Master Indeks & Tab Switcher — SAE
 */

document.addEventListener('DOMContentLoaded', function () {
    const container = document.getElementById('persuratanPengaturanContainer');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || window.CSRF_TOKEN || '';
    const testHddUrl = container?.dataset?.testHddUrl || window.PERSURATAN_TEST_HDD_URL || '';
    const indeksStoreUrl = container?.dataset?.indeksStoreUrl || window.PERSURATAN_INDEKS_STORE_URL || '';
    const indeksBaseUrl = container?.dataset?.indeksBaseUrl || window.PERSURATAN_INDEKS_BASE_URL || '';

    // =========================================================================
    // 1. Tab Switcher: Referensi vs Pengaturan Surat
    // =========================================================================
    const tabButtons = document.querySelectorAll('.dash-tab-btn');
    const tabPanes = document.querySelectorAll('.dash-tab-pane');

    function switchTab(targetSelector) {
        tabButtons.forEach(btn => {
            const isMatch = (btn.dataset.target === targetSelector);
            btn.classList.toggle('active', isMatch);
            btn.style.color = isMatch ? 'var(--primary)' : 'var(--text-muted)';
            btn.style.borderBottom = isMatch ? '2px solid var(--primary)' : 'transparent';
        });

        tabPanes.forEach(pane => {
            pane.style.display = ('#' + pane.id === targetSelector) ? 'block' : 'none';
        });

        // Update URL query parameter tab tanpa reload
        const tabKey = targetSelector.replace('#tab-', '');
        const currentUrl = new URL(window.location.href);
        currentUrl.searchParams.set('tab', tabKey);
        window.history.replaceState({}, '', currentUrl);
    }

    tabButtons.forEach(btn => {
        btn.addEventListener('click', function () {
            const target = this.dataset.target;
            if (target) {
                switchTab(target);
            }
        });
    });

    // =========================================================================
    // 2. Live Preview Penomoran Otomatis Berdasarkan Indeks
    // =========================================================================
    const inputSekolahKode = document.getElementById('inputSekolahKode');
    const inputFormatKeluar = document.getElementById('inputFormatKeluar');
    const inputFormatKet = document.getElementById('inputFormatKet');
    const counterKeluar = document.getElementById('counterKeluar');
    const counterKet = document.getElementById('counterKet');
    const previewIndeksSelect = document.getElementById('previewIndeksSelect');
    const previewKeluarText = document.getElementById('previewKeluarText');
    const previewKetText = document.getElementById('previewKetText');

    function getRomawiBulan(m) {
        const romawi = ['I','II','III','IV','V','VI','VII','VIII','IX','X','XI','XII'];
        return romawi[m - 1] || 'I';
    }

    function renderNumberPreview(template, nomorStr, kodeIndeks, sekolahKode) {
        if (!template) return '-';
        const now = new Date();
        const curYear = now.getFullYear();
        const curMonth = now.getMonth() + 1;
        const romawi = getRomawiBulan(curMonth);
        const bulanDuaDigit = String(curMonth).padStart(2, '0');

        let res = template;
        res = res.replace(/{nomor}/g, nomorStr);
        res = res.replace(/{kode_indeks}/g, kodeIndeks);
        res = res.replace(/{sekolah_kode}/g, sekolahKode);
        res = res.replace(/{sekolah_singkatan}/g, sekolahKode);
        res = res.replace(/{romawi_bulan}/g, romawi);
        res = res.replace(/{bulan}/g, bulanDuaDigit);
        res = res.replace(/{tahun}/g, curYear);
        return res;
    }

    function updateLivePreviews() {
        const sekolah = inputSekolahKode ? inputSekolahKode.value.trim() || 'SMKN1PGL' : 'SMKN1PGL';
        const formatKeluar = inputFormatKeluar ? inputFormatKeluar.value.trim() : '';
        const formatKet = inputFormatKet ? inputFormatKet.value.trim() : '';

        const nextKeluarNum = String(parseInt(counterKeluar ? counterKeluar.value : 0) + 1).padStart(4, '0');
        const nextKetNum = String(parseInt(counterKet ? counterKet.value : 0) + 1).padStart(4, '0');

        const selectedIndeks = previewIndeksSelect ? previewIndeksSelect.value : 'KPG.11.01';

        if (previewKeluarText && formatKeluar) {
            previewKeluarText.innerText = renderNumberPreview(formatKeluar, nextKeluarNum, selectedIndeks, sekolah);
        }
        if (previewKetText && formatKet) {
            previewKetText.innerText = renderNumberPreview(formatKet, nextKetNum, selectedIndeks, sekolah);
        }
    }

    if (inputSekolahKode) inputSekolahKode.addEventListener('input', updateLivePreviews);
    if (inputFormatKeluar) inputFormatKeluar.addEventListener('input', updateLivePreviews);
    if (inputFormatKet) inputFormatKet.addEventListener('input', updateLivePreviews);
    if (counterKeluar) counterKeluar.addEventListener('input', updateLivePreviews);
    if (counterKet) counterKet.addEventListener('input', updateLivePreviews);
    if (previewIndeksSelect) previewIndeksSelect.addEventListener('change', updateLivePreviews);

    // =========================================================================
    // 3. Uji Koneksi Harddisk (HDD) via AJAX
    // =========================================================================
    const btnTestHdd = document.getElementById('btnTestHdd');
    const hddPathInput = document.getElementById('hddPathInput');
    const hddStatusIndicator = document.getElementById('hddStatusIndicator');
    const hddFreeText = document.getElementById('hddFreeText');
    const hddTotalText = document.getElementById('hddTotalText');

    if (btnTestHdd && testHddUrl) {
        btnTestHdd.addEventListener('click', function () {
            const path = hddPathInput ? hddPathInput.value.trim() : '';
            if (!path) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Path HDD Kosong',
                    text: 'Silakan masukkan path direktori harddisk terlebih dahulu.',
                    confirmButtonColor: '#6366f1'
                });
                return;
            }

            btnTestHdd.disabled = true;
            btnTestHdd.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Menguji...';

            fetch(testHddUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ path: path })
            })
                .then(res => res.json())
                .then(data => {
                    btnTestHdd.disabled = false;
                    btnTestHdd.innerHTML = '<i class="fas fa-plug-circle-check me-1"></i> Uji Akses Folder';

                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Koneksi HDD Berhasil!',
                            html: `Direktori harddisk valid dan siap digunakan untuk membaca &amp; menulis berkas.<br><br>
                                   <strong>Kapasitas Tersedia:</strong> ${data.free_formatted} dari ${data.total_formatted}`,
                            confirmButtonColor: '#10b981'
                        });

                        if (hddStatusIndicator) {
                            hddStatusIndicator.className = 'badge-compact badge-success';
                            hddStatusIndicator.innerHTML = '<i class="fas fa-check-circle"></i> Terhubung &amp; Siap Tulis';
                        }
                        if (hddFreeText && data.free_formatted) hddFreeText.innerText = data.free_formatted;
                        if (hddTotalText && data.total_formatted) hddTotalText.innerText = data.total_formatted;
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Koneksi HDD Gagal',
                            text: data.message || 'Direktori harddisk tidak dapat diakses atau tidak memiliki izin tulis.',
                            confirmButtonColor: '#ef4444'
                        });

                        if (hddStatusIndicator) {
                            hddStatusIndicator.className = 'badge-compact badge-danger';
                            hddStatusIndicator.innerHTML = '<i class="fas fa-triangle-exclamation"></i> Tidak Dapat Diakses';
                        }
                    }
                })
                .catch(() => {
                    btnTestHdd.disabled = false;
                    btnTestHdd.innerHTML = '<i class="fas fa-plug-circle-check me-1"></i> Uji Akses Folder';
                    Swal.fire({
                        icon: 'error',
                        title: 'Kesalahan Sistem',
                        text: 'Terjadi kegagalan jaringan saat menghubungi server.',
                        confirmButtonColor: '#ef4444'
                    });
                });
        });
    }

    // =========================================================================
    // 4. Modal Tambah & Edit Master Indeks Klasifikasi Surat
    // =========================================================================
    const modalIndeks = document.getElementById('modalIndeks');
    const formIndeks = document.getElementById('formIndeks');
    const modalIndeksTitle = document.getElementById('modalIndeksTitle');
    const methodSpoofIndeks = document.getElementById('methodSpoofIndeks');
    const inputIndeksKode = document.getElementById('inputIndeksKode');
    const inputIndeksJudul = document.getElementById('inputIndeksJudul');
    const selectIndeksKategori = document.getElementById('selectIndeksKategori');
    const inputIndeksKeterangan = document.getElementById('inputIndeksKeterangan');
    const checkIndeksActive = document.getElementById('checkIndeksActive');
    const btnOpenCreateIndeks = document.getElementById('btnOpenCreateIndeks');
    const btnCloseModalIndeks = document.getElementById('btnCloseModalIndeks');
    const btnCancelModalIndeks = document.getElementById('btnCancelModalIndeks');

    function openIndeksModal(isEdit = false, data = {}) {
        if (!modalIndeks) return;

        if (isEdit) {
            modalIndeksTitle.innerText = 'Edit Kode Indeks Klasifikasi';
            formIndeks.action = indeksBaseUrl + '/' + data.id;
            methodSpoofIndeks.value = 'PUT';

            inputIndeksKode.value = data.kode || '';
            inputIndeksJudul.value = data.judul || '';
            if (selectIndeksKategori) selectIndeksKategori.value = data.kategori || 'Umum';
            inputIndeksKeterangan.value = data.keterangan || '';
            if (checkIndeksActive) checkIndeksActive.checked = (data.is_active == 1);
        } else {
            modalIndeksTitle.innerText = 'Tambah Kode Indeks Klasifikasi';
            formIndeks.action = indeksStoreUrl;
            methodSpoofIndeks.value = 'POST';

            inputIndeksKode.value = '';
            inputIndeksJudul.value = '';
            if (selectIndeksKategori) selectIndeksKategori.value = 'Umum';
            inputIndeksKeterangan.value = '';
            if (checkIndeksActive) checkIndeksActive.checked = true;
        }

        modalIndeks.style.display = 'flex';
        document.body.classList.add('modal-open');
    }

    function closeIndeksModal() {
        if (!modalIndeks) return;
        modalIndeks.style.display = 'none';
        document.body.classList.remove('modal-open');
    }

    if (btnOpenCreateIndeks) {
        btnOpenCreateIndeks.addEventListener('click', function () {
            openIndeksModal(false);
        });
    }

    if (btnCloseModalIndeks) btnCloseModalIndeks.addEventListener('click', closeIndeksModal);
    if (btnCancelModalIndeks) btnCancelModalIndeks.addEventListener('click', closeIndeksModal);

    // Event Delegation: Tombol Edit Indeks
    document.addEventListener('click', function (e) {
        const btnEdit = e.target.closest('.btn-edit-indeks');
        if (btnEdit) {
            const data = {
                id: btnEdit.dataset.id,
                kode: btnEdit.dataset.kode,
                judul: btnEdit.dataset.judul,
                kategori: btnEdit.dataset.kategori,
                keterangan: btnEdit.dataset.keterangan,
                is_active: btnEdit.dataset.active
            };
            openIndeksModal(true, data);
        }
    });

    // =========================================================================
    // 5. Konfirmasi Hapus Indeks dengan SweetAlert2
    // =========================================================================
    document.addEventListener('submit', function (e) {
        const form = e.target;
        if (form.getAttribute('data-confirm') === 'delete') {
            e.preventDefault();
            const name = form.getAttribute('data-name') || 'Item ini';

            Swal.fire({
                title: 'Hapus Kode Indeks?',
                text: `Apakah Anda yakin ingin menghapus "${name}"? Tindakan ini tidak dapat dibatalkan.`,
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

    // =========================================================================
    // 6. Datatable Realtime Live Search, Entri perPage & Filter Kategori Indeks Standar SAE
    // =========================================================================
    const searchIndeksInput = document.getElementById("liveSearchIndeks");
    const clearIndeksBtn = document.getElementById("clearSearchIndeks");
    const perPageSelect = document.getElementById("perPageSelect");
    const filterKategori = document.getElementById("filterKategori");

    function applyIndeksFilter() {
        const url = new URL(window.location.href);
        url.searchParams.set("tab", "referensi");

        if (searchIndeksInput && searchIndeksInput.value.trim()) {
            url.searchParams.set("q_indeks", searchIndeksInput.value.trim());
        } else {
            url.searchParams.delete("q_indeks");
        }

        if (filterKategori && filterKategori.value) {
            url.searchParams.set("kategori", filterKategori.value);
        } else {
            url.searchParams.delete("kategori");
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

    if (searchIndeksInput) {
        let timer = null;
        searchIndeksInput.addEventListener("input", function () {
            if (clearIndeksBtn) clearIndeksBtn.classList.toggle("visible", this.value.trim().length > 0);
            clearTimeout(timer);
            timer = setTimeout(applyIndeksFilter, 300);
        });

        searchIndeksInput.addEventListener("keydown", function (e) {
            if (e.key === "Enter") {
                e.preventDefault();
                clearTimeout(timer);
                applyIndeksFilter();
            }
        });
    }

    if (clearIndeksBtn) {
        clearIndeksBtn.addEventListener("click", function () {
            if (searchIndeksInput) {
                searchIndeksInput.value = "";
                clearIndeksBtn.classList.remove("visible");
                applyIndeksFilter();
            }
        });
    }

    if (filterKategori) filterKategori.addEventListener("change", applyIndeksFilter);
    if (perPageSelect) perPageSelect.addEventListener("change", applyIndeksFilter);
});
