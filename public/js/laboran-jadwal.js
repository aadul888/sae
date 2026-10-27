/**
 * JavaScript Modul Jadwal Praktik & Penggunaan Lokasi (Laboratorium / Bengkel / Lahan / Lapangan)
 * Standar Baku SAE: Vanilla JS, Modal Handling (z-index: 99999), SweetAlert2
 *
 * Fitur:
 *  1. Modal tambah & ubah jadwal (satu form dipakai ulang).
 *  2. Mata pelajaran otomatis dari kelas (fetch data pembelajaran Dapodik).
 *  3. Topik / materi praktik bersifat opsional.
 *  4. Update status sesi + laporan kerusakan.
 *  5. Hapus jadwal dengan konfirmasi.
 */

document.addEventListener('DOMContentLoaded', function () {
    if (!window.location.pathname.includes('/dashboard/laboran/jadwal')) {
        return;
    }

    // -------------------------------------------------------------------------
    // 1. Referensi Elemen
    // -------------------------------------------------------------------------
    const modalJadwal = document.getElementById('modalJadwal');
    const btnOpenModalJadwal = document.getElementById('btnOpenModalJadwal');
    const btnCloseModalJadwal = document.getElementById('btnCloseModalJadwal');
    const btnCancelModalJadwal = document.getElementById('btnCancelModalJadwal');
    const modalJadwalTitle = document.getElementById('modalJadwalTitle');
    const formJadwal = document.getElementById('formJadwal');
    const jadwalMethod = document.getElementById('jadwalMethod');

    const selectJenisLokasi = document.getElementById('selectJenisLokasi');
    const selectLokasi = document.getElementById('selectLokasi');
    const selectRombel = document.getElementById('selectRombel');
    const selectMapel = document.getElementById('selectMapel');
    const selectGuru = document.getElementById('selectGuru');
    const inputPembelajaranId = document.getElementById('inputPembelajaranId');
    const inputMapelId = document.getElementById('inputMapelId');
    const selectKbmSlot = document.getElementById('selectKbmSlot');
    const pemberlakuanHint = document.getElementById('pemberlakuanHint');
    const chkMapelManual = document.getElementById('chkMapelManual');
    const inputMapelManual = document.getElementById('inputMapelManual');
    const mapelHint = document.getElementById('mapelHint');
    const inputTanggal = document.getElementById('inputTanggal');
    const inputJamMulai = document.getElementById('inputJamMulai');
    const inputJamSelesai = document.getElementById('inputJamSelesai');
    const inputTopik = document.getElementById('inputTopik');
    const inputAlat = document.getElementById('inputAlat');

    const BASE_URL = '/dashboard/laboran/jadwal';
    const PEMBELAJARAN_URL = BASE_URL + '/pembelajaran';

    let mapelCache = {};
    let isEditMode = false;

    // -------------------------------------------------------------------------
    // 2. Util: Alert — delegasi ke helper bersama (laboran-common.js)
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
    // 3. Filter Lokasi Berdasarkan Jenis (optgroup)
    // -------------------------------------------------------------------------
    function filterLokasiByJenis(jenis, selectedName) {
        if (!selectLokasi) return;

        const groups = selectLokasi.querySelectorAll('optgroup');
        let hasVisible = false;

        groups.forEach(function (group) {
            const match = !jenis || group.dataset.jenis === jenis;
            group.style.display = match ? '' : 'none';
            group.disabled = !match;
            if (match) hasVisible = true;
        });

        // Opsi tanpa optgroup (fallback) tetap ditampilkan
        const looseOptions = Array.prototype.slice.call(selectLokasi.querySelectorAll('option:not(optgroup option)'));
        looseOptions.forEach(function (opt) {
            if (opt.value === '') return;
            if (jenis) {
                opt.style.display = 'none';
                opt.disabled = true;
            } else {
                opt.style.display = '';
                opt.disabled = false;
            }
        });

        if (selectedName) {
            selectLokasi.value = selectedName;
            // Bila opsi hasil edit tidak lagi terlihat, tetap pertahankan nilainya
            if (selectLokasi.value !== selectedName) {
                const opt = document.createElement('option');
                opt.value = selectedName;
                opt.textContent = selectedName;
                selectLokasi.appendChild(opt);
                selectLokasi.value = selectedName;
            }
        } else {
            selectLokasi.value = '';
        }

        if (!hasVisible) {
            // Tidak ada lokasi pada jenis tersebut; biarkan dropdown kosong agar pengguna sadar
            selectLokasi.value = '';
        }
    }

    // Sinkronkan jenis lokasi ketika pengguna memilih lokasi secara langsung
    function syncJenisFromLokasi() {
        if (!selectLokasi || !selectJenisLokasi) return;
        const opt = selectLokasi.options[selectLokasi.selectedIndex];
        if (!opt) return;
        const jenis = opt.dataset ? opt.dataset.jenis : '';
        if (jenis) {
            selectJenisLokasi.value = jenis;
        }
    }

    if (selectLokasi) {
        selectLokasi.addEventListener('change', syncJenisFromLokasi);
    }

    if (selectJenisLokasi) {
        selectJenisLokasi.addEventListener('change', function () {
            filterLokasiByJenis(this.value, '');
        });
    }

    // -------------------------------------------------------------------------
    // 4. Mata Pelajaran Otomatis dari Kelas
    // -------------------------------------------------------------------------
    function setMapelOptions(items) {
        if (!selectMapel) return;

        selectMapel.innerHTML = '';

        if (!items || items.length === 0) {
            const opt = document.createElement('option');
            opt.value = '';
            opt.textContent = '-- Tidak ada mata pelajaran pada kelas ini --';
            selectMapel.appendChild(opt);
            selectMapel.disabled = true;
            if (mapelHint) {
                mapelHint.innerHTML = '<i class="fas fa-triangle-exclamation me-1" style="color: var(--warning, #f59e0b);"></i>' +
                    'Kelas ini belum memiliki data pembelajaran. Aktifkan "Isi manual" atau sinkronkan Dapodik.';
            }
            return;
        }

        const placeholder = document.createElement('option');
        placeholder.value = '';
        placeholder.textContent = '-- Pilih Mata Pelajaran --';
        selectMapel.appendChild(placeholder);

        items.forEach(function (item, idx) {
            const opt = document.createElement('option');
            opt.value = String(idx);
            let label = item.nama_mata_pelajaran;
            if (item.nama_guru) {
                label += ' — ' + item.nama_guru;
            }
            opt.textContent = label;
            selectMapel.appendChild(opt);
        });

        selectMapel.disabled = false;
        if (mapelHint) {
            mapelHint.innerHTML = '<i class="fas fa-circle-check me-1" style="color: var(--success, #10b981);"></i>' +
                items.length + ' mata pelajaran dimuat otomatis dari kelas.';
        }
    }

    function loadMapelForRombel(rombelId, preselect, preselectPtk) {
        if (!selectMapel) return;

        if (!rombelId) {
            selectMapel.innerHTML = '<option value="">-- Pilih Kelas Terlebih Dahulu --</option>';
            selectMapel.disabled = true;
            if (mapelHint) {
                mapelHint.textContent = 'Pilih kelas untuk memuat daftar mata pelajaran.';
            }
            return;
        }

        if (mapelCache[rombelId]) {
            applyMapelList(rombelId, mapelCache[rombelId], preselect, preselectPtk);
            return;
        }

        selectMapel.innerHTML = '<option value="">Memuat mata pelajaran…</option>';
        selectMapel.disabled = true;
        if (mapelHint) {
            mapelHint.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Memuat mata pelajaran dari data kelas…';
        }

        fetch(PEMBELAJARAN_URL + '?rombel_id=' + encodeURIComponent(rombelId), {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            credentials: 'same-origin'
        })
            .then(function (res) {
                if (!res.ok) throw new Error('Gagal memuat data mata pelajaran.');
                return res.json();
            })
            .then(function (data) {
                const items = (data && data.pembelajaran) || [];
                mapelCache[rombelId] = items;
                applyMapelList(rombelId, items, preselect, preselectPtk);
            })
            .catch(function (err) {
                setMapelOptions([]);
                if (mapelHint) {
                    mapelHint.innerHTML = '<i class="fas fa-triangle-exclamation me-1" style="color: var(--danger, #ef4444);"></i>' +
                        (err.message || 'Gagal memuat mata pelajaran.');
                }
            });
    }

    function applyMapelList(rombelId, items, preselect, preselectPtk) {
        setMapelOptions(items);

        if (!selectMapel) return;

        if (preselect) {
            let foundIdx = -1;

            items.forEach(function (item, idx) {
                if (foundIdx !== -1) return;
                const namaMatch = item.nama_mata_pelajaran === preselect;
                const guruMatch = !preselectPtk || item.ptk_id === preselectPtk;
                if (namaMatch && guruMatch) {
                    foundIdx = idx;
                }
            });

            // Bila pencocokan ketat gagal, coba cocokkan nama saja
            if (foundIdx === -1) {
                items.forEach(function (item, idx) {
                    if (foundIdx === -1 && item.nama_mata_pelajaran === preselect) {
                        foundIdx = idx;
                    }
                });
            }

            if (foundIdx !== -1) {
                selectMapel.value = String(foundIdx);
                onMapelSelected();
                return;
            }
        }

        // Default: pilih item pertama bila tidak ada praseleksi
        if (!preselect && items.length > 0) {
            selectMapel.value = '0';
            onMapelSelected();
        }
    }

    function onMapelSelected() {
        if (!selectMapel || !selectRombel) return;

        const rombelId = selectRombel.value;
        const idx = selectMapel.value;
        const items = mapelCache[rombelId] || [];
        const item = idx !== '' ? items[Number(idx)] : null;

        if (!item) {
            if (inputPembelajaranId) inputPembelajaranId.value = '';
            if (inputMapelId) inputMapelId.value = '';
            return;
        }

        if (inputPembelajaranId) inputPembelajaranId.value = item.pembelajaran_id || '';
        if (inputMapelId) inputMapelId.value = item.mata_pelajaran_id || '';

        // Guru pengampu terisi otomatis dari pembelajaran (bila belum dipilih manual)
        if (selectGuru && item.ptk_id) {
            const hasOption = Array.prototype.some.call(selectGuru.options, function (o) {
                return o.value === item.ptk_id;
            });
            if (!hasOption) {
                const opt = document.createElement('option');
                opt.value = item.ptk_id;
                opt.textContent = item.nama_guru || 'Guru Pengampu';
                selectGuru.appendChild(opt);
            }
            selectGuru.value = item.ptk_id;
        }
    }

    let kbmCache = {};

    function loadKbmSlotsForRombel(rombelId) {
        if (!selectKbmSlot) return;

        if (!rombelId) {
            selectKbmSlot.innerHTML = '<option value="">-- Pilih Kelas Terlebih Dahulu --</option>';
            selectKbmSlot.disabled = true;
            return;
        }

        selectKbmSlot.innerHTML = '<option value="">Memuat jadwal KBM…</option>';
        selectKbmSlot.disabled = true;

        if (kbmCache[rombelId]) {
            applyKbmSlots(kbmCache[rombelId]);
            return;
        }

        fetch(BASE_URL + '/kbm?rombel_id=' + encodeURIComponent(rombelId), {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            credentials: 'same-origin'
        })
        .then(function (res) { return res.json(); })
        .then(function (data) {
            const list = (data && data.kbm) || [];
            kbmCache[rombelId] = list;
            applyKbmSlots(list);
        })
        .catch(function () {
            selectKbmSlot.innerHTML = '<option value="">-- Tidak ada jadwal KBM aktif --</option>';
            selectKbmSlot.disabled = true;
        });
    }

    function applyKbmSlots(slots) {
        if (!selectKbmSlot) return;

        selectKbmSlot.innerHTML = '';
        if (!slots || slots.length === 0) {
            selectKbmSlot.innerHTML = '<option value="">-- Tidak ada jadwal KBM aktif untuk kelas ini --</option>';
            selectKbmSlot.disabled = true;
            return;
        }

        const placeholder = document.createElement('option');
        placeholder.value = '';
        placeholder.textContent = '-- Pilih Slot Jadwal KBM (Auto-Fill) --';
        selectKbmSlot.appendChild(placeholder);

        slots.forEach(function (slot, idx) {
            const opt = document.createElement('option');
            opt.value = String(idx);
            opt.textContent = slot.label;
            selectKbmSlot.appendChild(opt);
        });

        selectKbmSlot.disabled = false;
    }

    if (selectKbmSlot) {
        selectKbmSlot.addEventListener('change', function () {
            const rombelId = selectRombel ? selectRombel.value : '';
            const slots = kbmCache[rombelId] || [];
            const idx = this.value;
            const slot = idx !== '' ? slots[Number(idx)] : null;
            if (!slot) return;

            // Auto-fill mapel
            if (slot.nama_mata_pelajaran) {
                if (chkMapelManual && chkMapelManual.checked) {
                    chkMapelManual.checked = false;
                    chkMapelManual.dispatchEvent(new Event('change'));
                }
                loadMapelForRombel(rombelId, slot.nama_mata_pelajaran, slot.ptk_id);
            }

            // Auto-fill jam
            if (inputJamMulai && slot.jam_mulai) inputJamMulai.value = slot.jam_mulai;
            if (inputJamSelesai && slot.jam_selesai) inputJamSelesai.value = slot.jam_selesai;

            // Auto-fill guru
            if (selectGuru && slot.ptk_id) {
                selectGuru.value = slot.ptk_id;
            }
        });
    }

    // =========================================================================
    // Pemberlakuan: 2 tab (Mingguan / Bulanan)
    // =========================================================================
    const inputPemberlakuan = document.getElementById('inputPemberlakuan');
    const wrapHariMingguan = document.getElementById('wrapHariMingguan');
    const labelTanggalAwal = document.getElementById('labelTanggalAwal');
    const labelTanggalAkhir = document.getElementById('labelTanggalAkhir');

    function applyPemberlakuan(mode) {
        if (!inputPemberlakuan) return;
        inputPemberlakuan.value = mode;

        // Sorot tab aktif
        document.querySelectorAll('[data-pemberlakuan-tab]').forEach(function (btn) {
            btn.classList.toggle('active', btn.dataset.pemberlakuanTab === mode);
        });

        // Pemetaan hari hanya relevan untuk mingguan; opsi semester hanya untuk mode semester
        if (wrapHariMingguan) {
            wrapHariMingguan.style.display = (mode === 'mingguan') ? 'block' : 'none';
        }

        const wrapOpsiSemester = document.getElementById('wrapOpsiSemester');
        if (wrapOpsiSemester) {
            wrapOpsiSemester.style.display = (mode === 'semester') ? 'block' : 'none';
        }

        if (labelTanggalAwal) {
            labelTanggalAwal.textContent = mode === 'bulanan' ? 'Tanggal Bulan Berlaku'
                : (mode === 'semester' ? 'Tanggal Awal Semester Berlaku' : 'Tanggal Awal Berlaku');
        }
        if (labelTanggalAkhir) {
            labelTanggalAkhir.textContent = mode === 'bulanan' ? 'Tanggal Akhir Bulan'
                : (mode === 'semester' ? 'Tanggal Akhir Semester Berlaku' : 'Tanggal Akhir Berlaku');
        }

        if (pemberlakuanHint) {
            pemberlakuanHint.innerHTML = mode === 'bulanan'
                ? '<i class="fas fa-calendar-days text-primary me-1"></i> Jadwal dibuat untuk <strong>seluruh bulan</strong> pada hari yang dipilih (Senin–Sabtu).'
                : (mode === 'semester'
                    ? '<i class="fas fa-calendar-range text-primary me-1"></i> Jadwal dibuat untuk <strong>satu semester penuh</strong> (Ganjil Juli–Desember / Genap Januari–Juni) pada hari yang dipilih.'
                    : '<i class="fas fa-calendar-week text-primary me-1"></i> Jadwal dibuat <strong>berulang setiap minggu</strong> pada hari yang dipilih, selama rentang tanggal.');
        }
    }

    document.querySelectorAll('[data-pemberlakuan-tab]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            applyPemberlakuan(this.dataset.pemberlakuanTab);
        });
    });

    // Sinkronkan checkbox hari dengan tanggal awal saat pertama dipilih
    const chkHariList = document.querySelectorAll('.chk-hari-praktik');
    chkHariList.forEach(function (chk) {
        chk.addEventListener('change', function () {
            if (this.checked && inputTanggal && inputTanggal.value) {
                // tidak menimpa tanggal, hanya penanda visual
            }
        });
    });

    // Saat tanggal awal dipilih dan belum ada hari tercentang, centang otomatis
    // sesuai nama hari tanggal tersebut.
    if (inputTanggal) {
        inputTanggal.addEventListener('change', function () {
            if (!this.value) return;

            const adaTercentang = Array.prototype.some.call(chkHariList, function (c) { return c.checked; });
            if (adaTercentang) return;

            const namaHari = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
            const d = new Date(this.value + 'T00:00:00');
            const hari = namaHari[d.getDay()];

            chkHariList.forEach(function (c) {
                if (c.value === hari) c.checked = true;
            });
        });
    }

    if (selectRombel) {
        selectRombel.addEventListener('change', function () {
            loadMapelForRombel(this.value, null, null);
            loadKbmSlotsForRombel(this.value);
        });
    }

    if (selectMapel) {
        selectMapel.addEventListener('change', onMapelSelected);
    }

    // Mode isi manual: menonaktifkan dropdown mapel & mengaktifkan input teks
    if (chkMapelManual) {
        chkMapelManual.addEventListener('change', function () {
            const manual = this.checked;
            if (selectMapel) selectMapel.disabled = manual;
            if (inputMapelManual) {
                inputMapelManual.disabled = !manual;
                inputMapelManual.style.display = manual ? 'block' : 'none';
                if (!manual) inputMapelManual.value = '';
            }
            if (manual) {
                if (inputPembelajaranId) inputPembelajaranId.value = '';
                if (inputMapelId) inputMapelId.value = '';
                if (mapelHint) {
                    mapelHint.innerHTML = '<i class="fas fa-keyboard me-1 text-primary"></i> Mode isi manual aktif.';
                }
            } else {
                onMapelSelected();
                if (mapelHint) {
                    mapelHint.innerHTML = '<i class="fas fa-wand-magic-sparkles me-1 text-primary"></i> Mata pelajaran otomatis dari data kelas.';
                }
            }
        });
    }

    // -------------------------------------------------------------------------
    // 5. Modal Buka / Tutup
    // -------------------------------------------------------------------------
    function defaultTanggal() {
        const now = new Date();
        const tzOffset = now.getTimezoneOffset() * 60000;
        return new Date(now.getTime() - tzOffset).toISOString().split('T')[0];
    }

    function openModalCreate() {
        if (!modalJadwal || !formJadwal) return;

        isEditMode = false;
        formJadwal.reset();
        formJadwal.action = BASE_URL;
        if (jadwalMethod) jadwalMethod.value = 'POST';
        if (modalJadwalTitle) modalJadwalTitle.textContent = 'Jadwalkan Praktik';

        if (inputPembelajaranId) inputPembelajaranId.value = '';
        if (inputMapelId) inputMapelId.value = '';
        if (inputTanggal) inputTanggal.value = defaultTanggal();
        if (inputJamMulai) inputJamMulai.value = '08:00';
        if (inputJamSelesai) inputJamSelesai.value = '09:30';

        if (chkMapelManual) {
            chkMapelManual.checked = false;
            chkMapelManual.dispatchEvent(new Event('change'));
        }

        if (selectJenisLokasi) {
            selectJenisLokasi.value = 'lab';
            filterLokasiByJenis('lab', '');
        }

        if (selectMapel) {
            selectMapel.innerHTML = '<option value="">-- Pilih Kelas Terlebih Dahulu --</option>';
            selectMapel.disabled = true;
        }

        if (selectGuru) selectGuru.value = '';

        applyPemberlakuan('mingguan');
        chkHariList.forEach(function (c) { c.checked = false; });
        if (inputTanggalAkhir) inputTanggalAkhir.value = '';

        document.querySelectorAll('.chk-guru-pendamping').forEach(function (chk) {
            chk.checked = false;
        });

        modalJadwal.style.display = 'flex';
    }

    function openModalEdit(data) {
        if (!modalJadwal || !formJadwal) return;

        isEditMode = true;
        formJadwal.reset();
        formJadwal.action = BASE_URL + '/' + data.id;
        if (jadwalMethod) jadwalMethod.value = 'PUT';
        if (modalJadwalTitle) modalJadwalTitle.textContent = 'Ubah Jadwal Praktik';

        if (selectJenisLokasi) selectJenisLokasi.value = data.jenis || 'lab';
        filterLokasiByJenis(data.jenis || 'lab', data.lokasi || '');

        if (inputTanggal) inputTanggal.value = data.tanggal || defaultTanggal();
        if (inputJamMulai) inputJamMulai.value = data.jamMulai || '08:00';
        if (inputJamSelesai) inputJamSelesai.value = data.jamSelesai || '09:30';
        if (inputTopik) inputTopik.value = data.topik || '';
        if (inputAlat) inputAlat.value = data.alat || '';

        if (selectRombel) selectRombel.value = data.rombelId || '';
        if (selectGuru) selectGuru.value = data.ptkId || '';

        // Saat mengubah jadwal, pemberlakuan dikunci ke sesi tunggal
        // agar tidak menduplikasi rangkaian yang sudah ada.
        applyPemberlakuan('mingguan');
        if (wrapHariMingguan) wrapHariMingguan.style.display = 'none';
        const wrapOpsiSemesterEdit = document.getElementById('wrapOpsiSemester');
        if (wrapOpsiSemesterEdit) wrapOpsiSemesterEdit.style.display = 'none';
        if (pemberlakuanHint) {
            pemberlakuanHint.innerHTML = '<i class="fas fa-pen-to-square text-primary me-1"></i> Mode ubah: hanya sesi ini yang diperbarui.';
        }

        // Checklist guru pendamping
        let ptkList = [];
        if (data.ptkIds) {
            try {
                ptkList = Array.isArray(data.ptkIds) ? data.ptkIds : JSON.parse(data.ptkIds);
            } catch (e) {
                ptkList = [];
            }
        }
        document.querySelectorAll('.chk-guru-pendamping').forEach(function (chk) {
            chk.checked = ptkList.indexOf(chk.value) !== -1 && chk.value !== data.ptkId;
        });

        if (chkMapelManual) {
            chkMapelManual.checked = false;
            chkMapelManual.dispatchEvent(new Event('change'));
        }

        if (inputPembelajaranId) inputPembelajaranId.value = data.pembelajaranId || '';
        if (inputMapelId) inputMapelId.value = '';

        if (data.rombelId) {
            loadMapelForRombel(data.rombelId, data.mapel || '', data.ptkId || '');
        } else if (data.mapel) {
            // Kelas tidak tersimpan (data lama): pakai mode manual agar mapel tetap terisi
            if (chkMapelManual) {
                chkMapelManual.checked = true;
                chkMapelManual.dispatchEvent(new Event('change'));
            }
            if (inputMapelManual) inputMapelManual.value = data.mapel;
        }

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

    // Delegasi tombol ubah jadwal di baris tabel
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
                ptkIds: btnEdit.dataset.ptkIds,
                pemberlakuan: btnEdit.dataset.pemberlakuan,
                guru: btnEdit.dataset.guru,
                topik: btnEdit.dataset.topik,
                tanggal: btnEdit.dataset.tanggal,
                jamMulai: btnEdit.dataset.jamMulai,
                jamSelesai: btnEdit.dataset.jamSelesai,
                alat: btnEdit.dataset.alat,
                status: btnEdit.dataset.status
            });
        }
    });

    // -------------------------------------------------------------------------
    // 6. Submit Form Jadwal (AJAX)
    // -------------------------------------------------------------------------
    if (formJadwal) {
        formJadwal.addEventListener('submit', function (e) {
            e.preventDefault();

            // Validasi klien: kelas wajib, mapel wajib (dropdown atau manual)
            if (selectRombel && !selectRombel.value) {
                notifyError('Data Belum Lengkap', 'Pilih kelas / rombel terlebih dahulu.');
                return;
            }

            const manual = chkMapelManual && chkMapelManual.checked;
            if (manual) {
                if (!inputMapelManual || !inputMapelManual.value.trim()) {
                    notifyError('Data Belum Lengkap', 'Isi nama mata pelajaran.');
                    return;
                }
            } else if (selectMapel && (!selectMapel.value || selectMapel.value === '')) {
                notifyError('Data Belum Lengkap', 'Pilih mata pelajaran, atau aktifkan "Isi manual".');
                return;
            }

            // Pemberlakuan mingguan wajib memilih minimal satu hari praktik
            if (!isEditMode && (inputPemberlakuan && inputPemberlakuan.value === 'mingguan')) {
                const adaHari = Array.prototype.some.call(chkHariList, function (c) { return c.checked; });
                if (!adaHari) {
                    notifyError('Data Belum Lengkap', 'Pilih minimal satu hari praktik untuk jadwal mingguan.');
                    return;
                }
            }

            const submitBtn = formJadwal.querySelector('button[type="submit"]');
            const originalHtml = submitBtn ? submitBtn.innerHTML : '';
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Menyimpan...';
            }

            const formData = new FormData(formJadwal);

            // Pastikan nama mapel terkirim baik dari dropdown maupun input manual
            if (manual) {
                formData.set('mata_pelajaran', (inputMapelManual.value || '').trim());
                formData.delete('pembelajaran_id');
                formData.delete('mata_pelajaran_id');
            } else if (selectMapel && selectRombel) {
                const items = mapelCache[selectRombel.value] || [];
                const item = items[Number(selectMapel.value)];
                if (item) {
                    formData.set('mata_pelajaran', item.nama_mata_pelajaran || '');
                    formData.set('pembelajaran_id', item.pembelajaran_id || '');
                    formData.set('mata_pelajaran_id', item.mata_pelajaran_id || '');
                }
            }

            fetch(formJadwal.action, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                credentials: 'same-origin',
                body: formData
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
    // 7. Modal Update Status & Laporan Kerusakan
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
    // 8. Hapus Jadwal
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
    // 9. Pemberlakuan Jadwal (Aktifkan Mode Mingguan / Semester)
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
