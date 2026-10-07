/**
 * SAE - Terminal Presensi Kiosk Authentication Script
 */

document.addEventListener('DOMContentLoaded', function () {
    // Live Clock
    const clockEl = document.getElementById('kioskClock');
    function tick() {
        if (!clockEl) return;
        const now = new Date();
        clockEl.textContent = now.toTimeString().split(' ')[0] + ' WIB';
    }
    setInterval(tick, 1000);
    tick();

    // Submit Handler & RFID Auto-Focus
    const form = document.getElementById('formKioskAuth');
    const input = document.getElementById('inputKodeAkses');
    const btn = document.getElementById('btnSubmitKiosk');

    function shouldAutoFocus() {
        if (typeof Swal !== 'undefined' && Swal.isVisible()) return false;
        if (document.querySelector('.sae-dialog-overlay')) return false;
        return true;
    }
    window.addEventListener('click', () => {
        if (input && document.activeElement !== input && shouldAutoFocus()) input.focus();
    });
    window.addEventListener('keydown', () => {
        if (input && document.activeElement !== input && shouldAutoFocus()) input.focus();
    });

    function showAlert(msg, title, type, timeout) {
        if (typeof Swal !== 'undefined') {
            const iconMap = { success: 'success', danger: 'error', error: 'error', warning: 'warning', info: 'info' };
            return Swal.fire({
                title: title,
                html: msg,
                icon: iconMap[type] || 'info',
                confirmButtonText: 'OK',
                confirmButtonColor: '#4f46e5',
                timer: timeout > 0 ? timeout : undefined,
                timerProgressBar: timeout > 0,
                background: '#0f172a',
                color: '#f8fafc',
                customClass: { popup: 'sae-swal-popup' }
            });
        }
        return new Promise((resolve) => {
            alert(title + '\n' + msg);
            resolve();
        });
    }

    function resetBtn(focusInput = false) {
        if (!btn) return;
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-key"></i> <span>Buka Terminal Pemindai</span>';
        if (input) input.value = '';
        if (focusInput && input) input.focus();
    }

    if (form) {
        form.addEventListener('submit', async function (e) {
            e.preventDefault();
            const code = input ? input.value.trim().toUpperCase().replace(/\s/g, '') : '';
            if (!code) {
                if (input) input.focus();
                return;
            }

            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <span>Memvalidasi...</span>';
            }

            const unlockUrl = form.getAttribute('action') || '/presensi/scan/unlock';
            const csrfMeta = document.querySelector('meta[name="csrf-token"]');
            const csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';

            try {
                const res = await fetch(unlockUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ kode_akses: code })
                });

                const contentType = res.headers.get('Content-Type') || '';
                if (!contentType.includes('application/json')) {
                    resetBtn();
                    await showAlert(
                        'Sesi habis atau server mengarahkan ulang. Halaman akan dimuat ulang.',
                        'Sesi Berakhir',
                        'warning',
                        2000
                    );
                    window.location.reload();
                    return;
                }

                const data = await res.json();

                if (res.ok && data.status === 'success') {
                    if (btn) btn.innerHTML = '<i class="fas fa-check-circle"></i> <span>Akses Diterima!</span>';
                    await showAlert(
                        data.message || 'Membuka layar terminal scanner presensi...',
                        'Akses Diterima',
                        'success',
                        1200
                    );
                    window.location.href = data.redirect_url || '/presensi/scan';
                } else {
                    let errMsg = data.message || '';
                    if (!errMsg && data.errors) {
                        errMsg = Object.values(data.errors).flat().join(' ');
                    }
                    errMsg = errMsg || 'Kode akses atau kartu RFID tidak valid.';

                    resetBtn(false);
                    await showAlert(errMsg, 'Akses Ditolak', 'danger', 2500);
                    if (input) input.focus();
                }
            } catch (err) {
                resetBtn(false);
                await showAlert(
                    'Gagal terhubung ke server. Periksa jaringan Anda dan coba lagi.',
                    'Kesalahan Jaringan',
                    'danger',
                    2500
                );
                if (input) input.focus();
            }
        });
    }

    window.goBackOrHome = function() {
        if (window.history.length > 1 && document.referrer && !document.referrer.includes('/presensi/scan/lock')) {
            window.history.back();
        } else {
            window.location.href = '/';
        }
    };
});
