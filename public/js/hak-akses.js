/**
 * Hak Akses & Peran JavaScript Module
 * Sistem Aplikasi Edukasi (SAE)
 */

document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('hakAksesContainer');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    const activeRole = container?.dataset.activeRole || 'admin';
    const roleName = container?.dataset.roleName || activeRole;
    const toggleUrl = container?.dataset.toggleUrl || '/dashboard/hak-akses/toggle';
    const syncUrl = container?.dataset.syncUrl || '/dashboard/hak-akses/sync';
    const resetUrl = container?.dataset.resetUrl || '/dashboard/hak-akses/reset';
    const addModuleUrl = container?.dataset.addModuleUrl || '/dashboard/hak-akses/add-module';
    const removeModuleUrl = container?.dataset.removeModuleUrl || '/dashboard/hak-akses/remove-module';
    const storeDutyUrl = container?.dataset.storeDutyUrl || '/dashboard/hak-akses/tugas-tambahan/store';
    const destroyDutyBaseUrl = container?.dataset.destroyDutyBaseUrl || '/dashboard/hak-akses/tugas-tambahan';
    const syncWaliUrl = container?.dataset.syncWaliUrl || '/dashboard/hak-akses/tugas-tambahan/sync-wali';

    // Unified Toast
    const showToast = (title, icon = 'success') => {
        const type = (icon === 'error' || icon === 'danger') ? 'danger' : (icon === 'warning' ? 'warning' : 'success');
        if (window.SAE && typeof window.SAE.toast === 'function') {
            window.SAE.toast(title, type);
        } else if (typeof Swal !== 'undefined') {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: type === 'danger' ? 'error' : type,
                title: title,
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            });
        }
    };

    // 1. Toggle 4 Aksi (Tambah, Lihat, Ubah, Hapus) di Kolom Aksi
    document.querySelectorAll('.crud-toggle').forEach(toggle => {
        toggle.addEventListener('change', async (e) => {
            const target = e.target;
            const role = target.dataset.role;
            const key = target.dataset.key;
            const action = target.dataset.action;
            const color = target.dataset.color || '#10b981';
            const isAllowed = target.checked;
            const slider = target.nextElementSibling;
            const row = target.closest('tr');

            slider.style.backgroundColor = isAllowed ? color : '#64748b';

            try {
                const res = await fetch(toggleUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({
                        role: role,
                        permission_key: key,
                        action: action,
                        is_allowed: isAllowed
                    })
                });

                const data = await res.json();
                if (data.status === 'success') {
                    showToast(data.message || 'Izin berhasil diperbarui.');

                    if (data.data) {
                        ['create', 'read', 'update', 'delete'].forEach(act => {
                            const input = row.querySelector(`.crud-toggle[data-action="${act}"]`);
                            if (input && typeof data.data['can_' + act] !== 'undefined') {
                                input.checked = data.data['can_' + act];
                                const s = input.nextElementSibling;
                                if (s) {
                                    s.style.backgroundColor = input.checked ? (input.dataset.color || '#10b981') : '#64748b';
                                }
                            }
                        });
                    }
                } else {
                    throw new Error(data.message || 'Gagal mengubah izin ' + action);
                }
            } catch (err) {
                target.checked = !isAllowed;
                slider.style.backgroundColor = !isAllowed ? color : '#64748b';
                showToast(err.message || 'Gagal mengubah izin ' + action, 'danger');
            }
        });
    });

    // 2. Client-side Search, Group Filter, Sorting & Pagination untuk Datatable Modul
    const liveSearchInput = document.getElementById('liveSearch');
    const clearSearchBtn = document.getElementById('clearSearch');
    const filterGroupSelect = document.getElementById('filterGroup');
    const perPageSelect = document.getElementById('perPageSelect');
    let allRows = Array.from(document.querySelectorAll('.module-row'));
    const noResultRow = document.getElementById('noSearchResultRow');
    const paginationWrap = document.getElementById('tablePaginationWrap');
    const totalBadge = document.getElementById('totalBadge');

    let currentPage = 1;
    let sortCol = 'no';
    let sortDir = 'asc';

    const sortRows = (rows) => {
        return rows.sort((a, b) => {
            let valA = '';
            let valB = '';
            if (sortCol === 'no') {
                valA = parseInt(a.querySelector('.row-number')?.textContent || '0', 10);
                valB = parseInt(b.querySelector('.row-number')?.textContent || '0', 10);
                return sortDir === 'asc' ? valA - valB : valB - valA;
            } else if (sortCol === 'modul') {
                valA = a.dataset.modul || '';
                valB = b.dataset.modul || '';
            } else if (sortCol === 'kelompok') {
                valA = a.dataset.kelompok || '';
                valB = b.dataset.kelompok || '';
            }
            const cmp = valA.localeCompare(valB);
            return sortDir === 'asc' ? cmp : -cmp;
        });
    };

    const renderTable = () => {
        if (!document.getElementById('tableBody')) return;

        const query = (liveSearchInput?.value || '').trim().toLowerCase();
        const groupFilter = (filterGroupSelect?.value || '').trim();
        const perPageVal = perPageSelect?.value || '25';
        const perPage = perPageVal === 'all' ? Infinity : parseInt(perPageVal, 10);

        let matchedRows = allRows.filter(row => {
            const name = row.dataset.name || '';
            const group = row.dataset.group || '';
            const matchQuery = !query || name.includes(query);
            const matchGroup = !groupFilter || group === groupFilter;
            return matchQuery && matchGroup;
        });

        matchedRows = sortRows(matchedRows);

        const totalMatched = matchedRows.length;
        if (totalBadge) {
            totalBadge.textContent = `Total: ${totalMatched} Modul`;
        }

        if (totalMatched === 0) {
            allRows.forEach(r => r.style.display = 'none');
            if (noResultRow) noResultRow.style.display = '';
            if (paginationWrap) paginationWrap.innerHTML = '';
            return;
        }

        if (noResultRow) noResultRow.style.display = 'none';

        const totalPages = Math.ceil(totalMatched / perPage);
        if (currentPage > totalPages) currentPage = Math.max(1, totalPages);

        const startIdx = (currentPage - 1) * perPage;
        const endIdx = startIdx + perPage;

        allRows.forEach(r => r.style.display = 'none');

        const tbody = document.getElementById('tableBody');
        matchedRows.forEach((row, i) => {
            if (i >= startIdx && i < endIdx) {
                row.style.display = '';
                const noCell = row.querySelector('.row-number');
                if (noCell) noCell.textContent = i + 1;
                if (tbody) tbody.appendChild(row);
            }
        });

        if (totalPages <= 1) {
            if (paginationWrap) paginationWrap.innerHTML = '';
            return;
        }

        let pagHtml = '';
        pagHtml += `<button type="button" class="page-btn ${currentPage === 1 ? 'disabled' : ''}" data-page="${currentPage - 1}" ${currentPage === 1 ? 'disabled' : ''}><i class="fas fa-chevron-left"></i></button>`;

        for (let p = 1; p <= totalPages; p++) {
            if (p === 1 || p === totalPages || (p >= currentPage - 2 && p <= currentPage + 2)) {
                pagHtml += `<button type="button" class="page-btn ${p === currentPage ? 'current' : ''}" data-page="${p}">${p}</button>`;
            } else if (p === currentPage - 3 || p === currentPage + 3) {
                pagHtml += `<span class="page-info">&hellip;</span>`;
            }
        }

        pagHtml += `<button type="button" class="page-btn ${currentPage === totalPages ? 'disabled' : ''}" data-page="${currentPage + 1}" ${currentPage === totalPages ? 'disabled' : ''}><i class="fas fa-chevron-right"></i></button>`;

        if (paginationWrap) {
            paginationWrap.innerHTML = pagHtml;
            paginationWrap.querySelectorAll('.page-btn[data-page]').forEach(btn => {
                btn.addEventListener('click', () => {
                    const targetP = parseInt(btn.dataset.page, 10);
                    if (targetP >= 1 && targetP <= totalPages && targetP !== currentPage) {
                        currentPage = targetP;
                        renderTable();
                    }
                });
            });
        }
    };

    document.querySelectorAll('.module-th[data-col]').forEach(th => {
        th.addEventListener('click', () => {
            const col = th.dataset.col;
            if (sortCol === col) {
                sortDir = sortDir === 'asc' ? 'desc' : 'asc';
            } else {
                sortCol = col;
                sortDir = 'asc';
            }

            document.querySelectorAll('.module-th').forEach(t => {
                t.classList.remove('sorted');
                const icon = t.querySelector('.sort-icon');
                if (icon) icon.innerHTML = '&#9650;&#9660;';
            });

            th.classList.add('sorted');
            const curIcon = th.querySelector('.sort-icon');
            if (curIcon) {
                curIcon.innerHTML = sortDir === 'asc' ? '&#9650;' : '&#9660;';
            }

            renderTable();
        });
    });

    if (liveSearchInput) {
        liveSearchInput.addEventListener('input', () => {
            if (clearSearchBtn) {
                clearSearchBtn.classList.toggle('visible', !!liveSearchInput.value);
            }
            currentPage = 1;
            renderTable();
        });
    }

    if (clearSearchBtn) {
        clearSearchBtn.addEventListener('click', () => {
            liveSearchInput.value = '';
            clearSearchBtn.classList.remove('visible');
            currentPage = 1;
            renderTable();
        });
    }

    if (filterGroupSelect) {
        filterGroupSelect.addEventListener('change', () => {
            currentPage = 1;
            renderTable();
        });
    }

    if (perPageSelect) {
        perPageSelect.addEventListener('change', () => {
            currentPage = 1;
            renderTable();
        });
    }

    renderTable();

    // 2b. Handler Tab Tugas Tambahan (Search, Sorting, Pagination, Modal, Sinkronisasi Wali Kelas, Hapus)
    const liveSearchDuty = document.getElementById('liveSearchDuty');
    const clearSearchDuty = document.getElementById('clearSearchDuty');
    const perPageDuty = document.getElementById('perPageDuty');
    const dutyTableBody = document.getElementById('dutyTableBody');
    const dutyPaginationWrap = document.getElementById('dutyPaginationWrap');
    const totalDutyBadge = document.getElementById('totalDutyBadge');
    const noDutyResultRow = document.getElementById('noDutyResultRow');
    let allDutyRows = Array.from(document.querySelectorAll('.duty-row'));

    let currentDutyPage = 1;
    let sortDutyCol = 'no';
    let sortDutyDir = 'asc';

    const sortDutyRows = (rows) => {
        return rows.sort((a, b) => {
            let valA = '';
            let valB = '';
            if (sortDutyCol === 'no') {
                valA = parseInt(a.querySelector('.duty-row-number')?.textContent || '0', 10);
                valB = parseInt(b.querySelector('.duty-row-number')?.textContent || '0', 10);
                return sortDutyDir === 'asc' ? valA - valB : valB - valA;
            } else if (sortDutyCol === 'nama') {
                valA = a.dataset.nama || '';
                valB = b.dataset.nama || '';
            } else if (sortDutyCol === 'tugas') {
                valA = a.dataset.tugas || '';
                valB = b.dataset.tugas || '';
            } else if (sortDutyCol === 'bidang') {
                valA = a.dataset.bidang || '';
                valB = b.dataset.bidang || '';
            } else if (sortDutyCol === 'jam') {
                valA = parseFloat(a.dataset.jam || '0');
                valB = parseFloat(b.dataset.jam || '0');
                return sortDutyDir === 'asc' ? valA - valB : valB - valA;
            }
            const cmp = valA.localeCompare(valB);
            return sortDutyDir === 'asc' ? cmp : -cmp;
        });
    };

    const renderDutyTable = () => {
        if (!dutyTableBody) return;

        const q = (liveSearchDuty?.value || '').trim().toLowerCase();
        const perPageVal = perPageDuty?.value || '25';
        const perPage = perPageVal === 'all' ? Infinity : parseInt(perPageVal, 10);

        let matched = allDutyRows.filter(r => {
            const search = r.dataset.search || '';
            return !q || search.includes(q);
        });

        matched = sortDutyRows(matched);

        const totalMatched = matched.length;
        if (totalDutyBadge) {
            totalDutyBadge.textContent = `Total: ${totalMatched} Penugasan`;
        }

        if (totalMatched === 0) {
            allDutyRows.forEach(r => r.style.display = 'none');
            if (noDutyResultRow) noDutyResultRow.style.display = '';
            if (dutyPaginationWrap) dutyPaginationWrap.innerHTML = '';
            return;
        }

        if (noDutyResultRow) noDutyResultRow.style.display = 'none';

        const totalPages = Math.ceil(totalMatched / perPage);
        if (currentDutyPage > totalPages) currentDutyPage = Math.max(1, totalPages);

        const startIdx = (currentDutyPage - 1) * perPage;
        const endIdx = startIdx + perPage;

        allDutyRows.forEach(r => r.style.display = 'none');

        matched.forEach((row, i) => {
            if (i >= startIdx && i < endIdx) {
                row.style.display = '';
                const noCell = row.querySelector('.duty-row-number');
                if (noCell) noCell.textContent = i + 1;
                dutyTableBody.appendChild(row);
            }
        });

        if (totalPages <= 1) {
            if (dutyPaginationWrap) dutyPaginationWrap.innerHTML = '';
            return;
        }

        let pagHtml = '';
        pagHtml += `<button type="button" class="page-btn ${currentDutyPage === 1 ? 'disabled' : ''}" data-page="${currentDutyPage - 1}" ${currentDutyPage === 1 ? 'disabled' : ''}><i class="fas fa-chevron-left"></i></button>`;

        for (let p = 1; p <= totalPages; p++) {
            if (p === 1 || p === totalPages || (p >= currentDutyPage - 2 && p <= currentDutyPage + 2)) {
                pagHtml += `<button type="button" class="page-btn ${p === currentDutyPage ? 'current' : ''}" data-page="${p}">${p}</button>`;
            } else if (p === currentDutyPage - 3 || p === currentDutyPage + 3) {
                pagHtml += `<span class="page-info">&hellip;</span>`;
            }
        }

        pagHtml += `<button type="button" class="page-btn ${currentDutyPage === totalPages ? 'disabled' : ''}" data-page="${currentDutyPage + 1}" ${currentDutyPage === totalPages ? 'disabled' : ''}><i class="fas fa-chevron-right"></i></button>`;

        if (dutyPaginationWrap) {
            dutyPaginationWrap.innerHTML = pagHtml;
            dutyPaginationWrap.querySelectorAll('.page-btn[data-page]').forEach(btn => {
                btn.addEventListener('click', () => {
                    const targetP = parseInt(btn.dataset.page, 10);
                    if (targetP >= 1 && targetP <= totalPages && targetP !== currentDutyPage) {
                        currentDutyPage = targetP;
                        renderDutyTable();
                    }
                });
            });
        }
    };

    document.querySelectorAll('.duty-th[data-col]').forEach(th => {
        th.addEventListener('click', () => {
            const col = th.dataset.col;
            if (sortDutyCol === col) {
                sortDutyDir = sortDutyDir === 'asc' ? 'desc' : 'asc';
            } else {
                sortDutyCol = col;
                sortDutyDir = 'asc';
            }

            document.querySelectorAll('.duty-th').forEach(t => {
                t.classList.remove('sorted');
                const icon = t.querySelector('.sort-icon');
                if (icon) icon.innerHTML = '&#9650;&#9660;';
            });

            th.classList.add('sorted');
            const curIcon = th.querySelector('.sort-icon');
            if (curIcon) {
                curIcon.innerHTML = sortDutyDir === 'asc' ? '&#9650;' : '&#9660;';
            }

            renderDutyTable();
        });
    });

    if (liveSearchDuty) {
        liveSearchDuty.addEventListener('input', () => {
            const q = liveSearchDuty.value.trim().toLowerCase();
            if (clearSearchDuty) clearSearchDuty.classList.toggle('visible', !!q);
            currentDutyPage = 1;
            renderDutyTable();
        });
    }

    if (clearSearchDuty) {
        clearSearchDuty.addEventListener('click', () => {
            liveSearchDuty.value = '';
            clearSearchDuty.classList.remove('visible');
            currentDutyPage = 1;
            renderDutyTable();
        });
    }

    if (perPageDuty) {
        perPageDuty.addEventListener('change', () => {
            currentDutyPage = 1;
            renderDutyTable();
        });
    }

    renderDutyTable();

    // 2c. Filter Katalog Master Tugas Tambahan
    const filterDutyCatalog = document.getElementById('filterDutyCatalog');
    const dutyCatalogRows = Array.from(document.querySelectorAll('.duty-catalog-row'));
    if (filterDutyCatalog && dutyCatalogRows.length > 0) {
        filterDutyCatalog.addEventListener('change', () => {
            const selected = filterDutyCatalog.value.toLowerCase().trim();
            dutyCatalogRows.forEach(row => {
                const group = (row.dataset.group || '').toLowerCase();
                const match = !selected || group === selected;
                row.style.display = match ? '' : 'none';
            });
        });
    }

    // Modal Tambah Penugasan
    const dutyModal = document.getElementById('dutyModal');
    const btnOpenAssignModal = document.getElementById('btnOpenAssignModal');
    const btnCloseDutyModal = document.getElementById('btnCloseDutyModal');
    const btnCancelDutyModal = document.getElementById('btnCancelDutyModal');
    const dutyTugasSelect = document.getElementById('dutyTugasSelect');
    const dutyRombelWrap = document.getElementById('dutyRombelWrap');
    const formAddDuty = document.getElementById('formAddDuty');

    if (btnOpenAssignModal && dutyModal) {
        btnOpenAssignModal.addEventListener('click', () => {
            dutyModal.style.display = 'flex';
        });
    }

    const closeDutyModal = () => {
        if (dutyModal) {
            dutyModal.style.display = 'none';
            if (formAddDuty) formAddDuty.reset();
            if (dutyRombelWrap) dutyRombelWrap.style.display = 'none';
        }
    };

    if (btnCloseDutyModal) btnCloseDutyModal.addEventListener('click', closeDutyModal);
    if (btnCancelDutyModal) btnCancelDutyModal.addEventListener('click', closeDutyModal);

    if (dutyTugasSelect && dutyRombelWrap) {
        dutyTugasSelect.addEventListener('change', () => {
            const selectedOpt = dutyTugasSelect.options[dutyTugasSelect.selectedIndex];
            const kode = selectedOpt ? selectedOpt.dataset.kode : '';
            dutyRombelWrap.style.display = (kode === 'WALI_KELAS') ? 'block' : 'none';
        });
    }

    if (formAddDuty) {
        formAddDuty.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(formAddDuty);
            const payload = Object.fromEntries(formData.entries());

            try {
                const res = await fetch(storeDutyUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (data.status === 'success') {
                    showToast(data.message, 'success');
                    closeDutyModal();
                    setTimeout(() => window.location.reload(), 600);
                } else {
                    throw new Error(data.message || 'Gagal menyimpan');
                }
            } catch (err) {
                showToast(err.message, 'danger');
            }
        });
    }

    // Hapus Penugasan
    document.querySelectorAll('.btn-delete-duty').forEach(btn => {
        btn.addEventListener('click', async () => {
            const id = btn.dataset.id;
            const name = btn.dataset.name;

            let confirmed = false;
            if (window.SAE && typeof window.SAE.confirm === 'function') {
                confirmed = await window.SAE.confirm(
                    `Hapus penugasan tugas tambahan untuk ${name}?`,
                    'Hapus Penugasan',
                    'warning'
                );
            } else {
                confirmed = confirm(`Hapus penugasan tugas tambahan untuk ${name}?`);
            }

            if (!confirmed) return;

            try {
                const res = await fetch(`${destroyDutyBaseUrl}/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    }
                });
                const data = await res.json();
                if (data.status === 'success') {
                    showToast(data.message, 'success');
                    setTimeout(() => window.location.reload(), 600);
                } else {
                    throw new Error(data.message || 'Gagal menghapus');
                }
            } catch (err) {
                showToast(err.message, 'danger');
            }
        });
    });

    // Sinkronkan Wali Kelas dari Dapodik
    const btnSyncWaliKelas = document.getElementById('btnSyncWaliKelas');
    if (btnSyncWaliKelas) {
        btnSyncWaliKelas.addEventListener('click', async () => {
            const originalHtml = btnSyncWaliKelas.innerHTML;
            btnSyncWaliKelas.disabled = true;
            btnSyncWaliKelas.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Menyinkronkan...';

            try {
                const res = await fetch(syncWaliUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    }
                });
                const data = await res.json();
                if (data.status === 'success') {
                    showToast(data.message, 'success');
                    setTimeout(() => window.location.reload(), 800);
                } else {
                    throw new Error(data.message || 'Gagal menyinkronkan');
                }
            } catch (err) {
                showToast(err.message, 'danger');
            } finally {
                btnSyncWaliKelas.disabled = false;
                btnSyncWaliKelas.innerHTML = originalHtml;
            }
        });
    }

    // 3. Tombol Sinkronisasi Modul Baru Otomatis
    const btnSync = document.getElementById('btnSyncModules');
    if (btnSync) {
        btnSync.addEventListener('click', async () => {
            const originalHtml = btnSync.innerHTML;
            btnSync.disabled = true;
            btnSync.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Menyinkronkan...';

            try {
                const res = await fetch(syncUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    }
                });
                const data = await res.json();
                if (data.status === 'success') {
                    showToast(data.message, 'success');
                    if (data.count > 0) {
                        setTimeout(() => window.location.reload(), 800);
                    }
                } else {
                    throw new Error(data.message || 'Gagal menyinkronkan modul');
                }
            } catch (err) {
                showToast(err.message || 'Terjadi kesalahan sinkronisasi', 'danger');
            } finally {
                btnSync.disabled = false;
                btnSync.innerHTML = originalHtml;
            }
        });
    }

    // 4. Tombol Reset
    const btnReset = document.getElementById('btnResetDefault');
    if (btnReset) {
        btnReset.addEventListener('click', async () => {
            let confirmed = false;
            const confirmMsg = 'Tindakan ini akan menghapus semua hak akses modul pada peran Guru, Tendik, dan Peserta Didik, serta memberikan akses penuh ke seluruh modul untuk Administrator. Lanjutkan?';
            if (window.SAE && typeof window.SAE.confirm === 'function') {
                confirmed = await window.SAE.confirm(confirmMsg, 'Reset Hak Akses?', 'warning');
            } else if (typeof Swal !== 'undefined') {
                const result = await Swal.fire({
                    title: 'Reset Hak Akses?',
                    text: confirmMsg,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ef4444',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Ya, Reset Sekarang',
                    cancelButtonText: 'Batal'
                });
                confirmed = result.isConfirmed;
            } else {
                confirmed = confirm(confirmMsg);
            }

            if (!confirmed) return;

            const originalHtml = btnReset.innerHTML;
            btnReset.disabled = true;
            btnReset.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Memproses...';

            try {
                const res = await fetch(resetUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({
                        role: activeRole
                    })
                });
                const data = await res.json();
                if (data.status === 'success') {
                    showToast(data.message, 'success');
                    setTimeout(() => window.location.reload(), 600);
                } else {
                    throw new Error(data.message || 'Gagal mereset izin');
                }
            } catch (err) {
                showToast(err.message || 'Gagal mereset izin', 'danger');
            } finally {
                btnReset.disabled = false;
                btnReset.innerHTML = originalHtml;
            }
        });
    }

    // 5. Tambah Modul ke Peran
    const btnOpenAddModule = document.getElementById('btnOpenAddModule');
    const modalAddModule = document.getElementById('modalAddModule');
    const btnCloseAddModule = document.getElementById('btnCloseAddModule');
    const btnCancelAddModule = document.getElementById('btnCancelAddModule');
    const formAddModule = document.getElementById('formAddModule');
    const btnSubmitAddModule = document.getElementById('btnSubmitAddModule');

    if (btnOpenAddModule && modalAddModule) {
        btnOpenAddModule.addEventListener('click', () => {
            modalAddModule.style.display = 'flex';
        });
    }

    if (btnCloseAddModule && modalAddModule) {
        btnCloseAddModule.addEventListener('click', () => {
            modalAddModule.style.display = 'none';
        });
    }

    if (btnCancelAddModule && modalAddModule) {
        btnCancelAddModule.addEventListener('click', () => {
            modalAddModule.style.display = 'none';
        });
    }

    if (modalAddModule) {
        modalAddModule.addEventListener('click', (e) => {
            if (e.target === modalAddModule) {
                modalAddModule.style.display = 'none';
            }
        });
    }

    const selectAddModule = document.getElementById('selectAddModule');
    const customModuleFields = document.getElementById('customModuleFields');
    const inputCustomModuleName = document.getElementById('inputCustomModuleName');

    if (selectAddModule && customModuleFields) {
        selectAddModule.addEventListener('change', () => {
            if (selectAddModule.value === '__NEW_CUSTOM_MODULE__') {
                customModuleFields.style.display = 'flex';
                if (inputCustomModuleName) {
                    inputCustomModuleName.required = true;
                    inputCustomModuleName.focus();
                }
            } else {
                customModuleFields.style.display = 'none';
                if (inputCustomModuleName) {
                    inputCustomModuleName.required = false;
                }
            }
        });
    }

    if (formAddModule) {
        formAddModule.addEventListener('submit', async (e) => {
            e.preventDefault();
            const permKey = selectAddModule?.value;
            const customName = inputCustomModuleName ? inputCustomModuleName.value.trim() : '';

            if (!permKey) {
                showToast('Silakan pilih modul terlebih dahulu.', 'warning');
                return;
            }

            if (permKey === '__NEW_CUSTOM_MODULE__' && !customName) {
                showToast('Silakan masukkan nama modul baru.', 'warning');
                inputCustomModuleName?.focus();
                return;
            }

            const origText = btnSubmitAddModule ? btnSubmitAddModule.innerHTML : '';
            if (btnSubmitAddModule) {
                btnSubmitAddModule.disabled = true;
                btnSubmitAddModule.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Menyimpan...';
            }

            try {
                const res = await fetch(addModuleUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({
                        role: activeRole,
                        permission_key: permKey,
                        custom_name: customName
                    })
                });

                const data = await res.json();
                if (data.status === 'success') {
                    showToast(data.message || 'Modul berhasil ditambahkan!', 'success');
                    if (modalAddModule) modalAddModule.style.display = 'none';
                    setTimeout(() => window.location.reload(), 600);
                } else {
                    throw new Error(data.message || 'Gagal menambahkan modul');
                }
            } catch (err) {
                showToast(err.message || 'Terjadi kesalahan sistem', 'danger');
                if (btnSubmitAddModule) {
                    btnSubmitAddModule.disabled = false;
                    btnSubmitAddModule.innerHTML = origText;
                }
            }
        });
    }

    // 6. Hapus Modul dari Peran
    document.addEventListener('click', async (e) => {
        const btn = e.target.closest('.btn-remove-module');
        if (!btn) return;

        const key = btn.dataset.key;
        const name = btn.dataset.name || key;

        let confirmed = false;
        const confirmMsg = `Hapus modul "${name}" dari peran ${roleName}? Modul ini tidak akan lagi tampil di menu peran ini.`;

        if (window.SAE && typeof window.SAE.confirm === 'function') {
            confirmed = await window.SAE.confirm(confirmMsg, 'Hapus Modul?', 'warning');
        } else {
            confirmed = confirm(confirmMsg);
        }

        if (!confirmed) return;

        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

        try {
            const res = await fetch(removeModuleUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({
                    role: activeRole,
                    permission_key: key
                })
            });

            const data = await res.json();
            if (data.status === 'success') {
                showToast(data.message || 'Modul berhasil dihapus dari peran ini.', 'success');

                // Hapus baris dari DOM secara mulus
                const row = btn.closest('tr');
                if (row) {
                    row.style.transition = 'all 0.3s ease';
                    row.style.opacity = '0';
                    row.style.transform = 'translateX(20px)';
                    setTimeout(() => {
                        row.remove();
                        allRows = allRows.filter(r => r !== row);
                        renderTable();
                    }, 300);
                } else {
                    setTimeout(() => window.location.reload(), 600);
                }
            } else {
                throw new Error(data.message || 'Gagal menghapus modul');
            }
        } catch (err) {
            showToast(err.message || 'Terjadi kesalahan saat menghapus modul', 'danger');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-trash-can"></i>';
        }
    });
});
