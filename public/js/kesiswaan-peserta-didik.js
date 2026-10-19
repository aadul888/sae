/**
 * Logika JavaScript Modular: Peserta Didik (Aktif, Tidak Aktif, Alumni, Berkas, Usulan Perubahan)
 * Standar Resmi SAE (Vanilla JS + SweetAlert2)
 */

document.addEventListener('DOMContentLoaded', function () {
    // 1. Modal Helper
    function openModal(selector) {
        const modal = document.querySelector(selector);
        if (modal) modal.style.display = 'flex';
    }

    function closeModal(selector) {
        const modal = document.querySelector(selector);
        if (modal) modal.style.display = 'none';
    }

    document.querySelectorAll('.close-modal').forEach(btn => {
        btn.addEventListener('click', function () {
            const target = this.getAttribute('data-target');
            if (target) closeModal(target);
        });
    });

    const btnOpenUsulan = document.getElementById('btnOpenUsulanModal');
    if (btnOpenUsulan) {
        btnOpenUsulan.addEventListener('click', () => openModal('#modalUsulan'));
    }

    // 2. Submit Usulan Perubahan Data
    const formUsulan = document.getElementById('formUsulan');
    if (formUsulan) {
        formUsulan.addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = new FormData(this);

            Swal.fire({
                title: 'Memproses...',
                text: 'Mengirim usulan perubahan data...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            fetch(this.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    'Accept': 'application/json',
                },
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire('Berhasil!', data.message, 'success').then(() => location.reload());
                } else {
                    Swal.fire('Gagal!', data.message || 'Terjadi kesalahan.', 'error');
                }
            })
            .catch(err => {
                Swal.fire('Error!', 'Gagal mengirim usulan.', 'error');
            });
        });
    }

    // 3. Validasi Berkas Digital PDF Siswa (Modal 3)
    let berkasStatusChanged = false;

    // Delegated click event listener agar selalu aktif bahkan setelah live search / ajax reload
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-validasi-berkas-detail');
        if (btn) {
            e.preventDefault();
            e.stopPropagation();
            const id = btn.getAttribute('data-id');
            const nama = btn.getAttribute('data-nama');
            if (id && typeof window.openModalVerifikasiBerkas === 'function') {
                window.openModalVerifikasiBerkas(id, nama);
            }
        }
    });

    // Delegated close listener for modalVerifikasiBerkasDigital & backdrop/ESC sync
    function handleModalVbClose() {
        const modal = document.getElementById('modalVerifikasiBerkasDigital');
        if (modal) {
            modal.style.display = 'none';
            document.body.style.overflow = '';
        }
        if (berkasStatusChanged) {
            location.reload();
        }
    }

    document.addEventListener('click', function (e) {
        const closeBtn = e.target.closest('.close-modal[data-target="#modalVerifikasiBerkasDigital"]');
        if (closeBtn) {
            e.preventDefault();
            handleModalVbClose();
        }
    });

    const modalVbEl = document.getElementById('modalVerifikasiBerkasDigital');
    if (modalVbEl) {
        modalVbEl.addEventListener('click', function (e) {
            if (e.target === modalVbEl) {
                handleModalVbClose();
            }
        });
    }

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            const modalVb = document.getElementById('modalVerifikasiBerkasDigital');
            const modalPdf = document.getElementById('modalPdfViewer');
            // If PDF viewer was on top, let closePdfViewer handle it
            if (modalPdf && modalPdf.style.display !== 'none') {
                window.closePdfViewer();
                return;
            }
            if (modalVb && modalVb.style.display !== 'none' && berkasStatusChanged) {
                location.reload();
            }
        }
    });

    window.openModalVerifikasiBerkas = async function (id, nama) {
        const modal = document.getElementById('modalVerifikasiBerkasDigital');
        const loading = document.getElementById('modalVbLoading');
        const content = document.getElementById('modalVbContent');
        const namaEl = document.getElementById('modalVbNama');
        const infoEl = document.getElementById('modalVbInfo');

        if (!modal) return;

        berkasStatusChanged = false;
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        loading.style.display = 'block';
        content.style.display = 'none';
        content.innerHTML = '';
        namaEl.textContent = `Validasi Berkas: ${nama || 'Peserta Didik'}`;
        infoEl.textContent = 'Memuat data siswa dan berkas digital...';

        try {
            const res = await fetch(`/dashboard/kesiswaan/peserta-didik/berkas-detail/${encodeURIComponent(id)}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                }
            });
            const json = await res.json();

            loading.style.display = 'none';
            content.style.display = 'block';

            if (json.status !== 'success') {
                Swal.fire('Error', json.message || 'Gagal memuat berkas siswa.', 'error');
                modal.style.display = 'none';
                document.body.style.overflow = '';
                return;
            }

            const siswa = json.siswa;
            infoEl.innerHTML = `NISN: <strong>${escapeHtml(siswa.nisn)}</strong> &bull; NIPD: <strong>${escapeHtml(siswa.nipd)}</strong> &bull; Rombel: <strong>${escapeHtml(siswa.rombel)}</strong>`;

            const rekomendasiList = json.rekomendasi_penolakan || [];
            let html = `<div style="display: flex; flex-direction: column; gap: 16px;">`;

            json.items.forEach((item) => {
                const statusBadge = item.status === 'valid'
                    ? `<span class="badge badge-success" style="font-size: 0.74rem; padding: 4px 10px;"><i class="fas fa-circle-check me-1"></i> Valid / Sesuai</span>`
                    : (item.status === 'tidak_valid'
                        ? `<span class="badge badge-danger" style="font-size: 0.74rem; padding: 4px 10px;"><i class="fas fa-circle-xmark me-1"></i> Tidak Valid / Tidak Sesuai</span>`
                        : (item.is_uploaded
                            ? `<span class="badge badge-warning" style="font-size: 0.74rem; padding: 4px 10px;"><i class="fas fa-clock me-1"></i> Menunggu Verifikasi</span>`
                            : `<span class="badge badge-outline" style="font-size: 0.74rem; padding: 4px 10px;"><i class="fas fa-circle-minus me-1"></i> Belum Diunggah Siswa</span>`));

                html += `
                    <div class="card card-berkas-item" id="cardBerkas_${item.jenis_berkas}" data-key="${item.jenis_berkas}" data-selected-status="${item.status || ''}" style="padding: 16px; border-radius: 12px; border: 1.5px solid var(--border-color); background: var(--bg-hover);">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 10px; flex-wrap: wrap; margin-bottom: 10px;">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <div style="width: 38px; height: 38px; border-radius: 8px; background: rgba(99,102,241,0.12); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 1.15rem; flex-shrink: 0;">
                                    <i class="fas ${item.icon}"></i>
                                </div>
                                <div>
                                    <div style="font-weight: 800; font-size: 0.95rem; color: var(--text-color);">
                                        ${escapeHtml(item.label)}
                                        ${item.wajib ? '<span class="badge badge-outline" style="font-size: 0.65rem; margin-left: 6px;">Wajib</span>' : '<span class="badge badge-outline" style="font-size: 0.65rem; margin-left: 6px; opacity: 0.7;">Opsional</span>'}
                                    </div>
                                    <div style="font-size: 0.75rem; color: var(--text-muted);">${escapeHtml(item.deskripsi)}</div>
                                </div>
                            </div>
                            <div class="status-badge-container">${statusBadge}</div>
                        </div>
                `;

                if (item.is_uploaded) {
                    html += `
                        <div style="background: rgba(0,0,0,0.12); border-radius: 8px; padding: 10px 14px; margin-bottom: 12px; display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap;">
                            <div style="display: flex; align-items: center; gap: 8px; min-width: 0;">
                                <i class="fas fa-file-pdf text-danger" style="font-size: 1.4rem;"></i>
                                <div style="min-width: 0;">
                                    <div style="font-size: 0.82rem; font-weight: 700; color: var(--text-color);">${escapeHtml(item.file_name)}</div>
                                    <div style="font-size: 0.72rem; color: var(--text-muted);">Ukuran: ${item.file_size} &bull; Diunggah: ${item.uploaded_at || '-'}</div>
                                </div>
                            </div>
                            <button type="button" class="btn btn-outline btn-preview-pdf-inline" data-url="${item.file_url}" data-title="${escapeHtml(item.label)} - ${escapeHtml(siswa.nama)}" data-pd-id="${id}" data-key="${item.jenis_berkas}" data-label="${escapeHtml(item.label)}" data-status="${item.status || ''}" data-catatan="${escapeHtml(item.catatan_penolakan || '')}" style="padding: 5px 12px; font-size: 0.78rem; border-radius: 6px; cursor: pointer;">
                                <i class="fas fa-eye me-1"></i> Lihat Dokumen PDF
                            </button>
                        </div>
                    `;
                } else {
                    html += `
                        <div style="background: rgba(245, 158, 11, 0.08); border: 1px dashed rgba(245, 158, 11, 0.3); border-radius: 8px; padding: 8px 12px; margin-bottom: 12px; color: #d97706; font-size: 0.78rem; display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-circle-info"></i>
                            <span>Siswa belum mengunggah file PDF untuk dokumen ini. Pengelola tetap dapat menetapkan status validasi (misal: verifikasi berkas fisik atau catatan kekurangan).</span>
                        </div>
                    `;
                }

                html += `
                    <!-- Fitur Upload Dokumen Langsung oleh Tendik (Otomatis Berstatus Valid) -->
                    <div style="background: rgba(16,185,129,0.06); border: 1px dashed rgba(16,185,129,0.3); border-radius: 8px; padding: 10px 14px; margin-bottom: 12px; display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap;">
                        <div style="font-size: 0.78rem; color: var(--text-color);">
                            <i class="fas fa-cloud-arrow-up text-success me-1"></i>
                            <strong>Unggah Berkas Siswa oleh Petugas Kesiswaan:</strong>
                            <span style="font-size: 0.72rem; color: var(--text-muted); display: block;">File (PDF, JPG, PNG) yang diunggah petugas otomatis diverifikasi berstatus <strong>VALID</strong>.</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <input type="file" id="fileTendik_${item.jenis_berkas}" accept=".pdf,.jpg,.jpeg,.png" style="display: none;" class="input-file-tendik" data-pd-id="${id}" data-key="${item.jenis_berkas}">
                            <button type="button" class="btn btn-outline" onclick="document.getElementById('fileTendik_${item.jenis_berkas}').click()" style="padding: 5px 12px; font-size: 0.78rem; border-color: #10b981; color: #10b981; border-radius: 6px; cursor: pointer;">
                                <i class="fas fa-upload me-1"></i> ${item.is_uploaded ? 'Ganti File (Auto-Valid)' : 'Unggah File (Auto-Valid)'}
                            </button>
                        </div>
                    </div>

                    <!-- Form Validasi Status (HANYA 2 PILIHAN STATUS: valid atau tidak_valid) -->
                    <div style="background: var(--card-bg); border-radius: 8px; padding: 12px 14px; border: 1px solid var(--border-color);">
                        <div style="font-size: 0.8rem; font-weight: 800; color: var(--text-color); margin-bottom: 8px;">
                            Tentukan Hasil Validasi Berkas:
                        </div>

                        <div style="display: flex; gap: 18px; align-items: center; margin-bottom: 10px; flex-wrap: wrap;">
                            <label style="display: flex; align-items: center; gap: 6px; font-size: 0.85rem; font-weight: 700; color: #10b981; cursor: pointer;">
                                <input type="radio" name="status_${item.jenis_berkas}" value="valid" ${item.status === 'valid' ? 'checked' : ''} class="radio-status-berkas" data-key="${item.jenis_berkas}">
                                <i class="fas fa-circle-check"></i> Valid / Sesuai
                            </label>
                            <label style="display: flex; align-items: center; gap: 6px; font-size: 0.85rem; font-weight: 700; color: #ef4444; cursor: pointer;">
                                <input type="radio" name="status_${item.jenis_berkas}" value="tidak_valid" ${item.status === 'tidak_valid' ? 'checked' : ''} class="radio-status-berkas" data-key="${item.jenis_berkas}">
                                <i class="fas fa-circle-xmark"></i> Tidak Valid / Tidak Sesuai
                            </label>
                        </div>

                        <!-- Area Penolakan & Rekomendasi (Muncul jika status tidak_valid) -->
                        <div id="boxPenolakan_${item.jenis_berkas}" style="display: ${item.status === 'tidak_valid' ? 'block' : 'none'}; margin-top: 10px; border-top: 1px dashed var(--border-color); padding-top: 10px;">
                            <div style="font-size: 0.76rem; font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">
                                Pilih Rekomendasi Alasan Penolakan (Klik tombol untuk mengisi catatan):
                            </div>
                            <div style="display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 10px;">
                                ${rekomendasiList.map(rek => `
                                    <button type="button" class="btn btn-outline btn-chip-rekomendasi" data-target-text="textarea_${item.jenis_berkas}" data-text="${escapeHtml(rek)}" style="padding: 3px 8px; font-size: 0.72rem; border-radius: 6px;">
                                        <i class="fas fa-reply me-1"></i> ${escapeHtml(rek)}
                                    </button>
                                `).join('')}
                            </div>

                            <div style="margin-bottom: 8px;">
                                <label style="font-size: 0.78rem; font-weight: 700; color: var(--text-color); display: block; margin-bottom: 4px;">
                                    Catatan Penolakan untuk Disampaikan ke Siswa <span class="text-danger">*</span>:
                                </label>
                                <textarea id="textarea_${item.jenis_berkas}" class="form-control input-catatan-penolakan" rows="2" placeholder="Tuliskan petunjuk perbaikan bagi peserta didik..." style="font-size: 0.82rem;">${escapeHtml(item.catatan_penolakan || '')}</textarea>
                            </div>
                        </div>

                        <div style="display: flex; justify-content: flex-end; margin-top: 10px;">
                            <button type="button" class="btn btn-primary btn-save-verif-berkas" data-pd-id="${id}" data-key="${item.jenis_berkas}" data-label="${escapeHtml(item.label)}" style="padding: 6px 14px; font-size: 0.8rem; font-weight: 700; border-radius: 8px; display: inline-flex; align-items: center; gap: 6px;">
                                <i class="fas fa-check"></i> Simpan Status Berkas
                            </button>
                        </div>
                    </div>
                `;

                html += `</div>`;
            });

            html += `</div>`;
            content.innerHTML = html;

            // Wire up radio toggle show/hide rejection box & card state sync
            content.querySelectorAll('.radio-status-berkas').forEach(radio => {
                radio.addEventListener('change', function () {
                    const key = this.getAttribute('data-key');
                    const box = document.getElementById(`boxPenolakan_${key}`);
                    if (box) {
                        box.style.display = this.value === 'tidak_valid' ? 'block' : 'none';
                    }
                    const card = document.getElementById(`cardBerkas_${key}`);
                    if (card) {
                        card.setAttribute('data-selected-status', this.value);
                    }
                });
            });

            // Wire up recommendation chips
            content.querySelectorAll('.btn-chip-rekomendasi').forEach(chip => {
                chip.addEventListener('click', function () {
                    const targetId = this.getAttribute('data-target-text');
                    const text = this.getAttribute('data-text');
                    const textarea = document.getElementById(targetId);
                    if (textarea) {
                        textarea.value = text;
                        textarea.focus();
                    }
                });
            });

            // Wire up file input change for direct tendik upload
            content.querySelectorAll('.input-file-tendik').forEach(fileInput => {
                fileInput.addEventListener('change', async function () {
                    const file = this.files[0];
                    if (!file) return;

                    const pdId = this.getAttribute('data-pd-id');
                    const key = this.getAttribute('data-key');

                    const formData = new FormData();
                    formData.append('jenis_berkas', key);
                    formData.append('file', file);

                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content
                        || document.querySelector('input[name="_token"]')?.value
                        || '';

                    Swal.fire({
                        title: 'Mengunggah Berkas...',
                        text: 'File sedang diproses dan otomatis diverifikasi VALID.',
                        allowOutsideClick: false,
                        didOpen: () => Swal.showLoading()
                    });

                    try {
                        const upRes = await fetch(`/dashboard/kesiswaan/peserta-didik/berkas-upload/${encodeURIComponent(pdId)}`, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            body: formData
                        });
                        const upJson = await upRes.json();
                        if (upRes.ok && upJson.status === 'success') {
                            berkasStatusChanged = true;
                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil Diunggah!',
                                text: upJson.message,
                                timer: 1800,
                                showConfirmButton: false
                            });
                            // Refresh modal detail to show updated file and valid status
                            window.openModalVerifikasiBerkas(pdId, siswa.nama);
                        } else {
                            Swal.fire('Gagal Unggah', upJson.message || 'Terjadi kesalahan sistem.', 'error');
                        }
                    } catch (err) {
                        Swal.fire('Kesalahan Jaringan', 'Tidak dapat terhubung ke server.', 'error');
                    }
                });
            });

            // Wire up save button per berkas
            content.querySelectorAll('.btn-save-verif-berkas').forEach(btn => {
                btn.addEventListener('click', async function () {
                    const pdId = this.getAttribute('data-pd-id');
                    const key = this.getAttribute('data-key');
                    const label = this.getAttribute('data-label');
                    const card = document.getElementById(`cardBerkas_${key}`) || this.closest('.card-berkas-item');
                    const selectedRadio = card
                        ? card.querySelector(`input[name="status_${key}"]:checked`)
                        : content.querySelector(`input[name="status_${key}"]:checked`);
                    const status = selectedRadio ? selectedRadio.value : (card ? card.getAttribute('data-selected-status') : '');

                    if (!status || (status !== 'valid' && status !== 'tidak_valid')) {
                        Swal.fire('Peringatan', 'Silakan pilih status validasi (Valid atau Tidak Valid).', 'warning');
                        return;
                    }

                    const textarea = document.getElementById(`textarea_${key}`);
                    const catatan = textarea ? textarea.value.trim() : '';

                    if (status === 'tidak_valid' && !catatan) {
                        Swal.fire('Wajib Catatan', 'Untuk status Tidak Valid / Tidak Sesuai, Anda wajib mengisi catatan atau memilih rekomendasi alasan penolakan.', 'warning');
                        if (textarea) textarea.focus();
                        return;
                    }

                    const originalBtnText = this.innerHTML;
                    this.disabled = true;
                    this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';

                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content
                        || document.querySelector('input[name="_token"]')?.value
                        || '';

                    try {
                        const postRes = await fetch(`/dashboard/kesiswaan/peserta-didik/berkas-verifikasi/${encodeURIComponent(pdId)}`, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': csrfToken,
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            body: JSON.stringify({
                                jenis_berkas: key,
                                status: status,
                                catatan_penolakan: catatan,
                                rekomendasi_penolakan: catatan,
                            })
                        });

                        const postJson = await postRes.json();
                        this.disabled = false;
                        this.innerHTML = originalBtnText;

                        if (postRes.ok && postJson.status === 'success') {
                            berkasStatusChanged = true;
                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil!',
                                text: postJson.message,
                                timer: 1500,
                                showConfirmButton: false,
                            });

                            // Update status badge & radio state on card
                            if (card) {
                                const badgeContainer = card.querySelector('.status-badge-container');
                                if (badgeContainer) {
                                    badgeContainer.innerHTML = status === 'valid'
                                        ? `<span class="badge badge-success" style="font-size: 0.74rem; padding: 4px 10px;"><i class="fas fa-circle-check me-1"></i> Valid / Sesuai</span>`
                                        : `<span class="badge badge-danger" style="font-size: 0.74rem; padding: 4px 10px;"><i class="fas fa-circle-xmark me-1"></i> Tidak Valid / Tidak Sesuai</span>`;
                                }
                                card.setAttribute('data-selected-status', status);
                                const currentRadio = card.querySelector(`input[name="status_${key}"][value="${status}"]`);
                                if (currentRadio) currentRadio.checked = true;
                                const prevBtn = card.querySelector('.btn-preview-pdf-inline');
                                if (prevBtn) {
                                    prevBtn.setAttribute('data-status', status);
                                    prevBtn.setAttribute('data-catatan', catatan);
                                }
                            }
                        } else {
                            Swal.fire('Gagal', postJson.message || 'Terjadi kesalahan.', 'error');
                        }
                    } catch (err) {
                        this.disabled = false;
                        this.innerHTML = originalBtnText;
                        Swal.fire('Error', 'Gagal menghubungi server.', 'error');
                    }
                });
            });

        } catch (err) {
            loading.style.display = 'none';
            Swal.fire('Error', 'Terjadi kesalahan saat memuat berkas.', 'error');
            modal.style.display = 'none';
            document.body.style.overflow = '';
        }
    };

    // 3b. Viewer Dokumen PDF Terpadu (In-App Modal tanpa Buka Tab Baru) dengan Kontrol Validasi Langsung
    let activePdfViewerData = null;

    window.openPdfViewer = function (url, title, pdId = null, key = null, label = null, currentStatus = null, catatan = null) {
        const modal = document.getElementById('modalPdfViewer');
        const frame = document.getElementById('pdfViewerFrame');
        const titleEl = document.getElementById('pdfViewerTitle');
        if (!modal || !frame) return;

        if (titleEl) titleEl.textContent = title || 'Pratinjau Dokumen PDF';
        frame.src = url;

        activePdfViewerData = pdId && key ? { pdId, key, label, currentStatus, catatan } : null;

        const controls = document.getElementById('pdfViewerVerifControls');
        const radioValid = document.getElementById('pdfViewerRadioValid');
        const radioTidakValid = document.getElementById('pdfViewerRadioTidakValid');
        const catatanInput = document.getElementById('pdfViewerCatatan');

        if (controls) {
            if (activePdfViewerData) {
                controls.style.display = 'flex';
                const card = document.getElementById(`cardBerkas_${key}`);
                const effectiveStatus = card ? (card.getAttribute('data-selected-status') || currentStatus) : currentStatus;

                if (radioValid) radioValid.checked = (effectiveStatus === 'valid');
                if (radioTidakValid) radioTidakValid.checked = (effectiveStatus === 'tidak_valid');

                if (catatanInput) {
                    const existingCatatan = document.getElementById(`textarea_${key}`)?.value?.trim() || catatan || '';
                    catatanInput.value = existingCatatan;
                    catatanInput.style.display = (effectiveStatus === 'tidak_valid') ? 'inline-block' : 'none';
                }
            } else {
                controls.style.display = 'none';
            }
        }

        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    };

    window.closePdfViewer = function () {
        const modal = document.getElementById('modalPdfViewer');
        const frame = document.getElementById('pdfViewerFrame');
        if (modal) modal.style.display = 'none';
        if (frame) frame.src = '';

        const closedKey = activePdfViewerData?.key;
        activePdfViewerData = null;

        // Restore active window focus immediately so parent clicks don't get swallowed
        window.focus();

        const modalVb = document.getElementById('modalVerifikasiBerkasDigital');
        if (!modalVb || modalVb.style.display === 'none') {
            document.body.style.overflow = '';
        } else if (closedKey) {
            const card = document.getElementById(`cardBerkas_${closedKey}`);
            if (card) {
                card.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
        }
    };

    // Listener interaksi radio & simpan langsung di viewer PDF
    document.addEventListener('change', function (e) {
        if (e.target && e.target.classList.contains('pdf-viewer-radio')) {
            const catatanInput = document.getElementById('pdfViewerCatatan');
            if (catatanInput) {
                catatanInput.style.display = e.target.value === 'tidak_valid' ? 'inline-block' : 'none';
                if (e.target.value === 'tidak_valid') catatanInput.focus();
            }
            if (activePdfViewerData?.key) {
                const card = document.getElementById(`cardBerkas_${activePdfViewerData.key}`);
                if (card) {
                    card.setAttribute('data-selected-status', e.target.value);
                    const cardRadio = card.querySelector(`input[name="status_${activePdfViewerData.key}"][value="${e.target.value}"]`);
                    if (cardRadio) {
                        cardRadio.checked = true;
                        cardRadio.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                }
            }
        }
    });

    document.addEventListener('click', async function (e) {
        const btnSimpan = e.target.closest('#btnPdfViewerSimpan');
        if (btnSimpan && activePdfViewerData) {
            e.preventDefault();
            const { pdId, key, label } = activePdfViewerData;
            const selectedRadio = document.querySelector('input[name="pdf_viewer_status"]:checked');
            if (!selectedRadio) {
                Swal.fire('Peringatan', 'Silakan pilih status validasi (Valid atau Tidak Valid).', 'warning');
                return;
            }

            const status = selectedRadio.value;
            const catatanInput = document.getElementById('pdfViewerCatatan');
            const catatan = catatanInput ? catatanInput.value.trim() : '';

            if (status === 'tidak_valid' && !catatan) {
                Swal.fire('Wajib Catatan', 'Untuk status Tidak Valid / Tidak Sesuai, Anda wajib mengisi catatan penolakan.', 'warning');
                if (catatanInput) catatanInput.focus();
                return;
            }

            const origText = btnSimpan.innerHTML;
            btnSimpan.disabled = true;
            btnSimpan.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content
                || document.querySelector('input[name="_token"]')?.value
                || '';

            try {
                const postRes = await fetch(`/dashboard/kesiswaan/peserta-didik/berkas-verifikasi/${encodeURIComponent(pdId)}`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({
                        jenis_berkas: key,
                        status: status,
                        catatan_penolakan: catatan,
                        rekomendasi_penolakan: catatan,
                    })
                });

                const postJson = await postRes.json();
                btnSimpan.disabled = false;
                btnSimpan.innerHTML = origText;

                if (postRes.ok && postJson.status === 'success') {
                    berkasStatusChanged = true;
                    const card = document.getElementById(`cardBerkas_${key}`);
                    if (card) {
                        const badgeContainer = card.querySelector('.status-badge-container');
                        if (badgeContainer) {
                            badgeContainer.innerHTML = status === 'valid'
                                ? `<span class="badge badge-success" style="font-size: 0.74rem; padding: 4px 10px;"><i class="fas fa-circle-check me-1"></i> Valid / Sesuai</span>`
                                : `<span class="badge badge-danger" style="font-size: 0.74rem; padding: 4px 10px;"><i class="fas fa-circle-xmark me-1"></i> Tidak Valid / Tidak Sesuai</span>`;
                        }
                        card.setAttribute('data-selected-status', status);
                        const cardRadio = card.querySelector(`input[name="status_${key}"][value="${status}"]`);
                        if (cardRadio) {
                            cardRadio.checked = true;
                            cardRadio.dispatchEvent(new Event('change', { bubbles: true }));
                        }
                        const cardTextarea = document.getElementById(`textarea_${key}`);
                        if (cardTextarea && catatan) {
                            cardTextarea.value = catatan;
                        }
                    }

                    window.closePdfViewer();

                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: postJson.message,
                        timer: 1500,
                        showConfirmButton: false,
                    });
                } else {
                    Swal.fire('Gagal', postJson.message || 'Terjadi kesalahan sistem.', 'error');
                }
            } catch (err) {
                btnSimpan.disabled = false;
                btnSimpan.innerHTML = origText;
                Swal.fire('Error', 'Gagal menghubungi server.', 'error');
            }
        }
    });

    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-preview-pdf-inline');
        if (btn) {
            e.preventDefault();
            e.stopPropagation();
            const url = btn.getAttribute('data-url');
            const title = btn.getAttribute('data-title');
            const pdId = btn.getAttribute('data-pd-id');
            const key = btn.getAttribute('data-key');
            const label = btn.getAttribute('data-label');
            const status = btn.getAttribute('data-status');
            const catatan = btn.getAttribute('data-catatan');
            if (url) window.openPdfViewer(url, title, pdId, key, label, status, catatan);
        }

        const closeBtn = e.target.closest('.close-pdf-viewer');
        if (closeBtn) {
            e.preventDefault();
            e.stopPropagation();
            window.closePdfViewer();
        }
    });

    // 4. Modal Pengelolaan Usulan Perubahan Data Siswa (Field Asal vs Penyesuaian)
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-kelola-usulan-modal, .btn-verif-usulan');
        if (btn) {
            e.preventDefault();
            const id = btn.getAttribute('data-id');
            if (id && typeof window.openModalPengelolaanUsulan === 'function') {
                window.openModalPengelolaanUsulan(id);
            }
        }
    });

    window.openModalPengelolaanUsulan = async function (id) {
        const modal = document.getElementById('modalPengelolaanUsulan');
        const loading = document.getElementById('modalUsulanLoading');
        const content = document.getElementById('modalUsulanContent');
        const namaEl = document.getElementById('modalUsulanNama');
        const subtitleEl = document.getElementById('modalUsulanSubtitle');

        if (!modal) return;

        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        loading.style.display = 'block';
        content.style.display = 'none';
        content.innerHTML = '';
        namaEl.textContent = 'Pengelolaan Usulan Perubahan Data';
        subtitleEl.textContent = 'Memuat rincian usulan perubahan...';

        try {
            const res = await fetch(`/dashboard/kesiswaan/peserta-didik/usulan-detail/${encodeURIComponent(id)}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                }
            });
            const json = await res.json();

            loading.style.display = 'none';
            content.style.display = 'block';

            if (json.status !== 'success') {
                Swal.fire('Error', json.message || 'Gagal memuat rincian usulan.', 'error');
                modal.style.display = 'none';
                document.body.style.overflow = '';
                return;
            }

            const d = json.data;
            subtitleEl.innerHTML = `Siswa: <strong>${escapeHtml(d.nama_siswa)}</strong> &bull; NISN: <strong>${escapeHtml(d.nisn)}</strong> &bull; Rombel: <strong>${escapeHtml(d.rombel)}</strong>`;

            const statusBadge = d.status === 'disetujui'
                ? `<span class="badge badge-success" style="font-size: 0.74rem; padding: 4px 10px;"><i class="fas fa-check me-1"></i> Disetujui (Siap Dapodik)</span>`
                : (d.status === 'sudah_ke_dapodik'
                    ? `<span class="badge badge-primary" style="font-size: 0.74rem; padding: 4px 10px;"><i class="fas fa-cloud-arrow-up me-1"></i> Selesai di Dapodik</span>`
                    : (d.status === 'ditolak'
                        ? `<span class="badge badge-danger" style="font-size: 0.74rem; padding: 4px 10px;"><i class="fas fa-circle-xmark me-1"></i> Ditolak Kesiswaan</span>`
                        : `<span class="badge badge-warning" style="font-size: 0.74rem; padding: 4px 10px;"><i class="fas fa-clock me-1"></i> Menunggu Review</span>`));

            const rekomendasiList = json.rekomendasi_penolakan || [];
            const items = d.items || [];

            let html = `
                <div style="display: flex; flex-direction: column; gap: 16px;">
                    <!-- 1. Header Identitas Siswa & Status Usulan -->
                    <div style="background: var(--bg-hover); border-radius: 12px; padding: 14px 18px; border: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div style="width: 46px; height: 46px; border-radius: 10px; background: rgba(99,102,241,0.12); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 1.3rem; flex-shrink: 0; overflow: hidden; border: 1.5px solid var(--border-color);">
                                ${d.foto_url ? `<img src="${d.foto_url}" style="width:100%; height:100%; object-fit:cover;">` : `<i class="fas fa-user-graduate"></i>`}
                            </div>
                            <div>
                                <div style="font-weight: 800; font-size: 1rem; color: var(--text-color);">${escapeHtml(d.nama_siswa)}</div>
                                <div style="font-size: 0.78rem; color: var(--text-muted); margin-top: 2px;">
                                    NISN: <strong>${escapeHtml(d.nisn)}</strong> &bull; NIPD: <strong>${escapeHtml(d.nipd)}</strong> &bull; Rombel: <strong>${escapeHtml(d.rombel)}</strong>
                                </div>
                            </div>
                        </div>
                        <div style="text-align: right;">
                            <div style="display: flex; gap: 6px; align-items: center; justify-content: flex-end;">
                                <span class="badge badge-primary" style="font-size: 0.74rem; padding: 4px 8px;">
                                    <i class="fas fa-list-check me-1"></i> ${d.total_usulan} Butir Usulan
                                </span>
                                ${statusBadge}
                            </div>
                            <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 4px;">Diajukan: ${escapeHtml(d.created_at || '-')}</div>
                        </div>
                    </div>

                    <!-- 2. FORMULIR PERBANDINGAN DATA LAMA VS DATA BARU SESUAI USULAN -->
                    <div style="background: var(--card-bg); border-radius: 12px; padding: 16px 18px; border: 1.5px solid var(--border-color);">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; flex-wrap: wrap; gap: 8px;">
                            <div style="font-size: 0.84rem; font-weight: 800; color: var(--text-color); text-transform: uppercase; letter-spacing: 0.5px;">
                                <i class="fas fa-pen-to-square me-1 text-primary"></i> Formulir Perbandingan Data Lama vs Data Baru Sesuai Usulan
                            </div>
                            <div style="font-size: 0.74rem; color: var(--text-muted);">
                                ${items.length} kolom data diajukan perubahan oleh peserta didik
                            </div>
                        </div>

                        <div style="display: flex; flex-direction: column; gap: 12px;">
                            ${items.map((item, idx) => {
                                const itmBadge = item.status === 'disetujui'
                                    ? `<span class="badge badge-success" style="font-size: 0.68rem; padding: 3px 8px;"><i class="fas fa-check me-1"></i> Disetujui</span>`
                                    : (item.status === 'sudah_ke_dapodik'
                                        ? `<span class="badge badge-primary" style="font-size: 0.68rem; padding: 3px 8px;"><i class="fas fa-cloud-arrow-up me-1"></i> Selesai Dapodik</span>`
                                        : (item.status === 'ditolak'
                                            ? `<span class="badge badge-danger" style="font-size: 0.68rem; padding: 3px 8px;"><i class="fas fa-circle-xmark me-1"></i> Ditolak</span>`
                                            : `<span class="badge badge-warning" style="font-size: 0.68rem; padding: 3px 8px;"><i class="fas fa-clock me-1"></i> Menunggu</span>`));

                                return `
                                    <div class="usulan-form-card" style="background: var(--bg-hover); border-radius: 10px; padding: 14px; border: 1px solid var(--border-color);">
                                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; flex-wrap: wrap; gap: 8px;">
                                            <div style="display: flex; align-items: center; gap: 8px;">
                                                <span class="badge" style="background: rgba(99,102,241,0.12); color: var(--primary); font-size: 0.74rem; font-weight: 700; padding: 3px 7px;">
                                                    #${idx + 1}
                                                </span>
                                                <strong style="font-size: 0.92rem; color: var(--text-color);">${escapeHtml(item.kolom_label)}</strong>
                                            </div>
                                            <div>${itmBadge}</div>
                                        </div>

                                        <!-- Grid Perbandingan 2 Kolom Formulir: Data Lama vs Data Baru -->
                                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 10px; margin-bottom: 8px;">
                                            <!-- Data Lama (Data Asal) -->
                                            <div style="background: var(--card-bg); border-radius: 8px; padding: 10px 12px; border: 1px dashed var(--border-color);">
                                                <div style="font-size: 0.7rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 4px; display: flex; align-items: center; gap: 5px;">
                                                    <i class="fas fa-history text-muted"></i> Data Lama (Data Asal Sebelumnya)
                                                </div>
                                                <div style="font-size: 0.88rem; color: var(--text-color); word-break: break-word;">
                                                    <span style="text-decoration: line-through; opacity: 0.8;">${escapeHtml(item.nilai_lama)}</span>
                                                </div>
                                            </div>

                                            <!-- Data Baru (Sesuai Usulan) -->
                                            <div style="background: rgba(16, 185, 129, 0.08); border-radius: 8px; padding: 10px 12px; border: 1.5px solid rgba(16, 185, 129, 0.35);">
                                                <div style="font-size: 0.7rem; font-weight: 700; color: #10b981; text-transform: uppercase; margin-bottom: 4px; display: flex; align-items: center; gap: 4px;">
                                                    <i class="fas fa-check-circle"></i> Data Baru (Sesuai Usulan Siswa)
                                                </div>
                                                <div style="font-size: 0.94rem; font-weight: 800; color: #10b981; word-break: break-word;">
                                                    ${escapeHtml(item.nilai_baru)}
                                                </div>
                                            </div>
                                        </div>

                                        ${item.alasan ? `
                                            <div style="font-size: 0.76rem; color: var(--text-muted); font-style: italic; margin-top: 4px;">
                                                <i class="fas fa-comment-dots me-1 text-primary"></i> Alasan: "${escapeHtml(item.alasan)}"
                                            </div>
                                        ` : ''}

                                        ${item.berkas_bukti_url ? `
                                            <div style="margin-top: 6px;">
                                                <button type="button" class="btn btn-outline btn-sm btn-preview-pdf-inline" data-url="${item.berkas_bukti_url}" data-title="Bukti Usulan: ${escapeHtml(item.kolom_label)} - ${escapeHtml(d.nama_siswa)}" style="padding: 3px 8px; font-size: 0.72rem; border-radius: 6px;">
                                                    <i class="fas fa-paperclip text-danger me-1"></i> Lihat Berkas Bukti Khusus PDF
                                                </button>
                                            </div>
                                        ` : ''}

                                        ${item.catatan_verifikasi ? `
                                            <div style="font-size: 0.72rem; background: rgba(0,0,0,0.06); padding: 5px 8px; border-radius: 6px; color: var(--text-muted); margin-top: 6px;">
                                                Catatan Verifikasi: ${escapeHtml(item.catatan_verifikasi)} ${item.verified_by ? ' &bull; Oleh: ' + escapeHtml(item.verified_by) : ''}
                                            </div>
                                        ` : ''}
                                    </div>
                                `;
                            }).join('')}
                        </div>
                    </div>

                    <!-- 3. KARTU DOKUMEN BUKTI & PRASYARAT (KK & IJAZAH) -->
                    <div style="background: var(--bg-hover); border-radius: 12px; padding: 14px 18px; border: 1px solid var(--border-color);">
                        <div style="font-size: 0.8rem; font-weight: 700; color: var(--text-color); margin-bottom: 10px; display: flex; align-items: center; justify-content: space-between;">
                            <span><i class="fas fa-folder-open text-primary me-1"></i> Dokumen Bukti &amp; Berkas Pendukung Siswa</span>
                            <span style="font-size: 0.72rem; color: var(--text-muted);">Pratinjau langsung tanpa buka tab baru</span>
                        </div>

                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 10px;">
                            <!-- Kartu Keluarga -->
                            <div style="background: var(--card-bg); padding: 10px 12px; border-radius: 8px; border: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; gap: 8px;">
                                <div>
                                    <div style="font-size: 0.78rem; font-weight: 700; color: var(--text-color);">Kartu Keluarga (KK)</div>
                                    <div style="font-size: 0.7rem;">
                                        ${d.kk.status === 'valid' ? '<span class="badge badge-success" style="padding:2px 6px;">Valid / Sesuai</span>' : '<span class="badge badge-outline" style="padding:2px 6px;">' + escapeHtml(d.kk.status) + '</span>'}
                                    </div>
                                </div>
                                ${d.kk.file_url ? `<button type="button" class="btn btn-outline btn-preview-pdf-inline" data-url="${d.kk.file_url}" data-title="Kartu Keluarga - ${escapeHtml(d.nama_siswa)}" style="padding: 4px 8px; font-size: 0.72rem; border-radius: 6px;"><i class="fas fa-eye me-1"></i> Lihat</button>` : `<span style="font-size:0.72rem; color:var(--text-muted);">-</span>`}
                            </div>

                            <!-- Ijazah SMP -->
                            <div style="background: var(--card-bg); padding: 10px 12px; border-radius: 8px; border: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; gap: 8px;">
                                <div>
                                    <div style="font-size: 0.78rem; font-weight: 700; color: var(--text-color);">Ijazah / SKL SMP</div>
                                    <div style="font-size: 0.7rem;">
                                        ${d.ijazah.status === 'valid' ? '<span class="badge badge-success" style="padding:2px 6px;">Valid / Sesuai</span>' : '<span class="badge badge-outline" style="padding:2px 6px;">' + escapeHtml(d.ijazah.status) + '</span>'}
                                    </div>
                                </div>
                                ${d.ijazah.file_url ? `<button type="button" class="btn btn-outline btn-preview-pdf-inline" data-url="${d.ijazah.file_url}" data-title="Ijazah SMP - ${escapeHtml(d.nama_siswa)}" style="padding: 4px 8px; font-size: 0.72rem; border-radius: 6px;"><i class="fas fa-eye me-1"></i> Lihat</button>` : `<span style="font-size:0.72rem; color:var(--text-muted);">-</span>`}
                            </div>

                            <!-- Akta Kelahiran -->
                            <div style="background: var(--card-bg); padding: 10px 12px; border-radius: 8px; border: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; gap: 8px;">
                                <div>
                                    <div style="font-size: 0.78rem; font-weight: 700; color: var(--text-color);">Akta Kelahiran</div>
                                    <div style="font-size: 0.7rem;">
                                        ${d.akta && d.akta.status === 'valid' ? '<span class="badge badge-success" style="padding:2px 6px;">Valid / Sesuai</span>' : '<span class="badge badge-outline" style="padding:2px 6px;">' + escapeHtml(d.akta?.status || '-') + '</span>'}
                                    </div>
                                </div>
                                ${d.akta && d.akta.file_url ? `<button type="button" class="btn btn-outline btn-preview-pdf-inline" data-url="${d.akta.file_url}" data-title="Akta Kelahiran - ${escapeHtml(d.nama_siswa)}" style="padding: 4px 8px; font-size: 0.72rem; border-radius: 6px;"><i class="fas fa-eye me-1"></i> Lihat</button>` : `<span style="font-size:0.72rem; color:var(--text-muted);">-</span>`}
                            </div>
                        </div>
                    </div>

                    <!-- 4. AREA FORMULIR KEPUTUSAN VALIDASI KESISWAAN -->
                    <div style="background: var(--card-bg); border-radius: 12px; padding: 16px 18px; border: 1px solid var(--border-color);">
                        <div style="font-size: 0.85rem; font-weight: 800; color: var(--text-color); margin-bottom: 10px;">
                            Tentukan Keputusan Verifikasi Usulan Data:
                        </div>

                        <div style="display: flex; gap: 20px; align-items: center; margin-bottom: 12px; flex-wrap: wrap;">
                            <label style="display: flex; align-items: center; gap: 6px; font-size: 0.88rem; font-weight: 700; color: #10b981; cursor: pointer;">
                                <input type="radio" name="usulan_status_decision" value="disetujui" ${d.status === 'disetujui' || d.status === 'sudah_ke_dapodik' ? 'checked' : ''} class="radio-usulan-decision">
                                <i class="fas fa-circle-check"></i> Setujui Seluruh Usulan (Data Otomatis Diperbarui)
                            </label>
                            <label style="display: flex; align-items: center; gap: 6px; font-size: 0.88rem; font-weight: 700; color: #ef4444; cursor: pointer;">
                                <input type="radio" name="usulan_status_decision" value="ditolak" ${d.status === 'ditolak' ? 'checked' : ''} class="radio-usulan-decision">
                                <i class="fas fa-circle-xmark"></i> Tolak Usulan (Tidak Sesuai / Berkas Tidak Sah)
                            </label>
                        </div>

                        <!-- Box Alasan Penolakan -->
                        <div id="boxPenolakanUsulan" style="display: ${d.status === 'ditolak' ? 'block' : 'none'}; border-top: 1px dashed var(--border-color); padding-top: 12px; margin-top: 10px;">
                            <div style="font-size: 0.76rem; font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">
                                Pilih Rekomendasi Alasan Penolakan Cepat:
                            </div>
                            <div style="display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 10px;">
                                ${rekomendasiList.map(rek => `
                                    <button type="button" class="btn btn-outline btn-chip-rekomendasi-usulan" data-text="${escapeHtml(rek)}" style="padding: 3px 8px; font-size: 0.72rem; border-radius: 6px;">
                                        <i class="fas fa-reply me-1"></i> ${escapeHtml(rek)}
                                    </button>
                                `).join('')}
                            </div>
                            <div style="margin-bottom: 8px;">
                                <label style="font-size: 0.78rem; font-weight: 700; color: var(--text-color); display: block; margin-bottom: 4px;">
                                    Alasan Penolakan untuk Disampaikan ke Siswa <span class="text-danger">*</span>:
                                </label>
                                <textarea id="textareaCatatanUsulan" class="form-control" rows="2" placeholder="Tuliskan alasan penolakan secara jelas..." style="font-size: 0.82rem;"></textarea>
                            </div>
                        </div>

                        <!-- Catatan jika sudah diverifikasi sebelumnya -->
                        ${d.verified_by ? `
                            <div style="font-size: 0.74rem; color: var(--text-muted); margin-top: 8px;">
                                Terakhir diverifikasi oleh: <strong>${escapeHtml(d.verified_by)}</strong> ${d.verified_at ? 'pada ' + escapeHtml(d.verified_at) : ''}
                            </div>
                        ` : ''}

                        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 14px; gap: 10px; flex-wrap: wrap;">
                            <div>
                                ${d.disetujui_count > 0 ? `
                                    <button type="button" class="btn btn-primary btn-sm btn-mark-dapodik" data-id="${d.peserta_didik_id}" data-nama="${escapeHtml(d.nama_siswa)}" style="font-weight: 700; border-radius: 8px;">
                                        <i class="fas fa-cloud-arrow-up me-1"></i> Tandai Sudah Di-update ke Dapodik
                                    </button>
                                ` : ''}
                            </div>
                            <button type="button" class="btn btn-primary" id="btnSimpanKeputusanUsulan" data-id="${d.peserta_didik_id}" style="padding: 8px 18px; font-size: 0.84rem; font-weight: 700; border-radius: 8px; display: inline-flex; align-items: center; gap: 6px;">
                                <i class="fas fa-check"></i> Simpan Keputusan Verifikasi
                            </button>
                        </div>
                    </div>
                </div>
            `;

            content.innerHTML = html;

            // Wire up decision radio
            content.querySelectorAll('.radio-usulan-decision').forEach(radio => {
                radio.addEventListener('change', function () {
                    const box = document.getElementById('boxPenolakanUsulan');
                    if (box) {
                        box.style.display = this.value === 'ditolak' ? 'block' : 'none';
                        if (this.value === 'ditolak') {
                            const ta = document.getElementById('textareaCatatanUsulan');
                            if (ta) ta.focus();
                        }
                    }
                });
            });

            // Wire up recommendation chips
            content.querySelectorAll('.btn-chip-rekomendasi-usulan').forEach(chip => {
                chip.addEventListener('click', function () {
                    const text = this.getAttribute('data-text');
                    const ta = document.getElementById('textareaCatatanUsulan');
                    if (ta && text) {
                        ta.value = text;
                        ta.focus();
                    }
                });
            });

            // Wire up save button
            const saveBtn = document.getElementById('btnSimpanKeputusanUsulan');
            if (saveBtn) {
                saveBtn.addEventListener('click', async function () {
                    const selectedRadio = content.querySelector('input[name="usulan_status_decision"]:checked');
                    if (!selectedRadio) {
                        Swal.fire('Peringatan', 'Silakan pilih keputusan verifikasi (Disetujui atau Ditolak).', 'warning');
                        return;
                    }

                    const status = selectedRadio.value;
                    const ta = document.getElementById('textareaCatatanUsulan');
                    const catatan = ta ? ta.value.trim() : '';

                    if (status === 'ditolak' && !catatan) {
                        Swal.fire('Wajib Alasan', 'Untuk status Ditolak, Anda wajib mengisi alasan penolakan untuk disampaikan ke peserta didik.', 'warning');
                        if (ta) ta.focus();
                        return;
                    }

                    const originalBtnText = this.innerHTML;
                    this.disabled = true;
                    this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';

                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

                    try {
                        const postRes = await fetch(`/dashboard/kesiswaan/peserta-didik/usulan/${encodeURIComponent(d.peserta_didik_id)}/verifikasi`, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': csrfToken,
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            body: JSON.stringify({
                                status: status,
                                catatan_verifikasi: catatan,
                            })
                        });

                        const postJson = await postRes.json();
                        this.disabled = false;
                        this.innerHTML = originalBtnText;

                        if (postJson.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil!',
                                text: postJson.message,
                                timer: 1800,
                                showConfirmButton: false,
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire('Gagal', postJson.message || 'Terjadi kesalahan.', 'error');
                        }
                    } catch (err) {
                        this.disabled = false;
                        this.innerHTML = originalBtnText;
                        Swal.fire('Error', 'Gagal menghubungi server.', 'error');
                    }
                });
            }

        } catch (err) {
            loading.style.display = 'none';
            Swal.fire('Error', 'Terjadi kesalahan saat memuat rincian usulan.', 'error');
            modal.style.display = 'none';
            document.body.style.overflow = '';
        }
    };

    // 4b. Tandai Usulan Perubahan Selesai Di-update ke Dapodik
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-mark-dapodik');
        if (btn) {
            e.preventDefault();
            const id = btn.getAttribute('data-id');
            const nama = btn.getAttribute('data-nama');
            const kolom = btn.getAttribute('data-kolom');

            const confirmMsg = kolom
                ? `Apakah perubahan kolom <strong>${escapeHtml(kolom)}</strong> untuk siswa <strong>${escapeHtml(nama)}</strong> sudah selesai Anda input / sinkronkan ke aplikasi Dapodik pusat?`
                : `Apakah seluruh usulan perubahan data siswa <strong>${escapeHtml(nama)}</strong> yang telah disetujui sudah selesai Anda input / sinkronkan ke aplikasi Dapodik pusat?`;

            Swal.fire({
                title: 'Tandai Diupdate ke Dapodik?',
                html: `
                    <div style="text-align: left; font-size: 0.88rem; line-height: 1.5;">
                        <p>${confirmMsg}</p>
                        <div style="margin-top: 10px;">
                            <label style="display:block; font-weight:700; margin-bottom:4px; font-size: 0.8rem;">Catatan Operator Dapodik (Opsional):</label>
                            <input id="swalCatatanDapodik" class="swal2-input" placeholder="Contoh: Selesai di-input pada Dapodik 2027 patch A" style="width: 100%; margin: 0; box-sizing: border-box; font-size: 0.85rem;">
                        </div>
                    </div>
                `,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: '<i class="fas fa-cloud-arrow-up"></i> Ya, Sudah Diupdate ke Dapodik',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#10b981',
            }).then(async (result) => {
                if (result.isConfirmed) {
                    const catatan = document.getElementById('swalCatatanDapodik')?.value || '';
                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
                    try {
                        const res = await fetch(`/dashboard/kesiswaan/peserta-didik/usulan/${id}/dapodik`, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': csrfToken,
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            body: JSON.stringify({ catatan_verifikasi: catatan })
                        });
                        const json = await res.json();
                        if (json.status === 'success') {
                            Swal.fire('Berhasil!', json.message, 'success').then(() => location.reload());
                        } else {
                            Swal.fire('Gagal!', json.message || 'Terjadi kesalahan.', 'error');
                        }
                    } catch (err) {
                        Swal.fire('Error!', 'Gagal menghubungi server.', 'error');
                    }
                }
            });
        }
    });

    // 5. Modal Detail Biodata Lengkap Peserta Didik
    window.openBiodataPesertaDidikModal = async function (id) {
        const modal = document.getElementById("biodataModal");
        const bioNama = document.getElementById("bioNama");
        const bioRombel = document.getElementById("bioRombel");
        const bioLoading = document.getElementById("bioLoading");
        const bioContent = document.getElementById("bioContent");

        if (!modal) return;

        bioNama.textContent = "Biodata Peserta Didik";
        bioRombel.textContent = "Memuat data...";
        bioLoading.style.display = "block";
        bioContent.style.display = "none";
        modal.style.display = "flex";
        document.body.style.overflow = "hidden";

        try {
            const res = await fetch(
                "/dashboard/kesiswaan/peserta-didik/" + encodeURIComponent(id),
                {
                    headers: {
                        Accept: "application/json",
                        "X-Requested-With": "XMLHttpRequest",
                    },
                }
            );
            const json = await res.json();

            bioLoading.style.display = "none";
            bioContent.style.display = "block";

            if ((json.status === "success" || json.success) && json.data) {
                const d = json.data;
                bioNama.textContent = d.nama || "Tanpa Nama";
                bioRombel.textContent =
                    [d.nama_rombel || d.nama_rombel_terakhir || d.rombel_terakhir, d.kurikulum_id_str]
                        .filter(Boolean)
                        .join(" • ") || "-";

                const fotoBox = document.getElementById("bioFotoContainer");
                if (fotoBox) {
                    const fUrl = json.foto_url || d.foto_url;
                    if (fUrl) {
                        fotoBox.innerHTML = `<img src="${fUrl}?v=${Date.now()}" alt="Foto ${escapeHtml(d.nama)}" style="width: 100%; height: 100%; object-fit: cover;">`;
                    } else {
                        fotoBox.innerHTML = `<i class="fas fa-user-graduate text-primary" style="font-size: 1.3rem;"></i>`;
                    }
                }

                document.getElementById("bioNisn").textContent =
                    [
                        d.nisn ? "NISN: " + d.nisn : null,
                        d.nipd ? "NIPD: " + d.nipd : null,
                    ]
                        .filter(Boolean)
                        .join(" / ") || "-";
                document.getElementById("bioNik").textContent = d.nik || "-";
                document.getElementById("bioJk").textContent =
                    d.jenis_kelamin === "L"
                        ? "Laki-Laki (L)"
                        : d.jenis_kelamin === "P"
                          ? "Perempuan (P)"
                          : "-";
                document.getElementById("bioTtl").textContent =
                    [d.tempat_lahir, d.tanggal_lahir].filter(Boolean).join(", ") || "-";
                document.getElementById("bioAgama").textContent =
                    d.agama_id_str || "-";
                document.getElementById("bioAnak").textContent = d.anak_keberapa
                    ? "Anak ke-" + d.anak_keberapa
                    : "-";

                const tb = d.tinggi_badan ? d.tinggi_badan + " cm" : null;
                const bb = d.berat_badan ? d.berat_badan + " kg" : null;
                document.getElementById("bioFisik").textContent =
                    [tb, bb].filter(Boolean).join(" • ") || "Belum dicatat";

                document.getElementById("bioKhusus").textContent =
                    d.kebutuhan_khusus || "Tidak Ada";

                const pendaftaranStr = [
                    d.jenis_pendaftaran_id_str ||
                        (json.anggota
                            ? json.anggota.jenis_pendaftaran_id_str
                            : null) ||
                        "Peserta Didik",
                    d.sekolah_asal ? "Asal: " + d.sekolah_asal : null,
                ]
                    .filter(Boolean)
                    .join(" • ");
                document.getElementById("bioPendaftaran").textContent =
                    pendaftaranStr || "-";

                document.getElementById("bioTglMasuk").textContent =
                    d.tanggal_masuk_sekolah || "-";

                const regArr = [
                    d.registrasi_id ? "Reg: " + d.registrasi_id : null,
                    json.anggota && json.anggota.anggota_rombel_id
                        ? "Anggota ID: " + json.anggota.anggota_rombel_id
                        : null,
                ].filter(Boolean);
                document.getElementById("bioRegId").textContent =
                    regArr.length > 0 ? regArr.join(" • ") : "-";

                document.getElementById("bioAyah").textContent =
                    [d.nama_ayah, d.pekerjaan_ayah_id_str]
                        .filter(Boolean)
                        .join(" • Pekerjaan: ") || "-";
                document.getElementById("bioIbu").textContent =
                    [d.nama_ibu, d.pekerjaan_ibu_id_str]
                        .filter(Boolean)
                        .join(" • Pekerjaan: ") || "-";
                document.getElementById("bioWali").textContent =
                    [d.nama_wali, d.pekerjaan_wali_id_str]
                        .filter(Boolean)
                        .join(" • Pekerjaan: ") || "-";

                const hpEmail = [
                    d.nomor_telepon_seluler || d.no_hp,
                    d.email,
                ].filter(Boolean).join(" / ");
                document.getElementById("bioHp").textContent = hpEmail || "-";
                document.getElementById("bioAlamat").textContent =
                    d.alamat_jalan || "-";

                const mapelSec = document.getElementById("bioMapelSection");
                const mapelList = document.getElementById("bioMapelList");
                const jmlMapel = document.getElementById("bioJmlMapel");
                if (mapelSec && mapelList) {
                    if (json.pembelajaran && json.pembelajaran.length > 0) {
                        mapelSec.style.display = "block";
                        if (jmlMapel) jmlMapel.textContent = `${json.total_mapel || json.pembelajaran.length} Mapel (${json.total_jam || 0} Jam)`;
                        mapelList.innerHTML = json.pembelajaran.map(p => `
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <td style="padding: 6px 10px; font-weight: 600;">${escapeHtml(p.nama_mata_pelajaran || p.mata_pelajaran_id_str || '-')}</td>
                                <td style="padding: 6px 10px; color: var(--text-muted);">${escapeHtml(p.nama_guru || '-')}</td>
                                <td style="padding: 6px 10px; text-align: center; font-weight: 700;">${p.jam_mengajar_per_minggu || 0}</td>
                            </tr>
                        `).join('');
                    } else {
                        mapelSec.style.display = "none";
                        mapelList.innerHTML = "";
                    }
                }
            } else {
                Swal.fire('Error', json.message || 'Gagal memuat biodata peserta didik', 'error');
                modal.style.display = "none";
                document.body.style.overflow = "";
            }
        } catch (err) {
            bioLoading.style.display = "none";
            Swal.fire('Error', 'Terjadi kesalahan saat memuat biodata', 'error');
            modal.style.display = "none";
            document.body.style.overflow = "";
        }
    };

    window.closeBiodataModal = function () {
        const modal = document.getElementById("biodataModal");
        if (modal) modal.style.display = "none";
        document.body.style.overflow = "";
    };

    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    // Delegated click event listener for clicking student photo or name
    document.addEventListener('click', function (e) {
        const target = e.target.closest('[data-biodata-id]');
        if (target) {
            e.preventDefault();
            const id = target.getAttribute('data-biodata-id');
            if (id) {
                window.openBiodataPesertaDidikModal(id);
            }
        }
    });

    // Modal Preview Pasfoto Siswa Tidak Aktif
    window.openPhotoPreviewModal = function (url, name) {
        const modal = document.getElementById("photoPreviewModal");
        const img = document.getElementById("photoPreviewImg");
        const nameEl = document.getElementById("photoPreviewName");
        if (!modal || !img) return;
        img.src = url;
        if (nameEl) nameEl.textContent = name || "Pasfoto Peserta Didik";
        modal.style.display = "flex";
        document.body.style.overflow = "hidden";
    };

    window.closePhotoPreviewModal = function () {
        const modal = document.getElementById("photoPreviewModal");
        if (modal) modal.style.display = "none";
        document.body.style.overflow = "";
    };

    // Datatable Realtime Live Search, Entri perPage, dan Filter Standar SAE
    function applyFilter() {
        const url = new URL(window.location.href);
        const searchInput = document.getElementById("liveSearch");
        const filterRombel = document.getElementById("filterRombel");
        const filterGender = document.getElementById("filterGender");
        const filterStatus = document.getElementById("filterStatus");
        const filterTahunLulus = document.getElementById("filterTahunLulus");
        const perPageSelect = document.getElementById("perPageSelect");

        if (searchInput && searchInput.value.trim()) {
            url.searchParams.set("q", searchInput.value.trim());
        } else {
            url.searchParams.delete("q");
        }

        if (filterRombel && filterRombel.value) {
            url.searchParams.set("rombel", filterRombel.value);
        } else {
            url.searchParams.delete("rombel");
        }

        if (filterGender && filterGender.value) {
            url.searchParams.set("gender", filterGender.value);
        } else {
            url.searchParams.delete("gender");
        }

        if (filterStatus && filterStatus.value) {
            url.searchParams.set("status", filterStatus.value);
        } else {
            url.searchParams.delete("status");
        }

        if (filterTahunLulus && filterTahunLulus.value.trim()) {
            url.searchParams.set("tahun_lulus", filterTahunLulus.value.trim());
        } else if (filterTahunLulus) {
            url.searchParams.delete("tahun_lulus");
        }

        if (perPageSelect && perPageSelect.value) {
            url.searchParams.set("perPage", perPageSelect.value);
        }

        // Reset sub-tab pages
        url.searchParams.delete("aktif_page");
        url.searchParams.delete("tidak_aktif_page");
        url.searchParams.delete("alumni_page");
        url.searchParams.delete("berkas_page");
        url.searchParams.delete("usulan_page");
        url.searchParams.delete("page");

        if (typeof window.refreshLiveTable === "function") {
            window.refreshLiveTable(url.toString());
        } else {
            window.location.href = url.toString();
        }
    }

    const searchInput = document.getElementById("liveSearch");
    const clearBtn = document.getElementById("clearSearch");
    if (searchInput) {
        let timer = null;
        searchInput.addEventListener("input", function () {
            if (clearBtn) clearBtn.classList.toggle("visible", this.value.trim().length > 0);
            clearTimeout(timer);
            timer = setTimeout(applyFilter, 450);
        });

        searchInput.addEventListener("keydown", function (e) {
            if (e.key === "Enter") {
                e.preventDefault();
                clearTimeout(timer);
                applyFilter();
            }
        });
    }

    if (clearBtn) {
        clearBtn.addEventListener("click", function () {
            if (searchInput) {
                searchInput.value = "";
                clearBtn.classList.remove("visible");
                applyFilter();
            }
        });
    }

    // Delegated Change Event untuk Filter Toolbar (Tahan AJAX DOM Swapping)
    document.addEventListener("change", function (e) {
        if (e.target.matches("#filterRombel, #filterGender, #filterStatus, #perPageSelect")) {
            applyFilter();
        }
    });

    const filterTahunLulus = document.getElementById("filterTahunLulus");
    if (filterTahunLulus) {
        filterTahunLulus.addEventListener("change", applyFilter);
        filterTahunLulus.addEventListener("keydown", function (e) {
            if (e.key === "Enter") {
                e.preventDefault();
                applyFilter();
            }
        });
    }

    // Delegated Click Event untuk Header Kolom Sortable
    document.addEventListener("click", function (e) {
        const th = e.target.closest(".sortable-th");
        if (th) {
            const sortKey = th.getAttribute("data-sort");
            if (!sortKey) return;
            const url = new URL(window.location.href);
            const curSort = url.searchParams.get("sort") || "nama";
            const curDir = url.searchParams.get("sort_dir") || "asc";
            let newDir = "asc";
            if (curSort === sortKey && curDir === "asc") {
                newDir = "desc";
            }
            url.searchParams.set("sort", sortKey);
            url.searchParams.set("sort_dir", newDir);
            url.searchParams.delete("page");
            url.searchParams.delete("aktif_page");
            url.searchParams.delete("tidak_aktif_page");
            url.searchParams.delete("berkas_page");
            url.searchParams.delete("usulan_page");
            url.searchParams.delete("alumni_page");

            if (typeof window.refreshLiveTable === "function") {
                window.refreshLiveTable(url.toString());
            } else {
                window.location.href = url.toString();
            }
        }
    });
});
