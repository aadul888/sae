/**
 * Modul JavaScript: e-Izin Siswa Terpadu (SAE Standardized)
 * Alur 4 Pihak: Siswa -> Wali Kelas -> Guru Piket -> Security Gerbang
 */
document.addEventListener('DOMContentLoaded', () => {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    // Modal elements
    const modalAjukan = document.getElementById('modalAjukanIzin');
    const modalApproval = document.getElementById('modalApprovalAction');
    const modalDetail = document.getElementById('modalDetailIzin');
    const modalSecurity = document.getElementById('modalSecurityGate');
    const modalSlipDigital = document.getElementById('modalSlipDigital');

    const btnOpenAjukan = document.getElementById('btnOpenAjukanModal');
    const btnOpenScan = document.getElementById('btnOpenScanModal');
    const inputJenisIzin = document.getElementById('inputJenisIzin');
    const inputJamKeluar = document.getElementById('inputJamKeluar');
    const inputJamRencanaKembali = document.getElementById('inputJamRencanaKembali');
    const wrapRencanaKembali = document.getElementById('wrapRencanaKembali');
    const selectSiswaModal = document.getElementById('selectSiswaModal');

    // Helper: Hitung default rencana kembali +10 menit
    const updateDefaultRencanaKembali = () => {
        if (!inputJamKeluar || !inputJamRencanaKembali) return;
        if (inputJenisIzin && inputJenisIzin.value === 'pulang_cepat') return;
        const val = inputJamKeluar.value;
        if (!val) return;
        const parts = val.split(':');
        if (parts.length >= 2) {
            const date = new Date();
            date.setHours(parseInt(parts[0], 10), parseInt(parts[1], 10) + 10, 0, 0);
            const hh = String(date.getHours()).padStart(2, '0');
            const mm = String(date.getMinutes()).padStart(2, '0');
            inputJamRencanaKembali.value = `${hh}:${mm}`;
        }
    };

    if (inputJamKeluar) {
        inputJamKeluar.addEventListener('change', updateDefaultRencanaKembali);
        inputJamKeluar.addEventListener('input', updateDefaultRencanaKembali);
    }

    // Helper: Tampilkan Modal
    const openModal = (modal) => {
        if (!modal) return;
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    };

    // Helper: Tutup Modal
    const closeModal = (modal) => {
        if (!modal) return;
        modal.style.display = 'none';
        document.body.style.overflow = '';
    };

    // Event listener tombol close modal
    document.querySelectorAll('.btn-close-modal').forEach((btn) => {
        btn.addEventListener('click', () => {
            const targetId = btn.getAttribute('data-target');
            if (targetId) {
                closeModal(document.getElementById(targetId));
            }
        });
    });

    // Tutup saat klik backdrop
    [modalAjukan, modalApproval, modalDetail, modalSecurity, modalSlipDigital].forEach((m) => {
        if (!m) return;
        m.addEventListener('click', (e) => {
            if (e.target === m) closeModal(m);
        });
    });

    // Helper Toast / Swal
    const showNotification = (message, type = 'success') => {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: type === 'danger' || type === 'error' ? 'error' : (type === 'warning' ? 'warning' : 'success'),
                title: message,
                showConfirmButton: false,
                timer: 3000
            });
        } else {
            alert(message);
        }
    };

    // Toggle input rencana kembali jika pulang cepat
    if (inputJenisIzin && wrapRencanaKembali) {
        inputJenisIzin.addEventListener('change', () => {
            if (inputJenisIzin.value === 'pulang_cepat') {
                wrapRencanaKembali.style.display = 'none';
            } else {
                wrapRencanaKembali.style.display = 'block';
                updateDefaultRencanaKembali();
            }
        });
    }

    // Buka Modal Ajukan e-Izin
    if (btnOpenAjukan && modalAjukan) {
        btnOpenAjukan.addEventListener('click', () => {
            const form = document.getElementById('formAjukanEIzin');
            if (form) form.reset();
            if (wrapRencanaKembali) wrapRencanaKembali.style.display = 'block';
            updateDefaultRencanaKembali();
            openModal(modalAjukan);

            // Load daftar siswa jika belum ada dan elemen dropdown tersedia
            if (selectSiswaModal && selectSiswaModal.options.length <= 1) {
                fetch('/dashboard/e-izin/search-siswa', {
                    headers: { 'Accept': 'application/json' }
                })
                    .then((res) => res.json())
                    .then((data) => {
                        selectSiswaModal.innerHTML = '<option value="">-- Pilih Peserta Didik --</option>';
                        data.forEach((s) => {
                            const opt = document.createElement('option');
                            opt.value = s.peserta_didik_id;
                            opt.textContent = `${s.nama} (${s.nama_rombel ?? 'Tanpa Rombel'} - NISN: ${s.nisn ?? '-'})`;
                            selectSiswaModal.appendChild(opt);
                        });
                    })
                    .catch((err) => console.error('Gagal memuat siswa:', err));
            }
        });
    }

    // Submit Form Ajukan e-Izin (AJAX)
    const formAjukan = document.getElementById('formAjukanEIzin');
    if (formAjukan) {
        formAjukan.addEventListener('submit', (e) => {
            e.preventDefault();
            const btnSubmit = document.getElementById('btnSubmitEIzin');
            if (btnSubmit) btnSubmit.disabled = true;

            const formData = new FormData(formAjukan);

            fetch(formAjukan.action, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: formData
            })
                .then(async (res) => {
                    const data = await res.json();
                    if (!res.ok) throw new Error(data.message || 'Gagal mengajukan e-Izin');
                    return data;
                })
                .then((data) => {
                    closeModal(modalAjukan);
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Pengajuan Berhasil!',
                            text: data.message,
                            confirmButtonColor: '#3b82f6'
                        }).then(() => {
                            window.location.reload();
                        });
                    } else {
                        window.location.reload();
                    }
                })
                .catch((err) => {
                    showNotification(err.message, 'error');
                })
                .finally(() => {
                    if (btnSubmit) btnSubmit.disabled = false;
                });
        });
    }

    // Modal Approval Action Elements
    const formApproval = document.getElementById('formApprovalAction');
    const approvalModalTitle = document.getElementById('approvalModalTitle');
    const approvalModalDesc = document.getElementById('approvalModalDesc');
    const approvalCatatan = document.getElementById('approvalCatatan');
    const btnSubmitApproval = document.getElementById('btnSubmitApprovalAction');

    // Helper: Buka Dialog Approval / Rejection
    const openApprovalDialog = (url, title, desc, submitLabel, isDanger = false) => {
        if (!formApproval) return;
        formApproval.action = url;
        if (approvalModalTitle) {
            approvalModalTitle.innerHTML = `<i class="fas ${isDanger ? 'fa-times-circle text-danger' : 'fa-check-circle text-primary'}"></i> ${title}`;
        }
        if (approvalModalDesc) approvalModalDesc.textContent = desc;
        if (approvalCatatan) approvalCatatan.value = '';
        if (btnSubmitApproval) {
            btnSubmitApproval.textContent = submitLabel;
            btnSubmitApproval.className = isDanger ? 'btn btn-danger' : 'btn btn-primary';
        }
        openModal(modalApproval);
    };

    // Delegasi Event untuk Aksi Tabel
    document.addEventListener('click', (e) => {
        // 1. Setujui Wali Kelas
        const btnApproveWali = e.target.closest('.btn-approve-wali');
        if (btnApproveWali) {
            const id = btnApproveWali.getAttribute('data-id');
            const tiket = btnApproveWali.getAttribute('data-tiket');
            const nama = btnApproveWali.getAttribute('data-nama');
            openApprovalDialog(
                `/dashboard/e-izin/${id}/approve-wali`,
                'Setujui e-Izin (Wali Kelas)',
                `Apakah Anda menyetujui pengajuan izin untuk siswa "${nama}" (${tiket})? Alur akan diteruskan ke Guru Piket.`,
                'Ya, Setujui',
                false
            );
            return;
        }

        // 2. Tolak Wali Kelas
        const btnRejectWali = e.target.closest('.btn-reject-wali');
        if (btnRejectWali) {
            const id = btnRejectWali.getAttribute('data-id');
            const tiket = btnRejectWali.getAttribute('data-tiket');
            const nama = btnRejectWali.getAttribute('data-nama');
            openApprovalDialog(
                `/dashboard/e-izin/${id}/reject-wali`,
                'Tolak e-Izin (Wali Kelas)',
                `Tolak permohonan izin siswa "${nama}" (${tiket}). Mohon tuliskan alasan penolakan pada kolom catatan di bawah.`,
                'Tolak Izin',
                true
            );
            return;
        }

        // 3. Setujui Guru Piket
        const btnApprovePiket = e.target.closest('.btn-approve-piket');
        if (btnApprovePiket) {
            const id = btnApprovePiket.getAttribute('data-id');
            const tiket = btnApprovePiket.getAttribute('data-tiket');
            const nama = btnApprovePiket.getAttribute('data-nama');
            openApprovalDialog(
                `/dashboard/e-izin/${id}/approve-piket`,
                'Setujui e-Izin (Guru Piket)',
                `Setujui tiket izin "${nama}" (${tiket}). Tiket resmi akan diterbitkan dan siap diverifikasi Security Pos Gerbang.`,
                'Ya, Terbitkan Izin',
                false
            );
            return;
        }

        // 4. Tolak Guru Piket
        const btnRejectPiket = e.target.closest('.btn-reject-piket');
        if (btnRejectPiket) {
            const id = btnRejectPiket.getAttribute('data-id');
            const tiket = btnRejectPiket.getAttribute('data-tiket');
            const nama = btnRejectPiket.getAttribute('data-nama');
            openApprovalDialog(
                `/dashboard/e-izin/${id}/reject-piket`,
                'Tolak e-Izin (Guru Piket)',
                `Tolak permohonan izin siswa "${nama}" (${tiket}) oleh Guru Piket. Mohon cantumkan alasan penolakan.`,
                'Tolak Izin',
                true
            );
            return;
        }

        // 5. Checkout Security Gerbang (Keluar)
        const btnCheckoutGate = e.target.closest('.btn-checkout-gate');
        if (btnCheckoutGate) {
            const id = btnCheckoutGate.getAttribute('data-id');
            const tiket = btnCheckoutGate.getAttribute('data-tiket');
            const nama = btnCheckoutGate.getAttribute('data-nama');
            const jenis = btnCheckoutGate.getAttribute('data-jenis');
            const jenisTeks = jenis === 'pulang_cepat' ? 'PULANG' : 'KELUAR';

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: `Verifikasi ${jenisTeks} Gerbang?`,
                    text: `Konfirmasi bahwa siswa "${nama}" (${tiket}) telah KELUAR melalui gerbang sekolah sekarang.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ea580c',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: 'Ya, Verifikasi Keluar',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        fetch(`/dashboard/e-izin/${id}/checkout-security`, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrfToken
                            }
                        })
                            .then((res) => res.json())
                            .then((data) => {
                                showNotification(data.message);
                                setTimeout(() => window.location.reload(), 1200);
                            })
                            .catch((err) => showNotification(err.message, 'error'));
                    }
                });
            }
            return;
        }

        // 6. Checkin Security Gerbang (Kembali Masuk)
        const btnCheckinGate = e.target.closest('.btn-checkin-gate');
        if (btnCheckinGate) {
            const id = btnCheckinGate.getAttribute('data-id');
            const tiket = btnCheckinGate.getAttribute('data-tiket');
            const nama = btnCheckinGate.getAttribute('data-nama');

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Verifikasi Masuk Kembali?',
                    text: `Konfirmasi bahwa siswa "${nama}" (${tiket}) telah KEMBALI masuk ke lingkungan sekolah sekarang.`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#10b981',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: 'Ya, Verifikasi Kembali',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        fetch(`/dashboard/e-izin/${id}/checkin-security`, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrfToken
                            }
                        })
                            .then((res) => res.json())
                            .then((data) => {
                                showNotification(data.message);
                                setTimeout(() => window.location.reload(), 1200);
                            })
                            .catch((err) => showNotification(err.message, 'error'));
                    }
                });
            }
            return;
        }

        // 7. Batalkan Pengajuan Izin
        const btnCancelPermit = e.target.closest('.btn-cancel-permit');
        if (btnCancelPermit) {
            const id = btnCancelPermit.getAttribute('data-id');
            const tiket = btnCancelPermit.getAttribute('data-tiket');

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Batalkan Pengajuan e-Izin?',
                    text: `Apakah Anda yakin ingin membatalkan pengajuan izin dengan nomor tiket ${tiket}?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ef4444',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: 'Ya, Batalkan',
                    cancelButtonText: 'Kembali'
                }).then((result) => {
                    if (result.isConfirmed) {
                        fetch(`/dashboard/e-izin/${id}/batalkan`, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrfToken
                            }
                        })
                            .then((res) => res.json())
                            .then((data) => {
                                showNotification(data.message);
                                setTimeout(() => window.location.reload(), 1000);
                            })
                            .catch((err) => showNotification(err.message, 'error'));
                    }
                });
            }
            return;
        }

        // 8. View Detail Modal
        const btnViewDetail = e.target.closest('.btn-view-detail');
        if (btnViewDetail) {
            const id = btnViewDetail.getAttribute('data-id');
            const content = document.getElementById('detailIzinContent');
            if (content) {
                content.innerHTML = `
                    <div style="text-align: center; padding: 24px; color: var(--text-muted);">
                        <i class="fas fa-spinner fa-spin" style="font-size: var(--text-2xl, 1.5rem);"></i>
                        <div style="margin-top: 8px;">Memuat rincian tiket...</div>
                    </div>`;
            }
            openModal(modalDetail);

            fetch(`/dashboard/e-izin/${id}`, {
                headers: { 'Accept': 'application/json' }
            })
                .then((res) => res.json())
                .then((res) => {
                    if (res.status === 'success' && res.data) {
                        const d = res.data;
                        content.innerHTML = `
                            <div style="display: flex; flex-direction: column; gap: 14px;">
                                <div style="display: flex; justify-content: space-between; align-items: center; background: rgba(59,130,246,0.08); padding: 12px 16px; border-radius: var(--radius-lg, 10px);">
                                    <div>
                                        <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted);">Nomor Tiket:</div>
                                        <div style="font-weight: 800; font-family: monospace; font-size: var(--text-base, 1rem); color: var(--text-color);">${d.nomor_tiket}</div>
                                    </div>
                                    <div>
                                        <span class="badge ${d.jenis_izin === 'pulang_cepat' ? 'badge-danger' : 'badge-primary'}" style="font-size: var(--text-xs, 0.75rem); padding: 4px 10px;">
                                            ${d.jenis_izin === 'pulang_cepat' ? 'Izin Pulang' : 'Keluar-Masuk KBM'}
                                        </span>
                                    </div>
                                </div>

                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; font-size: var(--text-sm, 0.875rem);">
                                    <div><strong>Nama Siswa:</strong> ${d.nama_siswa || '-'}</div>
                                    <div><strong>Kelas:</strong> ${d.nama_rombel || '-'}</div>
                                    <div><strong>NISN:</strong> ${d.nisn || '-'}</div>
                                    <div><strong>Tanggal:</strong> ${d.tanggal}</div>
                                    <div><strong>Waktu Keluar:</strong> Pukul ${d.jam_izin_keluar ? d.jam_izin_keluar.substring(0, 5) : '-'} WIB</div>
                                    <div><strong>Rencana Kembali:</strong> ${d.jam_rencana_kembali ? d.jam_rencana_kembali.substring(0, 5) + ' WIB' : '-'}</div>
                                </div>

                                <div style="background: var(--bg-hover, rgba(0,0,0,0.03)); padding: 10px 14px; border-radius: var(--radius-md, 8px);">
                                    <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); font-weight: 700;">Alasan / Keperluan:</div>
                                    <div style="font-size: var(--text-sm, 0.875rem); color: var(--text-color); margin-top: 2px;">&ldquo;${d.alasan}&rdquo;</div>
                                </div>

                                <div style="border-top: 1px solid var(--border-color); padding-top: 12px;">
                                    <div style="font-weight: 800; font-size: var(--text-sm, 0.875rem); margin-bottom: 8px;">
                                        <i class="fas fa-timeline text-primary"></i> Alur Riwayat Persetujuan
                                    </div>
                                    <div style="display: flex; flex-direction: column; gap: 8px; font-size: var(--text-xs, 0.75rem);">
                                        <div style="display: flex; align-items: center; gap: 8px;">
                                            <i class="fas fa-check-circle text-success"></i>
                                            <span><strong>1. Siswa:</strong> Diajukan (${d.created_at || '-'}) &bull; ${d.created_by || 'Siswa'}</span>
                                        </div>
                                        <div style="display: flex; align-items: center; gap: 8px;">
                                            <i class="fas ${d.wali_status === 'disetujui' ? 'fa-check-circle text-success' : (d.wali_status === 'ditolak' ? 'fa-times-circle text-danger' : 'fa-clock text-warning')}"></i>
                                            <span><strong>2. Wali Kelas:</strong> ${d.wali_status.toUpperCase()} ${d.nama_wali ? `(${d.nama_wali})` : ''} ${d.wali_approved_at ? `- ${d.wali_approved_at}` : ''} ${d.wali_catatan ? `[Catatan: "${d.wali_catatan}"]` : ''}</span>
                                        </div>
                                        <div style="display: flex; align-items: center; gap: 8px;">
                                            <i class="fas ${d.piket_status === 'disetujui' ? 'fa-check-circle text-success' : (d.piket_status === 'ditolak' ? 'fa-times-circle text-danger' : 'fa-clock text-warning')}"></i>
                                            <span><strong>3. Guru Piket:</strong> ${d.piket_status.toUpperCase()} ${d.nama_piket ? `(${d.nama_piket})` : ''} ${d.piket_approved_at ? `- ${d.piket_approved_at}` : ''} ${d.piket_catatan ? `[Catatan: "${d.piket_catatan}"]` : ''}</span>
                                        </div>
                                        <div style="display: flex; align-items: center; gap: 8px;">
                                            <i class="fas ${d.status === 'kembali' || d.status === 'pulang_selesai' ? 'fa-check-circle text-success' : (d.status === 'di_luar' ? 'fa-walking text-warning' : 'fa-shield-halved text-muted')}"></i>
                                            <span><strong>4. Security Gerbang:</strong> Status: ${d.status.toUpperCase()} ${d.satpam_checkout_at ? `[Keluar: ${d.satpam_checkout_at} by ${d.satpam_checkout_by || 'Satpam'}]` : ''} ${d.satpam_checkin_at ? `[Kembali: ${d.satpam_checkin_at} by ${d.satpam_checkin_by || 'Satpam'}]` : ''}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                    } else {
                        content.innerHTML = '<div style="color: var(--danger); text-align: center;">Gagal memuat rincian tiket.</div>';
                    }
                })
                .catch((err) => {
                    content.innerHTML = `<div style="color: var(--danger); text-align: center;">Error: ${err.message}</div>`;
                });
            return;
        }

        // 9. Buka Modal Slip / Tiket Digital Siswa
        const btnSlipDigital = e.target.closest('.btn-slip-digital');
        if (btnSlipDigital) {
            const id = btnSlipDigital.getAttribute('data-id');
            const content = document.getElementById('slipDigitalContent');
            if (content) {
                content.innerHTML = `
                    <div style="text-align: center; padding: 24px; color: var(--text-muted);">
                        <i class="fas fa-spinner fa-spin" style="font-size: 1.5rem;"></i>
                        <div style="margin-top: 8px;">Menyiapkan tiket digital...</div>
                    </div>`;
            }
            openModal(modalSlipDigital);

            fetch(`/dashboard/e-izin/${id}`, {
                headers: { 'Accept': 'application/json' }
            })
                .then((res) => res.json())
                .then((res) => {
                    if (res.status === 'success' && res.data) {
                        const d = res.data;
                        const statusColors = {
                            'menunggu_wali': '#f59e0b',
                            'menunggu_piket': '#6366f1',
                            'menunggu_satpam': '#0ea5e9',
                            'di_luar': '#ea580c',
                            'kembali': '#10b981',
                            'pulang_selesai': '#10b981',
                            'ditolak_wali': '#ef4444',
                            'ditolak_piket': '#ef4444',
                            'dibatalkan': '#64748b'
                        };
                        const statusLabels = {
                            'menunggu_wali': 'Menunggu Wali Kelas',
                            'menunggu_piket': 'Menunggu Guru Piket',
                            'menunggu_satpam': 'Disetujui Piket (Siap Gerbang)',
                            'di_luar': 'Sedang di Luar Kampus',
                            'kembali': 'Selesai (Kembali)',
                            'pulang_selesai': 'Selesai Pulang',
                            'ditolak_wali': 'Ditolak Wali Kelas',
                            'ditolak_piket': 'Ditolak Guru Piket',
                            'dibatalkan': 'Dibatalkan'
                        };
                        const color = statusColors[d.status] || '#64748b';
                        const label = statusLabels[d.status] || d.status;

                        content.innerHTML = `
                            <div style="display: flex; flex-direction: column; align-items: center; gap: 14px;">
                                <div style="display: flex; align-items: center; gap: 12px; width: 100%; background: var(--bg-hover, rgba(0,0,0,0.03)); padding: 12px; border-radius: var(--radius-lg, 10px);">
                                    <img src="${d.foto_url || '/img/avatar-default.png'}" alt="${d.nama_siswa}"
                                        style="width: 52px; height: 52px; border-radius: 50%; object-fit: cover; border: 2px solid var(--border-color); flex-shrink: 0;">
                                    <div style="flex: 1; min-width: 0;">
                                        <div style="font-weight: 800; font-size: 1rem; color: var(--text-color); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                            ${d.nama_siswa}
                                        </div>
                                        <div style="font-size: 0.75rem; color: var(--text-muted);">
                                            ${d.nama_rombel || 'Kelas'} &bull; NISN: ${d.nisn || '-'}
                                        </div>
                                        <div style="margin-top: 4px;">
                                            <span style="display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 0.7rem; font-weight: 800; color: #fff; background: ${color};">
                                                ${label}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <div style="display: flex; flex-direction: column; align-items: center; gap: 8px; padding: 14px 12px; background: #ffffff; border: 1px dashed var(--border-color); border-radius: 10px; width: 100%; box-shadow: inset 0 2px 4px rgba(0,0,0,0.02);">
                                    ${res.qr_svg ? `<div style="width: 140px; height: 140px; display: flex; align-items: center; justify-content: center;">${res.qr_svg}</div>` : ''}
                                    ${res.barcode_svg ? `<div style="width: 180px; height: 38px; overflow: hidden; display: flex; align-items: center; justify-content: center;">${res.barcode_svg}</div>` : ''}
                                    <div style="font-family: monospace; font-weight: 800; font-size: 0.95rem; color: #0f172a; letter-spacing: 0.5px;">
                                        ${d.nomor_tiket}
                                    </div>
                                    <div style="font-size: 0.72rem; color: #64748b; text-align: center;">
                                        Tunjukkan QR / Barcode ini saat melintas di Pos Gerbang
                                    </div>
                                </div>

                                <div style="width: 100%; font-size: 0.8125rem; display: flex; flex-direction: column; gap: 6px; background: rgba(59,130,246,0.04); padding: 10px 14px; border-radius: 8px; border: 1px solid rgba(59,130,246,0.15);">
                                    <div style="display: flex; justify-content: space-between;">
                                        <span style="color: var(--text-muted);">Jenis Izin:</span>
                                        <span style="font-weight: 700;">${d.jenis_izin === 'pulang_cepat' ? 'Pulang Cepat' : 'Keluar-Masuk Sementara'}</span>
                                    </div>
                                    <div style="display: flex; justify-content: space-between;">
                                        <span style="color: var(--text-muted);">Jam Keluar:</span>
                                        <span style="font-weight: 700;">${d.jam_izin_keluar ? d.jam_izin_keluar.substring(0, 5) : '-'} WIB</span>
                                    </div>
                                    ${d.jenis_izin === 'keluar_sebentar' ? `
                                        <div style="display: flex; justify-content: space-between;">
                                            <span style="color: var(--text-muted);">Rencana Kembali:</span>
                                            <span style="font-weight: 700; color: #2563eb;">${d.jam_rencana_kembali ? d.jam_rencana_kembali.substring(0, 5) + ' WIB' : '10 Menit'}</span>
                                        </div>
                                    ` : ''}
                                    <div style="border-top: 1px dashed var(--border-color); padding-top: 6px; margin-top: 2px;">
                                        <span style="color: var(--text-muted); font-size: 0.72rem; display: block;">Alasan / Keperluan:</span>
                                        <span style="font-style: italic; font-weight: 600;">&ldquo;${d.alasan}&rdquo;</span>
                                    </div>
                                </div>

                                <div style="display: flex; gap: 8px; width: 100%;">
                                    <a href="/dashboard/e-izin/${d.id}/cetak" target="_blank" class="btn btn-outline"
                                        style="flex: 1; height: 38px; display: inline-flex; align-items: center; justify-content: center; gap: 6px; border-radius: 8px; font-size: 0.8125rem;">
                                        <i class="fas fa-print"></i> Cetak Fisik
                                    </a>
                                    <button type="button" class="btn btn-primary btn-close-modal" data-target="modalSlipDigital"
                                        style="flex: 1; height: 38px; border-radius: 8px; font-size: 0.8125rem;">
                                        Tutup
                                    </button>
                                </div>
                            </div>
                        `;
                        content.querySelectorAll('.btn-close-modal').forEach((btn) => {
                            btn.addEventListener('click', () => closeModal(modalSlipDigital));
                        });
                    } else {
                        content.innerHTML = '<div style="color: var(--danger); text-align: center;">Gagal memuat tiket digital.</div>';
                    }
                })
                .catch((err) => {
                    content.innerHTML = `<div style="color: var(--danger); text-align: center;">Error: ${err.message}</div>`;
                });
            return;
        }
    });

    // Submit Modal Approval Action Form (AJAX)
    if (formApproval) {
        formApproval.addEventListener('submit', (e) => {
            e.preventDefault();
            if (btnSubmitApproval) btnSubmitApproval.disabled = true;

            const formData = new FormData(formApproval);

            fetch(formApproval.action, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: formData
            })
                .then(async (res) => {
                    const data = await res.json();
                    if (!res.ok) throw new Error(data.message || 'Gagal memproses tindakan.');
                    return data;
                })
                .then((data) => {
                    closeModal(modalApproval);
                    showNotification(data.message);
                    setTimeout(() => window.location.reload(), 1000);
                })
                .catch((err) => {
                    showNotification(err.message, 'error');
                })
                .finally(() => {
                    if (btnSubmitApproval) btnSubmitApproval.disabled = false;
                });
        });
    }

    // Modal Pos Gerbang Security
    if (btnOpenScan && modalSecurity) {
        btnOpenScan.addEventListener('click', () => {
            const inputScan = document.getElementById('inputScanTicket');
            const resultArea = document.getElementById('scanResultArea');
            if (inputScan) {
                inputScan.value = '';
                setTimeout(() => inputScan.focus(), 150);
            }
            if (resultArea) resultArea.style.display = 'none';
            openModal(modalSecurity);
        });
    }

    // Lookup Tiket Scanner
    const btnDoScanLookup = document.getElementById('btnDoScanLookup');
    const inputScanTicket = document.getElementById('inputScanTicket');
    const scanResultArea = document.getElementById('scanResultArea');

    const executeScanLookup = () => {
        const val = inputScanTicket ? inputScanTicket.value.trim() : '';
        if (!val) {
            showNotification('Masukkan nomor tiket atau NISN siswa.', 'warning');
            return;
        }

        if (scanResultArea) {
            scanResultArea.style.display = 'block';
            scanResultArea.innerHTML = `
                <div style="text-align: center; padding: 14px; color: var(--text-muted);">
                    <i class="fas fa-spinner fa-spin"></i> Memeriksa tiket di sistem...
                </div>`;
        }

        fetch(`/dashboard/e-izin/scan-tiket?keyword=${encodeURIComponent(val)}`, {
            headers: { 'Accept': 'application/json' }
        })
            .then(async (res) => {
                const data = await res.json();
                if (!res.ok) throw new Error(data.message || 'Tiket tidak ditemukan.');
                return data;
            })
            .then((res) => {
                const d = res.data;
                const canCheckout = (d.status === 'menunggu_satpam');
                const canCheckin = (d.status === 'di_luar' && d.jenis_izin === 'keluar_sebentar');

                scanResultArea.innerHTML = `
                    <div style="display: flex; flex-direction: column; gap: 10px;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="font-weight: 800; font-family: monospace; font-size: var(--text-base, 1rem); color: var(--primary, #3b82f6);">
                                ${d.nomor_tiket}
                            </span>
                            <span class="badge ${d.jenis_izin === 'pulang_cepat' ? 'badge-danger' : 'badge-primary'}" style="font-size: var(--text-xs, 0.75rem); padding: 3px 8px;">
                                ${d.jenis_izin === 'pulang_cepat' ? 'Izin Pulang' : 'Keluar-Masuk KBM'}
                            </span>
                        </div>

                        <div style="font-size: var(--text-sm, 0.875rem);">
                            <div><strong>Siswa:</strong> ${d.nama_siswa} (NISN: ${d.nisn || '-'})</div>
                            <div><strong>Kelas:</strong> ${d.nama_rombel || '-'} &bull; Wali: ${d.nama_wali || '-'}</div>
                            <div style="margin-top: 4px;"><strong>Alasan:</strong> &ldquo;${d.alasan}&rdquo;</div>
                            <div style="margin-top: 4px; color: var(--text-muted); font-size: var(--text-xs, 0.75rem);">
                                Jam Keluar: ${d.jam_izin_keluar ? d.jam_izin_keluar.substring(0, 5) : '-'} WIB
                                ${d.jam_rencana_kembali ? `| Rencana Kembali: ${d.jam_rencana_kembali.substring(0, 5)} WIB` : ''}
                            </div>
                        </div>

                        <div style="border-top: 1px solid var(--border-color); padding-top: 10px; display: flex; justify-content: flex-end; gap: 8px;">
                            ${canCheckout ? `
                                <button type="button" class="btn btn-warning btn-checkout-gate" data-id="${d.id}" data-tiket="${d.nomor_tiket}" data-nama="${d.nama_siswa}" data-jenis="${d.jenis_izin}"
                                    style="height: 38px; border-radius: var(--radius-md, 8px); font-size: var(--text-sm, 0.875rem);">
                                    <i class="fas fa-door-open"></i> Verifikasi KELUAR Gerbang
                                </button>
                            ` : ''}

                            ${canCheckin ? `
                                <button type="button" class="btn btn-success btn-checkin-gate" data-id="${d.id}" data-tiket="${d.nomor_tiket}" data-nama="${d.nama_siswa}"
                                    style="height: 38px; border-radius: var(--radius-md, 8px); font-size: var(--text-sm, 0.875rem);">
                                    <i class="fas fa-right-to-bracket"></i> Verifikasi KEMBALI Masuk
                                </button>
                            ` : ''}

                            ${!canCheckout && !canCheckin ? `
                                <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); padding: 6px 0;">
                                    Status tiket saat ini: <strong>${d.status.toUpperCase()}</strong> (Tidak memerlukan aksi gerbang saat ini).
                                </div>
                            ` : ''}
                        </div>
                    </div>
                `;
            })
            .catch((err) => {
                scanResultArea.innerHTML = `
                    <div style="text-align: center; color: var(--danger, #ef4444); padding: 10px;">
                        <i class="fas fa-circle-exclamation"></i> ${err.message}
                    </div>`;
            });
    };

    if (btnDoScanLookup) {
        btnDoScanLookup.addEventListener('click', executeScanLookup);
    }
    if (inputScanTicket) {
        inputScanTicket.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                executeScanLookup();
            }
        });
    }

    // Live Search & Auto Submit Filters
    const searchEIzin = document.getElementById('searchEIzin');
    const clearSearch = document.getElementById('clearSearch');
    const filterForm = document.getElementById('filterForm');

    if (filterForm) {
        filterForm.querySelectorAll('select, input[type="date"]').forEach((input) => {
            input.addEventListener('change', () => {
                filterForm.submit();
            });
        });
    }

    if (searchEIzin && filterForm) {
        let debounceTimer;
        searchEIzin.addEventListener('input', () => {
            clearTimeout(debounceTimer);
            if (clearSearch) {
                if (searchEIzin.value.trim().length > 0) {
                    clearSearch.classList.add('visible');
                } else {
                    clearSearch.classList.remove('visible');
                }
            }
            debounceTimer = setTimeout(() => {
                filterForm.submit();
            }, 500);
        });
    }

    if (clearSearch && searchEIzin && filterForm) {
        clearSearch.addEventListener('click', () => {
            searchEIzin.value = '';
            clearSearch.classList.remove('visible');
            filterForm.submit();
        });
    }
});
