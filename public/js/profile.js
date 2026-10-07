/**
 * SAE - User Profile Management Script
 * Handles password strength analysis, visibility toggles, and photo uploads.
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Show/Hide Password Eye Toggle
    document.querySelectorAll('.btn-toggle-eye').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('data-target');
            const input = document.getElementById(targetId);
            const icon = this.querySelector('i');

            if (input) {
                if (input.type === 'password') {
                    input.type = 'text';
                    if (icon) {
                        icon.classList.remove('fa-eye');
                        icon.classList.add('fa-eye-slash');
                    }
                } else {
                    input.type = 'password';
                    if (icon) {
                        icon.classList.remove('fa-eye-slash');
                        icon.classList.add('fa-eye');
                    }
                }
            }
        });
    });

    // 2. Password Live Validation & Strength Meter
    const newPwInput = document.getElementById('new_password');
    const confirmPwInput = document.getElementById('password_confirmation');
    const strengthBar = document.getElementById('strengthBar');
    const strengthLabel = document.getElementById('strengthLabel');

    const ruleLength = document.getElementById('rule-length');
    const ruleUpper = document.getElementById('rule-upper');
    const ruleLower = document.getElementById('rule-lower');
    const ruleNumber = document.getElementById('rule-number');
    const ruleSymbol = document.getElementById('rule-symbol');
    const ruleSpace = document.getElementById('rule-space');
    const ruleMatch = document.getElementById('rule-match');

    const updateRuleState = (el, isValid) => {
        if (!el) return;
        const icon = el.querySelector('i');
        if (isValid) {
            el.classList.add('valid');
            el.classList.remove('invalid');
            if (icon) icon.className = 'fas fa-circle-check';
        } else {
            el.classList.remove('valid');
            el.classList.add('invalid');
            if (icon) icon.className = 'fas fa-circle-dot';
        }
    };

    const evaluatePassword = () => {
        const val = newPwInput ? newPwInput.value || '' : '';
        const confirmVal = confirmPwInput ? confirmPwInput.value || '' : '';

        // Rules
        const isLengthValid = val.length >= 8 && val.length <= 15;
        const isUpperValid = /[A-Z]/.test(val);
        const isLowerValid = /[a-z]/.test(val);
        const isNumberValid = /[0-9]/.test(val);
        const isSymbolValid = /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?~`]/.test(val);
        const isNoSpaceValid = val.length > 0 && !/\s/.test(val);
        const isMatchValid = val.length > 0 && val === confirmVal;

        updateRuleState(ruleLength, isLengthValid);
        updateRuleState(ruleUpper, isUpperValid);
        updateRuleState(ruleLower, isLowerValid);
        updateRuleState(ruleNumber, isNumberValid);
        updateRuleState(ruleSymbol, isSymbolValid);
        updateRuleState(ruleSpace, isNoSpaceValid);
        updateRuleState(ruleMatch, isMatchValid);

        let score = 0;
        if (val.length >= 8) score++;
        if (val.length >= 10 && val.length <= 15) score++;
        if (isUpperValid) score++;
        if (isLowerValid) score++;
        if (isNumberValid) score++;
        if (isSymbolValid) score++;
        if (isNoSpaceValid) score++;

        if (!strengthBar || !strengthLabel) return;

        if (val.length === 0) {
            strengthBar.style.width = '0%';
            strengthBar.style.backgroundColor = 'transparent';
            strengthLabel.textContent = 'Belum Terisi';
            strengthLabel.style.color = 'var(--text-muted)';
        } else if (score <= 3) {
            strengthBar.style.width = '25%';
            strengthBar.style.backgroundColor = '#ef4444';
            strengthLabel.textContent = 'Lemah';
            strengthLabel.style.color = '#ef4444';
        } else if (score <= 5) {
            strengthBar.style.width = '60%';
            strengthBar.style.backgroundColor = '#f59e0b';
            strengthLabel.textContent = 'Sedang';
            strengthLabel.style.color = '#f59e0b';
        } else if (score < 7) {
            strengthBar.style.width = '85%';
            strengthBar.style.backgroundColor = '#3b82f6';
            strengthLabel.textContent = 'Kuat';
            strengthLabel.style.color = '#3b82f6';
        } else {
            strengthBar.style.width = '100%';
            strengthBar.style.backgroundColor = '#10b981';
            strengthLabel.textContent = 'Sangat Kuat';
            strengthLabel.style.color = '#10b981';
        }
    };

    if (newPwInput && confirmPwInput) {
        newPwInput.addEventListener('input', evaluatePassword);
        confirmPwInput.addEventListener('input', evaluatePassword);
    }

    // 3. Photo Upload & Preview
    let selectedFotoFile = null;
    const modalEl = document.getElementById('fotoUploadModal');
    const currentFotoUrl = modalEl?.getAttribute('data-current-foto') || '';

    window.openUploadFotoModal = function() {
        if (!modalEl) return;
        modalEl.style.display = 'flex';
        resetUploadFotoForm();
    };

    window.closeUploadFotoModal = function() {
        if (modalEl) modalEl.style.display = 'none';
    };

    function resetUploadFotoForm() {
        selectedFotoFile = null;
        const fileInput = document.getElementById('modalFotoFileInput');
        if (fileInput) fileInput.value = '';

        const previewImg = document.getElementById('modalFotoPreviewImg');
        const placeholder = document.getElementById('modalFotoPreviewPlaceholder');
        const specs = document.getElementById('modalFotoFileSpecs');
        const btnSimpan = document.getElementById('btnSimpanFotoGtk');

        if (btnSimpan) btnSimpan.disabled = true;

        if (currentFotoUrl) {
            if (previewImg) {
                previewImg.src = currentFotoUrl;
                previewImg.style.display = 'block';
            }
            if (placeholder) placeholder.style.display = 'none';
        } else {
            if (previewImg) {
                previewImg.src = '';
                previewImg.style.display = 'none';
            }
            if (placeholder) placeholder.style.display = 'flex';
        }

        if (specs) {
            specs.style.display = 'none';
            specs.textContent = '-';
        }
    }

    const fileInput = document.getElementById('modalFotoFileInput');
    const dropZone = document.getElementById('modalFotoDropZone');
    const previewImg = document.getElementById('modalFotoPreviewImg');
    const placeholder = document.getElementById('modalFotoPreviewPlaceholder');
    const specs = document.getElementById('modalFotoFileSpecs');
    const btnSimpan = document.getElementById('btnSimpanFotoGtk');

    if (fileInput) {
        fileInput.addEventListener('change', (e) => {
            const file = e.target.files[0];
            handleSelectedFile(file);
        });
    }

    if (dropZone) {
        ['dragenter', 'dragover'].forEach(eventName => {
            dropZone.addEventListener(eventName, (e) => {
                e.preventDefault();
                dropZone.style.borderColor = 'var(--primary)';
                dropZone.style.background = 'rgba(99, 102, 241, 0.08)';
            });
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, (e) => {
                e.preventDefault();
                dropZone.style.borderColor = 'rgba(99, 102, 241, 0.4)';
                dropZone.style.background = 'rgba(255, 255, 255, 0.01)';
            });
        });

        dropZone.addEventListener('drop', (e) => {
            const dt = e.dataTransfer;
            if (dt && dt.files && dt.files.length > 0) {
                handleSelectedFile(dt.files[0]);
            }
        });
    }

    function handleSelectedFile(file) {
        if (!file) return;

        const validTypes = ['image/png', 'image/jpeg', 'image/jpg'];
        if (!validTypes.includes(file.type.toLowerCase())) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'warning',
                    title: 'Format Tidak Sesuai',
                    text: 'Hanya format gambar PNG, JPG, atau JPEG yang diperbolehkan.',
                });
            } else {
                alert('Hanya format gambar PNG, JPG, atau JPEG yang diperbolehkan.');
            }
            return;
        }

        if (file.size > 5 * 1024 * 1024) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'warning',
                    title: 'Ukuran Melebihi Batas',
                    text: 'Ukuran file tidak boleh melebihi 5 MB.',
                });
            } else {
                alert('Ukuran file tidak boleh melebihi 5 MB.');
            }
            return;
        }

        selectedFotoFile = file;

        const reader = new FileReader();
        reader.onload = (e) => {
            if (previewImg) {
                previewImg.src = e.target.result;
                previewImg.style.display = 'block';
            }
            if (placeholder) placeholder.style.display = 'none';

            if (specs) {
                const sizeKb = (file.size / 1024).toFixed(1);
                specs.textContent = `${file.name} (${sizeKb} KB)`;
                specs.style.display = 'block';
            }

            if (btnSimpan) btnSimpan.disabled = false;
        };
        reader.readAsDataURL(file);
    }

    window.submitUploadFoto = function() {
        if (!selectedFotoFile) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'info',
                    title: 'Pilih File',
                    text: 'Silakan pilih file foto terlebih dahulu.',
                });
            } else {
                alert('Silakan pilih file foto terlebih dahulu.');
            }
            return;
        }

        const originalText = btnSimpan ? btnSimpan.innerHTML : '';
        if (btnSimpan) {
            btnSimpan.disabled = true;
            btnSimpan.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <span>Mengunggah...</span>';
        }

        const uploadUrl = modalEl?.getAttribute('data-upload-url') || '/dashboard/profile/foto';
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        const formData = new FormData();
        formData.append('_token', csrfToken);
        formData.append('foto', selectedFotoFile);

        fetch(uploadUrl, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
            body: formData,
        })
        .then(res => res.json().then(data => ({
            status: res.status,
            ok: res.ok,
            data
        })))
        .then(({ ok, data }) => {
            if (!ok || data.status !== 'success') {
                throw new Error(data.message || 'Gagal mengunggah foto profil.');
            }

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: data.message,
                    timer: 1600,
                    showConfirmButton: false,
                }).then(() => {
                    window.location.reload();
                });
            } else {
                alert(data.message);
                window.location.reload();
            }
        })
        .catch(err => {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Terjadi Kesalahan',
                    text: err.message,
                });
            } else {
                alert(err.message);
            }
        })
        .finally(() => {
            if (btnSimpan) {
                btnSimpan.disabled = false;
                btnSimpan.innerHTML = originalText;
            }
        });
    };

    window.confirmDeleteFoto = function() {
        const deleteUrl = modalEl?.getAttribute('data-delete-url') || '/dashboard/profile/foto';
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        const doDelete = () => {
            fetch(deleteUrl, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
            })
            .then(r => r.json().then(data => ({
                status: r.status,
                ok: r.ok,
                data
            })))
            .then(({ ok, data }) => {
                if (!ok || data.status !== 'success') {
                    throw new Error(data.message || 'Gagal menghapus foto.');
                }

                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Terhapus!',
                        text: data.message,
                        timer: 1500,
                        showConfirmButton: false,
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    alert(data.message);
                    window.location.reload();
                }
            })
            .catch(err => {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: err.message,
                    });
                } else {
                    alert(err.message);
                }
            });
        };

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Hapus Pasfoto?',
                text: 'Foto profil Anda akan dihapus dan dikembalikan ke avatar bawaan.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#64748b',
                confirmButtonText: '<i class="fas fa-trash-can me-1"></i> Ya, Hapus',
                cancelButtonText: 'Batal',
            }).then((res) => {
                if (res.isConfirmed) {
                    doDelete();
                }
            });
        } else if (confirm('Hapus Pasfoto? Foto profil Anda akan dikembalikan ke avatar bawaan.')) {
            doDelete();
        }
    };
});
