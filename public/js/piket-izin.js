document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('modalIzin');
    const form = document.getElementById('formIzin');
    const modalTitle = document.getElementById('modalIzinTitle');
    const selectSiswa = document.getElementById('izin_siswa_id');
    const filterRombel = document.getElementById('filter_rombel_modal');

    window.openModalIzin = function () {
        if (!modal) return;
        form.reset();
        document.getElementById('izinMethod').value = 'POST';
        form.action = form.dataset.storeUrl || '';
        modalTitle.textContent = 'Terbitkan e-Izin Siswa';

        const now = new Date();
        document.getElementById('izin_tanggal').value = now.toISOString().split('T')[0];
        const h = String(now.getHours()).padStart(2, '0');
        const m = String(now.getMinutes()).padStart(2, '0');
        document.getElementById('izin_jam_keluar').value = `${h}:${m}`;
        document.getElementById('izin_jenis').value = 'keluar_sebentar';

        document.getElementById('rowSiswaSelect').style.display = 'grid';

        // Reset filter rombel
        if (filterRombel) filterRombel.value = '';
        filterSiswaByRombel();

        modal.style.display = 'flex';
    };

    window.closeModalIzin = function () {
        if (!modal) return;
        modal.style.display = 'none';
    };

    window.filterSiswaByRombel = function () {
        if (!selectSiswa || !filterRombel) return;
        const selectedRombel = filterRombel.value;
        const options = selectSiswa.querySelectorAll('option');

        options.forEach(opt => {
            if (!opt.value) {
                opt.style.display = 'block';
                return;
            }
            const rombel = opt.dataset.rombel;
            if (!selectedRombel || rombel === selectedRombel) {
                opt.style.display = 'block';
            } else {
                opt.style.display = 'none';
            }
        });
        selectSiswa.value = '';
    };

    window.editIzin = function (data) {
        if (!modal) return;
        form.reset();
        document.getElementById('izinMethod').value = 'PUT';
        form.action = (form.dataset.updateUrl || '').replace(':id', data.id);
        modalTitle.textContent = 'Edit e-Izin Siswa (' + data.nomor_tiket + ')';

        document.getElementById('izin_tanggal').value = data.tanggal || '';
        document.getElementById('izin_jenis').value = data.jenis_izin || 'keluar_sebentar';
        document.getElementById('izin_jam_keluar').value = data.jam_izin_keluar ? data.jam_izin_keluar.substring(0, 5) : '';
        document.getElementById('izin_jam_kembali').value = data.jam_rencana_kembali ? data.jam_rencana_kembali.substring(0, 5) : '';
        document.getElementById('izin_alasan').value = data.alasan || '';

        // Saat edit, sembunyikan pemilihan siswa baru agar relasi ID konsisten
        document.getElementById('rowSiswaSelect').style.display = 'none';

        modal.style.display = 'flex';
    };

    if (modal) {
        modal.addEventListener('click', function (e) {
            if (e.target === modal) closeModalIzin();
        });
    }

    // Delete confirmation with SweetAlert2
    document.querySelectorAll('form[data-confirm="delete"]').forEach(function (delForm) {
        delForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const name = delForm.dataset.name || 'tiket ini';
            Swal.fire({
                title: 'Konfirmasi Hapus',
                text: `Apakah Anda yakin ingin menghapus ${name}?`,
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
