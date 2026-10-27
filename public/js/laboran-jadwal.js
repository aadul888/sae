/**
 * JavaScript Modul Jadwal Praktik & Penggunaan Lokasi (Laboratorium / Bengkel / Lahan / Lapangan)
 * Standar Baku SAE: Vanilla JS, Modal Handling (z-index: 99999), SweetAlert2
 *
 * Fitur:
 *  1. Modal tambah & ubah jadwal (satu form dipakai ulang).
 *  2. Mata pelajaran otomatis dari kelas (fetch data pembelajaran Dapodik).
 *  3. Alokasi waktu via jam pelajaran (Jam ke-N).
 *  4. Update status sesi + laporan kerusakan.
 *  5. Hapus jadwal dengan konfirmasi.
 */

document.addEventListener('DOMContentLoaded', function () {
    if (!window.location.pathname.includes('/dashboard/laboran/jadwal')) {
        return;
    }

    // -------------------------------------------------------------------------
    // -------------------------------------------------------------------------
    // 2. Referensi Elemen Modal (Form Simpel)
    // -------------------------------------------------------------------------
    const modalJadwal = document.getElementById('modalJadwal');
    const btnOpenModalJadwal = document.getElementById('btnOpenModalJadwal');
    const btnCloseModalJadwal = document.getElementById('btnCloseModalJadwal');
    const btnCancelModalJadwal = document.getElementById('btnCancelModalJadwal');
    const modalJadwalTitle = document.getElementById('modalJadwalTitle');
    const formJadwal = document.getElementById('formJadwal');
    const jadwalMethod = document.getElementById('jadwalMethod');
    const selectLokasi = document.getElementById('selectLokasi');
    const selectRombel = document.getElementById('selectRombel');
    const selectGuruMapel = document.getElementById('selectGuruMapel');
    const guruMapelHint = document.getElementById('guruMapelHint');
    const selectJamKeMulai = document.getElementById('selectJamKeMulai');
    const selectJamKeSelesai = document.getElementById('selectJamKeSelesai');
    const alokasiHint = document.getElementById('alokasiHint');
    const inputPembelajaranId = document.getElementById('inputPembelajaranId');
    const inputMapelId = document.getElementById('inputMapelId');
    const inputJenisLokasi = document.getElementById('inputJenisLokasi');
    const lokasiTerpilihInfo = document.getElementById('lokasiTerpilihInfo');
    const lokasiTerpilihNama = document.getElementById('lokasiTerpilihNama');
    const inputPemberlakuan = document.getElementById('inputPemberlakuan');
    const wrapOpsiMingguan = document.getElementById('wrapOpsiMingguan');
    const wrapOpsiSemester = document.getElementById('wrapOpsiSemester');
    const inputTanggal = document.getElementById('inputTanggal');
    const selectPemberlakuanMinggu = document.getElementById('selectPemberlakuanMinggu');
    const selectPemberlakuanMingguAkhir = document.getElementById('selectPemberlakuanMingguAkhir');

    const BASE_URL = '/dashboard/laboran/jadwal';
    const GURU_MAPEL_URL = BASE_URL + '/guru-mapel';

    let guruMapelCache = null;
    let isEditMode = false;

    // -------------------------------------------------------------------------
    // 3. Util: Alert â€” delegasi ke helper bersama (laboran-common.js)
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
    // 4. Dropdown Guru & Mata Pelajaran (mengikuti kelas terpilih)
    // -------------------------------------------------------------------------
    function setGuruMapelOptions(items) {
        if (!selectGuruMapel) return;

        selectGuruMapel.innerHTML = '';

        if (!items || items.length === 0) {
            const opt = document.createElement('option');
            opt.value = '';
            opt.textContent = '-- Belum ada data pembelajaran pada kelas ini --';
            selectGuruMapel.appendChild(opt);
            selectGuruMapel.disabled = true;
            if (guruMapelHint) {
                guruMapelHint.innerHTML = '<i class="fas fa-triangle-exclamation me-1" style="color: var(--warning, #f59e0b);"></i>' +
                    'Kelas ini belum memiliki data pembelajaran. Sinkronkan data Dapodik.';
            }
            return;
        }

        const placeholder = document.createElement('option');
        placeholder.value = '';
        placeholder.textContent = '-- Pilih Guru & Mata Pelajaran --';
        selectGuruMapel.appendChild(placeholder);

        items.forEach(function (item, idx) {
            const opt = document.createElement('option');
            opt.value = String(idx);
            let label = item.nama_mata_pelajaran;
            if (item.nama_guru) label += ' â€” ' + item.nama_guru;
            opt.textContent = label;
            selectGuruMapel.appendChild(opt);
        });

        selectGuruMapel.disabled = false;
        if (guruMapelHint) {
            guruMapelHint.innerHTML = '<i class="fas fa-circle-check me-1" style="color: var(--success, #10b981);"></i>' +
                items.length + ' pilihan guru & mata pelajaran tersedia.';
        }
    }

    function loadGuruMapel(rombelId, preselectPembelajaranId) {
        if (!selectGuruMapel) return;

        if (!rombelId) {
            selectGuruMapel.innerHTML = '<option value="">-- Pilih Kelas Terlebih Dahulu --</option>';
            selectGuruMapel.disabled = true;
            if (guruMapelHint) guruMapelHint.textContent = 'Pilih kelas untuk memuat daftar guru & mata pelajaran.';
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
                applyGuruMapel(items, preselectPembelajaranId);
            })
            .catch(function (err) {
                setGuruMapelOptions([]);
                if (guruMapelHint) {
                    guruMapelHint.innerHTML = '<i class="fas fa-triangle-exclamation me-1" style="color: var(--danger, #ef4444);"></i>' +
                        (err.message || 'Gagal memuat daftar.');
                }
            });
    }

    function applyGuruMapel(items, preselectPembelajaranId) {
        setGuruMapelOptions(items);
        if (!selectGuruMapel) return;

        if (preselectPembelajaranId) {
            const idx = items.findIndex(function (it) { return String(it.pembelajaran_id) === String(preselectPembelajaranId); });
            if (idx !== -1) {
                selectGuruMapel.value = String(idx);
                onGuruMapelSelected();
                return;
            }
        }

        onGuruMapelSelected();
    }

    function onGuruMapelSelected() {
        if (!selectGuruMapel) return;

        const idx = selectGuruMapel.value;
        const items = selectGuruMapel._items || [];
        const item = idx !== '' ? items[Number(idx)] : null;

        if (!item) {
            if (inputPembelajaranId) inputPembelajaranId.value = '';
            if (inputMapelId) inputMapelId.value = '';
            return;
        }

        if (inputPembelajaranId) inputPembelajaranId.value = item.pembelajaran_id || '';
        if (inputMapelId) inputMapelId.value = item.mata_pelajaran_id || '';
    }

    if (selectGuruMapel) {
        selectGuruMapel.addEventListener('change', onGuruMapelSelected);
    }

    // -------------------------------------------------------------------------
    // 5. Slot Jam Pelajaran (Alokasi Waktu JP)
    // -------------------------------------------------------------------------
    function fillJamKeSlots(slots, preselectMulai, preselectSelesai) {
        if (!selectJamKeMulai || !selectJamKeSelesai) return;

        selectJamKeMulai.innerHTML = '';
        selectJamKeSelesai.innerHTML = '';

        if (!slots || slots.length === 0) {
            for (let k = 1; k <= 10; k++) {
                selectJamKeMulai.appendChild(new Option('Jam ke-' + k, k));
                selectJamKeSelesai.appendChild(new Option('Jam ke-' + k, k));
            }
            if (alokasiHint) {
                alokasiHint.innerHTML = '<i class="fas fa-triangle-exclamation me-1" style="color: var(--warning, #f59e0b);"></i> Slot KBM tidak tersedia â€” durasi default 45 menit per JP dipakai.';
            }
            selectJamKeMulai.value = preselectMulai || '1';
            selectJamKeSelesai.value = preselectSelesai || '1';
            return;
        }

        const keys = Object.keys(slots);
        keys.forEach(function (ke) {
            const s = slots[ke];
            const label = 'Jam ke-' + s.ke + ' (' + s.mulai + ' - ' + s.selesai + ')' + (s.is_break ? ' Istirahat' : '');
            selectJamKeMulai.appendChild(new Option(label, s.ke));
            selectJamKeSelesai.appendChild(new Option(label, s.ke));
        });

        if (preselectMulai) selectJamKeMulai.value = String(preselectMulai);
        if (preselectSelesai) selectJamKeSelesai.value = String(preselectSelesai);
    }

    function loadJamKeSlots(preselectMulai, preselectSelesai, resolveFromJam) {
        fetch(BASE_URL + '/slots', {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            credentials: 'same-origin'
        })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                const slots = (data && data.slots) || [];
                if (resolveFromJam && preselectMulai) {
                    // Terjemahkan jam tersimpan (HH:MM) ke nomor JP terdekat
                    const mulai = cariSlotKe(slots, preselectMulai, 'jam_mulai');
                    const selesai = cariSlotKe(slots, preselectSelesai, 'jam_selesai');
                    fillJamKeSlots(slots, mulai, selesai || mulai);
                    return;
                }
                fillJamKeSlots(slots, preselectMulai, preselectSelesai);
            })
            .catch(function () {
                fillJamKeSlots([], preselectMulai, preselectSelesai);
            });
    }

    function cariSlotKe(slots, jam, kolom) {
        if (!jam) return null;
        const target = jam.slice(0, 5);
        const keys = Object.keys(slots);
        for (let i = 0; i < keys.length; i++) {
            const s = slots[keys[i]];
            if (s[kolom] && s[kolom].slice(0, 5) === target) return s.ke;
        }
        return null;
    }

    // -------------------------------------------------------------------------
    // 6. Lokasi Praktik: sinkron jenis + tampil nama terpilih
    // -------------------------------------------------------------------------
    if (selectLokasi) {
        selectLokasi.addEventListener('change', function () {
            const opt = this.options[this.selectedIndex];
            if (opt && inputJenisLokasi) {
                inputJenisLokasi.value = (opt.dataset && opt.dataset.jenis) || 'lainnya';
            }
            if (lokasiTerpilihInfo && lokasiTerpilihNama) {
                if (this.value) {
                    lokasiTerpilihNama.textContent = this.value;
                    lokasiTerpilihInfo.style.display = 'block';
                } else {
                    lokasiTerpilihInfo.style.display = 'none';
                }
            }
        });
    }

    // -------------------------------------------------------------------------
    // 6b. Cakupan Jadwal: tab Mingguan / Semester
    // -------------------------------------------------------------------------
    function applyCakupan(mode) {
        if (!inputPemberlakuan) return;
        inputPemberlakuan.value = mode;

        document.querySelectorAll('[data-cakupan-tab]').forEach(function (btn) {
            btn.classList.toggle('active', btn.dataset.cakupanTab === mode);
        });

        if (wrapOpsiMingguan) wrapOpsiMingguan.style.display = (mode === 'mingguan') ? 'block' : 'none';
        if (wrapOpsiSemester) wrapOpsiSemester.style.display = (mode === 'semester') ? 'block' : 'none';
    }

    document.querySelectorAll('[data-cakupan-tab]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            applyCakupan(this.dataset.cakupanTab);
        });
    });

    // Tanggal awal bergeser mengikuti minggu ke- yang dipilih (Senin minggu tsb)
    function tanggalAwalMingguKe(mingguKe, tanggalAcuan) {
        if (!mingguKe) return tanggalAcuan;
        const base = tanggalAcuan ? new Date(tanggalAcuan + 'T00:00:00') : new Date();
        const day = base.getDay(); // 0 = Minggu
        const senin = new Date(base);
        senin.setDate(base.getDate() - (day === 0 ? 6 : day - 1) + (mingguKe - 1) * 7);
        const tzOffset = senin.getTimezoneOffset() * 60000;
        return new Date(senin.getTime() - tzOffset).toISOString().split('T')[0];
    }

    if (selectPemberlakuanMinggu) {
        selectPemberlakuanMinggu.addEventListener('change', function () {
            if (selectPemberlakuanMingguAkhir) {
                if (Number(selectPemberlakuanMingguAkhir.value) < Number(this.value)) {
                    selectPemberlakuanMingguAkhir.value = this.value;
                }
            }
            if (inputTanggal && inputTanggal.value) {
                inputTanggal.value = tanggalAwalMingguKe(Number(this.value), inputTanggal.value);
            }
        });
    }

    if (selectPemberlakuanMingguAkhir) {
        selectPemberlakuanMingguAkhir.addEventListener('change', function () {
            if (selectPemberlakuanMinggu && Number(this.value) < Number(selectPemberlakuanMinggu.value)) {
                this.value = selectPemberlakuanMinggu.value;
            }
        });
    }

    // -------------------------------------------------------------------------
    // 6c. Quick Slot dari Matriks Grid (prefill lokasi + jam pelajaran)
    // -------------------------------------------------------------------------
    document.addEventListener('click', function (e) {
        const btnSlot = e.target.closest('.btn-quick-slot');
        if (!btnSlot || !modalJadwal) return;

        openModalCreate();

        if (btnSlot.dataset.lokasi) {
            selectLokasi.value = btnSlot.dataset.lokasi;
            selectLokasi.dispatchEvent(new Event('change'));
        }

        if (btnSlot.dataset.jamKe) {
            if (selectJamKeMulai) selectJamKeMulai.value = btnSlot.dataset.jamKe;
            if (selectJamKeSelesai) selectJamKeSelesai.value = btnSlot.dataset.jamKe;
        }

        if (guruMapelHint) {
            guruMapelHint.innerHTML = '<i class="fas fa-wand-magic-sparkles text-primary me-1"></i> Slot Jam Ke-' +
                (btnSlot.dataset.jamKe || '') + ' (' + (btnSlot.dataset.jamMulai || '') + '-' +
                (btnSlot.dataset.jamSelesai || '') + ') dipilih dari matriks.';
        }
    });

    // -------------------------------------------------------------------------
    // 7. Modal Buka / Tutup
    // -------------------------------------------------------------------------
    function openModalCreate() {
        if (!modalJadwal || !formJadwal) return;

        isEditMode = false;
        formJadwal.reset();
        formJadwal.action = BASE_URL;
        if (jadwalMethod) jadwalMethod.value = 'POST';
        if (modalJadwalTitle) modalJadwalTitle.textContent = 'Jadwalkan Praktik';

        if (inputPembelajaranId) inputPembelajaranId.value = '';
        if (inputMapelId) inputMapelId.value = '';

        if (lokasiTerpilihInfo) lokasiTerpilihInfo.style.display = 'none';

        if (selectRombel) {
            selectRombel.value = '';
        }
        setGuruMapelOptions([]);

        // Default cakupan: minggu berjalan (minggu ke-1, rentang 1 minggu)
        applyCakupan('mingguan');
        if (inputTanggal) {
            const now = new Date();
            const tzOffset = now.getTimezoneOffset() * 60000;
            inputTanggal.value = tanggalAwalMingguKe(1, new Date(now.getTime() - tzOffset).toISOString().split('T')[0]);
        }
        if (selectPemberlakuanMinggu) selectPemberlakuanMinggu.value = '1';
        if (selectPemberlakuanMingguAkhir) selectPemberlakuanMingguAkhir.value = '1';

        loadJamKeSlots(null, null);

        modalJadwal.style.display = 'flex';
    }

    function openModalEdit(data) {
        if (!modalJadwal || !formJadwal) return;

        isEditMode = true;
        formJadwal.reset();
        formJadwal.action = BASE_URL + '/' + data.id;
        if (jadwalMethod) jadwalMethod.value = 'PUT';
        if (modalJadwalTitle) modalJadwalTitle.textContent = 'Ubah Jadwal Praktik';

        if (inputPembelajaranId) inputPembelajaranId.value = data.pembelajaranId || '';
        if (inputMapelId) inputMapelId.value = data.mapelId || '';

        if (selectRombel) selectRombel.value = data.rombelId || '';

        // Mode ubah: kunci cakupan ke sesi tunggal agar tidak menduplikasi seri
        applyCakupan('mingguan');
        if (wrapOpsiMingguan) wrapOpsiMingguan.style.display = 'none';
        if (wrapOpsiSemester) wrapOpsiSemester.style.display = 'none';
        if (inputTanggal) inputTanggal.value = data.tanggal || '';
        if (guruMapelHint) {
            guruMapelHint.innerHTML = '<i class="fas fa-pen-to-square text-primary me-1"></i> Mode ubah: hanya sesi ini yang diperbarui.';
        }

        loadGuruMapel(data.rombelId || '', data.pembelajaranId || null);
        // Preselect JP dari jam tersimpan (terjemahkan HH:MM ke nomor JP)
        loadJamKeSlots(data.jamMulai, data.jamSelesai, true);

        modalJadwal.style.display = 'flex';
    }

    function closeModalJadwal() {
        if (!modalJadwal) return;
        modalJadwal.style.display = 'none';
    }

    window.openModalJadwal = openModalCreate;
    window.closeModalJadwal = closeModalJadwal;

    if (btnOpenModalJadwal) btnOpenModalJadwal.addEventListener('click', openModalCreate);
    if (btnCloseModalJadwal) btnCloseModalJadwal.addEventListener('click', closeModalJadwal);
    if (btnCancelModalJadwal) btnCancelModalJadwal.addEventListener('click', closeModalJadwal);
    if (modalJadwal) {
        modalJadwal.addEventListener('click', function (e) {
            if (e.target === modalJadwal) closeModalJadwal();
        });
    }

    // Muat ulang daftar guru & mapel saat kelas berubah
    if (selectRombel) {
        selectRombel.addEventListener('change', function () {
            loadGuruMapel(this.value, null);
        });
    }

    // Delegasi tombol ubah jadwal di baris tabel & sel matriks
    document.addEventListener('click', function (e) {
        const btnEdit = e.target.closest('.btn-edit-jadwal');
        if (btnEdit) {
            openModalEdit({
                id: btnEdit.dataset.id,
                lokasi: btnEdit.dataset.lokasi,
                jenis: btnEdit.dataset.jenis,
                rombelId: btnEdit.dataset.rombelId,
                rombelNama: btnEdit.dataset.rombelNama,
                pembelajaranId: btnEdit.dataset.pembelajaranId,
                mapel: btnEdit.dataset.mapel,
                ptkId: btnEdit.dataset.ptkId,
                tanggal: btnEdit.dataset.tanggal,
                jamMulai: btnEdit.dataset.jamMulai,
                jamSelesai: btnEdit.dataset.jamSelesai,
                jamKeMulai: btnEdit.dataset.jamKeMulai,
                jamKeSelesai: btnEdit.dataset.jamKeSelesai,
                status: btnEdit.dataset.status
            });
        }
    });

    // -------------------------------------------------------------------------
    // 7b. Submit Form Jadwal (AJAX)
    // -------------------------------------------------------------------------
    if (formJadwal) {
        formJadwal.addEventListener('submit', function (e) {
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

            if (selectJamKeMulai && selectJamKeSelesai && Number(selectJamKeSelesai.value) < Number(selectJamKeMulai.value)) {
                notifyError('Data Belum Lengkap', 'Jam pelajaran selesai tidak boleh lebih kecil dari jam mulai.');
                return;
            }

            const submitBtn = formJadwal.querySelector('button[type="submit"]');
            const originalHtml = submitBtn ? submitBtn.innerHTML : '';
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Menyimpan...';
            }

            fetch(formJadwal.action, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                credentials: 'same-origin',
                body: new FormData(formJadwal)
            })
                .then(async function (res) {
                    const data = await res.json().catch(function () { return {}; });
                    if (!res.ok) {
                        throw new Error(extractErrorMessage(data, 'Terjadi kesalahan saat menyimpan jadwal.'));
                    }
                    return data;
                })
                .then(function (data) {
                    closeModalJadwal();
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
    // -------------------------------------------------------------------------
    // 8. Modal Update Status & Laporan Kerusakan
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
    // 9. Hapus Jadwal
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
    // 10. Pemberlakuan Jadwal (Aktifkan Mode Mingguan / Semester)
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
