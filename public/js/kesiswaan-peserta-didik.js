/**
 * Logika JavaScript Modular: Peserta Didik (Aktif, Tidak Aktif, Alumni, Berkas, Usulan Perubahan)
 * Standar Resmi SAE (Vanilla JS + SweetAlert2)
 */

document.addEventListener('DOMContentLoaded', function () {
    // 1. Modal Helper
    function openModal(selector) {
        const modal = document.querySelector(selector);
        if (modal) modal.style.display = 'flex';
    }

    function closeModal(selector) {
        const modal = document.querySelector(selector);
        if (modal) modal.style.display = 'none';
    }

    document.querySelectorAll('.close-modal').forEach(btn => {
        btn.addEventListener('click', function () {
            const target = this.getAttribute('data-target');
            if (target) closeModal(target);
        });
    });

    const btnOpenUsulan = document.getElementById('btnOpenUsulanModal');
    if (btnOpenUsulan) {
        btnOpenUsulan.addEventListener('click', () => openModal('#modalUsulan'));
    }

    // 2. Submit Usulan Perubahan Data
    const formUsulan = document.getElementById('formUsulan');
    if (formUsulan) {
        formUsulan.addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = new FormData(this);

            Swal.fire({
                title: 'Memproses...',
                text: 'Mengirim usulan perubahan data...',
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
                    Swal.fire('Berhasil!', data.message, 'success').then(() => location.reload());
                } else {
                    Swal.fire('Gagal!', data.message || 'Terjadi kesalahan.', 'error');
                }
            })
            .catch(err => {
                Swal.fire('Error!', 'Gagal mengirim usulan.', 'error');
            });
        });
    }

    // 3. Edit Checklist Berkas Fisik
    document.querySelectorAll('.btn-edit-berkas').forEach(btn => {
        btn.addEventListener('click', function () {
            const id = this.getAttribute('data-id');
            const nama = this.getAttribute('data-nama');
            const akta = this.getAttribute('data-akta') === '1';
            const kk = this.getAttribute('data-kk') === '1';
            const ijazah = this.getAttribute('data-ijazah') === '1';
            const ktp = this.getAttribute('data-ktp') === '1';
            const kip = this.getAttribute('data-kip') === '1';

            Swal.fire({
                title: `Verifikasi Berkas: ${nama}`,
                html: `
                    <div style="text-align: left; font-size: 0.9rem; line-height: 2;">
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                            <input type="checkbox" id="swalAkta" ${akta ? 'checked' : ''}> Akta Kelahiran
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                            <input type="checkbox" id="swalKk" ${kk ? 'checked' : ''}> Kartu Keluarga (KK)
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                            <input type="checkbox" id="swalIjazah" ${ijazah ? 'checked' : ''}> Ijazah / SKL SMP
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                            <input type="checkbox" id="swalKtp" ${ktp ? 'checked' : ''}> KTP Orang Tua / Wali
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                            <input type="checkbox" id="swalKip" ${kip ? 'checked' : ''}> Kartu KIP / PIP (Jika Ada)
                        </label>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: 'Simpan Verifikasi',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#2563eb',
                preConfirm: () => {
                    return {
                        akta_kelahiran: document.getElementById('swalAkta').checked,
                        kartu_keluarga: document.getElementById('swalKk').checked,
                        ijazah_smp: document.getElementById('swalIjazah').checked,
                        ktp_orang_tua: document.getElementById('swalKtp').checked,
                        kip_pip: document.getElementById('swalKip').checked,
                    };
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch(`/dashboard/kesiswaan/peserta-didik/berkas/${id}`, {
                        method: 'POST',
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
                        Swal.fire('Error!', 'Gagal memperbarui berkas.', 'error');
                    });
                }
            });
        });
    });

    // 4. Verifikasi Usulan Perubahan Data (Setujui / Tolak)
    document.querySelectorAll('.btn-verif-usulan').forEach(btn => {
        btn.addEventListener('click', function () {
            const id = this.getAttribute('data-id');
            const nama = this.getAttribute('data-nama');
            const kolom = this.getAttribute('data-kolom');
            const nilai = this.getAttribute('data-nilai');

            Swal.fire({
                title: 'Verifikasi Usulan Data',
                html: `
                    <div style="text-align: left; font-size: 0.88rem; margin-bottom: 12px;">
                        <div><strong>Siswa:</strong> ${nama}</div>
                        <div><strong>Kolom:</strong> ${kolom}</div>
                        <div><strong>Nilai Baru:</strong> ${nilai}</div>
                        <div style="margin-top: 10px;">
                            <label style="display:block; font-weight:700; margin-bottom:4px;">Catatan Verifikasi:</label>
                            <input id="swalCatatanVerif" class="swal2-input" placeholder="Tuliskan catatan verifikasi..." style="width: 100%; margin: 0; box-sizing: border-box;">
                        </div>
                    </div>
                `,
                showCancelButton: true,
                showDenyButton: true,
                confirmButtonText: '<i class="fas fa-check"></i> Setujui',
                denyButtonText: '<i class="fas fa-times"></i> Tolak',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#10b981',
                denyButtonColor: '#ef4444',
            }).then((result) => {
                let status = null;
                if (result.isConfirmed) {
                    status = 'disetujui';
                } else if (result.isDenied) {
                    status = 'ditolak';
                }

                if (status) {
                    const catatan = document.getElementById('swalCatatanVerif')?.value || '';
                    fetch(`/dashboard/kesiswaan/peserta-didik/usulan/${id}/verifikasi`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ status: status, catatan_verifikasi: catatan })
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
                        Swal.fire('Error!', 'Gagal memverifikasi usulan.', 'error');
                    });
                }
            });
        });
    });
});
