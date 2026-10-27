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

    const wrapperAsetSarpras = document.getElementById('wrapperAsetSarpras');
    const selectAsetSarpras = document.getElementById('selectAsetSarpras');

    // Token CSRF dipakai bersama oleh hapus item & import Excel.
    // WAJIB dideklarasikan di scope terluar: sebelumnya berada di dalam blok
    // if (btnDelete) sehingga import melempar ReferenceError tanpa alert.
    function getCsrfToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        const input = document.querySelector('input[name="_token"]');
        return (meta ? meta.getAttribute('content') : '') || (input ? input.value : '');
    }

    function openModal(isEdit = false, data = {}) {
        if (!modal) return;
        modal.style.display = 'flex';
        if (selectAsetSarpras) selectAsetSarpras.value = '';

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
            document.getElementById('labTglPengadaan').value = data.tglpengadaan || '';
            document.getElementById('labTahunPengadaan').value = data.tahunpengadaan || '';
            const elSumber = document.getElementById('labSumberDana');
            if (elSumber) elSumber.value = data.sumber || 'BOS Reguler';

            // Rincian kondisi per jumlah
            setJumlahKondisi({
                baik: data.jumlahbaik,
                rusak_ringan: data.jumlahrusakringan,
                rusak_berat: data.jumlahrusakberat,
                kedaluwarsa: data.jumlahkedaluwarsa
            });

            const labJurusan = document.getElementById('labJurusan');
            if (labJurusan) labJurusan.value = data.jurusan || '';
            if (wrapperStokTersedia) wrapperStokTersedia.style.display = 'block';
            if (wrapperAsetSarpras) wrapperAsetSarpras.style.display = 'none';
        } else {
            modalTitle.textContent = 'Tambah Alat / Bahan Lab';
            methodInput.value = 'POST';
            form.action = '/dashboard/laboran/inventaris';
            form.reset();
            setJumlahKondisi({ baik: 0, rusak_ringan: 0, rusak_berat: 0, kedaluwarsa: 0 });
            if (wrapperStokTersedia) wrapperStokTersedia.style.display = 'none';
            if (wrapperAsetSarpras) wrapperAsetSarpras.style.display = 'block';
        }

        syncRincianKondisi();
    }

    // -------------------------------------------------------------------------
    // Rincian kondisi per jumlah unit
    // -------------------------------------------------------------------------
    function setJumlahKondisi(d) {
        const map = {
            labJumlahBaik: d.baik,
            labJumlahRusakRingan: d.rusak_ringan,
            labJumlahRusakBerat: d.rusak_berat,
            labJumlahKedaluwarsa: d.kedaluwarsa
        };
        Object.keys(map).forEach(function (id) {
            const el = document.getElementById(id);
            if (el) el.value = map[id] != null && map[id] !== '' ? map[id] : 0;
        });
    }

    function getJumlahKondisi() {
        const read = function (id) {
            const el = document.getElementById(id);
            return el ? (parseFloat(el.value) || 0) : 0;
        };
        return {
            baik: read('labJumlahBaik'),
            rusak_ringan: read('labJumlahRusakRingan'),
            rusak_berat: read('labJumlahRusakBerat'),
            kedaluwarsa: read('labJumlahKedaluwarsa')
        };
    }

    function syncRincianKondisi() {
        const totalEl = document.getElementById('labStokTotal');
        const total = totalEl ? (parseFloat(totalEl.value) || 0) : 0;
        const d = getJumlahKondisi();
        const sum = d.baik + d.rusak_ringan + d.rusak_berat + d.kedaluwarsa;

        const sumEl = document.getElementById('labRincianSum');
        const totalHintEl = document.getElementById('labRincianTotal');
        if (sumEl) sumEl.textContent = sum;
        if (totalHintEl) totalHintEl.textContent = total;

        // Kondisi keseluruhan otomatis mengikuti jumlah terbanyak
        const kondisiEl = document.getElementById('labKondisi');
        if (kondisiEl && sum > 0) {
            let kondisi = 'baik';
            let max = d.baik;
            if (d.rusak_ringan > max) { kondisi = 'rusak_ringan'; max = d.rusak_ringan; }
            if (d.rusak_berat >= max && d.rusak_berat > 0) { kondisi = 'rusak_berat'; max = d.rusak_berat; }
            if (d.kedaluwarsa > max) { kondisi = 'kedaluwarsa'; }
            kondisiEl.value = kondisi;
        }

        // Peringatan visual bila rincian tidak sama dengan stok total
        const hint = document.getElementById('labRincianHint');
        if (hint) {
            if (sum > 0 && total > 0 && Math.abs(sum - total) > 0.001) {
                hint.style.color = 'var(--warning, #f59e0b)';
                hint.innerHTML = 'Total rincian <strong>' + sum + '</strong> / <strong>' + total +
                    '</strong> &mdash; tidak sama dengan stok total.';
            } else {
                hint.style.color = 'var(--text-muted)';
                hint.innerHTML = 'Total rincian: <strong id="labRincianSum">' + sum +
                    '</strong> / <strong id="labRincianTotal">' + total + '</strong>';
            }
        }
    }

    document.querySelectorAll('.lab-jumlah-kondisi').forEach(function (el) {
        el.addEventListener('input', syncRincianKondisi);
    });

    const labStokTotalEl = document.getElementById('labStokTotal');
    if (labStokTotalEl) {
        labStokTotalEl.addEventListener('input', syncRincianKondisi);
    }

    const btnIsiSemuaBaik = document.getElementById('btnIsiSemuaBaik');
    if (btnIsiSemuaBaik) {
        btnIsiSemuaBaik.addEventListener('click', function () {
            const total = labStokTotalEl ? (parseFloat(labStokTotalEl.value) || 0) : 0;
            setJumlahKondisi({ baik: total, rusak_ringan: 0, rusak_berat: 0, kedaluwarsa: 0 });
            syncRincianKondisi();
        });
    }

    // Tahun pengadaan terisi otomatis dari tanggal pengadaan.
    const labTglPengadaan = document.getElementById('labTglPengadaan');
    const labTahunPengadaan = document.getElementById('labTahunPengadaan');
    if (labTglPengadaan && labTahunPengadaan) {
        labTglPengadaan.addEventListener('change', function () {
            if (!this.value) return;
            const tahun = this.value.slice(0, 4);
            if (/^\d{4}$/.test(tahun)) labTahunPengadaan.value = tahun;
        });
    }

    // Auto-fill form saat memilih aset dari Sarpras & Aset
    if (selectAsetSarpras) {
        selectAsetSarpras.addEventListener('change', function () {
            const selectedOpt = this.options[this.selectedIndex];
            if (!selectedOpt || !selectedOpt.value) return;

            const nama = selectedOpt.getAttribute('data-nama') || '';
            const kode = selectedOpt.getAttribute('data-kode') || '';
            const ruang = selectedOpt.getAttribute('data-ruang') || '';
            const satuan = selectedOpt.getAttribute('data-satuan') || 'unit';
            const kondisi = selectedOpt.getAttribute('data-kondisi') || 'baik';
            const spek = selectedOpt.getAttribute('data-spek') || '';

            if (nama) document.getElementById('labNama').value = nama;
            if (kode) document.getElementById('labKode').value = kode + '-LAB';
            if (satuan) document.getElementById('labSatuan').value = satuan;
            if (kondisi) document.getElementById('labKondisi').value = kondisi;
            if (spek) document.getElementById('labSpek').value = spek;

            if (ruang) {
                const ruangSelect = document.getElementById('labRuang');
                for (let i = 0; i < ruangSelect.options.length; i++) {
                    if (ruangSelect.options[i].value === ruang) {
                        ruangSelect.selectedIndex = i;
                        break;
                    }
                }
            }
        });
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
                tglpengadaan: btnEdit.dataset.tglpengadaan,
                tahunpengadaan: btnEdit.dataset.tahunpengadaan,
                sumber: btnEdit.dataset.sumber,
                jurusan: btnEdit.dataset.jurusan,
            };
            openModal(true, data);
        }

        // Event delegation tombol hapus
        const btnDelete = e.target.closest('.btn-delete-lab');
        if (btnDelete) {
            const id = btnDelete.dataset.id;
            const nama = btnDelete.dataset.nama || 'item ini';
            const csrfToken = getCsrfToken();

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

    // =========================================================================
    // Modal Import via Excel (Point 7)
    // =========================================================================
    const modalImport = document.getElementById('modalImportInventaris');
    const btnOpenImport = document.getElementById('btnOpenImportInventaris');
    const btnCloseImport = document.getElementById('btnCloseModalImport');
    const btnCancelImport = document.getElementById('btnCancelModalImport');
    const formImport = document.getElementById('formImportInventaris');

    function openModalImport() {
        if (!modalImport) return;
        if (formImport) formImport.reset();
        modalImport.style.display = 'flex';
    }

    function closeModalImport() {
        if (!modalImport) return;
        modalImport.style.display = 'none';
    }

    if (btnOpenImport) btnOpenImport.addEventListener('click', openModalImport);
    if (btnCloseImport) btnCloseImport.addEventListener('click', closeModalImport);
    if (btnCancelImport) btnCancelImport.addEventListener('click', closeModalImport);
    if (modalImport) {
        modalImport.addEventListener('click', function (e) {
            if (e.target === modalImport) closeModalImport();
        });
    }

    // =========================================================================
    // Hapus Massal via Ceklist (Bulk Delete)
    //
    // Seluruh handler memakai EVENT DELEGATION di document karena tabel ini
    // dirender ulang oleh engine live-table (search/filter/paginasi/realtime).
    // Listener langsung pada elemen akan hilang saat DOM ditukar; delegasi tetap
    // hidup. State ceklist disimpan agar terpulihkan setelah refresh tabel.
    // =========================================================================
    const selectedLabIds = new Set();

    function syncBulkState() {
        const checked = document.querySelectorAll('.chk-item-lab:checked').length;
        const countBulk = document.getElementById('countBulkLab');
        const btnBulk = document.getElementById('btnBulkDeleteLab');
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

        if (t.id === 'chkAllLab') {
            document.querySelectorAll('.chk-item-lab').forEach(function (c) {
                c.checked = t.checked;
                if (t.checked) selectedLabIds.add(c.value); else selectedLabIds.delete(c.value);
            });
            syncBulkState();
            return;
        }

        if (t.classList.contains('chk-item-lab')) {
            if (t.checked) selectedLabIds.add(t.value); else selectedLabIds.delete(t.value);
            const all = document.getElementById('chkAllLab');
            const boxes = document.querySelectorAll('.chk-item-lab');
            if (all) all.checked = boxes.length > 0 && Array.prototype.every.call(boxes, function (c) { return c.checked; });
            syncBulkState();
        }
    });

    document.addEventListener('click', function (e) {
        const btn = e.target.closest('#btnBulkDeleteLab');
        if (!btn || btn.disabled) return;

        const ids = Array.from(document.querySelectorAll('.chk-item-lab:checked')).map(function (c) { return c.value; });
        if (ids.length === 0) return;

        const doBulk = function () {
            fetch('/dashboard/laboran/inventaris/bulk-delete', {
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
                    selectedLabIds.clear();
                    Swal.fire('Berhasil!', res.message, 'success').then(function () { location.reload(); });
                } else {
                    Swal.fire('Gagal', res.message || 'Terjadi kesalahan.', 'error');
                }
            })
            .catch(function () {
                Swal.fire('Error', 'Gagal memproses permintaan.', 'error');
            });
        };

        Swal.fire({
            title: 'Hapus ' + ids.length + ' Item Lab?',
            text: 'Item yang sedang dipinjam akan dilewati otomatis.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then(function (res) {
            if (res.isConfirmed) doBulk();
        });
    });

    // Pulihkan ceklist setelah tabel dirender ulang (live search / filter /
    // paginasi / realtime SSE menukar innerHTML #tableDataContainer).
    // Juga amankan saat realtime menukar tabel tanpa dispatch event:
    // MutationObserver menangkap perubahan tbody dan memulihkan state.
    const restoreChecks = function () {
        document.querySelectorAll('.chk-item-lab').forEach(function (c) {
            c.checked = selectedLabIds.has(c.value);
        });
        const all = document.getElementById('chkAllLab');
        const boxes = document.querySelectorAll('.chk-item-lab');
        if (all) all.checked = boxes.length > 0 && Array.prototype.every.call(boxes, function (c) { return c.checked; });
        syncBulkState();
    };

    window.addEventListener('sae:tableRefreshed', restoreChecks);

    const tableBody = document.querySelector('.chk-item-lab') ? document.querySelector('.chk-item-lab').closest('tbody') : null;
    if (tableBody && typeof MutationObserver !== 'undefined') {
        new MutationObserver(restoreChecks).observe(tableBody.closest('table') || tableBody, { childList: true, subtree: true });
    }

    if (formImport) {
        formImport.addEventListener('submit', function (e) {
            e.preventDefault();

            const submitBtn = document.getElementById('btnSubmitImport');
            const originalHtml = submitBtn ? submitBtn.innerHTML : '';

            function restoreBtn() {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalHtml;
                }
            }

            function showError(title, msg) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ title: title, text: msg, icon: 'error' });
                } else {
                    alert(title + ': ' + msg);
                }
            }

            const fileInput = document.getElementById('fileImportInventaris');
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
                formData = new FormData(formImport);
            } catch (err) {
                restoreBtn();
                showError('Kesalahan Sistem', 'Gagal menyiapkan data unggahan.');
                return;
            }

            fetch(formImport.action, {
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
                // Server dapat membalas HTML (419/500/redirect) alih-alih JSON.
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

                    // Bukan JSON: teruskan pesan berbasis status HTTP.
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
                    closeModalImport();
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            title: 'Import Berhasil',
                            text: data.message || 'Data berhasil diimpor.',
                            icon: 'success'
                        }).then(function () {
                            window.location.reload();
                        });
                    } else {
                        alert(data.message || 'Data berhasil diimpor.');
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
