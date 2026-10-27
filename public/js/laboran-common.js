/**
 * SAE — Helper Bersama Modul Laboratorium per Jurusan.
 *
 * Menyediakan utilitas standar agar setiap modul laboran tidak menduplikasi kode:
 *  - Modal buka/tutup
 *  - Submit AJAX dengan CSRF, loading state, dan notifikasi SweetAlert2
 *  - Hapus dengan konfirmasi
 *  - Format rupiah & tanggal
 *
 * Standar Baku SAE: Vanilla JS, z-index modal 99999, SweetAlert2.
 */
(function () {
    'use strict';

    // -------------------------------------------------------------------------
    // Util
    // -------------------------------------------------------------------------

    function csrfToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        const input = document.querySelector('input[name="_token"]');
        return (meta ? meta.getAttribute('content') : '') || (input ? input.value : '');
    }

    function extractError(data, fallback) {
        if (data && data.errors) {
            const first = Object.values(data.errors)[0];
            if (Array.isArray(first) && first.length > 0) return first[0];
            if (typeof first === 'string') return first;
        }
        return (data && data.message) || fallback;
    }

    function notifySuccess(message, reload) {
        return Swal.fire({
            icon: 'success',
            title: 'Berhasil',
            text: message,
            timer: 1400,
            showConfirmButton: false
        }).then(function () {
            if (reload !== false) window.location.reload();
        });
    }

    function notifyError(title, message) {
        return Swal.fire({ icon: 'error', title: title, text: message });
    }

    function openModal(el) {
        if (el) el.style.display = 'flex';
    }

    function closeModal(el) {
        if (el) el.style.display = 'none';
    }

    function setBusy(btn, busy, label) {
        if (!btn) return;
        if (busy) {
            btn.dataset.originalHtml = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> ' + (label || 'Menyimpan...');
        } else {
            btn.disabled = false;
            if (btn.dataset.originalHtml) btn.innerHTML = btn.dataset.originalHtml;
        }
    }

    /**
     * Submit form via fetch (AJAX) dengan penanganan galat seragam.
     *
     * @param {HTMLFormElement} form
     * @param {Object} options { onSuccess, reload, confirmText }
     */
    function submitForm(form, options) {
        options = options || {};
        if (!form) return;

        const submitBtn = form.querySelector('button[type="submit"]');
        setBusy(submitBtn, true);

        fetch(form.action, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            credentials: 'same-origin',
            body: new FormData(form)
        })
            .then(async function (res) {
                const data = await res.json().catch(function () { return {}; });
                if (!res.ok) {
                    throw new Error(extractError(data, 'Terjadi kesalahan saat menyimpan data.'));
                }
                return data;
            })
            .then(function (data) {
                if (typeof options.onSuccess === 'function') {
                    options.onSuccess(data);
                    return;
                }
                notifySuccess(data.message || 'Data berhasil disimpan.', options.reload);
            })
            .catch(function (err) {
                notifyError('Gagal Menyimpan', err.message || 'Terjadi kesalahan pada sistem.');
            })
            .finally(function () {
                setBusy(submitBtn, false);
            });
    }

    /**
     * Hapus data dengan konfirmasi.
     *
     * @param {string} url
     * @param {string} label Nama data untuk konfirmasi
     * @param {Function} onSuccess
     */
    function deleteData(url, label, onSuccess) {
        const doDelete = function () {
            fetch(url, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                credentials: 'same-origin'
            })
                .then(async function (res) {
                    const data = await res.json().catch(function () { return {}; });
                    if (!res.ok) throw new Error(data.message || 'Gagal menghapus data.');
                    return data;
                })
                .then(function (data) {
                    if (typeof onSuccess === 'function') {
                        onSuccess(data);
                        return;
                    }
                    notifySuccess(data.message || 'Data berhasil dihapus.');
                })
                .catch(function (err) {
                    notifyError('Gagal Menghapus', err.message || 'Terjadi kesalahan sistem.');
                });
        };

        Swal.fire({
            title: 'Hapus Data?',
            text: 'Apakah Anda yakin ingin menghapus "' + label + '"?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then(function (result) {
            if (result.isConfirmed) doDelete();
        });
    }

    function formatRupiah(n) {
        const num = Number(n || 0);
        return 'Rp ' + num.toLocaleString('id-ID', { maximumFractionDigits: 0 });
    }

    // -------------------------------------------------------------------------
    // Ekspos API
    // -------------------------------------------------------------------------

    window.SAELaboran = {
        csrfToken: csrfToken,
        extractError: extractError,
        notifySuccess: notifySuccess,
        notifyError: notifyError,
        openModal: openModal,
        closeModal: closeModal,
        setBusy: setBusy,
        submitForm: submitForm,
        deleteData: deleteData,
        formatRupiah: formatRupiah
    };

    // -------------------------------------------------------------------------
    // Penutupan modal generik: klik backdrop, tombol close (data-close), Escape
    // -------------------------------------------------------------------------

    document.addEventListener('click', function (e) {
        // Klik pada backdrop modal -> tutup
        if (e.target.classList && e.target.classList.contains('modal-backdrop')) {
            e.target.style.display = 'none';
            return;
        }

        // Tombol dengan data-close="modalId"
        const closer = e.target.closest('[data-close]');
        if (closer) {
            const modal = document.getElementById(closer.dataset.close);
            if (modal) modal.style.display = 'none';
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape') return;
        if (['INPUT', 'SELECT', 'TEXTAREA'].includes((document.activeElement || {}).tagName)) return;

        document.querySelectorAll('.modal-backdrop').forEach(function (m) {
            if (m.style.display === 'flex') m.style.display = 'none';
        });
    });
})();
