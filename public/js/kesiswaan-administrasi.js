/**
 * Logika JavaScript Modular: Administrasi Kesiswaan (Buku Klaper, Mutasi, Kelulusan)
 * Standar Resmi SAE (Vanilla JS + SweetAlert2)
 */

document.addEventListener('DOMContentLoaded', function () {
    // 1. Modal Helper
    function openModal(selector) {
        const modal = document.querySelector(selector);
        if (modal) {
            modal.style.display = 'flex';
        }
    }

    function closeModal(selector) {
        const modal = document.querySelector(selector);
        if (modal) {
            modal.style.display = 'none';
        }
    }

    document.querySelectorAll('.close-modal').forEach(btn => {
        btn.addEventListener('click', function () {
            const target = this.getAttribute('data-target');
            if (target) closeModal(target);
        });
    });

    // 2. Tombol Buka Modal
    const btnOpenMutasi = document.getElementById('btnOpenMutasiModal');
    if (btnOpenMutasi) {
        btnOpenMutasi.addEventListener('click', () => openModal('#modalMutasi'));
    }

    const btnOpenKelulusan = document.getElementById('btnOpenKelulusanModal');
    if (btnOpenKelulusan) {
        btnOpenKelulusan.addEventListener('click', () => openModal('#modalKelulusan'));
    }

    // 3. Sinkronisasi Buku Klaper
    const btnSyncKlaper = document.getElementById('btnSyncKlaper');
    if (btnSyncKlaper) {
        btnSyncKlaper.addEventListener('click', function () {
            Swal.fire({
                title: 'Sinkronkan Buku Klaper?',
                text: 'Data seluruh peserta didik aktif dari Dapodik akan disinkronkan ke Buku Klaper.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya, Sinkronkan',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#2563eb',
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Memproses...',
                        text: 'Menyinkronkan Buku Klaper...',
                        allowOutsideClick: false,
                        didOpen: () => Swal.showLoading()
                    });

                    fetch('/dashboard/kesiswaan/klaper/sync', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                            'Accept': 'application/json',
                        }
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.status === 'success') {
                            Swal.fire('Berhasil!', data.message, 'success').then(() => location.reload());
                        } else {
                            Swal.fire('Gagal!', data.message || 'Terjadi kesalahan.', 'error');
                        }
                    })
                    .catch(err => {
                        Swal.fire('Error!', 'Gagal menghubungi server.', 'error');
                    });
                }
            });
        });
    }

    // 4. Form Submit Mutasi Siswa
    const formMutasi = document.getElementById('formMutasi');
    if (formMutasi) {
        formMutasi.addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = new FormData(this);

            Swal.fire({
                title: 'Memproses...',
                text: 'Menyimpan data mutasi siswa...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            fetch(this.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    'Accept': 'application/json',
                },
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire({
                        title: 'Berhasil!',
                        text: data.message,
                        icon: 'success',
                        showCancelButton: true,
                        confirmButtonText: 'Cetak Surat Mutasi',
                        cancelButtonText: 'Selesai',
                        confirmButtonColor: '#2563eb'
                    }).then((res) => {
                        if (res.isConfirmed && data.cetak_url) {
                            window.open(data.cetak_url, '_blank');
                        }
                        location.reload();
                    });
                } else {
                    Swal.fire('Gagal!', data.message || 'Terjadi kesalahan.', 'error');
                }
            })
            .catch(err => {
                Swal.fire('Error!', 'Gagal menyimpan mutasi.', 'error');
            });
        });
    }

    // 5. Form Submit Kelulusan Siswa
    const formKelulusan = document.getElementById('formKelulusan');
    if (formKelulusan) {
        formKelulusan.addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = new FormData(this);

            Swal.fire({
                title: 'Memproses...',
                text: 'Menyimpan data kelulusan & SKL...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            fetch(this.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    'Accept': 'application/json',
                },
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire({
                        title: 'Berhasil!',
                        text: data.message,
                        icon: 'success',
                        showCancelButton: true,
                        confirmButtonText: 'Cetak SKL',
                        cancelButtonText: 'Selesai',
                        confirmButtonColor: '#2563eb'
                    }).then((res) => {
                        if (res.isConfirmed && data.cetak_url) {
                            window.open(data.cetak_url, '_blank');
                        }
                        location.reload();
                    });
                } else {
                    Swal.fire('Gagal!', data.message || 'Terjadi kesalahan.', 'error');
                }
            })
            .catch(err => {
                Swal.fire('Error!', 'Gagal menyimpan kelulusan.', 'error');
            });
        });
    }

    // 6. Edit Nomor Klaper Inline Dialog
    document.querySelectorAll('.btn-edit-klaper').forEach(btn => {
        btn.addEventListener('click', function () {
            const id = this.getAttribute('data-id');
            const nama = this.getAttribute('data-nama');
            const noKlaper = this.getAttribute('data-nomor-klaper') || '';
            const noInduk = this.getAttribute('data-nomor-induk') || '';
            const tahun = this.getAttribute('data-tahun-masuk') || '';
            const status = this.getAttribute('data-status') || 'aktif';

            Swal.fire({
                title: `Edit Buku Klaper: ${nama}`,
                html: `
                    <div style="text-align: left; font-size: 0.88rem;">
                        <div style="margin-bottom: 10px;">
                            <label style="display:block; font-weight:700; margin-bottom:4px;">No. Klaper:</label>
                            <input id="swalNoKlaper" class="swal2-input" value="${noKlaper}" style="width: 100%; margin: 0; box-sizing: border-box;">
                        </div>
                        <div style="margin-bottom: 10px;">
                            <label style="display:block; font-weight:700; margin-bottom:4px;">No. Induk:</label>
                            <input id="swalNoInduk" class="swal2-input" value="${noInduk}" style="width: 100%; margin: 0; box-sizing: border-box;">
                        </div>
                        <div style="margin-bottom: 10px;">
                            <label style="display:block; font-weight:700; margin-bottom:4px;">Tahun Masuk:</label>
                            <input id="swalTahun" type="number" class="swal2-input" value="${tahun}" style="width: 100%; margin: 0; box-sizing: border-box;">
                        </div>
                        <div>
                            <label style="display:block; font-weight:700; margin-bottom:4px;">Status:</label>
                            <select id="swalStatus" class="swal2-select" style="width: 100%; margin: 0; box-sizing: border-box;">
                                <option value="aktif" ${status === 'aktif' ? 'selected' : ''}>Aktif</option>
                                <option value="lulus" ${status === 'lulus' ? 'selected' : ''}>Lulus</option>
                                <option value="mutasi_keluar" ${status === 'mutasi_keluar' ? 'selected' : ''}>Mutasi Keluar</option>
                                <option value="do" ${status === 'do' ? 'selected' : ''}>Drop Out (DO)</option>
                            </select>
                        </div>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: 'Simpan',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#2563eb',
                preConfirm: () => {
                    return {
                        nomor_klaper: document.getElementById('swalNoKlaper').value,
                        nomor_induk: document.getElementById('swalNoInduk').value,
                        tahun_masuk: document.getElementById('swalTahun').value,
                        status_klaper: document.getElementById('swalStatus').value,
                    };
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch(`/dashboard/kesiswaan/klaper/${id}`, {
                        method: 'PUT',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify(result.value)
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.status === 'success') {
                            Swal.fire('Berhasil!', data.message, 'success').then(() => location.reload());
                        } else {
                            Swal.fire('Gagal!', data.message || 'Terjadi kesalahan.', 'error');
                        }
                    })
                    .catch(err => {
                        Swal.fire('Error!', 'Gagal memperbarui klaper.', 'error');
                    });
                }
            });
        });
    });
});
