/**
 * Modul JavaScript: E-Learning — Materi, Tugas, Pengumpulan & Penilaian.
 * Interaksi terpisah dari Blade. Live search/filter memakai window.refreshLiveTable.
 */
document.addEventListener('DOMContentLoaded', () => {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    const showToast = (message, type = 'success') => {
        if (window.SAE && typeof window.SAE.toast === 'function') {
            window.SAE.toast(message, type);
        } else if (typeof Swal !== 'undefined') {
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

    const confirmAction = async (text, title = 'Konfirmasi?') => {
        if (window.SAE && typeof window.SAE.confirm === 'function') {
            return await window.SAE.confirm(text, title, 'warning');
        }
        if (typeof Swal !== 'undefined') {
            const r = await Swal.fire({
                title,
                text,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Ya, Lanjutkan!',
                cancelButtonText: 'Batal'
            });
            return r.isConfirmed;
        }
        return window.confirm(text);
    };

    const openModal = (el) => { if (el) el.style.display = 'flex'; };
    const closeModal = (el) => { if (el) el.style.display = 'none'; };
    const bindBackdrop = (el) => {
        if (el) el.addEventListener('click', (e) => { if (e.target === el) closeModal(el); });
    };

    const jsonFetch = async (url, options = {}) => {
        const res = await fetch(url, {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken, ...(options.headers || {}) },
            ...options
        });
        const data = await res.json().catch(() => ({}));
        return { ok: res.ok, data };
    };

    const reloadSoon = () => setTimeout(() => window.location.reload(), 600);

    /* ============================ MATERI ============================ */
    const modalForm = document.getElementById('modalFormItem');
    if (modalForm) {
        const mainForm = document.getElementById('mainFormItem');
        const modalTitle = document.getElementById('modalTitle');
        const inputPembelajaran = document.getElementById('inputPembelajaran');
        const inputJudul = document.getElementById('inputJudul');
        const inputTipe = document.getElementById('inputTipe');
        const inputStatus = document.getElementById('inputStatus');
        const inputUrl = document.getElementById('inputUrl');
        const inputFile = document.getElementById('inputFile');
        const inputDeskripsi = document.getElementById('inputDeskripsi');
        const wrapUrl = document.getElementById('wrapUrl');
        const wrapFile = document.getElementById('wrapFile');
        let editingId = null;

        /* ---------- Editor kaya (contenteditable → isi_html) ---------- */
        const editorContent = document.getElementById('materiEditorContent');
        const inputIsiHtml = document.getElementById('inputIsiHtml');

        document.querySelectorAll('#materiEditorToolbar .tb-btn[data-command]').forEach((btn) => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                document.execCommand(btn.dataset.command, false, btn.dataset.val || null);
                editorContent?.focus();
                syncEditor();
            });
        });
        document.getElementById('btnMateriInsertLink')?.addEventListener('click', (e) => {
            e.preventDefault();
            const url = prompt('Masukkan tautan URL (contoh: https://sekolah.sch.id):');
            if (url) document.execCommand('createLink', false, url);
            editorContent?.focus();
            syncEditor();
        });
        const syncEditor = () => {
            if (editorContent && inputIsiHtml) inputIsiHtml.value = editorContent.innerHTML.trim();
        };
        editorContent?.addEventListener('input', syncEditor);
        editorContent?.addEventListener('blur', syncEditor);

        /* ---------- Builder soal kuis ---------- */
        const kuisContainer = document.getElementById('kuisSoalContainer');
        const kuisKosong = document.getElementById('kuisKosong');
        const huruf = (i) => String.fromCharCode(65 + i);
        const esc = (s) => String(s ?? '').replace(/"/g, '&quot;').replace(/</g, '&lt;');

        const refreshKuisKosong = () => {
            if (kuisKosong) kuisKosong.style.display = kuisContainer && kuisContainer.children.length ? 'none' : '';
        };

        const renderKunciSelect = (wrap) => {
            const tipe = wrap.querySelector('.kuis-tipe')?.value || 'pg';
            const sel = wrap.querySelector('.kuis-kunci');
            const opsiInputs = [...wrap.querySelectorAll('.kuis-opsi-input')];
            if (!sel) return;
            const current = sel.value;
            if (tipe === 'pg') {
                sel.innerHTML = '<option value="">— Pilih kunci —</option>';
                opsiInputs.forEach((inp, i) => {
                    const val = inp.value.trim();
                    if (val === '') return;
                    const o = document.createElement('option');
                    o.value = val;
                    o.textContent = `${huruf(i)}. ${val}`;
                    sel.appendChild(o);
                });
            } else if (tipe === 'bs') {
                sel.innerHTML = '<option value="">— Pilih kunci —</option><option value="benar">Benar</option><option value="salah">Salah</option>';
            } else {
                sel.innerHTML = '<option value="">— Kunci (teks) —</option>';
            }
            if ([...sel.options].some((o) => o.value === current)) sel.value = current;
        };

        const renderOpsiRows = (wrap, values) => {
            const box = wrap.querySelector('.kuis-opsi-box');
            if (!box) return;
            box.innerHTML = '';
            (values.length ? values : ['', '']).forEach((val, i) => {
                const row = document.createElement('div');
                row.style.cssText = 'display:flex;gap:6px;align-items:center;';
                row.innerHTML = `<span style="font-size:var(--text-xs);font-weight:700;color:var(--text-muted);width:16px;">${huruf(i)}</span>
                    <input type="text" class="kuis-opsi-input" maxlength="500" value="${esc(val)}" placeholder="Teks opsi"
                        style="flex:1;height:34px;padding:0 10px;border:1px solid var(--border-color);background:var(--bg-card);color:var(--text-main);border-radius:var(--radius-md);font-size:var(--text-sm);box-sizing:border-box;">`;
                box.appendChild(row);
                row.querySelector('.kuis-opsi-input')?.addEventListener('input', () => renderKunciSelect(wrap));
            });
        };

        const addSoalRow = (data = null) => {
            if (!kuisContainer) return;
            const d = data || { tipe: 'pg', pertanyaan: '', opsi: ['', ''], kunci: '', bobot: 1 };
            const wrap = document.createElement('div');
            wrap.className = 'kuis-soal-item';
            wrap.style.cssText = 'border:1px solid var(--border-color);border-radius:var(--radius-md);padding:var(--space-4);background:rgba(255,255,255,0.02);';
            wrap.innerHTML = `
                <div style="display:flex;gap:var(--space-3);align-items:center;margin-bottom:6px;">
                    <select class="kuis-tipe" style="height:32px;padding:0 8px;border:1px solid var(--border-color);background:var(--bg-card);color:var(--text-main);border-radius:var(--radius-sm);font-size:var(--text-xs);">
                        <option value="pg">Pilihan Ganda</option>
                        <option value="bs">Benar / Salah</option>
                        <option value="isian">Isian</option>
                    </select>
                    <input type="number" class="kuis-bobot" min="1" max="100" value="${d.bobot || 1}" title="Bobot"
                        style="width:70px;height:32px;padding:0 8px;border:1px solid var(--border-color);background:var(--bg-card);color:var(--text-main);border-radius:var(--radius-sm);font-size:var(--text-xs);">
                    <button type="button" class="kuis-hapus" title="Hapus soal" style="margin-left:auto;background:none;border:none;color:var(--danger);cursor:pointer;"><i class="fas fa-trash-can"></i></button>
                </div>
                <input type="text" class="kuis-pertanyaan" maxlength="2000" value="${esc(d.pertanyaan)}" placeholder="Pertanyaan..."
                    style="width:100%;height:34px;padding:0 10px;border:1px solid var(--border-color);background:var(--bg-card);color:var(--text-main);border-radius:var(--radius-md);font-size:var(--text-sm);box-sizing:border-box;margin-bottom:6px;">
                <div class="kuis-opsi-box" style="display:flex;flex-direction:column;gap:4px;margin-bottom:6px;"></div>
                <div style="display:flex;gap:6px;align-items:center;">
                    <button type="button" class="kuis-tambah-opsi" style="background:none;border:1px dashed var(--border-color);color:var(--primary);border-radius:var(--radius-sm);font-size:var(--text-2xs);padding:3px 8px;cursor:pointer;">+ opsi</button>
                    <span style="font-size:var(--text-2xs);color:var(--text-muted);">Kunci:</span>
                    <select class="kuis-kunci" style="flex:1;height:32px;padding:0 8px;border:1px solid var(--border-color);background:var(--bg-card);color:var(--text-main);border-radius:var(--radius-sm);font-size:var(--text-xs);"></select>
                </div>`;
            kuisContainer.appendChild(wrap);

            const tipeSel = wrap.querySelector('.kuis-tipe');
            const kunciSel = wrap.querySelector('.kuis-kunci');
            const opsiBox = wrap.querySelector('.kuis-opsi-box');

            tipeSel.value = d.tipe || 'pg';
            renderOpsiRows(wrap, Array.isArray(d.opsi) && d.opsi.length ? d.opsi : ['', '']);

            const applyTipe = () => {
                const tipe = tipeSel.value;
                opsiBox.style.display = tipe === 'pg' ? '' : 'none';
                wrap.querySelector('.kuis-tambah-opsi').style.display = tipe === 'pg' ? '' : 'none';
                renderKunciSelect(wrap);
                if (tipe === 'isian') { kunciSel.style.display = ''; }
                else { kunciSel.style.display = ''; }
                if (d.kunci) kunciSel.value = d.kunci;
            };
            tipeSel.addEventListener('change', applyTipe);
            kunciSel.addEventListener('change', () => { d.kunci = kunciSel.value; });
            wrap.querySelector('.kuis-tambah-opsi')?.addEventListener('click', () => {
                const cur = [...wrap.querySelectorAll('.kuis-opsi-input')].map((i) => i.value);
                renderOpsiRows(wrap, [...cur, '']);
                renderKunciSelect(wrap);
            });
            wrap.querySelector('.kuis-hapus')?.addEventListener('click', () => { wrap.remove(); refreshKuisKosong(); });
            applyTipe();
            if (d.kunci) kunciSel.value = d.kunci;
            refreshKuisKosong();
        };

        document.getElementById('btnTambahSoalKuis')?.addEventListener('click', () => addSoalRow());

        const collectSoal = () => {
            if (!kuisContainer) return [];
            return [...kuisContainer.querySelectorAll('.kuis-soal-item')].map((wrap) => {
                const tipe = wrap.querySelector('.kuis-tipe').value;
                const opsi = [...wrap.querySelectorAll('.kuis-opsi-input')].map((i) => i.value.trim()).filter((v) => v !== '');
                return {
                    tipe,
                    pertanyaan: wrap.querySelector('.kuis-pertanyaan').value.trim(),
                    opsi: tipe === 'pg' ? opsi : [],
                    kunci: wrap.querySelector('.kuis-kunci').value.trim(),
                    bobot: parseInt(wrap.querySelector('.kuis-bobot').value, 10) || 1,
                };
            }).filter((s) => s.pertanyaan !== '');
        };

        /* ---------- Tipe materi & reset ---------- */
        const toggleTipe = () => {
            const tipe = inputTipe?.value || 'teks';
            if (wrapUrl) wrapUrl.style.display = tipe === 'link' ? '' : 'none';
            if (wrapFile) wrapFile.style.display = tipe === 'file' ? '' : 'none';
        };
        if (inputTipe) inputTipe.addEventListener('change', toggleTipe);

        const resetForm = () => {
            editingId = null;
            if (mainForm) mainForm.reset();
            if (editorContent) editorContent.innerHTML = '';
            if (inputIsiHtml) inputIsiHtml.value = '';
            if (kuisContainer) kuisContainer.innerHTML = '';
            refreshKuisKosong();
            if (modalTitle) modalTitle.innerHTML = '<i class="fas fa-plus-circle" style="color: var(--primary);"></i> Tambah Materi';
            toggleTipe();
        };

        document.getElementById('btnOpenCreateModal')?.addEventListener('click', () => {
            resetForm();
            openModal(modalForm);
            inputJudul?.focus();
        });
        document.getElementById('btnCloseModal')?.addEventListener('click', () => closeModal(modalForm));
        document.getElementById('btnCancelModal')?.addEventListener('click', () => closeModal(modalForm));
        bindBackdrop(modalForm);

        document.addEventListener('click', async (e) => {
            const editBtn = e.target.closest('.btn-edit-row');
            if (!editBtn) return;
            try {
                const { data } = await jsonFetch(`/dashboard/elearning/${editBtn.dataset.id}`);
                if (data.status !== 'success' || !data.data) return showToast(data.message || 'Gagal memuat materi.', 'danger');
                const d = data.data;
                editingId = d.id;
                if (inputPembelajaran) inputPembelajaran.value = d.pembelajaran_id || '';
                if (inputJudul) inputJudul.value = d.judul || '';
                if (inputTipe) inputTipe.value = d.tipe || 'teks';
                if (inputStatus) inputStatus.value = d.status || 'draft';
                if (inputUrl) inputUrl.value = d.url_embed || '';
                if (inputDeskripsi) inputDeskripsi.value = d.deskripsi || '';
                if (inputFile) inputFile.value = '';
                if (editorContent) editorContent.innerHTML = d.isi_html || '';
                if (inputIsiHtml) inputIsiHtml.value = d.isi_html || '';
                if (kuisContainer) {
                    kuisContainer.innerHTML = '';
                    (d.soal || []).forEach((s) => addSoalRow(s));
                    refreshKuisKosong();
                }
                if (modalTitle) modalTitle.innerHTML = '<i class="fas fa-pen-to-square" style="color: var(--primary);"></i> Ubah Materi';
                toggleTipe();
                openModal(modalForm);
            } catch (err) {
                showToast('Terjadi kesalahan koneksi server.', 'danger');
            }
        });

        mainForm?.addEventListener('submit', async (e) => {
            e.preventDefault();
            syncEditor();
            const fd = new FormData(mainForm);
            fd.set('soal', JSON.stringify(collectSoal()));
            let url = mainForm.dataset.store;
            let method = 'POST';
            if (editingId) { url = `/dashboard/elearning/${editingId}`; method = 'PUT'; fd.set('id', editingId); }
            const btnSave = document.getElementById('btnSaveModal');
            if (btnSave) btnSave.disabled = true;
            try {
                const { ok, data } = await jsonFetch(url, { method, body: fd });
                if (ok && data.status === 'success') {
                    showToast(data.message || 'Materi berhasil disimpan.');
                    closeModal(modalForm);
                    reloadSoon();
                } else {
                    showToast(data.message || 'Gagal menyimpan materi.', 'danger');
                }
            } catch (err) {
                showToast('Terjadi kesalahan koneksi server.', 'danger');
            } finally {
                if (btnSave) btnSave.disabled = false;
            }
        });

        document.addEventListener('click', async (e) => {
            const delBtn = e.target.closest('.btn-delete-row');
            if (!delBtn) return;
            const name = delBtn.dataset.name || 'materi ini';
            const ok = await confirmAction(`Yakin ingin menghapus "${name}"? Tindakan ini tidak dapat dibatalkan.`, 'Hapus Materi?');
            if (!ok) return;
            try {
                const { data } = await jsonFetch(`/dashboard/elearning/${delBtn.dataset.id}`, { method: 'DELETE' });
                if (data.status === 'success') { showToast(data.message || 'Materi berhasil dihapus.'); reloadSoon(); }
                else showToast(data.message || 'Gagal menghapus materi.', 'danger');
            } catch (err) { showToast('Terjadi kesalahan koneksi server.', 'danger'); }
        });

        toggleTipe();
        refreshKuisKosong();
    }

    /* ============ BACA MATERI + KERJAKAN KUIS (semua peran) ============ */
    const modalBaca = document.getElementById('modalBacaMateri');
    if (modalBaca) {
        let bacaMateriId = null;
        document.getElementById('btnCloseBacaModal')?.addEventListener('click', () => closeModal(modalBaca));
        bindBackdrop(modalBaca);

        const renderKuisPd = (soal, hasil, isPd) => {
            const wrapKuis = document.getElementById('wrapKuisPd');
            const box = document.getElementById('kuisPdContainer');
            const hasilBox = document.getElementById('kuisPdHasil');
            if (!soal || !soal.length) { if (wrapKuis) wrapKuis.style.display = 'none'; return; }
            if (wrapKuis) wrapKuis.style.display = '';
            if (box) box.innerHTML = '';
            if (hasilBox) {
                if (hasil) {
                    hasilBox.style.display = '';
                    hasilBox.innerHTML = `<i class="fas fa-circle-check"></i> Skor terakhir: <strong>${hasil.skor}</strong> (${hasil.benar}/${hasil.total_soal} benar)`;
                } else {
                    hasilBox.style.display = 'none';
                }
            }
            (soal || []).forEach((s, idx) => {
                const div = document.createElement('div');
                div.style.cssText = 'border:1px solid var(--border-color);border-radius:var(--radius-md);padding:var(--space-4);';
                let body = '';
                const old = hasil && hasil.jawaban ? hasil.jawaban[String(s.id)] : '';
                if (s.tipe === 'pg') {
                    body = (s.opsi || []).map((o, i) => `
                        <label style="display:flex;gap:8px;align-items:center;font-size:var(--text-sm);color:var(--text-main);margin-top:6px;cursor:pointer;">
                            <input type="radio" name="kuis_${s.id}" value="${esc(o)}" ${old === o ? 'checked' : ''}>
                            <span>${huruf(i)}. ${esc(o)}</span>
                        </label>`).join('');
                } else if (s.tipe === 'bs') {
                    body = ['benar', 'salah'].map((v) => `
                        <label style="display:flex;gap:8px;align-items:center;font-size:var(--text-sm);color:var(--text-main);margin-top:6px;cursor:pointer;">
                            <input type="radio" name="kuis_${s.id}" value="${v}" ${old === v ? 'checked' : ''}>
                            <span>${v.charAt(0).toUpperCase() + v.slice(1)}</span>
                        </label>`).join('');
                } else {
                    body = `<input type="text" class="kuis-pd-isian" data-soal="${s.id}" value="${esc(old)}" placeholder="Jawaban..."
                        style="width:100%;height:34px;padding:0 10px;border:1px solid var(--border-color);background:var(--bg-card);color:var(--text-main);border-radius:var(--radius-md);font-size:var(--text-sm);box-sizing:border-box;margin-top:6px;">`;
                }
                div.innerHTML = `<div style="font-size:var(--text-sm);font-weight:600;color:var(--text-main);">${idx + 1}. ${esc(s.pertanyaan)} <span style="font-weight:400;color:var(--text-muted);font-size:var(--text-xs);">(${s.bobot} poin)</span></div>${body}`;
                box?.appendChild(div);
            });
        };

        document.addEventListener('click', async (e) => {
            const btn = e.target.closest('.btn-buka-materi');
            if (!btn) return;
            try {
                const { data } = await jsonFetch(`/dashboard/elearning/${btn.dataset.id}`);
                if (data.status !== 'success' || !data.data) return showToast(data.message || 'Gagal memuat materi.', 'danger');
                const d = data.data;
                bacaMateriId = d.id;
                document.getElementById('bacaJudul').textContent = d.judul || '';
                document.getElementById('bacaMeta').textContent = d.tipe ? `Tipe: ${d.tipe}` : '';
                const linkWrap = document.getElementById('bacaTautan');
                const linkA = document.getElementById('bacaTautanLink');
                if (d.url_embed && linkWrap && linkA) { linkA.href = d.url_embed; linkWrap.style.display = ''; }
                else if (linkWrap) { linkWrap.style.display = 'none'; }
                const isi = document.getElementById('bacaIsi');
                if (isi) {
                    if (d.isi_html) { isi.innerHTML = d.isi_html; }
                    else if (d.deskripsi) { isi.textContent = d.deskripsi; }
                    else { isi.innerHTML = '<span style="color:var(--text-muted);">Materi tanpa isi teks.</span>'; }
                }
                renderKuisPd(d.soal, d.hasil_saya, !!d.hasil_saya);
                openModal(modalBaca);
            } catch (err) { showToast('Terjadi kesalahan koneksi server.', 'danger'); }
        });

        document.getElementById('formKuisPd')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            const jawaban = {};
            document.querySelectorAll('#kuisPdContainer input[type=radio]:checked').forEach((r) => {
                jawaban[r.name.replace('kuis_', '')] = r.value;
            });
            document.querySelectorAll('#kuisPdContainer .kuis-pd-isian').forEach((i) => {
                jawaban[i.dataset.soal] = i.value;
            });
            try {
                const { ok, data } = await jsonFetch(`/dashboard/elearning/${bacaMateriId}/kuis`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ jawaban }),
                });
                if (ok && data.status === 'success') {
                    showToast(data.message || 'Jawaban kuis terkirim.');
                    const hb = document.getElementById('kuisPdHasil');
                    if (hb && data.hasil) {
                        hb.style.display = '';
                        hb.innerHTML = `<i class="fas fa-circle-check"></i> Skor: <strong>${data.hasil.skor}</strong> (${data.hasil.benar}/${data.hasil.total} benar)`;
                    }
                } else showToast(data.message || 'Gagal mengirim jawaban kuis.', 'danger');
            } catch (err) { showToast('Terjadi kesalahan koneksi server.', 'danger'); }
        });
    }

    /* ==================== REKAP NILAI KUIS (guru/admin) ==================== */
    const modalRekap = document.getElementById('modalRekapKuis');
    if (modalRekap) {
        document.getElementById('btnCloseRekapModal')?.addEventListener('click', () => closeModal(modalRekap));
        bindBackdrop(modalRekap);

        const fmt = (n) => (n == null ? '—' : String(n).replace(/\.0+$/, ''));
        document.addEventListener('click', async (e) => {
            const btn = e.target.closest('.btn-rekap-kuis');
            if (!btn) return;
            const tbody = document.getElementById('rekapTbody');
            document.getElementById('rekapJudul').textContent = btn.dataset.name || '';
            document.getElementById('rekapRingkas').innerHTML = '';
            if (tbody) tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;padding:var(--space-8);color:var(--text-muted);font-size:var(--text-sm);">Memuat…</td></tr>';
            openModal(modalRekap);
            try {
                const { ok, data } = await jsonFetch(`/dashboard/elearning/${btn.dataset.id}/kuis-hasil`, { headers: { 'Accept': 'application/json' } });
                if (!ok || data.status !== 'success') { if (tbody) tbody.innerHTML = `<tr><td colspan="5" style="text-align:center;padding:var(--space-8);color:var(--danger);font-size:var(--text-sm);">${data.message || 'Gagal memuat rekap.'}</td></tr>`; return; }
                const d = data.data;
                const stat = (label, val, color) => `<div style="border:1px solid var(--border-color);border-radius:var(--radius-md);padding:var(--space-4);text-align:center;">
                    <div style="font-size:var(--text-lg);font-weight:800;color:${color};">${val}</div>
                    <div style="font-size:var(--text-xs);color:var(--text-muted);">${label}</div></div>`;
                document.getElementById('rekapRingkas').innerHTML =
                    stat('Soal', d.total_soal, 'var(--primary)') +
                    stat('Dikerjakan', d.dikerjakan, 'var(--accent)') +
                    stat('Rata-rata', fmt(d.rata_rata), 'var(--warning)') +
                    stat('Tuntas (≥70)', d.tuntas, 'var(--success)');
                if (!tbody) return;
                if (!d.rows.length) { tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;padding:var(--space-8);color:var(--text-muted);font-size:var(--text-sm);">Belum ada peserta didik yang mengerjakan.</td></tr>'; return; }
                tbody.innerHTML = d.rows.map((r, i) => {
                    const w = r.submitted_at ? new Date(r.submitted_at).toLocaleString('id-ID', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : '—';
                    const col = r.skor >= 70 ? 'var(--success)' : 'var(--danger)';
                    return `<tr style="border-bottom:1px solid var(--border-color);">
                        <td style="padding:10px 12px;text-align:center;font-size:var(--text-xs);color:var(--text-muted);" data-label="No">${i + 1}</td>
                        <td style="padding:10px 12px;font-size:var(--text-sm);color:var(--text-main);" data-label="Peserta Didik">${String(r.nama).replace(/</g, '&lt;')}</td>
                        <td style="padding:10px 12px;text-align:center;font-size:var(--text-sm);color:var(--text-main);" data-label="Benar">${r.benar}/${r.total_soal}</td>
                        <td style="padding:10px 12px;text-align:center;font-size:var(--text-sm);font-weight:700;color:${col};" data-label="Skor">${fmt(r.skor)}</td>
                        <td style="padding:10px 12px;text-align:right;font-size:var(--text-xs);color:var(--text-muted);" data-label="Waktu">${w}</td>
                    </tr>`;
                }).join('');
            } catch (err) {
                if (tbody) tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;padding:var(--space-8);color:var(--danger);font-size:var(--text-sm);">Terjadi kesalahan koneksi server.</td></tr>';
            }
        });
    }

    /* ==================== GRAFIK DASBOR (guru/admin) ==================== */
    const trendPayload = document.getElementById('elearningTrendPayload');
    if (trendPayload && typeof Chart !== 'undefined') {
        const trend = JSON.parse(trendPayload.textContent || '{}');
        const ctx = document.getElementById('chartElearningTrend')?.getContext('2d');
        if (ctx && trend.labels) {
            const grad = ctx.createLinearGradient(0, 0, 0, 220);
            grad.addColorStop(0, 'rgba(99, 102, 241, 0.32)');
            grad.addColorStop(1, 'rgba(99, 102, 241, 0.0)');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: trend.labels,
                    datasets: [{
                        label: 'Materi',
                        data: trend.values || [],
                        borderColor: '#6366f1',
                        backgroundColor: grad,
                        fill: true,
                        tension: 0.38,
                        pointRadius: 4,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
                },
            });
        }
    }

    /* ============ GRAFIK NILAI KUIS PER MAPEL (peserta didik) ============ */
    const nilaiPayload = document.getElementById('nilaiMapelPayload');
    if (nilaiPayload && typeof Chart !== 'undefined') {
        const nm = JSON.parse(nilaiPayload.textContent || '{}');
        const ctx2 = document.getElementById('chartNilaiMapelPd')?.getContext('2d');
        if (ctx2) {
            if (!nm.labels || !nm.labels.length) {
                const canvas = document.getElementById('chartNilaiMapelPd');
                if (canvas) canvas.insertAdjacentHTML('afterend', '<div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;color:var(--text-muted);font-size:var(--text-sm);">Belum ada nilai kuis.</div>');
            } else {
                new Chart(ctx2, {
                    type: 'bar',
                    data: {
                        labels: nm.labels,
                        datasets: [{
                            label: 'Rata-rata Skor',
                            data: nm.values || [],
                            backgroundColor: 'rgba(99, 102, 241, 0.7)',
                            borderColor: '#6366f1',
                            borderWidth: 1,
                            borderRadius: 6,
                            maxBarThickness: 54,
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            y: { beginAtZero: true, max: 100, ticks: { precision: 0 } },
                            x: { ticks: { autoSkip: false, maxRotation: 45, minRotation: 0 } },
                        },
                    },
                });
            }
        }
    }

    /* ============================ TUGAS (guru) ============================ */
    const modalTugas = document.getElementById('modalFormTugas');
    if (modalTugas) {
        const formTugas = document.getElementById('formTugas');
        const modalTugasTitle = document.getElementById('modalTugasTitle');
        let editingTugasId = null;

        const resetTugasForm = () => {
            editingTugasId = null;
            formTugas?.reset();
            if (modalTugasTitle) modalTugasTitle.innerHTML = '<i class="fas fa-plus-circle" style="color: var(--primary);"></i> Tambah Tugas';
        };

        document.getElementById('btnOpenTugasModal')?.addEventListener('click', () => {
            resetTugasForm();
            openModal(modalTugas);
            document.getElementById('tugasJudul')?.focus();
        });
        document.getElementById('btnCloseTugasModal')?.addEventListener('click', () => closeModal(modalTugas));
        document.getElementById('btnCancelTugasModal')?.addEventListener('click', () => closeModal(modalTugas));
        bindBackdrop(modalTugas);

        document.addEventListener('click', async (e) => {
            const editBtn = e.target.closest('.btn-edit-tugas');
            if (!editBtn) return;
            try {
                const { data } = await jsonFetch(`/dashboard/elearning/tugas/${editBtn.dataset.id}`);
                if (data.status !== 'success' || !data.data) return showToast(data.message || 'Gagal memuat tugas.', 'danger');
                const d = data.data;
                editingTugasId = d.id;
                document.getElementById('tugasPembelajaran').value = d.pembelajaran_id || '';
                document.getElementById('tugasJudul').value = d.judul || '';
                document.getElementById('tugasDeadline').value = d.deadline || '';
                document.getElementById('tugasBobot').value = d.bobot ?? 0;
                document.getElementById('tugasStatus').value = d.status || 'draft';
                document.getElementById('tugasDeskripsi').value = d.deskripsi || '';
                if (modalTugasTitle) modalTugasTitle.innerHTML = '<i class="fas fa-pen-to-square" style="color: var(--primary);"></i> Ubah Tugas';
                openModal(modalTugas);
            } catch (err) { showToast('Terjadi kesalahan koneksi server.', 'danger'); }
        });

        formTugas?.addEventListener('submit', async (e) => {
            e.preventDefault();
            const fd = new FormData(formTugas);
            let url = formTugas.dataset.store;
            let method = 'POST';
            if (editingTugasId) { url = `/dashboard/elearning/tugas/${editingTugasId}`; method = 'PUT'; fd.set('id', editingTugasId); }
            try {
                const { ok, data } = await jsonFetch(url, { method, body: fd });
                if (ok && data.status === 'success') {
                    showToast(data.message || 'Tugas berhasil disimpan.');
                    closeModal(modalTugas);
                    reloadSoon();
                } else showToast(data.message || 'Gagal menyimpan tugas.', 'danger');
            } catch (err) { showToast('Terjadi kesalahan koneksi server.', 'danger'); }
        });

        document.addEventListener('click', async (e) => {
            const delBtn = e.target.closest('.btn-delete-tugas');
            if (!delBtn) return;
            const name = delBtn.dataset.name || 'tugas ini';
            const ok = await confirmAction(`Hapus tugas "${name}" beserta seluruh pengumpulannya? Tindakan ini tidak dapat dibatalkan.`, 'Hapus Tugas?');
            if (!ok) return;
            try {
                const { data } = await jsonFetch(`/dashboard/elearning/tugas/${delBtn.dataset.id}`, { method: 'DELETE' });
                if (data.status === 'success') { showToast(data.message || 'Tugas berhasil dihapus.'); reloadSoon(); }
                else showToast(data.message || 'Gagal menghapus tugas.', 'danger');
            } catch (err) { showToast('Terjadi kesalahan koneksi server.', 'danger'); }
        });
    }

    /* ==================== KUMPULKAN TUGAS (peserta didik) ==================== */
    const modalKumpul = document.getElementById('modalKumpulTugas');
    if (modalKumpul) {
        const formKumpul = document.getElementById('formKumpul');
        const kumpulJudul = document.getElementById('kumpulJudul');

        const closeKumpul = () => closeModal(modalKumpul);
        document.getElementById('btnCloseKumpulModal')?.addEventListener('click', closeKumpul);
        document.getElementById('btnCancelKumpulModal')?.addEventListener('click', closeKumpul);
        bindBackdrop(modalKumpul);

        document.addEventListener('click', (e) => {
            const btn = e.target.closest('.btn-kumpul-tugas');
            if (!btn) return;
            formKumpul.reset();
            document.getElementById('kumpulJawaban').value = btn.dataset.jawaban || '';
            document.getElementById('kumpulLink').value = btn.dataset.link || '';
            if (kumpulJudul) kumpulJudul.textContent = btn.dataset.judul || '';
            formKumpul.dataset.store = `/dashboard/elearning/tugas/${btn.dataset.id}/kumpul`;
            openModal(modalKumpul);
        });

        formKumpul?.addEventListener('submit', async (e) => {
            e.preventDefault();
            const fd = new FormData(formKumpul);
            try {
                const { ok, data } = await jsonFetch(formKumpul.dataset.store, { method: 'POST', body: fd });
                if (ok && data.status === 'success') {
                    showToast(data.message || 'Jawaban berhasil dikumpulkan.');
                    closeKumpul();
                    reloadSoon();
                } else showToast(data.message || 'Gagal mengumpulkan jawaban.', 'danger');
            } catch (err) { showToast('Terjadi kesalahan koneksi server.', 'danger'); }
        });
    }

    /* ==================== PENILAIAN (guru) & tarik pengumpulan ==================== */
    const modalNilai = document.getElementById('modalNilai');
    if (modalNilai) {
        const formNilai = document.getElementById('formNilai');
        document.getElementById('btnCloseNilaiModal')?.addEventListener('click', () => closeModal(modalNilai));
        document.getElementById('btnCancelNilaiModal')?.addEventListener('click', () => closeModal(modalNilai));
        bindBackdrop(modalNilai);

        document.addEventListener('click', (e) => {
            const btn = e.target.closest('.btn-nilai-kumpul');
            if (!btn) return;
            document.getElementById('nilaiNama').textContent = btn.dataset.nama ? `Untuk: ${btn.dataset.nama}` : '';
            document.getElementById('nilaiInput').value = btn.dataset.nilai || '';
            formNilai.dataset.store = `/dashboard/elearning/pengumpulan/${btn.dataset.id}/nilai`;
            openModal(modalNilai);
        });

        formNilai?.addEventListener('submit', async (e) => {
            e.preventDefault();
            const fd = new FormData(formNilai);
            try {
                const { ok, data } = await jsonFetch(formNilai.dataset.store, { method: 'POST', body: fd });
                if (ok && data.status === 'success') {
                    showToast(data.message || 'Nilai berhasil disimpan.');
                    closeModal(modalNilai);
                    reloadSoon();
                } else showToast(data.message || 'Gagal menyimpan nilai.', 'danger');
            } catch (err) { showToast('Terjadi kesalahan koneksi server.', 'danger'); }
        });
    }

    document.addEventListener('click', async (e) => {
        const btn = e.target.closest('.btn-tarik-kumpul');
        if (!btn) return;
        const ok = await confirmAction('Tarik pengumpulan ini? Jawaban dan nilai yang tersimpan akan dihapus.', 'Tarik Pengumpulan?');
        if (!ok) return;
        try {
            const { data } = await jsonFetch(`/dashboard/elearning/pengumpulan/${btn.dataset.id}`, { method: 'DELETE' });
            if (data.status === 'success') { showToast(data.message || 'Pengumpulan ditarik.'); reloadSoon(); }
            else showToast(data.message || 'Gagal menarik pengumpulan.', 'danger');
        } catch (err) { showToast('Terjadi kesalahan koneksi server.', 'danger'); }
    });

    /* ==================== LIHAT JAWABAN TEKS ==================== */
    const modalJawaban = document.getElementById('modalJawaban');
    if (modalJawaban) {
        document.getElementById('btnCloseJawabanModal')?.addEventListener('click', () => closeModal(modalJawaban));
        bindBackdrop(modalJawaban);
        document.addEventListener('click', (e) => {
            const btn = e.target.closest('.btn-lihat-jawaban');
            if (!btn) return;
            document.getElementById('jawabanIsi').textContent = btn.dataset.jawaban || '';
            openModal(modalJawaban);
        });
    }

    /* ==================== Live search & filter (AJAX, tanpa reload) ==================== */
    let debounceTimer = null;

    const buildUrl = (overrides = {}) => {
        const url = new URL(window.location.href);
        const val = (id) => document.getElementById(id)?.value?.trim() ?? '';
        const q = overrides.q !== undefined ? overrides.q : (val('liveSearch'));
        const status = overrides.status !== undefined ? overrides.status : val('filterStatus');
        const pembelajaran = overrides.pembelajaran_id !== undefined ? overrides.pembelajaran_id : val('filterPembelajaran');
        const perPage = overrides.per_page !== undefined ? overrides.per_page : (val('perPageSelect') || '25');

        const setParam = (key, v) => { if (v) url.searchParams.set(key, v); else url.searchParams.delete(key); };
        setParam('q', q);
        setParam('status', status);
        setParam('pembelajaran_id', pembelajaran);
        setParam('per_page', perPage);
        url.searchParams.delete('page');
        return url.toString();
    };

    const refresh = (targetUrl) => {
        if (typeof window.refreshLiveTable === 'function') window.refreshLiveTable(targetUrl);
        else window.location.href = targetUrl;
    };

    document.addEventListener('input', (e) => {
        if (e.target && e.target.id === 'liveSearch') {
            document.getElementById('clearSearch')?.classList.toggle('visible', e.target.value.trim().length > 0);
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => refresh(buildUrl({ q: e.target.value.trim() })), 350);
        }
    });

    document.addEventListener('click', (e) => {
        const clearBtn = e.target.closest('#clearSearch');
        if (clearBtn) {
            const searchInput = document.getElementById('liveSearch');
            if (searchInput) {
                searchInput.value = '';
                clearBtn.classList.remove('visible');
                refresh(buildUrl({ q: '' }));
            }
        }
    });

    document.addEventListener('change', (e) => {
        if (!e.target) return;
        if (e.target.id === 'filterStatus') refresh(buildUrl({ status: e.target.value.trim() }));
        if (e.target.id === 'filterPembelajaran') refresh(buildUrl({ pembelajaran_id: e.target.value.trim() }));
        if (e.target.id === 'perPageSelect') refresh(buildUrl({ per_page: e.target.value.trim() }));
    });
});
