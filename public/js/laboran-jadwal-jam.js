/**
 * JavaScript Modul Jadwal Praktik Laboran — Jam Pelajaran (JP).
 *
 * Berisi seluruh logika jam: daftar slot JP per hari, ringkasan jam tersedia,
 * dan pembatasan pilihan "Jam ke-N s/d Jam ke-M" agar mengikuti Jadwal KBM
 * milik mapel yang dipilih. Dipisah dari `laboran-jadwal.js` agar berkas tetap
 * ringkas dan tanggung jawabnya jelas.
 *
 * Dipakai lewat `window.SAELaboranJam`.
 */
(function (window) {
    'use strict';

    let BASE = '/dashboard/laboran/jadwal';
    let slotPerHariCache = null;
    let slotPerHariRequest = null;

    function init(baseUrl) {
        if (baseUrl) BASE = baseUrl;
        return api;
    }

    /**
     * Isi pilihan Jam Pelajaran. Rentang boleh mencakup beberapa JP
     * (mis. Jam ke-1 s/d Jam ke-4), jadi kedua dropdown memuat daftar slot yang
     * sama dan pilihan selesai tidak lagi dipaksa mengikuti pilihan mulai.
     */
    function fillJamKeSlots(selectMulai, selectSelesai, slots, preselectMulai, preselectSelesai) {
        if (!selectMulai) return;

        selectMulai.innerHTML = '';
        if (selectSelesai) selectSelesai.innerHTML = '';

        const keys = Object.keys(slots || {}).filter(function (ke) {
            const s = slots[ke];
            // Lewati slot rutinitas (Upacara, Pembiasaan, Istirahat) — bukan jam praktik.
            return !s || !(s.is_break || s.is_upacara || s.is_pembiasaan || s.schedulable === false);
        });

        if (keys.length === 0) {
            for (let k = 1; k <= 10; k++) {
                selectMulai.appendChild(new Option('Jam ke-' + k, k));
                if (selectSelesai) selectSelesai.appendChild(new Option('Jam ke-' + k, k));
            }
            selectMulai.value = preselectMulai || '1';
            if (selectSelesai) selectSelesai.value = preselectSelesai || selectMulai.value;
            return;
        }

        keys.forEach(function (ke) {
            const s = slots[ke];
            const label = 'Jam ke-' + s.ke + ' (' + s.mulai + ' - ' + s.selesai + ')';
            selectMulai.appendChild(new Option(label, s.ke));
            if (selectSelesai) selectSelesai.appendChild(new Option(label, s.ke));
        });

        if (preselectMulai) selectMulai.value = String(preselectMulai);
        if (selectSelesai) {
            if (preselectSelesai) selectSelesai.value = String(preselectSelesai);
            else if (preselectMulai) selectSelesai.value = String(preselectMulai);
        }
    }

    /**
     * Jaga agar "Jam Selesai" tidak pernah lebih kecil dari "Jam Mulai".
     * Rentang beberapa JP tetap diperbolehkan (mis. 1 s/d 4).
     */
    function jagaRentangJam(selectMulai, selectSelesai) {
        if (!selectMulai || !selectSelesai) return;

        const sinkron = function () {
            const mulai = Number(selectMulai.value || 0);
            const selesai = Number(selectSelesai.value || 0);
            if (mulai > 0 && selesai < mulai) selectSelesai.value = String(mulai);
        };

        selectMulai.addEventListener('change', function () {
            // Jam selesai belum dipilih: ikut jam mulai agar tidak kosong.
            if (!selectSelesai.value) selectSelesai.value = selectMulai.value || '';
            sinkron();
        });
        selectSelesai.addEventListener('change', sinkron);
        sinkron();
    }

    /**
     * Pastikan slot satu hari sudah tersedia (memakai cache; ambil sekali saja).
     * @returns {Promise<Object>} peta { jamKe: {ke, mulai, selesai, schedulable, ...} }
     */
    function pastikanSlotHari(hari) {
        hari = hari || 'Senin';

        if (slotPerHariCache && slotPerHariCache[hari]) {
            return Promise.resolve(slotPerHariCache[hari]);
        }

        if (!slotPerHariRequest) {
            slotPerHariRequest = fetch(BASE + '/slots', {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                credentials: 'same-origin'
            })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    slotPerHariCache = (data && data.per_hari) || {};
                    return slotPerHariCache;
                })
                .catch(function () {
                    slotPerHariCache = slotPerHariCache || {};
                    return slotPerHariCache;
                });
        }

        return slotPerHariRequest.then(function (perHari) {
            return (perHari && perHari[hari]) || {};
        });
    }

    function loadSlotsForHari(hari, selectMulai, selectSelesai, preselectMulai, preselectSelesai) {
        pastikanSlotHari(hari).then(function (slots) {
            fillJamKeSlots(selectMulai, selectSelesai, slots, preselectMulai, preselectSelesai);
        });
    }

    /**
     * Ringkas daftar jam berurutan: [2,3,4,9] => "ke-2–ke-4, ke-9".
     */
    function ringkasJamKe(jam) {
        const arr = (jam || []).map(Number).filter(function (n) { return n > 0; })
            .sort(function (a, b) { return a - b; });
        if (arr.length === 0) return '';

        const rentang = [];
        let awal = null;
        let sebelum = null;
        arr.forEach(function (k) {
            if (awal === null) { awal = sebelum = k; return; }
            if (k === sebelum + 1) { sebelum = k; return; }
            rentang.push(awal === sebelum ? 'ke-' + awal : 'ke-' + awal + '\u2013ke-' + sebelum);
            awal = sebelum = k;
        });
        if (awal !== null) {
            rentang.push(awal === sebelum ? 'ke-' + awal : 'ke-' + awal + '\u2013ke-' + sebelum);
        }
        return rentang.join(', ');
    }

    /**
     * Jam KBM milik satu opsi guru & mapel.
     * `jamKe` = seluruh JP mapel tsb, `jamTersedia` = JP yang belum terpakai
     * praktik pada tanggal terpilih, `blok` = rentang per blok KBM (mis. [2,4]).
     */
    function jamKbmDariOpsi(selectGuruMapel) {
        const kosong = { jamKe: [], jamTersedia: [], blok: [] };
        if (!selectGuruMapel || selectGuruMapel.selectedIndex < 0) return kosong;

        const opt = selectGuruMapel.options[selectGuruMapel.selectedIndex];
        if (!opt || !opt.value) return kosong;

        const parse = function (raw) {
            if (!raw) return [];
            try {
                const v = JSON.parse(raw);
                return Array.isArray(v) ? v : [];
            } catch (e) {
                return [];
            }
        };

        const jamKe = parse(opt.dataset.jamKe);
        const jamTersedia = parse(opt.dataset.jamTersedia);

        return {
            jamKe: jamKe,
            jamTersedia: jamTersedia.length ? jamTersedia : jamKe,
            blok: parse(opt.dataset.jamKeBlok)
        };
    }

    /**
     * Isi pilihan "Jam ke-N s/d Jam ke-M" pada modal.
     *
     * SEMENTARA: pembatasan ke jam KBM mapel DIMATIKAN (selaras dengan
     * `PAKSA_VALIDASI_KBM = false` di controller). SELURUH slot hari itu
     * ditampilkan agar laboran bebas memilih. Blok jam KBM mapel tetap dipakai
     * sebagai titik awal praktis, bukan pengunci pilihan.
     *
     * @param {HTMLSelectElement} selectGuruMapel dropdown guru & mapel
     * @param {string} hari nama hari aktif
     * @param {HTMLSelectElement} selectMulai dropdown "Jam Mulai"
     * @param {HTMLSelectElement} selectSelesai dropdown "Jam Selesai"
     * @param {number|string} [jamAwal] jam yang diinginkan (mis. sel grid yang diklik)
     * @param {number|string} [jamAkhir] jam akhir yang diinginkan
     */
    function terapkanJamKbm(selectGuruMapel, hari, selectMulai, selectSelesai, jamAwal, jamAkhir) {
        if (!selectMulai) return;

        pastikanSlotHari(hari).then(function (semua) {
            const info = jamKbmDariOpsi(selectGuruMapel);
            // Semua slot hari itu selalu boleh dipilih.
            const slots = semua;

            const adaDalamSlots = function (v) {
                return v !== '' && v !== null && v !== undefined
                    && Object.prototype.hasOwnProperty.call(slots, String(v));
            };
            const blokMemuat = function (v) {
                return info.blok.find(function (b) {
                    return Number(v) >= Number(b.mulai) && Number(v) <= Number(b.selesai);
                }) || null;
            };

            // Jam yang diinginkan (mis. dari sel grid) bila tersedia di hari itu.
            let preMulai = (jamAwal !== undefined && jamAwal !== null && jamAwal !== '')
                ? String(jamAwal) : (selectMulai.value || '');
            let preSelesai = (jamAkhir !== undefined && jamAkhir !== null && jamAkhir !== '')
                ? String(jamAkhir) : (selectSelesai ? (selectSelesai.value || '') : '');

            if (!adaDalamSlots(preMulai)) {
                // Titik awal praktis: jam awal blok KBM mapel bila ada, jika tidak
                // slot pertama hari itu.
                const awalanBlok = info.blok.length ? String(info.blok[0].mulai) : '';
                preMulai = adaDalamSlots(awalanBlok) ? awalanBlok : (Object.keys(slots)[0] || '');
                preSelesai = '';
            }

            const blok = preMulai ? blokMemuat(preMulai) : null;
            if (!adaDalamSlots(preSelesai) || Number(preSelesai) < Number(preMulai)) {
                // Isi otomatis sampai akhir blok KBM mapel, agar rentang multi-JP
                // (mis. Jam ke-2 s/d Jam ke-4) langsung terbentuk. Tetap boleh
                // diubah laboran ke jam mana pun pada hari itu.
                preSelesai = blok ? String(blok.selesai) : preMulai;
                if (!adaDalamSlots(preSelesai)) preSelesai = preMulai;
            }
            // Jangan melampaui akhir blok KBM mapel ini.
            if (blok && Number(preSelesai) > Number(blok.selesai)) {
                preSelesai = String(blok.selesai);
            }
            if (blok && Number(preSelesai) < Number(blok.mulai)) {
                preSelesai = String(blok.selesai);
            }

            fillJamKeSlots(selectMulai, selectSelesai, slots, preMulai, preSelesai);
        });
    }


    /**
     * Terjemahkan rentang jam (mis. 08:00–09:30) menjadi nomor JP pada hari tsb.
     * @returns {Promise<{mulai:string, akhir:string}>} string kosong bila tak ketemu
     */
    function jamKeDariWaktu(hari, jamMulai, jamSelesai) {
        return pastikanSlotHari(hari).then(function (slots) {
            const mulai = String(jamMulai || '').slice(0, 5);
            const selesai = String(jamSelesai || '').slice(0, 5);
            if (!mulai || !selesai) return { mulai: '', akhir: '' };

            let awal = '';
            let akhir = '';
            Object.keys(slots).forEach(function (k) {
                const s = slots[k];
                if (!s) return;
                if (s.mulai === mulai) awal = String(s.ke || k);
                if (s.selesai === selesai) akhir = String(s.ke || k);
            });

            return { mulai: awal, akhir: akhir || awal };
        });
    }
    const api = {
        init: init,
        fillJamKeSlots: fillJamKeSlots,
        jagaRentangJam: jagaRentangJam,
        pastikanSlotHari: pastikanSlotHari,
        loadSlotsForHari: loadSlotsForHari,
        ringkasJamKe: ringkasJamKe,
        jamKbmDariOpsi: jamKbmDariOpsi,
        terapkanJamKbm: terapkanJamKbm,
        jamKeDariWaktu: jamKeDariWaktu
    };

    window.SAELaboranJam = api;
})(window);