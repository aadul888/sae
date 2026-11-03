/**
 * Modul JavaScript: Data Kebutuhan MBG (SAE Standardized)
 * Menggunakan window.refreshLiveTable dan debounce untuk pencarian live asinkron.
 */
document.addEventListener('DOMContentLoaded', () => {
    let debounceTimer = null;

    const buildUrl = (overrides = {}) => {
        const url = new URL(window.location.href);
        const searchInput = document.getElementById('liveSearch');
        const filterJk = document.getElementById('filterJk');
        const perPageSelect = document.getElementById('perPageSelect');

        const tab = overrides.tab !== undefined ? overrides.tab : (url.searchParams.get('tab') || 'guru_tendik');
        const q = overrides.q !== undefined ? overrides.q : (searchInput?.value || '').trim();
        const jk = overrides.jk !== undefined ? overrides.jk : (filterJk?.value || '').trim();
        const perPage = overrides.per_page !== undefined ? overrides.per_page : (perPageSelect?.value || '25');

        url.searchParams.set('tab', tab);

        if (q) {
            url.searchParams.set('q', q);
        } else {
            url.searchParams.delete('q');
        }

        if (jk) {
            url.searchParams.set('jk', jk);
        } else {
            url.searchParams.delete('jk');
        }

        if (perPage) {
            url.searchParams.set('per_page', perPage);
        } else {
            url.searchParams.delete('per_page');
        }

        url.searchParams.delete('page'); // Reset to page 1 on filter/search change
        return url.toString();
    };

    const triggerRefresh = (targetUrl) => {
        if (typeof window.refreshLiveTable === 'function') {
            window.refreshLiveTable(targetUrl);
        } else {
            window.location.href = targetUrl;
        }
    };

    // 1. Live Search dengan Debounce 350ms
    document.addEventListener('input', (e) => {
        if (e.target && e.target.id === 'liveSearch') {
            const clearBtn = document.getElementById('clearSearch');
            if (clearBtn) {
                if (e.target.value.trim().length > 0) {
                    clearBtn.classList.add('visible');
                } else {
                    clearBtn.classList.remove('visible');
                }
            }

            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                triggerRefresh(buildUrl({ q: e.target.value.trim() }));
            }, 350);
        }
    });

    // 2. Clear Search
    document.addEventListener('click', (e) => {
        const clearBtn = e.target.closest('#clearSearch');
        if (clearBtn) {
            const searchInput = document.getElementById('liveSearch');
            if (searchInput) {
                searchInput.value = '';
                clearBtn.classList.remove('visible');
                triggerRefresh(buildUrl({ q: '' }));
            }
        }
    });

    // 3. Filter Jenis Kelamin
    document.addEventListener('change', (e) => {
        if (e.target && e.target.id === 'filterJk') {
            triggerRefresh(buildUrl({ jk: e.target.value.trim() }));
        }
    });

    // 4. Per Page Change
    document.addEventListener('change', (e) => {
        if (e.target && e.target.id === 'perPageSelect') {
            triggerRefresh(buildUrl({ per_page: e.target.value.trim() }));
        }
    });

    // 5. Tab Navigasi Asinkron
    document.addEventListener('click', (e) => {
        const tabLink = e.target.closest('.periode-nav-tab');
        if (tabLink && tabLink.href && !tabLink.classList.contains('active')) {
            e.preventDefault();
            document.querySelectorAll('.periode-nav-tab').forEach(t => t.classList.remove('active'));
            tabLink.classList.add('active');
            triggerRefresh(tabLink.href);
        }
    });
});
