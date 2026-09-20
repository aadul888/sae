/**
 * JavaScript Modul Peminjaman Sarpras
 * Standar Baku SAE: Vanilla JS, Modal Handling (z-index: 99999), SweetAlert2
 */

document.addEventListener('DOMContentLoaded', function () {
    const modalPinjam = document.getElementById('modalPinjam');
    const btnOpenPinjam = document.getElementById('btnOpenModalPinjam');
    const btnClosePinjam = document.getElementById('btnCloseModalPinjam');
    const btnCancelPinjam = document.getElementById('btnCancelModalPinjam');

    const modalKembali = document.getElementById('modalKembali');
    const btnCloseKembali = document.getElementById('btnCloseModalKembali');
    const btnCancelKembali = document.getElementById('btnCancelModalKembali');
    const formKembali = document.getElementById('formKembali');
    const kembaliDesc = document.getElementById('kembaliDesc');

    if (btnOpenPinjam && modalPinjam) {
        btnOpenPinjam.addEventListener('click', () => modalPinjam.style.display = 'flex');
    }
    if (btnClosePinjam && modalPinjam) {
        btnClosePinjam.addEventListener('click', () => modalPinjam.style.display = 'none');
    }
    if (btnCancelPinjam && modalPinjam) {
        btnCancelPinjam.addEventListener('click', () => modalPinjam.style.display = 'none');
    }

    if (btnCloseKembali && modalKembali) {
        btnCloseKembali.addEventListener('click', () => modalKembali.style.display = 'none');
    }
    if (btnCancelKembali && modalKembali) {
        btnCancelKembali.addEventListener('click', () => modalKembali.style.display = 'none');
    }

    // Tutup jika klik backdrop
    window.addEventListener('click', function (e) {
        if (e.target === modalPinjam) modalPinjam.style.display = 'none';
        if (e.target === modalKembali) modalKembali.style.display = 'none';
    });

    // Event delegation tombol kembalikan
    document.addEventListener('click', function (e) {
        const btnKembali = e.target.closest('.btn-kembalikan');
        if (btnKembali && modalKembali) {
            const id = btnKembali.dataset.id;
            const nomor = btnKembali.dataset.nomor;
            const barang = btnKembali.dataset.barang;

            formKembali.action = `/dashboard/sarpras/peminjaman/${id}/kembalikan`;
            kembaliDesc.innerHTML = `Mencatat pengembalian untuk <strong>${barang}</strong> (Nomor: ${nomor}).`;
            modalKembali.style.display = 'flex';
        }

        // Event delegation tombol hapus
        const btnDelete = e.target.closest('.btn-delete-pinjam');
        if (btnDelete) {
            const id = btnDelete.dataset.id;
            const nomor = btnDelete.dataset.nomor || 'transaksi ini';
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

            if (window.Swal) {
                Swal.fire({
                    title: 'Hapus Log Peminjaman?',
                    text: `Apakah Anda yakin ingin menghapus data ${nomor}?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ef4444',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: 'Ya, Hapus',
                    cancelButtonText: 'Batal'
                }).then((res) => {
                    if (res.isConfirmed) {
                        fetch(`/dashboard/sarpras/peminjaman/${id}`, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json',
                                'Content-Type': 'application/json'
                            }
                        })
                        .then(r => r.json())
                        .then(res => {
                            if (res.status === 'success') {
                                Swal.fire('Berhasil!', res.message, 'success').then(() => location.reload());
                            } else {
                                Swal.fire('Gagal', res.message || 'Terjadi kesalahan.', 'error');
                            }
                        })
                        .catch(() => {
                            Swal.fire('Error', 'Gagal memproses permintaan.', 'error');
                        });
                    }
                });
            } else {
                if (confirm(`Apakah Anda yakin ingin menghapus ${nomor}?`)) {
                    fetch(`/dashboard/sarpras/peminjaman/${id}`, {
                        method: 'DELETE',
                        headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
                    }).then(() => location.reload());
                }
            }
        }
    });
});
