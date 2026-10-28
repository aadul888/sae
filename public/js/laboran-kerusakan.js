/**
 * Modul Berita Acara Kerusakan Alat.
 * Bergantung pada window.SAELaboran (laboran-common.js).
 *
 * Form mendukung LEBIH DARI SATU alat per berita acara. Alat disimpan dulu,
 * lalu tombol cetak tersedia pada daftar (tidak membuka tab cetak otomatis).
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

    const body = document.getElementById('krAlatBody');
    const empty = document.getElementById('krAlatEmpty');
    const tpl = document.getElementById('krAlatTemplate');
    const btnTambah = document.getElementById('krTambahAlat');

    function today() {
        const now = new Date();
        return new Date(now.getTime() - now.getTimezoneOffset() * 60000).toISOString().split('T')[0];
    }

    function tingkatDefault() {
        const el = document.getElementById('krTingkat');
        return el ? el.value : 'ringan';
    }

    function renumber() {
        if (!body) return;
        Array.prototype.forEach.call(body.querySelectorAll('.kr-alat-row'), function (row, i) {
            row.querySelectorAll('[name]').forEach(function (el) {
                el.name = el.name.replace(/alat\[\d+\]/, 'alat[' + i + ']');
            });
        });
        if (empty) empty.style.display = body.children.length ? 'none' : '';
    }

    function addRow(data) {
        if (!body || !tpl) return;
        const idx = body.children.length;

        const html = tpl.innerHTML.replace(/__IDX__/g, String(idx));
        const holder = document.createElement('tbody');
        holder.innerHTML = html.trim();
        const row = holder.firstElementChild;
        if (!row) return;

        const sel = row.querySelector('.kr-alat-item');
        const nama = row.querySelector('.kr-alat-nama');
        const jumlah = row.querySelector('.kr-alat-jumlah');
        const satuan = row.querySelector('.kr-alat-satuan');
        const tingkat = row.querySelector('.kr-alat-tingkat');

        if (data) {
            if (sel) sel.value = data.item_id != null ? String(data.item_id) : '';
            if (nama) nama.value = data.nama_alat || '';
            if (jumlah) jumlah.value = data.jumlah_rusak != null ? data.jumlah_rusak : 1;
            if (satuan) satuan.value = data.satuan || 'unit';
            if (tingkat) tingkat.value = data.tingkat_kerusakan || tingkatDefault();
        } else if (tingkat) {
            tingkat.value = tingkatDefault();
        }

        // Sinkronkan nama/satuan dari pilihan inventaris.
        if (sel) {
            sel.addEventListener('change', function () {
                const opt = this.options[this.selectedIndex];
                if (!opt || !opt.value) return;
                if (nama && !nama.value.trim()) nama.value = opt.dataset.nama || '';
                if (satuan && opt.dataset.satuan) satuan.value = opt.dataset.satuan;
            });
        }

        row.querySelector('.kr-alat-hapus')?.addEventListener('click', function () {
            row.remove();
            renumber();
        });

        body.appendChild(row);
        renumber();
    }

    if (btnTambah) {
        btnTambah.addEventListener('click', function () { addRow(null); });
    }

    function openCreate() {
        if (!form) return;
        form.reset();
        form.action = BASE;
        if (method) method.value = 'POST';
        if (title) title.textContent = 'Berita Acara Kerusakan Alat';
        if (catatanWrap) catatanWrap.style.display = 'none';
        if (body) body.innerHTML = '';

        const tgl = document.getElementById('krKejadian');
        if (tgl) tgl.value = today();
        const ba = document.getElementById('krBa');
        if (ba) ba.value = today();

        addRow(null);
        L.openModal(modal);
    }

    function openEdit(d) {
        if (!form) return;
        form.reset();
        form.action = BASE + '/' + d.id;
        if (method) method.value = 'PUT';
        if (title) title.textContent = 'Ubah Berita Acara Kerusakan';
        if (catatanWrap) catatanWrap.style.display = '';
        if (body) body.innerHTML = '';

        const set = function (id, val) {
            const el = document.getElementById(id);
            if (el) el.value = val != null ? val : '';
        };

        set('krLokasi', d.lokasi);
        set('krKejadian', d.kejadian);
        set('krBa', d.ba);
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

        let alat = [];
        try { alat = JSON.parse(d.alat || '[]'); } catch (e) { alat = []; }
        if (!Array.isArray(alat) || !alat.length) alat = [null];
        alat.forEach(function (a) { addRow(a); });

        L.openModal(modal);
    }

    if (btnOpen) btnOpen.addEventListener('click', openCreate);

    if (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();

            const rows = body ? Array.prototype.slice.call(body.querySelectorAll('.kr-alat-row')) : [];
            const terisi = rows.some(function (row) {
                const sel = row.querySelector('.kr-alat-item');
                const nama = row.querySelector('.kr-alat-nama');
                return (sel && sel.value) || (nama && nama.value.trim());
            });
            if (!terisi) {
                L.notifyError('Data Belum Lengkap', 'Pilih minimal satu alat yang dilaporkan.');
                return;
            }

            L.submitForm(form, {
                onSuccess: function (data) {
                    L.closeModal(modal);

                    // Berita acara tersimpan lebih dulu; cetak hanya ditawarkan
                    // setelah data benar-benar tersimpan di server.
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil Disimpan',
                        text: data.message || 'Berita acara tersimpan. Anda dapat mencetaknya sekarang.',
                        showCancelButton: true,
                        confirmButtonText: '<i class="fas fa-print me-1"></i> Cetak Sekarang',
                        cancelButtonText: 'Nanti',
                        confirmButtonColor: '#2563eb',
                        cancelButtonColor: '#6b7280',
                    }).then(function (res) {
                        if (res.isConfirmed && data.id) {
                            window.open(BASE + '/' + data.id + '/cetak', '_blank');
                        }
                        window.location.reload();
                    });
                }
            });
        });
    }

    document.addEventListener('click', function (e) {
        const editBtn = e.target.closest('.btn-edit-kerusakan');
        if (editBtn) {
            openEdit({
                id: editBtn.dataset.id,
                alat: editBtn.dataset.alat,
                lokasi: editBtn.dataset.lokasi,
                kejadian: editBtn.dataset.kejadian,
                ba: editBtn.dataset.ba,
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
