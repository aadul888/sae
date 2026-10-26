/**
 * Modul Perawatan / Pemeliharaan / Penataan Alat (K3) + Kartu Perawatan.
 * Bergantung pada window.SAELaboran (laboran-common.js).
 */
document.addEventListener('DOMContentLoaded', function () {
    if (!window.location.pathname.includes('/dashboard/laboran/perawatan')) return;

    const L = window.SAELaboran;
    if (!L) return;

    const BASE = '/dashboard/laboran/perawatan';

    // =====================================================================
    // 1. CATATAN PERAWATAN
    // =====================================================================
    const modalPrw = document.getElementById('modalPerawatan');
    const formPrw = document.getElementById('formPerawatan');
    const titlePrw = document.getElementById('modalPerawatanTitle');
    const methodPrw = document.getElementById('perawatanMethod');

    const btnOpenPrw = document.getElementById('btnOpenPerawatan');
    const btnClosePrw = document.getElementById('btnClosePerawatan');
    const btnCancelPrw = document.getElementById('btnCancelPerawatan');

    function today() {
        const now = new Date();
        return new Date(now.getTime() - now.getTimezoneOffset() * 60000).toISOString().split('T')[0];
    }

    function resetApd(selector) {
        document.querySelectorAll(selector).forEach(function (cb) { cb.checked = false; });
    }

    function setApd(selector, list) {
        resetApd(selector);
        (list || []).forEach(function (val) {
            document.querySelectorAll(selector).forEach(function (cb) {
                if (cb.value === val) cb.checked = true;
            });
        });
    }

    function openCreatePrw() {
        if (!formPrw) return;
        formPrw.reset();
        formPrw.action = BASE;
        if (methodPrw) methodPrw.value = 'POST';
        if (titlePrw) titlePrw.textContent = 'Catatan Perawatan Alat';

        const tgl = document.getElementById('prwTanggal');
        if (tgl) tgl.value = today();
        resetApd('.prw-apd');

        L.openModal(modalPrw);
    }

    function openEditPrw(d) {
        if (!formPrw) return;
        formPrw.reset();
        formPrw.action = BASE + '/' + d.id;
        if (methodPrw) methodPrw.value = 'PUT';
        if (titlePrw) titlePrw.textContent = 'Ubah Catatan Perawatan';

        const set = function (id, val) {
            const el = document.getElementById(id);
            if (el) el.value = val != null ? val : '';
        };

        set('prwItem', d.item);
        set('prwNama', d.nama);
        set('prwKode', d.kode);
        set('prwLokasi', d.lokasi);
        set('prwJenis', d.jenis);
        set('prwK3', d.k3);
        set('prwTanggal', d.tanggal);
        set('prwBerikut', d.berikut);
        set('prwPelaksana', d.pelaksana);
        set('prwUraian', d.uraian);
        set('prwProsedur', d.prosedur);
        set('prwHasil', d.hasil);
        set('prwKondisi', d.kondisi);
        set('prwBiaya', d.biaya);
        set('prwStatus', d.status);
        set('prwCatatan', d.catatan);

        // APD tersimpan sebagai JSON string pada data-apd
        let apd = [];
        try { apd = JSON.parse(d.apd || '[]'); } catch (e) { apd = []; }
        setApd('.prw-apd', apd);

        L.openModal(modalPrw);
    }

    if (btnOpenPrw) btnOpenPrw.addEventListener('click', openCreatePrw);
    if (btnClosePrw) btnClosePrw.addEventListener('click', function () { L.closeModal(modalPrw); });
    if (btnCancelPrw) btnCancelPrw.addEventListener('click', function () { L.closeModal(modalPrw); });

    // Sinkronkan nama/kode/lokasi saat memilih alat dari inventaris
    const selItem = document.getElementById('prwItem');
    if (selItem) {
        selItem.addEventListener('change', function () {
            const opt = this.options[this.selectedIndex];
            if (!opt || !opt.value) return;

            // Ambil nama & kode dari teks opsi "Nama (KODE)"
            const label = opt.textContent || '';
            const match = label.match(/^(.*?)\s*\((.*?)\)\s*$/);
            if (match) {
                const nama = document.getElementById('prwNama');
                const kode = document.getElementById('prwKode');
                if (nama && !nama.value) nama.value = match[1].trim();
                if (kode && !kode.value) kode.value = match[2].trim();
            }
        });
    }

    if (formPrw) {
        formPrw.addEventListener('submit', function (e) {
            e.preventDefault();
            L.submitForm(formPrw, {
                onSuccess: function (data) {
                    L.closeModal(modalPrw);
                    L.notifySuccess(data.message || 'Catatan perawatan berhasil disimpan.');
                }
            });
        });
    }

    // =====================================================================
    // 2. KARTU PERAWATAN
    // =====================================================================
    const modalKrt = document.getElementById('modalKartu');
    const formKrt = document.getElementById('formKartu');
    const titleKrt = document.getElementById('modalKartuTitle');
    const methodKrt = document.getElementById('kartuMethod');

    const btnOpenKrt = document.getElementById('btnOpenKartu');
    const btnCloseKrt = document.getElementById('btnCloseKartu');
    const btnCancelKrt = document.getElementById('btnCancelKartu');

    function openCreateKrt() {
        if (!formKrt) return;
        formKrt.reset();
        formKrt.action = BASE + '/kartu';
        if (methodKrt) methodKrt.value = 'POST';
        if (titleKrt) titleKrt.textContent = 'Kartu Perawatan Baru';

        const mulai = document.getElementById('krtMulai');
        if (mulai) mulai.value = today();
        const interval = document.getElementById('krtInterval');
        if (interval) interval.value = 30;
        resetApd('.krt-apd');

        L.openModal(modalKrt);
    }

    function openEditKrt(d) {
        if (!formKrt) return;
        formKrt.reset();
        formKrt.action = BASE + '/kartu/' + d.id;
        if (methodKrt) methodKrt.value = 'PUT';
        if (titleKrt) titleKrt.textContent = 'Ubah Kartu Perawatan';

        const set = function (id, val) {
            const el = document.getElementById(id);
            if (el) el.value = val != null ? val : '';
        };

        set('krtItem', d.item);
        set('krtNama', d.nama);
        set('krtKode', d.kode);
        set('krtLokasi', d.lokasi);
        set('krtMerk', d.merk);
        set('krtMulai', d.mulai);
        set('krtInterval', d.interval);
        set('krtPj', d.pj);
        set('krtInstruksi', d.instruksi);
        set('krtStatus', d.status);
        set('krtCatatan', d.catatan);

        L.openModal(modalKrt);
    }

    if (btnOpenKrt) btnOpenKrt.addEventListener('click', openCreateKrt);
    if (btnCloseKrt) btnCloseKrt.addEventListener('click', function () { L.closeModal(modalKrt); });
    if (btnCancelKrt) btnCancelKrt.addEventListener('click', function () { L.closeModal(modalKrt); });

    if (formKrt) {
        formKrt.addEventListener('submit', function (e) {
            e.preventDefault();
            L.submitForm(formKrt, {
                onSuccess: function (data) {
                    L.closeModal(modalKrt);
                    L.notifySuccess(data.message || 'Kartu perawatan berhasil disimpan.');
                }
            });
        });
    }

    // =====================================================================
    // 3. DELEGASI TOMBOL BARIS
    // =====================================================================
    document.addEventListener('click', function (e) {
        // Edit perawatan
        const editPrw = e.target.closest('.btn-edit-perawatan');
        if (editPrw) {
            openEditPrw({
                id: editPrw.dataset.id,
                item: editPrw.dataset.item,
                nama: editPrw.dataset.nama,
                kode: editPrw.dataset.kode,
                lokasi: editPrw.dataset.lokasi,
                jenis: editPrw.dataset.jenis,
                k3: editPrw.dataset.k3,
                tanggal: editPrw.dataset.tanggal,
                berikut: editPrw.dataset.berikut,
                pelaksana: editPrw.dataset.pelaksana,
                uraian: editPrw.dataset.uraian,
                prosedur: editPrw.dataset.prosedur,
                apd: editPrw.dataset.apd,
                hasil: editPrw.dataset.hasil,
                kondisi: editPrw.dataset.kondisi,
                biaya: editPrw.dataset.biaya,
                status: editPrw.dataset.status,
                catatan: editPrw.dataset.catatan
            });
            return;
        }

        // Hapus perawatan
        const delPrw = e.target.closest('.btn-delete-perawatan');
        if (delPrw) {
            L.deleteData(BASE + '/' + delPrw.dataset.id, delPrw.dataset.nama || 'catatan ini');
            return;
        }

        // Edit kartu
        const editKrt = e.target.closest('.btn-edit-kartu');
        if (editKrt) {
            openEditKrt({
                id: editKrt.dataset.id,
                item: editKrt.dataset.item,
                nama: editKrt.dataset.nama,
                kode: editKrt.dataset.kode,
                lokasi: editKrt.dataset.lokasi,
                merk: editKrt.dataset.merk,
                mulai: editKrt.dataset.mulai,
                interval: editKrt.dataset.interval,
                pj: editKrt.dataset.pj,
                instruksi: editKrt.dataset.instruksi,
                status: editKrt.dataset.status,
                catatan: editKrt.dataset.catatan
            });
            return;
        }

        // Hapus kartu
        const delKrt = e.target.closest('.btn-delete-kartu');
        if (delKrt) {
            L.deleteData(BASE + '/kartu/' + delKrt.dataset.id, delKrt.dataset.nama || 'kartu ini');
            return;
        }

        // Tandai kartu sudah dirawat
        const selesaiKrt = e.target.closest('.btn-selesai-kartu');
        if (selesaiKrt) {
            const id = selesaiKrt.dataset.id;
            const nama = selesaiKrt.dataset.nama || 'alat ini';

            const doPost = function () {
                fetch(BASE + '/kartu/' + id + '/selesai', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': L.csrfToken(),
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    credentials: 'same-origin'
                })
                    .then(async function (res) {
                        const data = await res.json().catch(function () { return {}; });
                        if (!res.ok) throw new Error(data.message || 'Gagal menandai perawatan.');
                        return data;
                    })
                    .then(function (data) {
                        L.notifySuccess(data.message || 'Perawatan ditandai selesai.');
                    })
                    .catch(function (err) {
                        L.notifyError('Gagal', err.message);
                    });
            };

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Tandai Sudah Dirawat?',
                    text: 'Jadwal berikutnya untuk "' + nama + '" akan digeser sesuai interval.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Tandai',
                    cancelButtonText: 'Batal'
                }).then(function (r) { if (r.isConfirmed) doPost(); });
            } else if (confirm('Tandai "' + nama + '" sudah dirawat?')) {
                doPost();
            }
        }
    });
});
