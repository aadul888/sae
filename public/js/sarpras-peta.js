/**
 * Interactive Site-Plan Builder (Konva.js) - dashboard/sarpras/peta.
 *
 * Kanvas denah sekolah: kop dokumen, legenda warna, blok ruangan (drag/resize/rotate),
 * alat garis jalan/batas, tracing layer, ekspor PNG/PDF.
 *
 * Dimuat sebagai aset biasa (bukan Vite) dari public/js/sarpras-peta.js agar ikut
 * ter-deploy lewat git pull / fallback ZIP tanpa langkah npm run build.
 * Ketergantungan: window.Konva dimuat lebih dulu dari public/vendor/konva/konva.min.js.
 * Warna status disuntik dari server (data-statuses) agar selaras legenda & cetak.
 */
const Konva = window.Konva;


(function () {
    'use strict';

    const host = document.getElementById('saePetaBuilder');
    if (!host) return;

    const page = document.getElementById('saePetaPage');
    const canUpdate = page && page.dataset.canUpdate === '1';
    const dataEl = document.getElementById('saePetaData');
    const DATA = dataEl ? JSON.parse(dataEl.textContent || '{}') : {};
    const STATUSES = JSON.parse((page && page.dataset.statuses) || '{}');
    const csrf = (page && page.dataset.csrf) || '';

    // Ukuran denah kerja (viewBox) + area kop di kanan.
    const [vbX, vbY, vbW, vbH] = String(DATA.viewBox || '0 0 1000 700').split(/\s+/).map(Number);
    const KOP_W = 260;
    const CANVAS_W = vbW + KOP_W;
    const CANVAS_H = vbH;
    const GRID = 10;

    // ---- Stage -------------------------------------------------------------
    const container = document.getElementById('saePetaStage');
    const stage = new Konva.Stage({
        container: container,
        width: CANVAS_W,
        height: CANVAS_H,
        scaleX: 1, scaleY: 1, x: 0, y: 0,
    });

    const bgLayer = new Konva.Layer({ listening: false });
    const viewLayer = new Konva.Layer();
    const transformLayer = new Konva.Layer();

    // Viewport: seluruh isi dokumen denah (bingkai, kop, blok) digambar di
    // dalam grup ini sehingga zoom/pan terasa seperti melihat satu lembar kerja.
    const viewport = new Konva.Group({ x: 0, y: 0, scaleX: 1, scaleY: 1, draggable: false });
    stage.add(bgLayer);
    stage.add(viewLayer);
    stage.add(transformLayer);
    viewLayer.add(viewport);

    // Latar netral (kertas) di belakang — tidak ikut zoom.
    bgLayer.add(new Konva.Rect({ x: 0, y: 0, width: CANVAS_W, height: CANVAS_H, fill: '#ffffff' }));

    // Latar citra referensi (tracing) ikut zoom bersama blok.
    const latarGroup = new Konva.Group({ listening: false });
    viewport.add(latarGroup);

    // Bingkai + kop digambar di dalam viewport (paling belakang).
    const frameGroup = new Konva.Group({ listening: false });
    viewport.add(frameGroup);

    const areaGroup = new Konva.Group({ id: 'areas' });
    const lineGroup = new Konva.Group({ id: 'lines' });
    viewport.add(lineGroup);
    viewport.add(areaGroup);

    const transformer = new Konva.Transformer({
        rotateEnabled: true,
        borderStroke: '#f59e0b',
        anchorStroke: '#f59e0b',
        anchorFill: '#1f2937',
        anchorSize: 9,
        boundBoxFunc: (oldBox, newBox) => (newBox.width < 8 || newBox.height < 8 ? oldBox : newBox),
    });
    transformLayer.add(transformer);

    let editMode = false;
    let drawTool = null;       // 'line' | 'boundary' | null
    let selectedNode = null;

    // ---- Helper status -----------------------------------------------------
    function statusColor(status) {
        return (STATUSES[status] && STATUSES[status].warna) || '#10b981';
    }
    function statusLabel(status) {
        return (STATUSES[status] && STATUSES[status].label) || status;
    }

    // ---- Blok ruangan ------------------------------------------------------
    function makeBlock(a) {
        const grp = new Konva.Group({
            x: a.x, y: a.y,
            rotation: a.rotate || 0,
            // draggable diatur oleh mode edit (lihat btnEdit); jangan aktif di mode baca.
            draggable: false,
            name: 'block',
        });
        grp.setAttrs({ data: a });

        const rect = new Konva.Rect({
            width: a.w, height: a.h,
            fill: statusColor(a.status),
            opacity: 0.55,
            stroke: '#0f172a',
            strokeWidth: 1.2,
            cornerRadius: 2,
            name: 'blockRect',
        });
        grp.add(rect);

        if (a.status === 'siap_bangun') {
            rect.fill('#3b82f6');
            rect.dash([8, 5]);
            rect.stroke('#2563eb');
            rect.opacity(0.3);
        } else if (a.status === 'lahan_kosong') {
            rect.dash([3, 4]);
            rect.opacity(0.22);
        } else if (a.status === 'rusak_2026' || a.status === 'revit_2025') {
            rect.opacity(0.6);
        }

        const label = new Konva.Text({
            width: a.w, height: a.h,
            text: a.kode || '',
            fontSize: 11,
            fontStyle: 'bold',
            fill: '#0f172a',
            align: 'center',
            verticalAlign: 'middle',
            padding: 2,
            wrap: 'word',
            ellipsis: false,
            listening: false,
        });
        grp.add(label);

        grp.on('dragmove', () => {
            if (!editMode) return;
            grp.position({ x: snap(grp.x()), y: snap(grp.y()) });
        });
        grp.on('dragend', () => markDirty());
        grp.on('transformend', () => {
            const sc = grp.scaleX();
            rect.width(Math.max(8, rect.width() * sc));
            rect.height(Math.max(8, rect.height() * grp.scaleY()));
            label.width(rect.width());
            label.height(rect.height());
            grp.scale({ x: 1, y: 1 });
            markDirty();
        });

        return grp;
    }

    function snap(v) {
        return editMode ? Math.round(v / GRID) * GRID : v;
    }

    DATA.areas.forEach((a) => areaGroup.add(makeBlock(a)));

    // ---- Garis jalan / batas ----------------------------------------------
    function makeLine(l) {
        const flat = [];
        (l.points || []).forEach((p) => { flat.push(p[0], p[1]); });
        return new Konva.Line({
            points: flat,
            stroke: l.tipe === 'batas' ? '#dc2626' : '#475569',
            strokeWidth: l.tipe === 'batas' ? 3 : 8,
            lineCap: 'round',
            lineJoin: 'round',
            tension: l.tipe === 'batas' ? 0 : 0.4,
            name: 'line',
            data: l,
        });
    }
    (DATA.lines || []).forEach((l) => lineGroup.add(makeLine(l)));

    // ---- Tracing layer (gambar referensi) ---------------------------------
    if (DATA.latarUrl) {
        const img = new Image();
        img.onload = () => {
            latarGroup.add(new Konva.Image({
                image: img, x: 0, y: 0, width: vbW, height: vbH,
                opacity: (DATA.latarOpacity || 70) / 100, listening: false,
            }));
            viewLayer.batchDraw();
        };
        img.src = DATA.latarUrl;
    }

    // ---- Kop dokumen + legenda + kompas + grid ----------------------------
    function drawFrame() {
        // Grid halus (di dalam viewport, di atas latar citra).
        for (let gx = 0; gx <= vbW; gx += 50) {
            frameGroup.add(new Konva.Line({ points: [gx, 0, gx, vbH], stroke: '#e2e8f0', strokeWidth: 0.5, listening: false }));
        }
        for (let gy = 0; gy <= vbH; gy += 50) {
            frameGroup.add(new Konva.Line({ points: [0, gy, vbW, gy], stroke: '#e2e8f0', strokeWidth: 0.5, listening: false }));
        }
        // Bingkai
        frameGroup.add(new Konva.Rect({ x: 0, y: 0, width: CANVAS_W, height: CANVAS_H, stroke: '#0f172a', strokeWidth: 2, listening: false }));
        frameGroup.add(new Konva.Line({ points: [vbW, 0, vbW, vbH], stroke: '#0f172a', strokeWidth: 2, listening: false }));

        // Kop kanan
        const kop = new Konva.Group({ x: vbW, y: 0 });
        frameGroup.add(kop);

        const addText = (t, x, y, size, bold, fill) => kop.add(new Konva.Text({
            text: t, x: x, y: y, fontSize: size, fontStyle: bold ? 'bold' : 'normal',
            fill: fill || '#0f172a', width: KOP_W - 24, wrap: 'word',
        }));

        let cy = 18;
        addText('PEMETAAN SARANA &', 12, cy, 15, true); cy += 20;
        addText('PRASARANA (SARPRAS)', 12, cy, 15, true); cy += 24;
        addText(DATA.nama || 'SMK Negeri 1 Pagelaran', 12, cy, 12, false, '#334155'); cy += 18;
        addText(DATA.periode || 'Periode Aktif', 12, cy, 11, false, '#334155'); cy += 16;
        addText('Skala ' + (DATA.skala || '1:125'), 12, cy, 11, false, '#334155'); cy += 24;

        kop.add(new Konva.Line({ points: [12, cy, KOP_W - 12, cy], stroke: '#cbd5e1', strokeWidth: 1 })); cy += 10;

        addText('LEGENDA WARNA', 12, cy, 12, true); cy += 20;

        Object.keys(STATUSES).forEach((key) => {
            const meta = STATUSES[key];
            kop.add(new Konva.Rect({
                x: 12, y: cy, width: 18, height: 14,
                fill: meta.warna, opacity: 0.6,
                stroke: '#0f172a', strokeWidth: 0.8,
                dash: key === 'siap_bangun' ? [4, 3] : undefined,
            }));
            kop.add(new Konva.Text({ x: 38, y: cy, text: meta.label, fontSize: 11, fill: '#0f172a' }));
            cy += 20;
        });

        // Kompas U-T-S-B
        const cx = vbW + KOP_W / 2;
        const ccy = vbH - 90;
        const compass = new Konva.Group({ x: cx, y: ccy });
        kop.add(compass);
        compass.add(new Konva.Circle({ radius: 30, stroke: '#0f172a', strokeWidth: 1.2 }));
        compass.add(new Konva.Line({ points: [0, 24, 0, -24], stroke: '#dc2626', strokeWidth: 2 }));
        compass.add(new Konva.Line({ points: [-24, 0, 24, 0], stroke: '#0f172a', strokeWidth: 1 }));
        compass.add(new Konva.Text({ text: 'U', x: -5, y: -44, fontSize: 12, fontStyle: 'bold', fill: '#dc2626' }));
        compass.add(new Konva.Text({ text: 'S', x: -4, y: 28, fontSize: 11, fill: '#0f172a' }));
        compass.add(new Konva.Text({ text: 'T', x: 28, y: -6, fontSize: 11, fill: '#0f172a' }));
        compass.add(new Konva.Text({ text: 'B', x: -36, y: -6, fontSize: 11, fill: '#0f172a' }));

        addText('Revisi 1 — Genap 2026/2027', 12, vbH - 28, 10, false, '#64748b');

        viewLayer.batchDraw();
    }
    drawFrame();

    // ---- Zoom & pan --------------------------------------------------------
    // Stage TIDAK di-scale (pointer tetap = pixel kontainer). Skala fit + zoom
    // diterapkan pada grup `viewport`, jadi semua hitungan pointer konsisten.
    let fitScale = 1;
    let zoom = 1;
    let panX = 0;
    let panY = 0;

    function applyView() {
        const s = fitScale * zoom;
        viewport.scale({ x: s, y: s });
        viewport.position({ x: panX, y: panY });
        viewLayer.batchDraw();
    }

    stage.on('wheel', (e) => {
        e.evt.preventDefault();
        const oldS = fitScale * zoom;
        const pointer = stage.getPointerPosition();
        const denah = { x: (pointer.x - panX) / oldS, y: (pointer.y - panY) / oldS };

        const direction = e.evt.deltaY > 0 ? -1 : 1;
        zoom = clamp(zoom * (direction > 0 ? 1.08 : 1 / 1.08), 0.4, 6);

        const newS = fitScale * zoom;
        panX = pointer.x - denah.x * newS;
        panY = pointer.y - denah.y * newS;
        applyView();
    });

    let panning = false;
    let lastPt = null;
    stage.on('mousedown touchstart', (e) => {
        if (editMode || drawTool) return;
        panning = true;
        lastPt = stage.getPointerPosition();
    });
    stage.on('mousemove touchmove', () => {
        if (!panning) return;
        const p = stage.getPointerPosition();
        panX += p.x - lastPt.x;
        panY += p.y - lastPt.y;
        lastPt = p;
        applyView();
    });
    stage.on('mouseup touchend', () => { panning = false; lastPt = null; });

    function resetView() {
        zoom = 1;
        panX = 0;
        panY = 0;
        applyView();
    }
    function zoomBy(f) {
        zoom = clamp(zoom * f, 0.4, 6);
        applyView();
    }
    const btnZoomIn = document.getElementById('saePetaZoomIn');
    const btnZoomOut = document.getElementById('saePetaZoomOut');
    const btnZoomReset = document.getElementById('saePetaZoomReset');
    if (btnZoomIn) btnZoomIn.addEventListener('click', () => zoomBy(1.2));
    if (btnZoomOut) btnZoomOut.addEventListener('click', () => zoomBy(1 / 1.2));
    if (btnZoomReset) btnZoomReset.addEventListener('click', resetView);

    function clamp(v, a, b) { return Math.max(a, Math.min(b, v)); }

    // ---- Seleksi & detail --------------------------------------------------
    areaGroup.on('click tap', (e) => {
        const grp = e.target.findAncestor('.block', true) || (e.target.hasName && e.target.hasName('block') ? e.target : null);
        if (!grp) { if (!editMode) closeDetail(); return; }
        if (drawTool) return;

        if (editMode) {
            selectNode(grp);
        } else {
            selectNode(grp);
            openDetail(grp.getAttr('data'));
        }
    });

    function selectNode(grp) {
        selectedNode = grp;
        if (editMode) transformer.nodes([grp]);
    }

    stage.on('click tap', (e) => {
        if (e.target === stage && !drawTool) {
            transformer.nodes([]);
            selectedNode = null;
            if (!editMode) closeDetail();
        }
    });

    // ---- Detail panel ------------------------------------------------------
    const detail = document.getElementById('saePetaDetail');
    const detailBody = document.getElementById('saePetaDetailBody');
    const detailTitle = document.getElementById('saePetaDetailTitle');

    function esc(v) {
        return String(v == null ? '' : v).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    }
    function field(label, value) {
        if (value == null || value === '') return '';
        return '<div class="sae-peta-field"><dt>' + esc(label) + '</dt><dd>' + esc(value) + '</dd></div>';
    }

    function openDetail(d) {
        if (!detail || !d) return;
        const isLahan = d.status === 'lahan_kosong' || d.status === 'siap_bangun';
        detailTitle.textContent = (d.kode || '') + ' — ' + (d.nama || '');

        let html = '';
        html += field('Kode', d.kode);
        html += field('Nama', d.nama);
        html += field('Kategori', d.kategori);
        html += field('Zona', d.zona);
        html += field('Luas', d.luas ? d.luas + ' m²' : null);
        html += field('Status', statusLabel(d.status));
        if (!isLahan) {
            html += field('Penanggung Jawab', d.pj);
            html += field('Keterangan', d.ket);
        }

        html += '<div class="sae-peta-detail-actions">';
        if (canUpdate) {
            html += '<button type="button" class="btn btn-outline btn-sm" id="saePetaUbahStatus" data-area-id="' + esc(d.id) + '">' +
                '<i class="fas fa-pen"></i> Ubah Status / Data</button>';
        }
        if (d.ruangId) {
            // ponytail: KIR belum punya deep-link per ruang; arahkan ke daftar KIR.
            html += '<a href="' + esc(page.dataset.kirUrl || '/dashboard/sarpras/kir') + '" class="btn btn-outline btn-sm">' +
                '<i class="fas fa-boxes-stacked"></i> Lihat Aset Ruangan</a>';
        }
        html += '</div>';

        detailBody.innerHTML = html;
        detail.classList.add('is-open');
    }

    function closeDetail() {
        if (detail) detail.classList.remove('is-open');
    }
    const closeBtn = document.getElementById('saePetaDetailClose');
    if (closeBtn) closeBtn.addEventListener('click', closeDetail);

    // Ubah status area (tombol di panel detail)
    detailBody.addEventListener('click', (e) => {
        const btn = e.target.closest('#saePetaUbahStatus');
        if (!btn) return;
        ubahStatus(btn.dataset.areaId);
    });

    function ubahStatus(areaId) {
        if (!window.Swal) return;
        const opts = Object.keys(STATUSES).map((k) =>
            '<option value="' + k + '">' + STATUSES[k].label + '</option>').join('');
        Swal.fire({
            title: 'Ubah Status / Data Area',
            html: '<select id="saePetaStatusSelect" class="swal2-select" style="width:100%;">' + opts + '</select>' +
                '<textarea id="saePetaStatusKet" class="swal2-textarea" placeholder="Keterangan (opsional)" style="width:100%;margin-top:8px;"></textarea>',
            showCancelButton: true,
            confirmButtonText: 'Simpan',
            cancelButtonText: 'Batal',
            reverseButtons: true,
            preConfirm: () => ({
                status_visual: document.getElementById('saePetaStatusSelect').value,
                keterangan: document.getElementById('saePetaStatusKet').value,
            }),
        }).then((res) => {
            if (!res.isConfirmed) return;
            fetch((page.dataset.statusUrl || '/dashboard/sarpras/peta/area') + '/' + areaId + '/status', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
                body: JSON.stringify(res.value),
            }).then((r) => r.json()).then((json) => {
                if (!json.ok) throw new Error(json.pesan || 'gagal');
                toast('Status area diperbarui.', 'success');
                setTimeout(() => window.location.reload(), 600);
            }).catch((err) => toast('Gagal: ' + err.message, 'error'));
        });
    }

    // ---- Dirty state + simpan ---------------------------------------------
    let dirty = false;
    function markDirty() {
        dirty = true;
        const btn = document.getElementById('saePetaSave');
        if (btn) btn.disabled = false;
    }

    function collectLayout() {
        const areas = [];
        areaGroup.getChildren().forEach((grp) => {
            const d = grp.getAttr('data') || {};
            const rect = grp.findOne('.blockRect');
            areas.push({
                id: d.id,
                x: Math.round(grp.x()),
                y: Math.round(grp.y()),
                w: Math.round(rect.width()),
                h: Math.round(rect.height()),
                rotate: Math.round(grp.rotation()),
            });
            // Sinkronkan agar label ikut saat dipakai di view mode.
            d.x = grp.x(); d.y = grp.y(); d.w = rect.width(); d.h = rect.height();
        });
        const lines = lineGroup.getChildren().map((ln) => ({
            tipe: (ln.getAttr('data') && ln.getAttr('data').tipe) || 'jalan',
            points: chunk2(ln.points()),
        }));
        return { areas, lines };
    }

    function chunk2(flat) {
        const out = [];
        for (let i = 0; i < flat.length; i += 2) out.push([Math.round(flat[i]), Math.round(flat[i + 1])]);
        return out;
    }

    // ---- Toolbar -----------------------------------------------------------
    const btnEdit = document.getElementById('saePetaToggleEdit');
    const btnSave = document.getElementById('saePetaSave');
    const modeBadge = document.getElementById('saePetaModeBadge');

    if (btnEdit) {
        btnEdit.addEventListener('click', () => {
            editMode = !editMode;
            transformer.nodes([]);
            areaGroup.getChildren().forEach((g) => {
                g.draggable(editMode && canUpdate);
            });
            host.classList.toggle('is-edit', editMode);
            if (modeBadge) {
                modeBadge.textContent = editMode ? 'Mode Edit' : 'Mode Lihat';
                modeBadge.classList.toggle('is-edit', editMode);
            }
            if (btnSave) btnSave.style.display = editMode ? '' : 'none';
            if (toolsBar) toolsBar.classList.toggle('is-open', editMode);
            if (!editMode) host.classList.remove('is-draw');
        });
    }

    if (btnSave) {
        btnSave.addEventListener('click', async () => {
            const payload = collectLayout();
            payload.ringkasan = 'Simpan tata letak dari editor Konva.';
            try {
                const res = await fetch(page.dataset.saveLayout || '/dashboard/sarpras/peta/layout', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
                    body: JSON.stringify(payload),
                });
                const json = await res.json();
                if (!json.ok) throw new Error(json.pesan || 'gagal');
                dirty = false;
                btnSave.disabled = true;
                toast('Tata letak tersimpan.', 'success');
            } catch (err) {
                toast('Gagal menyimpan: ' + err.message, 'error');
            }
        });
    }

    // Toolbar gambar (edit mode)
    const toolsBar = document.getElementById('saePetaTools');
    let drawingPath = null;

    document.querySelectorAll('[data-tool]').forEach((b) => {
        b.addEventListener('click', () => {
            const t = b.dataset.tool;
            if (t === 'add-ruang') { openRuangPicker(); return; }
            if (t === 'delete') { deleteSelected(); return; }
            drawTool = (drawTool === t) ? null : t;
            document.querySelectorAll('[data-tool]').forEach((x) => x.classList.remove('is-active'));
            if (drawTool) {
                b.classList.add('is-active');
                host.classList.add('is-draw');
            } else {
                host.classList.remove('is-draw');
            }
        });
    });

    // Mulai gambar garis
    stage.on('mousedown touchstart', () => {
        if (!drawTool) return;
        const pos = relPos();
        drawingPath = new Konva.Line({
            points: [pos.x, pos.y, pos.x, pos.y],
            stroke: drawTool === 'batas' ? '#dc2626' : '#475569',
            strokeWidth: drawTool === 'batas' ? 3 : 8,
            lineCap: 'round', lineJoin: 'round',
            tension: drawTool === 'batas' ? 0 : 0.4,
            name: 'line',
        });
        drawingPath.setAttr('data', { tipe: drawTool });
        lineGroup.add(drawingPath);
    });
    stage.on('mousemove touchmove', () => {
        if (!drawTool || !drawingPath) return;
        const pos = relPos();
        const pts = drawingPath.points().slice();
        pts[pts.length - 2] = pos.x;
        pts[pts.length - 1] = pos.y;
        drawingPath.points(pts);
        lineGroup.batchDraw();
    });
    stage.on('mouseup touchend', () => {
        if (!drawTool || !drawingPath) return;
        if (chunk2(drawingPath.points()).length < 2) drawingPath.destroy();
        drawingPath = null;
        markDirty();
    });

    /** Posisi pointer dalam koordinat viewport (denah). */
    function relPos() {
        const p = stage.getPointerPosition();
        const s = fitScale * zoom;
        return { x: (p.x - panX) / s, y: (p.y - panY) / s };
    }

    function deleteSelected() {
        if (!selectedNode) return;
        window.SAE.confirm('Hapus blok ini dari denah?', 'Konfirmasi', 'warning', 'Ya, hapus').then((res) => {
            if (!res || !res.isConfirmed) return;
            const d = selectedNode.getAttr('data') || {};
            selectedNode.destroy();
            transformer.nodes([]);
            selectedNode = null;
            if (d.id) {
                fetch((page.dataset.statusUrl || '/dashboard/sarpras/peta/area') + '/' + d.id, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
                }).catch(() => {});
            }
            markDirty();
        });
    }

    // ---- Pilih ruang belum ditempatkan ------------------------------------
    const ruangModal = document.getElementById('saePetaRuangPicker');
    const ruangList = document.getElementById('saePetaRuangList');

    function openRuangPicker() {
        if (!ruangModal) return;
        ruangModal.style.display = 'flex';
        ruangList.innerHTML = '<div class="sae-text-muted" style="padding:var(--space-8);">Memuat…</div>';

        fetch(page.dataset.ruangTersedia || '/dashboard/sarpras/peta/ruang-tersedia', { headers: { Accept: 'application/json' } })
            .then((r) => r.json())
            .then((j) => {
                if (!j.ok || !j.rows.length) {
                    ruangList.innerHTML = '<div class="sae-text-muted" style="padding:var(--space-8);">Tidak ada ruang master yang belum ditempatkan.</div>';
                    return;
                }
                ruangList.innerHTML = j.rows.map((r) =>
                    '<button type="button" class="sae-peta-ruang-item" data-id="' + r.id + '" data-kode="' + esc(r.kode_ruang) + '">' +
                    '<strong>' + esc(r.kode_ruang) + '</strong> — ' + esc(r.nama_ruang) +
                    '<span class="sae-text-muted"> (' + esc(r.gedung || '-') + ')</span></button>'
                ).join('');
                ruangList.querySelectorAll('.sae-peta-ruang-item').forEach((it) => {
                    it.addEventListener('click', () => placeRuang(it.dataset.id));
                });
            })
            .catch(() => {
                ruangList.innerHTML = '<div class="sae-text-muted" style="padding:var(--space-8);">Gagal memuat daftar ruang.</div>';
            });
    }

    async function placeRuang(id) {
        try {
            const res = await fetch(page.dataset.attachRuang || '/dashboard/sarpras/peta/area', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
                body: JSON.stringify({ ruang_id: id, x: Math.round(vbW / 2), y: Math.round(vbH / 2) }),
            });
            const json = await res.json();
            if (!json.ok) throw new Error(json.pesan || 'gagal');
            areaGroup.add(makeBlock(json.area));
            areaGroup.getChildren().forEach((g) => g.draggable(editMode && canUpdate));
            markDirty();
            if (ruangModal) ruangModal.style.display = 'none';
            toast('Ruang ditambahkan ke denah.', 'success');
        } catch (err) {
            toast('Gagal menambah ruang: ' + err.message, 'error');
        }
    }

    // ---- Latar / tracing ---------------------------------------------------
    const latarToggle = document.getElementById('saePetaLatarToggle');
    const latarOpacity = document.getElementById('saePetaLatarOpacity');
    const latarOpacityVal = document.getElementById('saePetaLatarOpacityVal');

    function applyLatar() {
        const visible = latarToggle ? latarToggle.checked : true;
        const op = latarOpacity ? Number(latarOpacity.value) / 100 : 0.7;
        latarGroup.getChildren().forEach((im) => im.opacity(visible ? op : 0));
        if (latarOpacityVal) latarOpacityVal.textContent = Math.round(op * 100) + '%';
        viewLayer.batchDraw();
    }
    if (latarToggle) latarToggle.addEventListener('change', applyLatar);
    if (latarOpacity) latarOpacity.addEventListener('input', applyLatar);

    // Upload latar (reuse modal yang ada di blade)
    const latarForm = document.getElementById('saePetaLatarForm');
    if (latarForm) {
        latarForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const fd = new FormData(latarForm);
            try {
                const res = await fetch(page.dataset.uploadLatar, {
                    method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' }, body: fd,
                });
                const json = await res.json();
                if (!json.ok) throw new Error(json.pesan || 'gagal');
                const img = new Image();
                img.onload = () => {
                    latarGroup.destroyChildren();
                    latarGroup.add(new Konva.Image({ image: img, x: 0, y: 0, width: vbW, height: vbH, opacity: 0.7, listening: false }));
                    viewLayer.batchDraw();
                };
                img.src = json.latar_url + '?t=' + Date.now();
                toast('Citra latar diperbarui.', 'success');
                document.getElementById('saePetaLatarModal').style.display = 'none';
            } catch (err) {
                toast('Gagal mengunggah: ' + err.message, 'error');
            }
        });
    }

    // ---- Legend interaktif -------------------------------------------------
    document.querySelectorAll('[data-legend-key]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const key = btn.dataset.legendKey;
            const active = btn.classList.contains('is-active');
            document.querySelectorAll('[data-legend-key]').forEach((b) => b.classList.remove('is-active'));
            areaGroup.getChildren().forEach((g) => {
                g.opacity(1);
                const rect = g.findOne('.blockRect');
                if (rect) rect.opacity(rect.getAttr('baseOpacity') || rect.opacity());
            });
            if (!active) {
                btn.classList.add('is-active');
                areaGroup.getChildren().forEach((g) => {
                    const d = g.getAttr('data') || {};
                    if (d.status === key) {
                        g.opacity(1);
                    } else {
                        g.opacity(0.15);
                    }
                });
            }
            viewLayer.batchDraw();
        });
    });

    // ---- Ekspor PNG / PDF --------------------------------------------------
    const btnPng = document.getElementById('saePetaDownloadPng');
    if (btnPng) {
        btnPng.addEventListener('click', () => {
            hideEditChrome();
            const url = stage.toDataURL({ pixelRatio: 3 });
            const a = document.createElement('a');
            a.href = url;
            a.download = 'peta-sekolah-' + Date.now() + '.png';
            a.click();
            restoreEditChrome();
        });
    }
    const btnPdf = document.getElementById('saePetaDownloadPdf');
    if (btnPdf) {
        btnPdf.addEventListener('click', () => {
            hideEditChrome();
            const url = stage.toDataURL({ pixelRatio: 2 });
            const w = window.open('', '_blank');
            if (!w) { restoreEditChrome(); return; }
            w.document.write('<html><head><title>Cetak Peta Sekolah</title></head><body style="margin:0;">' +
                '<img src="' + url + '" style="width:100%;" onload="window.print();"></body></html>');
            w.document.close();
            restoreEditChrome();
        });
    }

    function hideEditChrome() {
        transformer.nodes([]);
        transformer.visible(false);
        viewLayer.batchDraw();
    }
    function restoreEditChrome() {
        transformer.visible(true);
    }

    // ---- Modal buka/tutup + import vision ---------------------------------
    function showModal(id) { const m = document.getElementById(id); if (m) m.style.display = 'flex'; }
    function hideModal(el) { const m = el.closest('.modal-backdrop'); if (m) m.style.display = 'none'; }

    document.querySelectorAll('[data-close-modal]').forEach((b) => {
        b.addEventListener('click', () => hideModal(b));
    });

    const openLatar = document.getElementById('saePetaOpenLatar');
    if (openLatar) openLatar.addEventListener('click', () => showModal('saePetaLatarModal'));

    const openImport = document.getElementById('saePetaOpenImport');
    if (openImport) openImport.addEventListener('click', () => showModal('saePetaImportModal'));

    // Slider opacity di modal latar
    const latarOpacityModal = document.getElementById('saePetaLatarOpacityInput');
    const latarOpacityModalOut = document.getElementById('saePetaLatarOpacityOut');
    if (latarOpacityModal && latarOpacityModalOut) {
        latarOpacityModal.addEventListener('input', () => { latarOpacityModalOut.textContent = latarOpacityModal.value; });
    }

    // Import vision: pindai lalu tampilkan preview sebelum commit
    let importRows = [];
    const importScan = document.getElementById('saePetaImportScan');
    const importApply = document.getElementById('saePetaImportApply');
    const importStatus = document.getElementById('saePetaImportStatus');
    const importPreview = document.getElementById('saePetaImportPreview');

    if (importScan) {
        importScan.addEventListener('click', async () => {
            const fileEl = document.getElementById('saePetaImportFile');
            if (!fileEl || !fileEl.files.length) { toast('Pilih gambar denah dulu.', 'error'); return; }

            importStatus.textContent = 'Memindai gambar…';
            importPreview.innerHTML = '';
            importApply.disabled = true;

            const fd = new FormData();
            fd.append('gambar', fileEl.files[0]);

            try {
                const res = await fetch(page.dataset.importUrl, {
                    method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' }, body: fd,
                });
                const json = await res.json();
                if (!json.ok) throw new Error(json.pesan || 'gagal');

                importRows = json.rows || [];
                importStatus.textContent = importRows.length + ' baris terdeteksi. Periksa lalu klik Terapkan.';

                importPreview.innerHTML = '<table><thead><tr><th>Kode</th><th>Nama</th><th>Kategori</th><th>Status</th></tr></thead><tbody>' +
                    importRows.map((r) => '<tr><td>' + esc(r.kode) + '</td><td>' + esc(r.nama || '') + '</td><td>' + esc(r.kategori || '') + '</td><td>' + esc(r.status_visual || '') + '</td></tr>').join('') +
                    '</tbody></table>';
                importApply.disabled = importRows.length === 0;
            } catch (err) {
                importStatus.textContent = 'Gagal: ' + err.message;
            }
        });
    }

    if (importApply) {
        importApply.addEventListener('click', async () => {
            try {
                const res = await fetch(page.dataset.importCommitUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
                    body: JSON.stringify({ rows: importRows }),
                });
                const json = await res.json();
                if (!json.ok) throw new Error(json.pesan || 'gagal');
                toast(json.pesan, 'success');
                setTimeout(() => window.location.reload(), 800);
            } catch (err) {
                toast('Gagal menerapkan: ' + err.message, 'error');
            }
        });
    }

    // ---- Toast -------------------------------------------------------------
    function toast(msg, type) {
        if (window.SAE && window.SAE.toast) {
            window.SAE.toast(msg, type === 'error' ? 'error' : 'success');
        } else {
            // ponytail: fallback tanpa helper global; upgrade bila butuh pesan lebih kaya.
            console.log(msg);
        }
    }

    // Ukuran stage tetap (logis = CANVAS_W × CANVAS_H). Penskalaan agar pas
    // lebar kontainer dilakukan lewat CSS pada elemen canvas; Konva memakai
    // bounding rect kontainer untuk menghitung posisi pointer, jadi koordinat
    // logis tetap benar tanpa stage.scale().
})();
