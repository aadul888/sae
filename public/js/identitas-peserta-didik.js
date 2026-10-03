/**
 * Modul Identitas Peserta Didik — SAE Core JavaScript Module
 * Spesifikasi Formulir Dapodik 2026/2027
 */

document.addEventListener('DOMContentLoaded', function () {
    // 1. Inisialisasi Toggle Data Wali
    initWaliToggle();

    // 2. Inisialisasi Repeater Riwayat Prestasi
    initPrestasiRepeater();

    // 3. Inisialisasi Repeater Perlindungan Sosial / Kesejahteraan
    initSosialRepeater();

    // 4. Inisialisasi Navigasi Tab Seksi Formulir
    initSectionTabs();

    // 5. Inisialisasi Dialog Konfirmasi Data Siswa (Sesuai / Belum Sesuai)
    initKonfirmasiDialog();

    // 6. Inisialisasi Dropdown Bertingkat Wilayah Indonesia (Provinsi -> Kab/Kota -> Kecamatan -> Desa/Kelurahan)
    initWilayahCascade();
});

/**
 * Toggle fieldset data wali jika mempunyai wali dipilih
 */
function initWaliToggle() {
    const checkWali = document.getElementById('checkMempunyaiWali');
    const containerWali = document.getElementById('containerDataWali');
    if (!checkWali || !containerWali) return;

    const updateVisibility = () => {
        if (checkWali.checked) {
            containerWali.style.display = 'block';
        } else {
            containerWali.style.display = 'none';
        }
    };

    checkWali.addEventListener('change', updateVisibility);
    updateVisibility();
}

/**
 * Repeater Baris Riwayat Prestasi Siswa
 */
function initPrestasiRepeater() {
    const btnAdd = document.getElementById('btnAddPrestasi');
    const tbody = document.getElementById('tbodyPrestasi');
    if (!btnAdd || !tbody) return;

    btnAdd.addEventListener('click', function () {
        const rowCount = tbody.querySelectorAll('tr.row-prestasi').length;
        const newIdx = rowCount;

        const tr = document.createElement('tr');
        tr.className = 'row-prestasi';
        tr.style.borderBottom = '1px solid var(--border-color)';
        tr.innerHTML = `
            <td style="padding: 10px 8px;">
                <select name="riwayat_prestasi[${newIdx}][jenis]" class="form-select form-select-sm" style="font-size: 0.82rem;">
                    <option value="Sains">Sains</option>
                    <option value="Seni">Seni</option>
                    <option value="Olahraga">Olahraga</option>
                    <option value="Teknologi">Teknologi</option>
                    <option value="Keagamaan">Keagamaan</option>
                    <option value="Lain-lain">Lain-lain</option>
                </select>
            </td>
            <td style="padding: 10px 8px;">
                <select name="riwayat_prestasi[${newIdx}][tingkat]" class="form-select form-select-sm" style="font-size: 0.82rem;">
                    <option value="Sekolah">Sekolah</option>
                    <option value="Kecamatan">Kecamatan</option>
                    <option value="Kab/kota">Kab/kota</option>
                    <option value="Propinsi">Propinsi</option>
                    <option value="Nasional">Nasional</option>
                    <option value="Internasional">Internasional</option>
                </select>
            </td>
            <td style="padding: 10px 8px;">
                <input type="text" name="riwayat_prestasi[${newIdx}][nama]" class="form-control form-control-sm" placeholder="Nama Lomba / Kejuaraan" style="font-size: 0.82rem;" required>
            </td>
            <td style="padding: 10px 8px; width: 90px;">
                <input type="number" name="riwayat_prestasi[${newIdx}][tahun]" class="form-control form-control-sm" placeholder="Tahun" value="${new Date().getFullYear()}" style="font-size: 0.82rem;">
            </td>
            <td style="padding: 10px 8px;">
                <input type="text" name="riwayat_prestasi[${newIdx}][penyelenggara]" class="form-control form-control-sm" placeholder="Penyelenggara" style="font-size: 0.82rem;">
            </td>
            <td style="padding: 10px 8px; width: 100px;">
                <input type="text" name="riwayat_prestasi[${newIdx}][peringkat]" class="form-control form-control-sm" placeholder="Juara 1 / dll" style="font-size: 0.82rem;">
            </td>
            <td style="padding: 10px 8px; text-align: center; width: 45px;">
                <button type="button" class="btn-icon text-danger btn-remove-row" title="Hapus Baris">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        `;

        tbody.appendChild(tr);
        checkEmptyPlaceholder(tbody, 'emptyPrestasi');
    });

    tbody.addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-remove-row');
        if (!btn) return;
        const tr = btn.closest('tr');
        if (tr) tr.remove();
        checkEmptyPlaceholder(tbody, 'emptyPrestasi');
    });
}

