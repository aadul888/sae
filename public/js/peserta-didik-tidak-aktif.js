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
                setText('bioKhusus', d.kebutuhan_khusus || 'Tidak ada');

                const bioStatusEl = document.getElementById('bioStatus');
                if (bioStatusEl) {
                    const statusBadge = d.status_keluar === 'Alumni' ?
                        '<span class="badge" style="background: rgba(16,185,129,0.15); color: #10b981; font-weight: 700; padding: 3px 8px;"><i class="fas fa-graduation-cap me-1"></i>Alumni (Lulus)</span>' :
                        '<span class="badge" style="background: rgba(245,158,11,0.15); color: #f59e0b; font-weight: 700; padding: 3px 8px;"><i class="fas fa-right-from-bracket me-1"></i>' +
                        (d.status_keluar || 'Mutasi') + '</span>';
                    bioStatusEl.innerHTML = statusBadge;
                }

                setText('bioTahunLulus', d.tahun_lulus ? 'Tahun ' + d.tahun_lulus : '-');
                setText('bioTglKeluar', d.tanggal_keluar || '-');
                setText('bioRombelDetail', (d.nama_rombel_terakhir || '-') + (d.kurikulum_id_str ? ' (' + d.kurikulum_id_str + ')' : ''));
                setText('bioAlasan', d.alasan_keluar);
                setText('bioSkhun', d.skhun || '-');

                setText('bioAlamat', d.alamat_jalan || '-');
                const rtRwDusun = [
                    d.rt ? 'RT ' + d.rt : null,
                    d.rw ? 'RW ' + d.rw : null,
                    d.dusun ? 'Dusun ' + d.dusun : null
                ].filter(Boolean).join(' / ');
                setText('bioRtRwDusun', rtRwDusun || '-');
                setText('bioKelurahan', d.kelurahan || '-');
                const kecPos = [
                    d.kecamatan ? 'Kec. ' + d.kecamatan : null,
                    d.kode_pos ? 'Kode Pos ' + d.kode_pos : null
                ].filter(Boolean).join(' • ');
                setText('bioKecamatanPos', kecPos || '-');
                setText('bioJenisTinggal', d.jenis_tinggal || '-');
                setText('bioTransportasi', d.alat_transportasi || '-');

                setText('bioHp', d.nomor_telepon_seluler || '-');
                setText('bioTelepon', d.nomor_telepon_rumah || '-');
                setText('bioEmail', d.email || '-');
                const kpsInfo = [
                    d.penerima_kps ? 'Penerima: ' + d.penerima_kps : null,
                    d.no_kps ? 'No. KPS: ' + d.no_kps : null
                ].filter(Boolean).join(' • ');
                setText('bioKps', kpsInfo || '-');

                const ayahInfo = [
                    d.nama_ayah ? 'Nama: ' + d.nama_ayah : null,
                    d.tahun_lahir_ayah ? 'Thn Lahir: ' + d.tahun_lahir_ayah : null,
                    d.jenjang_pendidikan_ayah ? 'Pendidikan: ' + d.jenjang_pendidikan_ayah : null,
                    d.pekerjaan_ayah ? 'Pekerjaan: ' + d.pekerjaan_ayah : null,
                    d.penghasilan_ayah ? 'Penghasilan: ' + d.penghasilan_ayah : null,
                    (d.kebutuhan_khusus_ayah && d.kebutuhan_khusus_ayah !== 'Tidak ada') ? 'Khusus: ' + d.kebutuhan_khusus_ayah : null
                ].filter(Boolean).join(' • ');
                setText('bioAyah', ayahInfo || 'Belum ada data');

                const ibuInfo = [
                    d.nama_ibu ? 'Nama: ' + d.nama_ibu : null,
                    d.tahun_lahir_ibu ? 'Thn Lahir: ' + d.tahun_lahir_ibu : null,
                    d.jenjang_pendidikan_ibu ? 'Pendidikan: ' + d.jenjang_pendidikan_ibu : null,
                    d.pekerjaan_ibu ? 'Pekerjaan: ' + d.pekerjaan_ibu : null,
                    d.penghasilan_ibu ? 'Penghasilan: ' + d.penghasilan_ibu : null,
                    (d.kebutuhan_khusus_ibu && d.kebutuhan_khusus_ibu !== 'Tidak ada') ? 'Khusus: ' + d.kebutuhan_khusus_ibu : null
                ].filter(Boolean).join(' • ');
                setText('bioIbu', ibuInfo || 'Belum ada data');

                const waliInfo = [
                    d.nama_wali ? 'Nama: ' + d.nama_wali : null,
                    d.tahun_lahir_wali ? 'Thn Lahir: ' + d.tahun_lahir_wali : null,
                    d.jenjang_pendidikan_wali ? 'Pendidikan: ' + d.jenjang_pendidikan_wali : null,
                    d.pekerjaan_wali ? 'Pekerjaan: ' + d.pekerjaan_wali : null,
                    d.penghasilan_wali ? 'Penghasilan: ' + d.penghasilan_wali : null
                ].filter(Boolean).join(' • ');
                setText('bioWali', waliInfo || 'Tidak Ada (Ikut Orang Tua)');

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

