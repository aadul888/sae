/**
 * JavaScript Modul Jadwal Praktik & Penggunaan Lokasi (Laboratorium / Bengkel / Lahan / Lapangan)
 * Standar Baku SAE: Vanilla JS, Modal Handling (z-index: 99999), SweetAlert2
 */

document.addEventListener('DOMContentLoaded', function () {
    if (!window.location.pathname.includes('/dashboard/laboran/jadwal')) {
        return;
    }

    const BASE_URL = '/dashboard/laboran/jadwal';
    const GURU_MAPEL_URL = BASE_URL + '/guru-mapel';

    // -------------------------------------------------------------------------
    // 1. Referensi Elemen Banner & Navigasi
    // -------------------------------------------------------------------------
    const btnOpenModalJadwal = document.getElementById('btnOpenModalJadwal');
    const btnBukaModalPilihMode = document.getElementById('btnBukaModalPilihMode');
    const btnCloseLockModal = document.getElementById('btnCloseLockModal');
    const modalLockPilihModeJadwal = document.getElementById('modalLockPilihModeJadwal');

    const btnDropdownCetakJadwal = document.getElementById('btnDropdownCetakJadwal');
    const dropdownMenuCetakJadwal = document.getElementById('dropdownMenuCetakJadwal');

    // -------------------------------------------------------------------------
    // 2. Referensi Modal Mingguan & Semester Terpisah
    // -------------------------------------------------------------------------
    const modalJadwalMingguan = document.getElementById('modalJadwalMingguan');
    const formJadwalMingguan = document.getElementById('formJadwalMingguan');
    const modalJadwalMingguanTitle = document.getElementById('modalJadwalMingguanTitle');
    const jadwalMethodMingguan = document.getElementById('jadwalMethodMingguan');
    const selectLokasiMingguan = document.getElementById('selectLokasiMingguan');
    const selectRombelMingguan = document.getElementById('selectRombelMingguan');
    const selectGuruMapelMingguan = document.getElementById('selectGuruMapelMingguan');
    const hintMingguan = document.getElementById('guruMapelHintMingguan');
    const selectJamKeMulaiMingguan = document.getElementById('selectJamKeMulaiMingguan');
    const selectJamKeSelesaiMingguan = document.getElementById('selectJamKeSelesaiMingguan');
    const inputTanggalMingguan = document.getElementById('inputTanggalMingguan');
    const selectPemberlakuanMingguM = document.getElementById('selectPemberlakuanMingguM');
    const selectPemberlakuanMingguAkhirM = document.getElementById('selectPemberlakuanMingguAkhirM');

    const modalJadwalSemester = document.getElementById('modalJadwalSemester');
    const formJadwalSemester = document.getElementById('formJadwalSemester');
    const modalJadwalSemesterTitle = document.getElementById('modalJadwalSemesterTitle');
    const jadwalMethodSemester = document.getElementById('jadwalMethodSemester');
    const selectLokasiSemester = document.getElementById('selectLokasiSemester');
    const selectRombelSemester = document.getElementById('selectRombelSemester');
    const selectGuruMapelSemester = document.getElementById('selectGuruMapelSemester');
    const hintSemester = document.getElementById('guruMapelHintSemester');
    const selectHariSemester = document.getElementById('selectHariSemester');
    const selectJamKeMulaiSemester = document.getElementById('selectJamKeMulaiSemester');
    const selectJamKeSelesaiSemester = document.getElementById('selectJamKeSelesaiSemester');
    const selectPemberlakuanSemesterS = document.getElementById('selectPemberlakuanSemesterS');
    const selectPemberlakuanTaS = document.getElementById('selectPemberlakuanTaS');

    // -------------------------------------------------------------------------
    // 3. Util: Alert & CSRF
    // -------------------------------------------------------------------------
    function notifySuccess(message) {
        return window.SAELaboran.notifySuccess(message, false);
    }

    function notifyError(title, message) {
        return window.SAELaboran.notifyError(title, message);
    }

    function extractErrorMessage(data, fallback) {
        return window.SAELaboran.extractError(data, fallback);
    }

    function csrfToken() {
        return window.SAELaboran.csrfToken();
    }

    // -------------------------------------------------------------------------
    // 4. Dropdown Cetak Jadwal
    // -------------------------------------------------------------------------
    if (btnDropdownCetakJadwal && dropdownMenuCetakJadwal) {
        btnDropdownCetakJadwal.addEventListener('click', function (e) {
            e.stopPropagation();
            const isVisible = dropdownMenuCetakJadwal.style.display === 'block';
            dropdownMenuCetakJadwal.style.display = isVisible ? 'none' : 'block';
        });
        document.addEventListener('click', function (e) {
            if (!btnDropdownCetakJadwal.contains(e.target)) {
                dropdownMenuCetakJadwal.style.display = 'none';
            }
        });
    }

    // -------------------------------------------------------------------------
    // 5. Modal Penguncian Pilihan Mode Jadwal
    // -------------------------------------------------------------------------
    if (btnBukaModalPilihMode && modalLockPilihModeJadwal) {
        btnBukaModalPilihMode.addEventListener('click', function () {
            modalLockPilihModeJadwal.style.display = 'flex';
        });
    }

    if (btnCloseLockModal && modalLockPilihModeJadwal) {
        btnCloseLockModal.addEventListener('click', function () {
            modalLockPilihModeJadwal.style.display = 'none';
        });
    }

    // Penutupan Modal Umum via .btn-close-modal-jadwal / .btn-cancel-modal-jadwal
    document.addEventListener('click', function (e) {
        const btnClose = e.target.closest('.btn-close-modal-jadwal, .btn-cancel-modal-jadwal');
        if (btnClose) {
            const targetId = btnClose.dataset.target;
            const targetEl = document.getElementById(targetId);
            if (targetEl) targetEl.style.display = 'none';
        }
    });

    // -------------------------------------------------------------------------
    // 6. Slot Jam Pelajaran (JP) dari Pengaturan KBM
    // -------------------------------------------------------------------------
    let slotPerHariCache = null;

    function fillJamKeSlots(selectMulai, selectSelesai, slots, preselectMulai, preselectSelesai) {
        if (!selectMulai || !selectSelesai) return;

        selectMulai.innerHTML = '';
        selectSelesai.innerHTML = '';

        const keys = Object.keys(slots || {});
        if (keys.length === 0) {
            for (let k = 1; k <= 10; k++) {
                selectMulai.appendChild(new Option('Jam ke-' + k, k));
                selectSelesai.appendChild(new Option('Jam ke-' + k, k));
            }
            selectMulai.value = preselectMulai || '1';
            selectSelesai.value = preselectSelesai || '1';
            return;
        }

        keys.forEach(function (ke) {
            const s = slots[ke];
            let label = 'Jam ke-' + s.ke + ' (' + s.mulai + ' - ' + s.selesai + ')';
            if (s.is_break) label += ' [Istirahat]';
            selectMulai.appendChild(new Option(label, s.ke));
            selectSelesai.appendChild(new Option(label, s.ke));
        });

        if (preselectMulai) selectMulai.value = String(preselectMulai);
        if (preselectSelesai) selectSelesai.value = String(preselectSelesai);
    }

    function loadSlotsForHari(hari, selectMulai, selectSelesai, preselectMulai, preselectSelesai) {
        hari = hari || 'Senin';
        if (slotPerHariCache && slotPerHariCache[hari]) {
            fillJamKeSlots(selectMulai, selectSelesai, slotPerHariCache[hari], preselectMulai, preselectSelesai);
            return;
        }

        fetch(BASE_URL + '/slots', {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            credentials: 'same-origin'
        })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                slotPerHariCache = (data && data.per_hari) || {};
                const slots = slotPerHariCache[hari] || (data && data.slots) || {};
                fillJamKeSlots(selectMulai, selectSelesai, slots, preselectMulai, preselectSelesai);
            })
            .catch(function () {
                fillJamKeSlots(selectMulai, selectSelesai, {}, preselectMulai, preselectSelesai);
            });
    }

    // -------------------------------------------------------------------------
    // 7. Dropdown Guru & Mata Pelajaran dari Kelas (Dapodik Pembelajaran)
    // -------------------------------------------------------------------------
    function setGuruMapelOptions(selectEl, hintEl, items) {
        if (!selectEl) return;
        selectEl.innerHTML = '';
        selectEl._items = items || [];

        if (!items || items.length === 0) {
            selectEl.innerHTML = '<option value="">-- Tidak ada mata pelajaran pada kelas ini --</option>';
            selectEl.disabled = true;
            if (hintEl) {
                hintEl.innerHTML = '<i class="fas fa-triangle-exclamation me-1" style="color: var(--warning, #f59e0b);"></i> Kelas ini belum memiliki data pembelajaran Dapodik.';
            }
            return;
        }

        const placeholder = document.createElement('option');
        placeholder.value = '';
        placeholder.textContent = '-- Pilih Guru & Mata Pelajaran --';
        selectEl.appendChild(placeholder);

        items.forEach(function (item) {
            const opt = document.createElement('option');
            opt.value = item.pembelajaran_id;
            opt.dataset.mapelId = item.mata_pelajaran_id || '';
            opt.dataset.mapel = item.mata_pelajaran || '';
            opt.dataset.ptkId = item.ptk_id || '';
            opt.dataset.guru = item.nama_guru || '';
            let label = item.mata_pelajaran;
            if (item.nama_guru) label += ' — ' + item.nama_guru;
            opt.textContent = label;
            selectEl.appendChild(opt);
        });

        selectEl.disabled = false;
        if (hintEl) {
            hintEl.innerHTML = '<i class="fas fa-circle-check me-1" style="color: var(--success, #10b981);"></i> ' + items.length + ' mata pelajaran dimuat otomatis dari kelas.';
        }
    }

    function loadGuruMapel(rombelId, selectEl, hintEl, preselectPembelajaranId) {
        if (!selectEl) return;

        if (!rombelId) {
            selectEl.innerHTML = '<option value="">-- Pilih Kelas Terlebih Dahulu --</option>';
            selectEl.disabled = true;
            if (hintEl) hintEl.textContent = 'Pilih kelas untuk memuat daftar guru & mata pelajaran.';
            return;
        }

        fetch(GURU_MAPEL_URL + '?rombel_id=' + encodeURIComponent(rombelId), {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            credentials: 'same-origin'
        })
            .then(function (res) {
                if (!res.ok) throw new Error('Gagal memuat daftar guru & mapel.');
                return res.json();
            })
            .then(function (data) {
                const items = (data && data.guru_mapel) || [];
                setGuruMapelOptions(selectEl, hintEl, items);
                if (preselectPembelajaranId) {
                    selectEl.value = preselectPembelajaranId;
                }
            })
            .catch(function (err) {
                setGuruMapelOptions(selectEl, hintEl, []);
                if (hintEl) {
                    hintEl.innerHTML = '<i class="fas fa-triangle-exclamation me-1" style="color: var(--danger, #ef4444);"></i> ' + (err.message || 'Gagal memuat daftar.');
                }
            });
    }

    if (selectRombelMingguan) {
        selectRombelMingguan.addEventListener('change', function () {
            loadGuruMapel(this.value, selectGuruMapelMingguan, hintMingguan, null);
        });
    }

    if (selectRombelSemester) {
        selectRombelSemester.addEventListener('change', function () {
            loadGuruMapel(this.value, selectGuruMapelSemester, hintSemester, null);
        });
    }

    // -------------------------------------------------------------------------
    // 8. Buka / Tutup Modal Tambah & Ubah Jadwal
    // -------------------------------------------------------------------------
    function getActiveMode() {
        return (btnOpenModalJadwal && btnOpenModalJadwal.dataset.activeMode) || 'mingguan';
    }

    function openModalCreate(mode) {
        mode = mode || getActiveMode();

        if (mode === 'semester') {
            if (!modalJadwalSemester || !formJadwalSemester) return;
            formJadwalSemester.reset();
            formJadwalSemester.action = BASE_URL;
            if (jadwalMethodSemester) jadwalMethodSemester.value = 'POST';
            if (modalJadwalSemesterTitle) modalJadwalSemesterTitle.textContent = 'Jadwalkan Praktik (Semester)';
            setGuruMapelOptions(selectGuruMapelSemester, hintSemester, []);
            loadSlotsForHari(selectHariSemester ? selectHariSemester.value : 'Senin', selectJamKeMulaiSemester, selectJamKeSelesaiSemester);
            modalJadwalSemester.style.display = 'flex';
        } else {
            if (!modalJadwalMingguan || !formJadwalMingguan) return;
            formJadwalMingguan.reset();
            formJadwalMingguan.action = BASE_URL;
            if (jadwalMethodMingguan) jadwalMethodMingguan.value = 'POST';
            if (modalJadwalMingguanTitle) modalJadwalMingguanTitle.textContent = 'Jadwalkan Praktik (Mingguan)';
            setGuruMapelOptions(selectGuruMapelMingguan, hintMingguan, []);
            if (inputTanggalMingguan) {
                const now = new Date();
                const tzOffset = now.getTimezoneOffset() * 60000;
                inputTanggalMingguan.value = new Date(now.getTime() - tzOffset).toISOString().split('T')[0];
            }
            if (selectPemberlakuanMingguM) selectPemberlakuanMingguM.value = '1';
            if (selectPemberlakuanMingguAkhirM) selectPemberlakuanMingguAkhirM.value = '1';
            loadSlotsForHari('Senin', selectJamKeMulaiMingguan, selectJamKeSelesaiMingguan);
            modalJadwalMingguan.style.display = 'flex';
        }
    }

    if (btnOpenModalJadwal) {
        btnOpenModalJadwal.addEventListener('click', function () {
            openModalCreate();
        });
    }

    // Quick Slot pada Matriks Grid
    document.addEventListener('click', function (e) {
        const btnSlot = e.target.closest('.btn-quick-slot');
        if (!btnSlot) return;

        const mode = getActiveMode();
        openModalCreate(mode);

        const slotHari = btnSlot.dataset.hari || 'Senin';
        const jamKe = btnSlot.dataset.jamKe || '1';

        if (mode === 'semester') {
            if (selectHariSemester) selectHariSemester.value = slotHari;
            loadSlotsForHari(slotHari, selectJamKeMulaiSemester, selectJamKeSelesaiSemester, jamKe, jamKe);
        } else {
            loadSlotsForHari(slotHari, selectJamKeMulaiMingguan, selectJamKeSelesaiMingguan, jamKe, jamKe);
        }
    });

    // Delegasi Tombol Ubah Jadwal di Tabel / Matriks
    document.addEventListener('click', function (e) {
        const btnEdit = e.target.closest('.btn-edit-jadwal');
        if (!btnEdit) return;

        const d = btnEdit.dataset;
        const mode = d.pemberlakuan === 'semester' ? 'semester' : 'mingguan';

        if (mode === 'semester') {
            if (!modalJadwalSemester || !formJadwalSemester) return;
            formJadwalSemester.reset();
            formJadwalSemester.action = BASE_URL + '/' + d.id;
            if (jadwalMethodSemester) jadwalMethodSemester.value = 'PUT';
            if (modalJadwalSemesterTitle) modalJadwalSemesterTitle.textContent = 'Ubah Jadwal Praktik (Semester)';
            if (selectLokasiSemester) selectLokasiSemester.value = d.lokasi || '';
            if (selectRombelSemester) selectRombelSemester.value = d.rombelId || '';
            loadGuruMapel(d.rombelId || '', selectGuruMapelSemester, hintSemester, d.pembelajaranId || null);
            loadSlotsForHari('Senin', selectJamKeMulaiSemester, selectJamKeSelesaiSemester);
            modalJadwalSemester.style.display = 'flex';
        } else {
            if (!modalJadwalMingguan || !formJadwalMingguan) return;
            formJadwalMingguan.reset();
            formJadwalMingguan.action = BASE_URL + '/' + d.id;
            if (jadwalMethodMingguan) jadwalMethodMingguan.value = 'PUT';
            if (modalJadwalMingguanTitle) modalJadwalMingguanTitle.textContent = 'Ubah Jadwal Praktik (Mingguan)';
            if (selectLokasiMingguan) selectLokasiMingguan.value = d.lokasi || '';
            if (selectRombelMingguan) selectRombelMingguan.value = d.rombelId || '';
            if (inputTanggalMingguan) inputTanggalMingguan.value = d.tanggal || '';
            loadGuruMapel(d.rombelId || '', selectGuruMapelMingguan, hintMingguan, d.pembelajaranId || null);
            loadSlotsForHari('Senin', selectJamKeMulaiMingguan, selectJamKeSelesaiMingguan);
            modalJadwalMingguan.style.display = 'flex';
        }
    });

    // -------------------------------------------------------------------------
    // 9. Submit Form Jadwal (Mingguan & Semester)
    // -------------------------------------------------------------------------
    function handleFormSubmit(form, selectLokasi, selectRombel, selectGuruMapel, selectJamMulai, selectJamSelesai, modalEl) {
        if (!form) return;

        form.addEventListener('submit', function (e) {
            e.preventDefault();

            if (selectLokasi && !selectLokasi.value) {
                notifyError('Data Belum Lengkap', 'Pilih lokasi / tempat praktik terlebih dahulu.');
                return;
            }

            if (selectRombel && !selectRombel.value) {
                notifyError('Data Belum Lengkap', 'Pilih kelas terlebih dahulu.');
                return;
            }

            if (selectGuruMapel && !selectGuruMapel.value) {
                notifyError('Data Belum Lengkap', 'Pilih guru pengampu & mata pelajaran.');
                return;
            }

            if (selectJamMulai && selectJamSelesai && Number(selectJamSelesai.value) < Number(selectJamMulai.value)) {
                notifyError('Data Belum Lengkap', 'Jam pelajaran selesai tidak boleh lebih kecil dari jam mulai.');
                return;
            }

            const submitBtn = form.querySelector('button[type="submit"]');
            const originalHtml = submitBtn ? submitBtn.innerHTML : '';
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Menyimpan...';
            }

            fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                credentials: 'same-origin',
                body: new FormData(form)
            })
                .then(async function (res) {
                    const data = await res.json().catch(function () { return {}; });
                    if (!res.ok) {
                        throw new Error(extractErrorMessage(data, 'Terjadi kesalahan saat menyimpan jadwal.'));
                    }
                    return data;
                })
                .then(function (data) {
                    if (modalEl) modalEl.style.display = 'none';
                    notifySuccess(data.message || 'Jadwal praktik berhasil disimpan.')
                        .then(function () { window.location.reload(); });
                })
                .catch(function (err) {
                    notifyError('Gagal Menyimpan', err.message || 'Terjadi kesalahan pada sistem.');
                })
                .finally(function () {
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalHtml;
                    }
                });
        });
    }

    handleFormSubmit(
        formJadwalMingguan,
        selectLokasiMingguan,
        selectRombelMingguan,
        selectGuruMapelMingguan,
        selectJamKeMulaiMingguan,
        selectJamKeSelesaiMingguan,
        modalJadwalMingguan
    );

    handleFormSubmit(
        formJadwalSemester,
        selectLokasiSemester,
        selectRombelSemester,
        selectGuruMapelSemester,
        selectJamKeMulaiSemester,
        selectJamKeSelesaiSemester,
        modalJadwalSemester
    );

    // -------------------------------------------------------------------------
    // 10. Modal Update Status & Laporan Kerusakan
    // -------------------------------------------------------------------------
    const modalStatus = document.getElementById('modalStatus');
    const btnCloseModalStatus = document.getElementById('btnCloseModalStatus');
    const btnCancelModalStatus = document.getElementById('btnCancelModalStatus');
    const formStatus = document.getElementById('formStatus');
    const statusDesc = document.getElementById('statusDesc');
    const selectStatusLab = document.getElementById('selectStatusLab');
    const inputLaporanKerusakan = document.getElementById('inputLaporanKerusakan');

    function closeModalStatus() {
        if (!modalStatus) return;
        modalStatus.style.display = 'none';
    }

    window.closeModalStatus = closeModalStatus;

    if (btnCloseModalStatus) btnCloseModalStatus.addEventListener('click', closeModalStatus);
    if (btnCancelModalStatus) btnCancelModalStatus.addEventListener('click', closeModalStatus);
    if (modalStatus) {
        modalStatus.addEventListener('click', function (e) {
            if (e.target === modalStatus) closeModalStatus();
        });
    }

    document.addEventListener('click', function (e) {
        const btnStatus = e.target.closest('.btn-status-lab');
        if (btnStatus && modalStatus && formStatus) {
            formStatus.action = BASE_URL + '/' + btnStatus.dataset.id;

            if (statusDesc) {
                statusDesc.textContent = 'Memperbarui status sesi: ' + (btnStatus.dataset.mapel || '-');
            }
            if (selectStatusLab) selectStatusLab.value = btnStatus.dataset.status || 'dijadwalkan';
            if (inputLaporanKerusakan) inputLaporanKerusakan.value = btnStatus.dataset.laporan || '';

            modalStatus.style.display = 'flex';
        }
    });

    if (formStatus) {
        formStatus.addEventListener('submit', function (e) {
            e.preventDefault();

            const submitBtn = formStatus.querySelector('button[type="submit"]');
            const originalHtml = submitBtn ? submitBtn.innerHTML : '';
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Menyimpan...';
            }

            fetch(formStatus.action, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                credentials: 'same-origin',
                body: new FormData(formStatus)
            })
                .then(async function (res) {
                    const data = await res.json().catch(function () { return {}; });
                    if (!res.ok) {
                        throw new Error(extractErrorMessage(data, 'Terjadi kesalahan saat memperbarui status.'));
                    }
                    return data;
                })
                .then(function (data) {
                    closeModalStatus();
                    notifySuccess(data.message || 'Status penggunaan lokasi praktik berhasil diperbarui.')
                        .then(function () { window.location.reload(); });
                })
                .catch(function (err) {
                    notifyError('Gagal Memperbarui', err.message || 'Terjadi kesalahan pada sistem.');
                })
                .finally(function () {
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalHtml;
                    }
                });
        });
    }

    // -------------------------------------------------------------------------
    // 11. Hapus Jadwal
    // -------------------------------------------------------------------------
    document.addEventListener('click', function (e) {
        const btnDelete = e.target.closest('.btn-delete-jadwal');
        if (!btnDelete) return;

        const id = btnDelete.dataset.id;
        const mapel = btnDelete.dataset.mapel || 'sesi ini';

        const doDelete = function () {
            fetch(BASE_URL + '/' + id, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                credentials: 'same-origin'
            })
                .then(async function (res) {
                    const data = await res.json().catch(function () { return {}; });
                    if (!res.ok) throw new Error(data.message || 'Gagal menghapus jadwal.');
                    return data;
                })
                .then(function (data) {
                    notifySuccess(data.message || 'Jadwal berhasil dihapus.')
                        .then(function () { window.location.reload(); });
                })
                .catch(function (err) {
                    notifyError('Gagal Menghapus', err.message || 'Terjadi kesalahan sistem.');
                });
        };

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Hapus Jadwal Praktik?',
                text: 'Apakah Anda yakin ingin menghapus jadwal untuk "' + mapel + '"?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then(function (result) {
                if (result.isConfirmed) doDelete();
            });
        } else if (confirm('Hapus jadwal untuk "' + mapel + '"?')) {
            doDelete();
        }
    });

    // -------------------------------------------------------------------------
    // 12. Pemberlakuan Jadwal (Aktifkan Mode Mingguan / Semester)
    // -------------------------------------------------------------------------
    document.addEventListener('click', function (e) {
        const btnToggle = e.target.closest('.btn-toggle-pemberlakuan-lab');
        if (!btnToggle) return;

        const mode = btnToggle.dataset.mode || 'mingguan';
        const status = btnToggle.dataset.status || 'aktif';
        const url = btnToggle.dataset.url || BASE_URL + '/pemberlakuan';

        const doToggle = function () {
            btnToggle.disabled = true;
            const original = btnToggle.innerHTML;
            btnToggle.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Menyimpan...';

            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                credentials: 'same-origin',
                body: JSON.stringify({ mode: mode, status: status })
            })
                .then(async function (res) {
                    const data = await res.json().catch(function () { return {}; });
                    if (!res.ok) throw new Error(data.message || 'Gagal mengubah pemberlakuan jadwal.');
                    return data;
                })
                .then(function (data) {
                    notifySuccess(data.message || 'Pemberlakuan jadwal berhasil diperbarui.')
                        .then(function () { window.location.reload(); });
                })
                .catch(function (err) {
                    btnToggle.disabled = false;
                    btnToggle.innerHTML = original;
                    notifyError('Gagal', err.message || 'Terjadi kesalahan pada sistem.');
                });
        };

        const modeLabel = mode === 'semester' ? 'Semester' : 'Mingguan';
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: status === 'aktif' ? 'Aktifkan Pemberlakuan ' + modeLabel + '?' : 'Matikan Pemberlakuan?',
                html: status === 'aktif'
                    ? 'Mode <strong>' + modeLabel + '</strong> akan menjadi acuan penentuan jadwal praktik yang diberlakukan.'
                    : 'Pemberlakuan jadwal akan kembali ke status <strong>draft</strong>.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#10b981',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Ya, Lanjutkan',
                cancelButtonText: 'Batal'
            }).then(function (result) {
                if (result.isConfirmed) doToggle();
            });
        } else {
            doToggle();
        }
    });
});
