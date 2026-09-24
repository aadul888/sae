/**
 * Hak Akses & Peran JavaScript Module
 * Sistem Aplikasi Edukasi (SAE)
 */

document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('hakAksesContainer');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    const activeRole = container?.dataset.activeRole || 'global';
    const roleName = container?.dataset.roleName || activeRole;
    const toggleUrl = container?.dataset.toggleUrl || '/dashboard/hak-akses/toggle';
    const syncUrl = container?.dataset.syncUrl || '/dashboard/hak-akses/sync';
    const resetUrl = container?.dataset.resetUrl || '/dashboard/hak-akses/reset';
    const addModuleUrl = container?.dataset.addModuleUrl || '/dashboard/hak-akses/add-module';
    const removeModuleUrl = container?.dataset.removeModuleUrl || '/dashboard/hak-akses/remove-module';

    // Unified Toast Notifikasi
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
                timer: 2500,
                timerProgressBar: true
            });
        }
    };

    // 1. Quick Role Toggle (Di Kolom Peran pada Datatable Global)
    document.querySelectorAll('.role-quick-toggle').forEach(toggle => {
        toggle.addEventListener('change', async (e) => {
            const target = e.target;
            const role = target.dataset.role;
            const key = target.dataset.key;
            const action = target.dataset.action || 'read';
            const color = target.dataset.color || '#10b981';
            const isAllowed = target.checked;
            const slider = target.nextElementSibling;

            if (slider) {
                slider.style.backgroundColor = isAllowed ? color : '#64748b';
            }

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
                    showToast(data.message || 'Izin peran berhasil diperbarui.');
                } else {
                    throw new Error(data.message || 'Gagal mengubah izin');
                }
            } catch (err) {
                target.checked = !isAllowed;
                if (slider) {
                    slider.style.backgroundColor = !isAllowed ? color : '#64748b';
                }
                showToast(err.message || 'Gagal mengubah izin', 'danger');
            }
        });
    });

    // 2. Generic CRUD Toggle Handler (Untuk Tabel Tab Peran Spesifik & Modal Granular)
    const bindCrudToggles = (parent = document) => {
        parent.querySelectorAll('.crud-toggle').forEach(toggle => {
            if (toggle.dataset.bound) return;
            toggle.dataset.bound = 'true';

            toggle.addEventListener('change', async (e) => {
                const target = e.target;
                const role = target.dataset.role;
                const key = target.dataset.key;
                const action = target.dataset.action;
                const color = target.dataset.color || '#10b981';
                const isAllowed = target.checked;
                const slider = target.nextElementSibling;
                const row = target.closest('tr');

                if (slider) {
                    slider.style.backgroundColor = isAllowed ? color : '#64748b';
                }

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

                        if (data.data && row) {
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
                    if (slider) {
                        slider.style.backgroundColor = !isAllowed ? color : '#64748b';
                    }
                    showToast(err.message || 'Gagal mengubah izin ' + action, 'danger');
                }
            });
        });
    };
    bindCrudToggles(document);

    // 3. Modal Detail Izin Granular CRUD (Untuk Tampilan Global)
    const modalGranularCrud = document.getElementById('modalGranularCrud');
    const modalCrudTitle = document.getElementById('modalCrudTitle');
    const modalCrudSubtitle = document.getElementById('modalCrudSubtitle');
    const modalCrudContent = document.getElementById('modalCrudContent');
    const btnCloseCrudModal = document.getElementById('btnCloseCrudModal');
    const btnDoneCrudModal = document.getElementById('btnDoneCrudModal');

    document.querySelectorAll('.btn-open-crud-modal').forEach(btn => {
        btn.addEventListener('click', () => {
            const rawMod = btn.dataset.module;
            if (!rawMod) return;

            try {
                const mod = JSON.parse(rawMod);
                if (modalCrudTitle) {
                    modalCrudTitle.innerHTML = `<i class="fas ${mod.icon || 'fa-cube'} text-primary me-2"></i> ${mod.label}`;
                }
                if (modalCrudSubtitle) {
                    modalCrudSubtitle.textContent = `Kode: ${mod.key} | Kelompok: ${mod.group}`;
                }

                const rolesInfo = [
                    { key: 'admin', label: 'Administrator', icon: 'fa-user-shield', color: '#6366f1' },
                    { key: 'guru', label: 'Guru', icon: 'fa-chalkboard-user', color: '#10b981' },
                    { key: 'tendik', label: 'Tenaga Kependidikan', icon: 'fa-id-badge', color: '#0ea5e9' },
                    { key: 'peserta_didik', label: 'Peserta Didik', icon: 'fa-user-graduate', color: '#f59e0b' }
                ];

                let html = '';
                rolesInfo.forEach(r => {
                    const rData = mod.roles?.[r.key] || {};
                    const isLocked = !!rData.is_locked;

                    html += `
                    <div style="background: rgba(255,255,255,0.02); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <span style="display: inline-flex; align-items: center; justify-content: center; width: 28px; height: 28px; border-radius: 6px; background: rgba(255,255,255,0.06); color: ${r.color}; font-size: 0.85rem;">
                                    <i class="fas ${r.icon}"></i>
                                </span>
                                <span style="font-weight: 700; font-size: 0.88rem; color: var(--text-color);">${r.label}</span>
                            </div>
                            ${isLocked ? '<span class="badge badge-warning" style="font-size: 0.65rem;">Kunci Sistem</span>' : ''}
                        </div>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(115px, 1fr)); gap: 8px; padding-top: 8px; border-top: 1px solid rgba(255,255,255,0.05);">
                            ${['read', 'create', 'update', 'delete'].map(act => {
                                const actLabels = { read: 'Lihat (Read)', create: 'Tambah (Create)', update: 'Ubah (Update)', delete: 'Hapus (Delete)' };
                                const actColors = { read: '#10b981', create: '#3b82f6', update: '#f59e0b', delete: '#ef4444' };
                                const checked = !!rData['can_' + act];
                                return `
                                <div style="display: flex; align-items: center; justify-content: space-between; background: rgba(0,0,0,0.2); padding: 6px 10px; border-radius: 6px;">
                                    <span style="font-size: 0.73rem; color: var(--text-muted);">${actLabels[act]}</span>
                                    <label class="switch-container" style="position: relative; display: inline-block; width: 34px; height: 18px; margin: 0; cursor: ${isLocked ? 'not-allowed' : 'pointer'};">
                                        <input type="checkbox" class="crud-toggle"
                                            data-role="${r.key}"
                                            data-key="${mod.key}"
                                            data-action="${act}"
                                            data-color="${actColors[act]}"
                                            ${checked ? 'checked' : ''}
                                            ${isLocked ? 'disabled' : ''}
                                            style="opacity: 0; width: 0; height: 0;">
                                        <span class="slider-toggle-crud"
                                            style="position: absolute; inset: 0; background-color: ${checked ? actColors[act] : '#64748b'}; transition: .3s; border-radius: 20px;"></span>
                                    </label>
                                </div>
                                `;
                            }).join('')}
                        </div>
                    </div>
                    `;
                });

                if (modalCrudContent) {
                    modalCrudContent.innerHTML = html;
                    bindCrudToggles(modalCrudContent);
                }

                if (modalGranularCrud) {
                    modalGranularCrud.style.display = 'flex';
                }
            } catch (err) {
                console.error('Error parsing module data for modal:', err);
            }
        });
    });

    const closeGranularModal = () => {
        if (modalGranularCrud) modalGranularCrud.style.display = 'none';
    };
    if (btnCloseCrudModal) btnCloseCrudModal.addEventListener('click', closeGranularModal);
    if (btnDoneCrudModal) btnDoneCrudModal.addEventListener('click', closeGranularModal);
    if (modalGranularCrud) {
        modalGranularCrud.addEventListener('click', (e) => {
            if (e.target === modalGranularCrud) closeGranularModal();
        });
    }

    // 4. Client-side Search, Group Filter, Sorting & Pagination untuk Datatable
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

    // 5. Tombol Sinkronkan Modul
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
                    setTimeout(() => window.location.reload(), 600);
                } else {
                    throw new Error(data.message || 'Gagal menyinkronkan modul');
                }
            } catch (err) {
                showToast(err.message || 'Gagal menyinkronkan modul', 'danger');
            } finally {
                btnSync.disabled = false;
                btnSync.innerHTML = originalHtml;
            }
        });
    }

    // 6. Tombol Reset Default
    const btnReset = document.getElementById('btnResetDefault');
    if (btnReset) {
        btnReset.addEventListener('click', async () => {
            let confirmed = false;
            const isGlobal = !activeRole || activeRole === 'global';
            const titleMsg = isGlobal ? 'Reset Hak Akses Semua Peran?' : `Reset Default ${roleName}?`;
            const confirmMsg = isGlobal
                ? 'Tindakan ini akan mengembalikan matriks hak akses SELURUH PERAN (Administrator, Guru, Tendik, Peserta Didik) ke standar baku default kelompoknya masing-masing. Lanjutkan?'
                : `Tindakan ini akan mengembalikan hak akses peran ${roleName} ke standar default kelompoknya saja. Modul peran lain tidak akan terpengaruh. Lanjutkan?`;

            if (typeof Swal !== 'undefined') {
                const result = await Swal.fire({
                    title: titleMsg,
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

    // 7. Tambah Modul ke Peran (Untuk Tab Spesifik)
    const btnOpenAddModule = document.getElementById('btnOpenAddModule');
    const modalAddModule = document.getElementById('modalAddModule');
    const btnCloseAddModule = document.getElementById('btnCloseAddModule');
    const btnCancelAddModule = document.getElementById('btnCancelAddModule');
    const formAddModule = document.getElementById('formAddModule');
    const btnSubmitAddModule = document.getElementById('btnSubmitAddModule');

    const selectAddModule = document.getElementById('selectAddModule');
    const filterModuleOptions = document.getElementById('filterModuleOptions');
    const customModuleFields = document.getElementById('customModuleFields');
    const inputCustomModuleName = document.getElementById('inputCustomModuleName');

    if (btnOpenAddModule && modalAddModule) {
        btnOpenAddModule.addEventListener('click', () => {
            modalAddModule.style.display = 'flex';
            if (filterModuleOptions) {
                filterModuleOptions.value = '';
                filterModuleOptions.dispatchEvent(new Event('input'));
                setTimeout(() => filterModuleOptions.focus(), 100);
            }
        });
    }

    if (filterModuleOptions && selectAddModule) {
        filterModuleOptions.addEventListener('input', function () {
            const query = this.value.toLowerCase().trim();
            const optgroups = selectAddModule.querySelectorAll('optgroup');
            let firstMatchedVal = null;

            optgroups.forEach(group => {
                let groupHasVisible = false;
                const options = group.querySelectorAll('option');
                options.forEach(opt => {
                    const text = (opt.textContent || '').toLowerCase();
                    const val = (opt.value || '').toLowerCase();
                    if (val === '__new_custom_module__') {
                        opt.style.display = '';
                        groupHasVisible = true;
                        return;
                    }
                    if (!query || text.includes(query) || val.includes(query)) {
                        opt.style.display = '';
                        groupHasVisible = true;
                        if (!firstMatchedVal && !opt.disabled && opt.value) {
                            firstMatchedVal = opt.value;
                        }
                    } else {
                        opt.style.display = 'none';
                    }
                });
                group.style.display = groupHasVisible ? '' : 'none';
            });

            if (firstMatchedVal && query) {
                selectAddModule.value = firstMatchedVal;
                selectAddModule.dispatchEvent(new Event('change'));
            }
        });
    }

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

            const originalBtnText = btnSubmitAddModule?.innerHTML || 'Tambahkan';
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
                    showToast(data.message, 'success');
                    if (modalAddModule) modalAddModule.style.display = 'none';
                    setTimeout(() => window.location.reload(), 600);
                } else {
                    throw new Error(data.message || 'Gagal menambahkan modul');
                }
            } catch (err) {
                showToast(err.message || 'Gagal menambahkan modul', 'danger');
            } finally {
                if (btnSubmitAddModule) {
                    btnSubmitAddModule.disabled = false;
                    btnSubmitAddModule.innerHTML = originalBtnText;
                }
            }
        });
    }

    const closeAddModal = () => {
        if (modalAddModule) modalAddModule.style.display = 'none';
    };
    if (btnCloseAddModule) btnCloseAddModule.addEventListener('click', closeAddModal);
    if (btnCancelAddModule) btnCancelAddModule.addEventListener('click', closeAddModal);
    if (modalAddModule) {
        modalAddModule.addEventListener('click', (e) => {
            if (e.target === modalAddModule) closeAddModal();
        });
    }

    // 8. Hapus Modul dari Peran
    document.querySelectorAll('.btn-remove-module').forEach(btn => {
        btn.addEventListener('click', async () => {
            const key = btn.dataset.key;
            const name = btn.dataset.name || key;

            let confirmed = false;
            const confirmMsg = `Hapus modul "${name}" dari peran ${roleName}? Pengguna dengan peran ini tidak akan lagi memiliki hak akses ke modul tersebut.`;

            if (typeof Swal !== 'undefined') {
                const result = await Swal.fire({
                    title: 'Hapus Modul dari Peran?',
                    text: confirmMsg,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ef4444',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Ya, Hapus',
                    cancelButtonText: 'Batal'
                });
                confirmed = result.isConfirmed;
            } else {
                confirmed = confirm(confirmMsg);
            }

            if (!confirmed) return;

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
                    showToast(data.message, 'success');
                    const row = btn.closest('tr');
                    if (row) {
                        row.remove();
                        allRows = Array.from(document.querySelectorAll('.module-row'));
                        renderTable();
                    }
                } else {
                    throw new Error(data.message || 'Gagal menghapus modul');
                }
            } catch (err) {
                showToast(err.message || 'Gagal menghapus modul', 'danger');
            }
        });
    });

    // Close modals on Escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeGranularModal();
            closeAddModal();
        }
    });
});
