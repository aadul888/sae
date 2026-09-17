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

    // --- Live Search Debounce (850ms) for RFID Peserta Didik ---
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
                    text: 'Mendaftarkan kartu RFID ke peserta didik...',
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
        // Toggle Pilih Semua / Hapus Semua Jurusan
        const btnSelectAllJurusan = document.getElementById('btnSelectAllJurusan');
        const btnDeselectAllJurusan = document.getElementById('btnDeselectAllJurusan');
        if (btnSelectAllJurusan) {
            btnSelectAllJurusan.addEventListener('click', () => {
                document.querySelectorAll('.chk-jurusan').forEach(chk => chk.checked = true);
            });
        }
        if (btnDeselectAllJurusan) {
            btnDeselectAllJurusan.addEventListener('click', () => {
                document.querySelectorAll('.chk-jurusan').forEach(chk => chk.checked = false);
            });
        }

        // Interaksi Pengaturan Radius & Koordinat GPS
        document.querySelectorAll('.btn-radius-chip').forEach(chip => {
            chip.addEventListener('click', function () {
                const r = this.getAttribute('data-radius');
                const inputR = document.getElementById('inputRadiusMeter');
                const labelR = document.getElementById('labelRadiusDisplay');
                if (inputR) inputR.value = r;
                if (labelR) labelR.textContent = `${r} Meter`;
            });
        });

        const inputRadius = document.getElementById('inputRadiusMeter');
        if (inputRadius) {
            inputRadius.addEventListener('input', function () {
                const labelR = document.getElementById('labelRadiusDisplay');
                if (labelR) labelR.textContent = `${this.value || 0} Meter`;
            });
        }

        function updateGoogleMapsLink() {
            const lat = document.getElementById('inputLatitude')?.value;
            const lon = document.getElementById('inputLongitude')?.value;
            const btnMaps = document.getElementById('btnOpenGoogleMaps');
            if (btnMaps && lat && lon) {
                btnMaps.href = `https://www.google.com/maps?q=${encodeURIComponent(lat)},${encodeURIComponent(lon)}`;
            }
        }
        document.getElementById('inputLatitude')?.addEventListener('input', updateGoogleMapsLink);
        document.getElementById('inputLongitude')?.addEventListener('input', updateGoogleMapsLink);

        // Gunakan Titik Dapodik Sekolah
        const btnResetDapodik = document.getElementById('btnResetToDapodikLocation');
        if (btnResetDapodik) {
            btnResetDapodik.addEventListener('click', function () {
                const labelDapodik = document.getElementById('labelDapodikCoord');
                const lat = labelDapodik?.getAttribute('data-lat');
                const lon = labelDapodik?.getAttribute('data-lon');
                if (lat && lon) {
                    const inLat = document.getElementById('inputLatitude');
                    const inLon = document.getElementById('inputLongitude');
                    if (inLat) inLat.value = lat;
                    if (inLon) inLon.value = lon;
                    updateGoogleMapsLink();
                    Swal.fire({
                        icon: 'info',
                        title: 'Titik Dapodik Diterapkan',
                        text: `Lintang: ${lat}, Bujur: ${lon}`,
                        timer: 1500,
                        showConfirmButton: false
                    });
                } else {
                    Swal.fire('Informasi', 'Data koordinat sekolah di Dapodik belum terisi.', 'warning');
                }
            });
        }

        // Deteksi Lokasi GPS Saya
        const btnDetectGPS = document.getElementById('btnDetectMyLocation');
        if (btnDetectGPS) {
            btnDetectGPS.addEventListener('click', function () {
                if (!navigator.geolocation) {
                    Swal.fire('Tidak Didukung', 'Browser Anda tidak mendukung deteksi geolokasi GPS.', 'error');
                    return;
                }

                Swal.fire({
                    title: 'Mendeteksi Lokasi GPS...',
                    text: 'Harap izinkan akses lokasi pada browser Anda.',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });

                navigator.geolocation.getCurrentPosition(
                    (pos) => {
                        const lat = pos.coords.latitude.toFixed(8);
                        const lon = pos.coords.longitude.toFixed(8);
                        const inLat = document.getElementById('inputLatitude');
                        const inLon = document.getElementById('inputLongitude');
                        if (inLat) inLat.value = lat;
                        if (inLon) inLon.value = lon;
                        updateGoogleMapsLink();
                        Swal.fire({
                            icon: 'success',
                            title: 'Lokasi Terdeteksi!',
                            text: `GPS: ${lat}, ${lon} (Akurasi: ±${Math.round(pos.coords.accuracy)}m)`,
                            timer: 2000,
                            showConfirmButton: false
                        });
                    },
                    (err) => {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal Mendeteksi GPS',
                            text: err.message || 'Izin lokasi ditolak atau sinyal GPS tidak tersedia.'
                        });
                    },
                    { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
                );
            });
        }

        // Interaksi Pengaturan Kode Akses Kiosk Publik
        const btnToggleShowCode = document.getElementById('btnToggleShowKodeAkses');
        const inputKodeAkses = document.getElementById('inputKodeAksesKiosk');
        const btnCopyCode = document.getElementById('btnCopyKodeAkses');
        const btnGenerateCode = document.getElementById('btnGenerateRandomCode');

        function copyKodeAksesToClipboard(code, silent = false) {
            if (!code) return;
            const successNotice = () => {
                if (!silent) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Kode Akses Disalin!',
                        text: `Kode "${code}" berhasil disalin ke clipboard.`,
                        timer: 2000,
                        showConfirmButton: false,
                        toast: true,
                        position: 'top-end'
                    });
                }
            };

            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(code).then(successNotice).catch(() => fallbackClipboard(code, successNotice));
            } else {
                fallbackClipboard(code, successNotice);
            }
        }

        function fallbackClipboard(text, callback) {
            const textArea = document.createElement('textarea');
            textArea.value = text;
            textArea.style.position = 'fixed';
            textArea.style.left = '-999999px';
            textArea.style.top = '-999999px';
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            try {
                document.execCommand('copy');
                if (callback) callback();
            } catch (err) {
                console.error('Fallback clipboard copy failed:', err);
            }
            document.body.removeChild(textArea);
        }

        async function saveKodeAksesAjax(code) {
            const cleanCode = (code || '').trim().toUpperCase().replace(/\s/g, '');
            if (!cleanCode || cleanCode.length < 3) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Kode Terlalu Pendek',
                    text: 'Kode akses minimal terdiri dari 3 karakter.'
                });
                return;
            }

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            try {
                if (btnGenerateCode) {
                    btnGenerateCode.disabled = true;
                    btnGenerateCode.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Acak...';
                }

                const resp = await fetch('/dashboard/presensi/pengaturan/kode-akses', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ kode_akses: cleanCode })
                });

                const data = await resp.json();

                if (!resp.ok || data.status !== 'success') {
                    throw new Error(data.message || 'Gagal menyimpan kode akses.');
                }

                // Update DOM state
                if (inputKodeAkses) {
                    inputKodeAkses.value = data.kode_akses;
                }

                // Salin otomatis ke clipboard tanpa double alert (silent = true)
                copyKodeAksesToClipboard(data.kode_akses, true);

                // Cukup 1 alert/toast tunggal
                Swal.fire({
                    icon: 'success',
                    title: 'Kode Baru Disimpan!',
                    text: `Kode "${data.kode_akses}" berhasil diacak, disimpan, dan disalin ke clipboard.`,
                    timer: 2500,
                    showConfirmButton: false,
                    toast: true,
                    position: 'top-end'
                });
            } catch (err) {
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal Menyimpan Kode',
                    text: err.message || 'Terjadi kesalahan sistem saat memperbarui kode akses.'
                });
            } finally {
                if (btnGenerateCode) {
                    btnGenerateCode.disabled = false;
                    btnGenerateCode.innerHTML = '<i class="fas fa-dice"></i> Acak';
                }
            }
        }

        if (inputKodeAkses) {
            inputKodeAkses.addEventListener('input', function () {
                this.value = this.value.toUpperCase().replace(/\s/g, '');
            });
        }

        if (btnToggleShowCode && inputKodeAkses) {
            btnToggleShowCode.addEventListener('click', function () {
                const isPass = inputKodeAkses.type === 'password';
                inputKodeAkses.type = isPass ? 'text' : 'password';
                this.innerHTML = isPass ? '<i class="fas fa-eye-slash"></i>' : '<i class="fas fa-eye"></i>';
            });
        }

        if (btnCopyCode && inputKodeAkses) {
            btnCopyCode.addEventListener('click', function () {
                const val = inputKodeAkses.value.trim().toUpperCase().replace(/\s/g, '');
                if (!val) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Kode Kosong',
                        text: 'Silakan ketik atau acak kode akses terlebih dahulu.'
                    });
                    return;
                }
                copyKodeAksesToClipboard(val, false);
            });
        }

        if (btnGenerateCode && inputKodeAkses) {
            btnGenerateCode.addEventListener('click', function () {
                const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
                let res = '';
                for (let i = 0; i < 6; i++) {
                    res += chars.charAt(Math.floor(Math.random() * chars.length));
                }
                inputKodeAkses.value = res;
                inputKodeAkses.type = 'text';
                if (btnToggleShowCode) btnToggleShowCode.innerHTML = '<i class="fas fa-eye-slash"></i>';

                // Otomatis tersimpan ke database & disalin ke clipboard dengan 1 alert tunggal
                saveKodeAksesAjax(res);
            });
        }

        const btnCopyKiosk = document.getElementById('btnCopyKioskUrl');
        if (btnCopyKiosk) {
            btnCopyKiosk.addEventListener('click', function () {
                const url = this.getAttribute('data-url');
                if (url) {
                    const notify = () => {
                        Swal.fire({
                            icon: 'success',
                            title: 'Tautan Kiosk Disalin!',
                            text: 'URL terminal scanner berhasil disalin ke clipboard.',
                            timer: 2000,
                            showConfirmButton: false,
                            toast: true,
                            position: 'top-end'
                        });
                    };
                    if (navigator.clipboard && window.isSecureContext) {
                        navigator.clipboard.writeText(url).then(notify).catch(() => fallbackClipboard(url, notify));
                    } else {
                        fallbackClipboard(url, notify);
                    }
                }
            });
        }

        formPengaturan.addEventListener('submit', async function (e) {
            e.preventDefault();

            const formData = new FormData(this);
            const dataObj = {};
            const hariAktif = [];
            const jurusanAktif = [];

            formData.forEach((val, key) => {
                if (key === 'hari_aktif[]') {
                    hariAktif.push(val);
                } else if (key === 'jurusan_aktif[]') {
                    jurusanAktif.push(val);
                } else {
                    dataObj[key] = val;
                }
            });
            dataObj['hari_aktif'] = hariAktif;
            dataObj['jurusan_aktif'] = jurusanAktif;
            dataObj['kode_akses'] = (inputKodeAkses ? inputKodeAkses.value : '').trim().toUpperCase().replace(/\s/g, '');
            dataObj['require_camera'] = document.getElementById('settingRequireCamera')?.checked ? 1 : 0;
            dataObj['allow_rfid'] = document.getElementById('settingAllowRfid')?.checked ? 1 : 0;
            dataObj['allow_qr'] = document.getElementById('settingAllowQr')?.checked ? 1 : 0;
            dataObj['require_location'] = document.getElementById('settingRequireLocation')?.checked ? 1 : 0;
            dataObj['radius_meter'] = parseInt(document.getElementById('inputRadiusMeter')?.value || '100', 10);
            dataObj['latitude'] = document.getElementById('inputLatitude')?.value || null;
            dataObj['longitude'] = document.getElementById('inputLongitude')?.value || null;

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
                        <p><strong>Nama Peserta Didik:</strong> ${nama}</p>
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

    /* ==========================================================================
       SORT HEADER — Klik th.sortable-th untuk sort kolom server-side
       ========================================================================== */
    document.querySelectorAll('.sortable-th').forEach(function (th) {
        th.style.cursor = 'pointer';
        th.addEventListener('click', function () {
            const sortField = this.getAttribute('data-sort');
            if (!sortField) return;

            const url = new URL(window.location.href);
            const currentSort = url.searchParams.get('sort') || 'jam_masuk';
            const currentDir = url.searchParams.get('sort_dir') || 'desc';

            let newDir = 'asc';
            if (currentSort === sortField && currentDir === 'asc') {
                newDir = 'desc';
            }

            url.searchParams.set('sort', sortField);
            url.searchParams.set('sort_dir', newDir);
            url.searchParams.delete('page'); // reset ke halaman pertama
            // Pastikan tab log tetap aktif
            if (!url.searchParams.has('tab')) {
                url.searchParams.set('tab', 'log');
            }
            window.location.href = url.toString();
        });
    });
});
