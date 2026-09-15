/**
 * Formulir Public — SAE Core JavaScript Module
 * Digunakan pada Halaman Pengisian Formulir Publik
 */

window.selectRating = function (el, groupName) {
    const group = el.parentElement;
    group.querySelectorAll('.rating-pill').forEach(pill => pill.classList.remove('selected'));
    el.classList.add('selected');
    const input = el.querySelector('input');
    if (input) input.checked = true;
};

window.previewFile = function (input, previewId) {
    const preview = document.getElementById(previewId);
    if (!preview) return;

    if (input.files && input.files[0]) {
        preview.style.display = 'block';
        preview.innerHTML = '<i class="fas fa-file-check me-1"></i> Berkas terpilih: ' + input.files[0].name;
    } else {
        preview.style.display = 'none';
    }
};

document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('publicForm');
    if (form) {
        form.addEventListener('submit', function () {
            const btn = document.getElementById('btnSubmitForm');
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Mengirim tanggapan...';
            }
        });
    }

    // Countdown Timer untuk Auth Gate (Auto-redirect ke halaman login)
    const countdownEl = document.getElementById('authGateCountdown');
    if (countdownEl) {
        let seconds = parseInt(countdownEl.textContent.trim(), 10) || 3;
        const redirectUrl = countdownEl.getAttribute('data-redirect-url') || '/login';

        const timer = setInterval(function () {
            seconds--;
            if (seconds <= 0) {
                clearInterval(timer);
                window.location.href = redirectUrl;
            } else {
                countdownEl.textContent = seconds;
            }
        }, 1000);
    }
});
