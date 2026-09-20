document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('modalRonda');
    const form = document.getElementById('formRonda');
    const modalTitle = document.getElementById('modalRondaTitle');

    window.openModalRonda = function () {
        if (!modal) return;
        form.reset();
        document.getElementById('rondaMethod').value = 'POST';
        form.action = form.dataset.storeUrl || '';
        modalTitle.textContent = 'Catat Kontrol Ronda';

        const now = new Date();
        document.getElementById('ronda_tanggal').value = now.toISOString().split('T')[0];
        const h = String(now.getHours()).padStart(2, '0');
        const m = String(now.getMinutes()).padStart(2, '0');
        document.getElementById('ronda_jam').value = `${h}:${m}`;
        document.getElementById('ronda_pintu').value = 'terkunci_rapi';
        document.getElementById('ronda_lampu').value = 'menyala_sesuai';
        document.getElementById('ronda_situasi').value = 'kondusif';

        modal.style.display = 'flex';
    };

    window.closeModalRonda = function () {
        if (!modal) return;
        modal.style.display = 'none';
    };

    window.editRonda = function (data) {
        if (!modal) return;
        form.reset();
        document.getElementById('rondaMethod').value = 'PUT';
        form.action = (form.dataset.updateUrl || '').replace(':id', data.id);
        modalTitle.textContent = 'Edit Kontrol Ronda';

        document.getElementById('ronda_tanggal').value = data.tanggal || '';
        document.getElementById('ronda_jam').value = data.jam_kontrol ? data.jam_kontrol.substring(0, 5) : '';
        document.getElementById('ronda_zona').value = data.zona_kontrol || '';
        document.getElementById('ronda_pintu').value = data.status_pintu_jendela || 'terkunci_rapi';
        document.getElementById('ronda_lampu').value = data.status_lampu || 'menyala_sesuai';
        document.getElementById('ronda_situasi').value = data.situasi_keamanan || 'kondusif';
        document.getElementById('ronda_catatan').value = data.catatan_penjaga || '';

        modal.style.display = 'flex';
    };

    if (modal) {
        modal.addEventListener('click', function (e) {
            if (e.target === modal) closeModalRonda();
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
