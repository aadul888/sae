document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('modalJurnal');
    const form = document.getElementById('formJurnal');
    const modalTitle = document.getElementById('modalJurnalTitle');

    window.openModalJurnal = function () {
        if (!modal) return;
        form.reset();
        document.getElementById('jurnalMethod').value = 'POST';
        form.action = form.dataset.storeUrl || '';
        modalTitle.textContent = 'Tulis Jurnal Piket';

        const now = new Date();
        document.getElementById('jurnal_tanggal').value = now.toISOString().split('T')[0];
        document.getElementById('jurnal_shift').value = 'pagi';
        document.getElementById('jurnal_status').value = 'berjalan';

        modal.style.display = 'flex';
    };

    window.closeModalJurnal = function () {
        if (!modal) return;
        modal.style.display = 'none';
    };

    window.editJurnal = function (data) {
        if (!modal) return;
        form.reset();
        document.getElementById('jurnalMethod').value = 'PUT';
        form.action = (form.dataset.updateUrl || '').replace(':id', data.id);
        modalTitle.textContent = 'Edit Jurnal Piket';

        document.getElementById('jurnal_tanggal').value = data.tanggal || '';
        document.getElementById('jurnal_shift').value = data.shift_jam || 'pagi';
        document.getElementById('jurnal_terlambat').value = data.jumlah_siswa_terlambat ?? 0;
        document.getElementById('jurnal_izin').value = data.jumlah_siswa_izin ?? 0;
        document.getElementById('jurnal_catatan').value = data.catatan_kejadian || '';
        document.getElementById('jurnal_status').value = data.status || 'berjalan';

        modal.style.display = 'flex';
    };

    if (modal) {
        modal.addEventListener('click', function (e) {
            if (e.target === modal) closeModalJurnal();
        });
    }

    // Delete confirmation with SweetAlert2
    document.querySelectorAll('form[data-confirm="delete"]').forEach(function (delForm) {
        delForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const name = delForm.dataset.name || 'data ini';
            Swal.fire({
                title: 'Konfirmasi Hapus',
                text: `Apakah Anda yakin ingin menghapus "${name}"?`,
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
