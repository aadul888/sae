document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('modalScanTiket');
    const inputScan = document.getElementById('inputScanTiket');
    const resContainer = document.getElementById('scanResultContainer');

    window.openModalScan = function () {
        if (!modal) return;
        modal.style.display = 'flex';
        inputScan.value = '';
        resContainer.style.display = 'none';
        setTimeout(() => inputScan.focus(), 150);
    };

    window.closeModalScan = function () {
        if (!modal) return;
        modal.style.display = 'none';
    };

    if (modal) {
        modal.addEventListener('click', function (e) {
            if (e.target === modal) closeModalScan();
        });
    }

    if (inputScan) {
        inputScan.addEventListener('keypress', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                cariTiketScan();
            }
        });
    }

    window.cariTiketScan = function () {
        const noTiket = inputScan.value.trim();
        if (!noTiket) {
            Swal.fire('Perhatian', 'Silakan ketik atau scan nomor tiket.', 'info');
            return;
        }

        fetch(`${window.keamananRoutes.verifikasiTiket}?nomor_tiket=${encodeURIComponent(noTiket)}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(res => {
            if (!res.success) {
                resContainer.style.display = 'none';
                Swal.fire('Tidak Ditemukan', res.message || 'Tiket tidak ditemukan.', 'error');
                return;
            }

            const data = res.data;
            document.getElementById('resNomorTiket').textContent = data.nomor_tiket;
            document.getElementById('resNamaSiswa').textContent = data.nama_siswa + (data.nisn ? ` (NISN: ${data.nisn})` : '');
            document.getElementById('resRombel').textContent = 'Kelas/Rombel: ' + (data.nama_rombel || '-');
            document.getElementById('resAlasan').innerHTML = `<strong>Jenis:</strong> ${data.jenis_izin} &bull; <strong>Alasan:</strong> ${data.alasan}`;
            document.getElementById('resStatus').innerHTML = `<strong>Status Sekarang:</strong> <span class="badge" style="background: rgba(59,130,246,0.15); color: #3b82f6;">${data.status}</span>`;

            const actionContainer = document.getElementById('scanActionContainer');
            actionContainer.innerHTML = '';

            const token = document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').getAttribute('content') : '';

            if (data.status === 'menunggu_satpam') {
                const formOut = document.createElement('form');
                formOut.action = `${window.keamananRoutes.checkoutBase}/${data.id}`;
                formOut.method = 'POST';
                formOut.innerHTML = `
                    <input type="hidden" name="_token" value="${token}">
                    <button type="submit" class="btn btn-warning" style="display: flex; align-items: center; gap: 6px;">
                        <i class="fas fa-sign-out-alt"></i> Konfirmasi KELUAR Gerbang
                    </button>
                `;
                actionContainer.appendChild(formOut);
            } else if (data.status === 'di_luar' && data.jenis_izin === 'keluar_sebentar') {
                const formIn = document.createElement('form');
                formIn.action = `${window.keamananRoutes.checkinBase}/${data.id}`;
                formIn.method = 'POST';
                formIn.innerHTML = `
                    <input type="hidden" name="_token" value="${token}">
                    <button type="submit" class="btn btn-success" style="display: flex; align-items: center; gap: 6px;">
                        <i class="fas fa-sign-in-alt"></i> Konfirmasi KEMBALI Masuk Gerbang
                    </button>
                `;
                actionContainer.appendChild(formIn);
            } else {
                actionContainer.innerHTML = '<span style="color: var(--text-muted); font-size: 0.85rem;">Tiket ini sudah selesai atau tidak membutuhkan aksi gerbang lebih lanjut.</span>';
            }

            resContainer.style.display = 'block';
        })
        .catch(err => {
            console.error(err);
            Swal.fire('Error', 'Terjadi kesalahan saat memverifikasi tiket.', 'error');
        });
    };

    // Event delegation SweetAlert2 for forms with data-confirm-action
    document.querySelectorAll('form[data-confirm-action]').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            const actionType = form.dataset.confirmAction;
            const name = form.dataset.name || 'siswa ini';
            const isOut = actionType === 'checkout';

            Swal.fire({
                title: isOut ? 'Konfirmasi Keluar Gerbang' : 'Konfirmasi Kembali Masuk',
                text: `Apakah Anda yakin ingin memverifikasi ${isOut ? 'KELUAR' : 'KEMBALI'} untuk "${name}"?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: isOut ? '#f59e0b' : '#10b981',
                cancelButtonColor: '#6c757d',
                confirmButtonText: isOut ? 'Ya, Keluar' : 'Ya, Kembali',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    });
});
