// Self-check modal-drag.js: emulasi minimal DOM + pointer event.
// Jalankan: node tests/modal-drag.check.js
const assert = require('assert');
const fs = require('fs');
const path = require('path');

const listeners = {};
const docListeners = {};
function makeEl(tag, parent) {
    const el = {
        tagName: tag.toUpperCase(),
        parentElement: parent || null,
        dataset: {},
        style: {},
        children: [],
        classList: { _s: new Set(), add(c) { this._s.add(c); }, contains(c) { return this._s.has(c); }, remove(c) { this._s.delete(c); } },
        _attrs: {},
        setAttribute(k, v) { this._attrs[k] = v; },
        getAttribute(k) { return this._attrs[k]; },
        appendChild(c) { this.children.push(c); c.parentElement = this; return c; },
        addEventListener(t, fn) { (listeners[this._id] ||= {})[t] = fn; },
        setPointerCapture() {},
        querySelector(sel) {
            // dukungan terbatas: ':scope > .card' / ':scope > .modal-card' / ':scope > div'
            if (sel.includes('.card')) return this.children.find((c) => c.classList.contains('card')) || null;
            if (sel === ':scope > div') return this.children.find((c) => c.tagName === 'DIV') || null;
            return null;
        },
        matches() { return false; },
        closest(sel) {
            let n = this;
            while (n) {
                if (sel.includes('modal-backdrop') && n.classList.contains('modal-backdrop')) return n;
                if (sel.includes('.card') && n.classList.contains('card')) return n;
                if (sel.includes('div') && n.tagName === 'DIV' && n.classList.contains('modal-backdrop') === false) {
                    // 'div' polos: cocokkan node div pertama (header)
                    return n;
                }
                n = n.parentElement;
            }
            return null;
        },
        contains() { return false; },
    };
    return el;
}

const backdrop = makeEl('div');
backdrop.classList.add('modal-backdrop');
const card = makeEl('div', backdrop);
card.classList.add('card');
const header = makeEl('div', card);
header.classList.add('modal-header');
header.closest = function (sel) {
    if (sel.includes('.modal-backdrop')) return backdrop;
    if (sel.includes('.card')) return card;
    return this;
};
backdrop.appendChild(card);
card.appendChild(header);

global.document = {
    body: { style: {}, appendChild() {} },
    addEventListener(t, fn) { docListeners[t] = fn; },
    querySelectorAll() { return []; },
    getElementById() { return null; },
};
global.window = { innerWidth: 1400, innerHeight: 900, addEventListener() {} };
global.MutationObserver = function () { this.observe = () => {}; };

// muat skrip
const src = fs.readFileSync(path.join(__dirname, '..', 'public', 'js', 'modal-drag.js'), 'utf8');
new Function('window', 'document', 'MutationObserver', src)(global.window, global.document, global.MutationObserver);

// pointerdown di header, geser 120x60
const fakeTarget = {
    closest(sel) {
        if (sel.includes('button')) return null;
        if (sel.includes('modal-backdrop')) return header; // header.closest menangani sisanya
        return header;
    },
};
docListeners.pointerdown({ button: 0, target: fakeTarget, clientX: 100, clientY: 100, pointerId: 1, preventDefault() {} });
docListeners.pointermove({ clientX: 220, clientY: 160 });
assert.strictEqual(card.style.transform, 'translate(120px,60px)', 'transform setelah drag');
docListeners.pointerup();
assert.strictEqual(card.dataset.dragX, '120');
assert.strictEqual(card.dataset.dragY, '60');

// clamp: geser sangat jauh tidak melebihi setengah layar
docListeners.pointerdown({ button: 0, target: fakeTarget, clientX: 0, clientY: 0, pointerId: 1, preventDefault() {} });
docListeners.pointermove({ clientX: 100000, clientY: 100000 });
const x = parseFloat(card.dataset.dragX);
const y = parseFloat(card.dataset.dragY);
assert.ok(x <= 700 && y <= 450, 'clamp batas layar');
docListeners.pointerup();

// reset via window.SAE.modal
window.SAE.modal.reset(backdrop);
assert.strictEqual(card.style.transform, '', 'reset membersihkan transform');

// mobile: drag diabaikan
global.window.innerWidth = 480;
docListeners.pointerdown({ button: 0, target: fakeTarget, clientX: 0, clientY: 0, pointerId: 1, preventDefault() {} });
docListeners.pointermove({ clientX: 200, clientY: 200 });
assert.strictEqual(card.style.transform, '', 'drag nonaktif di mobile');

console.log('OK modal-drag: drag, clamp, reset, mobile');
