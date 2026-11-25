/**
 * Verval PD (Verifikasi & Validasi Peserta Didik) — SAE Standardized.
 * Menangani: import/hapus Residu, validasi Berkas Digital, verifikasi Usulan Perubahan.
 */
document.addEventListener('DOMContentLoaded', function () {
    const csrf = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const escapeHtml = (str) => {
        if (!str) return '';
        return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    };

    const openModal = (sel) => { const m = document.querySelector(sel); if (m) m.style.display = 'flex'; };
    const closeModal = (sel) => { const m = document.querySelector(sel); if (m) m.style.display = 'none'; };

    document.querySelectorAll('.close-modal').forEach((btn) => {
        btn.addEventListener('click', function () {
            const target = this.getAttribute('data-target');
            if (target) closeModal(target);
        });
    });

    // ============================================================= RESIDU
    const btnImport = document.getElementById('btnImportResidu');
    if (btnImport) {
        btnImport.addEventListener('click', () => openModal('#modalImportResidu'));
    }

    const formImport = document.getElementById('formImportResidu');
    if (formImport) {
        formImport.addEventListener('submit', async function (e) {
            e.preventDefault();
            const fileInput = document.getElementById('fileResidu');
            if (!fileInput || !fileInput.files[0]) {
                Swal.fire('Pilih Berkas', 'Silakan pilih berkas Residu (.xlsx/.xls/.csv) terlebih dahulu.', 'warning');
                return;
            }

            const formData = new FormData();
            formData.append('file_residu', fileInput.files[0]);

            Swal.fire({ title: 'Mengimpor Berkas...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
            try {
                const res = await fetch('/dashboard/verval-pd/residu/import', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    body: formData,
                });
                const json = await res.json();
                if (res.ok && json.status === 'success') {
                    closeModal('#modalImportResidu');
                    Swal.fire({ icon: 'success', title: 'Berhasil!', text: json.message, timer: 1800, showConfirmButton: false });
                    window.refreshLiveTable ? window.refreshLiveTable() : location.reload();
                } else {
                    Swal.fire('Gagal Import', json.message || 'Terjadi kesalahan sistem.', 'error');
                }
            } catch (err) {
                Swal.fire('Kesalahan Jaringan', 'Tidak dapat terhubung ke server.', 'error');
            }
        });
    }

    const templateTargets = [document.getElementById('btnTemplateResidu'), document.getElementById('btnTemplateFromModal')];
    templateTargets.forEach((el) => {
        if (el) {
            el.addEventListener('click', () => { window.location.href = '/dashboard/verval-pd/residu/template'; });
        }
    });

    // ---------------------------------------------------- Residu: pilih baris & cetak
    const residuRowChecks = () => Array.from(document.querySelectorAll('.residu-row-check'));
    const residuSelectedIds = () => residuRowChecks().filter((c) => c.checked).map((c) => c.value);

    const syncResiduSelection = () => {
        const checks = residuRowChecks();
        const count = checks.filter((c) => c.checked).length;

        const checkAll = document.getElementById('residuCheckAll');
        if (checkAll) {
            checkAll.checked = count > 0 && count === checks.length;
            checkAll.indeterminate = count > 0 && count < checks.length;
        }

        const btn = document.getElementById('btnCetakTerpilihResidu');
        const label = document.getElementById('residuSelectedCount');
        if (label) label.textContent = String(count);
        if (btn) {
            btn.disabled = count === 0;
            btn.style.display = count > 0 ? 'inline-flex' : 'none';
        }
    };

    document.querySelectorAll('.residu-row-check').forEach((c) => c.addEventListener('change', syncResiduSelection));

    const residuCheckAll = document.getElementById('residuCheckAll');
    if (residuCheckAll) {
        residuCheckAll.addEventListener('change', function () {
            residuRowChecks().forEach((c) => { c.checked = this.checked; });
            syncResiduSelection();
        });
    }

    // Cetak seluruh baris sesuai filter/pencarian aktif (tanpa centang).
    const btnExportResidu = document.getElementById('btnExportResidu');
    if (btnExportResidu) {
        btnExportResidu.addEventListener('click', () => {
            const params = new URLSearchParams(window.location.search);
            const q = document.getElementById('liveSearch')?.value?.trim();
            if (q) params.set('q', q); else params.delete('q');
            params.delete('ids[]');
            params.delete('ids');
            window.location.href = '/dashboard/verval-pd/residu/cetak?' + params.toString();
        });
    }

    // Reset status konfirmasi identitas → 'belum_konfirmasi' (khusus admin).
    // Tersedia di halaman Residu & Berkas. Satu tombol membuka modal pilih cakupan
    // (Semua Kelas / per kelas). Riwayat usulan perubahan tidak dihapus.
    const btnResetKonfirmasiAll = document.getElementById('btnResetKonfirmasiAll');
    if (btnResetKonfirmasiAll) {
        btnResetKonfirmasiAll.addEventListener('click', () => openModal('#modalResetKonfirmasi'));
    }

    const btnResetKonfirmasiProses = document.getElementById('btnResetKonfirmasiProses');
    if (btnResetKonfirmasiProses) {
        btnResetKonfirmasiProses.addEventListener('click', async function () {
            const sel = document.getElementById('resetKonfirmasiRombel');
            const rombel = sel ? sel.value : '';
            const scope = rombel ? `Kelas <b>${escapeHtml(rombel)}</b>` : '<b>SEMUA</b> kelas';

            const ok = await window.SAE.confirm(
                `Reset status konfirmasi <b>${scope}</b> kembali ke <b>Belum Konfirmasi</b>?<br><small>Riwayat usulan perubahan tetap utuh.</small>`,
                'Reset Konfirmasi?', 'warning', 'Ya, Reset', 'Batal'
            );
            if (!ok) return;

            btnResetKonfirmasiProses.disabled = true;
            try {
                const res = await fetch('/dashboard/verval-pd/konfirmasi/reset-all', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrf(),
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ rombel }),
                });
                const json = await res.json();
                if (res.ok && json.status === 'success') {
                    closeModal('#modalResetKonfirmasi');
                    Swal.fire({ icon: 'success', title: 'Terreset', text: json.message, timer: 1800, showConfirmButton: false });
                    window.refreshLiveTable ? window.refreshLiveTable() : location.reload();
                } else {
                    Swal.fire('Gagal', json.message || 'Reset konfirmasi gagal.', 'error');
                }
            } catch (err) {
                Swal.fire('Error', 'Gagal menghubungi server.', 'error');
            } finally {
                btnResetKonfirmasiProses.disabled = false;
            }
        });
    }

    // Cetak hanya baris yang dicentang.
    const btnCetakTerpilih = document.getElementById('btnCetakTerpilihResidu');
    if (btnCetakTerpilih) {
        btnCetakTerpilih.addEventListener('click', () => {
            const ids = residuSelectedIds();
            if (ids.length === 0) return;
            const p = new URLSearchParams();
            ids.forEach((id) => p.append('ids[]', id));
            window.location.href = '/dashboard/verval-pd/residu/cetak?' + p.toString();
        });
    }

    // ---------------------------------------- Residu: chart rekap Sesuai / Tidak Sesuai
    // Data dihitung server-side dari baris yang tampil (halaman + filter aktif),
    // + di-embed sebagai JSON. Chart digambar ulang setiap tabel di-refresh live.
    const renderResiduChart = (source) => {
        const root = source || document;
        const dataEl = root.querySelector('#residuChartData');
        const canvas = document.getElementById('chartResiduBanding');
        if (!dataEl || !canvas || typeof Chart === 'undefined') return;

        let data;
        try { data = JSON.parse(dataEl.textContent || '{}'); } catch (e) { return; }

        if (window.__residuChart) { window.__residuChart.destroy(); }

        const css = getComputedStyle(document.documentElement);
        const success = css.getPropertyValue('--success').trim() || '#10b981';
        const danger = css.getPropertyValue('--danger').trim() || '#ef4444';
        const textColor = css.getPropertyValue('--text-color').trim() || '#1e293b';

        // Plugin ringkas: tulis angka di atas tiap batang (tanpa dependensi chartjs-plugin-datalabels).
        const valueLabels = {
            id: 'residuValueLabels',
            afterDatasetsDraw(chart) {
                const { ctx } = chart;
                ctx.save();
                ctx.fillStyle = textColor;
                ctx.font = '600 10px -apple-system, Segoe UI, Roboto, sans-serif';
                ctx.textAlign = 'center';
                ctx.textBaseline = 'bottom';
                chart.data.datasets.forEach((ds, di) => {
                    chart.getDatasetMeta(di).data.forEach((bar, i) => {
                        const v = ds.data[i];
                        if (v === null || v === undefined || v === 0) return;
                        ctx.fillText(String(v), bar.x, bar.y - 2);
                    });
                });
                ctx.restore();
            },
        };

        window.__residuChart = new Chart(canvas.getContext('2d'), {
            type: 'bar',
            data: {
                labels: data.labels || [],
                datasets: [
                    { label: 'Sesuai', data: data.sesuai || [], backgroundColor: success, borderRadius: 4, maxBarThickness: 38 },
                    { label: 'Tidak Sesuai', data: data.tidak || [], backgroundColor: danger, borderRadius: 4, maxBarThickness: 38 },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                layout: { padding: { top: 18 } },
                plugins: { legend: { position: 'bottom' } },
                scales: {
                    x: { ticks: { autoSkip: false, maxRotation: 45, minRotation: 0 } },
                    y: { beginAtZero: true, ticks: { precision: 0 } },
                },
            },
            plugins: [valueLabels],
        });
    };

    renderResiduChart(document);
    window.addEventListener('sae:tableRefreshed', (e) => {
        renderResiduChart((e.detail && e.detail.doc) || document);
    });

    // ----------------------------------- Riwayat Residu: buka/tutup kartu per siswa
    // Delegasi agar tetap berfungsi setelah tabel di-refresh live tanpa reload.
    document.addEventListener('click', function (e) {
        const head = e.target.closest('.riwayat-siswa-head');
        if (!head) return;
        e.preventDefault();
        const card = head.closest('.riwayat-siswa-card');
        const body = card ? card.querySelector('.riwayat-siswa-body') : null;
        const caret = head.querySelector('.riwayat-siswa-caret');
        if (!body) return;
        const open = body.style.display !== 'none' && body.style.display !== '';
        body.style.display = open ? 'none' : 'block';
        if (caret) caret.style.transform = open ? '' : 'rotate(180deg)';
    });

    const btnClear = document.getElementById('btnClearResidu');
    if (btnClear) {
        btnClear.addEventListener('click', async function () {
            const confirm = await Swal.fire({
                title: 'Hapus Seluruh Data Residu?',
                text: 'Semua baris Residu akan dihapus permanen. Tindakan ini tidak dapat dibatalkan.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, Hapus',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#ef4444',
            });
            if (!confirm.isConfirmed) return;

            try {
                const res = await fetch('/dashboard/verval-pd/residu', {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                const json = await res.json();
                if (res.ok && json.status === 'success') {
                    Swal.fire({ icon: 'success', title: 'Terhapus', text: json.message, timer: 1600, showConfirmButton: false });
                    window.refreshLiveTable ? window.refreshLiveTable() : location.reload();
                } else {
                    Swal.fire('Gagal', json.message || 'Terjadi kesalahan.', 'error');
                }
            } catch (err) {
                Swal.fire('Error', 'Gagal menghubungi server.', 'error');
            }
        });
    }

    // Delegasi: klik pill "Proses" → ubah Belum → Sedang → Selesai (tercatat di riwayat).
    const prosesMeta = {
        belum: { label: 'Belum Diproses', icon: 'fa-circle-dashed', color: 'var(--text-muted)', bg: 'rgba(148,163,184,0.15)' },
        proses: { label: 'Sedang Proses', icon: 'fa-spinner', color: 'var(--warning, #f59e0b)', bg: 'rgba(245,158,11,0.15)' },
        selesai: { label: 'Selesai', icon: 'fa-circle-check', color: 'var(--success, #10b981)', bg: 'rgba(16,185,129,0.15)' },
    };
    const nextProses = { belum: 'proses', proses: 'selesai', selesai: 'belum' };

    document.addEventListener('click', async function (e) {
        const btn = e.target.closest('.residu-status-btn');
        if (!btn) return;
        e.preventDefault();
        if (btn.disabled) return;

        const id = btn.getAttribute('data-id');
        const next = btn.getAttribute('data-next') || 'proses';

        btn.disabled = true;
        try {
            const res = await fetch(`/dashboard/verval-pd/residu/${encodeURIComponent(id)}/status`, {
                method: 'PATCH',
                headers: {
                    'X-CSRF-TOKEN': csrf(),
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ status: next }),
            });
            const json = await res.json();
            if (res.ok && json.status === 'success') {
                const applied = json.status_proses || next;
                const meta = prosesMeta[applied] || prosesMeta.belum;
                const pill = btn.querySelector('span');
                if (pill) {
                    pill.style.cssText = `display: inline-flex; align-items: center; gap: 5px; padding: 4px 10px; border-radius: var(--radius-pill, 999px); background: ${meta.bg}; color: ${meta.color}; font-size: var(--text-sm, 0.8125rem); font-weight: 700; white-space: nowrap;`;
                    pill.innerHTML = `<i class="fas ${meta.icon}"></i> ${meta.label}`;
                }
                const following = nextProses[applied] || 'proses';
                const followingMeta = prosesMeta[following] || prosesMeta.belum;
                const title = `Klik untuk ubah ke: ${followingMeta.label}`;
                btn.setAttribute('data-status', applied);
                btn.setAttribute('data-next', following);
                btn.setAttribute('title', title);
                btn.setAttribute('aria-label', title);
            } else {
                Swal.fire('Gagal', json.message || 'Status tidak tersimpan.', 'error');
            }
        } catch (err) {
            Swal.fire('Error', 'Gagal menghubungi server.', 'error');
        } finally {
            btn.disabled = false;
        }
    });

    // Delegasi: klik ikon Catatan Residu → modal lihat/ubah catatan (tercatat di riwayat).
    document.addEventListener('click', async function (e) {
        const btn = e.target.closest('.residu-catatan-btn');
        if (!btn) return;
        e.preventDefault();

        const id = btn.getAttribute('data-id');
        const nama = btn.getAttribute('data-nama') || 'Peserta Didik';
        const current = btn.getAttribute('data-catatan') || '';
        const canEdit = btn.getAttribute('data-can-edit') === '1';

        // Tanpa hak update: tampilkan catatan saja (read-only), tanpa tombol simpan.
        if (!canEdit) {
            Swal.fire({
                title: 'Catatan Masalah',
                html: `<div style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); margin-bottom: 8px;">${escapeHtml(nama)}</div>`
                    + `<div style="text-align: left; white-space: pre-wrap; word-break: break-word; color: var(--text-color);">${current ? escapeHtml(current) : '(belum ada catatan)'}</div>`,
                confirmButtonText: 'Tutup',
            });
            return;
        }

        const result = await Swal.fire({
            title: 'Catatan Masalah',
            html: `<div style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); margin-bottom: 8px;">${escapeHtml(nama)}</div>`,
            input: 'textarea',
            inputValue: current,
            inputPlaceholder: 'Tuliskan masalah / tindak lanjut pada peserta didik ini...',
            showCancelButton: true,
            confirmButtonText: 'Simpan',
            cancelButtonText: 'Batal',
            inputAttributes: { maxlength: 1000, 'aria-label': 'Catatan masalah' },
        });
        if (!result.isConfirmed) return;

        const catatan = (result.value || '').trim();
        try {
            const res = await fetch(`/dashboard/verval-pd/residu/${encodeURIComponent(id)}/catatan`, {
                method: 'PATCH',
                headers: {
                    'X-CSRF-TOKEN': csrf(),
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ catatan }),
            });
            const json = await res.json();
            if (res.ok && json.status === 'success') {
                const applied = json.catatan || '';
                btn.setAttribute('data-catatan', applied);
                const icon = btn.querySelector('i');
                const dot = btn.querySelector('[data-catatan-dot]');
                if (applied !== '') {
                    if (icon) icon.className = 'fas fa-note-sticky';
                    btn.style.color = 'var(--warning, #f59e0b)';
                    btn.style.background = 'rgba(245,158,11,0.15)';
                    btn.style.borderColor = 'var(--warning, #f59e0b)';
                    if (!dot) {
                        const s = document.createElement('span');
                        s.setAttribute('data-catatan-dot', '1');
                        s.style.cssText = 'position: absolute; top: -3px; right: -3px; width: 8px; height: 8px; border-radius: 50%; background: var(--warning, #f59e0b); border: 1.5px solid var(--bg-card, #fff);';
                        btn.appendChild(s);
                    }
                } else {
                    if (icon) icon.className = 'fas fa-plus';
                    btn.style.color = 'var(--text-muted)';
                    btn.style.background = 'rgba(148,163,184,0.12)';
                    btn.style.borderColor = 'var(--text-muted)';
                    if (dot) dot.remove();
                }
                Swal.fire({ icon: 'success', title: 'Tersimpan', text: json.message, timer: 1400, showConfirmButton: false });
            } else {
                Swal.fire('Gagal', json.message || 'Catatan tidak tersimpan.', 'error');
            }
        } catch (err) {
            Swal.fire('Error', 'Gagal menghubungi server.', 'error');
        }
    });

    // Delegasi: klik sel centang Residu → ubah ✓/✗ (tercatat di riwayat).
    document.addEventListener('click', async function (e) {
        const btn = e.target.closest('.residu-cell-btn');
        if (!btn) return;
        e.preventDefault();
        if (btn.disabled) return;

        const id = btn.getAttribute('data-id');
        const column = btn.getAttribute('data-column');
        const current = btn.getAttribute('data-value') || '';
        const next = btn.getAttribute('data-next') || '✓';

        btn.disabled = true;
        try {
            const res = await fetch(`/dashboard/verval-pd/residu/${encodeURIComponent(id)}/cell`, {
                method: 'PATCH',
                headers: {
                    'X-CSRF-TOKEN': csrf(),
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ column, value: next }),
            });
            const json = await res.json();
            if (res.ok && json.status === 'success') {
                const applied = json.value === null ? '' : String(json.value);
                btn.setAttribute('data-value', applied);
                btn.setAttribute('data-next', (applied && /[✓✔√]/.test(applied)) ? '✗' : '✓');
                const box = btn.querySelector('span');
                const isYes = applied !== '' && /[✓✔√]/.test(applied);
                const isNo = applied !== '' && /[✗✘×]/.test(applied);
                if (box && (isYes || isNo)) {
                    box.style.cssText = `display: inline-flex; align-items: center; justify-content: center; width: 26px; height: 26px; border-radius: var(--radius-sm, 6px); background: rgba(${isYes ? '16,185,129' : '239,68,68'},0.15); color: ${isYes ? 'var(--success, #10b981)' : 'var(--danger, #ef4444)'};`;
                    box.innerHTML = `<i class="fas fa-${isYes ? 'check' : 'xmark'}"></i>`;
                    box.setAttribute('title', isYes ? 'Sesuai' : 'Tidak Sesuai');
                }
                const title = (applied && /[✓✔√]/.test(applied)) ? 'Klik untuk ubah menjadi Tidak Sesuai (✗)' : 'Klik untuk ubah menjadi Sesuai (✓)';
                btn.setAttribute('title', title);
                btn.setAttribute('aria-label', title);
            } else {
                Swal.fire('Gagal', json.message || 'Perubahan tidak tersimpan.', 'error');
            }
        } catch (err) {
            Swal.fire('Error', 'Gagal menghubungi server.', 'error');
        } finally {
            btn.disabled = false;
        }
    });

    // ====================================================== BERKAS DIGITAL
    let berkasStatusChanged = false;

    // Auto-refresh datatable tiap modal validasi tertutup (lewat jalur mana pun:
    // tombol Tutup, ESC, backdrop) jika ada perubahan status berkas.
    const modalVbWatch = document.getElementById('modalVerifikasiBerkasDigital');
    if (modalVbWatch) {
        new MutationObserver(() => {
            if (modalVbWatch.style.display === 'none' && berkasStatusChanged) {
                berkasStatusChanged = false;
                window.refreshLiveTable ? window.refreshLiveTable() : location.reload();
            }
        }).observe(modalVbWatch, { attributes: true, attributeFilter: ['style'] });
    }

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
            const res = await fetch(`/dashboard/verval-pd/berkas/detail/${encodeURIComponent(id)}`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
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

            // Simpan data siswa/berkas agar tombol "Lihat Semua" bisa membuka grid tanpa fetch ulang.
            window.__berkasAktif = { siswa, items: json.items };

            const jumlahFile = (json.items || []).filter((it) => it.is_uploaded).length;
            const vbTotal = json.items.length;
            let html = `
                <div style="display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap; margin-bottom: 14px; padding: 8px 12px; background: var(--bg-hover); border: 1px solid var(--border-color); border-radius: var(--radius-lg, 12px); position: sticky; top: 0; z-index: 5;">
                    <div style="display: flex; align-items: center; gap: 10px; min-width: 0;">
                        <span id="vbStepCounter" style="font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-color); white-space: nowrap;">Dokumen 1 / ${vbTotal}</span>
                        <span id="vbStepLabel" style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"></span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 6px;">
                        <button type="button" id="vbPrev" class="btn btn-outline" title="Dokumen sebelumnya" aria-label="Dokumen sebelumnya"
                            style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px);">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <button type="button" id="vbNext" class="btn btn-outline" title="Dokumen selanjutnya" aria-label="Dokumen selanjutnya"
                            style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px);">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                        <button type="button" id="btnLihatSemuaBerkas" class="btn btn-primary"
                            ${jumlahFile === 0 ? 'disabled' : ''} title="Lihat semua berkas sekaligus"
                            aria-label="Lihat semua berkas sekaligus"
                            style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px);">
                            <i class="fas fa-images"></i>
                        </button>
                    </div>
                </div>
                <div style="display: flex; flex-direction: column; gap: 16px;">`;

            json.items.forEach((item, stepIdx) => {
                const statusBadge = item.status === 'valid'
                    ? `<span class="badge badge-success" style="font-size: var(--text-xs, 0.75rem); padding: 3px 8px;"><i class="fas fa-circle-check me-1"></i> Valid</span>`
                    : (item.status === 'tidak_valid'
                        ? `<span class="badge badge-danger" style="font-size: var(--text-xs, 0.75rem); padding: 3px 8px;"><i class="fas fa-circle-xmark me-1"></i> Tidak Valid</span>`
                        : (item.is_uploaded
                            ? `<span class="badge badge-warning" style="font-size: var(--text-xs, 0.75rem); padding: 3px 8px;"><i class="fas fa-clock me-1"></i> Menunggu</span>`
                            : `<span class="badge badge-outline" style="font-size: var(--text-xs, 0.75rem); padding: 3px 8px;"><i class="fas fa-minus me-1"></i> Belum Unggah</span>`));

                html += `
                    <div class="card card-berkas-item vb-card" id="cardBerkas_${item.jenis_berkas}" data-key="${item.jenis_berkas}" data-step="${stepIdx}" data-uploaded="${item.is_uploaded ? '1' : '0'}" data-label="${escapeHtml(item.label)}" data-selected-status="${item.status || ''}">
                        <div class="vb-col-pdf">
                            ${item.is_uploaded ? `
                                <div style="border: 1px solid var(--border-color); border-radius: var(--radius-md, 8px); overflow: hidden; background: var(--text-main, #0f172a); height: 100%;">
                                    <iframe src="${item.file_url}" title="${escapeHtml(item.label)}" loading="lazy"
                                        style="width: 100%; height: 100%; min-height: 62vh; border: none; display: block;"></iframe>
                                </div>
                            ` : `
                                <div style="height: 100%; min-height: 40vh; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 10px; color: var(--text-muted); border: 1px dashed var(--border-color); border-radius: var(--radius-md, 8px); background: var(--bg-hover);">
                                    <i class="fas fa-file-circle-xmark" style="font-size: var(--text-3xl, 1.875rem); opacity: 0.5;"></i>
                                    <span style="font-size: var(--text-sm, 0.8125rem);">Belum ada berkas digital</span>
                                    <button type="button" class="btn btn-outline" onclick="document.getElementById('fileTendik_${item.jenis_berkas}').click()" style="padding: 6px 16px; font-size: var(--text-sm, 0.8125rem); border-radius: var(--radius-md, 8px);">
                                        <i class="fas fa-upload me-1"></i> Unggah File
                                    </button>
                                </div>
                            `}
                        </div>

                        <div class="vb-col-panel">
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
                                <div class="status-badge-container">${statusBadge}</div>
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
                                    <input type="file" id="fileTendik_${item.jenis_berkas}" accept=".pdf,.jpg,.jpeg,.png" style="display: none;" class="input-file-tendik" data-pd-id="${id}" data-key="${item.jenis_berkas}">
                                    <button type="button" class="btn btn-outline" onclick="document.getElementById('fileTendik_${item.jenis_berkas}').click()" style="padding: 4px 10px; font-size: var(--text-xs, 0.75rem); border-radius: var(--radius-sm, 6px); cursor: pointer;" title="Unggah file peserta didik">
                                        <i class="fas fa-upload me-1"></i> ${item.is_uploaded ? 'Ganti File' : 'Unggah File'}
                                    </button>
                                </div>
                            </div>

                            <div style="display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap;">
                                <div style="display: flex; gap: 16px; align-items: center;">
                                    <label style="display: inline-flex; align-items: center; gap: 6px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--success, #10b981); cursor: pointer; margin: 0;">
                                        <input type="radio" name="status_${item.jenis_berkas}" value="valid" ${item.status === 'valid' ? 'checked' : ''} class="radio-status-berkas" data-key="${item.jenis_berkas}">
                                        <i class="fas fa-circle-check"></i> Valid
                                    </label>
                                    <label style="display: inline-flex; align-items: center; gap: 6px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--danger, #ef4444); cursor: pointer; margin: 0;">
                                        <input type="radio" name="status_${item.jenis_berkas}" value="tidak_valid" ${item.status === 'tidak_valid' ? 'checked' : ''} class="radio-status-berkas" data-key="${item.jenis_berkas}">
                                        <i class="fas fa-circle-xmark"></i> Tidak Valid
                                    </label>
                                </div>
                                <button type="button" class="btn btn-primary btn-save-verif-berkas" data-pd-id="${id}" data-key="${item.jenis_berkas}" data-label="${escapeHtml(item.label)}" style="padding: 5px 14px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; border-radius: var(--radius-sm, 6px); display: inline-flex; align-items: center; gap: 6px;">
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

            const btnLihatSemua = document.getElementById('btnLihatSemuaBerkas');
            if (btnLihatSemua) {
                btnLihatSemua.addEventListener('click', () => window.openSemuaBerkas());
            }

            // -------- Stepper: satu dokumen terunggah per tampilan, urut, navigasi ikon saja.
            // Dokumen tanpa berkas (belum unggah) dilewati otomatis. Bila TIDAK ADA berkas
            // sama sekali → tutup modal & selesai (tak ada yang bisa divalidasi).
            const allCards = Array.from(content.querySelectorAll('.card-berkas-item'));
            const vbCards = allCards.filter((c) => c.getAttribute('data-uploaded') === '1');
            allCards.forEach((c) => { if (c.getAttribute('data-uploaded') !== '1') c.style.display = 'none'; });

            if (!vbCards.length) {
                modal.style.display = 'none';
                document.body.style.overflow = '';
                window.refreshLiveTable ? window.refreshLiveTable() : location.reload();
                return;
            }

            let vbIndex = 0;
            const vbShow = (i) => {
                vbIndex = Math.max(0, Math.min(i, vbCards.length - 1));
                vbCards.forEach((c, idx) => { c.style.display = idx === vbIndex ? 'grid' : 'none'; });
                const counter = document.getElementById('vbStepCounter');
                const labelEl = document.getElementById('vbStepLabel');
                const prevBtn = document.getElementById('vbPrev');
                const nextBtn = document.getElementById('vbNext');
                if (counter) counter.textContent = `Dokumen ${vbIndex + 1} / ${vbCards.length}`;
                if (labelEl) labelEl.textContent = vbCards[vbIndex].getAttribute('data-label') || '';
                if (prevBtn) prevBtn.disabled = vbIndex === 0;
                const onLast = vbIndex === vbCards.length - 1;
                if (nextBtn) {
                    nextBtn.disabled = onLast;
                    // Dokumen terakhir: tombol kanan jadi "Selesai" (tutup + refresh).
                    nextBtn.title = onLast ? 'Selesai & tutup' : 'Dokumen selanjutnya';
                    nextBtn.innerHTML = onLast ? '<i class="fas fa-check"></i>' : '<i class="fas fa-chevron-right"></i>';
                }
                content.scrollTop = 0;
            };
            const vbFinish = () => {
                berkasStatusChanged = false; // sudah ditangani di sini; cegah refresh ganda dari observer
                modal.style.display = 'none';
                document.body.style.overflow = '';
                window.refreshLiveTable ? window.refreshLiveTable() : location.reload();
            };
            const vbPrev = document.getElementById('vbPrev');
            const vbNext = document.getElementById('vbNext');
            if (vbPrev) vbPrev.addEventListener('click', () => vbShow(vbIndex - 1));
            if (vbNext) vbNext.addEventListener('click', () => {
                if (vbIndex === vbCards.length - 1) vbFinish();
                else vbShow(vbIndex + 1);
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

            content.querySelectorAll('.input-file-tendik').forEach((fileInput) => {
                fileInput.addEventListener('change', async function () {
                    const file = this.files[0];
                    if (!file) return;
                    const pdId = this.getAttribute('data-pd-id');
                    const key = this.getAttribute('data-key');

                    const formData = new FormData();
                    formData.append('jenis_berkas', key);
                    formData.append('file', file);

                    Swal.fire({ title: 'Mengunggah Berkas...', text: 'File sedang diproses dan otomatis diverifikasi VALID.', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
                    try {
                        const upRes = await fetch(`/dashboard/verval-pd/berkas/upload/${encodeURIComponent(pdId)}`, {
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                            body: formData,
                        });
                        const upJson = await upRes.json();
                        if (upRes.ok && upJson.status === 'success') {
                            berkasStatusChanged = true;
                            Swal.fire({ icon: 'success', title: 'Berhasil Diunggah!', text: upJson.message, timer: 1800, showConfirmButton: false });
                            window.openModalVerifikasiBerkas(pdId, siswa.nama);
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
                    const pdId = this.getAttribute('data-pd-id');
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
                        const postRes = await fetch(`/dashboard/verval-pd/berkas/verifikasi/${encodeURIComponent(pdId)}`, {
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': csrf(), 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                            body: JSON.stringify({ jenis_berkas: key, status, catatan_penolakan: catatan, rekomendasi_penolakan: catatan }),
                        });
                        const postJson = await postRes.json();
                        this.disabled = false;
                        this.innerHTML = originalBtnText;

                        if (postRes.ok && postJson.status === 'success') {
                            berkasStatusChanged = true;
                            Swal.fire({ icon: 'success', title: 'Berhasil!', text: postJson.message, timer: 1500, showConfirmButton: false });
                            if (card) {
                                const badgeContainer = card.querySelector('.status-badge-container');
                                if (badgeContainer) {
                                    badgeContainer.innerHTML = status === 'valid'
                                        ? `<span class="badge badge-success" style="font-size: var(--text-xs, 0.75rem); padding: 3px 8px;"><i class="fas fa-circle-check me-1"></i> Valid</span>`
                                        : `<span class="badge badge-danger" style="font-size: var(--text-xs, 0.75rem); padding: 3px 8px;"><i class="fas fa-circle-xmark me-1"></i> Tidak Valid</span>`;
                                }
                                card.setAttribute('data-selected-status', status);
                                const currentRadio = card.querySelector(`input[name="status_${key}"][value="${status}"]`);
                                if (currentRadio) currentRadio.checked = true;
                                const prevBtn = card.querySelector('.btn-preview-pdf-inline');
                                if (prevBtn) { prevBtn.setAttribute('data-status', status); prevBtn.setAttribute('data-catatan', catatan); }

                                // Simpan cepat: lanjut ke dokumen berikutnya; bila ini terakhir → selesai & tutup.
                                const curIdx = vbCards.indexOf(card);
                                if (curIdx > -1) {
                                    if (curIdx < vbCards.length - 1) setTimeout(() => vbShow(curIdx + 1), 350);
                                    else setTimeout(() => vbFinish(), 600);
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

    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-validasi-berkas-detail');
        if (btn) {
            e.preventDefault();
            window.openModalVerifikasiBerkas(btn.getAttribute('data-id'), btn.getAttribute('data-nama'));
        }
    });

    // Lihat SEMUA berkas sekaligus dalam satu grid — sekali buka, tanpa tutup-buka per file.
    window.openSemuaBerkas = function () {
        const modal = document.getElementById('modalSemuaBerkas');
        const grid = document.getElementById('semuaBerkasGrid');
        const titleEl = document.getElementById('semuaBerkasTitle');
        const subEl = document.getElementById('semuaBerkasSubtitle');
        if (!modal || !grid) return;

        const data = window.__berkasAktif || { siswa: {}, items: [] };
        const siswa = data.siswa || {};
        const items = (data.items || []).filter((it) => it.is_uploaded);

        if (titleEl) titleEl.textContent = `Semua Berkas: ${siswa.nama || 'Peserta Didik'}`;
        if (subEl) subEl.innerHTML = `${items.length} dokumen &bull; NISN: <strong>${escapeHtml(siswa.nisn || '-')}</strong> &bull; Rombel: <strong>${escapeHtml(siswa.rombel || '-')}</strong>`;

        if (!items.length) {
            grid.innerHTML = `<div style="grid-column: 1/-1; text-align: center; padding: 60px; color: var(--text-muted);"><i class="fas fa-folder-open mb-2" style="font-size: var(--text-3xl, 1.875rem); opacity: 0.5;"></i><div>Belum ada berkas yang diunggah.</div></div>`;
        } else {
            grid.innerHTML = items.map((item) => {
                const badge = item.status === 'valid'
                    ? `<span class="badge badge-success" style="font-size: var(--text-xs, 0.75rem); padding: 2px 8px;"><i class="fas fa-circle-check me-1"></i> Valid</span>`
                    : (item.status === 'tidak_valid'
                        ? `<span class="badge badge-danger" style="font-size: var(--text-xs, 0.75rem); padding: 2px 8px;"><i class="fas fa-circle-xmark me-1"></i> Tidak Valid</span>`
                        : `<span class="badge badge-warning" style="font-size: var(--text-xs, 0.75rem); padding: 2px 8px;"><i class="fas fa-clock me-1"></i> Menunggu</span>`);
                return `
                    <div class="card" style="display: flex; flex-direction: column; padding: 0; overflow: hidden; border: 1px solid var(--border-color); border-radius: var(--radius-lg, 12px); background: var(--card-bg, #fff);">
                        <div style="display: flex; align-items: center; justify-content: space-between; gap: 8px; padding: 10px 14px; border-bottom: 1px solid var(--border-color); background: var(--bg-hover);">
                            <span style="display: flex; align-items: center; gap: 8px; font-weight: 700; font-size: var(--text-sm, 0.8125rem); color: var(--text-color); min-width: 0;">
                                <i class="fas ${item.icon || 'fa-file-pdf'}" style="color: var(--primary);"></i>
                                <span style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">${escapeHtml(item.label)}</span>
                            </span>
                            ${badge}
                        </div>
                        <iframe src="${item.file_url}" title="${escapeHtml(item.label)}" loading="lazy"
                            style="width: 100%; height: 480px; border: none; background: var(--text-main, #0f172a);"></iframe>
                    </div>
                `;
            }).join('');
        }

        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    };

    window.closeSemuaBerkas = function () {
        const modal = document.getElementById('modalSemuaBerkas');
        const grid = document.getElementById('semuaBerkasGrid');
        if (modal) modal.style.display = 'none';
        if (grid) grid.innerHTML = '';
        const modalVb = document.getElementById('modalVerifikasiBerkasDigital');
        if (!modalVb || modalVb.style.display === 'none') document.body.style.overflow = '';
    };

    const modalSemuaEl = document.getElementById('modalSemuaBerkas');
    if (modalSemuaEl) {
        modalSemuaEl.addEventListener('click', function (e) {
            if (e.target === modalSemuaEl) window.closeSemuaBerkas();
        });
        const tutupBtn = document.getElementById('btnTutupSemuaBerkas');
        if (tutupBtn) tutupBtn.addEventListener('click', () => window.closeSemuaBerkas());
    }

    // Delegasi: klik sel Konfirmasi (khusus admin) → ubah Sesuai <-> Perlu Perbaikan (tercatat riwayat).
    const konfMeta = {
        sesuai: ['Sesuai', 'fa-circle-check', 'success'],
        perlu_perbaikan: ['Perlu Perbaikan', 'fa-triangle-exclamation', 'danger'],
        belum_konfirmasi: ['Belum Dikonfirmasi', 'fa-circle-question', 'outline'],
    };
    document.addEventListener('click', async function (e) {
        const btn = e.target.closest('.berkas-konfirmasi-btn');
        if (!btn) return;
        e.preventDefault();

        const id = btn.getAttribute('data-id');
        const nama = btn.getAttribute('data-nama') || 'Peserta Didik';
        const current = btn.getAttribute('data-status') || 'belum_konfirmasi';
        const next = current === 'sesuai' ? 'perlu_perbaikan' : 'sesuai';
        const nextLabel = next === 'sesuai' ? 'Sesuai' : 'Perlu Perbaikan';

        const ok = await window.SAE.confirm(
            `Ubah status konfirmasi data <b>${escapeHtml(nama)}</b> menjadi <b>${nextLabel}</b>?`,
            'Ubah Status Konfirmasi?', next === 'sesuai' ? 'info' : 'warning', 'Ya, Ubah', 'Batal'
        );
        if (!ok) return;

        btn.disabled = true;
        try {
            const res = await fetch(`/dashboard/verval-pd/berkas/${encodeURIComponent(id)}/konfirmasi`, {
                method: 'PATCH',
                headers: { 'X-CSRF-TOKEN': csrf(), 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ status: next }),
            });
            const json = await res.json();
            if (res.ok && json.status === 'success') {
                const applied = json.status_konfirmasi || next;
                const meta = konfMeta[applied] || konfMeta.belum_konfirmasi;
                btn.setAttribute('data-status', applied);
                btn.innerHTML = `<span class="badge badge-${meta[2]}" style="font-size: var(--text-xs, 0.75rem); padding: 3px 8px;"><i class="fas ${meta[1]}"></i> ${meta[0]}</span>`;
                window.SAE.toast ? window.SAE.toast(json.message || 'Status diperbarui.', 'success') : null;
            } else {
                Swal.fire('Gagal', json.message || 'Perubahan tidak tersimpan.', 'error');
            }
        } catch (err) {
            Swal.fire('Error', 'Gagal menghubungi server.', 'error');
        } finally {
            btn.disabled = false;
        }
    });

    // ==================================================== USULAN PERUBAHAN
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-kelola-usulan-modal, .btn-verif-usulan');
        if (btn) {
            e.preventDefault();
            const id = btn.getAttribute('data-id');
            if (id && typeof window.openModalPengelolaanUsulan === 'function') window.openModalPengelolaanUsulan(id);
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
            const res = await fetch(`/dashboard/verval-pd/perubahan/detail/${encodeURIComponent(id)}`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            const json = await res.json();
            if (json.status !== 'success' || !json.data) throw new Error(json.message || 'Gagal memuat rincian usulan.');

            const data = json.data;
            const items = data.items || [];
            const pdId = data.peserta_didik_id;

            modal.setAttribute('data-pd-id', pdId);
            modal.setAttribute('data-usulan-id', data.id || id);

            if (namaEl) namaEl.textContent = data.nama_siswa || '-';
            if (subtitleEl) subtitleEl.innerHTML = `NISN: <strong>${data.nisn || '-'}</strong> &bull; Rombel: <strong>${data.rombel || '-'}</strong> &bull; Total Pengajuan: <strong>${items.length} Data</strong>`;

            const fotoImg = document.getElementById('modalUsulanFoto');
            const fotoIcon = document.getElementById('modalUsulanFotoDefault');
            if (fotoImg && fotoIcon) {
                if (data.foto_url) { fotoImg.src = data.foto_url; fotoImg.style.display = 'block'; fotoIcon.style.display = 'none'; }
                else { fotoImg.style.display = 'none'; fotoIcon.style.display = 'flex'; }
            }

            const setDoc = (prefix, docData) => {
                const statusBadge = document.getElementById(`${prefix}StatusBadge`);
                const previewBtn = document.getElementById(`${prefix}PreviewBtn`);
                if (!statusBadge || !previewBtn) return;
                if (docData && docData.exists) {
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
                    previewBtn.setAttribute('data-url', '');
                    previewBtn.disabled = true;
                    previewBtn.style.opacity = '0.5';
                    statusBadge.innerHTML = '<span style="display:inline-flex; align-items:center; gap:4px; padding:2px 8px; border-radius:999px; font-size:0.7rem; font-weight:700; background:rgba(148,163,184,0.15); color:var(--text-muted);"><i class="fas fa-minus-circle"></i> Tidak Ada</span>';
                }
            };
            setDoc('docKk', data.kk);
            setDoc('docIjazah', data.ijazah);
            setDoc('docAkta', data.akta);

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
                        </div>`;
                }
                const filteredAlasan = distinctAlasan.filter(a => !distinctCatatan.includes(a));
                if (filteredAlasan.length > 0) {
                    boxHtml += `
                        <div style="display:flex; align-items:flex-start; gap:8px; background:rgba(99,102,241,0.08); border:1px solid rgba(99,102,241,0.25); color:var(--text-color); padding:8px 12px; border-radius:var(--radius-md, 8px); font-size:var(--text-xs, 0.75rem); line-height:1.4;">
                            <i class="fas fa-circle-info" style="color:var(--primary); margin-top:2px; flex-shrink:0;"></i>
                            <div><strong style="color:var(--primary);">Alasan Pengajuan:</strong> ${escapeHtml(filteredAlasan.join(' | '))}</div>
                        </div>`;
                }
                alasanBox.innerHTML = boxHtml;
                alasanBox.style.display = boxHtml ? 'block' : 'none';
            }

            const formatVal = (val) => {
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
                        if (item.status === 'menunggu') {
                            statusHtml = `
                                <div class="usulan-decision-group" data-usulan-id="${item.id}" data-decision="" style="display:inline-flex; align-items:center; gap:8px;">
                                    <button type="button" class="usulan-decision" data-decision="disetujui" title="Setujui" style="width:28px; height:28px; border-radius:50%; border:1.5px solid rgba(16,185,129,0.4); background:transparent; color:#10b981; cursor:pointer; display:inline-flex; align-items:center; justify-content:center;"><i class="fas fa-check"></i></button>
                                    <button type="button" class="usulan-decision" data-decision="ditolak" title="Tolak / Tidak Sesuai" style="width:28px; height:28px; border-radius:50%; border:1.5px solid rgba(239,68,68,0.4); background:transparent; color:#ef4444; cursor:pointer; display:inline-flex; align-items:center; justify-content:center;"><i class="fas fa-xmark"></i></button>
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

                        const valLama = formatVal(item.nilai_lama);
                        const valBaru = formatVal(item.nilai_baru);
                        const valBaruHtml = valBaru.raw
                            ? `<span class="copyable" data-copy="${escapeHtml(valBaru.raw)}" data-label="${escapeHtml(item.kolom_label)}" title="Klik untuk salin" style="font-weight:700; color:var(--success, #10b981); font-size:0.86rem; word-break:break-word; cursor:pointer;">${valBaru.html}</span>`
                            : valBaru.html;

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
                                <div style="color:var(--text-muted); font-size:0.84rem; word-break:break-word;">${valLama.html}</div>
                            </td>
                            <td style="padding:10px 14px; vertical-align:middle; background:rgba(16,185,129,0.03);">
                                <div>${valBaruHtml}</div>
                            </td>
                            <td style="padding:10px 14px; vertical-align:middle; text-align:center;">${statusHtml}</td>`;
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
                    chip.style.cssText = 'padding:4px 10px; font-size:0.74rem; border-radius:999px; border:1px solid var(--border-color); background:var(--bg-card); color:var(--text-muted); cursor:pointer; text-align:left; line-height:1.3;';
                    chip.textContent = alasan;
                    chip.addEventListener('click', () => { if (catatanInp) { catatanInp.value = alasan; catatanInp.focus(); } });
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

            const bulkSelect = (decision) => {
                document.querySelectorAll('#modalUsulanTableBody .usulan-decision-group').forEach((group) => applyDecision(group, decision));
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
    document.querySelectorAll('.close-modal-pengelolaan-usulan').forEach((btn) => btn.addEventListener('click', window.closeModalPengelolaanUsulan));

    document.addEventListener('click', function (e) {
        const previewBtn = e.target.closest('.btn-preview-lampiran-usulan, #docKkPreviewBtn, #docIjazahPreviewBtn, #docAktaPreviewBtn');
        if (previewBtn) {
            e.preventDefault();
            const url = previewBtn.getAttribute('data-url');
            if (url && typeof window.openPdfViewer === 'function') window.openPdfViewer(url, 'Pratinjau Dokumen Pendukung', null, null, null, null, null);
        }
    });

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
                if (decision === 'disetujui' || decision === 'ditolak') items.push({ id, status: decision });
                else belumDipilih.push(id);
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
                icon: 'question', showCancelButton: true, confirmButtonText: 'Ya, Simpan', cancelButtonText: 'Batal',
            });
            if (!confirm.isConfirmed) return;

            btnSimpan.disabled = true;
            btnSimpan.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            try {
                const res = await fetch(`/dashboard/verval-pd/perubahan/${encodeURIComponent(pdId)}/verifikasi`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf() },
                    body: JSON.stringify({ items, catatan_verifikasi: catatan }),
                });
                const json = await res.json();
                if (json.status !== 'success') throw new Error(json.message || 'Gagal menyimpan verifikasi.');
                Swal.fire({ title: 'Tersimpan', text: json.message, icon: 'success', timer: 1600, showConfirmButton: false });
                window.closeModalPengelolaanUsulan();
                window.refreshLiveTable ? window.refreshLiveTable() : location.reload();
            } catch (err) {
                Swal.fire('Gagal', err.message, 'error');
            } finally {
                btnSimpan.disabled = false;
                btnSimpan.innerHTML = '<i class="fas fa-save"></i>';
            }
        });
    }

    async function markDapodik(pdId, btn) {
        if (!pdId) return;
        const ok = await window.SAE.confirm(
            'Status usulan ini akan ditandai telah selesai di-input / disinkronkan ke aplikasi Dapodik.',
            'Tandai Selesai di Dapodik?', 'info', 'Ya, Tandai Selesai', 'Batal'
        );
        if (!ok) return;

        const original = btn ? btn.innerHTML : '';
        if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>'; }
        try {
            const res = await fetch(`/dashboard/verval-pd/perubahan/${encodeURIComponent(pdId)}/dapodik`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf() },
            });
            const json = await res.json();
            if (json.status !== 'success') throw new Error(json.message || 'Gagal menandai status.');
            Swal.fire({ title: 'Sinkronisasi Selesai', text: json.message, icon: 'success', timer: 1600, showConfirmButton: false });
            window.closeModalPengelolaanUsulan();
            window.refreshLiveTable ? window.refreshLiveTable() : location.reload();
        } catch (err) {
            Swal.fire('Gagal', err.message, 'error');
        } finally {
            if (btn) { btn.disabled = false; btn.innerHTML = original; }
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
        if (b) { e.preventDefault(); markDapodik(b.getAttribute('data-id'), b); }
    });

    // ====================================================== PDF VIEWER
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
                    catatanInput.value = document.getElementById(`textarea_${key}`)?.value?.trim() || catatan || '';
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
        window.focus();
        const modalVb = document.getElementById('modalVerifikasiBerkasDigital');
        if (!modalVb || modalVb.style.display === 'none') document.body.style.overflow = '';
        else if (closedKey) document.getElementById(`cardBerkas_${closedKey}`)?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    };

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
                    if (cardRadio) { cardRadio.checked = true; cardRadio.dispatchEvent(new Event('change', { bubbles: true })); }
                }
            }
        }
    });

    document.addEventListener('click', async function (e) {
        const btnSimpan = e.target.closest('#btnPdfViewerSimpan');
        if (btnSimpan && activePdfViewerData) {
            e.preventDefault();
            const { pdId, key } = activePdfViewerData;
            const selectedRadio = document.querySelector('input[name="pdf_viewer_status"]:checked');
            if (!selectedRadio) { Swal.fire('Peringatan', 'Silakan pilih status validasi (Valid atau Tidak Valid).', 'warning'); return; }
            const status = selectedRadio.value;
            const catatanInput = document.getElementById('pdfViewerCatatan');
            const catatan = catatanInput ? catatanInput.value.trim() : '';
            if (status === 'tidak_valid' && !catatan) {
                Swal.fire('Wajib Catatan', 'Untuk status Tidak Valid, Anda wajib mengisi catatan penolakan.', 'warning');
                if (catatanInput) catatanInput.focus();
                return;
            }

            const origText = btnSimpan.innerHTML;
            btnSimpan.disabled = true;
            btnSimpan.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';
            try {
                const postRes = await fetch(`/dashboard/verval-pd/berkas/verifikasi/${encodeURIComponent(pdId)}`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrf(), 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    body: JSON.stringify({ jenis_berkas: key, status, catatan_penolakan: catatan, rekomendasi_penolakan: catatan }),
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
                                ? `<span class="badge badge-success" style="font-size: var(--text-xs, 0.75rem); padding: 4px 10px;"><i class="fas fa-circle-check me-1"></i> Valid / Sesuai</span>`
                                : `<span class="badge badge-danger" style="font-size: var(--text-xs, 0.75rem); padding: 4px 10px;"><i class="fas fa-circle-xmark me-1"></i> Tidak Valid</span>`;
                        }
                        card.setAttribute('data-selected-status', status);
                        const cardRadio = card.querySelector(`input[name="status_${key}"][value="${status}"]`);
                        if (cardRadio) { cardRadio.checked = true; cardRadio.dispatchEvent(new Event('change', { bubbles: true })); }
                        const cardTextarea = document.getElementById(`textarea_${key}`);
                        if (cardTextarea && catatan) cardTextarea.value = catatan;
                    }
                    window.closePdfViewer();
                    Swal.fire({ icon: 'success', title: 'Berhasil!', text: postJson.message, timer: 1500, showConfirmButton: false });
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
            if (url) {
                window.openPdfViewer(url, btn.getAttribute('data-title'), btn.getAttribute('data-pd-id'), btn.getAttribute('data-key'), btn.getAttribute('data-label'), btn.getAttribute('data-status'), btn.getAttribute('data-catatan'));
            }
        }
        const closeBtn = e.target.closest('.close-pdf-viewer');
        if (closeBtn) { e.preventDefault(); e.stopPropagation(); window.closePdfViewer(); }
    });
});
