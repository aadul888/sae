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

    document.addEventListener('click', function (e) {
        const closeBtn = e.target.closest('.close-modal[data-target="#modalVerifikasiBerkasDigital"]');
        if (closeBtn) {
            e.preventDefault();
            const modal = document.getElementById('modalVerifikasiBerkasDigital');
            if (modal) {
                modal.style.display = 'none';
                document.body.style.overflow = '';
            }
            if (berkasStatusChanged) {
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
                    <div class="card card-berkas-item" id="cardBerkas_${item.jenis_berkas}" style="padding: 16px; border-radius: 12px; border: 1.5px solid var(--border-color); background: var(--bg-hover);">
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
                            <button type="button" class="btn btn-outline btn-preview-pdf-inline" data-url="${item.file_url}" data-title="${escapeHtml(item.label)} - ${escapeHtml(siswa.nama)}" style="padding: 5px 12px; font-size: 0.78rem; border-radius: 6px; cursor: pointer;">
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

            // Wire up radio toggle show/hide rejection box
            content.querySelectorAll('.radio-status-berkas').forEach(radio => {
                radio.addEventListener('change', function () {
                    const key = this.getAttribute('data-key');
                    const box = document.getElementById(`boxPenolakan_${key}`);
                    if (box) {
                        box.style.display = this.value === 'tidak_valid' ? 'block' : 'none';
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

            // Wire up save button per berkas
            content.querySelectorAll('.btn-save-verif-berkas').forEach(btn => {
                btn.addEventListener('click', async function () {
                    const pdId = this.getAttribute('data-pd-id');
                    const key = this.getAttribute('data-key');
                    const label = this.getAttribute('data-label');
                    const selectedRadio = content.querySelector(`input[name="status_${key}"]:checked`);

                    if (!selectedRadio) {
                        Swal.fire('Peringatan', 'Silakan pilih status validasi (Valid atau Tidak Valid).', 'warning');
                        return;
                    }

                    const status = selectedRadio.value;
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

                        if (postJson.status === 'success') {
                            berkasStatusChanged = true;
                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil!',
                                text: postJson.message,
                                timer: 1800,
                                showConfirmButton: false,
                            });

                            // Update status badge on card
                            const card = document.getElementById(`cardBerkas_${key}`);
                            if (card) {
                                const badgeContainer = card.querySelector('.status-badge-container');
                                if (badgeContainer) {
                                    badgeContainer.innerHTML = status === 'valid'
                                        ? `<span class="badge badge-success" style="font-size: 0.74rem; padding: 4px 10px;"><i class="fas fa-circle-check me-1"></i> Valid / Sesuai</span>`
                                        : `<span class="badge badge-danger" style="font-size: 0.74rem; padding: 4px 10px;"><i class="fas fa-circle-xmark me-1"></i> Tidak Valid / Tidak Sesuai</span>`;
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

    // 3b. Viewer Dokumen PDF Terpadu (In-App Modal tanpa Buka Tab Baru)
    window.openPdfViewer = function (url, title) {
        const modal = document.getElementById('modalPdfViewer');
        const frame = document.getElementById('pdfViewerFrame');
        const titleEl = document.getElementById('pdfViewerTitle');
        if (!modal || !frame) return;

        if (titleEl) titleEl.textContent = title || 'Pratinjau Dokumen PDF';
        frame.src = url;
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    };

    window.closePdfViewer = function () {
        const modal = document.getElementById('modalPdfViewer');
        const frame = document.getElementById('pdfViewerFrame');
        if (modal) modal.style.display = 'none';
        if (frame) frame.src = '';
        // Jika modal verifikasi masih terbuka, jangan reset body overflow
        const modalVb = document.getElementById('modalVerifikasiBerkasDigital');
        if (!modalVb || modalVb.style.display === 'none') {
            document.body.style.overflow = '';
        }
    };

    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-preview-pdf-inline');
        if (btn) {
            e.preventDefault();
            e.stopPropagation();
            const url = btn.getAttribute('data-url');
            const title = btn.getAttribute('data-title');
            if (url) window.openPdfViewer(url, title);
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

            let html = `
                <div style="display: flex; flex-direction: column; gap: 16px;">
                    <!-- 1. Header Ringkasan Siswa & Status Usulan -->
                    <div style="background: var(--bg-hover); border-radius: 12px; padding: 14px 18px; border: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div style="width: 44px; height: 44px; border-radius: 10px; background: rgba(99,102,241,0.12); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 1.25rem; flex-shrink: 0; overflow: hidden;">
                                ${d.foto_url ? `<img src="${d.foto_url}" style="width:100%; height:100%; object-fit:cover;">` : `<i class="fas fa-user-graduate"></i>`}
                            </div>
                            <div>
                                <div style="font-weight: 800; font-size: 0.98rem; color: var(--text-color);">${escapeHtml(d.nama_siswa)}</div>
                                <div style="font-size: 0.76rem; color: var(--text-muted); margin-top: 2px;">
                                    NISN: <strong>${escapeHtml(d.nisn)}</strong> &bull; NIPD: <strong>${escapeHtml(d.nipd)}</strong> &bull; Rombel: <strong>${escapeHtml(d.rombel)}</strong>
                                </div>
                            </div>
                        </div>
                        <div style="text-align: right;">
                            <div>${statusBadge}</div>
                            <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 4px;">Diajukan: ${escapeHtml(d.created_at || '-')}</div>
                        </div>
                    </div>

                    <!-- 2. KARTU PERBANDINGAN FIELD ASAL VS USULAN PENYESUAIAN -->
                    <div style="background: var(--card-bg); border-radius: 12px; padding: 16px 18px; border: 1.5px solid var(--border-color);">
                        <div style="font-size: 0.8rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 8px;">
                            <i class="fas fa-columns me-1 text-primary"></i> Perbandingan Kolom Data: <strong style="color: var(--text-color);">${escapeHtml(d.kolom_label)}</strong>
                        </div>

                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 14px; margin-bottom: 12px;">
                            <!-- Field Asal (Nilai Sebelumnya) -->
                            <div style="background: var(--bg-hover); border-radius: 10px; padding: 14px; border: 1px dashed var(--border-color);">
                                <div style="font-size: 0.72rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 6px; display: flex; align-items: center; gap: 6px;">
                                    <i class="fas fa-database text-warning"></i> Data Asal (Sebelumnya di Sistem)
                                </div>
                                <div style="font-size: 0.92rem; font-weight: 600; color: var(--text-color); word-break: break-word;">
                                    <span style="text-decoration: line-through; opacity: 0.85;">${escapeHtml(d.nilai_lama)}</span>
                                </div>
                            </div>

                            <!-- Field Usulan Baru (Penyesuaian Siswa) -->
                            <div style="background: rgba(16, 185, 129, 0.08); border-radius: 10px; padding: 14px; border: 1.5px solid rgba(16, 185, 129, 0.35);">
                                <div style="font-size: 0.72rem; font-weight: 700; color: #10b981; text-transform: uppercase; margin-bottom: 6px; display: flex; align-items: center; gap: 6px;">
                                    <i class="fas fa-pen-nib"></i> Usulan Penyesuaian Siswa (Nilai Baru)
                                </div>
                                <div style="font-size: 0.98rem; font-weight: 800; color: #10b981; word-break: break-word;">
                                    ${escapeHtml(d.nilai_baru)}
                                </div>
                            </div>
                        </div>

                        <div style="background: rgba(99,102,241,0.06); border-radius: 8px; padding: 10px 12px; border: 1px solid rgba(99,102,241,0.18);">
                            <div style="font-size: 0.75rem; font-weight: 700; color: var(--primary); margin-bottom: 3px;">
                                <i class="fas fa-comment-dots me-1"></i> Alasan Pengajuan oleh Siswa / Orang Tua:
                            </div>
                            <div style="font-size: 0.82rem; color: var(--text-color); font-style: italic;">
                                "${escapeHtml(d.alasan)}"
                            </div>
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

                            <!-- Bukti Tambahan Usulan -->
                            ${d.berkas_bukti_url ? `
                                <div style="background: var(--card-bg); padding: 10px 12px; border-radius: 8px; border: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; gap: 8px;">
                                    <div>
                                        <div style="font-size: 0.78rem; font-weight: 700; color: var(--text-color);">Bukti Khusus Usulan</div>
                                        <div style="font-size: 0.7rem; color: var(--text-muted);">Lampiran Tambahan</div>
                                    </div>
                                    <button type="button" class="btn btn-outline btn-preview-pdf-inline" data-url="${d.berkas_bukti_url}" data-title="Bukti Usulan - ${escapeHtml(d.nama_siswa)}" style="padding: 4px 8px; font-size: 0.72rem; border-radius: 6px;">
                                        <i class="fas fa-eye me-1"></i> Lihat
                                    </button>
                                </div>
                            ` : ''}
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
                                <i class="fas fa-circle-check"></i> Disetujui (Sesuai &amp; Siap Di-input Dapodik)
                            </label>
                            <label style="display: flex; align-items: center; gap: 6px; font-size: 0.88rem; font-weight: 700; color: #ef4444; cursor: pointer;">
                                <input type="radio" name="usulan_status_decision" value="ditolak" ${d.status === 'ditolak' ? 'checked' : ''} class="radio-usulan-decision">
                                <i class="fas fa-circle-xmark"></i> Ditolak (Tidak Sesuai / Berkas Tidak Sah)
                            </label>
                        </div>

                        <!-- Box Alasan Penolakan (Otomatis muncul jika Ditolak) -->
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
                                <textarea id="textareaCatatanUsulan" class="form-control" rows="2" placeholder="Tuliskan alasan penolakan secara jelas..." style="font-size: 0.82rem;">${escapeHtml(d.catatan_verifikasi || '')}</textarea>
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
                                ${d.status === 'disetujui' ? `
                                    <button type="button" class="btn btn-primary btn-sm btn-mark-dapodik" data-id="${d.id}" data-nama="${escapeHtml(d.nama_siswa)}" data-kolom="${escapeHtml(d.kolom_label)}" style="font-weight: 700; border-radius: 8px;">
                                        <i class="fas fa-cloud-arrow-up me-1"></i> Tandai Sudah Di-update ke Dapodik
                                    </button>
                                ` : ''}
                            </div>
                            <button type="button" class="btn btn-primary" id="btnSimpanKeputusanUsulan" data-id="${d.id}" style="padding: 8px 18px; font-size: 0.84rem; font-weight: 700; border-radius: 8px; display: inline-flex; align-items: center; gap: 6px;">
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
                        const postRes = await fetch(`/dashboard/kesiswaan/peserta-didik/usulan/${id}/verifikasi`, {
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

            Swal.fire({
                title: 'Tandai Diupdate ke Dapodik?',
                html: `
                    <div style="text-align: left; font-size: 0.88rem; line-height: 1.5;">
                        <p>Apakah perubahan kolom <strong>${escapeHtml(kolom)}</strong> untuk siswa <strong>${escapeHtml(nama)}</strong> sudah selesai Anda input / sinkronkan ke aplikasi Dapodik pusat?</p>
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

    // Datatable Realtime Live Search, Entri perPage, dan Filter Standar SAE
    const searchInput = document.getElementById("liveSearch");
    const clearBtn = document.getElementById("clearSearch");
    const perPageSelect = document.getElementById("perPageSelect");
    const filterRombel = document.getElementById("filterRombel");
    const filterGender = document.getElementById("filterGender");
    const filterTahunLulus = document.getElementById("filterTahunLulus");

    function applyFilter() {
        const url = new URL(window.location.href);
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

    if (searchInput) {
        let timer = null;
        searchInput.addEventListener("input", function () {
            if (clearBtn) clearBtn.classList.toggle("visible", this.value.trim().length > 0);
            clearTimeout(timer);
            timer = setTimeout(applyFilter, 300);
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

    if (filterRombel) filterRombel.addEventListener("change", applyFilter);
    if (filterGender) filterGender.addEventListener("change", applyFilter);
    if (filterTahunLulus) {
        filterTahunLulus.addEventListener("change", applyFilter);
        filterTahunLulus.addEventListener("keydown", function (e) {
            if (e.key === "Enter") {
                e.preventDefault();
                applyFilter();
            }
        });
    }
    if (perPageSelect) perPageSelect.addEventListener("change", applyFilter);
});
