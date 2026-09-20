/**
 * JavaScript Modul Ruang Sarpras
 * Standar Baku SAE: Vanilla JS, Modal Handling (z-index: 99999), SweetAlert2
 */

document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('modalRuang');
    const btnOpen = document.getElementById('btnOpenModalRuang');
    const btnClose = document.getElementById('btnCloseModalRuang');
    const btnCancel = document.getElementById('btnCancelModalRuang');
    const form = document.getElementById('formRuang');
    const modalTitle = document.getElementById('modalRuangTitle');
    const methodInput = document.getElementById('ruangMethod');

    function openModal(isEdit = false, data = {}) {
        if (!modal) return;
        modal.style.display = 'flex';
        if (isEdit) {
            modalTitle.textContent = 'Edit Data Ruang';
            methodInput.value = 'PUT';
            form.action = `/dashboard/sarpras/ruang/${data.id}`;
            document.getElementById('ruangKode').value = data.kode || '';
            document.getElementById('ruangNama').value = data.nama || '';
            document.getElementById('ruangGedung').value = data.gedung || '';
            document.getElementById('ruangLantai').value = data.lantai || '';
            document.getElementById('ruangPj').value = data.pj || '';
            document.getElementById('ruangKondisi').value = data.kondisi || 'baik';
            document.getElementById('ruangKeterangan').value = data.keterangan || '';
        } else {
            modalTitle.textContent = 'Tambah Data Ruang';
            methodInput.value = 'POST';
            form.action = '/dashboard/sarpras/ruang';
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
        const btnEdit = e.target.closest('.btn-edit-ruang');
        if (btnEdit) {
            const data = {
                id: btnEdit.dataset.id,
                kode: btnEdit.dataset.kode,
                nama: btnEdit.dataset.nama,
                gedung: btnEdit.dataset.gedung,
                lantai: btnEdit.dataset.lantai,
                pj: btnEdit.dataset.pj,
                kondisi: btnEdit.dataset.kondisi,
                keterangan: btnEdit.dataset.keterangan,
            };
            openModal(true, data);
        }

        // Event delegation tombol hapus
        const btnDelete = e.target.closest('.btn-delete-ruang');
        if (btnDelete) {
            const id = btnDelete.dataset.id;
            const nama = btnDelete.dataset.nama || 'ruang ini';
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

            if (window.Swal) {
                Swal.fire({
                    title: 'Hapus Ruang?',
                    text: `Apakah Anda yakin ingin menghapus ${nama}?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ef4444',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: 'Ya, Hapus',
                    cancelButtonText: 'Batal'
                }).then((res) => {
                    if (res.isConfirmed) {
                        fetch(`/dashboard/sarpras/ruang/${id}`, {
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
                    // Fallback
                    fetch(`/dashboard/sarpras/ruang/${id}`, {
                        method: 'DELETE',
                        headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
                    }).then(() => location.reload());
                }
            }
        }
    });
});
