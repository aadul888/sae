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
        if (window.SAE && window.SAE.modal) window.SAE.modal.reset(modal);
        if (isEdit) {
            modalTitle.textContent = 'Edit Data Ruang';
            methodInput.value = 'PUT';
            form.action = `/dashboard/sarpras/ruang/${data.id}`;
            document.getElementById('ruangKode').value = data.kode || '';
            document.getElementById('ruangNama').value = data.nama || '';
            document.getElementById('ruangGedung').value = data.gedung || '';
            document.getElementById('ruangLantai').value = data.lantai || '';
            document.getElementById('ruangPanjang').value = data.panjang || '';
            document.getElementById('ruangLebar').value = data.lebar || '';
            document.getElementById('ruangLuas').value = data.luas || '';
            const ruangPj = document.getElementById('ruangPj');
            const ruangPjId = document.getElementById('ruangPjId');
            if (ruangPj) ruangPj.value = '';
            if (ruangPjId) ruangPjId.value = data.pj || '';
            if (data.pj && ruangPj && window.SAEPeopleAutocomplete) {
                window.SAEPeopleAutocomplete.setSelection(ruangPj, data.pj);
            }
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

    window.openModalRuang = (isEdit, data) => openModal(isEdit, data);
    window.closeModalRuang = closeModal;

    if (btnOpen) btnOpen.addEventListener('click', () => openModal(false));
    if (btnClose) btnClose.addEventListener('click', closeModal);
    if (btnCancel) btnCancel.addEventListener('click', closeModal);

    const inputPanjang = document.getElementById('ruangPanjang');
    const inputLebar = document.getElementById('ruangLebar');
    const inputLuas = document.getElementById('ruangLuas');

    function hitungLuasOtomatis() {
        if (!inputPanjang || !inputLebar || !inputLuas) return;
        const p = parseFloat(inputPanjang.value);
        const l = parseFloat(inputLebar.value);
        if (!isNaN(p) && !isNaN(l) && p > 0 && l > 0) {
            inputLuas.value = (p * l).toFixed(2);
        }
    }

    if (inputPanjang) inputPanjang.addEventListener('input', hitungLuasOtomatis);
    if (inputLebar) inputLebar.addEventListener('input', hitungLuasOtomatis);

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
                panjang: btnEdit.dataset.panjang,
                lebar: btnEdit.dataset.lebar,
                luas: btnEdit.dataset.luas,
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
                window.SAE.confirm("Apakah Anda yakin ingin menghapus ${nama}?", "Hapus Ruang?", 'warning', "Ya, Hapus", "Batal").then((res) => {
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
                window.SAE.confirm(`Apakah Anda yakin ingin menghapus ${nama}?`, 'Konfirmasi Hapus', 'danger', 'Ya, Hapus').then((ok) => {
                    if (ok) {
                        fetch(`/dashboard/sarpras/ruang/${id}`, {
                            method: 'DELETE',
                            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
                        }).then(() => location.reload());
                    }
                });
            }
        }
    });
});
