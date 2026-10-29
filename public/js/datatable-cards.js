/**
 * Kartu datatable versi mobile (<= 768px) — gaya "ringkas lalu buka".
 *
 * Pada layar kecil setiap baris datatable dirender sebagai kartu. Agar tidak
 * memanjang, kartu hanya menampilkan teks utama (sel "hero": nama/judul/nomor).
 * Sel detail lain disembunyikan dan baru muncul setelah kartu diketuk.
 *
 * Markup tidak perlu diubah: kelas `dt-card-*` dipasang otomatis di sini dan
 * dipasang ulang lewat MutationObserver saat datatable diganti (live search,
 * pagination AJAX, dsb). Di layar >= 768px semua kelas dibersihkan lagi agar
 * tabel kembali normal.
 */
(function () {
    'use strict';

    var MOBILE_QUERY = '(max-width: 768px)';

    /** Label kolom yang dianggap sebagai teks utama kartu. */
    var HERO_LABELS = [
        'nama',
        'nama lengkap',
        'nama data',
        'nama pegawai',
        'nama barang',
        'barang',
        'judul',
        'agenda',
        'sasaran',
        'kegiatan',
        'uraian',
        'mata pelajaran',
        'nomor',
        'nomor wo',
        'nomor wo & tgl',
        'kode',
        'tanggal',
    ];

    /** Label kolom yang tidak layak menjadi teks utama. */
    var SKIP_LABELS = ['no', 'no.', 'n0.', 'aksi', 'action', '#'];

    var ITEM_SELECTOR = 'a, button, input, select, textarea, label, form, [onclick]';

    function normalize(text) {
        return (text || '')
            .replace(/\s+/g, ' ')
            .replace(/[:：]\s*$/, '')
            .trim()
            .toLowerCase();
    }

    function cellLabel(td) {
        return normalize(td.getAttribute('data-label'));
    }

    /** Salin label kolom dari <thead> untuk sel yang belum punya data-label. */
    function syncLabels(tr) {
        var table = tr.closest('table');
        if (!table) return;
        // Header bisa bertingkat (rowspan/colspan); pakai baris terakhir yang
        // sejajar dengan kolom data.
        var headRows = table.querySelectorAll('thead tr');
        if (!headRows.length) return;
        var headRow = headRows[headRows.length - 1];

        var headers = Array.prototype.filter.call(headRow.children, function (el) {
            return el.tagName === 'TH';
        });
        if (!headers.length) return;

        var tds = Array.prototype.filter.call(tr.children, function (el) {
            return el.tagName === 'TD';
        });
        // Jumlah kolom header harus sama agar pemetaan index tidak bergeser.
        if (headers.length !== tds.length) return;

        tds.forEach(function (td, i) {
            if (td.hasAttribute('data-label')) return;
            var th = headers[i];
            if (!th) return;
            var text = (th.textContent || '').replace(/\s+/g, ' ').trim();
            if (!text) return;
            td.setAttribute('data-label', text);
            td.setAttribute('data-dt-generated', '1');
        });
    }

    function removeGeneratedLabels(tr) {
        tr.querySelectorAll('td[data-dt-generated]').forEach(function (td) {
            td.removeAttribute('data-dt-generated');
            td.removeAttribute('data-label');
        });
    }

    function looksLikeIndex(td) {
        // Angka murni (nomor urut) — kandidat lemah sebagai teks utama.
        return /^\d{1,4}$/.test((td.textContent || '').trim().replace(/[.\s]/g, ''));
    }

    function isActionCell(td) {
        if (/^(aksi|action)$/.test(cellLabel(td))) return true;
        if (td.querySelector('.table-actions')) return true;
        // Sel yang isinya hanya tombol/form dianggap kolom aksi.
        var text = (td.textContent || '').replace(/\s+/g, '');
        return text === '' && !!td.querySelector('button, form');
    }

    function hasCheckbox(td) {
        return !!td.querySelector('input[type="checkbox"], input[type="radio"]');
    }

    function pickHero(tds) {
        var hero = tds.find(function (td) {
            return td.classList.contains('cell-pd-nama');
        });
        if (hero) return hero;

        hero = tds.find(function (td) {
            return HERO_LABELS.indexOf(cellLabel(td)) !== -1;
        });
        if (hero) return hero;

        // Tidak ada label yang cocok (tabel tanpa data-label): pilih sel dengan
        // isi teks paling informatif, bukan kolom nomor urut.
        var best = null;
        var bestScore = -1;
        tds.forEach(function (td, i) {
            if (hasCheckbox(td) || isActionCell(td)) return;
            var label = cellLabel(td);
            if (label && SKIP_LABELS.indexOf(label) !== -1) return;
            if (looksLikeIndex(td)) return;

            var text = (td.textContent || '').replace(/\s+/g, ' ').trim();
            if (!text) return;

            var score = Math.min(text.length, 80);
            // Sel dengan judul tebal / nomor monospace lebih layak jadi utama.
            if (td.querySelector('strong, b, .badge, [style*="font-weight:700"], [style*="font-weight: 700"]')) score += 25;
            if (td.querySelector('[style*="monospace"]')) score += 15;
            // Sedikit penalti untuk kolom paling kiri agar nomor urut tidak menang.
            if (i === 0) score -= 10;

            if (score > bestScore) {
                bestScore = score;
                best = td;
            }
        });
        if (best) return best;

        return tds.find(function (td) {
            return !hasCheckbox(td);
        }) || tds[0] || null;
    }

    function ensureToggle(td) {
        var toggle = td.querySelector(':scope > .dt-card-toggle');
        if (toggle) return toggle;

        toggle = document.createElement('button');
        toggle.type = 'button';
        toggle.className = 'dt-card-toggle';
        toggle.setAttribute('aria-expanded', 'false');
        toggle.setAttribute('aria-label', 'Tampilkan detail baris');
        toggle.title = 'Tampilkan detail';
        toggle.innerHTML = '<i class="fas fa-chevron-down" aria-hidden="true"></i>';
        td.appendChild(toggle);
        return toggle;
    }

    function removeToggle(tr) {
        tr.querySelectorAll('.dt-card-toggle').forEach(function (el) {
            el.remove();
        });
    }

    function markRow(tr) {
        var tds = Array.prototype.filter.call(tr.children, function (el) {
            return el.tagName === 'TD';
        });
        if (!tds.length) return;
        // Baris kosong / colspan tidak dijadikan kartu.
        if (tds.some(function (td) { return td.hasAttribute('colspan'); })) return;

        syncLabels(tr);

        var hero = pickHero(tds);
        if (!hero) return;

        var hasCheck = false;
        var actionCell = null;

        tds.forEach(function (td, i) {
            var isHero = td === hero;
            // Kolom centang (pilih baris) tetap terlihat agar aksi massal
            // masih bisa dipakai tanpa membuka detail.
            var isCheck = !isHero && hasCheckbox(td) && !cellLabel(td);
            // Kolom aksi tidak boleh dianggap nomor urut walau labelnya "Aksi".
            var isAction = !isHero && !isCheck && isActionCell(td);
            // Kolom nomor urut tidak perlu ditampilkan di kartu: hanya untuk
            // label "No"/"Aksi", atau sel tanpa label berisi angka di kolom awal
            // (jangan sampai kolom metrik seperti "Jumlah" ikut tersembunyi).
            var isIndex = !isHero && !isCheck && !isAction &&
                (SKIP_LABELS.indexOf(cellLabel(td)) !== -1 ||
                    (!cellLabel(td) && i <= 1 && looksLikeIndex(td)));

            if (isCheck) hasCheck = true;
            if (isAction) actionCell = td;

            td.classList.toggle('dt-card-hero', isHero);
            td.classList.toggle('dt-card-check', isCheck);
            td.classList.toggle('dt-card-index', isIndex);
            td.classList.toggle('dt-card-detail', !isHero && !isCheck && !isIndex);
        });

        tr.classList.add('dt-card');
        tr.classList.toggle('dt-card-has-check', hasCheck);

        if (actionCell) actionCell.classList.add('dt-card-actions');
        else tds.forEach(function (td) { td.classList.remove('dt-card-actions'); });

        var toggle = ensureToggle(hero);
        var open = tr.classList.contains('dt-card-open');
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        toggle.title = open ? 'Sembunyikan detail' : 'Tampilkan detail';
        toggle.setAttribute('aria-label', toggle.title);
        if (hero.classList.contains('dt-card-hero')) {
            hero.classList.add('dt-card-hero--interactive');
        }
    }

    function unmarkRow(tr) {
        tr.classList.remove('dt-card', 'dt-card-open', 'dt-card-has-check');
        removeToggle(tr);
        tr.querySelectorAll(
            '.dt-card-hero, .dt-card-detail, .dt-card-check, .dt-card-index, .dt-card-actions, .dt-card-hero--interactive'
        ).forEach(function (td) {
            td.classList.remove(
                'dt-card-hero', 'dt-card-detail', 'dt-card-check', 'dt-card-index', 'dt-card-actions',
                'dt-card-hero--interactive'
            );
        });
        removeGeneratedLabels(tr);
    }

    function isMobile() {
        return window.matchMedia(MOBILE_QUERY).matches;
    }

    function scan(root) {
        var scope = root && root.querySelectorAll ? root : document;
        var rows = scope.querySelectorAll('.table-responsive-stack tbody tr, .table-pd tbody tr');

        rows.forEach(function (tr) {
            // Hanya baris data (bukan baris header/no-data) di dalam datatable.
            if (tr.closest('thead')) return;
            if (!tr.closest('.table-responsive-stack, .table-pd')) return;

            if (isMobile()) {
                markRow(tr);
            } else if (tr.classList.contains('dt-card')) {
                unmarkRow(tr);
            }
        });
    }

    function toggleCard(tr, force) {
        var open = typeof force === 'boolean' ? force : !tr.classList.contains('dt-card-open');
        tr.classList.toggle('dt-card-open', open);
        var toggle = tr.querySelector('.dt-card-toggle');
        if (toggle) {
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            toggle.title = open ? 'Sembunyikan detail' : 'Tampilkan detail';
            toggle.setAttribute('aria-label', toggle.title);
        }
    }

    function onDocumentClick(event) {
        if (!isMobile()) return;

        var target = event.target;
        if (!(target instanceof Element)) return;

        var tr = target.closest('tr.dt-card');
        if (!tr) return;

        if (target.closest('.dt-card-toggle')) {
            event.preventDefault();
            toggleCard(tr);
            return;
        }

        // Jangan menutup/membuka saat pengguna berinteraksi dengan kontrol.
        if (target.closest(ITEM_SELECTOR)) return;
        if (!target.closest('td.dt-card-hero')) return;

        toggleCard(tr);
    }

    function init() {
        scan(document);

        document.addEventListener('click', onDocumentClick);

        // Datatable bisa diganti (live search, pagination AJAX) — pasang ulang.
        if (window.MutationObserver) {
            var pending = null;
            var observer = new MutationObserver(function (mutations) {
                var needScan = mutations.some(function (m) {
                    return m.addedNodes && m.addedNodes.length > 0;
                });
                if (!needScan) return;
                if (pending) return;
                pending = window.requestAnimationFrame(function () {
                    pending = null;
                    scan(document);
                });
            });
            observer.observe(document.body, { childList: true, subtree: true });
        }

        // Beralih antara mode kartu & tabel penuh.
        var mq = window.matchMedia(MOBILE_QUERY);
        var onChange = function () { scan(document); };
        if (mq.addEventListener) mq.addEventListener('change', onChange);
        else if (mq.addListener) mq.addListener(onChange);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    window.SAE = window.SAE || {};
    window.SAE.datatableCards = { scan: scan, toggle: toggleCard, isMobile: isMobile };
})();
