/**
 * JavaScript Modul Bahan Habis Pakai (BHP)
 * Standar Baku SAE: Vanilla JS, Modal (z-index 99999), SweetAlert2,
 * EVENT DELEGATION (tabel dirender ulang live-table/realtime).
 */

document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('modalBhp');
    const btnOpen = document.getElementById('btnOpenModalBhp');
    const btnClose = document.getElementById('btnCloseModalBhp');
    const btnCancel = document.getElementById('btnCancelModalBhp');
    const form = document.getElementById('formBhp');
    const modalTitle = document.getElementById('modalBhpTitle');
    const methodInput = document.getElementById('bhpMethod');
    const wrapperStokTersedia = document.getElementById('wrapperStokTersediaBhp');

    function getCsrfToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        const input = document.querySelector('input[name="_token"]');
        return (meta ? meta.getAttribute('content') : '') || (input ? input.value : '');
    }

    function openModal(isEdit = false, data = {}) {
        if (!modal) return;
        modal.style.display = 'flex';

        if (isEdit) {
            modalTitle.textContent = 'Edit Bahan Habis Pakai';
            methodInput.value = 'PUT';
            form.action = '/dashboard/laboran/bhp/' + data.id;
            document.getElementById('bhpRuang').value = data.lab || '';
            document.getElementById('bhpKode').value = data.kode || '';
            document.getElementById('bhpNama').value = data.nama || '';
            document.getElementById('bhpStokTotal').value = data.stoktotal || 1;
            document.getElementById('bhpStokTersedia').value = data.stoktersedia || 1;
            document.getElementById('bhpSatuan').value = data.satuan || 'botol';
            document.getElementById('bhpKondisi').value = data.kondisi || 'baik';
            document.getElementById('bhpSpek').value = data.spek || '';
            document.getElementById('bhpRak').value = data.rak || '';
            document.getElementById('bhpExp').value = data.exp || '';
            document.getElementById('bhpTglPengadaan').value = data.tglpengadaan || '';
            document.getElementById('bhpTahunPengadaan').value = data.tahunpengadaan || '';
            const elSumber = document.getElementById('bhpSumberDana');
            if (elSumber) elSumber.value = data.sumber || 'BOS Reguler';
            if (wrapperStokTersedia) wrapperStokTersedia.style.display = 'block';
        } else {
            modalTitle.textContent = 'Tambah Bahan Habis Pakai';
            methodInput.value = 'POST';
            form.action = '/dashboard/laboran/bhp';
            form.reset();
            if (wrapperStokTersedia) wrapperStokTersedia.style.display = 'none';
        }
    }

    function closeModal() {
        if (!modal) return;
        modal.style.display = 'none';
    }

    if (btnOpen) btnOpen.addEventListener('click', function () { openModal(false); });
    if (btnClose) btnClose.addEventListener('click', closeModal);
    if (btnCancel) btnCancel.addEventListener('click', closeModal);
    if (modal) {
        modal.addEventListener('click', function (e) {
            if (e.target === modal) closeModal();
        });
    }

    // Tahun pengadaan otomatis dari tanggal
    const tglPengadaan = document.getElementById('bhpTglPengadaan');
    const tahunPengadaan = document.getElementById('bhpTahunPengadaan');
    if (tglPengadaan && tahunPengadaan) {
        tglPengadaan.addEventListener('change', function () {
            if (!this.value) return;
            const tahun = this.value.slice(0, 4);
            if (/^\d{4}$/.test(tahun)) tahunPengadaan.value = tahun;
        });
    }

    // =========================================================================
    // Edit & Hapus (event delegation — kebal render ulang tabel)
    // =========================================================================
    document.addEventListener('click', function (e) {
        const btnEdit = e.target.closest('.btn-edit-bhp');
        if (btnEdit) {
            openModal(true, {
                id: btnEdit.dataset.id,
                lab: btnEdit.dataset.lab,
                kode: btnEdit.dataset.kode,
                nama: btnEdit.dataset.nama,
                stoktotal: btnEdit.dataset.stoktotal,
                stoktersedia: btnEdit.dataset.stoktersedia,
                satuan: btnEdit.dataset.satuan,
                kondisi: btnEdit.dataset.kondisi,
                spek: btnEdit.dataset.spek,
                rak: btnEdit.dataset.rak,
                jurusan: btnEdit.dataset.jurusan,
                exp: btnEdit.dataset.exp,
                tglpengadaan: btnEdit.dataset.tglpengadaan,
                tahunpengadaan: btnEdit.dataset.tahunpengadaan,
                sumber: btnEdit.dataset.sumber,
            });
            return;
        }

        const btnDelete = e.target.closest('.btn-delete-bhp');
        if (btnDelete) {
            const id = btnDelete.dataset.id;
            const nama = btnDelete.dataset.nama || 'bahan ini';

            Swal.fire({
                title: 'Hapus Bahan?',
                text: 'Apakah Anda yakin ingin menghapus "' + nama + '"?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Ya, Hapus',
                cancelButtonText: 'Batal'
            }).then(function (res) {
                if (!res.isConfirmed) return;
                fetch('/dashboard/laboran/bhp/' + id, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': getCsrfToken(), 'Accept': 'application/json' }
                })
                .then(function (r) { return r.json(); })
                .then(function (res) {
                    if (res.status === 'success') {
                        Swal.fire('Berhasil!', res.message, 'success').then(function () { location.reload(); });
                    } else {
                        Swal.fire('Gagal', res.message || 'Terjadi kesalahan.', 'error');
                    }
                })
                .catch(function () { Swal.fire('Error', 'Gagal memproses permintaan.', 'error'); });
            });
        }
    });

    if (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();

            const submitBtn = form.querySelector('button[type="submit"]');
            const originalHtml = submitBtn ? submitBtn.innerHTML : '';
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Menyimpan...';
            }

            fetch(form.action, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                credentials: 'same-origin',
                body: new FormData(form)
            })
            .then(async function (res) {
                const data = await res.json().catch(function () { return {}; });
                if (!res.ok) throw new Error((data && data.message) || 'Terjadi kesalahan saat menyimpan.');
                return data;
            })
            .then(function (data) {
                closeModal();
                Swal.fire('Berhasil!', data.message || 'Data berhasil disimpan.', 'success')
                    .then(function () { location.reload(); });
            })
            .catch(function (err) {
                Swal.fire('Gagal', err.message || 'Terjadi kesalahan pada sistem.', 'error');
            })
            .finally(function () {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalHtml;
                }
            });
        });
    }

    // =========================================================================
    // Hapus Massal via Ceklist (event delegation + state persist)
    // =========================================================================
    const selectedIds = new Set();

    function syncBulkState() {
        const checked = document.querySelectorAll('.chk-item-bhp:checked').length;
        const countBulk = document.getElementById('countBulkBhp');
        const btnBulk = document.getElementById('btnBulkDeleteBhp');
        if (countBulk) countBulk.textContent = checked;
        if (btnBulk) {
            btnBulk.disabled = checked === 0;
            btnBulk.style.opacity = checked === 0 ? '0.5' : '1';
            btnBulk.style.cursor = checked === 0 ? 'not-allowed' : 'pointer';
        }
    }

    document.addEventListener('change', function (e) {
        const t = e.target;
        if (!t || !t.classList) return;

        if (t.id === 'chkAllBhp') {
            document.querySelectorAll('.chk-item-bhp').forEach(function (c) {
                c.checked = t.checked;
                if (t.checked) selectedIds.add(c.value); else selectedIds.delete(c.value);
            });
            syncBulkState();
            return;
        }

        if (t.classList.contains('chk-item-bhp')) {
            if (t.checked) selectedIds.add(t.value); else selectedIds.delete(t.value);
            const all = document.getElementById('chkAllBhp');
            const boxes = document.querySelectorAll('.chk-item-bhp');
            if (all) all.checked = boxes.length > 0 && Array.prototype.every.call(boxes, function (c) { return c.checked; });
            syncBulkState();
        }
    });

    document.addEventListener('click', function (e) {
        const btn = e.target.closest('#btnBulkDeleteBhp');
        if (!btn || btn.disabled) return;

        const ids = Array.from(document.querySelectorAll('.chk-item-bhp:checked')).map(function (c) { return c.value; });
        if (ids.length === 0) return;

        Swal.fire({
            title: 'Hapus ' + ids.length + ' Bahan?',
            text: 'Bahan yang sedang dipinjam akan dilewati otomatis.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then(function (res) {
            if (!res.isConfirmed) return;
            fetch('/dashboard/laboran/bhp/bulk-delete', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ ids: ids })
            })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (res.status === 'success') {
                    selectedIds.clear();
                    Swal.fire('Berhasil!', res.message, 'success').then(function () { location.reload(); });
                } else {
                    Swal.fire('Gagal', res.message || 'Terjadi kesalahan.', 'error');
                }
            })
            .catch(function () { Swal.fire('Error', 'Gagal memproses permintaan.', 'error'); });
        });
    });

    // Pulihkan ceklist setelah tabel dirender ulang
    function restoreChecks() {
        document.querySelectorAll('.chk-item-bhp').forEach(function (c) {
            c.checked = selectedIds.has(c.value);
        });
        const all = document.getElementById('chkAllBhp');
        const boxes = document.querySelectorAll('.chk-item-bhp');
        if (all) all.checked = boxes.length > 0 && Array.prototype.every.call(boxes, function (c) { return c.checked; });
        syncBulkState();
    }
    window.addEventListener('sae:tableRefreshed', restoreChecks);
    const tableBody = document.querySelector('.chk-item-bhp') ? document.querySelector('.chk-item-bhp').closest('table') : null;
    if (tableBody && typeof MutationObserver !== 'undefined') {
        new MutationObserver(restoreChecks).observe(tableBody, { childList: true, subtree: true });
    }

    // =========================================================================
    // Modal Import Excel
    // =========================================================================
    const modalImport = document.getElementById('modalImportBhp');
    const btnOpenImport = document.getElementById('btnOpenImportBhp');
    const btnCloseImport = document.getElementById('btnCloseModalImportBhp');
    const btnCancelImport = document.getElementById('btnCancelImportBhp');
    const formImport = document.getElementById('formImportBhp');

    function openImport() { if (modalImport) { if (formImport) formImport.reset(); modalImport.style.display = 'flex'; } }
    function closeImport() { if (modalImport) modalImport.style.display = 'none'; }

    if (btnOpenImport) btnOpenImport.addEventListener('click', openImport);
    if (btnCloseImport) btnCloseImport.addEventListener('click', closeImport);
    if (btnCancelImport) btnCancelImport.addEventListener('click', closeImport);
    if (modalImport) {
        modalImport.addEventListener('click', function (e) {
            if (e.target === modalImport) closeImport();
        });
    }

    if (formImport) {
        formImport.addEventListener('submit', function (e) {
            e.preventDefault();

            const fileInput = document.getElementById('fileImportBhp');
            if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
                Swal.fire('Berkas Belum Dipilih', 'Pilih berkas Excel/CSV terlebih dahulu.', 'error');
                return;
            }

            const submitBtn = document.getElementById('btnSubmitImportBhp');
            const originalHtml = submitBtn ? submitBtn.innerHTML : '';
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Mengimpor...';
            }

            fetch(formImport.action, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': getCsrfToken(), 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
                body: new FormData(formImport)
            })
            .then(function (res) {
                return res.text().then(function (text) {
                    let parsed = null;
                    try { parsed = JSON.parse(text); } catch (e) { parsed = null; }
                    if (parsed) return { ok: res.ok, data: parsed };
                    let msg = 'Server mengembalikan respons tidak valid (HTTP ' + res.status + ').';
                    if (res.status === 419) msg = 'Sesi halaman telah berakhir. Muat ulang halaman lalu ulangi unggahan.';
                    if (res.status === 413) msg = 'Ukuran berkas terlalu besar untuk server.';
                    if (res.status >= 500) msg = 'Terjadi kesalahan pada server saat memproses berkas.';
                    return { ok: false, data: { status: 'error', message: msg } };
                });
            })
            .then(function (result) {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalHtml;
                }
                const data = result.data || {};
                if (result.ok && data.status === 'success') {
                    closeImport();
                    Swal.fire('Import Berhasil', data.message || 'Data berhasil diimpor.', 'success')
                        .then(function () { window.location.reload(); });
                    return;
                }
                let errMsg = data.message || 'Terjadi kesalahan saat memproses berkas.';
                if (data.errors) {
                    const first = Object.values(data.errors)[0];
                    if (Array.isArray(first) && first.length > 0) errMsg = first[0];
                }
                Swal.fire('Gagal Import', errMsg, 'error');
            })
            .catch(function (err) {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalHtml;
                }
                Swal.fire('Kesalahan Sistem', err.message || 'Gagal mengunggah berkas.', 'error');
            });
        });
    }
});
