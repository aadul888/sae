/**
 * Validasi form: tampilkan pesan error langsung di samping field terkait (global
 * untuk semua formulir di layout dashboard & publik), bukan sebagai banner di atas.
 * - Menandai field error (border merah + kelas is-invalid).
 * - Menyisipkan pesan tepat setelah field (atau setelah grup radio/checkbox).
 * - Menggulir + memfokuskan field pertama yang error.
 * - Menyembunyikan banner ringkasan error lama yang isinya pesan-pesan tersebut.
 * Sumber data: <script id="saeFormErrors" type="application/json"> (@json($errors->messages())).
 */
(function () {
    'use strict';

    function readErrors() {
        const holder = document.getElementById('saeFormErrors');
        if (!holder) return {};
        try {
            return JSON.parse(holder.textContent) || {};
        } catch (e) {
            return {};
        }
    }

    function normalizeName(name) {
        return String(name || '').replace(/\[([^\]]*)\]/g, '.$1');
    }

    function matchesField(el, key) {
        return el.name === key || normalizeName(el.name) === key;
    }

    function findField(key) {
        if (!key) return null;

        // Prioritaskan field di form POST (punya _token) agar tidak salah sasaran
        // ke kolom pencarian/filter GET yang kebetulan bernama sama.
        const forms = document.querySelectorAll('form');
        for (let i = 0; i < forms.length; i++) {
            if (!forms[i].querySelector('input[name="_token"]')) continue;
            const fields = forms[i].querySelectorAll('[name]');
            for (let j = 0; j < fields.length; j++) {
                if (matchesField(fields[j], key)) return fields[j];
            }
        }

        // Fallback: seluruh dokumen.
        const all = document.querySelectorAll('[name]');
        for (let i = 0; i < all.length; i++) {
            if (matchesField(all[i], key)) return all[i];
        }
        return null;
    }

    function reveal(el) {
        if (!el) return false;

        // Buka tab/pane tertutup yang membungkus field (Bootstrap & kustom).
        const pane = el.closest('.tab-pane');
        if (pane && pane.id && !pane.classList.contains('active')) {
            const trigger = document.querySelector(
                '[data-bs-target="#' + pane.id + '"], [href="#' + pane.id + '"], [data-target="#' + pane.id + '"]'
            );
            if (trigger) trigger.click();
        }

        // Buka lipatan kustom di sepanjang leluhur (mis. .identitas-form-section.is-collapsed).
        let node = el.parentElement;
        while (node && node !== document.body) {
            if (node.classList && node.classList.contains('is-collapsed')) {
                node.classList.remove('is-collapsed');
                const header = node.querySelector('[aria-expanded]');
                if (header) header.setAttribute('aria-expanded', 'true');
            }
            node = node.parentElement;
        }

        el.scrollIntoView({ behavior: 'smooth', block: 'center' });
        try {
            el.focus({ preventScroll: true });
        } catch (e) {
            try { el.focus(); } catch (err) { /* field tidak dapat difokuskan */ }
        }

        setTimeout(function () {
            el.style.outline = '';
            el.style.outlineOffset = '';
        }, 2500);
        return true;
    }

    function insertInlineError(el, messages) {
        if (!el) return;

        el.classList.add('is-invalid');
        if (!el.style.borderColor) {
            el.style.borderColor = '#ef4444';
            el.addEventListener('input', function once() {
                el.style.borderColor = '';
                el.classList.remove('is-invalid');
                el.removeEventListener('input', once);
            });
        }

        const isChoice = el.type === 'radio' || el.type === 'checkbox';
        const anchor = isChoice ? (el.closest('label')?.parentElement || el) : el;

        // Jangan duplikat bila view sudah merender pesan inline (mis. @error).
        if (anchor.nextElementSibling && anchor.nextElementSibling.classList.contains('sae-field-error')) return;

        const box = document.createElement('div');
        box.className = 'sae-field-error';
        box.setAttribute('role', 'alert');
        box.style.cssText = 'color:#dc2626;font-size:0.75rem;line-height:1.35;margin-top:3px;font-weight:600;';
        box.textContent = messages.filter(Boolean).join(' ');
        anchor.insertAdjacentElement('afterend', box);
    }

    function hideSummaryBanners(pairs) {
        if (!pairs.length) return;
        document.querySelectorAll('.alert-danger, .alert.alert-danger').forEach(function (banner) {
            const text = String(banner.textContent || '').replace(/\s+/g, ' ');
            const hit = pairs.some(function (p) {
                return p.text && text.indexOf(p.text) !== -1;
            });
            if (hit) banner.style.display = 'none';
        });
    }

    function init() {
        const errors = readErrors();
        const keys = Object.keys(errors);
        if (!keys.length) return;

        const pairs = [];
        keys.forEach(function (key) {
            (errors[key] || []).forEach(function (msg) {
                pairs.push({ key: key, text: String(msg).replace(/\s+/g, ' ').trim() });
            });
        });

        let firstField = null;
        keys.forEach(function (key) {
            const el = findField(key);
            if (!el) return;
            insertInlineError(el, errors[key] || []);
            if (!firstField) firstField = el;
        });

        hideSummaryBanners(pairs);

        if (firstField) {
            setTimeout(function () {
                firstField.style.outline = '2px solid #ef4444';
                firstField.style.outlineOffset = '1px';
                reveal(firstField);
            }, 200);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
