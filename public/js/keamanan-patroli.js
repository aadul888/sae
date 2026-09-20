document.addEventListener('DOMContentLoaded', function () {
    // --- Patroli Modal ---
    const modalPatroli = document.getElementById('modalPatroli');
    const formPatroli = document.getElementById('formPatroli');
    const modalPatroliTitle = document.getElementById('modalPatroliTitle');

    window.openModalPatroli = function () {
        if (!modalPatroli) return;
        formPatroli.reset();
        document.getElementById('patroliMethod').value = 'POST';
        formPatroli.action = formPatroli.dataset.storeUrl || '';
        modalPatroliTitle.textContent = 'Tambah Log Patroli';

        const now = new Date();
        document.getElementById('patroli_tanggal').value = now.toISOString().split('T')[0];
        const h = String(now.getHours()).padStart(2, '0');
        const m = String(now.getMinutes()).padStart(2, '0');
        document.getElementById('patroli_jam').value = `${h}:${m}`;

        modalPatroli.style.display = 'flex';
    };

    window.closeModalPatroli = function () {
        if (!modalPatroli) return;
        modalPatroli.style.display = 'none';
    };

    window.editPatroli = function (data) {
        if (!modalPatroli) return;
        formPatroli.reset();
        document.getElementById('patroliMethod').value = 'PUT';
        formPatroli.action = (formPatroli.dataset.updateUrl || '').replace(':id', data.id);
        modalPatroliTitle.textContent = 'Edit Log Patroli';

        document.getElementById('patroli_tanggal').value = data.tanggal || '';
        document.getElementById('patroli_jam').value = data.jam_patroli ? data.jam_patroli.substring(0, 5) : '';
        document.getElementById('patroli_rute').value = data.rute_zona || '';
        document.getElementById('patroli_kondisi').value = data.kondisi_lingkungan || 'aman_terkendali';
        document.getElementById('patroli_catatan').value = data.catatan_temuan || '';

        modalPatroli.style.display = 'flex';
    };

    if (modalPatroli) {
        modalPatroli.addEventListener('click', function (e) {
            if (e.target === modalPatroli) closeModalPatroli();
        });
    }

    // --- Insiden Modal ---
    const modalInsiden = document.getElementById('modalInsiden');
    const formInsiden = document.getElementById('formInsiden');
    const modalInsidenTitle = document.getElementById('modalInsidenTitle');

    window.openModalInsiden = function () {
        if (!modalInsiden) return;
        formInsiden.reset();
        document.getElementById('insidenMethod').value = 'POST';
        formInsiden.action = formInsiden.dataset.storeUrl || '';
        modalInsidenTitle.textContent = 'Laporkan Insiden Keamanan';

        const now = new Date();
        document.getElementById('insiden_tanggal').value = now.toISOString().split('T')[0];
        const h = String(now.getHours()).padStart(2, '0');
        const m = String(now.getMinutes()).padStart(2, '0');
        document.getElementById('insiden_jam').value = `${h}:${m}`;
        document.getElementById('insiden_urgensi').value = 'sedang';
        document.getElementById('insiden_status').value = 'dalam_penanganan';

        modalInsiden.style.display = 'flex';
    };

    window.closeModalInsiden = function () {
        if (!modalInsiden) return;
        modalInsiden.style.display = 'none';
    };

    window.editInsiden = function (data) {
        if (!modalInsiden) return;
        formInsiden.reset();
        document.getElementById('insidenMethod').value = 'PUT';
        formInsiden.action = (formInsiden.dataset.updateUrl || '').replace(':id', data.id);
        modalInsidenTitle.textContent = 'Edit Laporan Insiden';

        document.getElementById('insiden_tanggal').value = data.tanggal || '';
        document.getElementById('insiden_jam').value = data.jam_kejadian ? data.jam_kejadian.substring(0, 5) : '';
        document.getElementById('insiden_judul').value = data.judul_insiden || '';
        document.getElementById('insiden_lokasi').value = data.lokasi_kejadian || '';
        document.getElementById('insiden_urgensi').value = data.tingkat_urgensi || 'sedang';
        document.getElementById('insiden_pihak').value = data.pihak_terlibat || '';
        document.getElementById('insiden_kronologi').value = data.kronologi || '';
        document.getElementById('insiden_tindakan').value = data.tindakan_diambil || '';
        document.getElementById('insiden_status').value = data.status_penyelesaian || 'dalam_penanganan';

        modalInsiden.style.display = 'flex';
    };

    if (modalInsiden) {
        modalInsiden.addEventListener('click', function (e) {
            if (e.target === modalInsiden) closeModalInsiden();
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
