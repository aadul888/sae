/**
 * Modul JavaScript: CBT — Bank Soal & Definisi Ujian.
 * Interaksi terpisah dari Blade. Live search/filter memakai window.refreshLiveTable.
 */
document.addEventListener('DOMContentLoaded', () => {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    const showToast = (message, type = 'success') => {
        if (window.SAE && typeof window.SAE.toast === 'function') {
            window.SAE.toast(message, type);
        } else if (typeof Swal !== 'undefined') {
            Swal.fire({ toast: true, position: 'top-end', icon: type === 'danger' ? 'error' : type, title: message, showConfirmButton: false, timer: 3000 });
        }
    };

    const confirmAction = async (text, title = 'Konfirmasi?') => {
        if (window.SAE && typeof window.SAE.confirm === 'function') {
            return await window.SAE.confirm(text, title, 'warning');
        }
        if (typeof Swal !== 'undefined') {
            const r = await Swal.fire({ title, text, icon: 'warning', showCancelButton: true, confirmButtonColor: '#ef4444', cancelButtonColor: '#64748b', confirmButtonText: 'Ya, Lanjutkan!', cancelButtonText: 'Batal' });
            return r.isConfirmed;
        }
        return window.confirm(text);
    };

    const openModal = (el) => { if (el) el.style.display = 'flex'; };
    const closeModal = (el) => { if (el) el.style.display = 'none'; };
    const bindBackdrop = (el) => { if (el) el.addEventListener('click', (e) => { if (e.target === el) closeModal(el); }); };

    const jsonFetch = async (url, options = {}) => {
        const res = await fetch(url, {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken, ...(options.headers || {}) },
            ...options
        });
        const data = await res.json().catch(() => ({}));
        return { ok: res.ok, data };
    };

    const reloadSoon = () => setTimeout(() => window.location.reload(), 600);

    /* ============================ BANK SOAL ============================ */
    const modalSoal = document.getElementById('modalSoal');
    if (modalSoal) {
        const formSoal = document.getElementById('formSoal');
        const soalTitle = document.getElementById('soalModalTitle');
        const inputPembelajaran = document.getElementById('soalPembelajaran');
        const inputTipe = document.getElementById('soalTipe');
        const inputBobot = document.getElementById('soalBobot');
        const inputTingkat = document.getElementById('soalTingkat');
        const inputPertanyaan = document.getElementById('soalPertanyaan');
        const opsiContainer = document.getElementById('opsiContainer');
        const kunciOpsi = document.getElementById('soalKunciOpsi');
        const kunciTeks = document.getElementById('soalKunciTeks');
        const wrapOpsiPg = document.getElementById('wrapOpsiPg');
        const wrapKunci = document.getElementById('wrapKunci');
        const kunciUraianNote = document.getElementById('kunciUraianNote');
        let editingId = null;
        let opsiValues = ['', '', '', ''];
        let opsiGambarValues = [null, null, null, null];

        const huruf = (i) => String.fromCharCode(65 + i);

        const renderOpsi = () => {
            if (!opsiContainer) return;
            opsiContainer.innerHTML = '';
            opsiValues.forEach((val, i) => {
                const row = document.createElement('div');
                row.style.cssText = 'display: flex; flex-direction: column; gap: 4px; padding: 6px; border: 1px solid var(--border-color); border-radius: var(--radius-md);';
                const thumb = opsiGambarValues[i]
                    ? `<img src="${opsiGambarValues[i]}" alt="Gambar opsi" style="max-height: 60px; border-radius: var(--radius-sm); border: 1px solid var(--border-color);">`
                    : '';
                row.innerHTML = `
                    <div style="display: flex; gap: var(--space-3); align-items: center;">
                        <span style="font-size: var(--text-xs); font-weight: 700; color: var(--text-muted); width: 18px;">${huruf(i)}</span>
                        <input type="text" class="opsi-input" maxlength="500" value="${val.replace(/"/g, '&quot;')}" placeholder="Teks opsi"
                            style="flex: 1; height: 36px; padding: 0 10px; border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-main); border-radius: var(--radius-md); font-size: var(--text-sm); box-sizing: border-box;">
                        <button type="button" class="btn-remove-opsi" title="Hapus opsi" style="background: none; border: none; color: var(--danger); cursor: pointer;" ${opsiValues.length <= 2 ? 'disabled style="opacity:0.4;cursor:not-allowed;background:none;border:none;color:var(--danger);"' : ''}>
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div style="display: flex; align-items: center; gap: 8px; padding-left: 26px; flex-wrap: wrap;">
                        ${thumb}
                        <input type="file" class="opsi-gambar-input" accept="image/*" style="font-size: var(--text-2xs); color: var(--text-muted); max-width: 180px;">
                    </div>`;
                opsiContainer.appendChild(row);
                const input = row.querySelector('.opsi-input');
                input.addEventListener('input', () => { opsiValues[i] = input.value; renderKunciOptions(); });
                row.querySelector('.btn-remove-opsi')?.addEventListener('click', () => {
                    if (opsiValues.length <= 2) return;
                    opsiValues.splice(i, 1);
                    opsiGambarValues.splice(i, 1);
                    renderOpsi();
                    renderKunciOptions();
                });
            });
        };

        const renderKunciOptions = () => {
            if (!kunciOpsi) return;
            const current = kunciOpsi.value;
            kunciOpsi.innerHTML = '<option value="">— Pilih kunci —</option>';
            opsiValues.forEach((val, i) => {
                if (val.trim() === '') return;
                const opt = document.createElement('option');
                opt.value = huruf(i);
                opt.textContent = `${huruf(i)}. ${val}`;
                kunciOpsi.appendChild(opt);
            });
            if ([...kunciOpsi.options].some((o) => o.value === current)) kunciOpsi.value = current;
        };

        const toggleTipe = () => {
            const tipe = inputTipe?.value || 'pg';
            const showOpsi = tipe === 'pg';
            if (wrapOpsiPg) wrapOpsiPg.style.display = showOpsi ? '' : 'none';
            if (kunciUraianNote) kunciUraianNote.style.display = tipe === 'uraian' ? '' : 'none';
            // benar_salah: kunci = dropdown Benar/Salah; lainnya teks (kecuali uraian).
            if (kunciOpsi) kunciOpsi.style.display = (tipe === 'pg' || tipe === 'benar_salah') ? '' : 'none';
            if (kunciTeks) kunciTeks.style.display = (tipe === 'isian') ? '' : 'none';
            if (tipe === 'benar_salah' && kunciOpsi) {
                kunciOpsi.innerHTML = '<option value="">— Pilih kunci —</option><option value="Benar">Benar</option><option value="Salah">Salah</option>';
            } else if (tipe === 'pg') {
                renderKunciOptions();
            }
        };
        inputTipe?.addEventListener('change', toggleTipe);
        document.getElementById('btnTambahOpsi')?.addEventListener('click', () => { opsiValues.push(''); renderOpsi(); renderKunciOptions(); });

        const resetSoalForm = () => {
            editingId = null;
            formSoal?.reset();
            opsiValues = ['', '', '', ''];
            opsiGambarValues = [null, null, null, null];
            renderOpsi();
            if (soalTitle) soalTitle.innerHTML = '<i class="fas fa-plus-circle" style="color: var(--primary);"></i> Tambah Soal';
            const gp = document.getElementById('soalGambarPreviewWrap');
            if (gp) gp.style.display = 'none';
            toggleTipe();
        };

        document.getElementById('btnOpenSoalModal')?.addEventListener('click', () => { resetSoalForm(); openModal(modalSoal); inputPertanyaan?.focus(); });
        document.getElementById('btnCloseSoalModal')?.addEventListener('click', () => closeModal(modalSoal));
        document.getElementById('btnCancelSoalModal')?.addEventListener('click', () => closeModal(modalSoal));
        bindBackdrop(modalSoal);

        document.addEventListener('click', async (e) => {
            const editBtn = e.target.closest('.btn-edit-soal');
            if (!editBtn) return;
            try {
                const { data } = await jsonFetch(`/dashboard/cbt/soal/${editBtn.dataset.id}`);
                if (data.status !== 'success' || !data.data) return showToast(data.message || 'Gagal memuat soal.', 'danger');
                const d = data.data;
                editingId = d.id;
                if (inputPembelajaran) inputPembelajaran.value = d.pembelajaran_id || '';
                if (inputTipe) inputTipe.value = d.tipe || 'pg';
                if (inputBobot) inputBobot.value = d.bobot || 1;
                if (inputTingkat) inputTingkat.value = d.tingkat || '';
                if (inputPertanyaan) inputPertanyaan.value = d.pertanyaan || '';
                opsiValues = Array.isArray(d.opsi) && d.opsi.length ? d.opsi.slice() : ['', '', '', ''];
                opsiGambarValues = Array.isArray(d.opsi_media)
                    ? d.opsi_media.map((o) => o.gambar_url || null)
                    : opsiValues.map(() => null);
                while (opsiGambarValues.length < opsiValues.length) opsiGambarValues.push(null);
                renderOpsi();
                if (soalTitle) soalTitle.innerHTML = '<i class="fas fa-pen-to-square" style="color: var(--primary);"></i> Ubah Soal';
                toggleTipe();
                if (kunciOpsi && (d.tipe === 'pg' || d.tipe === 'benar_salah')) kunciOpsi.value = d.kunci || '';
                if (kunciTeks) kunciTeks.value = d.tipe === 'isian' ? (d.kunci || '') : '';
                const videoInput = document.getElementById('soalVideoUrl');
                if (videoInput) videoInput.value = d.video_url || '';
                const gpWrap = document.getElementById('soalGambarPreviewWrap');
                const gpImg = document.getElementById('soalGambarPreview');
                const gpDel = document.getElementById('soalHapusGambar');
                if (gpDel) gpDel.checked = false;
                if (gpWrap && gpImg) {
                    if (d.gambar_url) { gpImg.src = d.gambar_url; gpWrap.style.display = 'flex'; }
                    else gpWrap.style.display = 'none';
                }
                openModal(modalSoal);
            } catch (err) { showToast('Terjadi kesalahan koneksi server.', 'danger'); }
        });

        formSoal?.addEventListener('submit', async (e) => {
            e.preventDefault();
            let url = formSoal.dataset.store;
            let method = 'POST';
            const fd = new FormData();
            fd.set('pembelajaran_id', inputPembelajaran?.value || '');
            fd.set('tipe', inputTipe?.value || 'pg');
            fd.set('bobot', inputBobot?.value || '1');
            fd.set('tingkat', inputTingkat?.value || '');
            fd.set('pertanyaan', inputPertanyaan?.value || '');
            const tipe = inputTipe?.value || 'pg';
            if (tipe === 'pg') {
                const fileInputs = opsiContainer ? [...opsiContainer.querySelectorAll('.opsi-gambar-input')] : [];
                let outIdx = 0;
                opsiValues.forEach((v, i) => {
                    if (v.trim() === '') return;
                    fd.append(`opsi[${outIdx}]`, v);
                    const fi = fileInputs[i];
                    if (fi && fi.files[0]) fd.append(`opsi_gambar[${outIdx}]`, fi.files[0]);
                    outIdx++;
                });
                fd.set('kunci', kunciOpsi?.value || '');
            } else if (tipe === 'benar_salah') {
                fd.set('kunci', kunciOpsi?.value || '');
            } else if (tipe === 'isian') {
                fd.set('kunci', kunciTeks?.value || '');
            }
            const gambarFile = document.getElementById('soalGambar');
            if (gambarFile && gambarFile.files[0]) fd.set('gambar', gambarFile.files[0]);
            const videoInput = document.getElementById('soalVideoUrl');
            if (videoInput) fd.set('video_url', videoInput.value.trim());
            if (document.getElementById('soalHapusGambar')?.checked) fd.set('hapus_gambar', '1');
            if (editingId) { url = `/dashboard/cbt/soal/${editingId}`; method = 'PUT'; }
            try {
                const { ok, data } = await jsonFetch(url, { method, body: fd });
                if (ok && data.status === 'success') { showToast(data.message || 'Soal berhasil disimpan.'); closeModal(modalSoal); reloadSoon(); }
                else showToast(data.message || 'Gagal menyimpan soal.', 'danger');
            } catch (err) { showToast('Terjadi kesalahan koneksi server.', 'danger'); }
        });

        document.addEventListener('click', async (e) => {
            const delBtn = e.target.closest('.btn-delete-soal');
            if (!delBtn) return;
            const ok = await confirmAction('Hapus soal ini? Soal juga akan dilepas dari semua kertas ujian.', 'Hapus Soal?');
            if (!ok) return;
            try {
                const { data } = await jsonFetch(`/dashboard/cbt/soal/${delBtn.dataset.id}`, { method: 'DELETE' });
                if (data.status === 'success') { showToast(data.message || 'Soal dihapus.'); reloadSoon(); }
                else showToast(data.message || 'Gagal menghapus soal.', 'danger');
            } catch (err) { showToast('Terjadi kesalahan koneksi server.', 'danger'); }
        });

        /* ---- Lihat tampilan soal (read-only) ---- */
        const modalLihat = document.getElementById('modalLihatSoal');
        const lihatBody = document.getElementById('lihatSoalBody');
        const esc = (s) => String(s ?? '').replace(/</g, '&lt;');
        const tipeLabel = { pg: 'Pilihan Ganda', benar_salah: 'Benar / Salah', isian: 'Isian Singkat', uraian: 'Uraian' };

        const renderPreview = (d) => {
            if (!lihatBody) return;
            const mapel = document.getElementById('lihatSoalMapel');
            if (mapel) mapel.textContent = d.mapel || '';
            let html = `<div style="font-size: var(--text-md); font-weight: 600; color: var(--text-main); line-height: 1.6;">${esc(d.pertanyaan)}</div>`;
            if (d.gambar_url) {
                html += `<img src="${d.gambar_url}" alt="Gambar soal" style="max-width: 100%; border-radius: var(--radius-md); border: 1px solid var(--border-color);">`;
            }
            if (d.video_embed) {
                html += `<div style="position: relative; padding-bottom: 56.25%; height: 0; border-radius: var(--radius-md); overflow: hidden; border: 1px solid var(--border-color);"><iframe src="${d.video_embed}" allowfullscreen style="position: absolute; inset: 0; width: 100%; height: 100%; border: 0;"></iframe></div>`;
            } else if (d.video_url) {
                html += `<a href="${esc(d.video_url)}" target="_blank" rel="noopener" style="color: var(--accent); font-size: var(--text-sm);"><i class="fas fa-video"></i> ${esc(d.video_url)}</a>`;
            }
            if (Array.isArray(d.opsi_media) && d.opsi_media.length) {
                html += '<div style="display: flex; flex-direction: column; gap: var(--space-3);">';
                d.opsi_media.forEach((o, i) => {
                    const benar = (o.teks || '') === (d.kunci || '');
                    html += `<div style="display: flex; gap: var(--space-4); align-items: flex-start; padding: 10px 12px; border: 1px solid ${benar ? 'var(--success)' : 'var(--border-color)'}; border-radius: var(--radius-md); background: ${benar ? 'rgba(16,185,129,0.08)' : 'transparent'};">
                        <span style="font-weight: 700; color: ${benar ? 'var(--success)' : 'var(--text-muted)'};">${String.fromCharCode(65 + i)}.</span>
                        <div style="flex: 1; min-width: 0;">
                            <div style="color: var(--text-main); font-size: var(--text-sm);">${esc(o.teks)}</div>
                            ${o.gambar_url ? `<img src="${o.gambar_url}" alt="Gambar opsi" style="max-height: 120px; margin-top: 6px; border-radius: var(--radius-sm); border: 1px solid var(--border-color);">` : ''}
                        </div>
                        ${benar ? '<i class="fas fa-circle-check" style="color: var(--success);"></i>' : ''}
                    </div>`;
                });
                html += '</div>';
            } else if (d.tipe === 'benar_salah') {
                html += `<div style="font-size: var(--text-sm); color: var(--text-muted);">Kunci: <strong style="color: var(--success);">${esc(d.kunci)}</strong></div>`;
            } else if (d.tipe === 'isian') {
                html += `<div style="font-size: var(--text-sm); color: var(--text-muted);">Kunci: <strong style="color: var(--success);">${esc(d.kunci)}</strong></div>`;
            } else if (d.tipe === 'uraian') {
                html += `<div style="font-size: var(--text-xs); color: var(--text-muted); font-style: italic;">Soal uraian — dinilai manual.</div>`;
            }
            html += `<div style="font-size: var(--text-xs); color: var(--text-muted); border-top: 1px dashed var(--border-color); padding-top: var(--space-4);">
                <i class="fas fa-circle-info"></i> ${tipeLabel[d.tipe] || d.tipe} · Bobot ${d.bobot}${d.tingkat ? ' · ' + esc(d.tingkat) : ''}
            </div>`;
            lihatBody.innerHTML = html;
        };

        document.addEventListener('click', async (e) => {
            const btn = e.target.closest('.btn-lihat-soal');
            if (!btn || !modalLihat) return;
            try {
                const { data } = await jsonFetch(`/dashboard/cbt/soal/${btn.dataset.id}`);
                if (data.status !== 'success' || !data.data) return showToast(data.message || 'Gagal memuat soal.', 'danger');
                renderPreview(data.data);
                openModal(modalLihat);
            } catch (err) { showToast('Terjadi kesalahan koneksi server.', 'danger'); }
        });
        document.getElementById('btnCloseLihatSoal')?.addEventListener('click', () => closeModal(modalLihat));
        document.getElementById('btnTutupLihatSoal')?.addEventListener('click', () => closeModal(modalLihat));
        bindBackdrop(modalLihat);

        resetSoalForm();
    }

    /* ============================ UJIAN ============================ */
    const modalUjian = document.getElementById('modalUjian');
    if (modalUjian) {
        const formUjian = document.getElementById('formUjian');
        const ujianTitle = document.getElementById('ujianModalTitle');
        const inputPembelajaran = document.getElementById('ujianPembelajaran');
        let editingId = null;

        // Sembunyikan soal bank yang berbeda mapel dari pilihan saat ini.
        const filterSoalPicker = () => {
            const pid = inputPembelajaran?.value || '';
            document.querySelectorAll('#soalPicker .pilih-soal-row').forEach((row) => {
                const cocok = !pid || row.dataset.pembelajaran === pid;
                row.style.display = cocok ? '' : 'none';
                if (!cocok) { const cb = row.querySelector('.soal-check'); if (cb) cb.checked = false; }
            });
        };
        inputPembelajaran?.addEventListener('change', filterSoalPicker);

        const resetUjianForm = () => {
            editingId = null;
            formUjian?.reset();
            document.querySelectorAll('#soalPicker .soal-check').forEach((cb) => { cb.checked = false; });
            const tok = document.getElementById('ujianToken');
            if (tok) tok.value = '';
            if (ujianTitle) ujianTitle.innerHTML = '<i class="fas fa-plus-circle" style="color: var(--primary);"></i> Tambah Ujian';
            filterSoalPicker();
        };

        document.getElementById('btnOpenUjianModal')?.addEventListener('click', () => { resetUjianForm(); openModal(modalUjian); });
        document.getElementById('btnCloseUjianModal')?.addEventListener('click', () => closeModal(modalUjian));
        document.getElementById('btnCancelUjianModal')?.addEventListener('click', () => closeModal(modalUjian));
        bindBackdrop(modalUjian);

        document.getElementById('btnTokenAcak')?.addEventListener('click', () => {
            const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
            let t = '';
            for (let i = 0; i < 6; i++) t += chars[Math.floor(Math.random() * chars.length)];
            const tok = document.getElementById('ujianToken');
            if (tok) tok.value = t;
        });

        document.addEventListener('click', async (e) => {
            const editBtn = e.target.closest('.btn-edit-ujian');
            if (!editBtn) return;
            try {
                const { data } = await jsonFetch(`/dashboard/cbt/ujian/${editBtn.dataset.id}`);
                if (data.status !== 'success' || !data.data) return showToast(data.message || 'Gagal memuat ujian.', 'danger');
                const d = data.data;
                editingId = d.id;
                document.getElementById('ujianPembelajaran').value = d.pembelajaran_id || '';
                document.getElementById('ujianPeriode').value = d.periode_id || '';
                document.getElementById('ujianJudul').value = d.judul || '';
                document.getElementById('ujianTipe').value = d.tipe || 'ulangan';
                document.getElementById('ujianDurasi').value = d.durasi_menit || 60;
                document.getElementById('ujianKkm').value = d.kkm ?? 75;
                document.getElementById('ujianStatus').value = d.status || 'draft';
                document.getElementById('ujianToken').value = d.token || '';
                document.getElementById('ujianMulai').value = d.jadwal_mulai || '';
                document.getElementById('ujianSelesai').value = d.jadwal_selesai || '';
                document.getElementById('ujianDeskripsi').value = d.deskripsi || '';
                document.getElementById('ujianAcakSoal').checked = !!d.acak_soal;
                document.getElementById('ujianAcakOpsi').checked = !!d.acak_opsi;
                const ids = (d.soal_ids || []).map(String);
                document.querySelectorAll('#soalPicker .soal-check').forEach((cb) => { cb.checked = ids.includes(String(cb.value)); });
                if (ujianTitle) ujianTitle.innerHTML = '<i class="fas fa-pen-to-square" style="color: var(--primary);"></i> Ubah Ujian';
                filterSoalPicker();
                openModal(modalUjian);
            } catch (err) { showToast('Terjadi kesalahan koneksi server.', 'danger'); }
        });

        formUjian?.addEventListener('submit', async (e) => {
            e.preventDefault();
            const fd = new FormData(formUjian);
            // Checkbox boolean: kirim eksplisit 1/0.
            fd.set('acak_soal', document.getElementById('ujianAcakSoal').checked ? '1' : '0');
            fd.set('acak_opsi', document.getElementById('ujianAcakOpsi').checked ? '1' : '0');
            document.querySelectorAll('#soalPicker .soal-check:checked').forEach((cb) => fd.append('soal_ids[]', cb.value));
            let url = formUjian.dataset.store;
            let method = 'POST';
            if (editingId) { url = `/dashboard/cbt/ujian/${editingId}`; method = 'PUT'; }
            try {
                const { ok, data } = await jsonFetch(url, { method, body: fd });
                if (ok && data.status === 'success') { showToast(data.message || 'Ujian berhasil disimpan.'); closeModal(modalUjian); reloadSoon(); }
                else showToast(data.message || 'Gagal menyimpan ujian.', 'danger');
            } catch (err) { showToast('Terjadi kesalahan koneksi server.', 'danger'); }
        });

        document.addEventListener('click', async (e) => {
            const delBtn = e.target.closest('.btn-delete-ujian');
            if (!delBtn) return;
            const name = delBtn.dataset.name || 'ujian ini';
            const ok = await confirmAction(`Hapus "${name}"? Kertas soal akan dihapus, bank soal tetap tersimpan.`, 'Hapus Ujian?');
            if (!ok) return;
            try {
                const { data } = await jsonFetch(`/dashboard/cbt/ujian/${delBtn.dataset.id}`, { method: 'DELETE' });
                if (data.status === 'success') { showToast(data.message || 'Ujian dihapus.'); reloadSoon(); }
                else showToast(data.message || 'Gagal menghapus ujian.', 'danger');
            } catch (err) { showToast('Terjadi kesalahan koneksi server.', 'danger'); }
        });
    }

    /* ==================== BULK SOAL (banyak soal sekaligus) ==================== */
    const modalBulk = document.getElementById('modalBulkSoal');
    if (modalBulk) {
        const container = document.getElementById('bulkSoalContainer');
        const kosong = document.getElementById('bulkSoalKosong');
        const huruf = (i) => String.fromCharCode(65 + i);
        const esc = (s) => String(s ?? '').replace(/"/g, '&quot;').replace(/</g, '&lt;');

        const refreshKosong = () => { if (kosong) kosong.style.display = container && container.children.length ? 'none' : ''; };

        const renderKunciBulk = (wrap) => {
            const tipe = wrap.querySelector('.bulk-tipe').value;
            const sel = wrap.querySelector('.bulk-kunci');
            const opsi = [...wrap.querySelectorAll('.bulk-opsi-input')];
            const cur = sel.value;
            if (tipe === 'pg') {
                sel.innerHTML = '<option value="">— Kunci —</option>';
                opsi.forEach((inp, i) => {
                    if (inp.value.trim() === '') return;
                    const o = document.createElement('option');
                    o.value = huruf(i);
                    o.textContent = `${huruf(i)}. ${inp.value}`;
                    sel.appendChild(o);
                });
            } else if (tipe === 'benar_salah') {
                sel.innerHTML = '<option value="">— Kunci —</option><option value="Benar">Benar</option><option value="Salah">Salah</option>';
            } else {
                sel.innerHTML = '<option value="">— Kunci (teks) —</option>';
            }
            if ([...sel.options].some((o) => o.value === cur)) sel.value = cur;
        };
        const renderOpsiBulk = (wrap, values) => {
            const box = wrap.querySelector('.bulk-opsi-box');
            box.innerHTML = '';
            (values.length ? values : ['', '', '']).forEach((val, i) => {
                const row = document.createElement('div');
                row.style.cssText = 'display:flex;gap:6px;align-items:center;';
                row.innerHTML = `<span style="font-size:var(--text-xs);font-weight:700;color:var(--text-muted);width:16px;">${huruf(i)}</span>
                    <input type="text" class="bulk-opsi-input" maxlength="500" value="${esc(val)}" placeholder="Opsi ${huruf(i)}"
                        style="flex:1;height:34px;padding:0 10px;border:1px solid var(--border-color);background:var(--bg-card);color:var(--text-main);border-radius:var(--radius-md);font-size:var(--text-sm);box-sizing:border-box;">`;
                box.appendChild(row);
                row.querySelector('.bulk-opsi-input').addEventListener('input', () => renderKunciBulk(wrap));
            });
        };

        const addBulkRow = () => {
            if (!container) return;
            const wrap = document.createElement('div');
            wrap.className = 'bulk-soal-item';
            wrap.style.cssText = 'border:1px solid var(--border-color);border-radius:var(--radius-md);padding:var(--space-4);background:rgba(255,255,255,0.02);';
            wrap.innerHTML = `
                <div style="display:flex;gap:var(--space-3);align-items:center;margin-bottom:6px;">
                    <select class="bulk-tipe" style="height:32px;padding:0 8px;border:1px solid var(--border-color);background:var(--bg-card);color:var(--text-main);border-radius:var(--radius-sm);font-size:var(--text-xs);">
                        <option value="pg">Pilihan Ganda</option>
                        <option value="benar_salah">Benar / Salah</option>
                        <option value="isian">Isian</option>
                        <option value="uraian">Uraian</option>
                    </select>
                    <input type="number" class="bulk-bobot" min="1" max="100" value="1" title="Bobot"
                        style="width:70px;height:32px;padding:0 8px;border:1px solid var(--border-color);background:var(--bg-card);color:var(--text-main);border-radius:var(--radius-sm);font-size:var(--text-xs);">
                    <select class="bulk-tingkat" style="height:32px;padding:0 8px;border:1px solid var(--border-color);background:var(--bg-card);color:var(--text-main);border-radius:var(--radius-sm);font-size:var(--text-xs);">
                        <option value="">Tingkat —</option><option value="mudah">Mudah</option><option value="sedang">Sedang</option><option value="sulit">Sulit</option>
                    </select>
                    <button type="button" class="bulk-hapus" title="Hapus baris" style="margin-left:auto;background:none;border:none;color:var(--danger);cursor:pointer;"><i class="fas fa-trash-can"></i></button>
                </div>
                <input type="text" class="bulk-pertanyaan" maxlength="5000" placeholder="Pertanyaan..."
                    style="width:100%;height:34px;padding:0 10px;border:1px solid var(--border-color);background:var(--bg-card);color:var(--text-main);border-radius:var(--radius-md);font-size:var(--text-sm);box-sizing:border-box;margin-bottom:6px;">
                <div class="bulk-opsi-box" style="display:flex;flex-direction:column;gap:4px;margin-bottom:6px;"></div>
                <div style="display:flex;gap:6px;align-items:center;">
                    <button type="button" class="bulk-tambah-opsi" style="background:none;border:1px dashed var(--border-color);color:var(--primary);border-radius:var(--radius-sm);font-size:var(--text-2xs);padding:3px 8px;cursor:pointer;">+ opsi</button>
                    <span style="font-size:var(--text-2xs);color:var(--text-muted);">Kunci:</span>
                    <select class="bulk-kunci" style="flex:1;height:32px;padding:0 8px;border:1px solid var(--border-color);background:var(--bg-card);color:var(--text-main);border-radius:var(--radius-sm);font-size:var(--text-xs);"></select>
                </div>`;
            container.appendChild(wrap);

            const tipeSel = wrap.querySelector('.bulk-tipe');
            const opsiBox = wrap.querySelector('.bulk-opsi-box');
            renderOpsiBulk(wrap, ['', '', '']);
            const applyTipe = () => {
                const t = tipeSel.value;
                opsiBox.style.display = t === 'pg' ? '' : 'none';
                wrap.querySelector('.bulk-tambah-opsi').style.display = t === 'pg' ? '' : 'none';
                renderKunciBulk(wrap);
            };
            tipeSel.addEventListener('change', applyTipe);
            wrap.querySelector('.bulk-tambah-opsi').addEventListener('click', () => {
                const cur = [...wrap.querySelectorAll('.bulk-opsi-input')].map((i) => i.value);
                renderOpsiBulk(wrap, [...cur, '']);
                renderKunciBulk(wrap);
            });
            wrap.querySelector('.bulk-hapus').addEventListener('click', () => { wrap.remove(); refreshKosong(); });
            applyTipe();
            refreshKosong();
        };

        const resetBulk = () => {
            if (container) container.innerHTML = '';
            addBulkRow();
        };

        document.getElementById('btnOpenBulkSoalModal')?.addEventListener('click', () => { resetBulk(); openModal(modalBulk); });
        document.getElementById('btnTambahSoalBulk')?.addEventListener('click', () => addBulkRow());
        document.getElementById('btnCloseBulkSoalModal')?.addEventListener('click', () => closeModal(modalBulk));
        document.getElementById('btnCancelBulkSoalModal')?.addEventListener('click', () => closeModal(modalBulk));
        bindBackdrop(modalBulk);

        document.getElementById('formBulkSoal')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            const pid = document.getElementById('bulkPembelajaran')?.value || '';
            if (!pid) return showToast('Pilih mata pelajaran dulu.', 'danger');
            const soal = [...container.querySelectorAll('.bulk-soal-item')].map((wrap) => {
                const tipe = wrap.querySelector('.bulk-tipe').value;
                const opsi = [...wrap.querySelectorAll('.bulk-opsi-input')].map((i) => i.value.trim()).filter((v) => v !== '');
                return {
                    tipe,
                    pertanyaan: wrap.querySelector('.bulk-pertanyaan').value.trim(),
                    opsi: tipe === 'pg' ? opsi : [],
                    kunci: wrap.querySelector('.bulk-kunci').value.trim(),
                    bobot: parseInt(wrap.querySelector('.bulk-bobot').value, 10) || 1,
                    tingkat: wrap.querySelector('.bulk-tingkat').value || '',
                };
            }).filter((s) => s.pertanyaan !== '');
            if (!soal.length) return showToast('Isi minimal satu pertanyaan.', 'danger');
            const fd = new FormData();
            fd.set('pembelajaran_id', pid);
            fd.set('soal', JSON.stringify(soal));
            try {
                const { ok, data } = await jsonFetch(document.getElementById('formBulkSoal').dataset.store, { method: 'POST', body: fd });
                if (ok && data.status === 'success') { showToast(data.message || 'Soal tersimpan.'); closeModal(modalBulk); reloadSoon(); }
                else showToast(data.message || 'Gagal menyimpan soal.', 'danger');
            } catch (err) { showToast('Terjadi kesalahan koneksi server.', 'danger'); }
        });
    }

    /* ==================== IMPOR SOAL ==================== */
    const modalImport = document.getElementById('modalImportSoal');
    if (modalImport) {
        document.getElementById('btnOpenImportSoalModal')?.addEventListener('click', () => openModal(modalImport));
        document.getElementById('btnCloseImportSoalModal')?.addEventListener('click', () => closeModal(modalImport));
        document.getElementById('btnCancelImportSoalModal')?.addEventListener('click', () => closeModal(modalImport));
        bindBackdrop(modalImport);
    }

    /* ==================== PERIODE ASESMEN ==================== */
    const modalPeriode = document.getElementById('modalPeriode');
    if (modalPeriode) {
        const formPeriode = document.getElementById('formPeriode');
        const judul = document.getElementById('periodeModalTitle');
        let editId = null;

        const resetPeriode = () => {
            editId = null;
            formPeriode?.reset();
            if (judul) judul.innerHTML = '<i class="fas fa-plus-circle" style="color: var(--primary);"></i> Form Periode Asesmen';
        };

        document.getElementById('btnOpenPeriodeModal')?.addEventListener('click', () => { resetPeriode(); openModal(modalPeriode); });
        document.getElementById('btnClosePeriodeModal')?.addEventListener('click', () => closeModal(modalPeriode));
        document.getElementById('btnCancelPeriodeModal')?.addEventListener('click', () => closeModal(modalPeriode));
        bindBackdrop(modalPeriode);

        document.addEventListener('click', async (e) => {
            const btn = e.target.closest('.btn-edit-periode');
            if (!btn) return;
            try {
                const { data } = await jsonFetch(`/dashboard/cbt/periode/${btn.dataset.id}`);
                if (data.status !== 'success' || !data.data) return showToast(data.message || 'Gagal memuat periode.', 'danger');
                const d = data.data;
                editId = d.id;
                document.getElementById('periodeNama').value = d.nama || '';
                document.getElementById('periodeTipe').value = d.tipe || 'uts';
                document.getElementById('periodeStatus').value = d.status || 'draft';
                document.getElementById('periodeTahun').value = d.tahun_ajaran || '';
                document.getElementById('periodeSemester').value = d.semester || '';
                document.getElementById('periodeMulai').value = d.tanggal_mulai || '';
                document.getElementById('periodeSelesai').value = d.tanggal_selesai || '';
                document.getElementById('periodeKeterangan').value = d.keterangan || '';
                if (judul) judul.innerHTML = '<i class="fas fa-pen-to-square" style="color: var(--primary);"></i> Ubah Periode Asesmen';
                openModal(modalPeriode);
            } catch (err) { showToast('Terjadi kesalahan koneksi server.', 'danger'); }
        });

        formPeriode?.addEventListener('submit', async (e) => {
            e.preventDefault();
            const fd = new FormData(formPeriode);
            let url = formPeriode.dataset.store;
            let method = 'POST';
            if (editId) { url = `/dashboard/cbt/periode/${editId}`; method = 'PUT'; }
            try {
                const { ok, data } = await jsonFetch(url, { method, body: fd });
                if (ok && data.status === 'success') { showToast(data.message || 'Periode tersimpan.'); closeModal(modalPeriode); reloadSoon(); }
                else showToast(data.message || 'Gagal menyimpan periode.', 'danger');
            } catch (err) { showToast('Terjadi kesalahan koneksi server.', 'danger'); }
        });

        document.addEventListener('click', async (e) => {
            const btn = e.target.closest('.btn-delete-periode');
            if (!btn) return;
            const ok = await confirmAction(`Hapus periode "${btn.dataset.name || ''}"?`, 'Hapus Periode?');
            if (!ok) return;
            try {
                const { ok: okRes, data } = await jsonFetch(`/dashboard/cbt/periode/${btn.dataset.id}`, { method: 'DELETE' });
                if (okRes && data.status === 'success') { showToast(data.message || 'Periode dihapus.'); reloadSoon(); }
                else showToast(data.message || 'Gagal menghapus periode.', 'danger');
            } catch (err) { showToast('Terjadi kesalahan koneksi server.', 'danger'); }
        });
    }

    /* ==================== SESI UJIAN ==================== */
    const modalSesi = document.getElementById('modalSesi');
    if (modalSesi) {
        const formSesi = document.getElementById('formSesi');
        const judulSesi = document.getElementById('sesiModalTitle');
        let editIdSesi = null;

        const resetSesi = () => {
            editIdSesi = null;
            formSesi?.reset();
            const kuota = document.getElementById('sesiKuota');
            if (kuota) kuota.value = '0';
            if (judulSesi) judulSesi.innerHTML = '<i class="fas fa-plus-circle" style="color: var(--primary);"></i> Form Sesi Ujian';
        };

        document.getElementById('btnOpenSesiModal')?.addEventListener('click', () => { resetSesi(); openModal(modalSesi); });
        document.getElementById('btnCloseSesiModal')?.addEventListener('click', () => closeModal(modalSesi));
        document.getElementById('btnCancelSesiModal')?.addEventListener('click', () => closeModal(modalSesi));
        bindBackdrop(modalSesi);

        document.addEventListener('click', async (e) => {
            const btn = e.target.closest('.btn-edit-sesi');
            if (!btn) return;
            try {
                const { data } = await jsonFetch(`/dashboard/cbt/sesi/${btn.dataset.id}`);
                if (data.status !== 'success' || !data.data) return showToast(data.message || 'Gagal memuat sesi.', 'danger');
                const d = data.data;
                editIdSesi = d.id;
                document.getElementById('sesiPeriode').value = d.periode_id || '';
                document.getElementById('sesiPembelajaran').value = d.pembelajaran_id || '';
                document.getElementById('sesiTanggal').value = d.tanggal || '';
                document.getElementById('sesiJamMulai').value = d.jam_mulai || '';
                document.getElementById('sesiJamSelesai').value = d.jam_selesai || '';
                document.getElementById('sesiKuota').value = d.kuota ?? 0;
                document.getElementById('sesiRuang').value = d.ruang || '';
                document.getElementById('sesiCatatan').value = d.catatan || '';
                if (judulSesi) judulSesi.innerHTML = '<i class="fas fa-pen-to-square" style="color: var(--primary);"></i> Ubah Sesi Ujian';
                openModal(modalSesi);
            } catch (err) { showToast('Terjadi kesalahan koneksi server.', 'danger'); }
        });

        formSesi?.addEventListener('submit', async (e) => {
            e.preventDefault();
            const fd = new FormData(formSesi);
            let url = formSesi.dataset.store;
            let method = 'POST';
            if (editIdSesi) { url = `/dashboard/cbt/sesi/${editIdSesi}`; method = 'PUT'; }
            try {
                const { ok, data } = await jsonFetch(url, { method, body: fd });
                if (ok && data.status === 'success') { showToast(data.message || 'Sesi tersimpan.'); closeModal(modalSesi); reloadSoon(); }
                else showToast(data.message || 'Gagal menyimpan sesi.', 'danger');
            } catch (err) { showToast('Terjadi kesalahan koneksi server.', 'danger'); }
        });

        document.addEventListener('click', async (e) => {
            const btn = e.target.closest('.btn-delete-sesi');
            if (!btn) return;
            const ok = await confirmAction('Hapus sesi ujian ini?', 'Hapus Sesi?');
            if (!ok) return;
            try {
                const { ok: okRes, data } = await jsonFetch(`/dashboard/cbt/sesi/${btn.dataset.id}`, { method: 'DELETE' });
                if (okRes && data.status === 'success') { showToast(data.message || 'Sesi dihapus.'); reloadSoon(); }
                else showToast(data.message || 'Gagal menghapus sesi.', 'danger');
            } catch (err) { showToast('Terjadi kesalahan koneksi server.', 'danger'); }
        });
    }

    /* ==================== GRAFIK DASBOR (guru/admin) ==================== */
    const jenisPayload = document.getElementById('cbtJenisSoalPayload');
    if (jenisPayload && typeof Chart !== 'undefined') {
        const js = JSON.parse(jenisPayload.textContent || '{}');
        const ctx = document.getElementById('chartCbtJenisSoal')?.getContext('2d');
        if (ctx && js.labels && js.labels.length) {
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: js.labels,
                    datasets: [{
                        data: js.values || [],
                        backgroundColor: ['#6366f1', '#06b6d4', '#f59e0b', '#ef4444', '#10b981'],
                        borderWidth: 0,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 12 } } } },
                },
            });
        } else if (ctx) {
            const canvas = document.getElementById('chartCbtJenisSoal');
            canvas.insertAdjacentHTML('afterend', '<div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;color:var(--text-muted);font-size:var(--text-sm);">Belum ada soal di bank.</div>');
        }
    }

    /* ==================== Live search & filter (AJAX) ==================== */
    let debounceTimer = null;
    const buildUrl = (overrides = {}) => {
        const url = new URL(window.location.href);
        const val = (id) => document.getElementById(id)?.value?.trim() ?? '';
        const q = overrides.q !== undefined ? overrides.q : val('liveSearch');
        const pembelajaran = overrides.pembelajaran_id !== undefined ? overrides.pembelajaran_id : val('filterPembelajaran');
        const perPage = overrides.per_page !== undefined ? overrides.per_page : (val('perPageSelect') || '25');

        const setParam = (key, v) => { if (v) url.searchParams.set(key, v); else url.searchParams.delete(key); };
        setParam('q', q);
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
        if (e.target.id === 'filterPembelajaran') refresh(buildUrl({ pembelajaran_id: e.target.value.trim() }));
        if (e.target.id === 'perPageSelect') refresh(buildUrl({ per_page: e.target.value.trim() }));
    });
});
