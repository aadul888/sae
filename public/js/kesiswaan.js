/**
 * SAE - Kesiswaan (Buku Induk, Klaper, Mutasi & Berkas Siswa)
 * Mematuhi Standar Pemisahan JS & Blade SAE
 */

document.addEventListener('DOMContentLoaded', function () {
    // 1. Inisialisasi Modal Edit Klaper
    const modalKlaper = document.getElementById('modalEditKlaper');
    const formKlaper = document.getElementById('formKlaperEdit');

    document.querySelectorAll('.btn-edit-klaper').forEach(btn => {
        btn.addEventListener('click', function () {
            const id = this.dataset.id;
            const nama = this.dataset.nama || '';
            const nisn = this.dataset.nisn || '';
            const nipd = this.dataset.nipd || '';
            const noInduk = this.dataset.noInduk || '';
            const noKlaper = this.dataset.noKlaper || '';
            const thnMasuk = this.dataset.tahunMasuk || '';
            const thnLulus = this.dataset.tahunLulus || '';
            const statusPd = this.dataset.statusPd || 'Aktif';
            const catatan = this.dataset.catatan || '';

            if (formKlaper) {
                formKlaper.action = `/kesiswaan/klaper/${id}`;
                document.getElementById('klaperPdNama').textContent = `${nama} (NISN: ${nisn} / NIPD: ${nipd})`;
                document.getElementById('edit_nomor_induk').value = noInduk;
                document.getElementById('edit_nomor_klaper').value = noKlaper;
                document.getElementById('edit_tahun_masuk').value = thnMasuk;
                document.getElementById('edit_tahun_keluar_lulus').value = thnLulus;
                document.getElementById('edit_status_pd').value = statusPd;
                document.getElementById('edit_catatan').value = catatan;
            }

            if (modalKlaper) {
                modalKlaper.style.display = 'flex';
            }
        });
    });

    // 2. Tombol Tutup Modal Klaper
    const btnCloseKlaper = document.getElementById('btnCloseModalKlaper');
    const btnCancelKlaper = document.getElementById('btnCancelModalKlaper');

    [btnCloseKlaper, btnCancelKlaper].forEach(btn => {
        if (btn) {
            btn.addEventListener('click', function () {
                if (modalKlaper) modalKlaper.style.display = 'none';
            });
        }
    });

    if (modalKlaper) {
        modalKlaper.addEventListener('click', function (e) {
            if (e.target === modalKlaper) {
                modalKlaper.style.display = 'none';
            }
        });
    }

    // 3. Tombol Sinkronisasi Klaper dari Dapodik
    const btnSyncKlaper = document.getElementById('btnSyncKlaper');
    if (btnSyncKlaper) {
        btnSyncKlaper.addEventListener('click', function () {
            Swal.fire({
                title: 'Sinkronkan Buku Klaper?',
                text: 'Sistem akan mendaftarkan seluruh peserta didik aktif dari database ke buku klaper & induk secara otomatis.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya, Sinkronkan!',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#4f46e5',
                cancelButtonColor: '#6b7280'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Memproses...',
                        text: 'Mohon tunggu, sedang menyinkronkan data peserta didik.',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    fetch('/kesiswaan/klaper/sync', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                        }
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.status === 'success') {
                            Swal.fire({
                                title: 'Berhasil!',
                                text: data.message,
                                icon: 'success',
                                confirmButtonColor: '#4f46e5'
                            }).then(() => {
                                window.location.reload();
                            });
                        } else {
                            Swal.fire('Gagal!', data.message || 'Terjadi kesalahan.', 'error');
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        Swal.fire('Error!', 'Terjadi kesalahan saat memproses permintaan.', 'error');
                    });
                }
            });
        });
    }

    // 4. Modal Mutasi Siswa
    const modalMutasi = document.getElementById('modalMutasiSiswa');
    const btnOpenMutasi = document.getElementById('btnOpenMutasiModal');
    const btnCloseMutasi = document.getElementById('btnCloseModalMutasi');
    const btnCancelMutasi = document.getElementById('btnCancelModalMutasi');

    if (btnOpenMutasi && modalMutasi) {
        btnOpenMutasi.addEventListener('click', function () {
            modalMutasi.style.display = 'flex';
        });
    }

    [btnCloseMutasi, btnCancelMutasi].forEach(btn => {
        if (btn && modalMutasi) {
            btn.addEventListener('click', function () {
                modalMutasi.style.display = 'none';
            });
        }
    });

    if (modalMutasi) {
        modalMutasi.addEventListener('click', function (e) {
            if (e.target === modalMutasi) {
                modalMutasi.style.display = 'none';
            }
        });
    }

    // Auto-update NISN & Rombel saat peserta didik dipilih pada modal mutasi
    const selectPdMutasi = document.getElementById('mutasi_peserta_didik_id');
    if (selectPdMutasi) {
        selectPdMutasi.addEventListener('change', function () {
            const opt = this.options[this.selectedIndex];
            const nisn = opt.dataset.nisn || '';
            const rombel = opt.dataset.rombel || '';
            const nipd = opt.dataset.nipd || '';

            const infoEl = document.getElementById('mutasiPdInfo');
            if (infoEl) {
                if (this.value) {
                    infoEl.textContent = `NISN: ${nisn} | NIPD: ${nipd} | Kelas: ${rombel}`;
                    infoEl.style.display = 'block';
                } else {
                    infoEl.style.display = 'none';
                }
            }
        });
    }

    // 5. Checklist Verifikasi Berkas Persyaratan Siswa
    document.querySelectorAll('.chk-berkas-verifikasi').forEach(chk => {
        chk.addEventListener('change', function () {
            const pdId = this.dataset.pdId;
            const field = this.dataset.field;
            const isChecked = this.checked;

            const payload = {};
            payload[field] = isChecked;

            fetch(`/kesiswaan/berkas/${pdId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                },
                body: JSON.stringify(payload)
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    // Update status label & progress bar jika ada
                    const statusBadge = document.getElementById(`berkasStatus_${pdId}`);
                    if (statusBadge && data.data) {
                        statusBadge.className = `badge ${data.data.status_berkas === 'Lengkap' ? 'badge-success' : 'badge-warning'}`;
                        statusBadge.textContent = data.data.status_berkas;
                    }

                    // Toast notification kecil
                    const Toast = Swal.mixin({
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 1800,
                        timerProgressBar: true
                    });
                    Toast.fire({
                        icon: 'success',
                        title: 'Status berkas berhasil diperbarui'
                    });
                } else {
                    Swal.fire('Gagal!', data.message || 'Gagal menyimpan berkas.', 'error');
                }
            })
            .catch(err => {
                console.error(err);
                Swal.fire('Error!', 'Gagal menghubungi server.', 'error');
            });
        });
    });

    // 6. Form konfirmasi submit standar
    document.querySelectorAll('form[data-confirm="mutasi"]').forEach(form => {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            const jenis = document.getElementById('mutasi_jenis')?.value || 'Mutasi';

            Swal.fire({
                title: `Simpan Data ${jenis}?`,
                text: 'Pastikan nomor surat dan data sekolah asal/tujuan sudah sesuai.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya, Simpan',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#4f46e5',
                cancelButtonColor: '#6b7280'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    });
});
