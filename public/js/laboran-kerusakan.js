/**
 * Modul Berita Acara Kerusakan Alat.
 * Bergantung pada window.SAELaboran (laboran-common.js).
 */
document.addEventListener('DOMContentLoaded', function () {
    if (!window.location.pathname.includes('/dashboard/laboran/kerusakan')) return;

    const L = window.SAELaboran;
    if (!L) return;

    const BASE = '/dashboard/laboran/kerusakan';

    const modal = document.getElementById('modalKerusakan');
    const form = document.getElementById('formKerusakan');
    const title = document.getElementById('modalKerusakanTitle');
    const method = document.getElementById('kerusakanMethod');
    const btnOpen = document.getElementById('btnOpenKerusakan');
    const catatanWrap = document.getElementById('krCatatanWrap');

    function today() {
        const now = new Date();
        return new Date(now.getTime() - now.getTimezoneOffset() * 60000).toISOString().split('T')[0];
    }

    function openCreate() {
        if (!form) return;
        form.reset();
        form.action = BASE;
        if (method) method.value = 'POST';
        if (title) title.textContent = 'Berita Acara Kerusakan Alat';
        if (catatanWrap) catatanWrap.style.display = 'none';

        const tgl = document.getElementById('krKejadian');
        if (tgl) tgl.value = today();
        const ba = document.getElementById('krBa');
        if (ba) ba.value = today();

        L.openModal(modal);
    }

    function openEdit(d) {
        if (!form) return;
        form.reset();
        form.action = BASE + '/' + d.id;
        if (method) method.value = 'PUT';
        if (title) title.textContent = 'Ubah Berita Acara Kerusakan';
        if (catatanWrap) catatanWrap.style.display = '';

        const set = function (id, val) {
            const el = document.getElementById(id);
            if (el) el.value = val != null ? val : '';
        };

        set('krItem', d.item);
        set('krNama', d.nama);
        set('krLokasi', d.lokasi);
        set('krKejadian', d.kejadian);
        set('krBa', d.ba);
        set('krJumlah', d.jumlah);
        set('krSatuan', d.satuan);
        set('krTingkat', d.tingkat);
        set('krKronologi', d.kronologi);
        set('krPenyebab', d.penyebab);
        set('krPelakuTipe', d.pelakuTipe);
        set('krPelakuNama', d.pelakuNama);
        set('krSaksi1', d.saksi1);
        set('krSaksi2', d.saksi2);
        set('krTindakan', d.tindakan);
        set('krBiaya', d.biaya);
        set('krStatus', d.status);
        set('krCatatan', d.catatan);

        const perlu = document.getElementById('krPerlu');
        if (perlu) perlu.checked = String(d.perlu) === '1';

        L.openModal(modal);
    }

    if (btnOpen) btnOpen.addEventListener('click', openCreate);

    // Sinkronkan nama alat dari pilihan inventaris
    const selItem = document.getElementById('krItem');
    if (selItem) {
        selItem.addEventListener('change', function () {
            const opt = this.options[this.selectedIndex];
            if (!opt || !opt.value) return;

            const match = (opt.textContent || '').match(/^(.*?)\s*\((.*?)\)\s*$/);
            if (match) {
                const nama = document.getElementById('krNama');
                if (nama && !nama.value) nama.value = match[1].trim();
            }
        });
    }

    if (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            L.submitForm(form, {
                onSuccess: function (data) {
                    L.closeModal(modal);
                    if (data && data.redirect) {
                        // Buka cetak BA di tab baru, lalu muat ulang daftar
                        window.open(data.redirect, '_blank');
                        L.notifySuccess(data.message || 'Berita acara berhasil dibuat.');
                    } else {
                        L.notifySuccess(data.message || 'Berita acara berhasil disimpan.');
                    }
                }
            });
        });
    }

    document.addEventListener('click', function (e) {
        const editBtn = e.target.closest('.btn-edit-kerusakan');
        if (editBtn) {
            openEdit({
                id: editBtn.dataset.id,
                item: editBtn.dataset.item,
                nama: editBtn.dataset.nama,
                kode: editBtn.dataset.kode,
                lokasi: editBtn.dataset.lokasi,
                kejadian: editBtn.dataset.kejadian,
                ba: editBtn.dataset.ba,
                jumlah: editBtn.dataset.jumlah,
                satuan: editBtn.dataset.satuan,
                tingkat: editBtn.dataset.tingkat,
                kronologi: editBtn.dataset.kronologi,
                penyebab: editBtn.dataset.penyebab,
                pelakuTipe: editBtn.dataset.pelakuTipe,
                pelakuNama: editBtn.dataset.pelakuNama,
                saksi1: editBtn.dataset.saksi1,
                saksi2: editBtn.dataset.saksi2,
                tindakan: editBtn.dataset.tindakan,
                biaya: editBtn.dataset.biaya,
                perlu: editBtn.dataset.perlu,
                status: editBtn.dataset.status,
                catatan: editBtn.dataset.catatan
            });
            return;
        }

        const delBtn = e.target.closest('.btn-delete-kerusakan');
        if (delBtn) {
            L.deleteData(BASE + '/' + delBtn.dataset.id, delBtn.dataset.nama || 'berita acara ini');
        }
    });
});
