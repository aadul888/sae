/**
 * Peminjaman Sarpras (Teknisi) — halaman sendiri, terpisah dari peminjaman jurusan.
 * Bergantung pada window.SAE (sae.js) & modal generik modul Teknisi.
 */
document.addEventListener('DOMContentLoaded', function () {
    if (!window.location.pathname.includes('/dashboard/teknisi/peminjaman')) return;

    var SAE = window.SAE || {};
    var BASE = '/dashboard/teknisi/peminjaman';

    function csrf() {
        var m = document.querySelector('meta[name="csrf-token"]');
        if (m) return m.getAttribute('content');
        var t = document.querySelector('input[name="_token"]');
        return t ? t.value : '';
    }

    function toast(msg, type) {
        if (SAE && typeof SAE.toast === 'function') SAE.toast(msg, type || 'info');
        else alert(msg);
    }

    // ---- Modal ajukan: aktifkan autocomplete peminjam sesuai tipe ----
    var tipe = document.getElementById('tpTipe');
    var nama = document.getElementById('tpNama');
    var KIND = { siswa: 'siswa', guru: 'gtk', tendik: 'gtk', umum: 'all' };

    function syncKind() {
        if (!tipe || !nama) return;
        nama.dataset.peopleKind = KIND[tipe.value] || 'all';
    }
    if (tipe) {
        tipe.addEventListener('change', syncKind);
        syncKind();
    }

    // ---- Kirim form ajukan via fetch ----
    var form = document.querySelector('#modalPeminjaman [data-modal-form]');
    if (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();

            var checked = form.querySelectorAll('.tp-item:checked');
            if (checked.length === 0) {
                toast('Pilih minimal satu ruang atau aset yang akan dipinjam.', 'warning');
                return;
            }

            var btn = form.querySelector('button[type="submit"]');
            if (btn) btn.disabled = true;

            fetch(form.action, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' },
                credentials: 'same-origin',
                body: new FormData(form)
            })
                .then(function (r) { return r.json().then(function (d) { return { ok: r.ok, data: d }; }); })
                .then(function (res) {
                    if (!res.ok) {
                        toast((res.data && (res.data.message || (res.data.errors && Object.values(res.data.errors)[0][0]))) || 'Gagal menyimpan peminjaman.', 'danger');
                        return;
                    }
                    var modal = document.getElementById('modalPeminjaman');
                    if (modal) modal.style.display = 'none';
                    toast((res.data && res.data.message) || 'Pengajuan peminjaman berhasil dibuat.', 'success');
                    setTimeout(function () {
                        if (res.data && res.data.redirect) window.location.href = res.data.redirect;
                        else window.location.reload();
                    }, 600);
                })
                .catch(function () { toast('Terjadi kesalahan saat menyimpan.', 'danger'); })
                .finally(function () { if (btn) btn.disabled = false; });
        });
    }

    // ---- Ubah status (setujui / serah terima) ----
    function ubahStatus(id, status, label, btn) {
        if (btn) btn.disabled = true;
        fetch(BASE + '/' + id + '/status', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf(),
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            credentials: 'same-origin',
            body: JSON.stringify({ _method: 'PUT', status: status })
        })
            .then(function (r) { return r.json().then(function (d) { return { ok: r.ok, data: d }; }); })
            .then(function (res) {
                if (!res.ok) { toast((res.data && res.data.message) || 'Gagal memperbarui status.', 'danger'); return; }
                toast((res.data && res.data.message) || ('Status berubah menjadi ' + label + '.'), 'success');
                setTimeout(function () { window.location.reload(); }, 600);
            })
            .catch(function () { toast('Gagal memperbarui status.', 'danger'); })
            .finally(function () { if (btn) btn.disabled = false; });
    }

    document.addEventListener('click', function (e) {
        var setujui = e.target.closest('.btn-pinjam-setujui');
        if (setujui) {
            var cb = function (ok) { if (ok) ubahStatus(setujui.dataset.id, 'disetujui', 'Disetujui', setujui); };
            if (SAE.confirm) SAE.confirm('Setujui pengajuan dari "' + (setujui.dataset.nama || 'peminjam') + '"?', 'Setujui Peminjaman?', 'info', 'Ya, Setujui').then(cb);
            else cb(window.confirm('Setujui peminjaman ini?'));
            return;
        }

        var serah = e.target.closest('.btn-pinjam-serah');
        if (serah) {
            var cb2 = function (ok) { if (ok) ubahStatus(serah.dataset.id, 'dipinjam', 'Dipinjam', serah); };
            if (SAE.confirm) SAE.confirm('Serahkan kepada "' + (serah.dataset.nama || 'peminjam') + '"? Aset akan tercatat dipinjam.', 'Serah Terima?', 'info', 'Ya, Serahkan').then(cb2);
            else cb2(window.confirm('Serah terima?'));
            return;
        }

        var kembali = e.target.closest('.btn-pinjam-kembali');
        if (kembali) {
            var modal = document.getElementById('modalKembali');
            var formK = document.getElementById('formKembaliPeminjaman');
            var desc = document.getElementById('kembaliDesc');
            if (formK) formK.action = BASE + '/' + kembali.dataset.id + '/kembalikan';
            if (desc) desc.textContent = 'Mencatat pengembalian dari: ' + (kembali.dataset.nama || '—');
            if (modal) modal.style.display = 'flex';
            return;
        }

        var hapus = e.target.closest('.btn-pinjam-hapus');
        if (hapus) {
            var doDel = function () {
                fetch(BASE + '/' + hapus.dataset.id, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                    body: new URLSearchParams({ _method: 'DELETE' })
                })
                    .then(function (r) { return r.json().then(function (d) { return { ok: r.ok, data: d }; }); })
                    .then(function (res) {
                        toast((res.data && res.data.message) || (res.ok ? 'Data dihapus.' : 'Gagal menghapus.'), res.ok ? 'success' : 'danger');
                        if (res.ok) setTimeout(function () { window.location.reload(); }, 600);
                    })
                    .catch(function () { toast('Gagal menghapus data.', 'danger'); });
            };
            if (SAE.confirm) SAE.confirm('Hapus peminjaman "' + (hapus.dataset.nama || '') + '"?', 'Hapus Data?', 'danger', 'Ya, Hapus').then(function (ok) { if (ok) doDel(); });
            else doDel();
        }
    });

    // ---- Form pengembalian ----
    var formKembali = document.getElementById('formKembaliPeminjaman');
    if (formKembali) {
        formKembali.addEventListener('submit', function (e) {
            e.preventDefault();
            var btn = formKembali.querySelector('button[type="submit"]');
            if (btn) btn.disabled = true;

            fetch(formKembali.action, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' },
                credentials: 'same-origin',
                body: new FormData(formKembali)
            })
                .then(function (r) { return r.json().then(function (d) { return { ok: r.ok, data: d }; }); })
                .then(function (res) {
                    if (!res.ok) { toast((res.data && res.data.message) || 'Gagal mencatat pengembalian.', 'danger'); return; }
                    var modal = document.getElementById('modalKembali');
                    if (modal) modal.style.display = 'none';
                    toast((res.data && res.data.message) || 'Pengembalian berhasil dicatat.', 'success');
                    setTimeout(function () { window.location.reload(); }, 600);
                })
                .catch(function () { toast('Gagal mencatat pengembalian.', 'danger'); })
                .finally(function () { if (btn) btn.disabled = false; });
        });
    }
});
