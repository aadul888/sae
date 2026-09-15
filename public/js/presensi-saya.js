/**
 * SAE - Sistem Presensi Peserta Didik
 * Logic for Student Portal (Riwayat Saya & E-Izin)
 */

document.addEventListener('DOMContentLoaded', function () {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    // 1. Dynamic QR Code Anti-Screenshot Countdown
    let timeLeft = 60;
    const countdownEl = document.getElementById('qrCountdownText');
    const progressBar = document.getElementById('qrProgressBar');

    if (countdownEl && progressBar) {
        setInterval(() => {
            timeLeft--;
            if (timeLeft <= 0) {
                timeLeft = 60;
                // Refresh halaman untuk token baru atau reload data
                window.location.reload();
            }
            countdownEl.textContent = `${timeLeft}d`;
            const pct = ((60 - timeLeft) / 60) * 100;
            progressBar.style.width = pct + '%';
        }, 1000);
    }

    // 2. Modal Pengajuan E-Izin / Sakit
    const modalPengajuanIzin = document.getElementById('modalPengajuanIzin');
    const formPengajuanIzin = document.getElementById('formPengajuanIzin');
    const btnBukaModalIzin = document.getElementById('btnBukaModalIzin');
    const btnCloseModalIzin = document.getElementById('btnCloseModalIzin');
    const btnCancelModalIzin = document.getElementById('btnCancelModalIzin');

    if (btnBukaModalIzin && modalPengajuanIzin) {
        btnBukaModalIzin.addEventListener('click', () => {
            modalPengajuanIzin.style.display = 'flex';
        });
    }

    function closePengajuanModal() {
        if (modalPengajuanIzin) modalPengajuanIzin.style.display = 'none';
        if (formPengajuanIzin) formPengajuanIzin.reset();
    }

    if (btnCloseModalIzin) btnCloseModalIzin.addEventListener('click', closePengajuanModal);
    if (btnCancelModalIzin) btnCancelModalIzin.addEventListener('click', closePengajuanModal);

    if (formPengajuanIzin) {
        formPengajuanIzin.addEventListener('submit', async function (e) {
            e.preventDefault();

            const formData = new FormData(this);

            try {
                Swal.fire({
                    title: 'Mengirim Pengajuan...',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });

                const response = await fetch('/dashboard/presensi/saya/izin', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: formData
                });

                const res = await response.json();

                if (response.ok && res.status === 'success') {
                    closePengajuanModal();
                    Swal.fire({
                        icon: 'success',
                        title: 'Terkirim!',
                        text: res.message,
                        timer: 1800,
                        showConfirmButton: false
                    }).then(() => window.location.reload());
                } else {
                    Swal.fire('Gagal', res.message || 'Gagal mengirim pengajuan izin.', 'error');
                }
            } catch (err) {
                Swal.fire('Error', 'Terjadi kesalahan sistem saat mengirim permohonan.', 'error');
            }
        });
    }

    // 3. Modal Snapshot Bukti Foto
    const modalSnapshot = document.getElementById('modalSnapshotSaya');
    const imgSnapshot = document.getElementById('imgSnapshotSaya');
    const snapshotCaption = document.getElementById('snapshotSayaCaption');
    const btnCloseSnapshot = document.getElementById('btnCloseSnapshotSaya');

    document.querySelectorAll('.btn-view-snapshot').forEach(btn => {
        btn.addEventListener('click', function () {
            const url = this.getAttribute('data-url');
            const caption = this.getAttribute('data-caption') || 'Foto Bukti Presensi';

            if (modalSnapshot && imgSnapshot) {
                imgSnapshot.src = url;
                if (snapshotCaption) snapshotCaption.textContent = caption;
                modalSnapshot.style.display = 'flex';
            }
        });
    });

    if (btnCloseSnapshot && modalSnapshot) {
        btnCloseSnapshot.addEventListener('click', () => {
            modalSnapshot.style.display = 'none';
        });
    }
});
