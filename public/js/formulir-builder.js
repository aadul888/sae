/**
 * Formulir Builder — SAE Core JavaScript Module
 * Digunakan pada Create & Edit Formulir
 */

let questions = [];

document.addEventListener('DOMContentLoaded', function () {
    // 1. Baca data skema awal (untuk edit maupun create)
    const schemaEl = document.getElementById('initialSchemaData');
    if (schemaEl) {
        try {
            const rawData = schemaEl.textContent.trim();
            if (rawData) {
                questions = JSON.parse(rawData);
            }
        } catch (e) {
            console.error('Gagal mem-parsing skema awal formulir:', e);
            questions = [];
        }
    }

    // Jika mode create baru dan belum ada pertanyaan sama sekali, beri default 3 pertanyaan identitas & 1 pertanyaan teks
    const formEl = document.getElementById('builderForm');
    const isEditMode = formEl && formEl.dataset.mode === 'edit';
    if (!isEditMode && questions.length === 0) {
        addField('sae_nama', 'Nama Lengkap Siswa');
        addField('sae_nisn', 'NISN');
        addField('sae_rombel', 'Kelas / Rombel');
        addField('text', 'Pertanyaan Anda');
    } else {
        renderQuestions();
    }

    // Inisialisasi status filter target rombel
    toggleRombelTarget();

    // Event listener submit form
    if (formEl) {
        formEl.addEventListener('submit', function (e) {
            if (questions.length === 0) {
                e.preventDefault();
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: 'Pertanyaan Kosong',
                        text: 'Harap tambahkan setidaknya 1 pertanyaan ke dalam formulir.',
                        icon: 'warning',
                        confirmButtonColor: 'var(--primary, #0284c7)',
                        confirmButtonText: 'Mengerti'
                    });
                } else {
                    alert('Harap tambahkan setidaknya 1 pertanyaan ke dalam formulir.');
                }
                return;
            }

            const inputHidden = document.getElementById('skemaJsonInput');
            if (inputHidden) {
                inputHidden.value = JSON.stringify(questions);
            }
        });
    }
});

/**
 * Toggle visibilitas target rombel jika peran dipilih adalah siswa serta sinkronisasi opsi publik
 */
window.toggleRombelTarget = function () {
    const select = document.getElementById('targetPeranSelect');
    const box = document.getElementById('targetRombelBox');
    const isPublicCheck = document.getElementById('isPublic');
    const targetHint = document.getElementById('targetRoleHint');

    if (select) {
        if (box) {
            box.style.display = (select.value === 'peserta_didik') ? 'block' : 'none';
        }

        if (isPublicCheck) {
            if (select.value === 'publik') {
                isPublicCheck.checked = true;
            }
        }

        if (targetHint) {
            if (select.value === 'publik') {
                targetHint.innerHTML = '<i class="fas fa-globe text-info me-1"></i> Terbuka untuk umum tanpa login. Responden tamu dapat mengetik nama dan identitas secara mandiri.';
            } else if (select.value === 'peserta_didik') {
                targetHint.innerHTML = '<i class="fas fa-user-graduate text-success me-1"></i> Khusus Siswa aktif. Responden wajib login SAE, nama, NISN & kelas terisi otomatis.';
            } else if (select.value === 'guru') {
                targetHint.innerHTML = '<i class="fas fa-chalkboard-user text-primary me-1"></i> Khusus Pendidik / Guru. Responden wajib login akun SAE.';
            } else if (select.value === 'tendik') {
                targetHint.innerHTML = '<i class="fas fa-id-badge text-warning me-1"></i> Khusus Tenaga Kependidikan. Responden wajib login akun SAE.';
            } else {
                targetHint.innerHTML = '<i class="fas fa-users text-primary me-1"></i> Semua akun pengguna SAE (Siswa, Guru, Tendik, Admin) wajib login.';
            }
        }
    }
};

/**
 * Generate ID unik untuk pertanyaan
 */
function generateId() {
    return 'field_' + Math.random().toString(36).substring(2, 9);
}

/**
 * Tambah pertanyaan baru dari preset
 */
