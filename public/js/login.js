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
    if (params.get('tab') === 'ortu' || params.get('type') === 'orang_tua') {
        window.switchLoginTab('orang_tua');
    }
});
