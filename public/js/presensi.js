/**
 * SAE - Sistem Presensi Peserta Didik
 * Logic for Main Dashboard & RFID Management
 */

document.addEventListener('DOMContentLoaded', function () {
    // 1. Tab Switching Logic & State Retention (Desktop & Mobile Dropdown)
    const tabButtons = document.querySelectorAll('.presensi-tab-btn');
    const tabDropdownItems = document.querySelectorAll('.presensi-dropdown-tab');
    const tabContents = document.querySelectorAll('.presensi-tab-pane');
    const dropdownLabel = document.getElementById('presensiTabDropdownLabel');
    const dropdownTrigger = document.getElementById('presensiTabDropdownTrigger');

    const tabMeta = {
        'log': { icon: 'fa-list-check', label: 'Log Presensi Hari Ini' },
        'rekap': { icon: 'fa-chart-column', label: 'Rekapitulasi & Laporan' },
        'rfid': { icon: 'fa-id-card', label: 'Manajemen Kartu RFID' },
        'izin': { icon: 'fa-envelope-open-text', label: 'Pengajuan E-Izin' },
        'pengaturan': { icon: 'fa-sliders', label: 'Pengaturan Jam & Jadwal' }
    };

    function switchTab(targetTab) {
        // Desktop tabs update
        tabButtons.forEach(b => b.classList.remove('active'));
        const activeBtn = document.querySelector(`.presensi-tab-btn[data-tab="${targetTab}"]`);
        if (activeBtn) activeBtn.classList.add('active');

        // Mobile dropdown items update
        tabDropdownItems.forEach(item => item.classList.remove('active'));
        const activeDropdownItem = document.querySelector(`.presensi-dropdown-tab[data-tab="${targetTab}"]`);
        if (activeDropdownItem) activeDropdownItem.classList.add('active');

        // Update mobile trigger label
        if (dropdownLabel && tabMeta[targetTab]) {
            const meta = tabMeta[targetTab];
            dropdownLabel.innerHTML = `<i class="fas ${meta.icon} text-primary me-2"></i><span>${meta.label}</span>`;
        }

        // Close dropdown
        const customDropdown = document.querySelector('.dash-custom-dropdown.open');
        if (customDropdown) {
            customDropdown.classList.remove('open');
            if (dropdownTrigger) dropdownTrigger.setAttribute('aria-expanded', 'false');
        }

        // Show target pane
        tabContents.forEach(pane => pane.style.display = 'none');
        const activePane = document.getElementById('tab-' + targetTab);
        if (activePane) activePane.style.display = 'block';

        // Update URL query param 'tab' and hash without full reload
        const url = new URL(window.location.href);
        url.searchParams.set('tab', targetTab);
        history.replaceState(null, null, url.toString());
    }

    tabButtons.forEach(btn => {
        btn.addEventListener('click', function () {
            const targetTab = this.getAttribute('data-tab');
            switchTab(targetTab);
        });
    });

    tabDropdownItems.forEach(item => {
        item.addEventListener('click', function (e) {
            e.preventDefault();
            const targetTab = this.getAttribute('data-tab');
            switchTab(targetTab);
        });
    });

    // Cek URL param 'tab' atau URL hash saat initial load
    const urlParams = new URLSearchParams(window.location.search);
    const tabFromParam = urlParams.get('tab');
    const tabFromHash = window.location.hash ? window.location.hash.substring(1) : null;
    const initialTab = tabFromParam || tabFromHash;
    if (initialTab && document.getElementById('tab-' + initialTab)) {
        switchTab(initialTab);
    }

    // --- Per-Page Select Handlers ---
    const perPageLogSelect = document.getElementById('perPageLogSelect');
    if (perPageLogSelect) {
        perPageLogSelect.addEventListener('change', function () {
            const hiddenPerPage = document.getElementById('inputHiddenPerPageLog');
            if (hiddenPerPage) hiddenPerPage.value = this.value;
            const url = new URL(window.location.href);
            url.searchParams.set('perPageLog', this.value);
            url.searchParams.set('tab', 'log');
            url.searchParams.set('page', '1');
            window.location.href = url.toString();
        });
    }

    const perPageRfidSelect = document.getElementById('perPageRfidSelect');
    if (perPageRfidSelect) {
        perPageRfidSelect.addEventListener('change', function () {
            const hiddenPerPage = document.getElementById('inputHiddenPerPageRfid');
            if (hiddenPerPage) hiddenPerPage.value = this.value;
            const url = new URL(window.location.href);
            url.searchParams.set('perPageRfid', this.value);
            url.searchParams.set('tab', 'rfid');
            url.searchParams.set('rfid_page', '1');
            window.location.href = url.toString();
        });
    }

    // --- Live Search Debounce (850ms) for Log Presensi ---
    const logSearchInput = document.getElementById('logSearchInput');
    const clearLogSearch = document.getElementById('clearLogSearch');
    const formFilterLog = document.getElementById('formFilterLog');
    let logSearchTimer = null;

    if (logSearchInput && formFilterLog) {
        logSearchInput.addEventListener('input', function () {
            if (clearLogSearch) {
                clearLogSearch.classList.toggle('visible', this.value.trim().length > 0);
            }
            clearTimeout(logSearchTimer);
            logSearchTimer = setTimeout(() => {
                formFilterLog.submit();
            }, 850);
        });

        logSearchInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                clearTimeout(logSearchTimer);
                formFilterLog.submit();
            }
        });
    }

    if (clearLogSearch && logSearchInput && formFilterLog) {
        clearLogSearch.addEventListener('click', function () {
            logSearchInput.value = '';
            clearLogSearch.classList.remove('visible');
            formFilterLog.submit();
        });
    }

    // --- Live Search Debounce (850ms) for RFID Siswa ---
    const rfidSearchInput = document.getElementById('rfidSearchInput');
    const clearRfidSearch = document.getElementById('clearRfidSearch');
    const formFilterRfid = document.getElementById('formFilterRfid');
    let rfidSearchTimer = null;

    if (rfidSearchInput && formFilterRfid) {
        rfidSearchInput.addEventListener('input', function () {
            if (clearRfidSearch) {
                clearRfidSearch.classList.toggle('visible', this.value.trim().length > 0);
            }
            clearTimeout(rfidSearchTimer);
            rfidSearchTimer = setTimeout(() => {
                formFilterRfid.submit();
            }, 850);
        });

        rfidSearchInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                clearTimeout(rfidSearchTimer);
                formFilterRfid.submit();
            }
        });
    }

    if (clearRfidSearch && rfidSearchInput && formFilterRfid) {
        clearRfidSearch.addEventListener('click', function () {
            rfidSearchInput.value = '';
            clearRfidSearch.classList.remove('visible');
            formFilterRfid.submit();
        });
    }

    // 2. Modal Pasangkan / Binding Kartu RFID
    const modalRfid = document.getElementById('modalAssignRfid');
    const formRfid = document.getElementById('formAssignRfid');
    const inputPdId = document.getElementById('rfidPdId');
    const inputPdNama = document.getElementById('rfidPdNama');
    const inputPdNisn = document.getElementById('rfidPdNisn');
    const inputRfidUid = document.getElementById('rfidUidInput');
    const btnCloseRfidModal = document.getElementById('btnCloseRfidModal');
    const btnCancelRfidModal = document.getElementById('btnCancelRfidModal');

    document.querySelectorAll('.btn-assign-rfid').forEach(btn => {
        btn.addEventListener('click', function () {
            const pdId = this.getAttribute('data-id');
            const nama = this.getAttribute('data-nama');
            const nisn = this.getAttribute('data-nisn');
            const currentRfid = this.getAttribute('data-rfid') || '';

            inputPdId.value = pdId;
            inputPdNama.textContent = nama;
            inputPdNisn.textContent = nisn;
            inputRfidUid.value = currentRfid;

            modalRfid.style.display = 'flex';
            setTimeout(() => inputRfidUid.focus(), 150);
        });
    });

    function closeRfidModal() {
        if (modalRfid) modalRfid.style.display = 'none';
    }

    if (btnCloseRfidModal) btnCloseRfidModal.addEventListener('click', closeRfidModal);
    if (btnCancelRfidModal) btnCancelRfidModal.addEventListener('click', closeRfidModal);

    if (formRfid) {
        formRfid.addEventListener('submit', async function (e) {
            e.preventDefault();

            const pdId = inputPdId.value;
            const rfidUid = inputRfidUid.value.trim();

            if (!rfidUid) {
                Swal.fire({
                    icon: 'warning',
                    title: 'UID Kartu Kosong',
                    text: 'Silakan tap kartu pada reader atau ketikkan UID kartu RFID.',
                });
                return;
            }

            try {
                Swal.fire({
                    title: 'Menyimpan...',
                    text: 'Mendaftarkan kartu RFID ke siswa...',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });

                const response = await fetch('/dashboard/presensi/rfid/assign', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        peserta_didik_id: pdId,
                        rfid_uid: rfidUid
                    })
                });

                const res = await response.json();

                if (response.ok && res.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: res.message,
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        closeRfidModal();
                        window.location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal Menyimpan',
                        text: res.message || 'Terjadi kesalahan sistem.'
                    });
                }
            } catch (err) {
                Swal.fire({
                    icon: 'error',
                    title: 'Kesalahan Jaringan',
                    text: 'Tidak dapat terhubung ke server.'
                });
            }
        });
    }

    // 3. Form Simpan Pengaturan Presensi
    const formPengaturan = document.getElementById('formPengaturanPresensi');
    if (formPengaturan) {
        formPengaturan.addEventListener('submit', async function (e) {
            e.preventDefault();

            const formData = new FormData(this);
            const dataObj = {};
            const hariAktif = [];

            formData.forEach((val, key) => {
                if (key === 'hari_aktif[]') {
                    hariAktif.push(val);
                } else {
                    dataObj[key] = val;
                }
            });
            dataObj['hari_aktif'] = hariAktif;
            dataObj['require_camera'] = document.getElementById('settingRequireCamera')?.checked ? 1 : 0;
            dataObj['allow_rfid'] = document.getElementById('settingAllowRfid')?.checked ? 1 : 0;
            dataObj['allow_qr'] = document.getElementById('settingAllowQr')?.checked ? 1 : 0;

            try {
                Swal.fire({
                    title: 'Menyimpan Pengaturan...',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });

                const response = await fetch('/dashboard/presensi/pengaturan', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(dataObj)
                });

                const res = await response.json();

                if (response.ok && res.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Tersimpan!',
                        text: res.message,
                        timer: 1500,
                        showConfirmButton: false
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: res.message || 'Gagal menyimpan pengaturan.'
                    });
                }
            } catch (err) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Tidak dapat menghubungi server.'
                });
            }
        });
    }

    // 4. Modal Verifikasi Pengajuan Izin
    document.querySelectorAll('.btn-verif-izin').forEach(btn => {
        btn.addEventListener('click', function () {
            const izinId = this.getAttribute('data-id');
            const nama = this.getAttribute('data-nama');
            const jenis = this.getAttribute('data-jenis');
            const rentang = this.getAttribute('data-rentang');
            const alasan = this.getAttribute('data-alasan');

            Swal.fire({
                title: `Verifikasi ${jenis}`,
                html: `
                    <div style="text-align: left; font-size: 0.9rem;">
                        <p><strong>Nama Siswa:</strong> ${nama}</p>
                        <p><strong>Rentang Tanggal:</strong> ${rentang}</p>
                        <p><strong>Alasan:</strong> ${alasan}</p>
                        <hr style="border-color: var(--border-color); margin: 12px 0;">
                        <label style="display:block; margin-bottom: 6px; font-weight: 600;">Catatan Verifikator (Opsional):</label>
                        <input type="text" id="swalCatatanIzin" class="input" style="width: 100%;" placeholder="Catatan persetujuan/penolakan...">
                    </div>
                `,
                showCancelButton: true,
                showDenyButton: true,
                confirmButtonText: '<i class="fas fa-check"></i> Setujui',
                denyButtonText: '<i class="fas fa-times"></i> Tolak',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#10b981',
                denyButtonColor: '#ef4444'
            }).then(async (result) => {
                if (result.isConfirmed || result.isDenied) {
                    const statusVal = result.isConfirmed ? 'disetujui' : 'ditolak';
                    const catatan = document.getElementById('swalCatatanIzin')?.value || '';

                    try {
                        const res = await fetch(`/dashboard/presensi/izin/${izinId}/verifikasi`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({ status: statusVal, catatan: catatan })
                        });
                        const data = await res.json();
                        if (res.ok) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil!',
                                text: data.message,
                                timer: 1500,
                                showConfirmButton: false
                            }).then(() => window.location.reload());
                        } else {
                            Swal.fire('Error', data.message || 'Gagal memproses izin.', 'error');
                        }
                    } catch (e) {
                        Swal.fire('Error', 'Gagal memproses verifikasi izin.', 'error');
                    }
                }
            });
        });
    });

    // 5. Preview Foto Snapshot Presensi Modal
    const modalFoto = document.getElementById('modalFotoSnapshot');
    const imgFotoSnapshot = document.getElementById('imgFotoSnapshot');
    const fotoSnapshotTitle = document.getElementById('fotoSnapshotTitle');
    const btnCloseFotoModal = document.getElementById('btnCloseFotoModal');

    document.querySelectorAll('.btn-preview-snapshot').forEach(btn => {
        btn.addEventListener('click', function () {
            const url = this.getAttribute('data-url');
            const title = this.getAttribute('data-title') || 'Foto Bukti Presensi';

            if (imgFotoSnapshot && modalFoto) {
                imgFotoSnapshot.src = url;
                if (fotoSnapshotTitle) fotoSnapshotTitle.textContent = title;
                modalFoto.style.display = 'flex';
            }
        });
    });

    if (btnCloseFotoModal) {
        btnCloseFotoModal.addEventListener('click', () => {
            if (modalFoto) modalFoto.style.display = 'none';
        });
    }
});
