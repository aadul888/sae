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
    const inputHariMingguan = document.getElementById('inputHariMingguan');
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
    const inputHariSemester = document.getElementById('inputHariSemester');
    const selectJamKeMulaiSemester = document.getElementById('selectJamKeMulaiSemester');
    const selectJamKeSelesaiSemester = document.getElementById('selectJamKeSelesaiSemester');
    const selectPemberlakuanSemesterS = document.getElementById('selectPemberlakuanSemesterS');
    const selectPemberlakuanTaS = document.getElementById('selectPemberlakuanTaS');
    const inputTanggalSemester = document.getElementById('inputTanggalSemester');

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
            btnDropdownCetakJadwal.setAttribute('aria-expanded', isVisible ? 'false' : 'true');
        });
        document.addEventListener('click', function (e) {
            if (!btnDropdownCetakJadwal.contains(e.target)) {
                dropdownMenuCetakJadwal.style.display = 'none';
                btnDropdownCetakJadwal.setAttribute('aria-expanded', 'false');
            }
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

    /**
     * Isi pilihan Jam Pelajaran. Rentang boleh mencakup beberapa JP
     * (mis. Jam ke-1 s/d Jam ke-4), jadi kedua dropdown memuat daftar slot yang
     * sama dan pilihan selesai tidak lagi dipaksa mengikuti pilihan mulai.
     */
    function fillJamKeSlots(selectMulai, selectSelesai, slots, preselectMulai, preselectSelesai) {
        if (!selectMulai) return;

        selectMulai.innerHTML = '';
        if (selectSelesai) selectSelesai.innerHTML = '';

        const keys = Object.keys(slots || {}).filter(function (ke) {
            const s = slots[ke];
            // Lewati slot rutinitas (Upacara, Pembiasaan, Istirahat) — bukan jam praktik.
            return !s || !(s.is_break || s.is_upacara || s.is_pembiasaan || s.schedulable === false);
        });

        if (keys.length === 0) {
            for (let k = 1; k <= 10; k++) {
                selectMulai.appendChild(new Option('Jam ke-' + k, k));
                if (selectSelesai) selectSelesai.appendChild(new Option('Jam ke-' + k, k));
            }
            selectMulai.value = preselectMulai || '1';
            if (selectSelesai) selectSelesai.value = preselectSelesai || selectMulai.value;
            return;
        }

        keys.forEach(function (ke) {
            const s = slots[ke];
            const label = 'Jam ke-' + s.ke + ' (' + s.mulai + ' - ' + s.selesai + ')';
            selectMulai.appendChild(new Option(label, s.ke));
            if (selectSelesai) selectSelesai.appendChild(new Option(label, s.ke));
        });

        if (preselectMulai) selectMulai.value = String(preselectMulai);
        if (selectSelesai) {
            if (preselectSelesai) selectSelesai.value = String(preselectSelesai);
            else if (preselectMulai) selectSelesai.value = String(preselectMulai);
        }
    }

    /**
     * Jaga agar "Jam Selesai" tidak pernah lebih kecil dari "Jam Mulai".
     * Rentang beberapa JP tetap diperbolehkan (mis. 1 s/d 4).
     */
    function jagaRentangJam(selectMulai, selectSelesai) {
        if (!selectMulai || !selectSelesai) return;

        const sinkron = function () {
            const mulai = Number(selectMulai.value || 0);
            const selesai = Number(selectSelesai.value || 0);
            if (mulai > 0 && selesai < mulai) selectSelesai.value = String(mulai);
        };

        selectMulai.addEventListener('change', function () {
            // Jam selesai belum dipilih: ikut jam mulai agar tidak kosong.
            if (!selectSelesai.value) selectSelesai.value = selectMulai.value || '';
            sinkron();
        });
        selectSelesai.addEventListener('change', sinkron);
        sinkron();
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
            let label = item.nama_guru || 'Belum Ditugaskan';
            if (item.mata_pelajaran) label += ' \u2014 ' + item.mata_pelajaran;
            opt.textContent = label;
            selectEl.appendChild(opt);
        });

        selectEl.disabled = false;
        if (hintEl) {
            hintEl.innerHTML = '<i class="fas fa-circle-check me-1" style="color: var(--success, #10b981);"></i> ' + items.length + ' mata pelajaran dimuat otomatis dari kelas.';
        }
    }

    // Helper sinkronisasi dataset mapel dan guru ke input hidden form
    function syncGuruMapelHiddenInputs(form, selectEl) {
        if (!form || !selectEl) return;
        const selected = selectEl.options[selectEl.selectedIndex];
        let inMapel = form.querySelector('input[name="mata_pelajaran"]');
        let inMapelId = form.querySelector('input[name="mata_pelajaran_id"]');
        let inPtkId = form.querySelector('input[name="ptk_id"]');

        if (!inMapel) {
            inMapel = document.createElement('input');
            inMapel.type = 'hidden';
            inMapel.name = 'mata_pelajaran';
            form.appendChild(inMapel);
        }
        if (!inMapelId) {
            inMapelId = document.createElement('input');
            inMapelId.type = 'hidden';
            inMapelId.name = 'mata_pelajaran_id';
            form.appendChild(inMapelId);
        }
        if (!inPtkId) {
            inPtkId = document.createElement('input');
            inPtkId.type = 'hidden';
            inPtkId.name = 'ptk_id';
            form.appendChild(inPtkId);
        }

        if (selected && selected.value) {
            inMapel.value = selected.dataset.mapel || '';
            inMapelId.value = selected.dataset.mapelId || '';
            inPtkId.value = selected.dataset.ptkId || '';
        } else {
            inMapel.value = '';
            inMapelId.value = '';
            inPtkId.value = '';
        }
    }

    /**
     * Kunci / buka pilihan kelas pada modal.
     * Saat laboran mengisi dari sel matriks, kelas sudah ditentukan oleh sel
     * yang diklik sehingga dropdown kelas dibuat read-only (tidak bisa diganti)
     * agar jadwal tidak tersimpan ke kelas lain.
     *
     * Catatan: elemen `disabled` TIDAK ikut terkirim saat submit, jadi nilainya
     * disalin ke hidden input `rombel_id` pendamping.
     */
    function setKelasTerpilih(selectEl, rombelId, rombelNama) {
        if (!selectEl) return;

        const form = selectEl.form;
        let hidden = form ? form.querySelector('input[type="hidden"][data-kelas-locked]') : null;

        if (rombelId) {
            // Pastikan opsi tersedia sebelum mengunci (mis. kelas di luar cakupan list).
            if (![...selectEl.options].some(function (o) { return o.value === rombelId; })) {
                selectEl.appendChild(new Option(rombelNama || rombelId, rombelId));
            }
            selectEl.value = rombelId;
            selectEl.dataset.locked = '1';
            selectEl.disabled = true;
            selectEl.style.background = 'var(--bg-hover)';
            selectEl.style.cursor = 'not-allowed';

            if (form) {
                if (!hidden) {
                    hidden = document.createElement('input');
                    hidden.type = 'hidden';
                    hidden.name = 'rombel_id';
                    hidden.setAttribute('data-kelas-locked', '1');
                    form.appendChild(hidden);
                }
                hidden.value = rombelId;
            }
        } else {
            selectEl.disabled = false;
            delete selectEl.dataset.locked;
            selectEl.style.background = '';
            selectEl.style.cursor = '';
            if (hidden) hidden.remove();
        }
    }

    /**
     * Buka kunci kelas (dipakai saat modal dibuka tanpa konteks sel matriks).
     */
    function bukaKunciKelas(selectEl) {
        if (!selectEl) return;
        selectEl.disabled = false;
        delete selectEl.dataset.locked;
        selectEl.style.background = '';
        selectEl.style.cursor = '';
        const form = selectEl.form;
        const hidden = form ? form.querySelector('input[type="hidden"][data-kelas-locked]') : null;
        if (hidden) hidden.remove();
    }

    function loadGuruMapel(rombelId, selectEl, hintEl, preselectPembelajaranId, hari) {
        if (!selectEl) return;

        if (!rombelId) {
            selectEl.innerHTML = '<option value="">-- Pilih Kelas Terlebih Dahulu --</option>';
            selectEl.disabled = true;
            if (hintEl) hintEl.textContent = 'Pilih kelas untuk memuat daftar guru & mata pelajaran.';
            return;
        }

        let url = GURU_MAPEL_URL + '?rombel_id=' + encodeURIComponent(rombelId);
        if (hari) url += '&hari=' + encodeURIComponent(hari);

        fetch(url, {
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
            const hari = inputHariMingguan ? inputHariMingguan.value : '';
            loadGuruMapel(this.value, selectGuruMapelMingguan, hintMingguan, null, hari);
        });
    }

    if (inputTanggalMingguan) {
        inputTanggalMingguan.addEventListener('change', function () {
            if (!this.value) return;
            const targetHari = hariDariTanggal(this.value) || 'Senin';
            if (inputHariMingguan) inputHariMingguan.value = targetHari;
            loadSlotsForHari(targetHari, selectJamKeMulaiMingguan, selectJamKeSelesaiMingguan);
        });
    }

    if (inputTanggalSemester) {
        inputTanggalSemester.addEventListener('change', function () {
            if (!this.value) return;
            const targetHari = hariDariTanggal(this.value) || 'Senin';
            if (inputHariSemester) inputHariSemester.value = targetHari;
            loadSlotsForHari(targetHari, selectJamKeMulaiSemester, selectJamKeSelesaiSemester);
        });
    }

    // -------------------------------------------------------------------------
    // 7b. Util Hari & Tanggal (penjadwalan berbasis slot matriks)
    // -------------------------------------------------------------------------
    const HARI_URUT = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

    function fmtDate(d) {
        const y = d.getFullYear();
        const m = String(d.getMonth() + 1).padStart(2, '0');
        const dd = String(d.getDate()).padStart(2, '0');
        return y + '-' + m + '-' + dd;
    }

    function hariDariTanggal(dateStr) {
        if (!dateStr) return null;
        const d = new Date(dateStr + 'T00:00:00');
        if (isNaN(d.getTime())) return null;
        const nama = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'][d.getDay()];
        return inArray(nama, HARI_URUT) ? nama : null;
    }

    function tanggalUntukHari(hari) {
        const today = new Date();
        const target = HARI_URUT.indexOf(hari) + 1; // Senin=1 .. Sabtu=6
        const d = new Date(today.getFullYear(), today.getMonth(), today.getDate());
        if (target < 1) return fmtDate(d);
        const diff = (target - d.getDay() + 7) % 7;
        d.setDate(d.getDate() + diff);
        return fmtDate(d);
    }

    function inArray(needle, haystack) {
        return haystack.indexOf(needle) !== -1;
    }

    if (selectGuruMapelMingguan) {
        selectGuruMapelMingguan.addEventListener('change', function () {
            syncGuruMapelHiddenInputs(formJadwalMingguan, this);
        });
    }

    if (selectRombelSemester) {
        selectRombelSemester.addEventListener('change', function () {
            const hari = inputHariSemester ? inputHariSemester.value : '';
            loadGuruMapel(this.value, selectGuruMapelSemester, hintSemester, null, hari);
        });
    }

    if (selectGuruMapelSemester) {
        selectGuruMapelSemester.addEventListener('change', function () {
            syncGuruMapelHiddenInputs(formJadwalSemester, this);
        });
    }

    // -------------------------------------------------------------------------
    // 8. Buka / Tutup Modal Tambah & Ubah Jadwal
    // -------------------------------------------------------------------------
    function getActiveMode() {
        const activeTab = document.querySelector('.btn-tab-pemberlakuan[data-is-active="1"]');
        if (activeTab && activeTab.dataset.mode) return activeTab.dataset.mode;
        if (btnOpenModalJadwal && btnOpenModalJadwal.dataset.activeMode) return btnOpenModalJadwal.dataset.activeMode;
        return 'semester';
    }

    function openModalCreate(mode) {
        mode = mode || getActiveMode();

        if (mode === 'semester') {
            if (!modalJadwalSemester || !formJadwalSemester) return;
            formJadwalSemester.reset();
            formJadwalSemester.action = BASE_URL;
            if (jadwalMethodSemester) jadwalMethodSemester.value = 'POST';
            if (modalJadwalSemesterTitle) modalJadwalSemesterTitle.textContent = 'Jadwalkan Praktik (Semester)';
            bukaKunciKelas(selectRombelSemester);
            setGuruMapelOptions(selectGuruMapelSemester, hintSemester, []);
            if (inputTanggalSemester) {
                inputTanggalSemester.value = fmtDate(new Date());
            }
            const hariAwal = (inputHariSemester && inArray(inputHariSemester.value, HARI_URUT))
                ? inputHariSemester.value : 'Senin';
            if (inputHariSemester) inputHariSemester.value = hariAwal;
            loadSlotsForHari(hariAwal, selectJamKeMulaiSemester, selectJamKeSelesaiSemester);
            modalJadwalSemester.style.display = 'flex';
        } else {
            if (!modalJadwalMingguan || !formJadwalMingguan) return;
            formJadwalMingguan.reset();
            formJadwalMingguan.action = BASE_URL;
            if (jadwalMethodMingguan) jadwalMethodMingguan.value = 'POST';
            if (modalJadwalMingguanTitle) modalJadwalMingguanTitle.textContent = 'Jadwalkan Praktik (Mingguan)';
            bukaKunciKelas(selectRombelMingguan);
            setGuruMapelOptions(selectGuruMapelMingguan, hintMingguan, []);
            if (inputTanggalMingguan) {
                inputTanggalMingguan.value = fmtDate(new Date());
            }
            if (selectPemberlakuanMingguM) selectPemberlakuanMingguM.value = '1';
            if (selectPemberlakuanMingguAkhirM) selectPemberlakuanMingguAkhirM.value = '1';
            const hariAwal = (inputHariMingguan && inArray(inputHariMingguan.value, HARI_URUT))
                ? inputHariMingguan.value : 'Senin';
            if (inputHariMingguan) inputHariMingguan.value = hariAwal;
            loadSlotsForHari(hariAwal, selectJamKeMulaiMingguan, selectJamKeSelesaiMingguan);
            modalJadwalMingguan.style.display = 'flex';
        }
    }

    // Quick Slot pada Matriks Grid (baris = kelas, kolom = jam ke-1..13)
    document.addEventListener('click', function (e) {
        const btnSlot = e.target.closest('.btn-quick-slot');
        if (!btnSlot) return;

        const mode = getActiveMode();
        openModalCreate(mode);

        const slotHari = btnSlot.dataset.hari || 'Senin';
        const jamKe = btnSlot.dataset.jamKe || '1';
        const rombelId = btnSlot.dataset.rombelId || '';
        const rombelNama = btnSlot.dataset.rombelNama || '';

        if (mode === 'semester') {
            if (inputHariSemester) inputHariSemester.value = slotHari;
            // Kelas dikunci dari baris grid, lalu mapel/guru dimuat otomatis
            // terbatas pada mapel yang terjadwal di Jadwal KBM kelas + hari ini.
            setKelasTerpilih(selectRombelSemester, rombelId, rombelNama);
            if (rombelId) {
                loadGuruMapel(rombelId, selectGuruMapelSemester, hintSemester, null, slotHari);
            }
            loadSlotsForHari(slotHari, selectJamKeMulaiSemester, selectJamKeSelesaiSemester, jamKe, jamKe);
        } else {
            if (inputHariMingguan) inputHariMingguan.value = slotHari;
            if (inputTanggalMingguan) inputTanggalMingguan.value = tanggalUntukHari(slotHari);
            setKelasTerpilih(selectRombelMingguan, rombelId, rombelNama);
            if (rombelId) {
                loadGuruMapel(rombelId, selectGuruMapelMingguan, hintMingguan, null, slotHari);
            }
            loadSlotsForHari(slotHari, selectJamKeMulaiMingguan, selectJamKeSelesaiMingguan, jamKe, jamKe);
        }

        if (rombelNama) {
            const hint = mode === 'semester' ? hintSemester : hintMingguan;
            if (hint) hint.textContent = 'Kelas: ' + rombelNama;
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
            bukaKunciKelas(selectRombelSemester);
            if (selectRombelSemester) selectRombelSemester.value = d.rombelId || '';
            if (inputTanggalSemester) inputTanggalSemester.value = d.tanggal || '';
            const hariEditS = hariDariTanggal(d.tanggal) || 'Senin';
            if (inputHariSemester) inputHariSemester.value = hariEditS;
            loadGuruMapel(d.rombelId || '', selectGuruMapelSemester, hintSemester, d.pembelajaranId || null, hariEditS);
            loadSlotsForHari(hariEditS, selectJamKeMulaiSemester, selectJamKeSelesaiSemester);
            modalJadwalSemester.style.display = 'flex';
        } else {
            if (!modalJadwalMingguan || !formJadwalMingguan) return;
            formJadwalMingguan.reset();
            formJadwalMingguan.action = BASE_URL + '/' + d.id;
            if (jadwalMethodMingguan) jadwalMethodMingguan.value = 'PUT';
            if (modalJadwalMingguanTitle) modalJadwalMingguanTitle.textContent = 'Ubah Jadwal Praktik (Mingguan)';
            if (selectLokasiMingguan) selectLokasiMingguan.value = d.lokasi || '';
            bukaKunciKelas(selectRombelMingguan);
            if (selectRombelMingguan) selectRombelMingguan.value = d.rombelId || '';
            if (inputTanggalMingguan) inputTanggalMingguan.value = d.tanggal || '';
            const hariEditM = hariDariTanggal(d.tanggal) || 'Senin';
            if (inputHariMingguan) inputHariMingguan.value = hariEditM;
            loadGuruMapel(d.rombelId || '', selectGuruMapelMingguan, hintMingguan, d.pembelajaranId || null, hariEditM);
            loadSlotsForHari(hariEditM, selectJamKeMulaiMingguan, selectJamKeSelesaiMingguan);
            modalJadwalMingguan.style.display = 'flex';
        }
    });

    // -------------------------------------------------------------------------
    // 9. Submit Form Jadwal (Mingguan & Semester)
    // -------------------------------------------------------------------------
    function handleFormSubmit(form, selectLokasi, selectRombel, selectGuruMapel, selectJamMulai, selectJamSelesai, inputTanggal, selectMingguMulai, selectMingguAkhir, modalEl) {
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

            if (inputTanggal && !inputTanggal.value) {
                notifyError('Data Belum Lengkap', 'Pilih tanggal awal pelaksanaan praktik.');
                return;
            }

            if (selectMingguMulai && selectMingguAkhir && Number(selectMingguAkhir.value) < Number(selectMingguMulai.value)) {
                notifyError('Data Belum Lengkap', 'Sampai minggu ke- tidak boleh lebih kecil dari minggu ke- awal.');
                return;
            }

            if (selectJamMulai && selectJamSelesai && Number(selectJamSelesai.value) < Number(selectJamMulai.value)) {
                notifyError('Data Belum Lengkap', 'Jam pelajaran selesai tidak boleh lebih kecil dari jam mulai.');
                return;
            }

            // Pastikan data guru & mapel tersinkronisasi ke hidden input form
            syncGuruMapelHiddenInputs(form, selectGuruMapel);

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

    // Rentang jam: jam selesai tidak boleh lebih kecil dari jam mulai.
    jagaRentangJam(selectJamKeMulaiMingguan, selectJamKeSelesaiMingguan);
    jagaRentangJam(selectJamKeMulaiSemester, selectJamKeSelesaiSemester);

    handleFormSubmit(
        formJadwalMingguan,
        selectLokasiMingguan,
        selectRombelMingguan,
        selectGuruMapelMingguan,
        selectJamKeMulaiMingguan,
        selectJamKeSelesaiMingguan,
        inputTanggalMingguan,
        selectPemberlakuanMingguM,
        selectPemberlakuanMingguAkhirM,
        modalJadwalMingguan
    );

    handleFormSubmit(
        formJadwalSemester,
        selectLokasiSemester,
        selectRombelSemester,
        selectGuruMapelSemester,
        selectJamKeMulaiSemester,
        selectJamKeSelesaiSemester,
        inputTanggalSemester,
        null,
        null,
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
    // 12. Konfirmasi Pindah Tab Pemberlakuan (Mingguan <-> Semester)
    // -------------------------------------------------------------------------
    document.addEventListener('click', function (e) {
        const tabLink = e.target.closest('.btn-tab-pemberlakuan');
        if (!tabLink) return;

        const isActive = tabLink.dataset.isActive === '1';
        if (isActive) {
            return;
        }

        e.preventDefault();
        const targetUrl = tabLink.href;
        const targetLabel = tabLink.dataset.label || 'Jadwal';

        const promptTitle = 'Memberlakukan Jadwal ' + targetLabel + '?';
        const promptHtml = 'Apakah Anda yakin ingin memberlakukan <strong>Jadwal ' + targetLabel + '</strong>?<br><small style="color: var(--text-muted);">Sistem akan secara ketat menerapkan Jadwal ' + targetLabel + ' sebagai acuan tunggal.</small>';

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: promptTitle,
                html: promptHtml,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3b82f6',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Ya, Berlakukan',
                cancelButtonText: 'Batal'
            }).then(function (result) {
                if (result.isConfirmed) {
                    window.location.href = targetUrl;
                }
            });
        } else {
            if (confirm('Apakah Anda yakin ingin memberlakukan Jadwal ' + targetLabel + '?')) {
                window.location.href = targetUrl;
            }
        }
    });

    // -------------------------------------------------------------------------
    // 13. Pemberlakuan Jadwal (Aktifkan Mode Mingguan / Semester)
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
