/**
 * JavaScript Modul Jadwal & Penggunaan Laboratorium
 * Standar Baku SAE: Vanilla JS, Modal Handling (z-index: 99999), SweetAlert2
 */

document.addEventListener('DOMContentLoaded', function () {
    // -------------------------------------------------------------------------
    // 1. Modal Tambah Jadwal Praktik
    // -------------------------------------------------------------------------
    const modalJadwal = document.getElementById('modalJadwal');
    const btnOpenModalJadwal = document.getElementById('btnOpenModalJadwal');
    const btnCloseModalJadwal = document.getElementById('btnCloseModalJadwal');
    const btnCancelModalJadwal = document.getElementById('btnCancelModalJadwal');
    const formJadwal = document.getElementById('formJadwal');

    function openModalJadwal() {
        if (!modalJadwal) return;
        if (formJadwal) {
            formJadwal.reset();
            const dateInput = formJadwal.querySelector('input[name="tanggal"]');
            if (dateInput && !dateInput.value) {
                const today = new Date().toISOString().split('T')[0];
                dateInput.value = today;
            }
        }
        modalJadwal.style.display = 'flex';
    }

    function closeModalJadwal() {
        if (!modalJadwal) return;
        modalJadwal.style.display = 'none';
    }

    window.openModalJadwal = openModalJadwal;
    window.closeModalJadwal = closeModalJadwal;

    if (btnOpenModalJadwal) {
        btnOpenModalJadwal.addEventListener('click', openModalJadwal);
    }
    if (btnCloseModalJadwal) {
        btnCloseModalJadwal.addEventListener('click', closeModalJadwal);
    }
    if (btnCancelModalJadwal) {
        btnCancelModalJadwal.addEventListener('click', closeModalJadwal);
    }
    if (modalJadwal) {
        modalJadwal.addEventListener('click', function (e) {
            if (e.target === modalJadwal) {
                closeModalJadwal();
            }
        });
    }

    // Submit Form Tambah Jadwal via AJAX
    if (formJadwal) {
        formJadwal.addEventListener('submit', function (e) {
            e.preventDefault();

            const submitBtn = formJadwal.querySelector('button[type="submit"]');
            const originalBtnHtml = submitBtn ? submitBtn.innerHTML : '';
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Menyimpan...';
            }

            const formData = new FormData(formJadwal);

            fetch(formJadwal.action, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: formData
            })
            .then(async function (res) {
                const data = await res.json().catch(function () { return {}; });
                if (!res.ok) {
                    let msg = data.message || 'Terjadi kesalahan saat menyimpan jadwal.';
                    if (data.errors) {
                        const firstErr = Object.values(data.errors)[0];
                        if (Array.isArray(firstErr) && firstErr.length > 0) {
                            msg = firstErr[0];
                        }
                    }
                    throw new Error(msg);
                }
                return data;
            })
            .then(function (res) {
                closeModalJadwal();
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil',
                        text: res.message || 'Jadwal penggunaan lab berhasil disimpan.',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(function () {
                        window.location.reload();
                    });
                } else {
                    window.location.reload();
                }
            })
            .catch(function (err) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal Menyimpan',
                        text: err.message || 'Terjadi kesalahan pada sistem.'
                    });
                } else {
                    alert(err.message || 'Terjadi kesalahan pada sistem.');
                }
            })
            .finally(function () {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnHtml;
                }
            });
        });
    }

    // -------------------------------------------------------------------------
    // 2. Modal Update Status & Laporan Kerusakan
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

    if (btnCloseModalStatus) {
        btnCloseModalStatus.addEventListener('click', closeModalStatus);
    }
    if (btnCancelModalStatus) {
        btnCancelModalStatus.addEventListener('click', closeModalStatus);
    }
    if (modalStatus) {
        modalStatus.addEventListener('click', function (e) {
            if (e.target === modalStatus) {
                closeModalStatus();
            }
        });
    }

    // Event Delegation: Tombol Update Status Sesi di Baris Tabel
    document.addEventListener('click', function (e) {
        const btnStatus = e.target.closest('.btn-status-lab');
        if (btnStatus && modalStatus && formStatus) {
            const id = btnStatus.dataset.id;
            const mapel = btnStatus.dataset.mapel || '';
            const status = btnStatus.dataset.status || 'dijadwalkan';
            const laporan = btnStatus.dataset.laporan || '';

            formStatus.action = `/dashboard/laboran/jadwal/${id}`;
            if (statusDesc) {
                statusDesc.textContent = `Memperbarui status sesi: ${mapel}`;
            }
            if (selectStatusLab) {
                selectStatusLab.value = status;
            }
            if (inputLaporanKerusakan) {
                inputLaporanKerusakan.value = laporan;
            }

            modalStatus.style.display = 'flex';
        }
    });

    // Submit Form Status via AJAX
    if (formStatus) {
        formStatus.addEventListener('submit', function (e) {
            e.preventDefault();

            const submitBtn = formStatus.querySelector('button[type="submit"]');
            const originalBtnHtml = submitBtn ? submitBtn.innerHTML : '';
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Menyimpan...';
            }

            const formData = new FormData(formStatus);

            fetch(formStatus.action, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: formData
            })
            .then(async function (res) {
                const data = await res.json().catch(function () { return {}; });
                if (!res.ok) {
                    let msg = data.message || 'Terjadi kesalahan saat memperbarui status.';
                    if (data.errors) {
                        const firstErr = Object.values(data.errors)[0];
                        if (Array.isArray(firstErr) && firstErr.length > 0) {
                            msg = firstErr[0];
                        }
                    }
                    throw new Error(msg);
                }
                return data;
            })
            .then(function (res) {
                closeModalStatus();
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil',
                        text: res.message || 'Status penggunaan lab berhasil diperbarui.',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(function () {
                        window.location.reload();
                    });
                } else {
                    window.location.reload();
                }
            })
            .catch(function (err) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal Memperbarui',
                        text: err.message || 'Terjadi kesalahan pada sistem.'
                    });
                } else {
                    alert(err.message || 'Terjadi kesalahan pada sistem.');
                }
            })
            .finally(function () {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnHtml;
                }
            });
        });
    }

    // -------------------------------------------------------------------------
    // 3. Hapus Jadwal Lab dengan Konfirmasi SweetAlert2
    // -------------------------------------------------------------------------
    document.addEventListener('click', function (e) {
        const btnDelete = e.target.closest('.btn-delete-jadwal');
        if (btnDelete) {
            const id = btnDelete.dataset.id;
            const mapel = btnDelete.dataset.mapel || 'sesi ini';

            const doDelete = function () {
                const csrfMeta = document.querySelector('meta[name="csrf-token"]');
                const csrfInput = document.querySelector('input[name="_token"]');
                const csrfToken = (csrfMeta ? csrfMeta.getAttribute('content') : '') || (csrfInput ? csrfInput.value : '');

                fetch(`/dashboard/laboran/jadwal/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                })
                .then(async function (res) {
                    const data = await res.json().catch(function () { return {}; });
                    if (!res.ok) {
                        throw new Error(data.message || 'Gagal menghapus jadwal.');
                    }
                    return data;
                })
                .then(function (res) {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Terhapus!',
                            text: res.message || 'Jadwal berhasil dihapus.',
                            timer: 1500,
                            showConfirmButton: false
                        }).then(function () {
                            window.location.reload();
                        });
                    } else {
                        window.location.reload();
                    }
                })
                .catch(function (err) {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal Menghapus',
                            text: err.message || 'Terjadi kesalahan sistem.'
                        });
                    } else {
                        alert(err.message || 'Terjadi kesalahan sistem.');
                    }
                });
            };

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Hapus Jadwal Lab?',
                    text: `Apakah Anda yakin ingin menghapus jadwal untuk "${mapel}"?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ef4444',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, Hapus!',
                    cancelButtonText: 'Batal'
                }).then(function (result) {
                    if (result.isConfirmed) {
                        doDelete();
                    }
                });
            } else {
                if (confirm(`Apakah Anda yakin ingin menghapus jadwal untuk "${mapel}"?`)) {
                    doDelete();
                }
            }
        }
    });
});