/**
 * Repeater Baris Perlindungan Sosial / Kesejahteraan (KKS/KIP/KIS/PIP)
 */
function initSosialRepeater() {
    const btnAdd = document.getElementById('btnAddSosial');
    const tbody = document.getElementById('tbodySosial');
    if (!btnAdd || !tbody) return;

    btnAdd.addEventListener('click', function () {
        const rowCount = tbody.querySelectorAll('tr.row-sosial').length;
        const newIdx = rowCount;

        const tr = document.createElement('tr');
        tr.className = 'row-sosial';
        tr.style.borderBottom = '1px solid var(--border-color)';
        tr.innerHTML = `
            <td style="padding: 10px 8px;">
                <select name="perlindungan_sosial[${newIdx}][jenis]" class="form-select form-select-sm" style="font-size: 0.82rem;">
                    <option value="Program Indonesia Pintar (PIP)">Program Indonesia Pintar (PIP)</option>
                    <option value="Kartu Indonesia Pintar (KIP)">Kartu Indonesia Pintar (KIP)</option>
                    <option value="Kartu Keluarga Sejahtera (KKS)">Kartu Keluarga Sejahtera (KKS)</option>
                    <option value="Kartu Indonesia Sehat (KIS)">Kartu Indonesia Sehat (KIS)</option>
                    <option value="Orang Asli Papua (OAP)">Orang Asli Papua (OAP)</option>
                    <option value="Bantuan Pemerintah Daerah">Bantuan Pemerintah Daerah</option>
                    <option value="Lainnya">Lainnya</option>
                </select>
            </td>
            <td style="padding: 10px 8px;">
                <input type="text" name="perlindungan_sosial[${newIdx}][no_kartu]" class="form-control form-control-sm" placeholder="Nomor Fisik Kartu" style="font-size: 0.82rem;" required>
            </td>
            <td style="padding: 10px 8px;">
                <input type="text" name="perlindungan_sosial[${newIdx}][nama_di_kartu]" class="form-control form-control-sm" placeholder="Nama Tertera di Kartu" style="font-size: 0.82rem;">
            </td>
            <td style="padding: 10px 8px; width: 95px;">
                <input type="number" name="perlindungan_sosial[${newIdx}][tahun_mulai]" class="form-control form-control-sm" placeholder="Mulai" value="${new Date().getFullYear()}" style="font-size: 0.82rem;">
            </td>
            <td style="padding: 10px 8px; width: 95px;">
                <input type="number" name="perlindungan_sosial[${newIdx}][tahun_selesai]" class="form-control form-control-sm" placeholder="Selesai" style="font-size: 0.82rem;">
            </td>
            <td style="padding: 10px 8px; text-align: center; width: 45px;">
                <button type="button" class="btn-icon text-danger btn-remove-row" title="Hapus Baris">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        `;

        tbody.appendChild(tr);
        checkEmptyPlaceholder(tbody, 'emptySosial');
    });

    tbody.addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-remove-row');
        if (!btn) return;
        const tr = btn.closest('tr');
        if (tr) tr.remove();
        checkEmptyPlaceholder(tbody, 'emptySosial');
    });
}

function checkEmptyPlaceholder(tbody, emptyId) {
    const rowCount = tbody.querySelectorAll('tr.row-prestasi, tr.row-sosial').length;
    const placeholder = document.getElementById(emptyId);
    if (placeholder) {
        placeholder.style.display = rowCount === 0 ? '' : 'none';
    }
}

