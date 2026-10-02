window.openModalTamu = function () {
    const modal = document.getElementById('modalTamu');
    const form = document.getElementById('formTamu');
    const modalTitle = document.getElementById('modalTamuTitle');
    if (!modal || !form) return;
    form.reset();
    const methodInput = document.getElementById('tamuMethod');
    if (methodInput) methodInput.value = 'POST';
    form.action = form.dataset.storeUrl || '';
    if (modalTitle) modalTitle.textContent = 'Catat Tamu Masuk';

    const now = new Date();
    const dateStr = now.toISOString().split('T')[0];
    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');

    const tgl = document.getElementById('tamu_tanggal');
    if (tgl) tgl.value = dateStr;
    const jam = document.getElementById('tamu_jam_masuk');
    if (jam) jam.value = `${hours}:${minutes}`;
    const st = document.getElementById('tamu_status');
    if (st) st.value = 'berada_di_lokasi';

    modal.style.display = 'flex';
};

window.closeModalTamu = function () {
    const modal = document.getElementById('modalTamu');
    if (modal) modal.style.display = 'none';
};

window.editTamu = function (data) {
    if (typeof data === 'string') {
        try { data = JSON.parse(data); } catch(e) {}
    }
    const modal = document.getElementById('modalTamu');
    const form = document.getElementById('formTamu');
    const modalTitle = document.getElementById('modalTamuTitle');
    if (!modal || !form || !data) return;
    form.reset();
    const methodInput = document.getElementById('tamuMethod');
    if (methodInput) methodInput.value = 'PUT';
    form.action = (form.dataset.updateUrl || '').replace(':id', data.id);
    if (modalTitle) modalTitle.textContent = 'Edit Data Tamu';

    const setVal = (id, val) => {
        const el = document.getElementById(id);
        if (el) el.value = val !== undefined && val !== null ? val : '';
    };

    setVal('tamu_tanggal', data.tanggal);
    setVal('tamu_jam_masuk', data.jam_masuk ? data.jam_masuk.substring(0, 5) : '');
    setVal('tamu_jam_keluar', data.jam_keluar ? data.jam_keluar.substring(0, 5) : '');
    setVal('tamu_nama', data.nama_tamu);
    setVal('tamu_instansi', data.instansi_asal);
    setVal('tamu_kontak', data.nomor_kontak);
    setVal('tamu_tujuan', data.tujuan_bertemu);
    setVal('tamu_keperluan', data.keperluan);
    setVal('tamu_kartu', data.nomor_kartu_visitor);
    setVal('tamu_nopol', data.nomor_polisi_kendaraan);
    setVal('tamu_status', data.status || 'berada_di_lokasi');

    modal.style.display = 'flex';
};

document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('modalTamu');

    if (modal) {
        modal.addEventListener('click', function (e) {
            if (e.target === modal) window.closeModalTamu();
        });
    }

    // Delete confirmation with SweetAlert2
    document.querySelectorAll('form[data-confirm="delete"]').forEach(function (delForm) {
        delForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const name = delForm.dataset.name || 'tamu ini';
            if (typeof Swal !== 'undefined') {
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
            } else {
                if (confirm(`Apakah Anda yakin ingin menghapus catatan tamu "${name}"?`)) {
                    delForm.submit();
                }
            }
        });
    });
});