window.addField = function (type, defaultLabel) {
    const isSae = type.startsWith('sae_');
    const newField = {
        id: generateId(),
        type: type,
        label: defaultLabel,
        placeholder: isSae ? 'Terisi otomatis dari akun SAE' : '',
        description: '',
        required: isSae ? true : false,
        options: ['select', 'radio', 'checkbox'].includes(type) ? ['Pilihan 1', 'Pilihan 2'] : (type === 'rating' ? ['1', '2', '3', '4', '5'] : [])
    };

    questions.push(newField);
    renderQuestions();

    // Scroll otomatis ke card pertanyaan baru
    setTimeout(() => {
        const cards = document.querySelectorAll('.question-card');
        if (cards.length > 0) {
            cards[cards.length - 1].scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    }, 50);
};

/**
 * Hapus pertanyaan
 */
window.removeField = function (index) {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Hapus Pertanyaan?',
            text: `Hapus pertanyaan "${questions[index].label || 'ini'}"?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Hapus',
            cancelButtonText: 'Batal'
        }).then((res) => {
            if (res.isConfirmed) {
                questions.splice(index, 1);
                renderQuestions();
            }
        });
    } else {
        if (confirm('Hapus pertanyaan ini?')) {
            questions.splice(index, 1);
            renderQuestions();
        }
    }
};

/**
 * Naikkan urutan pertanyaan
 */
window.moveUp = function (index) {
    if (index > 0) {
        const temp = questions[index];
        questions[index] = questions[index - 1];
        questions[index - 1] = temp;
        renderQuestions();
    }
};

/**
 * Turunkan urutan pertanyaan
 */
window.moveDown = function (index) {
    if (index < questions.length - 1) {
        const temp = questions[index];
        questions[index] = questions[index + 1];
        questions[index + 1] = temp;
        renderQuestions();
    }
};

/**
 * Tambah opsi pilihan pada radio/checkbox/dropdown
 */
window.addOption = function (qIndex) {
    if (!questions[qIndex].options) questions[qIndex].options = [];
    questions[qIndex].options.push('Pilihan ' + (questions[qIndex].options.length + 1));
    renderQuestions();
};

/**
 * Hapus opsi pilihan
 */
window.removeOption = function (qIndex, optIndex) {
    if (questions[qIndex].options.length > 1) {
        questions[qIndex].options.splice(optIndex, 1);
        renderQuestions();
    }
};

/**
 * Perbarui atribut pertanyaan
 */
window.updateFieldProperty = function (index, prop, value) {
    if (questions[index]) {
        questions[index][prop] = value;
    }
};

/**
 * Perbarui isi teks opsi pilihan
 */
window.updateOption = function (qIndex, optIndex, value) {
    if (questions[qIndex] && questions[qIndex].options) {
        questions[qIndex].options[optIndex] = value;
    }
};

/**
 * Render ulang seluruh daftar pertanyaan ke DOM
 */
function renderQuestions() {
    const container = document.getElementById('questionsContainer');
    const emptyPlaceholder = document.getElementById('emptyQuestionsPlaceholder');
    const countBadge = document.getElementById('qCountBadge');

    if (countBadge) {
        countBadge.textContent = questions.length + ' Pertanyaan';
    }

    if (!container) return;

    if (questions.length === 0) {
        container.innerHTML = '';
        if (emptyPlaceholder) emptyPlaceholder.style.display = 'block';
        return;
    }

    if (emptyPlaceholder) emptyPlaceholder.style.display = 'none';
    let html = '';

    questions.forEach((q, idx) => {
        const isSae = q.type.startsWith('sae_');
        const hasOptions = ['select', 'radio', 'checkbox'].includes(q.type);

        html += `
        <div class="question-card">
            <div class="question-card-header">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span style="font-size: 0.85rem; font-weight: 800; color: var(--text-muted, #64748b);">#${idx + 1}</span>
                    <span class="q-type-badge ${isSae ? 'q-type-sae' : ''}">
                        <i class="fas ${getTypeIcon(q.type)}"></i> ${escapeHtml(q.type.replace('_', ' '))}
                    </span>
                    ${isSae ? '<span style="font-size: 0.72rem; color: #f59e0b; font-weight: 600;"><i class="fas fa-lock me-1"></i>Data Otomatis SAE</span>' : ''}
                </div>
                <div class="q-actions">
                    <button type="button" class="btn-action-mini" title="Pindah Naik" onclick="moveUp(${idx})" ${idx === 0 ? 'disabled' : ''}>
                        <i class="fas fa-arrow-up"></i>
                    </button>
                    <button type="button" class="btn-action-mini" title="Pindah Turun" onclick="moveDown(${idx})" ${idx === questions.length - 1 ? 'disabled' : ''}>
                        <i class="fas fa-arrow-down"></i>
                    </button>
                    <button type="button" class="btn-action-mini danger" title="Hapus Pertanyaan" onclick="removeField(${idx})">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr auto; gap: 16px; margin-bottom: 12px; align-items: end;">
                <div class="form-group" style="margin: 0;">
                    <label class="form-label" style="font-size: 0.8rem; margin-bottom: 4px;">Pertanyaan / Label Kolom <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" value="${escapeHtml(q.label)}" 
                           oninput="updateFieldProperty(${idx}, 'label', this.value)" required>
                </div>
                <div class="form-group" style="margin: 0; min-width: 140px;">
                    <label class="form-label" style="font-size: 0.8rem; margin-bottom: 4px;">Sifat Input</label>
                    <div class="form-check" style="margin: 0; padding-top: 6px;">
                        <input class="form-check-input" type="checkbox" id="req_${idx}" ${q.required ? 'checked' : ''} ${isSae ? 'disabled' : ''}
                               onchange="updateFieldProperty(${idx}, 'required', this.checked)">
                        <label class="form-check-label" for="req_${idx}">
                            Wajib Diisi
                        </label>
                    </div>
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 12px;">
                <label class="form-label" style="font-size: 0.8rem; margin-bottom: 4px;">Petunjuk Tambahan / Deskripsi (Opsional)</label>
                <input type="text" class="form-control" value="${escapeHtml(q.description || '')}" 
                       placeholder="Contoh: Pilih salah satu yang paling sesuai..."
                       oninput="updateFieldProperty(${idx}, 'description', this.value)">
            </div>

            ${hasOptions ? `
                <div class="options-list">
                    <span style="font-size: 0.76rem; font-weight: 700; color: var(--text-muted);">Daftar Pilihan Responden:</span>
                    ${(q.options || []).map((opt, optIdx) => `
                        <div class="option-row">
                            <i class="fas ${q.type === 'radio' ? 'fa-circle-dot' : (q.type === 'checkbox' ? 'fa-square-check' : 'fa-caret-right')}" style="font-size: 0.8rem; color: var(--text-muted);"></i>
                            <input type="text" class="form-control" value="${escapeHtml(opt)}" 
                                   oninput="updateOption(${idx}, ${optIdx}, this.value)" style="max-width: 360px;">
                            ${q.options.length > 1 ? `
                                <button type="button" class="btn-action-mini danger" title="Hapus Pilihan" onclick="removeOption(${idx}, ${optIdx})">
                                    <i class="fas fa-times"></i>
                                </button>
                            ` : ''}
                        </div>
                    `).join('')}
                    <div>
                        <button type="button" class="btn-add-choice" onclick="addOption(${idx})">
                            <i class="fas fa-plus"></i> Tambah Pilihan
                        </button>
                    </div>
                </div>
            ` : ''}
        </div>
        `;
    });

    container.innerHTML = html;
}

function getTypeIcon(type) {
    switch (type) {
        case 'text':
            return 'fa-font';
        case 'textarea':
            return 'fa-align-left';
        case 'radio':
            return 'fa-circle-dot';
        case 'checkbox':
            return 'fa-square-check';
        case 'select':
            return 'fa-chevron-down';
        case 'rating':
            return 'fa-star text-warning';
        case 'number':
            return 'fa-hashtag';
        case 'date':
            return 'fa-calendar';
        case 'file':
            return 'fa-paperclip';
        case 'sae_nama':
            return 'fa-user-check';
        case 'sae_nisn':
            return 'fa-fingerprint';
        case 'sae_rombel':
            return 'fa-school';
        default:
            return 'fa-pen';
    }
}

function escapeHtml(text) {
    if (!text) return '';
    return String(text)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}
