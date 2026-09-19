/**
 * Script Interaksi Dashboard Peserta Didik SAE
 * Standar: Vanilla JS terpisah, SweetAlert2 notification, Mobile Responsive
 */
document.addEventListener('DOMContentLoaded', function () {
    const btnCopyNisn = document.querySelector('.btn-copy-nisn');
    if (btnCopyNisn) {
        let lastCopyTime = 0;

        const copyNisnHandler = function (e) {
            const now = Date.now();
            if (now - lastCopyTime < 500) return; // Cegah double trigger touch + click
            lastCopyTime = now;

            if (e) {
                e.preventDefault();
                e.stopPropagation();
            }

            const nisn = this.getAttribute('data-nisn') || this.querySelector('.nisn-text')?.textContent?.trim();
            if (!nisn) return;

            const onSuccess = () => {
                // 1. Feedback visual pada elemen tombol
                const icon = this.querySelector('i');
                const origClass = icon ? icon.className : '';
                if (icon) icon.className = 'fas fa-check text-success';
                this.style.borderColor = '#10b981';
                this.style.background = 'rgba(16, 185, 129, 0.2)';
                this.style.color = '#10b981';

                setTimeout(() => {
                    if (icon) icon.className = origClass;
                    this.style.borderColor = 'rgba(6,182,212,0.45)';
                    this.style.background = 'rgba(6,182,212,0.12)';
                    this.style.color = '#38bdf8';
                }, 1500);

                // 2. SweetAlert2 Toast
                if (typeof Swal !== 'undefined') {
                    const Toast = Swal.mixin({
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 2000,
                        timerProgressBar: true,
                        background: '#0f172a',
                        color: '#f8fafc',
                        didOpen: (toast) => {
                            toast.style.border = '1px solid rgba(6,182,212,0.4)';
                            toast.style.boxShadow = '0 8px 24px rgba(0,0,0,0.5)';
                        }
                    });
                    Toast.fire({
                        icon: 'success',
                        title: `NISN ${nisn} berhasil disalin!`
                    });
                }
            };

            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(nisn).then(onSuccess).catch(() => {
                    fallbackCopyText(nisn, onSuccess);
                });
            } else {
                fallbackCopyText(nisn, onSuccess);
            }
        };

        btnCopyNisn.addEventListener('click', copyNisnHandler);
        btnCopyNisn.addEventListener('touchend', copyNisnHandler);
    }

    function fallbackCopyText(text, cb) {
        const textArea = document.createElement('textarea');
        textArea.value = text;
        textArea.style.position = 'fixed';
        textArea.style.left = '-999999px';
        textArea.style.top = '-999999px';
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        try {
            document.execCommand('copy');
            if (cb) cb();
        } catch (err) {
            console.error('Fallback copy NISN gagal:', err);
        }
        document.body.removeChild(textArea);
    }
});
