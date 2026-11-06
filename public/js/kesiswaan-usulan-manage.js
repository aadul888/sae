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
            const res = await fetch(`/dashboard/kesiswaan/peserta-didik/usulan-detail/${encodeURIComponent(id)}`, {
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

            // Tampilkan Catatan Penolakan / Alasan Siswa (Tunggal di atas strip berkas)
            const alasanBox = document.getElementById('modalUsulanAlasanBox');
            if (alasanBox) {
                const defaultAlasan = 'penyesuaian formulir identitas mandiri.';
                const distinctAlasan = [...new Set(items.map(i => (i.alasan || '').trim()).filter(a => a && a.toLowerCase() !== defaultAlasan))];
                const distinctCatatan = [...new Set(items.map(i => (i.catatan_verifikasi || '').trim()).filter(c => c))];

                let boxHtml = '';
                if (distinctCatatan.length > 0) {
                    boxHtml += `
                        <div style="display:flex; align-items:flex-start; gap:8px; background:rgba(239,68,68,0.08); border:1px solid rgba(239,68,68,0.25); color:var(--text-color); padding:8px 12px; border-radius:var(--radius-md, 8px); font-size:var(--text-xs, 0.75rem); line-height:1.4; margin-bottom:6px;">
                            <i class="fas fa-triangle-exclamation" style="color:#ef4444; margin-top:2px; flex-shrink:0;"></i>
                            <div><strong style="color:#ef4444;">Catatan Penolakan:</strong> ${escapeHtml(distinctCatatan.join(' | '))}</div>
                        </div>
                    `;
                }

                const filteredAlasan = distinctAlasan.filter(a => !distinctCatatan.includes(a));
                if (filteredAlasan.length > 0) {
                    boxHtml += `
                        <div style="display:flex; align-items:flex-start; gap:8px; background:rgba(99,102,241,0.08); border:1px solid rgba(99,102,241,0.25); color:var(--text-color); padding:8px 12px; border-radius:var(--radius-md, 8px); font-size:var(--text-xs, 0.75rem); line-height:1.4;">
                            <i class="fas fa-circle-info" style="color:var(--primary); margin-top:2px; flex-shrink:0;"></i>
                            <div><strong style="color:var(--primary);">Alasan Pengajuan:</strong> ${escapeHtml(filteredAlasan.join(' | '))}</div>
                        </div>
                    `;
                }

                if (boxHtml) {
                    alasanBox.innerHTML = boxHtml;
                    alasanBox.style.display = 'block';
                } else {
                    alasanBox.innerHTML = '';
                    alasanBox.style.display = 'none';
                }
            }

            const formatUsulanVal = (val) => {
                if (!val || val === '(Belum Terisi / Kosong)' || val === '(Kosong)') {
                    return { html: '<span style="color:var(--text-muted); opacity:0.5;">-</span>', raw: '' };
                }
                const str = String(val).trim();
                const dateMatch = str.match(/^(\d{4}-\d{2}-\d{2})[T\s](\d{2}:\d{2})/i);
                const clean = dateMatch ? dateMatch[1] : str;
                return { html: escapeHtml(clean), raw: clean };
            };

            const tbody = document.getElementById('modalUsulanTableBody');
            if (tbody) {
                tbody.innerHTML = '';

                if (items.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="4" style="text-align:center; padding:20px; color:var(--text-muted);">Tidak ada daftar butir perubahan data.</td></tr>';
                } else {
                    items.forEach((item) => {
                        let statusHtml = '';
                        if (item.status === 'disetujui') {
                            statusHtml = '<span style="display:inline-flex; align-items:center; gap:4px; padding:2px 8px; border-radius:999px; font-size:0.7rem; font-weight:700; background:rgba(16,185,129,0.15); color:#10b981;"><i class="fas fa-check"></i> Disetujui</span>';
                        } else if (item.status === 'ditolak') {
                            statusHtml = '<span style="display:inline-flex; align-items:center; gap:4px; padding:2px 8px; border-radius:999px; font-size:0.7rem; font-weight:700; background:rgba(239,68,68,0.15); color:#ef4444;"><i class="fas fa-times"></i> Ditolak</span>';
                        } else if (item.status === 'sudah_ke_dapodik') {
                            statusHtml = '<span style="display:inline-flex; align-items:center; gap:4px; padding:2px 8px; border-radius:999px; font-size:0.7rem; font-weight:700; background:rgba(59,130,246,0.15); color:#3b82f6;"><i class="fas fa-cloud-arrow-up"></i> Dapodik</span>';
                        } else {
                            statusHtml = '<span style="display:inline-flex; align-items:center; gap:4px; padding:2px 8px; border-radius:999px; font-size:0.7rem; font-weight:700; background:rgba(245,158,11,0.15); color:#f59e0b;"><i class="fas fa-clock"></i> Menunggu</span>';
                        }

                        let buktiHtml = '';
                        if (item.berkas_bukti_url) {
                            buktiHtml = `<button type="button" class="btn-icon btn-preview-lampiran-usulan" data-url="${item.berkas_bukti_url}" title="Lihat Lampiran Bukti" style="width:22px; height:22px; border-radius:4px; display:inline-flex; align-items:center; justify-content:center; background:rgba(99,102,241,0.12); color:var(--primary); border:1px solid rgba(99,102,241,0.25); margin-left:6px; cursor:pointer;"><i class="fas fa-paperclip" style="font-size:0.7rem;"></i></button>`;
                        }

                        const valLama = formatUsulanVal(item.nilai_lama);
                        const valBaru = formatUsulanVal(item.nilai_baru);

                        let valBaruHtml = '';
                        if (valBaru.raw) {
                            valBaruHtml = `
                                <span class="copyable" data-copy="${escapeHtml(valBaru.raw)}" data-label="${escapeHtml(item.kolom_label)}" title="Klik untuk salin" style="font-weight:700; color:var(--success, #10b981); font-size:0.86rem; word-break:break-word; cursor:pointer;">${valBaru.html}</span>
                            `;
                        } else {
                            valBaruHtml = valBaru.html;
                        }

                        const tr = document.createElement('tr');
                        tr.style.borderBottom = '1px solid var(--border-color)';
                        tr.innerHTML = `
                            <td style="padding:10px 14px; vertical-align:middle;">
                                <div style="display:flex; align-items:center;">
                                    <span style="font-weight:700; color:var(--text-color); font-size:0.84rem;">${escapeHtml(item.kolom_label)}</span>
                                    ${buktiHtml}
                                </div>
                            </td>
                            <td style="padding:10px 14px; vertical-align:middle; background:rgba(239,68,68,0.02);">
                                <div style="color:var(--text-muted); font-size:0.84rem; word-break:break-word;">
                                    ${valLama.html}
                                </div>
                            </td>
                            <td style="padding:10px 14px; vertical-align:middle; background:rgba(16,185,129,0.03);">
                                <div>
                                    ${valBaruHtml}
                                </div>
                            </td>
                            <td style="padding:10px 14px; vertical-align:middle; text-align:center;">
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

            const confirm = await window.SAE.confirm("Data identitas peserta didik akan langsung diperbarui ke database sesuai pengajuan yang dipilih.", "Setujui Seluruh Usulan?", 'info', "Ya, Setujui", "Batal");

            if (!confirm.isConfirmed) return;

            btnSetujui.disabled = true;
            btnSetujui.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Menyimpan...';

            try {
                const res = await fetch(`/dashboard/kesiswaan/peserta-didik/usulan/${encodeURIComponent(pdId)}/verifikasi`, {
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
                const res = await fetch(`/dashboard/kesiswaan/peserta-didik/usulan/${encodeURIComponent(pdId)}/verifikasi`, {
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

            const confirm = await window.SAE.confirm("Status usulan ini akan ditandai telah selesai di-input / disinkronkan ke aplikasi Dapodik.", "Tandai Selesai di Dapodik?", 'info', "Ya, Tandai Selesai", "Batal");

            if (!confirm.isConfirmed) return;

            btnDapodik.disabled = true;
            btnDapodik.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Memproses...';

            try {
                const res = await fetch(`/dashboard/kesiswaan/peserta-didik/usulan/${encodeURIComponent(pdId)}/dapodik`, {
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
