/**
 * Modal Validasi Berkas Digital — kartu stepper (1 dokumen per tampilan) + PDF inline 2 kolom.
 * Dipakai bersama oleh: Wali Kelas (validasi-data) & Kesiswaan (peserta-didik tab Berkas).
 *
 * Tiap halaman cukup memanggil window.SaeVbModal.render(content, cfg) setelah data berkas didapat.
 */
(function () {
    'use strict';

    const escapeHtml = (str) => {
        if (!str) return '';
        return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    };

    function statusBadgeHtml(item) {
        if (item.status === 'valid') {
            return `<span class="badge badge-success" style="font-size: var(--text-xs, 0.75rem); padding: 3px 8px;"><i class="fas fa-circle-check me-1"></i> Valid</span>`;
        }
        if (item.status === 'tidak_valid') {
            return `<span class="badge badge-danger" style="font-size: var(--text-xs, 0.75rem); padding: 3px 8px;"><i class="fas fa-circle-xmark me-1"></i> Tidak Valid</span>`;
        }
        return item.is_uploaded
            ? `<span class="badge badge-warning" style="font-size: var(--text-xs, 0.75rem); padding: 3px 8px;"><i class="fas fa-clock me-1"></i> Menunggu</span>`
            : `<span class="badge badge-outline" style="font-size: var(--text-xs, 0.75rem); padding: 3px 8px;"><i class="fas fa-minus me-1"></i> Belum Unggah</span>`;
    }

    /**
     * content : elemen #modalVbContent
     * cfg     : { items, siswa, pdId, rekomendasiList, canUpload,
     *             uploadUrl(pdId), verifyUrl(pdId), csrf(), reopen(id, nama), finish() }
     */
    window.SaeVbModal = {
        escapeHtml,
        render(content, cfg) {
            const {
                items = [], siswa = {}, pdId, rekomendasiList = [], canUpload = true,
                uploadUrl, verifyUrl, csrf, reopen, finish, onChanged = () => {},
                isAdmin = false, deleteUrl = (bid) => `/dashboard/berkas/${encodeURIComponent(bid)}`,
            } = cfg;

            const jumlahFile = items.filter((it) => it.is_uploaded).length;
            let html = `<div class="vb-cards" style="display: flex; flex-direction: column; gap: 16px;">`;

            items.forEach((item, stepIdx) => {
                html += `
                    <div class="card card-berkas-item vb-card" id="cardBerkas_${item.jenis_berkas}" data-key="${item.jenis_berkas}" data-step="${stepIdx}" data-uploaded="${item.is_uploaded ? '1' : '0'}" data-label="${escapeHtml(item.label)}" data-selected-status="${item.status || ''}">
                        <div class="vb-col-pdf">
                            ${item.is_uploaded ? `
                                <div class="vb-pdf-wrap" style="border: 1px solid var(--border-color); border-radius: var(--radius-md, 8px); overflow: hidden; background: var(--text-main, #0f172a); height: 100%;">
                                    <iframe data-src="${item.file_url}" title="${escapeHtml(item.label)}" loading="lazy"
                                        style="width: 100%; height: 100%; border: none; display: block;"></iframe>
                                </div>
                            ` : `
                                <div class="vb-pdf-wrap" style="height: 100%; min-height: 240px; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 10px; color: var(--text-muted); border: 1px dashed var(--border-color); border-radius: var(--radius-md, 8px); background: var(--bg-hover);">
                                    <i class="fas fa-file-circle-xmark" style="font-size: var(--text-3xl, 1.875rem); opacity: 0.5;"></i>
                                    <span style="font-size: var(--text-sm, 0.8125rem);">Belum ada berkas digital</span>
                                </div>
                            `}
                        </div>

                        <div class="vb-col-panel">
                            <div class="vb-stepbar">
                                <div style="display: flex; align-items: baseline; gap: 8px; min-width: 0; flex-wrap: wrap;">
                                    <span class="vb-step-counter" style="font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-color); white-space: nowrap;">Dokumen 1 / 1</span>
                                    <span class="vb-step-label" style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"></span>
                                </div>
                                <div style="display: flex; align-items: center; gap: 6px; flex-shrink: 0;">
                                    <button type="button" class="vb-prev btn btn-outline" title="Dokumen sebelumnya" aria-label="Dokumen sebelumnya"
                                        style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px);">
                                        <i class="fas fa-chevron-left"></i>
                                    </button>
                                    <button type="button" class="vb-next btn btn-outline" title="Dokumen selanjutnya" aria-label="Dokumen selanjutnya"
                                        style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px);">
                                        <i class="fas fa-chevron-right"></i>
                                    </button>
                                </div>
                            </div>

                            <div style="display: flex; justify-content: space-between; align-items: center; gap: 10px; margin-bottom: 10px;">
                                <div style="display: flex; align-items: center; gap: 10px; min-width: 0;">
                                    <div style="width: 34px; height: 34px; border-radius: var(--radius-md, 8px); background: rgba(99,102,241,0.12); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: var(--text-md, 1rem); flex-shrink: 0;">
                                        <i class="fas ${item.icon}"></i>
                                    </div>
                                    <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                                        <span style="font-weight: 700; font-size: var(--text-base, 0.875rem); color: var(--text-color);">${escapeHtml(item.label)}</span>
                                        ${item.wajib ? '<span class="badge badge-outline" style="font-size: var(--text-2xs, 0.7rem); padding: 1px 6px;">Wajib</span>' : ''}
                                    </div>
                                </div>
                                <div class="status-badge-container">${statusBadgeHtml(item)}</div>
                            </div>

                            <div style="display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 8px 12px; background: var(--bg-hover); border: 1px solid var(--border-color); border-radius: var(--radius-md, 8px); margin-bottom: 12px; flex-wrap: wrap;">
                                <div style="display: flex; align-items: center; gap: 8px; min-width: 0; font-size: var(--text-sm, 0.8125rem);">
                                    ${item.is_uploaded ? `
                                        <i class="fas fa-file-pdf" style="color: var(--danger, #ef4444); font-size: 1.15rem; flex-shrink: 0;"></i>
                                        <span style="font-weight: 600; color: var(--text-color); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 200px;" title="${escapeHtml(item.file_name)}">${escapeHtml(item.file_name)}</span>
                                    ` : `
                                        <span class="text-muted" style="font-size: var(--text-sm, 0.8125rem);"><i class="fas fa-minus-circle me-1"></i>Belum ada file digital</span>
                                    `}
                                </div>
                                <div style="display: flex; align-items: center; gap: 6px;">
                                    ${isAdmin && item.is_uploaded ? `
                                        <button type="button" class="btn btn-outline vb-delete-berkas" data-berkas-id="${item.berkas_id}" data-label="${escapeHtml(item.label)}" title="Hapus dokumen (Administrator)"
                                            style="padding: 4px 10px; font-size: var(--text-xs, 0.75rem); border-radius: var(--radius-sm, 6px); cursor: pointer; color: var(--danger, #ef4444); border-color: var(--danger, #ef4444);">
                                            <i class="fas fa-trash me-1"></i> Hapus
                                        </button>
                                    ` : ''}
                                    ${canUpload ? `
                                        <input type="file" class="vb-file-input input-file-tendik" data-pd-id="${pdId}" data-key="${item.jenis_berkas}" accept=".pdf,.jpg,.jpeg,.png" style="display: none;">
                                        <button type="button" class="btn btn-outline vb-upload-trigger" style="padding: 4px 10px; font-size: var(--text-xs, 0.75rem); border-radius: var(--radius-sm, 6px); cursor: pointer;" title="Unggah / ganti file">
                                            <i class="fas fa-upload me-1"></i> ${item.is_uploaded ? 'Ganti File' : 'Unggah File'}
                                        </button>
                                    ` : ''}
                                </div>
                            </div>

                            <div style="display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap;">
                                <div class="vb-status-group">
                                    <label class="vb-status-opt vb-status-valid">
                                        <input type="radio" name="status_${item.jenis_berkas}" value="valid" ${item.status === 'valid' ? 'checked' : ''} class="radio-status-berkas" data-key="${item.jenis_berkas}">
                                        <i class="fas fa-circle-check"></i> <span>Valid</span>
                                    </label>
                                    <label class="vb-status-opt vb-status-invalid">
                                        <input type="radio" name="status_${item.jenis_berkas}" value="tidak_valid" ${item.status === 'tidak_valid' ? 'checked' : ''} class="radio-status-berkas" data-key="${item.jenis_berkas}">
                                        <i class="fas fa-circle-xmark"></i> <span>Tidak Valid</span>
                                    </label>
                                </div>
                                <button type="button" class="btn btn-primary btn-save-verif-berkas" data-pd-id="${pdId}" data-key="${item.jenis_berkas}" data-label="${escapeHtml(item.label)}" style="padding: 5px 14px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; border-radius: var(--radius-sm, 6px); display: inline-flex; align-items: center; gap: 6px;">
                                    <i class="fas fa-check"></i> Simpan
                                </button>
                            </div>

                            <div id="boxPenolakan_${item.jenis_berkas}" style="display: ${item.status === 'tidak_valid' ? 'block' : 'none'}; margin-top: 10px; padding-top: 10px; border-top: 1px dashed var(--border-color);">
                                ${rekomendasiList.length ? `
                                    <div style="display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 8px;">
                                        ${rekomendasiList.map(rek => `
                                            <button type="button" class="btn btn-outline btn-chip-rekomendasi" data-target-text="textarea_${item.jenis_berkas}" data-text="${escapeHtml(rek)}" style="padding: 2px 8px; font-size: var(--text-xs, 0.75rem); border-radius: var(--radius-sm, 6px); cursor: pointer;">
                                                ${escapeHtml(rek)}
                                            </button>
                                        `).join('')}
                                    </div>
                                ` : ''}
                                <textarea id="textarea_${item.jenis_berkas}" class="form-control input-catatan-penolakan" rows="2" placeholder="Catatan perbaikan untuk siswa..." style="font-size: var(--text-sm, 0.8125rem); width: 100%; border-radius: var(--radius-sm, 6px);">${escapeHtml(item.catatan_penolakan || '')}</textarea>
                            </div>
                        </div>
                    </div>
                `;
            });

            html += `</div>`;
            content.innerHTML = html;

            // -------- Stepper: satu dokumen terunggah per tampilan, urut.
            // Dokumen tanpa berkas dilewati. Bila tak ada berkas → selesai (finish).
            const allCards = Array.from(content.querySelectorAll('.card-berkas-item'));
            const vbCards = allCards.filter((c) => c.getAttribute('data-uploaded') === '1');
            allCards.forEach((c) => { if (c.getAttribute('data-uploaded') !== '1') c.classList.add('vb-hidden'); });

            if (!vbCards.length) {
                finish();
                return { vbCards: [], vbShow: () => {}, vbFinish: finish };
            }

            let vbIndex = 0;
            const vbShow = (i) => {
                vbIndex = Math.max(0, Math.min(i, vbCards.length - 1));
                vbCards.forEach((c, idx) => {
                    const active = idx === vbIndex;
                    c.classList.toggle('vb-hidden', !active);

                    // Lazy PDF: muat iframe hanya untuk dokumen yang tampil.
                    // Dokumen lain dibebaskan (src dikosongkan) agar PDF besar
                    // tidak membanjiri memori/bandwidth saat modal dibuka.
                    const frame = c.querySelector('iframe');
                    if (frame) {
                        const src = frame.getAttribute('data-src') || '';
                        if (active) {
                            if (frame.getAttribute('src') !== src) frame.setAttribute('src', src);
                        } else if (frame.getAttribute('src')) {
                            frame.setAttribute('src', 'about:blank');
                        }
                    }
                });

                const onLast = vbIndex === vbCards.length - 1;
                vbCards.forEach((c) => {
                    const counter = c.querySelector('.vb-step-counter');
                    const labelEl = c.querySelector('.vb-step-label');
                    const prevBtn = c.querySelector('.vb-prev');
                    const nextBtn = c.querySelector('.vb-next');
                    if (counter) counter.textContent = `Dokumen ${vbIndex + 1} / ${vbCards.length}`;
                    if (labelEl) labelEl.textContent = c.getAttribute('data-label') || '';
                    if (prevBtn) prevBtn.disabled = vbIndex === 0;
                    if (nextBtn) {
                        // Dokumen terakhir → tombol jadi "Selesai & tutup", tetap AKTIF.
                        nextBtn.disabled = false;
                        nextBtn.title = onLast ? 'Selesai & tutup' : 'Dokumen selanjutnya';
                        nextBtn.innerHTML = onLast ? '<i class="fas fa-check"></i>' : '<i class="fas fa-chevron-right"></i>';
                    }
                });
                content.scrollTop = 0;
            };
            const vbFinish = () => finish();

            // Listener delegasi di #modalVbContent dipasang ulang tiap buka → pakai token
            // agar hanya handler sesi terakhir yang jalan (hindari state basi menutup modal).
            const vbToken = (content.__vbToken || 0) + 1;
            content.__vbToken = vbToken;
            content.addEventListener('click', (e) => {
                if (content.__vbToken !== vbToken) return;
                if (e.target.closest('.vb-prev')) { e.preventDefault(); vbShow(vbIndex - 1); }
                else if (e.target.closest('.vb-next')) {
                    e.preventDefault();
                    if (vbIndex === vbCards.length - 1) vbFinish();
                    else vbShow(vbIndex + 1);
                } else if (e.target.closest('.vb-delete-berkas')) {
                    e.preventDefault();
                    const btn = e.target.closest('.vb-delete-berkas');
                    const berkasId = btn.getAttribute('data-berkas-id');
                    const label = btn.getAttribute('data-label') || 'dokumen';
                    if (!berkasId) return;
                    Swal.fire({
                        icon: 'warning',
                        title: 'Hapus Dokumen?',
                        html: `Dokumen <strong>${escapeHtml(label)}</strong> akan dihapus permanen. Tindakan ini tidak dapat dibatalkan.`,
                        showCancelButton: true,
                        confirmButtonText: 'Ya, Hapus',
                        cancelButtonText: 'Batal',
                        confirmButtonColor: '#ef4444',
                    }).then(async (r) => {
                        if (!r.isConfirmed) return;
                        btn.disabled = true;
                        try {
                            const res = await fetch(deleteUrl(berkasId), {
                                method: 'DELETE',
                                headers: { 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                            });
                            const json = await res.json();
                            if (res.ok && json.status === 'success') {
                                onChanged();
                                Swal.fire({ icon: 'success', title: 'Terhapus', text: json.message, timer: 1400, showConfirmButton: false });
                                reopen(pdId, siswa.nama);
                            } else {
                                btn.disabled = false;
                                Swal.fire('Gagal', json.message || 'Tidak dapat menghapus dokumen.', 'error');
                            }
                        } catch (err) {
                            btn.disabled = false;
                            Swal.fire('Kesalahan Jaringan', 'Tidak dapat terhubung ke server.', 'error');
                        }
                    });
                }
            });
            vbShow(0);

            content.querySelectorAll('.radio-status-berkas').forEach((radio) => {
                radio.addEventListener('change', function () {
                    const key = this.getAttribute('data-key');
                    const box = document.getElementById(`boxPenolakan_${key}`);
                    if (box) box.style.display = this.value === 'tidak_valid' ? 'block' : 'none';
                    const card = document.getElementById(`cardBerkas_${key}`);
                    if (card) card.setAttribute('data-selected-status', this.value);
                });
            });

            content.querySelectorAll('.btn-chip-rekomendasi').forEach((chip) => {
                chip.addEventListener('click', function () {
                    const textarea = document.getElementById(this.getAttribute('data-target-text'));
                    if (textarea) { textarea.value = this.getAttribute('data-text'); textarea.focus(); }
                });
            });

            // Unggah / ganti file
            content.querySelectorAll('.vb-upload-trigger').forEach((btn) => {
                btn.addEventListener('click', () => {
                    const input = btn.closest('.vb-col-panel')?.querySelector('.vb-file-input');
                    if (input) input.click();
                });
            });

            content.querySelectorAll('.vb-file-input').forEach((fileInput) => {
                fileInput.addEventListener('change', async function () {
                    const file = this.files[0];
                    if (!file) return;
                    const key = this.getAttribute('data-key');
                    const formData = new FormData();
                    formData.append('jenis_berkas', key);
                    formData.append('file', file);

                    Swal.fire({ title: 'Mengunggah Berkas...', text: 'File sedang diproses dan otomatis diverifikasi VALID.', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
                    try {
                        const upRes = await fetch(uploadUrl(pdId), {
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                            body: formData,
                        });
                        const upJson = await upRes.json();
                        if (upRes.ok && upJson.status === 'success') {
                            onChanged();
                            Swal.fire({ icon: 'success', title: 'Berhasil Diunggah!', text: upJson.message, timer: 1800, showConfirmButton: false });
                            reopen(pdId, siswa.nama);
                        } else {
                            Swal.fire('Gagal Unggah', upJson.message || 'Terjadi kesalahan sistem.', 'error');
                        }
                    } catch (err) {
                        Swal.fire('Kesalahan Jaringan', 'Tidak dapat terhubung ke server.', 'error');
                    }
                });
            });

            content.querySelectorAll('.btn-save-verif-berkas').forEach((btn) => {
                btn.addEventListener('click', async function () {
                    const key = this.getAttribute('data-key');
                    const card = document.getElementById(`cardBerkas_${key}`) || this.closest('.card-berkas-item');
                    const selectedRadio = card ? card.querySelector(`input[name="status_${key}"]:checked`) : null;
                    const status = selectedRadio ? selectedRadio.value : (card ? card.getAttribute('data-selected-status') : '');

                    if (!status || (status !== 'valid' && status !== 'tidak_valid')) {
                        Swal.fire('Peringatan', 'Silakan pilih status validasi (Valid atau Tidak Valid).', 'warning');
                        return;
                    }

                    const textarea = document.getElementById(`textarea_${key}`);
                    const catatan = textarea ? textarea.value.trim() : '';
                    if (status === 'tidak_valid' && !catatan) {
                        Swal.fire('Wajib Catatan', 'Untuk status Tidak Valid, Anda wajib mengisi catatan atau memilih rekomendasi alasan penolakan.', 'warning');
                        if (textarea) textarea.focus();
                        return;
                    }

                    const originalBtnText = this.innerHTML;
                    this.disabled = true;
                    this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';

                    try {
                        const postRes = await fetch(verifyUrl(pdId), {
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': csrf(), 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                            body: JSON.stringify({ jenis_berkas: key, status, catatan_penolakan: catatan, rekomendasi_penolakan: catatan }),
                        });
                        const postJson = await postRes.json();
                        this.disabled = false;
                        this.innerHTML = originalBtnText;

                        if (postRes.ok && postJson.status === 'success') {
                            onChanged();
                            Swal.fire({ icon: 'success', title: 'Berhasil!', text: postJson.message, timer: 1200, showConfirmButton: false });
                            if (card) {
                                const badgeContainer = card.querySelector('.status-badge-container');
                                if (badgeContainer) badgeContainer.innerHTML = statusBadgeHtml({ status });
                                card.setAttribute('data-selected-status', status);
                                const currentRadio = card.querySelector(`input[name="status_${key}"][value="${status}"]`);
                                if (currentRadio) currentRadio.checked = true;
                            }
                            // Simpan cepat: lanjut ke dokumen berikutnya; bila terakhir → selesai & tutup.
                            const curIdx = vbCards.indexOf(card);
                            if (curIdx > -1) {
                                if (curIdx < vbCards.length - 1) setTimeout(() => vbShow(curIdx + 1), 350);
                                else setTimeout(() => vbFinish(), 300);
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

            return { vbCards, vbShow, vbFinish };
        },
    };
})();
