/**
 * Jadwal Pelajaran & Mengajar JavaScript Module
 * Sistem Aplikasi Edukasi (SAE)
 */

document.addEventListener('DOMContentLoaded', () => {
    const btnTable = document.getElementById('btnJadwalViewTable');
    const btnGrid = document.getElementById('btnJadwalViewGrid');
    const tableContainer = document.getElementById('tableDataContainerJadwal');
    const gridContainer = document.getElementById('gridDataContainerJadwal');
    const searchInput = document.getElementById('searchJadwalInput');
    const clearBtn = document.getElementById('clearSearchJadwalBtn');

    // 1. Tampilan Switcher (Tabel vs Kartu Grid) dengan memori LocalStorage
    const setViewMode = (mode) => {
        if (!tableContainer || !gridContainer) return;

        if (mode === 'grid') {
            tableContainer.style.display = 'none';
            gridContainer.style.display = 'block';
            if (btnGrid) {
                btnGrid.classList.remove('btn-outline');
                btnGrid.classList.add('btn-primary');
            }
            if (btnTable) {
                btnTable.classList.remove('btn-primary');
                btnTable.classList.add('btn-outline');
            }
        } else {
            tableContainer.style.display = 'block';
            gridContainer.style.display = 'none';
            if (btnTable) {
                btnTable.classList.remove('btn-outline');
                btnTable.classList.add('btn-primary');
            }
            if (btnGrid) {
                btnGrid.classList.remove('btn-primary');
                btnGrid.classList.add('btn-outline');
            }
        }
        try {
            localStorage.setItem('sae_jadwal_view_mode', mode);
        } catch (e) {}
    };

    const savedMode = localStorage.getItem('sae_jadwal_view_mode') || 'table';
    setViewMode(savedMode);

    if (btnTable) {
        btnTable.addEventListener('click', () => setViewMode('table'));
    }
    if (btnGrid) {
        btnGrid.addEventListener('click', () => setViewMode('grid'));
    }

    // 2. Client-side Realtime Live Search Filter (Instan di Tabel & Kartu)
    if (searchInput) {
        searchInput.addEventListener('input', () => {
            const query = searchInput.value.toLowerCase().trim();
            if (clearBtn) {
                if (query.length > 0) {
                    clearBtn.classList.add('visible');
                } else {
                    clearBtn.classList.remove('visible');
                }
            }

            // Filter baris di tabel
            document.querySelectorAll('.jadwal-row-item').forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(query) ? '' : 'none';
            });

            // Filter kartu di grid
            document.querySelectorAll('.jadwal-item-card').forEach(card => {
                const text = card.textContent.toLowerCase();
                card.style.display = text.includes(query) ? '' : 'none';
            });
        });
    }

    if (clearBtn) {
        clearBtn.addEventListener('click', () => {
            if (searchInput) {
                searchInput.value = '';
                searchInput.dispatchEvent(new Event('input'));
                searchInput.focus();
            }
        });
    }
});
