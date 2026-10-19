/**
 * Manajemen Data — Peserta Didik Tidak Aktif & Alumni - Frontend JS
 */

document.addEventListener('DOMContentLoaded', function () {
    const liveSearch = document.getElementById('liveSearch');
    const clearSearch = document.getElementById('clearSearch');
    const perPageSelect = document.getElementById('perPageSelect');
    const filterStatus = document.getElementById('filterStatus');
    const filterTahun = document.getElementById('filterTahun');
    const btnArchiveGrade12 = document.getElementById('btnArchiveGrade12');

    let debounceTimer;

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
        const liveSearch = document.getElementById('liveSearch');
        const perPageSelect = document.getElementById('perPageSelect');
        const filterStatus = document.getElementById('filterStatus');
        const filterTahun = document.getElementById('filterTahun');

        if (liveSearch && liveSearch.value.trim()) {
            url.searchParams.set('q', liveSearch.value.trim());
        } else {
            url.searchParams.delete('q');
        }

        if (filterStatus && filterStatus.value) {
            url.searchParams.set('status', filterStatus.value);
        } else {
            url.searchParams.delete('status');
        }

        if (filterTahun && filterTahun.value) {
            url.searchParams.set('tahun', filterTahun.value);
        } else {
            url.searchParams.delete('tahun');
        }

        const curPerPage = perPageSelect ? perPageSelect.value : (url.searchParams.get('perPage') || '15');
        if (curPerPage) {
            url.searchParams.set('perPage', curPerPage);
        }

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
    }

    // Delegated Change Handler untuk filter Peserta Didik Tidak Aktif
    document.addEventListener("change", function (e) {
        if (!e.target || e.target.tagName !== "SELECT") return;
        if (
            e.target.closest("#tableDataContainer, .datatable-toolbar") &&
            (e.target.id === "filterStatus" || e.target.id === "filterTahun" || e.target.id === "perPageSelect")
        ) {
            e.preventDefault();
            e.stopPropagation();
            applyFilter();
        }
    });

    // Delegated Sort Click Handler untuk tabel Peserta Didik Tidak Aktif
    document.addEventListener("click", function (e) {
        const th = e.target.closest(".sortable-th");
        if (!th || !th.closest("#tableDataContainer")) return;
        const sortField = th.getAttribute("data-sort");
        if (!sortField) return;
        e.preventDefault();
        e.stopPropagation();

        const url = new URL(window.location.href);
        const currentSort = url.searchParams.get("sort") || "nama";
        const currentDir = url.searchParams.get("sort_dir") || "asc";
        const newDir = currentSort === sortField && currentDir === "asc" ? "desc" : "asc";
        applyFilter({ sort: sortField, sort_dir: newDir });
    });

    // Aksi Pengarsipan Peserta Didik Tingkat XII
    if (btnArchiveGrade12) {
        btnArchiveGrade12.addEventListener('click', async function () {
            const count = this.getAttribute('data-count');
            const archiveUrl = this.getAttribute('data-url') || '/dashboard/manajemen-data/peserta-didik-tidak-aktif/archive-grade12';
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            if (!count || parseInt(count) <= 0) {
                if (window.Swal) {
                    Swal.fire({
                        icon: 'info',
                        title: 'Tidak Ada Peserta Didik',
                        text: 'Tidak ada peserta didik kelas XII aktif yang perlu diarsipkan.',
                        confirmButtonColor: 'var(--primary, #6366f1)',
                    });
                } else {
                    alert('Tidak ada peserta didik kelas XII yang perlu diarsipkan.');
                }
                return;
            }

            const confirmResult = window.Swal
                ? await Swal.fire({
                    title: 'Arsipkan Peserta Didik Kelas XII?',
                    text: `Apakah Anda yakin ingin memindahkan ${count} peserta didik kelas XII ke dalam arsip Alumni (lulus)? Status pada data aktif akan dipindahkan ke arsip tidak aktif.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: 'var(--primary, #6366f1)',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: '<i class="fas fa-box-archive me-1"></i> Ya, Arsipkan Sekarang',
                    cancelButtonText: 'Batal',
                    reverseButtons: true,
                })
                : { isConfirmed: confirm(`Apakah Anda yakin ingin mengarsipkan ${count} peserta didik kelas XII?`) };

            if (!confirmResult.isConfirmed) {
                return;
            }

            if (window.Swal) {
                Swal.fire({
                    title: 'Memproses Pengarsipan...',
                    text: `Sedang memindahkan data ${count} peserta didik kelas XII...`,
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
            }

            try {
                const response = await fetch(archiveUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({
                        count: parseInt(count),
                    })
                });

                const result = await response.json();

                if (response.ok && result.status === 'success') {
                    if (window.Swal) {
                        await Swal.fire({
                            icon: 'success',
                            title: 'Berhasil',
                            text: result.message,
                            confirmButtonColor: 'var(--primary, #6366f1)',
                        });
                    } else {
                        alert(result.message);
                    }
                    window.location.reload();
                } else {
                    if (window.Swal) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal',
                            text: result.message || 'Terjadi kesalahan saat mengarsipkan data.',
                            confirmButtonColor: 'var(--primary, #6366f1)',
                        });
                    } else {
                        alert('Gagal: ' + (result.message || 'Terjadi kesalahan'));
                    }
                }
            } catch (error) {
                console.error('Error:', error);
                if (window.Swal) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Kesalahan Sistem',
                        text: 'Terjadi kesalahan jaringan atau server saat mengarsipkan data: ' + error.message,
                        confirmButtonColor: 'var(--primary, #6366f1)',
                    });
                } else {
                    alert('Terjadi kesalahan saat mengarsipkan data.');
                }
            }
        });
    }
});

window.openBiodataModal = function (id) {
    const modal = document.getElementById('biodataModal');
    const loading = document.getElementById('bioLoading');
    const content = document.getElementById('bioContent');

    if (!modal) return;

    modal.style.display = 'flex';
    if (loading) loading.style.display = 'block';
    if (content) content.style.display = 'none';

    fetch('/dashboard/manajemen-data/peserta-didik-tidak-aktif/' + encodeURIComponent(id), {
        headers: { Accept: 'application/json' }
    })
        .then(res => res.json())
        .then(res => {
            if (loading) loading.style.display = 'none';
            if (res.status === 'success' && res.data) {
                const d = res.data;
                if (content) content.style.display = 'block';

                const setText = (id, val) => {
                    const el = document.getElementById(id);
                    if (el) el.textContent = val || '-';
                };

                setText('bioNama', d.nama);
                setText('bioRombel', (d.nama_rombel_terakhir ? d.nama_rombel_terakhir + ' • Tingkat ' + (d.tingkat_pendidikan_terakhir || '-') : '-'));
                setText('bioNisn', (d.nisn || '-') + (d.nipd ? ' / ' + d.nipd : ''));
                setText('bioNik', d.nik);
                setText('bioJk', d.jenis_kelamin === 'L' ? 'Laki-Laki (L)' : (d.jenis_kelamin === 'P' ? 'Perempuan (P)' : '-'));
                setText('bioTtl', [d.tempat_lahir, d.tanggal_lahir].filter(Boolean).join(', ') || '-');
                setText('bioAgama', d.agama_id_str);

                const bioStatusEl = document.getElementById('bioStatus');
                if (bioStatusEl) {
                    const statusBadge = d.status_keluar === 'Alumni' ?
                        '<span class="badge" style="background: rgba(16,185,129,0.15); color: #10b981; font-weight: 700; padding: 3px 8px;"><i class="fas fa-graduation-cap me-1"></i>Alumni (Lulus)</span>' :
                        '<span class="badge" style="background: rgba(245,158,11,0.15); color: #f59e0b; font-weight: 700; padding: 3px 8px;"><i class="fas fa-right-from-bracket me-1"></i>' +
                        (d.status_keluar || 'Mutasi') + '</span>';
                    bioStatusEl.innerHTML = statusBadge;
                }

                setText('bioTahunLulus', (d.tahun_lulus ? 'Tahun ' + d.tahun_lulus : '-') + (d.tanggal_keluar ? ' (Tgl: ' + d.tanggal_keluar + ')' : ''));
                setText('bioRombelDetail', (d.nama_rombel_terakhir || '-') + (d.kurikulum_id_str ? ' (' + d.kurikulum_id_str + ')' : ''));
                setText('bioAlasan', d.alasan_keluar);

                setText('bioHp', d.nomor_telepon_seluler);
                setText('bioEmail', d.email);
                setText('bioAlamat', d.alamat_jalan);

                const fotoContainer = document.getElementById('bioFotoContainer');
                if (fotoContainer) {
                    if (d.foto_url) {
                        fotoContainer.innerHTML =
                            `<img src="${d.foto_url}" alt="${d.nama}" style="width: 100%; height: 100%; object-fit: cover;">`;
                    } else {
                        fotoContainer.innerHTML =
                            `<i class="fas fa-user-graduate text-primary" style="font-size: 1.3rem;"></i>`;
                    }
                }
            } else {
                if (loading) {
                    loading.style.display = 'block';
                    loading.innerHTML =
                        '<div style="color: var(--danger);"><i class="fas fa-exclamation-circle me-1"></i> ' +
                        (res.message || 'Gagal memuat rincian arsip peserta didik.') + '</div>';
                }
            }
        })
        .catch(err => {
            if (loading) {
                loading.style.display = 'block';
                loading.innerHTML =
                    '<div style="color: var(--danger);"><i class="fas fa-exclamation-circle me-1"></i> Kesalahan jaringan: ' +
                    err.message + '</div>';
            }
        });
};

window.closeBiodataModal = function () {
    const modal = document.getElementById('biodataModal');
    if (modal) modal.style.display = 'none';
};

window.openPhotoPreviewModal = function (url, name) {
    const modal = document.getElementById('photoPreviewModal');
    const img = document.getElementById('imgFullPreview');
    const txt = document.getElementById('txtFullPreviewName');
    if (img) img.src = url;
    if (txt) txt.innerText = name;
    if (modal) modal.style.display = 'flex';
};

window.closePhotoPreviewModal = function () {
    const modal = document.getElementById('photoPreviewModal');
    if (modal) modal.style.display = 'none';
};
