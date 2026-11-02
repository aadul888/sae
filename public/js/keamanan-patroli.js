document.addEventListener('DOMContentLoaded', function () {
    // --- Patroli Modal ---
    const modalPatroli = document.getElementById('modalPatroli');
    const formPatroli = document.getElementById('formPatroli');
    const modalPatroliTitle = document.getElementById('modalPatroliTitle');

    window.openModalPatroli = function () {
        if (!modalPatroli) return;
        formPatroli.reset();
        document.getElementById('patroliMethod').value = 'POST';
        formPatroli.action = formPatroli.dataset.storeUrl || '';
        modalPatroliTitle.textContent = 'Tambah Log Patroli';

        const now = new Date();
        document.getElementById('patroli_tanggal').value = now.toISOString().split('T')[0];
        const h = String(now.getHours()).padStart(2, '0');
        const m = String(now.getMinutes()).padStart(2, '0');
        document.getElementById('patroli_jam').value = `${h}:${m}`;

        modalPatroli.style.display = 'flex';
    };

    window.closeModalPatroli = function () {
        if (!modalPatroli) return;
        modalPatroli.style.display = 'none';
    };

    window.editPatroli = function (data) {
        if (!modalPatroli) return;
        formPatroli.reset();
        document.getElementById('patroliMethod').value = 'PUT';
        formPatroli.action = (formPatroli.dataset.updateUrl || '').replace(':id', data.id);
        modalPatroliTitle.textContent = 'Edit Log Patroli';

        document.getElementById('patroli_tanggal').value = data.tanggal || '';
        document.getElementById('patroli_jam').value = data.jam_patroli ? data.jam_patroli.substring(0, 5) : '';
        document.getElementById('patroli_rute').value = data.rute_zona || '';
        document.getElementById('patroli_kondisi').value = data.kondisi_lingkungan || 'aman_terkendali';
        document.getElementById('patroli_catatan').value = data.catatan_temuan || '';

        modalPatroli.style.display = 'flex';
    };

    if (modalPatroli) {
        modalPatroli.addEventListener('click', function (e) {
            if (e.target === modalPatroli) closeModalPatroli();
        });
    }

    // --- Insiden Modal ---
    const modalInsiden = document.getElementById('modalInsiden');
    const formInsiden = document.getElementById('formInsiden');
    const modalInsidenTitle = document.getElementById('modalInsidenTitle');

    window.openModalInsiden = function () {
        if (!modalInsiden) return;
        formInsiden.reset();
        document.getElementById('insidenMethod').value = 'POST';
        formInsiden.action = formInsiden.dataset.storeUrl || '';
        modalInsidenTitle.textContent = 'Laporkan Insiden Keamanan';

        const now = new Date();
        document.getElementById('insiden_tanggal').value = now.toISOString().split('T')[0];
        const h = String(now.getHours()).padStart(2, '0');
        const m = String(now.getMinutes()).padStart(2, '0');
        document.getElementById('insiden_jam').value = `${h}:${m}`;
        document.getElementById('insiden_urgensi').value = 'sedang';
        document.getElementById('insiden_status').value = 'dalam_penanganan';

        modalInsiden.style.display = 'flex';
    };

    window.closeModalInsiden = function () {
        if (!modalInsiden) return;
        modalInsiden.style.display = 'none';
    };

    window.editInsiden = function (data) {
        if (!modalInsiden) return;
        formInsiden.reset();
        document.getElementById('insidenMethod').value = 'PUT';
        formInsiden.action = (formInsiden.dataset.updateUrl || '').replace(':id', data.id);
        modalInsidenTitle.textContent = 'Edit Laporan Insiden';

        document.getElementById('insiden_tanggal').value = data.tanggal || '';
        document.getElementById('insiden_jam').value = data.jam_kejadian ? data.jam_kejadian.substring(0, 5) : '';
        document.getElementById('insiden_judul').value = data.judul_insiden || '';
        document.getElementById('insiden_lokasi').value = data.lokasi_kejadian || '';
        document.getElementById('insiden_urgensi').value = data.tingkat_urgensi || 'sedang';
        document.getElementById('insiden_pihak').value = data.pihak_terlibat || '';
        document.getElementById('insiden_kronologi').value = data.kronologi || '';
        document.getElementById('insiden_tindakan').value = data.tindakan_diambil || '';
        document.getElementById('insiden_status').value = data.status_penyelesaian || 'dalam_penanganan';

        modalInsiden.style.display = 'flex';
    };

    if (modalInsiden) {
        modalInsiden.addEventListener('click', function (e) {
            if (e.target === modalInsiden) closeModalInsiden();
        });

        // 1. Dropdown Pencarian Interaktif Ruang/Lokasi untuk Rute Patroli & Lokasi Insiden
        let listRuang = [];
        const ruangJsonEl = document.getElementById('daftarRuangJson');
        if (ruangJsonEl) {
            try {
                listRuang = JSON.parse(ruangJsonEl.textContent || '[]');
            } catch (err) {
                listRuang = [];
            }
        }

        function setupRuangDropdown(inputId, resultsId) {
            const input = document.getElementById(inputId);
            const results = document.getElementById(resultsId);
            if (!input || !results) return;

            input.addEventListener('input', function () {
                const q = this.value.trim().toLowerCase();
                if (q.length === 0) {
                    results.style.display = 'none';
                    return;
                }

                const filtered = listRuang.filter(r =>
                    (r.nama_ruang && r.nama_ruang.toLowerCase().includes(q)) ||
                    (r.kode_ruang && r.kode_ruang.toLowerCase().includes(q)) ||
                    (r.gedung && r.gedung.toLowerCase().includes(q))
                ).slice(0, 12);

                results.innerHTML = '';
                if (filtered.length === 0) {
                    results.innerHTML = '<div style="padding: 10px 14px; font-size: 0.8125rem; color: var(--text-muted);">Tidak ada ruang yang cocok (bisa tetap ketik manual).</div>';
                } else {
                    filtered.forEach(r => {
                        const opt = document.createElement('div');
                        opt.style.padding = '8px 14px';
                        opt.style.cursor = 'pointer';
                        opt.style.borderBottom = '1px solid var(--border-color)';
                        opt.style.fontSize = '0.8125rem';
                        opt.innerHTML = `<strong>${r.nama_ruang}</strong> <span style="color: var(--text-muted); font-size: 0.75rem;">(${r.gedung || '-'} - Lt. ${r.lantai || 1})</span>`;
                        opt.addEventListener('mouseover', () => opt.style.background = 'rgba(59,130,246,0.06)');
                        opt.addEventListener('mouseout', () => opt.style.background = 'transparent');
                        opt.addEventListener('click', () => {
                            input.value = `${r.nama_ruang} (${r.gedung || 'Gedung'})`;
                            results.style.display = 'none';
                        });
                        results.appendChild(opt);
                    });
                }
                results.style.display = 'block';
            });

            document.addEventListener('click', function (e) {
                if (results && !results.contains(e.target) && e.target !== input) {
                    results.style.display = 'none';
                }
            });
        }

        setupRuangDropdown('patroli_rute', 'patroli_rute_results');
        setupRuangDropdown('insiden_lokasi', 'insiden_lokasi_results');

        // 2. Dropdown Pencarian Interaktif Aturan Tata Tertib pada Insiden
        const inputTatib = document.getElementById('insiden_tatib_search');
        const hiddenTatib = document.getElementById('insiden_tata_tertib_id');
        const resultsTatib = document.getElementById('insiden_tatib_results');
        const badgeTatib = document.getElementById('insiden_tatib_selected_badge');
        const labelTatib = document.getElementById('insiden_tatib_selected_label');
        const btnClearTatib = document.getElementById('insiden_tatib_btn_clear');

        let debounceTatib;
        if (inputTatib) {
            inputTatib.addEventListener('input', function () {
                clearTimeout(debounceTatib);
                const query = this.value.trim();
                if (query.length < 2) {
                    resultsTatib.style.display = 'none';
                    return;
                }

                debounceTatib = setTimeout(() => {
                    fetch(`/dashboard/keamanan/pelanggaran/search-tatib?q=${encodeURIComponent(query)}`)
                        .then(res => res.json())
                        .then(data => {
                            resultsTatib.innerHTML = '';
                            if (!data.length) {
                                resultsTatib.innerHTML = '<div style="padding: 10px 14px; font-size: 0.8125rem; color: var(--text-muted);">Aturan tidak ditemukan.</div>';
                            } else {
                                data.forEach(item => {
                                    const opt = document.createElement('div');
                                    opt.style.padding = '8px 12px';
                                    opt.style.cursor = 'pointer';
                                    opt.style.borderBottom = '1px solid var(--border-color)';
                                    opt.style.fontSize = '0.8125rem';
                                    opt.innerHTML = `
                                        <div style="display: flex; justify-content: space-between; align-items: center;">
                                            <div>
                                                <strong style="color: var(--primary);">[${item.kode}]</strong>
                                                <span style="font-weight: 600; color: var(--text-color); margin-left: 4px;">${item.nama_aturan}</span>
                                            </div>
                                            <span style="font-weight: 800; color: var(--danger); font-size: 0.75rem;">+${item.bobot_poin} Poin</span>
                                        </div>
                                    `;
                                    opt.addEventListener('mouseover', () => opt.style.background = 'rgba(59,130,246,0.06)');
                                    opt.addEventListener('mouseout', () => opt.style.background = 'transparent');
                                    opt.addEventListener('click', () => {
                                        hiddenTatib.value = item.id;
                                        labelTatib.textContent = `[${item.kode}] ${item.nama_aturan} (+${item.bobot_poin} Poin)`;
                                        badgeTatib.style.display = 'flex';
                                        inputTatib.style.display = 'none';
                                        resultsTatib.style.display = 'none';
                                    });
                                    resultsTatib.appendChild(opt);
                                });
                            }
                            resultsTatib.style.display = 'block';
                        })
                        .catch(() => {
                            resultsTatib.style.display = 'none';
                        });
                }, 250);
            });
        }

        if (btnClearTatib) {
            btnClearTatib.addEventListener('click', function () {
                hiddenTatib.value = '';
                badgeTatib.style.display = 'none';
                inputTatib.style.display = 'block';
                inputTatib.value = '';
                inputTatib.focus();
            });
        }

        document.addEventListener('click', function (e) {
            if (resultsTatib && !resultsTatib.contains(e.target) && e.target !== inputTatib) {
                resultsTatib.style.display = 'none';
            }
        });
    }

    // Delete confirmation with SweetAlert2
    document.querySelectorAll('form[data-confirm="delete"]').forEach(function (delForm) {
        delForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const name = delForm.dataset.name || 'data ini';
            window.SAE.confirm("Apakah Anda yakin ingin menghapus \"${name}\"?", "Konfirmasi Hapus", 'warning', "Ya, Hapus!", "Batal").then((result) => {
                if (result.isConfirmed) {
                    delForm.submit();
                }
            });
        });
    });
});
