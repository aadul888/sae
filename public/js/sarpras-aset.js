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

    // Token CSRF dipakai bersama oleh hapus aset & import Excel.
    // WAJIB di scope terluar: sebelumnya berada di dalam blok if (btnDelete)
    // sehingga import melempar ReferenceError tanpa alert apa pun.
    function getCsrfToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        const input = document.querySelector('input[name="_token"]');
        return (meta ? meta.getAttribute('content') : '') || (input ? input.value : '');
    }

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
            const asetTgl = document.getElementById('asetTanggalPerolehan');
            if (asetTgl) asetTgl.value = data.tglperolehan || '';
            const asetNoSeri = document.getElementById('asetNoSeri');
            if (asetNoSeri) asetNoSeri.value = data.noseri || '';
            const asetHarga = document.getElementById('asetHarga');
            if (asetHarga) asetHarga.value = data.harga || '';
            document.getElementById('asetSumber').value = data.sumber || 'BOS Reguler';
            document.getElementById('asetKondisi').value = data.kondisi || 'baik';
            document.getElementById('asetRuang').value = data.ruang || '';
            const asetJurusan = document.getElementById('asetJurusan');
            if (asetJurusan) asetJurusan.value = data.jurusan || '';
            document.getElementById('asetJumlah').value = data.jumlah || 1;
            document.getElementById('asetSatuan').value = data.satuan || 'unit';
            document.getElementById('asetStatus').value = data.status || 'tersedia';

            const jBaik = document.getElementById('asetJumlahBaik');
            const jRR = document.getElementById('asetJumlahRusakRingan');
            const jRB = document.getElementById('asetJumlahRusakBerat');
            const jml = parseInt(data.jumlah) || 1;
            if (jBaik) jBaik.value = data.baik !== undefined ? data.baik : (data.kondisi === 'baik' ? jml : 0);
            if (jRR) jRR.value = data.rr !== undefined ? data.rr : (data.kondisi === 'rusak_ringan' ? jml : 0);
            if (jRB) jRB.value = data.rb !== undefined ? data.rb : (data.kondisi === 'rusak_berat' ? jml : 0);
            syncRincianKondisi();
        } else {
            modalTitle.textContent = 'Tambah Aset Sarpras';
            methodInput.value = 'POST';
            form.action = '/dashboard/sarpras/aset';
            form.reset();
            const jBaik = document.getElementById('asetJumlahBaik');
            const jRR = document.getElementById('asetJumlahRusakRingan');
            const jRB = document.getElementById('asetJumlahRusakBerat');
            if (jBaik) jBaik.value = 1;
            if (jRR) jRR.value = 0;
            if (jRB) jRB.value = 0;
            syncRincianKondisi();
        }
    }

    function closeModal() {
        if (modal) modal.style.display = 'none';
    }

    window.openModalAset = (isEdit, data) => openModal(isEdit, data);
    window.closeModalAset = closeModal;

    const inputJumlah = document.getElementById('asetJumlah');
    const inputTglPerolehan = document.getElementById('asetTanggalPerolehan');
    const inputTahun = document.getElementById('asetTahun');
    const elRincianSum = document.getElementById('asetRincianSum');
    const elRincianTotal = document.getElementById('asetRincianTotal');
    const btnSemuaBaik = document.getElementById('btnAsetSemuaBaik');

    function syncRincianKondisi() {
        if (!elRincianSum || !elRincianTotal) return;
        const total = parseInt(inputJumlah?.value) || 0;
        const b = parseInt(document.getElementById('asetJumlahBaik')?.value) || 0;
        const rr = parseInt(document.getElementById('asetJumlahRusakRingan')?.value) || 0;
        const rb = parseInt(document.getElementById('asetJumlahRusakBerat')?.value) || 0;
        const sum = b + rr + rb;

        elRincianTotal.textContent = total;
        elRincianSum.textContent = sum;

        if (sum > total) {
            elRincianSum.style.color = 'var(--danger, #ef4444)';
        } else if (sum === total && total > 0) {
            elRincianSum.style.color = 'var(--success, #10b981)';
            const elKondisi = document.getElementById('asetKondisi');
            if (elKondisi) {
                if (rb >= b && rb >= rr && rb > 0) elKondisi.value = 'rusak_berat';
                else if (rr > b && rr > rb) elKondisi.value = 'rusak_ringan';
                else elKondisi.value = 'baik';
            }
        } else {
            elRincianSum.style.color = 'var(--text-muted)';
        }
    }

    if (inputTglPerolehan && inputTahun) {
        inputTglPerolehan.addEventListener('change', function () {
            if (this.value) {
                const parts = this.value.split('-');
                if (parts[0]) inputTahun.value = parts[0];
            }
        });
    }

    if (inputJumlah) {
        inputJumlah.addEventListener('input', syncRincianKondisi);
    }

    document.querySelectorAll('.aset-jumlah-kondisi').forEach(function (inp) {
        inp.addEventListener('input', syncRincianKondisi);
    });

    if (btnSemuaBaik) {
        btnSemuaBaik.addEventListener('click', function () {
            const total = parseInt(inputJumlah?.value) || 1;
            const jBaik = document.getElementById('asetJumlahBaik');
            const jRR = document.getElementById('asetJumlahRusakRingan');
            const jRB = document.getElementById('asetJumlahRusakBerat');
            if (jBaik) jBaik.value = total;
            if (jRR) jRR.value = 0;
            if (jRB) jRB.value = 0;
            const elKondisi = document.getElementById('asetKondisi');
            if (elKondisi) elKondisi.value = 'baik';
            syncRincianKondisi();
        });
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
                tglperolehan: btnEdit.dataset.tglperolehan,
                noseri: btnEdit.dataset.noseri,
                harga: btnEdit.dataset.harga,
                sumber: btnEdit.dataset.sumber,
                kondisi: btnEdit.dataset.kondisi,
                baik: btnEdit.dataset.baik,
                rr: btnEdit.dataset.rr,
                rb: btnEdit.dataset.rb,
                ruang: btnEdit.dataset.ruang,
                jurusan: btnEdit.dataset.jurusan,
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
            const csrfToken = getCsrfToken();

            if (window.Swal) {
                window.SAE.confirm("Apakah Anda yakin ingin menghapus data ${nama}?", "Hapus Aset?", 'warning', "Ya, Hapus", "Batal").then((res) => {
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

    // =========================================================================
    // Modal Import Aset via Excel (Point 7)
    // =========================================================================
    const modalImportAset = document.getElementById('modalImportAset');
    const btnOpenImportAset = document.getElementById('btnOpenImportAset');
    const btnCloseImportAset = document.getElementById('btnCloseModalImportAset');
    const btnCancelImportAset = document.getElementById('btnCancelModalImportAset');
    const formImportAset = document.getElementById('formImportAset');

    function openModalImportAset() {
        if (!modalImportAset) return;
        if (formImportAset) formImportAset.reset();
        modalImportAset.style.display = 'flex';
    }

    function closeModalImportAset() {
        if (!modalImportAset) return;
        modalImportAset.style.display = 'none';
    }

    if (btnOpenImportAset) btnOpenImportAset.addEventListener('click', openModalImportAset);
    if (btnCloseImportAset) btnCloseImportAset.addEventListener('click', closeModalImportAset);
    if (btnCancelImportAset) btnCancelImportAset.addEventListener('click', closeModalImportAset);
    if (modalImportAset) {
        modalImportAset.addEventListener('click', function (e) {
            if (e.target === modalImportAset) closeModalImportAset();
        });
    }

    if (formImportAset) {
        formImportAset.addEventListener('submit', function (e) {
            e.preventDefault();

            const submitBtn = document.getElementById('btnSubmitImportAset');
            const originalHtml = submitBtn ? submitBtn.innerHTML : '';

            function restoreBtn() {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalHtml;
                }
            }

            function showError(title, msg) {
                {
                    Swal.fire({ title: title, text: msg, icon: 'error' });
                }
            }

            const fileInput = document.getElementById('fileImportAset');
            if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
                showError('Berkas Belum Dipilih', 'Pilih berkas Excel (.xlsx/.xls) atau CSV terlebih dahulu.');
                return;
            }

            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Mengunggah &amp; Mengimpor...';
            }

            let formData;
            try {
                formData = new FormData(formImportAset);
            } catch (err) {
                restoreBtn();
                showError('Kesalahan Sistem', 'Gagal menyiapkan data unggahan.');
                return;
            }

            fetch(formImportAset.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin',
                body: formData
            })
            .then(function (res) {
                return res.text().then(function (text) {
                    let parsed = null;
                    try {
                        parsed = JSON.parse(text);
                    } catch (e) {
                        parsed = null;
                    }

                    if (parsed) {
                        return { ok: res.ok, status: res.status, data: parsed };
                    }

                    let msg = 'Server mengembalikan respons tidak valid (HTTP ' + res.status + ').';
                    if (res.status === 419) {
                        msg = 'Sesi halaman telah berakhir. Muat ulang halaman lalu ulangi unggahan.';
                    } else if (res.status === 413) {
                        msg = 'Ukuran berkas terlalu besar untuk server. Perkecil berkas lalu ulangi.';
                    } else if (res.status >= 500) {
                        msg = 'Terjadi kesalahan pada server saat memproses berkas. Periksa format berkas Anda.';
                    }
                    return { ok: false, status: res.status, data: { status: 'error', message: msg } };
                });
            })
            .then(function (result) {
                restoreBtn();
                const data = result.data || {};

                if (result.ok && data.status === 'success') {
                    closeModalImportAset();
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            title: 'Import Berhasil',
                            text: data.message || 'Data berhasil diimpor.',
                            icon: 'success'
                        }).then(function () {
                            window.location.reload();
                        });
                    } else {
                        window.SAE.alert(data.message || 'Data berhasil diimpor.', 'Informasi', 'info');
                        window.location.reload();
                    }
                    return;
                }

                let errMsg = data.message || 'Terjadi kesalahan saat memproses berkas.';
                if (data.errors) {
                    const first = Object.values(data.errors)[0];
                    if (Array.isArray(first) && first.length > 0) errMsg = first[0];
                    else if (typeof first === 'string') errMsg = first;
                }
                showError('Gagal Import', errMsg);
            })
            .catch(function (err) {
                restoreBtn();
                showError('Kesalahan Sistem', err.message || 'Gagal mengunggah berkas. Periksa koneksi lalu coba lagi.');
            });
        });
    }
});