function escapeHtml(str) {
    if (str === null || str === undefined) return "";
    return String(str)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

// =========================================================================
// MODAL IMPORT EXCEL PESERTA DIDIK KELUAR / TIDAK AKTIF (FORMAT DAPODIK)
// =========================================================================
window.openImportExcelModal = function () {
    const modal = document.getElementById("modalImportExcel");
    if (!modal) return;
    const form = document.getElementById("formImportExcel");
    if (form) form.reset();
    const progress = document.getElementById("importExcelProgress");
    if (progress) progress.style.display = "none";
    const btnSubmit = document.getElementById("btnSubmitImportExcel");
    if (btnSubmit) btnSubmit.disabled = false;
    modal.style.display = "flex";
    document.body.style.overflow = "hidden";
};

window.closeImportExcelModal = function () {
    const modal = document.getElementById("modalImportExcel");
    if (!modal) return;
    modal.style.display = "none";
    document.body.style.overflow = "";
};

window.handleImportExcelSubmit = async function (e) {
    e.preventDefault();
    const form = e.target;
    const fileInput = document.getElementById("inputExcelFile");
    if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
        if (typeof Swal !== "undefined") {
            Swal.fire({
                title: "Berkas Belum Dipilih",
                text: "Silakan pilih berkas Excel atau CSV terlebih dahulu.",
                icon: "warning",
                confirmButtonColor: "#6366f1",
            });
        } else {
            alert("Silakan pilih berkas Excel atau CSV terlebih dahulu.");
        }
        return;
    }

    const progress = document.getElementById("importExcelProgress");
    const btnSubmit = document.getElementById("btnSubmitImportExcel");
    const btnText = document.getElementById("btnSubmitImportExcelText");

    if (progress) progress.style.display = "block";
    if (btnSubmit) btnSubmit.disabled = true;
    if (btnText) btnText.textContent = "Mengimpor Data...";

    const formData = new FormData(form);

    try {
        const res = await fetch(form.action, {
            method: "POST",
            headers: {
                Accept: "application/json",
                "X-Requested-With": "XMLHttpRequest",
            },
            body: formData,
        });

        const json = await res.json();

        if (res.ok && json.status === "success") {
            window.closeImportExcelModal();

            let detailHtml = escapeHtml(json.message);
            if (json.errors && json.errors.length > 0) {
                detailHtml +=
                    "<br><br><small style='color: #ef4444; text-align: left; display: block; max-height: 120px; overflow-y: auto;'>" +
                    json.errors.map((err) => escapeHtml(err)).join("<br>") +
                    "</small>";
            }

            if (typeof Swal !== "undefined") {
                await Swal.fire({
                    title: "Import Berhasil!",
                    html: detailHtml,
                    icon: "success",
                    confirmButtonColor: "#10b981",
                });
            } else {
                alert(json.message);
            }

            const targetUrl = new URL(window.location.origin + window.location.pathname);
            if (typeof window.refreshLiveTable === "function") {
                window.refreshLiveTable(targetUrl.toString());
            } else {
                window.location.href = targetUrl.toString();
            }
        } else {
            let errorMsg = json.message || "Gagal mengimpor berkas Excel.";
            let errorHtml = escapeHtml(errorMsg);
            if (json.errors && Array.isArray(json.errors) && json.errors.length > 0) {
                errorHtml +=
                    "<br><br><small style='color: #ef4444; text-align: left; display: block; max-height: 140px; overflow-y: auto;'>" +
                    json.errors.map((err) => escapeHtml(err)).join("<br>") +
                    "</small>";
            } else if (json.errors && typeof json.errors === "object") {
                const flat = Object.values(json.errors).flat().join("<br>");
                errorHtml +=
                    "<br><br><small style='color: #ef4444; text-align: left; display: block;'>" +
                    escapeHtml(flat) +
                    "</small>";
            }

            if (typeof Swal !== "undefined") {
                Swal.fire({
                    title: "Gagal Mengimpor",
                    html: errorHtml,
                    icon: "error",
                    confirmButtonColor: "#ef4444",
                });
            } else {
                alert(errorMsg);
            }
        }
    } catch (err) {
        if (typeof Swal !== "undefined") {
            Swal.fire({
                title: "Kesalahan Jaringan",
                text:
                    "Gagal terhubung ke server: " +
                    (err.message || "Terjadi kesalahan."),
                icon: "error",
                confirmButtonColor: "#ef4444",
            });
        } else {
            alert("Kesalahan jaringan: " + err.message);
        }
    } finally {
        if (progress) progress.style.display = "none";
        if (btnSubmit) btnSubmit.disabled = false;
        if (btnText) btnText.textContent = "Import Sekarang";
    }
};

document.addEventListener("click", function (e) {
    if (e.target && e.target.id === "modalImportExcel") {
        window.closeImportExcelModal();
    }
});

