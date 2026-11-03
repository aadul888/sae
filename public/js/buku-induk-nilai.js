/**
 * SAE — Buku Induk: Import Nilai & Pengaturan + Penyusun Mata Pelajaran.
 * Vanilla JS + SweetAlert2. Tidak ada logika di Blade.
 */
document.addEventListener('DOMContentLoaded', function () {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

    function esc(s) {
        return String(s === null || s === undefined ? '' : s)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    }

    /* ============================ Import Nilai ============================ */
    const formImport = document.getElementById('formImportNilai');
    if (formImport) {
        formImport.addEventListener('submit', async function (e) {
            e.preventDefault();
            const btn = document.getElementById('btnImportNilai');
            const original = btn ? btn.innerHTML : '';
            if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mengimpor...'; }

            try {
                const fd = new FormData(formImport);
                const res = await fetch(formImport.action, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    body: fd,
                });
                const json = await res.json();
                if (btn) { btn.disabled = false; btn.innerHTML = original; }

                if (res.ok && json.status === 'success') {
                    let msg = json.message;
                    if (json.errors && json.errors.length) msg += '<br><small>' + json.errors.slice(0, 3).join('<br>') + '</small>';
                    Swal.fire({ icon: 'success', title: 'Import Berhasil', html: msg }).then(() => location.reload());
                } else {
                    Swal.fire('Gagal', json.message || 'Import nilai gagal.', 'error');
                }
            } catch (err) {
                if (btn) { btn.disabled = false; btn.innerHTML = original; }
                Swal.fire('Kesalahan Jaringan', 'Tidak dapat menghubungi server.', 'error');
            }
        });
    }

    /* ================== Tab Semester (Maping Mata Pelajaran) ================== */
    const semTabs = document.querySelectorAll('.bi-sem-tab');
    if (semTabs.length) {
        semTabs.forEach(function (tab) {
            tab.addEventListener('click', function () {
                const sem = tab.getAttribute('data-sem');
                semTabs.forEach(function (t) { t.classList.toggle('active', t === tab); });
                document.querySelectorAll('.bi-sem-panel').forEach(function (p) {
                    p.classList.toggle('active', p.getAttribute('data-sem') === sem);
                });
            });
        });
    }

    /* ================== Pelaksanaan PKL per semester ==================
     * Checkbox `pkl_semester[]` langsung terkirim bersama form; tidak ada
     * logika tambahan di sisi klien.
     */

    /* ============ Singkatan mapel (judul kolom ringkas Input Nilai) ============ */
    let singkatanData = {};
    const singkatanEl = document.getElementById('biSingkatanData');
    if (singkatanEl) {
        try { singkatanData = JSON.parse(singkatanEl.textContent) || {}; } catch (e) { /* abaikan */ }
    }

    const KATA_ABAI = ['dan', 'atau', 'serta', 'di', 'ke', 'dari', 'yang', 'untuk', 'pada', 'dengan', 'the', 'of', 'and'];

    function autoSingkatan(name) {
        const kata = String(name || '')
            .replace(/[^A-Za-z0-9]+/g, ' ').trim().split(/\s+/).filter(Boolean)
            .filter(function (k) { return KATA_ABAI.indexOf(k.toLowerCase()) === -1; });
        if (!kata.length) return '';
        if (kata.length > 1) {
            return kata.map(function (k) { return k[0].toUpperCase(); }).join('').slice(0, 12);
        }
        const s = kata[0];
        if (s.length <= 4) return s.toUpperCase();
        let out = '';
        for (const ch of s.toLowerCase()) {
            if ('aeiou'.indexOf(ch) !== -1) continue;
            const up = ch.toUpperCase();
            if (out.indexOf(up) === -1) out += up;
            if (out.length >= 6) break;
        }
        return out || s.slice(0, 3).toUpperCase();
    }

    function singkatanValue(name) {
        const v = singkatanData[name];
        return v !== undefined && v !== null && v !== '' ? v : autoSingkatan(name);
    }

    // Sinkronkan input singkatan dengan nama mapel sama di semua panel.
    document.addEventListener('input', function (e) {
        const inp = e.target.closest ? e.target.closest('.bi-singkatan-item') : null;
        if (!inp || !inp.name) return;
        document.querySelectorAll('.bi-singkatan-item').forEach(function (o) {
            if (o !== inp && o.name === inp.name) o.value = inp.value;
        });
    });

    /* ==================== Input Nilai per Kelas & Semester ==================== */
    const formInputNilai = document.getElementById('formInputNilai');
    if (formInputNilai) {
        formInputNilai.querySelectorAll('.bi-nilai-input').forEach(function (inp) {
            inp.addEventListener('blur', function () { inp.value = inp.value.replace(',', '.').trim(); });
        });

        formInputNilai.addEventListener('submit', async function (e) {
            e.preventDefault();
            const btn = formInputNilai.querySelector('button[type="submit"]');
            const info = document.getElementById('inputNilaiInfo');
            const original = btn ? btn.innerHTML : '';
            if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...'; }

            try {
                const res = await fetch(formInputNilai.action, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    body: new FormData(formInputNilai),
                });
                const json = await res.json();
                if (btn) { btn.disabled = false; btn.innerHTML = original; }

                if (res.ok && json.status === 'success') {
                    if (info) info.textContent = json.message;
                    Swal.fire({ icon: 'success', title: 'Tersimpan', text: json.message, timer: 1600, showConfirmButton: false });
                } else {
                    Swal.fire('Gagal', json.message || 'Gagal menyimpan nilai.', 'error');
                }
            } catch (err) {
                if (btn) { btn.disabled = false; btn.innerHTML = original; }
                Swal.fire('Kesalahan Jaringan', 'Tidak dapat menghubungi server.', 'error');
            }
        });
    }

    const formInputImport = document.getElementById('formInputImport');
    if (formInputImport) {
        formInputImport.addEventListener('submit', async function (e) {
            e.preventDefault();
            const btn = document.getElementById('btnInputImport');
            const original = btn ? btn.innerHTML : '';
            if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mengimpor...'; }

            try {
                const res = await fetch(formInputImport.action, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    body: new FormData(formInputImport),
                });
                const json = await res.json();
                if (btn) { btn.disabled = false; btn.innerHTML = original; }

                if (res.ok && json.status === 'success') {
                    let msg = json.message;
                    if (json.errors && json.errors.length) msg += '<br><small>' + json.errors.slice(0, 3).join('<br>') + '</small>';
                    Swal.fire({ icon: 'success', title: 'Import Berhasil', html: msg }).then(() => location.reload());
                } else {
                    Swal.fire('Gagal', json.message || 'Import nilai gagal.', 'error');
                }
            } catch (err) {
                if (btn) { btn.disabled = false; btn.innerHTML = original; }
                Swal.fire('Kesalahan Jaringan', 'Tidak dapat menghubungi server.', 'error');
            }
        });
    }

    /* ====================== Penyusun Mata Pelajaran ====================== */
    let refData = { umum: [], kejuruan: [] };
    const refEl = document.getElementById('biRefData');
    if (refEl) {
        try { refData = JSON.parse(refEl.textContent) || refData; } catch (e) { /* abaikan */ }
    }

    const cols = document.querySelectorAll('.bi-mapel-col');

    function updateCount(col) {
        const countEl = col.querySelector('[data-count]');
        if (countEl) countEl.textContent = col.querySelectorAll('.bi-sel-item').length;
    }

    function syncTextarea(col) {
        const ta = col.querySelector('.bi-hidden-mapel');
        if (!ta) return;
        const names = Array.from(col.querySelectorAll('.bi-sel-item .bi-sel-name')).map(function (n) { return n.textContent; });
        ta.value = names.join('\n');
    }

    function reindex(col) {
        col.querySelectorAll('.bi-sel-item').forEach(function (item, i) {
            const idx = item.querySelector('.bi-sel-idx');
            if (idx) idx.textContent = (i + 1);
        });
    }

    function refresh(col) {
        reindex(col);
        updateCount(col);
        syncTextarea(col);
        // Perbarui daftar referensi: mapel yang sudah ditambahkan disembunyikan.
        renderRef(col, col.dataset.refFilter || '');
    }

    function buildSelItem(name) {
        const div = document.createElement('div');
        div.className = 'bi-sel-item';
        div.innerHTML =
            '<span class="bi-sel-idx"></span>' +
            '<input type="text" class="bi-input bi-singkatan-item" maxlength="20" title="Singkatan kolom di Input Nilai" ' +
            'name="singkatan[' + esc(name) + ']" value="' + esc(singkatanValue(name)) + '">' +
            '<span class="bi-sel-name">' + esc(name) + '</span>' +
            '<span class="bi-sel-actions">' +
            '<button type="button" class="bi-up" title="Naikkan"><i class="fas fa-arrow-up"></i></button>' +
            '<button type="button" class="bi-down" title="Turunkan"><i class="fas fa-arrow-down"></i></button>' +
            '<button type="button" class="bi-remove" title="Hapus"><i class="fas fa-times"></i></button>' +
            '</span>';
        return div;
    }

    function hasName(col, name) {
        return Array.from(col.querySelectorAll('.bi-sel-item .bi-sel-name'))
            .some(function (n) { return n.textContent.trim().toLowerCase() === name.trim().toLowerCase(); });
    }

    function addName(col, name) {
        name = (name || '').trim();
        if (!name || hasName(col, name)) return;
        const sel = col.querySelector('[data-selected]');
        const empty = sel.querySelector('.bi-sel-empty');
        if (empty) empty.remove();
        sel.appendChild(buildSelItem(name));
        refresh(col);
    }

    function renderSelected(col, names) {
        const sel = col.querySelector('[data-selected]');
        sel.innerHTML = '';
        if (!names.length) {
            sel.innerHTML = '<div class="bi-sel-empty">Belum ada mapel. Tambahkan dari referensi di atas.</div>';
            refresh(col);
            return;
        }
        names.forEach(function (n) { sel.appendChild(buildSelItem(n)); });
        refresh(col);
    }

    function renderRef(col, filter) {
        const key = col.getAttribute('data-kelompok');
        col.dataset.refFilter = filter || '';
        const list = (refData[key] || []).filter(function (n) {
            if (hasName(col, n)) return false; // sudah ditambahkan → hilang dari referensi
            return !filter || n.toLowerCase().indexOf(filter.toLowerCase()) !== -1;
        });
        const box = col.querySelector('[data-ref]');
        if (!box) return;
        if (!list.length) {
            box.innerHTML = '<div class="bi-ref-empty">' + ((refData[key] || []).length ? 'Semua mapel referensi sudah ditambahkan.' : 'Tidak ada mapel referensi yang cocok.') + '</div>';
            return;
        }
        box.innerHTML = list.map(function (n) {
            return '<div class="bi-ref-item"><span>' + esc(n) + '</span>' +
                '<button type="button" class="bi-ref-add" data-name="' + esc(n) + '" title="Tambahkan"><i class="fas fa-plus"></i></button></div>';
        }).join('');
    }

    cols.forEach(function (col) {
        // Muat nilai awal dari textarea (mapel tersimpan)
        const ta = col.querySelector('.bi-hidden-mapel');
        const initial = ta ? ta.value.split('\n').map(function (s) { return s.trim(); }).filter(Boolean) : [];
        renderSelected(col, initial);
        renderRef(col, '');

        // Cari referensi
        const search = col.querySelector('.bi-ref-search');
        if (search) {
            search.addEventListener('input', function () { renderRef(col, search.value.trim()); });
        }

        // Tambah dari referensi
        const refBox = col.querySelector('[data-ref]');
        if (refBox) {
            refBox.addEventListener('click', function (e) {
                const btn = e.target.closest('.bi-ref-add');
                if (!btn) return;
                addName(col, btn.getAttribute('data-name'));
            });
        }

        // Tambah manual
        const customInput = col.querySelector('.bi-custom-input');
        const addBtn = col.querySelector('.bi-add-btn');
        function addCustom() {
            if (!customInput) return;
            addName(col, customInput.value);
            customInput.value = '';
        }
        if (addBtn) addBtn.addEventListener('click', addCustom);
        if (customInput) {
            customInput.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') { e.preventDefault(); addCustom(); }
            });
        }

        // Aksi pada item terpilih (naik/turun/hapus)
        const sel = col.querySelector('[data-selected]');
        if (sel) {
            sel.addEventListener('click', function (e) {
                const item = e.target.closest('.bi-sel-item');
                if (!item) return;
                if (e.target.closest('.bi-remove')) {
                    item.remove();
                    if (!sel.querySelector('.bi-sel-item')) {
                        sel.innerHTML = '<div class="bi-sel-empty">Belum ada mapel. Tambahkan dari referensi di atas.</div>';
                    }
                    refresh(col);
                } else if (e.target.closest('.bi-up')) {
                    const prev = item.previousElementSibling;
                    if (prev && prev.classList.contains('bi-sel-item')) {
                        sel.insertBefore(item, prev);
                        refresh(col);
                    }
                } else if (e.target.closest('.bi-down')) {
                    const next = item.nextElementSibling;
                    if (next && next.classList.contains('bi-sel-item')) {
                        sel.insertBefore(next, item);
                        refresh(col);
                    }
                }
            });
        }
    });

    /* ================== Salin mapel dari semester lain ================== */
    let perSemester = {};
    const perSemEl = document.getElementById('biPerSemesterData');
    if (perSemEl) {
        try { perSemester = JSON.parse(perSemEl.textContent) || {}; } catch (e) { /* abaikan */ }
    }

    document.querySelectorAll('.bi-copy-btn').forEach(function (btn) {
        btn.addEventListener('click', async function () {
            const sem = parseInt(btn.dataset.sem, 10);
            const row = btn.closest('.bi-copy-row');
            const sel = row ? row.querySelector('.bi-copy-select') : null;
            const sumber = sel ? parseInt(sel.value, 10) : NaN;
            if (!sumber) {
                Swal.fire('Pilih semester', 'Tentukan semester sumber yang ingin disalin.', 'info');
                return;
            }

            const panel = btn.closest('.bi-sem-panel');
            const data = perSemester[sumber] || {};
            const total = ['umum', 'kejuruan']
                .reduce(function (n, key) { return n + ((data[key] || []).length); }, 0);

            const konfirmasi = await Swal.fire({
                icon: 'question',
                title: 'Salin mapel semester ' + sumber + '?',
                html: 'Urutan dan pilihan mapel semester <strong>' + sem + '</strong> akan <strong>disamakan</strong> dengan semester ' + sumber +
                    (total ? '' : ' (semester sumber belum punya mapel).'),
                showCancelButton: true,
                confirmButtonText: 'Salin',
                cancelButtonText: 'Batal',
            });
            if (!konfirmasi.isConfirmed) return;

            ['umum', 'kejuruan'].forEach(function (key) {
                const col = panel ? panel.querySelector('.bi-mapel-col[data-kelompok="' + key + '"]') : null;
                if (!col) return;
                // Ganti seluruh daftar: urutan & pilihan persis seperti semester sumber.
                renderSelected(col, data[key] || []);
            });

            Swal.fire({
                icon: 'success',
                title: 'Mapel disalin',
                text: total + ' mapel dari semester ' + sumber + ' diterapkan ke semester ' + sem + '. Simpan untuk menerapkan.',
                timer: 2000,
                showConfirmButton: false,
            });
        });
    });

    /* ============================ Simpan Pengaturan ============================ */
    const formSet = document.getElementById('formPengaturan');
    if (formSet) {
        formSet.addEventListener('submit', async function (e) {
            e.preventDefault();

            // Pastikan textarea tersinkron sebelum submit
            cols.forEach(syncTextarea);

            const btn = formSet.querySelector('button[type="submit"]');
            const original = btn ? btn.innerHTML : '';
            if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...'; }

            try {
                const fd = new FormData(formSet);
                const res = await fetch(formSet.action, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    body: fd,
                });
                const json = await res.json();
                if (btn) { btn.disabled = false; btn.innerHTML = original; }

                if (res.ok && json.status === 'success') {
                    Swal.fire({ icon: 'success', title: 'Tersimpan', text: json.message, timer: 1500, showConfirmButton: false })
                        .then(function () { location.reload(); });
                } else {
                    Swal.fire('Gagal', json.message || 'Pengaturan gagal disimpan.', 'error');
                }
            } catch (err) {
                if (btn) { btn.disabled = false; btn.innerHTML = original; }
                Swal.fire('Kesalahan Jaringan', 'Tidak dapat menghubungi server.', 'error');
            }
        });
    }

    /* ============================ Atur Nilai (modal) ============================ */
    const modalNilai = document.getElementById('modalNilaiBukuInduk');
    const nilaiLoading = document.getElementById('nilaiLoading');
    const formNilai = document.getElementById('formNilaiBukuInduk');
    const nilaiTableWrap = document.getElementById('nilaiTableWrap');
    const nilaiTitle = document.getElementById('modalNilaiTitle');
    const nilaiSubtitle = document.getElementById('modalNilaiSubtitle');
    const nilaiPdId = document.getElementById('nilaiPesertaDidikId');
    const nilaiAkademik = document.getElementById('nilaiAkademik');
    const btnSimpanNilai = document.getElementById('btnSimpanNilai');

    function openNilaiModal() {
        if (!modalNilai) return;
        modalNilai.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
    function closeNilaiModal() {
        if (!modalNilai) return;
        modalNilai.style.display = 'none';
        document.body.style.overflow = '';
    }

    function fmtVal(v) {
        if (v === null || v === undefined) return '';
        const n = Number(v);
        if (isNaN(n)) return '';
        return (Math.round(n * 100) / 100).toString();
    }

    function renderNilaiTable(matriks) {
        if (!nilaiTableWrap) return;
        // Kumpulkan daftar semester dari data (aktif: 1..6, alumni: 5..6).
        let semesters = [];
        (matriks || []).forEach(function (row) {
            Object.keys(row.nilai || {}).forEach(function (s) {
                s = parseInt(s, 10);
                if (!isNaN(s) && semesters.indexOf(s) === -1) semesters.push(s);
            });
        });
        semesters.sort(function (a, b) { return a - b; });

        let html = '<table class="table" style="width:100%;border-collapse:collapse;font-size:var(--text-sm,0.8125rem);">';
        html += '<thead><tr style="border-bottom:1px solid var(--border-color);">';
        html += '<th style="padding:8px 10px;text-align:left;position:sticky;top:0;background:var(--card-bg,#fff);">Mata Pelajaran</th>';
        semesters.forEach(function (s) {
            html += '<th style="padding:8px 6px;text-align:center;width:80px;position:sticky;top:0;background:var(--card-bg,#fff);">Sem ' + s + '</th>';
        });
        html += '<th style="padding:8px 6px;text-align:center;width:80px;position:sticky;top:0;background:var(--card-bg,#fff);">Akhir</th>';
        html += '</tr></thead><tbody>';

        (matriks || []).forEach(function (row) {
            const badge = row.kelompok === 'kejuruan'
                ? '<span class="badge" style="font-size:9px;background:rgba(245,158,11,0.15);color:#f59e0b;padding:1px 6px;border-radius:6px;margin-left:6px;">Kejuruan</span>'
                : '<span class="badge" style="font-size:9px;background:rgba(16,185,129,0.15);color:#10b981;padding:1px 6px;border-radius:6px;margin-left:6px;">Umum</span>';
            html += '<tr style="border-bottom:1px solid var(--border-color);">';
            html += '<td style="padding:6px 10px;font-weight:600;">' + esc(row.nama) + badge + '</td>';
            const editable = Array.isArray(row.editable) ? row.editable : null;
            semesters.forEach(function (s) {
                const val = row.nilai ? row.nilai[s] : null;
                // Baris dengan `editable` (mis. PKL) hanya boleh diisi pada semester pelaksanaannya.
                if (editable && editable.indexOf(s) === -1) {
                    html += '<td style="padding:4px 4px;text-align:center;color:var(--text-muted,#9ca3af);">&mdash;</td>';
                    return;
                }
                html += '<td style="padding:4px 4px;text-align:center;">'
                    + '<input type="text" inputmode="decimal" class="nilai-input" data-mapel="' + esc(row.nama) + '" data-sem="' + s + '"'
                    + ' value="' + fmtVal(val) + '" placeholder="—"'
                    + ' style="width:100%;box-sizing:border-box;text-align:center;padding:6px 4px;border:1px solid var(--border-color);border-radius:6px;background:var(--bg-hover,#f9fafb);color:var(--text-color);">'
                    + '</td>';
            });
            html += '<td style="padding:6px;text-align:center;font-weight:700;color:var(--primary,#3b82f6);" data-akhir>' + (row.akhir != null ? fmtVal(row.akhir) : '—') + '</td>';
            html += '</tr>';
        });

        html += '</tbody></table>';
        nilaiTableWrap.innerHTML = html;
    }

    // Recompute "Akhir" per row on input change (client-side preview).
    if (nilaiTableWrap) {
        nilaiTableWrap.addEventListener('input', function (e) {
            if (!e.target.classList.contains('nilai-input')) return;
            const tr = e.target.closest('tr');
            if (!tr) return;
            const inputs = tr.querySelectorAll('.nilai-input');
            let sum = 0, count = 0;
            inputs.forEach(function (inp) {
                const v = parseFloat(String(inp.value).replace(',', '.'));
                if (!isNaN(v)) { sum += v; count++; }
            });
            const cell = tr.querySelector('[data-akhir]');
            if (cell) cell.textContent = count ? (Math.round((sum / count) * 100) / 100).toString() : '—';
        });
    }

    window.openNilaiModal = async function (id, nama, akademik) {
        if (!modalNilai || !formNilai) return;
        openNilaiModal();
        if (nilaiLoading) nilaiLoading.style.display = 'block';
        formNilai.style.display = 'none';
        if (nilaiTitle) nilaiTitle.textContent = 'Atur Nilai Buku Induk';
        if (nilaiSubtitle) nilaiSubtitle.textContent = 'Memuat nilai ' + (nama || 'peserta didik') + '...';
        if (nilaiPdId) nilaiPdId.value = id;
        if (nilaiAkademik) nilaiAkademik.value = akademik || 'aktif';

        try {
            const url = '/dashboard/kesiswaan/buku-induk/nilai/' + encodeURIComponent(id) + '?akademik=' + encodeURIComponent(akademik || 'aktif');
            const res = await fetch(url, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            const json = await res.json();
            if (nilaiLoading) nilaiLoading.style.display = 'none';

            if (json.status !== 'success') {
                Swal.fire('Gagal', json.message || 'Nilai tidak dapat dimuat.', 'error');
                closeNilaiModal();
                return;
            }

            const d = json.data;
            renderNilaiTable(d.matriks);
            if (nilaiTitle) nilaiTitle.textContent = 'Atur Nilai — ' + d.nama;
            if (nilaiSubtitle) {
                const ket = (d.akademik === 'alumni')
                    ? 'Alumni &bull; hanya nilai kelas XII (semester 5–6)'
                    : 'Siswa aktif &bull; semester 1–6';
                nilaiSubtitle.innerHTML = 'NISN: <strong>' + esc(d.nisn || '-') + '</strong> &bull; ' + ket;
            }
            formNilai.style.display = 'flex';
        } catch (err) {
            if (nilaiLoading) nilaiLoading.style.display = 'none';
            Swal.fire('Kesalahan Jaringan', 'Tidak dapat menghubungi server.', 'error');
            closeNilaiModal();
        }
    };

    document.addEventListener('click', function (e) {
        const closeBtn = e.target.closest('.close-modal[data-target="#modalNilaiBukuInduk"]');
        if (closeBtn) { e.preventDefault(); closeNilaiModal(); return; }
        if (modalNilai && e.target === modalNilai) closeNilaiModal();

        const btn = e.target.closest('.btn-atur-nilai');
        if (!btn) return;
        e.preventDefault();
        e.stopPropagation();
        const id = btn.getAttribute('data-id');
        const nama = btn.getAttribute('data-nama');
        const akademik = btn.getAttribute('data-akademik') || 'aktif';
        if (id && typeof window.openNilaiModal === 'function') {
            window.openNilaiModal(id, nama, akademik);
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && modalNilai && modalNilai.style.display !== 'none') closeNilaiModal();
    });

    if (formNilai) {
        formNilai.addEventListener('submit', async function (e) {
            e.preventDefault();
            const id = nilaiPdId ? nilaiPdId.value : '';
            const akademik = nilaiAkademik ? nilaiAkademik.value : 'aktif';
            if (!id) return;

            // Susun payload: { nilai: { mapel: { sem: value } } }
            const payload = { nilai: {}, akademik: akademik };
            (nilaiTableWrap ? nilaiTableWrap.querySelectorAll('.nilai-input') : []).forEach(function (inp) {
                const mapel = inp.getAttribute('data-mapel');
                const sem = inp.getAttribute('data-sem');
                if (!mapel || !sem) return;
                if (!payload.nilai[mapel]) payload.nilai[mapel] = {};
                payload.nilai[mapel][sem] = inp.value;
            });

            const originalText = btnSimpanNilai ? btnSimpanNilai.innerHTML : '';
            if (btnSimpanNilai) {
                btnSimpanNilai.disabled = true;
                btnSimpanNilai.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';
            }

            try {
                const res = await fetch('/dashboard/kesiswaan/buku-induk/nilai/' + encodeURIComponent(id), {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(Object.assign({ _method: 'PUT' }, payload)),
                });
                const json = await res.json();
                if (btnSimpanNilai) { btnSimpanNilai.disabled = false; btnSimpanNilai.innerHTML = originalText; }

                if (res.ok && json.status === 'success') {
                    closeNilaiModal();
                    Swal.fire({ icon: 'success', title: 'Tersimpan', text: json.message, timer: 1600, showConfirmButton: false });
                } else {
                    Swal.fire('Gagal', json.message || 'Nilai gagal disimpan.', 'error');
                }
            } catch (err) {
                if (btnSimpanNilai) { btnSimpanNilai.disabled = false; btnSimpanNilai.innerHTML = originalText; }
                Swal.fire('Kesalahan Jaringan', 'Tidak dapat menghubungi server.', 'error');
            }
        });
    }
});
