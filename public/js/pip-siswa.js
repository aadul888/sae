(function () {
    'use strict';

    var tabSelect = document.getElementById('pipTabMobile');
    if (tabSelect) {
        tabSelect.addEventListener('change', function () {
            if (tabSelect.value) window.location.href = tabSelect.value;
        });
    }

    var modal = document.getElementById('pipBuktiModal');
    if (modal) {
        var modalBody = document.getElementById('pipBuktiModalBody');
        var modalTitle = document.getElementById('pipBuktiModalTitle');

        var tutupModal = function () {
            modal.style.display = 'none';
            if (modalBody) modalBody.innerHTML = '';
        };

        document.querySelectorAll('[data-pip-bukti]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var tpl = document.querySelector(btn.getAttribute('data-pip-bukti'));
                if (!tpl || !modalBody) return;

                modalBody.innerHTML = tpl.innerHTML;
                if (modalTitle) modalTitle.textContent = btn.getAttribute('data-pip-bukti-title') || 'Bukti Laporan';
                modal.style.display = 'flex';
            });
        });

        modal.querySelectorAll('[data-pip-bukti-close]').forEach(function (btn) {
            btn.addEventListener('click', tutupModal);
        });

        modal.addEventListener('click', function (e) {
            if (e.target === modal) tutupModal();
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && modal.style.display === 'flex') tutupModal();
        });
    }

    var skSelect = document.getElementById('pipSkSelect');
    if (!skSelect) return;

    var danaInput = document.getElementById('pipDanaDiterima');
    var totalEl = document.getElementById('pipTotalRincian');
    var sisaEl = document.getElementById('pipSisaDana');
    var buktiInput = document.getElementById('pipBukti');
    var buktiInfo = document.getElementById('pipBuktiInfo');
    var form = skSelect.closest('form');
    var nominalInputs = document.querySelectorAll('.pip-rincian-nominal');

    var angka = function (value) {
        return String(value == null ? '' : value).replace(/[^\d]/g, '');
    };

    var rupiah = function (value) {
        return 'Rp ' + Number(value || 0).toLocaleString('id-ID', { maximumFractionDigits: 0 });
    };

    var formatRibuan = function (value) {
        var digit = angka(value).replace(/^0+(?=\d)/, '');
        return digit ? digit.replace(/\B(?=(\d{3})+(?!\d))/g, '.') : '';
    };

    var hitung = function () {
        var dana = parseFloat(skSelect.selectedOptions[0] && skSelect.selectedOptions[0].dataset.nominal || 0) || 0;
        var total = 0;

        nominalInputs.forEach(function (input) {
            total += parseFloat(angka(input.value)) || 0;
        });

        if (danaInput) danaInput.value = rupiah(dana);
        if (totalEl) totalEl.textContent = rupiah(total);

        if (sisaEl) {
            var sisa = dana - total;
            sisaEl.innerHTML = dana > 0
                ? 'Sisa dana: <strong style="color:' + (sisa < 0 ? 'var(--danger, #ef4444)' : 'var(--success, #10b981)') + ';">' + rupiah(sisa) + '</strong>'
                : '';
        }
    };

    nominalInputs.forEach(function (input) {
        input.value = formatRibuan(input.value);
        input.addEventListener('input', function () {
            input.value = formatRibuan(input.value);
            hitung();
        });
    });

    if (form) {
        form.addEventListener('submit', function () {
            nominalInputs.forEach(function (input) {
                input.value = angka(input.value);
            });
        });
    }

    skSelect.addEventListener('change', hitung);

    if (buktiInput && buktiInfo) {
        buktiInput.addEventListener('change', function () {
            var names = Array.prototype.map.call(buktiInput.files, function (file) {
                return file.name;
            });
            buktiInfo.textContent = names.length ? 'Terpilih: ' + names.join(', ') : '';
        });
    }

    hitung();
})();
