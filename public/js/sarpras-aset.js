/**
 * JavaScript Modul Aset & Inventaris Sarpras
 * Standar Baku SAE: Vanilla JS, Modal Handling (z-index: 99999), SweetAlert2
 */

document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('modalAset');
    const btnOpen = document.getElementById('btnOpenModalAset');
    const btnClose = document.getElementById('btnCloseModalAset');
    const btnCancel = document.getElementById('btnCancelModalAset');
    const form = document.getElementById('formAset');
    const modalTitle = document.getElementById('modalAsetTitle');
    const methodInput = document.getElementById('asetMethod');

    function openModal(isEdit = false, data = {}) {
        if (!modal) return;
        modal.style.display = 'flex';
        if (isEdit) {
            modalTitle.textContent = 'Edit Data Aset';
            methodInput.value = 'PUT';
            form.action = `/dashboard/sarpras/aset/${data.id}`;
            document.getElementById('asetKode').value = data.kode || '';
            document.getElementById('asetNama').value = data.nama || '';
            document.getElementById('asetKategori').value = data.kategori || 'Elektronik';
            document.getElementById('asetMerk').value = data.merk || '';
            document.getElementById('asetTahun').value = data.tahun || new Date().getFullYear();
            document.getElementById('asetSumber').value = data.sumber || 'BOS Reguler';
            document.getElementById('asetKondisi').value = data.kondisi || 'baik';
            document.getElementById('asetRuang').value = data.ruang || '';
            document.getElementById('asetJumlah').value = data.jumlah || 1;
            document.getElementById('asetSatuan').value = data.satuan || 'unit';
            document.getElementById('asetStatus').value = data.status || 'tersedia';
        } else {
            modalTitle.textContent = 'Tambah Aset Sarpras';
            methodInput.value = 'POST';
            form.action = '/dashboard/sarpras/aset';
            form.reset();
        }
    }

    function closeModal() {
        if (modal) modal.style.display = 'none';
    }

    if (btnOpen) btnOpen.addEventListener('click', () => openModal(false));
    if (btnClose) btnClose.addEventListener('click', closeModal);
    if (btnCancel) btnCancel.addEventListener('click', closeModal);

    if (modal) {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) closeModal();
        });
    }

    // Event delegation tombol edit
    document.addEventListener('click', function (e) {
        const btnEdit = e.target.closest('.btn-edit-aset');
        if (btnEdit) {
            const data = {
                id: btnEdit.dataset.id,
                kode: btnEdit.dataset.kode,
                nama: btnEdit.dataset.nama,
                kategori: btnEdit.dataset.kategori,
                merk: btnEdit.dataset.merk,
                tahun: btnEdit.dataset.tahun,
                sumber: btnEdit.dataset.sumber,
                kondisi: btnEdit.dataset.kondisi,
                ruang: btnEdit.dataset.ruang,
                jumlah: btnEdit.dataset.jumlah,
                satuan: btnEdit.dataset.satuan,
                status: btnEdit.dataset.status,
            };
            openModal(true, data);
        }

        // Event delegation tombol hapus
        const btnDelete = e.target.closest('.btn-delete-aset');
        if (btnDelete) {
            const id = btnDelete.dataset.id;
            const nama = btnDelete.dataset.nama || 'aset ini';
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

            if (window.Swal) {
                Swal.fire({
                    title: 'Hapus Aset?',
                    text: `Apakah Anda yakin ingin menghapus data ${nama}?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ef4444',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: 'Ya, Hapus',
                    cancelButtonText: 'Batal'
                }).then((res) => {
                    if (res.isConfirmed) {
                        fetch(`/dashboard/sarpras/aset/${id}`, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json',
                                'Content-Type': 'application/json'
                            }
                        })
                        .then(r => r.json())
                        .then(res => {
                            if (res.status === 'success') {
                                Swal.fire('Berhasil!', res.message, 'success').then(() => location.reload());
                            } else {
                                Swal.fire('Gagal', res.message || 'Terjadi kesalahan.', 'error');
                            }
                        })
                        .catch(() => {
                            Swal.fire('Error', 'Gagal memproses permintaan.', 'error');
                        });
                    }
                });
            } else {
                if (confirm(`Apakah Anda yakin ingin menghapus ${nama}?`)) {
                    fetch(`/dashboard/sarpras/aset/${id}`, {
                        method: 'DELETE',
                        headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
                    }).then(() => location.reload());
                }
            }
        }
    });
});
