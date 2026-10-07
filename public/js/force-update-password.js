/**
 * SAE - Force Update Password Script
 * Validates strong password rules and provides instant visual feedback.
 */

window.togglePasswordVisibility = function (inputId, iconId) {
    const input = document.getElementById(inputId);
    const icon = document.getElementById(iconId);
    if (!input) return;
    if (input.type === 'password') {
        input.type = 'text';
        if (icon) {
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        }
    } else {
        input.type = 'password';
        if (icon) {
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }
};

document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('forcePasswordForm');
    const studentNisn = form?.getAttribute('data-nisn') || '';
    const newPassInput = document.getElementById('newPassword');
    const confirmPassInput = document.getElementById('confirmPassword');
    const newPassWrap = document.getElementById('newPassWrap');
    const confirmPassWrap = document.getElementById('confirmPassWrap');
    const strengthBar = document.getElementById('strengthBar');
    const strengthLabel = document.getElementById('strengthLabel');
    const matchFeedback = document.getElementById('matchFeedback');
    const btnSubmit = document.getElementById('btnSubmitPassword');

    if (!newPassInput || !confirmPassInput) return;

    function setParamStatus(id, isValid, isNegative = false) {
        const item = document.getElementById('param-' + id);
        const wrap = document.getElementById('icon-wrap-' + id);
        if (!item || !wrap) return;

        const hasInput = newPassInput.value.length > 0;

        if (isValid) {
            item.style.color = '#10b981';
            wrap.innerHTML = '<i class="fas fa-circle-check" style="color: #10b981; font-size: 0.85rem;"></i>';
        } else if (isNegative || hasInput) {
            item.style.color = '#ef4444';
            wrap.innerHTML = '<i class="fas fa-circle-xmark" style="color: #ef4444; font-size: 0.85rem;"></i>';
        } else {
            item.style.color = 'var(--text-muted)';
            wrap.innerHTML = '<i class="fas fa-circle-xmark" style="color: var(--text-muted); opacity: 0.55; font-size: 0.85rem;"></i>';
        }
    }

    function evaluatePassword() {
        const val = newPassInput.value;
        const confirmVal = confirmPassInput.value;

        // 1. Min 8 & Max 15
        const ruleLen = val.length >= 8 && val.length <= 15;
        // 2. A-Z and a-z
        const ruleCase = /[A-Z]/.test(val) && /[a-z]/.test(val);
        // 3. 0-9
        const ruleNum = /[0-9]/.test(val);
        // 4. Special symbol !@#
        const ruleSym = /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?~`]/.test(val);
        // 5. No space
        const hasSpace = /\s/.test(val);
        const ruleSpace = val.length > 0 && !hasSpace;
        // 6. Not same as NISN
        const isSameNisn = studentNisn && val === studentNisn;
        const ruleNisn = val.length > 0 && !isSameNisn;

        // Update UI status
        setParamStatus('len', ruleLen, val.length > 15);
        setParamStatus('case', ruleCase);
        setParamStatus('num', ruleNum);
        setParamStatus('sym', ruleSym);
        setParamStatus('space', ruleSpace, hasSpace);
        setParamStatus('nisn', ruleNisn, isSameNisn);

        // Calculate strength score
        let score = 0;
        if (ruleLen) score++;
        if (ruleCase) score++;
        if (ruleNum) score++;
        if (ruleSym) score++;
        if (ruleSpace) score++;

        if (strengthBar && strengthLabel && newPassWrap) {
            if (val.length === 0) {
                strengthBar.style.width = '0%';
                strengthBar.style.backgroundColor = 'transparent';
                strengthLabel.textContent = 'Belum Diisi';
                strengthLabel.style.color = 'var(--text-muted)';
                newPassWrap.style.borderColor = 'var(--border-color)';
                newPassWrap.style.boxShadow = 'none';
            } else if (score <= 2) {
                strengthBar.style.width = '30%';
                strengthBar.style.backgroundColor = '#ef4444';
                strengthLabel.textContent = 'Lemah';
                strengthLabel.style.color = '#ef4444';
                newPassWrap.style.borderColor = '#ef4444';
                newPassWrap.style.boxShadow = '0 0 0 2px rgba(239,68,68,0.15)';
            } else if (score <= 4) {
                strengthBar.style.width = '70%';
                strengthBar.style.backgroundColor = '#f59e0b';
                strengthLabel.textContent = 'Cukup';
                strengthLabel.style.color = '#f59e0b';
                newPassWrap.style.borderColor = '#f59e0b';
                newPassWrap.style.boxShadow = '0 0 0 2px rgba(245,158,11,0.15)';
            } else {
                strengthBar.style.width = '100%';
                strengthBar.style.backgroundColor = '#10b981';
                strengthLabel.textContent = 'Sangat Kuat';
                strengthLabel.style.color = '#10b981';
                newPassWrap.style.borderColor = '#10b981';
                newPassWrap.style.boxShadow = '0 0 0 2px rgba(16,185,129,0.15)';
            }
        }

        // Confirm Password Evaluation
        const isMatch = confirmVal.length > 0 && confirmVal === val;
        if (matchFeedback && confirmPassWrap) {
            if (confirmVal.length === 0) {
                matchFeedback.textContent = 'Belum diisi';
                matchFeedback.style.color = 'var(--text-muted)';
                confirmPassWrap.style.borderColor = 'var(--border-color)';
                confirmPassWrap.style.boxShadow = 'none';
            } else if (isMatch) {
                matchFeedback.textContent = 'Konfirmasi cocok';
                matchFeedback.style.color = '#10b981';
                confirmPassWrap.style.borderColor = '#10b981';
                confirmPassWrap.style.boxShadow = '0 0 0 2px rgba(16,185,129,0.15)';
            } else {
                matchFeedback.textContent = 'Belum cocok';
                matchFeedback.style.color = '#ef4444';
                confirmPassWrap.style.borderColor = '#ef4444';
                confirmPassWrap.style.boxShadow = '0 0 0 2px rgba(239,68,68,0.15)';
            }
        }

        // Enable or disable submit button
        const allPassed = ruleLen && ruleCase && ruleNum && ruleSym && ruleSpace && ruleNisn && isMatch;
        if (btnSubmit) {
            if (allPassed) {
                btnSubmit.removeAttribute('disabled');
                btnSubmit.style.opacity = '1';
                btnSubmit.style.cursor = 'pointer';
            } else {
                btnSubmit.setAttribute('disabled', 'disabled');
                btnSubmit.style.opacity = '0.6';
                btnSubmit.style.cursor = 'not-allowed';
            }
        }

        return allPassed;
    }

    newPassInput.addEventListener('input', evaluatePassword);
    confirmPassInput.addEventListener('input', evaluatePassword);

    newPassInput.addEventListener('keydown', (e) => {
        if (e.key === ' ' || e.code === 'Space') {
            e.preventDefault();
            setParamStatus('space', false, true);
        }
    });

    confirmPassInput.addEventListener('keydown', (e) => {
        if (e.key === ' ' || e.code === 'Space') {
            e.preventDefault();
        }
    });

    if (form) {
        form.addEventListener('submit', (e) => {
            if (!evaluatePassword()) {
                e.preventDefault();
                newPassInput.focus();
            }
        });
    }

    evaluatePassword();
});
