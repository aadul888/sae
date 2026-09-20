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

    function applyFilter() {
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

    if (searchInput) {
        let timer = null;
        searchInput.addEventListener("input", function () {
            if (clearBtn) clearBtn.classList.toggle("visible", this.value.trim().length > 0);
            clearTimeout(timer);
            timer = setTimeout(applyFilter, 300);
        });

        searchInput.addEventListener("keydown", function (e) {
            if (e.key === "Enter") {
                e.preventDefault();
                clearTimeout(timer);
                applyFilter();
            }
        });
    }

    if (clearBtn) {
        clearBtn.addEventListener("click", function () {
            if (searchInput) {
                searchInput.value = "";
                clearBtn.classList.remove("visible");
                applyFilter();
            }
        });
    }

    if (perPageSelect) {
        perPageSelect.addEventListener("change", applyFilter);
    }

    if (filterKategori) {
        filterKategori.addEventListener("change", applyFilter);
    }
});
