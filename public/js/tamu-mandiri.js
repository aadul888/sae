document.addEventListener('DOMContentLoaded', function () {
    const categoryOptions = document.querySelectorAll('.category-option');
    const sectionOrangTua = document.getElementById('sectionOrangTua');
    const groupInstansi = document.getElementById('groupInstansi');
    const inputNikOrtu = document.getElementById('input_nik_ortu');
    const inputNisnSiswa = document.getElementById('input_nisn_siswa');
    const btnCekSiswa = document.getElementById('btnCekSiswa');
    const detectionResult = document.getElementById('studentDetectionResult');
    const manualStudentInput = document.getElementById('manualStudentInput');
    const hiddenPdId = document.getElementById('hidden_peserta_didik_id');
    const manualNamaSiswa = document.getElementById('input_manual_nama_siswa');
    const manualRombelSiswa = document.getElementById('input_manual_rombel_siswa');
    const tamuNama = document.getElementById('tamu_nama');
    const formPublic = document.getElementById('formPublicTamu');
    const btnSubmit = document.getElementById('btnSubmitTamu');

    const categoryStep = document.getElementById('categoryStep');
    const categoryWarning = document.getElementById('categoryWarning');
    const formFields = document.getElementById('formFields');
    let selectedCategory = null;

    // 1. Kategori Tamu — wajib pilih sebelum isi field lain
    categoryOptions.forEach(opt => {
        opt.addEventListener('click', function () {
            categoryOptions.forEach(o => o.classList.remove('active'));
            this.classList.add('active');
            const radio = this.querySelector('input[type="radio"]');
            if (radio) radio.checked = true;

            const val = this.dataset.type;
            selectedCategory = val;

            // Tandai langkah 1 selesai + buka field lanjutan
            if (categoryStep) categoryStep.classList.add('answered');
            if (categoryWarning) categoryWarning.style.display = 'none';
            if (formFields) formFields.classList.remove('etamu-fields-locked');

            if (val === 'orang_tua') {
                if (sectionOrangTua) sectionOrangTua.style.display = 'block';
                if (groupInstansi) groupInstansi.style.display = 'none';
            } else {
                if (sectionOrangTua) sectionOrangTua.style.display = 'none';
                if (groupInstansi) groupInstansi.style.display = 'block';
                if (hiddenPdId) hiddenPdId.value = '';
                if (manualNamaSiswa) manualNamaSiswa.value = '';
                if (manualRombelSiswa) manualRombelSiswa.value = '';
            }
        });
    });

    // 2. Deteksi Siswa (AJAX)
    let searchTimeout = null;

    function doCheckStudent() {
        const nik = inputNikOrtu ? inputNikOrtu.value.trim() : '';
        const nisn = inputNisnSiswa ? inputNisnSiswa.value.trim() : '';

        if (!nik && !nisn) {
            if (detectionResult) {
                detectionResult.style.display = 'none';
                detectionResult.innerHTML = '';
            }
            if (manualStudentInput) manualStudentInput.style.display = 'none';
            return;
        }

        const url = (window.ETAMU_CONFIG && window.ETAMU_CONFIG.checkStudentUrl) || '/tamu/api/cek-siswa';

        if (btnCekSiswa) {
            btnCekSiswa.disabled = true;
            btnCekSiswa.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        }

        fetch(`${url}?nik_ortu=${encodeURIComponent(nik)}&nisn_siswa=${encodeURIComponent(nisn)}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(res => res.json())
        .then(data => {
            if (btnCekSiswa) {
                btnCekSiswa.disabled = false;
                btnCekSiswa.innerHTML = '<i class="fas fa-search me-1"></i> Cek';
            }

            if (!detectionResult) return;

            if (data.found && data.students && data.students.length > 0) {
                if (manualStudentInput) manualStudentInput.style.display = 'none';

                // Auto-fill nama orang tua jika masih kosong
                if (data.suggested_parent_name && tamuNama && !tamuNama.value.trim()) {
                    tamuNama.value = data.suggested_parent_name;
                }

                if (data.students.length === 1) {
                    const st = data.students[0];
                    if (hiddenPdId) hiddenPdId.value = st.peserta_didik_id;
                    if (manualNamaSiswa) manualNamaSiswa.value = st.nama;
                    if (manualRombelSiswa) manualRombelSiswa.value = st.nama_rombel;

                    detectionResult.innerHTML = `
                        <div class="student-result-card" style="border-color: #10b981; background: #ecfdf5;">
                            <div class="student-result-info">
                                <h4><i class="fas fa-check-circle text-success me-1"></i> ${escapeHtml(st.nama)}</h4>
                                <p>Kelas: <strong>${escapeHtml(st.nama_rombel || '-')}</strong> &bull; NISN: ${escapeHtml(st.nisn || '-')}</p>
                                ${data.matched_role ? `<span style="font-size: 11px; color: #047857;">Terverifikasi sebagai data anak dari ${escapeHtml(data.matched_role)}</span>` : ''}
                            </div>
                            <span class="badge" style="background: #10b981; color: white; padding: 4px 8px; border-radius: 6px; font-size: 11px;">
                                Terpilih
                            </span>
                        </div>
                    `;
                } else {
                    // Beberapa anak ditemukan
                    let cardsHtml = `
                        <div style="font-size: 12px; font-weight: 700; color: var(--primary); margin-top: 8px;">
                            Ditemukan ${data.students.length} Siswa Terkait NIK/NISN:
                        </div>
                    `;
                    data.students.forEach((st, idx) => {
                        const isChecked = idx === 0 ? 'checked' : '';
                        cardsHtml += `
                            <label class="student-result-card" style="cursor: pointer; margin-top: 6px;">
                                <div class="student-result-info">
                                    <h4>${escapeHtml(st.nama)}</h4>
                                    <p>Kelas: <strong>${escapeHtml(st.nama_rombel || '-')}</strong> &bull; NISN: ${escapeHtml(st.nisn || '-')}</p>
                                </div>
                                <input type="radio" name="_pick_student" value="${escapeHtml(st.peserta_didik_id)}"
                                    data-nama="${escapeHtml(st.nama)}" data-rombel="${escapeHtml(st.nama_rombel || '')}" ${isChecked}
                                    style="width: 18px; height: 18px; accent-color: var(--primary);">
                            </label>
                        `;
                    });
                    detectionResult.innerHTML = cardsHtml;

                    // Set default first student
                    const first = data.students[0];
                    if (hiddenPdId) hiddenPdId.value = first.peserta_didik_id;
                    if (manualNamaSiswa) manualNamaSiswa.value = first.nama;
                    if (manualRombelSiswa) manualRombelSiswa.value = first.nama_rombel;

                    // Listen radio change
                    detectionResult.querySelectorAll('input[name="_pick_student"]').forEach(r => {
                        r.addEventListener('change', function () {
                            if (hiddenPdId) hiddenPdId.value = this.value;
                            if (manualNamaSiswa) manualNamaSiswa.value = this.dataset.nama;
                            if (manualRombelSiswa) manualRombelSiswa.value = this.dataset.rombel;
                        });
                    });
                }
                detectionResult.style.display = 'block';
            } else {
                // Not found
                if (hiddenPdId) hiddenPdId.value = '';
                detectionResult.innerHTML = `
                    <div style="font-size: 12px; color: #b45309; background: #fffbeb; border: 1px solid #fde68a; border-radius: 8px; padding: 8px 12px; margin-top: 8px;">
                        <i class="fas fa-exclamation-triangle me-1"></i> Data siswa belum ditemukan otomatis. Anda dapat mengetikkan nama siswa & kelas secara manual pada kolom di bawah.
                    </div>
                `;
                detectionResult.style.display = 'block';
                if (manualStudentInput) manualStudentInput.style.display = 'block';
            }
        })
        .catch(() => {
            if (btnCekSiswa) {
                btnCekSiswa.disabled = false;
                btnCekSiswa.innerHTML = '<i class="fas fa-search me-1"></i> Cek';
            }
            if (manualStudentInput) manualStudentInput.style.display = 'block';
        });
    }

    if (btnCekSiswa) {
        btnCekSiswa.addEventListener('click', doCheckStudent);
    }

    // Auto trigger saat NIK 16 digit terketik lengkap
    if (inputNikOrtu) {
        inputNikOrtu.addEventListener('input', function () {
            this.value = this.value.replace(/[^0-9]/g, '');
            if (this.value.length === 16) {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(doCheckStudent, 400);
            }
        });
        inputNikOrtu.addEventListener('blur', function () {
            if (this.value.length >= 10) {
                doCheckStudent();
            }
        });
    }

    if (inputNisnSiswa) {
        inputNisnSiswa.addEventListener('input', function () {
            this.value = this.value.replace(/[^0-9]/g, '');
            if (this.value.length >= 10) {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(doCheckStudent, 500);
            }
        });
        inputNisnSiswa.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                doCheckStudent();
            }
        });
    }

    // 3. Form Submission Handling
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