/**
 * Tab Navigasi Seksi Formulir (Semua, Pribadi & Alamat, Orang Tua & Wali, Akademik & Bantuan)
 */
function initSectionTabs() {
    const tabBtns = document.querySelectorAll('.identitas-tab-btn');
    const sections = document.querySelectorAll('.identitas-form-section');

    tabBtns.forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const targetSection = this.getAttribute('data-target');

            tabBtns.forEach(b => b.classList.remove('active', 'btn-primary'));
            tabBtns.forEach(b => b.classList.add('btn-outline'));

            this.classList.remove('btn-outline');
            this.classList.add('active', 'btn-primary');

            sections.forEach(sec => {
                if (targetSection === 'all' || sec.getAttribute('data-section') === targetSection) {
                    sec.style.display = 'block';
                } else {
                    sec.style.display = 'none';
                }
            });
        });
    });
}

/**
 * Dialog Konfirmasi Validitas Data Identitas Siswa (Modal Native SAE Theme)
 * Tile 1: Data Sudah Sesuai (Terkunci)
 * Tile 2: Data Belum Sesuai (Perlu Perbaikan / Buka Usulan)
 */
function initKonfirmasiDialog() {
    // Buka Modal Konfirmasi saat tombol diklik
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-konfirmasi-dialog, .btn-konfirmasi-trigger');
        if (btn) {
            e.preventDefault();
            const modal = document.getElementById('modalKonfirmasiData');
            if (modal) {
                modal.style.display = 'flex';
                document.body.style.overflow = 'hidden';
            }
        }
    });

    // Handle klik pada Opsi Tile Konfirmasi (Sesuai atau Perlu Perbaikan)
    document.addEventListener('click', function (e) {
        const tile = e.target.closest('.btn-tile-konfirmasi');
        if (tile) {
            e.preventDefault();
            const status = tile.getAttribute('data-status');
            const catatanInput = document.getElementById('inputCatatanKonfirmasiModal');
            const catatanVal = catatanInput ? catatanInput.value.trim() : '';

            const hiddenForm = document.getElementById('formActionKonfirmasi');
            const statusHidden = document.getElementById('inputStatusKonfirmasi');
            const catatanHidden = document.getElementById('inputCatatanKonfirmasi');

            if (hiddenForm && statusHidden) {
                statusHidden.value = status;
                if (catatanHidden) catatanHidden.value = catatanVal;

                tile.disabled = true;
                tile.style.opacity = '0.7';
                tile.innerHTML = '<i class="fas fa-spinner fa-spin" style="font-size: 1.5rem;"></i><div style="font-size: 0.85rem; font-weight: 700; margin-top: 4px;">Menyimpan...</div>';
                hiddenForm.submit();
            }
        }
    });
}

/**
 * Inisialisasi Cascading Dropdown Wilayah Indonesia (Provinsi -> Kab/Kota -> Kecamatan -> Desa/Kelurahan)
 * Mempersempit hirarki wilayah secara terstruktur dari tingkat nasional hingga desa/kelurahan
 */
