document.addEventListener('DOMContentLoaded', function () {
    const categoryOptions = document.querySelectorAll('.category-option');
    const sectionOrangTua = document.getElementById('sectionOrangTua');
    const groupInstansi = document.getElementById('groupInstansi');
    const formPublic = document.getElementById('formPublicTamu');
    const btnSubmit = document.getElementById('btnSubmitTamu');

    const categoryStep = document.getElementById('categoryStep');
    const categoryWarning = document.getElementById('categoryWarning');
    const formFields = document.getElementById('formFields');
    let selectedCategory = null;

    // Student Search & Selection Elements
    const inputCariSiswa = document.getElementById('input_cari_siswa');
    const studentSearchSpinner = document.getElementById('studentSearchSpinner');
    const studentDropdownList = document.getElementById('studentDropdownList');
    const studentSelectedBox = document.getElementById('studentSelectedBox');
    const selectedStudentNama = document.getElementById('selectedStudentNama');
    const selectedStudentDetail = document.getElementById('selectedStudentDetail');
    const btnClearStudent = document.getElementById('btnClearStudent');
    const studentSearchWrapper = document.getElementById('studentSearchWrapper');
    const btnToggleManualStudent = document.getElementById('btnToggleManualStudent');
    const manualStudentInput = document.getElementById('manualStudentInput');

    const hiddenPdId = document.getElementById('hidden_peserta_didik_id');
    const hiddenNamaSiswa = document.getElementById('hidden_nama_siswa');
    const hiddenRombelSiswa = document.getElementById('hidden_rombel_siswa');
    const hiddenNisnSiswa = document.getElementById('hidden_nisn_siswa');
    const hiddenNikOrtu = document.getElementById('hidden_nik_ortu');

    const inputManualNama = document.getElementById('input_manual_nama_siswa');
    const inputManualRombel = document.getElementById('input_manual_rombel_siswa');
    const tamuNama = document.getElementById('tamu_nama');

    // Kedinasan elements
    const sectionKedinasanSurat = document.getElementById('sectionKedinasanSurat');
    const sectionKedinasanIdentitas = document.getElementById('sectionKedinasanIdentitas');
    const radioSuratYa = document.getElementById('radioSuratYa');
    const radioSuratTidak = document.getElementById('radioSuratTidak');
    const boxDetailSuratTugas = document.getElementById('boxDetailSuratTugas');
    const inputNomorSurat = document.getElementById('nomor_surat_tugas');
    const inputTanggalSurat = document.getElementById('tanggal_surat_tugas');

    // Dropdowns: Instansi, Tujuan, Keperluan
    const selectInstansi = document.getElementById('tamu_instansi_select');
    const wrapInstansiLainnya = document.getElementById('wrapInstansiLainnya');
    const inputInstansiLainnya = document.getElementById('instansi_lainnya_input');
    const hiddenInstansi = document.getElementById('tamu_instansi');

    const selectTujuan = document.getElementById('tamu_tujuan_select');
    const wrapTujuanLainnya = document.getElementById('wrapTujuanLainnya');
    const inputTujuanLainnya = document.getElementById('tujuan_lainnya_input');
    const hiddenTujuan = document.getElementById('tamu_tujuan');

    const selectKeperluan = document.getElementById('tamu_keperluan_select');
    const wrapKeperluanLainnya = document.getElementById('wrapKeperluanLainnya');
    const inputKeperluanLainnya = document.getElementById('keperluan_lainnya_input');
    const hiddenKeperluan = document.getElementById('tamu_keperluan');

    function toggleSuratTugas(show) {
        if (!boxDetailSuratTugas) return;
        if (show) {
            boxDetailSuratTugas.style.display = 'block';
            if (inputNomorSurat) inputNomorSurat.required = true;
            if (inputTanggalSurat) inputTanggalSurat.required = true;
        } else {
            boxDetailSuratTugas.style.display = 'none';
            if (inputNomorSurat) {
                inputNomorSurat.required = false;
                inputNomorSurat.value = '';
            }
            if (inputTanggalSurat) {
                inputTanggalSurat.required = false;
                inputTanggalSurat.value = '';
            }
        }
    }

    if (radioSuratYa && radioSuratTidak) {
        radioSuratYa.addEventListener('change', function () {
            if (this.checked) toggleSuratTugas(true);
        });
        radioSuratTidak.addEventListener('change', function () {
            if (this.checked) toggleSuratTugas(false);
        });
    }

    // 1. Kategori Tamu — wajib pilih sebelum isi field lain
    categoryOptions.forEach(opt => {
        opt.addEventListener('click', function () {
            categoryOptions.forEach(o => o.classList.remove('active'));
            this.classList.add('active');
            const radio = this.querySelector('input[type="radio"]');
            if (radio) radio.checked = true;

            const val = this.dataset.type;
            selectedCategory = val;

            if (categoryStep) categoryStep.classList.add('answered');
            if (categoryWarning) categoryWarning.style.display = 'none';
            if (formFields) formFields.classList.remove('etamu-fields-locked');

            if (val === 'orang_tua') {
                if (sectionOrangTua) sectionOrangTua.style.display = 'block';
                if (groupInstansi) groupInstansi.style.display = 'none';
                if (sectionKedinasanSurat) sectionKedinasanSurat.style.display = 'none';
                if (sectionKedinasanIdentitas) sectionKedinasanIdentitas.style.display = 'none';
                if (hiddenInstansi) hiddenInstansi.value = 'Orang Tua / Wali Siswa';
                toggleSuratTugas(false);
            } else if (val === 'kedinasan') {
                if (sectionOrangTua) sectionOrangTua.style.display = 'none';
                if (groupInstansi) groupInstansi.style.display = 'block';
                if (sectionKedinasanSurat) sectionKedinasanSurat.style.display = 'block';
                if (sectionKedinasanIdentitas) sectionKedinasanIdentitas.style.display = 'block';
                clearStudentSelection();
            } else {
                if (sectionOrangTua) sectionOrangTua.style.display = 'none';
                if (groupInstansi) groupInstansi.style.display = 'block';
                if (sectionKedinasanSurat) sectionKedinasanSurat.style.display = 'none';
                if (sectionKedinasanIdentitas) sectionKedinasanIdentitas.style.display = 'none';
                toggleSuratTugas(false);
                clearStudentSelection();
            }
        });
    });

    // 2. Deteksi Otomatis Siswa: Autocomplete Live Search (Tanpa tombol Cari)
    let searchTimer = null;
    let abortCtrl = null;

    function clearStudentSelection() {
        if (hiddenPdId) hiddenPdId.value = '';
        if (hiddenNamaSiswa) hiddenNamaSiswa.value = '';
        if (hiddenRombelSiswa) hiddenRombelSiswa.value = '';
        if (hiddenNisnSiswa) hiddenNisnSiswa.value = '';
        if (hiddenNikOrtu) hiddenNikOrtu.value = '';
        if (inputManualNama) inputManualNama.value = '';
        if (inputManualRombel) inputManualRombel.value = '';

        if (studentSelectedBox) studentSelectedBox.style.display = 'none';
        if (studentSearchWrapper) studentSearchWrapper.style.display = 'block';
        if (inputCariSiswa) inputCariSiswa.value = '';
        hideStudentDropdown();
    }

    function hideStudentDropdown() {
        if (studentDropdownList) {
            studentDropdownList.style.display = 'none';
            studentDropdownList.innerHTML = '';
        }
    }

    function selectStudent(st, matchedRole, suggestedParent) {
        if (hiddenPdId) hiddenPdId.value = st.peserta_didik_id || '';
        if (hiddenNamaSiswa) hiddenNamaSiswa.value = st.nama || '';
        if (hiddenRombelSiswa) hiddenRombelSiswa.value = st.nama_rombel || '';
        if (hiddenNisnSiswa) hiddenNisnSiswa.value = st.nisn || '';
        if (hiddenNikOrtu && st.nik) hiddenNikOrtu.value = st.nik;

        if (inputManualNama) inputManualNama.value = st.nama || '';
        if (inputManualRombel) inputManualRombel.value = st.nama_rombel || '';

        // Auto-fill nama orang tua jika belum diisi
        if (suggestedParent && tamuNama && !tamuNama.value.trim()) {
            tamuNama.value = suggestedParent;
        }

        // Render card
        if (selectedStudentNama) selectedStudentNama.textContent = st.nama || '-';
        if (selectedStudentDetail) {
            const parts = [];
            if (st.nama_rombel) parts.push(`Kelas: ${st.nama_rombel}`);
            if (st.nisn) parts.push(`NISN: ${st.nisn}`);
            if (matchedRole) parts.push(matchedRole);
            selectedStudentDetail.textContent = parts.join(' • ');
        }

        if (studentSelectedBox) studentSelectedBox.style.display = 'block';
        if (studentSearchWrapper) studentSearchWrapper.style.display = 'none';
        if (manualStudentInput) manualStudentInput.style.display = 'none';
        hideStudentDropdown();
    }

    if (btnClearStudent) {
        btnClearStudent.addEventListener('click', function () {
            clearStudentSelection();
            if (inputCariSiswa) inputCariSiswa.focus();
        });
    }

    if (btnToggleManualStudent) {
        btnToggleManualStudent.addEventListener('click', function () {
            if (!manualStudentInput) return;
            const isHidden = manualStudentInput.style.display === 'none';
            manualStudentInput.style.display = isHidden ? 'block' : 'none';
            if (isHidden && inputManualNama) inputManualNama.focus();
        });
    }

    if (inputManualNama) {
        inputManualNama.addEventListener('input', function () {
            if (hiddenNamaSiswa) hiddenNamaSiswa.value = this.value;
        });
    }
    if (inputManualRombel) {
        inputManualRombel.addEventListener('input', function () {
            if (hiddenRombelSiswa) hiddenRombelSiswa.value = this.value;
        });
    }

    if (inputCariSiswa) {
        inputCariSiswa.addEventListener('input', function () {
            const q = this.value.trim();
            clearTimeout(searchTimer);
            if (abortCtrl) abortCtrl.abort();

            if (q.length < 2) {
                hideStudentDropdown();
                if (studentSearchSpinner) studentSearchSpinner.style.display = 'none';
                return;
            }

            if (studentSearchSpinner) studentSearchSpinner.style.display = 'block';

            searchTimer = setTimeout(function () {
                abortCtrl = new AbortController();
                const url = (window.ETAMU_CONFIG && window.ETAMU_CONFIG.checkStudentUrl) || '/tamu/api/cek-siswa';

                fetch(`${url}?q=${encodeURIComponent(q)}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    signal: abortCtrl.signal
                })
                .then(res => res.json())
                .then(data => {
                    if (studentSearchSpinner) studentSearchSpinner.style.display = 'none';
                    if (!studentDropdownList) return;

                    if (data.found && data.students && data.students.length > 0) {
                        studentDropdownList.innerHTML = '';
                        data.students.forEach(st => {
                            const btn = document.createElement('button');
                            btn.type = 'button';
                            btn.className = 'student-search-item';
                            btn.innerHTML = `
                                <div class="student-item-main">
                                    <span>${escapeHtml(st.nama)}</span>
                                    <span style="font-size: 0.72rem; color: #10b981; font-weight: 600;">${escapeHtml(st.nama_rombel || '-')}</span>
                                </div>
                                <div class="student-item-meta">
                                    NISN: ${escapeHtml(st.nisn || '-')} • ${st.nama_ayah ? 'Ayah: ' + escapeHtml(st.nama_ayah) : (st.nama_ibu ? 'Ibu: ' + escapeHtml(st.nama_ibu) : 'Siswa Aktif')}
                                </div>
                            `;
                            btn.addEventListener('click', function () {
                                selectStudent(st, data.matched_role, data.suggested_parent_name);
                            });
                            studentDropdownList.appendChild(btn);
                        });
                        studentDropdownList.style.display = 'block';
                    } else {
                        studentDropdownList.innerHTML = `
                            <div style="padding: 12px 14px; font-size: 0.78rem; color: var(--text-muted); text-align: center;">
                                Tidak ditemukan siswa dengan kata kunci "<strong>${escapeHtml(q)}</strong>".<br>
                                <span style="font-size: 0.72rem;">Gunakan tombol di bawah untuk mengetik manual.</span>
                            </div>
                        `;
                        studentDropdownList.style.display = 'block';
                    }
                })
                .catch(err => {
                    if (err.name !== 'AbortError') {
                        if (studentSearchSpinner) studentSearchSpinner.style.display = 'none';
                        hideStudentDropdown();
                    }
                });
            }, 250);
        });

        // Tutup dropdown saat klik di luar
        document.addEventListener('click', function (e) {
            if (!studentSearchWrapper || !studentSearchWrapper.contains(e.target)) {
                hideStudentDropdown();
            }
        });
    }

    // 3. Dropdown Synchronizers
    function bindDropdownWithCustom(selectEl, wrapEl, customInputEl, hiddenEl) {
        if (!selectEl) return;
        const update = () => {
            if (selectEl.value === 'Lainnya') {
                if (wrapEl) wrapEl.style.display = 'block';
                if (customInputEl) {
                    customInputEl.required = true;
                    if (hiddenEl) hiddenEl.value = customInputEl.value.trim();
                }
            } else {
                if (wrapEl) wrapEl.style.display = 'none';
                if (customInputEl) customInputEl.required = false;
                if (hiddenEl) hiddenEl.value = selectEl.value;
            }
        };

        selectEl.addEventListener('change', update);
        if (customInputEl) {
            customInputEl.addEventListener('input', function () {
                if (selectEl.value === 'Lainnya' && hiddenEl) {
                    hiddenEl.value = this.value.trim();
                }
            });
        }
        update();
    }

    bindDropdownWithCustom(selectTujuan, wrapTujuanLainnya, inputTujuanLainnya, hiddenTujuan);
    bindDropdownWithCustom(selectKeperluan, wrapKeperluanLainnya, inputKeperluanLainnya, hiddenKeperluan);
    bindDropdownWithCustom(selectInstansi, wrapInstansiLainnya, inputInstansiLainnya, hiddenInstansi);

    // 4. Form Submit
    if (formPublic) {
        formPublic.addEventListener('submit', function (e) {
            if (!selectedCategory) {
                e.preventDefault();
                if (categoryWarning) categoryWarning.style.display = 'flex';
                if (categoryStep) {
                    categoryStep.classList.add('shaking');
                    setTimeout(() => categoryStep.classList.remove('shaking'), 500);
                    categoryStep.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
                return;
            }

            // Sync final values
            if (selectTujuan && selectTujuan.value === 'Lainnya' && inputTujuanLainnya) {
                hiddenTujuan.value = inputTujuanLainnya.value.trim();
            } else if (selectTujuan) {
                hiddenTujuan.value = selectTujuan.value;
            }

            if (selectKeperluan && selectKeperluan.value === 'Lainnya' && inputKeperluanLainnya) {
                hiddenKeperluan.value = inputKeperluanLainnya.value.trim();
            } else if (selectKeperluan) {
                hiddenKeperluan.value = selectKeperluan.value;
            }

            if (selectedCategory !== 'orang_tua') {
                if (selectInstansi && selectInstansi.value === 'Lainnya' && inputInstansiLainnya) {
                    hiddenInstansi.value = inputInstansiLainnya.value.trim();
                } else if (selectInstansi && selectInstansi.value) {
                    hiddenInstansi.value = selectInstansi.value;
                }
            } else {
                if (hiddenInstansi) hiddenInstansi.value = 'Orang Tua / Wali Siswa';
            }

            if (btnSubmit) {
                btnSubmit.disabled = true;
                btnSubmit.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan Kunjungan...';
            }
        });
    }

    function escapeHtml(text) {
        if (!text) return '';
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
});
