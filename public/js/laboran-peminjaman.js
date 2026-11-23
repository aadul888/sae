/**
 * Modul Peminjaman Alat Laboratorium (indeks + detail).
 * Bergantung pada window.SAELaboran (laboran-common.js).
 */
document.addEventListener('DOMContentLoaded', function () {
    const path = window.location.pathname;
    if (!path.includes('/dashboard/laboran/peminjaman-alat')) return;

    const L = window.SAELaboran;
    if (!L) return;

    const BASE = '/dashboard/laboran/peminjaman-alat';

    function today() {
        const now = new Date();
        return new Date(now.getTime() - now.getTimezoneOffset() * 60000).toISOString().split('T')[0];
    }

    function daysFromNow(n) {
        const d = new Date();
        d.setDate(d.getDate() + n);
        return new Date(d.getTime() - d.getTimezoneOffset() * 60000).toISOString().split('T')[0];
    }

    // =====================================================================
    // 1. Modal ajukan peminjaman
    // =====================================================================
    const modal = document.getElementById('modalPeminjaman');
    const form = document.getElementById('formPeminjaman');
    const btnOpen = document.getElementById('btnOpenPeminjaman');

    // Input peminjam (pjNama) memakai komponen live-search global
    // (window.SAEPeopleAutocomplete via data-people-search pada markup).
    // Saat hasil dipilih, Tipe/Kelas/ID terisi otomatis lewat
    // data-people-type-target / data-people-unit-target / data-people-target.

    // Hasil pencarian nama dibatasi sesuai "Tipe Peminjam" yang dipilih.
    const pjTipe = document.getElementById('pjTipe');
    const pjNama = document.getElementById('pjNama');
    const KIND_BY_TIPE = { siswa: 'siswa', guru: 'gtk', tendik: 'gtk', umum: 'all' };

    function syncPeminjamKind() {
        if (!pjTipe || !pjNama) return;
        const tipe = pjTipe.value;
        pjNama.dataset.peopleKind = KIND_BY_TIPE[tipe] || 'all';
        if (tipe === 'guru' || tipe === 'tendik') {
            pjNama.dataset.peopleGtkType = tipe;
        } else {
            delete pjNama.dataset.peopleGtkType;
        }
    }

    if (pjTipe) {
        pjTipe.addEventListener('change', syncPeminjamKind);
        syncPeminjamKind();
    }

    if (btnOpen) {
        btnOpen.addEventListener('click', function () {
            if (form) form.reset();
            syncPeminjamKind();
            const tgl = document.getElementById('pjTanggal');
            if (tgl) tgl.value = today();
            const kembali = document.getElementById('pjKembali');
            if (kembali) kembali.value = daysFromNow(3);
            const cariAlat = document.getElementById('pjAlatCari');
            if (cariAlat) cariAlat.dispatchEvent(new Event('input', { bubbles: true }));
            if (typeof window.resetAlatPeminjaman === 'function') window.resetAlatPeminjaman();
            L.openModal(modal);
        });
    }

    if (form) {
        // Jumlah alat selalu bilangan bulat: normalisasi saat diubah / blur.
        function bulatkanJumlah(input) {
            const min = parseInt(input.getAttribute('min'), 10) || 1;
            const max = parseInt(input.getAttribute('max'), 10) || 0;
            let v = parseInt(String(input.value).replace(/[^\d-]/g, ''), 10);
            if (!Number.isFinite(v)) v = min;
            if (v < min) v = min;
            if (max > 0 && v > max) v = max;
            input.value = String(v);
        }

        // Delegasi: input jumlah dibuat dinamis saat alat ditambahkan.
        form.addEventListener('change', function (e) {
            const el = e.target;
            if (el && el.matches && el.matches('input[type="number"][name^="item_jumlah"], input[type="number"][name^="aset_jumlah"]')) {
                bulatkanJumlah(el);
            }
        });

        form.addEventListener('submit', function (e) {
            e.preventDefault();

            // Normalisasi seluruh input jumlah sebelum dikirim.
            form.querySelectorAll('input[type="number"][name^="item_jumlah"], input[type="number"][name^="aset_jumlah"]')
                .forEach(function (input) {
                    if (!input.disabled) bulatkanJumlah(input);
                });

            // Validasi minimal satu alat/ruang dipilih
            const terpilihWrap = document.getElementById('pjAlatTerpilih');
            const adaTerpilih = terpilihWrap && terpilihWrap.children.length > 0;
            if (!adaTerpilih) {
                L.notifyError('Data Belum Lengkap', 'Cari lalu tambahkan minimal satu alat yang akan dipinjam.');
                return;
            }

            L.submitForm(form, {
                onSuccess: function (data) {
                    L.closeModal(modal);
                    if (data && data.redirect) {
                        window.location.href = data.redirect;
                    } else {
                        L.notifySuccess(data.message || 'Pengajuan peminjaman berhasil dibuat.');
                    }
                }
            });
        });
    }

    // =====================================================================
    // 1b. Combobox Lokasi Pengambilan (dropdown seperti nama peminjam)
    // =====================================================================
    (function initLokasiCombobox() {
        const input = document.getElementById('pjLokasi');
        const dataEl = document.getElementById('daftarLokasiPeminjaman');
        const wrap = input ? input.parentElement : null;
        if (!input || !dataEl || !wrap) return;

        let daftar = [];
        try {
            daftar = JSON.parse(dataEl.textContent || '[]') || [];
        } catch (e) {
            daftar = [];
        }
        if (!daftar.length) return;

        const results = document.createElement('div');
        results.className = 'people-autocomplete-results';
        results.setAttribute('role', 'listbox');
        wrap.appendChild(results);

        function hideResults() {
            results.classList.remove('is-open');
            results.replaceChildren();
        }

        function pilih(nilai) {
            input.value = nilai;
            hideResults();
            input.dispatchEvent(new Event('change', { bubbles: true }));
        }

        function render(items) {
            results.replaceChildren();
            if (!items.length) {
                const empty = document.createElement('div');
                empty.className = 'people-autocomplete-empty';
                empty.textContent = 'Lokasi tidak ditemukan — tetap bisa diketik manual.';
                results.appendChild(empty);
                results.classList.add('is-open');
                return;
            }
            items.forEach(function (nilai) {
                const option = document.createElement('button');
                option.type = 'button';
                option.setAttribute('role', 'option');
                option.className = 'people-autocomplete-option';
                const name = document.createElement('span');
                name.className = 'people-autocomplete-name';
                name.textContent = nilai;
                option.appendChild(name);
                option.addEventListener('click', function () { pilih(nilai); });
                results.appendChild(option);
            });
            results.classList.add('is-open');
        }

        function filter(q) {
            const term = (q || '').trim().toLowerCase();
            const items = term === '' ? daftar : daftar.filter(function (v) {
                return String(v).toLowerCase().includes(term);
            });
            render(items);
        }

        input.addEventListener('focus', function () { filter(input.value); });
        input.addEventListener('input', function () { filter(input.value); });
        input.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') { hideResults(); return; }
            if (e.key === 'ArrowDown') {
                const first = results.querySelector('button[role="option"]');
                if (first) { e.preventDefault(); first.focus(); }
            }
        });
        results.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') { hideResults(); input.focus(); }
        });
        document.addEventListener('click', function (e) {
            if (!wrap.contains(e.target)) hideResults();
        });
    })();

    // =====================================================================
    // 1c. Pemilih alat: cari -> tambah -> isi kuantitas
    // =====================================================================
    (function initAlatPicker() {
        const input = document.getElementById('pjAlatCari');
        const hasil = document.getElementById('pjAlatHasil');
        const terpilih = document.getElementById('pjAlatTerpilih');
        const kosong = document.getElementById('pjAlatKosong');
        const dataEl = document.getElementById('daftarSumberPeminjaman');
        if (!input || !hasil || !terpilih || !dataEl) return;

        const NAME_BY_TIPE = { lab: 'item_ids[]', aset: 'aset_ids[]', ruang: 'ruang_ids[]' };
        const JUMLAH_BY_TIPE = { lab: 'item_jumlah', aset: 'aset_jumlah', ruang: 'ruang_jumlah' };

        let daftar = [];
        try {
            daftar = JSON.parse(dataEl.textContent || '[]') || [];
        } catch (e) {
            daftar = [];
        }
        const ditambah = {};

        function kunci(s) { return s.tipe + ':' + s.id; }

        function perbaruiKosong() {
            const ada = Object.keys(ditambah).length > 0;
            if (kosong) kosong.style.display = ada ? 'none' : 'block';
        }

        function sembunyikanHasil() {
            hasil.classList.remove('is-open');
            hasil.replaceChildren();
        }

        function renderHasil(term) {
            hasil.replaceChildren();
            const t = term.trim().toLowerCase();
            if (t.length < 2) {
                sembunyikanHasil();
                return;
            }

            const cocok = daftar
                .filter(function (s) { return !ditambah[kunci(s)] && s.cari.indexOf(t) !== -1; })
                .slice(0, 30);

            if (!cocok.length) {
                const empty = document.createElement('div');
                empty.className = 'people-autocomplete-empty';
                empty.style.padding = '9px 12px';
                empty.textContent = 'Alat tidak ditemukan.';
                hasil.appendChild(empty);
                hasil.classList.add('is-open');
                return;
            }

            cocok.forEach(function (s) {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.setAttribute('role', 'option');
                btn.className = 'people-autocomplete-option';
                btn.disabled = s.stok <= 0;

                const nama = document.createElement('span');
                nama.className = 'people-autocomplete-name';
                nama.textContent = s.nama + (s.merk ? ' — ' + s.merk : '');
                const meta = document.createElement('span');
                meta.className = 'people-autocomplete-meta';
                meta.textContent = [s.label, s.kode, s.lokasi,
                    s.stok > 0 ? ('Stok: ' + s.stok + ' ' + (s.satuan || '')) : 'Stok habis']
                    .filter(Boolean).join(' • ');
                btn.appendChild(nama);
                btn.appendChild(meta);

                if (s.stok <= 0) btn.style.opacity = '.55';
                btn.addEventListener('click', function () { tambah(s); });
                hasil.appendChild(btn);
            });
            hasil.classList.add('is-open');
        }

        function tambah(s) {
            if (s.stok <= 0 || ditambah[kunci(s)]) return;
            const k = kunci(s);
            ditambah[k] = s;

            const row = document.createElement('div');
            row.className = 'people-autocomplete-selected';
            row.setAttribute('data-kunci', k);
            row.style.flexWrap = 'wrap';

            const info = document.createElement('div');
            info.style.cssText = 'flex: 1; min-width: 0;';
            const judul = document.createElement('div');
            judul.style.cssText = 'font-weight: var(--font-semibold);';
            judul.textContent = s.nama + (s.merk ? ' — ' + s.merk : '');
            const meta = document.createElement('div');
            meta.className = 'people-autocomplete-meta';
            meta.textContent = [s.label, s.kode, s.lokasi,
                'Stok: ' + s.stok + ' ' + (s.satuan || '')].filter(Boolean).join(' • ');
            info.appendChild(judul);
            info.appendChild(meta);

            if (s.tipe !== 'ruang') {
                const jumlah = document.createElement('input');
                jumlah.type = 'number';
                jumlah.step = '1';
                jumlah.min = '1';
                jumlah.max = String(Math.max(1, s.stok));
                jumlah.value = '1';
                jumlah.className = 'form-control';
                jumlah.name = JUMLAH_BY_TIPE[s.tipe] + '[' + s.id + ']';
                jumlah.style.cssText = 'width: 86px; height: 34px; font-size: var(--text-sm);';
                jumlah.title = 'Jumlah dipinjam';
                row.appendChild(info);
                row.appendChild(jumlah);
            } else {
                row.appendChild(info);
            }

            const hapus = document.createElement('button');
            hapus.type = 'button';
            hapus.className = 'btn btn-outline btn-sm';
            hapus.style.cssText = 'padding: 4px 9px;';
            hapus.title = 'Hapus dari daftar';
            hapus.innerHTML = '<i class="fas fa-xmark"></i>';
            hapus.addEventListener('click', function () {
                delete ditambah[k];
                row.remove();
                perbaruiKosong();
            });
            row.appendChild(hapus);

            // Input tersembunyi ID (nama field selaras validasi backend).
            const id = document.createElement('input');
            id.type = 'hidden';
            id.name = NAME_BY_TIPE[s.tipe];
            id.value = s.id;
            row.appendChild(id);

            terpilih.appendChild(row);

            input.value = '';
            sembunyikanHasil();
            perbaruiKosong();
            input.focus();
        }

        input.addEventListener('input', function () { renderHasil(input.value); });
        input.addEventListener('focus', function () { renderHasil(input.value); });
        input.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') { sembunyikanHasil(); input.blur(); }
            if (e.key === 'ArrowDown') {
                const first = hasil.querySelector('button[role="option"]:not([disabled])');
                if (first) { e.preventDefault(); first.focus(); }
            }
        });
        document.addEventListener('click', function (e) {
            if (!input.parentElement.contains(e.target)) sembunyikanHasil();
        });

        // Reset daftar terpilih tiap modal dibuka.
        window.resetAlatPeminjaman = function () {
            Object.keys(ditambah).forEach(function (k) { delete ditambah[k]; });
            terpilih.replaceChildren();
            input.value = '';
            sembunyikanHasil();
            perbaruiKosong();
        };

        perbaruiKosong();
    })();

    // =====================================================================
    // 2. Modal pengembalian
    // =====================================================================
    const modalKembali = document.getElementById('modalKembali');
    const formKembali = document.getElementById('formKembali');
    const kembaliDesc = document.getElementById('kembaliDesc');

    if (formKembali) {
        formKembali.addEventListener('submit', function (e) {
            e.preventDefault();
            L.submitForm(formKembali, {
                onSuccess: function (data) {
                    L.closeModal(modalKembali);
                    L.notifySuccess(data.message || 'Pengembalian berhasil dicatat.');
                }
            });
        });
    }

    // =====================================================================
    // 3. Aksi status via AJAX
    // =====================================================================
    function ubahStatus(id, status, label, btn) {
        L.setBusy(btn, true, '');

        fetch(BASE + '/' + id + '/status', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': L.csrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            credentials: 'same-origin',
            body: JSON.stringify({ _method: 'PUT', status: status })
        })
            .then(async function (res) {
                const data = await res.json().catch(function () { return {}; });
                if (!res.ok) throw new Error(L.extractError(data, 'Gagal memperbarui status.'));
                return data;
            })
            .then(function (data) {
                L.notifySuccess(data.message || ('Status berubah menjadi ' + label + '.'));
            })
            .catch(function (err) {
                L.notifyError('Gagal', err.message);
            })
            .finally(function () {
                L.setBusy(btn, false);
            });
    }

    document.addEventListener('click', function (e) {
        // Setujui
        const setujui = e.target.closest('.btn-setujui-pinjam');
        if (setujui) {
            const nama = setujui.dataset.nama || 'peminjam ini';
            window.SAE.confirm('Setujui pengajuan dari "' + nama + '"?', 'Setujui Peminjaman?', 'info', 'Ya, Setujui').then(function (ok) {
                if (ok) ubahStatus(setujui.dataset.id, 'disetujui', 'Disetujui', setujui);
            });
            return;
        }

        // Serah terima
        const serah = e.target.closest('.btn-serah-terima');
        if (serah) {
            const nama = serah.dataset.nama || 'peminjam ini';
            window.SAE.confirm('Alat diserahkan kepada "' + nama + '". Stok tersedia akan berkurang.', 'Serah Terima Alat?', 'info', 'Ya, Serahkan').then(function (ok) {
                if (ok) ubahStatus(serah.dataset.id, 'dipinjam', 'Dipinjam', serah);
            });
            return;
        }

        // Proses pengembalian -> buka modal
        const kembali = e.target.closest('.btn-kembalikan-pinjam');
        if (kembali && formKembali) {
            formKembali.reset();
            formKembali.action = BASE + '/' + kembali.dataset.id + '/kembalikan';
            if (kembaliDesc) {
                kembaliDesc.textContent = 'Mencatat pengembalian alat dari: ' + (kembali.dataset.nama || '—');
            }
            L.openModal(modalKembali);
            return;
        }

        // Hapus peminjaman
        const del = e.target.closest('.btn-delete-pinjam');
        if (del) {
            L.deleteData(BASE + '/' + del.dataset.id, del.dataset.nama || 'peminjaman ini');
            return;
        }

        // Tandai semua terlambat
        const tandai = e.target.closest('#btnTandaiTerlambat');
        if (tandai) {
            const doPost = function () {
                fetch(BASE + '/tandai-terlambat', {
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
                        if (!res.ok) throw new Error(data.message || 'Gagal menandai.');
                        return data;
                    })
                    .then(function (data) {
                        L.notifySuccess(data.message || 'Peminjaman terlambat ditandai.');
                    })
                    .catch(function (err) {
                        L.notifyError('Gagal', err.message);
                    });
            };

            window.SAE.confirm('Semua peminjaman yang melewati tenggat akan ditandai terlambat.', 'Tandai Peminjaman Terlambat?', 'warning', 'Ya, Tandai').then(function (ok) {
                if (ok) doPost();
            });
        }
    });

    // =====================================================================
    // 4. Modal Import Excel
    // =====================================================================
    const modalImport = document.getElementById('modalImportPeminjaman');
    const btnOpenImport = document.getElementById('btnOpenImportPeminjaman');
    const btnCloseImport = document.getElementById('btnCloseModalImportPeminjaman');
    const btnCancelImport = document.getElementById('btnCancelImportPeminjaman');
    const formImport = document.getElementById('formImportPeminjaman');

    function openImportModal() {
        if (!modalImport) return;
        if (formImport) formImport.reset();
        L.openModal(modalImport);
    }

    function closeImportModal() {
        L.closeModal(modalImport);
    }

    if (btnOpenImport) btnOpenImport.addEventListener('click', openImportModal);
    if (btnCloseImport) btnCloseImport.addEventListener('click', closeImportModal);
    if (btnCancelImport) btnCancelImport.addEventListener('click', closeImportModal);
    if (modalImport) {
        modalImport.addEventListener('click', function (e) {
            if (e.target === modalImport) closeImportModal();
        });
    }

    if (formImport) {
        formImport.addEventListener('submit', function (e) {
            e.preventDefault();

            const fileInput = document.getElementById('fileImportPeminjaman');
            if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
                L.notifyError('Berkas Belum Dipilih', 'Pilih berkas Excel/CSV terlebih dahulu.');
                return;
            }

            const submitBtn = document.getElementById('btnSubmitImportPeminjaman');
            L.setBusy(submitBtn, true, 'Mengimpor...');

            fetch(formImport.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': L.csrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                credentials: 'same-origin',
                body: new FormData(formImport)
            })
                .then(async function (res) {
                    const data = await res.json().catch(function () { return {}; });
                    if (!res.ok) throw new Error(L.extractError(data, 'Gagal memproses berkas import.'));
                    return data;
                })
                .then(function (data) {
                    closeImportModal();
                    L.notifySuccess(data.message || 'Import peminjaman berhasil diproses.');
                })
                .catch(function (err) {
                    L.notifyError('Gagal Import', err.message || 'Terjadi kesalahan saat mengunggah berkas.');
                })
                .finally(function () {
                    L.setBusy(submitBtn, false);
                });
        });
    }
});
