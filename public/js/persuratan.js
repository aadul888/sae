/**
 * Modul JavaScript: Persuratan & Arsip Digital (SAE Standardized)
 * Menggunakan SweetAlert2 dan event delegation terpisah dari Blade.
 */
document.addEventListener('DOMContentLoaded', () => {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    // Modal Form Elements
    const modalForm = document.getElementById('modalFormItem');
    const btnOpenCreate = document.getElementById('btnOpenCreateModal');
    const btnCloseModal = document.getElementById('btnCloseModal');
    const btnCancelModal = document.getElementById('btnCancelModal');
    const mainForm = document.getElementById('mainFormItem');
    const modalTitle = document.getElementById('modalTitle');
    const formItemId = document.getElementById('formItemId');

    const inputNomorSurat = document.getElementById('inputNomorSurat');
    const inputJenisSurat = document.getElementById('inputJenisSurat');
    const inputPerihal = document.getElementById('inputPerihal');
    const inputPengirim = document.getElementById('inputPengirim');
    const inputTujuan = document.getElementById('inputTujuan');
    const inputTanggalSurat = document.getElementById('inputTanggalSurat');
    const inputTanggalDiterima = document.getElementById('inputTanggalDiterima');
    const inputStatus = document.getElementById('inputStatus');
    const inputKeterangan = document.getElementById('inputKeterangan');
    const btnSaveModal = document.getElementById('btnSaveModal');

    // Modal Detail Elements
    const modalDetail = document.getElementById('modalDetailItem');
    const btnCloseDetailModal = document.getElementById('btnCloseDetailModal');
    const btnCloseDetailBtn = document.getElementById('btnCloseDetailBtn');
    const detailContent = document.getElementById('detailContent');

    // Toast helper
    const showToast = (message, type = 'success') => {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: type === 'danger' ? 'error' : type,
                title: message,
                showConfirmButton: false,
                timer: 3000
            });
        }
    };

    // Close Modals
    const closeFormModal = () => {
        if (modalForm) modalForm.style.display = 'none';
    };

    const closeDetailModal = () => {
        if (modalDetail) modalDetail.style.display = 'none';
    };

    if (btnCloseModal) btnCloseModal.addEventListener('click', closeFormModal);
    if (btnCancelModal) btnCancelModal.addEventListener('click', closeFormModal);
    if (modalForm) {
        modalForm.addEventListener('click', (e) => {
            if (e.target === modalForm) closeFormModal();
        });
    }

    if (btnCloseDetailModal) btnCloseDetailModal.addEventListener('click', closeDetailModal);
    if (btnCloseDetailBtn) btnCloseDetailBtn.addEventListener('click', closeDetailModal);
    if (modalDetail) {
        modalDetail.addEventListener('click', (e) => {
            if (e.target === modalDetail) closeDetailModal();
        });
    }

    // Open Modal Create
    if (btnOpenCreate && modalForm) {
        btnOpenCreate.addEventListener('click', () => {
            if (mainForm) mainForm.reset();
            if (formItemId) formItemId.value = '';
            if (modalTitle) {
                modalTitle.innerHTML = '<i class="fas fa-plus-circle text-primary"></i> Catat Surat Baru';
            }
            // Set default date to today
            if (inputTanggalSurat) {
                inputTanggalSurat.value = new Date().toISOString().split('T')[0];
            }
            if (inputStatus) {
                inputStatus.value = 'menunggu_disposisi';
            }
            modalForm.style.display = 'flex';
            inputNomorSurat?.focus();
        });
    }

    // Submit Form (Create / Update)
    if (mainForm) {
        mainForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            const id = formItemId ? formItemId.value : '';
            const isEdit = !!id;
            const url = isEdit ? `/dashboard/persuratan/${id}` : '/dashboard/persuratan';
            const method = isEdit ? 'PUT' : 'POST';

            const payload = {
                nomor_surat: inputNomorSurat?.value || '',
                jenis_surat: inputJenisSurat?.value || 'masuk',
                perihal: inputPerihal?.value || '',
                pengirim_asal: inputPengirim?.value || '',
                tujuan_penerima: inputTujuan?.value || '',
                tanggal_surat: inputTanggalSurat?.value || '',
                tanggal_diterima: inputTanggalDiterima?.value || null,
                status: inputStatus?.value || 'menunggu_disposisi',
                keterangan: inputKeterangan?.value || '',
            };

            if (btnSaveModal) {
                btnSaveModal.disabled = true;
                btnSaveModal.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Menyimpan...';
            }

            try {
                const res = await fetch(url, {
                    method: method,
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify(payload)
                });

                const data = await res.json();

                if (res.ok && data.status === 'success') {
                    closeFormModal();
                    showToast(data.message || 'Data surat berhasil disimpan.');
                    setTimeout(() => window.location.reload(), 600);
                } else {
                    const errorMsg = data.message || (data.errors ? Object.values(data.errors).flat().join('\n') : 'Gagal menyimpan data.');
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Peringatan Validasi',
                            text: errorMsg,
                            confirmButtonColor: '#2563eb'
                        });
                    } else {
                        alert(errorMsg);
                    }
                }
            } catch (err) {
                showToast('Terjadi kesalahan koneksi server.', 'danger');
            } finally {
                if (btnSaveModal) {
                    btnSaveModal.disabled = false;
                    btnSaveModal.innerHTML = '<i class="fas fa-check me-1"></i> Simpan Surat';
                }
            }
        });
    }

    // Event Delegation: Detail, Edit, Hapus
    document.addEventListener('click', async (e) => {
        // 1. Detail Surat
        const detailBtn = e.target.closest('.btn-detail-row');
        if (detailBtn) {
            const id = detailBtn.dataset.id;
            try {
                const res = await fetch(`/dashboard/persuratan/${id}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    }
                });
                const resData = await res.json();
                if (resData.status === 'success') {
                    const item = resData.data;
                    if (detailContent) {
                        detailContent.innerHTML = `
                            <div style="background: var(--bg-hover); padding: 14px; border-radius: 10px; margin-bottom: 14px;">
                                <div style="display: flex; gap: 8px; margin-bottom: 8px;">
                                    ${resData.jenis_badge || ''}
                                    ${resData.status_badge || ''}
                                </div>
                                <div style="font-size: 1.05rem; font-weight: 700; color: var(--text-color); margin-bottom: 4px;">
                                    ${item.perihal || '-'}
                                </div>
                                <div style="font-family: monospace; font-size: 0.85rem; color: var(--primary);">
                                    Nomor: ${item.nomor_surat || '-'}
                                </div>
                            </div>
                            <table style="width: 100%; border-collapse: collapse; font-size: 0.84rem;">
                                <tr>
                                    <td style="padding: 6px 0; color: var(--text-muted); width: 140px;">Pengirim / Asal:</td>
                                    <td style="padding: 6px 0; font-weight: 600; color: var(--text-color);">${item.pengirim_asal || '-'}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 6px 0; color: var(--text-muted);">Tujuan / Penerima:</td>
                                    <td style="padding: 6px 0; font-weight: 600; color: var(--text-color);">${item.tujuan_penerima || '-'}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 6px 0; color: var(--text-muted);">Tanggal Surat:</td>
                                    <td style="padding: 6px 0; color: var(--text-color);">${item.tanggal_surat || '-'}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 6px 0; color: var(--text-muted);">Tanggal Diterima:</td>
                                    <td style="padding: 6px 0; color: var(--text-color);">${item.tanggal_diterima || '-'}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 6px 0; color: var(--text-muted);">Dicatat Oleh:</td>
                                    <td style="padding: 6px 0; color: var(--text-color);">${item.created_by || '-'}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 6px 0; color: var(--text-muted); vertical-align: top;">Catatan / Disposisi:</td>
                                    <td style="padding: 6px 0; color: var(--text-color); white-space: pre-wrap;">${item.keterangan || 'Tidak ada catatan.'}</td>
                                </tr>
                            </table>
                        `;

                        const actionExtra = document.getElementById('detailActionExtra');
                        if (actionExtra) {
                            if (item.jenis_surat === 'masuk') {
                                actionExtra.innerHTML = `
                                    <button type="button" class="btn btn-primary btn-sm btn-open-disp" data-id="${item.id}" data-nomor="${item.nomor_surat}" data-perihal="${item.perihal}">
                                        <i class="fas fa-clipboard-check me-1"></i> Beri Disposisi
                                    </button>
                                    <a href="/dashboard/persuratan/${item.id}/disposisi/cetak" target="_blank" class="btn btn-outline btn-sm">
                                        <i class="fas fa-print me-1"></i> Cetak Disposisi
                                    </a>
                                `;
                            } else {
                                actionExtra.innerHTML = '';
                            }
                        }
                    }
                    if (modalDetail) modalDetail.style.display = 'flex';
                }
            } catch (err) {
                showToast('Gagal memuat rincian surat.', 'danger');
            }
            return;
        }

        // 2. Edit Surat
        const editBtn = e.target.closest('.btn-edit-row');
        if (editBtn) {
            const id = editBtn.dataset.id;
            try {
                const res = await fetch(`/dashboard/persuratan/${id}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    }
                });
                const resData = await res.json();
                if (resData.status === 'success') {
                    const item = resData.data;
                    if (formItemId) formItemId.value = item.id;
                    if (inputNomorSurat) inputNomorSurat.value = item.nomor_surat || '';
                    if (inputJenisSurat) inputJenisSurat.value = item.jenis_surat || 'masuk';
                    if (inputPerihal) inputPerihal.value = item.perihal || '';
                    if (inputPengirim) inputPengirim.value = item.pengirim_asal || '';
                    if (inputTujuan) inputTujuan.value = item.tujuan_penerima || '';
                    if (inputTanggalSurat) inputTanggalSurat.value = (item.tanggal_surat || '').substring(0, 10);
                    if (inputTanggalDiterima) inputTanggalDiterima.value = item.tanggal_diterima ? item.tanggal_diterima.substring(0, 10) : '';
                    if (inputStatus) inputStatus.value = item.status || 'menunggu_disposisi';
                    if (inputKeterangan) inputKeterangan.value = item.keterangan || '';

                    if (modalTitle) {
                        modalTitle.innerHTML = '<i class="fas fa-pen-to-square text-primary"></i> Edit Data Surat';
                    }
                    if (modalForm) modalForm.style.display = 'flex';
                }
            } catch (err) {
                showToast('Gagal mengambil data surat untuk diedit.', 'danger');
            }
            return;
        }

        // 3. Hapus Surat
        const deleteBtn = e.target.closest('.btn-delete-row');
        if (deleteBtn) {
            const id = deleteBtn.dataset.id;
            const nomor = deleteBtn.dataset.nomor || 'surat ini';

            if (typeof Swal === 'undefined') {
                if (!confirm(`Yakin ingin menghapus arsip surat "${nomor}"?`)) return;
            } else {
                const result = await Swal.fire({
                    title: 'Hapus Arsip Surat?',
                    text: `Yakin ingin menghapus arsip surat nomor "${nomor}"? Tindakan ini tidak dapat dibatalkan.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ef4444',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Ya, Hapus!',
                    cancelButtonText: 'Batal'
                });
                if (!result.isConfirmed) return;
            }

            try {
                const res = await fetch(`/dashboard/persuratan/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    }
                });
                const data = await res.json();
                if (data.status === 'success') {
                    showToast(data.message || 'Arsip surat berhasil dihapus.');
                    setTimeout(() => window.location.reload(), 600);
                } else {
                    showToast(data.message || 'Gagal menghapus data.', 'danger');
                }
            } catch (err) {
                showToast('Terjadi kesalahan koneksi server.', 'danger');
            }
        }

        // 4. Buka Form Disposisi dari modal detail
        const openDispBtn = e.target.closest('.btn-open-disp');
        if (openDispBtn) {
            const suratId = openDispBtn.dataset.id;
            const nomor = openDispBtn.dataset.nomor;
            const perihal = openDispBtn.dataset.perihal;

            const modalDisp = document.getElementById('modalDisposisiItem');
            const dispSuratId = document.getElementById('disposisiSuratId');
            const dispSuratNomor = document.getElementById('dispSuratNomor');
            const dispSuratPerihal = document.getElementById('dispSuratPerihal');

            if (dispSuratId) dispSuratId.value = suratId;
            if (dispSuratNomor) dispSuratNomor.innerText = `Nomor: ${nomor}`;
            if (dispSuratPerihal) dispSuratPerihal.innerText = `Perihal: ${perihal}`;

            if (modalDetail) modalDetail.style.display = 'none';
            if (modalDisp) modalDisp.style.display = 'flex';
        }
    });

    // Close Disposisi Modal
    const modalDisp = document.getElementById('modalDisposisiItem');
    const btnCloseDisp = document.getElementById('btnCloseDisposisiModal');
    const btnCancelDisp = document.getElementById('btnCancelDisposisiModal');
    const closeDispModal = () => { if (modalDisp) modalDisp.style.display = 'none'; };
    if (btnCloseDisp) btnCloseDisp.addEventListener('click', closeDispModal);
    if (btnCancelDisp) btnCancelDisp.addEventListener('click', closeDispModal);

    // Submit Disposisi
    const formDisp = document.getElementById('formDisposisiItem');
    if (formDisp) {
        formDisp.addEventListener('submit', async (e) => {
            e.preventDefault();
            const suratId = document.getElementById('disposisiSuratId')?.value;
            const btnSave = document.getElementById('btnSaveDisposisiModal');

            const payload = {
                disposisi_dari: 'Kepala Sekolah',
                disposisi_ke: document.getElementById('dispTujuan')?.value || '',
                instruksi: document.getElementById('dispInstruksi')?.value || 'Tanggapi / Tindak Lanjuti',
                tanggal_disposisi: document.getElementById('dispTanggal')?.value || new Date().toISOString().split('T')[0],
                catatan: document.getElementById('dispCatatan')?.value || '',
                update_status_surat: 'diproses'
            };

            if (btnSave) {
                btnSave.disabled = true;
                btnSave.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Menyimpan...';
            }

            try {
                const res = await fetch(`/dashboard/persuratan/${suratId}/disposisi`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (res.ok && data.status === 'success') {
                    closeDispModal();
                    showToast(data.message || 'Lembar disposisi berhasil disimpan.');
                    setTimeout(() => window.location.reload(), 600);
                } else {
                    showToast(data.message || 'Gagal menyimpan disposisi.', 'danger');
                }
            } catch (err) {
                showToast('Terjadi kesalahan koneksi server.', 'danger');
            } finally {
                if (btnSave) {
                    btnSave.disabled = false;
                    btnSave.innerHTML = '<i class="fas fa-check me-1"></i> Simpan Disposisi';
                }
            }
        });
    }

    // Modal Surat Keterangan Siswa
    const modalKet = document.getElementById('modalSuratKetItem');
    const btnOpenKet = document.getElementById('btnOpenKetModal');
    const btnCloseKet = document.getElementById('btnCloseKetModal');
    const btnCancelKet = document.getElementById('btnCancelKetModal');
    const formKet = document.getElementById('formSuratKetItem');

    const closeKetModal = () => { if (modalKet) modalKet.style.display = 'none'; };
    if (btnOpenKet && modalKet) {
        btnOpenKet.addEventListener('click', () => {
            if (formKet) formKet.reset();
            const dateInput = document.getElementById('ketTanggal');
            if (dateInput) dateInput.value = new Date().toISOString().split('T')[0];
            modalKet.style.display = 'flex';
        });
    }
    if (btnCloseKet) btnCloseKet.addEventListener('click', closeKetModal);
    if (btnCancelKet) btnCancelKet.addEventListener('click', closeKetModal);

    // Submit Surat Keterangan
    if (formKet) {
        formKet.addEventListener('submit', async (e) => {
            e.preventDefault();
            const btnSave = document.getElementById('btnSaveKetModal');

            const payload = {
                peserta_didik_id: document.getElementById('ketPesertaDidikId')?.value || '',
                jenis_surat: document.getElementById('ketJenisSurat')?.value || 'siswa_aktif',
                tanggal_surat: document.getElementById('ketTanggal')?.value || new Date().toISOString().split('T')[0],
                keperluan: document.getElementById('ketKeperluan')?.value || ''
            };

            if (btnSave) {
                btnSave.disabled = true;
                btnSave.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Memproses...';
            }

            try {
                const res = await fetch('/dashboard/persuratan/keterangan', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (res.ok && data.status === 'success') {
                    closeKetModal();
                    showToast(data.message || 'Surat keterangan berhasil diterbitkan.');
                    if (data.cetak_url) {
                        window.open(data.cetak_url, '_blank');
                    }
                    setTimeout(() => window.location.reload(), 800);
                } else {
                    showToast(data.message || 'Gagal menerbitkan surat keterangan.', 'danger');
                }
            } catch (err) {
                showToast('Terjadi kesalahan koneksi server.', 'danger');
            } finally {
                if (btnSave) {
                    btnSave.disabled = false;
                    btnSave.innerHTML = '<i class="fas fa-check me-1"></i> Terbitkan &amp; Cetak';
                }
            }
        });
    }
});