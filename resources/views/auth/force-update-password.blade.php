@extends('layouts.app')

@section('title', 'Pembaruan Password Wajib — SAE (Sistem Aplikasi Edukasi)')

@section('content')
    <div class="container"
        style="min-height: 85vh; display: flex; align-items: center; justify-content: center; padding: 40px 16px;">
        <div
            style="width: 100%; max-width: 480px; background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 20px; padding: 34px 28px; box-shadow: var(--card-shadow); backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); position: relative; overflow: hidden;">
            <div
                style="position: absolute; top: -50px; right: -50px; width: 150px; height: 150px; background: radial-gradient(circle, rgba(239,68,68,0.2) 0%, transparent 70%); border-radius: 50%; pointer-events: none;">
            </div>

            <!-- Header Title -->
            <div style="text-align: center; margin-bottom: 22px;">
                <div
                    style="width: 54px; height: 54px; border-radius: 16px; background: rgba(239,68,68,0.12); color: #ef4444; display: inline-flex; align-items: center; justify-content: center; font-size: 1.5rem; margin-bottom: 12px; border: 1px solid rgba(239,68,68,0.25); box-shadow: 0 4px 12px rgba(239,68,68,0.15);">
                    <i class="fas fa-shield-halved"></i>
                </div>
                <h2 style="font-size: 1.35rem; font-weight: 800; color: var(--text-color); margin-bottom: 4px;">
                    Pembaruan Password Wajib
                </h2>
                <p style="font-size: 0.82rem; color: var(--text-muted); line-height: 1.4;">
                    Keamanan Akun Peserta Didik
                </p>
            </div>

            <!-- Student Account Details Banner -->
            <div
                style="background: rgba(99,102,241,0.08); border: 1px solid rgba(99,102,241,0.2); border-radius: 12px; padding: 12px 14px; margin-bottom: 18px; display: flex; align-items: center; justify-content: space-between; gap: 10px;">
                <div>
                    <div style="font-size: 0.72rem; text-transform: uppercase; color: var(--text-muted); letter-spacing: 0.05em; font-weight: 700;">Peserta Didik</div>
                    <div style="font-size: 0.92rem; font-weight: 700; color: var(--text-color);">{{ $forceData['nama'] ?? 'Peserta Didik' }}</div>
                </div>
                <div style="text-align: right;">
                    <div style="font-size: 0.72rem; text-transform: uppercase; color: var(--text-muted); letter-spacing: 0.05em; font-weight: 700;">NISN</div>
                    <div style="font-size: 0.88rem; font-weight: 700; color: var(--primary, #3b82f6); font-family: monospace;">{{ $forceData['nisn'] ?? '-' }}</div>
                </div>
            </div>

            <!-- Alert Notice -->
            <div
                style="background: rgba(245,158,11,0.1); border: 1px solid rgba(245,158,11,0.3); color: #d97706; padding: 10px 14px; border-radius: 12px; font-size: 0.79rem; line-height: 1.4; margin-bottom: 20px; display: flex; align-items: flex-start; gap: 10px;">
                <i class="fas fa-triangle-exclamation" style="margin-top: 2px; font-size: 0.95rem; flex-shrink: 0;"></i>
                <div>
                    <strong>Perhatian:</strong> Password akun Anda terdeteksi menggunakan password default (NISN). Demi melindungi privasi data &amp; nilai akademik, Anda <strong>wajib</strong> membuat password baru sebelum dapat mengakses portal.
                </div>
            </div>

            @if ($errors->any())
                <div
                    style="background: rgba(239,68,68,0.1); border: 1px solid #ef4444; color: #ef4444; padding: 10px 14px; border-radius: 10px; font-size: 0.82rem; margin-bottom: 20px;">
                    <i class="fas fa-circle-exclamation"></i> {{ $errors->first() }}
                </div>
            @endif

            <form action="{{ route('auth.force-update-password.post') }}" method="POST" id="forcePasswordForm" novalidate>
                @csrf

                <!-- Password Baru Input -->
                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-size: 0.8rem; font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">
                        <i class="fas fa-lock"></i> Password Baru
                    </label>
                    <div class="input-group"
                        style="background: var(--input-bg); border: 1px solid var(--border-color); border-radius: 12px; padding: 0; display: flex; align-items: center; overflow: hidden; transition: border-color 0.2s, box-shadow 0.2s;"
                        id="newPassWrap">
                        <input type="password" name="password" id="newPassword" required placeholder="Buat password baru..."
                            autocomplete="new-password"
                            style="flex: 1; border: none; background: transparent; padding: 12px 14px; color: var(--text-color); font-size: 0.9rem; outline: none;">
                        <button type="button" onclick="togglePasswordVisibility('newPassword', 'eyeIconNew')"
                            style="border: none; background: transparent; color: var(--text-muted); padding: 0 14px; cursor: pointer; height: 100%;">
                            <i class="fas fa-eye" id="eyeIconNew"></i>
                        </button>
                    </div>
                </div>

                <!-- Live Parameter Checklist -->
                <div
                    style="background: var(--input-bg, rgba(0,0,0,0.03)); border: 1px solid var(--border-color); border-radius: 14px; padding: 14px 16px; margin-bottom: 18px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                        <span style="font-size: 0.74rem; font-weight: 700; color: var(--text-color); text-transform: uppercase; letter-spacing: 0.04em;">
                            <i class="fas fa-list-check text-primary me-1"></i> Parameter Keamanan Password:
                        </span>
                        <span id="strengthLabel" style="font-size: 0.72rem; font-weight: 700; color: var(--text-muted);">
                            Belum Diisi
                        </span>
                    </div>

                    <!-- Strength Progress Bar -->
                    <div style="height: 5px; width: 100%; background: var(--border-color); border-radius: 10px; overflow: hidden; margin-bottom: 12px;">
                        <div id="strengthBar" style="height: 100%; width: 0%; transition: width 0.3s ease, background-color 0.3s ease; border-radius: 10px;"></div>
                    </div>

                    <!-- Checklist Items -->
                    <div style="display: grid; grid-template-columns: 1fr; gap: 7px; font-size: 0.77rem;">
                        <div id="param-len" class="param-item" style="display: flex; align-items: center; gap: 8px; color: var(--text-muted); transition: color 0.2s;">
                            <i class="far fa-circle" id="icon-len"></i>
                            <span>Panjang <strong>8 - 15 karakter</strong></span>
                        </div>
                        <div id="param-case" class="param-item" style="display: flex; align-items: center; gap: 8px; color: var(--text-muted); transition: color 0.2s;">
                            <i class="far fa-circle" id="icon-case"></i>
                            <span>Kombinasi Huruf Besar &amp; Kecil (<strong>A-z</strong>)</span>
                        </div>
                        <div id="param-num" class="param-item" style="display: flex; align-items: center; gap: 8px; color: var(--text-muted); transition: color 0.2s;">
                            <i class="far fa-circle" id="icon-num"></i>
                            <span>Mengandung minimal 1 angka (<strong>0-9</strong>)</span>
                        </div>
                        <div id="param-sym" class="param-item" style="display: flex; align-items: center; gap: 8px; color: var(--text-muted); transition: color 0.2s;">
                            <i class="far fa-circle" id="icon-sym"></i>
                            <span>Karakter khusus / simbol (<strong>!@#$%^&amp;*</strong> dll)</span>
                        </div>
                        <div id="param-space" class="param-item" style="display: flex; align-items: center; gap: 8px; color: var(--text-muted); transition: color 0.2s;">
                            <i class="far fa-circle" id="icon-space"></i>
                            <span><strong>Tanpa spasi</strong> sama sekali</span>
                        </div>
                        <div id="param-nisn" class="param-item" style="display: flex; align-items: center; gap: 8px; color: var(--text-muted); transition: color 0.2s;">
                            <i class="far fa-circle" id="icon-nisn"></i>
                            <span>Tidak boleh sama dengan NISN lama</span>
                        </div>
                    </div>
                </div>

                <!-- Konfirmasi Password Input -->
                <div style="margin-bottom: 24px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                        <label style="font-size: 0.8rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0;">
                            <i class="fas fa-lock-open"></i> Konfirmasi Password Baru
                        </label>
                        <span id="matchFeedback" style="font-size: 0.72rem; font-weight: 700; color: var(--text-muted);">
                            Belum cocok
                        </span>
                    </div>
                    <div class="input-group"
                        style="background: var(--input-bg); border: 1px solid var(--border-color); border-radius: 12px; padding: 0; display: flex; align-items: center; overflow: hidden; transition: border-color 0.2s, box-shadow 0.2s;"
                        id="confirmPassWrap">
                        <input type="password" name="password_confirmation" id="confirmPassword" required
                            placeholder="Ulangi password baru..." autocomplete="new-password"
                            style="flex: 1; border: none; background: transparent; padding: 12px 14px; color: var(--text-color); font-size: 0.9rem; outline: none;">
                        <button type="button" onclick="togglePasswordVisibility('confirmPassword', 'eyeIconConfirm')"
                            style="border: none; background: transparent; color: var(--text-muted); padding: 0 14px; cursor: pointer; height: 100%;">
                            <i class="fas fa-eye" id="eyeIconConfirm"></i>
                        </button>
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="submit" id="btnSubmitPassword" class="btn btn-primary" disabled
                    style="width: 100%; padding: 13px; font-weight: 700; font-size: 0.95rem; justify-content: center; box-shadow: 0 4px 16px rgba(99,102,241,0.35); opacity: 0.6; cursor: not-allowed; transition: all 0.2s;">
                    <i class="fas fa-key"></i> Simpan Password Baru &amp; Selesai
                </button>
            </form>

            <div style="margin-top: 20px; text-align: center;">
                <a href="{{ route('auth.cancel-force-update') }}" style="color: var(--text-muted); font-size: 0.8rem; text-decoration: none;">
                    <i class="fas fa-arrow-left"></i> Batal &amp; Kembali ke Halaman Login
                </a>
            </div>
        </div>
    </div>

    <script>
        const studentNisn = "{{ $forceData['nisn'] ?? '' }}";
        const newPassInput = document.getElementById('newPassword');
        const confirmPassInput = document.getElementById('confirmPassword');
        const newPassWrap = document.getElementById('newPassWrap');
        const confirmPassWrap = document.getElementById('confirmPassWrap');
        const strengthBar = document.getElementById('strengthBar');
        const strengthLabel = document.getElementById('strengthLabel');
        const matchFeedback = document.getElementById('matchFeedback');
        const btnSubmit = document.getElementById('btnSubmitPassword');
        const form = document.getElementById('forcePasswordForm');

        function togglePasswordVisibility(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        function setParamStatus(id, isValid, isNegative = false) {
            const item = document.getElementById('param-' + id);
            const icon = document.getElementById('icon-' + id);
            if (!item || !icon) return;

            if (isValid) {
                item.style.color = '#10b981';
                icon.className = 'fas fa-check-circle';
                icon.style.color = '#10b981';
            } else if (isNegative) {
                item.style.color = '#ef4444';
                icon.className = 'fas fa-circle-xmark';
                icon.style.color = '#ef4444';
            } else {
                item.style.color = 'var(--text-muted)';
                icon.className = 'far fa-circle';
                icon.style.color = 'var(--text-muted)';
            }
        }

        function evaluatePassword() {
            const val = newPassInput.value;
            const confirmVal = confirmPassInput.value;

            // 1. Min 8 & Max 15
            const ruleLen = val.length >= 8 && val.length <= 15;
            // 2. A-Z and a-z
            const ruleCase = /[A-Z]/.test(val) && /[a-z]/.test(val);
            // 3. 0-9
            const ruleNum = /[0-9]/.test(val);
            // 4. Special symbol !@#
            const ruleSym = /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?~`]/.test(val);
            // 5. No space
            const hasSpace = /\s/.test(val);
            const ruleSpace = val.length > 0 && !hasSpace;
            // 6. Not same as NISN
            const isSameNisn = studentNisn && val === studentNisn;
            const ruleNisn = val.length > 0 && !isSameNisn;

            // Update UI status
            setParamStatus('len', ruleLen, val.length > 15);
            setParamStatus('case', ruleCase);
            setParamStatus('num', ruleNum);
            setParamStatus('sym', ruleSym);
            setParamStatus('space', ruleSpace, hasSpace);
            setParamStatus('nisn', ruleNisn, isSameNisn);

            // Calculate strength score
            let score = 0;
            if (ruleLen) score++;
            if (ruleCase) score++;
            if (ruleNum) score++;
            if (ruleSym) score++;
            if (ruleSpace) score++;

            if (val.length === 0) {
                strengthBar.style.width = '0%';
                strengthBar.style.backgroundColor = 'transparent';
                strengthLabel.textContent = 'Belum Diisi';
                strengthLabel.style.color = 'var(--text-muted)';
                newPassWrap.style.borderColor = 'var(--border-color)';
                newPassWrap.style.boxShadow = 'none';
            } else if (score <= 2) {
                strengthBar.style.width = '30%';
                strengthBar.style.backgroundColor = '#ef4444';
                strengthLabel.textContent = 'Lemah';
                strengthLabel.style.color = '#ef4444';
                newPassWrap.style.borderColor = '#ef4444';
                newPassWrap.style.boxShadow = '0 0 0 2px rgba(239,68,68,0.15)';
            } else if (score <= 4) {
                strengthBar.style.width = '70%';
                strengthBar.style.backgroundColor = '#f59e0b';
                strengthLabel.textContent = 'Cukup';
                strengthLabel.style.color = '#f59e0b';
                newPassWrap.style.borderColor = '#f59e0b';
                newPassWrap.style.boxShadow = '0 0 0 2px rgba(245,158,11,0.15)';
            } else {
                strengthBar.style.width = '100%';
                strengthBar.style.backgroundColor = '#10b981';
                strengthLabel.textContent = 'Sangat Kuat';
                strengthLabel.style.color = '#10b981';
                newPassWrap.style.borderColor = '#10b981';
                newPassWrap.style.boxShadow = '0 0 0 2px rgba(16,185,129,0.15)';
            }

            // Confirm Password Evaluation
            const isMatch = confirmVal.length > 0 && confirmVal === val;
            if (confirmVal.length === 0) {
                matchFeedback.textContent = 'Belum diisi';
                matchFeedback.style.color = 'var(--text-muted)';
                confirmPassWrap.style.borderColor = 'var(--border-color)';
                confirmPassWrap.style.boxShadow = 'none';
            } else if (isMatch) {
                matchFeedback.textContent = 'Konfirmasi cocok';
                matchFeedback.style.color = '#10b981';
                confirmPassWrap.style.borderColor = '#10b981';
                confirmPassWrap.style.boxShadow = '0 0 0 2px rgba(16,185,129,0.15)';
            } else {
                matchFeedback.textContent = 'Belum cocok';
                matchFeedback.style.color = '#ef4444';
                confirmPassWrap.style.borderColor = '#ef4444';
                confirmPassWrap.style.boxShadow = '0 0 0 2px rgba(239,68,68,0.15)';
            }

            // Enable or disable submit button
            const allPassed = ruleLen && ruleCase && ruleNum && ruleSym && ruleSpace && ruleNisn && isMatch;
            if (allPassed) {
                btnSubmit.removeAttribute('disabled');
                btnSubmit.style.opacity = '1';
                btnSubmit.style.cursor = 'pointer';
            } else {
                btnSubmit.setAttribute('disabled', 'disabled');
                btnSubmit.style.opacity = '0.6';
                btnSubmit.style.cursor = 'not-allowed';
            }

            return allPassed;
        }

        newPassInput.addEventListener('input', evaluatePassword);
        confirmPassInput.addEventListener('input', evaluatePassword);

        // Blokir pengetikan spasi langsung pada input password
        newPassInput.addEventListener('keydown', (e) => {
            if (e.key === ' ' || e.code === 'Space') {
                e.preventDefault();
                setParamStatus('space', false, true);
            }
        });
        confirmPassInput.addEventListener('keydown', (e) => {
            if (e.key === ' ' || e.code === 'Space') {
                e.preventDefault();
            }
        });

        form.addEventListener('submit', (e) => {
            if (!evaluatePassword()) {
                e.preventDefault();
                newPassInput.focus();
            }
        });
    </script>
@endsection
