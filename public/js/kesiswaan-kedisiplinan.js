/**
 * Logika JavaScript Modular: Kedisiplinan Siswa (Tata Tertib, Poin, Pembinaan, Pemanggilan)
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
    const btnOpenTataTertib = document.getElementById('btnOpenTataTertibModal');
    const tatibForm = document.getElementById('formTataTertib');
    const inputKodeTatib = document.getElementById('inputTatibKode');
    const selectKategoriTatib = document.getElementById('selectTatibKategori');

    function syncNextKodeTatib(force = false) {
        if (!inputKodeTatib || !selectKategoriTatib || !tatibForm) return;
        const nextKodeUrl = tatibForm.getAttribute('data-next-kode-url');
        if (!nextKodeUrl) return;

        const kategori = selectKategoriTatib.value;
        fetch(`${nextKodeUrl}?kategori=${encodeURIComponent(kategori)}`, {
            headers: { 'Accept': 'application/json' }
        })
        .then(res => res.json())
        .then(data => {
            if (data?.kode) {
                inputKodeTatib.placeholder = `Otomatis: ${data.kode}`;
                if (force || !inputKodeTatib.dataset.userEdited) {
                    inputKodeTatib.value = data.kode;
                }
            }
        })
        .catch(() => {});
    }

    if (inputKodeTatib) {
        inputKodeTatib.addEventListener('input', () => {
            inputKodeTatib.dataset.userEdited = inputKodeTatib.value.trim() !== '' ? '1' : '';
        });
    }

    if (selectKategoriTatib) {
        selectKategoriTatib.addEventListener('change', () => {
            inputKodeTatib.dataset.userEdited = '';
            syncNextKodeTatib(true);
        });
    }

    if (btnOpenTataTertib) {
        btnOpenTataTertib.addEventListener('click', () => {
            openModal('#modalTataTertib');
            inputKodeTatib.dataset.userEdited = '';
            syncNextKodeTatib(true);
        });
    }

    const btnOpenImportTatib = document.getElementById('btnOpenImportTatibModal');
    if (btnOpenImportTatib) {
        btnOpenImportTatib.addEventListener('click', () => openModal('#modalImportTataTertib'));
    }

    const btnOpenPoin = document.getElementById('btnOpenPoinModal');
    if (btnOpenPoin) {
        btnOpenPoin.addEventListener('click', () => openModal('#modalPoin'));
    }

    const btnOpenPembinaan = document.getElementById('btnOpenPembinaanModal');
    if (btnOpenPembinaan) {
        btnOpenPembinaan.addEventListener('click', () => openModal('#modalPembinaan'));
    }

    const btnOpenPanggilan = document.getElementById('btnOpenPanggilanModal');
    if (btnOpenPanggilan) {
        btnOpenPanggilan.addEventListener('click', () => openModal('#modalPanggilan'));
    }

    // 3. Helper Submit Form AJAX
    function handleFormSubmit(formId, loadingText, successCallback) {
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
                    if (successCallback) {
                        successCallback(data);
                    } else {
                        Swal.fire('Berhasil!', data.message, 'success').then(() => location.reload());
                    }
                } else {
                    Swal.fire('Gagal!', data.message || 'Terjadi kesalahan.', 'error');
                }
            })
            .catch(err => {
                Swal.fire('Error!', 'Gagal memproses data.', 'error');
            });
        });
    }

    // 4. Inisialisasi Handlers Form
    handleFormSubmit('formTataTertib', 'Menyimpan aturan tata tertib...');
    handleFormSubmit('formImportTataTertib', 'Mengimpor aturan tata tertib...');
    handleFormSubmit('formPoin', 'Mencatat pelanggaran poin siswa...');
    handleFormSubmit('formPembinaan', 'Menyimpan sesi pembinaan konseling...');

    handleFormSubmit('formPanggilan', 'Menerbitkan surat pemanggilan orang tua...', function (data) {
        Swal.fire({
            title: 'Berhasil!',
            text: data.message,
            icon: 'success',
            showCancelButton: true,
            confirmButtonText: 'Cetak Surat Panggilan',
            cancelButtonText: 'Selesai',
            confirmButtonColor: '#2563eb'
        }).then((res) => {
            if (res.isConfirmed && data.cetak_url) {
                window.open(data.cetak_url, '_blank');
            }
            location.reload();
        });
    });

    // 5. Datatable Realtime Live Search & Entri perPage Standar SAE
    const searchInput = document.getElementById("liveSearch");
    const clearBtn = document.getElementById("clearSearch");
    const perPageSelect = document.getElementById("perPageSelect");
    const filterStatus = document.getElementById("filterStatus");
    const filterTingkat = document.getElementById("filterTingkat");
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

        if (filterStatus) {
            if (filterStatus.value) {
                url.searchParams.set("status", filterStatus.value);
            } else {
                url.searchParams.delete("status");
            }
        }

        if (filterTingkat) {
            if (filterTingkat.value) {
                url.searchParams.set("tingkat", filterTingkat.value);
            } else {
                url.searchParams.delete("tingkat");
            }
        }

        if (filterKategori) {
            if (filterKategori.value) {
                url.searchParams.set("kategori", filterKategori.value);
            } else {
                url.searchParams.delete("kategori");
            }
        }

        url.searchParams.delete("riwayat_page");
        url.searchParams.delete("pembinaan_page");
        url.searchParams.delete("pemanggilan_page");
        url.searchParams.delete("page");

        if (typeof window.refreshLiveTable === "function") {
            window.refreshLiveTable(url.toString());
        } else {
            window.location.href = url.toString();
        }
    }

    // Delegated Change Handler untuk filter Kedisiplinan
    document.addEventListener("change", function (e) {
        if (!e.target || e.target.tagName !== "SELECT") return;
        if (
            e.target.closest("#tableDataContainer, .datatable-toolbar, .dash-table-toolbar, .card") &&
            (e.target.id === "filterStatus" || e.target.id === "filterTingkat" || e.target.id === "filterKategori" || e.target.id === "perPageSelect")
        ) {
            e.preventDefault();
            e.stopPropagation();
            applyFilter();
        }
    });

    // 6. Inisialisasi Chart Informatif Rekapitulasi Kedisiplinan (Dapat dirender ulang saat tab switch / refreshLiveTable)
    let kedisiplinanChartInstances = [];

    function renderKedisiplinanCharts() {
        kedisiplinanChartInstances.forEach(c => {
            try { c.destroy(); } catch (e) {}
        });
        kedisiplinanChartInstances = [];

        const chartDataEl = document.getElementById('kedisiplinanChartData');
        if (!chartDataEl || typeof Chart === 'undefined') return;

        try {
            const cData = JSON.parse(chartDataEl.textContent || '{}');
            if (cData.activeTab === 'rekap') {
                // Chart 1: Tren Pelanggaran Bulanan
                const ctxTren = document.getElementById('chartTrenPelanggaran');
                if (ctxTren && cData.tren) {
                    kedisiplinanChartInstances.push(new Chart(ctxTren, {
                        type: 'line',
                        data: {
                            labels: cData.tren.labels || [],
                            datasets: [{
                                label: 'Kasus Pelanggaran',
                                data: cData.tren.data || [],
                                borderColor: '#3b82f6',
                                backgroundColor: 'rgba(59, 130, 246, 0.12)',
                                fill: true,
                                tension: 0.35,
                                pointRadius: 4,
                                pointBackgroundColor: '#2563eb'
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: { legend: { display: false } },
                            scales: {
                                y: { beginAtZero: true, ticks: { precision: 0 } }
                            }
                        }
                    }));
                }

                // Chart 2: Tingkat Keparahan (Ringan, Sedang, Berat)
                const ctxTingkat = document.getElementById('chartTingkatPelanggaran');
                if (ctxTingkat && cData.tingkat) {
                    kedisiplinanChartInstances.push(new Chart(ctxTingkat, {
                        type: 'doughnut',
                        data: {
                            labels: cData.tingkat.labels || [],
                            datasets: [{
                                data: cData.tingkat.data || [],
                                backgroundColor: ['#10b981', '#f59e0b', '#ef4444'],
                                borderWidth: 2,
                                borderColor: '#ffffff'
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } }
                            },
                            cutout: '62%'
                        }
                    }));
                }

                // Chart 3: Kategori Pelanggaran
                const ctxKategori = document.getElementById('chartKategoriPelanggaran');
                if (ctxKategori && cData.kategori) {
                    kedisiplinanChartInstances.push(new Chart(ctxKategori, {
                        type: 'bar',
                        data: {
                            labels: cData.kategori.labels || [],
                            datasets: [{
                                label: 'Kasus',
                                data: cData.kategori.data || [],
                                backgroundColor: ['#64748b', '#3b82f6', '#f59e0b', '#dc2626'],
                                borderRadius: 6
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: { legend: { display: false } },
                            scales: {
                                y: { beginAtZero: true, ticks: { precision: 0 } }
                            }
                        }
                    }));
                }

                // Chart 4: Status Resolusi & Sanggah
                const ctxStatus = document.getElementById('chartStatusPelanggaran');
                if (ctxStatus && cData.status) {
                    kedisiplinanChartInstances.push(new Chart(ctxStatus, {
                        type: 'doughnut',
                        data: {
                            labels: cData.status.labels || [],
                            datasets: [{
                                data: cData.status.data || [],
                                backgroundColor: ['#ef4444', '#f59e0b', '#3b82f6', '#10b981'],
                                borderWidth: 2,
                                borderColor: '#ffffff'
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } }
                            },
                            cutout: '62%'
                        }
                    }));
                }
            }
        } catch (e) {
            console.warn('Gagal memuat chart kedisiplinan:', e);
        }
    }

    renderKedisiplinanCharts();
    window.addEventListener('sae:tableRefreshed', renderKedisiplinanCharts);

    // 6b. Filter & Pencarian Siswa Terpadu di Modal Pembinaan & Pemanggilan
    function setupStudentFilter(searchInputId, selectId, detailId) {
        const searchInput = document.getElementById(searchInputId);
        const select = document.getElementById(selectId);
        const detail = detailId ? document.getElementById(detailId) : null;
        if (!searchInput || !select) return;

        searchInput.addEventListener('input', function () {
            const query = this.value.trim().toLowerCase();
            let firstMatch = null;
            Array.from(select.options).forEach((opt, idx) => {
                if (idx === 0) return; // Lewati placeholder
                const text = (opt.textContent || '').toLowerCase();
                const nama = (opt.getAttribute('data-nama') || '').toLowerCase();
                const nisn = (opt.getAttribute('data-nisn') || '').toLowerCase();
                const matches = !query || text.includes(query) || nama.includes(query) || nisn.includes(query);
                opt.hidden = !matches;
                if (matches && !firstMatch) firstMatch = opt;
            });
            if (query && firstMatch) {
                select.value = firstMatch.value;
                select.dispatchEvent(new Event('change'));
            }
        });

        select.addEventListener('change', function () {
            const opt = this.options[this.selectedIndex];
            if (!detail) return;
            if (opt && opt.value) {
                const aturan = opt.getAttribute('data-aturan') || '-';
                const poin = opt.getAttribute('data-poin') || '0';
                detail.style.display = 'block';
                detail.innerHTML = `<i class="fas fa-triangle-exclamation me-1"></i> Terpilih: <strong>${opt.textContent.split('•')[0].trim()}</strong> &bull; Aturan: <em>${aturan}</em> (+${poin} Poin)`;
            } else {
                detail.style.display = 'none';
            }
        });
    }

    setupStudentFilter('pembinaanSiswaSearch', 'pembinaanSiswaSelect', 'pembinaanSiswaDetail');
    setupStudentFilter('panggilanSiswaSearch', 'panggilanSiswaSelect', 'panggilanSiswaDetail');

    // 7. Modal Sanggah & Kompensasi Pelanggaran Poin
    document.addEventListener('click', function (e) {
        // Tombol Buka Sanggah
        const btnSanggah = e.target.closest('.btn-open-sanggah');
        if (btnSanggah) {
            e.preventDefault();
            const id = btnSanggah.getAttribute('data-id');
            const siswa = btnSanggah.getAttribute('data-siswa') || 'Siswa';
            const aturan = btnSanggah.getAttribute('data-aturan') || 'Pelanggaran';
            const poin = parseInt(btnSanggah.getAttribute('data-poin') || '0', 10);
            const sanksi = btnSanggah.getAttribute('data-sanksi') || '';

            const form = document.getElementById('formSanggah');
            if (form) {
                form.action = `/dashboard/kesiswaan/kedisiplinan/pelanggaran/${id}/sanggah`;
            }

            const elNama = document.getElementById('sanggahNamaSiswa');
            if (elNama) elNama.textContent = siswa;
            const elAturan = document.getElementById('sanggahNamaAturan');
            if (elAturan) elAturan.textContent = aturan;
            const elPoinAwal = document.getElementById('sanggahPoinAwal');
            if (elPoinAwal) elPoinAwal.textContent = `+${poin} Poin`;
            const inputPotong = document.getElementById('sanggahPoinPotong');
            if (inputPotong) {
                inputPotong.value = poin;
                inputPotong.max = poin;
            }

            // Prefill Rekomendasi Sanksi dari Tata Tertib & Datalist
            const infoRekomendasi = document.getElementById('sanggahRekomendasiInfo');
            const textRekomendasi = document.getElementById('sanggahRekomendasiText');
            const btnGunakan = document.getElementById('btnGunakanRekomendasi');
            const inputJenis = document.getElementById('sanggahJenisInput');

            if (sanksi && sanksi.trim() !== '' && sanksi !== '-') {
                if (infoRekomendasi) infoRekomendasi.style.display = 'block';
                if (textRekomendasi) textRekomendasi.textContent = sanksi;
                if (inputJenis) inputJenis.value = sanksi;
                if (btnGunakan) {
                    btnGunakan.onclick = function () {
                        if (inputJenis) inputJenis.value = sanksi;
                    };
                }
            } else {
                if (infoRekomendasi) infoRekomendasi.style.display = 'none';
                if (inputJenis && !inputJenis.value) {
                    inputJenis.value = 'Bakti Lingkungan & Sosial Sekolah (Kerja bakti taman, perpustakaan, pilah sampah)';
                }
            }

            openModal('#modalSanggah');
            return;
        }

        // Tombol Verifikasi & Selesaikan Sanggah Poin
        const btnVerifikasi = e.target.closest('.btn-verifikasi-sanggah');
        if (btnVerifikasi) {
            e.preventDefault();
            const id = btnVerifikasi.getAttribute('data-id');
            const siswa = btnVerifikasi.getAttribute('data-siswa') || 'Siswa';

            Swal.fire({
                title: 'Verifikasi Selesai?',
                text: `Konfirmasi bahwa ${siswa} telah menuntaskan masa pembinaan / kompensasi bakti sekolah? Poin pelanggaran akan diputihkan dan data dipindahkan ke Riwayat Pelanggaran.`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#059669',
                cancelButtonColor: '#64748b',
                confirmButtonText: '<i class="fas fa-check-circle"></i> Ya, Verifikasi & Selesai',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Memproses...',
                        text: 'Menyelesaikan sanggah & memutihkan poin...',
                        allowOutsideClick: false,
                        didOpen: () => Swal.showLoading()
                    });

                    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                    fetch(`/dashboard/kesiswaan/kedisiplinan/pelanggaran/${id}/verifikasi-sanggah`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                            'Accept': 'application/json'
                        }
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.status === 'success') {
                            Swal.fire('Berhasil!', data.message, 'success').then(() => {
                                if (typeof window.refreshLiveTable === 'function') {
                                    window.refreshLiveTable(window.location.href);
                                } else {
                                    location.reload();
                                }
                            });
                        } else {
                            Swal.fire('Gagal!', data.message || 'Terjadi kesalahan.', 'error');
                        }
                    })
                    .catch(() => {
                        Swal.fire('Error!', 'Gagal memproses verifikasi sanggah.', 'error');
                    });
                }
            });
            return;
        }

        // Tombol Terbitkan Surat Pemanggilan Ortu Cepat (Dari Tab Poin / Tindak Lanjut)
        const btnPanggil = e.target.closest('.btn-panggil-ortu-cepat');
        if (btnPanggil) {
            e.preventDefault();
            const siswaId = btnPanggil.getAttribute('data-siswa-id');
            const siswaNama = btnPanggil.getAttribute('data-siswa-nama');
            const alasan = btnPanggil.getAttribute('data-alasan');

            const selectSiswa = document.getElementById('panggilanSiswaSelect');
            if (selectSiswa && siswaId) {
                let opt = selectSiswa.querySelector(`option[value="${siswaId}"]`);
                if (!opt) {
                    opt = document.createElement('option');
                    opt.value = siswaId;
                    opt.textContent = siswaNama;
                    selectSiswa.appendChild(opt);
                }
                selectSiswa.value = siswaId;
                selectSiswa.dispatchEvent(new Event('change'));
            }

            const inputAlasan = document.querySelector('#formPanggilan textarea[name="alasan"], #formPanggilan input[name="alasan"]');
            if (inputAlasan && alasan) {
                inputAlasan.value = alasan;
            }

            openModal('#modalPanggilan');
            return;
        }

        // Tombol Detail Sanggah
        const btnDetail = e.target.closest('.btn-detail-sanggah');
        if (btnDetail) {
            e.preventDefault();
            const jenis = btnDetail.getAttribute('data-sanggah-jenis') || '-';
            const ket = btnDetail.getAttribute('data-sanggah-ket') || '-';
            const potong = btnDetail.getAttribute('data-sanggah-potong') || '0';
            const validator = btnDetail.getAttribute('data-sanggah-validator') || '-';
            const tgl = btnDetail.getAttribute('data-sanggah-tanggal') || '-';
            const bukti = btnDetail.getAttribute('data-sanggah-bukti') || '';
            const siswa = btnDetail.getAttribute('data-siswa') || '-';
            const aturan = btnDetail.getAttribute('data-aturan') || '-';

            let buktiHtml = '';
            if (bukti) {
                buktiHtml = `<div style="margin-top: 10px;">
                    <span style="font-weight: 700; color: var(--text-muted); font-size: 0.75rem;">Lampiran Bukti:</span>
                    <div style="margin-top: 4px;">
                        <a href="${bukti}" target="_blank" class="btn btn-outline" style="padding: 4px 10px; font-size: 0.75rem; border-radius: 6px; display: inline-flex; align-items: center; gap: 4px;">
                            <i class="fas fa-file-image"></i> Buka Berkas Lampiran
                        </a>
                    </div>
                </div>`;
            }

            const detailBody = document.getElementById('detailSanggahBody');
            if (detailBody) {
                detailBody.innerHTML = `
                    <div style="margin-bottom: 12px; padding-bottom: 10px; border-bottom: 1px solid var(--border-color);">
                        <div style="font-weight: 800; font-size: 1rem; color: var(--text-color);">${siswa}</div>
                        <div style="color: var(--text-muted); font-size: 0.8125rem;">${aturan}</div>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 12px;">
                        <div>
                            <div style="font-size: 0.75rem; color: var(--text-muted); font-weight: 700;">Bentuk Kompensasi:</div>
                            <div style="font-weight: 700; color: #059669;">${jenis}</div>
                        </div>
                        <div>
                            <div style="font-size: 0.75rem; color: var(--text-muted); font-weight: 700;">Poin Diputihkan:</div>
                            <div style="font-weight: 800; color: #059669;">-${potong} Poin</div>
                        </div>
                        <div>
                            <div style="font-size: 0.75rem; color: var(--text-muted); font-weight: 700;">Tanggal Sanggah:</div>
                            <div style="color: var(--text-color);">${tgl}</div>
                        </div>
                        <div>
                            <div style="font-size: 0.75rem; color: var(--text-muted); font-weight: 700;">Petugas Pembina:</div>
                            <div style="color: var(--text-color);">${validator}</div>
                        </div>
                    </div>
                    <div style="background: rgba(0,0,0,0.03); padding: 10px 12px; border-radius: 8px; border: 1px solid var(--border-color);">
                        <div style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 4px;">Catatan Pelaksanaan:</div>
                        <div style="color: var(--text-color); line-height: 1.4;">${ket}</div>
                    </div>
                    ${buktiHtml}
                `;
            }

            openModal('#modalDetailSanggah');
        }
    });

    handleFormSubmit('formSanggah', 'Menyimpan dan memvalidasi sanggahan...');
});
