/**
 * SAE - Orang Tua Kehadiran Script
 * Toggles between daily school gate attendance and classroom subject attendance views.
 */

document.addEventListener('DOMContentLoaded', function () {
    const selectKehadiran = document.getElementById('selectKategoriKehadiran');
    const panelHarian = document.getElementById('subPanelHarian');
    const panelMapel = document.getElementById('subPanelMapel');

    if (selectKehadiran) {
        selectKehadiran.addEventListener('change', function () {
            const val = this.value;
            if (panelHarian) panelHarian.style.display = (val === 'subHarian') ? 'block' : 'none';
            if (panelMapel) panelMapel.style.display = (val === 'subMapel') ? 'block' : 'none';
        });
    }
});
