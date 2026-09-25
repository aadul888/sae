/**
 * SAE - Sistem Presensi Peserta Didik
 * Logic for Class & Subject Attendance (Guru Mapel & Wali Kelas)
 */

document.addEventListener('DOMContentLoaded', function () {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const modalIzinSakit = document.getElementById('modalIzinSakit');
    const formIzinSakit = document.getElementById('formIzinSakit');
    const inputIzinPdId = document.getElementById('izinPdId');
    const inputIzinStatus = document.getElementById('izinStatusVal');
    const inputIzinKeterangan = document.getElementById('izinKeteranganInput');
    const textIzinPdNama = document.getElementById('izinPdNama');
    const titleModalIzin = document.getElementById('titleModalIzin');
    const btnCloseIzinModal = document.getElementById('btnCloseIzinModal');
    const btnCancelIzinModal = document.getElementById('btnCancelIzinModal');

    function getContextData() {
        const tanggal = document.getElementById('kelasTanggalInput')?.value || new Date().toISOString().slice(0, 10);
        const pembelajaranId = document.getElementById('kelasPembelajaranSelect')?.value || '';
        const rombelId = document.querySelector('select[name="rombel_id"]')?.value || '';
        const jamKe = document.getElementById('kelasJamKeInput')?.value || '';
        return { tanggal, pembelajaranId, rombelId, jamKe };
    }

    // 1. Quick Change Attendance Status Toggle (Event Delegation)
    document.addEventListener('click', async function (e) {
        const btn = e.target.closest('.btn-status-toggle');
        if (!btn) return;

        const pdId = btn.getAttribute('data-id');
        const targetStatus = btn.getAttribute('data-status');
        const namaSiswa = btn.getAttribute('data-nama');
        const ctx = getContextData();

        if (!pdId || !targetStatus) return;

        const isActive = btn.classList.contains('active-' + targetStatus);
        const finalStatus = isActive ? 'reset' : targetStatus;

        // Jika status Izin (I) atau Sakit (S) dan belum aktif, tampilkan modal input alasan
        if ((targetStatus === 'I' || targetStatus === 'S') && !isActive) {
            if (inputIzinPdId) inputIzinPdId.value = pdId;
            if (inputIzinStatus) inputIzinStatus.value = targetStatus;
            if (textIzinPdNama) textIzinPdNama.textContent = namaSiswa;
            if (inputIzinKeterangan) inputIzinKeterangan.value = '';
            if (titleModalIzin) {
                titleModalIzin.textContent = targetStatus === 'S'
                    ? 'Pencatatan Sakit pada Jam Mapel'
                    : 'Pencatatan Izin pada Jam Mapel';
            }
            if (modalIzinSakit) modalIzinSakit.style.display = 'flex';
            return;
        }

        // Untuk status H, T, A atau reset/toggle-off langsung eksekusi update cepat
        try {
            const response = await fetch('/dashboard/presensi/kelas/status', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    peserta_didik_id: pdId,
                    tanggal: ctx.tanggal,
                    status: finalStatus,
                    pembelajaran_id: ctx.pembelajaranId,
                    rombongan_belajar_id: ctx.rombelId,
                    jam_ke: ctx.jamKe
                })
            });

            const res = await response.json();

            if (response.ok && res.status === 'success') {
                // Update tampilan baris siswa
                const row = document.getElementById('row-siswa-' + pdId);
                if (row) {
                    row.querySelectorAll('.btn-status-toggle').forEach(b => {
                        b.className = 'btn-status-toggle';
                    });
                    if (finalStatus !== 'reset') {
                        btn.className = `btn-status-toggle active-${targetStatus}`;
                    }

                    const badgeCell = document.getElementById('badge-status-' + pdId);
                    if (badgeCell) badgeCell.innerHTML = res.badge;
                }

                Swal.fire({
                    icon: 'success',
                    title: finalStatus === 'reset' ? 'Presensi Dibatalkan' : 'Status Mapel Disimpan',
                    text: res.message,
                    timer: 1200,
                    showConfirmButton: false,
                    toast: true,
                    position: 'top-end'
                });
            } else {
                Swal.fire('Gagal', res.message || 'Gagal mengubah status mapel.', 'error');
            }
        } catch (err) {
            Swal.fire('Error', 'Terjadi kesalahan koneksi.', 'error');
        }
    });

    function closeIzinModal() {
        if (modalIzinSakit) modalIzinSakit.style.display = 'none';
        if (formIzinSakit) formIzinSakit.reset();
    }

    if (btnCloseIzinModal) btnCloseIzinModal.addEventListener('click', closeIzinModal);
    if (btnCancelIzinModal) btnCancelIzinModal.addEventListener('click', closeIzinModal);

    // 2. Form Submit Izin / Sakit Mapel
    if (formIzinSakit) {
        formIzinSakit.addEventListener('submit', async function (e) {
            e.preventDefault();

            const pdId = inputIzinPdId.value;
            const targetStatus = inputIzinStatus.value;
            const keterangan = inputIzinKeterangan?.value || '';
            const ctx = getContextData();

            try {
                Swal.fire({
                    title: 'Menyimpan...',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });

                const response = await fetch('/dashboard/presensi/kelas/status', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        peserta_didik_id: pdId,
                        tanggal: ctx.tanggal,
                        status: targetStatus,
                        pembelajaran_id: ctx.pembelajaranId,
                        rombongan_belajar_id: ctx.rombelId,
                        jam_ke: ctx.jamKe,
                        keterangan: keterangan
                    })
                });

                const res = await response.json();

                if (response.ok && res.status === 'success') {
                    closeIzinModal();

                    // Update UI baris
                    const row = document.getElementById('row-siswa-' + pdId);
                    if (row) {
                        row.querySelectorAll('.btn-status-toggle').forEach(b => {
                            if (b.getAttribute('data-status') === targetStatus) {
                                b.className = `btn-status-toggle active-${targetStatus}`;
                            } else {
                                b.className = 'btn-status-toggle';
                            }
                        });

                        const badgeCell = document.getElementById('badge-status-' + pdId);
                        if (badgeCell) badgeCell.innerHTML = res.badge;

                        const ketCell = document.getElementById('ket-status-' + pdId);
                        if (ketCell) ketCell.textContent = keterangan || '-';
                    }

                    Swal.fire({
                        icon: 'success',
                        title: 'Tersimpan!',
                        text: res.message,
                        timer: 1300,
                        showConfirmButton: false,
                        toast: true,
                        position: 'top-end'
                    });
                } else {
                    Swal.fire('Gagal', res.message || 'Gagal menyimpan.', 'error');
                }
            } catch (err) {
                Swal.fire('Error', 'Gagal memproses data.', 'error');
            }
        });
    }

    // 3. Tombol Hadir Semua di Mapel
    const btnHadirSemua = document.getElementById('btnHadirSemua') || document.getElementById('btnTandaiAlpha');
    if (btnHadirSemua) {
        btnHadirSemua.addEventListener('click', function () {
            const rombelId = this.getAttribute('data-rombel');
            const rombelNama = this.getAttribute('data-rombel-nama');
            const ctx = getContextData();

            Swal.fire({
                title: 'Tandai Hadir Semua?',
                text: `Tandai seluruh peserta didik di kelas ${rombelNama} sebagai Hadir (H) pada mata pelajaran ini?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#10b981',
                cancelButtonColor: '#64748b',
                confirmButtonText: '<i class="fas fa-check-double me-1"></i> Ya, Hadir Semua',
                cancelButtonText: 'Batal'
            }).then(async (result) => {
                if (result.isConfirmed) {
                    try {
                        Swal.fire({
                            title: 'Memproses Presensi...',
                            allowOutsideClick: false,
                            didOpen: () => Swal.showLoading()
                        });

                        const response = await fetch('/dashboard/presensi/kelas/hadir-semua', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                rombongan_belajar_id: rombelId,
                                tanggal: ctx.tanggal,
                                pembelajaran_id: ctx.pembelajaranId,
                                jam_ke: ctx.jamKe
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
                            }).then(() => window.location.reload());
                        } else {
                            Swal.fire('Gagal', res.message || 'Gagal memproses.', 'error');
                        }
                    } catch (e) {
                        Swal.fire('Error', 'Terjadi kesalahan sistem.', 'error');
                    }
                }
            });
        });
    }

    // 4. Tombol Reset / Kosongkan Presensi Kelas
    const btnResetPresensiKelas = document.getElementById('btnResetPresensiKelas');
    if (btnResetPresensiKelas) {
        btnResetPresensiKelas.addEventListener('click', function () {
            const rombelId = this.getAttribute('data-rombel');
            const rombelNama = this.getAttribute('data-rombel-nama');
            const ctx = getContextData();

            Swal.fire({
                title: 'Reset Presensi Kelas?',
                text: `Seluruh catatan presensi siswa kelas ${rombelNama} pada mata pelajaran ini akan dikosongkan kembali agar dapat diulang.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#64748b',
                confirmButtonText: '<i class="fas fa-rotate-left me-1"></i> Ya, Reset Presensi',
                cancelButtonText: 'Batal'
            }).then(async (result) => {
                if (result.isConfirmed) {
                    try {
                        Swal.fire({
                            title: 'Mereset Presensi...',
                            allowOutsideClick: false,
                            didOpen: () => Swal.showLoading()
                        });

                        const response = await fetch('/dashboard/presensi/kelas/reset', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                rombongan_belajar_id: rombelId,
                                tanggal: ctx.tanggal,
                                pembelajaran_id: ctx.pembelajaranId
                            })
                        });

                        const res = await response.json();

                        if (response.ok && res.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Presensi Dikosongkan!',
                                text: res.message,
                                timer: 1500,
                                showConfirmButton: false
                            }).then(() => window.location.reload());
                        } else {
                            Swal.fire('Gagal', res.message || 'Gagal mereset presensi.', 'error');
                        }
                    } catch (e) {
                        Swal.fire('Error', 'Terjadi kesalahan sistem.', 'error');
                    }
                }
            });
        });
    }

    // 5. Live Search Filter Siswa di Kelas (Client-Side Instant Search)
    const liveSearchKelasInput = document.getElementById('liveSearchKelasInput');
    const clearSearchKelasBtn  = document.getElementById('clearSearchKelasBtn');

    if (liveSearchKelasInput) {
        liveSearchKelasInput.addEventListener('input', function () {
            const q = this.value.trim().toLowerCase();
            if (clearSearchKelasBtn) {
                clearSearchKelasBtn.style.display = q ? 'block' : 'none';
            }

            document.querySelectorAll('.row-siswa-item').forEach(row => {
                const nama = row.querySelector('.text-nama-siswa')?.textContent.toLowerCase() || '';
                const nisn = row.querySelector('.text-nisn-siswa')?.textContent.toLowerCase() || '';
                if (!q || nama.includes(q) || nisn.includes(q)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }

    if (clearSearchKelasBtn) {
        clearSearchKelasBtn.addEventListener('click', function () {
            if (liveSearchKelasInput) {
                liveSearchKelasInput.value = '';
                liveSearchKelasInput.dispatchEvent(new Event('input'));
                liveSearchKelasInput.focus();
            }
        });
    }
});
