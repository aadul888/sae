/**
 * Logika JavaScript Modular: Kegiatan Siswa (OSIS, Organisasi, Ekstrakurikuler, Agenda)
 * Standar Resmi SAE (Vanilla JS + SweetAlert2)
 */

document.addEventListener('DOMContentLoaded', function () {
    // 1. Modal Helper
    function openModal(selector) {
        const modal = document.querySelector(selector);
        if (modal) modal.style.display = 'flex';
    }

    function closeModal(selector) {
        const modal = document.querySelector(selector);
        if (modal) modal.style.display = 'none';
    }

    document.querySelectorAll('.close-modal').forEach(btn => {
        btn.addEventListener('click', function () {
            const target = this.getAttribute('data-target');
            if (target) closeModal(target);
        });
    });

    // 2. Tombol Buka Modal
    const btnOpenOrg = document.getElementById('btnOpenOrganisasiModal');
    if (btnOpenOrg) {
        btnOpenOrg.addEventListener('click', () => openModal('#modalOrganisasi'));
    }

    const btnOpenEkskul = document.getElementById('btnOpenEkskulModal');
    if (btnOpenEkskul) {
        btnOpenEkskul.addEventListener('click', () => openModal('#modalEkskul'));
    }

    const btnOpenAgenda = document.getElementById('btnOpenAgendaModal');
    if (btnOpenAgenda) {
        btnOpenAgenda.addEventListener('click', () => openModal('#modalAgenda'));
    }

    // 3. Helper Submit Form AJAX
    function handleFormSubmit(formId, loadingText) {
        const form = document.getElementById(formId);
        if (!form) return;

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = new FormData(this);

            Swal.fire({
                title: 'Memproses...',
                text: loadingText,
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            fetch(this.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    'Accept': 'application/json',
                },
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire('Berhasil!', data.message, 'success').then(() => location.reload());
                } else {
                    Swal.fire('Gagal!', data.message || 'Terjadi kesalahan.', 'error');
                }
            })
            .catch(err => {
                Swal.fire('Error!', 'Gagal memproses data.', 'error');
            });
        });
    }

    handleFormSubmit('formOrganisasi', 'Menyimpan organisasi siswa...');
    handleFormSubmit('formEkskul', 'Menyimpan ekstrakurikuler...');
    handleFormSubmit('formAgenda', 'Menyimpan agenda kegiatan...');

    // 4. Datatable Realtime Live Search & Entri perPage Standar SAE
    const searchInput = document.getElementById("liveSearch");
    const clearBtn = document.getElementById("clearSearch");
    const perPageSelect = document.getElementById("perPageSelect");
    const filterKategori = document.getElementById("filterKategori");

    function applyFilter(overrideParams = {}) {
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
        if (searchInput && searchInput.value.trim()) {
            url.searchParams.set("q", searchInput.value.trim());
        } else {
            url.searchParams.delete("q");
        }

        if (perPageSelect && perPageSelect.value) {
            url.searchParams.set("perPage", perPageSelect.value);
        }

        if (filterKategori) {
            if (filterKategori.value) {
                url.searchParams.set("kategori", filterKategori.value);
            } else {
                url.searchParams.delete("kategori");
            }
        }

        url.searchParams.delete("agenda_page");
        url.searchParams.delete("page");

        if (typeof window.refreshLiveTable === "function") {
            window.refreshLiveTable(url.toString());
        } else {
            window.location.href = url.toString();
        }
    }

    // Delegated Change Handler untuk filter Kegiatan
    document.addEventListener("change", function (e) {
        if (!e.target || e.target.tagName !== "SELECT") return;
        if (
            e.target.closest("#tableDataContainer, .datatable-toolbar, .dash-table-toolbar, .card") &&
            (e.target.id === "filterKategori" || e.target.id === "perPageSelect")
        ) {
            e.preventDefault();
            e.stopPropagation();
            applyFilter();
        }
    });

    if (perPageSelect) {
        perPageSelect.addEventListener("change", applyFilter);
    }

    if (filterKategori) {
        filterKategori.addEventListener("change", applyFilter);
    }
});
