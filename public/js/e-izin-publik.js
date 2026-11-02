document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('formCekTiket');
    const input = document.getElementById('ticketInput');
    const btn = document.getElementById('btnCekTiket');
    const resultWrap = document.getElementById('ticketResultWrap');

    if (!form || !input || !resultWrap) return;

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const endpoint = (window.E_IZIN_CONFIG && window.E_IZIN_CONFIG.cekTiketUrl) || '/e-izin/cek-tiket';

    // Status map
    const STATUS_MAP = {
        menunggu_wali: { label: 'Menunggu Wali Kelas', color: '#f59e0b', bg: 'rgba(245, 158, 11, 0.12)', icon: 'fa-clock' },
        menunggu_piket: { label: 'Menunggu Guru Piket', color: '#0ea5e9', bg: 'rgba(14, 165, 233, 0.12)', icon: 'fa-user-clock' },
        disetujui: { label: 'Disetujui (Siap Gerbang)', color: '#10b981', bg: 'rgba(16, 185, 129, 0.12)', icon: 'fa-check-circle' },
        keluar: { label: 'Sedang di Luar Kampus', color: '#f97316', bg: 'rgba(249, 115, 22, 0.12)', icon: 'fa-person-walking-arrow-right' },
        selesai: { label: 'Selesai / Sudah Kembali', color: '#8b5cf6', bg: 'rgba(139, 92, 246, 0.12)', icon: 'fa-circle-check' },
        ditolak_wali: { label: 'Ditolak Wali Kelas', color: '#ef4444', bg: 'rgba(239, 68, 68, 0.12)', icon: 'fa-circle-xmark' },
        ditolak_piket: { label: 'Ditolak Guru Piket', color: '#ef4444', bg: 'rgba(239, 68, 68, 0.12)', icon: 'fa-circle-xmark' },
        dibatalkan: { label: 'Dibatalkan', color: '#64748b', bg: 'rgba(100, 116, 139, 0.12)', icon: 'fa-ban' }
    };

    function escapeHtml(str) {
        if (!str) return '-';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function renderError(message) {
        resultWrap.style.display = 'block';
        resultWrap.innerHTML = `
            <div style="padding: 14px 16px; border-radius: var(--radius-md, 10px); background: rgba(239, 68, 68, 0.08); border: 1px solid rgba(239, 68, 68, 0.25); color: var(--danger, #ef4444); font-size: var(--text-sm, 0.8125rem); display: flex; align-items: flex-start; gap: 10px;">
                <i class="fas fa-circle-exclamation" style="margin-top: 3px; font-size: 1rem;"></i>
                <div>
                    <div style="font-weight: 700;">Data Tidak Ditemukan</div>
                    <div style="color: var(--text-muted, #94a3b8); margin-top: 2px;">${escapeHtml(message)}</div>
                </div>
            </div>
        `;
    }

    function buildStep(title, subtitle, status) {
        // status: 'done', 'active', 'rejected', 'pending'
        let color = 'var(--text-muted, #94a3b8)';
        let icon = '<i class="far fa-circle"></i>';
        let bg = 'rgba(148, 163, 184, 0.1)';

        if (status === 'done') {
            color = '#10b981';
            icon = '<i class="fas fa-check"></i>';
            bg = 'rgba(16, 185, 129, 0.15)';
        } else if (status === 'active') {
            color = '#0ea5e9';
            icon = '<i class="fas fa-spinner fa-spin"></i>';
            bg = 'rgba(14, 165, 233, 0.15)';
        } else if (status === 'rejected') {
            color = '#ef4444';
            icon = '<i class="fas fa-xmark"></i>';
            bg = 'rgba(239, 68, 68, 0.15)';
        }

        return `
            <div style="display: flex; gap: 10px; align-items: flex-start; flex: 1; min-width: 120px;">
                <div style="width: 26px; height: 26px; border-radius: 50%; display: flex; align-items: center; justify-content: center; background: ${bg}; color: ${color}; font-size: 0.75rem; flex-shrink: 0; border: 1px solid ${color};">
                    ${icon}
                </div>
                <div>
                    <div style="font-weight: 700; font-size: 0.8rem; color: var(--text-heading);">${title}</div>
                    <div style="font-size: 0.72rem; color: ${color}; font-weight: 600;">${subtitle}</div>
                </div>
            </div>
        `;
    }

    function renderTicket(data, qrSvg, barcodeSvg) {
        const cfg = STATUS_MAP[data.status] || {
            label: data.status,
            color: '#64748b',
            bg: 'rgba(100, 116, 139, 0.12)',
            icon: 'fa-info-circle'
        };

        const jenisLabel = data.jenis_izin === 'pulang_cepat' ? 'Pulang Cepat' : 'Keluar Sebentar';
        const jenisColor = data.jenis_izin === 'pulang_cepat' ? '#ef4444' : '#0ea5e9';

        // Hitung status alur 4 tahap
        let step1 = { sub: data.created_at ? data.created_at.substring(11, 16) : 'Selesai', state: 'done' };
        let step2 = { sub: 'Menunggu', state: 'active' };
        let step3 = { sub: 'Menunggu', state: 'pending' };
        let step4 = { sub: 'Pos Satpam', state: 'pending' };

        if (data.status === 'ditolak_wali') {
            step2 = { sub: 'Ditolak', state: 'rejected' };
        } else if (data.status === 'menunggu_wali') {
            step2 = { sub: data.nama_wali || 'Pemeriksaan', state: 'active' };
        } else {
            step2 = { sub: data.nama_wali || 'Disetujui', state: 'done' };

            if (data.status === 'ditolak_piket') {
                step3 = { sub: 'Ditolak', state: 'rejected' };
            } else if (data.status === 'menunggu_piket') {
                step3 = { sub: data.nama_piket || 'Pemeriksaan', state: 'active' };
            } else {
                step3 = { sub: data.nama_piket || 'Disetujui', state: 'done' };

                if (data.status === 'disetujui') {
                    step4 = { sub: 'Menuju Gerbang', state: 'active' };
                } else if (data.status === 'keluar') {
                    step4 = { sub: 'Checkout (' + (data.satpam_checkout_at ? data.satpam_checkout_at.substring(11, 16) : 'OK') + ')', state: 'active' };
                } else if (data.status === 'selesai') {
                    step4 = { sub: 'Kembali (' + (data.satpam_checkin_at ? data.satpam_checkin_at.substring(11, 16) : 'OK') + ')', state: 'done' };
                }
            }
        }

        resultWrap.style.display = 'block';
        resultWrap.innerHTML = `
            <div style="background: var(--card-bg, #1e293b); border: 1px solid var(--border-color, rgba(255,255,255,0.1)); border-radius: var(--radius-lg, 14px); padding: 18px; margin-top: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.15);">
                <!-- Header Tiket -->
                <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 10px; border-bottom: 1px dashed var(--border-color, rgba(255,255,255,0.15)); padding-bottom: 12px; margin-bottom: 14px; flex-wrap: wrap;">
                    <div>
                        <div style="font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-muted, #94a3b8); font-weight: 700;">
                            Nomor Tiket Digital
                        </div>
                        <div style="font-family: var(--font-family-mono, monospace); font-weight: 900; font-size: 1.15rem; color: var(--primary, #0ea5e9); letter-spacing: 0.5px;">
                            ${escapeHtml(data.nomor_tiket)}
                        </div>
                    </div>
                    <div style="text-align: right;">
                        <span style="display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; border-radius: var(--radius-full, 999px); font-size: 0.78rem; font-weight: 700; background: ${cfg.bg}; color: ${cfg.color}; border: 1px solid ${cfg.color};">
                            <i class="fas ${cfg.icon}"></i> ${escapeHtml(cfg.label)}
                        </span>
                        <div style="font-size: 0.72rem; color: var(--text-muted, #94a3b8); margin-top: 4px;">
                            ${escapeHtml(data.tanggal || '-')}
                        </div>
                    </div>
                </div>

                <!-- Info Siswa & Perizinan -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; margin-bottom: 16px;">
                    <div>
                        <div style="font-size: 0.72rem; color: var(--text-muted, #94a3b8);">Nama Peserta Didik</div>
                        <div style="font-weight: 800; font-size: 0.95rem; color: var(--text-heading);">${escapeHtml(data.nama_siswa)}</div>
                        <div style="font-size: 0.75rem; color: var(--text-muted, #94a3b8);">NISN: <code style="font-weight:700;">${escapeHtml(data.nisn || '-')}</code></div>
                    </div>
                    <div>
                        <div style="font-size: 0.72rem; color: var(--text-muted, #94a3b8);">Kelas / Rombel</div>
                        <div style="font-weight: 700; font-size: 0.88rem; color: var(--text-heading);">${escapeHtml(data.nama_rombel || '-')}</div>
                        <div style="font-size: 0.75rem; color: ${jenisColor}; font-weight: 700;">
                            <i class="fas fa-tag"></i> ${jenisLabel}
                        </div>
                    </div>
                    <div>
                        <div style="font-size: 0.72rem; color: var(--text-muted, #94a3b8);">Waktu Izin</div>
                        <div style="font-weight: 700; font-size: 0.88rem; color: var(--text-heading);">
                            ${escapeHtml(data.jam_izin_keluar ? data.jam_izin_keluar.substring(0, 5) : '-')} 
                            ${data.jam_rencana_kembali ? 's.d ' + escapeHtml(data.jam_rencana_kembali.substring(0, 5)) : ''}
                        </div>
                        <div style="font-size: 0.75rem; color: var(--text-muted, #94a3b8); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="${escapeHtml(data.alasan)}">
                            Alasan: ${escapeHtml(data.alasan || '-')}
                        </div>
                    </div>
                </div>

                <!-- Alur 4 Tahap Tracker -->
                <div style="padding: 12px; border-radius: var(--radius-md, 10px); background: rgba(0,0,0,0.15); border: 1px solid var(--border-color, rgba(255,255,255,0.06)); margin-bottom: 14px;">
                    <div style="font-size: 0.72rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-muted, #94a3b8); margin-bottom: 10px;">
                        <i class="fas fa-timeline me-1"></i> Pelacakan Validasi 4 Tahap
                    </div>
                    <div style="display: flex; gap: 8px; justify-content: space-between; flex-wrap: wrap;">
                        ${buildStep('1. Siswa', step1.sub, step1.state)}
                        ${buildStep('2. Wali Kelas', step2.sub, step2.state)}
                        ${buildStep('3. Guru Piket', step3.sub, step3.state)}
                        ${buildStep('4. Gerbang', step4.sub, step4.state)}
                    </div>
                </div>

                <!-- QR / Barcode Preview jika Disetujui/Keluar -->
                ${(qrSvg || barcodeSvg) ? `
                    <div style="text-align: center; padding: 14px; background: #ffffff; border-radius: var(--radius-md, 10px); color: #0f172a; margin-top: 10px;">
                        <div style="font-size: 0.75rem; font-weight: 800; color: #475569; text-transform: uppercase; margin-bottom: 6px;">
                            Pindai untuk Scan Pos Gerbang
                        </div>
                        <div style="display: flex; justify-content: center; align-items: center; gap: 16px; flex-wrap: wrap;">
                            ${qrSvg ? `<div style="max-width: 120px;">${qrSvg}</div>` : ''}
                            ${barcodeSvg ? `<div style="max-width: 220px; overflow: hidden;">${barcodeSvg}</div>` : ''}
                        </div>
                    </div>
                ` : ''}
            </div>
        `;
    }

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        const kw = input.value.trim();
        if (!kw) {
            input.focus();
            renderError('Ketik nomor tiket e-Izin atau NISN siswa.');
            return;
        }

        btn.disabled = true;
        const originalHtml = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memeriksa...';

        fetch(endpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ keyword: kw })
        })
        .then(res => res.json())
        .then(res => {
            if (res.status === 'success' && res.data) {
                renderTicket(res.data, res.qr_svg, res.barcode_svg);
            } else {
                renderError(res.message || 'Tiket tidak ditemukan.');
            }
        })
        .catch(err => {
            renderError('Terjadi gangguan jaringan saat memverifikasi tiket.');
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        });
    });
});
