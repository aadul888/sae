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

    function applyFilter() {
        const params = new URLSearchParams(window.location.search);

        if (liveSearch && liveSearch.value.trim()) {
            params.set('q', liveSearch.value.trim());
        } else {
            params.delete('q');
        }

        if (filterStatus && filterStatus.value) {
            params.set('status', filterStatus.value);
        } else {
            params.delete('status');
        }

        if (filterTahun && filterTahun.value) {
            params.set('tahun', filterTahun.value);
        } else {
            params.delete('tahun');
        }

        if (perPageSelect && perPageSelect.value) {
            params.set('perPage', perPageSelect.value);
        }

        params.set('page', '1');
        const targetUrl = window.location.pathname + '?' + params.toString();
        if (typeof window.refreshLiveTable === 'function') {
            window.refreshLiveTable(targetUrl);
        } else {
            window.location.search = params.toString();
        }
    }

    if (perPageSelect) perPageSelect.addEventListener('change', applyFilter);
    if (filterStatus) filterStatus.addEventListener('change', applyFilter);
    if (filterTahun) filterTahun.addEventListener('change', applyFilter);

    if (liveSearch) {
        liveSearch.addEventListener('input', function () {
            if (this.value.trim()) {
                if (clearSearch) clearSearch.classList.add('visible');
            } else {
                if (clearSearch) clearSearch.classList.remove('visible');
            }
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(applyFilter, 300);
        });

        liveSearch.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                clearTimeout(debounceTimer);
                applyFilter();
            }
        });
    }

    if (clearSearch) {
        clearSearch.addEventListener('click', function () {
            if (liveSearch) liveSearch.value = '';
            clearSearch.classList.remove('visible');
            applyFilter();
        });
    }

    // Sortable column headers
    document.querySelectorAll('.sortable-th').forEach(function (th) {
        th.style.cursor = 'pointer';
        th.addEventListener('click', function () {
            const sortKey = this.dataset.sort;
            if (!sortKey) return;
            const params = new URLSearchParams(window.location.search);
            const currentSort = params.get('sort') || 'nama';
            const currentDir = params.get('sort_dir') || 'asc';
            let newDir = 'asc';
            if (currentSort === sortKey) {
                newDir = currentDir === 'asc' ? 'desc' : 'asc';
            }
            params.set('sort', sortKey);
            params.set('sort_dir', newDir);
            params.set('page', '1');
            window.location.search = params.toString();
        });
    });

    // Aksi Pengarsipan Siswa Tingkat XII
    if (btnArchiveGrade12) {
        btnArchiveGrade12.addEventListener('click', async function () {
            const count = this.getAttribute('data-count');
            const archiveUrl = this.getAttribute('data-url') || '/dashboard/manajemen-data/peserta-didik-tidak-aktif/archive-grade12';
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            if (!count || parseInt(count) <= 0) {
                if (window.Swal) {
                    Swal.fire({
                        icon: 'info',
                        title: 'Tidak Ada Siswa',
                        text: 'Tidak ada siswa kelas XII aktif yang perlu diarsipkan.',
                        confirmButtonColor: 'var(--primary, #6366f1)',
                    });
                } else {
                    alert('Tidak ada siswa kelas XII yang perlu diarsipkan.');
                }
                return;
            }

            const confirmResult = window.Swal
                ? await Swal.fire({
                    title: 'Arsipkan Siswa Kelas XII?',
                    text: `Apakah Anda yakin ingin memindahkan ${count} siswa kelas XII ke dalam arsip Alumni (lulus)? Status pada data aktif akan dipindahkan ke arsip tidak aktif.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: 'var(--primary, #6366f1)',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: '<i class="fas fa-box-archive me-1"></i> Ya, Arsipkan Sekarang',
                    cancelButtonText: 'Batal',
                    reverseButtons: true,
                })
                : { isConfirmed: confirm(`Apakah Anda yakin ingin mengarsipkan ${count} siswa kelas XII?`) };

            if (!confirmResult.isConfirmed) {
                return;
            }

            if (window.Swal) {
                Swal.fire({
                    title: 'Memproses Pengarsipan...',
                    text: `Sedang memindahkan data ${count} siswa kelas XII...`,
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
                        (res.message || 'Gagal memuat rincian arsip siswa.') + '</div>';
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
