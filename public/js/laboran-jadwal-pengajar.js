/**
 * Jadwal Praktik Laboran — pemilih "Guru Ikut Serta".
 *
 * Memungkinkan satu sesi praktik berisi lebih dari satu guru pada lokasi dan
 * jam yang sama (guru utama tetap dari dropdown guru & mapel). Guru tambahan
 * dikirim sebagai `ptk_ids[]`; server menggabungkannya dengan `ptk_id` lalu
 * menyusun nama guru pendamping.
 *
 * Dipakai lewat `window.SAELaboranPengajar`.
 */
(function () {
    'use strict';

    const PANEL_GURU = {
        'selectGuruMapelSemester': 'guruPengajarSemester',
        'selectGuruMapelMingguan': 'guruPengajarMingguan'
    };

    let BASE_URL = '';

    /** Cache daftar GTK: cukup satu permintaan untuk semua panel. */
    const cache = { items: null, promise: null };

    function cariPanelPengajar(selectEl) {
        if (!selectEl || !selectEl.id) return null;
        const panelId = PANEL_GURU[selectEl.id];
        return panelId ? document.getElementById(panelId) : null;
    }

    /** Daftar guru tambahan yang dipilih pada satu panel. */
    function guruTerpilih(panel) {
        if (!panel) return [];
        return Array.prototype.map.call(panel.querySelectorAll('.guru-pengajar-tags [data-ptk]'), function (el) {
            return { ptk_id: el.dataset.ptk, nama: el.dataset.nama };
        });
    }

    function isiPilihanGuru(panel, items) {
        const sel = panel.querySelector('.guru-pengajar-pilih');
        if (!sel) return;
        const terpilih = guruTerpilih(panel).map(function (g) { return g.ptk_id; });
        const nilaiLama = sel.value;

        sel.innerHTML = '<option value="">-- Tambah guru pengajar --</option>';
        (items || []).forEach(function (g) {
            if (terpilih.indexOf(g.ptk_id) !== -1) return;
            const opt = document.createElement('option');
            opt.value = g.ptk_id;
            opt.textContent = g.nama;
            opt.dataset.nama = g.nama;
            sel.appendChild(opt);
        });

        if (nilaiLama) sel.value = nilaiLama;
    }

    /** Ambil daftar GTK sekali, lalu bagikan hasilnya ke semua panel. */
    function muatDaftarGuru(panel) {
        if (!panel) return Promise.resolve([]);
        if (cache.items) {
            isiPilihanGuru(panel, cache.items);
            return Promise.resolve(cache.items);
        }
        if (!cache.promise) {
            cache.promise = fetch(BASE_URL + '/jadwal/daftar-guru', {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                credentials: 'same-origin'
            })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    cache.items = (data && data.guru) || [];
                    return cache.items;
                })
                .catch(function () {
                    cache.items = [];
                    return [];
                });
        }
        return cache.promise.then(function (items) {
            isiPilihanGuru(panel, items);
            return items;
        });
    }

    function renderTagGuru(panel) {
        const kontainer = panel.querySelector('.guru-pengajar-tags');
        if (!kontainer) return;

        const daftar = guruTerpilih(panel);
        kontainer.innerHTML = '';

        if (daftar.length === 0) {
            kontainer.innerHTML = '<span style="font-size: var(--text-xs); color: var(--text-muted);">Belum ada guru tambahan.</span>';
            return;
        }

        daftar.forEach(function (g) {
            const tag = document.createElement('span');
            tag.className = 'badge badge-outline';
            tag.dataset.ptk = g.ptk_id;
            tag.dataset.nama = g.nama;
            tag.style.display = 'inline-flex';
            tag.style.alignItems = 'center';
            tag.style.gap = '6px';
            tag.innerHTML = '<i class="fas fa-user me-1"></i>' + g.nama
                + ' <button type="button" class="guru-pengajar-hapus" data-ptk="' + g.ptk_id + '"'
                + ' style="background: none; border: none; color: var(--danger, #ef4444); cursor: pointer; padding: 0;"'
                + ' title="Hapus guru">&times;</button>';
            kontainer.appendChild(tag);
        });

        isiPilihanGuru(panel, cache.items || []);
    }

    function tambahGuruPengajar(panel, ptkId) {
        if (!panel || !ptkId) return;
        const ada = guruTerpilih(panel).some(function (g) { return g.ptk_id === ptkId; });
        if (ada) return;

        const sel = panel.querySelector('.guru-pengajar-pilih');
        const opt = sel ? sel.options[sel.selectedIndex] : null;
        const nama = (opt && opt.dataset.nama) || ptkId;

        const tag = document.createElement('span');
        tag.className = 'badge badge-outline';
        tag.dataset.ptk = ptkId;
        tag.dataset.nama = nama;
        panel.querySelector('.guru-pengajar-tags').appendChild(tag);
        renderTagGuru(panel);
    }

    function resetGuruPengajar(panel) {
        if (!panel) return;
        const kontainer = panel.querySelector('.guru-pengajar-tags');
        if (kontainer) kontainer.innerHTML = '';
        renderTagGuru(panel);
    }

    /**
     * Isi tag guru ikut serta dari data tombol Ubah (`data-ptk-ids` berisi
     * JSON seluruh guru sesi; guru utama dikeluarkan dari daftar tambahan).
     */
    function isiGuruPengajarDariData(panelId, dataset) {
        const panel = document.getElementById(panelId);
        if (!panel || !dataset) return;

        let ids = [];
        try { ids = JSON.parse(dataset.ptkIds || '[]'); } catch (e) { ids = []; }
        if (!Array.isArray(ids)) ids = [];

        const utama = String(dataset.ptkId || '');
        ids = ids.map(function (v) { return String(v); }).filter(function (v) {
            return v !== '' && v !== utama;
        });
        if (ids.length === 0) return;

        panel.style.display = 'block';
        muatDaftarGuru(panel).then(function (items) {
            const kontainer = panel.querySelector('.guru-pengajar-tags');
            if (!kontainer) return;
            ids.forEach(function (id) {
                const found = (items || []).find(function (g) { return String(g.ptk_id) === id; });
                const tag = document.createElement('span');
                tag.className = 'badge badge-outline';
                tag.dataset.ptk = id;
                tag.dataset.nama = found ? found.nama : id;
                kontainer.appendChild(tag);
            });
            renderTagGuru(panel);
        });
    }

    /**
     * Kirim daftar guru ikut serta sebagai `ptk_ids[]` (guru utama tetap
     * `ptk_id`). Server menggabungkan keduanya dan menyusun guru pendamping.
     */
    function syncGuruPengajarInputs(form, selectGuruMapel) {
        if (!form) return;
        form.querySelectorAll('input[name="ptk_ids[]"]').forEach(function (el) { el.remove(); });

        const panel = cariPanelPengajar(selectGuruMapel);
        if (!panel || panel.style.display === 'none') return;

        guruTerpilih(panel).forEach(function (g) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'ptk_ids[]';
            input.value = g.ptk_id;
            form.appendChild(input);
        });
    }

    function init(baseUrl) {
        BASE_URL = String(baseUrl || '').replace(/\/+$/, '');

        Object.keys(PANEL_GURU).forEach(function (selectId) {
            const panel = document.getElementById(PANEL_GURU[selectId]);
            if (!panel) return;

            panel.addEventListener('click', function (e) {
                const btnTambah = e.target.closest('.guru-pengajar-tambah');
                if (btnTambah) {
                    const sel = panel.querySelector('.guru-pengajar-pilih');
                    if (sel && sel.value) tambahGuruPengajar(panel, sel.value);
                    return;
                }
                const btnHapus = e.target.closest('.guru-pengajar-hapus');
                if (btnHapus) {
                    const tag = btnHapus.closest('[data-ptk]');
                    if (tag) tag.remove();
                    renderTagGuru(panel);
                }
            });
        });

        return api;
    }

    const api = {
        init: init,
        cariPanelPengajar: cariPanelPengajar,
        muatDaftarGuru: muatDaftarGuru,
        resetGuruPengajar: resetGuruPengajar,
        isiGuruPengajarDariData: isiGuruPengajarDariData,
        syncGuruPengajarInputs: syncGuruPengajarInputs
    };

    window.SAELaboranPengajar = api;
})();
