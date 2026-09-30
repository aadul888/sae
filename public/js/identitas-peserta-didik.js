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
