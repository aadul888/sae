document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('modalTamu');
    const form = document.getElementById('formTamu');
    const modalTitle = document.getElementById('modalTamuTitle');

    window.openModalTamu = function () {
        if (!modal) return;
        form.reset();
        document.getElementById('tamuMethod').value = 'POST';
        form.action = form.dataset.storeUrl || '';
        modalTitle.textContent = 'Catat Tamu Masuk';

        const now = new Date();
        const dateStr = now.toISOString().split('T')[0];
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');

        document.getElementById('tamu_tanggal').value = dateStr;
        document.getElementById('tamu_jam_masuk').value = `${hours}:${minutes}`;
        document.getElementById('tamu_status').value = 'berada_di_lokasi';

        modal.style.display = 'flex';
    };

    window.closeModalTamu = function () {
        if (!modal) return;
        modal.style.display = 'none';
    };

    window.editTamu = function (data) {
        if (!modal) return;
        form.reset();
        document.getElementById('tamuMethod').value = 'PUT';
        form.action = (form.dataset.updateUrl || '').replace(':id', data.id);
        modalTitle.textContent = 'Edit Data Tamu';

        document.getElementById('tamu_tanggal').value = data.tanggal || '';
        document.getElementById('tamu_jam_masuk').value = data.jam_masuk ? data.jam_masuk.substring(0, 5) : '';
        document.getElementById('tamu_jam_keluar').value = data.jam_keluar ? data.jam_keluar.substring(0, 5) : '';
        document.getElementById('tamu_nama').value = data.nama_tamu || '';
        document.getElementById('tamu_instansi').value = data.instansi_asal || '';
        document.getElementById('tamu_kontak').value = data.nomor_kontak || '';
        document.getElementById('tamu_tujuan').value = data.tujuan_bertemu || '';
        document.getElementById('tamu_keperluan').value = data.keperluan || '';
        document.getElementById('tamu_kartu').value = data.nomor_kartu_visitor || '';
        document.getElementById('tamu_nopol').value = data.nomor_polisi_kendaraan || '';
        document.getElementById('tamu_status').value = data.status || 'berada_di_lokasi';

        modal.style.display = 'flex';
    };

    if (modal) {
        modal.addEventListener('click', function (e) {
            if (e.target === modal) closeModalTamu();
        });
    }

    // Delete confirmation with SweetAlert2
    document.querySelectorAll('form[data-confirm="delete"]').forEach(function (delForm) {
        delForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const name = delForm.dataset.name || 'tamu ini';
            Swal.fire({
                title: 'Konfirmasi Hapus',
                text: `Apakah Anda yakin ingin menghapus catatan tamu "${name}"?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    delForm.submit();
                }
            });
        });
    });
});
