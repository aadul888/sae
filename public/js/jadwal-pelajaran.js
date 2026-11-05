/**
 * Jadwal Pelajaran & Mengajar JavaScript Module
 * Sistem Aplikasi Edukasi (SAE)
 */

document.addEventListener('DOMContentLoaded', () => {
    const btnTable = document.getElementById('btnJadwalViewTable');
    const btnGrid = document.getElementById('btnJadwalViewGrid');
    const tableContainer = document.getElementById('tableDataContainerJadwal');
    const gridContainer = document.getElementById('gridDataContainerJadwal');
    const searchInput = document.getElementById('searchJadwalInput');
    const clearBtn = document.getElementById('clearSearchJadwalBtn');

    // 1. Tampilan Switcher (Tabel vs Kartu Grid) dengan memori LocalStorage
    const setViewMode = (mode) => {
        if (!tableContainer || !gridContainer) return;

        if (mode === 'grid') {
            tableContainer.style.display = 'none';
            gridContainer.style.display = 'block';
            if (btnGrid) {
                btnGrid.classList.remove('btn-outline');
                btnGrid.classList.add('btn-primary');
            }
            if (btnTable) {
                btnTable.classList.remove('btn-primary');
                btnTable.classList.add('btn-outline');
            }
        } else {
            tableContainer.style.display = 'block';
            gridContainer.style.display = 'none';
            if (btnTable) {
                btnTable.classList.remove('btn-outline');
                btnTable.classList.add('btn-primary');
            }
            if (btnGrid) {
                btnGrid.classList.remove('btn-primary');
                btnGrid.classList.add('btn-outline');
            }
        }
        try {
            localStorage.setItem('sae_jadwal_view_mode', mode);
        } catch (e) {}
    };

    const savedMode = localStorage.getItem('sae_jadwal_view_mode') || 'table';
    setViewMode(savedMode);

    if (btnTable) {
        btnTable.addEventListener('click', () => setViewMode('table'));
    }
    if (btnGrid) {
        btnGrid.addEventListener('click', () => setViewMode('grid'));
    }

    // 2. Client-side Realtime Live Search Filter (Instan di Tabel & Kartu)
    if (searchInput) {
        searchInput.addEventListener('input', () => {
            const query = searchInput.value.toLowerCase().trim();
            if (clearBtn) {
                if (query.length > 0) {
                    clearBtn.classList.add('visible');
                } else {
                    clearBtn.classList.remove('visible');
                }
            }

            // Filter baris di tabel
            document.querySelectorAll('.jadwal-row-item').forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(query) ? '' : 'none';
            });

            // Filter kartu di grid
            document.querySelectorAll('.jadwal-item-card').forEach(card => {
                const text = card.textContent.toLowerCase();
                card.style.display = text.includes(query) ? '' : 'none';
            });
        });
    }

    if (clearBtn) {
        clearBtn.addEventListener('click', () => {
            if (searchInput) {
                searchInput.value = '';
                searchInput.dispatchEvent(new Event('input'));
                searchInput.focus();
            }
        });
    }
});

/**
 * Modul Input Manual Jadwal Pelajaran (modal CRUD) — memakai endpoint Jadwal KBM Manual.
 */
