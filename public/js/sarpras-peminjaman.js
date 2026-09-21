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

    // Relasi Dinamis Peminjam (GTK / Siswa / Umum) & Ruang Sarpras
    const pinjamTipeSelect = document.getElementById('pinjamTipeSelect');
    const wrapSelectGtk = document.getElementById('wrapSelectGtk');
    const wrapSelectSiswa = document.getElementById('wrapSelectSiswa');
    const wrapInputUmum = document.getElementById('wrapInputUmum');
    const selectPeminjamGtk = document.getElementById('selectPeminjamGtk');
    const inputPeminjamSiswa = document.getElementById('inputPeminjamSiswa');
    const inputPeminjamUmum = document.getElementById('inputPeminjamUmum');
    const realPeminjamNama = document.getElementById('realPeminjamNama');
    const pinjamIdVal = document.getElementById('pinjamIdVal');
    const suggestRuangSelect = document.getElementById('suggestRuangSelect');
    const pinjamKeperluan = document.getElementById('pinjamKeperluan');

    function syncPeminjam() {
        if (!pinjamTipeSelect) return;
        const tipe = pinjamTipeSelect.value;
        if (tipe === 'gtk') {
            if (wrapSelectGtk) wrapSelectGtk.style.display = 'block';
            if (wrapSelectSiswa) wrapSelectSiswa.style.display = 'none';
            if (wrapInputUmum) wrapInputUmum.style.display = 'none';
            if (selectPeminjamGtk) {
                const opt = selectPeminjamGtk.options[selectPeminjamGtk.selectedIndex];
                if (pinjamIdVal) pinjamIdVal.value = selectPeminjamGtk.value || '';
                if (realPeminjamNama) realPeminjamNama.value = opt && opt.dataset.nama ? opt.dataset.nama : '';
            }
        } else if (tipe === 'siswa') {
            if (wrapSelectGtk) wrapSelectGtk.style.display = 'none';
            if (wrapSelectSiswa) wrapSelectSiswa.style.display = 'block';
            if (wrapInputUmum) wrapInputUmum.style.display = 'none';
            if (realPeminjamNama && inputPeminjamSiswa) realPeminjamNama.value = inputPeminjamSiswa.value.trim();
            const dl = document.getElementById('listPeminjamSiswa');
            if (dl && inputPeminjamSiswa && pinjamIdVal) {
                const matched = Array.from(dl.options).find(o => o.value === inputPeminjamSiswa.value);
                pinjamIdVal.value = matched && matched.dataset.id ? matched.dataset.id : '';
            }
        } else {
            if (wrapSelectGtk) wrapSelectGtk.style.display = 'none';
            if (wrapSelectSiswa) wrapSelectSiswa.style.display = 'none';
            if (wrapInputUmum) wrapInputUmum.style.display = 'block';
            if (pinjamIdVal) pinjamIdVal.value = '';
            if (realPeminjamNama && inputPeminjamUmum) realPeminjamNama.value = inputPeminjamUmum.value.trim();
        }
    }

    if (pinjamTipeSelect) {
        pinjamTipeSelect.addEventListener('change', syncPeminjam);
    }
    if (selectPeminjamGtk) {
        selectPeminjamGtk.addEventListener('change', function () {
            const opt = selectPeminjamGtk.options[selectPeminjamGtk.selectedIndex];
            if (pinjamIdVal) pinjamIdVal.value = selectPeminjamGtk.value || '';
            if (realPeminjamNama) realPeminjamNama.value = opt && opt.dataset.nama ? opt.dataset.nama : '';
        });
    }
    if (inputPeminjamSiswa) {
        inputPeminjamSiswa.addEventListener('input', function () {
            if (realPeminjamNama) realPeminjamNama.value = this.value.trim();
            const dl = document.getElementById('listPeminjamSiswa');
            if (dl && pinjamIdVal) {
                const matched = Array.from(dl.options).find(o => o.value === inputPeminjamSiswa.value);
                pinjamIdVal.value = matched && matched.dataset.id ? matched.dataset.id : '';
            }
        });
    }
    if (inputPeminjamUmum) {
        inputPeminjamUmum.addEventListener('input', function () {
            if (realPeminjamNama) realPeminjamNama.value = this.value.trim();
            if (pinjamIdVal) pinjamIdVal.value = '';
        });
    }
    if (suggestRuangSelect && pinjamKeperluan) {
        suggestRuangSelect.addEventListener('change', function () {
            if (this.value) {
                if (pinjamKeperluan.value) {
                    pinjamKeperluan.value += ' ' + this.value;
                } else {
                    pinjamKeperluan.value = 'Penggunaan ' + this.value;
                }
            }
        });
    }

    // Inisialisasi awal sinkronisasi peminjam
    syncPeminjam();

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
