document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('modalKebersihan');
    const form = document.getElementById('formKebersihan');
    const modalTitle = document.getElementById('modalKebersihanTitle');

    window.openModalKebersihan = function () {
        if (!modal) return;
        form.reset();
        document.getElementById('kebersihanMethod').value = 'POST';
        form.action = form.dataset.storeUrl || '';
        modalTitle.textContent = 'Tambah Checklist Kebersihan';

        const now = new Date();
        document.getElementById('kebersihan_tanggal').value = now.toISOString().split('T')[0];
        document.getElementById('kebersihan_shift').value = 'pagi';
        document.getElementById('kebersihan_kondisi').value = 'bersih';
        document.getElementById('kebersihan_air').value = 'lengkap';

        modal.style.display = 'flex';
    };

    window.closeModalKebersihan = function () {
        if (!modal) return;
        modal.style.display = 'none';
    };

    window.editKebersihan = function (data) {
        if (!modal) return;
        form.reset();
        document.getElementById('kebersihanMethod').value = 'PUT';
        form.action = (form.dataset.updateUrl || '').replace(':id', data.id);
        modalTitle.textContent = 'Edit Checklist Kebersihan';

        document.getElementById('kebersihan_tanggal').value = data.tanggal || '';
        document.getElementById('kebersihan_shift').value = data.shift || 'pagi';
        document.getElementById('kebersihan_area').value = data.area_zona || '';
        document.getElementById('kebersihan_kondisi').value = data.kondisi_kebersihan || 'bersih';
        document.getElementById('kebersihan_air').value = data.ketersediaan_air_sabun || 'lengkap';
        document.getElementById('kebersihan_catatan').value = data.catatan_temuan || '';

        modal.style.display = 'flex';
    };

    if (modal) {
        modal.addEventListener('click', function (e) {
            if (e.target === modal) closeModalKebersihan();
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
