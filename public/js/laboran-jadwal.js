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
    // 2. Referensi Elemen Modal (Form Simpel)
    // -------------------------------------------------------------------------
    const modalJadwal = document.getElementById('modalJadwal');
    const btnOpenModalJadwal = document.getElementById('btnOpenModalJadwal');
    const btnCloseModalJadwal = document.getElementById('btnCloseModalJadwal');
    const btnCancelModalJadwal = document.getElementById('btnCancelModalJadwal');
    const modalJadwalTitle = document.getElementById('modalJadwalTitle');
    const jadwalMethod = document.getElementById('jadwalMethod');
    const formJadwal = document.getElementById('formJadwal');
    const selectLokasi = document.getElementById('selectLokasi');
    const selectGuruMapel = document.getElementById('selectGuruMapel');
    const guruMapelHint = document.getElementById('guruMapelHint');
    const inputPembelajaranId = document.getElementById('inputPembelajaranId');
    const inputRombelId = document.getElementById('inputRombelId');
    const inputMapelId = document.getElementById('inputMapelId');
    const inputJenisLokasi = document.getElementById('inputJenisLokasi');
    const lokasiTerpilihInfo = document.getElementById('lokasiTerpilihInfo');
    const lokasiTerpilihNama = document.getElementById('lokasiTerpilihNama');
    const inputJamMulai = document.getElementById('inputJamMulai');
    const inputJamSelesai = document.getElementById('inputJamSelesai');

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
    // 4. Dropdown Gabungan Guru & Mata Pelajaran (dari data pembelajaran)
    // -------------------------------------------------------------------------
    function setGuruMapelOptions(items) {
        if (!selectGuruMapel) return;

        selectGuruMapel.innerHTML = '';

        if (!items || items.length === 0) {
            const opt = document.createElement('option');
            opt.value = '';
            opt.textContent = '-- Belum ada data pembelajaran (guru & mapel) --';
            selectGuruMapel.appendChild(opt);
            selectGuruMapel.disabled = true;
            if (guruMapelHint) {
                guruMapelHint.innerHTML = '<i class="fas fa-triangle-exclamation me-1" style="color: var(--warning, #f59e0b);"></i>' +
                    'Belum ada data pembelajaran. Sinkronkan data Dapodik terlebih dahulu.';
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
            opt.textContent = item.label;
            selectGuruMapel.appendChild(opt);
        });

        selectGuruMapel.disabled = false;
        if (guruMapelHint) {
            guruMapelHint.innerHTML = '<i class="fas fa-circle-check me-1" style="color: var(--success, #10b981);"></i>' +
                items.length + ' pilihan guru & mata pelajaran tersedia dari data kelas.';
        }
    }

    function loadGuruMapel(preselectPembelajaranId) {
        if (!selectGuruMapel) return;

        if (guruMapelCache) {
            applyGuruMapel(guruMapelCache, preselectPembelajaranId);
            return;
        }

        selectGuruMapel.innerHTML = '<option value="">Memuat daftar guru & mapelâ€¦</option>';
        selectGuruMapel.disabled = true;
        if (guruMapelHint) {
            guruMapelHint.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Memuat data pembelajaranâ€¦';
        }

        fetch(GURU_MAPEL_URL, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            credentials: 'same-origin'
        })
            .then(function (res) {
                if (!res.ok) throw new Error('Gagal memuat daftar guru & mapel.');
                return res.json();
            })
            .then(function (data) {
                guruMapelCache = (data && data.guru_mapel) || [];
                applyGuruMapel(guruMapelCache, preselectPembelajaranId);
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
        const items = guruMapelCache || [];
        const item = idx !== '' ? items[Number(idx)] : null;

        if (!item) {
            if (inputPembelajaranId) inputPembelajaranId.value = '';
            if (inputRombelId) inputRombelId.value = '';
            if (inputMapelId) inputMapelId.value = '';
            return;
        }

        if (inputPembelajaranId) inputPembelajaranId.value = item.pembelajaran_id || '';
        if (inputRombelId) inputRombelId.value = item.rombel_id || '';
        if (inputMapelId) inputMapelId.value = item.mata_pelajaran_id || '';
    }

    if (selectGuruMapel) {
        selectGuruMapel.addEventListener('change', onGuruMapelSelected);
    }

    // -------------------------------------------------------------------------
    // 5. Lokasi Praktik: sinkron jenis + tampil nama terpilih
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
    // 5b. Quick Slot dari Matriks Grid (prefill lokasi + jam)
    // -------------------------------------------------------------------------
    document.addEventListener('click', function (e) {
        const btnSlot = e.target.closest('.btn-quick-slot');
        if (!btnSlot || !modalJadwal) return;

        openModalCreate();

        if (btnSlot.dataset.lokasi) {
            selectLokasi.value = btnSlot.dataset.lokasi;
            selectLokasi.dispatchEvent(new Event('change'));
        }

        if (inputJamMulai && btnSlot.dataset.jamMulai) inputJamMulai.value = btnSlot.dataset.jamMulai;
        if (inputJamSelesai && btnSlot.dataset.jamSelesai) inputJamSelesai.value = btnSlot.dataset.jamSelesai;

        if (guruMapelHint) {
            guruMapelHint.innerHTML = '<i class="fas fa-wand-magic-sparkles text-primary me-1"></i> Slot jam ' +
                (btnSlot.dataset.jamMulai || '') + '-' + (btnSlot.dataset.jamSelesai || '') +
                ' pada ' + (btnSlot.dataset.lokasi || '') + ' dipilih dari matriks.';
        }
    });

    // -------------------------------------------------------------------------
    // 6. Modal Buka / Tutup
    // -------------------------------------------------------------------------
    function openModalCreate() {
        if (!modalJadwal || !formJadwal) return;

        isEditMode = false;
        formJadwal.reset();
        formJadwal.action = BASE_URL;
        if (jadwalMethod) jadwalMethod.value = 'POST';
        if (modalJadwalTitle) modalJadwalTitle.textContent = 'Jadwalkan Praktik';

        if (inputPembelajaranId) inputPembelajaranId.value = '';
        if (inputRombelId) inputRombelId.value = '';
        if (inputMapelId) inputMapelId.value = '';
        if (inputJamMulai) inputJamMulai.value = '08:00';
        if (inputJamSelesai) inputJamSelesai.value = '09:30';

        if (lokasiTerpilihInfo) lokasiTerpilihInfo.style.display = 'none';

        document.querySelectorAll('.chk-guru-pendamping').forEach(function (chk) {
            chk.checked = false;
        });

        loadGuruMapel(null);

        modalJadwal.style.display = 'flex';
    }

    function openModalEdit(data) {
        if (!modalJadwal || !formJadwal) return;

        isEditMode = true;
        formJadwal.reset();
        formJadwal.action = BASE_URL + '/' + data.id;
        if (jadwalMethod) jadwalMethod.value = 'PUT';
        if (modalJadwalTitle) modalJadwalTitle.textContent = 'Ubah Jadwal Praktik';

        if (inputJamMulai) inputJamMulai.value = data.jamMulai || '08:00';
        if (inputJamSelesai) inputJamSelesai.value = data.jamSelesai || '09:30';

        if (inputPembelajaranId) inputPembelajaranId.value = data.pembelajaranId || '';
        if (inputRombelId) inputRombelId.value = data.rombelId || '';
        if (inputMapelId) inputMapelId.value = data.mapelId || '';

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

        // Muat dropdown lalu preselect sesuai pembelajaran tersimpan.
        // Bila pembelajaran tidak ada (data lama), pilih guru+mapel terdekat.
        loadGuruMapel(data.pembelajaranId || null);
        if (!data.pembelajaranId && guruMapelCache) {
            const idx = guruMapelCache.findIndex(function (it) {
                return it.mata_pelajaran === (data.mapel || '') && (!data.ptkId || it.ptk_id === data.ptkId);
            });
            if (idx !== -1) {
                selectGuruMapel.value = String(idx);
                onGuruMapelSelected();
            }
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
                mapelId: btnEdit.dataset.mapelId,
                ptkId: btnEdit.dataset.ptkId,
                ptkIds: btnEdit.dataset.ptkIds,
                guru: btnEdit.dataset.guru,
                tanggal: btnEdit.dataset.tanggal,
                jamMulai: btnEdit.dataset.jamMulai,
                jamSelesai: btnEdit.dataset.jamSelesai,
                status: btnEdit.dataset.status
            });
        }
    });

    // -------------------------------------------------------------------------
    // 6b. Submit Form Jadwal (AJAX)
    // -------------------------------------------------------------------------
    if (formJadwal) {
        formJadwal.addEventListener('submit', function (e) {
            e.preventDefault();

            if (selectLokasi && !selectLokasi.value) {
                notifyError('Data Belum Lengkap', 'Pilih lokasi / tempat praktik terlebih dahulu.');
                return;
            }

            if (selectGuruMapel && !selectGuruMapel.value) {
                notifyError('Data Belum Lengkap', 'Pilih guru pengampu & mata pelajaran.');
                return;
            }

            if (inputJamMulai && inputJamSelesai && inputJamSelesai.value <= inputJamMulai.value) {
                notifyError('Data Belum Lengkap', 'Jam selesai harus lebih besar dari jam mulai.');
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
