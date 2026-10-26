/**
 * Modul Kebersihan Lingkungan Produktif Jurusan (Tupoksi #9).
 * Bergantung pada window.SAELaboran (laboran-common.js).
 */
document.addEventListener('DOMContentLoaded', function () {
    if (!window.location.pathname.includes('/dashboard/laboran/kebersihan')) return;

    const L = window.SAELaboran;
    if (!L) return;

    const BASE = '/dashboard/laboran/kebersihan';

    const modal = document.getElementById('modalKebersihan');
    const form = document.getElementById('formKebersihan');
    const title = document.getElementById('modalKebersihanTitle');
    const method = document.getElementById('kebersihanMethod');
    const btnOpen = document.getElementById('btnOpenKebersihan');

    function today() {
        const now = new Date();
        return new Date(now.getTime() - now.getTimezoneOffset() * 60000).toISOString().split('T')[0];
    }

    if (btnOpen) {
        btnOpen.addEventListener('click', function () {
            if (form) form.reset();
            if (form) form.action = BASE;
            if (method) method.value = 'POST';
            if (title) title.textContent = 'Catat Kegiatan Kebersihan';
            const tgl = document.getElementById('kbTanggal');
            if (tgl) tgl.value = today();
            L.openModal(modal);
        });
    }

    /**
     * Ubah textarea baris-per-baris menjadi field array dokumentasi_paths[].
     */
    function kirimDokumentasi(formEl) {
        const ta = formEl.querySelector('#kbDokumentasi');
        if (!ta) return;

        // Hapus input array lama
        formEl.querySelectorAll('input[data-dok-input]').forEach(function (n) { n.remove(); });

        const lines = (ta.value || '').split('\n').map(function (s) { return s.trim(); }).filter(Boolean);
        lines.forEach(function (p) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'dokumentasi_paths[]';
            input.value = p;
            input.setAttribute('data-dok-input', '1');
            formEl.appendChild(input);
        });

        // Textarea asli jangan dikirim sebagai array
        ta.removeAttribute('name');
    }

    document.addEventListener('click', function (e) {
        const editBtn = e.target.closest('.btn-edit-kebersihan');
        if (editBtn && form) {
            form.reset();
            form.action = BASE + '/' + editBtn.dataset.id;
            if (method) method.value = 'PUT';
            if (title) title.textContent = 'Ubah Catatan Kebersihan';

            const set = function (id, val) {
                const el = document.getElementById(id);
                if (el) el.value = val != null ? val : '';
            };

            set('kbTanggal', editBtn.dataset.tanggal);
            set('kbArea', editBtn.dataset.area);
            set('kbLokasi', editBtn.dataset.lokasi);
            set('kbJenis', editBtn.dataset.jenis);
            set('kbUraian', editBtn.dataset.uraian);
            set('kbPeralatan', editBtn.dataset.peralatan);
            set('kbBahan', editBtn.dataset.bahan);
            set('kbPetugas', editBtn.dataset.petugas);
            set('kbNamaPetugas', editBtn.dataset.namaPetugas);
            set('kbKondisi', editBtn.dataset.kondisi);
            set('kbTemuan', editBtn.dataset.temuan);
            set('kbTindak', editBtn.dataset.tindak);
            set('kbStatus', editBtn.dataset.status);

            // dokumentasi_paths tersimpan sebagai JSON string
            const dok = document.getElementById('kbDokumentasi');
            if (dok) {
                let arr = [];
                try { arr = JSON.parse(editBtn.dataset.dokumentasi || '[]') || []; } catch (err) { arr = []; }
                dok.value = arr.join('\n');
            }

            L.openModal(modal);
            return;
        }

        const delBtn = e.target.closest('.btn-delete-kebersihan');
        if (delBtn) {
            L.deleteData(BASE + '/' + delBtn.dataset.id, delBtn.dataset.nama || 'catatan ini');
        }
    });

    if (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            kirimDokumentasi(form);
            L.submitForm(form, {
                onSuccess: function (data) {
                    L.closeModal(modal);
                    L.notifySuccess(data.message || 'Catatan kebersihan berhasil disimpan.');
                }
            });
        });
    }
});
