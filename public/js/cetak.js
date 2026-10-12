/**
 * SAE — Master Cetak & Print Layout Helper Script (Global)
 * Terstandarisasi untuk seluruh modul cetak sistem SAE.
 */
(function () {
    'use strict';

    let isPrinting = false;

    // 1. Debounced Safe Print Function
    window.SAE_Print = function () {
        if (isPrinting) return;
        isPrinting = true;

        const printBtns = document.querySelectorAll('.btn-print, .print-btn');
        printBtns.forEach(btn => {
            btn.classList.add('printing');
            btn.setAttribute('disabled', 'disabled');
        });

        // Beri sedikit jeda mikro agar DOM stabil sebelum browser memanggil renderer PDF
        setTimeout(function () {
            try {
                window.print();
            } catch (err) {
                console.error('[SAE Print Error]:', err);
            } finally {
                setTimeout(function () {
                    isPrinting = false;
                    printBtns.forEach(btn => {
                        btn.classList.remove('printing');
                        btn.removeAttribute('disabled');
                    });
                }, 800);
            }
        }, 120);
    };

    // 2. Event Listener: Before & After Print
    window.addEventListener('beforeprint', function () {
        document.body.classList.add('is-printing');
    });

    window.addEventListener('afterprint', function () {
        document.body.classList.remove('is-printing');
        isPrinting = false;
    });

    // 3. Keyboard Shortcuts (Ctrl+P / Cmd+P to Print, Escape to Exit)
    document.addEventListener('keydown', function (e) {
        // Ctrl+P / Cmd+P
        if ((e.ctrlKey || e.metaKey) && (e.key === 'p' || e.key === 'P')) {
            e.preventDefault();
            window.SAE_Print();
            return;
        }

        // Escape: Kembali / Tutup Jendela jika bukan di dalam input
        if (e.key === 'Escape' && !['INPUT', 'SELECT', 'TEXTAREA'].includes(document.activeElement?.tagName)) {
            const backBtn = document.querySelector('.no-print-bar .btn-back, .action-toolbar .btn-back');
            if (backBtn && backBtn.getAttribute('href')) {
                window.location.href = backBtn.getAttribute('href');
            } else if (window.history.length > 1) {
                window.history.back();
            } else {
                window.close();
            }
        }
    });

    // 4. Dropdown Filter Interaktif (.select-cetak)
    document.addEventListener('DOMContentLoaded', function () {
        const selectFilters = document.querySelectorAll('select.select-cetak');
        selectFilters.forEach(select => {
            select.addEventListener('change', function () {
                const paramName = this.getAttribute('data-param') || this.name;
                if (!paramName) return;

                const url = new URL(window.location.href);
                if (this.value) {
                    url.searchParams.set(paramName, this.value);
                } else {
                    url.searchParams.delete(paramName);
                }
                window.location.href = url.toString();
            });
        });

        // 5. Auto-Print handling
        if (document.body.hasAttribute('data-auto-print') || window.location.search.indexOf('autoprint=1') !== -1) {
            setTimeout(function () {
                window.SAE_Print();
            }, 450);
        }
    });
})();
