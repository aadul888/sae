/**
 * SAE Searchable Select
 * Mengubah <select data-searchable> menjadi dropdown yang bisa dicari saat
 * diketik. Elemen <select> asli tetap dipakai sebagai penyimpan nilai
 * (name/value) sehingga JS lain yang membaca .value atau mendengarkan
 * event 'change' tetap berjalan seperti biasa.
 */
(function () {
    'use strict';

    var OPEN_CLASS = 'is-open';
    var styleInjected = false;

    function injectStyle() {
        if (styleInjected) return;
        styleInjected = true;
        var style = document.createElement('style');
        style.id = 'sae-searchable-select-style';
        style.textContent = [
            '.sae-searchable-select{position:relative;width:100%;}',
            '.sae-searchable-select__list{display:none;position:absolute;left:0;right:0;top:calc(100% + 4px);',
            'background:var(--card-bg,#fff);border:1px solid var(--border-color,#e2e8f0);border-radius:var(--radius-md,8px);',
            'box-shadow:0 10px 24px rgba(0,0,0,.18);max-height:220px;overflow-y:auto;z-index:1000;padding:4px;}',
            '.sae-searchable-select.is-open .sae-searchable-select__list{display:block;}',
            '.sae-searchable-select__option{padding:8px 10px;border-radius:var(--radius-sm,6px);font-size:var(--text-sm,.8125rem);',
            'color:var(--text-color,#1e293b);cursor:pointer;white-space:normal;line-height:1.35;}',
            '.sae-searchable-select__option:hover,.sae-searchable-select__option.is-active{background:rgba(99,102,241,.1);color:var(--primary,#6366f1);}',
            '.sae-searchable-select__option.is-selected{font-weight:700;}',
            '.sae-searchable-select__empty{padding:8px 10px;font-size:var(--text-sm,.8125rem);color:var(--text-muted,#94a3b8);font-style:italic;}',
            '.sae-searchable-select__input{cursor:pointer;}',
            '.sae-searchable-select.is-open .sae-searchable-select__input{cursor:text;}'
        ].join('');
        document.head.appendChild(style);
    }

    function initSelect(select) {
        if (!select || select.dataset.searchableInit === '1') return;
        if (select.tagName !== 'SELECT' || select.multiple || select.disabled) return;
        select.dataset.searchableInit = '1';

        injectStyle();

        var wasRequired = select.required;
        var placeholderOption = null;
        for (var i = 0; i < select.options.length; i++) {
            if (select.options[i].value === '') { placeholderOption = select.options[i]; break; }
        }
        // Validasi hanya perlu bila ada opsi kosong (placeholder); select tanpa
        // placeholder selalu punya nilai default, jadi input tak boleh memblokir.
        var needsValidation = !!wasRequired && !!placeholderOption;

        var wrapper = document.createElement('div');
        wrapper.className = 'sae-searchable-select';

        var input = document.createElement('input');
        input.type = 'text';
        input.autocomplete = 'off';
        input.setAttribute('role', 'combobox');
        input.setAttribute('aria-expanded', 'false');
        input.className = (select.className || '') + ' sae-searchable-select__input';
        if (select.getAttribute('style')) input.setAttribute('style', select.getAttribute('style'));
        input.placeholder = select.dataset.searchablePlaceholder ||
            (placeholderOption ? placeholderOption.textContent.trim() : 'Ketik untuk mencari...');
        if (needsValidation) input.required = true;

        var list = document.createElement('div');
        list.className = 'sae-searchable-select__list';

        select.parentNode.insertBefore(wrapper, select);
        wrapper.appendChild(select);
        wrapper.appendChild(input);
        wrapper.appendChild(list);

        select.style.display = 'none';
        select.required = false;

        var filtered = [];
        var activeIndex = -1;

        function syncInput() {
            var match = null;
            for (var i = 0; i < select.options.length; i++) {
                if (select.options[i].value === select.value && select.options[i].value !== '') {
                    match = select.options[i];
                    break;
                }
            }
            input.value = match ? match.textContent.trim() : '';
        }

        // Selaraskan tampilan saat JS lain mengubah select.value langsung.
        var proto = Object.getOwnPropertyDescriptor(HTMLSelectElement.prototype, 'value');
        if (proto) {
            Object.defineProperty(select, 'value', {
                configurable: true,
                enumerable: proto.enumerable,
                get: function () { return proto.get.call(this); },
                set: function (val) { proto.set.call(this, val); syncInput(); }
            });
        }

        function buildFiltered(query) {
            var q = (query || '').trim().toLowerCase();
            filtered = [];
            for (var i = 0; i < select.options.length; i++) {
                var opt = select.options[i];
                if (opt.disabled) continue;
                var text = opt.textContent.trim();
                if (q && text.toLowerCase().indexOf(q) === -1 && opt.value.toLowerCase().indexOf(q) === -1) continue;
                filtered.push(opt);
            }
            return filtered;
        }

        function renderList() {
            list.innerHTML = '';
            if (!filtered.length) {
                var empty = document.createElement('div');
                empty.className = 'sae-searchable-select__empty';
                empty.textContent = 'Tidak ada opsi yang cocok';
                list.appendChild(empty);
                return;
            }
            filtered.forEach(function (opt, idx) {
                var item = document.createElement('div');
                item.className = 'sae-searchable-select__option';
                if (opt.value === select.value) item.classList.add('is-selected');
                if (idx === activeIndex) item.classList.add('is-active');
                item.textContent = opt.textContent.trim();
                item.addEventListener('mousedown', function (e) {
                    e.preventDefault();
                    pick(opt);
                });
                item.addEventListener('mouseenter', function () {
                    activeIndex = idx;
                    renderList();
                });
                list.appendChild(item);
            });
        }

        function openList() {
            buildFiltered(input.value);
            activeIndex = -1;
            wrapper.classList.add(OPEN_CLASS);
            input.setAttribute('aria-expanded', 'true');
            renderList();
        }

        function closeList() {
            wrapper.classList.remove(OPEN_CLASS);
            input.setAttribute('aria-expanded', 'false');
            activeIndex = -1;
        }

        function pick(opt) {
            if (opt.value === '') {
                select.value = '';
            } else {
                select.value = opt.value;
            }
            select.dispatchEvent(new Event('change', { bubbles: true }));
            syncInput();
            closeList();
            input.focus();
        }

        input.addEventListener('focus', openList);
        input.addEventListener('click', openList);
        input.addEventListener('input', function () {
            openList();
        });

        input.addEventListener('keydown', function (e) {
            if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
                e.preventDefault();
                if (!wrapper.classList.contains(OPEN_CLASS)) { openList(); return; }
                if (!filtered.length) return;
                activeIndex = e.key === 'ArrowDown'
                    ? (activeIndex + 1) % filtered.length
                    : (activeIndex - 1 + filtered.length) % filtered.length;
                renderList();
                var active = list.querySelector('.is-active');
                if (active && active.scrollIntoView) active.scrollIntoView({ block: 'nearest' });
            } else if (e.key === 'Enter') {
                if (wrapper.classList.contains(OPEN_CLASS) && filtered.length) {
                    e.preventDefault();
                    pick(filtered[activeIndex >= 0 ? activeIndex : 0]);
                }
            } else if (e.key === 'Escape') {
                syncInput();
                closeList();
            } else if (e.key === 'Tab') {
                closeList();
            }
        });

        select.addEventListener('change', syncInput);
        input.addEventListener('blur', function () {
            // Simpan jeda agar klik opsi tetap terproses.
            setTimeout(function () {
                if (!wrapper.contains(document.activeElement)) {
                    syncInput();
                    closeList();
                }
            }, 120);
        });

        document.addEventListener('click', function (e) {
            if (!wrapper.contains(e.target)) closeList();
        });

        var form = select.form;
        if (form) {
            form.addEventListener('reset', function () {
                setTimeout(syncInput, 0);
            });
        }
        if (form && needsValidation) {
            form.addEventListener('submit', function (e) {
                if (select.value === '') {
                    e.preventDefault();
                    input.setCustomValidity('Silakan pilih salah satu opsi dari daftar.');
                    input.reportValidity();
                    openList();
                } else {
                    input.setCustomValidity('');
                }
            });
        }
        input.addEventListener('input', function () { input.setCustomValidity(''); });

        syncInput();
    }

    function initAll(root) {
        var scope = root || document;
        scope.querySelectorAll('select[data-searchable]').forEach(initSelect);
    }

    window.SAESearchableSelect = { init: initAll, initSelect: initSelect };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { initAll(document); });
    } else {
        initAll(document);
    }
})();
