/**
 * Script Modul Master Data Pembelajaran — SAE
 * Standard Datatable Filters & Detail Modal via AJAX
 */

document.addEventListener('DOMContentLoaded', function () {
    // Helper filter global pembelajaran
    window.applyPembelajaranFilter = function (overrideParams = {}) {
        if (typeof window.collectDatatableUrl === "function") {
            const tableWrap = document.getElementById("tableDataContainer");
            const targetUrl = window.collectDatatableUrl(tableWrap, overrideParams);
            if (typeof window.refreshLiveTable === "function") {
                window.refreshLiveTable(targetUrl);
            } else {
                window.location.href = targetUrl;
            }
            return;
        }

        const url = new URL(window.location.href);
        const perPageSelect = document.getElementById('perPageSelect');
        const filterRombel = document.getElementById('filterRombel');
        const filterGuru = document.getElementById('filterGuru');
        const filterStatus = document.getElementById('filterStatus');
        const liveSearchInput = document.getElementById('liveSearch');

        if (liveSearchInput && liveSearchInput.value.trim()) url.searchParams.set('q', liveSearchInput.value.trim());
        else url.searchParams.delete('q');

        if (filterRombel && filterRombel.value) url.searchParams.set('rombel', filterRombel.value);
        else url.searchParams.delete('rombel');

        if (filterGuru && filterGuru.value) url.searchParams.set('guru', filterGuru.value);
        else url.searchParams.delete('guru');

        if (filterStatus && filterStatus.value) url.searchParams.set('status', filterStatus.value);
        else url.searchParams.delete('status');

        if (perPageSelect && perPageSelect.value) url.searchParams.set('perPage', perPageSelect.value);

        Object.keys(overrideParams).forEach((k) => {
            const v = overrideParams[k];
            if (v !== null && v !== undefined && v !== "") url.searchParams.set(k, v);
            else url.searchParams.delete(k);
        });

        if (!('page' in overrideParams)) url.searchParams.set('page', '1');

        if (typeof window.refreshLiveTable === 'function') {
            window.refreshLiveTable(url.toString());
        } else {
            window.location.href = url.toString();
        }
    };

    // Delegated Change Handler untuk filter Pembelajaran
    document.addEventListener("change", function (e) {
        if (!e.target || e.target.tagName !== "SELECT") return;
        if (
            e.target.closest("#tableDataContainer, .datatable-toolbar") &&
            (e.target.id === "filterRombel" || e.target.id === "filterGuru" || e.target.id === "filterStatus" || e.target.id === "perPageSelect")
        ) {
            e.preventDefault();
            e.stopPropagation();
            window.applyPembelajaranFilter();
        }
    });

    // Delegated Sort Click Handler untuk tabel Pembelajaran
    document.addEventListener("click", function (e) {
        const th = e.target.closest(".sortable-th");
        if (!th || !th.closest("#tableDataContainer")) return;
        const sortField = th.getAttribute("data-sort");
        if (!sortField) return;
        e.preventDefault();
        e.stopPropagation();

        const url = new URL(window.location.href);
        const currentSort = url.searchParams.get("sort") || "nama_mata_pelajaran";
        const currentDir = url.searchParams.get("sort_dir") || "asc";
        const newDir = currentSort === sortField && currentDir === "asc" ? "desc" : "asc";
        window.applyPembelajaranFilter({ sort: sortField, sort_dir: newDir });
    });

    // Modal Detail Pembelajaran
    const modal = document.getElementById('pembelajaranModal');
    const loading = document.getElementById('pemModalLoading');
    const content = document.getElementById('pemModalContent');

    window.openPembelajaranModal = function (id) {
        if (!modal) return;
        modal.style.display = 'flex';
        if (loading) loading.style.display = 'block';
        if (content) content.style.display = 'none';

        const baseUrl = window.PEMBELAJARAN_BASE_URL || '/dashboard/master-data/pembelajaran';
        fetch(`${baseUrl}/${id}`)
            .then(r => r.json())
            .then(res => {
                if (loading) loading.style.display = 'none';
                if (res.status === 'success' && res.data) {
                    const d = res.data;
                    const elTitle = document.getElementById('pemModalTitle');
                    const elSubtitle = document.getElementById('pemModalSubtitle');
                    const elMapel = document.getElementById('pemMapel');
                    const elIdMapel = document.getElementById('pemIdMapel');
                    const elRombel = document.getElementById('pemRombel');
                    const elTingkat = document.getElementById('pemTingkat');
                    const elKurikulum = document.getElementById('pemKurikulum');
                    const elGuru = document.getElementById('pemGuru');
                    const elGuruNip = document.getElementById('pemGuruNip');
                    const elGuruKontak = document.getElementById('pemGuruKontak');
                    const elJam = document.getElementById('pemJam');
                    const elStatusKur = document.getElementById('pemStatusKur');
                    const elWali = document.getElementById('pemWali');
                    const elRuang = document.getElementById('pemRuang');

                    if (elTitle) elTitle.textContent = d.nama_mata_pelajaran || d.mata_pelajaran_id_str || 'Pembelajaran';
                    if (elSubtitle) elSubtitle.textContent = (d.nama_rombel ? 'Kelas ' + d.nama_rombel : '') + (d.tingkat ? ' • ' + d.tingkat : '');
                    if (elMapel) elMapel.textContent = d.nama_mata_pelajaran || d.mata_pelajaran_id_str || '-';

                    const idArr = [
                        d.mata_pelajaran_id ? 'Mapel ID: ' + d.mata_pelajaran_id : null,
                        d.pembelajaran_id ? 'Pembelajaran ID: ' + d.pembelajaran_id : null
                    ].filter(Boolean);
                    if (elIdMapel) elIdMapel.textContent = idArr.length > 0 ? idArr.join(' • ') : '-';

                    if (elRombel) elRombel.textContent = d.nama_rombel || '-';
                    if (elTingkat) elTingkat.textContent = (d.tingkat || '-') + (d.jurusan ? ' / ' + d.jurusan : '');
                    if (elKurikulum) elKurikulum.textContent = d.kurikulum || '-';
                    if (elGuru) elGuru.textContent = d.nama_guru || 'Belum Ditugaskan';
                    if (elGuruNip) {
                        elGuruNip.textContent = (d.nuptk ? 'NUPTK: ' + d.nuptk : '') + (d.nip ? ' | NIP: ' + d.nip : (!d.nuptk ? '-' : ''));
                    }

                    const guruKontakArr = [
                        d.guru_status ? d.guru_status : null,
                        d.guru_hp ? 'HP: ' + d.guru_hp : null,
                        d.guru_email ? 'Email: ' + d.guru_email : null
                    ].filter(Boolean);
                    if (elGuruKontak) elGuruKontak.textContent = guruKontakArr.length > 0 ? guruKontakArr.join(' • ') : '-';

                    if (elJam) elJam.textContent = (d.jam_mengajar_per_minggu || 0) + ' Jam Pelajaran (JP) / Minggu';
                    if (elStatusKur) elStatusKur.textContent = d.status_di_kurikulum_str || 'Wajib';
                    if (elWali) elWali.textContent = d.wali_kelas || '-';
                    if (elRuang) elRuang.textContent = d.ruang || '-';

                    if (content) content.style.display = 'block';
                }
            })
            .catch(() => {
                if (loading) {
                    loading.innerHTML = '<div style="color: #ef4444;"><i class="fas fa-exclamation-triangle me-2"></i>Gagal memuat detail pembelajaran.</div>';
                }
            });
    };

    window.closePembelajaranModal = function () {
        if (modal) modal.style.display = 'none';
    };

    if (modal) {
        modal.addEventListener('click', function (e) {
            if (e.target === this) window.closePembelajaranModal();
        });
    }
});
