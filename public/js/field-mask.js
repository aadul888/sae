/**
 * Penjaga tipe input (global semua formulir): field numerik tidak menerima teks biasa.
 * Mode mengikuti atribut inputmode (numeric | decimal | tel) atau daftar nama field
 * yang memang numerik. Karakter di luar mode dibuang saat diketik/di-paste.
 */
(function () {
    'use strict';

    const DIGITS = /[^0-9]/g;
    const DECIMAL = /[^0-9.,-]/g;
    const TEL = /[^0-9+\-() ]/g;

    // ponytail: kurasi nama field, bukan deteksi otomatis semantik. Tambah nama baru bila perlu.
    const NAMED_MODES = {
        rt: 'numeric',
        rw: 'numeric',
        kode_pos: 'numeric',
        nik: 'numeric',
        nik_ayah: 'numeric',
        nik_ibu: 'numeric',
        nik_wali: 'numeric',
        no_kk: 'numeric',
        nisn: 'numeric',
        nipd: 'numeric',
        nomor_telepon_rumah: 'tel',
        nomor_telepon_seluler: 'tel',
        nomor_hp: 'tel',
        telepon: 'tel',
        hp: 'tel',
        lintang: 'decimal',
        bujur: 'decimal',
        anak_keberapa: 'numeric',
        jumlah_saudara_kandung: 'numeric',
        tinggi_badan: 'numeric',
        berat_badan: 'numeric',
        lingkar_kepala: 'numeric',
        waktu_tempuh_jam: 'numeric',
        waktu_tempuh_menit: 'numeric',
    };

    const MAXLENGTH = {
        rt: 3,
        rw: 3,
        kode_pos: 5,
        nik: 16,
        nik_ayah: 16,
        nik_ibu: 16,
        nik_wali: 16,
        no_kk: 16,
        nisn: 10,
    };

    function sanitize(value, mode) {
        if (mode === 'numeric') return value.replace(DIGITS, '');
        if (mode === 'decimal') return value.replace(DECIMAL, '').replace(/(?!^)-/g, '');
        if (mode === 'tel') return value.replace(TEL, '');
        return value;
    }

    function resolveMode(el) {
        if (el.type === 'number' || el.type === 'email') return null;
        const mode = String(el.getAttribute('inputmode') || '').toLowerCase();
        if (mode === 'numeric' || mode === 'decimal' || mode === 'tel') return mode;
        return NAMED_MODES[el.name] || null;
    }

    function apply(el) {
        const mode = resolveMode(el);
        if (!mode) return;

        el.setAttribute('inputmode', mode);

        const max = MAXLENGTH[el.name];
        if (max) el.setAttribute('maxlength', String(max));

        el.addEventListener('input', function () {
            const clean = sanitize(el.value, mode);
            if (clean !== el.value) el.value = clean;
            if (max && el.value.length > max) el.value = el.value.slice(0, max);
        });
    }

    function init() {
        document.querySelectorAll('input').forEach(apply);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
