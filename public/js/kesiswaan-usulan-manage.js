// =========================================================================
// KESISWAAN: PENGELOLAAN USULAN PERUBAHAN DATA SISWA & BIODATA MODAL
// =========================================================================
document.addEventListener('DOMContentLoaded', function () {
    // Modal Pengelolaan Usulan Perubahan Data Siswa (Field Asal vs Penyesuaian)
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

        try {
            const res = await fetch(`/dashboard/kesiswaan/usulan/${id}/detail`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            const json = await res.json();

            if (json.status !== 'success' || !json.data) {
                throw new Error(json.message || 'Gagal memuat rincian usulan.');
            }

            const data = json.data;
            const items = data.items || [];
            const pdId = data.peserta_didik_id;

            modal.setAttribute('data-pd-id', pdId);
            modal.setAttribute('data-usulan-id', data.id || id);

            if (namaEl) namaEl.textContent = data.nama_siswa || '-';
            if (subtitleEl) {
                subtitleEl.innerHTML = `NISN: <strong>${data.nisn || '-'}</strong> &bull; Rombel: <strong>${data.rombel || '-'}</strong> &bull; Total Pengajuan: <strong>${items.length} Data</strong>`;
            }

            const fotoImg = document.getElementById('modalUsulanFoto');
            const fotoIcon = document.getElementById('modalUsulanFotoDefault');
            if (fotoImg && fotoIcon) {
                if (data.foto_url) {
                    fotoImg.src = data.foto_url;
                    fotoImg.style.display = 'block';
                    fotoIcon.style.display = 'none';
                } else {
                    fotoImg.style.display = 'none';
                    fotoIcon.style.display = 'flex';
                }
            }

            const setDoc = (prefix, docData, defaultName) => {
                const statusBadge = document.getElementById(`${prefix}StatusBadge`);
                const previewBtn = document.getElementById(`${prefix}PreviewBtn`);
                const nameEl = document.getElementById(`${prefix}FileName`);

                if (!statusBadge || !previewBtn) return;

                if (docData && docData.exists) {
                    nameEl.textContent = docData.file_name || defaultName;
                    previewBtn.setAttribute('data-url', docData.file_url || '');
                    previewBtn.disabled = false;
                    previewBtn.style.opacity = '1';

                    if (docData.status === 'valid') {
                        statusBadge.innerHTML = '<span style="display:inline-flex; align-items:center; gap:4px; padding:2px 8px; border-radius:999px; font-size:0.7rem; font-weight:700; background:rgba(16,185,129,0.15); color:#10b981;"><i class="fas fa-check-circle"></i> Valid</span>';
                    } else if (docData.status === 'tidak_valid') {
                        statusBadge.innerHTML = '<span style="display:inline-flex; align-items:center; gap:4px; padding:2px 8px; border-radius:999px; font-size:0.7rem; font-weight:700; background:rgba(239,68,68,0.15); color:#ef4444;"><i class="fas fa-times-circle"></i> Tidak Valid</span>';
                    } else {
                        statusBadge.innerHTML = '<span style="display:inline-flex; align-items:center; gap:4px; padding:2px 8px; border-radius:999px; font-size:0.7rem; font-weight:700; background:rgba(245,158,11,0.15); color:#f59e0b;"><i class="fas fa-clock"></i> Belum Diverif</span>';
                    }
                } else {
                    nameEl.textContent = 'Belum diunggah';
                    previewBtn.setAttribute('data-url', '');
                    previewBtn.disabled = true;
                    previewBtn.style.opacity = '0.5';
                    statusBadge.innerHTML = '<span style="display:inline-flex; align-items:center; gap:4px; padding:2px 8px; border-radius:999px; font-size:0.7rem; font-weight:700; background:rgba(148,163,184,0.15); color:var(--text-muted);"><i class="fas fa-minus-circle"></i> Tidak Ada</span>';
                }
            };

            setDoc('docKk', data.kk, 'Kartu_Keluarga.pdf');
            setDoc('docIjazah', data.ijazah, 'Ijazah_SMP.pdf');
            setDoc('docAkta', data.akta, 'Akta_Kelahiran.pdf');

            const tbody = document.getElementById('modalUsulanTableBody');
            if (tbody) {
                tbody.innerHTML = '';

                if (items.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="4" style="text-align:center; padding:20px; color:var(--text-muted);">Tidak ada daftar butir perubahan data.</td></tr>';
                } else {
                    items.forEach((item, idx) => {
                        let statusHtml = '';
                        if (item.status === 'disetujui') {
                            statusHtml = '<span style="display:inline-flex; align-items:center; gap:4px; padding:2px 8px; border-radius:999px; font-size:0.7rem; font-weight:700; background:rgba(16,185,129,0.15); color:#10b981;"><i class="fas fa-check"></i> Disetujui</span>';
                        } else if (item.status === 'ditolak') {
                            statusHtml = '<span style="display:inline-flex; align-items:center; gap:4px; padding:2px 8px; border-radius:999px; font-size:0.7rem; font-weight:700; background:rgba(239,68,68,0.15); color:#ef4444;"><i class="fas fa-times"></i> Ditolak</span>';
                        } else if (item.status === 'sudah_ke_dapodik') {
                            statusHtml = '<span style="display:inline-flex; align-items:center; gap:4px; padding:2px 8px; border-radius:999px; font-size:0.7rem; font-weight:700; background:rgba(59,130,246,0.15); color:#3b82f6;"><i class="fas fa-cloud-arrow-up"></i> Ke Dapodik</span>';
                        } else {
                            statusHtml = '<span style="display:inline-flex; align-items:center; gap:4px; padding:2px 8px; border-radius:999px; font-size:0.7rem; font-weight:700; background:rgba(245,158,11,0.15); color:#f59e0b;"><i class="fas fa-clock"></i> Menunggu</span>';
                        }

                        let buktiHtml = '';
                        if (item.berkas_bukti_url) {
                            buktiHtml = `<button type="button" class="btn btn-outline btn-preview-lampiran-usulan" data-url="${item.berkas_bukti_url}" style="padding:2px 8px; font-size:0.72rem; border-radius:6px; display:inline-flex; align-items:center; gap:4px; margin-top:4px;"><i class="fas fa-paperclip"></i> Bukti Lampiran</button>`;
                        }

                        const tr = document.createElement('tr');
                        tr.style.borderBottom = '1px solid var(--border-color)';
                        tr.innerHTML = `
                            <td style="padding:10px 12px; vertical-align:top;">
                                <div style="font-weight:700; color:var(--text-color); font-size:0.84rem;">${escapeHtml(item.kolom_label)}</div>
                                <div style="font-size:0.7rem; font-family:monospace; color:var(--text-muted);">${escapeHtml(item.kolom_perubahan)}</div>
                                ${buktiHtml}
                            </td>
                            <td style="padding:10px 12px; vertical-align:top; background:rgba(239,68,68,0.02);">
                                <div style="color:var(--text-muted); font-size:0.82rem; word-break:break-word;">
                                    ${escapeHtml(item.nilai_lama)}
                                </div>
                            </td>
                            <td style="padding:10px 12px; vertical-align:top; background:rgba(16,185,129,0.03);">
                                <div style="font-weight:700; color:var(--text-color); font-size:0.84rem; word-break:break-word;">
                                    ${escapeHtml(item.nilai_baru)}
                                </div>
                                <div style="font-size:0.72rem; color:var(--text-muted); margin-top:4px; line-height:1.3;">
                                    <em>Alasan: ${escapeHtml(item.alasan)}</em>
                                </div>
                            </td>
                            <td style="padding:10px 12px; vertical-align:top; text-align:center;">
                                ${statusHtml}
                            </td>
                        `;
                        tbody.appendChild(tr);
                    });
                }
            }

            const chipsWrap = document.getElementById('rekomendasiAlasanChips');
            const catatanInp = document.getElementById('catatanVerifikasiUsulanInput');
            if (chipsWrap && json.rekomendasi_penolakan) {
                chipsWrap.innerHTML = '';
                json.rekomendasi_penolakan.forEach((alasan) => {
                    const chip = document.createElement('button');
                    chip.type = 'button';
                    chip.className = 'chip-alasan-usulan';
                    chip.style.cssText = 'padding:4px 10px; font-size:0.74rem; border-radius:999px; border:1px solid var(--border-color); background:var(--bg-card); color:var(--text-muted); cursor:pointer; text-align:left; transition:all 0.15s; line-height:1.3;';
                    chip.textContent = alasan;
                    chip.addEventListener('click', () => {
                        if (catatanInp) {
                            catatanInp.value = alasan;
                            catatanInp.focus();
                        }
                    });
                    chipsWrap.appendChild(chip);
                });
            }

            const btnSetujuiSemua = document.getElementById('btnSetujuiSemuaUsulan');
            const btnTolakSemua = document.getElementById('btnTolakSemuaUsulan');
            const btnTandaiDapodik = document.getElementById('btnTandaiDapodikUsulan');

            const hasMenunggu = items.some(i => i.status === 'menunggu');
            const hasDisetujui = items.some(i => i.status === 'disetujui');

            if (btnSetujuiSemua) btnSetujuiSemua.style.display = hasMenunggu ? 'inline-flex' : 'none';
            if (btnTolakSemua) btnTolakSemua.style.display = hasMenunggu ? 'inline-flex' : 'none';
            if (btnTandaiDapodik) btnTandaiDapodik.style.display = hasDisetujui ? 'inline-flex' : 'none';

            loading.style.display = 'none';
            content.style.display = 'block';

        } catch (err) {
            loading.innerHTML = `<div style="color:#ef4444;"><i class="fas fa-exclamation-triangle me-2"></i> ${escapeHtml(err.message)}</div>`;
        }
    };

    window.closeModalPengelolaanUsulan = function () {
        const modal = document.getElementById('modalPengelolaanUsulan');
        if (modal) modal.style.display = 'none';
        document.body.style.overflow = '';
    };

    document.querySelectorAll('.close-modal-pengelolaan-usulan').forEach(btn => {
        btn.addEventListener('click', window.closeModalPengelolaanUsulan);
    });

    // Preview Lampiran Dokumen dari Usulan
    document.addEventListener('click', function (e) {
        const previewBtn = e.target.closest('.btn-preview-lampiran-usulan, #docKkPreviewBtn, #docIjazahPreviewBtn, #docAktaPreviewBtn');
        if (previewBtn) {
            e.preventDefault();
            const url = previewBtn.getAttribute('data-url');
            if (url && typeof window.openPdfViewer === 'function') {
                window.openPdfViewer(url, 'Pratinjau Dokumen Pendukung', null, null, null, null, null);
            }
        }
    });

    // Action: Setujui Semua
    const btnSetujui = document.getElementById('btnSetujuiSemuaUsulan');
    if (btnSetujui) {
        btnSetujui.addEventListener('click', async function () {
            const modal = document.getElementById('modalPengelolaanUsulan');
            const pdId = modal.getAttribute('data-pd-id');
            const catatan = (document.getElementById('catatanVerifikasiUsulanInput')?.value || '').trim();

            const confirm = await Swal.fire({
                title: 'Setujui Seluruh Usulan?',
                text: 'Data identitas peserta didik akan langsung diperbarui ke database sesuai pengajuan yang dipilih.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya, Setujui',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#10b981'
            });

            if (!confirm.isConfirmed) return;

            btnSetujui.disabled = true;
            btnSetujui.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Menyimpan...';

            try {
                const res = await fetch(`/dashboard/kesiswaan/usulan/${pdId}/verifikasi`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                    },
                    body: JSON.stringify({
                        status: 'disetujui',
                        catatan_verifikasi: catatan
                    })
                });

                const json = await res.json();
                if (json.status !== 'success') throw new Error(json.message || 'Gagal menyimpan verifikasi.');

                Swal.fire({
                    title: 'Berhasil!',
                    text: json.message,
                    icon: 'success',
                    timer: 1600,
                    showConfirmButton: false
                });

                window.closeModalPengelolaanUsulan();
                if (typeof window.refreshLiveTable === 'function') {
                    window.refreshLiveTable();
                } else {
                    location.reload();
                }
            } catch (err) {
                Swal.fire('Gagal', err.message, 'error');
            } finally {
                btnSetujui.disabled = false;
                btnSetujui.innerHTML = '<i class="fas fa-check-double me-1"></i> Setujui Seluruh Usulan';
            }
        });
    }

    // Action: Tolak Seluruh Usulan
    const btnTolak = document.getElementById('btnTolakSemuaUsulan');
    if (btnTolak) {
        btnTolak.addEventListener('click', async function () {
            const modal = document.getElementById('modalPengelolaanUsulan');
            const pdId = modal.getAttribute('data-pd-id');
            const catatan = (document.getElementById('catatanVerifikasiUsulanInput')?.value || '').trim();

            if (!catatan) {
                Swal.fire('Catatan Wajib', 'Tuliskan atau pilih rekomendasi alasan penolakan terlebih dahulu agar siswa memahami perbaikannya.', 'warning');
                document.getElementById('catatanVerifikasiUsulanInput')?.focus();
                return;
            }

            const confirm = await Swal.fire({
                title: 'Tolak Seluruh Usulan?',
                text: 'Pengajuan perubahan data ini akan ditolak dengan alasan: "' + catatan + '"',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, Tolak',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#ef4444'
            });

            if (!confirm.isConfirmed) return;

            btnTolak.disabled = true;
            btnTolak.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Menyimpan...';

            try {
                const res = await fetch(`/dashboard/kesiswaan/usulan/${pdId}/verifikasi`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                    },
                    body: JSON.stringify({
                        status: 'ditolak',
                        catatan_verifikasi: catatan
                    })
                });

                const json = await res.json();
                if (json.status !== 'success') throw new Error(json.message || 'Gagal menyimpan penolakan.');

                Swal.fire({
                    title: 'Ditolak',
                    text: json.message,
                    icon: 'info',
                    timer: 1600,
                    showConfirmButton: false
                });

                window.closeModalPengelolaanUsulan();
                if (typeof window.refreshLiveTable === 'function') {
                    window.refreshLiveTable();
                } else {
                    location.reload();
                }
            } catch (err) {
                Swal.fire('Gagal', err.message, 'error');
            } finally {
                btnTolak.disabled = false;
                btnTolak.innerHTML = '<i class="fas fa-times-circle me-1"></i> Tolak Pengajuan';
            }
        });
    }

    // Action: Tandai Sudah Selesai ke Dapodik
    const btnDapodik = document.getElementById('btnTandaiDapodikUsulan');
    if (btnDapodik) {
        btnDapodik.addEventListener('click', async function () {
            const modal = document.getElementById('modalPengelolaanUsulan');
            const pdId = modal.getAttribute('data-pd-id');

            const confirm = await Swal.fire({
                title: 'Tandai Selesai di Dapodik?',
                text: 'Status usulan ini akan ditandai telah selesai di-input / disinkronkan ke aplikasi Dapodik.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya, Tandai Selesai',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#3b82f6'
            });

            if (!confirm.isConfirmed) return;

            btnDapodik.disabled = true;
            btnDapodik.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Memproses...';

            try {
                const res = await fetch(`/dashboard/kesiswaan/usulan/${pdId}/mark-dapodik`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                    }
                });

                const json = await res.json();
                if (json.status !== 'success') throw new Error(json.message || 'Gagal menandai status.');

                Swal.fire({
                    title: 'Sinkronisasi Selesai',
                    text: json.message,
                    icon: 'success',
                    timer: 1600,
                    showConfirmButton: false
                });

                window.closeModalPengelolaanUsulan();
                if (typeof window.refreshLiveTable === 'function') {
                    window.refreshLiveTable();
                } else {
                    location.reload();
                }
            } catch (err) {
                Swal.fire('Gagal', err.message, 'error');
            } finally {
                btnDapodik.disabled = false;
                btnDapodik.innerHTML = '<i class="fas fa-cloud-arrow-up me-1"></i> Tandai Sudah Selesai ke Dapodik';
            }
        });
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
});