document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('modalJadwalManual');
    if (!modal) return;

    const form = document.getElementById('mjForm');
    const title = document.getElementById('mjTitle');
    const inputId = document.getElementById('mjId');
    const selectRombel = document.getElementById('mjRombel');
    const selectPembelajaran = document.getElementById('mjPembelajaran');
    const selectHari = document.getElementById('mjHari');
    const selectJamMulai = document.getElementById('mjJamKeMulai');
    const selectJamSelesai = document.getElementById('mjJamKeSelesai');
    const inputKeterangan = document.getElementById('mjKeterangan');
    const chkOverwrite = document.getElementById('mjOverwrite');
    const alertBox = document.getElementById('mjAlert');
    const btnSimpan = document.getElementById('mjSimpan');

    const routeStore = modal.dataset.routeStore;
    const routeUpdateBase = modal.dataset.routeUpdate;
    const routeDeleteBase = modal.dataset.routeDelete;
    const routePembelajaran = modal.dataset.routePembelajaran;

    const csrf = () =>
        document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ||
        form?.querySelector('input[name="_token"]')?.value ||
        '';

    const showAlert = (type, message) => {
        if (!alertBox) return;
        const colors = {
            success: ['rgba(16,185,129,0.1)', 'rgba(16,185,129,0.3)', '#10b981'],
            warning: ['rgba(245,158,11,0.12)', 'rgba(245,158,11,0.4)', '#d97706'],
            danger: ['rgba(239,68,68,0.12)', 'rgba(239,68,68,0.35)', '#ef4444'],
        }[type] || ['rgba(99,102,241,0.08)', 'rgba(99,102,241,0.3)', 'var(--primary)'];
        alertBox.style.display = 'block';
        alertBox.style.background = colors[0];
        alertBox.style.border = `1px solid ${colors[1]}`;
        alertBox.style.color = colors[2];
        alertBox.innerHTML = message;
    };

    const hideAlert = () => {
        if (alertBox) {
            alertBox.style.display = 'none';
            alertBox.innerHTML = '';
        }
    };

    const ensureRombelOption = (id, name) => {
        if (!id || !selectRombel) return;
        const exists = Array.from(selectRombel.options).some((o) => String(o.value) === String(id));
        if (!exists) {
            const opt = document.createElement('option');
            opt.value = id;
            opt.textContent = name || id;
            selectRombel.appendChild(opt);
        }
    };

    const loadPembelajaran = (rombelId, selectedId = '') => {
        if (!selectPembelajaran) return;
        if (!rombelId) {
            selectPembelajaran.innerHTML = '<option value="">-- Pilih Kelas Terlebih Dahulu --</option>';
            return;
        }
        selectPembelajaran.innerHTML = '<option value="">Memuat mata pelajaran...</option>';
        fetch(`${routePembelajaran}?rombel_id=${encodeURIComponent(rombelId)}`, {
            headers: { Accept: 'application/json' },
        })
            .then((res) => res.json())
            .then((res) => {
                const items = res.pembelajaran || [];
                if (!items.length) {
                    selectPembelajaran.innerHTML = '<option value="">Tidak ada mata pelajaran untuk kelas ini</option>';
                    return;
                }
                selectPembelajaran.innerHTML = '<option value="">-- Pilih Mata Pelajaran --</option>';
                items.forEach((p) => {
                    const opt = document.createElement('option');
                    opt.value = p.pembelajaran_id;
                    opt.textContent = `${p.nama_mata_pelajaran} — ${p.nama_guru || 'Tanpa Guru'}${p.sisa_jp !== null && p.sisa_jp !== undefined ? ` (Sisa: ${p.sisa_jp} JP)` : ''}`;
                    if (selectedId && String(p.pembelajaran_id) === String(selectedId)) opt.selected = true;
                    selectPembelajaran.appendChild(opt);
                });
            })
            .catch(() => {
                selectPembelajaran.innerHTML = '<option value="">Gagal memuat mata pelajaran</option>';
            });
    };

    const bukaModal = (isEdit = false, data = {}) => {
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        hideAlert();
        if (chkOverwrite) chkOverwrite.checked = false;
        if (inputId) inputId.value = isEdit ? data.id || '' : '';
        if (title) {
            title.innerHTML = isEdit
                ? '<i class="fas fa-pen text-primary me-2"></i> Edit Slot Jadwal'
                : '<i class="fas fa-calendar-plus text-primary me-2"></i> Jadwalkan Pelajaran';
        }
        if (btnSimpan) btnSimpan.innerHTML = isEdit ? '<i class="fas fa-save me-1"></i> Perbarui Slot' : '<i class="fas fa-save me-1"></i> Simpan Slot';

        if (isEdit) ensureRombelOption(data.rombelId, data.rombelName);
        if (selectRombel) selectRombel.value = data.rombelId || (selectRombel.options[1]?.value || '');
        if (selectHari) selectHari.value = data.hari || 'Senin';
        if (selectJamMulai) selectJamMulai.value = data.jamKeMulai || '1';
        if (selectJamSelesai) selectJamSelesai.value = data.jamKeSelesai || data.jamKeMulai || '1';
        if (inputKeterangan) inputKeterangan.value = data.keterangan || '';
        loadPembelajaran(selectRombel?.value, data.pembelajaranId);
    };

    const tutupModal = () => {
        modal.style.display = 'none';
        document.body.style.overflow = '';
        if (form) form.reset();
        if (inputId) inputId.value = '';
        hideAlert();
    };

    if (selectRombel) {
        selectRombel.addEventListener('change', () => {
            if (selectRombel.value) loadPembelajaran(selectRombel.value);
        });
    }
    if (selectJamMulai) {
        selectJamMulai.addEventListener('change', () => {
            if (selectJamSelesai && parseInt(selectJamSelesai.value) < parseInt(selectJamMulai.value)) {
                selectJamSelesai.value = selectJamMulai.value;
            }
        });
    }

    const btnTambah = document.getElementById('btnTambahJadwalManual');
    if (btnTambah) btnTambah.addEventListener('click', () => bukaModal(false));

    document.getElementById('mjTutup')?.addEventListener('click', tutupModal);
    document.getElementById('mjBatal')?.addEventListener('click', tutupModal);
    modal.addEventListener('click', (e) => {
        if (e.target === modal) tutupModal();
    });

    document.querySelectorAll('.btn-jadwal-edit').forEach((btn) => {
        btn.addEventListener('click', () => {
            bukaModal(true, {
                id: btn.dataset.id,
                pembelajaranId: btn.dataset.pembelajaranId,
                rombelId: btn.dataset.rombelId,
                rombelName: btn.dataset.rombelName,
                hari: btn.dataset.hari,
                jamKeMulai: btn.dataset.jamKeMulai,
                jamKeSelesai: btn.dataset.jamKeSelesai,
                ruangan: btn.dataset.ruangan,
                keterangan: btn.dataset.keterangan,
            });
        });
    });

    document.querySelectorAll('.btn-jadwal-hapus').forEach((btn) => {
        btn.addEventListener('click', () => {
            const nama = btn.dataset.namaMapel || 'pelajaran ini';
            if (!window.confirm(`Hapus jadwal "${nama}" (${btn.dataset.hari}, Jam ke-${btn.dataset.jamKe})?`)) return;
            fetch(`${routeDeleteBase}/${encodeURIComponent(btn.dataset.id)}`, {
                method: 'DELETE',
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf() },
            })
                .then((res) => res.json())
                .then((res) => {
                    if (res.success) {
                        window.location.reload();
                    } else {
                        window.alert(res.message || 'Gagal menghapus jadwal.');
                    }
                })
                .catch(() => window.alert('Gagal menghapus jadwal. Coba lagi.'));
        });
    });

    if (form) {
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            const id = inputId?.value;
            const url = id ? `${routeUpdateBase}/${encodeURIComponent(id)}` : routeStore;
            const data = new FormData(form);
            if (id && !data.has('_method')) data.append('_method', 'PUT');

            fetch(url, {
                method: 'POST',
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf() },
                body: data,
            })
                .then((res) => res.json().then((body) => ({ ok: res.ok, body })))
                .then(({ ok, body }) => {
                    if (body.success) {
                        window.alert(body.message || 'Jadwal berhasil disimpan.');
                        window.location.reload();
                        return;
                    }
                    if (body.can_overwrite) {
                        if (chkOverwrite) chkOverwrite.checked = true;
                        showAlert('warning', `<strong>Slot waktu sudah terisi.</strong><br>${body.message || ''}<br>Centang "Ganti / Timpa" lalu simpan lagi untuk mengganti jadwal tersebut.`);
                        return;
                    }
                    showAlert('danger', `<strong>Gagal menyimpan:</strong><br>${body.message || 'Terjadi kesalahan.'}`);
                })
                .catch(() => showAlert('danger', 'Gagal menyimpan jadwal. Periksa koneksi dan coba lagi.'));
        });
    }
});