function initWilayahCascade() {
    const selProv = document.getElementById('selectProvinsi');
    const selKab = document.getElementById('selectKabupaten');
    const selKec = document.getElementById('selectKecamatan');
    const selDesa = document.getElementById('selectDesa');
    const badgeLoading = document.getElementById('badgeProvLoading');

    if (!selProv || !selKab || !selKec || !selDesa) return;

    // Nilai awal dari database / server
    const initProv = (selProv.getAttribute('data-initial') || '').trim();
    const initKab = (selKab.getAttribute('data-initial') || '').trim();
    const initKec = (selKec.getAttribute('data-initial') || '').trim();
    const initDesa = (selDesa.getAttribute('data-initial') || '').trim();

    // Cache aman di sessionStorage
    const fetchApi = async (url) => {
        const cacheKey = 'sae_wilayah_' + url.replace(/[^a-zA-Z0-9_]/g, '_');
        try {
            const cached = sessionStorage.getItem(cacheKey);
            if (cached) return JSON.parse(cached);
        } catch (e) {}

        let data = [];
        try {
            const res = await fetch(url, { headers: { Accept: 'application/json' } });
            if (res.ok) {
                data = await res.json();
            }
        } catch (e) {
            console.warn('Gagal fetch wilayah internal:', url, e);
        }

        // Fallback langsung ke mirror publik jika endpoint lokal gagal
        if (!data || data.length === 0) {
            let mirrorUrl = '';
            if (url.includes('provinces')) {
                mirrorUrl = 'https://www.emsifa.com/api-wilayah-indonesia/api/provinces.json';
            } else if (url.includes('regencies/')) {
                const id = url.split('regencies/')[1];
                mirrorUrl = `https://www.emsifa.com/api-wilayah-indonesia/api/regencies/${id}.json`;
            } else if (url.includes('districts/')) {
                const id = url.split('districts/')[1];
                mirrorUrl = `https://www.emsifa.com/api-wilayah-indonesia/api/districts/${id}.json`;
            } else if (url.includes('villages/')) {
                const id = url.split('villages/')[1];
                mirrorUrl = `https://www.emsifa.com/api-wilayah-indonesia/api/villages/${id}.json`;
            }
            if (mirrorUrl) {
                try {
                    const mRes = await fetch(mirrorUrl);
                    if (mRes.ok) data = await mRes.json();
                } catch (err) {}
            }
        }

        if (Array.isArray(data) && data.length > 0) {
            try { sessionStorage.setItem(cacheKey, JSON.stringify(data)); } catch (e) {}
        }

        return Array.isArray(data) ? data : [];
    };

    // Helper pembersih & pembanding string nama wilayah (toleran variasi singkatan Kemendagri / BPS / Dapodik)
    const normalize = (str) => {
        if (!str) return '';
        return str.toUpperCase()
            .replace(/^KABUPATEN\s+/, '')
            .replace(/^KAB\.\s+/, '')
            .replace(/^KOTA\s+/, '')
            .replace(/^KECAMATAN\s+/, '')
            .replace(/^KEC\.\s+/, '')
            .replace(/^DESA\s+/, '')
            .replace(/^KELURAHAN\s+/, '')
            .replace(/^KEL\.\s+/, '')
            .replace(/[^A-Z0-9]/g, '');
    };

    const matchOption = (list, targetName) => {
        if (!targetName) return null;
        const normTarget = normalize(targetName);
        if (!normTarget) return null;

        // 1. Exact match
        let found = list.find(item => item.name.toUpperCase() === targetName.toUpperCase());
        if (found) return found;

        // 2. Normalized match (abaikan prefiks KAB/KOTA/KEC/DESA)
        found = list.find(item => normalize(item.name) === normTarget);
        if (found) return found;

        // 3. Substring match
        found = list.find(item => {
            const n = normalize(item.name);
            return (n.length >= 3 && normTarget.includes(n)) || (normTarget.length >= 3 && n.includes(normTarget));
        });
        return found || null;
    };

    // 1. Load Daftar Provinsi
    const loadProvinces = async () => {
        if (badgeLoading) badgeLoading.style.display = 'inline-block';
        selProv.innerHTML = '<option value="">-- Memuat Daftar Provinsi... --</option>';

        const list = await fetchApi('/dashboard/wilayah/provinces');
        if (badgeLoading) badgeLoading.style.display = 'none';

        selProv.innerHTML = '<option value="">-- Pilih Provinsi --</option>';
        list.forEach(p => {
            const opt = document.createElement('option');
            opt.value = p.name;
            opt.textContent = p.name;
            opt.dataset.id = p.id;
            selProv.appendChild(opt);
        });

        const optManual = document.createElement('option');
        optManual.value = '__manual__';
        optManual.textContent = '+ Ketik Nama Lainnya Secara Manual...';
        selProv.appendChild(optManual);

        if (initProv) {
            const matched = matchOption(list, initProv);
            if (matched) {
                selProv.value = matched.name;
                await loadRegencies(matched.id, initKab);
            } else {
                // Pertahankan nilai awal yang tersimpan di database
                const optCustom = document.createElement('option');
                optCustom.value = initProv;
                optCustom.textContent = initProv;
                optCustom.selected = true;
                selProv.insertBefore(optCustom, optManual);
            }
        }
    };

    // 2. Load Daftar Kabupaten / Kota berdasarkan ID Provinsi
    const loadRegencies = async (provId, selectedKabName = '') => {
        selKab.disabled = true;
        selKec.disabled = true;
        selDesa.disabled = true;
        selKab.innerHTML = '<option value="">-- Memuat Kabupaten/Kota... --</option>';
        selKec.innerHTML = '<option value="">-- Pilih Kecamatan --</option>';
        selDesa.innerHTML = '<option value="">-- Pilih Desa / Kelurahan --</option>';

        if (!provId) {
            selKab.innerHTML = '<option value="">-- Pilih Kabupaten / Kota --</option>';
            return;
        }

        const list = await fetchApi(`/dashboard/wilayah/regencies/${provId}`);
        selKab.innerHTML = '<option value="">-- Pilih Kabupaten / Kota --</option>';
        list.forEach(k => {
            const opt = document.createElement('option');
            opt.value = k.name;
            opt.textContent = k.name;
            opt.dataset.id = k.id;
            selKab.appendChild(opt);
        });

        const optManual = document.createElement('option');
        optManual.value = '__manual__';
        optManual.textContent = '+ Ketik Nama Lainnya Secara Manual...';
        selKab.appendChild(optManual);
        selKab.disabled = false;

        const targetKab = selectedKabName || initKab;
        if (targetKab) {
            const matched = matchOption(list, targetKab);
            if (matched) {
                selKab.value = matched.name;
                await loadDistricts(matched.id, initKec);
            } else if (selectedKabName) {
                const optCustom = document.createElement('option');
                optCustom.value = selectedKabName;
                optCustom.textContent = selectedKabName;
                optCustom.selected = true;
                selKab.insertBefore(optCustom, optManual);
            }
        }
    };

    // 3. Load Daftar Kecamatan berdasarkan ID Kabupaten / Kota
    const loadDistricts = async (kabId, selectedKecName = '') => {
        selKec.disabled = true;
        selDesa.disabled = true;
        selKec.innerHTML = '<option value="">-- Memuat Kecamatan... --</option>';
        selDesa.innerHTML = '<option value="">-- Pilih Desa / Kelurahan --</option>';

        if (!kabId) {
            selKec.innerHTML = '<option value="">-- Pilih Kecamatan --</option>';
            return;
        }

        const list = await fetchApi(`/dashboard/wilayah/districts/${kabId}`);
        selKec.innerHTML = '<option value="">-- Pilih Kecamatan --</option>';
        list.forEach(k => {
            const opt = document.createElement('option');
            opt.value = k.name;
            opt.textContent = k.name;
            opt.dataset.id = k.id;
            selKec.appendChild(opt);
        });

        const optManual = document.createElement('option');
        optManual.value = '__manual__';
        optManual.textContent = '+ Ketik Nama Lainnya Secara Manual...';
        selKec.appendChild(optManual);
        selKec.disabled = false;

        const targetKec = selectedKecName || initKec;
        if (targetKec) {
            const matched = matchOption(list, targetKec);
            if (matched) {
                selKec.value = matched.name;
                await loadVillages(matched.id, initDesa);
            } else if (selectedKecName) {
                const optCustom = document.createElement('option');
                optCustom.value = selectedKecName;
                optCustom.textContent = selectedKecName;
                optCustom.selected = true;
                selKec.insertBefore(optCustom, optManual);
            }
        }
    };

    // 4. Load Daftar Desa / Kelurahan berdasarkan ID Kecamatan
    const loadVillages = async (kecId, selectedDesaName = '') => {
        selDesa.disabled = true;
        selDesa.innerHTML = '<option value="">-- Memuat Desa/Kelurahan... --</option>';

        if (!kecId) {
            selDesa.innerHTML = '<option value="">-- Pilih Desa / Kelurahan --</option>';
            return;
        }

        const list = await fetchApi(`/dashboard/wilayah/villages/${kecId}`);
        selDesa.innerHTML = '<option value="">-- Pilih Desa / Kelurahan --</option>';
        list.forEach(v => {
            const opt = document.createElement('option');
            opt.value = v.name;
            opt.textContent = v.name;
            opt.dataset.id = v.id;
            selDesa.appendChild(opt);
        });

        const optManual = document.createElement('option');
        optManual.value = '__manual__';
        optManual.textContent = '+ Ketik Nama Lainnya Secara Manual...';
        selDesa.appendChild(optManual);
        selDesa.disabled = false;

        const targetDesa = selectedDesaName || initDesa;
        if (targetDesa) {
            const matched = matchOption(list, targetDesa);
            if (matched) {
                selDesa.value = matched.name;
            } else if (selectedDesaName) {
                const optCustom = document.createElement('option');
                optCustom.value = selectedDesaName;
                optCustom.textContent = selectedDesaName;
                optCustom.selected = true;
                selDesa.insertBefore(optCustom, optManual);
            }
        }
    };

    // Event Listener Cascade Dinamis
    selProv.addEventListener('change', function () {
        if (this.value === '__manual__') {
            const manual = prompt('Masukkan Nama Provinsi:');
            if (manual && manual.trim()) {
                const opt = document.createElement('option');
                opt.value = manual.trim().toUpperCase();
                opt.textContent = manual.trim().toUpperCase();
                opt.selected = true;
                this.insertBefore(opt, this.lastElementChild);
            } else {
                this.value = '';
            }
            selKab.innerHTML = '<option value="">-- Pilih Kabupaten / Kota --</option>';
            selKab.disabled = false;
            return;
        }

        const selOpt = this.options[this.selectedIndex];
        const provId = selOpt ? selOpt.dataset.id : '';
        loadRegencies(provId);
    });

    selKab.addEventListener('change', function () {
        if (this.value === '__manual__') {
            const manual = prompt('Masukkan Nama Kabupaten / Kota:');
            if (manual && manual.trim()) {
                const opt = document.createElement('option');
                opt.value = manual.trim().toUpperCase();
                opt.textContent = manual.trim().toUpperCase();
                opt.selected = true;
                this.insertBefore(opt, this.lastElementChild);
            } else {
                this.value = '';
            }
            selKec.innerHTML = '<option value="">-- Pilih Kecamatan --</option>';
            selKec.disabled = false;
            return;
        }

        const selOpt = this.options[this.selectedIndex];
        const kabId = selOpt ? selOpt.dataset.id : '';
        loadDistricts(kabId);
    });

    selKec.addEventListener('change', function () {
        if (this.value === '__manual__') {
            const manual = prompt('Masukkan Nama Kecamatan:');
            if (manual && manual.trim()) {
                const opt = document.createElement('option');
                opt.value = manual.trim().toUpperCase();
                opt.textContent = manual.trim().toUpperCase();
                opt.selected = true;
                this.insertBefore(opt, this.lastElementChild);
            } else {
                this.value = '';
            }
            selDesa.innerHTML = '<option value="">-- Pilih Desa / Kelurahan --</option>';
            selDesa.disabled = false;
            return;
        }

        const selOpt = this.options[this.selectedIndex];
        const kecId = selOpt ? selOpt.dataset.id : '';
        loadVillages(kecId);
    });

    selDesa.addEventListener('change', function () {
        if (this.value === '__manual__') {
            const manual = prompt('Masukkan Nama Desa / Kelurahan:');
            if (manual && manual.trim()) {
                const opt = document.createElement('option');
                opt.value = manual.trim().toUpperCase();
                opt.textContent = manual.trim().toUpperCase();
                opt.selected = true;
                this.insertBefore(opt, this.lastElementChild);
            } else {
                this.value = '';
            }
        }
    });

    // Jalankan inisialisasi awal
    loadProvinces();
}
