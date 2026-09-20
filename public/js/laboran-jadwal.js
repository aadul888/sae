document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('modalJadwal');
    const form = document.getElementById('formJadwal');
    const modalTitle = document.getElementById('modalJadwalTitle');

    window.openModalJadwal = function () {
        if (!modal) return;
        form.reset();
        document.getElementById('jadwalMethod').value = 'POST';
        form.action = form.dataset.storeUrl || '';
        modalTitle.textContent = 'Tambah Penggunaan Lab';
        modal.style.display = 'flex';
    };

    window.closeModalJadwal = function () {
        if (!modal) return;
        modal.style.display = 'none';
    };

    window.editJadwal = function (data) {
        if (!modal) return;
        form.reset();
        document.getElementById('jadwalMethod').value = 'PUT';
        form.action = (form.dataset.updateUrl || '').replace(':id', data.id);
        modalTitle.textContent = 'Edit Penggunaan Lab';

        document.getElementById('ruang_id').value = data.ruang_id || '';
        document.getElementById('tanggal').value = data.tanggal || '';
        document.getElementById('jam_mulai').value = data.jam_mulai || '';
        document.getElementById('jam_selesai').value = data.jam_selesai || '';
        document.getElementById('guru_id').value = data.guru_id || '';
        document.getElementById('mata_pelajaran').value = data.mata_pelajaran || '';
        document.getElementById('rombongan_belajar_id').value = data.rombongan_belajar_id || '';
        document.getElementById('materi_praktikum').value = data.materi_praktikum || '';
        document.getElementById('status').value = data.status || 'menunggu';
        document.getElementById('catatan').value = data.catatan || '';

        modal.style.display = 'flex';
    };

    // Close modal when clicking outside
    if (modal) {
        modal.addEventListener('click', function (e) {
            if (e.target === modal) closeModalJadwal();
        });
    }

    // Delete confirmation with SweetAlert2
    document.querySelectorAll('form[data-confirm="delete"]').forEach(function (delForm) {
        delForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const name = delForm.dataset.name || 'data ini';
            Swal.fire({
                title: 'Konfirmasi Hapus',
                text: `Apakah Anda yakin ingin menghapus data "${name}"?`,
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
