/**
 * SAE - Login Page Script
 * Password toggle and role-based login tab switcher (Umum vs Orang Tua)
 */

window.togglePass = function (inputId, iconId) {
    const pass = document.getElementById(inputId);
    const eye = document.getElementById(iconId);
    if (!pass || !eye) return;
    if (pass.type === 'password') {
        pass.type = 'text';
        eye.classList.remove('fa-eye');
        eye.classList.add('fa-eye-slash');
    } else {
        pass.type = 'password';
        eye.classList.remove('fa-eye-slash');
        eye.classList.add('fa-eye');
    }
};

window.switchLoginTab = function (type) {
    const fUmum = document.getElementById('formLoginUmum');
    const fOrtu = document.getElementById('formLoginOrtu');
    const bUmum = document.getElementById('tabBtnUmum');
    const bOrtu = document.getElementById('tabBtnOrtu');
    const uInput = document.getElementById('usernameInput');
    const pInput = document.getElementById('passwordInput');
    const nInput = document.getElementById('inputNisnAnak');
    const tInput = document.getElementById('inputTglLahirAnak');

    if (type === 'orang_tua') {
        if (fUmum) fUmum.style.display = 'none';
        if (fOrtu) fOrtu.style.display = 'block';

        if (bOrtu) {
            bOrtu.style.background = '#10b981';
            bOrtu.style.color = '#fff';
            bOrtu.style.fontWeight = '700';
        }
        if (bUmum) {
            bUmum.style.background = 'transparent';
            bUmum.style.color = 'var(--text-muted)';
            bUmum.style.fontWeight = '600';
        }

        if (uInput) uInput.removeAttribute('required');
        if (pInput) pInput.removeAttribute('required');
        if (nInput) nInput.setAttribute('required', 'required');
        if (tInput) tInput.setAttribute('required', 'required');
    } else {
        if (fOrtu) fOrtu.style.display = 'none';
        if (fUmum) fUmum.style.display = 'block';

        if (bUmum) {
            bUmum.style.background = 'var(--primary)';
            bUmum.style.color = '#fff';
            bUmum.style.fontWeight = '700';
        }
        if (bOrtu) {
            bOrtu.style.background = 'transparent';
            bOrtu.style.color = 'var(--text-muted)';
            bOrtu.style.fontWeight = '600';
        }

        if (nInput) nInput.removeAttribute('required');
        if (tInput) tInput.removeAttribute('required');
        if (uInput) uInput.setAttribute('required', 'required');
        if (pInput) pInput.setAttribute('required', 'required');
    }
};

document.addEventListener('DOMContentLoaded', function () {
    const params = new URLSearchParams(window.location.search);
    const ortuForm = document.getElementById('formLoginOrtu');
    if (params.get('tab') === 'ortu' || params.get('type') === 'orang_tua' || (ortuForm && ortuForm.dataset.active === '1')) {
        window.switchLoginTab('orang_tua');
    }

    const nisnInput = document.getElementById('inputNisnAnak');
    const tglInput = document.getElementById('inputTglLahirAnak');
    const datePicker = document.getElementById('nativeDatePicker');
    const btnPickerWrapper = document.getElementById('btnPickerWrapper');

    // 1. Sanitasi input NISN / NIK (Hanya angka, maks 16 digit)
    if (nisnInput) {
        nisnInput.addEventListener('input', function () {
            this.value = this.value.replace(/\D/g, '').slice(0, 16);
        });
    }

    // 2. Format tanggal lahir interaktif (DD-MM-YYYY) dengan keypad angka
    let lastTglValue = tglInput ? tglInput.value : '';

    if (tglInput) {
        tglInput.addEventListener('input', function (e) {
            let val = this.value;

            // Jika sedang menghapus, jangan otomatis menempelkan tanda hubung lagi
            const isDeleting = (e.inputType && e.inputType.startsWith('delete')) || val.length < lastTglValue.length;
            if (isDeleting) {
                if (val.endsWith('-')) {
                    val = val.slice(0, -1);
                    this.value = val;
                }
                lastTglValue = this.value;
                return;
            }

            // Ambil hanya digit angka, batasi maks 8 digit (DDMMYYYY)
            const rawDigits = val.replace(/\D/g, '').slice(0, 8);
            let formatted = '';

            if (rawDigits.length <= 2) {
                formatted = rawDigits;
            } else if (rawDigits.length <= 4) {
                formatted = rawDigits.slice(0, 2) + '-' + rawDigits.slice(2);
            } else {
                formatted = rawDigits.slice(0, 2) + '-' + rawDigits.slice(2, 4) + '-' + rawDigits.slice(4);
            }

            this.value = formatted;
            lastTglValue = formatted;

            // Sinkronisasi ke native date picker jika tanggal lengkap 8 digit
            if (rawDigits.length === 8 && datePicker) {
                const dd = rawDigits.slice(0, 2);
                const mm = rawDigits.slice(2, 4);
                const yyyy = rawDigits.slice(4, 8);
                const iY = parseInt(yyyy, 10);
                const iM = parseInt(mm, 10);
                const iD = parseInt(dd, 10);
                if (iY >= 1970 && iY <= 2040 && iM >= 1 && iM <= 12 && iD >= 1 && iD <= 31) {
                    datePicker.value = `${yyyy}-${mm.padStart(2, '0')}-${dd.padStart(2, '0')}`;
                }
            }
        });
    }

    // 3. Integrasi pemilih tanggal kalender (PWA / Mobile / Desktop)
    if (datePicker && btnPickerWrapper) {
        btnPickerWrapper.addEventListener('click', function () {
            if (typeof datePicker.showPicker === 'function') {
                try {
                    datePicker.showPicker();
                } catch (err) {}
            }
        });

        datePicker.addEventListener('change', function () {
            if (!this.value) return;
            const parts = this.value.split('-');
            if (parts.length === 3) {
                const formatted = `${parts[2]}-${parts[1]}-${parts[0]}`;
                if (tglInput) {
                    tglInput.value = formatted;
                    lastTglValue = formatted;
                    tglInput.dispatchEvent(new Event('input', { bubbles: true }));
                    tglInput.focus();
                }
            }
        });
    }
});
