{{--
    Skrip baku modul Teknisi & IT (batch ringkas).

    • Modal open/close via `[data-modal-open]` & `[data-modal-close]`.
    • Tombol edit mengisi form: `data-teknisi-form` + `data-field-<name>`.
    • Aksi hapus: form `[data-confirm="delete"]` pakai dialog konfirmasi global
      `window.SAE.confirm()`.
    • Mode edit: set `data-edit-route` (route update + ID) → isi action & _method PUT.
--}}
@once
    @push('scripts')
        <script>
            (function() {
                function openModal(id) {
                    var el = document.getElementById(id);
                    if (el) el.style.display = 'flex';
                }

                function closeModal(id) {
                    var el = document.getElementById(id);
                    if (el) el.style.display = 'none';
                }

                document.addEventListener('click', function(e) {
                    var openBtn = e.target.closest('[data-modal-open]');
                    if (openBtn) {
                        openModal(openBtn.getAttribute('data-modal-open'));
                        return;
                    }
                    var closeBtn = e.target.closest('[data-modal-close]');
                    if (closeBtn) {
                        closeModal(closeBtn.getAttribute('data-modal-close'));
                    }
                });

                // Tutup modal saat klik backdrop
                document.addEventListener('click', function(e) {
                    if (e.target.classList && e.target.classList.contains('modal-backdrop')) {
                        e.target.style.display = 'none';
                    }
                });

                // Isi form edit dari tombol baris
                document.addEventListener('click', function(e) {
                    var btn = e.target.closest('[data-teknisi-form]');
                    if (!btn) return;

                    var modalId = btn.getAttribute('data-teknisi-form');
                    var modal = document.getElementById(modalId);
                    if (!modal) return;

                    var form = modal.querySelector('[data-modal-form]');
                    var methodInput = modal.querySelector('[data-modal-method]');
                    var titleEl = modal.querySelector('[data-modal-title]');

                    var editRoute = btn.getAttribute('data-edit-route');
                    if (editRoute) {
                        form.setAttribute('action', editRoute);
                        methodInput.value = 'PUT';
                        if (titleEl && btn.getAttribute('data-title')) titleEl.textContent = btn.getAttribute('data-title');
                    } else {
                        form.setAttribute('action', btn.getAttribute('data-store-route') || form.getAttribute('action'));
                        methodInput.value = 'POST';
                        if (titleEl && btn.getAttribute('data-title')) titleEl.textContent = btn.getAttribute('data-title');
                        form.reset();
                    }

                    var fields = btn.attributes;
                    for (var i = 0; i < fields.length; i++) {
                        var name = fields[i].name;
                        if (name && name.indexOf('data-field-') === 0) {
                            var key = name.substring('data-field-'.length);
                            var input = form.querySelector('[name="' + key + '"]');
                            // Dukung nama array: data-field-skor_3 -> [name="skor[3]"]
                            if (!input) {
                                var arrMatch = key.match(/^([a-z_]+)_(\d+)$/i);
                                if (arrMatch) {
                                    input = form.querySelector('[name="' + arrMatch[1] + '[' + arrMatch[2] + ']"]');
                                }
                            }
                            if (input) {
                                input.value = fields[i].value;
                            }
                        }
                    }

                    openModal(modalId);
                });

                // Konfirmasi hapus
                document.addEventListener('submit', function(e) {
                    var form = e.target;
                    if (form.getAttribute('data-confirm') === 'delete') {
                        e.preventDefault();
                        var msg = form.getAttribute('data-confirm-message') ||
                            'Yakin ingin menghapus data ini? Tindakan tidak dapat dibatalkan.';
                        window.SAE.confirm(msg, 'Konfirmasi Hapus', 'danger', 'Ya, Hapus').then(function(ok) {
                            if (ok) {
                                form.removeAttribute('data-confirm');
                                form.submit();
                            }
                        });
                    }
                });

                // Import Excel via fetch (modal `[data-import-excel]`)
                document.addEventListener('submit', function(e) {
                    var form = e.target;
                    if (!form.hasAttribute('data-import-excel')) return;

                    e.preventDefault();
                    var btn = form.querySelector('button[type="submit"]');
                    var orig = btn ? btn.innerHTML : '';
                    if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...'; }

                    fetch(form.action, {
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': (form.querySelector('[name="_token"]') || {}).value || '', 'Accept': 'application/json' },
                            body: new FormData(form)
                        })
                        .then(function(r) { return r.json().then(function(d) { return { ok: r.ok, data: d }; }); })
                        .then(function(res) {
                            var pesan = (res.data && res.data.message) || 'Import selesai.';
                            if (res.ok) {
                                window.SAE.alert(pesan, 'Import Berhasil', 'success').then(function() { location.reload(); });
                            } else {
                                window.SAE.alert(pesan, 'Import Gagal', 'error');
                            }
                        })
                        .catch(function() {
                            window.SAE.alert('Gagal memproses import. Periksa berkas lalu coba lagi.', 'Kesalahan Sistem', 'error');
                        })
                        .finally(function() {
                            if (btn) { btn.disabled = false; btn.innerHTML = orig; }
                        });
                });
            })();
        </script>
    @endpush
@endonce
