/**
 * SAE - Orang Tua Kehadiran Script
 * Handles Category toggle, Calendar View, Table View, Asynchronous Month Switching,
 * and Interactive Date Details including e-Izin, Surat Izin, and Kedisiplinan.
 */

document.addEventListener('DOMContentLoaded', function () {
    // 1. Kategori switcher & view switcher state
    const selectKehadiran = document.getElementById('selectKategoriKehadiran');
    const calHeaderTitle = document.getElementById('calHeaderTitle');
    const calFilterAlert = document.getElementById('calFilterAlert');
    const calFilterAlertText = document.getElementById('calFilterAlertText');
    const btnResetFilter = document.getElementById('btnResetFilter');
    const calLegendBar = document.getElementById('calLegendBar');
    const btnKalender = document.getElementById('btnViewKalender');
    const btnTabel = document.getElementById('btnViewTabel');
    const viewKalender = document.getElementById('viewKalenderHarian');
    const panelTabelContainer = document.getElementById('panelTabelContainer');

    const panelMap = {
        subHarian: document.getElementById('subPanelHarian'),
        subMapel: document.getElementById('subPanelMapel'),
        subIzinKeluar: document.getElementById('subPanelIzinKeluar'),
        subSuratIzin: document.getElementById('subPanelSuratIzin'),
        subKedisiplinan: document.getElementById('subPanelKedisiplinan'),
    };

    const categoryMeta = {
        subHarian: {
            title: '<i class="fas fa-id-card-clip text-primary me-1"></i> Log Presensi Harian Masuk &amp; Pulang',
            filterClass: '',
            targetSelector: '',
            alertHtml: '',
            alertStyle: ''
        },
        subMapel: {
            title: '<i class="fas fa-book-open text-success me-1"></i> Presensi Per Mata Pelajaran (KBM)',
            filterClass: 'cal-filter-mapel',
            targetSelector: '.cal-day-cell[data-has-mapel="1"]',
            alertHtml: '<i class="fas fa-book-open me-1"></i> <strong>Filter Aktif:</strong> Menampilkan Jadwal &amp; Kehadiran Mata Pelajaran. Tanggal dengan KBM disorot hijau.',
            alertStyle: 'background: rgba(16, 185, 129, 0.12); color: #047857; border: 1px solid rgba(16, 185, 129, 0.3);'
        },
        subIzinKeluar: {
            title: '<i class="fas fa-ticket-alt text-warning me-1"></i> Riwayat Transaksi e-Izin Keluar / Masuk Siswa',
            filterClass: 'cal-filter-izin-keluar',
            targetSelector: '.cal-day-cell[data-has-izin-keluar="1"]',
            alertHtml: '<i class="fas fa-ticket-alt me-1"></i> <strong>Filter Aktif:</strong> Menampilkan e-Izin Keluar Sekolah. Tanggal dengan tiket ditandai border oranye.',
            alertStyle: 'background: rgba(245, 158, 11, 0.12); color: #b45309; border: 1px solid rgba(245, 158, 11, 0.3);'
        },
        subSuratIzin: {
            title: '<i class="fas fa-envelope-open-text text-primary me-1"></i> Daftar Pengajuan Surat Permohonan Izin / Sakit',
            filterClass: 'cal-filter-surat-izin',
            targetSelector: '.cal-day-cell[data-has-surat-izin="1"]',
            alertHtml: '<i class="fas fa-envelope-open-text me-1"></i> <strong>Filter Aktif:</strong> Menampilkan Surat Izin / Sakit. Tanggal pengajuan disorot ungu.',
            alertStyle: 'background: rgba(99, 102, 241, 0.12); color: #4338ca; border: 1px solid rgba(99, 102, 241, 0.3);'
        },
        subKedisiplinan: {
            title: '<i class="fas fa-triangle-exclamation text-danger me-1"></i> Catatan Tata Tertib &amp; Kedisiplinan Siswa',
            filterClass: 'cal-filter-kedisiplinan',
            targetSelector: '.cal-day-cell[data-has-kedisiplinan="1"]',
            alertHtml: '<i class="fas fa-triangle-exclamation me-1"></i> <strong>Filter Aktif:</strong> Menampilkan Catatan Kedisiplinan. Tanggal kejadian disorot merah.',
            alertStyle: 'background: rgba(239, 68, 68, 0.12); color: #b91c1c; border: 1px solid rgba(239, 68, 68, 0.3);'
        }
    };

    let activeCategory = selectKehadiran ? selectKehadiran.value : 'subHarian';
    let activeView = 'kalender';

    function refreshCalendarDisplay() {
        const cells = document.querySelectorAll('.cal-day-cell');
        cells.forEach(cell => {
            const infoStr = cell.getAttribute('data-day-info');
            if (!infoStr) return;
            let info = {};
            try { info = JSON.parse(infoStr); } catch (e) {}

            const badgeEl = cell.querySelector('.cal-badge');
            const indicatorsEl = cell.querySelector('.cal-indicators');

            if (activeCategory === 'subIzinKeluar') {
                if (info.has_izin_keluar) {
                    cell.style.cssText = 'border: 2px solid #f59e0b !important; background: rgba(245, 158, 11, 0.18) !important; opacity: 1 !important; transform: scale(1.03); z-index: 3; box-shadow: 0 0 8px rgba(245, 158, 11, 0.35);';
                    if (badgeEl) {
                        badgeEl.innerHTML = '<i class="fas fa-ticket-alt" style="color: #d97706;"></i>';
                        badgeEl.style.cssText = 'background: rgba(245, 158, 11, 0.25); color: #d97706; border: 1px solid #f59e0b; display: inline-flex; align-items: center; justify-content: center; width: 22px; height: 22px; border-radius: 5px;';
                    }
                } else {
                    cell.style.cssText = 'opacity: 0.22 !important; filter: grayscale(90%); transform: none; box-shadow: none;';
                }
            } else if (activeCategory === 'subSuratIzin') {
                if (info.has_surat_izin) {
                    cell.style.cssText = 'border: 2px solid #6366f1 !important; background: rgba(99, 102, 241, 0.18) !important; opacity: 1 !important; transform: scale(1.03); z-index: 3; box-shadow: 0 0 8px rgba(99, 102, 241, 0.35);';
                    if (badgeEl) {
                        badgeEl.innerHTML = '<i class="fas fa-envelope-open-text" style="color: #6366f1;"></i>';
                        badgeEl.style.cssText = 'background: rgba(99, 102, 241, 0.25); color: #6366f1; border: 1px solid #6366f1; display: inline-flex; align-items: center; justify-content: center; width: 22px; height: 22px; border-radius: 5px;';
                    }
                } else {
                    cell.style.cssText = 'opacity: 0.22 !important; filter: grayscale(90%); transform: none; box-shadow: none;';
                }
            } else if (activeCategory === 'subKedisiplinan') {
                if (info.has_kedisiplinan) {
                    cell.style.cssText = 'border: 2px solid #ef4444 !important; background: rgba(239, 68, 68, 0.18) !important; opacity: 1 !important; transform: scale(1.03); z-index: 3; box-shadow: 0 0 8px rgba(239, 68, 68, 0.35);';
                    if (badgeEl) {
                        badgeEl.innerHTML = '<i class="fas fa-triangle-exclamation" style="color: #ef4444;"></i>';
                        badgeEl.style.cssText = 'background: rgba(239, 68, 68, 0.25); color: #ef4444; border: 1px solid #ef4444; display: inline-flex; align-items: center; justify-content: center; width: 22px; height: 22px; border-radius: 5px;';
                    }
                } else {
                    cell.style.cssText = 'opacity: 0.22 !important; filter: grayscale(90%); transform: none; box-shadow: none;';
                }
            } else if (activeCategory === 'subMapel') {
                if (info.has_mapel) {
                    cell.style.cssText = 'border: 2px solid #10b981 !important; background: rgba(16, 185, 129, 0.18) !important; opacity: 1 !important; transform: scale(1.03); z-index: 3; box-shadow: 0 0 8px rgba(16, 185, 129, 0.35);';
                    if (badgeEl) {
                        badgeEl.innerHTML = '<i class="fas fa-book-open" style="color: #10b981;"></i>';
                        badgeEl.style.cssText = 'background: rgba(16, 185, 129, 0.25); color: #10b981; border: 1px solid #10b981; display: inline-flex; align-items: center; justify-content: center; width: 22px; height: 22px; border-radius: 5px;';
                    }
                } else {
                    cell.style.cssText = 'opacity: 0.22 !important; filter: grayscale(90%); transform: none; box-shadow: none;';
                }
            } else {
                // subHarian (Presensi Normal)
                cell.style.cssText = `background: ${info.bg || 'transparent'}; border-color: ${info.border || 'var(--border-color)'}; opacity: 1; transform: none; box-shadow: none;`;
                if (badgeEl) {
                    badgeEl.innerHTML = `<i class="${info.icon || 'fas fa-calendar-day'}"></i>`;
                    badgeEl.style.cssText = `background: ${info.bg || 'transparent'}; color: ${info.color || 'var(--text-color)'}; border: 1px solid ${info.border || 'transparent'};`;
                }
            }
        });
    }

    function syncView() {
        const meta = categoryMeta[activeCategory] || categoryMeta.subHarian;

        // 1. Update header title
        if (calHeaderTitle) {
            calHeaderTitle.innerHTML = meta.title;
        }

        // 2. Update Tabs
        if (btnKalender && btnTabel) {
            if (activeView === 'kalender') {
                btnKalender.classList.add('active');
                btnTabel.classList.remove('active');
            } else {
                btnTabel.classList.add('active');
                btnKalender.classList.remove('active');
            }
        }

        // 3. Switch Mode Containers
        if (activeView === 'kalender') {
            if (viewKalender) viewKalender.style.display = 'block';
            if (panelTabelContainer) panelTabelContainer.style.display = 'none';
            if (calLegendBar) calLegendBar.style.display = (activeCategory === 'subHarian') ? 'flex' : 'none';

            // Alert banner
            if (calFilterAlert && calFilterAlertText) {
                if (meta.alertHtml) {
                    calFilterAlertText.innerHTML = meta.alertHtml;
                    calFilterAlert.style.cssText = meta.alertStyle + ' display: flex; margin-bottom: 12px; padding: 10px 14px; border-radius: 8px; font-size: 0.8125rem; font-weight: 600; align-items: center; justify-content: space-between;';
                } else {
                    calFilterAlert.style.display = 'none';
                }
            }

            // Refresh cells sesuai kategori
            refreshCalendarDisplay();

            // Auto select matching date cell
            let targetCell = null;
            if (meta.targetSelector) {
                targetCell = document.querySelector(meta.targetSelector);
            }
            if (!targetCell) {
                targetCell = document.querySelector('.cal-day-cell.cal-day-today') || document.querySelector('.cal-day-cell');
            }
            if (targetCell) {
                targetCell.click();
            }
        } else {
            // Mode Tabel
            if (viewKalender) viewKalender.style.display = 'none';
            if (panelTabelContainer) panelTabelContainer.style.display = 'block';
            if (calLegendBar) calLegendBar.style.display = 'none';
            if (calFilterAlert) calFilterAlert.style.display = 'none';

            // Tampilkan hanya subPanel yang aktif sesuai activeCategory
            Object.keys(panelMap).forEach(key => {
                const el = panelMap[key];
                if (el) {
                    el.style.display = (key === activeCategory) ? 'block' : 'none';
                }
            });

            const viewTabelHarian = document.getElementById('viewTabelHarian');
            if (viewTabelHarian && activeCategory === 'subHarian') {
                viewTabelHarian.style.display = 'block';
            }
        }
    }

    if (selectKehadiran) {
        selectKehadiran.addEventListener('change', function () {
            activeCategory = this.value;
            syncView();
        });
    }

    if (btnKalender) {
        btnKalender.addEventListener('click', function () {
            activeView = 'kalender';
            syncView();
        });
    }

    if (btnTabel) {
        btnTabel.addEventListener('click', function () {
            activeView = 'tabel';
            syncView();
        });
    }

    if (btnResetFilter) {
        btnResetFilter.addEventListener('click', function () {
            activeCategory = 'subHarian';
            if (selectKehadiran) selectKehadiran.value = 'subHarian';
            syncView();
        });
    }

    // 3. Detail card renderer for selected date
    const detailBox = document.getElementById('detailPresensiBox');

    function updateDetail(info) {
        if (!info) return;

        const detailDate = document.getElementById('detailDate');
        const detailStatusBadge = document.getElementById('detailStatusBadge');
        const detailJamMasuk = document.getElementById('detailJamMasuk');
        const detailJamPulang = document.getElementById('detailJamPulang');
        const detailMetode = document.getElementById('detailMetode');
        const detailKeterangan = document.getElementById('detailKeterangan');
        const detailIcon = document.getElementById('detailIcon');
        const detailIconBox = document.getElementById('detailIconBox');
        const detailExtraBox = document.getElementById('detailExtraBox');
        const detailExtraContent = document.getElementById('detailExtraContent');

        if (detailDate) detailDate.textContent = info.date_formatted || info.date;

        if (activeCategory === 'subIzinKeluar') {
            if (info.has_izin_keluar && Array.isArray(info.izin_keluar_items) && info.izin_keluar_items.length > 0) {
                const firstTicket = info.izin_keluar_items[0];
                if (detailJamMasuk) detailJamMasuk.textContent = firstTicket.jam_keluar;
                if (detailJamPulang) detailJamPulang.textContent = firstTicket.jam_kembali || '--:--';
                if (detailMetode) detailMetode.textContent = 'Tiket ' + firstTicket.nomor_tiket;
                if (detailKeterangan) detailKeterangan.textContent = firstTicket.alasan || '-';

                if (detailStatusBadge) {
                    detailStatusBadge.innerHTML = `<span class="badge" style="background: rgba(245, 158, 11, 0.2); color: #b45309; border: 1px solid #f59e0b; font-weight:700; padding:4px 10px; font-size:0.75rem;"><i class="fas fa-ticket-alt me-1"></i> ${firstTicket.jenis} (${firstTicket.status})</span>`;
                }
            } else {
                if (detailJamMasuk) detailJamMasuk.textContent = '--:--';
                if (detailJamPulang) detailJamPulang.textContent = '--:--';
                if (detailMetode) detailMetode.textContent = '-';
                if (detailKeterangan) detailKeterangan.textContent = 'Tidak ada tiket e-izin keluar sekolah pada tanggal ini.';

                if (detailStatusBadge) {
                    detailStatusBadge.innerHTML = `<span class="badge" style="background: rgba(100, 116, 139, 0.15); color: var(--text-muted); border: 1px solid var(--border-color); font-weight:700; padding:4px 10px; font-size:0.75rem;"><i class="fas fa-ticket-alt me-1"></i> Tidak Ada Tiket</span>`;
                }
            }
            if (detailIconBox) {
                detailIconBox.style.background = 'rgba(245, 158, 11, 0.18)';
                detailIconBox.style.color = '#d97706';
            }
            if (detailIcon) {
                detailIcon.className = 'fas fa-ticket-alt';
            }
        } else if (activeCategory === 'subSuratIzin') {
            if (info.has_surat_izin && Array.isArray(info.surat_izin_items) && info.surat_izin_items.length > 0) {
                const firstSurat = info.surat_izin_items[0];
                if (detailJamMasuk) detailJamMasuk.textContent = 'Izin';
                if (detailJamPulang) detailJamPulang.textContent = 'Resmi';
                if (detailMetode) detailMetode.textContent = 'Surat ' + firstSurat.jenis;
                if (detailKeterangan) detailKeterangan.textContent = firstSurat.alasan || '-';

                if (detailStatusBadge) {
                    detailStatusBadge.innerHTML = `<span class="badge" style="background: rgba(99, 102, 241, 0.2); color: #4338ca; border: 1px solid #6366f1; font-weight:700; padding:4px 10px; font-size:0.75rem;"><i class="fas fa-envelope-open-text me-1"></i> Surat ${firstSurat.jenis} (${firstSurat.status})</span>`;
                }
            } else {
                if (detailJamMasuk) detailJamMasuk.textContent = '--:--';
                if (detailJamPulang) detailJamPulang.textContent = '--:--';
                if (detailMetode) detailMetode.textContent = '-';
                if (detailKeterangan) detailKeterangan.textContent = 'Tidak ada surat permohonan izin/sakit pada tanggal ini.';

                if (detailStatusBadge) {
                    detailStatusBadge.innerHTML = `<span class="badge" style="background: rgba(100, 116, 139, 0.15); color: var(--text-muted); border: 1px solid var(--border-color); font-weight:700; padding:4px 10px; font-size:0.75rem;"><i class="fas fa-envelope-open-text me-1"></i> Tidak Ada Surat</span>`;
                }
            }
            if (detailIconBox) {
                detailIconBox.style.background = 'rgba(99, 102, 241, 0.18)';
                detailIconBox.style.color = '#6366f1';
            }
            if (detailIcon) {
                detailIcon.className = 'fas fa-envelope-open-text';
            }
        } else if (activeCategory === 'subKedisiplinan') {
            if (info.has_kedisiplinan && Array.isArray(info.kedisiplinan_items) && info.kedisiplinan_items.length > 0) {
                const firstKasus = info.kedisiplinan_items[0];
                if (detailJamMasuk) detailJamMasuk.textContent = '+' + firstKasus.poin;
                if (detailJamPulang) detailJamPulang.textContent = 'Poin';
                if (detailMetode) detailMetode.textContent = firstKasus.aturan || 'Tata Tertib';
                if (detailKeterangan) detailKeterangan.textContent = firstKasus.keterangan || '-';

                if (detailStatusBadge) {
                    detailStatusBadge.innerHTML = `<span class="badge" style="background: rgba(239, 68, 68, 0.2); color: #ef4444; border: 1px solid #ef4444; font-weight:700; padding:4px 10px; font-size:0.75rem;"><i class="fas fa-triangle-exclamation me-1"></i> Pelanggaran (+${firstKasus.poin} Poin)</span>`;
                }
            } else {
                if (detailJamMasuk) detailJamMasuk.textContent = '-';
                if (detailJamPulang) detailJamPulang.textContent = '-';
                if (detailMetode) detailMetode.textContent = 'Tertib';
                if (detailKeterangan) detailKeterangan.textContent = 'Tidak ada catatan pelanggaran tata tertib pada tanggal ini (Bersih).';

                if (detailStatusBadge) {
                    detailStatusBadge.innerHTML = `<span class="badge" style="background: rgba(16, 185, 129, 0.15); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.3); font-weight:700; padding:4px 10px; font-size:0.75rem;"><i class="fas fa-check me-1"></i> Tertib (0 Poin)</span>`;
                }
            }
            if (detailIconBox) {
                detailIconBox.style.background = 'rgba(239, 68, 68, 0.18)';
                detailIconBox.style.color = '#ef4444';
            }
            if (detailIcon) {
                detailIcon.className = 'fas fa-triangle-exclamation';
            }
        } else if (activeCategory === 'subMapel') {
            if (info.has_mapel && Array.isArray(info.mapel_items) && info.mapel_items.length > 0) {
                const firstMapel = info.mapel_items[0];
                if (detailJamMasuk) detailJamMasuk.textContent = 'Jam ' + firstMapel.jam_ke;
                if (detailJamPulang) detailJamPulang.textContent = firstMapel.status;
                if (detailMetode) detailMetode.textContent = firstMapel.mapel;
                if (detailKeterangan) detailKeterangan.textContent = 'Guru: ' + firstMapel.guru + (firstMapel.catatan && firstMapel.catatan !== '-' ? ' (' + firstMapel.catatan + ')' : '');

                if (detailStatusBadge) {
                    detailStatusBadge.innerHTML = `<span class="badge" style="background: rgba(16, 185, 129, 0.2); color: #047857; border: 1px solid #10b981; font-weight:700; padding:4px 10px; font-size:0.75rem;"><i class="fas fa-book-open me-1"></i> ${firstMapel.mapel} (${firstMapel.status})</span>`;
                }
            } else {
                if (detailJamMasuk) detailJamMasuk.textContent = '--:--';
                if (detailJamPulang) detailJamPulang.textContent = '--:--';
                if (detailMetode) detailMetode.textContent = '-';
                if (detailKeterangan) detailKeterangan.textContent = 'Tidak ada jadwal presensi mata pelajaran kelas pada tanggal ini.';

                if (detailStatusBadge) {
                    detailStatusBadge.innerHTML = `<span class="badge" style="background: rgba(100, 116, 139, 0.15); color: var(--text-muted); border: 1px solid var(--border-color); font-weight:700; padding:4px 10px; font-size:0.75rem;"><i class="fas fa-book-open me-1"></i> Tidak Ada KBM</span>`;
                }
            }
            if (detailIconBox) {
                detailIconBox.style.background = 'rgba(16, 185, 129, 0.18)';
                detailIconBox.style.color = '#10b981';
            }
            if (detailIcon) {
                detailIcon.className = 'fas fa-book-open';
            }
        } else {
            // Presensi harian RFID normal
            if (detailJamMasuk) detailJamMasuk.textContent = info.jam_masuk || '--:--';
            if (detailJamPulang) detailJamPulang.textContent = info.jam_pulang || '--:--';
            if (detailMetode) detailMetode.textContent = info.metode || '-';
            if (detailKeterangan) detailKeterangan.textContent = info.keterangan || '-';

            if (detailStatusBadge) {
                let labelText = info.label || '-';
                if (info.code === 'T' && info.sublabel && info.sublabel !== 'Terlambat') {
                    labelText += ` (${info.sublabel})`;
                }
                detailStatusBadge.innerHTML = `<span class="badge" style="background:${info.bg}; color:${info.color}; border:1px solid ${info.border}; font-weight:700; padding:4px 10px; font-size:0.75rem;"><i class="${info.icon} me-1"></i> ${labelText}</span>`;
            }

            if (detailIconBox) {
                detailIconBox.style.background = info.bg;
                detailIconBox.style.color = info.color;
            }
            if (detailIcon) {
                detailIcon.className = info.icon || 'fas fa-calendar-day';
            }
        }

        // Render transaksi terkait: e-Izin Keluar, Surat Izin, Kedisiplinan, Mapel
        if (detailExtraBox && detailExtraContent) {
            const hasExtras = (info.has_izin_keluar || info.has_surat_izin || info.has_kedisiplinan || info.has_mapel);
            let extraHtml = '';

            if (info.has_mapel && Array.isArray(info.mapel_items)) {
                info.mapel_items.forEach(item => {
                    extraHtml += `
                        <div style="background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 8px; padding: 6px 10px; font-size: 0.75rem; width: 100%;">
                            <div style="font-weight: 700; color: #059669; display: flex; align-items: center; gap: 4px;">
                                <i class="fas fa-book-open"></i> ${item.mapel} (Jam ke-${item.jam_ke})
                            </div>
                            <div style="color: var(--text-color); margin-top: 2px;">
                                Guru: ${item.guru} &bull; Status: <span class="badge" style="background: rgba(16, 185, 129, 0.2); color: #047857; padding: 1px 5px; font-size: 0.65rem;">${item.status}</span>
                            </div>
                            ${item.catatan && item.catatan !== '-' ? `<div style="color: var(--text-muted); font-size: 0.7rem; margin-top: 1px;">Catatan: ${item.catatan}</div>` : ''}
                        </div>
                    `;
                });
            }

            if (info.has_izin_keluar && Array.isArray(info.izin_keluar_items)) {
                info.izin_keluar_items.forEach(item => {
                    extraHtml += `
                        <div style="background: rgba(245, 158, 11, 0.1); border: 1px solid rgba(245, 158, 11, 0.35); border-radius: 8px; padding: 8px 10px; font-size: 0.75rem; width: 100%;">
                            <div style="font-weight: 700; color: #d97706; display: flex; align-items: center; gap: 4px; font-size: 0.8125rem;">
                                <i class="fas fa-ticket-alt"></i> ${item.jenis} (${item.nomor_tiket})
                            </div>
                            <div style="color: var(--text-color); margin-top: 4px;">
                                Waktu: <strong>${item.jam_keluar}</strong> s/d <strong>${item.jam_kembali}</strong> &bull; Status: <span class="badge" style="background: rgba(245, 158, 11, 0.2); color: #b45309; padding: 2px 6px; font-size: 0.7rem;">${item.status}</span>
                            </div>
                            <div style="color: var(--text-muted); font-size: 0.75rem; margin-top: 2px;">
                                Alasan: ${item.alasan}
                            </div>
                        </div>
                    `;
                });
            }

            if (info.has_surat_izin && Array.isArray(info.surat_izin_items)) {
                info.surat_izin_items.forEach(item => {
                    extraHtml += `
                        <div style="background: rgba(99, 102, 241, 0.1); border: 1px solid rgba(99, 102, 241, 0.3); border-radius: 8px; padding: 6px 10px; font-size: 0.75rem; width: 100%;">
                            <div style="font-weight: 700; color: #6366f1; display: flex; align-items: center; gap: 4px;">
                                <i class="fas fa-envelope-open-text"></i> Surat ${item.jenis}
                            </div>
                            <div style="color: var(--text-color); margin-top: 2px;">
                                Status: <span class="badge" style="background: rgba(99, 102, 241, 0.2); color: #4338ca; padding: 1px 5px; font-size: 0.65rem;">${item.status}</span>
                            </div>
                            <div style="color: var(--text-muted); font-size: 0.7rem; margin-top: 1px;">
                                Keterangan: ${item.alasan}
                            </div>
                        </div>
                    `;
                });
            }

            if (info.has_kedisiplinan && Array.isArray(info.kedisiplinan_items)) {
                info.kedisiplinan_items.forEach(item => {
                    extraHtml += `
                        <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); border-radius: 8px; padding: 6px 10px; font-size: 0.75rem; width: 100%;">
                            <div style="font-weight: 700; color: #ef4444; display: flex; align-items: center; gap: 4px;">
                                <i class="fas fa-triangle-exclamation"></i> ${item.aturan}
                            </div>
                            <div style="color: var(--text-color); margin-top: 2px;">
                                Poin: <strong style="color: #ef4444;">+${item.poin} Poin</strong> &bull; ${item.keterangan}
                            </div>
                        </div>
                    `;
                });
            }

            if (extraHtml) {
                detailExtraBox.style.display = 'block';
                detailExtraContent.innerHTML = extraHtml;
            } else if (activeCategory !== 'subHarian') {
                detailExtraBox.style.display = 'block';
                detailExtraContent.innerHTML = '<div style="font-size: 0.75rem; color: var(--text-muted); font-style: italic;">Tidak ada transaksi kategori ini pada tanggal yang dipilih. Klik tanggal bertanda khusus pada kalender.</div>';
            } else {
                detailExtraBox.style.display = 'none';
                detailExtraContent.innerHTML = '';
            }
        }
    }

    // 4. Bind day cells click and auto-select initial cell
    function bindCalendarCells() {
        const cells = document.querySelectorAll('.cal-day-cell');
        let defaultCell = null;

        cells.forEach(cell => {
            if (cell.classList.contains('cal-day-today')) {
                defaultCell = cell;
            }

            cell.addEventListener('click', function () {
                cells.forEach(c => c.classList.remove('is-selected'));
                this.classList.add('is-selected');

                try {
                    const info = JSON.parse(this.dataset.dayInfo || '{}');
                    updateDetail(info);
                } catch (e) {
                    console.error('Failed to parse day info', e);
                }
            });
        });

        if (!defaultCell && cells.length > 0) {
            for (let i = cells.length - 1; i >= 0; i--) {
                try {
                    const info = JSON.parse(cells[i].dataset.dayInfo || '{}');
                    if (info.has_log) {
                        defaultCell = cells[i];
                        break;
                    }
                } catch (e) {}
            }
            if (!defaultCell) defaultCell = cells[0];
        }

        if (defaultCell) {
            defaultCell.classList.add('is-selected');
            try {
                const info = JSON.parse(defaultCell.dataset.dayInfo || '{}');
                updateDetail(info);
            } catch (e) {}
        }
    }

    // 5. Asynchronous Month Loader (Kalender only, without full page reload)
    let isFetchingMonth = false;

    async function loadCalendarMonth(targetMonth) {
        if (!targetMonth || isFetchingMonth) return;
        isFetchingMonth = true;

        if (viewKalender) {
            viewKalender.style.opacity = '0.5';
            viewKalender.style.pointerEvents = 'none';
        }

        try {
            const url = new URL(window.location.href);
            url.searchParams.set('bulan', targetMonth);
            url.searchParams.set('ajax', '1');

            const response = await fetch(url.toString(), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error('Gagal memuat kalender: ' + response.statusText);
            }

            let data = await response.json();
            if (typeof data === 'string') {
                try { data = JSON.parse(data); } catch (e) {}
            }

            if (data && data.status === 'success') {
                if (viewKalender && data.calendarHtml) {
                    let calHtml = data.calendarHtml;
                    if (typeof calHtml === 'string') {
                        if (calHtml.startsWith('"') && calHtml.endsWith('"')) {
                            try { calHtml = JSON.parse(calHtml); } catch (e) {}
                        }
                        calHtml = calHtml
                            .replace(/<\\\//g, '</')
                            .replace(/\\"/g, '"')
                            .replace(/\\n/g, '\n')
                            .replace(/\\r/g, '')
                            .replace(/\\t/g, ' ');
                    }
                    viewKalender.innerHTML = calHtml;
                }
                const viewTabelHarian = document.getElementById('viewTabelHarian');
                if (viewTabelHarian && data.tableHtml) {
                    let tblHtml = data.tableHtml;
                    if (typeof tblHtml === 'string') {
                        if (tblHtml.startsWith('"') && tblHtml.endsWith('"')) {
                            try { tblHtml = JSON.parse(tblHtml); } catch (e) {}
                        }
                        tblHtml = tblHtml
                            .replace(/<\\\//g, '</')
                            .replace(/\\"/g, '"')
                            .replace(/\\n/g, '\n')
                            .replace(/\\r/g, '')
                            .replace(/\\t/g, ' ');
                    }
                    viewTabelHarian.innerHTML = tblHtml;
                }

                // Update Month Selector dropdown
                const selectBulan = document.getElementById('selectBulanKalender');
                if (selectBulan && data.selectedMonth) {
                    selectBulan.value = data.selectedMonth;
                }

                // Update Nav Buttons
                const btnPrev = document.getElementById('btnPrevMonth');
                const btnNext = document.getElementById('btnNextMonth');
                const btnThis = document.getElementById('btnThisMonth');

                if (btnPrev && data.prevMonth) {
                    btnPrev.setAttribute('data-month', data.prevMonth);
                    const prevUrl = new URL(window.location.href);
                    prevUrl.searchParams.set('bulan', data.prevMonth);
                    prevUrl.searchParams.delete('ajax');
                    btnPrev.setAttribute('href', prevUrl.toString());
                }

                if (btnNext && data.nextMonth) {
                    btnNext.setAttribute('data-month', data.nextMonth);
                    const nextUrl = new URL(window.location.href);
                    nextUrl.searchParams.set('bulan', data.nextMonth);
                    nextUrl.searchParams.delete('ajax');
                    btnNext.setAttribute('href', nextUrl.toString());
                }

                if (btnThis) {
                    const currentYm = new Date().toISOString().slice(0, 7);
                    btnThis.style.display = (data.selectedMonth !== currentYm) ? 'inline-flex' : 'none';
                }

                // Update Stat counters
                if (data.statsHarian) {
                    const s = data.statsHarian;
                    const elTotal = document.getElementById('statTotalSesi');
                    const elHadir = document.getElementById('statHadir');
                    const elTerlambat = document.getElementById('statTerlambat');
                    const elIzinSakit = document.getElementById('statIzinSakit');
                    const elAlpha = document.getElementById('statAlpha');

                    if (elTotal) elTotal.textContent = s.total_sesi + ' Hari';
                    if (elHadir) elHadir.textContent = s.hadir + ' Hari';
                    if (elTerlambat) elTerlambat.textContent = s.terlambat + ' Hari';
                    if (elIzinSakit) elIzinSakit.textContent = ((s.izin || 0) + (s.sakit || 0)) + ' Hari';
                    if (elAlpha) {
                        elAlpha.textContent = s.alpha + ' Hari';
                        elAlpha.style.color = (s.alpha > 0) ? '#ef4444' : 'var(--success, #10b981)';
                    }
                }

                // Update URL in browser bar without page reload
                const cleanUrl = new URL(window.location.href);
                cleanUrl.searchParams.set('bulan', data.selectedMonth);
                cleanUrl.searchParams.delete('ajax');
                window.history.pushState({ bulan: data.selectedMonth }, '', cleanUrl.toString());

                // Re-bind click event on calendar cells
                bindCalendarCells();
                syncView();
            }
        } catch (err) {
            console.error('Error loading calendar month:', err);
            // Fallback: regular full page redirect
            const fallbackUrl = new URL(window.location.href);
            fallbackUrl.searchParams.set('bulan', targetMonth);
            fallbackUrl.searchParams.delete('ajax');
            window.location.href = fallbackUrl.toString();
        } finally {
            isFetchingMonth = false;
            if (viewKalender) {
                viewKalender.style.opacity = '1';
                viewKalender.style.pointerEvents = 'auto';
            }
        }
    }

    // Bind Month Select Change
    const selectBulan = document.getElementById('selectBulanKalender');
    if (selectBulan) {
        selectBulan.addEventListener('change', function (e) {
            e.preventDefault();
            loadCalendarMonth(this.value);
        });
    }

    // Bind Month Prev/Next/Today buttons
    const btnPrev = document.getElementById('btnPrevMonth');
    if (btnPrev) {
        btnPrev.addEventListener('click', function (e) {
            e.preventDefault();
            const target = this.getAttribute('data-month');
            if (target) loadCalendarMonth(target);
        });
    }

    const btnNext = document.getElementById('btnNextMonth');
    if (btnNext) {
        btnNext.addEventListener('click', function (e) {
            e.preventDefault();
            const target = this.getAttribute('data-month');
            if (target) loadCalendarMonth(target);
        });
    }

    const btnThis = document.getElementById('btnThisMonth');
    if (btnThis) {
        btnThis.addEventListener('click', function (e) {
            e.preventDefault();
            const target = this.getAttribute('data-month');
            if (target) loadCalendarMonth(target);
        });
    }

    // Browser popstate back/forward support
    window.addEventListener('popstate', function (e) {
        const urlParams = new URLSearchParams(window.location.search);
        const bulan = urlParams.get('bulan');
        if (bulan) {
            loadCalendarMonth(bulan);
        }
    });

    // Initial binding
    bindCalendarCells();
    syncView();
});
