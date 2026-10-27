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
