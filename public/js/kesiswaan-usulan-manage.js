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
                        // Kolom verifikasi: ceklis ✓ setujui / ✕ tolak per butir data.
                        let statusHtml = '';
                        if (item.status === 'menunggu') {
                            statusHtml = `
                                <div class="usulan-decision-group" data-usulan-id="${item.id}" data-decision=""
                                    style="display:inline-flex; align-items:center; gap:8px;">
                                    <button type="button" class="usulan-decision" data-decision="disetujui" title="Setujui"
                                        style="width:28px; height:28px; border-radius:50%; border:1.5px solid rgba(16,185,129,0.4); background:transparent; color:#10b981; cursor:pointer; display:inline-flex; align-items:center; justify-content:center;">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    <button type="button" class="usulan-decision" data-decision="ditolak" title="Tolak / Tidak Sesuai"
                                        style="width:28px; height:28px; border-radius:50%; border:1.5px solid rgba(239,68,68,0.4); background:transparent; color:#ef4444; cursor:pointer; display:inline-flex; align-items:center; justify-content:center;">
                                        <i class="fas fa-xmark"></i>
                                    </button>
                                </div>`;
                        } else {
                            const iconMap = {
                                disetujui: ['check', '#10b981', 'rgba(16,185,129,0.15)'],
                                ditolak: ['xmark', '#ef4444', 'rgba(239,68,68,0.15)'],
                                sudah_ke_dapodik: ['cloud-arrow-up', '#3b82f6', 'rgba(59,130,246,0.15)'],
                            };
                            const m = iconMap[item.status] || ['clock', '#f59e0b', 'rgba(245,158,11,0.15)'];
                            statusHtml = `<span title="${item.status}" style="display:inline-flex; align-items:center; justify-content:center; width:28px; height:28px; border-radius:50%; background:${m[2]}; color:${m[1]};"><i class="fas fa-${m[0]}"></i></span>`;
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

            const btnSimpanVerifikasi = document.getElementById('btnSimpanVerifikasiUsulan');
            const btnCeklisSemua = document.getElementById('btnCeklisSemuaUsulan');
            const btnXSemua = document.getElementById('btnXSemuaUsulan');
            const btnTandaiDapodik = document.getElementById('btnTandaiDapodikUsulan');

            const hasMenunggu = items.some(i => i.status === 'menunggu');
            const hasDisetujui = items.some(i => i.status === 'disetujui');

            const toggleBtn = (el, on) => { if (el) el.style.display = on ? 'inline-flex' : 'none'; };
            toggleBtn(btnSimpanVerifikasi, hasMenunggu);
            toggleBtn(btnCeklisSemua, hasMenunggu);
            toggleBtn(btnXSemua, hasMenunggu);
            toggleBtn(btnTandaiDapodik, hasDisetujui);

            // Pilih semua: terapkan keputusan ke seluruh butir yang masih menunggu.
            const bulkSelect = (decision) => {
                document.querySelectorAll('#modalUsulanTableBody .usulan-decision-group').forEach((group) => {
                    applyDecision(group, decision);
                });
            };
            if (btnCeklisSemua) btnCeklisSemua.onclick = () => bulkSelect('disetujui');
            if (btnXSemua) btnXSemua.onclick = () => bulkSelect('ditolak');

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

    // Pilih keputusan per butir usulan (ceklis ✓ setujui / ✕ tolak)
    function applyDecision(group, decision) {
        group.dataset.decision = decision || '';
        group.querySelectorAll('.usulan-decision').forEach((btn) => {
            const on = btn.getAttribute('data-decision') === decision;
            btn.style.background = on ? (decision === 'disetujui' ? 'rgba(16,185,129,0.2)' : 'rgba(239,68,68,0.2)') : 'transparent';
            btn.style.borderStyle = on ? 'solid' : '1.5px solid';
            btn.style.fontWeight = on ? '900' : '400';
        });
    }

    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.usulan-decision');
        if (!btn) return;
        const group = btn.closest('.usulan-decision-group');
        if (!group) return;
        const decision = btn.getAttribute('data-decision');
        applyDecision(group, group.dataset.decision === decision ? '' : decision);
    });

    // Action: Simpan Verifikasi (per butir / sesuai pilihan ceklis)
    const btnSimpan = document.getElementById('btnSimpanVerifikasiUsulan');
    if (btnSimpan) {
        btnSimpan.addEventListener('click', async function () {
            const modal = document.getElementById('modalPengelolaanUsulan');
            const pdId = modal.getAttribute('data-pd-id');
            const catatan = (document.getElementById('catatanVerifikasiUsulanInput')?.value || '').trim();

            const groups = Array.from(document.querySelectorAll('#modalUsulanTableBody .usulan-decision-group'));
            if (groups.length === 0) {
                Swal.fire('Tidak Ada Butir', 'Tidak ada usulan berstatus menunggu yang dapat diverifikasi.', 'info');
                return;
            }

            const items = [];
            const belumDipilih = [];
            groups.forEach((g) => {
                const id = parseInt(g.getAttribute('data-usulan-id'), 10);
                const decision = g.dataset.decision;
                if (decision === 'disetujui' || decision === 'ditolak') {
                    items.push({ id, status: decision });
                } else {
                    belumDipilih.push(id);
                }
            });

            if (belumDipilih.length > 0) {
                Swal.fire('Belum Lengkap', 'Tentukan keputusan (✓ setujui / ✕ tolak) untuk setiap butir usulan terlebih dahulu.', 'warning');
                return;
            }

            const adaTolak = items.some(i => i.status === 'ditolak');
            if (adaTolak && !catatan) {
                Swal.fire('Catatan Wajib', 'Tuliskan atau pilih rekomendasi alasan penolakan agar siswa memahami perbaikannya.', 'warning');
                document.getElementById('catatanVerifikasiUsulanInput')?.focus();
                return;
            }

            const confirm = await Swal.fire({
                title: 'Simpan Verifikasi?',
                text: `${items.filter(i => i.status === 'disetujui').length} disetujui, ${items.filter(i => i.status === 'ditolak').length} ditolak. Data yang disetujui langsung diperbarui ke database.`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya, Simpan',
                cancelButtonText: 'Batal'
            });
            if (!confirm.isConfirmed) return;

            btnSimpan.disabled = true;
            btnSimpan.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

            try {
                const res = await fetch(`/dashboard/kesiswaan/peserta-didik/usulan/${encodeURIComponent(pdId)}/verifikasi`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                    },
                    body: JSON.stringify({ items, catatan_verifikasi: catatan })
                });

                const json = await res.json();
                if (json.status !== 'success') throw new Error(json.message || 'Gagal menyimpan verifikasi.');

                Swal.fire({ title: 'Tersimpan', text: json.message, icon: 'success', timer: 1600, showConfirmButton: false });

                window.closeModalPengelolaanUsulan();
                if (typeof window.refreshLiveTable === 'function') {
                    window.refreshLiveTable();
                } else {
                    location.reload();
                }
            } catch (err) {
                Swal.fire('Gagal', err.message, 'error');
            } finally {
                btnSimpan.disabled = false;
                btnSimpan.innerHTML = '<i class="fas fa-save"></i>';
            }
        });
    }

    // Action: Tandai Sudah Selesai ke Dapodik (modal & tombol pada tabel usulan)
    async function markDapodik(pdId, btn) {
        if (!pdId) return;

        // SAE.confirm mengembalikan boolean.
        const ok = await window.SAE.confirm(
            "Status usulan ini akan ditandai telah selesai di-input / disinkronkan ke aplikasi Dapodik.",
            "Tandai Selesai di Dapodik?",
            'info',
            "Ya, Tandai Selesai",
            "Batal"
        );
        if (!ok) return;

        const original = btn ? btn.innerHTML : '';
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        }

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
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = original;
            }
        }
    }

    const btnDapodik = document.getElementById('btnTandaiDapodikUsulan');
    if (btnDapodik) {
        btnDapodik.addEventListener('click', function () {
            const modal = document.getElementById('modalPengelolaanUsulan');
            markDapodik(modal ? modal.getAttribute('data-pd-id') : null, btnDapodik);
        });
    }

    document.addEventListener('click', function (e) {
        const b = e.target.closest('.btn-mark-dapodik');
        if (b) {
            e.preventDefault();
            markDapodik(b.getAttribute('data-id'), b);
        }
    });

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
