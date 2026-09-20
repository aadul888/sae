/**
 * JavaScript Modul Inventaris Alat & Bahan Laboratorium
 * Standar Baku SAE: Vanilla JS, Modal Handling (z-index: 99999), SweetAlert2
 */

document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('modalLab');
    const btnOpen = document.getElementById('btnOpenModalLab');
    const btnClose = document.getElementById('btnCloseModalLab');
    const btnCancel = document.getElementById('btnCancelModalLab');
    const form = document.getElementById('formLab');
    const modalTitle = document.getElementById('modalLabTitle');
    const methodInput = document.getElementById('labMethod');
    const wrapperStokTersedia = document.getElementById('wrapperStokTersedia');

    function openModal(isEdit = false, data = {}) {
        if (!modal) return;
        modal.style.display = 'flex';
        if (isEdit) {
            modalTitle.textContent = 'Edit Alat / Bahan Lab';
            methodInput.value = 'PUT';
            form.action = `/dashboard/laboran/inventaris/${data.id}`;
            document.getElementById('labRuang').value = data.lab || '';
            document.getElementById('labKode').value = data.kode || '';
            document.getElementById('labNama').value = data.nama || '';
            document.getElementById('labJenis').value = data.jenis || 'alat';
            document.getElementById('labStokTotal').value = data.stoktotal || 1;
            document.getElementById('labStokTersedia').value = data.stoktersedia || 1;
            document.getElementById('labSatuan').value = data.satuan || 'unit';
            document.getElementById('labKondisi').value = data.kondisi || 'baik';
            document.getElementById('labSpek').value = data.spek || '';
            document.getElementById('labRak').value = data.rak || '';
            document.getElementById('labExp').value = data.exp || '';
            if (wrapperStokTersedia) wrapperStokTersedia.style.display = 'block';
        } else {
            modalTitle.textContent = 'Tambah Alat / Bahan Lab';
            methodInput.value = 'POST';
            form.action = '/dashboard/laboran/inventaris';
            form.reset();
            if (wrapperStokTersedia) wrapperStokTersedia.style.display = 'none';
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
        const btnEdit = e.target.closest('.btn-edit-lab');
        if (btnEdit) {
            const data = {
                id: btnEdit.dataset.id,
                lab: btnEdit.dataset.lab,
                kode: btnEdit.dataset.kode,
                nama: btnEdit.dataset.nama,
                jenis: btnEdit.dataset.jenis,
                stoktotal: btnEdit.dataset.stoktotal,
                stoktersedia: btnEdit.dataset.stoktersedia,
                satuan: btnEdit.dataset.satuan,
                kondisi: btnEdit.dataset.kondisi,
                spek: btnEdit.dataset.spek,
                rak: btnEdit.dataset.rak,
                exp: btnEdit.dataset.exp,
            };
            openModal(true, data);
        }

        // Event delegation tombol hapus
        const btnDelete = e.target.closest('.btn-delete-lab');
        if (btnDelete) {
            const id = btnDelete.dataset.id;
            const nama = btnDelete.dataset.nama || 'item ini';
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

            if (window.Swal) {
                Swal.fire({
                    title: 'Hapus Item Lab?',
                    text: `Apakah Anda yakin ingin menghapus data ${nama}?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ef4444',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: 'Ya, Hapus',
                    cancelButtonText: 'Batal'
                }).then((res) => {
                    if (res.isConfirmed) {
                        fetch(`/dashboard/laboran/inventaris/${id}`, {
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
                    fetch(`/dashboard/laboran/inventaris/${id}`, {
                        method: 'DELETE',
                        headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
                    }).then(() => location.reload());
                }
            }
        }
    });
});
