/**
 * Modul Arsip Dokumen Laboratorium (Tupoksi #7).
 * Bergantung pada window.SAELaboran (laboran-common.js).
 */
document.addEventListener('DOMContentLoaded', function () {
    if (!window.location.pathname.includes('/dashboard/laboran/arsip')) return;

    const L = window.SAELaboran;
    if (!L) return;

    const BASE = '/dashboard/laboran/arsip';

    const modal = document.getElementById('modalArsip');
    const form = document.getElementById('formArsip');
    const btnOpen = document.getElementById('btnOpenArsip');

    const modalMassal = document.getElementById('modalMassal');
    const formMassal = document.getElementById('formMassal');
    const btnMassal = document.getElementById('btnArsipMassal');

    if (btnOpen) {
        btnOpen.addEventListener('click', function () {
            if (form) form.reset();
            L.openModal(modal);
        });
    }

    if (btnMassal) {
        btnMassal.addEventListener('click', function () {
            if (formMassal) formMassal.reset();
            L.openModal(modalMassal);
        });
    }

    if (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            L.submitForm(form, {
                onSuccess: function (data) {
                    L.closeModal(modal);
                    L.notifySuccess(data.message || 'Dokumen berhasil diarsipkan.');
                }
            });
        });
    }

    if (formMassal) {
        formMassal.addEventListener('submit', function (e) {
            e.preventDefault();
            L.submitForm(formMassal, {
                onSuccess: function (data) {
                    L.closeModal(modalMassal);
                    L.notifySuccess(data.message || 'Dokumen berhasil diarsipkan massal.');
                }
            });
        });
    }

    document.addEventListener('click', function (e) {
        const delBtn = e.target.closest('.btn-delete-arsip');
        if (delBtn) {
            L.deleteData(BASE + '/' + delBtn.dataset.id, delBtn.dataset.nama || 'arsip ini');
        }
    });
});
