/**
 * SAE - Sistem Presensi Peserta Didik
 * Logic for Class Attendance (Wali Kelas & Guru)
 */

document.addEventListener('DOMContentLoaded', function () {
    // 1. Quick Change Attendance Status
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const modalIzinSakit = document.getElementById('modalIzinSakit');
    const formIzinSakit = document.getElementById('formIzinSakit');
    const inputIzinPdId = document.getElementById('izinPdId');
    const inputIzinStatus = document.getElementById('izinStatusVal');
    const textIzinPdNama = document.getElementById('izinPdNama');
    const titleModalIzin = document.getElementById('titleModalIzin');
    const btnCloseIzinModal = document.getElementById('btnCloseIzinModal');
    const btnCancelIzinModal = document.getElementById('btnCancelIzinModal');

    document.querySelectorAll('.btn-status-toggle').forEach(btn => {
        btn.addEventListener('click', async function () {
            const pdId = this.getAttribute('data-id');
            const targetStatus = this.getAttribute('data-status');
            const tanggal = document.getElementById('kelasTanggalInput')?.value || new Date().toISOString().slice(0, 10);
            const namaSiswa = this.getAttribute('data-nama');

            // Jika status Izin (I) atau Sakit (S), tampilkan modal input alasan & lampiran surat
            if (targetStatus === 'I' || targetStatus === 'S') {
                if (inputIzinPdId) inputIzinPdId.value = pdId;
                if (inputIzinStatus) inputIzinStatus.value = targetStatus;
                if (textIzinPdNama) textIzinPdNama.textContent = namaSiswa;
                if (titleModalIzin) {
                    titleModalIzin.textContent = targetStatus === 'S'
                        ? 'Pencatatan Sakit & Surat Dokter'
                        : 'Pencatatan Izin & Surat Permohonan';
                }
                if (modalIzinSakit) modalIzinSakit.style.display = 'flex';
                return;
            }

            // Untuk status H, T, A, D langsung eksekusi update cepat
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
                        tanggal: tanggal,
                        status: targetStatus
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
                        this.className = `btn-status-toggle active-${targetStatus}`;

                        const badgeCell = document.getElementById('badge-status-' + pdId);
                        if (badgeCell) badgeCell.innerHTML = res.badge;
                    }

                    Swal.fire({
                        icon: 'success',
                        title: 'Status Disimpan',
                        text: res.message,
                        timer: 1200,
                        showConfirmButton: false,
                        toast: true,
                        position: 'top-end'
                    });
                } else {
                    Swal.fire('Gagal', res.message || 'Gagal mengubah status.', 'error');
                }
            } catch (err) {
                Swal.fire('Error', 'Terjadi kesalahan koneksi.', 'error');
            }
        });
    });

    function closeIzinModal() {
        if (modalIzinSakit) modalIzinSakit.style.display = 'none';
        if (formIzinSakit) formIzinSakit.reset();
    }

    if (btnCloseIzinModal) btnCloseIzinModal.addEventListener('click', closeIzinModal);
    if (btnCancelIzinModal) btnCancelIzinModal.addEventListener('click', closeIzinModal);

    // 2. Form Submit Izin / Sakit dengan Lampiran Surat
    if (formIzinSakit) {
        formIzinSakit.addEventListener('submit', async function (e) {
            e.preventDefault();

            const pdId = inputIzinPdId.value;
            const targetStatus = inputIzinStatus.value;
            const tanggal = document.getElementById('kelasTanggalInput')?.value || new Date().toISOString().slice(0, 10);

            const formData = new FormData(this);
            formData.append('peserta_didik_id', pdId);
            formData.append('tanggal', tanggal);
            formData.append('status', targetStatus);

            try {
                Swal.fire({
                    title: 'Menyimpan...',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });

                const response = await fetch('/dashboard/presensi/kelas/status', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: formData
                });

                const res = await response.json();

                if (response.ok && res.status === 'success') {
                    closeIzinModal();
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: res.message,
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => window.location.reload());
                } else {
                    Swal.fire('Gagal', res.message || 'Gagal menyimpan.', 'error');
                }
            } catch (err) {
                Swal.fire('Error', 'Gagal memproses data.', 'error');
            }
        });
    }

    // 3. Tombol Tandai Sisa Siswa sebagai Alpha
    const btnTandaiAlpha = document.getElementById('btnTandaiAlpha');
    if (btnTandaiAlpha) {
        btnTandaiAlpha.addEventListener('click', function () {
            const rombelId = this.getAttribute('data-rombel');
            const rombelNama = this.getAttribute('data-rombel-nama');
            const tanggal = document.getElementById('kelasTanggalInput')?.value || new Date().toISOString().slice(0, 10);

            Swal.fire({
                title: 'Tandai Sisa sebagai Alpha?',
                text: `Seluruh siswa di kelas ${rombelNama} yang belum memiliki data presensi pada tanggal ${tanggal} akan otomatis dicatat sebagai Alpha (Tanpa Keterangan).`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#64748b',
                confirmButtonText: '<i class="fas fa-check"></i> Ya, Tandai Alpha',
                cancelButtonText: 'Batal'
            }).then(async (result) => {
                if (result.isConfirmed) {
                    try {
                        Swal.fire({
                            title: 'Memproses...',
                            allowOutsideClick: false,
                            didOpen: () => Swal.showLoading()
                        });

                        const response = await fetch('/dashboard/presensi/kelas/auto-alpha', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                rombongan_belajar_id: rombelId,
                                tanggal: tanggal
                            })
                        });

                        const res = await response.json();

                        if (response.ok && res.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil!',
                                text: res.message,
                                timer: 1600,
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

    // 4. Modal Preview Surat / Lampiran
    const modalLampiran = document.getElementById('modalPreviewLampiran');
    const frameLampiran = document.getElementById('frameLampiran');
    const imgLampiran = document.getElementById('imgLampiran');
    const btnCloseLampiranModal = document.getElementById('btnCloseLampiranModal');

    document.querySelectorAll('.btn-preview-lampiran').forEach(btn => {
        btn.addEventListener('click', function () {
            const url = this.getAttribute('data-url');
            const isPdf = url.toLowerCase().endsWith('.pdf');

            if (modalLampiran) {
                if (isPdf) {
                    if (frameLampiran) {
                        frameLampiran.src = url;
                        frameLampiran.style.display = 'block';
                    }
                    if (imgLampiran) imgLampiran.style.display = 'none';
                } else {
                    if (imgLampiran) {
                        imgLampiran.src = url;
                        imgLampiran.style.display = 'block';
                    }
                    if (frameLampiran) frameLampiran.style.display = 'none';
                }
                modalLampiran.style.display = 'flex';
            }
        });
    });

    if (btnCloseLampiranModal) {
        btnCloseLampiranModal.addEventListener('click', () => {
            if (modalLampiran) modalLampiran.style.display = 'none';
            if (frameLampiran) frameLampiran.src = '';
            if (imgLampiran) imgLampiran.src = '';
        });
    }
});
