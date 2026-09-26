/**
 * SAE — Target & Capaian Pekerjaan Tendik (Indikator Input Harian)
 * JS Modular (Kepatuhan Rule #2: Zero inline script di Blade)
 */

document.addEventListener('DOMContentLoaded', function () {
    // 1. Toolbar Dynamic Visibility Handler
    const selectPeriode = document.getElementById('selectPeriodeTipe');
    const wrapHari = document.getElementById('wrapFilterHari');
    const wrapBulan = document.getElementById('wrapFilterBulan');
    const wrapSemester = document.getElementById('wrapFilterSemester');
    const wrapTA = document.getElementById('wrapFilterTA');

    if (selectPeriode) {
        selectPeriode.addEventListener('change', function () {
            const val = this.value;

            if (wrapHari) wrapHari.style.display = (val === 'hari') ? 'flex' : 'none';
            if (wrapBulan) wrapBulan.style.display = (val === 'bulan' || val === 'triwulan' || val === 'tahun') ? 'flex' : 'none';
            if (wrapSemester) wrapSemester.style.display = (val === 'semester') ? 'flex' : 'none';
            if (wrapTA) wrapTA.style.display = (val === 'semester' || val === 'tahun_ajaran') ? 'flex' : 'none';
        });
    }

    // 2. Load Chart Data Payload
    const chartDataEl = document.getElementById('targetCapaianChartData');
    if (!chartDataEl || typeof Chart === 'undefined') return;

    let payload = {};
    try {
        payload = JSON.parse(chartDataEl.textContent);
    } catch (e) {
        console.error('Gagal parsing targetCapaianChartData', e);
        return;
    }

    const isDarkMode = document.documentElement.classList.contains('dark') ||
        window.matchMedia('(prefers-color-scheme: dark)').matches;
    const gridColor = isDarkMode ? 'rgba(255, 255, 255, 0.08)' : 'rgba(0, 0, 0, 0.06)';
    const textColor = isDarkMode ? '#94a3b8' : '#64748b';

    // Chart 1: Line / Area Chart — Target vs Realisasi
    const ctxTrend = document.getElementById('chartTargetVsRealisasi');
    if (ctxTrend && payload.timeSeries) {
        new Chart(ctxTrend, {
            type: 'line',
            data: {
                labels: payload.timeSeries.labels || [],
                datasets: [
                    {
                        label: 'Target Pekerjaan',
                        data: payload.timeSeries.target || [],
                        borderColor: '#6366f1',
                        backgroundColor: 'rgba(99, 102, 241, 0.12)',
                        borderWidth: 2,
                        borderDash: [5, 5],
                        fill: false,
                        tension: 0.3,
                        pointRadius: 3,
                    },
                    {
                        label: 'Realisasi Capaian',
                        data: payload.timeSeries.realisasi || [],
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.15)',
                        borderWidth: 2.5,
                        fill: true,
                        tension: 0.3,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false,
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        padding: 10,
                        backgroundColor: 'rgba(15, 23, 42, 0.9)',
                    }
                },
                scales: {
                    x: {
                        grid: { color: gridColor },
                        ticks: { color: textColor, font: { size: 11 }, maxRotation: 45, autoSkip: true }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: gridColor },
                        ticks: { color: textColor, font: { size: 11 }, precision: 0 }
                    }
                }
            }
        });
    }

    // Chart 2: Doughnut Chart — Proporsi Status Pelaksanaan
    const ctxStatus = document.getElementById('chartStatusTugas');
    if (ctxStatus && payload.status) {
        new Chart(ctxStatus, {
            type: 'doughnut',
            data: {
                labels: payload.status.labels || ['Selesai', 'Proses', 'Tertunda'],
                datasets: [{
                    data: payload.status.data || [0, 0, 0],
                    backgroundColor: ['#10b981', '#f59e0b', '#ef4444'],
                    borderWidth: 2,
                    borderColor: isDarkMode ? '#1e293b' : '#ffffff',
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: textColor,
                            boxWidth: 12,
                            padding: 12,
                            font: { size: 11 }
                        }
                    }
                },
                cutout: '65%'
            }
        });
    }

    // Chart 3: Bar Chart — Distribusi Capaian Berdasarkan Bidang Kerja
    const ctxBidang = document.getElementById('chartBebanBidang');
    if (ctxBidang && payload.bidang) {
        new Chart(ctxBidang, {
            type: 'bar',
            data: {
                labels: payload.bidang.labels || [],
                datasets: [{
                    label: 'Aktivitas Tercatat',
                    data: payload.bidang.data || [],
                    backgroundColor: 'rgba(59, 130, 246, 0.75)',
                    borderColor: '#3b82f6',
                    borderWidth: 1,
                    borderRadius: 6,
                    maxBarThickness: 36,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        padding: 10,
                        backgroundColor: 'rgba(15, 23, 42, 0.9)',
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { color: textColor, font: { size: 11 } }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: gridColor },
                        ticks: { color: textColor, font: { size: 11 }, precision: 0 }
                    }
                }
            }
        });
    }

    // 3. Modal Form Tambah / Edit Target Kinerja
    const modalTarget = document.getElementById('modalTargetKinerja');
    const formTarget = document.getElementById('formTargetKinerja');
    const modalTitle = document.getElementById('modalTargetTitle');
    const methodOverride = document.getElementById('targetMethodOverride');
    const btnOpenTarget = document.getElementById('btnOpenModalTarget');
    const btnCloseTarget = document.getElementById('btnCloseModalTarget');
    const btnCancelTarget = document.getElementById('btnCancelModalTarget');

    const inputBidang = document.getElementById('inputTargetBidang');
    const inputSasaran = document.getElementById('inputTargetSasaran');
    const inputIndikator = document.getElementById('inputTargetIndikator');
    const inputKuantitas = document.getElementById('inputTargetKuantitas');
    const inputSatuan = document.getElementById('inputTargetSatuan');

    const defaultStoreUrl = formTarget ? formTarget.getAttribute('action') : '';

    function openModalTarget() {
        if (!modalTarget) return;
        modalTarget.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    function closeModalTarget() {
        if (!modalTarget) return;
        modalTarget.style.display = 'none';
        document.body.style.overflow = '';
        if (formTarget) {
            formTarget.reset();
            formTarget.action = defaultStoreUrl;
            if (methodOverride) methodOverride.innerHTML = '';
        }
        if (modalTitle) {
            modalTitle.innerHTML = '<i class="fas fa-bullseye text-primary"></i> Tambah Sasaran &amp; Target Kinerja';
        }
    }

    if (btnOpenTarget) {
        btnOpenTarget.addEventListener('click', function () {
            closeModalTarget();
            openModalTarget();
        });
    }

    document.querySelectorAll('.btn-tambah-target-modal').forEach(btn => {
        btn.addEventListener('click', function () {
            closeModalTarget();
            openModalTarget();
        });
    });

    if (btnCloseTarget) btnCloseTarget.addEventListener('click', closeModalTarget);
    if (btnCancelTarget) btnCancelTarget.addEventListener('click', closeModalTarget);

    if (modalTarget) {
        modalTarget.addEventListener('click', function (e) {
            if (e.target === modalTarget) closeModalTarget();
        });
    }

    // Edit Target Kinerja
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-edit-target');
        if (!btn) return;

        const id = btn.getAttribute('data-id');
        const bidang = btn.getAttribute('data-bidang');
        const sasaran = btn.getAttribute('data-sasaran');
        const indikator = btn.getAttribute('data-indikator');
        const kuantitas = btn.getAttribute('data-kuantitas');
        const satuan = btn.getAttribute('data-satuan');

        if (formTarget) {
            formTarget.action = '/dashboard/tendik/target-capaian/' + id;
            if (methodOverride) {
                methodOverride.innerHTML = '<input type="hidden" name="_method" value="PUT">';
            }
        }

        if (modalTitle) {
            modalTitle.innerHTML = '<i class="fas fa-pen-to-square text-warning"></i> Edit Sasaran &amp; Target Kinerja';
        }

        if (inputBidang) inputBidang.value = bidang || 'kepegawaian';
        if (inputSasaran) inputSasaran.value = sasaran || '';
        if (inputIndikator) inputIndikator.value = indikator || '';
        if (inputKuantitas) inputKuantitas.value = kuantitas || 1;
        if (inputSatuan) inputSatuan.value = satuan || 'dokumen';

        openModalTarget();
    });

    // Delete Confirmation dengan SweetAlert2
    document.addEventListener('submit', function (e) {
        const form = e.target;
        if (form.getAttribute('data-confirm') === 'delete') {
            e.preventDefault();
            const name = form.getAttribute('data-name') || 'sasaran ini';

            if (window.Swal) {
                Swal.fire({
                    title: 'Hapus Sasaran & Target?',
                    text: `Apakah Anda yakin ingin menghapus "${name}"? Tindakan ini tidak dapat dibatalkan.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Hapus',
                    cancelButtonText: 'Batal',
                    confirmButtonColor: '#ef4444',
                    cancelButtonColor: '#6b7280'
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.removeAttribute('data-confirm');
                        form.submit();
                    }
                });
            } else {
                if (confirm(`Hapus "${name}"?`)) {
                    form.removeAttribute('data-confirm');
                    form.submit();
                }
            }
        }
    });

    // 4. Toggle Collapsible Table Handler (Monitoring Harian & Rekap Capaian)
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-toggle-collapse');
        if (!btn) return;

        const targetId = btn.getAttribute('data-target');
        const targetEl = document.querySelector(targetId);
        if (!targetEl) return;

        const isHidden = targetEl.style.display === 'none' || !targetEl.style.display;
        if (isHidden) {
            targetEl.style.display = 'block';
            const icon = btn.querySelector('.toggle-icon');
            const text = btn.querySelector('.toggle-text');
            if (icon) {
                icon.classList.remove('fa-chevron-down');
                icon.classList.add('fa-chevron-up');
            }
            if (text) text.textContent = 'Tutup Tabel';
            btn.classList.add('active');
        } else {
            targetEl.style.display = 'none';
            const icon = btn.querySelector('.toggle-icon');
            const text = btn.querySelector('.toggle-text');
            if (icon) {
                icon.classList.remove('fa-chevron-up');
                icon.classList.add('fa-chevron-down');
            }
            if (text) text.textContent = 'Buka Tabel';
            btn.classList.remove('active');
        }
    });
});
